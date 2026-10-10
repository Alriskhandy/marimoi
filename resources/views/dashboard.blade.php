@extends('backend.partials.main')

@php($isExecutive = $profile === \App\Support\DashboardMetrics::PROFILE_EXECUTIVE)

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi {{ $isExecutive ? 'mdi-chart-box-outline' : 'mdi-home' }}"></i>
            </span> {{ $isExecutive ? 'Dashboard Eksekutif' : 'Dashboard' }}
        </h3>
        @if ($canSwitchToExecutive)
            <div class="dash-switch" role="tablist" aria-label="Pilih tampilan dashboard">
                <a href="{{ route('dashboard') }}" role="tab" aria-selected="{{ $isExecutive ? 'false' : 'true' }}" @class(['is-active' => ! $isExecutive])>
                    <i class="mdi mdi-view-dashboard-outline"></i> Operasional
                </a>
                <a href="{{ route('dashboard', ['tampilan' => \App\Support\DashboardMetrics::PROFILE_EXECUTIVE]) }}" role="tab" aria-selected="{{ $isExecutive ? 'true' : 'false' }}" @class(['is-active' => $isExecutive])>
                    <i class="mdi mdi-chart-box-outline"></i> Eksekutif
                </a>
            </div>
        @else
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>{{ $isExecutive ? 'Ringkasan pimpinan' : 'Overview' }}
                    </li>
                </ul>
            </nav>
        @endif
    </div>

    <div class="dash" data-dashboard-profile="{{ $profile }}">
        @if ($isExecutive)
            @include('backend.pages.dashboard.executive')
        @else
            @include('backend.pages.dashboard.operational')
        @endif
    </div>
@endsection

@section('scripts')
    @php($dashboardCharts = ['aspirasi' => $aspirasiTrend, 'visitors' => $visitorTrend ?? null, 'projectsYear' => $projectsByYear ?? null])
    <script>
        (function () {
            if (typeof Chart === 'undefined') { return; }

            var charts = @json($dashboardCharts);
            var font = { family: getComputedStyle(document.body).fontFamily, size: 12 };
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            var grid = { color: isDark ? 'rgba(148, 163, 184, .12)' : 'rgba(15, 23, 42, .06)' };
            var number = new Intl.NumberFormat('id-ID');

            Chart.defaults.font = font;
            Chart.defaults.color = isDark ? '#94a3b8' : '#64748b';

            function rupiah(value) {
                if (value >= 1e12) { return 'Rp ' + (value / 1e12).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' T'; }
                if (value >= 1e9) { return 'Rp ' + (value / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M'; }
                if (value >= 1e6) { return 'Rp ' + (value / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' Jt'; }
                return 'Rp ' + number.format(value);
            }

            function gradient(ctx, color) {
                var g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.clientHeight || 300);
                g.addColorStop(0, color + '55');
                g.addColorStop(1, color + '00');
                return g;
            }

            function line(id, labels, sets) {
                var el = document.getElementById(id);
                if (!el) { return; }
                var ctx = el.getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: sets.map(function (set) {
                            return {
                                label: set.label, data: set.data, borderColor: set.color, backgroundColor: gradient(ctx, set.color),
                                fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 3, pointHoverRadius: 5, pointBackgroundColor: set.color,
                            };
                        }),
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
                            tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + number.format(c.parsed.y); } } },
                        },
                        scales: {
                            y: { beginAtZero: true, grid: grid, ticks: { precision: 0, callback: function (v) { return number.format(v); } } },
                            x: { grid: { display: false } },
                        },
                    },
                });
            }

            if (charts.aspirasi) {
                line('chartAspirasi', charts.aspirasi.labels, [
                    { label: 'Masuk', data: charts.aspirasi.total, color: '#7c3aed' },
                    { label: 'Selesai', data: charts.aspirasi.selesai, color: '#10b981' },
                ]);
            }

            if (charts.visitors) {
                line('chartVisitors', charts.visitors.labels, [
                    { label: 'Kunjungan', data: charts.visitors.total, color: '#0a84ff' },
                    { label: 'Pengunjung unik', data: charts.visitors.unique, color: '#f59e0b' },
                ]);
            }

            var yearEl = document.getElementById('chartProjectsYear');
            if (yearEl && charts.projectsYear) {
                new Chart(yearEl.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: charts.projectsYear.map(function (r) { return 'TA ' + r.year; }),
                        datasets: [
                            { type: 'bar', label: 'Anggaran', data: charts.projectsYear.map(function (r) { return r.budget; }), backgroundColor: isDark ? '#3b6fd8' : '#1d3557', borderRadius: 8, maxBarThickness: 56, yAxisID: 'y' },
                            { type: 'line', label: 'Jumlah proyek', data: charts.projectsYear.map(function (r) { return r.total; }), borderColor: '#20d9ff', backgroundColor: '#20d9ff', borderWidth: 2.5, pointRadius: 5, tension: 0.3, yAxisID: 'y1' },
                        ],
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
                            tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + (c.dataset.yAxisID === 'y' ? rupiah(c.parsed.y) : number.format(c.parsed.y) + ' proyek'); } } },
                        },
                        scales: {
                            y: { beginAtZero: true, grid: grid, ticks: { callback: function (v) { return rupiah(v); } } },
                            y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { precision: 0 } },
                            x: { grid: { display: false } },
                        },
                    },
                });
            }
        })();
    </script>

    <style>
        .dash-switch { display: inline-flex; padding: 4px; gap: 4px; border-radius: 999px; background: rgba(124, 58, 237, .08); border: 1px solid rgba(124, 58, 237, .15); }
        .dash-switch a { display: inline-flex; align-items: center; gap: .35rem; padding: .45rem 1rem; border-radius: 999px; font-size: .85rem; font-weight: 600; color: #64748b; text-decoration: none; transition: background .2s, color .2s; }
        .dash-switch a:hover { color: #7c3aed; }
        .dash-switch a.is-active { background: linear-gradient(135deg, #7c3aed, #0a84ff); color: #fff; box-shadow: 0 6px 16px -8px rgba(124, 58, 237, .8); }
        [data-theme="dark"] .dash-switch a { color: #94a3b8; }
        @media (max-width: 576px) { .dash-switch { width: 100%; margin-top: .75rem; } .dash-switch a { flex: 1; justify-content: center; } }
        .dash { --dash-ink: #0f172a; --dash-muted: #64748b; --dash-line: rgba(15, 23, 42, .08); --dash-surface: #fff; --dash-soft: #f8fafc; --dash-track: #eef2f7; --dash-accent: #7c3aed; color: var(--dash-ink); }
        [data-theme="dark"] .dash { --dash-ink: #e2e8f0; --dash-muted: #94a3b8; --dash-line: rgba(148, 163, 184, .16); --dash-surface: rgba(255, 255, 255, .03); --dash-soft: rgba(255, 255, 255, .05); --dash-track: rgba(148, 163, 184, .18); --dash-accent: #a78bfa; }
        .dash .dash-tone-primary { --tone: #7c3aed; --tone-bg: #f3e8ff; }
        .dash .dash-tone-info { --tone: #0a84ff; --tone-bg: #e0f2fe; }
        .dash .dash-tone-success { --tone: #059669; --tone-bg: #d1fae5; }
        .dash .dash-tone-warning { --tone: #d97706; --tone-bg: #fef3c7; }
        .dash .dash-tone-danger { --tone: #dc2626; --tone-bg: #fee2e2; }
        .dash .dash-tone-secondary { --tone: #475569; --tone-bg: #f1f5f9; }
        [data-theme="dark"] .dash .dash-tone-primary { --tone: #c4b5fd; --tone-bg: rgba(124, 58, 237, .18); }
        [data-theme="dark"] .dash .dash-tone-info { --tone: #7cc4ff; --tone-bg: rgba(10, 132, 255, .16); }
        [data-theme="dark"] .dash .dash-tone-success { --tone: #6ee7b7; --tone-bg: rgba(16, 185, 129, .16); }
        [data-theme="dark"] .dash .dash-tone-warning { --tone: #fcd34d; --tone-bg: rgba(245, 158, 11, .16); }
        [data-theme="dark"] .dash .dash-tone-danger { --tone: #fca5a5; --tone-bg: rgba(239, 68, 68, .16); }
        [data-theme="dark"] .dash .dash-tone-secondary { --tone: #cbd5e1; --tone-bg: rgba(148, 163, 184, .14); }
        [data-theme="dark"] .dash .dash-trend.is-up { color: #34d399; }
        [data-theme="dark"] .dash .dash-trend.is-down { color: #f87171; }

        .dash-eyebrow { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--dash-accent); }
        .dash-trend { display: inline-flex; align-items: center; gap: 1px; font-weight: 700; }
        .dash-trend.is-up { color: #059669; }
        .dash-trend.is-down { color: #dc2626; }

        /* Kartu umum */
        .dash-card { border: 1px solid var(--dash-line); border-radius: 16px; box-shadow: 0 10px 30px -24px rgba(15, 23, 42, .35); }
        .dash-card .card-body { padding: 1.4rem 1.5rem; }
        .dash-card-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
        .dash-card-title { margin: 0; font-size: 1rem; font-weight: 700; color: var(--dash-ink); display: flex; align-items: center; gap: .5rem; }
        .dash-card-title i { color: var(--dash-accent); font-size: 1.2rem; }
        .dash-card-meta { font-size: .8rem; color: var(--dash-muted); }
        .dash-card-link { font-size: .82rem; font-weight: 600; text-decoration: none; }
        .dash-subhead { margin: 1.25rem 0 .6rem; font-size: .72rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--dash-muted); }
        .dash-empty { display: flex; align-items: center; gap: .6rem; padding: 1.25rem; border-radius: 12px; background: var(--dash-soft); color: var(--dash-muted); font-size: .9rem; }
        .dash-empty i { font-size: 1.4rem; }

        /* KPI */
        .dash-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; }
        @media (min-width: 1200px) { .dash-kpis { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
        .dash-status { display: inline-block; padding: .25rem .6rem; border-radius: 999px; background: var(--tone-bg); color: var(--tone); font-size: .75rem; font-weight: 700; white-space: nowrap; }
        .dash-note { display: flex; gap: .5rem; align-items: center; padding: .6rem .8rem; border-radius: 10px; background: var(--dash-soft); color: var(--dash-muted); font-size: .84rem; }
        .dash-kpi { display: flex; gap: .9rem; align-items: flex-start; padding: 1.15rem 1.2rem; border: 1px solid var(--dash-line); border-radius: 16px; background: var(--dash-surface); color: inherit; text-decoration: none; box-shadow: 0 10px 30px -24px rgba(15, 23, 42, .35); transition: transform .2s, box-shadow .2s; }
        a.dash-kpi:hover { transform: translateY(-2px); box-shadow: 0 16px 36px -22px rgba(15, 23, 42, .45); color: inherit; }
        .dash-kpi-icon { flex: none; display: grid; place-items: center; width: 44px; height: 44px; border-radius: 12px; background: var(--tone-bg); color: var(--tone); font-size: 1.4rem; }
        .dash-kpi-body { display: flex; flex-direction: column; min-width: 0; }
        .dash-kpi-label { font-size: .8rem; font-weight: 600; color: var(--dash-muted); }
        .dash-kpi-value { font-size: 1.75rem; font-weight: 800; line-height: 1.15; margin-top: .15rem; letter-spacing: -.02em; }
        .dash-kpi-note { margin-top: .3rem; font-size: .78rem; color: var(--dash-muted); }

        /* Sapaan operasional */
        .dash-greeting { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
        .dash-greeting h2 { margin: .2rem 0 .25rem; font-size: 1.5rem; font-weight: 800; }
        .dash-greeting p { margin: 0; color: var(--dash-muted); }
        .dash-date { font-size: .85rem; color: var(--dash-muted); padding: .45rem .9rem; border-radius: 999px; background: var(--dash-surface); border: 1px solid var(--dash-line); }

        /* Daftar tindakan */
        .dash-attention { display: flex; align-items: center; gap: .85rem; padding: .8rem .9rem; border-radius: 12px; color: inherit; text-decoration: none; border: 1px solid var(--dash-line); margin-bottom: .6rem; transition: background .2s; }
        a.dash-attention:hover { background: var(--tone-bg); color: inherit; }
        .dash-attention-icon { flex: none; display: grid; place-items: center; width: 38px; height: 38px; border-radius: 10px; background: var(--tone-bg); color: var(--tone); font-size: 1.2rem; }
        .dash-kpi-value, .dash-greeting h2, .dash-card-title, .dash-bar-label b, .dash-mini-stats b { color: var(--dash-ink); }
        .dash-attention-count { font-size: 1.25rem; font-weight: 800; color: var(--tone); }

        /* Bar horizontal */
        .dash-bar + .dash-bar { margin-top: .95rem; }
        .dash-bar-label { display: flex; justify-content: space-between; gap: .75rem; font-size: .88rem; margin-bottom: .35rem; }
        .dash-bar-label > span:first-child { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; }
        .dash-bar-label > :last-child { flex: none; color: var(--dash-muted); }
        .dash-bar-label b { color: var(--dash-ink); }
        .dash-bar-track { height: 9px; border-radius: 999px; background: var(--dash-track); overflow: hidden; }
        .dash-bar-track i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #7c3aed, #0a84ff); }
        .dash-bar-track.is-teal i { background: linear-gradient(90deg, #0d9488, #20d9ff); }
        .dash-bar-track.is-amber i { background: linear-gradient(90deg, #f59e0b, #f97316); }

        .dash-chart { position: relative; height: 320px; }
        .dash-chart-sm { height: 230px; }
        .dash-mini-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: .6rem; margin-bottom: 1rem; }
        .dash-mini-stats > div { padding: .7rem .85rem; border-radius: 12px; background: var(--dash-soft); }
        .dash-mini-stats span { display: block; font-size: .74rem; color: var(--dash-muted); }
        .dash-mini-stats b { font-size: 1.15rem; font-weight: 800; }

        .dash-table { font-size: .88rem; }
        .dash-table thead th { font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--dash-muted); border-bottom: 1px solid var(--dash-line); background: transparent; }
        .dash-table td { color: var(--dash-ink); background: transparent; vertical-align: middle; border-color: var(--dash-line); padding-top: .8rem; padding-bottom: .8rem; }
        .dash-table a { text-decoration: none; }

        /* Eksekutif */
        .dash-hero { position: relative; overflow: hidden; margin-bottom: 1.5rem; padding: 1.75rem 1.9rem; border-radius: 22px; color: #fff; background: radial-gradient(120% 140% at 100% 0%, #1e5aa8 0%, transparent 55%), linear-gradient(135deg, #071a2d 0%, #0b2545 55%, #13315c 100%); box-shadow: 0 30px 60px -36px rgba(7, 26, 45, .9); }
        .dash-hero::after { content: ''; position: absolute; inset: auto -60px -80px auto; width: 280px; height: 280px; border-radius: 50%; border: 1px solid rgba(32, 217, 255, .25); }
        .dash-hero .dash-eyebrow { color: #20d9ff; }
        .dash-hero-top { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
        .dash-hero-title { margin: .35rem 0 .35rem; font-size: 1.65rem; font-weight: 800; letter-spacing: -.01em; }
        .dash-hero-sub { margin: 0; max-width: 640px; color: rgba(255, 255, 255, .72); }
        .dash-hero-btn { color: #071a2d; background: #20d9ff; border-radius: 999px; font-weight: 700; padding: .6rem 1.15rem; }
        .dash-hero-btn:hover { background: #fff; color: #071a2d; }
        .dash-hero-kpis { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1px; margin-top: 1.6rem; border-radius: 16px; overflow: hidden; background: rgba(255, 255, 255, .12); }
        .dash-hero-kpi { padding: 1.1rem 1.2rem; background: rgba(7, 26, 45, .55); backdrop-filter: blur(8px); }
        .dash-hero-kpi span { display: block; font-size: .74rem; letter-spacing: .08em; text-transform: uppercase; color: rgba(255, 255, 255, .6); }
        .dash-hero-kpi b { display: block; margin: .3rem 0 .2rem; font-size: 1.85rem; font-weight: 800; letter-spacing: -.02em; }
        .dash-hero-kpi small { color: rgba(255, 255, 255, .7); font-size: .8rem; }
        .dash-hero-kpi .dash-trend.is-up { color: #34d399; }
        .dash-hero-kpi .dash-trend.is-down { color: #f87171; }

        .dash-highlights { list-style: none; margin: 0; padding: 0; display: grid; gap: .7rem; }
        .dash-highlights li { position: relative; padding: .75rem .9rem .75rem 2.4rem; border-radius: 12px; background: var(--tone-bg); color: var(--dash-ink); font-size: .92rem; line-height: 1.5; }
        .dash-highlights li::before { content: ''; position: absolute; left: .95rem; top: 1.1rem; width: 9px; height: 9px; border-radius: 50%; background: var(--tone); box-shadow: 0 0 0 4px color-mix(in srgb, var(--tone) 20%, transparent); }
        .dash-highlights b { color: var(--dash-ink); }
        .dash-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .75rem; border-radius: 999px; background: var(--tone-bg); color: var(--tone); font-size: .8rem; font-weight: 600; }

        @media (max-width: 576px) {
            .dash-hero { padding: 1.35rem 1.2rem; }
            .dash-hero-title { font-size: 1.3rem; }
            .dash-hero-kpi b, .dash-kpi-value { font-size: 1.5rem; }
            .dash-chart { height: 260px; }
            .dash-card .card-body { padding: 1.1rem; }
        }
    </style>
@endsection
