@extends('backend.partials.main', ['title' => 'Input Data Spasial'])

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

        .input-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), rgba(0, 86, 179, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .input-option:hover {
            border-color: #0d6efd;
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.15);
        }

        .input-option:hover::before {
            opacity: 1;
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
            position: relative;
            z-index: 1;
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
            position: relative;
            z-index: 1;
        }

        .input-option.selected .option-title,
        .input-option:hover .option-title {
            color: #0d6efd;
        }

        .input-option .option-description {
            font-size: 0.85rem;
            color: #6c757d;
            line-height: 1.4;
            position: relative;
            z-index: 1;
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

        .data-type-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .badge-lokasi {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .badge-usulan_musrenbang {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }

        .badge-pokir_dprd {
            background: linear-gradient(135deg, #17a2b8, #007bff);
            color: white;
        }

        .badge-proyek_strategis {
            background: linear-gradient(135deg, #6f42c1, #e83e8c);
            color: white;
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

        .alert-info-custom {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            border: 1px solid #b8daff;
            border-radius: 10px;
        }

        .year-input-group {
            background: #ffffff;
            /* putih polos */
            border: 1px solid #dee2e6;
            /* abu muda seperti form biasa */
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #212529;
            /* teks gelap agar mudah dibaca */
        }
    </style>
@endpush

@section('main')
    @php
        // Dynamic configuration based on data type
        $dataTypeConfig = [
            'lokasi' => [
                'title' => 'Input Data Spasial Peta ',
                'icon' => 'mdi-map-marker-multiple',
                'breadcrumb' => 'Data Spasial Peta ',
                'badge_class' => 'badge-lokasi',
                'description' => 'Tambahkan lokasi untuk peta  Maluku Utara',
            ],
            'usulan_musrenbang' => [
                'title' => 'Input Data Usulan Musrenbang',
                'icon' => 'mdi-forum',
                'breadcrumb' => 'Usulan Musrenbang',
                'badge_class' => 'badge-usulan_musrenbang',
                'description' => 'Tambahkan data usulan hasil Musrenbang',
            ],
            'pokir_dprd' => [
                'title' => 'Input Data Pokir DPRD',
                'icon' => 'mdi-gavel',
                'breadcrumb' => 'Pokir DPRD',
                'badge_class' => 'badge-pokir_dprd',
                'description' => 'Tambahkan data Pokok Pikiran DPRD',
            ],
            'proyek_strategis' => [
                'title' => 'Input Data Proyek Strategis',
                'icon' => 'mdi-city',
                'breadcrumb' => 'Proyek Strategis',
                'badge_class' => 'badge-proyek_strategis',
                'description' => 'Tambahkan data proyek strategis',
            ],
        ];

        $config = $dataTypeConfig[$dataType] ?? $dataTypeConfig['lokasi'];

        // Get display type for current data type
        $displayTypes = [
            'lokasi' => 'Lokasi',
            'usulan_musrenbang' => 'Usulan Musrenbang',
            'pokir_dprd' => 'Pokir DPRD',
            'proyek_strategis' => 'Proyek Strategis',
        ];

        $displayType = $displayTypes[$dataType] ?? 'Data Spasial';
        if ($dataType === 'proyek_strategis' && isset($year)) {
            $displayType .= ' ' . ucfirst(request()->get('sub_type', 'psd')) . ' ' . $year;
        } elseif ($dataType === 'proyek_strategis') {
            $displayType .= ' ' . ucfirst(request()->get('sub_type', 'psn'));
        }
    @endphp

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="{{ $config['icon'] }}"></i>
            </span>
            {{ $config['title'] }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span></span>{{ $config['breadcrumb'] }} <i
                        class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                </li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <!-- Data Type Badge -->
                    <div class="text-center mb-4">
                        <span class="data-type-badge {{ $config['badge_class'] }}">
                            {{ $displayType }}
                        </span>
                        <p class="text-muted">{{ $config['description'] }}</p>
                    </div>

                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step active" id="step-1">1</div>
                        <div class="step-connector"></div>
                        <div class="step" id="step-2">2</div>
                        <div class="step-connector"></div>
                        <div class="step" id="step-3">3</div>
                    </div>

                    <!-- Alert Messages -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

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

                    <!-- Form -->
                    <form method="POST" action="{{ route('data-spatial.store') }}" enctype="multipart/form-data"
                        id="gisForm">
                        @csrf

                        <!-- Hidden Fields -->
                        <input type="hidden" name="data_type" value="{{ request()->get('type') }}">

                        @php
                            $subType = request()->get('sub_type');
                            $year = request()->get('year') ?? date('Y');
                        @endphp
                        <input type="hidden" name="sub_type" value="{{ $subType }}">
                        {{-- <input type="hidden" name="tahun" value="{{ $year }}"> --}}


                        <!-- Step 1: Basic Information -->
                        <div class="form-section active" id="section-1">
                            <h5 class="mb-4">
                                <i class="mdi mdi-information me-2"></i>
                                Informasi Dasar
                            </h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="kategori" class="form-label">
                                            <i class="mdi mdi-tag me-1"></i>
                                            Kategori <span class="text-danger">*</span>
                                        </label>
                                        <select name="kategori_id" id="kategori" class="form-select" required>
                                            <option value="">-- Pilih Kategori --</option>
                                            @foreach ($categories as $kategori)
                                                @include('backend.partials.kategori_option', [
                                                    'kategori' => $kategori,
                                                    'level' => 0,
                                                ])
                                            @endforeach
                                        </select>
                                        <div class="form-text">Pilih kategori sesuai jenis {{ strtolower($displayType) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="deskripsi" class="form-label">
                                            <i class="mdi mdi-text me-1"></i>
                                            Deskripsi
                                        </label>
                                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"
                                            placeholder="Masukkan deskripsi {{ strtolower($displayType) }} (opsional)">{{ old('deskripsi') }}</textarea>
                                        <div class="form-text">Deskripsi detail tentang {{ strtolower($displayType) }} ini.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($dataType === 'proyek_strategis')
                                <div class="year-input-group">
                                    <div class="row">
                                        @php
                                            $currentYear = date('Y');
                                            $endYear = $currentYear + 2;
                                        @endphp

                                        <div class="col-md-6">
                                            <label for="tahun" class="form-label">
                                                <i class="mdi mdi-calendar me-1"></i>
                                                Tahun <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select bg-white text-dark" id="tahun" name="tahun"
                                                required>
                                                @for ($year = $endYear; $year >= 2020; $year--)
                                                    <option value="{{ $year }}"
                                                        {{ old('tahun', $currentYear) == $year ? 'selected' : '' }}>
                                                        {{ $year }}
                                                    </option>
                                                @endfor
                                            </select>
                                            <div class="form-text">Tahun pelaksanaan proyek strategis</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="mdi mdi-information me-1"></i>
                                                Jenis Proyek
                                            </label>
                                            <div class="form-control-plaintext bg-white text-dark p-2 rounded border">
                                                <span class="badge badge-gradient-primary">
                                                    {{ ucfirst(request()->get('sub_type', 'daerah')) }}
                                                </span>
                                            </div>
                                            <div class="form-text">Jenis proyek strategis yang dipilih</div>
                                        </div>
                                    </div>
                                </div>
                            @endif


                            <div class="text-end">
                                <button type="button" class="btn btn-gradient-primary" onclick="nextStep(1)">
                                    Lanjut <i class="mdi mdi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Input Type Selection & Data Input -->
                        <div class="form-section" id="section-2">
                            <h5 class="mb-4">
                                <i class="mdi mdi-cloud-upload me-2"></i>
                                Pilih Jenis Input Data
                            </h5>

                            <!-- Input Type Selector -->
                            <div class="input-type-selector">
                                <h6 class="text-center mb-4">
                                    <i class="mdi mdi-cog me-2"></i>
                                    Pilih Metode Input Data Spasial
                                </h6>
                                <div class="input-options">
                                    <div class="input-option" data-type="shapefile"
                                        onclick="selectInputType('shapefile')">
                                        <div class="option-icon">
                                            <i class="mdi mdi-file-document-outline"></i>
                                        </div>
                                        <div class="option-title">Shapefile</div>
                                        <div class="option-description">Upload file .shp, .shx, dan .dbf untuk data vektor
                                            kompleks</div>
                                    </div>
                                    <div class="input-option" data-type="coordinates"
                                        onclick="selectInputType('coordinates')">
                                        <div class="option-icon">
                                            <i class="mdi mdi-map-marker"></i>
                                        </div>
                                        <div class="option-title">Koordinat</div>
                                        <div class="option-description">Input manual latitude dan longitude untuk titik
                                            lokasi</div>
                                    </div>
                                    <div class="input-option" data-type="kmz" onclick="selectInputType('kmz')">
                                        <div class="option-icon">
                                            <i class="mdi mdi-earth"></i>
                                        </div>
                                        <div class="option-title">File KMZ</div>
                                        <div class="option-description">Upload file KMZ dari Google Earth atau aplikasi GIS
                                            lainnya</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden input for selected type -->
                            <input type="hidden" name="input_type" id="input_type" value="">

                            <!-- Shapefile Input Content -->
                            <div class="input-content" id="shapefile-content">
                                <div class="row">
                                    <!-- SHP File -->
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="mdi mdi-file me-1"></i>
                                                File .shp <span class="text-danger">*</span>
                                            </label>
                                            <div class="upload-area" ondrop="handleDrop(event, 'shp_file')"
                                                ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                                <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                                                <p class="upload-text">Drag & drop file .shp atau klik untuk browse</p>
                                                <input type="file" class="form-control" id="shp_file"
                                                    name="shp_file" accept=".shp" style="display: none;"
                                                    onchange="handleFileSelect(this, 'shp')">
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

                                    <!-- SHX File -->
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="mdi mdi-file me-1"></i>
                                                File .shx <span class="text-danger">*</span>
                                            </label>
                                            <div class="upload-area" ondrop="handleDrop(event, 'shx_file')"
                                                ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                                <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                                                <p class="upload-text">Drag & drop file .shx atau klik untuk browse</p>
                                                <input type="file" class="form-control" id="shx_file"
                                                    name="shx_file" accept=".shx" style="display: none;"
                                                    onchange="handleFileSelect(this, 'shx')">
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

                                    <!-- DBF File -->
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="mdi mdi-file me-1"></i>
                                                File .dbf <span class="text-danger">*</span>
                                            </label>
                                            <div class="upload-area" ondrop="handleDrop(event, 'dbf_file')"
                                                ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                                <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                                                <p class="upload-text">Drag & drop file .dbf atau klik untuk browse</p>
                                                <input type="file" class="form-control" id="dbf_file"
                                                    name="dbf_file" accept=".dbf" style="display: none;"
                                                    onchange="handleFileSelect(this, 'dbf')">
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
                                    <strong>Catatan:</strong> Pastikan ketiga file (.shp, .shx, .dbf) memiliki nama yang
                                    sama dan berasal dari dataset yang sama.
                                </div>
                            </div>

                            <!-- Coordinates Input Content -->
                            <div class="input-content" id="coordinates-content">
                                <div class="coord-input-group">
                                    <h6 class="mb-3">
                                        <i class="mdi mdi-map-marker me-2"></i>
                                        Input Koordinat Lokasi
                                    </h6>
                                    <div id="coordinate-inputs">
                                        <div class="coord-input-row">
                                            <div class="coord-field">
                                                <label class="form-label">Nama Lokasi</label>
                                                <input type="text" class="form-control coord-name"
                                                    name="coordinates[0][name]" placeholder="Nama lokasi (opsional)">
                                            </div>
                                            <div class="coord-field">
                                                <label class="form-label">Latitude <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control coord-lat"
                                                    name="coordinates[0][latitude]" step="any"
                                                    placeholder="-6.123456">
                                            </div>
                                            <div class="coord-field">
                                                <label class="form-label">Longitude <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control coord-lng"
                                                    name="coordinates[0][longitude]" step="any"
                                                    placeholder="106.123456">
                                            </div>
                                            <div class="coord-actions">
                                                <button type="button" class="btn btn-add-coord"
                                                    onclick="addCoordinateInput()">
                                                    <i class="mdi mdi-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="mdi mdi-information me-1"></i>
                                            Format: Latitude (-90 sampai 90), Longitude (-180 sampai 180). Gunakan titik (.)
                                            untuk desimal.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- KMZ Input Content -->
                            <div class="input-content" id="kmz-content">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="mdi mdi-file me-1"></i>
                                        File KMZ/KML <span class="text-danger">*</span>
                                    </label>
                                    <div class="upload-area" ondrop="handleDrop(event, 'kmz_file')"
                                        ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                        <i class="mdi mdi-cloud-upload file-upload-icon"></i>
                                        <p class="upload-text">Drag & drop file .kmz/.kml atau klik untuk browse</p>
                                        <input type="file" class="form-control" id="kmz_file" name="kmz_file"
                                            accept=".kmz,.kml" style="display: none;"
                                            onchange="handleFileSelect(this, 'kmz')">
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
                                    <strong>Catatan:</strong> File KMZ harus berisi data lokasi yang valid. Format yang
                                    didukung: .kmz dan .kml
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary" onclick="prevStep(2)">
                                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                                </button>
                                <div>
                                    <button type="button" class="btn btn-outline-info me-2" id="previewBtn"
                                        onclick="previewData()" style="display: none;">
                                        <i class="mdi mdi-eye me-1"></i> Preview
                                    </button>
                                    <button type="button" class="btn btn-gradient-primary" onclick="nextStep(2)">
                                        Lanjut <i class="mdi mdi-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Preview & Confirm -->
                        <div class="form-section" id="section-3">
                            <h5 class="mb-4">
                                <i class="mdi mdi-eye me-2"></i>
                                Preview & Konfirmasi
                            </h5>

                            <!-- Summary -->
                            <div class="summary-card">
                                <h6 class="card-title mb-3">
                                    <i class="mdi mdi-information me-2"></i>Ringkasan Data
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Tipe Data:</strong> <span
                                                id="summary-data-type">{{ $displayType }}</span></p>
                                        <p><strong>Kategori:</strong> <span id="summary-kategori">-</span></p>
                                        <p><strong>Deskripsi:</strong> <span id="summary-deskripsi">-</span></p>
                                        @if ($dataType === 'proyek_strategis')
                                            <p><strong>Tahun:</strong> <span id="summary-tahun">{{ $year ?? '-' }}</span>
                                            </p>
                                            <p><strong>Jenis:</strong> <span
                                                    id="summary-sub-type">{{ ucfirst(request()->get('sub_type', 'daerah')) }}</span>
                                            </p>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Jenis Input:</strong> <span id="summary-input-type">-</span></p>
                                        <div id="summary-files">
                                            <!-- Dynamic summary based on input type -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview Section -->
                            <div class="preview-section" id="preview-section">
                                <h6><i class="mdi mdi-table me-2"></i>Preview Data</h6>
                                <div id="preview-content">
                                    <div class="text-center">
                                        <div class="loading-spinner">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2">Memproses data...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
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

    <!-- Toast Notification Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="mdi mdi-check-circle me-2"></i>
                    Data berhasil disimpan!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JS -->
    <script>
        let currentStep = 1;
        let uploadedFiles = {};
        let selectedInputType = '';
        let coordinateCount = 1;
        const dataType = '{{ $dataType }}';
        const subType = '{{ request()->get('sub_type', '') }}';

        // Input type selection
        function selectInputType(type) {
            selectedInputType = type;
            document.getElementById('input_type').value = type;

            // Update visual selection
            document.querySelectorAll('.input-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelector(`[data-type="${type}"]`).classList.add('selected');

            // Show/hide content sections
            document.querySelectorAll('.input-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`${type}-content`).classList.add('active');

            // Show/hide preview button based on type
            const previewBtn = document.getElementById('previewBtn');
            if (type === 'shapefile' || type === 'kmz') {
                previewBtn.style.display = 'inline-block';
            } else {
                previewBtn.style.display = 'none';
            }
        }

        // Coordinate input management
        function addCoordinateInput() {
            coordinateCount++;
            const container = document.getElementById('coordinate-inputs');
            const newRow = document.createElement('div');
            newRow.className = 'coord-input-row';
            newRow.innerHTML = `
                <div class="coord-field">
                    <label class="form-label">Nama Lokasi</label>
                    <input type="text" class="form-control coord-name" name="coordinates[${coordinateCount-1}][name]" 
                           placeholder="Nama lokasi (opsional)">
                </div>
                <div class="coord-field">
                    <label class="form-label">Latitude <span class="text-danger">*</span></label>
                    <input type="number" class="form-control coord-lat" name="coordinates[${coordinateCount-1}][latitude]" 
                           step="any" placeholder="-6.123456">
                </div>
                <div class="coord-field">
                    <label class="form-label">Longitude <span class="text-danger">*</span></label>
                    <input type="number" class="form-control coord-lng" name="coordinates[${coordinateCount-1}][longitude]" 
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
            const rows = document.querySelectorAll('.coord-input-row');
            rows.forEach((row, index) => {
                row.querySelector('.coord-name').name = `coordinates[${index}][name]`;
                row.querySelector('.coord-lat').name = `coordinates[${index}][latitude]`;
                row.querySelector('.coord-lng').name = `coordinates[${index}][longitude]`;
            });
        }

        // Step navigation
        function nextStep(step) {
            if (validateStep(step)) {
                // Update step indicators
                document.getElementById(`section-${step}`).classList.remove('active');
                document.getElementById(`step-${step}`).classList.remove('active');
                document.getElementById(`step-${step}`).classList.add('completed');

                // Update connector
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

                // Smooth scroll to top
                document.querySelector('.card-body').scrollIntoView({
                    behavior: 'smooth'
                });
            }
        }

        function prevStep(step) {
            document.getElementById(`section-${step}`).classList.remove('active');
            document.getElementById(`step-${step}`).classList.remove('active');

            currentStep = step - 1;
            document.getElementById(`section-${currentStep}`).classList.add('active');
            document.getElementById(`step-${currentStep}`).classList.add('active');
            document.getElementById(`step-${currentStep}`).classList.remove('completed');

            // Update connector
            const connectors = document.querySelectorAll('.step-connector');
            if (connectors[step - 1]) {
                connectors[step - 1].classList.remove('completed');
            }

            // Smooth scroll to top
            document.querySelector('.card-body').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Validation
        function validateStep(step) {
            if (step === 1) {
                const kategori = document.getElementById('kategori').value.trim();

                if (!kategori) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validasi Gagal',
                        text: 'Kategori harus diisi!',
                        confirmButtonColor: '#667eea',
                    });
                    return false;
                }

                // Validate year for strategic projects if required
                if (dataType === 'proyek_strategis') {
                    const tahunInput = document.getElementById('tahun');
                    if (tahunInput && !tahunInput.value.trim()) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validasi Gagal',
                            text: 'Tahun harus diisi untuk proyek strategis!',
                            confirmButtonColor: '#667eea',
                        });
                        return false;
                    }
                }

            } else if (step === 2) {
                if (!selectedInputType) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validasi Gagal',
                        text: 'Pilih jenis input data terlebih dahulu!',
                        confirmButtonColor: '#667eea',
                    });
                    return false;
                }

                if (selectedInputType === 'shapefile') {
                    const requiredFiles = ['shp_file', 'shx_file', 'dbf_file'];
                    for (let fileId of requiredFiles) {
                        if (!document.getElementById(fileId).files.length) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Validasi Gagal',
                                text: `File ${fileId.replace('_file', '').toUpperCase()} harus dipilih!`,
                                confirmButtonColor: '#667eea',
                            });
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
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Validasi Koordinat',
                                    text: `Latitude harus antara -90 sampai 90 (baris ${i + 1})`,
                                    confirmButtonColor: '#667eea',
                                });
                                return false;
                            }

                            if (lng < -180 || lng > 180) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Validasi Koordinat',
                                    text: `Longitude harus antara -180 sampai 180 (baris ${i + 1})`,
                                    confirmButtonColor: '#667eea',
                                });
                                return false;
                            }

                            hasValidCoord = true;
                        }
                    }

                    if (!hasValidCoord) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validasi Gagal',
                            text: 'Minimal satu koordinat harus diisi!',
                            confirmButtonColor: '#667eea',
                        });
                        return false;
                    }
                } else if (selectedInputType === 'kmz') {
                    if (!document.getElementById('kmz_file').files.length) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validasi Gagal',
                            text: 'File KMZ/KML harus dipilih!',
                            confirmButtonColor: '#667eea',
                        });
                        return false;
                    }
                }
            }
            return true;
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
                input.closest('.upload-area').classList.add('uploaded');
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
        function previewData() {
            if (selectedInputType === 'shapefile') {
                previewShapefile();
            } else if (selectedInputType === 'kmz') {
                previewKMZ();
            }
        }

        function previewShapefile() {
            const formData = new FormData();
            const shpFile = document.getElementById('shp_file').files[0];
            const shxFile = document.getElementById('shx_file').files[0];
            const dbfFile = document.getElementById('dbf_file').files[0];

            if (!shpFile || !shxFile || !dbfFile) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Tidak Lengkap',
                    text: 'Semua file (.shp, .shx, .dbf) harus dipilih terlebih dahulu!'
                });
                return;
            }

            formData.append('shp_file', shpFile);
            formData.append('shx_file', shxFile);
            formData.append('dbf_file', dbfFile);

            document.querySelector('.loading-spinner').style.display = 'block';
            document.getElementById('preview-section').classList.add('show');

            fetch('{{ route('data-spatial.debug.shapefile') }}', {
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
                        displayShapefilePreview(data);
                    } else {
                        document.getElementById('preview-content').innerHTML =
                            `<div class="alert alert-danger"><i class="mdi mdi-alert-circle me-2"></i>Error: ${data.error}</div>`;
                    }
                })
                .catch(error => {
                    document.querySelector('.loading-spinner').style.display = 'none';
                    document.getElementById('preview-content').innerHTML =
                        `<div class="alert alert-danger"><i class="mdi mdi-alert-circle me-2"></i>Terjadi kesalahan saat memproses file.</div>`;
                    console.error('Error:', error);
                });
        }

        function previewKMZ() {
            const formData = new FormData();
            const kmzFile = document.getElementById('kmz_file').files[0];

            if (!kmzFile) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Tidak Ada',
                    text: 'File KMZ/KML harus dipilih terlebih dahulu!'
                });
                return;
            }

            formData.append('kmz_file', kmzFile);

            document.querySelector('.loading-spinner').style.display = 'block';
            document.getElementById('preview-section').classList.add('show');

            fetch('{{ route('data-spatial.debug.kmz') }}', {
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
                        displayKMZPreview(data);
                    } else {
                        document.getElementById('preview-content').innerHTML =
                            `<div class="alert alert-danger"><i class="mdi mdi-alert-circle me-2"></i>Error: ${data.error}</div>`;
                    }
                })
                .catch(error => {
                    document.querySelector('.loading-spinner').style.display = 'none';
                    document.getElementById('preview-content').innerHTML =
                        `<div class="alert alert-danger"><i class="mdi mdi-alert-circle me-2"></i>Terjadi kesalahan saat memproses file KMZ.</div>`;
                    console.error('Error:', error);
                });
        }

        function displayShapefilePreview(data) {
            let html = `
                <div class="alert alert-success">
                    <i class="mdi mdi-check-circle me-2"></i>
                    <strong>Berhasil!</strong> File shapefile valid dan berisi ${data.sample.length} sample data.
                </div>
            `;

            if (data.dbf_columns && data.dbf_columns.length > 0) {
                html += `
                    <h6><i class="mdi mdi-table me-2"></i>Kolom DBF (${data.total_columns} kolom):</h6>
                    <div class="mb-3">
                        ${data.dbf_columns.map(col => `<span class="badge bg-secondary me-1 mb-1">${col}</span>`).join('')}
                    </div>
                `;
            }

            if (data.sample && data.sample.length > 0) {
                html += `
                    <h6><i class="mdi mdi-database me-2"></i>Sample Data:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tipe Geometri</th>
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

        function displayKMZPreview(data) {
            let html = `
                <div class="alert alert-success">
                    <i class="mdi mdi-check-circle me-2"></i>
                    <strong>Berhasil!</strong> File KMZ valid dan berisi ${data.total_features || data.features?.length || 0} fitur.
                </div>
            `;

            if (data.features && data.features.length > 0) {
                html += `
                    <h6><i class="mdi mdi-map me-2"></i>Preview Fitur KMZ:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Tipe Geometri</th>
                                    <th>Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.features.slice(0, 10).forEach((feature, index) => {
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${feature.name || 'N/A'}</td>
                            <td><span class="badge bg-info">${feature.geometry_type || 'Unknown'}</span></td>
                            <td>${feature.description ? (feature.description.length > 100 ? feature.description.substring(0, 100) + '...' : feature.description) : 'N/A'}</td>
                        </tr>
                    `;
                });

                if (data.features.length > 10) {
                    html += `
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                <i class="mdi mdi-dots-horizontal me-1"></i>dan ${data.features.length - 10} fitur lainnya
                            </td>
                        </tr>
                    `;
                }

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
                document.querySelector('#kategori option:checked').textContent || '-';
            document.getElementById('summary-deskripsi').textContent =
                document.getElementById('deskripsi').value || '-';

            // Update year for strategic projects
            const tahunSpan = document.getElementById('summary-tahun');
            if (tahunSpan) {
                const tahunInput = document.getElementById('tahun');
                tahunSpan.textContent = tahunInput ? tahunInput.value : '{{ $year ?? '-' }}';
            }

            // Update input type summary
            const inputTypeLabels = {
                'shapefile': 'Shapefile (.shp, .shx, .dbf)',
                'coordinates': 'Input Koordinat Manual',
                'kmz': 'File KMZ/KML'
            };
            document.getElementById('summary-input-type').textContent =
                inputTypeLabels[selectedInputType] || '-';

            // Update files summary based on input type
            const summaryFiles = document.getElementById('summary-files');
            let filesHtml = '';

            if (selectedInputType === 'shapefile') {
                filesHtml = `
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
                filesHtml = `
                    <p><strong>Total Input:</strong> ${coordCount} koordinat</p>
                    <p><strong>Koordinat Valid:</strong> ${validCoords} titik</p>
                `;
            } else if (selectedInputType === 'kmz') {
                filesHtml = `
                    <p><strong>File KMZ:</strong> ${document.getElementById('kmz_file').files[0]?.name || '-'}</p>
                `;
            }

            summaryFiles.innerHTML = filesHtml;
        }

        // Form submission with progress
        document.getElementById('gisForm').addEventListener('submit', function(e) {
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Menyimpan...';

            document.querySelector('.progress-container').style.display = 'block';

            // Simulate progress for user feedback
            let progress = 0;
            const progressBar = document.querySelector('.progress-bar');
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 500);

            // Clean up on actual form submission
            setTimeout(() => {
                clearInterval(interval);
                progressBar.style.width = '100%';
            }, 2000);

            // Re-enable button on error (form validation will prevent submission)
            setTimeout(() => {
                if (this.checkValidity && !this.checkValidity()) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    document.querySelector('.progress-container').style.display = 'none';
                }
            }, 100);
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-select first input type for better UX
            // selectInputType('coordinates');

            // Add form validation styling
            const form = document.getElementById('gisForm');
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });

            // Add tooltips to help users
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(tooltip => {
                new bootstrap.Tooltip(tooltip);
            });
        });

        // Utility functions
        function showToast(message, type = 'success') {
            const toast = document.getElementById('successToast');
            const toastBody = toast.querySelector('.toast-body');

            // Update content and styling
            toastBody.innerHTML =
                `<i class="mdi mdi-${type === 'success' ? 'check-circle' : 'alert-circle'} me-2"></i>${message}`;
            toast.className = `toast align-items-center text-white bg-${type} border-0`;

            // Show toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }

        // Add keyboard shortcuts for better UX
        document.addEventListener('keydown', function(e) {
            // Alt + N for next step
            if (e.altKey && e.key === 'n' && currentStep < 3) {
                e.preventDefault();
                nextStep(currentStep);
            }

            // Alt + P for previous step  
            if (e.altKey && e.key === 'p' && currentStep > 1) {
                e.preventDefault();
                prevStep(currentStep);
            }

            // Alt + S for submit (only on step 3)
            if (e.altKey && e.key === 's' && currentStep === 3) {
                e.preventDefault();
                document.getElementById('submitBtn').click();
            }
        });

        // Add file validation
        function validateFileType(file, allowedTypes) {
            const fileExtension = file.name.split('.').pop().toLowerCase();
            return allowedTypes.includes(fileExtension);
        }

        function validateFileSize(file, maxSizeMB = 50) {
            const maxSizeBytes = maxSizeMB * 1024 * 1024;
            return file.size <= maxSizeBytes;
        }

        // Enhanced file select with validation
        function handleFileSelect(input, type) {
            const file = input.files[0];
            if (!file) return;

            // Define allowed file types for each input type
            const allowedTypes = {
                'shp': ['shp'],
                'shx': ['shx'],
                'dbf': ['dbf'],
                'kmz': ['kmz', 'kml']
            };

            // Validate file type
            if (!validateFileType(file, allowedTypes[type] || [])) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Tidak Valid',
                    text: `File harus berformat .${allowedTypes[type]?.join(' atau .')}`,
                    confirmButtonColor: '#667eea'
                });
                input.value = '';
                return;
            }

            // Validate file size
            if (!validateFileSize(file)) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Terlalu Besar',
                    text: 'Ukuran file maksimal 50MB',
                    confirmButtonColor: '#667eea'
                });
                input.value = '';
                return;
            }

            // File is valid, proceed with display
            const filename = file.name;
            const size = formatFileSize(file.size);

            document.getElementById(`${type}-filename`).textContent = filename;
            document.getElementById(`${type}-size`).textContent = size;
            document.getElementById(`${type}-info`).classList.add('show');

            uploadedFiles[type] = file;

            // Update upload area visual state
            input.closest('.upload-area').classList.add('uploaded');

            // Show success animation
            const uploadArea = input.closest('.upload-area');
            uploadArea.style.transform = 'scale(1.02)';
            setTimeout(() => {
                uploadArea.style.transform = 'scale(1)';
            }, 200);
        }
    </script>
@endsection
