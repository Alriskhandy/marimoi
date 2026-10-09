/**
 * Detail fitur dari tombol "Detail" di popup peta.
 *
 * Titik (Point/MultiPoint) dibuka sebagai modal di tengah; area/garis sebagai panel kanan
 * yang menutupi sidebar & tombol kontrol kanan. Urutan tumpukan (lihat peta.css):
 * modal titik > panel area/garis > sidebar.
 */
(function () {
    const HIDDEN_KEYS = new Set(["id", "uuid", "data_type", "sub_type", "kategori_id", "kategori", "icon", "warna", "is_marker", "gambar", "deskripsi"]);
    const INFO_FIELDS = [
        ["tahun", "Tahun"],
        ["sumber_data", "Sumber Data"],
        ["opd_pengelola", "OPD Pengelola"],
        ["tanggal_data", "Tanggal Data"],
    ];
    const TITLE_KEYS = ["NAMOBJ", "NAMA", "nama", "name", "Nama", "KEGIATAN", "kegiatan", "Keterangan", "keterangan", "label"];

    const panels = {};
    let currentLayer = null;

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function isEmpty(value) {
        return value === null || value === undefined || (typeof value === "string" && value.trim() === "");
    }

    function isPoint(feature) {
        return ["Point", "MultiPoint"].includes(feature?.geometry?.type);
    }

    function humanizeKey(key) {
        const spaced = String(key).replace(/_/g, " ").trim();
        // Kode atribut ber-huruf besar (ORDE01, KKOP 1) dibiarkan apa adanya.
        return spaced === spaced.toLowerCase() ? spaced.replace(/\b\w/g, (char) => char.toUpperCase()) : spaced;
    }

    function formatValue(value) {
        // Bilangan bulat sering berupa tahun/kode (2025, 32020000): tanpa pemisah ribuan.
        if (typeof value === "number") {
            return escapeHtml(Number.isInteger(value) ? String(value) : value.toLocaleString("id-ID", { maximumFractionDigits: 6, useGrouping: false }));
        }
        if (typeof value === "boolean") {
            return value ? "Ya" : "Tidak";
        }
        if (typeof value === "object") {
            return `<code>${escapeHtml(JSON.stringify(value))}</code>`;
        }
        const text = String(value);
        if (/^https?:\/\/\S+$/i.test(text)) {
            return `<a href="${escapeHtml(text)}" target="_blank" rel="noopener noreferrer">${escapeHtml(text)}</a>`;
        }
        return escapeHtml(text);
    }

    function featureTitle(props, fallback) {
        const key = TITLE_KEYS.find((name) => !isEmpty(props[name]));
        return key ? String(props[key]) : fallback || "Detail Fitur";
    }

    function featureCenter(feature, layer) {
        const geometry = feature.geometry;
        if (geometry?.type === "Point") {
            return { lat: geometry.coordinates[1], lng: geometry.coordinates[0] };
        }
        if (typeof layer?.getLatLng === "function") {
            return layer.getLatLng();
        }
        if (typeof layer?.getBounds === "function" && layer.getBounds().isValid()) {
            return layer.getBounds().getCenter();
        }
        return null;
    }

    function rowsHtml(rows) {
        return rows
            .map(([label, value]) => `<tr><th scope="row">${escapeHtml(label)}</th><td>${formatValue(value)}</td></tr>`)
            .join("");
    }

    function sectionHtml(icon, title, rows) {
        if (rows.length === 0) {
            return "";
        }
        return `
            <section class="feature-section">
                <h4><i class="bi ${icon}"></i> ${escapeHtml(title)}</h4>
                <table class="feature-table"><tbody>${rowsHtml(rows)}</tbody></table>
            </section>`;
    }

    function render(feature, layer) {
        const props = feature.properties || {};
        const mapsetName = props.kategori || "";
        const entry = window.MarimoiCatalog?.findEntriesByName(mapsetName)?.find((item) => item.leafName === mapsetName);
        const title = featureTitle(props, mapsetName);
        const center = featureCenter(feature, layer);

        const chips = [
            mapsetName ? `<span class="feature-chip is-mapset">${escapeHtml(mapsetName)}</span>` : "",
            entry ? `<span class="feature-chip">${escapeHtml(entry.rootName)}</span>` : "",
        ].join("");

        const coordinate = center
            ? `<span class="feature-coordinate"><i class="bi bi-geo-alt-fill"></i>${center.lat.toFixed(6)}, ${center.lng.toFixed(6)}</span>`
            : "";

        const infoRows = INFO_FIELDS.filter(([key]) => !isEmpty(props[key])).map(([key, label]) => [label, props[key]]);
        const infoKeys = new Set(INFO_FIELDS.map(([key]) => key));
        const attributeRows = Object.entries(props)
            .filter(([key, value]) => !HIDDEN_KEYS.has(key) && !infoKeys.has(key) && !isEmpty(value))
            .map(([key, value]) => [humanizeKey(key), value]);

        const photo = props.gambar
            ? `<section class="feature-section">
                    <h4><i class="bi bi-camera"></i> Foto Dokumentasi</h4>
                    <a href="${escapeHtml(props.gambar)}" target="_blank" rel="noopener noreferrer" class="feature-photo">
                        <img src="${escapeHtml(props.gambar)}" alt="Foto ${escapeHtml(title)}" loading="lazy">
                    </a>
                </section>`
            : "";

        return `
            <h3 class="feature-title">${escapeHtml(title)}</h3>
            <div class="feature-meta">
                <div class="feature-chips">${chips}</div>
                ${coordinate}
            </div>
            ${isEmpty(props.deskripsi) ? "" : `<p class="feature-description">${escapeHtml(props.deskripsi)}</p>`}
            ${sectionHtml("bi-info-circle", "Informasi Data", infoRows)}
            ${sectionHtml("bi-card-list", "Atribut", attributeRows)}
            ${attributeRows.length === 0 && infoRows.length === 0 ? '<p class="feature-empty">Fitur ini tidak memiliki atribut.</p>' : ""}
            ${photo}
            <div class="feature-actions">
                <button type="button" class="catalog-btn-primary" data-feature-zoom><i class="bi bi-zoom-in"></i> Perbesar ke fitur</button>
            </div>`;
    }

    function zoomToCurrent() {
        if (!currentLayer) {
            return;
        }
        if (typeof currentLayer.getLatLng === "function") {
            map.setView(currentLayer.getLatLng(), Math.max(map.getZoom(), 16));
        } else if (typeof currentLayer.getBounds === "function" && currentLayer.getBounds().isValid()) {
            map.fitBounds(currentLayer.getBounds(), { padding: [40, 40], maxZoom: 17 });
        }
    }

    function close(kind) {
        const targets = kind ? [panels[kind]] : Object.values(panels);
        targets.forEach((panel) => panel?.root.classList.add("hidden"));
        if (Object.values(panels).every((panel) => panel.root.classList.contains("hidden"))) {
            currentLayer = null;
        }
    }

    function open(feature, layer) {
        const kind = isPoint(feature) ? "modal" : "drawer";
        const panel = panels[kind];
        if (!panel) {
            return;
        }
        currentLayer = layer;
        panel.body.innerHTML = render(feature, layer);
        panel.body.scrollTop = 0;
        panel.root.classList.remove("hidden");
        panel.root.querySelector("[data-feature-close]")?.focus();
    }

    document.addEventListener("DOMContentLoaded", () => {
        [["drawer", "feature-drawer"], ["modal", "feature-modal"]].forEach(([kind, id]) => {
            const root = document.getElementById(id);
            if (!root) {
                return;
            }
            panels[kind] = { root, body: root.querySelector("[data-feature-body]") };
            root.querySelectorAll("[data-feature-close]").forEach((button) => button.addEventListener("click", () => close(kind)));
            root.addEventListener("click", (event) => {
                if (kind === "modal" && event.target === root) {
                    close(kind);
                }
                if (event.target.closest("[data-feature-zoom]")) {
                    zoomToCurrent();
                    if (kind === "modal") {
                        close(kind);
                    }
                }
            });
        });

        document.addEventListener("keydown", (event) => {
            if (event.key !== "Escape") {
                return;
            }
            // Tutup yang paling atas dulu: modal titik, lalu panel area/garis.
            if (panels.modal && !panels.modal.root.classList.contains("hidden")) {
                close("modal");
            } else if (panels.drawer && !panels.drawer.root.classList.contains("hidden")) {
                close("drawer");
            }
        });
    });

    window.MarimoiFeatureDetail = { open, close };
})();
