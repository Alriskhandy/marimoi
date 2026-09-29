@php
    $mapType = $mapType ?? null;
    $existingAttributes = $mapType?->dynamicAttributes ?? collect();
    // Disiapkan sebagai array PHP biasa di sini (bukan langsung di dalam @json()
    // multi-baris) — direktif @json() Blade tidak menangani argumen array literal
    // multi-baris dengan banyak koma dengan benar (hasil kompilasinya terpotong).
    $existingAttributesForJs = $existingAttributes->map(fn ($a) => [
        'tipe' => $a->tipe,
        'kode_atribut' => $a->kode_atribut,
        'label' => $a->label,
        'satuan' => $a->satuan,
        'is_wajib' => $a->is_wajib,
    ]);
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" class="form-control" value="{{ old('slug', $mapType?->slug) }}" required>
        @error('slug') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Nama</label>
        <input type="text" name="nama" class="form-control" value="{{ old('nama', $mapType?->nama) }}" required>
        @error('nama') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Deskripsi</label>
    <textarea name="deskripsi" class="form-control" rows="2">{{ old('deskripsi', $mapType?->deskripsi) }}</textarea>
</div>

<hr>
<h6>Metadata Utama <span class="text-danger">*</span> (wajib)</h6>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Sumber Data</label>
        <input type="text" name="sumber_data" class="form-control" value="{{ old('sumber_data', $mapType?->sumber_data) }}" required>
        @error('sumber_data') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">OPD Penanggung Jawab</label>
        <select name="opd_penanggung_jawab_id" class="form-select" required>
            <option value="">Pilih OPD</option>
            @foreach ($opdOptions as $opd)
                <option value="{{ $opd->id }}" @selected(old('opd_penanggung_jawab_id', $mapType?->opd_penanggung_jawab_id) == $opd->id)>{{ $opd->name }}</option>
            @endforeach
        </select>
        @error('opd_penanggung_jawab_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Tahun/Tanggal Data</label>
        <input type="date" name="tanggal_data" class="form-control" value="{{ old('tanggal_data', $mapType?->tanggal_data?->format('Y-m-d')) }}" required>
        @error('tanggal_data') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

<hr>
<h6>Metadata Dinamis (opsional — panduan pengisian atribut untuk Data Spasial di bawah Jenis ini)</h6>
<p class="text-muted small">
    Kosongkan semua kalau Jenis ini bukan objek pembangunan/intervensi (mis. Administrasi, Pola Ruang).
    Aktifkan placeholder atau tambah atribut custom kalau Data Spasial di bawah Jenis ini perlu dilacak
    (mis. Pagu, Realisasi Anggaran) — biasanya untuk Jenis "... — Intervensi [OPD]".
</p>

<div id="dynamic-attributes-list"></div>
<button type="button" id="btn-add-custom-attribute" class="btn btn-sm btn-outline-primary mb-3">
    <i class="mdi mdi-plus"></i> Tambah Atribut Custom
</button>

<template id="dynamic-attribute-row-template">
    <div class="row align-items-end mb-2 dynamic-attribute-row border-bottom pb-2">
        <input type="hidden" class="attr-tipe" value="custom">
        <div class="col-md-3">
            <label class="form-label small">Kode Atribut</label>
            <input type="text" class="form-control form-control-sm attr-kode" placeholder="mis. lebar_jalan">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Label</label>
            <input type="text" class="form-control form-control-sm attr-label" placeholder="mis. Lebar Jalan">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Satuan</label>
            <input type="text" class="form-control form-control-sm attr-satuan" placeholder="mis. meter">
        </div>
        <div class="col-md-2 form-check">
            <input type="checkbox" class="form-check-input attr-wajib">
            <label class="form-check-label small">Wajib</label>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-attribute">Hapus</button>
        </div>
    </div>
</template>

<script>
(function () {
    const list = document.getElementById('dynamic-attributes-list');
    const template = document.getElementById('dynamic-attribute-row-template');
    const placeholders = @json($placeholderAttributes);
    const existing = @json($existingAttributesForJs);

    function addRow(data) {
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.dynamic-attribute-row');
        row.querySelector('.attr-tipe').value = data.tipe ?? 'custom';
        row.querySelector('.attr-kode').value = data.kode_atribut ?? '';
        row.querySelector('.attr-label').value = data.label ?? '';
        row.querySelector('.attr-satuan').value = data.satuan ?? '';
        row.querySelector('.attr-wajib').checked = !!data.is_wajib;

        if (data.tipe === 'placeholder') {
            row.querySelector('.attr-kode').readOnly = true;
        }

        row.querySelector('.btn-remove-attribute').addEventListener('click', () => row.remove());
        list.appendChild(row);
    }

    // Baris placeholder siap-pakai (4 pilihan di desain) — dicentang kalau sudah aktif untuk Jenis ini.
    Object.entries(placeholders).forEach(([kode, meta]) => {
        const existingRow = existing.find((e) => e.kode_atribut === kode);
        const wrapper = document.createElement('div');
        wrapper.className = 'form-check mb-1';
        wrapper.innerHTML = `
            <input type="checkbox" class="form-check-input placeholder-toggle" data-kode="${kode}" data-label="${meta.label}" data-satuan="${meta.satuan ?? ''}" ${existingRow ? 'checked' : ''}>
            <label class="form-check-label">${meta.label}${meta.satuan ? ' (' + meta.satuan + ')' : ''}</label>
            <input type="checkbox" class="form-check-input ms-2 placeholder-wajib" data-kode="${kode}" ${existingRow?.is_wajib ? 'checked' : ''}> <label class="small">Wajib</label>
        `;
        list.parentNode.insertBefore(wrapper, list);
    });

    // Atribut custom yang sudah ada.
    existing.filter((e) => e.tipe === 'custom').forEach(addRow);

    document.getElementById('btn-add-custom-attribute').addEventListener('click', () => addRow({}));

    // Susun payload dynamic_attributes[] sebelum submit.
    const form = list.closest('form');
    form.addEventListener('submit', () => {
        document.querySelectorAll('input[name^="dynamic_attributes"]').forEach((el) => el.remove());
        let index = 0;

        document.querySelectorAll('.placeholder-toggle:checked').forEach((toggle) => {
            const kode = toggle.dataset.kode;
            const wajib = form.querySelector(`.placeholder-wajib[data-kode="${kode}"]`).checked;
            appendHidden(index, 'tipe', 'placeholder');
            appendHidden(index, 'kode_atribut', kode);
            appendHidden(index, 'label', toggle.dataset.label);
            appendHidden(index, 'satuan', toggle.dataset.satuan);
            appendHidden(index, 'is_wajib', wajib ? '1' : '0');
            index++;
        });

        list.querySelectorAll('.dynamic-attribute-row').forEach((row) => {
            const kode = row.querySelector('.attr-kode').value.trim();
            if (!kode) return;
            appendHidden(index, 'tipe', 'custom');
            appendHidden(index, 'kode_atribut', kode);
            appendHidden(index, 'label', row.querySelector('.attr-label').value);
            appendHidden(index, 'satuan', row.querySelector('.attr-satuan').value);
            appendHidden(index, 'is_wajib', row.querySelector('.attr-wajib').checked ? '1' : '0');
            index++;
        });

        function appendHidden(idx, key, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `dynamic_attributes[${idx}][${key}]`;
            input.value = value;
            form.appendChild(input);
        }
    });
})();
</script>

<div class="row mt-3">
    <div class="col-md-6 mb-3">
        <label class="form-label">Icon</label>
        <input type="text" name="icon" class="form-control" value="{{ old('icon', $mapType?->icon) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Urutan</label>
        <input type="number" name="urutan" class="form-control" value="{{ old('urutan', $mapType?->urutan ?? 0) }}">
    </div>
    <div class="col-md-3 mb-3 form-check mt-4">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $mapType?->is_active ?? true))>
        <label class="form-check-label">Aktif</label>
    </div>
</div>
