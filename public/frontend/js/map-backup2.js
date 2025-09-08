// map-app.js - Fixed version based on working original code
/**
 * map-app.js - Fixed version based on working original code
 * Entry point utama aplikasi peta frontend.
 */
console.log("map-app.js loaded");

/**
 * Konfigurasi utama peta, termasuk daftar basemap, center, zoom, dan style default.
 */
const mapConfig = {
    weight: 6,
    center: [0.735485, 128.028201],
    zoom: 7,
    baseMapsList: [
        {
            id: "osm",
            label: "OpenStreetMap",
            url: "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
            maxZoom: 19,
        },
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
        {
            id: "esri-world-imagery",
            label: "ESRI World Imagery",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 18,
        },
        {
            id: "esri-dark-gray",
            label: "ESRI Dark Gray Canvas",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}",
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

/**
 * Menghasilkan style untuk kategori tertentu.
 * Digunakan untuk styling fitur non-marker (polygon/line).
 * @param {string} kategori - Nama kategori
 * @returns {object} Style Leaflet
 */
function getStyleForCategory(kategori) {
    const warna = kategoriWarnaMap[kategori] || "#ECE6D6";
    return {
        color: warna,
        weight: 5,
        fillColor: warna,
        fillOpacity: 0.4,
        opacity: 1,
    };
}

/**
 * Membuat dan menampilkan legend pada UI berdasarkan kategori dan icon/warna.
 * Hanya menampilkan kategori yang memiliki data dan icon jika marker.
 */
function generateLegend() {
    const legendContainer = document.getElementById("legend-content");
    if (!legendContainer) return;

    legendContainer.innerHTML = "";
    const added = new Set();

    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        Object.keys(sublayers).forEach((sub) => {
            if (added.has(sub)) return;

            // Cek apakah sub adalah marker (point) dan punya icon
            let icon = iconMap[sub] || null;
            let color =
                kategoriWarnaMap[sub] || kategoriWarnaMap[kategori] || "#ccc";

            // Jika marker, gunakan icon dan warna kategori
            if (icon) {
                legendContainer.innerHTML += `
                    <div class="d-flex align-items-center mb-2">
                        <div class="custom-fa-icon d-flex align-items-center justify-content-center" style="width: 14px; height: 14px; background: transparent; border: none; margin-right: 8px;">
                            <i class="${icon}" style="font-size: 12px; color: ${color}; line-height: 1;"></i>
                        </div>
                        <span style="font-size: 0.85rem;">${sub}</span>
                    </div>
                `;
            } else {
                legendContainer.innerHTML += `
                    <div class="d-flex align-items-center mb-2">
                        <div style="width: 14px; height: 14px; background-color: ${color}; border: 1px solid #333; margin-right: 8px;"></div>
                        <span style="font-size: 0.85rem;">${sub}</span>
                    </div>
                `;
            }
            added.add(sub);
        });
    });
}

/**
 * Membuat dan mengikat konten popup pada setiap fitur peta.
 * Menampilkan info properti, gambar, tombol zoom dan tombol detail.
 * @param {object} feature - GeoJSON feature
 * @param {object} layer - Leaflet layer
 * @param {string} urlPath - Path untuk link detail
 */
function bindPopupContent(feature, layer, urlPath) {
    const props = feature.properties;
    let content = `<div class="py-1" style="max-width: 230px; font-size: 12px;"><h5 class="fw-bold text-primary" style="font-size: 12px; margin-bottom: 5px;">${
        props.kategori || "Feature"
    }</h5>`;

    if (props.gambar) {
        content += `<img src="${props.gambar}" alt="Gambar ${props.KEGIATAN}" style="width: 100%; max-height: 120px; object-fit: cover; margin-bottom: 5px; border: 1.5px solid #ccc;">`;
    };
    content += `<hr style="margin: 5px 0;"><div style="max-height: 150px; overflow-y:auto; padding-right: 5px;">
        <table class="table table-sm table-borderless" style="font-size: 9px; width: 100%; margin-bottom: 5px;">`;
    Object.entries(props).forEach(([key, value]) => {
        const allowedKeys = ["KEGIATAN", "TAHUN", "KABUPATEN", "URUSAN"];

        if (allowedKeys.includes(key.toUpperCase()) && value) {
            const label = key
                .replace(/_/g, " ")
                .replace(/\b\w/g, (l) => l.toUpperCase());
            content += `<tr><td class="fw-medium">${label}</td><td>${value}</td></tr>`;
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
            content += `<tr><td class="fw-medium">Panjang</td><td>${length.toFixed(
                2
            )} km</td></tr>`;
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
            content += `<tr><td class="fw-medium">Koordinat</td><td>${center[1].toFixed(
                5
            )}, ${center[0].toFixed(5)}</td></tr>`;
        }
        content += `</table>`;
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

    layer.bindPopup(content);

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
 * Menampilkan alert/toast pada UI dan log ke console.
 * @param {string} message - Pesan yang ditampilkan
 * @param {string} [type="info"] - Jenis alert (info, warning, danger, dll)
 */
function showAlert(message, type = "info") {
    console.log(`${type}: ${message}`);
    const toastContainer = document.getElementById("toast-container");
    if (!toastContainer) return;

    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");
    toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>`;
    toastContainer.appendChild(toast);
    new bootstrap.Toast(toast).show();
    toast.addEventListener("hidden.bs.toast", () => toast.remove());
}

/**
 * Mengganti basemap yang aktif sesuai pilihan user.
 * @param {string} baseMapId - ID basemap yang dipilih
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

// ✅ getDataType function to determine data type based on URL path
/**
 * Menentukan tipe data berdasarkan path URL.
 * @param {string} urlPath - Path URL yang digunakan
 * @returns {object} Objek dengan properti type dan sub_type
 */
function getDataType(urlPath) {
    // Default return value
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

// ✅ Modified initMap with lazy loading for proyek strategis
/**
 * Inisialisasi peta, memuat struktur layer, dan setup lazy loading untuk proyek strategis.
 * Untuk render awal hanya menampilkan tahun dan kategori, data dimuat ketika checkbox diklik.
 */
async function initMap() {
    // Tampilkan loading di sidebar layer
    const layerListContainer = document.getElementById("layer-list");
    if (layerListContainer) {
        layerListContainer.innerHTML = `<div id="layer-loading" style="display:flex;align-items:center;justify-content:center;height:120px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
    }
    
    try {
        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);

        const dataType = tipeLayer.type;
        const subType = tipeLayer.sub_type || null;
        const year = tipeLayer.year || null;

        // Build query string for API call
        let queryString = "?";
        if (dataType) queryString += `type=${dataType}`;
        if (subType) queryString += `&sub_type=${subType}`;
        if (year) queryString += `&year=${year}`;

        // 🔸 Deteksi apakah ini adalah proyek strategis berdasarkan dataType atau URL
        const isProyekStrategis = dataType === 'proyek_strategis' || 
                                 urlPath.includes('proyek-strategis') || 
                                 urlPath.includes('strategis');

        if (isProyekStrategis) {
            // Untuk proyek strategis: muat struktur saja, data dimuat secara lazy
            await initProyekStrategisStructure(queryString);
        } else {
            // Untuk peta lainnya: muat semua data sekaligus (existing behavior)
            await initRegularMap(queryString, urlPath);
        }

        // Sembunyikan loading setelah selesai
        const loadingDiv = document.getElementById("layer-loading");
        if (loadingDiv) loadingDiv.remove();
        
    } catch (error) {
        console.error("Error:", error);
        // Tampilkan pesan error di sidebar layer
        const layerListContainer = document.getElementById("layer-list");
        if (layerListContainer) {
            layerListContainer.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center" style="height:120px;">
                <i class="bi bi-x-circle text-danger" style="font-size:2rem;"></i>
                <span class="mt-2 text-muted">Terjadi kesalahan saat memuat data peta.</span>
            </div>`;
        }
        showAlert("Gagal memuat data peta", "danger");
    }
}

/**
 * Inisialisasi struktur untuk proyek strategis dengan lazy loading
 */
async function initProyekStrategisStructure(queryString) {
    try {
        // Muat struktur kategori saja (tanpa features)
        const response = await fetch(`/geojson${queryString}`);
        const structureData = await response.json();

        console.log("Structure Data:", structureData);

        if (!structureData?.structure) {
            showAlert("Struktur data tidak tersedia", "warning");
            return;
        }

        // Reset layerGroups
        layerGroups = {};
        kategoriWarnaMap = {};
        iconMap = {};

        // Build kategoriWarnaMap dan iconMap dari struktur
        if (Array.isArray(structureData.all_categories)) {
            structureData.all_categories.forEach((cat) => {
                if (!cat.nama || !cat.warna) return;
                kategoriWarnaMap[cat.nama] = cat.warna;
                if (cat.is_marker === true && cat.icon) {
                    iconMap[cat.nama] = cat.icon;
                }
            });
        }

        // Build struktur layer dari structure data
        buildProyekStrategisStructureOnly(structureData.structure);

        updateLayerList();
        generateLegend();

    } catch (error) {
        console.error("Error loading structure:", error);
        showAlert("Gagal memuat struktur peta", "danger");
    }
}

/**
 * Inisialisasi untuk peta reguler (existing behavior)
 */
async function initRegularMap(queryString, urlPath) {
    const response = await fetch(`/geojson${queryString}`);
    const geoJsonData = await response.json();

    if (!geoJsonData?.features?.length) {
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

    // Build kategoriWarnaMap dan iconMap
    kategoriWarnaMap = {};
    iconMap = {};

    if (Array.isArray(geoJsonData.all_categories)) {
        geoJsonData.all_categories.forEach((cat) => {
            if (!cat.nama || !cat.warna) return;
            kategoriWarnaMap[cat.nama] = cat.warna;
            if (cat.is_marker === true && cat.icon) {
                iconMap[cat.nama] = cat.icon;
            }
        });
    }

    // Hapus layer lama dari peta
    Object.values(layerGroups).forEach((group) => {
        if (typeof group === 'object' && group !== null) {
            if (group.getLayers && typeof group.getLayers === 'function') {
                if (map.hasLayer(group)) {
                    map.removeLayer(group);
                }
            } else {
                Object.values(group).forEach((subGroup) => {
                    if (subGroup && subGroup.getLayers && typeof subGroup.getLayers === 'function') {
                        if (map.hasLayer(subGroup)) {
                            map.removeLayer(subGroup);
                        }
                    }
                });
            }
        }
    });

    layerGroups = {};

    // Build regular layers
    buildRegularLayers(geoJsonData);

    // Kelompokkan fitur dan process
    const kategoriFiturMap = {};
    geoJsonData.features.forEach((feature) => {
        const kategori = (feature.properties?.kategori || "").trim();
        if (!kategori) return;
        if (!kategoriFiturMap[kategori]) kategoriFiturMap[kategori] = [];
        kategoriFiturMap[kategori].push(feature);
    });

    // Process dan add features ke layer
    processRegularFeatures(kategoriFiturMap, urlPath);

    // Clean empty layers
    cleanEmptyLayers();
    updateLayerList();
    generateLegend();
}

/**
 * Membangun struktur layer untuk proyek strategis tanpa data (structure only)
 */
function buildProyekStrategisStructureOnly(structure) {
    // structure format: { "2025": { "Pembangunan RTLH": ["Pembangunan Rumah Baru", "Renovasi"] } }
    Object.entries(structure).forEach(([tahun, categories]) => {
        layerGroups[tahun] = {};
        
        Object.entries(categories).forEach(([kategori, subKategoriList]) => {
            layerGroups[tahun][kategori] = {};
            
            // Untuk saat ini, hanya buat placeholder, layer akan dibuat saat dimuat
            if (Array.isArray(subKategoriList) && subKategoriList.length > 0) {
                subKategoriList.forEach((subKategori) => {
                    layerGroups[tahun][kategori][subKategori] = null; // Placeholder
                });
            } else {
                layerGroups[tahun][kategori][kategori] = null; // Placeholder
            }
        });
    });
}

/**
 * Load data untuk kategori/sub-kategori tertentu secara lazy
 */
async function loadCategoryData(tahun, kategori, subKategori = null) {
    try {
        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);
        const dataType = tipeLayer.type;
        const subType = tipeLayer.sub_type || null;
        const year = tipeLayer.year || null;

        // Build query string dengan filter kategori
        let queryString = "?";
        if (dataType) queryString += `type=${dataType}`;
        if (subType) queryString += `&sub_type=${subType}`;
        if (year) queryString += `&year=${year}`;
        if (tahun) queryString += `&filter_year=${tahun}`;
        if (kategori) queryString += `&filter_category=${encodeURIComponent(kategori)}`;
        if (subKategori) queryString += `&filter_subcategory=${encodeURIComponent(subKategori)}`;

        const response = await fetch(`/geojson${queryString}`);
        const geoJsonData = await response.json();

        if (!geoJsonData?.features?.length) {
            console.log(`No data found for ${tahun}/${kategori}/${subKategori || 'all'}`);
            return null;
        }

        // Buat layer jika belum ada
        const layerKey = subKategori || kategori;
        if (!layerGroups[tahun][kategori][layerKey] || layerGroups[tahun][kategori][layerKey] === null) {
            layerGroups[tahun][kategori][layerKey] = L.layerGroup();
        }

        const targetLayer = layerGroups[tahun][kategori][layerKey];

        // Process features
        geoJsonData.features.forEach((feature) => {
            const props = feature.properties || {};
            const featureTahun = props.tahun || props.year || '';
            const featureKategori = (props.kategori || '').trim();
            const featureSubKategori = (props.sub_kategori || props.subcategory || '').trim();

            // Filter sesuai parameter yang diminta
            if (tahun && featureTahun !== tahun) return;
            if (kategori && featureKategori !== kategori) return;
            if (subKategori && featureSubKategori !== subKategori) return;

            // Tentukan kategori untuk styling
            const kategoriForStyle = featureSubKategori || featureKategori;

            // Tentukan markerOptions
            let markerOptions = null;
            const catObj = kategoriWarnaMap[kategoriForStyle] ? 
                          { warna: kategoriWarnaMap[kategoriForStyle], icon: iconMap[kategoriForStyle] } : 
                          null;

            if (catObj && catObj.icon) {
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

            // Add feature ke layer
            L.geoJSON(feature, {
                pointToLayer: (feature, latlng) =>
                    markerOptions
                        ? L.marker(latlng, { icon: markerOptions })
                        : L.marker(latlng),
                style: getStyleForCategory(kategoriForStyle),
                onEachFeature: (f, l) =>
                    bindPopupContent(f, l, urlPath),
            }).addTo(targetLayer);
        });

        return targetLayer;

    } catch (error) {
        console.error(`Error loading data for ${tahun}/${kategori}/${subKategori}:`, error);
        return null;
    }
}

/**
 * Process features untuk regular maps
 */
function processRegularFeatures(kategoriFiturMap, urlPath) {
    const targetLayerMap = {};
    const markerOptionsMap = {};

    Object.entries(kategoriFiturMap).forEach(([kategori, fiturList]) => {
        let targetLayer = null;

        // Cari layer yang sesuai di dalam layerGroups
        for (const [parentName, children] of Object.entries(layerGroups)) {
            if (children[kategori]) {
                targetLayer = children[kategori];
                break;
            }
        }

        if (!targetLayer && layerGroups[kategori] && layerGroups[kategori][kategori]) {
            targetLayer = layerGroups[kategori][kategori];
        }

        if (!targetLayer) {
            targetLayer = L.layerGroup();
            if (!layerGroups[kategori]) layerGroups[kategori] = {};
            layerGroups[kategori][kategori] = targetLayer;
        }

        targetLayerMap[kategori] = targetLayer;

        // Setup marker options
        let isMarker = false;
        let iconClass = null;
        let iconWarna = kategoriWarnaMap[kategori] || "blue";
        
        if (window.geoJsonData && Array.isArray(window.geoJsonData.all_categories)) {
            const catObj = window.geoJsonData.all_categories.find(c => c.nama === kategori);
            if (catObj) {
                isMarker = !!catObj.is_marker;
                iconClass = catObj.icon || null;
                iconWarna = catObj.warna || iconWarna;
            }
        }

        if (isMarker && iconClass) {
            markerOptionsMap[kategori] = L.ExtraMarkers.icon({
                icon: iconClass,
                prefix: "fa",
                svg: true,
                markerColor: iconWarna,
                iconColor: "white",
                shape: "circle",
                html: `<i class='fa ${iconClass}' style='color:white; background: blue;'></i>`,
            });
        } else {
            markerOptionsMap[kategori] = null;
        }
    });

    // Add features to layers
    Object.entries(kategoriFiturMap).forEach(([kategori, fiturList]) => {
        const targetLayer = targetLayerMap[kategori];
        const markerOptions = markerOptionsMap[kategori];
        
        if (targetLayer) {
            fiturList.forEach((feature) => {
                L.geoJSON(feature, {
                    pointToLayer: (feature, latlng) =>
                        markerOptions
                            ? L.marker(latlng, { icon: markerOptions })
                            : L.marker(latlng),
                    style: getStyleForCategory(kategori),
                    onEachFeature: (f, l) =>
                        bindPopupContent(f, l, urlPath),
                }).addTo(targetLayer);
            });
        }
    });
}

/**
 * Membangun struktur layer reguler
 */
function buildRegularLayers(geoJsonData) {
    if (geoJsonData.all_categories?.length) {
        const parents = geoJsonData.all_categories.filter(cat => !cat.parent_id);
        const children = geoJsonData.all_categories.filter(cat => cat.parent_id);

        parents.forEach((parent) => {
            layerGroups[parent.nama] = {};
            const anak = children.filter(child => child.parent_id === parent.id);

            if (anak.length > 0) {
                anak.forEach((child) => {
                    layerGroups[parent.nama][child.nama] = L.layerGroup();
                });
            } else {
                layerGroups[parent.nama][parent.nama] = L.layerGroup();
            }
        });
    } else if (geoJsonData.root_categories) {
        geoJsonData.root_categories.forEach((cat) => {
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
}

/**
 * Membersihkan layer kosong dari layerGroups
 */
function cleanEmptyLayers() {
    Object.entries(layerGroups).forEach(([level1Key, level1Value]) => {
        if (typeof level1Value === 'object' && level1Value !== null && !level1Value.getLayers) {
            Object.entries(level1Value).forEach(([level2Key, level2Value]) => {
                if (typeof level2Value === 'object' && level2Value !== null && !level2Value.getLayers) {
                    Object.entries(level2Value).forEach(([level3Key, layer]) => {
                        if (layer && layer.getLayers && layer.getLayers().length === 0) {
                            delete layerGroups[level1Key][level2Key][level3Key];
                        }
                    });
                    if (Object.keys(layerGroups[level1Key][level2Key]).length === 0) {
                        delete layerGroups[level1Key][level2Key];
                    }
                } else if (level2Value && level2Value.getLayers && level2Value.getLayers().length === 0) {
                    delete layerGroups[level1Key][level2Key];
                }
            });
            if (Object.keys(layerGroups[level1Key]).length === 0) {
                delete layerGroups[level1Key];
            }
        } else if (level1Value && level1Value.getLayers && level1Value.getLayers().length === 0) {
            delete layerGroups[level1Key];
        }
    });
}

// ✅ Enhanced updateLayerList with lazy loading support
/**
 * Membuat dan memperbarui daftar layer pada sidebar UI dengan lazy loading.
 */
function updateLayerList() {
    const container = document.getElementById("layer-list");
    if (!container) return;

    container.innerHTML = "";

    // Deteksi struktur
    const isThreeLevelStructure = Object.values(layerGroups).some(level1 => 
        typeof level1 === 'object' && level1 !== null && !level1.getLayers &&
        Object.values(level1).some(level2 => 
            typeof level2 === 'object' && level2 !== null && !level2.getLayers
        )
    );

    if (isThreeLevelStructure) {
        updateProyekStrategisLayerList(container);
    } else {
        updateRegularLayerList(container);
    }
}

/**
 * Update layer list untuk struktur 3 level (proyek strategis) dengan lazy loading
 */
function updateProyekStrategisLayerList(container) {
    Object.entries(layerGroups).forEach(([tahun, categories]) => {
        const yearWrapper = document.createElement("div");
        yearWrapper.classList.add("layer-group", "mb-3");
        
        // Year header (tanpa checkbox, hanya toggle)
        const yearHeader = createYearHeader(tahun, Object.keys(categories).length);
        yearWrapper.appendChild(yearHeader);
        
        // Year content container
        const yearContent = document.createElement("div");
        yearContent.id = `year-${tahun.replace(/\s+/g, "-")}`;
        yearContent.className = "border border-top-0 rounded-bottom bg-light";
        yearContent.style.display = "none";
        
        Object.entries(categories).forEach(([kategori, subCategories]) => {
            const categoryWrapper = document.createElement("div");
            categoryWrapper.classList.add("ms-3", "mb-2");
            
            // Category header dengan checkbox dan lazy loading
            const categoryHeader = createCategoryHeader(kategori, Object.keys(subCategories).length, tahun);
            categoryWrapper.appendChild(categoryHeader);
            
            // Category content container (akan dimuat saat diperlukan)
            const categoryContent = document.createElement("div");
            categoryContent.id = `cat-${tahun}-${kategori}`.replace(/\s+/g, "-");
            categoryContent.className = "border border-top-0 rounded-bottom bg-white ms-2";
            categoryContent.style.display = "none";
            
            categoryWrapper.appendChild(categoryContent);
            yearContent.appendChild(categoryWrapper);
        });
        
        yearWrapper.appendChild(yearContent);
        container.appendChild(yearWrapper);
    });
}

/**
 * Update layer list untuk struktur 2 level (regular)
 */
function updateRegularLayerList(container) {
    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        const groupWrapper = document.createElement("div");
        groupWrapper.classList.add("layer-group", "mb-2");

        const header = createRegularHeader(kategori, Object.keys(sublayers).length);
        groupWrapper.appendChild(header);

        const subLayerList = document.createElement("div");
        subLayerList.id = `group-${kategori.replace(/\s+/g, "-")}`;
        subLayerList.className = "border border-top-0 rounded-bottom bg-light";
        subLayerList.style.display = "none";

        Object.entries(sublayers).forEach(([subname, layer]) => {
            const hasChildren = Object.keys(sublayers).length > 1;
            if (subname === kategori && hasChildren) return;

            const row = createRegularLayerRow(subname, layer, kategori);
            subLayerList.appendChild(row);
        });

        groupWrapper.appendChild(subLayerList);
        container.appendChild(groupWrapper);
    });
}

/**
 * Membuat header untuk tahun (proyek strategis)
 */
function createYearHeader(tahun, categoryCount) {
    const header = document.createElement("div");
    header.className = "d-flex align-items-center justify-content-between px-3 py-2 border rounded bg-primary text-white";
    header.style.cursor = "pointer";

    const leftSection = document.createElement("div");
    leftSection.className = "d-flex align-items-center";

    const toggleBtn = document.createElement("span");
    toggleBtn.className = "me-2";
    toggleBtn.innerHTML = `<i class="bi bi-chevron-right text-white"></i>`;
    toggleBtn.style.transition = "transform 0.3s ease";

    const label = document.createElement("span");
    label.className = "fw-bold";
    label.textContent = `Tahun ${tahun}`;

    const badge = document.createElement("span");
    badge.className = "badge bg-light text-dark ms-2";
    badge.textContent = categoryCount;

    leftSection.appendChild(toggleBtn);
    leftSection.appendChild(label);
    leftSection.appendChild(badge);
    header.appendChild(leftSection);

    // Toggle functionality
    header.addEventListener("click", () => {
        const content = document.getElementById(`year-${tahun.replace(/\s+/g, "-")}`);
        if (content) {
            const isVisible = content.style.display !== "none";
            content.style.display = isVisible ? "none" : "block";
            toggleBtn.innerHTML = isVisible
                ? `<i class="bi bi-chevron-right text-white"></i>`
                : `<i class="bi bi-chevron-down text-white"></i>`;
        }
    });

    return header;
}

/**
 * Membuat header untuk kategori (proyek strategis) dengan lazy loading
 */
function createCategoryHeader(kategori, subCount, tahun) {
    const headerId = `cat-${tahun}-${kategori}`.replace(/\s+/g, "-");
    
    const header = document.createElement("div");
    header.className = "d-flex align-items-center justify-content-between px-3 py-2 border rounded";
    header.style.cursor = "pointer";

    const leftSection = document.createElement("div");
    leftSection.className = "d-flex align-items-center";

    const toggleBtn = document.createElement("span");
    toggleBtn.className = "me-2";
    toggleBtn.innerHTML = `<i class="bi bi-chevron-right"></i>`;

    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.className = "form-check-input me-2";
    checkbox.id = headerId;
    checkbox.style.border = "2px solid #999";

    const label = document.createElement("label");
    label.className = "form-check-label fw-bold";
    label.style.fontSize = "0.85rem";
    label.htmlFor = headerId;
    label.textContent = kategori;

    const badge = document.createElement("span");
    badge.className = "badge bg-secondary ms-2";
    badge.textContent = subCount;

    leftSection.appendChild(toggleBtn);
    leftSection.appendChild(checkbox);
    leftSection.appendChild(label);
    leftSection.appendChild(badge);
    header.appendChild(leftSection);

    // Lazy loading pada checkbox change
    checkbox.addEventListener("change", async () => {
        if (checkbox.checked) {
            // Tampilkan loading
            checkbox.disabled = true;
            const originalText = label.textContent;
            label.textContent = `${originalText} (Loading...)`;

            try {
                // Load subcategory content jika belum dimuat
                await loadAndDisplaySubcategories(tahun, kategori);
                
                // Check semua subcategories
                const categoryContent = document.getElementById(`cat-${tahun}-${kategori}`.replace(/\s+/g, "-"));
                const subCheckboxes = categoryContent.querySelectorAll('input[type="checkbox"]');
                subCheckboxes.forEach(subCb => {
                    if (!subCb.checked) {
                        subCb.click();
                    }
                });
            } catch (error) {
                console.error("Error loading subcategories:", error);
                showAlert("Gagal memuat data kategori", "danger");
                checkbox.checked = false;
            } finally {
                checkbox.disabled = false;
                label.textContent = originalText;
            }
        } else {
            // Uncheck semua subcategories
            const categoryContent = document.getElementById(`cat-${tahun}-${kategori}`.replace(/\s+/g, "-"));
            const subCheckboxes = categoryContent.querySelectorAll('input[type="checkbox"]');
            subCheckboxes.forEach(subCb => {
                if (subCb.checked) {
                    subCb.click();
                }
            });
        }
    });

    // Toggle functionality
    header.addEventListener("click", async (e) => {
        if (e.target === checkbox || e.target === label) return;
        
        const categoryContent = document.getElementById(`cat-${tahun}-${kategori}`.replace(/\s+/g, "-"));
        if (categoryContent) {
            const isVisible = categoryContent.style.display !== "none";
            
            if (!isVisible) {
                // Load subcategories jika belum dimuat
                if (categoryContent.children.length === 0) {
                    await loadAndDisplaySubcategories(tahun, kategori);
                }
            }
            
            categoryContent.style.display = isVisible ? "none" : "block";
            toggleBtn.innerHTML = isVisible
                ? `<i class="bi bi-chevron-right"></i>`
                : `<i class="bi bi-chevron-down"></i>`;
        }
    });

    return header;
}

/**
 * Load dan display subcategories untuk kategori tertentu
 */
async function loadAndDisplaySubcategories(tahun, kategori) {
    const categoryContent = document.getElementById(`cat-${tahun}-${kategori}`.replace(/\s+/g, "-"));
    if (!categoryContent) return;

    // Jika sudah dimuat, skip
    if (categoryContent.children.length > 0) return;

    const subCategories = layerGroups[tahun][kategori];
    
    Object.entries(subCategories).forEach(([subKategori, layer]) => {
        const hasChildren = Object.keys(subCategories).length > 1;
        if (subKategori === kategori && hasChildren) return;

        const row = createSubcategoryRow(subKategori, tahun, kategori);
        categoryContent.appendChild(row);
    });
}

/**
 * Membuat row untuk subcategory dengan lazy loading
 */
function createSubcategoryRow(subKategori, tahun, kategori) {
    const rowId = `sub-${tahun}-${kategori}-${subKategori}`.replace(/\s+/g, "-");
    
    const row = document.createElement("div");
    row.className = "d-flex align-items-center px-4 py-2";

    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.className = "form-check-input me-3";
    checkbox.id = rowId;
    checkbox.style.border = "2px solid #999";

    // Lazy loading pada subcategory checkbox
    checkbox.addEventListener("change", async () => {
        if (checkbox.checked) {
            checkbox.disabled = true;
            const originalLabel = label.textContent;
            label.textContent = `${originalLabel} (Loading...)`;

            try {
                const layer = await loadCategoryData(tahun, kategori, subKategori);
                if (layer) {
                    map.addLayer(layer);
                } else {
                    showAlert(`Tidak ada data untuk ${subKategori}`, "info");
                    checkbox.checked = false;
                }
            } catch (error) {
                console.error(`Error loading ${subKategori}:`, error);
                showAlert("Gagal memuat data", "danger");
                checkbox.checked = false;
            } finally {
                checkbox.disabled = false;
                label.textContent = originalLabel;
            }
        } else {
            const layer = layerGroups[tahun][kategori][subKategori];
            if (layer && map.hasLayer(layer)) {
                map.removeLayer(layer);
            }
        }
        
        updateParentCheckboxStates(tahun, kategori);
    });

    const label = document.createElement("label");
    label.className = "form-check-label";
    label.htmlFor = rowId;
    label.textContent = subKategori;
    label.style.cssText = `
        font-size: 0.75rem;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        flex: 1;
        max-width: calc(100% - 40px);
        line-height: 1.2;
    `;

    row.style.cssText = `
        display: flex;
        align-items: center;
        width: 100%;
        gap: 0.5rem;
    `;

    row.appendChild(checkbox);
    row.appendChild(label);
    
    return row;
}

/**
 * Membuat header untuk regular maps
 */
function createRegularHeader(kategori, subCount) {
    const rootId = `root-${kategori.replace(/\s+/g, "-")}`;
    
    const header = document.createElement("div");
    header.className = "d-flex align-items-center justify-content-between px-3 py-2 border rounded";
    header.style.cursor = "pointer";

    const leftSection = document.createElement("div");
    leftSection.className = "d-flex align-items-center";

    const toggleBtn = document.createElement("span");
    toggleBtn.className = "me-2";
    toggleBtn.innerHTML = `<i class="bi bi-chevron-right"></i>`;
    toggleBtn.style.transition = "transform 0.3s ease";

    const checkboxRoot = document.createElement("input");
    checkboxRoot.type = "checkbox";
    checkboxRoot.className = "form-check-input me-2";
    checkboxRoot.id = rootId;
    checkboxRoot.style.border = "2px solid #999";

    const labelRoot = document.createElement("label");
    labelRoot.className = "form-check-label fw-bold";
    labelRoot.style.fontSize = "0.85rem";
    labelRoot.htmlFor = rootId;
    labelRoot.textContent = kategori;

    const badge = document.createElement("span");
    badge.className = "badge bg-light text-dark ms-2";
    badge.textContent = subCount;

    // Parent checkbox controls all children
    checkboxRoot.addEventListener("change", () => {
        const isChecked = checkboxRoot.checked;
        Object.entries(layerGroups[kategori]).forEach(([subname, layer]) => {
            const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
            const checkbox = document.getElementById(subId);
            if (checkbox) {
                checkbox.checked = isChecked;
                isChecked ? map.addLayer(layer) : map.removeLayer(layer);
            }
        });
    });

    leftSection.appendChild(toggleBtn);
    leftSection.appendChild(checkboxRoot);
    leftSection.appendChild(labelRoot);
    leftSection.appendChild(badge);
    header.appendChild(leftSection);

    // Toggle functionality
    header.addEventListener("click", (e) => {
        if (e.target !== checkboxRoot && e.target !== labelRoot) {
            const content = document.getElementById(`group-${kategori.replace(/\s+/g, "-")}`);
            if (content) {
                const isVisible = content.style.display !== "none";
                content.style.display = isVisible ? "none" : "block";
                toggleBtn.innerHTML = isVisible
                    ? `<i class="bi bi-chevron-right"></i>`
                    : `<i class="bi bi-chevron-down"></i>`;
            }
        }
    });

    return header;
}

/**
 * Membuat row untuk regular layer
 */
function createRegularLayerRow(subname, layer, kategori) {
    const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
    
    const row = document.createElement("div");
    row.className = "d-flex align-items-center px-4 py-2";

    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.className = "form-check-input me-3";
    checkbox.id = subId;
    checkbox.style.border = "2px solid #999";

    checkbox.addEventListener("change", () => {
        checkbox.checked ? map.addLayer(layer) : map.removeLayer(layer);

        // Update parent state
        const parentCheckbox = document.getElementById(`root-${kategori}`.replace(/\s+/g, "-"));
        if (parentCheckbox) {
            const allSubs = Array.from(document.querySelectorAll(`input[id^="sub-${kategori}-"]`));
            const checkedCount = allSubs.filter((cb) => cb.checked).length;

            if (checkedCount === 0) {
                parentCheckbox.checked = false;
                parentCheckbox.indeterminate = false;
            } else if (checkedCount === allSubs.length) {
                parentCheckbox.checked = true;
                parentCheckbox.indeterminate = false;
            } else {
                parentCheckbox.checked = false;
                parentCheckbox.indeterminate = true;
            }
        }
    });

    const label = document.createElement("label");
    label.className = "form-check-label";
    label.htmlFor = subId;
    label.textContent = subname;
    label.style.cssText = `
        font-size: 0.75rem;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        flex: 1;
        max-width: calc(100% - 40px);
        line-height: 1.2;
    `;

    row.style.cssText = `
        display: flex;
        align-items: center;
        width: 100%;
        gap: 0.5rem;
    `;

    row.appendChild(checkbox);
    row.appendChild(label);
    
    return row;
}

/**
 * Update parent checkbox states berdasarkan child checkboxes
 */
function updateParentCheckboxStates(tahun, kategori) {
    if (!tahun) {
        // Regular structure - update category checkbox
        const categoryCheckbox = document.getElementById(`root-${kategori}`.replace(/\s+/g, "-"));
        if (categoryCheckbox) {
            const subCheckboxes = Array.from(document.querySelectorAll(`input[id^="sub-${kategori}-"]`));
            const checkedCount = subCheckboxes.filter(cb => cb.checked).length;
            
            if (checkedCount === 0) {
                categoryCheckbox.checked = false;
                categoryCheckbox.indeterminate = false;
            } else if (checkedCount === subCheckboxes.length) {
                categoryCheckbox.checked = true;
                categoryCheckbox.indeterminate = false;
            } else {
                categoryCheckbox.checked = false;
                categoryCheckbox.indeterminate = true;
            }
        }
    } else {
        // Proyek strategis structure - update category checkbox
        const categoryCheckbox = document.getElementById(`cat-${tahun}-${kategori}`.replace(/\s+/g, "-"));
        if (categoryCheckbox) {
            const subCheckboxes = Array.from(document.querySelectorAll(`input[id^="sub-${tahun}-${kategori}-"]`));
            const checkedCount = subCheckboxes.filter(cb => cb.checked).length;
            
            if (checkedCount === 0) {
                categoryCheckbox.checked = false;
                categoryCheckbox.indeterminate = false;
            } else if (checkedCount === subCheckboxes.length) {
                categoryCheckbox.checked = true;
                categoryCheckbox.indeterminate = false;
            } else {
                categoryCheckbox.checked = false;
                categoryCheckbox.indeterminate = true;
            }
        }
    }
}


/**
 * Inisialisasi dan setup event handler untuk UI (slider transparansi, basemap, sidebar, dll).
 * Mengatur interaksi user dengan kontrol peta dan sidebar.
 */
function setupUI() {
    const transparencySlider = document.getElementById("transparency");
    if (transparencySlider) {
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
        mapConfig.baseMapsList.forEach((bm, i) => {
            basemapList.innerHTML += `
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="radio" role="switch" name="basemap-radio" id="bm-${
                        bm.id
                    }" value="${bm.id}" ${i === 4 ? "checked" : ""}>
                    <label class="form-check-label" for="bm-${bm.id}">${
                bm.label
            }</label>
                </div>`;
        });

        basemapList.addEventListener("change", (e) => {
            if (e.target.name === "basemap-radio")
                changeBaseMap(e.target.value);
        });
    }
}

/**
 * Entry point aplikasi frontend peta.
 * Menjalankan inisialisasi peta, basemap, dan UI saat DOM siap.
 */
document.addEventListener("DOMContentLoaded", () => {
    initMap();
    changeBaseMap("esri-world-imagery");
    setupUI();

    // Sidebar Elements
    const sidebarElements = {
        layer: document.getElementById("sidebar-layer"),
        basemap: document.getElementById("sidebar-basemap"),
        legend: document.getElementById("sidebar-legend"),
        download: document.getElementById("sidebar-download"),
        help: document.getElementById("guideModal"),
    };

    // Toggle Buttons
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
            if (el && el !== guideModal) el.style.display = "none";
        });
    }

    let currentStep = 1;
    const totalSteps = guideSteps.length;

    function clearHighlights() {
        controlButtons.forEach((btn) => {
            if (btn) {
                btn.classList.remove("highlighted-control");
                btn.style.position = "";
                btn.style.zIndex = "";
                btn.style.padding = "";
            }
        });
    }

    function showStep(step) {
        guideSteps.forEach((stepDiv) => {
            stepDiv.classList.toggle(
                "d-none",
                parseInt(stepDiv.dataset.step) !== step
            );
        });

        btnPrev.disabled = step === 1;
        btnNext.textContent = step === totalSteps ? "Finish" : "Next";
        clearHighlights();

        switch (step) {
            case 3:
                controlButtons[0]?.classList.add("highlighted-control");
                break;
            case 4:
                controlButtons[1]?.classList.add("highlighted-control");
                break;
            case 5:
                controlButtons[2]?.classList.add("highlighted-control");
                break;
            case 6:
                controlButtons[3]?.classList.add("highlighted-control");
                break;
            case 7:
                controlButtons[4]?.classList.add("highlighted-control");
                break;
            case 8:
                controlButtons[5]?.classList.add("highlighted-control");
                break;
            case 9:
                controlButtons[6]?.classList.add("highlighted-control");
                break;
        }
    }

    function hideGuideModal() {
        const modalInstance = bootstrap.Modal.getInstance(guideModal);
        modalInstance?.hide();

        document.body.classList.remove("modal-open");
        document
            .querySelectorAll(".modal-backdrop")
            .forEach((el) => el.remove());
        document.querySelector(".guide-overlay")?.remove();
    }

    btnToggleHelp?.addEventListener("click", () => {
        const modalInstance =
            bootstrap.Modal.getInstance(guideModal) ||
            new bootstrap.Modal(guideModal);
        const isVisible = guideModal.classList.contains("show");

        closeAllSidebars();
        clearHighlights();

        if (!isVisible) {
            currentStep = 1;
            showStep(currentStep);
            modalInstance.show();
        } else {
            modalInstance.hide();
        }
    });

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
            clearHighlights();
        }
    });

    btnSkip?.addEventListener("click", () => {
        hideGuideModal();
        clearHighlights();
    });

    // Sidebar toggles
    Object.entries(toggleButtons).forEach(([key, button]) => {
        if (button && sidebarElements[key]) {
            button.addEventListener("click", () => {
                const sidebar = sidebarElements[key];
                const isVisible = sidebar.style.display === "block";
                closeAllSidebars();

                if (key === "help" && window.bootstrap) {
                    const modal = new bootstrap.Modal(sidebar);
                    if (!isVisible) modal.show();
                } else {
                    sidebar.style.display = isVisible ? "none" : "block";
                }
            });
        }
    });

    // Close sidebar buttons
    ["layer", "basemap", "legend", "download"].forEach((type) => {
        const closeBtn = document.getElementById(`btn-close-sidebar-${type}`);
        if (closeBtn && sidebarElements[type]) {
            closeBtn.addEventListener("click", () => {
                sidebarElements[type].style.display = "none";
            });
        }
    });

    // Fullscreen toggle
    const btnFullscreen = document.getElementById("btn-fullscreen");
    btnFullscreen?.addEventListener("click", () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(console.error);
        } else {
            document.exitFullscreen().catch(console.error);
        }
    });

    // Zoom reset
    const btnDefaultZoom = document.getElementById("btn-default-zoom");
    btnDefaultZoom?.addEventListener("click", () => {
        map.setView(mapConfig.center, mapConfig.zoom);
    });

    // Search layer
    const layerSearchInput = document.getElementById("layer-search");
    layerSearchInput?.addEventListener("input", (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const layerGroups = document.querySelectorAll(".layer-group");

        layerGroups.forEach((group) => {
            const parentLabel = group.querySelector(".fw-bold");
            const childLabels = group.querySelectorAll(".bg-light label");
            let hasMatch = false;

            if (
                parentLabel &&
                parentLabel.textContent.toLowerCase().includes(searchTerm)
            )
                hasMatch = true;

            childLabels.forEach((label) => {
                if (label.textContent.toLowerCase().includes(searchTerm)) {
                    hasMatch = true;
                }
            });

            group.style.display =
                hasMatch || searchTerm === "" ? "block" : "none";
        });
    });
});
