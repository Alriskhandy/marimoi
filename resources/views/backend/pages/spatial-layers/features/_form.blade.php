@php
    $feature = $feature ?? null;
    $geometryWkt = $geometryWkt ?? null;
    $regionsByLevel = $regionsByLevel ?? collect();
    $regionLevelLabels = ['provinsi' => 'Provinsi', 'kabupaten_kota' => 'Kabupaten/Kota', 'kecamatan' => 'Kecamatan'];
@endphp

<div class="mb-3">
    <label class="form-label">Geometri (WKT)</label>
    <textarea name="geometry_wkt" class="form-control" rows="3" placeholder="mis. POINT(127.5 0.8)" required>{{ old('geometry_wkt', $geometryWkt) }}</textarea>
    @error('geometry_wkt') <div class="text-danger small">{{ $message }}</div> @enderror
    <small class="text-muted">Format Well-Known Text standar (POINT/LINESTRING/POLYGON), SRID 4326.</small>
</div>

@if ($regionsByLevel->isNotEmpty())
    <div class="mb-3">
        <label class="form-label">Penanda Wilayah</label>
        <select name="region_id" class="form-select">
            <option value="">-- Tidak ditandai --</option>
            @foreach ($regionsByLevel as $level => $regions)
                <optgroup label="{{ $regionLevelLabels[$level] ?? $level }}">
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}" @selected(old('region_id', $feature?->region_id) == $region->id)>{{ $region->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('region_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
@endif

<div class="mb-3">
    <label class="form-label">Gambar</label>
    <input type="file" name="gambar" class="form-control" accept="image/*">
    @error('gambar') <div class="text-danger small">{{ $message }}</div> @enderror
    @if ($feature?->gambar)
        <img src="{{ asset('storage/'.$feature->gambar) }}" alt="Gambar Data Spasial" class="img-thumbnail mt-2" style="max-width:200px;">
    @endif
</div>

@if ($feature)
    <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="mdi mdi-palette-outline me-1"></i> Warna/ukuran/marker Data Spasial ini diatur di halaman
            Style Layer, bersama Data Spasial lain — supaya semua style terkumpul di satu tempat.</span>
        <a href="{{ route('spatial-layers.styles.index', $layer) }}" class="btn btn-sm btn-outline-primary">
            <i class="mdi mdi-arrow-right"></i> Kelola Style
        </a>
    </div>
@endif

@php
    // `properties` v3 menggabungkan atribut mentah hasil impor DAN metadata
    // dinamis (dulu 2 kolom terpisah, lihat SpatialLayerFeatureController) —
    // kode yang sudah tampil di bagian "Metadata Dinamis" di bawah dikecualikan
    // di sini supaya tidak dobel tampil.
    $dynamicCodes = $dynamicAttributes->pluck('metadataDefinition.kode')->all();
    $rawAttributes = \Illuminate\Support\Arr::except($feature?->properties ?? [], $dynamicCodes);
@endphp

@if (! empty($rawAttributes))
    <hr>
    <h6>Atribut Impor (hasil SHP/KMZ/KML — apa adanya, tidak diedit di sini)</h6>
    <table class="table table-sm">
        @foreach ($rawAttributes as $key => $value)
            <tr><th>{{ $key }}</th><td>{{ is_scalar($value) ? $value : json_encode($value) }}</td></tr>
        @endforeach
    </table>
@endif

@if ($dynamicAttributes->isNotEmpty())
    <hr>
    <h6>Metadata Dinamis</h6>
@endif
@include('backend.pages.spatial-layers.features._metadata-dinamis', ['dynamicAttributes' => $dynamicAttributes, 'feature' => $feature])
