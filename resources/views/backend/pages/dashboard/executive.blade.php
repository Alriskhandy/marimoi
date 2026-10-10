@php
    use App\Support\DashboardMetrics as M;

    $maxSectorBudget = max(1, (float) $sectors->max('budget'));
    $scorecard = $opdScorecard->filter(fn ($row) => $row->layers || $row->projects || $row->aspirasi);
    $maxOpdBudget = max(1, (float) $scorecard->max('budget'));
@endphp

{{-- Hero eksekutif: identitas, tanggal, dan 4 indikator utama. --}}
<section class="dash-hero">
    <div class="dash-hero-top">
        <div>
            <span class="dash-eyebrow">Ringkasan Eksekutif · MARIMOI</span>
            <h2 class="dash-hero-title">Selamat datang, {{ auth()->user()->name }}</h2>
            <p class="dash-hero-sub">Gambaran pembangunan, data spasial, dan layanan publik Provinsi Maluku Utara per {{ now()->locale('id')->translatedFormat('l, d F Y') }}.</p>
        </div>
        <a href="{{ route('tampil.interaktif') }}" target="_blank" rel="noopener" class="btn dash-hero-btn">
            <i class="mdi mdi-map-search-outline"></i> Buka Peta Interaktif
        </a>
    </div>

    <div class="dash-hero-kpis">
        <div class="dash-hero-kpi">
            <span>Anggaran pembangunan</span>
            <b>{{ M::rupiah($projects['budget']) }}</b>
            <small>{{ M::number($projects['count']) }} proyek{{ $projects['latestYear'] ? ' · s.d. TA '.$projects['latestYear'] : '' }}</small>
        </div>
        <div class="dash-hero-kpi">
            <span>Proyek berjalan</span>
            <b>{{ M::number($projects['active']) }}</b>
            <small>{{ $projects['avgProgress'] !== null ? 'Progres fisik rata-rata '.M::number($projects['avgProgress'], 1).'%' : 'Belum ada laporan progres' }}</small>
        </div>
        <div class="dash-hero-kpi">
            <span>Layer data terbit</span>
            <b>{{ M::number($spatial['published']) }}</b>
            <small>{{ M::number($spatial['features']) }} fitur spasial</small>
        </div>
        <div class="dash-hero-kpi">
            <span>Penyelesaian aspirasi</span>
            <b>{{ $aspirasi['total'] ? M::number($aspirasi['completionRate'], 1).'%' : '—' }}</b>
            <small>{{ M::number($aspirasi['selesai']) }} dari {{ M::number($aspirasi['total']) }} aspirasi</small>
        </div>
        <div class="dash-hero-kpi">
            <span>Kunjungan 30 hari</span>
            <b>{{ M::number($reach['last30']) }}</b>
            <small>
                @if ($reach['growth'] !== null)
                    <span class="dash-trend {{ $reach['growth'] >= 0 ? 'is-up' : 'is-down' }}"><i class="mdi {{ $reach['growth'] >= 0 ? 'mdi-arrow-up' : 'mdi-arrow-down' }}"></i>{{ M::number(abs($reach['growth']), 1) }}%</span>
                    vs 30 hari sebelumnya
                @else
                    {{ M::number($reach['unique30']) }} pengunjung unik
                @endif
            </small>
        </div>
    </div>
</section>

<div class="row g-4 mb-4">
    {{-- Sorotan otomatis --}}
    <div class="col-xl-5">
        <div class="card dash-card h-100">
            <div class="card-body">
                <div class="dash-card-head">
                    <h4 class="dash-card-title"><i class="mdi mdi-lightbulb-on-outline"></i> Sorotan Utama</h4>
                </div>
                <ul class="dash-highlights">
                    @foreach ($highlights as $item)
                        <li class="dash-tone-{{ $item['tone'] }}">{!! $item['text'] !!}</li>
                    @endforeach
                </ul>

                @if ($attention)
                    <div class="dash-subhead">Perlu perhatian</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($attention as $item)
                            <span class="dash-chip dash-tone-{{ $item['tone'] }}"><i class="mdi {{ $item['icon'] }}"></i>{{ $item['label'] }} <b>{{ M::number($item['count']) }}</b></span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Anggaran per sektor --}}
    <div class="col-xl-7">
        <div class="card dash-card h-100">
            <div class="card-body">
                <div class="dash-card-head">
                    <h4 class="dash-card-title"><i class="mdi mdi-cash-multiple"></i> Alokasi Anggaran per Sektor</h4>
                    <span class="dash-card-meta">Total {{ M::rupiah($projects['budget']) }}</span>
                </div>
                @forelse ($sectors as $sector)
                    <div class="dash-bar">
                        <div class="dash-bar-label">
                            <span>{{ $sector->name }}</span>
                            <span><b>{{ M::rupiah($sector->budget) }}</b> · {{ M::number($sector->total) }} proyek</span>
                        </div>
                        <div class="dash-bar-track"><i style="width: {{ max(2, $sector->budget / $maxSectorBudget * 100) }}%"></i></div>
                    </div>
                @empty
                    <div class="dash-empty"><i class="mdi mdi-information-outline"></i><span>Belum ada data proyek pembangunan.</span></div>
                @endforelse

                @if ($projects['count'] > 0)
                    @php($unsectored = $sectors->firstWhere('name', 'Tanpa sektor'))
                    @if ($unsectored)
                        <div class="dash-note mt-3"><i class="mdi mdi-information-outline"></i> {{ M::number($unsectored->total) }} proyek belum dikaitkan ke sektor pembangunan.</div>
                    @endif
                    <div class="dash-subhead">Portofolio proyek</div>
                    <div class="dash-mini-stats mb-0">
                        <div><span>Rata-rata / proyek</span><b>{{ M::rupiah($projects['budget'] / $projects['count']) }}</b></div>
                        <div><span>Berjalan</span><b>{{ M::number($projects['active']) }}</b></div>
                        <div><span>Selesai</span><b>{{ M::number($projects['completed']) }}</b></div>
                        <div><span>Realisasi</span><b>{{ $projects['realisasi'] ? M::rupiah($projects['realisasi']) : '—' }}</b></div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card dash-card h-100">
            <div class="card-body">
                <div class="dash-card-head">
                    <h4 class="dash-card-title"><i class="mdi mdi-calendar-range"></i> Proyek per Tahun Anggaran</h4>
                </div>
                <div class="dash-chart"><canvas id="chartProjectsYear"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card dash-card h-100">
            <div class="card-body">
                <div class="dash-card-head">
                    <h4 class="dash-card-title"><i class="mdi mdi-account-group-outline"></i> Jangkauan Publik</h4>
                    <span class="dash-card-meta">12 bulan terakhir</span>
                </div>
                <div class="dash-mini-stats">
                    <div><span>Total kunjungan</span><b>{{ M::number($reach['total']) }}</b></div>
                    <div><span>Publikasi</span><b>{{ M::number($reach['publications']) }}</b></div>
                    <div><span>Unduhan publikasi</span><b>{{ M::number($reach['downloads']) }}</b></div>
                    <div><span>Peta dibagikan</span><b>{{ M::number($reach['sharedMaps']) }}</b></div>
                </div>
                <div class="dash-chart dash-chart-sm"><canvas id="chartVisitors"></canvas></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card dash-card h-100">
            <div class="card-body">
                <div class="dash-card-head">
                    <h4 class="dash-card-title"><i class="mdi mdi-domain"></i> Rapor Kinerja OPD</h4>
                    <span class="dash-card-meta">{{ $scorecard->count() }} OPD aktif</span>
                </div>
                <div class="table-responsive">
                    <table class="table dash-table mb-0">
                        <thead>
                            <tr>
                                <th>OPD</th>
                                <th class="text-end">Layer terbit</th>
                                <th class="text-end">Proyek</th>
                                <th style="min-width: 180px">Anggaran</th>
                                <th class="text-end">Aspirasi selesai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($scorecard as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->name }}</td>
                                    <td class="text-end">{{ M::number($row->layers) }}</td>
                                    <td class="text-end">{{ M::number($row->projects) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="dash-bar-track flex-grow-1"><i style="width: {{ $row->budget ? max(2, $row->budget / $maxOpdBudget * 100) : 0 }}%"></i></div>
                                            <small class="text-nowrap">{{ $row->budget ? M::rupiah($row->budget) : '-' }}</small>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @if ($row->completionRate !== null)
                                            <span class="dash-status dash-tone-{{ $row->completionRate >= 80 ? 'success' : ($row->completionRate >= 50 ? 'warning' : 'danger') }}">{{ M::number($row->completionRate, 0) }}%</span>
                                            <small class="text-muted d-block">{{ $row->selesai }}/{{ $row->aspirasi }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada layer, proyek, atau aspirasi yang terhubung ke OPD.<br><small>Lengkapi OPD pengelola pada layer &amp; proyek agar rapor terisi.</small></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card dash-card h-100">
            <div class="card-body">
                <div class="dash-card-head">
                    <h4 class="dash-card-title"><i class="mdi mdi-layers-triple-outline"></i> Cakupan Data per Kategori</h4>
                </div>
                @php($maxCategory = max(1, (int) $topCategories->max('total')))
                @forelse ($topCategories as $category)
                    <div class="dash-bar">
                        <div class="dash-bar-label"><span>{{ $category->name }}</span><b>{{ M::number($category->total) }} layer</b></div>
                        <div class="dash-bar-track is-teal"><i style="width: {{ max(2, $category->total / $maxCategory * 100) }}%"></i></div>
                    </div>
                @empty
                    <div class="dash-empty"><i class="mdi mdi-information-outline"></i><span>Belum ada layer terbit.</span></div>
                @endforelse
            </div>
        </div>
    </div>
</div>

