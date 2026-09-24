@extends('backend.partials.main')

@section('main')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-clipboard-text"></i></span>
        Progres Proyek Strategis
    </h3>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            @if ($opdOptions->isNotEmpty())
                <div class="col-auto">
                    <select name="opd_pengelola_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua OPD</option>
                        @foreach ($opdOptions as $opd)
                            <option value="{{ $opd->id }}" @selected(request('opd_pengelola_id') == $opd->id)>{{ $opd->singkatan }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-auto">
                <select name="sub_type" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Jenis</option>
                    <option value="psd" @selected(request('sub_type') === 'psd')>Proyek Strategis Daerah</option>
                    <option value="psn" @selected(request('sub_type') === 'psn')>Proyek Strategis Nasional</option>
                </select>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Proyek</th>
                        <th>OPD</th>
                        <th>Sektor</th>
                        <th>Laporan Terakhir</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proyek as $item)
                        @php($latest = $item->progressReports->first())
                        <tr>
                            <td>{{ $item->deskripsi }}</td>
                            <td>{{ $item->opdPengelola?->singkatan ?? '-' }}</td>
                            <td>{{ $item->kategori?->nama ?? '-' }}</td>
                            <td>
                                @if ($latest)
                                    {{ $latest->periode_laporan }} {{ $latest->tahun_anggaran }} — {{ $latest->progres_fisik_persen }}%
                                @else
                                    Belum ada laporan
                                @endif
                            </td>
                            <td>{{ $latest?->status ?? '-' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('project-progress.show', $item->uuid) }}" class="btn btn-sm btn-outline-primary">Histori</a>
                                @can('project-progress.create')
                                    <a href="{{ route('project-progress.create', $item->uuid) }}" class="btn btn-sm btn-primary">Tambah Laporan</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                Belum ada proyek strategis yang tercatat{{ $opdOptions->isEmpty() ? ' untuk OPD Anda' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $proyek->links() }}
    </div>
</div>
@endsection
