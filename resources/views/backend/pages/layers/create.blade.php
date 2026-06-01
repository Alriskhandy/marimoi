@extends('backend.partials.main', ['title' => 'Tambah Layer'])

@section('main')
@php
    $categories = [
        'tematik'    => 'Peta Tematik',
        'psd'        => 'Proyek Strategis Daerah',
        'psn'        => 'Proyek Strategis Nasional',
        'pokir'      => 'Pokir DPRD',
        'musrenbang' => 'Usulan Musrenbang',
    ];
@endphp

<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-layers-plus"></i>
        </span>
        Tambah Layer Baru
    </h3>
    <nav aria-label="breadcrumb">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('layers.index', array_filter(['category' => $category])) }}">
                    {{ $categories[$category] ?? 'Semua Layer' }}
                </a>
            </li>
            <li class="breadcrumb-item active">Tambah Layer</li>
        </ul>
    </nav>
</div>

<div class="row">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
                @endif

                <form method="POST" action="{{ route('layers.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Layer <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="Contoh: Jaringan Jalan Nasional" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $key => $label)
                            <option value="{{ $key }}"
                                {{ old('category', $category) === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe Geometri <span class="text-danger">*</span></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="point" {{ old('type') === 'point' ? 'selected' : '' }}>
                                Point (Titik)
                            </option>
                            <option value="line" {{ old('type') === 'line' ? 'selected' : '' }}>
                                Line (Garis)
                            </option>
                            <option value="polygon" {{ old('type') === 'polygon' ? 'selected' : '' }}>
                                Polygon (Area)
                            </option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent Layer <span class="text-muted">(opsional)</span></label>
                        <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                            <option value="">-- Tidak ada (layer utama) --</option>
                            @foreach($parentLayers as $parent)
                            <option value="{{ $parent->id }}"
                                {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Biarkan kosong jika ini adalah layer utama.</div>
                        @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Warna Layer</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" name="color" class="form-control form-control-color"
                                   value="{{ old('color', '#3388ff') }}" style="width: 60px; height: 38px;">
                            <span class="text-muted small">Warna tampilan pada peta</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="isActive" value="1"
                                   {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Layer Aktif</label>
                        </div>
                        <div class="form-text">Centang untuk menampilkan layer di peta.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan Layer
                        </button>
                        <a href="{{ route('layers.index', array_filter(['category' => $category])) }}"
                           class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
