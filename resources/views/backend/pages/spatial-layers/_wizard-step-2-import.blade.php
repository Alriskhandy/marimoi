<h5 class="mb-1"><i class="mdi mdi-cloud-upload-outline me-2"></i>Impor Data Spasial</h5>
<p class="text-muted small mb-4">Unggah file geometri untuk Layer <strong>{{ $layer->name }}</strong>.</p>

@if ($failedImports->isNotEmpty())
    <div class="alert alert-warning">
        <i class="mdi mdi-alert-circle me-2"></i>
        <strong>Ada {{ $failedImports->count() }} percobaan impor sebelumnya yang gagal.</strong>
        File yang rusak tidak memengaruhi Layer ini — Informasi Layer yang sudah Anda simpan tetap aman.
        Perbaiki file sesuai pesan di bawah, lalu unggah ulang.
        <ul class="mb-0 mt-2">
            @foreach ($failedImports as $failedImport)
                <li>
                    <strong>{{ $failedImport->original_filename }}</strong>
                    ({{ $failedImport->created_at?->format('d/m/Y H:i') }}) &mdash;
                    {{ $failedImport->error_message }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('spatial-layers.wizard.import', $layer) }}" enctype="multipart/form-data"
    id="wizardImportForm">
    @csrf

    <div class="input-type-selector mb-4">
        <div class="input-options">
            <div class="input-option" data-type="shapefile" onclick="selectInputType('shapefile')">
                <div class="option-icon"><i class="mdi mdi-file-document-outline"></i></div>
                <div class="option-title">Shapefile</div>
                <div class="option-description">Upload file .shp, .shx, dan .dbf untuk data vektor kompleks</div>
            </div>
            <div class="input-option" data-type="kmz" onclick="selectInputType('kmz')">
                <div class="option-icon"><i class="mdi mdi-earth"></i></div>
                <div class="option-title">File KMZ/KML</div>
                <div class="option-description">Upload file dari Google Earth atau aplikasi GIS lainnya</div>
            </div>
            <div class="input-option" data-type="coordinates" onclick="selectInputType('coordinates')">
                <div class="option-icon"><i class="mdi mdi-map-marker"></i></div>
                <div class="option-title">Koordinat</div>
                <div class="option-description">Input manual latitude dan longitude, bisa lebih dari satu titik</div>
            </div>
        </div>
        <input type="hidden" name="input_type" id="input_type" value="{{ old('input_type', 'shapefile') }}">
    </div>
    @error('input_type')
        <div class="text-danger small mb-3">{{ $message }}</div>
    @enderror

    <!-- Shapefile -->
    <div class="input-content" id="shapefile-content">
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label"><i class="mdi mdi-file me-1"></i>File .shp <span class="text-danger">*</span></label>
                    <div class="upload-area" ondrop="handleDrop(event, 'shp_file')" ondragover="handleDragOver(event)"
                        ondragleave="handleDragLeave(event)">
                        <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                        <p class="upload-text">Drag & drop atau klik untuk browse</p>
                        <input type="file" class="form-control" id="shp_file" name="shp_file" accept=".shp"
                            style="display: none;" onchange="handleFileSelect(this, 'shp')">
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
                    @error('shp_file')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label"><i class="mdi mdi-file me-1"></i>File .shx <span class="text-danger">*</span></label>
                    <div class="upload-area" ondrop="handleDrop(event, 'shx_file')" ondragover="handleDragOver(event)"
                        ondragleave="handleDragLeave(event)">
                        <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                        <p class="upload-text">Drag & drop atau klik untuk browse</p>
                        <input type="file" class="form-control" id="shx_file" name="shx_file" accept=".shx"
                            style="display: none;" onchange="handleFileSelect(this, 'shx')">
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
                    @error('shx_file')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label"><i class="mdi mdi-file me-1"></i>File .dbf <span class="text-danger">*</span></label>
                    <div class="upload-area" ondrop="handleDrop(event, 'dbf_file')" ondragover="handleDragOver(event)"
                        ondragleave="handleDragLeave(event)">
                        <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                        <p class="upload-text">Drag & drop atau klik untuk browse</p>
                        <input type="file" class="form-control" id="dbf_file" name="dbf_file" accept=".dbf"
                            style="display: none;" onchange="handleFileSelect(this, 'dbf')">
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
                    @error('dbf_file')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
        <div class="alert alert-info alert-info-custom">
            <i class="mdi mdi-information me-2"></i>
            <strong>Catatan:</strong> Pastikan ketiga file (.shp, .shx, .dbf) memiliki nama yang sama dan
            berasal dari dataset yang sama. Satu baris data pada shapefile akan menjadi satu Data Spasial.
        </div>
    </div>

    <!-- KMZ -->
    <div class="input-content" id="kmz-content">
        <div class="mb-3">
            <label class="form-label"><i class="mdi mdi-file me-1"></i>File KMZ/KML <span class="text-danger">*</span></label>
            <div class="upload-area" ondrop="handleDrop(event, 'kmz_file')" ondragover="handleDragOver(event)"
                ondragleave="handleDragLeave(event)">
                <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                <p class="upload-text">Drag & drop file .kmz/.kml atau klik untuk browse</p>
                <input type="file" class="form-control" id="kmz_file" name="kmz_file" accept=".kmz,.kml"
                    style="display: none;" onchange="handleFileSelect(this, 'kmz')">
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
            @error('kmz_file')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>
        <div class="alert alert-info alert-info-custom">
            <i class="mdi mdi-information me-2"></i>
            <strong>Catatan:</strong> Setiap Placemark pada file KMZ/KML akan menjadi satu Data Spasial
            terpisah. Format yang didukung: .kmz dan .kml.
        </div>
    </div>

    <!-- Koordinat -->
    <div class="input-content" id="coordinates-content">
        <div class="coord-input-group">
            <h6 class="mb-3"><i class="mdi mdi-map-marker me-2"></i>Input Koordinat Lokasi</h6>
            <div id="coordinate-inputs">
                @php $oldCoordinates = old('coordinates', [['name' => '', 'latitude' => '', 'longitude' => '']]); @endphp
                @foreach ($oldCoordinates as $i => $coord)
                    <div class="coord-input-row">
                        <div class="coord-field">
                            <label class="form-label">Nama Lokasi</label>
                            <input type="text" class="form-control coord-name" name="coordinates[{{ $i }}][name]"
                                placeholder="Nama lokasi (opsional)" value="{{ $coord['name'] ?? '' }}">
                        </div>
                        <div class="coord-field">
                            <label class="form-label">Latitude <span class="text-danger">*</span></label>
                            <input type="number" class="form-control coord-lat" name="coordinates[{{ $i }}][latitude]"
                                step="any" placeholder="-6.123456" value="{{ $coord['latitude'] ?? '' }}">
                            @error('coordinates.'.$i.'.latitude')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="coord-field">
                            <label class="form-label">Longitude <span class="text-danger">*</span></label>
                            <input type="number" class="form-control coord-lng" name="coordinates[{{ $i }}][longitude]"
                                step="any" placeholder="106.123456" value="{{ $coord['longitude'] ?? '' }}">
                            @error('coordinates.'.$i.'.longitude')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="coord-actions">
                            @if ($loop->last)
                                <button type="button" class="btn btn-add-coord" onclick="addCoordinateInput()">
                                    <i class="mdi mdi-plus"></i>
                                </button>
                            @endif
                            @if (count($oldCoordinates) > 1)
                                <button type="button" class="btn btn-remove-coord" onclick="removeCoordinateInput(this)">
                                    <i class="mdi mdi-minus"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @error('coordinates')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
            <small class="text-muted d-block mt-2">
                <i class="mdi mdi-information me-1"></i>
                Format: Latitude (-90 sampai 90), Longitude (-180 sampai 180). Setiap baris akan menjadi satu
                Data Spasial terpisah (titik), langsung tersimpan tanpa tahap pemetaan kolom.
            </small>
        </div>
    </div>

    <div class="mode-import-group mt-2">
        <label class="form-label fw-semibold mb-2">Mode Impor</label>
        <div class="d-flex flex-column flex-md-row gap-2">
            <label class="mode-import-option" for="import_mode_append">
                <input type="radio" name="import_mode" id="import_mode_append" value="append"
                    @checked(old('import_mode', 'append') === 'append')>
                <div>
                    <div class="fw-semibold"><i class="mdi mdi-plus-circle-outline me-1"></i>Tambahkan</div>
                    <div class="text-muted small">Data baru ditambahkan ke data yang sudah ada</div>
                </div>
            </label>
            <label class="mode-import-option" for="import_mode_replace">
                <input type="radio" name="import_mode" id="import_mode_replace" value="replace"
                    @checked(old('import_mode') === 'replace')>
                <div>
                    <div class="fw-semibold text-danger"><i class="mdi mdi-swap-horizontal me-1"></i>Ganti</div>
                    <div class="text-muted small">Seluruh data lama dihapus, diganti hasil impor ini</div>
                </div>
            </label>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-between">
        <a href="{{ route('spatial-layers.wizard', [$layer, 'step' => 1]) }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left"></i> Kembali
        </a>
        <button type="submit" class="btn btn-gradient-primary" id="wizardImportSubmitBtn">
            Unggah & Lanjut <i class="mdi mdi-arrow-right"></i>
        </button>
    </div>
</form>

@push('styles')
    <style>
        .input-type-selector {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 25px;
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
        }

        .input-option:hover {
            border-color: #0d6efd;
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.15);
        }

        .input-option.selected {
            border-color: #0d6efd;
            background: linear-gradient(135deg, #e7f3ff, #cce7ff);
            transform: scale(1.03);
            box-shadow: 0 8px 30px rgba(13, 110, 253, 0.3);
        }

        .input-option .option-icon {
            font-size: 2.75rem;
            margin-bottom: 12px;
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
            font-size: 1.05rem;
            margin-bottom: 6px;
            color: #495057;
        }

        .input-option.selected .option-title,
        .input-option:hover .option-title {
            color: #0d6efd;
        }

        .input-option .option-description {
            font-size: 0.82rem;
            color: #6c757d;
            line-height: 1.4;
        }

        .input-content {
            display: none;
        }

        .input-content.active {
            display: block;
            animation: wizardFadeIn 0.3s ease-in;
        }

        @keyframes wizardFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 25px;
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

        .file-upload-icon {
            font-size: 2.2rem;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .upload-text {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 12px;
        }

        .file-info {
            background-color: #e9ecef;
            border-radius: 5px;
            padding: 8px 10px;
            margin-top: 8px;
            display: none;
        }

        .file-info.show {
            display: block;
        }

        .alert-info-custom {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            border: 1px solid #b8daff;
            border-radius: 10px;
        }

        .mode-import-group {
            background-color: #f8f9fa;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            padding: 1rem 1.1rem;
        }

        .mode-import-option {
            flex: 1;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            padding: 0.7rem 0.9rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .mode-import-option:has(input:checked) {
            border-color: #0d6efd;
            background: #e7f3ff;
            box-shadow: 0 0 0 1px #0d6efd inset;
        }

        .mode-import-option input[type="radio"] {
            margin-top: 0.3rem;
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
    </style>
@endpush

@push('scripts')
    <script>
        $(function() {
            const submitBtnLabels = {
                shapefile: 'Unggah & Lanjut Pemetaan <i class="mdi mdi-arrow-right"></i>',
                kmz: 'Unggah & Lanjut Pemetaan <i class="mdi mdi-arrow-right"></i>',
                coordinates: 'Simpan & Lanjut ke Review <i class="mdi mdi-arrow-right"></i>',
            };

            function selectInputType(type) {
                document.getElementById('input_type').value = type;

                document.querySelectorAll('.input-option').forEach((option) => option.classList.remove('selected'));
                document.querySelector(`[data-type="${type}"]`).classList.add('selected');

                document.querySelectorAll('.input-content').forEach((content) => content.classList.remove('active'));
                document.getElementById(`${type}-content`).classList.add('active');

                const submitBtn = document.getElementById('wizardImportSubmitBtn');
                if (submitBtn && submitBtnLabels[type]) {
                    submitBtn.innerHTML = submitBtnLabels[type];
                }
            }

            window.selectInputType = selectInputType;

            selectInputType(document.getElementById('input_type').value || 'shapefile');

            // Input Koordinat — tambah/hapus baris, pola sama dengan
            // features/create.blade.php (input koordinat Layer yang sudah jadi).
            let coordinateCount = document.querySelectorAll('.coord-input-row').length || 1;

            window.addCoordinateInput = function() {
                const container = document.getElementById('coordinate-inputs');
                const newRow = document.createElement('div');
                newRow.className = 'coord-input-row';
                newRow.innerHTML = `
                    <div class="coord-field">
                        <label class="form-label">Nama Lokasi</label>
                        <input type="text" class="form-control coord-name" name="coordinates[${coordinateCount}][name]"
                               placeholder="Nama lokasi (opsional)">
                    </div>
                    <div class="coord-field">
                        <label class="form-label">Latitude <span class="text-danger">*</span></label>
                        <input type="number" class="form-control coord-lat" name="coordinates[${coordinateCount}][latitude]"
                               step="any" placeholder="-6.123456">
                    </div>
                    <div class="coord-field">
                        <label class="form-label">Longitude <span class="text-danger">*</span></label>
                        <input type="number" class="form-control coord-lng" name="coordinates[${coordinateCount}][longitude]"
                               step="any" placeholder="106.123456">
                    </div>
                    <div class="coord-actions">
                        <button type="button" class="btn btn-remove-coord" onclick="removeCoordinateInput(this)">
                            <i class="mdi mdi-minus"></i>
                        </button>
                    </div>
                `;
                container.appendChild(newRow);
                coordinateCount++;
                refreshCoordinateAddButton();
            };

            window.removeCoordinateInput = function(button) {
                if (document.querySelectorAll('.coord-input-row').length <= 1) {
                    return;
                }
                button.closest('.coord-input-row').remove();
                updateCoordinateNames();
                refreshCoordinateAddButton();
            };

            function updateCoordinateNames() {
                document.querySelectorAll('.coord-input-row').forEach((row, index) => {
                    row.querySelector('.coord-name').name = `coordinates[${index}][name]`;
                    row.querySelector('.coord-lat').name = `coordinates[${index}][latitude]`;
                    row.querySelector('.coord-lng').name = `coordinates[${index}][longitude]`;
                });
                coordinateCount = document.querySelectorAll('.coord-input-row').length;
            }

            // Tombol "+" cuma ada satu, selalu di baris terakhir — dipindah
            // ke sana setiap tambah/hapus baris, bukan dirender ulang per baris.
            function refreshCoordinateAddButton() {
                document.querySelectorAll('.btn-add-coord').forEach((btn) => btn.closest('.coord-actions')?.removeChild(btn));

                const rows = document.querySelectorAll('.coord-input-row');
                const lastActions = rows[rows.length - 1]?.querySelector('.coord-actions');
                if (!lastActions) {
                    return;
                }

                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'btn btn-add-coord';
                addBtn.innerHTML = '<i class="mdi mdi-plus"></i>';
                addBtn.addEventListener('click', addCoordinateInput);
                lastActions.insertBefore(addBtn, lastActions.firstChild);
            }

            window.handleDragOver = function(e) {
                e.preventDefault();
                e.target.closest('.upload-area').classList.add('dragover');
            };

            window.handleDragLeave = function(e) {
                e.target.closest('.upload-area').classList.remove('dragover');
            };

            window.handleDrop = function(e, inputId) {
                e.preventDefault();
                e.target.closest('.upload-area').classList.remove('dragover');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    document.getElementById(inputId).files = files;
                    handleFileSelect(document.getElementById(inputId), inputId.replace('_file', ''));
                }
            };

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            window.handleFileSelect = function(input, type) {
                const file = input.files[0];
                if (!file) return;

                document.getElementById(`${type}-filename`).textContent = file.name;
                document.getElementById(`${type}-size`).textContent = formatFileSize(file.size);
                document.getElementById(`${type}-info`).classList.add('show');
                input.closest('.upload-area').classList.add('uploaded');
            };
        });
    </script>
@endpush
