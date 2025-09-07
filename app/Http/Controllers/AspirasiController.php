<?php

namespace App\Http\Controllers;

use App\Exports\AspirasiExport;
use App\Mail\TanggapanMail;
use App\Models\Aspirasi;
use App\Models\KategoriAspirasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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
/**
 * Bulk delete aspirasi
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\RedirectResponse
 */
public function bulkDestroy(Request $request): RedirectResponse
{
    // Validasi input
    $request->validate([
        'ids' => 'required|array|min:1',
        'ids.*' => 'required|integer|exists:aspirasi,id'
    ], [
        'ids.required' => 'Tidak ada aspirasi yang dipilih untuk dihapus',
        'ids.array' => 'Format data tidak valid',
        'ids.min' => 'Pilih minimal 1 aspirasi untuk dihapus',
        'ids.*.exists' => 'Salah satu aspirasi yang dipilih tidak ditemukan'
    ]);

    $ids = $request->ids;
    $user = Auth::user();
    
    try {
        // Cek authorization berdasarkan role
        if (!in_array($user->role->slug, ['super-admin', 'admin-bappeda'])) {
            // Admin OPD hanya bisa hapus aspirasi yang sesuai dengan OPD mereka
            if ($user->role->slug === 'admin-opd') {
                $validIds = Aspirasi::whereIn('id', $ids)
                    ->whereHas('kategoriAspirasi', function($query) use ($user) {
                        $query->where('opd_id', $user->opd_id);
                    })
                    ->pluck('id')
                    ->toArray();
                
                if (count($validIds) !== count($ids)) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menghapus beberapa aspirasi yang dipilih');
                }
                
                $ids = $validIds;
            } else {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menghapus aspirasi');
            }
        }

        // Ambil data aspirasi yang akan dihapus untuk logging dan hapus lampiran
        $aspirasiToDelete = Aspirasi::whereIn('id', $ids)
            ->select('id', 'nomor_tiket', 'nama_pengirim', 'judul_aspirasi', 'lampiran')
            ->get();

        // Hapus lampiran files jika ada
        foreach ($aspirasiToDelete as $aspirasi) {
            if ($aspirasi->lampiran) {
                $lampiranData = json_decode($aspirasi->lampiran, true);
                
                if (is_array($lampiranData)) {
                    foreach ($lampiranData as $file) {
                        if (isset($file['path'])) {
                            Storage::disk('public')->delete($file['path']);
                        }
                    }
                }
            }
        }

        // Hapus data dari database
        $deletedCount = Aspirasi::whereIn('id', $ids)->delete();

        // Log activity untuk audit trail
        Log::info('Bulk delete aspirasi', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'deleted_count' => $deletedCount,
            'deleted_aspirasi' => $aspirasiToDelete->pluck('nomor_tiket')->toArray(),
            'timestamp' => now()
        ]);

        return redirect()->back()->with('success', "Berhasil menghapus {$deletedCount} aspirasi");

    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()
            ->withErrors($e->errors())
            ->with('error', 'Terjadi kesalahan validasi data');
            
    } catch (\Illuminate\Database\QueryException $e) {
        Log::error('Database error during bulk delete aspirasi: ' . $e->getMessage(), [
            'user_id' => $user->id,
            'ids' => $ids,
            'error' => $e->getMessage()
        ]);
        
        return redirect()->back()->with('error', 'Terjadi kesalahan database. Silakan coba lagi.');
        
    } catch (\Exception $e) {
        Log::error('Error during bulk delete aspirasi: ' . $e->getMessage(), [
            'user_id' => $user->id,
            'ids' => $ids,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()->back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
    }
}

public function export(Request $request) // No $id parameter
{
    try {
        $filters = $request->only(['kategori', 'opd', 'status', 'jenis', 'start_date', 'end_date']);
        
        $fileName = 'aspirasi_masyarakat_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return (new AspirasiExport($filters))->download($fileName);
        
    } catch (\Exception $e) {
        return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage());
    }
}

    public function exportFiltered(Request $request)
    {
        try {
            $validated = $request->validate([
                'kategori' => 'nullable|exists:kategori_aspirasi,id',
                'opd' => 'nullable|exists:opd,id',
                'status' => 'nullable|in:pending,diproses,selesai,ditolak',
                'jenis' => 'nullable|in:usulan,kritik & saran',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date'
            ]);

            $fileName = 'aspirasi_filtered_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return (new AspirasiExport($validated))->download($fileName);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengekspor data: ' . $e->getMessage()
            ], 500);
        }
    }

    
public function previewExport(Request $request)
{
    try {
        $filters = $request->only(['kategori', 'opd', 'status', 'jenis', 'start_date', 'end_date']);
        
        $query = Aspirasi::with(['kategoriAspirasi.opd']);
        
        $appliedFilters = [];
        
        // Apply filters and track what's being filtered
        if (!empty($filters['kategori'])) {
            $query->where('kategori_aspirasi_id', $filters['kategori']);
            $kategori = \App\Models\KategoriAspirasi::find($filters['kategori']);
            $appliedFilters[] = 'Kategori: ' . ($kategori->nama_kategori ?? 'Unknown');
        }
        
        if (!empty($filters['opd'])) {
            $query->whereHas('kategoriAspirasi', function ($q) use ($filters) {
                $q->where('opd_id', $filters['opd']);
            });
            $opd = \App\Models\Opd::find($filters['opd']);
            $appliedFilters[] = 'OPD: ' . ($opd->name ?? 'Unknown');
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
            $appliedFilters[] = 'Status: ' . ucfirst($filters['status']);
        }
        
        if (!empty($filters['jenis'])) {
            $query->where('jenis_aspirasi', $filters['jenis']);
            $appliedFilters[] = 'Jenis: ' . ucfirst($filters['jenis']);
        }
        
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
            $appliedFilters[] = 'Mulai: ' . Carbon::parse($filters['start_date'])->format('d/m/Y');
        }
        
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
            $appliedFilters[] = 'Sampai: ' . Carbon::parse($filters['end_date'])->format('d/m/Y');
        }
        
        $count = $query->count();
        
        return response()->json([
            'status' => 'success',
            'count' => $count,
            'applied_filters' => $appliedFilters,
            'message' => $count > 0 ? "Ditemukan {$count} data aspirasi" : 'Tidak ada data yang sesuai filter'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal memuat preview: ' . $e->getMessage()
        ], 500);
    }
}
public function quickExport()
{
    try {
        $fileName = 'aspirasi_masyarakat_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new AspirasiExport(), $fileName);
        
    } catch (\Exception $e) {
        return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage());
    }
}
}