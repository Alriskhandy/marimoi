@extends('backend.partials.main')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
@endpush

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
                                <h4 class="card-title">Data Spasial Maluku Utara</h4>
                                <p class="card-description">
                                    Kelola dan pantau data spasial untuk mendukung perencanaan pembangunan daerah
                                </p>
                            </div>
                            <div>
                                <a href="{{ route('lokasi.create') }}"
                                    class="btn btn-gradient-primary btn-rounded btn-fw me-2">
                                    <i class="mdi mdi-map-marker-plus"></i> Input GIS
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="dataSpasialTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kategori</th>
                                        <th>Nama/Deskripsi</th>
                                        {{-- <th>Status</th> --}}
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>


                                    @forelse($lokasis as $lokasi)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <label
                                                    class="badge badge-gradient-info">{{ $lokasi->kategori->nama }}</label>

                                            </td>
                                            <td>
                                                <div>
                                                    {{-- <strong>{{ $lokasi->nama ?? 'Tanpa Nama' }}</strong> --}}
                                                    @if ($lokasi->deskripsi)
                                                        <br><small
                                                            class="text-muted">{{ Str::limit($lokasi->deskripsi, 50) }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            {{-- <td class="text-center">
                                                @if (($lokasi->status ?? 'aktif') == 'aktif')
                                                    <label class="badge badge-gradient-success">Aktif</label>
                                                @else
                                                    <label class="badge badge-gradient-secondary">Tidak Aktif</label>
                                                @endif
                                            </td> --}}
                                            <td class="text-center">
                                                {{ $lokasi->created_at ? $lokasi->created_at->format('d M Y') : date('d M Y') }}
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('lokasi.edit', $lokasi->id) }}"
                                                    class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <form action="{{ route('lokasi.destroy', $lokasi->id) }}" method="POST"
                                                    style="display:inline-block;"
                                                    onsubmit="return confirm('Yakin ingin menghapus {{ $lokasi->nama ?? $lokasi->kategori }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="mdi mdi-database-remove mdi-48px text-muted"></i>
                                                <br>
                                                <h5 class="text-muted mt-2">Belum ada data spasial</h5>
                                                <p class="text-muted">Klik tombol "Tambah Data" untuk menambah data baru</p>
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


    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#dataSpasialTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'excel',
                        text: '<i class="mdi mdi-file-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        exportOptions: {
                            columns: [0, 1, 2, 4, 5]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="mdi mdi-file-pdf me-1"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        exportOptions: {
                            columns: [0, 1, 2, 4, 5]
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="mdi mdi-printer me-1"></i> Print',
                        className: 'btn btn-info btn-sm',
                        exportOptions: {
                            columns: [0, 1, 2, 4, 5]
                        }
                    }
                ],
                language: {
                    "sProcessing": "Sedang memproses...",
                    "sLengthMenu": "Tampilkan _MENU_ data",
                    "sZeroRecords": "Tidak ditemukan data yang sesuai",
                    "sInfo": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    "sInfoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
                    "sInfoFiltered": "(disaring dari _MAX_ data keseluruhan)",
                    "sSearch": "Cari:",
                    "oPaginate": {
                        "sFirst": "Pertama",
                        "sPrevious": "Sebelumnya",
                        "sNext": "Selanjutnya",
                        "sLast": "Terakhir"
                    }
                },
                columnDefs: [{
                        targets: [0, 3, 4, 5, 6],
                        className: 'text-center'
                    },
                    {
                        targets: [6],
                        orderable: false
                    }
                ],
                order: [
                    [0, 'asc']
                ]
            });

            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
@endsection
