<?php

namespace App\Http\Controllers;

use App\Mail\TanggapanMail;
use App\Models\Aspirasi;
use App\Models\KategoriAspirasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AspirasiController extends Controller
{
    /**
     * Display a listing of aspirasi.
     */
    public function index(Request $request): View 
{
    $user = Auth::user();
    $query = Aspirasi::with(['kategoriAspirasi.opd', 'admin']);

    // 🔒 Filter hanya jika bukan super-admin atau admin-bappeda
    if (!in_array($user->role->slug, ['super-admin', 'admin-bappeda'])) {
        $userOpdId = $user->opd_id;

        // Filter berdasarkan relasi ke OPD melalui kategoriAspirasi
        $query->whereHas('kategoriAspirasi.opd', function ($q) use ($userOpdId) {
            $q->where('id', $userOpdId);
        });
    }

    // Filter berdasarkan parameter
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('jenis_aspirasi')) {
        $query->where('jenis_aspirasi', $request->jenis_aspirasi);
    }

    if ($request->filled('kategori')) {
        $query->where('kategori_aspirasi_id', $request->kategori);
    }

    // Search
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('nomor_tiket', 'like', "%$search%")
              ->orWhere('nama_pengirim', 'like', "%$search%")
              ->orWhere('email', 'like', "%$search%")
              ->orWhere('judul_aspirasi', 'like', "%$search%")
              ->orWhere('isi_aspirasi', 'like', "%$search%");
        });
    }

    // Filter tanggal
    if ($request->filled('tanggal_mulai')) {
        $query->whereDate('created_at', '>=', $request->tanggal_mulai);
    }

    if ($request->filled('tanggal_akhir')) {
        $query->whereDate('created_at', '<=', $request->tanggal_akhir);
    }

    $aspirasi = $query->latest()->paginate(15);
    $kategoriAspirasi = KategoriAspirasi::all();

    // Statistik
    $stats = [
        'total' => $query->count(),
        'pending' => (clone $query)->where('status', 'pending')->count(),
        'diproses' => (clone $query)->where('status', 'diproses')->count(),
        'selesai' => (clone $query)->where('status', 'selesai')->count(),
        'ditolak' => (clone $query)->where('status', 'ditolak')->count(),
    ];

    return view('backend.pages.aspirasi.index', compact('aspirasi', 'kategoriAspirasi', 'stats'));
}

    /**
     * Display the specified aspirasi.
     */
    public function show(Aspirasi $aspirasi)
    {
        $aspirasi->load(['kategoriAspirasi.opd', 'admin']);
        
        // Return JSON response for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $aspirasi
            ]);
        }

        return view('backend.pages.aspirasi.show', compact('aspirasi'));
    }

   
    /**
     * Remove the specified aspirasi from storage.
     */
    public function destroy(Aspirasi $aspirasi)
{
    $user = Auth::user();

    // Jika user adalah admin-opd, periksa apakah aspirasi miliknya
    if ($user->role->slug === 'admin-opd') {
        $aspirasiOpdId = $aspirasi->kategoriAspirasi->opd_id ?? null;
        $userOpdId = $user->opd_id;

        if ($aspirasiOpdId !== $userOpdId) {
            // Jika bukan miliknya, tolak akses
            abort(403, 'Anda tidak memiliki izin untuk menghapus aspirasi ini.');
        }
    }

    // Hapus file lampiran jika ada
    if ($aspirasi->lampiran) {
        $files = json_decode($aspirasi->lampiran, true);
        foreach ($files as $file) {
            Storage::disk('public')->delete($file['path']);
        }
    }

    $aspirasi->delete();

    // Jika request dari AJAX
    if (request()->ajax()) {
        return response()->json([
            'status' => 'success',
            'message' => 'Aspirasi berhasil dihapus.'
        ]);
    }

    return redirect()->route('aspirasi.index')
                    ->with('success', 'Aspirasi berhasil dihapus.');
}


    /**
     * Update status aspirasi
     */
    public function updateStatus(Request $request, Aspirasi $aspirasi)
    {
        $user = Auth::user();

        // Jika user adalah admin-opd, pastikan aspirasi miliknya
        if ($user->role->slug === 'admin-opd') {
            $aspirasiOpdId = $aspirasi->kategoriAspirasi->opd_id ?? null;
            $userOpdId = $user->opd_id;

            if ($aspirasiOpdId !== $userOpdId) {
                abort(403, 'Anda tidak memiliki izin untuk mengubah status aspirasi ini.');
            }
        }
        $validated = $request->validate([
            'status' => 'required|in:pending,diproses,selesai,ditolak',
            'tanggapan_admin' => 'nullable|string',
        ], [
            'status.required' => 'Status harus dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        $validated['tanggal_respon'] = now();
        $validated['admin_id'] = Auth::id();

           // Data untuk user
        $userData = [
            'nama'      => $aspirasi->nama_pengirim,
            'email'     => $aspirasi->email,
            'tanggapan' => $aspirasi->isiaspirasi,
            'tanggal'   => now()->format('d-m-Y H:i'),
        ];

       

        // Tentukan tipe email berdasarkan status
        $emailType = null;
        
        switch ($validated['status']) {
            case 'diproses':
                $emailType = 'diproses';
                break;
            case 'selesai':
                $emailType = 'selesai';
                break;
            case 'ditolak':
                $emailType = 'ditolak';
                break;
        }

        // Kirim email jika ada alamat email pengguna & tipe email ditentukan
        if (!empty($aspirasi->email) && $emailType) {
            Mail::to($aspirasi->email)->queue(new TanggapanMail($userData, $emailType));
        }

        $aspirasi->update($validated);

        // Return JSON response for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Status aspirasi berhasil diperbarui.'
            ]);
        }

        return redirect()->back()
                        ->with('success', 'Status aspirasi berhasil diperbarui.');
    }

    /**
     * Download lampiran file
     */
    public function downloadLampiran(Aspirasi $aspirasi, $index)
    {
        if (!$aspirasi->lampiran) {
            abort(404);
        }

        $files = json_decode($aspirasi->lampiran, true);
        
        if (!isset($files[$index])) {
            abort(404);
        }

        $file = $files[$index];
        $filePath = storage_path('app/public/' . $file['path']);

        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->download($filePath, $file['original_name']);
    }

}