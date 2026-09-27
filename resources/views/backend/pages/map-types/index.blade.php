@extends('backend.partials.main', ['title' => 'Jenis Peta'])

@section('main')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-map-marker-radius"></i>
            </span>
            Jenis Peta
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Jenis Peta</li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="card-title mb-0">Master jenis peta (dipakai sebagai <code>map_type_id</code> pada Layer)</p>
                        <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="mdi mdi-plus"></i> Tambah Jenis Peta
                        </button>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Urutan</th>
                                    <th>Slug</th>
                                    <th>Nama</th>
                                    <th>Jumlah Layer</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mapTypes as $mapType)
                                    <tr>
                                        <td>{{ $mapType->urutan }}</td>
                                        <td><code>{{ $mapType->slug }}</code></td>
                                        <td>{{ $mapType->nama }}</td>
                                        <td>{{ $mapType->spatial_layers_count }}</td>
                                        <td>
                                            <span class="badge {{ $mapType->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $mapType->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#editModal"
                                                data-id="{{ $mapType->id }}"
                                                data-slug="{{ $mapType->slug }}"
                                                data-nama="{{ $mapType->nama }}"
                                                data-deskripsi="{{ $mapType->deskripsi }}"
                                                data-icon="{{ $mapType->icon }}"
                                                data-urutan="{{ $mapType->urutan }}"
                                                data-is-active="{{ $mapType->is_active ? 1 : 0 }}">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <form action="{{ route('map-types.destroy', $mapType) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Hapus jenis peta ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Belum ada jenis peta.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('map-types.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="addModalLabel">Tambah Jenis Peta</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @include('backend.pages.map-types._form')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title" id="editModalLabel">Ubah Jenis Peta</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @include('backend.pages.map-types._form')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('editModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const form = document.getElementById('editForm');
            form.action = '{{ route('map-types.update', ':id') }}'.replace(':id', button.dataset.id);

            form.querySelector('[name="slug"]').value = button.dataset.slug || '';
            form.querySelector('[name="nama"]').value = button.dataset.nama || '';
            form.querySelector('[name="deskripsi"]').value = button.dataset.deskripsi || '';
            form.querySelector('[name="icon"]').value = button.dataset.icon || '';
            form.querySelector('[name="urutan"]').value = button.dataset.urutan || 0;
            form.querySelector('[name="is_active"]').checked = button.dataset.isActive === '1';
        });
    </script>
@endsection
