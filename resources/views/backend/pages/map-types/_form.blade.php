@php
    $mapType = $mapType ?? null;
    $existingAttributes = $mapType?->dynamicAttributes ?? collect();
    // Disiapkan sebagai array PHP biasa di sini (bukan langsung di dalam @json()
    // multi-baris) — direktif @json() Blade tidak menangani argumen array literal
    // multi-baris dengan banyak koma dengan benar (hasil kompilasinya terpotong).
    $coreAttributeCodes = \App\Http\Controllers\MapTypeController::CORE_ATTRIBUTE_CODES;
    $existingAttributesForJs = $existingAttributes->map(
        fn($a) => [
            'metadata_definition_id' => $a->metadata_definition_id,
            'kode' => $a->metadataDefinition->kode,
            'label' => $a->metadataDefinition->label,
            'satuan' => $a->metadataDefinition->satuan,
            'data_type' => $a->metadataDefinition->data_type,
            'is_system' => $a->metadataDefinition->is_system,
            'is_wajib' => $a->is_wajib,
            'is_core' => in_array($a->metadataDefinition->kode, $coreAttributeCodes, true),
        ],
    );
@endphp

<div class="form-section mb-3">
    <div class="form-section-header">
        <i class="mdi mdi-information-outline"></i> Informasi Dasar
    </div>
    <div class="form-section-body">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="map_type_nama" class="form-control"
                        value="{{ old('nama', $mapType?->nama) }}" required>
                    @error('nama')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="mdi mdi-lock-outline"></i></span>
                        <input type="text" name="slug" id="map_type_slug" class="form-control" readonly
                            value="{{ old('slug', $mapType?->slug) }}" pattern="[a-z0-9_]+" required disabled>
                    </div>
                    @error('slug')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Urutan Tampil</label>
                    <input type="number" name="urutan" class="form-control"
                        value="{{ old('urutan', $mapType?->urutan ?? 0) }}">
                    <div class="form-text">Menentukan urutan Jenis ini di daftar.</div>
                </div>
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="map_type_is_active"
                        class="form-check-input" @checked(old('is_active', $mapType?->is_active ?? true))>
                    <label class="form-check-label" for="map_type_is_active">
                        <i class="mdi mdi-check-circle text-success me-1"></i>Aktifkan Jenis Peta
                    </label>
                </div>
                <div class="form-text">Jenis nonaktif tidak muncul sebagai pilihan.</div>

            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const namaInput = document.getElementById('map_type_nama');
        const slugInput = document.getElementById('map_type_slug');
        const isNewMapType = {{ $mapType ? 'false' : 'true' }};

        // Slug readonly bagi user — cuma di-generate otomatis dari Nama lewat JS
        // (readonly cuma blokir ketikan manual, tetap bisa di-set via script & tetap
        // ikut ter-submit). Untuk Jenis yang SUDAH ADA, slug tidak lagi mengikuti
        // perubahan Nama sama sekali — mengubahnya bisa merusak referensi yang sudah
        // memakai slug lama (mis. lookup slug hardcode di beberapa command/migration).
        if (isNewMapType) {
            namaInput.addEventListener('input', () => {
                slugInput.value = namaInput.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
            });
        }
    })();
</script>

<div class="form-section mb-3">
    <div class="form-section-header">
        <i class="mdi mdi-clipboard-text-outline"></i> Metadata
    </div>
    <div class="form-section-body">

        <p class="text-muted small mb-2">
            <i class="mdi mdi-information-outline"></i>
            <strong>Sumber Data</strong>, <strong>OPD Penanggung Jawab</strong>, dan
            <strong>Tahun/Tanggal Data</strong> otomatis terpasang sebagai atribut wajib
            untuk setiap Jenis Peta (tampil di tabel setelah Jenis ini disimpan).
        </p>

        <div class="row g-2 align-items-center mb-2">
            <div class="col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                    <input type="text" class="form-control" id="catalog-search-input"
                        placeholder="Cari atribut dari katalog untuk dipakai ulang...">
                </div>
            </div>
            <div class="col-sm-6 text-sm-end">
                <button type="button" id="btn-add-custom-attribute" class="btn btn-sm btn-outline-primary">
                    <i class="mdi mdi-plus"></i> Buat Atribut Baru
                </button>
            </div>
        </div>
        <div id="catalog-search-results" class="list-group mb-3" style="display:none;"></div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0" id="dynamic-attributes-table">
                <thead class="table-light">
                    <tr>
                        <th>Nama/Key</th>
                        <th>Nilai (Label)</th>
                        <th style="width:110px;">Satuan</th>
                        <th style="width:110px;">Tipe Data</th>
                        <th style="width:70px;" class="text-center">Wajib</th>
                        <th style="width:70px;" class="text-center">Filter</th>
                        <th style="width:50px;"></th>
                    </tr>
                </thead>
                <tbody id="dynamic-attributes-list">
                    <tr id="dynamic-attributes-empty">
                        <td colspan="7" class="text-center text-muted py-3">
                            Belum ada atribut tambahan. Cari dari katalog atau klik "Buat Atribut Baru".
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<template id="picked-row-template">
    <tr class="dynamic-attribute-row picked-row">
        <td><code class="picked-kode"></code></td>
        <td class="picked-label"></td>
        <td class="picked-satuan"></td>
        <td><span class="badge bg-secondary picked-data-type"></span></td>
        <td class="text-center"><input type="checkbox" class="form-check-input attr-wajib"></td>
        <td class="text-center"><span class="text-muted small">-</span></td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-attribute" title="Hapus">
                <i class="mdi mdi-delete"></i>
            </button>
        </td>
    </tr>
</template>

<template id="new-row-template">
    <tr class="dynamic-attribute-row new-row">
        <td>
            <input type="text" class="form-control form-control-sm attr-kode" disabled tabindex="-1"
                placeholder="otomatis dari Label">
        </td>
        <td><input type="text" class="form-control form-control-sm attr-label" placeholder="mis. Lebar Jalan">
        </td>
        <td><input type="text" class="form-control form-control-sm attr-satuan" placeholder="mis. meter"></td>
        <td>
            <select class="form-select form-select-sm attr-data-type">
                @foreach (\App\Models\MetadataDefinition::DATA_TYPES as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </td>
        <td class="text-center"><input type="checkbox" class="form-check-input attr-wajib"></td>
        <td class="text-center"><input type="checkbox" class="form-check-input attr-filterable"></td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-attribute" title="Hapus">
                <i class="mdi mdi-delete"></i>
            </button>
        </td>
    </tr>
    <tr class="dynamic-attribute-row new-row-opsi" style="display:none;">
        <td></td>
        <td colspan="5">
            <label class="form-label small mb-1">Daftar Opsi (satu per baris) — untuk Tipe Data "Select"</label>
            <textarea class="form-control form-control-sm attr-opsi" rows="3"
                placeholder="Belum Mulai&#10;Berjalan&#10;Selesai"></textarea>
        </td>
        <td></td>
    </tr>
</template>

<script>
    (function() {
        const tbody = document.getElementById('dynamic-attributes-list');
        const pickedTemplate = document.getElementById('picked-row-template');
        const newTemplate = document.getElementById('new-row-template');
        const existing = @json($existingAttributesForJs);
        const searchUrl = @json(route('metadata-definitions.search'));
        const usedDefinitionIds = new Set();
        const emptyRow = document.getElementById('dynamic-attributes-empty');

        function updateEmptyState() {
            if (!emptyRow) return;
            const hasRows = tbody.querySelectorAll('.dynamic-attribute-row').length > 0;
            emptyRow.style.display = hasRows ? 'none' : '';
        }

        // sumber_data/opd_penanggung_jawab/tanggal_data dipasang otomatis & wajib ke
        // setiap Jenis Peta lewat server (MapTypeController::syncCoreAttributes()) —
        // tidak bisa dilepas/diubah lewat form ini, jadi ditampilkan tanpa checkbox
        // atau tombol hapus, cuma badge "Wajib (bawaan)" sebagai info.
        const coreAttributeCodes = ['sumber_data', 'opd_penanggung_jawab', 'tanggal_data'];

        function addPickedRow(data) {
            const id = String(data.metadata_definition_id);
            if (usedDefinitionIds.has(id)) {
                return;
            }
            usedDefinitionIds.add(id);

            const isCore = coreAttributeCodes.includes(data.kode);

            const clone = pickedTemplate.content.cloneNode(true);
            const row = clone.querySelector('.dynamic-attribute-row');
            row.dataset.metadataDefinitionId = id;
            row.querySelector('.picked-kode').textContent = data.kode;
            row.querySelector('.picked-label').textContent = data.label;
            row.querySelector('.picked-satuan').textContent = data.satuan || '-';
            row.querySelector('.picked-data-type').textContent = data.data_type || 'text';

            if (isCore) {
                row.dataset.core = '1';
                const wajibCell = row.querySelector('.attr-wajib').closest('td');
                wajibCell.innerHTML = '<span class="badge bg-danger-subtle text-danger">Wajib (bawaan)</span>';
                row.querySelector('.btn-remove-attribute').closest('td').innerHTML = '';
            } else {
                row.querySelector('.attr-wajib').checked = !!data.is_wajib;
                row.querySelector('.btn-remove-attribute').addEventListener('click', () => {
                    usedDefinitionIds.delete(id);
                    row.remove();
                    updateEmptyState();
                });
            }

            tbody.appendChild(row);
            updateEmptyState();
        }

        function addNewRow(data) {
            data = data || {};
            const clone = newTemplate.content.cloneNode(true);
            const rows = clone.querySelectorAll('.dynamic-attribute-row');
            const row = rows[0];
            const opsiRow = rows[1];

            row.querySelector('.attr-kode').value = data.kode ?? '';
            row.querySelector('.attr-label').value = data.label ?? '';
            row.querySelector('.attr-satuan').value = data.satuan ?? '';
            row.querySelector('.attr-data-type').value = data.data_type ?? 'text';
            row.querySelector('.attr-wajib').checked = !!data.is_wajib;
            row.querySelector('.attr-filterable').checked = !!data.is_filterable;
            opsiRow.querySelector('.attr-opsi').value = Array.isArray(data.opsi) ? data.opsi.join('\n') : '';

            // Nama/Key cukup diisi sekali lewat Label — kode (key teknis) otomatis
            // di-generate dari Label (pola sama dengan Slug<-Nama di "Informasi
            // Dasar"), supaya user tidak perlu mengetik hal yang sama dua kali.
            row.querySelector('.attr-label').addEventListener('input', (e) => {
                row.querySelector('.attr-kode').value = e.target.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
            });

            function syncOpsiVisibility() {
                opsiRow.style.display = row.querySelector('.attr-data-type').value === 'select' ? '' : 'none';
            }
            row.querySelector('.attr-data-type').addEventListener('change', syncOpsiVisibility);
            syncOpsiVisibility();

            const remove = () => {
                row.remove();
                opsiRow.remove();
                updateEmptyState();
            };
            row.querySelector('.btn-remove-attribute').addEventListener('click', remove);

            tbody.appendChild(row);
            tbody.appendChild(opsiRow);
            updateEmptyState();
            row.querySelector('.attr-label').focus();
        }

        // Atribut yang sudah dipakai Jenis ini (existing, apa pun sumbernya — dulu
        // siap-pakai maupun custom, sekarang tidak dibedakan lagi UI-nya) selalu
        // tampil sebagai "picked" (bukan editable "buat baru"), karena definisinya
        // sudah ada di katalog. Mengedit label/satuan definisi katalog dilakukan di
        // tempat lain, bukan lewat form Jenis ini (supaya perubahan tidak diam-diam
        // mempengaruhi Jenis lain yang memakai definisi sama).
        existing.forEach(addPickedRow);

        document.getElementById('btn-add-custom-attribute').addEventListener('click', () => addNewRow());

        // Cari & pilih dari katalog.
        const searchInput = document.getElementById('catalog-search-input');
        const searchResults = document.getElementById('catalog-search-results');
        let searchTimer = null;

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            const q = searchInput.value.trim();
            searchTimer = setTimeout(async () => {
                try {
                    const response = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`);
                    const results = response.ok ? await response.json() : [];
                    renderSearchResults(results);
                } catch (error) {
                    console.error('Gagal mencari katalog metadata', error);
                }
            }, 250);
        });

        function renderSearchResults(results) {
            searchResults.innerHTML = '';

            if (results.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'list-group-item text-muted small';
                empty.innerHTML =
                    '<i class="mdi mdi-information-outline me-1"></i>Tidak ditemukan — klik "Buat Atribut Baru" untuk menambahkannya ke katalog.';
                searchResults.appendChild(empty);
                searchResults.style.display = '';
                return;
            }

            results.forEach((item) => {
                const alreadyUsed = usedDefinitionIds.has(String(item.id));
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action' + (alreadyUsed ? ' disabled' : '');
                btn.innerHTML =
                    `<code>${item.kode}</code> — ${item.label}${item.satuan ? ' ('+item.satuan+')' : ''}` +
                    (item.is_system ? ' <span class="badge bg-info text-white">siap pakai</span>' : '') +
                    (alreadyUsed ? ' <span class="badge bg-light text-muted border">sudah dipakai</span>' :
                        '');
                if (!alreadyUsed) {
                    btn.addEventListener('click', () => {
                        addPickedRow({
                            metadata_definition_id: item.id,
                            kode: item.kode,
                            label: item.label,
                            satuan: item.satuan,
                            data_type: item.data_type,
                        });
                        searchResults.style.display = 'none';
                        searchInput.value = '';
                    });
                }
                searchResults.appendChild(btn);
            });

            searchResults.style.display = '';
        }

        document.addEventListener('click', (event) => {
            if (!searchResults.contains(event.target) && event.target !== searchInput) {
                searchResults.style.display = 'none';
            }
        });

        // Susun payload dynamic_attributes[] sebelum submit.
        const form = tbody.closest('form');
        form.addEventListener('submit', () => {
            document.querySelectorAll('input[name^="dynamic_attributes"]').forEach((el) => el.remove());
            let index = 0;

            tbody.querySelectorAll('.picked-row').forEach((row) => {
                // Baris core (sumber_data/opd_penanggung_jawab/tanggal_data) sengaja TIDAK
                // dikirim — server selalu memaksa sinkronnya sendiri (syncCoreAttributes()),
                // bukan dari payload form (lihat komentar coreAttributeCodes di atas).
                if (row.dataset.core === '1') return;

                appendHidden(index, 'metadata_definition_id', row.dataset.metadataDefinitionId);
                appendHidden(index, 'is_wajib', row.querySelector('.attr-wajib').checked ? '1' :
                    '0');
                index++;
            });

            tbody.querySelectorAll('.new-row').forEach((row) => {
                const kode = row.querySelector('.attr-kode').value.trim();
                if (!kode) return;
                appendHidden(index, 'kode', kode);
                appendHidden(index, 'label', row.querySelector('.attr-label').value);
                appendHidden(index, 'satuan', row.querySelector('.attr-satuan').value);
                appendHidden(index, 'data_type', row.querySelector('.attr-data-type').value);
                appendHidden(index, 'is_wajib', row.querySelector('.attr-wajib').checked ? '1' :
                    '0');
                appendHidden(index, 'is_filterable', row.querySelector('.attr-filterable').checked ?
                    '1' : '0');
                const opsiRow = row.nextElementSibling;
                appendHidden(index, 'opsi', opsiRow ? opsiRow.querySelector('.attr-opsi').value :
                    '');
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

@push('styles')
    <style>
        .form-section {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .form-section-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            font-size: 0.95rem;
            color: #343a40;
        }

        .form-section-body {
            padding: 1rem 1.25rem;
        }

        #dynamic-attributes-table td,
        #dynamic-attributes-table th {
            padding: 0.4rem 0.6rem;
        }

        .form-check-input:disabled {
            opacity: 0.35;
        }

        .attr-kode:disabled {
            background-color: #e9ecef;
            color: #6c757d;
            font-style: italic;
        }

        #dynamic-attributes-list tr[data-core="1"] {
            background-color: #f8f9fa;
        }

        #catalog-search-results {
            max-width: 480px;
            max-height: 220px;
            overflow-y: auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
    </style>
@endpush
