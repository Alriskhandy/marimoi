/**
 * map-catalog.js — Katalog Peta (modal pemilih mapset) & sidebar Layer Aktif.
 *
 * File ini satu-satunya pemegang state layer aktif. Setiap mapset (layer group di
 * `layerGroups[kategori][sub kategori][mapset]` milik map.js) diaktifkan/dinonaktifkan
 * lewat API window.MarimoiCatalog di bawah. Pilihan di modal katalog disusun dulu
 * (staged) lalu diterapkan sekaligus dengan "Terapkan Pilihan".
 *
 * Markup yang dibuat di sini memakai kelas CSS dari resources/css/peta.css, bukan
 * utility Tailwind, karena file di public/ tidak dipindai Tailwind.
 */
(function () {
    // Proses seret urutan Layer Aktif yang sedang berjalan (lihat onDragStart).
    let drag = null;

    const state = {
        entries: [],
        entryByKey: new Map(),
        groups: [],
        activeKeys: [],
        hiddenKeys: new Set(),
        // Pemuatan per layer: key → { status: "queued" | "loading", loaded, controller }.
        loads: new Map(),
        // Layer yang gagal dimuat: key → pesan galat (ditampilkan di baris + tombol Coba lagi).
        errors: new Map(),
        queue: [],
        running: 0,
        staged: new Set(),
        currentGroup: null,
        currentSub: null,
        expandedGroups: new Set(),
        searchTerm: "",
        view: "grid",
        isApplying: false,
        // Filter yang sudah diterapkan ke peta vs. yang sedang disusun di modal.
        appliedFilters: { kabupaten: "", tahun: "", opd_pengelola: "" },
        filterMatches: null,
        filterRequest: 0,
    };

    const el = {};

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function formatCount(value) {
        return Number(value || 0).toLocaleString("en-US");
    }

    // Kunci unik mapset: nama bisa kembar antar kategori, jadi jalurnya ikut digabung.
    function entryKey(rootName, secondName, leafName) {
        return [rootName, secondName, leafName].join("\u0001");
    }

    /**
     * Bangun daftar mapset dari `layerGroups` (struktur) dan metadata server
     * (warna, ikon, deskripsi, gambar, jumlah data) hasil loadCategoriesMetadata().
     */
    function rebuild() {
        const meta = window.MARIMOI_CATEGORY_METADATA || {};
        const categoriesByName = new Map();
        (meta.all_categories || []).forEach((cat) => {
            if (cat?.nama && !categoriesByName.has(cat.nama)) {
                categoriesByName.set(cat.nama, cat);
            }
        });
        const counts = meta.category_counts || {};

        state.entries = [];
        state.entryByKey = new Map();
        state.groups = [];

        Object.entries(layerGroups).forEach(([rootName, secondLevel]) => {
            const group = { name: rootName, entries: [], total: 0, subgroups: [] };
            const subgroupByName = new Map();

            Object.entries(secondLevel).forEach(([secondName, thirdLevel]) => {
                Object.entries(thirdLevel).forEach(([leafName, layerGroup]) => {
                    const cat = categoriesByName.get(leafName) || {};
                    const entry = {
                        key: entryKey(rootName, secondName, leafName),
                        rootName,
                        secondName,
                        leafName,
                        layerGroup,
                        color: cat.warna || "#0a84ff",
                        icon: cat.icon || null,
                        isMarker: cat.is_marker === true,
                        description: cat.deskripsi || "",
                        image: cat.gambar ? `/storage/${cat.gambar}` : null,
                        count: Number(counts[leafName] || 0),
                        path: [rootName, secondName].filter((name, i, arr) => name !== leafName && arr.indexOf(name) === i),
                    };
                    group.entries.push(entry);
                    group.total += entry.count;

                    // Mapset di level 3 dikelompokkan ke sub kategorinya (level 2).
                    if (secondName !== leafName) {
                        if (!subgroupByName.has(secondName)) {
                            const subgroup = { name: secondName, entries: [], total: 0 };
                            subgroupByName.set(secondName, subgroup);
                            group.subgroups.push(subgroup);
                        }
                        const subgroup = subgroupByName.get(secondName);
                        subgroup.entries.push(entry);
                        subgroup.total += entry.count;
                    }

                    state.entries.push(entry);
                    state.entryByKey.set(entry.key, entry);
                });
            });

            state.groups.push(group);
        });

        state.activeKeys = state.activeKeys.filter((key) => state.entryByKey.has(key));
        renderActiveList();
        if (isCatalogOpen()) {
            renderCatalog();
        }
    }

    // ==================== Layer aktif: aktifkan, muat, batalkan ====================

    function isActive(entry) {
        return state.activeKeys.includes(entry.key);
    }

    // Perbarui semua tampilan yang bergantung pada layer aktif: legenda, filter, label, daftar.
    function refreshDependentPanels() {
        generateLegend();
        if (typeof refreshFilterPanel === "function") {
            refreshFilterPanel();
        }
        window.MarimoiLabels?.refresh();
        renderActiveList();
    }

    // Paling banyak segini layer dimuat bersamaan; sisanya menunggu di antrean.
    const MAX_PARALLEL_LOADS = 2;

    // Masukkan mapset ke antrean pemuatan; resolve setelah selesai, gagal, atau dibatalkan.
    function enqueue(entry) {
        return new Promise((resolve) => {
            state.loads.set(entry.key, { status: "queued", loaded: 0, controller: null });
            state.queue.push({ entry, resolve });
            pumpQueue();
        });
    }

    // Jalankan antrean selama slot pemuatan masih ada; lewati mapset yang sudah dinonaktifkan.
    function pumpQueue() {
        while (state.running < MAX_PARALLEL_LOADS && state.queue.length) {
            const job = state.queue.shift();
            if (!isActive(job.entry) || !state.loads.has(job.entry.key)) {
                job.resolve();
                continue;
            }
            state.running++;
            loadEntry(job.entry).finally(() => {
                state.running--;
                job.resolve();
                pumpQueue();
            });
        }
    }

    /**
     * Muat satu layer: tampil bertahap di peta selama dimuat, kemajuan di barisnya sendiri,
     * dan bisa dibatalkan lewat AbortController (tombol Batalkan / hapus layer).
     */
    async function loadEntry(entry) {
        const controller = new AbortController();
        state.loads.set(entry.key, { status: "loading", loaded: 0, controller });
        state.errors.delete(entry.key);
        renderActiveList();

        if (!state.hiddenKeys.has(entry.key)) {
            map.addLayer(entry.layerGroup);
            applyLayerOrder();
        }

        const result = await loadCategoryData(entry.leafName, entry.secondName, entry.rootName, {
            signal: controller.signal,
            onProgress: (loaded) => updateLoadProgress(entry, loaded),
        });

        state.loads.delete(entry.key);
        if (result.status === "aborted" || !isActive(entry)) {
            return;
        }

        if (result.status === "error") {
            state.errors.set(entry.key, result.error);
            if (map.hasLayer(entry.layerGroup)) {
                map.removeLayer(entry.layerGroup);
            }
            showAlert(`Gagal memuat ${entry.leafName}: ${result.error}`, "danger");
        } else if (!state.hiddenKeys.has(entry.key)) {
            map.addLayer(entry.layerGroup);
            if (layerOpacityState.has(entry.leafName)) {
                setLayerGroupOpacity(entry.layerGroup, layerOpacityState.get(entry.leafName));
            }
            applyLayerOrder();
        }

        refreshDependentPanels();
    }

    /**
     * Perbarui teks & bilah kemajuan satu baris tanpa menggambar ulang seluruh daftar.
     */
    function updateLoadProgress(entry, loaded) {
        const load = state.loads.get(entry.key);
        if (!load) {
            return;
        }
        load.loaded = loaded;
        const row = document.querySelector(`#layer-list [data-key="${CSS.escape(entry.key)}"]`);
        if (!row) {
            return;
        }
        const percent = entry.count > 0 ? Math.min(100, Math.round((loaded / entry.count) * 100)) : 0;
        row.querySelector("[data-layer-progress-text]")?.replaceChildren(loadText(entry, load));
        const bar = row.querySelector("[data-layer-progress-bar]");
        if (bar) {
            bar.style.width = `${percent}%`;
            bar.parentElement.classList.toggle("is-indeterminate", loaded === 0);
            bar.parentElement.setAttribute("aria-valuenow", String(percent));
        }
    }

    // Teks status pemuatan di baris Layer Aktif.
    function loadText(entry, load) {
        if (load.status === "queued") {
            return "Menunggu antrean…";
        }
        return entry.count > 0
            ? `Memuat ${formatCount(load.loaded)} / ${formatCount(entry.count)} data`
            : `Memuat ${formatCount(load.loaded)} data`;
    }

    /**
     * Aktifkan layer (masuk antrean pemuatan). Resolve setelah semua layer tersebut
     * selesai dimuat, gagal, atau dibatalkan.
     */
    async function activate(entries) {
        const pending = [];
        for (const entry of entries) {
            if (isActive(entry)) {
                if (state.hiddenKeys.has(entry.key)) {
                    setVisibility(entry, true);
                }
                continue;
            }

            // Urutan daftar = urutan gambar di peta (indeks 0 paling atas); layer baru di atas.
            state.activeKeys.unshift(entry.key);
            pending.push(enqueue(entry));
        }
        renderActiveList();
        await Promise.all(pending);
    }

    // Batalkan request yang berjalan (AbortController) atau keluarkan dari antrean.
    function cancelLoad(entry) {
        state.loads.get(entry.key)?.controller?.abort();
        state.loads.delete(entry.key);
        state.queue = state.queue.filter((job) => {
            if (job.entry.key === entry.key) {
                job.resolve();
                return false;
            }
            return true;
        });
    }

    // Tombol "Coba lagi" setelah pemuatan gagal.
    function retry(entry) {
        state.errors.delete(entry.key);
        enqueue(entry);
        renderActiveList();
    }

    // Nonaktifkan mapset: batalkan pemuatannya bila masih berjalan, lalu lepas dari peta.
    function deactivate(entries) {
        entries.forEach((entry) => {
            cancelLoad(entry);
            state.errors.delete(entry.key);
            state.activeKeys = state.activeKeys.filter((key) => key !== entry.key);
            state.hiddenKeys.delete(entry.key);
            if (map.hasLayer(entry.layerGroup)) {
                map.removeLayer(entry.layerGroup);
            }
        });
        refreshDependentPanels();
    }

    // Tampilkan/sembunyikan tanpa menonaktifkan (data tetap tersimpan, tombol mata).
    function setVisibility(entry, isVisible) {
        if (isVisible) {
            state.hiddenKeys.delete(entry.key);
            map.addLayer(entry.layerGroup);
            applyLayerOrder();
        } else {
            state.hiddenKeys.add(entry.key);
            map.removeLayer(entry.layerGroup);
        }
        refreshDependentPanels();
    }

    /**
     * Susun tumpukan gambar di peta sesuai urutan daftar: dari layer paling bawah ke paling
     * atas, area/garis dibawa ke depan (bringToFront) dan marker diberi zIndexOffset bertingkat.
     */
    function applyLayerOrder() {
        const entries = getActiveEntries().filter((entry) => map.hasLayer(entry.layerGroup));
        const total = entries.length;

        const stack = (layer, rank) => {
            if (typeof layer.setZIndexOffset === "function") {
                layer.setZIndexOffset(rank * 1000);
            } else if (typeof layer.eachLayer === "function") {
                layer.eachLayer((child) => stack(child, rank));
            } else if (typeof layer.bringToFront === "function") {
                layer.bringToFront();
            }
        };

        for (let index = total - 1; index >= 0; index--) {
            stack(entries[index].layerGroup, total - index);
        }
    }

    // Pindahkan mapset ke posisi `to` di daftar sekaligus urutan gambar di peta.
    function reorderEntry(entry, to) {
        const from = state.activeKeys.indexOf(entry.key);
        if (from < 0 || to < 0 || to >= state.activeKeys.length || to === from) {
            return false;
        }
        state.activeKeys.splice(from, 1);
        state.activeKeys.splice(to, 0, entry.key);
        applyLayerOrder();
        renderActiveList();
        generateLegend();
        window.MarimoiLabels?.refresh();
        return true;
    }

    // Tombol naik/turun & panah keyboard pada pegangan seret.
    function moveEntry(entry, direction) {
        return reorderEntry(entry, state.activeKeys.indexOf(entry.key) + direction);
    }

    function focusDragHandle(key) {
        document.querySelector(`#layer-list [data-key="${CSS.escape(key)}"] [data-layer-drag]`)?.focus();
    }

    // ==================== Seret untuk mengubah urutan ====================
    // Pegangan (⋮⋮) diseret dengan mouse/sentuh; baris lain bergeser memberi tempat, lalu
    // urutan diterapkan saat dilepas. Dekat tepi atas/bawah, daftar ikut menggulir.

    const DRAG_EDGE = 40;

    function onDragStart(event) {
        const handle = event.target.closest("[data-layer-drag]");
        if (!handle || handle.disabled || event.button > 0) {
            return;
        }
        const row = handle.closest("[data-key]");
        const rows = [...row.parentElement.children];
        const scroller = document.getElementById("layer-list");
        event.preventDefault();
        handle.setPointerCapture(event.pointerId);

        const rects = rows.map((item) => item.getBoundingClientRect());
        const from = rows.indexOf(row);
        const gap = rows.length > 1 ? Math.abs(rects[1].top - rects[0].bottom) : 8;
        drag = {
            handle,
            row,
            rows,
            scroller,
            from,
            to: from,
            startY: event.clientY,
            startScroll: scroller.scrollTop,
            centers: rects.map((rect) => rect.top + rect.height / 2),
            shift: rects[from].height + gap,
            pointerY: event.clientY,
            frame: null,
        };
        row.classList.add("is-dragging");
        row.parentElement.classList.add("is-sorting");
        document.body.classList.add("is-sorting-layers");
        handle.addEventListener("pointermove", onDragMove);
        handle.addEventListener("pointerup", onDragEnd);
        handle.addEventListener("pointercancel", onDragEnd);
        drag.frame = requestAnimationFrame(autoScroll);
    }

    function updateDragPositions() {
        const offset = drag.pointerY - drag.startY + (drag.scroller.scrollTop - drag.startScroll);
        drag.row.style.transform = `translateY(${offset}px)`;

        // Posisi tujuan: jumlah baris lain yang titik tengahnya di atas titik tengah baris yang diseret.
        const center = drag.centers[drag.from] + offset;
        drag.to = drag.centers.filter((value, index) => index !== drag.from && value < center).length;

        drag.rows.forEach((item, index) => {
            if (index === drag.from) {
                return;
            }
            let move = 0;
            if (drag.from < drag.to && index > drag.from && index <= drag.to) {
                move = -drag.shift;
            } else if (drag.from > drag.to && index >= drag.to && index < drag.from) {
                move = drag.shift;
            }
            item.style.transform = move ? `translateY(${move}px)` : "";
        });
    }

    function onDragMove(event) {
        if (!drag) {
            return;
        }
        drag.pointerY = event.clientY;
        updateDragPositions();
    }

    function autoScroll() {
        if (!drag) {
            return;
        }
        const bounds = drag.scroller.getBoundingClientRect();
        let speed = 0;
        if (drag.pointerY < bounds.top + DRAG_EDGE) {
            speed = -Math.ceil((bounds.top + DRAG_EDGE - drag.pointerY) / 4);
        } else if (drag.pointerY > bounds.bottom - DRAG_EDGE) {
            speed = Math.ceil((drag.pointerY - (bounds.bottom - DRAG_EDGE)) / 4);
        }
        if (speed) {
            drag.scroller.scrollTop += speed;
            updateDragPositions();
        }
        drag.frame = requestAnimationFrame(autoScroll);
    }

    function onDragEnd() {
        if (!drag) {
            return;
        }
        const { handle, row, rows, from, to } = drag;
        cancelAnimationFrame(drag.frame);
        handle.removeEventListener("pointermove", onDragMove);
        handle.removeEventListener("pointerup", onDragEnd);
        handle.removeEventListener("pointercancel", onDragEnd);
        rows.forEach((item) => { item.style.transform = ""; });
        row.classList.remove("is-dragging");
        row.parentElement.classList.remove("is-sorting");
        document.body.classList.remove("is-sorting-layers");
        const pendingRender = drag.pendingRender;
        drag = null;

        const entry = state.entryByKey.get(row.dataset.key);
        if (entry && reorderEntry(entry, to)) {
            focusDragHandle(entry.key);
        } else {
            if (pendingRender) {
                renderActiveList();
            }
            focusDragHandle(row.dataset.key);
        }
    }

    function onDragKeydown(event) {
        const handle = event.target.closest("[data-layer-drag]");
        if (!handle || handle.disabled || (event.key !== "ArrowUp" && event.key !== "ArrowDown")) {
            return;
        }
        const entry = state.entryByKey.get(handle.closest("[data-key]")?.dataset.key);
        event.preventDefault();
        if (entry && moveEntry(entry, event.key === "ArrowUp" ? -1 : 1)) {
            focusDragHandle(entry.key);
        }
    }

    // Opacity untuk slider: nilai pilihan pengguna, atau opacity style bila belum diubah.
    function opacityOf(entry) {
        if (!layerOpacityState.has(entry.leafName)) {
            layerOpacityState.set(entry.leafName, getLayerGroupOpacity(entry.layerGroup));
        }
        return layerOpacityState.get(entry.leafName);
    }

    // Mapset aktif sesuai urutan daftar (indeks 0 = paling atas di peta).
    function getActiveEntries() {
        return state.activeKeys.map((key) => state.entryByKey.get(key)).filter(Boolean);
    }

    /**
     * Cari mapset berdasarkan nama dari link share/session. Nama bisa berupa mapset atau
     * kelompok di atasnya (kategori / sub kategori) — kelompok berarti semua mapset di dalamnya.
     */
    function findEntriesByName(name) {
        const exact = state.entries.filter((entry) => entry.leafName === name);
        if (exact.length > 0) {
            return exact;
        }
        return state.entries.filter((entry) => entry.secondName === name || entry.rootName === name);
    }

    // Aktifkan mapset dari daftar nama (link share, session, filter); kembalikan nama yang tidak ditemukan.
    async function activateByNames(names) {
        const missing = [];
        const entries = [];
        names.forEach((name) => {
            const found = findEntriesByName(name);
            if (found.length === 0) {
                missing.push(name);
            }
            found.forEach((entry) => {
                if (!entries.includes(entry)) {
                    entries.push(entry);
                }
            });
        });
        await activate(entries);
        return { activated: entries, missing };
    }

    // ==================== Sidebar Layer Aktif ====================

    // Gambar kartu katalog: gambar kategori bila ada, di atas pola titik berwarna + ikon layer.
    function thumbnailHtml(entry, extraClass = "") {
        const iconClass = entry.icon || (entry.isMarker ? "bi bi-geo-alt-fill" : "bi bi-bounding-box-circles");
        const fallback = `<span class="catalog-thumb-fallback" style="--layer-color:${escapeHtml(entry.color)}">
                <i class="${escapeHtml(iconClass)}"></i>
            </span>`;
        const image = entry.image
            ? `<img src="${escapeHtml(entry.image)}" alt="" loading="lazy" onerror="this.remove()">`
            : "";
        return `<span class="catalog-thumb ${extraClass}">${fallback}${image}</span>`;
    }

    // Gambar ulang sidebar Layer Aktif (chip filter, status pemuatan, slider, tombol aksi).
    // Gambar ulang daftar Layer Aktif lalu kabari modul lain (mis. Analisis) bahwa isinya berubah.
    function renderActiveList() {
        // Sedang diseret: gambar ulang setelah dilepas agar baris yang dipegang tidak hilang.
        if (drag) {
            drag.pendingRender = true;
            return;
        }
        drawActiveList();
        document.dispatchEvent(new CustomEvent("marimoi:active-layers-change"));
    }

    function drawActiveList() {
        const container = document.getElementById("layer-list");
        if (!container) {
            return;
        }

        const active = getActiveEntries();

        if (active.length === 0) {
            container.innerHTML = `
                <div class="active-layer-empty">
                    <i class="bi bi-layers"></i>
                    <p class="active-layer-empty-title">Belum ada layer aktif</p>
                    <p>Pilih mapset dari Katalog untuk ditampilkan di peta.</p>
                    <button type="button" class="catalog-btn-primary" data-open-catalog>
                        <i class="bi bi-grid-3x3-gap-fill"></i> Buka Katalog
                    </button>
                </div>`;
            return;
        }

        const rows = active.map((entry, index) => {
            const isHidden = state.hiddenKeys.has(entry.key);
            const load = state.loads.get(entry.key);
            const error = state.errors.get(entry.key);
            const isBusy = Boolean(load) || Boolean(error);
            const name = escapeHtml(entry.leafName);
            const path = entry.path.length ? `<span class="active-layer-path">${escapeHtml(entry.path.join(" › "))}</span>` : "";
            const opacity = Math.round(opacityOf(entry) * 100);

            let status = `<span class="active-layer-count">${formatCount(entry.count)} data</span>`;
            if (load) {
                status = `<span class="active-layer-count is-loading" data-layer-progress-text>${escapeHtml(loadText(entry, load))}</span>`;
            } else if (error) {
                status = `<span class="active-layer-count is-error" title="${escapeHtml(error)}">Gagal memuat data</span>`;
            }

            let controls;
            if (load) {
                const percent = entry.count > 0 ? Math.min(100, Math.round((load.loaded / entry.count) * 100)) : 0;
                controls = `
                    <span class="active-layer-progress${load.status === "queued" ? " is-queued" : load.loaded === 0 ? " is-indeterminate" : ""}" role="progressbar" aria-label="Kemajuan memuat ${name}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${percent}">
                        <span data-layer-progress-bar style="width:${load.status === "queued" ? 0 : percent}%"></span>
                    </span>
                    <button type="button" class="active-layer-cancel" data-layer-action="cancel" aria-label="Batalkan memuat ${name}">
                        <i class="bi bi-x-circle"></i> Batalkan
                    </button>`;
            } else if (error) {
                controls = `
                    <button type="button" class="active-layer-retry" data-layer-action="retry" aria-label="Coba muat ulang ${name}">
                        <i class="bi bi-arrow-clockwise"></i> Coba lagi
                    </button>
                    <span class="active-layer-actions">
                        <button type="button" data-layer-action="remove" title="Hapus dari peta" aria-label="Hapus ${name}">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </span>`;
            } else {
                controls = `
                    <label class="active-layer-opacity">
                        <span class="sr-only">Opacity ${name}</span>
                        <i class="bi bi-circle-half" aria-hidden="true"></i>
                        <input type="range" min="0" max="100" step="5" value="${opacity}" data-layer-opacity style="accent-color:${escapeHtml(entry.color)}"${isHidden ? " disabled" : ""}>
                        <output>${opacity}%</output>
                    </label>
                    <span class="active-layer-actions">
                        <button type="button" data-layer-action="zoom" title="Perbesar ke layer" aria-label="Perbesar ke ${name}"${isHidden ? " disabled" : ""}>
                            <i class="bi bi-crosshair"></i>
                        </button>
                        <button type="button" data-layer-action="visibility" title="${isHidden ? "Tampilkan" : "Sembunyikan"}" aria-label="${isHidden ? "Tampilkan" : "Sembunyikan"} ${name}" aria-pressed="${isHidden ? "false" : "true"}">
                            <i class="bi ${isHidden ? "bi-eye-slash" : "bi-eye"}"></i>
                        </button>
                        <button type="button" data-layer-action="remove" title="Hapus dari peta" aria-label="Hapus ${name}">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </span>`;
            }

            return `
                <li class="active-layer${isHidden ? " is-hidden" : ""}${load ? " is-loading" : ""}${error ? " is-error" : ""}" data-key="${escapeHtml(entry.key)}">
                    <div class="active-layer-main">
                        <button type="button" class="active-layer-drag" data-layer-drag title="Seret untuk mengubah urutan" aria-label="Ubah urutan ${name}: seret, atau tekan panah atas/bawah"${active.length < 2 || isBusy ? " disabled" : ""}>
                            <i class="bi bi-grip-vertical"></i>
                        </button>
                        <span class="active-layer-swatch" style="--layer-color:${escapeHtml(entry.color)}" aria-hidden="true"></span>
                        <span class="active-layer-text">
                            <span class="active-layer-name" title="${name}">${name}</span>
                            ${path}
                            ${status}
                        </span>
                        <span class="active-layer-order">
                            <button type="button" data-layer-action="up" title="Naikkan urutan" aria-label="Naikkan ${name}"${index === 0 || isBusy ? " disabled" : ""}>
                                <i class="bi bi-chevron-up"></i>
                            </button>
                            <button type="button" data-layer-action="down" title="Turunkan urutan" aria-label="Turunkan ${name}"${index === active.length - 1 || isBusy ? " disabled" : ""}>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </span>
                    </div>
                    <div class="active-layer-controls">${controls}</div>
                </li>`;
        });

        const labels = filterLabels(state.appliedFilters);
        const filterChip = labels.length
            ? `<div class="active-filter-chip">
                    <i class="bi bi-funnel-fill"></i>
                    <span title="${escapeHtml(labels.join(" · "))}">${escapeHtml(labels.join(" · "))}</span>
                    <button type="button" data-open-catalog data-open-filter>Ubah</button>
                </div>`
            : "";

        container.innerHTML = `
            ${filterChip}
            <div class="active-layer-head">
                <span title="Layer paling atas di daftar digambar paling atas di peta">${active.length} layer aktif</span>
                <button type="button" data-layer-action="remove-all">Hapus semua</button>
            </div>
            <label class="active-layer-switch">
                <span><i class="bi bi-fonts" aria-hidden="true"></i> Label fitur di peta</span>
                <input type="checkbox" role="switch" data-toggle-labels${window.MarimoiLabels?.isEnabled?.() === false ? "" : " checked"}>
                <span class="active-layer-switch-track" aria-hidden="true"></span>
            </label>
            <ul class="active-layer-list">${rows.join("")}</ul>`;
    }

    // Perbesar peta ke seluruh fitur satu mapset.
    function zoomToEntry(entry) {
        const bounds = typeof entry.layerGroup.getBounds === "function" ? entry.layerGroup.getBounds() : null;
        if (bounds && bounds.isValid()) {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
            return;
        }
        const collected = L.featureGroup();
        entry.layerGroup.eachLayer((layer) => collected.addLayer(layer));
        const fallbackBounds = collected.getBounds();
        if (fallbackBounds.isValid()) {
            map.fitBounds(fallbackBounds, { padding: [40, 40], maxZoom: 16 });
        }
    }

    // Satu handler untuk semua tombol di Layer Aktif (data-layer-action).
    function onActiveListClick(event) {
        const button = event.target.closest("[data-layer-action]");
        if (!button || button.disabled) {
            return;
        }
        const action = button.dataset.layerAction;

        if (action === "remove-all") {
            deactivate(getActiveEntries());
            return;
        }

        const row = button.closest("[data-key]");
        const entry = row ? state.entryByKey.get(row.dataset.key) : null;
        if (!entry) {
            return;
        }

        if (action === "remove" || action === "cancel") {
            deactivate([entry]);
        } else if (action === "retry") {
            retry(entry);
        } else if (action === "visibility") {
            setVisibility(entry, state.hiddenKeys.has(entry.key));
        } else if (action === "zoom") {
            zoomToEntry(entry);
        } else if (action === "up" || action === "down") {
            moveEntry(entry, action === "up" ? -1 : 1);
        }
    }

    // Slider opacity: terapkan langsung ke peta saat digeser.
    function onActiveListInput(event) {
        const slider = event.target.closest("[data-layer-opacity]");
        const entry = slider ? state.entryByKey.get(slider.closest("[data-key]")?.dataset.key) : null;
        if (!entry) {
            return;
        }
        const opacity = Number(slider.value) / 100;
        layerOpacityState.set(entry.leafName, opacity);
        setLayerGroupOpacity(entry.layerGroup, opacity);
        slider.nextElementSibling.textContent = `${slider.value}%`;
    }

    // ==================== Filter Data ====================

    const FILTER_FIELDS = { kabupaten: "filter-kabupaten", tahun: "filter-tahun", opd_pengelola: "filter-opd" };

    // Nilai select filter di modal (belum tentu sudah diterapkan ke peta).
    function readFilterInputs() {
        const values = {};
        Object.entries(FILTER_FIELDS).forEach(([key, id]) => {
            values[key] = document.getElementById(id)?.value || "";
        });
        return values;
    }

    // Pasang nilai ke select filter; opsi yang belum ada ditambahkan dulu.
    function writeFilterInputs(values) {
        Object.entries(FILTER_FIELDS).forEach(([key, id]) => {
            const select = document.getElementById(id);
            if (!select) {
                return;
            }
            const value = values[key] || "";
            if (value && !Array.from(select.options).some((option) => option.value === value)) {
                select.add(new Option(value, value));
            }
            select.value = value;
        });
    }

    function hasAnyFilter(values) {
        return Object.values(values).some(Boolean);
    }

    // Teks ringkas filter untuk chip di Layer Aktif, mis. "Kota Ternate · 2025".
    function filterLabels(values) {
        const opdLabel = document.getElementById(FILTER_FIELDS.opd_pengelola)?.selectedOptions[0]?.textContent;
        return [values.kabupaten, values.tahun, values.opd_pengelola ? opdLabel || values.opd_pengelola : ""].filter(Boolean);
    }

    // Saring kartu katalog ke mapset yang punya data sesuai filter (dari server).
    async function updateFilterMatches() {
        const filters = readFilterInputs();
        const requestId = ++state.filterRequest;
        renderFilterState();

        if (!hasAnyFilter(filters)) {
            state.filterMatches = null;
            renderCatalog();
            return;
        }

        el.items.classList.add("is-busy");
        const names = await fetchCategoriesMatchingFilter(filters);
        if (requestId !== state.filterRequest) {
            return;
        }
        el.items.classList.remove("is-busy");
        state.filterMatches = new Set(names);
        renderCatalog();
    }

    // Badge jumlah filter pada tombol Filter.
    function renderFilterState() {
        const count = Object.values(readFilterInputs()).filter(Boolean).length;
        el.filterCount.textContent = count ? String(count) : "";
        el.filterCount.classList.toggle("hidden", count === 0);
        el.filterToggle.classList.toggle("is-active", count > 0);
    }

    // ==================== Modal Katalog Peta ====================

    function isCatalogOpen() {
        return el.modal && !el.modal.classList.contains("hidden");
    }

    // Buka katalog: pilihan disusun ulang dari layer aktif, filter dari yang sedang berlaku.
    function openCatalog() {
        if (!el.modal) {
            return;
        }
        state.staged = new Set(state.activeKeys);
        state.searchTerm = "";
        el.search.value = "";
        writeFilterInputs(state.appliedFilters);
        state.filterMatches = null;
        if (hasAnyFilter(state.appliedFilters)) {
            updateFilterMatches();
        }
        renderFilterState();
        el.modal.classList.remove("hidden");
        document.body.classList.add("catalog-open");
        renderCatalog();
        setTimeout(() => el.search.focus(), 50);
    }

    function setFilterPanelOpen(isOpen) {
        el.filterPanel.classList.toggle("hidden", !isOpen);
        el.filterToggle.setAttribute("aria-expanded", String(isOpen));
    }

    /**
     * Menutup tanpa Terapkan membatalkan perubahan filter, supaya isi select selalu
     * sama dengan filter yang benar-benar berlaku di peta (dibaca refreshFilterPanel()).
     */
    function closeCatalog() {
        el.modal?.classList.add("hidden");
        document.body.classList.remove("catalog-open");
        if (!state.isApplying) {
            writeFilterInputs(state.appliedFilters);
        }
    }

    function findGroup(name) {
        return state.groups.find((group) => group.name === name) || null;
    }

    // Kartu yang tampil sesuai kelompok terpilih, filter, dan kata pencarian.
    function visibleEntries() {
        const term = state.searchTerm.trim().toLowerCase();
        // Pencarian selalu lintas kelompok; memilih kelompok mengosongkan pencarian.
        const group = state.currentGroup && !term ? findGroup(state.currentGroup) : null;
        const subgroup = group && state.currentSub ? group.subgroups.find((sub) => sub.name === state.currentSub) : null;
        let source = subgroup ? subgroup.entries : group ? group.entries : state.entries;

        if (state.filterMatches) {
            source = source.filter((entry) => state.filterMatches.has(entry.leafName));
        }

        if (!term) {
            return source;
        }
        return source.filter((entry) =>
            [entry.leafName, entry.description, ...entry.path].some((text) => text && text.toLowerCase().includes(term))
        );
    }

    // Tombol kategori/sub kategori di kolom kiri katalog.
    function groupButtonHtml({ group, sub, label, total, icon, isCurrent, depth, expandable, isExpanded }) {
        const chevron = expandable
            ? `<span class="catalog-group-toggle" data-group-toggle="${escapeHtml(group)}" role="button" tabindex="0" aria-label="${isExpanded ? "Tutup" : "Buka"} sub kategori ${escapeHtml(label)}" aria-expanded="${isExpanded}"><i class="bi ${isExpanded ? "bi-chevron-down" : "bi-chevron-right"}"></i></span>`
            : `<span class="catalog-group-toggle is-empty"></span>`;
        return `
            <button type="button" class="catalog-group${depth ? " is-sub" : ""}${isCurrent ? " is-current" : ""}" data-group="${escapeHtml(group)}" data-sub="${escapeHtml(sub)}"${isCurrent ? ' aria-current="true"' : ""}>
                <i class="bi ${icon}"></i>
                <span class="catalog-group-name" title="${escapeHtml(label)}">${escapeHtml(label)}</span>
                <span class="catalog-badge">${formatCount(total)}</span>
                ${depth ? "" : chevron}
            </button>`;
    }

    // Kolom kiri katalog: "Semua Mapset", kategori, dan sub kategori yang sedang dibuka.
    function renderGroups() {
        const allTotal = state.groups.reduce((sum, group) => sum + group.total, 0);
        const isSearching = state.searchTerm.trim() !== "";
        const items = [
            `<li>${groupButtonHtml({ group: "", sub: "", label: "Semua Mapset", total: allTotal, icon: "bi-collection", isCurrent: !isSearching && !state.currentGroup, depth: 0, expandable: false })}</li>`,
        ];

        state.groups.forEach((group) => {
            const hasSubs = group.subgroups.length > 0;
            const isExpanded = hasSubs && state.expandedGroups.has(group.name);
            const isCurrentGroup = !isSearching && state.currentGroup === group.name;
            let html = groupButtonHtml({
                group: group.name,
                sub: "",
                label: group.name,
                total: group.total,
                icon: isExpanded ? "bi-folder2-open" : "bi-folder2",
                isCurrent: isCurrentGroup && !state.currentSub,
                depth: 0,
                expandable: hasSubs,
                isExpanded,
            });

            if (isExpanded) {
                html += `<ul class="catalog-subgroups">${group.subgroups
                    .map((sub) => `<li>${groupButtonHtml({
                        group: group.name,
                        sub: sub.name,
                        label: sub.name,
                        total: sub.total,
                        icon: "bi-dot",
                        isCurrent: isCurrentGroup && state.currentSub === sub.name,
                        depth: 1,
                    })}</li>`)
                    .join("")}</ul>`;
            }
            items.push(`<li>${html}</li>`);
        });

        el.groupList.innerHTML = items.join("");
        el.groupList.querySelector(".catalog-group.is-current")?.scrollIntoView({ block: "nearest", inline: "nearest" });
    }

    // Satu kartu mapset (gambar, jumlah data, nama, deskripsi, tombol Tambah/Hapus).
    function cardHtml(entry) {
        const isSelected = state.staged.has(entry.key);
        const isSearching = state.searchTerm.trim() !== "";
        // Saat kelompok dibuka, jalurnya sudah terlihat di judul, jadi tidak diulang di kartu.
        const relativePath = isSearching || !state.currentGroup ? entry.path : [];
        const path = relativePath.length ? `<span class="catalog-card-path">${escapeHtml(relativePath.join(" › "))}</span>` : "";
        const description = entry.description || (entry.isMarker ? "Data titik lokasi" : "Data area / garis");
        return `
            <article class="catalog-card${isSelected ? " is-selected" : ""}" data-key="${escapeHtml(entry.key)}">
                <div class="catalog-card-media">
                    ${thumbnailHtml(entry)}
                    <span class="catalog-count" title="Jumlah data">${formatCount(entry.count)}</span>
                </div>
                <div class="catalog-card-body">
                    ${path}
                    <h4>${escapeHtml(entry.leafName)}</h4>
                    <p>${escapeHtml(description)}</p>
                </div>
                <button type="button" class="catalog-card-action${isSelected ? " is-remove" : ""}" data-catalog-toggle aria-pressed="${isSelected}">
                    <i class="bi ${isSelected ? "bi-x-lg" : "bi-plus-lg"}"></i>
                    <span>${isSelected ? "Hapus" : "Tambah"}</span>
                </button>
            </article>`;
    }

    /**
     * Saat satu kategori dibuka (tanpa sub kategori/pencarian), kartu dikelompokkan per
     * sub kategori: mapset yang langsung di bawah kategori dulu, lalu tiap sub kategori.
     */
    function sectionsFor(entries) {
        const group = state.currentGroup && !state.currentSub && !state.searchTerm.trim() ? findGroup(state.currentGroup) : null;
        if (!group || group.subgroups.length === 0) {
            return [{ title: null, entries }];
        }
        const visible = new Set(entries);
        const direct = entries.filter((entry) => entry.secondName === entry.leafName);
        const sections = direct.length ? [{ title: "Mapset utama", entries: direct }] : [];
        group.subgroups.forEach((sub) => {
            const subEntries = sub.entries.filter((entry) => visible.has(entry));
            if (subEntries.length) {
                sections.push({ title: sub.name, sub: sub.name, entries: subEntries });
            }
        });
        return sections;
    }

    // Kolom kanan katalog: judul, daftar kartu (per bagian bila perlu), dan ringkasan pilihan.
    function renderItems() {
        const entries = visibleEntries();
        el.title.textContent = state.searchTerm.trim()
            ? "Hasil Pencarian"
            : state.currentSub || state.currentGroup || "Semua Mapset";
        el.breadcrumb.textContent = !state.searchTerm.trim() && state.currentSub ? state.currentGroup : "";
        el.breadcrumb.hidden = el.breadcrumb.textContent === "";
        el.items.classList.toggle("is-grid", state.view === "grid");
        el.items.classList.toggle("is-list", state.view === "list");

        if (entries.length === 0) {
            el.items.innerHTML = `
                <div class="catalog-empty">
                    <i class="bi bi-search"></i>
                    <p>${state.searchTerm
                        ? `Tidak ada mapset yang cocok dengan "${escapeHtml(state.searchTerm)}".`
                        : state.filterMatches
                          ? "Tidak ada mapset yang memiliki data sesuai filter."
                          : "Belum ada mapset pada kelompok ini."}</p>
                </div>`;
        } else {
            el.items.innerHTML = sectionsFor(entries)
                .map((section) => {
                    const heading = section.title
                        ? `<div class="catalog-section-heading">
                                <span>${escapeHtml(section.title)}</span>
                                <small>${formatCount(section.entries.length)} mapset</small>
                                ${section.sub ? `<button type="button" data-open-sub="${escapeHtml(section.sub)}">Lihat <i class="bi bi-arrow-right"></i></button>` : ""}
                            </div>`
                        : "";
                    return heading + section.entries.map(cardHtml).join("");
                })
                .join("");
        }

        renderSummary(entries);
    }

    // Ringkasan "N layer · M terpilih" dan tombol Pilih/Batalkan Semua.
    function renderSummary(entries = visibleEntries()) {
        el.summary.textContent = `${formatCount(entries.length)} layer · ${formatCount(state.staged.size)} terpilih`;
        const allSelected = entries.length > 0 && entries.every((entry) => state.staged.has(entry.key));
        el.selectAll.querySelector("span").textContent = allSelected ? "Batalkan Semua" : "Pilih Semua";
        el.selectAll.disabled = entries.length === 0;
        el.selectAll.dataset.mode = allSelected ? "clear" : "select";
    }

    function renderCatalog() {
        renderGroups();
        renderItems();
        el.viewButtons.forEach((button) => {
            button.setAttribute("aria-pressed", String(button.dataset.catalogView === state.view));
        });
    }

    /**
     * Terapkan pilihan katalog. Pemuatan layer berjalan di latar (antrean + kemajuan per baris
     * di Layer Aktif), jadi katalog bisa langsung dipakai lagi tanpa menunggu.
     */
    function applySelection() {
        const toRemove = getActiveEntries().filter((entry) => !state.staged.has(entry.key));
        const toAdd = state.entries.filter((entry) => state.staged.has(entry.key) && !isActive(entry));
        state.appliedFilters = readFilterInputs();

        // isApplying: closeCatalog() tidak boleh mengembalikan select filter yang baru diterapkan.
        state.isApplying = true;
        closeCatalog();
        state.isApplying = false;

        if (toRemove.length) {
            deactivate(toRemove);
        }
        if (toAdd.length || state.activeKeys.length) {
            document.getElementById("sidebar-layer")?.classList.remove("hidden");
        }
        if (toAdd.length) {
            activate(toAdd);
        }
        refreshDependentPanels();
    }

    // Ikat semua event modal katalog (sekali saat halaman dibuka).
    function bindCatalogEvents() {
        el.modal = document.getElementById("catalogModal");
        if (!el.modal) {
            return;
        }
        el.groupList = document.getElementById("catalog-group-list");
        el.items = document.getElementById("catalog-items");
        el.title = document.getElementById("catalog-section-title");
        el.filterToggle = document.getElementById("catalog-filter-toggle");
        el.filterPanel = document.getElementById("catalog-filter-panel");
        el.filterCount = document.getElementById("filter-summary-count");
        el.breadcrumb = document.getElementById("catalog-section-parent");
        el.search = document.getElementById("catalog-search");
        el.summary = document.getElementById("catalog-summary-text");
        el.selectAll = document.getElementById("catalog-select-all");
        el.apply = document.getElementById("catalog-apply");
        el.viewButtons = el.modal.querySelectorAll("[data-catalog-view]");

        document.addEventListener("click", (event) => {
            const opener = event.target.closest("[data-open-catalog]");
            if (opener) {
                openCatalog();
                if (opener.hasAttribute("data-open-filter")) {
                    setFilterPanelOpen(true);
                }
            }
        });

        // Belum ada layer aktif: tombol Layer langsung membuka katalog, bukan sidebar kosong.
        document.addEventListener(
            "click",
            (event) => {
                if (event.target.closest("#btn-toggle-sidebar-layer") && state.activeKeys.length === 0) {
                    event.stopPropagation();
                    ["sidebar-layer", "sidebar-basemap", "sidebar-legend"].forEach((id) => {
                        document.getElementById(id)?.classList.add("hidden");
                    });
                    openCatalog();
                }
            },
            true
        );

        el.modal.querySelectorAll("[data-catalog-close]").forEach((button) => button.addEventListener("click", closeCatalog));
        el.modal.addEventListener("click", (event) => {
            if (event.target === el.modal) {
                closeCatalog();
            }
        });
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && isCatalogOpen()) {
                closeCatalog();
            }
        });

        el.groupList.addEventListener("click", (event) => {
            const toggle = event.target.closest("[data-group-toggle]");
            if (toggle) {
                event.stopPropagation();
                const name = toggle.dataset.groupToggle;
                if (state.expandedGroups.has(name)) {
                    state.expandedGroups.delete(name);
                } else {
                    state.expandedGroups.add(name);
                }
                renderGroups();
                return;
            }

            const button = event.target.closest("[data-group]");
            if (!button) {
                return;
            }
            selectGroup(button.dataset.group || null, button.dataset.sub || null);
        });

        el.items.addEventListener("click", (event) => {
            const subLink = event.target.closest("[data-open-sub]");
            if (subLink) {
                event.stopPropagation();
                selectGroup(state.currentGroup, subLink.dataset.openSub);
            }
        }, true);

        el.groupList.addEventListener("keydown", (event) => {
            const toggle = event.target.closest("[data-group-toggle]");
            if (toggle && (event.key === "Enter" || event.key === " ")) {
                event.preventDefault();
                toggle.click();
            }
        });

        function selectGroup(groupName, subName) {
            state.currentGroup = groupName;
            state.currentSub = subName;
            if (groupName && findGroup(groupName)?.subgroups.length) {
                state.expandedGroups.add(groupName);
            }
            state.searchTerm = "";
            el.search.value = "";
            renderCatalog();
            el.items.scrollTop = 0;
        }

        let searchTimer = null;
        el.search.addEventListener("input", () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                state.searchTerm = el.search.value;
                renderGroups();
                renderItems();
            }, 150);
        });

        el.viewButtons.forEach((button) =>
            button.addEventListener("click", () => {
                state.view = button.dataset.catalogView;
                try {
                    localStorage.setItem("marimoi.catalogView", state.view);
                } catch (error) {
                    // localStorage bisa diblokir browser; preferensi tampilan cukup tidak disimpan.
                }
                renderCatalog();
            })
        );

        el.items.addEventListener("click", (event) => {
            const card = event.target.closest(".catalog-card");
            if (!card) {
                return;
            }
            const key = card.dataset.key;
            if (state.staged.has(key)) {
                state.staged.delete(key);
            } else {
                state.staged.add(key);
            }
            renderItems();
        });

        el.selectAll.addEventListener("click", () => {
            const entries = visibleEntries();
            if (el.selectAll.dataset.mode === "clear") {
                entries.forEach((entry) => state.staged.delete(entry.key));
            } else {
                entries.forEach((entry) => state.staged.add(entry.key));
            }
            renderItems();
        });

        el.apply.addEventListener("click", applySelection);

        el.filterToggle.addEventListener("click", () => {
            setFilterPanelOpen(el.filterPanel.classList.contains("hidden"));
        });
        Object.values(FILTER_FIELDS).forEach((id) => {
            document.getElementById(id)?.addEventListener("change", updateFilterMatches);
        });
        document.getElementById("btn-reset-filter")?.addEventListener("click", () => {
            writeFilterInputs({});
            updateFilterMatches();
        });

        try {
            state.view = localStorage.getItem("marimoi.catalogView") === "list" ? "list" : "grid";
        } catch (error) {
            state.view = "grid";
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        bindCatalogEvents();
        const layerList = document.getElementById("layer-list");
        layerList?.addEventListener("click", onActiveListClick);
        layerList?.addEventListener("input", onActiveListInput);
        // Sakelar label: tampilkan/sembunyikan semua label fitur di peta.
        layerList?.addEventListener("change", (event) => {
            if (event.target.matches("[data-toggle-labels]")) {
                window.MarimoiLabels?.setEnabled(event.target.checked);
            }
        });
        layerList?.addEventListener("pointerdown", onDragStart);
        layerList?.addEventListener("keydown", onDragKeydown);
    });

    window.MarimoiCatalog = {
        rebuild,
        renderActiveList,
        syncAppliedFilters: (values) => {
            if (values) {
                writeFilterInputs(values);
            }
            state.appliedFilters = readFilterInputs();
            renderActiveList();
        },
        activate,
        activateByNames,
        deactivate,
        findEntriesByName,
        getActiveEntries,
        isActive,
        // Sedang antre/dimuat: datanya belum lengkap untuk dianalisis.
        isLoading: (entry) => state.loads.has(entry.key),
        isHidden: (entry) => state.hiddenKeys.has(entry.key),
        open: openCatalog,
        close: closeCatalog,
    };
})();
