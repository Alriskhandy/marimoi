@extends('backend.partials.main')

@section('main')
<div class="page-header">
    <h3 class="page-title">Histori Progres — {{ $proyek->deskripsi }}</h3>
</div>

@can('project-progress.create')
    <a href="{{ route('project-progress.create', $proyek->uuid) }}" class="btn btn-primary mb-3">Tambah Laporan</a>
@endcan

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Pagu</th>
                        <th>Realisasi</th>
                        <th>% Realisasi</th>
                        <th>Progres Fisik</th>
                        <th>Status</th>
                        <th>Dilaporkan Oleh</th>
                        <th>Catatan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporan as $item)
                        <tr>
                            <td>
                                {{ $item->periode_laporan }} {{ $item->tahun_anggaran }}
                                @if ($item->revisions->isNotEmpty())
                                    <span class="badge bg-info text-white" title="Terakhir diperbarui {{ $item->revisions->first()->created_at?->diffForHumans() }} oleh {{ $item->revisions->first()->diperbaruiOleh?->name ?? '-' }}">
                                        Diperbarui {{ $item->revisions->count() }}×
                                    </span>
                                @endif
                            </td>
                            <td>Rp {{ number_format($item->pagu ?? 0, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->realisasi_anggaran ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $item->pagu > 0 ? round($item->realisasi_anggaran / $item->pagu * 100, 1) : 0 }}%</td>
                            <td>{{ $item->progres_fisik_persen }}%</td>
                            <td>{{ $item->status }}</td>
                            <td>{{ $item->pelapor?->name ?? '-' }}</td>
                            <td>{{ $item->catatan }}</td>
                            <td>
                                @can('project-progress.edit')
                                    <a href="{{ route('project-progress.laporan.edit', [$proyek->uuid, $item->id]) }}" class="btn btn-sm btn-outline-secondary">Perbarui</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">Belum ada laporan progres untuk proyek ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
