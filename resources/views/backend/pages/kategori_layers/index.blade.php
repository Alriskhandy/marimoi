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
                                    <input type="text" id="searchInput" class="form-control"
                                        placeholder="Cari kategori...">
                                </div>
                            </div>

                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Warna</th> <!-- Ubah dari Deskripsi -->
                                    <th>Parent</th>
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
                                            @if ($kategori->warna)
                                                <span class="badge rounded-pill px-3 py-2 text-white"
                                                    style="background-color: {{ $kategori->warna }}">
                                                    {{ $kategori->warna }}
                                                </span>
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
                            <label for="add_warna" class="form-label">Warna</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="add_warna"
                                    name="warna" value="#007bff">
                                <span class="input-group-text" id="add_colorPreview"
                                    style="background-color: #007bff; color: white;">●</span>
                            </div>
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
                            <label for="edit_warna" class="form-label">Warna</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="edit_warna"
                                    name="warna">
                                <span class="input-group-text" id="edit_colorPreview"
                                    style="background-color: #007bff; color: white;">●</span>
                            </div>
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
    <!-- Show Modal -->
    <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <!-- Ukuran lebih lebar agar nyaman dibaca -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="mdi mdi-eye"></i> Detail Kategori Layer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Nama Kategori:</strong>
                            <p id="show_nama" class="text-muted mb-0"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Warna:</strong><br>
                            <span id="show_warna" class="badge rounded-pill px-3 py-2"
                                style="background-color: #ccc;">-</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Parent:</strong>
                            <p id="show_parent" class="text-muted mb-0"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Jumlah Anak:</strong>
                            <p id="show_children_count" class="text-muted mb-0"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Tanggal Dibuat:</strong>
                            <p id="show_created_at" class="text-muted mb-0"></p>
                        </div>
                        <div class="col-md-6"></div>
                    </div>

                    <div class="row mb-3">
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
        document.addEventListener("DOMContentLoaded", function() {
            // Preview warna saat memilih (add)
            const addWarna = document.getElementById('add_warna');
            const addPreview = document.getElementById('add_colorPreview');
            addWarna.addEventListener('input', () => {
                addPreview.style.backgroundColor = addWarna.value;
            });

            // Preview warna saat memilih (edit)
            const editWarna = document.getElementById('edit_warna');
            const editPreview = document.getElementById('edit_colorPreview');
            editWarna.addEventListener('input', () => {
                editPreview.style.backgroundColor = editWarna.value;
            });
        });
    </script>

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
                            if (xhr.responseJSON.errors) {
                                showFormErrors(form, xhr.responseJSON.errors); // validasi field
                            }

                            if (xhr.responseJSON.message) {
                                showAlert(xhr.responseJSON.message,
                                    'error'
                                ); // pesan umum dari server (misal: "Nama sudah digunakan")
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

                        // Tampilkan warna
                        $('#show_warna')
                            .text(kategori.warna || '-')
                            .css('background-color', kategori.warna || '#ccc');

                        // Tampilkan anak jika ada
                        if (kategori.children.length > 0) {
                            let childrenHtml = '';
                            $.each(kategori.children, function(index, child) {
                                childrenHtml +=
                                    `<span class="badge bg-info me-1 mb-1">${child.nama}</span>`;
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
    <script>
        document.addEventListener("DOMContentLoaded", function() {
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

                if (totalFiltered <= 10) {
                    pagination.style.display = "none";
                    return;
                }

                pagination.style.display = "flex";

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
            }

            searchInput.addEventListener("input", () => {
                currentPage = 1;
                updateTable();
            });

            rowsPerPageSelect.addEventListener("change", () => {
                currentPage = 1;
                updateTable();
            });

            updateTable(); // inisialisasi awal
        });
    </script>
@endsection
@push('styles')
    <style>
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

        #show_warna {
            font-weight: bold;
            color: #fff;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
        }
    </style>
@endpush
