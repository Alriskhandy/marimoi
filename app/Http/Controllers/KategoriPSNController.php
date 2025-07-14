<?php

namespace App\Http\Controllers;

use App\Models\KategoriPSN;
use App\Models\ProyekStrategisNasional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KategoriPSNController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kategoris = KategoriPSN::with('children', 'proyekStrategisNasional')
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
        $parentKategoris = KategoriPSN::whereNull('parent_id')->orderBy('nama')->get();
        
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

        $kategori = KategoriPSN::create($request->all());

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
    public function show(KategoriPSN $kategoriPsn)
    {
        $kategoriPsn->load('children', 'proyeks');
        
        $proyeks = $kategoriPsn->proyeks()
            ->with('kategori')
            ->orderBy('tahun', 'desc')
            ->paginate(20);

        // Statistik
        $stats = [
            'total_proyek' => $kategoriPsn->proyeks()->count(),
            'tahun_tersedia' => $kategoriPsn->proyeks()
                ->select('tahun')
                ->distinct()
                ->orderBy('tahun', 'desc')
                ->pluck('tahun'),
            'proyek_per_tahun' => $kategoriPsn->proyeks()
                ->select('tahun', DB::raw('count(*) as total'))
                ->groupBy('tahun')
                ->orderBy('tahun', 'desc')
                ->get()
        ];

        // Return JSON for AJAX requests
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $kategoriPsn,
                'proyeks' => $proyeks->items(),
                'stats' => $stats
            ]);
        }

        return view('backend.pages.kategori-psd.show', compact('kategoriPsn', 'proyeks', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KategoriPSN $kategoriPsn)
    {
        $parentKategoris = KategoriPSN::whereNull('parent_id')
            ->where('id', '!=', $kategoriPsn->id)
            ->orderBy('nama')
            ->get();

        // Return JSON for AJAX requests
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $kategoriPsn,
                'parentKategoris' => $parentKategoris
            ]);
        }

        return view('backend.pages.kategori-psd.edit', compact('kategoriPsn', 'parentKategoris'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KategoriPSN $kategoriPsn)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'warna' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:kategori_psd,id',
            'deskripsi' => 'nullable|string',
        ]);

        // Pastikan tidak ada circular reference
        if ($request->parent_id == $kategoriPsn->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak bisa menjadi parent dari dirinya sendiri.'
                ], 422);
            }
            return back()->withErrors(['parent_id' => 'Kategori tidak bisa menjadi parent dari dirinya sendiri.']);
        }

        $kategoriPsn->update($request->all());

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diperbarui.',
                'data' => $kategoriPsn
            ]);
        }

        return redirect()->route('kategori-psd.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KategoriPSN $kategoriPsn)
    {
        // Cek apakah kategori memiliki proyek
        if ($kategoriPsn->proyekStrategisNasional()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kategori yang masih memiliki proyek.');
        }

        // Cek apakah kategori memiliki sub kategori
        if ($kategoriPsn->children()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kategori yang masih memiliki sub kategori.');
        }

        $kategoriPsn->delete();

        // dd($kategoriPsn);
        return redirect()->back()
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Display kategoris with their projects (untuk digunakan di proyekStrategisNasionalController)
     */
    public function indexByCategory()
    {
        $kategoris = KategoriPSN::with(['children', 'proyeks' => function($query) {
            $query->orderBy('tahun', 'desc');
        }])
        ->withCount('proyeks')
        ->orderBy('nama')
        ->get();

        // dd($kategoris);
        // Grouping berdasarkan parent
        $parentKategoris = $kategoris->whereNull('parent_id');
        $childKategoris = $kategoris->whereNotNull('parent_id')->groupBy('parent_id');

        return view('backend.pages.proyek_strategis_nasional.kategori', compact('parentKategoris', 'childKategoris'));
    }

    /**
     * Show projects by specific category
     */
    public function showByCategory($kategoriId)
    {
        $kategori = KategoriPSN::with('children', 'parent')->findOrFail($kategoriId);
        
        $proyeks = ProyekStrategisNasional::where('kategori_id', $kategoriId)
            ->with('kategori')
            ->orderBy('tahun', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistik
        $stats = [
            'total_proyek' => $proyeks->total(),
            'tahun_tersedia' => ProyekStrategisNasional::where('kategori_id', $kategoriId)
                ->select('tahun')
                ->distinct()
                ->orderBy('tahun', 'desc')
                ->pluck('tahun'),
            'proyek_per_tahun' => ProyekStrategisNasional::where('kategori_id', $kategoriId)
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
        $kategori = KategoriPSN::findOrFail($kategoriId);
        $kategoriLayers = KategoriPSN::with('children')->whereNull('parent_id')->get();
        
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
        $kategori = KategoriPSN::with('children', 'parent')->findOrFail($kategoriId);
        
        $proyeks = ProyekStrategisNasional::where('kategori_id', $kategoriId)
            ->where('tahun', $year)
            ->with('kategori')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistik untuk kategori dan tahun tersebut
        $stats = [
            'total_proyek' => $proyeks->total(),
            'kategori' => $kategori->nama,
            'year' => $year,
            'other_years' => ProyekStrategisNasional::where('kategori_id', $kategoriId)
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
        $kategoris = KategoriPSN::with('children')
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
        $kategori = KategoriPSN::findOrFail($kategoriId);
        
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
            'bounds' => DB::table('proyek_strategis_nasionals')
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
