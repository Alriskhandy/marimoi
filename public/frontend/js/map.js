// map-app.js - Enhanced version with comprehensive loading effects
/**
 * map-app.js - Enhanced version with comprehensive loading effects
 * Entry point utama aplikasi peta frontend dengan loading data yang efisien dan visual loading indicators.
 */
console.log("map-app.js loaded - enhanced version with loading effects");

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

let layerGroups = {};
let currentBaseMap = null;
let kategoriWarnaMap = {};
let iconMap = {};
let loadedCategories = new Set(); // Track loaded categories
let isLoadingData = false; // Prevent concurrent loading
let currentLoadingCategory = null; // Track current loading category
let loadingProgressInterval = null; // For animated progress

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
    console.log(`${type}: ${message}`);
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

    // BACKUP
    // Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
    //     Object.keys(sublayers).forEach((sub) => {
    //         if (added.has(sub)) return;

    //         let icon = iconMap[sub] || null;
    //         let color =
    //             kategoriWarnaMap[sub] || kategoriWarnaMap[kategori] || "#ccc";

    //         if (icon) {
    //             legendContainer.innerHTML += `
    //                 <div class="inline-flex align-items-center mb-2">
    //                     <div class="custom-fa-icon inline-flex items-center justify-center" style="width: 14px; height: 14px; background: transparent; border: none; margin-right: 8px;">
    //                         <i class="${icon} text-[${color}]" style="font-size: 12px; color: ${color}; line-height: 1;"></i>
    //                     </div>
    //                     <span style="font-size: 0.85rem;">${sub}</span>
    //                 </div>
    //             `;
    //         } else {
    //             legendContainer.innerHTML += `
    //                 <div class="inline-flex items-center mb-2">
    //                     <div style="width: 14px; height: 14px; background-color: ${color}; border: 1px solid #333;   margin-right: 8px;"></div>
    //                     <span style="font-size: 0.85rem;">${sub}</span>
    //                 </div>
    //             `;
    //         }
    //         added.add(sub);
    //     });
    // });

    // BARU
    // Ambil layer yang sedang aktif
    const activeLayers = new Set();

    // Cek layer yang di tambahkan ke peta
    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        Object.entries(sublayers).forEach(([subname, layer]) => {
            // Check if layer is added to map and has layers
            if (map.hasLayer(layer) && layer.getLayers().length > 0) {
                activeLayers.add(subname);
            }
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
    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        Object.keys(sublayers).forEach((sub) => {
            // Skip if not active or already added
            if (!activeLayers.has(sub) || added.has(sub)) return;

            let icon = iconMap[sub] || null;
            let color = kategoriWarnaMap[sub] || kategoriWarnaMap[kategori] || "#ccc";

            if (icon) {
                legendContainer.innerHTML += `
                    <div class="flex items-center mb-2 w-full">
                        <div class="custom-fa-icon flex-shrink-0 flex items-center justify-center" style="width: 14px; height: 14px; background: transparent; border: none; margin-right: 8px;">
                            <i class="${icon} text-[${color}]" style="font-size: 12px; color: ${color}; line-height: 1;"></i>
                        </div>
                        <span class="flex-1" style="font-size: 0.85rem;">${sub}</span>
                    </div>
                `;
            } else {
                legendContainer.innerHTML += `
                    <div class="flex items-center mb-2 w-full">
                        <div class="flex-shrink-0" style="width: 14px; height: 14px; background-color: ${color}; border: 1px solid #333; margin-right: 8px;"></div>
                        <span class="flex-1" style="font-size: 0.85rem;">${sub}</span>
                    </div>
                `;
            }
            added.add(sub);
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

    const allowedKeys = ["KEGIATAN", "TAHUN", "KABUPATEN", "URUSAN"];
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
 * Load only categories metadata without spatial data
 */
async function loadCategoriesMetadata() {
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

        console.log("Requesting metadata:", `/geojson${queryString}`);

        const response = await fetch(`/geojson${queryString}`);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
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

        // Initialize empty layer structure
        layerGroups = {};

        if (data.all_categories?.length) {
            const parents = data.all_categories.filter((cat) => !cat.parent_id);
            const children = data.all_categories.filter((cat) => cat.parent_id);

            parents.forEach((parent) => {
                layerGroups[parent.nama] = {};
                const anak = children.filter(
                    (child) => child.parent_id === parent.id
                );

                if (anak.length > 0) {
                    anak.forEach((child) => {
                        layerGroups[parent.nama][child.nama] = L.layerGroup();
                    });
                } else {
                    layerGroups[parent.nama][parent.nama] = L.layerGroup();
                }
            });
        } else if (data.root_categories) {
            data.root_categories.forEach((cat) => {
                const kategori = cat.nama;
                layerGroups[kategori] = {};

                if (Array.isArray(cat.children) && cat.children.length > 0) {
                    cat.children.forEach((sub) => {
                        layerGroups[kategori][sub.nama] = L.layerGroup();
                    });
                } else {
                    layerGroups[kategori][kategori] = L.layerGroup();
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
 * Load spatial data for specific category on-demand with pagination (max 3000 records)
 */
async function loadCategoryData(categoryName) {
    if (isLoadingData || loadedCategories.has(categoryName)) {
        return;
    }

    let loadingToast = null;

    try {
        isLoadingData = true;

        // Show loading overlay
        showLoadingOverlay(categoryName);

        // Update checkbox to show loading state
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
        for (const [parentName, children] of Object.entries(layerGroups)) {
            if (children[categoryName]) {
                targetLayer = children[categoryName];
                break;
            }
        }

        if (!targetLayer && layerGroups[categoryName]?.[categoryName]) {
            targetLayer = layerGroups[categoryName][categoryName];
        }

        if (!targetLayer) {
            console.warn(`No target layer found for category: ${categoryName}`);
            return;
        }

        // Clear existing data in layer
        targetLayer.clearLayers();

        let offset = 0;
        let totalLoaded = 0;
        let hasMore = true;
        const maxRecords = 3000; // Maximum records to load
        const chunkSize = 500; // Records per request
        let estimatedTotal = maxRecords; // Initial estimate

        // Load data in chunks with pagination
        while (hasMore && totalLoaded < maxRecords) {
            try {
                let queryString = "?";
                if (dataType)
                    queryString += `type=${encodeURIComponent(dataType)}`;
                if (subType)
                    queryString += `&sub_type=${encodeURIComponent(subType)}`;
                if (year) queryString += `&year=${encodeURIComponent(year)}`;
                queryString += `&kategori[]=${encodeURIComponent(
                    categoryName
                )}`;

                // Calculate remaining records to load
                const remainingRecords = maxRecords - totalLoaded;
                const currentChunkSize = Math.min(chunkSize, remainingRecords);

                queryString += `&limit=${currentChunkSize}&offset=${offset}`;

                console.log(`Loading Layer: ${queryString}`);

                // Update loading progress
                updateLoadingProgress(
                    totalLoaded,
                    estimatedTotal,
                    `Memuat Layer ${Math.floor(offset / chunkSize) + 1}...`
                );

                const response = await fetch(`/geojson${queryString}`);

                if (!response.ok) {
                    // Enhanced error handling
                    let errorDetails = `HTTP ${response.status}: ${response.statusText}`;
                    try {
                        const errorData = await response.json();
                        if (errorData.message) {
                            errorDetails += ` - ${errorData.message}`;
                        }
                        if (
                            errorData.details &&
                            errorData.details !== errorData.message
                        ) {
                            errorDetails += ` (${errorData.details})`;
                        }
                        console.error("Server error response:", errorData);
                    } catch (e) {
                        try {
                            const errorText = await response.text();
                            if (errorText && errorText.length > 0) {
                                errorDetails += ` - ${errorText.substring(
                                    0,
                                    200
                                )}${errorText.length > 200 ? "..." : ""}`;
                            }
                            console.error("Server error text:", errorText);
                        } catch (e2) {
                            console.error("Could not parse error response");
                        }
                    }
                    throw new Error(errorDetails);
                }

                const geoJsonData = await response.json();

                // Check if we got any features
                if (!geoJsonData?.features?.length) {
                    console.log("No more features to load");
                    break;
                }

                // Update estimate if we have metadata
                if (geoJsonData.meta?.total_features && offset === 0) {
                    estimatedTotal = Math.min(
                        geoJsonData.meta.total_features,
                        maxRecords
                    );
                }

                // Determine marker options (only need to do this once)
                let markerOptions = null;
                if (offset === 0) {
                    const catObj = geoJsonData.all_categories?.find(
                        (c) => c.nama === categoryName
                    );
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
                        // Validate feature structure
                        if (!feature || !feature.geometry) {
                            console.warn("Skipping invalid feature:", feature);
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
                                    console.error(
                                        "Error binding popup:",
                                        popupError
                                    );
                                }
                            },
                        }).addTo(targetLayer);

                        featuresAdded++;

                        // Update progress periodically during feature loading
                        if (index % 50 === 0) {
                            updateLoadingProgress(
                                totalLoaded + featuresAdded,
                                estimatedTotal,
                                `Memproses fitur ${
                                    totalLoaded + featuresAdded
                                }...`
                            );
                        }
                    } catch (featureError) {
                        console.error(
                            `Error adding feature to map:`,
                            featureError,
                            feature
                        );
                    }
                });

                totalLoaded += featuresAdded;

                // Update progress after chunk completion
                updateLoadingProgress(
                    totalLoaded,
                    estimatedTotal,
                    totalLoaded >= maxRecords
                        ? `${totalLoaded} fitur dimuat (maksimum tercapai)`
                        : `${totalLoaded} fitur dimuat...`
                );

                // Check if we have more data and haven't reached the limit
                const serverHasMore = geoJsonData.meta?.has_more === true;
                hasMore =
                    serverHasMore &&
                    totalLoaded < maxRecords &&
                    featuresAdded > 0;
                offset += chunkSize;

                // Add small delay to prevent overwhelming the server and show progress
                if (hasMore) {
                    await new Promise((resolve) => setTimeout(resolve, 100));
                }
            } catch (chunkError) {
                console.error(
                    `Error loading layer at offset ${offset}:`,
                    chunkError
                );

                // If this is the first chunk, re-throw the error
                if (offset === 0) {
                    throw chunkError;
                }

                // For subsequent chunks, just log and break
                updateLoadingProgress(
                    totalLoaded,
                    estimatedTotal,
                    `Error pada Layer ${Math.floor(offset / chunkSize)}: ${
                        chunkError.message
                    }`
                );
                await new Promise((resolve) => setTimeout(resolve, 1000)); // Show error for 1 second
                break;
            }
        }

        loadedCategories.add(categoryName);

        // Final progress update
        updateLoadingProgress(totalLoaded, totalLoaded, "Selesai!");

        // Wait a moment to show completion
        await new Promise((resolve) => setTimeout(resolve, 500));

        // Hide loading states
        hideLoadingOverlay();
        updateCheckboxLoadingState(categoryName, false);

        // Hide loading toast safely
        if (loadingToast) {
            hideToast(loadingToast);
        }

        // Final success message
        let finalMessage;
        if (totalLoaded >= maxRecords) {
            finalMessage = `Data ${categoryName} berhasil dimuat (${totalLoaded} fitur - maksimum tercapai)`;
        } else {
            finalMessage = `Data ${categoryName} berhasil dimuat (${totalLoaded} fitur)`;
        }

        showAlert(finalMessage, "success");

        // Log performance info
        console.log(
            `Loaded ${totalLoaded} features for category "${categoryName}"`
        );
    } catch (error) {
        console.error(
            `Error loading data for category ${categoryName}:`,
            error
        );

        // Hide loading states
        hideLoadingOverlay();
        updateCheckboxLoadingState(categoryName, false);

        // Hide loading toast safely
        if (loadingToast) {
            hideToast(loadingToast);
        }

        // Show detailed error in alert
        let errorMessage = `Gagal memuat data ${categoryName}`;

        // Provide more specific error messages
        if (error.message.includes("500")) {
            errorMessage +=
                ": Server mengalami masalah internal. Coba lagi nanti.";
        } else if (error.message.includes("404")) {
            errorMessage += ": Data tidak ditemukan.";
        } else if (error.message.includes("timeout")) {
            errorMessage += ": Koneksi timeout. Periksa koneksi internet Anda.";
        } else {
            errorMessage += `: ${error.message}`;
        }

        // Log full error stack for debugging
        console.error("Full error details:", {
            name: error.name,
            message: error.message,
            stack: error.stack,
            categoryName: categoryName,
        });

        showAlert(errorMessage, "danger");

        // Remove category from loaded set so user can retry
        loadedCategories.delete(categoryName);
    } finally {
        isLoadingData = false;
    }
}

/**
 * Enhanced updateLayerList with on-demand loading - Tailwind version
 */
function updateLayerList() {
    const container = document.getElementById("layer-list");
    if (!container) return;

    container.innerHTML = "";

    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        const groupId = `group-${kategori.replace(/\s+/g, "-")}`;
        const groupWrapper = document.createElement("div");
        groupWrapper.className = "mb-3";

        const rootId = `root-${kategori.replace(/\s+/g, "-")}`;

        // Create header with Tailwind classes
        const header = document.createElement("div");
        header.className =
            "flex items-center justify-between px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200";

        const leftSection = document.createElement("div");
        leftSection.className = "flex items-center";

        // Toggle icon with Tailwind
        const toggleBtn = document.createElement("span");
        toggleBtn.className =
            "mr-2 transition-transform duration-300 ease-in-out";
        toggleBtn.innerHTML = `<i class="bi bi-chevron-right text-gray-600"></i>`;

        // Parent checkbox with Tailwind
        const checkboxRoot = document.createElement("input");
        checkboxRoot.type = "checkbox";
        checkboxRoot.className =
            "mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-2 border-gray-400 rounded";
        checkboxRoot.id = rootId;

        // Parent label with Tailwind
        const labelRoot = document.createElement("label");
        labelRoot.className =
            "font-semibold text-gray-900 text-sm cursor-pointer";
        labelRoot.htmlFor = rootId;
        labelRoot.textContent = kategori;

        // Count badge with Tailwind
        const subCount = Object.keys(sublayers).length;
        const badge = document.createElement("span");
        badge.className =
            "ml-2 px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full";
        badge.textContent = subCount;

        // Parent checkbox controls all children with on-demand loading
        checkboxRoot.addEventListener("change", async () => {
            const isChecked = checkboxRoot.checked;

            // Disable parent checkbox during loading
            checkboxRoot.disabled = true;
            checkboxRoot.className =
                checkboxRoot.className + " opacity-50 cursor-not-allowed";

            try {
                for (const [subname, layer] of Object.entries(sublayers)) {
                    const subId = `sub-${kategori}-${subname}`.replace(
                        /\s+/g,
                        "-"
                    );
                    const checkbox = document.getElementById(subId);

                    if (checkbox) {
                        checkbox.checked = isChecked;

                        if (isChecked) {
                            // Load data on-demand if not loaded yet
                            await loadCategoryData(subname);
                            map.addLayer(layer);
                        } else {
                            map.removeLayer(layer);
                        }
                    }
                }

                generateLegend();
            } finally {
                checkboxRoot.disabled = false;
                checkboxRoot.className = checkboxRoot.className.replace(
                    " opacity-50 cursor-not-allowed",
                    ""
                );
            }
        });

        leftSection.appendChild(toggleBtn);
        leftSection.appendChild(checkboxRoot);
        leftSection.appendChild(labelRoot);
        leftSection.appendChild(badge);
        header.appendChild(leftSection);
        groupWrapper.appendChild(header);

        // Create sublayers container with Tailwind
        const subLayerList = document.createElement("div");
        subLayerList.id = groupId;
        subLayerList.className =
            "border-l border-r border-b border-gray-300 rounded-b-lg bg-gray-50 rounded-lg hidden";

        // Add sublayers
        Object.entries(sublayers).forEach(([subname, layer]) => {
            // Skip if same name as parent and has multiple children
            const hasChildren = Object.keys(sublayers).length > 1;
            if (subname === kategori && hasChildren) return;

            const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
            const row = document.createElement("div");
            row.className =
                "flex items-center px-4 py-2 hover:bg-gray-100 transition-colors duration-150";

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.className =
                "mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-2 border-gray-400 rounded";
            checkbox.id = subId;

            checkbox.addEventListener("change", async () => {
                // Disable checkbox during loading with Tailwind
                checkbox.disabled = true;
                checkbox.className =
                    checkbox.className + " opacity-50 cursor-not-allowed";

                try {
                    if (checkbox.checked) {
                        // Load data on-demand if not loaded yet
                        await loadCategoryData(subname);
                        map.addLayer(layer);
                    } else {
                        map.removeLayer(layer);
                    }

                    // Update parent state
                    const allSubs = Array.from(
                        subLayerList.querySelectorAll('input[type="checkbox"]')
                    );
                    const checkedCount = allSubs.filter(
                        (cb) => cb.checked
                    ).length;

                    if (checkedCount === 0) {
                        checkboxRoot.checked = false;
                        checkboxRoot.indeterminate = false;
                    } else if (checkedCount === allSubs.length) {
                        checkboxRoot.checked = true;
                        checkboxRoot.indeterminate = false;
                    } else {
                        checkboxRoot.checked = false;
                        checkboxRoot.indeterminate = true;
                    }

                    // Update legend after individual layer change
                    generateLegend();
                } finally {
                    checkbox.disabled = false;
                    checkbox.className = checkbox.className.replace(
                        " opacity-50 cursor-not-allowed",
                        ""
                    );
                }
            });

            const label = document.createElement("label");
            label.className =
                "text-sm text-gray-700 cursor-pointer flex-1 leading-tight";
            label.htmlFor = subId;
            label.textContent = subname;

            row.appendChild(checkbox);
            row.appendChild(label);
            subLayerList.appendChild(row);
        });

        // Toggle functionality with Tailwind
        const toggleDropdown = () => {
            const isVisible = !subLayerList.classList.contains("hidden");

            if (isVisible) {
                subLayerList.classList.add("hidden");
                toggleBtn.innerHTML = `<i class="bi bi-chevron-right text-gray-600"></i>`;
                toggleBtn.classList.remove("rotate-90");
            } else {
                subLayerList.classList.remove("hidden");
                toggleBtn.innerHTML = `<i class="bi bi-chevron-down text-gray-600"></i>`;
                toggleBtn.classList.add("rotate-90");
            }
        };

        header.addEventListener("click", (e) => {
            if (e.target !== checkboxRoot && e.target !== labelRoot) {
                toggleDropdown();
            }
        });

        groupWrapper.appendChild(subLayerList);
        container.appendChild(groupWrapper);
    });
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
            Object.values(layerGroups).forEach((group) => {
                Object.values(group).forEach((layerGroup) => {
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
            if (i === 4) radioInput.checked = true;

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
 * Entry point aplikasi frontend peta dengan optimized loading.
 */
document.addEventListener("DOMContentLoaded", async () => {
    // Init map
    changeBaseMap("esri-world-imagery");
    setupUI();

    // Show loading spinner
    const layerListContainer = document.getElementById("layer-list");
    if (layerListContainer) {
        layerListContainer.innerHTML = `
            <div id="layer-loading" class="flex items-center justify-center h-[120px]">
                <div class="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>`;
    }

    // Load metadata
    await loadCategoriesMetadata();

    // Remove spinner
    document.getElementById("layer-loading")?.remove();

    console.log("Map app initialized ✅");

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
            const groups = document.querySelectorAll(".layer-group");

            groups.forEach((group) => {
                const parent = group.querySelector(".fw-bold");
                const children = group.querySelectorAll(".bg-light label");

                const matchParent =
                    parent && parent.textContent.toLowerCase().includes(term);

                const matchChild = Array.from(children).some((label) =>
                    label.textContent.toLowerCase().includes(term)
                );

                group.style.display =
                    matchParent || matchChild || term === "" ? "block" : "none";
            });
        }, 300);
    });
});
