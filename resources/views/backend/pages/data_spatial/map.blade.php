@extends('backend.partials.main', ['title' => 'Peta Tematik'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-earth"></i>
            </span>
            Peta Tematik
        </h3>
        <div class="btn-group" role="group" aria-label="Ganti tampilan">
            <a href="{{ route('data-spatial.index', ['type' => 'tematik']) }}" class="btn btn-outline-primary">
                <i class="mdi mdi-table"></i> Tabel
            </a>
            <a href="{{ route('data-spatial.map') }}" class="btn btn-outline-primary active">
                <i class="mdi mdi-map"></i> Peta
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body p-2">

                    <div id="mapWrapper" class="map-wrapper">
                        <div id="dataSpasialMap"></div>

                        <!-- Efek loading -->
                        <div id="mapLoading" class="map-loading" role="status" aria-live="polite">
                            <div class="map-loading-box">
                                <div class="map-loading-spinner"></div>
                                <div class="map-loading-text" id="mapLoadingText">Memuat peta...</div>
                            </div>
                        </div>

                        <!-- Tombol layer (di bawah zoom in/out) -->
                        <button type="button" id="btnToggleLayers" class="map-layers-btn" title="Layer"
                            aria-expanded="false">
                            <i class="mdi mdi-layers"></i>
                        </button>

                        <!-- Panel layer -->
                        <div id="layerPanel" class="layer-panel d-none">
                            <div class="layer-panel-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="mdi mdi-layers me-1"></i>Layer</h6>
                                <button type="button" id="btnCloseLayers" class="layer-panel-close"
                                    aria-label="Tutup">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </div>

                            <div class="layer-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="text" id="layerSearch" autocomplete="off" spellcheck="false"
                                    maxlength="100" placeholder="Cari layer atau kategori...">
                                <button type="button" id="layerSearchClear" class="d-none"
                                    aria-label="Hapus pencarian">
                                    <i class="mdi mdi-close-circle"></i>
                                </button>
                            </div>
                            <p id="layerSearchEmpty" class="text-muted small d-none px-1">Tidak ada layer/kategori
                                yang cocok.</p>

                            <div id="mapLayerList" class="map-layer-list">
                                @include('backend.pages.data_spatial._map_layer_checklist', [
                                    'categories' => $categories,
                                ])
                            </div>

                            <div class="layer-panel-footer">
                                <label for="layerOpacity" class="form-label small fw-semibold mb-1">
                                    <i class="mdi mdi-opacity me-1"></i>Transparansi data
                                    <span id="layerOpacityValue" class="text-muted">100%</span>
                                </label>
                                <input type="range" class="form-range" id="layerOpacity" min="10" max="100"
                                    step="5" value="100">
                                <div class="d-grid mt-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnZoomActive">
                                        <i class="mdi mdi-fit-to-page-outline me-1"></i>Zoom ke semua layer aktif
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Pencarian lokasi & data -->
                        <div class="map-search">
                            <div class="input-group input-group-sm shadow-sm">
                                <span class="input-group-text bg-white"><i class="mdi mdi-magnify"></i></span>
                                <input type="text" id="mapSearchInput" class="form-control"
                                    placeholder="Cari lokasi, data, atau koordinat (lat, lng)" autocomplete="off">
                                <button type="button" class="btn btn-light border d-none" id="mapSearchClear"
                                    title="Bersihkan">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </div>
                            <div id="mapSearchResults" class="list-group shadow-sm d-none"></div>
                        </div>

                        <!-- Toolbar -->
                        <div class="map-toolbar">
                            <button type="button" class="map-tool" id="toolFullscreen" title="Layar penuh">
                                <i class="mdi mdi-fullscreen"></i>
                            </button>
                            <button type="button" class="map-tool" id="toolLocate" title="Lokasi saya">
                                <i class="mdi mdi-crosshairs-gps"></i>
                            </button>
                            <button type="button" class="map-tool" id="toolHome" title="Kembali ke Maluku Utara">
                                <i class="mdi mdi-home-map-marker"></i>
                            </button>
                            <span class="map-tool-sep"></span>
                            <button type="button" class="map-tool" id="toolPoint" title="Gambar titik">
                                <i class="mdi mdi-map-marker-plus"></i>
                            </button>
                            <button type="button" class="map-tool" id="toolDistance" title="Gambar garis / ukur jarak">
                                <i class="mdi mdi-ruler"></i>
                            </button>
                            <button type="button" class="map-tool" id="toolArea" title="Gambar poligon / ukur luas">
                                <i class="mdi mdi-vector-polygon"></i>
                            </button>
                        </div>

                        <!-- Panel gambar & ukur -->
                        <div id="measurePanel" class="measure-panel d-none">
                            <div class="fw-semibold small" id="measureTitle"></div>
                            <div class="measure-value" id="measureValue">-</div>
                            <div class="text-muted small" id="measureHint"></div>
                            <div class="small mt-2" id="drawSummary"></div>
                            <div class="mt-2 d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-primary" id="measureFinish">Selesai</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    id="measureClear">Hapus semua</button>
                            </div>
                            <div class="mt-2 small fw-semibold">Simpan sebagai file:</div>
                            <div class="btn-group btn-group-sm w-100 mt-1" role="group">
                                <button type="button" class="btn btn-outline-primary export-btn" data-format="kmz"
                                    disabled>KMZ</button>
                                <button type="button" class="btn btn-outline-primary export-btn" data-format="kml"
                                    disabled>KML</button>
                                <button type="button" class="btn btn-outline-primary export-btn"
                                    data-format="geojson" disabled>GeoJSON</button>
                            </div>
                        </div>

                        <!-- Pilihan peta dasar -->
                        <div class="basemap-switcher" id="basemapSwitcher">
                            <button type="button" class="basemap-btn active" data-basemap="satelit">
                                <i class="mdi mdi-satellite-variant"></i> Satelit
                            </button>
                            <button type="button" class="basemap-btn" data-basemap="jalan">
                                <i class="mdi mdi-road-variant"></i> Jalan
                            </button>
                            <button type="button" class="basemap-btn" data-basemap="topografi">
                                <i class="mdi mdi-terrain"></i> Topografi
                            </button>
                            <button type="button" class="basemap-btn" data-basemap="gelap">
                                <i class="mdi mdi-weather-night"></i> Gelap
                            </button>
                        </div>

                        <!-- Koordinat kursor -->
                        <div class="map-coords" id="mapCoords">
                            <span id="mapCoordsText">-</span>
                            <span class="mx-1">|</span>
                            Zoom <span id="mapZoomText">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('backend.pages.data_spatial._detail_modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        .map-wrapper {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: #1b1f24;
        }

        #dataSpasialMap {
            height: calc(100vh - 200px);
            min-height: 620px;
        }

        .map-loading {
            position: absolute;
            inset: 0;
            z-index: 1100;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(17, 24, 39, .45);
            backdrop-filter: blur(1px);
            opacity: 1;
            transition: opacity .25s ease;
        }

        .map-loading.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .map-loading-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 20px 28px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .3);
        }

        .map-loading-spinner {
            width: 42px;
            height: 42px;
            border: 4px solid #dbeafe;
            border-top-color: #0d6efd;
            border-radius: 50%;
            animation: map-loading-spin .8s linear infinite;
        }

        .map-loading-text {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        @keyframes map-loading-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .map-layers-btn {
            position: absolute;
            top: 80px;
            left: 10px;
            z-index: 1000;
            width: 34px;
            height: 34px;
            padding: 0;
            font-size: 20px;
            line-height: 1;
            color: #333;
            background: #fff;
            border: 2px solid rgba(0, 0, 0, .2);
            border-radius: 4px;
            background-clip: padding-box;
        }

        .map-layers-btn:hover,
        .map-layers-btn.active {
            background: #0d6efd;
            color: #fff;
        }

        .layer-panel {
            position: absolute;
            top: 80px;
            left: 54px;
            z-index: 1001;
            width: 310px;
            max-width: calc(100% - 130px);
            max-height: calc(100% - 175px);
            display: flex;
            flex-direction: column;
            padding: 10px;
            background: #f8fafc;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .35);
        }

        .layer-panel .map-layer-list {
            flex: 1 1 auto;
            min-height: 0;
            max-height: none;
            overflow-y: auto;
        }

        .layer-panel-footer {
            flex: 0 0 auto;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
        }

        .layer-panel-close {
            border: 0;
            background: transparent;
            padding: 0 2px;
            font-size: 18px;
            line-height: 1;
            color: #fff;
        }

        .map-wrapper:fullscreen #dataSpasialMap {
            height: 100vh;
        }

        .map-wrapper.measuring #dataSpasialMap {
            cursor: crosshair;
        }

        .map-wrapper.measuring .leaflet-interactive {
            pointer-events: none;
        }

        .leaflet-popup-content {
            min-width: 230px;
        }

        .map-popup-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .map-popup-actions {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }

        .map-layer-list {
            max-height: 430px;
            overflow-y: auto;
        }

        .layer-panel-header {
            background: linear-gradient(135deg, #007fff, #0066cc);
            color: #fff;
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .layer-panel-header h6 {
            color: #fff;
            font-size: 14px;
            font-weight: 600;
        }

        .layer-search {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0 10px;
            margin-bottom: 10px;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: 8px;
        }

        .layer-search:focus-within {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, .4);
        }

        .layer-search input {
            flex: 1 1 auto;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            padding: 8px 0;
            font-size: 13px;
        }

        .layer-search button {
            border: 0;
            background: transparent;
            padding: 0;
            color: #9ca3af;
        }

        .layer-node {
            margin-bottom: 6px;
        }

        .layer-row {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            transition: background .15s;
        }

        .layer-row:hover {
            background: #f3f4f6;
        }

        .layer-row.layer-depth-1 {
            border-color: #e5e7eb;
            border-radius: 6px;
            padding: 6px 8px;
        }

        .layer-row.layer-depth-2 {
            border: 0;
            border-radius: 4px;
            padding: 4px 6px;
        }

        .layer-children {
            margin: 4px 0 0 14px;
            padding-left: 8px;
            border-left: 1px solid #e5e7eb;
        }

        .layer-toggle {
            flex: 0 0 18px;
            width: 18px;
            border: 0;
            background: transparent;
            padding: 0;
            font-size: 18px;
            line-height: 1;
            color: #4b5563;
        }

        .layer-toggle i {
            transition: transform .2s;
            display: inline-block;
        }

        .layer-node.open > .layer-row .layer-toggle i {
            transform: rotate(90deg);
        }

        .layer-label {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1 1 auto;
            min-width: 0;
            margin: 0;
            cursor: pointer;
            font-size: 13px;
            color: #1f2937;
        }

        .layer-depth-0 .layer-label {
            font-weight: 600;
            font-size: 14px;
        }

        .layer-label input.map-layer-checkbox {
            -webkit-appearance: checkbox;
            appearance: auto;
            position: static;
            opacity: 1;
            flex: 0 0 auto;
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: #3b82f6;
            cursor: pointer;
        }

        .layer-swatch {
            flex: 0 0 auto;
            width: 12px;
            height: 12px;
            border-radius: 3px;
            border: 1px solid rgba(0, 0, 0, .25);
        }

        .layer-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .layer-badge {
            flex: 0 0 auto;
            font-size: 11px;
            line-height: 1;
            padding: 3px 7px;
            border-radius: 10px;
            background: #e5e7eb;
            color: #374151;
        }

        .layer-status {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .layer-count {
            font-size: 11px;
            line-height: 1;
            padding: 3px 7px;
            border-radius: 10px;
            background: #dbeafe;
            color: #1d4ed8;
        }

        .layer-zoom {
            border: 0;
            background: transparent;
            padding: 0;
            line-height: 1;
            font-size: 16px;
            color: #0d6efd;
        }

        .map-search {
            position: absolute;
            top: 10px;
            left: 56px;
            width: min(360px, calc(100% - 130px));
            z-index: 1000;
        }

        .map-search #mapSearchResults {
            max-height: 320px;
            overflow-y: auto;
            margin-top: 2px;
        }

        .map-search .list-group-item {
            font-size: 12px;
            cursor: pointer;
        }

        .map-search .result-heading {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6c757d;
            background: #f8f9fa;
            cursor: default;
        }

        .map-toolbar {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: #fff;
            padding: 4px;
            border-radius: 6px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, .45);
        }

        .map-tool {
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 4px;
            background: #fff;
            font-size: 20px;
            line-height: 1;
            color: #333;
        }

        .map-tool:hover {
            background: #eef3ff;
        }

        .map-tool.active {
            background: #0d6efd;
            color: #fff;
        }

        .map-tool-sep {
            height: 1px;
            background: #dee2e6;
            margin: 2px 0;
        }

        .measure-panel {
            position: absolute;
            top: 10px;
            right: 60px;
            z-index: 1000;
            background: #fff;
            padding: 10px 12px;
            border-radius: 6px;
            min-width: 230px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, .45);
        }

        .measure-value {
            font-size: 20px;
            font-weight: 700;
            color: #0d6efd;
        }

        .basemap-switcher {
            position: absolute;
            left: 10px;
            bottom: 34px;
            z-index: 1000;
            display: flex;
            gap: 4px;
            background: #fff;
            padding: 4px;
            border-radius: 6px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, .45);
        }

        .basemap-btn {
            border: 0;
            background: transparent;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            color: #333;
        }

        .basemap-btn:hover {
            background: #eef3ff;
        }

        .basemap-btn.active {
            background: #0d6efd;
            color: #fff;
        }

        .map-coords {
            position: absolute;
            right: 10px;
            bottom: 10px;
            z-index: 1000;
            background: rgba(0, 0, 0, .65);
            color: #fff;
            font-size: 12px;
            padding: 3px 10px;
            border-radius: 4px;
            font-family: monospace;
        }

        .measure-label {
            background: #0d6efd;
            border: 0;
            color: #fff;
            font-weight: 600;
        }

        .measure-label::before {
            display: none;
        }

        /* Dark mode */
        html[data-theme="dark"] .map-toolbar,
        html[data-theme="dark"] .measure-panel,
        html[data-theme="dark"] .basemap-switcher {
            background: var(--admin-surface);
            color: var(--admin-text);
            border: 1px solid var(--admin-border);
            box-shadow: 0 2px 10px rgba(0, 0, 0, .5);
        }

        html[data-theme="dark"] .map-tool,
        html[data-theme="dark"] .basemap-btn {
            background: transparent;
            color: var(--admin-text);
        }

        html[data-theme="dark"] .map-tool:hover,
        html[data-theme="dark"] .basemap-btn:hover {
            background: var(--admin-surface-soft);
        }

        html[data-theme="dark"] .map-tool.active,
        html[data-theme="dark"] .basemap-btn.active {
            background: var(--admin-primary-dark);
            color: #fff;
        }

        html[data-theme="dark"] .map-tool-sep {
            background: var(--admin-border);
        }

        html[data-theme="dark"] .measure-panel .text-muted {
            color: var(--admin-muted) !important;
        }

        html[data-theme="dark"] .measure-value {
            color: var(--admin-primary);
        }

        html[data-theme="dark"] .measure-panel .btn-outline-secondary {
            color: var(--admin-text);
            border-color: var(--admin-border);
        }

        html[data-theme="dark"] .measure-panel .btn-outline-primary {
            color: var(--admin-primary);
            border-color: var(--admin-primary);
        }

        html[data-theme="dark"] .measure-panel .btn-outline-primary:hover:not(:disabled) {
            background: var(--admin-primary-dark);
            color: #fff;
        }

        @media (max-width: 767px) {
            .basemap-switcher {
                flex-wrap: wrap;
                max-width: calc(100% - 20px);
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        const csrfToken = '{{ csrf_token() }}';
        const geojsonUrl = '{{ route('data-spatial.geojson') }}';
        const editUrlTemplate = '{{ route('data-spatial.edit', ':uuid') }}';
        const destroyUrlTemplate = '{{ route('data-spatial.destroy', ':uuid') }}';

        const HOME_VIEW = {
            center: [1.5, 127.5],
            zoom: 8
        };

        let dataSpasialMap = null;
        const mapLayerGroups = {}; // categoryId -> L.layerGroup
        const loadedMapLayers = new Set(); // categoryId yang datanya sudah di-fetch
        const featureIndex = {}; // categoryId -> [{layer, props}]
        let layerOpacity = 1;

        // ---------- Efek loading ----------
        let pendingLoads = 0;
        let initialTilesLoaded = false;

        function showMapLoading(text) {
            pendingLoads++;
            document.getElementById('mapLoadingText').textContent = text;
            document.getElementById('mapLoading').classList.remove('hidden');
        }

        function updateMapLoadingText(text) {
            document.getElementById('mapLoadingText').textContent = text;
        }

        function hideMapLoading() {
            pendingLoads = Math.max(0, pendingLoads - 1);

            if (pendingLoads === 0) {
                document.getElementById('mapLoading').classList.add('hidden');
            }
        }

        // Overlay hanya untuk pemuatan awal peta dasar; geser/zoom berikutnya tidak menutupi peta.
        function trackInitialTiles(tileLayer) {
            tileLayer.once('load', () => {
                if (!initialTilesLoaded) {
                    initialTilesLoaded = true;
                    hideMapLoading();
                }
            });
        }

        // ---------- Peta dasar ----------
        function buildBasemaps() {
            const esriImagery = L.tileLayer(
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: 'Tiles &copy; Esri',
                    maxZoom: 19
                });
            const esriLabels = L.tileLayer(
                'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19
                });

            return {
                satelit: L.layerGroup([esriImagery, esriLabels]),
                jalan: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19
                }),
                topografi: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenTopoMap, OpenStreetMap contributors',
                    maxZoom: 17
                }),
                gelap: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                    maxZoom: 19
                })
            };
        }

        let basemaps = {};
        let activeBasemap = null;

        function setBasemap(name) {
            if (!basemaps[name]) return;

            if (activeBasemap) {
                dataSpasialMap.removeLayer(activeBasemap);
            }

            activeBasemap = basemaps[name];
            activeBasemap.addTo(dataSpasialMap);
            if (activeBasemap.bringToBack) {
                activeBasemap.bringToBack();
            }

            document.querySelectorAll('.basemap-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.basemap === name);
            });
        }

        // ---------- Inisialisasi ----------
        function initDataSpasialMap() {
            dataSpasialMap = L.map('dataSpasialMap', {
                zoomControl: true
            }).setView(HOME_VIEW.center, HOME_VIEW.zoom);

            L.control.scale({
                imperial: false,
                position: 'bottomleft'
            }).addTo(dataSpasialMap);

            showMapLoading('Memuat peta...');
            basemaps = buildBasemaps();
            basemaps.satelit.eachLayer(trackInitialTiles);
            setBasemap('satelit');

            // Cadangan bila tile lambat/gagal dimuat agar overlay tidak menggantung.
            setTimeout(() => {
                if (!initialTilesLoaded) {
                    initialTilesLoaded = true;
                    hideMapLoading();
                }
            }, 10000);

            document.querySelectorAll('.basemap-btn').forEach(btn => {
                btn.addEventListener('click', () => setBasemap(btn.dataset.basemap));
            });

            initLayerTree();

            const layerPanel = document.getElementById('layerPanel');
            const layerBtn = document.getElementById('btnToggleLayers');
            const toggleLayerPanel = (open) => {
                layerPanel.classList.toggle('d-none', !open);
                layerBtn.classList.toggle('active', open);
                layerBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            };
            layerBtn.addEventListener('click', () => toggleLayerPanel(layerPanel.classList.contains('d-none')));
            document.getElementById('btnCloseLayers').addEventListener('click', () => toggleLayerPanel(false));

            document.querySelectorAll('.layer-zoom').forEach(btn => {
                btn.addEventListener('click', () => zoomToCategory(btn.dataset.categoryId));
            });

            initCoordinateReadout();
            initToolbar();
            initMeasure();
            initSearch();
            initLayerControls();
        }

        // ---------- Daftar layer (akordeon bertingkat seperti frontend) ----------
        function setLayerVisibility(checkbox, visible) {
            checkbox.checked = visible;

            if (visible) {
                showMapLayer(checkbox.value);
            } else {
                hideMapLayer(checkbox.value);
            }
        }

        function initLayerTree() {
            document.querySelectorAll('.map-layer-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', function() {
                    setLayerVisibility(this, this.checked);

                    // Checkbox induk mengatur seluruh sub kategorinya.
                    const children = this.closest('.layer-node').querySelector(':scope > .layer-children');
                    if (children) {
                        children.querySelectorAll('.map-layer-checkbox').forEach(child => {
                            if (child.checked !== this.checked) {
                                setLayerVisibility(child, this.checked);
                            }
                        });

                        if (this.checked) {
                            this.closest('.layer-node').classList.add('open');
                            children.classList.remove('d-none');
                        }
                    }
                });
            });

            document.querySelectorAll('.layer-toggle:not(.layer-toggle-empty)').forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    const node = toggle.closest('.layer-node');
                    const open = node.classList.toggle('open');
                    node.querySelector(':scope > .layer-children').classList.toggle('d-none', !open);
                });
            });

            const input = document.getElementById('layerSearch');
            const clearBtn = document.getElementById('layerSearchClear');
            const empty = document.getElementById('layerSearchEmpty');

            function filterNode(node, query) {
                const children = node.querySelector(':scope > .layer-children');
                const ownMatch = node.dataset.name.includes(query);
                let childMatch = false;

                if (children) {
                    children.querySelectorAll(':scope > .layer-node').forEach(child => {
                        if (filterNode(child, query)) childMatch = true;
                    });
                }

                const visible = query === '' || ownMatch || childMatch;
                node.classList.toggle('d-none', !visible);

                if (children && query !== '') {
                    // Sub kategori tampil lengkap bila induknya cocok; kalau tidak, hanya yang cocok.
                    if (ownMatch && !childMatch) {
                        children.querySelectorAll('.layer-node').forEach(n => n.classList.remove('d-none'));
                    }
                    node.classList.toggle('open', true);
                    children.classList.remove('d-none');
                }

                return visible;
            }

            function applySearch() {
                const query = input.value.trim().toLowerCase();
                clearBtn.classList.toggle('d-none', query === '');

                let anyVisible = false;
                document.querySelectorAll('#mapLayerList > .layer-node').forEach(node => {
                    if (filterNode(node, query)) anyVisible = true;
                });

                empty.classList.toggle('d-none', anyVisible || query === '');
            }

            input.addEventListener('input', applySearch);
            clearBtn.addEventListener('click', () => {
                input.value = '';
                applySearch();
                input.focus();
            });
        }

        // ---------- Koordinat kursor ----------
        function initCoordinateReadout() {
            const text = document.getElementById('mapCoordsText');
            const zoomText = document.getElementById('mapZoomText');

            zoomText.textContent = dataSpasialMap.getZoom();
            dataSpasialMap.on('zoomend', () => zoomText.textContent = dataSpasialMap.getZoom());
            dataSpasialMap.on('mousemove', (e) => {
                text.textContent = e.latlng.lat.toFixed(6) + ', ' + e.latlng.lng.toFixed(6);
            });

            dataSpasialMap.on('contextmenu', (e) => {
                if (drawMode) return;

                const value = e.latlng.lat.toFixed(6) + ', ' + e.latlng.lng.toFixed(6);
                const box = document.createElement('div');
                const label = document.createElement('div');
                label.className = 'small fw-semibold mb-1';
                label.textContent = value;
                const copy = document.createElement('button');
                copy.className = 'btn btn-sm btn-outline-primary';
                copy.innerHTML = '<i class="mdi mdi-content-copy"></i> Salin koordinat';
                copy.addEventListener('click', () => {
                    navigator.clipboard?.writeText(value);
                    copy.innerHTML = '<i class="mdi mdi-check"></i> Tersalin';
                });
                box.append(label, copy);

                L.popup().setLatLng(e.latlng).setContent(box).openOn(dataSpasialMap);
            });
        }

        // ---------- Toolbar ----------
        function initToolbar() {
            const wrapper = document.getElementById('mapWrapper');

            document.getElementById('toolFullscreen').addEventListener('click', () => {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                } else {
                    wrapper.requestFullscreen?.();
                }
            });

            document.addEventListener('fullscreenchange', () => {
                setTimeout(() => dataSpasialMap.invalidateSize(), 100);
                document.getElementById('toolFullscreen').classList.toggle('active', !!document.fullscreenElement);
            });

            document.getElementById('toolHome').addEventListener('click', () => {
                dataSpasialMap.flyTo(HOME_VIEW.center, HOME_VIEW.zoom);
            });

            let locateMarker = null;
            document.getElementById('toolLocate').addEventListener('click', () => {
                dataSpasialMap.locate({
                    setView: true,
                    maxZoom: 16
                });
            });
            dataSpasialMap.on('locationfound', (e) => {
                if (locateMarker) dataSpasialMap.removeLayer(locateMarker);
                locateMarker = L.circleMarker(e.latlng, {
                    radius: 8,
                    color: '#fff',
                    weight: 3,
                    fillColor: '#0d6efd',
                    fillOpacity: 1
                }).addTo(dataSpasialMap).bindPopup('Lokasi Anda');
            });
            dataSpasialMap.on('locationerror', () => {
                Swal.fire({
                    icon: 'warning',
                    title: 'Lokasi tidak tersedia',
                    text: 'Izinkan akses lokasi pada browser Anda.'
                });
            });
        }

        // ---------- Gambar & ukur (titik, garis, poligon) ----------
        const exportUrl = '{{ route('data-spatial.export-drawings') }}';
        let drawMode = null; // 'point' | 'distance' | 'area' | null
        let workingPoints = [];
        let workingGroup = null; // objek yang sedang digambar
        let workingShape = null;
        let workingTemp = null;
        let drawnGroup = null; // objek yang sudah selesai
        const drawnShapes = []; // {type, latlngs, name, layer}

        function formatDistance(meters) {
            return meters < 1000 ? meters.toFixed(1) + ' m' : (meters / 1000).toFixed(2) + ' km';
        }

        function formatArea(sqMeters) {
            if (sqMeters < 10000) return sqMeters.toFixed(1) + ' m²';
            if (sqMeters < 1000000) return (sqMeters / 10000).toFixed(2) + ' ha';
            return (sqMeters / 1000000).toFixed(2) + ' km²';
        }

        function polygonAreaMeters(latlngs) {
            const R = 6378137;
            const rad = (d) => d * Math.PI / 180;
            let total = 0;

            latlngs.forEach((p1, i) => {
                const p2 = latlngs[(i + 1) % latlngs.length];
                total += rad(p2.lng - p1.lng) * (2 + Math.sin(rad(p1.lat)) + Math.sin(rad(p2.lat)));
            });

            return Math.abs(total * R * R / 2);
        }

        function pathDistanceMeters(latlngs) {
            let total = 0;
            for (let i = 1; i < latlngs.length; i++) {
                total += latlngs[i - 1].distanceTo(latlngs[i]);
            }
            return total;
        }

        const DRAW_TITLES = {
            point: 'Gambar Titik',
            distance: 'Gambar Garis / Ukur Jarak',
            area: 'Gambar Poligon / Ukur Luas'
        };

        const DRAW_HINTS = {
            point: 'Klik peta untuk menambah titik. Klik "Selesai" atau Esc untuk berhenti.',
            distance: 'Klik peta untuk menambah titik garis. Klik ganda / Enter untuk selesai, Esc untuk batal.',
            area: 'Klik peta untuk menambah sudut poligon. Klik ganda / Enter untuk selesai, Esc untuk batal.'
        };

        function initMeasure() {
            workingGroup = L.layerGroup().addTo(dataSpasialMap);
            drawnGroup = L.layerGroup().addTo(dataSpasialMap);

            document.getElementById('toolPoint').addEventListener('click', () => startDraw('point'));
            document.getElementById('toolDistance').addEventListener('click', () => startDraw('distance'));
            document.getElementById('toolArea').addEventListener('click', () => startDraw('area'));
            document.getElementById('measureFinish').addEventListener('click', () => finishDraw());
            document.getElementById('measureClear').addEventListener('click', () => clearAllDrawings());

            document.querySelectorAll('.export-btn').forEach(btn => {
                btn.addEventListener('click', () => exportDrawings(btn.dataset.format));
            });

            dataSpasialMap.on('click', (e) => {
                if (!drawMode) return;

                if (drawMode === 'point') {
                    commitShape('Point', [e.latlng]);
                    return;
                }

                workingPoints.push(e.latlng);
                L.circleMarker(e.latlng, {
                    radius: 4,
                    color: '#fff',
                    weight: 2,
                    fillColor: '#0d6efd',
                    fillOpacity: 1
                }).addTo(workingGroup);
                redrawWorking();
            });

            dataSpasialMap.on('mousemove', (e) => {
                if (!drawMode || drawMode === 'point' || workingPoints.length === 0) return;
                if (workingTemp) workingGroup.removeLayer(workingTemp);
                workingTemp = L.polyline([workingPoints[workingPoints.length - 1], e.latlng], {
                    color: '#0d6efd',
                    weight: 2,
                    dashArray: '5,6'
                }).addTo(workingGroup);
            });

            dataSpasialMap.on('dblclick', () => {
                if (!drawMode || drawMode === 'point') return;
                workingPoints.pop(); // klik kedua dari klik ganda
                finishDraw();
            });

            document.addEventListener('keydown', (e) => {
                if (!drawMode) return;
                if (e.key === 'Escape') cancelWorking(true);
                if (e.key === 'Enter') finishDraw();
            });

            updateDrawSummary();
        }

        function startDraw(mode) {
            if (drawMode === mode) {
                finishDraw();
                return;
            }

            cancelWorking(false);
            drawMode = mode;
            dataSpasialMap.doubleClickZoom.disable();
            dataSpasialMap.closePopup();

            document.getElementById('mapWrapper').classList.add('measuring');
            ['point', 'distance', 'area'].forEach(m => {
                const id = m === 'point' ? 'toolPoint' : (m === 'distance' ? 'toolDistance' : 'toolArea');
                document.getElementById(id).classList.toggle('active', m === mode);
            });

            document.getElementById('measureTitle').textContent = DRAW_TITLES[mode];
            document.getElementById('measureHint').textContent = DRAW_HINTS[mode];
            document.getElementById('measureValue').textContent = '-';
            document.getElementById('measurePanel').classList.remove('d-none');
        }

        function redrawWorking() {
            if (workingShape) workingGroup.removeLayer(workingShape);
            if (workingTemp) {
                workingGroup.removeLayer(workingTemp);
                workingTemp = null;
            }

            const valueEl = document.getElementById('measureValue');
            workingShape = null;
            valueEl.textContent = '-';

            if (drawMode === 'area' && workingPoints.length >= 3) {
                workingShape = L.polygon(workingPoints, {
                    color: '#0d6efd',
                    weight: 3,
                    fillOpacity: 0.2
                }).addTo(workingGroup);
                valueEl.textContent = formatArea(polygonAreaMeters(workingPoints));
            } else if (workingPoints.length >= 2) {
                workingShape = L.polyline(workingPoints, {
                    color: '#0d6efd',
                    weight: 3
                }).addTo(workingGroup);
                if (drawMode === 'distance') {
                    valueEl.textContent = formatDistance(pathDistanceMeters(workingPoints));
                }
            }
        }

        function clearWorking() {
            workingGroup.clearLayers();
            workingPoints = [];
            workingShape = null;
            workingTemp = null;
        }

        function endDrawMode() {
            drawMode = null;
            dataSpasialMap.doubleClickZoom.enable();
            document.getElementById('mapWrapper').classList.remove('measuring');
            ['toolPoint', 'toolDistance', 'toolArea'].forEach(id => {
                document.getElementById(id).classList.remove('active');
            });
        }

        // Batal: buang gambar yang belum selesai. Esc kedua menutup mode gambar.
        function cancelWorking(exitMode) {
            const hadWork = workingPoints.length > 0;
            clearWorking();
            document.getElementById('measureValue').textContent = '-';

            if (exitMode && !hadWork) {
                endDrawMode();
            }
        }

        // Selesai: simpan objek yang sedang digambar (bila cukup titik) lalu keluar dari mode gambar.
        function finishDraw() {
            if (drawMode === 'distance' && workingPoints.length >= 2) {
                commitShape('LineString', workingPoints);
            } else if (drawMode === 'area' && workingPoints.length >= 3) {
                commitShape('Polygon', workingPoints);
            }

            clearWorking();
            endDrawMode();
            document.getElementById('measureHint').textContent = 'Gunakan tombol di bawah untuk menyimpan sebagai file.';
        }

        function commitShape(type, latlngs) {
            const points = latlngs.slice();
            const index = drawnShapes.filter(s => s.type === type).length + 1;
            const labels = {
                Point: 'Titik',
                LineString: 'Garis',
                Polygon: 'Poligon'
            };
            let name = `${labels[type]} ${index}`;
            let layer;
            let measureText = '';

            if (type === 'Point') {
                layer = L.marker(points[0]);
                measureText = points[0].lat.toFixed(6) + ', ' + points[0].lng.toFixed(6);
            } else if (type === 'LineString') {
                layer = L.polyline(points, {
                    color: '#e8590c',
                    weight: 5,
                    opacity: 0.9,
                    lineCap: 'round',
                    lineJoin: 'round'
                });
                measureText = formatDistance(pathDistanceMeters(points));
            } else {
                layer = L.polygon(points, {
                    color: '#e8590c',
                    weight: 2,
                    opacity: 0.8,
                    fillColor: '#e8590c',
                    fillOpacity: 0.3
                });
                measureText = formatArea(polygonAreaMeters(points));
            }

            const shape = {
                type,
                latlngs: points,
                name,
                layer
            };

            layer.bindPopup(() => {
                const box = document.createElement('div');
                const title = document.createElement('div');
                title.className = 'map-popup-title';
                title.textContent = shape.name;
                const value = document.createElement('div');
                value.className = 'small text-muted';
                value.textContent = measureText;
                const remove = document.createElement('button');
                remove.className = 'btn btn-sm btn-outline-danger mt-2';
                remove.innerHTML = '<i class="mdi mdi-trash-can-outline"></i> Hapus objek';
                remove.addEventListener('click', () => removeDrawnShape(shape));
                box.append(title, value, remove);
                return box;
            });

            if (type !== 'Point') {
                layer.bindTooltip(measureText, {
                    permanent: true,
                    direction: 'center',
                    className: 'measure-label'
                });
            }

            layer.addTo(drawnGroup);
            drawnShapes.push(shape);
            updateDrawSummary();
        }

        function removeDrawnShape(shape) {
            drawnGroup.removeLayer(shape.layer);
            drawnShapes.splice(drawnShapes.indexOf(shape), 1);
            updateDrawSummary();
        }

        function clearAllDrawings() {
            clearWorking();
            drawnGroup.clearLayers();
            drawnShapes.length = 0;
            endDrawMode();
            updateDrawSummary();
            document.getElementById('measurePanel').classList.add('d-none');
        }

        function updateDrawSummary() {
            const counts = {
                Point: 0,
                LineString: 0,
                Polygon: 0
            };
            drawnShapes.forEach(s => counts[s.type]++);

            document.getElementById('drawSummary').textContent = drawnShapes.length ?
                `${drawnShapes.length} objek: ${counts.Point} titik, ${counts.LineString} garis, ${counts.Polygon} poligon` :
                'Belum ada objek tersimpan di peta.';

            document.querySelectorAll('.export-btn').forEach(btn => {
                btn.disabled = drawnShapes.length === 0;
            });

            if (drawnShapes.length > 0) {
                document.getElementById('measurePanel').classList.remove('d-none');
            }
        }

        function drawnFeatures() {
            return drawnShapes.map(shape => {
                const position = (ll) => [ll.lng, ll.lat];
                let coordinates;

                if (shape.type === 'Point') {
                    coordinates = position(shape.latlngs[0]);
                } else if (shape.type === 'LineString') {
                    coordinates = shape.latlngs.map(position);
                } else {
                    const ring = shape.latlngs.map(position);
                    ring.push(ring[0]);
                    coordinates = [ring];
                }

                return {
                    type: shape.type,
                    name: shape.name,
                    coordinates
                };
            });
        }

        function downloadBlob(blob, filename) {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        }

        function exportDrawings(format) {
            if (drawnShapes.length === 0) return;

            const features = drawnFeatures();

            if (format === 'geojson') {
                const collection = {
                    type: 'FeatureCollection',
                    features: features.map(f => ({
                        type: 'Feature',
                        properties: {
                            name: f.name
                        },
                        geometry: {
                            type: f.type,
                            coordinates: f.coordinates
                        }
                    }))
                };
                downloadBlob(new Blob([JSON.stringify(collection, null, 2)], {
                    type: 'application/geo+json'
                }), 'gambar-peta.geojson');
                return;
            }

            fetch(exportUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/octet-stream',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        format,
                        features
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error('Gagal membuat file ' + format.toUpperCase());
                    return response.blob();
                })
                .then(blob => downloadBlob(blob, 'gambar-peta.' + format))
                .catch(error => Swal.fire({
                    icon: 'error',
                    title: 'Gagal mengunduh',
                    text: error.message
                }));
        }

        // ---------- Pencarian lokasi & data ----------
        function initSearch() {
            const input = document.getElementById('mapSearchInput');
            const results = document.getElementById('mapSearchResults');
            const clearBtn = document.getElementById('mapSearchClear');
            let searchMarker = null;
            let debounce = null;

            function goTo(lat, lng, label, zoom = 15) {
                if (searchMarker) dataSpasialMap.removeLayer(searchMarker);
                searchMarker = L.marker([lat, lng]).addTo(dataSpasialMap).bindPopup(label).openPopup();
                dataSpasialMap.flyTo([lat, lng], zoom);
                results.classList.add('d-none');
            }

            function addHeading(text) {
                const el = document.createElement('div');
                el.className = 'list-group-item result-heading';
                el.textContent = text;
                results.appendChild(el);
            }

            function addItem(text, onClick) {
                const el = document.createElement('a');
                el.className = 'list-group-item list-group-item-action';
                el.textContent = text;
                el.addEventListener('click', onClick);
                results.appendChild(el);
            }

            function search() {
                const query = input.value.trim();
                results.innerHTML = '';
                clearBtn.classList.toggle('d-none', query === '');

                if (query.length < 2) {
                    results.classList.add('d-none');
                    return;
                }

                // Koordinat "lat, lng"
                const coord = query.match(/^(-?\d+(?:\.\d+)?)\s*[, ]\s*(-?\d+(?:\.\d+)?)$/);
                if (coord) {
                    const lat = parseFloat(coord[1]);
                    const lng = parseFloat(coord[2]);
                    if (Math.abs(lat) <= 90 && Math.abs(lng) <= 180) {
                        addHeading('Koordinat');
                        addItem(`Ke ${lat.toFixed(6)}, ${lng.toFixed(6)}`, () => goTo(lat, lng,
                            `${lat.toFixed(6)}, ${lng.toFixed(6)}`, 16));
                        results.classList.remove('d-none');
                        return;
                    }
                }

                // Data yang sudah dimuat di peta
                const lowered = query.toLowerCase();
                const matches = [];
                Object.values(featureIndex).forEach(list => list.forEach(item => {
                    const haystack = [item.props.deskripsi, item.props.kategori].join(' ').toLowerCase();
                    if (haystack.includes(lowered)) matches.push(item);
                }));

                if (matches.length) {
                    addHeading(`Data di peta (${matches.length})`);
                    matches.slice(0, 8).forEach(item => {
                        addItem(`${item.props.deskripsi || 'Tanpa nama'} — ${item.props.kategori || '-'}`, () => {
                            focusFeature(item.layer);
                            results.classList.add('d-none');
                        });
                    });
                }

                results.classList.remove('d-none');

                // Nama tempat (Nominatim)
                clearTimeout(debounce);
                debounce = setTimeout(() => {
                    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&countrycodes=id&q=' +
                            encodeURIComponent(query))
                        .then(r => r.json())
                        .then(places => {
                            if (input.value.trim() !== query || !places.length) {
                                if (!matches.length && !results.children.length) {
                                    addHeading('Tidak ada hasil');
                                }
                                return;
                            }

                            addHeading('Lokasi');
                            places.forEach(place => addItem(place.display_name, () => {
                                const box = place.boundingbox;
                                goTo(parseFloat(place.lat), parseFloat(place.lon), place.display_name);
                                if (box) {
                                    dataSpasialMap.flyToBounds([
                                        [parseFloat(box[0]), parseFloat(box[2])],
                                        [parseFloat(box[1]), parseFloat(box[3])]
                                    ]);
                                }
                            }));
                        })
                        .catch(() => {
                            if (!results.children.length) addHeading('Pencarian lokasi tidak tersedia');
                        });
                }, 500);
            }

            input.addEventListener('input', search);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    input.value = '';
                    search();
                }
            });
            clearBtn.addEventListener('click', () => {
                input.value = '';
                search();
            });
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.map-search')) results.classList.add('d-none');
            });
        }

        function focusFeature(layer) {
            if (layer.getBounds) {
                dataSpasialMap.flyToBounds(layer.getBounds(), {
                    maxZoom: 17,
                    padding: [40, 40]
                });
            } else if (layer.getLatLng) {
                dataSpasialMap.flyTo(layer.getLatLng(), 16);
            }

            setTimeout(() => layer.openPopup(), 700);
        }

        // ---------- Kontrol layer ----------
        function initLayerControls() {
            const slider = document.getElementById('layerOpacity');
            const valueLabel = document.getElementById('layerOpacityValue');

            slider.addEventListener('input', () => {
                layerOpacity = slider.value / 100;
                valueLabel.textContent = slider.value + '%';
                applyOpacityToAll();
            });

            document.getElementById('btnZoomActive').addEventListener('click', () => {
                const bounds = L.latLngBounds([]);

                Object.entries(mapLayerGroups).forEach(([id, group]) => {
                    if (dataSpasialMap.hasLayer(group)) {
                        group.eachLayer(l => l.getBounds && bounds.extend(l.getBounds()));
                    }
                });

                if (bounds.isValid()) {
                    dataSpasialMap.flyToBounds(bounds, {
                        padding: [30, 30]
                    });
                }
            });
        }

        // Gaya sama dengan peta di frontend: garis tebal, poligon transparan.
        function featureStyle(feature) {
            const warna = feature.properties.warna || '#0d6efd';
            const type = feature.geometry.type;

            if (type === 'LineString' || type === 'MultiLineString') {
                return {
                    color: warna,
                    weight: 5,
                    opacity: 0.9,
                    lineCap: 'round',
                    lineJoin: 'round'
                };
            }

            return {
                color: warna,
                weight: 2,
                opacity: 0.7,
                fillColor: warna,
                fillOpacity: 0.4,
                lineCap: 'round',
                lineJoin: 'round'
            };
        }

        function applyOpacity(layer) {
            if (layer.setOpacity) {
                layer.setOpacity(layerOpacity);
                return;
            }

            if (!layer.setStyle) return;

            if (layer.__baseStyle === undefined) {
                layer.__baseStyle = {
                    opacity: layer.options.opacity ?? 1,
                    fillOpacity: layer.options.fillOpacity ?? 0.2
                };
            }

            layer.setStyle({
                opacity: layer.__baseStyle.opacity * layerOpacity,
                fillOpacity: layer.__baseStyle.fillOpacity * layerOpacity
            });
        }

        function applyOpacityToAll() {
            Object.values(featureIndex).forEach(list => list.forEach(item => applyOpacity(item.layer)));
        }

        function layerRow(categoryId) {
            return document.getElementById(`layer-cat-${categoryId}`)?.closest('.layer-row');
        }

        function setLayerLoading(categoryId, loading) {
            layerRow(categoryId)?.querySelector('.spinner-border')?.classList.toggle('d-none', !loading);
        }

        function setLayerCount(categoryId, count) {
            const row = layerRow(categoryId);
            if (!row) return;

            const badge = row.querySelector('.layer-count');
            badge.textContent = count;
            badge.classList.remove('d-none');
            row.querySelector('.layer-zoom').classList.toggle('d-none', count === 0);
        }

        function zoomToCategory(categoryId) {
            const group = mapLayerGroups[categoryId];
            if (!group) return;

            const bounds = L.latLngBounds([]);
            group.eachLayer(l => l.getBounds && bounds.extend(l.getBounds()));

            if (bounds.isValid()) {
                dataSpasialMap.flyToBounds(bounds, {
                    padding: [30, 30]
                });
            }
        }

        function getMapLayerGroup(categoryId) {
            if (!mapLayerGroups[categoryId]) {
                mapLayerGroups[categoryId] = L.layerGroup();
            }

            return mapLayerGroups[categoryId];
        }

        function hideMapLayer(categoryId) {
            if (mapLayerGroups[categoryId]) {
                dataSpasialMap.removeLayer(mapLayerGroups[categoryId]);
            }
        }

        const LAYER_BATCH_SIZE = 500;
        const layerLoadTokens = {}; // categoryId -> token, untuk membatalkan pemuatan yang usang

        function addFeaturesToLayer(categoryId, layerGroup, collection) {
            const geoJsonLayer = L.geoJSON(collection, {
                pointToLayer: (feature, latlng) => L.marker(latlng),
                style: (feature) => featureStyle(feature),
                onEachFeature: (feature, layer) => {
                    layer.bindPopup(buildMapPopup(feature.properties));
                    featureIndex[categoryId].push({
                        layer,
                        props: feature.properties
                    });

                    layer.on('mouseover', () => layer.setStyle && layer.setStyle({
                        weight: featureStyle(feature).weight + 2
                    }));
                    layer.on('mouseout', () => layer.setStyle && layer.setStyle({
                        weight: featureStyle(feature).weight
                    }));
                }
            });

            geoJsonLayer.addTo(layerGroup);
            applyOpacityToAll();
        }

        function fitToLayerGroup(layerGroup) {
            const bounds = L.latLngBounds([]);
            layerGroup.eachLayer(l => l.getBounds && bounds.extend(l.getBounds()));

            if (bounds.isValid()) {
                dataSpasialMap.fitBounds(bounds, {
                    padding: [30, 30]
                });
            }
        }

        // Ambil data per 500 fitur sampai habis agar semua data tampil tanpa membebani sekaligus.
        async function loadLayerInBatches(categoryId, layerGroup, token) {
            let offset = 0;
            let total = null;

            while (true) {
                const params = new URLSearchParams({
                    data_type: 'tematik',
                    category_id: categoryId,
                    limit: LAYER_BATCH_SIZE,
                    offset
                });

                const response = await fetch(`${geojsonUrl}?${params.toString()}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                // Layer sudah dimuat ulang atau dihapus centangnya: hentikan pemuatan lama.
                if (layerLoadTokens[categoryId] !== token) {
                    return false;
                }

                total = result.meta?.total_matching ?? total;
                const features = result.features || [];

                if (features.length > 0) {
                    addFeaturesToLayer(categoryId, layerGroup, result);
                }

                offset += features.length;
                setLayerCount(categoryId, offset);

                if (total !== null && total > LAYER_BATCH_SIZE) {
                    updateMapLoadingText(`Memuat data layer... ${offset} / ${total}`);
                }

                if (!result.meta?.has_more || features.length === 0) {
                    return true;
                }
            }
        }

        function showMapLayer(categoryId, forceReload = false) {
            const layerGroup = getMapLayerGroup(categoryId);
            layerGroup.addTo(dataSpasialMap);

            if (loadedMapLayers.has(categoryId) && !forceReload) {
                return;
            }

            const token = (layerLoadTokens[categoryId] || 0) + 1;
            layerLoadTokens[categoryId] = token;

            layerGroup.clearLayers();
            featureIndex[categoryId] = [];
            setLayerLoading(categoryId, true);
            showMapLoading('Memuat data layer...');

            loadLayerInBatches(categoryId, layerGroup, token)
                .then(completed => {
                    if (!completed) return;

                    loadedMapLayers.add(categoryId);
                    fitToLayerGroup(layerGroup);
                })
                .catch(error => {
                    console.error('Gagal memuat layer peta:', error);

                    layerLoadTokens[categoryId] = 0;
                    layerGroup.clearLayers();
                    featureIndex[categoryId] = [];
                    document.getElementById(`layer-cat-${categoryId}`).checked = false;
                    dataSpasialMap.removeLayer(layerGroup);

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal memuat layer',
                        text: error.message
                    });
                })
                .finally(() => {
                    setLayerLoading(categoryId, false);
                    hideMapLoading();
                });
        }

        function buildMapPopup(props) {
            const title = props.deskripsi || props.kategori || 'Tanpa Nama';
            const editUrl = editUrlTemplate.replace(':uuid', props.uuid);

            const container = document.createElement('div');

            const titleEl = document.createElement('div');
            titleEl.className = 'map-popup-title';
            titleEl.textContent = title;
            container.appendChild(titleEl);

            const kategoriEl = document.createElement('div');
            kategoriEl.className = 'text-muted small';
            kategoriEl.textContent = 'Kategori: ' + (props.kategori || '-');
            container.appendChild(kategoriEl);

            const actions = document.createElement('div');
            actions.className = 'map-popup-actions';

            const detailBtn = document.createElement('button');
            detailBtn.className = 'btn btn-sm btn-outline-info';
            detailBtn.innerHTML = '<i class="mdi mdi-eye"></i> Detail';
            detailBtn.addEventListener('click', () => showDetails(props.uuid));
            actions.appendChild(detailBtn);

            const editBtn = document.createElement('a');
            editBtn.className = 'btn btn-sm btn-outline-warning';
            editBtn.href = editUrl;
            editBtn.innerHTML = '<i class="mdi mdi-pencil"></i> Edit';
            actions.appendChild(editBtn);

            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn btn-sm btn-outline-danger';
            deleteBtn.innerHTML = '<i class="mdi mdi-trash-can-outline"></i> Hapus';
            deleteBtn.addEventListener('click', () => deleteMapFeature(props.uuid, props.kategori_id));
            actions.appendChild(deleteBtn);

            container.appendChild(actions);

            return container;
        }

        function deleteMapFeature(uuid, categoryId) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus data ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) return;

                fetch(destroyUrlTemplate.replace(':uuid', uuid), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Gagal menghapus data');

                        dataSpasialMap.closePopup();
                        loadedMapLayers.delete(categoryId);
                        showMapLayer(categoryId, true);

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil dihapus',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal menghapus',
                            text: error.message
                        });
                    });
            });
        }

        document.addEventListener('DOMContentLoaded', initDataSpasialMap);
    </script>
@endpush
