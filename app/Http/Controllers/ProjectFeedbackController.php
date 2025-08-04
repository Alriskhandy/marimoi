<?php

// app/Http/Controllers/ProjectFeedbackController.php

namespace App\Http\Controllers;

use App\Models\ProjectFeedback;
use App\Models\DataSpatial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProjectFeedbackController extends Controller
{
    public function index(Request $request)
{
    // dd($request->all());
    $type = $request->get('type');
    $subType = $request->get('sub_type');

    // Validasi type yang diizinkan
    $allowedTypes = [ 'pokir_dprd', 'usulan_musrenbang', 'proyek_strategis'];
    
    // Jika type tidak ada atau tidak valid, redirect back dengan error
    if (!$type || !in_array($type, $allowedTypes)) {
        return redirect()->back()->with('error', 'Halaman tidak ditemukan. Tipe proyek tidak valid.');
    }

    // Validasi sub_type untuk proyek_strategis
    if ($type === 'proyek_strategis') {
        $allowedSubTypes = ['psn', 'psd'];
        if ($subType && !in_array($subType, $allowedSubTypes)) {
            return redirect()->back()->with('error', 'Sub-tipe proyek strategis tidak valid.');
        }
    }

    // Build query based on type and sub_type
    $query = ProjectFeedback::with('dataSpatial');

    // Filter berdasarkan type yang sudah divalidasi
    $query->whereHas('dataSpatial', function ($q) use ($type, $subType) {
        if ($type === 'proyek_strategis') {
            // Untuk proyek strategis, filter berdasarkan data_type dan sub_type
            $q->where('data_type', 'proyek_strategis');
            if ($subType === 'psn') {
                $q->where('sub_type', 'psn');
            } elseif ($subType === 'psd') {
                $q->where('sub_type', 'psd');
            }
            // Jika tidak ada sub_type, tampilkan semua proyek strategis
        } else {
            // Untuk type lainnya, gunakan data_type
            $q->where('data_type', $type);
        }
    });

    $feedbacks = $query->orderBy('created_at', 'desc')->paginate(10);

    // Get statistics untuk type yang dipilih
    $stats = $this->getFilteredStatistics($type, $subType);
    $kabupaten_list = $this->getKabupatenList();

    // Get project type info
    $projectTypeInfo = $this->getProjectTypeInfo($type, $subType);

    // Get available projects untuk dropdown di form
    $availableProjects = $this->getAvailableProjects($type, $subType);

    return view('backend.pages.feedback.project_feedback', compact(
        'feedbacks',
        'stats',
        'kabupaten_list',
        'projectTypeInfo',
        'type',
        'subType',
        'availableProjects'
    ));
}

/**
 * Get project type information based on type and sub_type
 */
private function getProjectTypeInfo($type, $subType = null)
{
    $typeInfo = [
        // 'lokasi' => [
        //     'name' => 'RPJMD',
        //     'color' => 'danger',
        //     'icon' => 'mdi-map',
        //     'description' => 'Feedback untuk Rencana Pembangunan Jangka Menengah Daerah'
        // ],
        'pokir_dprd' => [
            'name' => 'Pokir DPRD',
            'color' => 'warning',
            'icon' => 'mdi-gavel',
            'description' => 'Feedback untuk Pokok Pikiran DPRD'
        ],
        'usulan_musrenbang' => [
            'name' => 'Usulan Musrenbang',
            'color' => 'success',
            'icon' => 'mdi-account-group',
            'description' => 'Feedback untuk Usulan Musyawarah Perencanaan Pembangunan'
        ],
        'proyek_strategis' => [
            'name' => 'Proyek Strategis',
            'color' => 'info',
            'icon' => 'mdi-flag',
            'description' => 'Feedback untuk Proyek Strategis'
        ]
    ];

    // Handle proyek strategis sub-types
    if ($type === 'proyek_strategis' && $subType) {
        if ($subType === 'psn') {
            return [
                'name' => 'Proyek Strategis Nasional',
                'color' => 'primary',
                'icon' => 'mdi-flag',
                'description' => 'Feedback untuk Proyek Strategis Nasional'
            ];
        } elseif ($subType === 'psd') {
            return [
                'name' => 'Proyek Strategis Daerah',
                'color' => 'info',
                'icon' => 'mdi-map-marker',
                'description' => 'Feedback untuk Proyek Strategis Daerah'
            ];
        }
    }

    return $typeInfo[$type] ?? [
        'name' => 'Unknown Type',
        'color' => 'secondary',
        'icon' => 'mdi-help-circle',
        'description' => 'Tipe proyek tidak dikenal'
    ];
}

/**
 * Get filtered statistics based on type and sub_type
 */
private function getFilteredStatistics($type, $subType = null)
{
    $query = ProjectFeedback::whereHas('dataSpatial', function ($q) use ($type, $subType) {
        if ($type === 'proyek_strategis') {
            $q->where('data_type', 'proyek_strategis');
            if ($subType === 'psn') {
                $q->where('sub_type', 'psn');
            } elseif ($subType === 'psd') {
                $q->where('sub_type', 'psd');
            }
        } else {
            $q->where('data_type', $type);
        }
    });

    return [
        'total' => $query->count(),
        'pending' => (clone $query)->where('status', 'pending')->count(),
        'ditinjau' => (clone $query)->where('status', 'ditinjau')->count(),
        'ditindaklanjuti' => (clone $query)->where('status', 'ditindaklanjuti')->count(),
        'selesai' => (clone $query)->where('status', 'selesai')->count(),
        'keluhan' => (clone $query)->where('jenis_tanggapan', 'keluhan')->count(),
        'saran' => (clone $query)->where('jenis_tanggapan', 'saran')->count(),
        'apresiasi' => (clone $query)->where('jenis_tanggapan', 'apresiasi')->count(),
        'pertanyaan' => (clone $query)->where('jenis_tanggapan', 'pertanyaan')->count(),
    ];
}

/**
 * Get available projects based on type and sub_type
 */
private function getAvailableProjects($type, $subType = null)
{
    $query = DataSpatial::whereNotNull('deskripsi')
        ->where('deskripsi', '!=', '');

    if ($type === 'proyek_strategis') {
        $query->where('data_type', 'proyek_strategis');
        if ($subType === 'psn') {
            $query->where('sub_type', 'psn');
        } elseif ($subType === 'psd') {
            $query->where('sub_type', 'psd');
        }
    } else {
        $query->where('data_type', $type);
    }

    return $query->orderBy('deskripsi')->get();
}
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_spatial_id' => 'nullable|exists:data_spatial,id',
            'nama_pemberi_aspirasi' => 'required|string|max:255',
            'nama_proyek' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'tanggapan' => 'required|string',
            'jenis_tanggapan' => 'required|in:keluhan,saran,apresiasi,pertanyaan',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'laporan_gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Handle file upload
            if ($request->hasFile('laporan_gambar')) {
                $file = $request->file('laporan_gambar');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/feedback_images', $filename);
                $data['laporan_gambar'] = $filename;
            }

            $feedback = ProjectFeedback::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback berhasil ditambahkan',
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $feedback = ProjectFeedback::with('dataSpatial')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'data_spatial_id' => 'nullable|exists:data_spatial,id',
            'nama_pemberi_aspirasi' => 'required|string|max:255',
            'nama_proyek' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'tanggapan' => 'required|string',
            'jenis_tanggapan' => 'required|in:keluhan,saran,apresiasi,pertanyaan',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'laporan_gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $feedback = ProjectFeedback::findOrFail($id);
            $data = $validator->validated();

            // Handle file upload
            if ($request->hasFile('laporan_gambar')) {
                // Delete old image
                if ($feedback->laporan_gambar) {
                    Storage::delete('public/feedback_images/' . $feedback->laporan_gambar);
                }

                $file = $request->file('laporan_gambar');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/feedback_images', $filename);
                $data['laporan_gambar'] = $filename;
            }

            $feedback->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback berhasil diupdate',
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $feedback = ProjectFeedback::findOrFail($id);

            // Delete image file if exists
            if ($feedback->laporan_gambar) {
                Storage::delete('public/feedback_images/' . $feedback->laporan_gambar);
            }

            $feedback->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update feedback status and add admin response
     */
    public function respond(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:ditinjau,ditindaklanjuti,selesai',
            'response_admin' => 'required|string|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $feedback = ProjectFeedback::findOrFail($id);

            $feedback->update([
                'status' => $request->status,
                'response_admin' => $request->response_admin,
                'responded_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Response berhasil dikirim',
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // /**
    //  * Get project type information based on type and sub_type
    //  */
    // private function getProjectTypeInfo($type, $subType = null)
    // {
    //     $typeInfo = [
    //         'all' => [
    //             'name' => 'Semua Tanggapan Masyarakat',
    //             'color' => 'primary',
    //             'icon' => 'mdi-comment-multiple-outline',
    //             'description' => 'Menampilkan semua feedback dari berbagai jenis proyek'
    //         ],
    //         'lokasi' => [
    //             'name' => 'RPJMD',
    //             'color' => 'danger',
    //             'icon' => 'mdi-map',
    //             'description' => 'Feedback untuk Rencana Pembangunan Jangka Menengah Daerah'
    //         ],
    //         'pokir_dprd' => [
    //             'name' => 'Pokir DPRD',
    //             'color' => 'warning',
    //             'icon' => 'mdi-gavel',
    //             'description' => 'Feedback untuk Pokok Pikiran DPRD'
    //         ],
    //         'usulan_musrenbang' => [
    //             'name' => 'Usulan Musrenbang',
    //             'color' => 'success',
    //             'icon' => 'mdi-account-group',
    //             'description' => 'Feedback untuk Usulan Musyawarah Perencanaan Pembangunan'
    //         ],
    //         'proyek_strategis' => [
    //             'name' => 'Proyek Strategis',
    //             'color' => 'info',
    //             'icon' => 'mdi-flag',
    //             'description' => 'Feedback untuk Proyek Strategis'
    //         ]
    //     ];

    //     // Handle proyek strategis sub-types
    //     if ($type === 'proyek_strategis' && $subType) {
    //         if ($subType === 'psn') {
    //             return [
    //                 'name' => 'Proyek Strategis Nasional',
    //                 'color' => 'primary',
    //                 'icon' => 'mdi-flag',
    //                 'description' => 'Feedback untuk Proyek Strategis Nasional'
    //             ];
    //         } elseif ($subType === 'psd') {
    //             return [
    //                 'name' => 'Proyek Strategis Daerah',
    //                 'color' => 'info',
    //                 'icon' => 'mdi-map-marker',
    //                 'description' => 'Feedback untuk Proyek Strategis Daerah'
    //             ];
    //         }
    //     }

    //     return $typeInfo[$type] ?? $typeInfo['all'];
    // }


    /**
     * Get list of kabupaten for dropdown
     */
    private function getKabupatenList()
    {
        return [
            'Halmahera Barat',
            'Halmahera Tengah',
            'Halmahera Timur',
            'Halmahera Selatan',
            'Halmahera Utara',
            'Kepulauan Sula',
            'Pulau Morotai',
            'Ternate',
            'Tidore Kepulauan'
        ];
    }
}