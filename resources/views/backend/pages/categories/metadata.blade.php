@extends('backend.partials.main', ['title' => 'Metadata Layer'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-file-document-outline"></i>
            </span>
            Metadata Layer: {{ $category->nama }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Kategori Peta</a></li>
                <li class="breadcrumb-item active" aria-current="page">Metadata Layer</li>
            </ul>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">
                        Metadata standar untuk layer <code>{{ $layer->slug }}</code> (skema baru
                        <code>spatial_layer_metadata</code>) — belum ada padanannya di sistem lama.
                    </p>

                    <form method="POST" action="{{ route('categories.metadata.update', $category->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Abstrak / Ringkasan Dataset</label>
                            <textarea name="abstract" class="form-control" rows="3">{{ old('abstract', $metadata->abstract) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Sumber Data</label>
                                <input type="text" name="source_name" class="form-control" value="{{ old('source_name', $metadata->source_name) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL Sumber</label>
                                <input type="url" name="source_url" class="form-control" value="{{ old('source_url', $metadata->source_url) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lisensi</label>
                                <input type="text" name="license" class="form-control" value="{{ old('license', $metadata->license) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Frekuensi Pembaruan</label>
                                <input type="text" name="update_frequency" class="form-control" placeholder="mis. Tahunan, Triwulan" value="{{ old('update_frequency', $metadata->update_frequency) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahun Referensi Data</label>
                                <input type="number" name="data_reference_year" class="form-control" min="1900" max="2100" value="{{ old('data_reference_year', $metadata->data_reference_year) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Atribusi</label>
                            <textarea name="attribution" class="form-control" rows="2">{{ old('attribution', $metadata->attribution) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-gradient-primary">Simpan Metadata</button>
                        <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
