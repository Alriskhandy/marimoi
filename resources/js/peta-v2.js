/**
 * Pratinjau peta publik di atas skema baru (spatial_layers/spatial_layer_features).
 * Berdiri sendiri dari resources/frontend/js/map.js (halaman lama /peta-tematik) —
 * lihat docs/marimoi v2/04_implementation/10-plan-peta-skema-baru.md.
 */

const map = L.map('peta-v2-map').setView([1.5, 127.8], 8);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
}).addTo(map);

const activeLayers = new Map();

function colorOrDefault(color) {
    return color && color.trim() !== '' ? color : '#2563eb';
}

async function loadFeatureDetail(featureId) {
    try {
        const response = await fetch(`/peta-v2/feature/${featureId}`);
        if (!response.ok) {
            return null;
        }
        return await response.json();
    } catch (error) {
        console.error('Gagal memuat detail feature', error);
        return null;
    }
}

function attachPopup(layer, feature, layerColor) {
    layer.on('click', async () => {
        layer.bindPopup('Memuat detail...').openPopup();
        const detail = await loadFeatureDetail(feature.properties.id);

        if (!detail) {
            layer.setPopupContent('Detail tidak tersedia.');
            return;
        }

        const legacy = detail.legacy;
        const region = detail.region;

        const parts = [];
        parts.push(`<strong>${detail.layer?.name ?? 'Layer'}</strong>`);
        if (legacy?.deskripsi) {
            parts.push(`<p>${legacy.deskripsi}</p>`);
        }
        if (legacy?.sumber_data) {
            parts.push(`<small>Sumber: ${legacy.sumber_data}</small>`);
        }
        if (legacy?.tahun) {
            parts.push(`<small>Tahun: ${legacy.tahun}</small>`);
        }
        if (region?.name) {
            parts.push(`<small>Wilayah: ${region.name}</small>`);
        } else {
            parts.push('<small><em>Wilayah belum tercatat</em></small>');
        }
        if (!legacy) {
            parts.push('<small><em>Detail deskriptif tidak tersedia untuk feature ini.</em></small>');
        }

        layer.setPopupContent(parts.join('<br>'));
    });
}

async function toggleLayer(layerMeta, checked) {
    if (!checked) {
        const existing = activeLayers.get(layerMeta.slug);
        if (existing) {
            map.removeLayer(existing);
            activeLayers.delete(layerMeta.slug);
        }
        return;
    }

    try {
        const response = await fetch(`/peta-v2/geojson/${layerMeta.slug}`);
        if (!response.ok) {
            return;
        }
        const geojson = await response.json();
        const color = colorOrDefault(layerMeta.color);

        const leafletLayer = L.geoJSON(geojson, {
            pointToLayer: (feature, latlng) => L.circleMarker(latlng, {
                radius: 6,
                fillColor: color,
                color,
                weight: 1,
                fillOpacity: 0.8,
            }),
            style: () => ({ color, weight: 2, fillOpacity: 0.3 }),
            onEachFeature: (feature, layer) => attachPopup(layer, feature, color),
        }).addTo(map);

        activeLayers.set(layerMeta.slug, leafletLayer);
    } catch (error) {
        console.error(`Gagal memuat layer ${layerMeta.slug}`, error);
    }
}

function renderLayerList(layers) {
    const container = document.getElementById('peta-v2-layer-list');
    container.innerHTML = '';

    if (layers.length === 0) {
        container.textContent = 'Tidak ada layer aktif.';
        return;
    }

    layers.forEach((layerMeta) => {
        const item = document.createElement('label');
        item.className = 'layer-item';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.addEventListener('change', (event) => toggleLayer(layerMeta, event.target.checked));

        const swatch = document.createElement('span');
        swatch.className = 'layer-swatch';
        swatch.style.backgroundColor = colorOrDefault(layerMeta.color);

        const label = document.createElement('span');
        label.textContent = layerMeta.title ?? layerMeta.name;

        item.append(checkbox, swatch, label);
        container.appendChild(item);
    });
}

async function init() {
    const container = document.getElementById('peta-v2-layer-list');
    try {
        const response = await fetch('/peta-v2/layers');
        const layers = await response.json();
        renderLayerList(layers);
    } catch (error) {
        container.textContent = 'Gagal memuat daftar layer.';
        console.error(error);
    }
}

init();
