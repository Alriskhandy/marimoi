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

                        <div class="table-responsive">
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
                                    <input type="text" id="searchInput" class="form-control" placeholder="Cari data...">
                                </div>
                            </div>

                            <table id="dataSpasialTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
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
                                    @forelse($data as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <label class="badge badge-gradient-info">
                                                    {{ $item->kategori->nama ?? 'Tidak ada kategori' }}
                                                </label>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $item->title ?? ($item->deskripsi ?? 'Tanpa Nama') }}</strong>
                                                    @if ($item->deskripsi && $item->title !== $item->deskripsi)
                                                        <br><small
                                                            class="text-muted">{{ Str::limit($item->deskripsi, 50) }}</small>
                                                    @endif
                                                    @if ($item->dbf_attributes && is_array($item->dbf_attributes))
                                                        @php
                                                            $nameFields = ['NAMA', 'NAME', 'NAMA_OBJEK', 'NAMOBJ'];
                                                            $displayName = null;
                                                            foreach ($nameFields as $field) {
                                                                if (
                                                                    isset($item->dbf_attributes[$field]) &&
                                                                    !empty($item->dbf_attributes[$field])
                                                                ) {
                                                                    $displayName = $item->dbf_attributes[$field];
                                                                    break;
                                                                }
                                                            }
                                                        @endphp
                                                        @if ($displayName && $displayName !== ($item->title ?? $item->deskripsi))
                                                            <br><small class="text-info"><i class="mdi mdi-database"></i>
                                                                {{ $displayName }}</small>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                            @if ($item->tahun)
                                                <td class="text-center">
                                                    {{ $item->tahun }}
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
                                            <td colspan="6" class="text-center py-4">
                                                <i class="mdi mdi-database-remove mdi-48px text-muted"></i>
                                                <br>
                                                <h5 class="text-muted mt-2">Belum ada data spasial</h5>
                                                <p class="text-muted">Klik tombol "Input GIS" untuk menambah data baru</p>
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
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-primary text-white rounded-top-3 py-2">
                    <h6 class="modal-title fw-semibold" id="detailModalLabel"><i class="fa fa-map-marker"
                            style="margin-right: 6px;"></i>Detail Data Spasial</h6>
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tableBody = document.querySelector("#dataSpasialTable tbody");
            const pagination = document.getElementById("pagination");
            const searchInput = document.getElementById("searchInput");
            const rowsPerPageSelect = document.getElementById("rowsPerPageSelect");

            let currentPage = 1;
            let rowsPerPage = parseInt(rowsPerPageSelect.value);

            const originalRows = Array.from(tableBody.querySelectorAll("tr"));

            function updateTable() {
                const search = searchInput.value.toLowerCase();
                rowsPerPage = parseInt(rowsPerPageSelect.value);

                const filteredRows = originalRows.filter(row => {
                    // Skip empty state row
                    if (row.cells.length === 1 && row.cells[0].colSpan > 1) {
                        return false;
                    }
                    return row.innerText.toLowerCase().includes(search);
                });

                const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
                currentPage = Math.min(currentPage, totalPages) || 1;

                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                // Clear table body
                tableBody.innerHTML = "";

                if (filteredRows.length === 0) {
                    // Show no results message
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.innerHTML = `
                        <td colspan="6" class="text-center py-4">
                            <i class="mdi mdi-magnify mdi-48px text-muted"></i>
                            <br>
                            <h5 class="text-muted mt-2">Tidak ada data yang sesuai</h5>
                            <p class="text-muted">Coba gunakan kata kunci pencarian yang berbeda</p>
                        </td>
                    `;
                    tableBody.appendChild(noResultsRow);
                } else {
                    // Show filtered results
                    filteredRows.slice(start, end).forEach((row, index) => {
                        const newRow = row.cloneNode(true);
                        // Update row number
                        newRow.cells[0].textContent = start + index + 1;
                        tableBody.appendChild(newRow);
                    });
                }

                renderPagination(totalPages, filteredRows.length);
            }

            function renderPagination(totalPages, totalFiltered) {
                pagination.innerHTML = "";

                if (totalFiltered <= rowsPerPage) {
                    pagination.style.display = "none";
                    return;
                }

                pagination.style.display = "flex";

                // Previous button
                if (currentPage > 1) {
                    const prevLi = document.createElement("li");
                    prevLi.classList.add("page-item");
                    prevLi.innerHTML =
                        `<a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>`;
                    prevLi.addEventListener("click", function(e) {
                        e.preventDefault();
                        currentPage--;
                        updateTable();
                    });
                    pagination.appendChild(prevLi);
                }

                // Page numbers
                const startPage = Math.max(1, currentPage - 2);
                const endPage = Math.min(totalPages, currentPage + 2);

                for (let i = startPage; i <= endPage; i++) {
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

                // Next button
                if (currentPage < totalPages) {
                    const nextLi = document.createElement("li");
                    nextLi.classList.add("page-item");
                    nextLi.innerHTML =
                        `<a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>`;
                    nextLi.addEventListener("click", function(e) {
                        e.preventDefault();
                        currentPage++;
                        updateTable();
                    });
                    pagination.appendChild(nextLi);
                }
            }

            // Event listeners
            searchInput.addEventListener("input", () => {
                currentPage = 1;
                updateTable();
            });

            rowsPerPageSelect.addEventListener("change", () => {
                currentPage = 1;
                updateTable();
            });

            updateTable(); // Initial load
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

            // Fetch data details (you can implement this endpoint in your controller)
            fetch(`/dashboard/data-spatial/${id}/details`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalBody.innerHTML = `
                <div class="row justify-content-center small"><div class="card border-0 shadow-sm rounded-4 p-3 bg-light" style="max-width: 500px;"><h6 class="fw-semibold text-primary mb-3 border-bottom pb-1" style="font-size: 0.95rem;"><i class="fa fa-info-circle" style="margin-right: 6px;"></i>Informasi Dasar</h6><table class="table table-sm table-borderless mb-3"><tr><td class="fw-semibold text-muted">Data Type:</td><td>${data.data.data_type}</td></tr><tr><td class="fw-semibold text-muted">Sub Type:</td><td>${data.data.sub_type || '-'}</td></tr><tr><td class="fw-semibold text-muted">Tahun:</td><td>${data.data.tahun || '-'}</td></tr><tr><td class="fw-semibold text-muted">Kategori:</td><td>${data.data.kategori?.nama || '-'}</td></tr><tr><td class="fw-semibold text-muted">Deskripsi:</td><td>${data.data.deskripsi || '-'}</td></tr></table><h6 class="fw-semibold text-primary mb-2 border-bottom pb-1" style="font-size: 0.95rem;"><i class="fa fa-database" style="margin-right: 6px;"></i>Atribut DBF</h6><div style="max-height: 200px; overflow-y: auto;" class="border rounded-3 bg-white p-2">${data.data.dbf_attributes ? '<table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Atribut</th><th>Nilai</th></tr></thead><tbody>'+Object.entries(data.data.dbf_attributes).map(([key,value])=>`<tr><td class="fw-semibold">${key}</td><td>${value}</td></tr>`).join('')+'</tbody></table>' : '<p class="text-muted mb-0">Tidak ada atribut DBF</p>'}</div></div></div>


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
    </script>
@endsection

@push('styles')
    <style>
        #rowsPerPageSelect:focus {
            box-shadow: none;
            border-color: #764ba2;
        }

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

        .btn-group .btn {
            margin-right: 2px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
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
    </style>
@endpush
