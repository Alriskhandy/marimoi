const map = L.map("map").setView(mapConfig.center, mapConfig.zoom);

// Inisialisasi variabel
let layerGroups = {};
let currentBaseMap = null;

// Fungsi utama untuk memuat dan menampilkan data
async function initMap() {
    try {
        const response = await fetch('/geojson');
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

    data.features.forEach(feature => {
        const kategori = feature.properties?.kategori || "Lainnya";
        if (!layerGroups[kategori]) {
            layerGroups[kategori] = L.layerGroup().addTo(map); // Tambahkan ke peta secara default
        }

        L.geoJSON(feature, {
            style: getStyleForCategory(kategori),
            onEachFeature: (feature, layer) => bindPopupContent(feature, layer)
        }).addTo(layerGroups[kategori]);
    });
}

// Fungsi untuk update daftar layer di sidebar
function updateLayerList() {
    const layerListDiv = document.getElementById('layer-list');
    layerListDiv.innerHTML = '';

    Object.keys(layerGroups).forEach(kategori => {
        const layerId = `layer-${normalizeId(kategori)}`;
        const item = document.createElement('div');
        item.className = 'flex items-center py-2 px-1 hover:bg-gray-50 rounded';
        item.innerHTML = `
            <input type="checkbox" 
                   id="${layerId}"
                   class="layer-checkbox checkbox checkbox-primary checkbox-xs" 
                   data-kategori="${kategori}" 
                   checked>
            <label for="${layerId}" class="ml-2 text-sm cursor-pointer">${kategori}</label>
        `;
        
        layerListDiv.appendChild(item);
    });

    layerListDiv.addEventListener('change', (e) => {
        if (e.target.classList.contains('layer-checkbox')) {
            const kategori = e.target.dataset.kategori;
            e.target.checked ? map.addLayer(layerGroups[kategori]) : map.removeLayer(layerGroups[kategori]);
        }
    });
}

// Helper functions
function normalizeId(str) {
    return str.toLowerCase().replace(/\s+/g, '-');
}

function getStyleForCategory(kategori) {
    const colorMap = {
        'Infrastruktur': { color: '#3b82f6', fillColor: '#93c5fd' },
        'Perekonomian': { color: '#10b981', fillColor: '#6ee7b7' },
        'Lingkungan': { color: '#f59e0b', fillColor: '#fcd34d' },
        'default': { color: '#6b7280', fillColor: '#9ca3af' }
    };
    return colorMap[kategori] || colorMap.default;
}

function bindPopupContent(feature, layer) {
    const props = feature.properties;
    let content = `<div class="p-2 max-w-xs"><h4 class="font-bold mb-2 text-blue-600">${props.kategori || 'Feature'}</h4>`;
    
    Object.entries(props).forEach(([key, value]) => {
        if (value && !['geometry', 'id'].includes(key)) {
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            content += `<p class="text-sm mb-1"><span class="font-medium">${label}:</span> ${value}</p>`;
        }
    });
    
    content += `</div>`;
    layer.bindPopup(content);
}

function showAlert(message, type = 'info') {
    console.log(`${type}: ${message}`);
}

// Fungsi untuk mengubah base map
function changeBaseMap(baseMap) {
    if (currentBaseMap) {
        map.removeLayer(currentBaseMap);
    }

    const config = mapConfig.baseMaps[baseMap];
    if (config) {
        currentBaseMap = L.tileLayer(config.url, {
            maxZoom: 19,
            attribution: config.attribution,
        }).addTo(map);
    }
}

// Inisialisasi peta saat dokumen siap
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-toggle-section]').forEach(btn => {
        btn.addEventListener('click', () => {
            const section = btn.dataset.toggleSection;
            document.getElementById(`${section}-section`).classList.toggle('hidden');
        });
    });
    
    initMap();
    changeBaseMap("osm"); // Ganti dengan base map default yang diinginkan
});
