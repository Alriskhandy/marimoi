@php
    $layer = $layer ?? null;
    $opds = $opds ?? collect();
    $layerTypes = $layerTypes ?? collect();
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Nama</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $layer?->name) }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Jenis Peta</label>
        <select name="map_type_id" class="form-select" required>
            <option value="">Pilih Jenis</option>
            @foreach ($mapTypes as $mapType)
                <option value="{{ $mapType->id }}" @selected(old('map_type_id', $layer?->map_type_id) == $mapType->id)>{{ $mapType->nama }}</option>
            @endforeach
        </select>
        @error('map_type_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Jenis Layer</label>
        <select name="layer_type_id" class="form-select">
            @foreach ($layerTypes as $layerType)
                <option value="{{ $layerType->id }}" @selected(old('layer_type_id', $layer?->layer_type_id ?? 4) == $layerType->id)>{{ $layerType->name }}</option>
            @endforeach
        </select>
        @error('layer_type_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Visibility</label>
        <select name="visibility" class="form-select">
            @foreach (['public' => 'Publik', 'internal' => 'Internal', 'private' => 'Privat'] as $value => $label)
                <option value="{{ $value }}" @selected(old('visibility', $layer?->visibility ?? 'public') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('visibility') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">OPD Pemilik</label>
    @if ($opds->isNotEmpty())
        <select name="opd_id" class="form-select">
            <option value="">-- Provinsi/Bappeda --</option>
            @foreach ($opds as $opd)
                <option value="{{ $opd->id }}" @selected(old('opd_id', $layer?->opd_id) == $opd->id)>{{ $opd->singkatan }} - {{ $opd->name }}</option>
            @endforeach
        </select>
    @else
        <input type="text" class="form-control" value="{{ Auth::user()->opd?->name ?? '-' }}" disabled>
        <div class="form-text">OPD pemilik otomatis mengikuti OPD Anda.</div>
    @endif
    @error('opd_id') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

@include('backend.pages.spatial-layers._category-picker', [
    'selectedCategoryId' => $layer?->category_id ?? request('category_id'),
    'selectedCategoryNodeId' => $layer?->category_node_id ?? request('category_node_id'),
])

<div class="mb-3">
    <label class="form-label">Deskripsi</label>
    <textarea name="short_description" class="form-control" rows="2">{{ old('short_description', $layer?->short_description) }}</textarea>
</div>

<hr>
<h6>Style</h6>
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Warna</label>
        <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', $layer?->color ?? '#0d6efd') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Icon</label>
        <input type="text" name="icon" class="form-control" placeholder="mis. mdi mdi-road" value="{{ old('icon', $layer?->icon) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Opacity</label>
        <input type="number" name="default_opacity" class="form-control" step="0.1" min="0" max="1" value="{{ old('default_opacity', $layer?->opacity ?? 1) }}">
    </div>
    <div class="col-md-3 mb-3 form-check mt-4">
        <input type="checkbox" name="is_marker" value="1" class="form-check-input" @checked(old('is_marker', $layer?->is_marker))>
        <label class="form-check-label">Tampil sebagai marker</label>
    </div>
</div>

<hr>
<h6>Properti Tampilan Peta</h6>
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Min Zoom</label>
        <input type="number" name="min_zoom" class="form-control" min="0" max="24" value="{{ old('min_zoom', $layer?->min_zoom) }}">
        @error('min_zoom') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Max Zoom</label>
        <input type="number" name="max_zoom" class="form-control" min="0" max="24" value="{{ old('max_zoom', $layer?->max_zoom) }}">
        @error('max_zoom') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Urutan Tampil</label>
        <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', $layer?->sort_order ?? 0) }}">
        @error('sort_order') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-4 form-check">
        <input type="hidden" name="is_default_on" value="0">
        <input type="checkbox" name="is_default_on" value="1" class="form-check-input" @checked(old('is_default_on', $layer?->is_default_on ?? false))>
        <label class="form-check-label">Aktif otomatis saat peta dibuka</label>
    </div>
    <div class="col-md-4 form-check">
        <input type="hidden" name="is_queryable" value="0">
        <input type="checkbox" name="is_queryable" value="1" class="form-check-input" @checked(old('is_queryable', $layer?->is_queryable ?? true))>
        <label class="form-check-label">Bisa diklik untuk info (queryable)</label>
    </div>
    <div class="col-md-4 form-check">
        <input type="hidden" name="is_downloadable" value="0">
        <input type="checkbox" name="is_downloadable" value="1" class="form-check-input" @checked(old('is_downloadable', $layer?->is_downloadable ?? false))>
        <label class="form-check-label">Bisa diunduh publik</label>
    </div>
</div>

@can('spatial-layers.publish')
    <div class="form-check mb-3">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $layer?->is_active ?? false))>
        <label class="form-check-label">Aktif (published)</label>
    </div>
@else
    <p class="text-muted small mb-3">Layer akan tersimpan sebagai <strong>draft</strong>. Hanya super-admin/admin-bappeda yang bisa mempublikasikan Layer.</p>
@endcan
