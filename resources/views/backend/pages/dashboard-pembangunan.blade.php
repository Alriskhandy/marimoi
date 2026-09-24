@extends('backend.partials.main')

@section('main')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-chart-line"></i></span>
        Dashboard Pembangunan
    </h3>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-auto">
        <select name="tahun" class="form-select" onchange="this.form.submit()">
            @foreach ($tahunOptions as $t)
                <option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    @if ($opdOptions->isNotEmpty())
        <div class="col-auto">
            <select name="opd_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua OPD</option>
                @foreach ($opdOptions as $opd)
                    <option value="{{ $opd->id }}" @selected($opdId == $opd->id)>{{ $opd->singkatan }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-auto">
        <select name="kategori_id" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Sektor</option>
            @foreach ($kategoriOptions as $kategori)
                <option value="{{ $kategori->id }}" @selected($kategoriId == $kategori->id)>{{ $kategori->nama }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            @foreach (\App\Models\ProjectProgressReport::STATUSES as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="row">
    <div class="col-md-4 stretch-card grid-margin">
        <div class="card bg-gradient-primary card-img-holder text-white">
            <div class="card-body">
                <h6 class="font-weight-normal">Proyek Terdaftar</h6>
                <h2 class="mb-2">{{ $cards['jumlah_proyek'] }}</h2>
                <small>{{ $cards['jumlah_dilaporkan'] }} sudah melapor tahun {{ $tahun }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 stretch-card grid-margin">
        <div class="card bg-gradient-info card-img-holder text-white">
            <div class="card-body">
                <h6 class="font-weight-normal">Realisasi Anggaran</h6>
                <h2 class="mb-2">{{ $cards['persen_realisasi'] }}%</h2>
                <small>Rp {{ number_format($cards['total_realisasi'], 0, ',', '.') }} / Rp {{ number_format($cards['total_pagu'], 0, ',', '.') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 stretch-card grid-margin">
        <div class="card bg-gradient-success card-img-holder text-white">
            <div class="card-body">
                <h6 class="font-weight-normal">Rata-rata Progres Fisik</h6>
                <h2 class="mb-2">{{ $cards['rata_progres_fisik'] }}%</h2>
                <small>{{ $cards['jumlah_bermasalah'] }} proyek berstatus terlambat</small>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h6 class="card-title">Rata-rata Progres Fisik per Sektor</h6>
        <canvas id="progresSektorChart" height="80"></canvas>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Proyek</th>
                        <th>OPD</th>
                        <th>Sektor</th>
                        <th>Periode</th>
                        <th>Pagu</th>
                        <th>Realisasi</th>
                        <th>Progres Fisik</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporan as $item)
                        <tr>
                            <td>{{ $item->dataSpatial?->deskripsi }}</td>
                            <td>{{ $item->opd?->singkatan ?? '-' }}</td>
                            <td>{{ $item->kategori?->nama ?? '-' }}</td>
                            <td>{{ $item->periode_laporan }} {{ $item->tahun_anggaran }}</td>
                            <td>Rp {{ number_format($item->pagu ?? 0, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->realisasi_anggaran ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $item->progres_fisik_persen }}%</td>
                            <td>{{ $item->status }}</td>
                            <td>
                                @if ($item->dataSpatial)
                                    <a href="{{ route('project-progress.show', $item->dataSpatial->uuid) }}">Detail</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">Belum ada laporan progres untuk filter yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    new Chart(document.getElementById('progresSektorChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: @json($progresPerSektor->keys()),
            datasets: [{
                label: 'Progres Fisik (%)',
                data: @json($progresPerSektor->values()),
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
</script>
@endsection
