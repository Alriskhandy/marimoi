// map-app.js
console.log("map-app.js loaded");

const mapConfig = {
    center: [0.36310009945603017, 127.12398149281738],
    zoom: 8,
    baseMapsList: [
        {
            id: "osm",
            label: "OpenStreetMap",
            url: "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
        },
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
            id: "esri-world-imagery",
            label: "ESRI World Imagery",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
        },
    ],
};

const map = L.map("map", {
    zoomControl: true,
    attributionControl: true,
}).setView(mapConfig.center, mapConfig.zoom);

let layerGroups = {};
let currentBaseMap = null;

function getStyleForCategory(kategori) {
    const colorMap = {
        Infrastruktur: { color: "#172953", fillColor: "#B6D8C7" },
        Perekonomian: { color: "#172953", fillColor: "#F2A44E" },
        Lingkungan: { color: "#172953", fillColor: "#C4D17C" },
        default: { color: "#172953", fillColor: "#ECE6D6" },
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

function changeBaseMap(baseMapId) {
    if (currentBaseMap) map.removeLayer(currentBaseMap);
    const config = mapConfig.baseMapsList.find((bm) => bm.id === baseMapId);
    if (config) {
        currentBaseMap = L.tileLayer(config.url, {
            subdomains: config.subdomains || [],
            maxZoom: 20,
        }).addTo(map);
    }
}

async function initMap() {
    try {
        const response = await fetch("/geojson");
        const geoJsonData = await response.json();
        if (!geoJsonData?.features?.length)
            return showAlert("Data GeoJSON kosong", "warning");

        layerGroups = {};
        geoJsonData.features.forEach((feature) => {
            const kategori = feature.properties?.kategori || "Lainnya";
            if (!layerGroups[kategori]) layerGroups[kategori] = L.layerGroup();
            L.geoJSON(feature, {
                style: getStyleForCategory(kategori),
                onEachFeature: (f, l) => bindPopupContent(f, l),
            }).addTo(layerGroups[kategori]);
        });

        updateLayerList();
    } catch (error) {
        console.error("Error:", error);
        showAlert("Gagal memuat data peta", "danger");
    }
}

function updateLayerList() {
    const listDiv = document.getElementById("layer-list");
    listDiv.innerHTML = "";

    const select = document.createElement("select");
    select.className = "form-select mb-3";
    select.innerHTML = `<option value="all">Semua Kategori</option>`;
    Object.keys(layerGroups).forEach((kategori) => {
        select.innerHTML += `<option value="${kategori}">${kategori}</option>`;
    });

    listDiv.appendChild(select);

    const list = document.createElement("div");
    Object.keys(layerGroups).forEach((kategori) => {
        const id = `layer-${kategori.toLowerCase().replace(/\s+/g, "-")}`;
        list.innerHTML += `
      <div class="d-flex align-items-center py-1">
        <input type="checkbox" id="${id}" class="form-check-input me-2" data-kategori="${kategori}" />
        <label for="${id}" class="form-check-label small">${kategori}</label>
      </div>`;
    });

    listDiv.appendChild(list);

    select.addEventListener("change", (e) => {
        const val = e.target.value;
        list.querySelectorAll("input").forEach((input) => {
            const match = val === "all" || input.dataset.kategori === val;
            input.parentElement.style.display = match ? "flex" : "none";
            if (!match && input.checked) {
                map.removeLayer(layerGroups[input.dataset.kategori]);
                input.checked = false;
            }
        });
    });

    list.addEventListener("change", (e) => {
        if (e.target.type === "checkbox") {
            const kat = e.target.dataset.kategori;
            e.target.checked
                ? map.addLayer(layerGroups[kat])
                : map.removeLayer(layerGroups[kat]);
        }
    });
}

function setupUI() {
    document.getElementById("transparency")?.addEventListener("input", (e) => {
        const val = e.target.value / 100;
        Object.values(layerGroups).forEach((g) =>
            g.eachLayer((l) => l.setStyle?.({ fillOpacity: val }))
        );
    });

    const basemapList = document.getElementById("basemap-list");
    mapConfig.baseMapsList.forEach((bm, i) => {
        basemapList.innerHTML += `
      <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="radio" role="switch" name="basemap-radio" id="bm-${
            bm.id
        }" value="${bm.id}" ${i === 0 ? "checked" : ""}>
        <label class="form-check-label" for="bm-${bm.id}">${bm.label}</label>
      </div>`;
    });

    basemapList.addEventListener("change", (e) => {
        if (e.target.name === "basemap-radio") changeBaseMap(e.target.value);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initMap();
    changeBaseMap("esri-world-imagery");
    setupUI();

    // Sidebar toggle buttons
    const btnToggleLayer = document.getElementById("btn-toggle-sidebar-layer");
    const btnToggleBasemap = document.getElementById("btn-toggle-sidebar-basemap");
    const btnToggleLegend = document.getElementById("btn-toggle-sidebar-legend");

    const sidebarLayer = document.getElementById("sidebar-layer");
    const sidebarBasemap = document.getElementById("sidebar-basemap");
    const sidebarLegend = document.getElementById("sidebar-legend");

    const btnCloseLayer = document.getElementById("btn-close-sidebar-layer");
    const btnCloseBasemap = document.getElementById("btn-close-sidebar-basemap");
    const btnCloseLegend = document.getElementById("btn-close-sidebar-legend");

    function closeAllSidebars() {
        sidebarLayer.style.display = "none";
        sidebarBasemap.style.display = "none";
        sidebarLegend.style.display = "none";
    }

    btnToggleLayer?.addEventListener("click", () => {
        const isVisible = sidebarLayer.style.display === "block";
        closeAllSidebars();
        sidebarLayer.style.display = isVisible ? "none" : "block";
    });

    btnToggleBasemap?.addEventListener("click", () => {
        const isVisible = sidebarBasemap.style.display === "block";
        closeAllSidebars();
        sidebarBasemap.style.display = isVisible ? "none" : "block";
    });

    btnToggleLegend?.addEventListener("click", () => {
        const isVisible = sidebarLegend.style.display === "block";
        closeAllSidebars();
        sidebarLegend.style.display = isVisible ? "none" : "block";
    });

    btnCloseLayer?.addEventListener("click", () => {
        sidebarLayer.style.display = "none";
    });

    btnCloseBasemap?.addEventListener("click", () => {
        sidebarBasemap.style.display = "none";
    });

    btnCloseLegend?.addEventListener("click", () => {
        sidebarLegend.style.display = "none";
    });

    // Layer search filter
    const layerSearchInput = document.getElementById("layer-search");
    const layerListDiv = document.getElementById("layer-list");

    layerSearchInput?.addEventListener("input", (e) => {
        const searchTerm = e.target.value.toLowerCase();
        if (!layerListDiv) return;

        // Filter checkboxes by label text
        const checkboxes = layerListDiv.querySelectorAll("input[type='checkbox']");
        checkboxes.forEach((checkbox) => {
            const label = layerListDiv.querySelector(`label[for='${checkbox.id}']`);
            if (label) {
                const text = label.textContent.toLowerCase();
                const match = text.includes(searchTerm);
                checkbox.parentElement.style.display = match ? "flex" : "none";
                if (!match && checkbox.checked) {
                    map.removeLayer(layerGroups[checkbox.dataset.kategori]);
                    checkbox.checked = false;
                }
            }
        });
    });
});
