// map-app.js - Enhanced version with comprehensive loading effects and 3-level hierarchy
/**
 * map-app.js - Enhanced version with comprehensive loading effects and 3-level hierarchy
 * Entry point utama aplikasi peta frontend dengan loading data yang efisien dan visual loading indicators.
 */

/**
 * Konfigurasi utama peta, termasuk daftar basemap, center, zoom, dan style default.
 */
const mapConfig = {
    weight: 6,
    center: [0.735485, 128.028201], // Koordinat tengah Maluku Utara
    zoom: 7,
    baseMapsList: [
        // OpenStreetMap
        {
            id: "osm",
            label: "OpenStreetMap",
            url: "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
            maxZoom: 19,
        },

        // ESRI Streets
        {
            id: "esri-streets",
            label: "ESRI Streets",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 19,
        },

        // Topographic
        {
            id: "esri-topographic",
            label: "Topographic",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 19,
        },

        // ESRI Oceans
        {
            id: "esri-oceans",
            label: "ESRI Oceans",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/Ocean/World_Ocean_Base/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 16,
        },

        // ESRI World Imagery
        {
            id: "esri-world-imagery",
            label: "ESRI World Imagery",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 18,
        },

        // ESRI Dark Gray Canvas
        {
            id: "esri-dark-gray",
            label: "ESRI Dark Gray Canvas",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 16,
        },

        // Light Gray Canvas
        {
            id: "esri-light-gray",
            label: "Light Gray Canvas",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 16,
        },

        // Google Maps (mungkin perlu API key)
        {
            id: "google-roadmap",
            label: "Google Map (ROADMAP)",
            url: "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            maxZoom: 20,
        },
        {
            id: "google-hybrid",
            label: "Google Map (Hybrid)",
            url: "https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            maxZoom: 20,
        },
        {
            id: "google-terrain",
            label: "Google Map (Terrain)",
            url: "https://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            maxZoom: 16,
        },
    ],
};

/**
 * Inisialisasi objek Leaflet map dengan konfigurasi awal.
 */
/**
 * Pengganti scrollWheelZoom bawaan Leaflet (yang men-debounce wheel lalu melompat
 * dengan animasi per kelompok). Setiap event wheel hanya menggeser target zoom; tiap
 * frame zoom peta dikejar ke target di sekitar posisi kursor, sehingga scroll mouse
 * dan pinch/scroll trackpad (wheel + ctrlKey) mengalir kontinu.
 *
 * Anti-goyang: (1) titik acuan dihitung dari center/zoom eksak milik handler ini dan
 * hanya diperbarui saat kursor benar-benar berpindah, supaya galat pembulatan tidak
 * menumpuk; (2) Leaflet membulatkan pixel origin ke piksel bulat di setiap _move —
 * sisa pecahannya dikompensasi dengan translate sub-piksel pada map pane.
 */
const SmoothWheelZoom = L.Handler.extend({
    addHooks: function () {
        L.DomEvent.on(this._map.getContainer(), "wheel", this._onWheel, this);
    },

    removeHooks: function () {
        L.DomEvent.off(this._map.getContainer(), "wheel", this._onWheel, this);
        this._stop();
    },

    _onWheel: function (e) {
        L.DomEvent.preventDefault(e);
        const map = this._map;
        const sensitivity = e.ctrlKey ? 0.03 : 0.015;
        const mousePoint = map.mouseEventToContainerPoint(e);

        if (!this._isWheeling) {
            this._start();
        }

        if (!this._mousePoint || this._mousePoint.distanceTo(mousePoint) > 2) {
            this._mousePoint = mousePoint;
            this._anchorLatLng = this._containerPointToExactLatLng(mousePoint);
        }

        this._goalZoom = Math.min(
            map.getMaxZoom(),
            Math.max(map.getMinZoom(), this._goalZoom + L.DomEvent.getWheelDelta(e) * sensitivity)
        );

        clearTimeout(this._endTimer);
        this._endTimer = setTimeout(() => this._finish(), 220);
    },

    _start: function () {
        const map = this._map;
        map.stop();
        this._isWheeling = true;
        this._zoom = map.getZoom();
        this._center = map.getCenter();
        this._goalZoom = this._zoom;
        this._mousePoint = null;
        map._moveStart(true, false);
        this._frame = requestAnimationFrame(() => this._step());
    },

    _containerPointToExactLatLng: function (point) {
        const map = this._map;
        const offset = point.subtract(map.getSize().divideBy(2));
        return map.unproject(map.project(this._center, this._zoom).add(offset), this._zoom);
    },

    _step: function () {
        const map = this._map;
        const diff = this._goalZoom - this._zoom;

        if (Math.abs(diff) > 0.0005) {
            this._zoom = Math.abs(diff) < 0.005 ? this._goalZoom : this._zoom + diff * 0.2;
            const offset = this._mousePoint.subtract(map.getSize().divideBy(2));
            this._center = map.unproject(map.project(this._anchorLatLng, this._zoom).subtract(offset), this._zoom);
            map._move(this._center, this._zoom);
            this._compensateRounding();
        }

        this._frame = requestAnimationFrame(() => this._step());
    },

    _compensateRounding: function () {
        const map = this._map;
        const panePos = map._getMapPanePos();
        const exactOrigin = map
            .project(this._center, this._zoom)
            .subtract(map.getSize().divideBy(2))
            .add(panePos);
        const error = exactOrigin.subtract(map.getPixelOrigin());
        map.getPane("mapPane").style.transform =
            `translate3d(${panePos.x - error.x}px, ${panePos.y - error.y}px, 0)`;
    },

    _finish: function () {
        const snapped = Math.round(this._goalZoom);
        if (snapped !== this._goalZoom) {
            this._goalZoom = Math.min(this._map.getMaxZoom(), Math.max(this._map.getMinZoom(), snapped));
        }
        if (Math.abs(this._goalZoom - this._zoom) > 0.0005) {
            this._endTimer = setTimeout(() => this._finish(), 50);
            return;
        }
        this._stop();
        L.DomUtil.setPosition(this._map.getPane("mapPane"), this._map._getMapPanePos());
        this._map._moveEnd(true);
    },

    _stop: function () {
        this._isWheeling = false;
        cancelAnimationFrame(this._frame);
        clearTimeout(this._endTimer);
    },
});
L.Map.addInitHook("addHandler", "smoothWheelZoom", SmoothWheelZoom);

// Peta selalu berhenti di level zoom bulat: di level pecahan tile diskalakan sehingga buram
// dan muncul garis celah antar-tile. Gerakan zoom tetap halus selama berlangsung.
const map = L.map("map", {
    zoomControl: true,
    attributionControl: true,
    zoomSnap: 1,
    zoomDelta: 1,
    scrollWheelZoom: false,
    smoothWheelZoom: true,
}).setView(mapConfig.center, mapConfig.zoom);

/**
 * Tombol Fullscreen & Home, dirender sebagai Leaflet control 'topleft' (bukan div
 * absolute custom) supaya otomatis memakai class/ukuran/spacing yang persis sama
 * dengan tombol zoom in/out bawaan Leaflet di atasnya (termasuk saat leaflet-touch
 * mode aktif dan tombol zoom membesar ke 30px) — tanpa perlu hardcode pixel manual.
 */
const LeftControlButtons = L.Control.extend({
    options: { position: "topleft" },
    onAdd: function () {
        const container = L.DomUtil.create("div", "leaflet-bar leaflet-control");

        const fullscreenBtn = L.DomUtil.create("a", "", container);
        fullscreenBtn.id = "btn-fullscreen";
        fullscreenBtn.href = "#";
        fullscreenBtn.title = "Tampilan Penuh";
        fullscreenBtn.setAttribute("role", "button");
        fullscreenBtn.setAttribute("aria-label", "Tampilan Penuh");
        fullscreenBtn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';

        const homeBtn = L.DomUtil.create("a", "", container);
        homeBtn.id = "btn-default-zoom";
        homeBtn.href = "#";
        homeBtn.title = "Default Zoom";
        homeBtn.setAttribute("role", "button");
        homeBtn.setAttribute("aria-label", "Default Zoom");
        homeBtn.innerHTML = '<i class="bi bi-house-door-fill"></i>';

        L.DomEvent.on(container, "click", L.DomEvent.preventDefault);
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.disableScrollPropagation(container);

        return container;
    },
});
map.addControl(new LeftControlButtons());

/**
 * Tombol toggle panel "Layer Tools", dirender sebagai Leaflet control 'topleft'
 * terpisah supaya otomatis menyambung tepat di bawah tombol Fullscreen/Home
 * dengan spacing yang sama seperti antar-control Leaflet lainnya.
 */
const LayerToolsControl = L.Control.extend({
    options: { position: "topleft" },
    onAdd: function () {
        const container = L.DomUtil.create("div", "leaflet-bar leaflet-control");

        const toolsBtn = L.DomUtil.create("a", "", container);
        toolsBtn.id = "btn-toggle-layer-tools";
        toolsBtn.href = "#";
        toolsBtn.title = "Layer Tools";
        toolsBtn.setAttribute("role", "button");
        toolsBtn.setAttribute("aria-label", "Layer Tools");
        toolsBtn.innerHTML = '<i class="bi bi-sliders"></i>';

        L.DomEvent.on(container, "click", L.DomEvent.preventDefault);
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.disableScrollPropagation(container);

        return container;
    },
});
map.addControl(new LayerToolsControl());

// Menyimpan opacity per layer aktif (key: nama kategori level-3) agar tetap
// konsisten saat panel Layer Tools dibuka/ditutup atau layer lain diaktifkan.
const layerOpacityState = new Map();

// Update layerGroups structure to support 3 levels
let layerGroups = {};
let currentBaseMap = null;
let kategoriWarnaMap = {};
let iconMap = {};
let loadedCategories = new Set(); // Track loaded categories
let isLoadingData = false; // Prevent concurrent loading
let currentLoadingCategory = null; // Track current loading category
let loadingProgressInterval = null; // For animated progress

// Add near the top after other global variables
let mapDataStore = null; // Will be initialized in DOMContentLoaded

/**
 * Create and manage loading overlay
 */
function createLoadingOverlay() {
    if (document.getElementById("map-loading-overlay")) return;

    const overlay = document.createElement("div");
    overlay.id = "map-loading-overlay";
    overlay.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: white;
        font-family: Arial, sans-serif;
    `;

    overlay.innerHTML = `
        <div class="loading-spinner" style="
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        "></div>
        <div id="loading-text" style="
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        ">Memuat data...</div>
        <div id="loading-progress" style="
            font-size: 14px;
            opacity: 0.9;
            text-align: center;
        ">Mempersiapkan...</div>
        <div id="loading-bar-container" style="
            width: 300px;
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            margin-top: 15px;
            overflow: hidden;
        ">
            <div id="loading-bar" style="
                width: 0%;
                height: 100%;
                background: linear-gradient(90deg, #4CAF50, #81C784);
                border-radius: 3px;
                transition: width 0.3s ease;
            "></div>
        </div>
    `;

    // Add CSS animation for spinner
    const style = document.createElement("style");
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
    `;
    document.head.appendChild(style);

    const mapContainer = document.getElementById("map");
    mapContainer.style.position = "relative";
    mapContainer.appendChild(overlay);
}

/**
 * Show loading overlay with category name
 */
function showLoadingOverlay(categoryName) {
    createLoadingOverlay();
    const overlay = document.getElementById("map-loading-overlay");
    const loadingText = document.getElementById("loading-text");
    const loadingProgress = document.getElementById("loading-progress");
    const loadingBar = document.getElementById("loading-bar");

    currentLoadingCategory = categoryName;
    loadingText.textContent = `Memuat data ${categoryName}`;
    loadingProgress.textContent = "Mengirim permintaan ke server...";
    loadingBar.style.width = "10%";

    overlay.style.display = "flex";
}

/**
 * Update loading progress
 */
function updateLoadingProgress(loaded, total, message = "") {
    const loadingProgress = document.getElementById("loading-progress");
    const loadingBar = document.getElementById("loading-bar");

    if (loadingProgress && loadingBar) {
        const percentage = Math.min(Math.max((loaded / total) * 100, 10), 100);
        loadingBar.style.width = `${percentage}%`;

        if (message) {
            loadingProgress.textContent = message;
        } else {
            loadingProgress.textContent = `${loaded} dari ${total} fitur dimuat`;
        }
    }
}

/**
 * Hide loading overlay
 */
function hideLoadingOverlay() {
    const overlay = document.getElementById("map-loading-overlay");
    if (overlay) {
        overlay.style.display = "none";
    }
    currentLoadingCategory = null;

    if (loadingProgressInterval) {
        clearInterval(loadingProgressInterval);
        loadingProgressInterval = null;
    }
}


function showAlert(message, type = "info", persistent = false) {
    // Debug logging disabled for production
    const toastContainer = document.getElementById("toast-container");
    if (!toastContainer) return;

    // Mapping warna sesuai tipe
    const colors = {
        success: "bg-green-500 text-white",
        danger: "bg-red-500 text-white",
        warning: "bg-yellow-500 text-black",
        info: "bg-blue-500 text-white",
    };

    const isLoading =
        type === "info" &&
        (message.includes("Memuat") || message.includes("Loading"));

    // Elemen toast
    const toast = document.createElement("div");
    toast.className = `
        flex items-center px-4 py-3 rounded-lg shadow-lg text-sm font-medium
        ${colors[type] || colors.info}
        transform transition-all duration-500 opacity-0 translate-y-2
    `;

    if (isLoading) {
        toast.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>${message}</span>
            </div>
        `;
    } else {
        toast.innerHTML = `
            <span class="flex-1">${message}</span>
            <button class="ml-3 text-lg leading-none focus:outline-none">&times;</button>
        `;

        // Tombol close
        toast
            .querySelector("button")
            .addEventListener("click", () => hideToast(toast));
    }

    toastContainer.appendChild(toast);

    // Trigger animasi masuk
    setTimeout(() => {
        toast.classList.remove("opacity-0", "translate-y-2");
        toast.classList.add("opacity-100", "translate-y-0");
    }, 50);

    // Auto hide
    if (!persistent && !isLoading) {
        const delay = type === "success" ? 4000 : 6000;
        setTimeout(() => hideToast(toast), delay);
    }

    return toast;
}

function hideToast(toast) {
    if (!toast || !toast.parentNode) return;

    toast.classList.add("opacity-0", "translate-y-2");
    setTimeout(() => {
        if (toast && toast.parentNode) {
            toast.remove();
        }
    }, 500);
}

/**
 * Menghasilkan style untuk kategori tertentu.
 */
function getStyleForCategory(kategori) {
    const warna = kategoriWarnaMap[kategori] || "#ECE6D6";

    // Return function yang akan dipanggil dengan feature
    return function (feature) {
        const geometryType = feature.geometry.type;
        const categoryStyles = {
            polygon: {
                color: warna,
                weight: 2,
                opacity: 0.7,
                fillColor: warna,
                fillOpacity: 0.4,
                lineCap: "round",
                lineJoin: "round",
            },
            line: {
                color: warna,
                weight: 5,
                opacity: 0.9,
                lineCap: "round",
                lineJoin: "round",
            },
        };

        // Tentukan style berdasarkan geometry type
        if (
            geometryType === "LineString" ||
            geometryType === "MultiLineString"
        ) {
            return {
                ...categoryStyles.line,
                interactive: true,
                className: "leaflet-interactive-line",
            };
        } else if (
            geometryType === "Polygon" ||
            geometryType === "MultiPolygon"
        ) {
            return {
                ...categoryStyles.polygon,
                interactive: true,
                className: "leaflet-interactive-polygon",
            };
        } else {
            // Point akan menggunakan marker, return basic style
            return {
                ...categoryStyles.polygon,
                interactive: true,
            };
        }
    };
}

/**
 * Membuat dan menampilkan legend pada UI berdasarkan kategori dan icon/warna.
 */
function generateLegend() {
    const legendContainer = document.getElementById("legend-content");
    if (!legendContainer) return;

    legendContainer.innerHTML = "";
    const added = new Set();

    // Ambil layer yang sedang aktif
    const activeLayers = new Set();

    // Loop through all levels to find active layers
    Object.entries(layerGroups).forEach(([rootName, secondLevel]) => {
        Object.entries(secondLevel).forEach(([secondName, thirdLevel]) => {
            Object.entries(thirdLevel).forEach(([thirdName, layer]) => {
                // Check if layer is added to map and has layers
                if (map.hasLayer(layer) && layer.getLayers().length > 0) {
                    activeLayers.add(thirdName);
                }
            });
        });
    });

    // If no active layers, show message
    if (activeLayers.size === 0) {
        legendContainer.innerHTML = `
            <div class="flex flex-col items-center justify-center text-center py-8 text-gray-500">
                <i class="bi bi-layers text-3xl mb-2"></i>
                <p class="text-sm">Tidak ada layer aktif</p>
                <p class="text-xs">Aktifkan layer untuk melihat legenda</p>
            </div>
        `;
        return;
    }

    // Only show legend for active layers
    Object.entries(layerGroups).forEach(([rootName, secondLevel]) => {
        Object.entries(secondLevel).forEach(([secondName, thirdLevel]) => {
            Object.entries(thirdLevel).forEach(([thirdName, layer]) => {
                // Skip if not active or already added
                if (!activeLayers.has(thirdName) || added.has(thirdName)) return;

                let icon = iconMap[thirdName] || null;
                let color =
                    kategoriWarnaMap[thirdName] || 
                    kategoriWarnaMap[secondName] || 
                    kategoriWarnaMap[rootName] || 
                    "#ccc";

                const legendItem = document.createElement('div');
                legendItem.className = 'flex items-center mb-2 w-full';

                const iconWrap = document.createElement('div');
                iconWrap.style.cssText = 'width: 14px; height: 14px; margin-right: 8px;';

                if (icon) {
                    iconWrap.className = 'custom-fa-icon flex-shrink-0 flex items-center justify-center';
                    iconWrap.style.cssText += 'background: transparent; border: none;';

                    const iconEl = document.createElement('i');
                    iconEl.className = `${icon} text-[${color}]`;
                    iconEl.style.cssText = `font-size: 12px; color: ${color}; line-height: 1;`;
                    iconWrap.appendChild(iconEl);
                } else {
                    iconWrap.className = 'flex-shrink-0';
                    iconWrap.style.cssText += `background-color: ${color}; border: 1px solid #333;`;
                }

                const labelEl = document.createElement('span');
                labelEl.className = 'flex-1';
                labelEl.style.fontSize = '0.85rem';
                labelEl.textContent = thirdName;

                legendItem.appendChild(iconWrap);
                legendItem.appendChild(labelEl);
                legendContainer.appendChild(legendItem);

                added.add(thirdName);
            });
        });
    });

    // If no legend items were added (edge case), show empty message
    if (added.size === 0) {
        legendContainer.innerHTML = `
            <div class="flex flex-col items-center justify-center text-center py-8 text-gray-500">
                <i class="bi bi-exclamation-triangle text-3xl mb-2"></i>
                <p class="text-sm">Legenda tidak tersedia</p>
                <p class="text-xs">Layer aktif tidak memiliki legenda</p>
            </div>
        `;
    }
}

/**
 * Baca opacity yang SEDANG dirender pada sebuah layerGroup (bukan asumsi
 * default 100%) — dipakai supaya nilai awal slider di Layer Tools selalu
 * sinkron dengan tampilan layer yang sebenarnya. Polygon dan garis punya
 * default opacity berbeda-beda (lihat getStyleForCategory).
 *
 * Setiap feature ditambahkan lewat `L.geoJSON(feature, {...}).addTo(targetLayer)`,
 * jadi anak langsung dari layerGroup adalah WRAPPER L.GeoJSON (FeatureGroup) —
 * options-nya cuma menyimpan referensi fungsi `style` yang dipakai, BUKAN angka
 * fillOpacity/opacity hasil resolusinya. Nilai numerik yang sebenarnya ada satu
 * level lebih dalam, di layer Path/Marker asli. Makanya di sini turun rekursif
 * lewat setiap eachLayer() sampai ketemu layer yang benar-benar punya opsi
 * numerik tsb.
 */
/**
 * Terapkan opacity ke seluruh isi layer group: area/garis lewat setStyle, marker lewat
 * setOpacity (termasuk marker di dalam marker cluster / L.GeoJSON bertingkat).
 */
function setLayerGroupOpacity(layer, opacity) {
    if (typeof layer.eachLayer === "function") {
        layer.eachLayer((child) => setLayerGroupOpacity(child, opacity));
    } else if (layer.marimoiFilteredOut) {
        return;
    } else if (typeof layer.setStyle === "function") {
        layer.setStyle({ opacity, fillOpacity: opacity });
    } else if (typeof layer.setOpacity === "function") {
        layer.setOpacity(opacity);
    }
}

function getLayerGroupOpacity(layerGroup) {
    let opacity = null;

    function visit(layer) {
        if (opacity !== null) return;

        if (layer.eachLayer) {
            layer.eachLayer(visit);
            return;
        }

        if (layer.options) {
            if (typeof layer.options.fillOpacity === "number") {
                opacity = layer.options.fillOpacity;
            } else if (typeof layer.options.opacity === "number") {
                opacity = layer.options.opacity;
            }
        }
    }

    if (layerGroup.eachLayer) {
        layerGroup.eachLayer(visit);
    }

    return opacity ?? 1;
}

/**
 * Nilai filter yang sedang aktif, dibaca dari 3 <select> di panel Filter.
 * String kosong berarti "semua" (tidak memfilter dimensi itu).
 */
function getActiveFilterValues() {
    return {
        kabupaten: document.getElementById("filter-kabupaten")?.value || "",
        tahun: document.getElementById("filter-tahun")?.value || "",
        opd_pengelola: document.getElementById("filter-opd")?.value || "",
    };
}

/**
 * Isi <select> dengan opsi baru yang belum ada, tanpa mengubah pilihan yang
 * sedang aktif (append-only) — dipanggil berulang setiap kali data baru dimuat.
 */
function ensureFilterOptions(selectEl, values) {
    if (!selectEl) return;
    const existing = new Set(Array.from(selectEl.options).map((o) => o.value));

    Array.from(values).sort().forEach((value) => {
        if (value === "" || existing.has(String(value))) return;
        const opt = document.createElement("option");
        opt.value = value;
        opt.textContent = value;
        selectEl.appendChild(opt);
        existing.add(String(value));
    });
}

/**
 * Jalan-jalan rekursif ke setiap leaf layer yang sedang aktif di peta (pola sama
 * dengan generateLegend()/getLayerGroupOpacity()), lalu: (a) kumpulkan nilai
 * distinct KABUPATEN/tahun/opd_pengelola untuk opsi dropdown, (b) terapkan
 * visibility sesuai filter aktif. Dipanggil setelah data baru dimuat, setiap kali
 * filter berubah, dan saat panel filter dibuka.
 */
function refreshFilterPanel() {
    const filters = getActiveFilterValues();
    const kabupatenSet = new Set();
    const tahunSet = new Set();
    const opdSet = new Set();
    let shown = 0;
    let total = 0;

    function matches(props) {
        if (filters.kabupaten && (props.KABUPATEN || "") !== filters.kabupaten) return false;
        if (filters.tahun && String(props.tahun || "") !== filters.tahun) return false;
        if (filters.opd_pengelola && (props.opd_pengelola || "") !== filters.opd_pengelola) return false;
        return true;
    }

    // Feature yang lolos filter memakai opacity pilihan pengguna (slider Layer Aktif /
    // Layer Tools); yang tersaring ditandai supaya slider tidak memunculkannya lagi.
    function applyVisibility(leaf, visible) {
        const userOpacity = layerOpacityState.get(leaf.feature?.properties?.kategori);
        leaf.marimoiFilteredOut = !visible;

        if (typeof leaf.setStyle === "function") {
            if (visible) {
                const opacityStyle = typeof userOpacity === "number" ? { opacity: userOpacity, fillOpacity: userOpacity } : {};
                leaf.setStyle({ ...(leaf.marimoiOriginalStyle || {}), ...opacityStyle });
            } else {
                leaf.marimoiOriginalStyle = leaf.marimoiOriginalStyle || { ...leaf.options };
                leaf.setStyle({ opacity: 0, fillOpacity: 0 });
            }
        } else if (typeof leaf.setOpacity === "function") {
            leaf.setOpacity(visible ? (userOpacity ?? 1) : 0);
        }
    }

    function visit(layer) {
        if (layer.eachLayer) {
            layer.eachLayer(visit);
            return;
        }
        if (!layer.feature?.properties) return;

        const props = layer.feature.properties;
        total++;
        if (props.KABUPATEN) kabupatenSet.add(props.KABUPATEN);
        if (props.tahun) tahunSet.add(String(props.tahun));
        if (props.opd_pengelola) opdSet.add(props.opd_pengelola);

        const visible = matches(props);
        if (visible) shown++;
        applyVisibility(layer, visible);
    }

    Object.values(layerGroups).forEach((secondLevel) => {
        Object.values(secondLevel).forEach((thirdLevel) => {
            Object.values(thirdLevel).forEach((leafGroup) => {
                if (map.hasLayer(leafGroup)) leafGroup.eachLayer(visit);
            });
        });
    });

    ensureFilterOptions(document.getElementById("filter-kabupaten"), kabupatenSet);
    ensureFilterOptions(document.getElementById("filter-tahun"), tahunSet);
    ensureFilterOptions(document.getElementById("filter-opd"), opdSet);

    const countEl = document.getElementById("filter-count");
    if (countEl) {
        countEl.textContent = total > 0 ? `${shown} dari ${total} titik ditampilkan` : "Belum ada data dimuat";
    }

    const activeCount = Object.values(filters).filter(Boolean).length;
    const summaryEl = document.getElementById("filter-summary-count");
    if (summaryEl) {
        summaryEl.textContent = activeCount > 0 ? String(activeCount) : "";
        summaryEl.classList.toggle("hidden", activeCount === 0);
    }
}

/**
 * Isi 3 dropdown filter dari nilai distinct yang ada di database (endpoint
 * /geojson/filter-options), supaya pilihan seperti "Kota Ternate" sudah bisa dipilih
 * sejak awal — tanpa menunggu satu pun layer dimuat/dicentang dulu. Dipanggil sekali
 * saat inisialisasi peta. refreshFilterPanel() tetap menambah opsi baru secara
 * progresif dari feature yang sudah dirender (ensureFilterOptions bersifat
 * append-only, jadi tidak ada duplikasi antara sumber server ini dan sumber client).
 */
async function loadFilterOptionsFromServer() {
    try {
        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);
        const params = new URLSearchParams();
        if (tipeLayer.type) params.set("type", tipeLayer.type);
        if (tipeLayer.sub_type) params.set("sub_type", tipeLayer.sub_type);
        if (tipeLayer.year) params.set("year", tipeLayer.year);

        const response = await fetch(`/geojson/filter-options?${params.toString()}`);
        if (!response.ok) return;

        const data = await response.json();
        ensureFilterOptions(document.getElementById("filter-kabupaten"), data.kabupaten || []);
        ensureFilterOptions(document.getElementById("filter-tahun"), (data.tahun || []).map(String));
        ensureFilterOptions(document.getElementById("filter-opd"), data.opd_pengelola || []);
    } catch (error) {
        console.error("Gagal memuat opsi filter:", error);
    }
}

/**
 * Muat & centang hanya kategori yang punya feature cocok dengan kombinasi filter aktif
 * (lewat endpoint /geojson/filter-categories) — bukan seluruh pohon layer, supaya
 * memilih satu Kabupaten/Kota saja tidak memicu pemuatan semua data yang ada.
 * Memakai pola pencarian checkbox + dispatchEvent("change") yang sama persis dengan
 * applySharedMapState() (sudah terbukti bekerja untuk memuat layer dari share link).
 */
/**
 * Nama kategori (dataset) yang punya data sesuai filter kabupaten/tahun/OPD.
 */
async function fetchCategoriesMatchingFilter(filters) {
    const urlPath = window.location.pathname.replace(/\/$/, "");
    const tipeLayer = getDataType(urlPath);
    const params = new URLSearchParams();
    if (tipeLayer.type) params.set("type", tipeLayer.type);
    if (tipeLayer.sub_type) params.set("sub_type", tipeLayer.sub_type);
    if (tipeLayer.year) params.set("year", tipeLayer.year);
    if (filters.kabupaten) params.set("kabupaten", filters.kabupaten);
    if (filters.tahun) params.set("tahun", filters.tahun);
    if (filters.opd_pengelola) params.set("opd_pengelola", filters.opd_pengelola);

    let categoryNames = [];
    try {
        const response = await fetch(`/geojson/filter-categories?${params.toString()}`);
        if (response.ok) {
            const data = await response.json();
            categoryNames = Array.isArray(data.categories) ? data.categories : [];
        }
    } catch (error) {
        console.error("Gagal memuat daftar layer yang cocok dengan filter:", error);
    }

    return categoryNames;
}

async function loadCategoriesMatchingFilter(filters) {
    const categoryNames = await fetchCategoriesMatchingFilter(filters);
    await window.MarimoiCatalog?.activateByNames(categoryNames);
}

/**
 * Terapkan filter yang sedang aktif: bila ada minimal satu dimensi filter terisi,
 * muat dulu kategori yang cocok (loadCategoriesMatchingFilter) supaya filter bekerja
 * lintas layer tanpa perlu layer dicentang manual lebih dulu, baru refreshFilterPanel()
 * menyaring hasilnya per-feature. Kontrol filter dikunci sementara selama pemuatan.
 */
async function applyStructuredFilters() {
    const filters = getActiveFilterValues();
    const hasActiveFilter = Object.values(filters).some(Boolean);

    if (!hasActiveFilter) {
        refreshFilterPanel();
        return;
    }

    const filterFields = ["filter-kabupaten", "filter-tahun", "filter-opd", "btn-reset-filter"];
    filterFields.forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.disabled = true;
    });

    const countEl = document.getElementById("filter-count");
    if (countEl) countEl.textContent = "Mencari layer yang cocok dengan filter...";

    try {
        await loadCategoriesMatchingFilter(filters);
    } finally {
        filterFields.forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.disabled = false;
        });
        refreshFilterPanel();
    }
}

/**
 * Resolusi warna representatif sebuah layer, sama persis dengan urutan
 * fallback yang dipakai generateLegend() supaya warna slider di Layer Tools
 * konsisten dengan warna swatch di Legenda.
 */
function getLayerColor(rootName, secondName, thirdName) {
    return (
        kategoriWarnaMap[thirdName] ||
        kategoriWarnaMap[secondName] ||
        kategoriWarnaMap[rootName] ||
        "#9ca3af"
    );
}

/**
 * Isi ulang panel "Layer Tools": satu slider transparansi per layer yang
 * sedang aktif di peta (bukan satu slider global untuk semua layer).
 *
 * Layer kategori point/marker (is_marker) dikecualikan: feature-nya dirender
 * sebagai L.marker (icon), bukan Path, jadi tidak punya .setStyle() sama
 * sekali — slider transparansi tidak akan berefek apa-apa padanya. Ditandai
 * lewat `iconMap`, flag yang sama yang sudah dipakai generateLegend() untuk
 * membedakan kategori marker dari kategori vector (lihat loadCategoriesMetadata).
 */
function updateLayerToolsPanel() {
    const content = document.getElementById("layer-tools-content");
    if (!content) return;

    const activeEntries = [];
    Object.entries(layerGroups).forEach(([rootName, secondLevel]) => {
        Object.entries(secondLevel).forEach(([secondName, thirdLevel]) => {
            Object.entries(thirdLevel).forEach(([thirdName, layerGroup]) => {
                if (iconMap[thirdName]) return; // layer point/marker, tidak ditampilkan

                if (
                    map.hasLayer(layerGroup) &&
                    layerGroup.getLayers &&
                    layerGroup.getLayers().length > 0
                ) {
                    activeEntries.push({ rootName, secondName, thirdName, layerGroup });
                }
            });
        });
    });

    content.innerHTML = "";

    if (activeEntries.length === 0) {
        const empty = document.createElement("div");
        empty.className = "text-center py-8 px-4";
        empty.innerHTML = `
            <i class="bi bi-layers text-4xl text-gray-300 mb-3 block"></i>
            <p class="text-sm font-semibold text-gray-700 mb-1">Tidak ada layer aktif</p>
            <p class="text-xs text-gray-400">Aktifkan layer dari panel Layer untuk menggunakan alat ini.</p>
        `;
        content.appendChild(empty);
        return;
    }

    activeEntries.forEach(({ rootName, secondName, thirdName, layerGroup }) => {
        // Nilai awal: pakai yang pernah diset user di sesi ini (jika ada),
        // kalau belum pernah sama sekali baca opacity ASLI dari layer yang
        // sedang dirender supaya value slider = tampilan layer sebenarnya.
        if (!layerOpacityState.has(thirdName)) {
            layerOpacityState.set(thirdName, getLayerGroupOpacity(layerGroup));
        }
        const opacity = layerOpacityState.get(thirdName);
        const color = getLayerColor(rootName, secondName, thirdName);

        const row = document.createElement("div");
        row.className = "px-4 py-3 border-b border-gray-200 last:border-b-0";

        const labelRow = document.createElement("div");
        labelRow.className = "flex items-center justify-between mb-2 gap-2";

        const labelWrap = document.createElement("span");
        labelWrap.className = "flex items-center gap-2 min-w-0";

        const colorDot = document.createElement("span");
        colorDot.className = "inline-block w-2.5 h-2.5 rounded-full shrink-0";
        colorDot.style.backgroundColor = color;

        const label = document.createElement("span");
        label.className = "text-sm text-gray-700 truncate";
        label.textContent = thirdName;

        labelWrap.appendChild(colorDot);
        labelWrap.appendChild(label);

        const valueLabel = document.createElement("span");
        valueLabel.className = "text-xs text-gray-400 shrink-0";
        valueLabel.textContent = `${Math.round(opacity * 100)}%`;

        labelRow.appendChild(labelWrap);
        labelRow.appendChild(valueLabel);

        const slider = document.createElement("input");
        slider.type = "range";
        slider.min = "0";
        slider.max = "100";
        slider.value = String(Math.round(opacity * 100));
        slider.className = "range range-sm w-full";
        slider.style.accentColor = color;
        slider.setAttribute("aria-label", `Transparansi ${thirdName}`);

        slider.addEventListener("input", (e) => {
            const val = Number(e.target.value) / 100;
            layerOpacityState.set(thirdName, val);
            valueLabel.textContent = `${e.target.value}%`;

            setLayerGroupOpacity(layerGroup, val);
        });
        slider.addEventListener("change", () => window.MarimoiCatalog?.renderActiveList());

        row.appendChild(labelRow);
        row.appendChild(slider);
        content.appendChild(row);
    });
}

/**
 * Posisikan panel Layer Tools tepat di bawah kolom tombol Leaflet (zoom,
 * fullscreen/home, layer tools) di sisi kiri peta, dihitung dari posisi
 * render sebenarnya supaya tetap presisi walau ukuran tombol Leaflet
 * berubah (mis. saat mode leaflet-touch aktif). max-height konten dihitung
 * dari sisa ruang yang benar-benar tersedia sampai tepi bawah peta, supaya
 * daftar layer aktif selalu bisa di-scroll alih-alih meluber keluar peta.
 */
function positionLayerToolsPanel() {
    const panel = document.getElementById("sidebar-layer-tools");
    const header = document.getElementById("layer-tools-header");
    const content = document.getElementById("layer-tools-content");
    const leftControls = document.querySelector("#map .leaflet-top.leaflet-left");
    const mapEl = document.getElementById("map");
    if (!panel || !leftControls || !mapEl) return;

    const mapRect = mapEl.getBoundingClientRect();
    const controlsRect = leftControls.getBoundingClientRect();

    // Kontrol kiri ada di tengah vertikal: panel dibuka di samping kanannya, mulai di
    // bawah baris tombol atas (Beranda/pencarian, ±70px) supaya tidak tertutup.
    const topRowBottom = 70;
    const top = Math.max(topRowBottom, controlsRect.top - mapRect.top);
    panel.style.top = `${top}px`;
    panel.style.left = `${controlsRect.right - mapRect.left + 12}px`;

    if (content) {
        const headerHeight = header?.getBoundingClientRect().height || 40;
        const bottomMargin = 16;
        const available = mapRect.height - top - headerHeight - bottomMargin;
        content.style.maxHeight = `${Math.max(120, available)}px`;
    }
}

/**
 * Membuat dan mengikat konten popup pada setiap fitur peta.
 */
function bindPopupContent(feature, layer, urlPath) {
    const props = feature.properties;
    let content = `
        <div class="p-4 max-w-xs bg-white rounded-lg shadow-lg border border-gray-200">
            <h5 class="text-md font-semibold text-blue-600 mb-3 border-b border-gray-200 pb-2">
                ${props.kategori || "Feature"}
            </h5>`;

    if (props.gambar) {
        content += `
            <div class="mb-3">
                <img src="${props.gambar}" alt="Gambar ${props.KEGIATAN}"
                    class="w-full h-32 object-cover rounded-md border border-gray-300 shadow-sm">
            </div>`;
    }

    content += `
        <div class="space-y-2 mb-4">
            <div class="max-h-40 overflow-y-auto">
                <table class="w-full text-[9px]" >`;

    const allowedKeys = ["KEGIATAN", "TAHUN", "KABUPATEN", "URUSAN", "SUMBER_DATA", "OPD_PENGELOLA", "TANGGAL_DATA"];
    Object.entries(props).forEach(([key, value]) => {
        if (allowedKeys.includes(key.toUpperCase()) && value) {
            const label = key
                .replace(/_/g, " ")
                .replace(/\b\w/g, (l) => l.toUpperCase());
            content += `
                <tr class="border-b border-gray-100">
                    <td class="text-[9px] font-medium text-gray-700 py-1 pr-2 align-top">${label}</td>
                    <td class="text-[9px] text-gray-600 py-1">${value}</td>
                </tr>`;
        }
    });

    content += `</table></div></div>`;

    const geom = feature.geometry;
    let center = null;

    if (geom) {
        const type = geom.type;
        content += `
            <div class="border-t border-gray-200 pt-3 mb-3">
                <table class="w-full text-[9px]">
                    <tr class="border-b border-gray-100">
                        <td class="text-[9px] font-medium text-gray-700 py-1 pr-2">Geometry</td>
                        <td class="text-[9px] text-gray-600 py-1">${type}</td>
                    </tr>`;

        if (type === "LineString" && Array.isArray(geom.coordinates)) {
            let length = 0;
            for (let i = 1; i < geom.coordinates.length; i++) {
                const [lon1, lat1] = geom.coordinates[i - 1];
                const [lon2, lat2] = geom.coordinates[i];
                const R = 6371;
                const rad = Math.PI / 180;
                const dLat = (lat2 - lat1) * rad;
                const dLon = (lon2 - lon1) * rad;
                const a =
                    Math.sin(dLat / 2) ** 2 +
                    Math.cos(lat1 * rad) *
                        Math.cos(lat2 * rad) *
                        Math.sin(dLon / 2) ** 2;
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                length += R * c;
            }
            content += `
                <tr class="border-b border-gray-100">
                    <td class="text-[9px] font-medium text-gray-700 py-1 pr-2">Panjang</td>
                    <td class="text-[9px] text-gray-600 py-1">${length.toFixed(
                        2
                    )} km</td>
                </tr>`;
        }

        // Hitung center
        if (type === "Point") {
            center = geom.coordinates;
        } else if (type === "LineString") {
            const mid = Math.floor(geom.coordinates.length / 2);
            center = geom.coordinates[mid];
        } else if (type === "Polygon") {
            const poly = geom.coordinates[0];
            const mid = Math.floor(poly.length / 2);
            center = poly[mid];
        } else if (type === "MultiPolygon") {
            const poly = geom.coordinates[0][0];
            const mid = Math.floor(poly.length / 2);
            center = poly[mid];
        }

        if (center && center.length >= 2) {
            content += `
                <tr>
                    <td class="text-[9px] ont-medium text-gray-700 py-1 pr-2">Koordinat</td>
                    <td class="text-[9px] text-gray-600 py-1 font-mono text-xs">${center[1].toFixed(
                        5
                    )}, ${center[0].toFixed(5)}</td>
                </tr>`;
        }
        content += `</table></div>`;
    }

    const lat = center?.[1] || 0;
    const lng = center?.[0] || 0;

    content += `
        <div class="flex gap-2 pt-2">
            <button class="zoomToBtn flex-1 bg-blue-500 hover:bg-blue-600 text-white text-sm px-2 py-1 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                data-lat="${lat}" data-lng="${lng}">
                <i class="bi bi-zoom-in mr-1"></i>
                Zoom To
            </button>
            <button type="button" class="featureDetailBtn flex-1 bg-green-500 hover:bg-green-600 text-white text-sm px-2 py-1 rounded-md text-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                <i class="bi bi-eye mr-1"></i>
                Detail
            </button>
        </div>
    </div>`;

    // Set popup options untuk Tailwind styling
    const popupOptions = {
        maxWidth: 320,
        minWidth: 280,
        className: "tailwind-popup",
        closeButton: true,
        autoClose: false,
        closeOnEscapeKey: true,
    };

    layer.bindPopup(content, popupOptions);

    layer.on("popupopen", function () {
        const popupNode = layer.getPopup().getElement();
        const zoomButton = popupNode.querySelector(".zoomToBtn");

        popupNode.querySelector(".featureDetailBtn")?.addEventListener("click", () => {
            layer.closePopup();
            window.MarimoiFeatureDetail?.open(feature, layer);
        });

        if (zoomButton) {
            zoomButton.addEventListener("click", function () {
                const geom = feature.geometry;
                if (!geom) return;

                const mapInstance = layer._map;
                if (geom.type === "Point") {
                    const lat = parseFloat(this.getAttribute("data-lat"));
                    const lng = parseFloat(this.getAttribute("data-lng"));
                    mapInstance.setView([lat, lng], 15);
                } else if (geom.type === "LineString") {
                    const latlngs = geom.coordinates.map(([lng, lat]) => [
                        lat,
                        lng,
                    ]);
                    mapInstance.fitBounds(latlngs);
                } else if (geom.type === "Polygon") {
                    const latlngs = geom.coordinates[0].map(([lng, lat]) => [
                        lat,
                        lng,
                    ]);
                    mapInstance.fitBounds(latlngs);
                } else if (geom.type === "MultiPolygon") {
                    let allLatLngs = [];
                    geom.coordinates.forEach((poly) => {
                        poly[0].forEach(([lng, lat]) => {
                            allLatLngs.push([lat, lng]);
                        });
                    });
                    if (allLatLngs.length > 0) {
                        mapInstance.fitBounds(allLatLngs);
                    }
                }
            });
        }
    });
}

/**
 * Mengganti basemap yang aktif sesuai pilihan user.
 */
function changeBaseMap(baseMapId) {
    if (currentBaseMap) {
        map.removeLayer(currentBaseMap);
    }

    const config = mapConfig.baseMapsList.find((bm) => bm.id === baseMapId);
    if (config) {
        currentBaseMap = L.tileLayer(config.url, {
            subdomains: config.subdomains || [],
            minZoom: config.minZoom || 4,
            maxZoom: config.maxZoom || 18,
        });
        currentBaseMap.addTo(map);
    }
}

/**
 * Menentukan tipe data berdasarkan path URL.
 */
function getDataType(urlPath) {
    const defaultResult = { type: "tematik", sub_type: null, year: null };

    switch (urlPath) {
        case "/proyek-strategis-daerah":
            return { type: "proyek_strategis", sub_type: "psd", year: null };
        case "/proyek-strategis-nasional":
            return { type: "proyek_strategis", sub_type: "psn", year: null };
        case "/peta-tematik":
            return { type: "tematik", sub_type: null, year: null };
        case "/usulan-musrenbang":
            return { type: "usulan_musrenbang", sub_type: null, year: null };
        case "/pokir-dprd":
            return { type: "pokir_dprd", sub_type: null, year: null };
        default:
            return defaultResult;
    }
}

/**
 * Buat layer group untuk satu kategori "leaf". Kategori marker (is_marker = true)
 * memakai Leaflet.markercluster supaya titik yang berdekatan/menumpuk otomatis
 * dikelompokkan jadi satu bubble dan tidak berantakan di peta; kategori garis/poligon
 * tetap pakai layer group biasa karena clustering hanya relevan untuk point marker.
 */
function createCategoryLayerGroup(catObj) {
    if (catObj?.is_marker && typeof L.markerClusterGroup === "function") {
        return L.markerClusterGroup({
            maxClusterRadius: 60,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            disableClusteringAtZoom: 18,
        });
    }

    return L.layerGroup();
}

/**
 * Load only categories metadata without spatial data - Modified for 3-level hierarchy
 */
async function loadCategoriesMetadata() {
    try {
        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);
        const dataType = tipeLayer.type;
        const subType = tipeLayer.sub_type || null;
        const year = tipeLayer.year || null;

        let queryString = "?metadata_only=true";
        if (dataType) queryString += `&type=${dataType}`;
        if (subType) queryString += `&sub_type=${subType}`;
        if (year) queryString += `&year=${year}`;

        const metaKey = `metadata_${dataType || "default"}_${subType || "none"}_${year || "all"}`;

        const fetchMetadata = async () => {
            const response = await fetch(`/geojson${queryString}`);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response.json();
        };

        // Cache-first: daftar kategori yang sudah pernah dimuat langsung dipakai tanpa loading.
        // Di latar belakang cache diperbarui untuk kunjungan berikutnya.
        let data = mapDataStore ? await mapDataStore.getCachedMetadata(metaKey) : null;
        const fromCache = Boolean(data);
        let loadingToast = null;

        if (fromCache) {
            fetchMetadata()
                .then((fresh) => mapDataStore.setCachedMetadata(metaKey, fresh))
                .catch(() => {});
        } else {
            loadingToast = showAlert("Memuat daftar kategori...", "info", true);
            data = await fetchMetadata();
            if (mapDataStore) {
                await mapDataStore.setCachedMetadata(metaKey, data);
            }
        }

        // Build kategoriWarnaMap dan iconMap
        kategoriWarnaMap = {};
        iconMap = {};

        if (Array.isArray(data.all_categories)) {
            data.all_categories.forEach((cat) => {
                if (!cat.nama || !cat.warna) return;
                kategoriWarnaMap[cat.nama] = cat.warna;
                if (cat.is_marker === true && cat.icon) {
                    iconMap[cat.nama] = cat.icon;
                }
            });
        }

        // Initialize empty layer structure - Now with 3 levels
        layerGroups = {};

        if (data.all_categories?.length) {
            // Level 1: Root categories (no parent)
            const rootCategories = data.all_categories.filter(cat => !cat.parent_id);
            
            // Level 2: Second-level categories (parent is a root category)
            const secondLevelCategories = data.all_categories.filter(cat => 
                cat.parent_id && rootCategories.some(root => root.id === cat.parent_id)
            );
            
            // Level 3: Third-level categories (parent is a second-level category)
            const thirdLevelCategories = data.all_categories.filter(cat => 
                cat.parent_id && secondLevelCategories.some(second => second.id === cat.parent_id)
            );

            rootCategories.forEach((root) => {
                layerGroups[root.nama] = {};
                
                // Find second level children for this root
                const childrenL2 = secondLevelCategories.filter(child => child.parent_id === root.id);
                
                if (childrenL2.length > 0) {
                    // For each second level category
                    childrenL2.forEach((childL2) => {
                        layerGroups[root.nama][childL2.nama] = {};
                        
                        // Find third level children for this second level
                        const childrenL3 = thirdLevelCategories.filter(child => child.parent_id === childL2.id);
                        
                        if (childrenL3.length > 0) {
                            // Add third level categories
                            childrenL3.forEach((childL3) => {
                                layerGroups[root.nama][childL2.nama][childL3.nama] = createCategoryLayerGroup(childL3);
                            });
                        } else {
                            // No third level, use second level as leaf
                            layerGroups[root.nama][childL2.nama][childL2.nama] = createCategoryLayerGroup(childL2);
                        }
                    });
                } else {
                    // No second level, use root as both second and third
                    layerGroups[root.nama][root.nama] = {};
                    layerGroups[root.nama][root.nama][root.nama] = createCategoryLayerGroup(root);
                }
            });
        } else if (data.root_categories) {
            // Alternative structure if using root_categories format
            data.root_categories.forEach((root) => {
                const rootName = root.nama;
                layerGroups[rootName] = {};
                
                if (Array.isArray(root.children) && root.children.length > 0) {
                    root.children.forEach((childL2) => {
                        layerGroups[rootName][childL2.nama] = {};
                        
                        if (Array.isArray(childL2.children) && childL2.children.length > 0) {
                            childL2.children.forEach((childL3) => {
                                layerGroups[rootName][childL2.nama][childL3.nama] = createCategoryLayerGroup(childL3);
                            });
                        } else {
                            layerGroups[rootName][childL2.nama][childL2.nama] = createCategoryLayerGroup(childL2);
                        }
                    });
                } else {
                    layerGroups[rootName][rootName] = {};
                    layerGroups[rootName][rootName][rootName] = createCategoryLayerGroup(root);
                }
            });
        }

        window.MARIMOI_CATEGORY_METADATA = data;
        updateLayerList();
        generateLegend();
        updateLayerToolsPanel();

        // Tutup loading toast manual
        if (loadingToast) hideToast(loadingToast);

        // Tampilkan pesan sukses (dilewati bila daftar kategori berasal dari cache)
        if (!fromCache) {
            showAlert(
                "Kategori berhasil dimuat. Pilih layer untuk memuat data.",
                "success"
            );
        }
    } catch (error) {
        console.error("Error loading categories metadata:", error);
        showAlert(`Gagal memuat kategori: ${error.message}`, "danger");

        const layerListContainer = document.getElementById("layer-list");
        if (layerListContainer) {
            layerListContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center text-center h-[120px] text-gray-700">
                    <i class="bi bi-x-circle text-red" style="font-size:2rem;"></i>
                    <span class="mt-2 text-sm">Terjadi kesalahan saat memuat kategori.</span>
                    <button class="mt-3 text-sm py-1 px-3 rounded bg-gray-500 text-white hover:bg-gray-600 transition" onclick="loadCategoriesMetadata()">Coba Lagi</button>
                </div>`;
        }
    }
}

/**
 * Buat ikon marker untuk kategori bertipe marker (dipakai jalur jaringan dan jalur cache).
 */
function buildMarkerOptions(geoJsonData, categoryName) {
    // Gaya marker dari metadata katalog (respons /geojson per potongan tidak lagi membawa daftar kategori).
    const catObj = (window.MARIMOI_CATEGORY_METADATA?.all_categories || geoJsonData?.all_categories || [])
        .find((c) => c.nama === categoryName);
    if (!(catObj?.is_marker && catObj.icon)) {
        return null;
    }

    return L.ExtraMarkers.icon({
        icon: catObj.icon,
        prefix: "fa",
        svg: true,
        markerColor: catObj.warna || "blue",
        iconColor: "white",
        shape: "circle",
        html: `<i class='fa ${catObj.icon}' style='color:white; background: blue;'></i>`,
    });
}

/**
 * Tambahkan satu feature ke layer. Mengembalikan true bila feature valid dan ditambahkan.
 */
function addFeatureToLayer(feature, targetLayer, categoryName, markerOptions, urlPath) {
    if (!feature || !feature.geometry) {
        return false;
    }

    L.geoJSON(feature, {
        pointToLayer: (f, latlng) =>
            markerOptions ? L.marker(latlng, { icon: markerOptions }) : L.marker(latlng),
        style: getStyleForCategory(categoryName),
        onEachFeature: (f, l) => {
            try {
                bindPopupContent(f, l, urlPath);
            } catch (popupError) {
                // Silently handle popup binding errors
            }
        },
    }).addTo(targetLayer);

    return true;
}

/**
 * Baca seluruh potongan (chunk) data kategori dari cache IndexedDB.
 * Mengembalikan array chunk bila SEMUA potongan ada di cache, atau null bila ada yang kurang
 * (mis. belum pernah dimuat) sehingga pemanggil harus memakai jalur jaringan.
 */
async function readAllCachedChunks(baseParams, maxRecords, chunkSize) {
    if (!mapDataStore) return null;

    const chunks = [];
    let offset = 0;
    let totalLoaded = 0;

    while (totalLoaded < maxRecords) {
        const key = mapDataStore.generateCacheKey({
            ...baseParams,
            limit: Math.min(chunkSize, maxRecords - totalLoaded),
            offset,
        });
        const chunk = await mapDataStore.getCachedData(key);

        if (!chunk) return null;
        chunks.push(chunk);

        const valid = (chunk.features || []).filter((f) => f && f.geometry).length;
        totalLoaded += valid;

        const hasMore = chunk.meta?.has_more === true && totalLoaded < maxRecords && valid > 0;
        if (!hasMore) break;

        offset += chunkSize;
    }

    return chunks.length ? chunks : null;
}

/**
 * Render chunk dari cache ke layer secara bertahap (per irisan kecil) agar UI tetap responsif
 * tanpa layar loading. Mengembalikan jumlah feature yang ditambahkan.
 */
async function renderCachedChunks(chunks, targetLayer, categoryName, urlPath) {
    const markerOptions = buildMarkerOptions(chunks[0], categoryName);
    const slice = 200;
    let added = 0;

    for (const chunk of chunks) {
        const features = chunk.features || [];

        for (let i = 0; i < features.length; i += slice) {
            features.slice(i, i + slice).forEach((feature) => {
                try {
                    if (addFeatureToLayer(feature, targetLayer, categoryName, markerOptions, urlPath)) {
                        added++;
                    }
                } catch (featureError) {
                    // Silently handle individual feature errors
                }
            });

            if (i + slice < features.length) {
                await new Promise((resolve) => setTimeout(resolve, 0));
            }
        }
    }

    return added;
}

/**
 * Enhanced loadCategoryData with cache integration
 */
async function loadCategoryData(categoryName, parentName = null, grandparentName = null) {
    // Skip if already loaded or currently loading
    if (isLoadingData || loadedCategories.has(categoryName)) {
        return;
    }

    let loadingToast = null;

    try {
        isLoadingData = true;

        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);
        const dataType = tipeLayer.type;
        const subType = tipeLayer.sub_type || null;
        const year = tipeLayer.year || null;

        // Find target layer for this category
        let targetLayer = null;

        if (grandparentName && parentName) {
            if (layerGroups[grandparentName]?.[parentName]?.[categoryName]) {
                targetLayer = layerGroups[grandparentName][parentName][categoryName];
            }
        } else if (parentName) {
            if (layerGroups[parentName]?.[categoryName]) {
                if (layerGroups[parentName][categoryName][categoryName]) {
                    targetLayer = layerGroups[parentName][categoryName][categoryName];
                }
            }
        } else {
            if (layerGroups[categoryName]?.[categoryName]?.[categoryName]) {
                targetLayer = layerGroups[categoryName][categoryName][categoryName];
            }
        }

        if (!targetLayer) {
            throw new Error(`Layer group for ${categoryName} not found`);
        }

        const maxRecords = 3000;
        const chunkSize = 500;

        // Jalur cepat: bila seluruh data kategori sudah tersimpan di cache, tampilkan langsung
        // tanpa layar loading, toast, maupun jeda buatan.
        const cachedChunks = await readAllCachedChunks(
            { type: dataType, sub_type: subType, year: year, category: categoryName },
            maxRecords,
            chunkSize
        );

        if (cachedChunks) {
            targetLayer.clearLayers();
            await renderCachedChunks(cachedChunks, targetLayer, categoryName, urlPath);
            loadedCategories.add(categoryName);
            refreshFilterPanel();
            return;
        }

        // Belum (sepenuhnya) ada di cache: tampilkan layar loading seperti biasa.
        showLoadingOverlay(categoryName);
        loadingToast = showAlert(
            `Memuat data untuk ${categoryName}...`,
            "info",
            true
        );

        targetLayer.clearLayers();

        let offset = 0;
        let totalLoaded = 0;
        let hasMore = true;
        let estimatedTotal = maxRecords;

        // Load data in chunks with cache check
        while (hasMore && totalLoaded < maxRecords) {
            try {
                const cacheParams = {
                    type: dataType,
                    sub_type: subType,
                    year: year,
                    category: categoryName,
                    limit: Math.min(chunkSize, maxRecords - totalLoaded),
                    offset: offset
                };

                const cacheKey = mapDataStore?.generateCacheKey(cacheParams);
                
                // Try cache first
                let geoJsonData = null;
                if (mapDataStore && cacheKey) {
                    geoJsonData = await mapDataStore.getCachedData(cacheKey);
                    
                    if (geoJsonData) {
                        // Update progress for cache hit
                        updateLoadingProgress(
                            totalLoaded,
                            estimatedTotal,
                            `Memuat Data - Layer ${Math.floor(offset / chunkSize) + 1}...`
                        );
                    }
                }

                // If no cache hit, fetch from network
                if (!geoJsonData) {
                    let queryString = "?";
                    if (dataType) queryString += `type=${encodeURIComponent(dataType)}`;
                    if (subType) queryString += `&sub_type=${encodeURIComponent(subType)}`;
                    if (year) queryString += `&year=${encodeURIComponent(year)}`;
                    queryString += `&kategori[]=${encodeURIComponent(categoryName)}`;

                    const remainingRecords = maxRecords - totalLoaded;
                    const currentChunkSize = Math.min(chunkSize, remainingRecords);
                    queryString += `&limit=${currentChunkSize}&offset=${offset}`;

                    updateLoadingProgress(
                        totalLoaded,
                        estimatedTotal,
                        `Memuat Data - Layer ${Math.floor(offset / chunkSize) + 1}...`
                    );

                    const response = await fetch(`/geojson${queryString}`);

                    if (!response.ok) {
                        let errorDetails = `HTTP ${response.status}: ${response.statusText}`;
                        try {
                            const errorData = await response.json();
                            if (errorData.message) {
                                errorDetails += ` - ${errorData.message}`;
                            }
                        } catch (e) {
                            // Handle error parsing
                        }
                        throw new Error(errorDetails);
                    }

                    geoJsonData = await response.json();

                    // Cache the result
                    if (mapDataStore && cacheKey && Array.isArray(geoJsonData?.features)) {
                        await mapDataStore.setCachedData(cacheKey, geoJsonData, categoryName);
                    }
                }

                // Check if we got any features
                if (!geoJsonData?.features?.length) {
                    break;
                }

                // Update estimate if we have metadata
                if (geoJsonData.meta?.total_features && offset === 0) {
                    estimatedTotal = Math.min(geoJsonData.meta.total_features, maxRecords);
                }

                // Determine marker options (only need to do this once)
                let markerOptions = null;
                if (offset === 0) {
                    markerOptions = buildMarkerOptions(geoJsonData, categoryName);
                }

                // Add features to layer with error handling
                let featuresAdded = 0;
                geoJsonData.features.forEach((feature, index) => {
                    try {
                        if (!addFeatureToLayer(feature, targetLayer, categoryName, markerOptions, urlPath)) {
                            return;
                        }

                        featuresAdded++;

                        if (index % 50 === 0) {
                            updateLoadingProgress(
                                totalLoaded + featuresAdded,
                                estimatedTotal,
                                `Memproses fitur ${totalLoaded + featuresAdded}...`
                            );
                        }
                    } catch (featureError) {
                        // Silently handle individual feature errors
                    }
                });

                totalLoaded += featuresAdded;

                updateLoadingProgress(
                    totalLoaded,
                    estimatedTotal,
                    totalLoaded >= maxRecords
                        ? `${totalLoaded} fitur dimuat (maksimum tercapai)`
                        : `${totalLoaded} fitur dimuat...`
                );

                const serverHasMore = geoJsonData.meta?.has_more === true;
                hasMore = serverHasMore && totalLoaded < maxRecords && featuresAdded > 0;
                offset += chunkSize;

                if (hasMore) {
                    await new Promise((resolve) => setTimeout(resolve, 100));
                }
            } catch (chunkError) {
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    console.error(`Error loading layer at offset ${offset}:`, chunkError);
                }

                if (offset === 0) {
                    throw chunkError;
                }

                updateLoadingProgress(
                    totalLoaded,
                    estimatedTotal,
                    `Error pada Layer ${Math.floor(offset / chunkSize)}: ${chunkError.message}`
                );
                await new Promise((resolve) => setTimeout(resolve, 1000));
                break;
            }
        }

        loadedCategories.add(categoryName);
        updateLoadingProgress(totalLoaded, totalLoaded, "Selesai!");
        await new Promise((resolve) => setTimeout(resolve, 500));

        hideLoadingOverlay();
        refreshFilterPanel();

        if (loadingToast) {
            hideToast(loadingToast);
        }

        let finalMessage;
        if (totalLoaded >= maxRecords) {
            finalMessage = `Data ${categoryName} berhasil dimuat (${totalLoaded} fitur - maksimum tercapai)`;
        } else {
            finalMessage = `Data ${categoryName} berhasil dimuat (${totalLoaded} fitur)`;
        }

        showAlert(finalMessage, "success");

    } catch (error) {
        console.error(`Error loading data for category ${categoryName}:`, error);

        hideLoadingOverlay();

        if (loadingToast) {
            hideToast(loadingToast);
        }

        let errorMessage = `Gagal memuat data ${categoryName}`;

        if (error.message.includes("500")) {
            errorMessage += ": Server mengalami masalah internal. Coba lagi nanti.";
        } else if (error.message.includes("404")) {
            errorMessage += ": Data tidak ditemukan.";
        } else if (error.message.includes("timeout")) {
            errorMessage += ": Koneksi timeout. Periksa koneksi internet Anda.";
        } else {
            errorMessage += `: ${error.message}`;
        }

        showAlert(errorMessage, "danger");
        loadedCategories.delete(categoryName);
    } finally {
        isLoadingData = false;
    }
}


/**
 * Daftar layer kini berupa Katalog Data (modal) + sidebar Layer Aktif, lihat map-catalog.js.
 */
function updateLayerList() {
    window.MarimoiCatalog?.rebuild();
}




function generatePreviewUrl(basemap) {
    switch (basemap.id) {
        case "osm":
            return `frontend/img/map-preview/${basemap.id}-min.png`;
        case "google-roadmap":
            return `/frontend/img/map-preview/${basemap.id}-min.png`;
        case "google-hybrid":
            return `/frontend/img/map-preview/${basemap.id}-min.png`;
        case "google-terrain":
            return `/frontend/img/map-preview/${basemap.id}-min.png`;
        case "esri-world-imagery":
            return `/frontend/img/map-preview/${basemap.id}-min.png`;
        case "esri-dark-gray":
            return `/frontend/img/map-preview/${basemap.id}-min.png`;
        case "esri-streets":
            return `/frontend/img/map-preview/${basemap.id}-min.png`;
        case "esri-topographic":
            return `/frontend/img/map-preview/${basemap.id}-min.png`;
        case "esri-oceans":
            return `/frontend/img/map-preview/${basemap.id}-min.png`;
        case "esri-light-gray":
            return `/frontend/img/map-preview/${basemap.id}-min.png`;

        default:
            return "/frontend/img/placeholder.png";
    }
}

/**
 * Inisialisasi dan setup event handler untuk UI (slider transparansi, basemap, sidebar, dll) - Tailwind version
 */
function setupUI() {
    const basemapList = document.getElementById("basemap-list");
    if (basemapList) {
        basemapList.innerHTML = "";

        const gridContainer = document.createElement("div");
        gridContainer.className = "grid grid-cols-2 gap-3";

        mapConfig.baseMapsList.forEach((bm, i) => {
            const itemContainer = document.createElement("div");
            itemContainer.className = "col-span-1";

            const basemapItem = document.createElement("div");
            basemapItem.className =
                "basemap-item overflow-hidden cursor-pointer position-relative";
            basemapItem.style.cssText = `
            transition: all 0.2s ease;
            cursor: pointer;
        `;

            // Preview image
            const previewImg = document.createElement("img");
            previewImg.src = generatePreviewUrl(bm);
            previewImg.alt = bm.label;
            previewImg.className = "w-100 border-2 border-white shadow-lg";
            previewImg.style.cssText = `
            width: 90%;
            height: 70px;
            object-fit: cover;
            transition: all 0.2s ease;
            box-shadow: 6px rgba(0,0,0,1);
        `;

            // Error handling
            previewImg.onerror = function () {
                this.style.display = "none";
                const placeholder = document.createElement("div");
                placeholder.className =
                    "flex items-center justify-center bg-white";
                placeholder.style.cssText = `
                width: 100%;
                height: 80px;
            `;
                placeholder.innerHTML = `
                <div class="text-center">
                    <i class="bi bi-image text-muted" style="font-size: 1.1rem;"></i>
                    <div class="small text-muted text-xs">Preview tidak tersedia</div>
                </div>
            `;
                this.parentNode.insertBefore(placeholder, this);
            };

            // Label
            const label = document.createElement("div");
            label.className = "p-2";
            label.style.cssText = `
            font-size: 0.7rem;
            font-weight: 500;
            text-align: center;
            line-height: 1.2;
            transition: color 0.2s ease;
        `;
            label.textContent = bm.label;

            // Radio input (hidden)
            const radioInput = document.createElement("input");
            radioInput.type = "radio";
            radioInput.name = "basemap-radio";
            radioInput.id = `bm-${bm.id}`;
            radioInput.value = bm.id;
            radioInput.className = "hidden";
            radioInput.style.cssText = "display:none;";
            if (i === 0) radioInput.checked = true;

            // Click handler
            basemapItem.addEventListener("click", function () {
                document
                    .querySelectorAll('input[name="basemap-radio"]')
                    .forEach((input) => {
                        input.checked = false;
                        const item = input.closest(".basemap-item");
                        if (item) {
                            // reset style
                            const img = item.querySelector("img");
                            const lbl = item.querySelector("div.p-2");
                            if (img) img.style.boxShadow = "6px rgba(0,0,0,1)";
                            if (lbl) lbl.style.color = "inherit";
                        }
                    });

                // Aktifkan yang dipilih
                radioInput.checked = true;
                previewImg.style.boxShadow = "0 0 10px rgba(0, 123, 255, 0.6)";
                label.style.color = "#0d6efd";

                changeBaseMap(bm.id);
            });

            // Set initial state
            if (radioInput.checked) {
                previewImg.style.boxShadow = "0 0 10px rgba(0, 123, 255, 0.6)";
                label.style.color = "#0d6efd";
            }

            basemapItem.appendChild(previewImg);
            basemapItem.appendChild(label);
            basemapItem.appendChild(radioInput);

            itemContainer.appendChild(basemapItem);
            gridContainer.appendChild(itemContainer);
        });

        basemapList.appendChild(gridContainer);
    }
}

/**
 * Terapkan state dari link share (kombinasi beberapa layer + viewport) ke peta.
 * Dipicu saat halaman dibuka lewat /peta-tematik/share/{slug} dan server
 * sudah menaruh state-nya di window.MARIMOI_SHARED_STATE (lihat peta.blade.php).
 */
async function applySharedMapState() {
    const state = window.MARIMOI_SHARED_STATE;

    if (!state || !Array.isArray(state.layers) || state.layers.length === 0) {
        return;
    }

    const { missing } = await window.MarimoiCatalog.activateByNames(state.layers);
    missing.forEach((categoryName) => {
        showAlert(`Layer "${categoryName}" dari link share tidak ditemukan.`, "warning");
    });

    const viewport = state.viewport;
    if (viewport && typeof viewport.lat === "number" && typeof viewport.lng === "number") {
        map.setView([viewport.lat, viewport.lng], viewport.zoom || mapConfig.zoom);
    }

    if (state.filters) {
        const kabupatenEl = document.getElementById("filter-kabupaten");
        const tahunEl = document.getElementById("filter-tahun");
        const opdEl = document.getElementById("filter-opd");
        if (state.filters.kabupaten && kabupatenEl) kabupatenEl.value = state.filters.kabupaten;
        if (state.filters.tahun && tahunEl) tahunEl.value = String(state.filters.tahun);
        if (state.filters.opd_pengelola && opdEl) opdEl.value = state.filters.opd_pengelola;
        // Opsi select bisa belum termuat dari server; nilai dari link share tetap dipasang.
        window.MarimoiCatalog?.syncAppliedFilters({
            kabupaten: state.filters.kabupaten || "",
            tahun: state.filters.tahun ? String(state.filters.tahun) : "",
            opd_pengelola: state.filters.opd_pengelola || "",
        });
        // Filter di share link juga harus menampilkan layer yang cocok di luar
        // state.layers yang eksplisit tercentang — bukan cuma menyaring yang sudah dimuat.
        await applyStructuredFilters();
    }
}

/**
 * Get selected category from session/server
 */
function getSelectedCategoryFromSession() {
    // Check if there's a global variable set by server
    if (typeof window.MARIMOI_SELECTED_CATEGORY !== "undefined") {
        return window.MARIMOI_SELECTED_CATEGORY;
    }
    return null;
}

/**
 * Aktifkan kategori yang dipilih dari halaman lain (disimpan di session).
 */
async function autoClickCategoryFromSession() {
    const selectedCategory = getSelectedCategoryFromSession();

    if (!selectedCategory || !window.MarimoiCatalog) {
        return;
    }

    if (window.MarimoiCatalog.findEntriesByName(selectedCategory).length === 0) {
        showAlert(`Kategori "${selectedCategory}" tidak ditemukan di daftar layer`, "warning");
        return;
    }

    try {
        showAlert(`Memuat peta ${selectedCategory}...`, "info");
        await window.MarimoiCatalog.activateByNames([selectedCategory]);
    } catch (error) {
        showAlert(`Gagal memuat kategori "${selectedCategory}": ${error.message}`, "danger");
    }
}



// Update existing DOMContentLoaded event listener
document.addEventListener("DOMContentLoaded", async () => {
    // Initialize MapDataStore first
    if (window.MapDataStore) {
        mapDataStore = new window.MapDataStore();
        
        // Add debug tools in development
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            window.MAP_DEBUG = {
                cache: mapDataStore,
                getCacheStats: () => mapDataStore.getCacheStats(),
                clearCache: () => mapDataStore.clearAllCache(),
                layerGroups: () => layerGroups,
                loadedCategories: () => Array.from(loadedCategories)
            };
            console.log('Debug tools available at window.MAP_DEBUG');
        }
    }

    // Cache dua skenario: TTL 24 jam (di MapDataStore) dan pembuangan saat data/kategori berubah.
    // Versi data dicek sebelum daftar kategori dibaca dari cache.
    if (mapDataStore && window.MARIMOI_MAP_VERSION_URL) {
        await mapDataStore.syncVersion(window.MARIMOI_MAP_VERSION_URL);
    }

    // Init map
    changeBaseMap("osm");
    setupUI();

    // Beri tahu pengguna jika mereka datang dari link share yang tidak valid/kedaluwarsa
    if (window.MARIMOI_SHARE_ERROR) {
        showAlert(window.MARIMOI_SHARE_ERROR, "warning");
    }

    // Show loading spinner for layer list
    const layerListContainer = document.getElementById("layer-list");
    if (layerListContainer) {
        layerListContainer.innerHTML = `
            <div id="layer-loading" class="flex items-center justify-center h-[120px]">
                <div class="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="ml-2 text-sm">Memuat daftar kategori...</span>
            </div>`;
    }

    try {
        // Isi dropdown filter dari database (paralel, tidak perlu menunggu pohon layer
        // selesai dibangun — elemen <select>-nya statis di Blade, tanpa layerGroups).
        loadFilterOptionsFromServer();

        // Load categories metadata - ini akan build layerGroups dan UI
        await loadCategoriesMetadata();

        // Remove spinner
        document.getElementById("layer-loading")?.remove();

        // Tunggu sebentar agar UI benar-benar selesai di-render
        setTimeout(async () => {
            // Auto-click checkbox untuk kategori yang dipilih dari session
            await autoClickCategoryFromSession();

            // Terapkan state dari link share (multi-layer + viewport), jika ada
            await applySharedMapState();
        }, 1000); // 1 detik delay untuk memastikan UI siap

    } catch (error) {
        console.error("Error during map initialization:", error);
        showAlert("Terjadi kesalahan saat memuat aplikasi peta", "danger");
    }

    // Sidebar elements
    const sidebarElements = {
        layer: document.getElementById("sidebar-layer"),
        basemap: document.getElementById("sidebar-basemap"),
        legend: document.getElementById("sidebar-legend"),
        help: document.getElementById("guideModal"),
    };

    // Toggle buttons
    const toggleButtons = {
        layer: document.getElementById("btn-toggle-sidebar-layer"),
        basemap: document.getElementById("btn-toggle-sidebar-basemap"),
        legend: document.getElementById("btn-toggle-sidebar-legend"),
        help: document.getElementById("btn-toggle-sidebar-help"),
    };

    const guideModal = document.getElementById("guideModal");
    const guideSteps = document.querySelectorAll(".guide-step");
    const btnPrev = document.getElementById("btnPrev");
    const btnNext = document.getElementById("btnNext");
    const btnSkip = document.getElementById("btnSkip");
    const btnToggleHelp = toggleButtons.help;

    const controlButtons = [
        toggleButtons.help,
        toggleButtons.legend,
        toggleButtons.basemap,
        toggleButtons.layer,
        document.getElementById("btn-fullscreen"),
        document.getElementById("btn-default-zoom"),
    ];

    function closeAllSidebars() {
        Object.values(sidebarElements).forEach((el) => {
            if (el && el !== guideModal) el.classList.add("hidden");
        });
    }

    let currentStep = 1;
    const totalSteps = guideSteps.length;

    function clearHighlights() {
        controlButtons.forEach((btn) => {
            btn?.classList.remove("ring-2", "ring-white", "shadow-lg", "z-50");
        });
    }

    function showStep(step) {
        guideSteps.forEach((div) => {
            div.classList.toggle("hidden", parseInt(div.dataset.step) !== step);
        });

        btnPrev.disabled = step === 1;
        btnNext.textContent = step === totalSteps ? "Finish" : "Next";

        clearHighlights();
        if (controlButtons[step - 3]) {
            controlButtons[step - 3]?.classList.add(
                "ring-2",
                "ring-white",
                "shadow-lg",
                "z-50"
            );
        }
    }

    function showGuideModal() {
        closeAllSidebars();
        currentStep = 1;
        showStep(currentStep);
        guideModal.classList.remove("hidden");
        guideModal.classList.add("flex");
    }

    function hideGuideModal() {
        guideModal.classList.add("hidden");
        guideModal.classList.remove("flex");
        clearHighlights();
    }

    // Modal help toggle
    btnToggleHelp?.addEventListener("click", () => {
        const isHidden = guideModal.classList.contains("hidden");
        isHidden ? showGuideModal() : hideGuideModal();
    });

    // Modal controls
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
            hideGuideModal();
        }
    });

    btnSkip?.addEventListener("click", hideGuideModal);

    // Sidebar toggles
    Object.entries(toggleButtons).forEach(([key, btn]) => {
        if (btn && sidebarElements[key]) {
            btn.addEventListener("click", () => {
                const sidebar = sidebarElements[key];
                const isHidden = sidebar.classList.contains("hidden");
                closeAllSidebars();
                if (key !== "help") {
                    sidebar.classList.toggle("hidden", !isHidden);
                }
            });
        }
    });

    // Close sidebar buttons
    ["layer", "basemap", "legend"].forEach((type) => {
        const closeBtn = document.getElementById(`btn-close-sidebar-${type}`);
        if (closeBtn && sidebarElements[type]) {
            closeBtn.addEventListener("click", () => {
                sidebarElements[type].classList.add("hidden");
            });
        }
    });

    // Kontrol Filter Data ada di modal Katalog Data dan diikat oleh map-catalog.js.
    toggleButtons.layer?.addEventListener("click", refreshFilterPanel);

    // Layer Tools panel (independen dari sidebar lain: boleh dibuka bersamaan
    // dengan panel Layer, karena isinya bergantung pada layer yang sedang aktif)
    const layerToolsPanel = document.getElementById("sidebar-layer-tools");
    const btnToggleLayerTools = document.getElementById("btn-toggle-layer-tools");
    const btnCloseLayerTools = document.getElementById("btn-close-sidebar-layer-tools");

    function openLayerToolsPanel() {
        if (!layerToolsPanel) return;
        positionLayerToolsPanel();
        updateLayerToolsPanel();
        layerToolsPanel.classList.remove("hidden");
        btnToggleLayerTools?.classList.add("bg-blue-50", "text-blue-600");
    }

    function closeLayerToolsPanel() {
        if (!layerToolsPanel) return;
        layerToolsPanel.classList.add("hidden");
        btnToggleLayerTools?.classList.remove("bg-blue-50", "text-blue-600");
    }

    btnToggleLayerTools?.addEventListener("click", () => {
        if (!layerToolsPanel) return;
        const isHidden = layerToolsPanel.classList.contains("hidden");
        isHidden ? openLayerToolsPanel() : closeLayerToolsPanel();
    });

    btnCloseLayerTools?.addEventListener("click", closeLayerToolsPanel);

    window.addEventListener("resize", () => {
        if (layerToolsPanel && !layerToolsPanel.classList.contains("hidden")) {
            positionLayerToolsPanel();
        }
    });

    // Fullscreen
    document.getElementById("btn-fullscreen")?.addEventListener("click", () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(console.error);
        } else {
            document.exitFullscreen().catch(console.error);
        }
    });

    // Zoom reset
    document
        .getElementById("btn-default-zoom")
        ?.addEventListener("click", () => {
            map.setView(mapConfig.center, mapConfig.zoom);
        });

    // ==================== SHARE PETA ====================
    const shareModal = document.getElementById("shareMapModal");
    const shareMapLinkInput = document.getElementById("shareMapLink");
    const shareMapLinkSpinner = document.getElementById("shareMapLinkSpinner");
    const shareMapContent = document.getElementById("shareMapContent");
    const shareMapEmptyState = document.getElementById("shareMapEmptyState");
    const btnShareMap = document.getElementById("btn-share-map");
    const btnCloseShareModal = document.getElementById("btn-close-share-modal");
    const btnCopyShareLink = document.getElementById("btn-copy-share-link");
    const btnCopyShareIcon = document.getElementById("btn-copy-share-icon");
    const btnCopyShareLabel = document.getElementById("btn-copy-share-label");
    const shareQuickButtons = document.querySelectorAll(".share-quick-btn");
    let copyFeedbackTimeout = null;

    function getCheckedLayerNames() {
        return (window.MarimoiCatalog?.getActiveEntries() || [])
            .filter((entry) => map.hasLayer(entry.layerGroup))
            .map((entry) => entry.leafName);
    }

    function openShareModal() {
        shareModal?.classList.remove("hidden");
        shareModal?.classList.add("flex");
    }

    function closeShareModal() {
        shareModal?.classList.add("hidden");
        shareModal?.classList.remove("flex");
    }

    function setShareLinkLoading(isLoading) {
        shareMapLinkSpinner?.classList.toggle("hidden", !isLoading);

        if (btnCopyShareLink) {
            btnCopyShareLink.disabled = isLoading;
        }

        shareQuickButtons.forEach((btn) => {
            btn.classList.toggle("pointer-events-none", isLoading);
            btn.classList.toggle("opacity-40", isLoading);
        });
    }

    btnShareMap?.addEventListener("click", async () => {
        const layers = getCheckedLayerNames();

        if (layers.length === 0) {
            shareMapEmptyState?.classList.remove("hidden");
            shareMapContent?.classList.add("hidden");
            openShareModal();
            return;
        }

        shareMapEmptyState?.classList.add("hidden");
        shareMapContent?.classList.remove("hidden");

        if (shareMapLinkInput) {
            shareMapLinkInput.value = "";
            shareMapLinkInput.placeholder = "Membuat link...";
        }

        setShareLinkLoading(true);
        openShareModal();

        const center = map.getCenter();
        const zoom = map.getZoom();
        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);

        try {
            const response = await fetch(window.MARIMOI_SHARE_STORE_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": window.MARIMOI_CSRF_TOKEN,
                },
                body: JSON.stringify({
                    layers,
                    viewport: { lat: center.lat, lng: center.lng, zoom },
                    data_type: tipeLayer.type,
                    sub_type: tipeLayer.sub_type,
                    year: tipeLayer.year,
                    filters: getActiveFilterValues(),
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            const shareUrl = result.url;

            if (shareMapLinkInput) shareMapLinkInput.value = shareUrl;

            const encodedUrl = encodeURIComponent(shareUrl);
            const shareText = encodeURIComponent(`Lihat peta tematik ini: ${shareUrl}`);

            const waLink = document.getElementById("share-whatsapp");
            const tgLink = document.getElementById("share-telegram");
            const mailLink = document.getElementById("share-email");

            if (waLink) waLink.href = `https://wa.me/?text=${shareText}`;
            if (tgLink) tgLink.href = `https://t.me/share/url?url=${encodedUrl}`;
            if (mailLink) {
                mailLink.href = `mailto:?subject=${encodeURIComponent("Peta Tematik MARIMOI")}&body=${shareText}`;
            }

            setShareLinkLoading(false);
        } catch (error) {
            console.error("Gagal membuat link share:", error);
            if (shareMapLinkInput) shareMapLinkInput.placeholder = "Gagal membuat link";
            shareMapLinkSpinner?.classList.add("hidden");
            showAlert("Gagal membuat link share. Silakan coba lagi.", "danger");
        }
    });

    btnCloseShareModal?.addEventListener("click", closeShareModal);

    function showCopyFeedback() {
        if (!btnCopyShareLink || !btnCopyShareIcon || !btnCopyShareLabel) return;

        clearTimeout(copyFeedbackTimeout);

        btnCopyShareIcon.className = "bi bi-check-lg";
        btnCopyShareLabel.textContent = "Tersalin!";
        btnCopyShareLink.classList.remove("bg-blue-500", "hover:bg-blue-600");
        btnCopyShareLink.classList.add("bg-green-500", "hover:bg-green-600");

        if (shareMapLinkInput) {
            shareMapLinkInput.style.borderColor = "#4ade80";
            shareMapLinkInput.style.backgroundColor = "#f0fdf4";
        }

        copyFeedbackTimeout = setTimeout(() => {
            btnCopyShareIcon.className = "bi bi-clipboard";
            btnCopyShareLabel.textContent = "Copy Link";
            btnCopyShareLink.classList.remove("bg-green-500", "hover:bg-green-600");
            btnCopyShareLink.classList.add("bg-blue-500", "hover:bg-blue-600");

            if (shareMapLinkInput) {
                shareMapLinkInput.style.borderColor = "";
                shareMapLinkInput.style.backgroundColor = "";
            }
        }, 2000);
    }

    btnCopyShareLink?.addEventListener("click", async () => {
        if (!shareMapLinkInput?.value) return;

        try {
            await navigator.clipboard.writeText(shareMapLinkInput.value);
        } catch (error) {
            shareMapLinkInput.select();
            document.execCommand("copy");
        }

        showCopyFeedback();
        showAlert("Link berhasil disalin.", "success");
    });
});

