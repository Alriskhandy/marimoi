console.log("map-main.js loaded");

const mapConfig = {
    center: [0.36310009945603017, 127.12398149281738],
    zoom: 8,
    baseMaps: {
        osm: {
            url: "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
            attribution:
                '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        },
        satellite: {
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}.png",
            attribution:
                "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community",
        },
        terrain: {
            url: "https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png",
            attribution:
                'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
        },
    },
    baseMapsList: [
        {
            id: "google-roadmap",
            label: "Google Map (ROADMAP)",
            url: "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
        },
        {
            id: "google-hybrid",
            label: "Google Map (Hybrid)",
            url: "https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
        },
        {
            id: "google-terrain",
            label: "Google Map (Terrain)",
            url: "https://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
        },
        {
            id: "mapquest-osm",
            label: "MapQuest OSM",
            url: "https://otile{s}.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.png",
            subdomains: ["1", "2", "3", "4"],
        },
        {
            id: "bing-satellite",
            label: "Bing Maps (Satellite)",
            url: "https://ecn.t{s}.tiles.virtualearth.net/tiles/a{q}.jpeg?g=1",
            subdomains: ["0", "1", "2", "3"],
        },
        {
            id: "esri-world-topo",
            label: "Esri World Topo Map",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}",
        },
        {
            id: "esri-world-imagery",
            label: "ESRI World Imagery",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
        },
        {
            id: "esri-world-street",
            label: "ESRI World Street Map",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}",
        },
        {
            id: "esri-gray",
            label: "ESRI Gray Map",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}",
        },
    ],
};

const API = {
    geojson: "/geojson",
    categories: "/api/categories",
    statistics: "/api/statistics",
    dbfColumns: "/api/dbf/columns",
    dbfColumnValues: "/api/dbf/column/{column}/values",
    categoryData: "/api/category/{kategori}",
    debugShapefile: "/debug-shapefile",
};

const map = L.map("map", {
    zoomControl: true, // Ensure zoom control is enabled
    attributionControl: true, // Ensure attribution control is enabled
}).setView(mapConfig.center, mapConfig.zoom);

// Inisialisasi variabel
let layerGroups = {};
let currentBaseMap = null;
function normalizeId(str) {
    return str.toLowerCase().replace(/\s+/g, "-");
}

function getStyleForCategory(kategori) {
    const colorMap = {
        Infrastruktur: { color: "#0d6efd", fillColor: "#b6d7ff" },
        Perekonomian: { color: "#198754", fillColor: "#a3e8c4" },
        Lingkungan: { color: "#fd7e14", fillColor: "#ffc582" },
        default: { color: "#6c757d", fillColor: "#adb5bd" },
    };
    return colorMap[kategori] || colorMap.default;
}

function bindPopupContent(feature, layer) {
    const props = feature.properties;
    let content = `<div class="p-2" style="max-width: 300px;"><h5 class="fw-bold mb-2 text-primary">${
        props.kategori || "Feature"
    }</h5>`;

    Object.entries(props).forEach(([key, value]) => {
        if (value && !["geometry", "id"].includes(key)) {
            const label = key
                .replace(/_/g, " ")
                .replace(/\b\w/g, (l) => l.toUpperCase());
            content += `<p class="small mb-1"><span class="fw-medium">${label}:</span> ${value}</p>`;
        }
    });

    content += `</div>`;
    layer.bindPopup(content);
}

function showAlert(message, type = "info") {
    console.log(`${type}: ${message}`);

    // Optional: Tambahkan notifikasi Bootstrap Toast
    const toastContainer = document.getElementById("toast-container");
    if (toastContainer) {
        const toast = document.createElement("div");
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.setAttribute("role", "alert");
        toast.setAttribute("aria-live", "assertive");
        toast.setAttribute("aria-atomic", "true");

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        // Hapus toast setelah ditutup
        toast.addEventListener("hidden.bs.toast", () => {
            toast.remove();
        });
    }
}

function showSidebarSection(section) {
    // Hide all sidebars
    document.querySelectorAll(".sidebar").forEach((div) => {
        div.classList.add("d-none");
    });

    // Show the selected sidebar
    const targetSidebar = document.getElementById(`sidebar-${section}`);
    if (targetSidebar) {
        targetSidebar.classList.remove("d-none");
        // Also ensure the corresponding radio button is checked
        const radioBtn = document.querySelector(
            `input[name="btnradio"][onclick="showSidebarSection('${section}')"]`
        );
        if (radioBtn) {
            radioBtn.checked = true;
        }
    }
}

// Fungsi utama untuk memuat dan menampilkan data
async function initMap() {
    try {
        const response = await fetch("/geojson");
        const geoJsonData = await response.json();

        if (!geoJsonData?.features?.length) {
            showAlert("Data GeoJSON kosong atau tidak valid", "warning");
            return;
        }

        processGeoJSONData(geoJsonData);
        updateLayerList();
    } catch (error) {
        console.error("Error:", error);
        showAlert("Gagal memuat data peta", "danger");
    }
}

// Fungsi untuk memproses data GeoJSON
function processGeoJSONData(data) {
    layerGroups = {}; // Reset layer groups

    data.features.forEach((feature) => {
        const kategori = feature.properties?.kategori || "Lainnya";
        if (!layerGroups[kategori]) {
            layerGroups[kategori] = L.layerGroup(); // Do NOT add to map by default
        }

        L.geoJSON(feature, {
            style: getStyleForCategory(kategori),
            onEachFeature: (feature, layer) => bindPopupContent(feature, layer),
        }).addTo(layerGroups[kategori]);
    });
}

// Fungsi untuk update daftar layer di sidebar
function updateLayerList() {
    const layerListDiv = document.getElementById("layer-list");
    layerListDiv.innerHTML = "";

    // Create category select dropdown
    const categorySelect = document.createElement("select");
    categorySelect.id = "category-select";
    categorySelect.className = "form-select mb-3";
    const allOption = document.createElement("option");
    allOption.value = "all";
    allOption.textContent = "Semua Kategori";
    categorySelect.appendChild(allOption);

    // Collect unique categories from layerGroups
    const categories = Object.keys(layerGroups);
    categories.forEach((kategori) => {
        const option = document.createElement("option");
        option.value = kategori;
        option.textContent = kategori;
        categorySelect.appendChild(option);
    });

    layerListDiv.appendChild(categorySelect);

    // Create list of maps (layers)
    const mapListDiv = document.createElement("div");
    mapListDiv.id = "map-list";

    categories.forEach((kategori) => {
        const layerId = `layer-${normalizeId(kategori)}`;
        const item = document.createElement("div");
        item.className = "d-flex align-items-center py-2 px-1 rounded";
        item.style.cursor = "pointer";
        item.innerHTML = `
                <input type="checkbox" 
                       id="${layerId}"
                       class="layer-checkbox form-check-input me-2" 
                       data-kategori="${kategori}" 
                       >
                <label for="${layerId}" class="form-check-label small" style="cursor: pointer;">${kategori}</label>
            `;

        // Tambahkan hover effect
        item.addEventListener("mouseenter", () => {
            item.classList.add("bg-light");
        });
        item.addEventListener("mouseleave", () => {
            item.classList.remove("bg-light");
        });

        mapListDiv.appendChild(item);
    });

    layerListDiv.appendChild(mapListDiv);

    // Event listener for category select to filter map list
    categorySelect.addEventListener("change", (e) => {
        const selectedCategory = e.target.value;
        const mapItems = mapListDiv.querySelectorAll("div");
        mapItems.forEach((item) => {
            const checkbox = item.querySelector("input.layer-checkbox");
            if (!checkbox) return;
            const layerCategory = checkbox.dataset.kategori;
            if (
                selectedCategory === "all" ||
                layerCategory === selectedCategory
            ) {
                item.style.display = "flex";
            } else {
                item.style.display = "none";
                if (checkbox.checked) {
                    checkbox.checked = false;
                    map.removeLayer(layerGroups[layerCategory]);
                }
            }
        });
    });

    // Event listener for layer checkboxes
    mapListDiv.addEventListener("change", (e) => {
        if (e.target.classList.contains("layer-checkbox")) {
            const kategori = e.target.dataset.kategori;
            e.target.checked
                ? map.addLayer(layerGroups[kategori])
                : map.removeLayer(layerGroups[kategori]);
        }
    });
}

// Panggil adjustMapHeight saat halaman dimuat dan saat ukuran jendela berubah
window.addEventListener("load", () => {
    adjustMapHeight();
    setSidebarPositionLeft();
});

window.addEventListener("resize", () => {
    adjustMapHeight();
});

function setSidebarPositionLeft() {
    document.querySelectorAll(".sidebar").forEach((div) => {
        div.style.right = "auto";
        div.style.left = "20px";
    });
}

function adjustMapHeight() {
    const mapElement = document.getElementById("map");
    if (!mapElement) return;

    const headerHeight = 60; // Adjust to header height
    const marginTop = 20; // Additional top margin if needed
    const windowHeight = window.innerHeight;

    const mapHeight = windowHeight - headerHeight - marginTop;
    mapElement.style.height = `${mapHeight}px`;
}

function toggleSidebar(sidebarId) {
    const sidebar = document.getElementById(sidebarId);
    if (!sidebar) return;
    if (sidebar.style.display === "block" || sidebar.style.display === "") {
        sidebar.style.display = "none";
    } else {
        // Hide other sidebars
        ["sidebar-layer", "sidebar-basemap", "sidebar-legend"].forEach((id) => {
            if (id !== sidebarId) {
                const otherSidebar = document.getElementById(id);
                if (otherSidebar) otherSidebar.style.display = "none";
            }
        });
        sidebar.style.display = "block";
    }
}

function filterLayersByCategory(category) {
    const layerCheckboxes = document.querySelectorAll(".layer-checkbox");

    layerCheckboxes.forEach((checkbox) => {
        const layerCategory = checkbox.dataset.kategori;
        const layerItem = checkbox.closest("div");

        if (category === "all" || layerCategory === category) {
            layerItem.style.display = "flex";
        } else {
            layerItem.style.display = "none";
            // Hide layer from map if not matching filter
            if (checkbox.checked) {
                checkbox.checked = false;
                map.removeLayer(layerGroups[layerCategory]);
            }
        }
    });
}

function populateBaseMapList() {
    const basemapListDiv = document.getElementById("basemap-list");
    basemapListDiv.innerHTML = "";

    // Ambil list baseMaps dari mapConfig
    const baseMaps = mapConfig.baseMapsList || [];

    baseMaps.forEach((baseMap, index) => {
        const baseMapId = `basemap-${baseMap.id}`;
        const item = document.createElement("div");
        item.className = "form-check";

        item.innerHTML = `
            <input class="form-check-input" type="radio" name="basemap-radio" id="${baseMapId}" value="${
            baseMap.id
        }" ${index === 0 ? "checked" : ""}>
            <label class="form-check-label" for="${baseMapId}">${
            baseMap.label
        }</label>
        `;

        basemapListDiv.appendChild(item);
    });

    basemapListDiv.addEventListener("change", (e) => {
        if (e.target.name === "basemap-radio") {
            changeBaseMap(e.target.value);
        }
    });
}

function populateLegendList() {
    const legendListDiv = document.getElementById("legend-list");
    legendListDiv.innerHTML = "";
    // TODO: Implementasi isi legend sesuai kebutuhan
}

// Fungsi utama untuk memuat dan menampilkan data
async function initMap() {
    try {
        const response = await fetch("/geojson");
        const geoJsonData = await response.json();

        if (!geoJsonData?.features?.length) {
            showAlert("Data GeoJSON kosong atau tidak valid", "warning");
            return;
        }

        processGeoJSONData(geoJsonData);
        updateLayerList();
    } catch (error) {
        console.error("Error:", error);
        showAlert("Gagal memuat data peta", "danger");
    }
}

// Fungsi untuk memproses data GeoJSON
function processGeoJSONData(data) {
    layerGroups = {}; // Reset layer groups

    data.features.forEach((feature) => {
        const kategori = feature.properties?.kategori || "Lainnya";
        if (!layerGroups[kategori]) {
            layerGroups[kategori] = L.layerGroup(); // Do NOT add to map by default
        }

        L.geoJSON(feature, {
            style: getStyleForCategory(kategori),
            onEachFeature: (feature, layer) => bindPopupContent(feature, layer),
        }).addTo(layerGroups[kategori]);
    });
}

// Fungsi untuk update daftar layer di sidebar
function updateLayerList() {
    const layerListDiv = document.getElementById("layer-list");
    layerListDiv.innerHTML = "";

    // Create category select dropdown
    const categorySelect = document.createElement("select");
    categorySelect.id = "category-select";
    categorySelect.className = "form-select mb-3";
    const allOption = document.createElement("option");
    allOption.value = "all";
    allOption.textContent = "Semua Kategori";
    categorySelect.appendChild(allOption);

    // Collect unique categories from layerGroups
    const categories = Object.keys(layerGroups);
    categories.forEach((kategori) => {
        const option = document.createElement("option");
        option.value = kategori;
        option.textContent = kategori;
        categorySelect.appendChild(option);
    });

    layerListDiv.appendChild(categorySelect);

    // Create list of maps (layers)
    const mapListDiv = document.createElement("div");
    mapListDiv.id = "map-list";

    categories.forEach((kategori) => {
        const layerId = `layer-${normalizeId(kategori)}`;
        const item = document.createElement("div");
        item.className = "d-flex align-items-center py-2 px-1 rounded";
        item.style.cursor = "pointer";
        item.innerHTML = `
                <input type="checkbox" 
                       id="${layerId}"
                       class="layer-checkbox form-check-input me-2" 
                       data-kategori="${kategori}" 
                       >
                <label for="${layerId}" class="form-check-label small" style="cursor: pointer;">${kategori}</label>
            `;

        // Tambahkan hover effect
        item.addEventListener("mouseenter", () => {
            item.classList.add("bg-light");
        });
        item.addEventListener("mouseleave", () => {
            item.classList.remove("bg-light");
        });

        mapListDiv.appendChild(item);
    });

    layerListDiv.appendChild(mapListDiv);

    // Event listener for category select to filter map list
    categorySelect.addEventListener("change", (e) => {
        const selectedCategory = e.target.value;
        const mapItems = mapListDiv.querySelectorAll("div");
        mapItems.forEach((item) => {
            const checkbox = item.querySelector("input.layer-checkbox");
            if (!checkbox) return;
            const layerCategory = checkbox.dataset.kategori;
            if (
                selectedCategory === "all" ||
                layerCategory === selectedCategory
            ) {
                item.style.display = "flex";
            } else {
                item.style.display = "none";
                if (checkbox.checked) {
                    checkbox.checked = false;
                    map.removeLayer(layerGroups[layerCategory]);
                }
            }
        });
    });

    // Event listener for layer checkboxes
    mapListDiv.addEventListener("change", (e) => {
        if (e.target.classList.contains("layer-checkbox")) {
            const kategori = e.target.dataset.kategori;
            e.target.checked
                ? map.addLayer(layerGroups[kategori])
                : map.removeLayer(layerGroups[kategori]);
        }
    });
}

// Panggil adjustMapHeight saat halaman dimuat dan saat ukuran jendela berubah
window.addEventListener("load", () => {
    adjustMapHeight();
    setSidebarPositionLeft();
});

window.addEventListener("resize", () => {
    adjustMapHeight();
});

function changeBaseMap(baseMapId) {
    if (currentBaseMap) {
        map.removeLayer(currentBaseMap);
    }

    const config = mapConfig.baseMapsList.find((bm) => bm.id === baseMapId);
    if (config) {
        currentBaseMap = L.tileLayer(config.url, {
            subdomains: config.subdomains || [],
            maxZoom: 20,
        }).addTo(map);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    // Event listener untuk toggle section (jika ada)
    document.querySelectorAll("[data-toggle-section]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const section = btn.dataset.toggleSection;
            const sectionElement = document.getElementById(
                `${section}-section`
            );
            if (sectionElement) {
                sectionElement.classList.toggle("d-none");
            }
        });
    });

    // Event listener for transparency slider
    const transparencySlider = document.getElementById("transparency");
    if (transparencySlider) {
        transparencySlider.addEventListener("input", (e) => {
            const opacity = e.target.value / 100;
            Object.values(layerGroups).forEach((layerGroup) => {
                layerGroup.eachLayer((layer) => {
                    if (layer.setStyle) {
                        layer.setStyle({ fillOpacity: opacity });
                    }
                });
            });
        });
    }

    // Event listener for location select
    const locationSelect = document.getElementById("location-select");
    if (locationSelect) {
        // TODO: Fetch location options from API if needed
        locationSelect.addEventListener("change", (e) => {
            // Implement filtering layers by location if applicable
            // For now, just log selected values
            console.log(
                "Selected locations:",
                Array.from(e.target.selectedOptions).map((o) => o.value)
            );
        });
    }

    // Event listener for layer search input
    const layerSearchInput = document.getElementById("layer-search");
    if (layerSearchInput) {
        layerSearchInput.addEventListener("input", (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const layerListDiv = document.getElementById("layer-list");
            if (!layerListDiv) return;
            const items = layerListDiv.querySelectorAll(
                "div.d-flex.align-items-center"
            );
            items.forEach((item) => {
                const label = item.querySelector("label");
                if (
                    label &&
                    label.textContent.toLowerCase().includes(searchTerm)
                ) {
                    item.style.display = "flex";
                } else {
                    item.style.display = "none";
                }
            });
        });
    }

    // Event listener for sidebar close button
    const btnCloseSidebar = document.getElementById("btn-close-sidebar");
    if (btnCloseSidebar) {
        btnCloseSidebar.addEventListener("click", () => {
            const sidebar = document.getElementById("sidebar-layer");
            if (sidebar) {
                sidebar.style.display = "none";
            }
            // Also uncheck the toggle button for layer sidebar
            const toggleBtn = document.getElementById(
                "btn-toggle-sidebar-layer"
            );
            if (toggleBtn) {
                toggleBtn.classList.remove("active");
            }
        });
    }

    // Event listeners for sidebar control buttons
    const btnToggleSidebarLayer = document.getElementById(
        "btn-toggle-sidebar-layer"
    );
    const btnToggleSidebarBasemap = document.getElementById(
        "btn-toggle-sidebar-basemap"
    );
    const btnToggleSidebarLegend = document.getElementById(
        "btn-toggle-sidebar-legend"
    );

    function toggleSidebar(sidebarId) {
        const sidebar = document.getElementById(sidebarId);
        if (!sidebar) return;
        if (sidebar.style.display === "block" || sidebar.style.display === "") {
            sidebar.style.display = "none";
        } else {
            // Hide other sidebars
            ["sidebar-layer", "sidebar-basemap", "sidebar-legend"].forEach(
                (id) => {
                    if (id !== sidebarId) {
                        const otherSidebar = document.getElementById(id);
                        if (otherSidebar) otherSidebar.style.display = "none";
                    }
                }
            );
            sidebar.style.display = "block";
        }
    }

    if (btnToggleSidebarLayer) {
        btnToggleSidebarLayer.addEventListener("click", () => {
            toggleSidebar("sidebar-layer");
        });
    }
    if (btnToggleSidebarBasemap) {
        btnToggleSidebarBasemap.addEventListener("click", () => {
            toggleSidebar("sidebar-basemap");
            // Populate base map list when sidebar is shown
            populateBaseMapList();
            // Ensure sidebar is visible
            const sidebarBasemap = document.getElementById("sidebar-basemap");
            if (sidebarBasemap) {
                sidebarBasemap.style.display = "block";
            }
        });
    }
    if (btnToggleSidebarLegend) {
        btnToggleSidebarLegend.addEventListener("click", () => {
            toggleSidebar("sidebar-legend");
        });
    }

    initMap();
    changeBaseMap("osm"); // Ganti dengan base map default yang diinginkan

    // Populate base map list and legend list after map initialization
    populateBaseMapList();
    populateLegendList();

    // Initialize Bootstrap tooltips for control buttons
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Inisialisasi peta saat dokumen siap
import {
    populateBaseMapList,
    populateLegendList,
    changeBaseMap,
} from "./map-utils.js";

document.addEventListener("DOMContentLoaded", () => {
    // Event listener untuk toggle section (jika ada)
    document.querySelectorAll("[data-toggle-section]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const section = btn.dataset.toggleSection;
            const sectionElement = document.getElementById(
                `${section}-section`
            );
            if (sectionElement) {
                sectionElement.classList.toggle("d-none");
            }
        });
    });

    // Event listener for transparency slider
    const transparencySlider = document.getElementById("transparency");
    if (transparencySlider) {
        transparencySlider.addEventListener("input", (e) => {
            const opacity = e.target.value / 100;
            Object.values(layerGroups).forEach((layerGroup) => {
                layerGroup.eachLayer((layer) => {
                    if (layer.setStyle) {
                        layer.setStyle({ fillOpacity: opacity });
                    }
                });
            });
        });
    }

    // Event listener for location select
    const locationSelect = document.getElementById("location-select");
    if (locationSelect) {
        // TODO: Fetch location options from API if needed
        locationSelect.addEventListener("change", (e) => {
            // Implement filtering layers by location if applicable
            // For now, just log selected values
            console.log(
                "Selected locations:",
                Array.from(e.target.selectedOptions).map((o) => o.value)
            );
        });
    }

    // Event listener for layer search input
    const layerSearchInput = document.getElementById("layer-search");
    if (layerSearchInput) {
        layerSearchInput.addEventListener("input", (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const layerListDiv = document.getElementById("layer-list");
            if (!layerListDiv) return;
            const items = layerListDiv.querySelectorAll(
                "div.d-flex.align-items-center"
            );
            items.forEach((item) => {
                const label = item.querySelector("label");
                if (
                    label &&
                    label.textContent.toLowerCase().includes(searchTerm)
                ) {
                    item.style.display = "flex";
                } else {
                    item.style.display = "none";
                }
            });
        });
    }

    // Event listener for sidebar close button
    const btnCloseSidebar = document.getElementById("btn-close-sidebar");
    if (btnCloseSidebar) {
        btnCloseSidebar.addEventListener("click", () => {
            const sidebar = document.getElementById("sidebar-layer");
            if (sidebar) {
                sidebar.style.display = "none";
            }
            // Also uncheck the toggle button for layer sidebar
            const toggleBtn = document.getElementById(
                "btn-toggle-sidebar-layer"
            );
            if (toggleBtn) {
                toggleBtn.classList.remove("active");
            }
        });
    }

    // Event listeners for sidebar control buttons
    const btnToggleSidebarLayer = document.getElementById(
        "btn-toggle-sidebar-layer"
    );
    const btnToggleSidebarBasemap = document.getElementById(
        "btn-toggle-sidebar-basemap"
    );
    const btnToggleSidebarLegend = document.getElementById(
        "btn-toggle-sidebar-legend"
    );

    function toggleSidebar(sidebarId) {
        const sidebar = document.getElementById(sidebarId);
        if (!sidebar) return;
        if (sidebar.style.display === "block" || sidebar.style.display === "") {
            sidebar.style.display = "none";
        } else {
            // Hide other sidebars
            ["sidebar-layer", "sidebar-basemap", "sidebar-legend"].forEach(
                (id) => {
                    if (id !== sidebarId) {
                        const otherSidebar = document.getElementById(id);
                        if (otherSidebar) otherSidebar.style.display = "none";
                    }
                }
            );
            sidebar.style.display = "block";
        }
    }

    if (btnToggleSidebarLayer) {
        btnToggleSidebarLayer.addEventListener("click", () => {
            toggleSidebar("sidebar-layer");
        });
    }
    if (btnToggleSidebarBasemap) {
        btnToggleSidebarBasemap.addEventListener("click", () => {
            toggleSidebar("sidebar-basemap");
            // Populate base map list when sidebar is shown
            populateBaseMapList();
            // Ensure sidebar is visible
            const sidebarBasemap = document.getElementById("sidebar-basemap");
            if (sidebarBasemap) {
                sidebarBasemap.style.display = "block";
            }
        });
    }
    if (btnToggleSidebarLegend) {
        btnToggleSidebarLegend.addEventListener("click", () => {
            toggleSidebar("sidebar-legend");
        });
    }

    initMap();
    changeBaseMap("osm"); // Ganti dengan base map default yang diinginkan

    // Populate base map list and legend list after map initialization
    populateBaseMapList();
    populateLegendList();

    // Initialize Bootstrap tooltips for control buttons
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
