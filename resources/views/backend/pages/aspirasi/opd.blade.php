@extends('backend.partials.main', ['title' => 'Data OPD'])

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-office-building"></i>
            </span>
            Data Organisasi Perangkat Daerah (OPD)
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Data OPD
                </li>
            </ul>
        </nav>
    </div>

    <!-- Statistics Cards -->
    @if ($opdList->count() > 0)
        <div class="row mb-4">
            <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
                <div class="card bg-gradient-primary card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Total OPD
                            <i class="mdi mdi-office-building mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['total'] ?? $opdList->count() }}</h2>
                        <h6 class="card-text">Seluruh OPD terdaftar</h6>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
                <div class="card bg-gradient-success card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Dengan Logo
                            <i class="mdi mdi-image mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['dengan_logo'] ?? $opdList->whereNotNull('logo')->count() }}</h2>
                        <h6 class="card-text">OPD yang memiliki logo</h6>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
                <div class="card bg-gradient-info card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Dengan Email
                            <i class="mdi mdi-email mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['dengan_email'] ?? $opdList->whereNotNull('email')->count() }}</h2>
                        <h6 class="card-text">OPD dengan kontak email</h6>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 stretch-card grid-margin">
                <div class="card bg-gradient-warning card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Dengan Telepon
                            <i class="mdi mdi-phone mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['dengan_telepon'] ?? $opdList->whereNotNull('telepon')->count() }}</h2>
                        <h6 class="card-text">OPD dengan kontak telepon</h6>
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
                            <i class="mdi mdi-office-building"></i>
                            Daftar Organisasi Perangkat Daerah
                        </h4>
                        <div>
                            <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                data-bs-target="#addModal">
                                <i class="mdi mdi-plus"></i> Tambah OPD
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
                                    placeholder="Cari nama OPD atau singkatan...">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-6">
                                    <select id="filterLogo" class="form-select">
                                        <option value="">Semua Logo</option>
                                        <option value="with">Dengan Logo</option>
                                        <option value="without">Tanpa Logo</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select id="filterContact" class="form-select">
                                        <option value="">Semua Kontak</option>
                                        <option value="with">Dengan Kontak</option>
                                        <option value="without">Tanpa Kontak</option>
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

                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table table-hover" id="opdTable" style="min-width: 800px;">
                            <thead>
                                <tr>
                                    <th class="text-dark text-center d-none d-md-table-cell" style="width: 50px;">No</th>
                                    <th class="text-dark text-center" style="width: 80px;">Logo</th>
                                    <th class="text-dark" style="min-width: 200px;">
                                        <div class="d-flex flex-column">
                                            <span>Nama OPD</span>
                                            <small class="text-muted d-md-none">Singkatan & Kontak</small>
                                        </div>
                                    </th>
                                    <th class="text-dark d-none d-lg-table-cell" style="min-width: 150px;">Kontak</th>
                                    <th class="text-dark text-center" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="opdTableBody">
                                @forelse($opdList as $index => $opd)
                                    <tr class="opd-row" data-name="{{ strtolower($opd->name) }}"
                                        data-singkatan="{{ strtolower($opd->singkatan) }}"
                                        data-has-logo="{{ $opd->logo ? 'true' : 'false' }}"
                                        data-has-contact="{{ $opd->telepon || $opd->email ? 'true' : 'false' }}">
                                        <td class="text-center d-none d-md-table-cell">{{ $index + 1 }}</td>
                                        <td class="text-center">
                                            @if ($opd->logo)
                                                <img src="{{ asset('storage/' . $opd->logo) }}"
                                                    alt="Logo {{ $opd->singkatan }}" class="rounded logo-img"
                                                    style="width: 40px; height: 40px; object-fit: contain; background-color: #f8f9fa; border: 1px solid #dee2e6;">
                                            @else
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center logo-placeholder"
                                                    style="width: 40px; height: 40px;">
                                                    <i class="mdi mdi-office-building text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <div class="d-flex flex-column">
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                    <span class="d-none d-md-inline text-muted small">{{ $opd->name }}
                                                        - {{ $opd->singkatan }}</span>
                                                </div>
                                                <span class="d-md-none small fw-bold">{{ $opd->name }}</span>

                                                <!-- Contact info for mobile -->
                                                <div class="d-lg-none mt-1">
                                                    @if ($opd->telepon || $opd->email)
                                                        <div class="d-flex flex-column small text-muted">
                                                            @if ($opd->telepon)
                                                                <span><i
                                                                        class="mdi mdi-phone me-1"></i>{{ $opd->telepon }}</span>
                                                            @endif
                                                            @if ($opd->email)
                                                                <span><i
                                                                        class="mdi mdi-email me-1"></i>{{ Str::limit($opd->email, 25) }}</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">Tidak ada kontak</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-lg-table-cell" style="white-space: nowrap;">
                                            <div class="d-flex flex-column">
                                                @if ($opd->telepon)
                                                    <small><i class="mdi mdi-phone me-1"></i>{{ $opd->telepon }}</small>
                                                @endif
                                                @if ($opd->email)
                                                    <small><i class="mdi mdi-email me-1"></i>{{ $opd->email }}</small>
                                                @endif
                                                @if (!$opd->telepon && !$opd->email)
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center" style="white-space: nowrap;">
                                            <div class="btn-group-vertical d-md-none" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show mb-1"
                                                    data-id="{{ $opd->id }}" title="Lihat Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                        data-id="{{ $opd->id }}" data-name="{{ $opd->name }}"
                                                        data-singkatan="{{ $opd->singkatan }}"
                                                        data-telepon="{{ $opd->telepon }}"
                                                        data-email="{{ $opd->email }}" data-logo="{{ $opd->logo }}"
                                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                                        title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger btn-delete"
                                                        data-id="{{ $opd->id }}"
                                                        onclick="deleteOpd({{ $opd->id }})" title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="d-none d-md-flex" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show me-1"
                                                    data-id="{{ $opd->id }}" title="Lihat Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-success btn-edit me-1"
                                                    data-id="{{ $opd->id }}" data-name="{{ $opd->name }}"
                                                    data-singkatan="{{ $opd->singkatan }}"
                                                    data-telepon="{{ $opd->telepon }}" data-email="{{ $opd->email }}"
                                                    data-logo="{{ $opd->logo }}" data-bs-toggle="modal"
                                                    data-bs-target="#editModal" title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                                    data-id="{{ $opd->id }}"
                                                    onclick="deleteOpd({{ $opd->id }})" title="Hapus">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-data-row">
                                        <td colspan="5" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-office-building-outline mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Belum ada data OPD</p>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#addModal">
                                                    <i class="mdi mdi-plus"></i> Tambah OPD Pertama
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="addForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="addModalLabel">
                            <i class="mdi mdi-plus me-2"></i> Tambah OPD Baru
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body row">
                        <div class="col-md-6 mb-3">
                            <label for="add_name" class="form-label">Nama OPD <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="add_name" class="form-control"
                                placeholder="Contoh: Dinas Komunikasi dan Informatika">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_singkatan" class="form-label">Singkatan <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="singkatan" id="add_singkatan" class="form-control"
                                placeholder="Contoh: DISKOMINFO" maxlength="20">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_telepon" class="form-label">Nomor Telepon</label>
                            <input type="text" name="telepon" id="add_telepon" class="form-control"
                                placeholder="Contoh: (021) 1234567" maxlength="20">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_email" class="form-label">Email</label>
                            <input type="email" name="email" id="add_email" class="form-control"
                                placeholder="Contoh: diskominfo@daerah.go.id">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="add_logo" class="form-label">Logo OPD</label>
                            <input type="file" name="logo" id="add_logo" class="form-control"
                                accept="image/jpeg,image/png,image/jpg,image/gif">
                            <div class="form-text">Format: JPG, JPEG, PNG, GIF. Maksimal 2MB</div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12">
                            <div id="add_logoPreview" class="text-center">
                                <span class="text-muted">Pilih file logo untuk melihat pratinjau</span>
                            </div>
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
                        <i class="mdi mdi-eye me-2"></i> Detail OPD
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <div id="show_logo_container">
                                <div id="show_logo_placeholder"
                                    class="bg-light rounded d-flex align-items-center justify-content-center"
                                    style="width: 100px; height: 100px; margin: 0 auto;">
                                    <i class="mdi mdi-office-building mdi-48px text-muted"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h4 id="show_name" class="mb-2"></h4>
                            <h6 id="show_singkatan" class="text-primary mb-3"></h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Telepon:</strong>
                                    <p id="show_telepon" class="text-muted"></p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Email:</strong>
                                    <p id="show_email" class="text-muted"></p>
                                </div>
                            </div>
                        </div>
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
                            <i class="mdi mdi-pencil me-2"></i> Edit Data OPD
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">Nama OPD <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit_name">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_singkatan" class="form-label">Singkatan <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="singkatan" id="edit_singkatan"
                                maxlength="20">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_telepon" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" name="telepon" id="edit_telepon" maxlength="20">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="edit_logo" class="form-label">Logo OPD</label>
                            <input type="file" name="logo" id="edit_logo" class="form-control"
                                accept="image/jpeg,image/png,image/jpg,image/gif">
                            <div class="form-text">Format: JPG, JPEG, PNG, GIF. Maksimal 2MB. Kosongkan jika tidak ingin
                                mengubah logo.</div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12">
                            <div id="edit_logoPreview" class="text-center">
                                <span class="text-muted">Logo saat ini akan ditampilkan di sini</span>
                            </div>
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

            // Logo Preview Functions
            function previewLogo(input, previewContainer) {
                const file = input.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewContainer.html(`
                            <img src="${e.target.result}" alt="Logo Preview" 
                                 class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                        `);
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewContainer.html(
                        '<span class="text-muted">Pilih file logo untuk melihat pratinjau</span>');
                }
            }

            // Logo Preview Events
            $('#add_logo').on('change', function() {
                previewLogo(this, $('#add_logoPreview'));
            });

            $('#edit_logo').on('change', function() {
                previewLogo(this, $('#edit_logoPreview'));
            });

            // Add Modal
            $('#addModal').on('show.bs.modal', function() {
                const form = $('#addForm');
                form[0].reset();
                clearFormErrors(form);
                $('#add_logoPreview').html(
                    '<span class="text-muted">Pilih file logo untuk melihat pratinjau</span>');
            });

            // Add Form Submit
            $('#addForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const formData = new FormData(this);
                const submitBtn = form.find('button[type="submit"]');

                clearFormErrors(form);
                submitBtn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin"></i> Menyimpan...');

                $.ajax({
                    url: "{{ route('opd.store') }}",
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
                            showFormErrors(form, xhr.responseJSON.errors);
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
                $('#edit_name').val($(this).data('name'));
                $('#edit_singkatan').val($(this).data('singkatan'));
                $('#edit_telepon').val($(this).data('telepon'));
                $('#edit_email').val($(this).data('email'));

                // Show current logo if exists
                const currentLogo = $(this).data('logo');
                if (currentLogo) {
                    $('#edit_logoPreview').html(`
                        <div class="text-center">
                            <p class="mb-2"><strong>Logo saat ini:</strong></p>
                            <img src="{{ asset('storage/') }}/${currentLogo}" alt="Current Logo" 
                                 class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                        </div>
                    `);
                } else {
                    $('#edit_logoPreview').html('<span class="text-muted">Tidak ada logo saat ini</span>');
                }

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
                    url: "{{ route('opd.update', ['opd' => '__ID__']) }}".replace('__ID__', id),
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
                    url: "{{ route('opd.show', ['opd' => '__ID__']) }}".replace('__ID__', id),
                    type: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        const opd = response.data || response;

                        $('#show_name').text(opd.name);
                        $('#show_singkatan').text(opd.singkatan);
                        $('#show_telepon').text(opd.telepon || 'Tidak ada');
                        $('#show_email').text(opd.email || 'Tidak ada');
                        $('#show_created_at').text(new Date(opd.created_at).toLocaleDateString(
                            'id-ID'));
                        $('#show_updated_at').text(new Date(opd.updated_at).toLocaleDateString(
                            'id-ID'));

                        // Show logo
                        if (opd.logo) {
                            $('#show_logo_container').html(`
                                <img src="{{ asset('storage/') }}/${opd.logo}" alt="Logo ${opd.singkatan}" 
                                     class="rounded" style="width: 100px; height: 100px; object-fit: cover;">
                            `);
                        } else {
                            $('#show_logo_container').html(`
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                     style="width: 100px; height: 100px;">
                                    <i class="mdi mdi-office-building mdi-48px text-muted"></i>
                                </div>
                            `);
                        }

                        const modal = new bootstrap.Modal(document.getElementById('showModal'));
                        modal.show();
                    },
                    error: function(xhr) {
                        console.error('Error loading OPD details:', xhr);
                        showAlert('Gagal memuat data detail: ' + (xhr.responseJSON?.message ||
                            'Unknown error'), 'error');
                    }
                });
            });

            // Delete function
            window.deleteOpd = function(id) {
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Data OPD ini akan dihapus permanen!",
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
                            url: "{{ route('opd.destroy', ['opd' => '__ID__']) }}".replace(
                                '__ID__', id),
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

            // Input formatting
            $('#add_singkatan, #edit_singkatan').on('input', function() {
                this.value = this.value.toUpperCase();
            });

            $('#add_telepon, #edit_telepon').on('input', function() {
                // Only allow numbers, spaces, parentheses, hyphens, and plus sign
                this.value = this.value.replace(/[^0-9\s\(\)\-\+]/g, '');
            });

            // Search and Filter Functions
            let searchTimeout;

            function performSearch() {
                const searchTerm = $('#searchInput').val().toLowerCase().trim();
                const logoFilter = $('#filterLogo').val();
                const contactFilter = $('#filterContact').val();

                let visibleCount = 0;
                const $rows = $('.opd-row');

                $rows.each(function() {
                    const $row = $(this);
                    const name = $row.data('name');
                    const singkatan = $row.data('singkatan');
                    const hasLogo = $row.data('has-logo').toString();
                    const hasContact = $row.data('has-contact').toString();

                    let showRow = true;

                    // Search filter
                    if (searchTerm) {
                        showRow = name.includes(searchTerm) || singkatan.includes(searchTerm);
                    }

                    // Logo filter
                    if (showRow && logoFilter) {
                        if (logoFilter === 'with' && hasLogo !== 'true') showRow = false;
                        if (logoFilter === 'without' && hasLogo !== 'false') showRow = false;
                    }

                    // Contact filter
                    if (showRow && contactFilter) {
                        if (contactFilter === 'with' && hasContact !== 'true') showRow = false;
                        if (contactFilter === 'without' && hasContact !== 'false') showRow = false;
                    }

                    if (showRow) {
                        $row.show();
                        visibleCount++;
                    } else {
                        $row.hide();
                    }
                });

                // Update search info
                updateSearchInfo(searchTerm, logoFilter, contactFilter, visibleCount);

                // Show/hide no results message
                if (visibleCount === 0 && (searchTerm || logoFilter || contactFilter)) {
                    $('#no-search-results').removeClass('d-none');
                    $('#no-data-row').addClass('d-none');
                } else {
                    $('#no-search-results').addClass('d-none');
                    if (visibleCount === 0 && !searchTerm && !logoFilter && !contactFilter) {
                        $('#no-data-row').removeClass('d-none');
                    } else {
                        $('#no-data-row').addClass('d-none');
                    }
                }

                // Update row numbers for visible rows
                updateRowNumbers();
            }

            function updateSearchInfo(searchTerm, logoFilter, contactFilter, visibleCount) {
                const totalRows = $('.opd-row').length;
                let infoText = '';

                if (searchTerm || logoFilter || contactFilter) {
                    infoText = `Menampilkan ${visibleCount} dari ${totalRows} data OPD`;

                    const filters = [];
                    if (searchTerm) filters.push(`pencarian: "${searchTerm}"`);
                    if (logoFilter) filters.push(`logo: ${logoFilter === 'with' ? 'dengan logo' : 'tanpa logo'}`);
                    if (contactFilter) filters.push(
                        `kontak: ${contactFilter === 'with' ? 'dengan kontak' : 'tanpa kontak'}`);

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
                $('.opd-row:visible').each(function() {
                    $(this).find('td:first-child').text(counter++);
                });
            }

            function resetAllFilters() {
                $('#searchInput').val('');
                $('#filterLogo').val('');
                $('#filterContact').val('');
                performSearch();
            }

            // Search event handlers
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            });

            $('#filterLogo, #filterContact').on('change', performSearch);

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
        });
    </script>

    <style>
        /* Custom styles for OPD page */
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
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table td {
            border-color: #e9ecef;
            vertical-align: middle;
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

        /* Logo styling */
        .logo-img,
        .logo-placeholder {
            border-radius: 4px;
            transition: transform 0.2s ease;
        }

        .logo-img:hover {
            transform: scale(1.1);
        }

        /* Mobile responsive adjustments */
        @media (max-width: 767px) {
            .table-responsive {
                border-radius: 8px;
            }

            .card-body {
                padding: 1rem 0.75rem;
            }

            .btn-group-vertical .btn {
                border-radius: 0.25rem !important;
                margin-bottom: 2px;
            }

            .btn-group-vertical .btn:last-child {
                margin-bottom: 0;
            }

            .badge {
                font-size: 0.7em;
                padding: 0.25rem 0.5rem;
            }

            /* Search section mobile */
            .input-group {
                margin-bottom: 0.75rem;
            }

            .form-select {
                margin-bottom: 0.5rem;
            }

            /* Statistics cards mobile */
            .card-body h2 {
                font-size: 1.5rem;
            }

            .card-body h4 {
                font-size: 1rem;
            }

            .card-body h6 {
                font-size: 0.875rem;
            }
        }

        /* Tablet responsive adjustments */
        @media (min-width: 768px) and (max-width: 991px) {

            .table th,
            .table td {
                padding: 0.5rem;
                font-size: 0.875rem;
            }

            .btn-sm {
                padding: 0.25rem 0.375rem;
                font-size: 0.75rem;
            }
        }

        /* Large screen optimizations */
        @media (min-width: 1200px) {
            .table-responsive {
                border-radius: 12px;
            }

            .card-body {
                padding: 1.5rem;
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

        #add_logoPreview,
        #edit_logoPreview {
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            background-color: #f8f9fa;
            margin-top: 10px;
        }

        .img-thumbnail {
            border: 2px solid #dee2e6;
            border-radius: 8px;
        }

        .font-weight-bold {
            font-weight: 600 !important;
        }

        /* Logo container styling */
        .logo-container {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Contact info styling */
        .contact-info small {
            display: block;
            margin-bottom: 2px;
        }

        .contact-info small:last-child {
            margin-bottom: 0;
        }

        /* Badge styling */
        .badge.bg-primary {
            background-color: #007bff !important;
            font-weight: 500;
        }

        /* Modal header gradients */
        .modal-header.bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .modal-header.bg-gradient-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%) !important;
        }

        .modal-header.bg-gradient-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
        }

        /* Statistics cards hover effect */
        /* Custom styles for OPD page */
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

        #add_logoPreview,
        #edit_logoPreview {
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            background-color: #f8f9fa;
            margin-top: 10px;
        }

        .img-thumbnail {
            border: 2px solid #dee2e6;
            border-radius: 8px;
        }

        .font-weight-bold {
            font-weight: 600 !important;
        }

        /* Logo container styling */
        .logo-container {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Contact info styling */
        .contact-info small {
            display: block;
            margin-bottom: 2px;
        }

        .contact-info small:last-child {
            margin-bottom: 0;
        }

        /* Badge styling */
        .badge.bg-primary {
            background-color: #007bff !important;
            font-weight: 500;
        }

        /* Modal header gradients */
        .modal-header.bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .modal-header.bg-gradient-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%) !important;
        }

        .modal-header.bg-gradient-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .btn-group .btn {
                margin-right: 0;
                margin-bottom: 2px;
            }

            .btn-group .btn:last-child {
                margin-bottom: 0;
            }
        }
    </style>
@endsection
