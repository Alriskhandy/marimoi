/**
 * Unduh Peta: tata letak cetak (judul, peta, legenda, skala, arah utara, sumber) ke PNG atau PDF.
 *
 * Peta digambar ulang ke canvas, bukan disalin dari layar, supaya resolusinya mengikuti kertas:
 * tile basemap diunduh pada zoom yang sesuai lalu fitur vektor digambar dari GeoJSON-nya
 * dengan style yang sedang tampil. Area cetak = bingkai pratinjau yang tampil di peta selama
 * panel Unduh Peta terbuka. PDF dibuat langsung (satu halaman berisi gambar JPEG) tanpa pustaka.
 */
(function () {
    const PAPER_MM = { A1: [594, 841], A2: [420, 594], A3: [297, 420], A4: [210, 297], A5: [148, 210] };
    const RESOLUTION_DPI = { low: 96, medium: 150, high: 300 };
    const RESOLUTION_LABEL = { low: "Rendah", medium: "Sedang", high: "Tinggi" };
    // Batas luas canvas: Safari (termasuk iOS) gagal di atas ±16,7 juta piksel.
    const IS_SAFARI = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    const MAX_PIXELS = IS_SAFARI ? 16.7e6 : 36e6;
    const MAX_TILES = 600;
    const TILE_CONCURRENCY = 12;
    const FONT = "Inter, 'Segoe UI', Arial, sans-serif";

    const el = {};
    let frameEl = null;
    let job = null;

    // ---------------------------------------------------------------------------------------
    // Pengaturan & ukuran keluaran
    // ---------------------------------------------------------------------------------------

    function readSettings() {
        const form = el.form;
        return {
            title: form.elements.title.value.trim() || "Peta Interaktif MARIMOI",
            paper: form.elements.paper.value,
            orientation: form.elements.orientation.value,
            resolution: form.elements.resolution.value,
            format: form.elements.format.value,
            legend: form.elements.legend.checked,
            labels: form.elements.labels.checked,
        };
    }

    /**
     * Ukuran halaman (mm & px) dan dpi efektif; dpi diturunkan bila melewati batas canvas.
     */
    function pageSize(settings) {
        const [shortSide, longSide] = PAPER_MM[settings.paper];
        const widthMm = settings.orientation === "landscape" ? longSide : shortSide;
        const heightMm = settings.orientation === "landscape" ? shortSide : longSide;
        let dpi = RESOLUTION_DPI[settings.resolution];
        const pixelsAt = (value) => (widthMm / 25.4) * value * ((heightMm / 25.4) * value);
        const limited = pixelsAt(dpi) > MAX_PIXELS;
        if (limited) {
            dpi = Math.floor(Math.sqrt(MAX_PIXELS / ((widthMm / 25.4) * (heightMm / 25.4))));
        }
        return {
            widthMm,
            heightMm,
            dpi,
            limited,
            width: Math.round((widthMm / 25.4) * dpi),
            height: Math.round((heightMm / 25.4) * dpi),
        };
    }

    /**
     * Tata letak halaman dalam mm; ukuran huruf & jarak sebanding sisi pendek kertas.
     * Lanskap: legenda di kanan peta. Potret: legenda di bawah peta.
     */
    function layout(page, settings) {
        const unit = Math.min(page.widthMm, page.heightMm) / 210;
        const margin = 10 * unit;
        const header = 17 * unit;
        const footer = 9 * unit;
        const gap = 5 * unit;
        const content = { x: margin, y: margin + header, w: page.widthMm - margin * 2, h: page.heightMm - margin * 2 - header - footer };
        let mapBox = { ...content };
        let legendBox = null;
        if (settings.legend) {
            if (settings.orientation === "landscape") {
                const legendWidth = 62 * unit;
                mapBox = { ...content, w: content.w - legendWidth - gap };
                legendBox = { x: mapBox.x + mapBox.w + gap, y: content.y, w: legendWidth, h: content.h };
            } else {
                const legendHeight = 46 * unit;
                mapBox = { ...content, h: content.h - legendHeight - gap };
                legendBox = { x: content.x, y: mapBox.y + mapBox.h + gap, w: content.w, h: legendHeight };
            }
        }
        return { unit, margin, header, footer, mapBox, legendBox };
    }

    function describeOutput() {
        const settings = readSettings();
        const page = pageSize(settings);
        const megapixels = (page.width * page.height) / 1e6;
        el.summary.innerHTML = `
            <span><b>${page.widthMm} × ${page.heightMm} mm</b> · ${settings.orientation === "landscape" ? "lanskap" : "potret"}</span>
            <span>${page.width.toLocaleString("id-ID")} × ${page.height.toLocaleString("id-ID")} px · ±${page.dpi} dpi (${megapixels.toLocaleString("id-ID", { maximumFractionDigits: 1 })} MP)</span>
            ${page.limited ? `<span class="download-summary-warn"><i class="bi bi-info-circle"></i> Resolusi ${RESOLUTION_LABEL[settings.resolution].toLowerCase()} untuk ${settings.paper} melebihi batas browser, jadi diturunkan ke ±${page.dpi} dpi.</span>` : ""}`;
        placeFrame();
    }

    // ---------------------------------------------------------------------------------------
    // Bingkai pratinjau di atas peta
    // ---------------------------------------------------------------------------------------

    function isOpen() {
        return el.sidebar && !el.sidebar.classList.contains("hidden");
    }

    /**
     * Bingkai seukuran rasio area peta pada kertas, di ruang peta yang tidak tertutup panel.
     * @returns {{x: number, y: number, w: number, h: number}} posisi dalam piksel kontainer peta
     */
    function frameRect() {
        const size = map.getSize();
        const settings = readSettings();
        const box = layout(pageSize(settings), settings).mapBox;
        const container = map.getContainer().getBoundingClientRect();
        let area;
        if (window.innerWidth < 768) {
            // Ponsel: panel menutup bagian bawah, bingkai di ruang atas.
            const panelTop = isOpen() ? el.sidebar.getBoundingClientRect().top - container.top : size.y;
            area = { x: 16, y: 76, w: size.x - 32, h: Math.max(120, Math.min(size.y * 0.45, panelTop - 76 - 16)) };
        } else {
            // Ruang di kiri panel samping, di antara kontrol kiri dan tombol atas/bawah.
            const panelLeft = isOpen() ? el.sidebar.getBoundingClientRect().left - container.left : size.x - 70;
            area = { x: 70, y: 76, w: panelLeft - 24 - 70, h: size.y - 76 - 90 };
        }
        const ratio = box.w / box.h;
        let w = area.w;
        let h = w / ratio;
        if (h > area.h) {
            h = area.h;
            w = h * ratio;
        }
        return { x: area.x + (area.w - w) / 2, y: area.y + (area.h - h) / 2, w, h };
    }

    function placeFrame() {
        if (!frameEl) {
            return;
        }
        frameEl.hidden = !isOpen();
        if (!isOpen()) {
            return;
        }
        const rect = frameRect();
        Object.assign(frameEl.style, { left: `${rect.x}px`, top: `${rect.y}px`, width: `${rect.w}px`, height: `${rect.h}px` });
    }

    // ---------------------------------------------------------------------------------------
    // Basemap: tile pada zoom keluaran
    // ---------------------------------------------------------------------------------------

    function basemapInfo() {
        const layer = typeof currentBaseMap !== "undefined" ? currentBaseMap : null;
        if (!layer || !layer._url) {
            return null;
        }
        const config = (mapConfig.baseMapsList || []).find((item) => item.url === layer._url) || {};
        return {
            url: layer._url,
            subdomains: layer.options.subdomains,
            maxNativeZoom: config.maxZoom || layer.options.maxNativeZoom || layer.options.maxZoom || 18,
            minZoom: layer.options.minZoom || 0,
            label: config.label || "Basemap",
        };
    }

    function tileUrl(info, x, y, z) {
        const subdomains = typeof info.subdomains === "string" ? info.subdomains.split("") : info.subdomains || [];
        const s = subdomains.length ? subdomains[Math.abs(x + y) % subdomains.length] : "";
        return L.Util.template(info.url, { s, x, y, z, r: "" });
    }

    function loadImage(url) {
        return new Promise((resolve) => {
            const image = new Image();
            image.crossOrigin = "anonymous";
            image.onload = () => resolve(image);
            // Tile gagal/terblokir CORS dilewati (area itu kosong) daripada membatalkan unduhan.
            image.onerror = () => resolve(null);
            image.src = url;
        });
    }

    /**
     * Gambar tile basemap ke area peta. `view` memuat zoom keluaran (pecahan) dan titik asal piksel.
     */
    async function drawBasemap(ctx, view, box, onProgress) {
        const info = basemapInfo();
        if (!info) {
            return { failed: 0, total: 0, label: null };
        }
        let tileZoom = Math.max(info.minZoom, Math.min(info.maxNativeZoom, Math.round(view.zoom)));
        const tileRange = (zoom) => {
            const scale = Math.pow(2, view.zoom - zoom);
            const size = 256 * scale;
            return {
                scale,
                size,
                x0: Math.floor(view.origin.x / size),
                y0: Math.floor(view.origin.y / size),
                x1: Math.floor((view.origin.x + box.w) / size),
                y1: Math.floor((view.origin.y + box.h) / size),
            };
        };
        let range = tileRange(tileZoom);
        // Terlalu banyak tile (kertas besar, resolusi tinggi): pakai zoom tile lebih rendah lalu diperbesar.
        while ((range.x1 - range.x0 + 1) * (range.y1 - range.y0 + 1) > MAX_TILES && tileZoom > info.minZoom) {
            tileZoom--;
            range = tileRange(tileZoom);
        }

        const count = Math.pow(2, tileZoom);
        const tiles = [];
        for (let y = range.y0; y <= range.y1; y++) {
            for (let x = range.x0; x <= range.x1; x++) {
                if (y >= 0 && y < count) {
                    tiles.push({ x, y });
                }
            }
        }

        let done = 0;
        let failed = 0;
        const queue = [...tiles];
        const worker = async () => {
            while (queue.length) {
                throwIfCancelled();
                const tile = queue.shift();
                const wrappedX = ((tile.x % count) + count) % count;
                const image = await loadImage(tileUrl(info, wrappedX, tile.y, tileZoom));
                throwIfCancelled();
                if (image) {
                    // +1 px menutup celah tipis antar-tile akibat pembulatan.
                    ctx.drawImage(image, box.x + tile.x * range.size - view.origin.x, box.y + tile.y * range.size - view.origin.y, range.size + 1, range.size + 1);
                } else {
                    failed++;
                }
                onProgress(++done / tiles.length);
            }
        };
        await Promise.all(Array.from({ length: Math.min(TILE_CONCURRENCY, tiles.length) }, worker));
        return { failed, total: tiles.length, label: info.label };
    }

    // ---------------------------------------------------------------------------------------
    // Fitur vektor dari layer aktif
    // ---------------------------------------------------------------------------------------

    function collectLeaves(layer, result) {
        if (layer.feature) {
            result.push(layer);
        } else if (typeof layer.getLayers === "function") {
            layer.getLayers().forEach((child) => collectLeaves(child, result));
        }
        return result;
    }

    function visibleEntries() {
        const catalog = window.MarimoiCatalog;
        if (!catalog) {
            return [];
        }
        return catalog.getActiveEntries().filter((entry) => !catalog.isHidden(entry) && map.hasLayer(entry.layerGroup));
    }

    // Karakter ikon Bootstrap Icons dari class-nya (dibaca dari ::before), untuk simbol titik ber-ikon.
    const glyphCache = new Map();
    function iconGlyph(className) {
        if (!glyphCache.has(className)) {
            const probe = document.createElement("i");
            probe.className = className;
            probe.style.cssText = "position:absolute;visibility:hidden;";
            document.body.appendChild(probe);
            const style = getComputedStyle(probe, "::before");
            const content = style.content.replace(/^["']|["']$/g, "");
            glyphCache.set(className, content && content !== "none" ? { char: content, family: style.fontFamily } : null);
            probe.remove();
        }
        return glyphCache.get(className);
    }

    function tracePath(ctx, rings, project, close) {
        rings.forEach((ring) => {
            ring.forEach(([lng, lat], index) => {
                const point = project(lat, lng);
                if (index === 0) {
                    ctx.moveTo(point.x, point.y);
                } else {
                    ctx.lineTo(point.x, point.y);
                }
            });
            if (close) {
                ctx.closePath();
            }
        });
    }

    function drawPath(ctx, leaf, project, scale) {
        const geometry = leaf.feature.geometry;
        const options = leaf.options || {};
        if (!geometry) {
            return;
        }
        const isArea = /Polygon/.test(geometry.type);
        const parts = geometry.type === "Polygon" ? [geometry.coordinates]
            : geometry.type === "MultiPolygon" ? geometry.coordinates
            : geometry.type === "LineString" ? [[geometry.coordinates]]
            : geometry.type === "MultiLineString" ? [geometry.coordinates]
            : [];
        ctx.beginPath();
        parts.forEach((rings) => tracePath(ctx, rings, project, isArea));
        if (isArea && options.fill !== false) {
            ctx.globalAlpha = options.fillOpacity ?? 0.2;
            ctx.fillStyle = options.fillColor || options.color || "#3388ff";
            ctx.fill("evenodd");
        }
        if (options.stroke !== false && (options.weight ?? 3) > 0) {
            ctx.globalAlpha = options.opacity ?? 1;
            ctx.strokeStyle = options.color || "#3388ff";
            ctx.lineWidth = (options.weight ?? 3) * scale;
            ctx.lineJoin = "round";
            ctx.lineCap = "round";
            ctx.setLineDash(options.dashArray ? String(options.dashArray).split(/[ ,]+/).map((part) => Number(part) * scale) : []);
            ctx.stroke();
            ctx.setLineDash([]);
        }
        ctx.globalAlpha = 1;
    }

    function drawPoint(ctx, leaf, project, scale) {
        const latlng = leaf.getLatLng();
        const point = project(latlng.lat, latlng.lng);
        const options = leaf.options || {};
        if (leaf instanceof L.CircleMarker) {
            ctx.beginPath();
            ctx.arc(point.x, point.y, (options.radius || 6) * scale, 0, Math.PI * 2);
            ctx.globalAlpha = options.fillOpacity ?? 0.8;
            ctx.fillStyle = options.fillColor || options.color || "#3388ff";
            ctx.fill();
            ctx.globalAlpha = options.opacity ?? 1;
            ctx.strokeStyle = options.color || "#3388ff";
            ctx.lineWidth = (options.weight ?? 1) * scale;
            ctx.stroke();
            ctx.globalAlpha = 1;
            return;
        }
        // Marker ikon (divIcon berisi <i class="bi ..." style="color;font-size">).
        const html = options.icon?.options?.html || "";
        const className = (html.match(/class="([^"]+)"/) || [])[1];
        const color = (html.match(/color:\s*([^;"]+)/) || [])[1] || "#0a84ff";
        const size = Number((html.match(/font-size:\s*([\d.]+)px/) || [])[1] || 16) * scale;
        const glyph = className ? iconGlyph(className) : null;
        ctx.globalAlpha = options.opacity ?? 1;
        if (glyph) {
            ctx.font = `${size}px ${glyph.family}`;
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.lineWidth = Math.max(1, size * 0.12);
            ctx.strokeStyle = "rgba(255,255,255,.9)";
            ctx.strokeText(glyph.char, point.x, point.y);
            ctx.fillStyle = color;
            ctx.fillText(glyph.char, point.x, point.y);
        } else {
            ctx.beginPath();
            ctx.arc(point.x, point.y, size / 3, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    function drawFeatures(ctx, view, box, scale, withLabels) {
        const crs = map.options.crs;
        const project = (lat, lng) => {
            const point = crs.latLngToPoint(L.latLng(lat, lng), view.zoom);
            return { x: box.x + point.x - view.origin.x, y: box.y + point.y - view.origin.y };
        };
        // Urutan Layer Aktif: indeks 0 paling atas, jadi digambar terakhir. Titik di atas area/garis (seperti di peta).
        const entries = visibleEntries().reverse();
        const points = [];
        entries.forEach((entry) => {
            collectLeaves(entry.layerGroup, []).forEach((leaf) => {
                if (leaf.marimoiFilteredOut) {
                    return;
                }
                if (typeof leaf.getLatLng === "function") {
                    points.push(leaf);
                } else {
                    drawPath(ctx, leaf, project, scale);
                }
            });
        });
        points.forEach((leaf) => drawPoint(ctx, leaf, project, scale));

        // Label fitur yang sedang tampil di peta (map-labels.js), dengan halo putih seperti di layar.
        (withLabels ? window.MarimoiLabels?.getLabels?.() || [] : []).forEach((label) => {
            const point = project(label.latlng.lat, label.latlng.lng);
            const size = 11 * scale;
            ctx.font = `600 ${size}px ${FONT}`;
            ctx.textAlign = "center";
            ctx.textBaseline = label.isPoint ? "top" : "middle";
            const y = label.isPoint ? point.y + 8 * scale : point.y;
            ctx.lineJoin = "round";
            ctx.lineWidth = size * 0.32;
            ctx.strokeStyle = "rgba(255,255,255,.95)";
            ctx.strokeText(label.text, point.x, y);
            ctx.fillStyle = "#0f172a";
            ctx.fillText(label.text, point.x, y);
        });
    }

    // ---------------------------------------------------------------------------------------
    // Elemen kartografis: judul, legenda, skala, arah utara, sumber
    // ---------------------------------------------------------------------------------------

    function niceNumber(value) {
        const power = Math.pow(10, Math.floor(Math.log10(value)));
        const fraction = value / power;
        return (fraction >= 5 ? 5 : fraction >= 2 ? 2 : 1) * power;
    }

    function drawScaleBar(ctx, view, box, px) {
        const center = map.options.crs.pointToLatLng(L.point(view.origin.x + box.w / 2, view.origin.y + box.h / 2), view.zoom);
        const metersPerPixel = (40075016.686 * Math.cos((center.lat * Math.PI) / 180)) / (256 * Math.pow(2, view.zoom));
        const meters = niceNumber(metersPerPixel * box.w * 0.22);
        const barWidth = meters / metersPerPixel;
        const label = meters >= 1000 ? `${(meters / 1000).toLocaleString("id-ID")} km` : `${meters.toLocaleString("id-ID")} m`;
        const x = box.x + px(4);
        const y = box.y + box.h - px(6);
        const height = px(1.6);

        ctx.fillStyle = "rgba(255,255,255,.88)";
        roundRect(ctx, x - px(2), y - px(6.2), barWidth + px(4) + px(2) * 2, px(8.4), px(1.2));
        ctx.fill();
        for (let i = 0; i < 4; i++) {
            ctx.fillStyle = i % 2 ? "#ffffff" : "#1d3557";
            ctx.fillRect(x + (barWidth / 4) * i, y, barWidth / 4, height);
        }
        ctx.strokeStyle = "#1d3557";
        ctx.lineWidth = px(0.25);
        ctx.strokeRect(x, y, barWidth, height);
        ctx.fillStyle = "#1d3557";
        ctx.font = `600 ${px(2.6)}px ${FONT}`;
        ctx.textBaseline = "bottom";
        ctx.textAlign = "left";
        ctx.fillText("0", x, y - px(0.8));
        ctx.textAlign = "right";
        ctx.fillText(label, x + barWidth, y - px(0.8));

        // Skala angka (1 : n) untuk ukuran kertas cetak.
        return Math.round((metersPerPixel * view.dpi) / 0.0254);
    }

    function drawNorthArrow(ctx, box, px) {
        const cx = box.x + box.w - px(9);
        const cy = box.y + px(11);
        const size = px(5);
        ctx.fillStyle = "rgba(255,255,255,.88)";
        ctx.beginPath();
        ctx.arc(cx, cy, size * 1.25, 0, Math.PI * 2);
        ctx.fill();
        ctx.beginPath();
        ctx.moveTo(cx, cy - size);
        ctx.lineTo(cx + size * 0.55, cy + size * 0.7);
        ctx.lineTo(cx, cy + size * 0.35);
        ctx.closePath();
        ctx.fillStyle = "#1d3557";
        ctx.fill();
        ctx.beginPath();
        ctx.moveTo(cx, cy - size);
        ctx.lineTo(cx - size * 0.55, cy + size * 0.7);
        ctx.lineTo(cx, cy + size * 0.35);
        ctx.closePath();
        ctx.fillStyle = "#0a84ff";
        ctx.fill();
        ctx.font = `700 ${px(2.6)}px ${FONT}`;
        ctx.textAlign = "center";
        ctx.textBaseline = "top";
        ctx.fillStyle = "#1d3557";
        ctx.fillText("U", cx, cy + size * 1.35);
    }

    function roundRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    // Potong teks agar muat lebar tertentu (dengan elipsis).
    function fitText(ctx, text, maxWidth) {
        if (ctx.measureText(text).width <= maxWidth) {
            return text;
        }
        let cut = text;
        while (cut.length > 1 && ctx.measureText(`${cut}…`).width > maxWidth) {
            cut = cut.slice(0, -1);
        }
        return `${cut}…`;
    }

    // Simbol legenda: ikon (titik ber-ikon), lingkaran (titik), garis, atau kotak isian (area).
    function drawLegendSymbol(ctx, group, color, x, cy, size) {
        ctx.fillStyle = color;
        ctx.strokeStyle = color;
        const glyph = group.icon ? iconGlyph(group.icon) : null;
        if (glyph) {
            ctx.font = `${size * 1.1}px ${glyph.family}`;
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(glyph.char, x + size / 2, cy);
        } else if (group.kind === "point") {
            ctx.beginPath();
            ctx.arc(x + size / 2, cy, size * 0.36, 0, Math.PI * 2);
            ctx.fill();
        } else if (group.kind === "line") {
            ctx.lineWidth = size * 0.2;
            ctx.lineCap = "round";
            ctx.beginPath();
            ctx.moveTo(x, cy + size * 0.22);
            ctx.lineTo(x + size, cy - size * 0.22);
            ctx.stroke();
        } else {
            const top = cy - size / 2;
            ctx.globalAlpha = Math.min(1, (group.opacity ?? 1) * 0.5);
            ctx.fillRect(x, top, size, size);
            ctx.globalAlpha = 1;
            ctx.lineWidth = size * 0.12;
            ctx.strokeRect(x, top, size, size);
        }
    }

    /**
     * Baris legenda dari legendGroups() (map.js) — sama dengan panel Legenda di layar.
     * Layer berwarna tunggal = satu baris; layer berkelas/berwarna per fitur = judul + sub-baris.
     */
    function legendRows() {
        const groups = typeof legendGroups === "function" ? legendGroups() : [];
        const rows = [];
        groups.forEach((group) => {
            if (!group.items.length) {
                rows.push({ type: "item", group, color: group.color, label: group.title, strong: true });
                return;
            }
            rows.push({ type: "title", label: group.title });
            group.items.forEach((item) => rows.push({ type: "item", group, color: item.color, label: item.label, sub: true }));
        });
        return rows;
    }

    function drawLegend(ctx, box, px) {
        ctx.fillStyle = "#f5f7fa";
        roundRect(ctx, box.x, box.y, box.w, box.h, px(2));
        ctx.fill();
        ctx.strokeStyle = "#e3e8ef";
        ctx.lineWidth = px(0.3);
        ctx.stroke();

        const pad = px(4);
        ctx.fillStyle = "#1d3557";
        ctx.font = `700 ${px(3.6)}px ${FONT}`;
        ctx.textAlign = "left";
        ctx.textBaseline = "top";
        ctx.fillText("Legenda", box.x + pad, box.y + pad);

        const rows = legendRows();
        if (!rows.length) {
            ctx.fillStyle = "#64748b";
            ctx.font = `${px(2.8)}px ${FONT}`;
            ctx.fillText("Tidak ada layer aktif.", box.x + pad, box.y + pad + px(7));
            return;
        }

        // Cari ukuran huruf terbesar & kolom tersedikit yang memuat semua baris.
        const top = box.y + pad + px(8);
        const innerW = box.w - pad * 2;
        const innerH = box.y + box.h - pad - top;
        let fit = null;
        for (const scale of [1, 0.9, 0.8, 0.7, 0.6, 0.5]) {
            const rowHeight = px(5.6) * scale;
            const rowsPerColumn = Math.max(1, Math.floor(innerH / rowHeight));
            for (let columns = 1; columns <= 4; columns++) {
                if (innerW / columns < px(38) * scale) {
                    break;
                }
                fit = { scale, rowHeight, rowsPerColumn, columns };
                if (rowsPerColumn * columns >= rows.length) {
                    break;
                }
            }
            if (fit && fit.rowsPerColumn * fit.columns >= rows.length) {
                break;
            }
        }

        // Ruang sangat sempit: satu kolom dengan huruf terkecil.
        fit = fit || { scale: 0.5, rowHeight: px(2.8), rowsPerColumn: Math.max(1, Math.floor(innerH / px(2.8))), columns: 1 };
        const capacity = fit.rowsPerColumn * fit.columns;
        const overflow = rows.length > capacity;
        const shown = overflow ? rows.slice(0, capacity - 1) : rows;
        const columnWidth = innerW / fit.columns;
        const symbol = px(3.6) * fit.scale;
        const fontSize = px(2.7) * fit.scale;

        shown.forEach((row, index) => {
            const column = Math.floor(index / fit.rowsPerColumn);
            const x = box.x + pad + column * columnWidth;
            const cy = top + (index % fit.rowsPerColumn) * fit.rowHeight + fit.rowHeight / 2;
            ctx.textBaseline = "middle";
            ctx.textAlign = "left";
            if (row.type === "title") {
                ctx.fillStyle = "#1d3557";
                ctx.font = `700 ${fontSize}px ${FONT}`;
                ctx.fillText(fitText(ctx, row.label, columnWidth - px(3)), x, cy);
                return;
            }
            const indent = row.sub ? px(2.5) * fit.scale : 0;
            drawLegendSymbol(ctx, row.group, row.color, x + indent, cy, symbol);
            ctx.fillStyle = row.sub ? "#334155" : "#1f2937";
            ctx.font = `${row.strong ? 600 : 500} ${fontSize}px ${FONT}`;
            ctx.textAlign = "left";
            ctx.textBaseline = "middle";
            const textX = x + indent + symbol + px(2.2) * fit.scale;
            ctx.fillText(fitText(ctx, row.label, columnWidth - (textX - x) - px(3)), textX, cy);
        });

        if (overflow) {
            const index = shown.length;
            ctx.fillStyle = "#64748b";
            ctx.font = `italic ${fontSize}px ${FONT}`;
            ctx.textBaseline = "middle";
            ctx.fillText(`+${rows.length - shown.length} keterangan lainnya`, box.x + pad + Math.floor(index / fit.rowsPerColumn) * columnWidth, top + (index % fit.rowsPerColumn) * fit.rowHeight + fit.rowHeight / 2);
        }
    }

    // ---------------------------------------------------------------------------------------
    // Render halaman
    // ---------------------------------------------------------------------------------------

    function throwIfCancelled() {
        if (job?.cancelled) {
            throw new DOMException("Dibatalkan", "AbortError");
        }
    }

    async function renderPage(settings, onProgress) {
        const page = pageSize(settings);
        const plan = layout(page, settings);
        const pxPerMm = page.dpi / 25.4;
        const px = (mm) => mm * pxPerMm * plan.unit;
        const toPx = (box) => ({ x: box.x * pxPerMm, y: box.y * pxPerMm, w: box.w * pxPerMm, h: box.h * pxPerMm });

        const canvas = document.createElement("canvas");
        canvas.width = page.width;
        canvas.height = page.height;
        const ctx = canvas.getContext("2d");
        if (!ctx) {
            throw new Error("Browser tidak dapat menyiapkan gambar sebesar ini. Pilih kertas lebih kecil atau resolusi lebih rendah.");
        }
        await document.fonts?.ready;

        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, page.width, page.height);

        // Area peta = bingkai pratinjau; zoom keluaran diskalakan dari lebar bingkai di layar.
        const box = toPx(plan.mapBox);
        const frame = frameRect();
        const frameCenter = map.containerPointToLatLng([frame.x + frame.w / 2, frame.y + frame.h / 2]);
        const zoom = map.getZoom() + Math.log2(box.w / frame.w);
        const centerPoint = map.options.crs.latLngToPoint(frameCenter, zoom);
        const view = { zoom, dpi: page.dpi, origin: { x: centerPoint.x - box.w / 2, y: centerPoint.y - box.h / 2 } };
        // Ketebalan garis & ukuran simbol: setara tampilan layar (96 dpi), sedikit diperbesar di kertas besar.
        const symbolScale = (page.dpi / 96) * Math.sqrt(Math.max(1, plan.unit));

        ctx.save();
        ctx.beginPath();
        ctx.rect(box.x, box.y, box.w, box.h);
        ctx.clip();
        ctx.fillStyle = "#e8eef5";
        ctx.fillRect(box.x, box.y, box.w, box.h);
        const tiles = await drawBasemap(ctx, view, box, (ratio) => onProgress(0.05 + ratio * 0.75, "Mengunduh basemap…"));
        throwIfCancelled();
        onProgress(0.82, "Menggambar layer…");
        drawFeatures(ctx, view, box, symbolScale, settings.labels);
        drawNorthArrow(ctx, box, px);
        const ratio = drawScaleBar(ctx, view, box, px);
        ctx.restore();

        ctx.strokeStyle = "#1d3557";
        ctx.lineWidth = px(0.35);
        ctx.strokeRect(box.x, box.y, box.w, box.h);

        // Judul & keterangan.
        const marginPx = plan.margin * pxPerMm;
        ctx.fillStyle = "#1d3557";
        ctx.textAlign = "left";
        ctx.textBaseline = "top";
        ctx.font = `800 ${px(6.4)}px ${FONT}`;
        ctx.fillText(fitText(ctx, settings.title, page.width - marginPx * 2), marginPx, marginPx);
        ctx.fillStyle = "#64748b";
        ctx.font = `${px(3)}px ${FONT}`;
        const printed = new Date().toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
        ctx.fillText(`Provinsi Maluku Utara · Skala ±1 : ${Number(ratio.toPrecision(2)).toLocaleString("id-ID")} (pada kertas ${settings.paper}) · ${printed}`, marginPx, marginPx + px(8.2));

        if (plan.legendBox) {
            drawLegend(ctx, toPx(plan.legendBox), px);
        }

        // Sumber & atribusi di kaki halaman.
        ctx.fillStyle = "#64748b";
        ctx.font = `${px(2.5)}px ${FONT}`;
        ctx.textBaseline = "bottom";
        const footerY = page.height - marginPx;
        const host = window.location.host;
        const sourceWidth = page.width - marginPx * 2 - ctx.measureText(host).width - px(6);
        const source = `Sumber data: MARIMOI — Bappeda Provinsi Maluku Utara · Basemap: ${tiles.label || "-"} · WGS 84 / Web Mercator`;
        ctx.fillText(fitText(ctx, source, sourceWidth), marginPx, footerY);
        ctx.textAlign = "right";
        ctx.fillText(host, page.width - marginPx, footerY);

        onProgress(0.9, settings.format === "pdf" ? "Menyusun PDF…" : "Menyimpan PNG…");
        return { canvas, page, tiles };
    }

    // ---------------------------------------------------------------------------------------
    // Berkas keluaran
    // ---------------------------------------------------------------------------------------

    const canvasBlob = (canvas, type, quality) => new Promise((resolve, reject) => {
        canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error("Gagal membuat gambar."))), type, quality);
    });

    /**
     * PDF satu halaman berisi satu gambar JPEG (DCTDecode) seukuran kertas.
     */
    function buildPdf(jpeg, imageWidth, imageHeight, widthMm, heightMm, title) {
        const encoder = new TextEncoder();
        const chunks = [];
        const offsets = [];
        let length = 0;
        const push = (data) => {
            const bytes = typeof data === "string" ? encoder.encode(data) : data;
            chunks.push(bytes);
            length += bytes.length;
        };
        const object = (number, write) => {
            offsets[number] = length;
            push(`${number} 0 obj\n`);
            write();
            push("\nendobj\n");
        };
        const pt = (mm) => ((mm * 72) / 25.4).toFixed(2);
        const width = pt(widthMm);
        const height = pt(heightMm);
        const content = `q ${width} 0 0 ${height} 0 0 cm /Im0 Do Q`;
        const pdfText = (text) => `(${text.replace(/[\\()]/g, "\\$&").replace(/[^\x20-\x7e]/g, "")})`;

        push("%PDF-1.4\n");
        object(1, () => push("<< /Type /Catalog /Pages 2 0 R >>"));
        object(2, () => push("<< /Type /Pages /Kids [3 0 R] /Count 1 >>"));
        object(3, () => push(`<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${width} ${height}] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>`));
        object(4, () => {
            push(`<< /Type /XObject /Subtype /Image /Width ${imageWidth} /Height ${imageHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${jpeg.length} >>\nstream\n`);
            push(jpeg);
            push("\nendstream");
        });
        object(5, () => push(`<< /Length ${content.length} >>\nstream\n${content}\nendstream`));
        object(6, () => push(`<< /Title ${pdfText(title)} /Producer (MARIMOI) /Creator (MARIMOI Peta Interaktif) >>`));

        const xref = length;
        push(`xref\n0 7\n0000000000 65535 f \n${offsets.slice(1).map((offset) => `${String(offset).padStart(10, "0")} 00000 n \n`).join("")}`);
        push(`trailer\n<< /Size 7 /Root 1 0 R /Info 6 0 R >>\nstartxref\n${xref}\n%%EOF`);
        return new Blob(chunks, { type: "application/pdf" });
    }

    function saveBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 10000);
    }

    function fileName(settings) {
        const slug = settings.title.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "") || "peta-marimoi";
        const date = new Date().toISOString().slice(0, 10);
        return `${slug}-${settings.paper}-${settings.orientation === "landscape" ? "lanskap" : "potret"}-${date}.${settings.format}`;
    }

    // ---------------------------------------------------------------------------------------
    // Alur unduh & panel
    // ---------------------------------------------------------------------------------------

    function setBusy(busy) {
        el.submit.disabled = busy;
        el.submit.hidden = busy;
        el.cancel.hidden = !busy;
        el.progress.hidden = !busy;
        el.form.querySelectorAll("input, select").forEach((input) => { input.disabled = busy; });
    }

    function showProgress(ratio, message) {
        el.progressBar.style.width = `${Math.round(ratio * 100)}%`;
        el.progressText.textContent = message;
    }

    function showStatus(message, type = "info") {
        el.status.hidden = !message;
        el.status.className = `download-status is-${type}`;
        el.status.innerHTML = message;
    }

    async function download(event) {
        event.preventDefault();
        if (job) {
            return;
        }
        const settings = readSettings();
        job = { cancelled: false };
        setBusy(true);
        showStatus("");
        showProgress(0.02, "Menyiapkan halaman…");

        try {
            const { canvas, page, tiles } = await renderPage(settings, showProgress);
            throwIfCancelled();
            let blob;
            if (settings.format === "pdf") {
                const jpeg = new Uint8Array(await (await canvasBlob(canvas, "image/jpeg", 0.92)).arrayBuffer());
                blob = buildPdf(jpeg, canvas.width, canvas.height, page.widthMm, page.heightMm, settings.title);
            } else {
                blob = await canvasBlob(canvas, "image/png");
            }
            throwIfCancelled();
            saveBlob(blob, fileName(settings));
            const size = blob.size >= 1048576 ? `${(blob.size / 1048576).toLocaleString("id-ID", { maximumFractionDigits: 1 })} MB` : `${Math.round(blob.size / 1024)} KB`;
            const warning = tiles.failed ? `<br><i class="bi bi-exclamation-triangle"></i> ${tiles.failed} dari ${tiles.total} tile basemap gagal dimuat; coba basemap lain bila ada area kosong.` : "";
            showStatus(`<i class="bi bi-check-circle-fill"></i> Peta berhasil diunduh (${settings.format.toUpperCase()}, ${size}).${warning}`, tiles.failed ? "warn" : "success");
        } catch (error) {
            if (error.name === "AbortError") {
                showStatus("Unduhan dibatalkan.", "info");
            } else {
                // Canvas "tainted": basemap tidak mengizinkan disalin (CORS).
                const message = error.name === "SecurityError"
                    ? "Basemap ini tidak mengizinkan peta disalin. Ganti basemap (mis. OpenStreetMap atau ESRI) lalu coba lagi."
                    : error.message || "Terjadi kesalahan saat membuat berkas.";
                showStatus(`<i class="bi bi-x-circle-fill"></i> ${message}`, "error");
            }
        } finally {
            job = null;
            setBusy(false);
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        el.sidebar = document.getElementById("sidebar-download");
        el.form = document.getElementById("download-form");
        if (!el.sidebar || !el.form || typeof map === "undefined") {
            return;
        }
        el.summary = el.sidebar.querySelector("[data-download-summary]");
        el.submit = el.sidebar.querySelector("[data-download-submit]");
        el.cancel = el.sidebar.querySelector("[data-download-cancel]");
        el.progress = el.sidebar.querySelector("[data-download-progress]");
        el.progressBar = el.progress.querySelector("span");
        el.progressText = el.sidebar.querySelector("[data-download-progress-text]");
        el.status = el.sidebar.querySelector("[data-download-status]");

        // Bingkai pratinjau: area di luar bingkai diredupkan, tidak menghalangi geser/zoom peta.
        frameEl = document.createElement("div");
        frameEl.className = "download-frame";
        frameEl.hidden = true;
        frameEl.innerHTML = '<span class="download-frame-label"><i class="bi bi-printer"></i> Area cetak</span>';
        map.getContainer().appendChild(frameEl);

        el.form.addEventListener("submit", download);
        el.form.addEventListener("change", describeOutput);
        el.cancel.addEventListener("click", () => { if (job) job.cancelled = true; });
        new MutationObserver(placeFrame).observe(el.sidebar, { attributes: true, attributeFilter: ["class"] });
        map.on("resize", placeFrame);
        window.addEventListener("resize", placeFrame);
        describeOutput();
    });

    window.MarimoiDownload = { pageSize, layout, buildPdf };
})();
