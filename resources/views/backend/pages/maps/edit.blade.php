@extends('backend.partials.main', ['title' => 'Kelola Peta: '.$map->title])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-layers"></i></span>
            {{ $map->title }}
        </h3>
        <a href="{{ route('maps.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('shareToken'))
        <div class="alert alert-warning">
            <strong>Link berbagi dibuat.</strong> Salin sekarang — token tidak bisa ditampilkan lagi setelah halaman ini ditutup.<br>
            <code>{{ session('shareUrl') }}</code>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">Layer di Peta Ini ({{ $map->layers->count() }})</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Layer</th>
                                    <th>Opacity</th>
                                    <th>Tampil</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($map->layers as $mapLayer)
                                    <tr>
                                        <td>{{ $mapLayer->display_name ?? $mapLayer->spatialLayer?->name }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('maps.layers.update', [$map, $mapLayer]) }}" class="d-flex gap-1">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" name="opacity" step="0.1" min="0" max="1" value="{{ $mapLayer->opacity }}" class="form-control form-control-sm" style="width:5rem">
                                                <input type="hidden" name="is_visible" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Simpan</button>
                                            </form>
                                        </td>
                                        <td>{{ $mapLayer->is_visible ? 'Ya' : 'Tidak' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('maps.layers.destroy', [$map, $mapLayer]) }}" onsubmit="return confirm('Hapus layer ini dari peta?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="mdi mdi-delete"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Belum ada layer.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <hr>

                    <form method="POST" action="{{ route('maps.layers.store', $map) }}" class="d-flex gap-2">
                        @csrf
                        <select name="spatial_layer_id" class="form-select" required>
                            <option value="">Pilih layer untuk ditambahkan...</option>
                            @foreach ($availableLayers as $layer)
                                <option value="{{ $layer->id }}">{{ $layer->title ?? $layer->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-gradient-primary">Tambah</button>
                    </form>
                    @if ($availableLayers->isEmpty())
                        <small class="text-muted d-block mt-2">
                            Tidak ada layer aktif tersisa untuk ditambahkan. Layer yang tersedia berasal dari
                            <code>spatial_layers</code> (snapshot backfill kategori) — kategori baru yang belum
                            tersinkron tidak akan muncul di sini.
                        </small>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="card-title mb-0">Publikasi & Berbagi</p>
                        <form method="POST" action="{{ route('maps.publish', $map) }}">
                            @csrf
                            <button type="submit" class="btn btn-gradient-success">
                                <i class="mdi mdi-publish"></i> Terbitkan
                            </button>
                        </form>
                    </div>

                    @forelse ($map->publications as $publication)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Revisi #{{ $publication->revision }}</strong>
                                    @if ($publication->is_current)
                                        <span class="badge bg-success text-white">Aktif</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">
                                        Diterbitkan {{ $publication->published_at->format('d M Y H:i') }}
                                        oleh {{ $publication->publisher?->name ?? '-' }}
                                        · {{ count($publication->config_snapshot['layers'] ?? []) }} layer
                                    </small>
                                </div>
                                <form method="POST" action="{{ route('maps.publications.share', [$map, $publication]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Buat Link Berbagi</button>
                                </form>
                            </div>

                            @if ($publication->shares->isNotEmpty())
                                <table class="table table-sm mt-2 mb-0">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Diakses</th>
                                            <th>Terakhir Diakses</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($publication->shares as $share)
                                            <tr>
                                                <td>
                                                    @if ($share->isValid())
                                                        <span class="badge bg-success text-white">Aktif</span>
                                                    @else
                                                        <span class="badge bg-secondary text-white">Tidak aktif</span>
                                                    @endif
                                                </td>
                                                <td>{{ $share->access_count }}x</td>
                                                <td>{{ $share->last_accessed_at?->diffForHumans() ?? '-' }}</td>
                                                <td>
                                                    @if ($share->isValid())
                                                        <form method="POST" action="{{ route('maps.shares.revoke', [$map, $share]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cabut</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">Belum pernah diterbitkan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
