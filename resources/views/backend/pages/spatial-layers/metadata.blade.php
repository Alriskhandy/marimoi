@extends('backend.partials.main', ['title' => 'Metadata Layer'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-file-document-outline"></i>
            </span>
            Metadata Layer: {{ $layer->name }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('spatial-layers.index') }}">Daftar Layer & Data</a></li>
                <li class="breadcrumb-item"><a href="{{ route('spatial-layers.show', $layer) }}">{{ $layer->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Metadata</li>
            </ul>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-9 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">
                        Metadata standar (ISO 19115 / Satu Data Indonesia) untuk layer <code>{{ $layer->slug }}</code>.
                    </p>

                    <form method="POST" action="{{ route('spatial-layers.metadata.update', $layer) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Judul Dataset</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $metadata->title) }}">
                                @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kategori Topik</label>
                                <input type="text" name="topic_category" class="form-control" placeholder="mis. boundaries, transportation" value="{{ old('topic_category', $metadata->topic_category) }}">
                                @error('topic_category') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Abstrak / Ringkasan Dataset</label>
                            <textarea name="abstract" class="form-control" rows="3">{{ old('abstract', $metadata->abstract) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tujuan (Purpose)</label>
                            <textarea name="purpose" class="form-control" rows="2">{{ old('purpose', $metadata->purpose) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kata Kunci</label>
                            <input type="text" name="keywords" class="form-control" placeholder="pisahkan dengan koma, mis. jalan, infrastruktur, provinsi" value="{{ old('keywords', implode(', ', $keywords)) }}">
                            @error('keywords') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <hr>
                        <h6>Produsen & Kontak</h6>
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
                                <label class="form-label">Sumber Data</label>
                                <input type="text" name="sumber_data" class="form-control" value="{{ old('sumber_data', $metadata->sumber_data) }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nama Kontak</label>
                                <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name', $metadata->contact_name) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Email Kontak</label>
                                <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $metadata->contact_email) }}">
                                @error('contact_email') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Telepon Kontak</label>
                                <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $metadata->contact_phone) }}">
                            </div>
                        </div>

                        <hr>
                        <h6>Waktu & Keakuratan</h6>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Tahun Data</label>
                                <input type="number" name="data_year" class="form-control" min="1900" max="2100" value="{{ old('data_year', $metadata->data_year) }}">
                                @error('data_year') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Tanggal Referensi</label>
                                <input type="date" name="reference_date" class="form-control" value="{{ old('reference_date', optional($metadata->reference_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Jenis Tanggal</label>
                                <select name="date_type" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    @foreach ($dateTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('date_type', $metadata->date_type) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
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
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Skala (Penyebut)</label>
                                <input type="number" name="scale_denominator" class="form-control" min="1" placeholder="mis. 25000" value="{{ old('scale_denominator', $metadata->scale_denominator) }}">
                                @error('scale_denominator') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Akurasi Posisi</label>
                                <input type="text" name="positional_accuracy" class="form-control" placeholder="mis. ±5 meter" value="{{ old('positional_accuracy', $metadata->positional_accuracy) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Wilayah Administratif</label>
                                <input type="text" name="administrative_area" class="form-control" value="{{ old('administrative_area', $metadata->administrative_area) }}">
                            </div>
                        </div>

                        <hr>
                        <h6>Lisensi & Batasan</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lisensi</label>
                                <input type="text" name="license" class="form-control" value="{{ old('license', $metadata->license) }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Riwayat Pengolahan (Lineage)</label>
                            <textarea name="lineage" class="form-control" rows="2">{{ old('lineage', $metadata->lineage) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Batasan Penggunaan</label>
                            <textarea name="use_constraints" class="form-control" rows="2">{{ old('use_constraints', $metadata->use_constraints) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Atribusi</label>
                            <textarea name="attribution" class="form-control" rows="2">{{ old('attribution', $metadata->extra['attribution'] ?? null) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-gradient-primary">Simpan Metadata</button>
                        <a href="{{ route('spatial-layers.show', $layer) }}" class="btn btn-outline-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
