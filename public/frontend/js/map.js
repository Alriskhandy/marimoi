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
const map = L.map("map", {
    zoomControl: true,
    attributionControl: true,
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

/**
 * Update checkbox state with loading indicator - Tailwind version
 */
function updateCheckboxLoadingState(categoryName, isLoading) {
    // Find the checkbox for this category
    const container = document.getElementById("layer-list");
    if (!container) return;

    const labels = container.querySelectorAll("label");
    labels.forEach((label) => {
        if (label.textContent.trim() === categoryName) {
            const checkbox = document.getElementById(label.htmlFor);
            if (checkbox) {
                if (isLoading) {
                    // Add loading state with Tailwind classes
                    checkbox.disabled = true;
                    label.classList.add("opacity-75", "animate-pulse");

                    // Add loading icon with Tailwind
                    if (!label.querySelector(".loading-icon")) {
                        const loadingIcon = document.createElement("span");
                        loadingIcon.className =
                            "loading-icon ml-2 text-blue-500 animate-spin";
                        loadingIcon.innerHTML =
                            '<i class="bi bi-arrow-clockwise"></i>';
                        label.appendChild(loadingIcon);
                    }
                } else {
                    // Remove loading state
                    checkbox.disabled = false;
                    label.classList.remove("opacity-75", "animate-pulse");

                    // Remove loading icon
                    const loadingIcon = label.querySelector(".loading-icon");
                    if (loadingIcon) {
                        loadingIcon.remove();
                    }
                }
            }
        }
    });
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

    function applyVisibility(leaf, visible) {
        if (typeof leaf.setStyle === "function") {
            if (visible) {
                leaf.setStyle(leaf.marimoiOriginalStyle || {});
            } else {
                leaf.marimoiOriginalStyle = leaf.marimoiOriginalStyle || { ...leaf.options };
                leaf.setStyle({ opacity: 0, fillOpacity: 0 });
            }
        } else if (typeof leaf.setOpacity === "function") {
            leaf.setOpacity(visible ? 1 : 0);
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
async function loadCategoriesMatchingFilter(filters) {
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

    for (const categoryName of categoryNames) {
        const checkbox = findCheckboxForCategory(categoryName);
        if (!checkbox || checkbox.checked) continue;

        await expandParentGroupIfNeeded(checkbox);
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event("change", { bubbles: true }));
        await new Promise((resolve) => setTimeout(resolve, 300));
    }
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

            if (layerGroup.eachLayer) {
                layerGroup.eachLayer((layer) => {
                    if (layer.setStyle) {
                        layer.setStyle({ opacity: val, fillOpacity: val });
                    }
                });
            }
        });

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

    const top = controlsRect.bottom - mapRect.top + 8;
    panel.style.top = `${top}px`;
    panel.style.left = `${controlsRect.left - mapRect.left}px`;

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

    const id = props.uuid || "";
    const lat = center?.[1] || 0;
    const lng = center?.[0] || 0;

    content += `
        <div class="flex gap-2 pt-2">
            <button class="zoomToBtn flex-1 bg-blue-500 hover:bg-blue-600 text-white text-sm px-2 py-1 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                data-lat="${lat}" data-lng="${lng}">
                <i class="bi bi-zoom-in mr-1"></i>
                Zoom To
            </button>
            <a href="${urlPath}/${id}"
                class="flex-1 bg-green-500 hover:bg-green-600 text-white text-sm px-2 py-1 rounded-md text-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 no-underline">
                <i class="bi bi-eye mr-1"></i>
                Detail
            </a>
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
    const catObj = geoJsonData.all_categories?.find((c) => c.nama === categoryName);
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
        updateCheckboxLoadingState(categoryName, true);
        const cachedChunks = await readAllCachedChunks(
            { type: dataType, sub_type: subType, year: year, category: categoryName },
            maxRecords,
            chunkSize
        );

        if (cachedChunks) {
            targetLayer.clearLayers();
            await renderCachedChunks(cachedChunks, targetLayer, categoryName, urlPath);
            loadedCategories.add(categoryName);
            updateCheckboxLoadingState(categoryName, false);
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
        updateCheckboxLoadingState(categoryName, false);
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
        updateCheckboxLoadingState(categoryName, false);

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
 * Helper function to update parent checkbox state based on children
 */
function updateParentCheckboxState(parentId, childContainer) {
    const parentCheckbox = document.getElementById(parentId);
    if (!parentCheckbox) return;
    
    const childCheckboxes = childContainer.querySelectorAll('input[type="checkbox"]');
    if (childCheckboxes.length === 0) return;
    
    const checkedCount = Array.from(childCheckboxes).filter(cb => cb.checked).length;
    
    if (checkedCount === 0) {
        parentCheckbox.checked = false;
        parentCheckbox.indeterminate = false;
    } else if (checkedCount === childCheckboxes.length) {
        parentCheckbox.checked = true;
        parentCheckbox.indeterminate = false;
    } else {
        parentCheckbox.checked = false;
        parentCheckbox.indeterminate = true;
    }
}

/**
 * Enhanced updateLayerList with support for 3-level hierarchy
 * Root level is now just a header (no checkbox)
 */
function updateLayerList() {
    const container = document.getElementById("layer-list");
    if (!container) return;

    container.innerHTML = "";

    // Process each root category (Level 1)
    Object.entries(layerGroups).forEach(([rootName, secondLevel]) => {
        const rootId = `root-${rootName.replace(/\s+/g, "-")}`;
        const rootWrapper = document.createElement("div");
        rootWrapper.className = "mb-3";

        // Create root header (Level 1) - no checkbox, just a clickable header
        const rootHeader = document.createElement("div");
        rootHeader.id = rootId;
        rootHeader.className =
            "flex items-center justify-between px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200";

        const rootLeftSection = document.createElement("div");
        rootLeftSection.className = "flex items-center";

        // Root toggle icon
        const rootToggleBtn = document.createElement("span");
        rootToggleBtn.className = "mr-2 transition-transform duration-300 ease-in-out";
        rootToggleBtn.innerHTML = `<i class="bi bi-chevron-right text-gray-600"></i>`;

        // Induk hanya boleh dicentang bila tidak ada sub kategorinya yang masih punya sub kategori
        // lagi (aturan yang sama dengan peta admin). Induk bertingkat hanya berfungsi sebagai grup.
        const isRootCheckable = Object.entries(secondLevel).every(([secondName, thirdLevel]) => {
            const thirdNames = Object.keys(thirdLevel);
            return thirdNames.length === 1 && thirdNames[0] === secondName;
        });

        // Induk tanpa sub kategori sungguhan (hanya placeholder bernama sama dengan dirinya):
        // cukup satu baris dengan checkbox, tanpa daftar turunan yang menggandakan nama.
        const isLeafRoot = Object.entries(secondLevel).every(([secondName, thirdLevel]) => {
            const thirdNames = Object.keys(thirdLevel);
            return secondName === rootName && thirdNames.length === 1 && thirdNames[0] === rootName;
        });

        let rootCheckbox = null;
        if (isRootCheckable) {
            rootCheckbox = document.createElement("input");
            rootCheckbox.type = "checkbox";
            rootCheckbox.className = "mr-2 h-4 w-4 text-blue-500 focus:ring-blue-400 border-2 border-gray-400 rounded";
            rootCheckbox.id = rootId + "-checkbox";
            rootCheckbox.setAttribute("data-level", "1");
            rootCheckbox.setAttribute("data-category", rootName);
        }

        // Root label (memakai <label> bila induk punya checkbox)
        const rootLabel = document.createElement(isRootCheckable ? "label" : "div");
        rootLabel.className = "font-semibold text-gray-900 text-sm layer-label";
        rootLabel.dataset.layerLevel = "1";
        rootLabel.textContent = rootName;
        if (isRootCheckable) {
            rootLabel.htmlFor = rootCheckbox.id;
            rootLabel.classList.add("cursor-pointer");
        }

        // Count badge for root
        const secondLevelCount = Object.keys(secondLevel).length;
        const rootBadge = document.createElement("span");
        rootBadge.className = "ml-2 px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full";
        rootBadge.textContent = secondLevelCount;

        // Add root elements to header
        if (isLeafRoot) {
            // Tetap ada agar lebar sejajar dengan induk lain, tapi tidak terlihat.
            rootToggleBtn.classList.add("invisible");
            rootHeader.classList.remove("cursor-pointer");
        }

        rootLeftSection.appendChild(rootToggleBtn);
        if (rootCheckbox) rootLeftSection.appendChild(rootCheckbox);
        rootLeftSection.appendChild(rootLabel);
        if (!isLeafRoot) rootLeftSection.appendChild(rootBadge);
        rootHeader.appendChild(rootLeftSection);
        rootWrapper.appendChild(rootHeader);

        // Create container for second level items
        const secondLevelContainer = document.createElement("div");
        secondLevelContainer.className = "border-l border-r border-b border-gray-300 rounded-b-lg bg-gray-50 rounded-lg hidden layer-root-children";
        secondLevelContainer.id = `${rootId}-children`;
        
        // Process each second level category (Level 2)
        Object.entries(secondLevel).forEach(([secondName, thirdLevel]) => {
            const secondId = `second-${rootName}-${secondName}`.replace(/\s+/g, "-");
            const secondItemRow = document.createElement("div");
            secondItemRow.className = "px-2 py-1 layer-second-row";
            
            // Create second level header
            const secondHeader = document.createElement("div");
            secondHeader.className = 
                "flex items-center justify-between px-3 py-2 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-100 transition-colors duration-200 ml-3 mb-1"; // Added more padding and bottom margin
            
            const secondLeftSection = document.createElement("div");
            secondLeftSection.className = "flex items-center";
            
            // Second level toggle icon
            const secondToggleBtn = document.createElement("span");
            secondToggleBtn.className = "mr-2 transition-transform duration-300 ease-in-out";
            secondToggleBtn.innerHTML = `<i class="bi bi-chevron-right text-gray-500"></i>`;
            
            // Second level checkbox
            const secondCheckbox = document.createElement("input");
            secondCheckbox.type = "checkbox";
            secondCheckbox.className = "mr-2 h-4 w-4 text-blue-500 focus:ring-blue-400 border-2 border-gray-400 rounded";
            secondCheckbox.id = secondId;
            secondCheckbox.setAttribute('data-level', '2');
            secondCheckbox.setAttribute('data-category', secondName);
            secondCheckbox.setAttribute('data-parent', rootName);
            
            // Second level label
            const secondLabel = document.createElement("label");
            secondLabel.className = "text-sm text-gray-700 cursor-pointer layer-label";
            secondLabel.dataset.layerLevel = "2";
            secondLabel.htmlFor = secondId;
            secondLabel.textContent = secondName;
            
            // Count badge for second level
            const thirdLevelCount = Object.keys(thirdLevel).length;
            const secondBadge = document.createElement("span");
            secondBadge.className = "ml-2 px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full";
            secondBadge.textContent = thirdLevelCount;
            
            // Sub kategori tanpa turunan sungguhan (hanya placeholder bernama sama): berperilaku
            // sebagai daun, jadi tidak ada panah/hitungan dan tidak menampilkan baris duplikat.
            const thirdNamesOfSecond = Object.keys(thirdLevel);
            const isPlaceholderOnly = thirdNamesOfSecond.length === 1 && thirdNamesOfSecond[0] === secondName;
            if (isPlaceholderOnly) {
                secondToggleBtn.classList.add("invisible");
                secondHeader.classList.remove("cursor-pointer");
            }

            // Add second level elements to header
            secondLeftSection.appendChild(secondToggleBtn);
            secondLeftSection.appendChild(secondCheckbox);
            secondLeftSection.appendChild(secondLabel);
            if (!isPlaceholderOnly) secondLeftSection.appendChild(secondBadge);
            secondHeader.appendChild(secondLeftSection);
            secondItemRow.appendChild(secondHeader);
            
            // Create container for third level items
            const thirdLevelContainer = document.createElement("div");
            thirdLevelContainer.className = "pl-4 ml-5 border-l border-gray-200 mt-1 hidden layer-third-children";
            thirdLevelContainer.id = `${secondId}-children`;
            
            // Second level checkbox controls all children
            const applySecondLevelChange = async () => {
                const isChecked = secondCheckbox.checked;
                
                // Disable checkbox during loading
                secondCheckbox.disabled = true;
                secondCheckbox.className = secondCheckbox.className + " opacity-50 cursor-not-allowed";
                
                try {
                    // Update all third level checkboxes
                    const thirdLevelCheckboxes = thirdLevelContainer.querySelectorAll('input[type="checkbox"]');
                    
                    for (const checkbox of thirdLevelCheckboxes) {
                        checkbox.checked = isChecked;
                        
                        // Get category data
                        const categoryName = checkbox.getAttribute('data-category');
                        
                        if (categoryName) {
                            if (isChecked) {
                                // Load data for checked categories
                                await loadCategoryData(categoryName, secondName, rootName);
                                
                                // Add layer to map
                                if (layerGroups[rootName]?.[secondName]?.[categoryName]) {
                                    map.addLayer(layerGroups[rootName][secondName][categoryName]);
                                }
                            } else {
                                // Remove layer from map
                                if (layerGroups[rootName]?.[secondName]?.[categoryName]) {
                                    map.removeLayer(layerGroups[rootName][secondName][categoryName]);
                                }
                            }
                        }
                    }

                    updateRootCheckboxState(rootCheckbox, secondLevelContainer);
                    generateLegend();
                    updateLayerToolsPanel();
                } finally {
                    secondCheckbox.disabled = false;
                    secondCheckbox.className = secondCheckbox.className.replace(" opacity-50 cursor-not-allowed", "");
                }
            };
            secondCheckbox.addEventListener("change", applySecondLevelChange);
            // Dipakai checkbox induk agar tiap sub kategori dimuat berurutan (loadCategoryData tidak paralel).
            secondCheckbox._applyChange = applySecondLevelChange;
            
            // Process third level categories (Level 3)
            Object.keys(thirdLevel).forEach((thirdName) => {
                // Skip if same name as parent (used as placeholder)
                if (thirdName === secondName && Object.keys(thirdLevel).length > 1) return;
                
                const thirdId = `third-${rootName}-${secondName}-${thirdName}`.replace(/\s+/g, "-");
                const thirdRow = document.createElement("div");
                thirdRow.className = "flex items-center py-2 hover:bg-gray-100 transition-colors duration-150 rounded px-2 mt-1 layer-third-row"; // Added vertical spacing
                
                // Third level checkbox - with consistent size
                const thirdCheckbox = document.createElement("input");
                thirdCheckbox.type = "checkbox";
                thirdCheckbox.className = "mr-2 h-4 w-4 text-blue-400 focus:ring-blue-300 border-2 border-gray-300 rounded";
                thirdCheckbox.id = thirdId;
                thirdCheckbox.setAttribute('data-level', '3');
                thirdCheckbox.setAttribute('data-category', thirdName);
                thirdCheckbox.setAttribute('data-parent', secondName);
                thirdCheckbox.setAttribute('data-grandparent', rootName);
                
                // Third level label
                const thirdLabel = document.createElement("label");
                thirdLabel.className = "text-xs text-gray-600 cursor-pointer flex-1 leading-tight layer-label";
                thirdLabel.dataset.layerLevel = "3";
                thirdLabel.htmlFor = thirdId;
                thirdLabel.textContent = thirdName;
                
                // Add third level elements
                thirdRow.appendChild(thirdCheckbox);
                thirdRow.appendChild(thirdLabel);
                thirdLevelContainer.appendChild(thirdRow);
                
                // Third level checkbox handler
                const applyThirdLevelChange = async () => {
                    // Disable checkbox during loading
                    thirdCheckbox.disabled = true;
                    thirdCheckbox.className = thirdCheckbox.className + " opacity-50 cursor-not-allowed";

                    try {
                        if (thirdCheckbox.checked) {
                            // Load data on-demand if not loaded yet
                            await loadCategoryData(thirdName, secondName, rootName);

                            // Add layer to map
                            if (layerGroups[rootName]?.[secondName]?.[thirdName]) {
                                map.addLayer(layerGroups[rootName][secondName][thirdName]);
                            }
                        } else {
                            // Remove layer from map
                            if (layerGroups[rootName]?.[secondName]?.[thirdName]) {
                                map.removeLayer(layerGroups[rootName][secondName][thirdName]);
                            }
                        }

                        // Update second level checkbox state based on third level checkboxes
                        updateSecondLevelCheckboxState(secondCheckbox, thirdLevelContainer);
                        updateRootCheckboxState(rootCheckbox, secondLevelContainer);

                        // Update legend & layer tools panel
                        generateLegend();
                        updateLayerToolsPanel();
                    } finally {
                        thirdCheckbox.disabled = false;
                        thirdCheckbox.className = thirdCheckbox.className.replace(" opacity-50 cursor-not-allowed", "");
                    }
                };
                thirdCheckbox.addEventListener("change", applyThirdLevelChange);
                // Referensi ke handler-nya sendiri, dipakai checkbox induk (root/second level)
                // agar tiap kategori dimuat berurutan lewat pemanggilan langsung yang bisa di-`await`.
                thirdCheckbox._applyChange = applyThirdLevelChange;
            });
            
            // Toggle functionality for second level
            secondHeader.addEventListener("click", (e) => {
                if (isPlaceholderOnly) return;

                // Ignore clicks on checkbox and label
                if (e.target !== secondCheckbox && e.target !== secondLabel) {
                    const isVisible = !thirdLevelContainer.classList.contains("hidden");
                    
                    if (isVisible) {
                        thirdLevelContainer.classList.add("hidden");
                        secondToggleBtn.innerHTML = `<i class="bi bi-chevron-right text-gray-500"></i>`;
                        secondToggleBtn.classList.remove("rotate-90");
                    } else {
                        thirdLevelContainer.classList.remove("hidden");
                        secondToggleBtn.innerHTML = `<i class="bi bi-chevron-down text-gray-500"></i>`;
                        secondToggleBtn.classList.add("rotate-90");
                    }
                }
            });
            
            // Add third level container to second level row
            secondItemRow.appendChild(thirdLevelContainer);
            secondLevelContainer.appendChild(secondItemRow);
        });
        
        // Checkbox induk: centang/hapus centang seluruh sub kategori (berurutan).
        if (rootCheckbox) {
            rootCheckbox.addEventListener("change", async () => {
                const isChecked = rootCheckbox.checked;
                rootCheckbox.indeterminate = false;
                rootCheckbox.disabled = true;
                rootCheckbox.classList.add("opacity-50", "cursor-not-allowed");

                try {
                    const secondCheckboxes = secondLevelContainer.querySelectorAll('input[data-level="2"]');

                    for (const cb of secondCheckboxes) {
                        if (cb.checked !== isChecked && cb._applyChange) {
                            cb.checked = isChecked;
                            await cb._applyChange();
                        }
                    }

                    if (isChecked && !isLeafRoot) {
                        secondLevelContainer.classList.remove("hidden");
                        rootToggleBtn.innerHTML = `<i class="bi bi-chevron-down text-gray-600"></i>`;
                        rootToggleBtn.classList.add("rotate-90");
                    }
                } finally {
                    rootCheckbox.disabled = false;
                    rootCheckbox.classList.remove("opacity-50", "cursor-not-allowed");
                    updateRootCheckboxState(rootCheckbox, secondLevelContainer);
                }
            });
        }

        // Toggle functionality for root level (klik pada checkbox/label tidak ikut membuka/menutup)
        rootHeader.addEventListener("click", (e) => {
            if (isLeafRoot) return;
            if (rootCheckbox && (e.target === rootCheckbox || e.target === rootLabel)) return;

            const isVisible = !secondLevelContainer.classList.contains("hidden");
            
            if (isVisible) {
                secondLevelContainer.classList.add("hidden");
                rootToggleBtn.innerHTML = `<i class="bi bi-chevron-right text-gray-600"></i>`;
                rootToggleBtn.classList.remove("rotate-90");
            } else {
                secondLevelContainer.classList.remove("hidden");
                rootToggleBtn.innerHTML = `<i class="bi bi-chevron-down text-gray-600"></i>`;
                rootToggleBtn.classList.add("rotate-90");
            }
        });
        
        // Add second level container to root
        rootWrapper.appendChild(secondLevelContainer);
        container.appendChild(rootWrapper);
    });
}

/**
 * Sinkronkan checkbox induk dengan status checkbox sub kategorinya (centang penuh / sebagian).
 */
function updateRootCheckboxState(rootCheckbox, secondLevelContainer) {
    if (!rootCheckbox) return;

    const children = secondLevelContainer.querySelectorAll('input[data-level="2"]');
    if (children.length === 0) return;

    const checkedCount = Array.from(children).filter((cb) => cb.checked).length;
    rootCheckbox.checked = checkedCount === children.length;
    rootCheckbox.indeterminate = checkedCount > 0 && checkedCount < children.length;
}

/**
 * Helper function to update second level checkbox state based on third level checkboxes
 */
function updateSecondLevelCheckboxState(secondLevelCheckbox, thirdLevelContainer) {
    if (!secondLevelCheckbox) return;
    
    const childCheckboxes = thirdLevelContainer.querySelectorAll('input[type="checkbox"]');
    if (childCheckboxes.length === 0) return;
    
    const checkedCount = Array.from(childCheckboxes).filter(cb => cb.checked).length;
    
    if (checkedCount === 0) {
        secondLevelCheckbox.checked = false;
        secondLevelCheckbox.indeterminate = false;
    } else if (checkedCount === childCheckboxes.length) {
        secondLevelCheckbox.checked = true;
        secondLevelCheckbox.indeterminate = false;
    } else {
        secondLevelCheckbox.checked = false;
        secondLevelCheckbox.indeterminate = true;
    }
}

/**
 * This function needs to be updated for expandParentGroupIfNeeded
 * to work with the new hierarchy structure where root level has no checkbox
 */
async function expandParentGroupIfNeeded(checkbox) {
    // For third level - need to expand both parent and grandparent
    if (checkbox.getAttribute('data-level') === '3') {
        const parentName = checkbox.getAttribute('data-parent');
        const grandparentName = checkbox.getAttribute('data-grandparent');
        
        if (parentName && grandparentName) {
            // First expand root level
            const rootId = `root-${grandparentName.replace(/\s+/g, "-")}`;
            const rootContainer = document.getElementById(`${rootId}-children`);
            
            if (rootContainer && rootContainer.classList.contains('hidden')) {
                // Find root header and click it
                const rootWrapper = rootContainer.closest('.mb-3');
                const rootHeader = rootWrapper?.querySelector('.flex.items-center.justify-between');
                if (rootHeader) {
                    rootHeader.click();
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
            }
            
            // Then expand second level
            const secondId = `second-${grandparentName}-${parentName}`.replace(/\s+/g, "-");
            const secondContainer = document.getElementById(`${secondId}-children`);
            
            if (secondContainer && secondContainer.classList.contains('hidden')) {
                // Find second header and click it
                const secondItem = secondContainer.closest('.px-2.py-1');
                const secondHeader = secondItem?.querySelector('.flex.items-center.justify-between');
                if (secondHeader) {
                    secondHeader.click();
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
            }
            
            return;
        }
    }
    
    // For second level - just expand parent
    if (checkbox.getAttribute('data-level') === '2') {
        const parentName = checkbox.getAttribute('data-parent');
        
        if (parentName) {
            const rootId = `root-${parentName.replace(/\s+/g, "-")}`;
            const rootContainer = document.getElementById(`${rootId}-children`);
            
            if (rootContainer && rootContainer.classList.contains('hidden')) {
                const rootWrapper = rootContainer.closest('.mb-3');
                const rootHeader = rootWrapper?.querySelector('.flex.items-center.justify-between');
                if (rootHeader) {
                    rootHeader.click();
                    await new Promise(resolve => setTimeout(resolve, 300));
                }
            }
            
            return;
        }
    }
    
    // Legacy handling for backward compatibility
    const groupElement = checkbox.closest(".mb-3");

    if (!groupElement) {
        return;
    }

    // Cari sub-layer list (container yang mungkin hidden)
    const subLayerList = groupElement.querySelector(".border-l");

    if (subLayerList && subLayerList.classList.contains("hidden")) {
        // Cari header untuk diklik
        const header = groupElement.querySelector(
            ".flex.items-center.justify-between"
        );

        if (header) {
            // Simulasi klik header untuk expand
            header.click();

            // Tunggu animasi expand selesai
            await new Promise((resolve) => setTimeout(resolve, 300));
        }
    }
}

/**
 * Helper function to update second level checkbox state based on third level checkboxes
 */
function updateSecondLevelCheckboxState(secondLevelCheckbox, thirdLevelContainer) {
    if (!secondLevelCheckbox) return;
    
    const childCheckboxes = thirdLevelContainer.querySelectorAll('input[type="checkbox"]');
    if (childCheckboxes.length === 0) return;
    
    const checkedCount = Array.from(childCheckboxes).filter(cb => cb.checked).length;
    
    if (checkedCount === 0) {
        secondLevelCheckbox.checked = false;
        secondLevelCheckbox.indeterminate = false;
    } else if (checkedCount === childCheckboxes.length) {
        secondLevelCheckbox.checked = true;
        secondLevelCheckbox.indeterminate = false;
    } else {
        secondLevelCheckbox.checked = false;
        secondLevelCheckbox.indeterminate = true;
    }
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

    for (const categoryName of state.layers) {
        const checkbox = findCheckboxForCategory(categoryName);

        if (!checkbox) {
            showAlert(`Layer "${categoryName}" dari link share tidak ditemukan.`, "warning");
            continue;
        }

        await expandParentGroupIfNeeded(checkbox);

        if (!checkbox.checked) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event("change", { bubbles: true }));
            // Beri jeda supaya pemuatan data tiap layer tidak saling tabrakan
            await new Promise((resolve) => setTimeout(resolve, 300));
        }
    }

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
 * Auto-click checkbox untuk kategori yang dipilih dari session
 */
async function autoClickCategoryFromSession() {
    const selectedCategory = getSelectedCategoryFromSession();

    if (!selectedCategory) {
        return;
    }

    // Auto-click category from session

    // Tunggu hingga UI benar-benar siap
    let attempts = 0;
    const maxAttempts = 30; // 15 detik maksimal

    const waitForUI = async () => {
        // Cek apakah layer list sudah ada dan tidak kosong
        const layerList = document.getElementById("layer-list");
        const checkboxes = layerList?.querySelectorAll(
            'input[type="checkbox"]'
        );

        if (!layerList || !checkboxes || checkboxes.length === 0) {
            if (attempts < maxAttempts) {
                attempts++;
                await new Promise((resolve) => setTimeout(resolve, 500));
                return waitForUI();
            }
            return false;
        }
        return true;
    };

    const uiReady = await waitForUI();

    if (!uiReady) {
        showAlert(
            `UI tidak siap untuk memuat kategori ${selectedCategory}`,
            "warning"
        );
        return;
    }

    // Cari checkbox yang sesuai dengan kategori
    const targetCheckbox = findCheckboxForCategory(selectedCategory);

    if (!targetCheckbox) {
        showAlert(
            `Kategori "${selectedCategory}" tidak ditemukan di daftar layer`,
            "warning"
        );
        return;
    }

    try {
        // Show info message
        showAlert(`Memuat peta ${selectedCategory}...`, "info", true);

        // Expand parent group jika diperlukan (untuk sub-kategori)
        await expandParentGroupIfNeeded(targetCheckbox);

        // Simulasi klik checkbox - ini akan trigger event handler yang sudah ada
        targetCheckbox.checked = true;

        // Trigger change event untuk mengaktifkan fungsi loadCategoryData yang sudah ada
        const changeEvent = new Event("change", { bubbles: true });
        targetCheckbox.dispatchEvent(changeEvent);

    } catch (error) {
        // Log error in development only
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.error("Error during auto-click:", error);
        }
        showAlert(
            `Gagal memuat kategori "${selectedCategory}": ${error.message}`,
            "danger"
        );
    }
}

/**
 * Cari checkbox yang sesuai dengan nama kategori
 */
function findCheckboxForCategory(categoryName) {
    const allCheckboxes = document.querySelectorAll(
        '#layer-list input[type="checkbox"]'
    );

    // Cari berdasarkan label text
    for (const checkbox of allCheckboxes) {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        if (label && label.textContent.trim() === categoryName) {
            return checkbox;
        }
    }

    // Cari berdasarkan data attributes sebagai prioritas
    for (const checkbox of allCheckboxes) {
        if (checkbox.getAttribute('data-category') === categoryName) {
            return checkbox;
        }
    }

    // Cari berdasarkan ID pattern sebagai fallback
    const possibleIds = [
        `root-${categoryName}`.replace(/\s+/g, "-"),
        `second-${categoryName}`.replace(/\s+/g, "-"),
        `third-.*-${categoryName}`.replace(/\s+/g, "-"),
    ];

    for (const idPattern of possibleIds) {
        const regex = new RegExp(idPattern);
        for (const checkbox of allCheckboxes) {
            if (regex.test(checkbox.id)) {
                return checkbox;
            }
        }
    }

    // Cari dengan pattern yang lebih fleksibel
    for (const checkbox of allCheckboxes) {
        const checkboxId = checkbox.id.toLowerCase();
        const categoryLower = categoryName.toLowerCase().replace(/\s+/g, "-");

        if (checkboxId.includes(categoryLower)) {
            return checkbox;
        }
    }

    return null;
}

/**
 * Expand parent group jika checkbox adalah sub-kategori
 */
async function expandParentGroupIfNeeded(checkbox) {
    // For third level - need to expand both parent and grandparent
    if (checkbox.getAttribute('data-level') === '3') {
        const parentName = checkbox.getAttribute('data-parent');
        const grandparentName = checkbox.getAttribute('data-grandparent');
        
        if (parentName && grandparentName) {
            // First expand root level
            const rootId = `root-${grandparentName.replace(/\s+/g, "-")}`;
            const rootHeader = document.querySelector(`#${rootId}`).closest('.flex.items-center.justify-between');
            const rootContainer = document.getElementById(`${rootId}-children`);
            
            if (rootHeader && rootContainer && rootContainer.classList.contains('hidden')) {
                rootHeader.click();
                await new Promise(resolve => setTimeout(resolve, 300));
            }
            
            // Then expand second level
            const secondId = `second-${grandparentName}-${parentName}`.replace(/\s+/g, "-");
            const secondHeader = document.querySelector(`#${secondId}`).closest('.flex.items-center.justify-between');
            const secondContainer = document.getElementById(`${secondId}-children`);
            
            if (secondHeader && secondContainer && secondContainer.classList.contains('hidden')) {
                secondHeader.click();
                await new Promise(resolve => setTimeout(resolve, 300));
            }
            
            return;
        }
    }
    
    // For second level - just expand parent
    if (checkbox.getAttribute('data-level') === '2') {
        const parentName = checkbox.getAttribute('data-parent');
        
        if (parentName) {
            const rootId = `root-${parentName.replace(/\s+/g, "-")}`;
            const rootHeader = document.querySelector(`#${rootId}`).closest('.flex.items-center.justify-between');
            const rootContainer = document.getElementById(`${rootId}-children`);
            
            if (rootHeader && rootContainer && rootContainer.classList.contains('hidden')) {
                rootHeader.click();
                await new Promise(resolve => setTimeout(resolve, 300));
            }
            
            return;
        }
    }
    
    // Legacy handling for backward compatibility
    const groupElement = checkbox.closest(".mb-3");

    if (!groupElement) {
        return;
    }

    // Cari sub-layer list (container yang mungkin hidden)
    const subLayerList = groupElement.querySelector(".border-l");

    if (subLayerList && subLayerList.classList.contains("hidden")) {
        // Cari header untuk diklik
        const header = groupElement.querySelector(
            ".flex.items-center.justify-between"
        );

        if (header) {
            // Simulasi klik header untuk expand
            header.click();

            // Tunggu animasi expand selesai
            await new Promise((resolve) => setTimeout(resolve, 300));
        }
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
        download: document.getElementById("sidebar-download"),
        help: document.getElementById("guideModal"),
    };

    // Toggle buttons
    const toggleButtons = {
        layer: document.getElementById("btn-toggle-sidebar-layer"),
        basemap: document.getElementById("btn-toggle-sidebar-basemap"),
        legend: document.getElementById("btn-toggle-sidebar-legend"),
        download: document.getElementById("btn-toggle-sidebar-download"),
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
        toggleButtons.download,
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
    ["layer", "basemap", "legend", "download"].forEach((type) => {
        const closeBtn = document.getElementById(`btn-close-sidebar-${type}`);
        if (closeBtn && sidebarElements[type]) {
            closeBtn.addEventListener("click", () => {
                sidebarElements[type].classList.add("hidden");
            });
        }
    });

    // Filter Data kini menyatu di sidebar Layer: opsi bertambah otomatis saat data
    // dimuat (lihat loadCategoryData). Memilih nilai filter memuat & mencentang seluruh
    // kategori yang belum aktif (applyStructuredFilters -> activateAllCategoriesForFilter)
    // supaya filter menampilkan layer yang cocok, bukan cuma menyaring layer yang
    // kebetulan sudah dicentang lebih dulu.
    ["filter-kabupaten", "filter-tahun", "filter-opd"].forEach((id) => {
        document.getElementById(id)?.addEventListener("change", applyStructuredFilters);
    });

    document.getElementById("btn-reset-filter")?.addEventListener("click", () => {
        ["filter-kabupaten", "filter-tahun", "filter-opd"].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.value = "";
        });
        refreshFilterPanel();
    });

    toggleButtons.layer?.addEventListener("click", refreshFilterPanel);

    // Toggle panel Filter Data, terpisah dari toggle sidebar Layer supaya filter
    // bisa dibuka/ditutup tanpa menutup sidebar Layer itu sendiri.
    const filterPanel = document.getElementById("filter-panel");
    const btnToggleFilterPanel = document.getElementById("btn-toggle-filter-panel");
    btnToggleFilterPanel?.addEventListener("click", () => {
        if (!filterPanel) return;
        const isHidden = filterPanel.classList.toggle("hidden");
        btnToggleFilterPanel.setAttribute("aria-expanded", String(!isHidden));
    });

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

    // Search (debounce)
    const layerSearchInput = document.getElementById("layer-search");
    const layerSearchClear = document.getElementById("layer-search-clear");
    const layerSearchEmpty = document.getElementById("layer-search-empty");
    let searchTimeout;

    function applyLayerSearch(rawTerm) {
        const term = rawTerm.trim().toLowerCase();
        const showAll = term === "";

        // Strict substring match (equivalent to SQL LIKE '%term%'): a row is only
        // ever shown if its OWN label literally contains the term, or it is an
        // ancestor of a row that does — non-matching siblings stay hidden even when
        // a parent/child in the same branch matches. Matching/navigation below relies
        // only on stable classes (.layer-label, .layer-root-children, .layer-second-row,
        // .layer-third-row, .layer-third-children) added in updateLayerList() — no
        // selectors are ever built from category-name-derived strings, so there is
        // nothing here for an unusual category name to break.
        const rootWrappers = document.querySelectorAll("#layer-list > .mb-3");
        let resultCount = 0;

        const isMatch = (label) => showAll || (label?.textContent.toLowerCase().includes(term) ?? false);

        rootWrappers.forEach((rootWrapper) => {
            try {
                const rootHeader = rootWrapper.querySelector(".flex.items-center.justify-between");
                const rootLabelEl = rootWrapper.querySelector('.layer-label[data-layer-level="1"]');
                const rootOwnMatch = isMatch(rootLabelEl);

                let rootHasVisibleChild = false;

                rootWrapper.querySelectorAll('.layer-second-row').forEach((secondRow) => {
                    const secondLabelEl = secondRow.querySelector('.layer-label[data-layer-level="2"]');
                    const secondOwnMatch = isMatch(secondLabelEl);
                    if (secondOwnMatch) resultCount++;

                    let secondHasVisibleChild = false;

                    secondRow.querySelectorAll('.layer-third-row').forEach((thirdRow) => {
                        const thirdLabelEl = thirdRow.querySelector('.layer-label[data-layer-level="3"]');
                        const thirdOwnMatch = isMatch(thirdLabelEl);
                        if (thirdOwnMatch) resultCount++;

                        thirdRow.style.display = thirdOwnMatch ? "" : "none";
                        if (thirdOwnMatch) secondHasVisibleChild = true;
                    });

                    const secondVisible = secondOwnMatch || secondHasVisibleChild;
                    secondRow.style.display = secondVisible ? "" : "none";
                    if (secondVisible) rootHasVisibleChild = true;

                    // Auto-expand this branch so a matching third-level child is visible
                    if (!showAll && secondHasVisibleChild) {
                        const thirdChildren = secondRow.querySelector('.layer-third-children');
                        const secondHeader = secondRow.querySelector('.flex.items-center.justify-between');
                        if (secondHeader && thirdChildren && thirdChildren.classList.contains('hidden')) {
                            secondHeader.click();
                        }
                    }
                });

                const rootVisible = rootOwnMatch || rootHasVisibleChild;
                rootWrapper.style.display = rootVisible ? "block" : "none";

                // Auto-expand the root so a matching branch/leaf underneath is visible
                if (!showAll && rootVisible && rootHasVisibleChild) {
                    const rootChildren = rootWrapper.querySelector('.layer-root-children');
                    if (rootChildren && rootChildren.classList.contains("hidden")) {
                        rootHeader?.click();
                    }
                }
            } catch (err) {
                console.warn('Layer search: gagal memproses root wrapper', err);
            }
        });

        if (layerSearchEmpty) {
            layerSearchEmpty.classList.toggle('hidden', showAll || resultCount > 0);
        }
        if (layerSearchClear) {
            layerSearchClear.classList.toggle('hidden', rawTerm === "");
        }
    }

    layerSearchInput?.addEventListener("input", (e) => {
        clearTimeout(searchTimeout);
        const value = e.target.value;
        searchTimeout = setTimeout(() => applyLayerSearch(value), 300);
    });

    layerSearchClear?.addEventListener("click", () => {
        if (!layerSearchInput) return;
        layerSearchInput.value = "";
        layerSearchInput.focus();
        clearTimeout(searchTimeout);
        applyLayerSearch("");
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
        const checked = document.querySelectorAll(
            '#layer-list input[type="checkbox"]:checked'
        );
        const names = new Set();

        checked.forEach((checkbox) => {
            const name = checkbox.getAttribute("data-category");
            if (name) names.add(name);
        });

        return Array.from(names);
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

