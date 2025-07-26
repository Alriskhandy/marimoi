<?php

namespace App\Http\Controllers;

use App\Models\KategoriMusrenbang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class KategoriMusrenbangController extends Controller
{
    public function index()
    {
      $kategoris = KategoriMusrenbang::with(['children', 'proyeks' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])
        ->withCount('proyeks')
        ->orderBy('nama')
        ->get();

        // Grouping berdasarkan parent
        $parentKategoris = $kategoris->whereNull('parent_id');
        $childKategoris = $kategoris->whereNotNull('parent_id')->groupBy('parent_id');

        return view('backend.pages.usulan_musrenbang.kategori', compact('parentKategoris', 'childKategoris'));
    }

    /**
     * Get data for create modal
     */
   public function create()
{
    $parentKategori = KategoriMusrenbang::orderBy('nama')->get();

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
            'parent_id' => 'nullable|exists:kategori_musrenbangs,id'
        ]);
        
        if ($validator->fails()) {
            dd($validator->fails());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $kategori_usulan_musrenbang = KategoriMusrenbang::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kategori Layer berhasil ditambahkan!',
            'data' => $kategori_usulan_musrenbang->load('parent', 'children')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(KategoriMusrenbang $kategori_usulan_musrenbang)
    {
        $kategori_usulan_musrenbang->load('parent', 'children');
        return response()->json([
            'success' => true,
            'data' => $kategori_usulan_musrenbang
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KategoriMusrenbang $kategori_usulan_musrenbang)
    {
    $parentKategori = KategoriMusrenbang::where('id', '!=', $kategori_usulan_musrenbang->id)
    ->orderBy('nama')
    ->get();

        
        return response()->json([
            'success' => true,
            'data' => $kategori_usulan_musrenbang,
            'parentKategori' => $parentKategori
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KategoriMusrenbang $kategori_usulan_musrenbang)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:kategori_musrenbangs,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Prevent setting parent to itself or its children
        if ($request->parent_id && $this->isDescendant($kategori_usulan_musrenbang->id, $request->parent_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat memilih kategori anak sebagai parent!'
            ], 422);
        }

        $kategori_usulan_musrenbang->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kategori Layer berhasil diperbarui!',
            'data' => $kategori_usulan_musrenbang->load('parent', 'children')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KategoriMusrenbang $kategori_usulan_musrenbang)
    {
        // dd($kategori_usulan_musrenbang);
        try {
             $kategori_usulan_musrenbang->delete();

        return redirect()->route('kategori-usulan-musrenbang.index')
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
        $category = KategoriMusrenbang::find($childId);
        
        while ($category && $category->parent_id) {
            if ($category->parent_id == $parentId) {
                return true;
            }
            $category = $category->parent;
        }
        
        return false;
    }

     public function indexByCategory()
    {
        $kategoris = KategoriMusrenbang::with(['children', 'proyeks' => function($query) {
            $query->orderBy('tahun', 'desc');
        }])
        ->withCount('proyeks')
        ->orderBy('nama')
        ->get();

        // Grouping berdasarkan parent
        $parentKategoris = $kategoris->whereNull('parent_id');
        $childKategoris = $kategoris->whereNotNull('parent_id')->groupBy('parent_id');

        return view('backend.pages.usulan_musrenbang.kategori', compact('parentKategoris', 'childKategoris'));
    }
}
