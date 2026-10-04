@extends('backend.partials.main', ['title' => 'Tambah Data Spasial'])

@push('styles')
    <style>
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .upload-area:hover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
            cursor: pointer;
        }

        .upload-area.dragover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
            transform: scale(1.02);
        }

        .upload-area.uploaded {
            border-color: #198754;
            background-color: #d1e7dd;
        }

        .file-info {
            background-color: #e9ecef;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            display: none;
        }

        .file-info.show {
            display: block;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            align-items: center;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dee2e6, #adb5bd);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: #6c757d;
            font-weight: bold;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .step.active {
            background: linear-gradient(135deg, #0d6efd, #0056b3);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
        }

        .step.completed {
            background: linear-gradient(135deg, #198754, #146c43);
            color: white;
            box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3);
        }

        .step-connector {
            width: 60px;
            height: 2px;
            background-color: #dee2e6;
        }

        .step-connector.completed {
            background: linear-gradient(90deg, #198754, #20c997);
        }

        .form-section {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }

        .form-section.active {
            display: block;
        }

        .input-type-selector {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .input-options {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .input-option {
            border: 2px solid #dee2e6;
            border-radius: 15px;
            padding: 25px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            flex: 1;
            min-width: 200px;
            max-width: 280px;
            position: relative;
            overflow: hidden;
        }

        .input-option:hover {
            border-color: #0d6efd;
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.15);
        }

        .input-option.selected {
            border-color: #0d6efd;
            background: linear-gradient(135deg, #e7f3ff, #cce7ff);
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(13, 110, 253, 0.3);
        }

        .input-option .option-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .input-option.selected .option-icon,
        .input-option:hover .option-icon {
            color: #0d6efd;
            transform: scale(1.1);
        }

        .input-option .option-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #495057;
        }

        .input-option.selected .option-title,
        .input-option:hover .option-title {
            color: #0d6efd;
        }

        .input-option .option-description {
            font-size: 0.85rem;
            color: #6c757d;
            line-height: 1.4;
        }

        .input-content {
            display: none;
            margin-top: 30px;
        }

        .input-content.active {
            display: block;
            animation: fadeIn 0.5s ease-in;
        }

        .coord-input-group {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .coord-input-row {
            display: flex;
            gap: 15px;
            align-items: end;
            margin-bottom: 15px;
        }

        .coord-input-row:last-child {
            margin-bottom: 0;
        }

        .coord-field {
            flex: 1;
        }

        .coord-actions {
            display: flex;
            gap: 8px;
        }

        .btn-add-coord {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-add-coord:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .btn-remove-coord {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-remove-coord:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .file-upload-icon {
            font-size: 2.5rem;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .upload-text {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .summary-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .alert-info-custom {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            border: 1px solid #b8daff;
            border-radius: 10px;
        }

        .progress-container {
            display: none;
            margin-top: 20px;
        }
    </style>
@endpush

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-map-marker-plus"></i></span>
            Tambah Data Spasial: {{ $layer->name }}
        </h3>
        <a href="{{ route('spatial-layers.show', $layer) }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step active" id="step-1">1</div>
                        <div class="step-connector"></div>
                        <div class="step" id="step-2">2</div>
                        <div class="step-connector"></div>
                        <div class="step" id="step-3">3</div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-alert-circle me-2"></i>
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('spatial-layers.features.store', $layer) }}"
                        enctype="multipart/form-data" id="featureForm">
                        @csrf
                        <input type="hidden" name="input_type" id="input_type" value="">

                        <!-- Step 1: Metode Input Geometri -->
                        <div class="form-section active" id="section-1">
                            <h5 class="mb-4">
                                <i class="mdi mdi-cloud-upload me-2"></i>
                                Pilih Metode Input Data Spasial
                            </h5>

                            <div class="input-type-selector">
                                <div class="input-options">
                                    <div class="input-option" data-type="shapefile" onclick="selectInputType('shapefile')">
                                        <div class="option-icon"><i class="mdi mdi-file-document-outline"></i></div>
                                        <div class="option-title">Shapefile</div>
                                        <div class="option-description">Upload file .shp, .shx, dan .dbf untuk data
                                            vektor kompleks</div>
                                    </div>
                                    <div class="input-option" data-type="coordinates" onclick="selectInputType('coordinates')">
                                        <div class="option-icon"><i class="mdi mdi-map-marker"></i></div>
                                        <div class="option-title">Koordinat</div>
                                        <div class="option-description">Input manual latitude dan longitude, bisa lebih
                                            dari satu titik</div>
                                    </div>
                                    <div class="input-option" data-type="kmz" onclick="selectInputType('kmz')">
                                        <div class="option-icon"><i class="mdi mdi-earth"></i></div>
                                        <div class="option-title">File KMZ</div>
                                        <div class="option-description">Upload file KMZ/KML dari Google Earth atau
                                            aplikasi GIS lainnya</div>
                                    </div>
                                </div>
                            </div>

                            @if ($layer->features()->exists())
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Mode Impor</label>
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="import_mode" id="import_mode_append" value="append" checked>
                                        <label class="form-check-label" for="import_mode_append">
                                            Tambahkan ke Data Spasial yang sudah ada ({{ $layer->features()->count() }} data)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="import_mode" id="import_mode_replace" value="replace">
                                        <label class="form-check-label" for="import_mode_replace">
                                            <span class="text-danger">Ganti seluruh</span> Data Spasial layer ini dengan hasil impor ini
                                        </label>
                                    </div>
                                    <div class="form-text">Mode "Ganti" menghapus seluruh Data Spasial layer ini sebelum mengisi hasil impor baru, dalam satu transaksi.</div>
                                </div>
                            @endif

                            <!-- Shapefile -->
                            <div class="input-content" id="shapefile-content">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label"><i class="mdi mdi-file me-1"></i>File .shp
                                                <span class="text-danger">*</span></label>
                                            <div class="upload-area" ondrop="handleDrop(event, 'shp_file')"
                                                ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                                <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                                                <p class="upload-text">Drag & drop file .shp atau klik untuk browse</p>
                                                <input type="file" class="form-control" id="shp_file" name="shp_file"
                                                    accept=".shp" style="display: none;" onchange="handleFileSelect(this, 'shp')">
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    onclick="document.getElementById('shp_file').click()">
                                                    <i class="mdi mdi-folder-open me-1"></i>Browse File
                                                </button>
                                            </div>
                                            <div class="file-info" id="shp-info">
                                                <i class="mdi mdi-check-circle text-success me-1"></i>
                                                <span id="shp-filename"></span>
                                                <small class="text-muted d-block" id="shp-size"></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label"><i class="mdi mdi-file me-1"></i>File .shx
                                                <span class="text-danger">*</span></label>
                                            <div class="upload-area" ondrop="handleDrop(event, 'shx_file')"
                                                ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                                <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                                                <p class="upload-text">Drag & drop file .shx atau klik untuk browse</p>
                                                <input type="file" class="form-control" id="shx_file" name="shx_file"
                                                    accept=".shx" style="display: none;" onchange="handleFileSelect(this, 'shx')">
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    onclick="document.getElementById('shx_file').click()">
                                                    <i class="mdi mdi-folder-open me-1"></i>Browse File
                                                </button>
                                            </div>
                                            <div class="file-info" id="shx-info">
                                                <i class="mdi mdi-check-circle text-success me-1"></i>
                                                <span id="shx-filename"></span>
                                                <small class="text-muted d-block" id="shx-size"></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label"><i class="mdi mdi-file me-1"></i>File .dbf
                                                <span class="text-danger">*</span></label>
                                            <div class="upload-area" ondrop="handleDrop(event, 'dbf_file')"
                                                ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                                <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                                                <p class="upload-text">Drag & drop file .dbf atau klik untuk browse</p>
                                                <input type="file" class="form-control" id="dbf_file" name="dbf_file"
                                                    accept=".dbf" style="display: none;" onchange="handleFileSelect(this, 'dbf')">
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    onclick="document.getElementById('dbf_file').click()">
                                                    <i class="mdi mdi-folder-open me-1"></i>Browse File
                                                </button>
                                            </div>
                                            <div class="file-info" id="dbf-info">
                                                <i class="mdi mdi-check-circle text-success me-1"></i>
                                                <span id="dbf-filename"></span>
                                                <small class="text-muted d-block" id="dbf-size"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info alert-info-custom mt-3">
                                    <i class="mdi mdi-information me-2"></i>
                                    <strong>Catatan:</strong> Pastikan ketiga file (.shp, .shx, .dbf) memiliki nama
                                    yang sama dan berasal dari dataset yang sama. Satu baris data pada shapefile akan
                                    menjadi satu Data Spasial.
                                </div>
                            </div>

                            <!-- Koordinat -->
                            <div class="input-content" id="coordinates-content">
                                <div class="coord-input-group">
                                    <h6 class="mb-3"><i class="mdi mdi-map-marker me-2"></i>Input Koordinat Lokasi</h6>
                                    <div id="coordinate-inputs">
                                        <div class="coord-input-row">
                                            <div class="coord-field">
                                                <label class="form-label">Nama Lokasi</label>
                                                <input type="text" class="form-control coord-name"
                                                    name="coordinates[0][name]" placeholder="Nama lokasi (opsional)">
                                            </div>
                                            <div class="coord-field">
                                                <label class="form-label">Latitude <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control coord-lat"
                                                    name="coordinates[0][latitude]" step="any" placeholder="-6.123456">
                                            </div>
                                            <div class="coord-field">
                                                <label class="form-label">Longitude <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control coord-lng"
                                                    name="coordinates[0][longitude]" step="any" placeholder="106.123456">
                                            </div>
                                            <div class="coord-actions">
                                                <button type="button" class="btn btn-add-coord" onclick="addCoordinateInput()">
                                                    <i class="mdi mdi-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="mdi mdi-information me-1"></i>
                                        Format: Latitude (-90 sampai 90), Longitude (-180 sampai 180). Setiap baris
                                        akan menjadi satu Data Spasial terpisah (titik).
                                    </small>
                                </div>
                            </div>

                            <!-- KMZ -->
                            <div class="input-content" id="kmz-content">
                                <div class="mb-3">
                                    <label class="form-label"><i class="mdi mdi-file me-1"></i>File KMZ/KML
                                        <span class="text-danger">*</span></label>
                                    <div class="upload-area" ondrop="handleDrop(event, 'kmz_file')"
                                        ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                        <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                                        <p class="upload-text">Drag & drop file .kmz/.kml atau klik untuk browse</p>
                                        <input type="file" class="form-control" id="kmz_file" name="kmz_file"
                                            accept=".kmz,.kml" style="display: none;" onchange="handleFileSelect(this, 'kmz')">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="document.getElementById('kmz_file').click()">
                                            <i class="mdi mdi-folder-open me-1"></i>Browse File
                                        </button>
                                    </div>
                                    <div class="file-info" id="kmz-info">
                                        <i class="mdi mdi-check-circle text-success me-1"></i>
                                        <span id="kmz-filename"></span>
                                        <small class="text-muted d-block" id="kmz-size"></small>
                                    </div>
                                </div>
                                <div class="alert alert-info alert-info-custom">
                                    <i class="mdi mdi-information me-2"></i>
                                    <strong>Catatan:</strong> Setiap Placemark pada file KMZ/KML akan menjadi satu
                                    Data Spasial terpisah. Format yang didukung: .kmz dan .kml.
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <button type="button" class="btn btn-gradient-primary" onclick="handleStep1Next()" id="step1NextBtn">
                                    Lanjut <i class="mdi mdi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Metadata Dinamis -->
                        <div class="form-section" id="section-2">
                            <h5 class="mb-4">
                                <i class="mdi mdi-clipboard-text-outline me-2"></i>
                                Metadata{{ $layer->mapType ? ' (sesuai skema Jenis "'.$layer->mapType->nama.'")' : '' }}
                            </h5>

                            @include('backend.pages.spatial-layers.features._metadata-dinamis', ['dynamicAttributes' => $dynamicAttributes, 'feature' => null])

                            <hr>
                            @php
                                $regionLevelLabels = ['provinsi' => 'Provinsi', 'kabupaten_kota' => 'Kabupaten/Kota', 'kecamatan' => 'Kecamatan'];
                            @endphp
                            @if (($regionsByLevel ?? collect())->isNotEmpty())
                                <div class="mb-3">
                                    <label class="form-label">Penanda Wilayah</label>
                                    <select name="region_id" class="form-select">
                                        <option value="">-- Tidak ditandai --</option>
                                        @foreach ($regionsByLevel as $level => $regions)
                                            <optgroup label="{{ $regionLevelLabels[$level] ?? $level }}">
                                                @foreach ($regions as $region)
                                                    <option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('region_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                    <div class="form-text">Berlaku untuk semua Data Spasial yang dibuat dari satu kali submit ini.</div>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Gambar (opsional)</label>
                                <input type="file" name="gambar" class="form-control" accept="image/*">
                                @error('gambar') <div class="text-danger small">{{ $message }}</div> @enderror
                                <div class="form-text">Berlaku untuk semua Data Spasial yang dibuat dari satu kali
                                    submit ini.</div>
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

                        <!-- Step 3: Preview & Konfirmasi -->
                        <div class="form-section" id="section-3">
                            <h5 class="mb-4"><i class="mdi mdi-eye me-2"></i>Preview & Konfirmasi</h5>

                            <div class="summary-card">
                                <h6 class="card-title mb-3"><i class="mdi mdi-information me-2"></i>Ringkasan</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Layer:</strong> {{ $layer->name }}</p>
                                        <p><strong>Jenis:</strong> {{ $layer->mapType?->nama ?? '-' }}</p>
                                        <p><strong>Jenis Input:</strong> <span id="summary-input-type">-</span></p>
                                    </div>
                                    <div class="col-md-6" id="summary-files"></div>
                                </div>
                            </div>

                            <div class="progress-container">
                                <div class="progress mb-2">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-gradient-primary"
                                        role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted">Mengupload dan memproses data...</small>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary" onclick="prevStep(3)">
                                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                                </button>
                                <button type="submit" class="btn btn-gradient-success" id="submitBtn">
                                    <i class="mdi mdi-cloud-upload me-1"></i> Simpan Data
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
    <script>
        let currentStep = 1;
        let selectedInputType = '';
        let coordinateCount = 1;

        const storeUrl = @json(route('spatial-layers.features.store', $layer));
        const uploadUrl = @json(route('spatial-layers.imports.upload', $layer));

        function selectInputType(type) {
            selectedInputType = type;
            document.getElementById('input_type').value = type;

            document.querySelectorAll('.input-option').forEach((option) => option.classList.remove('selected'));
            document.querySelector(`[data-type="${type}"]`).classList.add('selected');

            document.querySelectorAll('.input-content').forEach((content) => content.classList.remove('active'));
            document.getElementById(`${type}-content`).classList.add('active');

            const form = document.getElementById('featureForm');
            const nextBtn = document.getElementById('step1NextBtn');

            if (type === 'coordinates') {
                form.action = storeUrl;
                nextBtn.innerHTML = 'Lanjut <i class="mdi mdi-arrow-right ms-1"></i>';
            } else {
                // Shapefile/KMZ: dipetakan 2 tahap (D.2) — submit di sini HANYA
                // mengunggah & parsing file, metadata/pemetaan kolom diisi di
                // halaman berikutnya (lihat handleStep1Next()).
                form.action = uploadUrl;
                nextBtn.innerHTML = 'Unggah & Lanjut Pemetaan <i class="mdi mdi-arrow-right ms-1"></i>';
            }
        }

        function handleStep1Next() {
            if (!validateStep(1)) {
                return;
            }

            if (selectedInputType === 'coordinates') {
                nextStep(1);
            } else {
                document.getElementById('featureForm').submit();
            }
        }

        function addCoordinateInput() {
            coordinateCount++;
            const container = document.getElementById('coordinate-inputs');
            const newRow = document.createElement('div');
            newRow.className = 'coord-input-row';
            newRow.innerHTML = `
                <div class="coord-field">
                    <label class="form-label">Nama Lokasi</label>
                    <input type="text" class="form-control coord-name" name="coordinates[${coordinateCount - 1}][name]"
                           placeholder="Nama lokasi (opsional)">
                </div>
                <div class="coord-field">
                    <label class="form-label">Latitude <span class="text-danger">*</span></label>
                    <input type="number" class="form-control coord-lat" name="coordinates[${coordinateCount - 1}][latitude]"
                           step="any" placeholder="-6.123456">
                </div>
                <div class="coord-field">
                    <label class="form-label">Longitude <span class="text-danger">*</span></label>
                    <input type="number" class="form-control coord-lng" name="coordinates[${coordinateCount - 1}][longitude]"
                           step="any" placeholder="106.123456">
                </div>
                <div class="coord-actions">
                    <button type="button" class="btn btn-add-coord" onclick="addCoordinateInput()">
                        <i class="mdi mdi-plus"></i>
                    </button>
                    <button type="button" class="btn btn-remove-coord" onclick="removeCoordinateInput(this)">
                        <i class="mdi mdi-minus"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
        }

        function removeCoordinateInput(button) {
            if (coordinateCount > 1) {
                button.closest('.coord-input-row').remove();
                coordinateCount--;
                updateCoordinateNames();
            }
        }

        function updateCoordinateNames() {
            document.querySelectorAll('.coord-input-row').forEach((row, index) => {
                row.querySelector('.coord-name').name = `coordinates[${index}][name]`;
                row.querySelector('.coord-lat').name = `coordinates[${index}][latitude]`;
                row.querySelector('.coord-lng').name = `coordinates[${index}][longitude]`;
            });
        }

        function nextStep(step) {
            if (!validateStep(step)) {
                return;
            }

            document.getElementById(`section-${step}`).classList.remove('active');
            document.getElementById(`step-${step}`).classList.remove('active');
            document.getElementById(`step-${step}`).classList.add('completed');

            const connectors = document.querySelectorAll('.step-connector');
            if (connectors[step - 1]) {
                connectors[step - 1].classList.add('completed');
            }

            currentStep = step + 1;
            document.getElementById(`section-${currentStep}`).classList.add('active');
            document.getElementById(`step-${currentStep}`).classList.add('active');

            if (currentStep === 3) {
                updateSummary();
            }

            document.querySelector('.card-body').scrollIntoView({ behavior: 'smooth' });
        }

        function prevStep(step) {
            document.getElementById(`section-${step}`).classList.remove('active');
            document.getElementById(`step-${step}`).classList.remove('active');

            currentStep = step - 1;
            document.getElementById(`section-${currentStep}`).classList.add('active');
            document.getElementById(`step-${currentStep}`).classList.add('active');
            document.getElementById(`step-${currentStep}`).classList.remove('completed');

            const connectors = document.querySelectorAll('.step-connector');
            if (connectors[step - 1]) {
                connectors[step - 1].classList.remove('completed');
            }

            document.querySelector('.card-body').scrollIntoView({ behavior: 'smooth' });
        }

        function validateStep(step) {
            if (step === 1) {
                if (!selectedInputType) {
                    Swal.fire({ icon: 'warning', title: 'Validasi Gagal', text: 'Pilih metode input data terlebih dahulu!' });
                    return false;
                }

                if (selectedInputType === 'shapefile') {
                    for (const fileId of ['shp_file', 'shx_file', 'dbf_file']) {
                        if (!document.getElementById(fileId).files.length) {
                            Swal.fire({ icon: 'warning', title: 'Validasi Gagal', text: `File ${fileId.replace('_file', '').toUpperCase()} harus dipilih!` });
                            return false;
                        }
                    }
                } else if (selectedInputType === 'coordinates') {
                    const latInputs = document.querySelectorAll('.coord-lat');
                    const lngInputs = document.querySelectorAll('.coord-lng');
                    let hasValidCoord = false;

                    for (let i = 0; i < latInputs.length; i++) {
                        const lat = latInputs[i].value.trim();
                        const lng = lngInputs[i].value.trim();

                        if (lat && lng) {
                            if (lat < -90 || lat > 90) {
                                Swal.fire({ icon: 'warning', title: 'Validasi Koordinat', text: `Latitude harus antara -90 sampai 90 (baris ${i + 1})` });
                                return false;
                            }
                            if (lng < -180 || lng > 180) {
                                Swal.fire({ icon: 'warning', title: 'Validasi Koordinat', text: `Longitude harus antara -180 sampai 180 (baris ${i + 1})` });
                                return false;
                            }
                            hasValidCoord = true;
                        }
                    }

                    if (!hasValidCoord) {
                        Swal.fire({ icon: 'warning', title: 'Validasi Gagal', text: 'Minimal satu koordinat harus diisi!' });
                        return false;
                    }
                } else if (selectedInputType === 'kmz') {
                    if (!document.getElementById('kmz_file').files.length) {
                        Swal.fire({ icon: 'warning', title: 'Validasi Gagal', text: 'File KMZ/KML harus dipilih!' });
                        return false;
                    }
                }
            }

            return true;
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.target.closest('.upload-area').classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.target.closest('.upload-area').classList.remove('dragover');
        }

        function handleDrop(e, inputId) {
            e.preventDefault();
            e.target.closest('.upload-area').classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById(inputId).files = files;
                handleFileSelect(document.getElementById(inputId), inputId.replace('_file', ''));
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        const allowedFileTypes = { shp: ['shp'], shx: ['shx'], dbf: ['dbf'], kmz: ['kmz', 'kml'] };

        function validateFileType(file, allowedTypes) {
            const fileExtension = file.name.split('.').pop().toLowerCase();
            return allowedTypes.includes(fileExtension);
        }

        function validateFileSize(file, maxSizeMB = 300) {
            return file.size <= maxSizeMB * 1024 * 1024;
        }

        function handleFileSelect(input, type) {
            const file = input.files[0];
            if (!file) return;

            if (!validateFileType(file, allowedFileTypes[type] || [])) {
                Swal.fire({ icon: 'error', title: 'File Tidak Valid', text: `File harus berformat .${(allowedFileTypes[type] || []).join(' atau .')}` });
                input.value = '';
                return;
            }

            if (!validateFileSize(file)) {
                Swal.fire({ icon: 'error', title: 'File Terlalu Besar', text: 'Ukuran file maksimal 300MB' });
                input.value = '';
                return;
            }

            document.getElementById(`${type}-filename`).textContent = file.name;
            document.getElementById(`${type}-size`).textContent = formatFileSize(file.size);
            document.getElementById(`${type}-info`).classList.add('show');
            input.closest('.upload-area').classList.add('uploaded');
        }

        function updateSummary() {
            const inputTypeLabels = {
                shapefile: 'Shapefile (.shp, .shx, .dbf)',
                coordinates: 'Input Koordinat Manual',
                kmz: 'File KMZ/KML',
            };
            document.getElementById('summary-input-type').textContent = inputTypeLabels[selectedInputType] || '-';

            const summaryFiles = document.getElementById('summary-files');
            let html = '';

            if (selectedInputType === 'shapefile') {
                html = `
                    <p><strong>File SHP:</strong> ${document.getElementById('shp_file').files[0]?.name || '-'}</p>
                    <p><strong>File SHX:</strong> ${document.getElementById('shx_file').files[0]?.name || '-'}</p>
                    <p><strong>File DBF:</strong> ${document.getElementById('dbf_file').files[0]?.name || '-'}</p>
                `;
            } else if (selectedInputType === 'coordinates') {
                const coordCount = document.querySelectorAll('.coord-input-row').length;
                let validCoords = 0;
                document.querySelectorAll('.coord-lat').forEach((input, index) => {
                    const lat = input.value.trim();
                    const lng = document.querySelectorAll('.coord-lng')[index].value.trim();
                    if (lat && lng) validCoords++;
                });
                html = `
                    <p><strong>Total Input:</strong> ${coordCount} baris</p>
                    <p><strong>Koordinat Valid:</strong> ${validCoords} titik</p>
                `;
            } else if (selectedInputType === 'kmz') {
                html = `<p><strong>File KMZ:</strong> ${document.getElementById('kmz_file').files[0]?.name || '-'}</p>`;
            }

            summaryFiles.innerHTML = html;
        }

        document.getElementById('featureForm').addEventListener('submit', function () {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Menyimpan...';
            document.querySelector('.progress-container').style.display = 'block';

            let progress = 0;
            const progressBar = document.querySelector('.progress-bar');
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 500);
            setTimeout(() => {
                clearInterval(interval);
                progressBar.style.width = '100%';
            }, 2000);
        });

        document.addEventListener('keydown', function (e) {
            if (e.altKey && e.key === 'n' && currentStep < 3) {
                e.preventDefault();
                nextStep(currentStep);
            }
            if (e.altKey && e.key === 'p' && currentStep > 1) {
                e.preventDefault();
                prevStep(currentStep);
            }
            if (e.altKey && e.key === 's' && currentStep === 3) {
                e.preventDefault();
                document.getElementById('submitBtn').click();
            }
        });

        @if (old('input_type'))
            selectInputType(@json(old('input_type')));
        @endif

        @php
            $step1ErrorKeys = ['input_type', 'shp_file', 'shx_file', 'dbf_file', 'kmz_file', 'coordinates'];
            $hasStep1Error = collect($errors->keys())->contains(
                fn($key) => in_array($key, $step1ErrorKeys) || str_starts_with($key, 'coordinates.'),
            );
        @endphp

        @if ($errors->any() && ! $hasStep1Error)
            // Error-nya ada di Metadata (Step 2) — lompat langsung ke sana tanpa lewat
            // validateStep(1) (file shapefile/KMZ tidak bisa di-restore dari old() oleh
            // browser, jadi gerbang validasi Step 1 akan selalu gagal setelah reload).
            document.getElementById('section-1').classList.remove('active');
            document.getElementById('step-1').classList.remove('active');
            document.getElementById('step-1').classList.add('completed');
            document.getElementById('section-2').classList.add('active');
            document.getElementById('step-2').classList.add('active');
            currentStep = 2;
        @endif
    </script>
@endpush
