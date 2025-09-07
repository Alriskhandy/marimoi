@extends('backend.partials.main', ['title' => 'Aspirasi Masyarakat'])

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-message-text"></i>
            </span> Manajemen Aspirasi
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Aspirasi
                </li>
            </ul>
        </nav>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
            <div class="card bg-gradient-warning card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Pending
                        <i class="mdi mdi-clock-outline mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-4">{{ $stats['pending'] ?? 0 }}</h2>
                    <h6 class="card-text">Menunggu ditinjau</h6>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
            <div class="card bg-gradient-info card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Diproses
                        <i class="mdi mdi-cog-outline mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-4">{{ $stats['diproses'] ?? 0 }}</h2>
                    <h6 class="card-text">Sedang diproses</h6>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
            <div class="card bg-gradient-success card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Selesai
                        <i class="mdi mdi-check-circle-outline mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-4">{{ $stats['selesai'] ?? 0 }}</h2>
                    <h6 class="card-text">Telah diselesaikan</h6>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
            <div class="card bg-gradient-danger card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Ditolak
                        <i class="mdi mdi-close-circle-outline mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-4">{{ $stats['ditolak'] ?? 0 }}</h2>
                    <h6 class="card-text">Ditolak</h6>
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
                        <div>
                            <h4 class="card-title">
                                <i class="mdi mdi-message-text"></i>
                                Daftar Aspirasi
                            </h4>
                            <p class="card-description">
                                Kelola dan pantau aspirasi masyarakat
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            {{-- Quick Export Button --}}
                            <a href="{{ route('aspirasi.export') }}" class="btn btn-outline-success btn-sm"
                                title="Export Semua Data">
                                <i class="mdi mdi-file-excel"></i>
                                <span class="d-none d-md-inline">Quick Export</span>
                            </a>

                            {{-- Advanced Export Button --}}
                            <button type="button" class="btn btn-gradient-success btn-sm" data-bs-toggle="modal"
                                data-bs-target="#exportModal" title="Export dengan Filter">
                                <i class="mdi mdi-file-excel"></i>
                                <span class="d-none d-md-inline">Export Excel</span>
                            </button>
                        </div>
                    </div>

                    <!-- Bulk Actions Bar -->
                    <div id="bulkActionsBar" class="alert alert-info d-none mb-3" role="alert">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="mdi mdi-checkbox-multiple-marked me-2"></i>
                                <span id="selectedCount">0</span> aspirasi dipilih
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
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

                    <!-- Enhanced Search and Filter Box -->
                    <div class="row mb-3">


                        @php
                            $user = auth()->user();
                            $role = $user->role->slug;
                            $filteredKategori = $kategoriAspirasi;

                            if ($role === 'admin-opd') {
                                $filteredKategori = $kategoriAspirasi->filter(function ($kategori) use ($user) {
                                    return $kategori->opd_id === $user->opd_id;
                                });
                            }
                        @endphp

                        <div class="col-lg-3 col-md-6 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-tag"></i></span>
                                <select class="form-control" id="kategoriFilter">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($filteredKategori as $kategori)
                                        <option value="{{ $kategori->id }}">{{ $kategori->nama_kategori }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @php
                            $user = auth()->user();
                            $roleSlug = $user->role->slug ?? null;
                        @endphp

                        @if (in_array($roleSlug, ['super-admin', 'admin-bappeda']))
                            <div class="col-lg-3 col-md-6 mb-2">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="mdi mdi-office-building"></i></span>
                                    <select class="form-control" id="opdFilter">
                                        <option value="">Semua OPD</option>
                                        @php
                                            $opdList = collect($kategoriAspirasi)
                                                ->map(function ($kategori) {
                                                    return $kategori->opd;
                                                })
                                                ->filter()
                                                ->unique('id')
                                                ->sortBy('name');
                                        @endphp
                                        @foreach ($opdList as $opd)
                                            <option value="{{ $opd->id }}">{{ $opd->singkatan }} -
                                                {{ $opd->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div class="col-lg-3 col-md-6 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-filter"></i></span>
                                <select class="form-control" id="statusFilter">
                                    <option value="">Semua Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="diproses">Diproses</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <!-- DataTable -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="aspirasiTable" style="width:100%">
                            <thead>
                                <tr>
                                    @if ($aspirasi->isNotEmpty())
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
                                    <th style="min-width: 150px;">Nomor Tiket</th>
                                    <th style="min-width: 200px;">Pengirim</th>
                                    <th style="min-width: 150px;">Tertuju</th>
                                    <th class="text-center" style="width: 100px;">Jenis</th>
                                    <th class="text-center" style="width: 100px;">Status</th>
                                    <th class="text-center" style="width: 120px;">Tanggal</th>
                                    <th class="text-center" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($aspirasi as $index => $item)
                                    <tr data-id="{{ $item->id }}" data-status="{{ $item->status }}"
                                        data-jenis="{{ $item->jenis_aspirasi }}"
                                        data-kategori="{{ $item->kategori_aspirasi_id }}"
                                        data-opd="{{ $item->kategoriAspirasi?->opd_id ?? '' }}">
                                        @if ($aspirasi->isNotEmpty())
                                            <td>
                                                <div class="checkbox-wrapper">
                                                    <input class="form-check-input row-checkbox" type="checkbox"
                                                        value="{{ $item->id }}" id="check-{{ $item->id }}">
                                                    <label class="form-check-label" for="check-{{ $item->id }}">
                                                        <span class="visually-hidden">Select row</span>
                                                    </label>
                                                </div>
                                            </td>
                                        @endif
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong class="text-primary">{{ $item->nomor_tiket }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong>{{ $item->nama_pengirim }}</strong>
                                                <small class="text-muted">{{ $item->email }}</small>
                                                @if ($item->phone)
                                                    <small class="text-muted">{{ $item->phone }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($item->kategoriAspirasi?->opd)
                                                <div class="d-flex flex-column">
                                                    <strong
                                                        class="text-info">{{ $item->kategoriAspirasi->opd->singkatan }}</strong>
                                                    <small
                                                        class="text-muted">{{ Str::limit($item->kategoriAspirasi->opd->name, 25) }}</small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">Umum</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $jenis = strtolower($item->jenis_aspirasi);
                                                $jenisBadge = match ($jenis) {
                                                    'usulan' => 'primary',
                                                    'keluhan' => 'danger',
                                                    'kritik & saran' => 'info',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $jenisBadge }}">
                                                {{ ucfirst($item->jenis_aspirasi) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusBadge = match (strtolower($item->status)) {
                                                    'pending' => 'warning',
                                                    'diproses' => 'info',
                                                    'selesai' => 'success',
                                                    'ditolak' => 'danger',
                                                    default => 'light',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusBadge }}">
                                                {{ ucfirst($item->status) }}
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <small>{{ $item->created_at->format('d/m/Y') }}</small>
                                            @if ($item->tanggal_respon)
                                                <br><small class="text-success">Resp:
                                                    {{ Carbon\Carbon::parse($item->tanggal_respon)->format('d/m/Y') }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show"
                                                    data-id="{{ $item->id }}" title="Lihat Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                                    data-id="{{ $item->id }}"
                                                    onclick="deleteAspirasi({{ $item->id }})" title="Hapus">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-data-row">
                                        <td colspan="9" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-message-text-outline mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Tidak ada data aspirasi</p>
                                            </div>
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
    {{-- Add the Export Modal before the closing @endsection --}}
    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient-success text-white">
                    <h5 class="modal-title" id="exportModalLabel">
                        <i class="mdi mdi-file-excel me-2"></i>
                        Export Data Aspirasi ke Excel
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="exportForm">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="mdi mdi-information me-2"></i>
                            <strong>Informasi:</strong> Pilih filter untuk mengekspor data sesuai kebutuhan, atau biarkan
                            kosong untuk mengekspor semua data.
                        </div>

                        <div class="row">
                            <!-- Kategori Filter -->
                            <div class="col-md-6 mb-3">
                                <label for="export_kategori" class="form-label">
                                    <i class="mdi mdi-tag me-1"></i>Kategori
                                </label>
                                <select class="form-control" id="export_kategori" name="kategori">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($filteredKategori as $kategori)
                                        <option value="{{ $kategori->id }}">{{ $kategori->nama_kategori }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- OPD Filter -->
                            @if (in_array($roleSlug, ['super-admin', 'admin-bappeda']))
                                <div class="col-md-6 mb-3">
                                    <label for="export_opd" class="form-label">
                                        <i class="mdi mdi-office-building me-1"></i>OPD
                                    </label>
                                    <select class="form-control" id="export_opd" name="opd">
                                        <option value="">Semua OPD</option>
                                        @php
                                            $opdList = collect($kategoriAspirasi)
                                                ->map(function ($kategori) {
                                                    return $kategori->opd;
                                                })
                                                ->filter()
                                                ->unique('id')
                                                ->sortBy('name');
                                        @endphp
                                        @foreach ($opdList as $opd)
                                            <option value="{{ $opd->id }}">{{ $opd->singkatan }} -
                                                {{ $opd->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        <div class="row">
                            <!-- Status Filter -->
                            <div class="col-md-6 mb-3">
                                <label for="export_status" class="form-label">
                                    <i class="mdi mdi-filter me-1"></i>Status
                                </label>
                                <select class="form-control" id="export_status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="diproses">Diproses</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                            </div>

                            <!-- Jenis Filter -->
                            <div class="col-md-6 mb-3">
                                <label for="export_jenis" class="form-label">
                                    <i class="mdi mdi-format-list-bulleted me-1"></i>Jenis Aspirasi
                                </label>
                                <select class="form-control" id="export_jenis" name="jenis">
                                    <option value="">Semua Jenis</option>
                                    <option value="usulan">Usulan</option>
                                    <option value="kritik & saran">Kritik & Saran</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Date Range -->
                            <div class="col-md-6 mb-3">
                                <label for="export_start_date" class="form-label">
                                    <i class="mdi mdi-calendar-start me-1"></i>Tanggal Mulai
                                </label>
                                <input type="date" class="form-control" id="export_start_date" name="start_date">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="export_end_date" class="form-label">
                                    <i class="mdi mdi-calendar-end me-1"></i>Tanggal Akhir
                                </label>
                                <input type="date" class="form-control" id="export_end_date" name="end_date">
                            </div>
                        </div>

                        <!-- Preview Count -->
                        <div class="alert alert-secondary" id="exportPreview" style="display: none;">
                            <div class="d-flex align-items-center">
                                <i class="mdi mdi-file-document-outline me-2"></i>
                                <span id="previewText">Memuat preview...</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="mdi mdi-close me-1"></i>Batal
                        </button>
                        <button type="button" class="btn btn-outline-info me-2" id="previewExport">
                            <i class="mdi mdi-eye me-1"></i>Preview Jumlah Data
                        </button>
                        <button type="submit" class="btn btn-gradient-success" id="confirmExport">
                            <i class="mdi mdi-download me-1"></i>Download Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Show Modal -->
    <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="mdi mdi-eye me-2"></i>
                        Detail Aspirasi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- Loading State -->
                <div id="modalLoadingState" class="modal-body text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-3">
                        <h5 class="text-muted">Memuat detail aspirasi...</h5>
                        <p class="text-muted small">Mohon tunggu sebentar</p>
                    </div>
                </div>

                <!-- Content State (hidden by default) -->
                <div id="modalContentState" class="modal-body" style="display: none;">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Informasi Aspirasi</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Nomor Tiket:</strong>
                                            <p id="show_nomor_tiket" class="text-primary font-weight-bold"></p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Kategori:</strong>
                                            <p id="show_kategori" class="text-muted"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Nama Pengirim:</strong>
                                            <p id="show_nama_pengirim" class="text-muted"></p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Email:</strong>
                                            <p id="show_email" class="text-muted"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Telepon:</strong>
                                            <p id="show_phone" class="text-muted"></p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Jenis Aspirasi:</strong>
                                            <span id="show_jenis_aspirasi" class="badge"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Alamat:</strong>
                                            <p id="show_alamat" class="text-muted"></p>
                                        </div>
                                        <div class="col-6">
                                            <strong>OPD TERKAIT:</strong>
                                            <p id="show_opd" class="text-muted"></p>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <strong>Judul Aspirasi:</strong>
                                            <h6 id="show_judul_aspirasi" class="text-primary mt-2"></h6>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <strong>Isi Aspirasi:</strong>
                                            <div id="show_isi_aspirasi" class="text-muted p-3 bg-light rounded mt-2"
                                                style="min-height: 100px;"></div>
                                        </div>
                                    </div>

                                    <div class="row mt-3" id="show_koordinat_row" style="display: none;">
                                        <div class="col-12">
                                            <div class="border-top pt-3">
                                                <h6 class="text-success"><i class="mdi mdi-map-marker me-2"></i>Lokasi
                                                </h6>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <a id="openMapBtn" class="btn btn-success btn-sm" target="_blank"
                                                            rel="noopener noreferrer">
                                                            <i class="mdi mdi-map-marker"></i> Lihat di Google Maps
                                                        </a>
                                                        <button type="button" class="btn btn-outline-info btn-sm ms-2"
                                                            id="copyCoordinates">
                                                            <i class="mdi mdi-content-copy"></i> Copy Koordinat
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3" id="show_lampiran_container" style="display: none;">
                                        <div class="col-12">
                                            <div class="border-top pt-3">
                                                <h6 class="text-info"><i class="mdi mdi-attachment me-2"></i>Lampiran</h6>
                                                <div id="show_lampiran_list"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Status & Response</h6>
                                    <div>
                                        <span id="show_status" class="badge"></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Tanggal Dibuat:</strong>
                                        <p id="show_created_at" class="text-muted"></p>
                                    </div>

                                    <div class="mb-3" id="show_admin_info" style="display: none;">
                                        <strong>Admin Penanggungjawab:</strong>
                                        <p id="show_admin_name" class="text-muted"></p>
                                    </div>

                                    <div id="show_response_container" style="display: none;">
                                        <div class="border-top pt-3">
                                            <strong class="text-success">Tanggapan Admin:</strong>
                                            <div id="show_tanggapan_admin" class="text-muted p-3 bg-light rounded mt-2">
                                            </div>
                                            <small class="text-muted">
                                                <i class="mdi mdi-clock-outline me-1"></i>Ditanggapi pada: <span
                                                    id="show_tanggal_respon"></span>
                                            </small>
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
                    <button type="button" class="btn btn-gradient-primary btn-status" id="btnUpdateStatus">
                        <i class="mdi mdi-send"></i> Kirim Respons
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="statusModalLabel">
                        <i class="mdi mdi-pencil me-2"></i> Update Status Aspirasi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="statusForm" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="status_aspirasi_id" name="id">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="diproses">Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="tanggapan_admin" class="form-label">Tanggapan Admin</label>
                            <textarea class="form-control" id="tanggapan_admin" name="tanggapan_admin" rows="4"
                                placeholder="Berikan tanggapan/respon terhadap aspirasi ini..."></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-send"></i> Kirim Respons
                        </button>
                    </div>
                </form>
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
                            Anda akan menghapus <strong id="deleteCount">0</strong> aspirasi yang dipilih.
                            <br>Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmBulkDelete">
                        <i class="mdi mdi-trash-can-outline me-1"></i>Hapus Aspirasi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Form (Hidden) -->

    <!-- Menjadi -->
    <form id="bulkDeleteForm" method="POST" action="{{ route('aspirasi.bulk-destroy') }}" style="display: none;">
        @csrf
        @method('DELETE')
        <div id="bulkDeleteIds"></div>
    </form>
@endsection

@push('styles')
    <style>
        /* Checkbox styling yang sama dengan data spatial */
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

        /* DataTable styling */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 8px 12px !important;
            margin: 0 2px !important;
            border-radius: 6px !important;
            border: 1px solid #dee2e6 !important;
            background: #fff !important;
            color: #495057 !important;
            transition: all 0.2s ease !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e9ecef !important;
            border-color: #adb5bd !important;
            color: #495057 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #007bff, #0056b3) !important;
            border-color: #007bff !important;
            color: white !important;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3) !important;
        }

        /* Badge styling */
        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        /* Modal improvements */
        .modal-content {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        /* Loading spinner */
        .spinner-border {
            animation: spinner-border .75s linear infinite;
        }

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
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

        /* Accessibility */
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

        .btn:focus,
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
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
    </style>
@endpush

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#previewExport').on('click', function() {
                const btn = $(this);
                const originalText = btn.html();

                btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Memuat...');

                const formData = {
                    kategori: $('#export_kategori').val(),
                    opd: $('#export_opd').val(),
                    status: $('#export_status').val(),
                    jenis: $('#export_jenis').val(),
                    start_date: $('#export_start_date').val(),
                    end_date: $('#export_end_date').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $.ajax({
                    url: '/dashboard/aspirasi/preview-export', // Updated URL
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        const count = response.count || 0;
                        const filters = response.applied_filters || [];

                        let previewText =
                            `Akan mengekspor <strong>${count}</strong> data aspirasi`;

                        if (filters.length > 0) {
                            previewText += ` dengan filter: <em>${filters.join(', ')}</em>`;
                        }

                        $('#previewText').html(previewText);
                        $('#exportPreview').show();

                        if (count > 0) {
                            $('#confirmExport').prop('disabled', false);
                        } else {
                            $('#confirmExport').prop('disabled', true);
                            $('#previewText').html(
                                '<span class="text-warning"><i class="mdi mdi-alert me-1"></i>Tidak ada data yang sesuai dengan filter yang dipilih</span>'
                            );
                        }
                    },
                    error: function(xhr) {
                        console.error('Preview error:', xhr);
                        $('#previewText').html(
                            '<span class="text-danger"><i class="mdi mdi-alert-circle me-1"></i>Gagal memuat preview data</span>'
                        );
                        $('#exportPreview').show();
                        $('#confirmExport').prop('disabled', true);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Export Form Submit
            $('#exportForm').on('submit', function(e) {
                e.preventDefault();

                const btn = $('#confirmExport');
                const originalText = btn.html();

                btn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin me-1"></i>Mengekspor...');

                const formData = {
                    kategori: $('#export_kategori').val(),
                    opd: $('#export_opd').val(),
                    status: $('#export_status').val(),
                    jenis: $('#export_jenis').val(),
                    start_date: $('#export_start_date').val(),
                    end_date: $('#export_end_date').val()
                };

                // Create form for file download
                const downloadForm = $('<form>', {
                    method: 'POST',
                    action: '/dashboard/aspirasi/export-filtered' // Updated URL
                });

                // Add CSRF token
                downloadForm.append($('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: $('meta[name="csrf-token"]').attr('content')
                }));

                // Add form data
                $.each(formData, function(key, value) {
                    if (value) {
                        downloadForm.append($('<input>', {
                            type: 'hidden',
                            name: key,
                            value: value
                        }));
                    }
                });

                // Append form to body and submit
                $('body').append(downloadForm);

                try {
                    downloadForm.submit();

                    setTimeout(function() {
                        Swal.fire({
                            title: 'Export Berhasil!',
                            html: `
                        <div class="text-center">
                            <i class="mdi mdi-file-excel text-success" style="font-size: 3rem;"></i>
                            <p class="mt-2">File Excel sedang diunduh...</p>
                            <small class="text-muted">Periksa folder unduhan Anda</small>
                        </div>
                    `,
                            icon: 'success',
                            timer: 4000,
                            showConfirmButton: false,
                            allowOutsideClick: true
                        });

                        $('#exportModal').modal('hide');
                    }, 1000);

                } catch (error) {
                    console.error('Export error:', error);
                    Swal.fire({
                        title: 'Export Gagal!',
                        text: 'Terjadi kesalahan saat mengekspor data',
                        icon: 'error'
                    });
                } finally {
                    setTimeout(() => {
                        downloadForm.remove();
                        btn.prop('disabled', false).html(originalText);
                    }, 2000);
                }
            });


            // Initialize DataTable
            const table = $('#aspirasiTable').DataTable({
                "processing": true,
                "pageLength": 10,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Semua"]
                ],
                "order": [
                    [1, 'desc']
                ],
                "columnDefs": [{
                        "orderable": false,
                        "targets": [0, -1] // Disable ordering on checkbox and action columns
                    },
                    {
                        "searchable": false,
                        "targets": [0, -1] // Disable search on checkbox and action columns
                    },
                    {
                        "className": "text-center",
                        "targets": [0, 1, 5, 6, 7, -1] // Center align specific columns
                    }
                ],
                "language": {
                    "processing": "<div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div>",
                    "lengthMenu": "Tampilkan _MENU_ aspirasi per halaman",
                    "zeroRecords": "Aspirasi tidak ditemukan",
                    "emptyTable": "Tidak ada aspirasi tersedia",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ aspirasi",
                    "infoEmpty": "Menampilkan 0 sampai 0 dari 0 aspirasi",
                    "infoFiltered": "(difilter dari _MAX_ total aspirasi)",
                    "search": "Cari:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                },
                "dom": '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "drawCallback": function(settings) {
                    initializeCheckboxEvents();
                    $(this).find('tbody tr').css('opacity', '0').animate({
                        'opacity': '1'
                    }, 300);
                },
                "initComplete": function() {
                    $('.dataTables_filter input').attr('placeholder', 'Ketik untuk mencari...');

                    // Connect custom filters
                    $('#globalSearch').on('keyup', function() {
                        table.search(this.value).draw();
                    });

                    $('#kategoriFilter, #opdFilter, #statusFilter').on('change', function() {
                        applyFilters();
                    });
                }
            });

            // Custom filtering function
            function applyFilters() {
                const kategoriFilter = $('#kategoriFilter').val();
                const opdFilter = $('#opdFilter').val();
                const statusFilter = $('#statusFilter').val();

                // Clear previous search
                $.fn.dataTable.ext.search.pop();

                // Add custom filter
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    const row = table.row(dataIndex).node();
                    const $row = $(row);

                    // Kategori filter
                    if (kategoriFilter && $row.data('kategori') != kategoriFilter) {
                        return false;
                    }

                    // OPD filter
                    if (opdFilter && $row.data('opd') != opdFilter) {
                        return false;
                    }

                    // Status filter
                    if (statusFilter && $row.data('status') != statusFilter) {
                        return false;
                    }

                    return true;
                });

                table.draw();
            }

            // Bulk actions functionality
            let selectedItems = [];

            function initializeCheckboxEvents() {
                $('#selectAll').off('change');
                $('.row-checkbox').off('change');

                $('#selectAll').on('change', function() {
                    const isChecked = this.checked;
                    const visibleCheckboxes = table.$('.row-checkbox', {
                        "page": "current"
                    });

                    visibleCheckboxes.each(function() {
                        this.checked = isChecked;
                        const value = this.value;

                        if (isChecked && !selectedItems.includes(value)) {
                            selectedItems.push(value);
                        } else if (!isChecked) {
                            selectedItems = selectedItems.filter(id => id !== value);
                        }
                    });

                    updateBulkActionsBar();
                    updateSelectAllState();
                });

                $('.row-checkbox').on('change', function() {
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
            }

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
                const visibleCheckboxes = table.$('.row-checkbox', {
                    "page": "current"
                });
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

            // Global functions
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

            window.bulkDelete = function() {
                if (selectedItems.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Tidak ada aspirasi terpilih',
                        text: 'Silakan pilih aspirasi yang akan dihapus terlebih dahulu',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                document.getElementById('deleteCount').textContent = selectedItems.length;
                const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
                modal.show();
            };

            // Confirm bulk delete
            document.getElementById('confirmBulkDelete').addEventListener('click', function() {
                if (selectedItems.length === 0) {
                    alert('Tidak ada aspirasi yang dipilih untuk dihapus');
                    return;
                }

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

            // Initialize checkbox events
            initializeCheckboxEvents();

            // Helper Functions
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

            function clearFormErrors(form) {
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
            }

            function showFormErrors(form, errors) {
                clearFormErrors(form);
                $.each(errors, function(field, messages) {
                    const input = form.find(`[name="${field}"]`);
                    input.addClass('is-invalid');
                    input.siblings('.invalid-feedback').text(messages[0]);
                });
            }

            function getStatusClass(status) {
                const statusClasses = {
                    'pending': 'warning',
                    'diproses': 'info',
                    'selesai': 'success',
                    'ditolak': 'danger'
                };
                return statusClasses[status] || 'secondary';
            }

            function getJenisClass(jenis) {
                const jenisClasses = {
                    'usulan': 'primary',
                    'kritik & saran': 'info',
                };
                return jenisClasses[jenis] || 'secondary';
            }

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
                    console.error('Fallback: Oops, unable to copy', err);
                    showAlert('Gagal menyalin koordinat', 'error');
                }

                document.body.removeChild(textArea);
            }

            // Show Modal with Loading Effect
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
                    url: `{{ route('aspirasi.show', '') }}/${id}`,
                    type: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        setTimeout(() => {
                            const aspirasi = response.data || response;

                            // Populate modal with data
                            $('#show_opd').text(aspirasi.kategori_aspirasi?.opd?.name ||
                                'Tidak ada OPD');
                            $('#show_nomor_tiket').text(aspirasi.nomor_tiket);
                            $('#show_kategori').text(aspirasi.kategori_aspirasi?.nama ||
                                'Tanpa Kategori');
                            $('#show_nama_pengirim').text(aspirasi.nama_pengirim);
                            $('#show_email').text(aspirasi.email);
                            $('#show_phone').text(aspirasi.phone || '-');
                            $('#show_alamat').text(aspirasi.alamat);
                            $('#show_judul_aspirasi').text(aspirasi.judul_aspirasi);
                            $('#show_isi_aspirasi').text(aspirasi.isi_aspirasi);
                            $('#show_created_at').text(new Date(aspirasi.created_at)
                                .toLocaleDateString('id-ID'));

                            // Jenis aspirasi badge
                            const jenisClass = getJenisClass(aspirasi.jenis_aspirasi);
                            $('#show_jenis_aspirasi').removeClass().addClass(
                                    `badge bg-${jenisClass}`)
                                .text(aspirasi.jenis_aspirasi.charAt(0).toUpperCase() +
                                    aspirasi.jenis_aspirasi.slice(1));

                            // Status badge
                            const statusClass = getStatusClass(aspirasi.status);
                            $('#show_status').removeClass().addClass(
                                    `badge bg-${statusClass}`)
                                .text(aspirasi.status.charAt(0).toUpperCase() + aspirasi
                                    .status.slice(1));

                            // Koordinat
                            if (aspirasi.latitude && aspirasi.longitude) {
                                $('#show_koordinat_row').show();
                                const googleMapsUrl =
                                    `https://www.google.com/maps/search/?api=1&query=${aspirasi.latitude},${aspirasi.longitude}`;
                                $('#openMapBtn').attr('href', googleMapsUrl);

                                $('#copyCoordinates').off('click').on('click',
                                    function() {
                                        const coordinates =
                                            `${aspirasi.latitude}, ${aspirasi.longitude}`;
                                        copyToClipboard(coordinates);
                                    });
                            } else {
                                $('#show_koordinat_row').hide();
                            }

                            // Lampiran
                            if (aspirasi.lampiran) {
                                const lampiran = typeof aspirasi.lampiran === 'string' ?
                                    JSON.parse(aspirasi.lampiran) : aspirasi.lampiran;
                                let lampiranHtml = '';
                                lampiran.forEach(function(file, index) {
                                    lampiranHtml += `
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="mdi mdi-file-outline me-2"></i>
                                            <span>${file.original_name}</span>
                                            <a href="{{ route('aspirasi.downloadLampiran', ['aspirasi' => '__ID__', 'index' => '__INDEX__']) }}" 
                                               class="btn btn-outline-primary btn-sm ms-auto" download>
                                                <i class="mdi mdi-download"></i>
                                            </a>
                                        </div>
                                    `.replace('__ID__', aspirasi.id).replace('__INDEX__', index);
                                });
                                $('#show_lampiran_list').html(lampiranHtml);
                                $('#show_lampiran_container').show();
                            } else {
                                $('#show_lampiran_container').hide();
                            }

                            // Admin info
                            if (aspirasi.admin) {
                                $('#show_admin_name').text(aspirasi.admin.name);
                                $('#show_admin_info').show();
                            } else {
                                $('#show_admin_info').hide();
                            }

                            // Tanggapan admin
                            if (aspirasi.tanggapan_admin) {
                                $('#show_tanggapan_admin').text(aspirasi
                                    .tanggapan_admin);
                                $('#show_tanggal_respon').text(new Date(aspirasi
                                    .tanggal_respon).toLocaleDateString(
                                    'id-ID'));
                                $('#show_response_container').show();
                            } else {
                                $('#show_response_container').hide();
                            }

                            $('#btnUpdateStatus').data('id', aspirasi.id);

                            $('#modalLoadingState').hide();
                            $('#modalContentState').show();
                            $('#modalFooter').show();

                        }, 800);
                    },
                    error: function(xhr) {
                        console.error('Error loading aspirasi details:', xhr);
                        $('#modalLoadingState').html(`
                            <div class="text-center py-5">
                                <i class="mdi mdi-alert-circle-outline text-danger" style="font-size: 3rem;"></i>
                                <div class="mt-3">
                                    <h5 class="text-danger">Gagal Memuat Data</h5>
                                    <p class="text-muted">${xhr.responseJSON?.message || 'Terjadi kesalahan saat memuat detail aspirasi'}</p>
                                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">
                                        <i class="mdi mdi-close"></i> Tutup
                                    </button>
                                </div>
                            </div>
                        `);
                        showAlert('Gagal memuat data detail: ' + (xhr.responseJSON?.message ||
                            'Unknown error'), 'error');
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

            // Status Modal
            $(document).on('click', '.btn-status, #btnUpdateStatus', function() {
                const id = $(this).data('id');
                if (!id) {
                    showAlert('ID aspirasi tidak ditemukan', 'error');
                    return;
                }

                $('#status_aspirasi_id').val(id);
                $('#statusForm')[0].reset();
                clearFormErrors($('#statusForm'));
                $('#statusModal').modal('show');
                $('#showModal').modal('hide');
            });

            // Status Form Submit
            $('#statusForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const id = $('#status_aspirasi_id').val();

                if (!id) {
                    showAlert('ID aspirasi tidak ditemukan', 'error');
                    return;
                }

                clearFormErrors(form);

                const submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin"></i> Mengupdate...');

                const formData = {
                    status: $('#status').val(),
                    tanggapan_admin: $('#tanggapan_admin').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $.ajax({
                    url: `{{ route('aspirasi.updateStatus', '') }}/${id}`,
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#statusModal').modal('hide');
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Status update error:', xhr);
                        if (xhr.status === 422) {
                            showFormErrors(form, xhr.responseJSON.errors);
                        } else {
                            showAlert('Terjadi kesalahan server: ' + (xhr.responseJSON
                                ?.message || 'Unknown error'), 'error');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(
                            '<i class="mdi mdi-send"></i> Kirim Respons');
                    }
                });
            });

            // Delete function
            window.deleteAspirasi = function(id) {
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Data aspirasi akan dihapus permanen!",
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
                            url: `{{ route('aspirasi.destroy', '') }}/${id}`,
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

                                    // Remove row from DataTable
                                    const row = table.row($(`tr[data-id="${id}"]`));
                                    row.remove().draw();
                                }
                            },
                            error: function(xhr) {
                                console.error('Delete error:', xhr);
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan saat menghapus data: ' +
                                        (xhr.responseJSON?.message ||
                                            'Unknown error'),
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            };

            // Add keyboard shortcuts
            $(document).on('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                    e.preventDefault();
                    const selectAllCheckbox = document.getElementById('selectAll');
                    if (selectAllCheckbox && !selectAllCheckbox.checked) {
                        selectAllCheckbox.click();
                    }
                }

                if (e.key === 'Delete' && selectedItems.length > 0) {
                    e.preventDefault();
                    bulkDelete();
                }

                if (e.key === 'Escape' && selectedItems.length > 0) {
                    e.preventDefault();
                    clearSelection();
                }
            });

            // Add tooltips to action buttons
            $('[title]').tooltip();
        });
    </script>
@endsection
