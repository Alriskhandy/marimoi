<?php

namespace App\Http\Controllers;

use App\Models\Aspirasi;
use App\Models\KategoriAspirasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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
        $query = Aspirasi::with(['kategoriAspirasi.opd', 'admin']);

        // Filter berdasarkan parameter
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('jenis_aspirasi') && $request->jenis_aspirasi != '') {
            $query->where('jenis_aspirasi', $request->jenis_aspirasi);
        }

        if ($request->has('kategori') && $request->kategori != '') {
            $query->where('kategori_aspirasi_id', $request->kategori);
        }

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_tiket', 'like', '%' . $search . '%')
                  ->orWhere('nama_pengirim', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('judul_aspirasi', 'like', '%' . $search . '%')
                  ->orWhere('isi_aspirasi', 'like', '%' . $search . '%');
            });
        }

        // Filter berdasarkan tanggal
        if ($request->has('tanggal_mulai') && $request->tanggal_mulai != '') {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        }

        if ($request->has('tanggal_akhir') && $request->tanggal_akhir != '') {
            $query->whereDate('created_at', '<=', $request->tanggal_akhir);
        }

        $aspirasi = $query->latest()->paginate(15);
        $kategoriAspirasi = KategoriAspirasi::all();

        // Statistics
        $stats = [
            'total' => Aspirasi::count(),
            'pending' => Aspirasi::where('status', 'pending')->count(),
            'diproses' => Aspirasi::where('status', 'diproses')->count(),
            'selesai' => Aspirasi::where('status', 'selesai')->count(),
            'ditolak' => Aspirasi::where('status', 'ditolak')->count(),
        ];

        return view('backend.pages.aspirasi.index', compact('aspirasi', 'kategoriAspirasi', 'stats'));
    }

    /**
     * Show the form for creating a new aspirasi.
     */
    public function create(): View
    {
        $kategoriAspirasi = KategoriAspirasi::all();
        return view('backend.pages.aspirasi.create', compact('kategoriAspirasi'));
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
     * Show the form for editing the specified aspirasi.
     */
    public function edit(Aspirasi $aspirasi): View
    {
        $kategoriAspirasi = KategoriAspirasi::all();
        $admins = User::whereHas('role', function($query) {
            $query->whereIn('name', ['Super Admin', 'Admin OPD', 'Operator']);
        })->get();

        return view('backend.pages.aspirasi.edit', compact('aspirasi', 'kategoriAspirasi', 'admins'));
    }

   

    /**
     * Remove the specified aspirasi from storage.
     */
    public function destroy(Aspirasi $aspirasi)
    {
        // Delete associated files
        if ($aspirasi->lampiran) {
            $files = json_decode($aspirasi->lampiran, true);
            foreach ($files as $file) {
                Storage::disk('public')->delete($file['path']);
            }
        }

        $aspirasi->delete();

        // Return JSON response for AJAX requests
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
        $validated = $request->validate([
            'status' => 'required|in:pending,diproses,selesai,ditolak',
            'tanggapan_admin' => 'nullable|string',
        ], [
            'status.required' => 'Status harus dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        $validated['tanggal_respon'] = now();
        $validated['admin_id'] = Auth::id();

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
     * Generate unique nomor tiket
     */
    private function generateNomorTiket(): string
    {
        do {
            // Membuat nomor acak 6 digit, misalnya: 009090
            $randomNumber = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Format: MARIMOI-ASP-009090
            $nomor = 'MARIMOI-ASP-' . $randomNumber;
        } while (Aspirasi::where('nomor_tiket', $nomor)->exists());

        return $nomor;
    }

    /**
     * Export data aspirasi (for API)
     */
    public function export(Request $request)
    {
        // Implementation for export functionality
        // Could be Excel, PDF, etc.
    }
}