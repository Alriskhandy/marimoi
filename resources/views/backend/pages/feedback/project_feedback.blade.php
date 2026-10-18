@extends('backend.partials.main', ['title' => 'Feedback Peta'])

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-comment-text-multiple"></i>
            </span>
            Feedback Peta
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Feedback Peta</li>
            </ul>
        </nav>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 stats-row-compact">
        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Total Feedback</p>
                            <h3 class="stat-value">{{ $stats['total'] ?? 0 }}</h3>
                        </div>
                        <i class="mdi mdi-comment-text-multiple stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Pending</p>
                            <h3 class="stat-value">{{ $stats['pending'] ?? 0 }}</h3>
                        </div>
                        <i class="mdi mdi-clock-outline stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Dalam Proses</p>
                            <h3 class="stat-value">{{ ($stats['ditinjau'] ?? 0) + ($stats['ditindaklanjuti'] ?? 0) }}</h3>
                        </div>
                        <i class="mdi mdi-cog-outline stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Selesai</p>
                            <h3 class="stat-value">{{ $stats['selesai'] ?? 0 }}</h3>
                        </div>
                        <i class="mdi mdi-check-circle stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">Daftar Feedback</h4>
                        @can('project-feedbacks.respond')
                            <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                data-bs-target="#addModal">
                                <i class="mdi mdi-plus"></i> Tambah Feedback
                            </button>
                        @endcan
                    </div>

                    <!-- Filter: dikirim ke server supaya berlaku untuk semua halaman, bukan cuma baris yang tampil -->
                    <form method="GET" action="{{ route('project-feedbacks.index') }}" id="feedbackFilterForm"
                        class="mb-4">
                        <div class="row g-3 align-items-end filter-toolbar mb-3">
                            <div class="col-lg-3 col-md-4">
                                <label for="statusFilter" class="form-label fw-semibold mb-1">
                                    <i class="mdi mdi-flag me-1"></i>Status
                                </label>
                                <select class="form-select filter-control auto-submit" id="statusFilter" name="status">
                                    <option value="">Semua Status</option>
                                    @foreach (['pending' => 'Pending', 'ditinjau' => 'Ditinjau', 'ditindaklanjuti' => 'Ditindaklanjuti', 'selesai' => 'Selesai'] as $value => $label)
                                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-4">
                                <label for="jenisFilter" class="form-label fw-semibold mb-1">
                                    <i class="mdi mdi-shape-outline me-1"></i>Jenis
                                </label>
                                <select class="form-select filter-control auto-submit" id="jenisFilter" name="jenis">
                                    <option value="">Semua Jenis</option>
                                    @foreach (['keluhan' => 'Keluhan', 'saran' => 'Saran', 'apresiasi' => 'Apresiasi', 'pertanyaan' => 'Pertanyaan'] as $value => $label)
                                        <option value="{{ $value }}" @selected(request('jenis') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-4">
                                <label for="kabupatenFilter" class="form-label fw-semibold mb-1">
                                    <i class="mdi mdi-map-marker me-1"></i>Kabupaten/Kota
                                </label>
                                <select class="form-select filter-control auto-submit" id="kabupatenFilter"
                                    name="kabupaten_kota">
                                    <option value="">Semua Kabupaten/Kota</option>
                                    @foreach ($kabupaten_list as $kab)
                                        <option value="{{ $kab }}" @selected(request('kabupaten_kota') === $kab)>{{ $kab }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if (in_array($userRole, ['super-admin', 'admin-bappeda']))
                                <div class="col-lg-3 col-md-4">
                                    <label for="opdFilter" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-domain me-1"></i>OPD
                                    </label>
                                    <select class="form-select filter-control auto-submit" id="opdFilter" name="opd_id">
                                        <option value="">Semua OPD</option>
                                        @foreach ($opd as $opdItem)
                                            <option value="{{ $opdItem->id }}" @selected((string) request('opd_id') === (string) $opdItem->id)>
                                                {{ $opdItem->singkatan ?: $opdItem->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        <div class="row g-3 align-items-end justify-content-between filter-toolbar">
                            <div class="col-lg-6 col-md-6">
                                <label for="searchInput" class="form-label fw-semibold mb-1">
                                    <i class="mdi mdi-magnify me-1"></i>Cari Feedback
                                </label>
                                <div class="input-group filter-input-group">
                                    <input type="text" class="form-control filter-control" id="searchInput"
                                        name="search" value="{{ request('search') }}"
                                        placeholder="Nama, email, proyek, atau isi tanggapan...">
                                    <button class="btn btn-md btn-primary filter-btn" type="submit">
                                        <i class="mdi mdi-magnify"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-3">
                                <label for="per_page" class="form-label fw-semibold mb-1">
                                    <i class="mdi mdi-table-row me-1"></i>Per halaman
                                </label>
                                <select class="form-select filter-control auto-submit" id="per_page" name="per_page">
                                    @foreach ([10, 25, 50, 100] as $size)
                                        <option value="{{ $size }}" @selected($feedbacks->perPage() === $size)>{{ $size }} data</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-3">
                                <a href="{{ route('project-feedbacks.index') }}" class="btn btn-outline-secondary w-100"
                                    title="Reset semua filter">
                                    <i class="mdi mdi-filter-remove"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <div class="table-responsive">
                        <table class="table table-striped" id="feedbackTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 40px;">No</th>
                                    <th>Pemberi</th>
                                    <th class="d-none d-md-table-cell">Proyek Terkait</th>
                                    <th class="d-none d-lg-table-cell">OPD Penanggung Jawab</th>
                                    <th class="text-center">Jenis</th>
                                    <th class="text-center">Status</th>
                                    <th class="d-none d-md-table-cell text-center">Tanggal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($feedbacks as $index => $feedback)
                                    @php
                                        $jenisBadge = match (strtolower($feedback->jenis_tanggapan)) {
                                            'keluhan' => 'danger',
                                            'saran' => 'info',
                                            'apresiasi' => 'success',
                                            'pertanyaan' => 'warning',
                                            default => 'secondary',
                                        };
                                        $statusBadge = match (strtolower($feedback->status)) {
                                            'pending' => 'warning',
                                            'ditinjau' => 'info',
                                            'ditindaklanjuti' => 'primary',
                                            'selesai' => 'success',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr data-id="{{ $feedback->id }}">
                                        <td class="text-center">{{ $feedbacks->firstItem() + $index }}</td>
                                        <td>
                                            <span class="text-dark fw-bold">{{ $feedback->nama_pemberi_aspirasi }}</span>
                                            @if ($feedback->email)
                                                <br><small class="text-muted">{{ $feedback->email }}</small>
                                            @endif
                                            <div class="d-md-none mt-1">
                                                <small class="text-muted">{{ Str::limit($feedback->nama_proyek, 40) }}</small>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <span class="text-dark">{{ Str::limit($feedback->nama_proyek, 60) }}</span>
                                            <br><small class="text-muted">{{ Str::limit($feedback->tanggapan, 60) }}</small>
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            @if ($feedback->opd)
                                                <div class="d-flex align-items-center gap-2">
                                                    <small class="text-muted" title="{{ $feedback->opd->name }}">
                                                        {{ $feedback->opd->singkatan ?: Str::limit($feedback->opd->name, 30) }}
                                                    </small>
                                                    @if (in_array($userRole, ['super-admin', 'admin-bappeda']) && auth()->user()->can('project-feedbacks.respond'))
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-change-opd"
                                                            data-id="{{ $feedback->id }}"
                                                            data-current-opd="{{ $feedback->opd_id }}" title="Ubah OPD">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                <select class="form-select form-select-sm opd-select"
                                                    data-feedback-id="{{ $feedback->id }}"
                                                    onchange="updateOpdFeedback({{ $feedback->id }}, this.value)">
                                                    <option value="">Pilih OPD</option>
                                                    @foreach ($opd as $opdItem)
                                                        <option value="{{ $opdItem->id }}">
                                                            {{ $opdItem->singkatan ?: $opdItem->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $jenisBadge }} text-white">{{ ucfirst($feedback->jenis_tanggapan) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $statusBadge }} text-white">{{ ucfirst($feedback->status) }}</span>
                                        </td>
                                        <td class="d-none d-md-table-cell text-center">
                                            <small class="text-muted">{{ $feedback->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-show"
                                                    data-id="{{ $feedback->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#showModal" title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                @can('project-feedbacks.delete')
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                                        style="margin-left:-1px;" data-id="{{ $feedback->id }}"
                                                        onclick="deleteFeedback({{ $feedback->id }})" title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-data-row">
                                        <td colspan="8" class="text-center py-4">
                                            <i class="mdi mdi-comment-off-outline mdi-48px text-muted"></i>
                                            @if (request()->hasAny(['status', 'jenis', 'kabupaten_kota', 'opd_id', 'search']))
                                                <h5 class="text-muted mt-2">Tidak ada feedback yang cocok dengan filter</h5>
                                                <p class="text-muted">Ubah atau reset filter untuk melihat data lain</p>
                                            @else
                                                <h5 class="text-muted mt-2">Belum ada feedback</h5>
                                                <p class="text-muted">Feedback masyarakat dari halaman detail peta akan muncul di sini</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                        <!-- Pagination -->
                        @if ($feedbacks->total() > 0)
                            <div class="row mt-3 align-items-center">
                                <div class="col-sm-12 col-md-5">
                                    <small class="text-muted">
                                        Menampilkan {{ $feedbacks->firstItem() }} sampai {{ $feedbacks->lastItem() }}
                                        dari {{ $feedbacks->total() }} Feedback
                                    </small>
                                </div>
                                <div class="col-sm-12 col-md-7 d-flex justify-content-md-end">
                                    {{ $feedbacks->appends(request()->query())->links('pagination::bootstrap-4') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Ubah OPD Modal -->
        <div class="modal fade" id="changeOpdModal" tabindex="-1" aria-labelledby="changeOpdModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeOpdModalLabel">
                            <i class="mdi mdi-domain"></i> Ubah OPD Penanggung Jawab
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <form id="changeOpdForm">
                            <label for="opdSelect" class="form-label fw-semibold">OPD <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="opdSelect" name="opd_id" required>
                                <option value="">-- Pilih OPD --</option>
                                @foreach ($opd->sortBy('singkatan') as $opdItem)
                                    <option value="{{ $opdItem->id }}">
                                        {{ $opdItem->singkatan ? $opdItem->singkatan . ' - ' : '' }}{{ $opdItem->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Feedback akan tampil di daftar OPD yang dipilih untuk ditindaklanjuti.
                            </div>
                            <input type="hidden" id="feedbackId" name="feedback_id">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary btn-gradient-primary" onclick="saveOpdChange()">
                            <i class="mdi mdi-content-save"></i> Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tambah Feedback Modal -->
        <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">
                            <i class="mdi mdi-comment-plus-outline"></i> Tambah Feedback
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form id="addForm" action="{{ route('project-feedbacks.store') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <p class="form-section-label"><i class="mdi mdi-map-outline"></i> Data spasial</p>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label for="data_spatial_id" class="form-label fw-semibold">Data Spasial <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" id="data_spatial_id" name="data_spatial_id"
                                        data-search-url="{{ route('project-feedbacks.data-spatial') }}" required>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                    <div class="form-text">Ketik untuk mencari data spasial yang ditanggapi.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_nama_proyek" class="form-label fw-semibold">Nama Proyek <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_nama_proyek" name="nama_proyek"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label for="add_kabupaten_kota" class="form-label fw-semibold">Kabupaten/Kota <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="add_kabupaten_kota" name="kabupaten_kota" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach ($kabupaten_list as $kab)
                                            <option value="{{ $kab }}">{{ $kab }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label for="add_kecamatan" class="form-label fw-semibold">Kecamatan</label>
                                    <select class="form-select" id="add_kecamatan" name="kecamatan">
                                        <option value="">-- Pilih --</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <p class="form-section-label"><i class="mdi mdi-account-outline"></i> Pemberi feedback</p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="add_nama_pemberi_aspirasi" class="form-label fw-semibold">Nama <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_nama_pemberi_aspirasi"
                                        name="nama_pemberi_aspirasi" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="add_email" class="form-label fw-semibold">Email</label>
                                    <input type="email" class="form-control" id="add_email" name="email"
                                        placeholder="nama@email.com">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="add_phone" class="form-label fw-semibold">No. Telepon</label>
                                    <input type="text" class="form-control" id="add_phone" name="phone"
                                        placeholder="08xxxxxxxxxx">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <p class="form-section-label"><i class="mdi mdi-message-text-outline"></i> Isi feedback</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="add_jenis_tanggapan" class="form-label fw-semibold">Jenis <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="add_jenis_tanggapan" name="jenis_tanggapan"
                                        required>
                                        <option value="">-- Pilih Jenis --</option>
                                        <option value="keluhan">Keluhan</option>
                                        <option value="saran">Saran</option>
                                        <option value="apresiasi">Apresiasi</option>
                                        <option value="pertanyaan">Pertanyaan</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_laporan_gambar" class="form-label fw-semibold">Foto Laporan</label>
                                    <input type="file" class="form-control" id="add_laporan_gambar"
                                        name="laporan_gambar" accept="image/*">
                                    <div class="form-text">Maksimal 2MB (JPG, PNG, GIF)</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12">
                                    <label for="add_tanggapan" class="form-label fw-semibold">Pesan <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" id="add_tanggapan" name="tanggapan" rows="4" required
                                        placeholder="Isi keluhan, saran, apresiasi, atau pertanyaan warga..."></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_latitude" class="form-label fw-semibold">Latitude</label>
                                    <input type="number" step="any" class="form-control" id="add_latitude"
                                        name="latitude" placeholder="0.7881">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_longitude" class="form-label fw-semibold">Longitude</label>
                                    <input type="number" step="any" class="form-control" id="add_longitude"
                                        name="longitude" placeholder="127.3781">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-gradient-primary">
                                <i class="mdi mdi-content-save"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Detail Feedback Modal -->
        <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="showModalLabel">
                            <i class="mdi mdi-comment-text-outline"></i> Detail Feedback
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div id="show_loading" class="modal-body text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Memuat...</span>
                        </div>
                        <p class="text-muted mt-3 mb-0">Memuat detail feedback...</p>
                    </div>

                    <div id="show_content" class="modal-body" style="display: none;">
                        <div class="detail-summary d-flex flex-wrap align-items-center gap-2 mb-3">
                            <span id="show_kode" class="ticket-code fs-6"></span>
                            <span id="show_status" class="badge"></span>
                            <span id="show_jenis_tanggapan" class="badge"></span>
                            <span class="text-muted small ms-auto">
                                <i class="mdi mdi-calendar"></i> Dikirim <span id="show_created_at"></span>
                            </span>
                        </div>

                        <div class="row g-4">
                            <div class="col-lg-8">
                                <h4 id="show_nama_proyek" class="detail-title mb-1"></h4>
                                <p class="text-muted small mb-3">
                                    <i class="mdi mdi-map-marker-outline"></i>
                                    <span id="show_kecamatan"></span>, <span id="show_kabupaten_kota"></span>
                                </p>
                                <div id="show_tanggapan" class="detail-text"></div>

                                <div id="show_gambar_container" class="detail-section" style="display: none;">
                                    <p class="detail-section-label"><i class="mdi mdi-image-outline"></i> Foto laporan</p>
                                    <div id="show_gambar_image_wrap">
                                        <img id="show_laporan_gambar" src="" alt="Foto laporan"
                                            class="detail-photo" title="Klik untuk memperbesar">
                                        <div class="mt-2">
                                            <a id="download_gambar" href="#" class="btn btn-sm btn-outline-primary"
                                                download>
                                                <i class="mdi mdi-download"></i> Unduh
                                            </a>
                                        </div>
                                    </div>
                                    <div id="show_gambar_file_wrap" style="display: none;"></div>
                                </div>

                                <div id="show_koordinat_row" class="detail-section" style="display: none;">
                                    <p class="detail-section-label"><i class="mdi mdi-crosshairs-gps"></i> Lokasi pemberi
                                        feedback</p>
                                    <a id="openMapBtn" class="btn btn-sm btn-outline-success" target="_blank"
                                        rel="noopener noreferrer">
                                        <i class="mdi mdi-google-maps"></i> Buka di Google Maps
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1"
                                        id="copyCoordinates">
                                        <i class="mdi mdi-content-copy"></i> Salin Koordinat
                                    </button>
                                </div>

                                <div class="detail-section">
                                    <p class="detail-section-label"><i class="mdi mdi-reply"></i> Tanggapan petugas</p>
                                    <div id="show_response_container" style="display: none;">
                                        <div id="show_response_admin" class="detail-text detail-response"></div>
                                        <small class="text-muted">
                                            <i class="mdi mdi-clock-outline"></i> Ditanggapi
                                            <span id="show_responded_at"></span>
                                        </small>
                                    </div>
                                    <p id="show_response_empty" class="text-muted small mb-0">
                                        Belum ada tanggapan. Klik <b>Tanggapi</b> untuk mengubah status dan membalas warga.
                                    </p>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="detail-side">
                                    <p class="detail-section-label"><i class="mdi mdi-account"></i> Pemberi feedback</p>
                                    <dl class="detail-list">
                                        <dt>Nama</dt>
                                        <dd id="show_nama_pemberi_aspirasi"></dd>
                                        <dt>Email</dt>
                                        <dd id="show_email" class="text-break"></dd>
                                        <dt>No. Telepon</dt>
                                        <dd id="show_phone"></dd>
                                        <dt>OPD penanggung jawab</dt>
                                        <dd id="show_opd"></dd>
                                    </dl>

                                    <div id="show_project_info" style="display: none;">
                                        <p class="detail-section-label mt-3"><i class="mdi mdi-map-outline"></i> Data
                                            spasial terkait</p>
                                        <dl class="detail-list mb-0">
                                            <dt>Kode</dt>
                                            <dd id="show_project_uuid" class="font-monospace small text-break"></dd>
                                            <dt>Tipe data</dt>
                                            <dd><span id="show_project_type"></span> <span id="show_project_subtype"
                                                    class="text-muted"></span></dd>
                                            <dt>Tahun</dt>
                                            <dd id="show_project_year"></dd>
                                            <dt>Deskripsi</dt>
                                            <dd id="show_project_description" class="detail-text small"></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        @can('project-feedbacks.respond')
                            <button type="button" class="btn btn-gradient-primary btn-respond" id="btnRespond" disabled>
                                <i class="mdi mdi-reply"></i> Tanggapi
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <!-- Tanggapi Modal -->
        <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="responseModalLabel">
                            <i class="mdi mdi-reply"></i> Tanggapi Feedback
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form id="responseForm" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="response_id" name="id">
                        <div class="modal-body">
                            <p class="text-muted small">Tiket <span id="response_ticket" class="ticket-code"></span></p>

                            <div class="mb-3">
                                <label for="response_status" class="form-label fw-semibold">Status <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="response_status" name="status" required>
                                    <option value="ditinjau">Ditinjau</option>
                                    <option value="ditindaklanjuti">Ditindaklanjuti</option>
                                    <option value="selesai">Selesai</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="response_admin" class="form-label fw-semibold">Tanggapan untuk warga <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="response_admin" name="response_admin" rows="5"
                                    required minlength="10"
                                    placeholder="Tuliskan tindak lanjut atau jawaban untuk pemberi feedback (minimal 10 karakter)..."></textarea>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="alert alert-light border small mb-0">
                                <i class="mdi mdi-email-outline me-1"></i>
                                Bila status <b>Ditindaklanjuti</b> atau <b>Selesai</b>, pemberi feedback menerima email
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
    @endsection

    @section('scripts')
        <link rel="stylesheet" href="{{ asset('backend/assets/vendors/select2/select2.min.css') }}">
        <script src="{{ asset('backend/assets/vendors/select2/select2.min.js') }}"></script>
        <script>
            $(document).ready(function() {

                // Data kecamatan untuk setiap kabupaten di Maluku Utara
                const kecamatanData = {
                    'Halmahera Barat': ['Jailolo', 'Jailolo Selatan', 'Loloda', 'Sahu', 'Sahu Timur', 'Ibu',
                        'Ibu Utara', 'Ibu Selatan'
                    ],
                    'Halmahera Tengah': ['Weda', 'Weda Selatan', 'Weda Utara', 'Weda Tengah', 'Patani',
                        'Patani Utara', 'Kobe'
                    ],
                    'Halmahera Timur': ['Maba', 'Maba Selatan', 'Maba Utara', 'Wasile', 'Wasile Selatan',
                        'Wasile Timur', 'Wasile Tengah', 'Buli'
                    ],
                    'Halmahera Selatan': ['Kayoa', 'Kayoa Barat', 'Kayoa Selatan', 'Kayoa Utara', 'Bacan',
                        'Bacan Barat', 'Bacan Selatan', 'Bacan Timur', 'Bacan Barat Utara', 'Makian',
                        'Makian Barat', 'Obi', 'Obi Selatan', 'Obi Utara', 'Obi Barat'
                    ],
                    'Halmahera Utara': ['Tobelo', 'Tobelo Selatan', 'Tobelo Utara', 'Tobelo Tengah', 'Tobelo Timur',
                        'Tobelo Barat', 'Galela', 'Galela Barat', 'Galela Utara', 'Galela Selatan',
                        'Loloda Utara', 'Kao', 'Kao Utara', 'Kao Barat', 'Kao Teluk', 'Malifut'
                    ],
                    'Kepulauan Sula': ['Sula Besi Barat', 'Sula Besi Tengah', 'Sula Besi Timur',
                        'Sula Besi Selatan', 'Taliabu Timur', 'Taliabu Barat', 'Taliabu Utara',
                        'Taliabu Selatan', 'Mangole', 'Mangole Utara Timur', 'Sanana', 'Sulabesi Tengah'
                    ],
                    'Pulau Morotai': ['Morotai Selatan', 'Morotai Selatan Barat', 'Morotai Timur', 'Morotai Utara',
                        'Morotai Jaya'
                    ],
                    'Ternate': ['Ternate Selatan', 'Ternate Tengah', 'Ternate Utara', 'Ternate Barat',
                        'Pulau Ternate', 'Moti', 'Pulau Batang Dua', 'Pulau Hiri'
                    ],
                    'Tidore Kepulauan': ['Tidore', 'Tidore Selatan', 'Tidore Timur', 'Tidore Utara', 'Oba',
                        'Oba Selatan', 'Oba Utara', 'Oba Tengah'
                    ]
                };

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

                const STATUS_LABELS = {
                    'pending': 'Menunggu',
                    'ditinjau': 'Ditinjau',
                    'ditindaklanjuti': 'Ditindaklanjuti',
                    'selesai': 'Selesai'
                };

                function formatTanggal(value) {
                    return value ? new Date(value).toLocaleString('id-ID', {
                        dateStyle: 'long',
                        timeStyle: 'short'
                    }) : '-';
                }

                function getStatusClass(status) {
                    const statusClasses = {
                        'pending': 'warning',
                        'ditinjau': 'info',
                        'ditindaklanjuti': 'primary',
                        'selesai': 'success'
                    };
                    return statusClasses[status] || 'secondary';
                }

                function getJenisClass(jenis) {
                    const jenisClasses = {
                        'keluhan': 'danger',
                        'saran': 'info',
                        'apresiasi': 'success',
                        'pertanyaan': 'warning'
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

                // Load Kecamatan based on Kabupaten selection
                $('#add_kabupaten_kota').on('change', function() {
                    const kabupaten = $(this).val();
                    const kecamatanSelect = $('#add_kecamatan');

                    kecamatanSelect.empty().append('<option value="">-- Pilih Kecamatan --</option>');

                    if (kabupaten && kecamatanData[kabupaten]) {
                        kecamatanData[kabupaten].forEach(function(kecamatan) {
                            kecamatanSelect.append(
                                `<option value="${kecamatan}">${kecamatan}</option>`);
                        });
                    }
                });

                // Filter dikirim ke server; select langsung menerapkan filter saat berubah.
                $('#feedbackFilterForm .auto-submit').on('change', function() {
                    $('#feedbackFilterForm').trigger('submit');
                });

                // Add Modal
                $('#addModal').on('show.bs.modal', function() {
                    const form = $('#addForm');
                    form[0].reset();
                    clearFormErrors(form);
                    $('#add_kecamatan').empty().append('<option value="">-- Pilih Kecamatan --</option>');
                    $('#data_spatial_id').val(null).trigger('change');
                });

                // Pilih data spasial lewat pencarian (jumlahnya puluhan ribu, tidak dimuat sekaligus)
                const dataSpatialSelect = $('#data_spatial_id');
                dataSpatialSelect.select2({
                    width: '100%',
                    dropdownParent: $('#addModal'),
                    placeholder: '-- Cari data spasial --',
                    minimumInputLength: 2,
                    ajax: {
                        url: dataSpatialSelect.data('search-url'),
                        dataType: 'json',
                        delay: 300,
                        data: params => ({ search: params.term, page: params.page || 1 }),
                    },
                    language: {
                        inputTooShort: () => 'Ketik minimal 2 huruf',
                        noResults: () => 'Data spasial tidak ditemukan',
                        searching: () => 'Mencari...',
                    },
                });

                dataSpatialSelect.on('select2:select', function(e) {
                    $('#add_nama_proyek').val(e.params.data.nama_proyek || e.params.data.text);
                });

                // Add Form Submit
                $('#addForm').on('submit', function(e) {
                    e.preventDefault();
                    const form = $(this);
                    const formData = new FormData(this);

                    // Disable submit button to prevent double submission
                    const submitBtn = form.find('button[type="submit"]');
                    submitBtn.prop('disabled', true).html(
                        '<i class="mdi mdi-loading mdi-spin"></i> Menyimpan...');

                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.status === 'success') {
                                $('#addModal').modal('hide');
                                showAlert(response.message);
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                showFormErrors(form, xhr.responseJSON.errors);
                            } else {
                                showAlert('Terjadi kesalahan server: ' + (xhr.responseJSON
                                    ?.message || 'Unknown error'), 'error');
                            }
                        },
                        complete: function() {
                            // Re-enable submit button
                            submitBtn.prop('disabled', false).html(
                                '<i class="mdi mdi-content-save"></i> Simpan');
                        }
                    });
                });

                // Show Modal - Load data via AJAX
                $(document).on('click', '.btn-show', function() {
                    const id = $(this).data('id');

                    // Modal terbuka lewat data-bs-toggle sebelum data siap: tampilkan status memuat
                    // supaya isi feedback sebelumnya tidak sempat terlihat.
                    $('#show_loading').html(`
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Memuat...</span></div>
                        <p class="text-muted mt-3 mb-0">Memuat detail feedback...</p>`).show();
                    $('#show_content').hide();
                    $('#btnRespond').prop('disabled', true);

                    const showUrl = `{{ route('project-feedbacks.index') }}/${id}`;

                    $.ajax({
                        url: showUrl,
                        type: 'GET',
                        success: function(data) {
                            if (data.status === 'success') {
                                const feedback = data.data;
                                $('#show_kode').text(data.kode || '');
                                $('#show_opd').text(feedback.opd ?
                                    `${feedback.opd.singkatan ? feedback.opd.singkatan + ' - ' : ''}${feedback.opd.name}` :
                                    'Belum ditentukan');

                                // Basic info
                                $('#show_nama_pemberi_aspirasi').text(feedback
                                    .nama_pemberi_aspirasi);
                                $('#show_nama_proyek').text(feedback.nama_proyek);
                                $('#show_kabupaten_kota').text(feedback.kabupaten_kota);
                                $('#show_kecamatan').text(feedback.kecamatan || 'Kecamatan tidak diisi');
                                $('#show_email').text(feedback.email || '-');
                                $('#show_phone').text(feedback.phone || '-');
                                $('#show_tanggapan').text(feedback.tanggapan);
                                $('#show_created_at').text(formatTanggal(feedback.created_at));

                                // Enhanced Project info with UUID and description
                                if (feedback.data_spatial) {
                                    const project = feedback.data_spatial;

                                    $('#show_project_uuid').text(project.uuid || 'Tidak ada UUID');
                                    $('#show_project_type').text(project.data_type ? project
                                        .data_type.replace('_', ' ').toUpperCase() : '-');
                                    $('#show_project_subtype').text(project.sub_type ? project
                                        .sub_type.toUpperCase() : '-');
                                    $('#show_project_year').text(project.tahun || '-');
                                    $('#show_project_description').text(project.deskripsi ||
                                        'Tidak ada deskripsi');

                                    $('#show_project_info').show();
                                } else {
                                    $('#show_project_info').hide();
                                }

                                // Jenis tanggapan badge
                                const jenisClass = getJenisClass(feedback.jenis_tanggapan);
                                $('#show_jenis_tanggapan').removeClass().addClass(
                                        `badge bg-${jenisClass}`)
                                    .text(feedback.jenis_tanggapan.charAt(0).toUpperCase() +
                                        feedback.jenis_tanggapan.slice(1));

                                // Status badge
                                const statusClass = getStatusClass(feedback.status);
                                $('#show_status').removeClass().addClass(`badge bg-${statusClass}`)
                                    .text(STATUS_LABELS[feedback.status] || feedback.status);

                                // Koordinat
                                if (feedback.latitude && feedback.longitude) {
                                    $('#show_koordinat_row').show();

                                    const googleMapsUrl =
                                        `https://www.google.com/maps/search/?api=1&query=${feedback.latitude},${feedback.longitude}`;
                                    $('#openMapBtn').attr('href', googleMapsUrl);

                                    // Copy coordinates functionality
                                    $('#copyCoordinates').off('click').on('click', function() {
                                        const coordinates =
                                            `${feedback.latitude}, ${feedback.longitude}`;
                                        copyToClipboard(coordinates);
                                    });

                                } else {
                                    $('#show_koordinat_row').hide();
                                }

                                // Gambar
                                // Gambar - Handler untuk single file (string)
                                if (feedback.laporan_gambar && feedback.laporan_gambar.trim() !==
                                    '') {
                                    try {
                                        // laporan_gambar sekarang adalah string, bukan array
                                        const filename = feedback.laporan_gambar;

                                        // Buat path URL untuk gambar
                                        const imagePath = `/storage/aspirasi_lampiran/${filename}`;
                                        const downloadUrl =
                                            `/dashboard/project-feedbacks/${feedback.id}/download-image`;

                                        // Extract nama file tanpa timestamp untuk display
                                        const originalName = filename.replace(/^\d+_[a-f0-9]+\./,
                                            '');
                                        const displayName = originalName.length > 20 ? originalName
                                            .substring(0, 20) + '...' : originalName;

                                        const fileExt = filename.split('.').pop().toLowerCase();
                                        const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp',
                                            'webp'
                                        ];

                                        if (imageExts.includes(fileExt)) {
                                            // Untuk gambar, tampilkan preview dan tombol download
                                            $('#show_gambar_file_wrap').hide().empty();
                                            $('#show_gambar_image_wrap').show();
                                            $('#show_laporan_gambar').off('error').attr('src', imagePath).on(
                                                'error',
                                                function() {
                                                    // Jika direct path gagal, coba via controller
                                                    $(this).attr('src', downloadUrl);
                                                });

                                            // Update download link dengan filename yang benar
                                            $('#download_gambar')
                                                .attr('href', downloadUrl)
                                                .attr('download', originalName)
                                                .html(`<i class="mdi mdi-download"></i> Unduh ${$('<span>').text(displayName).html()}`)
                                                .off('click')
                                                .on('click', function(e) {
                                                    e.preventDefault();
                                                    downloadSingleImage(downloadUrl,
                                                        originalName, filename);
                                                });

                                            $('#show_gambar_container').show();
                                        } else {
                                            // Untuk file non-gambar, tampilkan info file dengan tombol download
                                            $('#show_gambar_image_wrap').hide();
                                            $('#show_gambar_container').show();
                                            $('#show_gambar_file_wrap').html(`
                <div>
                    <div class="p-3 border rounded bg-light">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-file-outline me-2 text-primary" style="font-size: 1.5rem;"></i>
                            <div class="flex-grow-1">
                                <div class="fw-bold">${displayName}</div>
                                <small class="text-muted">${fileExt.toUpperCase()}</small>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                    onclick="downloadSingleImage('${downloadUrl}', '${originalName}', '${filename}')">
                                <i class="mdi mdi-download"></i> Download
                            </button>
                        </div>
                    </div>
                </div>
            `).show();
                                        }

                                    } catch (error) {
                                        console.error('Error processing laporan_gambar:', error);
                                        $('#show_gambar_container').hide();
                                    }
                                } else {
                                    $('#show_gambar_container').hide();
                                }

                                // Function untuk download single image/file
                                function downloadSingleImage(downloadUrl, originalName, filename) {
                                    // Tampilkan loading state
                                    const loadingToast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true
                                    });

                                    loadingToast.fire({
                                        icon: 'info',
                                        title: `Mengunduh ${originalName}...`
                                    });

                                    // Coba download via fetch terlebih dahulu
                                    fetch(downloadUrl)
                                        .then(response => {
                                            if (response.ok) {
                                                return response.blob();
                                            } else {
                                                throw new Error('Controller download failed');
                                            }
                                        })
                                        .then(blob => {
                                            // Jika berhasil via controller, download blob
                                            const url = window.URL.createObjectURL(blob);
                                            const a = document.createElement('a');
                                            a.href = url;
                                            a.download = originalName;
                                            document.body.appendChild(a);
                                            a.click();
                                            document.body.removeChild(a);
                                            window.URL.revokeObjectURL(url);

                                            loadingToast.fire({
                                                icon: 'success',
                                                title: `${originalName} berhasil diunduh!`
                                            });
                                        })
                                        .catch(error => {
                                            console.log(
                                                'Controller download failed, trying direct URL...',
                                                error);

                                            // Fallback ke direct URL
                                            const directUrl =
                                                `/storage/aspirasi_lampiran/${filename}`;
                                            const link = document.createElement('a');
                                            link.href = directUrl;
                                            link.download = originalName;
                                            link.style.display = 'none';
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);

                                            loadingToast.fire({
                                                icon: 'success',
                                                title: `${originalName} berhasil diunduh (direct)!`
                                            });
                                        });
                                }

                                // Function untuk preview gambar dengan modal (jika diperlukan)
                                function previewSingleImage(imagePath, filename) {
                                    const modalHtml = `
        <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="mdi mdi-image me-2"></i>${filename}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center p-0" style="background: #000;">
                        <img src="${imagePath}" class="img-fluid" style="max-height: 70vh; max-width: 100%;" alt="${filename}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light" onclick="window.open('${imagePath}', '_blank')">
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
                                    $('#imagePreviewModal').remove();

                                    // Add and show modal
                                    $('body').append(modalHtml);
                                    const modal = new bootstrap.Modal(document.getElementById(
                                        'imagePreviewModal'));
                                    modal.show();

                                    // Clean up when modal is hidden
                                    $('#imagePreviewModal').on('hidden.bs.modal', function() {
                                        $(this).remove();
                                    });
                                }

                                // Optional: Tambahkan click handler untuk preview gambar
                                $(document).off('click.feedbackPreview').on('click.feedbackPreview', '#show_laporan_gambar', function() {
                                    const src = $(this).attr('src');
                                    const alt = $(this).attr('alt') || 'Laporan Gambar';
                                    if (src) {
                                        previewSingleImage(src, alt);
                                    }
                                });

                                // Response admin
                                if (feedback.response_admin) {
                                    $('#show_response_admin').text(feedback.response_admin);
                                    $('#show_responded_at').text(formatTanggal(feedback.responded_at));
                                    $('#show_response_container').show();
                                    $('#show_response_empty').hide();
                                } else {
                                    $('#show_response_container').hide();
                                    $('#show_response_empty').show();
                                }

                                // Dipakai form Tanggapi agar terisi status & tanggapan yang sudah ada.
                                $('#btnRespond').data({
                                    id: feedback.id,
                                    ticket: data.kode || '',
                                    status: feedback.status,
                                    response: feedback.response_admin || ''
                                }).prop('disabled', false);

                                $('#show_loading').hide();
                                $('#show_content').show();
                            }
                        },
                        error: function(xhr) {
                            console.error('Error loading feedback details:', xhr);
                            $('#show_loading').html(`
                                <i class="mdi mdi-alert-circle-outline text-danger" style="font-size: 2.5rem;"></i>
                                <p class="text-danger mt-2 mb-0">Gagal memuat detail feedback.</p>`).show();
                            showAlert('Gagal memuat data detail: ' + (xhr.responseJSON?.message ||
                                'Unknown error'), 'error');
                        }
                    });
                });

                // Response Modal
                $(document).on('click', '.btn-respond, #btnRespond', function() {
                    const id = $(this).data('id');
                    if (!id) {
                        showAlert('ID feedback tidak ditemukan', 'error');
                        return;
                    }

                    $('#response_id').val(id);
                    $('#responseForm')[0].reset();
                    clearFormErrors($('#responseForm'));
                    const currentStatus = $(this).data('status');
                    $('#response_ticket').text($(this).data('ticket') || '');
                    $('#response_status').val(['ditinjau', 'ditindaklanjuti', 'selesai'].includes(currentStatus) ?
                        currentStatus : 'ditinjau');
                    $('#response_admin').val($(this).data('response') || '');
                    $('#responseModal').modal('show');
                    $('#showModal').modal('hide');
                });

                // Response Form Submit
                $('#responseForm').on('submit', function(e) {
                    e.preventDefault();
                    const form = $(this);
                    const id = $('#response_id').val();

                    if (!id) {
                        showAlert('ID feedback tidak ditemukan', 'error');
                        return;
                    }

                    clearFormErrors(form);

                    const submitBtn = form.find('button[type="submit"]');
                    submitBtn.prop('disabled', true).html(
                        '<i class="mdi mdi-loading mdi-spin"></i> Mengirim...');

                    const formData = {
                        status: $('#response_status').val(),
                        response_admin: $('#response_admin').val(),
                        _token: $('meta[name="csrf-token"]').attr('content')
                    };

                    const respondUrl = `{{ route('project-feedbacks.index') }}/${id}/respond`;

                    $.ajax({
                        url: respondUrl,
                        type: 'POST',
                        data: formData,
                        headers: {
                            'X-HTTP-Method-Override': 'PUT'
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                $('#responseModal').modal('hide');

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
                            console.error('Response error:', xhr);
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
                // Custom SweetAlert dengan tema yang sesuai
                // Delete function dengan SweetAlert
                window.deleteFeedback = function(id) {
                    Swal.fire({
                        title: 'Yakin ingin menghapus?',
                        text: "Data feedback akan dihapus permanen!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, hapus!',
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

                            const deleteUrl = `{{ route('project-feedbacks.index') }}/${id}`;

                            $.ajax({
                                url: deleteUrl,
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

                                        // Muat ulang supaya nomor urut, statistik, dan pagination ikut terbarui.
                                        setTimeout(() => location.reload(), 1500);
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
            });
        </script>
        <script>
            // Function untuk update OPD langsung dari dropdown - TANPA RELOAD
            function updateOpdFeedback(feedbackId, opdId) {
                if (!opdId) return;

                // Show loading state
                const select = document.querySelector(`select[data-feedback-id="${feedbackId}"]`);
                const originalHtml = select.parentElement.innerHTML;
                select.parentElement.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';

                // AJAX call untuk update OPD menggunakan named route
                fetch(`{{ route('project-feedbacks.update-opd', ':id') }}`.replace(':id', feedbackId), {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            opd_id: opdId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update tampilan TANPA reload halaman
                            updateOpdDisplay(feedbackId, data.data.opd);

                            // Show success message tanpa reload
                            showSuccessToast('Diteruskan ke OPD: ' + data.data.opd.singkatan);
                        } else {
                            // Restore original content jika gagal
                            select.parentElement.innerHTML = originalHtml;
                            showErrorToast('Gagal mengupdate OPD: ' + data.message);
                        }
                    })
                    .catch(error => {
                        // Restore original content jika error
                        select.parentElement.innerHTML = originalHtml;
                        console.error('Error:', error);
                        showErrorToast('Terjadi kesalahan saat mengupdate OPD');
                    });
            }

            // Function untuk update tampilan OPD setelah berhasil
            function updateOpdDisplay(feedbackId, opdData) {
                const row = document.querySelector(`tr[data-id="${feedbackId}"]`);
                if (!row) return;

                const opdCell = row.querySelector('td.d-none.d-lg-table-cell');

                // Update desktop view
                if (opdCell) {
                    const logoHtml = opdData.logo ?
                        `<img src="{{ asset('storage/opd-logos/') }}/${opdData.logo}" 
                 alt="Logo ${opdData.singkatan}" 
                 class="rounded me-2" 
                 style="width: 24px; height: 24px; object-fit: cover;">` : '';

                    const singkatanHtml = opdData.singkatan ?
                        `<small class="text-muted">
                <i class="mdi mdi-tag-outline me-1"></i>${opdData.singkatan}
            </small>` : '';

                    opdCell.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center">
                        ${logoHtml}
                        <div>
                            <strong class="text-truncate d-block" style="max-width: 150px;">
                                ${opdData.name}
                            </strong>
                            ${singkatanHtml}
                        </div>
                    </div>
                </div>
@can('project-feedbacks.respond')
                <button type="button" class="btn btn-sm btn-outline-primary ms-2 btn-change-opd"
                        data-id="${feedbackId}" 
                        data-current-opd="${opdData.id}" 
                        title="Ubah OPD">
                    <i class="mdi mdi-pencil"></i>
                </button>
@endcan
            </div>
        `;
                }

                // Update mobile view
                const mobileOpdElement = row.querySelector('.d-md-none .text-muted:has(strong)');
                if (mobileOpdElement) {
                    const opdText = opdData.name.length > 25 ? opdData.name.substring(0, 25) + '...' : opdData.name;
                    // Cari dan update hanya bagian OPD
                    const opdLines = mobileOpdElement.querySelectorAll('br');
                    if (opdLines.length >= 1) {
                        // Update text setelah <br> kedua (bagian OPD)
                        const textNodes = mobileOpdElement.childNodes;
                        for (let i = 0; i < textNodes.length; i++) {
                            if (textNodes[i].nodeType === 3 && textNodes[i].textContent.includes('OPD:')) {
                                textNodes[i].textContent = `OPD: ${opdText}`;
                                break;
                            }
                        }
                    }
                }

                // Reattach event listener untuk tombol change OPD yang baru
                attachChangeOpdListeners();
            }

            // Function untuk save perubahan OPD dari modal - TANPA RELOAD
            function saveOpdChange() {
                const feedbackId = document.getElementById('feedbackId').value;
                const opdId = document.getElementById('opdSelect').value;

                if (!opdId) {
                    showErrorToast('Silakan Pilih OPD Yang Bertanggung Jawab terlebih dahulu');
                    return;
                }

                // Show loading state on button
                const saveBtn = document.querySelector('#changeOpdModal .btn-primary');
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Menyimpan...';
                saveBtn.disabled = true;

                // AJAX call untuk update OPD menggunakan named route
                fetch(`{{ route('project-feedbacks.update-opd', ':id') }}`.replace(':id', feedbackId), {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            opd_id: opdId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('changeOpdModal'));
                            modal.hide();

                            // Update tampilan TANPA reload
                            updateOpdDisplay(feedbackId, data.data.opd);

                            // Show success message
                            showSuccessToast('Diteruskan ke OPD: ' + data.data.opd.singkatan);
                        } else {
                            showErrorToast('Gagal mengupdate OPD: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showErrorToast('Terjadi kesalahan saat mengupdate OPD');
                    })
                    .finally(() => {
                        // Restore button state
                        saveBtn.innerHTML = originalText;
                        saveBtn.disabled = false;
                    });
            }

            // Function untuk attach event listeners pada tombol change OPD
            function attachChangeOpdListeners() {
                document.querySelectorAll('.btn-change-opd').forEach(button => {
                    // Remove existing listeners to prevent duplicates
                    button.removeEventListener('click', handleChangeOpdClick);
                    // Add new listener
                    button.addEventListener('click', handleChangeOpdClick);
                });
            }

            // Handler untuk click tombol change OPD
            function handleChangeOpdClick(event) {
                const feedbackId = this.getAttribute('data-id');
                const currentOpdId = this.getAttribute('data-current-opd');

                document.getElementById('feedbackId').value = feedbackId;
                document.getElementById('opdSelect').value = currentOpdId;

                const modal = new bootstrap.Modal(document.getElementById('changeOpdModal'));
                modal.show();
            }

            // Toast notification functions
            function showSuccessToast(message) {
                // Menggunakan SweetAlert Toast untuk notifikasi yang lebih baik
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                Toast.fire({
                    icon: 'success',
                    title: message
                });
            }

            function showErrorToast(message) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                Toast.fire({
                    icon: 'error',
                    title: message
                });
            }

            // Initialize event listeners when document is ready
            document.addEventListener('DOMContentLoaded', function() {
                // Attach initial listeners
                attachChangeOpdListeners();

                // Handle modal reset
                const changeOpdModal = document.getElementById('changeOpdModal');
                if (changeOpdModal) {
                    changeOpdModal.addEventListener('hidden.bs.modal', function() {
                        document.getElementById('changeOpdForm').reset();
                    });
                }
            });

            // Alternative menggunakan sistem alert yang sudah ada jika tidak menggunakan SweetAlert
            function showAlert(message, type = 'success') {
                const alertClass = type === 'success' ? 'alert-success' : (type === 'info' ? 'alert-info' : 'alert-danger');
                const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

                const alertContainer = document.getElementById('alertContainer');
                if (alertContainer) {
                    alertContainer.innerHTML = alertHtml;

                    setTimeout(function() {
                        const alert = alertContainer.querySelector('.alert');
                        if (alert) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, 5000);
                }
            }

            // Fallback functions jika tidak menggunakan SweetAlert
            function showSuccessToastFallback(message) {
                showAlert(message, 'success');
            }

            function showErrorToastFallback(message) {
                showAlert(message, 'error');
            }
        </script>
        <style>
            /* Disamakan dengan halaman Daftar Layer & Data (spatial-layers/index) */
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

            .opd-select {
                min-width: 140px;
            }

            /* Modal feedback — pola sama dengan modal detail Aspirasi */
            .ticket-code {
                font-family: 'Courier New', monospace;
                font-size: 0.75rem;
                color: #0d6efd;
                letter-spacing: 0.3px;
            }

            .form-section-label,
            .detail-section-label {
                margin-bottom: 0.5rem;
                font-size: 0.7rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #6b7280;
            }

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

            .detail-photo {
                display: block;
                max-width: 100%;
                max-height: 320px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                object-fit: contain;
                cursor: zoom-in;
            }
        </style>
    @endsection
