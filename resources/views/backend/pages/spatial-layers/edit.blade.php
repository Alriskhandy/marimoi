@extends('backend.partials.main', ['title' => 'Ubah Layer'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-layers"></i></span>
            Ubah Layer: {{ $layer->name }}
        </h3>
        <a href="{{ route('spatial-layers.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-7 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('spatial-layers.update', $layer) }}">
                        @csrf
                        @method('PUT')
                        @include('backend.pages.spatial-layers._form', ['layer' => $layer])
                        <button type="submit" class="btn btn-gradient-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="card-title mb-0">Data Spasial ({{ $layer->features()->count() }})</p>
                        <a href="{{ route('spatial-layers.features.create', $layer) }}" class="btn btn-sm btn-gradient-primary">
                            <i class="mdi mdi-plus"></i> Tambah
                        </a>
                    </div>
                    <ul class="list-group">
                        @forelse ($layer->features as $feature)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>#{{ $feature->id }} — {{ $feature->external_id ?? 'Tanpa ID eksternal' }}</span>
                                <span>
                                    <a href="{{ route('spatial-layers.features.edit', [$layer, $feature]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <form action="{{ route('spatial-layers.features.destroy', [$layer, $feature]) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus Data Spasial ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="mdi mdi-delete"></i></button>
                                    </form>
                                </span>
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
