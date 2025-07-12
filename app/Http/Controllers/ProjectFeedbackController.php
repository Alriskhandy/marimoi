<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectFeedback;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProjectFeedbackController extends Controller
{
    /**
     * Display a listing of the feedbacks
     */
    public function index(Request $request)
    {
        // Get query parameters for filtering (optional, for URL-based filtering)
        $status = $request->get('status');
        $jenis = $request->get('jenis');
        $search = $request->get('search');
        $kabupaten = $request->get('kabupaten');

        // Build query
        $query = ProjectFeedback::query()
            ->orderBy('created_at', 'desc');

        // Apply filters if needed for URL-based filtering
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

        // Get statistics
        $stats = [
            'pending' => ProjectFeedback::where('status', 'pending')->count(),
            'ditinjau' => ProjectFeedback::where('status', 'ditinjau')->count(),
            'ditindaklanjuti' => ProjectFeedback::where('status', 'ditindaklanjuti')->count(),
            'selesai' => ProjectFeedback::where('status', 'selesai')->count(),
        ];

        // Get kabupaten list for filter dropdown
        $kabupaten_list = $this->getKabupatenList();

        return view('backend.pages.aspirasi.project_feedback', compact('feedbacks', 'stats', 'kabupaten_list'));
    }

    /**
     * Store a newly created feedback
     */
    public function store(Request $request)
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
            'laporan_gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'nama_pemberi_aspirasi.required' => 'Nama pemberi aspirasi wajib diisi',
            'nama_proyek.required' => 'Nama proyek wajib diisi',
            'kabupaten_kota.required' => 'Kabupaten/Kota wajib dipilih',
            'jenis_tanggapan.required' => 'Jenis tanggapan wajib dipilih',
            'jenis_tanggapan.in' => 'Jenis tanggapan tidak valid',
            'tanggapan.required' => 'Tanggapan wajib diisi',
            'email.email' => 'Format email tidak valid',
            'laporan_gambar.image' => 'File harus berupa gambar',
            'laporan_gambar.max' => 'Ukuran gambar maksimal 2MB'
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
            $data['status'] = 'pending'; // Default status

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
     * Show specific feedback for modal detail
     */
    public function show($id)
    {
        try {
            $feedback = ProjectFeedback::findOrFail($id);

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
            'status.in' => 'Status tidak valid',
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
            $feedback = ProjectFeedback::findOrFail($id);

            $feedback->update([
                'status' => $request->status,
                'response_admin' => $request->response_admin,
                'responded_at' => Carbon::now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Response berhasil dikirim',
                'data' => $feedback
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
    public function destroy($id)
    {
        try {
            $feedback = ProjectFeedback::findOrFail($id);

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
    public function statistics()
    {
        try {
            $stats = [
                'pending' => ProjectFeedback::where('status', 'pending')->count(),
                'ditinjau' => ProjectFeedback::where('status', 'ditinjau')->count(),
                'ditindaklanjuti' => ProjectFeedback::where('status', 'ditindaklanjuti')->count(),
                'selesai' => ProjectFeedback::where('status', 'selesai')->count(),
            ];

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
     * Get kabupaten list for Maluku Utara
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