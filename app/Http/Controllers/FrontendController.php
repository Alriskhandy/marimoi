<?php

namespace App\Http\Controllers;

use App\Mail\AspirasiMail;
use App\Mail\TanggapanMail;
use App\Models\Aspirasi;
use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Dokumen;
use App\Models\KategoriAspirasi;
use App\Models\ProjectFeedback;
use App\Models\User;
use App\Models\Visitor;
use App\Rules\ValidHCaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FrontendController extends Controller
{
    // public function index()
    // {
    //     // Ambil 6 data PSD secara random
    //     $psd = DataSpatial::where('data_type', 'proyek_strategis')
    //     ->where('sub_type', 'psd')
    //     ->orderBy('views', 'desc')->limit(6)->get();

    //     // Ambil 6 data PSN secara random
    //     $psn = DataSpatial::where('data_type', 'proyek_strategis')
    //     ->where('sub_type', 'psn')
    //     ->orderBy('views', 'desc')->limit(6)->get();

    //     // Ambil 6 data Pokir secara random
    //     $pokir = DataSpatial::where('data_type', 'pokir_dprd')
    //     ->orderBy('views', 'desc')->limit(6)->get();

    //     // Ambil 6 data Musrenbang secara random
    //     $musrenbang = DataSpatial::where('data_type', 'usulan_musrenbang')
    //     ->orderBy('views', 'desc')->limit(6)->get();

    //     // Menggabungkan semuanya dengan concat
    //     $dataPeta = collect()->concat($psd)->concat($psn)->concat($pokir)->concat($musrenbang);

    //     $links = [
    //         'psd' => 'proyek-strategis-daerah',
    //         'psn' => 'proyek-strategis-nasional',
    //         'pokir_dprd' => 'pokir-dprd',
    //         'usulan_musrenbang' => 'usulan-musrenbang',
    //     ];

    //     // Total masing-masing kategori
    //     $totalPsd = DataSpatial::where('data_type', 'proyek_strategis')->where('sub_type', 'psd')->count();
    //     $totalPsn = DataSpatial::where('data_type', 'proyek_strategis')->where('sub_type', 'psn')->count();
    //     $totalMusrenbang = DataSpatial::where('data_type', 'usulan_musrenbang')->count();
    //     $totalPokir = DataSpatial::where('data_type', 'pokir_dprd')->count();

    //     return view('frontend.pages.index-dark', compact(
    //         'dataPeta',
    //         'links',
    //         'totalPsd',
    //         'totalPsn',
    //         'totalMusrenbang',
    //         'totalPokir'
    //     ));
    // }

    public function indexDark()
    {
        $petaTematik = Category::where('type', 'tematik')
            ->where('is_active', true)
            ->where('gambar', '!=', null)
            ->get();

        $totalKritik = Aspirasi::where('jenis_aspirasi', 'kritik & saran')->count();
        $totalUsulan = Aspirasi::where('jenis_aspirasi', 'usulan')->count();

        // Real visitor counts using Visitor model scopes
        $visitorsToday = Visitor::today()->count();
        $visitorsWeek = Visitor::thisWeek()->count();
        $visitorsMonth = Visitor::thisMonth()->count();
        $visitorsTotal = Visitor::count();

        return view('frontend.pages.index-dark', compact(
            'petaTematik',
            'totalKritik',
            'totalUsulan',
            'visitorsToday',
            'visitorsWeek',
            'visitorsMonth',
            'visitorsTotal'
        ));
    }

    public function reformer()
    {
        return view('frontend.pages.reformer');
    }

    public function publikasi()
    {
        return view('frontend.pages.publikasi');
    }

    public function aspirasi()
    {
        $aspirasi = KategoriAspirasi::where('nama_kategori', '!=', 'Kritik dan Saran')->get();
        return view('frontend.pages.aspirasi', compact('aspirasi'));
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

    public function tematik()
    {
        $documents = Dokumen::all();

        // Get selected category from session if exists
        $selectedCategory = session('selectedCategory');

        return view('frontend.pages.peta', compact('documents', 'selectedCategory'));
    }

    public function lihatTematik($id)
    {
        try {
            $category = Category::findOrFail($id);

            // Store selected category in session
            session(['selectedCategory' => $category->nama]);

            // Flash message for user feedback
            session()->flash('info', "Memuat peta {$category->nama}...");

            return redirect()->route('tampil.tematik');
        } catch (\Exception $e) {
            Log::error('Error in lihatTematik: ' . $e->getMessage());

            return redirect()->route('tampil.tematik')
                ->with('error', 'Kategori peta tidak ditemukan.');
        }
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

    // API - AMBIL DATA GEOJSON BERDASARKAN DATA_TYPE - OPTIMIZED VERSION //
    public function getGeojsonByDataType(Request $request)
    {
        try {
            $dataType = $request->get('type');
            $subType = $request->get('sub_type');
            $year = $request->get('year');
            $metadataOnly = $request->boolean('metadata_only');

            // Jika hanya butuh metadata (kategori), return categories saja
            if ($metadataOnly) {
                return $this->getCategoriesMetadata($dataType, $subType);
            }

            // Build base query with proper joins and error handling
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
                    'categories.is_marker'
                );

            // Only select geometry if it exists and is valid
            try {
                $query->addSelect(DB::raw('ST_AsGeoJSON(data_spatial.geom) as geojson'));
            } catch (\Exception $e) {
                // If ST_AsGeoJSON fails, fall back to simple geometry selection
                Log::warning('ST_AsGeoJSON failed, using alternative method: ' . $e->getMessage());
                $query->addSelect('data_spatial.geom as geojson');
            }

            // Apply filters with validation
            if ($dataType && is_string($dataType)) {
                $query->where('data_spatial.data_type', $dataType);
            }

            if ($subType && is_string($subType)) {
                $query->where('data_spatial.sub_type', $subType);
            }

            if ($year && is_numeric($year)) {
                $query->where('data_spatial.tahun', intval($year));
            }

            // Filter by specific categories (untuk on-demand loading)
            if ($request->has('kategori') && !empty($request->kategori)) {
                $categories = is_array($request->kategori) ? $request->kategori : [$request->kategori];
                // Sanitize category names
                $categories = array_filter(array_map('trim', $categories));
                if (!empty($categories)) {
                    $query->whereIn('categories.nama', $categories);
                }
            }

            // Bounding box filter dengan validasi koordinat
            if ($request->has('bbox') && !empty($request->bbox)) {
                $bbox = explode(',', $request->bbox);
                if (count($bbox) === 4) {
                    $bbox = array_map('floatval', $bbox);
                    // Validate bbox coordinates
                    if (
                        $bbox[0] >= -180 && $bbox[0] <= 180 &&
                        $bbox[1] >= -90 && $bbox[1] <= 90 &&
                        $bbox[2] >= -180 && $bbox[2] <= 180 &&
                        $bbox[3] >= -90 && $bbox[3] <= 90
                    ) {
                        try {
                            $query->whereRaw("ST_Intersects(data_spatial.geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))", $bbox);
                        } catch (\Exception $e) {
                            Log::warning('Bounding box filter failed: ' . $e->getMessage());
                        }
                    }
                }
            }

            // Search filter dengan sanitasi
            if ($request->has('search') && !empty($request->search)) {
                $search = trim($request->search);
                if (strlen($search) > 0) {
                    $query->where(function ($q) use ($search) {
                        $q->where('categories.nama', 'ILIKE', "%{$search}%")
                            ->orWhere('data_spatial.deskripsi', 'ILIKE', "%{$search}%");

                        // Only add JSON search if dbf_attributes column exists
                        try {
                            $q->orWhereRaw("dbf_attributes::text ILIKE ?", ["%{$search}%"]);
                        } catch (\Exception $e) {
                            Log::debug('DBF attributes search skipped: ' . $e->getMessage());
                        }
                    });
                }
            }

            // DBF attribute filter dengan validasi JSON
            if ($request->has('dbf_filter') && !empty($request->dbf_filter) && is_array($request->dbf_filter)) {
                foreach ($request->dbf_filter as $attribute => $value) {
                    if (is_string($attribute) && !empty($attribute)) {
                        try {
                            $query->whereRaw("dbf_attributes->? = ?", [$attribute, json_encode($value)]);
                        } catch (\Exception $e) {
                            Log::warning("DBF filter failed for {$attribute}: " . $e->getMessage());
                        }
                    }
                }
            }

            // Enhanced limit and offset with maximum cap
            $limit = min(intval($request->get('limit', 500)), 3000); // Max 3000 records
            $offset = max(0, intval($request->get('offset', 0)));

            // Apply limit and offset
            $query->limit($limit)->offset($offset);

            // Add ordering untuk konsistensi
            $query->orderBy('data_spatial.id');

            // Execute query with timeout protection
            $startTime = microtime(true);
            $lokasis = $query->get();
            $queryTime = microtime(true) - $startTime;

            Log::info("Query executed in {$queryTime} seconds, returned " . $lokasis->count() . " records");

            // Check if query took too long
            if ($queryTime > 30) {
                Log::warning("Slow query detected: {$queryTime} seconds");
            }

            $features = [];
            $processedCount = 0;

            foreach ($lokasis as $lokasi) {
                try {
                    // Safely decode DBF attributes
                    $dbfAttributes = [];
                    if (!empty($lokasi->dbf_attributes)) {
                        if (is_string($lokasi->dbf_attributes)) {
                            $decoded = json_decode($lokasi->dbf_attributes, true);
                            if (is_array($decoded)) {
                                $dbfAttributes = $decoded;
                            }
                        } elseif (is_array($lokasi->dbf_attributes)) {
                            $dbfAttributes = $lokasi->dbf_attributes;
                        }
                    }

                    // Handle geometry safely
                    $geometry = null;
                    if (!empty($lokasi->geojson)) {
                        if (is_string($lokasi->geojson)) {
                            $geometry = json_decode($lokasi->geojson);
                        } else {
                            $geometry = $lokasi->geojson;
                        }
                    }

                    $feature = [
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
                        'geometry' => $geometry,
                    ];

                    $features[] = $feature;
                    $processedCount++;
                } catch (\Exception $featureError) {
                    Log::error("Error processing feature {$lokasi->id}: " . $featureError->getMessage());
                    // Continue processing other features
                    continue;
                }
            }

            $categoryType = $this->getCategoryTypeByDataType($dataType, $subType);

            // Ambil categories untuk reference dengan error handling
            $rootCategories = [];
            $allCategories = [];

            try {
                $rootCategories = Category::where('type', $categoryType)
                    ->with(['children' => function ($query) {
                        $query->orderBy('nama');
                    }])
                    ->roots()
                    ->orderBy('nama')
                    ->get();

                $allCategories = Category::where('type', $categoryType)
                    ->with('parent')
                    ->orderBy('nama')
                    ->get();
            } catch (\Exception $e) {
                Log::warning('Failed to load categories: ' . $e->getMessage());
            }

            $response = [
                'type' => 'FeatureCollection',
                'features' => $features,
                'root_categories' => $rootCategories,
                'all_categories' => $allCategories,
                'meta' => [
                    'data_type' => $dataType,
                    'sub_type' => $subType,
                    'year' => $year,
                    'total_features' => count($features),
                    'total_root_categories' => count($rootCategories),
                    'total_categories' => count($allCategories),
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => count($features) == $limit, // Indikasi ada data lagi
                    'query_time' => round($queryTime, 3),
                    'processed_count' => $processedCount,
                    'max_limit' => 3000,
                    'generated_at' => now()->toISOString()
                ]
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error in getGeojsonByDataType: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Terjadi kesalahan saat memuat data.',
                'details' => config('app.debug') ? $e->getMessage() : null,
                'meta' => [
                    'limit' => 3000,
                    'max_limit' => 3000,
                    'generated_at' => now()->toISOString()
                ]
            ], 500);
        }
    }

    /**
     * Get only categories metadata without spatial data
     */
    private function getCategoriesMetadata($dataType, $subType)
    {
        try {
            $categoryType = $this->getCategoryTypeByDataType($dataType, $subType);

            $rootCategories = Category::where('type', $categoryType)
                ->with(['children' => function ($query) {
                    $query->orderBy('nama');
                }])
                ->roots()
                ->orderBy('nama')
                ->get();

            $allCategories = Category::where('type', $categoryType)
                ->with('parent')
                ->orderBy('nama')
                ->get();

            // Hitung jumlah data per kategori (optional, bisa di-comment jika lambat)
            $categoryCounts = [];
            foreach ($allCategories as $category) {
                $count = DataSpatial::where('kategori_id', $category->id);
                if ($dataType) {
                    $count->where('data_type', $dataType);
                }
                if ($subType) {
                    $count->where('sub_type', $subType);
                }
                $categoryCounts[$category->nama] = $count->count();
            }

            return response()->json([
                'type' => 'MetadataCollection',
                'root_categories' => $rootCategories,
                'all_categories' => $allCategories,
                'category_counts' => $categoryCounts,
                'meta' => [
                    'data_type' => $dataType,
                    'sub_type' => $subType,
                    'category_type' => $categoryType,
                    'total_root_categories' => $rootCategories->count(),
                    'total_categories' => $allCategories->count(),
                    'generated_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getCategoriesMetadata: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Gagal memuat metadata kategori.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getCategoryTypeByDataType($dataType, $subType)
    {
        return match ($dataType) {
            'tematik' => 'tematik',
            'usulan_musrenbang' => 'usulan_musrenbang',
            'pokir_dprd' => 'pokir_dprd',
            'proyek_strategis' => in_array($subType, ['psn', 'psd']) ? $subType : 'psd',
            default => 'tematik',
        };
    }

    // DETAIL LOKASI //
    public function detailPeta(Request $request, $uuid)
    {
        $project = DataSpatial::select('*', DB::raw('ST_AsGeoJSON(geom) as geojson'))
            ->where('uuid', $uuid)
            ->firstOrFail();

        $project->increment('views');

        $project->geojson = json_decode($project->geojson);

        $projectType = $this->getProjectTypeFromRequest($request);

        return view('frontend.pages.detail', compact('project', 'projectType'));
    }

    public function detailPetaTematik(Request $request, $uuid)
    {
        $project = DataSpatial::select('*', DB::raw('ST_AsGeoJSON(geom) as geojson'))
            ->where('uuid', $uuid)
            ->firstOrFail();

        $project->increment('views');

        $project->geojson = json_decode($project->geojson);

        $projectType = $this->getProjectTypeFromRequest($request);

        return view('frontend.pages.detailTematik', compact('project', 'projectType'));
    }

    /**
     * Store feedback for specific project type
     */
    public function store(Request $request)
    {
        // Rules untuk validasi inputan user
        $rules = [
            'data_spatial_id' => 'required',
            'nama_pemberi_aspirasi' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'nama_proyek' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'jenis_tanggapan' => 'required|in:keluhan,saran,apresiasi,pertanyaan',
            'tanggapan' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'h-captcha-response' => ['required', new ValidHCaptcha()],
        ];

        // Cek Jenis Tanggapan, jika pengaduan maka wajib ada file
        if ($request->jenis_tanggapan === 'keluhan') {
            $rules['laporan_gambar'] = 'required|file|mimes:jpeg,png,jpg|max:5120';
        } else {
            $rules['laporan_gambar'] = 'nullable|file|mimes:jpeg,png,jpg|max:5120';
        }

        $messages = [
            'data_spatial_id.required' => 'Id Kegiatan tidak ada',
            'nama_pemberi_aspirasi.required' => 'Nama wajib diisi',
            'nama_pemberi_aspirasi.min' => 'Nama minimal 3 karakter',
            'nama_pemberi_aspirasi.max' => 'Nama maksimal 100 karakter',
            'jenis_tanggapan.required' => 'Jenis tanggapan wajib dipilih',
            'jenis_tanggapan.in' => 'Jenis tanggapan tidak valid',
            'tanggapan.required' => 'Tanggapan wajib diisi',
            'email.email' => 'Format email tidak valid',
            'phone.max' => 'Nomor telepon terlalu panjang',
            'laporan_gambar.required' => 'Lampiran gambar wajib untuk pengaduan',
            'laporan_gambar.file' => 'Lampiran harus berupa gambar dengan format yang benar',
            'laporan_gambar.mimes' => 'Format Lampiran harus jpeg, png, atau jpg',
            'laporan_gambar.max' => 'Ukuran file maksimal 5MB',
            'h-captcha-response.required' => 'Verifikasi CAPTCHA wajib diselesaikan',
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
            $dataSpatialExists = DataSpatial::find($request->data_spatial_id);

            if (!$dataSpatialExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Proyek yang dipilih tidak ditemukan'
                ], 404);
            }

            $user = User::find($dataSpatialExists->user_id);

            // Data dari request user
            $data = $request->only([
                'data_spatial_id',
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

            // Tambahkan data status = pending (default);
            $data['status'] = 'pending';
            $data['opd_id'] = $user->opd_id;

            // Handle single file upload langsung
            if ($request->hasFile('laporan_gambar')) {
                $file = $request->file('laporan_gambar');

                if ($file->isValid()) {
                    // Generate unique filename
                    $timestamp = now()->timestamp;
                    $randomString = Str::random(13);
                    $extension = $file->getClientOriginalExtension();
                    $filename = $timestamp . '_' . $randomString . '.' . $extension;

                    // Store file
                    $path = $file->storeAs('aspirasi_lampiran', $filename, 'public');

                    if ($path) {
                        $data['laporan_gambar'] = $filename;

                        Log::info('File uploaded', [
                            'original' => $file->getClientOriginalName(),
                            'saved' => $filename,
                            'size' => $file->getSize()
                        ]);
                    }
                }
            }

            ProjectFeedback::create($data);

            // Data untuk user
            $userData = [
                'nama'      => $request->nama_pemberi_aspirasi,
                'email'     => $request->email,
                'tanggapan' => $request->tanggapan,
                'tanggal'   => now()->format('d-m-Y H:i'),
            ];

            // Data untuk admin
            $adminData = [
                'nama'      =>  $request->nama_pemberi_aspirasi,
                'email'     =>  $request->email,
                'tanggapan' => $request->tanggapan,
                'tanggal'   => now()->format('d-m-Y H:i'),
                'nama_proyek' => $request->nama_proyek,
                'kabupaten_kota' => $request->kabupaten_kota,
                'kecamatan'     => $request->kecamatan,
                'jenis_tanggapan' => $request->jenis_tanggapan,
            ];

            // Kirim email penerimaan ke pengguna jika ada email
            if ($request->filled('email')) {
                Mail::to($request->email)->queue(new TanggapanMail($userData, 'penerimaan'));
            }

            // Kirim notifikasi ke admin (gunakan email admin dari user/project terkait)
            $adminEmail = $user->email ?? config('mail.from.address');
            Mail::to($adminEmail)->queue(new TanggapanMail($adminData, 'admin'));

            return response()->json([
                'status' => 'success',
                'message' => 'Tanggapan berhasil ditambahkan',
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing feedback: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function aspirasiStore(Request $request)
    {
        // Base rules yang berlaku untuk semua jenis aspirasi
        $rules = [
            'nama_pengirim' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'alamat' => 'required|string|min:5|max:200',
            'jenis_aspirasi' => 'required|in:usulan,kritik & saran',
            'judul_aspirasi' => 'required|string|min:5|max:150',
            'isi_aspirasi' => 'required|string|min:10|max:1000',
            'agreement' => 'required|accepted',
            'h-captcha-response' => ['required', new ValidHCaptcha()],
        ];

        // Validasi berdasarkan jenis aspirasi
        if ($request->jenis_aspirasi === 'usulan') {
            $rules['kategori_aspirasi_id'] = 'required|exists:kategori_aspirasi,id';
            $rules['latitude'] = 'required|numeric|between:-90,90';
            $rules['longitude'] = 'required|numeric|between:-180,180';
            $rules['lampiran'] = 'required|file|mimes:jpeg,png,jpg,doc,docx,pdf,|max:5120';
        } elseif ($request->jenis_aspirasi === 'kritik & saran') {
            // Untuk Kritik & Saran: semua field wajib diisi kecuali lampiran (tidak wajib)
            $rules['lampiran'] = 'nullable|file|mimes:jpeg,png,jpg,doc,docx,pdf,|max:5120';
            $rules['latitude'] = 'nullable|numeric|between:-90,90';
            $rules['longitude'] = 'nullable|numeric|between:-180,180';
        }

        // Custom messages untuk validasi
        $messages = [
            'nama_pengirim.required' => 'Nama lengkap wajib diisi',
            'nama_pengirim.min' => 'Nama lengkap minimal 3 karakter',
            'nama_pengirim.max' => 'Nama lengkap maksimal 100 karakter',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'phone.required' => 'Nomor WhatsApp wajib diisi',
            'phone.regex' => 'Format nomor WhatsApp tidak valid (contoh: 08xxxxxxxxxx)',
            'alamat.required' => 'Alamat wajib diisi',
            'alamat.min' => 'Alamat minimal 5 karakter',
            'alamat.max' => 'Alamat maksimal 200 karakter',
            'jenis_aspirasi.required' => 'Jenis aspirasi wajib dipilih',
            'jenis_aspirasi.in' => 'Jenis aspirasi tidak valid',
            'judul_aspirasi.required' => 'Judul aspirasi wajib diisi',
            'judul_aspirasi.min' => 'Judul aspirasi minimal 5 karakter',
            'judul_aspirasi.max' => 'Judul aspirasi maksimal 150 karakter',
            'isi_aspirasi.required' => 'Isi aspirasi wajib diisi',
            'isi_aspirasi.min' => 'Isi aspirasi minimal 10 karakter',
            'isi_aspirasi.max' => 'Isi aspirasi maksimal 1000 karakter',
            'kategori_aspirasi_id.required' => 'Kategori usulan wajib dipilih untuk usulan pembangunan',
            'kategori_aspirasi_id.exists' => 'Kategori usulan tidak valid',
            'latitude.required' => 'Lokasi wajib diisi untuk usulan pembangunan',
            'longitude.required' => 'Lokasi wajib diisi untuk usulan pembangunan',
            'latitude.numeric' => 'Koordinat latitude harus berupa angka',
            'latitude.between' => 'Koordinat latitude tidak valid',
            'longitude.numeric' => 'Koordinat longitude harus berupa angka',
            'longitude.between' => 'Koordinat longitude tidak valid',
            'lampiran.required' => 'Lampiran wajib disertakan untuk usulan pembangunan.',
            'lampiran.file' => 'Lampiran harus berupa file',
            'lampiran.mimes' => 'Format lampiran: JPG, PNG, JPEG, DOC, DOCX, atau PDF.',
            'lampiran.max' => 'Ukuran lampiran maksimal 5MB',
            'agreement.required' => 'Anda harus menyetujui syarat dan ketentuan',
            'agreement.accepted' => 'Anda harus menyetujui syarat dan ketentuan',
            'h-captcha-response.required' => 'Verifikasi CAPTCHA wajib diselesaikan',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        // Jika validasi gagal, kembalikan response dengan error
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal. Periksa kembali data Anda.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Data dari request user
            $data = $request->only([
                'nama_pengirim',
                'email',
                'phone',
                'alamat',
                'jenis_aspirasi',
                'judul_aspirasi',
                'isi_aspirasi'
            ]);

            // Set default status ke pending;
            $data['status'] = 'pending';

            // Ambil data admin dan OPD di awal untuk memastikan ketersediaan data
            $adminData = User::where('role_id', 1)->first();
            $opdData = null;

            if ($request->jenis_aspirasi === 'usulan' && $request->kategori_aspirasi_id) {
                $opdData = KategoriAspirasi::with('opd')->find($request->kategori_aspirasi_id);
            }

            // Handle data berdasarkan jenis aspirasi
            if ($request->jenis_aspirasi === 'usulan') {
                $data['admin_id'] = $opdData && $opdData->opd ? $opdData->opd->id : ($adminData ? $adminData->id : null);
                $data['kategori_aspirasi_id'] = $request->kategori_aspirasi_id;
                $data['latitude'] = $request->latitude;
                $data['longitude'] = $request->longitude;
            } else {
                $data['admin_id'] = $adminData ? $adminData->id : null;
                $data['kategori_aspirasi_id'] = 1; // ID kategori default untuk kritik & saran
                $data['latitude'] = $request->latitude ?? null;
                $data['longitude'] = $request->longitude ?? null;
            }

            // Handle file(lampiran) upload
            if ($request->hasFile('lampiran')) {
                $file = $request->file('lampiran');

                if ($file->isValid()) {
                    try {
                        // Generate unique filename
                        $timestamp = now()->timestamp;
                        $randomString = Str::random(13);
                        $extension = $file->getClientOriginalExtension();
                        $filename = $timestamp . '_' . $randomString . '.' . $extension;

                        // Store file
                        $path = $file->storeAs('aspirasi_lampiran', $filename, 'public');

                        if ($path) {
                            $data['lampiran'] = $filename;
                            Log::info('Lampiran uploaded', [
                                'original' => $file->getClientOriginalName(),
                                'saved' => $filename,
                                'size' => $file->getSize()
                            ]);
                        } else {
                            Log::error('Failed to store file');
                        }
                    } catch (\Exception $e) {
                        Log::error('File upload error: ' . $e->getMessage());
                    }
                }
            }

            // Create aspirasi record
            $aspirasi = Aspirasi::create($data);

            // Prepare data for notifications
            $userData = [
                'id_aspirasi' => $aspirasi->id,
                'nomor_tiket' => $aspirasi->nomor_tiket,
                'nama_pengirim' => $data['nama_pengirim'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'alamat' => $data['alamat'],
                'jenis_aspirasi' => $data['jenis_aspirasi'],
                'judul_aspirasi' => $data['judul_aspirasi'],
                'isi_aspirasi' => $data['isi_aspirasi'],
                'tanggal' => $aspirasi->created_at->format('d-m-Y H:i:s'),
                'kategori_aspirasi' => $opdData ? $opdData->nama_kategori : 'N/A',
                'opd_terkait' => ($opdData && $opdData->opd) ? $opdData->opd->singkatan : 'N/A'
            ];

            // 1. Kirim email konfirmasi ke user (masyarakat)
            if ($request->filled('email')) {
                try {
                    Mail::to($request->email)->queue(new AspirasiMail($userData, 'penerimaan'));
                    Log::info('Confirmation email queued for user: ' . $request->email);
                } catch (\Exception $e) {
                    Log::error('Failed to queue user email: ' . $e->getMessage());
                }
            }

            // 2. Kirim email notifikasi ke admin sistem
            if ($adminData && !empty($adminData->email)) {
                try {
                    Mail::to($adminData->email)->queue(new AspirasiMail($userData, 'admin'));
                    Log::info('Admin notification email queued for: ' . $adminData->email);
                } catch (\Exception $e) {
                    Log::error('Failed to queue admin email: ' . $e->getMessage());
                }
            }

            // 3. Kirim email notifikasi ke OPD terkait (hanya untuk usulan)
            if ($request->jenis_aspirasi === 'usulan' && $opdData && $opdData->opd && !empty($opdData->opd->email)) {
                try {
                    Mail::to($opdData->opd->email)->queue(new AspirasiMail($userData, 'opd'));
                    Log::info('OPD notification email queued for: ' . $opdData->opd->email);
                } catch (\Exception $e) {
                    Log::error('Failed to queue OPD email: ' . $e->getMessage());
                }
            }

            // Log successful creation
            Log::info('Aspirasi created successfully', [
                'id' => $aspirasi->id,
                'jenis' => $data['jenis_aspirasi'],
                'pengirim' => $data['nama_pengirim']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Aspirasi Anda telah berhasil dikirim. Email konfirmasi telah dikirim ke alamat email Anda.',
                'data' => [
                    'id' => $aspirasi->id,
                    'nomor_tiket' => $aspirasi->nomor_tiket,
                    'tanggal' => $aspirasi->created_at->format('d-m-Y H:i:s')
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error storing aspirasi', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi admin jika masalah berlanjut.'
            ], 500);
        }
    }

    /**
     * Handle lampiran upload
     */
    private function handleLampiranUpload($file)
    {
        try {
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Ensure directory exists
            $uploadPath = storage_path('app/public/aspirasi_lampiran');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $file->storeAs('public/aspirasi_lampiran', $fileName);
            return $fileName;
        } catch (\Exception $e) {
            Log::error('Error uploading lampiran: ' . $e->getMessage());
            return null;
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
            Log::error('Error uploading image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if project exists safely
     */
    private function checkProjectExists($projectId)
    {
        try {
            return DataSpatial::where('id', $projectId)->exists();
        } catch (\Exception $e) {
            return false;
        }
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
     * Show last lines of laravel.log with simple search and pagination
     */
    public function showLogs(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');

        // Parameters
        $query = $request->get('q', null);

        $parsedLogs = [];
        $sizeHuman = '0 B';
        $linesShown = 0;

        if (!file_exists($logPath)) {
            return view('frontend.logs.log', compact('parsedLogs', 'linesShown', 'sizeHuman'))->with('logFile', $logPath);
        }

        try {
            $size = filesize($logPath);
            $sizeHuman = $this->humanFileSize($size);

            // Read the whole file into lines (trim newlines)
            $allLines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // Filter out stack-trace lines and unwanted content
            $filtered = array_values(array_filter($allLines, function ($l) {
                $trimmed = ltrim($l);
                // Filter out lines that start with #0, #1, etc. (stack traces)
                if (strlen($trimmed) > 0 && $trimmed[0] === '#' && preg_match('/^#\d+/', $trimmed)) {
                    return false;
                }
                // Filter out [stacktrace] lines
                if (stripos($trimmed, '[stacktrace]') !== false) {
                    return false;
                }
                // Filter out lines that are just closing braces or empty
                if (trim($l) === '}' || trim($l) === '') {
                    return false;
                }
                return true;
            }));

            // Apply search if provided
            if ($query) {
                $q = trim($query);
                $filtered = array_values(array_filter($filtered, function ($l) use ($q) {
                    return stripos($l, $q) !== false;
                }));
            }

            // Parse each line into date, time, and content
            foreach ($filtered as $line) {
                $date = null;
                $time = null;
                $content = $line;

                // Example log line: [2025-09-13 14:02:54] local.INFO: Visitor tracked {..}
                if (preg_match('/^\[(?<dt>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(?<rest>.*)$/', $line, $m)) {
                    $datetime = $m['dt'];
                    $after = $m['rest'];
                    // split datetime to date/time
                    [$date, $time] = explode(' ', $datetime);
                    // strip channel and level e.g. "local.INFO: " prefix
                    if (preg_match('/^[^:]+:\s*(?<msg>.*)$/', $after, $m2)) {
                        $content = $m2['msg'];
                    } else {
                        $content = $after;
                    }
                }

                $parsedLogs[] = [
                    'date' => $date,
                    'time' => $time,
                    'content' => $content,
                ];
            }

            $linesShown = count($parsedLogs);

            return view('frontend.logs.log', compact('parsedLogs', 'linesShown', 'sizeHuman'))->with('logFile', $logPath);
        } catch (\Exception $e) {
            Log::error('Error reading log file: ' . $e->getMessage());
            return view('frontend.logs.log', compact('parsedLogs', 'linesShown', 'sizeHuman'))->with('logFile', $logPath);
        }
    }

    private function humanFileSize($bytes, $decimals = 2)
    {
        $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        if ($factor == 0) return $bytes . ' ' . $size[0];
        return sprintf("%.{$decimals}f %s", $bytes / pow(1024, $factor), $size[$factor]);
    }
}
