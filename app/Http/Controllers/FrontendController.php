<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Dokumen;
use App\Models\ProjectFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FrontendController extends Controller
{
    public function index()
    {
        // Ambil 6 data PSD secara random
        $psd = DataSpatial::where('data_type', 'proyek_strategis')
        ->where('sub_type', 'psd')
        ->inRandomOrder()->limit(6)->get();
        
        // Ambil 6 data PSN secara random
        $psn = DataSpatial::where('data_type', 'proyek_strategis')
        ->where('sub_type', 'psn')
        ->inRandomOrder()->limit(6)->get();
        
        // Ambil 6 data Pokir secara random
        $pokir = DataSpatial::where('data_type', 'pokir_dprd')
        ->inRandomOrder()->limit(6)->get();
        
        // Ambil 6 data Musrenbang secara random
        $musrenbang = DataSpatial::where('data_type', 'musrenbang')
            ->inRandomOrder()->limit(6)->get();

        // Menggabungkan semuanya dengan concat
        $dataPeta = collect()->concat($psd)->concat($psn)->concat($pokir)->concat($musrenbang);

        // Total masing-masing kategori
        $totalPsd = DataSpatial::where('data_type', 'proyek_strategis')->where('sub_type', 'psd')->count();
        $totalPsn = DataSpatial::where('data_type', 'proyek_strategis')->where('sub_type', 'psn')->count();
        $totalMusrenbang = DataSpatial::where('data_type', 'musrenbang')->count();
        $totalPokir = DataSpatial::where('data_type', 'pokir_dprd')->count();

        return view('frontend.pages.index', compact(
            'dataPeta',
            'totalPsd',
            'totalPsn',
            'totalMusrenbang',
            'totalPokir'
        ));
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
    
    // NANTINYA DIISI PETA RPJMD //
    public function prioritas()
    {
        $documents = Dokumen::all();
        return view('frontend.pages.prioritas', compact('documents'));
    }

    // API - AMBIL DATA GEOJSON BERDASARKAN DATA_TYPE //
    public function getGeojsonByDataType(Request $request)
    {
        $dataType = $request->get('type');
        $subType = $request->get('sub_type');
        $year = $request->get('year');

        $query = DB::table('data_spatial')
            ->join('categories', 'data_spatial.kategori_id', '=', 'categories.id')
            ->select(
                'data_spatial.id',
                'data_spatial.uuid',
                'data_spatial.data_type',
                'data_spatial.sub_type',
                'data_spatial.gambar',
                'data_spatial.kategori_id',
                'data_spatial.tahun',
                'categories.nama as kategori',
                'data_spatial.deskripsi',
                'data_spatial.dbf_attributes',
                'categories.icon',
                'categories.warna',
                'categories.is_marker',
                DB::raw('ST_AsGeoJSON(data_spatial.geom) as geojson')
            );

        if ($dataType) {
            $query->where('data_spatial.data_type', $dataType);
        }

        if ($subType) {
            $query->where('data_spatial.sub_type', $subType);
        }

        if ($year) {
            $query->where('data_spatial.tahun', $year);
        }

        if ($request->has('kategori') && !empty($request->kategori)) {
            $categories = is_array($request->kategori) ? $request->kategori : [$request->kategori];
            $query->whereIn('categories.nama', $categories);
        }

        if ($request->has('dbf_filter') && !empty($request->dbf_filter)) {
            foreach ($request->dbf_filter as $attribute => $value) {
                $query->whereRaw("dbf_attributes->? = ?", [$attribute, json_encode($value)]);
            }
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('categories.nama', 'ILIKE', "%{$search}%")
                ->orWhere('data_spatial.deskripsi', 'ILIKE', "%{$search}%")
                ->orWhereRaw("dbf_attributes::text ILIKE ?", ["%{$search}%"]);
            });
        }

        if ($request->has('bbox') && !empty($request->bbox)) {
            $bbox = explode(',', $request->bbox);
            if (count($bbox) === 4) {
                $query->whereRaw("ST_Intersects(data_spatial.geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))", $bbox);
            }
        }

        $lokasis = $query->get(); // Langsung eksekusi tanpa cache

        $features = $lokasis->map(function ($lokasi) {
            $dbfAttributes = json_decode($lokasi->dbf_attributes, true) ?? [];

            return [
                'type' => 'Feature',
                'properties' => array_merge([
                    'id' => $lokasi->id,
                    'uuid' => $lokasi->uuid,
                    'data_type' => $lokasi->data_type,
                    'sub_type' => $lokasi->sub_type,
                    'gambar' => $lokasi->gambar ? asset('storage/' . $lokasi->gambar) : null,
                    'kategori_id' => $lokasi->kategori_id,
                    'kategori' => $lokasi->kategori,
                    'tahun' => $lokasi->tahun,
                    'deskripsi' => $lokasi->deskripsi,
                    'icon' => $lokasi->icon,
                    'warna' => $lokasi->warna,
                    'is_marker' => (bool) $lokasi->is_marker,
                ], $dbfAttributes),
                'geometry' => json_decode($lokasi->geojson),
            ];
        });

        $categoryType = $this->getCategoryTypeByDataType($dataType, $subType);

        // Kategori juga diambil tanpa cache
        $rootCategories = Category::where('type', $categoryType)
            ->with(['children' => function($query) {
                $query->orderBy('nama');
            }])
            ->roots()
            ->orderBy('nama')
            ->get();

        $allCategories = Category::where('type', $categoryType)
            ->with('parent')
            ->orderBy('nama')
            ->get();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'root_categories' => $rootCategories,
            'all_categories' => $allCategories,
            'meta' => [
                'data_type' => $dataType,
                'sub_type' => $subType,
                'year' => $year,
                'total_features' => $features->count(),
                'total_root_categories' => $rootCategories->count(),
                'total_categories' => $allCategories->count(),
                'generated_at' => now()->toISOString()
            ]
        ]);
    }


    public function getCategoryTypeByDataType($dataType, $subType)
    {
        return match ($dataType) {
            'lokasi' => 'layer',
            'usulan_musrenbang' => 'usulan_musrenbang',
            'pokir_dprd' => 'pokir_dprd',
            'proyek_strategis' => in_array($subType, ['psn', 'psd']) ? $subType : 'psd',
            default => 'layer',
        };
    }


    // DETAIL LOKASI //
    public function detailPeta(Request $request, $uuid)
    {
        $project = DataSpatial::select('*', DB::raw('ST_AsGeoJSON(geom) as geojson'))
            ->where('uuid', $uuid)
            ->firstOrFail();

        $project->geojson = json_decode($project->geojson);

        $projectType = $this->getProjectTypeFromRequest($request);

        return view('frontend.pages.detail', compact('project', 'projectType'));
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