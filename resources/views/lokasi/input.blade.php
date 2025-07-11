<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data GIS</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .upload-area:hover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }

        .upload-area.dragover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
            transform: scale(1.02);
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
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: #6c757d;
            font-weight: bold;
            position: relative;
        }

        .step.active {
            background-color: #0d6efd;
            color: white;
        }

        .step.completed {
            background-color: #198754;
            color: white;
        }

        .step::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 20px;
            height: 2px;
            background-color: #dee2e6;
            margin-left: 10px;
        }

        .step:last-child::after {
            display: none;
        }

        .step.completed::after {
            background-color: #198754;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        .category-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }

        .category-badge {
            background-color: #e9ecef;
            border: none;
            border-radius: 15px;
            padding: 5px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .category-badge:hover {
            background-color: #0d6efd;
            color: white;
        }

        .loading-spinner {
            display: none;
        }

        .progress-container {
            display: none;
            margin-top: 20px;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <!-- Header -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="bi bi-geo-alt-fill me-2"></i>
                            Input Data Lokasi GIS
                        </h3>
                        <p class="mb-0 mt-2 opacity-75">Upload file shapefile dan atur informasi lokasi</p>
                    </div>

                    <div class="card-body p-4">
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
                        <form method="POST" action="{{ route('lokasi.store') }}" enctype="multipart/form-data"
                            id="gisForm">
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
                                            <input type="text" class="form-control" id="kategori" name="kategori"
                                                placeholder="Masukkan kategori lokasi" required
                                                value="{{ old('kategori') }}">
                                            <div class="form-text">Contoh: Hutan, Sawah, Permukiman, dll.</div>

                                            <!-- Category Suggestions -->
                                            <div class="category-suggestions">
                                                <button type="button" class="category-badge"
                                                    onclick="selectCategory('Ekonomi')">Ekonomi</button>
                                                <button type="button" class="category-badge"
                                                    onclick="selectCategory('Infrastruktur')">Infrastruktur</button>
                                                <button type="button" class="category-badge"
                                                    onclick="selectCategory('Kemiskinan')">Kemiskinan</button>
                                                <button type="button" class="category-badge"
                                                    onclick="selectCategory('Kependudukan')">Kependudukan</button>
                                                <button type="button" class="category-badge"
                                                    onclick="selectCategory('Kesehatan')">Kesehatan</button>
                                                <button type="button" class="category-badge"
                                                    onclick="selectCategory('Lingkungan Hidup')">Lingkungan
                                                    Hidup</button>
                                                <button type="button" class="category-badge"
                                                    onclick="selectCategory('Pariwisata & Kebudayaan')">Pariwisata &
                                                    Kebudayaan</button>
                                                <button type="button" class="category-badge"
                                                    onclick="selectCategory('Pendidikan')">Pendidikan</button>
                                                <button type="button" class="category-badge"
                                                    onclick="selectCategory('Sosial')">Sosial</button>
                                            </div>
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
                                                ondragover="handleDragOver(event)"
                                                ondragleave="handleDragLeave(event)">
                                                <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                                                <p class="mt-2 mb-2">Drag & drop file .shp atau klik untuk browse</p>
                                                <input type="file" class="form-control" id="shp_file"
                                                    name="shp_file" accept=".shp" required style="display: none;"
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
                                                ondragover="handleDragOver(event)"
                                                ondragleave="handleDragLeave(event)">
                                                <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                                                <p class="mt-2 mb-2">Drag & drop file .shx atau klik untuk browse</p>
                                                <input type="file" class="form-control" id="shx_file"
                                                    name="shx_file" accept=".shx" required style="display: none;"
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
                                                ondragover="handleDragOver(event)"
                                                ondragleave="handleDragLeave(event)">
                                                <i class="bi bi-cloud-upload fs-1 text-muted"></i>
                                                <p class="mt-2 mb-2">Drag & drop file .dbf atau klik untuk browse</p>
                                                <input type="file" class="form-control" id="dbf_file"
                                                    name="dbf_file" accept=".dbf" required style="display: none;"
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
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
</body>

</html>
