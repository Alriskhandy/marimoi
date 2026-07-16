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
        <div class="row g-3 stats-row-compact">
            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Kategori Utama</p>
                                <h3 class="stat-value">{{ $categories->where('parent_id', null)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-format-list-bulleted-type stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Sub Kategori</p>
                                <h3 class="stat-value">{{ $categories->where('parent_id', '!=', null)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-subdirectory-arrow-right stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Marker Aktif</p>
                                <h3 class="stat-value">{{ $categories->where('is_marker', true)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-map-marker stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card stat-card-compact bg-gradient-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="stat-label">Status Aktif</p>
                                <h3 class="stat-value">{{ $categories->where('is_active', true)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-check-circle stat-icon"></i>
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

                    @php
                        $resetQuery = request()->except(['search', 'page']);
                        $resetQuery = array_filter($resetQuery, fn($value) => $value !== null && $value !== '');
                        $resetUrl = request()->url() . (count($resetQuery) ? '?' . http_build_query($resetQuery) : '');
                    @endphp

                    <div class="row mb-4 g-3 align-items-end">
                        <div class="col-12">
                            <div class="row g-3 align-items-end filter-toolbar">
                                <div class="col-lg-9 col-md-8">
                                    <label for="tableSearch" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-magnify me-1"></i>Cari Kategori
                                    </label>
                                    <div class="input-group filter-input-group">
                                        <input type="text" class="form-control filter-control" id="tableSearch"
                                            placeholder="Ketik nama kategori..." value="{{ request('search') }}">
                                        <button class="btn btn-md btn-primary filter-btn" type="button"
                                            id="searchTableBtn">
                                            <i class="mdi mdi-magnify"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-4">
                                    <label for="per_page" class="form-label fw-semibold mb-1">
                                        <i class="mdi mdi-table-row me-1"></i>Tampilkan per halaman
                                    </label>
                                    <select class="form-select filter-control" id="per_page">
                                        <option value="25" {{ request('per_page', 200) == 25 ? 'selected' : '' }}>25
                                            data</option>
                                        <option value="50" {{ request('per_page', 200) == 50 ? 'selected' : '' }}>50
                                            data</option>
                                        <option value="100" {{ request('per_page', 200) == 100 ? 'selected' : '' }}>100
                                            data</option>
                                        <option value="200" {{ request('per_page', 200) == 200 ? 'selected' : '' }}>200
                                            data</option>
                                        <option value="500" {{ request('per_page', 200) == 500 ? 'selected' : '' }}>500
                                            data</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="categoriesTable" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Gambar</th>
                                    <th>Nama & Hirarki</th>
                                    <th>Type</th>
                                    <th>Level</th>
                                    <th>Parent</th>
                                    <th>Warna</th>
                                    <th>Jenis</th>
                                    <th>Icon</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $no = 1;
                                    $user = Auth::user();
                                    $role = $user->role->slug ?? null;

                                    // Function to recursively render hierarchy with 3 levels
                                    function renderCategoryHierarchy(
                                        $categories,
                                        $parentId = null,
                                        $level = 0,
                                        &$no,
                                        $user,
                                        $role,
                                    ) {
                                        $filteredCategories = $categories
                                            ->where('parent_id', $parentId)
                                            ->sortBy('nama');
                                        $output = '';

                                        foreach ($filteredCategories as $kategori) {
                                            // Determine styling based on level
                                            $rowClass = '';
                                            $indentClass = '';
                                            $levelBadge = '';
                                            $hierarchyIcon = '';

                                            switch ($level) {
                                                case 0: // Parent (Level 1)
                                                    $rowClass = 'parent-category level-0';
                                                    $levelBadge = '<span class="badge bg-primary">Level 1</span>';
                                                    $imageSize = '50px';
                                                    $colorSize = '24px';
                                                    $iconSize = '1.4em';
                                                    break;
                                                case 1: // Child (Level 2)
                                                    $rowClass = 'child-category level-1 children-' . $parentId;
                                                    $indentClass = 'hierarchy-level-1';
                                                    $levelBadge = '<span class="badge bg-success">Level 2</span>';
                                                    $hierarchyIcon =
                                                        '<i class="mdi mdi-subdirectory-arrow-right text-success me-2"></i>';
                                                    $imageSize = '40px';
                                                    $colorSize = '20px';
                                                    $iconSize = '1.2em';
                                                    break;
                                                case 2: // Grandchild (Level 3)
                                                    $rowClass = 'grandchild-category level-2 children-' . $parentId;
                                                    $indentClass = 'hierarchy-level-2';
                                                    $levelBadge =
                                                        '<span class="badge bg-warning text-dark">Level 3</span>';
                                                    $hierarchyIcon =
                                                        '<i class="mdi mdi-call-split text-warning me-2"></i>';
                                                    $imageSize = '35px';
                                                    $colorSize = '18px';
                                                    $iconSize = '1.1em';
                                                    break;
                                            }

                                            // Check if has children
                                            $hasChildren = $categories->where('parent_id', $kategori->id)->count() > 0;
                                            $childCount = $categories->where('parent_id', $kategori->id)->count();

                                            // Start building row
                                            $output .=
                                                '<tr data-category-id="' .
                                                $kategori->id .
                                                '" data-parent-id="' .
                                                $kategori->parent_id .
                                                '" class="' .
                                                $rowClass .
                                                '"';
                                            if ($level > 0) {
                                                $output .= ' style="display: none;"';
                                            }
                                            $output .= '>';

                                            // No
                                            $output .= '<td>' . $no++ . '</td>';

                                            // Gambar
                                            $output .= '<td class="text-center">';
                                            if ($kategori->gambar) {
                                                $output .=
                                                    '<img src="' .
                                                    asset('storage/' . $kategori->gambar) .
                                                    '" alt="' .
                                                    $kategori->nama .
                                                    '" class="category-image img-thumbnail" style="width: ' .
                                                    $imageSize .
                                                    '; height: ' .
                                                    $imageSize .
                                                    '; object-fit: cover; cursor: pointer;" onclick="showImageModal(\'' .
                                                    asset('storage/' . $kategori->gambar) .
                                                    '\', \'' .
                                                    $kategori->nama .
                                                    '\')">';
                                            } else {
                                                $output .=
                                                    '<div class="text-muted" style="width: ' .
                                                    $imageSize .
                                                    '; height: ' .
                                                    $imageSize .
                                                    '; display: flex; align-items: center; justify-content: center; border: 1px dashed #dee2e6; border-radius: 4px;"><i class="mdi mdi-image-outline"></i></div>';
                                            }
                                            $output .= '</td>';

                                            // Nama & Hirarki
                                            $output .= '<td>';
                                            $output .= '<div class="d-flex align-items-center ' . $indentClass . '">';

                                            // Toggle button for categories that have children
                                            if ($hasChildren) {
                                                $output .=
                                                    '<button class="btn btn-link btn-sm p-0 me-2 hierarchy-toggle text-secondary" data-target="children-' .
                                                    $kategori->id .
                                                    '" title="Expand"><i class="mdi mdi-chevron-right"></i></button>';
                                            } else {
                                                $output .= '<span class="me-4"></span>';
                                            }

                                            $output .= $hierarchyIcon;

                                            $output .= '<div>';
                                            $fontWeight = $level == 0 ? 'fw-bold' : 'fw-medium';
                                            $output .=
                                                '<span class="text-dark ' .
                                                $fontWeight .
                                                '">' .
                                                $kategori->nama .
                                                '</span>';

                                            // Description
                                            if ($kategori->deskripsi) {
                                                $descLimit = $level == 0 ? 50 : 40;
                                                $output .=
                                                    '<br><small class="text-muted">' .
                                                    Str::limit($kategori->deskripsi, $descLimit) .
                                                    '</small>';
                                            }

                                            // Children count badge
                                            if ($hasChildren) {
                                                $output .=
                                                    '<span class="badge bg-info text-white ms-2" style="font-size: 0.65em;">' .
                                                    $childCount .
                                                    ' sub</span>';
                                            }

                                            // Show hierarchy path for deeper levels
                                            if ($level > 0) {
                                                $parentNames = [];
                                                $currentParentId = $kategori->parent_id;
                                                $tempLevel = 0;

                                                while ($currentParentId && $tempLevel < 3) {
                                                    // Prevent infinite loop
                                                    $parentCat = $categories->where('id', $currentParentId)->first();
                                                    if ($parentCat) {
                                                        array_unshift($parentNames, $parentCat->nama);
                                                        $currentParentId = $parentCat->parent_id;
                                                        $tempLevel++;
                                                    } else {
                                                        break;
                                                    }
                                                }

                                                // if (!empty($parentNames)) {
                                                //     $output .=
                                                //         '<br><small class="text-muted"><i class="mdi mdi-link me-1"></i>Path: ' .
                                                //         implode(' → ', $parentNames) .
                                                //         ' → <span class="text-primary">' .
                                                //         $kategori->nama .
                                                //         '</span></small>';
                                                // }
                                            }

                                            $output .= '</div>';
                                            $output .= '</div>';
                                            $output .= '</td>';

                                            // Type
                                            $typeBadgeClass =
                                                $level == 0
                                                    ? 'bg-primary'
                                                    : ($level == 1
                                                        ? 'bg-success'
                                                        : 'bg-warning text-dark');
                                            $output .=
                                                '<td><span class="badge ' .
                                                $typeBadgeClass .
                                                ' text-white">' .
                                                strtoupper($kategori->type) .
                                                '</span></td>';

                                            // Level
                                            $output .= '<td>' . $levelBadge . '</td>';

                                            // Parent
                                            if ($kategori->parent_id) {
                                                $parent = $categories->where('id', $kategori->parent_id)->first();
                                                $output .=
                                                    '<td><span class="text-primary">' .
                                                    ($parent ? $parent->nama : '-') .
                                                    '</span></td>';
                                            } else {
                                                $output .= '<td><span class="text-muted">-</span></td>';
                                            }

                                            // Warna
                                            $output .= '<td>';
                                            if ($kategori->warna) {
                                                $output .= '<div class="color-preview d-flex align-items-center">';
                                                $output .=
                                                    '<span class="color-box me-2" style="background-color: ' .
                                                    $kategori->warna .
                                                    '; width: ' .
                                                    $colorSize .
                                                    '; height: ' .
                                                    $colorSize .
                                                    '; border-radius: 4px; border: 1px solid #dee2e6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></span>';
                                                $output .= '<small class="text-muted">' . $kategori->warna . '</small>';
                                                $output .= '</div>';
                                            } else {
                                                $output .= '<span class="text-muted">-</span>';
                                            }
                                            $output .= '</td>';

                                            // Jenis
                                            $output .= '<td>';
                                            if ($kategori->is_marker) {
                                                $output .=
                                                    '<span class="badge bg-warning text-dark"><i class="mdi mdi-map-marker"></i> Marker</span>';
                                            } else {
                                                $output .=
                                                    '<span class="badge bg-info text-white"><i class="mdi mdi-layers"></i> Layer</span>';
                                            }
                                            $output .= '</td>';

                                            // Icon
                                            $output .= '<td class="text-center">';
                                            if ($kategori->is_marker && $kategori->icon) {
                                                $output .=
                                                    '<i class="' .
                                                    $kategori->icon .
                                                    '" style="color: ' .
                                                    ($kategori->warna ?? '#007bff') .
                                                    '; font-size: ' .
                                                    $iconSize .
                                                    ';" title="' .
                                                    $kategori->icon .
                                                    '"></i>';
                                            } else {
                                                $output .= '<span class="text-muted">-</span>';
                                            }
                                            $output .= '</td>';

                                            // Status
                                            $output .= '<td class="text-center">';
                                            if ($kategori->is_active) {
                                                $output .=
                                                    '<span class="badge bg-success text-white"><i class="mdi mdi-check-circle"></i> Aktif</span>';
                                            } else {
                                                $output .=
                                                    '<span class="badge bg-secondary text-white"><i class="mdi mdi-pause-circle"></i> Nonaktif</span>';
                                            }
                                            $output .= '</td>';

                                            // Actions
                                            $output .= '<td>';
                                            if (
                                                in_array($role, ['super-admin', 'admin-bappeda']) ||
                                                $kategori->user_id === $user->id
                                            ) {
                                                $output .= '<div class="btn-group" role="group">';
                                                $output .=
                                                    '<button type="button" class="btn btn-sm btn-outline-success btn-edit" ';
                                                $output .= 'data-id="' . $kategori->id . '" ';
                                                $output .= 'data-nama="' . htmlspecialchars($kategori->nama) . '" ';
                                                $output .= 'data-type="' . $kategori->type . '" ';
                                                $output .= 'data-parent-id="' . $kategori->parent_id . '" ';
                                                $output .= 'data-warna="' . $kategori->warna . '" ';
                                                $output .= 'data-is-marker="' . ($kategori->is_marker ? 1 : 0) . '" ';
                                                $output .= 'data-is-active="' . ($kategori->is_active ? 1 : 0) . '" ';
                                                $output .= 'data-icon="' . $kategori->icon . '" ';
                                                $output .= 'data-gambar="' . $kategori->gambar . '" ';
                                                $output .=
                                                    'data-deskripsi="' . htmlspecialchars($kategori->deskripsi) . '" ';
                                                $output .=
                                                    'data-bs-toggle="modal" data-bs-target="#editModal" title="Edit">';
                                                $output .= '<i class="mdi mdi-pencil"></i>';
                                                $output .= '</button>';

                                                $output .=
                                                    '<form action="' .
                                                    route('categories.destroy', $kategori->id) .
                                                    '" method="POST" style="display: inline-block;" data-confirm="delete" data-name="' .
                                                    htmlspecialchars($kategori->nama) .
                                                    '">';
                                                $output .= csrf_field();
                                                $output .= method_field('DELETE');
                                                $output .=
                                                    '<button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus kategori">';
                                                $output .= '<i class="fa fa-trash"></i>';
                                                $output .= '</button>';
                                                $output .= '</form>';
                                                $output .= '</div>';
                                            }
                                            $output .= '</td>';

                                            $output .= '</tr>';

                                            // Recursively render children (supports unlimited depth, but we limit to 3 levels)
                                            if ($hasChildren && $level < 2) {
                                                // Limit to 3 levels (0, 1, 2)
                                                $output .= renderCategoryHierarchy(
                                                    $categories,
                                                    $kategori->id,
                                                    $level + 1,
                                                    $no,
                                                    $user,
                                                    $role,
                                                );
                                            }
                                        }

                                        return $output;
                                    }

                                    // Render the hierarchy starting from root categories
                                    echo renderCategoryHierarchy($categories, null, 0, $no, $user, $role);
                                @endphp

                                @if ($categories->count() == 0)
                                    <tr>
                                        <td colspan="11" class="text-center py-4">
                                            <i class="mdi mdi-tag-multiple mdi-48px text-muted"></i>
                                            <h5 class="text-muted mt-2">Belum ada kategori yang dibuat</h5>
                                            <p class="text-muted">Klik tombol "Tambah Kategori" untuk memulai</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#addModal">
                                                <i class="mdi mdi-plus"></i> Tambah Kategori Pertama
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Preview Gambar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="" class="img-fluid" style="max-height: 500px;">
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
                <form id="addForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <!-- LEFT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
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

                                <div class="form-group mb-2">
                                    <label for="add_nama" class="form-label">Nama Kategori <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="add_nama" name="nama" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="add_deskripsi" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="add_deskripsi" name="deskripsi" rows="2"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="add_parent_id" class="form-label">Parent Kategori</label>
                                    <select class="form-control" id="add_parent_id" name="parent_id">
                                        <option value="">-- Pilih Parent (Opsional) --</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label d-block">Status & Jenis Kategori</label>
                                    <div class="settings-switch-group">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_active" value="0">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="add_is_active" name="is_active" checked>
                                            <label class="form-check-label" for="add_is_active">
                                                <i class="mdi mdi-check-circle text-success me-1"></i>Aktifkan
                                                Kategori
                                            </label>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_marker" value="0">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="add_is_marker" name="is_marker">
                                            <label class="form-check-label" for="add_is_marker">
                                                <i class="mdi mdi-map-marker text-warning me-1"></i>Gunakan sebagai
                                                Marker
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="add_gambar" class="form-label">Upload Gambar</label>
                                    <input type="file" class="form-control" id="add_gambar" name="gambar"
                                        accept="image/*"
                                        onchange="previewImage(this, 'add_imagePreview'); document.getElementById('add_imagePreviewPlaceholder').style.display = this.files.length ? 'none' : 'flex';">
                                    <div class="form-text">Format: JPG, PNG, GIF. Maksimal 2MB</div>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="form-group mb-2">
                                    <label class="form-label d-block">Preview Gambar</label>
                                    <div class="image-preview-box">
                                        <div class="image-preview-placeholder" id="add_imagePreviewPlaceholder">
                                            <i class="mdi mdi-image-outline"></i>
                                            <span>Belum ada gambar dipilih</span>
                                        </div>
                                        <div id="add_imagePreview" class="image-preview-content"
                                            style="display: none;">
                                            <img id="add_previewImg" src="" alt="Preview" class="img-fluid">
                                            <button type="button" class="btn btn-sm btn-outline-danger mt-2 w-100"
                                                onclick="removeImagePreview('add')">
                                                <i class="mdi mdi-close"></i> Hapus Gambar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label for="add_warna" class="form-label">Warna</label>
                                    <div class="color-picker-widget">
                                        <div class="color-swatch-list" id="add_colorSwatches">
                                            <button type="button" class="color-swatch" data-color="#007bff"
                                                style="background-color:#007bff" title="#007bff"></button>
                                            <button type="button" class="color-swatch" data-color="#28a745"
                                                style="background-color:#28a745" title="#28a745"></button>
                                            <button type="button" class="color-swatch" data-color="#dc3545"
                                                style="background-color:#dc3545" title="#dc3545"></button>
                                            <button type="button" class="color-swatch" data-color="#ffc107"
                                                style="background-color:#ffc107" title="#ffc107"></button>
                                            <button type="button" class="color-swatch" data-color="#17a2b8"
                                                style="background-color:#17a2b8" title="#17a2b8"></button>
                                            <button type="button" class="color-swatch" data-color="#6f42c1"
                                                style="background-color:#6f42c1" title="#6f42c1"></button>
                                            <button type="button" class="color-swatch" data-color="#fd7e14"
                                                style="background-color:#fd7e14" title="#fd7e14"></button>
                                            <button type="button" class="color-swatch" data-color="#20c997"
                                                style="background-color:#20c997" title="#20c997"></button>
                                            <button type="button" class="color-swatch" data-color="#6c757d"
                                                style="background-color:#6c757d" title="#6c757d"></button>
                                            <button type="button" class="color-swatch" data-color="#212529"
                                                style="background-color:#212529" title="#212529"></button>
                                        </div>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color"
                                                id="add_warna" name="warna" value="#007bff">
                                            <input type="text" class="form-control text-uppercase" id="add_warnaHex"
                                                maxlength="7" placeholder="#RRGGBB" autocomplete="off"
                                                value="#007BFF">
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ICON MARKER PICKER (1 column, full width) -->
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group mb-0" id="add_iconContainer" style="display: none;">
                                    <label class="form-label">
                                        <i class="mdi mdi-map-marker me-1"></i> Ikon Marker
                                    </label>

                                    <!-- Hidden select acts as the source of truth & data source for the picker -->
                                    <select id="add_icon" name="icon" class="d-none">
                                        <option value="">-- Pilih Ikon --</option>
                                        @include('backend.partials.icon-options')
                                    </select>

                                    <div class="row g-2 mb-2">
                                        <div class="col-md-7">
                                            <div class="icon-picker-search h-100">
                                                <div class="input-group input-group-sm h-100">
                                                    <span class="input-group-text"><i
                                                            class="mdi mdi-magnify"></i></span>
                                                    <input type="text" class="form-control" id="add_iconSearch"
                                                        placeholder="Cari ikon berdasarkan nama atau class...">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div id="add_iconPreview" class="icon-preview-container icon-preview-inline">
                                                <span class="text-muted">Pilih ikon untuk melihat pratinjau</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="icon-picker-grid" id="add_iconGrid"></div>

                                    <div class="form-text">Ikon hanya berlaku untuk kategori marker (Point). Klik salah
                                        satu ikon di atas untuk memilih.</div>
                                </div>
                            </div>
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

                <form id="editForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="row">
                            <!-- LEFT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
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

                                <div class="form-group mb-2">
                                    <label for="edit_nama" class="form-label">Nama Kategori <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_nama" name="nama" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="2"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="form-group mb-2">
                                    <label for="edit_parent_id" class="form-label">Parent Kategori</label>
                                    <select class="form-control" id="edit_parent_id" name="parent_id">
                                        <option value="">-- Pilih Parent (Opsional) --</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label d-block">Status & Jenis Kategori</label>
                                    <div class="settings-switch-group">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_active" value="0">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="edit_is_active" name="is_active">
                                            <label class="form-check-label" for="edit_is_active">
                                                <i class="mdi mdi-check-circle text-success me-1"></i>Aktifkan
                                                Kategori
                                            </label>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="hidden" name="is_marker" value="0">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="edit_is_marker" name="is_marker">
                                            <label class="form-check-label" for="edit_is_marker">
                                                <i class="mdi mdi-map-marker text-warning me-1"></i>Gunakan sebagai
                                                Marker
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="edit_gambar" class="form-label">Upload Gambar</label>
                                    <input type="file" class="form-control" id="edit_gambar" name="gambar"
                                        accept="image/*"
                                        onchange="previewImage(this, 'edit_imagePreview'); document.getElementById('edit_imagePreviewPlaceholder').style.display = this.files.length ? 'none' : 'flex';">
                                    <div class="form-text">Format: JPG, PNG, GIF. Maksimal 2MB. Kosongkan jika tidak
                                        ingin mengubah.</div>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="form-group mb-2">
                                    <label class="form-label d-block">Preview Gambar</label>
                                    <div class="image-preview-box">
                                        <div class="image-preview-placeholder" id="edit_imagePreviewPlaceholder">
                                            <i class="mdi mdi-image-outline"></i>
                                            <span>Belum ada gambar</span>
                                        </div>
                                        <div id="edit_currentImage" class="image-preview-content"
                                            style="display: none;">
                                            <small class="text-muted d-block mb-1">Gambar saat ini</small>
                                            <img id="edit_currentImg" src="" alt="Current" class="img-fluid">
                                        </div>
                                        <div id="edit_imagePreview" class="image-preview-content"
                                            style="display: none;">
                                            <small class="text-muted d-block mb-1">Preview gambar baru</small>
                                            <img id="edit_previewImg" src="" alt="Preview" class="img-fluid">
                                            <button type="button" class="btn btn-sm btn-outline-danger mt-2 w-100"
                                                onclick="removeImagePreview('edit')">
                                                <i class="mdi mdi-close"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label for="edit_warna" class="form-label">Warna</label>
                                    <div class="color-picker-widget">
                                        <div class="color-swatch-list" id="edit_colorSwatches">
                                            <button type="button" class="color-swatch" data-color="#007bff"
                                                style="background-color:#007bff" title="#007bff"></button>
                                            <button type="button" class="color-swatch" data-color="#28a745"
                                                style="background-color:#28a745" title="#28a745"></button>
                                            <button type="button" class="color-swatch" data-color="#dc3545"
                                                style="background-color:#dc3545" title="#dc3545"></button>
                                            <button type="button" class="color-swatch" data-color="#ffc107"
                                                style="background-color:#ffc107" title="#ffc107"></button>
                                            <button type="button" class="color-swatch" data-color="#17a2b8"
                                                style="background-color:#17a2b8" title="#17a2b8"></button>
                                            <button type="button" class="color-swatch" data-color="#6f42c1"
                                                style="background-color:#6f42c1" title="#6f42c1"></button>
                                            <button type="button" class="color-swatch" data-color="#fd7e14"
                                                style="background-color:#fd7e14" title="#fd7e14"></button>
                                            <button type="button" class="color-swatch" data-color="#20c997"
                                                style="background-color:#20c997" title="#20c997"></button>
                                            <button type="button" class="color-swatch" data-color="#6c757d"
                                                style="background-color:#6c757d" title="#6c757d"></button>
                                            <button type="button" class="color-swatch" data-color="#212529"
                                                style="background-color:#212529" title="#212529"></button>
                                        </div>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color"
                                                id="edit_warna" name="warna" value="#007bff">
                                            <input type="text" class="form-control text-uppercase" id="edit_warnaHex"
                                                maxlength="7" placeholder="#RRGGBB" autocomplete="off"
                                                value="#007BFF">
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ICON MARKER PICKER (1 column, full width) -->
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group mb-0" id="edit_iconContainer" style="display: none;">
                                    <label class="form-label">
                                        <i class="mdi mdi-map-marker me-1"></i> Ikon Marker
                                    </label>

                                    <!-- Hidden select acts as the source of truth & data source for the picker -->
                                    <select id="edit_icon" name="icon" class="d-none">
                                        <option value="">-- Pilih Ikon --</option>
                                        @include('backend.partials.icon-options')
                                    </select>

                                    <div class="row g-2 mb-2">
                                        <div class="col-md-7">
                                            <div class="icon-picker-search h-100">
                                                <div class="input-group input-group-sm h-100">
                                                    <span class="input-group-text"><i
                                                            class="mdi mdi-magnify"></i></span>
                                                    <input type="text" class="form-control" id="edit_iconSearch"
                                                        placeholder="Cari ikon berdasarkan nama atau class...">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div id="edit_iconPreview"
                                                class="icon-preview-container icon-preview-inline">
                                                <span class="text-muted">Pilih ikon untuk melihat pratinjau</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="icon-picker-grid" id="edit_iconGrid"></div>

                                    <div class="form-text">Ikon hanya berlaku untuk kategori marker (Point). Klik
                                        salah satu ikon di atas untuk memilih.</div>
                                </div>
                            </div>
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

        /* Category Image Styling */
        .category-image {
            transition: transform 0.2s ease;
            border-radius: 6px;
        }

        .category-image:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

        .bg-success {
            background-color: #28a745 !important;
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

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
            margin-bottom: 0;
            width: 100%;
        }

        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0.35rem;
            width: 100%;
            margin: 0;
            font-weight: 600;
            color: #495057;
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            background-color: #fff;
            font-size: 0.875rem;
            min-height: 44px;
            width: 100%;
        }

        .dataTables_wrapper .dataTables_filter .input-group {
            width: 100%;
        }

        .dataTables_wrapper .dataTables_filter input {
            padding: 8px 12px;
            border-radius: 6px 0 0 6px;
            border: 1px solid #ced4da;
            background-color: #fff;
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
            min-height: 44px;
            max-width: 100%;
        }

        .dataTables_wrapper .dataTables_filter .btn {
            min-height: 44px;
            border-radius: 0 6px 6px 0;
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

        .image-preview-box {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            padding: 12px;
            text-align: center;
            border: 1px dashed #ced4da;
            border-radius: 8px;
            background-color: #f8f9fa;
        }

        .image-preview-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            color: #adb5bd;
        }

        .image-preview-placeholder i {
            font-size: 2.2rem;
        }

        .image-preview-placeholder span {
            font-size: 0.8rem;
        }

        .image-preview-content {
            width: 100%;
        }

        .image-preview-content img {
            max-width: 100%;
            max-height: 160px;
            border-radius: 6px;
            object-fit: contain;
        }

        /* ===========================================
                                                                                                                                                                               ICON PICKER
                                                                                                                                                                            =========================================== */
        .icon-picker-grid {
            max-height: 260px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fff;
        }

        .icon-picker-group-title {
            margin: 12px 0 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9aa4af;
        }

        .icon-picker-group-title:first-child {
            margin-top: 0;
        }

        .icon-picker-items {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
        }

        @media (max-width: 576px) {
            .icon-picker-items {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .icon-picker-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 4px;
            padding: 10px 4px;
            border: 1px solid #eef2f7;
            border-radius: 6px;
            background: #fff;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .icon-picker-item:hover {
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.06);
        }

        .icon-picker-item.active {
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.12);
            box-shadow: 0 0 0 1px #007bff inset;
        }

        .icon-picker-glyph {
            font-size: 1.9rem;
            line-height: 1;
            color: #495057;
        }

        .icon-picker-item.active .icon-picker-glyph {
            color: #007bff;
        }

        .icon-picker-name {
            display: -webkit-box;
            width: 100%;
            overflow: hidden;
            font-size: 0.65rem;
            font-weight: 600;
            color: #343a40;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .icon-picker-class {
            display: block;
            width: 100%;
            overflow: hidden;
            font-size: 0.58rem;
            color: #868e96;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .icon-picker-empty {
            padding: 20px 0;
            color: #adb5bd;
            font-size: 0.85rem;
            text-align: center;
        }

        /* ===========================================
                                                                                                                                                                               COLOR PICKER WIDGET
                                                                                                                                                                            =========================================== */
        .color-picker-widget {
            padding: 0.75rem;
            background-color: #f8f9fa;
            border: 1px solid #eef2f7;
            border-radius: 8px;
        }

        .color-swatch-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 0.65rem;
        }

        .color-swatch {
            width: 26px;
            height: 26px;
            padding: 0;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 0 1px #dee2e6;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .color-swatch:hover {
            transform: scale(1.12);
        }

        .color-swatch.active {
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #007bff;
        }

        .color-picker-widget .input-group .form-control-color {
            max-width: 50px;
        }

        .settings-switch-group {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            padding: 0.65rem 0.85rem;
            background-color: #f8f9fa;
            border: 1px solid #eef2f7;
            border-radius: 8px;
        }

        .settings-switch-group .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-left: 0;
            margin: 0;
            min-height: auto;
        }

        .settings-switch-group .form-check-input {
            flex-shrink: 0;
            float: none;
            margin: 0;
        }

        .settings-switch-group .form-check-label {
            margin: 0;
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

        /* Compact variant used when the preview sits beside the icon search box */
        .icon-preview-inline {
            min-height: 31px;
            padding: 4px 10px;
        }

        .icon-preview-inline span.text-muted {
            font-size: 0.72rem;
        }

        .icon-preview-inline .icon-preview-icon {
            font-size: 1.4em;
            margin-right: 8px;
        }

        .icon-preview-inline .icon-preview-details h6 {
            display: none;
        }

        .icon-preview-inline .icon-preview-details small {
            font-size: 0.72em;
        }

        .icon-preview-inline .icon-preview-code {
            font-size: 0.68em;
            padding: 1px 4px;
        }

        /* ===========================================
                                                                                                                                                                               STATISTICS CARDS (COMPACT)
                                                                                                                                                                            =========================================== */
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
            font-size: 1.6rem;
            opacity: 0.55;
            flex-shrink: 0;
            margin-left: 0.5rem;
        }

        @media (max-width: 576px) {
            .stat-card-compact .stat-value {
                font-size: 1.25rem;
            }

            .stat-card-compact .stat-icon {
                font-size: 1.3rem;
            }
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
            border-radius: 5px;
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

            .category-image {
                width: 35px !important;
                height: 35px !important;
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

        /* ===========================================
                                                                                                                                                                               ACTIVE COUNT WARNING STYLES
                                                                                                                                                                            =========================================== */
        .form-text.text-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 8px;
            font-size: 0.875rem;
        }

        .form-check.text-muted {
            opacity: 0.6;
        }

        .form-check.text-muted .form-check-label {
            color: #6c757d !important;
        }

        .form-check-input:disabled {
            opacity: 0.5;
        }

        /* Active count badge styling */
        .active-count-badge {
            background: linear-gradient(135deg, #ffc107, #ff8f00);
            color: #212529;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .active-count-badge.warning {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border-color: rgba(220, 53, 69, 0.3);
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Image preview function
        function previewImage(input, previewId) {
            const file = input.files[0];
            const preview = document.getElementById(previewId);
            const previewImg = document.getElementById(previewId.replace('_imagePreview', '_previewImg'));

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';

                    // Hide current image when preview new one (for edit modal)
                    if (previewId === 'edit_imagePreview') {
                        const currentImageDiv = document.getElementById('edit_currentImage');
                        if (currentImageDiv) {
                            currentImageDiv.style.display = 'none';
                        }
                    }
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                previewImg.src = '';
            }
        }

        // Remove image preview function
        function removeImagePreview(type) {
            const fileInput = document.getElementById(`${type}_gambar`);
            const preview = document.getElementById(`${type}_imagePreview`);
            const previewImg = document.getElementById(`${type}_previewImg`);

            // Reset file input
            fileInput.value = '';

            // Hide preview
            preview.style.display = 'none';
            previewImg.src = '';

            // Show current image again (for edit modal) if one exists, otherwise fall
            // back to the "no image" placeholder
            let hasCurrentImage = false;
            if (type === 'edit') {
                const currentImageDiv = document.getElementById('edit_currentImage');
                const currentImg = document.getElementById('edit_currentImg');
                hasCurrentImage = !!(currentImg && currentImg.getAttribute('src'));
                if (currentImageDiv) {
                    currentImageDiv.style.display = hasCurrentImage ? 'block' : 'none';
                }
            }

            const placeholder = document.getElementById(`${type}_imagePreviewPlaceholder`);
            if (placeholder) {
                placeholder.style.display = hasCurrentImage ? 'none' : 'flex';
            }
        }

        // Show image modal function
        function showImageModal(imageSrc, altText) {
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('imageModalLabel');

            modalImage.src = imageSrc;
            modalImage.alt = altText;
            modalTitle.textContent = `Preview: ${altText}`;

            modal.show();
        }

        $(document).ready(function() {
            // Global state tracking - declare at the top
            let isAllExpanded = false;

            // Initialize DataTable with hierarchy support
            const table = $('#categoriesTable').DataTable({
                "processing": true,
                "pageLength": {{ request('per_page', 200) }},
                "lengthMenu": [
                    [10, 25, 50, 100, 200, 500],
                    [10, 25, 50, 100, 200, 500]
                ],
                "ordering": false, // Disable all sorting to maintain hierarchy
                "columnDefs": [{
                        "searchable": false,
                        "targets": [-1] // Disable search on action column
                    },
                    {
                        "className": "text-center",
                        "targets": [0, 1, 7, 8, -
                            1
                        ] // Center align for no, image, icon, status, action columns
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
                "dom": '<"row mb-2"<"col-sm-12"<"hierarchy-controls text-start">>>' +
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
                    $('#searchTableBtn').on('click', function() {
                        table.search($('#tableSearch').val()).draw();
                    });

                    $('#tableSearch').on('input', function() {
                        table.search($(this).val()).draw();
                    });

                    $('#per_page').on('change', function() {
                        table.page.len(parseInt($(this).val(), 10)).draw();
                    });

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

            // Build a visual icon picker (icon + name + class) from a hidden <select>'s options
            function buildIconPicker(selectId, gridId) {
                const $select = $(selectId);
                const $grid = $(gridId);
                $grid.empty();

                let $itemsWrap = null;

                $select.children('option, optgroup').each(function() {
                    if (this.tagName === 'OPTGROUP') {
                        $grid.append(
                            `<div class="icon-picker-group-title">${$(this).attr('label')}</div>`
                        );
                        $itemsWrap = $('<div class="icon-picker-items"></div>');
                        $grid.append($itemsWrap);
                        $(this).children('option').each(function() {
                            appendIconItem($itemsWrap, $(this));
                        });
                    } else {
                        const value = $(this).val();
                        if (!value) return; // skip the placeholder option
                        if (!$itemsWrap) {
                            $itemsWrap = $('<div class="icon-picker-items"></div>');
                            $grid.append($itemsWrap);
                        }
                        appendIconItem($itemsWrap, $(this));
                    }
                });

                if ($grid.find('.icon-picker-item').length === 0) {
                    $grid.html('<div class="icon-picker-empty">Tidak ada ikon tersedia</div>');
                }
            }

            function appendIconItem($container, $option) {
                const value = $option.val();
                const label = $option.text().trim();
                const searchText = (label + ' ' + value).toLowerCase();

                $container.append(`
                    <div class="icon-picker-item" data-icon-value="${value}" data-search="${searchText}">
                        <i class="${value} icon-picker-glyph"></i>
                        <span class="icon-picker-name">${label}</span>
                        <code class="icon-picker-class">${value}</code>
                    </div>
                `);
            }

            buildIconPicker('#add_icon', '#add_iconGrid');
            buildIconPicker('#edit_icon', '#edit_iconGrid');

            // Select an icon from the picker (works for both add & edit grids)
            $(document).on('click', '.icon-picker-grid .icon-picker-item', function() {
                const iconValue = $(this).data('icon-value');
                const $grid = $(this).closest('.icon-picker-grid');
                const prefix = $grid.attr('id').replace('_iconGrid', '');

                $grid.find('.icon-picker-item').removeClass('active');
                $(this).addClass('active');
                $(`#${prefix}_icon`).val(iconValue).trigger('change');
            });

            // Filter the icon picker by name or class (works for both add & edit)
            $(document).on('input', '#add_iconSearch, #edit_iconSearch', function() {
                const prefix = this.id.replace('_iconSearch', '');
                const $grid = $(`#${prefix}_iconGrid`);
                const query = $(this).val().trim().toLowerCase();

                $grid.find('.icon-picker-item').each(function() {
                    const matches = !query || $(this).data('search').toString().includes(query);
                    $(this).toggle(matches);
                });

                $grid.find('.icon-picker-items').each(function() {
                    const hasVisible = $(this).find('.icon-picker-item:visible').length > 0;
                    $(this).toggle(hasVisible);
                    $(this).prev('.icon-picker-group-title').toggle(hasVisible);
                });
            });

            // Icon preview dengan styling yang lebih baik
            $('#add_icon, #edit_icon').on('change', function() {
                const iconClass = $(this).val();
                const isAdd = $(this).attr('id') === 'add_icon';
                const previewElementId = isAdd ? '#add_iconPreview' : '#edit_iconPreview';
                const colorInputId = isAdd ? '#add_warna' : '#edit_warna';
                const colorValue = $(colorInputId).val() || '#007bff';

                updateIconPreview(iconClass, previewElementId, colorValue);
            });

            // Apply a color to the native picker, hex field, swatches & icon preview.
            // `prefix` is "add" or "edit" — shared by both modals' color-picker widgets.
            function applyColor(prefix, newColor) {
                $(`#${prefix}_warna`).val(newColor);
                $(`#${prefix}_warnaHex`).val(newColor.toUpperCase());
                $(`#${prefix}_colorSwatches .color-swatch`).removeClass('active');
                $(`#${prefix}_colorSwatches .color-swatch[data-color="${newColor.toLowerCase()}"]`)
                    .addClass('active');

                const iconElement = $(`#${prefix}_iconPreview .icon-preview-icon`);
                if (iconElement.length) {
                    iconElement.css('color', newColor);
                }
            }

            // Native color picker change
            $('#add_warna, #edit_warna').on('change input', function() {
                const prefix = this.id.replace('_warna', '');
                applyColor(prefix, $(this).val());
            });

            // Hex text field: only apply once it's a valid #RRGGBB value
            $('#add_warnaHex, #edit_warnaHex').on('input', function() {
                const prefix = this.id.replace('_warnaHex', '');
                let value = $(this).val().trim();
                if (value && value[0] !== '#') {
                    value = '#' + value;
                }
                $(this).val(value.toUpperCase());

                if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    applyColor(prefix, value);
                }
            });

            // Quick color swatches
            $(document).on('click', '.color-swatch-list .color-swatch', function() {
                const prefix = $(this).closest('.color-swatch-list').attr('id').replace(
                    '_colorSwatches', '');
                applyColor(prefix, $(this).data('color'));
            });

            // Marker checkbox functionality
            $('#add_is_marker, #edit_is_marker').on('change', function() {
                const isAdd = $(this).attr('id') === 'add_is_marker';
                const prefix = isAdd ? 'add' : 'edit';
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

                    $(`#${prefix}_iconGrid .icon-picker-item`).removeClass('active').show();
                    $(`#${prefix}_iconGrid .icon-picker-items, #${prefix}_iconGrid .icon-picker-group-title`)
                        .show();
                    $(`#${prefix}_iconSearch`).val('');
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
                applyColor('add', '#007bff');
                $('#add_iconContainer').hide();
                $('#add_iconPreview').html(
                    '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>').removeClass(
                    'has-icon');

                // Reset icon picker selection & search
                $('#add_iconGrid .icon-picker-item').removeClass('active').show();
                $('#add_iconGrid .icon-picker-items, #add_iconGrid .icon-picker-group-title').show();
                $('#add_iconSearch').val('');

                // Reset image preview
                $('#add_imagePreview').hide();
                $('#add_previewImg').attr('src', '');
                $('#add_imagePreviewPlaceholder').show();

                // Set default values
                $('#add_is_active').prop('checked', false); // Default to active
                $('#add_is_marker').prop('checked', false);

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
                $('#edit_deskripsi').val($(this).data('deskripsi'));
                applyColor('edit', $(this).data('warna') || '#007bff');

                // Handle is_active checkbox
                const isActive = $(this).data('is-active');
                $('#edit_is_active').prop('checked', isActive);

                // Handle marker checkbox and icon
                const isMarker = $(this).data('is-marker');
                $('#edit_is_marker').prop('checked', isMarker);

                // Reset icon picker selection & search
                $('#edit_iconGrid .icon-picker-item').removeClass('active').show();
                $('#edit_iconGrid .icon-picker-items, #edit_iconGrid .icon-picker-group-title').show();
                $('#edit_iconSearch').val('');

                if (isMarker) {
                    $('#edit_iconContainer').show();
                    const iconClass = $(this).data('icon');
                    $('#edit_icon').val(iconClass);
                    const colorValue = $(this).data('warna') || '#007bff';
                    updateIconPreview(iconClass, '#edit_iconPreview', colorValue);
                    $(`#edit_iconGrid .icon-picker-item[data-icon-value="${iconClass}"]`).addClass(
                        'active');
                } else {
                    $('#edit_iconContainer').hide();
                    $('#edit_icon').val('');
                    $('#edit_iconPreview').html(
                            '<span class="text-muted">Pilih ikon untuk melihat pratinjau</span>')
                        .removeClass('has-icon');
                }

                // Handle current image display
                const currentImage = $(this).data('gambar');
                if (currentImage) {
                    $('#edit_currentImage').show();
                    $('#edit_currentImg').attr('src', `{{ asset('storage/') }}/${currentImage}`);
                    $('#edit_imagePreviewPlaceholder').hide();
                } else {
                    $('#edit_currentImage').hide();
                    $('#edit_imagePreviewPlaceholder').show();
                }

                // Reset new image preview
                $('#edit_imagePreview').hide();
                $('#edit_previewImg').attr('src', '');
                $('#edit_gambar').val('');

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
