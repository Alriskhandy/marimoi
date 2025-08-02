@extends('backend.partials.main')

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-{{ $projectTypeInfo['color'] ?? 'primary' }} text-white me-2">
                <!-- Icon Marker -->
                <i class="fa fa-map-marker"></i>
            </span> {{ $projectTypeInfo['name'] ?? 'Tanggapan Masyarakat' }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('project-feedbacks.index') }}">Feedback</a>
                </li>
                @if ($type !== 'all')
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ $projectTypeInfo['name'] }}
                    </li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">
                        Semua
                    </li>
                @endif
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
                    <h6 class="card-text">Menunggu proses lanjutan</h6>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
            <div class="card bg-gradient-info card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Ditinjau
                        <i class="mdi mdi-eye-outline mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-4">{{ $stats['ditinjau'] ?? 0 }}</h2>
                    <h6 class="card-text">Sedang dalam peninjauan</h6>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
            <div class="card bg-gradient-primary card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Ditindaklanjuti
                        <i class="mdi mdi-cog-outline mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-4">{{ $stats['ditindaklanjuti'] ?? 0 }}</h2>
                    <h6 class="card-text">Dalam proses tindak lanjut</h6>
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
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">
                            <!-- Icon Marker -->
                            <i class="fa fa-map-marker"></i>
                            Daftar {{ $projectTypeInfo['name'] }}
                        </h4>
                        <div class="d-flex gap-2">
                            <!-- Filter Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown"
                                    data-bs-toggle="dropdown">
                                    <i class="mdi mdi-filter"></i> Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item filter-item" href="#" data-filter="all">Semua</a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <h6 class="dropdown-header">Status</h6>
                                    </li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="pending">Pending</a></li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="ditinjau">Ditinjau</a></li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="ditindaklanjuti">Ditindaklanjuti</a></li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="selesai">Selesai</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <h6 class="dropdown-header">Jenis</h6>
                                    </li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="keluhan">Keluhan</a></li>
                                    <li><a class="dropdown-item filter-item" href="#" data-filter="saran">Saran</a>
                                    </li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="apresiasi">Apresiasi</a></li>
                                    <li><a class="dropdown-item filter-item" href="#"
                                            data-filter="pertanyaan">Pertanyaan</a></li>
                                </ul>
                            </div>
                            {{-- <button type="button" class="btn btn-gradient-{{ $projectTypeInfo['color'] ?? 'primary' }}"
                                data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="mdi mdi-plus"></i> <span class="d-none d-sm-inline">Tambah Feedback</span>
                            </button> --}}
                            <button type="button" class="btn btn-warning w-100" id="resetFilter">
                                <i class="mdi mdi-refresh"></i> Reset Filter
                            </button>
                        </div>
                    </div>

                    <!-- Search Box -->
                    <div class="row mb-3">
                        <div class="col-lg-6 col-md-12 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                <input type="text" class="form-control" id="searchInput"
                                    placeholder="Cari berdasarkan nama, proyek, atau tanggapan...">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-map-marker"></i></span>
                                <select class="form-control" id="kabupatenFilter">
                                    <option value="">Semua Kabupaten</option>
                                    @foreach ($kabupaten_list as $kab)
                                        <option value="{{ $kab }}">{{ $kab }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>


                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <!-- Responsive Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="feedbackTable">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Nama Pemberi</th>
                                    <th class="d-none d-md-table-cell">Proyek Terkait</th>
                                    {{-- <th class="d-none d-lg-table-cell">Tanggapan</th> --}}
                                    <th class="text-center">Jenis</th>
                                    <th class="text-center">Status</th>
                                    <th class="d-none d-md-table-cell text-center">Tanggal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($feedbacks as $index => $feedback)
                                    <tr data-id="{{ $feedback->id }}" data-status="{{ $feedback->status }}"
                                        data-jenis="{{ $feedback->jenis_tanggapan }}"
                                        data-kabupaten="{{ $feedback->kabupaten_kota }}"
                                        data-search="{{ strtolower($feedback->nama_pemberi_aspirasi . ' ' . $feedback->nama_proyek . ' ' . $feedback->tanggapan) }}">
                                        <td class="text-center">{{ $feedbacks->firstItem() + $index }}</td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong class="text-truncate"
                                                    style="max-width: 150px;">{{ $feedback->nama_pemberi_aspirasi }}</strong>
                                                @if ($feedback->email)
                                                    <small class="text-muted text-truncate"
                                                        style="max-width: 150px;">{{ $feedback->email }}</small>
                                                @endif
                                                <!-- Mobile view: Show project and tanggapan on small screens -->
                                                <div class="d-md-none mt-1">
                                                    <small class="text-muted">
                                                        <strong>Proyek:</strong>
                                                        {{ Str::limit($feedback->nama_proyek, 30) }}
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <strong>Tanggapan:</strong>
                                                        {{ Str::limit($feedback->tanggapan, 50) }}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <div class="d-flex flex-column">
                                                <strong class="text-truncate"
                                                    style="max-width: 200px;">{{ $feedback->nama_proyek }}</strong>
                                                {{-- @if ($feedback->dataSpatial)
                                                    <small class="text-muted">
                                                        <span class="badge badge-outline-{{ $projectTypeInfo['color'] }}">
                                                            {{ ucwords(str_replace('_', ' ', $feedback->dataSpatial->data_type ?? '')) }}
                                                        </span>
                                                    </small>
                                                    @if ($feedback->dataSpatial->uuid)
                                                        <small class="text-muted">
                                                            <i
                                                                class="mdi mdi-identifier me-1"></i>{{ Str::limit($feedback->dataSpatial->uuid, 8) }}...
                                                        </small>
                                                    @endif
                                                @endif --}}
                                            </div>
                                        </td>
                                        {{-- <td class="d-none d-lg-table-cell">
                                            <div class="text-truncate" style="max-width: 250px;"
                                                title="{{ $feedback->tanggapan }}">
                                                {{ Str::limit($feedback->tanggapan, 100) }}
                                            </div>
                                        </td> --}}
                                        <td class="text-center">
                                            @php
                                                $jenis = strtolower($feedback->jenis_tanggapan);
                                                $jenisBadge = match ($jenis) {
                                                    'keluhan' => 'danger',
                                                    'saran' => 'info',
                                                    'apresiasi' => 'success',
                                                    'pertanyaan' => 'warning',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $jenisBadge }} d-none d-sm-inline">
                                                {{ ucfirst($feedback->jenis_tanggapan) }}
                                            </span>
                                            <!-- Mobile badge with shorter text -->
                                            <span class="badge bg-{{ $jenisBadge }} d-sm-none">
                                                {{ substr(ucfirst($feedback->jenis_tanggapan), 0, 1) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusBadge = match (strtolower($feedback->status)) {
                                                    'pending' => 'warning',
                                                    'ditinjau' => 'info',
                                                    'ditindaklanjuti' => 'primary',
                                                    'selesai' => 'success',
                                                    default => 'light',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusBadge }} d-none d-sm-inline">
                                                {{ ucfirst($feedback->status) }}
                                            </span>
                                            <!-- Mobile badge with icon -->
                                            <span class="badge bg-{{ $statusBadge }} d-sm-none">
                                                @switch($feedback->status)
                                                    @case('pending')
                                                        <i class="mdi mdi-clock-outline"></i>
                                                    @break

                                                    @case('ditinjau')
                                                        <i class="mdi mdi-eye-outline"></i>
                                                    @break

                                                    @case('ditindaklanjuti')
                                                        <i class="mdi mdi-cog-outline"></i>
                                                    @break

                                                    @case('selesai')
                                                        <i class="mdi mdi-check-circle-outline"></i>
                                                    @break
                                                @endswitch
                                            </span>
                                        </td>
                                        <td class="d-none d-md-table-cell text-center">
                                            <small>{{ $feedback->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show"
                                                    data-id="{{ $feedback->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#showModal" title="Lihat Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                                    data-id="{{ $feedback->id }}"
                                                    onclick="deleteFeedback({{ $feedback->id }})" title="Hapus">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                        <tr id="no-data-row">
                                            <td colspan="8" class="text-center">
                                                <div class="py-4">
                                                    <!-- Icon Marker -->
                                                    <i class="fa fa-map-marker"></i>
                                                    <p class="text-muted mt-2">
                                                        Tidak ada feedback untuk {{ $projectTypeInfo['name'] }}
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if ($feedbacks->hasPages())
                            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                                <div class="mb-2 mb-md-0">
                                    <span class="text-muted">
                                        Menampilkan {{ $feedbacks->firstItem() ?? 0 }} - {{ $feedbacks->lastItem() ?? 0 }}
                                        dari {{ $feedbacks->total() }} data
                                    </span>
                                </div>
                                <nav>
                                    {{ $feedbacks->appends(request()->query())->links('pagination::bootstrap-4', ['class' => 'pagination-sm']) }}
                                </nav>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-gradient-{{ $projectTypeInfo['color'] ?? 'primary' }} text-white">
                        <h5 class="modal-title" id="addModalLabel">
                            <i class="mdi-plus me-2"></i>
                            Tambah Feedback {{ $projectTypeInfo['name'] }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form id="addForm" action="{{ route('project-feedbacks.store') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="filter_type" value="{{ $type }}">
                        <input type="hidden" name="filter_sub_type" value="{{ $subType }}">
                        <div class="modal-body">
                            @if ($type !== 'all' && !empty($availableProjects) && $availableProjects->count() > 0)
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group mb-3">
                                            <label for="data_spatial_id" class="form-label">Pilih
                                                {{ $projectTypeInfo['name'] }} <span class="text-danger">*</span></label>
                                            <select class="form-control" id="data_spatial_id" name="data_spatial_id"
                                                required>
                                                <option value="">-- Pilih {{ $projectTypeInfo['name'] }} --</option>
                                                @foreach ($availableProjects as $project)
                                                    <option value="{{ $project->id }}">
                                                        {{ $project->deskripsi ?? 'Tanpa Deskripsi' }}
                                                        @if ($project->uuid)
                                                            ({{ Str::limit($project->uuid, 8) }}...)
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_nama_pemberi_aspirasi" class="form-label">Nama Pemberi Aspirasi <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="add_nama_pemberi_aspirasi"
                                            name="nama_pemberi_aspirasi" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_nama_proyek" class="form-label">Nama Proyek <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="add_nama_proyek" name="nama_proyek"
                                            required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_kabupaten_kota" class="form-label">Kabupaten/Kota <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" id="add_kabupaten_kota" name="kabupaten_kota" required>
                                            <option value="">-- Pilih Kabupaten/Kota --</option>
                                            @foreach ($kabupaten_list as $kab)
                                                <option value="{{ $kab }}">{{ $kab }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_kecamatan" class="form-label">Kecamatan</label>
                                        <select class="form-control" id="add_kecamatan" name="kecamatan">
                                            <option value="">-- Pilih Kecamatan --</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_jenis_tanggapan" class="form-label">Jenis Tanggapan <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" id="add_jenis_tanggapan" name="jenis_tanggapan"
                                            required>
                                            <option value="">-- Pilih Jenis --</option>
                                            <option value="keluhan">Keluhan</option>
                                            <option value="saran">Saran</option>
                                            <option value="apresiasi">Apresiasi</option>
                                            <option value="pertanyaan">Pertanyaan</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_laporan_gambar" class="form-label">Laporan Gambar</label>
                                        <input type="file" class="form-control" id="add_laporan_gambar"
                                            name="laporan_gambar" accept="image/*">
                                        <small class="text-muted">Maksimal 2MB (JPG, PNG, GIF)</small>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="add_email" name="email">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_phone" class="form-label">No. Telepon</label>
                                        <input type="text" class="form-control" id="add_phone" name="phone">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_latitude" class="form-label">Latitude</label>
                                        <input type="number" step="any" class="form-control" id="add_latitude"
                                            name="latitude" placeholder="0.7881">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_longitude" class="form-label">Longitude</label>
                                        <input type="number" step="any" class="form-control" id="add_longitude"
                                            name="longitude" placeholder="127.3781">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="add_tanggapan" class="form-label">Tanggapan <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="add_tanggapan" name="tanggapan" rows="4" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-gradient-{{ $projectTypeInfo['color'] ?? 'primary' }}">
                                <i class="mdi mdi-content-save"></i> Simpan
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
                    <div class="modal-header bg-gradient-{{ $projectTypeInfo['color'] ?? 'primary' }} text-white">
                        <h5 class="modal-title" id="showModalLabel">
                            <i class="mdi-eye me-2"></i>
                            Detail {{ $projectTypeInfo['name'] }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Informasi Feedback</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Nama Pemberi:</strong>
                                                <p id="show_nama_pemberi_aspirasi" class="text-muted"></p>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Nama Proyek:</strong>
                                                <p id="show_nama_proyek" class="text-muted"></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>Kabupaten:</strong>
                                                <p id="show_kabupaten_kota" class="text-muted"></p>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Kecamatan:</strong>
                                                <p id="show_kecamatan" class="text-muted"></p>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Jenis:</strong>
                                                <span id="show_jenis_tanggapan" class="badge"></span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Email:</strong>
                                                <p id="show_email" class="text-muted"></p>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Telepon:</strong>
                                                <p id="show_phone" class="text-muted"></p>
                                            </div>
                                        </div>

                                        <!-- Project Info Section - Enhanced with UUID and Description -->
                                        <div class="row" id="show_project_info" style="display: none;">
                                            <div class="col-12">
                                                <div class="border-top pt-3 mt-3">
                                                    <h6 class="text-primary"><i
                                                            class="mdi mdi-folder-outline me-2"></i>Informasi Proyek Terkait
                                                    </h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>UUID Proyek:</strong>
                                                            <p id="show_project_uuid" class="text-muted font-monospace"></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Tipe Data:</strong>
                                                            <p id="show_project_type" class="text-muted"></p>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Sub Tipe:</strong>
                                                            <p id="show_project_subtype" class="text-muted"></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Tahun:</strong>
                                                            <p id="show_project_year" class="text-muted"></p>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <strong>Deskripsi Proyek:</strong>
                                                            <div id="show_project_description"
                                                                class="text-muted p-3 bg-light rounded mt-2"
                                                                style="min-height: 60px;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <strong>Tanggapan:</strong>
                                                <div id="show_tanggapan" class="text-muted p-3 bg-light rounded mt-2"
                                                    style="min-height: 80px;"></div>
                                            </div>
                                        </div>

                                        <div class="row mt-3" id="show_koordinat_row" style="display: none;">
                                            <div class="col-12">
                                                <div class="border-top pt-3">
                                                    <h6 class="text-success"><i class="mdi mdi-map-marker me-2"></i>Lokasi
                                                        Pemberi Aspirasi</h6>
                                                    {{-- <div class="row">
                                                        <div class="col-md-6">
                                                            <strong>Latitude:</strong>
                                                            <p id="show_latitude" class="text-muted font-monospace"
                                                                style="cursor: pointer;" title="Klik untuk copy koordinat">
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong>Longitude:</strong>
                                                            <p id="show_longitude" class="text-muted font-monospace"
                                                                style="cursor: pointer;" title="Klik untuk copy koordinat">
                                                            </p>
                                                        </div>
                                                    </div> --}}
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
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Status & Response</h6>
                                        <span id="show_status" class="badge"></span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <strong>Tanggal Dibuat:</strong>
                                            <p id="show_created_at" class="text-muted"></p>
                                        </div>

                                        <div class="mb-3" id="show_gambar_container" style="display: none;">
                                            <strong>Laporan Gambar:</strong>
                                            <div class="mt-2">
                                                <img id="show_laporan_gambar" src="" alt="Laporan"
                                                    class="img-fluid rounded shadow-sm"
                                                    style="max-height: 200px; width: 100%; object-fit: cover;">
                                                <div class="mt-2">
                                                    <a id="download_gambar" href="#"
                                                        class="btn btn-outline-primary btn-sm" download>
                                                        <i class="mdi mdi-download"></i> Download Gambar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="show_response_container" style="display: none;">
                                            <div class="border-top pt-3">
                                                <strong class="text-success">Response Admin:</strong>
                                                <div id="show_response_admin" class="text-muted p-3 bg-light rounded mt-2">
                                                </div>
                                                <small class="text-muted">
                                                    <i class="mdi mdi-clock-outline me-1"></i>Direspon pada: <span
                                                        id="show_responded_at"></span>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="mdi mdi-close"></i> Tutup
                        </button>
                        <button type="button"
                            class="btn btn-gradient-{{ $projectTypeInfo['color'] ?? 'primary' }} btn-respond"
                            id="btnRespond">
                            <i class="mdi mdi-reply"></i> Beri Response
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Response Modal -->
        <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-gradient-{{ $projectTypeInfo['color'] ?? 'primary' }} text-white">
                        <h5 class="modal-title" id="responseModalLabel">
                            <i class="mdi mdi-reply me-2"></i> Beri Response Admin
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form id="responseForm" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="response_id" name="id">
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label for="response_status" class="form-label">Update Status <span
                                        class="text-danger">*</span></label>
                                <select class="form-control" id="response_status" name="status" required>
                                    <option value="ditinjau">Ditinjau</option>
                                    <option value="ditindaklanjuti">Ditindaklanjuti</option>
                                    <option value="selesai">Selesai</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="response_admin" class="form-label">Response Admin <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="response_admin" name="response_admin" rows="4" required
                                    placeholder="Berikan response/tanggapan admin terhadap feedback ini..."></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-gradient-{{ $projectTypeInfo['color'] ?? 'primary' }}">
                                <i class="mdi mdi-send"></i> Kirim Response
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
                const currentType = '{{ $type }}';
                const currentSubType = '{{ $subType }}';

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

                // Client-side filtering functionality
                function filterTable() {
                    const searchTerm = $('#searchInput').val().toLowerCase();
                    const kabupatenFilter = $('#kabupatenFilter').val();
                    const activeFilter = $('.filter-item.active').data('filter') || 'all';

                    let visibleCount = 0;

                    $('#feedbackTable tbody tr').each(function() {
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

                        // Kabupaten filter
                        if (kabupatenFilter) {
                            const rowKabupaten = $row.data('kabupaten');
                            if (rowKabupaten !== kabupatenFilter) {
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

                    // Show/hide no data message
                    const $noDataRow = $('#no-data-row');
                    if (visibleCount === 0 && $noDataRow.length === 0) {
                        $('#feedbackTable tbody').append(`
                <tr id="no-data-row">
                    <td colspan="8" class="text-center">
                        <div class="py-4">
                        <i class="mdi mdi-map-marker me-2"></i>
                            <p class="text-muted mt-2">Tidak ada data yang cocok dengan filter</p>
                        </div>
                    </td>
                </tr>
            `);
                    } else if (visibleCount > 0) {
                        $noDataRow.remove();
                    }
                }

                // Event Handlers
                $('.filter-item').on('click', function(e) {
                    e.preventDefault();
                    $('.filter-item').removeClass('active');
                    $(this).addClass('active');
                    filterTable();
                });

                $('#searchInput').on('input', filterTable);
                $('#kabupatenFilter').on('change', filterTable);

                $('#resetFilter').on('click', function() {
                    $('.filter-item').removeClass('active');
                    $('.filter-item[data-filter="all"]').addClass('active');
                    $('#searchInput').val('');
                    $('#kabupatenFilter').val('');
                    filterTable();
                });

                // Add Modal
                $('#addModal').on('show.bs.modal', function() {
                    const form = $('#addForm');
                    form[0].reset();
                    clearFormErrors(form);
                    $('#add_kecamatan').empty().append('<option value="">-- Pilih Kecamatan --</option>');
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

                    // Build the correct URL based on current context
                    let showUrl = '';
                    if (currentType !== 'all') {
                        // If we're in a filtered view, maintain the URL structure
                        const currentParams = new URLSearchParams(window.location.search);
                        showUrl = `${window.location.pathname}/${id}?${currentParams.toString()}`;
                    } else {
                        showUrl = `{{ route('project-feedbacks.index') }}/${id}`;
                    }

                    $.ajax({
                        url: showUrl,
                        type: 'GET',
                        success: function(data) {
                            if (data.status === 'success') {
                                const feedback = data.data;

                                // Basic info
                                $('#show_nama_pemberi_aspirasi').text(feedback
                                    .nama_pemberi_aspirasi);
                                $('#show_nama_proyek').text(feedback.nama_proyek);
                                $('#show_kabupaten_kota').text(feedback.kabupaten_kota);
                                $('#show_kecamatan').text(feedback.kecamatan || '-');
                                $('#show_email').text(feedback.email || '-');
                                $('#show_phone').text(feedback.phone || '-');
                                $('#show_tanggapan').text(feedback.tanggapan);
                                $('#show_created_at').text(new Date(feedback.created_at)
                                    .toLocaleDateString('id-ID'));

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
                                    .text(feedback.status.charAt(0).toUpperCase() + feedback.status
                                        .slice(1));

                                // Koordinat
                                if (feedback.latitude && feedback.longitude) {
                                    $('#show_latitude').text(feedback.latitude);
                                    $('#show_longitude').text(feedback.longitude);
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

                                    $('#show_latitude, #show_longitude').off('click').on('click',
                                        function() {
                                            const coordinates =
                                                `${feedback.latitude}, ${feedback.longitude}`;
                                            copyToClipboard(coordinates);
                                        });
                                } else {
                                    $('#show_koordinat_row').hide();
                                }

                                // Gambar
                                if (feedback.laporan_gambar) {
                                    const imagePath =
                                        `/storage/feedback_images/${feedback.laporan_gambar}`;
                                    $('#show_laporan_gambar').attr('src', imagePath);
                                    $('#download_gambar').attr('href', imagePath);
                                    $('#show_gambar_container').show();
                                } else {
                                    $('#show_gambar_container').hide();
                                }

                                // Response admin
                                if (feedback.response_admin) {
                                    $('#show_response_admin').text(feedback.response_admin);
                                    $('#show_responded_at').text(new Date(feedback.responded_at)
                                        .toLocaleDateString('id-ID'));
                                    $('#show_response_container').show();
                                } else {
                                    $('#show_response_container').hide();
                                }

                                // Set respond button data
                                $('#btnRespond').data('id', feedback.id);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error loading feedback details:', xhr);
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

                    // Build the correct respond URL
                    let respondUrl = '';
                    if (currentType !== 'all') {
                        const currentParams = new URLSearchParams(window.location.search);
                        respondUrl = `${window.location.pathname}/${id}/respond?${currentParams.toString()}`;
                    } else {
                        respondUrl = `{{ route('project-feedbacks.index') }}/${id}/respond`;
                    }

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
                                showAlert(response.message);
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
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
                                '<i class="mdi mdi-send"></i> Kirim Response');
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

                            let deleteUrl = '';
                            if (currentType !== 'all') {
                                const currentParams = new URLSearchParams(window.location.search);
                                deleteUrl = `${window.location.pathname}/${id}?${currentParams.toString()}`;
                            } else {
                                deleteUrl = `{{ route('project-feedbacks.index') }}/${id}`;
                            }

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
            });
        </script>

        <style>
            .input-group .form-control,
            .input-group .form-select {
                height: calc(1.5em + .75rem + 2px);
                /* Atur sesuai kebutuhan */
            }

            /* Responsive Table Styles */
            .table-responsive {
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            }

            .table th {
                color: #000000;
                border: none;
                font-weight: 600;
                white-space: nowrap;
            }

            .table td {
                border-color: #e9ecef;
                vertical-align: middle;
            }

            .table tbody tr:hover {
                background-color: #f9f8fa;
            }

            /* Mobile Responsive Adjustments */
            @media (max-width: 767px) {

                .table th,
                .table td {
                    padding: 0.5rem 0.25rem;
                    font-size: 0.875rem;
                }

                .btn-group .btn {
                    padding: 0.25rem 0.5rem;
                    font-size: 0.75rem;
                }

                .badge {
                    font-size: 0.65rem;
                    padding: 0.2rem 0.4rem;
                }
            }

            @media (max-width: 575px) {

                .table th,
                .table td {
                    padding: 0.4rem 0.2rem;
                    font-size: 0.8rem;
                }
            }

            /* Enhanced Modal Styles */
            .modal-content {
                border: none;
                border-radius: 15px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            }

            .modal-xl {
                max-width: 95%;
            }

            @media (max-width: 991px) {
                .modal-xl {
                    max-width: 98%;
                    margin: 0.5rem;
                }
            }

            /* Project Info Styling */
            .font-monospace {
                font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace !important;
                font-size: 0.875rem;
            }

            /* Form and Button Styles */
            .form-control:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }

            .filter-item.active {
                background-color: #667eea;
                color: white;
            }

            .btn-gradient-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                color: white;
            }

            .btn-gradient-success {
                background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                border: none;
                color: white;
            }

            .btn-gradient-info {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                color: white;
            }

            .btn-gradient-warning {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                border: none;
                color: white;
            }

            .btn-gradient-danger {
                background: linear-gradient(135deg, #ff6b6b 0%, #ee4757 100%);
                border: none;
                color: white;
            }

            /* Badge Improvements */
            .badge {
                font-size: 0.75em;
                padding: 0.375rem 0.75rem;
                border-radius: 0.375rem;
            }

            .badge-outline-primary {
                border: 1px solid #007bff;
                color: #007bff;
                background-color: transparent;
            }

            .badge-outline-info {
                border: 1px solid #17a2b8;
                color: #17a2b8;
                background-color: transparent;
            }

            .badge-outline-success {
                border: 1px solid #28a745;
                color: #28a745;
                background-color: transparent;
            }

            .badge-outline-warning {
                border: 1px solid #ffc107;
                color: #ffc107;
                background-color: transparent;
            }

            .badge-outline-danger {
                border: 1px solid #dc3545;
                color: #dc3545;
                background-color: transparent;
            }

            /* Card and Alert Styles */
            .alert {
                border-radius: 10px;
            }

            .card {
                border-radius: 15px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                border: none;
            }

            .card-header {
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                background-color: rgba(0, 0, 0, 0.02);
            }

            /* Text Truncation */
            .text-truncate {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            /* Hover Effects */
            .btn-group .btn:hover {
                transform: translateY(-1px);
                transition: all 0.2s ease;
            }

            /* Coordinate Copy Cursor */
            [style*="cursor: pointer"]:hover {
                background-color: rgba(0, 123, 255, 0.1);
                border-radius: 4px;
                padding: 2px 4px;
                transition: all 0.2s ease;
            }

            /* Image Container */
            #show_gambar_container img {
                border: 2px solid #e9ecef;
                transition: all 0.3s ease;
            }

            #show_gambar_container img:hover {
                border-color: #007bff;
                transform: scale(1.02);
            }

            /* Status Badge Animation */
            .badge {
                transition: all 0.2s ease;
            }

            .badge:hover {
                transform: scale(1.05);
            }

            /* Responsive Improvements */
            @media (max-width: 767px) {
                .modal-dialog {
                    margin: 0.5rem;
                }

                .card-body {
                    padding: 1rem;
                }

                .btn-group {
                    display: flex;
                    flex-direction: column;
                    width: 100%;
                }

                .btn-group .btn {
                    margin-bottom: 0.25rem;
                    border-radius: 0.375rem !important;
                }
            }

            /* Loading States */
            .btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }

            /* Enhanced Form Styling */
            .form-control {
                border-radius: 0.5rem;
                border: 1px solid #e3e6f0;
                transition: all 0.2s ease;
            }

            .form-control:focus {
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
                border-color: #667eea;
            }

            .form-label {
                font-weight: 600;
                color: #5a5c69;
                margin-bottom: 0.5rem;
            }

            /* Search Box Enhancement */
            .input-group .form-control {
                border-right: none;
            }

            .input-group-text {
                border-left: none;
                background-color: #f8f9fc;
                border-color: #e3e6f0;
            }

            /* Statistics Cards */
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

            /* Dropdown Menu */
            .dropdown-menu {
                border-radius: 0.5rem;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                border: none;
            }

            .dropdown-item:hover {
                background-color: #f8f9fc;
                color: #5a5c69;
            }

            .dropdown-header {
                font-weight: 600;
                color: #5a5c69;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            /* Page Header */
            .page-title-icon {
                width: 50px;
                height: 50px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
            }

            .breadcrumb {
                background-color: transparent;
                margin-bottom: 0;
            }

            .breadcrumb-item a {
                color: #858796;
                text-decoration: none;
            }

            .breadcrumb-item a:hover {
                color: #5a5c69;
            }

            .breadcrumb-item.active {
                color: #5a5c69;
            }

            /* Utility Classes */
            .shadow-sm {
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
            }

            .bg-light {
                background-color: #f8f9fc !important;
            }

            /* Print Styles */
            @media print {

                .btn,
                .dropdown,
                .modal,
                .alert {
                    display: none !important;
                }

                .table {
                    font-size: 0.8rem;
                }

                .card {
                    box-shadow: none;
                    border: 1px solid #000;
                }
            }
        </style>
    @endsection
