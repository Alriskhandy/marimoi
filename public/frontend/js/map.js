// map-app.js - True On-Demand Loading
/**
 * map-app.js - HANYA ambil data yang benar-benar dicentang
 * Strategi: Load metadata kategori dulu, data spatial HANYA saat checkbox dicentang
 */
console.log("map-app.js loaded - true on-demand loading");

/**
 * Konfigurasi utama peta
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
 * Inisialisasi peta
 */
const map = L.map("map", {
    zoomControl: true,
    attributionControl: true,
}).setView(mapConfig.center, mapConfig.zoom);

// State management - MINIMAL
let layerGroups = {};
let currentBaseMap = null;
let kategoriWarnaMap = {};
let iconMap = {};
let categoryDataCounts = {};
let availableCategories = [];
let isCategoriesLoaded = false;

// HANYA untuk kategori yang sedang aktif
let activeCategories = new Set(); // Kategori yang sedang dicentang
let loadingCategories = new Set(); // Kategori yang sedang loading

/**
 * Utility functions
 */
function showAlert(message, type = "info") {
    console.log(`${type}: ${message}`);
    const toastContainer = document.getElementById("toast-container");
    if (!toastContainer) return;

    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute("role", "alert");
    toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
    toastContainer.appendChild(toast);
    new bootstrap.Toast(toast).show();
    toast.addEventListener("hidden.bs.toast", () => toast.remove());
}

function getDataType(urlPath) {
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
            return { type: "tematik", sub_type: null, year: null };
    }
}

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
 * STEP 1: Load HANYA metadata kategori (ZERO data spatial)
 */
async function loadCategories() {
    if (isCategoriesLoaded) return;

    try {
        showAlert("Memuat daftar kategori...", "info");

        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);

        // HANYA metadata - TIDAK ADA DATA SPATIAL SAMA SEKALI
        let queryString = "?metadata_only=true";
        if (tipeLayer.type) queryString += `&type=${tipeLayer.type}`;
        if (tipeLayer.sub_type) queryString += `&sub_type=${tipeLayer.sub_type}`;
        if (tipeLayer.year) queryString += `&year=${tipeLayer.year}`;

        const response = await fetch(`/geojson${queryString}`);
        const metadata = await response.json();

        if (!metadata?.all_categories?.length) {
            showAlert("Tidak ada kategori tersedia", "warning");
            return;
        }

        availableCategories = metadata.all_categories;
        
        // Build HANYA mapping, BUKAN data
        kategoriWarnaMap = {};
        iconMap = {};
        categoryDataCounts = {};
        
        metadata.all_categories.forEach((cat) => {
            if (cat.nama && cat.warna) {
                kategoriWarnaMap[cat.nama] = cat.warna;
                if (cat.is_marker && cat.icon) {
                    iconMap[cat.nama] = cat.icon;
                }
                if (cat.data_count !== undefined) {
                    categoryDataCounts[cat.nama] = cat.data_count;
                }
            }
        });

        // Build HANYA struktur layer KOSONG
        layerGroups = {};
        const parents = metadata.all_categories.filter(cat => !cat.parent_id);
        const children = metadata.all_categories.filter(cat => cat.parent_id);

        parents.forEach((parent) => {
            layerGroups[parent.nama] = {};
            const anak = children.filter(child => child.parent_id === parent.id);

            if (anak.length > 0) {
                anak.forEach((child) => {
                    // Layer group KOSONG - TIDAK ADA DATA
                    layerGroups[parent.nama][child.nama] = L.layerGroup();
                });
            } else {
                layerGroups[parent.nama][parent.nama] = L.layerGroup();
            }
        });

        updateLayerList();
        isCategoriesLoaded = true;
        showAlert(`${metadata.all_categories.length} kategori tersedia`, "success");

    } catch (error) {
        console.error("Error loading categories:", error);
        showAlert("Gagal memuat kategori", "danger");
    }
}

/**
 * STEP 2: Load data HANYA untuk kategori yang dicentang
 */
async function toggleCategoryData(kategori, isChecked) {
    if (isChecked) {
        // CENTANG: Load data untuk kategori ini
        await loadSingleCategoryData(kategori);
    } else {
        // UNCENTANG: Hapus data kategori ini
        clearSingleCategoryData(kategori);
    }
}

/**
 * Load data untuk SATU kategori saja
 */
async function loadSingleCategoryData(kategori) {
    // Prevent double loading
    if (activeCategories.has(kategori) || loadingCategories.has(kategori)) {
        return;
    }

    loadingCategories.add(kategori);

    try {
        showAlert(`Memuat data ${kategori}...`, "info");

        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);

        // Request data HANYA untuk kategori ini
        let queryString = `?category=${encodeURIComponent(kategori)}`;
        if (tipeLayer.type) queryString += `&type=${tipeLayer.type}`;
        if (tipeLayer.sub_type) queryString += `&sub_type=${tipeLayer.sub_type}`;
        if (tipeLayer.year) queryString += `&year=${tipeLayer.year}`;

        const response = await fetch(`/geojson${queryString}`);
        const categoryData = await response.json();

        if (!categoryData?.features?.length) {
            showAlert(`Tidak ada data untuk kategori ${kategori}`, "warning");
            loadingCategories.delete(kategori);
            return;
        }

        // Find target layer
        let targetLayer = getTargetLayer(kategori);
        if (!targetLayer) {
            console.error(`Target layer tidak ditemukan untuk kategori: ${kategori}`);
            loadingCategories.delete(kategori);
            return;
        }

        // Clear existing data (jika ada)
        targetLayer.clearLayers();

        // Get marker options
        const markerOptions = getMarkerOptions(kategori);

        // Add HANYA features untuk kategori ini
        let addedCount = 0;
        categoryData.features.forEach((feature) => {
            try {
                L.geoJSON(feature, {
                    pointToLayer: (feature, latlng) =>
                        markerOptions ? L.marker(latlng, { icon: markerOptions }) : L.marker(latlng),
                    style: getStyleForCategory(kategori),
                    onEachFeature: (f, l) => bindPopupContent(f, l, urlPath),
                }).addTo(targetLayer);
                addedCount++;
            } catch (e) {
                console.warn(`Error adding feature:`, e);
            }
        });

        // Add ke peta
        if (!map.hasLayer(targetLayer)) {
            map.addLayer(targetLayer);
        }

        activeCategories.add(kategori);
        loadingCategories.delete(kategori);
        
        showAlert(`${addedCount} data ${kategori} dimuat`, "success");
        updateLegend();

    } catch (error) {
        console.error(`Error loading ${kategori}:`, error);
        showAlert(`Gagal memuat data ${kategori}`, "danger");
        loadingCategories.delete(kategori);
    }
}

/**
 * Hapus data untuk SATU kategori saja
 */
function clearSingleCategoryData(kategori) {
    const targetLayer = getTargetLayer(kategori);
    if (targetLayer) {
        // Remove dari peta
        if (map.hasLayer(targetLayer)) {
            map.removeLayer(targetLayer);
        }
        
        // Clear semua features
        targetLayer.clearLayers();
        
        activeCategories.delete(kategori);
        
        showAlert(`Data ${kategori} dihapus`, "info");
        updateLegend();
    }
}

/**
 * Get target layer for kategori
 */
function getTargetLayer(kategori) {
    for (const [parentName, children] of Object.entries(layerGroups)) {
        if (children[kategori]) {
            return children[kategori];
        }
    }

    if (layerGroups[kategori]?.[kategori]) {
        return layerGroups[kategori][kategori];
    }

    return null;
}

/**
 * Get marker options for kategori
 */
function getMarkerOptions(kategori) {
    const catObj = availableCategories.find(c => c.nama === kategori);
    if (catObj && catObj.is_marker && catObj.icon) {
        const iconWarna = catObj.warna || "blue";
        return L.ExtraMarkers.icon({
            icon: catObj.icon,
            prefix: "fa",
            svg: true,
            markerColor: iconWarna,
            iconColor: "white",
            shape: "circle",
            html: `<i class='fa ${catObj.icon}' style='color:white; background: blue;'></i>`,
        });
    }
    return null;
}

/**
 * Update legend HANYA untuk kategori aktif
 */
function updateLegend() {
    const legendContainer = document.getElementById("legend-content");
    if (!legendContainer) return;

    legendContainer.innerHTML = "";

    activeCategories.forEach((kategori) => {
        const icon = iconMap[kategori];
        const color = kategoriWarnaMap[kategori] || "#ccc";
        
        const targetLayer = getTargetLayer(kategori);
        let featureCount = 0;
        if (targetLayer) {
            targetLayer.eachLayer(() => featureCount++);
        }

        if (icon) {
            legendContainer.innerHTML += `
                <div class="d-flex align-items-center mb-2">
                    <div class="custom-fa-icon d-flex align-items-center justify-content-center" style="width: 14px; height: 14px; background: transparent; border: none; margin-right: 8px;">
                        <i class="${icon}" style="font-size: 12px; color: ${color}; line-height: 1;"></i>
                    </div>
                    <span style="font-size: 0.85rem;">${kategori} (${featureCount})</span>
                </div>
            `;
        } else {
            legendContainer.innerHTML += `
                <div class="d-flex align-items-center mb-2">
                    <div style="width: 14px; height: 14px; background-color: ${color}; border: 1px solid #333; margin-right: 8px;"></div>
                    <span style="font-size: 0.85rem;">${kategori} (${featureCount})</span>
                </div>
            `;
        }
    });
}

/**
 * Popup content
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

        if (type === "Point") {
            center = geom.coordinates;
        } else if (type === "LineString") {
            const mid = Math.floor(geom.coordinates.length / 2);
            center = geom.coordinates[mid];
        } else if (type === "Polygon") {
            const poly = geom.coordinates[0];
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
}

/**
 * Update layer list dengan checkbox yang BENAR-BENAR on-demand
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

        // Parent checkbox
        const checkboxRoot = document.createElement("input");
        checkboxRoot.type = "checkbox";
        checkboxRoot.className = "form-check-input me-2";
        checkboxRoot.id = rootId;

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

        // Parent checkbox - TOGGLE SEMUA CHILDREN
        checkboxRoot.addEventListener("change", async () => {
            const isChecked = checkboxRoot.checked;
            
            for (const [subname] of Object.entries(sublayers)) {
                const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
                const checkbox = document.getElementById(subId);
                
                if (checkbox) {
                    checkbox.checked = isChecked;
                    await toggleCategoryData(subname, isChecked);
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
            const hasChildren = Object.keys(sublayers).length > 1;
            if (subname === kategori && hasChildren) return;

            const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
            const row = document.createElement("div");
            row.className = "d-flex align-items-center px-4 py-2";

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.className = "form-check-input me-3";
            checkbox.id = subId;

            // INDIVIDUAL CHECKBOX - TOGGLE HANYA KATEGORI INI
            checkbox.addEventListener("change", async () => {
                await toggleCategoryData(subname, checkbox.checked);

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
            
            // Tampilkan jumlah data
            const dataCount = categoryDataCounts[subname];
            const labelText = dataCount ? `${subname} (${dataCount})` : subname;
            label.textContent = labelText;

            label.style.cssText = `
                font-size: 0.75rem;
                white-space: normal;
                word-wrap: break-word;
                overflow-wrap: break-word;
                flex: 1;
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
 * Setup UI controls
 */
function setupUI() {
    const transparencySlider = document.getElementById("transparency");
    if (transparencySlider) {
        transparencySlider.addEventListener("input", (e) => {
            const val = e.target.value / 100;
            activeCategories.forEach(kategori => {
                const targetLayer = getTargetLayer(kategori);
                if (targetLayer) {
                    targetLayer.eachLayer((layer) => {
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
    }

    const basemapList = document.getElementById("basemap-list");
    if (basemapList) {
        mapConfig.baseMapsList.forEach((bm, i) => {
            basemapList.innerHTML += `
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="radio" role="switch" name="basemap-radio" id="bm-${bm.id}" value="${bm.id}" ${i === 4 ? "checked" : ""}>
                    <label class="form-check-label" for="bm-${bm.id}">${bm.label}</label>
                </div>`;
        });

        basemapList.addEventListener("change", (e) => {
            if (e.target.name === "basemap-radio") changeBaseMap(e.target.value);
        });
    }
}

/**
 * Entry point - Load HANYA kategori, data on-demand
 */
document.addEventListener("DOMContentLoaded", async () => {
    // Setup basemap
    changeBaseMap("esri-world-imagery");
    setupUI();
    
    // Load HANYA kategori metadata
    await loadCategories();

    // Setup sidebar controls
    const sidebarElements = {
        layer: document.getElementById("sidebar-layer"),
        basemap: document.getElementById("sidebar-basemap"),
        legend: document.getElementById("sidebar-legend"),
        download: document.getElementById("sidebar-download"),
        help: document.getElementById("guideModal"),
    };

    const toggleButtons = {
        layer: document.getElementById("btn-toggle-sidebar-layer"),
        basemap: document.getElementById("btn-toggle-sidebar-basemap"),
        legend: document.getElementById("btn-toggle-sidebar-legend"),
        download: document.getElementById("btn-toggle-sidebar-download"),
        help: document.getElementById("btn-toggle-sidebar-help"),
    };

    function closeAllSidebars() {
        Object.values(sidebarElements).forEach((el) => {
            if (el && el !== sidebarElements.help) el.style.display = "none";
        });
    }

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

    // Layer search
    const layerSearchInput = document.getElementById("layer-search");
    layerSearchInput?.addEventListener("input", (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const layerGroups = document.querySelectorAll(".layer-group");

        layerGroups.forEach((group) => {
            const parentLabel = group.querySelector(".fw-bold");
            const childLabels = group.querySelectorAll(".bg-light label");
            let hasMatch = false;

            if (parentLabel && parentLabel.textContent.toLowerCase().includes(searchTerm)) hasMatch = true;

            childLabels.forEach((label) => {
                if (label.textContent.toLowerCase().includes(searchTerm)) {
                    hasMatch = true;
                }
            });

            group.style.display = hasMatch || searchTerm === "" ? "block" : "none";
        });
    });

    // Zoom to feature functionality
    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("zoomToBtn")) {
            const lat = parseFloat(e.target.dataset.lat);
            const lng = parseFloat(e.target.dataset.lng);
            if (!isNaN(lat) && !isNaN(lng)) {
                map.setView([lat, lng], 15);
            }
        }
    });

    console.log("Map app initialized - true on-demand loading");
});