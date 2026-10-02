@php
    $layer = $layer ?? null;
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Nama</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $layer?->name) }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Jenis</label>
        <select name="map_type_id" class="form-select" required>
            <option value="">Pilih Jenis</option>
            @foreach ($mapTypes as $mapType)
                <option value="{{ $mapType->id }}" @selected(old('map_type_id', $layer?->map_type_id) == $mapType->id)>{{ $mapType->nama }}</option>
            @endforeach
        </select>
        @error('map_type_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

@include('backend.pages.spatial-layers._category-picker', [
    'selectedCategoryId' => $layer?->category_id,
    'selectedCategoryNodeId' => $layer?->category_node_id,
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

<div class="form-check mb-3">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $layer?->is_active ?? true))>
    <label class="form-check-label">Aktif</label>
</div>
