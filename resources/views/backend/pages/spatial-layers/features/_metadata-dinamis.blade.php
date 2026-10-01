@php
    $feature = $feature ?? null;
@endphp

@if ($dynamicAttributes->isNotEmpty())
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
