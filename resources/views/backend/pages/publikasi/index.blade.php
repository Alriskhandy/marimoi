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

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title">Daftar Publikasi</h4>
                <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="mdi mdi-plus"></i> Tambah Publikasi
                </button>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle-outline"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-alert-circle-outline"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Filter Section -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <select class="form-select" id="categoryFilter">
                        <option value="">Semua Kategori</option>
                        <option value="Laporan">Laporan</option>
                        <option value="Buku">Buku</option>
                        <option value="Jurnal">Jurnal</option>
                        <option value="Panduan">Panduan</option>
                        <option value="Policy Brief">Policy Brief</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Cari publikasi...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="publicationsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 25%;">Judul</th>
                            <th style="width: 15%;">Kategori</th>
                            <th style="width: 12%;">Penulis</th>
                            <th style="width: 10%;">Tanggal</th>
                            <th style="width: 8%;">Status</th>
                            <th style="width: 8%;">Download</th>
                            <th style="width: 12%;">Ukuran</th>
                            <th style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse($publications as $publication)
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="mdi mdi-file-pdf-box text-danger me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <strong>{{ $publication->title }}</strong>
                                            @if ($publication->description)
                                                <br><small
                                                    class="text-muted">{{ Str::limit($publication->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($publication->category)
                                        <span class="badge bg-info text-white">{{ $publication->category }}</span>
                                    @else
                                        <span class="badge bg-secondary">-</span>
                                    @endif
                                </td>
                                <td>{{ $publication->author ?? '-' }}</td>
                                <td>
                                    @if ($publication->published_date)
                                        {{ \Carbon\Carbon::parse($publication->published_date)->format('d/m/Y') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($publication->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $publication->download_count }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ number_format($publication->file_size / 1024 / 1024, 2) }}
                                        MB</small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <!-- View Button -->
                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                                            data-bs-target="#viewModal{{ $publication->id }}" title="Lihat Detail">
                                            <i class="mdi mdi-eye"></i>
                                        </button>

                                        <!-- Edit Button -->
                                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal"
                                            data-bs-target="#editModal{{ $publication->id }}" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>

                                        <!-- Download Button -->
                                        <a href="{{ route('publications.download', $publication->id) }}"
                                            class="btn btn-sm btn-outline-success" title="Download">
                                            <i class="mdi mdi-download"></i>
                                        </a>

                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn"
                                            data-id="{{ $publication->id }}" data-title="{{ $publication->title }}"
                                            title="Hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal{{ $publication->id }}" tabindex="-1"
                                aria-labelledby="viewModalLabel{{ $publication->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewModalLabel{{ $publication->id }}">
                                                <i class="mdi mdi-eye"></i> Detail Publikasi
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Judul:</strong>
                                                    <p>{{ $publication->title }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Kategori:</strong>
                                                    <p>{{ $publication->category ?? '-' }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Penulis:</strong>
                                                    <p>{{ $publication->author ?? '-' }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Tanggal Publikasi:</strong>
                                                    <p>{{ $publication->published_date ? \Carbon\Carbon::parse($publication->published_date)->format('d F Y') : '-' }}
                                                    </p>
                                                </div>
                                                <div class="col-md-12">
                                                    <strong>Deskripsi:</strong>
                                                    <p>{{ $publication->description ?? 'Tidak ada deskripsi' }}</p>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Ukuran File:</strong>
                                                    <p>{{ number_format($publication->file_size / 1024 / 1024, 2) }} MB</p>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Tipe File:</strong>
                                                    <p>{{ strtoupper($publication->file_type) }}</p>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Total Download:</strong>
                                                    <p>{{ $publication->download_count }} kali</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Tutup</button>
                                            <a href="{{ route('publications.download', $publication->id) }}"
                                                class="btn btn-gradient-primary">
                                                <i class="mdi mdi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal{{ $publication->id }}" tabindex="-1"
                                aria-labelledby="editModalLabel{{ $publication->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel{{ $publication->id }}">
                                                <i class="mdi mdi-pencil"></i> Edit Publikasi
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
                                                            class="form-label">Judul Publikasi <span
                                                                class="text-danger">*</span></label>
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
                                                        <label for="edit_author_{{ $publication->id }}"
                                                            class="form-label">Penulis</label>
                                                        <input type="text" class="form-control"
                                                            id="edit_author_{{ $publication->id }}" name="author"
                                                            value="{{ $publication->author }}">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="edit_published_date_{{ $publication->id }}"
                                                            class="form-label">Tanggal Publikasi</label>
                                                        <input type="date" class="form-control"
                                                            id="edit_published_date_{{ $publication->id }}"
                                                            name="published_date"
                                                            value="{{ $publication->published_date }}">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="edit_is_active_{{ $publication->id }}"
                                                            class="form-label">Status</label>
                                                        <select class="form-select"
                                                            id="edit_is_active_{{ $publication->id }}" name="is_active">
                                                            <option value="1"
                                                                {{ $publication->is_active ? 'selected' : '' }}>Aktif
                                                            </option>
                                                            <option value="0"
                                                                {{ !$publication->is_active ? 'selected' : '' }}>Tidak
                                                                Aktif</option>
                                                        </select>
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
                                                        <small class="text-muted">Maksimal 50MB (PDF, DOC, DOCX, XLS, XLSX,
                                                            PPT, PPTX). Kosongkan jika tidak ingin mengubah file.</small>
                                                        <div class="mt-2">
                                                            <small class="text-info">File saat ini:
                                                                {{ $publication->file_name }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-gradient-primary">
                                                    <i class="mdi mdi-content-save"></i> Simpan Perubahan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="mdi mdi-book-open-page-variant" style="font-size: 3rem;"></i>
                                        <p class="mt-2">Belum ada publikasi yang dibuat.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination if needed -->
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
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="mdi mdi-plus"></i> Tambah Publikasi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                <label for="add_author" class="form-label">Penulis</label>
                                <input type="text" class="form-control" id="add_author" name="author">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_published_date" class="form-label">Tanggal Publikasi</label>
                                <input type="date" class="form-control" id="add_published_date"
                                    name="published_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_is_active" class="form-label">Status</label>
                                <select class="form-select" id="add_is_active" name="is_active">
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('styles')
    <style>
        #publicationsTable th,
        #publicationsTable td {
            vertical-align: middle;
        }

        .btn-group .btn {
            border-radius: 0.375rem !important;
            margin-right: 2px;
        }

        .table-responsive {
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
        }

        .badge {
            font-size: 0.75rem;
        }
    </style>
@endpush

@push('scripts')
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
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
            $('#categoryFilter, #statusFilter').on('change', function() {
                filterTable();
            });

            $('#searchInput').on('keyup', function() {
                var table = $('#publicationsTable').DataTable();
                table.search(this.value).draw();
            });

            function filterTable() {
                var category = $('#categoryFilter').val();
                var status = $('#statusFilter').val();
                var table = $('#publicationsTable').DataTable();

                // Reset search
                table.search('').columns().search('').draw();

                // Apply filters
                if (category !== '') {
                    table.column(2).search(category, true, false);
                }
                if (status !== '') {
                    if (status === '1') {
                        table.column(5).search('Aktif', true, false);
                    } else {
                        table.column(5).search('Tidak Aktif', true, false);
                    }
                }

                table.draw();
            }

            // Delete functionality with SweetAlert
            $('.delete-btn').on('click', function() {
                var publicationId = $(this).data('id');
                var publicationTitle = $(this).data('title');

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
                        // Set form action and submit
                        $('#deleteForm').attr('action', `/publications/${publicationId}`);
                        $('#deleteForm').submit();
                    }
                });
            });

            // Form validation
            $('#addForm, form[id^="editForm"]').on('submit', function(e) {
                var form = $(this);
                var fileInput = form.find('input[type="file"]');
                var file = fileInput[0].files[0];

                if (file && file.size > 50 * 1024 * 1024) { // 50MB
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
        });
    </script>
@endpush
