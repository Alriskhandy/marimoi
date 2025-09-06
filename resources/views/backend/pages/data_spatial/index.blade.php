@extends('backend.partials.main', ['title' => 'Data Peta '])

@section('main')
    <!-- Data Table View -->
    <div id="tableView">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-map-marker-multiple"></i>
                </span>
                Data Spasial
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#!">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Data Spasial Peta Spatial<i
                            class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                @php
                                    // Deteksi type dan sub_type berdasarkan URL dan route
                                    $type = request()->get('type');
                                    $subType = request()->get('sub_type');
                                    $currentRoute = request()->route()->getName();
                                    $currentUrl = request()->url();

                                    // Jika tidak ada parameter, deteksi dari route name atau URL
                                    if (!$type) {
                                        if (
                                            str_contains($currentRoute, 'psn.') ||
                                            str_contains($currentUrl, 'proyek-strategis-nasional')
                                        ) {
                                            $type = 'proyek_strategis';
                                            $subType = 'psn';
                                        } elseif (
                                            str_contains($currentRoute, 'psd.') ||
                                            str_contains($currentUrl, 'proyek-strategis-daerah')
                                        ) {
                                            $type = 'proyek_strategis';
                                            $subType = 'psd';
                                        } elseif (
                                            str_contains($currentRoute, 'pokir') ||
                                            str_contains($currentUrl, 'pokir')
                                        ) {
                                            $type = 'pokir_dprd';
                                            $subType = null;
                                        } elseif (
                                            str_contains($currentRoute, 'usulan') ||
                                            str_contains($currentUrl, 'usulan')
                                        ) {
                                            $type = 'usulan_musrenbang';
                                            $subType = null;
                                        } else {
                                            $type = 'lokasi'; // default
                                            $subType = null;
                                        }
                                    }

                                    // Label tombol dinamis
                                    $typeLabels = [
                                        'lokasi' => 'Input Lokasi',
                                        'usulan_musrenbang' => 'Input Musrenbang',
                                        'pokir_dprd' => 'Input Pokir DPRD',
                                        'proyek_strategis' => match ($subType) {
                                            'psd' => 'Input PSD',
                                            'psn' => 'Input PSN',
                                            default => 'Input Proyek Strategis',
                                        },
                                    ];

                                    // Title dinamis
                                    $titles = [
                                        'lokasi' => 'Peta Tematik',
                                        'usulan_musrenbang' => 'Usulan Musrenbang',
                                        'pokir_dprd' => 'POKIR DPRD',
                                        'proyek_strategis' => match ($subType) {
                                            'psd' => 'Proyek Strategis Daerah',
                                            'psn' => 'Proyek Strategis Nasional',
                                            default => 'Proyek Strategis',
                                        },
                                    ];

                                    $label = $typeLabels[$type] ?? 'Input GIS';
                                    $title = $titles[$type] ?? 'Data Spasial';

                                    // Route dinamis
                                    $createUrl = route(
                                        'data-spatial.create',
                                        array_filter([
                                            'type' => $type,
                                            'sub_type' => $subType,
                                        ]),
                                    );
                                @endphp
                                <h4 class="card-title">Data Spasial {{ $title }} Maluku Utara</h4>
                                <p class="card-description">
                                    Kelola dan pantau data spasial untuk mendukung perencanaan pembangunan daerah
                                </p>
                            </div>
                            <div>
                                <a href="{{ $createUrl }}" class="btn btn-gradient-primary btn-rounded btn-fw me-2">
                                    <i class="mdi mdi-map-marker-plus"></i> {{ $label }}
                                </a>
                            </div>
                        </div>

                        <!-- Bulk Actions Bar -->
                        <div id="bulkActionsBar" class="alert alert-info d-none mb-3" role="alert">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="mdi mdi-checkbox-multiple-marked me-2"></i>
                                    <span id="selectedCount">0</span> item dipilih
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                                        <i class="mdi mdi-trash-can-outline me-1"></i>
                                        Hapus Terpilih
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick="clearSelection()">
                                        <i class="mdi mdi-close me-1"></i>
                                        Batal
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Search and Filter Controls -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <form method="GET" id="filterForm" class="d-flex align-items-center">
                                    <!-- Preserve existing parameters -->
                                    @if (request('type'))
                                        <input type="hidden" name="type" value="{{ request('type') }}">
                                    @endif
                                    @if (request('sub_type'))
                                        <input type="hidden" name="sub_type" value="{{ request('sub_type') }}">
                                    @endif
                                    @if (request('year'))
                                        <input type="hidden" name="year" value="{{ request('year') }}">
                                    @endif

                                    <label for="perPage" class="me-2">Tampilkan</label>
                                    <select name="per_page" id="perPage" class="form-select d-inline-block w-auto me-2"
                                        onchange="this.form.submit()">
                                        <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10
                                        </option>
                                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20
                                        </option>
                                        <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50
                                        </option>
                                        <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100
                                        </option>
                                    </select>
                                    <span>data per halaman</span>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <!-- Preserve existing parameters -->
                                    @if (request('type'))
                                        <input type="hidden" name="type" value="{{ request('type') }}">
                                    @endif
                                    @if (request('sub_type'))
                                        <input type="hidden" name="sub_type" value="{{ request('sub_type') }}">
                                    @endif
                                    @if (request('year'))
                                        <input type="hidden" name="year" value="{{ request('year') }}">
                                    @endif
                                    @if (request('per_page'))
                                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                    @endif

                                    <input type="text" name="search" class="form-control" placeholder="Cari data..."
                                        value="{{ request('search') }}" id="searchInput">
                                    <button type="submit" class="btn btn-outline-primary ms-2">
                                        <i class="mdi mdi-magnify"></i>
                                    </button>
                                    @if (request('search'))
                                        <a href="{{ request()->url() }}{{ request()->except('search') ? '?' . http_build_query(request()->except('search')) : '' }}"
                                            class="btn btn-outline-secondary ms-1" title="Clear search">
                                            <i class="mdi mdi-close"></i>
                                        </a>
                                    @endif
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive ps-3">
                            <table id="dataSpasialTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                                <label class="form-check-label" for="selectAll">
                                                    <span class="visually-hidden">Select All</span>
                                                </label>
                                            </div>
                                        </th>
                                        <th>No</th>
                                        <th>KODE</th>
                                        <th>Kategori</th>
                                        <th>Nama/Deskripsi</th>
                                        @php
                                            $hasTahun = $data->some(fn($item) => !empty($item->tahun));
                                        @endphp
                                        @if ($hasTahun)
                                            <th>Tahun</th>
                                        @endif
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data as $index => $item)
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input row-checkbox" type="checkbox"
                                                        value="{{ $item->id }}" id="check-{{ $item->id }}">
                                                    <label class="form-check-label" for="check-{{ $item->id }}">
                                                        <span class="visually-hidden">Select row</span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>{{ $data->firstItem() + $index }}</td>
                                            <td>{{ $item->uuid }}</td>
                                            <td>
                                                <label class="badge badge-gradient-info">
                                                    {{ $item->kategori->nama ?? 'Tidak ada kategori' }}
                                                </label>
                                            </td>
                                            <td style="word-wrap: break-word; white-space: normal; max-width: 300px;">
                                                <div>
                                                    <strong>{{ $item->title ?? ($item->deskripsi ?? 'Tanpa Nama') }}</strong>

                                                </div>
                                            </td>

                                            @if ($hasTahun)
                                                <td class="text-center">
                                                    {{ $item->tahun ?? '-' }}
                                                </td>
                                            @endif
                                            <td class="text-center">
                                                {{ $item->created_at ? $item->created_at->format('d M Y') : date('d M Y') }}
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <a href="{{ route('data-spatial.edit', $item->uuid) }}"
                                                        class="btn btn-sm btn-outline-warning" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>

                                                    <button type="button" class="btn btn-sm btn-outline-info"
                                                        onclick="showDetails('{{ $item->uuid }}')" title="Detail">
                                                        <i class="mdi mdi-eye"></i>
                                                    </button>

                                                    <form action="{{ route('data-spatial.destroy', $item->uuid) }}"
                                                        method="POST" style="display:inline-block;"
                                                        data-confirm="delete">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $hasTahun ? '8' : '7' }}" class="text-center py-4">
                                                @if (request('search'))
                                                    <i class="mdi mdi-magnify mdi-48px text-muted"></i>
                                                    <br>
                                                    <h5 class="text-muted mt-2">Tidak ada data yang sesuai dengan pencarian
                                                        "{{ request('search') }}"</h5>
                                                    <p class="text-muted">Coba gunakan kata kunci yang berbeda atau hapus
                                                        filter pencarian</p>
                                                @else
                                                    <i class="mdi mdi-database-remove mdi-48px text-muted"></i>
                                                    <br>
                                                    <h5 class="text-muted mt-2">Belum ada data spasial</h5>
                                                    <p class="text-muted">Klik tombol "{{ $label }}" untuk menambah
                                                        data baru</p>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Laravel Pagination -->
                            @if ($data->hasPages())
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted">
                                        Menampilkan {{ $data->firstItem() }} - {{ $data->lastItem() }} dari
                                        {{ $data->total() }} data
                                    </div>
                                    <div>
                                        {{ $data->appends(request()->query())->links('pagination::bootstrap-4') }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-primary text-white rounded-top-3 py-2">
                    <h6 class="modal-title fw-semibold" id="detailModalLabel">
                        <i class="fa fa-map-marker" style="margin-right: 6px;"></i>Detail Data Spasial
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" id="detailModalBody">
                    <div class="d-flex justify-content-center align-items-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-3 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times" style="margin-right: 5px;"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkDeleteModalLabel">
                        <i class="mdi mdi-alert-circle me-2"></i>Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="mdi mdi-trash-can-outline text-danger" style="font-size: 4rem;"></i>
                        <h5 class="mt-3">Apakah Anda yakin?</h5>
                        <p class="text-muted">
                            Anda akan menghapus <strong id="deleteCount">0</strong> data spasial yang dipilih.
                            <br>Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-close me-1"></i>Batal
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmBulkDelete">
                        <i class="mdi mdi-trash-can-outline me-1"></i>Hapus Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Form (Hidden) -->
    <form id="bulkDeleteForm" method="POST" action="{{ route('data-spatial.destroy', 'bulk-destroy') }}"
        style="display: none;">
        @csrf
        @method('DELETE')
        <div id="bulkDeleteIds"></div>
        <!-- Debug input -->
        <input type="hidden" name="debug_source" value="bulk_delete_form">
        <input type="hidden" name="current_url" value="{{ request()->fullUrl() }}">
    </form>
@endsection
@push('scripts')
    <script>
        // Bulk actions functionality
        let selectedItems = [];

        // Select All functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const isChecked = this.checked;

            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                if (isChecked && !selectedItems.includes(checkbox.value)) {
                    selectedItems.push(checkbox.value);
                } else if (!isChecked) {
                    selectedItems = selectedItems.filter(id => id !== checkbox.value);
                }
            });

            updateBulkActionsBar();
        });

        // Individual checkbox functionality
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('row-checkbox')) {
                const value = e.target.value;

                if (e.target.checked) {
                    if (!selectedItems.includes(value)) {
                        selectedItems.push(value);
                    }
                } else {
                    selectedItems = selectedItems.filter(id => id !== value);
                    document.getElementById('selectAll').checked = false;
                }

                updateBulkActionsBar();
                updateSelectAllState();
            }
        });

        // Update bulk actions bar visibility and count
        function updateBulkActionsBar() {
            const bulkActionsBar = document.getElementById('bulkActionsBar');
            const selectedCount = document.getElementById('selectedCount');

            if (selectedItems.length > 0) {
                bulkActionsBar.classList.remove('d-none');
                selectedCount.textContent = selectedItems.length;
            } else {
                bulkActionsBar.classList.add('d-none');
            }
        }

        // Update select all checkbox state
        function updateSelectAllState() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;

            if (checkedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === checkboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        // Clear selection
        function clearSelection() {
            selectedItems = [];
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            document.getElementById('selectAll').indeterminate = false;
            updateBulkActionsBar();
        }

        // Bulk delete functionality
        function bulkDelete() {
            if (selectedItems.length === 0) {
                alert('Silakan pilih data yang akan dihapus');
                return;
            }

            document.getElementById('deleteCount').textContent = selectedItems.length;
            const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
            modal.show();
        }

        // Confirm bulk delete
        document.getElementById('confirmBulkDelete').addEventListener('click', function() {
            // Debug log
            console.log('Selected items for deletion:', selectedItems);

            if (selectedItems.length === 0) {
                alert('Tidak ada data yang dipilih untuk dihapus');
                return;
            }

            // Create hidden inputs for selected IDs
            const bulkDeleteIds = document.getElementById('bulkDeleteIds');
            bulkDeleteIds.innerHTML = '';

            selectedItems.forEach((id, index) => {
                console.log(`Adding ID ${index + 1}:`, id);
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkDeleteIds.appendChild(input);
            });

            // Debug: Show form data before submit
            const formData = new FormData(document.getElementById('bulkDeleteForm'));
            console.log('Form data being submitted:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            // Submit the form
            document.getElementById('bulkDeleteForm').submit();
        });

        // Show details function
        function showDetails(id) {
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            const modalBody = document.getElementById('detailModalBody');

            // Show loading
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat detail data...</p>
                </div>
            `;

            modal.show();

            // Fetch data details
            fetch(`/dashboard/data-spatial/${id}/details`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalBody.innerHTML = `
                            <div class="row justify-content-center small">
                                <div class="card border-0 shadow-sm rounded-4 p-3 bg-light" style="max-width: 500px;">
                                    <h6 class="fw-semibold text-primary mb-3 border-bottom pb-1" style="font-size: 0.95rem;">
                                        <i class="fa fa-info-circle" style="margin-right: 6px;"></i>Informasi Dasar
                                    </h6>
                                    <table class="table table-sm table-borderless mb-3">
                                        <tr><td class="fw-semibold text-muted">Data Type:</td><td>${data.data.data_type}</td></tr>
                                        <tr><td class="fw-semibold text-muted">Sub Type:</td><td>${data.data.sub_type || '-'}</td></tr>
                                        <tr><td class="fw-semibold text-muted">Tahun:</td><td>${data.data.tahun || '-'}</td></tr>
                                        <tr><td class="fw-semibold text-muted">Kategori:</td><td>${data.data.kategori?.nama || '-'}</td></tr>
                                        <tr><td class="fw-semibold text-muted">Deskripsi:</td><td>${data.data.deskripsi || '-'}</td></tr>
                                    </table>
                                    <h6 class="fw-semibold text-primary mb-2 border-bottom pb-1" style="font-size: 0.95rem;">
                                        <i class="fa fa-database" style="margin-right: 6px;"></i>Atribut DBF
                                    </h6>
                                    <div style="max-height: 200px; overflow-y: auto;" class="border rounded-3 bg-white p-2">
                                        ${data.data.dbf_attributes ? 
                                            '<table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Atribut</th><th>Nilai</th></tr></thead><tbody>'+
                                            Object.entries(data.data.dbf_attributes).map(([key,value])=>`<tr><td class="fw-semibold">${key}</td><td>${value}</td></tr>`).join('')+
                                            '</tbody></table>' : 
                                            '<p class="text-muted mb-0">Tidak ada atribut DBF</p>'
                                        }
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="mdi mdi-alert-circle me-2"></i>
                                Gagal memuat detail data: ${data.message || 'Unknown error'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="mdi mdi-alert-circle me-2"></i>
                            Terjadi kesalahan saat memuat data
                        </div>
                    `;
                });
        }

        // Auto-submit search form with delay
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
@endpush
@push('styles')
    <style>
        .form-check-input {
            appearance: none;
            /* hilangkan style bawaan browser */
            -webkit-appearance: none;
            -moz-appearance: none;

            width: 18px;
            height: 18px;
            border: 2px solid #44444469;
            /* garis tegas */
            border-radius: 4px;
            /* kotak agak rounded */
            background-color: #fff;
            /* latar tetap putih */
            box-shadow: 0 0 4px rgba(0, 0, 0, 0);
            /* shadow halus */
            cursor: pointer;
            position: relative;
        }

        /* efek hover sebelum dicentang */
        .form-check-input:hover {
            border-color: #007bff;
            box-shadow: 0 0 6px rgba(0, 123, 255, 0.4);
        }

        /* saat dicentang */
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
            box-shadow: 0 0 6px rgba(0, 123, 255, 0.6);
        }

        /* bikin tanda centang custom */
        .form-check-input:checked::after {
            content: "✔";
            color: #fff;
            font-size: 14px;
            position: absolute;
            top: 0;
            left: 3px;
        }

        .table td {
            vertical-align: middle;
        }

        .badge {
            font-size: 0.75em;
        }

        .modal-body table {
            font-size: 0.9em;
        }

        .modal-body table td {
            padding: 0.25rem;
            border-top: 1px solid #dee2e6;
        }

        .modal-body table tr:first-child td {
            border-top: none;
        }

        .pagination {
            margin: 0;
        }

        .pagination .page-link {
            border: 1px solid #dee2e6;
            color: #4b4b4b;
            padding: 8px 12px;
            border-radius: 4px;
            background-color: #fff;
            transition: all 0.3s ease;
            margin: 0 2px;
        }

        .pagination .page-link:hover {
            background-color: #667eea;
            color: #fff;
            border-color: #667eea;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-color: transparent;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .pagination .page-item.disabled .page-link {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        /* Search input styling */
        #searchInput:focus {
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }

        /* Per page select styling */
        #perPage:focus {
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }

        /* Checkbox styling */
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .form-check-input:focus {
            border-color: #667eea;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Bulk actions bar */
        #bulkActionsBar {
            background-color: #e3f2fd;
            border-color: #2196f3;
            color: #1976d2;
        }

        /* Table row hover effect */
        .table tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }

        /* Custom checkbox indeterminate state */
        .form-check-input:indeterminate {
            background-color: #667eea;
            border-color: #667eea;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
        }
    </style>
@endpush
