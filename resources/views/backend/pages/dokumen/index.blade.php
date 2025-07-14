@extends('backend.partials.main', ['title' => 'Daftar Dokumen'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-file-document"></i>
            </span> Daftar Dokumen
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Dokumen
                </li>
            </ul>
        </nav>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title">Daftar Dokumen</h4>
                <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="mdi mdi-plus"></i> Tambah Dokumen
                </button>
            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover" id="dokumenTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse($dokumens as $dokumen)
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td>{{ $dokumen->nama }}</td>
                                <td>
                                    <!-- Edit Button triggers modal -->
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $dokumen->id }}" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <form action="{{ route('dokumen.destroy', $dokumen->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin ingin menghapus dokumen ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </form>
                                    <a href="{{ asset('storage/' . $dokumen->file) }}" class="btn btn-sm btn-outline-info" title="Download" download><i class="mdi mdi-download"></i></a>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal{{ $dokumen->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $dokumen->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-md">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel{{ $dokumen->id }}">
                                                <i class="mdi mdi-pencil"></i> Edit Dokumen
                                            </h5>
                                            <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('dokumen.update', $dokumen->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="form-group mb-3">
                                                    <label for="edit_nama_{{ $dokumen->id }}" class="form-label">Nama Dokumen <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="edit_nama_{{ $dokumen->id }}" name="nama" value="{{ $dokumen->nama }}" required>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                                <div class="form-group mb-3">
                                                    <label for="edit_file_{{ $dokumen->id }}" class="form-label">File Dokumen</label>
                                                    <input type="file" class="form-control" id="edit_file_{{ $dokumen->id }}" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                                                    <small class="text-muted">Maksimal 10MB (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX)</small>
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
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">Belum ada dokumen yang dibuat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="mdi mdi-plus"></i> Tambah Dokumen
                    </h5>
                    <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addForm" action="{{ route('dokumen.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="add_nama" class="form-label">Nama Dokumen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_nama" name="nama" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="add_file" class="form-label">File Dokumen <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="add_file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                            <small class="text-muted">Maksimal 10MB (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX)</small>
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
@endsection

@push('styles')
    <style>
        #dokumenTable th,
        #dokumenTable td {
            vertical-align: middle;
        }
    </style>
@endpush
