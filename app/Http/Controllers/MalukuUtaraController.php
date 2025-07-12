<?php

namespace App\Http\Controllers;

use App\Models\ProjectFeedback;
use Illuminate\Http\JsonResponse;

class MalukuUtaraController extends Controller
{
    /**
     * Get semua data referensi untuk Maluku Utara
     */
    public function getReferenceData(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'provinsi' => ProjectFeedback::PROVINSI,
                'kabupaten_kota' => ProjectFeedback::KABUPATEN_KOTA,
                'kecamatan_by_kabupaten' => ProjectFeedback::KECAMATAN_BY_KABUPATEN,
                'jenis_tanggapan' => [
                    'keluhan' => 'Keluhan',
                    'saran' => 'Saran',
                    'apresiasi' => 'Apresiasi',
                    'pertanyaan' => 'Pertanyaan'
                ],
                'status_options' => [
                    'pending' => 'Menunggu',
                    'ditinjau' => 'Sedang Ditinjau',
                    'ditindaklanjuti' => 'Ditindaklanjuti',
                    'selesai' => 'Selesai'
                ]
            ]
        ]);
    }

    /**
     * Get kecamatan berdasarkan kabupaten
     */
    public function getKecamatan(string $kabupaten): JsonResponse
    {
        if (!in_array($kabupaten, ProjectFeedback::KABUPATEN_KOTA)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kabupaten tidak valid',
                'valid_kabupaten' => ProjectFeedback::KABUPATEN_KOTA
            ], 404);
        }

        $kecamatan = ProjectFeedback::getKecamatanByKabupaten($kabupaten);

        return response()->json([
            'status' => 'success',
            'data' => [
                'kabupaten' => $kabupaten,
                'kecamatan' => $kecamatan,
                'total_kecamatan' => count($kecamatan)
            ]
        ]);
    }

    /**
     * Get statistik lengkap Maluku Utara
     */
    public function getDetailedStatistics(): JsonResponse
    {
        $stats = [];

        // Statistik per kabupaten
        foreach (ProjectFeedback::KABUPATEN_KOTA as $kabupaten) {
            $total = ProjectFeedback::where('kabupaten_kota', $kabupaten)->count();
            $byStatus = ProjectFeedback::where('kabupaten_kota', $kabupaten)
                                     ->selectRaw('status, COUNT(*) as count')
                                     ->groupBy('status')
                                     ->pluck('count', 'status');
            
            $byJenis = ProjectFeedback::where('kabupaten_kota', $kabupaten)
                                    ->selectRaw('jenis_tanggapan, COUNT(*) as count')
                                    ->groupBy('jenis_tanggapan')
                                    ->pluck('count', 'jenis_tanggapan');

            $stats['per_kabupaten'][$kabupaten] = [
                'total' => $total,
                'by_status' => $byStatus,
                'by_jenis' => $byJenis,
                'response_rate' => $total > 0 ? 
                    round((($byStatus['selesai'] ?? 0) / $total) * 100, 2) : 0
            ];
        }

        // Trend bulanan
        $monthlyTrend = ProjectFeedback::selectRaw('
                kabupaten_kota,
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                COUNT(*) as count
            ')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('kabupaten_kota', 'year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy('kabupaten_kota');

        // Top 5 proyek dengan feedback terbanyak
        $topProjects = ProjectFeedback::selectRaw('nama_proyek, COUNT(*) as total_feedback')
                                    ->groupBy('nama_proyek')
                                    ->orderBy('total_feedback', 'desc')
                                    ->limit(5)
                                    ->get();

        // Rata-rata response time (hari)
        $avgResponseTime = ProjectFeedback::whereNotNull('responded_at')
                                        ->selectRaw('AVG(DATEDIFF(responded_at, created_at)) as avg_days')
                                        ->value('avg_days');

        return response()->json([
            'status' => 'success',
            'data' => [
                'overview' => [
                    'total_feedback' => ProjectFeedback::count(),
                    'total_kabupaten' => count(ProjectFeedback::KABUPATEN_KOTA),
                    'avg_response_time_days' => round($avgResponseTime ?? 0, 1),
                    'completion_rate' => ProjectFeedback::count() > 0 ? 
                        round((ProjectFeedback::where('status', 'selesai')->count() / ProjectFeedback::count()) * 100, 2) : 0
                ],
                'per_kabupaten' => $stats['per_kabupaten'],
                'monthly_trend' => $monthlyTrend,
                'top_projects' => $topProjects,
                'summary' => [
                    'most_active_kabupaten' => ProjectFeedback::selectRaw('kabupaten_kota, COUNT(*) as total')
                                                            ->groupBy('kabupaten_kota')
                                                            ->orderBy('total', 'desc')
                                                            ->first(),
                    'most_common_feedback_type' => ProjectFeedback::selectRaw('jenis_tanggapan, COUNT(*) as total')
                                                                 ->groupBy('jenis_tanggapan')
                                                                 ->orderBy('total', 'desc')
                                                                 ->first(),
                    'pending_needs_attention' => ProjectFeedback::where('status', 'pending')
                                                               ->where('created_at', '<', now()->subDays(7))
                                                               ->count()
                ]
            ]
        ]);
    }

    /**
     * Get koordinat center untuk maps Maluku Utara
     */
    public function getMapsCenter(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'center' => [
                    'latitude' => 0.7881,  // Ternate sebagai center
                    'longitude' => 127.3781
                ],
                'zoom_level' => 8,
                'bounds' => [
                    'north' => 2.5,
                    'south' => -2.5,
                    'east' => 129.0,
                    'west' => 124.0
                ],
                'kabupaten_coordinates' => [
                    'Ternate' => ['lat' => 0.7881, 'lng' => 127.3781],
                    'Tidore Kepulauan' => ['lat' => 0.6781, 'lng' => 127.4020],
                    'Halmahera Barat' => ['lat' => 1.0147, 'lng' => 127.7334],
                    'Halmahera Timur' => ['lat' => 1.4853, 'lng' => 127.8492],
                    'Halmahera Utara' => ['lat' => 1.7281, 'lng' => 128.0139],
                    'Halmahera Selatan' => ['lat' => -0.9500, 'lng' => 127.4833],
                    'Kepulauan Sula' => ['lat' => -1.9833, 'lng' => 125.9667],
                    'Halmahera Tengah' => ['lat' => -0.2167, 'lng' => 127.8833],
                    'Pulau Morotai' => ['lat' => 2.3167, 'lng' => 128.4167],
                    'Pulau Taliabu' => ['lat' => -1.8333, 'lng' => 124.7833]
                ]
            ]
        ]);
    }

    /**
     * Validate input data untuk Maluku Utara
     */
    public function validateInput(string $kabupaten, string $kecamatan = null): JsonResponse
    {
        $errors = [];

        // Validasi kabupaten
        if (!in_array($kabupaten, ProjectFeedback::KABUPATEN_KOTA)) {
            $errors['kabupaten'] = 'Kabupaten tidak valid untuk Maluku Utara';
        }

        // Validasi kecamatan jika ada
        if ($kecamatan && in_array($kabupaten, ProjectFeedback::KABUPATEN_KOTA)) {
            $validKecamatan = ProjectFeedback::getKecamatanByKabupaten($kabupaten);
            if (!in_array($kecamatan, $validKecamatan)) {
                $errors['kecamatan'] = "Kecamatan '$kecamatan' tidak valid untuk $kabupaten";
            }
        }

        return response()->json([
            'status' => empty($errors) ? 'success' : 'error',
            'valid' => empty($errors),
            'errors' => $errors,
            'suggestions' => [
                'valid_kabupaten' => ProjectFeedback::KABUPATEN_KOTA,
                'valid_kecamatan_for_kabupaten' => $kecamatan && isset(ProjectFeedback::KECAMATAN_BY_KABUPATEN[$kabupaten]) 
                    ? ProjectFeedback::KECAMATAN_BY_KABUPATEN[$kabupaten] 
                    : []
            ]
        ]);
    }
}