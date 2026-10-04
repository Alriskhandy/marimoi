@extends('backend.partials.main', ['title' => 'Pemetaan Kolom Impor'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-table-arrow-right"></i></span>
            Pemetaan Kolom: {{ $layer->name }}
        </h3>
        <a href="{{ route('spatial-layers.show', $layer) }}" class="btn btn-outline-secondary">Batal</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-9 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i>
                        File <strong>{{ $import->original_filename }}</strong> berhasil diunggah,
                        <strong>{{ $import->total_features }}</strong> fitur terdeteksi, mode impor:
                        <strong>{{ ucfirst($import->import_mode) }}</strong>.
                        Petakan tiap kolom di bawah sebelum data disimpan.
                    </div>

                    <form method="POST" action="{{ route('spatial-layers.imports.mapping.process', [$layer, $import]) }}" enctype="multipart/form-data">
                        @csrf

                        <h6 class="mb-3"><i class="mdi mdi-table-column me-2"></i>Pemetaan Kolom</h6>
                        @forelse ($import->detected_fields as $field)
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-4">
                                    <code>{{ $field }}</code>
                                </div>
                                <div class="col-md-8">
                                    <select name="mapping[{{ $field }}]" class="form-select">
                                        <option value="keep" selected>Simpan apa adanya ({{ $field }})</option>
                                        <option value="ignore">Abaikan (jangan simpan)</option>
                                        @foreach ($dynamicAttributes as $attribute)
                                            @php $definition = $attribute->metadataDefinition; @endphp
                                            <option value="map:{{ $definition->kode }}">Petakan ke "{{ $definition->label }}"</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">Tidak ada kolom terdeteksi dari file ini.</p>
                        @endforelse

                        <hr>
                        <h6 class="mb-3"><i class="mdi mdi-clipboard-text-outline me-2"></i>
                            Metadata{{ $layer->mapType ? ' (sesuai skema Jenis "'.$layer->mapType->nama.'")' : '' }}
                        </h6>
                        @include('backend.pages.spatial-layers.features._metadata-dinamis', ['dynamicAttributes' => $dynamicAttributes, 'feature' => null])

                        <div class="mb-3">
                            <label class="form-label">Gambar (opsional)</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*">
                            @error('gambar') <div class="text-danger small">{{ $message }}</div> @enderror
                            <div class="form-text">Berlaku untuk semua Data Spasial hasil impor ini.</div>
                        </div>

                        <button type="submit" class="btn btn-gradient-success">
                            <i class="mdi mdi-check me-1"></i> Proses & Simpan
                        </button>
                        <a href="{{ route('spatial-layers.show', $layer) }}" class="btn btn-outline-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
