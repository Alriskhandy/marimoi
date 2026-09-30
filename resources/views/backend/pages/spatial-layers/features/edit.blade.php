@extends('backend.partials.main', ['title' => 'Ubah Data Spasial'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-map-marker"></i></span>
            Ubah Data Spasial #{{ $feature->id }}: {{ $layer->name }}
        </h3>
        <a href="{{ route('spatial-layers.show', $layer) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="row">
        <div class="col-lg-9 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('spatial-layers.features.update', [$layer, $feature]) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('backend.pages.spatial-layers.features._form', ['feature' => $feature])
                        <button type="submit" class="btn btn-gradient-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
