@php
    $isEdit = $template->exists;
    $title = $isEdit ? 'Ubah Template Dokumen' : 'Tambah Template Dokumen';
    // Pratinjau logo yang sudah tersimpan (inline, agar tetap tampil walau template nonaktif).
    $logoPreview = function (string $slot) use ($template): ?string {
        $path = $template->{\App\Models\DocumentTemplate::LOGO_SLOTS[$slot]};
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (! $path || ! $disk->exists($path)) {
            return null;
        }

        return 'data:'.$disk->mimeType($path).';base64,'.base64_encode($disk->get($path));
    };
    $checked = fn (string $field) => old($field, $template->{$field}) ? 'checked' : '';
@endphp

@extends('backend.partials.main', ['title' => $title])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-file-document-edit"></i></span>
            {{ $title }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('document-templates.index') }}">Template Dokumen</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Ubah' : 'Tambah' }}</li>
            </ul>
        </nav>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" id="templateForm"
        action="{{ $isEdit ? route('document-templates.update', $template) : route('document-templates.store') }}">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-lg-7 grid-margin">
                {{-- Identitas template --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Informasi Template</h4>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama template <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" maxlength="100" required
                                value="{{ old('name', $template->name) }}" placeholder="Contoh: Kop Resmi Bappeda">
                            <div class="form-text">Ditampilkan sebagai pilihan template kepada pengunjung.</div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <input type="text" class="form-control" id="description" name="description" maxlength="255"
                                value="{{ old('description', $template->description) }}" placeholder="Keterangan singkat penggunaan template">
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label d-block">Dipakai untuk <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="for_map" name="for_map" value="1" {{ $checked('for_map') }}>
                                    <label class="form-check-label" for="for_map">Unduh Peta (PNG/PDF)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="for_analysis" name="for_analysis" value="1" {{ $checked('for_analysis') }}>
                                    <label class="form-check-label" for="for_analysis">Cetak Analisis Peta</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label d-block">Ketersediaan</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ $checked('is_active') }}>
                                    <label class="form-check-label" for="is_active">Aktif (dapat dipilih pengunjung)</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_default" name="is_default" value="1" {{ $checked('is_default') }}>
                                    <label class="form-check-label" for="is_default">Jadikan template bawaan</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="accent_color" class="form-label">Warna aksen</label>
                                <input type="color" class="form-control form-control-color w-100" id="accent_color" name="accent_color"
                                    value="{{ old('accent_color', $template->accent_color ?: '#1d3557') }}">
                                <div class="form-text">Warna garis kop dan judul dokumen.</div>
                            </div>
                            <div class="col-sm-6">
                                <label for="sort_order" class="form-label">Urutan</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" max="999"
                                    value="{{ old('sort_order', $template->sort_order ?? 0) }}">
                                <div class="form-text">Angka kecil tampil lebih dulu (bawaan selalu pertama).</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kop / Header --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">Kop / Header</h4>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="header_enabled" name="header_enabled" value="1" {{ $checked('header_enabled') }}>
                                <label class="form-check-label" for="header_enabled">Tampilkan kop</label>
                            </div>
                        </div>
                        <div id="headerFields" class="mt-3">
                            <div class="mb-3">
                                <label for="header_line1" class="form-label">Baris 1 (instansi induk) <small class="text-muted">· Arial 12pt, kapital</small></label>
                                <input type="text" class="form-control text-uppercase" id="header_line1" name="header_line1" maxlength="150"
                                    value="{{ old('header_line1', $template->header_line1) }}" placeholder="PEMERINTAH PROVINSI MALUKU UTARA">
                            </div>
                            <div class="mb-3">
                                <label for="header_line2" class="form-label">Baris 2 (nama instansi) <small class="text-muted">· Arial 14pt, tebal, kapital</small></label>
                                <input type="text" class="form-control text-uppercase fw-bold" id="header_line2" name="header_line2" maxlength="150"
                                    value="{{ old('header_line2', $template->header_line2) }}" placeholder="BADAN PERENCANAAN PEMBANGUNAN DAERAH">
                            </div>
                            <div class="mb-3">
                                <label for="header_line3" class="form-label">Baris 3 (alamat / kontak) <small class="text-muted">· Arial 10pt, sesuai isian</small></label>
                                <textarea class="form-control" id="header_line3" name="header_line3" maxlength="500" rows="3"
                                    placeholder="Alamat, telepon, email, atau situs web">{{ old('header_line3', $template->header_line3) }}</textarea>
                                <div class="form-text">Tekan Enter untuk membuat baris baru. Tinggi kop menyesuaikan jumlah baris.</div>
                            </div>
                            <div class="row g-3">
                                @foreach (['left' => 'Logo kiri', 'right' => 'Logo kanan'] as $slot => $label)
                                    @php $preview = $logoPreview($slot); @endphp
                                    <div class="col-sm-6">
                                        <label for="logo_{{ $slot }}" class="form-label">{{ $label }}</label>
                                        <input type="file" class="form-control @error('logo_'.$slot) is-invalid @enderror" id="logo_{{ $slot }}" name="logo_{{ $slot }}"
                                            accept="image/png,image/jpeg,image/webp" data-logo-input="{{ $slot }}">
                                        <div class="form-text">PNG/JPG/WebP, maks. 1 MB. PNG transparan disarankan.</div>
                                        @if ($preview)
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox" id="remove_logo_{{ $slot }}" name="remove_logo_{{ $slot }}" value="1" data-logo-remove="{{ $slot }}">
                                                <label class="form-check-label small" for="remove_logo_{{ $slot }}">Hapus logo ini</label>
                                            </div>
                                        @endif
                                        <img src="{{ $preview }}" alt="" data-logo-saved="{{ $slot }}" hidden>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Footer</h4>
                        <div class="mb-3">
                            <label for="footer_text" class="form-label">Teks kiri <small class="text-muted">· Arial 9pt</small></label>
                            <textarea class="form-control" id="footer_text" name="footer_text" maxlength="500" rows="3"
                                placeholder="Sumber data: MARIMOI — Bappeda Provinsi Maluku Utara">{{ old('footer_text', $template->footer_text) }}</textarea>
                            <div class="form-text">Mis. sumber data atau catatan/disclaimer. Tekan Enter untuk baris baru; teks panjang dibungkus otomatis.</div>
                        </div>
                        <div class="alert alert-light border small mb-0">
                            <i class="mdi mdi-lock-outline"></i> <b>Sisi kanan footer diisi otomatis</b> dan tidak dapat diatur:
                            basemap &amp; sistem koordinat (Unduh Peta), serta tanggal &amp; waktu cetak.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Orientasi, tata letak & pratinjau --}}
            <div class="col-lg-5 grid-margin">
                <div class="card position-sticky" style="top: 90px;">
                    <div class="card-body">
                        <h4 class="card-title">Tata Letak & Pratinjau</h4>
                        <p class="text-muted small mb-3">Seret kotak untuk memindahkan, tarik sudut kanan bawah untuk mengubah ukuran.
                            Tata letak dipakai pada <b>Unduh Peta</b>; cetak Analisis hanya memakai kop, footer, dan orientasi.</p>

                        <div class="btn-group w-100 mb-3" role="group" aria-label="Orientasi halaman">
                            @foreach (\App\Models\DocumentTemplate::ORIENTATIONS as $value => $label)
                                <input type="radio" class="btn-check" name="orientation" id="orientation_{{ $value }}" value="{{ $value }}"
                                    @checked(old('orientation', $template->orientation ?: 'landscape') === $value)>
                                <label class="btn btn-outline-primary" for="orientation_{{ $value }}">
                                    <i class="mdi {{ $value === 'portrait' ? 'mdi-file-outline' : 'mdi-file-outline mdi-rotate-90' }}"></i> {{ $label }}
                                </label>
                            @endforeach
                        </div>

                        <div class="layout-elements mb-3">
                            @foreach (\App\Models\DocumentTemplate::LAYOUT_ELEMENTS as $key => $element)
                                <label class="layout-element-toggle" data-element-color="{{ $key }}">
                                    <input type="checkbox" data-layout-toggle="{{ $key }}" @if ($element['required']) checked disabled @endif>
                                    <span>{{ $element['label'] }}</span>
                                </label>
                            @endforeach
                            <button type="button" class="btn btn-sm btn-link ms-auto px-0" id="layoutReset"><i class="mdi mdi-restore"></i> Atur ulang</button>
                        </div>

                        <input type="hidden" name="layout" id="layoutInput"
                            value="{{ old('layout', json_encode($template->exists ? $template->resolvedLayout() : \App\Models\DocumentTemplate::defaultLayout($template->orientation ?: 'landscape'))) }}">

                        <div class="layout-page" id="layoutPage">
                            <div id="previewHeader" class="layout-kop">
                                <img id="previewLogoLeft" alt="" hidden>
                                <div class="flex-grow-1 text-center" style="line-height: 1.15;">
                                    <div id="previewLine1" class="fw-bold layout-kop-line1"></div>
                                    <div id="previewLine2" class="fw-bolder layout-kop-line2"></div>
                                    <div id="previewLine3" class="text-muted layout-kop-line3"></div>
                                </div>
                                <img id="previewLogoRight" alt="" hidden>
                            </div>
                            <div id="previewTitle" class="layout-title">Peta Interaktif MARIMOI</div>
                            <div class="layout-subtitle">Provinsi Maluku Utara · Skala ±1 : 2.800.000</div>
                            <div class="layout-content" id="layoutContent"></div>
                            <div class="layout-footer">
                                <span id="previewFooterText"></span>
                                <span id="previewFooterNote" class="text-end"></span>
                            </div>
                        </div>
                        <p class="small text-muted mt-2 mb-0" id="layoutReadout">&nbsp;</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-gradient-primary"><i class="mdi mdi-content-save"></i> Simpan Template</button>
            <a href="{{ route('document-templates.index') }}" class="btn btn-light">Batal</a>
        </div>
    </form>
@endsection

@push('styles')
    <style>
        .layout-elements { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
        .layout-element-toggle { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border: 1px solid #dee2e6; border-radius: 999px; font-size: 12px; cursor: pointer; user-select: none; }
        .layout-element-toggle input { margin: 0; }
        .layout-element-toggle::before { content: ""; width: 10px; height: 10px; border-radius: 3px; background: var(--element-color); }
        .layout-page { display: flex; flex-direction: column; padding: 4.5%; font-family: Arial, Helvetica, sans-serif !important; border: 1px solid #dee2e6; border-radius: 10px; background: #fff; color: #0f172a; box-shadow: 0 10px 30px -18px rgba(15, 23, 42, .5); font-family: Inter, sans-serif; transition: aspect-ratio .2s; }
        .layout-kop { display: flex; align-items: center; gap: 8px; padding-bottom: 6px; margin-bottom: 6px; border-bottom: 3px double #1d3557; }
        .layout-kop img { height: 30px; width: auto; }
        .layout-kop-line1 { font-size: 8.57px; text-transform: uppercase; } .layout-kop-line2 { font-size: 10px; text-transform: uppercase; } .layout-kop-line3 { font-size: 7.14px; white-space: pre-line; color: #334155 !important; }
        .layout-title { font-size: 11px; font-weight: 800; color: #1d3557; }
        .layout-subtitle { margin-bottom: 6px; font-size: 7px; color: #64748b; }
        .layout-content { position: relative; flex: 1; min-height: 120px; outline: 1px dashed #cbd5e1; }
        .layout-footer { display: flex; justify-content: space-between; gap: 8px; margin-top: 8px; padding-top: 4px; border-top: 1px solid #e2e8f0; font-size: 6.43px; line-height: 1.15; color: #475569; }
        .layout-footer #previewFooterText { white-space: pre-line; }
        .layout-box { position: absolute; display: grid; place-items: center; border: 1.5px solid var(--element-color); border-radius: 3px; background: color-mix(in srgb, var(--element-color) 14%, white); color: #0f172a; font-size: 10px; font-weight: 600; text-align: center; cursor: move; touch-action: none; user-select: none; }
        .layout-box.is-map { background: repeating-linear-gradient(45deg, #e8eef5, #e8eef5 6px, #f3f6fa 6px, #f3f6fa 12px); z-index: 0; }
        .layout-box:not(.is-map) { z-index: 1; }
        .layout-box.is-active { box-shadow: 0 0 0 2px #fff, 0 0 0 4px var(--element-color); z-index: 2; }
        .layout-box-handle { position: absolute; right: -5px; bottom: -5px; width: 12px; height: 12px; border: 2px solid #fff; border-radius: 50%; background: var(--element-color); cursor: nwse-resize; }
        [data-element-color="map"], .layout-box[data-key="map"] { --element-color: #0a84ff; }
        [data-element-color="legend"], .layout-box[data-key="legend"] { --element-color: #10b981; }
        [data-element-color="inset"], .layout-box[data-key="inset"] { --element-color: #f59e0b; }
        [data-element-color="scale"], .layout-box[data-key="scale"] { --element-color: #a855f7; }
        [data-element-color="north"], .layout-box[data-key="north"] { --element-color: #ef4444; }
    </style>
@endpush

@push('scripts')
    <script>
        // Pratinjau kop & footer + editor tata letak (seret/ubah ukuran, persen area isi halaman).
        (function () {
            const form = document.getElementById('templateForm');
            const $ = (id) => document.getElementById(id);
            const uploaded = {};
            const printDate = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            const ELEMENTS = @json(collect(\App\Models\DocumentTemplate::LAYOUT_ELEMENTS)->map(fn ($element) => $element['label']));
            const DEFAULTS = @json(collect(\App\Models\DocumentTemplate::ORIENTATIONS)->keys()->mapWithKeys(fn ($value) => [$value => \App\Models\DocumentTemplate::defaultLayout($value)]));
            const MIN = {{ \App\Models\DocumentTemplate::LAYOUT_MIN_SIZE }};
            const content = $('layoutContent');
            let layout = JSON.parse($('layoutInput').value || 'null') || DEFAULTS.landscape;
            let activeKey = null;

            const orientation = () => form.querySelector('input[name="orientation"]:checked')?.value || 'landscape';
            const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
            const round = (value) => Math.round(value * 2) / 2;

            function logoSource(slot) {
                if (uploaded[slot]) {
                    return uploaded[slot];
                }
                const remove = form.querySelector(`[data-logo-remove="${slot}"]`);
                const saved = form.querySelector(`[data-logo-saved="${slot}"]`);
                return remove?.checked || !saved?.getAttribute('src') ? null : saved.getAttribute('src');
            }

            function save() {
                $('layoutInput').value = JSON.stringify(layout);
            }

            function readout(key) {
                const box = layout[key];
                $('layoutReadout').textContent = key
                    ? `${ELEMENTS[key]}: posisi ${box.x}% , ${box.y}% · ukuran ${box.w}% × ${box.h}%`
                    : '\u00a0';
            }

            function drawBoxes() {
                content.replaceChildren(...Object.keys(ELEMENTS).filter((key) => layout[key]?.enabled).map((key) => {
                    const box = layout[key];
                    const el = document.createElement('div');
                    el.className = `layout-box${key === 'map' ? ' is-map' : ''}${key === activeKey ? ' is-active' : ''}`;
                    el.dataset.key = key;
                    Object.assign(el.style, { left: `${box.x}%`, top: `${box.y}%`, width: `${box.w}%`, height: `${box.h}%` });
                    el.innerHTML = `<span>${key === 'north' ? 'U ▲' : ELEMENTS[key]}</span><span class="layout-box-handle" data-resize></span>`;
                    return el;
                }));
                form.querySelectorAll('[data-layout-toggle]').forEach((toggle) => { toggle.checked = Boolean(layout[toggle.dataset.layoutToggle]?.enabled); });
            }

            function render() {
                const color = $('accent_color').value;
                const headerOn = $('header_enabled').checked;
                $('headerFields').style.opacity = headerOn ? 1 : 0.45;
                $('previewHeader').classList.toggle('d-none', !headerOn);
                $('previewHeader').style.borderBottomColor = color;
                $('previewTitle').style.color = color;
                ['1', '2', '3'].forEach((n) => { $(`previewLine${n}`).textContent = $(`header_line${n}`).value; });
                [['left', 'previewLogoLeft'], ['right', 'previewLogoRight']].forEach(([slot, id]) => {
                    const src = logoSource(slot);
                    $(id).hidden = !src;
                    if (src) $(id).src = src;
                });
                $('previewFooterText').textContent = $('footer_text').value;
                $('previewFooterNote').innerHTML = `Basemap: OpenStreetMap · WGS 84 / Web Mercator<br>Dicetak ${printDate}, 14.05 WIT`;
                $('layoutPage').style.aspectRatio = orientation() === 'portrait' ? '1 / 1.414' : '1.414 / 1';
            }

            // Seret (pindah) atau tarik pegangan (ubah ukuran) dengan pointer events (mouse & sentuh).
            content.addEventListener('pointerdown', (event) => {
                const el = event.target.closest('.layout-box');
                if (!el) {
                    return;
                }
                event.preventDefault();
                const key = el.dataset.key;
                const resizing = Boolean(event.target.closest('[data-resize]'));
                const rect = content.getBoundingClientRect();
                const start = { x: event.clientX, y: event.clientY, box: { ...layout[key] } };
                activeKey = key;
                drawBoxes();
                readout(key);
                const target = content.querySelector(`[data-key="${key}"]`);
                target.setPointerCapture(event.pointerId);

                const move = (moveEvent) => {
                    const dx = ((moveEvent.clientX - start.x) / rect.width) * 100;
                    const dy = ((moveEvent.clientY - start.y) / rect.height) * 100;
                    const box = layout[key];
                    if (resizing) {
                        box.w = round(clamp(start.box.w + dx, MIN, 100 - start.box.x));
                        box.h = round(clamp(start.box.h + dy, MIN, 100 - start.box.y));
                    } else {
                        box.x = round(clamp(start.box.x + dx, 0, 100 - start.box.w));
                        box.y = round(clamp(start.box.y + dy, 0, 100 - start.box.h));
                    }
                    Object.assign(target.style, { left: `${box.x}%`, top: `${box.y}%`, width: `${box.w}%`, height: `${box.h}%` });
                    readout(key);
                };
                const end = () => {
                    target.removeEventListener('pointermove', move);
                    target.removeEventListener('pointerup', end);
                    target.removeEventListener('pointercancel', end);
                    save();
                };
                target.addEventListener('pointermove', move);
                target.addEventListener('pointerup', end);
                target.addEventListener('pointercancel', end);
            });

            form.querySelectorAll('[data-layout-toggle]').forEach((toggle) => toggle.addEventListener('change', () => {
                const key = toggle.dataset.layoutToggle;
                layout[key] = { ...(layout[key] || DEFAULTS[orientation()][key]), enabled: toggle.checked };
                activeKey = toggle.checked ? key : null;
                save();
                drawBoxes();
                readout(activeKey);
            }));

            // Ganti orientasi: tata letak disetel ke bawaan orientasi baru (posisi lama tidak cocok lagi).
            form.querySelectorAll('input[name="orientation"]').forEach((radio) => radio.addEventListener('change', () => {
                const enabled = Object.fromEntries(Object.keys(ELEMENTS).map((key) => [key, layout[key]?.enabled ?? true]));
                layout = JSON.parse(JSON.stringify(DEFAULTS[orientation()]));
                Object.keys(enabled).forEach((key) => { layout[key].enabled = key === 'map' || enabled[key]; });
                save();
                drawBoxes();
                render();
            }));

            $('layoutReset').addEventListener('click', () => {
                layout = JSON.parse(JSON.stringify(DEFAULTS[orientation()]));
                activeKey = null;
                save();
                drawBoxes();
                readout(null);
            });

            form.querySelectorAll('[data-logo-input]').forEach((input) => input.addEventListener('change', () => {
                const file = input.files[0];
                const slot = input.dataset.logoInput;
                if (!file) {
                    delete uploaded[slot];
                    render();
                    return;
                }
                const reader = new FileReader();
                reader.onload = () => { uploaded[slot] = reader.result; render(); };
                reader.readAsDataURL(file);
            }));
            form.addEventListener('input', render);
            form.addEventListener('change', render);
            save();
            drawBoxes();
            render();
        })();
    </script>
@endpush
