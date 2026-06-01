@extends('backend.partials.main', ['title' => 'Detail Fitur'])

@section('main')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-info text-white me-2">
            <i class="mdi mdi-map-marker-circle"></i>
        </span>
        Detail Fitur
    </h3>
    <nav aria-label="breadcrumb">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('layers.index', ['category' => $layer->category]) }}">Layer</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('layers.features.index', $layer) }}">{{ $layer->name }}</a>
            </li>
            <li class="breadcrumb-item active">Detail</li>
        </ul>
    </nav>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="mdi mdi-information me-2"></i>Informasi Fitur</h5>
                <a href="{{ route('layers.features.edit', [$layer, $feature]) }}"
                   class="btn btn-sm btn-outline-warning">
                    <i class="mdi mdi-pencil me-1"></i>Edit
                </a>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th style="width: 130px;">UUID</th>
                        <td><code>{{ $feature->uuid }}</code></td>
                    </tr>
                    <tr>
                        <th>Layer</th>
                        <td>{{ $layer->name }}</td>
                    </tr>
                    <tr>
                        <th>Tahun</th>
                        <td>{{ $feature->year ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Dibuat</th>
                        <td>{{ $feature->created_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>

                @if($feature->properties && count($feature->properties) > 0)
                <hr>
                <h6 class="mb-2">Properties</h6>
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Key</th><th>Value</th></tr></thead>
                    <tbody>
                        @foreach($feature->properties as $key => $val)
                        <tr>
                            <td class="font-monospace small">{{ $key }}</td>
                            <td>{{ is_array($val) ? json_encode($val) : $val }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="mdi mdi-image-multiple me-2"></i>
                    Gambar ({{ $feature->images->count() }})
                </h5>
            </div>
            <div class="card-body">
                @if($feature->images->count() > 0)
                <div class="row g-2 mb-3">
                    @foreach($feature->images as $img)
                    <div class="col-6">
                        <div class="position-relative">
                            <img src="{{ asset('storage/' . $img->file_path) }}"
                                 class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;"
                                 alt="{{ $img->caption }}">
                            @if($img->caption)
                            <div class="small text-muted mt-1">{{ $img->caption }}</div>
                            @endif
                            <form method="POST"
                                  action="{{ route('layers.features.images.destroy', [$layer, $feature, $img]) }}"
                                  class="position-absolute top-0 end-0 m-1"
                                  onsubmit="return confirm('Hapus gambar ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger py-0 px-1">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                <form method="POST"
                      action="{{ route('layers.features.images.store', [$layer, $feature]) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <input type="file" name="image" class="form-control form-control-sm"
                               accept="image/*" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="caption" class="form-control form-control-sm"
                               placeholder="Keterangan gambar (opsional)">
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                        <i class="mdi mdi-upload me-1"></i> Upload Gambar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<a href="{{ route('layers.features.index', $layer) }}" class="btn btn-light">
    <i class="mdi mdi-arrow-left me-1"></i> Kembali ke Daftar Fitur
</a>
@endsection
