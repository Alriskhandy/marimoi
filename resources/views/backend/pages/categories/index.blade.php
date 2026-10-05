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
                                <h3 class="stat-value">{{ $categories->whereNull('parent_id')->sum('data_spatial_count') }}</h3>
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
                                title="Pilih kategori di pohon taksonomi terlebih dahulu">
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
                    <h4 class="card-title mb-0"><i class="mdi mdi-file-tree-outline me-1"></i>Pohon Taksonomi Laye</h4>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                        <input type="text" id="treeSearch" class="form-control"
                            placeholder="Saring kategori berdasarkan nama...">
                    </div>

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
                            <p class="mt-2 mb-0">Pilih kategori di pohon taksonomi untuk melihat detail & daftar
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
                            <input type="number" class="form-control" id="add_sort_order" name="sort_order" min="0"
                                value="0">
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
                            <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
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
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Pohon Taksonomi: pilih kategori -> tampilkan panel detailnya,
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
