<?php

// ========================================
// SCOPED PROJECT FEEDBACK CONTROLLER
// ========================================


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

class ScopedProjectFeedbackController extends Controller
{
    /**
     * Display a listing of feedbacks filtered by project type
     */
    public function index(Request $request)
    {
        $projectType = $request->route()->getDefaults()['project_type'] ?? $request->get('project_type');
        $modelClass = $this->getModelClass($projectType);
        
        if (!$modelClass) {
            abort(404, 'Project type not found');
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
     * Store feedback for specific project type
     */
    public function store(Request $request)
    {
        $projectType = $request->route()->getDefaults()['project_type'] ?? $request->get('project_type');
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
     * Show specific feedback (validate it belongs to project type)
     */
    public function show(Request $request, $id)
    {
        $projectType = $request->route()->getDefaults()['project_type'] ?? $request->get('project_type');
        $modelClass = $this->getModelClass($projectType);
        
        try {
            $feedback = ProjectFeedback::with('feedbackable')
                ->where('feedbackable_type', $modelClass)
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan atau tidak sesuai dengan jenis proyek'
            ], 404);
        }
    }

    /**
     * Update feedback response (validate project type)
     */
    public function respond(Request $request, $id)
    {
        $projectType = $request->route()->getDefaults()['project_type'] ?? $request->get('project_type');
        $modelClass = $this->getModelClass($projectType);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:ditinjau,ditindaklanjuti,selesai',
            'response_admin' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $feedback = ProjectFeedback::where('feedbackable_type', $modelClass)
                ->findOrFail($id);

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
     * Delete feedback (validate project type)
     */
    public function destroy(Request $request, $id)
    {
        $projectType = $request->route()->getDefaults()['project_type'] ?? $request->get('project_type');
        $modelClass = $this->getModelClass($projectType);

        try {
            $feedback = ProjectFeedback::where('feedbackable_type', $modelClass)
                ->findOrFail($id);

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
     * Helper Methods
     */
    private function getModelClass($type)
    {
        $modelMap = [
            'usulan_musrenbang' => UsulanMusrenbang::class,
            'proyek_strategis_nasional' => ProyekStrategisNasional::class,
            'proyek_strategis_daerah' => ProyekStrategisDaerah::class,
            'pokir_dprd' => PokirDprd::class,
            'lokasi' => Lokasi::class,
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