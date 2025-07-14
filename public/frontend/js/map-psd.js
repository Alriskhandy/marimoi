console.log("map-psd.js loaded");

// Fungsi umum dan tampilan
const mapConfig = {
    center: [0.735485, 128.028201],
    zoom: 7,
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
let availableYears = [];
let selectedYear = null;
let selectedCategories = new Set();

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
        <input
          class="form-check-input"
          type="radio"
          role="switch"
          name="basemap-radio"
          id="bm-${bm.id}"
          value="${bm.id}"
          ${i === 0 ? "checked" : ""}
        />
        <label class="form-check-label" for="bm-${bm.id}">${bm.label}</label>
      </div>`;
    });

    basemapList.addEventListener("change", (e) => {
        if (e.target.name === "basemap-radio") changeBaseMap(e.target.value);
    });
}

// Render layer list with categories and years checkboxes
function renderLayerList(categories, years, selectedYear) {
    const layerListDiv = document.getElementById("layer-list");
    if (!layerListDiv) return;

    layerListDiv.innerHTML = "";

    if (selectedYear === "all") {
        categories.forEach((category) => {
            const categoryDiv = document.createElement("div");
            categoryDiv.className = "mb-3";

            const categoryLabel = document.createElement("div");
            categoryLabel.className = "fw-bold mb-1";
            categoryLabel.textContent = category.nama;
            categoryDiv.appendChild(categoryLabel);

            years.forEach((year) => {
                const yearId = `year-${category.id}-${year}`;
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.className = "form-check-input me-1";
                checkbox.id = yearId;
                checkbox.dataset.category = category.nama;
                checkbox.dataset.year = year;
                checkbox.checked = true;

                checkbox.addEventListener("change", (e) => {
                    const cat = e.target.dataset.category;
                    const yr = e.target.dataset.year;
                    const key = `${cat}-${yr}`;
                    if (e.target.checked) {
                        selectedCategories.add(key);
                    } else {
                        selectedCategories.delete(key);
                    }
                    updateMapLayers();
                });

                const label = document.createElement("label");
                label.className = "form-check-label me-3";
                label.htmlFor = yearId;
                label.textContent = year;

                const wrapper = document.createElement("div");
                wrapper.className = "form-check form-check-inline";
                wrapper.appendChild(checkbox);
                wrapper.appendChild(label);

                categoryDiv.appendChild(wrapper);
            });

            layerListDiv.appendChild(categoryDiv);
        });
    } else {
        categories.forEach((category) => {
            const categoryId = `category-${category.id}`;
            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.className = "form-check-input me-1";
            checkbox.id = categoryId;
            checkbox.dataset.category = category.nama;
            checkbox.checked = true;

            checkbox.addEventListener("change", (e) => {
                const cat = e.target.dataset.category;
                if (e.target.checked) {
                    selectedCategories.add(cat);
                } else {
                    selectedCategories.delete(cat);
                }
                updateMapLayers();
            });

            const label = document.createElement("label");
            label.className = "form-check-label";
            label.htmlFor = categoryId;
            label.textContent = category.nama;

            const wrapper = document.createElement("div");
            wrapper.className = "form-check mb-2";
            wrapper.appendChild(checkbox);
            wrapper.appendChild(label);

            layerListDiv.appendChild(wrapper);
        });
    }
}

// Update map layers based on selected categories and years
function updateMapLayers() {
    Object.values(layerGroups).forEach((group) => map.removeLayer(group));
    layerGroups = {};

    if (selectedYear === "all") {
        selectedCategories.forEach((key) => {
            const [category, year] = key.split("-");
            fetchMapData(year, category);
        });
    } else {
        selectedCategories.forEach((category) => {
            fetchMapData(selectedYear, category);
        });
    }
}

// Modified fetchMapData to accept year and optional category
async function fetchMapData(year = "all", category = null) {
    // Remove any existing layers
    Object.values(layerGroups).forEach((group) => map.removeLayer(group));
    layerGroups = {};

    let url = "/api/psd/geojson?";
    if (year && year !== "all") {
        url += `tahun=${year}&`;
    }
    if (category) {
        url += `kategori=${encodeURIComponent(category)}&`;
    }
    url = url.slice(0, -1); // Remove trailing &

    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        if (data.features) {
            const newLayerGroup = L.layerGroup().addTo(map);
            L.geoJSON(data, {
                style: (feature) =>
                    getStyleForCategory(feature.properties.kategori),
                onEachFeature: (feature, layer) =>
                    bindPopupContent(feature, layer),
            }).addTo(newLayerGroup);

            const key = category ? `${category}-${year}` : year;
            layerGroups[key] = newLayerGroup;
        }
    } catch (error) {
        console.error("Error fetching map data:", error);
        showAlert("Gagal memuat data peta. Silakan coba lagi.", "danger");
    }
}

// Handle year select change
document.getElementById("yearSelect").addEventListener("change", function () {
    selectedYear = this.value;
    selectedCategories.clear();
    if (selectedYear === "all") {
        window.psdCategories.forEach((cat) => {
            availableYears.forEach((year) => {
                selectedCategories.add(`${cat.nama}-${year}`);
            });
        });
    } else {
        window.psdCategories.forEach((cat) => {
            selectedCategories.add(cat.nama);
        });
    }
    renderLayerList(window.psdCategories, availableYears, selectedYear);
    updateMapLayers();
});

document.addEventListener("DOMContentLoaded", () => {
    changeBaseMap("esri-world-imagery");
    setupUI();

    // Fetch available years from API or from blade variable
    // For now, assume availableYears is populated from blade via window.availableYears
    if (window.availableYears) {
        availableYears = window.availableYears;
    } else {
        // fallback: fetch from API
        fetch("/api/psd/years")
            .then((res) => res.json())
            .then((data) => {
                availableYears = data.years || [];
                // Initialize selectedYear and categories
                selectedYear =
                    document.getElementById("yearSelect").value || "all";
                if (selectedYear === "all") {
                    window.psdCategories.forEach((cat) => {
                        availableYears.forEach((year) => {
                            selectedCategories.add(`${cat.nama}-${year}`);
                        });
                    });
                } else {
                    window.psdCategories.forEach((cat) => {
                        selectedCategories.add(cat.nama);
                    });
                }
                renderLayerList(
                    window.psdCategories,
                    availableYears,
                    selectedYear
                );
                updateMapLayers();
            });
    }

    // If availableYears already set, initialize UI
    if (availableYears.length > 0) {
        selectedYear = document.getElementById("yearSelect").value || "all";
        if (selectedYear === "all") {
            window.psdCategories.forEach((cat) => {
                availableYears.forEach((year) => {
                    selectedCategories.add(`${cat.nama}-${year}`);
                });
            });
        } else {
            window.psdCategories.forEach((cat) => {
                selectedCategories.add(cat.nama);
            });
        }
        renderLayerList(window.psdCategories, availableYears, selectedYear);
        updateMapLayers();
    }
});

// Fungsi untuk mengecek apakah sudah dalam mode fullscreen
function isFullscreen() {
    return (
        document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement
    );
}

// Fungsi untuk masuk dan keluar dari mode fullscreen
function toggleFullscreen() {
    if (isFullscreen()) {
        // Keluar dari fullscreen
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            // Untuk Safari
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            // Untuk Firefox
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            // Untuk IE/Edge
            document.msExitFullscreen();
        }
    } else {
        // Masuk ke fullscreen
        const elem = document.documentElement; // Bisa diubah dengan elemen lain jika perlu
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            // Untuk Safari
            elem.webkitRequestFullscreen();
        } else if (elem.mozRequestFullScreen) {
            // Untuk Firefox
            elem.mozRequestFullScreen();
        } else if (elem.msRequestFullscreen) {
            // Untuk IE/Edge
            elem.msRequestFullscreen();
        }
    }
}

// Ganti Peta Dasar
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
    content += `<button type="button" class="btn btn-success btn-sm mt-2">Berikan Tanggapan</button></div>`;
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

// Fungsi untuk mengambil data berdasarkan tahun
async function fetchMapData(year) {
    // Remove existing layers
    Object.values(layerGroups).forEach((group) => map.removeLayer(group));
    layerGroups = {};

    // Build URL with handling for "all" option
    let url = "/api/psd/geojson?";
    if (year && year !== "all") {
        url += `tahun=${year}&`;
    }
    url = url.slice(0, -1); // Remove trailing & or ?

    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        if (data.features) {
            // Create a new layer group for the fetched data
            const newLayerGroup = L.layerGroup().addTo(map);

            // Add geojson features to the layer group
            L.geoJSON(data, {
                style: (feature) =>
                    getStyleForCategory(feature.properties.kategori),
                onEachFeature: (feature, layer) =>
                    bindPopupContent(feature, layer),
            }).addTo(newLayerGroup);

            // Store the new layer group
            const key = year;
            layerGroups[key] = newLayerGroup;
        }
    } catch (error) {
        console.error("Error fetching map data:", error);
        showAlert("Gagal memuat data peta. Silakan coba lagi.", "danger");
    }
}

// Menangani perubahan pada tahun
document.getElementById("yearSelect").addEventListener("change", function () {
    const year = this.value; // Mengambil tahun yang dipilih
    fetchMapData(year); // Mengambil data berdasarkan tahun
});

let defaultYear = "all";

// Remove initial fetchMapData call to prevent loading layers on page load
// fetchMapData(defaultCategory, defaultYear);

document.addEventListener("DOMContentLoaded", () => {
    // Removed initMap() call because map is initialized in this file
    changeBaseMap("esri-world-imagery");
    setupUI();

    // Hide layer-list sidebar since category selection is removed
    const layerListDiv = document.getElementById("layer-list");
    if (layerListDiv) {
        layerListDiv.style.display = "none";
    }

    // Fungsi untuk menutup semua sidebar
    function closeAllSidebars() {
        sidebarLayer.style.display = "none";
        sidebarBasemap.style.display = "none";
        sidebarLegend.style.display = "none";
        sidebarDownload.style.display = "none";
        modalHelp.style.display = "none";
    }

    // Fungsi untuk langkah-langkah panduan (Help)
    function showStep(step) {
        guideSteps.forEach((stepDiv) => {
            stepDiv.classList.toggle(
                "d-none",
                parseInt(stepDiv.dataset.step) !== step
            );
        });

        btnPrev.disabled = step === 1;
        btnNext.textContent = step === totalSteps ? "Finish" : "Next";

        // Hapus highlight dari semua tombol kontrol
        controlButtons.forEach((btn) => {
            btn.classList.remove("highlighted-control");
            btn.style.position = "";
            btn.style.zIndex = "";
            btn.style.padding = "";
        });

        switch (step) {
            case 1:
                break;
            case 2:
                break;
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
            default:
                break;
        }
    }

    // Sidebar toggle buttons
    const btnToggleLayer = document.getElementById("btn-toggle-sidebar-layer");
    const btnToggleBasemap = document.getElementById(
        "btn-toggle-sidebar-basemap"
    );
    const btnToggleLegend = document.getElementById(
        "btn-toggle-sidebar-legend"
    );
    const btnToggleDownload = document.getElementById(
        "btn-toggle-sidebar-download"
    );
    const btnFullscreen = document.getElementById("btn-fullscreen");
    const btnDefaultZoom = document.getElementById("btn-default-zoom");

    // Panduan awal dengan modal dan highlight tombol kontrol
    const guideModal = document.getElementById("guideModal");
    const guideSteps = document.querySelectorAll(".guide-step");
    const btnPrev = document.getElementById("btnPrev");
    const btnNext = document.getElementById("btnNext");

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

    // Modal Help Control
    const btnToggleHelp = document.getElementById("btn-toggle-sidebar-help");
    const btnSkip = document.getElementById("btnSkip");

    const sidebarLayer = document.getElementById("sidebar-layer");
    const sidebarBasemap = document.getElementById("sidebar-basemap");
    const sidebarLegend = document.getElementById("sidebar-legend");
    const sidebarDownload = document.getElementById("sidebar-download");
    const modalHelp = document.getElementById("guideModal");

    const btnCloseLayer = document.getElementById("btn-close-sidebar-layer");
    const btnCloseBasemap = document.getElementById(
        "btn-close-sidebar-basemap"
    );
    const btnCloseLegend = document.getElementById("btn-close-sidebar-legend");
    const btnCloseDownload = document.getElementById(
        "btn-close-sidebar-download"
    );

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

    btnToggleDownload?.addEventListener("click", () => {
        const isVisible = sidebarDownload.style.display === "block";
        closeAllSidebars();
        sidebarDownload.style.display = isVisible ? "none" : "block";
    });

    btnToggleHelp?.addEventListener("click", () => {
        const modalInstance =
            bootstrap.Modal.getInstance(modalHelp) ||
            new bootstrap.Modal(modalHelp);
        const isVisible = modalInstance._isShown;
        closeAllSidebars();
        if (!isVisible) {
            currentStep = 1;
            showStep(currentStep);
            // Hapus overlay bebas di sekitar tombol
            controlButtons.forEach((btn) => {
                btn.style.position = "";
                btn.style.zIndex = "";
                btn.style.padding = "";
            });
            modalInstance.show();
        } else {
            // Hapus overlay bebas saat modal ditutup
            controlButtons.forEach((btn) => {
                btn.style.position = "";
                btn.style.zIndex = "";
                btn.style.padding = "";
            });
            modalInstance.hide();
        }
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

    btnCloseDownload?.addEventListener("click", () => {
        sidebarDownload.style.display = "none";
    });

    btnSkip?.addEventListener("click", () => {
        const modalInstance = bootstrap.Modal.getInstance(guideModal);
        if (modalInstance) {
            modalInstance.hide();
        }
        controlButtons.forEach((btn) =>
            btn.classList.remove("highlighted-control")
        );
    });

    btnPrev.addEventListener("click", () => {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    btnNext.addEventListener("click", () => {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        } else {
            // Tutup modal dengan Bootstrap method
            const modalInstance = bootstrap.Modal.getInstance(guideModal);
            modalInstance.hide();
            controlButtons.forEach((btn) =>
                btn.classList.remove("highlighted-control")
            );
        }
    });

    // Menambahkan event listener pada tombol fullscreen
    btnFullscreen.addEventListener("click", toggleFullscreen);

    // Menambahkan event listener pada tombol Default Zoom
    btnDefaultZoom.addEventListener("click", function () {
        map.setView(mapConfig.center, mapConfig.zoom); // Mengatur ulang peta ke koordinat default dan zoom level
    });

    // Tampilkan modal dengan Bootstrap method
    const modalInstance = new bootstrap.Modal(guideModal);
    modalInstance.show();
    showStep(currentStep);
});
