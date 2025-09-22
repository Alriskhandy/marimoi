@extends('backend.partials.main', ['title' => 'Data Pengguna Download Publikasi'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-account-group"></i>
            </span> Data Pengguna Download Publikasi
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('publications.index') }}">Publikasi</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Data Download
                </li>
            </ul>
        </nav>
    </div>
    <div class="row mb-4">
        <div class="col-md-3 stretch-card grid-margin">
            <div class="card bg-gradient-primary card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Total Download
                        <i class="mdi mdi-download mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-5">{{ $downloads->total() }}</h2>
                    <h6 class="card-text">Data download publikasi</h6>
                </div>
            </div>
        </div>

        <div class="col-md-3 stretch-card grid-margin">
            <div class="card bg-gradient-success card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Publikasi Tersedia
                        <i class="mdi mdi-file-document-multiple mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-5">{{ $publications->count() }}</h2>
                    <h6 class="card-text">Total publikasi aktif</h6>
                </div>
            </div>
        </div>

        <div class="col-md-3 stretch-card grid-margin">
            <div class="card bg-gradient-info card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Download Hari Ini
                        <i class="mdi mdi-calendar-today mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-5">{{ $downloads->where('downloaded_at', '>=', today())->count() }}</h2>
                    <h6 class="card-text">Download hari ini</h6>
                </div>
            </div>
        </div>

        <div class="col-md-3 stretch-card grid-margin">
            <div class="card bg-gradient-warning card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle" />
                    <h4 class="font-weight-normal mb-3">
                        Organisasi Unik
                        <i class="mdi mdi-office-building mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-5">{{ $downloads->pluck('organization')->filter()->unique()->count() }}
                    </h2>
                    <h6 class="card-text">Instansi/organisasi</h6>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="card-title mb-0">
                    <i class="mdi mdi-chart-bar text-primary me-2"></i>
                    Data Pengguna Download Publikasi
                </h4>
                <button type="button" class="btn btn-gradient-success btn-rounded" onclick="exportData()">
                    <i class="mdi mdi-export me-1"></i>Export Data
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



            <!-- Filter Section -->
            <div class="card border-0 bg-light mb-4">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('publications.downloads.index') }}" id="filterForm">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label small text-muted mb-1">Publikasi</label>
                                <select class="form-select form-select-sm" name="publication_id">
                                    <option value="">Semua Publikasi</option>
                                    @foreach ($publications as $publication)
                                        <option value="{{ $publication->id }}"
                                            {{ request('publication_id') == $publication->id ? 'selected' : '' }}>
                                            {{ Str::limit($publication->title, 50) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label small text-muted mb-1">Tanggal Mulai</label>
                                <input type="date" class="form-control form-control-sm" name="start_date"
                                    value="{{ request('start_date') }}">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label small text-muted mb-1">Tanggal Akhir</label>
                                <input type="date" class="form-control form-control-sm" name="end_date"
                                    value="{{ request('end_date') }}">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label small text-muted mb-1">Organisasi</label>
                                <input type="text" class="form-control form-control-sm" name="organization"
                                    value="{{ request('organization') }}" placeholder="Cari organisasi...">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label small text-muted mb-1">Pencarian</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" name="search"
                                        value="{{ request('search') }}" placeholder="Cari nama, email, dll...">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="mdi mdi-magnify"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm me-2">
                                    <i class="mdi mdi-filter me-1"></i>Filter
                                </button>
                                <a href="{{ route('publications.downloads.index') }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="mdi mdi-refresh me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="downloadsTable">
                    <thead class="table-primary">
                        <tr>
                            <th style="width: 8%;" class="text-center">No</th>
                            <th style="width: 35%;">Publikasi</th>
                            <th style="width: 25%;">Nama Pengguna</th>
                            <th style="width: 20%;">Organisasi</th>
                            <th style="width: 12%;" class="text-center">Tanggal</th>
                            <th style="width: 8%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $no = ($downloads->currentPage() - 1) * $downloads->perPage() + 1;
                        @endphp
                        @forelse($downloads as $download)
                            <tr>
                                <td class="text-center fw-bold text-primary">{{ $no++ }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="file-icon me-3">
                                            @if ($download->publication->file_type === 'pdf')
                                                <i class="mdi mdi-file-pdf-box text-danger fs-4"></i>
                                            @elseif(in_array($download->publication->file_type, ['doc', 'docx']))
                                                <i class="mdi mdi-file-word-box text-primary fs-4"></i>
                                            @elseif(in_array($download->publication->file_type, ['xls', 'xlsx']))
                                                <i class="mdi mdi-file-excel-box text-success fs-4"></i>
                                            @elseif(in_array($download->publication->file_type, ['ppt', 'pptx']))
                                                <i class="mdi mdi-file-powerpoint-box text-warning fs-4"></i>
                                            @else
                                                <i class="mdi mdi-file-document-box text-secondary fs-4"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-semibold">
                                                {{ Str::limit($download->publication->title, 45) }}</h6>
                                            <small
                                                class="text-muted">{{ $download->publication->category ?? 'Tanpa Kategori' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-semibold">{{ $download->name }}</div>
                                        @if ($download->position)
                                            <small class="text-muted">{{ Str::limit($download->position, 25) }}</small>
                                        @endif
                                        @if ($download->email)
                                            <br><small class="text-info">{{ $download->email }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($download->organization)
                                        <div class="fw-medium">{{ Str::limit($download->organization, 25) }}</div>
                                        @if ($download->phone)
                                            <small class="text-muted">{{ $download->phone }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="text-nowrap">
                                        <div class="fw-semibold">{{ $download->downloaded_at->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $download->downloaded_at->format('H:i') }}</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- View Detail Button -->
                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                                            data-bs-target="#viewModal{{ $download->id }}" title="Detail">
                                            <i class="mdi mdi-information"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn"
                                            data-id="{{ $download->id }}" data-name="{{ $download->name }}"
                                            title="Hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </div>
                                </td>

                            </tr>

                            <!-- View Detail Modal -->
                            <div class="modal fade" id="viewModal{{ $download->id }}" tabindex="-1"
                                aria-labelledby="viewModalLabel{{ $download->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title" id="viewModalLabel{{ $download->id }}">
                                                <i class="mdi mdi-information me-2"></i>Detail Data Download
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
                                                                <div class="col-md-12 mb-2">
                                                                    <label class="form-label small text-muted">Judul
                                                                        Publikasi</label>
                                                                    <p class="mb-0 fw-semibold">
                                                                        {{ $download->publication->title }}</p>
                                                                </div>
                                                                <div class="col-md-6 mb-2">
                                                                    <label
                                                                        class="form-label small text-muted">Kategori</label>
                                                                    <p class="mb-0">
                                                                        {{ $download->publication->category ?? '-' }}</p>
                                                                </div>
                                                                <div class="col-md-6 mb-2">
                                                                    <label class="form-label small text-muted">Ukuran
                                                                        File</label>
                                                                    <p class="mb-0">
                                                                        {{ number_format($download->publication->file_size / 1024 / 1024, 2) }}
                                                                        MB</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="card border-0 bg-light">
                                                        <div class="card-body">
                                                            <h6 class="card-title">Informasi Pengunduh</h6>
                                                            <div class="row">
                                                                <div class="col-md-6 mb-2">
                                                                    <label class="form-label small text-muted">Nama</label>
                                                                    <p class="mb-0">{{ $download->name }}</p>
                                                                </div>
                                                                <div class="col-md-6 mb-2">
                                                                    <label
                                                                        class="form-label small text-muted">Email</label>
                                                                    <p class="mb-0">{{ $download->email }}</p>
                                                                </div>
                                                                <div class="col-md-6 mb-2">
                                                                    <label
                                                                        class="form-label small text-muted">Telepon</label>
                                                                    <p class="mb-0">{{ $download->phone ?? '-' }}</p>
                                                                </div>
                                                                <div class="col-md-6 mb-2">
                                                                    <label
                                                                        class="form-label small text-muted">Organisasi</label>
                                                                    <p class="mb-0">{{ $download->organization ?? '-' }}
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6 mb-2">
                                                                    <label
                                                                        class="form-label small text-muted">Posisi</label>
                                                                    <p class="mb-0">{{ $download->position ?? '-' }}</p>
                                                                </div>
                                                                <div class="col-md-6 mb-2">
                                                                    <label
                                                                        class="form-label small text-muted">Tujuan</label>
                                                                    <p class="mb-0">{{ $download->purpose ?? '-' }}</p>
                                                                </div>
                                                                <div class="col-md-6 mb-2">
                                                                    <label class="form-label small text-muted">IP
                                                                        Address</label>
                                                                    <p class="mb-0">
                                                                        <code>{{ $download->ip_address }}</code>
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-6 mb-2">
                                                                    <label class="form-label small text-muted">Tanggal
                                                                        Download</label>
                                                                    <p class="mb-0">
                                                                        {{ $download->downloaded_at->format('d F Y H:i:s') }}
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
                                            <a href="{{ route('publications.download', $download->publication->id) }}"
                                                class="btn btn-primary" target="_blank">
                                                <i class="mdi mdi-download me-1"></i>Download File
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="mdi mdi-download" style="font-size: 4rem; opacity: 0.3;"></i>
                                        <h5 class="mt-3 mb-2">Belum ada data download</h5>
                                        <p>Data download publikasi akan ditampilkan di sini ketika ada pengguna yang
                                            mendownload publikasi.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if (method_exists($downloads, 'links'))
                <div class="d-flex justify-content-center mt-4">
                    {{ $downloads->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Hidden Forms -->
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <form id="exportForm" action="{{ route('publications.downloads.export') }}" method="GET" style="display: none;">
        <input type="hidden" name="publication_id" value="{{ request('publication_id') }}">
        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
        <input type="hidden" name="end_date" value="{{ request('end_date') }}">
        <input type="hidden" name="organization" value="{{ request('organization') }}">
        <input type="hidden" name="search" value="{{ request('search') }}">
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

        .download-checkbox {
            cursor: pointer;
        }

        .file-icon {
            transition: transform 0.3s ease;
        }

        .file-icon:hover {
            transform: scale(1.1);
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
        $(document).ready(function() {
            // Delete functionality
            $('.delete-btn').on('click', function() {
                const downloadId = $(this).data('id');
                const downloadName = $(this).data('name');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: `Data download "${downloadName}" akan dihapus permanen!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deleteForm').attr('action',
                            `/dashboard/publications/downloads/${downloadId}`);
                        $('#deleteForm').submit();
                    }
                });
            });

            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });

        function exportData() {
            $('#exportForm').submit();
        }
    </script>
@endpush
