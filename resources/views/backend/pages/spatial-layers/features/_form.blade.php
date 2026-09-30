@php
    $feature = $feature ?? null;
    $geometryWkt = $geometryWkt ?? null;
@endphp

<div class="mb-3">
    <label class="form-label">Geometri (WKT)</label>
    <textarea name="geometry_wkt" class="form-control" rows="3" placeholder="mis. POINT(127.5 0.8)" required>{{ old('geometry_wkt', $geometryWkt) }}</textarea>
    @error('geometry_wkt') <div class="text-danger small">{{ $message }}</div> @enderror
    <small class="text-muted">Format Well-Known Text standar (POINT/LINESTRING/POLYGON), SRID 4326.</small>
</div>

<div class="mb-3">
    <label class="form-label">Gambar</label>
    <input type="file" name="gambar" class="form-control" accept="image/*">
    @error('gambar') <div class="text-danger small">{{ $message }}</div> @enderror
    @if ($feature?->gambar)
        <img src="{{ asset('storage/'.$feature->gambar) }}" alt="Gambar Data Spasial" class="img-thumbnail mt-2" style="max-width:200px;">
    @endif
</div>

@if ($feature?->attributes)
    <hr>
    <h6>Atribut Impor (hasil SHP/KMZ/KML — apa adanya, tidak diedit di sini)</h6>
    <table class="table table-sm">
        @foreach ($feature->attributes as $key => $value)
            <tr><th>{{ $key }}</th><td>{{ is_scalar($value) ? $value : json_encode($value) }}</td></tr>
        @endforeach
    </table>
@endif

@if ($dynamicAttributes->isNotEmpty())
    <hr>
    <h6>Metadata Dinamis (sesuai skema Jenis "{{ $layer->mapType?->nama }}")</h6>
    @foreach ($dynamicAttributes as $attribute)
        @php $definition = $attribute->metadataDefinition; @endphp
        <div class="mb-3">
            <label class="form-label">
                {{ $definition->label }}{{ $definition->satuan ? ' ('.$definition->satuan.')' : '' }}
                @if ($attribute->is_wajib) <span class="text-danger">*</span> @endif
            </label>
            @if ($definition->data_type === \App\Models\MetadataDefinition::TYPE_SELECT && ! empty($definition->opsi))
                <select name="metadata_dinamis[{{ $definition->kode }}]" class="form-select"
                    @if ($attribute->is_wajib) required @endif>
                    <option value="">-- Pilih --</option>
                    @foreach ($definition->opsi as $opsi)
                        <option value="{{ $opsi }}" @selected(old('metadata_dinamis.'.$definition->kode, $feature?->metadata_dinamis[$definition->kode] ?? '') == $opsi)>{{ $opsi }}</option>
                    @endforeach
                </select>
            @else
                <input type="text" name="metadata_dinamis[{{ $definition->kode }}]" class="form-control"
                    value="{{ old('metadata_dinamis.'.$definition->kode, $feature?->metadata_dinamis[$definition->kode] ?? '') }}"
                    @if ($attribute->is_wajib) required @endif>
            @endif
            @error('metadata_dinamis.'.$definition->kode) <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
    @endforeach
@else
    <p class="text-muted small mt-3">Jenis Layer ini tidak punya skema Metadata Dinamis aktif — tidak ada isian tambahan.</p>
@endif
