<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Peta Lokasi GIS - {{ config('app.name') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/peta.css') }}">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('lokasi.index') }}">
                <i class="bi bi-geo-alt-fill me-2"></i>
                MARIMOI
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="{{ route('lokasi.create') }}">
                        <i class="bi bi-plus-circle me-1"></i>
                        Tambah Data
                    </a>
                    <a class="nav-link" href="{{ route('lokasi.index') }}">
                        <i class="bi bi-table me-1"></i>
                        Lihat Tabel
                    </a>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>
                            Tools
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportData('geojson')">
                                    <i class="bi bi-download me-2"></i>Export GeoJSON
                                </a></li>
                            <li><a class="dropdown-item" href="#" onclick="printMap()">
                                    <i class="bi bi-printer me-2"></i>Print Map
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#" onclick="showHelp()">
                                    <i class="bi bi-question-circle me-2"></i>Help
                                </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Map Container -->
    <div id="map"></div>

    <!-- Map Controls -->
    <div class="map-controls">
        <!-- Layer Control -->
        <div class="control-panel" id="layer-control-panel">
            <div class="control-header">
                <span><i class="bi bi-layers me-2"></i>Kontrol Layer</span>
                <button class="collapse-btn" onclick="togglePanel('layer-control-body')" data-bs-toggle="tooltip"
                    title="Toggle Layer Control">
                    <i class="bi bi-chevron-up"></i>
                </button>
            </div>
            <div class="control-body" id="layer-control-body">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    Memuat data...
                </div>
            </div>
        </div>

        <!-- Filter Control -->
        <div class="control-panel" id="filter-control-panel">
            <div class="control-header">
                <span><i class="bi bi-funnel me-2"></i>Filter Data</span>
                <button class="collapse-btn" onclick="togglePanel('filter-control-body')" data-bs-toggle="tooltip"
                    title="Toggle Filter Control">
                    <i class="bi bi-chevron-up"></i>
                </button>
            </div>
            <div class="control-body" id="filter-control-body">
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="bi bi-search me-1"></i>Pencarian
                    </label>
                    <input type="text" class="filter-input" id="search-input" placeholder="Cari data..."
                        autocomplete="off">
                </div>
                <div id="dbf-filters"></div>
                <div class="filter-buttons">
                    <button class="btn-filter btn-apply" onclick="applyFilters()" data-bs-toggle="tooltip"
                        title="Terapkan Filter">
                        <i class="bi bi-check-circle me-1"></i>Terapkan
                    </button>
                    <button class="btn-filter btn-reset" onclick="resetFilters()" data-bs-toggle="tooltip"
                        title="Reset Semua Filter">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- <!-- Map Tools -->
        <div class="control-panel" id="tools-control-panel">
            <div class="control-header">
                <span><i class="bi bi-tools me-2"></i>Map Tools</span>
                <button class="collapse-btn" onclick="togglePanel('tools-control-body')" data-bs-toggle="tooltip"
                    title="Toggle Map Tools">
                    <i class="bi bi-chevron-up"></i>
                </button>
            </div>
            <div class="control-body" id="tools-control-body">
                <div class="tools-grid">
                    <button class="tool-btn" onclick="toggleFullscreen()" data-bs-toggle="tooltip"
                        title="Toggle Fullscreen">
                        <i class="bi bi-fullscreen"></i>
                    </button>
                    <button class="tool-btn" onclick="locateUser()" data-bs-toggle="tooltip" title="Lokasi Saya">
                        <i class="bi bi-geo-alt"></i>
                    </button>
                    <button class="tool-btn" onclick="measureDistance()" data-bs-toggle="tooltip"
                        title="Ukur Jarak">
                        <i class="bi bi-rulers"></i>
                    </button>
                    <button class="tool-btn" onclick="shareMap()" data-bs-toggle="tooltip" title="Share Map">
                        <i class="bi bi-share"></i>
                    </button>
                </div>
            </div>
        </div> --}}
    </div>

    <!-- Legend -->
    <div class="legend">
        <div class="legend-header">
            <i class="bi bi-info-circle me-2"></i>Statistik Data
        </div>
        <div class="legend-body">
            <div class="legend-stats">
                <div class="stat-item">
                    <span class="stat-number" id="total-areas">0</span>
                    <div class="stat-label">Total Area</div>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="categories-count">0</span>
                    <div class="stat-label">Kategori</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner-large"></div>
            <h5>Memuat Data...</h5>
            <p class="text-muted">Mohon tunggu sebentar</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Custom JS -->
    <script src="{{ asset('js/peta-config.js') }}"></script>
    <script src="{{ asset('js/peta-utils.js') }}"></script>
    <script src="{{ asset('js/peta-main.js') }}"></script>
</body>

</html>
