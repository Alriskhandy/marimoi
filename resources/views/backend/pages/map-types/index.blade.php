@extends('backend.partials.main', ['title' => 'Jenis Peta'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-map-marker-radius"></i>
            </span>
            Jenis Peta
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Jenis Peta</li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="card-title mb-0">Master jenis peta (dipakai sebagai <code>map_type_id</code> pada Layer) — Jenis hanya referensi/panduan metadata, tidak menyimpan nilai</p>
                        <a href="{{ route('map-types.create') }}" class="btn btn-gradient-primary">
                            <i class="mdi mdi-plus"></i> Tambah Jenis Peta
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Urutan</th>
                                    <th>Slug</th>
                                    <th>Nama</th>
                                    <th>Sumber Data</th>
                                    <th>Jumlah Layer</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mapTypes as $mapType)
                                    <tr>
                                        <td>{{ $mapType->urutan }}</td>
                                        <td><code>{{ $mapType->slug }}</code></td>
                                        <td>{{ $mapType->nama }}</td>
                                        <td>{{ $mapType->sumber_data ?? '-' }}</td>
                                        <td>{{ $mapType->spatial_layers_count }}</td>
                                        <td>
                                            <span class="badge {{ $mapType->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $mapType->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('map-types.edit', $mapType) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <form action="{{ route('map-types.destroy', $mapType) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Hapus jenis peta ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Belum ada jenis peta.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
