@extends('backend.partials.main', ['title' => 'Daftar Layer & Data'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-layers"></i>
            </span>
            Daftar Layer & Data
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Daftar Layer & Data</li>
            </ul>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Statistics Cards -->
    @if ($layers->count() > 0)
        <div class="row g-3 stats-row-compact">
            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Total Layer</p>
                                <h3 class="stat-value">{{ $layers->count() }}</h3>
                            </div>
                            <i class="mdi mdi-layers stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Punya Data Spasial</p>
                                <h3 class="stat-value">{{ $layers->where('features_count', '>', 0)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-map-marker-multiple stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Marker Aktif</p>
                                <h3 class="stat-value">{{ $layers->where('is_marker', true)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-map-marker stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Status Aktif</p>
                                <h3 class="stat-value">{{ $layers->where('is_active', true)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-check-circle stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">Daftar Layer</h4>
                        @can('spatial-layers.create')
                            <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                data-bs-target="#addLayerModal">
                                <i class="mdi mdi-plus"></i> Tambah Layer
                            </button>
                        @endcan
                    </div>

                    <div class="row mb-4 g-3 align-items-end">
                        <div class="col-12">
                            <div class="row g-3 align-items-end filter-toolbar">
                                <div class="col-lg-6 col-md-6">
                                    <label for="tableSearch" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-magnify me-1"></i>Cari Layer
                                    </label>
                                    <div class="input-group filter-input-group">
                                        <input type="text" class="form-control filter-control" id="tableSearch"
                                            placeholder="Ketik nama layer...">
                                        <button class="btn btn-md btn-primary filter-btn" type="button" id="searchTableBtn">
                                            <i class="mdi mdi-magnify"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-3">
                                    <label for="mapTypeFilter" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-shape me-1"></i>Jenis Peta
                                    </label>
                                    <select class="form-select filter-control" id="mapTypeFilter">
                                        <option value="">Semua Jenis</option>
                                        @foreach ($mapTypes as $mapType)
                                            <option value="{{ $mapType->id }}">{{ $mapType->nama }}</option>
                                        @endforeach
                                        <option value="0">- Tanpa Jenis -</option>
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-3">
                                    <label for="per_page" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-table-row me-1"></i>Tampilkan per halaman
                                    </label>
                                    <select class="form-select filter-control" id="per_page">
                                        <option value="25">25 data</option>
                                        <option value="50">50 data</option>
                                        <option value="100">100 data</option>
                                        <option value="200" selected>200 data</option>
                                        <option value="500">500 data</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Actions Bar -->
                    <div id="bulkActionsBar" class="alert alert-info d-none mb-3" role="alert">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="mdi mdi-checkbox-multiple-marked me-2"></i>
                                <span id="selectedCount">0</span> Layer dipilih
                            </div>
                            <div>
                                @can('spatial-layers.edit')
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="bulkUpdateMapType()">
                                        <i class="mdi mdi-shape-outline me-1"></i>
                                        Ubah Jenis Peta
                                    </button>
                                @endcan
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                                    <i class="mdi mdi-close me-1"></i>
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="spatialLayersTable" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <div class="checkbox-wrapper">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                            <label class="form-check-label" for="selectAll">
                                                <span class="visually-hidden">Select All</span>
                                            </label>
                                        </div>
                                    </th>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Jenis</th>
                                    <th style="width: 15%;">Style</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($layers->sortBy('name') as $layer)
                                    @php
                                        $categoryKey = $layer->category_node_id ? 'node:'.$layer->category_node_id : 'cat:'.$layer->category_id;
                                        $categoryLabel = $categoryPaths[$categoryKey] ?? '-';
                                    @endphp
                                    <tr data-layer-id="{{ $layer->id }}" data-map-type-id="{{ $layer->map_type_id ?? '0' }}" class="spatial-layer-row">
                                        <td>
                                            <div class="checkbox-wrapper">
                                                <input class="form-check-input row-checkbox" type="checkbox" value="{{ $layer->id }}" id="check-{{ $layer->id }}">
                                                <label class="form-check-label" for="check-{{ $layer->id }}"><span class="visually-hidden">Select row</span></label>
                                            </div>
                                        </td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="text-dark fw-bold">{{ $layer->name }}</span>
                                            @if ($layer->short_description)
                                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($layer->short_description, 50) }}</small>
                                            @endif
                                            @if ($layer->features_count > 0)
                                                <span class="badge bg-success text-white ms-2" style="font-size:0.65em;">{{ $layer->features_count }} data</span>
                                            @endif
                                        </td>
                                        <td><small class="text-muted">{{ $categoryLabel }}</small></td>
                                        <td>{!! $layer->mapType?->nama ? '<span class="badge bg-primary text-white">'.e($layer->mapType->nama).'</span>' : '<span class="text-muted">-</span>' !!}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                @if ($layer->color)
                                                    <span class="color-box" style="display:inline-block;background-color:{{ $layer->color }};width:18px;height:18px;border-radius:4px;border:1px solid #dee2e6;box-shadow:0 1px 3px rgba(0,0,0,0.1);" title="{{ $layer->color }}"></span>
                                                @endif
                                                @if ($layer->is_marker && $layer->icon)
                                                    <i class="{{ $layer->icon }}" style="color:{{ $layer->color ?? '#007bff' }};font-size:1.2em;" title="{{ $layer->icon }}"></i>
                                                @endif
                                                @if ($layer->is_marker)
                                                    <span class="badge bg-warning text-dark"><i class="mdi mdi-map-marker"></i> Marker</span>
                                                @else
                                                    <span class="badge bg-info text-white"><i class="mdi mdi-layers"></i> Layer</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('spatial-layers.show', $layer->id) }}" class="btn btn-sm btn-outline-primary" title="Detail"><i class="mdi mdi-eye"></i></a>
                                                <form action="{{ route('spatial-layers.destroy', $layer->id) }}" method="POST" style="display:inline-block" data-confirm="delete" data-name="{{ $layer->name }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="mdi mdi-delete"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @if ($layers->count() == 0)
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="mdi mdi-layers-outline mdi-48px text-muted"></i>
                                            <h5 class="text-muted mt-2">Belum ada Layer yang dibuat</h5>
                                            <p class="text-muted">Klik tombol "Tambah Layer" untuk memulai</p>
                                            @can('spatial-layers.create')
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#addLayerModal">
                                                    <i class="mdi mdi-plus"></i> Tambah Layer Pertama
                                                </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Update Jenis Peta Modal -->
    <div class="modal fade" id="bulkMapTypeModal" tabindex="-1" aria-labelledby="bulkMapTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="bulkMapTypeModalLabel">
                        <i class="mdi mdi-shape-outline me-2"></i>Ubah Jenis Peta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Mengubah Jenis Peta untuk <strong id="bulkMapTypeCount">0</strong> Layer yang dipilih.
                    </p>
                    <label for="bulkMapTypeSelect" class="form-label fw-semibold">Jenis Peta Baru</label>
                    <select class="form-select" id="bulkMapTypeSelect">
                        <option value="">-- Pilih Jenis Peta --</option>
                        @foreach ($mapTypes as $mapType)
                            <option value="{{ $mapType->id }}">{{ $mapType->nama }}</option>
                        @endforeach
                    </select>
                    <div id="bulkMapTypeError" class="text-danger small mt-2 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmBulkMapType">
                        <i class="mdi mdi-check me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Update Jenis Peta Form (Hidden) -->
    <form id="bulkMapTypeForm" method="POST" action="{{ route('spatial-layers.bulk-update-map-type') }}"
        style="display: none;">
        @csrf
        @method('PUT')
        <div id="bulkMapTypeIds"></div>
        <input type="hidden" name="map_type_id" id="bulkMapTypeSelectInput">
    </form>

    <!-- Tambah Layer Modal (pola sama dengan modal edit Layer di spatial-layers/show.blade.php
         dan modal tambah Kategori di categories/index.blade.php) -->
    <div class="modal fade" id="addLayerModal" tabindex="-1" aria-labelledby="addLayerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLayerModalLabel">
                        <i class="mdi mdi-plus"></i> Tambah Layer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="addLayerForm" method="POST" action="{{ route('spatial-layers.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <!-- LEFT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="layer_add_map_type_id" class="form-label">Jenis <span class="text-danger">*</span></label>
                                    <select class="form-control" id="layer_add_map_type_id" name="map_type_id" required>
                                        <option value="">-- Pilih Jenis --</option>
                                        @foreach ($mapTypes as $mapType)
                                            <option value="{{ $mapType->id }}" @selected(old('map_type_id') == $mapType->id)>{{ $mapType->nama }}</option>
                                        @endforeach
                                    </select>
                                    @error('map_type_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group mb-2">
                                    <label for="layer_add_name" class="form-label">Nama Layer <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="layer_add_name" name="name" value="{{ old('name') }}" required>
                                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group mb-2">
                                    <label for="layer_add_description" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="layer_add_description" name="description" rows="2">{{ old('description') }}</textarea>
                                    @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                @include('backend.pages.spatial-layers._category-picker', ['prefix' => 'layer_add'])

                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="layer_add_warna" class="form-label">Warna</label>
                                    <div class="color-picker-widget">
                                        <div class="color-swatch-list" id="layer_add_colorSwatches">
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
                                                id="layer_add_warna" name="color" value="{{ old('color', '#007bff') }}">
                                            <input type="text" class="form-control text-uppercase" id="layer_add_warnaHex"
                                                maxlength="7" placeholder="#RRGGBB" autocomplete="off"
                                                value="{{ strtoupper(old('color', '#007bff')) }}">
                                        </div>
                                        @error('color') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="layer_add_opacity" class="form-label">Opacity</label>
                                    <input type="number" class="form-control" id="layer_add_opacity" name="opacity"
                                        step="0.1" min="0" max="1" value="{{ old('opacity', 1) }}">
                                    @error('opacity') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label d-block">Status & Jenis Layer</label>
                                    <div class="settings-switch-group">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_active" value="0">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="layer_add_is_active" name="is_active" @checked(old('is_active', true))>
                                            <label class="form-check-label" for="layer_add_is_active">
                                                <i class="mdi mdi-check-circle text-success me-1"></i>Aktifkan Layer
                                            </label>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_marker" value="0">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="layer_add_is_marker" name="is_marker" @checked(old('is_marker'))>
                                            <label class="form-check-label" for="layer_add_is_marker">
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
                                <div class="form-group mb-0" id="layer_add_iconContainer" style="display: none;">
                                    <label class="form-label">
                                        <i class="mdi mdi-map-marker me-1"></i> Ikon Marker
                                    </label>

                                    <!-- Hidden select acts as the source of truth & data source for the picker -->
                                    <select id="layer_add_icon" name="icon" class="d-none">
                                        <option value="">-- Pilih Ikon --</option>
                                        @include('backend.partials.icon-options')
                                    </select>

                                    <div class="row g-2 mb-2">
                                        <div class="col-md-7">
                                            <div class="icon-picker-search h-100">
                                                <div class="input-group input-group-sm h-100">
                                                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                                    <input type="text" class="form-control" id="layer_add_iconSearch"
                                                        placeholder="Cari ikon berdasarkan nama atau class...">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div id="layer_add_iconPreview" class="icon-preview-container icon-preview-inline">
                                                <span class="text-muted">Pilih ikon untuk melihat pratinjau</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="icon-picker-grid" id="layer_add_iconGrid"></div>

                                    <div class="form-text">Ikon hanya berlaku untuk Layer bertipe marker. Klik salah satu ikon di atas untuk memilih.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/select2/select2.min.css') }}">
    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
            border: 1px solid var(--admin-border, #dee2e6);
            border-radius: 0.375rem;
            background: var(--admin-surface, #fff);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(1.5em + 0.75rem);
            color: var(--admin-text, #212529);
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + 0.75rem);
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            padding: 8px;
        }

        #bulkActionsBar {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border: 1px solid #2196f3;
            border-radius: 8px;
            color: #1976d2;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #495057;
            padding: 10px 8px;
        }

        .table td {
            vertical-align: middle;
            padding: 10px 8px;
            border-bottom: 1px solid #eef2f7;
            font-size: 0.85rem;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .stats-row-compact {
            margin-bottom: 1rem;
        }

        .stat-card-compact {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
        }

        .stat-card-compact .card-body {
            padding: 0.85rem 1rem;
        }

        .stat-card-compact .stat-label {
            margin: 0 0 2px;
            font-size: 0.75rem;
            font-weight: 500;
            opacity: 0.9;
            white-space: nowrap;
        }

        .stat-card-compact .stat-value {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .stat-card-compact .stat-icon {
            font-size: 2rem;
            opacity: 0.5;
        }

        /* ===========================================
           ICON & COLOR PICKER — modal Tambah Layer, disamakan dengan modal edit
           Layer di spatial-layers/show.blade.php (prefix "layer_add_" di sini).
           =========================================== */
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
    <script src="{{ asset('backend/assets/vendors/select2/select2.min.js') }}"></script>
    <script>
        let selectedLayerItems = [];

        $(document).ready(function () {
            $('#bulkMapTypeSelect').select2({
                dropdownParent: $('#bulkMapTypeModal'),
                placeholder: '-- Pilih Jenis Peta --',
                width: '100%',
                allowClear: true,
                language: {
                    noResults: () => 'Jenis Peta tidak ditemukan',
                    searching: () => 'Mencari...',
                },
            }).on('change', function () {
                document.getElementById('bulkMapTypeError').classList.add('d-none');
            });

            $('#bulkMapTypeModal').on('shown.bs.modal', function () {
                $('#bulkMapTypeSelect').select2('open');
            });

            $(document).on('change', '#selectAll', function () {
                const isChecked = this.checked;

                $('.row-checkbox').each(function () {
                    this.checked = isChecked;
                    const value = this.value;

                    if (isChecked && !selectedLayerItems.includes(value)) {
                        selectedLayerItems.push(value);
                    } else if (!isChecked) {
                        selectedLayerItems = selectedLayerItems.filter((id) => id !== value);
                    }

                    $(this).closest('tr').toggleClass('table-active', isChecked);
                });

                updateBulkActionsBar();
            });

            $(document).on('change', '.row-checkbox', function () {
                const value = this.value;

                if (this.checked) {
                    if (!selectedLayerItems.includes(value)) {
                        selectedLayerItems.push(value);
                    }
                } else {
                    selectedLayerItems = selectedLayerItems.filter((id) => id !== value);
                }

                $(this).closest('tr').toggleClass('table-active', this.checked);
                updateBulkActionsBar();
                updateSelectAllState();
            });

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex, rowData, counter) {
                if (settings.nTable.id !== 'spatialLayersTable') {
                    return true;
                }

                const selected = $('#mapTypeFilter').val();
                if (!selected) {
                    return true;
                }

                const row = settings.aoData[dataIndex].nTr;
                return $(row).data('map-type-id').toString() === selected;
            });

            const table = $('#spatialLayersTable').DataTable({
                "processing": true,
                "pageLength": 200,
                "lengthMenu": [[10, 25, 50, 100, 200, 500], [10, 25, 50, 100, 200, 500]],
                "ordering": false,
                "columnDefs": [
                    { "searchable": false, "orderable": false, "targets": [0, -1] },
                    { "className": "text-center", "targets": [0, 1, -1] },
                ],
                "language": {
                    "processing": "<div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div>",
                    "lengthMenu": "Tampilkan _MENU_ layer per halaman",
                    "zeroRecords": "Layer tidak ditemukan",
                    "emptyTable": "Tidak ada Layer tersedia",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ Layer",
                    "infoEmpty": "Menampilkan 0 sampai 0 dari 0 Layer",
                    "infoFiltered": "(difilter dari _MAX_ total Layer)",
                    "search": "Cari Layer:",
                    "paginate": { "first": "Pertama", "last": "Terakhir", "next": "Selanjutnya", "previous": "Sebelumnya" },
                },
                "dom": '<"row"<"col-sm-12"tr>>' +
                    '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "initComplete": function () {
                    $('#searchTableBtn').on('click', function () {
                        table.search($('#tableSearch').val()).draw();
                    });
                    $('#tableSearch').on('input', function () {
                        table.search($(this).val()).draw();
                    });
                    $('#per_page').on('change', function () {
                        table.page.len(parseInt($(this).val(), 10)).draw();
                    });
                    $('#mapTypeFilter').on('change', function () {
                        table.draw();
                    });
                }
            });
        });

        function updateBulkActionsBar() {
            const bulkActionsBar = document.getElementById('bulkActionsBar');
            const selectedCount = document.getElementById('selectedCount');

            if (selectedLayerItems.length > 0) {
                bulkActionsBar.classList.remove('d-none');
                selectedCount.textContent = selectedLayerItems.length;
            } else {
                bulkActionsBar.classList.add('d-none');
            }
        }

        function updateSelectAllState() {
            const checkboxes = $('.row-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            let checkedCount = 0;

            checkboxes.each(function () {
                if (this.checked) checkedCount++;
            });

            if (checkedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === checkboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        function clearSelection() {
            selectedLayerItems = [];
            document.querySelectorAll('.row-checkbox').forEach((cb) => {
                cb.checked = false;
                $(cb).closest('tr').removeClass('table-active');
            });
            document.getElementById('selectAll').checked = false;
            document.getElementById('selectAll').indeterminate = false;
            updateBulkActionsBar();
        }

        function bulkUpdateMapType() {
            if (selectedLayerItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak ada Layer terpilih',
                    text: 'Silakan pilih Layer yang akan diubah Jenis Petanya terlebih dahulu',
                    confirmButtonText: 'OK',
                });
                return;
            }

            document.getElementById('bulkMapTypeCount').textContent = selectedLayerItems.length;
            $('#bulkMapTypeSelect').val('').trigger('change');
            const errorBox = document.getElementById('bulkMapTypeError');
            errorBox.classList.add('d-none');
            errorBox.textContent = '';

            const modal = new bootstrap.Modal(document.getElementById('bulkMapTypeModal'));
            modal.show();
        }

        document.getElementById('confirmBulkMapType').addEventListener('click', function () {
            const select = document.getElementById('bulkMapTypeSelect');
            const errorBox = document.getElementById('bulkMapTypeError');

            if (selectedLayerItems.length === 0) {
                errorBox.textContent = 'Tidak ada Layer yang dipilih untuk diubah.';
                errorBox.classList.remove('d-none');
                return;
            }

            if (!select.value) {
                errorBox.textContent = 'Silakan pilih Jenis Peta tujuan.';
                errorBox.classList.remove('d-none');
                return;
            }

            errorBox.classList.add('d-none');

            this.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>Menyimpan...';
            this.disabled = true;

            const bulkMapTypeIds = document.getElementById('bulkMapTypeIds');
            bulkMapTypeIds.innerHTML = '';

            selectedLayerItems.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkMapTypeIds.appendChild(input);
            });

            document.getElementById('bulkMapTypeSelectInput').value = select.value;

            document.getElementById('bulkMapTypeForm').submit();
        });
    </script>

    <script>
        /**
         * Icon-picker & color-picker modal Tambah Layer — pola & markup sama persis
         * dengan modal edit Layer di spatial-layers/show.blade.php (prefix "layer_edit_"
         * di sana, "layer_add_" di sini), yang pada gilirannya disamakan dengan modal
         * Kategori di categories/index.blade.php.
         */
        $(function () {
            const prefix = 'layer_add';

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

            // Reset form tiap kali modal "Tambah Layer" dibuka (beda dari modal edit
            // yang selalu pra-isi data Layer tertentu — di sini harus mulai kosong
            // supaya tidak membawa sisa input percobaan sebelumnya).
            $(`#addLayerModal`).on('show.bs.modal', function () {
                if (document.querySelector('#addLayerForm [name=name]').value) {
                    // Ada old-input (submit sebelumnya gagal validasi) — biarkan terisi
                    // supaya user tidak perlu mengetik ulang, cukup sinkronkan picker-nya.
                } else {
                    $(`#${prefix}_iconContainer`).hide();
                    $(`#${prefix}_iconGrid .icon-picker-item`).removeClass('active').show();
                    $(`#${prefix}_iconSearch`).val('');
                }

                const initialColor = ($(`#${prefix}_warna`).val() || '#007bff');
                applyColor(initialColor);

                if ($(`#${prefix}_is_marker`).is(':checked')) {
                    $(`#${prefix}_iconContainer`).show();
                    updateIconPreview($(`#${prefix}_icon`).val(), initialColor);
                    $(`#${prefix}_iconGrid .icon-picker-item[data-icon-value="${$(`#${prefix}_icon`).val()}"]`).addClass('active');
                }
            });

            @if ($errors->any() && old('name') !== null)
                const addLayerModalEl = document.getElementById('addLayerModal');
                if (addLayerModalEl && window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(addLayerModalEl).show();
                }
            @endif
        });
    </script>
@endpush
