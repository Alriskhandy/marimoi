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

                        <div class="table-responsive">
                            <table id="dataSpasialTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        @if ($data->isNotEmpty())
                                            <th style="width: 40px;">
                                                <div class="checkbox-wrapper">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                    <label class="form-check-label" for="selectAll">
                                                        <span class="visually-hidden">Select All</span>
                                                    </label>
                                                </div>
                                        @endif
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
                                                <div class="checkbox-wrapper">
                                                    <input class="form-check-input row-checkbox" type="checkbox"
                                                        value="{{ $item->id }}" id="check-{{ $item->id }}">
                                                    <label class="form-check-label" for="check-{{ $item->id }}">
                                                        <span class="visually-hidden">Select row</span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item->uuid }}</td>
                                            <td>
                                                <span class="badge bg-gradient-info text-white">
                                                    {{ $item->kategori->nama ?? 'Tidak ada kategori' }}
                                                </span>
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
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="{{ route('data-spatial.edit', $item->uuid) }}"
                                                        class="btn btn-sm btn-outline-warning" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>

                                                    <button type="button" class="btn btn-sm btn-outline-info"
                                                        onclick="showDetails('{{ $item->uuid }}')" title="Detail">
                                                        <i class="mdi mdi-eye"></i>
                                                    </button>

                                                    <form action="{{ route('data-spatial.destroy', $item->uuid) }}"
                                                        method="POST" style="display:inline-block;" data-confirm="delete">
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
                                                <i class="mdi mdi-database-remove mdi-48px text-muted"></i>
                                                <br>
                                                <h5 class="text-muted mt-2">Belum ada data spasial</h5>
                                                <p class="text-muted">Klik tombol "{{ $label }}" untuk menambah data
                                                    baru</p>
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

@push('styles')
    <link rel="stylesheet" href="{{ asset('datatables/datatables.css') }}">
    <style>
        /* ===========================================
                                               CHECKBOX STYLING - PERBAIKAN UTAMA
                                            =========================================== */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            padding: 8px;
        }

        .form-check-input {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #6c757d;
            border-radius: 4px;
            background-color: #fff;
            cursor: pointer;
            position: relative;
            margin: 0 !important;
            padding: 0 !important;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .form-check-input:hover {
            border-color: #007bff;
            box-shadow: 0 2px 6px rgba(0, 123, 255, 0.25);
            transform: translateY(-1px);
        }

        .form-check-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.4);
        }

        .form-check-input:checked::before {
            content: "✓";
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            line-height: 1;
        }

        /* Indeterminate state untuk select all */
        .form-check-input:indeterminate {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .form-check-input:indeterminate::before {
            content: "─";
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            line-height: 1;
        }

        /* ===========================================
                                               TABLE STYLING IMPROVEMENTS
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

        /* Badge styling */
        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8, #20c997);
        }

        /* Button group styling */
        .btn-group .btn {
            border-radius: 6px !important;
            margin: 0 2px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        /* ===========================================
                                               DATATABLE CUSTOM STYLING
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
                                               BULK ACTIONS BAR
                                            =========================================== */
        #bulkActionsBar {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border: 1px solid #2196f3;
            border-radius: 8px;
            color: #1976d2;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===========================================
                                               MODAL IMPROVEMENTS
                                            =========================================== */
        .modal-content {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
        }

        /* ===========================================
                                               RESPONSIVE IMPROVEMENTS
                                            =========================================== */
        @media (max-width: 768px) {
            .checkbox-wrapper {
                padding: 4px;
            }

            .form-check-input {
                width: 18px;
                height: 18px;
            }

            .btn-sm {
                padding: 4px 8px;
                font-size: 0.8rem;
            }

            .table th,
            .table td {
                padding: 8px 4px;
                font-size: 0.85rem;
            }
        }

        /* ===========================================
                                               LOADING STATES
                                            =========================================== */
        .table-loading {
            position: relative;
        }

        .table-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            z-index: 999;
        }

        /* ===========================================
                                               ACCESSIBILITY IMPROVEMENTS
                                            =========================================== */
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

        /* Focus indicators */
        .btn:focus,
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }
    </style>
@endpush

@push('scripts')
    <!-- jQuery harus pertama -->
    <script src="{{ asset('backend/assets/js/jquery-3.7.1.min.js') }}"></script>

    <!-- DataTables JS -->
    <script src="{{ asset('datatables/datatables.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable with improved configuration
            const table = $('#dataSpasialTable').DataTable({
                "processing": true,
                "pageLength": 10,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Semua"]
                ],
                "order": [
                    [1, 'asc']
                ],
                "columnDefs": [{
                        "orderable": false,
                        "targets": [0, -1] // Disable ordering on checkbox and action columns
                    },
                    {
                        "searchable": false,
                        "targets": [0, -1] // Disable search on checkbox and action columns
                    },
                    {
                        "className": "text-center",
                        "targets": [0, 1, -2, -1] // Center align for checkbox, no, date, action columns
                    }
                ],
                "language": {
                    "processing": "<div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div>",
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "zeroRecords": "Data tidak ditemukan",
                    "emptyTable": "Tidak ada data tersedia",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
                    "infoFiltered": "(difilter dari _MAX_ total data)",
                    "search": "Cari:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                },
                "dom": '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "drawCallback": function(settings) {
                    // Reinitialize checkbox events after table redraw
                    initializeCheckboxEvents();

                    // Add smooth animation to new rows
                    $(this).find('tbody tr').css('opacity', '0').animate({
                        'opacity': '1'
                    }, 300);
                },
                "initComplete": function() {
                    // Style the search input
                    $('.dataTables_filter input').attr('placeholder', 'Ketik untuk mencari...');
                }
            });

            // Initialize checkbox events
            function initializeCheckboxEvents() {
                // Clear previous event listeners to avoid duplicates
                $('#selectAll').off('change');
                $('.row-checkbox').off('change');

                // Select All functionality
                $('#selectAll').on('change', function() {
                    const isChecked = this.checked;
                    const visibleCheckboxes = table.$('.row-checkbox', {
                        "page": "current"
                    });

                    visibleCheckboxes.each(function() {
                        this.checked = isChecked;
                        const value = this.value;

                        if (isChecked && !selectedItems.includes(value)) {
                            selectedItems.push(value);
                        } else if (!isChecked) {
                            selectedItems = selectedItems.filter(id => id !== value);
                        }
                    });

                    updateBulkActionsBar();
                    updateSelectAllState();
                });

                // Individual checkbox functionality
                $('.row-checkbox').on('change', function() {
                    const value = this.value;

                    if (this.checked) {
                        if (!selectedItems.includes(value)) {
                            selectedItems.push(value);
                        }
                    } else {
                        selectedItems = selectedItems.filter(id => id !== value);
                    }

                    updateBulkActionsBar();
                    updateSelectAllState();
                });
            }

            // Initialize on page load
            initializeCheckboxEvents();
        });

        // Bulk actions functionality
        let selectedItems = [];

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
            const table = $('#dataSpasialTable').DataTable();
            const visibleCheckboxes = table.$('.row-checkbox', {
                "page": "current"
            });
            const selectAllCheckbox = document.getElementById('selectAll');
            let checkedCount = 0;

            visibleCheckboxes.each(function() {
                if (this.checked) checkedCount++;
            });

            if (checkedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === visibleCheckboxes.length) {
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
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = false;
                // Add smooth animation
                $(cb).closest('tr').removeClass('table-active');
            });
            document.getElementById('selectAll').checked = false;
            document.getElementById('selectAll').indeterminate = false;
            updateBulkActionsBar();
        }

        // Bulk delete functionality
        function bulkDelete() {
            if (selectedItems.length === 0) {
                // Enhanced alert with better UX
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak ada data terpilih',
                    text: 'Silakan pilih data yang akan dihapus terlebih dahulu',
                    confirmButtonText: 'OK'
                });
                return;
            }

            document.getElementById('deleteCount').textContent = selectedItems.length;
            const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
            modal.show();
        }

        // Confirm bulk delete
        document.getElementById('confirmBulkDelete').addEventListener('click', function() {
            if (selectedItems.length === 0) {
                alert('Tidak ada data yang dipilih untuk dihapus');
                return;
            }

            // Show loading state
            this.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>Menghapus...';
            this.disabled = true;

            // Create hidden inputs for selected IDs
            const bulkDeleteIds = document.getElementById('bulkDeleteIds');
            bulkDeleteIds.innerHTML = '';

            selectedItems.forEach((id, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkDeleteIds.appendChild(input);
            });

            // Submit the form
            document.getElementById('bulkDeleteForm').submit();
        });

        // Show details function with improved UX
        function showDetails(id) {
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            const modalBody = document.getElementById('detailModalBody');

            // Show loading with better animation
            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h6 class="text-muted">Memuat detail data...</h6>
                    <p class="text-muted small">Mohon tunggu sebentar</p>
                </div>
            `;

            modal.show();

            // Fetch data details with improved error handling
            fetch(`/dashboard/data-spatial/${id}/details`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        modalBody.innerHTML = `
                            <div class="container-fluid px-0">
                                <div class="card border-0 shadow-sm rounded-4 bg-light">
                                    <div class="card-header bg-primary text-white rounded-top-4 py-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="mdi mdi-information-outline me-2"></i>Informasi Dasar
                                        </h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Data Type:</small>
                                                <div class="fw-medium">${data.data.data_type || '-'}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Sub Type:</small>
                                                <div class="fw-medium">${data.data.sub_type || '-'}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Tahun:</small>
                                                <div class="fw-medium">${data.data.tahun || '-'}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Kategori:</small>
                                                <div class="fw-medium">${data.data.kategori?.nama || '-'}</div>
                                            </div>
                                            <div class="col-12">
                                                <small class="text-muted fw-semibold">Deskripsi:</small>
                                                <div class="fw-medium">${data.data.deskripsi || '-'}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card border-0 shadow-sm rounded-4 bg-light mt-3">
                                    <div class="card-header bg-info text-white rounded-top-4 py-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="mdi mdi-database me-2"></i>Atribut DBF
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div style="max-height: 300px; overflow-y: auto;">
                                            ${data.data.dbf_attributes ? 
                                                '<div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead class="table-dark sticky-top"><tr><th class="fw-semibold">Atribut</th><th class="fw-semibold">Nilai</th></tr></thead><tbody>'+
                                                Object.entries(data.data.dbf_attributes).map(([key,value])=>`<tr><td class="fw-medium text-primary">${key}</td><td>${value || '-'}</td></tr>`).join('')+
                                                '</tbody></table></div>' : 
                                                '<div class="text-center py-4"><i class="mdi mdi-database-remove text-muted" style="font-size: 3rem;"></i><p class="text-muted mb-0 mt-2">Tidak ada atribut DBF tersedia</p></div>'
                                            }
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div class="text-center py-5">
                            <i class="mdi mdi-alert-circle text-danger" style="font-size: 4rem;"></i>
                            <h5 class="text-danger mt-3">Terjadi Kesalahan</h5>
                            <p class="text-muted">
                                ${error.message || 'Gagal memuat detail data. Silakan coba lagi.'}
                            </p>
                            <button class="btn btn-outline-primary btn-sm" onclick="showDetails('${id}')">
                                <i class="mdi mdi-refresh me-1"></i>Coba Lagi
                            </button>
                        </div>
                    `;
                });
        }

        // Enhanced row selection visual feedback
        $(document).on('change', '.row-checkbox', function() {
            const row = $(this).closest('tr');
            if (this.checked) {
                row.addClass('table-active');
            } else {
                row.removeClass('table-active');
            }
        });

        // Add keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + A to select all visible rows
            if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                e.preventDefault();
                const selectAllCheckbox = document.getElementById('selectAll');
                if (selectAllCheckbox && !selectAllCheckbox.checked) {
                    selectAllCheckbox.click();
                }
            }

            // Delete key to bulk delete selected items
            if (e.key === 'Delete' && selectedItems.length > 0) {
                e.preventDefault();
                bulkDelete();
            }

            // Escape key to clear selection
            if (e.key === 'Escape' && selectedItems.length > 0) {
                e.preventDefault();
                clearSelection();
            }
        });

        // Add tooltips to action buttons
        $(document).ready(function() {
            $('[title]').tooltip();
        });

        // Enhanced form submission with confirmation
        $('form[data-confirm="delete"]').on('submit', function(e) {
            e.preventDefault();
            const form = this;

            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus data ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@endpush
