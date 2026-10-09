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

    @if ($wizardDrafts->isNotEmpty())
        <div class="alert alert-warning">
            <i class="mdi mdi-progress-pencil me-2"></i>
            <strong>Anda punya {{ $wizardDrafts->count() }} Layer yang belum selesai dibuat.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($wizardDrafts as $draft)
                    <li>
                        {{ $draft->name }} &mdash; Tahap {{ $draft->wizard_step }}/4
                        <a href="{{ route('spatial-layers.wizard', $draft) }}" class="ms-1">Lanjutkan</a>
                    </li>
                @endforeach
            </ul>
        </div>
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
                            <a href="{{ route('spatial-layers.create') }}" class="btn btn-gradient-primary">
                                <i class="mdi mdi-plus"></i> Tambah Layer
                            </a>
                        @endcan
                    </div>

                    <div class="row mb-4 g-3 align-items-end">
                        <div class="col-12">
                            <div class="row g-3 align-items-end justify-content-between filter-toolbar mb-3">
                                <div class="col-lg-2 col-md-3">
                                    <label for="categoryFilter" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-shape-outline me-1"></i>Kategori
                                    </label>
                                    <select class="form-select filter-control" id="categoryFilter">
                                        <option value="">Semua Kategori</option>
                                        @foreach ($categoryOptions->sortBy('name') as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-3">
                                    <label for="categoryNodeFilter" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-shape-plus-outline me-1"></i>Sub Kategori
                                    </label>
                                    <select class="form-select filter-control" id="categoryNodeFilter">
                                        <option value="">Semua Sub Kategori</option>
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-3">
                                    <label for="opdFilter" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-domain me-1"></i>OPD
                                    </label>
                                    <select class="form-select filter-control" id="opdFilter">
                                        <option value="">Semua OPD</option>
                                        @foreach ($opds as $opd)
                                            <option value="{{ $opd->id }}">{{ $opd->singkatan }}</option>
                                        @endforeach
                                        <option value="0">- Tanpa OPD -</option>
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-2">
                                    <label for="layerTypeFilter" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-shape-outline me-1"></i>Tipe
                                    </label>
                                    <select class="form-select filter-control" id="layerTypeFilter">
                                        <option value="">Semua Tipe</option>
                                        @foreach ($layerTypes->sortBy('id') as $layerType)
                                            <option value="{{ $layerType->id }}">
                                                {{ $layerType->geometry_type ?? $layerType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-3">
                                    <label for="statusFilter" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-flag me-1"></i>Status
                                    </label>
                                    <select class="form-select filter-control" id="statusFilter">
                                        <option value="">Semua Status</option>
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 align-items-end justify-content-between filter-toolbar">
                                <div class="col-lg-4 col-md-6">
                                    <label for="tableSearch" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-magnify me-1"></i>Cari Layer
                                    </label>
                                    <div class="input-group filter-input-group">
                                        <input type="text" class="form-control filter-control" id="tableSearch"
                                            placeholder="Ketik nama layer...">
                                        <button class="btn btn-md btn-primary filter-btn" type="button"
                                            id="searchTableBtn">
                                            <i class="mdi mdi-magnify"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-3">
                                    <label for="sortBySelect" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-sort me-1"></i>Urutkan
                                    </label>
                                    <select class="form-select filter-control" id="sortBySelect">
                                        <option value="2-asc">Nama (A-Z)</option>
                                        <option value="2-desc">Nama (Z-A)</option>
                                        <option value="3-asc">Kategori (A-Z)</option>
                                        <option value="5-asc">Status (A-Z)</option>
                                        <option value="5-desc">Status (Z-A)</option>
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-3">
                                    <label for="per_page" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-table-row me-1"></i>Per halaman
                                    </label>
                                    <select class="form-select filter-control" id="per_page">
                                        <option value="25">25 data</option>
                                        <option value="50">50 data</option>
                                        <option value="100">100 data</option>
                                        <option value="200" selected>200 data</option>
                                        <option value="500">500 data</option>
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-3">
                                    <button type="button" class="btn btn-outline-secondary w-100" id="resetFiltersBtn"
                                        title="Reset semua filter">
                                        <i class="mdi mdi-filter-remove"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="layerBulkActionsBar" class="alert alert-info d-none mb-3" role="alert">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <i class="mdi mdi-checkbox-multiple-marked me-2"></i>
                                <span id="layerSelectedCount">0</span> Layer dipilih
                            </div>
                            <div>
                                @can('spatial-layers.edit')
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="openBulkCategoryModal()">
                                        <i class="mdi mdi-folder-move-outline me-1"></i> Pindah Kategori
                                    </button>
                                @endcan
                                @can('spatial-layers.publish')
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="openBulkStatusModal()">
                                        <i class="mdi mdi-publish me-1"></i> Ubah Status
                                    </button>
                                @endcan
                                @can('spatial-layers.delete')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDeleteLayers()">
                                        <i class="mdi mdi-delete me-1"></i> Hapus Terpilih
                                    </button>
                                @endcan
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearLayerSelection()">
                                    <i class="mdi mdi-close me-1"></i> Batal
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="spatialLayersTable" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width: 12px;">
                                        <div class="checkbox-wrapper">
                                            <input class="form-check-input" type="checkbox" id="layerSelectAll">
                                            <label class="form-check-label" for="layerSelectAll">
                                                <span class="visually-hidden">Select All</span>
                                            </label>
                                        </div>
                                    </th>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th style="width: 15%;">Tipe Geometri</th>
                                    <th>Status</th>
                                    <th>OPD</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($layers->sortBy('name') as $layer)
                                    @php
                                        $categoryKey = $layer->category_node_id
                                            ? 'node:' . $layer->category_node_id
                                            : 'cat:' . $layer->category_id;
                                        $categoryLabel = $categoryPaths[$categoryKey] ?? '-';
                                        $statusBadge =
                                            ['draft' => 'secondary', 'published' => 'success', 'archived' => 'dark'][
                                                $layer->status
                                            ] ?? 'secondary';
                                    @endphp
                                    <tr data-layer-id="{{ $layer->id }}" data-status="{{ $layer->status }}"
                                        data-opd-id="{{ $layer->opd_id ?? '0' }}"
                                        data-category-id="{{ $layer->category_id }}"
                                        data-category-node-id="{{ $layer->category_node_id ?? '' }}"
                                        data-layer-type-id="{{ $layer->layer_type_id ?? '0' }}"
                                        class="spatial-layer-row">
                                        <td>
                                            <div class="checkbox-wrapper">
                                                <input class="form-check-input layer-row-checkbox" type="checkbox"
                                                    value="{{ $layer->id }}" id="check-layer-{{ $layer->id }}">
                                                <label class="form-check-label" for="check-layer-{{ $layer->id }}">
                                                    <span class="visually-hidden">Select row</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-dark fw-bold">{{ $layer->name }}</span>
                                            @if ($layer->wizard_step !== null)
                                                <span class="badge bg-warning text-dark ms-2" style="font-size:0.65em;">
                                                    <i class="mdi mdi-progress-pencil"></i> Tahap
                                                    {{ $layer->wizard_step }}/4
                                                </span>
                                            @endif
                                            @if ($layer->short_description)
                                                <br><small
                                                    class="text-muted">{{ \Illuminate\Support\Str::limit($layer->short_description, 50) }}</small>
                                            @endif
                                            @if ($layer->features_count > 0)
                                                <span class="badge bg-success text-white ms-2"
                                                    style="font-size:0.65em;">{{ $layer->features_count }} data</span>
                                            @endif
                                            @php $metadataPercent = $layer->metadata?->completenessPercent() ?? 0; @endphp
                                            <span
                                                class="badge {{ $metadataPercent >= 80 ? 'bg-success' : ($metadataPercent > 0 ? 'bg-warning text-dark' : 'bg-light text-muted border') }} ms-2"
                                                style="font-size:0.65em;" title="Kelengkapan metadata">
                                                Metadata {{ $metadataPercent }}%
                                            </span>
                                        </td>
                                        <td><small class="text-muted">{{ $categoryLabel }}</small></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                @if ($layer->geometry_type)
                                                    <span class="badge"
                                                        style="display:inline-block;background-color:{{ $layer->color }};">{{ $layer->geometry_type }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td><span
                                                class="badge bg-{{ $statusBadge }} text-white">{{ ucfirst($layer->status) }}</span>
                                        </td>
                                        <td><small class="text-muted">{{ $layer->opd?->singkatan ?? '-' }}</small></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if ($layer->wizard_step !== null)
                                                    <a href="{{ route('spatial-layers.wizard', $layer->id) }}"
                                                        class="btn btn-sm btn-outline-warning" title="Lanjutkan"><i
                                                            class="mdi mdi-play"></i> Lanjutkan</a>
                                                @else
                                                    <a href="{{ route('spatial-layers.show', $layer->id) }}"
                                                        class="btn btn-sm btn-outline-primary" title="Detail"><i
                                                            class="mdi mdi-eye"></i></a>
                                                @endif
                                                @can('spatial-layers.edit')
                                                    @if ($layer->features_count > 0)
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-info move-all-features-btn"
                                                            style="margin-left:-1px;" title="Pindahkan Semua Data Spasial"
                                                            data-layer-id="{{ $layer->id }}"
                                                            data-layer-name="{{ $layer->name }}"
                                                            data-feature-count="{{ $layer->features_count }}">
                                                            <i class="mdi mdi-database-arrow-right-outline"></i>
                                                        </button>
                                                    @endif
                                                @endcan
                                                <form action="{{ route('spatial-layers.destroy', $layer->id) }}"
                                                    method="POST" style="display:inline-block; margin-left:-1px;"
                                                    data-confirm="delete" data-name="{{ $layer->name }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-start-0"
                                                        title="Hapus"><i class="mdi mdi-delete"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @if ($layers->count() == 0)
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="mdi mdi-layers-outline mdi-48px text-muted"></i>
                                            <h5 class="text-muted mt-2">Belum ada Layer yang dibuat</h5>
                                            <p class="text-muted">Klik tombol "Tambah Layer" untuk memulai</p>
                                            @can('spatial-layers.create')
                                                <a href="{{ route('spatial-layers.create') }}" class="btn btn-primary">
                                                    <i class="mdi mdi-plus"></i> Tambah Layer Pertama
                                                </a>
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

    <!-- Bulk Hapus Form (Hidden) -->
    <form id="layerBulkDestroyForm" method="POST" action="{{ route('spatial-layers.bulk-destroy') }}"
        style="display: none;" data-confirm="delete" data-name="Layer terpilih">
        @csrf
        <div id="layerBulkDestroyIds"></div>
    </form>

    <!-- Pindahkan Semua Data Spasial Modal -->
    <div class="modal fade" id="moveAllFeaturesModal" tabindex="-1" aria-labelledby="moveAllFeaturesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveAllFeaturesModalLabel">
                        <i class="mdi mdi-database-arrow-right-outline"></i> Pindahkan Semua Data Spasial
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="moveAllFeaturesForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small">
                            Pindahkan <strong id="moveAllFeaturesCount">0</strong> Data Spasial dari Layer
                            "<strong id="moveAllFeaturesSourceName"></strong>" ke Layer tujuan. Layer asal akan
                            kosong (bukan terhapus) setelah ini.
                        </p>

                        <div class="mb-2">
                            <label for="moveAllFeaturesTargetLayer" class="form-label">Layer Tujuan <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="moveAllFeaturesTargetLayer" name="target_layer_id" required>
                                <option value="">-- Pilih Layer Tujuan --</option>
                            </select>
                        </div>
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

    @can('spatial-layers.publish')
        <!-- Bulk Ubah Status Modal -->
        <div class="modal fade" id="bulkStatusModal" tabindex="-1" aria-labelledby="bulkStatusModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkStatusModalLabel">
                            <i class="mdi mdi-publish"></i> Ubah Status Layer
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="bulkStatusForm" method="POST" action="{{ route('spatial-layers.bulk-update-status') }}">
                        @csrf
                        <div class="modal-body">
                            <p class="text-muted small">
                                Ubah status <strong id="bulkStatusCount">0</strong> Layer terpilih.
                            </p>

                            <label for="bulkStatusSelect" class="form-label fw-semibold">Status baru</label>
                            <select id="bulkStatusSelect" name="status" class="form-select" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="published">Published (tampil di peta publik)</option>
                                <option value="draft">Draft</option>
                                <option value="archived">Archived</option>
                            </select>
                            <div class="form-text">
                                Layer tanpa Data Spasial atau tanpa style default dilewati saat dipublikasikan.
                            </div>

                            <div id="bulkStatusIds"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">
                                <i class="mdi mdi-check"></i> Simpan Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <!-- Bulk Pindah Kategori Modal -->
    <div class="modal fade" id="bulkCategoryModal" tabindex="-1" aria-labelledby="bulkCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkCategoryModalLabel">
                        <i class="mdi mdi-folder-move-outline"></i> Pindah Kategori
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="bulkCategoryForm" method="POST" action="{{ route('spatial-layers.bulk-update-category') }}">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted small">
                            Pindahkan <strong id="bulkCategoryCount">0</strong> Layer terpilih ke Kategori/Sub Kategori
                            baru.
                        </p>

                        @include('backend.pages.spatial-layers._category-picker', [
                            'prefix' => 'layer_bulk_category',
                            'selectedCategoryId' => null,
                            'selectedCategoryNodeId' => null,
                        ])

                        <div id="bulkCategoryIds"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-check"></i> Pindahkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
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
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            const categoryNodeOptions = @json($categoryNodeOptions);
            const categoryNodeById = new Map(categoryNodeOptions.map((node) => [node.id, node]));

            // category_nodes adalah POHON (parent_id self-referencing) — Layer
            // bisa ditempatkan di node mana pun di pohon itu, bukan cuma daun
            // (lihat _category-picker.blade.php, flat list semua depth). Jadi
            // "Sub Kategori" dipilih harus cocok dengan node itu SENDIRI atau
            // salah satu KETURUNANnya, sama seperti Kategori (root) yang juga
            // meliputi seluruh Sub Kategori di bawahnya. Exact-match id saja
            // membuat Layer yang ditempatkan di node anak tidak pernah muncul
            // saat user memilih node induknya.
            function categoryNodeMatchesFilter(rowNodeId, selectedNodeId) {
                let current = rowNodeId ? categoryNodeById.get(rowNodeId) : null;
                while (current) {
                    if (current.id === selectedNodeId) {
                        return true;
                    }
                    current = current.parent_id ? categoryNodeById.get(current.parent_id) : null;
                }
                return false;
            }

            // Sub Kategori dibangun ulang tiap Kategori (root) berubah — hanya
            // menampilkan node milik root yang dipilih, sama seperti picker
            // Kategori/Subkategori di form Layer (_category-picker.blade.php).
            function refreshCategoryNodeFilter() {
                const rootId = $('#categoryFilter').val();
                const $nodeFilter = $('#categoryNodeFilter');
                const preserved = $nodeFilter.val();

                $nodeFilter.html('<option value="">Semua Sub Kategori</option>');

                categoryNodeOptions
                    .filter((node) => !rootId || node.category_id === rootId)
                    .forEach((node) => {
                        const opt = document.createElement('option');
                        opt.value = node.id;
                        opt.textContent = '— '.repeat(Math.max(node.depth - 1, 0)) + node.name;
                        $nodeFilter.append(opt);
                    });

                const stillValid = categoryNodeOptions.some((node) => node.id === preserved && (!rootId ||
                    node.category_id === rootId));
                $nodeFilter.val(stillValid ? preserved : '');
            }

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
                if (settings.nTable.id !== 'spatialLayersTable') {
                    return true;
                }

                const row = settings.aoData[dataIndex].nTr;

                const status = $('#statusFilter').val();
                if (status && $(row).data('status') !== status) {
                    return false;
                }

                const opd = $('#opdFilter').val();
                if (opd && $(row).data('opd-id').toString() !== opd) {
                    return false;
                }

                // Kategori adalah ROOT (layers.category_id SELALU root, lihat
                // docblock SpatialLayer) — memfilter berdasarkan ini otomatis
                // ikut menampilkan Layer dari seluruh Sub Kategori di bawahnya,
                // tanpa logic tambahan. Sub Kategori memfilter lebih spesifik
                // ke satu node saja kalau dipilih.
                const category = $('#categoryFilter').val();
                if (category && $(row).data('category-id') !== category) {
                    return false;
                }

                const categoryNode = $('#categoryNodeFilter').val();
                if (categoryNode && !categoryNodeMatchesFilter($(row).data('category-node-id'), categoryNode)) {
                    return false;
                }

                const layerType = $('#layerTypeFilter').val();
                if (layerType && $(row).data('layer-type-id').toString() !== layerType) {
                    return false;
                }

                return true;
            });

            const table = $('#spatialLayersTable').DataTable({
                "processing": true,
                "pageLength": 200,
                "lengthMenu": [
                    [10, 25, 50, 100, 200, 500],
                    [10, 25, 50, 100, 200, 500]
                ],
                "ordering": true,
                "order": [],
                "columnDefs": [{
                        "orderable": false,
                        "targets": "_all"
                    },
                    {
                        "searchable": false,
                        "orderable": false,
                        "targets": [0, -1]
                    },
                    {
                        "className": "text-center",
                        "targets": [0, 1, -1]
                    },
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
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    },
                },
                "dom": '<"row"<"col-sm-12"tr>>' +
                    '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "initComplete": function() {
                    const STORAGE_KEY = 'spatialLayersIndexFilters';

                    function saveFiltersToStorage() {
                        try {
                            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                                status: $('#statusFilter').val(),
                                opd: $('#opdFilter').val(),
                                categoryId: $('#categoryFilter').val(),
                                categoryNodeId: $('#categoryNodeFilter').val(),
                                layerType: $('#layerTypeFilter').val(),
                                search: $('#tableSearch').val(),
                                sortBy: $('#sortBySelect').val(),
                                perPage: $('#per_page').val(),
                            }));
                        } catch (e) {
                            // localStorage bisa saja diblokir (mode privat dll.) —
                            // filter tetap bekerja untuk sesi ini, hanya tidak
                            // bertahan ke reload berikutnya.
                        }
                    }

                    function loadFiltersFromStorage() {
                        try {
                            return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
                        } catch (e) {
                            return null;
                        }
                    }

                    function clearFiltersStorage() {
                        try {
                            localStorage.removeItem(STORAGE_KEY);
                        } catch (e) {
                            // no-op
                        }
                    }

                    // PENTING: tidak boleh pakai `table` (const di luar) di sini —
                    // initComplete dipanggil SINKRON oleh DataTables di tengah
                    // evaluasi `const table = $(...).DataTable({...})`, jadi
                    // `table` belum selesai di-assign (temporal dead zone,
                    // ReferenceError kalau diakses). `this.api()` adalah API
                    // resmi DataTables untuk kasus ini — dipakai konsisten di
                    // SELURUH initComplete (termasuk closure event handler di
                    // bawah) supaya tidak ada yang diam-diam balik ke `table`.
                    const api = this.api();

                    $('#searchTableBtn').on('click', function() {
                        api.search($('#tableSearch').val()).draw();
                        saveFiltersToStorage();
                    });
                    $('#tableSearch').on('input', function() {
                        api.search($(this).val()).draw();
                        saveFiltersToStorage();
                    });
                    $('#per_page').on('change', function() {
                        api.page.len(parseInt($(this).val(), 10)).draw();
                        saveFiltersToStorage();
                    });

                    $('#categoryFilter').on('change', function() {
                        refreshCategoryNodeFilter();
                        api.draw();
                        saveFiltersToStorage();
                    });

                    $('#statusFilter, #opdFilter, #categoryNodeFilter, #layerTypeFilter').on('change',
                        function() {
                            api.draw();
                            saveFiltersToStorage();
                        });

                    $('#sortBySelect').on('change', function() {
                        const [column, direction] = $(this).val().split('-');
                        api.order([parseInt(column, 10), direction]).draw();
                        saveFiltersToStorage();
                    });

                    $('#resetFiltersBtn').on('click', function() {
                        $('#statusFilter, #opdFilter, #categoryFilter, #layerTypeFilter').val('');
                        refreshCategoryNodeFilter();
                        $('#tableSearch').val('');
                        $('#sortBySelect').val('2-asc');
                        api.search('').order([2, 'asc']).draw();
                        clearFiltersStorage();
                    });

                    // Terapkan filter tersimpan (localStorage) supaya setelah aksi
                    // yang memuat ulang halaman (hapus, bulk-hapus, dst.) user
                    // tidak perlu memfilter ulang dari awal. Parameter URL
                    // (?category_id=/&category_node_id=, dari link "panel isi
                    // katalog" §5.1 butir 4) SELALU menang atas localStorage —
                    // itu navigasi eksplisit, bukan sekadar state lama.
                    const urlParams = new URLSearchParams(window.location.search);
                    const stored = loadFiltersFromStorage() || {};
                    const filters = {
                        status: stored.status || '',
                        opd: stored.opd || '',
                        categoryId: urlParams.get('category_id') || stored.categoryId || '',
                        categoryNodeId: urlParams.get('category_node_id') || stored.categoryNodeId || '',
                        layerType: stored.layerType || '',
                        search: stored.search || '',
                        sortBy: stored.sortBy || '2-asc',
                        perPage: stored.perPage || '200',
                    };

                    $('#statusFilter').val(filters.status);
                    $('#opdFilter').val(filters.opd);
                    $('#layerTypeFilter').val(filters.layerType);
                    $('#categoryFilter').val(filters.categoryId);
                    refreshCategoryNodeFilter();
                    $('#categoryNodeFilter').val(filters.categoryNodeId);
                    $('#tableSearch').val(filters.search);
                    $('#sortBySelect').val(filters.sortBy);
                    $('#per_page').val(filters.perPage);

                    const [sortColumn, sortDirection] = filters.sortBy.split('-');
                    api.page.len(parseInt(filters.perPage, 10));
                    api.order([parseInt(sortColumn, 10), sortDirection]);
                    api.search(filters.search).draw();

                    saveFiltersToStorage();
                }
            });
        });
    </script>

    <script>
        /**
         * Bulk-selection tabel Layer — pola sama dengan bulk-selection Data
         * Spasial di spatial-layers/show.blade.php ("Feature"), diberi nama
         * terpisah ("Layer") supaya tidak bentrok. Seleksi lintas-halaman
         * TIDAK dipertahankan (DataTables hanya mem-render baris halaman
         * aktif ke DOM) — ini sengaja, sama seperti bulk-selection lain di
         * proyek ini: "pilih semua" berarti "semua baris yang sedang
         * terlihat", bukan seluruh hasil filter di semua halaman.
         */
        let selectedLayerItems = [];

        function updateLayerBulkActionsBar() {
            const bar = document.getElementById('layerBulkActionsBar');
            const countEl = document.getElementById('layerSelectedCount');

            if (selectedLayerItems.length > 0) {
                bar.classList.remove('d-none');
                countEl.textContent = selectedLayerItems.length;
            } else {
                bar.classList.add('d-none');
            }
        }

        function updateLayerSelectAllState() {
            const checkboxes = $('.layer-row-checkbox');
            const selectAllCheckbox = document.getElementById('layerSelectAll');
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

        function clearLayerSelection() {
            selectedLayerItems = [];
            document.querySelectorAll('.layer-row-checkbox').forEach((cb) => {
                cb.checked = false;
                cb.closest('tr')?.classList.remove('table-active');
            });
            document.getElementById('layerSelectAll').checked = false;
            document.getElementById('layerSelectAll').indeterminate = false;
            updateLayerBulkActionsBar();
        }

        $(document).on('change', '#layerSelectAll', function() {
            const isChecked = this.checked;

            $('.layer-row-checkbox').each(function() {
                this.checked = isChecked;
                const value = this.value;
                $(this).closest('tr').toggleClass('table-active', isChecked);

                if (isChecked && !selectedLayerItems.includes(value)) {
                    selectedLayerItems.push(value);
                } else if (!isChecked) {
                    selectedLayerItems = selectedLayerItems.filter((id) => id !== value);
                }
            });

            updateLayerBulkActionsBar();
        });

        $(document).on('change', '.layer-row-checkbox', function() {
            const value = this.value;

            if (this.checked) {
                if (!selectedLayerItems.includes(value)) {
                    selectedLayerItems.push(value);
                }
            } else {
                selectedLayerItems = selectedLayerItems.filter((id) => id !== value);
            }

            $(this).closest('tr').toggleClass('table-active', this.checked);
            updateLayerBulkActionsBar();
            updateLayerSelectAllState();
        });

        function bulkDeleteLayers() {
            if (selectedLayerItems.length === 0) {
                return;
            }

            const idsContainer = document.getElementById('layerBulkDestroyIds');
            idsContainer.innerHTML = '';
            selectedLayerItems.forEach((id) => {
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
            document.getElementById('layerBulkDestroyForm').requestSubmit();
        }

        function openBulkStatusModal() {
            if (selectedLayerItems.length === 0) {
                return;
            }

            document.getElementById('bulkStatusCount').textContent = selectedLayerItems.length;
            document.getElementById('bulkStatusSelect').value = '';

            const idsContainer = document.getElementById('bulkStatusIds');
            idsContainer.innerHTML = '';
            selectedLayerItems.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                idsContainer.appendChild(input);
            });

            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkStatusModal')).show();
        }

        function openBulkCategoryModal() {
            if (selectedLayerItems.length === 0) {
                return;
            }

            document.getElementById('bulkCategoryCount').textContent = selectedLayerItems.length;

            const idsContainer = document.getElementById('bulkCategoryIds');
            idsContainer.innerHTML = '';
            selectedLayerItems.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                idsContainer.appendChild(input);
            });

            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkCategoryModal')).show();
        }

        /**
         * Pindahkan SEMUA Data Spasial satu Layer ke Layer lain (per baris,
         * tombol ikon di kolom Aksi) — beda dari "Pindah Kategori" bulk di
         * atas yang memindahkan Layer itu sendiri antar Kategori. Daftar
         * Layer tujuan dibangun dari data yang sudah dirender di tabel
         * (bukan query baru) supaya konsisten dengan apa yang user lihat.
         */
        const allLayersForMove = @json($layers->sortBy('name')->values()->map(fn ($l) => ['id' => $l->id, 'name' => $l->name]));
        const moveAllFeaturesFormActionTemplate = @json(route('spatial-layers.features.move-all', ['spatialLayer' => '__LAYER_ID__']));

        document.querySelectorAll('.move-all-features-btn').forEach((btn) => {
            btn.addEventListener('click', function() {
                const layerId = this.dataset.layerId;

                document.getElementById('moveAllFeaturesCount').textContent = this.dataset.featureCount;
                document.getElementById('moveAllFeaturesSourceName').textContent = this.dataset.layerName;
                document.getElementById('moveAllFeaturesForm').action = moveAllFeaturesFormActionTemplate.replace(
                    '__LAYER_ID__', layerId);

                const targetSelect = document.getElementById('moveAllFeaturesTargetLayer');
                targetSelect.innerHTML = '<option value="">-- Pilih Layer Tujuan --</option>';
                allLayersForMove
                    .filter((l) => l.id !== layerId)
                    .forEach((l) => {
                        const opt = document.createElement('option');
                        opt.value = l.id;
                        opt.textContent = l.name;
                        targetSelect.appendChild(opt);
                    });

                bootstrap.Modal.getOrCreateInstance(document.getElementById('moveAllFeaturesModal')).show();
            });
        });

        @if ($errors->hasAny(['category_id', 'category_node_id']))
            // Validasi bulk pindah kategori gagal (mis. subkategori tidak
            // sesuai) — buka ulang modalnya dan kembalikan ids[] yang tadi
            // dikirim supaya submit ulang setelah memperbaiki pilihan tidak
            // kehilangan Layer mana saja yang dipilih (checkbox tabel sendiri
            // TIDAK dicentang ulang — DataTables hanya me-render baris
            // halaman aktif, jadi baris yang dipilih mungkin tidak terlihat).
            document.addEventListener('DOMContentLoaded', function() {
                const idsContainer = document.getElementById('bulkCategoryIds');
                @foreach (old('ids', []) as $oldId)
                    (function() {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = @json($oldId);
                        idsContainer.appendChild(input);
                    })();
                @endforeach
                document.getElementById('bulkCategoryCount').textContent = @json(count(old('ids', [])));
                bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkCategoryModal')).show();
            });
        @endif
    </script>
@endpush
