<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProjectFeedback;

class FeedbackController extends Controller
{
    /**
     * Store feedback - Method sederhana yang fokus pada penyimpanan data
     */
    public function store(Request $request)
    {
        // dd($request->all());
        // Tentukan tipe project berdasarkan URL atau parameter
        $projectType = $this->getProjectTypeFromRequest($request);
        $modelClass = $this->getModelClass($projectType);
        
        // Validation rules - disesuaikan dengan struktur database
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

        // Image validation - required untuk keluhan
        if ($request->jenis_tanggapan === 'keluhan') {
            $rules['laporan_gambar'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
        } else {
            $rules['laporan_gambar'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $messages = [
            'feedbackable_id.required' => 'Proyek wajib dipilih',
            'nama_pemberi_aspirasi.required' => 'Nama pemberi aspirasi wajib diisi',
            'jenis_tanggapan.required' => 'Jenis tanggapan wajib dipilih',
            'tanggapan.required' => 'Tanggapan wajib diisi',
            'laporan_gambar.required' => 'Lampiran gambar wajib untuk pengaduan',
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
            // Siapkan data untuk disimpan
            $data = [
                'feedbackable_id' => $request->feedbackable_id,
                'feedbackable_type' => $modelClass,
                'nama_pemberi_aspirasi' => $request->nama_pemberi_aspirasi,
                'nama_proyek' => $request->nama_proyek,
                'kabupaten_kota' => $request->kabupaten_kota,
                'kecamatan' => $request->kecamatan,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'tanggapan' => $request->tanggapan,
                'jenis_tanggapan' => $request->jenis_tanggapan,
                'status' => 'pending',
                'email' => $request->email,
                'phone' => $request->phone,
            ];

            // Handle upload gambar
            if ($request->hasFile('laporan_gambar')) {
                $image = $request->file('laporan_gambar');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // Pastikan folder ada
                $uploadPath = storage_path('app/public/feedback_images');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Simpan gambar
                $image->storeAs('public/feedback_images', $imageName);
                $data['laporan_gambar'] = $imageName;
            }

            // Simpan ke database
            $feedback = ProjectFeedback::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Tanggapan berhasil dikirim. Terima kasih atas partisipasi Anda!',
                'data' => [
                    'id' => $feedback->id,
                    'status' => $feedback->status,
                    'created_at' => $feedback->created_at->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            // \Log::error('Error storing feedback: ' . $e->getMessage(), [
            //     'request_data' => $request->except(['laporan_gambar', '_token'])
            // ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Get model class berdasarkan project type
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

        return $modelMap[$type] ?? null;
    }

    /**
     * Tentukan project type dari request
     */
    private function getProjectTypeFromRequest(Request $request)
    {
        // Cek parameter project_type
        if ($request->has('project_type')) {
            return $request->get('project_type');
        }
        
        // Tentukan dari URL path
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
        
        return 'pokir_dprd'; // Default berdasarkan contoh data Anda
    }
}