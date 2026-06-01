@extends('backend.partials.main', ['title' => 'Fitur Layer: ' . $layer->name])

@push('styles')
<style>
    .layer-badge { display: inline-flex; align-items: center; gap: 6px; padding: .3rem .8rem; border-radius: 20px; font-weight: 600; font-size: .82rem; }
    .layer-badge.tematik    { background: #d1ecf1; color: #0c5460; }
    .layer-badge.psd        { background: #d4edda; color: #155724; }
    .layer-badge.psn        { background: #cce5ff; color: #004085; }
    .layer-badge.pokir      { background: #fff3cd; color: #856404; }
    .layer-badge.musrenbang { background: #fce4ec; color: #880e4f; }
</style>
@endpush

@section('main')
@php
    $categoryLabels = [
        'tematik'    => 'Peta Tematik',
        'psd'        => 'Proyek Strategis Daerah',
        'psn'        => 'Proyek Strategis Nasional',
        'pokir'      => 'Pokir DPRD',
        'musrenbang' => 'Usulan Musrenbang',
    ];
@endphp

<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-map-marker-multiple"></i>
        </span>
        Fitur — {{ $layer->name }}
        <span class="badge bg-secondary ms-2">{{ ucfirst($layer->type) }}</span>
    </h3>
    <nav aria-label="breadcrumb">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('layers.index', ['category' => $layer->category]) }}">
                    {{ $categoryLabels[$layer->category] ?? 'Layer' }}
                </a>
            </li>
            <li class="breadcrumb-item active">{{ $layer->name }}</li>
        </ul>
    </nav>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── TABEL FITUR ──────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <form method="GET" action="{{ route('layers.features.index', $layer) }}" class="d-flex gap-2">
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:120px;">
                        <option value="">Semua Tahun</option>
                        @foreach($availableYears as $year)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </form>
                <span class="text-muted small">Total: <strong>{{ $features->total() }}</strong> fitur</span>
            </div>
            <a href="{{ route('layers.features.create', $layer) }}" class="btn btn-sm btn-gradient-primary">
                <i class="mdi mdi-plus me-1"></i> Tambah Fitur
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width:100px;">UUID</th>
                        <th>Properties</th>
                        <th class="text-center" style="width:80px;">Tahun</th>
                        <th class="text-center" style="width:80px;">Gambar</th>
                        <th class="text-center" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($features as $feature)
                    <tr data-feature-id="{{ $feature->id }}">
                        <td>
                            <code class="small text-muted" title="{{ $feature->uuid }}">
                                {{ substr($feature->uuid, 0, 8) }}…
                            </code>
                        </td>
                        <td>
                            @if($feature->properties && count($feature->properties) > 0)
                                @php $props = array_slice($feature->properties, 0, 2, true); @endphp
                                @foreach($props as $key => $val)
                                    <span class="badge bg-light text-dark border me-1">
                                        {{ $key }}: {{ is_array($val) ? json_encode($val) : Str::limit($val, 22) }}
                                    </span>
                                @endforeach
                                @if(count($feature->properties) > 2)
                                    <span class="text-muted small">+{{ count($feature->properties) - 2 }} lainnya</span>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $feature->year ?? '—' }}</td>
                        <td class="text-center">
                            @php $imgCount = $feature->images_count ?? $feature->images->count(); @endphp
                            @if($imgCount > 0)
                                <span class="badge bg-success"><i class="mdi mdi-image me-1"></i>{{ $imgCount }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('layers.features.show', [$layer, $feature]) }}"
                               class="btn btn-sm btn-outline-info me-1" title="Detail">
                                <i class="mdi mdi-eye"></i>
                            </a>
                            <a href="{{ route('layers.features.edit', [$layer, $feature]) }}"
                               class="btn btn-sm btn-outline-warning me-1" title="Edit">
                                <i class="mdi mdi-pencil"></i>
                            </a>
                            <form method="POST"
                                  action="{{ route('layers.features.destroy', [$layer, $feature]) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('Hapus fitur ini beserta semua gambarnya?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="mdi mdi-map-marker-off mdi-48px d-block mb-2 opacity-50"></i>
                            Belum ada fitur di layer ini.
                            <a href="{{ route('layers.features.create', $layer) }}">Tambah fitur pertama</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $features->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
