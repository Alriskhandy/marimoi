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

                    <!-- Search and Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="mdi mdi-magnify"></i>
                                </span>
                                <input type="text" id="searchInput" class="form-control"
                                    placeholder="Cari nama kategori atau deskripsi...">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-6">
                                    <select id="filterOPD" class="form-select">
                                        <option value="">Semua OPD</option>
                                        <option value="with">Dengan OPD</option>
                                        <option value="without">Tanpa OPD (Umum)</option>
                                        @foreach ($opdList as $opd)
                                            <option value="{{ $opd->id }}">{{ $opd->singkatan }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select id="filterDeskripsi" class="form-select">
                                        <option value="">Semua Deskripsi</option>
                                        <option value="with">Dengan Deskripsi</option>
                                        <option value="without">Tanpa Deskripsi</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search Results Info -->
                    <div id="searchInfo" class="d-none mb-3">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="mdi mdi-information-outline me-2"></i>
                            <span id="searchResultText"></span>
                            <button type="button" class="btn btn-sm btn-outline-info ms-auto" id="resetFilters">
                                Reset Filter
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="kategoriTable">
                            <thead>
                                <tr>
                                    <th class="text-dark text-center" style="width: 50px; min-width: 50px;">No</th>
                                    <th class="text-dark" style="min-width: 200px;">Nama Kategori</th>
                                    <th class="text-dark" style="min-width: 150px;">OPD</th>
                                    <th class="text-dark" style="min-width: 250px;">Deskripsi</th>
                                    <th class="text-dark text-center" style="width: 120px; min-width: 120px;">Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="kategoriTableBody">
                                @forelse($kategoriAspirasi as $index => $kategori)
                                    <tr class="kategori-row" data-nama="{{ strtolower($kategori->nama_kategori) }}"
                                        data-deskripsi="{{ strtolower($kategori->deskripsi ?? '') }}"
                                        data-opd-id="{{ $kategori->opd_id ?? '' }}"
                                        data-opd-nama="{{ strtolower($kategori->opd->name ?? '') }}"
                                        data-has-opd="{{ $kategori->opd ? 'true' : 'false' }}"
                                        data-has-deskripsi="{{ $kategori->deskripsi ? 'true' : 'false' }}">
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>
                                            <strong class="kategori-name">{{ $kategori->nama_kategori }}</strong>
                                        </td>
                                        <td>
                                            @if ($kategori->opd)
                                                <div class="d-flex flex-column">
                                                    <strong class="text-info">{{ $kategori->opd->singkatan }}</strong>
                                                    <small class="text-muted">{{ $kategori->opd->name }}</small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">Umum</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($kategori->deskripsi)
                                                <span title="{{ $kategori->deskripsi }}" class="text-truncate d-block"
                                                    style="max-width: 250px;">
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
                                                @if ($kategori->id != 1)
                                                    <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                        data-id="{{ $kategori->id }}"
                                                        data-opd-id="{{ $kategori->opd_id }}"
                                                        data-nama="{{ $kategori->nama_kategori }}"
                                                        data-kode="{{ $kategori->kode_kategori }}"
                                                        data-icon="{{ $kategori->icon }}"
                                                        data-deskripsi="{{ $kategori->deskripsi }}"
                                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                                        title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger btn-delete"
                                                        data-id="{{ $kategori->id }}"
                                                        onclick="deleteKategori({{ $kategori->id }})" title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-data-row">
                                        <td colspan="5" class="text-center">
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

                        <!-- No search results row -->
                        <div id="no-search-results" class="d-none text-center py-4">
                            <i class="mdi mdi-magnify-close mdi-48px text-muted"></i>
                            <p class="text-muted mt-2">Tidak ada hasil yang ditemukan</p>
                            <p class="text-muted small">Coba gunakan kata kunci yang berbeda atau reset filter</p>
                        </div>

                        <!-- Scroll indicator for mobile -->
                        <div class="scroll-indicator d-md-none">
                            <div class="d-flex justify-content-center align-items-center py-2">
                                <i class="mdi mdi-gesture-swipe-horizontal text-muted me-2"></i>
                                <small class="text-muted">Geser tabel ke kiri/kanan untuk melihat lebih banyak</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="addForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="addModalLabel">
                            <i class="mdi mdi-plus me-2"></i> Tambah Kategori Aspirasi
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body row">
                        <div class="col-md-6 mb-3">
                            <label for="add_nama_kategori" class="form-label">Nama Kategori <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="nama_kategori" id="add_nama_kategori" class="form-control"
                                placeholder="Contoh: Infrastruktur">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_opd_id" class="form-label">OPD</label>
                            <select name="opd_id" id="add_opd_id" class="form-control">
                                <option value="">-- Kategori Umum --</option>
                                @foreach ($opdList as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->singkatan }} - {{ $opd->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            <div class="form-text">Pilih OPD yang menangani kategori ini atau biarkan kosong untuk kategori
                                umum</div>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="add_deskripsi" class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" id="add_deskripsi" class="form-control" rows="3"
                                placeholder="Deskripsi singkat tentang kategori aspirasi ini..."></textarea>
                            <div class="invalid-feedback"></div>
                            <div class="form-text">Opsional - Jelaskan cakupan kategori aspirasi ini</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save"></i> Simpan
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="mdi mdi-close"></i> Batal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Show Modal -->
    <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient-info text-white">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="mdi mdi-eye me-2"></i> Detail Kategori Aspirasi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Nama Kategori:</strong>
                            <p id="show_nama_kategori" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>OPD:</strong>
                            <p id="show_opd" class="text-muted"></p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Deskripsi:</strong>
                        <div id="show_deskripsi" class="text-muted p-3 bg-light rounded mt-2" style="min-height: 80px;">
                            Tidak ada deskripsi</div>
                    </div>

                    <div class="row">
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
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="editForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-gradient-success text-white">
                        <h5 class="modal-title" id="editModalLabel">
                            <i class="mdi mdi-pencil me-2"></i> Edit Kategori Aspirasi
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="col-md-6 mb-3">
                            <label for="edit_nama_kategori" class="form-label">Nama Kategori <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_kategori" id="edit_nama_kategori">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_opd_id" class="form-label">OPD</label>
                            <select name="opd_id" id="edit_opd_id" class="form-control">
                                <option value="">-- Kategori Umum --</option>
                                @foreach ($opdList as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->singkatan }} - {{ $opd->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="mdi mdi-content-save"></i> Update
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="mdi mdi-close"></i> Batal
                        </button>
                    </div>
                </div>
            </form>
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

            // Add Modal
            $('#addModal').on('show.bs.modal', function() {
                const form = $('#addForm');
                form[0].reset();
                clearFormErrors(form);
            });

            // Add Form Submit
            $('#addForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const formData = new FormData(this);
                const submitBtn = form.find('button[type="submit"]');

                // Reset validasi
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');

                submitBtn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin"></i> Menyimpan...');

                $.ajax({
                    url: "{{ route('kategori-aspirasi.store') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#addModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            setTimeout(() => location.reload(), 1500);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, message) {
                                const input = form.find(`[name="${field}"]`);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(message[0]);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan server.'
                            });
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
                $('#edit_nama_kategori').val($(this).data('nama'));
                $('#edit_opd_id').val($(this).data('opd-id'));
                $('#edit_deskripsi').val($(this).data('deskripsi'));

                clearFormErrors(form);
            });

            // Edit Form Submit
            $('#editForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const id = $('#edit_id').val();
                const formData = new FormData(this);
                formData.append('_method', 'PUT');
                const submitBtn = form.find('button[type="submit"]');

                submitBtn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin"></i> Mengupdate...');

                $.ajax({
                    url: "{{ route('kategori-aspirasi.update', ['kategori_aspirasi' => '__ID__']) }}"
                        .replace('__ID__', id),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#editModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => location.reload());
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
                    url: "{{ route('kategori-aspirasi.show', ['kategori_aspirasi' => '__ID__']) }}"
                        .replace('__ID__', id),
                    type: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        const kategori = response.data || response;

                        $('#show_nama_kategori').text(kategori.nama_kategori);
                        $('#show_opd').text(kategori.opd ?
                            `${kategori.opd.singkatan} - ${kategori.opd.name}` :
                            'Kategori Umum');
                        $('#show_deskripsi').text(kategori.deskripsi || 'Tidak ada deskripsi');
                        $('#show_created_at').text(new Date(kategori.created_at)
                            .toLocaleDateString('id-ID'));
                        $('#show_updated_at').text(new Date(kategori.updated_at)
                            .toLocaleDateString('id-ID'));

                        const modal = new bootstrap.Modal(document.getElementById('showModal'));
                        modal.show();
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
                            url: "{{ route('kategori-aspirasi.destroy', ['kategori_aspirasi' => '__ID__']) }}"
                                .replace('__ID__', id),
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

            // Search and Filter Functions
            let searchTimeout;

            function performSearch() {
                const searchTerm = $('#searchInput').val().toLowerCase().trim();
                const opdFilter = $('#filterOPD').val();
                const deskripsiFilter = $('#filterDeskripsi').val();

                let visibleCount = 0;
                const $rows = $('.kategori-row');

                $rows.each(function() {
                    const $row = $(this);
                    const nama = $row.data('nama');
                    const deskripsi = $row.data('deskripsi');
                    const opdId = $row.data('opd-id').toString();
                    const opdNama = $row.data('opd-nama');
                    const hasOpd = $row.data('has-opd').toString();
                    const hasDeskripsi = $row.data('has-deskripsi').toString();

                    let showRow = true;

                    // Search filter
                    if (searchTerm) {
                        showRow = nama.includes(searchTerm) ||
                            deskripsi.includes(searchTerm) ||
                            opdNama.includes(searchTerm);
                    }

                    // OPD filter
                    if (showRow && opdFilter) {
                        if (opdFilter === 'with' && hasOpd !== 'true') showRow = false;
                        if (opdFilter === 'without' && hasOpd !== 'false') showRow = false;
                        if (opdFilter !== 'with' && opdFilter !== 'without' && opdId !== opdFilter)
                            showRow = false;
                    }

                    // Deskripsi filter
                    if (showRow && deskripsiFilter) {
                        if (deskripsiFilter === 'with' && hasDeskripsi !== 'true') showRow = false;
                        if (deskripsiFilter === 'without' && hasDeskripsi !== 'false') showRow = false;
                    }

                    if (showRow) {
                        $row.show();
                        visibleCount++;
                    } else {
                        $row.hide();
                    }
                });

                // Update search info
                updateSearchInfo(searchTerm, opdFilter, deskripsiFilter, visibleCount);

                // Show/hide no results message
                if (visibleCount === 0 && (searchTerm || opdFilter || deskripsiFilter)) {
                    $('#no-search-results').removeClass('d-none');
                    $('#no-data-row').addClass('d-none');
                } else {
                    $('#no-search-results').addClass('d-none');
                    if (visibleCount === 0 && !searchTerm && !opdFilter && !deskripsiFilter) {
                        $('#no-data-row').removeClass('d-none');
                    } else {
                        $('#no-data-row').addClass('d-none');
                    }
                }

                // Update row numbers for visible rows
                updateRowNumbers();
            }

            function updateSearchInfo(searchTerm, opdFilter, deskripsiFilter, visibleCount) {
                const totalRows = $('.kategori-row').length;
                let infoText = '';

                if (searchTerm || opdFilter || deskripsiFilter) {
                    infoText = `Menampilkan ${visibleCount} dari ${totalRows} kategori aspirasi`;

                    const filters = [];
                    if (searchTerm) filters.push(`pencarian: "${searchTerm}"`);
                    if (opdFilter) {
                        if (opdFilter === 'with') filters.push('OPD: dengan OPD');
                        else if (opdFilter === 'without') filters.push('OPD: tanpa OPD');
                        else {
                            const opdName = $('#filterOPD option:selected').text();
                            filters.push(`OPD: ${opdName}`);
                        }
                    }
                    if (deskripsiFilter) {
                        filters.push(
                            `deskripsi: ${deskripsiFilter === 'with' ? 'dengan deskripsi' : 'tanpa deskripsi'}`);
                    }

                    if (filters.length > 0) {
                        infoText += ` (${filters.join(', ')})`;
                    }

                    $('#searchResultText').text(infoText);
                    $('#searchInfo').removeClass('d-none');
                } else {
                    $('#searchInfo').addClass('d-none');
                }
            }

            function updateRowNumbers() {
                let counter = 1;
                $('.kategori-row:visible').each(function() {
                    $(this).find('td:first-child').text(counter++);
                });
            }

            function resetAllFilters() {
                $('#searchInput').val('');
                $('#filterOPD').val('');
                $('#filterDeskripsi').val('');
                performSearch();
            }

            // Search event handlers
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            });

            $('#filterOPD, #filterDeskripsi').on('change', performSearch);

            $('#clearSearch').on('click', function() {
                $('#searchInput').val('');
                performSearch();
            });

            $('#resetFilters').on('click', resetAllFilters);

            // Handle Enter key in search
            $('#searchInput').on('keypress', function(e) {
                if (e.which === 13) {
                    clearTimeout(searchTimeout);
                    performSearch();
                }
            });

            // Mobile responsive enhancements
            function handleMobileView() {
                const isMobile = window.innerWidth < 768;

                if (isMobile) {
                    // Adjust modal size for mobile
                    $('.modal-dialog').addClass('modal-fullscreen-sm-down');
                } else {
                    $('.modal-dialog').removeClass('modal-fullscreen-sm-down');
                }
            }

            // Call on load and resize
            handleMobileView();
            $(window).on('resize', handleMobileView);

            // Enhanced scroll functionality for table
            function initTableScroll() {
                const $tableResponsive = $('.table-responsive');

                if ($tableResponsive.length) {
                    // Add scroll shadow effect
                    $tableResponsive.on('scroll', function() {
                        const scrollLeft = $(this).scrollLeft();
                        const maxScrollLeft = this.scrollWidth - this.clientWidth;

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

                    // Touch scroll enhancement for mobile
                    let isScrolling = false;

                    $tableResponsive.on('touchstart', function() {
                        isScrolling = true;
                    });

                    $tableResponsive.on('touchend', function() {
                        setTimeout(() => {
                            isScrolling = false;
                        }, 100);
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

            // Reinitialize on window resize
            $(window).on('resize', function() {
                setTimeout(initTableScroll, 100);
            });

            // Enhanced search with scroll to result
            function scrollToFirstResult() {
                const $firstVisible = $('.kategori-row:visible').first();
                if ($firstVisible.length) {
                    $firstVisible.addClass('highlight');
                    setTimeout(() => {
                        $firstVisible.removeClass('highlight');
                    }, 2000);
                }
            }

            // Update search to include scroll to result
            const originalPerformSearch = performSearch;
            performSearch = function() {
                originalPerformSearch();

                // Scroll to first result if search term exists
                const searchTerm = $('#searchInput').val().toLowerCase().trim();
                if (searchTerm && $('.kategori-row:visible').length > 0) {
                    setTimeout(scrollToFirstResult, 100);
                }
            };
        });
    </script>

    <style>
        /* Custom styles for kategori aspirasi */
        .table-responsive {
            border-radius: 10px;
            overflow-x: auto;
            overflow-y: visible;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            -webkit-overflow-scrolling: touch;
        }

        .table {
            min-width: 720px;
            /* Minimum width untuk memastikan tabel tidak terlalu sempit */
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

        /* Kolom yang bisa wrap text */
        .table td:nth-child(2),
        /* Nama Kategori */
        .table td:nth-child(4) {
            /* Deskripsi */
            white-space: normal;
            word-wrap: break-word;
        }

        /* Custom scrollbar untuk table-responsive */
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

        /* Search and filter styling */
        .input-group-text {
            border: 1px solid #ced4da;
        }

        #searchInput:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Mobile optimizations */
        @media (max-width: 767px) {
            .table-responsive {
                border-radius: 8px;
                box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            }

            .table {
                min-width: 600px;
                /* Minimum width untuk mobile */
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
            }
        }

        /* Tablet responsive adjustments */
        @media (min-width: 768px) and (max-width: 991px) {
            .table {
                min-width: 800px;
            }

            .table th,
            .table td {
                padding: 0.75rem;
                font-size: 0.875rem;
            }
        }

        /* Large screen optimizations */
        @media (min-width: 1200px) {
            .table-responsive {
                border-radius: 12px;
            }

            .table {
                min-width: auto;
                /* Tidak perlu min-width di desktop */
            }
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

        .btn-gradient-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            border: none;
            color: white;
        }

        .btn-gradient-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }

        /* Enhanced mobile modal */
        @media (max-width: 576px) {
            .modal-dialog.modal-fullscreen-sm-down {
                width: 100vw;
                height: 100vh;
                margin: 0;
                border-radius: 0;
            }

            .modal-fullscreen-sm-down .modal-content {
                height: 100vh;
                border-radius: 0;
            }

            .modal-fullscreen-sm-down .modal-body {
                overflow-y: auto;
                flex: 1;
            }
        }

        /* Statistics cards hover effect */
        .card.card-img-holder:hover {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* Form validation styling */
        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .invalid-feedback {
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        /* Alert styling */
        .alert {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Table row hover effect */
        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
            transition: background-color 0.2s ease;
        }

        /* Button styling */
        .btn-outline-info:hover,
        .btn-outline-success:hover,
        .btn-outline-danger:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }

        /* Loading spinner */
        .mdi-spin {
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

        /* Search highlight effect */
        .table tbody tr.highlight {
            background-color: rgba(255, 193, 7, 0.2);
            animation: highlight-fade 2s ease-out;
        }

        @keyframes highlight-fade {
            0% {
                background-color: rgba(255, 193, 7, 0.4);
            }

            100% {
                background-color: transparent;
            }
        }

        /* No results styling */
        #no-search-results {
            padding: 2rem;
            color: #6c757d;
        }

        /* Kategori name highlighting */
        .kategori-name {
            color: #2c3e50;
            font-weight: 600;
        }

        /* OPD badge styling */
        .badge.bg-info {
            background-color: #17a2b8 !important;
        }

        .badge.bg-secondary {
            background-color: #6c757d !important;
        }

        /* Smooth transitions */
        .kategori-row {
            transition: all 0.3s ease;
        }

        .kategori-row.hiding {
            opacity: 0;
            transform: translateX(-10px);
        }

        .kategori-row.showing {
            opacity: 1;
            transform: translateX(0);
        }

        /* Better responsive grid for filters */
        @media (max-width: 575px) {
            .filter-row .col-6 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 0.5rem;
            }

            .card-body {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.25rem;
            }

            .breadcrumb {
                font-size: 0.875rem;
                margin-bottom: 0;
            }
        }

        /* Text truncation with tooltip */
        .text-truncate {
            cursor: help;
        }

        /* Sticky scroll shadow effect */
        .table-responsive::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 30px;
            background: linear-gradient(to left, rgba(0, 0, 0, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 1;
        }

        .table-responsive.scrolled::before {
            opacity: 1;
        }

        /* Enhanced button group spacing */
        .btn-group .btn {
            border-radius: 0.25rem !important;
            margin-right: 0.125rem;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        /* Improved table cell alignment */
        .table td:first-child,
        .table td:last-child {
            text-align: center;
        }

        /* Enhanced scroll indicator animation */
        .scroll-indicator {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        /* Hide scroll indicator after user interaction */
        .table-responsive.user-scrolled .scroll-indicator {
            display: none;
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

        .btn-gradient-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            border: none;
            color: white;
        }

        .btn-gradient-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }

        /* Enhanced mobile modal */
        @media (max-width: 576px) {
            .modal-dialog.modal-fullscreen-sm-down {
                width: 100vw;
                height: 100vh;
                margin: 0;
                border-radius: 0;
            }

            .modal-fullscreen-sm-down .modal-content {
                height: 100vh;
                border-radius: 0;
            }

            .modal-fullscreen-sm-down .modal-body {
                overflow-y: auto;
                flex: 1;
            }
        }

        /* Statistics cards hover effect */
        .card.card-img-holder:hover {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        /* Form validation styling */
        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .invalid-feedback {
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        /* Alert styling */
        .alert {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Table row hover effect */
        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
            transition: background-color 0.2s ease;
        }

        /* Button styling */
        .btn-outline-info:hover,
        .btn-outline-success:hover,
        .btn-outline-danger:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }

        /* Loading spinner */
        .mdi-spin {
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

        /* Search highlight effect */
        .table tbody tr.highlight {
            background-color: rgba(255, 193, 7, 0.2);
            animation: highlight-fade 2s ease-out;
        }

        @keyframes highlight-fade {
            0% {
                background-color: rgba(255, 193, 7, 0.4);
            }

            100% {
                background-color: transparent;
            }
        }

        /* No results styling */
        #no-search-results {
            padding: 2rem;
            color: #6c757d;
        }

        /* Enhanced button group for mobile */
        @media (max-width: 767px) {
            .btn-group-vertical {
                width: 100%;
            }

            .btn-group-vertical .btn {
                width: 100%;
                margin-bottom: 0.25rem;
                border-radius: 0.375rem !important;
            }

            .btn-group-vertical .btn:last-child {
                margin-bottom: 0;
            }
        }

        /* Improved table cell content */
        .table td {
            word-wrap: break-word;
        }

        .table td.text-center {
            max-width: none;
        }

        /* Kategori name highlighting */
        .kategori-name {
            color: #2c3e50;
            font-weight: 600;
        }

        /* OPD badge styling */
        .badge.bg-info {
            background-color: #17a2b8 !important;
        }

        .badge.bg-secondary {
            background-color: #6c757d !important;
        }

        /* Smooth transitions */
        .kategori-row {
            transition: all 0.3s ease;
        }

        .kategori-row.hiding {
            opacity: 0;
            transform: translateX(-10px);
        }

        .kategori-row.showing {
            opacity: 1;
            transform: translateX(0);
        }

        /* Better responsive grid for filters */
        @media (max-width: 575px) {
            .filter-row .col-6 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 0.5rem;
            }
        }

        /* Enhanced card responsiveness */
        @media (max-width: 575px) {
            .stretch-card {
                margin-bottom: 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.25rem;
            }

            .breadcrumb {
                font-size: 0.875rem;
                margin-bottom: 0;
            }
        }

        /* Text truncation for long content */
        .text-truncate-mobile {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 200px;
        }

        @media (max-width: 767px) {
            .text-truncate-mobile {
                max-width: 150px;
            }
        }

        /* Improved scrollbar */
        @media (max-width: 991px) {
            .table-responsive {
                -webkit-overflow-scrolling: touch;
            }

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
            }

            .table-responsive::-webkit-scrollbar-thumb:hover {
                background: #a8a8a8;
            }
        }
    </style>
@endsection
