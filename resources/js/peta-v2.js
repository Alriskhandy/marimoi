/**
 * Pratinjau peta publik di atas skema baru (spatial_layers/spatial_layer_features).
 * Berdiri sendiri dari resources/frontend/js/map.js (halaman lama /peta-tematik) —
 * lihat docs/marimoi v2/04_implementation/10-plan-peta-skema-baru.md dan perluasan
 * icon/opacity/metadata_dinamis/feedback di 12-implementasi-perbaikan-pemetaan.md
 * Bagian 5.
 */

const map = L.map('peta-v2-map').setView([1.5, 127.8], 8);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
}).addTo(map);

const activeLayers = new Map();

function colorOrDefault(color) {
    return color && color.trim() !== '' ? color : '#2563eb';
}

function opacityOrDefault(opacity) {
    const value = Number(opacity);
    return Number.isFinite(value) && value >= 0 && value <= 1 ? value : 1;
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

async function submitFeedback(featureId, form) {
    const statusEl = form.querySelector('.feedback-status');
    statusEl.textContent = 'Mengirim...';

    try {
        const response = await fetch('/peta-v2/feedback', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                spatial_layer_feature_id: featureId,
                nama_pemberi: form.querySelector('[name="nama_pemberi"]').value,
                email: form.querySelector('[name="email"]').value,
                pesan: form.querySelector('[name="pesan"]').value,
            }),
        });

        statusEl.textContent = response.ok ? 'Terima kasih, feedback terkirim.' : 'Gagal mengirim, coba lagi.';
        if (response.ok) {
            form.reset();
        }
    } catch (error) {
        statusEl.textContent = 'Gagal mengirim, coba lagi.';
        console.error('Gagal mengirim feedback', error);
    }
}

function buildFeedbackForm(featureId) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <hr>
        <details>
            <summary style="cursor:pointer;">Kirim Feedback</summary>
            <div style="margin-top:0.5rem;">
                <input type="text" name="nama_pemberi" placeholder="Nama" style="width:100%;margin-bottom:0.25rem;" required>
                <input type="email" name="email" placeholder="Email (opsional)" style="width:100%;margin-bottom:0.25rem;">
                <textarea name="pesan" placeholder="Pesan" style="width:100%;margin-bottom:0.25rem;" required></textarea>
                <button type="button" class="btn-send-feedback">Kirim</button>
                <div class="feedback-status" style="font-size:0.75rem;"></div>
            </div>
        </details>
    `;

    wrapper.querySelector('.btn-send-feedback').addEventListener('click', () => {
        submitFeedback(featureId, wrapper);
    });

    return wrapper;
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

        const container = document.createElement('div');
        const parts = [];
        parts.push(`<strong>${detail.layer?.name ?? 'Layer'}</strong>`);

        if (detail.gambar) {
            parts.push(`<img src="/storage/${detail.gambar}" alt="Gambar" style="max-width:200px;display:block;margin:0.25rem 0;">`);
        }
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

        if (Array.isArray(detail.metadata_dinamis) && detail.metadata_dinamis.length > 0) {
            parts.push('<hr><strong>Metadata</strong>');
            detail.metadata_dinamis.forEach((item) => {
                const satuan = item.satuan ? ` ${item.satuan}` : '';
                parts.push(`<small>${item.label}: ${item.value}${satuan}</small>`);
            });
        }

        container.innerHTML = parts.join('<br>');
        container.appendChild(buildFeedbackForm(feature.properties.id));

        layer.setPopupContent(container);
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
        const opacity = opacityOrDefault(layerMeta.opacity);

        const leafletLayer = L.geoJSON(geojson, {
            pointToLayer: (feature, latlng) => {
                if (layerMeta.icon) {
                    return L.marker(latlng, {
                        icon: L.divIcon({
                            html: `<i class="${layerMeta.icon}" style="color:${color};font-size:1.5rem;"></i>`,
                            className: 'peta-v2-marker-icon',
                            iconSize: [24, 24],
                        }),
                    });
                }

                return L.circleMarker(latlng, {
                    radius: 6,
                    fillColor: color,
                    color,
                    weight: 1,
                    fillOpacity: opacity,
                });
            },
            style: () => ({ color, weight: 2, opacity, fillOpacity: opacity * 0.4 }),
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
