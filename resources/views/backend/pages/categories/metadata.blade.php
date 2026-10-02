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
                        Metadata standar untuk layer <code>{{ $layer->slug }}</code> (skema v3
                        <code>layer_metadata</code>) — belum ada padanannya di sistem lama.
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
                                <label class="form-label">Organisasi Produsen Data</label>
                                <input type="text" name="producer_organization" class="form-control" value="{{ old('producer_organization', $metadata->producer_organization) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">URL Sumber</label>
                                <input type="url" name="source_url" class="form-control" value="{{ old('source_url', $metadata->extra['source_url'] ?? null) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lisensi</label>
                                <input type="text" name="license" class="form-control" value="{{ old('license', $metadata->license) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Frekuensi Pembaruan</label>
                                <select name="update_frequency" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    @foreach ($updateFrequencies as $value => $label)
                                        <option value="{{ $value }}" @selected(old('update_frequency', $metadata->update_frequency) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahun Data</label>
                                <input type="number" name="data_year" class="form-control" min="1900" max="2100" value="{{ old('data_year', $metadata->data_year) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Atribusi</label>
                            <textarea name="attribution" class="form-control" rows="2">{{ old('attribution', $metadata->extra['attribution'] ?? null) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-gradient-primary">Simpan Metadata</button>
                        <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
