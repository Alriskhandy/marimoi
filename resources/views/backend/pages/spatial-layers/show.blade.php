@extends('backend.partials.main', ['title' => 'Detail Layer'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-layers"></i></span>
            Detail Layer: {{ $layer->name }}
        </h3>
        <div>
            <a href="{{ route('spatial-layers.edit', $layer) }}" class="btn btn-outline-warning">
                <i class="mdi mdi-pencil"></i> Kelola
            </a>
            <a href="{{ route('spatial-layers.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">Informasi Layer</p>
                    <table class="table table-sm">
                        <tr>
                            <th style="width:200px;">Nama</th>
                            <td>{{ $layer->name }}</td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td>{{ $layer->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Layer Induk</th>
                            <td>
                                @if ($layer->parent)
                                    <a href="{{ route('spatial-layers.show', $layer->parent) }}">{{ $layer->parent->name }}</a>
                                @else
                                    <span class="text-muted">Tidak ada (akar)</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Kelas Layer</th>
                            <td>{{ ucfirst($layer->layer_class) }}</td>
                        </tr>
                        <tr>
                            <th>Style</th>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($layer->color)
                                        <span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:{{ $layer->color }};border:1px solid #dee2e6;"></span>
                                        <small>{{ $layer->color }}</small>
                                    @endif
                                    @if ($layer->is_marker && $layer->icon)
                                        <i class="{{ $layer->icon }}" style="color:{{ $layer->color ?? '#007bff' }};font-size:1.3em;"></i>
                                    @endif
                                    <span class="badge {{ $layer->is_marker ? 'bg-warning text-dark' : 'bg-info text-white' }}">
                                        {{ $layer->is_marker ? 'Marker' : 'Layer' }}
                                    </span>
                                    <small class="text-muted">Opacity: {{ $layer->opacity }}</small>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if ($layer->is_active)
                                    <span class="badge bg-success text-white">Aktif</span>
                                @else
                                    <span class="badge bg-secondary text-white">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <p class="card-title">Jenis: {{ $layer->mapType?->nama ?? '-' }}</p>
                    @if ($layer->mapType)
                        <table class="table table-sm">
                            <tr>
                                <th style="width:200px;">Sumber Data</th>
                                <td>{{ $layer->mapType->sumber_data ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>OPD Penanggung Jawab</th>
                                <td>{{ $layer->mapType->opdPenanggungJawab?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Tahun/Tanggal Data</th>
                                <td>{{ $layer->mapType->tanggal_data?->format('d M Y') ?? '-' }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">Layer ini belum memiliki Jenis.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">Layer Anak ({{ $layer->children->count() }})</p>
                    <ul class="list-group mb-3">
                        @forelse ($layer->children as $child)
                            <li class="list-group-item">
                                <a href="{{ route('spatial-layers.show', $child) }}">{{ $child->name }}</a>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">Tidak ada Layer anak.</li>
                        @endforelse
                    </ul>

                    <p class="card-title">Data Spasial ({{ $layer->features->count() }})</p>
                    <ul class="list-group">
                        @forelse ($layer->features as $feature)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>#{{ $feature->id }} — {{ $feature->external_id ?? 'Tanpa ID eksternal' }}</span>
                                <a href="{{ route('spatial-layers.features.edit', [$layer, $feature]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="mdi mdi-pencil"></i>
                                </a>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">Belum ada Data Spasial.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
