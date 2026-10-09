/**
 * Katalog Data & daftar Layer Aktif untuk peta tematik.
 *
 * Satu-satunya sumber state layer aktif: setiap dataset (kategori daun di
 * `layerGroups[root][second][leaf]` milik map.js) aktif/nonaktif lewat API di sini.
 * Modal katalog memilih dataset secara bertahap (staged) lalu diterapkan sekaligus;
 * sidebar "Layer Aktif" hanya menampilkan dataset yang sedang dipilih.
 *
 * Elemen yang dibuat di file ini memakai kelas CSS dari resources/css/peta.css
 * (bukan utility Tailwind), karena file di public/ tidak dipindai Tailwind.
 */
(function () {
    const state = {
        entries: [],
        entryByKey: new Map(),
        groups: [],
        activeKeys: [],
        hiddenKeys: new Set(),
        loadingKeys: new Set(),
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

    function entryKey(rootName, secondName, leafName) {
        return [rootName, secondName, leafName].join("\u0001");
    }

    /**
     * Bangun indeks dataset dari `layerGroups` (struktur) + metadata kategori
     * (warna, ikon, deskripsi, gambar, jumlah fitur) hasil loadCategoriesMetadata().
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

                    // Daun level 3 → milik sub kategori (level 2) yang punya turunan.
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

    // ==================== API layer aktif ====================

    function isActive(entry) {
        return state.activeKeys.includes(entry.key);
    }

    async function waitUntilDataIdle() {
        while (typeof isLoadingData !== "undefined" && isLoadingData) {
            await new Promise((resolve) => setTimeout(resolve, 100));
        }
    }

    function refreshDependentPanels() {
        generateLegend();
        updateLayerToolsPanel();
        if (typeof refreshFilterPanel === "function") {
            refreshFilterPanel();
        }
        renderActiveList();
    }

    function setLoading(entry, isLoading) {
        if (isLoading) {
            state.loadingKeys.add(entry.key);
        } else {
            state.loadingKeys.delete(entry.key);
        }
        renderActiveList();
    }

    /**
     * Aktifkan dataset satu per satu (loadCategoryData tidak boleh paralel).
     */
    async function activate(entries) {
        for (const entry of entries) {
            if (isActive(entry)) {
                if (state.hiddenKeys.has(entry.key)) {
                    setVisibility(entry, true);
                }
                continue;
            }

            // Urutan daftar = urutan gambar di peta (indeks 0 paling atas); layer baru di atas.
            state.activeKeys.unshift(entry.key);
            setLoading(entry, true);
            try {
                await waitUntilDataIdle();
                await loadCategoryData(entry.leafName, entry.secondName, entry.rootName);
                if (isActive(entry) && !state.hiddenKeys.has(entry.key)) {
                    map.addLayer(entry.layerGroup);
                    if (layerOpacityState.has(entry.leafName)) {
                        setLayerGroupOpacity(entry.layerGroup, layerOpacityState.get(entry.leafName));
                    }
                    applyLayerOrder();
                }
            } finally {
                setLoading(entry, false);
            }
        }
        refreshDependentPanels();
    }

    function deactivate(entries) {
        entries.forEach((entry) => {
            state.activeKeys = state.activeKeys.filter((key) => key !== entry.key);
            state.hiddenKeys.delete(entry.key);
            if (map.hasLayer(entry.layerGroup)) {
                map.removeLayer(entry.layerGroup);
            }
        });
        refreshDependentPanels();
    }

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

    function moveEntry(entry, direction) {
        const from = state.activeKeys.indexOf(entry.key);
        const to = from + direction;
        if (from < 0 || to < 0 || to >= state.activeKeys.length) {
            return;
        }
        state.activeKeys.splice(from, 1);
        state.activeKeys.splice(to, 0, entry.key);
        applyLayerOrder();
        renderActiveList();
        updateLayerToolsPanel();
    }

    function opacityOf(entry) {
        if (!layerOpacityState.has(entry.leafName)) {
            layerOpacityState.set(entry.leafName, getLayerGroupOpacity(entry.layerGroup));
        }
        return layerOpacityState.get(entry.leafName);
    }

    function getActiveEntries() {
        return state.activeKeys.map((key) => state.entryByKey.get(key)).filter(Boolean);
    }

    /**
     * Nama dari link share/session bisa menunjuk dataset (daun) atau kelompok di
     * atasnya (induk/sub kategori) — kelompok berarti semua dataset di bawahnya.
     */
    function findEntriesByName(name) {
        const exact = state.entries.filter((entry) => entry.leafName === name);
        if (exact.length > 0) {
            return exact;
        }
        return state.entries.filter((entry) => entry.secondName === name || entry.rootName === name);
    }

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

    function renderActiveList() {
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
            const isLoading = state.loadingKeys.has(entry.key);
            const name = escapeHtml(entry.leafName);
            const path = entry.path.length ? `<span class="active-layer-path">${escapeHtml(entry.path.join(" › "))}</span>` : "";
            const opacity = Math.round(opacityOf(entry) * 100);
            return `
                <li class="active-layer${isHidden ? " is-hidden" : ""}${isLoading ? " is-loading" : ""}" data-key="${escapeHtml(entry.key)}">
                    <div class="active-layer-main">
                        <span class="active-layer-swatch" style="--layer-color:${escapeHtml(entry.color)}" aria-hidden="true"></span>
                        <span class="active-layer-text">
                            <span class="active-layer-name" title="${name}">${name}</span>
                            ${path}
                            <span class="active-layer-count">${isLoading ? "Memuat data..." : `${formatCount(entry.count)} data`}</span>
                        </span>
                        <span class="active-layer-order">
                            <button type="button" data-layer-action="up" title="Naikkan urutan" aria-label="Naikkan ${name}"${index === 0 ? " disabled" : ""}>
                                <i class="bi bi-chevron-up"></i>
                            </button>
                            <button type="button" data-layer-action="down" title="Turunkan urutan" aria-label="Turunkan ${name}"${index === active.length - 1 ? " disabled" : ""}>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </span>
                    </div>
                    <div class="active-layer-controls">
                        <label class="active-layer-opacity">
                            <span class="sr-only">Opacity ${name}</span>
                            <i class="bi bi-circle-half" aria-hidden="true"></i>
                            <input type="range" min="0" max="100" step="5" value="${opacity}" data-layer-opacity style="accent-color:${escapeHtml(entry.color)}"${isHidden || isLoading ? " disabled" : ""}>
                            <output>${opacity}%</output>
                        </label>
                        <span class="active-layer-actions">
                            <button type="button" data-layer-action="zoom" title="Perbesar ke layer" aria-label="Perbesar ke ${name}"${isHidden || isLoading ? " disabled" : ""}>
                                <i class="bi bi-crosshair"></i>
                            </button>
                            <button type="button" data-layer-action="visibility" title="${isHidden ? "Tampilkan" : "Sembunyikan"}" aria-label="${isHidden ? "Tampilkan" : "Sembunyikan"} ${name}" aria-pressed="${isHidden ? "false" : "true"}"${isLoading ? " disabled" : ""}>
                                <i class="bi ${isHidden ? "bi-eye-slash" : "bi-eye"}"></i>
                            </button>
                            <button type="button" data-layer-action="remove" title="Hapus dari peta" aria-label="Hapus ${name}"${isLoading ? " disabled" : ""}>
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </span>
                    </div>
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
            <ul class="active-layer-list">${rows.join("")}</ul>`;
    }

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

        if (action === "remove") {
            deactivate([entry]);
        } else if (action === "visibility") {
            setVisibility(entry, state.hiddenKeys.has(entry.key));
        } else if (action === "zoom") {
            zoomToEntry(entry);
        } else if (action === "up" || action === "down") {
            moveEntry(entry, action === "up" ? -1 : 1);
        }
    }

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

    function readFilterInputs() {
        const values = {};
        Object.entries(FILTER_FIELDS).forEach(([key, id]) => {
            values[key] = document.getElementById(id)?.value || "";
        });
        return values;
    }

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

    function filterLabels(values) {
        const opdLabel = document.getElementById(FILTER_FIELDS.opd_pengelola)?.selectedOptions[0]?.textContent;
        return [values.kabupaten, values.tahun, values.opd_pengelola ? opdLabel || values.opd_pengelola : ""].filter(Boolean);
    }

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

    function renderFilterState() {
        const count = Object.values(readFilterInputs()).filter(Boolean).length;
        el.filterCount.textContent = count ? String(count) : "";
        el.filterCount.classList.toggle("hidden", count === 0);
        el.filterToggle.classList.toggle("is-active", count > 0);
    }

    // ==================== Modal Katalog Data ====================

    function isCatalogOpen() {
        return el.modal && !el.modal.classList.contains("hidden");
    }

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

    function cardHtml(entry) {
        const isSelected = state.staged.has(entry.key);
        const isSearching = state.searchTerm.trim() !== "";
        // Saat kelompok dibuka, jalurnya sudah terlihat di judul/judul bagian, jadi tidak diulang.
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
     * Saat satu kelompok induk dipilih (tanpa sub kategori/pencarian), kartu dikelompokkan
     * per sub kategori: dataset langsung milik induk dulu, lalu tiap sub kategori.
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

    async function applySelection() {
        if (state.isApplying) {
            return;
        }
        state.isApplying = true;
        el.apply.disabled = true;

        const toRemove = getActiveEntries().filter((entry) => !state.staged.has(entry.key));
        const toAdd = state.entries.filter((entry) => state.staged.has(entry.key) && !isActive(entry));
        state.appliedFilters = readFilterInputs();

        closeCatalog();
        try {
            if (toRemove.length) {
                deactivate(toRemove);
            }
            if (toAdd.length || state.activeKeys.length) {
                document.getElementById("sidebar-layer")?.classList.remove("hidden");
            }
            if (toAdd.length) {
                await activate(toAdd);
            }
            refreshDependentPanels();
        } finally {
            state.isApplying = false;
            el.apply.disabled = false;
        }
    }

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
                    // Penyimpanan preferensi tampilan bersifat opsional.
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
        // Selesai menggeser: samakan slider di panel Layer Tools.
        layerList?.addEventListener("change", (event) => {
            if (event.target.matches("[data-layer-opacity]")) {
                updateLayerToolsPanel();
            }
        });
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
        open: openCatalog,
        close: closeCatalog,
    };
})();
