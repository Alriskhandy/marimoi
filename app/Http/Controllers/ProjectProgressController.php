<?php

namespace App\Http\Controllers;

use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\ProjectProgressReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectProgressController extends Controller
{
    private function isAdminOpd(): bool
    {
        return Auth::user()?->role?->slug === 'admin-opd';
    }

    private function scopeProjectsToOpd($query)
    {
        if ($this->isAdminOpd()) {
            $query->where('opd_pengelola_id', Auth::user()->opd_id);
        }

        return $query;
    }

    private function authorizeProject(DataSpatial $proyek): void
    {
        if ($this->isAdminOpd() && (int) $proyek->opd_pengelola_id !== (int) Auth::user()->opd_id) {
            abort(403, 'Anda tidak memiliki akses ke proyek OPD lain.');
        }
    }

    public function index(Request $request)
    {
        $query = DataSpatial::query()
            ->proyekStrategis()
            ->with(['kategori:id,nama', 'opdPengelola:id,name,singkatan'])
            ->with(['progressReports' => fn ($q) => $q->limit(1)]);

        $this->scopeProjectsToOpd($query);

        if ($request->filled('sub_type')) {
            $query->where('sub_type', $request->sub_type);
        }

        if ($request->filled('opd_pengelola_id') && ! $this->isAdminOpd()) {
            $query->where('opd_pengelola_id', $request->opd_pengelola_id);
        }

        $proyek = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $opdOptions = $this->isAdminOpd() ? collect() : Opd::orderBy('name')->get(['id', 'name', 'singkatan']);

        return view('backend.pages.project-progress.index', compact('proyek', 'opdOptions'));
    }

    public function show(string $uuid)
    {
        $proyek = DataSpatial::where('uuid', $uuid)
            ->proyekStrategis()
            ->with(['kategori:id,nama', 'opdPengelola:id,name,singkatan'])
            ->firstOrFail();

        $this->authorizeProject($proyek);

        $laporan = $proyek->progressReports()->with(['pelapor:id,name', 'revisions'])->get();

        return view('backend.pages.project-progress.show', compact('proyek', 'laporan'));
    }

    public function create(string $uuid)
    {
        $proyek = DataSpatial::where('uuid', $uuid)
            ->proyekStrategis()
            ->firstOrFail();

        $this->authorizeProject($proyek);

        return view('backend.pages.project-progress.create', [
            'proyek' => $proyek,
            'statuses' => ProjectProgressReport::STATUSES,
            'periodeOptions' => ProjectProgressReport::PERIODE,
        ]);
    }

    public function store(Request $request, string $uuid)
    {
        $proyek = DataSpatial::where('uuid', $uuid)
            ->proyekStrategis()
            ->firstOrFail();

        $this->authorizeProject($proyek);

        $validated = $request->validate([
            'tahun_anggaran' => 'required|integer|min:2000|max:2100',
            'periode_laporan' => 'required|string|in:'.implode(',', ProjectProgressReport::PERIODE),
            'pagu' => 'nullable|numeric|min:0',
            'realisasi_anggaran' => 'nullable|numeric|min:0',
            'progres_fisik_persen' => 'required|numeric|min:0|max:100',
            'status' => 'required|string|in:'.implode(',', ProjectProgressReport::STATUSES),
            'catatan' => 'nullable|string|max:2000',
        ]);

        $duplikat = ProjectProgressReport::where('data_spatial_id', $proyek->id)
            ->where('tahun_anggaran', $validated['tahun_anggaran'])
            ->where('periode_laporan', $validated['periode_laporan'])
            ->exists();

        if ($duplikat) {
            return back()->withInput()->withErrors([
                'periode_laporan' => 'Laporan untuk periode dan tahun anggaran ini sudah pernah ditambahkan.',
            ]);
        }

        ProjectProgressReport::create([
            ...$validated,
            'data_spatial_id' => $proyek->id,
            'opd_id' => $proyek->opd_pengelola_id,
            'kategori_id' => $proyek->kategori_id,
            'sumber_data' => ProjectProgressReport::SUMBER_MANUAL,
            'dilaporkan_oleh' => Auth::id(),
        ]);

        return redirect()
            ->route('project-progress.show', $proyek->uuid)
            ->with('success', 'Laporan progres berhasil ditambahkan.');
    }

    /**
     * Cari satu laporan progres milik proyek $uuid — memastikan $report benar-benar
     * anak dari proyek itu (bukan sekadar ID valid milik proyek lain).
     */
    private function findReportForProject(DataSpatial $proyek, int $reportId): ProjectProgressReport
    {
        return ProjectProgressReport::where('data_spatial_id', $proyek->id)
            ->findOrFail($reportId);
    }

    public function edit(string $uuid, int $report)
    {
        $proyek = DataSpatial::where('uuid', $uuid)
            ->proyekStrategis()
            ->firstOrFail();

        $this->authorizeProject($proyek);

        $laporan = $this->findReportForProject($proyek, $report);

        return view('backend.pages.project-progress.edit', [
            'proyek' => $proyek,
            'laporan' => $laporan,
            'statuses' => ProjectProgressReport::STATUSES,
        ]);
    }

    /**
     * Perbarui field finansial/progres pada laporan yang sudah ada (bukan append-only
     * murni seperti versi awal bagian D) — setiap pembaruan mencatat nilai sebelumnya
     * ke project_progress_report_revisions supaya histori tetap tertelusuri. tahun_anggaran
     * dan periode_laporan (identitas laporan) sengaja tidak bisa diubah lewat aksi ini.
     */
    public function update(Request $request, string $uuid, int $report)
    {
        $proyek = DataSpatial::where('uuid', $uuid)
            ->proyekStrategis()
            ->firstOrFail();

        $this->authorizeProject($proyek);

        $laporan = $this->findReportForProject($proyek, $report);

        $validated = $request->validate([
            'pagu' => 'nullable|numeric|min:0',
            'realisasi_anggaran' => 'nullable|numeric|min:0',
            'progres_fisik_persen' => 'required|numeric|min:0|max:100',
            'status' => 'required|string|in:'.implode(',', ProjectProgressReport::STATUSES),
            'catatan' => 'nullable|string|max:2000',
        ]);

        $nilaiSebelumnya = $laporan->only(ProjectProgressReport::FIELD_DAPAT_DIPERBARUI);

        $laporan->revisions()->create([
            'data_sebelumnya' => $nilaiSebelumnya,
            'diperbarui_oleh' => Auth::id(),
        ]);

        $laporan->update($validated);

        return redirect()
            ->route('project-progress.show', $proyek->uuid)
            ->with('success', 'Laporan progres berhasil diperbarui.');
    }
}
