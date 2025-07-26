@extends('backend.partials.main', ['title' => 'Data Usulan Musrenbang'])


@section('main')
    <!-- Data Table View -->
    <div id="tableView">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-account-group"></i>
                </span>
                Data Usulan Musrenbang
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#!">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span></span>Data Usulan Musrenbang<i
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
                                <h4 class="card-title">Data Usulan Musrenbang Maluku Utara</h4>
                                <p class="card-description">
                                    Kelola dan pantau Usulan Musrenbang
                                </p>
                            </div>
                            <div>
                                <a href="{{ route('usulan-musrenbang.create') }}"
                                    class="btn btn-gradient-primary btn-rounded btn-fw me-2">
                                    <i class="mdi mdi-plus"></i> Tambah Data Musrenbang
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
                                        placeholder="Cari musrenbang...">
                                </div>
                            </div>

                            <table id="musrenbangTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Judul/deskripsi</th>
                                        <th>Kategori</th>
                                        <th>Dibuat Pada</th> {{-- Tambahan --}}
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($usulanMusrenbang as $musrenbang)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <div>
                                                    <strong>{{ $musrenbang->judul }}</strong>
                                                    @if ($musrenbang->deskripsi)
                                                        <br><small
                                                            class="text-muted">{{ Str::limit($musrenbang->deskripsi, 50) }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <label
                                                    class="badge badge-gradient-info">{{ $musrenbang->kategori->nama ?? 'Tanpa Kategori' }}</label>
                                            </td>
                                            <td class="text-center">
                                                {{ $musrenbang->created_at
                                                    ? $musrenbang->created_at->setTimezone('Asia/Jayapura')->format('d M Y H:i') . ' WIT'
                                                    : '-' }}
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('usulan-musrenbang.edit', $musrenbang->id) }}"
                                                        class="btn btn-sm btn-outline-warning" title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('usulan-musrenbang.destroy', $musrenbang->id) }}"
                                                        method="POST" style="display:inline-block;" data-confirm="delete">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="mdi mdi-account-group-outline mdi-48px text-muted"></i>
                                                <br>
                                                <h5 class="text-muted mt-2">Belum ada data Usulan Musrenbang</h5>
                                                <p class="text-muted">Klik tombol "Tambah musrenbang" untuk menambah data
                                                    baru</p>
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tableBody = document.querySelector("#musrenbangTable tbody");
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

                renderPagination(totalPages, filteredRows.length);
            }

            function renderPagination(totalPages, totalFiltered) {
                pagination.innerHTML = "";

                if (totalFiltered <= 10) {
                    pagination.style.display = "none";
                    return;
                }

                pagination.style.display = "flex";

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

            // Event listeners
            searchInput.addEventListener("input", () => {
                currentPage = 1;
                updateTable();
            });

            rowsPerPageSelect.addEventListener("change", () => {
                currentPage = 1;
                updateTable();
            });

            updateTable(); // inisialisasi awal
        });
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
    </style>
@endpush
