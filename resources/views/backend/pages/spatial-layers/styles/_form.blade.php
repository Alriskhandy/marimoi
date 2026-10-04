@php
    $style = $style ?? null;
    $definition = $style?->definition ?? [];
    $existingClasses = $definition['classes'] ?? [];
    $currentType = old('style_type', $style?->style_type ?? 'simple');
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Nama Style</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $style?->name) }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Tipe</label>
        <select name="style_type" id="style_type_{{ $uid }}" class="form-select" onchange="toggleStyleFields('{{ $uid }}')" required>
            @foreach ($styleTypes as $value => $label)
                <option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('style_type') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

<div class="form-check mb-3">
    <input type="hidden" name="is_default" value="0">
    <input type="checkbox" name="is_default" value="1" class="form-check-input" @checked(old('is_default', $style?->is_default ?? false))>
    <label class="form-check-label">Jadikan style default</label>
</div>

<div id="simple_fields_{{ $uid }}" style="{{ $currentType === 'simple' ? '' : 'display:none' }}">
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label">Warna</label>
            <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', $definition['color'] ?? '#2563eb') }}">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Icon</label>
            <input type="text" name="icon" class="form-control" placeholder="mis. mdi mdi-road" value="{{ old('icon', $definition['icon'] ?? '') }}">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Opacity</label>
            <input type="number" name="opacity" class="form-control" step="0.1" min="0" max="1" value="{{ old('opacity', $definition['opacity'] ?? 1) }}">
        </div>
        <div class="col-md-3 mb-3 form-check mt-4">
            <input type="checkbox" name="is_marker" value="1" class="form-check-input" @checked(old('is_marker', $definition['is_marker'] ?? false))>
            <label class="form-check-label">Tampil sebagai marker</label>
        </div>
    </div>
</div>

<div id="classified_fields_{{ $uid }}" style="{{ $currentType === 'simple' ? 'display:none' : '' }}">
    <div class="mb-3">
        <label class="form-label">Atribut untuk Diklasifikasi</label>
        <select name="classification_field" class="form-select">
            <option value="">-- Pilih Atribut --</option>
            @foreach ($classificationFields as $definitionField)
                <option value="{{ $definitionField->kode }}" @selected(old('classification_field', $style?->classification_field) === $definitionField->kode)>
                    {{ $definitionField->label }}</option>
            @endforeach
        </select>
        @error('classification_field') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <label class="form-label d-block">Kelas</label>
    <div id="classes_{{ $uid }}">
        @foreach ($existingClasses as $i => $class)
            <div class="row g-2 mb-2 class-row align-items-center">
                <div class="col-3 class-field-value" style="display:{{ $currentType === 'categorized' ? '' : 'none' }}">
                    <input type="text" class="form-control form-control-sm" name="classes[{{ $i }}][value]" placeholder="Nilai" value="{{ $class['value'] ?? '' }}">
                </div>
                <div class="col-1 class-field-range" style="display:{{ $currentType === 'graduated' ? '' : 'none' }}">
                    <input type="number" step="any" class="form-control form-control-sm" name="classes[{{ $i }}][min]" placeholder="Min" value="{{ $class['min'] ?? '' }}">
                </div>
                <div class="col-1 class-field-range" style="display:{{ $currentType === 'graduated' ? '' : 'none' }}">
                    <input type="number" step="any" class="form-control form-control-sm" name="classes[{{ $i }}][max]" placeholder="Max" value="{{ $class['max'] ?? '' }}">
                </div>
                <div class="col-2">
                    <input type="color" class="form-control form-control-sm form-control-color" name="classes[{{ $i }}][color]" value="{{ $class['color'] ?? '#2563eb' }}">
                </div>
                <div class="col-4">
                    <input type="text" class="form-control form-control-sm" name="classes[{{ $i }}][label]" placeholder="Label legenda (opsional)" value="{{ $class['label'] ?? '' }}">
                </div>
                <div class="col-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.class-row').remove()">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addClassRow('{{ $uid }}')">
        <i class="mdi mdi-plus"></i> Tambah Kelas
    </button>
</div>
