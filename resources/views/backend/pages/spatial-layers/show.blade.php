@extends('backend.partials.main', ['title' => 'Detail Layer'])

@section('main')
    {{-- Dipakai fetch() AJAX edit style langsung di peta, lihat blok scripts di bawah --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
                                    $categoryKey = $layer->category_node_id
                                        ? 'node:' . $layer->category_node_id
                                        : 'cat:' . $layer->category_id;
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
                @php
                    $statusGradient =
                        [
                            'draft' => 'bg-gradient-secondary',
                            'published' => 'bg-gradient-warning',
                            'archived' => 'bg-gradient-dark',
                        ][$layer->status] ?? 'bg-gradient-secondary';
                    $statusIcon =
                        [
                            'draft' => 'mdi-pencil-circle-outline',
                            'published' => 'mdi-check-circle',
                            'archived' => 'mdi-archive',
                        ][$layer->status] ?? 'mdi-help-circle';
                @endphp
                <div class="card stat-card-compact {{ $statusGradient }} text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Status</p>
                                <h3 class="stat-value" style="font-size:1.15rem;">{{ ucfirst($layer->status) }}</h3>
                            </div>
                            <i class="mdi {{ $statusIcon }} stat-icon"></i>
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
                                <a href="{{ route('spatial-layers.metadata.edit', $layer) }}"
                                    class="btn btn-sm btn-outline-info" title="Metadata">
                                    <i class="mdi mdi-file-document-outline"></i> Metadata
                                </a>
                                <a href="{{ route('spatial-layers.imports.index', $layer) }}"
                                    class="btn btn-sm btn-outline-secondary" title="Riwayat Impor">
                                    <i class="mdi mdi-history"></i> Riwayat Impor
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                    data-bs-target="#editLayerStyleModal" title="Style">
                                    <i class="mdi mdi-palette-outline"></i> Style
                                </button>
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
                                    data-bs-target="#layerInfoCollapse" aria-expanded="false"
                                    aria-controls="layerInfoCollapse" id="layerInfoToggle">
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
                                    <th>Jenis Layer</th>
                                    <td>{{ $layer->layerType?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>OPD Penanggungjawab</th>
                                    <td>{{ $layer->opd?->name ?? 'Provinsi/Bappeda' }}</td>
                                </tr>
                                <tr>
                                    <th>Tahun Data</th>
                                    <td>{{ $layer->metadata?->data_year ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Sumber Data</th>
                                    <td>{{ $layer->metadata?->sumber_data ?? '-' }}</td>
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
                                            <small class="text-muted">Opacity: {{ $layer->opacity }} &middot; Ukuran:
                                                {{ $layer->size }}px</small>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @php
                                            $statusBadge =
                                                [
                                                    'draft' => 'secondary',
                                                    'published' => 'success',
                                                    'archived' => 'dark',
                                                ][$layer->status] ?? 'secondary';
                                        @endphp
                                        <span
                                            class="badge bg-{{ $statusBadge }} text-white">{{ ucfirst($layer->status) }}</span>

                                        @can('spatial-layers.publish')
                                            <form action="{{ route('spatial-layers.update-status', $layer) }}" method="POST"
                                                class="d-inline-flex align-items-center gap-1 ms-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm" style="width:auto;"
                                                    onchange="this.form.submit()">
                                                    <option value="draft" @selected($layer->status === 'draft')>Draft</option>
                                                    <option value="published" @selected($layer->status === 'published')>Published</option>
                                                    <option value="archived" @selected($layer->status === 'archived')>Archived</option>
                                                </select>
                                            </form>
                                            @error('status')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        @else
                                            <small class="text-muted ms-2">Hanya super-admin/admin-bappeda yang bisa mengubah
                                                status Layer.</small>
                                        @endcan
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
                                <select class="form-select form-select-sm" id="dataSpasialAttributeFilterField"
                                    style="width: auto;">
                                    <option value="">Filter per Atribut...</option>
                                    @foreach ($dynamicAttributes as $attribute)
                                        <option value="{{ $attribute->metadataDefinition->kode }}">
                                            {{ $attribute->metadataDefinition->label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" class="form-control form-control-sm d-none"
                                    id="dataSpasialAttributeFilterValue" placeholder="Nilai..." style="width: 160px;">
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
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="bulkEditFeatureAttribute()">
                                            <i class="mdi mdi-pencil-box-multiple-outline me-1"></i> Ubah Atribut
                                        </button>
                                        @if ($moveTargetLayers->isNotEmpty())
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                onclick="openBulkMoveFeatureModal()">
                                                <i class="mdi mdi-database-arrow-right-outline me-1"></i> Pindah ke Layer Lain
                                            </button>
                                        @endif
                                    @endcan
                                    @can('spatial-layers.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="bulkDeleteFeatures()">
                                            <i class="mdi mdi-delete me-1"></i> Hapus Terpilih
                                        </button>
                                    @endcan
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick="clearFeatureSelection()">
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
                            $dataSpasialEmptyHtml =
                                '<div class="text-center py-4 text-muted">' .
                                '<i class="mdi mdi-map-marker-off-outline mdi-36px text-muted d-block mb-2"></i>' .
                                'Belum ada Data Spasial.';
                            if (auth()->user()?->can('spatial-layers.create')) {
                                $dataSpasialEmptyHtml .=
                                    '<br><a href="' .
                                    e(route('spatial-layers.features.create', $layer)) .
                                    '" class="btn btn-sm btn-primary mt-2">' .
                                    '<i class="mdi mdi-map-marker-plus"></i> Tambah Data Spasial Pertama</a>';
                            }
                            $dataSpasialEmptyHtml .= '</div>';

                            // Dikenal dari dua sumber: kode atribut dinamis Jenis Peta layer, DAN
                            // nama kunci mentah hasil impor yang sudah ada di properties fitur —
                            // bulk edit atribut bisa menyasar keduanya (sama seperti modul lama yang
                            // bisa ubah sembarang kolom DBF, bukan cuma atribut terdefinisi).
                            $knownAttributeKeys = $dynamicAttributes
                                ->pluck('metadataDefinition.kode')
                                ->merge($layer->features->flatMap(fn($f) => array_keys($f->properties ?? [])))
                                ->filter()
                                ->unique()
                                ->sort()
                                ->values();
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
                                            <th>ID</th>
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
                                                $featureMetadataRows = $dynamicAttributes
                                                    ->map(function ($attribute) use ($feature) {
                                                        $definition = $attribute->metadataDefinition;

                                                        return [
                                                            'kode' => $definition->kode,
                                                            'label' => $definition->label,
                                                            'satuan' => $definition->satuan,
                                                            'value' => $feature->properties[$definition->kode] ?? null,
                                                        ];
                                                    })
                                                    ->values();

                                                $featureDynamicCodes = $dynamicAttributes
                                                    ->pluck('metadataDefinition.kode')
                                                    ->all();
                                                $featureRawAttributes = \Illuminate\Support\Arr::except(
                                                    $feature->properties ?? [],
                                                    $featureDynamicCodes,
                                                );

                                                $featureDetailPayload = [
                                                    'kode' => $feature->label ?? '#' . $feature->id,
                                                    'wilayah' => $feature->region->name ?? null,
                                                    'gambar' => $feature->gambar
                                                        ? asset('storage/' . $feature->gambar)
                                                        : null,
                                                    'tanggal_input' => $feature->created_at?->format('d M Y H:i'),
                                                    'metadata' => $featureMetadataRows,
                                                    'atribut' => collect($featureRawAttributes)
                                                        ->map(fn ($value, $key) => [
                                                            'key' => $key,
                                                            'value' => is_scalar($value) ? $value : json_encode($value),
                                                        ])
                                                        ->values(),
                                                    'edit_url' => route('spatial-layers.features.edit', [
                                                        $layer,
                                                        $feature,
                                                    ]),
                                                    'delete_url' => route('spatial-layers.features.destroy', [
                                                        $layer,
                                                        $feature,
                                                    ]),
                                                    'delete_name' => $feature->label ?? '#' . $feature->id,
                                                ];
                                            @endphp
                                            <tr data-feature-row data-feature="{{ json_encode($featureDetailPayload) }}"
                                                style="cursor: pointer;" title="Klik untuk lihat detail">
                                                <td>
                                                    <div class="checkbox-wrapper">
                                                        <input class="form-check-input feature-row-checkbox" type="checkbox"
                                                            value="{{ $feature->id }}"
                                                            id="check-feature-{{ $feature->id }}">
                                                        <label class="form-check-label"
                                                            for="check-feature-{{ $feature->id }}">
                                                            <span class="visually-hidden">Select row</span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td>{{ $feature->id }}</td>
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
                                <div class="col-12">
                                    <div class="form-group mb-2">
                                        <label for="layer_edit_layer_type_id" class="form-label">Tipe Geometri Layer</label>
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

                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label for="layer_edit_data_year" class="form-label">Tahun Data</label>
                                            <input type="number" class="form-control" id="layer_edit_data_year"
                                                name="data_year" min="1900" max="2100"
                                                value="{{ old('data_year', $layer->metadata?->data_year) }}">
                                            @error('data_year')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label for="layer_edit_sumber_data" class="form-label">Sumber Data</label>
                                            <input type="text" class="form-control" id="layer_edit_sumber_data"
                                                name="sumber_data"
                                                value="{{ old('sumber_data', $layer->metadata?->sumber_data) }}">
                                            @error('sumber_data')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

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
                                            <input type="text" class="form-control"
                                                value="{{ $layer->opd?->name ?? '-' }}" disabled>
                                            <div class="form-text">OPD pemilik otomatis mengikuti OPD Anda.</div>
                                        @endif
                                        @error('opd_id')
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

        <!-- STYLE LAYER (warna/ukuran/opacity/marker style default, pola editor
                 simbol QGIS: satu simbol, bukan multi-kelas — categorized/graduated
                 tetap di halaman "Kelola Style Lanjutan" lewat link di footer) -->
        @php
            $defaultStyle = $layer->defaultStyle;
            $styleFormAction = $defaultStyle
                ? route('spatial-layers.styles.update', [$layer, $defaultStyle])
                : route('spatial-layers.styles.store', $layer);
            $isPointLayer = in_array($layer->layerType?->geometry_type, ['MULTIPOINT', 'GEOMETRY']);
        @endphp
        <div class="modal fade" id="editLayerStyleModal" tabindex="-1" aria-labelledby="editLayerStyleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editLayerStyleModalLabel">
                            <i class="mdi mdi-palette-outline"></i> Style Layer
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form method="POST" action="{{ $styleFormAction }}">
                        @csrf
                        @if ($defaultStyle)
                            @method('PUT')
                        @endif
                        <input type="hidden" name="return_to_show" value="1">
                        <input type="hidden" name="name" value="{{ $defaultStyle->name ?? 'Default' }}">
                        <input type="hidden" name="style_type" value="simple">
                        <input type="hidden" name="is_default" value="1">
                        @if (!$isPointLayer)
                            <input type="hidden" name="is_marker" value="0">
                        @endif

                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <div id="layerStylePreviewCircle" class="layer-style-preview-circle"
                                    style="background-color: {{ old('color', $layer->color ?? '#2563eb') }}; opacity: {{ old('opacity', $layer->opacity ?? 1) }}; width: {{ old('size', $layer->size ?? 6) * 4 }}px; height: {{ old('size', $layer->size ?? 6) * 4 }}px;">
                                    <i id="layerStylePreviewIcon"
                                        class="{{ old('is_marker', $layer->is_marker) ? old('icon', $layer->icon) : '' }}"
                                        style="{{ old('is_marker', $layer->is_marker) && ($layer->icon || old('icon')) ? '' : 'display:none' }}; color: {{ old('color', $layer->color ?? '#2563eb') }};"></i>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="layer_style_color" class="form-label">Warna</label>
                                <input type="color" class="form-control form-control-color w-100" id="layer_style_color"
                                    name="color" value="{{ old('color', $layer->color ?? '#2563eb') }}">
                                @error('color')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="layer_style_opacity" class="form-label">
                                        Opacity — <span
                                            id="layerStyleOpacityValue">{{ old('opacity', $layer->opacity ?? 1) }}</span>
                                    </label>
                                    <input type="range" class="form-range" id="layer_style_opacity" name="opacity"
                                        min="0" max="1" step="0.05"
                                        value="{{ old('opacity', $layer->opacity ?? 1) }}">
                                    @error('opacity')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="layer_style_size" class="form-label">Ukuran (px)</label>
                                    <input type="number" class="form-control" id="layer_style_size" name="size"
                                        min="1" max="100" step="1"
                                        value="{{ old('size', $layer->size ?? 6) }}">
                                    @error('size')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            @if ($isPointLayer)
                                <div class="mb-3">
                                    <label class="form-label d-block">Jenis Marker</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="is_marker" value="0"
                                            id="layer_style_marker_dot" autocomplete="off" @checked(!old('is_marker', $layer->is_marker))>
                                        <label class="btn btn-outline-secondary" for="layer_style_marker_dot">
                                            <i class="mdi mdi-circle"></i> Dot
                                        </label>

                                        <input type="radio" class="btn-check" name="is_marker" value="1"
                                            id="layer_style_marker_icon" autocomplete="off" @checked(old('is_marker', $layer->is_marker))>
                                        <label class="btn btn-outline-secondary" for="layer_style_marker_icon">
                                            <i class="mdi mdi-map-marker"></i> Icon
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3" id="layer_style_icon_wrapper"
                                    style="{{ old('is_marker', $layer->is_marker) ? '' : 'display:none' }}">
                                    <label class="form-label">Pilih Icon</label>

                                    <!-- Hidden select = sumber kebenaran & data untuk kartu ikon -->
                                    <select id="layer_style_icon" name="icon" class="d-none">
                                        <option value="">-- Pilih Icon --</option>
                                        @include('backend.partials.icon-options')
                                    </select>

                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                        <input type="text" class="form-control" id="layer_style_icon_search"
                                            placeholder="Cari ikon berdasarkan nama atau class...">
                                    </div>

                                    <div class="icon-card-grid" id="layer_style_icon_grid"></div>
                                    @error('icon')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer justify-content-between">
                            <a href="{{ route('spatial-layers.styles.index', $layer) }}" class="btn btn-sm btn-link">
                                Kelola Style Lanjutan & Custom per Data Spasial <i class="mdi mdi-arrow-right"></i>
                            </a>
                            <div>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" class="btn btn-gradient-primary">
                                    <i class="mdi mdi-content-save"></i> Simpan
                                </button>
                            </div>
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
                        <p class="card-title mb-2" id="featureDetailAttributeTitle" style="font-size:0.95rem;display:none;">
                            Atribut Impor</p>
                        <table class="table table-sm" id="featureDetailAttributeTable" style="display:none;">
                            <tbody id="featureDetailAttribute"></tbody>
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
        <div class="modal fade" id="bulkFeatureAttributeModal" tabindex="-1"
            aria-labelledby="bulkFeatureAttributeModalLabel" aria-hidden="true">
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
                            Mengubah satu atribut untuk <strong id="bulkFeatureAttributeCount">0</strong> Data Spasial yang
                            dipilih.
                        </p>

                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="bulkFeatureAction"
                                    id="bulkFeatureActionSet" value="set" checked>
                                <label class="form-check-label" for="bulkFeatureActionSet">Set (isi/timpa)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="bulkFeatureAction"
                                    id="bulkFeatureActionRemove" value="remove">
                                <label class="form-check-label" for="bulkFeatureActionRemove">Hapus atribut ini</label>
                            </div>
                        </div>

                        <label for="bulkFeatureKey" class="form-label fw-semibold">Nama Atribut</label>
                        <input type="text" class="form-control" id="bulkFeatureKey" list="bulkFeatureKeyList"
                            placeholder="mis. kondisi, sumber_dana">
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
        <form id="bulkFeatureAttributeForm" method="POST"
            action="{{ route('spatial-layers.features.bulk-update-attribute', $layer) }}" style="display: none;">
            @csrf
            <div id="bulkFeatureAttributeIds"></div>
            <input type="hidden" name="action" id="bulkFeatureActionInput">
            <input type="hidden" name="key" id="bulkFeatureKeyInput">
            <input type="hidden" name="value" id="bulkFeatureValueInput">
        </form>

        <!-- Bulk Hapus Form (Hidden) -->
        <form id="bulkFeatureDestroyForm" method="POST"
            action="{{ route('spatial-layers.features.bulk-destroy', $layer) }}" style="display: none;"
            data-confirm="delete" data-name="Data Spasial terpilih">
            @csrf
            <div id="bulkFeatureDestroyIds"></div>
        </form>

        <!-- Bulk Pindah ke Layer Lain Modal — hanya ada kalau ada Layer lain
                 untuk dijadikan tujuan (lihat $moveTargetLayers di
                 SpatialLayerController::show()) -->
        @if ($moveTargetLayers->isNotEmpty())
            <div class="modal fade" id="bulkMoveFeatureModal" tabindex="-1" aria-labelledby="bulkMoveFeatureModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="bulkMoveFeatureModalLabel">
                                <i class="mdi mdi-database-arrow-right-outline"></i> Pindah ke Layer Lain
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="bulkMoveFeatureForm" method="POST"
                            action="{{ route('spatial-layers.features.bulk-move', $layer) }}">
                            @csrf
                            <div class="modal-body">
                                <p class="text-muted small">
                                    Pindahkan <strong id="bulkMoveFeatureCount">0</strong> Data Spasial terpilih dari Layer ini
                                    ke Layer tujuan.
                                </p>

                                <div class="mb-2">
                                    <label for="bulkMoveFeatureTargetLayer" class="form-label">Layer Tujuan <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="bulkMoveFeatureTargetLayer" name="target_layer_id"
                                        required>
                                        <option value="">-- Pilih Layer Tujuan --</option>
                                        @foreach ($moveTargetLayers as $targetLayer)
                                            <option value="{{ $targetLayer->id }}">{{ $targetLayer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="bulkMoveFeatureIds"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-gradient-primary">
                                    <i class="mdi mdi-database-arrow-right-outline"></i> Pindahkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endsection

    @push('styles')
        @include('backend.partials._style-picker-styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <style>
            .layer-map-wrapper {
                position: relative;
                border-radius: 8px;
                overflow: hidden;
            }

            #layerDetailMap {
                aspect-ratio: 16 / 9;
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
        </style>
    @endpush

    @push('scripts')
        @include('backend.partials._style-picker-script')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            (function() {
                const layerSlug = @json($layer->slug);
                const layerColor = @json($layer->color ?? '#2563eb');
                const layerIcon = @json($layer->is_marker ? $layer->icon : null);
                const layerOpacity = @json($layer->opacity ?? 1);
                const layerSize = @json($layer->size ?? 6);
                const isPointLayer = @json($isPointLayer);
                const updateStyleUrlTemplate = @json(route('spatial-layers.features.update-style', [$layer, '__FEATURE_ID__']));

                let map = null;
                let dataLayer = null;

                // style_override per Data Spasial (lihat §"Custom Style" di
                // halaman Style Layer) menang atas style default Layer kalau
                // diisi — NULL berarti ikut Layer.
                function resolveFeatureStyle(feature) {
                    const o = feature.properties?.style_override;
                    if (!o) {
                        return {
                            color: layerColor,
                            icon: layerIcon,
                            opacity: layerOpacity,
                            size: layerSize
                        };
                    }

                    return {
                        color: o.color ?? layerColor,
                        icon: o.is_marker ? (o.icon || null) : null,
                        opacity: o.opacity ?? layerOpacity,
                        size: o.size ?? layerSize,
                    };
                }

                // Form mini di popup Leaflet — edit warna/ukuran/opacity (dan
                // marker kalau Layer-nya titik) SATU Data Spasial langsung di
                // peta, tanpa pindah ke halaman Style Layer. "Pakai Default"
                // cuma tampil kalau Data Spasial ini memang sedang dikustom.
                function buildStylePopupHtml(feature) {
                    const id = feature.properties.id;
                    const o = feature.properties.style_override;
                    const s = resolveFeatureStyle(feature);
                    const label = feature.properties.external_id ?? `#${id}`;

                    const markerFieldsHtml = isPointLayer ? `
                    <div class="mb-2">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="map_style_marker_${id}" id="map_style_dot_${id}" value="0" ${!(o?.is_marker) ? 'checked' : ''}>
                            <label class="btn btn-outline-secondary" for="map_style_dot_${id}"><i class="mdi mdi-circle"></i> Dot</label>
                            <input type="radio" class="btn-check" name="map_style_marker_${id}" id="map_style_icon_radio_${id}" value="1" ${(o?.is_marker) ? 'checked' : ''}>
                            <label class="btn btn-outline-secondary" for="map_style_icon_radio_${id}"><i class="mdi mdi-map-marker"></i> Icon</label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm" id="map_style_icon_${id}"
                            placeholder="mis. mdi mdi-home" value="${o?.icon ?? ''}">
                        <div class="form-text" style="font-size:0.7rem;">Kelas ikon Material Design Icons. Kosongkan untuk Dot.</div>
                    </div>
                ` : '';

                    return `
                    <div class="map-style-popup" style="min-width:200px;">
                        <div class="fw-semibold mb-2">${label}</div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Warna</label>
                            <input type="color" class="form-control form-control-sm form-control-color w-100" id="map_style_color_${id}" value="${s.color}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Ukuran</label>
                            <input type="number" class="form-control form-control-sm" id="map_style_size_${id}" min="1" max="100" value="${s.size}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Opacity — <span id="map_style_opacity_value_${id}">${s.opacity}</span></label>
                            <input type="range" class="form-range" id="map_style_opacity_${id}" min="0" max="1" step="0.05" value="${s.opacity}">
                        </div>
                        ${markerFieldsHtml}
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-gradient-primary flex-grow-1" data-map-style-save="${id}">
                                <i class="mdi mdi-content-save"></i> Simpan
                            </button>
                            ${o ? `<button type="button" class="btn btn-sm btn-outline-secondary" data-map-style-reset="${id}" title="Pakai style default Layer"><i class="mdi mdi-backup-restore"></i></button>` : ''}
                        </div>
                        <div class="small text-danger mt-1 d-none" id="map_style_error_${id}"></div>
                    </div>
                `;
                }

                function saveFeatureStyle(featureId, body) {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                    const errorEl = document.getElementById(`map_style_error_${featureId}`);

                    return fetch(updateStyleUrlTemplate.replace('__FEATURE_ID__', featureId), {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(body),
                        })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error('Gagal menyimpan style.');
                            }
                            map.closePopup();
                            loadLayerFeatures(false);
                        })
                        .catch(() => {
                            if (errorEl) {
                                errorEl.textContent = 'Gagal menyimpan style. Coba lagi.';
                                errorEl.classList.remove('d-none');
                            }
                        });
                }

                // Delegasi dari `document` (bukan listener per tombol) — konten
                // popup dibuat ulang setiap kali popup dibuka, jadi listener
                // langsung akan hilang begitu popup ditutup lalu dibuka lagi.
                document.addEventListener('click', function(e) {
                    const saveBtn = e.target.closest('[data-map-style-save]');
                    if (saveBtn) {
                        const id = saveBtn.dataset.mapStyleSave;
                        const body = {
                            custom_style: '1',
                            style_color: document.getElementById(`map_style_color_${id}`).value,
                            style_size: document.getElementById(`map_style_size_${id}`).value,
                            style_opacity: document.getElementById(`map_style_opacity_${id}`).value,
                        };
                        if (isPointLayer) {
                            body.style_is_marker = document.querySelector(
                                `input[name="map_style_marker_${id}"]:checked`)?.value ?? '0';
                            body.style_icon = document.getElementById(`map_style_icon_${id}`)?.value ?? '';
                        }
                        saveFeatureStyle(id, body);
                        return;
                    }

                    const resetBtn = e.target.closest('[data-map-style-reset]');
                    if (resetBtn) {
                        saveFeatureStyle(resetBtn.dataset.mapStyleReset, {
                            custom_style: '0'
                        });
                    }
                });

                document.addEventListener('input', function(e) {
                    if (e.target.id?.startsWith('map_style_opacity_')) {
                        const id = e.target.id.replace('map_style_opacity_', '');
                        const valueEl = document.getElementById(`map_style_opacity_value_${id}`);
                        if (valueEl) {
                            valueEl.textContent = e.target.value;
                        }
                    }
                });

                function loadLayerFeatures(fitBounds) {
                    fetch(`/peta-v2/geojson/${layerSlug}`)
                        .then((response) => (response.ok ? response.json() : null))
                        .then((geojson) => {
                            if (!geojson) {
                                return;
                            }

                            if (dataLayer) {
                                map.removeLayer(dataLayer);
                            }

                            dataLayer = L.geoJSON(geojson, {
                                pointToLayer: (feature, latlng) => {
                                    const s = resolveFeatureStyle(feature);

                                    if (s.icon) {
                                        return L.marker(latlng, {
                                            icon: L.divIcon({
                                                html: `<i class="${s.icon}" style="color:${s.color};font-size:${s.size * 4}px;"></i>`,
                                                className: 'layer-detail-marker-icon',
                                                iconSize: [s.size * 4, s.size * 4],
                                            }),
                                        });
                                    }

                                    return L.circleMarker(latlng, {
                                        radius: s.size,
                                        fillColor: s.color,
                                        color: s.color,
                                        weight: 1,
                                        fillOpacity: s.opacity,
                                    });
                                },
                                style: (feature) => {
                                    const s = resolveFeatureStyle(feature);

                                    return {
                                        color: s.color,
                                        weight: 2,
                                        opacity: s.opacity,
                                        fillOpacity: s.opacity * 0.4
                                    };
                                },
                                onEachFeature: (feature, leafletLayer) => {
                                    leafletLayer.bindPopup(buildStylePopupHtml(feature), {
                                        maxWidth: 260
                                    });
                                },
                            }).addTo(map);

                            if (fitBounds && dataLayer.getBounds().isValid()) {
                                map.fitBounds(dataLayer.getBounds(), {
                                    maxZoom: 15
                                });
                            }
                        })
                        .catch((error) => console.error('Gagal memuat Data Spasial Layer ini', error));
                }

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

                    loadLayerFeatures(true);
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

                // Preview langsung modal "Style Layer" (warna/ukuran/opacity/jenis
                // marker) — pola editor simbol tunggal, lihat docblock modal
                // editLayerStyleModal. initStylePicker (shared, lihat
                // backend/partials/_style-picker-script.blade.php) dipakai ulang
                // juga di halaman custom style per Data Spasial.
                initStylePicker({
                    colorId: 'layer_style_color',
                    opacityId: 'layer_style_opacity',
                    opacityValueId: 'layerStyleOpacityValue',
                    sizeId: 'layer_style_size',
                    previewCircleId: 'layerStylePreviewCircle',
                    previewIconId: 'layerStylePreviewIcon',
                    iconSelectId: 'layer_style_icon',
                    iconWrapperId: 'layer_style_icon_wrapper',
                    iconGridId: 'layer_style_icon_grid',
                    iconSearchId: 'layer_style_icon_search',
                    markerRadioName: 'is_marker',
                    initialIconValue: @json(old('icon', $layer->is_marker ? $layer->icon : null) ?? ''),
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

                    // Elemen filter atribut HANYA dirender kalau $dynamicAttributes
                    // tidak kosong (lihat kondisi di markup di atas) — saat ini SELALU kosong
                    // (layers.map_type_id sudah dihapus), jadi elemennya tidak ada
                    // sama sekali di DOM. .val() pada selector kosong mengembalikan
                    // undefined, dan memanggil .trim() di atasnya langsung
                    // melempar TypeError di SETIAP draw tabel — mematikan seluruh
                    // interaksi tabel (search/filter/pagination) tanpa pesan error
                    // yang terlihat di UI.
                    const $attributeField = $('#dataSpasialAttributeFilterField');
                    const attributeField = $attributeField.length ? $attributeField.val() : '';
                    const attributeValue = ($('#dataSpasialAttributeFilterValue').val() || '').trim().toLowerCase();
                    if (attributeField && attributeValue) {
                        const feature = $(row).data('feature');
                        const entry = (feature.metadata || []).find((m) => m.kode === attributeField);
                        const value = entry && entry.value !== null && entry.value !== undefined ? String(entry
                                .value)
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

                    const $attribute = $('#featureDetailAttribute').empty();
                    if (feature.atribut && feature.atribut.length) {
                        feature.atribut.forEach((row) => {
                            $attribute.append(
                                `<tr><th style="width:200px;">${escapeHtml(row.key)}</th><td>${escapeHtml(row.value)}</td></tr>`
                            );
                        });
                        $('#featureDetailAttributeTitle, #featureDetailAttributeTable').show();
                    } else {
                        $('#featureDetailAttributeTitle, #featureDetailAttributeTable').hide();
                    }

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

                @if (
                    $errors->hasAny([
                        'layer_type_id',
                        'name',
                        'short_description',
                        'category_id',
                        'category_node_id',
                        'opd_id',
                        'data_year',
                        'sumber_data',
                    ]))
                    const editLayerModalEl = document.getElementById('editLayerModal');
                    if (editLayerModalEl && window.bootstrap) {
                        bootstrap.Modal.getOrCreateInstance(editLayerModalEl).show();
                    }
                @endif

                @if ($errors->hasAny(['color', 'opacity', 'size', 'icon']))
                    const editLayerStyleModalEl = document.getElementById('editLayerStyleModal');
                    if (editLayerStyleModalEl && window.bootstrap) {
                        bootstrap.Modal.getOrCreateInstance(editLayerStyleModalEl).show();
                    }
                @endif

                @if ($errors->has('target_layer_id'))
                    // Validasi "Pindah ke Layer Lain" gagal — buka ulang modalnya
                    // dan kembalikan ids[] yang tadi dikirim, sama seperti pola
                    // bulkCategoryModal di index.blade.php (checkbox tabel sendiri
                    // tidak dicentang ulang, baris yang dipilih mungkin tidak
                    // terlihat di halaman DataTables yang aktif sekarang).
                    const bulkMoveFeatureIdsContainer = document.getElementById('bulkMoveFeatureIds');
                    @foreach (old('ids', []) as $oldId)
                        (function() {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'ids[]';
                            input.value = @json($oldId);
                            bulkMoveFeatureIdsContainer.appendChild(input);
                        })();
                    @endforeach
                    document.getElementById('bulkMoveFeatureCount').textContent = @json(count(old('ids', [])));
                    const bulkMoveFeatureModalEl = document.getElementById('bulkMoveFeatureModal');
                    if (bulkMoveFeatureModalEl && window.bootstrap) {
                        bootstrap.Modal.getOrCreateInstance(bulkMoveFeatureModalEl).show();
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
                document.getElementById('bulkFeatureValueInput').value = document.getElementById('bulkFeatureValue')
                    .value;

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

            function openBulkMoveFeatureModal() {
                if (selectedFeatureItems.length === 0) {
                    return;
                }

                document.getElementById('bulkMoveFeatureCount').textContent = selectedFeatureItems.length;
                document.getElementById('bulkMoveFeatureTargetLayer').value = '';

                const idsContainer = document.getElementById('bulkMoveFeatureIds');
                idsContainer.innerHTML = '';
                selectedFeatureItems.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    idsContainer.appendChild(input);
                });

                bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkMoveFeatureModal')).show();
            }
        </script>
    @endpush
