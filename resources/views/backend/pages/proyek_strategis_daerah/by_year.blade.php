@extends('backend.partials.main', ['title' => 'Proyek Strategis Daerah Tahun ' . $year])

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
    </style>
@endpush
@section('main')
    <!-- Data Table View -->
    <div id="tableView">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-map-marker-multiple"></i>
                </span>
                Proyek Strategis Daerah Tahun {{ $year }}
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#!">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Data Spasial <i
                            class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Alert Messages -->
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
                                <h4 class="card-title">Data Spasial Proyek Strategis Daerah Tahun {{ $year }}</h4>
                                <p class="card-description">
                                    Kelola dan pantau Proyek Strategis Daerah untuk mendukung perencanaan pembangunan daerah
                                </p>
                            </div>
                            <div>
                                <a href="{{ route('psd.create') }}"
                                    class="btn btn-gradient-primary btn-rounded btn-fw me-2">
                                    <i class="mdi mdi-map-marker-plus"></i> Input GIS
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
                                    <input type="text" id="searchInput" class="form-control"
                                        placeholder="Cari data..." />
                                </div>
                            </div>


                            <table id="customTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th onclick="sortTable(0)">No</th>
                                        <th onclick="sortTable(1)">Kategori</th>
                                        <th>Nama/Deskripsi</th>
                                        <th onclick="sortTable(3)">Data Tahun</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="dataRows">
                                    @forelse($lokasis as $lokasi)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><span class="badge bg-info">{{ $lokasi->kategori->nama }}</span></td>
                                            <td>
                                                @if ($lokasi->deskripsi)
                                                    <small
                                                        class="text-muted">{{ Str::limit($lokasi->deskripsi, 50) }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $lokasi->tahun }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('psd.edit', $lokasi->id) }}"
                                                    class="btn btn-sm btn-outline-warning">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <form action="{{ route('psd.destroy', $lokasi->id) }}" method="POST"
                                                    style="display:inline-block;" data-confirm="delete">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Tidak ada data ditemukan.</td>
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
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tableBody = document.getElementById("dataRows");
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

                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                pagination.innerHTML = "";
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

            function sortTable(colIndex) {
                originalRows.sort((a, b) => {
                    const aText = a.children[colIndex].innerText.trim();
                    const bText = b.children[colIndex].innerText.trim();
                    return aText.localeCompare(bText, 'id', {
                        numeric: true
                    });
                });
                updateTable();
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

            window.sortTable = sortTable;

            updateTable(); // inisialisasi pertama
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const table = document.getElementById("customTable");
            const searchInput = document.getElementById("searchInput");
            const tableBody = document.getElementById("dataRows");
            const pagination = document.getElementById("pagination");
            const rowsPerPage = 5;
            let currentPage = 1;

            // Simpan semua baris awal (original) agar bisa di-reset saat refresh
            const originalRows = Array.from(tableBody.querySelectorAll("tr"));

            function updateTable() {
                const search = searchInput.value.toLowerCase();

                const filteredRows = originalRows.filter(row => {
                    return row.innerText.toLowerCase().includes(search);
                });

                const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
                currentPage = Math.min(currentPage, totalPages) || 1;

                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                tableBody.innerHTML = "";
                filteredRows.slice(start, end).forEach(row => {
                    tableBody.appendChild(row.cloneNode(true));
                });

                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                pagination.innerHTML = "";
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

            function sortTable(colIndex) {
                originalRows.sort((a, b) => {
                    const aText = a.children[colIndex].innerText.trim();
                    const bText = b.children[colIndex].innerText.trim();
                    return aText.localeCompare(bText, 'id', {
                        numeric: true
                    });
                });
                updateTable(); // langsung refresh setelah sort
            }

            // Event pencarian
            searchInput.addEventListener("input", () => {
                currentPage = 1;
                updateTable();
            });

            // Jalankan pertama kali
            updateTable();

            // Ekspos global agar fungsi onclick di header kolom bisa jalan
            window.sortTable = sortTable;
        });
    </script>
@endsection
