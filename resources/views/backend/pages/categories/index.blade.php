@extends('backend.partials.main', ['title' => 'Kategori'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-tematik"></i>
            </span>
            Kategori {{ isset($typeLabel) ? $typeLabel : '' }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Kategori
                </li>
            </ul>
        </nav>
    </div>

    @if (!request()->get('type'))
        <!-- Type Filter -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label">Filter berdasarkan Tipe:</label>
                                <select id="typeFilter" class="form-select d-inline-block w-auto">
                                    <option value="">Semua Tipe</option>
                                    <option value="tematik" {{ request('type') == 'tematik' ? 'selected' : '' }}>tematik
                                        (Lokasi)
                                    </option>
                                    <option value="psd" {{ request('type') == 'psd' ? 'selected' : '' }}>PSD (Proyek
                                        Strategis Daerah)</option>
                                    <option value="psn" {{ request('type') == 'psn' ? 'selected' : '' }}>PSN (Proyek
                                        Strategis Nasional)</option>
                                    <option value="pokir_dprd" {{ request('type') == 'pokir_dprd' ? 'selected' : '' }}>
                                        Pokir
                                        DPRD</option>
                                    <option value="usulan_musrenbang"
                                        {{ request('type') == 'usulan_musrenbang' ? 'selected' : '' }}>
                                        Musenbang
                                        (Usulan Musrenbang)</option>
                                </select>
                            </div>
                            <div>
                                <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                    data-bs-target="#addModal">
                                    <i class="mdi mdi-plus"></i> Tambah Kategori
                                </button>
                            </div>
                        </div>
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
                        <h4 class="card-title">Daftar Kategori</h4>
                        @if (request()->get('type'))
                            <div>
                                <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                    data-bs-target="#addModal">
                                    <i class="mdi mdi-plus"></i> Tambah Kategori
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif --}}

                    <!-- Statistics Cards -->
                    @if ($categories->count() > 0)
                        <div class="row mb-4">
                            <div class="col-md-4 stretch-card grid-margin">
                                <div class="card bg-gradient-primary card-img-holder text-white">
                                    <div class="card-body">
                                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}"
                                            class="card-img-absolute" alt="circle" />
                                        <h4 class="font-weight-normal mb-3">
                                            Kategori Utama
                                            <i class="mdi mdi-format-list-bulleted-type mdi-24px float-end"></i>
                                        </h4>
                                        <h2 class="mb-5">{{ $categories->where('parent_id', null)->count() }}</h2>
                                        <h6 class="card-text">Jumlah kategori induk</h6>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 stretch-card grid-margin">
                                <div class="card bg-gradient-success card-img-holder text-white">
                                    <div class="card-body">
                                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}"
                                            class="card-img-absolute" alt="circle" />
                                        <h4 class="font-weight-normal mb-3">
                                            Sub Kategori
                                            <i class="mdi mdi-subdirectory-arrow-right mdi-24px float-end"></i>
                                        </h4>
                                        <h2 class="mb-5">{{ $categories->where('parent_id', '!=', null)->count() }}</h2>
                                        <h6 class="card-text">Kategori turunan</h6>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 stretch-card grid-margin">
                                <div class="card bg-gradient-warning card-img-holder text-white">
                                    <div class="card-body">
                                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}"
                                            class="card-img-absolute" alt="circle" />
                                        <h4 class="font-weight-normal mb-3">
                                            Marker Aktif
                                            <i class="mdi mdi-map-marker mdi-24px float-end"></i>
                                        </h4>
                                        <h2 class="mb-5">{{ $categories->where('is_marker', true)->count() }}</h2>
                                        <h6 class="card-text">Kategori marker point</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Type</th>
                                    <th>Warna</th>
                                    <th>Jenis</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $no = 1;
                                    $parentKategoris = $categories->where('parent_id', null);
                                    $childKategoris = $categories->where('parent_id', '!=', null)->groupBy('parent_id');
                                @endphp
                                @forelse($parentKategoris as $kategori)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td><strong>{{ $kategori->nama }}</strong></td>
                                        <td>
                                            <span class="badge badge-secondary">{{ strtoupper($kategori->type) }}</span>
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
                                        <td>
                                            @if ($kategori->is_marker)
                                                <span class="badge badge-warning">
                                                    <i class="mdi mdi-map-marker"></i> Marker
                                                </span>
                                            @else
                                                <span class="badge badge-info">
                                                    <i class="mdi mdi-tematik"></i> Layer
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                    data-id="{{ $kategori->id }}" data-nama="{{ $kategori->nama }}"
                                                    data-type="{{ $kategori->type }}"
                                                    data-parent-id="{{ $kategori->parent_id }}"
                                                    data-warna="{{ $kategori->warna }}"
                                                    data-is-marker="{{ $kategori->is_marker }}"
                                                    data-icon="{{ $kategori->icon }}"
                                                    data-deskripsi="{{ $kategori->deskripsi }}" data-bs-toggle="modal"
                                                    data-bs-target="#editModal" title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <form action="{{ route('categories.destroy', $kategori->id) }}"
                                                    method="POST" style="display: inline-block;" data-confirm="delete"
                                                    data-name="{{ $kategori->nama }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Hapus kategori">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Sub Kategori --}}
                                    @if (isset($childKategoris[$kategori->id]) && $childKategoris[$kategori->id]->count() > 0)
                                        @foreach ($childKategoris[$kategori->id] as $child)
                                            <tr class="table-secondary">
                                                <td>{{ $no++ }}</td>
                                                <td>
                                                    <i class="mdi mdi-subdirectory-arrow-right text-muted me-1"></i>
                                                    <strong>{{ $child->nama }}</strong>
                                                    <br><small class="text-muted">Sub dari: {{ $kategori->nama }}</small>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge badge-secondary">{{ strtoupper($child->type) }}</span>
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
                                                <td>
                                                    @if ($child->is_marker)
                                                        <span class="badge badge-warning">
                                                            <i class="mdi mdi-map-marker"></i> Marker
                                                        </span>
                                                        @if ($child->icon)
                                                            <br><small class="text-muted">{{ $child->icon }}</small>
                                                        @endif
                                                    @else
                                                        <span class="badge badge-info">
                                                            <i class="mdi mdi-tematik"></i> Layer
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-success btn-edit"
                                                            data-id="{{ $child->id }}"
                                                            data-nama="{{ $child->nama }}"
                                                            data-type="{{ $child->type }}"
                                                            data-parent-id="{{ $child->parent_id }}"
                                                            data-warna="{{ $child->warna }}"
                                                            data-is-marker="{{ $child->is_marker }}"
                                                            data-icon="{{ $child->icon }}"
                                                            data-deskripsi="{{ $child->deskripsi }}"
                                                            data-bs-toggle="modal" data-bs-target="#editModal"
                                                            title="Edit">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </button>
                                                        <form action="{{ route('categories.destroy', $child->id) }}"
                                                            method="POST" style="display: inline-block;"
                                                            onsubmit="return confirm('Yakin ingin menghapus kategori {{ $child->nama }}?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
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
                                                <i class="mdi mdi-tematik mdi-48px text-muted"></i>
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
                        <i class="mdi mdi-plus"></i> Tambah Kategori
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="add_type" class="form-label">Tipe Kategori <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" id="add_type" name="type" required disabled>
                                        <option value="">-- Pilih Tipe --</option>
                                        <option value="tematik" {{ request('type') == 'tematik' ? 'selected' : '' }}>
                                            Peta Tematik
                                        </option>
                                        <option value="psd" {{ request('type') == 'psd' ? 'selected' : '' }}>PSD
                                            (Proyek Strategis Daerah)</option>
                                        <option value="psn" {{ request('type') == 'psn' ? 'selected' : '' }}>PSN
                                            (Proyek Strategis Nasional)</option>
                                        <option value="pokir_dprd"
                                            {{ request('type') == 'pokir_dprd' ? 'selected' : '' }}>Pokir DPRD</option>
                                        <option value="usulan_musrenbang"
                                            {{ request('type') == 'usulan_musrenbang' ? 'selected' : '' }}>Musenbang
                                            (Usulan
                                            Musrenbang)</option>
                                    </select>

                                    <!-- Hidden input untuk memastikan value tetap terkirim -->
                                    <input type="hidden" name="type" value="{{ request('type') }}">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="add_nama" class="form-label">Nama Kategori <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_nama" name="nama" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="add_parent_id" class="form-label">Parent Kategori</label>
                                    <select class="form-control" id="add_parent_id" name="parent_id">
                                        <option value="">-- Pilih Parent (Opsional) --</option>
                                    </select>
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

                        <div class="form-group mb-3 text-center">
                            <div class="form-check d-inline-block">
                                <input type="hidden" name="is_marker" value="0">
                                <input class="form-check-input" type="checkbox" value="1" id="add_is_marker"
                                    name="is_marker">
                                <label class="form-check-label" for="add_is_marker">
                                    Gunakan sebagai Marker (Point)
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-3" id="add_iconContainer" style="display: none;">
                            <label for="add_icon" class="form-label">
                                <i class="mdi mdi-map-marker me-1"></i> Ikon Marker
                            </label>
                            <select class="form-select" id="add_icon" name="icon">
                                <option value="">-- Pilih Ikon --</option>
                                @include('backend.partials.icon-options')
                            </select>
                            <div class="form-text">Ikon hanya berlaku untuk kategori marker (Point)</div>
                            <div id="add_iconPreview" class="mt-2 text-dark">
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
                        <i class="mdi mdi-pencil"></i> Edit Kategori
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="edit_type" class="form-label">Tipe Kategori <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" id="edit_type" name="type" required>
                                        <option value="">-- Pilih Tipe --</option>
                                        <option value="tematik">Peta Tematik</option>
                                        <option value="psd">PSD (Proyek Strategis Daerah)</option>
                                        <option value="psn">PSN (Proyek Strategis Nasional)</option>
                                        <option value="pokir_dprd">Pokir DPRD</option>
                                        <option value="usulan_musrenbang">Musenbang (Usulan Musrenbang)</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="edit_nama" class="form-label">Nama Kategori <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_nama" name="nama" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="edit_parent_id" class="form-label">Parent Kategori</label>
                                    <select class="form-control" id="edit_parent_id" name="parent_id">
                                        <option value="">-- Pilih Parent (Opsional) --</option>
                                    </select>
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

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        /* Icon Preview Styles */
        #add_iconPreview,
        #edit_iconPreview {
            min-height: 60px;
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }

        #add_iconPreview:has(i),
        #edit_iconPreview:has(i) {
            background-color: #fff;
            border-color: #007bff;
        }

        .icon-preview-container {
            width: 100%;
        }

        .fa-2x {
            font-size: 2em;
        }
    </style>
@endpush

@section('scripts')
    <script>
        $(document).ready(function() {
            // Show Alert Function
            function showAlert(message, type = 'success') {
                const icon = type === 'success' ? 'success' : 'error';
                const title = type === 'success' ? 'Berhasil!' : 'Error!';

                Swal.fire({
                    title: title,
                    text: message,
                    icon: icon,
                    timer: 4000,
                    showConfirmButton: false,
                    allowOutsideClick: true,
                    allowEscapeKey: true
                });
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
            function loadParentCategories(selectElement, type, excludeId = null) {
                if (!type) return;

                selectElement.empty();
                selectElement.append('<option value="">-- Pilih Parent (Opsional) --</option>');

                // Get categories by type via AJAX
                $.get(`{{ route('categories.api.options', '') }}/${type}`, function(response) {
                    if (response.success) {
                        $.each(response.data, function(index, kategori) {
                            if (excludeId && kategori.id == excludeId) return;
                            selectElement.append(
                                `<option value="${kategori.id}">${kategori.nama}</option>`);
                        });
                    }
                });
            }

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

            // Icon preview dengan konversi FA6 ke FA4
            $('#add_icon').on('change', function() {
                const fa6Icon = $(this).val();
                if (fa6Icon) {
                    const fa4Icon = convertFa6ToFa4(fa6Icon);
                    $('#add_iconPreview').html(`
                        <div class="d-flex align-items-center">
                            <i class="${fa4Icon} fa-2x me-3" style="color: #007bff;"></i>
                            <div>
                                <div><strong>Preview:</strong> <span class="text-muted">(FA4 untuk tampilan)</span></div>
                                <div><small><strong>Disimpan:</strong> <code>${fa6Icon}</code></small></div>
                            </div>
                        </div>
                    `);
                } else {
                    $('#add_iconPreview').html(
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
            $('#add_warna').on('change input', function() {
                $('#add_colorPreview').css('background-color', $(this).val());
            });

            $('#edit_warna').on('change input', function() {
                $('#edit_colorPreview').css('background-color', $(this).val());
            });

            // Marker checkbox functionality
            $('#add_is_marker').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#add_iconContainer').show();
                } else {
                    $('#add_iconContainer').hide();
                    $('#add_icon').val('');
                    $('#add_iconPreview').html(
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

            // Type change handler for add form
            $('#add_type').on('change', function() {
                const selectedType = $(this).val();
                console.log('Add form type changed to:', selectedType);
                loadParentCategories($('#add_parent_id'), selectedType);
            });

            // Type change handler for edit form
            $('#edit_type').on('change', function() {
                const selectedType = $(this).val();
                const excludeId = $('#edit_id').val();
                console.log('Edit form type changed to:', selectedType, 'excluding ID:', excludeId);
                loadParentCategories($('#edit_parent_id'), selectedType, excludeId);
            });

            // Add Modal
            $('#addModal').on('show.bs.modal', function() {
                const form = $('#addForm');
                form[0].reset();
                clearFormErrors(form);
                $('#add_colorPreview').css('background-color', '#007bff');
                $('#add_iconContainer').hide();
                $('#add_iconPreview').html(
                    '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>');

                // Set default type if coming from filtered page
                const currentType = '{{ request('type') }}';
                if (currentType) {
                    $('#add_type').val(currentType).trigger('change');
                }
            });

            // Add Form Submit
            $('#addForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route('categories.store') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#addModal').modal('hide');
                            showAlert(response.message);
                            // Tunggu 2 detik sebelum reload
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
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
                const form = $('#editForm');
                const id = $(this).data('id');

                $('#edit_id').val(id);
                $('#edit_nama').val($(this).data('nama'));
                $('#edit_type').val($(this).data('type'));
                $('#edit_warna').val($(this).data('warna') || '#007bff');
                $('#edit_deskripsi').val($(this).data('deskripsi'));
                $('#edit_colorPreview').css('background-color', $(this).data('warna') || '#007bff');

                // Marker checkbox
                if ($(this).data('is-marker')) {
                    $('#edit_is_marker').prop('checked', true);
                    $('#edit_iconContainer').show();
                    $('#edit_icon').val($(this).data('icon'));
                } else {
                    $('#edit_is_marker').prop('checked', false);
                    $('#edit_iconContainer').hide();
                }

                // Load parent categories
                const type = $(this).data('type');
                loadParentCategories($('#edit_parent_id'), type, id);

                // Set selected parent after loading options
                setTimeout(function() {
                    const parentId = $(this).data('parent-id');
                    if (parentId) {
                        $('#edit_parent_id').val(parentId);
                    }
                }.bind(this), 500);

                clearFormErrors(form);
            });

            // Edit Form Submit
            $('#editForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const id = $('#edit_id').val();
                const formData = new FormData(this);

                $.ajax({
                    url: `{{ route('categories.index') }}/${id}`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#editModal').modal('hide');
                            showAlert(response.message);
                            // Tunggu 2 detik sebelum reload
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
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

            // Type filter change handler
            $('#typeFilter').on('change', function() {
                const selectedType = $(this).val();
                if (selectedType) {
                    window.location.href = `{{ route('categories.index') }}?type=${selectedType}`;
                } else {
                    window.location.href = `{{ route('categories.index') }}`;
                }
            });

            // Auto hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        });
    </script>
@endsection
