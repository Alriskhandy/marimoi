@extends('backend.partials.main', ['title' => 'Tambah Fitur — ' . $layer->name])

@push('styles')
<style>
    .upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        transition: all .3s;
        background: #f8f9fa;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .upload-area:hover, .upload-area.dragover {
        border-color: #667eea;
        background: #eeebff;
    }
    .upload-area.uploaded { border-color: #198754; background: #d1e7dd; }
    .file-info { display: none; background: #e9ecef; border-radius: 6px; padding: 8px 12px; margin-top: 8px; font-size: .85rem; }
    .file-info.show { display: block; }

    .step-indicator { display: flex; align-items: center; justify-content: center; margin-bottom: 2rem; }
    .step {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg, #dee2e6, #adb5bd);
        display: flex; align-items: center; justify-content: center;
        color: #6c757d; font-weight: 700; transition: all .3s;
        box-shadow: 0 2px 4px rgba(0,0,0,.1);
    }
    .step.active  { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; transform: scale(1.1); box-shadow: 0 4px 12px rgba(102,126,234,.4); }
    .step.done    { background: linear-gradient(135deg, #198754, #20c997); color: #fff; box-shadow: 0 4px 12px rgba(25,135,84,.3); }
    .step-connector { flex: 1; max-width: 80px; height: 2px; background: #dee2e6; }
    .step-connector.done { background: linear-gradient(90deg, #198754, #20c997); }
    .step-label { font-size: .75rem; color: #6c757d; margin-top: 4px; }

    .form-section { display: none; animation: fadeIn .3s ease; }
    .form-section.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

    .input-option {
        border: 2px solid #dee2e6; border-radius: 14px; padding: 24px 18px;
        text-align: center; cursor: pointer; transition: all .3s;
        background: #fff; position: relative; overflow: hidden;
    }
    .input-option:hover { border-color: #667eea; transform: translateY(-4px); box-shadow: 0 8px 24px rgba(102,126,234,.15); }
    .input-option.selected { border-color: #667eea; background: linear-gradient(135deg, #f0ecff, #e6e0ff); transform: scale(1.03); box-shadow: 0 8px 28px rgba(102,126,234,.3); }
    .input-option .option-icon { font-size: 2.4rem; color: #6c757d; margin-bottom: 10px; transition: all .3s; }
    .input-option:hover .option-icon, .input-option.selected .option-icon { color: #667eea; transform: scale(1.1); }
    .input-option .option-title { font-weight: 600; font-size: 1rem; color: #495057; }
    .input-option:hover .option-title, .input-option.selected .option-title { color: #667eea; }
    .input-option .option-desc { font-size: .8rem; color: #6c757d; line-height: 1.4; margin-top: 4px; }

    .input-content { display: none; margin-top: 24px; animation: fadeIn .4s ease; }
    .input-content.active { display: block; }

    .prop-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
    .prop-row input { flex: 1; }
    .btn-remove-prop { flex-shrink: 0; }

    .coord-row { display: flex; gap: 8px; margin-bottom: 10px; align-items: flex-end; }
    .coord-row .coord-field { flex: 1; }

    .summary-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 10px; padding: 18px; }

    .layer-badge { display: inline-flex; align-items: center; gap: 6px; padding: .35rem .9rem; border-radius: 20px; font-weight: 600; font-size: .85rem; }
    .layer-badge.tematik    { background: #d1ecf1; color: #0c5460; }
    .layer-badge.psd        { background: #d4edda; color: #155724; }
    .layer-badge.psn        { background: #cce5ff; color: #004085; }
    .layer-badge.pokir      { background: #fff3cd; color: #856404; }
    .layer-badge.musrenbang { background: #fce4ec; color: #880e4f; }

    .type-icon.point   { color: #e83e8c; }
    .type-icon.line    { color: #fd7e14; }
    .type-icon.polygon { color: #20c997; }

    .image-preview-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
    .image-preview-item { position: relative; width: 90px; height: 90px; border-radius: 8px; overflow: hidden; border: 2px solid #dee2e6; }
    .image-preview-item img { width: 100%; height: 100%; object-fit: cover; }
    .image-preview-item .remove-img { position: absolute; top: 2px; right: 2px; background: rgba(220,53,69,.8); border: none; border-radius: 50%; width: 20px; height: 20px; color: #fff; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
</style>
@endpush

@section('main')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi mdi-map-marker-plus"></i>
        </span>
        Tambah Fitur
    </h3>
    <nav aria-label="breadcrumb">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('layers.index', ['category' => $layer->category]) }}">
                    {{ ['tematik'=>'Peta Tematik','psd'=>'PSD','psn'=>'PSN','pokir'=>'Pokir DPRD','musrenbang'=>'Musrenbang'][$layer->category] ?? $layer->category }}
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('layers.features.index', $layer) }}">{{ $layer->name }}</a>
            </li>
            <li class="breadcrumb-item active">Tambah Fitur</li>
        </ul>
    </nav>
</div>

<div class="row">
    <div class="col-lg-10 col-xl-9 mx-auto">
        <div class="card">
            <div class="card-body">

                {{-- Layer info banner --}}
                <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-3">
                    <span class="layer-badge {{ $layer->category }}">
                        <i class="mdi mdi-layers"></i>
                        {{ ['tematik'=>'Peta Tematik','psd'=>'Proyek Strategis Daerah','psn'=>'Proyek Strategis Nasional','pokir'=>'Pokir DPRD','musrenbang'=>'Usulan Musrenbang'][$layer->category] ?? $layer->category }}
                    </span>
                    <span class="fw-semibold">{{ $layer->name }}</span>
                    <span class="ms-auto text-muted small">
                        <i class="mdi mdi-vector-{{ $layer->type === 'polygon' ? 'polygon' : ($layer->type === 'line' ? 'line' : 'point') }} type-icon {{ $layer->type }} me-1"></i>
                        {{ ucfirst($layer->type) }}
                    </span>
                </div>

                {{-- Step Indicator --}}
                <div class="mb-4">
                    <div class="step-indicator">
                        <div>
                            <div class="step active" id="step-1">1</div>
                            <div class="step-label text-center">Informasi</div>
                        </div>
                        <div class="step-connector" id="conn-1"></div>
                        <div>
                            <div class="step" id="step-2">2</div>
                            <div class="step-label text-center">Geometri</div>
                        </div>
                        <div class="step-connector" id="conn-2"></div>
                        <div>
                            <div class="step" id="step-3">3</div>
                            <div class="step-label text-center">Selesai</div>
                        </div>
                    </div>
                </div>

                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
                @endif

                <form method="POST" action="{{ route('layers.features.store', $layer) }}"
                      enctype="multipart/form-data" id="featureForm">
                    @csrf
                    <input type="hidden" name="input_type" id="input_type_hidden" value="">

                    {{-- ===================== STEP 1 : Informasi ===================== --}}
                    <div class="form-section active" id="section-1">
                        <h5 class="mb-4"><i class="mdi mdi-information-outline me-2 text-primary"></i>Informasi Dasar</h5>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Tahun Data</label>
                                    <input type="number" name="year" id="inp-year" class="form-control"
                                           value="{{ old('year', date('Y')) }}"
                                           min="1900" max="{{ date('Y') + 5 }}" style="width:130px;">
                                    <div class="form-text">Tahun pengambilan data</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Properties Tambahan
                                <span class="text-muted fw-normal">(opsional)</span>
                            </label>
                            <div class="form-text mb-2">Atribut tambahan yang akan disertakan di setiap fitur yang diimport.</div>

                            <div id="prop-rows">
                                <div class="prop-row">
                                    <input type="text" name="prop_keys[]" class="form-control form-control-sm" placeholder="Nama atribut (contoh: keterangan)">
                                    <input type="text" name="prop_values[]" class="form-control form-control-sm" placeholder="Nilai">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-prop" onclick="removePropRow(this)" style="display:none;">
                                        <i class="mdi mdi-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addPropRow()">
                                <i class="mdi mdi-plus me-1"></i>Tambah Atribut
                            </button>
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-gradient-primary" onclick="nextStep(1)">
                                Lanjut <i class="mdi mdi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ===================== STEP 2 : Geometri ===================== --}}
                    <div class="form-section" id="section-2">
                        <h5 class="mb-4"><i class="mdi mdi-map-outline me-2 text-primary"></i>Pilih Metode Input Geometri</h5>

                        <div class="row g-3 mb-4" id="input-options">
                            <div class="col-6 col-md-3">
                                <div class="input-option" data-type="geojson" onclick="selectInput('geojson')">
                                    <div class="option-icon"><i class="mdi mdi-code-json"></i></div>
                                    <div class="option-title">GeoJSON</div>
                                    <div class="option-desc">Upload file .geojson</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="input-option" data-type="shapefile" onclick="selectInput('shapefile')">
                                    <div class="option-icon"><i class="mdi mdi-file-document-multiple-outline"></i></div>
                                    <div class="option-title">Shapefile</div>
                                    <div class="option-desc">.shp + .shx + .dbf</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="input-option" data-type="coordinates" onclick="selectInput('coordinates')">
                                    <div class="option-icon"><i class="mdi mdi-map-marker-multiple-outline"></i></div>
                                    <div class="option-title">Koordinat</div>
                                    <div class="option-desc">Input manual lat/lng</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="input-option" data-type="kmz" onclick="selectInput('kmz')">
                                    <div class="option-icon"><i class="mdi mdi-earth"></i></div>
                                    <div class="option-title">KMZ / KML</div>
                                    <div class="option-desc">File dari Google Earth</div>
                                </div>
                            </div>
                        </div>

                        {{-- GeoJSON --}}
                        <div class="input-content" id="content-geojson">
                            <label class="form-label fw-semibold">File GeoJSON <span class="text-danger">*</span></label>
                            <div class="upload-area" id="drop-geojson" ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onDrop(event,'geojson_file','geojson')" onclick="document.getElementById('geojson_file').click()">
                                <i class="mdi mdi-code-json" style="font-size:2.5rem;color:#6c757d;"></i>
                                <p class="mb-2 mt-2 text-muted">Drag & drop file .geojson atau klik untuk browse</p>
                                <span class="btn btn-sm btn-outline-primary">Browse File</span>
                                <input type="file" id="geojson_file" name="geojson_file" accept=".geojson,.json" style="display:none;" onchange="onFileSelect(this,'geojson')">
                            </div>
                            <div class="file-info" id="info-geojson"></div>
                            <div class="alert alert-info mt-3 py-2 small">
                                <i class="mdi mdi-information me-1"></i>
                                Setiap <code>feature</code> dalam GeoJSON akan disimpan sebagai satu fitur layer.
                            </div>
                        </div>

                        {{-- Shapefile --}}
                        <div class="input-content" id="content-shapefile">
                            <div class="row g-3">
                                @foreach(['shp'=>'.shp (geometri)','shx'=>'.shx (indeks)','dbf'=>'.dbf (atribut)'] as $ext => $label)
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">File {{ strtoupper($ext) }} <span class="text-danger">*</span></label>
                                    <div class="upload-area" id="drop-{{ $ext }}" ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onDrop(event,'{{ $ext }}_file','{{ $ext }}')" onclick="document.getElementById('{{ $ext }}_file').click()">
                                        <i class="mdi mdi-file-outline" style="font-size:2rem;color:#6c757d;"></i>
                                        <p class="mb-1 mt-1 small text-muted">{{ $label }}</p>
                                        <span class="btn btn-xs btn-outline-secondary" style="font-size:.75rem;padding:.2rem .6rem;">Browse</span>
                                        <input type="file" id="{{ $ext }}_file" name="{{ $ext }}_file" accept=".{{ $ext }}" style="display:none;" onchange="onFileSelect(this,'{{ $ext }}')">
                                    </div>
                                    <div class="file-info" id="info-{{ $ext }}"></div>
                                </div>
                                @endforeach
                            </div>
                            <div class="alert alert-info mt-3 py-2 small">
                                <i class="mdi mdi-information me-1"></i>
                                Ketiga file harus memiliki nama yang sama dan berasal dari dataset yang sama. Atribut DBF akan tersimpan sebagai <em>properties</em> fitur.
                            </div>
                        </div>

                        {{-- Koordinat Manual --}}
                        <div class="input-content" id="content-coordinates">
                            <label class="form-label fw-semibold">Koordinat Lokasi <span class="text-danger">*</span></label>
                            <div id="coord-rows">
                                <div class="coord-row">
                                    <div class="coord-field">
                                        <label class="form-label small mb-1">Nama (opsional)</label>
                                        <input type="text" name="coordinates[0][name]" class="form-control form-control-sm" placeholder="Nama lokasi">
                                    </div>
                                    <div class="coord-field">
                                        <label class="form-label small mb-1">Latitude <span class="text-danger">*</span></label>
                                        <input type="number" name="coordinates[0][latitude]" class="form-control form-control-sm coord-lat" step="any" placeholder="-2.123456">
                                    </div>
                                    <div class="coord-field">
                                        <label class="form-label small mb-1">Longitude <span class="text-danger">*</span></label>
                                        <input type="number" name="coordinates[0][longitude]" class="form-control form-control-sm coord-lng" step="any" placeholder="128.123456">
                                    </div>
                                    <div style="flex-shrink:0;padding-top:22px;">
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="addCoordRow()"><i class="mdi mdi-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text mt-1"><i class="mdi mdi-information me-1"></i>Format: Latitude (-90 s/d 90), Longitude (-180 s/d 180). Gunakan titik (.) untuk desimal.</div>
                            <div class="alert alert-warning mt-3 py-2 small">
                                <i class="mdi mdi-image-outline me-1"></i>
                                Metode koordinat mendukung upload gambar (langkah berikutnya).
                            </div>
                        </div>

                        {{-- KMZ --}}
                        <div class="input-content" id="content-kmz">
                            <label class="form-label fw-semibold">File KMZ / KML <span class="text-danger">*</span></label>
                            <div class="upload-area" id="drop-kmz" ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onDrop(event,'kmz_file','kmz')" onclick="document.getElementById('kmz_file').click()">
                                <i class="mdi mdi-earth" style="font-size:2.5rem;color:#6c757d;"></i>
                                <p class="mb-2 mt-2 text-muted">Drag & drop file .kmz/.kml atau klik untuk browse</p>
                                <span class="btn btn-sm btn-outline-primary">Browse File</span>
                                <input type="file" id="kmz_file" name="kmz_file" accept=".kmz,.kml" style="display:none;" onchange="onFileSelect(this,'kmz')">
                            </div>
                            <div class="file-info" id="info-kmz"></div>
                            <div class="alert alert-info mt-3 py-2 small">
                                <i class="mdi mdi-information me-1"></i>
                                Setiap <em>Placemark</em> dalam file KMZ/KML akan disimpan sebagai satu fitur layer.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary" onclick="prevStep(2)">
                                <i class="mdi mdi-arrow-left me-1"></i> Kembali
                            </button>
                            <button type="button" class="btn btn-gradient-primary" onclick="nextStep(2)">
                                Lanjut <i class="mdi mdi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ===================== STEP 3 : Gambar & Konfirmasi ===================== --}}
                    <div class="form-section" id="section-3">
                        <h5 class="mb-4"><i class="mdi mdi-check-circle-outline me-2 text-primary"></i>Ringkasan & Konfirmasi</h5>

                        {{-- Summary --}}
                        <div class="summary-card mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Layer:</strong> {{ $layer->name }}</p>
                                    <p class="mb-1"><strong>Kategori:</strong> {{ ['tematik'=>'Peta Tematik','psd'=>'Proyek Strategis Daerah','psn'=>'Proyek Strategis Nasional','pokir'=>'Pokir DPRD','musrenbang'=>'Usulan Musrenbang'][$layer->category] ?? $layer->category }}</p>
                                    <p class="mb-1"><strong>Tipe Geometri Layer:</strong> {{ ucfirst($layer->type) }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Tahun:</strong> <span id="summary-year">—</span></p>
                                    <p class="mb-1"><strong>Metode Input:</strong> <span id="summary-method">—</span></p>
                                    <p class="mb-1"><strong>Detail:</strong> <span id="summary-detail">—</span></p>
                                </div>
                            </div>
                        </div>

                        {{-- Image upload — shown only for coordinate input --}}
                        <div id="image-upload-section" style="display:none;">
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Upload Gambar
                                    <span class="text-muted fw-normal">(opsional, bisa lebih dari satu)</span>
                                </label>
                                <input type="file" name="images[]" id="images-input" class="form-control"
                                       accept="image/jpg,image/jpeg,image/png,image/webp" multiple
                                       onchange="previewImages(this)">
                                <div class="form-text">Format: JPG, PNG, WEBP. Maks 5 MB per file.</div>
                                <div class="image-preview-grid" id="image-preview-grid"></div>
                            </div>
                        </div>

                        <div id="image-note-section" style="display:none;">
                            <div class="alert alert-secondary py-2 small mb-4">
                                <i class="mdi mdi-image-off-outline me-1"></i>
                                Upload gambar tidak tersedia untuk metode import file. Anda dapat menambahkan gambar setelah fitur berhasil disimpan melalui halaman <strong>Detail Fitur</strong>.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="prevStep(3)">
                                <i class="mdi mdi-arrow-left me-1"></i> Kembali
                            </button>
                            <button type="submit" class="btn btn-gradient-primary" id="btn-submit">
                                <i class="mdi mdi-content-save me-1"></i> Simpan Fitur
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentStep = 1;
let selectedInput = '';
let coordCount    = 1;

const methodLabels = {
    geojson:     'File GeoJSON',
    shapefile:   'Shapefile (.shp .shx .dbf)',
    coordinates: 'Koordinat Manual',
    kmz:         'File KMZ / KML',
};

// ── Step navigation ──────────────────────────────────────────────
function nextStep(from) {
    if (!validateStep(from)) return;
    document.getElementById(`section-${from}`).classList.remove('active');
    document.getElementById(`step-${from}`).classList.remove('active');
    document.getElementById(`step-${from}`).classList.add('done');
    document.getElementById(`conn-${from}`).classList.add('done');
    currentStep = from + 1;
    document.getElementById(`section-${currentStep}`).classList.add('active');
    document.getElementById(`step-${currentStep}`).classList.add('active');
    if (currentStep === 3) updateSummary();
    document.querySelector('.card-body').scrollIntoView({ behavior: 'smooth' });
}

function prevStep(from) {
    document.getElementById(`section-${from}`).classList.remove('active');
    document.getElementById(`step-${from}`).classList.remove('active');
    currentStep = from - 1;
    document.getElementById(`section-${currentStep}`).classList.add('active');
    document.getElementById(`step-${currentStep}`).classList.add('active');
    document.getElementById(`step-${currentStep}`).classList.remove('done');
    document.getElementById(`conn-${currentStep}`).classList.remove('done');
    document.querySelector('.card-body').scrollIntoView({ behavior: 'smooth' });
}

// ── Validation ────────────────────────────────────────────────────
function validateStep(step) {
    if (step === 1) return true; // nothing required

    if (step === 2) {
        if (!selectedInput) {
            Swal.fire({ icon: 'warning', title: 'Pilih Metode', text: 'Silakan pilih salah satu metode input geometri.', confirmButtonColor: '#667eea' });
            return false;
        }
        if (selectedInput === 'geojson' && !document.getElementById('geojson_file').files.length) {
            Swal.fire({ icon: 'warning', title: 'File Belum Dipilih', text: 'Silakan upload file GeoJSON.', confirmButtonColor: '#667eea' });
            return false;
        }
        if (selectedInput === 'shapefile') {
            for (const ext of ['shp','shx','dbf']) {
                if (!document.getElementById(ext + '_file').files.length) {
                    Swal.fire({ icon: 'warning', title: 'File Tidak Lengkap', text: `File .${ext} belum dipilih.`, confirmButtonColor: '#667eea' });
                    return false;
                }
            }
        }
        if (selectedInput === 'kmz' && !document.getElementById('kmz_file').files.length) {
            Swal.fire({ icon: 'warning', title: 'File Belum Dipilih', text: 'Silakan upload file KMZ/KML.', confirmButtonColor: '#667eea' });
            return false;
        }
        if (selectedInput === 'coordinates') {
            const lats = document.querySelectorAll('.coord-lat');
            const lngs = document.querySelectorAll('.coord-lng');
            let valid = false;
            for (let i = 0; i < lats.length; i++) {
                const lat = parseFloat(lats[i].value), lng = parseFloat(lngs[i].value);
                if (!isNaN(lat) && !isNaN(lng)) {
                    if (lat < -90 || lat > 90) { Swal.fire({ icon:'warning', title:'Koordinat Tidak Valid', text:`Latitude baris ${i+1} harus antara -90 dan 90.`, confirmButtonColor:'#667eea' }); return false; }
                    if (lng < -180 || lng > 180) { Swal.fire({ icon:'warning', title:'Koordinat Tidak Valid', text:`Longitude baris ${i+1} harus antara -180 dan 180.`, confirmButtonColor:'#667eea' }); return false; }
                    valid = true;
                }
            }
            if (!valid) {
                Swal.fire({ icon: 'warning', title: 'Koordinat Kosong', text: 'Minimal satu pasang koordinat harus diisi.', confirmButtonColor: '#667eea' });
                return false;
            }
        }
    }
    return true;
}

// ── Input type selection ──────────────────────────────────────────
function selectInput(type) {
    selectedInput = type;
    document.getElementById('input_type_hidden').value = type;

    document.querySelectorAll('.input-option').forEach(el => el.classList.remove('selected'));
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');

    document.querySelectorAll('.input-content').forEach(el => el.classList.remove('active'));
    document.getElementById(`content-${type}`).classList.add('active');
}

// ── File handling ─────────────────────────────────────────────────
function onDragOver(e) { e.preventDefault(); e.currentTarget.classList.add('dragover'); }
function onDragLeave(e) { e.currentTarget.classList.remove('dragover'); }
function onDrop(e, inputId, type) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    const dt = e.dataTransfer;
    if (dt && dt.files.length) {
        const input = document.getElementById(inputId);
        input.files = dt.files;
        onFileSelect(input, type);
    }
}

function onFileSelect(input, type) {
    const file = input.files[0];
    if (!file) return;
    const infoEl = document.getElementById(`info-${type}`);
    infoEl.innerHTML = `<i class="mdi mdi-check-circle text-success me-1"></i><strong>${file.name}</strong> (${formatSize(file.size)})`;
    infoEl.classList.add('show');
    input.closest('.upload-area') && input.closest('.upload-area').classList.add('uploaded');
}

function formatSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024, sizes = ['B','KB','MB','GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(1) + ' ' + sizes[i];
}

// ── Properties rows ───────────────────────────────────────────────
function addPropRow() {
    const container = document.getElementById('prop-rows');
    const idx       = container.children.length;
    const row       = document.createElement('div');
    row.className   = 'prop-row';
    row.innerHTML   = `
        <input type="text"  name="prop_keys[]"   class="form-control form-control-sm" placeholder="Nama atribut">
        <input type="text"  name="prop_values[]" class="form-control form-control-sm" placeholder="Nilai">
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-prop" onclick="removePropRow(this)">
            <i class="mdi mdi-minus"></i>
        </button>`;
    container.appendChild(row);
    updateRemovePropButtons();
}

function removePropRow(btn) {
    btn.closest('.prop-row').remove();
    updateRemovePropButtons();
}

function updateRemovePropButtons() {
    const rows    = document.querySelectorAll('.prop-row');
    const showBtn = rows.length > 1;
    rows.forEach(r => {
        const btn = r.querySelector('.btn-remove-prop');
        if (btn) btn.style.display = showBtn ? '' : 'none';
    });
}

// ── Coordinate rows ───────────────────────────────────────────────
function addCoordRow() {
    const container = document.getElementById('coord-rows');
    const idx       = coordCount++;
    const row       = document.createElement('div');
    row.className   = 'coord-row';
    row.innerHTML   = `
        <div class="coord-field">
            <label class="form-label small mb-1">Nama (opsional)</label>
            <input type="text" name="coordinates[${idx}][name]" class="form-control form-control-sm" placeholder="Nama lokasi">
        </div>
        <div class="coord-field">
            <label class="form-label small mb-1">Latitude <span class="text-danger">*</span></label>
            <input type="number" name="coordinates[${idx}][latitude]" class="form-control form-control-sm coord-lat" step="any" placeholder="-2.123456">
        </div>
        <div class="coord-field">
            <label class="form-label small mb-1">Longitude <span class="text-danger">*</span></label>
            <input type="number" name="coordinates[${idx}][longitude]" class="form-control form-control-sm coord-lng" step="any" placeholder="128.123456">
        </div>
        <div style="flex-shrink:0;padding-top:22px;">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.coord-row').remove(); coordCount--;">
                <i class="mdi mdi-minus"></i>
            </button>
        </div>`;
    container.appendChild(row);
}

// ── Image preview ─────────────────────────────────────────────────
function previewImages(input) {
    const grid = document.getElementById('image-preview-grid');
    grid.innerHTML = '';
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const item = document.createElement('div');
            item.className = 'image-preview-item';
            item.innerHTML = `<img src="${e.target.result}" alt="">`;
            grid.appendChild(item);
        };
        reader.readAsDataURL(file);
    });
}

// ── Summary ───────────────────────────────────────────────────────
function updateSummary() {
    document.getElementById('summary-year').textContent =
        document.getElementById('inp-year').value || '—';
    document.getElementById('summary-method').textContent =
        methodLabels[selectedInput] || '—';

    let detail = '';
    if (selectedInput === 'geojson') {
        const f = document.getElementById('geojson_file').files[0];
        detail = f ? f.name : '—';
    } else if (selectedInput === 'shapefile') {
        const f = document.getElementById('shp_file').files[0];
        detail = f ? f.name.replace(/\.shp$/i, '') + ' (.shp/.shx/.dbf)' : '—';
    } else if (selectedInput === 'kmz') {
        const f = document.getElementById('kmz_file').files[0];
        detail = f ? f.name : '—';
    } else if (selectedInput === 'coordinates') {
        let cnt = 0;
        document.querySelectorAll('.coord-lat').forEach(el => { if (el.value) cnt++; });
        detail = `${cnt} titik koordinat`;
    }
    document.getElementById('summary-detail').textContent = detail;

    // Show/hide image upload
    const imgSection  = document.getElementById('image-upload-section');
    const noteSection = document.getElementById('image-note-section');
    if (selectedInput === 'coordinates') {
        imgSection.style.display  = '';
        noteSection.style.display = 'none';
    } else {
        imgSection.style.display  = 'none';
        noteSection.style.display = '';
    }
}

// ── Submit guard ──────────────────────────────────────────────────
document.getElementById('featureForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Menyimpan...';
});
</script>
@endpush
