@extends('backend.partials.main', ['title' => 'Kelola Peta'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-layers"></i>
            </span>
            Kelola Peta
        </h3>
        <a href="{{ route('maps.create') }}" class="btn btn-gradient-primary">
            <i class="mdi mdi-plus"></i> Buat Peta
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Visibilitas</th>
                            <th>Jumlah Layer</th>
                            <th>Terakhir Terbit</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($maps as $map)
                            <tr>
                                <td>{{ $map->title }}</td>
                                <td>
                                    <span class="badge bg-{{ $map->visibility === 'public' ? 'success' : 'secondary' }} text-white">
                                        {{ ucfirst($map->visibility) }}
                                    </span>
                                </td>
                                <td>{{ $map->layers_count }}</td>
                                <td>{{ $map->published_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('maps.edit', $map) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-pencil"></i> Kelola
                                    </a>
                                    <form action="{{ route('maps.destroy', $map) }}" method="POST" style="display:inline-block" data-confirm="delete" data-name="{{ $map->title }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="mdi mdi-delete"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">Belum ada peta. Klik "Buat Peta" untuk mulai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
