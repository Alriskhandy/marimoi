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

                if (icon) {
                    legendContainer.innerHTML += `
                        <div class="flex items-center mb-2 w-full">
                            <div class="custom-fa-icon flex-shrink-0 flex items-center justify-center" style="width: 14px; height: 14px; background: transparent; border: none; margin-right: 8px;">
                                <i class="${icon} text-[${color}]" style="font-size: 12px; color: ${color}; line-height: 1;"></i>
                            </div>
                            <span class="flex-1" style="font-size: 0.85rem;">${thirdName}</span>
                        </div>
                    `;
                } else {
                    legendContainer.innerHTML += `
                        <div class="flex items-center mb-2 w-full">
                            <div class="flex-shrink-0" style="width: 14px; height: 14px; background-color: ${color}; border: 1px solid #333; margin-right: 8px;"></div>
                            <span class="flex-1" style="font-size: 0.85rem;">${thirdName}</span>
                        </div>
                    `;
                }
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
 * Membuat dan mengikat konten popup pada setiap fitur peta.
 */
function bindPopupContent(feature, layer, urlPath) {
    const props = feature.properties;
    let content = `<div class="py-1" style="max-width: 230px; font-size: 12px;">
        <h5 class="fw-bold text-primary" style="font-size: 12px; margin-bottom: 5px;">${
            props.kategori || "Feature"
        }</h5>
        <img src="frontend/img/kantor-gub-malut.jpeg" alt="Template Image" style="width: 100%; max-height: 120px; object-fit: cover; margin-bottom: 5px;">`;

    content += `<hr style="margin: 5px 0;"><div style="max-height: 100px; overflow-y:auto; padding-right: 5px;">
        <table class="table table-sm table-borderless" style="font-size: 9px; width: 100%; margin-bottom: 5px;">`;

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

    content += `</table></div>`;

    const geom = feature.geometry;
    let center = null;

    if (geom) {
        const type = geom.type;
        content += `<hr style="margin: 5px 0;"><table class="table table-sm table-borderless" style="font-size: 9px; width: 100%; margin-bottom: 5px;">`;
        content += `<tr><td class="fw-medium">Geometry</td><td>${type}</td></tr>`;

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
        <div class="d-flex justify-content-between">
            <button class="btn text-white btn-sm btn-warning zoomToBtn" data-lat="${lat}" data-lng="${lng}" style="font-size: 10px; padding: 4px 8px;">Zoom To</button>
            <a href="${urlPath}/${id}" class="btn text-white btn-sm btn-warning" style="font-size: 10px; padding: 4px 8px;">Lihat Detail</a>
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
async function initMap() {
    // Tampilkan loading di sidebar layer
    const layerListContainer = document.getElementById("layer-list");
    if (layerListContainer) {
        layerListContainer.innerHTML = `<div id="layer-loading" style="display:flex;align-items:center;justify-content:center;height:120px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
    }
    try {
        // tampilkan loading toast (persistent)
        const loadingToast = showAlert(
            "Memuat daftar kategori...",
            "info",
            true
        );

        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);
        const dataType = tipeLayer.type;
        const subType = tipeLayer.sub_type || null;
        const year = tipeLayer.year || null;

        let queryString = "?metadata_only=true";
        if (dataType) queryString += `&type=${dataType}`;
        if (subType) queryString += `&sub_type=${subType}`;
        if (year) queryString += `&year=${year}`;

        if (!geoJsonData?.features?.length) {
            // Tampilkan pesan di sidebar layer jika data kosong
            const layerListContainer = document.getElementById("layer-list");
            if (layerListContainer) {
                layerListContainer.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center" style="height:120px;">
                    <i class="bi bi-exclamation-circle text-warning" style="font-size:2rem;"></i>
                    <span class="mt-2 text-muted">Data peta belum tersedia.</span>
                </div>`;
            }
            showAlert("Data GeoJSON kosong", "warning");
            return;
        }

        const data = await response.json();

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
                                layerGroups[root.nama][childL2.nama][childL3.nama] = L.layerGroup();
                            });
                        } else {
                            // No third level, use second level as leaf
                            layerGroups[root.nama][childL2.nama][childL2.nama] = L.layerGroup();
                        }
                    });
                } else {
                    // No second level, use root as both second and third
                    layerGroups[root.nama][root.nama] = {};
                    layerGroups[root.nama][root.nama][root.nama] = L.layerGroup();
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
                                layerGroups[rootName][childL2.nama][childL3.nama] = L.layerGroup();
                            });
                        } else {
                            layerGroups[rootName][childL2.nama][childL2.nama] = L.layerGroup();
                        }
                    });
                } else {
                    layerGroups[rootName][rootName] = {};
                    layerGroups[rootName][rootName][rootName] = L.layerGroup();
                }
            });
        }

        updateLayerList();
        generateLegend();

        // Tutup loading toast manual
        if (loadingToast) hideToast(loadingToast);

        // Tampilkan pesan sukses
        showAlert(
            "Kategori berhasil dimuat. Pilih layer untuk memuat data.",
            "success"
        );
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

        // Show loading overlay
        showLoadingOverlay(categoryName);
        updateCheckboxLoadingState(categoryName, true);

        loadingToast = showAlert(
            `Memuat data untuk ${categoryName}...`,
            "info",
            true
        );

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

        targetLayer.clearLayers();

        let offset = 0;
        let totalLoaded = 0;
        let hasMore = true;
        const maxRecords = 3000;
        const chunkSize = 500;
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
                    if (mapDataStore && cacheKey && geoJsonData?.features?.length) {
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
                    const catObj = geoJsonData.all_categories?.find((c) => c.nama === categoryName);
                    if (catObj?.is_marker && catObj.icon) {
                        markerOptions = L.ExtraMarkers.icon({
                            icon: catObj.icon,
                            prefix: "fa",
                            svg: true,
                            markerColor: catObj.warna || "blue",
                            iconColor: "white",
                            shape: "circle",
                            html: `<i class='fa ${catObj.icon}' style='color:white; background: blue;'></i>`,
                        });
                    }
                }

                // Add features to layer with error handling
                let featuresAdded = 0;
                geoJsonData.features.forEach((feature, index) => {
                    try {
                        if (!feature || !feature.geometry) {
                            return;
                        }

                        L.geoJSON(feature, {
                            pointToLayer: (feature, latlng) =>
                                markerOptions
                                    ? L.marker(latlng, { icon: markerOptions })
                                    : L.marker(latlng),
                            style: getStyleForCategory(categoryName),
                            onEachFeature: (f, l) => {
                                try {
                                    bindPopupContent(f, l, urlPath);
                                } catch (popupError) {
                                    // Silently handle popup binding errors
                                }
                            },
                        }).addTo(targetLayer);

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

        updateLayerList();
        // Sembunyikan loading setelah selesai
        const loadingDiv = document.getElementById("layer-loading");
        if (loadingDiv) loadingDiv.remove();
        generateLegend();
    } catch (error) {
        console.error("Error:", error);
        // Tampilkan pesan error di sidebar layer
        const layerListContainer = document.getElementById("layer-list");
        if (layerListContainer) {
            layerListContainer.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center" style="height:120px;">
                <i class="bi bi-x-circle text-danger" style="font-size:2rem;"></i>
                <span class="mt-2 text-danger">Terjadi kesalahan saat memuat data peta.</span>
            </div>`;
        }
        showAlert("Gagal memuat data peta", "danger");
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
        rootHeader.className = 
            "flex items-center justify-between px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200";

        const rootLeftSection = document.createElement("div");
        rootLeftSection.className = "flex items-center";

        // Root toggle icon
        const rootToggleBtn = document.createElement("span");
        rootToggleBtn.className = "mr-2 transition-transform duration-300 ease-in-out";
        rootToggleBtn.innerHTML = `<i class="bi bi-chevron-right text-gray-600"></i>`;

        // Root label - directly in the left section without a checkbox
        const rootLabel = document.createElement("div");
        rootLabel.className = "font-semibold text-gray-900 text-sm";
        rootLabel.textContent = rootName;

        // Count badge for root
        const secondLevelCount = Object.keys(secondLevel).length;
        const rootBadge = document.createElement("span");
        rootBadge.className = "ml-2 px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full";
        rootBadge.textContent = secondLevelCount;

        // Add root elements to header
        rootLeftSection.appendChild(rootToggleBtn);
        rootLeftSection.appendChild(rootLabel);
        rootLeftSection.appendChild(rootBadge);
        rootHeader.appendChild(rootLeftSection);
        rootWrapper.appendChild(rootHeader);

        // Create container for second level items
        const secondLevelContainer = document.createElement("div");
        secondLevelContainer.className = "border-l border-r border-b border-gray-300 rounded-b-lg bg-gray-50 rounded-lg hidden";
        secondLevelContainer.id = `${rootId}-children`;
        
        // Process each second level category (Level 2)
        Object.entries(secondLevel).forEach(([secondName, thirdLevel]) => {
            const secondId = `second-${rootName}-${secondName}`.replace(/\s+/g, "-");
            const secondItemRow = document.createElement("div");
            secondItemRow.className = "px-2 py-1";
            
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
            secondLabel.className = "text-sm text-gray-700 cursor-pointer";
            secondLabel.htmlFor = secondId;
            secondLabel.textContent = secondName;
            
            // Count badge for second level
            const thirdLevelCount = Object.keys(thirdLevel).length;
            const secondBadge = document.createElement("span");
            secondBadge.className = "ml-2 px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full";
            secondBadge.textContent = thirdLevelCount;
            
            // Add second level elements to header
            secondLeftSection.appendChild(secondToggleBtn);
            secondLeftSection.appendChild(secondCheckbox);
            secondLeftSection.appendChild(secondLabel);
            secondLeftSection.appendChild(secondBadge);
            secondHeader.appendChild(secondLeftSection);
            secondItemRow.appendChild(secondHeader);
            
            // Create container for third level items
            const thirdLevelContainer = document.createElement("div");
            thirdLevelContainer.className = "pl-4 ml-5 border-l border-gray-200 mt-1 hidden";
            thirdLevelContainer.id = `${secondId}-children`;
            
            // Second level checkbox controls all children
            secondCheckbox.addEventListener("change", async () => {
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
                    
                    generateLegend();
                } finally {
                    secondCheckbox.disabled = false;
                    secondCheckbox.className = secondCheckbox.className.replace(" opacity-50 cursor-not-allowed", "");
                }
            });
            
            // Process third level categories (Level 3)
            Object.keys(thirdLevel).forEach((thirdName) => {
                // Skip if same name as parent (used as placeholder)
                if (thirdName === secondName && Object.keys(thirdLevel).length > 1) return;
                
                const thirdId = `third-${rootName}-${secondName}-${thirdName}`.replace(/\s+/g, "-");
                const thirdRow = document.createElement("div");
                thirdRow.className = "flex items-center py-2 hover:bg-gray-100 transition-colors duration-150 rounded px-2 mt-1"; // Added vertical spacing
                
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
                thirdLabel.className = "text-xs text-gray-600 cursor-pointer flex-1 leading-tight";
                thirdLabel.htmlFor = thirdId;
                thirdLabel.textContent = thirdName;
                
                // Add third level elements
                thirdRow.appendChild(thirdCheckbox);
                thirdRow.appendChild(thirdLabel);
                thirdLevelContainer.appendChild(thirdRow);
                
                // Third level checkbox handler
                thirdCheckbox.addEventListener("change", async () => {
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
                        
                        // Update legend
                        generateLegend();
                    } finally {
                        thirdCheckbox.disabled = false;
                        thirdCheckbox.className = thirdCheckbox.className.replace(" opacity-50 cursor-not-allowed", "");
                    }
                });
            });
            
            // Toggle functionality for second level
            secondHeader.addEventListener("click", (e) => {
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
        
        // Toggle functionality for root level - modified to not reference checkbox
        rootHeader.addEventListener("click", () => {
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
    const transparencySlider = document.getElementById("transparency");
    if (transparencySlider) {
        // Add Tailwind classes to slider if not already present
        if (!transparencySlider.classList.contains("range")) {
            transparencySlider.className = "range range-primary w-full";
        }

        transparencySlider.addEventListener("input", (e) => {
            const val = e.target.value / 100;
            Object.values(layerGroups).forEach((secondLevel) => {
                Object.values(secondLevel).forEach((thirdLevel) => {
                    Object.values(thirdLevel).forEach((layerGroup) => {
                        if (layerGroup.eachLayer) {
                            layerGroup.eachLayer((layer) => {
                                if (layer.setStyle) {
                                    layer.setStyle({
                                        fillOpacity: val,
                                        opacity: val,
                                    });
                                }
                            });
                        }
                    });
                });
            });
        });
    }

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

    // Init map
    changeBaseMap("osm");
    setupUI();

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
        // Load categories metadata - ini akan build layerGroups dan UI
        await loadCategoriesMetadata();

        // Remove spinner
        document.getElementById("layer-loading")?.remove();

        // Tunggu sebentar agar UI benar-benar selesai di-render
        setTimeout(async () => {
            // Auto-click checkbox untuk kategori yang dipilih dari session
            await autoClickCategoryFromSession();
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
    let searchTimeout;
    layerSearchInput?.addEventListener("input", (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const term = e.target.value.toLowerCase();
            
            // In 3-level hierarchy, we need to handle all levels
            const rootWrappers = document.querySelectorAll("#layer-list > .mb-3");
            
            rootWrappers.forEach((rootWrapper) => {
                const rootHeader = rootWrapper.querySelector(".flex.items-center.justify-between");
                const rootLabel = rootHeader?.querySelector("label");
                const rootLabelText = rootLabel?.textContent.toLowerCase() || "";
                
                const secondLevelItems = rootWrapper.querySelectorAll('[data-level="2"]');
                const thirdLevelItems = rootWrapper.querySelectorAll('[data-level="3"]');
                
                // Check if root matches
                const matchesRoot = rootLabelText.includes(term);
                
                // Check if any second level matches
                const matchesSecondLevel = Array.from(secondLevelItems).some(item => {
                    const label = document.querySelector(`label[for="${item.id}"]`);
                    return label && label.textContent.toLowerCase().includes(term);
                });
                
                // Check if any third level matches
                const matchesThirdLevel = Array.from(thirdLevelItems).some(item => {
                    const label = document.querySelector(`label[for="${item.id}"]`);
                    return label && label.textContent.toLowerCase().includes(term);
                });
                
                // Show or hide based on matches
                const shouldShow = matchesRoot || matchesSecondLevel || matchesThirdLevel || term === "";
                rootWrapper.style.display = shouldShow ? "block" : "none";
                
                // If showing and term is not empty, expand to show matches
                if (shouldShow && term !== "") {
                    // Expand root level
                    const rootContainer = rootWrapper.querySelector(`.border-l.border-r.border-b`);
                    if (rootContainer && rootContainer.classList.contains("hidden")) {
                        rootHeader.click();
                    }
                    
                    // Expand second level if third level matches
                    if (matchesThirdLevel) {
                        thirdLevelItems.forEach(item => {
                            if (document.querySelector(`label[for="${item.id}"]`)?.textContent.toLowerCase().includes(term)) {
                                // Find and expand parent
                                const parentId = item.getAttribute('data-parent');
                                const grandparentId = item.getAttribute('data-grandparent');
                                if (parentId && grandparentId) {
                                    const secondId = `second-${grandparentId}-${parentId}`.replace(/\s+/g, "-");
                                    const secondHeader = document.querySelector(`#${secondId}`)?.closest('.flex.items-center.justify-between');
                                    const secondContainer = document.getElementById(`${secondId}-children`);
                                    
                                    if (secondHeader && secondContainer && secondContainer.classList.contains('hidden')) {
                                        secondHeader.click();
                                    }
                                }
                            }
                        });
                    }
                }
            });
        }, 300);
    });
});

