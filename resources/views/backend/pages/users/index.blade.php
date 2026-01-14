@extends('backend.partials.main', ['title' => 'Manajemen Pengguna'])

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-account-multiple"></i>
            </span>
            Manajemen Pengguna
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Manajemen Pengguna
                </li>
            </ul>
        </nav>
    </div>

    <!-- Statistics Cards -->
    @if ($userList->count() > 0)
        <div class="row mb-4">
            <div class="col-12 col-md-4 stretch-card grid-margin">
                <div class="card bg-gradient-primary card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Total Pengguna
                            <i class="mdi mdi-account-multiple mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['total'] ?? $userList->count() }}</h2>
                        <h6 class="card-text">Seluruh pengguna terdaftar</h6>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 stretch-card grid-margin">
                <div class="card bg-gradient-success card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            Administrator
                            <i class="mdi mdi-shield-account mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['admin'] ?? $userList->where('role.name', 'Super Admin')->count() }}
                        </h2>
                        <h6 class="card-text">Pengguna dengan role admin</h6>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 stretch-card grid-margin">
                <div class="card bg-gradient-warning card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle" />
                        <h4 class="font-weight-normal mb-3">
                            OPD
                            <i class="mdi mdi-office-building mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-4">{{ $stats['dengan_opd'] ?? $userList->whereNotNull('opd_id')->count() }}</h2>
                        <h6 class="card-text">Pengguna terkait OPD</h6>
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
                            <i class="mdi mdi-account-multiple"></i>
                            Daftar Pengguna
                        </h4>
                        <div>
                            <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal"
                                data-bs-target="#addModal">
                                <i class="mdi mdi-plus"></i> Tambah Pengguna
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
                                    placeholder="Cari nama atau email pengguna...">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-6">
                                    <select id="filterRole" class="form-select">
                                        <option value="">Semua Role</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select id="filterOpd" class="form-select">
                                        <option value="">Semua OPD</option>
                                        <option value="null">Tanpa OPD</option>
                                        @foreach ($opdList as $opd)
                                            <option value="{{ $opd->id }}">{{ $opd->singkatan }}</option>
                                        @endforeach
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

                    <div class="table-responsive">
                        <table class="table table-hover" id="userTable">
                            <thead>
                                <tr>
                                    <th class="text-dark text-center d-none d-md-table-cell" style="width: 50px;">No</th>
                                    <th class="text-dark" style="min-width: 200px;">
                                        <div class="d-flex flex-column">
                                            <span>Nama & Email</span>
                                            <small class="text-muted d-md-none">Role & OPD</small>
                                        </div>
                                    </th>
                                    <th class="text-dark d-none d-lg-table-cell" style="min-width: 120px;">Role</th>
                                    <th class="text-dark d-none d-lg-table-cell" style="min-width: 150px;">OPD</th>
                                    <th class="text-dark text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="userTableBody">
                                @forelse($userList as $index => $user)
                                    <tr class="user-row" data-name="{{ strtolower($user->name) }}"
                                        data-email="{{ strtolower($user->email) }}" data-role-id="{{ $user->role_id }}"
                                        data-opd-id="{{ $user->opd_id ?: 'null' }}">
                                        <td class="text-center d-none d-md-table-cell">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold">{{ $user->name }}</span>
                                                <small class="text-muted">{{ $user->email }}</small>

                                                <!-- Role and OPD info for mobile -->
                                                <div class="d-lg-none mt-1">
                                                    <div class="d-flex flex-column small">
                                                        @if ($user->role)
                                                            <span
                                                                class="badge bg-{{ $user->role->name === 'Admin' ? 'danger' : 'info' }} mb-1"
                                                                style="font-size: 0.7em; width: fit-content;">
                                                                {{ $user->role->name }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary mb-1"
                                                                style="font-size: 0.7em; width: fit-content;">
                                                                No Role
                                                            </span>
                                                        @endif
                                                        @if ($user->opd)
                                                            <span class="text-muted">{{ $user->opd->singkatan }}</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            @if ($user->role)
                                                <span
                                                    class="badge bg-{{ $user->role->name === 'Super Admin' ? 'danger' : 'info' }}">
                                                    {{ $user->role->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">No Role</span>
                                            @endif
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            @if ($user->opd)
                                                <div class="d-flex align-items-center">
                                                    <i class="mdi mdi-office-building me-1"></i>
                                                    {{ $user->opd->singkatan }}
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group-vertical d-md-none" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info btn-show mb-1"
                                                    data-id="{{ $user->id }}" title="Lihat Detail">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                        data-id="{{ $user->id }}" data-name="{{ $user->name }}"
                                                        data-email="{{ $user->email }}"
                                                        data-role-id="{{ $user->role_id }}"
                                                        data-opd-id="{{ $user->opd_id }}" data-bs-toggle="modal"
                                                        data-bs-target="#editModal" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                    @if ($user->id !== 1)
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger btn-delete"
                                                            data-id="{{ $user->id }}"
                                                            onclick="deleteUser({{ $user->id }})" title="Hapus">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="btn-group d-none d-md-flex" role="group">
                                                @if ($user->id !== 1)
                                                    <button type="button" class="btn btn-sm btn-outline-info btn-show"
                                                        data-id="{{ $user->id }}" title="Lihat Detail">
                                                        <i class="mdi mdi-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                        data-id="{{ $user->id }}" data-name="{{ $user->name }}"
                                                        data-email="{{ $user->email }}"
                                                        data-role-id="{{ $user->role_id }}"
                                                        data-opd-id="{{ $user->opd_id }}" data-bs-toggle="modal"
                                                        data-bs-target="#editModal" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger btn-delete"
                                                        data-id="{{ $user->id }}"
                                                        onclick="deleteUser({{ $user->id }})" title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-data-row">
                                        <td colspan="6" class="text-center">
                                            <div class="py-4">
                                                <i class="mdi mdi-account-multiple-outline mdi-48px text-muted"></i>
                                                <p class="text-muted mt-2">Belum ada data pengguna</p>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#addModal">
                                                    <i class="mdi mdi-plus"></i> Tambah Pengguna Pertama
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
            <form id="addForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="addModalLabel">
                            <i class="mdi mdi-plus me-2"></i> Tambah Pengguna Baru
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body row">
                        <div class="col-md-6 mb-3">
                            <label for="add_name" class="form-label">Nama Lengkap <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" id="add_name" class="form-control"
                                placeholder="Masukkan nama lengkap">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="add_email" class="form-control"
                                placeholder="Masukkan email">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_password" class="form-label">Password <span
                                    class="text-danger">*</span></label>
                            <div class="password-input-group">
                                <input type="password" name="password" id="add_password" class="form-control"
                                    placeholder="Minimal 8 karakter">
                                <button type="button" class="password-toggle" data-target="add_password">
                                    <i class="mdi mdi-eye-off"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="add_password_strength" style="display: none;">
                                <div class="strength-bar" id="add_password_bar"></div>
                                <div class="strength-text" id="add_password_text"></div>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_password_confirmation" class="form-label">Konfirmasi Password <span
                                    class="text-danger">*</span></label>
                            <div class="password-input-group">
                                <input type="password" name="password_confirmation" id="add_password_confirmation"
                                    class="form-control" placeholder="Ulangi password">
                                <button type="button" class="password-toggle" data-target="add_password_confirmation">
                                    <i class="mdi mdi-eye-off"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role_id" id="add_role_id" class="form-select">
                                <option value="">Pilih Role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_opd_id" class="form-label">OPD</label>
                            <select name="opd_id" id="add_opd_id" class="form-select">
                                <option value="">Pilih OPD (Opsional)</option>
                                @foreach ($opdList as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->name }} ({{ $opd->singkatan }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
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
                        <i class="mdi mdi-eye me-2"></i> Detail Pengguna
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 100px; height: 100px; margin: 0 auto;">
                                <i class="mdi mdi-account mdi-48px text-primary"></i>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h4 id="show_name" class="mb-2"></h4>
                            <h6 id="show_email" class="text-primary mb-3"></h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Role:</strong>
                                    <p id="show_role" class="text-muted"></p>
                                </div>
                                <div class="col-md-6">
                                    <strong>OPD:</strong>
                                    <p id="show_opd" class="text-muted"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <strong>Dibuat pada:</strong>
                            <p id="show_created_at" class="text-muted"></p>
                        </div>
                    </div>
                </div>
            </div>
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
                            <i class="mdi mdi-pencil me-2"></i> Edit Data Pengguna
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">Nama Lengkap <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit_name">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_password" class="form-label">Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" name="password" id="edit_password"
                                    placeholder="Kosongkan jika tidak ingin mengubah">
                                <button type="button" class="password-toggle" data-target="edit_password">
                                    <i class="mdi mdi-eye-off"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="edit_password_strength" style="display: none;">
                                <div class="strength-bar" id="edit_password_bar"></div>
                                <div class="strength-text" id="edit_password_text"></div>
                            </div>
                            <div class="form-text">Minimal 8 karakter. Kosongkan jika tidak ingin mengubah password.</div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_password_confirmation" class="form-label">Konfirmasi Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" name="password_confirmation"
                                    id="edit_password_confirmation" placeholder="Ulangi password jika diubah">
                                <button type="button" class="password-toggle" data-target="edit_password_confirmation">
                                    <i class="mdi mdi-eye-off"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role_id" id="edit_role_id">
                                <option value="">Pilih Role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_opd_id" class="form-label">OPD</label>
                            <select class="form-select" name="opd_id" id="edit_opd_id">
                                <option value="">Pilih OPD (Opsional)</option>
                                @foreach ($opdList as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->name }} ({{ $opd->singkatan }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
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
        // Updated JavaScript section for user management with password features
        $(document).ready(function() {
            // Password Strength Checker Function
            function checkPasswordStrength(password) {
                if (!password || password.length === 0) {
                    return {
                        strength: 'none',
                        score: 0
                    };
                }

                let score = 0;
                const checks = {
                    length: password.length >= 8,
                    lowercase: /[a-z]/.test(password),
                    uppercase: /[A-Z]/.test(password),
                    numbers: /\d/.test(password),
                    symbols: /[^A-Za-z0-9]/.test(password)
                };

                // Calculate score based on criteria
                if (checks.length) score += 2;
                if (checks.lowercase) score += 1;
                if (checks.uppercase) score += 1;
                if (checks.numbers) score += 1;
                if (checks.symbols) score += 1;

                // Bonus for longer passwords
                if (password.length >= 12) score += 1;
                if (password.length >= 16) score += 1;

                // Determine strength level
                let strength = 'weak';
                if (score >= 6) strength = 'strong';
                else if (score >= 4) strength = 'medium';

                return {
                    strength,
                    score,
                    checks
                };
            }

            // Update Password Strength Display
            function updatePasswordStrength(inputId) {
                const password = $(`#${inputId}`).val();
                const result = checkPasswordStrength(password);

                const strengthContainer = $(`#${inputId}_strength`);
                const strengthBar = $(`#${inputId}_bar`);
                const strengthText = $(`#${inputId}_text`);

                if (password.length === 0) {
                    strengthContainer.hide();
                    return;
                }

                strengthContainer.show();

                // Update strength bar
                strengthBar.removeClass('strength-weak strength-medium strength-strong')
                    .addClass(`strength-${result.strength}`);

                // Update strength text
                const strengthTexts = {
                    weak: 'Password Lemah',
                    medium: 'Password Sedang',
                    strong: 'Password Kuat'
                };

                strengthText.removeClass('weak medium strong')
                    .addClass(result.strength)
                    .text(strengthTexts[result.strength]);
            }

            // Password Toggle Functionality
            function initializePasswordToggles() {
                $(document).on('click', '.password-toggle', function() {
                    const targetId = $(this).data('target');
                    const targetInput = $(`#${targetId}`);
                    const icon = $(this).find('i');

                    if (targetInput.attr('type') === 'password') {
                        targetInput.attr('type', 'text');
                        icon.removeClass('mdi-eye-off').addClass('mdi-eye');
                    } else {
                        targetInput.attr('type', 'password');
                        icon.removeClass('mdi-eye').addClass('mdi-eye-off');
                    }
                });
            }

            // Initialize Password Strength Checking
            function initializePasswordStrength() {
                // Add password input event listeners
                $('#add_password, #edit_password').on('input', function() {
                    const inputId = $(this).attr('id');
                    updatePasswordStrength(inputId);
                });
            }

            // Reset password visibility and strength when modals are shown
            $('#addModal, #editModal').on('show.bs.modal', function() {
                // Reset password field types to password
                $(this).find('input[type="text"]').each(function() {
                    if ($(this).attr('name') === 'password' || $(this).attr('name') ===
                        'password_confirmation') {
                        $(this).attr('type', 'password');
                    }
                });

                // Reset toggle icons
                $(this).find('.password-toggle i').removeClass('mdi-eye').addClass('mdi-eye-off');

                // Hide strength indicators
                $(this).find('.password-strength').hide();
            });

            // Initialize password features
            initializePasswordToggles();
            initializePasswordStrength();

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
                clearFormErrors(form);
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
                    url: "{{ route('users.store') }}",
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
                $('#edit_email').val($(this).data('email'));
                $('#edit_role_id').val($(this).data('role-id'));
                $('#edit_opd_id').val($(this).data('opd-id'));

                // Clear password fields
                $('#edit_password').val('');
                $('#edit_password_confirmation').val('');

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
                    url: "{{ route('users.update', ['user' => '__ID__']) }}".replace('__ID__', id),
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
                    url: "{{ route('users.show', ['user' => '__ID__']) }}".replace('__ID__', id),
                    type: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        const user = response.data || response;

                        $('#show_name').text(user.name);
                        $('#show_email').text(user.email);
                        $('#show_role').text(user.role ? user.role.name : '-');
                        $('#show_opd').text(user.opd ? user.opd.name + ' (' + user.opd
                            .singkatan + ')' : 'Tidak ada');
                        $('#show_status').text(user.is_active ? 'Aktif' : 'Nonaktif');
                        $('#show_created_at').text(new Date(user.created_at).toLocaleDateString(
                            'id-ID'));

                        const modal = new bootstrap.Modal(document.getElementById('showModal'));
                        modal.show();
                    },
                    error: function(xhr) {
                        console.error('Error loading user details:', xhr);
                        showAlert('Gagal memuat data detail: ' + (xhr.responseJSON?.message ||
                            'Unknown error'), 'error');
                    }
                });
            });

            // Delete function
            window.deleteUser = function(id) {
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: "Data pengguna ini akan dihapus permanen!",
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
                            url: "{{ route('users.destroy', ['user' => '__ID__']) }}".replace(
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

            // FIXED SEARCH AND FILTER FUNCTIONS
            let searchTimeout;

            function performSearch() {
                const searchTerm = $('#searchInput').val().toLowerCase().trim();
                const roleFilter = $('#filterRole').val();
                const opdFilter = $('#filterOpd').val();

                let visibleCount = 0;
                const $rows = $('.user-row');

                $rows.each(function() {
                    const $row = $(this);
                    const name = $row.data('name') || '';
                    const email = $row.data('email') || '';
                    const roleId = String($row.data('role-id') || '');
                    const opdId = String($row.data('opd-id') || '');

                    let showRow = true;

                    // Search filter - check both name and email
                    if (searchTerm) {
                        const nameMatch = name.toString().toLowerCase().includes(searchTerm);
                        const emailMatch = email.toString().toLowerCase().includes(searchTerm);
                        showRow = nameMatch || emailMatch;
                    }

                    // Role filter
                    if (showRow && roleFilter) {
                        showRow = (roleId === roleFilter);
                    }

                    // OPD filter - handle both null values and actual IDs
                    if (showRow && opdFilter) {
                        if (opdFilter === 'null') {
                            showRow = (opdId === 'null' || opdId === '' || opdId === 'undefined');
                        } else {
                            showRow = (opdId === opdFilter);
                        }
                    }

                    if (showRow) {
                        $row.show();
                        visibleCount++;
                    } else {
                        $row.hide();
                    }
                });

                // Update search info
                updateSearchInfo(searchTerm, roleFilter, opdFilter, visibleCount);

                // Show/hide no results message
                if (visibleCount === 0 && (searchTerm || roleFilter || opdFilter)) {
                    $('#no-search-results').removeClass('d-none');
                    $('#no-data-row').addClass('d-none');
                } else {
                    $('#no-search-results').addClass('d-none');
                    if (visibleCount === 0 && !searchTerm && !roleFilter && !opdFilter) {
                        $('#no-data-row').removeClass('d-none');
                    } else {
                        $('#no-data-row').addClass('d-none');
                    }
                }

                // Update row numbers for visible rows
                updateRowNumbers();
            }

            function updateSearchInfo(searchTerm, roleFilter, opdFilter, visibleCount) {
                const totalRows = $('.user-row').length;
                let infoText = '';

                if (searchTerm || roleFilter || opdFilter) {
                    infoText = `Menampilkan ${visibleCount} dari ${totalRows} data pengguna`;

                    const filters = [];
                    if (searchTerm) filters.push(`pencarian: "${searchTerm}"`);
                    if (roleFilter) {
                        const roleName = $('#filterRole option:selected').text();
                        filters.push(`role: ${roleName}`);
                    }
                    if (opdFilter) {
                        const opdName = $('#filterOpd option:selected').text();
                        filters.push(`OPD: ${opdName}`);
                    }

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
                $('.user-row:visible').each(function() {
                    $(this).find('td:first-child').text(counter++);
                });
            }

            function resetAllFilters() {
                $('#searchInput').val('');
                $('#filterRole').val('');
                $('#filterOpd').val('');
                performSearch();
            }

            // Search event handlers
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            });

            $('#filterRole, #filterOpd').on('change', function() {
                performSearch();
            });

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
        /* Custom styles for User Management page */
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

        /* Password input group styling */
        .password-input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
            padding: 5px;
            font-size: 18px;
        }

        .password-toggle:hover {
            color: #495057;
        }

        .password-toggle:focus {
            outline: none;
            color: #495057;
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 5px;
        }

        .strength-bar {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
            margin-bottom: 5px;
            background-color: #e9ecef;
        }

        .strength-weak {
            background-color: #dc3545;
            width: 33%;
        }

        .strength-medium {
            background-color: #ffc107;
            width: 66%;
        }

        .strength-strong {
            background-color: #28a745;
            width: 100%;
        }

        .strength-text {
            font-size: 0.75rem;
            font-weight: 500;
        }

        .strength-text.weak {
            color: #dc3545;
        }

        .strength-text.medium {
            color: #ffc107;
        }

        .strength-text.strong {
            color: #28a745;
        }

        /* Adjust form control padding for password toggle */
        .password-input-group .form-control {
            padding-right: 40px;
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

        /* Status toggle styling */
        .form-check-input:checked {
            background-color: #28a745;
            border-color: #28a745;
        }

        .form-check-input:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        /* Badge styling */
        .badge {
            font-size: 0.75em;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
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

        /* User avatar styling */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Status badge styling */
        .status-badge {
            font-size: 0.75em;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
        }

        .status-active {
            background-color: #28a745;
            color: white;
        }

        .status-inactive {
            background-color: #6c757d;
            color: white;
        }

        /* Role badge colors */
        .role-admin {
            background-color: #dc3545 !important;
        }

        .role-user {
            background-color: #17a2b8 !important;
        }
    </style>
@endsection
