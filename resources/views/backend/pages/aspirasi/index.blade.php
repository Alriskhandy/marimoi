@extends('backend.partials.main', ['title' => 'Aspirasi Masyarakat'])

@section('main')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $user = auth()->user();
        $roleSlug = $user->role->slug ?? null;
        $canSeeAllOpd = in_array($roleSlug, ['super-admin', 'admin-bappeda']);
        $filteredKategori = $roleSlug === 'admin-opd'
            ? $kategoriAspirasi->where('opd_id', $user->opd_id)
            : $kategoriAspirasi;
        $opdList = $kategoriAspirasi->pluck('opd')->filter()->unique('id')->sortBy('singkatan');
        $statusBadges = ['pending' => 'warning', 'diproses' => 'info', 'selesai' => 'success', 'ditolak' => 'danger'];
        $statusLabels = ['pending' => 'Menunggu', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak'];
        $jenisBadges = ['usulan' => 'primary', 'kritik & saran' => 'dark'];
    @endphp

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-message-text"></i>
            </span>
            Aspirasi Masyarakat
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Aspirasi</li>
            </ul>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Statistics Cards (klik untuk memfilter status) -->
    <div class="row g-3 stats-row-compact">
        @foreach ([['pending', 'warning', 'mdi-clock-outline'], ['diproses', 'info', 'mdi-cog-outline'], ['selesai', 'success', 'mdi-check-circle-outline'], ['ditolak', 'danger', 'mdi-close-circle-outline']] as [$statusKey, $color, $icon])
            <div class="col-6 col-md-3">
                <button type="button" class="card stat-card-compact stat-card-filter bg-gradient-{{ $color }} text-white w-100 text-start"
                    data-status-shortcut="{{ $statusKey }}" title="Tampilkan aspirasi {{ strtolower($statusLabels[$statusKey]) }}">
                    <div class="card-body w-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">{{ $statusLabels[$statusKey] }}</p>
                                <h3 class="stat-value">{{ number_format($stats[$statusKey] ?? 0) }}</h3>
                            </div>
                            <i class="mdi {{ $icon }} stat-icon"></i>
                        </div>
                    </div>
                </button>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Daftar Aspirasi</h4>
                            <p class="text-muted small mb-0">{{ number_format($stats['total'] ?? $aspirasi->count()) }} aspirasi
                                masuk dari masyarakat</p>
                        </div>
                        @can('aspirasi.export')
                            <div class="d-flex gap-2">
                                <a href="{{ route('aspirasi.export') }}" class="btn btn-outline-success" title="Export semua data">
                                    <i class="mdi mdi-file-excel"></i> <span class="d-none d-md-inline">Quick Export</span>
                                </a>
                                <button type="button" class="btn btn-gradient-success" data-bs-toggle="modal"
                                    data-bs-target="#exportModal" title="Export dengan filter">
                                    <i class="mdi mdi-file-excel"></i> <span class="d-none d-md-inline">Export Excel</span>
                                </button>
                            </div>
                        @endcan
                    </div>

                    <div class="row g-3 align-items-end filter-toolbar mb-3">
                        <div class="col-lg-3 col-md-6">
                            <label for="kategoriFilter" class="form-label fw-semibold mb-1">
                                <i class="mdi mdi-tag-outline me-1"></i>Kategori
                            </label>
                            <select class="form-select filter-control" id="kategoriFilter">
                                <option value="">Semua Kategori</option>
                                @foreach ($filteredKategori->sortBy('nama_kategori') as $kategori)
                                    <option value="{{ $kategori->id }}">{{ $kategori->nama_kategori }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($canSeeAllOpd)
                            <div class="col-lg-3 col-md-6">
                                <label for="opdFilter" class="form-label fw-semibold mb-1">
                                    <i class="mdi mdi-domain me-1"></i>OPD Tujuan
                                </label>
                                <select class="form-select filter-control" id="opdFilter">
                                    <option value="">Semua OPD</option>
                                    @foreach ($opdList as $opd)
                                        <option value="{{ $opd->id }}">{{ $opd->singkatan }}</option>
                                    @endforeach
                                    <option value="0">- Umum (tanpa OPD) -</option>
                                </select>
                            </div>
                        @endif

                        <div class="col-lg-3 col-md-6">
                            <label for="jenisFilter" class="form-label fw-semibold mb-1">
                                <i class="mdi mdi-shape-outline me-1"></i>Jenis
                            </label>
                            <select class="form-select filter-control" id="jenisFilter">
                                <option value="">Semua Jenis</option>
                                <option value="usulan">Usulan</option>
                                <option value="kritik & saran">Kritik &amp; Saran</option>
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="statusFilter" class="form-label fw-semibold mb-1">
                                <i class="mdi mdi-flag me-1"></i>Status
                            </label>
                            <select class="form-select filter-control" id="statusFilter">
                                <option value="">Semua Status</option>
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 align-items-end justify-content-between filter-toolbar mb-4">
                        <div class="col-lg-5 col-md-6">
                            <label for="tableSearch" class="form-label fw-semibold mb-1">
                                <i class="mdi mdi-magnify me-1"></i>Cari Aspirasi
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control filter-control" id="tableSearch"
                                    placeholder="Nomor tiket, nama, email, atau judul...">
                                <button class="btn btn-primary" type="button" id="searchTableBtn" title="Cari">
                                    <i class="mdi mdi-magnify"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="sortBySelect" class="form-label fw-semibold mb-1">
                                <i class="mdi mdi-sort me-1"></i>Urutkan
                            </label>
                            <select class="form-select filter-control" id="sortBySelect">
                                <option value="6-desc">Terbaru</option>
                                <option value="6-asc">Terlama</option>
                                <option value="2-asc">Pengirim (A-Z)</option>
                                <option value="5-asc">Status (A-Z)</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label for="per_page" class="form-label fw-semibold mb-1">
                                <i class="mdi mdi-table-row me-1"></i>Per halaman
                            </label>
                            <select class="form-select filter-control" id="per_page">
                                <option value="10">10 data</option>
                                <option value="25" selected>25 data</option>
                                <option value="50">50 data</option>
                                <option value="100">100 data</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <button type="button" class="btn btn-outline-secondary w-100" id="resetFiltersBtn"
                                title="Reset semua filter">
                                <i class="mdi mdi-filter-remove"></i> Reset
                            </button>
                        </div>
                    </div>

                    <div id="bulkActionsBar" class="alert alert-info d-none mb-3" role="alert">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <i class="mdi mdi-checkbox-multiple-marked me-2"></i>
                                <span id="selectedCount">0</span> aspirasi dipilih
                            </div>
                            <div>
                                @can('aspirasi.delete')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                                        <i class="mdi mdi-delete me-1"></i> Hapus Terpilih
                                    </button>
                                @endcan
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                                    <i class="mdi mdi-close me-1"></i> Batal
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="alertContainer"></div>

                    <div class="table-responsive">
                        <table class="table table-striped" id="aspirasiTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width: 12px;">
                                        <div class="checkbox-wrapper">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                            <label class="form-check-label" for="selectAll">
                                                <span class="visually-hidden">Pilih semua</span>
                                            </label>
                                        </div>
                                    </th>
                                    <th>Tiket &amp; Judul</th>
                                    <th>Pengirim</th>
                                    <th>Tujuan</th>
                                    <th>Jenis</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($aspirasi as $item)
                                    @php
                                        $jenis = strtolower($item->jenis_aspirasi);
                                        $opd = $item->kategoriAspirasi?->opd;
                                    @endphp
                                    <tr data-id="{{ $item->id }}" data-status="{{ $item->status }}"
                                        data-jenis="{{ $jenis }}" data-kategori="{{ $item->kategori_aspirasi_id }}"
                                        data-opd="{{ $opd?->id ?? '0' }}">
                                        <td>
                                            <div class="checkbox-wrapper">
                                                <input class="form-check-input row-checkbox" type="checkbox"
                                                    value="{{ $item->id }}" id="check-{{ $item->id }}">
                                                <label class="form-check-label" for="check-{{ $item->id }}">
                                                    <span class="visually-hidden">Pilih baris</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="ticket-code">{{ $item->nomor_tiket }}</span>
                                            <div class="text-dark fw-bold">{{ Str::limit($item->judul_aspirasi, 60) }}</div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $item->nama_pengirim }}</span>
                                            <br><small class="text-muted">{{ $item->email }}</small>
                                            @if ($item->phone)
                                                <br><small class="text-muted">{{ $item->phone }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($opd)
                                                <span class="fw-semibold">{{ $opd->singkatan }}</span>
                                                <br><small class="text-muted">{{ Str::limit($opd->name, 30) }}</small>
                                            @else
                                                <span class="badge bg-light text-muted border">Umum</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $jenisBadges[$jenis] ?? 'secondary' }}">{{ ucwords($item->jenis_aspirasi) }}</span>
                                        </td>
                                        <td data-order="{{ array_search($item->status, array_keys($statusLabels)) }}">
                                            <span class="badge bg-{{ $statusBadges[$item->status] ?? 'secondary' }}">
                                                {{ $statusLabels[$item->status] ?? ucfirst($item->status) }}
                                            </span>
                                        </td>
                                        <td data-order="{{ $item->created_at->timestamp }}">
                                            <span class="text-nowrap">{{ $item->created_at->translatedFormat('d M Y') }}</span>
                                            @if ($item->tanggal_respon)
                                                <br><small class="text-success text-nowrap" title="Tanggal respon">
                                                    <i class="mdi mdi-reply"></i> {{ $item->tanggal_respon->translatedFormat('d M Y') }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-show"
                                                data-id="{{ $item->id }}" title="Lihat detail &amp; tanggapi">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">
                        <i class="mdi mdi-file-excel text-success"></i> Export Aspirasi ke Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="exportForm">
                    <div class="modal-body">
                        <p class="text-muted small">
                            Pilih filter sesuai kebutuhan, atau biarkan kosong untuk mengekspor semua aspirasi.
                        </p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="export_kategori" class="form-label fw-semibold">Kategori</label>
                                <select class="form-select" id="export_kategori" name="kategori">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($filteredKategori->sortBy('nama_kategori') as $kategori)
                                        <option value="{{ $kategori->id }}">{{ $kategori->nama_kategori }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($canSeeAllOpd)
                                <div class="col-md-6">
                                    <label for="export_opd" class="form-label fw-semibold">OPD Tujuan</label>
                                    <select class="form-select" id="export_opd" name="opd">
                                        <option value="">Semua OPD</option>
                                        @foreach ($opdList as $opd)
                                            <option value="{{ $opd->id }}">{{ $opd->singkatan }} - {{ $opd->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="col-md-6">
                                <label for="export_status" class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="export_status" name="status">
                                    <option value="">Semua Status</option>
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="export_jenis" class="form-label fw-semibold">Jenis</label>
                                <select class="form-select" id="export_jenis" name="jenis">
                                    <option value="">Semua Jenis</option>
                                    <option value="usulan">Usulan</option>
                                    <option value="kritik & saran">Kritik &amp; Saran</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="export_start_date" class="form-label fw-semibold">Dari tanggal</label>
                                <input type="date" class="form-control" id="export_start_date" name="start_date">
                            </div>

                            <div class="col-md-6">
                                <label for="export_end_date" class="form-label fw-semibold">Sampai tanggal</label>
                                <input type="date" class="form-control" id="export_end_date" name="end_date">
                            </div>
                        </div>

                        <div class="alert alert-light border mt-3 mb-0" id="exportPreview" style="display: none;">
                            <i class="mdi mdi-file-document-outline me-1"></i>
                            <span id="previewText">Memuat preview...</span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-outline-primary" id="previewExport">
                            <i class="mdi mdi-counter"></i> Hitung Data
                        </button>
                        <button type="submit" class="btn btn-gradient-success" id="confirmExport">
                            <i class="mdi mdi-download"></i> Unduh Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="mdi mdi-message-text-outline"></i> Detail Aspirasi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div id="modalLoadingState" class="modal-body text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <p class="text-muted mt-3 mb-0">Memuat detail aspirasi...</p>
                </div>

                <div id="modalContentState" class="modal-body" style="display: none;">
                    {{-- Ringkasan --}}
                    <div class="detail-summary d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span id="show_nomor_tiket" class="ticket-code fs-6"></span>
                        <span id="show_status" class="badge"></span>
                        <span id="show_jenis_aspirasi" class="badge"></span>
                        <span class="text-muted small ms-auto">
                            <i class="mdi mdi-calendar"></i> Dikirim <span id="show_created_at"></span>
                        </span>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <h4 id="show_judul_aspirasi" class="detail-title mb-2"></h4>
                            <div id="show_isi_aspirasi" class="detail-text"></div>

                            <div id="show_koordinat_row" class="detail-section" style="display: none;">
                                <p class="detail-section-label"><i class="mdi mdi-map-marker"></i> Lokasi</p>
                                <a id="openMapBtn" class="btn btn-sm btn-outline-success" target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="mdi mdi-google-maps"></i> Buka di Google Maps
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="copyCoordinates">
                                    <i class="mdi mdi-content-copy"></i> Salin Koordinat
                                </button>
                            </div>

                            <div id="show_lampiran_container" class="detail-section" style="display: none;">
                                <p class="detail-section-label"><i class="mdi mdi-paperclip"></i> Lampiran</p>
                                <div id="show_lampiran_list"></div>
                            </div>

                            <div class="detail-section">
                                <p class="detail-section-label"><i class="mdi mdi-reply"></i> Tanggapan Petugas</p>
                                <div id="show_response_container" style="display: none;">
                                    <div id="show_tanggapan_admin" class="detail-text detail-response"></div>
                                    <small class="text-muted">
                                        <i class="mdi mdi-clock-outline"></i> Ditanggapi
                                        <span id="show_tanggal_respon"></span>
                                        <span id="show_admin_info" style="display: none;">oleh <span
                                                id="show_admin_name" class="fw-semibold"></span></span>
                                    </small>
                                </div>
                                <p id="show_response_empty" class="text-muted small mb-0">
                                    Belum ada tanggapan. Klik <b>Tanggapi</b> untuk mengubah status dan membalas warga.
                                </p>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="detail-side">
                                <p class="detail-section-label"><i class="mdi mdi-account"></i> Pengirim</p>
                                <dl class="detail-list">
                                    <dt>Nama</dt>
                                    <dd id="show_nama_pengirim"></dd>
                                    <dt>Email</dt>
                                    <dd id="show_email" class="text-break"></dd>
                                    <dt>No. WhatsApp</dt>
                                    <dd id="show_phone"></dd>
                                    <dt>Alamat</dt>
                                    <dd id="show_alamat"></dd>
                                </dl>

                                <p class="detail-section-label mt-3"><i class="mdi mdi-domain"></i> Tujuan</p>
                                <dl class="detail-list mb-0">
                                    <dt>Kategori</dt>
                                    <dd id="show_kategori"></dd>
                                    <dt>OPD</dt>
                                    <dd id="show_opd"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" id="modalFooter" style="display: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    @can('aspirasi.edit')
                        <button type="button" class="btn btn-gradient-primary btn-status" id="btnUpdateStatus">
                            <i class="mdi mdi-reply"></i> Tanggapi
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Tanggapi / Ubah Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">
                        <i class="mdi mdi-reply"></i> Tanggapi Aspirasi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="statusForm" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="status_aspirasi_id" name="id">
                    <div class="modal-body">
                        <p class="text-muted small">
                            Tiket <span id="status_ticket" class="ticket-code"></span>
                        </p>

                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="tanggapan_admin" class="form-label fw-semibold">Tanggapan untuk warga</label>
                            <textarea class="form-control" id="tanggapan_admin" name="tanggapan_admin" rows="5"
                                placeholder="Tuliskan tindak lanjut atau jawaban untuk pengirim aspirasi..."></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="alert alert-light border small mb-0">
                            <i class="mdi mdi-email-outline me-1"></i>
                            Bila status <b>Diproses</b>, <b>Selesai</b>, atau <b>Ditolak</b>, pengirim menerima email
                            berisi tanggapan ini dan dapat melacaknya dengan nomor tiket.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-send"></i> Simpan &amp; Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Konfirmasi Hapus Massal Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkDeleteModalLabel">
                        <i class="mdi mdi-delete text-danger"></i> Hapus Aspirasi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">Hapus <strong id="deleteCount">0</strong> aspirasi terpilih?</p>
                    <p class="text-muted small mb-0">Data dihapus permanen dan tidak dapat dikembalikan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmBulkDelete">
                        <i class="mdi mdi-delete"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <form id="bulkDeleteForm" method="POST" action="{{ route('aspirasi.bulk-destroy') }}" style="display: none;">
        @csrf
        @method('DELETE')
        <div id="bulkDeleteIds"></div>
    </form>
@endsection

@push('styles')
    <style>
        /* Pola tampilan daftar — mengikuti halaman Daftar Layer & Data (spatial-layers/index). */
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

        /* Kartu statistik berfungsi sebagai pintasan filter status. */
        .stat-card-filter {
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .stat-card-filter:hover,
        .stat-card-filter:focus-visible {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .stat-card-filter.is-active {
            outline: 3px solid rgba(0, 0, 0, 0.25);
            outline-offset: 2px;
        }

        .ticket-code {
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            color: #0d6efd;
            letter-spacing: 0.3px;
        }

        /* Modal detail aspirasi */
        .detail-summary {
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #eef2f7;
        }

        .detail-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.4;
        }

        .detail-text {
            white-space: pre-line;
            word-break: break-word;
            color: #374151;
            line-height: 1.6;
        }

        .detail-response {
            padding: 0.75rem 1rem;
            margin-bottom: 0.4rem;
            background: #f0f7ff;
            border-left: 3px solid #0d6efd;
            border-radius: 6px;
        }

        .detail-section {
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid #eef2f7;
        }

        .detail-section-label {
            margin-bottom: 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
        }

        .detail-side {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .detail-list dt {
            font-size: 0.75rem;
            font-weight: 500;
            color: #6b7280;
        }

        .detail-list dd {
            margin-bottom: 0.6rem;
            font-size: 0.875rem;
            color: #1f2937;
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


            // Filter kustom khusus tabel aspirasi (dibaca dari data-* tiap baris).
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'aspirasiTable') {
                    return true;
                }

                const $row = $(settings.aoData[dataIndex].nTr);
                const checks = {
                    kategori: $('#kategoriFilter').val(),
                    opd: $('#opdFilter').val(),
                    jenis: $('#jenisFilter').val(),
                    status: $('#statusFilter').val(),
                };

                return Object.entries(checks).every(([key, value]) => !value || String($row.data(key)) === value);
            });

            const FILTER_STORAGE_KEY = 'aspirasiIndexFilters';
            const filterSelects = '#kategoriFilter, #opdFilter, #jenisFilter, #statusFilter';

            function saveFiltersToStorage() {
                try {
                    localStorage.setItem(FILTER_STORAGE_KEY, JSON.stringify({
                        kategori: $('#kategoriFilter').val(),
                        opd: $('#opdFilter').val() || '',
                        jenis: $('#jenisFilter').val(),
                        status: $('#statusFilter').val(),
                        search: $('#tableSearch').val(),
                        sortBy: $('#sortBySelect').val(),
                        perPage: $('#per_page').val(),
                    }));
                } catch (e) {
                    // localStorage bisa diblokir (mode privat) — filter tetap berlaku untuk sesi ini.
                }
            }

            function loadFiltersFromStorage() {
                try {
                    return JSON.parse(localStorage.getItem(FILTER_STORAGE_KEY) || 'null');
                } catch (e) {
                    return null;
                }
            }

            function syncStatusShortcuts() {
                const status = $('#statusFilter').val();
                $('[data-status-shortcut]').each(function() {
                    const active = this.dataset.statusShortcut === status;
                    this.classList.toggle('is-active', active);
                    this.setAttribute('aria-pressed', String(active));
                });
            }

            const table = $('#aspirasiTable').DataTable({
                "processing": true,
                "pageLength": 25,
                "lengthMenu": [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                "order": [
                    [6, 'desc']
                ],
                "columnDefs": [{
                        "orderable": false,
                        "searchable": false,
                        "targets": [0, -1]
                    },
                    {
                        "className": "text-center",
                        "targets": [0, 4, 5, -1]
                    },
                ],
                "language": {
                    "processing": "<div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Memuat...</span></div>",
                    "zeroRecords": "Aspirasi tidak ditemukan. Coba ubah kata kunci atau reset filter.",
                    "emptyTable": "Belum ada aspirasi yang masuk",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ aspirasi",
                    "infoEmpty": "Menampilkan 0 dari 0 aspirasi",
                    "infoFiltered": "(difilter dari _MAX_ total aspirasi)",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    },
                },
                "dom": '<"row"<"col-sm-12"tr>>' +
                    '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "drawCallback": function() {
                    initializeCheckboxEvents();
                },
                "initComplete": function() {
                    // initComplete berjalan sinkron di dalam evaluasi `const table = ...` — pakai this.api().
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
                    $(filterSelects).on('change', function() {
                        syncStatusShortcuts();
                        api.draw();
                        saveFiltersToStorage();
                    });
                    $('#sortBySelect').on('change', function() {
                        const [column, direction] = $(this).val().split('-');
                        api.order([parseInt(column, 10), direction]).draw();
                        saveFiltersToStorage();
                    });

                    // Kartu statistik: klik untuk memfilter status, klik lagi untuk melepas.
                    $('[data-status-shortcut]').on('click', function() {
                        const status = this.dataset.statusShortcut;
                        $('#statusFilter').val($('#statusFilter').val() === status ? '' : status).trigger('change');
                    });

                    $('#resetFiltersBtn').on('click', function() {
                        $(filterSelects).val('');
                        $('#tableSearch').val('');
                        $('#sortBySelect').val('6-desc');
                        $('#per_page').val('25');
                        syncStatusShortcuts();
                        api.search('').order([6, 'desc']).page.len(25).draw();
                        try {
                            localStorage.removeItem(FILTER_STORAGE_KEY);
                        } catch (e) {
                            // no-op
                        }
                    });

                    // Pulihkan filter terakhir supaya tidak perlu memfilter ulang setelah reload.
                    const stored = loadFiltersFromStorage() || {};
                    $('#kategoriFilter').val(stored.kategori || '');
                    $('#opdFilter').val(stored.opd || '');
                    $('#jenisFilter').val(stored.jenis || '');
                    $('#statusFilter').val(stored.status || '');
                    $('#tableSearch').val(stored.search || '');
                    $('#sortBySelect').val(stored.sortBy || '6-desc');
                    $('#per_page').val(stored.perPage || '25');

                    // Opsi yang tersimpan bisa saja sudah tidak ada (mis. kategori dihapus).
                    $(filterSelects + ', #sortBySelect, #per_page').each(function() {
                        if (this.selectedIndex === -1) {
                            this.selectedIndex = 0;
                        }
                    });

                    const [sortColumn, sortDirection] = $('#sortBySelect').val().split('-');
                    syncStatusShortcuts();
                    api.page.len(parseInt($('#per_page').val(), 10));
                    api.order([parseInt(sortColumn, 10), sortDirection]);
                    api.search($('#tableSearch').val()).draw();
                }
            });

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

            const STATUS_LABELS = @json($statusLabels);
            const JENIS_LABELS = {
                'usulan': 'Usulan',
                'kritik & saran': 'Kritik & Saran'
            };

            function formatTanggal(value, withTime = true) {
                if (!value) {
                    return '-';
                }
                return new Date(value).toLocaleString('id-ID', withTime ? {
                    dateStyle: 'long',
                    timeStyle: 'short'
                } : {
                    dateStyle: 'long'
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
                    'kritik & saran': 'dark',
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
                    url: "{{ route('aspirasi.show', ['aspirasi' => '__ID__']) }}".replace('__ID__',
                        id),
                    type: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        {
                            const aspirasi = response.data || response;

                            // Populate modal with data
                            const opd = aspirasi.kategori_aspirasi?.opd;
                            $('#show_opd').text(opd ? `${opd.singkatan} - ${opd.name}` : 'Umum (tanpa OPD)');
                            $('#show_nomor_tiket').text(aspirasi.nomor_tiket);
                            $('#show_kategori').text(aspirasi.kategori_aspirasi?.nama_kategori ||
                                'Tanpa Kategori');
                            $('#show_nama_pengirim').text(aspirasi.nama_pengirim);
                            $('#show_email').text(aspirasi.email);
                            $('#show_phone').text(aspirasi.phone || '-');
                            $('#show_alamat').text(aspirasi.alamat || '-');
                            $('#show_judul_aspirasi').text(aspirasi.judul_aspirasi);
                            $('#show_isi_aspirasi').text(aspirasi.isi_aspirasi);
                            $('#show_created_at').text(formatTanggal(aspirasi.created_at));

                            // Jenis aspirasi badge
                            const jenisClass = getJenisClass(aspirasi.jenis_aspirasi);
                            $('#show_jenis_aspirasi').removeClass().addClass(
                                    `badge bg-${jenisClass}`)
                                .text(JENIS_LABELS[aspirasi.jenis_aspirasi] || aspirasi.jenis_aspirasi);

                            // Status badge
                            const statusClass = getStatusClass(aspirasi.status);
                            $('#show_status').removeClass().addClass(
                                    `badge bg-${statusClass}`)
                                .text(STATUS_LABELS[aspirasi.status] || aspirasi.status);

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
                            // Perbaikan untuk bagian lampiran - SINGLE FILE (String)
                            // Ganti bagian lampiran yang ada dengan kode ini:

                            // Lampiran - Handler untuk single file (string)
                            if (aspirasi.lampiran && aspirasi.lampiran.trim() !== '') {
                                try {
                                    // Lampiran sekarang adalah string, bukan array
                                    const filename = aspirasi.lampiran;

                                    // Buat URL untuk download dan preview
                                    const downloadUrl =
                                        `/storage/aspirasi_lampiran/${filename}`;
                                    const directDownloadUrl =
                                        `/dashboard/aspirasi/${aspirasi.id}/lampiran/0/download`;
                                    const previewUrl =
                                        `/dashboard/aspirasi/${aspirasi.id}/lampiran/0/preview`;

                                    // Extract nama file tanpa timestamp
                                    const originalName = filename.replace(
                                        /^\d+_[a-f0-9]+\./, '');
                                    const displayName = originalName.length > 30 ?
                                        originalName.substring(0, 30) + '...' :
                                        originalName;

                                    const fileExt = filename.split('.').pop()
                                        .toLowerCase();
                                    let iconClass = 'mdi-file-outline';
                                    let canPreview = false;

                                    // Tentukan icon dan kemampuan preview
                                    switch (fileExt) {
                                        case 'pdf':
                                            iconClass = 'mdi-file-pdf-box';
                                            canPreview = true;
                                            break;
                                        case 'jpg':
                                        case 'jpeg':
                                        case 'png':
                                        case 'gif':
                                        case 'bmp':
                                        case 'webp':
                                            iconClass = 'mdi-file-image-box';
                                            canPreview = true;
                                            break;
                                        case 'doc':
                                        case 'docx':
                                            iconClass = 'mdi-file-word-box';
                                            canPreview = false;
                                            break;
                                        case 'xls':
                                        case 'xlsx':
                                            iconClass = 'mdi-file-excel-box';
                                            canPreview = false;
                                            break;
                                        case 'dwg':
                                        case 'dxf':
                                            iconClass = 'mdi-file-cad-box';
                                            canPreview = false;
                                            break;
                                        default:
                                            iconClass = 'mdi-file-outline';
                                            canPreview = false;
                                    }

                                    const lampiranHtml = `
            <div class="d-flex align-items-center mb-2 p-3 border rounded lampiran-item" 
                 data-file-name="${filename}"
                 data-file-ext="${fileExt}"
                 data-aspirasi-id="${aspirasi.id}"
                 data-direct-url="${downloadUrl}">
                <i class="mdi ${iconClass} me-3 text-primary" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark" title="${originalName}">${displayName}</div>
                    <small class="text-muted">${fileExt.toUpperCase()} • Single File</small>
                </div>
                <div class="btn-group ms-2">
                    ${canPreview ? `
                                        <button type="button" class="btn btn-outline-info btn-sm btn-preview-single" 
                                                data-preview-url="${previewUrl}"
                                                data-direct-url="${downloadUrl}"
                                                data-filename="${originalName}"
                                                data-ext="${fileExt}"
                                                title="Preview ${originalName}">
                                            <i class="mdi mdi-eye"></i>
                                            <span class="d-none d-md-inline ms-1">Preview</span>
                                        </button>
                                    ` : ''}
                    <button type="button" class="btn btn-outline-primary btn-sm btn-download-single" 
                            data-download-url="${directDownloadUrl}"
                            data-direct-url="${downloadUrl}"
                            data-filename="${originalName}"
                            title="Download ${originalName}">
                        <i class="mdi mdi-download"></i>
                        <span class="d-none d-md-inline ms-1">Download</span>
                    </button>
                </div>
            </div>
        `;

                                    $('#show_lampiran_list').html(lampiranHtml);
                                    $('#show_lampiran_container').show();

                                    // Bind event handlers untuk single file
                                    bindSingleLampiranEvents();

                                } catch (error) {
                                    console.error('Error processing lampiran:', error);
                                    $('#show_lampiran_container').hide();
                                }
                            } else {
                                $('#show_lampiran_container').hide();
                            }

                            // Function untuk bind event handlers single file
                            function bindSingleLampiranEvents() {
                                // Remove existing handlers
                                $(document).off('click', '.btn-download-single');
                                $(document).off('click', '.btn-preview-single');

                                // Download button handler untuk single file
                                $(document).on('click', '.btn-download-single',
                                    function(e) {
                                        e.preventDefault();
                                        e.stopPropagation();

                                        const $btn = $(this);
                                        const downloadUrl = $btn.data(
                                            'download-url');
                                        const directUrl = $btn.data('direct-url');
                                        const filename = $btn.data('filename');

                                        if (!filename) {
                                            showAlert('Data file tidak lengkap',
                                                'error');
                                            return;
                                        }

                                        downloadSingleFile(downloadUrl, directUrl,
                                            filename, $btn);
                                    });

                                // Preview button handler untuk single file
                                $(document).on('click', '.btn-preview-single', function(
                                    e) {
                                    e.preventDefault();
                                    e.stopPropagation();

                                    const $btn = $(this);
                                    const previewUrl = $btn.data('preview-url');
                                    const directUrl = $btn.data('direct-url');
                                    const filename = $btn.data('filename');
                                    const ext = $btn.data('ext');

                                    if (!filename) {
                                        showAlert('Data file tidak lengkap',
                                            'error');
                                        return;
                                    }

                                    previewSingleFile(previewUrl, directUrl,
                                        filename, ext, $btn);
                                });
                            }

                            // Function untuk download single file dengan fallback
                            function downloadSingleFile(downloadUrl, directUrl,
                                filename, $btn) {
                                const originalHtml = $btn.html();
                                $btn.prop('disabled', true).html(
                                    '<i class="mdi mdi-loading mdi-spin"></i>');

                                // Coba download via controller terlebih dahulu
                                fetch(downloadUrl)
                                    .then(response => {
                                        if (response.ok) {
                                            // Jika controller berhasil, download via controller
                                            window.location.href = downloadUrl;
                                            showAlert(
                                                `Download ${filename} dimulai...`,
                                                'success');
                                        } else {
                                            // Jika controller gagal, coba direct URL
                                            console.log(
                                                'Controller download failed, trying direct URL...'
                                            );
                                            window.location.href = directUrl;
                                            showAlert(
                                                `Download ${filename} dimulai (direct)...`,
                                                'success');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Download error:', error);
                                        // Fallback ke direct URL
                                        window.location.href = directUrl;
                                        showAlert(
                                            `Download ${filename} dimulai (fallback)...`,
                                            'success');
                                    })
                                    .finally(() => {
                                        setTimeout(() => {
                                            $btn.prop('disabled', false)
                                                .html(originalHtml);
                                        }, 1000);
                                    });
                            }

                            // Function untuk preview single file dengan fallback
                            function previewSingleFile(previewUrl, directUrl, filename,
                                fileExt, $btn) {
                                const originalHtml = $btn.html();
                                $btn.prop('disabled', true).html(
                                    '<i class="mdi mdi-loading mdi-spin"></i>');

                                const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp',
                                    'webp'
                                ];
                                const pdfExts = ['pdf'];

                                if (imageExts.includes(fileExt.toLowerCase())) {
                                    // Preview gambar
                                    previewSingleImage(previewUrl, directUrl, filename,
                                        $btn, originalHtml);
                                } else if (pdfExts.includes(fileExt.toLowerCase())) {
                                    // Preview PDF di tab baru
                                    window.open(previewUrl, '_blank');
                                    $btn.prop('disabled', false).html(originalHtml);
                                } else {
                                    // File lain, langsung download
                                    downloadSingleFile(previewUrl.replace('preview',
                                        'download'), directUrl, filename, $btn);
                                }
                            }

                            // Function untuk preview single image dengan fallback
                            function previewSingleImage(previewUrl, directUrl, filename,
                                $btn, originalHtml) {
                                // Test image load dari controller terlebih dahulu
                                const testImg = new Image();

                                testImg.onload = function() {
                                    showSingleImageModal(previewUrl, filename);
                                    $btn.prop('disabled', false).html(originalHtml);
                                };

                                testImg.onerror = function() {
                                    console.log(
                                        'Controller preview failed, trying direct URL...'
                                    );

                                    // Jika controller gagal, coba direct URL
                                    const fallbackImg = new Image();

                                    fallbackImg.onload = function() {
                                        showSingleImageModal(directUrl,
                                            filename);
                                        $btn.prop('disabled', false).html(
                                            originalHtml);
                                    };

                                    fallbackImg.onerror = function() {
                                        console.error(
                                            'Both preview methods failed');
                                        showAlert(
                                            'Gagal memuat preview gambar. File mungkin tidak dapat diakses.',
                                            'error');
                                        $btn.prop('disabled', false).html(
                                            originalHtml);
                                    };

                                    fallbackImg.src = directUrl;
                                };

                                testImg.src = previewUrl;
                            }

                            // Function untuk menampilkan modal gambar single file
                            function showSingleImageModal(imageUrl, filename) {
                                const modalHtml = `
        <div class="modal fade" id="singleImagePreviewModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="mdi mdi-image me-2"></i>${filename}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center p-0" style="background: #000;">
                        <img src="${imageUrl}" class="img-fluid" style="max-height: 70vh; max-width: 100%;" alt="${filename}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light" onclick="window.open('${imageUrl}', '_blank')">
                            <i class="mdi mdi-open-in-new me-1"></i>Buka di Tab Baru
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="mdi mdi-close me-1"></i>Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

                                // Remove existing modal
                                $('#singleImagePreviewModal').remove();

                                // Add and show modal
                                $('body').append(modalHtml);
                                const modal = new bootstrap.Modal(document
                                    .getElementById('singleImagePreviewModal'));
                                modal.show();

                                // Clean up when modal is hidden
                                $('#singleImagePreviewModal').on('hidden.bs.modal',
                                    function() {
                                        $(this).remove();
                                    });
                            }

                            // CSS untuk single lampiran
                            const singleLampiranCSS = `
<style id="single-lampiran-styles">
.lampiran-item {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef !important;
    background: #fff;
}

.lampiran-item:hover {
    background-color: #f8f9fa;
    border-color: #007bff !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
}

.lampiran-item .mdi-file-pdf-box { color: #dc3545; }
.lampiran-item .mdi-file-word-box { color: #2b579a; }
.lampiran-item .mdi-file-excel-box { color: #217346; }
.lampiran-item .mdi-file-image-box { color: #6f42c1; }
.lampiran-item .mdi-file-cad-box { color: #ff9800; }
.lampiran-item .mdi-file-outline { color: #6c757d; }

.lampiran-item .btn-group .btn {
    transition: all 0.2s ease;
}

.lampiran-item .btn-group .btn:hover {
    transform: scale(1.05);
}

#singleImagePreviewModal .modal-content {
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

#singleImagePreviewModal .modal-body {
    background: #000;
}

#singleImagePreviewModal img {
    border-radius: 4px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

@media (max-width: 768px) {
    .lampiran-item {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .lampiran-item .btn-group {
        margin-top: 10px;
        margin-left: 0 !important;
        width: 100%;
    }
    
    .lampiran-item .btn-group .btn {
        flex: 1;
    }
}
</style>
`;

                            // Append CSS jika belum ada
                            if (!$('#single-lampiran-styles').length) {
                                $('head').append(singleLampiranCSS);
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
                                $('#show_tanggal_respon').text(formatTanggal(aspirasi.tanggal_respon));
                                $('#show_response_container').show();
                                $('#show_response_empty').hide();
                            } else {
                                $('#show_response_container').hide();
                                $('#show_response_empty').show();
                            }

                            // Dipakai form Tanggapi agar terisi status & tanggapan yang sudah ada.
                            $('#btnUpdateStatus').data({
                                id: aspirasi.id,
                                ticket: aspirasi.nomor_tiket,
                                status: aspirasi.status,
                                tanggapan: aspirasi.tanggapan_admin || ''
                            });

                            $('#modalLoadingState').hide();
                            $('#modalContentState').show();
                            $('#modalFooter').show();
                        }
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
                $('#status_ticket').text($(this).data('ticket') || '');
                $('#status').val($(this).data('status') || 'pending');
                $('#tanggapan_admin').val($(this).data('tanggapan') || '');
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
                    '<i class="mdi mdi-loading mdi-spin"></i> Menyimpan...');

                const formData = {
                    status: $('#status').val(),
                    tanggapan_admin: $('#tanggapan_admin').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $.ajax({
                    url: "{{ route('aspirasi.updateStatus', ['aspirasi' => '__ID__']) }}".replace(
                        '__ID__', id),
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
                            '<i class="mdi mdi-send"></i> Simpan &amp; Kirim');
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
                            url: "{{ route('aspirasi.destroy', ['aspirasi' => '__ID__']) }}"
                                .replace('__ID__', id),
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
