<!DOCTYPE html>
<html>

<head>
    <title>Daftar Lokasi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">Daftar Lokasi</h2>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lokasis as $lokasi)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $lokasi->kategori }}</td>
                        <td>{{ $lokasi->deskripsi }}</td>
                        <td>
                            <a href="{{ route('lokasi.edit', $lokasi->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('lokasi.destroy', $lokasi->id) }}" method="POST"
                                style="display:inline-block;" onsubmit="return confirm('Yakin ingin menghapus?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Tidak ada data lokasi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>

</html>
