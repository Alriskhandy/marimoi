<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectFeedback;
use App\Models\UsulanMusrenbang;
use App\Models\ProyekStrategisNasional;
use App\Models\ProyekStrategisDaerah;
use App\Models\PokirDprd;
use App\Models\Lokasi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProjectFeedbackController extends Controller
{
    /**
     * Display a listing of feedbacks filtered by project type
     */
    public function index(Request $request)
    {
        // Determine project type from route or URL
        $projectType = $this->getProjectTypeFromRequest($request);
        $modelClass = $this->getModelClass($projectType);
        
        if (!$modelClass) {
            // If no specific project type, show all feedbacks
            return $this->showAllFeedbacks($request);
        }

        // Get query parameters for additional filtering
        $status = $request->get('status');
        $jenis = $request->get('jenis');
        $search = $request->get('search');
        $kabupaten = $request->get('kabupaten');

        // Build query filtered by project type
        $query = ProjectFeedback::with('feedbackable')
            ->where('feedbackable_type', $modelClass)
            ->orderBy('created_at', 'desc');

        // Apply additional filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($jenis) {
            $query->where('jenis_tanggapan', $jenis);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_pemberi_aspirasi', 'LIKE', "%{$search}%")
                  ->orWhere('nama_proyek', 'LIKE', "%{$search}%")
                  ->orWhere('tanggapan', 'LIKE', "%{$search}%");
            });
        }

        if ($kabupaten) {
            $query->where('kabupaten_kota', $kabupaten);
        }

        // Paginate results
        $feedbacks = $query->paginate(15);

        // Get statistics for this project type only
        $stats = [
            'pending' => ProjectFeedback::where('feedbackable_type', $modelClass)->where('status', 'pending')->count(),
            'ditinjau' => ProjectFeedback::where('feedbackable_type', $modelClass)->where('status', 'ditinjau')->count(),
            'ditindaklanjuti' => ProjectFeedback::where('feedbackable_type', $modelClass)->where('status', 'ditindaklanjuti')->count(),
            'selesai' => ProjectFeedback::where('feedbackable_type', $modelClass)->where('status', 'selesai')->count(),
        ];

        // Get available projects for this type
        $availableProjects = $this->getProjectsForType($projectType);
        $kabupaten_list = $this->getKabupatenList();
        
        // Get project type info
        $projectTypeInfo = $this->getProjectTypeInfo($projectType);

        return view('backend.pages.aspirasi.project_feedback', compact(
            'feedbacks', 
            'stats', 
            'kabupaten_list', 
            'availableProjects',
            'projectType',
            'projectTypeInfo'
        ));
    }

    /**
     * Show all feedbacks (when no specific project type)
     */
    private function showAllFeedbacks(Request $request)
    {
        $status = $request->get('status');
        $jenis = $request->get('jenis');
        $search = $request->get('search');
        $kabupaten = $request->get('kabupaten');

        $query = ProjectFeedback::with('feedbackable')
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($jenis) {
            $query->where('jenis_tanggapan', $jenis);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_pemberi_aspirasi', 'LIKE', "%{$search}%")
                  ->orWhere('nama_proyek', 'LIKE', "%{$search}%")
                  ->orWhere('tanggapan', 'LIKE', "%{$search}%");
            });
        }

        if ($kabupaten) {
            $query->where('kabupaten_kota', $kabupaten);
        }

        $feedbacks = $query->paginate(15);

        $stats = [
            'pending' => ProjectFeedback::where('status', 'pending')->count(),
            'ditinjau' => ProjectFeedback::where('status', 'ditinjau')->count(),
            'ditindaklanjuti' => ProjectFeedback::where('status', 'ditindaklanjuti')->count(),
            'selesai' => ProjectFeedback::where('status', 'selesai')->count(),
        ];

        $modelStats = [
            'usulan_musrenbang' => ProjectFeedback::forUsulanMusrenbang()->count(),
            'proyek_strategis_nasional' => ProjectFeedback::forProyekStrategisNasional()->count(),
            'proyek_strategis_daerah' => ProjectFeedback::forProyekStrategisDaerah()->count(),
            'pokir_dprd' => ProjectFeedback::forPokirDprd()->count(),
            'lokasi' => ProjectFeedback::forLokasi()->count(),
        ];

        $kabupaten_list = $this->getKabupatenList();
        $projectTypeInfo = [
            'name' => 'Semua Jenis Proyek',
            'description' => 'Feedback untuk semua jenis proyek di Maluku Utara',
            'icon' => 'mdi-comment-multiple-outline',
            'color' => 'primary'
        ];
        $projectType = 'all';
        $availableProjects = [];

        return view('backend.pages.aspirasi.project_feedback', compact(
            'feedbacks', 
            'stats', 
            'modelStats',
            'kabupaten_list',
            'projectTypeInfo',
            'projectType',
            'availableProjects'
        ));
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
     * Store general feedback (original method)
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

    /**
     * Show specific feedback
     */
    public function show(Request $request, $id)
    {
        $projectType = $this->getProjectTypeFromRequest($request);
        
        try {
            $query = ProjectFeedback::with('feedbackable');
            
            // If scoped to specific project type, filter by it
            if ($projectType && $projectType !== 'all') {
                $modelClass = $this->getModelClass($projectType);
                if ($modelClass) {
                    $query->where('feedbackable_type', $modelClass);
                }
            }
            
            $feedback = $query->findOrFail($id);

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
     * Update feedback with admin response
     */
    public function respond(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:ditinjau,ditindaklanjuti,selesai',
            'response_admin' => 'required|string'
        ], [
            'status.required' => 'Status wajib dipilih',
            'response_admin.required' => 'Response admin wajib diisi'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $projectType = $this->getProjectTypeFromRequest($request);
            $query = ProjectFeedback::query();
            
            // If scoped to specific project type, filter by it
            if ($projectType && $projectType !== 'all') {
                $modelClass = $this->getModelClass($projectType);
                if ($modelClass) {
                    $query->where('feedbackable_type', $modelClass);
                }
            }
            
            $feedback = $query->findOrFail($id);

            $feedback->update([
                'status' => $request->status,
                'response_admin' => $request->response_admin,
                'responded_at' => Carbon::now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Response berhasil dikirim',
                'data' => $feedback->load('feedbackable')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete feedback
     */
    public function destroy(Request $request, $id)
    {
        try {
            $projectType = $this->getProjectTypeFromRequest($request);
            $query = ProjectFeedback::query();
            
            // If scoped to specific project type, filter by it
            if ($projectType && $projectType !== 'all') {
                $modelClass = $this->getModelClass($projectType);
                if ($modelClass) {
                    $query->where('feedbackable_type', $modelClass);
                }
            }
            
            $feedback = $query->findOrFail($id);

            // Delete image if exists
            if ($feedback->laporan_gambar) {
                Storage::delete('public/feedback_images/' . $feedback->laporan_gambar);
            }

            $feedback->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Tanggapan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for AJAX calls
     */
    public function statistics(Request $request)
    {
        try {
            $projectType = $this->getProjectTypeFromRequest($request);
            
            if ($projectType && $projectType !== 'all') {
                $modelClass = $this->getModelClass($projectType);
                $stats = [
                    'pending' => ProjectFeedback::where('feedbackable_type', $modelClass)->where('status', 'pending')->count(),
                    'ditinjau' => ProjectFeedback::where('feedbackable_type', $modelClass)->where('status', 'ditinjau')->count(),
                    'ditindaklanjuti' => ProjectFeedback::where('feedbackable_type', $modelClass)->where('status', 'ditindaklanjuti')->count(),
                    'selesai' => ProjectFeedback::where('feedbackable_type', $modelClass)->where('status', 'selesai')->count(),
                ];
            } else {
                $stats = [
                    'pending' => ProjectFeedback::where('status', 'pending')->count(),
                    'ditinjau' => ProjectFeedback::where('status', 'ditinjau')->count(),
                    'ditindaklanjuti' => ProjectFeedback::where('status', 'ditindaklanjuti')->count(),
                    'selesai' => ProjectFeedback::where('status', 'selesai')->count(),
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil statistik'
            ], 500);
        }
    }

    /**
     * Helper Methods
     */
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

    private function getProjectsForType($type)
    {
        $modelClass = $this->getModelClass($type);
        if (!$modelClass) {
            return collect();
        }

        try {
            return $modelClass::select('id', 'deskripsi')
                ->latest()
                ->limit(50)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    private function getProjectTypeInfo($type)
    {
        $typeInfo = [
            'usulan_musrenbang' => [
                'name' => 'Usulan Musrenbang',
                'description' => 'Feedback untuk usulan hasil musyawarah perencanaan pembangunan',
                'icon' => 'mdi-account-group',
                'color' => 'success'
            ],
            'proyek_strategis_nasional' => [
                'name' => 'Proyek Strategis Nasional',
                'description' => 'Feedback untuk proyek strategis tingkat nasional',
                'icon' => 'mdi-flag',
                'color' => 'primary'
            ],
            'proyek_strategis_daerah' => [
                'name' => 'Proyek Strategis Daerah',
                'description' => 'Feedback untuk proyek strategis tingkat daerah',
                'icon' => 'mdi-map-marker',
                'color' => 'info'
            ],
            'pokir_dprd' => [
                'name' => 'Pokir DPRD',
                'description' => 'Feedback untuk pokok-pokok pikiran DPRD',
                'icon' => 'mdi-gavel',
                'color' => 'warning'
            ],
            'lokasi' => [
                'name' => 'Lokasi',
                'description' => 'Feedback untuk lokasi-lokasi tertentu',
                'icon' => 'mdi-map',
                'color' => 'danger'
            ],
        ];

        return $typeInfo[$type] ?? [
            'name' => 'Unknown Project Type',
            'description' => 'Unknown project type',
            'icon' => 'mdi-help-circle',
            'color' => 'secondary'
        ];
    }

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
            'Pulau Taliabu',
            'Ternate',
            'Tidore Kepulauan'
        ];
    }
}
