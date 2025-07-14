// enhanced-map-app.js - Updated for Your Database Structure
console.log("Enhanced WebGIS Application loaded - Updated Version");

// Global variables
let layerGroups = {};
let parentLayerGroups = {};
let currentBaseMap = null;
let allCategoriesData = [];
let allFeaturesData = [];

// Map configuration
const mapConfig = {
    center: [0.735485, 128.028201], // Ternate coordinates
    zoom: 7,
    baseMapsList: [
        {
            id: "osm",
            label: "OpenStreetMap",
            url: "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        },
        {
            id: "google-roadmap",
            label: "Google Map (Roadmap)",
            url: "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            attribution: '&copy; <a href="https://maps.google.com">Google Maps</a>'
        },
        {
            id: "google-hybrid",
            label: "Google Map (Hybrid)",
            url: "https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            attribution: '&copy; <a href="https://maps.google.com">Google Maps</a>'
        },
        {
            id: "google-terrain",
            label: "Google Map (Terrain)",
            url: "https://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            attribution: '&copy; <a href="https://maps.google.com">Google Maps</a>'
        },
        {
            id: "esri-world-imagery",
            label: "ESRI World Imagery",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
            attribution: '&copy; <a href="https://www.esri.com">ESRI</a>'
        },
    ],
};

// Initialize map
const map = L.map("map", {
    zoomControl: true,
    attributionControl: true,
}).setView(mapConfig.center, mapConfig.zoom);

// Enhanced toast notifications
function showAlert(message, type = "info") {
    console.log(`${type}: ${message}`);
    
    const toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
        const container = document.createElement("div");
        container.id = "toast-container";
        container.className = "toast-container position-fixed top-0 end-0 p-3";
        container.style.zIndex = "9999";
        document.body.appendChild(container);
    }

    const iconMap = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill', 
        danger: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };

    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0`;
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi ${iconMap[type] || iconMap.info} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                    onclick="this.parentElement.parentElement.remove()"></button>
        </div>`;
    
    toastContainer.appendChild(toast);
    
    toast.style.display = "block";
    toast.classList.add("show");
    
    setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Change basemap
function changeBaseMap(baseMapId) {
    if (currentBaseMap) map.removeLayer(currentBaseMap);
    const config = mapConfig.baseMapsList.find((bm) => bm.id === baseMapId);
    if (config) {
        currentBaseMap = L.tileLayer(config.url, {
            subdomains: config.subdomains || [],
            maxZoom: 20,
            attribution: config.attribution
        }).addTo(map);
        showAlert(`Basemap berubah ke: ${config.label}`, "success");
    }
}

// Get styling for category - Updated untuk field 'warna'
function getStyleForCategory(kategori, colorMap) {
    return colorMap[kategori] || {
        color: "#172953", 
        fillColor: "#ECE6D6",
        weight: 2,
        fillOpacity: 0.7
    };
}

// Enhanced popup content - Updated untuk structure baru
function bindPopupContent(feature, layer) {
    const props = feature.properties;
    
    let content = `
        <div class="enhanced-popup" style="max-width: 400px;">
            <div class="popup-header bg-primary text-white p-2 rounded-top">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-info-circle me-2"></i>
                    ${props.kategori || "Feature Detail"}
                </h6>
            </div>
            <div class="popup-body p-3">
    `;
    
    // Main information
    if (props.nama || props.NAMOBJ) {
        const namaLokasi = props.nama || props.NAMOBJ || 'Nama tidak tersedia';
        content += `
            <div class="mb-2">
                <label class="fw-medium text-primary small">NAMA LOKASI</label>
                <div class="text-dark">${namaLokasi}</div>
            </div>
        `;
    }
    
    if (props.kategori_full_path) {
        content += `
            <div class="mb-2">
                <label class="fw-medium text-primary small">KATEGORI</label>
                <div class="text-dark">${props.kategori_full_path}</div>
            </div>
        `;
    }
    
    if (props.deskripsi) {
        content += `
            <div class="mb-2">
                <label class="fw-medium text-primary small">DESKRIPSI</label>
                <div class="text-dark">${props.deskripsi}</div>
            </div>
        `;
    }
    
    // Other properties
    const excludeFields = [
        "geometry", "id", "nama", "NAMOBJ", "kategori", "kategori_full_path", 
        "kategori_color", "parent_kategori", "created_at", "updated_at", "deskripsi"
    ];
    const otherProps = Object.entries(props).filter(([key, value]) => 
        value && value !== "" && !excludeFields.includes(key)
    );
    
    if (otherProps.length > 0) {
        content += `<hr class="my-2"><div class="small">`;
        otherProps.forEach(([key, value]) => {
            const label = key.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase());
            content += `
                <div class="mb-1">
                    <span class="fw-medium text-secondary">${label}:</span>
                    <span class="text-dark">${value}</span>
                </div>
            `;
        });
        content += `</div>`;
    }
    
    content += `
            </div>
            <div class="popup-footer bg-light p-2 rounded-bottom">
                <small class="text-muted">
                    <i class="bi bi-geo-alt me-1"></i>
                    Klik pada peta untuk melihat detail lokasi lain
                </small>
            </div>
        </div>
    `;
    
    layer.bindPopup(content, {
        maxWidth: 400,
        className: 'custom-enhanced-popup'
    });
}

// Initialize map data
async function initMap() {
    try {
        showAlert("Memuat data peta...", "info");
        
        // Fetch hierarchical categories
        const kategorisResponse = await fetch("/api/kategoris-hierarchical");
        if (!kategorisResponse.ok) {
            throw new Error("Gagal memuat data kategori");
        }
        const kategorisData = await kategorisResponse.json();
        allCategoriesData = kategorisData;
        
        console.log("Categories loaded:", kategorisData);
        
        // Build color map - Updated untuk field 'warna'
        const colorMap = {};
        kategorisData.all_categories.forEach(kat => {
            colorMap[kat.nama] = {
                color: "#172953",
                fillColor: kat.warna || "#ECE6D6",
                weight: 2,
                fillOpacity: 0.7
            };
        });

        // Fetch GeoJSON data
        const response = await fetch("/api/geojson");
        if (!response.ok) {
            throw new Error("Gagal memuat data GeoJSON");
        }
        const geoJsonData = await response.json();
        allFeaturesData = geoJsonData.features || [];
        
        console.log("GeoJSON loaded:", geoJsonData);
        
        if (!allFeaturesData.length) {
            showAlert("Data GeoJSON kosong", "warning");
            updateHierarchicalLayerList();
            updateHierarchicalLegend();
            return;
        }

        // Initialize layer groups
        layerGroups = {};
        parentLayerGroups = {};
        
        allFeaturesData.forEach((feature) => {
            const kategori = feature.properties?.kategori || "Lainnya";
            const parentKategori = feature.properties?.parent_kategori;
            const fullPath = feature.properties?.kategori_full_path || kategori;
            
            // Create parent group if doesn't exist
            if (parentKategori && !parentLayerGroups[parentKategori]) {
                parentLayerGroups[parentKategori] = L.layerGroup();
            }
            
            // Create category group if doesn't exist
            if (!layerGroups[fullPath]) {
                layerGroups[fullPath] = L.layerGroup();
            }
            
            const geoJsonLayer = L.geoJSON(feature, {
                style: () => getStyleForCategory(kategori, colorMap),
                onEachFeature: (f, l) => bindPopupContent(f, l),
            });
            
            // Add to category group
            geoJsonLayer.addTo(layerGroups[fullPath]);
            
            // Add to parent group if exists
            if (parentKategori && parentLayerGroups[parentKategori]) {
                layerGroups[fullPath].addTo(parentLayerGroups[parentKategori]);
            }
        });

        updateHierarchicalLayerList();
        updateHierarchicalLegend();
        showAlert(`Berhasil memuat ${allFeaturesData.length} features dari ${Object.keys(layerGroups).length} kategori`, "success");
        
    } catch (error) {
        console.error("Error:", error);
        showAlert(`Error: ${error.message}`, "danger");
    }
}

// Update hierarchical layer list
function updateHierarchicalLayerList() {
    const listDiv = document.getElementById("layer-list");
    if (!listDiv) return;
    
    listDiv.innerHTML = "";

    // Search input
    const searchDiv = document.createElement("div");
    searchDiv.className = "mb-3";
    searchDiv.innerHTML = `
        <input type="text" id="layer-search" class="form-control" 
               placeholder="🔍 Cari kategori..." autocomplete="off">
    `;
    listDiv.appendChild(searchDiv);

    // Select all/none buttons
    const controlDiv = document.createElement("div");
    controlDiv.className = "mb-3 d-flex gap-2";
    controlDiv.innerHTML = `
        <button id="select-all-layers" class="btn btn-outline-primary btn-sm flex-fill">
            <i class="bi bi-check-all"></i> Pilih Semua
        </button>
        <button id="deselect-all-layers" class="btn btn-outline-secondary btn-sm flex-fill">
            <i class="bi bi-x-circle"></i> Hapus Semua
        </button>
    `;
    listDiv.appendChild(controlDiv);

    // Category hierarchy
    const hierarchyDiv = document.createElement("div");
    hierarchyDiv.className = "category-hierarchy";
    hierarchyDiv.style.maxHeight = "calc(100vh - 300px)";
    hierarchyDiv.style.overflowY = "auto";
    
    // Group categories by parent
    const rootCategories = allCategoriesData.root_categories || [];
    const categoriesWithData = rootCategories.filter(cat => 
        hasDataInCategory(cat) || (cat.children && cat.children.some(child => hasDataInCategory(child)))
    );
    
    if (categoriesWithData.length === 0) {
        hierarchyDiv.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-info-circle text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2">Belum ada kategori dengan data</p>
            </div>
        `;
    } else {
        categoriesWithData.forEach(rootCategory => {
            const categoryDiv = createCategoryDropdown(rootCategory);
            hierarchyDiv.appendChild(categoryDiv);
        });
    }
    
    listDiv.appendChild(hierarchyDiv);
    
    setupLayerEventListeners();
}

// Check if category has data
function hasDataInCategory(category) {
    const fullPath = getFullCategoryPath(category);
    return layerGroups[fullPath] && layerGroups[fullPath].getLayers().length > 0;
}

// Create category dropdown
function createCategoryDropdown(category) {
    const categoryDiv = document.createElement("div");
    categoryDiv.className = "category-dropdown mb-2";
    
    const hasChildren = category.children && category.children.length > 0;
    const categoryPath = getFullCategoryPath(category);
    const hasDirectData = layerGroups[categoryPath] && layerGroups[categoryPath].getLayers().length > 0;
    
    let html = `
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light py-2 px-3">
                <div class="d-flex align-items-center">
    `;
    
    // Parent category checkbox
    if (hasDirectData || parentLayerGroups[category.nama]) {
        html += `
            <input type="checkbox" id="parent-${category.id}" 
                   class="form-check-input me-2 parent-checkbox" 
                   data-parent-name="${category.nama}"
                   data-category-path="${categoryPath}">
        `;
    }
    
    // Color indicator - Updated untuk field 'warna'
    html += `
        <div class="category-color me-2" 
             style="width: 16px; height: 16px; background-color: ${category.warna || '#gray'}; 
             border: 1px solid #ddd; border-radius: 2px;"></div>
        <label for="parent-${category.id}" class="form-check-label fw-medium flex-grow-1 mb-0">
            ${category.nama}
        </label>
    `;
    
    // Dropdown toggle for children
    if (hasChildren) {
        html += `
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                    type="button" data-bs-toggle="collapse" 
                    data-bs-target="#children-${category.id}" 
                    aria-expanded="false">
                <span class="child-count badge bg-secondary">${category.children.length}</span>
            </button>
        `;
    }
    
    html += `
                </div>
            </div>
    `;
    
    // Children container
    if (hasChildren) {
        html += `
            <div class="collapse" id="children-${category.id}">
                <div class="card-body py-2 px-3 bg-white">
        `;
        
        category.children.forEach(child => {
            const childPath = getFullCategoryPath(child);
            const childHasData = layerGroups[childPath] && layerGroups[childPath].getLayers().length > 0;
            
            if (childHasData) {
                html += `
                    <div class="d-flex align-items-center py-1 ps-2 child-category">
                        <input type="checkbox" id="child-${child.id}" 
                               class="form-check-input me-2 child-checkbox" 
                               data-parent-name="${category.nama}"
                               data-category-path="${childPath}">
                        <div class="category-color me-2" 
                             style="width: 12px; height: 12px; background-color: ${child.warna || '#gray'}; 
                             border: 1px solid #ddd; border-radius: 2px;"></div>
                        <label for="child-${child.id}" class="form-check-label small flex-grow-1 mb-0">
                            ${child.nama}
                        </label>
                        <small class="text-muted">${layerGroups[childPath].getLayers().length} item</small>
                    </div>
                `;
            }
        });
        
        html += `
                </div>
            </div>
        `;
    }
    
    html += `</div>`;
    
    categoryDiv.innerHTML = html;
    return categoryDiv;
}

// Setup layer event listeners
function setupLayerEventListeners() {
    // Search functionality
    const searchInput = document.getElementById("layer-search");
    if (searchInput) {
        searchInput.addEventListener("input", (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const categoryCards = document.querySelectorAll(".category-dropdown");
            
            categoryCards.forEach(card => {
                const labels = card.querySelectorAll("label");
                let match = false;
                
                labels.forEach(label => {
                    if (label.textContent.toLowerCase().includes(searchTerm)) {
                        match = true;
                    }
                });
                
                card.style.display = match ? "block" : "none";
            });
        });
    }
    
    // Select all functionality
    const selectAllBtn = document.getElementById("select-all-layers");
    if (selectAllBtn) {
        selectAllBtn.addEventListener("click", () => {
            document.querySelectorAll(".parent-checkbox, .child-checkbox").forEach(checkbox => {
                if (!checkbox.checked) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event("change"));
                }
            });
        });
    }
    
    // Deselect all functionality
    const deselectAllBtn = document.getElementById("deselect-all-layers");
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener("click", () => {
            document.querySelectorAll(".parent-checkbox, .child-checkbox").forEach(checkbox => {
                if (checkbox.checked) {
                    checkbox.checked = false;
                    checkbox.dispatchEvent(new Event("change"));
                }
            });
        });
    }
    
    // Parent checkbox functionality
    document.querySelectorAll(".parent-checkbox").forEach(checkbox => {
        checkbox.addEventListener("change", (e) => {
            const parentName = e.target.dataset.parentName;
            const categoryPath = e.target.dataset.categoryPath;
            
            if (e.target.checked) {
                // Show parent layer if has direct data
                if (layerGroups[categoryPath]) {
                    map.addLayer(layerGroups[categoryPath]);
                }
                // Show parent group layer
                if (parentLayerGroups[parentName]) {
                    map.addLayer(parentLayerGroups[parentName]);
                }
                // Check all children
                document.querySelectorAll(`.child-checkbox[data-parent-name="${parentName}"]`).forEach(child => {
                    child.checked = true;
                });
            } else {
                // Hide parent layer
                if (layerGroups[categoryPath]) {
                    map.removeLayer(layerGroups[categoryPath]);
                }
                // Hide parent group layer
                if (parentLayerGroups[parentName]) {
                    map.removeLayer(parentLayerGroups[parentName]);
                }
                // Uncheck all children
                document.querySelectorAll(`.child-checkbox[data-parent-name="${parentName}"]`).forEach(child => {
                    child.checked = false;
                });
            }
        });
    });
    
    // Child checkbox functionality
    document.querySelectorAll(".child-checkbox").forEach(checkbox => {
        checkbox.addEventListener("change", (e) => {
            const categoryPath = e.target.dataset.categoryPath;
            const parentName = e.target.dataset.parentName;
            
            if (e.target.checked) {
                if (layerGroups[categoryPath]) {
                    map.addLayer(layerGroups[categoryPath]);
                }
            } else {
                if (layerGroups[categoryPath]) {
                    map.removeLayer(layerGroups[categoryPath]);
                }
                // Uncheck parent if no children are checked
                const siblingCheckboxes = document.querySelectorAll(`.child-checkbox[data-parent-name="${parentName}"]`);
                const anyChecked = Array.from(siblingCheckboxes).some(cb => cb.checked);
                if (!anyChecked) {
                    const parentCheckbox = document.querySelector(`.parent-checkbox[data-parent-name="${parentName}"]`);
                    if (parentCheckbox) {
                        parentCheckbox.checked = false;
                    }
                }
            }
        });
    });
}

// Update hierarchical legend
function updateHierarchicalLegend() {
    const legendContent = document.getElementById("legend-content");
    if (!legendContent) return;
    
    let legendHTML = `
        <div class="legend-header mb-3">
            <h6 class="fw-bold text-primary mb-2">
                <i class="bi bi-palette me-2"></i>Legenda Peta
            </h6>
            <p class="small text-muted mb-0">Kategori yang tersedia pada peta</p>
        </div>
        <div class="legend-hierarchy">
    `;
    
    const rootCategories = allCategoriesData.root_categories || [];
    const categoriesWithData = rootCategories.filter(cat => 
        hasDataInCategory(cat) || (cat.children && cat.children.some(child => hasDataInCategory(child)))
    );
    
    if (categoriesWithData.length === 0) {
        legendHTML += `
            <div class="text-center py-4">
                <i class="bi bi-info-circle text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2">Belum ada data untuk ditampilkan</p>
            </div>
        `;
    } else {
        categoriesWithData.forEach(rootCategory => {
            legendHTML += createLegendNode(rootCategory, true);
        });
    }
    
    legendHTML += '</div>';
    legendContent.innerHTML = legendHTML;
}

// Create legend node - Updated untuk field 'warna'
function createLegendNode(category, isRoot = false) {
    const hasChildren = category.children && category.children.length > 0;
    const categoryPath = getFullCategoryPath(category);
    const hasDirectData = layerGroups[categoryPath] && layerGroups[categoryPath].getLayers().length > 0;
    
    let html = `
        <div class="legend-item ${isRoot ? 'root-legend mb-3' : 'child-legend mb-2'}">
            <div class="d-flex align-items-center">
                <div class="legend-color me-2" 
                     style="width: ${isRoot ? '18' : '14'}px; height: ${isRoot ? '18' : '14'}px; 
                     background-color: ${category.warna || '#gray'}; border: 1px solid #ddd; border-radius: 2px;"></div>
                <span class="legend-label ${isRoot ? 'fw-bold' : 'small'}">${category.nama}</span>
                ${hasDirectData ? `<small class="text-muted ms-auto">${layerGroups[categoryPath].getLayers().length}</small>` : ''}
            </div>
    `;
    
    if (hasChildren) {
        html += '<div class="ms-3 mt-1">';
        category.children.forEach(child => {
            if (hasDataInCategory(child)) {
                html += createLegendNode(child, false);
            }
        });
        html += '</div>';
    }
    
    html += '</div>';
    return html;
}

// Get full category path
function getFullCategoryPath(category) {
    if (!category) return '';
    
    let path = [category.nama];
    let current = category;
    
    while (current.parent) {
        path.unshift(current.parent.nama);
        current = current.parent;
    }
    
    return path.join(' > ');
}

// Setup basemap list
function setupBasemapList() {
    const basemapList = document.getElementById("basemap-list");
    if (!basemapList) return;
    
    let html = `
        <div class="mb-3">
            <p class="text-muted small">Pilih jenis peta dasar yang ingin ditampilkan</p>
        </div>
    `;
    
    mapConfig.baseMapsList.forEach((bm, i) => {
        html += `
            <div class="form-check form-switch mb-3 p-3 border rounded">
                <input class="form-check-input" type="radio" role="switch" 
                       name="basemap-radio" id="bm-${bm.id}" value="${bm.id}" 
                       ${i === 4 ? "checked" : ""}>
                <label class="form-check-label fw-medium" for="bm-${bm.id}">
                    <i class="bi bi-map me-2"></i>${bm.label}
                </label>
            </div>
        `;
    });
    
    basemapList.innerHTML = html;
    
    basemapList.addEventListener("change", (e) => {
        if (e.target.name === "basemap-radio") {
            changeBaseMap(e.target.value);
        }
    });
}

// Setup download content
function setupDownloadContent() {
    const downloadContent = document.getElementById("download-content");
    if (!downloadContent) return;
    
    downloadContent.innerHTML = `
        <div class="mb-3">
            <h6 class="fw-bold text-primary mb-2">
                <i class="bi bi-download me-2"></i>Download Peta
            </h6>
            <p class="small text-muted mb-0">Download peta dalam berbagai format</p>
        </div>
        
        <div class="download-options">
            <button id="download-png" class="btn btn-outline-primary btn-sm w-100 mb-2">
                <i class="bi bi-file-earmark-image me-2"></i>Download as PNG
            </button>
            <button id="download-jpg" class="btn btn-outline-primary btn-sm w-100 mb-2">
                <i class="bi bi-file-earmark-image-fill me-2"></i>Download as JPG
            </button>
            <button id="download-geojson" class="btn btn-outline-success btn-sm w-100 mb-2">
                <i class="bi bi-file-earmark-code me-2"></i>Download GeoJSON
            </button>
            <button id="download-csv" class="btn btn-outline-info btn-sm w-100 mb-2">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Download CSV
            </button>
            <button id="download-kml" class="btn btn-outline-warning btn-sm w-100 mb-2">
                <i class="bi bi-file-earmark-text me-2"></i>Download KML
            </button>
            <button id="print-map" class="btn btn-outline-secondary btn-sm w-100">
                <i class="bi bi-printer me-2"></i>Print Map
            </button>
        </div>
    `;
    
    // Download functionality
    document.getElementById("download-png")?.addEventListener("click", () => downloadMapImage("png"));
    document.getElementById("download-jpg")?.addEventListener("click", () => downloadMapImage("jpg"));
    document.getElementById("download-geojson")?.addEventListener("click", downloadGeoJSON);
    document.getElementById("download-csv")?.addEventListener("click", downloadCSV);
    document.getElementById("download-kml")?.addEventListener("click", downloadKML);
    document.getElementById("print-map")?.addEventListener("click", printMap);
}

// Download functions
function downloadMapImage(format = "png") {
    showAlert("Memproses download gambar...", "info");
    
    setTimeout(() => {
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        canvas.width = 1200;
        canvas.height = 800;
        
        ctx.fillStyle = "#f8f9fa";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        ctx.fillStyle = "#212529";
        ctx.font = "24px Arial";
        ctx.textAlign = "center";
        ctx.fillText("WebGIS Perencanaan - Export", canvas.width / 2, 50);
        
        ctx.font = "14px Arial";
        ctx.fillText(`Exported on: ${new Date().toLocaleString()}`, canvas.width / 2, 80);
        
        const link = document.createElement("a");
        link.download = `webgis-map-${Date.now()}.${format}`;
        link.href = canvas.toDataURL(`image/${format}`);
        link.click();
        
        showAlert(`Peta berhasil didownload sebagai ${format.toUpperCase()}`, "success");
    }, 1000);
}

function downloadGeoJSON() {
    showAlert("Mengunduh data GeoJSON...", "info");
    window.location.href = "/api/export?format=geojson";
}

function downloadCSV() {
    showAlert("Mengunduh data CSV...", "info");
    window.location.href = "/api/export?format=csv";
}

function downloadKML() {
    showAlert("Mengunduh data KML...", "info");
    window.location.href = "/api/export?format=kml";
}

function printMap() {
    showAlert("Membuka dialog print...", "info");
    window.print();
}

// Setup transparency control
function setupTransparencyControl() {
    const transparencySlider = document.getElementById("transparency");
    if (transparencySlider) {
        transparencySlider.addEventListener("input", (e) => {
            const opacity = e.target.value / 100;
            Object.values(layerGroups).forEach((group) => {
                group.eachLayer((layer) => {
                    if (layer.setStyle) {
                        layer.setStyle({ fillOpacity: opacity });
                    }
                });
            });
            showAlert(`Transparansi diatur ke ${e.target.value}%`, "info");
        });
    }
}

// Setup UI controls
function setupUIControls() {
    const controls = {
        "btn-toggle-sidebar-layer": () => toggleSidebar("sidebar-layer"),
        "btn-toggle-sidebar-basemap": () => toggleSidebar("sidebar-basemap"),
        "btn-toggle-sidebar-legend": () => toggleSidebar("sidebar-legend"),
        "btn-toggle-sidebar-download": () => toggleSidebar("sidebar-download"),
        "btn-toggle-sidebar-help": () => showGuideModal(),
        "btn-fullscreen": () => toggleFullscreen(),
        "btn-default-zoom": () => resetToDefaultView(),
    };
    
    Object.entries(controls).forEach(([id, handler]) => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener("click", handler);
        }
    });
    
    // Close sidebar buttons
    const closeBtns = [
        "btn-close-sidebar-layer",
        "btn-close-sidebar-basemap", 
        "btn-close-sidebar-legend",
        "btn-close-sidebar-download"
    ];
    
    closeBtns.forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            const sidebarId = id.replace("btn-close-", "");
            btn.addEventListener("click", () => closeSidebar(sidebarId));
        }
    });
}

function toggleSidebar(sidebarId) {
    closeAllSidebars();
    const sidebar = document.getElementById(sidebarId);
    if (sidebar) {
        sidebar.classList.toggle('show');
        
        // Update button state
        const btn = document.getElementById(`btn-toggle-${sidebarId.replace('sidebar-', '')}`);
        if (btn) {
            btn.classList.toggle('active', sidebar.classList.contains('show'));
        }
    }
}

function closeSidebar(sidebarId) {
    const sidebar = document.getElementById(sidebarId);
    if (sidebar) {
        sidebar.classList.remove('show');
        
        // Update button state
        const btn = document.getElementById(`btn-toggle-${sidebarId.replace('sidebar-', '')}`);
        if (btn) {
            btn.classList.remove('active');
        }
    }
}

function closeAllSidebars() {
    ["sidebar-layer", "sidebar-basemap", "sidebar-legend", "sidebar-download"].forEach(id => {
        const sidebar = document.getElementById(id);
        if (sidebar) {
            sidebar.classList.remove('show');
        }
        
        // Update button state
        const btn = document.getElementById(`btn-toggle-${id.replace('sidebar-', '')}`);
        if (btn) {
            btn.classList.remove('active');
        }
    });
}

function toggleFullscreen() {
    if (document.fullscreenElement) {
        document.exitFullscreen();
        showAlert("Keluar dari mode fullscreen", "info");
    } else {
        document.documentElement.requestFullscreen();
        showAlert("Masuk ke mode fullscreen", "info");
    }
}

function resetToDefaultView() {
    map.setView(mapConfig.center, mapConfig.zoom);
    showAlert("Peta kembali ke posisi default", "info");
}

function showGuideModal() {
    const modal = document.getElementById("guideModal");
    if (modal) {
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        setupGuideModal();
    }
}

function setupGuideModal() {
    const guideSteps = document.querySelectorAll(".guide-step");
    const btnPrev = document.getElementById("btnPrev");
    const btnNext = document.getElementById("btnNext");
    const btnSkip = document.getElementById("btnSkip");
    const stepIndicator = document.getElementById("step-indicator");
    
    let currentStep = 1;
    const totalSteps = guideSteps.length;
    
    const controlButtons = [
        document.getElementById("btn-toggle-sidebar-help"),
        document.getElementById("btn-toggle-sidebar-legend"),
        document.getElementById("btn-toggle-sidebar-basemap"),
        document.getElementById("btn-toggle-sidebar-layer"),
        document.getElementById("btn-toggle-sidebar-download"),
        document.getElementById("btn-fullscreen"),
        document.getElementById("btn-default-zoom"),
    ];

    function showStep(step) {
        guideSteps.forEach((stepDiv) => {
            stepDiv.classList.toggle("d-none", parseInt(stepDiv.dataset.step) !== step);
        });

        btnPrev.disabled = step === 1;
        btnNext.textContent = step === totalSteps ? "Selesai" : "Selanjutnya";
        if (stepIndicator) {
            stepIndicator.textContent = `${step} / ${totalSteps}`;
        }

        // Remove all highlights
        controlButtons.forEach((btn) => {
            if (btn) btn.classList.remove("highlighted-control");
        });

        // Add highlight based on step
        const highlightMap = {
            3: 0, 4: 1, 5: 2, 6: 3, 7: 4, 8: 5, 9: 6
        };
        
        if (highlightMap[step] !== undefined && controlButtons[highlightMap[step]]) {
            controlButtons[highlightMap[step]].classList.add("highlighted-control");
        }
    }

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
            const modal = document.getElementById("guideModal");
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) modalInstance.hide();
            controlButtons.forEach((btn) => {
                if (btn) btn.classList.remove("highlighted-control");
            });
        }
    });

    btnSkip?.addEventListener("click", () => {
        const modal = document.getElementById("guideModal");
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) modalInstance.hide();
        controlButtons.forEach((btn) => {
            if (btn) btn.classList.remove("highlighted-control");
        });
    });

    showStep(currentStep);
}

function setupKeyboardShortcuts() {
    document.addEventListener("keydown", (e) => {
        if (e.target.tagName === "INPUT" || e.target.tagName === "TEXTAREA") return;
        
        switch(e.key.toLowerCase()) {
            case "h":
                if (e.ctrlKey) {
                    e.preventDefault();
                    showGuideModal();
                }
                break;
            case "l":
                if (e.ctrlKey) {
                    e.preventDefault();
                    toggleSidebar("sidebar-layer");
                }
                break;
            case "b":
                if (e.ctrlKey) {
                    e.preventDefault();
                    toggleSidebar("sidebar-basemap");
                }
                break;
            case "f11":
                e.preventDefault();
                toggleFullscreen();
                break;
            case "escape":
                closeAllSidebars();
                break;
        }
    });
}

// Click outside to close sidebars
function setupClickOutside() {
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.sidebar') && !e.target.closest('.control-buttons')) {
            if (!e.target.closest('.leaflet-control-container')) {
                setTimeout(() => {
                    const anySidebarOpen = document.querySelector('.sidebar.show');
                    if (anySidebarOpen && !document.querySelector('.modal.show')) {
                        closeAllSidebars();
                    }
                }, 100);
            }
        }
    });
}

// Initialize everything when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM loaded, initializing WebGIS...");
    
    // Replace the placeholder functions in webgis.blade.php
    if (typeof window !== 'undefined') {
        window.initMap = initMap;
        window.changeBaseMap = changeBaseMap;
        window.setupBasemapList = setupBasemapList;
        window.setupDownloadContent = setupDownloadContent;
        window.setupTransparencyControl = setupTransparencyControl;
        window.setupUIControls = setupUIControls;
        window.setupKeyboardShortcuts = setupKeyboardShortcuts;
    }
    
    // Initialize the application
    initMap();
    changeBaseMap("esri-world-imagery");
    setupBasemapList();
    setupDownloadContent();
    setupTransparencyControl();
    setupUIControls();
    setupKeyboardShortcuts();
    setupClickOutside();
    
    console.log("WebGIS initialization complete");
});// enhanced-map-app.js - Updated for Your Database Structure
console.log("Enhanced WebGIS Application loaded - Updated Version");
