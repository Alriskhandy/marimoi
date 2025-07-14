<?php

namespace App\Http\Controllers;

use App\Models\KategoriPSD;
use App\Models\ProyekStrategisDaerah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FrontendController extends Controller
{
    public function index()
    {
        return view('frontend.pages.index');
    }

    public function psd()
    {
        // Fetch distinct years
        $allYear = ProyekStrategisDaerah::select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        // Fetch categories based on year
        $allCategory = ProyekStrategisDaerah::select('kategori_id')
            ->distinct()
            ->orderBy('kategori_id', )
            ->get() 
            ->groupBy('tahun'); // Group categories by year

        // dd($allData, $allYear);

        // Pass both years and categories data to the view
        return view('frontend.pages.psd', compact('allYear', 'allCategory'));
    }


    public function psn()
    {
        return view('frontend.pages.psn');
    }

    public function rpjmd()
    {
        return view('frontend.pages.rpjmd');
    }

    public function pokir()
    {
        return view('frontend.pages.pokir');
    }

    public function showMap()
    {
        return view('frontend.pages.peta');
    }

    public function psdGeojson(Request $request)
    {
        // Memulai query dasar
        $query = DB::table('proyek_strategis_daerahs')
            ->join('kategori_psd', 'proyek_strategis_daerahs.kategori_id', '=', 'kategori_psd.id')
            ->select(
                'proyek_strategis_daerahs.id',
                'proyek_strategis_daerahs.kategori_id',
                'kategori_psd.nama as kategori',
                'proyek_strategis_daerahs.deskripsi',
                'proyek_strategis_daerahs.dbf_attributes',
                DB::raw('ST_AsGeoJSON(proyek_strategis_daerahs.geom) as geojson'),
                'proyek_strategis_daerahs.tahun' // Menambahkan kolom tahun
            );

        // Filter berdasarkan tahun, jika tahun tidak 'all'
        if ($request->has('tahun') && $request->tahun !== 'all') {
            $years = is_array($request->tahun) ? $request->tahun : [$request->tahun];
            $query->whereIn('proyek_strategis_daerahs.tahun', $years); // Menambahkan filter tahun
        }

        // Filter berdasarkan kategori nama, jika ada
        if ($request->has('kategori') && !empty($request->kategori)) {
            $categories = is_array($request->kategori) ? $request->kategori : [$request->kategori];
            $query->whereIn('kategori_psd.nama', $categories);
        }

        // Filter atribut DBF
        if ($request->has('dbf_filter') && !empty($request->dbf_filter)) {
            foreach ($request->dbf_filter as $attribute => $value) {
                $query->whereRaw("dbf_attributes->? = ?", [$attribute, json_encode($value)]);
            }
        }

        // Pencarian berdasarkan kata kunci
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kategori_psd.nama', 'ILIKE', "%{$search}%")
                ->orWhere('proyek_strategis_daerahs.deskripsi', 'ILIKE', "%{$search}%")
                ->orWhereRaw("dbf_attributes::text ILIKE ?", ["%{$search}%"]);
            });
        }

        // Menjalankan query dan mengambil data
        $lokasis = $query->get();

        // Mengolah data menjadi GeoJSON format
        $features = $lokasis->map(function ($lokasi) {
            $dbfAttributes = json_decode($lokasi->dbf_attributes, true) ?? [];

            return [
                'type' => 'Feature',
                'properties' => array_merge([
                    'id' => $lokasi->id,
                    'kategori_id' => $lokasi->kategori_id,
                    'kategori' => $lokasi->kategori,
                    'deskripsi' => $lokasi->deskripsi,
                    'tahun' => $lokasi->tahun, // Menambahkan tahun ke properti
                ], $dbfAttributes),
                'geometry' => json_decode($lokasi->geojson),
            ];
        });

        // Mengembalikan response dalam format GeoJSON
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
