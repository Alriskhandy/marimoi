@extends('backend.partials.main', ['title' => 'Jenis Peta'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-map-marker-radius"></i>
            </span>
            Jenis Peta
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Jenis Peta</li>
            </ul>
        </nav>
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

    <!-- Statistics Cards -->
    @if ($mapTypes->count() > 0)
        <div class="row g-3 stats-row-compact">
            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Total Jenis</p>
                                <h3 class="stat-value">{{ $mapTypes->count() }}</h3>
                            </div>
                            <i class="mdi mdi-map-marker-radius stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Aktif</p>
                                <h3 class="stat-value">{{ $mapTypes->where('is_active', true)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-check-circle stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-secondary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Nonaktif</p>
                                <h3 class="stat-value">{{ $mapTypes->where('is_active', false)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-close-circle stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Total Atribut Tambahan</p>
                                <h3 class="stat-value">{{ $mapTypes->sum('atribut_tambahan_count') }}</h3>
                            </div>
                            <i class="mdi mdi-shape-plus stat-icon"></i>
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
                        <div>
                            <h4 class="card-title mb-1">Daftar Jenis Peta</h4>
                        </div>
                        <a href="{{ route('map-types.create') }}" class="btn btn-gradient-primary text-nowrap">
                            <i class="mdi mdi-plus"></i> Tambah Jenis Peta
                        </a>
                    </div>

                    <div class="row mb-4 g-3 align-items-end">
                        <div class="col-12">
                            <div class="row g-3 align-items-end filter-toolbar">
                                <div class="col-lg-9 col-md-8">
                                    <label for="tableSearch" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-magnify me-1"></i>Cari Jenis Peta
                                    </label>
                                    <div class="input-group filter-input-group">
                                        <input type="text" class="form-control filter-control" id="tableSearch"
                                            placeholder="Ketik nama atau slug...">
                                        <button class="btn btn-md btn-primary filter-btn" type="button" id="searchTableBtn">
                                            <i class="mdi mdi-magnify"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-4">
                                    <label for="per_page" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-table-row me-1"></i>Tampilkan per halaman
                                    </label>
                                    <select class="form-select filter-control" id="per_page">
                                        <option value="10">10 data</option>
                                        <option value="25" selected>25 data</option>
                                        <option value="50">50 data</option>
                                        <option value="100">100 data</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="mapTypesTable" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Urutan</th>
                                    <th>Jenis Peta</th>
                                    <th class="text-center" title="Atribut baku (Pagu, Realisasi Anggaran, dst.) yang wajib diisi user saat menambah Layer dengan Jenis ini">Atribut Utama</th>
                                    <th class="text-center" title="Atribut tambahan khusus yang didefinisikan untuk Jenis ini">Atribut Tambahan</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mapTypes as $mapType)
                                    <tr>
                                        <td class="text-center">{{ $mapType->urutan }}</td>
                                        <td>
                                            <span class="text-dark fw-bold">{{ $mapType->nama }}</span>
                                            <br>
                                            <code>{{ $mapType->slug }}</code>
                                        </td>
                                        <td class="text-center">
                                            @if ($mapType->atribut_utama_count > 0)
                                                <span class="badge bg-info text-white">{{ $mapType->atribut_utama_count }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($mapType->atribut_tambahan_count > 0)
                                                <span class="badge bg-warning text-dark">{{ $mapType->atribut_tambahan_count }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $mapType->is_active ? 'bg-success' : 'bg-secondary' }} text-white">
                                                {{ $mapType->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="{{ route('map-types.edit', $mapType) }}" class="btn btn-sm btn-outline-warning" title="Kelola">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <form action="{{ route('map-types.destroy', $mapType) }}" method="POST" style="display:inline-block;"
                                                    data-confirm="delete" data-name="{{ $mapType->nama }}">
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
                                        <td colspan="7" class="text-center py-4">
                                            <i class="mdi mdi-map-marker-radius mdi-48px text-muted"></i>
                                            <h5 class="text-muted mt-2">Belum ada Jenis Peta</h5>
                                            <p class="text-muted">Klik tombol "Tambah Jenis Peta" untuk memulai</p>
                                            <a href="{{ route('map-types.create') }}" class="btn btn-primary">
                                                <i class="mdi mdi-plus"></i> Tambah Jenis Peta Pertama
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
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
        $(document).ready(function () {
            const table = $('#mapTypesTable').DataTable({
                "processing": true,
                "pageLength": 25,
                "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
                "ordering": false,
                "columnDefs": [
                    { "searchable": false, "targets": [-1] },
                    { "className": "text-center", "targets": [0, -1] },
                ],
                "language": {
                    "processing": "<div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div>",
                    "lengthMenu": "Tampilkan _MENU_ jenis peta per halaman",
                    "zeroRecords": "Jenis peta tidak ditemukan",
                    "emptyTable": "Tidak ada Jenis Peta tersedia",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ Jenis Peta",
                    "infoEmpty": "Menampilkan 0 sampai 0 dari 0 Jenis Peta",
                    "infoFiltered": "(difilter dari _MAX_ total Jenis Peta)",
                    "search": "Cari Jenis Peta:",
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
                }
            });
        });
    </script>
@endpush
