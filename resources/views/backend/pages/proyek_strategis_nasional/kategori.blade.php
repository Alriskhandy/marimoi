{{-- File: resources/views/backend/pages/data-spasial/kategori_index.blade.php --}}

@extends('backend.partials.main', ['title' => 'Kategori Proyek Strategis Nasional'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-tag-multiple"></i>
            </span> Kategori Proyek Strategis Nasional
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('psn.index') }}">Proyek Strategis Nasional</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Kategori Proyek
                </li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">Daftar Kategori Proyek Strategis Nasional</h4>
                        <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                            data-bs-target="#addModal">
                            <i class="mdi mdi-plus"></i> Tambah Kategori
                        </button>
                    </div>

                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <div class="row">
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
                                    <h2 class="mb-5">{{ $childKategoris->flatten()->count() }}</h2>
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
                                        Total Proyek
                                        <i class="mdi mdi-briefcase-check mdi-24px float-end"></i>
                                    </h4>
                                    <h2 class="mb-5">{{ $parentKategoris->sum('proyeks_count') }}</h2>
                                    <h6 class="card-text">Akumulasi proyek strategis</h6>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 stretch-card grid-margin">
                            <div class="card bg-gradient-warning card-img-holder text-white">
                                <div class="card-body">
                                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}"
                                        class="card-img-absolute" alt="circle" />
                                    <h4 class="font-weight-normal mb-3">
                                        Kategori Aktif
                                        <i class="mdi mdi-check-circle-outline mdi-24px float-end"></i>
                                    </h4>
                                    <h2 class="mb-5">{{ $parentKategoris->where('proyeks_count', '>', 0)->count() }}</h2>
                                    <h6 class="card-text">Memiliki proyek aktif</h6>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="table-responsive">
                        <table class="table table-hover" id="kategoriTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Warna</th>
                                    {{-- <th>Deskripsi</th> --}}
                                    <th>Parent</th>
                                    <th>Jumlah Proyek</th>
                                    <th>Sub Kategori</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @forelse($parentKategoris as $kategori)
                                    <tr data-id="{{ $kategori->id }}">
                                        <td>{{ $no++ }}</td>
                                        <td>
                                            <strong>{{ $kategori->nama }}</strong>
                                        </td>
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
                                        {{-- <td>
                                            @if ($kategori->deskripsi)
                                                {{ Str::limit($kategori->deskripsi, 50) }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td> --}}
                                        <td>
                                            <span class="badge badge-secondary">Root</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">
                                                {{ $kategori->proyeks_count ?? 0 }}
                                            </span>

                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ isset($childKategoris[$kategori->id]) ? $childKategoris[$kategori->id]->count() : 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show"
                                                    data-id="{{ $kategori->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#showModal" title="Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning btn-edit"
                                                    data-id="{{ $kategori->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#editModal" title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                {{-- @if (($kategori->proyeks_count ?? 0) > 0)
                                                    <a href="{{ route('psn.kategori.show', $kategori->id) }}"
                                                        class="btn btn-sm btn-outline-success" title="Lihat Proyek">
                                                        <i class="mdi mdi-eye-check"></i>
                                                    </a>
                                                @endif --}}
                                                <form action="{{ route('kategori-psn.destroy', $kategori->id) }}"
                                                    method="POST" style="display: inline-block;"
                                                    onsubmit="return confirmDelete('{{ $kategori->nama }}', {{ $kategori->proyeks_count ?? 0 }})">
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

                                    {{-- Sub Categories --}}
                                    @if (isset($childKategoris[$kategori->id]) && $childKategoris[$kategori->id]->count() > 0)
                                        @foreach ($childKategoris[$kategori->id] as $child)
                                            <tr data-id="{{ $child->id }}" class="table-secondary">
                                                <td>{{ $no++ }}</td>
                                                <td>
                                                    <i class="mdi mdi-subdirectory-arrow-right text-muted me-1"></i>
                                                    <strong>{{ $child->nama }}</strong>
                                                    <br><small class="text-muted">Sub dari: {{ $kategori->nama }}</small>
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
                                                {{-- <td>
                                                    @if ($child->deskripsi)
                                                        {{ Str::limit($child->deskripsi, 50) }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td> --}}
                                                <td>
                                                    <span class="badge badge-info">{{ $kategori->nama }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        {{ $child->proyeks_count ?? 0 }}
                                                    </span>
                                                    @if (($child->proyeks_count ?? 0) > 0)
                                                        <br><small class="text-muted">
                                                            <a href="{{ route('psn.kategori.show', $child->id) }}"
                                                                class="text-decoration-none">
                                                                Lihat Proyek
                                                            </a>
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-secondary">0</span>
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
                                                            class="btn btn-sm btn-outline-warning btn-edit"
                                                            data-id="{{ $child->id }}" data-bs-toggle="modal"
                                                            data-bs-target="#editModal" title="Edit">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </button>
                                                        @if (($child->proyeks_count ?? 0) > 0)
                                                            <a href="{{ route('psn.kategori.show', $child->id) }}"
                                                                class="btn btn-sm btn-outline-success"
                                                                title="Lihat Proyek">
                                                                <i class="mdi mdi-eye-check"></i>
                                                            </a>
                                                        @endif
                                                        <form action="{{ route('kategori-psn.destroy', $child->id) }}"
                                                            method="POST" style="display: inline-block;"
                                                            onsubmit="return confirmDelete('{{ $child->nama }}', {{ $child->proyeks_count ?? 0 }})">
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
                                        <td colspan="8" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-tag-multiple mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Belum ada kategori PSN yang dibuat</p>
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
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="mdi mdi-plus"></i> Tambah Kategori PSN
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
                        <i class="mdi mdi-pencil"></i> Edit Kategori PSN
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
                        <i class="mdi mdi-eye-outline me-1"></i> Detail Kategori PSN
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
                            <span><strong>Jumlah Proyek:</strong></span>
                            <span id="show_proyek_count" class="badge bg-success"></span>
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

                    <div class="mb-3" id="show_proyek_container" style="display: none;">
                        <strong>Proyek Terbaru:</strong>
                        <div id="show_proyeks" class="mt-2 ps-3 border-start border-3 border-primary"></div>
                        <div class="mt-3">
                            <a href="#" id="show_all_proyeks_link" class="btn btn-sm btn-outline-primary">
                                <i class="mdi mdi-eye-check"></i> Lihat Semua Proyek
                            </a>
                        </div>
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

            // Confirm Delete Function
            window.confirmDelete = function(nama, proyekCount) {
                if (proyekCount > 0) {
                    alert(
                        `Tidak dapat menghapus kategori "${nama}" karena masih memiliki ${proyekCount} proyek.`
                    );
                    return false;
                }
                return confirm(`Yakin ingin menghapus kategori "${nama}"?`);
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
                // Use static data for now, you can replace with AJAX call
                const parentKategoris = @json($parentKategoris);
                selectElement.empty();
                selectElement.append('<option value="">-- Pilih Parent (Opsional) --</option>');
                $.each(parentKategoris, function(index, kategori) {
                    if (excludeId && kategori.id == excludeId) return;
                    selectElement.append(`<option value="${kategori.id}">${kategori.nama}</option>`);
                });
            }

            // Color preview functionality
            $('#add_warna').on('change input', function() {
                $('#add_colorPreview').css('background-color', $(this).val());
            });

            $('#edit_warna').on('change input', function() {
                $('#edit_colorPreview').css('background-color', $(this).val());
            });

            // Add Modal
            $('#addModal').on('show.bs.modal', function() {
                const form = $('#addForm');
                form[0].reset();
                clearFormErrors(form);
                loadParentCategories($('#add_parent_id'));
                $('#add_colorPreview').css('background-color', '#007bff');
            });

            // Add Form Submit
            $('#addForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route('kategori-psn.store') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#addModal').modal('hide');
                            showAlert(response.message);
                            location.reload(); // Reload page to update table
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

            // Edit Modal
            $(document).on('click', '.btn-edit', function() {
                const id = $(this).data('id');
                const form = $('#editForm');

                $.get(`{{ route('kategori-psn.index') }}/${id}/edit`, function(data) {
                    if (data.success) {
                        $('#edit_id').val(data.data.id);
                        $('#edit_nama').val(data.data.nama);
                        $('#edit_warna').val(data.data.warna || '#007bff');
                        $('#edit_deskripsi').val(data.data.deskripsi);
                        $('#edit_colorPreview').css('background-color', data.data.warna ||
                            '#007bff');

                        // Load parent categories
                        const parentSelect = $('#edit_parent_id');
                        parentSelect.empty();
                        parentSelect.append(
                            '<option value="">-- Pilih Parent (Opsional) --</option>');

                        if (data.parentKategoris) {
                            $.each(data.parentKategoris, function(index, kategori) {
                                if (kategori.id == data.data.id) return; // Exclude self
                                const selected = data.data.parent_id == kategori.id ?
                                    'selected' : '';
                                parentSelect.append(
                                    `<option value="${kategori.id}" ${selected}>${kategori.nama}</option>`
                                );
                            });
                        }

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
                    url: `{{ route('kategori-psn.index') }}/${id}`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#editModal').modal('hide');
                            showAlert(response.message);
                            location.reload(); // Reload page to update table
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

                $.get(`{{ route('kategori-psn.index') }}/${id}`, function(data) {
                    if (data.success) {
                        const kategori = data.data;
                        console.log(kategori);
                        $('#show_nama').text(kategori.nama);
                        $('#show_parent').text(kategori.parent ? kategori.parent.nama : 'Root');
                        $('#show_children_count').text(kategori.children ? kategori.children
                            .length : 0);
                        $('#show_proyek_count').text(kategori.proyeks_count || 0);
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

                        // Show children if any
                        if (kategori.children && kategori.children.length > 0) {
                            let childrenHtml = '';
                            $.each(kategori.children, function(index, child) {
                                childrenHtml +=
                                    `<span class="badge badge-info me-1">${child.nama}</span>`;
                            });
                            $('#show_children').html(childrenHtml);
                            $('#show_children_container').show();
                        } else {
                            $('#show_children_container').hide();
                        }

                        // Show recent projects if any
                        if (kategori.proyeks && kategori.proyeks.length > 0) {
                            let proyeksHtml = '<div class="list-group">';
                            $.each(kategori.proyeks.slice(0, 5), function(index, proyek) {
                                proyeksHtml += `
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">${proyek.deskripsi || 'Proyek ' + (index + 1)}</h6>
                                            <small>Tahun ${proyek.tahun}</small>
                                        </div>
                                    </div>
                                `;
                            });
                            proyeksHtml += '</div>';
                            $('#show_proyeks').html(proyeksHtml);
                            $('#show_all_proyeks_link').attr('href',
                                `{{ route('psn.kategori.show', '') }}/${kategori.id}`);
                            $('#show_proyek_container').show();
                        } else {
                            $('#show_proyek_container').hide();
                        }
                    }
                });
            });

            // Initialize DataTable if there are many rows
            if ($('#kategoriTable tbody tr').length > 10) {
                $('#kategoriTable').DataTable({
                    responsive: true,
                    pageLength: 25,
                    order: [
                        [0, 'asc']
                    ],
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json'
                    },
                    columnDefs: [{
                            orderable: false,
                            targets: [2, 7]
                        } // Disable sorting on Warna and Aksi columns
                    ]
                });
            }

            // Auto-refresh statistics every 2 minutes
            setInterval(function() {
                $.get('{{ route('kategori-psn.api.categories') }}')
                    .done(function(data) {
                        if (data.success) {
                            // Update badge counts
                            data.categories.forEach(function(kategori) {
                                $(`tr[data-id="${kategori.id}"] .badge-primary`).text(kategori
                                    .proyek_count);
                            });
                        }
                    })
                    .fail(function() {
                        console.log('Failed to refresh category statistics');
                    });
            }, 120000);
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
    </style>
@endpush
