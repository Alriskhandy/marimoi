<?php

namespace App\Http\Controllers;

use App\Models\KategoriAspirasi;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;

class KategoriAspirasiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $kategoriAspirasi = KategoriAspirasi::with('opd')->latest()->get();
        $opd = Opd::all();

        // Statistics
        $stats = [
            'total' => $kategoriAspirasi->count(),
            'dengan_opd' => $kategoriAspirasi->whereNotNull('opd_id')->count(),
            'tanpa_opd' => $kategoriAspirasi->whereNull('opd_id')->count(),
        ];

        return view('backend.pages.aspirasi.kategori-aspirasi', compact('kategoriAspirasi', 'opd', 'stats'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'opd_id' => 'nullable|exists:opd,id',
            'nama_kategori' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ], [
            'opd_id.exists' => 'OPD yang dipilih tidak valid.',
            'nama_kategori.required' => 'Nama kategori harus diisi.',
            'nama_kategori.max' => 'Nama kategori maksimal 255 karakter.',
        ]);

        KategoriAspirasi::create($validated);

        // Return JSON response for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori aspirasi berhasil ditambahkan.'
            ]);
        }

        return redirect()->route('kategori-aspirasi.index')
                        ->with('success', 'Kategori aspirasi berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(KategoriAspirasi $kategoriAspirasi): View
    {
        $kategoriAspirasi->load('opd');
        return view('backend.pages.kategori-aspirasi.show', compact('kategoriAspirasi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KategoriAspirasi $kategoriAspirasi): View
    {
        $opd = Opd::all();
        return view('backend.pages.kategori-aspirasi.edit', compact('kategoriAspirasi', 'opd'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KategoriAspirasi $kategoriAspirasi)
    {
        $validated = $request->validate([
            'opd_id' => 'nullable|exists:opd,id',
            'nama_kategori' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ], [
            'opd_id.exists' => 'OPD yang dipilih tidak valid.',
            'nama_kategori.required' => 'Nama kategori harus diisi.',
            'nama_kategori.max' => 'Nama kategori maksimal 255 karakter.',
        ]);

        $kategoriAspirasi->update($validated);

        // Return JSON response for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori aspirasi berhasil diperbarui.'
            ]);
        }

        return redirect()->route('kategori-aspirasi.index')
                        ->with('success', 'Kategori aspirasi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KategoriAspirasi $kategoriAspirasi)
    {
        // Check if kategori has aspirasi
        if ($kategoriAspirasi->aspirasi()->count() > 0) {
            return redirect()->route('kategori-aspirasi.index')
                            ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh aspirasi.');
        }

        $kategoriAspirasi->delete();

        // Return JSON response for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori aspirasi berhasil dihapus.'
            ]);
        }

        return redirect()->route('kategori-aspirasi.index')
                        ->with('success', 'Kategori aspirasi berhasil dihapus.');
    }

    public function apiOptions(): JsonResponse
    {
        $kategori = KategoriAspirasi::select('id', 'nama_kategori', 'kode_kategori')
                                   ->orderBy('nama_kategori')
                                   ->get();

        return response()->json([
            'success' => true,
            'data' => $kategori
        ]);
    }
}