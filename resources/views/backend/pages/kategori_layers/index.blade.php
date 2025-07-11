@extends('backend.partials.main')

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-layers"></i>
            </span> Kategori Layer
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span></span>Kategori Layer
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

                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="kategoriTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Deskripsi</th>
                                    <th>Parent</th>
                                    {{-- <th>Jumlah Anak</th> --}}
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kategoriLayers as $index => $kategori)
                                    <tr data-id="{{ $kategori->id }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $kategori->nama }}</strong>
                                            @if ($kategori->parent_id)
                                                <br><small class="text-muted">{{ $kategori->full_path }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($kategori->deskripsi)
                                                {{ Str::limit($kategori->deskripsi, 50) }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($kategori->parent)
                                                <span class="badge badge-info">{{ $kategori->parent->nama }}</span>
                                            @else
                                                <span class="badge badge-secondary">Root</span>
                                            @endif
                                        </td>
                                        {{-- <td>
                                            <span class="badge badge-primary">{{ $kategori->children->count() }}</span>
                                        </td> --}}
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show"
                                                    data-id="{{ $kategori->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#showModal">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning btn-edit"
                                                    data-id="{{ $kategori->id }}" data-bs-toggle="modal"
                                                    data-bs-target="#editModal">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <form action="{{ route('kategori-layers.destroy', $kategori->id) }}"
                                                    method="POST" style="display: inline-block;"
                                                    onsubmit="return confirm('Yakin ingin menghapus kategori {{ $kategori->nama }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-layers mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Belum ada kategori layer yang dibuat</p>
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
        <div class="modal-dialog">
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
                        <div class="form-group mb-3">
                            <label for="add_nama" class="form-label">Nama Kategori <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_nama" name="nama" required>
                            <div class="invalid-feedback"></div>
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
        <div class="modal-dialog">
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
                        <div class="form-group mb-3">
                            <label for="edit_nama" class="form-label">Nama Kategori <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nama" name="nama" required>
                            <div class="invalid-feedback"></div>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="mdi mdi-eye"></i> Detail Kategori Layer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Nama:</strong>
                            <p id="show_nama" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Parent:</strong>
                            <p id="show_parent" class="text-muted"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Jumlah Anak:</strong>
                            <p id="show_children_count" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Dibuat:</strong>
                            <p id="show_created_at" class="text-muted"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <strong>Deskripsi:</strong>
                            <p id="show_deskripsi" class="text-muted"></p>
                        </div>
                    </div>
                    <div class="row" id="show_children_container" style="display: none;">
                        <div class="col-12">
                            <strong>Kategori Anak:</strong>
                            <div id="show_children" class="mt-2"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
                $.get('{{ route('kategori-layers.create') }}', function(data) {
                    selectElement.empty();
                    selectElement.append('<option value="">-- Pilih Parent (Opsional) --</option>');
                    $.each(data.parentKategori, function(index, kategori) {
                        if (excludeId && kategori.id == excludeId) return;
                        selectElement.append(
                            `<option value="${kategori.id}">${kategori.nama}</option>`);
                    });
                });
            }

            // Add Modal
            $('#addModal').on('show.bs.modal', function() {
                const form = $('#addForm');
                form[0].reset();
                clearFormErrors(form);
                loadParentCategories($('#add_parent_id'));
            });

            // Add Form Submit
            $('#addForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route('kategori-layers.store') }}',
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

                $.get(`{{ route('kategori-layers.index') }}/${id}/edit`, function(data) {
                    if (data.success) {
                        $('#edit_id').val(data.data.id);
                        $('#edit_nama').val(data.data.nama);
                        $('#edit_deskripsi').val(data.data.deskripsi);

                        // Load parent categories
                        const parentSelect = $('#edit_parent_id');
                        parentSelect.empty();
                        parentSelect.append(
                            '<option value="">-- Pilih Parent (Opsional) --</option>');
                        $.each(data.parentKategori, function(index, kategori) {
                            const selected = data.data.parent_id == kategori.id ?
                                'selected' : '';
                            parentSelect.append(
                                `<option value="${kategori.id}" ${selected}>${kategori.nama}</option>`
                            );
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
                    url: `{{ route('kategori-layers.index') }}/${id}`,
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

                $.get(`{{ route('kategori-layers.index') }}/${id}`, function(data) {
                    if (data.success) {
                        const kategori = data.data;
                        $('#show_nama').text(kategori.nama);
                        $('#show_parent').text(kategori.parent ? kategori.parent.nama : 'Root');
                        $('#show_children_count').text(kategori.children.length);
                        $('#show_created_at').text(new Date(kategori.created_at).toLocaleDateString(
                            'id-ID'));
                        $('#show_deskripsi').text(kategori.deskripsi || 'Tidak ada deskripsi');

                        // Show children if any
                        if (kategori.children.length > 0) {
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
                    }
                });
            });


        });
    </script>
@endsection
