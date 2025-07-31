<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\KategoriLayer;
use App\Models\KategoriPSD;
use App\Models\Lokasi;
use App\Models\PokirDprd;
use App\Models\ProjectFeedback;
use App\Models\ProyekStrategisDaerah;
use App\Models\ProyekStrategisNasional;
use App\Models\UsulanMusrenbang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FrontendController extends Controller
{
    public function index()
    {
        return view('frontend.pages.index');
    }
    public function aspirasi()
    {
        return view('frontend.pages.aspirasi');
    }
    
    
    // TAMPILAN PETA //
    public function psd()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.psd', compact('documents'));
    }

    public function psn()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.psn', compact('documents'));
    }

    public function rpjmd()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.rpjmd', compact('documents'));
    }

    public function prioritas()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.prioritas', compact('documents'));
    }

    public function pokir()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.pokir', compact('documents'));
    }
    public function musrenbang()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.musrenbang', compact('documents'));
    }

    // API //
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
        $rootCategories = KategoriPSD::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->orderBy('nama');
            }])
            ->orderBy('nama')
            ->get();
                    
        $allCategories = KategoriPSD::with('parent')->orderBy('nama')->get();
                    
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
    
    public function musrenbangGeojson(Request $request)
    {
        // Variabel dinamis untuk nama tabel dan kolom
        $tableName = 'usulan_musrenbangs'; // Nama tabel utama
        $categoryTable = 'kategori_musrenbangs'; // Nama tabel kategori
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


    // DETAIL LOKASI //
    public function showDetail($id)
    {
        $project = Lokasi::findOrFail($id);
        return view('frontend.pages.detail', compact('project'));
    }
    public function detailRpjmd($id)
    {
        $project = Lokasi::select('*', DB::raw('ST_AsGeoJSON(geom) as geojson'))
            ->findOrFail($id);
        $project->geojson = json_decode($project->geojson);
        return view('frontend.pages.detail', compact('project'));
    }
    public function detailPsd(Request $request, $id)
    {
        $project = ProyekStrategisDaerah::select('*', DB::raw('ST_AsGeoJSON(geom) as geojson'))
            ->findOrFail($id);
        $project->geojson = json_decode($project->geojson);
        $projectType = $this->getProjectTypeFromRequest($request);
        // $attr = json_decode($project->dbf_attributes, true);
        // dd($projectType);
        return view('frontend.pages.detail', compact('project', 'projectType'));
    }
    public function detailPsn($id)
    {
        $project = ProyekStrategisNasional::select('*', DB::raw('ST_AsGeoJSON(geom) as geojson'))
            ->findOrFail($id);
        $project->geojson = json_decode($project->geojson);
        return view('frontend.pages.detail', compact('project'));
    }
    public function detailPokir($id)
    {
        $project = PokirDprd::select('*', DB::raw('ST_AsGeoJSON(geom) as geojson'))
            ->findOrFail($id);
        $project->geojson = json_decode($project->geojson);
        return view('frontend.pages.detail', compact('project'));
    }
    public function detailMusrenbang($id)
    {
        $project = UsulanMusrenbang::select('*', DB::raw('ST_AsGeoJSON(geom) as geojson'))
            ->findOrFail($id);
        $project->geojson = json_decode($project->geojson);
        return view('frontend.pages.detail', compact('project'));
    }


    // PENGADUAN
    /**
     * Store feedback for specific project type
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $projectType = $this->getProjectTypeFromRequest($request);
        
        // If specific project type, validate and store accordingly
        if ($projectType && $projectType !== 'all') {
            return $this->storeScopedFeedback($request, $projectType);
        }
        
        // Original store method for general feedback
        return $this->storeGeneralFeedback($request);
    }

    /**
     * Store scoped feedback
     */
    private function storeScopedFeedback(Request $request, $projectType)
    {
        $modelClass = $this->getModelClass($projectType);
        
        if (!$modelClass) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid project type'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'feedbackable_id' => 'required|integer',
            'nama_pemberi_aspirasi' => 'required|string|max:255',
            'nama_proyek' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'jenis_tanggapan' => 'required|in:keluhan,saran,apresiasi,pertanyaan',
            'tanggapan' => 'required|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'laporan_gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'feedbackable_id.required' => 'Proyek wajib dipilih',
            'nama_pemberi_aspirasi.required' => 'Nama pemberi aspirasi wajib diisi',
            'nama_proyek.required' => 'Nama proyek wajib diisi',
            'kabupaten_kota.required' => 'Kabupaten/Kota wajib dipilih',
            'jenis_tanggapan.required' => 'Jenis tanggapan wajib dipilih',
            'tanggapan.required' => 'Tanggapan wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate that the project exists
            if (!$modelClass::find($request->feedbackable_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Proyek yang dipilih tidak ditemukan'
                ], 404);
            }

            $data = $request->all();
            $data['status'] = 'pending';
            $data['feedbackable_type'] = $modelClass;

            // Handle image upload
            if ($request->hasFile('laporan_gambar')) {
                $image = $request->file('laporan_gambar');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/feedback_images', $imageName);
                $data['laporan_gambar'] = $imageName;
            }

            $feedback = ProjectFeedback::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Tanggapan berhasil ditambahkan',
                'data' => $feedback->load('feedbackable')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store general feedback (original method) - ASPIRASI
     */
    private function storeGeneralFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_pemberi_aspirasi' => 'required|string|max:255',
            'nama_proyek' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'jenis_tanggapan' => 'required|in:keluhan,saran,apresiasi,pertanyaan',
            'tanggapan' => 'required|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'laporan_gambar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'nama_pemberi_aspirasi.required' => 'Nama pemberi aspirasi wajib diisi',
            'nama_proyek.required' => 'Nama proyek wajib diisi',
            'kabupaten_kota.required' => 'Kabupaten/Kota wajib dipilih',
            'jenis_tanggapan.required' => 'Jenis tanggapan wajib dipilih',
            'tanggapan.required' => 'Tanggapan wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['status'] = 'pending';

            // Handle image upload
            if ($request->hasFile('laporan_gambar')) {
                $image = $request->file('laporan_gambar');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/feedback_images', $imageName);
                $data['laporan_gambar'] = $imageName;
            }

            $feedback = ProjectFeedback::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Tanggapan berhasil ditambahkan',
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getModelClass($type)
    {
        $modelMap = [
            'usulan_musrenbang' => 'App\\Models\\UsulanMusrenbang',
            'proyek_strategis_nasional' => 'App\\Models\\ProyekStrategisNasional',
            'proyek_strategis_daerah' => 'App\\Models\\ProyekStrategisDaerah',
            'pokir_dprd' => 'App\\Models\\PokirDprd',
            'lokasi' => 'App\\Models\\Lokasi',
        ];

        return $modelMap[$type] ?? null;
    }

    private function getProjectTypeFromRequest(Request $request)
    {
        // Check if project_type is passed as parameter
        if ($request->has('project_type')) {
            return $request->get('project_type');
        }
        
        // Determine from URL path
        $path = $request->path();
        
        if (strpos($path, 'pokir/') !== false) {
            return 'pokir_dprd';
        } elseif (strpos($path, 'usulan/') !== false) {
            return 'usulan_musrenbang';
        } elseif (strpos($path, 'nasional/') !== false) {
            return 'proyek_strategis_nasional';
        } elseif (strpos($path, 'daerah/') !== false) {
            return 'proyek_strategis_daerah';
        } elseif (strpos($path, 'lokasi/') !== false) {
            return 'lokasi';
        }
        
        return 'all'; // Default to show all
    }
}
