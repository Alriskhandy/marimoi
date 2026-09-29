@extends('backend.partials.main', ['title' => 'Tambah Jenis Peta'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-shape-plus"></i></span>
            Tambah Jenis Peta
        </h3>
        <a href="{{ route('map-types.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="row">
        <div class="col-lg-9 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('map-types.store') }}">
                        @csrf
                        @include('backend.pages.map-types._form', ['mapType' => null])
                        <button type="submit" class="btn btn-gradient-primary">Simpan</button>
                        <a href="{{ route('map-types.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
