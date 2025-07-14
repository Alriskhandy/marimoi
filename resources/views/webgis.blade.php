</body>

</html>
<!DOCTYPE html>
<html lang="id" data-bs-theme="auto">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="WebGIS Perencanaan - Sistem Informasi Geografis berbasis web untuk perencanaan wilayah">
    <meta name="keywords" content="WebGIS, Peta, GIS, Perencanaan, Ternate">
    <meta name="author" content="WebGIS Team">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>WebGIS Perencanaan - Peta Interaktif</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <!-- Custom Styles -->
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        /* Header Styles */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1000;
            height: 60px;
            min-height: 60px;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            color: white !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .navbar-nav .nav-link:hover {
            color: white !important;
            text-shadow: 0 2px 8px rgba(255, 255, 255, 0.3);
        }

        /* Map Container */
        .map-container {
            height: calc(100vh - 60px);
            position: relative;
            overflow: hidden;
        }

        #map {
            height: 100%;
            width: 100%;
            z-index: 1;
        }

        /* Sidebar Styles */
        .sidebar {
            position: absolute;
            top: 0;
            left: 0;
            width: 350px;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 0 15px 15px 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            z-index: 100;
            overflow-y: auto;
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .sidebar-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1rem 1.25rem;
            margin: 0;
            border-radius: 0 15px 0 0;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .sidebar-header h6 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .sidebar-content {
            padding: 1.25rem;
            height: calc(100% - 80px);
            overflow-y: auto;
        }

        /* Enhanced Button Groups */
        .control-buttons {
            position: absolute;
            z-index: 99;
            background: rgba(90, 90, 90, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            padding: 0.25rem;
        }

        .control-buttons-right {
            top: 20px;
            right: 20px;
        }

        .control-buttons-bottom {
            bottom: 20px;
            right: 20px;
        }

        .control-buttons .btn {
            color: white;
            border: none;
            margin: 2px;
            padding: 0.5rem;
            transition: all 0.2s ease;
            background: transparent;
            border-radius: 6px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .control-buttons .btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
            color: white;
        }

        .control-buttons .btn:focus {
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
            color: white;
        }

        .control-buttons .btn.active {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        /* Category Styles */
        .category-dropdown .card {
            transition: all 0.2s ease;
            border: 1px solid #e9ecef;
            margin-bottom: 0.75rem;
            border-radius: 10px;
            overflow: hidden;
        }

        .category-dropdown .card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .category-dropdown .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
        }

        .category-dropdown .category-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }

        .category-dropdown .form-check-input {
            cursor: pointer;
        }

        .category-dropdown .form-check-label {
            cursor: pointer;
            font-weight: 500;
        }

        .child-category {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .child-category:last-child {
            border-bottom: none;
        }

        /* Legend Styles */
        .legend-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
        }

        .legend-item {
            transition: all 0.2s ease;
            padding: 0.5rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }

        .legend-item:hover {
            background: #f8f9fa;
        }

        .root-legend {
            border-left: 4px solid var(--primary-color);
            padding-left: 1rem;
            margin-bottom: 1rem;
            background: rgba(102, 126, 234, 0.05);
        }

        .child-legend {
            border-left: 2px solid #6c757d;
            padding-left: 0.75rem;
            margin-left: 1rem;
            background: rgba(108, 117, 125, 0.05);
        }

        /* Enhanced Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 500;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        /* Toast Styles */
        .toast-container {
            z-index: 9999;
        }

        .toast {
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
        }

        /* Highlighted Control Animation */
        .highlighted-control {
            position: relative !important;
            z-index: 1051 !important;
            animation: highlight-pulse 2s infinite;
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.6) !important;
            border-radius: 8px !important;
        }

        @keyframes highlight-pulse {
            0% {
                box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.6);
            }

            50% {
                box-shadow: 0 0 0 6px rgba(255, 193, 7, 0.3);
            }

            100% {
                box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.6);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: 50%;
                top: auto;
                bottom: 0;
                border-radius: 15px 15px 0 0;
                transform: translateY(100%);
            }

            .sidebar.show {
                transform: translateY(0);
            }

            .control-buttons {
                flex-direction: row;
                position: fixed;
                top: 70px;
                left: 50%;
                transform: translateX(-50%);
                right: auto;
                bottom: auto;
            }

            .control-buttons-bottom {
                bottom: 10px;
                right: 10px;
                top: auto;
                left: auto;
                transform: none;
            }
        }

        /* Custom Scrollbar */
        .sidebar-content::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-geo-alt-fill me-2"></i>
                WebGIS Perencanaan
            </a>

            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        Menu
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-house me-2"></i>Beranda</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-info-circle me-2"></i>Tentang</a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Map Container -->
    <div class="map-container">
        <!-- Map -->
        <div id="map"></div>

        <!-- Control Buttons - Right Side -->
        <div class="control-buttons control-buttons-right">
            <button id="btn-toggle-sidebar-help" type="button" class="btn" title="Bantuan" data-bs-toggle="tooltip"
                data-bs-placement="left">
                <i class="bi bi-info-circle-fill"></i>
            </button>
            <button id="btn-toggle-sidebar-legend" type="button" class="btn" title="Legenda Peta"
                data-bs-toggle="tooltip" data-bs-placement="left">
                <i class="bi bi-list-ul"></i>
            </button>
            <button id="btn-toggle-sidebar-basemap" type="button" class="btn" title="Basemap Peta"
                data-bs-toggle="tooltip" data-bs-placement="left">
                <i class="bi bi-grid-fill"></i>
            </button>
            <button id="btn-toggle-sidebar-layer" type="button" class="btn" title="Layer Peta"
                data-bs-toggle="tooltip" data-bs-placement="left">
                <i class="bi bi-layers-fill"></i>
            </button>
        </div>

        <!-- Control Buttons - Bottom Right -->
        <div class="control-buttons control-buttons-bottom">
            <button id="btn-toggle-sidebar-download" type="button" class="btn" title="Download Peta"
                data-bs-toggle="tooltip" data-bs-placement="left">
                <i class="bi bi-file-earmark-arrow-down-fill"></i>
            </button>
            <button id="btn-fullscreen" type="button" class="btn" title="Tampilan Penuh" data-bs-toggle="tooltip"
                data-bs-placement="left">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <button id="btn-default-zoom" type="button" class="btn" title="Reset Zoom" data-bs-toggle="tooltip"
                data-bs-placement="left">
                <i class="bi bi-house-door-fill"></i>
            </button>
        </div>

        <!-- Sidebar Layer -->
        <div id="sidebar-layer" class="sidebar">
            <div class="sidebar-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6><i class="bi bi-layers-fill me-2"></i>Layer Peta</h6>
                    <button id="btn-close-sidebar-layer" class="btn btn-sm text-white">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="sidebar-content">
                <div class="mb-3">
                    <label for="transparency" class="form-label">
                        <i class="bi bi-transparency me-2"></i>Transparansi Layer
                    </label>
                    <input type="range" class="form-range" min="0" max="100" value="100"
                        id="transparency">
                    <div class="d-flex justify-content-between small text-muted">
                        <span>0%</span>
                        <span>100%</span>
                    </div>
                </div>

                <div class="mb-3">
                    <input type="text" id="layer-search" class="form-control" placeholder="🔍 Cari kategori..."
                        autocomplete="off">
                </div>

                <div id="layer-list">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Memuat kategori...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Basemap -->
        <div id="sidebar-basemap" class="sidebar">
            <div class="sidebar-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6><i class="bi bi-grid-fill me-2"></i>Basemap</h6>
                    <button id="btn-close-sidebar-basemap" class="btn btn-sm text-white">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="sidebar-content">
                <div id="basemap-list">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Memuat basemap...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Legend -->
        <div id="sidebar-legend" class="sidebar">
            <div class="sidebar-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6><i class="bi bi-palette-fill me-2"></i>Legenda</h6>
                    <button id="btn-close-sidebar-legend" class="btn btn-sm text-white">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="sidebar-content">
                <div id="legend-content">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Memuat legenda...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Download -->
        <div id="sidebar-download" class="sidebar">
            <div class="sidebar-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6><i class="bi bi-download me-2"></i>Download Peta</h6>
                    <button id="btn-close-sidebar-download" class="btn btn-sm text-white">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="sidebar-content">
                <div id="download-content">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Menyiapkan opsi download...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guide Modal -->
    <div class="modal fade" id="guideModal" tabindex="-1" aria-labelledby="guideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="guideModalLabel">
                        <i class="bi bi-compass me-2"></i>Panduan Penggunaan WebGIS
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="guide-step" data-step="1">
                        <div class="text-center mb-3">
                            <i class="bi bi-geo-alt-fill" style="font-size: 3rem; color: var(--primary-color);"></i>
                        </div>
                        <h6 class="fw-bold text-primary">Selamat Datang di WebGIS Perencanaan!</h6>
                        <p>Sistem informasi geografis berbasis web ini membantu Anda dalam perencanaan wilayah.
                            Gunakan tombol-tombol kontrol untuk mengatur tampilan peta sesuai kebutuhan.</p>
                    </div>

                    <div class="guide-step d-none" data-step="2">
                        <div class="text-center mb-3">
                            <i class="bi bi-zoom-in" style="font-size: 2.5rem; color: var(--success-color);"></i>
                        </div>
                        <h6 class="fw-bold text-success">Kontrol Zoom Peta</h6>
                        <p>Gunakan kontrol zoom bawaan Leaflet <strong>(+ dan -)</strong> di pojok kiri atas peta,
                            atau scroll mouse untuk mengatur tingkat zoom peta sesuai kebutuhan.</p>
                    </div>

                    <div class="guide-step d-none" data-step="3">
                        <div class="text-center mb-3">
                            <i class="bi bi-info-circle-fill"
                                style="font-size: 2.5rem; color: var(--info-color);"></i>
                        </div>
                        <h6 class="fw-bold text-info">Tombol Bantuan</h6>
                        <p>Tombol <strong><i class="bi bi-info-circle-fill"></i> Bantuan</strong>
                            dapat digunakan untuk menampilkan panduan ini kapan saja. Gunakan shortcut <kbd>Ctrl +
                                H</kbd>
                            untuk akses cepat.</p>
                    </div>

                    <div class="guide-step d-none" data-step="4">
                        <div class="text-center mb-3">
                            <i class="bi bi-list-ul" style="font-size: 2.5rem; color: var(--warning-color);"></i>
                        </div>
                        <h6 class="fw-bold text-warning">Legenda Peta</h6>
                        <p>Tombol <strong><i class="bi bi-list-ul"></i> Legenda Peta</strong>
                            menampilkan keterangan warna dan simbol yang digunakan pada peta.
                            Legenda akan update otomatis berdasarkan layer yang aktif.</p>
                    </div>

                    <div class="guide-step d-none" data-step="5">
                        <div class="text-center mb-3">
                            <i class="bi bi-grid-fill" style="font-size: 2.5rem; color: var(--secondary-color);"></i>
                        </div>
                        <h6 class="fw-bold" style="color: var(--secondary-color);">Basemap Peta</h6>
                        <p>Tombol <strong><i class="bi bi-grid-fill"></i> Basemap Peta</strong>
                            digunakan untuk memilih jenis peta dasar: OpenStreetMap, Google Maps (Roadmap, Hybrid,
                            Terrain),
                            atau ESRI World Imagery. Gunakan shortcut <kbd>Ctrl + B</kbd>.</p>
                    </div>

                    <div class="guide-step d-none" data-step="6">
                        <div class="text-center mb-3">
                            <i class="bi bi-layers-fill" style="font-size: 2.5rem; color: var(--primary-color);"></i>
                        </div>
                        <h6 class="fw-bold text-primary">Layer Peta</h6>
                        <p>Tombol <strong><i class="bi bi-layers-fill"></i> Layer Peta</strong>
                            digunakan untuk mengatur layer yang ingin ditampilkan. Anda dapat memilih kategori secara
                            hierarkis dan mengatur transparansi. Gunakan shortcut <kbd>Ctrl + L</kbd>.</p>
                    </div>

                    <div class="guide-step d-none" data-step="7">
                        <div class="text-center mb-3">
                            <i class="bi bi-file-earmark-arrow-down-fill"
                                style="font-size: 2.5rem; color: var(--success-color);"></i>
                        </div>
                        <h6 class="fw-bold text-success">Download Peta</h6>
                        <p>Tombol <strong><i class="bi bi-file-earmark-arrow-down-fill"></i> Download Peta</strong>
                            memungkinkan Anda mengunduh peta dalam berbagai format: PNG, JPG, GeoJSON, CSV, dan KML.
                            Pilih format sesuai kebutuhan analisis Anda.</p>
                    </div>

                    <div class="guide-step d-none" data-step="8">
                        <div class="text-center mb-3">
                            <i class="bi bi-arrows-fullscreen"
                                style="font-size: 2.5rem; color: var(--dark-color);"></i>
                        </div>
                        <h6 class="fw-bold text-dark">Mode Fullscreen</h6>
                        <p>Tombol <strong><i class="bi bi-arrows-fullscreen"></i> Fullscreen</strong>
                            memungkinkan Anda masuk dan keluar dari tampilan layar penuh untuk pengalaman pemetaan
                            yang lebih immersive. Gunakan <kbd>F11</kbd> atau <kbd>Esc</kbd>.</p>
                    </div>

                    <div class="guide-step d-none" data-step="9">
                        <div class="text-center mb-3">
                            <i class="bi bi-house-door-fill"
                                style="font-size: 2.5rem; color: var(--danger-color);"></i>
                        </div>
                        <h6 class="fw-bold text-danger">Reset ke Posisi Awal</h6>
                        <p>Tombol <strong><i class="bi bi-house-door-fill"></i> Home</strong>
                            memungkinkan Anda kembali ke posisi dan zoom default peta. Berguna ketika Anda
                            sudah terlalu jauh menjelajahi peta dan ingin kembali ke area utama.</p>
                    </div>

                    <div class="guide-step d-none" data-step="10">
                        <div class="text-center mb-3">
                            <i class="bi bi-keyboard" style="font-size: 2.5rem; color: var(--info-color);"></i>
                        </div>
                        <h6 class="fw-bold text-info">Keyboard Shortcuts</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled small">
                                    <li><kbd>Ctrl + H</kbd> - Bantuan</li>
                                    <li><kbd>Ctrl + L</kbd> - Layer</li>
                                    <li><kbd>Ctrl + B</kbd> - Basemap</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled small">
                                    <li><kbd>F11</kbd> - Fullscreen</li>
                                    <li><kbd>Esc</kbd> - Tutup sidebar</li>
                                </ul>
                            </div>
                        </div>
                        <p class="small text-muted">Gunakan shortcut ini untuk navigasi yang lebih cepat!</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex justify-content-between w-100">
                        <div>
                            <span class="badge bg-secondary" id="step-indicator">1 / 10</span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-secondary me-2" id="btnSkip">
                                <button type="button" class="btn btn-outline-secondary me-2" id="btnSkip">
                                    <i class="bi bi-skip-end me-1"></i>Lewati
                                </button>
                                <button type="button" class="btn btn-outline-primary me-2" id="btnPrev" disabled>
                                    <i class="bi bi-chevron-left me-1"></i>Sebelumnya
                                </button>
                                <button type="button" class="btn btn-primary" id="btnNext">
                                    Selanjutnya<i class="bi bi-chevron-right ms-1"></i>
                                </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

    <!-- Scripts -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Fixed JavaScript -->
    <script>
        // Fixed WebGIS Application - Khusus untuk data LineString Anda
        console.log("WebGIS Application loaded - Fixed for LineString data");

        // Global variables
        let layerGroups = {};
        let parentLayerGroups = {};
        let currentBaseMap = null;
        let allCategoriesData = [];
        let allFeaturesData = [];

        // Map configuration
        const mapConfig = {
            center: [0.78, 127.35], // Koordinat tengah Ternate berdasarkan data Anda
            zoom: 12,
            baseMapsList: [{
                    id: "osm",
                    label: "OpenStreetMap",
                    url: "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                },
                {
                    id: "google-roadmap",
                    label: "Google Map (Roadmap)",
                    url: "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",
                    subdomains: ["mt0", "mt1", "mt2", "mt3"],
                    attribution: '&copy; <a href="https://maps.google.com">Google Maps</a>'
                },
                {
                    id: "google-hybrid",
                    label: "Google Map (Hybrid)",
                    url: "https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}",
                    subdomains: ["mt0", "mt1", "mt2", "mt3"],
                    attribution: '&copy; <a href="https://maps.google.com">Google Maps</a>'
                },
                {
                    id: "google-terrain",
                    label: "Google Map (Terrain)",
                    url: "https://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}",
                    subdomains: ["mt0", "mt1", "mt2", "mt3"],
                    attribution: '&copy; <a href="https://maps.google.com">Google Maps</a>'
                },
                {
                    id: "esri-world-imagery",
                    label: "ESRI World Imagery",
                    url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
                    attribution: '&copy; <a href="https://www.esri.com">ESRI</a>'
                },
            ],
        };

        // Initialize map
        const map = L.map("map", {
            zoomControl: true,
            attributionControl: true,
        }).setView(mapConfig.center, mapConfig.zoom);

        // Enhanced toast notifications
        function showAlert(message, type = "info") {
            console.log(`${type}: ${message}`);

            const toastContainer = document.getElementById("toast-container");
            if (!toastContainer) {
                const container = document.createElement("div");
                container.id = "toast-container";
                container.className = "toast-container position-fixed top-0 end-0 p-3";
                container.style.zIndex = "9999";
                document.body.appendChild(container);
            }

            const iconMap = {
                success: 'bi-check-circle-fill',
                error: 'bi-x-circle-fill',
                danger: 'bi-x-circle-fill',
                warning: 'bi-exclamation-triangle-fill',
                info: 'bi-info-circle-fill'
            };

            const toast = document.createElement("div");
            toast.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0`;
            toast.setAttribute("role", "alert");
            toast.setAttribute("aria-live", "assertive");
            toast.setAttribute("aria-atomic", "true");
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${iconMap[type] || iconMap.info} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                            onclick="this.parentElement.parentElement.remove()"></button>
                </div>`;

            toastContainer.appendChild(toast);

            toast.style.display = "block";
            toast.classList.add("show");

            setTimeout(() => {
                toast.classList.remove("show");
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Change basemap
        function changeBaseMap(baseMapId) {
            if (currentBaseMap) map.removeLayer(currentBaseMap);
            const config = mapConfig.baseMapsList.find((bm) => bm.id === baseMapId);
            if (config) {
                currentBaseMap = L.tileLayer(config.url, {
                    subdomains: config.subdomains || [],
                    maxZoom: 20,
                    attribution: config.attribution
                }).addTo(map);
                showAlert(`Basemap berubah ke: ${config.label}`, "success");
            }
        }

        // Get styling for category - Fixed untuk LineString
        function getStyleForLineString(kategori, colorMap) {
            const baseStyle = colorMap[kategori] || {
                color: "#667eea",
                weight: 3,
                opacity: 0.8
            };

            // Untuk LineString, kita fokus pada style garis
            return {
                color: baseStyle.fillColor || baseStyle.color || "#667eea",
                weight: 3,
                opacity: 0.8,
                dashArray: kategori === 'Pendidikan' ? '5, 5' : null // Contoh: dash untuk kategori tertentu
            };
        }

        // Enhanced popup content - Fixed untuk data Anda
        function bindPopupContent(feature, layer) {
            const props = feature.properties;

            let content = `
                <div class="enhanced-popup" style="max-width: 400px;">
                    <div class="popup-header bg-primary text-white p-2 rounded-top">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-info-circle me-2"></i>
                            ${props.kategori || "Detail Batas Wilayah"}
                        </h6>
                    </div>
                    <div class="popup-body p-3">
            `;

            // Main information
            if (props.NAMOBJ || props.nama) {
                const namaLokasi = props.NAMOBJ || props.nama || 'Nama tidak tersedia';
                content += `
                    <div class="mb-2">
                        <label class="fw-medium text-primary small">NAMA OBJEK</label>
                        <div class="text-dark">${namaLokasi}</div>
                    </div>
                `;
            }

            if (props.kategori_full_path) {
                content += `
                    <div class="mb-2">
                        <label class="fw-medium text-primary small">KATEGORI</label>
                        <div class="text-dark">${props.kategori_full_path}</div>
                    </div>
                `;
            }

            if (props.REMARK) {
                content += `
                    <div class="mb-2">
                        <label class="fw-medium text-primary small">KETERANGAN</label>
                        <div class="text-dark">${props.REMARK}</div>
                    </div>
                `;
            }

            if (props.deskripsi && props.deskripsi !== props.NAMOBJ) {
                content += `
                    <div class="mb-2">
                        <label class="fw-medium text-primary small">DESKRIPSI</label>
                        <div class="text-dark">${props.deskripsi}</div>
                    </div>
                `;
            }

            // Other properties
            const excludeFields = [
                "geometry", "id", "nama", "NAMOBJ", "kategori", "kategori_full_path",
                "kategori_color", "parent_kategori", "created_at", "updated_at", "deskripsi", "REMARK"
            ];
            const otherProps = Object.entries(props).filter(([key, value]) =>
                value && value !== "" && !excludeFields.includes(key)
            );

            if (otherProps.length > 0) {
                content += `<hr class="my-2"><div class="small">`;
                otherProps.slice(0, 5).forEach(([key, value]) => { // Limit to 5 properties
                    const label = key.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase());
                    content += `
                        <div class="mb-1">
                            <span class="fw-medium text-secondary">${label}:</span>
                            <span class="text-dark">${value}</span>
                        </div>
                    `;
                });
                content += `</div>`;
            }

            content += `
                    </div>
                    <div class="popup-footer bg-light p-2 rounded-bottom">
                        <small class="text-muted">
                            <i class="bi bi-geo-alt me-1"></i>
                            Klik pada peta untuk melihat detail lokasi lain
                        </small>
                    </div>
                </div>
            `;

            layer.bindPopup(content, {
                maxWidth: 400,
                className: 'custom-enhanced-popup'
            });
        }

        // Initialize map data
        async function initMap() {
            try {
                showAlert("Memuat data peta...", "info");

                // Fetch hierarchical categories
                const kategorisResponse = await fetch("/api/kategoris-hierarchical");
                if (!kategorisResponse.ok) {
                    throw new Error(`HTTP ${kategorisResponse.status}: Gagal memuat data kategori`);
                }
                const kategorisData = await kategorisResponse.json();
                allCategoriesData = kategorisData;

                console.log("Categories loaded:", kategorisData);

                // Build color map - Updated untuk field 'warna'
                const colorMap = {};
                if (kategorisData.all_categories) {
                    kategorisData.all_categories.forEach(kat => {
                        colorMap[kat.nama] = {
                            color: "#172953",
                            fillColor: kat.warna || "#667eea",
                            weight: 3,
                            opacity: 0.8
                        };
                    });
                }

                // Fetch GeoJSON data
                const response = await fetch("/api/geojson");
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: Gagal memuat data GeoJSON`);
                }
                const geoJsonData = await response.json();
                allFeaturesData = geoJsonData.features || [];

                console.log("GeoJSON loaded:", geoJsonData);
                console.log("Total features:", allFeaturesData.length);

                if (!allFeaturesData.length) {
                    showAlert("Data GeoJSON kosong - tidak ada features", "warning");
                    updateHierarchicalLayerList();
                    updateHierarchicalLegend();
                    return;
                }

                // Initialize layer groups
                layerGroups = {};
                parentLayerGroups = {};
                let addedFeatures = 0;

                allFeaturesData.forEach((feature, index) => {
                    try {
                        const kategori = feature.properties?.kategori || "Lainnya";
                        const parentKategori = feature.properties?.parent_kategori;
                        const fullPath = feature.properties?.kategori_full_path || kategori;

                        console.log(`Processing feature ${index + 1}:`, {
                            nama: feature.properties?.NAMOBJ || feature.properties?.nama,
                            kategori: kategori,
                            geometryType: feature.geometry?.type
                        });

                        // Create parent group if doesn't exist
                        if (parentKategori && !parentLayerGroups[parentKategori]) {
                            parentLayerGroups[parentKategori] = L.layerGroup();
                        }

                        // Create category group if doesn't exist
                        if (!layerGroups[fullPath]) {
                            layerGroups[fullPath] = L.layerGroup();
                        }

                        // Create layer dengan style yang sesuai untuk LineString
                        const geoJsonLayer = L.geoJSON(feature, {
                            style: () => getStyleForLineString(kategori, colorMap),
                            onEachFeature: (f, l) => bindPopupContent(f, l),
                        });

                        // Add to category group
                        geoJsonLayer.addTo(layerGroups[fullPath]);
                        addedFeatures++;

                        // Add to parent group if exists
                        if (parentKategori && parentLayerGroups[parentKategori]) {
                            layerGroups[fullPath].addTo(parentLayerGroups[parentKategori]);
                        }

                    } catch (featureError) {
                        console.error(`Error processing feature ${index + 1}:`, featureError);
                    }
                });

                console.log(`Successfully processed ${addedFeatures} features`);
                console.log("Layer groups created:", Object.keys(layerGroups));

                updateHierarchicalLayerList();
                updateHierarchicalLegend();

                // Auto-show semua layer yang ada
                Object.values(layerGroups).forEach(group => {
                    map.addLayer(group);
                });

                showAlert(`Berhasil memuat ${addedFeatures} features dari ${Object.keys(layerGroups).length} kategori`,
                    "success");

                // Fit map to show all features
                if (addedFeatures > 0) {
                    const group = new L.featureGroup(Object.values(layerGroups));
                    map.fitBounds(group.getBounds(), {
                        padding: [20, 20]
                    });
                }

            } catch (error) {
                console.error("Error in initMap:", error);
                showAlert(`Error: ${error.message}`, "danger");
            }
        }

        // Update hierarchical layer list
        function updateHierarchicalLayerList() {
            const listDiv = document.getElementById("layer-list");
            if (!listDiv) return;

            listDiv.innerHTML = "";

            // Search input
            const searchDiv = document.createElement("div");
            searchDiv.className = "mb-3";
            searchDiv.innerHTML = `
                <input type="text" id="layer-search" class="form-control" 
                       placeholder="🔍 Cari kategori..." autocomplete="off">
            `;
            listDiv.appendChild(searchDiv);

            // Select all/none buttons
            const controlDiv = document.createElement("div");
            controlDiv.className = "mb-3 d-flex gap-2";
            controlDiv.innerHTML = `
                <button id="select-all-layers" class="btn btn-outline-primary btn-sm flex-fill">
                    <i class="bi bi-check-all"></i> Pilih Semua
                </button>
                <button id="deselect-all-layers" class="btn btn-outline-secondary btn-sm flex-fill">
                    <i class="bi bi-x-circle"></i> Hapus Semua
                </button>
            `;
            listDiv.appendChild(controlDiv);

            // Category hierarchy
            const hierarchyDiv = document.createElement("div");
            hierarchyDiv.className = "category-hierarchy";
            hierarchyDiv.style.maxHeight = "calc(100vh - 300px)";
            hierarchyDiv.style.overflowY = "auto";

            if (!allCategoriesData.root_categories || allCategoriesData.root_categories.length === 0) {
                hierarchyDiv.innerHTML = `
                    <div class="text-center py-4">
                        <i class="bi bi-info-circle text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">Belum ada kategori yang terdaftar</p>
                    </div>
                `;
            } else {
                // Show categories that have data
                const categoriesWithData = [];

                // Group features by category
                const featuresPerCategory = {};
                allFeaturesData.forEach(feature => {
                    const kategori = feature.properties?.kategori || "Lainnya";
                    if (!featuresPerCategory[kategori]) {
                        featuresPerCategory[kategori] = 0;
                    }
                    featuresPerCategory[kategori]++;
                });

                // Create category items
                Object.keys(featuresPerCategory).forEach(kategoriNama => {
                    const count = featuresPerCategory[kategoriNama];
                    const categoryDiv = createSimpleCategoryItem(kategoriNama, count);
                    categoriesWithData.push(categoryDiv);
                });

                if (categoriesWithData.length === 0) {
                    hierarchyDiv.innerHTML = `
                        <div class="text-center py-4">
                            <i class="bi bi-info-circle text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">Belum ada data untuk ditampilkan</p>
                        </div>
                    `;
                } else {
                    categoriesWithData.forEach(categoryDiv => {
                        hierarchyDiv.appendChild(categoryDiv);
                    });
                }
            }

            listDiv.appendChild(hierarchyDiv);
            setupLayerEventListeners();
        }

        // Create simple category item
        function createSimpleCategoryItem(kategoriNama, count) {
            const categoryDiv = document.createElement("div");
            categoryDiv.className = "category-dropdown mb-2";

            // Find category data for color
            const categoryData = allCategoriesData.all_categories?.find(cat => cat.nama === kategoriNama);
            const color = categoryData?.warna || "#667eea";

            categoryDiv.innerHTML = `
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light py-2 px-3">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" id="category-${kategoriNama}" 
                                   class="form-check-input me-2 category-checkbox" 
                                   data-category-name="${kategoriNama}" checked>
                            <div class="category-color me-2" 
                                 style="width: 16px; height: 16px; background-color: ${color}; 
                                 border: 1px solid #ddd; border-radius: 2px;"></div>
                            <label for="category-${kategoriNama}" class="form-check-label fw-medium flex-grow-1 mb-0">
                                ${kategoriNama}
                            </label>
                            <small class="text-muted">${count} item</small>
                        </div>
                    </div>
                </div>
            `;

            return categoryDiv;
        }

        // Setup layer event listeners
        function setupLayerEventListeners() {
            // Search functionality
            const searchInput = document.getElementById("layer-search");
            if (searchInput) {
                searchInput.addEventListener("input", (e) => {
                    const searchTerm = e.target.value.toLowerCase();
                    const categoryCards = document.querySelectorAll(".category-dropdown");

                    categoryCards.forEach(card => {
                        const label = card.querySelector("label");
                        const text = label ? label.textContent.toLowerCase() : "";
                        const match = text.includes(searchTerm);
                        card.style.display = match ? "block" : "none";
                    });
                });
            }

            // Select all functionality
            const selectAllBtn = document.getElementById("select-all-layers");
            if (selectAllBtn) {
                selectAllBtn.addEventListener("click", () => {
                    document.querySelectorAll(".category-checkbox").forEach(checkbox => {
                        if (!checkbox.checked) {
                            checkbox.checked = true;
                            checkbox.dispatchEvent(new Event("change"));
                        }
                    });
                });
            }

            // Deselect all functionality
            const deselectAllBtn = document.getElementById("deselect-all-layers");
            if (deselectAllBtn) {
                deselectAllBtn.addEventListener("click", () => {
                    document.querySelectorAll(".category-checkbox").forEach(checkbox => {
                        if (checkbox.checked) {
                            checkbox.checked = false;
                            checkbox.dispatchEvent(new Event("change"));
                        }
                    });
                });
            }

            // Category checkbox functionality
            document.querySelectorAll(".category-checkbox").forEach(checkbox => {
                checkbox.addEventListener("change", (e) => {
                    const categoryName = e.target.dataset.categoryName;
                    const fullPath = categoryName; // For simple implementation

                    if (e.target.checked) {
                        if (layerGroups[fullPath]) {
                            map.addLayer(layerGroups[fullPath]);
                        }
                    } else {
                        if (layerGroups[fullPath]) {
                            map.removeLayer(layerGroups[fullPath]);
                        }
                    }
                });
            });
        }

        // Update hierarchical legend
        function updateHierarchicalLegend() {
            const legendContent = document.getElementById("legend-content");
            if (!legendContent) return;

            let legendHTML = `
                <div class="legend-header mb-3">
                    <h6 class="fw-bold text-primary mb-2">
                        <i class="bi bi-palette me-2"></i>Legenda Peta
                    </h6>
                    <p class="small text-muted mb-0">Kategori yang tersedia pada peta</p>
                </div>
                <div class="legend-hierarchy">
            `;

            // Group features by category for legend
            const featuresPerCategory = {};
            allFeaturesData.forEach(feature => {
                const kategori = feature.properties?.kategori || "Lainnya";
                if (!featuresPerCategory[kategori]) {
                    featuresPerCategory[kategori] = 0;
                }
                featuresPerCategory[kategori]++;
            });

            if (Object.keys(featuresPerCategory).length === 0) {
                legendHTML += `
                    <div class="text-center py-4">
                        <i class="bi bi-info-circle text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">Belum ada data untuk ditampilkan</p>
                    </div>
                `;
            } else {
                Object.keys(featuresPerCategory).forEach(kategoriNama => {
                    const count = featuresPerCategory[kategoriNama];
                    const categoryData = allCategoriesData.all_categories?.find(cat => cat.nama === kategoriNama);
                    const color = categoryData?.warna || "#667eea";

                    legendHTML += `
                        <div class="legend-item root-legend mb-2">
                            <div class="d-flex align-items-center">
                                <div class="legend-color me-2" 
                                     style="width: 18px; height: 18px; 
                                     background-color: ${color}; border: 1px solid #ddd; border-radius: 2px;"></div>
                                <span class="legend-label fw-bold">${kategoriNama}</span>
                                <small class="text-muted ms-auto">${count} item</small>
                            </div>
                        </div>
                    `;
                });
            }

            legendHTML += '</div>';
            legendContent.innerHTML = legendHTML;
        }

        // Setup basemap list
        function setupBasemapList() {
            const basemapList = document.getElementById("basemap-list");
            if (!basemapList) return;

            let html = `
                <div class="mb-3">
                    <p class="text-muted small">Pilih jenis peta dasar yang ingin ditampilkan</p>
                </div>
            `;

            mapConfig.baseMapsList.forEach((bm, i) => {
                html += `
                    <div class="form-check form-switch mb-3 p-3 border rounded">
                        <input class="form-check-input" type="radio" role="switch" 
                               name="basemap-radio" id="bm-${bm.id}" value="${bm.id}" 
                               ${i === 4 ? "checked" : ""}>
                        <label class="form-check-label fw-medium" for="bm-${bm.id}">
                            <i class="bi bi-map me-2"></i>${bm.label}
                        </label>
                    </div>
                `;
            });

            basemapList.innerHTML = html;

            basemapList.addEventListener("change", (e) => {
                if (e.target.name === "basemap-radio") {
                    changeBaseMap(e.target.value);
                }
            });
        }

        // Setup download content
        function setupDownloadContent() {
            const downloadContent = document.getElementById("download-content");
            if (!downloadContent) return;

            downloadContent.innerHTML = `
                <div class="mb-3">
                    <h6 class="fw-bold text-primary mb-2">
                        <i class="bi bi-download me-2"></i>Download Peta
                    </h6>
                    <p class="small text-muted mb-0">Download peta dalam berbagai format</p>
                </div>
                
                <div class="download-options">
                    <button id="download-geojson" class="btn btn-outline-success btn-sm w-100 mb-2">
                        <i class="bi bi-file-earmark-code me-2"></i>Download GeoJSON
                    </button>
                    <button id="download-csv" class="btn btn-outline-info btn-sm w-100 mb-2">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Download CSV
                    </button>
                    <button id="download-kml" class="btn btn-outline-warning btn-sm w-100 mb-2">
                        <i class="bi bi-file-earmark-text me-2"></i>Download KML
                    </button>
                    <button id="print-map" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-printer me-2"></i>Print Map
                    </button>
                </div>
            `;

            // Download functionality
            document.getElementById("download-geojson")?.addEventListener("click", downloadGeoJSON);
            document.getElementById("download-csv")?.addEventListener("click", downloadCSV);
            document.getElementById("download-kml")?.addEventListener("click", downloadKML);
            document.getElementById("print-map")?.addEventListener("click", printMap);
        }

        // Download functions
        function downloadGeoJSON() {
            showAlert("Mengunduh data GeoJSON...", "info");
            window.location.href = "/api/export?format=geojson";
        }

        function downloadCSV() {
            showAlert("Mengunduh data CSV...", "info");
            window.location.href = "/api/export?format=csv";
        }

        function downloadKML() {
            showAlert("Mengunduh data KML...", "info");
            window.location.href = "/api/export?format=kml";
        }

        function printMap() {
            showAlert("Membuka dialog print...", "info");
            window.print();
        }

        // Setup transparency control
        function setupTransparencyControl() {
            const transparencySlider = document.getElementById("transparency");
            if (transparencySlider) {
                transparencySlider.addEventListener("input", (e) => {
                    const opacity = e.target.value / 100;
                    Object.values(layerGroups).forEach((group) => {
                        group.eachLayer((layer) => {
                            if (layer.setStyle) {
                                layer.setStyle({
                                    opacity: opacity
                                });
                            }
                        });
                    });
                    showAlert(`Transparansi diatur ke ${e.target.value}%`, "info");
                });
            }
        }

        // Setup UI controls
        function setupUIControls() {
            const controls = {
                "btn-toggle-sidebar-layer": () => toggleSidebar("sidebar-layer"),
                "btn-toggle-sidebar-basemap": () => toggleSidebar("sidebar-basemap"),
                "btn-toggle-sidebar-legend": () => toggleSidebar("sidebar-legend"),
                "btn-toggle-sidebar-download": () => toggleSidebar("sidebar-download"),
                "btn-toggle-sidebar-help": () => showGuideModal(),
                "btn-fullscreen": () => toggleFullscreen(),
                "btn-default-zoom": () => resetToDefaultView(),
            };

            Object.entries(controls).forEach(([id, handler]) => {
                const btn = document.getElementById(id);
                if (btn) {
                    btn.addEventListener("click", handler);
                }
            });

            // Close sidebar buttons
            const closeBtns = [
                "btn-close-sidebar-layer",
                "btn-close-sidebar-basemap",
                "btn-close-sidebar-legend",
                "btn-close-sidebar-download"
            ];

            closeBtns.forEach(id => {
                const btn = document.getElementById(id);
                if (btn) {
                    const sidebarId = id.replace("btn-close-", "");
                    btn.addEventListener("click", () => closeSidebar(sidebarId));
                }
            });
        }

        function toggleSidebar(sidebarId) {
            closeAllSidebars();
            const sidebar = document.getElementById(sidebarId);
            if (sidebar) {
                sidebar.classList.toggle('show');

                // Update button state
                const btn = document.getElementById(`btn-toggle-${sidebarId.replace('sidebar-', '')}`);
                if (btn) {
                    btn.classList.toggle('active', sidebar.classList.contains('show'));
                }
            }
        }

        function closeSidebar(sidebarId) {
            const sidebar = document.getElementById(sidebarId);
            if (sidebar) {
                sidebar.classList.remove('show');

                // Update button state
                const btn = document.getElementById(`btn-toggle-${sidebarId.replace('sidebar-', '')}`);
                if (btn) {
                    btn.classList.remove('active');
                }
            }
        }

        function closeAllSidebars() {
            ["sidebar-layer", "sidebar-basemap", "sidebar-legend", "sidebar-download"].forEach(id => {
                const sidebar = document.getElementById(id);
                if (sidebar) {
                    sidebar.classList.remove('show');
                }

                // Update button state
                const btn = document.getElementById(`btn-toggle-${id.replace('sidebar-', '')}`);
                if (btn) {
                    btn.classList.remove('active');
                }
            });
        }

        function toggleFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen();
                showAlert("Keluar dari mode fullscreen", "info");
            } else {
                document.documentElement.requestFullscreen();
                showAlert("Masuk ke mode fullscreen", "info");
            }
        }

        function resetToDefaultView() {
            map.setView(mapConfig.center, mapConfig.zoom);
            showAlert("Peta kembali ke posisi default", "info");
        }

        function showGuideModal() {
            const modal = document.getElementById("guideModal");
            if (modal) {
                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
                setupGuideModal();
            }
        }

        function setupGuideModal() {
            const guideSteps = document.querySelectorAll(".guide-step");
            const btnPrev = document.getElementById("btnPrev");
            const btnNext = document.getElementById("btnNext");
            const btnSkip = document.getElementById("btnSkip");
            const stepIndicator = document.getElementById("step-indicator");

            let currentStep = 1;
            const totalSteps = guideSteps.length;

            const controlButtons = [
                document.getElementById("btn-toggle-sidebar-help"),
                document.getElementById("btn-toggle-sidebar-legend"),
                document.getElementById("btn-toggle-sidebar-basemap"),
                document.getElementById("btn-toggle-sidebar-layer"),
                document.getElementById("btn-toggle-sidebar-download"),
                document.getElementById("btn-fullscreen"),
                document.getElementById("btn-default-zoom"),
            ];

            function showStep(step) {
                guideSteps.forEach((stepDiv) => {
                    stepDiv.classList.toggle("d-none", parseInt(stepDiv.dataset.step) !== step);
                });

                btnPrev.disabled = step === 1;
                btnNext.textContent = step === totalSteps ? "Selesai" : "Selanjutnya";
                if (stepIndicator) {
                    stepIndicator.textContent = `${step} / ${totalSteps}`;
                }

                // Remove all highlights
                controlButtons.forEach((btn) => {
                    if (btn) btn.classList.remove("highlighted-control");
                });

                // Add highlight based on step
                const highlightMap = {
                    3: 0,
                    4: 1,
                    5: 2,
                    6: 3,
                    7: 4,
                    8: 5,
                    9: 6
                };

                if (highlightMap[step] !== undefined && controlButtons[highlightMap[step]]) {
                    controlButtons[highlightMap[step]].classList.add("highlighted-control");
                }
            }

            btnPrev?.addEventListener("click", () => {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                }
            });

            btnNext?.addEventListener("click", () => {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                } else {
                    const modal = document.getElementById("guideModal");
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) modalInstance.hide();
                    controlButtons.forEach((btn) => {
                        if (btn) btn.classList.remove("highlighted-control");
                    });
                }
            });

            btnSkip?.addEventListener("click", () => {
                const modal = document.getElementById("guideModal");
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
                controlButtons.forEach((btn) => {
                    if (btn) btn.classList.remove("highlighted-control");
                });
            });

            showStep(currentStep);
        }

        function setupKeyboardShortcuts() {
            document.addEventListener("keydown", (e) => {
                if (e.target.tagName === "INPUT" || e.target.tagName === "TEXTAREA") return;

                switch (e.key.toLowerCase()) {
                    case "h":
                        if (e.ctrlKey) {
                            e.preventDefault();
                            showGuideModal();
                        }
                        break;
                    case "l":
                        if (e.ctrlKey) {
                            e.preventDefault();
                            toggleSidebar("sidebar-layer");
                        }
                        break;
                    case "b":
                        if (e.ctrlKey) {
                            e.preventDefault();
                            toggleSidebar("sidebar-basemap");
                        }
                        break;
                    case "f11":
                        e.preventDefault();
                        toggleFullscreen();
                        break;
                    case "escape":
                        closeAllSidebars();
                        break;
                }
            });
        }

        // Click outside to close sidebars
        function setupClickOutside() {
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.sidebar') && !e.target.closest('.control-buttons')) {
                    if (!e.target.closest('.leaflet-control-container')) {
                        setTimeout(() => {
                            const anySidebarOpen = document.querySelector('.sidebar.show');
                            if (anySidebarOpen && !document.querySelector('.modal.show')) {
                                closeAllSidebars();
                            }
                        }, 100);
                    }
                }
            });
        }

        // Initialize everything when DOM is ready
        document.addEventListener("DOMContentLoaded", () => {
            console.log("DOM loaded, initializing WebGIS...");

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize the application
            initMap();
            changeBaseMap("esri-world-imagery");
            setupBasemapList();
            setupDownloadContent();
            setupTransparencyControl();
            setupUIControls();
            setupKeyboardShortcuts();
            setupClickOutside();

            // Show guide modal setelah load
            setTimeout(() => {
                showGuideModal();
            }, 2000);

            console.log("WebGIS initialization complete");
        });
    </script>
