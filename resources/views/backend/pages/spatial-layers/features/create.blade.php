@extends('backend.partials.main', ['title' => 'Tambah Data Spasial'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-map-marker-plus"></i></span>
            Tambah Data Spasial: {{ $layer->name }}
        </h3>
        <a href="{{ route('spatial-layers.edit', $layer) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="row">
        <div class="col-lg-9 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('spatial-layers.features.store', $layer) }}" enctype="multipart/form-data">
                        @csrf
                        @include('backend.pages.spatial-layers.features._form', ['feature' => null])
                        <button type="submit" class="btn btn-gradient-primary">Simpan</button>
                        <a href="{{ route('spatial-layers.edit', $layer) }}" class="btn btn-outline-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
