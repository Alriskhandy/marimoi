@extends('backend.partials.main')

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

        .preview-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .preview-section.show {
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
            position: relative;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .category-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .category-badge {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 6px 15px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #495057;
        }

        .category-badge:hover {
            background: linear-gradient(135deg, #0d6efd, #0056b3);
            color: white;
            border-color: #0d6efd;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(13, 110, 253, 0.3);
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .progress-container {
            display: none;
            margin-top: 20px;
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

        .btn-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: all 0.3s ease;
        }

        .btn-gradient-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            border: none;
            transition: all 0.3s ease;
        }

        .btn-gradient-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(86, 171, 47, 0.3);
        }

        .step-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>
@endpush

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-map-marker-multiple"></i>
            </span>
            Tambah Data Proyek Strategis Nasional
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#!">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span></span>Data Spasial <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                </li>
            </ul>
        </nav>
    </div>
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">

                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step active" id="step-1">1</div>
                        <div class="step" id="step-2">2</div>
                        <div class="step" id="step-3">3</div>
                    </div>

                    <!-- Alert Messages -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Form -->
                    <form method="POST" action="{{ route('psn.store') }}" enctype="multipart/form-data" id="gisForm">
                        @csrf

                        <!-- Step 1: Basic Information -->
                        <div class="form-section active" id="section-1">
                            <h5 class="mb-4">
                                <i class="bi bi-info-circle me-2"></i>
                                Informasi Dasar
                            </h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="kategori" class="form-label">
                                            <i class="bi bi-tag me-1"></i>
                                            Kategori <span class="text-danger">*</span>
                                        </label>
                                        <select name="kategori_id" id="kategori" class="form-select" required>
                                            <option value="">-- Pilih Kategori --</option>
                                            @foreach ($kategoriLayers as $kategori)
                                                @include('backend.partials.kategori_option', [
                                                    'kategori' => $kategori,
                                                    'level' => 0,
                                                ])
                                            @endforeach
                                        </select>
                                        <div class="form-text">Pilih kategori sesuai jenis lokasi</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tahun" class="form-label">
                                            <i class="bi bi-calendar me-1"></i>
                                            Tahun <span class="text-danger">*</span>
                                        </label>
                                        <select name="tahun" id="tahun" class="form-select" required>
                                            <option value="">-- Pilih Tahun --</option>
                                            @for ($i = date('Y') + 2; $i >= 2000; $i--)
                                                <option value="{{ $i }}">{{ $i }}</option>
                                            @endfor
                                        </select>
                                        <div class="form-text">Pilih tahun proyek</div>
                                    </div>
                                </div>


                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="deskripsi" class="form-label">
                                            <i class="bi bi-textarea me-1"></i>
                                            Deskripsi
                                        </label>
                                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"
                                            placeholder="Masukkan deskripsi lokasi (opsional)">{{ old('deskripsi') }}</textarea>
                                        <div class="form-text">Deskripsi detail tentang lokasi ini.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="button" class="btn btn-primary" onclick="nextStep(1)">
                                    Lanjut <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: File Upload -->
                        <div class="form-section" id="section-2">
                            <h5 class="mb-4">
                                <i class="bi bi-cloud-upload me-2"></i>
                                Upload File Shapefile
                            </h5>

                            <div class="row">
                                <!-- SHP File -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-file-earmark-code me-1"></i>
                                            File .shp <span class="text-danger">*</span>
                                        </label>
                                        <div class="upload-area" ondrop="handleDrop(event, 'shp_file')"
                                            ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                            <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                                            <p class="mt-2 mb-2">Drag & drop file .shp atau klik untuk browse</p>
                                            <input type="file" class="form-control" id="shp_file" name="shp_file"
                                                accept=".shp" required style="display: none;"
                                                onchange="handleFileSelect(this, 'shp')">
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                onclick="document.getElementById('shp_file').click()">
                                                <i class="bi bi-folder2-open me-1"></i>Browse File
                                            </button>
                                        </div>
                                        <div class="file-info" id="shp-info">
                                            <i class="bi bi-file-check text-success me-1"></i>
                                            <span id="shp-filename"></span>
                                            <small class="text-muted d-block" id="shp-size"></small>
                                        </div>
                                    </div>
                                </div>

                                <!-- SHX File -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-file-earmark-text me-1"></i>
                                            File .shx <span class="text-danger">*</span>
                                        </label>
                                        <div class="upload-area" ondrop="handleDrop(event, 'shx_file')"
                                            ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                            <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                                            <p class="mt-2 mb-2">Drag & drop file .shx atau klik untuk browse</p>
                                            <input type="file" class="form-control" id="shx_file" name="shx_file"
                                                accept=".shx" required style="display: none;"
                                                onchange="handleFileSelect(this, 'shx')">
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                onclick="document.getElementById('shx_file').click()">
                                                <i class="bi bi-folder2-open me-1"></i>Browse File
                                            </button>
                                        </div>
                                        <div class="file-info" id="shx-info">
                                            <i class="bi bi-file-check text-success me-1"></i>
                                            <span id="shx-filename"></span>
                                            <small class="text-muted d-block" id="shx-size"></small>
                                        </div>
                                    </div>
                                </div>

                                <!-- DBF File -->
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                                            File .dbf <span class="text-danger">*</span>
                                        </label>
                                        <div class="upload-area" ondrop="handleDrop(event, 'dbf_file')"
                                            ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                            <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                                            <p class="mt-2 mb-2">Drag & drop file .dbf atau klik untuk browse</p>
                                            <input type="file" class="form-control" id="dbf_file" name="dbf_file"
                                                accept=".dbf" required style="display: none;"
                                                onchange="handleFileSelect(this, 'dbf')">
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                onclick="document.getElementById('dbf_file').click()">
                                                <i class="bi bi-folder2-open me-1"></i>Browse File
                                            </button>
                                        </div>
                                        <div class="file-info" id="dbf-info">
                                            <i class="bi bi-file-check text-success me-1"></i>
                                            <span id="dbf-filename"></span>
                                            <small class="text-muted d-block" id="dbf-size"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Catatan:</strong> Pastikan ketiga file (.shp, .shx, .dbf) memiliki nama yang
                                sama dan berasal dari dataset yang sama.
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="prevStep(2)">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </button>
                                <div>
                                    <button type="button" class="btn btn-outline-info me-2"
                                        onclick="previewShapefile()">
                                        <i class="bi bi-eye me-1"></i> Preview
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                                        Lanjut <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Preview & Confirm -->
                        <div class="form-section" id="section-3">
                            <h5 class="mb-4">
                                <i class="bi bi-eye me-2"></i>
                                Preview & Konfirmasi
                            </h5>

                            <!-- Summary -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Ringkasan Data</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Kategori:</strong> <span id="summary-kategori">-</span></p>
                                            <p><strong>Deskripsi:</strong> <span id="summary-deskripsi">-</span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>File SHP:</strong> <span id="summary-shp">-</span></p>
                                            <p><strong>File SHX:</strong> <span id="summary-shx">-</span></p>
                                            <p><strong>File DBF:</strong> <span id="summary-dbf">-</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview Section -->
                            <div class="preview-section" id="preview-section">
                                <h6><i class="bi bi-table me-2"></i>Preview Data</h6>
                                <div id="preview-content">
                                    <div class="text-center">
                                        <div class="loading-spinner">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2">Memproses shapefile...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress-container">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                                        role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted mt-1">Mengupload dan memproses data...</small>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary" onclick="prevStep(3)">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </button>
                                <button type="submit" class="btn btn-success" id="submitBtn">
                                    <i class="bi bi-cloud-upload me-1"></i> Upload Data
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script>
        let currentStep = 1;
        let uploadedFiles = {};

        // Step navigation
        function nextStep(step) {
            if (validateStep(step)) {
                document.getElementById(`section-${step}`).classList.remove('active');
                document.getElementById(`step-${step}`).classList.remove('active');
                document.getElementById(`step-${step}`).classList.add('completed');

                currentStep = step + 1;
                document.getElementById(`section-${currentStep}`).classList.add('active');
                document.getElementById(`step-${currentStep}`).classList.add('active');

                if (currentStep === 3) {
                    updateSummary();
                }
            }
        }

        function prevStep(step) {
            document.getElementById(`section-${step}`).classList.remove('active');
            document.getElementById(`step-${step}`).classList.remove('active');

            currentStep = step - 1;
            document.getElementById(`section-${currentStep}`).classList.add('active');
            document.getElementById(`step-${currentStep}`).classList.add('active');
            document.getElementById(`step-${currentStep}`).classList.remove('completed');
        }

        // Validation
        function validateStep(step) {
            if (step === 1) {
                const kategori = document.getElementById('kategori').value.trim();
                if (!kategori) {
                    alert('Kategori harus diisi!');
                    return false;
                }
            } else if (step === 2) {
                const requiredFiles = ['shp_file', 'shx_file', 'dbf_file'];
                for (let fileId of requiredFiles) {
                    if (!document.getElementById(fileId).files.length) {
                        alert(`File ${fileId.replace('_file', '').toUpperCase()} harus dipilih!`);
                        return false;
                    }
                }
            }
            return true;
        }

        // Category selection
        function selectCategory(category) {
            document.getElementById('kategori').value = category;
        }

        // File handling
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

        function handleFileSelect(input, type) {
            const file = input.files[0];
            if (file) {
                const filename = file.name;
                const size = formatFileSize(file.size);

                document.getElementById(`${type}-filename`).textContent = filename;
                document.getElementById(`${type}-size`).textContent = size;
                document.getElementById(`${type}-info`).classList.add('show');

                uploadedFiles[type] = file;

                // Update upload area
                const uploadArea = input.closest('.upload-area');
                uploadArea.style.borderColor = '#198754';
                uploadArea.style.backgroundColor = '#d1e7dd';
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Preview functionality
        function previewShapefile() {
            const formData = new FormData();
            const shpFile = document.getElementById('shp_file').files[0];
            const shxFile = document.getElementById('shx_file').files[0];
            const dbfFile = document.getElementById('dbf_file').files[0];

            if (!shpFile || !shxFile || !dbfFile) {
                alert('Semua file harus dipilih terlebih dahulu!');
                return;
            }

            formData.append('shp_file', shpFile);
            formData.append('shx_file', shxFile);
            formData.append('dbf_file', dbfFile);

            document.querySelector('.loading-spinner').style.display = 'block';
            document.getElementById('preview-section').classList.add('show');

            fetch('/debug-shapefile', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                            document.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.loading-spinner').style.display = 'none';

                    if (data.success) {
                        displayPreview(data);
                    } else {
                        document.getElementById('preview-content').innerHTML =
                            `<div class="alert alert-danger">Error: ${data.error}</div>`;
                    }
                })
                .catch(error => {
                    document.querySelector('.loading-spinner').style.display = 'none';
                    document.getElementById('preview-content').innerHTML =
                        `<div class="alert alert-danger">Terjadi kesalahan saat memproses file.</div>`;
                    console.error('Error:', error);
                });
        }

        function displayPreview(data) {
            let html = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>Berhasil!</strong> File shapefile valid dan berisi ${data.sample.length} sample data.
                </div>
            `;

            if (data.dbf_columns && data.dbf_columns.length > 0) {
                html += `
                    <h6>Kolom DBF (${data.total_columns} kolom):</h6>
                    <div class="mb-3">
                        ${data.dbf_columns.map(col => `<span class="badge bg-secondary me-1">${col}</span>`).join('')}
                    </div>
                `;
            }

            if (data.sample && data.sample.length > 0) {
                html += `
                    <h6>Sample Data:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Geometry Type</th>
                                    <th>Properties</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.sample.forEach((item, index) => {
                    const geometryType = item.geometry ? item.geometry.split('(')[0] : 'Unknown';
                    const properties = JSON.stringify(item.properties, null, 2);

                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><span class="badge bg-info">${geometryType}</span></td>
                            <td><pre class="mb-0" style="font-size: 10px; max-height: 100px; overflow-y: auto;">${properties}</pre></td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }

            document.getElementById('preview-content').innerHTML = html;
        }

        // Update summary
        function updateSummary() {
            document.getElementById('summary-kategori').textContent =
                document.getElementById('kategori').value || '-';
            document.getElementById('summary-deskripsi').textContent =
                document.getElementById('deskripsi').value || '-';
            document.getElementById('summary-shp').textContent =
                document.getElementById('shp_file').files[0]?.name || '-';
            document.getElementById('summary-shx').textContent =
                document.getElementById('shx_file').files[0]?.name || '-';
            document.getElementById('summary-dbf').textContent =
                document.getElementById('dbf_file').files[0]?.name || '-';
        }

        // Form submission with progress
        document.getElementById('gisForm').addEventListener('submit', function(e) {
            document.getElementById('submitBtn').disabled = true;
            document.querySelector('.progress-container').style.display = 'block';

            // Simulate progress
            let progress = 0;
            const progressBar = document.querySelector('.progress-bar');
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 500);

            // Clear interval after form submission
            setTimeout(() => {
                clearInterval(interval);
                progressBar.style.width = '100%';
            }, 3000);
        });
    </script>
@endsection
