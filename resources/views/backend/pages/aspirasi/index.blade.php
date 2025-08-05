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
                        <h4 class="card-title">
                            <i class="mdi mdi-message-text"></i>
                            Daftar Aspirasi
                        </h4>
                        <div class="d-flex gap-2">
                            <!-- Filter Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown"
                                    data-bs-toggle="dropdown">
                                    <i class="mdi mdi-filter"></i> Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item filter-item" href="#" data-filter="all">Semua</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <h6 class="dropdown-header">Status</h6>
                                    </li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="pending">Pending</a></li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="diproses">Diproses</a></li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="selesai">Selesai</a></li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="ditolak">Ditolak</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <h6 class="dropdown-header">Jenis Aspirasi</h6>
                                    </li>
                                    <li><a class="dropdown-item filter-item" href="#" data-filter="usulan">Usulan</a>
                                    </li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="keluhan">Keluhan</a></li>
                                    <li><a class="dropdown-item filter-item" href="#" data-filter="kritik">Kritik</a>
                                    </li>
                                    <li><a class="dropdown-item filter-item" href="#" data-filter="saran">Saran</a>
                                    </li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-warning" id="resetFilter">
                                <i class="mdi mdi-refresh"></i> Reset Filter
                            </button>
                        </div>
                    </div>

                    <!-- Enhanced Search and Filter Box -->
                    <div class="row mb-3">
                        <div class="col-lg-3 col-md-6 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                <input type="text" class="form-control" id="searchInput"
                                    placeholder="Cari nomor tiket, nama, email...">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-tag"></i></span>
                                <select class="form-control" id="kategoriFilter">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($kategoriAspirasi as $kategori)
                                        <option value="{{ $kategori->id }}">{{ $kategori->nama_kategori }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
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
                                        <option value="{{ $opd->id }}">{{ $opd->singkatan }} - {{ $opd->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                                <input type="date" class="form-control" id="tanggalFilter"
                                    placeholder="Filter Tanggal">
                            </div>
                        </div>
                    </div>

                    <!-- Search Results Info -->
                    <div id="searchInfo" class="d-none mb-3">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="mdi mdi-information-outline me-2"></i>
                            <span id="searchResultText"></span>
                            <button type="button" class="btn btn-sm btn-outline-info ms-auto" id="clearAllFilters">
                                Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <!-- Responsive Table -->
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table table-hover table-striped" id="aspirasiTable" style="min-width: 1200px;">
                            <thead class="table">
                                <tr>
                                    <th class="text-center" style="width: 50px; min-width: 50px;">No</th>
                                    <th style="min-width: 150px;">Nomor Tiket</th>
                                    <th style="min-width: 200px;">Pengirim</th>
                                    <th style="min-width: 150px;">Tertuju</th>
                                    <th class="text-center" style="width: 100px; min-width: 100px;">Jenis</th>
                                    <th class="text-center" style="width: 100px; min-width: 100px;">Status</th>
                                    <th class="text-center" style="width: 120px; min-width: 120px;">Tanggal</th>
                                    <th class="text-center" style="width: 120px; min-width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($aspirasi as $index => $item)
                                    <tr data-id="{{ $item->id }}" data-status="{{ $item->status }}"
                                        data-jenis="{{ $item->jenis_aspirasi }}"
                                        data-kategori="{{ $item->kategori_aspirasi_id }}"
                                        data-opd="{{ $item->kategoriAspirasi?->opd_id ?? '' }}"
                                        data-search="{{ strtolower($item->nomor_tiket . ' ' . $item->nama_pengirim . ' ' . $item->email . ' ' . $item->judul_aspirasi . ' ' . ($item->kategoriAspirasi?->opd?->name ?? '') . ' ' . ($item->kategoriAspirasi?->opd?->singkatan ?? '')) }}"
                                        data-date="{{ $item->created_at->format('Y-m-d') }}">
                                        <td class="text-center">{{ $aspirasi->firstItem() + $index }}</td>
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
                                                    'kritik' => 'warning',
                                                    'saran' => 'info',
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
                                        <td colspan="8" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-message-text-outline mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Tidak ada data aspirasi</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <!-- Scroll indicator for mobile -->
                        <div class="scroll-indicator d-md-none">
                            <div class="d-flex justify-content-center align-items-center py-2">
                                <i class="mdi mdi-gesture-swipe-horizontal text-muted me-2"></i>
                                <small class="text-muted">Geser tabel ke kiri/kanan untuk melihat lebih banyak</small>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if ($aspirasi->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                            <div class="mb-2 mb-md-0">
                                <span class="text-muted">
                                    Menampilkan {{ $aspirasi->firstItem() ?? 0 }} - {{ $aspirasi->lastItem() ?? 0 }}
                                    dari {{ $aspirasi->total() }} data
                                </span>
                            </div>
                            <nav>
                                {{ $aspirasi->appends(request()->query())->links('pagination::bootstrap-4', ['class' => 'pagination-sm']) }}
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
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Helper Functions
            function showAlert(message, type = 'success') {
                const alertClass = type === 'success' ? 'alert-success' : (type === 'info' ? 'alert-info' :
                    'alert-danger');
                const alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('#alertContainer').html(alertHtml);

                setTimeout(function() {
                    $('#alertContainer .alert').alert('close');
                }, 5000);
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
                    'keluhan': 'danger',
                    'kritik': 'warning',
                    'saran': 'info'
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

            // Enhanced filtering functionality
            function filterTable() {
                const searchTerm = $('#searchInput').val().toLowerCase();
                const kategoriFilter = $('#kategoriFilter').val();
                const opdFilter = $('#opdFilter').val();
                const tanggalFilter = $('#tanggalFilter').val();
                const activeFilter = $('.filter-item.active').data('filter') || 'all';

                let visibleCount = 0;
                const totalRows = $('#aspirasiTable tbody tr').not('#no-data-row').length;

                $('#aspirasiTable tbody tr').each(function() {
                    const $row = $(this);

                    if ($row.attr('id') === 'no-data-row') {
                        return;
                    }

                    let showRow = true;

                    // Search filter
                    if (searchTerm) {
                        const searchData = $row.data('search') || '';
                        if (!searchData.includes(searchTerm)) {
                            showRow = false;
                        }
                    }

                    // Kategori filter
                    if (kategoriFilter) {
                        const rowKategori = $row.data('kategori');
                        if (rowKategori != kategoriFilter) {
                            showRow = false;
                        }
                    }

                    // OPD filter
                    if (opdFilter) {
                        const rowOpd = $row.data('opd');
                        if (rowOpd != opdFilter) {
                            showRow = false;
                        }
                    }

                    // Tanggal filter
                    if (tanggalFilter) {
                        const rowDate = $row.data('date');
                        if (rowDate !== tanggalFilter) {
                            showRow = false;
                        }
                    }

                    // Status/Jenis filter
                    if (activeFilter !== 'all') {
                        const rowStatus = $row.data('status');
                        const rowJenis = $row.data('jenis');

                        if (rowStatus !== activeFilter && rowJenis !== activeFilter) {
                            showRow = false;
                        }
                    }

                    if (showRow) {
                        $row.show();
                        visibleCount++;
                        $row.find('td:first').text(visibleCount);
                    } else {
                        $row.hide();
                    }
                });

                // Update search info
                updateSearchInfo(searchTerm, kategoriFilter, opdFilter, tanggalFilter, activeFilter, visibleCount,
                    totalRows);

                // Show/hide no data message
                const $noDataRow = $('#no-data-row');
                if (visibleCount === 0 && $noDataRow.length === 0) {
                    $('#aspirasiTable tbody').append(`
                        <tr id="no-data-row">
                            <td colspan="8" class="text-center">
                                <div class="py-4">
                                    <i class="mdi mdi-message-text-outline mdi-48px text-muted"></i>
                                    <p class="text-muted mt-2">Tidak ada data yang cocok dengan filter</p>
                                </div>
                            </td>
                        </tr>
                    `);
                } else if (visibleCount > 0) {
                    $noDataRow.remove();
                }
            }

            function updateSearchInfo(searchTerm, kategoriFilter, opdFilter, tanggalFilter, activeFilter,
                visibleCount, totalRows) {
                const hasActiveFilters = searchTerm || kategoriFilter || opdFilter || tanggalFilter || (
                    activeFilter !== 'all');

                if (hasActiveFilters) {
                    let infoText = `Menampilkan ${visibleCount} dari ${totalRows} aspirasi`;

                    const filters = [];
                    if (searchTerm) filters.push(`pencarian: "${searchTerm}"`);
                    if (kategoriFilter) {
                        const kategoriText = $('#kategoriFilter option:selected').text();
                        filters.push(`kategori: ${kategoriText}`);
                    }
                    if (opdFilter) {
                        const opdText = $('#opdFilter option:selected').text();
                        filters.push(`OPD: ${opdText}`);
                    }
                    if (tanggalFilter) filters.push(`tanggal: ${tanggalFilter}`);
                    if (activeFilter !== 'all') filters.push(`filter: ${activeFilter}`);

                    if (filters.length > 0) {
                        infoText += ` (${filters.join(', ')})`;
                    }

                    $('#searchResultText').text(infoText);
                    $('#searchInfo').removeClass('d-none');
                } else {
                    $('#searchInfo').addClass('d-none');
                }
            }

            function clearAllFilters() {
                $('.filter-item').removeClass('active');
                $('.filter-item[data-filter="all"]').addClass('active');
                $('#searchInput').val('');
                $('#kategoriFilter').val('');
                $('#opdFilter').val('');
                $('#tanggalFilter').val('');
                filterTable();
            }

            // Event Handlers
            $('.filter-item').on('click', function(e) {
                e.preventDefault();
                $('.filter-item').removeClass('active');
                $(this).addClass('active');
                filterTable();
            });

            $('#searchInput, #kategoriFilter, #opdFilter, #tanggalFilter').on('input change', filterTable);

            $('#resetFilter, #clearAllFilters').on('click', clearAllFilters);

            // Show Modal with Loading Effect
            $(document).on('click', '.btn-show', function() {
                const id = $(this).data('id');

                // Show modal with loading state
                $('#modalLoadingState').show();
                $('#modalContentState').hide();
                $('#modalFooter').hide();
                $('#showModal').modal('show');

                // Add loading animation to button
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
                        // Simulate minimum loading time for better UX
                        setTimeout(() => {
                            // Handle both direct data and wrapped response
                            const aspirasi = response.data || response;

                            // Basic info
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

                            // Set status button data
                            $('#btnUpdateStatus').data('id', aspirasi.id);

                            // Show content and hide loading
                            $('#modalLoadingState').hide();
                            $('#modalContentState').show();
                            $('#modalFooter').show();

                        }, 800); // Minimum loading time for smooth UX
                    },
                    error: function(xhr) {
                        console.error('Error loading aspirasi details:', xhr);

                        // Show error in modal
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
                        // Restore button state
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

                                    $(`tr[data-id="${id}"]`).fadeOut(function() {
                                        $(this).remove();
                                        filterTable();
                                    });
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

            // Enhanced scroll functionality for table
            function initTableScroll() {
                const $tableResponsive = $('.table-responsive');

                if ($tableResponsive.length) {
                    // Add scroll shadow effect
                    $tableResponsive.on('scroll', function() {
                        const scrollLeft = $(this).scrollLeft();

                        // Add scrolled class when user scrolls
                        if (scrollLeft > 0) {
                            $(this).addClass('scrolled user-scrolled');
                        } else {
                            $(this).removeClass('scrolled');
                        }

                        // Hide scroll indicator after user interacts
                        if (scrollLeft > 10) {
                            $(this).addClass('user-scrolled');
                        }
                    });

                    // Auto-hide scroll indicator after 3 seconds on mobile
                    if (window.innerWidth < 768) {
                        setTimeout(() => {
                            $tableResponsive.addClass('user-scrolled');
                        }, 3000);
                    }
                }
            }

            // Initialize table scroll functionality
            initTableScroll();
        });
    </script>

    <style>
        /* Enhanced table responsive with horizontal scroll */
        .table-responsive {
            border-radius: 10px;
            overflow-x: auto;
            overflow-y: visible;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            -webkit-overflow-scrolling: touch;
        }

        .table {
            min-width: 1200px;
            margin-bottom: 0;
        }

        .table th {
            color: #000000 !important;
            border: none;
            font-weight: 600;
            white-space: nowrap;
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table td {
            border-color: #e9ecef;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* Allow text wrapping for specific columns */
        .table td:nth-child(3),
        /* Pengirim */
        .table td:nth-child(4) {
            /* Tertuju */
            white-space: normal;
            word-wrap: break-word;
        }

        /* Custom scrollbar */
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Scroll indicator */
        .scroll-indicator {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 10px 10px;
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile optimizations */
        @media (max-width: 767px) {
            .table-responsive {
                border-radius: 8px;
                box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            }

            .table {
                min-width: 1000px;
                font-size: 0.875rem;
            }

            .table th,
            .table td {
                padding: 0.5rem 0.75rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .scroll-indicator {
                position: sticky;
                bottom: 0;
                background: rgba(248, 249, 250, 0.95);
                backdrop-filter: blur(10px);
                z-index: 5;
                animation: pulse 2s infinite;
            }
        }

        /* Hide scroll indicator after user interaction */
        .table-responsive.user-scrolled .scroll-indicator {
            display: none;
        }

        /* Loading spinner styles */
        .spinner-border {
            animation: spinner-border .75s linear infinite;
        }

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
            }
        }

        /* Modal loading state */
        #modalLoadingState {
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Enhanced search info styling */
        #searchInfo .alert {
            border-left: 4px solid #17a2b8;
            background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
        }

        /* Input group styling */
        .input-group .form-control,
        .input-group .form-select {
            height: calc(1.5em + .75rem + 2px);
        }

        .input-group-text {
            border: 1px solid #ced4da;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        /* Enhanced button styling */
        .btn-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }

        /* Enhanced card styling */
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .card-img-holder {
            position: relative;
            overflow: hidden;
        }

        .card-img-absolute {
            position: absolute;
            top: 0;
            right: 0;
            opacity: 0.1;
        }

        /* Badge styling */
        .badge {
            font-size: 0.75em;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
        }

        /* Enhanced modal styling */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .modal-xl {
            max-width: 95%;
        }

        /* Form styling */
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Filter item styling */
        .filter-item.active {
            background-color: #667eea;
            color: white;
        }

        /* Table hover effects */
        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
            transition: background-color 0.2s ease;
        }

        /* Button hover effects */
        .btn-outline-info:hover,
        .btn-outline-danger:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }

        /* Pulse animation for scroll indicator */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        /* Statistics cards hover effect */
        .card.card-img-holder:hover {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* Loading button state */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endsection
