<?php

namespace App\Http\Controllers;

use App\Models\KategoriPSD;
use App\Models\ProyekStrategisDaerah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KategoriPSDController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kategoris = KategoriPSD::with('children', 'proyekStrategisDaerah')
            ->whereNull('parent_id')
            ->orderBy('nama')
            ->get();

        return view('backend.pages.kategori-psd.index', compact('kategoris'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parentKategoris = KategoriPSD::whereNull('parent_id')->orderBy('nama')->get();
        
        // Return JSON for AJAX requests
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'parentKategoris' => $parentKategoris
            ]);
        }
        
        return view('backend.pages.kategori-psd.create', compact('parentKategoris'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'warna' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:kategori_psd,id',
            'deskripsi' => 'nullable|string',
        ]);

        $kategori = KategoriPSD::create($request->all());

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil ditambahkan.',
                'data' => $kategori
            ]);
        }

        return redirect()->route('kategori-psd.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(KategoriPSD $kategoriPsd)
    {
        $kategoriPsd->load('children', 'proyeks');
        
        $proyeks = $kategoriPsd->proyeks()
            ->with('kategori')
            ->orderBy('tahun', 'desc')
            ->paginate(20);

        // Statistik
        $stats = [
            'total_proyek' => $kategoriPsd->proyeks()->count(),
            'tahun_tersedia' => $kategoriPsd->proyeks()
                ->select('tahun')
                ->distinct()
                ->orderBy('tahun', 'desc')
                ->pluck('tahun'),
            'proyek_per_tahun' => $kategoriPsd->proyeks()
                ->select('tahun', DB::raw('count(*) as total'))
                ->groupBy('tahun')
                ->orderBy('tahun', 'desc')
                ->get()
        ];

        // Return JSON for AJAX requests
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $kategoriPsd,
                'proyeks' => $proyeks->items(),
                'stats' => $stats
            ]);
        }

        return view('backend.pages.kategori-psd.show', compact('kategoriPsd', 'proyeks', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KategoriPSD $kategoriPsd)
    {
        $parentKategoris = KategoriPSD::whereNull('parent_id')
            ->where('id', '!=', $kategoriPsd->id)
            ->orderBy('nama')
            ->get();

        // Return JSON for AJAX requests
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $kategoriPsd,
                'parentKategoris' => $parentKategoris
            ]);
        }

        return view('backend.pages.kategori-psd.edit', compact('kategoriPsd', 'parentKategoris'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KategoriPSD $kategoriPsd)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'warna' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:kategori_psd,id',
            'deskripsi' => 'nullable|string',
        ]);

        // Pastikan tidak ada circular reference
        if ($request->parent_id == $kategoriPsd->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak bisa menjadi parent dari dirinya sendiri.'
                ], 422);
            }
            return back()->withErrors(['parent_id' => 'Kategori tidak bisa menjadi parent dari dirinya sendiri.']);
        }

        $kategoriPsd->update($request->all());

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diperbarui.',
                'data' => $kategoriPsd
            ]);
        }

        return redirect()->route('kategori-psd.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KategoriPSD $kategoriPsd)
    {
        // Cek apakah kategori memiliki proyek
        if ($kategoriPsd->proyekStrategisDaerah()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kategori yang masih memiliki proyek.');
        }

        // Cek apakah kategori memiliki sub kategori
        if ($kategoriPsd->children()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kategori yang masih memiliki sub kategori.');
        }

        $kategoriPsd->delete();

        return redirect()->back()
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Display kategoris with their projects (untuk digunakan di ProyekStrategisDaerahController)
     */
    public function indexByCategory()
    {
        $kategoris = KategoriPSD::with(['children', 'proyeks' => function($query) {
            $query->orderBy('tahun', 'desc');
        }])
        ->withCount('proyeks')
        ->orderBy('nama')
        ->get();

        // Grouping berdasarkan parent
        $parentKategoris = $kategoris->whereNull('parent_id');
        $childKategoris = $kategoris->whereNotNull('parent_id')->groupBy('parent_id');

        return view('backend.pages.proyek_strategis_daerah.kategori', compact('parentKategoris', 'childKategoris'));
    }

    /**
     * Show projects by specific category
     */
    public function showByCategory($kategoriId)
    {
        $kategori = KategoriPSD::with('children', 'parent')->findOrFail($kategoriId);
        
        $proyeks = ProyekStrategisDaerah::where('kategori_id', $kategoriId)
            ->with('kategori')
            ->orderBy('tahun', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistik
        $stats = [
            'total_proyek' => $proyeks->total(),
            'tahun_tersedia' => ProyekStrategisDaerah::where('kategori_id', $kategoriId)
                ->select('tahun')
                ->distinct()
                ->orderBy('tahun', 'desc')
                ->pluck('tahun'),
            'proyek_per_tahun' => ProyekStrategisDaerah::where('kategori_id', $kategoriId)
                ->select('tahun', DB::raw('count(*) as total'))
                ->groupBy('tahun')
                ->orderBy('tahun', 'desc')
                ->get()
        ];

        return view('backend.pages.data-spasial.kategori_show', compact('kategori', 'proyeks', 'stats'));
    }

    /**
     * Create form for specific category
     */
    public function createByCategory($kategoriId)
    {
        $kategori = KategoriPSD::findOrFail($kategoriId);
        $kategoriLayers = KategoriPSD::with('children')->whereNull('parent_id')->get();
        
        // Return JSON for AJAX requests
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'kategori' => $kategori,
                'kategoriLayers' => $kategoriLayers
            ]);
        }
        
        return view('backend.pages.data-spasial.input-gis', compact('kategoriLayers', 'kategori'));
    }

    /**
     * Show projects by category and year
     */
    public function showByCategoryAndYear($kategoriId, $year)
    {
        $kategori = KategoriPSD::with('children', 'parent')->findOrFail($kategoriId);
        
        $proyeks = ProyekStrategisDaerah::where('kategori_id', $kategoriId)
            ->where('tahun', $year)
            ->with('kategori')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistik untuk kategori dan tahun tersebut
        $stats = [
            'total_proyek' => $proyeks->total(),
            'kategori' => $kategori->nama,
            'year' => $year,
            'other_years' => ProyekStrategisDaerah::where('kategori_id', $kategoriId)
                ->where('tahun', '!=', $year)
                ->select('tahun')
                ->distinct()
                ->orderBy('tahun', 'desc')
                ->pluck('tahun')
        ];

        return view('backend.pages.data-spasial.kategori_year_show', compact('kategori', 'proyeks', 'stats', 'year'));
    }

    /**
     * API: Get categories with project counts
     */
    public function getCategoriesApi()
    {
        $kategoris = KategoriPSD::with('children')
            ->withCount('proyeks')
            ->orderBy('nama')
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $kategoris->map(function($kategori) {
                return [
                    'id' => $kategori->id,
                    'nama' => $kategori->nama,
                    'warna' => $kategori->warna,
                    'parent_id' => $kategori->parent_id,
                    'deskripsi' => $kategori->deskripsi,
                    'proyek_count' => $kategori->proyeks_count,
                    'children' => $kategori->children->map(function($child) {
                        return [
                            'id' => $child->id,
                            'nama' => $child->nama,
                            'warna' => $child->warna,
                            'proyek_count' => $child->proyeks_count ?? 0
                        ];
                    })
                ];
            })
        ]);
    }

    /**
     * API: Get category statistics
     */
    public function getCategoryStatistics($kategoriId)
    {
        $kategori = KategoriPSD::findOrFail($kategoriId);
        
        $stats = [
            'kategori_id' => $kategori->id,
            'kategori_nama' => $kategori->nama,
            'total_proyek' => $kategori->proyeks()->count(),
            'tahun_tersedia' => $kategori->proyeks()
                ->select('tahun')
                ->distinct()
                ->orderBy('tahun', 'desc')
                ->pluck('tahun'),
            'proyek_per_tahun' => $kategori->proyeks()
                ->select('tahun', DB::raw('count(*) as total'))
                ->groupBy('tahun')
                ->orderBy('tahun', 'desc')
                ->get(),
            'bounds' => DB::table('proyek_strategis_daerahs')
                ->select(
                    DB::raw('ST_XMin(ST_Extent(geom)) as min_lng'),
                    DB::raw('ST_YMin(ST_Extent(geom)) as min_lat'),
                    DB::raw('ST_XMax(ST_Extent(geom)) as max_lng'),
                    DB::raw('ST_YMax(ST_Extent(geom)) as max_lat')
                )
                ->where('kategori_id', $kategoriId)
                ->first()
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }
}