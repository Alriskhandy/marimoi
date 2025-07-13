<?php

namespace App\Http\Controllers;

use App\Models\KategoriPokirDprd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class KategoriPokirDprdController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kategori_pokir_dprds = KategoriPokirDprd::with('parent', 'children')->orderBy('nama')->get();
        return view('backend.pages.pokir_dprd.kategori', compact('kategori_pokir_dprds'));
    }

    /**
     * Get data for create modal
     */
   public function create()
{
    $parentKategori = KategoriPokirDprd::orderBy('nama')->get();

    return response()->json([
        'parentKategori' => $parentKategori
    ]);
}


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'warna' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:kategori_pokir_dprds,id'
        ]);
        
        if ($validator->fails()) {
            dd($validator->fails());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $kategori_pokir_dprd = KategoriPokirDprd::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kategori Layer berhasil ditambahkan!',
            'data' => $kategori_pokir_dprd->load('parent', 'children')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(KategoriPokirDprd $kategori_pokir_dprd)
    {
        $kategori_pokir_dprd->load('parent', 'children');
        return response()->json([
            'success' => true,
            'data' => $kategori_pokir_dprd
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KategoriPokirDprd $kategori_pokir_dprd)
    {
    $parentKategori = KategoriPokirDprd::where('id', '!=', $kategori_pokir_dprd->id)
    ->orderBy('nama')
    ->get();

        
        return response()->json([
            'success' => true,
            'data' => $kategori_pokir_dprd,
            'parentKategori' => $parentKategori
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KategoriPokirDprd $kategori_pokir_dprd)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:kategori_pokir_dprds,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Prevent setting parent to itself or its children
        if ($request->parent_id && $this->isDescendant($kategori_pokir_dprd->id, $request->parent_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat memilih kategori anak sebagai parent!'
            ], 422);
        }

        $kategori_pokir_dprd->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kategori Layer berhasil diperbarui!',
            'data' => $kategori_pokir_dprd->load('parent', 'children')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KategoriPokirDprd $kategori_pokir_dprd)
    {
        try {
             $kategori_pokir_dprd->delete();

        return redirect()->route('kategori-pokir-dprd.index')
            ->with('success', 'Kategori Layer berhasil dihapus!');
        } catch (QueryException $e) {
        if ($e->getCode() === '23503') { // Kode error foreign key PostgreSQL
            return redirect()->back()->with('error', 'Data tidak bisa dihapus karena masih digunakan di tabel lain.');
        }

        // Untuk error lainnya
        return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data.');
    }
      
    }

    /**
     * Check if a category is descendant of another
     */
    private function isDescendant($parentId, $childId)
    {
        $category = KategoriPokirDprd::find($childId);
        
        while ($category && $category->parent_id) {
            if ($category->parent_id == $parentId) {
                return true;
            }
            $category = $category->parent;
        }
        
        return false;
    }
}
