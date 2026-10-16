@php
    use App\Support\DashboardMetrics as M;

    $isOpd = $profile === M::PROFILE_OPD;
    $statusTone = ['pending' => 'warning', 'diproses' => 'info', 'selesai' => 'success', 'published' => 'success', 'draft' => 'warning', 'archived' => 'secondary'];
    $statusLabel = ['published' => 'Terbit', 'draft' => 'Draft', 'archived' => 'Arsip'];
    $canAspirasi = auth()->user()->can('aspirasi.view');
    $canLayers = auth()->user()->can('spatial-layers.view');
@endphp

<div class="dash-greeting">
    <div>
        <span class="dash-eyebrow">{{ $isOpd ? 'Dashboard OPD · '.(auth()->user()->opd->name ?? 'OPD') : 'Dashboard Operasional' }}</span>
        <h2>Halo, {{ auth()->user()->name }}</h2>
        <p>{{ $isOpd ? 'Ringkasan data, aspirasi, dan proyek yang dikelola OPD Anda.' : 'Pantau kualitas data, layanan aspirasi, dan jangkauan publik MARIMOI.' }}</p>
    </div>
    <span class="dash-date"><i class="mdi mdi-calendar-blank-outline"></i> {{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
</div>

<div class="dash-kpis mb-4">
    @include('backend.pages.dashboard.kpi', [
        'label' => $isOpd ? 'Layer OPD terbit' : 'Layer terbit',
        'value' => M::number($spatial['published']),
        'note' => M::number($spatial['draft']).' draft · '.M::number($spatial['publishedRate'], 1).'% terbit',
        'icon' => 'mdi-layers-outline', 'tone' => 'primary',
        'url' => $canLayers ? route('spatial-layers.index') : null,
    ])
    @include('backend.pages.dashboard.kpi', [
        'label' => $isOpd ? 'Fitur yang Anda input' : 'Fitur spasial',
        'value' => M::number($spatial['features']),
        'note' => M::number($spatial['updatedRecently']).' layer diperbarui 30 hari terakhir',
        'icon' => 'mdi-vector-polygon', 'tone' => 'info',
    ])
    @include('backend.pages.dashboard.kpi', [
        'label' => 'Aspirasi pending',
        'value' => M::number($aspirasi['pending']),
        'note' => $aspirasi['overdue'] ? M::number($aspirasi['overdue']).' lewat '.M::OVERDUE_DAYS.' hari' : 'Tidak ada yang terlambat',
        'icon' => 'mdi-email-alert-outline', 'tone' => $aspirasi['overdue'] ? 'danger' : 'warning',
        'url' => $canAspirasi ? route('aspirasi.index') : null,
    ])
    @include('backend.pages.dashboard.kpi', [
        'label' => 'Penyelesaian aspirasi',
        'value' => $aspirasi['total'] ? M::number($aspirasi['completionRate'], 1).'%' : '—',
        'note' => $aspirasi['avgResponseDays'] !== null ? 'Respon rata-rata '.M::number($aspirasi['avgResponseDays'], 1).' hari' : 'Belum ada respon tercatat',
        'icon' => 'mdi-check-decagram-outline', 'tone' => 'success',
    ])
    @if ($isOpd)
        @include('backend.pages.dashboard.kpi', [
            'label' => 'Proyek OPD',
            'value' => M::number($projects['count']),
            'note' => M::rupiah($projects['budget']).' anggaran',
            'icon' => 'mdi-hammer-wrench', 'tone' => 'primary',
        ])
    @else
        @include('backend.pages.dashboard.kpi', [
            'label' => 'Kunjungan 30 hari',
            'value' => M::number($reach['last30']),
            'trend' => $reach['growth'],
            'note' => $reach['growth'] !== null ? 'vs 30 hari sebelumnya' : M::number($reach['today']).' hari ini',
            'icon' => 'mdi-account-group-outline', 'tone' => 'primary',
            'url' => auth()->user()->can('visitors.view') ? route('visitors.index') : null,
        ])
    @endif
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4">
        @include('backend.pages.dashboard.attention')
    </div>
    <div class="col-xl-8">
        <div class="card dash-card h-100">
            <div class="card-body">
                <div class="dash-card-head">
                    <h4 class="dash-card-title"><i class="mdi mdi-chart-line"></i> Tren Aspirasi</h4>
                    <span class="dash-card-meta">
                        {{ M::number($aspirasi['thisMonth']) }} bulan ini · {{ M::number($aspirasi['lastMonth']) }} bulan lalu
                    </span>
                </div>
                <div class="dash-chart"><canvas id="chartAspirasi"></canvas></div>
            </div>
        </div>
    </div>
</div>

@unless ($isOpd)
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card dash-card h-100">
                <div class="card-body">
                    <div class="dash-card-head">
                        <h4 class="dash-card-title"><i class="mdi mdi-chart-areaspline"></i> Kunjungan Publik</h4>
                        <span class="dash-card-meta">{{ M::number($reach['unique30']) }} pengunjung unik · 30 hari</span>
                    </div>
                    <div class="dash-chart"><canvas id="chartVisitors"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card dash-card h-100">
                <div class="card-body">
                    <div class="dash-card-head">
                        <h4 class="dash-card-title"><i class="mdi mdi-fire"></i> Halaman Terpopuler</h4>
                        <span class="dash-card-meta">30 hari</span>
                    </div>
                    @php($maxPage = max(1, (int) $topPages->max('total')))
                    @forelse ($topPages as $page)
                        <div class="dash-bar">
                            <div class="dash-bar-label"><span class="text-truncate" title="{{ $page->page }}">{{ \Illuminate\Support\Str::of($page->page)->after('://')->after('/')->start('/')->limit(40) }}</span><b>{{ M::number($page->total) }}</b></div>
                            <div class="dash-bar-track is-amber"><i style="width: {{ max(2, $page->total / $maxPage * 100) }}%"></i></div>
                        </div>
                    @empty
                        <div class="dash-empty"><i class="mdi mdi-information-outline"></i><span>Belum ada kunjungan 30 hari terakhir.</span></div>
                    @endforelse
                    <div class="dash-mini-stats mt-3">
                        <div><span>Publikasi</span><b>{{ M::number($reach['publications']) }}</b></div>
                        <div><span>Unduhan</span><b>{{ M::number($reach['downloads']) }}</b></div>
                        <div><span>Peta dibagikan</span><b>{{ M::number($reach['sharedMaps']) }}</b></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endunless

<div class="row g-4 mb-4">
    <div class="{{ $isOpd ? 'col-xl-6' : 'col-xl-8' }}">
        <div class="card dash-card h-100">
            <div class="card-body">
                <div class="dash-card-head">
                    <h4 class="dash-card-title"><i class="mdi mdi-update"></i> Layer Terakhir Diperbarui</h4>
                    @if ($canLayers)
                        <a href="{{ route('spatial-layers.index') }}" class="dash-card-link">Semua layer <i class="mdi mdi-arrow-right"></i></a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table dash-table mb-0">
                        <thead><tr><th>Layer</th>@unless ($isOpd)<th>OPD</th>@endunless<th class="text-end">Fitur</th><th>Status</th><th class="text-end">Diperbarui</th></tr></thead>
                        <tbody>
                            @forelse ($recentLayers as $layer)
                                <tr>
                                    <td class="fw-semibold">
                                        @if ($canLayers)
                                            <a href="{{ route('spatial-layers.show', $layer->id) }}">{{ \Illuminate\Support\Str::limit($layer->name, 48) }}</a>
                                        @else
                                            {{ \Illuminate\Support\Str::limit($layer->name, 48) }}
                                        @endif
                                    </td>
                                    @unless ($isOpd)<td class="text-muted">{{ $layer->opd_name ?? '—' }}</td>@endunless
                                    <td class="text-end">{{ M::number($layer->feature_count) }}</td>
                                    <td><span class="dash-status dash-tone-{{ $statusTone[$layer->status] ?? 'secondary' }}">{{ $statusLabel[$layer->status] ?? ucfirst($layer->status) }}</span></td>
                                    <td class="text-end text-muted text-nowrap">{{ \Illuminate\Support\Carbon::parse($layer->updated_at)->locale('id')->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada layer.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @unless ($isOpd)
        <div class="col-xl-4">
            <div class="card dash-card h-100">
                <div class="card-body">
                    <div class="dash-card-head">
                        <h4 class="dash-card-title"><i class="mdi mdi-layers-triple-outline"></i> Layer Terbit per Kategori</h4>
                    </div>
                    @php($maxCategory = max(1, (int) $topCategories->max('total')))
                    @forelse ($topCategories as $category)
                        <div class="dash-bar">
                            <div class="dash-bar-label"><span>{{ $category->name }}</span><b>{{ M::number($category->total) }}</b></div>
                            <div class="dash-bar-track is-teal"><i style="width: {{ max(2, $category->total / $maxCategory * 100) }}%"></i></div>
                        </div>
                    @empty
                        <div class="dash-empty"><i class="mdi mdi-information-outline"></i><span>Belum ada layer terbit.</span></div>
                    @endforelse
                </div>
            </div>
        </div>
    @endunless

    <div class="{{ $isOpd ? 'col-xl-6' : 'col-12' }}">
        <div class="card dash-card h-100">
            <div class="card-body">
                <div class="dash-card-head">
                    <h4 class="dash-card-title"><i class="mdi mdi-email-outline"></i> Aspirasi Terbaru</h4>
                    @if ($canAspirasi)
                        <a href="{{ route('aspirasi.index') }}" class="dash-card-link">Semua aspirasi <i class="mdi mdi-arrow-right"></i></a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table dash-table mb-0">
                        <thead><tr><th>Tiket</th><th>Judul</th>@unless ($isOpd)<th>Kategori</th>@endunless<th>Status</th><th class="text-end">Masuk</th></tr></thead>
                        <tbody>
                            @forelse ($recentAspirasi as $item)
                                <tr>
                                    <td class="text-nowrap">
                                        @if ($canAspirasi)
                                            <a href="{{ route('aspirasi.show', $item->id) }}" class="font-monospace">{{ $item->nomor_tiket }}</a>
                                        @else
                                            <span class="font-monospace">{{ $item->nomor_tiket }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-semibold d-block">{{ \Illuminate\Support\Str::limit($item->judul_aspirasi, 50) }}</span>
                                        <small class="text-muted">{{ $item->nama_pengirim }}</small>
                                    </td>
                                    @unless ($isOpd)<td class="text-muted">{{ $item->nama_kategori ?? '—' }}</td>@endunless
                                    <td><span class="dash-status dash-tone-{{ $statusTone[$item->status] ?? 'secondary' }}">{{ ucfirst($item->status) }}</span></td>
                                    <td class="text-end text-muted text-nowrap">{{ \Illuminate\Support\Carbon::parse($item->created_at)->locale('id')->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada aspirasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($isOpd && $sectors->isNotEmpty())
    @php($maxSectorBudget = max(1, (float) $sectors->max('budget')))
    <div class="card dash-card mb-4">
        <div class="card-body">
            <div class="dash-card-head">
                <h4 class="dash-card-title"><i class="mdi mdi-cash-multiple"></i> Proyek OPD per Sektor</h4>
                <span class="dash-card-meta">{{ M::number($projects['active']) }} berjalan · {{ M::rupiah($projects['budget']) }}</span>
            </div>
            @foreach ($sectors as $sector)
                <div class="dash-bar">
                    <div class="dash-bar-label"><span>{{ $sector->name }}</span><span><b>{{ M::rupiah($sector->budget) }}</b> · {{ M::number($sector->total) }} proyek</span></div>
                    <div class="dash-bar-track"><i style="width: {{ max(2, $sector->budget / $maxSectorBudget * 100) }}%"></i></div>
                </div>
            @endforeach
        </div>
    </div>
@endif
