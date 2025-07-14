<?php

namespace App\Http\Controllers;

use App\Models\KategoriLayer;
use App\Models\KategoriPSD;
use App\Models\Lokasi;
use App\Models\ProyekStrategisDaerah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FrontendController extends Controller
{
    public function index()
    {
        return view('frontend.pages.index');
    }

    public function showDetail($id)
    {
        Lokasi::findOrFail($id);
        return view('frontend.pages.detail', compact('id'));
    }

    public function showMap()
    {
        return view('frontend.pages.peta');
    }

    public function psd()
    {
        return view('frontend.pages.psd');
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

    public function psdGeojson(Request $request)
    {
        // Variabel dinamis untuk nama tabel dan kolom
        $tableName = 'proyek_strategis_daerahs'; // Nama tabel utama
        $categoryTable = 'kategori_psd'; // Nama tabel kategori
        $categoryColumn = 'nama'; // Nama kolom kategori
        
        // Query dinamis berdasarkan variabel
        $query = DB::table($tableName)
            ->join($categoryTable, "$tableName.kategori_id", '=', "$categoryTable.id")
            ->select(
                "$tableName.id",
                "$tableName.kategori_id",
                "$categoryTable.$categoryColumn as kategori", // Menggunakan variabel untuk kategori
                "$tableName.deskripsi",
                "$tableName.dbf_attributes",
                DB::raw("ST_AsGeoJSON($tableName.geom) as geojson")
            );

        // Filter kategori
        if ($request->has('kategori') && !empty($request->kategori)) {
            $categories = is_array($request->kategori) ? $request->kategori : [$request->kategori];
            $query->whereIn("$categoryTable.$categoryColumn", $categories); // Dinamis berdasarkan kategori
        }

        // Filter atribut DBF
        if ($request->has('dbf_filter') && !empty($request->dbf_filter)) {
            foreach ($request->dbf_filter as $attribute => $value) {
                $query->whereRaw("dbf_attributes->? = ?", [$attribute, json_encode($value)]);
            }
        }

        // BBOX
        if ($request->has('bbox') && !empty($request->bbox)) {
            $bbox = explode(',', $request->bbox);
            if (count($bbox) === 4) {
                $query->whereRaw("ST_Intersects($tableName.geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))", $bbox);
            }
        }

        $lokasis = $query->get();

        $features = $lokasis->map(function ($lokasi) {
            $dbfAttributes = json_decode($lokasi->dbf_attributes, true) ?? [];

            return [
                'type' => 'Feature',
                'properties' => array_merge([
                    'id' => $lokasi->id,
                    'kategori_id' => $lokasi->kategori_id,
                    'kategori' => $lokasi->kategori,
                    'deskripsi' => $lokasi->deskripsi,
                ], $dbfAttributes),
                'geometry' => json_decode($lokasi->geojson),
            ];
        });

        // Menggunakan variabel yang lebih dinamis untuk kategori
        $rootCategories = KategoriLayer::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->orderBy('nama');
            }])
            ->orderBy('nama')
            ->get();
                    
        $allCategories = KategoriLayer::with('parent')->orderBy('nama')->get();
                    
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'root_categories' => $rootCategories,
            'all_categories' => $allCategories,
            'meta' => [
                'total_root_categories' => $rootCategories->count(),
                'total_categories' => $allCategories->count(),
                'generated_at' => now()->toISOString()
            ]
        ]);
    }

    public function psnGeojson(Request $request)
    {
        // Variabel dinamis untuk nama tabel dan kolom
        $tableName = 'proyek_strategis_nasionals'; // Nama tabel utama
        $categoryTable = 'kategori_psn'; // Nama tabel kategori
        $categoryColumn = 'nama'; // Nama kolom kategori
        
        // Query dinamis berdasarkan variabel
        $query = DB::table($tableName)
            ->join($categoryTable, "$tableName.kategori_id", '=', "$categoryTable.id")
            ->select(
                "$tableName.id",
                "$tableName.kategori_id",
                "$categoryTable.$categoryColumn as kategori", // Menggunakan variabel untuk kategori
                "$tableName.deskripsi",
                "$tableName.dbf_attributes",
                DB::raw("ST_AsGeoJSON($tableName.geom) as geojson")
            );

        // Filter kategori
        if ($request->has('kategori') && !empty($request->kategori)) {
            $categories = is_array($request->kategori) ? $request->kategori : [$request->kategori];
            $query->whereIn("$categoryTable.$categoryColumn", $categories); // Dinamis berdasarkan kategori
        }

        // Filter atribut DBF
        if ($request->has('dbf_filter') && !empty($request->dbf_filter)) {
            foreach ($request->dbf_filter as $attribute => $value) {
                $query->whereRaw("dbf_attributes->? = ?", [$attribute, json_encode($value)]);
            }
        }

        // BBOX
        if ($request->has('bbox') && !empty($request->bbox)) {
            $bbox = explode(',', $request->bbox);
            if (count($bbox) === 4) {
                $query->whereRaw("ST_Intersects($tableName.geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))", $bbox);
            }
        }

        $lokasis = $query->get();

        $features = $lokasis->map(function ($lokasi) {
            $dbfAttributes = json_decode($lokasi->dbf_attributes, true) ?? [];

            return [
                'type' => 'Feature',
                'properties' => array_merge([
                    'id' => $lokasi->id,
                    'kategori_id' => $lokasi->kategori_id,
                    'kategori' => $lokasi->kategori,
                    'deskripsi' => $lokasi->deskripsi,
                ], $dbfAttributes),
                'geometry' => json_decode($lokasi->geojson),
            ];
        });

        // Menggunakan variabel yang lebih dinamis untuk kategori
        $rootCategories = KategoriLayer::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->orderBy('nama');
            }])
            ->orderBy('nama')
            ->get();
                    
        $allCategories = KategoriLayer::with('parent')->orderBy('nama')->get();
                    
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'root_categories' => $rootCategories,
            'all_categories' => $allCategories,
            'meta' => [
                'total_root_categories' => $rootCategories->count(),
                'total_categories' => $allCategories->count(),
                'generated_at' => now()->toISOString()
            ]
        ]);
    }

    public function rpjmdGeojson(Request $request)
    {
        // Variabel dinamis untuk nama tabel dan kolom
        $tableName = 'lokasis'; // Nama tabel utama
        $categoryTable = 'kategori_layers'; // Nama tabel kategori
        $categoryColumn = 'nama'; // Nama kolom kategori
        
        // Query dinamis berdasarkan variabel
        $query = DB::table($tableName)
            ->join($categoryTable, "$tableName.kategori_id", '=', "$categoryTable.id")
            ->select(
                "$tableName.id",
                "$tableName.kategori_id",
                "$categoryTable.$categoryColumn as kategori", // Menggunakan variabel untuk kategori
                "$tableName.deskripsi",
                "$tableName.dbf_attributes",
                DB::raw("ST_AsGeoJSON($tableName.geom) as geojson")
            );

        // Filter kategori
        if ($request->has('kategori') && !empty($request->kategori)) {
            $categories = is_array($request->kategori) ? $request->kategori : [$request->kategori];
            $query->whereIn("$categoryTable.$categoryColumn", $categories); // Dinamis berdasarkan kategori
        }

        // Filter atribut DBF
        if ($request->has('dbf_filter') && !empty($request->dbf_filter)) {
            foreach ($request->dbf_filter as $attribute => $value) {
                $query->whereRaw("dbf_attributes->? = ?", [$attribute, json_encode($value)]);
            }
        }

        // BBOX
        if ($request->has('bbox') && !empty($request->bbox)) {
            $bbox = explode(',', $request->bbox);
            if (count($bbox) === 4) {
                $query->whereRaw("ST_Intersects($tableName.geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))", $bbox);
            }
        }

        $lokasis = $query->get();

        $features = $lokasis->map(function ($lokasi) {
            $dbfAttributes = json_decode($lokasi->dbf_attributes, true) ?? [];

            return [
                'type' => 'Feature',
                'properties' => array_merge([
                    'id' => $lokasi->id,
                    'kategori_id' => $lokasi->kategori_id,
                    'kategori' => $lokasi->kategori,
                    'deskripsi' => $lokasi->deskripsi,
                ], $dbfAttributes),
                'geometry' => json_decode($lokasi->geojson),
            ];
        });

        // Menggunakan variabel yang lebih dinamis untuk kategori
        $rootCategories = KategoriLayer::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->orderBy('nama');
            }])
            ->orderBy('nama')
            ->get();
                    
        $allCategories = KategoriLayer::with('parent')->orderBy('nama')->get();
                    
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'root_categories' => $rootCategories,
            'all_categories' => $allCategories,
            'meta' => [
                'total_root_categories' => $rootCategories->count(),
                'total_categories' => $allCategories->count(),
                'generated_at' => now()->toISOString()
            ]
        ]);
    }

    public function pokirGeojson(Request $request)
    {
        // Variabel dinamis untuk nama tabel dan kolom
        $tableName = 'pokir_dprds'; // Nama tabel utama
        $categoryTable = 'kategori_pokir_dprds'; // Nama tabel kategori
        $categoryColumn = 'nama'; // Nama kolom kategori
        
        // Query dinamis berdasarkan variabel
        $query = DB::table($tableName)
            ->join($categoryTable, "$tableName.kategori_id", '=', "$categoryTable.id")
            ->select(
                "$tableName.id",
                "$tableName.kategori_id",
                "$categoryTable.$categoryColumn as kategori", // Menggunakan variabel untuk kategori
                "$tableName.deskripsi",
                "$tableName.dbf_attributes",
                DB::raw("ST_AsGeoJSON($tableName.geom) as geojson")
            );

        // Filter kategori
        if ($request->has('kategori') && !empty($request->kategori)) {
            $categories = is_array($request->kategori) ? $request->kategori : [$request->kategori];
            $query->whereIn("$categoryTable.$categoryColumn", $categories); // Dinamis berdasarkan kategori
        }

        // Filter atribut DBF
        if ($request->has('dbf_filter') && !empty($request->dbf_filter)) {
            foreach ($request->dbf_filter as $attribute => $value) {
                $query->whereRaw("dbf_attributes->? = ?", [$attribute, json_encode($value)]);
            }
        }

        // BBOX
        if ($request->has('bbox') && !empty($request->bbox)) {
            $bbox = explode(',', $request->bbox);
            if (count($bbox) === 4) {
                $query->whereRaw("ST_Intersects($tableName.geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))", $bbox);
            }
        }

        $lokasis = $query->get();

        $features = $lokasis->map(function ($lokasi) {
            $dbfAttributes = json_decode($lokasi->dbf_attributes, true) ?? [];

            return [
                'type' => 'Feature',
                'properties' => array_merge([
                    'id' => $lokasi->id,
                    'kategori_id' => $lokasi->kategori_id,
                    'kategori' => $lokasi->kategori,
                    'deskripsi' => $lokasi->deskripsi,
                ], $dbfAttributes),
                'geometry' => json_decode($lokasi->geojson),
            ];
        });

        // Menggunakan variabel yang lebih dinamis untuk kategori
        $rootCategories = KategoriLayer::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->orderBy('nama');
            }])
            ->orderBy('nama')
            ->get();
                    
        $allCategories = KategoriLayer::with('parent')->orderBy('nama')->get();
                    
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'root_categories' => $rootCategories,
            'all_categories' => $allCategories,
            'meta' => [
                'total_root_categories' => $rootCategories->count(),
                'total_categories' => $allCategories->count(),
                'generated_at' => now()->toISOString()
            ]
        ]);
    }
}
