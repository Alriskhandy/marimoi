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
                                <p class="stat-label">Layer Akar</p>
                                <h3 class="stat-value">{{ $roots->count() }}</h3>
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
                                <p class="stat-label">Sub Layer</p>
                                <h3 class="stat-value">{{ $layers->whereNotNull('parent_id')->count() }}</h3>
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
                        <a href="{{ route('spatial-layers.create') }}" class="btn btn-gradient-primary">
                            <i class="mdi mdi-plus"></i> Tambah Layer
                        </a>
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
                                    <th>Nama & Hirarki</th>
                                    <th>Jenis</th>
                                    <th style="width: 15%;">Style</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $no = 1;

                                    // Guard function_exists() wajib — deklarasi fungsi top-level di dalam view
                                    // Blade akan fatal "Cannot redeclare" kalau halaman ini dirender lebih dari
                                    // sekali dalam satu proses PHP (mis. beberapa test yang GET index ini di
                                    // satu test run, atau di production kalau view pernah ter-include ganda).
                                    if (! function_exists('renderSpatialLayerHierarchy')) {
                                    function renderSpatialLayerHierarchy($layers, &$no, $parentId = null, $level = 0)
                                    {
                                        $filtered = $layers->where('parent_id', $parentId)->sortBy('name');
                                        $output = '';

                                        foreach ($filtered as $layer) {
                                            $hasChildren = $layer->children_count > 0;
                                            $indentStyle = $level > 0 ? 'padding-left:' . ($level * 1.5) . 'rem;' : '';

                                            $output .= '<tr data-layer-id="' . $layer->id . '" data-parent-id="' . $layer->parent_id . '" data-map-type-id="' . ($layer->map_type_id ?? '0') . '"';
                                            $output .= ' class="spatial-layer-row' . ($level > 0 ? ' children-of-' . $layer->parent_id : '') . '"';
                                            if ($level > 0) {
                                                $output .= ' style="display:none;"';
                                            }
                                            $output .= '>';

                                            $output .= '<td><div class="checkbox-wrapper"><input class="form-check-input row-checkbox" type="checkbox" value="' . $layer->id . '" id="check-' . $layer->id . '"><label class="form-check-label" for="check-' . $layer->id . '"><span class="visually-hidden">Select row</span></label></div></td>';

                                            $output .= '<td>' . $no++ . '</td>';

                                            $output .= '<td><div class="d-flex align-items-center" style="' . $indentStyle . '">';
                                            if ($hasChildren) {
                                                $output .= '<button type="button" class="btn btn-link btn-sm p-0 me-2 hierarchy-toggle text-secondary" data-target="children-of-' . $layer->id . '" title="Expand"><i class="mdi mdi-chevron-right"></i></button>';
                                            } else {
                                                $output .= '<span class="me-4"></span>';
                                            }
                                            $output .= '<div><span class="text-dark ' . ($level == 0 ? 'fw-bold' : 'fw-medium') . '">' . e($layer->name) . '</span>';
                                            if ($layer->description) {
                                                $output .= '<br><small class="text-muted">' . e(\Illuminate\Support\Str::limit($layer->description, 50)) . '</small>';
                                            }
                                            if ($hasChildren) {
                                                $output .= '<span class="badge bg-info text-white ms-2" style="font-size:0.65em;">' . $layer->children_count . ' sub</span>';
                                            }
                                            if ($layer->features_count > 0) {
                                                $output .= '<span class="badge bg-success text-white ms-2" style="font-size:0.65em;">' . $layer->features_count . ' data</span>';
                                            }
                                            $output .= '</div></div></td>';

                                            $output .= '<td>' . ($layer->mapType?->nama ? '<span class="badge bg-primary text-white">' . e($layer->mapType->nama) . '</span>' : '<span class="text-muted">-</span>') . '</td>';

                                            // Style — gabungan warna, tipe (marker/layer), dan icon dalam 1 kolom.
                                            $output .= '<td><div class="d-flex align-items-center gap-2 flex-wrap">';
                                            if ($layer->color) {
                                                $output .= '<span class="color-box" style="display:inline-block;background-color:' . $layer->color . ';width:18px;height:18px;border-radius:4px;border:1px solid #dee2e6;box-shadow:0 1px 3px rgba(0,0,0,0.1);" title="' . $layer->color . '"></span>';
                                            }
                                            if ($layer->is_marker && $layer->icon) {
                                                $output .= '<i class="' . $layer->icon . '" style="color:' . ($layer->color ?? '#007bff') . ';font-size:1.2em;" title="' . e($layer->icon) . '"></i>';
                                            }
                                            $output .= $layer->is_marker
                                                ? '<span class="badge bg-warning text-dark"><i class="mdi mdi-map-marker"></i> Marker</span>'
                                                : '<span class="badge bg-info text-white"><i class="mdi mdi-layers"></i> Layer</span>';
                                            $output .= '</div></td>';

                                            $output .= '<td><div class="btn-group" role="group">';
                                            $output .= '<a href="' . route('spatial-layers.show', $layer->id) . '" class="btn btn-sm btn-outline-primary" title="Detail"><i class="mdi mdi-eye"></i></a>';
                                            $output .= '<form action="' . route('spatial-layers.destroy', $layer->id) . '" method="POST" style="display:inline-block" data-confirm="delete" data-name="' . e($layer->name) . '">';
                                            $output .= csrf_field() . method_field('DELETE');
                                            $output .= '<button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="mdi mdi-delete"></i></button>';
                                            $output .= '</form></div></td>';

                                            $output .= '</tr>';

                                            if ($hasChildren) {
                                                $output .= renderSpatialLayerHierarchy($layers, $no, $layer->id, $level + 1);
                                            }
                                        }

                                        return $output;
                                    }
                                    }

                                    echo renderSpatialLayerHierarchy($layers, $no);
                                @endphp

                                @if ($layers->count() == 0)
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="mdi mdi-layers-outline mdi-48px text-muted"></i>
                                            <h5 class="text-muted mt-2">Belum ada Layer yang dibuat</h5>
                                            <p class="text-muted">Klik tombol "Tambah Layer" untuk memulai</p>
                                            <a href="{{ route('spatial-layers.create') }}" class="btn btn-primary">
                                                <i class="mdi mdi-plus"></i> Tambah Layer Pertama
                                            </a>
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
           HIERARCHY CONTROLS STYLING — sempat lewat tersalin dari categories/
           index.blade.php, tombol "Expand All" jadi tanpa gaya (warna default
           Bootstrap polos, kontras buruk).
           =========================================== */
        .hierarchy-controls {
            margin-bottom: 10px;
        }

        .hierarchy-toggle-all {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.25);
        }

        .hierarchy-toggle-all:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.35);
            color: white;
        }

        .hierarchy-toggle-all:active {
            transform: translateY(0);
        }

        .hierarchy-toggle-all[data-state="expanded"] {
            background: linear-gradient(135deg, #6c757d, #545b62);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .hierarchy-toggle-all[data-state="expanded"]:hover {
            background: linear-gradient(135deg, #545b62, #383d41);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        /* Tombol expand per-baris — warna eksplisit, bukan cuma text-secondary/
           text-primary bawaan Bootstrap yang gampang tertimpa .btn-link.
           Ditukar (2026-09-30): default (collapsed) sekarang biru, expanded abu. */
        .hierarchy-toggle {
            color: #0d6efd !important;
        }

        .hierarchy-toggle:hover {
            color: #6c757d !important;
        }

        .hierarchy-toggle.text-primary {
            color: #6c757d !important;
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
                "dom": '<"row mb-2"<"col-sm-12"<"hierarchy-controls text-start">>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "drawCallback": function () {
                    initializeHierarchyControls();
                },
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

                    if ($('.hierarchy-toggle').length > 0) {
                        $('.hierarchy-controls').html(
                            '<button type="button" class="btn hierarchy-toggle-all" id="hierarchyToggleAll" data-state="collapsed">' +
                            '<i class="mdi mdi-chevron-down me-1"></i><span class="toggle-text">Expand All</span></button>'
                        );
                    }

                    initializeHierarchyControls();
                    $('.spatial-layer-row[class*="children-of-"]').hide();
                    $('.hierarchy-toggle i').addClass('collapsed');
                }
            });

            function initializeHierarchyControls() {
                if ($('.hierarchy-toggle').length === 0) return;

                $('.hierarchy-toggle').off('click').on('click', function (e) {
                    e.preventDefault();
                    const target = $(this).data('target');
                    const childRows = $(`.${target}`);
                    const icon = $(this).find('i');

                    if (childRows.is(':visible')) {
                        childRows.slideUp(200);
                        icon.addClass('collapsed');
                        $(this).attr('title', 'Expand').removeClass('text-primary').addClass('text-secondary');
                    } else {
                        childRows.slideDown(200);
                        icon.removeClass('collapsed');
                        $(this).attr('title', 'Collapse').removeClass('text-secondary').addClass('text-primary');
                    }
                });

                $('#hierarchyToggleAll').off('click').on('click', function () {
                    const $btn = $(this);
                    const currentState = $btn.attr('data-state');

                    if (currentState === 'collapsed') {
                        $('[class*="children-of-"]').slideDown(200);
                        $('.hierarchy-toggle i').removeClass('collapsed');
                        $btn.attr('data-state', 'expanded').find('.toggle-text').text('Collapse All');
                        $btn.find('i').removeClass('mdi-chevron-down').addClass('mdi-chevron-up');
                    } else {
                        $('[class*="children-of-"]').slideUp(200);
                        $('.hierarchy-toggle i').addClass('collapsed');
                        $btn.attr('data-state', 'collapsed').find('.toggle-text').text('Expand All');
                        $btn.find('i').removeClass('mdi-chevron-up').addClass('mdi-chevron-down');
                    }
                });
            }
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
@endpush
