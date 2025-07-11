const map = L.map("map").setView([-0.8, 127.4], 9);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
}).addTo(map);

// Definisikan warna untuk setiap kategori
const categoryColors = {
    default: { color: "#ff7800", fillColor: "#ffd27f" },
    hutan: { color: "#228B22", fillColor: "#90EE90" },
    sawah: { color: "#DAA520", fillColor: "#F0E68C" },
    permukiman: { color: "#DC143C", fillColor: "#FFB6C1" },
    industri: { color: "#4169E1", fillColor: "#87CEEB" },
    jalan: { color: "#2F4F4F", fillColor: "#708090" },
    sungai: { color: "#1E90FF", fillColor: "#87CEFA" },
    danau: { color: "#000080", fillColor: "#ADD8E6" },
    kebun: { color: "#32CD32", fillColor: "#98FB98" },
    pantai: { color: "#FFD700", fillColor: "#FFFFE0" },
};

// Simpan layer berdasarkan kategori
const categoryLayers = {};
const layerGroups = {};
let allData = [];
let dbfColumns = [];

// Fungsi untuk toggle panel
function togglePanel(panelId) {
    const panel = document.getElementById(panelId);
    const button = panel.parentElement.querySelector(".collapse-btn i");

    if (panel.style.display === "none") {
        panel.style.display = "block";
        button.className = "bi bi-chevron-up";
    } else {
        panel.style.display = "none";
        button.className = "bi bi-chevron-down";
    }
}

// Fungsi untuk mendapatkan warna berdasarkan kategori
function getCategoryColor(kategori) {
    const key = kategori ? kategori.toLowerCase() : "default";
    return categoryColors[key] || categoryColors["default"];
}

// Fungsi untuk toggle layer
function toggleLayer(kategori, isChecked) {
    if (layerGroups[kategori]) {
        if (isChecked) {
            map.addLayer(layerGroups[kategori]);
        } else {
            map.removeLayer(layerGroups[kategori]);
        }
    }
}

// Fungsi untuk update control checkbox
function updateLayerControl(categories) {
    const controlDiv = document.getElementById("layer-control-body");
    let html = "";

    categories.forEach((cat) => {
        const count = categoryLayers[cat.kategori]
            ? categoryLayers[cat.kategori].length
            : 0;
        const colors = getCategoryColor(cat.kategori);

        html += `
                    <div class="layer-item">
                        <input type="checkbox" class="layer-checkbox" id="layer-${cat.kategori}" checked onchange="toggleLayer('${cat.kategori}', this.checked)">
                        <div class="category-indicator" style="background-color: ${colors.fillColor}; border-color: ${colors.color}"></div>
                        <label class="layer-label" for="layer-${cat.kategori}">${cat.kategori}</label>
                        <span class="layer-count">${count}</span>
                    </div>
                `;
    });

    controlDiv.innerHTML = html;
}

// Fungsi untuk memuat kolom DBF dan membuat filter
function loadDbfColumns() {
    fetch("/api/dbf/columns")
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                dbfColumns = data.columns;
                createDbfFilters();
            }
        })
        .catch((err) => {
            console.error("Gagal memuat kolom DBF:", err);
            document.getElementById("dbf-filters").innerHTML =
                '<p class="text-muted small">Tidak ada filter tambahan tersedia</p>';
        });
}

// Fungsi untuk membuat filter DBF
function createDbfFilters() {
    const filterDiv = document.getElementById("dbf-filters");
    let html = "";

    dbfColumns.slice(0, 3).forEach((column) => {
        // Batasi 3 kolom untuk UI yang lebih clean
        html += `
                  
                `;
    });

    filterDiv.innerHTML = html;

    // Load values untuk setiap kolom
    dbfColumns.slice(0, 3).forEach((column) => {
        loadColumnValues(column);
    });
}

// Fungsi untuk memuat nilai kolom
function loadColumnValues(column) {
    fetch(`/api/dbf/column/${column}/values`)
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                const select = document.getElementById(`filter-${column}`);
                if (select) {
                    data.values.slice(0, 20).forEach((value) => {
                        // Batasi 20 nilai pertama
                        const option = document.createElement("option");
                        option.value = value;
                        option.textContent =
                            value.length > 30
                                ? value.substring(0, 30) + "..."
                                : value;
                        select.appendChild(option);
                    });
                }
            }
        })
        .catch((err) =>
            console.error(`Gagal memuat nilai untuk ${column}:`, err)
        );
}

// Fungsi untuk menerapkan filter
function applyFilters() {
    const params = new URLSearchParams();

    // Search parameter
    const searchValue = document.getElementById("search-input").value;
    if (searchValue) {
        params.append("search", searchValue);
    }

    // DBF filter parameters
    dbfColumns.slice(0, 3).forEach((column) => {
        const select = document.getElementById(`filter-${column}`);
        if (select && select.value) {
            params.append(`dbf_filter[${column}]`, select.value);
        }
    });

    // Show loading
    showLoading();

    // Reload data dengan filter
    loadMapData(params.toString());
}

// Fungsi untuk reset filter
function resetFilters() {
    document.getElementById("search-input").value = "";
    dbfColumns.slice(0, 3).forEach((column) => {
        const select = document.getElementById(`filter-${column}`);
        if (select) select.value = "";
    });

    showLoading();
    loadMapData();
}

// Fungsi untuk menampilkan loading
function showLoading() {
    document.getElementById("layer-control-body").innerHTML = `
                <div class="loading">
                    <div class="loading-spinner"></div>
                    Memuat data...
                </div>
            `;
}

// Fungsi untuk menampilkan alert
function showAlert(message, type = "warning") {
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-custom`;
    alertDiv.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 5000);
}

// Fungsi untuk memuat data peta
function loadMapData(queryParams = "") {
    const url = queryParams ? `/geojson?${queryParams}` : "/geojson";

    fetch(url)
        .then((res) => res.json())
        .then((data) => {
            if (!data || !data.features || data.features.length === 0) {
                showAlert(
                    "Tidak ada data yang ditemukan dengan filter yang dipilih.",
                    "info"
                );
                updateLayerControl([]);
                document.getElementById("total-areas").textContent = "0";
                document.getElementById("categories-count").textContent = "0";
                return;
            }

            // Clear existing layers
            Object.values(layerGroups).forEach((group) => {
                if (map.hasLayer(group)) {
                    map.removeLayer(group);
                }
            });

            // Reset data
            Object.keys(categoryLayers).forEach(
                (key) => delete categoryLayers[key]
            );
            Object.keys(layerGroups).forEach((key) => delete layerGroups[key]);

            // Kelompokkan data berdasarkan kategori
            data.features.forEach((feature) => {
                const kategori =
                    feature.properties.kategori || "Tidak Dikategorikan";

                if (!categoryLayers[kategori]) {
                    categoryLayers[kategori] = [];
                }

                categoryLayers[kategori].push(feature);
            });

            // Buat layer group untuk setiap kategori
            Object.keys(categoryLayers).forEach((kategori) => {
                const colors = getCategoryColor(kategori);
                const layerGroup = L.layerGroup();

                categoryLayers[kategori].forEach((feature) => {
                    const layer = L.geoJSON(feature, {
                        style: function (feature) {
                            return {
                                color: colors.color,
                                weight: 2,
                                opacity: 0.8,
                                fillColor: colors.fillColor,
                                fillOpacity: 0.4,
                            };
                        },
                        onEachFeature: function (feature, layer) {
                            const props = feature.properties;

                            // Buat popup content dengan atribut DBF
                            let popupContent = `
                                        <div class="popup-custom">
                                            <div class="popup-header">
                                                <i class="bi bi-geo-alt-fill me-2"></i>
                                                ${
                                                    props.kategori ||
                                                    "Tidak Dikategorikan"
                                                }
                                            </div>
                                    `;

                            if (
                                props.deskripsi &&
                                props.deskripsi !== "-" &&
                                props.deskripsi !== ""
                            ) {
                                popupContent += `
                                            <div class="popup-section">
                                                <div class="popup-label">Deskripsi</div>
                                                <div class="popup-value">${props.deskripsi}</div>
                                            </div>
                                        `;
                            }

                            // Tambahkan atribut DBF lainnya
                            const excludeKeys = ["id", "kategori", "deskripsi"];
                            const dbfAttributes = Object.keys(props)
                                .filter((key) => !excludeKeys.includes(key))
                                .filter(
                                    (key) =>
                                        props[key] !== null &&
                                        props[key] !== undefined &&
                                        props[key] !== ""
                                );

                            if (dbfAttributes.length > 0) {
                                popupContent += `<div class="popup-section">`;

                                dbfAttributes.slice(0, 6).forEach((key) => {
                                    // Batasi 6 atribut untuk UI yang clean
                                    const value = props[key];
                                    const displayKey =
                                        key.charAt(0).toUpperCase() +
                                        key.slice(1).replace(/_/g, " ");
                                    const displayValue =
                                        typeof value === "string" &&
                                        value.length > 50
                                            ? value.substring(0, 50) + "..."
                                            : value;

                                    popupContent += `
                                                <div style="margin-bottom: 8px;">
                                                    <div class="popup-label">${displayKey}</div>
                                                    <div class="popup-value">${displayValue}</div>
                                                </div>
                                            `;
                                });

                                if (dbfAttributes.length > 6) {
                                    popupContent += `<small class="text-muted">... dan ${
                                        dbfAttributes.length - 6
                                    } atribut lainnya</small>`;
                                }

                                popupContent += `</div>`;
                            }

                            popupContent += `</div>`;
                            layer.bindPopup(popupContent, {
                                maxWidth: 350,
                                className: "custom-popup",
                            });

                            // Interaksi visual
                            layer.on({
                                mouseover: function (e) {
                                    e.target.setStyle({
                                        weight: 3,
                                        color: colors.color,
                                        fillOpacity: 0.7,
                                    });
                                },
                                mouseout: function (e) {
                                    e.target.setStyle({
                                        weight: 2,
                                        color: colors.color,
                                        fillOpacity: 0.4,
                                    });
                                },
                            });
                        },
                    });

                    layerGroup.addLayer(layer);
                });

                layerGroups[kategori] = layerGroup;
                map.addLayer(layerGroup); // Tampilkan semua layer secara default
            });

            // Update kontrol layer
            const categories = Object.keys(categoryLayers).map((kategori) => ({
                kategori: kategori,
            }));

            updateLayerControl(categories);

            // Update legend dengan animasi
            animateCounter("total-areas", 0, data.features.length);
            animateCounter("categories-count", 0, categories.length);

            // Fit map bounds ke semua data
            if (Object.keys(layerGroups).length > 0) {
                const group = new L.featureGroup(Object.values(layerGroups));
                map.fitBounds(group.getBounds(), { padding: [20, 20] });
            }
        })
        .catch((err) => {
            console.error("Gagal mengambil data GeoJSON:", err);
            showAlert(
                "Gagal memuat data peta. Silakan refresh halaman.",
                "danger"
            );
        });
}

// Fungsi untuk animasi counter
function animateCounter(elementId, start, end, duration = 1000) {
    const element = document.getElementById(elementId);
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if (current >= end) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current);
    }, 16);
}

// Fungsi untuk search dengan debounce
let searchTimeout;
document.getElementById("search-input").addEventListener("input", function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

// Keyboard shortcuts
document.addEventListener("keydown", function (e) {
    // Ctrl/Cmd + F untuk focus ke search
    if ((e.ctrlKey || e.metaKey) && e.key === "f") {
        e.preventDefault();
        document.getElementById("search-input").focus();
    }

    // Escape untuk clear search
    if (e.key === "Escape") {
        document.getElementById("search-input").value = "";
        resetFilters();
    }
});

// Touch gestures untuk mobile
let touchStartY = 0;
let touchEndY = 0;

document.addEventListener("touchstart", function (e) {
    touchStartY = e.changedTouches[0].screenY;
});

document.addEventListener("touchend", function (e) {
    touchEndY = e.changedTouches[0].screenY;
    handleSwipe();
});

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartY - touchEndY;

    if (Math.abs(diff) > swipeThreshold) {
        const controls = document.querySelector(".map-controls");
        if (diff > 0) {
            // Swipe up - show controls
            controls.style.transform = "translateY(0)";
        } else {
            // Swipe down - hide controls
            controls.style.transform = "translateY(-70%)";
        }
    }
}

// Initialize tooltips
function initTooltips() {
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Map event handlers
map.on("moveend", function () {
    // Update URL with current map view
    const center = map.getCenter();
    const zoom = map.getZoom();
    const newUrl = `${window.location.pathname}#${zoom}/${center.lat.toFixed(
        5
    )}/${center.lng.toFixed(5)}`;
    history.replaceState(null, null, newUrl);
});

map.on("click", function (e) {
    // Show coordinates on map click (for debugging)
    if (e.originalEvent.ctrlKey) {
        L.popup()
            .setLatLng(e.latlng)
            .setContent(
                `
                        <div class="popup-custom">
                            <div class="popup-header">
                                <i class="bi bi-crosshair me-2"></i>Koordinat
                            </div>
                            <div class="popup-section">
                                <div class="popup-label">Latitude</div>
                                <div class="popup-value">${e.latlng.lat.toFixed(
                                    6
                                )}</div>
                            </div>
                            <div class="popup-section">
                                <div class="popup-label">Longitude</div>
                                <div class="popup-value">${e.latlng.lng.toFixed(
                                    6
                                )}</div>
                            </div>
                        </div>
                    `
            )
            .openOn(map);
    }
});

// Load URL parameters on page load
function loadUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);

    // Load search parameter
    const search = urlParams.get("search");
    if (search) {
        document.getElementById("search-input").value = search;
    }

    // Load map view from hash
    const hash = window.location.hash.substring(1);
    if (hash) {
        const parts = hash.split("/");
        if (parts.length === 3) {
            const zoom = parseInt(parts[0]);
            const lat = parseFloat(parts[1]);
            const lng = parseFloat(parts[2]);
            if (!isNaN(zoom) && !isNaN(lat) && !isNaN(lng)) {
                map.setView([lat, lng], zoom);
            }
        }
    }
}

// Error handling
window.addEventListener("error", function (e) {
    console.error("JavaScript Error:", e.error);
    // showAlert('Terjadi kesalahan pada aplikasi. Silakan refresh halaman.', 'danger');
});

// Network status
window.addEventListener("online", function () {
    showAlert("Koneksi internet tersambung kembali.", "success");
});

window.addEventListener("offline", function () {
    showAlert(
        "Koneksi internet terputus. Beberapa fitur mungkin tidak berfungsi.",
        "warning"
    );
});

// Performance monitoring
function logPerformance() {
    if ("performance" in window) {
        const loadTime =
            window.performance.timing.loadEventEnd -
            window.performance.timing.navigationStart;
        console.log(`Page load time: ${loadTime}ms`);
    }
}

// Initialize application
function initApp() {
    console.log("🗺️ Initializing GIS Application...");

    // Load URL parameters
    loadUrlParameters();

    // Load initial data
    loadDbfColumns();
    loadMapData();

    // Initialize tooltips
    setTimeout(initTooltips, 1000);

    // Log performance
    setTimeout(logPerformance, 2000);

    console.log("✅ GIS Application initialized successfully");
}

// Start the application when DOM is ready
document.addEventListener("DOMContentLoaded", initApp);

// Add some helpful console messages for developers
console.log(`
╔═══════════════════════════════════════╗
║           GIS Web Application         ║
║                                       ║
║  🗺️  Interactive Map with Filters    ║
║  📊  Real-time Statistics             ║
║  🎨  Bootstrap 5 Design              ║
║  📱  Mobile Responsive               ║
║                                       ║
║  Shortcuts:                           ║
║  • Ctrl+F: Focus search               ║
║  • Escape: Clear search               ║
║  • Ctrl+Click: Show coordinates       ║
║                                       ║
╚═══════════════════════════════════════╝
        `);
