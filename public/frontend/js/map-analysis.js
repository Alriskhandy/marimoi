/**
 * Analisis Peta: ringkasan dan statistik dari layer aktif yang sudah dimuat di peta.
 *
 * Semua dihitung di browser dari fitur yang sedang tampil (filter katalog ikut berlaku):
 * luas & panjang geodesik, perbandingan layer, tren per tahun, sebaran wilayah administrasi,
 * OPD pengelola, sebaran spasial, atribut numerik, rincian atribut, dan wawasan singkat.
 * Cakupan bisa "Tampilan peta" (fitur yang terlihat di layar) atau "Seluruh data" layer aktif.
 * Tombol Analisis hanya aktif bila minimal satu layer aktif.
 */
(function () {
    const EARTH_RADIUS = 6378137;
    // Kunci properti sistem/struktural: tidak dianalisis sebagai atribut bebas.
    const SYSTEM_KEYS = new Set(["id", "uuid", "data_type", "sub_type", "kategori_id", "kategori", "icon", "warna", "is_marker", "gambar", "gambar_list", "style_override", "deskripsi", "tahun", "sumber_data", "opd_pengelola", "tanggal_data", "label"]);
    // Kolom identitas hasil impor (FID, OBJECTID, dsb.) tidak bermakna sebagai statistik.
    const ID_LIKE = /^(ogr_)?(fid|gid|oid|objectid|object_id|id|no|nomor|urut|kode|shape_(leng|area|length)|shape_le(ng)?|shape_ar(ea)?)$|(_id|_fid)$/i;
    // Atribut wilayah administrasi (dari terbesar ke terkecil), dicoba berurutan per tingkat.
    const REGION_LEVELS = [
        { label: "Kabupaten/Kota", keys: ["KABUPATEN", "Kabupaten", "kabupaten", "WADMKK", "KAB_KOTA", "kab_kota", "KABKOT", "NAMA_KAB"], normalize: normalizeRegency },
        { label: "Kecamatan", keys: ["KECAMATAN", "Kecamatan", "kecamatan", "WADMKC", "NAMA_KEC"], normalize: titleCase },
        { label: "Desa/Kelurahan", keys: ["DESA", "Desa", "desa", "DESA_KELUR", "KELURAHAN", "kelurahan", "WADMKD", "NAMA_DESA"], normalize: titleCase },
    ];
    const REGION_KEYS = new Set(REGION_LEVELS.flatMap((level) => level.keys));
    const QUADRANTS = { NW: "Barat Laut", NE: "Timur Laut", SW: "Barat Daya", SE: "Tenggara" };
    const PALETTE = ["#0a84ff", "#20d9ff", "#4de1c1", "#f59e0b", "#a855f7", "#ef4444", "#10b981", "#6366f1"];

    // Template dokumen aktif (dashboard › Template Dokumen) untuk cetak analisis.
    const TEMPLATES = (window.MARIMOI_DOCUMENT_TEMPLATES || []).filter((template) => template.forAnalysis);

    const el = {};
    let scope = "view";
    let refreshTimer = null;

    // ---------------------------------------------------------------------------------------
    // Utilitas format & teks
    // ---------------------------------------------------------------------------------------

    const numberFormat = (value, digits = 0) => Number(value).toLocaleString("id-ID", { minimumFractionDigits: digits, maximumFractionDigits: digits });
    const smartNumber = (value) => numberFormat(value, Math.abs(value) >= 100 || Number.isInteger(value) ? 0 : 2);
    const percent = (part, whole) => (whole > 0 ? `${numberFormat((part / whole) * 100, 1)}%` : "0%");

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = String(value ?? "");
        return div.innerHTML;
    }

    function titleCase(value) {
        return String(value).trim().toLowerCase().replace(/\s+/g, " ").replace(/(^|[\s(/-])(\p{L})/gu, (match, sep, char) => sep + char.toUpperCase());
    }

    // "KAB. HALMAHERA BARAT", "Kabupaten Halmahera Barat", "Halmahera Barat" → satu nama yang sama.
    function normalizeRegency(value) {
        return titleCase(value).replace(/^(Kabupaten|Kab\.?)\s+/i, "");
    }

    // Nama kolom standar data spasial (Badan Informasi Geospasial) yang tidak terbaca dari namanya saja.
    const FIELD_LABELS = { NAMOBJ: "Nama objek", REMARK: "Keterangan", FCODE: "Kode unsur", SRS_ID: "Sistem referensi", LCODE: "Kode lokasi", METADATA: "Metadata" };

    function prettyField(key) {
        if (FIELD_LABELS[String(key).toUpperCase()]) {
            return FIELD_LABELS[String(key).toUpperCase()];
        }
        const text = String(key).replace(/[_-]+/g, " ").trim();
        return text === text.toUpperCase() ? titleCase(text) : text.charAt(0).toUpperCase() + text.slice(1);
    }

    function formatArea(squareMeters) {
        if (squareMeters <= 0) {
            return "–";
        }
        if (squareMeters < 10000) {
            return `${numberFormat(squareMeters)} m²`;
        }
        const hectares = squareMeters / 10000;
        return `${numberFormat(hectares, hectares < 100 ? 2 : 0)} ha`;
    }

    function formatLength(meters) {
        if (meters <= 0) {
            return "–";
        }
        return meters < 1000 ? `${numberFormat(meters)} m` : `${numberFormat(meters / 1000, meters < 100000 ? 2 : 0)} km`;
    }

    // ---------------------------------------------------------------------------------------
    // Geometri: luas & panjang geodesik dari koordinat GeoJSON (lng, lat)
    // ---------------------------------------------------------------------------------------

    const rad = (degree) => (degree * Math.PI) / 180;

    // Luas cincin pada bola (algoritma yang sama dengan @mapbox/geojson-area).
    function ringArea(coords) {
        const count = coords.length;
        if (count < 3) {
            return 0;
        }
        let total = 0;
        for (let i = 0; i < count; i++) {
            const lower = coords[i];
            const middle = coords[(i + 1) % count];
            const upper = coords[(i + 2) % count];
            total += (rad(upper[0]) - rad(lower[0])) * Math.sin(rad(middle[1]));
        }
        return Math.abs((total * EARTH_RADIUS * EARTH_RADIUS) / 2);
    }

    function polygonArea(rings) {
        if (!rings.length) {
            return 0;
        }
        const holes = rings.slice(1).reduce((sum, ring) => sum + ringArea(ring), 0);
        return Math.max(0, ringArea(rings[0]) - holes);
    }

    function lineLength(coords) {
        let total = 0;
        for (let i = 1; i < coords.length; i++) {
            total += L.latLng(coords[i - 1][1], coords[i - 1][0]).distanceTo(L.latLng(coords[i][1], coords[i][0]));
        }
        return total;
    }

    /**
     * @returns {{kind: "point"|"line"|"polygon"|null, area: number, length: number}}
     */
    function measureGeometry(geometry) {
        const result = { kind: null, area: 0, length: 0 };
        if (!geometry) {
            return result;
        }
        switch (geometry.type) {
            case "Point":
            case "MultiPoint":
                result.kind = "point";
                break;
            case "LineString":
                result.kind = "line";
                result.length = lineLength(geometry.coordinates);
                break;
            case "MultiLineString":
                result.kind = "line";
                result.length = geometry.coordinates.reduce((sum, line) => sum + lineLength(line), 0);
                break;
            case "Polygon":
                result.kind = "polygon";
                result.area = polygonArea(geometry.coordinates);
                break;
            case "MultiPolygon":
                result.kind = "polygon";
                result.area = geometry.coordinates.reduce((sum, polygon) => sum + polygonArea(polygon), 0);
                break;
            case "GeometryCollection":
                (geometry.geometries || []).forEach((part) => {
                    const measured = measureGeometry(part);
                    result.kind = result.kind || measured.kind;
                    result.area += measured.area;
                    result.length += measured.length;
                });
                break;
        }
        return result;
    }

    // ---------------------------------------------------------------------------------------
    // Pengumpulan fitur dari layer aktif
    // ---------------------------------------------------------------------------------------

    function collectLeaves(layer, result) {
        if (layer.feature) {
            result.push(layer);
        } else if (typeof layer.getLayers === "function") {
            layer.getLayers().forEach((child) => collectLeaves(child, result));
        }
        return result;
    }

    function leafCenter(leaf) {
        try {
            if (typeof leaf.getLatLng === "function") {
                return leaf.getLatLng();
            }
            if (typeof leaf.getBounds === "function") {
                return leaf.getBounds().getCenter();
            }
        } catch (error) {
            // Geometri kosong: Leaflet melempar error pada getBounds/getCenter.
        }
        return null;
    }

    function leafInView(leaf, viewBounds) {
        try {
            if (typeof leaf.getLatLng === "function") {
                return viewBounds.contains(leaf.getLatLng());
            }
            if (typeof leaf.getBounds === "function") {
                return viewBounds.intersects(leaf.getBounds());
            }
        } catch (error) {
            return false;
        }
        return false;
    }

    function readyEntries() {
        const catalog = window.MarimoiCatalog;
        if (!catalog) {
            return { ready: [], pending: [] };
        }
        const shown = catalog.getActiveEntries().filter((entry) => !catalog.isHidden(entry));
        return {
            ready: shown.filter((entry) => !catalog.isLoading(entry) && map.hasLayer(entry.layerGroup)),
            pending: shown.filter((entry) => catalog.isLoading(entry)),
        };
    }

    /**
     * Kumpulkan fitur per layer (sesuai cakupan) beserta ukurannya.
     */
    function gatherFeatures(entries) {
        const viewBounds = map.getBounds();
        return entries.map((entry) => {
            const features = [];
            collectLeaves(entry.layerGroup, []).forEach((leaf) => {
                if (leaf.marimoiFilteredOut || (scope === "view" && !leafInView(leaf, viewBounds))) {
                    return;
                }
                const measured = measureGeometry(leaf.feature.geometry);
                features.push({ properties: leaf.feature.properties || {}, center: leafCenter(leaf), ...measured });
            });
            return { entry, features };
        });
    }

    // ---------------------------------------------------------------------------------------
    // Perhitungan statistik
    // ---------------------------------------------------------------------------------------

    function countBy(values) {
        const counts = new Map();
        values.forEach((value) => counts.set(value, (counts.get(value) || 0) + 1));
        return [...counts.entries()].sort((a, b) => b[1] - a[1] || String(a[0]).localeCompare(String(b[0]), "id"));
    }

    function firstValue(properties, keys) {
        for (const key of keys) {
            const value = properties[key];
            if (value !== null && value !== undefined && String(value).trim() !== "") {
                return value;
            }
        }
        return null;
    }

    /**
     * Baca angka format Indonesia dari atribut impor: "5", "33,4", "1.250", "Rp. 2.267.678.070,00".
     * @returns {{value: number, currency: boolean}|null} null bila bukan angka
     */
    function parseNumber(raw) {
        if (typeof raw === "number") {
            return Number.isFinite(raw) ? { value: raw, currency: false } : null;
        }
        if (typeof raw !== "string") {
            return null;
        }
        const currency = /^\s*rp\.?/i.test(raw);
        let text = raw.trim().replace(/^rp\.?\s*/i, "").replace(/\s+/g, "").replace(/,-$/, "");
        if (!/^-?[\d.,]+$/.test(text) || !/\d/.test(text)) {
            return null;
        }
        if (text.includes(".") && text.includes(",")) {
            text = text.replace(/\./g, "").replace(",", ".");
        } else if (text.includes(",")) {
            text = text.replace(",", ".");
        } else if (/^-?\d{1,3}(\.\d{3})+$/.test(text)) {
            // Titik sebagai pemisah ribuan (1.250 / 2.000.000).
            text = text.replace(/\./g, "");
        }
        const value = Number(text);
        return Number.isFinite(value) ? { value, currency } : null;
    }

    // Rupiah ringkas: Rp 2,27 miliar.
    function formatRupiah(value) {
        const units = [[1e12, "triliun"], [1e9, "miliar"], [1e6, "juta"]];
        const unit = units.find(([size]) => Math.abs(value) >= size);
        return unit ? `Rp ${numberFormat(value / unit[0], 2)} ${unit[1]}` : `Rp ${numberFormat(value)}`;
    }
    const looksLikeFile = (value) => /\.(jpe?g|png|gif|webp|pdf|docx?|xlsx?|zip)$/i.test(value) || /^https?:\/\//i.test(value);
    const looksLikeDateTime = (value) => /^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2})?/.test(value) || /^\d{2}[-/]\d{2}[-/]\d{4}/.test(value);

    function analyse(layers) {
        const all = layers.flatMap((layer) => layer.features.map((feature) => ({ ...feature, layer: layer.entry })));
        const total = all.length;

        const perLayer = layers.map(({ entry, features }) => {
            const kinds = countBy(features.map((feature) => feature.kind).filter(Boolean));
            return {
                entry,
                count: features.length,
                area: features.reduce((sum, feature) => sum + feature.area, 0),
                length: features.reduce((sum, feature) => sum + feature.length, 0),
                kind: kinds[0]?.[0] || null,
            };
        });

        const years = countBy(all.map((feature) => Number(feature.properties.tahun)).filter((year) => year >= 1900 && year <= 2100)).sort((a, b) => a[0] - b[0]);

        const regions = REGION_LEVELS.map((level) => {
            const values = all.map((feature) => firstValue(feature.properties, level.keys)).filter((value) => value !== null).map((value) => level.normalize(value));
            return { label: level.label, coverage: values.length, values: countBy(values) };
        }).filter((level) => level.coverage > 0);

        const opd = countBy(all.map((feature) => feature.properties.opd_pengelola).filter(Boolean).map((value) => String(value).trim()));
        const sources = countBy(all.map((feature) => feature.properties.sumber_data).filter(Boolean).map((value) => String(value).trim()));

        // Sebaran spasial: titik pusat, rentang koordinat, dan kuadran terhadap titik pusat.
        const centers = all.map((feature) => feature.center).filter(Boolean);
        let spatial = null;
        if (centers.length) {
            const lats = centers.map((point) => point.lat);
            const lngs = centers.map((point) => point.lng);
            const center = { lat: lats.reduce((a, b) => a + b, 0) / lats.length, lng: lngs.reduce((a, b) => a + b, 0) / lngs.length };
            const quadrantCounts = { NW: 0, NE: 0, SW: 0, SE: 0 };
            centers.forEach((point) => { quadrantCounts[`${point.lat >= center.lat ? "N" : "S"}${point.lng >= center.lng ? "E" : "W"}`]++; });
            spatial = {
                center,
                lat: [Math.min(...lats), Math.max(...lats)],
                lng: [Math.min(...lngs), Math.max(...lngs)],
                quadrants: Object.entries(quadrantCounts).map(([key, count]) => [QUADRANTS[key], count]).sort((a, b) => b[1] - a[1]),
            };
        }

        // Atribut bebas hasil impor: numerik → statistik, teks berulang → rincian nilai.
        const fieldValues = new Map();
        all.forEach((feature) => {
            Object.entries(feature.properties).forEach(([key, value]) => {
                if (SYSTEM_KEYS.has(key) || REGION_KEYS.has(key) || ID_LIKE.test(key) || value === null || value === undefined || typeof value === "object") {
                    return;
                }
                const text = String(value).trim();
                if (text === "") {
                    return;
                }
                if (!fieldValues.has(key)) {
                    fieldValues.set(key, []);
                }
                fieldValues.get(key).push(value);
            });
        });

        const numeric = [];
        const categorical = [];
        fieldValues.forEach((values, key) => {
            const parsed = values.map(parseNumber);
            if (parsed.every(Boolean)) {
                const numbers = parsed.map((item) => item.value);
                const sum = numbers.reduce((a, b) => a + b, 0);
                const min = Math.min(...numbers);
                const max = Math.max(...numbers);
                // Kolom yang isinya nol semua tidak informatif.
                if (min === 0 && max === 0) {
                    return;
                }
                numeric.push({ key, count: numbers.length, min, max, avg: sum / numbers.length, sum, currency: parsed.some((item) => item.currency) });
                return;
            }
            const texts = values.map((value) => String(value).trim());
            const distinct = new Set(texts);
            const averageLength = texts.reduce((a, b) => a + b.length, 0) / texts.length;
            const allUnique = distinct.size === texts.length && texts.length > 5;
            if (distinct.size < 2 || distinct.size > 40 || averageLength > 60 || allUnique || texts.some(looksLikeFile) || texts.every(looksLikeDateTime)) {
                return;
            }
            categorical.push({ key, count: texts.length, distinct: distinct.size, values: countBy(texts) });
        });
        numeric.sort((a, b) => Number(b.currency) - Number(a.currency) || b.count - a.count);

        // Anggaran/nilai kegiatan: atribut Rupiah pertama per fitur (agar tidak terhitung ganda).
        const currencyKeys = new Set(numeric.filter((field) => field.currency).map((field) => field.key));
        const regencyLevel = REGION_LEVELS[0];
        const budgetByRegency = new Map();
        const budgetByLayer = new Map();
        let budget = 0;
        let budgetCount = 0;
        all.forEach((feature) => {
            const key = Object.keys(feature.properties).find((name) => currencyKeys.has(name));
            const amount = key ? parseNumber(feature.properties[key])?.value : null;
            if (!amount) {
                return;
            }
            budget += amount;
            budgetCount++;
            budgetByLayer.set(feature.layer.leafName, (budgetByLayer.get(feature.layer.leafName) || 0) + amount);
            const regency = firstValue(feature.properties, regencyLevel.keys);
            if (regency !== null) {
                const name = regencyLevel.normalize(regency);
                budgetByRegency.set(name, (budgetByRegency.get(name) || 0) + amount);
            }
        });
        // Atribut yang paling sering terisi dan paling sedikit ragam nilainya didahulukan.
        categorical.sort((a, b) => b.count - a.count || a.distinct - b.distinct);

        return {
            total,
            perLayer,
            area: perLayer.reduce((sum, layer) => sum + layer.area, 0),
            length: perLayer.reduce((sum, layer) => sum + layer.length, 0),
            points: all.filter((feature) => feature.kind === "point").length,
            years,
            regions,
            opd,
            sources,
            spatial,
            numeric: numeric.slice(0, 8),
            budget,
            budgetCount,
            budgetByLayer: [...budgetByLayer.entries()].sort((a, b) => b[1] - a[1]),
            budgetByRegency: [...budgetByRegency.entries()].sort((a, b) => b[1] - a[1]),
            categorical: categorical.slice(0, 6),
        };
    }

    function insights(result) {
        const list = [];
        const layers = [...result.perLayer].filter((layer) => layer.count > 0).sort((a, b) => b.count - a.count);
        if (layers.length > 1) {
            list.push(`<b>${escapeHtml(layers[0].entry.leafName)}</b> menyumbang ${percent(layers[0].count, result.total)} dari seluruh fitur yang dianalisis.`);
        } else if (layers.length === 1) {
            list.push(`Seluruh ${numberFormat(result.total)} fitur berasal dari layer <b>${escapeHtml(layers[0].entry.leafName)}</b>.`);
        }
        const regency = result.regions.find((level) => level.label === "Kabupaten/Kota");
        if (regency && regency.values.length) {
            const [name, count] = regency.values[0];
            const isDominant = regency.values.length === 1 || count > regency.values[1][1];
            if (isDominant) {
                list.push(`Fitur paling banyak berada di <b>${escapeHtml(name)}</b> (${numberFormat(count)} fitur, ${percent(count, regency.coverage)} dari data berwilayah).`);
            }
            if (regency.values.length > 1) {
                list.push(`Data tersebar di <b>${numberFormat(regency.values.length)} kabupaten/kota</b>${isDominant ? "" : " dengan jumlah fitur terbanyak yang sama"}.`);
            }
        }
        if (result.years.length) {
            const peak = [...result.years].sort((a, b) => b[1] - a[1])[0];
            const span = result.years.length > 1 ? `rentang ${result.years[0][0]}–${result.years[result.years.length - 1][0]}` : `tahun ${result.years[0][0]}`;
            list.push(`Data mencakup ${span}; terbanyak pada tahun <b>${peak[0]}</b> (${numberFormat(peak[1])} fitur).`);
        }
        const largest = [...result.perLayer].filter((layer) => layer.area > 0).sort((a, b) => b.area - a.area)[0];
        if (largest) {
            list.push(`Cakupan area terluas dari <b>${escapeHtml(largest.entry.leafName)}</b>, sekitar ${formatArea(largest.area)}.`);
        }
        if (result.length > 0) {
            list.push(`Total panjang jaringan/garis yang terpetakan sekitar <b>${formatLength(result.length)}</b>.`);
        }
        if (result.opd.length) {
            list.push(`OPD pengelola terbanyak: <b>${escapeHtml(result.opd[0][0])}</b> (${numberFormat(result.opd[0][1])} fitur).`);
        }
        if (result.budget > 0) {
            const top = result.budgetByLayer[0];
            list.push(`Total anggaran/nilai kegiatan sekitar <b>${formatRupiah(result.budget)}</b> dari ${numberFormat(result.budgetCount)} fitur${result.budgetByLayer.length > 1 ? `; terbesar pada <b>${escapeHtml(top[0])}</b> (${percent(top[1], result.budget)})` : ""}.`);
        }
        if (result.budgetByRegency.length > 1) {
            const [name, amount] = result.budgetByRegency[0];
            list.push(`Alokasi anggaran terbesar berada di <b>${escapeHtml(name)}</b>, ${formatRupiah(amount)} (${percent(amount, result.budgetByRegency.reduce((a, b) => a + b[1], 0))}).`);
        }
        // Atribut numerik pertama yang nilainya bervariasi (bukan Rupiah, sudah dibahas di atas).
        const numeric = result.numeric.find((field) => !field.currency && field.min !== field.max);
        if (numeric) {
            list.push(`${escapeHtml(prettyField(numeric.key))} berkisar ${smartNumber(numeric.min)}–${smartNumber(numeric.max)} dengan rata-rata ${smartNumber(numeric.avg)}.`);
        }
        return list;
    }

    // ---------------------------------------------------------------------------------------
    // Tampilan
    // ---------------------------------------------------------------------------------------

    function card(title, body, { wide = false, note = "" } = {}) {
        return `<section class="analysis-card${wide ? " is-wide" : ""}">
            <header><h3>${title}</h3>${note ? `<span>${note}</span>` : ""}</header>
            ${body}
        </section>`;
    }

    function bars(rows, { max = null, color = null, limit = 8, total = null } = {}) {
        const visible = rows.slice(0, limit);
        const top = max ?? Math.max(1, ...visible.map((row) => row[1]));
        const items = visible.map(([label, value, rowColor], index) => `
            <li>
                <span class="analysis-bar-label" title="${escapeHtml(label)}">${escapeHtml(label)}</span>
                <span class="analysis-bar-track"><span style="width:${Math.max(2, (value / top) * 100)}%;background:${rowColor || color || PALETTE[index % PALETTE.length]}"></span></span>
                <span class="analysis-bar-value">${numberFormat(value)}${total ? `<small>${percent(value, total)}</small>` : ""}</span>
            </li>`).join("");
        const more = rows.length > limit ? `<p class="analysis-more">+${numberFormat(rows.length - limit)} nilai lainnya</p>` : "";
        return `<ul class="analysis-bars">${items}</ul>${more}`;
    }

    // Grafik kolom per tahun (SVG sederhana, tanpa pustaka grafik).
    function yearChart(years) {
        const width = 640;
        const height = 200;
        const pad = { top: 18, right: 8, bottom: 28, left: 8 };
        const max = Math.max(...years.map(([, count]) => count));
        const slot = (width - pad.left - pad.right) / years.length;
        const barWidth = Math.min(48, slot * 0.6);
        const columns = years.map(([year, count], index) => {
            const h = ((height - pad.top - pad.bottom) * count) / max;
            const x = pad.left + slot * index + (slot - barWidth) / 2;
            const y = height - pad.bottom - h;
            return `<g>
                <rect x="${x}" y="${y}" width="${barWidth}" height="${h}" rx="4" fill="url(#analysisYearGradient)"><title>${year}: ${numberFormat(count)} fitur</title></rect>
                <text x="${x + barWidth / 2}" y="${y - 5}" class="analysis-chart-value">${numberFormat(count)}</text>
                <text x="${x + barWidth / 2}" y="${height - 9}" class="analysis-chart-axis">${year}</text>
            </g>`;
        }).join("");
        return `<svg viewBox="0 0 ${width} ${height}" class="analysis-year-chart" role="img" aria-label="Jumlah fitur per tahun">
            <defs><linearGradient id="analysisYearGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#20d9ff"/><stop offset="1" stop-color="#0a84ff"/></linearGradient></defs>
            <line x1="${pad.left}" x2="${width - pad.right}" y1="${height - pad.bottom}" y2="${height - pad.bottom}" class="analysis-chart-base"/>
            ${columns}
        </svg>`;
    }

    // Donat komposisi geometri (conic-gradient).
    function geometryDonut(result) {
        const counts = { point: 0, line: 0, polygon: 0 };
        result.perLayer.forEach((layer) => {
            if (layer.kind) {
                counts[layer.kind] += layer.count;
            }
        });
        const parts = [["Titik", counts.point, "#0a84ff"], ["Garis", counts.line, "#f59e0b"], ["Area", counts.polygon, "#4de1c1"]].filter((part) => part[1] > 0);
        let start = 0;
        const stops = parts.map(([, value, color]) => {
            const end = start + (value / result.total) * 360;
            const stop = `${color} ${start}deg ${end}deg`;
            start = end;
            return stop;
        }).join(", ");
        const legend = parts.map(([label, value, color]) => `<li><i style="background:${color}"></i>${label}<b>${numberFormat(value)}</b><small>${percent(value, result.total)}</small></li>`).join("");
        return `<div class="analysis-donut-wrap">
            <div class="analysis-donut" style="background:conic-gradient(${stops})"><span>${numberFormat(result.total)}<small>fitur</small></span></div>
            <ul class="analysis-legend">${legend}</ul>
        </div>`;
    }

    function layerTable(result) {
        const kindLabel = { point: "Titik", line: "Garis", polygon: "Area" };
        const max = Math.max(1, ...result.perLayer.map((layer) => layer.count));
        const rows = [...result.perLayer].sort((a, b) => b.count - a.count).map((layer) => {
            const measure = layer.kind === "polygon" ? formatArea(layer.area) : layer.kind === "line" ? formatLength(layer.length) : "–";
            const group = [layer.entry.rootName, layer.entry.secondName].filter((name, index, arr) => name && name !== layer.entry.leafName && arr.indexOf(name) === index).join(" › ");
            return `<tr>
                <td><span class="analysis-layer-name"><i style="background:${escapeHtml(layer.entry.color)}"></i><span><b>${escapeHtml(layer.entry.leafName)}</b><small>${escapeHtml(group)}${group ? " · " : ""}${kindLabel[layer.kind] || "Tanpa geometri"}</small></span></span></td>
                <td class="analysis-num"><span class="analysis-inline-bar"><span style="width:${(layer.count / max) * 100}%;background:${escapeHtml(layer.entry.color)}"></span></span>${numberFormat(layer.count)}</td>
                <td class="analysis-num">${percent(layer.count, result.total)}</td>
                <td class="analysis-num">${measure}</td>
            </tr>`;
        }).join("");
        return `<div class="analysis-table-wrap"><table class="analysis-table">
            <thead><tr><th>Layer</th><th class="analysis-num">Fitur</th><th class="analysis-num">Porsi</th><th class="analysis-num">Luas / Panjang</th></tr></thead>
            <tbody>${rows}</tbody>
        </table></div>`;
    }

    function numericTable(numeric) {
        const rows = numeric.map((field) => {
            const show = field.currency ? formatRupiah : smartNumber;
            return `<tr>
                <td>${escapeHtml(prettyField(field.key))}${field.currency ? ' <small class="analysis-tag">Rupiah</small>' : ""}</td>
                <td class="analysis-num">${numberFormat(field.count)}</td>
                <td class="analysis-num">${show(field.min)}</td>
                <td class="analysis-num">${show(field.avg)}</td>
                <td class="analysis-num">${show(field.max)}</td>
                <td class="analysis-num">${show(field.sum)}</td>
            </tr>`;
        }).join("");
        return `<div class="analysis-table-wrap"><table class="analysis-table">
            <thead><tr><th>Atribut</th><th class="analysis-num">Jumlah</th><th class="analysis-num">Min</th><th class="analysis-num">Rata-rata</th><th class="analysis-num">Maks</th><th class="analysis-num">Total</th></tr></thead>
            <tbody>${rows}</tbody>
        </table></div>`;
    }

    function render() {
        if (!isOpen()) {
            return;
        }
        const { ready, pending } = readyEntries();
        const scopeLabel = scope === "view" ? "Fitur yang terlihat pada tampilan peta saat ini" : "Seluruh fitur pada layer aktif";
        el.subtitle.textContent = scopeLabel;

        const notices = [];
        if (pending.length) {
            notices.push(`<p class="analysis-notice"><i class="bi bi-hourglass-split"></i> ${numberFormat(pending.length)} layer masih dimuat dan belum ikut dihitung: ${escapeHtml(pending.map((entry) => entry.leafName).join(", "))}.</p>`);
        }

        if (!ready.length) {
            el.body.innerHTML = `${notices.join("")}<div class="analysis-empty"><i class="bi bi-layers"></i><p>Belum ada layer yang siap dianalisis.</p><span>Aktifkan minimal satu layer dari Katalog Peta.</span></div>`;
            return;
        }

        const result = analyse(gatherFeatures(ready));
        if (!result.total) {
            el.body.innerHTML = `${notices.join("")}<div class="analysis-empty"><i class="bi bi-binoculars"></i><p>Tidak ada fitur pada tampilan peta ini.</p><span>Geser atau perkecil peta, atau pilih cakupan "Seluruh data".</span></div>`;
            return;
        }

        if (scope === "view") {
            notices.push(`<p class="analysis-notice is-soft"><i class="bi bi-info-circle"></i> Luas dan panjang dihitung utuh untuk setiap fitur yang terlihat, termasuk bagian di luar layar.</p>`);
        }

        const kpis = [
            ["bi-geo-alt-fill", numberFormat(result.total), "Fitur dianalisis"],
            ["bi-layers-fill", numberFormat(result.perLayer.filter((layer) => layer.count > 0).length), "Layer berisi data"],
            ["bi-bounding-box", formatArea(result.area), "Total luas area"],
            ["bi-signpost-split-fill", formatLength(result.length), "Total panjang garis"],
            ...(result.budget > 0 ? [["bi-cash-stack", formatRupiah(result.budget), "Total anggaran/nilai"]] : []),
        ].map(([icon, value, label]) => `<div class="analysis-kpi"><i class="bi ${icon}"></i><b>${value}</b><span>${label}</span></div>`).join("");

        const insightItems = insights(result).map((text) => `<li><i class="bi bi-lightbulb-fill"></i><span>${text}</span></li>`).join("");

        // Kartu dikelompokkan per bagian; kartu setengah lebar terakhir yang tak berpasangan
        // dibuat selebar penuh agar tidak menyisakan ruang kosong.
        const groups = [];
        const group = (title, icon, cards) => {
            const list = cards.filter(Boolean);
            if (!list.length) {
                return;
            }
            const halves = list.filter((item) => !item.wide);
            if (halves.length % 2 === 1) {
                halves[halves.length - 1].wide = true;
            }
            groups.push(`<section class="analysis-group">
                <h3 class="analysis-group-title"><i class="bi ${icon}"></i> ${title}</h3>
                <div class="analysis-grid">${list.map((item) => card(item.title, item.body, { wide: item.wide, note: item.note })).join("")}</div>
            </section>`);
        };

        const budgetCard = (() => {
            if (!result.budgetByRegency.length) {
                return null;
            }
            const rows = result.budgetByRegency.slice(0, 8);
            const top = Math.max(...rows.map((row) => row[1]));
            const items = rows.map(([name, amount], index) => `<li>
                <span class="analysis-bar-label" title="${escapeHtml(name)}">${escapeHtml(name)}</span>
                <span class="analysis-bar-track"><span style="width:${Math.max(2, (amount / top) * 100)}%;background:${PALETTE[index % PALETTE.length]}"></span></span>
                <span class="analysis-bar-value">${formatRupiah(amount)}</span>
            </li>`).join("");
            return { title: "Anggaran per Kabupaten/Kota", body: `<ul class="analysis-bars is-money">${items}</ul>`, note: `${numberFormat(result.budgetCount)} fitur beranggaran` };
        })();

        const spatialCard = (() => {
            if (!result.spatial) {
                return null;
            }
            const sp = result.spatial;
            const fmt = (value) => numberFormat(value, 5);
            return {
                title: "Sebaran Spasial",
                body: `<dl class="analysis-spatial">
                        <div><dt>Titik pusat</dt><dd>${fmt(sp.center.lat)}, ${fmt(sp.center.lng)}</dd></div>
                        <div><dt>Rentang lintang</dt><dd>${fmt(sp.lat[0])} s.d. ${fmt(sp.lat[1])}</dd></div>
                        <div><dt>Rentang bujur</dt><dd>${fmt(sp.lng[0])} s.d. ${fmt(sp.lng[1])}</dd></div>
                    </dl>
                    <p class="analysis-caption">Kuadran terhadap titik pusat sebaran</p>
                    ${bars(sp.quadrants, { total: result.total, color: "#20d9ff" })}`,
            };
        })();

        group("Ringkasan", "bi-clipboard-data", [
            insightItems && { title: "Wawasan", body: `<ul class="analysis-insights">${insightItems}</ul>`, wide: true },
            { title: "Perbandingan Layer", body: layerTable(result), wide: true },
            { title: "Komposisi Geometri", body: geometryDonut(result) },
            result.years.length && { title: "Tren per Tahun", body: yearChart(result.years), note: `${numberFormat(result.years.reduce((a, b) => a + b[1], 0))} dari ${numberFormat(result.total)} fitur bertahun` },
        ]);

        group("Sebaran Wilayah & Pengelola", "bi-geo-alt", [
            ...result.regions.slice(0, 2).map((level) => ({ title: `Sebaran per ${level.label}`, body: bars(level.values, { total: level.coverage }), note: `${numberFormat(level.coverage)} dari ${numberFormat(result.total)} fitur berwilayah` })),
            budgetCard,
            result.opd.length && { title: "OPD Pengelola", body: bars(result.opd, { total: result.total, limit: 6 }) },
            result.sources.length > 1 && { title: "Sumber Data", body: bars(result.sources, { total: result.total, limit: 6 }) },
            spatialCard,
        ]);

        group("Detail Atribut", "bi-table", [
            result.numeric.length && { title: "Statistik Atribut Numerik", body: numericTable(result.numeric), wide: true },
            ...result.categorical.map((field) => ({ title: prettyField(field.key), body: bars(field.values, { limit: 5, total: field.count }), note: `${numberFormat(field.distinct)} nilai` })),
        ]);

        const template = selectedTemplate();
        // Footer: kiri = teks template; kanan = otomatis (cakupan analisis & waktu cetak WIT).
        const now = new Date();
        const created = `${now.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric", timeZone: "Asia/Jayapura" })}, ${now.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit", timeZone: "Asia/Jayapura" })} WIT`;
        const footerLeft = template?.footer.text || "Sumber data: MARIMOI — Bappeda Provinsi Maluku Utara";
        const footnote = `<span class="analysis-footer-left">${escapeHtml(footerLeft)}</span>
            <span class="analysis-footer-right">${escapeHtml(scopeLabel)}<br>Dicetak ${escapeHtml(created)}</span>`;
        el.body.innerHTML = `
            ${notices.join("")}
            <div class="analysis-kpis">${kpis}</div>
            ${groups.join("")}
            <footer class="analysis-footnote">${footnote}</footer>`;
    }

    // Pengunjung hanya memilih orientasi; template = template analisis pertama (bawaan dulu) berorientasi itu.
    let orientation = null;

    function templateFor(value) {
        return TEMPLATES.find((template) => template.orientation === value) || null;
    }

    // Template terpilih di antara template berorientasi sama (bawaan/urutan pertama bila belum dipilih).
    function selectedTemplate() {
        if (!orientation) {
            return null;
        }
        const list = TEMPLATES.filter((template) => template.orientation === orientation);
        return list.find((template) => String(template.id) === el.templateSelect?.value) || list[0] || null;
    }

    function populateTemplates() {
        if (!el.templateSelect) {
            return;
        }
        const list = TEMPLATES.filter((template) => template.orientation === orientation);
        const keep = list.some((template) => String(template.id) === el.templateSelect.value) ? el.templateSelect.value : String(list[0]?.id ?? "");
        el.templateSelect.innerHTML = list.map((template) => `<option value="${template.id}"${String(template.id) === keep ? " selected" : ""}>${escapeHtml(template.name)}</option>`).join("");
        el.templateSelect.closest(".analysis-template-select").hidden = list.length <= 1;
    }

    function setOrientation(value) {
        orientation = value;
        populateTemplates();
        el.orientationButtons.forEach((button) => button.setAttribute("aria-pressed", String(button.dataset.analysisOrientation === value)));
        render();
    }

    // ---------------------------------------------------------------------------------------
    // Unduh PDF: laporan A4 multi-halaman digambar ke canvas (tanpa dialog cetak browser)
    // ---------------------------------------------------------------------------------------

    const REPORT_DPI = 150;
    const A4_MM = { portrait: [210, 297], landscape: [297, 210] };
    const REPORT_FONT = "Inter, 'Segoe UI', Arial, sans-serif";
    const KIND_LABEL = { point: "Titik", line: "Garis", polygon: "Area" };
    const INK = "#1d3557";

    function decodeEntities(text) {
        const area = document.createElement("textarea");
        area.innerHTML = text;
        return area.value;
    }

    // Teks wawasan ber-<b> → potongan teks dengan penanda tebal.
    function richRuns(html) {
        let bold = false;
        return html.split(/(<b>|<\/b>)/).flatMap((part) => {
            if (part === "<b>" || part === "</b>") {
                bold = part === "<b>";
                return [];
            }
            return part ? [{ text: decodeEntities(part), bold }] : [];
        });
    }

    /**
     * Kanvas laporan: halaman A4, margin, satuan mm → piksel, dan posisi tulis saat ini.
     */
    function createReport(orientation, template) {
        const D = window.MarimoiDownload;
        const [widthMm, heightMm] = A4_MM[orientation];
        const k = REPORT_DPI / 25.4;
        const mm = (value) => value * k;
        const margin = { x: 14, top: 14, bottom: 12 };
        const contentWidthMm = widthMm - margin.x * 2;
        const footerSize = 9 * D.PT_TO_MM;
        const footerLeft = D.wrapText(template?.footer.text || "Sumber data: MARIMOI — Bappeda Provinsi Maluku Utara", footerSize, 400, contentWidthMm * 0.58);
        const footerHeightMm = 5 + Math.max(footerLeft.length, 3) * footerSize * D.LINE_HEIGHT;
        const R = {
            D, k, mm, orientation, template, widthMm, heightMm, margin, footerLeft, footerSize, footerHeightMm,
            x: mm(margin.x),
            width: mm(contentWidthMm),
            top: mm(margin.top),
            bottom: mm(heightMm - margin.bottom - footerHeightMm),
            pages: [],
            ctx: null,
            y: 0,
            newPage() {
                const canvas = document.createElement("canvas");
                canvas.width = Math.round(mm(widthMm));
                canvas.height = Math.round(mm(heightMm));
                R.ctx = canvas.getContext("2d");
                R.ctx.fillStyle = "#ffffff";
                R.ctx.fillRect(0, 0, canvas.width, canvas.height);
                R.pages.push(canvas);
                R.y = R.top;
            },
        };
        R.newPage();
        return R;
    }

    function setFont(ctx, sizePx, weight = 400, family = REPORT_FONT) {
        ctx.font = `${weight} ${sizePx}px ${family}`;
    }

    function ellipsis(ctx, text, maxWidth) {
        const value = String(text ?? "");
        if (ctx.measureText(value).width <= maxWidth) {
            return value;
        }
        let cut = value;
        while (cut.length > 1 && ctx.measureText(`${cut}…`).width > maxWidth) {
            cut = cut.slice(0, -1);
        }
        return `${cut}…`;
    }

    function roundedRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    // Kerangka kartu: bingkai, judul, catatan kanan. Mengembalikan y awal isi kartu.
    function drawCard(R, x, y, w, h, title, note = "") {
        const { ctx, mm } = R;
        roundedRect(ctx, x, y, w, h, mm(2));
        ctx.fillStyle = "#ffffff";
        ctx.fill();
        ctx.strokeStyle = "#e3e8ef";
        ctx.lineWidth = mm(0.25);
        ctx.stroke();
        ctx.textBaseline = "top";
        ctx.textAlign = "left";
        ctx.fillStyle = INK;
        setFont(ctx, mm(3.1), 700);
        ctx.fillText(ellipsis(ctx, title, w - mm(7) - (note ? mm(40) : 0)), x + mm(3.5), y + mm(3.2));
        if (note) {
            ctx.textAlign = "right";
            ctx.fillStyle = "#94a3b8";
            setFont(ctx, mm(2.3));
            ctx.fillText(ellipsis(ctx, note, mm(40)), x + w - mm(3.5), y + mm(3.6));
        }
        return y + mm(9.5);
    }

    const CARD_CHROME = 9.5 + 3.5; // judul + padding bawah (mm)

    // Daftar batang horizontal: label · batang · nilai (opsional persentase).
    function barsBlock(title, rows, { total = null, note = "", color = null, limit = 8, format = numberFormat, span = "half" } = {}) {
        const visible = rows.slice(0, limit);
        const more = rows.length - visible.length;
        const rowMm = 5.4;
        return {
            span,
            measure: (R) => R.mm(CARD_CHROME + visible.length * rowMm + (more > 0 ? 4 : 0)),
            draw(R, x, y, w, h) {
                const { ctx, mm } = R;
                let top = drawCard(R, x, y, w, h, title, note);
                const max = Math.max(1, ...visible.map((row) => row[1]));
                const labelWidth = w * 0.38;
                const valueWidth = mm(total ? 26 : 22);
                const trackX = x + mm(3.5) + labelWidth + mm(2);
                const trackWidth = w - mm(7) - labelWidth - mm(4) - valueWidth;
                visible.forEach(([label, value], index) => {
                    const cy = top + mm(rowMm / 2);
                    ctx.textBaseline = "middle";
                    ctx.textAlign = "left";
                    ctx.fillStyle = "#334155";
                    setFont(ctx, mm(2.6));
                    ctx.fillText(ellipsis(ctx, label, labelWidth), x + mm(3.5), cy);
                    roundedRect(ctx, trackX, cy - mm(1), trackWidth, mm(2), mm(1));
                    ctx.fillStyle = "#eef2f7";
                    ctx.fill();
                    roundedRect(ctx, trackX, cy - mm(1), Math.max(mm(0.8), (value / max) * trackWidth), mm(2), mm(1));
                    ctx.fillStyle = color || PALETTE[index % PALETTE.length];
                    ctx.fill();
                    ctx.textAlign = "right";
                    ctx.fillStyle = "#0f172a";
                    setFont(ctx, mm(2.6), 700);
                    const valueText = total ? `${format(value)}  ${percent(value, total)}` : format(value);
                    ctx.fillText(ellipsis(ctx, valueText, valueWidth), x + w - mm(3.5), cy);
                    top += mm(rowMm);
                });
                if (more > 0) {
                    ctx.textAlign = "left";
                    ctx.fillStyle = "#94a3b8";
                    setFont(ctx, mm(2.3));
                    ctx.fillText(`+${numberFormat(more)} nilai lainnya`, x + mm(3.5), top + mm(1.5));
                }
            },
        };
    }

    // Kartu angka utama (satu baris penuh).
    function kpiBlock(items) {
        return {
            span: "full",
            measure: (R) => R.mm(15),
            draw(R, x, y, w) {
                const { ctx, mm } = R;
                const gap = mm(2.5);
                const cardWidth = (w - gap * (items.length - 1)) / items.length;
                items.forEach(([value, label], index) => {
                    const cx = x + index * (cardWidth + gap);
                    const gradient = ctx.createLinearGradient(cx, y, cx + cardWidth, y + mm(15));
                    gradient.addColorStop(0, "#071a2d");
                    gradient.addColorStop(1, "#0b2a45");
                    roundedRect(ctx, cx, y, cardWidth, mm(15), mm(2));
                    ctx.fillStyle = gradient;
                    ctx.fill();
                    ctx.textAlign = "left";
                    ctx.textBaseline = "top";
                    ctx.fillStyle = "#ffffff";
                    // Huruf nilai mengecil bila kartu sempit (mis. 5 kartu pada A4 potret).
                    let size = mm(4.4);
                    setFont(ctx, size, 800);
                    while (size > mm(3) && ctx.measureText(value).width > cardWidth - mm(6)) {
                        size -= mm(0.2);
                        setFont(ctx, size, 800);
                    }
                    ctx.fillText(ellipsis(ctx, value, cardWidth - mm(6)), cx + mm(3), y + mm(2.6) + (mm(4.4) - size) / 2);
                    ctx.fillStyle = "rgba(255,255,255,.7)";
                    setFont(ctx, mm(2.4));
                    ctx.fillText(ellipsis(ctx, label, cardWidth - mm(6)), cx + mm(3), y + mm(9.2));
                });
            },
        };
    }

    // Wawasan: butir teks kaya (tebal) yang dibungkus mengikuti lebar kartu.
    function insightsBlock(items, span = "full") {
        const lineMm = 3.9;
        const layoutLines = (R, width) => {
            const { ctx, mm } = R;
            const maxWidth = width - mm(7) - mm(4.5);
            return items.map((html) => {
                const words = richRuns(html).flatMap((run) => run.text.split(/(\s+)/).filter(Boolean).map((text) => ({ text, bold: run.bold })));
                const lines = [[]];
                let lineWidth = 0;
                words.forEach((word) => {
                    setFont(ctx, mm(2.7), word.bold ? 700 : 400);
                    const wordWidth = ctx.measureText(word.text).width;
                    if (/^\s+$/.test(word.text) && !lines[lines.length - 1].length) {
                        return;
                    }
                    if (lineWidth + wordWidth > maxWidth && lines[lines.length - 1].length) {
                        lines.push([]);
                        lineWidth = 0;
                        if (/^\s+$/.test(word.text)) {
                            return;
                        }
                    }
                    lines[lines.length - 1].push({ ...word, width: wordWidth });
                    lineWidth += wordWidth;
                });
                return lines;
            });
        };
        return {
            span,
            measure: (R, width) => R.mm(CARD_CHROME + layoutLines(R, width).reduce((sum, lines) => sum + lines.length * lineMm + 1.2, 0)),
            draw(R, x, y, w, h) {
                const { ctx, mm } = R;
                let top = drawCard(R, x, y, w, h, "Wawasan");
                layoutLines(R, w).forEach((lines) => {
                    ctx.fillStyle = "#f59e0b";
                    ctx.beginPath();
                    ctx.arc(x + mm(5), top + mm(lineMm / 2), mm(0.8), 0, Math.PI * 2);
                    ctx.fill();
                    lines.forEach((line) => {
                        let cursor = x + mm(3.5) + mm(4.5);
                        line.forEach((word) => {
                            setFont(ctx, mm(2.7), word.bold ? 700 : 400);
                            ctx.fillStyle = word.bold ? "#0f172a" : "#334155";
                            ctx.textAlign = "left";
                            ctx.textBaseline = "middle";
                            ctx.fillText(word.text, cursor, top + mm(lineMm / 2));
                            cursor += word.width;
                        });
                        top += mm(lineMm);
                    });
                    top += mm(1.2);
                });
            },
        };
    }

    // Donat komposisi geometri + legenda.
    function donutBlock(result) {
        const counts = { point: 0, line: 0, polygon: 0 };
        result.perLayer.forEach((layer) => { if (layer.kind) counts[layer.kind] += layer.count; });
        const parts = [["Titik", counts.point, "#0a84ff"], ["Garis", counts.line, "#f59e0b"], ["Area", counts.polygon, "#4de1c1"]].filter((part) => part[1] > 0);
        return {
            span: "half",
            measure: (R) => R.mm(CARD_CHROME + 26),
            draw(R, x, y, w, h) {
                const { ctx, mm } = R;
                const top = drawCard(R, x, y, w, h, "Komposisi Geometri");
                const cx = x + mm(16);
                const cy = top + mm(13);
                let start = -Math.PI / 2;
                parts.forEach(([, value, color]) => {
                    const end = start + (value / result.total) * Math.PI * 2;
                    ctx.beginPath();
                    ctx.arc(cx, cy, mm(11), start, end);
                    ctx.arc(cx, cy, mm(7), end, start, true);
                    ctx.closePath();
                    ctx.fillStyle = color;
                    ctx.fill();
                    start = end;
                });
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.fillStyle = "#0f172a";
                setFont(ctx, mm(3.4), 800);
                ctx.fillText(numberFormat(result.total), cx, cy - mm(0.8));
                ctx.fillStyle = "#94a3b8";
                setFont(ctx, mm(2.1));
                ctx.fillText("fitur", cx, cy + mm(2.6));
                parts.forEach(([label, value, color], index) => {
                    const ly = top + mm(6 + index * 6);
                    const lx = x + mm(33);
                    ctx.fillStyle = color;
                    roundedRect(ctx, lx, ly - mm(1.4), mm(2.8), mm(2.8), mm(0.6));
                    ctx.fill();
                    ctx.textAlign = "left";
                    ctx.fillStyle = "#334155";
                    setFont(ctx, mm(2.7));
                    ctx.fillText(label, lx + mm(4.5), ly);
                    ctx.textAlign = "right";
                    ctx.fillStyle = "#0f172a";
                    setFont(ctx, mm(2.7), 700);
                    ctx.fillText(`${numberFormat(value)}  ${percent(value, result.total)}`, x + w - mm(3.5), ly);
                });
            },
        };
    }

    // Grafik kolom per tahun.
    function yearBlock(result) {
        const years = result.years;
        const note = `${numberFormat(years.reduce((a, b) => a + b[1], 0))} dari ${numberFormat(result.total)} fitur memiliki tahun`;
        return {
            span: "full",
            measure: (R) => R.mm(CARD_CHROME + 30),
            draw(R, x, y, w, h) {
                const { ctx, mm } = R;
                const top = drawCard(R, x, y, w, h, "Tren per Tahun", note);
                const chartHeight = mm(22);
                const base = top + chartHeight + mm(2);
                const max = Math.max(...years.map((year) => year[1]));
                const slot = (w - mm(7)) / years.length;
                const barWidth = Math.min(mm(12), slot * 0.6);
                ctx.strokeStyle = "#e2e8f0";
                ctx.lineWidth = mm(0.25);
                ctx.beginPath();
                ctx.moveTo(x + mm(3.5), base);
                ctx.lineTo(x + w - mm(3.5), base);
                ctx.stroke();
                years.forEach(([year, count], index) => {
                    const barHeight = (chartHeight - mm(4)) * (count / max);
                    const bx = x + mm(3.5) + slot * index + (slot - barWidth) / 2;
                    const gradient = ctx.createLinearGradient(0, base - barHeight, 0, base);
                    gradient.addColorStop(0, "#20d9ff");
                    gradient.addColorStop(1, "#0a84ff");
                    roundedRect(ctx, bx, base - barHeight, barWidth, barHeight, Math.min(mm(1), barHeight / 2));
                    ctx.fillStyle = gradient;
                    ctx.fill();
                    ctx.textAlign = "center";
                    ctx.textBaseline = "bottom";
                    ctx.fillStyle = "#0f172a";
                    setFont(ctx, mm(2.4), 700);
                    ctx.fillText(numberFormat(count), bx + barWidth / 2, base - barHeight - mm(0.6));
                    ctx.textBaseline = "top";
                    ctx.fillStyle = "#64748b";
                    setFont(ctx, mm(2.4));
                    ctx.fillText(String(year), bx + barWidth / 2, base + mm(1));
                });
            },
        };
    }

    // Sebaran spasial: titik pusat, rentang koordinat, dan kuadran.
    function spatialBlock(result) {
        const s = result.spatial;
        const fmt = (value) => numberFormat(value, 5);
        const stats = [["Titik pusat", `${fmt(s.center.lat)}, ${fmt(s.center.lng)}`], ["Rentang lintang", `${fmt(s.lat[0])} s.d. ${fmt(s.lat[1])}`], ["Rentang bujur", `${fmt(s.lng[0])} s.d. ${fmt(s.lng[1])}`]];
        return {
            span: "half",
            measure: (R) => R.mm(CARD_CHROME + stats.length * 5 + 2 + s.quadrants.length * 5.4),
            draw(R, x, y, w, h) {
                const { ctx, mm } = R;
                let top = drawCard(R, x, y, w, h, "Sebaran Spasial");
                stats.forEach(([label, value]) => {
                    ctx.textBaseline = "middle";
                    ctx.textAlign = "left";
                    ctx.fillStyle = "#64748b";
                    setFont(ctx, mm(2.5));
                    ctx.fillText(label, x + mm(3.5), top + mm(2.5));
                    ctx.textAlign = "right";
                    ctx.fillStyle = "#0f172a";
                    setFont(ctx, mm(2.5), 700);
                    ctx.fillText(value, x + w - mm(3.5), top + mm(2.5));
                    top += mm(5);
                });
                // Kuadran terhadap titik pusat sebaran.
                const max = Math.max(1, ...s.quadrants.map((row) => row[1]));
                s.quadrants.forEach(([label, value], index) => {
                    const cy = top + mm(2) + mm(2.7) + index * mm(5.4);
                    const labelWidth = w * 0.38;
                    const valueWidth = mm(26);
                    const trackX = x + mm(3.5) + labelWidth + mm(2);
                    const trackWidth = w - mm(7) - labelWidth - mm(4) - valueWidth;
                    ctx.textAlign = "left";
                    ctx.fillStyle = "#334155";
                    setFont(ctx, mm(2.6));
                    ctx.fillText(label, x + mm(3.5), cy);
                    roundedRect(ctx, trackX, cy - mm(1), trackWidth, mm(2), mm(1));
                    ctx.fillStyle = "#eef2f7";
                    ctx.fill();
                    roundedRect(ctx, trackX, cy - mm(1), Math.max(mm(0.8), (value / max) * trackWidth), mm(2), mm(1));
                    ctx.fillStyle = "#20d9ff";
                    ctx.fill();
                    ctx.textAlign = "right";
                    ctx.fillStyle = "#0f172a";
                    setFont(ctx, mm(2.6), 700);
                    ctx.fillText(`${numberFormat(value)}  ${percent(value, result.total)}`, x + w - mm(3.5), cy);
                });
            },
        };
    }

    /**
     * Tabel yang boleh terpotong antarhalaman (kepala tabel diulang di halaman lanjutan).
     * columns: [{ label, width (fraksi), align, value(row) → string | {text, sub} }]
     */
    function tableBlock(title, columns, rows, { rowMm = 5.6, note = "" } = {}) {
        const headerMm = 6;
        return {
            span: "full",
            rows,
            headerHeight: (R) => R.mm(9.5 + headerMm),
            rowHeight: (R) => R.mm(rowMm),
            drawPart(R, x, y, w, from, to, continued) {
                const { ctx, mm } = R;
                const h = mm(9.5 + headerMm + (to - from) * rowMm + 3.5);
                let top = drawCard(R, x, y, w, h, continued ? `${title} (lanjutan)` : title, note);
                const inner = w - mm(7);
                const xs = [];
                let cursor = x + mm(3.5);
                columns.forEach((column) => { xs.push(cursor); cursor += column.width * inner; });
                const cellX = (index) => (columns[index].align === "right" ? xs[index] + columns[index].width * inner - mm(1) : xs[index] + mm(1));
                ctx.textBaseline = "middle";
                setFont(ctx, mm(2.4), 600);
                ctx.fillStyle = "#64748b";
                columns.forEach((column, index) => {
                    ctx.textAlign = column.align === "right" ? "right" : "left";
                    ctx.fillText(column.label, cellX(index), top + mm(headerMm / 2));
                });
                top += mm(headerMm);
                ctx.strokeStyle = "#e3e8ef";
                ctx.lineWidth = mm(0.25);
                for (let index = from; index < to; index++) {
                    ctx.beginPath();
                    ctx.moveTo(x + mm(3.5), top);
                    ctx.lineTo(x + w - mm(3.5), top);
                    ctx.stroke();
                    columns.forEach((column, columnIndex) => {
                        const raw = column.value(rows[index]);
                        // Sel teks biasa vs sel objek {text, sub, color} (string punya method bawaan .sub!).
                        const cell = raw !== null && typeof raw === "object" ? raw : { text: raw };
                        const text = cell.text;
                        const maxWidth = column.width * inner - mm(2);
                        ctx.textAlign = column.align === "right" ? "right" : "left";
                        if (cell.color) {
                            ctx.fillStyle = cell.color;
                            roundedRect(ctx, xs[columnIndex] + mm(1), top + mm(rowMm / 2) - mm(1.2), mm(2.4), mm(2.4), mm(0.5));
                            ctx.fill();
                        }
                        const textX = cell.color ? cellX(columnIndex) + mm(3.6) : cellX(columnIndex);
                        ctx.fillStyle = "#1e293b";
                        setFont(ctx, mm(2.6), columnIndex === 0 ? 600 : 400);
                        if (cell.sub) {
                            ctx.fillText(ellipsis(ctx, text, maxWidth - mm(3.6)), textX, top + mm(rowMm / 2) - mm(1.5));
                            ctx.fillStyle = "#94a3b8";
                            setFont(ctx, mm(2.2));
                            ctx.fillText(ellipsis(ctx, cell.sub, maxWidth - mm(3.6)), textX, top + mm(rowMm / 2) + mm(1.6));
                        } else {
                            ctx.fillText(ellipsis(ctx, text, maxWidth), textX, top + mm(rowMm / 2));
                        }
                    });
                    top += mm(rowMm);
                }
                return h;
            },
        };
    }

    // Susun blok ke halaman: blok "half" dipasangkan dua per baris; tabel dipotong per baris.
    function layoutBlocks(R, input) {
        // Pasangkan kartu setengah lebar: kartu setengah yang akan tersisa sendirian mengambil
        // kartu setengah berikutnya sebagai pasangan agar tidak ada ruang kosong di sebelahnya.
        const blocks = [...input];
        for (let index = 0; index < blocks.length; index++) {
            if (blocks[index].span !== "half") {
                continue;
            }
            if (blocks[index + 1]?.span === "half") {
                index++;
                continue;
            }
            const partner = blocks.findIndex((block, other) => other > index && block.span === "half");
            if (partner > -1) {
                blocks.splice(index + 1, 0, ...blocks.splice(partner, 1));
                index++;
            }
        }
        const gap = R.mm(3);
        const halfWidth = (R.width - gap) / 2;
        let pending = null;

        const ensure = (height) => {
            if (R.y + height > R.bottom && R.y > R.top + 1) {
                R.newPage();
            }
        };
        const placeRow = (items) => {
            const height = Math.max(...items.map((item) => item.block.measure(R, item.width)));
            ensure(height);
            items.forEach((item) => item.block.draw(R, item.x, R.y, item.width, height));
            R.y += height + gap;
        };
        const flush = () => {
            if (pending) {
                placeRow([{ block: pending, x: R.x, width: halfWidth }]);
                pending = null;
            }
        };

        blocks.forEach((block) => {
            if (block.span === "half") {
                if (pending) {
                    placeRow([{ block: pending, x: R.x, width: halfWidth }, { block, x: R.x + halfWidth + gap, width: halfWidth }]);
                    pending = null;
                } else {
                    pending = block;
                }
                return;
            }
            flush();
            if (block.rows) {
                let from = 0;
                let continued = false;
                while (from < block.rows.length) {
                    let fit = Math.floor((R.bottom - R.y - block.headerHeight(R) - R.mm(3.5)) / block.rowHeight(R));
                    if (fit < Math.min(2, block.rows.length - from)) {
                        R.newPage();
                        fit = Math.floor((R.bottom - R.y - block.headerHeight(R) - R.mm(3.5)) / block.rowHeight(R));
                    }
                    const to = Math.min(block.rows.length, from + Math.max(1, fit));
                    const height = block.drawPart(R, R.x, R.y, R.width, from, to, continued);
                    R.y += height + gap;
                    from = to;
                    continued = true;
                    if (from < block.rows.length) {
                        R.newPage();
                    }
                }
                return;
            }
            placeRow([{ block, x: R.x, width: R.width }]);
        });
        flush();
    }

    // Kop (halaman pertama) + judul laporan.
    async function drawReportHeader(R, scopeLabel, entries) {
        const { ctx, mm, D, template } = R;
        if (template?.header) {
            const plan = D.planKop(template, R.width / R.k, 1);
            await D.drawKop(ctx, template, plan, { x: R.x, y: R.y, w: R.width, h: mm(plan.height) }, R.k);
            R.y += mm(plan.height + 2);
        }
        ctx.textAlign = "left";
        ctx.textBaseline = "top";
        ctx.fillStyle = template?.accentColor || INK;
        setFont(ctx, mm(5.6), 800);
        ctx.fillText("Analisis Peta", R.x, R.y);
        R.y += mm(7.4);
        ctx.fillStyle = "#64748b";
        setFont(ctx, mm(2.7));
        const layers = entries.map((entry) => entry.leafName).join(", ");
        // Dibungkus dengan font yang sama dengan saat digambar.
        const words = `${scopeLabel} · Layer: ${layers}`.split(/\s+/);
        const lines = [""];
        words.forEach((word) => {
            const candidate = lines[lines.length - 1] ? `${lines[lines.length - 1]} ${word}` : word;
            if (lines[lines.length - 1] && ctx.measureText(candidate).width > R.width) {
                lines.push(word);
            } else {
                lines[lines.length - 1] = candidate;
            }
        });
        if (lines.length > 3) {
            lines.length = 3;
            lines[2] = ellipsis(ctx, `${lines[2]} …`, R.width);
        }
        lines.forEach((line) => {
            ctx.fillText(line, R.x, R.y);
            R.y += mm(3.6);
        });
        R.y += mm(2.5);
    }

    // Footer tiap halaman (Arial 9pt): kiri teks template, kanan otomatis + nomor halaman.
    function drawReportFooters(R, scopeLabel) {
        const { mm, D } = R;
        const right = [scopeLabel, D.printedAt()];
        R.pages.forEach((canvas, index) => {
            const ctx = canvas.getContext("2d");
            const top = mm(R.heightMm - R.margin.bottom - R.footerHeightMm + 5);
            ctx.strokeStyle = "#e2e8f0";
            ctx.lineWidth = mm(0.3);
            ctx.beginPath();
            ctx.moveTo(R.x, top - mm(2.2));
            ctx.lineTo(R.x + R.width, top - mm(2.2));
            ctx.stroke();
            ctx.fillStyle = "#475569";
            ctx.textBaseline = "top";
            ctx.font = `${mm(R.footerSize)}px ${D.KOP_FONT}`;
            const lineHeight = mm(R.footerSize * D.LINE_HEIGHT);
            ctx.textAlign = "left";
            R.footerLeft.forEach((line, row) => ctx.fillText(line, R.x, top + row * lineHeight));
            ctx.textAlign = "right";
            [...right, `Halaman ${index + 1} dari ${R.pages.length}`].forEach((line, row) => ctx.fillText(line, R.x + R.width, top + row * lineHeight));
        });
    }

    /**
     * Susun seluruh laporan untuk hasil analisis saat ini dan unduh sebagai PDF A4.
     */
    async function downloadReport() {
        const D = window.MarimoiDownload;
        const { ready } = readyEntries();
        if (!D || !ready.length) {
            return;
        }
        await document.fonts?.ready;
        const result = analyse(gatherFeatures(ready));
        const orientationValue = orientation || "portrait";
        const template = selectedTemplate();
        const scopeLabel = scope === "view" ? "Cakupan: tampilan peta saat ini" : "Cakupan: seluruh data layer aktif";
        const R = createReport(orientationValue, template);
        await drawReportHeader(R, scopeLabel, ready);

        const blocks = [];
        const kpis = [
            [numberFormat(result.total), "Fitur dianalisis"],
            [numberFormat(result.perLayer.filter((layer) => layer.count > 0).length), "Layer berisi data"],
            [formatArea(result.area), "Total luas area"],
            [formatLength(result.length), "Total panjang garis"],
        ];
        if (result.budget > 0) {
            kpis.push([formatRupiah(result.budget), "Total anggaran/nilai"]);
        }
        blocks.push(kpiBlock(kpis));

        const layerRows = [...result.perLayer].sort((a, b) => b.count - a.count);
        blocks.push(tableBlock("Perbandingan Layer", [
            { label: "Layer", width: 0.5, value: (layer) => ({ text: layer.entry.leafName, sub: [[layer.entry.rootName, layer.entry.secondName].filter((name, index, arr) => name && name !== layer.entry.leafName && arr.indexOf(name) === index).join(" › "), KIND_LABEL[layer.kind] || "Tanpa geometri"].filter(Boolean).join(" · "), color: layer.entry.color }) },
            { label: "Fitur", width: 0.14, align: "right", value: (layer) => numberFormat(layer.count) },
            { label: "Porsi", width: 0.14, align: "right", value: (layer) => percent(layer.count, result.total) },
            { label: "Luas / Panjang", width: 0.22, align: "right", value: (layer) => (layer.kind === "polygon" ? formatArea(layer.area) : layer.kind === "line" ? formatLength(layer.length) : "–") },
        ], layerRows, { rowMm: 8 }));

        const insightItems = insights(result);
        if (insightItems.length) {
            blocks.push(insightsBlock(insightItems, R.orientation === "landscape" ? "half" : "full"));
        }
        blocks.push(donutBlock(result));
        if (result.years.length) {
            blocks.push(yearBlock(result));
        }
        result.regions.slice(0, 2).forEach((level) => {
            blocks.push(barsBlock(`Sebaran per ${level.label}`, level.values, { total: level.coverage, note: `${numberFormat(level.coverage)} fitur berwilayah` }));
        });
        if (result.budgetByRegency.length) {
            blocks.push(barsBlock("Anggaran per Kabupaten/Kota", result.budgetByRegency, { format: formatRupiah, note: `${numberFormat(result.budgetCount)} fitur beranggaran` }));
        }
        if (result.opd.length) {
            blocks.push(barsBlock("OPD Pengelola", result.opd, { total: result.total, limit: 6 }));
        }
        if (result.sources.length > 1) {
            blocks.push(barsBlock("Sumber Data", result.sources, { total: result.total, limit: 6 }));
        }
        if (result.spatial) {
            blocks.push(spatialBlock(result));
        }
        if (result.numeric.length) {
            blocks.push(tableBlock("Statistik Atribut Numerik", [
                { label: "Atribut", width: 0.3, value: (field) => prettyField(field.key) + (field.currency ? " (Rp)" : "") },
                ...["min", "avg", "max", "sum"].map((key, index) => ({ label: ["Min", "Rata-rata", "Maks", "Total"][index], width: 0.15, align: "right", value: (field) => (field.currency ? formatRupiah : smartNumber)(field[key]) })),
                { label: "Jumlah", width: 0.1, align: "right", value: (field) => numberFormat(field.count) },
            ], result.numeric));
        }
        result.categorical.forEach((field) => {
            blocks.push(barsBlock(`Atribut: ${prettyField(field.key)}`, field.values, { total: field.count, limit: 5, note: `${numberFormat(field.distinct)} nilai` }));
        });

        layoutBlocks(R, blocks);
        drawReportFooters(R, scopeLabel);

        const pages = [];
        for (const canvas of R.pages) {
            pages.push(await D.canvasJpeg(canvas));
        }
        const [widthMm, heightMm] = A4_MM[orientationValue];
        const blob = D.buildPdf(pages, widthMm, heightMm, "Analisis Peta MARIMOI");
        D.saveBlob(blob, `analisis-peta-marimoi-${orientationValue === "portrait" ? "potret" : "lanskap"}-${new Date().toISOString().slice(0, 10)}.pdf`);
        return pages.length;
    }

    async function onDownloadClick() {
        const button = el.downloadButton;
        if (button.disabled) {
            return;
        }
        const label = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>Menyiapkan PDF…</span>';
        try {
            const pages = await downloadReport();
            button.innerHTML = `<i class="bi bi-check-lg"></i> <span>Terunduh (${pages} hlm)</span>`;
        } catch (error) {
            button.innerHTML = '<i class="bi bi-x-lg"></i> <span>Gagal, coba lagi</span>';
        }
        setTimeout(() => {
            button.innerHTML = label;
            button.disabled = false;
        }, 2200);
    }

    // ---------------------------------------------------------------------------------------
    // Modal & tombol
    // ---------------------------------------------------------------------------------------

    function isOpen() {
        return el.modal && !el.modal.classList.contains("hidden");
    }

    function hasActiveLayer() {
        return (window.MarimoiCatalog?.getActiveEntries() || []).length > 0;
    }

    function syncButton() {
        if (!el.button) {
            return;
        }
        const enabled = hasActiveLayer();
        el.button.setAttribute("aria-disabled", String(!enabled));
        el.button.classList.toggle("is-disabled", !enabled);
        el.button.dataset.tooltip = enabled ? "Analisis Peta" : "Analisis Peta (aktifkan minimal 1 layer)";
        el.button.title = el.button.dataset.tooltip;
        if (!enabled && isOpen()) {
            close();
        }
    }

    function open() {
        if (!hasActiveLayer()) {
            if (typeof showAlert === "function") {
                showAlert("Aktifkan minimal satu layer untuk melihat analisis peta.", "info");
            }
            window.MarimoiCatalog?.open();
            return;
        }
        el.modal.classList.remove("hidden");
        document.body.classList.add("analysis-open");
        render();
        el.closeButton.focus();
    }

    function close() {
        el.modal.classList.add("hidden");
        document.body.classList.remove("analysis-open");
        el.button?.focus();
    }

    // Hitung ulang setelah peta berhenti bergerak/layer berubah, digabung agar tidak berat.
    function scheduleRender() {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(render, 250);
    }

    function setScope(next) {
        scope = next;
        el.scopeButtons.forEach((button) => button.setAttribute("aria-pressed", String(button.dataset.analysisScope === scope)));
        render();
    }

    document.addEventListener("DOMContentLoaded", () => {
        el.modal = document.getElementById("analysisModal");
        el.button = document.getElementById("btn-open-analysis");
        if (!el.modal || !el.button || typeof map === "undefined") {
            return;
        }
        el.body = el.modal.querySelector("[data-analysis-body]");
        el.subtitle = el.modal.querySelector("[data-analysis-subtitle]");
        el.closeButton = el.modal.querySelector("[data-analysis-close]");
        el.scopeButtons = [...el.modal.querySelectorAll("[data-analysis-scope]")];
        el.orientationButtons = [...el.modal.querySelectorAll("[data-analysis-orientation]")];
        el.templateSelect = el.modal.querySelector("[data-analysis-template]");
        el.templateSelect?.addEventListener("change", render);
        const orientationGroup = el.modal.querySelector("[data-analysis-orientations]");
        // Tanpa template pun pengunjung tetap memilih orientasi A4 (tanpa kop).
        if (orientationGroup && !TEMPLATES.length) {
            orientationGroup.hidden = false;
            el.orientationButtons.forEach((button) => button.addEventListener("click", () => setOrientation(button.dataset.analysisOrientation)));
            setOrientation("portrait");
        }
        if (orientationGroup && TEMPLATES.length) {
            orientationGroup.hidden = false;
            el.orientationButtons.forEach((button) => {
                const available = Boolean(templateFor(button.dataset.analysisOrientation));
                button.disabled = !available;
                button.title = available ? `Cetak ${button.textContent.trim().toLowerCase()} (${templateFor(button.dataset.analysisOrientation).name})` : "Belum ada template untuk orientasi ini";
                button.addEventListener("click", () => setOrientation(button.dataset.analysisOrientation));
            });
            // Awal: orientasi template bawaan (urutan pertama).
            orientation = TEMPLATES[0].orientation;
            setOrientation(orientation);
        }

        el.button.addEventListener("click", open);
        el.closeButton.addEventListener("click", close);
        el.modal.addEventListener("click", (event) => { if (event.target === el.modal) close(); });
        el.scopeButtons.forEach((button) => button.addEventListener("click", () => setScope(button.dataset.analysisScope)));
        el.downloadButton = el.modal.querySelector("[data-analysis-download]");
        el.downloadButton.addEventListener("click", onDownloadClick);
        document.addEventListener("keydown", (event) => { if (event.key === "Escape" && isOpen()) close(); });

        document.addEventListener("marimoi:active-layers-change", () => { syncButton(); if (isOpen()) scheduleRender(); });
        map.on("moveend", () => { if (isOpen() && scope === "view") scheduleRender(); });
        syncButton();
    });

    window.MarimoiAnalysis = { open, close, refresh: scheduleRender };
})();
