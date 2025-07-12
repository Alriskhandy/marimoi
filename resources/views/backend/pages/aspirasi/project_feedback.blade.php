@extends('backend.partials.main')

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-comment-multiple-outline"></i>
            </span> Tanggapan Masyarakat 
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span></span>Tanggapan Masyarakat
                </li>
            </ul>
        </nav>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-sm-6 mt-2">
            <div class="card card-stats">
                <div class="card-header card-header-warning card-header-icon">
                    <div class="card-icon">
                        <i class="mdi mdi-clock-outline"></i>
                    </div>
                    <p class="card-category">Pending</p>
                    <h3 class="card-title">{{ $stats['pending'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mt-2">
            <div class="card card-stats">
                <div class="card-header card-header-info card-header-icon">
                    <div class="card-icon">
                        <i class="mdi mdi-eye-outline"></i>
                    </div>
                    <p class="card-category">Ditinjau</p>
                    <h3 class="card-title">{{ $stats['ditinjau'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mt-2">
            <div class="card card-stats">
                <div class="card-header card-header-primary card-header-icon">
                    <div class="card-icon">
                        <i class="mdi mdi-cog-outline"></i>
                    </div>
                    <p class="card-category">Ditindaklanjuti</p>
                    <h3 class="card-title">{{ $stats['ditindaklanjuti'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mt-2">
            <div class="card card-stats">
                <div class="card-header card-header-success card-header-icon">
                    <div class="card-icon">
                        <i class="mdi mdi-check-circle-outline"></i>
                    </div>
                    <p class="card-category">Selesai</p>
                    <h3 class="card-title">{{ $stats['selesai'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">Daftar Tanggapan Masyarakat</h4>
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
                            <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                data-bs-target="#addModal">
                                <i class="mdi mdi-plus"></i> Tambah Tanggapan
                            </button>
                        </div>
                    </div>

                    <!-- Search Box -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                <input type="text" class="form-control" id="searchInput"
                                    placeholder="Cari berdasarkan nama, proyek, atau tanggapan...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="kabupatenFilter">
                                <option value="">Semua Kabupaten</option>
                                @foreach ($kabupaten_list as $kab)
                                    <option value="{{ $kab }}">{{ $kab }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-secondary" id="resetFilter">
                                <i class="mdi mdi-refresh"></i> Reset Filter
                            </button>
                        </div>
                    </div>

                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="feedbackTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pemberi</th>
                                    <th>Jenis</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($feedbacks as $index => $feedback)
                                    <tr data-id="{{ $feedback->id }}" data-status="{{ $feedback->status }}"
                                        data-jenis="{{ $feedback->jenis_tanggapan }}"
                                        data-kabupaten="{{ $feedback->kabupaten_kota }}"
                                        data-search="{{ strtolower($feedback->nama_pemberi_aspirasi . ' ' . $feedback->nama_proyek . ' ' . $feedback->tanggapan) }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $feedback->nama_pemberi_aspirasi }}</strong>
                                            @if ($feedback->email)
                                                <br><small class="text-muted">{{ $feedback->email }}</small>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="badge badge-{{ $feedback->jenis_badge_class }}">
                                                {{ ucfirst($feedback->jenis_tanggapan) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $feedback->status_badge_class }}">
                                                {{ ucfirst($feedback->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $feedback->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show"
                                                    data-id="{{ $feedback->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#showModal">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                                    data-id="{{ $feedback->id }}"
                                                    onclick="deleteFeedback({{ $feedback->id }})">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-data-row">
                                        <td colspan="6" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-comment-remove-outline mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Tidak ada data tanggapan masyarakat</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($feedbacks->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span class="text-muted">
                                    Menampilkan {{ $feedbacks->firstItem() ?? 0 }} - {{ $feedbacks->lastItem() ?? 0 }}
                                    dari {{ $feedbacks->total() }} data
                                </span>
                            </div>
                            <nav>
                                {{ $feedbacks->links('pagination::bootstrap-4', ['class' => 'pagination-sm']) }}
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
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="mdi mdi-plus"></i> Tambah Tanggapan Masyarakat
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addForm" action="{{ route('project-feedbacks.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
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
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label for="add_latitude" class="form-label">Latitude</label>
                                    <input type="number" step="any" class="form-control" id="add_latitude"
                                        name="latitude" placeholder="0.7881">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label for="add_longitude" class="form-label">Longitude</label>
                                    <input type="number" step="any" class="form-control" id="add_longitude"
                                        name="longitude" placeholder="127.3781">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            {{-- <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Dapatkan Lokasi</label>
                                    <div class="d-flex gap-2">
                                        <!-- Sesudah -->
                                        <a id="openMapBtn" class="btn btn-info btn-sm" target="_blank"
                                            rel="noopener noreferrer">Lokasi Saat Ini</a>

                                    </div>
                                </div>
                            </div> --}}
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
                        <button type="submit" class="btn btn-gradient-primary">
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
                <div class="modal-header">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="mdi mdi-eye"></i> Detail Tanggapan Masyarakat
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Informasi Tanggapan</h6>
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
                                    <div class="row">
                                        <div class="col-12">
                                            <strong>Tanggapan:</strong>
                                            <p id="show_tanggapan" class="text-muted"></p>
                                        </div>
                                    </div>
                                    <div class="row" id="show_koordinat_row" style="display: none;">
                                        <div class="col-md-6">
                                            <strong>Latitude:</strong>
                                            <p id="show_latitude" class="text-muted" style="cursor: pointer;"
                                                title="Klik untuk copy koordinat"></p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Longitude:</strong>
                                            <p id="show_longitude" class="text-muted" style="cursor: pointer;"
                                                title="Klik untuk copy koordinat"></p>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <!-- Sesudah -->
                                            <a id="openMapBtn" class="btn btn-info btn-sm" target="_blank"
                                                rel="noopener noreferrer">Lokasi Saat Ini</a>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
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
                                                class="img-fluid rounded" style="max-height: 200px;">
                                        </div>
                                    </div>
                                    <div id="show_response_container" style="display: none;">
                                        <strong>Response Admin:</strong>
                                        <p id="show_response_admin" class="text-muted"></p>
                                        <small class="text-muted">Direspon pada: <span
                                                id="show_responded_at"></span></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-gradient-warning btn-respond" id="btnRespond">
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
                <div class="modal-header">
                    <h5 class="modal-title" id="responseModalLabel">
                        <i class="mdi mdi-reply"></i> Beri Response Admin
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                placeholder="Berikan response/tanggapan admin terhadap Tanggapan Masyarakat..."></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-gradient-success">
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

            // Show Alert Function
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

            // Load Kecamatan based on Kabupaten selection
            $('#add_kabupaten_kota').on('change', function() {
                const kabupaten = $(this).val();
                const kecamatanSelect = $('#add_kecamatan');

                kecamatanSelect.empty().append('<option value="">-- Pilih Kecamatan --</option>');

                if (kabupaten && kecamatanData[kabupaten]) {
                    kecamatanData[kabupaten].forEach(function(kecamatan) {
                        kecamatanSelect.append(
                            `<option value="${kecamatan}">${kecamatan}</option>`
                        );
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

                    // Skip the no-data row
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
                        // Update row number
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
                            <td colspan="6" class="text-center">
                                <div class="py-4">
                                    <i class="mdi mdi-comment-remove-outline mdi-48px text-muted"></i>
                                    <p class="text-muted mt-2">Tidak ada data yang cocok dengan filter</p>
                                </div>
                            </td>
                        </tr>
                    `);
                } else if (visibleCount > 0) {
                    $noDataRow.remove();
                }
            }

            // Helper Functions
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

            // Event Handlers
            // Filter functionality
            $('.filter-item').on('click', function(e) {
                e.preventDefault();
                $('.filter-item').removeClass('active');
                $(this).addClass('active');
                filterTable();
            });

            // Search functionality
            $('#searchInput').on('input', filterTable);

            // Kabupaten filter
            $('#kabupatenFilter').on('change', filterTable);

            // Reset filter
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
                            // Reload page to show new data
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            showFormErrors(form, xhr.responseJSON.errors);
                        } else {
                            showAlert('Terjadi kesalahan server', 'error');
                        }
                    }
                });
            });

            // Show Modal - Load data via AJAX only for viewing
            $(document).on('click', '.btn-show', function() {
                const id = $(this).data('id');

                $.get(`/project-feedbacks/${id}`, function(data) {
                    if (data.status === 'success') {
                        const feedback = data.data;

                        $('#show_nama_pemberi_aspirasi').text(feedback.nama_pemberi_aspirasi);
                        $('#show_nama_proyek').text(feedback.nama_proyek);
                        $('#show_kabupaten_kota').text(feedback.kabupaten_kota);
                        $('#show_kecamatan').text(feedback.kecamatan || '-');
                        $('#show_email').text(feedback.email || '-');
                        $('#show_phone').text(feedback.phone || '-');
                        $('#show_tanggapan').text(feedback.tanggapan);
                        $('#show_created_at').text(new Date(feedback.created_at).toLocaleDateString(
                            'id-ID'));

                        // Jenis tanggapan badge
                        const jenisClass = getJenisClass(feedback.jenis_tanggapan);
                        $('#show_jenis_tanggapan').removeClass().addClass(
                                `badge badge-${jenisClass}`)
                            .text(feedback.jenis_tanggapan.charAt(0).toUpperCase() + feedback
                                .jenis_tanggapan.slice(1));

                        // Status badge
                        const statusClass = getStatusClass(feedback.status);
                        $('#show_status').removeClass().addClass(`badge badge-${statusClass}`)
                            .text(feedback.status.charAt(0).toUpperCase() + feedback.status.slice(
                                1));

                        // Koordinat
                        if (feedback.latitude && feedback.longitude) {
                            $('#show_latitude').text(feedback.latitude);
                            $('#show_longitude').text(feedback.longitude);
                            $('#show_koordinat_row').show();

                            console.log('Setting coordinates data:', feedback.latitude, feedback
                                .longitude);

                            // Store coordinates as data attribute (cara aman)
                            $('#openMapBtn').attr('data-lat', feedback.latitude);
                            $('#openMapBtn').attr('data-lng', feedback.longitude);

                            // Backup ke jQuery data
                            $('#openMapBtn').data('lat', feedback.latitude);
                            $('#openMapBtn').data('lng', feedback.longitude);

                            // Global variable (opsional)
                            window.currentLat = feedback.latitude;
                            window.currentLng = feedback.longitude;

                            // Buat URL Google Maps dan set ke tombol (misal tombol itu <a>)
                            const googleMapsUrl =
                                `https://www.google.com/maps/search/?api=1&query=${feedback.latitude},${feedback.longitude}`;
                            $('#openMapBtn').attr('href', googleMapsUrl);
                            $('#openMapBtn').attr('target', '_blank'); // agar buka di tab baru

                        } else {
                            $('#show_koordinat_row').hide();
                        }


                        // Gambar
                        if (feedback.laporan_gambar) {
                            $('#show_laporan_gambar').attr('src',
                                `/storage/feedback_images/${feedback.laporan_gambar}`);
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
                }).fail(function() {
                    showAlert('Gagal memuat data detail', 'error');
                });
            });

            // Response Modal
            $(document).on('click', '.btn-respond, #btnRespond', function() {
                const id = $(this).data('id');
                $('#response_id').val(id);
                $('#responseForm')[0].reset(); // Reset form
                clearFormErrors($('#responseForm')); // Clear any previous errors
                $('#responseModal').modal('show');
                $('#showModal').modal('hide');
            });

            // Response Form Submit
            $('#responseForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const id = $('#response_id').val();

                // Clear previous errors
                clearFormErrors(form);

                const formData = {
                    status: $('#response_status').val(),
                    response_admin: $('#response_admin').val(),
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT'
                };

                $.ajax({
                    url: `/project-feedbacks/${id}/respond`,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#responseModal').modal('hide');
                            showAlert(response.message);
                            // Reload page to show updated data
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        console.log('Error:', xhr.responseJSON);
                        if (xhr.status === 422) {
                            showFormErrors(form, xhr.responseJSON.errors);
                        } else {
                            showAlert('Terjadi kesalahan server: ' + (xhr.responseJSON
                                ?.message || 'Unknown error'), 'error');
                        }
                    }
                });
            });

            // Delete function
            window.deleteFeedback = function(id) {
                if (confirm('Yakin ingin menghapus tanggapan ini?')) {
                    $.ajax({
                        url: `/project-feedbacks/${id}`,
                        type: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                showAlert(response.message);
                                // Remove row from table
                                $(`tr[data-id="${id}"]`).fadeOut(function() {
                                    $(this).remove();
                                    filterTable(); // Reapply filters and renumber
                                });
                            }
                        },
                        error: function(xhr) {
                            console.log('Delete Error:', xhr.responseJSON);
                            showAlert('Terjadi kesalahan saat menghapus data: ' + (xhr.responseJSON
                                ?.message || 'Unknown error'), 'error');
                        }
                    });
                }
            };
        });
    </script>

    <style>
        .card-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .card-stats .card-header {
            border: none;
            background: transparent;
            padding: 1.5rem;
        }

        .card-stats .card-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .card-stats .card-icon i {
            font-size: 24px;
        }

        .card-stats .card-category {
            margin: 0;
            font-size: 14px;
            opacity: 0.8;
        }

        .card-stats .card-title {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }

        .card-header-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .card-header-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .card-header-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-header-success {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
        }

        .table td {
            border-color: #e9ecef;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-group .btn {
            margin-right: 2px;
        }

        .badge {
            font-size: 0.75em;
            padding: 0.375rem 0.75rem;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }

        .btn-gradient-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            color: white;
        }

        .btn-gradient-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }

        .filter-item.active {
            background-color: #667eea;
            color: white;
        }
    </style>
@endsection
