/**
 * Label teks fitur di peta untuk layer yang sedang tampil.
 *
 * Teks diambil dari atribut nama yang umum di data impor (kolom `spatial_features.label`
 * berisi kode internal "MARIMOI-…", bukan nama). Supaya peta tetap terbaca: hanya fitur di
 * layar yang diberi label, layer besar baru diberi label saat zoom cukup dekat, label yang
 * bertabrakan dilewati (layer paling atas di Layer Aktif didahulukan), dan teks yang sama
 * dari satu layer tidak diulang dalam jarak dekat.
 */
(function () {
    const LABEL_KEYS = ["NAMOBJ", "NAMA_OBJEK", "NAMA", "NAME", "OBJEK", "nama", "name", "Nama", "KEGIATAN", "Keterangan", "KABUPATEN", "WADMKK", "DESA", "DESA_KELUR"];
    const MAX_LABELS = 300;
    const MAX_LENGTH = 48;
    // Teks yang sama dari layer yang sama (mis. nama kabupaten di banyak poligon) cukup sekali per radius ini.
    const SAME_TEXT_DISTANCE = 300;

    let labelLayer = null;
    let frame = null;

    // Zoom minimum agar label tampil: layer kecil selalu, layer besar baru saat zoom dekat.
    function minZoomFor(featureCount) {
        if (featureCount <= 50) {
            return 0;
        }
        return featureCount <= 300 ? 9 : 11;
    }

    // Teks label dari atribut nama pertama yang terisi dan berbeda dari nama mapset.
    function featureLabel(properties, mapsetName) {
        const mapset = String(mapsetName || "").trim().toLowerCase();
        for (const key of LABEL_KEYS) {
            const value = properties?.[key];
            if (typeof value !== "string" && typeof value !== "number") {
                continue;
            }
            const text = String(value).trim();
            // Nilai yang sama dengan nama mapset (mis. NAMA_OBJEK "Kawasan Permukiman") tidak informatif.
            if (text && text.toLowerCase() !== mapset) {
                return text.length > MAX_LENGTH ? `${text.slice(0, MAX_LENGTH - 1)}…` : text;
            }
        }
        return null;
    }

    // Titik label: posisi titik itu sendiri, atau titik tengah area/garis.
    function labelPosition(leaf) {
        try {
            if (typeof leaf.getLatLng === "function") {
                return { latlng: leaf.getLatLng(), isPoint: true };
            }
            if (typeof leaf.getCenter === "function") {
                return { latlng: leaf.getCenter(), isPoint: false };
            }
        } catch (error) {
            // getCenter() Leaflet melempar error untuk geometri kosong; lewati fitur itu.
        }
        return null;
    }

    // Semua fitur (Path/Marker asli) di dalam layer group, rekursif.
    function collectLeaves(layer, result) {
        if (layer.feature) {
            result.push(layer);
        } else if (typeof layer.getLayers === "function") {
            layer.getLayers().forEach((child) => collectLeaves(child, result));
        }
        return result;
    }

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value;
        return div.innerHTML;
    }

    // Apakah kotak label baru bertabrakan dengan label yang sudah dipasang.
    function overlaps(box, placed) {
        return placed.some((other) => box.left < other.right && box.right > other.left && box.top < other.bottom && box.bottom > other.top);
    }

    // Hapus lalu pasang ulang semua label untuk tampilan peta saat ini.
    function refresh() {
        if (!labelLayer || typeof map === "undefined" || !window.MarimoiCatalog) {
            return;
        }
        labelLayer.clearLayers();

        const zoom = map.getZoom();
        const viewBounds = map.getBounds();
        const placed = [];
        let count = 0;

        const entries = window.MarimoiCatalog.getActiveEntries().filter((entry) => map.hasLayer(entry.layerGroup));

        for (const entry of entries) {
            if (count >= MAX_LABELS) {
                break;
            }
            if (zoom < minZoomFor(entry.count)) {
                continue;
            }

            const pointsByText = new Map();

            for (const leaf of collectLeaves(entry.layerGroup, [])) {
                if (count >= MAX_LABELS) {
                    break;
                }
                if (leaf.marimoiFilteredOut) {
                    continue;
                }

                const text = featureLabel(leaf.feature.properties, entry.leafName);
                const position = text ? labelPosition(leaf) : null;
                if (!position || !viewBounds.contains(position.latlng)) {
                    continue;
                }

                // Perkiraan kotak teks (px) untuk mencegah label saling menumpuk.
                const point = map.latLngToContainerPoint(position.latlng);
                const width = text.length * 6.4 + 12;
                const offsetY = position.isPoint ? 14 : 0;
                const box = {
                    left: point.x - width / 2,
                    right: point.x + width / 2,
                    top: point.y + offsetY - 9,
                    bottom: point.y + offsetY + 9,
                };
                if (overlaps(box, placed)) {
                    continue;
                }
                const sameText = pointsByText.get(text) || [];
                if (sameText.some((other) => other.distanceTo(point) < SAME_TEXT_DISTANCE)) {
                    continue;
                }
                sameText.push(point);
                pointsByText.set(text, sameText);
                placed.push(box);

                labelLayer.addLayer(
                    L.marker(position.latlng, {
                        pane: "featureLabels",
                        interactive: false,
                        keyboard: false,
                        icon: L.divIcon({
                            className: `map-feature-label${position.isPoint ? " is-point" : ""}`,
                            html: `<span>${escapeHtml(text)}</span>`,
                            iconSize: null,
                        }),
                    })
                );
                count++;
            }
        }
    }

    // Gabungkan beberapa permintaan refresh dalam satu frame.
    function scheduleRefresh() {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(refresh);
    }

    document.addEventListener("DOMContentLoaded", () => {
        if (typeof map === "undefined") {
            return;
        }
        // Pane sendiri di atas area/garis & marker, tidak menangkap klik.
        const pane = map.createPane("featureLabels");
        pane.style.zIndex = 650;
        pane.style.pointerEvents = "none";

        labelLayer = L.layerGroup().addTo(map);
        map.on("moveend zoomend", scheduleRefresh);
    });

    window.MarimoiLabels = { refresh: scheduleRefresh };
})();
