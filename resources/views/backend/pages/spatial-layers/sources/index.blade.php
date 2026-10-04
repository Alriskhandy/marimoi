@extends('backend.partials.main', ['title' => 'Sumber Data Layer'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-cloud-outline"></i></span>
            Sumber Data: {{ $layer->name }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('spatial-layers.index') }}">Daftar Layer & Data</a></li>
                <li class="breadcrumb-item"><a href="{{ route('spatial-layers.show', $layer) }}">{{ $layer->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sumber Data</li>
            </ul>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="card-title mb-0">
                            Layanan Eksternal (WMS/WMTS/WFS/XYZ/ArcGIS/dll.) — layer bertipe service/raster dirender
                            langsung dari sini, tanpa Data Spasial tersimpan.
                        </p>
                        <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#addSourceModal">
                            <i class="mdi mdi-plus"></i> Tambah Source
                        </button>
                    </div>

                    <div class="alert alert-warning">
                        <i class="mdi mdi-shield-alert-outline me-2"></i>
                        <strong>Jangan masukkan kredensial mentah</strong> (password/API key sungguhan) di field apa pun
                        di halaman ini. Field "Referensi Kredensial" hanya untuk MENYEBUT nama entri di secret
                        store/env server (R11) — bukan tempat menyimpan kredensial itu sendiri.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Primary</th>
                                    <th>Jenis</th>
                                    <th>Nama</th>
                                    <th>URL</th>
                                    <th>Status Koneksi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sources as $source)
                                    <tr>
                                        <td>
                                            @if ($source->is_primary)
                                                <span class="badge bg-success text-white">Primary</span>
                                            @endif
                                        </td>
                                        <td>{{ $sourceTypes[$source->source_type] ?? $source->source_type }}</td>
                                        <td>{{ $source->name ?? '-' }}</td>
                                        <td><small class="text-muted">{{ \Illuminate\Support\Str::limit($source->url, 60) }}</small></td>
                                        <td>
                                            @php
                                                $healthBadge = ['ok' => 'success', 'error' => 'danger', 'unknown' => 'secondary'][$source->health_status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $healthBadge }} text-white">{{ ucfirst($source->health_status) }}</span>
                                            @if ($source->last_checked_at)
                                                <br><small class="text-muted">{{ $source->last_checked_at->format('d/m/Y H:i') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <form action="{{ route('spatial-layers.sources.test-connection', [$layer, $source]) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-info" title="Uji Koneksi">
                                                        <i class="mdi mdi-lan-connect"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-warning" title="Edit"
                                                    data-bs-toggle="modal" data-bs-target="#editSourceModal{{ $loop->index }}">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <form action="{{ route('spatial-layers.sources.destroy', [$layer, $source]) }}" method="POST"
                                                    data-confirm="delete" data-name="{{ $source->name ?? $source->source_type }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            Belum ada Source untuk Layer ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambah Source Modal -->
    <div class="modal fade" id="addSourceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('spatial-layers.sources.store', $layer) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="mdi mdi-plus"></i> Tambah Source</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @include('backend.pages.spatial-layers.sources._form', ['source' => null, 'sourceTypes' => $sourceTypes])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-gradient-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Source Modals -->
    @foreach ($sources as $source)
        <div class="modal fade" id="editSourceModal{{ $loop->index }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('spatial-layers.sources.update', [$layer, $source]) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="mdi mdi-pencil"></i> Edit Source</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @include('backend.pages.spatial-layers.sources._form', ['source' => $source, 'sourceTypes' => $sourceTypes])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-gradient-warning">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
