@extends('backend.partials.main', ['title' => 'Edit Layer'])

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
        <span class="page-title-icon bg-gradient-warning text-white me-2">
            <i class="mdi mdi-pencil"></i>
        </span>
        Edit Layer
    </h3>
    <nav aria-label="breadcrumb">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('layers.index', ['category' => $layer->category]) }}">
                    {{ $categories[$layer->category] ?? 'Layer' }}
                </a>
            </li>
            <li class="breadcrumb-item active">Edit: {{ $layer->name }}</li>
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

                <form method="POST" action="{{ route('layers.update', $layer) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Layer <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $layer->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            @foreach($categories as $key => $label)
                            <option value="{{ $key }}"
                                {{ old('category', $layer->category) === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe Geometri <span class="text-danger">*</span></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="point" {{ old('type', $layer->type) === 'point' ? 'selected' : '' }}>
                                Point (Titik)
                            </option>
                            <option value="line" {{ old('type', $layer->type) === 'line' ? 'selected' : '' }}>
                                Line (Garis)
                            </option>
                            <option value="polygon" {{ old('type', $layer->type) === 'polygon' ? 'selected' : '' }}>
                                Polygon (Area)
                            </option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent Layer <span class="text-muted">(opsional)</span></label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- Tidak ada (layer utama) --</option>
                            @foreach($parentLayers as $parent)
                            <option value="{{ $parent->id }}"
                                {{ old('parent_id', $layer->parent_id) == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Warna Layer</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" name="color" class="form-control form-control-color"
                                   value="{{ old('color', $layer->style['color'] ?? '#3388ff') }}"
                                   style="width: 60px; height: 38px;">
                            <span class="text-muted small">Warna tampilan pada peta</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch" style="margin-left: none;">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="isActive" value="1"
                                   {{ old('is_active', $layer->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Layer Aktif</label>
                        </div>
                        <div class="form-text">Centang untuk menampilkan layer di peta.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-gradient-warning">
                            <i class="mdi mdi-content-save me-1"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('layers.index', ['category' => $layer->category]) }}"
                           class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
