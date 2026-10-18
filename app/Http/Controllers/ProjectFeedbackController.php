<?php

// app/Http/Controllers/ProjectFeedbackController.php

namespace App\Http\Controllers;

use App\Mail\TanggapanMail;
use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\ProjectFeedback;
use App\Models\User;
use App\Support\AspirasiMailer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProjectFeedbackController extends Controller
{
    /**
     * Folder lampiran di disk public — sama dengan form publik (FrontendController::store).
     */
    private const IMAGE_DIRECTORY = 'aspirasi_lampiran';

    public function index(Request $request)
    {

        // Get user dan role
        $user = Auth::user();
        $userRole = $user->role->slug ?? null;
        // Get all OPD for dropdown - filter berdasarkan role
        if (in_array($userRole, ['super-admin', 'admin-bappeda'])) {
            // Super Admin dan Admin Bappeda bisa lihat semua OPD
            $opd = Opd::select('id', 'name', 'singkatan', 'logo')
                ->orderBy('name', 'asc')
                ->get();
        } else {
            // Role lain hanya bisa lihat OPD mereka sendiri
            $opd = Opd::select('id', 'name', 'singkatan', 'logo')
                ->where('id', $user->opd_id)
                ->orderBy('name', 'asc')
                ->get();
        }

        $query = $this->accessibleFeedbacks()->with(['dataSpatial', 'opd:id,name,singkatan,logo']);

        // Filter tambahan berdasarkan request parameters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis_tanggapan', $request->jenis);
        }

        if ($request->filled('opd_id') && in_array($userRole, ['super-admin', 'admin-bappeda'])) {
            // Filter OPD hanya untuk Super Admin dan Admin Bappeda
            $query->where('opd_id', $request->opd_id);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nama_pemberi_aspirasi', 'ilike', "%{$searchTerm}%")
                    ->orWhere('email', 'ilike', "%{$searchTerm}%")
                    ->orWhere('nama_proyek', 'ilike', "%{$searchTerm}%")
                    ->orWhere('tanggapan', 'ilike', "%{$searchTerm}%");
            });
        }

        if ($request->filled('kabupaten_kota')) {
            $query->where('kabupaten_kota', $request->kabupaten_kota);
        }

        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50, 100]) ? (int) $request->get('per_page') : 10;
        $feedbacks = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $stats = $this->getFilteredStatistics();
        $kabupaten_list = $this->getKabupatenList();

        return view('backend.pages.feedback.project_feedback', compact(
            'feedbacks',
            'stats',
            'kabupaten_list',
            'opd',
            'userRole' // Kirim userRole ke view untuk conditional rendering
        ));
    }

    /**
     * Update OPD terkait untuk feedback tertentu
     */
    public function updateOpd(Request $request, ProjectFeedback $feedback)
    {
        if (Auth::user()->role->slug !== 'super-admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }
        try {
            // Validasi input
            $validated = $request->validate([
                'opd_id' => 'required|exists:opd,id',
            ]);

            // Update feedback dengan OPD baru
            $feedback->update([
                'opd_id' => $validated['opd_id'],
                'updated_at' => now(),
            ]);

            // Load relasi OPD untuk response
            $feedback->load('opd:id,name,singkatan,logo');

            return response()->json([
                'success' => true,
                'message' => 'OPD berhasil diperbarui',
                'data' => [
                    'feedback_id' => $feedback->id,
                    'opd' => [
                        'id' => $feedback->opd->id,
                        'name' => $feedback->opd->name,
                        'singkatan' => $feedback->opd->singkatan,
                        'logo' => $feedback->opd->logo,
                    ],
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating OPD for feedback: '.$e->getMessage(), [
                'feedback_id' => $feedback->id,
                'opd_id' => $request->opd_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui OPD. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Query feedback yang boleh diakses user login.
     *
     * Super Admin & Admin Bappeda melihat semua feedback. Role lain hanya feedback yang
     * ditujukan ke OPD-nya, atau feedback pada data spasial miliknya sendiri.
     */
    private function accessibleFeedbacks(): Builder
    {
        $user = Auth::user();
        $query = ProjectFeedback::query();

        if ($this->canSeeAllFeedback()) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($user) {
            $scoped->whereHas('dataSpatial', fn (Builder $dataSpatial) => $dataSpatial->where('user_id', $user->id));

            if ($user->opd_id) {
                $scoped->orWhere('opd_id', $user->opd_id);
            }
        });
    }

    private function canSeeAllFeedback(): bool
    {
        return in_array(Auth::user()->role->slug ?? null, ['super-admin', 'admin-bappeda']);
    }

    /**
     * Statistik feedback, mengikuti cakupan akses user yang sama dengan daftar.
     *
     * @return array<string, int>
     */
    private function getFilteredStatistics(): array
    {
        $query = $this->accessibleFeedbacks();

        return [
            'total' => (clone $query)->count(),
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
     * Pencarian data spasial untuk dropdown Select2 di form tambah feedback.
     * Semua jenis data spasial bisa menerima feedback; role non-admin hanya datanya sendiri.
     */
    public function searchDataSpatial(Request $request): JsonResponse
    {
        $perPage = 20;
        $search = trim((string) $request->get('search'));

        $query = DataSpatial::select('id', 'uuid', 'deskripsi')
            ->whereNotNull('deskripsi')
            ->where('deskripsi', '!=', '');

        if ($search !== '') {
            $query->where(fn (Builder $matches) => $matches->where('deskripsi', 'ilike', "%{$search}%")
                ->orWhere('uuid', 'ilike', "%{$search}%"));
        }

        if (! $this->canSeeAllFeedback()) {
            $query->where('user_id', Auth::id());
        }

        $page = $query->orderBy('deskripsi')->simplePaginate($perPage);

        return response()->json([
            'results' => $page->getCollection()->map(fn (DataSpatial $dataSpatial) => [
                'id' => $dataSpatial->id,
                'text' => "{$dataSpatial->deskripsi} ({$dataSpatial->uuid})",
                'nama_proyek' => $dataSpatial->deskripsi,
            ])->values(),
            'pagination' => ['more' => $page->hasMorePages()],
        ]);
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
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Handle file upload
            if ($request->hasFile('laporan_gambar')) {
                $file = $request->file('laporan_gambar');
                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->storeAs(self::IMAGE_DIRECTORY, $filename, 'public');
                $data['laporan_gambar'] = $filename;
            }

            // OPD tujuan mengikuti pemilik data spasial, sama seperti form publik.
            if (! empty($data['data_spatial_id'])) {
                $ownerId = DataSpatial::whereKey($data['data_spatial_id'])->value('user_id');
                $data['opd_id'] = User::whereKey($ownerId)->value('opd_id');
            }

            $feedback = ProjectFeedback::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback berhasil ditambahkan',
                'data' => $feedback,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $feedback = $this->accessibleFeedbacks()->with(['dataSpatial', 'opd'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $feedback,
                'kode' => $feedback->ticketCode(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
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
                'errors' => $validator->errors(),
            ], 422);
        }

        $feedback = $this->accessibleFeedbacks()->findOrFail($id);

        try {
            $data = $validator->validated();

            // Handle file upload
            if ($request->hasFile('laporan_gambar')) {
                // Delete old image
                if ($feedback->laporan_gambar) {
                    Storage::disk('public')->delete(self::IMAGE_DIRECTORY.'/'.$feedback->laporan_gambar);
                }

                $file = $request->file('laporan_gambar');
                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->storeAs(self::IMAGE_DIRECTORY, $filename, 'public');
                $data['laporan_gambar'] = $filename;
            }

            $feedback->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback berhasil diupdate',
                'data' => $feedback,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $feedback = $this->accessibleFeedbacks()->findOrFail($id);

        try {
            // Delete image file if exists
            if ($feedback->laporan_gambar) {
                Storage::disk('public')->delete(self::IMAGE_DIRECTORY.'/'.$feedback->laporan_gambar);
            }

            $feedback->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
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
            'response_admin' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feedback = $this->accessibleFeedbacks()->findOrFail($id);

        try {
            $feedback->update([
                'status' => $request->status,
                'response_admin' => $request->response_admin,
                'responded_at' => now(),
            ]);

            // Email ke warga hanya bila status/tanggapan benar-benar berubah (bukan simpan ulang)
            $type = match ($feedback->status) {
                'ditindaklanjuti' => 'diproses',
                'selesai' => 'selesai',
                default => null,
            };

            if ($type && $feedback->wasChanged(['status', 'response_admin'])) {
                AspirasiMailer::queue($feedback->email, new TanggapanMail([
                    'nama' => $feedback->nama_pemberi_aspirasi,
                    'email' => $feedback->email,
                    'kode' => $feedback->ticketCode(),
                    'tanggapan' => $feedback->tanggapan,
                    'tanggal' => now()->format('d-m-Y H:i'),
                    'response_admin' => $feedback->response_admin,
                ], $type));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Response berhasil dikirim',
                'data' => $feedback,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }

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
            'Tidore Kepulauan',
        ];
    }
}
