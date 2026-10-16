<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Angka-angka Dashboard backend, dibedakan per profil role:
 * - `executive` (pimpinan): ringkasan strategis lintas OPD, tanpa daftar operasional.
 * - `opd` (admin-opd): hanya data milik OPD pengguna (layer, aspirasi kategori OPD, proyek OPD).
 * - `admin` (super-admin, admin-bappeda, role lain): operasional menyeluruh + daftar tindakan.
 *   Super-admin & admin-bappeda bisa beralih ke tampilan eksekutif (?tampilan=executive).
 */
class DashboardMetrics
{
    public const PROFILE_EXECUTIVE = 'executive';

    public const PROFILE_OPD = 'opd';

    public const PROFILE_ADMIN = 'admin';

    /** Aspirasi pending lebih lama dari ini (hari) dianggap terlambat ditanggapi. */
    public const OVERDUE_DAYS = 7;

    /** Role selain pimpinan yang boleh beralih ke tampilan eksekutif. */
    public const EXECUTIVE_SWITCHERS = ['super-admin', 'admin-bappeda'];

    public readonly string $profile;

    /** True bila pengguna bisa beralih antara tampilan operasional dan eksekutif. */
    public readonly bool $canSwitchToExecutive;

    private readonly ?int $opdId;

    /**
     * @param  ?string  $requestedProfile  Tampilan yang diminta (mis. dari ?tampilan=); hanya dihormati bila role mengizinkan.
     */
    public function __construct(private readonly User $user, ?string $requestedProfile = null)
    {
        $slug = $user->role?->slug;
        $this->canSwitchToExecutive = in_array($slug, self::EXECUTIVE_SWITCHERS, true);

        $this->profile = match (true) {
            $slug === 'pimpinan' => self::PROFILE_EXECUTIVE,
            $slug === 'admin-opd' => self::PROFILE_OPD,
            $this->canSwitchToExecutive && $requestedProfile === self::PROFILE_EXECUTIVE => self::PROFILE_EXECUTIVE,
            default => self::PROFILE_ADMIN,
        };
        $this->opdId = $this->profile === self::PROFILE_OPD ? $user->opd_id : null;
    }

    public function isOpd(): bool
    {
        return $this->profile === self::PROFILE_OPD;
    }

    /**
     * Ringkasan data spasial (Layer V3 + fitur).
     *
     * @return array{published: int, draft: int, archived: int, total: int, features: int, updatedRecently: int, withoutOpd: int, publishedRate: float}
     */
    public function spatial(): array
    {
        $statuses = $this->layers()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $statuses->sum();
        $published = (int) ($statuses['published'] ?? 0);

        // Admin OPD: fitur yang ia buat sendiri (konsisten dengan angka "Total Data Spasial" lama).
        $features = DB::table('spatial_features')
            ->when($this->isOpd(), fn (Builder $query) => $query->where('created_by', $this->user->id))
            ->count();

        return [
            'published' => $published,
            'draft' => (int) ($statuses['draft'] ?? 0),
            'archived' => (int) ($statuses['archived'] ?? 0),
            'total' => $total,
            'features' => $features,
            'updatedRecently' => $this->layers()->where('updated_at', '>=', now()->subDays(30))->count(),
            'withoutOpd' => $this->isOpd() ? 0 : $this->layers()->whereNull('opd_id')->count(),
            'publishedRate' => $total ? round($published / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * Kategori dengan layer terbit terbanyak.
     *
     * @return Collection<int, object{name: string, total: int}>
     */
    public function topCategories(int $limit = 6): Collection
    {
        return $this->layers()
            ->join('categories_v3', 'categories_v3.id', '=', 'layers.category_id')
            ->where('layers.status', 'published')
            ->selectRaw('categories_v3.name as name, count(*) as total')
            ->groupBy('categories_v3.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * Layer yang terakhir diperbarui.
     *
     * @return Collection<int, object>
     */
    public function recentLayers(int $limit = 6): Collection
    {
        return $this->layers()
            ->leftJoin('opd', 'opd.id', '=', 'layers.opd_id')
            ->select('layers.id', 'layers.name', 'layers.status', 'layers.feature_count', 'layers.updated_at', 'opd.name as opd_name')
            ->orderByDesc('layers.updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Kinerja layanan aspirasi.
     *
     * @return array{total: int, pending: int, diproses: int, selesai: int, completionRate: float, overdue: int, avgResponseDays: ?float, thisMonth: int, lastMonth: int}
     */
    public function aspirasi(): array
    {
        $statuses = $this->aspirasiQuery()->selectRaw('aspirasi.status, count(*) as total')->groupBy('aspirasi.status')->pluck('total', 'status');
        $total = (int) $statuses->sum();
        $selesai = (int) ($statuses['selesai'] ?? 0);

        $avgSeconds = $this->aspirasiQuery()
            ->whereNotNull('aspirasi.tanggal_respon')
            ->selectRaw('avg(extract(epoch from (aspirasi.tanggal_respon - aspirasi.created_at))) as seconds')
            ->value('seconds');

        return [
            'total' => $total,
            'pending' => (int) ($statuses['pending'] ?? 0),
            'diproses' => (int) ($statuses['diproses'] ?? 0),
            'selesai' => $selesai,
            'completionRate' => $total ? round($selesai / $total * 100, 1) : 0.0,
            'overdue' => $this->aspirasiQuery()->where('aspirasi.status', 'pending')->where('aspirasi.created_at', '<', now()->subDays(self::OVERDUE_DAYS))->count(),
            'avgResponseDays' => $avgSeconds !== null ? round(max(0, (float) $avgSeconds) / 86400, 1) : null,
            'thisMonth' => $this->aspirasiQuery()->where('aspirasi.created_at', '>=', now()->startOfMonth())->count(),
            'lastMonth' => $this->aspirasiQuery()->whereBetween('aspirasi.created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth()])->count(),
        ];
    }

    /**
     * Tren aspirasi 12 bulan terakhir (masuk vs selesai).
     *
     * @return array{labels: array<int, string>, total: array<int, int>, selesai: array<int, int>}
     */
    public function aspirasiTrend(): array
    {
        $months = $this->lastMonths(12);
        $rows = $this->aspirasiQuery()
            ->where('aspirasi.created_at', '>=', $months->first()['start'])
            ->selectRaw("to_char(aspirasi.created_at, 'YYYY-MM') as month, count(*) as total, sum(case when aspirasi.status = 'selesai' then 1 else 0 end) as selesai")
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        return [
            'labels' => $months->pluck('label')->all(),
            'total' => $months->map(fn (array $month) => (int) ($rows[$month['key']]->total ?? 0))->all(),
            'selesai' => $months->map(fn (array $month) => (int) ($rows[$month['key']]->selesai ?? 0))->all(),
        ];
    }

    /**
     * Aspirasi terbaru (untuk admin & OPD).
     *
     * @return Collection<int, object>
     */
    public function recentAspirasi(int $limit = 6): Collection
    {
        return $this->aspirasiQuery()
            ->select('aspirasi.id', 'aspirasi.nomor_tiket', 'aspirasi.nama_pengirim', 'aspirasi.judul_aspirasi', 'aspirasi.status', 'aspirasi.created_at', 'kategori_aspirasi.nama_kategori')
            ->orderByDesc('aspirasi.created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Portofolio proyek pembangunan (development_projects) + realisasi dari laporan progres.
     *
     * @return array{count: int, budget: float, active: int, completed: int, needsReview: int, realisasi: float, avgProgress: ?float, absorption: ?float, latestYear: ?int}
     */
    public function projects(): array
    {
        $summary = $this->projectsQuery()
            ->selectRaw("count(*) as total, coalesce(sum(budget_amount), 0) as budget,
                sum(case when status = 'berjalan' then 1 else 0 end) as active,
                sum(case when status = 'selesai' then 1 else 0 end) as completed,
                sum(case when needs_review then 1 else 0 end) as needs_review,
                max(fiscal_year) as latest_year")
            ->first();

        $reports = DB::table('project_progress_reports')
            ->whereIn('development_project_id', $this->projectsQuery()->select('id'))
            ->selectRaw('coalesce(sum(realisasi_anggaran), 0) as realisasi, sum(pagu) as pagu, avg(progres_fisik_persen) as progress, count(*) as total')
            ->first();

        $pagu = (float) ($reports->pagu ?? 0);

        return [
            'count' => (int) $summary->total,
            'budget' => (float) $summary->budget,
            'active' => (int) $summary->active,
            'completed' => (int) $summary->completed,
            'needsReview' => (int) $summary->needs_review,
            'realisasi' => (float) $reports->realisasi,
            'avgProgress' => $reports->total ? round((float) $reports->progress, 1) : null,
            'absorption' => $pagu > 0 ? round((float) $reports->realisasi / $pagu * 100, 1) : null,
            'latestYear' => $summary->latest_year ? (int) $summary->latest_year : null,
        ];
    }

    /**
     * Anggaran & jumlah proyek per sektor, terbesar dulu.
     *
     * @return Collection<int, object{name: string, total: int, budget: float}>
     */
    public function projectsBySector(int $limit = 8): Collection
    {
        return $this->projectsQuery()
            ->leftJoin('sectors', 'sectors.id', '=', 'development_projects.sector_id')
            ->selectRaw("coalesce(sectors.name, 'Tanpa sektor') as name, count(*) as total, coalesce(sum(development_projects.budget_amount), 0) as budget")
            ->groupByRaw("coalesce(sectors.name, 'Tanpa sektor')")
            ->orderByDesc('budget')
            ->limit($limit)
            ->get()
            ->map(fn (object $row) => (object) ['name' => $row->name, 'total' => (int) $row->total, 'budget' => (float) $row->budget]);
    }

    /**
     * Jumlah & anggaran proyek per tahun anggaran.
     *
     * @return Collection<int, object{year: int, total: int, budget: float}>
     */
    public function projectsByYear(): Collection
    {
        return $this->projectsQuery()
            ->whereNotNull('fiscal_year')
            ->selectRaw('fiscal_year as year, count(*) as total, coalesce(sum(budget_amount), 0) as budget')
            ->groupBy('fiscal_year')
            ->orderBy('fiscal_year')
            ->get()
            ->map(fn (object $row) => (object) ['year' => (int) $row->year, 'total' => (int) $row->total, 'budget' => (float) $row->budget]);
    }

    /**
     * Rapor tiap OPD: layer terbit, proyek & anggaran, serta penyelesaian aspirasi.
     *
     * @return Collection<int, object{name: string, layers: int, projects: int, budget: float, aspirasi: int, selesai: int, completionRate: ?float}>
     */
    public function opdScorecard(): Collection
    {
        $layers = DB::table('layers')->whereNull('deleted_at')->where('status', 'published')
            ->selectRaw('opd_id, count(*) as total')->groupBy('opd_id')->pluck('total', 'opd_id');
        $projects = DB::table('development_projects')
            ->selectRaw('owner_opd_id, count(*) as total, coalesce(sum(budget_amount), 0) as budget')
            ->groupBy('owner_opd_id')->get()->keyBy('owner_opd_id');
        $aspirasi = DB::table('aspirasi')
            ->join('kategori_aspirasi', 'kategori_aspirasi.id', '=', 'aspirasi.kategori_aspirasi_id')
            ->selectRaw("kategori_aspirasi.opd_id, count(*) as total, sum(case when aspirasi.status = 'selesai' then 1 else 0 end) as selesai")
            ->groupBy('kategori_aspirasi.opd_id')->get()->keyBy('opd_id');

        return DB::table('opd')->orderBy('name')->get(['id', 'name'])
            ->map(function (object $opd) use ($layers, $projects, $aspirasi) {
                $aspirasiTotal = (int) ($aspirasi[$opd->id]->total ?? 0);
                $selesai = (int) ($aspirasi[$opd->id]->selesai ?? 0);

                return (object) [
                    'name' => $opd->name,
                    'layers' => (int) ($layers[$opd->id] ?? 0),
                    'projects' => (int) ($projects[$opd->id]->total ?? 0),
                    'budget' => (float) ($projects[$opd->id]->budget ?? 0),
                    'aspirasi' => $aspirasiTotal,
                    'selesai' => $selesai,
                    'completionRate' => $aspirasiTotal ? round($selesai / $aspirasiTotal * 100, 1) : null,
                ];
            })
            ->sortByDesc(fn (object $row) => [$row->budget, $row->layers])
            ->values();
    }

    /**
     * Jangkauan publik: pengunjung 30 hari terakhir dibanding 30 hari sebelumnya.
     *
     * @return array{last30: int, previous30: int, growth: ?float, unique30: int, today: int, total: int, publications: int, downloads: int, sharedMaps: int}
     */
    public function reach(): array
    {
        $now = now();
        $last30 = DB::table('visitors')->where('created_at', '>=', $now->copy()->subDays(30))->count();
        $previous30 = DB::table('visitors')->whereBetween('created_at', [$now->copy()->subDays(60), $now->copy()->subDays(30)])->count();

        return [
            'last30' => $last30,
            'previous30' => $previous30,
            'growth' => $previous30 ? round(($last30 - $previous30) / $previous30 * 100, 1) : null,
            'unique30' => (int) DB::table('visitors')->where('created_at', '>=', $now->copy()->subDays(30))->distinct()->count('ip'),
            'today' => DB::table('visitors')->whereDate('created_at', $now->toDateString())->count(),
            'total' => DB::table('visitors')->count(),
            'publications' => DB::table('publications')->count(),
            'downloads' => (int) DB::table('publications')->sum('download_count'),
            'sharedMaps' => DB::table('shared_maps')->count(),
        ];
    }

    /**
     * Pengunjung per bulan, 12 bulan terakhir.
     *
     * @return array{labels: array<int, string>, total: array<int, int>, unique: array<int, int>}
     */
    public function visitorTrend(): array
    {
        $months = $this->lastMonths(12);
        $rows = DB::table('visitors')
            ->where('created_at', '>=', $months->first()['start'])
            ->selectRaw("to_char(created_at, 'YYYY-MM') as month, count(*) as total, count(distinct ip) as unique_total")
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        return [
            'labels' => $months->pluck('label')->all(),
            'total' => $months->map(fn (array $month) => (int) ($rows[$month['key']]->total ?? 0))->all(),
            'unique' => $months->map(fn (array $month) => (int) ($rows[$month['key']]->unique_total ?? 0))->all(),
        ];
    }

    /**
     * Halaman publik paling sering dikunjungi (30 hari terakhir).
     *
     * @return Collection<int, object{page: string, total: int}>
     */
    public function topPages(int $limit = 6): Collection
    {
        return DB::table('visitors')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('page_visited')
            ->selectRaw('page_visited as page, count(*) as total')
            ->groupBy('page_visited')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * Hal yang butuh tindak lanjut; hanya item dengan jumlah > 0 yang dikembalikan.
     *
     * @param  array<string, mixed>  $spatial
     * @param  array<string, mixed>  $aspirasi
     * @param  array<string, mixed>  $projects
     * @return array<int, array{label: string, hint: string, count: int, tone: string, icon: string, url: ?string}>
     */
    public function attention(array $spatial, array $aspirasi, array $projects): array
    {
        $items = [
            [
                'label' => 'Aspirasi terlambat ditanggapi',
                'hint' => 'Masih pending lebih dari '.self::OVERDUE_DAYS.' hari',
                'count' => $aspirasi['overdue'],
                'tone' => 'danger',
                'icon' => 'mdi-alarm',
                'url' => $this->routeIfAllowed('aspirasi.index', 'aspirasi.view'),
            ],
            [
                'label' => 'Aspirasi menunggu respon',
                'hint' => 'Status pending',
                'count' => $aspirasi['pending'],
                'tone' => 'warning',
                'icon' => 'mdi-email-alert-outline',
                'url' => $this->routeIfAllowed('aspirasi.index', 'aspirasi.view'),
            ],
            [
                'label' => 'Layer masih draft',
                'hint' => 'Belum tampil di peta publik',
                'count' => $spatial['draft'],
                'tone' => 'info',
                'icon' => 'mdi-file-document-edit-outline',
                'url' => $this->routeIfAllowed('spatial-layers.index', 'spatial-layers.view'),
            ],
            [
                'label' => 'Layer tanpa OPD pengelola',
                'hint' => 'Lengkapi instansi penanggung jawab',
                'count' => $spatial['withoutOpd'],
                'tone' => 'secondary',
                'icon' => 'mdi-domain-off',
                'url' => $this->routeIfAllowed('spatial-layers.index', 'spatial-layers.view'),
            ],
            [
                'label' => 'Proyek perlu ditinjau',
                'hint' => 'Ditandai needs review saat migrasi data',
                'count' => $projects['needsReview'],
                'tone' => 'secondary',
                'icon' => 'mdi-clipboard-alert-outline',
                'url' => null,
            ],
        ];

        return array_values(array_filter($items, fn (array $item) => $item['count'] > 0));
    }

    /**
     * Kalimat sorotan otomatis untuk pimpinan.
     *
     * @param  array<string, mixed>  $spatial
     * @param  array<string, mixed>  $aspirasi
     * @param  array<string, mixed>  $projects
     * @param  array<string, mixed>  $reach
     * @param  Collection<int, object>  $sectors
     * @return array<int, array{tone: string, text: string}>
     */
    public function highlights(array $spatial, array $aspirasi, array $projects, array $reach, Collection $sectors): array
    {
        $items = [];
        $topSector = $sectors->first();

        if ($topSector && $projects['budget'] > 0) {
            $share = round($topSector->budget / $projects['budget'] * 100, 1);
            $items[] = ['tone' => 'info', 'text' => "Sektor <b>{$topSector->name}</b> menyerap <b>".self::number($share, 1)."%</b> anggaran ({$topSector->total} proyek, ".self::rupiah($topSector->budget).').'];
        }

        if ($projects['count'] > 0) {
            $items[] = ['tone' => 'info', 'text' => '<b>'.self::number($projects['active']).'</b> dari '.self::number($projects['count']).' proyek pembangunan berstatus berjalan.'];
        }

        if ($projects['absorption'] !== null) {
            $tone = $projects['absorption'] < 50 ? 'warning' : 'success';
            $items[] = ['tone' => $tone, 'text' => 'Serapan anggaran tercatat <b>'.self::number($projects['absorption'], 1).'%</b> dengan rata-rata progres fisik '.self::number($projects['avgProgress'] ?? 0, 1).'%.'];
        } elseif ($projects['count'] > 0) {
            $items[] = ['tone' => 'warning', 'text' => 'Belum ada laporan progres fisik & realisasi anggaran dari OPD.'];
        }

        if ($aspirasi['total'] > 0) {
            $tone = $aspirasi['overdue'] > 0 ? 'danger' : 'success';
            $text = '<b>'.self::number($aspirasi['completionRate'], 1).'%</b> aspirasi masyarakat telah diselesaikan';
            $text .= $aspirasi['overdue'] > 0 ? ", <b>{$aspirasi['overdue']}</b> terlambat ditanggapi." : '.';
            $items[] = ['tone' => $tone, 'text' => $text];
        }

        if ($reach['growth'] !== null) {
            $up = $reach['growth'] >= 0;
            $items[] = ['tone' => $up ? 'success' : 'warning', 'text' => 'Kunjungan publik 30 hari terakhir '.($up ? 'naik' : 'turun').' <b>'.self::number(abs($reach['growth']), 1).'%</b> ('.self::number($reach['last30']).' kunjungan).'];
        }

        $items[] = ['tone' => 'info', 'text' => '<b>'.self::number($spatial['published']).'</b> layer data spasial terbit ('.self::number($spatial['publishedRate'], 1).'% dari seluruh layer).'];

        return $items;
    }

    public static function number(float|int|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }

    /**
     * Rupiah ringkas: Rp 6,22 T / Rp 109,9 M / Rp 450 Jt.
     */
    public static function rupiah(float|int|null $value): string
    {
        $value = (float) $value;

        return match (true) {
            $value >= 1e12 => 'Rp '.self::number($value / 1e12, 2).' T',
            $value >= 1e9 => 'Rp '.self::number($value / 1e9, 1).' M',
            $value >= 1e6 => 'Rp '.self::number($value / 1e6, 1).' Jt',
            default => 'Rp '.self::number($value),
        };
    }

    private function layers(): Builder
    {
        return DB::table('layers')
            ->whereNull('layers.deleted_at')
            ->when($this->isOpd(), fn (Builder $query) => $query->where('layers.opd_id', $this->opdId));
    }

    private function aspirasiQuery(): Builder
    {
        return DB::table('aspirasi')
            ->leftJoin('kategori_aspirasi', 'kategori_aspirasi.id', '=', 'aspirasi.kategori_aspirasi_id')
            ->when($this->isOpd(), fn (Builder $query) => $query->where('kategori_aspirasi.opd_id', $this->opdId));
    }

    private function projectsQuery(): Builder
    {
        return DB::table('development_projects')
            ->when($this->isOpd(), fn (Builder $query) => $query->where('development_projects.owner_opd_id', $this->opdId));
    }

    /**
     * @return Collection<int, array{key: string, label: string, start: Carbon}>
     */
    private function lastMonths(int $count): Collection
    {
        $start = now()->startOfMonth()->subMonthsNoOverflow($count - 1);

        return collect(range(0, $count - 1))->map(function (int $offset) use ($start) {
            $month = $start->copy()->addMonthsNoOverflow($offset);

            return ['key' => $month->format('Y-m'), 'label' => $month->locale('id')->translatedFormat('M y'), 'start' => $month];
        });
    }

    private function routeIfAllowed(string $route, string $permission): ?string
    {
        return $this->user->can($permission) ? route($route) : null;
    }
}
