/**
 * map.js — inti Peta Interaktif publik (URL /peta-interaktif).
 *
 * Berisi: inisialisasi Leaflet & basemap, zoom halus, style fitur dari dashboard,
 * pemuatan data per mapset (cache IndexedDB + /geojson), popup, legenda, filter,
 * dan share. File pendamping (dimuat setelah file ini, memakai global di bawah):
 *   - map-catalog.js        Katalog Peta & daftar Layer Aktif (state layer aktif)
 *   - map-feature-detail.js Panel/modal detail fitur
 *   - map-labels.js         Label teks fitur di peta
 *   - map-guide.js          Panduan (tur) peta
 *   - map-cache.js          MapDataStore (cache IndexedDB), dimuat sebelum file ini
 */

/**
 * Posisi awal peta (tengah Maluku Utara) dan daftar basemap yang bisa dipilih.
 */
const mapConfig = {
    center: [0.735485, 128.028201],
    zoom: 7,
    baseMapsList: [
        {
            id: "osm",
            label: "OpenStreetMap",
            url: "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
            maxZoom: 19,
        },

        {
            id: "esri-streets",
            label: "ESRI Streets",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 19,
        },

        {
            id: "esri-topographic",
            label: "Topographic",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 19,
        },

        {
            id: "esri-oceans",
            label: "ESRI Oceans",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/Ocean/World_Ocean_Base/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 16,
        },

        {
            id: "esri-world-imagery",
            label: "ESRI World Imagery",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 18,
        },

        {
            id: "esri-dark-gray",
            label: "ESRI Dark Gray Canvas",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 16,
        },

        {
            id: "esri-light-gray",
            label: "Light Gray Canvas",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}",
            maxZoom: 16,
        },

        // Tile Google tanpa API key resmi: bisa sewaktu-waktu diblokir Google.
        {
            id: "google-roadmap",
            label: "Google Map (ROADMAP)",
            url: "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            maxZoom: 20,
        },
        {
            id: "google-hybrid",
            label: "Google Map (Hybrid)",
            url: "https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            maxZoom: 20,
        },
        {
            id: "google-terrain",
            label: "Google Map (Terrain)",
            url: "https://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            maxZoom: 16,
        },
    ],
};

/**
 * Zoom halus untuk scroll mouse & touchpad, pengganti scrollWheelZoom bawaan Leaflet
 * (yang mengumpulkan beberapa scroll lalu melompat sekaligus).
 *
 * Cara kerja: setiap event wheel hanya menggeser target zoom; tiap frame zoom peta
 * dikejar mendekati target dengan titik di bawah kursor sebagai pusat. Gerakan cubit
 * touchpad (wheel + ctrlKey) diberi sensitivitas lebih tinggi. Saat scroll berhenti, zoom
 * dirapikan ke kelipatan 0,1 terdekat (mis. 9,3) dan dipertahankan di sana.
 *
 * Supaya peta tidak bergoyang:
 *   1. Titik acuan dihitung dari center/zoom milik handler ini (bukan dari peta yang sudah
 *      dibulatkan) dan baru diperbarui bila kursor berpindah > 2px.
 *   2. Leaflet membulatkan posisi peta ke piksel bulat setiap _move; sisa pecahannya
 *      dikoreksi dengan translate sub-piksel pada map pane (_compensateRounding).
 */
const SmoothWheelZoom = L.Handler.extend({
    addHooks: function () {
        L.DomEvent.on(this._map.getContainer(), "wheel", this._onWheel, this);
    },

    removeHooks: function () {
        L.DomEvent.off(this._map.getContainer(), "wheel", this._onWheel, this);
        this._stop();
    },

    _onWheel: function (e) {
        L.DomEvent.preventDefault(e);
        const map = this._map;
        const sensitivity = e.ctrlKey ? 0.03 : 0.015;
        const mousePoint = map.mouseEventToContainerPoint(e);

        if (!this._isWheeling) {
            this._start();
        }

        if (!this._mousePoint || this._mousePoint.distanceTo(mousePoint) > 2) {
            this._mousePoint = mousePoint;
            this._anchorLatLng = this._containerPointToExactLatLng(mousePoint);
        }

        this._goalZoom = Math.min(
            map.getMaxZoom(),
            Math.max(map.getMinZoom(), this._goalZoom + L.DomEvent.getWheelDelta(e) * sensitivity)
        );

        clearTimeout(this._endTimer);
        this._endTimer = setTimeout(() => this._finish(), 220);
    },

    _start: function () {
        const map = this._map;
        map.stop();
        this._isWheeling = true;
        this._zoom = map.getZoom();
        this._center = map.getCenter();
        this._goalZoom = this._zoom;
        this._mousePoint = null;
        map._moveStart(true, false);
        this._frame = requestAnimationFrame(() => this._step());
    },

    _containerPointToExactLatLng: function (point) {
        const map = this._map;
        const offset = point.subtract(map.getSize().divideBy(2));
        return map.unproject(map.project(this._center, this._zoom).add(offset), this._zoom);
    },

    _step: function () {
        const map = this._map;
        const diff = this._goalZoom - this._zoom;

        if (Math.abs(diff) > 0.0005) {
            this._zoom = Math.abs(diff) < 0.005 ? this._goalZoom : this._zoom + diff * 0.2;
            const offset = this._mousePoint.subtract(map.getSize().divideBy(2));
            this._center = map.unproject(map.project(this._anchorLatLng, this._zoom).subtract(offset), this._zoom);
            map._move(this._center, this._zoom);
            this._compensateRounding();
        }

        this._frame = requestAnimationFrame(() => this._step());
    },

    _compensateRounding: function () {
        const map = this._map;
        const panePos = map._getMapPanePos();
        const exactOrigin = map
            .project(this._center, this._zoom)
            .subtract(map.getSize().divideBy(2))
            .add(panePos);
        const error = exactOrigin.subtract(map.getPixelOrigin());
        map.getPane("mapPane").style.transform =
            `translate3d(${panePos.x - error.x}px, ${panePos.y - error.y}px, 0)`;
    },

    // Scroll berhenti: target dirapikan ke kelipatan zoomSnap terdekat (0,1), tunggu animasi
    // mencapainya, lalu akhiri gerakan di sana.
    _finish: function () {
        const map = this._map;
        const snap = map.options.zoomSnap;
        if (snap > 0) {
            const snapped = Math.round(this._goalZoom / snap) * snap;
            this._goalZoom = Math.min(map.getMaxZoom(), Math.max(map.getMinZoom(), Number(snapped.toFixed(2))));
        }
        if (Math.abs(this._goalZoom - this._zoom) > 0.0005) {
            this._endTimer = setTimeout(() => this._finish(), 50);
            return;
        }
        this._stop();
        L.DomUtil.setPosition(this._map.getPane("mapPane"), this._map._getMapPanePos());
        this._map._moveEnd(true);
    },

    _stop: function () {
        this._isWheeling = false;
        cancelAnimationFrame(this._frame);
        clearTimeout(this._endTimer);
    },
});
L.Map.addInitHook("addHandler", "smoothWheelZoom", SmoothWheelZoom);

// zoomSnap 0.1: zoom berhenti di kelipatan 0,1 (scroll, pinch, fitBounds). Di zoom pecahan
// tile basemap diskalakan browser, jadi bisa sedikit kurang tajam dibanding zoom bulat.
// (Memperbesar ukuran tile untuk menutup celah justru memunculkan garis putih — jangan.)
const map = L.map("map", {
    zoomControl: true,
    attributionControl: true,
    zoomSnap: 0.1,
    zoomDelta: 1,
    scrollWheelZoom: false,
    smoothWheelZoom: true,
}).setView(mapConfig.center, mapConfig.zoom);

/**
 * Tombol Tampilan Penuh & Default Zoom, dibuat sebagai Leaflet control agar ukuran dan
 * jaraknya otomatis sama dengan tombol zoom bawaan di atasnya. Aksinya diikat di
 * DOMContentLoaded (#btn-fullscreen, #btn-default-zoom).
 */
const LeftControlButtons = L.Control.extend({
    options: { position: "topleft" },
    onAdd: function () {
        const container = L.DomUtil.create("div", "leaflet-bar leaflet-control");

        const fullscreenBtn = L.DomUtil.create("a", "", container);
        fullscreenBtn.id = "btn-fullscreen";
        fullscreenBtn.href = "#";
        fullscreenBtn.title = "Tampilan Penuh";
        fullscreenBtn.setAttribute("role", "button");
        fullscreenBtn.setAttribute("aria-label", "Tampilan Penuh");
        fullscreenBtn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';

        const homeBtn = L.DomUtil.create("a", "", container);
        homeBtn.id = "btn-default-zoom";
        homeBtn.href = "#";
        homeBtn.title = "Default Zoom";
        homeBtn.setAttribute("role", "button");
        homeBtn.setAttribute("aria-label", "Default Zoom");
        homeBtn.innerHTML = '<i class="bi bi-house-door-fill"></i>';

        L.DomEvent.on(container, "click", L.DomEvent.preventDefault);
        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.disableScrollPropagation(container);

        return container;
    },
});
map.addControl(new LeftControlButtons());

// ---- State global (juga dibaca file map-*.js lain) ----

// Opacity pilihan pengguna per mapset (key: nama mapset), diatur slider di Layer Aktif.
const layerOpacityState = new Map();

// Layer group Leaflet per mapset: layerGroups[kategori][sub kategori][mapset].
let layerGroups = {};
let currentBaseMap = null;
// Warna utama per nama mapset (cadangan bila style lengkap tidak tersedia).
let kategoriWarnaMap = {};
// Mapset yang datanya sudah selesai dimuat ke layer group-nya.
let loadedCategories = new Set();
// Cache IndexedDB (MapDataStore dari map-cache.js), dibuat saat DOMContentLoaded.
let mapDataStore = null;

/**
 * Tampilkan notifikasi singkat (toast) di tengah atas peta.
 * Pesan info berawalan "Memuat" diberi spinner dan tidak hilang sendiri.
 *
 * @returns {HTMLElement} elemen toast, untuk ditutup manual lewat hideToast()
 */
function showAlert(message, type = "info", persistent = false) {
    const toastContainer = document.getElementById("toast-container");
    if (!toastContainer) return;

    const colors = {
        success: "bg-green-500 text-white",
        danger: "bg-red-500 text-white",
        warning: "bg-yellow-500 text-black",
        info: "bg-blue-500 text-white",
    };

    const isLoading =
        type === "info" &&
        (message.includes("Memuat") || message.includes("Loading"));

    const toast = document.createElement("div");
    toast.className = `
        flex items-center px-4 py-3 rounded-lg shadow-lg text-sm font-medium
        ${colors[type] || colors.info}
        transform transition-all duration-500 opacity-0 translate-y-2
    `;

    if (isLoading) {
        toast.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>${message}</span>
            </div>
        `;
    } else {
        toast.innerHTML = `
            <span class="flex-1">${message}</span>
            <button class="ml-3 text-lg leading-none focus:outline-none">&times;</button>
        `;

        toast
            .querySelector("button")
            .addEventListener("click", () => hideToast(toast));
    }

    toastContainer.appendChild(toast);

    // Kelas transisi dilepas setelah elemen masuk DOM supaya animasi masuknya berjalan.
    setTimeout(() => {
        toast.classList.remove("opacity-0", "translate-y-2");
        toast.classList.add("opacity-100", "translate-y-0");
    }, 50);

    if (!persistent && !isLoading) {
        const delay = type === "success" ? 4000 : 6000;
        setTimeout(() => hideToast(toast), delay);
    }

    return toast;
}

function hideToast(toast) {
    if (!toast || !toast.parentNode) return;

    toast.classList.add("opacity-0", "translate-y-2");
    setTimeout(() => {
        if (toast && toast.parentNode) {
            toast.remove();
        }
    }, 500);
}

// =====================================================================
// Style fitur — aturan sama dengan preview peta di dashboard (spatial-layers/show)
// =====================================================================

/**
 * Rasio isian area terhadap opacity style — sama dengan preview peta di dashboard
 * (spatial-layers/show: fillOpacity = opacity * 0.4), supaya area tetap tembus pandang.
 */
const POLYGON_FILL_RATIO = 0.4;

/**
 * Style Layer (mapset) dari metadata katalog: hasil PublicMapCatalog::style() di server.
 */
function getLayerStyle(categoryName) {
    const item = (window.MARIMOI_CATEGORY_METADATA?.all_categories || []).find((c) => c.nama === categoryName);
    return (
        item?.style || {
            type: "simple",
            color: kategoriWarnaMap[categoryName] || "#2563eb",
            icon: null,
            is_marker: false,
            opacity: 1,
            size: 6,
            field: null,
            classes: [],
        }
    );
}

/**
 * Kelas categorized (nilai sama persis) / graduated (min ≤ nilai ≤ max) yang cocok.
 */
function matchStyleClass(layerStyle, properties) {
    if (!layerStyle.field || !Array.isArray(layerStyle.classes)) {
        return null;
    }
    const raw = properties?.[layerStyle.field];
    if (raw === undefined || raw === null) {
        return null;
    }

    if (layerStyle.type === "categorized") {
        return layerStyle.classes.find((cls) => String(cls.value ?? "") === String(raw)) || null;
    }

    const value = Number(raw);
    if (layerStyle.type === "graduated" && !Number.isNaN(value)) {
        return (
            layerStyle.classes.find((cls) => {
                const min = cls.min === null || cls.min === undefined || cls.min === "" ? -Infinity : Number(cls.min);
                const max = cls.max === null || cls.max === undefined || cls.max === "" ? Infinity : Number(cls.max);
                return value >= min && value <= max;
            }) || null
        );
    }

    return null;
}

/**
 * Style akhir satu feature, urutan sama dengan dashboard: style Layer → kelas
 * (categorized/graduated) → style_override per Data Spasial.
 */
function resolveFeatureStyle(feature, categoryName) {
    const layerStyle = getLayerStyle(categoryName);
    const matchedClass = matchStyleClass(layerStyle, feature.properties);
    const override = feature.properties?.style_override;

    const resolved = {
        color: matchedClass?.color || layerStyle.color || "#2563eb",
        icon: layerStyle.is_marker ? layerStyle.icon : null,
        opacity: Number(layerStyle.opacity ?? 1),
        size: Number(layerStyle.size ?? 6),
    };

    if (override) {
        resolved.color = override.color ?? resolved.color;
        resolved.icon = override.is_marker ? override.icon || null : null;
        resolved.opacity = Number(override.opacity ?? resolved.opacity);
        resolved.size = Number(override.size ?? resolved.size);
    }

    return resolved;
}

/**
 * Style Leaflet untuk area/garis (fungsi `style` L.geoJSON).
 */
function getStyleForCategory(categoryName) {
    return function (feature) {
        const s = resolveFeatureStyle(feature, categoryName);
        const isLine = ["LineString", "MultiLineString"].includes(feature.geometry?.type);

        return {
            color: s.color,
            weight: 2,
            opacity: s.opacity,
            fillColor: s.color,
            fillOpacity: s.opacity * POLYGON_FILL_RATIO,
            lineCap: "round",
            lineJoin: "round",
            interactive: true,
            className: isLine ? "leaflet-interactive-line" : "leaflet-interactive-polygon",
        };
    };
}

/**
 * Simbol titik: ikon berwarna bila style memakai ikon, selain itu lingkaran berjari-jari
 * `size` — sama dengan preview peta di dashboard.
 */
function pointToLayerForCategory(categoryName) {
    return function (feature, latlng) {
        const s = resolveFeatureStyle(feature, categoryName);

        if (s.icon) {
            const pixels = s.size * 4;
            return L.marker(latlng, {
                opacity: s.opacity,
                icon: L.divIcon({
                    html: `<i class="${s.icon}" style="color:${s.color};font-size:${pixels}px;line-height:1;"></i>`,
                    className: "map-feature-icon",
                    iconSize: [pixels, pixels],
                    iconAnchor: [pixels / 2, pixels / 2],
                }),
            });
        }

        return L.circleMarker(latlng, {
            radius: s.size,
            color: s.color,
            fillColor: s.color,
            weight: 1,
            opacity: s.opacity,
            fillOpacity: s.opacity,
        });
    };
}

// =====================================================================
// Legenda
// =====================================================================

/**
 * Atribut nama yang dicoba (berurutan) untuk label warna per-fitur di legenda.
 */
const LEGEND_LABEL_KEYS = ["NAMOBJ", "NAMA", "nama", "name", "KABUPATEN", "WADMKK", "KEGIATAN", "Keterangan", "label"];

/**
 * Jenis simbol legenda dari geometri fitur pertama di layer: "point", "line", atau "polygon".
 */
function legendGeometryKind(layerGroup) {
    let kind = null;
    const visit = (layer) => {
        if (kind) return;
        const type = layer.feature?.geometry?.type;
        if (type) {
            kind = /Point/.test(type) ? "point" : /LineString/.test(type) ? "line" : "polygon";
        } else if (typeof layer.eachLayer === "function") {
            layer.eachLayer(visit);
        }
    };
    visit(layerGroup);
    return kind || "polygon";
}

/**
 * Warna per-feature dari style_override (mis. tiap kabupaten beda warna), dikelompokkan
 * per warna dengan nama feature-nya sebagai label.
 */
function legendOverrideItems(layerGroup) {
    const byColor = new Map();
    const visit = (layer) => {
        if (typeof layer.eachLayer === "function" && !layer.feature) {
            layer.eachLayer(visit);
            return;
        }
        const props = layer.feature?.properties;
        const color = props?.style_override?.color;
        if (!color) return;
        const key = LEGEND_LABEL_KEYS.find((name) => props[name]);
        const label = key ? String(props[key]) : null;
        if (!byColor.has(color)) byColor.set(color, new Set());
        if (label) byColor.get(color).add(label);
    };
    visit(layerGroup);
    return Array.from(byColor.entries()).map(([color, labels]) => ({
        color,
        label: labels.size ? Array.from(labels).sort().join(", ") : color,
    }));
}

function legendSymbol(kind, color, icon, opacity) {
    const symbol = document.createElement("span");
    symbol.className = `legend-symbol is-${icon ? "icon" : kind}`;
    symbol.style.setProperty("--legend-color", color);
    symbol.style.setProperty("--legend-fill-opacity", String((opacity ?? 1) * POLYGON_FILL_RATIO));
    if (icon) {
        const iconEl = document.createElement("i");
        iconEl.className = icon;
        symbol.appendChild(iconEl);
    }
    return symbol;
}

function legendRow(kind, color, icon, opacity, text, isSub) {
    const row = document.createElement("div");
    row.className = `legend-row${isSub ? " is-sub" : ""}`;
    row.appendChild(legendSymbol(kind, color, icon, opacity));
    const label = document.createElement("span");
    label.className = "legend-label";
    label.textContent = text;
    row.appendChild(label);
    return row;
}

/**
 * Legenda layer yang sedang tampil, urut sesuai daftar Layer Aktif (atas = paling atas
 * di peta), memakai style dari dashboard: simbol sesuai geometri, kelas categorized/
 * graduated, dan warna per-feature (style_override).
 */
function generateLegend() {
    const legendContainer = document.getElementById("legend-content");
    if (!legendContainer) return;

    legendContainer.innerHTML = "";
    const entries = (window.MarimoiCatalog?.getActiveEntries() || []).filter(
        (entry) => map.hasLayer(entry.layerGroup) && entry.layerGroup.getLayers().length > 0
    );

    if (entries.length === 0) {
        legendContainer.innerHTML = `
            <div class="legend-empty">
                <i class="bi bi-layers"></i>
                <p>Tidak ada layer aktif</p>
                <small>Aktifkan layer untuk melihat legenda</small>
            </div>`;
        return;
    }

    const maxSubItems = 15;

    entries.forEach((entry) => {
        const layerStyle = getLayerStyle(entry.leafName);
        const kind = legendGeometryKind(entry.layerGroup);
        const icon = layerStyle.is_marker ? layerStyle.icon : null;

        const group = document.createElement("section");
        group.className = "legend-group";

        const title = document.createElement("h6");
        title.className = "legend-title";
        title.textContent = entry.leafName;
        group.appendChild(title);

        let subItems = [];
        if (["categorized", "graduated"].includes(layerStyle.type) && layerStyle.classes?.length) {
            subItems = layerStyle.classes.map((cls) => ({
                color: cls.color,
                label: cls.label || (layerStyle.type === "categorized" ? cls.value : `${cls.min ?? ""} – ${cls.max ?? ""}`),
            }));
        }
        subItems = subItems.concat(legendOverrideItems(entry.layerGroup));

        if (subItems.length === 0) {
            group.appendChild(legendRow(kind, layerStyle.color, icon, layerStyle.opacity, entry.leafName, false));
        } else {
            subItems.slice(0, maxSubItems).forEach((item) => {
                group.appendChild(legendRow(kind, item.color, icon, layerStyle.opacity, item.label, true));
            });
            if (subItems.length > maxSubItems) {
                const more = document.createElement("p");
                more.className = "legend-more";
                more.textContent = `+${subItems.length - maxSubItems} warna lainnya`;
                group.appendChild(more);
            }
        }

        legendContainer.appendChild(group);
    });
}

// =====================================================================
// Opacity per layer (slider di Layer Aktif)
// =====================================================================

/**
 * Style opacity untuk satu fitur: area memakai rasio isian dashboard, lingkaran titik
 * terisi penuh. (Ikon titik diatur lewat setOpacity, lihat setLayerGroupOpacity.)
 */
function opacityStyleFor(leaf, opacity) {
    const fillOpacity = leaf instanceof L.CircleMarker ? opacity : opacity * POLYGON_FILL_RATIO;
    return { opacity, fillOpacity };
}

/**
 * Terapkan opacity ke seluruh fitur di layer group (rekursif). Fitur yang sedang
 * tersaring Filter Data dilewati supaya tidak muncul lagi.
 */
function setLayerGroupOpacity(layer, opacity) {
    if (typeof layer.eachLayer === "function") {
        layer.eachLayer((child) => setLayerGroupOpacity(child, opacity));
    } else if (layer.marimoiFilteredOut) {
        return;
    } else if (typeof layer.setStyle === "function") {
        layer.setStyle(opacityStyleFor(layer, opacity));
    } else if (typeof layer.setOpacity === "function") {
        layer.setOpacity(opacity);
    }
}

/**
 * Opacity yang sedang dipakai sebuah layer group, untuk nilai awal slider opacity.
 *
 * Anak langsung layer group adalah pembungkus L.GeoJSON (satu per fitur) yang tidak
 * menyimpan angka opacity, jadi pencarian turun rekursif sampai Path/Marker asli.
 */
function getLayerGroupOpacity(layerGroup) {
    let opacity = null;

    function visit(layer) {
        if (opacity !== null) return;

        if (layer.eachLayer) {
            layer.eachLayer(visit);
            return;
        }

        // `opacity` (garis tepi / ikon) = nilai style; fillOpacity area sudah dikalikan rasio isian.
        if (layer.options && typeof layer.options.opacity === "number") {
            opacity = layer.options.opacity;
        }
    }

    if (layerGroup.eachLayer) {
        layerGroup.eachLayer(visit);
    }

    return opacity ?? 1;
}

// =====================================================================
// Filter Data (kabupaten / tahun / OPD). Kontrolnya di modal Katalog (map-catalog.js).
// =====================================================================

/**
 * Nilai filter yang sedang berlaku, dibaca dari 3 <select> di panel Filter.
 * String kosong berarti "semua" (dimensi itu tidak disaring).
 */
function getActiveFilterValues() {
    return {
        kabupaten: document.getElementById("filter-kabupaten")?.value || "",
        tahun: document.getElementById("filter-tahun")?.value || "",
        opd_pengelola: document.getElementById("filter-opd")?.value || "",
    };
}

/**
 * Tambahkan opsi yang belum ada ke <select> tanpa mengubah pilihan saat ini
 * (hanya menambah), sehingga aman dipanggil berulang setiap data baru dimuat.
 */
function ensureFilterOptions(selectEl, values) {
    if (!selectEl) return;
    const existing = new Set(Array.from(selectEl.options).map((o) => o.value));

    Array.from(values).sort().forEach((value) => {
        if (value === "" || existing.has(String(value))) return;
        const opt = document.createElement("option");
        opt.value = value;
        opt.textContent = value;
        selectEl.appendChild(opt);
        existing.add(String(value));
    });
}

/**
 * Terapkan Filter Data ke setiap fitur layer yang tampil: fitur yang tidak cocok
 * disembunyikan (opacity 0), sekaligus mengumpulkan nilai KABUPATEN/tahun/OPD dari
 * data yang sudah dimuat sebagai opsi tambahan dropdown filter.
 */
function refreshFilterPanel() {
    const filters = getActiveFilterValues();
    const kabupatenSet = new Set();
    const tahunSet = new Set();
    const opdSet = new Set();
    let shown = 0;
    let total = 0;

    function matches(props) {
        if (filters.kabupaten && (props.KABUPATEN || "") !== filters.kabupaten) return false;
        if (filters.tahun && String(props.tahun || "") !== filters.tahun) return false;
        if (filters.opd_pengelola && (props.opd_pengelola || "") !== filters.opd_pengelola) return false;
        return true;
    }

    // Fitur yang lolos memakai opacity pilihan pengguna (slider Layer Aktif); yang
    // tersaring ditandai marimoiFilteredOut supaya slider tidak memunculkannya lagi.
    function applyVisibility(leaf, visible) {
        const userOpacity = layerOpacityState.get(leaf.feature?.properties?.kategori);
        leaf.marimoiFilteredOut = !visible;

        if (typeof leaf.setStyle === "function") {
            if (visible) {
                const opacityStyle = typeof userOpacity === "number" ? opacityStyleFor(leaf, userOpacity) : {};
                leaf.setStyle({ ...(leaf.marimoiOriginalStyle || {}), ...opacityStyle });
            } else {
                leaf.marimoiOriginalStyle = leaf.marimoiOriginalStyle || { ...leaf.options };
                leaf.setStyle({ opacity: 0, fillOpacity: 0 });
            }
        } else if (typeof leaf.setOpacity === "function") {
            leaf.setOpacity(visible ? (userOpacity ?? 1) : 0);
        }
    }

    function visit(layer) {
        if (layer.eachLayer) {
            layer.eachLayer(visit);
            return;
        }
        if (!layer.feature?.properties) return;

        const props = layer.feature.properties;
        total++;
        if (props.KABUPATEN) kabupatenSet.add(props.KABUPATEN);
        if (props.tahun) tahunSet.add(String(props.tahun));
        if (props.opd_pengelola) opdSet.add(props.opd_pengelola);

        const visible = matches(props);
        if (visible) shown++;
        applyVisibility(layer, visible);
    }

    Object.values(layerGroups).forEach((secondLevel) => {
        Object.values(secondLevel).forEach((thirdLevel) => {
            Object.values(thirdLevel).forEach((leafGroup) => {
                if (map.hasLayer(leafGroup)) leafGroup.eachLayer(visit);
            });
        });
    });

    ensureFilterOptions(document.getElementById("filter-kabupaten"), kabupatenSet);
    ensureFilterOptions(document.getElementById("filter-tahun"), tahunSet);
    ensureFilterOptions(document.getElementById("filter-opd"), opdSet);

    const countEl = document.getElementById("filter-count");
    if (countEl) {
        countEl.textContent = total > 0 ? `${shown} dari ${total} titik ditampilkan` : "Belum ada data dimuat";
    }

    const activeCount = Object.values(filters).filter(Boolean).length;
    const summaryEl = document.getElementById("filter-summary-count");
    if (summaryEl) {
        summaryEl.textContent = activeCount > 0 ? String(activeCount) : "";
        summaryEl.classList.toggle("hidden", activeCount === 0);
    }
}

/**
 * Isi dropdown filter dari seluruh data publik (/geojson/filter-options), supaya
 * pilihan sudah tersedia sebelum ada layer yang dimuat. Dipanggil sekali saat peta dibuka.
 */
async function loadFilterOptionsFromServer() {
    try {
        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);
        const params = new URLSearchParams();
        if (tipeLayer.type) params.set("type", tipeLayer.type);
        if (tipeLayer.sub_type) params.set("sub_type", tipeLayer.sub_type);
        if (tipeLayer.year) params.set("year", tipeLayer.year);

        const response = await fetch(`/geojson/filter-options?${params.toString()}`);
        if (!response.ok) return;

        const data = await response.json();
        ensureFilterOptions(document.getElementById("filter-kabupaten"), data.kabupaten || []);
        ensureFilterOptions(document.getElementById("filter-tahun"), (data.tahun || []).map(String));
        ensureFilterOptions(document.getElementById("filter-opd"), data.opd_pengelola || []);
    } catch (error) {
        console.error("Gagal memuat opsi filter:", error);
    }
}

/**
 * Nama mapset yang punya minimal satu fitur cocok dengan filter (/geojson/filter-categories).
 * Dipakai katalog untuk menyaring kartu, dan link share untuk mengaktifkan layer yang cocok.
 */
async function fetchCategoriesMatchingFilter(filters) {
    const urlPath = window.location.pathname.replace(/\/$/, "");
    const tipeLayer = getDataType(urlPath);
    const params = new URLSearchParams();
    if (tipeLayer.type) params.set("type", tipeLayer.type);
    if (tipeLayer.sub_type) params.set("sub_type", tipeLayer.sub_type);
    if (tipeLayer.year) params.set("year", tipeLayer.year);
    if (filters.kabupaten) params.set("kabupaten", filters.kabupaten);
    if (filters.tahun) params.set("tahun", filters.tahun);
    if (filters.opd_pengelola) params.set("opd_pengelola", filters.opd_pengelola);

    let categoryNames = [];
    try {
        const response = await fetch(`/geojson/filter-categories?${params.toString()}`);
        if (response.ok) {
            const data = await response.json();
            categoryNames = Array.isArray(data.categories) ? data.categories : [];
        }
    } catch (error) {
        console.error("Gagal memuat daftar layer yang cocok dengan filter:", error);
    }

    return categoryNames;
}

/**
 * Aktifkan semua mapset yang cocok dengan filter.
 */
async function loadCategoriesMatchingFilter(filters) {
    const categoryNames = await fetchCategoriesMatchingFilter(filters);
    await window.MarimoiCatalog?.activateByNames(categoryNames);
}

/**
 * Terapkan filter dari link share: aktifkan mapset yang cocok lebih dulu, lalu saring
 * fiturnya (refreshFilterPanel). Kontrol filter dikunci selama pemuatan.
 */
async function applyStructuredFilters() {
    const filters = getActiveFilterValues();
    const hasActiveFilter = Object.values(filters).some(Boolean);

    if (!hasActiveFilter) {
        refreshFilterPanel();
        return;
    }

    const filterFields = ["filter-kabupaten", "filter-tahun", "filter-opd", "btn-reset-filter"];
    filterFields.forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.disabled = true;
    });

    const countEl = document.getElementById("filter-count");
    if (countEl) countEl.textContent = "Mencari layer yang cocok dengan filter...";

    try {
        await loadCategoriesMatchingFilter(filters);
    } finally {
        filterFields.forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.disabled = false;
        });
        refreshFilterPanel();
    }
}

// =====================================================================
// Popup fitur & pemuatan data
// =====================================================================

/**
 * Pasang popup ringkas pada satu fitur: atribut utama, geometri, koordinat, dan tombol
 * Zoom To serta Detail (membuka panel detail di map-feature-detail.js).
 */
function bindPopupContent(feature, layer, urlPath) {
    const props = feature.properties;
    let content = `
        <div class="p-4 max-w-xs bg-white rounded-lg shadow-lg border border-gray-200">
            <h5 class="text-md font-semibold text-blue-600 mb-3 border-b border-gray-200 pb-2">
                ${props.kategori || "Feature"}
            </h5>`;

    if (props.gambar) {
        content += `
            <div class="mb-3">
                <img src="${props.gambar}" alt="Gambar ${props.KEGIATAN}"
                    class="w-full h-32 object-cover rounded-md border border-gray-300 shadow-sm">
            </div>`;
    }

    content += `
        <div class="space-y-2 mb-4">
            <div class="max-h-40 overflow-y-auto">
                <table class="w-full text-[9px]" >`;

    // Popup hanya menampilkan atribut ringkas ini; atribut lengkap ada di panel detail.
    const allowedKeys = ["KEGIATAN", "TAHUN", "KABUPATEN", "URUSAN", "SUMBER_DATA", "OPD_PENGELOLA", "TANGGAL_DATA"];
    Object.entries(props).forEach(([key, value]) => {
        if (allowedKeys.includes(key.toUpperCase()) && value) {
            const label = key
                .replace(/_/g, " ")
                .replace(/\b\w/g, (l) => l.toUpperCase());
            content += `
                <tr class="border-b border-gray-100">
                    <td class="text-[9px] font-medium text-gray-700 py-1 pr-2 align-top">${label}</td>
                    <td class="text-[9px] text-gray-600 py-1">${value}</td>
                </tr>`;
        }
    });

    content += `</table></div></div>`;

    const geom = feature.geometry;
    let center = null;

    if (geom) {
        const type = geom.type;
        content += `
            <div class="border-t border-gray-200 pt-3 mb-3">
                <table class="w-full text-[9px]">
                    <tr class="border-b border-gray-100">
                        <td class="text-[9px] font-medium text-gray-700 py-1 pr-2">Geometry</td>
                        <td class="text-[9px] text-gray-600 py-1">${type}</td>
                    </tr>`;

        // Panjang garis (km) dengan rumus haversine antar titik berurutan.
        if (type === "LineString" && Array.isArray(geom.coordinates)) {
            let length = 0;
            for (let i = 1; i < geom.coordinates.length; i++) {
                const [lon1, lat1] = geom.coordinates[i - 1];
                const [lon2, lat2] = geom.coordinates[i];
                const R = 6371;
                const rad = Math.PI / 180;
                const dLat = (lat2 - lat1) * rad;
                const dLon = (lon2 - lon1) * rad;
                const a =
                    Math.sin(dLat / 2) ** 2 +
                    Math.cos(lat1 * rad) *
                        Math.cos(lat2 * rad) *
                        Math.sin(dLon / 2) ** 2;
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                length += R * c;
            }
            content += `
                <tr class="border-b border-gray-100">
                    <td class="text-[9px] font-medium text-gray-700 py-1 pr-2">Panjang</td>
                    <td class="text-[9px] text-gray-600 py-1">${length.toFixed(
                        2
                    )} km</td>
                </tr>`;
        }

        // Titik perkiraan untuk koordinat & Zoom To: titik tengah daftar koordinat.
        if (type === "Point") {
            center = geom.coordinates;
        } else if (type === "LineString") {
            const mid = Math.floor(geom.coordinates.length / 2);
            center = geom.coordinates[mid];
        } else if (type === "Polygon") {
            const poly = geom.coordinates[0];
            const mid = Math.floor(poly.length / 2);
            center = poly[mid];
        } else if (type === "MultiPolygon") {
            const poly = geom.coordinates[0][0];
            const mid = Math.floor(poly.length / 2);
            center = poly[mid];
        }

        if (center && center.length >= 2) {
            content += `
                <tr>
                    <td class="text-[9px] ont-medium text-gray-700 py-1 pr-2">Koordinat</td>
                    <td class="text-[9px] text-gray-600 py-1 font-mono text-xs">${center[1].toFixed(
                        5
                    )}, ${center[0].toFixed(5)}</td>
                </tr>`;
        }
        content += `</table></div>`;
    }

    const lat = center?.[1] || 0;
    const lng = center?.[0] || 0;

    content += `
        <div class="popup-actions">
            <button type="button" class="zoomToBtn popup-action popup-action-zoom"
                data-lat="${lat}" data-lng="${lng}">
                <i class="bi bi-zoom-in"></i>
                Zoom To
            </button>
            <button type="button" class="featureDetailBtn popup-action popup-action-detail">
                <i class="bi bi-eye"></i>
                Detail
            </button>
        </div>
    </div>`;

    const popupOptions = {
        maxWidth: 320,
        minWidth: 280,
        className: "tailwind-popup",
        closeButton: true,
        autoClose: false,
        closeOnEscapeKey: true,
    };

    layer.bindPopup(content, popupOptions);

    layer.on("popupopen", function () {
        const popupNode = layer.getPopup().getElement();
        const zoomButton = popupNode.querySelector(".zoomToBtn");

        popupNode.querySelector(".featureDetailBtn")?.addEventListener("click", () => {
            layer.closePopup();
            window.MarimoiFeatureDetail?.open(feature, layer);
        });

        if (zoomButton) {
            zoomButton.addEventListener("click", function () {
                const geom = feature.geometry;
                if (!geom) return;

                const mapInstance = layer._map;
                if (geom.type === "Point") {
                    const lat = parseFloat(this.getAttribute("data-lat"));
                    const lng = parseFloat(this.getAttribute("data-lng"));
                    mapInstance.setView([lat, lng], 15);
                } else if (geom.type === "LineString") {
                    const latlngs = geom.coordinates.map(([lng, lat]) => [
                        lat,
                        lng,
                    ]);
                    mapInstance.fitBounds(latlngs);
                } else if (geom.type === "Polygon") {
                    const latlngs = geom.coordinates[0].map(([lng, lat]) => [
                        lat,
                        lng,
                    ]);
                    mapInstance.fitBounds(latlngs);
                } else if (geom.type === "MultiPolygon") {
                    let allLatLngs = [];
                    geom.coordinates.forEach((poly) => {
                        poly[0].forEach(([lng, lat]) => {
                            allLatLngs.push([lat, lng]);
                        });
                    });
                    if (allLatLngs.length > 0) {
                        mapInstance.fitBounds(allLatLngs);
                    }
                }
            });
        }
    });
}

/**
 * Ganti basemap aktif dengan basemap dari mapConfig.baseMapsList.
 */
function changeBaseMap(baseMapId) {
    if (currentBaseMap) {
        map.removeLayer(currentBaseMap);
    }

    const config = mapConfig.baseMapsList.find((bm) => bm.id === baseMapId);
    if (config) {
        currentBaseMap = L.tileLayer(config.url, {
            subdomains: config.subdomains || [],
            minZoom: config.minZoom || 4,
            maxZoom: config.maxZoom || 18,
        });
        currentBaseMap.addTo(map);
    }
}

/**
 * Jenis data peta dari path URL. Halaman lama (PSD, PSN, musrenbang, pokir) kini
 * dialihkan ke /peta-interaktif, jadi praktis selalu "tematik".
 */
function getDataType(urlPath) {
    const defaultResult = { type: "tematik", sub_type: null, year: null };

    switch (urlPath) {
        case "/proyek-strategis-daerah":
            return { type: "proyek_strategis", sub_type: "psd", year: null };
        case "/proyek-strategis-nasional":
            return { type: "proyek_strategis", sub_type: "psn", year: null };
        case "/peta-interaktif":
            return { type: "tematik", sub_type: null, year: null };
        case "/usulan-musrenbang":
            return { type: "usulan_musrenbang", sub_type: null, year: null };
        case "/pokir-dprd":
            return { type: "pokir_dprd", sub_type: null, year: null };
        default:
            return defaultResult;
    }
}

/**
 * Layer group untuk satu kategori "leaf". Titik tidak dikelompokkan (tanpa clustering):
 * setiap fitur tampil apa adanya dengan simbol dari style dashboard.
 */
function createCategoryLayerGroup() {
    return L.layerGroup();
}

/**
 * Muat daftar mapset (tanpa data spasial) dari /geojson?metadata_only=true, lalu bangun
 * layerGroups[kategori][sub kategori][mapset] (maksimal 3 level, lihat PublicMapCatalog
 * di server) dan isi Katalog Peta.
 *
 * Cache-first: metadata dari IndexedDB langsung dipakai, versi terbaru diambil di latar
 * untuk kunjungan berikutnya.
 */
async function loadCategoriesMetadata() {
    try {
        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);
        const dataType = tipeLayer.type;
        const subType = tipeLayer.sub_type || null;
        const year = tipeLayer.year || null;

        let queryString = "?metadata_only=true";
        if (dataType) queryString += `&type=${dataType}`;
        if (subType) queryString += `&sub_type=${subType}`;
        if (year) queryString += `&year=${year}`;

        const metaKey = `metadata_${dataType || "default"}_${subType || "none"}_${year || "all"}`;

        const fetchMetadata = async () => {
            const response = await fetch(`/geojson${queryString}`);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response.json();
        };

        let data = mapDataStore ? await mapDataStore.getCachedMetadata(metaKey) : null;
        const fromCache = Boolean(data);
        let loadingToast = null;

        if (fromCache) {
            fetchMetadata()
                .then((fresh) => mapDataStore.setCachedMetadata(metaKey, fresh))
                .catch(() => {});
        } else {
            loadingToast = showAlert("Memuat daftar kategori...", "info", true);
            data = await fetchMetadata();
            if (mapDataStore) {
                await mapDataStore.setCachedMetadata(metaKey, data);
            }
        }

        kategoriWarnaMap = {};

        if (Array.isArray(data.all_categories)) {
            data.all_categories.forEach((cat) => {
                if (!cat.nama || !cat.warna) return;
                kategoriWarnaMap[cat.nama] = cat.warna;
            });
        }

        // all_categories berisi item datar dengan parent_id: level 1 = kategori, level 2 =
        // node (sub kategori) atau mapset langsung di bawah kategori, level 3 = mapset.
        // Mapset yang tidak punya level di bawahnya memakai namanya sendiri sebagai kunci
        // berikutnya, misalnya layerGroups[kategori][mapset][mapset].
        layerGroups = {};

        if (data.all_categories?.length) {
            const rootCategories = data.all_categories.filter(cat => !cat.parent_id);
            const secondLevelCategories = data.all_categories.filter(cat => 
                cat.parent_id && rootCategories.some(root => root.id === cat.parent_id)
            );
            const thirdLevelCategories = data.all_categories.filter(cat => 
                cat.parent_id && secondLevelCategories.some(second => second.id === cat.parent_id)
            );

            rootCategories.forEach((root) => {
                layerGroups[root.nama] = {};
                const childrenL2 = secondLevelCategories.filter(child => child.parent_id === root.id);
                
                if (childrenL2.length > 0) {
                    childrenL2.forEach((childL2) => {
                        layerGroups[root.nama][childL2.nama] = {};
                        const childrenL3 = thirdLevelCategories.filter(child => child.parent_id === childL2.id);
                        
                        if (childrenL3.length > 0) {
                            childrenL3.forEach((childL3) => {
                                layerGroups[root.nama][childL2.nama][childL3.nama] = createCategoryLayerGroup();
                            });
                        } else {
                            layerGroups[root.nama][childL2.nama][childL2.nama] = createCategoryLayerGroup();
                        }
                    });
                } else {
                    layerGroups[root.nama][root.nama] = {};
                    layerGroups[root.nama][root.nama][root.nama] = createCategoryLayerGroup();
                }
            });
        } else if (data.root_categories) {
            // Cadangan untuk respons berbentuk pohon (root_categories[].children).
            data.root_categories.forEach((root) => {
                const rootName = root.nama;
                layerGroups[rootName] = {};
                
                if (Array.isArray(root.children) && root.children.length > 0) {
                    root.children.forEach((childL2) => {
                        layerGroups[rootName][childL2.nama] = {};
                        
                        if (Array.isArray(childL2.children) && childL2.children.length > 0) {
                            childL2.children.forEach((childL3) => {
                                layerGroups[rootName][childL2.nama][childL3.nama] = createCategoryLayerGroup();
                            });
                        } else {
                            layerGroups[rootName][childL2.nama][childL2.nama] = createCategoryLayerGroup();
                        }
                    });
                } else {
                    layerGroups[rootName][rootName] = {};
                    layerGroups[rootName][rootName][rootName] = createCategoryLayerGroup();
                }
            });
        }

        window.MARIMOI_CATEGORY_METADATA = data;
        updateLayerList();
        generateLegend();

        if (loadingToast) hideToast(loadingToast);

        // Pesan sukses hanya saat daftar diambil dari server (bukan dari cache).
        if (!fromCache) {
            showAlert(
                "Kategori berhasil dimuat. Pilih layer untuk memuat data.",
                "success"
            );
        }
    } catch (error) {
        console.error("Error loading categories metadata:", error);
        showAlert(`Gagal memuat kategori: ${error.message}`, "danger");

        const layerListContainer = document.getElementById("layer-list");
        if (layerListContainer) {
            layerListContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center text-center h-[120px] text-gray-700">
                    <i class="bi bi-x-circle text-red" style="font-size:2rem;"></i>
                    <span class="mt-2 text-sm">Terjadi kesalahan saat memuat kategori.</span>
                    <button class="mt-3 text-sm py-1 px-3 rounded bg-gray-500 text-white hover:bg-gray-600 transition" onclick="loadCategoriesMetadata()">Coba Lagi</button>
                </div>`;
        }
    }
}

/**
 * Gambar satu fitur GeoJSON ke layer group dengan style & popup-nya.
 *
 * @returns {boolean} false bila fitur tidak punya geometri
 */
function addFeatureToLayer(feature, targetLayer, categoryName, urlPath) {
    if (!feature || !feature.geometry) {
        return false;
    }

    L.geoJSON(feature, {
        pointToLayer: pointToLayerForCategory(categoryName),
        style: getStyleForCategory(categoryName),
        onEachFeature: (f, l) => {
            try {
                bindPopupContent(f, l, urlPath);
            } catch (popupError) {
                // Popup yang gagal dibuat tidak boleh menghalangi fiturnya tampil.
            }
        },
    }).addTo(targetLayer);

    return true;
}

/**
 * Baca seluruh potongan (chunk) data satu mapset dari cache IndexedDB.
 *
 * @returns {Promise<Array|null>} semua potongan bila lengkap di cache; null bila ada yang
 *   belum tersimpan sehingga data harus diambil dari jaringan
 */
async function readAllCachedChunks(baseParams, maxRecords, chunkSize) {
    if (!mapDataStore) return null;

    const chunks = [];
    let offset = 0;
    let totalLoaded = 0;

    while (totalLoaded < maxRecords) {
        const key = mapDataStore.generateCacheKey({
            ...baseParams,
            limit: Math.min(chunkSize, maxRecords - totalLoaded),
            offset,
        });
        const chunk = await mapDataStore.getCachedData(key);

        if (!chunk) return null;
        chunks.push(chunk);

        const valid = (chunk.features || []).filter((f) => f && f.geometry).length;
        totalLoaded += valid;

        const hasMore = chunk.meta?.has_more === true && totalLoaded < maxRecords && valid > 0;
        if (!hasMore) break;

        offset += chunkSize;
    }

    return chunks.length ? chunks : null;
}

/**
 * Gambar potongan data dari cache per 200 fitur, dengan jeda antar irisan agar halaman
 * tetap responsif. Berhenti bila `signal` dibatalkan.
 *
 * @returns {Promise<number>} jumlah fitur yang digambar
 */
async function renderCachedChunks(chunks, targetLayer, categoryName, urlPath, { signal, onProgress } = {}) {
    const slice = 200;
    let added = 0;

    for (const chunk of chunks) {
        const features = chunk.features || [];

        for (let i = 0; i < features.length; i += slice) {
            throwIfAborted(signal);
            features.slice(i, i + slice).forEach((feature) => {
                try {
                    if (addFeatureToLayer(feature, targetLayer, categoryName, urlPath)) {
                        added++;
                    }
                } catch (featureError) {
                    // Satu feature rusak tidak boleh menggagalkan seluruh layer.
                }
            });
            onProgress?.(added);

            if (i + slice < features.length) {
                await new Promise((resolve) => setTimeout(resolve, 0));
            }
        }
    }

    return added;
}

/**
 * Hentikan pemuatan (lempar AbortError) bila pengguna sudah membatalkannya.
 */
function throwIfAborted(signal) {
    if (signal?.aborted) {
        throw new DOMException("Pemuatan layer dibatalkan", "AbortError");
    }
}

/**
 * Layer group milik satu mapset di layerGroups (lihat loadCategoriesMetadata).
 */
function findCategoryLayerGroup(categoryName, parentName, grandparentName) {
    if (grandparentName && parentName) {
        return layerGroups[grandparentName]?.[parentName]?.[categoryName] || null;
    }
    if (parentName) {
        return layerGroups[parentName]?.[categoryName]?.[categoryName] || null;
    }
    return layerGroups[categoryName]?.[categoryName]?.[categoryName] || null;
}

/**
 * Muat data satu kategori (mapset) ke layer group-nya: dari cache IndexedDB bila lengkap,
 * kalau tidak per potongan dari /geojson. Fitur langsung ditambahkan selama pemuatan
 * (tampil bertahap bila layer group sudah ada di peta).
 *
 * Antrean & status per layer diatur map-catalog.js; fungsi ini tidak menampilkan overlay
 * maupun toast. Pemanggil bisa membatalkan lewat `signal` (AbortController) dan menerima
 * jumlah fitur yang sudah dimuat lewat `onProgress`.
 *
 * @returns {Promise<{status: "loaded"|"already"|"aborted"|"error", count?: number, error?: string}>}
 */
async function loadCategoryData(categoryName, parentName = null, grandparentName = null, { signal, onProgress } = {}) {
    if (loadedCategories.has(categoryName)) {
        return { status: "already" };
    }

    const targetLayer = findCategoryLayerGroup(categoryName, parentName, grandparentName);
    if (!targetLayer) {
        return { status: "error", error: `Layer ${categoryName} tidak ditemukan` };
    }

    const urlPath = window.location.pathname.replace(/\/$/, "");
    const tipeLayer = getDataType(urlPath);
    const dataType = tipeLayer.type;
    const subType = tipeLayer.sub_type || null;
    const year = tipeLayer.year || null;
    const maxRecords = 3000;
    const chunkSize = 500;

    targetLayer.clearLayers();

    try {
        const cachedChunks = await readAllCachedChunks(
            { type: dataType, sub_type: subType, year: year, category: categoryName },
            maxRecords,
            chunkSize
        );
        throwIfAborted(signal);

        if (cachedChunks) {
            const count = await renderCachedChunks(cachedChunks, targetLayer, categoryName, urlPath, { signal, onProgress });
            loadedCategories.add(categoryName);
            return { status: "loaded", count };
        }

        let offset = 0;
        let totalLoaded = 0;
        let hasMore = true;

        while (hasMore && totalLoaded < maxRecords) {
            throwIfAborted(signal);

            const limit = Math.min(chunkSize, maxRecords - totalLoaded);
            const cacheParams = { type: dataType, sub_type: subType, year, category: categoryName, limit, offset };
            const cacheKey = mapDataStore?.generateCacheKey(cacheParams);

            let geoJsonData = mapDataStore && cacheKey ? await mapDataStore.getCachedData(cacheKey) : null;

            if (!geoJsonData) {
                const params = new URLSearchParams();
                if (dataType) params.set("type", dataType);
                if (subType) params.set("sub_type", subType);
                if (year) params.set("year", year);
                params.append("kategori[]", categoryName);
                params.set("limit", String(limit));
                params.set("offset", String(offset));

                const response = await fetch(`/geojson?${params.toString()}`, { signal });
                if (!response.ok) {
                    throw new Error(`Server merespons ${response.status}`);
                }

                geoJsonData = await response.json();

                if (mapDataStore && cacheKey && Array.isArray(geoJsonData?.features)) {
                    await mapDataStore.setCachedData(cacheKey, geoJsonData, categoryName);
                }
            }

            throwIfAborted(signal);

            if (!geoJsonData?.features?.length) {
                break;
            }

            let featuresAdded = 0;
            geoJsonData.features.forEach((feature) => {
                try {
                    if (addFeatureToLayer(feature, targetLayer, categoryName, urlPath)) {
                        featuresAdded++;
                    }
                } catch (featureError) {
                    // Satu feature rusak tidak boleh menggagalkan seluruh layer.
                }
            });

            totalLoaded += featuresAdded;
            onProgress?.(totalLoaded);

            hasMore = geoJsonData.meta?.has_more === true && featuresAdded > 0;
            offset += chunkSize;
        }

        loadedCategories.add(categoryName);
        return { status: "loaded", count: totalLoaded };
    } catch (error) {
        targetLayer.clearLayers();
        loadedCategories.delete(categoryName);

        if (error?.name === "AbortError") {
            return { status: "aborted" };
        }

        console.error(`Gagal memuat data ${categoryName}:`, error);
        return { status: "error", error: error?.message || "Terjadi kesalahan" };
    }
}


/**
 * Bangun ulang Katalog Peta & Layer Aktif setelah daftar mapset dimuat (map-catalog.js).
 */
function updateLayerList() {
    window.MarimoiCatalog?.rebuild();
}

/**
 * Gambar pratinjau basemap untuk panel Basemap (public/frontend/img/map-preview).
 */
function generatePreviewUrl(basemap) {
    return `/frontend/img/map-preview/${basemap.id}-min.png`;
}

/**
 * Isi panel Basemap dengan kartu pratinjau; klik kartu untuk mengganti basemap.
 */
function setupUI() {
    const basemapList = document.getElementById("basemap-list");
    if (basemapList) {
        basemapList.innerHTML = "";

        const gridContainer = document.createElement("div");
        gridContainer.className = "grid grid-cols-2 gap-3";

        mapConfig.baseMapsList.forEach((bm, i) => {
            const itemContainer = document.createElement("div");
            itemContainer.className = "col-span-1";

            const basemapItem = document.createElement("div");
            basemapItem.className =
                "basemap-item overflow-hidden cursor-pointer position-relative";
            basemapItem.style.cssText = `
            transition: all 0.2s ease;
            cursor: pointer;
        `;

            const previewImg = document.createElement("img");
            previewImg.src = generatePreviewUrl(bm);
            previewImg.alt = bm.label;
            previewImg.className = "w-100 border-2 border-white shadow-lg";
            previewImg.style.cssText = `
            width: 90%;
            height: 70px;
            object-fit: cover;
            transition: all 0.2s ease;
            box-shadow: 6px rgba(0,0,0,1);
        `;

            // Gambar pratinjau gagal dimuat: ganti dengan kotak "Preview tidak tersedia".
            previewImg.onerror = function () {
                this.style.display = "none";
                const placeholder = document.createElement("div");
                placeholder.className =
                    "flex items-center justify-center bg-white";
                placeholder.style.cssText = `
                width: 100%;
                height: 80px;
            `;
                placeholder.innerHTML = `
                <div class="text-center">
                    <i class="bi bi-image text-muted" style="font-size: 1.1rem;"></i>
                    <div class="small text-muted text-xs">Preview tidak tersedia</div>
                </div>
            `;
                this.parentNode.insertBefore(placeholder, this);
            };

            const label = document.createElement("div");
            label.className = "p-2";
            label.style.cssText = `
            font-size: 0.7rem;
            font-weight: 500;
            text-align: center;
            line-height: 1.2;
            transition: color 0.2s ease;
        `;
            label.textContent = bm.label;

            // Radio tersembunyi menyimpan basemap terpilih (kartu pertama = OSM, default).
            const radioInput = document.createElement("input");
            radioInput.type = "radio";
            radioInput.name = "basemap-radio";
            radioInput.id = `bm-${bm.id}`;
            radioInput.value = bm.id;
            radioInput.className = "hidden";
            radioInput.style.cssText = "display:none;";
            if (i === 0) radioInput.checked = true;

            basemapItem.addEventListener("click", function () {
                document
                    .querySelectorAll('input[name="basemap-radio"]')
                    .forEach((input) => {
                        input.checked = false;
                        const item = input.closest(".basemap-item");
                        if (item) {
                            const img = item.querySelector("img");
                            const lbl = item.querySelector("div.p-2");
                            if (img) img.style.boxShadow = "6px rgba(0,0,0,1)";
                            if (lbl) lbl.style.color = "inherit";
                        }
                    });

                radioInput.checked = true;
                previewImg.style.boxShadow = "0 0 10px rgba(0, 123, 255, 0.6)";
                label.style.color = "#0d6efd";

                changeBaseMap(bm.id);
            });

            if (radioInput.checked) {
                previewImg.style.boxShadow = "0 0 10px rgba(0, 123, 255, 0.6)";
                label.style.color = "#0d6efd";
            }

            basemapItem.appendChild(previewImg);
            basemapItem.appendChild(label);
            basemapItem.appendChild(radioInput);

            itemContainer.appendChild(basemapItem);
            gridContainer.appendChild(itemContainer);
        });

        basemapList.appendChild(gridContainer);
    }
}

/**
 * Pulihkan tampilan dari link share (/peta-interaktif/share/{slug}): layer, posisi peta,
 * dan filter. State-nya ditaruh server di window.MARIMOI_SHARED_STATE (peta.blade.php).
 */
async function applySharedMapState() {
    const state = window.MARIMOI_SHARED_STATE;

    if (!state || !Array.isArray(state.layers) || state.layers.length === 0) {
        return;
    }

    const { missing } = await window.MarimoiCatalog.activateByNames(state.layers);
    missing.forEach((categoryName) => {
        showAlert(`Layer "${categoryName}" dari link share tidak ditemukan.`, "warning");
    });

    const viewport = state.viewport;
    if (viewport && typeof viewport.lat === "number" && typeof viewport.lng === "number") {
        map.setView([viewport.lat, viewport.lng], viewport.zoom || mapConfig.zoom);
    }

    if (state.filters) {
        const kabupatenEl = document.getElementById("filter-kabupaten");
        const tahunEl = document.getElementById("filter-tahun");
        const opdEl = document.getElementById("filter-opd");
        if (state.filters.kabupaten && kabupatenEl) kabupatenEl.value = state.filters.kabupaten;
        if (state.filters.tahun && tahunEl) tahunEl.value = String(state.filters.tahun);
        if (state.filters.opd_pengelola && opdEl) opdEl.value = state.filters.opd_pengelola;
        // Catat sebagai filter yang berlaku. Opsi select bisa belum termuat dari server,
        // jadi nilainya dipasang lewat katalog (yang menambahkan opsinya bila perlu).
        window.MarimoiCatalog?.syncAppliedFilters({
            kabupaten: state.filters.kabupaten || "",
            tahun: state.filters.tahun ? String(state.filters.tahun) : "",
            opd_pengelola: state.filters.opd_pengelola || "",
        });
        // Filter dari link share juga mengaktifkan mapset lain yang cocok, bukan hanya
        // menyaring layer yang tercantum di state.layers.
        await applyStructuredFilters();
    }
}

/**
 * Mapset yang dipilih dari halaman lain (tautan "Lihat peta" di beranda), dikirim server
 * lewat session sebagai window.MARIMOI_SELECTED_CATEGORY.
 */
function getSelectedCategoryFromSession() {
    if (typeof window.MARIMOI_SELECTED_CATEGORY !== "undefined") {
        return window.MARIMOI_SELECTED_CATEGORY;
    }
    return null;
}

/**
 * Aktifkan mapset yang dipilih dari halaman lain (lihat getSelectedCategoryFromSession).
 */
async function autoClickCategoryFromSession() {
    const selectedCategory = getSelectedCategoryFromSession();

    if (!selectedCategory || !window.MarimoiCatalog) {
        return;
    }

    if (window.MarimoiCatalog.findEntriesByName(selectedCategory).length === 0) {
        showAlert(`Kategori "${selectedCategory}" tidak ditemukan di daftar layer`, "warning");
        return;
    }

    try {
        showAlert(`Memuat peta ${selectedCategory}...`, "info");
        await window.MarimoiCatalog.activateByNames([selectedCategory]);
    } catch (error) {
        showAlert(`Gagal memuat kategori "${selectedCategory}": ${error.message}`, "danger");
    }
}


// =====================================================================
// Inisialisasi halaman
// =====================================================================

document.addEventListener("DOMContentLoaded", async () => {
    if (window.MapDataStore) {
        mapDataStore = new window.MapDataStore();
    }

    // Cache browser dibuang bila kedaluwarsa (24 jam, di MapDataStore) atau bila versi data
    // di server berubah. Versi dicek lebih dulu sebelum daftar mapset dibaca dari cache.
    if (mapDataStore && window.MARIMOI_MAP_VERSION_URL) {
        await mapDataStore.syncVersion(window.MARIMOI_MAP_VERSION_URL);
    }

    changeBaseMap("osm");
    setupUI();

    // Link share tidak valid / kedaluwarsa (pesan dari server).
    if (window.MARIMOI_SHARE_ERROR) {
        showAlert(window.MARIMOI_SHARE_ERROR, "warning");
    }

    const layerListContainer = document.getElementById("layer-list");
    if (layerListContainer) {
        layerListContainer.innerHTML = `
            <div id="layer-loading" class="flex items-center justify-center h-[120px]">
                <div class="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="ml-2 text-sm">Memuat daftar kategori...</span>
            </div>`;
    }

    try {
        // Opsi filter diambil paralel; tidak bergantung pada daftar mapset.
        loadFilterOptionsFromServer();

        await loadCategoriesMetadata();
        document.getElementById("layer-loading")?.remove();

        // Jeda singkat agar Katalog & Layer Aktif selesai dirender sebelum layer diaktifkan.
        setTimeout(async () => {
            await autoClickCategoryFromSession();
            await applySharedMapState();
        }, 1000);

    } catch (error) {
        console.error("Error during map initialization:", error);
        showAlert("Terjadi kesalahan saat memuat aplikasi peta", "danger");
    }

    // Panel samping kanan dan tombol pembukanya; hanya satu panel terbuka sekaligus.
    // (Tombol Bantuan ditangani map-guide.js.)
    const sidebarElements = {
        layer: document.getElementById("sidebar-layer"),
        basemap: document.getElementById("sidebar-basemap"),
        legend: document.getElementById("sidebar-legend"),
    };

    const toggleButtons = {
        layer: document.getElementById("btn-toggle-sidebar-layer"),
        basemap: document.getElementById("btn-toggle-sidebar-basemap"),
        legend: document.getElementById("btn-toggle-sidebar-legend"),
    };

    function closeAllSidebars() {
        Object.values(sidebarElements).forEach((el) => {
            if (el) el.classList.add("hidden");
        });
    }

    Object.entries(toggleButtons).forEach(([key, btn]) => {
        if (btn && sidebarElements[key]) {
            btn.addEventListener("click", () => {
                const sidebar = sidebarElements[key];
                const isHidden = sidebar.classList.contains("hidden");
                closeAllSidebars();
                sidebar.classList.toggle("hidden", !isHidden);
            });
        }
    });

    ["layer", "basemap", "legend"].forEach((type) => {
        const closeBtn = document.getElementById(`btn-close-sidebar-${type}`);
        if (closeBtn && sidebarElements[type]) {
            closeBtn.addEventListener("click", () => {
                sidebarElements[type].classList.add("hidden");
            });
        }
    });

    // Kontrol Filter Data ada di modal Katalog (map-catalog.js); di sini hanya memastikan
    // filter yang berlaku diterapkan ulang saat panel Layer dibuka.
    toggleButtons.layer?.addEventListener("click", refreshFilterPanel);

    document.getElementById("btn-fullscreen")?.addEventListener("click", () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(console.error);
        } else {
            document.exitFullscreen().catch(console.error);
        }
    });

    document
        .getElementById("btn-default-zoom")
        ?.addEventListener("click", () => {
            map.setView(mapConfig.center, mapConfig.zoom);
        });

    // ---- Share peta: simpan layer + posisi + filter ke link pendek (/peta-interaktif/share/{slug}) ----
    const shareModal = document.getElementById("shareMapModal");
    const shareMapLinkInput = document.getElementById("shareMapLink");
    const shareMapLinkSpinner = document.getElementById("shareMapLinkSpinner");
    const shareMapContent = document.getElementById("shareMapContent");
    const shareMapEmptyState = document.getElementById("shareMapEmptyState");
    const btnShareMap = document.getElementById("btn-share-map");
    const btnCloseShareModal = document.getElementById("btn-close-share-modal");
    const btnCopyShareLink = document.getElementById("btn-copy-share-link");
    const btnCopyShareIcon = document.getElementById("btn-copy-share-icon");
    const btnCopyShareLabel = document.getElementById("btn-copy-share-label");
    const shareQuickButtons = document.querySelectorAll(".share-quick-btn");
    let copyFeedbackTimeout = null;

    // Hanya layer yang sedang tampil (layer tersembunyi tidak ikut dibagikan).
    function getCheckedLayerNames() {
        return (window.MarimoiCatalog?.getActiveEntries() || [])
            .filter((entry) => map.hasLayer(entry.layerGroup))
            .map((entry) => entry.leafName);
    }

    function openShareModal() {
        shareModal?.classList.remove("hidden");
        shareModal?.classList.add("flex");
    }

    function closeShareModal() {
        shareModal?.classList.add("hidden");
        shareModal?.classList.remove("flex");
    }

    function setShareLinkLoading(isLoading) {
        shareMapLinkSpinner?.classList.toggle("hidden", !isLoading);

        if (btnCopyShareLink) {
            btnCopyShareLink.disabled = isLoading;
        }

        shareQuickButtons.forEach((btn) => {
            btn.classList.toggle("pointer-events-none", isLoading);
            btn.classList.toggle("opacity-40", isLoading);
        });
    }

    btnShareMap?.addEventListener("click", async () => {
        const layers = getCheckedLayerNames();

        if (layers.length === 0) {
            shareMapEmptyState?.classList.remove("hidden");
            shareMapContent?.classList.add("hidden");
            openShareModal();
            return;
        }

        shareMapEmptyState?.classList.add("hidden");
        shareMapContent?.classList.remove("hidden");

        if (shareMapLinkInput) {
            shareMapLinkInput.value = "";
            shareMapLinkInput.placeholder = "Membuat link...";
        }

        setShareLinkLoading(true);
        openShareModal();

        const center = map.getCenter();
        const zoom = map.getZoom();
        const urlPath = window.location.pathname.replace(/\/$/, "");
        const tipeLayer = getDataType(urlPath);

        try {
            const response = await fetch(window.MARIMOI_SHARE_STORE_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": window.MARIMOI_CSRF_TOKEN,
                },
                body: JSON.stringify({
                    layers,
                    viewport: { lat: center.lat, lng: center.lng, zoom },
                    data_type: tipeLayer.type,
                    sub_type: tipeLayer.sub_type,
                    year: tipeLayer.year,
                    filters: getActiveFilterValues(),
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            const shareUrl = result.url;

            if (shareMapLinkInput) shareMapLinkInput.value = shareUrl;

            const encodedUrl = encodeURIComponent(shareUrl);
            const shareText = encodeURIComponent(`Lihat peta interaktif ini: ${shareUrl}`);

            const waLink = document.getElementById("share-whatsapp");
            const tgLink = document.getElementById("share-telegram");
            const mailLink = document.getElementById("share-email");

            if (waLink) waLink.href = `https://wa.me/?text=${shareText}`;
            if (tgLink) tgLink.href = `https://t.me/share/url?url=${encodedUrl}`;
            if (mailLink) {
                mailLink.href = `mailto:?subject=${encodeURIComponent("Peta Interaktif MARIMOI")}&body=${shareText}`;
            }

            setShareLinkLoading(false);
        } catch (error) {
            console.error("Gagal membuat link share:", error);
            if (shareMapLinkInput) shareMapLinkInput.placeholder = "Gagal membuat link";
            shareMapLinkSpinner?.classList.add("hidden");
            showAlert("Gagal membuat link share. Silakan coba lagi.", "danger");
        }
    });

    btnCloseShareModal?.addEventListener("click", closeShareModal);

    function showCopyFeedback() {
        if (!btnCopyShareLink || !btnCopyShareIcon || !btnCopyShareLabel) return;

        clearTimeout(copyFeedbackTimeout);

        btnCopyShareIcon.className = "bi bi-check-lg";
        btnCopyShareLabel.textContent = "Tersalin!";
        btnCopyShareLink.classList.remove("bg-blue-500", "hover:bg-blue-600");
        btnCopyShareLink.classList.add("bg-green-500", "hover:bg-green-600");

        if (shareMapLinkInput) {
            shareMapLinkInput.style.borderColor = "#4ade80";
            shareMapLinkInput.style.backgroundColor = "#f0fdf4";
        }

        copyFeedbackTimeout = setTimeout(() => {
            btnCopyShareIcon.className = "bi bi-clipboard";
            btnCopyShareLabel.textContent = "Copy Link";
            btnCopyShareLink.classList.remove("bg-green-500", "hover:bg-green-600");
            btnCopyShareLink.classList.add("bg-blue-500", "hover:bg-blue-600");

            if (shareMapLinkInput) {
                shareMapLinkInput.style.borderColor = "";
                shareMapLinkInput.style.backgroundColor = "";
            }
        }, 2000);
    }

    btnCopyShareLink?.addEventListener("click", async () => {
        if (!shareMapLinkInput?.value) return;

        try {
            await navigator.clipboard.writeText(shareMapLinkInput.value);
        } catch (error) {
            // Clipboard API ditolak (mis. bukan HTTPS): pakai cara lama lewat seleksi teks.
            shareMapLinkInput.select();
            document.execCommand("copy");
        }

        showCopyFeedback();
        showAlert("Link berhasil disalin.", "success");
    });
});

