<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\ProjectProgressReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PembangunanDashboardController extends Controller
{
    private function isAdminOpd(): bool
    {
        return Auth::user()?->role?->slug === 'admin-opd';
    }

    public function index(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $opdId = $this->isAdminOpd() ? Auth::user()->opd_id : $request->get('opd_id');
        $kategoriId = $request->get('kategori_id');
        $status = $request->get('status');

        // Laporan terbaru per proyek untuk tahun anggaran terpilih (lihat Keputusan #6
        // di 06-dashboard-eksekutif-minimum.md): MAX(id) per data_spatial_id, mengasumsikan
        // input berurutan per triwulan.
        $latestReportIds = ProjectProgressReport::query()
            ->where('tahun_anggaran', $tahun)
            ->groupBy('data_spatial_id')
            ->selectRaw('MAX(id) as id')
            ->pluck('id');

        $laporanQuery = ProjectProgressReport::query()
            ->whereIn('id', $latestReportIds)
            ->with(['dataSpatial:id,uuid,deskripsi,data_type,sub_type', 'opd:id,name,singkatan', 'kategori:id,nama']);

        if ($opdId) {
            $laporanQuery->where('opd_id', $opdId);
        }

        if ($kategoriId) {
            $laporanQuery->whereIn('kategori_id', Category::selfAndDescendantIds((int) $kategoriId));
        }

        if ($status) {
            $laporanQuery->where('status', $status);
        }

        $laporan = $laporanQuery->get();

        $cards = [
            'jumlah_proyek' => DataSpatial::proyekStrategis()
                ->when($opdId, fn ($q) => $q->where('opd_pengelola_id', $opdId))
                ->count(),
            'jumlah_dilaporkan' => $laporan->count(),
            'total_pagu' => (float) $laporan->sum('pagu'),
            'total_realisasi' => (float) $laporan->sum('realisasi_anggaran'),
            'rata_progres_fisik' => $laporan->count() ? round((float) $laporan->avg('progres_fisik_persen'), 1) : 0,
            'jumlah_bermasalah' => $laporan->where('status', 'terlambat')->count(),
        ];
        $cards['persen_realisasi'] = $cards['total_pagu'] > 0
            ? round(($cards['total_realisasi'] / $cards['total_pagu']) * 100, 1)
            : 0;

        $progresPerSektor = $laporan
            ->groupBy(fn (ProjectProgressReport $item) => $item->kategori?->nama ?? 'Tanpa Sektor')
            ->map(fn ($group) => round((float) $group->avg('progres_fisik_persen'), 1));

        $tahunOptions = ProjectProgressReport::query()
            ->distinct()
            ->orderByDesc('tahun_anggaran')
            ->pluck('tahun_anggaran');
        if ($tahunOptions->isEmpty()) {
            $tahunOptions = collect([now()->year]);
        }

        $opdOptions = $this->isAdminOpd() ? collect() : Opd::orderBy('name')->get(['id', 'name', 'singkatan']);

        $kategoriOptions = Category::whereIn(
            'id',
            DataSpatial::proyekStrategis()->whereNotNull('kategori_id')->pluck('kategori_id')->unique()
        )->orderBy('nama')->get(['id', 'nama']);

        return view('backend.pages.dashboard-pembangunan', compact(
            'cards', 'laporan', 'progresPerSektor', 'tahun', 'tahunOptions', 'opdOptions', 'kategoriOptions', 'opdId', 'kategoriId', 'status'
        ));
    }
}
