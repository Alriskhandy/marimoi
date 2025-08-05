@extends('backend.partials.main', ['title' => 'Kategori Aspirasi'])

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-tag-multiple"></i>
            </span>
            Kategori Aspirasi
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Kategori Aspirasi
                </li>
            </ul>
        </nav>
    </div>

    <!-- Statistics Cards -->
    @if ($kategoriAspirasi->count() > 0)
        <div class="row mb-4">
            <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
                <div class="card bg-gradient-primary card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Total Kategori
                            <i class="mdi mdi-tag-multiple mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['total'] }}</h2>
                        <h6 class="card-text">Semua kategori aspirasi</h6>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
                <div class="card bg-gradient-success card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Dengan OPD
                            <i class="mdi mdi-office-building mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['dengan_opd'] }}</h2>
                        <h6 class="card-text">Kategori yang memiliki OPD</h6>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
                <div class="card bg-gradient-info card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Tanpa OPD
                            <i class="mdi mdi-help-circle-outline mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['tanpa_opd'] }}</h2>
                        <h6 class="card-text">Kategori umum</h6>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
                <div class="card bg-gradient-warning card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Dengan Icon
                            <i class="mdi mdi-star-outline mdi-24px float-end"></i>
                        </h4>
                        {{-- <h2 class="mb-4">{{ $stats['dengan_icon'] }}</h2> --}}
                        <h6 class="card-text">Kategori dengan icon</h6>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">
                            <i class="mdi mdi-tag-multiple"></i>
                            Daftar Kategori Aspirasi
                        </h4>
                        <div>
                            <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                data-bs-target="#addModal">
                                <i class="mdi mdi-plus"></i> Tambah Kategori
                            </button>
                        </div>
                    </div>

                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th class="text-dark text-center" style="width: 50px;">No</th>
                                    <th class="text-dark" style="min-width: 200px;">Nama Kategori</th>
                                    <th class="text-dark" style="min-width: 150px;">OPD</th>
                                    <th class="text-dark" style="min-width: 250px;">Deskripsi</th>
                                    <th class="text-dark text-center" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($kategoriAspirasi as $index => $kategori)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $kategori->nama_kategori }}</strong>
                                        </td>
                                        <td>
                                            @if ($kategori->opd)
                                                <div class="d-flex flex-column">
                                                    <strong class="text-info">{{ $kategori->opd->singkatan }}</strong>
                                                    <small class="text-muted">{{ $kategori->opd->nama }}</small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">Umum</span>
                                            @endif
                                        <td>
                                            @if ($kategori->deskripsi)
                                                <span title="{{ $kategori->deskripsi }}">
                                                    {{ Str::limit($kategori->deskripsi, 50) }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show"
                                                    data-id="{{ $kategori->id }}" title="Lihat Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                    data-id="{{ $kategori->id }}" data-opd-id="{{ $kategori->opd_id }}"
                                                    data-nama="{{ $kategori->nama_kategori }}"
                                                    data-kode="{{ $kategori->kode_kategori }}"
                                                    data-icon="{{ $kategori->icon }}"
                                                    data-deskripsi="{{ $kategori->deskripsi }}" data-bs-toggle="modal"
                                                    data-bs-target="#editModal" title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                                    data-id="{{ $kategori->id }}"
                                                    onclick="deleteKategori({{ $kategori->id }})" title="Hapus">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-data-row">
                                        <td colspan="7" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-tag-multiple-outline mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Belum ada kategori aspirasi</p>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#addModal">
                                                    <i class="mdi mdi-plus"></i> Tambah Kategori Pertama
                                                </button>
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

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="mdi mdi-plus me-2"></i>
                        Tambah Kategori Aspirasi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Kode Kategori:</strong>
                            <p id="show_kode_kategori" class="text-primary font-weight-bold"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Nama Kategori:</strong>
                            <p id="show_nama_kategori" class="text-muted"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>OPD:</strong>
                            <p id="show_opd" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Icon:</strong>
                            <div id="show_icon_container" class="mt-2">
                                <span class="text-muted">Tidak ada icon</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <strong>Deskripsi:</strong>
                            <div id="show_deskripsi" class="text-muted p-3 bg-light rounded mt-2"
                                style="min-height: 80px;">
                                Tidak ada deskripsi
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>Dibuat pada:</strong>
                            <p id="show_created_at" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Terakhir diubah:</strong>
                            <p id="show_updated_at" class="text-muted"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close"></i> Tutup
                    </button>
                </div>
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

            // Icon preview functionality
            $('#add_icon').on('change', function() {
                const iconClass = $(this).val();
                if (iconClass) {
                    $('#add_iconPreview').html(`
                <div class="d-flex align-items-center justify-content-center">
                    <i class="${iconClass} fa-3x text-primary me-3"></i>
                    <div>
                        <div><strong>Preview</strong></div>
                        <small class="text-muted">${iconClass}</small>
                    </div>
                </div>
            `);
                } else {
                    $('#add_iconPreview').html(
                        '<span class="text-muted">Pilih icon untuk melihat pratinjau</span>');
                }
            });

            $('#edit_icon').on('change', function() {
                const iconClass = $(this).val();
                if (iconClass) {
                    $('#edit_iconPreview').html(`
                <div class="d-flex align-items-center justify-content-center">
                    <i class="${iconClass} fa-3x text-primary me-3"></i>
                    <div>
                        <div><strong>Preview</strong></div>
                        <small class="text-muted">${iconClass}</small>
                    </div>
                </div>
            `);
                } else {
                    $('#edit_iconPreview').html(
                        '<span class="text-muted">Pilih icon untuk melihat pratinjau</span>');
                }
            });

            // Generate kode kategori
            $('#generateKodeBtn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i>');

                $.ajax({
                    url: '{{ route('kategori-aspirasi.generateKode') }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            $('#add_kode_kategori').val(response.kode);
                            showAlert('Kode kategori berhasil digenerate: ' + response.kode,
                                'info');
                        }
                    },
                    error: function() {
                        showAlert('Gagal generate kode kategori', 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="mdi mdi-refresh"></i>');
                    }
                });
            });

            // Add Modal
            $('#addModal').on('show.bs.modal', function() {
                const form = $('#addForm');
                form[0].reset();
                clearFormErrors(form);
                $('#add_iconPreview').html(
                    '<span class="text-muted">Pilih icon untuk melihat pratinjau</span>');
            });

            // Add Form Submit
            $('#addForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = new FormData(this);

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
                        if (response.success) {
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
                        submitBtn.prop('disabled', false).html(
                            '<i class="mdi mdi-content-save"></i> Simpan');
                    }
                });
            });

            // Edit Modal
            $(document).on('click', '.btn-edit', function() {
                const form = $('#editForm');
                const id = $(this).data('id');

                $('#edit_id').val(id);
                $('#edit_kode_kategori').val($(this).data('kode'));
                $('#edit_nama_kategori').val($(this).data('nama'));
                $('#edit_opd_id').val($(this).data('opd-id'));
                $('#edit_icon').val($(this).data('icon'));
                $('#edit_deskripsi').val($(this).data('deskripsi'));

                // Trigger icon preview
                $('#edit_icon').trigger('change');

                clearFormErrors(form);
            });

            // Edit Form Submit
            $('#editForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const id = $('#edit_id').val();
                const formData = new FormData(this);

                const submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin"></i> Mengupdate...');

                $.ajax({
                    url: `/kategori-aspirasi/${id}`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#editModal').modal('hide');
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
                        submitBtn.prop('disabled', false).html(
                            '<i class="mdi mdi-content-save"></i> Update');
                    }
                });
            });

            // Show Modal - Load data via AJAX
            $(document).on('click', '.btn-show', function() {
                const id = $(this).data('id');

                $.ajax({
                    url: `/kategori-aspirasi/${id}`,
                    type: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        const kategori = response.data || response;

                        $('#show_kode_kategori').text(kategori.kode_kategori);
                        $('#show_nama_kategori').text(kategori.nama_kategori);
                        $('#show_opd').text(kategori.opd ?
                            `${kategori.opd.singkatan} - ${kategori.opd.nama}` :
                            'Kategori Umum');
                        $('#show_deskripsi').text(kategori.deskripsi || 'Tidak ada deskripsi');
                        $('#show_created_at').text(new Date(kategori.created_at)
                            .toLocaleDateString('id-ID'));
                        $('#show_updated_at').text(new Date(kategori.updated_at)
                            .toLocaleDateString('id-ID'));

                        // Icon
                        if (kategori.icon) {
                            $('#show_icon_container').html(`
                        <div class="d-flex align-items-center">
                            <i class="${kategori.icon} fa-2x text-primary me-3"></i>
                            <small class="text-muted">${kategori.icon}</small>
                        </div>
                    `);
                        } else {
                            $('#show_icon_container').html(
                                '<span class="text-muted">Tidak ada icon</span>');
                        }

                        $('#showModal').modal('show');
                    },
                    error: function(xhr) {
                        console.error('Error loading kategori details:', xhr);
                        showAlert('Gagal memuat data detail: ' + (xhr.responseJSON?.message ||
                            'Unknown error'), 'error');
                    }
                });
            });

            // Delete function
            window.deleteKategori = function(id) {
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Kategori aspirasi akan dihapus permanen!",
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
                            url: `/kategori-aspirasi/${id}`,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: response.message,
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });

                                    setTimeout(function() {
                                        location.reload();
                                    }, 1500);
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
        /* Custom styles for kategori aspirasi */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .table th {
            color: #000000 !important;
            border: none;
            font-weight: 600;
            white-space: nowrap;
        }

        .table td {
            border-color: #e9ecef;
            vertical-align: middle;
        }

        /* .table tbody tr:hover {
                    background-color: #f9f8fa;
                } */

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

        .badge {
            font-size: 0.75em;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
        }

        .btn-group .btn {
            margin-right: 2px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
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

        .btn-gradient-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }

        #add_iconPreview,
        #edit_iconPreview {
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #f8f9fa;
        }

        .fa-2x {
            font-size: 2em;
        }

        .fa-3x {
            font-size: 3em;
        }

        .font-weight-bold {
            font-weight: 600 !important;
        }
    </style>
@endsection
