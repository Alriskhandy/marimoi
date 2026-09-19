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
        <div class="col-lg-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-semibold mb-0">
                            <i class="mdi mdi-layers-outline me-1"></i>Layer Kategori
                        </h6>
                    </div>
                    <p class="text-muted small mb-3">Centang kategori untuk menampilkan datanya di peta.</p>

                    <div id="mapLayerList" class="map-layer-list">
                        @include('backend.pages.data_spatial._map_layer_checklist', ['categories' => $categories])
                    </div>

                    <hr>
                    <label for="layerOpacity" class="form-label small fw-semibold mb-1">
                        <i class="mdi mdi-opacity me-1"></i>Transparansi data
                        <span id="layerOpacityValue" class="text-muted">100%</span>
                    </label>
                    <input type="range" class="form-range" id="layerOpacity" min="10" max="100"
                        step="5" value="100">

                    <div class="d-grid mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnZoomActive">
                            <i class="mdi mdi-fit-to-page-outline me-1"></i>Zoom ke semua layer aktif
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9 grid-margin stretch-card">
            <div class="card">
                <div class="card-body p-2">
                    <div id="mapTruncatedNotice" class="alert alert-warning d-none m-2"></div>

                    <div id="mapWrapper" class="map-wrapper">
                        <div id="dataSpasialMap"></div>

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
                            <button type="button" class="map-tool" id="toolDistance" title="Ukur jarak">
                                <i class="mdi mdi-ruler"></i>
                            </button>
                            <button type="button" class="map-tool" id="toolArea" title="Ukur luas">
                                <i class="mdi mdi-vector-polygon"></i>
                            </button>
                        </div>

                        <!-- Hasil pengukuran -->
                        <div id="measurePanel" class="measure-panel d-none">
                            <div class="fw-semibold small" id="measureTitle"></div>
                            <div class="measure-value" id="measureValue">-</div>
                            <div class="text-muted small" id="measureHint"></div>
                            <div class="mt-2 d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-primary" id="measureFinish">Selesai</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    id="measureClear">Hapus</button>
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
            height: calc(100vh - 260px);
            min-height: 560px;
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

        .layer-tree {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .layer-tree-child {
            margin-left: 18px;
            padding-left: 8px;
            border-left: 1px dashed #d0d5dd;
        }

        .layer-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 6px 8px;
            border-radius: 6px;
        }

        .layer-item:hover {
            background: #f3f6fb;
        }

        .layer-label {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1 1 auto;
            min-width: 0;
            margin: 0;
            cursor: pointer;
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
            accent-color: #0d6efd;
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
            background: #eef2f7;
            color: #495057;
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
            min-width: 190px;
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

            basemaps = buildBasemaps();
            setBasemap('satelit');

            document.querySelectorAll('.basemap-btn').forEach(btn => {
                btn.addEventListener('click', () => setBasemap(btn.dataset.basemap));
            });

            document.querySelectorAll('.map-layer-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        showMapLayer(this.value);
                    } else {
                        hideMapLayer(this.value);
                    }
                });
            });

            document.querySelectorAll('.layer-zoom').forEach(btn => {
                btn.addEventListener('click', () => zoomToCategory(btn.dataset.categoryId));
            });

            initCoordinateReadout();
            initToolbar();
            initMeasure();
            initSearch();
            initLayerControls();
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
                if (measureMode) return;

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

        // ---------- Pengukuran jarak & luas ----------
        let measureMode = null; // 'distance' | 'area' | null
        let measurePoints = [];
        let measureGroup = null;
        let measureShape = null;
        let measureTemp = null;

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

        function initMeasure() {
            measureGroup = L.layerGroup().addTo(dataSpasialMap);

            document.getElementById('toolDistance').addEventListener('click', () => startMeasure('distance'));
            document.getElementById('toolArea').addEventListener('click', () => startMeasure('area'));
            document.getElementById('measureFinish').addEventListener('click', () => stopMeasure(false));
            document.getElementById('measureClear').addEventListener('click', () => stopMeasure(true));

            dataSpasialMap.on('click', (e) => {
                if (!measureMode) return;
                measurePoints.push(e.latlng);
                L.circleMarker(e.latlng, {
                    radius: 4,
                    color: '#fff',
                    weight: 2,
                    fillColor: '#0d6efd',
                    fillOpacity: 1
                }).addTo(measureGroup);
                redrawMeasure();
            });

            dataSpasialMap.on('mousemove', (e) => {
                if (!measureMode || measurePoints.length === 0) return;
                if (measureTemp) measureGroup.removeLayer(measureTemp);
                measureTemp = L.polyline([measurePoints[measurePoints.length - 1], e.latlng], {
                    color: '#0d6efd',
                    weight: 2,
                    dashArray: '5,6'
                }).addTo(measureGroup);
            });

            dataSpasialMap.on('dblclick', () => {
                if (measureMode) stopMeasure(false);
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && measureMode) stopMeasure(true);
                if (e.key === 'Enter' && measureMode) stopMeasure(false);
            });
        }

        function startMeasure(mode) {
            if (measureMode === mode) {
                stopMeasure(true);
                return;
            }

            clearMeasure();
            measureMode = mode;
            dataSpasialMap.doubleClickZoom.disable();
            dataSpasialMap.closePopup();

            document.getElementById('mapWrapper').classList.add('measuring');
            document.getElementById('toolDistance').classList.toggle('active', mode === 'distance');
            document.getElementById('toolArea').classList.toggle('active', mode === 'area');

            document.getElementById('measureTitle').textContent = mode === 'distance' ? 'Ukur Jarak' : 'Ukur Luas';
            document.getElementById('measureHint').textContent =
                'Klik peta untuk menambah titik. Klik ganda / Enter untuk selesai, Esc untuk batal.';
            document.getElementById('measureValue').textContent = '-';
            document.getElementById('measurePanel').classList.remove('d-none');
        }

        function redrawMeasure() {
            if (measureShape) measureGroup.removeLayer(measureShape);
            if (measureTemp) {
                measureGroup.removeLayer(measureTemp);
                measureTemp = null;
            }

            const valueEl = document.getElementById('measureValue');

            if (measureMode === 'area' && measurePoints.length >= 3) {
                measureShape = L.polygon(measurePoints, {
                    color: '#0d6efd',
                    weight: 3,
                    fillOpacity: 0.2
                }).addTo(measureGroup);
                valueEl.textContent = formatArea(polygonAreaMeters(measurePoints));
            } else if (measurePoints.length >= 2) {
                measureShape = L.polyline(measurePoints, {
                    color: '#0d6efd',
                    weight: 3
                }).addTo(measureGroup);
                valueEl.textContent = measureMode === 'area' ? '-' : formatDistance(pathDistanceMeters(measurePoints));
            } else {
                measureShape = null;
                valueEl.textContent = '-';
            }
        }

        function stopMeasure(clear) {
            measureMode = null;
            dataSpasialMap.doubleClickZoom.enable();
            document.getElementById('mapWrapper').classList.remove('measuring');
            document.getElementById('toolDistance').classList.remove('active');
            document.getElementById('toolArea').classList.remove('active');

            if (measureTemp) {
                measureGroup.removeLayer(measureTemp);
                measureTemp = null;
            }

            if (clear) {
                clearMeasure();
                document.getElementById('measurePanel').classList.add('d-none');
            } else {
                document.getElementById('measureHint').textContent = 'Pengukuran selesai.';
            }
        }

        function clearMeasure() {
            measureGroup.clearLayers();
            measurePoints = [];
            measureShape = null;
            measureTemp = null;
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
            return document.getElementById(`layer-cat-${categoryId}`)?.closest('.layer-item');
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

        function showMapLayer(categoryId, forceReload = false) {
            const layerGroup = getMapLayerGroup(categoryId);
            layerGroup.addTo(dataSpasialMap);

            if (loadedMapLayers.has(categoryId) && !forceReload) {
                return;
            }

            layerGroup.clearLayers();
            featureIndex[categoryId] = [];
            setLayerLoading(categoryId, true);

            const params = new URLSearchParams();
            params.set('data_type', 'tematik');
            params.set('category_id', categoryId);

            const notice = document.getElementById('mapTruncatedNotice');

            fetch(`${geojsonUrl}?${params.toString()}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    loadedMapLayers.add(categoryId);

                    if (result.meta && result.meta.truncated) {
                        notice.textContent =
                            `Salah satu layer punya ${result.meta.total_matching} data, hanya ${result.meta.total_features} yang ditampilkan. Gunakan pencarian di tampilan tabel untuk mempersempit.`;
                        notice.classList.remove('d-none', 'alert-danger');
                        notice.classList.add('alert-warning');
                    }

                    const features = result.features || [];
                    setLayerCount(categoryId, features.length);

                    if (features.length === 0) {
                        return;
                    }

                    const geoJsonLayer = L.geoJSON(result, {
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

                    if (geoJsonLayer.getBounds().isValid()) {
                        dataSpasialMap.fitBounds(geoJsonLayer.getBounds(), {
                            padding: [30, 30]
                        });
                    }
                })
                .catch(error => {
                    console.error('Gagal memuat layer peta:', error);

                    document.getElementById(`layer-cat-${categoryId}`).checked = false;
                    dataSpasialMap.removeLayer(layerGroup);

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal memuat layer',
                        text: error.message
                    });
                })
                .finally(() => setLayerLoading(categoryId, false));
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
