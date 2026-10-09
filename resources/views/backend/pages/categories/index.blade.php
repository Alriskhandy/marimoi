@extends('backend.partials.main', ['title' => 'Kategori'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-tag-multiple"></i>
            </span>
            Kategori
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Kategori
                </li>
            </ul>
        </nav>
    </div>


    <!-- Statistics Cards -->
    @if ($categories->count() > 0)
        <div class="row g-3 stats-row-compact">
            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Kategori Utama</p>
                                <h3 class="stat-value">{{ $categories->where('parent_id', null)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-format-list-bulleted-type stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Sub Kategori</p>
                                <h3 class="stat-value">{{ $categories->where('parent_id', '!=', null)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-subdirectory-arrow-right stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Total Layer</p>
                                <h3 class="stat-value">{{ $categories->whereNull('parent_id')->sum('layers_count') }}</h3>
                            </div>
                            <i class="mdi mdi-layers stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Total Data Spasial</p>
                                <h3 class="stat-value">{{ $categories->whereNull('parent_id')->sum('data_spatial_count') }}
                                </h3>
                            </div>
                            <i class="mdi mdi-map-marker-multiple stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Toolbar: aksi tambah kategori -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-wrap justify-content-end align-items-center gap-2">
                    @can('categories.create')
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary" id="btnAddRootCategory"
                                data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="mdi mdi-folder-plus"></i> Kategori Utama
                            </button>
                            <button type="button" class="btn btn-gradient-primary" id="btnAddSubCategory"
                                data-bs-toggle="modal" data-bs-target="#addModal" disabled
                                title="Pilih kategori di struktur kategori terlebih dahulu">
                                <i class="mdi mdi-plus"></i> Subkategori
                            </button>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 grid-margin stretch-card">
            <div class="card taxonomy-tree-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><i class="mdi mdi-file-tree-outline me-1"></i>Struktur Kategori</h4>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                        <input type="text" id="treeSearch" class="form-control"
                            placeholder="Saring kategori berdasarkan nama...">
                    </div>

                    @can('categories.delete')
                        <div id="categoryBulkActionsBar" class="alert alert-info d-none py-2 px-3 mb-3"
                            role="alert">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <small><i class="mdi mdi-checkbox-multiple-marked me-1"></i>
                                    <span id="categorySelectedCount">0</span> dipilih</small>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="bulkDeleteCategories()">
                                        <i class="mdi mdi-delete me-1"></i> Hapus Terpilih
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick="clearCategorySelection()">
                                        <i class="mdi mdi-close me-1"></i> Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endcan

                    @php
                        $user = Auth::user();
                        $role = $user->role->slug ?? null;
                        $rootCategories = $categories->whereNull('parent_id')->sortBy(['sort_order', 'nama']);
                    @endphp

                    <ul class="taxonomy-tree" id="taxonomyTree">
                        @forelse ($rootCategories as $root)
                            @include('backend.pages.categories._tree-node', ['category' => $root])
                        @empty
                            <li class="text-muted small px-2 py-3">Belum ada kategori.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="card-footer d-flex justify-content-between text-muted small">
                    <span>{{ $categories->count() }} Kategori</span>
                    <span>{{ $rootCategories->sum('layers_count') }} Total Layer</span>
                </div>
            </div>
        </div>

        <div class="col-lg-8 grid-margin stretch-card">
            <div id="categoryDetailPanels">
                @foreach ($categories as $category)
                    @include('backend.pages.categories._detail-panel', [
                        'category' => $category,
                        'user' => $user,
                        'role' => $role,
                    ])
                @endforeach

                @if ($categories->isEmpty())
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="mdi mdi-tag-multiple mdi-48px text-muted"></i>
                            <h5 class="text-muted mt-2">Belum ada kategori yang dibuat</h5>
                            <p class="text-muted">Klik tombol "Kategori Utama" untuk memulai</p>
                            @can('categories.create')
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#addModal">
                                    <i class="mdi mdi-plus"></i> Tambah Kategori Pertama
                                </button>
                            @endcan
                        </div>
                    </div>
                @else
                    <div class="card" id="noCategorySelectedCard" style="display: none;">
                        <div class="card-body text-center py-5 text-muted">
                            <i class="mdi mdi-cursor-default-click-outline mdi-48px"></i>
                            <p class="mt-2 mb-0">Pilih kategori di struktur kategori untuk melihat detail & daftar
                                layer.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="mdi mdi-plus"></i> Tambah Kategori
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-2">
                            <label for="add_nama" class="form-label">Nama Kategori <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_nama" name="nama" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group mb-2">
                            <label for="add_deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="add_deskripsi" name="deskripsi" rows="2"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group mb-2">
                            <label for="add_parent_id" class="form-label">Parent Kategori</label>
                            <select class="form-control" id="add_parent_id" name="parent_id">
                                <option value="">-- Pilih Parent (Opsional) --</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="add_sort_order" class="form-label">Urutan Tampil</label>
                            <input type="number" class="form-control" id="add_sort_order" name="sort_order"
                                min="0" value="0">
                            <div class="form-text">Angka lebih kecil ditampilkan lebih dulu.</div>
                            <div class="invalid-feedback"></div>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="mdi mdi-pencil"></i> Edit Kategori
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="editForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="form-group mb-2">
                            <label for="edit_nama" class="form-label">Nama Kategori <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nama" name="nama" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group mb-2">
                            <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="2"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group mb-2">
                            <label for="edit_parent_id" class="form-label">Parent Kategori</label>
                            <select class="form-control" id="edit_parent_id" name="parent_id">
                                <option value="">-- Pilih Parent (Opsional) --</option>
                            </select>
                            <div class="invalid-feedback"></div>
                            <div class="form-text">Memindahkan parent otomatis memperbarui urutan hirarki seluruh
                                sub-kategori di bawahnya.</div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="edit_sort_order" class="form-label">Urutan Tampil</label>
                            <input type="number" class="form-control" id="edit_sort_order" name="sort_order"
                                min="0">
                            <div class="form-text">Angka lebih kecil ditampilkan lebih dulu.</div>
                            <div class="invalid-feedback"></div>
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

    <!-- Bulk Hapus Form (Hidden) -->
    <form id="categoryBulkDestroyForm" method="POST" action="{{ route('categories.bulk-destroy') }}"
        style="display: none;" data-confirm="delete" data-name="Kategori/Subkategori terpilih">
        @csrf
        <div id="categoryBulkDestroyIds"></div>
    </form>
@endsection

@push('styles')
    <style>
        /* ===========================================
                                                                                                                                                                                                           TAXONOMY TREE (PANEL KIRI)
                                                                                                                                                                                                        =========================================== */
        .taxonomy-tree-card .card-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .taxonomy-tree,
        .taxonomy-tree-children {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .taxonomy-tree-children {
            padding-left: 1.1rem;
            display: none;
        }

        .taxonomy-tree-node.expanded>.taxonomy-tree-children {
            display: block;
        }

        .taxonomy-tree-row {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .taxonomy-tree-row:hover {
            background-color: rgba(0, 123, 255, 0.08);
        }

        .taxonomy-tree-row.active {
            background-color: rgba(0, 123, 255, 0.15);
            font-weight: 600;
        }

        .taxonomy-tree-toggle,
        .taxonomy-tree-toggle-spacer {
            width: 18px;
            flex-shrink: 0;
        }

        .taxonomy-tree-toggle {
            background: none;
            border: none;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

        .taxonomy-tree-toggle i {
            transition: transform 0.15s ease;
        }

        .taxonomy-tree-node.expanded>.taxonomy-tree-row .taxonomy-tree-toggle i {
            transform: rotate(90deg);
        }

        .taxonomy-tree-icon {
            font-size: 1rem;
            color: #6c757d;
            flex-shrink: 0;
        }

        .taxonomy-tree-label {
            flex: 1 1 auto;
            font-size: 0.9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .taxonomy-tree-badge {
            font-size: 0.7rem;
        }

        /* ===========================================
                                                                                                                                                                                                           CATEGORY DETAIL PANEL (PANEL KANAN)
                                                                                                                                                                                                        =========================================== */
        .category-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
        }

        .category-detail-meta .text-muted.small {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .category-layer-card {
            border: 1px solid #eef2f7;
            transition: box-shadow 0.15s ease;
        }

        .category-layer-card:hover {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .category-layer-meta .text-muted.small {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ===========================================
                                                                                                                                                                                                           BADGE STYLING
                                                                                                                                                                                                        =========================================== */
        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .bg-secondary {
            background-color: #6c757d !important;
        }

        .bg-info {
            background-color: #17a2b8 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        /* ===========================================
                                                                                                                                                                                                           COLOR PREVIEW STYLING
                                                                                                                                                                                                        =========================================== */
        .color-preview {
            display: flex;
            align-items: center;
        }

        .color-box {
            display: inline-block;
            border: 1px solid #dee2e6;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* ===========================================
                                                                                                                                                                                                           BUTTON GROUP STYLING
                                                                                                                                                                                                        =========================================== */
        .btn-group .btn {
            border-radius: 6px !important;
            margin: 0 2px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        /* ===========================================
                                                                                                                                                                                                           MODAL STYLING
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

        .image-preview-box {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            padding: 12px;
            text-align: center;
            border: 1px dashed #ced4da;
            border-radius: 8px;
            background-color: #f8f9fa;
        }

        .image-preview-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            color: #adb5bd;
        }

        .image-preview-placeholder i {
            font-size: 2.2rem;
        }

        .image-preview-placeholder span {
            font-size: 0.8rem;
        }

        .image-preview-content {
            width: 100%;
        }

        .image-preview-content img {
            max-width: 100%;
            max-height: 160px;
            border-radius: 6px;
            object-fit: contain;
        }

        /* ===========================================
                                                                                                                                                                                                           ICON PICKER
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

        /* ===========================================
                                                                                                                                                                                                           COLOR PICKER WIDGET
                                                                                                                                                                                                        =========================================== */
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

        /* ===========================================
                                                                                                                                                                                                           ICON PREVIEW STYLING
                                                                                                                                                                                                        =========================================== */
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

        /* Compact variant used when the preview sits beside the icon search box */
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

        /* ===========================================
                                                                                                                                                                                                           STATISTICS CARDS (COMPACT)
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
            font-size: 1.6rem;
            opacity: 0.55;
            flex-shrink: 0;
            margin-left: 0.5rem;
        }

        @media (max-width: 576px) {
            .stat-card-compact .stat-value {
                font-size: 1.25rem;
            }

            .stat-card-compact .stat-icon {
                font-size: 1.3rem;
            }
        }

        /* ===========================================
                                                                                                                                                                                                           UTILITY CLASSES
                                                                                                                                                                                                        =========================================== */
        .text-center i.mdi-48px {
            font-size: 3rem;
        }

        .mdi-subdirectory-arrow-right {
            font-size: 1.2em;
        }

        /* ===========================================
                                                                                                                                                                                                           RESPONSIVE IMPROVEMENTS
                                                                                                                                                                                                        =========================================== */
        @media (max-width: 768px) {
            .btn-sm {
                padding: 4px 8px;
                font-size: 0.8rem;
            }

            .badge {
                font-size: 0.7rem;
                padding: 4px 8px;
            }

            .icon-preview-container {
                min-height: 50px;
                padding: 10px;
            }

            .icon-preview-icon {
                font-size: 2em;
                margin-right: 10px;
            }

            .category-thumb {
                width: 35px !important;
                height: 35px !important;
            }
        }

        /* ===========================================
                                                                                                                                                                                                           FOCUS AND ACCESSIBILITY
                                                                                                                                                                                                        =========================================== */
        .btn:focus,
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .visually-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        /* ===========================================
                                                                                                                                                                                                           ACTIVE COUNT WARNING STYLES
                                                                                                                                                                                                        =========================================== */
        .form-text.text-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 8px;
            font-size: 0.875rem;
        }

        .form-check.text-muted {
            opacity: 0.6;
        }

        .form-check.text-muted .form-check-label {
            color: #6c757d !important;
        }

        .form-check-input:disabled {
            opacity: 0.5;
        }

        /* Active count badge styling */
        .active-count-badge {
            background: linear-gradient(135deg, #ffc107, #ff8f00);
            color: #212529;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .active-count-badge.warning {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border-color: rgba(220, 53, 69, 0.3);
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Struktur Kategori: pilih kategori -> tampilkan panel detailnya,
            // expand/collapse manual, dan filter pencarian nama kategori.
            const $tree = $('#taxonomyTree');

            function selectCategory(id) {
                $('.taxonomy-tree-row.active').removeClass('active');
                $('.category-detail-panel').hide();
                $('#noCategorySelectedCard').hide();

                const $row = $tree.find(`.taxonomy-tree-row[data-category-id="${id}"]`);
                const $panel = $(`#category-panel-${id}`);

                if ($row.length) {
                    $row.addClass('active');

                    // Buka seluruh leluhur supaya node terpilih tetap terlihat
                    let $node = $row.closest('.taxonomy-tree-node');
                    while ($node.length) {
                        $node.addClass('expanded');
                        $node = $node.parent().closest('.taxonomy-tree-node');
                    }
                }

                if ($panel.length) {
                    $panel.show();
                }

                const $subBtn = $('#btnAddSubCategory');
                if ($subBtn.length) {
                    $subBtn.prop('disabled', false);
                    $subBtn.data('root-id', $row.data('root-id') || id);
                    $subBtn.data('type', $row.data('type') || '');
                }
            }

            $tree.on('click', '.taxonomy-tree-toggle', function(e) {
                e.stopPropagation();
                $(this).closest('.taxonomy-tree-node').toggleClass('expanded');
            });

            // Checkbox hapus massal tidak boleh ikut memicu selectCategory()
            // (klik checkbox bukan maksud "lihat detail kategori ini").
            $tree.on('click', '.category-row-checkbox', function(e) {
                e.stopPropagation();
            });

            $tree.on('click', '.taxonomy-tree-row', function() {
                selectCategory($(this).data('category-id'));
            });

            $('#treeSearch').on('input', function() {
                const term = $(this).val().trim().toLowerCase();
                const $rows = $tree.find('.taxonomy-tree-row');

                if (!term) {
                    $tree.find('.taxonomy-tree-node').show();
                    return;
                }

                $rows.each(function() {
                    const $row = $(this);
                    const $node = $row.closest('.taxonomy-tree-node');
                    const matches = ($row.data('search') || '').toString().includes(term);

                    $node.toggle(matches);

                    if (matches) {
                        $node.addClass('expanded');
                        let $parentNode = $node.parent().closest('.taxonomy-tree-node');
                        while ($parentNode.length) {
                            $parentNode.show().addClass('expanded');
                            $parentNode = $parentNode.parent().closest('.taxonomy-tree-node');
                        }
                    }
                });
            });

            // Filter pencarian & tipe geometri di dalam tiap panel detail kategori
            $('#categoryDetailPanels').on('input', '.layer-search', function() {
                applyLayerFilters($(this).closest('.category-detail-panel'));
            });

            $('#categoryDetailPanels').on('click', '.layer-geometry-filter button', function() {
                const $group = $(this).closest('.layer-geometry-filter');
                $group.find('button').removeClass('active');
                $(this).addClass('active');
                applyLayerFilters($(this).closest('.category-detail-panel'));
            });

            function applyLayerFilters($panel) {
                const term = ($panel.find('.layer-search').val() || '').trim().toLowerCase();
                const geometry = $panel.find('.layer-geometry-filter button.active').data('geometry') || '';
                let visibleCount = 0;

                $panel.find('.category-layer-card').each(function() {
                    const $card = $(this);
                    const matchesSearch = !term || (($card.data('layer-search') || '').toString().includes(
                        term));
                    const matchesGeometry = !geometry || (($card.data('geometry') || '').toString()
                        .includes(geometry));
                    const visible = matchesSearch && matchesGeometry;
                    $card.toggle(visible);
                    if (visible) visibleCount++;
                });

                $panel.find('.category-layer-no-match').toggle(visibleCount === 0 && $panel.find(
                    '.category-layer-card').length > 0);
            }

            // Pilih kategori pertama di pohon secara default supaya panel kanan tidak kosong
            const $firstRow = $tree.find('.taxonomy-tree-row').first();
            if ($firstRow.length) {
                selectCategory($firstRow.data('category-id'));
            } else {
                $('#noCategorySelectedCard').show();
            }

            // Show Alert Function
            function showAlert(message, type = 'success') {
                const icon = type === 'success' ? 'success' : 'error';
                const title = type === 'success' ? 'Berhasil!' : 'Error!';

                Swal.fire({
                    title: title,
                    text: message,
                    icon: icon,
                    timer: 4000,
                    showConfirmButton: false,
                    allowOutsideClick: true,
                    allowEscapeKey: true
                });
            }

            // Clear form errors
            function clearFormErrors(form) {
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
            }

            // Show form errors
            function showFormErrors(form, errors) {
                clearFormErrors(form);
                $.each(errors, function(field, messages) {
                    const input = form.find(`[name="${field}"]`);
                    input.addClass('is-invalid');
                    input.siblings('.invalid-feedback').text(messages[0]);
                });
            }

            // Load parent categories for form
            function loadParentCategories(selectElement, excludeId = null) {
                selectElement.empty();
                selectElement.append('<option value="">-- Pilih Parent (Opsional) --</option>');

                $.get('{{ route('categories.api.options') }}', function(response) {
                    if (response.success) {
                        $.each(response.data, function(index, kategori) {
                            if (excludeId && kategori.id == excludeId) return;
                            if (kategori.parent_id == null) { // Only show parent categories
                                selectElement.append(
                                    `<option value="${kategori.id}">${kategori.nama}</option>`);
                            }
                        });
                    }
                }).fail(function() {
                    console.warn('Failed to load parent categories');
                });
            }

            // Add Modal setup
            $('#addModal').on('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                const form = $('#addForm');
                form[0].reset();
                clearFormErrors(form);

                loadParentCategories($('#add_parent_id'));

                // "+ Subkategori" dipicu dari panel detail/toolbar: paksa parent-nya
                // mengikuti kategori utama yang sedang dipilih di pohon (lihat
                // selectCategory() di atas yang mengisi data-root-id tombol
                // #btnAddSubCategory).
                const isSubCategory = trigger && trigger.id === 'btnAddSubCategory';

                if (isSubCategory && $(trigger).data('root-id')) {
                    setTimeout(function() {
                        $('#add_parent_id').val($(trigger).data('root-id'));
                    }, 600);
                }
            });

            // Add Form Submit
            $('#addForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = new FormData(this);
                const submitBtn = form.find('button[type="submit"]');

                // Show loading state
                submitBtn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin me-1"></i>Menyimpan...');

                $.ajax({
                    url: '{{ route('categories.store') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#addModal').modal('hide');
                            showAlert(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            showFormErrors(form, xhr.responseJSON.errors);
                        } else {
                            showAlert('Terjadi kesalahan server', 'error');
                        }
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.prop('disabled', false).html(
                            '<i class="mdi mdi-content-save"></i> Simpan');
                    }
                });
            });

            // Edit Modal setup
            $(document).on('click', '.btn-edit', function() {
                const form = $('#editForm');
                const id = $(this).data('id');

                $('#edit_id').val(id);
                $('#edit_nama').val($(this).data('nama'));
                $('#edit_deskripsi').val($(this).data('deskripsi'));
                $('#edit_sort_order').val($(this).data('sort-order') ?? 0);

                // Load parent categories
                loadParentCategories($('#edit_parent_id'), id);

                // Set selected parent after loading options
                const parentId = $(this).data('parent-id');
                setTimeout(function() {
                    if (parentId) {
                        $('#edit_parent_id').val(parentId);
                    }
                }, 500);

                clearFormErrors(form);
            });

            // Edit Form Submit
            $('#editForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const id = $('#edit_id').val();
                const formData = new FormData(this);
                const submitBtn = form.find('button[type="submit"]');

                // Show loading state
                submitBtn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin me-1"></i>Memperbarui...');

                $.ajax({
                    url: `{{ route('categories.index') }}/${id}`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#editModal').modal('hide');
                            showAlert(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            showFormErrors(form, xhr.responseJSON.errors);
                        } else {
                            showAlert('Terjadi kesalahan server', 'error');
                        }
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.prop('disabled', false).html(
                            '<i class="mdi mdi-content-save"></i> Update');
                    }
                });
            });

            // Type filter change handler
            $('#typeFilter').on('change', function() {
                const selectedType = $(this).val();
                if (selectedType) {
                    window.location.href = `{{ route('categories.index') }}?type=${selectedType}`;
                } else {
                    window.location.href = `{{ route('categories.index') }}`;
                }
            });

            // Hapus massal Kategori/Subkategori — checkbox di struktur pohon
            // (lihat _tree-node.blade.php), pola sama dengan bulk-selection
            // Layer/Data Spasial di spatial-layers/index.blade.php & show.blade.php.
            let selectedCategoryIds = [];

            function updateCategoryBulkActionsBar() {
                const bar = document.getElementById('categoryBulkActionsBar');
                if (!bar) {
                    return;
                }
                document.getElementById('categorySelectedCount').textContent = selectedCategoryIds.length;
                bar.classList.toggle('d-none', selectedCategoryIds.length === 0);
            }

            $tree.on('change', '.category-row-checkbox', function() {
                const id = this.value;

                if (this.checked) {
                    if (!selectedCategoryIds.includes(id)) {
                        selectedCategoryIds.push(id);
                    }
                } else {
                    selectedCategoryIds = selectedCategoryIds.filter((existing) => existing !== id);
                }

                updateCategoryBulkActionsBar();
            });

            window.clearCategorySelection = function() {
                selectedCategoryIds = [];
                $tree.find('.category-row-checkbox').prop('checked', false);
                updateCategoryBulkActionsBar();
            };

            window.bulkDeleteCategories = function() {
                if (selectedCategoryIds.length === 0) {
                    return;
                }

                const idsContainer = document.getElementById('categoryBulkDestroyIds');
                idsContainer.innerHTML = '';
                selectedCategoryIds.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    idsContainer.appendChild(input);
                });

                // requestSubmit() (bukan submit()) supaya event 'submit' tetap
                // terpicu — handler data-confirm="delete" di bawah menunggu
                // event ini untuk menampilkan konfirmasi sebelum benar-benar
                // mengirim form.
                document.getElementById('categoryBulkDestroyForm').requestSubmit();
            };

            // Enhanced delete confirmation
            $('form[data-confirm="delete"]').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const categoryName = $(form).data('name');

                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    html: `Apakah Anda yakin ingin menghapus kategori <strong>"${categoryName}"</strong>?<br><small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Menghapus...',
                            text: 'Mohon tunggu sebentar',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        form.submit();
                    }
                });
            });

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + N to add new category
                if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                    e.preventDefault();
                    $('#addModal').modal('show');
                }

                // Escape to close modals
                if (e.key === 'Escape') {
                    $('.modal').modal('hide');
                }
            });

            // Add tooltips
            $('[title]').tooltip({
                placement: 'top',
                trigger: 'hover'
            });

            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);

            // Initialize tooltips for keyboard shortcuts
            $('#btnAddRootCategory').attr('title', 'Keyboard shortcut: Ctrl+N');
        });
    </script>
@endpush
