/**
 * Detail fitur dari tombol "Detail" di popup peta.
 *
 * Titik (Point/MultiPoint) dibuka sebagai modal di tengah; area/garis sebagai panel kanan
 * yang menutupi sidebar & tombol kontrol kanan. Urutan tumpukan (lihat peta.css):
 * modal titik > panel area/garis > sidebar.
 */
(function () {
    // Properti teknis yang tidak ditampilkan sebagai atribut.
    const HIDDEN_KEYS = new Set(["id", "uuid", "data_type", "sub_type", "kategori_id", "kategori", "icon", "warna", "is_marker", "gambar", "gambar_list", "style_override", "deskripsi"]);
    // Nilai atribut berupa URL gambar ikut masuk galeri foto.
    const IMAGE_URL = /^https?:\/\/\S+\.(jpe?g|png|gif|webp|avif)(\?\S*)?$/i;
    // Ditampilkan di bagian "Informasi Data", terpisah dari tabel atribut.
    const INFO_FIELDS = [
        ["tahun", "Tahun"],
        ["sumber_data", "Sumber Data"],
        ["opd_pengelola", "OPD Pengelola"],
        ["tanggal_data", "Tanggal Data"],
    ];
    // Atribut nama yang dicoba (berurutan) untuk judul panel.
    const TITLE_KEYS = ["NAMOBJ", "NAMA", "nama", "name", "Nama", "KEGIATAN", "kegiatan", "Keterangan", "keterangan", "label"];

    const panels = {};
    let currentLayer = null;
    // Foto per panel yang sedang terbuka, dipakai galeri & lightbox.
    const galleries = { drawer: [], modal: [] };
    let lightbox = null;

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

    // Nama atribut untuk tabel: "desa_kelurahan" → "Desa Kelurahan".
    function humanizeKey(key) {
        const spaced = String(key).replace(/_/g, " ").trim();
        // Kode atribut ber-huruf besar (ORDE01, KKOP 1) dibiarkan apa adanya.
        return spaced === spaced.toLowerCase() ? spaced.replace(/\b\w/g, (char) => char.toUpperCase()) : spaced;
    }

    // Nilai atribut siap tampil (sudah di-escape): angka, ya/tidak, objek JSON, atau tautan.
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

    // Judul panel: atribut nama pertama yang terisi, atau nama mapset.
    function featureTitle(props, fallback) {
        const key = TITLE_KEYS.find((name) => !isEmpty(props[name]));
        return key ? String(props[key]) : fallback || "Detail Fitur";
    }

    // Koordinat yang ditampilkan: titik itu sendiri, atau tengah batas area/garis.
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

    /**
     * Foto dokumentasi: daftar dari server (gambar fitur + data lama), lalu atribut berisi URL gambar.
     */
    function featureImages(props) {
        const urls = [...(Array.isArray(props.gambar_list) ? props.gambar_list : []), props.gambar];
        Object.entries(props).forEach(([key, value]) => {
            if (!HIDDEN_KEYS.has(key) && typeof value === "string" && IMAGE_URL.test(value.trim())) {
                urls.push(value.trim());
            }
        });
        return [...new Set(urls.filter(Boolean))];
    }

    // Galeri foto dengan tombol sebelumnya/berikutnya bila lebih dari satu foto.
    function galleryHtml(images, title) {
        if (images.length === 0) {
            return "";
        }
        const multiple = images.length > 1;
        return `
            <div class="feature-gallery" data-gallery data-index="0">
                <button type="button" class="feature-gallery-image" data-gallery-zoom aria-label="Perbesar foto">
                    <img src="${escapeHtml(images[0])}" alt="Foto ${escapeHtml(title)}" loading="lazy">
                    <span class="feature-gallery-zoom"><i class="bi bi-arrows-fullscreen"></i></span>
                </button>
                ${multiple ? `
                    <button type="button" class="feature-gallery-nav is-prev" data-gallery-step="-1" aria-label="Foto sebelumnya"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="feature-gallery-nav is-next" data-gallery-step="1" aria-label="Foto berikutnya"><i class="bi bi-chevron-right"></i></button>
                    <span class="feature-gallery-counter" data-gallery-counter>1 / ${images.length}</span>` : ""}
            </div>`;
    }

    function rowsHtml(rows) {
        return rows
            .map(([label, value]) => `<tr><th scope="row">${escapeHtml(label)}</th><td>${formatValue(value)}</td></tr>`)
            .join("");
    }

    // Satu bagian berjudul berisi tabel label–nilai; kosong bila tidak ada baris.
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

    // Isi panel detail. kind = "modal" (titik) atau "drawer" (area/garis).
    function render(feature, layer, kind) {
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

        const images = featureImages(props);
        galleries[kind] = images;
        // Titik: foto paling atas; area/garis: di bagian Foto Dokumentasi di bawah atribut.
        const topGallery = kind === "modal" ? galleryHtml(images, title) : "";
        const photo = kind === "drawer" && images.length
            ? `<section class="feature-section">
                    <h4><i class="bi bi-camera"></i> Foto Dokumentasi</h4>
                    ${galleryHtml(images, title)}
                </section>`
            : "";

        const detailUrl = props.uuid && window.MARIMOI_FEATURE_DETAIL_URL_TEMPLATE
            ? window.MARIMOI_FEATURE_DETAIL_URL_TEMPLATE.replace(":uuid", encodeURIComponent(props.uuid))
            : null;

        return `
            ${topGallery}
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
                <button type="button" class="catalog-btn-outline" data-feature-zoom><i class="bi bi-zoom-in"></i> Perbesar ke fitur</button>
                ${detailUrl ? `<a href="${escapeHtml(detailUrl)}" class="catalog-btn-primary" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Halaman Detail</a>` : ""}
            </div>`;
    }

    // Tombol "Perbesar ke fitur".
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

    // Pindah foto di galeri panel (indeks berputar dari akhir ke awal).
    function showGalleryImage(gallery, images, index) {
        const total = images.length;
        const next = ((index % total) + total) % total;
        gallery.dataset.index = String(next);
        gallery.querySelector("img").src = images[next];
        const counter = gallery.querySelector("[data-gallery-counter]");
        if (counter) {
            counter.textContent = `${next + 1} / ${total}`;
        }
    }

    // Lightbox (foto diperbesar) dibuat sekali saat pertama dipakai, lalu dipakai ulang.
    function ensureLightbox() {
        if (lightbox) {
            return lightbox;
        }
        const root = document.createElement("div");
        root.className = "feature-lightbox hidden";
        root.setAttribute("role", "dialog");
        root.setAttribute("aria-modal", "true");
        root.setAttribute("aria-label", "Foto diperbesar");
        root.innerHTML = `
            <button type="button" class="feature-lightbox-close" data-lightbox-close aria-label="Tutup foto"><i class="bi bi-x-lg"></i></button>
            <button type="button" class="feature-lightbox-nav is-prev" data-lightbox-step="-1" aria-label="Foto sebelumnya"><i class="bi bi-chevron-left"></i></button>
            <img alt="">
            <button type="button" class="feature-lightbox-nav is-next" data-lightbox-step="1" aria-label="Foto berikutnya"><i class="bi bi-chevron-right"></i></button>
            <span class="feature-lightbox-counter"></span>`;
        document.body.appendChild(root);

        lightbox = { root, images: [], index: 0 };
        root.addEventListener("click", (event) => {
            const step = event.target.closest("[data-lightbox-step]");
            if (step) {
                showLightboxImage(lightbox.index + Number(step.dataset.lightboxStep));
            } else if (event.target === root || event.target.closest("[data-lightbox-close]")) {
                closeLightbox();
            }
        });
        return lightbox;
    }

    function showLightboxImage(index) {
        const total = lightbox.images.length;
        lightbox.index = ((index % total) + total) % total;
        lightbox.root.querySelector("img").src = lightbox.images[lightbox.index];
        lightbox.root.querySelector(".feature-lightbox-counter").textContent = total > 1 ? `${lightbox.index + 1} / ${total}` : "";
        lightbox.root.querySelectorAll("[data-lightbox-step]").forEach((button) => {
            button.hidden = total < 2;
        });
    }

    function openLightbox(images, index) {
        ensureLightbox();
        lightbox.images = images;
        showLightboxImage(index);
        lightbox.root.classList.remove("hidden");
        lightbox.root.querySelector("[data-lightbox-close]").focus();
    }

    function closeLightbox() {
        lightbox?.root.classList.add("hidden");
    }

    function isLightboxOpen() {
        return lightbox && !lightbox.root.classList.contains("hidden");
    }

    // Tutup panel tertentu, atau keduanya bila kind tidak diisi.
    function close(kind) {
        const targets = kind ? [panels[kind]] : Object.values(panels);
        targets.forEach((panel) => panel?.root.classList.add("hidden"));
        if (Object.values(panels).every((panel) => panel.root.classList.contains("hidden"))) {
            currentLayer = null;
        }
    }

    // Buka detail fitur: titik sebagai modal tengah, area/garis sebagai panel kanan.
    function open(feature, layer) {
        const kind = isPoint(feature) ? "modal" : "drawer";
        const panel = panels[kind];
        if (!panel) {
            return;
        }
        currentLayer = layer;
        panel.body.innerHTML = render(feature, layer, kind);
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
                const gallery = event.target.closest("[data-gallery]");
                const step = event.target.closest("[data-gallery-step]");
                if (gallery && step) {
                    showGalleryImage(gallery, galleries[kind], Number(gallery.dataset.index) + Number(step.dataset.galleryStep));
                    return;
                }
                if (gallery && event.target.closest("[data-gallery-zoom]")) {
                    openLightbox(galleries[kind], Number(gallery.dataset.index));
                    return;
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
            if (isLightboxOpen()) {
                if (event.key === "Escape") {
                    closeLightbox();
                } else if (event.key === "ArrowLeft" || event.key === "ArrowRight") {
                    showLightboxImage(lightbox.index + (event.key === "ArrowRight" ? 1 : -1));
                }
                return;
            }
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
