@php
    $source = $source ?? null;
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Jenis Source</label>
        <select name="source_type" class="form-select" required>
            <option value="">-- Pilih --</option>
            @foreach ($sourceTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('source_type', $source?->source_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('source_type') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Nama</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $source?->name) }}">
    </div>
</div>

<div class="mb-3">
    <label class="form-label">URL</label>
    <input type="url" name="url" class="form-control" value="{{ old('url', $source?->url) }}" required>
    @error('url') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Nama Layer Layanan</label>
        <input type="text" name="service_layer_name" class="form-control" value="{{ old('service_layer_name', $source?->service_layer_name) }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Format</label>
        <input type="text" name="format" class="form-control" placeholder="mis. image/png" value="{{ old('format', $source?->format) }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">CRS</label>
        <input type="text" name="crs" class="form-control" placeholder="mis. EPSG:4326" value="{{ old('crs', $source?->crs) }}">
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Jenis Autentikasi</label>
        <select name="auth_type" class="form-select">
            @foreach (['none' => 'Tanpa Autentikasi', 'api_key' => 'API Key', 'basic' => 'Basic Auth', 'token' => 'Token'] as $value => $label)
                <option value="{{ $value }}" @selected(old('auth_type', $source?->auth_type ?? 'none') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Referensi Kredensial</label>
        <input type="text" name="credential_ref" class="form-control" placeholder="mis. secrets/wms-bappeda-api-key"
            value="{{ old('credential_ref', $source?->credential_ref) }}">
        <div class="form-text text-danger">Hanya nama referensi — JANGAN isi kredensial sungguhan (R11).</div>
    </div>
</div>

<div class="form-check">
    <input type="hidden" name="is_primary" value="0">
    <input type="checkbox" name="is_primary" value="1" class="form-check-input" @checked(old('is_primary', $source?->is_primary ?? false))>
    <label class="form-check-label">Jadikan Source primary (menggantikan primary lain bila ada)</label>
</div>
