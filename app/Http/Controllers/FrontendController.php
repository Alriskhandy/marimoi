<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\KategoriLayer;
use App\Models\KategoriMusrenbang;
use App\Models\KategoriPokirDprd;
use App\Models\KategoriPSD;
use App\Models\KategoriPSN;
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
        return view('frontend.pages.peta', compact('documents'));
    }

    public function psn()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.peta', compact('documents'));
    }

    public function rpjmd()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.peta', compact('documents'));
    }

    public function prioritas()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.prioritas', compact('documents'));
    }

    public function pokir()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.peta', compact('documents'));
    }
    public function musrenbang()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.peta', compact('documents'));
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
        $rootCategories = KategoriPSN::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->orderBy('nama');
            }])
            ->orderBy('nama')
            ->get();
                    
        $allCategories = KategoriPSN::with('parent')->orderBy('nama')->get();
                    
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
        $rootCategories = KategoriPokirDprd::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->orderBy('nama');
            }])
            ->orderBy('nama')
            ->get();
                    
        $allCategories = KategoriPokirDprd::with('parent')->orderBy('nama')->get();
                    
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
        $rootCategories = KategoriMusrenbang::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->orderBy('nama');
            }])
            ->orderBy('nama')
            ->get();
                    
        $allCategories = KategoriMusrenbang::with('parent')->orderBy('nama')->get();
                    
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


   /**
     * Store feedback for specific project type
     */
    public function store(Request $request)
    {
        $projectType = $this->getProjectTypeFromRequest($request);
        
        // If specific project type, validate and store accordingly
        if ($projectType && $projectType !== 'all') {
            return $this->storeScopedFeedback($request, $projectType);
        }
        
        // Original store method for general feedback
        return $this->storeGeneralFeedback($request);
    }

    /**
     * Store scoped feedback (PENGADUAN)
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

        // Dynamic validation rules based on whether image is required
        $rules = [
            'feedbackable_id' => 'required|integer',
            'nama_pemberi_aspirasi' => 'required|string|max:255',
            'nama_proyek' => 'nullable|string|max:255',
            'kabupaten_kota' => 'nullable|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'jenis_tanggapan' => 'required|in:keluhan,saran,apresiasi,pertanyaan',
            'tanggapan' => 'required|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];

        // Make image required only for keluhan (complaints)
        if ($request->jenis_tanggapan === 'keluhan') {
            $rules['laporan_gambar'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
        } else {
            $rules['laporan_gambar'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $messages = [
            'feedbackable_id.required' => 'Proyek wajib dipilih',
            'nama_pemberi_aspirasi.required' => 'Nama pemberi aspirasi wajib diisi',
            'jenis_tanggapan.required' => 'Jenis tanggapan wajib dipilih',
            'jenis_tanggapan.in' => 'Jenis tanggapan tidak valid',
            'tanggapan.required' => 'Tanggapan wajib diisi',
            'email.email' => 'Format email tidak valid',
            'phone.max' => 'Nomor telepon terlalu panjang',
            'laporan_gambar.required' => 'Lampiran gambar wajib untuk pengaduan',
            'laporan_gambar.image' => 'File harus berupa gambar',
            'laporan_gambar.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif',
            'laporan_gambar.max' => 'Ukuran gambar maksimal 2MB',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate that the project exists using dynamic resolution
            $projectExists = $this->checkProjectExists($modelClass, $request->feedbackable_id);
            
            if (!$projectExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Proyek yang dipilih tidak ditemukan'
                ], 404);
            }

            $data = $request->only([
                'feedbackable_id',
                'nama_pemberi_aspirasi',
                'nama_proyek',
                'kabupaten_kota',
                'kecamatan',
                'jenis_tanggapan',
                'tanggapan',
                'email',
                'phone',
                'latitude',
                'longitude'
            ]);

            $data['status'] = 'pending';
            $data['feedbackable_type'] = $modelClass;

            // Handle image upload
            if ($request->hasFile('laporan_gambar')) {
                $imageName = $this->handleImageUpload($request->file('laporan_gambar'));
                if ($imageName) {
                    $data['laporan_gambar'] = $imageName;
                }
            }

            $feedback = ProjectFeedback::create($data);

            // Load the feedback with its relationship
            $feedback->load('feedbackable');

            return response()->json([
                'status' => 'success',
                'message' => 'Tanggapan berhasil ditambahkan',
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            // \Log::error('Error storing scoped feedback: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data'
            ], 500);
        }
    }

    /**
     * Store general feedback (ASPIRASI)
     */
    private function storeGeneralFeedback(Request $request)
    {
        // Dynamic validation rules
        $rules = [
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
        ];

        // Make image required only for keluhan (complaints)
        if ($request->jenis_tanggapan === 'keluhan') {
            $rules['laporan_gambar'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
        } else {
            $rules['laporan_gambar'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $messages = [
            'nama_pemberi_aspirasi.required' => 'Nama pemberi aspirasi wajib diisi',
            'nama_proyek.required' => 'Nama proyek wajib diisi',
            'kabupaten_kota.required' => 'Kabupaten/Kota wajib dipilih',
            'jenis_tanggapan.required' => 'Jenis tanggapan wajib dipilih',
            'jenis_tanggapan.in' => 'Jenis tanggapan tidak valid',
            'tanggapan.required' => 'Tanggapan wajib diisi',
            'email.email' => 'Format email tidak valid',
            'phone.max' => 'Nomor telepon terlalu panjang',
            'laporan_gambar.required' => 'Lampiran gambar wajib untuk pengaduan',
            'laporan_gambar.image' => 'File harus berupa gambar',
            'laporan_gambar.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif',
            'laporan_gambar.max' => 'Ukuran gambar maksimal 2MB',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'nama_pemberi_aspirasi',
                'nama_proyek',
                'kabupaten_kota',
                'kecamatan',
                'jenis_tanggapan',
                'tanggapan',
                'email',
                'phone',
                'latitude',
                'longitude'
            ]);

            $data['status'] = 'pending';

            // Handle image upload
            if ($request->hasFile('laporan_gambar')) {
                $imageName = $this->handleImageUpload($request->file('laporan_gambar'));
                if ($imageName) {
                    $data['laporan_gambar'] = $imageName;
                }
            }

            $feedback = ProjectFeedback::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Tanggapan berhasil ditambahkan',
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            // \Log::error('Error storing general feedback: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data'
            ], 500);
        }
    }

    /**
     * Handle image upload
     */
    private function handleImageUpload($image)
    {
        try {
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            // Ensure directory exists
            $uploadPath = storage_path('app/public/feedback_images');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $image->storeAs('public/feedback_images', $imageName);
            return $imageName;
            
        } catch (\Exception $e) {
            // \Log::error('Error uploading image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if project exists safely
     */
    private function checkProjectExists($modelClass, $projectId)
    {
        try {
            if (!class_exists($modelClass)) {
                return false;
            }
            
            $modelInstance = app($modelClass);
            return $modelInstance->where('id', $projectId)->exists();
            
        } catch (\Exception $e) {
            // \Log::error('Error checking project existence: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get model class based on project type
     */
    private function getModelClass($type)
    {
        $modelMap = [
            'usulan_musrenbang' => 'App\\Models\\UsulanMusrenbang',
            'proyek_strategis_nasional' => 'App\\Models\\ProyekStrategisNasional',
            'proyek_strategis_daerah' => 'App\\Models\\ProyekStrategisDaerah',
            'pokir_dprd' => 'App\\Models\\PokirDprd',
            'lokasi' => 'App\\Models\\Lokasi',
        ];

        $className = $modelMap[$type] ?? null;
        
        // Check if class exists
        if ($className && class_exists($className)) {
            return $className;
        }
        
        return null;
    }

    /**
     * Determine project type from request
     */
    private function getProjectTypeFromRequest(Request $request)
    {
        // Check if project_type is passed as parameter
        if ($request->has('project_type')) {
            return $request->get('project_type');
        }
        
        // Determine from URL path
        $path = $request->path();
        
        if (str_contains($path, 'pokir/')) {
            return 'pokir_dprd';
        } elseif (str_contains($path, 'usulan/')) {
            return 'usulan_musrenbang';
        } elseif (str_contains($path, 'nasional/')) {
            return 'proyek_strategis_nasional';
        } elseif (str_contains($path, 'daerah/')) {
            return 'proyek_strategis_daerah';
        } elseif (str_contains($path, 'lokasi/')) {
            return 'lokasi';
        }
        
        return 'all'; // Default to show all
    }

    /**
     * Get available projects safely based on your model structure
     */
    private function getAvailableProjects($projectType)
    {
        $modelClass = $this->getModelClass($projectType);
        
        if (!$modelClass) {
            return collect();
        }
        
        try {
            $modelInstance = app($modelClass);
            
            // For your ProyekStrategisDaerah model, use 'deskripsi' column
            if ($projectType === 'proyek_strategis_daerah') {
                return $modelInstance->select('id', 'deskripsi', 'tahun', 'dbf_attributes')
                    ->orderBy('tahun', 'desc')
                    ->limit(50)
                    ->get()
                    ->map(function($item) {
                        // Use description from deskripsi or dbf_attributes
                        $description = $item->deskripsi;
                        if (!$description && $item->dbf_attributes) {
                            $description = $item->dbf_attributes['KEGIATAN'] ?? 
                                         $item->dbf_attributes['NAMA'] ?? 
                                         "Proyek Tahun {$item->tahun}";
                        }
                        $item->display_name = $description ?: "Proyek ID: {$item->id}";
                        return $item;
                    });
            }
            
            // For other models, try common column names
            $possibleColumns = ['deskripsi', 'nama', 'title', 'judul'];
            
            foreach ($possibleColumns as $column) {
                try {
                    return $modelInstance->select('id', $column . ' as deskripsi')
                        ->limit(50)
                        ->get()
                        ->map(function($item) {
                            $item->display_name = $item->deskripsi ?: "Proyek ID: {$item->id}";
                            return $item;
                        });
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Fallback - just get id
            return $modelInstance->select('id')->limit(50)->get()->map(function($item) {
                $item->display_name = "Proyek ID: {$item->id}";
                return $item;
            });
            
        } catch (\Exception $e) {
            // \Log::error('Error getting available projects: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Load project safely with proper data extraction
     */
    private function loadProject($projectType, $projectId)
    {
        $modelClass = $this->getModelClass($projectType);
        
        if (!$modelClass) {
            return null;
        }
        
        try {
            $modelInstance = app($modelClass);
            $project = $modelInstance->find($projectId);
            
            if ($project && $projectType === 'proyek_strategis_daerah') {
                // Add additional data for ProyekStrategisDaerah
                $project->geojson = $project->geom; // Assuming geom contains the GeoJSON
                
                // Ensure dbf_attributes is properly accessible
                if (!$project->dbf_attributes) {
                    $project->dbf_attributes = [];
                }
            }
            
            return $project;
        } catch (\Exception $e) {
            // \Log::error('Error loading project: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Show form for creating feedback
     */
    public function create(Request $request, $projectId = null)
    {
        $projectType = $this->getProjectTypeFromRequest($request);
        $project = null;
        $availableProjects = collect();
        
        // Try to load specific project
        if ($projectId && $projectType !== 'all') {
            $project = $this->loadProject($projectType, $projectId);
        }
        
        // If no specific project, get available projects
        if (!$project) {
            if ($projectType && $projectType !== 'all') {
                $availableProjects = $this->getAvailableProjects($projectType);
            }
        }
        
        // Get kabupaten list from your ProyekStrategisDaerah data or define manually
        $kabupaten_list = $this->getKabupatenList();
        
        return view('feedback.create', compact('project', 'projectType', 'availableProjects', 'kabupaten_list'));
    }

    /**
     * Get kabupaten list from database or return default list
     */
    private function getKabupatenList()
    {
        try {
            // Try to get unique kabupaten from ProyekStrategisDaerah dbf_attributes
            $kabupatenFromDb = app('App\\Models\\ProyekStrategisDaerah')
                ->whereNotNull('dbf_attributes')
                ->get()
                ->pluck('dbf_attributes')
                ->map(function($attr) {
                    return $attr['KABUPATEN'] ?? $attr['KOTA'] ?? null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            if (!empty($kabupatenFromDb)) {
                return $kabupatenFromDb;
            }
        } catch (\Exception $e) {
            // \Log::info('Could not load kabupaten from database, using default list');
        }

        // Default kabupaten list
        return [
            'Aceh Barat', 'Aceh Besar', 'Aceh Jaya', 'Aceh Selatan', 'Aceh Singkil',
            'Badung', 'Bandung', 'Bandung Barat', 'Banjarnegara', 'Bantul',
            'Bekasi', 'Bogor', 'Cianjur', 'Depok', 'Garut', 'Indramayu',
            'Jakarta Barat', 'Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Timur', 'Jakarta Utara',
            'Karawang', 'Kuningan', 'Majalengka', 'Purwakarta', 'Subang', 'Sukabumi', 'Sumedang',
            'Tangerang', 'Tasikmalaya', 'Yogyakarta'
        ];
    }

   
}
