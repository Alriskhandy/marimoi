<?php

namespace App\Http\Controllers;

use App\Models\KategoriLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KategoriLayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kategoriLayers = KategoriLayer::with('parent', 'children')->orderBy('nama')->get();
        return view('backend.pages.kategori_layers.index', compact('kategoriLayers'));
    }

    /**
     * Get data for create modal
     */
   public function create()
{
    $parentKategori = KategoriLayer::orderBy('nama')->get();

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
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:kategori_layers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $kategoriLayer = KategoriLayer::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kategori Layer berhasil ditambahkan!',
            'data' => $kategoriLayer->load('parent', 'children')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(KategoriLayer $kategoriLayer)
    {
        $kategoriLayer->load('parent', 'children');
        return response()->json([
            'success' => true,
            'data' => $kategoriLayer
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KategoriLayer $kategoriLayer)
    {
    $parentKategori = KategoriLayer::where('id', '!=', $kategoriLayer->id)
    ->orderBy('nama')
    ->get();

        
        return response()->json([
            'success' => true,
            'data' => $kategoriLayer,
            'parentKategori' => $parentKategori
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KategoriLayer $kategoriLayer)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:kategori_layers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Prevent setting parent to itself or its children
        if ($request->parent_id && $this->isDescendant($kategoriLayer->id, $request->parent_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat memilih kategori anak sebagai parent!'
            ], 422);
        }

        $kategoriLayer->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kategori Layer berhasil diperbarui!',
            'data' => $kategoriLayer->load('parent', 'children')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KategoriLayer $kategoriLayer)
    {
        $kategoriLayer->delete();

        return redirect()->route('kategori-layers.index')
            ->with('success', 'Kategori Layer berhasil dihapus!');
    }

    /**
     * Check if a category is descendant of another
     */
    private function isDescendant($parentId, $childId)
    {
        $category = KategoriLayer::find($childId);
        
        while ($category && $category->parent_id) {
            if ($category->parent_id == $parentId) {
                return true;
            }
            $category = $category->parent;
        }
        
        return false;
    }
}