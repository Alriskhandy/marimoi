@extends('backend.partials.main', ['title' => 'Manajemen Role'])

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-shield-crown"></i>
            </span>
            Manajemen Role
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Manajemen Role
                </li>
            </ul>
        </nav>
    </div>

    <!-- Statistics Cards -->
    @if ($roleList->count() > 0)
        <div class="row mb-4">
            <div class="col-12 col-md-4 stretch-card grid-margin">
                <div class="card bg-gradient-primary card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Total Role
                            <i class="mdi mdi-shield-crown mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['total'] }}</h2>
                        <h6 class="card-text">Seluruh role terdaftar</h6>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 stretch-card grid-margin">
                <div class="card bg-gradient-success card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Role Aktif
                            <i class="mdi mdi-check-circle mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['aktif'] }}</h2>
                        <h6 class="card-text">Dapat digunakan untuk user baru</h6>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 stretch-card grid-margin">
                <div class="card bg-gradient-warning card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Role Nonaktif
                            <i class="mdi mdi-close-circle mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['nonaktif'] }}</h2>
                        <h6 class="card-text">Tidak tersedia untuk user baru</h6>
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
                            <i class="mdi mdi-shield-crown"></i>
                            Daftar Role
                        </h4>
                        <div>
                            <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                data-bs-target="#addModal">
                                <i class="mdi mdi-plus"></i> Tambah Role
                            </button>
                        </div>
                    </div>

                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="roleTable">
                            <thead>
                                <tr>
                                    <th class="text-dark text-center d-none d-md-table-cell" style="width: 50px;">No</th>
                                    <th class="text-dark" style="min-width: 200px;">Nama & Slug</th>
                                    <th class="text-dark d-none d-lg-table-cell">Deskripsi</th>
                                    <th class="text-dark text-center" style="width: 100px;">Pengguna</th>
                                    <th class="text-dark text-center" style="width: 190px;">Hak Akses</th>
                                    <th class="text-dark text-center" style="width: 100px;">Status</th>
                                    <th class="text-dark text-center" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="roleTableBody">
                                @forelse($roleList as $index => $role)
                                    @php $isReserved = in_array($role->slug, ['super-admin', 'admin-bappeda', 'admin-opd', 'user']); @endphp
                                    <tr>
                                        <td class="text-center d-none d-md-table-cell">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold">{{ $role->name }}</span>
                                                <small class="text-muted">
                                                    <code>{{ $role->slug }}</code>
                                                    @if ($isReserved)
                                                        <span class="badge bg-secondary ms-1">Role Sistem</span>
                                                    @endif
                                                </small>
                                            </div>
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            <span class="text-muted">{{ $role->description ?: '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $role->users_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-permissions"
                                                data-id="{{ $role->id }}" data-name="{{ $role->name }}"
                                                title="Atur hak akses role ini">
                                                <i class="mdi mdi-shield-key me-1"></i>
                                                {{ $role->slug === 'super-admin' ? 'Semua' : $role->permissions_count }}
                                                hak akses
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            @if ($role->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center" style="white-space: nowrap;">
                                            @can('roles.edit')
                                            <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                data-id="{{ $role->id }}" data-name="{{ $role->name }}"
                                                data-slug="{{ $role->slug }}"
                                                data-description="{{ $role->description }}"
                                                data-is-active="{{ $role->is_active ? 1 : 0 }}" data-bs-toggle="modal"
                                                data-bs-target="#editModal" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            @endcan
                                            @unless ($isReserved)
                                                @can('roles.delete')
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                                        onclick="deleteRole({{ $role->id }})" title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                @endcan
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-shield-crown-outline mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Belum ada data role</p>
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
            <form id="addForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="addModalLabel">
                            <i class="mdi mdi-plus me-2"></i> Tambah Role Baru
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body row">
                        <div class="col-md-6 mb-3">
                            <label for="add_name" class="form-label">Nama Role <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="add_name" class="form-control"
                                placeholder="Contoh: Admin Sektor">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_slug" class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug" id="add_slug" class="form-control"
                                placeholder="Contoh: admin-sektor">
                            <div class="form-text">Huruf kecil, angka, dan tanda hubung. Tidak dapat diubah setelah
                                dibuat.</div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="add_description" class="form-label">Deskripsi</label>
                            <textarea name="description" id="add_description" class="form-control" rows="2"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active"
                                    value="1" checked>
                                <label class="form-check-label" for="add_is_active">Aktif</label>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="editForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-gradient-success text-white">
                        <h5 class="modal-title" id="editModalLabel">
                            <i class="mdi mdi-pencil me-2"></i> Edit Role
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">Nama Role <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit_name">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="edit_slug" disabled>
                            <div class="form-text">Slug tidak dapat diubah.</div>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="edit_description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active"
                                    value="1">
                                <label class="form-check-label" for="edit_is_active">Aktif</label>
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
<div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="permissionModalLabel">
                    <i class="mdi mdi-shield-key me-1"></i>Hak Akses: <span id="permissionRoleName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="permissionLocked" class="alert alert-info d-none">
                    Super Admin selalu memiliki seluruh hak akses dan tidak dapat diubah.
                </div>
                <div id="permissionLoading" class="text-center text-muted py-4">Memuat hak akses...</div>
                <div id="permissionModules" class="row g-3"></div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="permissionCheckAll">Pilih semua</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="permissionUncheckAll">Kosongkan</button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                @can('roles.edit')
                    <button type="button" class="btn btn-primary" id="permissionSave">
                        <i class="mdi mdi-content-save"></i> Simpan
                    </button>
                @endcan
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            function showAlert(message, type = 'success') {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                $('#alertContainer').html(`
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                setTimeout(() => $('#alertContainer .alert').alert('close'), 5000);
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

            // Auto-generate slug from name on Add modal
            $('#add_name').on('input', function() {
                const slug = $(this).val()
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                $('#add_slug').val(slug);
            });

            $('#addModal').on('show.bs.modal', function() {
                const form = $('#addForm');
                form[0].reset();
                clearFormErrors(form);
            });

            $('#addForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                clearFormErrors(form);
                submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Menyimpan...');

                $.ajax({
                    url: "{{ route('roles.store') }}",
                    type: 'POST',
                    data: form.serialize() + '&is_active=' + ($('#add_is_active').is(':checked') ? 1 : 0),
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
                                text: xhr.responseJSON?.message || 'Terjadi kesalahan server.'
                            });
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('<i class="mdi mdi-content-save"></i> Simpan');
                    }
                });
            });

            $(document).on('click', '.btn-edit', function() {
                const form = $('#editForm');
                $('#edit_id').val($(this).data('id'));
                $('#edit_name').val($(this).data('name'));
                $('#edit_slug').val($(this).data('slug'));
                $('#edit_description').val($(this).data('description'));
                $('#edit_is_active').prop('checked', $(this).data('is-active') == 1);
                clearFormErrors(form);
            });

            $('#editForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const id = $('#edit_id').val();
                const submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Mengupdate...');

                $.ajax({
                    url: "{{ route('roles.update', ['role' => '__ID__']) }}".replace('__ID__', id),
                    type: 'PUT',
                    data: {
                        name: $('#edit_name').val(),
                        description: $('#edit_description').val(),
                        is_active: $('#edit_is_active').is(':checked') ? 1 : 0,
                        _token: $('meta[name="csrf-token"]').attr('content')
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
                            showAlert('Terjadi kesalahan server: ' + (xhr.responseJSON?.message ||
                                'Unknown error'), 'error');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('<i class="mdi mdi-content-save"></i> Update');
                    }
                });
            });

            window.deleteRole = function(id) {
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Role ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('roles.destroy', ['role' => '__ID__']) }}".replace('__ID__', id),
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
                                    setTimeout(() => location.reload(), 1500);
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    title: 'Error!',
                                    text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus data.',
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
        }

        .table td {
            border-color: #e9ecef;
            vertical-align: middle;
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }

        .modal-header.bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .modal-header.bg-gradient-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%) !important;
        }

        .invalid-feedback {
            font-size: 0.875em;
        }
    </style>
@endsection

@push('scripts')
    <script>
        $(function() {
            const permissionsUrl = "{{ route('roles.permissions', ['role' => '__ID__']) }}";
            const syncUrl = "{{ route('roles.permissions.sync', ['role' => '__ID__']) }}";
            const csrf = $('meta[name="csrf-token"]').attr('content');
            let currentRoleId = null;
            let locked = false;

            function renderModules(modules) {
                const html = modules.map(function(m) {
                    const items = m.actions.map(function(a) {
                        const id = 'perm-' + a.name.replace(/[^a-z0-9]/gi, '-');
                        return '<div class="form-check">' +
                            '<input class="form-check-input perm-check" type="checkbox" id="' + id + '" value="' + a.name + '"' +
                            (a.granted ? ' checked' : '') + (locked ? ' disabled' : '') + '>' +
                            '<label class="form-check-label" for="' + id + '">' + a.label + '</label></div>';
                    }).join('');
                    return '<div class="col-md-6"><div class="card h-100"><div class="card-body py-3">' +
                        '<h6 class="mb-2">' + m.label + '</h6>' + items + '</div></div></div>';
                }).join('');
                $('#permissionModules').html(html);
            }

            $(document).on('click', '.btn-permissions', function() {
                currentRoleId = $(this).data('id');
                $('#permissionRoleName').text($(this).data('name'));
                $('#permissionModules').empty();
                $('#permissionLocked').addClass('d-none');
                $('#permissionLoading').removeClass('d-none');
                new bootstrap.Modal(document.getElementById('permissionModal')).show();

                $.getJSON(permissionsUrl.replace('__ID__', currentRoleId), function(res) {
                    locked = res.data.locked;
                    $('#permissionLocked').toggleClass('d-none', !locked);
                    $('#permissionSave, #permissionCheckAll, #permissionUncheckAll').prop('disabled', locked);
                    renderModules(res.data.modules);
                }).fail(function() {
                    $('#permissionModules').html('<div class="col-12 text-danger">Gagal memuat hak akses.</div>');
                }).always(function() {
                    $('#permissionLoading').addClass('d-none');
                });
            });

            $('#permissionCheckAll').on('click', function() {
                $('.perm-check').prop('checked', true);
            });
            $('#permissionUncheckAll').on('click', function() {
                $('.perm-check').prop('checked', false);
            });

            $('#permissionSave').on('click', function() {
                const btn = $(this);
                const permissions = $('.perm-check:checked').map(function() {
                    return this.value;
                }).get();
                btn.prop('disabled', true);

                $.ajax({
                    url: syncUrl.replace('__ID__', currentRoleId),
                    type: 'PUT',
                    data: {
                        permissions: permissions
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrf
                    },
                    success: function(res) {
                        $('.btn-permissions[data-id="' + currentRoleId + '"]').html(
                            '<i class="mdi mdi-shield-key me-1"></i>' + permissions.length + ' hak akses');
                        bootstrap.Modal.getInstance(document.getElementById('permissionModal')).hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message,
                            timer: 1800,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan.'
                        });
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
