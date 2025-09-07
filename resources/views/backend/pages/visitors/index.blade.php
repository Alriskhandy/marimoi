{{-- resources/views/backend/pages/visitors/index.blade.php --}}

@extends('backend.partials.main', ['title' => 'Analytics Pengunjung'])

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-info text-white me-2">
                <i class="mdi mdi-chart-line"></i>
            </span> Analytics Pengunjung
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Visitors
                </li>
            </ul>
        </nav>
    </div>

    <!-- Period Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="d-flex gap-2 mb-2 mb-md-0">
                            <a href="{{ route('visitors.index', ['period' => 'today']) }}" 
                               class="btn {{ $period === 'today' ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                                Hari Ini
                            </a>
                            <a href="{{ route('visitors.index', ['period' => 'week']) }}" 
                               class="btn {{ $period === 'week' ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                                Minggu Ini
                            </a>
                            <a href="{{ route('visitors.index', ['period' => 'month']) }}" 
                               class="btn {{ $period === 'month' ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                                Bulan Ini
                            </a>
                            <a href="{{ route('visitors.index', ['period' => 'year']) }}" 
                               class="btn {{ $period === 'year' ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                                Tahun Ini
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="mdi mdi-file-excel"></i> Export
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkDelete()">
                                <i class="mdi mdi-delete"></i> Hapus Data Lama
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
            <div class="card bg-gradient-primary card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute" alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Total Kunjungan
                        <i class="mdi mdi-eye mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-4">{{ number_format($stats['total_visits']) }}</h2>
                    <h6 class="card-text">Page views</h6>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
            <div class="card bg-gradient-success card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute" alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Pengunjung Unik
                        <i class="mdi mdi-account-multiple mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-4">{{ number_format($stats['unique_visitors']) }}</h2>
                    <h6 class="card-text">Unique IPs</h6>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
            <div class="card bg-gradient-warning card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute" alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Bounce Rate
                        <i class="mdi mdi-bounce-left mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-4">{{ $stats['bounce_rate'] }}%</h2>
                    <h6 class="card-text">Single page visits</h6>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
            <div class="card bg-gradient-info card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute" alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Rata-rata
                        <i class="mdi mdi-chart-bar mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-4">{{ number_format($stats['total_visits'] / max($stats['unique_visitors'], 1), 1) }}</h2>
                    <h6 class="card-text">Pages per visitor</h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-chart-line me-2"></i>Tren Kunjungan
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="visitsChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-web me-2"></i>Browser
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="browserChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Widgets -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-file-document-outline me-2"></i>Halaman Populer
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Halaman</th>
                                    <th class="text-end">Kunjungan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($analytics['top_pages'] as $page)
                                    <tr>
                                        <td>
                                            <a href="{{ $page->page_visited }}" target="_blank" class="text-decoration-none">
                                                {{ Str::limit($page->page_visited, 40) }}
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-primary">{{ number_format($page->visits) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-earth me-2"></i>Negara Teratas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Negara</th>
                                    <th class="text-end">Kunjungan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($analytics['top_countries'] as $country)
                                    <tr>
                                        <td>
                                            <i class="flag-icon flag-icon-{{ strtolower($country->country) }} me-2"></i>
                                            {{ $country->country }}
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-success">{{ number_format($country->visits) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">
                            <i class="mdi mdi-account-group"></i>
                            Daftar Pengunjung
                        </h4>
                    </div>

                    <!-- Search and Filter Box -->
                    <div class="row mb-3">
                        <div class="col-lg-4 col-md-6 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                <input type="text" class="form-control" id="searchInput" 
                                       placeholder="Cari IP, negara, kota..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <select class="form-control" id="countryFilter">
                                <option value="">Semua Negara</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country }}" {{ request('country') === $country ? 'selected' : '' }}>
                                        {{ $country }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <select class="form-control" id="cityFilter">
                                <option value="">Semua Kota</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>
                                        {{ $city }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-2">
                            <button type="button" class="btn btn-warning w-100" onclick="resetFilters()">
                                <i class="mdi mdi-refresh"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Bulk Actions Bar -->
                    <div id="bulkActionsBar" class="alert alert-info d-none mb-3" role="alert">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="mdi mdi-checkbox-multiple-marked me-2"></i>
                                <span id="selectedCount">0</span> data dipilih
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDeleteSelected()">
                                    <i class="mdi mdi-trash-can-outline me-1"></i>
                                    Hapus Terpilih
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                                    <i class="mdi mdi-close me-1"></i>
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <!-- DataTable -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="visitorsTable" style="width:100%">
                            <thead>
                                <tr>
                                    @if($visitors->isNotEmpty())
                                        <th style="width: 40px;">
                                            <div class="checkbox-wrapper">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                                <label class="form-check-label" for="selectAll">
                                                    <span class="visually-hidden">Select All</span>
                                                </label>
                                            </div>
                                        </th>
                                    @endif
                                    <th class="text-center" style="width: 50px;">No</th>
                                    <th>IP Address</th>
                                    <th>Lokasi</th>
                                    <th>Halaman</th>
                                    <th>Browser</th>
                                    <th>Device</th>
                                    <th class="text-center">Waktu Kunjungan</th>
                                    <th class="text-center" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visitors as $index => $visitor)
                                    <tr data-id="{{ $visitor->id }}">
                                        @if($visitors->isNotEmpty())
                                            <td>
                                                <div class="checkbox-wrapper">
                                                    <input class="form-check-input row-checkbox" type="checkbox"
                                                        value="{{ $visitor->id }}" id="check-{{ $visitor->id }}">
                                                    <label class="form-check-label" for="check-{{ $visitor->id }}">
                                                        <span class="visually-hidden">Select row</span>
                                                    </label>
                                                </div>
                                            </td>
                                        @endif
                                        <td class="text-center">{{ $visitors->firstItem() + $index }}</td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong class="text-primary font-monospace">{{ $visitor->ip }}</strong>
                                                <small class="text-muted">{{ $visitor->operating_system }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                @if($visitor->country)
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="flag-icon flag-icon-{{ strtolower($visitor->country) }} me-2"></i>
                                                        <strong>{{ $visitor->country }}</strong>
                                                    </div>
                                                @endif
                                                @if($visitor->city)
                                                    <small class="text-muted">
                                                        <i class="mdi mdi-map-marker me-1"></i>{{ $visitor->city }}
                                                    </small>
                                                @endif
                                                @if($visitor->latitude && $visitor->longitude)
                                                    <small class="text-muted font-monospace">
                                                        {{ number_format($visitor->latitude, 4) }}, {{ number_format($visitor->longitude, 4) }}
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <a href="{{ $visitor->page_visited }}" target="_blank" 
                                                   class="text-decoration-none" title="{{ $visitor->page_visited }}">
                                                    {{ Str::limit($visitor->page_visited, 30) }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @php
                                                    $browserIcons = [
                                                        'Chrome' => 'mdi-google-chrome',
                                                        'Firefox' => 'mdi-firefox',
                                                        'Safari' => 'mdi-apple-safari',
                                                        'Edge' => 'mdi-microsoft-edge',
                                                        'Opera' => 'mdi-opera',
                                                        'Internet Explorer' => 'mdi-internet-explorer'
                                                    ];
                                                    $icon = $browserIcons[$visitor->browser] ?? 'mdi-web';
                                                @endphp
                                                <i class="mdi {{ $icon }} me-2 text-primary"></i>
                                                <span>{{ $visitor->browser }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @php
                                                    $deviceIcons = [
                                                        'Mobile' => 'mdi-cellphone',
                                                        'Tablet' => 'mdi-tablet',
                                                        'Desktop' => 'mdi-monitor'
                                                    ];
                                                    $deviceIcon = $deviceIcons[$visitor->device_type] ?? 'mdi-help';
                                                    $deviceColors = [
                                                        'Mobile' => 'text-success',
                                                        'Tablet' => 'text-warning',
                                                        'Desktop' => 'text-info'
                                                    ];
                                                    $deviceColor = $deviceColors[$visitor->device_type] ?? 'text-muted';
                                                @endphp
                                                <i class="mdi {{ $deviceIcon }} me-2 {{ $deviceColor }}"></i>
                                                <span>{{ $visitor->device_type }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column">
                                                <small>{{ $visitor->created_at->format('d/m/Y') }}</small>
                                                <small class="text-muted">{{ $visitor->created_at->format('H:i:s') }}</small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show"
                                                    data-id="{{ $visitor->id }}" title="Lihat Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                @if($visitor->latitude && $visitor->longitude)
                                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $visitor->latitude }},{{ $visitor->longitude }}" 
                                                       target="_blank" class="btn btn-sm btn-outline-success" title="Lihat di Maps">
                                                        <i class="mdi mdi-map-marker"></i>
                                                    </a>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                                    data-id="{{ $visitor->id }}"
                                                    onclick="deleteVisitor({{ $visitor->id }})" title="Hapus">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-data-row">
                                        <td colspan="9" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-account-off mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Tidak ada data pengunjung</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($visitors->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                            <div class="mb-2 mb-md-0">
                                <span class="text-muted">
                                    Menampilkan {{ $visitors->firstItem() ?? 0 }} - {{ $visitors->lastItem() ?? 0 }}
                                    dari {{ $visitors->total() }} data
                                </span>
                            </div>
                            <nav>
                                {{ $visitors->appends(request()->query())->links('pagination::bootstrap-4', ['class' => 'pagination-sm']) }}
                            </nav>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Show Modal -->
    <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-gradient-info text-white">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="mdi mdi-eye me-2"></i>
                        Detail Pengunjung
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Loading State -->
                <div id="modalLoadingState" class="modal-body text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-3">
                        <h5 class="text-muted">Memuat detail pengunjung...</h5>
                        <p class="text-muted small">Mohon tunggu sebentar</p>
                    </div>
                </div>

                <!-- Content State -->
                <div id="modalContentState" class="modal-body" style="display: none;">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Informasi Kunjungan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>IP Address:</strong>
                                            <p id="show_ip" class="text-primary font-monospace"></p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Halaman Dikunjungi:</strong>
                                            <p id="show_page" class="text-muted"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Negara:</strong>
                                            <p id="show_country" class="text-muted"></p>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Kota:</strong>
                                            <p id="show_city" class="text-muted"></p>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Browser:</strong>
                                            <p id="show_browser" class="text-muted"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Device Type:</strong>
                                            <p id="show_device" class="text-muted"></p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Operating System:</strong>
                                            <p id="show_os" class="text-muted"></p>
                                        </div>
                                    </div>

                                    <div class="row mt-3" id="show_koordinat_row" style="display: none;">
                                        <div class="col-12">
                                            <div class="border-top pt-3">
                                                <h6 class="text-success"><i class="mdi mdi-map-marker me-2"></i>Lokasi</h6>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <a id="openMapBtn" class="btn btn-success btn-sm" target="_blank">
                                                            <i class="mdi mdi-map-marker"></i> Lihat di Google Maps
                                                        </a>
                                                        <button type="button" class="btn btn-outline-info btn-sm ms-2" id="copyCoordinates">
                                                            <i class="mdi mdi-content-copy"></i> Copy Koordinat
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="border-top pt-3">
                                                <h6 class="text-info"><i class="mdi mdi-information me-2"></i>User Agent</h6>
                                                <div id="show_user_agent" class="text-muted p-3 bg-light rounded font-monospace small" style="word-break: break-all;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Session Data</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Waktu Kunjungan:</strong>
                                        <p id="show_created_at" class="text-muted"></p>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Total Kunjungan IP Ini:</strong>
                                        <p id="show_total_visits" class="text-muted"></p>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Kunjungan Pertama:</strong>
                                        <p id="show_first_visit" class="text-muted"></p>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Kunjungan Terakhir:</strong>
                                        <p id="show_last_visit" class="text-muted"></p>
                                    </div>

                                    <div id="show_related_visits" style="display: none;">
                                        <div class="border-top pt-3">
                                            <strong class="text-primary">Kunjungan Terkait:</strong>
                                            <div id="related_visits_list" class="mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" id="modalFooter" style="display: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-gradient-success text-white">
                    <h5 class="modal-title" id="exportModalLabel">
                        <i class="mdi mdi-file-excel me-2"></i>
                        Export Data Pengunjung
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="exportForm" action="{{ route('visitors.export') }}" method="GET">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="export_period" class="form-label">Periode</label>
                            <select class="form-control" id="export_period" name="period">
                                <option value="today">Hari Ini</option>
                                <option value="week">Minggu Ini</option>
                                <option value="month">Bulan Ini</option>
                                <option value="year">Tahun Ini</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="export_format" class="form-label">Format</label>
                            <select class="form-control" id="export_format" name="format">
                                <option value="xlsx">Excel (.xlsx)</option>
                                <option value="csv">CSV (.csv)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-gradient-success">
                            <i class="mdi mdi-download"></i> Download
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
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
                        <p class="text-muted" id="deleteMessage">
                            Tindakan ini tidak dapat dibatalkan.
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

    <!-- Hidden Form for Bulk Operations -->
    <form id="bulkDeleteForm" method="POST" action="{{ route('visitors.bulk-destroy') }}" style="display: none;">
        @csrf
        @method('DELETE')
        <div id="bulkDeleteIds"></div>
    </form>
@endsection
// Add this to the @section('scripts') section of your visitor index view

@push('styles')
<style>
/* Checkbox styling */
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

/* Table styling */
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

/* Statistics cards hover effect */
.card.card-img-holder:hover {
    transform: translateY(-2px);
    transition: transform 0.3s ease;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.card-img-absolute {
    position: absolute;
    top: 0;
    right: 0;
    opacity: 0.1;
}

/* Flag icons */
.flag-icon {
    width: 20px;
    height: 15px;
    border-radius: 2px;
}

/* Modal improvements */
.modal-content {
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

/* Bulk Actions Bar */
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

/* Responsive improvements */
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

/* Chart containers */
#visitsChart, #browserChart {
    max-height: 400px;
}
</style>
@endpush

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize charts
    initializeCharts();
    
    // Initialize DataTable functionality
    initializeDataTable();
    
    // Initialize bulk selection
    initializeBulkSelection();
    
    // Initialize filters
    initializeFilters();
    
    // Initialize modals
    initializeModals();

    // Initialize charts
    function initializeCharts() {
        // Visits Chart
        const visitsCtx = document.getElementById('visitsChart');
        if (visitsCtx) {
            const dailyVisits = @json($analytics['daily_visits']);
            
            new Chart(visitsCtx, {
                type: 'line',
                data: {
                    labels: dailyVisits.map(item => new Date(item.date).toLocaleDateString('id-ID')),
                    datasets: [{
                        label: 'Total Kunjungan',
                        data: dailyVisits.map(item => item.visits),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1,
                        fill: true
                    }, {
                        label: 'Pengunjung Unik',
                        data: dailyVisits.map(item => item.unique_visitors),
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }

        // Browser Chart
        const browserCtx = document.getElementById('browserChart');
        if (browserCtx) {
            const browserStats = @json($analytics['browser_stats']);
            
            new Chart(browserCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(browserStats),
                    datasets: [{
                        data: Object.values(browserStats),
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }
    }

    function initializeDataTable() {
        // Simple table initialization since we're using server-side pagination
        // Add any custom table functionality here
    }

    function initializeBulkSelection() {
        let selectedItems = [];

        // Select All functionality
        $('#selectAll').on('change', function() {
            const isChecked = this.checked;
            $('.row-checkbox:visible').each(function() {
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

        // Individual checkbox
        $(document).on('change', '.row-checkbox', function() {
            const value = this.value;

            if (this.checked) {
                if (!selectedItems.includes(value)) {
                    selectedItems.push(value);
                }
                $(this).closest('tr').addClass('table-active');
            } else {
                selectedItems = selectedItems.filter(id => id !== value);
                $(this).closest('tr').removeClass('table-active');
            }

            updateBulkActionsBar();
            updateSelectAllState();
        });

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

        function updateSelectAllState() {
            const visibleCheckboxes = $('.row-checkbox:visible');
            const selectAllCheckbox = document.getElementById('selectAll');
            let checkedCount = 0;

            visibleCheckboxes.each(function() {
                if (this.checked) checkedCount++;
            });

            if (checkedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === visibleCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        // Global functions for bulk operations
        window.clearSelection = function() {
            selectedItems = [];
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = false;
                $(cb).closest('tr').removeClass('table-active');
            });
            document.getElementById('selectAll').checked = false;
            document.getElementById('selectAll').indeterminate = false;
            updateBulkActionsBar();
        };

        window.bulkDeleteSelected = function() {
            if (selectedItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak ada data terpilih',
                    text: 'Silakan pilih data yang akan dihapus terlebih dahulu',
                    confirmButtonText: 'OK'
                });
                return;
            }

            document.getElementById('deleteMessage').innerHTML = 
                `Anda akan menghapus <strong>${selectedItems.length}</strong> data pengunjung yang dipilih.<br>Tindakan ini tidak dapat dibatalkan.`;
            
            const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
            modal.show();
        };

        // Confirm bulk delete
        document.getElementById('confirmBulkDelete').addEventListener('click', function() {
            if (selectedItems.length === 0) return;

            this.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>Menghapus...';
            this.disabled = true;

            const bulkDeleteIds = document.getElementById('bulkDeleteIds');
            bulkDeleteIds.innerHTML = '';

            selectedItems.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkDeleteIds.appendChild(input);
            });

            document.getElementById('bulkDeleteForm').submit();
        });
    }

    function initializeFilters() {
        // Search functionality
        let searchTimeout;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();
            
            searchTimeout = setTimeout(function() {
                applyFilters();
            }, 500);
        });

        // Filter dropdowns
        $('#countryFilter, #cityFilter').on('change', function() {
            applyFilters();
        });

        function applyFilters() {
            const search = $('#searchInput').val();
            const country = $('#countryFilter').val();
            const city = $('#cityFilter').val();
            const period = '{{ $period }}';

            // Build URL with filters
            const url = new URL(window.location.href);
            url.searchParams.set('period', period);
            
            if (search) url.searchParams.set('search', search);
            else url.searchParams.delete('search');
            
            if (country) url.searchParams.set('country', country);
            else url.searchParams.delete('country');
            
            if (city) url.searchParams.set('city', city);
            else url.searchParams.delete('city');

            window.location.href = url.toString();
        }

        window.resetFilters = function() {
            const period = '{{ $period }}';
            window.location.href = `{{ route('visitors.index') }}?period=${period}`;
        };
    }

    function initializeModals() {
        // Show Modal functionality
        $(document).on('click', '.btn-show', function() {
            const id = $(this).data('id');

            $('#modalLoadingState').show();
            $('#modalContentState').hide();
            $('#modalFooter').hide();
            $('#showModal').modal('show');

            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i>');

            $.ajax({
                url: `{{ route('visitors.index') }}/${id}`,
                type: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    setTimeout(() => {
                        const visitor = response.data;
                        const sessionData = response.session_data;
                        const relatedVisits = response.related_visits;

                        // Populate modal with data
                        $('#show_ip').text(visitor.ip);
                        $('#show_page').html(`<a href="${visitor.page_visited}" target="_blank">${visitor.page_visited}</a>`);
                        $('#show_country').text(visitor.country || '-');
                        $('#show_city').text(visitor.city || '-');
                        $('#show_browser').text(visitor.browser);
                        $('#show_device').text(visitor.device_type);
                        $('#show_os').text(visitor.operating_system);
                        $('#show_created_at').text(new Date(visitor.created_at).toLocaleString('id-ID'));
                        $('#show_user_agent').text(visitor.user_agent || '-');

                        // Session data
                        $('#show_total_visits').text(sessionData.total_visits);
                        $('#show_first_visit').text(sessionData.first_visit ? new Date(sessionData.first_visit).toLocaleString('id-ID') : '-');
                        $('#show_last_visit').text(sessionData.last_visit ? new Date(sessionData.last_visit).toLocaleString('id-ID') : '-');

                        // Coordinates
                        if (visitor.latitude && visitor.longitude) {
                            $('#show_koordinat_row').show();
                            const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${visitor.latitude},${visitor.longitude}`;
                            $('#openMapBtn').attr('href', googleMapsUrl);

                            $('#copyCoordinates').off('click').on('click', function() {
                                const coordinates = `${visitor.latitude}, ${visitor.longitude}`;
                                copyToClipboard(coordinates);
                            });
                        } else {
                            $('#show_koordinat_row').hide();
                        }

                        // Related visits
                        if (relatedVisits && relatedVisits.length > 0) {
                            let relatedHtml = '';
                            relatedVisits.forEach(function(visit) {
                                relatedHtml += `
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                        <div>
                                            <small><strong>${new Date(visit.created_at).toLocaleString('id-ID')}</strong></small>
                                            <br>
                                            <small class="text-muted">${visit.page_visited.substring(0, 40)}...</small>
                                        </div>
                                    </div>
                                `;
                            });
                            $('#related_visits_list').html(relatedHtml);
                            $('#show_related_visits').show();
                        } else {
                            $('#show_related_visits').hide();
                        }

                        $('#modalLoadingState').hide();
                        $('#modalContentState').show();
                        $('#modalFooter').show();
                    }, 800);
                },
                error: function(xhr) {
                    console.error('Error loading visitor details:', xhr);
                    $('#modalLoadingState').html(`
                        <div class="text-center py-5">
                            <i class="mdi mdi-alert-circle-outline text-danger" style="font-size: 3rem;"></i>
                            <div class="mt-3">
                                <h5 class="text-danger">Gagal Memuat Data</h5>
                                <p class="text-muted">${xhr.responseJSON?.message || 'Terjadi kesalahan saat memuat detail pengunjung'}</p>
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">
                                    <i class="mdi mdi-close"></i> Tutup
                                </button>
                            </div>
                        </div>
                    `);
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Reset modal state when closed
        $('#showModal').on('hidden.bs.modal', function() {
            $('#modalLoadingState').show();
            $('#modalContentState').hide();
            $('#modalFooter').hide();
        });
    }

    // Delete function
    window.deleteVisitor = function(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data pengunjung akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
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

                $.ajax({
                    url: `{{ route('visitors.index') }}/${id}`,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Remove row from table
                            $(`tr[data-id="${id}"]`).fadeOut(function() {
                                $(this).remove();
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Delete error:', xhr);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menghapus data: ' + (xhr.responseJSON?.message || 'Unknown error'),
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    };

    // Bulk delete old data
    window.bulkDelete = function() {
        Swal.fire({
            title: 'Hapus Data Lama?',
            text: "Pilih periode data yang akan dihapus:",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Lanjutkan',
            cancelButtonText: 'Batal',
            input: 'select',
            inputOptions: {
                '30': 'Data lebih dari 30 hari',
                '60': 'Data lebih dari 60 hari',
                '90': 'Data lebih dari 90 hari',
                '180': 'Data lebih dari 6 bulan',
                '365': 'Data lebih dari 1 tahun'
            },
            inputPlaceholder: 'Pilih periode'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const days = result.value;
                
                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    text: `Anda akan menghapus semua data pengunjung yang lebih dari ${days} hari. Tindakan ini tidak dapat dibatalkan!`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus semua!',
                    cancelButtonText: 'Batal'
                }).then((finalResult) => {
                    if (finalResult.isConfirmed) {
                        // Implement bulk delete old data logic here
                        Swal.fire({
                            title: 'Menghapus...',
                            text: 'Sedang menghapus data lama...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: '{{ route("visitors.bulk-destroy") }}',
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                days: days
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: `Data lama berhasil dihapus`,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan saat menghapus data',
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            }
        });
    };

    // Copy coordinates function
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showAlert('Koordinat berhasil disalin ke clipboard!', 'success');
            }, function(err) {
                console.error('Could not copy text: ', err);
                fallbackCopyTextToClipboard(text);
            });
        } else {
            fallbackCopyTextToClipboard(text);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showAlert('Koordinat berhasil disalin ke clipboard!', 'success');
            } else {
                showAlert('Gagal menyalin koordinat', 'error');
            }
        } catch (err) {
            console.error('Fallback: unable to copy', err);
            showAlert('Gagal menyalin koordinat', 'error');
        }

        document.body.removeChild(textArea);
    }

    // Helper function
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
});
</script>
@endsection
