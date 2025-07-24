const map = L.map('map');

// Batas geografis Maluku Utara (approximate)
const malukuUtaraBounds = [
    [1.9, 125.2],   // [north, west]
    [-1.9, 129.2]   // [south, east]
];

map.fitBounds(malukuUtaraBounds);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
}).addTo(map);

// Definisikan warna untuk setiap kategori
const categoryColors = {
    'default': { color: '#ff7800', fillColor: '#ffd27f' },
    'Ekonomi': { color: '#228B22', fillColor: '#90EE90' },
    'Infrastruktur': { color: '#DAA520', fillColor: '#F0E68C' },
    'Kependudukan': { color: '#DC143C', fillColor: '#FFB6C1' },
    'Kemiskinan': { color: '#4169E1', fillColor: '#87CEEB' },
    'Kesehatan': { color: '#2F4F4F', fillColor: '#708090' },
    'Lingkungan Hidup': { color: '#1E90FF', fillColor: '#87CEFA' },
    'Pariwisata & Kebudayaan': { color: '#000080', fillColor: '#ADD8E6' },
    'Pendidikan': { color: '#32CD32', fillColor: '#98FB98' },
    'Sosial': { color: '#FFD700', fillColor: '#FFFFE0' }
};

// Simpan layer berdasarkan kategori
const categoryLayers = {};
const layerGroups = {};
let allData = [];
let dbfColumns = [];

// Fungsi untuk membuat custom marker icon
function createCustomMarkerIcon(iconClass, color, size = 24) {
    // Fallback icon jika tidak ada icon class
    const fallbackIcon = 'fa-solid fa-location-dot';
    const finalIcon = iconClass || fallbackIcon;
    const markerColor = color || '#007bff';
    
    console.log('🎨 Creating custom icon:', { iconClass: finalIcon, color: markerColor, size });
    
    return L.divIcon({
        html: `
            <div class="custom-marker-container" style="
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: ${size + 8}px;
                height: ${size + 8}px;
                cursor: pointer;
            ">
                <div class="marker-shadow" style="
                    position: absolute;
                    bottom: -2px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: ${size - 4}px;
                    height: 4px;
                    background: rgba(0,0,0,0.2);
                    border-radius: 50%;
                    filter: blur(1px);
                "></div>
                <div class="marker-clickable-area" style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    cursor: pointer;
                    z-index: 999;
                "></div>
                <i class="${finalIcon}" style="
                    font-size: ${size}px;
                    color: ${markerColor} !important;
                    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
                    z-index: 1000;
                    transition: all 0.2s ease;
                    display: block;
                    line-height: 1;
                    pointer-events: none;
                "></i>
            </div>
        `,
        className: 'custom-marker-icon',
        iconSize: [size + 8, size + 8],
        iconAnchor: [(size + 8) / 2, size + 4],
        popupAnchor: [0, -(size + 4)]
    });
}

// Fungsi untuk membuat marker dengan hover effect
function createEnhancedMarker(coords, iconClass, color, properties) {
    const marker = L.marker([coords[1], coords[0]], {
        icon: createCustomMarkerIcon(iconClass, color, 24)
    });

    // Tambahkan hover effect
    marker.on('mouseover', function(e) {
        const newIcon = createCustomMarkerIcon(iconClass, color, 28);
        e.target.setIcon(newIcon);
        
        // Tambahkan z-index lebih tinggi
        if (e.target.getElement()) {
            e.target.getElement().style.zIndex = 10000;
        }
    });

    marker.on('mouseout', function(e) {
        const newIcon = createCustomMarkerIcon(iconClass, color, 24);
        e.target.setIcon(newIcon);
        
        // Reset z-index
        if (e.target.getElement()) {
            e.target.getElement().style.zIndex = 1000;
        }
    });

    // Bind popup dengan event handling yang lebih robust
    const popupContent = generatePopupContent(properties);
    
    marker.bindPopup(popupContent, {
        maxWidth: 300,
        className: 'custom-popup',
        closeButton: true,
        autoClose: false,
        closeOnEscapeKey: true
    });

    // Tambahkan event listener untuk click
    marker.on('click', function(e) {
        console.log('🖱️ Marker clicked:', properties.nama || properties.id);
        
        // Pastikan popup terbuka
        if (!marker.isPopupOpen()) {
            marker.openPopup();
        }
        
        // Stop event propagation untuk mencegah map click
        L.DomEvent.stopPropagation(e);
    });

    // Event untuk debugging
    marker.on('popupopen', function(e) {
        console.log('✅ Popup opened for:', properties.nama || properties.id);
    });

    marker.on('popupclose', function(e) {
        console.log('❌ Popup closed for:', properties.nama || properties.id);
    });

    return marker;
}

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
        // Batasi 3 kolom untuk UI yanglebih clean
        html += `
            <div class="filter-group">
                <label class="form-label" for="filter-${column}">${column}</label>
                <select class="form-select" id="filter-${column}">
                    <option value="">Semua ${column}</option>
                </select>
            </div>
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

function generatePopupContent(props) {
    let content = `
        <div class="popup-custom">
            <div class="popup-header">
                <i class="bi bi-geo-alt-fill me-2"></i>
                ${props.nama || props.kategori || "Tanpa Nama"}
            </div>
    `;

    // Tampilkan nama jika ada
    if (props.nama && props.nama !== "-") {
        content += `
            <div class="popup-section">
                <div class="popup-label">Nama</div>
                <div class="popup-value">${props.nama}</div>
            </div>`;
    }

    // Tampilkan kategori jika berbeda dari nama
    if (props.kategori && props.kategori !== props.nama) {
        content += `
            <div class="popup-section">
                <div class="popup-label">Kategori</div>
                <div class="popup-value">${props.kategori}</div>
            </div>`;
    }

    if (props.deskripsi && props.deskripsi !== "-") {
        content += `
            <div class="popup-section">
                <div class="popup-label">Deskripsi</div>
                <div class="popup-value">${props.deskripsi}</div>
            </div>`;
    }

    // Tampilkan informasi marker jika is_marker = true
    if (props.is_marker) {
        content += `
            <div class="popup-section">
                <div class="popup-label">Tipe</div>
                <div class="popup-value">
                    <i class="${props.icon || 'fa-solid fa-location-dot'}" style="color: ${props.warna || '#007bff'}; margin-right: 5px;"></i>
                    Marker Point
                </div>
            </div>`;
    }

    const excludeKeys = ['id', 'kategori_id', 'deskripsi', 'icon', 'is_marker', 'warna', 'nama', 'kategori', 'parent_id', 'created_at', 'updated_at'];
    const dbfAttributes = Object.keys(props)
        .filter(key => !excludeKeys.includes(key))
        .filter(key => props[key] !== null && props[key] !== '');

    if (dbfAttributes.length > 0) {
        content += `<div class="popup-section">`;
        dbfAttributes.slice(0, 6).forEach(key => {
            const displayKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            content += `
                <div style="margin-bottom: 8px;">
                    <div class="popup-label">${displayKey}</div>
                    <div class="popup-value">${props[key]}</div>
                </div>`;
        });
        content += `</div>`;
    }

    content += `</div>`;
    return content;
}

// Fungsi untuk memuat data peta
function loadMapData(queryParams = "") {
    const url = queryParams ? `/geojson?${queryParams}` : "/geojson";

    console.log('🔄 Loading map data from:', url);

    fetch(url)
        .then((res) => res.json())
        .then((data) => {
            console.log('📊 Raw data received:', data);
            
            if (!data || !data.features || data.features.length === 0) {
                showAlert(
                    "Tidak ada data yang ditemukan dengan filter yang dipilih.",
                    "info"
                );
                updateLayerControl([]);
                if (document.getElementById("total-areas")) {
                    document.getElementById("total-areas").textContent = "0";
                }
                if (document.getElementById("categories-count")) {
                    document.getElementById("categories-count").textContent = "0";
                }
                return;
            }

            // Debug: Log sample feature data
            if (data.features.length > 0) {
                console.log('🔍 Sample feature:', data.features[0]);
                console.log('🔍 Sample properties:', data.features[0].properties);
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

                categoryLayers[kategori].forEach(feature => {
                    const props = feature.properties;
                    
                    // Konversi berbagai format boolean
                    let isMarker = false;
                    if (props.is_marker === true || props.is_marker === 1 || 
                        props.is_marker === 'true' || props.is_marker === '1') {
                        isMarker = true;
                    }
                    
                    let layer;

                    // Debug log untuk melihat data yang masuk
                    console.log('🔧 Processing feature:', {
                        id: props.id,
                        nama: props.nama,
                        type: feature.geometry.type,
                        isMarker: isMarker,
                        icon: props.icon,
                        warna: props.warna,
                        is_marker_raw: props.is_marker,
                        coordinates: feature.geometry.coordinates
                    });

                    // Cek apakah ini adalah Point geometry dan is_marker = true
                    if (feature.geometry.type === "Point" && isMarker) {
                        const coords = feature.geometry.coordinates;
                        const iconClass = props.icon || 'fa-solid fa-location-dot';
                        const warna = props.warna || '#007bff';

                        console.log('✅ Creating CUSTOM marker with:', {
                            coords: coords,
                            iconClass: iconClass,
                            warna: warna
                        });

                        // Gunakan fungsi createEnhancedMarker untuk marker dari database
                        layer = createEnhancedMarker(coords, iconClass, warna, props);
                        
                    } else if (feature.geometry.type === "Point") {
                        // Point biasa dengan custom divIcon juga (bukan marker bawaan leaflet)
                        const coords = feature.geometry.coordinates;
                        const iconClass = 'fa-solid fa-map-pin';
                        const warna = colors.color;
                        
                        console.log('📍 Creating regular point marker');
                        
                        layer = L.marker([coords[1], coords[0]], {
                            icon: createCustomMarkerIcon(iconClass, warna, 20)
                        });

                        // Bind popup dengan event handling yang sama seperti custom marker
                        const popupContent = generatePopupContent(props);
                        
                        layer.bindPopup(popupContent, {
                            maxWidth: 300,
                            className: 'custom-popup',
                            closeButton: true,
                            autoClose: false,
                            closeOnEscapeKey: true
                        });

                        // Tambahkan event listener untuk click
                        layer.on('click', function(e) {
                            console.log('🖱️ Regular marker clicked:', props.nama || props.id);
                            
                            // Pastikan popup terbuka
                            if (!layer.isPopupOpen()) {
                                layer.openPopup();
                            }
                            
                            // Stop event propagation
                            L.DomEvent.stopPropagation(e);
                        });

                        // Event debugging
                        layer.on('popupopen', function(e) {
                            console.log('✅ Regular popup opened for:', props.nama || props.id);
                        });
                        
                    } else {
                        // Polygon atau geometry lainnya
                        console.log('🔷 Creating polygon/other geometry');
                        
                        layer = L.geoJSON(feature, {
                            style: function() {
                                return {
                                    color: colors.color,
                                    weight: 2,
                                    opacity: 0.8,
                                    fillColor: colors.fillColor,
                                    fillOpacity: 0.4
                                };
                            },
                            onEachFeature: function(feature, layer) {
                                layer.bindPopup(generatePopupContent(feature.properties));
                            }
                        });
                    }

                    if (layer) {
                        layerGroup.addLayer(layer);
                    }
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

            console.log('✅ Map data loaded successfully!');
        })
        .catch(err => {
            console.error("❌ Gagal mengambil data GeoJSON:", err);
            // showAlert("Gagal memuat data peta. Silakan refresh halaman.", "danger");
        });
}

// Fungsi untuk animasi counter
function animateCounter(elementId, start, end, duration = 1000) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
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
if (document.getElementById("search-input")) {
    document.getElementById("search-input").addEventListener("input", function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 500);
    });
}

// Keyboard shortcuts
document.addEventListener("keydown", function (e) {
    // Ctrl/Cmd + F untuk focus ke search
    if ((e.ctrlKey || e.metaKey) && e.key === "f") {
        e.preventDefault();
        const searchInput = document.getElementById("search-input");
        if (searchInput) searchInput.focus();
    }

    // Escape untuk clear search
    if (e.key === "Escape") {
        const searchInput = document.getElementById("search-input");
        if (searchInput) {
            searchInput.value = "";
            resetFilters();
        }
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
        if (controls) {
            if (diff > 0) {
                // Swipe up - show controls
                controls.style.transform = "translateY(0)";
            } else {
                // Swipe down - hide controls
                controls.style.transform = "translateY(-70%)";
            }
        }
    }
}

// Initialize tooltips
function initTooltips() {
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
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
                        <div class="popup-value">${e.latlng.lat.toFixed(6)}</div>
                    </div>
                    <div class="popup-section">
                        <div class="popup-label">Longitude</div>
                        <div class="popup-value">${e.latlng.lng.toFixed(6)}</div>
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
    const searchInput = document.getElementById("search-input");
    if (search && searchInput) {
        searchInput.value = search;
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