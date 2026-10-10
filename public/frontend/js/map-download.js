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
    // Kop & footer dokumen resmi memakai Arial.
    const KOP_FONT = "Arial, Helvetica, sans-serif";
    const PT_TO_MM = 25.4 / 72;
    // Spasi antar baris kop & footer.
    const LINE_HEIGHT = 1.15;

    // Canvas kecil untuk mengukur & membungkus teks saat menghitung tata letak (satuan mm).
    const measureCtx = document.createElement("canvas").getContext("2d");
    const MEASURE_SCALE = 10;

    /**
     * Bungkus teks ke beberapa baris selebar maxWidthMm (baris baru dari Enter dipertahankan).
     */
    function wrapText(text, sizeMm, weight, maxWidthMm) {
        measureCtx.font = `${weight} ${sizeMm * MEASURE_SCALE}px ${KOP_FONT}`;
        const limit = maxWidthMm * MEASURE_SCALE;
        const lines = [];
        String(text || "").split("\n").forEach((paragraph) => {
            const words = paragraph.trim().split(/\s+/).filter(Boolean);
            let line = "";
            words.forEach((word) => {
                const candidate = line ? `${line} ${word}` : word;
                if (line && measureCtx.measureText(candidate).width > limit) {
                    lines.push(line);
                    line = word;
                } else {
                    line = candidate;
                }
            });
            if (line) {
                lines.push(line);
            }
        });
        return lines;
    }

    /**
     * Rencana kop: baris 1 Arial 12pt kapital, baris 2 Arial 14pt tebal kapital,
     * baris 3 Arial 10pt sesuai isian (boleh beberapa baris). Ukuran pt berlaku di A4 dan
     * ikut membesar sebanding di kertas lebih besar.
     */
    function planKop(template, widthMm, unit) {
        const header = template.header;
        const hasLogo = Boolean(header.logoLeft || header.logoRight);
        const logoSlot = hasLogo ? 26 * unit : 0;
        const textWidth = widthMm - logoSlot * 2 - (hasLogo ? 6 * unit : 0);
        const specs = [
            [header.line1, 12, 400, "#0f172a"],
            [header.line2, 14, 700, "#0f172a"],
            [header.line3, 10, 400, "#334155"],
        ];
        const rows = [];
        specs.forEach(([text, pt, weight, color]) => {
            if (!text) {
                return;
            }
            const size = pt * PT_TO_MM * unit;
            wrapText(text, size, weight, textWidth).forEach((line) => rows.push({ text: line, size, weight, color }));
        });
        const textHeight = rows.reduce((sum, row) => sum + row.size * LINE_HEIGHT, 0);
        const logoMinHeight = hasLogo ? 22 * unit : 0;
        const ruleSpace = 5 * unit;
        return { unit, rows, textHeight, logoSlot, logoMinHeight, ruleSpace, height: Math.max(textHeight, logoMinHeight) + ruleSpace };
    }

    // Waktu cetak dalam WIT (zona waktu Maluku Utara).
    function printedAt() {
        const now = new Date();
        const date = now.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric", timeZone: "Asia/Jayapura" });
        const time = now.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit", timeZone: "Asia/Jayapura" });
        return `Dicetak ${date}, ${time} WIT`;
    }

    /**
     * Rencana footer Arial 9pt: kiri teks template (bisa beberapa baris), kanan otomatis.
     */
    function planFooter(settings, widthMm, unit) {
        const size = 9 * PT_TO_MM * unit;
        const leftText = settings.template ? settings.template.footer.text : "Sumber data: MARIMOI — Bappeda Provinsi Maluku Utara";
        const basemap = basemapInfo()?.label || "-";
        const left = wrapText(leftText, size, 400, widthMm * 0.58);
        const right = [`Basemap: ${basemap} · WGS 84 / Web Mercator`, printedAt()].flatMap((line) => wrapText(line, size, 400, widthMm * 0.4));
        const gapTop = 5 * unit;
        return { size, left, right, gapTop, height: gapTop + Math.max(left.length, right.length, 1) * size * LINE_HEIGHT };
    }

    const el = {};
    let frameEl = null;
    let job = null;

    // Template dokumen aktif dari dashboard (Template Dokumen) yang boleh dipakai Unduh Peta.
    const TEMPLATES = (window.MARIMOI_DOCUMENT_TEMPLATES || []).filter((template) => template.forMap);

    // ---------------------------------------------------------------------------------------
    // Pengaturan & ukuran keluaran
    // ---------------------------------------------------------------------------------------

    function readFormSettings() {
        const form = el.form;
        return {
            title: form.elements.title.value.trim() || "Peta Interaktif MARIMOI",
            paper: form.elements.paper.value,
            orientation: form.elements.orientation.value,
            resolution: form.elements.resolution.value,
            format: form.elements.format.value,
            legend: form.elements.legend.checked,
            labels: form.elements.labels.checked,
            template: null,
        };
    }

    // Template menentukan orientasi & elemen tata letak; pilihan manual hanya bila tanpa template.
    function readSettings() {
        const settings = readFormSettings();
        const template = TEMPLATES.find((item) => String(item.id) === el.form.elements.template?.value) || null;
        if (template) {
            settings.template = template;
            settings.orientation = template.orientation;
            settings.legend = Boolean(template.layout.legend?.enabled);
        }
        return settings;
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
        // Margin atas & bawah lebih lega agar kop dan footer tidak menempel ke tepi kertas.
        const marginTop = 13 * unit;
        const marginBottom = 13 * unit;
        const contentWidth = page.widthMm - margin * 2;
        // Kop (logo + baris instansi + garis) dan footer: tingginya mengikuti jumlah baris teks.
        const kopPlan = settings.template?.header ? planKop(settings.template, contentWidth, unit) : null;
        const kop = kopPlan ? kopPlan.height : 0;
        const header = kop + 18 * unit;
        const footerPlan = planFooter(settings, contentWidth, unit);
        const footer = footerPlan.height;
        const gap = 5 * unit;
        const content = { x: margin, y: marginTop + header, w: contentWidth, h: page.heightMm - marginTop - marginBottom - header - footer };

        // Template: setiap elemen ditempatkan sesuai persen area isi (diatur di dashboard).
        if (settings.template) {
            const boxes = settings.template.layout;
            const place = (key) => {
                const box = boxes[key];
                return box?.enabled ? { x: content.x + (box.x / 100) * content.w, y: content.y + (box.y / 100) * content.h, w: (box.w / 100) * content.w, h: (box.h / 100) * content.h } : null;
            };
            return { unit, margin, marginTop, marginBottom, kop, kopPlan, header, footer, footerPlan, mapBox: place("map"), legendBox: place("legend"), insetBox: place("inset"), scaleBox: place("scale"), northBox: place("north") };
        }

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
        // Tanpa template: skala di kiri bawah dan arah utara di kanan atas di dalam peta.
        const scaleBox = { x: mapBox.x + 2 * unit, y: mapBox.y + mapBox.h - 12 * unit, w: Math.min(mapBox.w * 0.3, 60 * unit), h: 10 * unit };
        const northBox = { x: mapBox.x + mapBox.w - 17 * unit, y: mapBox.y + 3 * unit, w: 14 * unit, h: 16 * unit };
        return { unit, margin, marginTop, marginBottom, kop, kopPlan, header, footer, footerPlan, mapBox, legendBox, insetBox: null, scaleBox, northBox };
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

    // Meter per piksel keluaran di titik tengah peta utama.
    function metersPerPixelAt(view, mapBox) {
        const center = map.options.crs.pointToLatLng(L.point(view.origin.x + mapBox.w / 2, view.origin.y + mapBox.h / 2), view.zoom);
        return (40075016.686 * Math.cos((center.lat * Math.PI) / 180)) / (256 * Math.pow(2, view.zoom));
    }

    /**
     * Skala batang di dalam kotaknya (panjang batang menyesuaikan lebar kotak).
     */
    function drawScaleBar(ctx, metersPerPixel, box, px) {
        const pad = Math.min(px(2), box.w * 0.08);
        const fontSize = Math.min(px(2.6), box.h * 0.3);
        const meters = niceNumber(metersPerPixel * (box.w - pad * 2) * 0.95);
        const barWidth = meters / metersPerPixel;
        const label = meters >= 1000 ? `${(meters / 1000).toLocaleString("id-ID")} km` : `${meters.toLocaleString("id-ID")} m`;
        const height = Math.min(px(1.6), box.h * 0.18);
        const x = box.x + (box.w - barWidth) / 2;
        const y = box.y + box.h - pad - height;

        ctx.fillStyle = "rgba(255,255,255,.88)";
        roundRect(ctx, box.x, box.y, box.w, box.h, Math.min(px(1.2), box.h * 0.2));
        ctx.fill();
        for (let i = 0; i < 4; i++) {
            ctx.fillStyle = i % 2 ? "#ffffff" : "#1d3557";
            ctx.fillRect(x + (barWidth / 4) * i, y, barWidth / 4, height);
        }
        ctx.strokeStyle = "#1d3557";
        ctx.lineWidth = px(0.25);
        ctx.strokeRect(x, y, barWidth, height);
        ctx.fillStyle = "#1d3557";
        ctx.font = `600 ${fontSize}px ${FONT}`;
        ctx.textBaseline = "bottom";
        ctx.textAlign = "left";
        ctx.fillText("0", x, y - px(0.8));
        ctx.textAlign = "right";
        ctx.fillText(label, x + barWidth, y - px(0.8));
    }

    // Arah mata angin di tengah kotaknya, ukurannya mengikuti sisi terpendek kotak.
    function drawNorthArrow(ctx, box, px) {
        const size = Math.min(box.w, box.h * 0.82) * 0.32;
        const cx = box.x + box.w / 2;
        const cy = box.y + box.h * 0.42;
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
        ctx.font = `700 ${Math.max(size * 0.5, px(1.8))}px ${FONT}`;
        ctx.textAlign = "center";
        ctx.textBaseline = "top";
        ctx.fillStyle = "#1d3557";
        ctx.fillText("U", cx, cy + size * 1.35);
    }

    /**
     * Inset (peta lokasi): seluruh Provinsi Maluku Utara (diperluas bila perlu) dengan
     * kotak merah penanda area peta utama.
     */
    async function drawInset(ctx, view, mapBox, box, px) {
        const crs = map.options.crs;
        const mainBounds = L.latLngBounds(
            crs.pointToLatLng(L.point(view.origin.x, view.origin.y + mapBox.h), view.zoom),
            crs.pointToLatLng(L.point(view.origin.x + mapBox.w, view.origin.y), view.zoom)
        );
        const extent = L.latLngBounds([[-2.6, 124.2], [2.8, 129.7]]).extend(mainBounds);
        const nw = crs.latLngToPoint(extent.getNorthWest(), 0);
        const se = crs.latLngToPoint(extent.getSouthEast(), 0);
        const zoom = Math.log2(Math.min(box.w / (se.x - nw.x), box.h / (se.y - nw.y)) * 0.94);
        const center = crs.latLngToPoint(extent.getCenter(), zoom);
        const insetView = { zoom, dpi: view.dpi, origin: { x: center.x - box.w / 2, y: center.y - box.h / 2 } };

        ctx.save();
        ctx.beginPath();
        ctx.rect(box.x, box.y, box.w, box.h);
        ctx.clip();
        ctx.fillStyle = "#e8eef5";
        ctx.fillRect(box.x, box.y, box.w, box.h);
        await drawBasemap(ctx, insetView, box, () => {});

        const project = (latlng) => {
            const point = crs.latLngToPoint(latlng, zoom);
            return { x: box.x + point.x - insetView.origin.x, y: box.y + point.y - insetView.origin.y };
        };
        const a = project(mainBounds.getNorthWest());
        const b = project(mainBounds.getSouthEast());
        const width = Math.max(b.x - a.x, px(1.2));
        const height = Math.max(b.y - a.y, px(1.2));
        ctx.fillStyle = "rgba(239, 68, 68, .15)";
        ctx.fillRect(a.x, a.y, width, height);
        ctx.strokeStyle = "#ef4444";
        ctx.lineWidth = px(0.5);
        ctx.strokeRect(a.x, a.y, width, height);

        const fontSize = Math.min(px(2.4), box.h * 0.09);
        ctx.font = `700 ${fontSize}px ${FONT}`;
        ctx.textAlign = "left";
        ctx.textBaseline = "top";
        const labelWidth = ctx.measureText("Peta Lokasi").width + px(2.4);
        ctx.fillStyle = "rgba(255,255,255,.9)";
        ctx.fillRect(box.x, box.y, labelWidth, fontSize + px(1.6));
        ctx.fillStyle = "#1d3557";
        ctx.fillText("Peta Lokasi", box.x + px(1.2), box.y + px(0.8));
        ctx.restore();

        ctx.strokeStyle = "#1d3557";
        ctx.lineWidth = px(0.3);
        ctx.strokeRect(box.x, box.y, box.w, box.h);
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

    /**
     * Kop surat template: logo kiri & kanan, baris instansi di tengah, garis aksen di bawahnya.
     */
    async function drawKop(ctx, template, plan, box, pxPerMm) {
        const [left, right] = await Promise.all([template.header.logoLeft, template.header.logoRight].map((url) => (url ? loadImage(url) : null)));
        const logoArea = { y: box.y, h: plan.textHeight * pxPerMm };
        const logoMax = plan.logoSlot * pxPerMm;
        const drawLogo = (image, alignRight) => {
            if (!image) {
                return;
            }
            const scale = Math.min(logoMax / image.naturalWidth, Math.max(logoArea.h, plan.logoMinHeight * pxPerMm) / image.naturalHeight);
            const width = image.naturalWidth * scale;
            const height = image.naturalHeight * scale;
            const x = alignRight ? box.x + box.w - (logoMax + width) / 2 : box.x + (logoMax - width) / 2;
            ctx.drawImage(image, x, logoArea.y + (Math.max(logoArea.h, height) - height) / 2, width, height);
        };
        drawLogo(left, false);
        drawLogo(right, true);

        // Baris kop dipusatkan di antara slot logo (Arial; ukuran pt sudah dihitung planKop).
        let y = box.y + Math.max(0, (plan.logoMinHeight - plan.textHeight) / 2) * pxPerMm;
        ctx.textAlign = "center";
        ctx.textBaseline = "top";
        plan.rows.forEach((row) => {
            ctx.fillStyle = row.color;
            ctx.font = `${row.weight} ${row.size * pxPerMm}px ${KOP_FONT}`;
            ctx.fillText(row.text, box.x + box.w / 2, y);
            y += row.size * LINE_HEIGHT * pxPerMm;
        });

        const ruleY = box.y + (plan.height - plan.ruleSpace) * pxPerMm;
        ctx.fillStyle = template.accentColor || "#1d3557";
        ctx.fillRect(box.x, ruleY, box.w, 0.9 * plan.unit * pxPerMm);
        ctx.fillRect(box.x, ruleY + 1.4 * plan.unit * pxPerMm, box.w, 0.3 * plan.unit * pxPerMm);
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
        ctx.restore();

        ctx.strokeStyle = "#1d3557";
        ctx.lineWidth = px(0.35);
        ctx.strokeRect(box.x, box.y, box.w, box.h);

        const metersPerPixel = metersPerPixelAt(view, box);
        // Skala angka (1 : n) untuk ukuran kertas cetak.
        const ratio = Math.round((metersPerPixel * view.dpi) / 0.0254);
        if (plan.insetBox) {
            onProgress(0.86, "Menggambar inset…");
            await drawInset(ctx, view, box, toPx(plan.insetBox), px);
            throwIfCancelled();
        }

        // Kop template, lalu judul & keterangan.
        const marginPx = plan.margin * pxPerMm;
        const marginTopPx = plan.marginTop * pxPerMm;
        const template = settings.template;
        const accent = template?.accentColor || "#1d3557";
        if (plan.kopPlan) {
            await drawKop(ctx, template, plan.kopPlan, { x: marginPx, y: marginTopPx, w: page.width - marginPx * 2, h: plan.kop * pxPerMm }, pxPerMm);
        }
        const titleY = marginTopPx + plan.kop * pxPerMm;
        ctx.fillStyle = accent;
        ctx.textAlign = "left";
        ctx.textBaseline = "top";
        ctx.font = `800 ${px(6.4)}px ${FONT}`;
        ctx.fillText(fitText(ctx, settings.title, page.width - marginPx * 2), marginPx, titleY);
        ctx.fillStyle = "#64748b";
        ctx.font = `${px(3)}px ${FONT}`;
        const subtitle = ["Provinsi Maluku Utara", `Skala ±1 : ${Number(ratio.toPrecision(2)).toLocaleString("id-ID")} (pada kertas ${settings.paper})`].join(" · ");
        ctx.fillText(subtitle, marginPx, titleY + px(8.2));

        if (plan.legendBox) {
            drawLegend(ctx, toPx(plan.legendBox), px);
        }
        // Skala & arah mata angin terakhir agar tetap di atas peta/inset bila bertumpuk.
        if (plan.scaleBox) {
            drawScaleBar(ctx, metersPerPixel, toPx(plan.scaleBox), px);
        }
        if (plan.northBox) {
            drawNorthArrow(ctx, toPx(plan.northBox), px);
        }

        // Footer (Arial 9pt): kiri = teks template, kanan = otomatis (basemap, sistem koordinat, waktu cetak).
        const footerPlan = plan.footerPlan;
        const footerTop = page.height - plan.marginBottom * pxPerMm - (footerPlan.height - footerPlan.gapTop) * pxPerMm;
        ctx.strokeStyle = "#e2e8f0";
        ctx.lineWidth = 0.3 * plan.unit * pxPerMm;
        ctx.beginPath();
        ctx.moveTo(marginPx, footerTop - footerPlan.gapTop * 0.45 * pxPerMm);
        ctx.lineTo(page.width - marginPx, footerTop - footerPlan.gapTop * 0.45 * pxPerMm);
        ctx.stroke();
        ctx.fillStyle = "#475569";
        ctx.font = `${footerPlan.size * pxPerMm}px ${KOP_FONT}`;
        ctx.textBaseline = "top";
        [["left", footerPlan.left, marginPx], ["right", footerPlan.right, page.width - marginPx]].forEach(([align, lines, x]) => {
            ctx.textAlign = align;
            lines.forEach((line, index) => ctx.fillText(line, x, footerTop + index * footerPlan.size * LINE_HEIGHT * pxPerMm));
        });

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
     * PDF berisi satu gambar JPEG (DCTDecode) seukuran kertas per halaman.
     * @param {Array<{jpeg: Uint8Array, width: number, height: number}>} pages
     */
    function buildPdf(pages, widthMm, heightMm, title) {
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
        // Objek: 1 katalog, 2 daftar halaman, 3 info; tiap halaman = page, gambar, isi (3 objek).
        const pageObject = (index) => 4 + index * 3;
        const total = 4 + pages.length * 3;

        push("%PDF-1.4\n");
        object(1, () => push("<< /Type /Catalog /Pages 2 0 R >>"));
        object(2, () => push(`<< /Type /Pages /Kids [${pages.map((_, index) => `${pageObject(index)} 0 R`).join(" ")}] /Count ${pages.length} >>`));
        object(3, () => push(`<< /Title ${pdfText(title)} /Producer (MARIMOI) /Creator (MARIMOI Peta Interaktif) >>`));
        pages.forEach((page, index) => {
            const number = pageObject(index);
            object(number, () => push(`<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${width} ${height}] /Resources << /XObject << /Im0 ${number + 1} 0 R >> >> /Contents ${number + 2} 0 R >>`));
            object(number + 1, () => {
                push(`<< /Type /XObject /Subtype /Image /Width ${page.width} /Height ${page.height} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${page.jpeg.length} >>\nstream\n`);
                push(page.jpeg);
                push("\nendstream");
            });
            object(number + 2, () => push(`<< /Length ${content.length} >>\nstream\n${content}\nendstream`));
        });

        const xref = length;
        push(`xref\n0 ${total}\n0000000000 65535 f \n${offsets.slice(1).map((offset) => `${String(offset).padStart(10, "0")} 00000 n \n`).join("")}`);
        push(`trailer\n<< /Size ${total} /Root 1 0 R /Info 3 0 R >>\nstartxref\n${xref}\n%%EOF`);
        return new Blob(chunks, { type: "application/pdf" });
    }

    // Canvas → byte JPEG untuk satu halaman PDF.
    async function canvasJpeg(canvas) {
        const blob = await canvasBlob(canvas, "image/jpeg", 0.92);
        return { jpeg: new Uint8Array(await blob.arrayBuffer()), width: canvas.width, height: canvas.height };
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
    // Pilihan template & pratinjaunya
    // ---------------------------------------------------------------------------------------

    const escapeText = (value) => String(value ?? "").replace(/[&<>"]/g, (char) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[char]));
    const PREVIEW_LABELS = { map: "Peta", legend: "Legenda", inset: "Inset", scale: "Skala", north: "U" };

    // Miniatur halaman: kop, judul, kotak elemen sesuai tata letak, dan footer.
    function templatePreview(template) {
        const boxes = Object.entries(template.layout).filter(([, box]) => box.enabled).map(([key, box]) =>
            `<span class="download-preview-box is-${key}" style="left:${box.x}%;top:${box.y}%;width:${box.w}%;height:${box.h}%">${PREVIEW_LABELS[key]}</span>`).join("");
        const kop = template.header
            ? `<div class="download-preview-kop" style="border-color:${escapeText(template.accentColor)}">
                    ${template.header.logoLeft ? `<img src="${escapeText(template.header.logoLeft)}" alt="">` : "<i></i>"}
                    <span><b class="is-line-1">${escapeText(template.header.line1 || "")}</b><b class="is-line-2">${escapeText(template.header.line2 || "")}</b><span class="is-line-3">${escapeText(template.header.line3 || "")}</span></span>
                    ${template.header.logoRight ? `<img src="${escapeText(template.header.logoRight)}" alt="">` : "<i></i>"}
                </div>`
            : "";
        return `<div class="download-preview-page is-${template.orientation}">
                ${kop}
                <div class="download-preview-title" style="background:${escapeText(template.accentColor)}"></div>
                <div class="download-preview-content">${boxes}</div>
                <div class="download-preview-footer"></div>
            </div>
            <p class="download-preview-meta"><b>${escapeText(template.name)}</b> · ${template.orientation === "portrait" ? "Potret" : "Lanskap"}${template.description ? `<br>${escapeText(template.description)}` : ""}</p>`;
    }

    const templatesFor = (orientation) => TEMPLATES.filter((template) => template.orientation === orientation);

    /**
     * Alur pilihan: orientasi dulu, lalu template berorientasi sama. Template dari dashboard wajib
     * dipakai bila ada; satu template untuk orientasi itu langsung terpilih tanpa perlu memilih.
     */
    function setupTemplates() {
        const select = el.form.elements.template;
        el.templatePreview = el.sidebar.querySelector("[data-template-preview]");
        if (!select || !TEMPLATES.length) {
            return;
        }
        select.closest(".download-field").hidden = false;
        // Orientasi awal = orientasi template bawaan (urutan pertama).
        const initial = TEMPLATES[0].orientation;
        el.form.querySelector(`input[name="orientation"][value="${initial}"]`).checked = true;
        populateTemplates(initial);
        syncTemplateControls();
    }

    function populateTemplates(orientation) {
        const select = el.form.elements.template;
        const list = templatesFor(orientation);
        const keep = list.some((template) => String(template.id) === select.value) ? select.value : null;
        select.innerHTML = list.map((template, index) => {
            const selected = keep ? String(template.id) === keep : template.isDefault || index === 0;
            return `<option value="${template.id}"${selected ? " selected" : ""}>${escapeText(template.name)}</option>`;
        }).join("");
        select.hidden = list.length <= 1;
        el.form.querySelector("[data-template-single]").hidden = list.length !== 1;
    }

    // Orientasi tanpa template dinonaktifkan; legenda mengikuti template; tampilkan pratinjau.
    function syncTemplateControls() {
        const template = readSettings().template;
        if (TEMPLATES.length) {
            el.form.querySelectorAll('input[name="orientation"]').forEach((radio) => {
                const available = templatesFor(radio.value).length > 0;
                radio.disabled = !available;
                radio.closest("label").title = available ? "" : "Belum ada template untuk orientasi ini";
            });
        }
        el.form.querySelector("[data-download-legend]").hidden = Boolean(template);
        if (el.templatePreview) {
            el.templatePreview.hidden = !template;
            el.templatePreview.innerHTML = template ? templatePreview(template) : "";
        }
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
        if (!busy) {
            // Pulihkan orientasi yang memang tidak punya template.
            syncTemplateControls();
        }
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
                blob = buildPdf([await canvasJpeg(canvas)], page.widthMm, page.heightMm, settings.title);
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

        setupTemplates();

        el.form.addEventListener("submit", download);
        el.form.addEventListener("change", (event) => {
            if (event.target.name === "orientation" && TEMPLATES.length) {
                populateTemplates(event.target.value);
            }
            syncTemplateControls();
            describeOutput();
        });
        el.cancel.addEventListener("click", () => { if (job) job.cancelled = true; });
        new MutationObserver(placeFrame).observe(el.sidebar, { attributes: true, attributeFilter: ["class"] });
        map.on("resize", placeFrame);
        window.addEventListener("resize", placeFrame);
        describeOutput();
    });

    // Dipakai bersama Unduh Analisis (map-analysis.js): kop, footer, teks, dan penyusun PDF yang sama.
    window.MarimoiDownload = { pageSize, layout, buildPdf, canvasJpeg, saveBlob, loadImage, planKop, drawKop, wrapText, printedAt, KOP_FONT, PT_TO_MM, LINE_HEIGHT };
})();
