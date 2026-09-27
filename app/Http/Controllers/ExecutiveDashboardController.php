<?php

namespace App\Http\Controllers;

use App\Models\DevelopmentProject;
use App\Models\ProjectProgressReport;
use Illuminate\Http\Request;

/**
 * Dashboard eksekutif berbasis fondasi database V2 (development_projects,
 * project_progress_reports via development_project_id, sectors, administrative_regions).
 *
 * Sengaja dibuat terpisah dari PembangunanDashboardController (dashboard existing yang
 * masih berbasis data_spatial/categories) — bukan menggantikannya. Tujuannya membuktikan
 * fondasi Prioritas 5 sudah cukup untuk kebutuhan dashboard eksekutif; keputusan produk
 * "dashboard mana yang dipakai di UI" adalah pekerjaan terpisah, bukan bagian ini.
 */
class ExecutiveDashboardController extends Controller
{
    private function filteredProjects(Request $request)
    {
        return DevelopmentProject::query()
            ->when($request->get('sector_id'), fn ($q, $v) => $q->where('sector_id', $v))
            ->when($request->get('owner_opd_id'), fn ($q, $v) => $q->where('owner_opd_id', $v))
            ->when($request->get('fiscal_year'), fn ($q, $v) => $q->where('fiscal_year', $v))
            ->when($request->get('region_id'), fn ($q, $v) => $q->whereHas('regions', fn ($rq) => $rq->where('administrative_regions.id', $v)));
    }

    /**
     * Kartu ringkasan utama. Setiap angka bisa ditelusuri balik ke baris sumber
     * (development_projects/project_progress_reports), bukan hasil query JSONB ad hoc.
     */
    public function summary(Request $request)
    {
        $projects = $this->filteredProjects($request)->get();
        $projectIds = $projects->pluck('id');

        $reports = ProjectProgressReport::whereIn('development_project_id', $projectIds)->get();

        return response()->json([
            'jumlah_proyek' => $projects->count(),
            'proyek_berjalan' => $projects->where('status', 'berjalan')->count(),
            'proyek_selesai' => $projects->where('status', 'selesai')->count(),
            'perlu_review' => $projects->where('needs_review', true)->count(),
            'total_anggaran' => (float) $projects->sum('budget_amount'),
            'total_realisasi' => (float) $reports->sum('realisasi_anggaran'),
            'rata_progres_fisik' => $reports->count() ? round((float) $reports->avg('progres_fisik_persen'), 1) : 0,
        ]);
    }

    /**
     * Distribusi jumlah dan anggaran proyek per sektor.
     */
    public function bySector(Request $request)
    {
        $data = $this->filteredProjects($request)
            ->with('sector:id,name')
            ->get()
            ->groupBy(fn (DevelopmentProject $p) => $p->sector?->name ?? 'Tanpa Sektor')
            ->map(fn ($group) => [
                'jumlah_proyek' => $group->count(),
                'total_anggaran' => (float) $group->sum('budget_amount'),
            ]);

        return response()->json($data);
    }

    /**
     * Distribusi jumlah proyek per wilayah (lewat pivot project_regions — satu proyek
     * bisa mencakup lebih dari satu wilayah).
     */
    public function byRegion(Request $request)
    {
        $data = $this->filteredProjects($request)
            ->with('regions:id,name')
            ->get()
            ->flatMap(fn (DevelopmentProject $p) => $p->regions->map(fn ($r) => $r->name))
            ->countBy();

        return response()->json($data);
    }

    /**
     * Tren jumlah proyek dan anggaran per tahun anggaran.
     */
    public function trend(Request $request)
    {
        $data = $this->filteredProjects($request)
            ->get()
            ->groupBy('fiscal_year')
            ->map(fn ($group) => [
                'jumlah_proyek' => $group->count(),
                'total_anggaran' => (float) $group->sum('budget_amount'),
            ])
            ->sortKeys();

        return response()->json($data);
    }
}
