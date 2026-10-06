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
                                <div class="col-lg-3 col-md-3">
                                    <label for="categoryFilter" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-shape-outline me-1"></i>Kategori
                                    </label>
                                    <select class="form-select filter-control" id="categoryFilter">
                                        <option value="">Semua Kategori</option>
                                        @foreach (collect($categoryPaths)->sort() as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-3">
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

                                <div class="col-lg-3 col-md-2">
                                    <label for="layerTypeFilter" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-shape-outline me-1"></i>Tipe
                                    </label>
                                    <select class="form-select filter-control" id="layerTypeFilter">
                                        <option value="">Semua Tipe</option>
                                        @foreach ($layerTypes->sortBy('id') as $layerType)
                                            <option value="{{ $layerType->id }}">{{ $layerType->geometry_type ?? $layerType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-3 col-md-3">
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
                                        <option value="1-asc">Nama (A-Z)</option>
                                        <option value="1-desc">Nama (Z-A)</option>
                                        <option value="2-asc">Kategori (A-Z)</option>
                                        <option value="4-asc">Status (A-Z)</option>
                                        <option value="4-desc">Status (Z-A)</option>
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

                    <div class="table-responsive">
                        <table id="spatialLayersTable" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
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
                                        data-opd-id="{{ $layer->opd_id ?? '0' }}" data-category-key="{{ $categoryKey }}"
                                        data-layer-type-id="{{ $layer->layer_type_id ?? '0' }}"
                                        class="spatial-layer-row">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="text-dark fw-bold">{{ $layer->name }}</span>
                                            @if ($layer->wizard_step !== null)
                                                <span class="badge bg-warning text-dark ms-2" style="font-size:0.65em;">
                                                    <i class="mdi mdi-progress-pencil"></i> Tahap {{ $layer->wizard_step }}/4
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
                                                    <span class="badge" style="display:inline-block;background-color:{{ $layer->color }};" >{{ $layer->geometry_type }}</span>
                                                @endif
                                                {{-- @if ($layer->color)
                                                    <span class="color-box"
                                                        style="display:inline-block;background-color:{{ $layer->color }};width:18px;height:18px;border-radius:4px;border:1px solid #dee2e6;box-shadow:0 1px 3px rgba(0,0,0,0.1);"
                                                        title="{{ $layer->color }}"></span>
                                                @endif
                                                @if ($layer->is_marker && $layer->icon)
                                                    <i class="{{ $layer->icon }}"
                                                        style="color:{{ $layer->color ?? '#007bff' }};font-size:1.2em;"
                                                        title="{{ $layer->icon }}"></i>
                                                @endif
                                                @if ($layer->is_marker)
                                                    <span class="badge bg-warning text-dark"><i
                                                            class="mdi mdi-map-marker"></i> Marker</span>
                                                @else
                                                    <span class="badge bg-info text-white"><i class="mdi mdi-layers"></i>
                                                        Layer</span>
                                                @endif --}}
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
                                                <form action="{{ route('spatial-layers.destroy', $layer->id) }}"
                                                    method="POST" style="display:inline-block" data-confirm="delete"
                                                    data-name="{{ $layer->name }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Hapus"><i class="mdi mdi-delete"></i></button>
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

                const category = $('#categoryFilter').val();
                if (category && $(row).data('category-key') !== category) {
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
                        "targets": [-1]
                    },
                    {
                        "className": "text-center",
                        "targets": [0, -1]
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
                    $('#searchTableBtn').on('click', function() {
                        table.search($('#tableSearch').val()).draw();
                    });
                    $('#tableSearch').on('input', function() {
                        table.search($(this).val()).draw();
                    });
                    $('#per_page').on('change', function() {
                        table.page.len(parseInt($(this).val(), 10)).draw();
                    });
                    $('#statusFilter, #opdFilter, #categoryFilter, #layerTypeFilter').on('change', function() {
                        table.draw();
                    });

                    $('#sortBySelect').on('change', function() {
                        const [column, direction] = $(this).val().split('-');
                        table.order([parseInt(column, 10), direction]).draw();
                    });

                    $('#resetFiltersBtn').on('click', function() {
                        $('#statusFilter, #opdFilter, #categoryFilter, #layerTypeFilter').val('');
                        $('#tableSearch').val('');
                        $('#sortBySelect').val('1-asc');
                        table.search('').order([1, 'asc']).draw();
                    });

                    // §5.1 butir 4 — "panel isi katalog": link dari halaman
                    // Kategori (?category=cat:ID atau node:ID) langsung
                    // menerapkan filter ini saat halaman dimuat.
                    const categoryParam = new URLSearchParams(window.location.search).get('category');
                    if (categoryParam) {
                        $('#categoryFilter').val(categoryParam);
                        table.draw();
                    }
                }
            });
        });
    </script>

@endpush
