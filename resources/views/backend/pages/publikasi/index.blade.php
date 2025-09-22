@extends('backend.partials.main', ['title' => 'Daftar Publikasi'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-book-open-page-variant"></i>
            </span> Daftar Publikasi
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Publikasi
                </li>
            </ul>
        </nav>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-file-document-multiple text-primary me-2"></i>
                    Daftar Publikasi
                </h4>
                <button type="button" class="btn btn-gradient-primary btn-rounded" data-bs-toggle="modal"
                    data-bs-target="#addModal">
                    <i class="mdi mdi-plus me-1"></i>Tambah Publikasi
                </button>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="mdi mdi-check-circle-outline fs-4 me-2"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="mdi mdi-alert-circle-outline fs-4 me-2"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Filter Section -->
            <div class="card border-0 bg-light mb-4">
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small text-muted mb-1">Kategori</label>
                            <select class="form-select form-select-sm" id="categoryFilter">
                                <option value="">Semua Kategori</option>
                                <option value="Laporan">Laporan</option>
                                <option value="Buku">Buku</option>
                                <option value="Jurnal">Jurnal</option>
                                <option value="Panduan">Panduan</option>
                                <option value="Policy Brief">Policy Brief</option>
                            </select>
                        </div>
                        <div class="col-lg-8">
                            <label class="form-label small text-muted mb-1">Pencarian</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="mdi mdi-magnify text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="searchInput"
                                    placeholder="Cari publikasi...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="publicationsTable">
                    <thead class="table-primary">
                        <tr>
                            <th style="width: 5%;" class="text-center">No</th>
                            <th style="width: 45%;">Publikasi</th>
                            <th style="width: 18%;" class="text-center">Kategori</th>
                            <th style="width: 15%;" class="text-center">Tanggal</th>
                            <th style="width: 8%;" class="text-center">Download</th>
                            <th style="width: 12%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse($publications as $publication)
                            <tr>
                                <td class="text-center fw-bold text-primary">{{ $no++ }}</td>
                                <td>
                                    <div class="d-flex align-items-start">
                                        <div class="file-icon me-3">
                                            @if (pathinfo($publication->file_name, PATHINFO_EXTENSION) === 'pdf')
                                                <i class="mdi mdi-file-pdf-box text-danger" style="font-size: 2rem;"></i>
                                            @elseif(in_array(pathinfo($publication->file_name, PATHINFO_EXTENSION), ['doc', 'docx']))
                                                <i class="mdi mdi-file-word-box text-primary" style="font-size: 2rem;"></i>
                                            @elseif(in_array(pathinfo($publication->file_name, PATHINFO_EXTENSION), ['xls', 'xlsx']))
                                                <i class="mdi mdi-file-excel-box text-success" style="font-size: 2rem;"></i>
                                            @elseif(in_array(pathinfo($publication->file_name, PATHINFO_EXTENSION), ['ppt', 'pptx']))
                                                <i class="mdi mdi-file-powerpoint-box text-warning"
                                                    style="font-size: 2rem;"></i>
                                            @else
                                                <i class="mdi mdi-file-document-box text-secondary"
                                                    style="font-size: 2rem;"></i>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-semibold">{{ $publication->title }}</h6>
                                            @if ($publication->description)
                                                <p class="text-muted small mb-1">
                                                    {{ Str::limit($publication->description, 60) }}</p>
                                            @endif
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-light text-dark small">
                                                    {{ number_format($publication->file_size / 1024 / 1024, 1) }} MB
                                                </span>
                                                <span class="badge bg-light text-dark small">
                                                    {{ strtoupper(pathinfo($publication->file_name, PATHINFO_EXTENSION)) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($publication->category)
                                        <span
                                            class="badge bg-info bg-gradient rounded-pill">{{ $publication->category }}</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="text-nowrap">
                                        {{ $publication->created_at->format('d/m/Y') }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary bg-gradient rounded-pill fs-6">
                                        {{ $publication->download_count }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#previewModal{{ $publication->id }}"
                                            title="Lihat File">
                                            <i class="mdi mdi-file-eye"></i>
                                        </button>

                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                                            data-bs-target="#viewModal{{ $publication->id }}" title="Detail">
                                            <i class="mdi mdi-information"></i>
                                        </button>

                                        <button type="button" class="btn btn-sm btn-outline-warning"
                                            data-bs-toggle="modal" data-bs-target="#editModal{{ $publication->id }}"
                                            title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>

                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn"
                                            data-id="{{ $publication->id }}" data-title="{{ $publication->title }}"
                                            title="Hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </div>
                                </td>

                            </tr>

                            <!-- Preview Modal -->
                            <div class="modal fade" id="previewModal{{ $publication->id }}" tabindex="-1"
                                aria-labelledby="previewModalLabel{{ $publication->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title" id="previewModalLabel{{ $publication->id }}">
                                                <i class="mdi mdi-file-eye me-2"></i>Preview: {{ $publication->title }}
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white"
                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-0">
                                            @if (pathinfo($publication->file_name, PATHINFO_EXTENSION) === 'pdf')
                                                <div class="text-center p-4" style="height: 600px;">
                                                    <div class="pdf-preview-container"
                                                        style="height: 100%; position: relative;">
                                                        <!-- Loading indicator -->
                                                        <div class="preview-loading text-center"
                                                            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10;">
                                                            <div class="spinner-border text-primary" role="status">
                                                                <span class="visually-hidden">Loading...</span>
                                                            </div>
                                                            <p class="mt-2">Memuat preview...</p>
                                                        </div>

                                                        <!-- PDF iframe -->
                                                        <iframe id="pdfPreview{{ $publication->id }}"
                                                            src="{{ asset('storage/' . $publication->file_path) }}"
                                                            width="100%" height="100%"
                                                            style="border: none; border-radius: 8px; display: none;"
                                                            onload="hideLoading({{ $publication->id }})"
                                                            onerror="showError({{ $publication->id }})">
                                                        </iframe>

                                                        <!-- Error message -->
                                                        <div id="previewError{{ $publication->id }}"
                                                            class="preview-error text-center"
                                                            style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10;">
                                                            <i class="mdi mdi-alert-circle text-warning"
                                                                style="font-size: 3rem;"></i>
                                                            <h5 class="mt-3">Preview tidak dapat dimuat</h5>
                                                            <p class="text-muted">File mungkin terlalu besar atau browser
                                                                tidak mendukung preview</p>
                                                            <div class="mt-3">
                                                                <a href="{{ asset('storage/' . $publication->file_path) }}"
                                                                    target="_blank" class="btn btn-primary me-2">
                                                                    <i class="mdi mdi-open-in-new me-1"></i>Buka di Tab
                                                                    Baru
                                                                </a>
                                                                <a href="{{ route('publications.download', $publication->id) }}"
                                                                    class="btn btn-success">
                                                                    <i class="mdi mdi-download me-1"></i>Download
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center p-5">
                                                    <div class="mb-4">
                                                        @if (in_array(pathinfo($publication->file_name, PATHINFO_EXTENSION), ['doc', 'docx']))
                                                            <i class="mdi mdi-file-word-box text-primary"
                                                                style="font-size: 5rem;"></i>
                                                        @elseif(in_array(pathinfo($publication->file_name, PATHINFO_EXTENSION), ['xls', 'xlsx']))
                                                            <i class="mdi mdi-file-excel-box text-success"
                                                                style="font-size: 5rem;"></i>
                                                        @elseif(in_array(pathinfo($publication->file_name, PATHINFO_EXTENSION), ['ppt', 'pptx']))
                                                            <i class="mdi mdi-file-powerpoint-box text-warning"
                                                                style="font-size: 5rem;"></i>
                                                        @else
                                                            <i class="mdi mdi-file-document-box text-secondary"
                                                                style="font-size: 5rem;"></i>
                                                        @endif
                                                    </div>
                                                    <h5 class="mb-3">{{ $publication->file_name }}</h5>
                                                    <p class="text-muted mb-4">Preview tidak tersedia untuk file jenis ini
                                                    </p>
                                                    <div class="row text-start">
                                                        <div class="col-md-6 offset-md-3">
                                                            <div class="card border-0 bg-light">
                                                                <div class="card-body">
                                                                    <h6 class="card-title">Informasi File</h6>
                                                                    <ul class="list-unstyled mb-0">
                                                                        <li><strong>Nama:</strong>
                                                                            {{ $publication->file_name }}</li>
                                                                        <li><strong>Ukuran:</strong>
                                                                            {{ number_format($publication->file_size / 1024 / 1024, 2) }}
                                                                            MB</li>
                                                                        <li><strong>Tipe:</strong>
                                                                            {{ strtoupper(pathinfo($publication->file_name, PATHINFO_EXTENSION)) }}
                                                                        </li>
                                                                        <li><strong>Download:</strong>
                                                                            {{ $publication->download_count }} kali</li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="mdi mdi-close me-1"></i>Tutup
                                            </button>
                                            <a href="{{ route('publications.download', $publication->id) }}"
                                                class="btn btn-gradient-primary">
                                                <i class="mdi mdi-download me-1"></i>Download File
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- View Detail Modal -->
                            <div class="modal fade" id="viewModal{{ $publication->id }}" tabindex="-1"
                                aria-labelledby="viewModalLabel{{ $publication->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title" id="viewModalLabel{{ $publication->id }}">
                                                <i class="mdi mdi-information me-2"></i>Detail Publikasi
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white"
                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-12">
                                                    <div class="card border-0 bg-light">
                                                        <div class="card-body">
                                                            <h6 class="card-title">Informasi Publikasi</h6>
                                                            <div class="row">
                                                                <div class="col-md-12 mb-3">
                                                                    <label
                                                                        class="form-label small text-muted">Judul</label>
                                                                    <p class="mb-0 fw-semibold">{{ $publication->title }}
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label
                                                                        class="form-label small text-muted">Kategori</label>
                                                                    <p class="mb-0">
                                                                        @if ($publication->category)
                                                                            <span
                                                                                class="badge bg-info">{{ $publication->category }}</span>
                                                                        @else
                                                                            <span class="text-muted">-</span>
                                                                        @endif
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label small text-muted">Tanggal
                                                                        Publikasi</label>
                                                                    <p class="mb-0">
                                                                        {{ $publication->created_at->format('d F Y') }}
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-12 mb-3">
                                                                    <label
                                                                        class="form-label small text-muted">Deskripsi</label>
                                                                    <p class="mb-0">
                                                                        {{ $publication->description ?? 'Tidak ada deskripsi' }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="card border-0 bg-light">
                                                        <div class="card-body">
                                                            <h6 class="card-title">Informasi File</h6>
                                                            <div class="row">
                                                                <div class="col-md-4 mb-2">
                                                                    <label class="form-label small text-muted">Ukuran
                                                                        File</label>
                                                                    <p class="mb-0">
                                                                        {{ number_format($publication->file_size / 1024 / 1024, 2) }}
                                                                        MB</p>
                                                                </div>
                                                                <div class="col-md-4 mb-2">
                                                                    <label class="form-label small text-muted">Tipe
                                                                        File</label>
                                                                    <p class="mb-0">
                                                                        {{ strtoupper($publication->file_type) }}</p>
                                                                </div>
                                                                <div class="col-md-4 mb-2">
                                                                    <label class="form-label small text-muted">Total
                                                                        Download</label>
                                                                    <p class="mb-0">
                                                                        <span
                                                                            class="badge bg-primary">{{ $publication->download_count }}
                                                                            kali</span>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="mdi mdi-close me-1"></i>Tutup
                                            </button>
                                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal"
                                                data-bs-toggle="modal"
                                                data-bs-target="#previewModal{{ $publication->id }}">
                                                <i class="mdi mdi-file-eye me-1"></i>Lihat File
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal{{ $publication->id }}" tabindex="-1"
                                aria-labelledby="editModalLabel{{ $publication->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title" id="editModalLabel{{ $publication->id }}">
                                                <i class="mdi mdi-pencil me-2"></i>Edit Publikasi
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('publications.update', $publication->id) }}"
                                            method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label for="edit_title_{{ $publication->id }}"
                                                            class="form-label">
                                                            Judul Publikasi <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" class="form-control"
                                                            id="edit_title_{{ $publication->id }}" name="title"
                                                            value="{{ $publication->title }}" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="edit_category_{{ $publication->id }}"
                                                            class="form-label">Kategori</label>
                                                        <select class="form-select"
                                                            id="edit_category_{{ $publication->id }}" name="category">
                                                            <option value="">Pilih Kategori</option>
                                                            <option value="Laporan"
                                                                {{ $publication->category == 'Laporan' ? 'selected' : '' }}>
                                                                Laporan</option>
                                                            <option value="Buku"
                                                                {{ $publication->category == 'Buku' ? 'selected' : '' }}>
                                                                Buku</option>
                                                            <option value="Jurnal"
                                                                {{ $publication->category == 'Jurnal' ? 'selected' : '' }}>
                                                                Jurnal</option>
                                                            <option value="Panduan"
                                                                {{ $publication->category == 'Panduan' ? 'selected' : '' }}>
                                                                Panduan</option>
                                                            <option value="Policy Brief"
                                                                {{ $publication->category == 'Policy Brief' ? 'selected' : '' }}>
                                                                Policy Brief</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Tanggal Publikasi</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $publication->created_at->format('d F Y') }}"
                                                            readonly>
                                                        <small class="text-muted">Tanggal otomatis dari waktu
                                                            upload</small>
                                                    </div>
                                                    <div class="col-md-12 mb-3">
                                                        <label for="edit_description_{{ $publication->id }}"
                                                            class="form-label">Deskripsi</label>
                                                        <textarea class="form-control" id="edit_description_{{ $publication->id }}" name="description" rows="3">{{ $publication->description }}</textarea>
                                                    </div>
                                                    <div class="col-md-12 mb-3">
                                                        <label for="edit_file_{{ $publication->id }}"
                                                            class="form-label">File Publikasi</label>
                                                        <input type="file" class="form-control"
                                                            id="edit_file_{{ $publication->id }}" name="file"
                                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                                                        <small class="text-muted">
                                                            Maksimal 50MB (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX).
                                                            Kosongkan jika tidak ingin mengubah file.
                                                        </small>
                                                        <div class="mt-2">
                                                            <small class="text-info">File saat ini:
                                                                {{ $publication->file_name }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="mdi mdi-close me-1"></i>Tutup
                                                </button>
                                                <button type="submit" class="btn btn-gradient-primary">
                                                    <i class="mdi mdi-content-save me-1"></i>Simpan Perubahan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="mdi mdi-book-open-page-variant"
                                            style="font-size: 4rem; opacity: 0.3;"></i>
                                        <h5 class="mt-3 mb-2">Belum ada publikasi</h5>
                                        <p>Mulai dengan menambahkan publikasi pertama Anda.</p>
                                        <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                            data-bs-target="#addModal">
                                            <i class="mdi mdi-plus me-1"></i>Tambah Publikasi
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if (method_exists($publications, 'links'))
                <div class="d-flex justify-content-center mt-4">
                    {{ $publications->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="mdi mdi-plus me-2"></i>Tambah Publikasi Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="addForm" action="{{ route('publications.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="add_title" class="form-label">Judul Publikasi <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_title" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_category" class="form-label">Kategori</label>
                                <select class="form-select" id="add_category" name="category">
                                    <option value="">Pilih Kategori</option>
                                    <option value="Laporan">Laporan</option>
                                    <option value="Buku">Buku</option>
                                    <option value="Jurnal">Jurnal</option>
                                    <option value="Panduan">Panduan</option>
                                    <option value="Policy Brief">Policy Brief</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Publikasi</label>
                                <input type="text" class="form-control"
                                    value="Otomatis ({{ now()->format('d F Y') }})" readonly>
                                <small class="text-muted">Tanggal akan diset otomatis saat upload</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="add_description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="add_file" class="form-label">File Publikasi <span
                                        class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="add_file" name="file" required
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                                <small class="text-muted">Maksimal 50MB (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX)</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="mdi mdi-close me-1"></i>Tutup
                        </button>
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden Forms -->
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('styles')
    <style>
        .card {
            border: none;
            border-radius: 15px;
        }

        .card-body {
            padding: 2rem;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
            border: none;
            padding: 1rem 0.75rem;
        }

        .table td {
            padding: 1rem 0.75rem;
            border-color: #f8f9fa;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #f8f9ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .btn-group .btn {
            border-radius: 8px !important;
            margin-right: 3px;
            transition: all 0.3s ease;
        }

        .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            font-weight: 500;
        }

        .file-icon {
            transition: transform 0.3s ease;
        }

        .file-icon:hover {
            transform: scale(1.1);
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-radius: 15px 15px 0 0;
            padding: 1.5rem 2rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .btn-rounded {
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .table-responsive {
            border-radius: 15px;
            border: none;
        }

        .input-group-text {
            background: white;
            border-color: #dee2e6;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .page-title-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Preview handling functions
        function hideLoading(publicationId) {
            const loading = document.querySelector(`#previewModal${publicationId} .preview-loading`);
            const iframe = document.querySelector(`#pdfPreview${publicationId}`);
            if (loading) loading.style.display = 'none';
            if (iframe) iframe.style.display = 'block';
        }

        function showError(publicationId) {
            const loading = document.querySelector(`#previewModal${publicationId} .preview-loading`);
            const error = document.querySelector(`#previewError${publicationId}`);
            const iframe = document.querySelector(`#pdfPreview${publicationId}`);

            if (loading) loading.style.display = 'none';
            if (iframe) iframe.style.display = 'none';
            if (error) error.style.display = 'block';
        }

        $(document).ready(function() {
            // Initialize DataTable
            $('#publicationsTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                "order": [
                    [0, "desc"]
                ],
                "columnDefs": [{
                        "orderable": false,
                        "targets": [5]
                    }, // Disable sorting for actions column
                ],
                "language": {
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "zeroRecords": "Data tidak ditemukan",
                    "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                    "infoEmpty": "Tidak ada data tersedia",
                    "infoFiltered": "(difilter dari _MAX_ total data)",
                    "search": "Cari:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });

            // Filter functionality
            $('#categoryFilter').on('change', function() {
                filterTable();
            });

            $('#searchInput').on('keyup', function() {
                const table = $('#publicationsTable').DataTable();
                table.search(this.value).draw();
            });

            function filterTable() {
                const category = $('#categoryFilter').val();
                const table = $('#publicationsTable').DataTable();

                table.search('').columns().search('').draw();

                if (category !== '') {
                    table.column(2).search(category, true, false);
                }

                table.draw();
            }

            // Delete functionality
            $('.delete-btn').on('click', function() {
                const publicationId = $(this).data('id');
                const publicationTitle = $(this).data('title');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: `Publikasi "${publicationTitle}" akan dihapus permanen!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deleteForm').attr('action', `/dashboard/publications/${publicationId}`);
                        $('#deleteForm').submit();
                    }
                });
            });

            // Form validation
            $('#addForm, form[action*="update"]').on('submit', function(e) {
                const form = $(this);
                const fileInput = form.find('input[type="file"]');
                const file = fileInput[0].files[0];

                if (file && file.size > 50 * 1024 * 1024) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'File Terlalu Besar!',
                        text: 'Ukuran file maksimal adalah 50MB',
                        icon: 'error'
                    });
                    return false;
                }
            });

            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Preview modal handling
            $('[id^="previewModal"]').on('shown.bs.modal', function() {
                const modalId = this.id;
                const publicationId = modalId.replace('previewModal', '');
                const iframe = document.querySelector(`#pdfPreview${publicationId}`);
                const loading = document.querySelector(`#previewModal${publicationId} .preview-loading`);

                if (loading) loading.style.display = 'block';

                setTimeout(() => {
                    if (iframe && iframe.style.display === 'none') {
                        showError(publicationId);
                    }
                }, 10000);

                if (iframe) {
                    const originalSrc = iframe.src;
                    iframe.src = '';
                    setTimeout(() => {
                        iframe.src = originalSrc;
                    }, 100);
                }
            });
        });
    </script>
@endpush
