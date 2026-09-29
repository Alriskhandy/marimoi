@extends('backend.partials.main', ['title' => 'Ubah Jenis Peta'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-shape"></i></span>
            Ubah Jenis Peta: {{ $mapType->nama }}
        </h3>
        <a href="{{ route('map-types.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-9 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('map-types.update', $mapType) }}">
                        @csrf
                        @method('PUT')
                        @include('backend.pages.map-types._form', ['mapType' => $mapType])
                        <button type="submit" class="btn btn-gradient-primary">Simpan</button>
                        <a href="{{ route('map-types.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
