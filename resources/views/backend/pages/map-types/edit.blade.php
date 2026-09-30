@extends('backend.partials.main', ['title' => 'Detail & Ubah Jenis Peta'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-shape"></i></span>
            {{ $mapType->nama }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('map-types.index') }}">Jenis Peta</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $mapType->nama }}</li>
            </ul>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">Ubah Informasi</p>
                    <form method="POST" action="{{ route('map-types.update', $mapType) }}">
                        @csrf
                        @method('PUT')
                        @include('backend.pages.map-types._form', ['mapType' => $mapType])
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-gradient-primary">
                                <i class="mdi mdi-content-save"></i> Simpan
                            </button>
                            <a href="{{ route('map-types.index') }}" class="btn btn-outline-secondary">
                                <i class="mdi mdi-close"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
