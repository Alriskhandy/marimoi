@extends('backend.partials.main', ['title' => $typeLabel ?? 'Kategori'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-tag-multiple"></i>
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
                                    <option value="tematik" {{ request('type') == 'tematik' ? 'selected' : '' }}>
                                        Tematik (Lokasi)
                                    </option>
                                    <option value="psd" {{ request('type') == 'psd' ? 'selected' : '' }}>
                                        PSD (Proyek Strategis Daerah)
                                    </option>
                                    <option value="psn" {{ request('type') == 'psn' ? 'selected' : '' }}>
                                        PSN (Proyek Strategis Nasional)
                                    </option>
                                    <option value="pokir_dprd" {{ request('type') == 'pokir_dprd' ? 'selected' : '' }}>
                                        Pokir DPRD
                                    </option>
                                    <option value="usulan_musrenbang"
                                        {{ request('type') == 'usulan_musrenbang' ? 'selected' : '' }}>
                                        Musrenbang (Usulan Musrenbang)
                                    </option>
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

    <!-- Statistics Cards -->
    @if ($categories->count() > 0)
        <div class="row mb-4">
            <div class="col-md-4 stretch-card grid-margin">
                <div class="card bg-gradient-primary card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
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
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Sub Kategori
                            <i class="mdi mdi-subdirectory-arrow-right mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-5">
                            {{ $categories->where('parent_id', '!=', null)->count() }}</h2>
                        <h6 class="card-text">Kategori turunan</h6>
                    </div>
                </div>
            </div>

            <div class="col-md-4 stretch-card grid-margin">
                <div class="card bg-gradient-warning card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Marker Aktif
                            <i class="mdi mdi-map-marker mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-5">{{ $categories->where('is_marker', true)->count() }}
                        </h2>
                        <h6 class="card-text">Kategori marker point</h6>
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

                    <div class="table-responsive">
                        <table id="categoriesTable" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Type</th>
                                    <th>Parent</th>
                                    <th>Warna</th>
                                    <th>Jenis</th>
                                    <th>Icon</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $no = 1;
                                    // Separate parent and child categories for proper hierarchy
                                    $parentKategoris = $categories->where('parent_id', null)->sortBy('nama');
                                    $childKategoris = $categories->where('parent_id', '!=', null)->groupBy('parent_id');
                                @endphp
                                @forelse($parentKategoris as $kategori)
                                    {{-- Parent Category Row --}}
                                    <tr data-category-id="{{ $kategori->id }}" data-parent-id="{{ $kategori->parent_id }}"
                                        class="parent-category">
                                        <td>{{ $no++ }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if (isset($childKategoris[$kategori->id]) && $childKategoris[$kategori->id]->count() > 0)
                                                    <button
                                                        class="btn btn-link btn-sm p-0 me-2 hierarchy-toggle text-secondary"
                                                        data-target="children-{{ $kategori->id }}" title="Expand">
                                                        <i class="mdi mdi-chevron-right"></i>
                                                    </button>
                                                @else
                                                    <span class="me-4"></span>
                                                @endif
                                                <div>
                                                    <strong class="text-dark">{{ $kategori->nama }}</strong>
                                                    @if ($kategori->deskripsi)
                                                        <br><small
                                                            class="text-muted">{{ Str::limit($kategori->deskripsi, 50) }}</small>
                                                    @endif
                                                    @if (isset($childKategoris[$kategori->id]) && $childKategoris[$kategori->id]->count() > 0)
                                                        <span class="badge bg-info text-white ms-2"
                                                            style="font-size: 0.65em;">
                                                            {{ $childKategoris[$kategori->id]->count() }} sub
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-primary text-white">{{ strtoupper($kategori->type) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">-</span>
                                        </td>
                                        <td>
                                            @if ($kategori->warna)
                                                <div class="color-preview d-flex align-items-center">
                                                    <span class="color-box me-2"
                                                        style="background-color: {{ $kategori->warna }}; width: 24px; height: 24px; border-radius: 4px; border: 1px solid #dee2e6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></span>
                                                    <small class="text-muted">{{ $kategori->warna }}</small>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($kategori->is_marker)
                                                <span class="badge bg-warning text-dark">
                                                    <i class="mdi mdi-map-marker"></i> Marker
                                                </span>
                                            @else
                                                <span class="badge bg-info text-white">
                                                    <i class="mdi mdi-layers"></i> Layer
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($kategori->is_marker && $kategori->icon)
                                                <i class="{{ $kategori->icon }}"
                                                    style="color: {{ $kategori->warna ?? '#007bff' }}; font-size: 1.4em;"
                                                    title="{{ $kategori->icon }}"></i>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $user = Auth::user();
                                                $role = $user->role->slug ?? null;
                                            @endphp

                                            @if ($role === 'super-admin' || $role === 'admin-bappeda' || $kategori->user_id === $user->id)
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                        data-id="{{ $kategori->id }}" data-nama="{{ $kategori->nama }}"
                                                        data-type="{{ $kategori->type }}"
                                                        data-parent-id="{{ $kategori->parent_id }}"
                                                        data-warna="{{ $kategori->warna }}"
                                                        data-is-marker="{{ $kategori->is_marker }}"
                                                        data-icon="{{ $kategori->icon }}"
                                                        data-deskripsi="{{ $kategori->deskripsi }}"
                                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                                        title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                    <form action="{{ route('categories.destroy', $kategori->id) }}"
                                                        method="POST" style="display: inline-block;"
                                                        data-confirm="delete" data-name="{{ $kategori->nama }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            title="Hapus kategori">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>

                                    {{-- Child Categories Rows (directly after parent) --}}
                                    @if (isset($childKategoris[$kategori->id]) && $childKategoris[$kategori->id]->count() > 0)
                                        @foreach ($childKategoris[$kategori->id]->sortBy('nama') as $child)
                                            <tr data-category-id="{{ $child->id }}"
                                                data-parent-id="{{ $child->parent_id }}"
                                                class="child-category children-{{ $kategori->id }}"
                                                style="display: none;">
                                                <td>{{ $no++ }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="hierarchy-line me-2">
                                                            <span class="hierarchy-connector"></span>
                                                        </div>
                                                        <i class="mdi mdi-subdirectory-arrow-right text-secondary me-2"
                                                            style="font-size: 1.1em;"></i>
                                                        <div>
                                                            <span class="text-dark fw-medium">{{ $child->nama }}</span>
                                                            <br><small class="text-muted">
                                                                <i class="mdi mdi-link me-1"></i>Sub dari:
                                                                <span class="text-primary">{{ $kategori->nama }}</span>
                                                            </small>
                                                            @if ($child->deskripsi)
                                                                <br><small
                                                                    class="text-muted">{{ Str::limit($child->deskripsi, 40) }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-secondary text-white">{{ strtoupper($child->type) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-primary">{{ $kategori->nama }}</span>
                                                </td>
                                                <td>
                                                    @if ($child->warna)
                                                        <div class="color-preview d-flex align-items-center">
                                                            <span class="color-box me-2"
                                                                style="background-color: {{ $child->warna }}; width: 20px; height: 20px; border-radius: 3px; border: 1px solid #dee2e6;"></span>
                                                            <small class="text-muted">{{ $child->warna }}</small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($child->is_marker)
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="mdi mdi-map-marker"></i> Marker
                                                        </span>
                                                    @else
                                                        <span class="badge bg-info text-white">
                                                            <i class="mdi mdi-layers"></i> Layer
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($child->is_marker && $child->icon)
                                                        <i class="{{ $child->icon }}"
                                                            style="color: {{ $child->warna ?? '#007bff' }}; font-size: 1.2em;"
                                                            title="{{ $child->icon }}"></i>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($role === 'super-admin' || $role === 'admin-bappeda' || $child->user_id === $user->id)
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
                                                                data-confirm="delete" data-name="{{ $child->nama }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    title="Hapus kategori">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="mdi mdi-tag-multiple mdi-48px text-muted"></i>
                                            <h5 class="text-muted mt-2">Belum ada kategori yang dibuat</h5>
                                            <p class="text-muted">Klik tombol "Tambah Kategori" untuk memulai</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#addModal">
                                                <i class="mdi mdi-plus"></i> Tambah Kategori Pertama
                                            </button>
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
                                    <select class="form-control" id="add_type" name="type" required
                                        {{ request('type') ? 'disabled' : '' }}>
                                        <option value="">-- Pilih Tipe --</option>
                                        <option value="tematik" {{ request('type') == 'tematik' ? 'selected' : '' }}>
                                            Peta Tematik
                                        </option>
                                        <option value="psd" {{ request('type') == 'psd' ? 'selected' : '' }}>
                                            PSD (Proyek Strategis Daerah)
                                        </option>
                                        <option value="psn" {{ request('type') == 'psn' ? 'selected' : '' }}>
                                            PSN (Proyek Strategis Nasional)
                                        </option>
                                        <option value="pokir_dprd"
                                            {{ request('type') == 'pokir_dprd' ? 'selected' : '' }}>
                                            Pokir DPRD
                                        </option>
                                        <option value="usulan_musrenbang"
                                            {{ request('type') == 'usulan_musrenbang' ? 'selected' : '' }}>
                                            Musrenbang (Usulan Musrenbang)
                                        </option>
                                    </select>

                                    @if (request('type'))
                                        <!-- Hidden input untuk memastikan value tetap terkirim -->
                                        <input type="hidden" name="type" value="{{ request('type') }}">
                                    @endif
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
                            <div id="add_iconPreview" class="mt-2 icon-preview-container">
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
                                        <option value="usulan_musrenbang">Musrenbang (Usulan Musrenbang)</option>
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
                            <div id="edit_iconPreview" class="mt-2 icon-preview-container">
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
        /* ===========================================
                                                                                                                                       FORM STYLING
                                                                                                                                    =========================================== */
        .form-control-color {
            max-width: 50px;
            height: 38px;
            padding: 0.2rem;
            border-radius: 0.375rem 0 0 0.375rem;
        }

        .input-group-text {
            min-width: 60px;
            text-align: center;
        }

        /* ===========================================
                                                                                                                                       TABLE STYLING
                                                                                                                                    =========================================== */
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            color: #495057;
            padding: 12px 8px;
            position: sticky;
            top: 0;
            z-index: 10;
            border-top: none;
        }

        .table td {
            vertical-align: middle;
            padding: 12px 8px;
            border-bottom: 1px solid #eef2f7;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Parent-child category styling */
        .parent-category {
            background-color: #fff;
        }

        .child-category {
            background-color: rgba(108, 117, 125, 0.08);
        }

        .child-category:hover {
            background-color: rgba(0, 123, 255, 0.08) !important;
        }

        /* ===========================================
                                                                                                                                       BADGE STYLING
                                                                                                                                    =========================================== */
        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .bg-secondary {
            background-color: #6c757d !important;
        }

        .bg-info {
            background-color: #17a2b8 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
        }

        /* ===========================================
                                                                                                                                       COLOR PREVIEW STYLING
                                                                                                                                    =========================================== */
        .color-preview {
            display: flex;
            align-items: center;
        }

        .color-box {
            display: inline-block;
            border: 1px solid #dee2e6;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* ===========================================
                                                                                                                                       BUTTON GROUP STYLING
                                                                                                                                    =========================================== */
        .btn-group .btn {
            border-radius: 6px !important;
            margin: 0 2px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        /* ===========================================
                                                                                                                                       DATATABLE STYLING
                                                                                                                                    =========================================== */
        .dataTables_wrapper {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #495057;
            font-size: 0.875rem;
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            background-color: #fff;
            font-size: 0.875rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            background-color: #fff;
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 8px 12px !important;
            margin: 0 2px !important;
            border-radius: 6px !important;
            border: 1px solid #dee2e6 !important;
            background: #fff !important;
            color: #495057 !important;
            transition: all 0.2s ease !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e9ecef !important;
            border-color: #adb5bd !important;
            color: #495057 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #007bff, #0056b3) !important;
            border-color: #007bff !important;
            color: white !important;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: #6c757d !important;
            background: #f8f9fa !important;
            border-color: #dee2e6 !important;
        }

        /* ===========================================
                                                                                                                                       MODAL STYLING
                                                                                                                                    =========================================== */
        .modal-lg {
            max-width: 800px;
        }

        .modal-content {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid #dee2e6;
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
        }

        /* ===========================================
                                                                                                                                       ICON PREVIEW STYLING
                                                                                                                                    =========================================== */
        .icon-preview-container {
            min-height: 60px;
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
            width: 100%;
        }

        .icon-preview-container.has-icon {
            background-color: #fff;
            border-color: #007bff;
            border-style: solid;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15);
        }

        .icon-preview-content {
            display: flex;
            align-items: center;
            width: 100%;
        }

        .icon-preview-icon {
            font-size: 2.5em;
            margin-right: 15px;
            color: #007bff;
        }

        .icon-preview-details h6 {
            margin: 0 0 5px 0;
            font-weight: 600;
            color: #495057;
        }

        .icon-preview-details small {
            color: #6c757d;
            font-size: 0.85em;
        }

        .icon-preview-code {
            background-color: #f1f3f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.8em;
            color: #d63384;
        }

        /* ===========================================
                                                                                                                                       STATISTICS CARDS
                                                                                                                                    =========================================== */
        .card.bg-gradient-primary,
        .card.bg-gradient-success,
        .card.bg-gradient-warning {
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }

        .card-img-absolute {
            position: absolute;
            top: 0;
            right: 0;
            opacity: 0.2;
        }

        /* ===========================================
                                                                                                                                       HIERARCHY CONTROLS STYLING
                                                                                                                                    =========================================== */
        .hierarchy-controls {
            margin-bottom: 10px;
        }

        .hierarchy-toggle-all {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.25);
        }

        .hierarchy-toggle-all:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.35);
            color: white;
        }

        .hierarchy-toggle-all:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.3);
        }

        .hierarchy-toggle-all.collapsed {
            background: linear-gradient(135deg, #6c757d, #545b62);
        }

        .hierarchy-toggle-all.collapsed:hover {
            background: linear-gradient(135deg, #545b62, #383d41);
        }

        /* ===========================================
                                                                                                                                       UTILITY CLASSES
                                                                                                                                    =========================================== */
        .text-center i.mdi-48px {
            font-size: 3rem;
        }

        .mdi-subdirectory-arrow-right {
            font-size: 1.2em;
        }

        /* ===========================================
                                                                                                                                       RESPONSIVE IMPROVEMENTS
                                                                                                                                    =========================================== */
        @media (max-width: 768px) {
            .btn-sm {
                padding: 4px 8px;
                font-size: 0.8rem;
            }

            .table th,
            .table td {
                padding: 8px 4px;
                font-size: 0.85rem;
            }

            .badge {
                font-size: 0.7rem;
                padding: 4px 8px;
            }

            .icon-preview-container {
                min-height: 50px;
                padding: 10px;
            }

            .icon-preview-icon {
                font-size: 2em;
                margin-right: 10px;
            }
        }

        /* ===========================================
                                                                                                                                       FOCUS AND ACCESSIBILITY
                                                                                                                                    =========================================== */
        .btn:focus,
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .visually-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Global state tracking - declare at the top
            let isAllExpanded = false;

            // Initialize DataTable with hierarchy support
            const table = $('#categoriesTable').DataTable({
                "processing": true,
                "pageLength": 25,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Semua"]
                ],
                "ordering": false, // Disable all sorting to maintain hierarchy
                "columnDefs": [{
                        "searchable": false,
                        "targets": [-1] // Disable search on action column
                    },
                    {
                        "className": "text-center",
                        "targets": [0, 5, -1] // Center align for no, icon, action columns
                    }
                ],
                "language": {
                    "processing": "<div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div>",
                    "lengthMenu": "Tampilkan _MENU_ kategori per halaman",
                    "zeroRecords": "Kategori tidak ditemukan",
                    "emptyTable": "Tidak ada kategori tersedia",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ kategori",
                    "infoEmpty": "Menampilkan 0 sampai 0 dari 0 kategori",
                    "infoFiltered": "(difilter dari _MAX_ total kategori)",
                    "search": "Cari kategori:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                },
                "dom": '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row mb-2"<"col-sm-12"<"hierarchy-controls text-end">>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "drawCallback": function(settings) {
                    // Reinitialize hierarchy functionality after table redraw
                    initializeHierarchyControls();

                    // Add smooth animation to new rows
                    $(this).find('tbody tr').css('opacity', '0').animate({
                        'opacity': '1'
                    }, 300);
                },
                "initComplete": function() {
                    // Style the search input
                    $('.dataTables_filter input').attr('placeholder', 'Ketik nama kategori...');

                    // Add hierarchy control button only if there are parent categories with children
                    if ($('.hierarchy-toggle').length > 0) {
                        $('.hierarchy-controls').html(`
            <button type="button" class="btn hierarchy-toggle-all" id="hierarchyToggleAll" data-state="collapsed">
                <i class="mdi mdi-chevron-down me-1"></i>
                <span class="toggle-text">Expand All</span>
            </button>
        `);
                    }

                    // Initialize hierarchy controls
                    initializeHierarchyControls();

                    // Initially collapse all children for better UX
                    $('.child-category').hide();
                    $('.hierarchy-toggle i').addClass('collapsed');
                    updateToggleAllButton();
                }
            });

            // Function to update toggle all button appearance
            function updateToggleAllButton() {
                const $toggleAllBtn = $('#hierarchyToggleAll');
                if ($toggleAllBtn.length === 0) return;

                const $toggleIcon = $toggleAllBtn.find('i');
                const $toggleText = $toggleAllBtn.find('.toggle-text');

                const visibleChildren = $('.child-category:visible').length;
                const totalChildren = $('.child-category').length;

                if (visibleChildren === 0) {
                    // All collapsed
                    isAllExpanded = false;
                    $toggleAllBtn.removeClass('collapsed').attr('data-state', 'collapsed');
                    $toggleIcon.removeClass('mdi-chevron-up').addClass('mdi-chevron-down');
                    $toggleText.text('Expand All');
                } else if (visibleChildren === totalChildren) {
                    // All expanded
                    isAllExpanded = true;
                    $toggleAllBtn.addClass('collapsed').attr('data-state', 'expanded');
                    $toggleIcon.removeClass('mdi-chevron-down').addClass('mdi-chevron-up');
                    $toggleText.text('Collapse All');
                } else {
                    // Mixed state - show expand option
                    isAllExpanded = false;
                    $toggleAllBtn.removeClass('collapsed').attr('data-state', 'mixed');
                    $toggleIcon.removeClass('mdi-chevron-up').addClass('mdi-chevron-down');
                    $toggleText.text('Expand All');
                }
            }

            // Hierarchy control functions
            function initializeHierarchyControls() {
                // Only bind events if there are toggle buttons
                if ($('.hierarchy-toggle').length === 0) return;

                // Toggle individual parent categories
                $('.hierarchy-toggle').off('click').on('click', function(e) {
                    e.preventDefault();
                    const target = $(this).data('target');
                    const childRows = $(`.${target}`);
                    const icon = $(this).find('i');

                    if (childRows.is(':visible')) {
                        // Collapse children
                        childRows.slideUp(300, updateToggleAllButton);
                        icon.addClass('collapsed');
                        $(this).attr('title', 'Expand').removeClass('text-primary').addClass(
                            'text-secondary');
                    } else {
                        // Expand children
                        childRows.slideDown(300, updateToggleAllButton);
                        icon.removeClass('collapsed');
                        $(this).attr('title', 'Collapse').removeClass('text-secondary').addClass(
                            'text-primary');
                    }
                });

                // Single toggle all button
                $('#hierarchyToggleAll').off('click').on('click', function() {
                    const $btn = $(this);
                    const currentState = $btn.attr('data-state');

                    // Add loading state
                    $btn.prop('disabled', true);

                    if (currentState === 'collapsed' || currentState === 'mixed') {
                        // Expand all
                        $('.child-category').slideDown(300);
                        $('.hierarchy-toggle i').removeClass('collapsed');
                        $('.hierarchy-toggle').attr('title', 'Collapse').removeClass('text-secondary')
                            .addClass('text-primary');

                        setTimeout(() => {
                            updateToggleAllButton();
                            $btn.prop('disabled', false);
                        }, 350);
                    } else {
                        // Collapse all
                        $('.child-category').slideUp(300);
                        $('.hierarchy-toggle i').addClass('collapsed');
                        $('.hierarchy-toggle').attr('title', 'Expand').removeClass('text-primary').addClass(
                            'text-secondary');

                        setTimeout(() => {
                            updateToggleAllButton();
                            $btn.prop('disabled', false);
                        }, 350);
                    }

                    // Visual feedback
                    $btn.addClass('active');
                    setTimeout(() => $btn.removeClass('active'), 200);
                });
            }

            // Enhanced search that maintains hierarchy structure
            $('.dataTables_filter input').on('input', function() {
                const searchTerm = $(this).val().toLowerCase().trim();

                if (searchTerm === '') {
                    // Reset to collapsed state when search is cleared
                    $('.child-category').hide();
                    $('.hierarchy-toggle i').addClass('collapsed');
                    $('.hierarchy-toggle').attr('title', 'Expand').removeClass('text-primary').addClass(
                        'text-secondary');
                    updateToggleAllButton();
                } else {
                    // Show matching rows and their relationships
                    $('.parent-category, .child-category').each(function() {
                        const rowText = $(this).text().toLowerCase();
                        const categoryId = $(this).data('category-id');
                        const parentId = $(this).data('parent-id');

                        if (rowText.includes(searchTerm)) {
                            $(this).show();

                            // If this is a parent that matches, show its children
                            if ($(this).hasClass('parent-category')) {
                                $(`.children-${categoryId}`).show();
                                const toggle = $(this).find('.hierarchy-toggle');
                                if (toggle.length) {
                                    toggle.find('i').removeClass('collapsed');
                                    toggle.attr('title', 'Collapse').removeClass('text-secondary')
                                        .addClass('text-primary');
                                }
                            }

                            // If this is a child that matches, ensure its parent is visible and expanded
                            if ($(this).hasClass('child-category') && parentId) {
                                const parentRow = $(
                                    `.parent-category[data-category-id="${parentId}"]`);
                                parentRow.show();
                                const parentToggle = parentRow.find('.hierarchy-toggle');
                                if (parentToggle.length) {
                                    parentToggle.find('i').removeClass('collapsed');
                                    parentToggle.attr('title', 'Collapse').removeClass(
                                        'text-secondary').addClass('text-primary');
                                }
                            }
                        }
                    });
                    updateToggleAllButton();
                }
            });

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
                            if (kategori.parent_id == null) { // Only show parent categories
                                selectElement.append(
                                    `<option value="${kategori.id}">${kategori.nama}</option>`);
                            }
                        });
                    }
                }).fail(function() {
                    console.warn('Failed to load parent categories');
                });
            }

            // Simplified icon preview function for FontAwesome 4
            function updateIconPreview(iconClass, previewElementId, colorValue = '#007bff') {
                const $previewElement = $(previewElementId);

                if (iconClass && iconClass.trim()) {
                    const iconHtml = `
                        <div class="icon-preview-content">
                            <i class="${iconClass} icon-preview-icon" style="color: ${colorValue}; font-size: 2.5em;"></i>
                            <div class="icon-preview-details">
                                <h6>Icon Preview</h6>
                                <small>Class: <span class="icon-preview-code">${iconClass}</span></small>
                            </div>
                        </div>
                    `;
                    $previewElement.html(iconHtml).addClass('has-icon');
                } else {
                    $previewElement.html('<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>')
                        .removeClass('has-icon');
                }
            }

            // Icon preview dengan styling yang lebih baik
            $('#add_icon, #edit_icon').on('change', function() {
                const iconClass = $(this).val();
                const isAdd = $(this).attr('id') === 'add_icon';
                const previewElementId = isAdd ? '#add_iconPreview' : '#edit_iconPreview';
                const colorInputId = isAdd ? '#add_warna' : '#edit_warna';
                const colorValue = $(colorInputId).val() || '#007bff';

                updateIconPreview(iconClass, previewElementId, colorValue);
            });

            // Color preview updates with icon color sync
            $('#add_warna').on('change input', function() {
                const newColor = $(this).val();
                $('#add_colorPreview').css('background-color', newColor);

                // Update icon color if preview exists
                const iconElement = $('#add_iconPreview .icon-preview-icon');
                if (iconElement.length) {
                    iconElement.css('color', newColor);
                }
            });

            $('#edit_warna').on('change input', function() {
                const newColor = $(this).val();
                $('#edit_colorPreview').css('background-color', newColor);

                // Update icon color if preview exists
                const iconElement = $('#edit_iconPreview .icon-preview-icon');
                if (iconElement.length) {
                    iconElement.css('color', newColor);
                }
            });

            // Marker checkbox functionality
            $('#add_is_marker, #edit_is_marker').on('change', function() {
                const isAdd = $(this).attr('id') === 'add_is_marker';
                const iconContainer = isAdd ? '#add_iconContainer' : '#edit_iconContainer';
                const iconSelect = isAdd ? '#add_icon' : '#edit_icon';
                const iconPreview = isAdd ? '#add_iconPreview' : '#edit_iconPreview';

                if ($(this).is(':checked')) {
                    $(iconContainer).slideDown(300);
                } else {
                    $(iconContainer).slideUp(300);
                    $(iconSelect).val('');
                    $(iconPreview).html(
                            '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>')
                        .removeClass('has-icon');
                }
            });

            // Type change handlers
            $('#add_type').on('change', function() {
                const selectedType = $(this).val();
                loadParentCategories($('#add_parent_id'), selectedType);
            });

            $('#edit_type').on('change', function() {
                const selectedType = $(this).val();
                const excludeId = $('#edit_id').val();
                loadParentCategories($('#edit_parent_id'), selectedType, excludeId);
            });

            // Add Modal setup
            $('#addModal').on('show.bs.modal', function() {
                const form = $('#addForm');
                form[0].reset();
                clearFormErrors(form);
                $('#add_colorPreview').css('background-color', '#007bff');
                $('#add_iconContainer').hide();
                $('#add_iconPreview').html(
                    '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>').removeClass(
                    'has-icon');

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
                const submitBtn = form.find('button[type="submit"]');

                // Show loading state
                submitBtn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin me-1"></i>Menyimpan...');

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
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.prop('disabled', false).html(
                            '<i class="mdi mdi-content-save"></i> Simpan');
                    }
                });
            });

            // Edit Modal setup
            $(document).on('click', '.btn-edit', function() {
                const form = $('#editForm');
                const id = $(this).data('id');

                $('#edit_id').val(id);
                $('#edit_nama').val($(this).data('nama'));
                $('#edit_type').val($(this).data('type'));
                $('#edit_warna').val($(this).data('warna') || '#007bff');
                $('#edit_deskripsi').val($(this).data('deskripsi'));
                $('#edit_colorPreview').css('background-color', $(this).data('warna') || '#007bff');

                // Handle marker checkbox and icon
                const isMarker = $(this).data('is-marker');
                $('#edit_is_marker').prop('checked', isMarker);

                if (isMarker) {
                    $('#edit_iconContainer').show();
                    const iconClass = $(this).data('icon');
                    $('#edit_icon').val(iconClass);
                    const colorValue = $(this).data('warna') || '#007bff';
                    updateIconPreview(iconClass, '#edit_iconPreview', colorValue);
                } else {
                    $('#edit_iconContainer').hide();
                    $('#edit_icon').val('');
                    $('#edit_iconPreview').html(
                            '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>')
                        .removeClass('has-icon');
                }

                // Load parent categories
                const type = $(this).data('type');
                loadParentCategories($('#edit_parent_id'), type, id);

                // Set selected parent after loading options
                const parentId = $(this).data('parent-id');
                setTimeout(function() {
                    if (parentId) {
                        $('#edit_parent_id').val(parentId);
                    }
                }, 500);

                clearFormErrors(form);
            });

            // Edit Form Submit
            $('#editForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const id = $('#edit_id').val();
                const formData = new FormData(this);
                const submitBtn = form.find('button[type="submit"]');

                // Show loading state
                submitBtn.prop('disabled', true).html(
                    '<i class="mdi mdi-loading mdi-spin me-1"></i>Memperbarui...');

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
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.prop('disabled', false).html(
                            '<i class="mdi mdi-content-save"></i> Update');
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

            // Enhanced delete confirmation
            $('form[data-confirm="delete"]').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const categoryName = $(form).data('name');

                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    html: `Apakah Anda yakin ingin menghapus kategori <strong>"${categoryName}"</strong>?<br><small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
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
                        form.submit();
                    }
                });
            });

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + N to add new category
                if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                    e.preventDefault();
                    $('#addModal').modal('show');
                }

                // Ctrl/Cmd + T to toggle all hierarchy
                if ((e.ctrlKey || e.metaKey) && e.key === 't') {
                    e.preventDefault();
                    $('#hierarchyToggleAll').click();
                }

                // Escape to close modals
                if (e.key === 'Escape') {
                    $('.modal').modal('hide');
                }
            });

            // Add tooltips
            $('[title]').tooltip({
                placement: 'top',
                trigger: 'hover'
            });

            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);

            // Initialize tooltips for keyboard shortcuts
            $('#hierarchyToggleAll').attr('title', 'Keyboard shortcut: Ctrl+T');
            $('.btn[data-bs-target="#addModal"]').attr('title', 'Keyboard shortcut: Ctrl+N');
        });
    </script>
@endpush
