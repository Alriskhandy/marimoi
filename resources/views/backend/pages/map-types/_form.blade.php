@php
    $old = fn ($key, $default = '') => old($key, $default);
@endphp

<div class="mb-3">
    <label class="form-label">Slug <span class="text-danger">*</span></label>
    <input type="text" name="slug" class="form-control" pattern="[a-z0-9_]+"
        placeholder="mis. tematik" value="{{ $old('slug') }}" required>
    <small class="text-muted">Huruf kecil, angka, underscore saja — dipakai sebagai identifier stabil.</small>
</div>

<div class="mb-3">
    <label class="form-label">Nama <span class="text-danger">*</span></label>
    <input type="text" name="nama" class="form-control" value="{{ $old('nama') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Deskripsi</label>
    <textarea name="deskripsi" class="form-control" rows="2">{{ $old('deskripsi') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">Icon</label>
    <input type="text" name="icon" class="form-control" placeholder="mis. mdi-map" value="{{ $old('icon') }}">
</div>

<div class="mb-3">
    <label class="form-label">Urutan</label>
    <input type="number" name="urutan" class="form-control" min="0" value="{{ $old('urutan', 0) }}">
</div>

<div class="form-check form-switch">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
    <label class="form-check-label">Aktif</label>
</div>
