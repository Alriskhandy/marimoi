@extends('backend.partials.main', ['title' => 'Detail Layer'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-layers"></i></span>
            {{ $layer->name }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('spatial-layers.index') }}">Daftar Layer & Data</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $layer->name }}</li>
            </ul>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row g-3 stats-row-compact">
        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Jenis Layer</p>
                            <h3 class="stat-value" style="font-size:1.15rem;">{{ $layer->layerType?->name ?? '-' }}</h3>
                        </div>
                        <i class="mdi mdi-shape stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Kategori</p>
                            @php
                                $categoryKey = $layer->category_node_id ? 'node:'.$layer->category_node_id : 'cat:'.$layer->category_id;
                            @endphp
                            <h3 class="stat-value" style="font-size:1rem;">{{ $categoryPaths[$categoryKey] ?? '-' }}</h3>
                        </div>
                        <i class="mdi mdi-shape-outline stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Data Spasial</p>
                            <h3 class="stat-value">{{ $layer->features->count() }}</h3>
                        </div>
                        <i class="mdi mdi-map-marker-multiple stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div
                class="card stat-card-compact {{ $layer->is_active ? 'bg-gradient-warning' : 'bg-gradient-secondary' }} text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Status</p>
                            <h3 class="stat-value" style="font-size:1.15rem;">
                                {{ $layer->is_active ? 'Aktif' : 'Nonaktif' }}</h3>
                        </div>
                        <i class="mdi {{ $layer->is_active ? 'mdi-check-circle' : 'mdi-pause-circle' }} stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                        <div>
                            <p class="card-title mb-1">Informasi Layer</p>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                @if ($layer->color)
                                    <span
                                        style="display:inline-block;width:16px;height:16px;border-radius:4px;background:{{ $layer->color }};border:1px solid #dee2e6;"></span>
                                @endif
                                @if ($layer->is_marker && $layer->icon)
                                    <i class="{{ $layer->icon }}"
                                        style="color:{{ $layer->color ?? '#007bff' }};font-size:1.2em;"></i>
                                @endif
                                <span
                                    class="badge {{ $layer->is_marker ? 'bg-warning text-dark' : 'bg-info text-white' }}">
                                    {{ $layer->is_marker ? 'Marker' : 'Layer' }}
                                </span>
                                <span class="badge bg-light text-dark border">
                                    <i class="mdi mdi-shape-outline"></i>
                                    {{ $categoryPaths[$categoryKey] ?? '-' }}
                                </span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('spatial-layers.metadata.edit', $layer) }}" class="btn btn-sm btn-outline-info" title="Metadata">
                                <i class="mdi mdi-file-document-outline"></i> Metadata
                            </a>
                            <a href="{{ route('spatial-layers.imports.index', $layer) }}" class="btn btn-sm btn-outline-secondary" title="Riwayat Impor">
                                <i class="mdi mdi-history"></i> Riwayat Impor
                            </a>
                            <a href="{{ route('spatial-layers.styles.index', $layer) }}" class="btn btn-sm btn-outline-secondary" title="Style">
                                <i class="mdi mdi-palette-outline"></i> Style
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal"
                                data-bs-target="#editLayerModal" title="Edit">
                                <i class="mdi mdi-pencil"></i> Edit
                            </button>
                            @if ($layer->features->isEmpty())
                                <form action="{{ route('spatial-layers.destroy', $layer) }}" method="POST"
                                    style="display:inline-block;" data-confirm="delete" data-name="{{ $layer->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Layer">
                                        <i class="mdi mdi-delete"></i> Hapus
                                    </button>
                                </form>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                                data-bs-target="#layerInfoCollapse" aria-expanded="false" aria-controls="layerInfoCollapse"
                                id="layerInfoToggle">
                                <i class="mdi mdi-arrow-expand"></i> Detail
                            </button>
                        </div>
                    </div>
                    <div class="collapse" id="layerInfoCollapse">
                        <hr class="mt-0">
                        <table class="table table-sm">
                            <tr>
                                <th style="width:200px;">Nama</th>
                                <td>{{ $layer->name }}</td>
                            </tr>
                            <tr>
                                <th>Deskripsi</th>
                                <td>{{ $layer->short_description ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Kategori</th>
                                <td>{{ $categoryPaths[$categoryKey] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Style</th>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($layer->color)
                                            <span
                                                style="display:inline-block;width:20px;height:20px;border-radius:4px;background:{{ $layer->color }};border:1px solid #dee2e6;"></span>
                                            <small>{{ $layer->color }}</small>
                                        @endif
                                        @if ($layer->is_marker && $layer->icon)
                                            <i class="{{ $layer->icon }}"
                                                style="color:{{ $layer->color ?? '#007bff' }};font-size:1.3em;"></i>
                                        @endif
                                        <span
                                            class="badge {{ $layer->is_marker ? 'bg-warning text-dark' : 'bg-info text-white' }}">
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
                            <div class="btn-group" role="group" aria-label="Ganti tampilan">
                                <button type="button" class="btn btn-sm btn-outline-primary active"
                                    id="viewModeTableBtn" onclick="setDataViewMode('table')">
                                    <i class="mdi mdi-table"></i> Tabel
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="viewModeMapBtn"
                                    onclick="setDataViewMode('map')">
                                    <i class="mdi mdi-map"></i> Peta
                                </button>
                            </div>
                            <a href="{{ route('spatial-layers.features.create', $layer) }}"
                                class="btn btn-sm btn-gradient-primary text-nowrap">
                                <i class="mdi mdi-map-marker-plus"></i> Tambah Data
                            </a>
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                        <div class="input-group" style="max-width: 320px;">
                            <input type="text" class="form-control form-control-sm" id="dataSpasialSearchInput"
                                placeholder="Cari kode, wilayah...">
                            <button type="button" class="btn btn-sm btn-primary" id="dataSpasialSearchBtn">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                        </div>
                        @if ($dynamicAttributes->isNotEmpty())
                            <select class="form-select form-select-sm" id="dataSpasialAttributeFilterField" style="width: auto;">
                                <option value="">Filter per Atribut...</option>
                                @foreach ($dynamicAttributes as $attribute)
                                    <option value="{{ $attribute->metadataDefinition->kode }}">{{ $attribute->metadataDefinition->label }}</option>
                                @endforeach
                            </select>
                            <input type="text" class="form-control form-control-sm d-none" id="dataSpasialAttributeFilterValue"
                                placeholder="Nilai..." style="width: 160px;">
                        @endif
                        <select class="form-select form-select-sm" id="dataSpasialPerPage" style="width: auto;">
                            <option value="10">10 / halaman</option>
                            <option value="25" selected>25 / halaman</option>
                            <option value="50">50 / halaman</option>
                            <option value="100">100 / halaman</option>
                        </select>
                    </div>

                    <!-- Bulk Actions Bar (§5.7 butir 3 — porting dari Data Spasial lama) -->
                    <div id="featureBulkActionsBar" class="alert alert-info d-none mb-3" role="alert">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <i class="mdi mdi-checkbox-multiple-marked me-2"></i>
                                <span id="featureSelectedCount">0</span> Data Spasial dipilih
                            </div>
                            <div>
                                @can('spatial-layers.edit')
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="bulkEditFeatureAttribute()">
                                        <i class="mdi mdi-pencil-box-multiple-outline me-1"></i> Ubah Atribut
                                    </button>
                                @endcan
                                @can('spatial-layers.delete')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDeleteFeatures()">
                                        <i class="mdi mdi-delete me-1"></i> Hapus Terpilih
                                    </button>
                                @endcan
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearFeatureSelection()">
                                    <i class="mdi mdi-close me-1"></i> Batal
                                </button>
                            </div>
                        </div>
                    </div>

                    @php
                        // Pesan "kosong" dirender lewat opsi emptyTable DataTables (bukan baris
                        // <tr> statis di tbody seperti sebelumnya) — baris statis itu ikut
                        // dihitung DataTables sebagai 1 data sungguhan, jadi info paginasi
                        // sempat menampilkan "Menampilkan 1 sampai 1 dari 1 Data Spasial" padahal
                        // sebenarnya nol (lihat screenshot bug yang dilaporkan user).
                        $dataSpasialEmptyHtml = '<div class="text-center py-4 text-muted">'
                            .'<i class="mdi mdi-map-marker-off-outline mdi-36px text-muted d-block mb-2"></i>'
                            .'Belum ada Data Spasial.';
                        if (auth()->user()?->can('spatial-layers.create')) {
                            $dataSpasialEmptyHtml .= '<br><a href="'.e(route('spatial-layers.features.create', $layer)).'" class="btn btn-sm btn-primary mt-2">'
                                .'<i class="mdi mdi-map-marker-plus"></i> Tambah Data Spasial Pertama</a>';
                        }
                        $dataSpasialEmptyHtml .= '</div>';

                        // Dikenal dari dua sumber: kode atribut dinamis Jenis Peta layer, DAN
                        // nama kunci mentah hasil impor yang sudah ada di properties fitur —
                        // bulk edit atribut bisa menyasar keduanya (sama seperti modul lama yang
                        // bisa ubah sembarang kolom DBF, bukan cuma atribut terdefinisi).
                        $knownAttributeKeys = $dynamicAttributes->pluck('metadataDefinition.kode')
                            ->merge($layer->features->flatMap(fn ($f) => array_keys($f->properties ?? [])))
                            ->filter()->unique()->sort()->values();
                    @endphp

                    <!-- TABEL -->
                    <div id="dataSpasialTableView">
                        <div class="table-responsive">
                            <table class="table table-striped" id="dataSpasialLayerTable">
                                <thead>
                                    <tr>
                                        <th style="width: 36px;">
                                            <div class="checkbox-wrapper">
                                                <input class="form-check-input" type="checkbox" id="featureSelectAll">
                                                <label class="form-check-label" for="featureSelectAll">
                                                    <span class="visually-hidden">Select All</span>
                                                </label>
                                            </div>
                                        </th>
                                        <th>No</th>
                                        <th style="width:56px;">Gambar</th>
                                        <th>Kode</th>
                                        <th>Wilayah</th>
                                        <th>Tanggal Input</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($layer->features as $feature)
                                        @php
                                            $featureMetadataRows = $dynamicAttributes->map(function ($attribute) use ($feature) {
                                                $definition = $attribute->metadataDefinition;

                                                return [
                                                    'kode' => $definition->kode,
                                                    'label' => $definition->label,
                                                    'satuan' => $definition->satuan,
                                                    'value' => $feature->properties[$definition->kode] ?? null,
                                                ];
                                            })->values();

                                            $featureDetailPayload = [
                                                'kode' => $feature->label ?? '#'.$feature->id,
                                                'wilayah' => $feature->region->name ?? null,
                                                'gambar' => $feature->gambar ? asset('storage/'.$feature->gambar) : null,
                                                'tanggal_input' => $feature->created_at?->format('d M Y H:i'),
                                                'metadata' => $featureMetadataRows,
                                                'edit_url' => route('spatial-layers.features.edit', [$layer, $feature]),
                                                'delete_url' => route('spatial-layers.features.destroy', [$layer, $feature]),
                                                'delete_name' => $feature->label ?? '#'.$feature->id,
                                            ];
                                        @endphp
                                        <tr data-feature-row data-feature="{{ json_encode($featureDetailPayload) }}"
                                            style="cursor: pointer;" title="Klik untuk lihat detail">
                                            <td>
                                                <div class="checkbox-wrapper">
                                                    <input class="form-check-input feature-row-checkbox" type="checkbox"
                                                        value="{{ $feature->id }}" id="check-feature-{{ $feature->id }}">
                                                    <label class="form-check-label" for="check-feature-{{ $feature->id }}">
                                                        <span class="visually-hidden">Select row</span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="text-center">
                                                @if ($feature->gambar)
                                                    <img src="{{ asset('storage/' . $feature->gambar) }}" alt="Gambar"
                                                        class="img-thumbnail"
                                                        style="width:40px;height:40px;object-fit:cover;">
                                                @else
                                                    <i class="mdi mdi-image-off-outline text-muted"
                                                        style="font-size:1.3rem;"></i>
                                                @endif
                                            </td>
                                            <td>{{ $feature->label ?? '-' }}</td>
                                            <td>{{ $feature->region->name ?? '-' }}</td>
                                            <td class="text-center">{{ $feature->created_at?->format('d M Y') ?? '-' }}
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="{{ route('spatial-layers.features.edit', [$layer, $feature]) }}"
                                                        class="btn btn-sm btn-outline-warning" title="Kelola">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <form
                                                        action="{{ route('spatial-layers.features.destroy', [$layer, $feature]) }}"
                                                        method="POST" style="display:inline-block;"
                                                        data-confirm="delete"
                                                        data-name="{{ $feature->label ?? '#' . $feature->id }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            title="Hapus">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- PETA -->
                    <div id="dataSpasialMapView" style="display:none;">
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

    <div class="modal fade" id="editLayerModal" tabindex="-1" aria-labelledby="editLayerModalLabel"
        aria-hidden="true">
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
                                    <label for="layer_edit_layer_type_id" class="form-label">Jenis Layer</label>
                                    <select class="form-control" id="layer_edit_layer_type_id" name="layer_type_id">
                                        @foreach ($layerTypes as $layerType)
                                            <option value="{{ $layerType->id }}" @selected(old('layer_type_id', $layer->layer_type_id) == $layerType->id)>
                                                {{ $layerType->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('layer_type_id')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-2">
                                    <label for="layer_edit_name" class="form-label">Nama Layer <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="layer_edit_name" name="name"
                                        value="{{ old('name', $layer->name) }}" required>
                                    @error('name')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-2">
                                    <label for="layer_edit_short_description" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="layer_edit_short_description" name="short_description" rows="2">{{ old('short_description', $layer->short_description) }}</textarea>
                                    @error('short_description')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>

                                @include('backend.pages.spatial-layers._category-picker', [
                                    'prefix' => 'layer_edit',
                                    'selectedCategoryId' => $layer->category_id,
                                    'selectedCategoryNodeId' => $layer->category_node_id,
                                ])

                                <div class="form-group mb-2">
                                    <label for="layer_edit_opd_id" class="form-label">OPD Pemilik</label>
                                    @if ($opds->isNotEmpty())
                                        <select class="form-control" id="layer_edit_opd_id" name="opd_id">
                                            <option value="">-- Provinsi/Bappeda --</option>
                                            @foreach ($opds as $opd)
                                                <option value="{{ $opd->id }}" @selected(old('opd_id', $layer->opd_id) == $opd->id)>
                                                    {{ $opd->singkatan }} - {{ $opd->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" class="form-control" value="{{ $layer->opd?->name ?? '-' }}" disabled>
                                        <div class="form-text">OPD pemilik otomatis mengikuti OPD Anda.</div>
                                    @endif
                                    @error('opd_id')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="layer_edit_warna" class="form-label">Warna</label>
                                    <div class="color-picker-widget">
                                        <div class="color-swatch-list" id="layer_edit_colorSwatches">
                                            <button type="button" class="color-swatch" data-color="#007bff"
                                                style="background-color:#007bff" title="#007bff"></button>
                                            <button type="button" class="color-swatch" data-color="#28a745"
                                                style="background-color:#28a745" title="#28a745"></button>
                                            <button type="button" class="color-swatch" data-color="#dc3545"
                                                style="background-color:#dc3545" title="#dc3545"></button>
                                            <button type="button" class="color-swatch" data-color="#ffc107"
                                                style="background-color:#ffc107" title="#ffc107"></button>
                                            <button type="button" class="color-swatch" data-color="#17a2b8"
                                                style="background-color:#17a2b8" title="#17a2b8"></button>
                                            <button type="button" class="color-swatch" data-color="#6f42c1"
                                                style="background-color:#6f42c1" title="#6f42c1"></button>
                                            <button type="button" class="color-swatch" data-color="#fd7e14"
                                                style="background-color:#fd7e14" title="#fd7e14"></button>
                                            <button type="button" class="color-swatch" data-color="#20c997"
                                                style="background-color:#20c997" title="#20c997"></button>
                                            <button type="button" class="color-swatch" data-color="#6c757d"
                                                style="background-color:#6c757d" title="#6c757d"></button>
                                            <button type="button" class="color-swatch" data-color="#212529"
                                                style="background-color:#212529" title="#212529"></button>
                                        </div>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color"
                                                id="layer_edit_warna" name="color"
                                                value="{{ old('color', $layer->color ?? '#007bff') }}">
                                            <input type="text" class="form-control text-uppercase"
                                                id="layer_edit_warnaHex" maxlength="7" placeholder="#RRGGBB"
                                                autocomplete="off"
                                                value="{{ strtoupper(old('color', $layer->color ?? '#007bff')) }}">
                                        </div>
                                        @error('color')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="layer_edit_opacity" class="form-label">Opacity</label>
                                    <input type="number" class="form-control" id="layer_edit_opacity" name="default_opacity"
                                        step="0.1" min="0" max="1"
                                        value="{{ old('default_opacity', $layer->opacity ?? 1) }}">
                                    @error('default_opacity')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label d-block">Status & Jenis Layer</label>
                                    <div class="settings-switch-group">
                                        @can('spatial-layers.publish')
                                            <div class="form-check form-switch">
                                                <input type="hidden" name="is_active" value="0">
                                                <input class="form-check-input" type="checkbox" value="1"
                                                    id="layer_edit_is_active" name="is_active" @checked(old('is_active', $layer->is_active))>
                                                <label class="form-check-label" for="layer_edit_is_active">
                                                    <i class="mdi mdi-check-circle text-success me-1"></i>Aktifkan Layer
                                                </label>
                                            </div>
                                        @else
                                            <p class="text-muted small mb-2">
                                                Status: <strong>{{ $layer->status }}</strong> — hanya super-admin/admin-bappeda
                                                yang bisa mengubah status Layer.
                                            </p>
                                        @endcan

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

                                <div class="row mt-2">
                                    <div class="col-4">
                                        <label for="layer_edit_sort_order" class="form-label">Urutan Tampil</label>
                                        <input type="number" class="form-control" id="layer_edit_sort_order" name="sort_order"
                                            min="0" value="{{ old('sort_order', $layer->sort_order ?? 0) }}">
                                        @error('sort_order')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
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
                                            <div id="layer_edit_iconPreview"
                                                class="icon-preview-container icon-preview-inline">
                                                <span class="text-muted">Pilih ikon untuk melihat pratinjau</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="icon-picker-grid" id="layer_edit_iconGrid"></div>

                                    <div class="form-text">Ikon hanya berlaku untuk Layer marker. Klik salah satu ikon
                                        di atas untuk memilih.</div>
                                    @error('icon')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
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

    <!-- DETAIL DATA SPASIAL (dibuka saat baris tabel diklik, tanpa pindah halaman) -->
    <div class="modal fade" id="featureDetailModal" tabindex="-1" aria-labelledby="featureDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="featureDetailModalLabel">
                        <i class="mdi mdi-map-marker"></i> Detail Data Spasial
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3" id="featureDetailImageWrap" style="display:none;">
                        <img src="" alt="Gambar" id="featureDetailImage" class="img-thumbnail"
                            style="max-height:160px;object-fit:cover;">
                    </div>
                    <table class="table table-sm mb-3">
                        <tr>
                            <th style="width:200px;">Kode</th>
                            <td id="featureDetailKode">-</td>
                        </tr>
                        <tr>
                            <th>Wilayah</th>
                            <td id="featureDetailWilayah">-</td>
                        </tr>
                        <tr>
                            <th>Tanggal Input</th>
                            <td id="featureDetailTanggal">-</td>
                        </tr>
                    </table>
                    <p class="card-title mb-2" style="font-size:0.95rem;">Metadata Dinamis</p>
                    <table class="table table-sm" id="featureDetailMetadataTable">
                        <tbody id="featureDetailMetadata"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <form id="featureDetailDeleteForm" action="" method="POST" style="display:inline-block;"
                        data-confirm="delete" data-name="">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="mdi mdi-delete"></i> Hapus
                        </button>
                    </form>
                    <a href="" id="featureDetailEditBtn" class="btn btn-gradient-warning">
                        <i class="mdi mdi-pencil"></i> Kelola
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Edit Atribut Modal (§5.7 butir 3) -->
    <div class="modal fade" id="bulkFeatureAttributeModal" tabindex="-1" aria-labelledby="bulkFeatureAttributeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="bulkFeatureAttributeModalLabel">
                        <i class="mdi mdi-pencil-box-multiple-outline me-2"></i>Ubah Atribut
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Mengubah satu atribut untuk <strong id="bulkFeatureAttributeCount">0</strong> Data Spasial yang dipilih.
                    </p>

                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="bulkFeatureAction" id="bulkFeatureActionSet" value="set" checked>
                            <label class="form-check-label" for="bulkFeatureActionSet">Set (isi/timpa)</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="bulkFeatureAction" id="bulkFeatureActionRemove" value="remove">
                            <label class="form-check-label" for="bulkFeatureActionRemove">Hapus atribut ini</label>
                        </div>
                    </div>

                    <label for="bulkFeatureKey" class="form-label fw-semibold">Nama Atribut</label>
                    <input type="text" class="form-control" id="bulkFeatureKey" list="bulkFeatureKeyList" placeholder="mis. kondisi, sumber_dana">
                    <datalist id="bulkFeatureKeyList">
                        @foreach ($knownAttributeKeys as $key)
                            <option value="{{ $key }}"></option>
                        @endforeach
                    </datalist>
                    <div class="form-text">Huruf, angka, underscore — diawali huruf/underscore.</div>

                    <div class="mt-3" id="bulkFeatureValueWrapper">
                        <label for="bulkFeatureValue" class="form-label fw-semibold">Nilai Baru</label>
                        <input type="text" class="form-control" id="bulkFeatureValue">
                    </div>

                    <div id="bulkFeatureAttributeError" class="text-danger small mt-2 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmBulkFeatureAttribute">
                        <i class="mdi mdi-check me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Edit Atribut Form (Hidden) -->
    <form id="bulkFeatureAttributeForm" method="POST" action="{{ route('spatial-layers.features.bulk-update-attribute', $layer) }}" style="display: none;">
        @csrf
        <div id="bulkFeatureAttributeIds"></div>
        <input type="hidden" name="action" id="bulkFeatureActionInput">
        <input type="hidden" name="key" id="bulkFeatureKeyInput">
        <input type="hidden" name="value" id="bulkFeatureValueInput">
    </form>

    <!-- Bulk Hapus Form (Hidden) -->
    <form id="bulkFeatureDestroyForm" method="POST" action="{{ route('spatial-layers.features.bulk-destroy', $layer) }}" style="display: none;" data-confirm="delete" data-name="Data Spasial terpilih">
        @csrf
        <div id="bulkFeatureDestroyIds"></div>
    </form>
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

        /* ===========================================
                           TABEL DATA SPASIAL — disamakan dengan tabel index
                           data_spatial/index.blade.php (header uppercase, hover terangkat, badge pill).
                        =========================================== */
        #dataSpasialTableView .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        #dataSpasialLayerTable th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #495057;
            padding: 12px 8px;
        }

        #dataSpasialLayerTable td {
            vertical-align: middle;
            padding: 12px 8px;
            border-bottom: 1px solid #eef2f7;
            font-size: 0.85rem;
        }

        #dataSpasialTableView .table tbody tr {
            transition: all 0.2s ease;
        }

        #dataSpasialTableView .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        #dataSpasialTableView .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        /* ===========================================
                           STATISTICS CARDS (COMPACT) — disamakan dengan
                           spatial-layers/index.blade.php & categories/index.blade.php
                        =========================================== */
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
        (function() {
            const layerSlug = @json($layer->slug);
            const layerColor = @json($layer->color ?? '#2563eb');
            const layerIcon = @json($layer->is_marker ? $layer->icon : null);
            const layerOpacity = @json($layer->opacity ?? 1);

            let map = null;

            // Peta di-init BARU saat pertama kali mode "Peta" dibuka, bukan langsung
            // saat halaman dimuat — container-nya mulai dalam keadaan display:none
            // (mode default tabel), dan Leaflet gagal menghitung ukuran peta yang
            // di-init di dalam container tersembunyi (hasilnya peta abu-abu kosong).
            function initMapOnce() {
                if (map) {
                    return;
                }

                map = L.map('layerDetailMap').setView([1.5, 127.8], 8);

                const esriImagery = L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        attribution: 'Tiles &copy; Esri'
                    }
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
                    if (!basemaps[name] || basemaps[name] === activeBasemap) {
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
                        if (!geojson) {
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
                            style: () => ({
                                color: layerColor,
                                weight: 2,
                                opacity: layerOpacity,
                                fillOpacity: layerOpacity * 0.4
                            }),
                            onEachFeature: (feature, leafletLayer) => {
                                const externalId = feature.properties?.external_id ??
                                    `#${feature.properties?.id ?? ''}`;
                                leafletLayer.bindPopup(String(externalId));
                            },
                        }).addTo(map);

                        if (dataLayer.getBounds().isValid()) {
                            map.fitBounds(dataLayer.getBounds(), {
                                maxZoom: 15
                            });
                        }
                    })
                    .catch((error) => console.error('Gagal memuat Data Spasial Layer ini', error));
            }

            // Toggle tabel <-> peta dalam SATU kartu (bukan dua kartu terpisah seperti
            // sebelumnya) — pola switcher-nya disamakan dengan tombol Tabel/Peta di
            // data_spatial/index.blade.php, bedanya di sini murni client-side (tidak
            // pindah halaman) karena datanya sudah sama-sama dimuat di kartu ini.
            window.setDataViewMode = function(mode) {
                const tableView = document.getElementById('dataSpasialTableView');
                const mapView = document.getElementById('dataSpasialMapView');
                const tableBtn = document.getElementById('viewModeTableBtn');
                const mapBtn = document.getElementById('viewModeMapBtn');

                if (mode === 'map') {
                    tableView.style.display = 'none';
                    mapView.style.display = '';
                    tableBtn.classList.remove('active');
                    mapBtn.classList.add('active');

                    initMapOnce();
                    // invalidateSize butuh container yang sudah display:block —
                    // ditunda 1 tick lewat requestAnimationFrame supaya browser
                    // sempat reflow dulu sebelum Leaflet menghitung ukurannya.
                    requestAnimationFrame(() => map && map.invalidateSize());
                } else {
                    mapView.style.display = 'none';
                    tableView.style.display = '';
                    mapBtn.classList.remove('active');
                    tableBtn.classList.add('active');
                }
            };

            const infoCollapseEl = document.getElementById('layerInfoCollapse');
            const infoToggleBtn = document.getElementById('layerInfoToggle');

            infoCollapseEl.addEventListener('shown.bs.collapse', () => {
                infoToggleBtn.innerHTML = '<i class="mdi mdi-arrow-collapse"></i> Collapse';
            });

            infoCollapseEl.addEventListener('hidden.bs.collapse', () => {
                infoToggleBtn.innerHTML = '<i class="mdi mdi-arrow-expand"></i> Expand';
            });

            // Tabel Data Spasial pakai DataTables (sama seperti tabel Daftar Layer di
            // spatial-layers/index.blade.php) supaya dapat pagination & info jumlah
            // data sungguhan, bukan cuma sembunyikan baris lewat JS manual seperti
            // sebelumnya — penting untuk Layer yang datanya ratusan baris.
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
                if (settings.nTable.id !== 'dataSpasialLayerTable') {
                    return true;
                }

                const row = settings.aoData[dataIndex].nTr;

                const attributeField = $('#dataSpasialAttributeFilterField').val();
                const attributeValue = $('#dataSpasialAttributeFilterValue').val().trim().toLowerCase();
                if (attributeField && attributeValue) {
                    const feature = $(row).data('feature');
                    const entry = (feature.metadata || []).find((m) => m.kode === attributeField);
                    const value = entry && entry.value !== null && entry.value !== undefined ? String(entry.value)
                        .toLowerCase() : '';
                    if (!value.includes(attributeValue)) {
                        return false;
                    }
                }

                return true;
            });

            const dataSpasialTable = $('#dataSpasialLayerTable').DataTable({
                pageLength: 25,
                lengthChange: false,
                ordering: false,
                columnDefs: [{
                        searchable: false,
                        orderable: false,
                        targets: [0, 2, -1]
                    },
                    {
                        className: 'text-center',
                        targets: [0, 1, 2, -1]
                    },
                ],
                language: {
                    processing: "<div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div>",
                    zeroRecords: 'Tidak ada data yang cocok dengan pencarian',
                    emptyTable: @json($dataSpasialEmptyHtml),
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ Data Spasial',
                    infoEmpty: 'Menampilkan 0 sampai 0 dari 0 Data Spasial',
                    infoFiltered: '(difilter dari _MAX_ total Data Spasial)',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Selanjutnya',
                        previous: 'Sebelumnya'
                    },
                },
                dom: '<"row"<"col-sm-12"tr>>' +
                    '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            });

            $('#dataSpasialSearchInput').on('keyup', function() {
                dataSpasialTable.search(this.value).draw();
            });

            document.getElementById('dataSpasialSearchBtn')?.addEventListener('click', () => {
                dataSpasialTable.search($('#dataSpasialSearchInput').val()).draw();
            });

            $('#dataSpasialAttributeFilterField').on('change', function() {
                $('#dataSpasialAttributeFilterValue').toggleClass('d-none', !this.value).val('');
                dataSpasialTable.draw();
            });

            $('#dataSpasialAttributeFilterValue').on('keyup', function() {
                dataSpasialTable.draw();
            });

            $('#dataSpasialPerPage').on('change', function() {
                dataSpasialTable.page.len(parseInt(this.value, 10)).draw();
            });

            // Detail Data Spasial dibuka lewat modal saat baris diklik (tidak perlu
            // halaman terpisah) — delegated ke document karena baris di-redraw ulang
            // oleh DataTables tiap ganti halaman/filter.
            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function openFeatureDetailModal(feature) {
                const $imageWrap = $('#featureDetailImageWrap');
                if (feature.gambar) {
                    $('#featureDetailImage').attr('src', feature.gambar);
                    $imageWrap.show();
                } else {
                    $imageWrap.hide();
                }

                $('#featureDetailKode').text(feature.kode || '-');
                $('#featureDetailWilayah').text(feature.wilayah || '-');
                $('#featureDetailTanggal').text(feature.tanggal_input || '-');

                const $metadata = $('#featureDetailMetadata').empty();
                if (feature.metadata && feature.metadata.length) {
                    feature.metadata.forEach((row) => {
                        const label = escapeHtml(row.label) + (row.satuan ? ' (' + escapeHtml(row.satuan) +
                            ')' : '');
                        const value = (row.value === null || row.value === '') ?
                            '<span class="text-muted">-</span>' : escapeHtml(row.value);
                        $metadata.append(
                            `<tr><th style="width:200px;">${label}</th><td>${value}</td></tr>`);
                    });
                } else {
                    $metadata.append(
                        '<tr><td colspan="2" class="text-muted">Tidak ada Metadata Dinamis untuk Jenis Layer ini.</td></tr>'
                    );
                }

                $('#featureDetailEditBtn').attr('href', feature.edit_url);
                $('#featureDetailDeleteForm').attr('action', feature.delete_url);
                $('#featureDetailDeleteForm').attr('data-name', feature.delete_name);

                bootstrap.Modal.getOrCreateInstance(document.getElementById('featureDetailModal')).show();
            }

            $(document).on('click', '#dataSpasialLayerTable tbody tr[data-feature-row]', function(e) {
                if ($(e.target).closest('a, button, form, input, label').length) {
                    return;
                }
                openFeatureDetailModal($(this).data('feature'));
            });

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
         * Bulk edit atribut / hapus Data Spasial terpilih (§5.7 butir 3) — pola
         * checkbox + bar sendiri (bulk select Layer di spatial-layers/
         * index.blade.php sudah dihapus bersama fitur "Ubah Jenis Peta" massal,
         * 2026-10-06), diberi nama terpisah di sini ("Feature") supaya tidak
         * bentrok kalau kedua halaman pernah disatukan.
         */
        let selectedFeatureItems = [];

        function updateFeatureBulkActionsBar() {
            const bar = document.getElementById('featureBulkActionsBar');
            const countEl = document.getElementById('featureSelectedCount');

            if (selectedFeatureItems.length > 0) {
                bar.classList.remove('d-none');
                countEl.textContent = selectedFeatureItems.length;
            } else {
                bar.classList.add('d-none');
            }
        }

        function updateFeatureSelectAllState() {
            const checkboxes = $('.feature-row-checkbox');
            const selectAllCheckbox = document.getElementById('featureSelectAll');
            const checkedCount = checkboxes.filter(':checked').length;

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

        function clearFeatureSelection() {
            selectedFeatureItems = [];
            document.querySelectorAll('.feature-row-checkbox').forEach((cb) => {
                cb.checked = false;
                cb.closest('tr')?.classList.remove('table-active');
            });
            document.getElementById('featureSelectAll').checked = false;
            document.getElementById('featureSelectAll').indeterminate = false;
            updateFeatureBulkActionsBar();
        }

        $(document).on('change', '#featureSelectAll', function() {
            const isChecked = this.checked;

            $('.feature-row-checkbox').each(function() {
                this.checked = isChecked;
                const value = this.value;
                $(this).closest('tr').toggleClass('table-active', isChecked);

                if (isChecked && !selectedFeatureItems.includes(value)) {
                    selectedFeatureItems.push(value);
                } else if (!isChecked) {
                    selectedFeatureItems = selectedFeatureItems.filter((id) => id !== value);
                }
            });

            updateFeatureBulkActionsBar();
        });

        $(document).on('change', '.feature-row-checkbox', function() {
            const value = this.value;

            if (this.checked) {
                if (!selectedFeatureItems.includes(value)) {
                    selectedFeatureItems.push(value);
                }
            } else {
                selectedFeatureItems = selectedFeatureItems.filter((id) => id !== value);
            }

            $(this).closest('tr').toggleClass('table-active', this.checked);
            updateFeatureBulkActionsBar();
            updateFeatureSelectAllState();
        });

        function bulkEditFeatureAttribute() {
            if (selectedFeatureItems.length === 0) {
                return;
            }

            document.getElementById('bulkFeatureAttributeCount').textContent = selectedFeatureItems.length;
            document.getElementById('bulkFeatureKey').value = '';
            document.getElementById('bulkFeatureValue').value = '';
            document.getElementById('bulkFeatureActionSet').checked = true;
            document.getElementById('bulkFeatureValueWrapper').style.display = '';
            document.getElementById('bulkFeatureAttributeError').classList.add('d-none');

            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkFeatureAttributeModal')).show();
        }

        document.querySelectorAll('input[name="bulkFeatureAction"]').forEach((radio) => {
            radio.addEventListener('change', function() {
                document.getElementById('bulkFeatureValueWrapper').style.display =
                    this.value === 'set' ? '' : 'none';
            });
        });

        document.getElementById('confirmBulkFeatureAttribute').addEventListener('click', function() {
            const key = document.getElementById('bulkFeatureKey').value.trim();
            const action = document.querySelector('input[name="bulkFeatureAction"]:checked').value;
            const errorEl = document.getElementById('bulkFeatureAttributeError');

            if (!key) {
                errorEl.textContent = 'Nama atribut harus diisi.';
                errorEl.classList.remove('d-none');
                return;
            }

            const idsContainer = document.getElementById('bulkFeatureAttributeIds');
            idsContainer.innerHTML = '';
            selectedFeatureItems.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                idsContainer.appendChild(input);
            });

            document.getElementById('bulkFeatureActionInput').value = action;
            document.getElementById('bulkFeatureKeyInput').value = key;
            document.getElementById('bulkFeatureValueInput').value = document.getElementById('bulkFeatureValue').value;

            document.getElementById('bulkFeatureAttributeForm').submit();
        });

        function bulkDeleteFeatures() {
            if (selectedFeatureItems.length === 0) {
                return;
            }

            const idsContainer = document.getElementById('bulkFeatureDestroyIds');
            idsContainer.innerHTML = '';
            selectedFeatureItems.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                idsContainer.appendChild(input);
            });

            // requestSubmit() (bukan submit()) supaya event 'submit' tetap
            // terpicu — handler global data-confirm="delete" di
            // backend.partials.main menunggu event ini untuk menampilkan
            // konfirmasi SweetAlert sebelum benar-benar mengirim form.
            document.getElementById('bulkFeatureDestroyForm').requestSubmit();
        }
    </script>

    <script>
        /**
         * Icon-picker & color-picker modal Edit Layer — disamakan dengan modal edit
         * Kategori di categories/index.blade.php (prefix "edit_"), diadaptasi jadi
         * prefix "layer_edit_" karena di sini cuma ada satu instance (tidak ada modal
         * "add" terpisah seperti di categories).
         */
        $(function() {
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

                $select.children('option, optgroup').each(function() {
                    if (this.tagName === 'OPTGROUP') {
                        $grid.append(`<div class="icon-picker-group-title">${$(this).attr('label')}</div>`);
                        $itemsWrap = $('<div class="icon-picker-items"></div>');
                        $grid.append($itemsWrap);
                        $(this).children('option').each(function() {
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
                $(`#${prefix}_colorSwatches .color-swatch[data-color="${newColor.toLowerCase()}"]`).addClass(
                    'active');

                const iconElement = $(`#${prefix}_iconPreview .icon-preview-icon`);
                if (iconElement.length) {
                    iconElement.css('color', newColor);
                }
            }

            buildIconPicker(`#${prefix}_icon`, `#${prefix}_iconGrid`);

            $(document).on('click', `#${prefix}_iconGrid .icon-picker-item`, function() {
                const iconValue = $(this).data('icon-value');
                $(`#${prefix}_iconGrid .icon-picker-item`).removeClass('active');
                $(this).addClass('active');
                $(`#${prefix}_icon`).val(iconValue).trigger('change');
            });

            $(document).on('input', `#${prefix}_iconSearch`, function() {
                const $grid = $(`#${prefix}_iconGrid`);
                const query = $(this).val().trim().toLowerCase();

                $grid.find('.icon-picker-item').each(function() {
                    const matches = !query || $(this).data('search').toString().includes(query);
                    $(this).toggle(matches);
                });

                $grid.find('.icon-picker-items').each(function() {
                    const hasVisible = $(this).find('.icon-picker-item:visible').length > 0;
                    $(this).toggle(hasVisible);
                    $(this).prev('.icon-picker-group-title').toggle(hasVisible);
                });
            });

            $(`#${prefix}_icon`).on('change', function() {
                const colorValue = $(`#${prefix}_warna`).val() || '#007bff';
                updateIconPreview($(this).val(), colorValue);
            });

            $(`#${prefix}_warna`).on('change input', function() {
                applyColor($(this).val());
            });

            $(`#${prefix}_warnaHex`).on('input', function() {
                let value = $(this).val().trim();
                if (value && value[0] !== '#') {
                    value = '#' + value;
                }
                $(this).val(value.toUpperCase());

                if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    applyColor(value);
                }
            });

            $(document).on('click', `#${prefix}_colorSwatches .color-swatch`, function() {
                applyColor($(this).data('color'));
            });

            $(`#${prefix}_is_marker`).on('change', function() {
                if ($(this).is(':checked')) {
                    $(`#${prefix}_iconContainer`).slideDown(300);
                } else {
                    $(`#${prefix}_iconContainer`).slideUp(300);
                    $(`#${prefix}_icon`).val('');
                    updateIconPreview('', '#007bff');
                    $(`#${prefix}_iconGrid .icon-picker-item`).removeClass('active').show();
                    $(`#${prefix}_iconGrid .icon-picker-items, #${prefix}_iconGrid .icon-picker-group-title`)
                        .show();
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
