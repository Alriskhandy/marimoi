@extends('backend.partials.main', ['title' => 'Detail Layer'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-layers"></i></span>
            Detail Layer: {{ $layer->name }}
        </h3>
        <div>
            <a href="{{ route('spatial-layers.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <p class="card-title mb-0">Informasi Layer</p>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal"
                                data-bs-target="#editLayerModal" title="Edit">
                                <i class="mdi mdi-pencil"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                                data-bs-target="#layerInfoCollapse" aria-expanded="false" aria-controls="layerInfoCollapse"
                                id="layerInfoToggle">
                                <i class="mdi mdi-arrow-expand"></i> Expand
                            </button>
                        </div>
                    </div>
                    <div class="collapse" id="layerInfoCollapse">
                        <table class="table table-sm">
                            <tr>
                                <th style="width:200px;">Nama</th>
                                <td>{{ $layer->name }}</td>
                            </tr>
                            <tr>
                                <th>Deskripsi</th>
                                <td>{{ $layer->description ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Layer Induk</th>
                                <td>
                                    @if ($layer->parent)
                                        <a href="{{ route('spatial-layers.show', $layer->parent) }}">{{ $layer->parent->name }}</a>
                                    @else
                                        <span class="text-muted">Tidak ada (akar)</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Style</th>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($layer->color)
                                            <span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:{{ $layer->color }};border:1px solid #dee2e6;"></span>
                                            <small>{{ $layer->color }}</small>
                                        @endif
                                        @if ($layer->is_marker && $layer->icon)
                                            <i class="{{ $layer->icon }}" style="color:{{ $layer->color ?? '#007bff' }};font-size:1.3em;"></i>
                                        @endif
                                        <span class="badge {{ $layer->is_marker ? 'bg-warning text-dark' : 'bg-info text-white' }}">
                                            {{ $layer->is_marker ? 'Marker' : 'Layer' }}
                                        </span>
                                        <small class="text-muted">Opacity: {{ $layer->opacity }}</small>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if ($layer->is_active)
                                        <span class="badge bg-success text-white">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary text-white">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <p class="card-title mb-0">Jenis: {{ $layer->mapType?->nama ?? '-' }}</p>
                        @unless ($layer->mapType)
                            <p class="text-muted">Layer ini belum memiliki Jenis.</p>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <p class="card-title mb-0">Peta Data Spasial</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                            data-bs-target="#layerMapCollapse" aria-expanded="true" aria-controls="layerMapCollapse"
                            id="layerMapToggle">
                            <i class="mdi mdi-arrow-collapse"></i> Collapse
                        </button>
                    </div>
                    <div class="collapse show" id="layerMapCollapse">
                        <div id="layerMapWrapper" class="layer-map-wrapper">
                            <div id="layerDetailMap"></div>
                            <div class="basemap-switcher" id="layerMapBasemapSwitcher">
                                <button type="button" class="basemap-btn active" data-basemap="satelit">
                                    <i class="mdi mdi-satellite-variant"></i> Satelit
                                </button>
                                <button type="button" class="basemap-btn" data-basemap="jalan">
                                    <i class="mdi mdi-road-variant"></i> Jalan
                                </button>
                                <button type="button" class="basemap-btn" data-basemap="topografi">
                                    <i class="mdi mdi-terrain"></i> Topografi
                                </button>
                                <button type="button" class="basemap-btn" data-basemap="gelap">
                                    <i class="mdi mdi-weather-night"></i> Gelap
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <p class="card-title mb-0">Data Spasial ({{ $layer->features->count() }})</p>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <select class="form-select form-select-sm" id="dataSpasialPerPage" style="width: auto;">
                                <option value="10">Tampilkan 10 data</option>
                                <option value="25">Tampilkan 25 data</option>
                                <option value="50">Tampilkan 50 data</option>
                                <option value="all" selected>Tampilkan semua</option>
                            </select>
                            <div class="input-group filter-input-group" style="max-width: 320px;">
                                <input type="text" class="form-control filter-control" id="dataSpasialSearchInput"
                                    placeholder="Cari kode, wilayah...">
                                <span class="btn btn-md btn-primary filter-btn"><i class="mdi mdi-magnify"></i></span>
                            </div>
                            <a href="{{ route('spatial-layers.features.create', $layer) }}"
                                class="btn btn-sm btn-gradient-primary text-nowrap">
                                <i class="mdi mdi-map-marker-plus"></i> Tambah Data Spasial
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped" id="dataSpasialLayerTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Wilayah</th>
                                    <th>Metadata</th>
                                    <th>Tanggal Input</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($layer->features as $feature)
                                    <tr data-feature-row>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $feature->external_id ?? '-' }}</td>
                                        <td>{{ $feature->region->name ?? '-' }}</td>
                                        <td class="text-center">
                                            @if (! empty($feature->metadata_dinamis))
                                                <span class="badge bg-gradient-success text-white">Lengkap</span>
                                            @else
                                                <span class="badge bg-gradient-warning text-white">Belum lengkap</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $feature->created_at?->format('d M Y') ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="{{ route('spatial-layers.features.edit', [$layer, $feature]) }}"
                                                    class="btn btn-sm btn-outline-warning" title="Kelola">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <form action="{{ route('spatial-layers.features.destroy', [$layer, $feature]) }}"
                                                    method="POST" style="display:inline-block;" data-confirm="delete"
                                                    data-name="{{ $feature->external_id ?? '#'.$feature->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada Data Spasial.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <p id="dataSpasialNoResult" class="text-center text-muted py-3 d-none">Tidak ada data yang cocok dengan pencarian.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editLayerModal" tabindex="-1" aria-labelledby="editLayerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLayerModalLabel">
                        <i class="mdi mdi-pencil"></i> Edit Layer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="editLayerForm" method="POST" action="{{ route('spatial-layers.update', $layer) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <!-- LEFT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="layer_edit_map_type_id" class="form-label">Jenis <span class="text-danger">*</span></label>
                                    <select class="form-control" id="layer_edit_map_type_id" name="map_type_id" required>
                                        <option value="">-- Pilih Jenis --</option>
                                        @foreach ($mapTypes as $mapType)
                                            <option value="{{ $mapType->id }}" @selected(old('map_type_id', $layer->map_type_id) == $mapType->id)>{{ $mapType->nama }}</option>
                                        @endforeach
                                    </select>
                                    @error('map_type_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group mb-2">
                                    <label for="layer_edit_name" class="form-label">Nama Layer <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="layer_edit_name" name="name" value="{{ old('name', $layer->name) }}" required>
                                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group mb-2">
                                    <label for="layer_edit_description" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="layer_edit_description" name="description" rows="2">{{ old('description', $layer->description) }}</textarea>
                                    @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group mb-2">
                                    <label for="layer_edit_parent_id" class="form-label">Layer Induk</label>
                                    <select class="form-control" id="layer_edit_parent_id" name="parent_id">
                                        <option value="">-- Tidak ada (jadi akar) --</option>
                                        @foreach ($parentOptions as $option)
                                            <option value="{{ $option->id }}" @selected(old('parent_id', $layer->parent_id) == $option->id)>{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('parent_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                
                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="layer_edit_warna" class="form-label">Warna</label>
                                    <div class="color-picker-widget">
                                        <div class="color-swatch-list" id="layer_edit_colorSwatches">
                                            <button type="button" class="color-swatch" data-color="#007bff" style="background-color:#007bff" title="#007bff"></button>
                                            <button type="button" class="color-swatch" data-color="#28a745" style="background-color:#28a745" title="#28a745"></button>
                                            <button type="button" class="color-swatch" data-color="#dc3545" style="background-color:#dc3545" title="#dc3545"></button>
                                            <button type="button" class="color-swatch" data-color="#ffc107" style="background-color:#ffc107" title="#ffc107"></button>
                                            <button type="button" class="color-swatch" data-color="#17a2b8" style="background-color:#17a2b8" title="#17a2b8"></button>
                                            <button type="button" class="color-swatch" data-color="#6f42c1" style="background-color:#6f42c1" title="#6f42c1"></button>
                                            <button type="button" class="color-swatch" data-color="#fd7e14" style="background-color:#fd7e14" title="#fd7e14"></button>
                                            <button type="button" class="color-swatch" data-color="#20c997" style="background-color:#20c997" title="#20c997"></button>
                                            <button type="button" class="color-swatch" data-color="#6c757d" style="background-color:#6c757d" title="#6c757d"></button>
                                            <button type="button" class="color-swatch" data-color="#212529" style="background-color:#212529" title="#212529"></button>
                                        </div>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color"
                                                id="layer_edit_warna" name="color" value="{{ old('color', $layer->color ?? '#007bff') }}">
                                            <input type="text" class="form-control text-uppercase" id="layer_edit_warnaHex"
                                                maxlength="7" placeholder="#RRGGBB" autocomplete="off"
                                                value="{{ strtoupper(old('color', $layer->color ?? '#007bff')) }}">
                                        </div>
                                        @error('color') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="layer_edit_opacity" class="form-label">Opacity</label>
                                    <input type="number" class="form-control" id="layer_edit_opacity" name="opacity"
                                        step="0.1" min="0" max="1" value="{{ old('opacity', $layer->opacity ?? 1) }}">
                                    @error('opacity') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label d-block">Status & Jenis Layer</label>
                                    <div class="settings-switch-group">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_active" value="0">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="layer_edit_is_active" name="is_active" @checked(old('is_active', $layer->is_active))>
                                            <label class="form-check-label" for="layer_edit_is_active">
                                                <i class="mdi mdi-check-circle text-success me-1"></i>Aktifkan Layer
                                            </label>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_marker" value="0">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="layer_edit_is_marker" name="is_marker" @checked(old('is_marker', $layer->is_marker))>
                                            <label class="form-check-label" for="layer_edit_is_marker">
                                                <i class="mdi mdi-map-marker text-warning me-1"></i>Gunakan sebagai Marker
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ICON MARKER PICKER (1 column, full width) -->
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group mb-0" id="layer_edit_iconContainer" style="display: none;">
                                    <label class="form-label">
                                        <i class="mdi mdi-map-marker me-1"></i> Ikon Marker
                                    </label>

                                    <!-- Hidden select acts as the source of truth & data source for the picker -->
                                    <select id="layer_edit_icon" name="icon" class="d-none">
                                        <option value="">-- Pilih Ikon --</option>
                                        @include('backend.partials.icon-options')
                                    </select>

                                    <div class="row g-2 mb-2">
                                        <div class="col-md-7">
                                            <div class="icon-picker-search h-100">
                                                <div class="input-group input-group-sm h-100">
                                                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                                    <input type="text" class="form-control" id="layer_edit_iconSearch"
                                                        placeholder="Cari ikon berdasarkan nama atau class...">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div id="layer_edit_iconPreview" class="icon-preview-container icon-preview-inline">
                                                <span class="text-muted">Pilih ikon untuk melihat pratinjau</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="icon-picker-grid" id="layer_edit_iconGrid"></div>

                                    <div class="form-text">Ikon hanya berlaku untuk Layer marker. Klik salah satu ikon
                                        di atas untuk memilih.</div>
                                    @error('icon') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-gradient-warning">
                            <i class="mdi mdi-content-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        .layer-map-wrapper {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }

        #layerDetailMap {
            height: 450px;
            width: 100%;
        }

        .basemap-switcher {
            position: absolute;
            left: 10px;
            bottom: 20px;
            z-index: 1000;
            display: flex;
            gap: 4px;
            background: #fff;
            padding: 4px;
            border-radius: 6px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, .45);
        }

        .basemap-btn {
            border: 0;
            background: transparent;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            color: #333;
        }

        .basemap-btn:hover {
            background: #eef3ff;
        }

        .basemap-btn.active {
            background: #0d6efd;
            color: #fff;
        }

        #dataSpasialLayerTable th {
            font-size: 0.75rem;
        }

        #dataSpasialLayerTable td {
            font-size: 0.85rem;
        }

        /* ===========================================
           MODAL EDIT LAYER — disamakan dengan modal edit Kategori
           (resources/views/backend/pages/categories/index.blade.php)
        =========================================== */
        .modal-lg {
            max-width: 800px;
        }

        .modal-content {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid #dee2e6;
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
        }

        .icon-picker-grid {
            max-height: 260px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fff;
        }

        .icon-picker-group-title {
            margin: 12px 0 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9aa4af;
        }

        .icon-picker-group-title:first-child {
            margin-top: 0;
        }

        .icon-picker-items {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
        }

        @media (max-width: 576px) {
            .icon-picker-items {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .icon-picker-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 4px;
            padding: 10px 4px;
            border: 1px solid #eef2f7;
            border-radius: 6px;
            background: #fff;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .icon-picker-item:hover {
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.06);
        }

        .icon-picker-item.active {
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.12);
            box-shadow: 0 0 0 1px #007bff inset;
        }

        .icon-picker-glyph {
            font-size: 1.9rem;
            line-height: 1;
            color: #495057;
        }

        .icon-picker-item.active .icon-picker-glyph {
            color: #007bff;
        }

        .icon-picker-name {
            display: -webkit-box;
            width: 100%;
            overflow: hidden;
            font-size: 0.65rem;
            font-weight: 600;
            color: #343a40;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .icon-picker-class {
            display: block;
            width: 100%;
            overflow: hidden;
            font-size: 0.58rem;
            color: #868e96;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .icon-picker-empty {
            padding: 20px 0;
            color: #adb5bd;
            font-size: 0.85rem;
            text-align: center;
        }

        .color-picker-widget {
            padding: 0.75rem;
            background-color: #f8f9fa;
            border: 1px solid #eef2f7;
            border-radius: 8px;
        }

        .color-swatch-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 0.65rem;
        }

        .color-swatch {
            width: 26px;
            height: 26px;
            padding: 0;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 0 1px #dee2e6;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .color-swatch:hover {
            transform: scale(1.12);
        }

        .color-swatch.active {
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #007bff;
        }

        .color-picker-widget .input-group .form-control-color {
            max-width: 50px;
        }

        .settings-switch-group {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            padding: 0.65rem 0.85rem;
            background-color: #f8f9fa;
            border: 1px solid #eef2f7;
            border-radius: 8px;
        }

        .settings-switch-group .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-left: 0;
            margin: 0;
            min-height: auto;
        }

        .settings-switch-group .form-check-input {
            flex-shrink: 0;
            float: none;
            margin: 0;
        }

        .settings-switch-group .form-check-label {
            margin: 0;
        }

        .icon-preview-container {
            min-height: 60px;
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
            width: 100%;
        }

        .icon-preview-container.has-icon {
            background-color: #fff;
            border-color: #007bff;
            border-style: solid;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15);
        }

        .icon-preview-content {
            display: flex;
            align-items: center;
            width: 100%;
        }

        .icon-preview-icon {
            font-size: 2.5em;
            margin-right: 15px;
            color: #007bff;
        }

        .icon-preview-details h6 {
            margin: 0 0 5px 0;
            font-weight: 600;
            color: #495057;
        }

        .icon-preview-details small {
            color: #6c757d;
            font-size: 0.85em;
        }

        .icon-preview-code {
            background-color: #f1f3f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.8em;
            color: #d63384;
        }

        .icon-preview-inline {
            min-height: 31px;
            padding: 4px 10px;
        }

        .icon-preview-inline span.text-muted {
            font-size: 0.72rem;
        }

        .icon-preview-inline .icon-preview-icon {
            font-size: 1.4em;
            margin-right: 8px;
        }

        .icon-preview-inline .icon-preview-details h6 {
            display: none;
        }

        .icon-preview-inline .icon-preview-details small {
            font-size: 0.72em;
        }

        .icon-preview-inline .icon-preview-code {
            font-size: 0.68em;
            padding: 1px 4px;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            const layerSlug = @json($layer->slug);
            const layerColor = @json($layer->color ?? '#2563eb');
            const layerIcon = @json($layer->is_marker ? $layer->icon : null);
            const layerOpacity = @json($layer->opacity ?? 1);

            const map = L.map('layerDetailMap').setView([1.5, 127.8], 8);

            const esriImagery = L.tileLayer(
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                { attribution: 'Tiles &copy; Esri' }
            );

            const basemaps = {
                satelit: esriImagery,
                jalan: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                }),
                topografi: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenTopoMap contributors',
                }),
                gelap: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', {
                    attribution: '&copy; CARTO',
                }),
            };

            basemaps.satelit.addTo(map);
            let activeBasemap = basemaps.satelit;

            function setBasemap(name) {
                if (! basemaps[name] || basemaps[name] === activeBasemap) {
                    return;
                }
                map.removeLayer(activeBasemap);
                basemaps[name].addTo(map);
                activeBasemap = basemaps[name];
                document.querySelectorAll('#layerMapBasemapSwitcher .basemap-btn').forEach((btn) => {
                    btn.classList.toggle('active', btn.dataset.basemap === name);
                });
            }

            document.querySelectorAll('#layerMapBasemapSwitcher .basemap-btn').forEach((btn) => {
                btn.addEventListener('click', () => setBasemap(btn.dataset.basemap));
            });

            fetch(`/peta-v2/geojson/${layerSlug}`)
                .then((response) => (response.ok ? response.json() : null))
                .then((geojson) => {
                    if (! geojson) {
                        return;
                    }

                    const dataLayer = L.geoJSON(geojson, {
                        pointToLayer: (feature, latlng) => {
                            if (layerIcon) {
                                return L.marker(latlng, {
                                    icon: L.divIcon({
                                        html: `<i class="${layerIcon}" style="color:${layerColor};font-size:1.5rem;"></i>`,
                                        className: 'layer-detail-marker-icon',
                                        iconSize: [24, 24],
                                    }),
                                });
                            }

                            return L.circleMarker(latlng, {
                                radius: 6,
                                fillColor: layerColor,
                                color: layerColor,
                                weight: 1,
                                fillOpacity: layerOpacity,
                            });
                        },
                        style: () => ({ color: layerColor, weight: 2, opacity: layerOpacity, fillOpacity: layerOpacity * 0.4 }),
                        onEachFeature: (feature, leafletLayer) => {
                            const externalId = feature.properties?.external_id ?? `#${feature.properties?.id ?? ''}`;
                            leafletLayer.bindPopup(String(externalId));
                        },
                    }).addTo(map);

                    if (dataLayer.getBounds().isValid()) {
                        map.fitBounds(dataLayer.getBounds(), { maxZoom: 15 });
                    }
                })
                .catch((error) => console.error('Gagal memuat Data Spasial Layer ini', error));

            const mapCollapseEl = document.getElementById('layerMapCollapse');
            const mapToggleBtn = document.getElementById('layerMapToggle');

            mapCollapseEl.addEventListener('shown.bs.collapse', () => {
                map.invalidateSize();
                mapToggleBtn.innerHTML = '<i class="mdi mdi-arrow-collapse"></i> Collapse';
            });

            mapCollapseEl.addEventListener('hidden.bs.collapse', () => {
                mapToggleBtn.innerHTML = '<i class="mdi mdi-arrow-expand"></i> Expand';
            });

            const infoCollapseEl = document.getElementById('layerInfoCollapse');
            const infoToggleBtn = document.getElementById('layerInfoToggle');

            infoCollapseEl.addEventListener('shown.bs.collapse', () => {
                infoToggleBtn.innerHTML = '<i class="mdi mdi-arrow-collapse"></i> Collapse';
            });

            infoCollapseEl.addEventListener('hidden.bs.collapse', () => {
                infoToggleBtn.innerHTML = '<i class="mdi mdi-arrow-expand"></i> Expand';
            });

            const searchInput = document.getElementById('dataSpasialSearchInput');
            const perPageSelect = document.getElementById('dataSpasialPerPage');
            const tableBody = document.querySelector('#dataSpasialLayerTable tbody');
            const noResultEl = document.getElementById('dataSpasialNoResult');

            function applyDataSpasialTableView() {
                if (! tableBody) {
                    return;
                }

                const term = searchInput ? searchInput.value.trim().toLowerCase() : '';
                const limit = ! perPageSelect || perPageSelect.value === 'all' ? Infinity : parseInt(perPageSelect.value, 10);
                let matchedCount = 0;
                let shownCount = 0;

                tableBody.querySelectorAll('tr[data-feature-row]').forEach((row) => {
                    const matches = row.textContent.toLowerCase().includes(term);
                    if (matches) {
                        matchedCount++;
                    }

                    const visible = matches && shownCount < limit;
                    if (visible) {
                        shownCount++;
                    }

                    row.classList.toggle('d-none', ! visible);
                });

                if (noResultEl) {
                    noResultEl.classList.toggle('d-none', matchedCount > 0);
                }
            }

            if (searchInput) {
                searchInput.addEventListener('keyup', applyDataSpasialTableView);
            }

            if (perPageSelect) {
                perPageSelect.addEventListener('change', applyDataSpasialTableView);
            }

            applyDataSpasialTableView();

            @if ($errors->any())
                const editLayerModalEl = document.getElementById('editLayerModal');
                if (editLayerModalEl && window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(editLayerModalEl).show();
                }
            @endif
        })();
    </script>

    <script>
        /**
         * Icon-picker & color-picker modal Edit Layer — disamakan dengan modal edit
         * Kategori di categories/index.blade.php (prefix "edit_"), diadaptasi jadi
         * prefix "layer_edit_" karena di sini cuma ada satu instance (tidak ada modal
         * "add" terpisah seperti di categories).
         */
        $(function () {
            const prefix = 'layer_edit';

            function updateIconPreview(iconClass, colorValue) {
                const $previewElement = $(`#${prefix}_iconPreview`);

                if (iconClass && iconClass.trim()) {
                    const iconHtml = `
                        <div class="icon-preview-content">
                            <i class="${iconClass} icon-preview-icon" style="color: ${colorValue}; font-size: 2.5em;"></i>
                            <div class="icon-preview-details">
                                <h6>Icon Preview</h6>
                                <small>Class: <span class="icon-preview-code">${iconClass}</span></small>
                            </div>
                        </div>
                    `;
                    $previewElement.html(iconHtml).addClass('has-icon');
                } else {
                    $previewElement.html('<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>')
                        .removeClass('has-icon');
                }
            }

            function appendIconItem($container, $option) {
                const value = $option.val();
                const label = $option.text().trim();
                const searchText = (label + ' ' + value).toLowerCase();

                $container.append(`
                    <div class="icon-picker-item" data-icon-value="${value}" data-search="${searchText}">
                        <i class="${value} icon-picker-glyph"></i>
                        <span class="icon-picker-name">${label}</span>
                        <code class="icon-picker-class">${value}</code>
                    </div>
                `);
            }

            function buildIconPicker(selectId, gridId) {
                const $select = $(selectId);
                const $grid = $(gridId);
                $grid.empty();

                let $itemsWrap = null;

                $select.children('option, optgroup').each(function () {
                    if (this.tagName === 'OPTGROUP') {
                        $grid.append(`<div class="icon-picker-group-title">${$(this).attr('label')}</div>`);
                        $itemsWrap = $('<div class="icon-picker-items"></div>');
                        $grid.append($itemsWrap);
                        $(this).children('option').each(function () {
                            appendIconItem($itemsWrap, $(this));
                        });
                    } else {
                        const value = $(this).val();
                        if (!value) return;
                        if (!$itemsWrap) {
                            $itemsWrap = $('<div class="icon-picker-items"></div>');
                            $grid.append($itemsWrap);
                        }
                        appendIconItem($itemsWrap, $(this));
                    }
                });

                if ($grid.find('.icon-picker-item').length === 0) {
                    $grid.html('<div class="icon-picker-empty">Tidak ada ikon tersedia</div>');
                }
            }

            function applyColor(newColor) {
                $(`#${prefix}_warna`).val(newColor);
                $(`#${prefix}_warnaHex`).val(newColor.toUpperCase());
                $(`#${prefix}_colorSwatches .color-swatch`).removeClass('active');
                $(`#${prefix}_colorSwatches .color-swatch[data-color="${newColor.toLowerCase()}"]`).addClass('active');

                const iconElement = $(`#${prefix}_iconPreview .icon-preview-icon`);
                if (iconElement.length) {
                    iconElement.css('color', newColor);
                }
            }

            buildIconPicker(`#${prefix}_icon`, `#${prefix}_iconGrid`);

            $(document).on('click', `#${prefix}_iconGrid .icon-picker-item`, function () {
                const iconValue = $(this).data('icon-value');
                $(`#${prefix}_iconGrid .icon-picker-item`).removeClass('active');
                $(this).addClass('active');
                $(`#${prefix}_icon`).val(iconValue).trigger('change');
            });

            $(document).on('input', `#${prefix}_iconSearch`, function () {
                const $grid = $(`#${prefix}_iconGrid`);
                const query = $(this).val().trim().toLowerCase();

                $grid.find('.icon-picker-item').each(function () {
                    const matches = !query || $(this).data('search').toString().includes(query);
                    $(this).toggle(matches);
                });

                $grid.find('.icon-picker-items').each(function () {
                    const hasVisible = $(this).find('.icon-picker-item:visible').length > 0;
                    $(this).toggle(hasVisible);
                    $(this).prev('.icon-picker-group-title').toggle(hasVisible);
                });
            });

            $(`#${prefix}_icon`).on('change', function () {
                const colorValue = $(`#${prefix}_warna`).val() || '#007bff';
                updateIconPreview($(this).val(), colorValue);
            });

            $(`#${prefix}_warna`).on('change input', function () {
                applyColor($(this).val());
            });

            $(`#${prefix}_warnaHex`).on('input', function () {
                let value = $(this).val().trim();
                if (value && value[0] !== '#') {
                    value = '#' + value;
                }
                $(this).val(value.toUpperCase());

                if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    applyColor(value);
                }
            });

            $(document).on('click', `#${prefix}_colorSwatches .color-swatch`, function () {
                applyColor($(this).data('color'));
            });

            $(`#${prefix}_is_marker`).on('change', function () {
                if ($(this).is(':checked')) {
                    $(`#${prefix}_iconContainer`).slideDown(300);
                } else {
                    $(`#${prefix}_iconContainer`).slideUp(300);
                    $(`#${prefix}_icon`).val('');
                    updateIconPreview('', '#007bff');
                    $(`#${prefix}_iconGrid .icon-picker-item`).removeClass('active').show();
                    $(`#${prefix}_iconGrid .icon-picker-items, #${prefix}_iconGrid .icon-picker-group-title`).show();
                    $(`#${prefix}_iconSearch`).val('');
                }
            });

            // Pra-isi state awal dari data Layer (server-rendered), setara dengan yang
            // dilakukan handler klik ".btn-edit" di categories/index.blade.php — di
            // sini tidak perlu klik apa pun karena cuma ada satu Layer pada halaman ini.
            const initialColor = @json(old('color', $layer->color ?? '#007bff'));
            applyColor(initialColor);

            const isMarker = @json(old('is_marker', $layer->is_marker));
            if (isMarker) {
                $(`#${prefix}_iconContainer`).show();
                const initialIcon = @json(old('icon', $layer->icon ?? ''));
                $(`#${prefix}_icon`).val(initialIcon);
                updateIconPreview(initialIcon, initialColor);
                $(`#${prefix}_iconGrid .icon-picker-item[data-icon-value="${initialIcon}"]`).addClass('active');
            }
        });
    </script>
@endpush
