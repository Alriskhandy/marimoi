@extends('backend.partials.main', ['title' => 'Kategori Aspirasi'])

@section('main')
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
                <li class="breadcrumb-item active" aria-current="page">Kategori Aspirasi</li>
            </ul>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Statistics Cards -->
    @if ($kategoriAspirasi->count() > 0)
        <div class="row g-3 stats-row-compact">
            @foreach ([['Total Kategori', $stats['total'], 'primary', 'mdi-tag-multiple'], ['Dengan OPD', $stats['dengan_opd'], 'success', 'mdi-domain'], ['Umum (tanpa OPD)', $stats['tanpa_opd'], 'warning', 'mdi-help-circle-outline'], ['Aspirasi Masuk', $stats['total_aspirasi'], 'info', 'mdi-message-text']] as [$label, $value, $color, $icon])
                <div class="col-6 col-md-3">
                    <div class="card stat-card-compact bg-gradient-{{ $color }} text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="stat-label">{{ $label }}</p>
                                    <h3 class="stat-value">{{ number_format($value) }}</h3>
                                </div>
                                <i class="mdi {{ $icon }} stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Daftar Kategori</h4>
                            <p class="text-muted small mb-0">Kategori usulan menentukan OPD yang menerima notifikasi
                                aspirasi.</p>
                        </div>
                        @can('kategori-aspirasi.create')
                            <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                data-bs-target="#addModal">
                                <i class="mdi mdi-plus"></i> Tambah Kategori
                            </button>
                        @endcan
                    </div>

                    <div id="alertContainer"></div>

                    <div class="row g-3 align-items-end justify-content-between filter-toolbar mb-4">
                        <div class="col-lg-4 col-md-6">
                            <label for="tableSearch" class="form-label fw-semibold mb-1">
                                <i class="mdi mdi-magnify me-1"></i>Cari Kategori
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control filter-control" id="tableSearch"
                                    placeholder="Nama kategori, OPD, atau deskripsi...">
                                <button class="btn btn-primary" type="button" id="searchTableBtn" title="Cari">
                                    <i class="mdi mdi-magnify"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="filterOPD" class="form-label fw-semibold mb-1">
                                <i class="mdi mdi-domain me-1"></i>OPD
                            </label>
                            <select id="filterOPD" class="form-select filter-control">
                                <option value="">Semua OPD</option>
                                <option value="with">Dengan OPD</option>
                                <option value="without">Umum (tanpa OPD)</option>
                                @foreach ($opdList->sortBy('singkatan') as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->singkatan }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label for="sortBySelect" class="form-label fw-semibold mb-1">
                                <i class="mdi mdi-sort me-1"></i>Urutkan
                            </label>
                            <select class="form-select filter-control" id="sortBySelect">
                                <option value="0-asc">Nama (A-Z)</option>
                                <option value="0-desc">Nama (Z-A)</option>
                                <option value="1-asc">OPD (A-Z)</option>
                                <option value="2-desc">Aspirasi terbanyak</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <button type="button" class="btn btn-outline-secondary w-100" id="resetFiltersBtn"
                                title="Reset semua filter">
                                <i class="mdi mdi-filter-remove"></i> Reset
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped" id="kategoriTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Nama Kategori</th>
                                    <th>OPD Tujuan</th>
                                    <th>Aspirasi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="kategoriTableBody">
                                @foreach ($kategoriAspirasi as $kategori)
                                    <tr class="kategori-row" data-opd-id="{{ $kategori->opd_id ?? '' }}"
                                        data-has-opd="{{ $kategori->opd ? 'true' : 'false' }}">
                                        <td>
                                            <span class="text-dark fw-bold kategori-name">{{ $kategori->nama_kategori }}</span>
                                            @if ($kategori->deskripsi)
                                                <br><small class="text-muted" title="{{ $kategori->deskripsi }}">{{ Str::limit($kategori->deskripsi, 80) }}</small>
                                            @endif
                                        </td>
                                        <td data-order="{{ $kategori->opd?->singkatan ?? 'zzz' }}">
                                            @if ($kategori->opd)
                                                <span class="fw-semibold">{{ $kategori->opd->singkatan }}</span>
                                                <br><small class="text-muted">{{ Str::limit($kategori->opd->name, 40) }}</small>
                                            @else
                                                <span class="badge bg-light text-muted border">Umum</span>
                                            @endif
                                        </td>
                                        <td data-order="{{ $kategori->aspirasi_count }}">
                                            <span class="badge {{ $kategori->aspirasi_count > 0 ? 'bg-success text-white' : 'bg-light text-muted border' }}">
                                                {{ number_format($kategori->aspirasi_count) }} aspirasi
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-show"
                                                    data-id="{{ $kategori->id }}" title="Lihat detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                @if ($kategori->id != 1)
                                                    @can('kategori-aspirasi.edit')
                                                        <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                            style="margin-left:-1px;"
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
                                                    @endcan
                                                    @can('kategori-aspirasi.delete')
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                                            style="margin-left:-1px;"
                                                            data-id="{{ $kategori->id }}"
                                                            onclick="deleteKategori({{ $kategori->id }})" title="Hapus">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambah Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="addForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">
                            <i class="mdi mdi-tag-plus-outline"></i> Tambah Kategori
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_nama_kategori" class="form-label fw-semibold">Nama Kategori <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="nama_kategori" id="add_nama_kategori" class="form-control"
                                placeholder="Contoh: Infrastruktur Jalan" maxlength="255">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="add_opd_id" class="form-label fw-semibold">OPD Penanggung Jawab <span
                                    class="text-danger">*</span></label>
                            <select name="opd_id" id="add_opd_id" class="form-select">
                                <option value="">-- Pilih OPD --</option>
                                @foreach ($opdList->sortBy('singkatan') as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->singkatan }} - {{ $opd->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            <div class="form-text">OPD ini menerima notifikasi email setiap ada usulan di kategori
                                ini.</div>
                        </div>

                        <div class="mb-0">
                            <label for="add_deskripsi" class="form-label fw-semibold">Deskripsi</label>
                            <textarea name="deskripsi" id="add_deskripsi" class="form-control" rows="3"
                                placeholder="Cakupan usulan yang masuk ke kategori ini (opsional)"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="showModalLabel">
                        <i class="mdi mdi-tag-outline"></i> Detail Kategori
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <h4 id="show_nama_kategori" class="detail-title mb-1"></h4>
                    <p class="text-muted small mb-3"><i class="mdi mdi-domain"></i> <span id="show_opd"></span></p>

                    <p class="detail-section-label">Deskripsi</p>
                    <div id="show_deskripsi" class="detail-text mb-3"></div>

                    <div class="row g-2 detail-stats">
                        <div class="col-4">
                            <div class="detail-stat">
                                <span class="detail-stat-value" id="show_aspirasi_count">0</span>
                                <span class="detail-stat-label">Aspirasi</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="detail-stat">
                                <span class="detail-stat-value fs-6" id="show_created_at"></span>
                                <span class="detail-stat-label">Dibuat</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="detail-stat">
                                <span class="detail-stat-value fs-6" id="show_updated_at"></span>
                                <span class="detail-stat-label">Diubah</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">
                            <i class="mdi mdi-pencil"></i> Ubah Kategori
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="mb-3">
                            <label for="edit_nama_kategori" class="form-label fw-semibold">Nama Kategori <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_kategori" id="edit_nama_kategori"
                                maxlength="255">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_opd_id" class="form-label fw-semibold">OPD Penanggung Jawab <span
                                    class="text-danger">*</span></label>
                            <select name="opd_id" id="edit_opd_id" class="form-select">
                                <option value="">-- Pilih OPD --</option>
                                @foreach ($opdList->sortBy('singkatan') as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->singkatan }} - {{ $opd->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            <div class="form-text">Usulan baru di kategori ini dikirim ke email OPD tersebut.</div>
                        </div>

                        <div class="mb-0">
                            <label for="edit_deskripsi" class="form-label fw-semibold">Deskripsi</label>
                            <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-gradient-primary">
                            <i class="mdi mdi-content-save"></i> Simpan Perubahan
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
                form.find('#add_opd_id').trigger('change.select2');
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
                $('#edit_opd_id').val($(this).data('opd-id')).trigger('change.select2');
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
                    '<i class="mdi mdi-loading mdi-spin"></i> Menyimpan...');

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
                            '<i class="mdi mdi-content-save"></i> Simpan Perubahan');
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
                        const tanggal = (value) => value ? new Date(value).toLocaleDateString('id-ID', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        }) : '-';

                        $('#show_opd').text(kategori.opd ?
                            `${kategori.opd.singkatan} - ${kategori.opd.name}` :
                            'Umum (tanpa OPD)');
                        $('#show_deskripsi').text(kategori.deskripsi || 'Tidak ada deskripsi.')
                            .toggleClass('text-muted', !kategori.deskripsi);
                        $('#show_aspirasi_count').text((kategori.aspirasi_count ?? 0).toLocaleString('id-ID'));
                        $('#show_created_at').text(tanggal(kategori.created_at));
                        $('#show_updated_at').text(tanggal(kategori.updated_at));

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

            // Daftar kategori: DataTables + toolbar filter (pola sama dengan Daftar Layer & Data).
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'kategoriTable') {
                    return true;
                }

                const opdFilter = $('#filterOPD').val();
                if (!opdFilter) {
                    return true;
                }

                const $row = $(settings.aoData[dataIndex].nTr);
                if (opdFilter === 'with') {
                    return $row.data('has-opd') === true;
                }
                if (opdFilter === 'without') {
                    return $row.data('has-opd') === false;
                }

                return String($row.data('opd-id')) === opdFilter;
            });

            $('#kategoriTable').DataTable({
                "pageLength": 25,
                "order": [
                    [0, 'asc']
                ],
                "columnDefs": [{
                        "orderable": false,
                        "searchable": false,
                        "targets": [-1]
                    },
                    {
                        "className": "text-center",
                        "targets": [2, -1]
                    },
                ],
                "language": {
                    "zeroRecords": "Kategori tidak ditemukan. Coba ubah kata kunci atau reset filter.",
                    "emptyTable": "Belum ada kategori aspirasi",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ kategori",
                    "infoEmpty": "Menampilkan 0 dari 0 kategori",
                    "infoFiltered": "(difilter dari _MAX_ total kategori)",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    },
                },
                "dom": '<"row"<"col-sm-12"tr>>' +
                    '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "initComplete": function() {
                    const api = this.api();

                    $('#tableSearch').on('input', function() {
                        api.search($(this).val()).draw();
                    });
                    $('#searchTableBtn').on('click', function() {
                        api.search($('#tableSearch').val()).draw();
                    });
                    $('#filterOPD').on('change', function() {
                        api.draw();
                    });
                    $('#sortBySelect').on('change', function() {
                        const [column, direction] = $(this).val().split('-');
                        api.order([parseInt(column, 10), direction]).draw();
                    });
                    $('#resetFiltersBtn').on('click', function() {
                        $('#tableSearch').val('');
                        $('#filterOPD').val('');
                        $('#sortBySelect').val('0-asc');
                        api.search('').order([0, 'asc']).draw();
                    });
                }
            });
        });
    </script>

    <style>
        /* Pola tampilan daftar — mengikuti halaman Daftar Layer & Data (spatial-layers/index). */
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #495057;
            padding: 10px 8px;
        }

        .table td {
            vertical-align: middle;
            padding: 10px 8px;
            border-bottom: 1px solid #eef2f7;
            font-size: 0.85rem;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .stats-row-compact {
            margin-bottom: 1rem;
        }

        .stat-card-compact {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
        }

        .stat-card-compact .card-body {
            padding: 0.85rem 1rem;
        }

        .stat-card-compact .stat-label {
            margin: 0 0 2px;
            font-size: 0.75rem;
            font-weight: 500;
            opacity: 0.9;
            white-space: nowrap;
        }

        .stat-card-compact .stat-value {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .stat-card-compact .stat-icon {
            font-size: 2rem;
            opacity: 0.5;
        }

        /* Modal detail kategori */
        .detail-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1f2937;
        }

        .detail-text {
            white-space: pre-line;
            word-break: break-word;
            color: #374151;
        }

        .detail-section-label {
            margin-bottom: 0.4rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
        }

        .detail-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.75rem 0.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
        }

        .detail-stat-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }

        .detail-stat-label {
            margin-top: 2px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
        }
    </style>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/select2/select2.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('backend/assets/vendors/select2/select2.min.js') }}"></script>
    <script>
        $(function() {
            const opdSelect2 = {
                width: '100%',
                language: {
                    noResults: () => 'OPD tidak ditemukan',
                    searching: () => 'Mencari...'
                }
            };

            $('#add_opd_id').select2({
                ...opdSelect2,
                dropdownParent: $('#addModal')
            });
            $('#edit_opd_id').select2({
                ...opdSelect2,
                dropdownParent: $('#editModal')
            });
        });
    </script>
@endpush
