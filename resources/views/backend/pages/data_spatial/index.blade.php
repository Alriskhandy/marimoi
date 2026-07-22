@extends('backend.partials.main', ['title' => 'Data Peta '])

@section('main')
    <!-- Data Table View -->
    <div id="tableView">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-map-marker-multiple"></i>
                </span>
                Data Spasial
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#!">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Data Spasial Peta Spatial<i
                            class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                @php
                                    // Deteksi type dan sub_type berdasarkan URL dan route
                                    $type = request()->get('type');
                                    $subType = request()->get('sub_type');
                                    $currentRoute = request()->route()->getName();
                                    $currentUrl = request()->url();

                                    // Jika tidak ada parameter, deteksi dari route name atau URL
                                    if (!$type) {
                                        if (
                                            str_contains($currentRoute, 'psn.') ||
                                            str_contains($currentUrl, 'proyek-strategis-nasional')
                                        ) {
                                            $type = 'proyek_strategis';
                                            $subType = 'psn';
                                        } elseif (
                                            str_contains($currentRoute, 'psd.') ||
                                            str_contains($currentUrl, 'proyek-strategis-daerah')
                                        ) {
                                            $type = 'proyek_strategis';
                                            $subType = 'psd';
                                        } elseif (
                                            str_contains($currentRoute, 'pokir') ||
                                            str_contains($currentUrl, 'pokir')
                                        ) {
                                            $type = 'pokir_dprd';
                                            $subType = null;
                                        } elseif (
                                            str_contains($currentRoute, 'usulan') ||
                                            str_contains($currentUrl, 'usulan')
                                        ) {
                                            $type = 'usulan_musrenbang';
                                            $subType = null;
                                        } else {
                                            $type = 'tematik'; // changed from 'lokasi' to 'tematik'
                                            $subType = null;
                                        }
                                    }

                                    // Label tombol dinamis
                                    $typeLabels = [
                                        'tematik' => 'Input Tematik',
                                        'usulan_musrenbang' => 'Input Musrenbang',
                                        'pokir_dprd' => 'Input Pokir DPRD',
                                        'proyek_strategis' => match ($subType) {
                                            'psd' => 'Input PSD',
                                            'psn' => 'Input PSN',
                                            default => 'Input Proyek Strategis',
                                        },
                                    ];

                                    // Title dinamis
                                    $titles = [
                                        'tematik' => 'Peta Tematik',
                                        'usulan_musrenbang' => 'Usulan Musrenbang',
                                        'pokir_dprd' => 'POKIR DPRD',
                                        'proyek_strategis' => match ($subType) {
                                            'psd' => 'Proyek Strategis Daerah',
                                            'psn' => 'Proyek Strategis Nasional',
                                            default => 'Proyek Strategis',
                                        },
                                    ];

                                    $label = $typeLabels[$type] ?? 'Input GIS';
                                    $title = $titles[$type] ?? 'Data Spasial';

                                    // Route dinamis
                                    $createUrl = route(
                                        'data-spatial.create',
                                        array_filter([
                                            'type' => $type,
                                            'sub_type' => $subType,
                                        ]),
                                    );
                                @endphp
                                <h4 class="card-title">Data Spasial {{ $title }} Maluku Utara</h4>
                                <p class="card-description">
                                    Kelola dan pantau data spasial untuk mendukung perencanaan pembangunan daerah
                                </p>
                            </div>
                            <div>
                                <a href="{{ $createUrl }}" class="btn btn-gradient-primary btn-rounded btn-fw me-2">
                                    <i class="mdi mdi-map-marker-plus"></i> {{ $label }}
                                </a>
                            </div>
                        </div>

                        <!-- Search and Filter Controls -->
                        @php
                            $resetQuery = request()->except(['search', 'category_id', 'page']);
                            $resetQuery = array_filter($resetQuery, fn($value) => $value !== null && $value !== '');
                            $resetUrl =
                                request()->url() . (count($resetQuery) ? '?' . http_build_query($resetQuery) : '');
                        @endphp

                        <div class="row mb-4 g-3 align-items-end">
                            <div class="col-12">
                                <form method="GET" action="{{ request()->url() }}"
                                    class="row g-3 align-items-end filter-toolbar">
                                    <!-- Preserve current parameters -->
                                    @foreach (request()->query() as $key => $value)
                                        @if (!in_array($key, ['search', 'category_id', 'per_page', 'page']))
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach

                                    <div class="col-lg-5 col-md-12">
                                        <label for="search" class="form-label fw-semibold mb-1">
                                            <i class="mdi mdi-magnify me-1"></i>Cari Data
                                        </label>
                                        <div class="input-group filter-input-group">
                                            <input type="text" class="form-control filter-control" id="search"
                                                name="search" value="{{ request('search') }}"
                                                placeholder="Cari berdasarkan kode, kategori, atau deskripsi...">
                                            <button class="btn btn-md btn-primary filter-btn" type="submit">
                                                <i class="mdi mdi-magnify"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="category_id" class="form-label fw-semibold mb-1">
                                            <i class="mdi mdi-shape me-1"></i>Filter Kategori
                                        </label>
                                        <select class="form-select filter-control" id="category_id" name="category_id"
                                            onchange="this.form.submit()">
                                            <option value="">-- Semua Kategori --</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}"
                                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->nama }}
                                                </option>
                                                @foreach ($category->children as $child)
                                                    <option value="{{ $child->id }}"
                                                        {{ request('category_id') == $child->id ? 'selected' : '' }}>
                                                        &nbsp;&nbsp;↳ {{ $child->nama }}
                                                    </option>
                                                    @foreach ($child->children as $grandchild)
                                                        <option value="{{ $grandchild->id }}"
                                                            {{ request('category_id') == $grandchild->id ? 'selected' : '' }}>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;↳ {{ $grandchild->nama }}
                                                        </option>
                                                    @endforeach
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-lg-2 col-md-4">
                                        <label for="per_page" class="form-label fw-semibold mb-1">
                                            <i class="mdi mdi-table-row me-1"></i>Tampilkan per halaman
                                        </label>
                                        <select class="form-select filter-control" id="per_page" name="per_page"
                                            onchange="this.form.submit()">
                                            <option value="25" {{ request('per_page', 50) == 25 ? 'selected' : '' }}>25
                                                data</option>
                                            <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50
                                                data</option>
                                            <option value="100" {{ request('per_page', 50) == 100 ? 'selected' : '' }}>
                                                100 data</option>
                                            <option value="200" {{ request('per_page', 50) == 200 ? 'selected' : '' }}>
                                                200 data</option>
                                            <option value="500" {{ request('per_page', 50) == 500 ? 'selected' : '' }}>
                                                500 data</option>
                                        </select>
                                    </div>

                                    <div class="col-lg-1 col-md-2">
                                        <a href="{{ $resetUrl }}"
                                            class="btn btn-outline-secondary btn-sm filter-reset-btn w-100 @if (!request('search') && !request('category_id')) disabled opacity-50 @endif"
                                            @if (!request('search') && !request('category_id')) tabindex="-1" aria-disabled="true" @endif>
                                            Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Search Results Info -->
                        @if (request('search'))
                            <div class="alert alert-info mb-3">
                                <i class="mdi mdi-information me-2"></i>
                                Menampilkan hasil pencarian untuk: <strong>"{{ request('search') }}"</strong>
                                @if ($data->total() > 0)
                                    - Ditemukan {{ $data->total() }} data
                                @else
                                    - Tidak ada data yang ditemukan
                                @endif
                            </div>
                        @endif

                        <!-- Bulk Actions Bar -->
                        <div id="bulkActionsBar" class="alert alert-info d-none mb-3" role="alert">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="mdi mdi-checkbox-multiple-marked me-2"></i>
                                    <span id="selectedCount">0</span> item dipilih
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="bulkUpdateCategory()">
                                        <i class="mdi mdi-shape-outline me-1"></i>
                                        Ubah Kategori/Layer
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                                        <i class="mdi mdi-trash-can-outline me-1"></i>
                                        Hapus Terpilih
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick="clearSelection()">
                                        <i class="mdi mdi-close me-1"></i>
                                        Batal
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="dataSpasialTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        @if ($data->count() > 0)
                                            <th style="width: 40px;">
                                                <div class="checkbox-wrapper">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                    <label class="form-check-label" for="selectAll">
                                                        <span class="visually-hidden">Select All</span>
                                                    </label>
                                                </div>
                                            </th>
                                        @endif
                                        <th>No</th>
                                        <th>KODE</th>
                                        <th>Kategori/Layer</th>
                                        <th>Nama/Deskripsi</th>
                                        @php
                                            $hasTahun = $data->some(fn($item) => !empty($item->tahun));
                                        @endphp
                                        @if ($hasTahun)
                                            <th>Tahun</th>
                                        @endif
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data as $index => $item)
                                        <tr>
                                            <td>
                                                <div class="checkbox-wrapper">
                                                    <input class="form-check-input row-checkbox" type="checkbox"
                                                        value="{{ $item->id }}" id="check-{{ $item->id }}">
                                                    <label class="form-check-label" for="check-{{ $item->id }}">
                                                        <span class="visually-hidden">Select row</span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>{{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}</td>
                                            <td>{{ $item->uuid }}</td>
                                            <td>
                                                <span class="badge bg-gradient-info text-white">
                                                    {{ $item->kategori->nama ?? 'Tidak ada kategori' }}
                                                </span>
                                            </td>
                                            <td style="word-wrap: break-word; white-space: normal; max-width: 300px;">
                                                <div>
                                                    <strong>{{ $item->title ?? ($item->deskripsi ?? 'Tanpa Nama') }}</strong>
                                                </div>
                                            </td>
                                            @if ($hasTahun)
                                                <td class="text-center">
                                                    {{ $item->tahun ?? '-' }}
                                                </td>
                                            @endif
                                            <td class="text-center">
                                                {{ $item->created_at ? $item->created_at->format('d M Y') : date('d M Y') }}
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="{{ route('data-spatial.edit', $item->uuid) }}"
                                                        class="btn btn-sm btn-outline-warning" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>

                                                    <button type="button" class="btn btn-sm btn-outline-info"
                                                        onclick="showDetails('{{ $item->uuid }}')" title="Detail">
                                                        <i class="mdi mdi-eye"></i>
                                                    </button>

                                                    <form action="{{ route('data-spatial.destroy', $item->uuid) }}"
                                                        method="POST" style="display:inline-block;"
                                                        data-confirm="delete">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $hasTahun ? '8' : '7' }}" class="text-center py-4">
                                                <i class="mdi mdi-database-remove mdi-48px text-muted"></i>
                                                <br>
                                                <h5 class="text-muted mt-2">
                                                    @if (request('search'))
                                                        Tidak ada data yang cocok dengan pencarian
                                                    @else
                                                        Belum ada data spasial
                                                    @endif
                                                </h5>
                                                <p class="text-muted">
                                                    @if (request('search'))
                                                        Coba gunakan kata kunci yang berbeda atau
                                                        <a
                                                            href="{{ request()->url() }}?{{ http_build_query(array_filter(request()->query(), fn($k) => !in_array($k, ['search', 'page']), ARRAY_FILTER_USE_KEY)) }}">hapus
                                                            filter pencarian</a>
                                                    @else
                                                        Klik tombol "{{ $label }}" untuk menambah data baru
                                                    @endif
                                                </p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination and Info -->
                        @if ($data->hasPages() || $data->total() > 0)
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="showing-info">
                                    <p class="text-muted mb-0">
                                        @if ($data->total() > 0)
                                            Menampilkan {{ $data->firstItem() }} sampai {{ $data->lastItem() }}
                                            dari {{ $data->total() }} total data
                                            @if (request('search'))
                                                (hasil pencarian)
                                            @endif
                                        @else
                                            Tidak ada data untuk ditampilkan
                                        @endif
                                    </p>
                                </div>

                                @if ($data->hasPages())
                                    <div class="pagination-wrapper">
                                        {{ $data->appends(request()->query())->links() }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-primary text-white rounded-top-3 py-2">
                    <h6 class="modal-title fw-semibold" id="detailModalLabel">
                        <i class="fa fa-map-marker" style="margin-right: 6px;"></i>Detail Data Spasial
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" id="detailModalBody">
                    <div class="d-flex justify-content-center align-items-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-3 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times" style="margin-right: 5px;"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkDeleteModalLabel">
                        <i class="mdi mdi-alert-circle me-2"></i>Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="mdi mdi-trash-can-outline text-danger" style="font-size: 4rem;"></i>
                        <h5 class="mt-3">Apakah Anda yakin?</h5>
                        <p class="text-muted">
                            Anda akan menghapus <strong id="deleteCount">0</strong> data spasial yang dipilih.
                            <br>Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmBulkDelete">
                        <i class="mdi mdi-trash-can-outline me-1"></i>Hapus Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Form (Hidden) -->
    <form id="bulkDeleteForm" method="POST" action="{{ route('data-spatial.destroy', 'bulk-destroy') }}"
        style="display: none;">
        @csrf
        @method('DELETE')
        <div id="bulkDeleteIds"></div>
        <!-- Debug input -->
        <input type="hidden" name="debug_source" value="bulk_delete_form">
        <input type="hidden" name="current_url" value="{{ request()->fullUrl() }}">
    </form>

    <!-- Bulk Update Category Modal -->
    <div class="modal fade" id="bulkCategoryModal" tabindex="-1" aria-labelledby="bulkCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="bulkCategoryModalLabel">
                        <i class="mdi mdi-shape-outline me-2"></i>Ubah Kategori/Layer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Mengubah kategori/layer untuk <strong id="bulkCategoryCount">0</strong> data yang dipilih.
                    </p>
                    <label for="bulkCategorySelect" class="form-label fw-semibold">Kategori/Layer Baru</label>
                    <select class="form-select" id="bulkCategorySelect">
                        <option value="">-- Pilih Kategori/Layer --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->nama }}</option>
                            @foreach ($category->children as $child)
                                <option value="{{ $child->id }}">&nbsp;&nbsp;↳ {{ $child->nama }}</option>
                                @foreach ($child->children as $grandchild)
                                    <option value="{{ $grandchild->id }}">&nbsp;&nbsp;&nbsp;&nbsp;↳
                                        {{ $grandchild->nama }}</option>
                                @endforeach
                            @endforeach
                        @endforeach
                    </select>
                    <div id="bulkCategoryError" class="text-danger small mt-2 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmBulkCategory">
                        <i class="mdi mdi-check me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Update Category Form (Hidden) -->
    <form id="bulkCategoryForm" method="POST" action="{{ route('data-spatial.bulk-update-category') }}"
        style="display: none;">
        @csrf
        <div id="bulkCategoryIds"></div>
        <input type="hidden" name="kategori_id" id="bulkCategoryKategoriId">
    </form>
@endsection

@push('styles')
    <style>
        /* Search and filter styling */
        .form-label {
            color: #495057;
            font-size: 0.875rem;
        }

        .input-group .btn {
            border-left: none;
        }

        .input-group .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            border-color: #80bdff;
        }

        .input-group .form-control:focus+.btn {
            border-color: #80bdff;
        }

        /* Existing styles... */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            padding: 8px;
        }

        .form-check-input {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #6c757d;
            border-radius: 4px;
            background-color: #fff;
            cursor: pointer;
            position: relative;
            margin: 0 !important;
            padding: 0 !important;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .form-check-input:hover {
            border-color: #007bff;
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.25);
            transform: translateY(-1px);
        }

        .form-check-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.4);
        }

        .form-check-input:checked::before {
            content: "✓";
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            line-height: 1;
        }

        .form-check-input:indeterminate {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .form-check-input:indeterminate::before {
            content: "─";
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            line-height: 1;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            color: #495057;
            padding: 12px 8px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table td {
            vertical-align: middle;
            padding: 12px 8px;
            border-bottom: 1px solid #eef2f7;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8, #20c997);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        #bulkActionsBar {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border: 1px solid #2196f3;
            border-radius: 8px;
            color: #1976d2;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-content {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
        }

        @media (max-width: 768px) {
            .checkbox-wrapper {
                padding: 4px;
            }

            .form-check-input {
                width: 18px;
                height: 18px;
            }

            .btn-sm {
                padding: 4px 8px;
                font-size: 0.8rem;
            }

            .table th,
            .table td {
                padding: 8px 4px;
                font-size: 0.85rem;
            }
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

        .filter-toolbar {
            margin-bottom: 0;
        }

        .filter-control,
        .filter-btn,
        .filter-reset-btn {
            min-height: 44px;
        }

        .filter-input-group .form-control {
            border-right: 0;
        }

        .filter-input-group .btn {
            border-left: 0;
        }

        .filter-reset-btn {
            white-space: nowrap;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .btn:focus,
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize checkbox events directly
            initializeCheckboxEvents();

            // Add tooltips to action buttons
            $('[title]').tooltip();
        });

        function initializeCheckboxEvents() {
            // Select All functionality  
            $('#selectAll').on('change', function() {
                const isChecked = this.checked;
                const checkboxes = $('.row-checkbox');

                checkboxes.each(function() {
                    this.checked = isChecked;
                    const value = this.value;

                    if (isChecked && !selectedItems.includes(value)) {
                        selectedItems.push(value);
                    } else if (!isChecked) {
                        selectedItems = selectedItems.filter(id => id !== value);
                    }
                });

                updateBulkActionsBar();
            });

            // Individual checkbox functionality
            $('.row-checkbox').on('change', function() {
                const value = this.value;

                if (this.checked) {
                    if (!selectedItems.includes(value)) {
                        selectedItems.push(value);
                    }
                } else {
                    selectedItems = selectedItems.filter(id => id !== value);
                }

                updateBulkActionsBar();
                updateSelectAllState();
            });
        }

        function updateSelectAllState() {
            const checkboxes = $('.row-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            let checkedCount = 0;

            checkboxes.each(function() {
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

        // Bulk actions functionality
        let selectedItems = [];

        // Update bulk actions bar visibility and count
        function updateBulkActionsBar() {
            const bulkActionsBar = document.getElementById('bulkActionsBar');
            const selectedCount = document.getElementById('selectedCount');

            if (selectedItems.length > 0) {
                bulkActionsBar.classList.remove('d-none');
                selectedCount.textContent = selectedItems.length;
            } else {
                bulkActionsBar.classList.add('d-none');
            }
        }

        // Clear selection
        function clearSelection() {
            selectedItems = [];
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = false;
                $(cb).closest('tr').removeClass('table-active');
            });
            document.getElementById('selectAll').checked = false;
            document.getElementById('selectAll').indeterminate = false;
            updateBulkActionsBar();
        }

        // Bulk delete functionality
        function bulkDelete() {
            if (selectedItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak ada data terpilih',
                    text: 'Silakan pilih data yang akan dihapus terlebih dahulu',
                    confirmButtonText: 'OK'
                });
                return;
            }

            document.getElementById('deleteCount').textContent = selectedItems.length;
            const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
            modal.show();
        }

        // Confirm bulk delete
        document.getElementById('confirmBulkDelete').addEventListener('click', function() {
            if (selectedItems.length === 0) {
                alert('Tidak ada data yang dipilih untuk dihapus');
                return;
            }

            // Show loading state
            this.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>Menghapus...';
            this.disabled = true;

            // Create hidden inputs for selected IDs
            const bulkDeleteIds = document.getElementById('bulkDeleteIds');
            bulkDeleteIds.innerHTML = '';

            selectedItems.forEach((id, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkDeleteIds.appendChild(input);
            });

            // Submit the form
            document.getElementById('bulkDeleteForm').submit();
        });

        // Bulk update category/layer functionality
        function bulkUpdateCategory() {
            if (selectedItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak ada data terpilih',
                    text: 'Silakan pilih data yang akan diubah kategori/layernya terlebih dahulu',
                    confirmButtonText: 'OK'
                });
                return;
            }

            document.getElementById('bulkCategoryCount').textContent = selectedItems.length;
            document.getElementById('bulkCategorySelect').value = '';
            const errorBox = document.getElementById('bulkCategoryError');
            errorBox.classList.add('d-none');
            errorBox.textContent = '';

            const modal = new bootstrap.Modal(document.getElementById('bulkCategoryModal'));
            modal.show();
        }

        // Confirm bulk category update
        document.getElementById('confirmBulkCategory').addEventListener('click', function() {
            const select = document.getElementById('bulkCategorySelect');
            const errorBox = document.getElementById('bulkCategoryError');

            if (selectedItems.length === 0) {
                errorBox.textContent = 'Tidak ada data yang dipilih untuk diubah.';
                errorBox.classList.remove('d-none');
                return;
            }

            if (!select.value) {
                errorBox.textContent = 'Silakan pilih kategori/layer tujuan.';
                errorBox.classList.remove('d-none');
                return;
            }

            errorBox.classList.add('d-none');

            // Show loading state
            this.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>Menyimpan...';
            this.disabled = true;

            // Create hidden inputs for selected IDs
            const bulkCategoryIds = document.getElementById('bulkCategoryIds');
            bulkCategoryIds.innerHTML = '';

            selectedItems.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkCategoryIds.appendChild(input);
            });

            document.getElementById('bulkCategoryKategoriId').value = select.value;

            // Submit the form
            document.getElementById('bulkCategoryForm').submit();
        });

        // Show details function with improved UX
        function showDetails(id) {
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            const modalBody = document.getElementById('detailModalBody');

            // Show loading with better animation
            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h6 class="text-muted">Memuat detail data...</h6>
                    <p class="text-muted small">Mohon tunggu sebentar</p>
                </div>
            `;

            modal.show();

            // Fetch data details with improved error handling
            fetch(`/dashboard/data-spatial/${id}/details`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        modalBody.innerHTML = `
                            <div class="container-fluid px-0">
                                <div class="card border-0 shadow-sm rounded-4 bg-light">
                                    <div class="card-header bg-primary text-white rounded-top-4 py-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="mdi mdi-information-outline me-2"></i>Informasi Dasar
                                        </h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Data Type:</small>
                                                <div class="fw-medium">${data.data.data_type || '-'}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Sub Type:</small>
                                                <div class="fw-medium">${data.data.sub_type || '-'}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Tahun:</small>
                                                <div class="fw-medium">${data.data.tahun || '-'}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Kategori:</small>
                                                <div class="fw-medium">${data.data.kategori?.nama || '-'}</div>
                                            </div>
                                            <div class="col-12">
                                                <small class="text-muted fw-semibold">Deskripsi:</small>
                                                <div class="fw-medium">${data.data.deskripsi || '-'}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card border-0 shadow-sm rounded-4 bg-light mt-3">
                                    <div class="card-header bg-info text-white rounded-top-4 py-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="mdi mdi-database me-2"></i>Atribut DBF
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div style="max-height: 300px; overflow-y: auto;">
                                            ${data.data.dbf_attributes ? 
                                                '<div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead class="table-dark sticky-top"><tr><th class="fw-semibold">Atribut</th><th class="fw-semibold">Nilai</th></tr></thead><tbody>'+
                                                Object.entries(data.data.dbf_attributes).map(([key,value])=>`<tr><td class="fw-medium text-primary">${key}</td><td>${value || '-'}</td></tr>`).join('')+
                                                '</tbody></table></div>' : 
                                                '<div class="text-center py-4"><i class="mdi mdi-database-remove text-muted" style="font-size: 3rem;"></i><p class="text-muted mb-0 mt-2">Tidak ada atribut DBF tersedia</p></div>'
                                            }
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div class="text-center py-5">
                            <i class="mdi mdi-alert-circle text-danger" style="font-size: 4rem;"></i>
                            <h5 class="text-danger mt-3">Terjadi Kesalahan</h5>
                            <p class="text-muted">
                                ${error.message || 'Gagal memuat detail data. Silakan coba lagi.'}
                            </p>
                            <button class="btn btn-outline-primary btn-sm" onclick="showDetails('${id}')">
                                <i class="mdi mdi-refresh me-1"></i>Coba Lagi
                            </button>
                        </div>
                    `;
                });
        }

        // Enhanced row selection visual feedback
        $(document).on('change', '.row-checkbox', function() {
            const row = $(this).closest('tr');
            if (this.checked) {
                row.addClass('table-active');
            } else {
                row.removeClass('table-active');
            }
        });

        // Add keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + A to select all visible rows
            if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                e.preventDefault();
                const selectAllCheckbox = document.getElementById('selectAll');
                if (selectAllCheckbox && !selectAllCheckbox.checked) {
                    selectAllCheckbox.click();
                }
            }

            // Delete key to bulk delete selected items
            if (e.key === 'Delete' && selectedItems.length > 0) {
                e.preventDefault();
                bulkDelete();
            }

            // Escape key to clear selection
            if (e.key === 'Escape' && selectedItems.length > 0) {
                e.preventDefault();
                clearSelection();
            }
        });

        // Enhanced form submission with confirmation
        $('form[data-confirm="delete"]').on('submit', function(e) {
            e.preventDefault();
            const form = this;

            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus data ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@endpush
