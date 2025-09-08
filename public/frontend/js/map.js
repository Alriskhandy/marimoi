// map-app.js - Optimized version with on-demand loading
/**
 * map-app.js - Optimized version with on-demand loading
 * Entry point utama aplikasi peta frontend dengan loading data yang efisien.
 */
console.log("map-app.js loaded - optimized version");

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
let loadedCategories = new Set(); // Track loaded categories
let isLoadingData = false; // Prevent concurrent loading

/**
 * Menghasilkan style untuk kategori tertentu.
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
 */
function generateLegend() {
    const legendContainer = document.getElementById("legend-content");
    if (!legendContainer) return;

    legendContainer.innerHTML = "";
    const added = new Set();

    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        Object.keys(sublayers).forEach((sub) => {
            if (added.has(sub)) return;

            let icon = iconMap[sub] || null;
            let color = kategoriWarnaMap[sub] || kategoriWarnaMap[kategori] || "#ccc";

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
 */
function bindPopupContent(feature, layer, urlPath) {
    const props = feature.properties;
    let content = `<div class="py-1" style="max-width: 230px; font-size: 12px;"><h5 class="fw-bold text-primary" style="font-size: 12px; margin-bottom: 5px;">${
        props.kategori || "Feature"
    }</h5>`;

    if (props.gambar) {
        content += `<img src="${props.gambar}" alt="Gambar ${props.KEGIATAN}" style="width: 100%; max-height: 120px; object-fit: cover; margin-bottom: 5px; border: 1.5px solid #ccc;">`;
    }

    content += `<hr style="margin: 5px 0;"><div style="max-height: 150px; overflow-y:auto; padding-right: 5px;">
        <table class="table table-sm table-borderless" style="font-size: 9px; width: 100%; margin-bottom: 5px;">`;

    Object.entries(props).forEach(([key, value]) => {
        const allowedKeys = ["KEGIATAN", "TAHUN", "KABUPATEN", "URUSAN"];
        if (allowedKeys.includes(key.toUpperCase()) && value) {
            const label = key.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase());
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
                    Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(dLon / 2) ** 2;
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                length += R * c;
            }
            content += `<tr><td class="fw-medium">Panjang</td><td>${length.toFixed(2)} km</td></tr>`;
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
            content += `<tr><td class="fw-medium">Koordinat</td><td>${center[1].toFixed(5)}, ${center[0].toFixed(5)}</td></tr>`;
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
                    const latlngs = geom.coordinates.map(([lng, lat]) => [lat, lng]);
                    mapInstance.fitBounds(latlngs);
                } else if (geom.type === "Polygon") {
                    const latlngs = geom.coordinates[0].map(([lng, lat]) => [lat, lng]);
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
        showAlert("Memuat daftar kategori...", "info");
        
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
                const anak = children.filter((child) => child.parent_id === parent.id);

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
        showAlert("Kategori berhasil dimuat. Pilih layer untuk memuat data.", "success");
        
    } catch (error) {
        console.error("Error loading categories metadata:", error);
        showAlert(`Gagal memuat kategori: ${error.message}`, "danger");
        
        const layerListContainer = document.getElementById("layer-list");
        if (layerListContainer) {
            layerListContainer.innerHTML = `
                <div class="d-flex flex-column align-items-center justify-content-center" style="height:120px;">
                    <i class="bi bi-x-circle text-danger" style="font-size:2rem;"></i>
                    <span class="mt-2 text-muted">Terjadi kesalahan saat memuat kategori.</span>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadCategoriesMetadata()">Coba Lagi</button>
                </div>`;
        }
    }
}

/**
 * Load spatial data for specific category on-demand
 */
async function loadCategoryData(categoryName) {
    if (isLoadingData || loadedCategories.has(categoryName)) {
        return;
    }

    try {
        isLoadingData = true;
        showAlert(`Memuat data untuk ${categoryName}...`, "info");

        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);
        const dataType = tipeLayer.type;
        const subType = tipeLayer.sub_type || null;
        const year = tipeLayer.year || null;

        let queryString = "?";
        if (dataType) queryString += `type=${dataType}`;
        if (subType) queryString += `&sub_type=${subType}`;
        if (year) queryString += `&year=${year}`;
        queryString += `&kategori[]=${encodeURIComponent(categoryName)}`;

        const response = await fetch(`/geojson${queryString}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const geoJsonData = await response.json();

        if (!geoJsonData?.features?.length) {
            showAlert(`Tidak ada data untuk kategori ${categoryName}`, "warning");
            return;
        }

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

        // Determine marker options
        let markerOptions = null;
        const catObj = geoJsonData.all_categories?.find(c => c.nama === categoryName);
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

        // Add features to layer
        geoJsonData.features.forEach((feature) => {
            L.geoJSON(feature, {
                pointToLayer: (feature, latlng) =>
                    markerOptions
                        ? L.marker(latlng, { icon: markerOptions })
                        : L.marker(latlng),
                style: getStyleForCategory(categoryName),
                onEachFeature: (f, l) => bindPopupContent(f, l, urlPath),
            }).addTo(targetLayer);
        });

        loadedCategories.add(categoryName);
        showAlert(`Data ${categoryName} berhasil dimuat (${geoJsonData.features.length} fitur)`, "success");

    } catch (error) {
        console.error(`Error loading data for category ${categoryName}:`, error);
        showAlert(`Gagal memuat data ${categoryName}: ${error.message}`, "danger");
    } finally {
        isLoadingData = false;
    }
}

/**
 * Enhanced updateLayerList with on-demand loading
 */
function updateLayerList() {
    const container = document.getElementById("layer-list");
    if (!container) return;

    container.innerHTML = "";

    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        const groupId = `group-${kategori.replace(/\s+/g, "-")}`;
        const groupWrapper = document.createElement("div");
        groupWrapper.classList.add("layer-group", "mb-2");

        const rootId = `root-${kategori.replace(/\s+/g, "-")}`;

        // Create header
        const header = document.createElement("div");
        header.className = "d-flex align-items-center justify-content-between px-3 py-2 border rounded";
        header.style.cursor = "pointer";

        const leftSection = document.createElement("div");
        leftSection.className = "d-flex align-items-center";

        // Toggle icon
        const toggleBtn = document.createElement("span");
        toggleBtn.className = "me-2";
        toggleBtn.innerHTML = `<i class="bi bi-chevron-right"></i>`;
        toggleBtn.style.transition = "transform 0.3s ease";

        // Parent checkbox
        const checkboxRoot = document.createElement("input");
        checkboxRoot.type = "checkbox";
        checkboxRoot.className = "form-check-input me-2";
        checkboxRoot.id = rootId;
        checkboxRoot.style.border = "2px solid #999";

        // Parent label
        const labelRoot = document.createElement("label");
        labelRoot.className = "form-check-label fw-bold";
        labelRoot.style.fontSize = "0.85rem";
        labelRoot.htmlFor = rootId;
        labelRoot.textContent = kategori;

        // Count badge
        const subCount = Object.keys(sublayers).length;
        const badge = document.createElement("span");
        badge.className = "badge bg-light text-dark ms-2";
        badge.textContent = subCount;

        // Parent checkbox controls all children with on-demand loading
        checkboxRoot.addEventListener("change", async () => {
            const isChecked = checkboxRoot.checked;
            
            for (const [subname, layer] of Object.entries(sublayers)) {
                const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
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
        });

        leftSection.appendChild(toggleBtn);
        leftSection.appendChild(checkboxRoot);
        leftSection.appendChild(labelRoot);
        leftSection.appendChild(badge);
        header.appendChild(leftSection);
        groupWrapper.appendChild(header);

        // Create sublayers container
        const subLayerList = document.createElement("div");
        subLayerList.id = groupId;
        subLayerList.className = "border border-top-0 rounded-bottom bg-light";
        subLayerList.style.display = "none";

        // Add sublayers
        Object.entries(sublayers).forEach(([subname, layer]) => {
            // Skip if same name as parent and has multiple children
            const hasChildren = Object.keys(sublayers).length > 1;
            if (subname === kategori && hasChildren) return;

            const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
            const row = document.createElement("div");
            row.className = "d-flex align-items-center px-4 py-2";

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.className = "form-check-input me-3";
            checkbox.id = subId;
            checkbox.style.border = "2px solid #999";

            checkbox.addEventListener("change", async () => {
                if (checkbox.checked) {
                    // Load data on-demand if not loaded yet
                    await loadCategoryData(subname);
                    map.addLayer(layer);
                } else {
                    map.removeLayer(layer);
                }

                // Update parent state
                const allSubs = Array.from(subLayerList.querySelectorAll('input[type="checkbox"]'));
                const checkedCount = allSubs.filter((cb) => cb.checked).length;

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
            });

            const label = document.createElement("label");
            label.className = "form-check-label";
            label.htmlFor = subId;
            label.textContent = subname;

            // Style for better text wrapping
            row.style.cssText = `
                display: flex;
                align-items: center;
                width: 100%;
                gap: 0.5rem;
            `;

            label.style.cssText = `
                font-size: 0.75rem;
                white-space: normal;
                word-wrap: break-word;
                overflow-wrap: break-word;
                flex: 1;
                max-width: calc(100% - 40px);
                line-height: 1.2;
            `;

            row.appendChild(checkbox);
            row.appendChild(label);
            subLayerList.appendChild(row);
        });

        // Toggle functionality
        const toggleDropdown = () => {
            const isVisible = subLayerList.style.display !== "none";
            subLayerList.style.display = isVisible ? "none" : "block";
            toggleBtn.innerHTML = isVisible
                ? `<i class="bi bi-chevron-right"></i>`
                : `<i class="bi bi-chevron-down"></i>`;
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

/**
 * Inisialisasi dan setup event handler untuk UI (slider transparansi, basemap, sidebar, dll).
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
                    <label class="form-check-label" for="bm-${bm.id}">${bm.label}</label>
                </div>`;
        });

        basemapList.addEventListener("change", (e) => {
            if (e.target.name === "basemap-radio")
                changeBaseMap(e.target.value);
        });
    }
}

/**
 * Entry point aplikasi frontend peta dengan optimized loading.
 */
document.addEventListener("DOMContentLoaded", async () => {
    // Initialize map and basemap first
    changeBaseMap("esri-world-imagery");
    setupUI();

    // Set loading indicator
    const layerListContainer = document.getElementById("layer-list");
    if (layerListContainer) {
        layerListContainer.innerHTML = `
            <div id="layer-loading" style="display:flex;align-items:center;justify-content:center;height:120px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`;
    }

    // Load only categories metadata first
    await loadCategoriesMetadata();

    // Remove loading indicator
    const loadingDiv = document.getElementById("layer-loading");
    if (loadingDiv) loadingDiv.remove();

    console.log("Map application initialized with on-demand loading");

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

    // Search layer with debouncing for better performance
    const layerSearchInput = document.getElementById("layer-search");
    let searchTimeout;
    
    layerSearchInput?.addEventListener("input", (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
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
        }, 300); // 300ms debounce
    });
});