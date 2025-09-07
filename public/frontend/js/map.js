// map.js - Fixed untuk working dengan controller yang sudah diperbaiki
/**
 * map.js - True on-demand loading dengan metadata first approach
 */
console.log("map.js loaded - fixed version for optimized controller");

/**
 * Map configuration
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
 * Initialize map
 */
const map = L.map("map", {
    zoomControl: true,
    attributionControl: true,
}).setView(mapConfig.center, mapConfig.zoom);

// State management
let layerGroups = {};
let currentBaseMap = null;
let kategoriMetadata = {};
let activeCategories = new Set();
let loadingCategories = new Set();
let isCategoriesLoaded = false;

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
    const routes = {
        "/proyek-strategis-daerah": { type: "proyek_strategis", sub_type: "psd", year: null },
        "/proyek-strategis-nasional": { type: "proyek_strategis", sub_type: "psn", year: null },
        "/peta-tematik": { type: "tematik", sub_type: null, year: null },
        "/usulan-musrenbang": { type: "usulan_musrenbang", sub_type: null, year: null },
        "/pokir-dprd": { type: "pokir_dprd", sub_type: null, year: null }
    };
    return routes[urlPath] || { type: "tematik", sub_type: null, year: null };
}

function getStyleForCategory(kategori) {
    const metadata = kategoriMetadata[kategori];
    const warna = metadata?.warna || "#ECE6D6";
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
 * STEP 1: Load metadata kategori saja (NO spatial data)
 */
async function loadCategoriesMetadata() {
    if (isCategoriesLoaded) return;

    try {
        // Show loading di sidebar
        const layerListContainer = document.getElementById("layer-list");
        if (layerListContainer) {
            layerListContainer.innerHTML = `
                <div id="layer-loading" style="display:flex;align-items:center;justify-content:center;height:120px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>`;
        }

        showAlert("Memuat daftar kategori...", "info");

        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);

        // Request HANYA metadata
        let queryString = "?metadata_only=true";
        if (tipeLayer.type) queryString += `&type=${tipeLayer.type}`;
        if (tipeLayer.sub_type) queryString += `&sub_type=${tipeLayer.sub_type}`;
        if (tipeLayer.year) queryString += `&year=${tipeLayer.year}`;

        console.log('Requesting metadata:', `/geojson${queryString}`);

        const response = await fetch(`/geojson${queryString}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const metadata = await response.json();
        console.log('Metadata response:', metadata);

        if (metadata.type === 'error') {
            throw new Error(metadata.message || 'Server error');
        }

        if (!metadata?.all_categories?.length) {
            showAlert("Tidak ada kategori tersedia", "warning");
            // Show empty state
            if (layerListContainer) {
                layerListContainer.innerHTML = `
                    <div class="d-flex flex-column align-items-center justify-content-center" style="height:120px;">
                        <i class="bi bi-exclamation-circle text-warning" style="font-size:2rem;"></i>
                        <span class="mt-2 text-muted">Tidak ada kategori tersedia.</span>
                    </div>`;
            }
            return;
        }

        // Build metadata map
        kategoriMetadata = {};
        metadata.all_categories.forEach((cat) => {
            if (cat.nama) {
                kategoriMetadata[cat.nama] = {
                    id: cat.id,
                    nama: cat.nama,
                    warna: cat.warna || "#ECE6D6",
                    icon: cat.icon,
                    is_marker: cat.is_marker,
                    parent_id: cat.parent_id,
                    data_count: cat.data_count || 0
                };
            }
        });

        // Build empty layer structure berdasarkan hierarchy
        layerGroups = {};
        const parents = metadata.all_categories.filter(cat => !cat.parent_id);
        const children = metadata.all_categories.filter(cat => cat.parent_id);

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

        // Hide loading
        const loadingDiv = document.getElementById("layer-loading");
        if (loadingDiv) loadingDiv.remove();

        updateLayerList();
        isCategoriesLoaded = true;
        showAlert(`${metadata.all_categories.length} kategori siap dimuat`, "success");

    } catch (error) {
        console.error("Error loading categories metadata:", error);
        
        // Show error state
        const layerListContainer = document.getElementById("layer-list");
        if (layerListContainer) {
            layerListContainer.innerHTML = `
                <div class="d-flex flex-column align-items-center justify-content-center" style="height:120px;">
                    <i class="bi bi-x-circle text-danger" style="font-size:2rem;"></i>
                    <span class="mt-2 text-muted">Gagal memuat kategori.</span>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadCategoriesMetadata()">Coba Lagi</button>
                </div>`;
        }
        
        showAlert(`Gagal memuat kategori: ${error.message}`, "danger");
    }
}

/**
 * STEP 2: Toggle category data on demand
 */
async function toggleCategoryData(kategori, isChecked) {
    if (isChecked) {
        await loadCategoryData(kategori);
    } else {
        clearCategoryData(kategori);
    }
}

/**
 * Load data untuk kategori spesifik
 */
async function loadCategoryData(kategori) {
    if (activeCategories.has(kategori) || loadingCategories.has(kategori)) {
        return;
    }

    loadingCategories.add(kategori);

    try {
        showAlert(`Memuat data ${kategori}...`, "info");

        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);

        // Request data untuk kategori spesifik
        let queryString = `?category=${encodeURIComponent(kategori)}`;
        if (tipeLayer.type) queryString += `&type=${tipeLayer.type}`;
        if (tipeLayer.sub_type) queryString += `&sub_type=${tipeLayer.sub_type}`;
        if (tipeLayer.year) queryString += `&year=${tipeLayer.year}`;

        console.log('Requesting category data:', `/geojson${queryString}`);

        const response = await fetch(`/geojson${queryString}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const categoryData = await response.json();
        console.log('Category data response:', categoryData);

        if (categoryData.type === 'error') {
            throw new Error(categoryData.message || 'Server error');
        }

        if (!categoryData?.features?.length) {
            showAlert(`Tidak ada data untuk kategori ${kategori}`, "warning");
            loadingCategories.delete(kategori);
            return;
        }

        // Get target layer
        const targetLayer = getTargetLayer(kategori);
        if (!targetLayer) {
            throw new Error(`Target layer tidak ditemukan untuk kategori: ${kategori}`);
        }

        // Clear existing data
        targetLayer.clearLayers();

        // Get marker options
        const markerOptions = getMarkerOptions(kategori);

        // Add features to layer
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

        // Add to map
        if (!map.hasLayer(targetLayer)) {
            map.addLayer(targetLayer);
        }

        activeCategories.add(kategori);
        loadingCategories.delete(kategori);
        
        showAlert(`${addedCount} data ${kategori} berhasil dimuat`, "success");
        generateLegend();

        // Handle pagination if available
        if (categoryData.pagination?.has_more) {
            addLoadMoreButton(kategori, categoryData.pagination);
        }

    } catch (error) {
        console.error(`Error loading category ${kategori}:`, error);
        showAlert(`Gagal memuat data ${kategori}: ${error.message}`, "danger");
        loadingCategories.delete(kategori);
    }
}

/**
 * Clear category data
 */
function clearCategoryData(kategori) {
    const targetLayer = getTargetLayer(kategori);
    if (targetLayer) {
        if (map.hasLayer(targetLayer)) {
            map.removeLayer(targetLayer);
        }
        targetLayer.clearLayers();
        activeCategories.delete(kategori);
        showAlert(`Data ${kategori} dihapus`, "info");
        generateLegend();
        removeLoadMoreButton(kategori);
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
    const metadata = kategoriMetadata[kategori];
    if (metadata && metadata.is_marker && metadata.icon) {
        return L.ExtraMarkers.icon({
            icon: metadata.icon,
            prefix: "fa",
            svg: true,
            markerColor: metadata.warna || "blue",
            iconColor: "white",
            shape: "circle",
        });
    }
    return null;
}

/**
 * Add load more button for paginated data
 */
function addLoadMoreButton(kategori, pagination) {
    const checkboxContainer = document.querySelector(`input[data-category="${kategori}"]`)?.closest('.px-4');
    if (!checkboxContainer) return;

    removeLoadMoreButton(kategori); // Remove existing button

    const loadMoreBtn = document.createElement('button');
    loadMoreBtn.className = 'btn btn-sm btn-outline-primary ms-2';
    loadMoreBtn.style.fontSize = '0.7rem';
    loadMoreBtn.innerHTML = `<i class="bi bi-plus-circle me-1"></i>Load More (${pagination.page}/${Math.ceil(pagination.total / pagination.limit)})`;
    loadMoreBtn.setAttribute('data-load-more', kategori);
    
    loadMoreBtn.addEventListener('click', async (e) => {
        e.stopPropagation();
        await loadMoreCategoryData(kategori, pagination.next_page);
    });

    checkboxContainer.appendChild(loadMoreBtn);
}

/**
 * Remove load more button
 */
function removeLoadMoreButton(kategori) {
    const existingBtn = document.querySelector(`button[data-load-more="${kategori}"]`);
    if (existingBtn) {
        existingBtn.remove();
    }
}

/**
 * Load more data for category (pagination)
 */
async function loadMoreCategoryData(kategori, page) {
    if (loadingCategories.has(kategori)) return;

    loadingCategories.add(kategori);

    try {
        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);

        let queryString = `?category=${encodeURIComponent(kategori)}&page=${page}`;
        if (tipeLayer.type) queryString += `&type=${tipeLayer.type}`;
        if (tipeLayer.sub_type) queryString += `&sub_type=${tipeLayer.sub_type}`;
        if (tipeLayer.year) queryString += `&year=${tipeLayer.year}`;

        const response = await fetch(`/geojson${queryString}`);
        const categoryData = await response.json();

        if (categoryData?.features?.length) {
            const targetLayer = getTargetLayer(kategori);
            const markerOptions = getMarkerOptions(kategori);

            categoryData.features.forEach((feature) => {
                try {
                    L.geoJSON(feature, {
                        pointToLayer: (feature, latlng) =>
                            markerOptions ? L.marker(latlng, { icon: markerOptions }) : L.marker(latlng),
                        style: getStyleForCategory(kategori),
                        onEachFeature: (f, l) => bindPopupContent(f, l, urlPath),
                    }).addTo(targetLayer);
                } catch (e) {
                    console.warn(`Error adding feature:`, e);
                }
            });

            showAlert(`${categoryData.features.length} data tambahan ${kategori} dimuat`, "success");

            if (categoryData.pagination?.has_more) {
                addLoadMoreButton(kategori, categoryData.pagination);
            } else {
                removeLoadMoreButton(kategori);
            }
        }

        loadingCategories.delete(kategori);

    } catch (error) {
        console.error(`Error loading more data for ${kategori}:`, error);
        showAlert(`Gagal memuat data tambahan ${kategori}`, "danger");
        loadingCategories.delete(kategori);
    }
}

/**
 * Generate legend untuk kategori aktif
 */
function generateLegend() {
    const legendContainer = document.getElementById("legend-content");
    if (!legendContainer) return;

    legendContainer.innerHTML = "";

    activeCategories.forEach((kategori) => {
        const metadata = kategoriMetadata[kategori];
        if (!metadata) return;

        const targetLayer = getTargetLayer(kategori);
        let featureCount = 0;
        if (targetLayer) {
            targetLayer.eachLayer(() => featureCount++);
        }

        if (metadata.icon) {
            legendContainer.innerHTML += `
                <div class="d-flex align-items-center mb-2">
                    <div class="custom-fa-icon d-flex align-items-center justify-content-center" style="width: 14px; height: 14px; background: transparent; border: none; margin-right: 8px;">
                        <i class="${metadata.icon}" style="font-size: 12px; color: ${metadata.warna}; line-height: 1;"></i>
                    </div>
                    <span style="font-size: 0.85rem;">${kategori} (${featureCount})</span>
                </div>
            `;
        } else {
            legendContainer.innerHTML += `
                <div class="d-flex align-items-center mb-2">
                    <div style="width: 14px; height: 14px; background-color: ${metadata.warna}; border: 1px solid #333; margin-right: 8px;"></div>
                    <span style="font-size: 0.85rem;">${kategori} (${featureCount})</span>
                </div>
            `;
        }
    });
}

/**
 * Bind popup content
 */
function bindPopupContent(feature, layer, urlPath) {
    const props = feature.properties;
    let content = `<div class="py-1" style="max-width: 230px; font-size: 12px;"><h5 class="fw-bold text-primary" style="font-size: 12px; margin-bottom: 5px;">${
        props.kategori || "Feature"
    }</h5>`;

    if (props.gambar) {
        content += `<img src="${props.gambar}" alt="Gambar ${props.KEGIATAN || ''}" style="width: 100%; max-height: 120px; object-fit: cover; margin-bottom: 5px; border: 1.5px solid #ccc;">`;
    }
    
    content += `<hr style="margin: 5px 0;"><div style="max-height: 150px; overflow-y:auto; padding-right: 5px;">
        <table class="table table-sm table-borderless" style="font-size: 9px; width: 100%; margin-bottom: 5px;">`;
    
    const allowedKeys = ["KEGIATAN", "TAHUN", "KABUPATEN", "URUSAN", "deskripsi"];
    allowedKeys.forEach(key => {
        const value = props[key] || props[key.toLowerCase()] || props[key.toUpperCase()];
        if (value) {
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
 * Update layer list dengan true on-demand checkboxes
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

        // Parent checkbox event
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
            checkbox.setAttribute('data-category', subname);

            // Individual checkbox event
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
            
            // Show data count
            const metadata = kategoriMetadata[subname];
            const dataCount = metadata?.data_count || 0;
            const labelText = dataCount > 0 ? `${subname} (${dataCount})` : subname;
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
    // Transparency slider
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

    // Basemap controls
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
 * Initialize application
 */
document.addEventListener("DOMContentLoaded", async () => {
    // Setup basemap
    changeBaseMap("esri-world-imagery");
    setupUI();
    
    // Load categories metadata only
    await loadCategoriesMetadata();

    // Setup sidebar controls (existing code from original)
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

    // Sidebar toggles dan event handlers lainnya
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

    console.log("Map application initialized with true on-demand loading");
});