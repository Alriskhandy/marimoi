@extends('backend.partials.main', ['title' => 'Riwayat Impor'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-history"></i></span>
            Riwayat Impor: {{ $layer->name }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('spatial-layers.index') }}">Daftar Layer & Data</a></li>
                <li class="breadcrumb-item"><a href="{{ route('spatial-layers.show', $layer) }}">{{ $layer->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Riwayat Impor</li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">
                        Satu-satunya riwayat perubahan data layer ini (tidak ada versi/rollback terpisah) —
                        setiap impor file (Shapefile/KMZ/KML) tercatat di sini. Input koordinat manual tidak tercatat di sini.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>File</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                    <th>Hasil</th>
                                    <th>Oleh</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($imports as $import)
                                    @php
                                        $statusBadge = ['completed' => 'success', 'failed' => 'danger', 'processing' => 'info', 'pending' => 'secondary'][$import->status] ?? 'secondary';
                                    @endphp
                                    <tr>
                                        <td><small>{{ $import->created_at?->format('d/m/Y H:i') }}</small></td>
                                        <td>
                                            {{ $import->original_filename }}
                                            <br><small class="text-muted">{{ strtoupper($import->file_format) }} · {{ number_format(($import->file_size_bytes ?? 0) / 1024, 1) }} KB</small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ ucfirst($import->import_mode) }}</span></td>
                                        <td><span class="badge bg-{{ $statusBadge }} text-white">{{ ucfirst($import->status) }}</span></td>
                                        <td>
                                            <small>
                                                Berhasil: <strong class="text-success">{{ $import->imported_features }}</strong>
                                                / Gagal: <strong class="text-danger">{{ $import->failed_features }}</strong>
                                                @if ($import->total_features !== null)
                                                    / Total: {{ $import->total_features }}
                                                @endif
                                            </small>
                                            @if ($import->error_message)
                                                <br><small class="text-danger">{{ \Illuminate\Support\Str::limit($import->error_message, 80) }}</small>
                                            @endif
                                        </td>
                                        <td><small>{{ $import->importedBy?->name ?? '-' }}</small></td>
                                        <td>
                                            <a href="{{ route('spatial-layers.imports.log', [$layer, $import]) }}" class="btn btn-sm btn-outline-secondary" title="Unduh Log">
                                                <i class="mdi mdi-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            Belum ada riwayat impor file untuk Layer ini.
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
@endsection
