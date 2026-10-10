/**
 * Panduan Peta Interaktif: tur berlangkah yang menyorot kontrol peta satu per satu
 * (dibuka lewat tombol Bantuan, dan otomatis sekali pada kunjungan pertama).
 */
(function () {
    const STORAGE_KEY = "marimoi.mapGuideSeen";
    const SPOTLIGHT_PADDING = 6;
    const EDGE = 12;

    const STEPS = [
        {
            icon: "bi-map",
            title: "Selamat datang di Peta Interaktif",
            text: "Jelajahi data spasial Maluku Utara: pilih layer dari katalog, lihat detail setiap fitur, lalu bagikan tampilan peta Anda. Ikuti panduan singkat ini untuk mengenal kontrolnya.",
        },
        {
            target: "#btn-toggle-sidebar-layer",
            icon: "bi-layers-fill",
            title: "Layer Peta",
            text: "Buka daftar Layer Aktif untuk mengatur urutan, transparansi, dan visibilitas layer. Bila belum ada layer, tombol ini langsung membuka Katalog Peta untuk memilih mapset, menyaringnya dengan Filter, lalu Terapkan Pilihan.",
        },
        {
            target: "#btn-open-analysis",
            icon: "bi-bar-chart-line-fill",
            title: "Analisis Peta",
            text: "Lihat ringkasan layer aktif: jumlah fitur, luas dan panjang, sebaran per kabupaten/kota, tren per tahun, OPD pengelola, hingga wawasan singkat. Aktif bila minimal satu layer aktif, dan bisa dicetak atau disimpan sebagai PDF.",
        },
        {
            target: "#map-search-bar",
            icon: "bi-search",
            title: "Pencarian",
            text: "Cari data pada layer yang sedang aktif, misalnya nama lokasi atau kegiatan. Pilih hasilnya untuk langsung menuju fitur tersebut di peta.",
        },
        {
            target: "#btn-toggle-sidebar-legend",
            icon: "bi-list-ul",
            title: "Legenda",
            text: "Lihat arti warna dan simbol setiap layer aktif, termasuk warna khusus per fitur.",
        },
        {
            target: "#btn-toggle-sidebar-basemap",
            icon: "bi-grid-fill",
            title: "Basemap",
            text: "Ganti peta dasar, misalnya jalan, citra satelit, atau topografi.",
        },
        {
            target: "#map .leaflet-top.leaflet-left",
            icon: "bi-zoom-in",
            title: "Zoom & Navigasi",
            text: "Perbesar atau perkecil peta, masuk ke tampilan layar penuh, dan kembali ke tampilan awal. Anda juga bisa memakai scroll mouse atau gerakan cubit pada touchpad.",
        },
        {
            icon: "bi-cursor-fill",
            title: "Detail Fitur",
            text: "Klik fitur di peta untuk membuka ringkasannya. Tombol Detail menampilkan atribut lengkap beserta foto, dan tombol Halaman Detail membuka halaman khusus fitur tersebut.",
        },
        {
            target: "#nav-control-buttons",
            icon: "bi-share-fill",
            title: "Bagikan Peta",
            text: "Buat tautan yang menyimpan layer, filter, dan posisi peta saat ini untuk dibagikan.",
        },
        {
            target: "#map-bottom-bar",
            icon: "bi-crosshair",
            title: "Koordinat & Skala",
            text: "Pantau koordinat di bawah kursor, tingkat zoom, dan skala jarak peta.",
        },
        {
            target: "#btn-toggle-sidebar-help",
            icon: "bi-info-circle-fill",
            title: "Buka Panduan Kapan Saja",
            text: "Tekan tombol Bantuan ini bila ingin melihat panduan lagi.",
        },
    ];

    const el = {};
    let current = 0;

    // Akses localStorage yang aman bila diblokir browser (mode privat, dsb.).
    function storage(action) {
        try {
            return action(window.localStorage);
        } catch (error) {
            return null;
        }
    }

    // Kotak elemen yang disorot langkah ini; null bila tidak ada atau sedang tersembunyi.
    function visibleTarget(step) {
        const target = step.target ? document.querySelector(step.target) : null;
        if (!target) {
            return null;
        }
        const rect = target.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0 ? rect : null;
    }

    // Lubang sorotan di atas target; tanpa target seluruh layar diredupkan.
    function placeSpotlight(rect) {
        const spot = el.spotlight;
        if (!rect) {
            spot.classList.add("is-empty");
            spot.style.cssText = `left:50%;top:50%;width:0;height:0;`;
            return;
        }
        spot.classList.remove("is-empty");
        spot.style.cssText = `left:${rect.left - SPOTLIGHT_PADDING}px;top:${rect.top - SPOTLIGHT_PADDING}px;width:${rect.width + SPOTLIGHT_PADDING * 2}px;height:${rect.height + SPOTLIGHT_PADDING * 2}px;`;
    }

    /**
     * Kartu di sisi target yang muat (kanan, kiri, bawah, atas); tanpa target, di tengah.
     */
    function placeCard(rect) {
        const card = el.card;
        const width = card.offsetWidth;
        const height = card.offsetHeight;
        const viewWidth = window.innerWidth;
        const viewHeight = window.innerHeight;
        const clampX = (x) => Math.min(Math.max(x, EDGE), viewWidth - width - EDGE);
        const clampY = (y) => Math.min(Math.max(y, EDGE), viewHeight - height - EDGE);

        let left = (viewWidth - width) / 2;
        let top = (viewHeight - height) / 2;

        if (rect) {
            const gap = SPOTLIGHT_PADDING + 14;
            const centerY = rect.top + rect.height / 2 - height / 2;
            const centerX = rect.left + rect.width / 2 - width / 2;
            if (rect.right + gap + width + EDGE <= viewWidth) {
                left = rect.right + gap;
                top = clampY(centerY);
            } else if (rect.left - gap - width - EDGE >= 0) {
                left = rect.left - gap - width;
                top = clampY(centerY);
            } else if (rect.bottom + gap + height + EDGE <= viewHeight) {
                left = clampX(centerX);
                top = rect.bottom + gap;
            } else {
                left = clampX(centerX);
                top = clampY(rect.top - gap - height);
            }
        }

        card.style.left = `${left}px`;
        card.style.top = `${top}px`;
    }

    // Tampilkan isi langkah saat ini lalu atur posisi sorotan & kartu.
    function render() {
        const step = STEPS[current];
        el.icon.innerHTML = `<i class="bi ${step.icon}"></i>`;
        el.progress.textContent = `Langkah ${current + 1} dari ${STEPS.length}`;
        el.title.textContent = step.title;
        el.text.textContent = step.text;
        el.dots.innerHTML = STEPS.map((_, index) => `<span class="${index === current ? "is-current" : ""}"></span>`).join("");
        el.prev.disabled = current === 0;
        el.next.textContent = current === STEPS.length - 1 ? "Selesai" : "Berikutnya";

        const rect = visibleTarget(step);
        placeSpotlight(rect);
        placeCard(rect);
    }

    function go(index) {
        current = Math.min(Math.max(index, 0), STEPS.length - 1);
        render();
        el.next.focus();
    }

    // Mulai panduan dari langkah pertama.
    function open() {
        // Panel samping & katalog ditutup dulu supaya kontrol yang disorot terlihat.
        ["sidebar-layer", "sidebar-basemap", "sidebar-legend"].forEach((id) => document.getElementById(id)?.classList.add("hidden"));
        window.MarimoiCatalog?.close();
        el.root.classList.remove("hidden");
        go(0);
    }

    // Tutup panduan dan tandai sudah dilihat (tidak muncul otomatis lagi).
    function close() {
        el.root.classList.add("hidden");
        storage((store) => store.setItem(STORAGE_KEY, "1"));
        document.getElementById("btn-toggle-sidebar-help")?.focus();
    }

    function isOpen() {
        return el.root && !el.root.classList.contains("hidden");
    }

    document.addEventListener("DOMContentLoaded", () => {
        el.root = document.getElementById("mapGuide");
        if (!el.root) {
            return;
        }
        el.spotlight = el.root.querySelector("[data-guide-spotlight]");
        el.card = el.root.querySelector("[data-guide-card]");
        el.icon = el.root.querySelector("[data-guide-icon]");
        el.progress = el.root.querySelector("[data-guide-progress]");
        el.title = el.root.querySelector("[data-guide-title]");
        el.text = el.root.querySelector("[data-guide-text]");
        el.dots = el.root.querySelector("[data-guide-dots]");
        el.prev = el.root.querySelector("[data-guide-prev]");
        el.next = el.root.querySelector("[data-guide-next]");

        el.prev.addEventListener("click", () => go(current - 1));
        el.next.addEventListener("click", () => (current === STEPS.length - 1 ? close() : go(current + 1)));
        el.root.querySelectorAll("[data-guide-skip]").forEach((button) => button.addEventListener("click", close));
        document.getElementById("btn-toggle-sidebar-help")?.addEventListener("click", open);

        document.addEventListener("keydown", (event) => {
            if (!isOpen()) {
                return;
            }
            if (event.key === "Escape") {
                close();
            } else if (event.key === "ArrowRight") {
                go(current + 1);
            } else if (event.key === "ArrowLeft") {
                go(current - 1);
            }
        });
        window.addEventListener("resize", () => isOpen() && render());

        // Kunjungan pertama: tampilkan sekali setelah kontrol peta selesai dirender.
        if (!storage((store) => store.getItem(STORAGE_KEY)) && !window.MARIMOI_SHARED_STATE) {
            setTimeout(open, 900);
        }
    });

    window.MarimoiGuide = { open, close };
})();
