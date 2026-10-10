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

        const insightItems = insights(result).map((text) => `<li><i class="bi bi-lightbulb"></i><span>${text}</span></li>`).join("");
        const sections = [];

        sections.push(card("Perbandingan Layer", layerTable(result), { wide: true }));
        sections.push(card("Wawasan", `<ul class="analysis-insights">${insightItems}</ul>`));
        sections.push(card("Komposisi Geometri", geometryDonut(result)));

        if (result.years.length) {
            sections.push(card("Tren per Tahun", yearChart(result.years), { wide: true, note: `${numberFormat(result.years.reduce((a, b) => a + b[1], 0))} dari ${numberFormat(result.total)} fitur memiliki tahun` }));
        }

        result.regions.slice(0, 2).forEach((level) => {
            sections.push(card(`Sebaran per ${level.label}`, bars(level.values, { total: level.coverage }), { note: `${numberFormat(level.coverage)} dari ${numberFormat(result.total)} fitur memiliki data wilayah` }));
        });

        if (result.budgetByRegency.length) {
            const rows = result.budgetByRegency.slice(0, 8);
            const top = Math.max(...rows.map((row) => row[1]));
            const items = rows.map(([name, amount], index) => `<li>
                <span class="analysis-bar-label" title="${escapeHtml(name)}">${escapeHtml(name)}</span>
                <span class="analysis-bar-track"><span style="width:${Math.max(2, (amount / top) * 100)}%;background:${PALETTE[index % PALETTE.length]}"></span></span>
                <span class="analysis-bar-value">${formatRupiah(amount)}</span>
            </li>`).join("");
            sections.push(card("Anggaran per Kabupaten/Kota", `<ul class="analysis-bars is-money">${items}</ul>`, { note: `${numberFormat(result.budgetCount)} fitur beranggaran` }));
        }

        if (result.opd.length) {
            sections.push(card("OPD Pengelola", bars(result.opd, { total: result.total, limit: 6 })));
        }
        if (result.sources.length > 1) {
            sections.push(card("Sumber Data", bars(result.sources, { total: result.total, limit: 6 })));
        }

        if (result.spatial) {
            const s = result.spatial;
            const fmt = (value) => numberFormat(value, 5);
            sections.push(card("Sebaran Spasial", `
                <dl class="analysis-spatial">
                    <div><dt>Titik pusat</dt><dd>${fmt(s.center.lat)}, ${fmt(s.center.lng)}</dd></div>
                    <div><dt>Rentang lintang</dt><dd>${fmt(s.lat[0])} s.d. ${fmt(s.lat[1])}</dd></div>
                    <div><dt>Rentang bujur</dt><dd>${fmt(s.lng[0])} s.d. ${fmt(s.lng[1])}</dd></div>
                </dl>
                <p class="analysis-caption">Kuadran terhadap titik pusat sebaran</p>
                ${bars(s.quadrants, { total: result.total, color: "#20d9ff" })}`));
        }

        if (result.numeric.length) {
            sections.push(card("Statistik Atribut Numerik", numericTable(result.numeric), { wide: true }));
        }

        if (result.categorical.length) {
            const groups = result.categorical.map((field) => `<div class="analysis-attribute">
                <h4>${escapeHtml(prettyField(field.key))} <small>${numberFormat(field.distinct)} nilai</small></h4>
                ${bars(field.values, { limit: 5, total: field.count })}
            </div>`).join("");
            sections.push(card("Rincian Atribut", `<div class="analysis-attributes">${groups}</div>`, { wide: true }));
        }

        el.body.innerHTML = `
            ${notices.join("")}
            <div class="analysis-kpis">${kpis}</div>
            <div class="analysis-grid">${sections.join("")}</div>
            <p class="analysis-footnote">Sumber: data MARIMOI pada layer aktif · dibuat ${new Date().toLocaleString("id-ID", { dateStyle: "long", timeStyle: "short" })}</p>`;
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

        el.button.addEventListener("click", open);
        el.closeButton.addEventListener("click", close);
        el.modal.addEventListener("click", (event) => { if (event.target === el.modal) close(); });
        el.scopeButtons.forEach((button) => button.addEventListener("click", () => setScope(button.dataset.analysisScope)));
        el.modal.querySelector("[data-analysis-print]").addEventListener("click", () => window.print());
        document.addEventListener("keydown", (event) => { if (event.key === "Escape" && isOpen()) close(); });

        document.addEventListener("marimoi:active-layers-change", () => { syncButton(); if (isOpen()) scheduleRender(); });
        map.on("moveend", () => { if (isOpen() && scope === "view") scheduleRender(); });
        syncButton();
    });

    window.MarimoiAnalysis = { open, close, refresh: scheduleRender };
})();
