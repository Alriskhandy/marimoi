@extends('backend.partials.main')

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-layers"></i>
            </span> Kategori Usulan Musrembang Layer
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Kategori Usulan Musrembang Layer
                </li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">Daftar Kategori Layer</h4>
                        <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                            data-bs-target="#addModal">
                            <i class="mdi mdi-plus"></i> Tambah Kategori
                        </button>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 stretch-card grid-margin">
                            <div class="card bg-gradient-primary card-img-holder text-white">
                                <div class="card-body">
                                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}"
                                        class="card-img-absolute" alt="circle" />
                                    <h4 class="font-weight-normal mb-3">
                                        Kategori Utama
                                        <i class="mdi mdi-format-list-bulleted-type mdi-24px float-end"></i>
                                    </h4>
                                    <h2 class="mb-5">{{ $parentKategoris->count() }}</h2>
                                    <h6 class="card-text">Jumlah kategori induk</h6>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 stretch-card grid-margin">
                            <div class="card bg-gradient-success card-img-holder text-white">
                                <div class="card-body">
                                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}"
                                        class="card-img-absolute" alt="circle" />
                                    <h4 class="font-weight-normal mb-3">
                                        Sub Kategori
                                        <i class="mdi mdi-subdirectory-arrow-right mdi-24px float-end"></i>
                                    </h4>
                                    <h2 class="mb-5">{{ collect($childKategoris)->flatten()->count() }}</h2>
                                    <h6 class="card-text">Kategori turunan</h6>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 stretch-card grid-margin">
                            <div class="card bg-gradient-info card-img-holder text-white">
                                <div class="card-body">
                                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}"
                                        class="card-img-absolute" alt="circle" />
                                    <h4 class="font-weight-normal mb-3">
                                        Total Kategori
                                        <i class="mdi mdi-layers mdi-24px float-end"></i>
                                    </h4>
                                    <h2 class="mb-5">
                                        {{ $parentKategoris->count() + collect($childKategoris)->flatten()->count() }}</h2>
                                    <h6 class="card-text">Semua kategori</h6>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 stretch-card grid-margin">
                            <div class="card bg-gradient-warning card-img-holder text-white">
                                <div class="card-body">
                                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}"
                                        class="card-img-absolute" alt="circle" />
                                    <h4 class="font-weight-normal mb-3">
                                        Marker Aktif
                                        <i class="mdi mdi-map-marker mdi-24px float-end"></i>
                                    </h4>
                                    <h2 class="mb-5">
                                        {{ $parentKategoris->where('is_marker', true)->count() + collect($childKategoris)->flatten()->where('is_marker', true)->count() }}
                                    </h2>
                                    <h6 class="card-text">Kategori marker point</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <label for="rowsPerPageSelect" class="me-2">Tampilkan</label>
                                <select id="rowsPerPageSelect" class="form-select d-inline-block w-auto"
                                    style="background-image: none;">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                                <span class="ms-2">data per halaman</span>
                            </div>
                            <div>
                                <input type="text" id="searchInput" class="form-control" placeholder="Cari kategori...">
                            </div>
                        </div>

                        <table class="table table-hover" id="kategoriTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Warna</th>
                                    <th>Parent</th>
                                    <th>Type</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @forelse($parentKategoris as $kategori)
                                    {{-- Root Kategori --}}
                                    <tr data-id="{{ $kategori->id }}" class="table-light">
                                        <td>{{ $no++ }}</td>
                                        <td><strong>{{ $kategori->nama }}</strong></td>
                                        <td>
                                            @if ($kategori->warna)
                                                <span class="badge"
                                                    style="background-color: {{ $kategori->warna }}; color: white;">
                                                    {{ $kategori->warna }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td><span class="badge badge-secondary">Root</span></td>
                                        <td>
                                            @if ($kategori->is_marker)
                                                <span class="badge badge-warning">
                                                    <i class="mdi mdi-map-marker"></i> Marker
                                                </span>
                                            @else
                                                <span class="badge badge-info">
                                                    <i class="mdi mdi-layers"></i> Layer
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show"
                                                    data-id="{{ $kategori->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#showModal" title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                    data-id="{{ $kategori->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#editModal" title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <form
                                                    action="{{ route('kategori-usulan-musrenbang.destroy', $kategori->id) }}"
                                                    method="POST" style="display: inline-block;" data-confirm="delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Subkategori --}}
                                    @if (isset($childKategoris[$kategori->id]) && $childKategoris[$kategori->id]->count() > 0)
                                        @foreach ($childKategoris[$kategori->id] as $child)
                                            <tr data-id="{{ $child->id }}" class="table-secondary">
                                                <td>{{ $no++ }}</td>
                                                <td>
                                                    <i class="mdi mdi-subdirectory-arrow-right text-muted me-1"></i>
                                                    <strong>{{ $child->nama }}</strong>
                                                    <br>
                                                    <small class="text-muted">Sub dari: {{ $kategori->nama }}</small>
                                                </td>
                                                <td>
                                                    @if ($child->warna)
                                                        <span class="badge"
                                                            style="background-color: {{ $child->warna }}; color: white;">
                                                            {{ $child->warna }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td><span class="badge badge-info">{{ $kategori->nama }}</span></td>
                                                <td>
                                                    @if ($child->is_marker)
                                                        <span class="badge badge-warning">
                                                            <i class="mdi mdi-map-marker"></i> Marker
                                                        </span>
                                                        @if ($child->icon)
                                                            <br>
                                                            <small class="text-muted d-flex align-items-center mt-1">
                                                                <i class="fa fa-map-marker fa-lg me-1"
                                                                    title="Icon: {{ $child->icon }}"></i>
                                                                <span>{{ $child->icon }}</span>
                                                            </small>
                                                        @endif
                                                    @else
                                                        <span class="badge badge-info">
                                                            <i class="mdi mdi-layers"></i> Layer
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-info btn-show"
                                                            data-id="{{ $child->id }}" data-bs-toggle="modal"
                                                            data-bs-target="#showModal" title="Detail">
                                                            <i class="mdi mdi-eye"></i>
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-success btn-edit"
                                                            data-id="{{ $child->id }}" data-bs-toggle="modal"
                                                            data-bs-target="#editModal" title="Edit">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </button>
                                                        <form
                                                            action="{{ route('kategori-usulan-musrenbang.destroy', $child->id) }}"
                                                            method="POST" style="display: inline-block;"
                                                            data-confirm="delete">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                title="Hapus">
                                                                <i class="mdi mdi-delete"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-layers mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Belum ada kategori yang dibuat</p>
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

                        <nav>
                            <ul class="pagination justify-content-center" id="pagination"></ul>
                        </nav>
                    </div>
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
                        <i class="mdi mdi-plus"></i> Tambah Kategori Layer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="add_nama" class="form-label">Nama Kategori <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_nama" name="nama" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="add_warna" class="form-label">Warna</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="add_warna"
                                            name="warna" value="#007bff">
                                        <span class="input-group-text" id="add_colorPreview"
                                            style="background-color: #007bff; color: white;">●</span>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="add_parent_id" class="form-label">Parent Kategori</label>
                            <select class="form-control" id="add_parent_id" name="parent_id">
                                <option value="">-- Pilih Parent (Opsional) --</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group mb-3 text-center">
                            <div class="form-check d-inline-block">
                                <input type="hidden" name="is_marker" value="0">
                                <input class="form-check-input" type="checkbox" value="1" id="is_marker"
                                    name="is_marker">
                                <label class="form-check-label" for="is_marker">
                                    Gunakan sebagai Marker (Point)
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-3" id="iconContainer" style="display: none;">
                            <label for="add_icon" class="form-label">
                                <i class="mdi mdi-map-marker me-1"></i> Ikon Marker
                            </label>
                            <select class="form-select" id="add_icon" name="icon">
                                <option value="">-- Pilih Ikon --</option>
                                @include('backend.partials.icon-options')
                            </select>
                            <div class="form-text">Ikon hanya berlaku untuk kategori marker (Point)</div>
                            <div id="iconPreview" class="mt-2 text-dark">
                                <span class="text-muted">Pilih ikon untuk melihat pratinjau</span>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="add_deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="add_deskripsi" name="deskripsi" rows="3"></textarea>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="mdi mdi-pencil"></i> Edit Kategori Layer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="edit_nama" class="form-label">Nama Kategori <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_nama" name="nama" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="edit_warna" class="form-label">Warna</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="edit_warna"
                                            name="warna">
                                        <span class="input-group-text" id="edit_colorPreview"
                                            style="background-color: #007bff; color: white;">●</span>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_parent_id" class="form-label">Parent Kategori</label>
                            <select class="form-control" id="edit_parent_id" name="parent_id">
                                <option value="">-- Pilih Parent (Opsional) --</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group mb-3 text-center">
                            <div class="form-check d-inline-block">
                                <input type="hidden" name="is_marker" value="0">
                                <input class="form-check-input" type="checkbox" value="1" id="edit_is_marker"
                                    name="is_marker">
                                <label class="form-check-label" for="edit_is_marker">
                                    Gunakan sebagai Marker (Point)
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-3" id="edit_iconContainer" style="display: none;">
                            <label for="edit_icon" class="form-label">
                                <i class="mdi mdi-map-marker me-1"></i> Ikon Marker
                            </label>
                            <select class="form-select" id="edit_icon" name="icon">
                                <option value="">-- Pilih Ikon --</option>
                                @include('backend.partials.icon-options')
                            </select>
                            <div class="form-text">Ikon hanya berlaku untuk kategori marker (Point)</div>
                            <div id="edit_iconPreview" class="mt-2 text-dark">
                                <span class="text-muted">Pilih ikon untuk melihat pratinjau</span>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-gradient-warning">
                            <i class="mdi mdi-content-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Show Modal -->
    <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="mdi mdi-eye-outline me-1"></i> Detail Kategori Layer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>Nama:</strong></span>
                            <span id="show_nama" class="text-muted"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>Warna:</strong></span>
                            <span id="show_warna" class="badge" style="background-color: #ccc;"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>Parent:</strong></span>
                            <span id="show_parent" class="text-muted"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>Type:</strong></span>
                            <span id="show_type" class="badge bg-info"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>Jumlah Sub Kategori:</strong></span>
                            <span id="show_children_count" class="badge bg-info"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><strong>Dibuat:</strong></span>
                            <span id="show_created_at" class="text-muted"></span>
                        </li>
                    </ul>

                    <div class="mb-3">
                        <strong>Deskripsi:</strong>
                        <p id="show_deskripsi" class="text-muted mb-0"></p>
                    </div>

                    <div class="mb-3" id="show_children_container" style="display: none;">
                        <strong>Sub Kategori:</strong>
                        <div id="show_children" class="mt-2 ps-3 border-start border-3 border-info"></div>
                    </div>

                    <div class="mb-3" id="show_icon_container" style="display: none;">
                        <strong>Ikon Marker:</strong>
                        <div id="show_icon" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('backend/assets/vendors/sweetalert/sweetalert2.all.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Show Alert Function
            function showAlert(message, type = 'success') {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('#alertContainer').html(alertHtml);

                // Auto hide after 3 seconds
                setTimeout(function() {
                    $('#alertContainer .alert').alert('close');
                }, 3000);
            }

            // Clear form errors
            function clearFormErrors(form) {
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
            }

            // Show form errors
            function showFormErrors(form, errors) {
                clearFormErrors(form);
                $.each(errors, function(field, messages) {
                    const input = form.find(`[name="${field}"]`);
                    input.addClass('is-invalid');
                    input.siblings('.invalid-feedback').text(messages[0]);
                });
            }

            // Load parent categories for form
            function loadParentCategories(selectElement, excludeId = null) {
                $.get('{{ route('kategori-usulan-musrenbang.create') }}', function(data) {
                    selectElement.empty();
                    selectElement.append('<option value="">-- Pilih Parent (Opsional) --</option>');
                    $.each(data.parentKategori, function(index, kategori) {
                        if (kategori.parent_id !== null) return; // only root categories
                        if (excludeId && kategori.id == excludeId) return;
                        selectElement.append(
                            `<option value="${kategori.id}">${kategori.nama}</option>`);
                    });
                });
            }

            // Color preview functionality
            $('#add_warna').on('change input', function() {
                $('#add_colorPreview').css('background-color', $(this).val());
            });

            $('#edit_warna').on('change input', function() {
                $('#edit_colorPreview').css('background-color', $(this).val());
            });

            // Fungsi konversi FontAwesome 6 ke FontAwesome 4 untuk preview
            function convertFa6ToFa4(fa6Class) {
                if (!fa6Class) return '';

                // Mapping FA6 to FA4 classes
                const iconMappings = {
                    // Lokasi & Navigasi
                    'fa-solid fa-location-dot': 'fa fa-map-marker',
                    'fa-solid fa-map-pin': 'fa fa-thumb-tack',
                    'fa-solid fa-compass': 'fa fa-compass',
                    'fa-solid fa-route': 'fa fa-road',
                    'fa-solid fa-crosshairs': 'fa fa-crosshairs',
                    'fa-solid fa-map-marker-alt': 'fa fa-map-marker',
                    'fa-solid fa-directions': 'fa fa-location-arrow',

                    // Pemerintahan & Fasilitas Publik
                    'fa-solid fa-landmark': 'fa fa-university',
                    'fa-solid fa-university': 'fa fa-university',
                    'fa-solid fa-building': 'fa fa-building',
                    'fa-solid fa-building-columns': 'fa fa-bank',
                    'fa-solid fa-scale-balanced': 'fa fa-balance-scale',
                    'fa-solid fa-shield-halved': 'fa fa-shield',
                    'fa-solid fa-flag': 'fa fa-flag',
                    'fa-solid fa-city': 'fa fa-building-o',

                    // Kesehatan & Pendidikan
                    'fa-solid fa-hospital': 'fa fa-hospital-o',
                    'fa-solid fa-user-doctor': 'fa fa-user-md',
                    'fa-solid fa-pills': 'fa fa-medkit',
                    'fa-solid fa-school': 'fa fa-university',
                    'fa-solid fa-graduation-cap': 'fa fa-graduation-cap',
                    'fa-solid fa-book': 'fa fa-book',
                    'fa-solid fa-heartbeat': 'fa fa-heartbeat',
                    'fa-solid fa-stethoscope': 'fa fa-stethoscope',

                    // Transportasi
                    'fa-solid fa-car': 'fa fa-car',
                    'fa-solid fa-bus': 'fa fa-bus',
                    'fa-solid fa-train': 'fa fa-train',
                    'fa-solid fa-plane': 'fa fa-plane',
                    'fa-solid fa-ship': 'fa fa-ship',
                    'fa-solid fa-gas-pump': 'fa fa-car',
                    'fa-solid fa-motorcycle': 'fa fa-motorcycle',
                    'fa-solid fa-taxi': 'fa fa-taxi',
                    'fa-solid fa-parking': 'fa fa-car',

                    // Perdagangan & Ekonomi
                    'fa-solid fa-store': 'fa fa-shopping-bag',
                    'fa-solid fa-shopping-cart': 'fa fa-shopping-cart',
                    'fa-solid fa-utensils': 'fa fa-cutlery',
                    'fa-solid fa-coffee': 'fa fa-coffee',
                    'fa-solid fa-warehouse': 'fa fa-building',
                    'fa-solid fa-industry': 'fa fa-industry',
                    'fa-solid fa-shopping-bag': 'fa fa-shopping-bag',
                    'fa-solid fa-cash-register': 'fa fa-credit-card',

                    // Lingkungan & Alam
                    'fa-solid fa-tree': 'fa fa-tree',
                    'fa-solid fa-mountain': 'fa fa-mountain',
                    'fa-solid fa-water': 'fa fa-tint',
                    'fa-solid fa-seedling': 'fa fa-leaf',
                    'fa-solid fa-leaf': 'fa fa-leaf',
                    'fa-solid fa-sun': 'fa fa-sun-o',
                    'fa-solid fa-cloud-rain': 'fa fa-cloud',
                    'fa-solid fa-snowflake': 'fa fa-snowflake-o',

                    // Infrastruktur
                    'fa-solid fa-tower-broadcast': 'fa fa-signal',
                    'fa-solid fa-bolt': 'fa fa-bolt',
                    'fa-solid fa-wrench': 'fa fa-wrench',
                    'fa-solid fa-road': 'fa fa-road',
                    'fa-solid fa-bridge': 'fa fa-building',
                    'fa-solid fa-tower-cell': 'fa fa-signal',
                    'fa-solid fa-wifi': 'fa fa-wifi',
                    'fa-solid fa-satellite-dish': 'fa fa-wifi',

                    // Olahraga & Rekreasi
                    'fa-solid fa-football': 'fa fa-soccer-ball-o',
                    'fa-solid fa-dumbbell': 'fa fa-dumbbell',
                    'fa-solid fa-swimmer': 'fa fa-life-ring',
                    'fa-solid fa-person-hiking': 'fa fa-male',
                    'fa-solid fa-tent': 'fa fa-home',
                    'fa-solid fa-camera': 'fa fa-camera',
                    'fa-solid fa-volleyball': 'fa fa-circle-o',
                    'fa-solid fa-table-tennis-paddle-ball': 'fa fa-circle',

                    // Keagamaan
                    'fa-solid fa-mosque': 'fa fa-building',
                    'fa-solid fa-church': 'fa fa-building',
                    'fa-solid fa-place-of-worship': 'fa fa-building',
                    'fa-solid fa-cross': 'fa fa-plus',
                    'fa-solid fa-om': 'fa fa-circle-o',
                    'fa-solid fa-dharmachakra': 'fa fa-circle-o',

                    // Keamanan & Darurat
                    'fa-solid fa-fire-flame-curved': 'fa fa-fire',
                    'fa-solid fa-truck-medical': 'fa fa-ambulance',
                    'fa-solid fa-shield': 'fa fa-shield',
                    'fa-solid fa-siren-on': 'fa fa-volume-up',
                    'fa-solid fa-life-ring': 'fa fa-life-ring',
                    'fa-solid fa-triangle-exclamation': 'fa fa-warning',
                    'fa-solid fa-hard-hat': 'fa fa-user',

                    // Pariwisata & Budaya
                    'fa-solid fa-monument': 'fa fa-building',
                    'fa-solid fa-museum': 'fa fa-university',
                    'fa-solid fa-ticket': 'fa fa-ticket',
                    'fa-solid fa-map': 'fa fa-map',
                    'fa-solid fa-binoculars': 'fa fa-search',
                    'fa-solid fa-mountain-sun': 'fa fa-mountain',
                    'fa-solid fa-masks-theater': 'fa fa-music',
                    'fa-solid fa-star': 'fa fa-star',

                    // Utilitas & Layanan
                    'fa-solid fa-trash': 'fa fa-trash',
                    'fa-solid fa-recycle': 'fa fa-recycle',
                    'fa-solid fa-toilet': 'fa fa-home',
                    'fa-solid fa-faucet': 'fa fa-tint',
                    'fa-solid fa-hammer': 'fa fa-wrench',
                    'fa-solid fa-tools': 'fa fa-wrench',
                    'fa-solid fa-envelope': 'fa fa-envelope',
                    'fa-solid fa-phone': 'fa fa-phone'
                };

                return iconMappings[fa6Class] || 'fa fa-question-circle';
            }

            // Marker checkbox functionality
            $('#is_marker').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#iconContainer').show();
                } else {
                    $('#iconContainer').hide();
                    $('#add_icon').val('');
                    $('#iconPreview').html(
                        '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>');
                }
            });

            $('#edit_is_marker').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#edit_iconContainer').show();
                } else {
                    $('#edit_iconContainer').hide();
                    $('#edit_icon').val('');
                    $('#edit_iconPreview').html(
                        '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>');
                }
            });

            // Icon preview dengan konversi FA6 ke FA4
            $('#add_icon').on('change', function() {
                const fa6Icon = $(this).val();
                if (fa6Icon) {
                    const fa4Icon = convertFa6ToFa4(fa6Icon);
                    $('#iconPreview').html(`
                        <div class="d-flex align-items-center">
                            <i class="${fa4Icon} fa-2x me-3" style="color: #007bff;"></i>
                            <div>
                                <div><strong>Preview:</strong> <span class="text-muted">(FA4 untuk tampilan)</span></div>
                                <div><small><strong>Disimpan:</strong> <code>${fa6Icon}</code></small></div>
                            </div>
                        </div>
                    `);
                } else {
                    $('#iconPreview').html(
                        '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>');
                }
            });

            $('#edit_icon').on('change', function() {
                const fa6Icon = $(this).val();
                if (fa6Icon) {
                    const fa4Icon = convertFa6ToFa4(fa6Icon);
                    $('#edit_iconPreview').html(`
                        <div class="d-flex align-items-center">
                            <i class="${fa4Icon} fa-2x me-3" style="color: #007bff;"></i>
                            <div>
                                <div><strong>Preview:</strong> <span class="text-muted">(FA4 untuk tampilan)</span></div>
                                <div><small><strong>Disimpan:</strong> <code>${fa6Icon}</code></small></div>
                            </div>
                        </div>
                    `);
                } else {
                    $('#edit_iconPreview').html(
                        '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>');
                }
            });

            // Add Modal
            $('#addModal').on('show.bs.modal', function() {
                const form = $('#addForm');
                form[0].reset();
                clearFormErrors(form);
                loadParentCategories($('#add_parent_id'));
                $('#add_colorPreview').css('background-color', '#007bff');
                $('#iconContainer').hide();
                $('#iconPreview').html(
                    '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>');
            });

            // Add Form Submit
            $('#addForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route('kategori-usulan-musrenbang.store') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#addModal').modal('hide');
                            showAlert(response.message);
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            if (xhr.responseJSON.errors) {
                                showFormErrors(form, xhr.responseJSON.errors);
                            }
                            if (xhr.responseJSON.message) {
                                showAlert(xhr.responseJSON.message, 'error');
                            }
                        } else {
                            const message = xhr.responseJSON?.message ||
                                'Terjadi kesalahan server';
                            showAlert(message, 'error');
                        }
                    }
                });
            });

            // Edit Modal
            $(document).on('click', '.btn-edit', function() {
                const id = $(this).data('id');
                const form = $('#editForm');

                $.get(`{{ route('kategori-usulan-musrenbang.index') }}/${id}/edit`, function(data) {
                    if (data.success) {
                        $('#edit_id').val(data.data.id);
                        $('#edit_nama').val(data.data.nama);
                        $('#edit_warna').val(data.data.warna || '#007bff');
                        $('#edit_deskripsi').val(data.data.deskripsi);
                        $('#edit_colorPreview').css('background-color', data.data.warna ||
                            '#007bff');

                        // Marker checkbox
                        if (data.data.is_marker) {
                            $('#edit_is_marker').prop('checked', true);
                            $('#edit_iconContainer').show();
                            $('#edit_icon').val(data.data.icon);
                            if (data.data.icon) {
                                const fa4Icon = convertFa6ToFa4(data.data.icon);
                                $('#edit_iconPreview').html(`
                                    <div class="d-flex align-items-center">
                                        <i class="${fa4Icon} fa-2x me-3" style="color: #007bff;"></i>
                                        <div>
                                            <div><strong>Preview:</strong> <span class="text-muted">(FA4 untuk tampilan)</span></div>
                                            <div><small><strong>Disimpan:</strong> <code>${data.data.icon}</code></small></div>
                                        </div>
                                    </div>
                                `);
                            }
                        } else {
                            $('#edit_is_marker').prop('checked', false);
                            $('#edit_iconContainer').hide();
                            $('#edit_iconPreview').html(
                                '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>'
                            );
                        }

                        // Load parent categories
                        const parentSelect = $('#edit_parent_id');
                        parentSelect.empty();
                        parentSelect.append(
                            '<option value="">-- Pilih Parent (Opsional) --</option>');
                        $.each(data.parentKategori, function(index, kategori) {
                            if (kategori.id != data.data.id) { // exclude current category
                                const selected = data.data.parent_id == kategori.id ?
                                    'selected' : '';
                                parentSelect.append(
                                    `<option value="${kategori.id}" ${selected}>${kategori.nama}</option>`
                                );
                            }
                        });

                        clearFormErrors(form);
                    }
                });
            });

            // Edit Form Submit
            $('#editForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const id = $('#edit_id').val();
                const formData = new FormData(this);

                $.ajax({
                    url: `{{ route('kategori-usulan-musrenbang.index') }}/${id}`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#editModal').modal('hide');
                            showAlert(response.message);
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            if (xhr.responseJSON.errors) {
                                showFormErrors(form, xhr.responseJSON.errors);
                            } else {
                                showAlert(xhr.responseJSON.message, 'error');
                            }
                        } else {
                            showAlert('Terjadi kesalahan server', 'error');
                        }
                    }
                });
            });

            // Show Modal
            $(document).on('click', '.btn-show', function() {
                const id = $(this).data('id');

                $.get(`{{ route('kategori-usulan-musrenbang.index') }}/${id}`, function(data) {
                    if (data.success) {
                        const kategori = data.data;
                        $('#show_nama').text(kategori.nama);
                        $('#show_parent').text(kategori.parent ? kategori.parent.nama : 'Root');
                        $('#show_children_count').text(kategori.children ? kategori.children
                            .length : 0);
                        $('#show_created_at').text(new Date(kategori.created_at).toLocaleDateString(
                            'id-ID'));
                        $('#show_deskripsi').text(kategori.deskripsi || 'Tidak ada deskripsi');

                        // Show warna
                        if (kategori.warna) {
                            $('#show_warna').html(
                                `<span class="badge" style="background-color: ${kategori.warna}; color: white;">${kategori.warna}</span>`
                            );
                        } else {
                            $('#show_warna').text('Tidak ada');
                        }

                        // Show type
                        if (kategori.is_marker) {
                            $('#show_type').html('<i class="mdi mdi-map-marker"></i> Marker')
                                .removeClass('bg-info').addClass('bg-warning');
                        } else {
                            $('#show_type').html('<i class="mdi mdi-layers"></i> Layer')
                                .removeClass('bg-warning').addClass('bg-info');
                        }

                        // Show icon if marker
                        if (kategori.is_marker && kategori.icon) {
                            const fa4Icon = convertFa6ToFa4(kategori.icon);
                            $('#show_icon').html(`
                                <div class="d-flex align-items-center">
                                    <i class="${fa4Icon} fa-2x me-3" style="color: #333;"></i>
                                    <div>
                                        <div><strong>Preview:</strong> <span class="text-muted">(FA4 untuk tampilan)</span></div>
                                        <div><small><strong>Tersimpan:</strong> <code>${kategori.icon}</code></small></div>
                                    </div>
                                </div>
                            `);
                            $('#show_icon_container').show();
                        } else {
                            $('#show_icon_container').hide();
                        }

                        // Show children if any
                        if (kategori.children && kategori.children.length > 0) {
                            let childrenHtml = '';
                            $.each(kategori.children, function(index, child) {
                                const childType = child.is_marker ?
                                    '<i class="mdi mdi-map-marker text-warning"></i>' :
                                    '<i class="mdi mdi-layers text-info"></i>';
                                childrenHtml +=
                                    `<span class="badge badge-info me-1 mb-1">${childType} ${child.nama}</span>`;
                            });
                            $('#show_children').html(childrenHtml);
                            $('#show_children_container').show();
                        } else {
                            $('#show_children_container').hide();
                        }
                    }
                });
            });

            // Delete confirmation with SweetAlert
            document.addEventListener("DOMContentLoaded", function() {
                document.body.addEventListener("submit", function(e) {
                    const form = e.target;
                    if (form.matches('form[data-confirm="delete"]')) {
                        e.preventDefault();
                        const categoryName = form.closest('tr').querySelector('strong').textContent;

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Yakin ingin menghapus?',
                                text: `Kategori "${categoryName}" akan dihapus secara permanen!`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: 'Ya, hapus!',
                                cancelButtonText: 'Batal'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    form.submit();
                                }
                            });
                        } else {
                            if (confirm(`Yakin ingin menghapus kategori "${categoryName}"?`)) {
                                form.submit();
                            }
                        }
                    }
                });
            });

            // Pagination and Search functionality
            const tableBody = document.querySelector("#kategoriTable tbody");
            const pagination = document.getElementById("pagination");
            const searchInput = document.getElementById("searchInput");
            const rowsPerPageSelect = document.getElementById("rowsPerPageSelect");

            let currentPage = 1;
            let rowsPerPage = parseInt(rowsPerPageSelect.value);
            const originalRows = Array.from(tableBody.querySelectorAll("tr"));

            function updateTable() {
                const search = searchInput.value.toLowerCase();
                rowsPerPage = parseInt(rowsPerPageSelect.value);

                const filteredRows = originalRows.filter(row =>
                    row.innerText.toLowerCase().includes(search)
                );

                const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
                currentPage = Math.min(currentPage, totalPages) || 1;

                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                tableBody.innerHTML = "";
                filteredRows.slice(start, end).forEach(row => {
                    tableBody.appendChild(row.cloneNode(true));
                });

                renderPagination(totalPages, filteredRows.length);
            }

            function renderPagination(totalPages, totalFiltered) {
                pagination.innerHTML = "";

                if (totalFiltered <= rowsPerPage) {
                    pagination.style.display = "none";
                    return;
                }

                pagination.style.display = "flex";

                // Previous button
                if (currentPage > 1) {
                    const prevLi = document.createElement("li");
                    prevLi.classList.add("page-item");
                    prevLi.innerHTML =
                        `<a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>`;
                    prevLi.addEventListener("click", function(e) {
                        e.preventDefault();
                        currentPage--;
                        updateTable();
                    });
                    pagination.appendChild(prevLi);
                }

                // Page numbers
                for (let i = 1; i <= totalPages; i++) {
                    const li = document.createElement("li");
                    li.classList.add("page-item");
                    if (i === currentPage) li.classList.add("active");
                    li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                    li.addEventListener("click", function(e) {
                        e.preventDefault();
                        currentPage = i;
                        updateTable();
                    });
                    pagination.appendChild(li);
                }

                // Next button
                if (currentPage < totalPages) {
                    const nextLi = document.createElement("li");
                    nextLi.classList.add("page-item");
                    nextLi.innerHTML =
                        `<a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>`;
                    nextLi.addEventListener("click", function(e) {
                        e.preventDefault();
                        currentPage++;
                        updateTable();
                    });
                    pagination.appendChild(nextLi);
                }
            }

            searchInput.addEventListener("input", () => {
                currentPage = 1;
                updateTable();
            });

            rowsPerPageSelect.addEventListener("change", () => {
                currentPage = 1;
                updateTable();
            });

            // Initialize table
            updateTable();
        });
    </script>
@endsection

@push('styles')
    <style>
        .form-control-color {
            max-width: 50px;
            height: 38px;
            padding: 0.2rem;
            border-radius: 0.375rem 0 0 0.375rem;
        }

        .table-secondary {
            background-color: rgba(108, 117, 125, 0.1);
        }

        .table-light {
            background-color: rgba(0, 0, 0, 0.025);
        }

        .badge {
            font-size: 0.875em;
        }

        .btn-group .btn {
            margin-right: 2px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        .text-center i.mdi-48px {
            font-size: 3rem;
        }

        .modal-lg {
            max-width: 800px;
        }

        .input-group-text {
            min-width: 60px;
            text-align: center;
        }

        .list-group-item {
            padding: 0.5rem 0.75rem;
            border: 1px solid rgba(0, 0, 0, .125);
            margin-bottom: 2px;
        }

        .card .card-body {
            padding: 1rem;
        }

        .card.bg-primary,
        .card.bg-success,
        .card.bg-info,
        .card.bg-warning {
            border: none;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            background-color: #f8f9fa;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, .075);
        }

        .mdi-subdirectory-arrow-right {
            font-size: 1.2em;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        #iconPreview,
        #edit_iconPreview {
            min-height: 60px;
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background-color: #f8f9fa;
        }

        #show_icon {
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background-color: #f8f9fa;
        }

        /* Pagination Styles */
        #pagination {
            margin-top: 20px;
        }

        #pagination .page-item {
            margin: 0 2px;
        }

        #pagination .page-link {
            border: 1px solid #dee2e6;
            color: #4b4b4b;
            padding: 6px 12px;
            border-radius: 4px;
            background-color: #fff;
            transition: all 0.3s ease;
        }

        #pagination .page-link:hover {
            background-color: #667eea;
            color: #fff;
            border-color: #667eea;
        }

        #pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-color: transparent;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
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
            width: 100px;
            height: 100px;
        }

        /* Color badge styling */
        .badge[style*="background-color"] {
            font-weight: bold;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Search and pagination controls */
        .form-select {
            min-width: 80px;
        }

        /* Modal improvements */
        .modal-header.bg-primary {
            border-bottom: none;
        }

        .modal-footer.bg-light {
            border-top: 1px solid #dee2e6;
        }

        /* Button improvements */
        .btn-outline-info:hover {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .btn-outline-success:hover {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        /* Loading state for buttons */
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .btn-group .btn {
                margin-bottom: 2px;
            }

            .modal-lg {
                max-width: 95%;
            }

            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 10px;
            }

            .d-flex.justify-content-between>div {
                width: 100%;
            }
        }
    </style>
@endpush
