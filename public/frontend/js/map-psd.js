// map-app.js
console.log("map-app.js loaded");

const mapConfig = {
    center: [0.735485, 128.028201],
    zoom: 7,
    baseMapsList: [
        {
            id: "osm",
            label: "OpenStreetMap",
            url: "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
        },
        {
            id: "google-roadmap",
            label: "Google Map (ROADMAP)",
            url: "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
        },
        {
            id: "google-hybrid",
            label: "Google Map (Hybrid)",
            url: "https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
        },
        {
            id: "google-terrain",
            label: "Google Map (Terrain)",
            url: "https://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}",
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
        },
        {
            id: "esri-world-imagery",
            label: "ESRI World Imagery",
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
        },
    ],
};

const map = L.map("map", {
    zoomControl: true,
    attributionControl: true,
}).setView(mapConfig.center, mapConfig.zoom);

let layerGroups = {};
let currentBaseMap = null;
let kategoriWarnaMap = {}; // ✅ Tambahkan ini
// console.log(kategori);
function getStyleForCategory(kategori) {
    const warna = kategoriWarnaMap[kategori] || "#ECE6D6";
    return {
        color: warna, // outline color (untuk LineString dan Polygon)
        weight: 2, // ketebalan garis
        fillColor: warna, // isi warna (hanya dipakai Polygon)
        fillOpacity: 0.4, // opacity isi (Polygon saja)
        opacity: 1, // opacity garis
    };
}

function generateLegend() {
    const legendContainer = document.getElementById("legend-content");
    if (!legendContainer) return;

    legendContainer.innerHTML = ""; // Kosongkan dulu

    const added = new Set();

    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        Object.keys(sublayers).forEach((sub) => {
            if (!added.has(sub)) {
                const color =
                    kategoriWarnaMap[sub] ||
                    kategoriWarnaMap[kategori] ||
                    "#ccc";
                legendContainer.innerHTML += `
                    <div class="d-flex align-items-center mb-2">
                        <div style="width: 16px; height: 16px; background-color: ${color}; border: 1px solid #333; margin-right: 8px;"></div>
                        <span>${sub}</span>
                    </div>
                `;
                added.add(sub);
            }
        });
    });
}

function bindPopupContent(feature, layer) {
    const props = feature.properties;
    let content = `<div class="p-2" style="max-width: 300px;">
        <h5 class="fw-bold text-primary">${props.kategori || "Feature"}</h5>`;

    // Menampilkan properti dalam bentuk tabel dua kolom, kecuali id, kategori, dan kategori id
    content += `<hr><table class="table table-sm table-borderless" style="font-size: 12px; width: 100%;">`;
    Object.entries(props).forEach(([key, value]) => {
        if (
            value &&
            ![
                "geometry",
                "ID",
                "Kategori Id",
                "id",
                "kategori",
                "kategori id",
                "kategori_id",
            ].includes(key.toLowerCase())
        ) {
            const label = key
                .replace(/_/g, " ")
                .replace(/\b\w/g, (l) => l.toUpperCase());
            content += `<tr><td class="fw-medium">${label}</td><td>${value}</td></tr>`;
        }
    });
    content += `</table>`;

    // Menambahkan detail geometry dalam format tabel dua kolom
    const geom = feature.geometry;
    if (geom) {
        const type = geom.type;
        content += `<hr><table class="table table-sm table-borderless" style="font-size: 12px; width: 100%;">`;
        content += `<tr><td class="fw-medium">Geometry</td><td>${type}</td></tr>`;

        if (type === "LineString" && Array.isArray(geom.coordinates)) {
            let length = 0;
            for (let i = 1; i < geom.coordinates.length; i++) {
                const [lon1, lat1] = geom.coordinates[i - 1];
                const [lon2, lat2] = geom.coordinates[i];
                const R = 6371; // Radius Earth in km
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
            content += `<tr><td class="fw-medium">Panjang</td><td>${length.toFixed(
                2
            )} km</td></tr>`;
        }

        // Tampilkan koordinat tengah
        let center;
        if (geom.type === "Point") {
            center = geom.coordinates;
        } else if (geom.type === "LineString") {
            const mid = Math.floor(geom.coordinates.length / 2);
            center = geom.coordinates[mid];
        } else if (geom.type === "Polygon") {
            const poly = geom.coordinates[0];
            const mid = Math.floor(poly.length / 2);
            center = poly[mid];
        }

        if (center) {
            content += `<tr><td class="fw-medium">Koordinat</td><td>${center[1].toFixed(
                5
            )}, ${center[0].toFixed(5)}</td></tr>`;
        }

        content += `</table>`;
    }

    // Tambahkan tombol "Lihat Detail"
    const id = props.id || "";
    content += `
        <div class="d-flex justify-content-between mt-3">
            <button class="btn text-white btn-sm btn-warning zoomToBtn" data-lat="${feature.geometry.coordinates[1]}" data-lng="${feature.geometry.coordinates[0]}">Zoom To</button>
            <a href="/detail-psd/${id}" class="btn text-white btn-sm btn-warning">Lihat Detail</a>
        </div>
    </div>`;

    layer.bindPopup(content);

    layer.on("popupopen", function () {
        const popupNode = layer.getPopup().getElement();
        const zoomButton = popupNode.querySelector(".zoomToBtn");
        if (zoomButton) {
            zoomButton.addEventListener("click", function () {
                const lat = parseFloat(this.getAttribute("data-lat"));
                const lng = parseFloat(this.getAttribute("data-lng"));
                layer._map.setView([lat, lng], 15);
            });
        }
    });
}

function showAlert(message, type = "info") {
    console.log(`${type}: ${message}`);
    const toastContainer = document.getElementById("toast-container");
    if (!toastContainer) return;

    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");
    toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>`;
    toastContainer.appendChild(toast);
    new bootstrap.Toast(toast).show();
    toast.addEventListener("hidden.bs.toast", () => toast.remove());
}

function changeBaseMap(baseMapId) {
    if (currentBaseMap) map.removeLayer(currentBaseMap);
    const config = mapConfig.baseMapsList.find((bm) => bm.id === baseMapId);
    if (config) {
        currentBaseMap = L.tileLayer(config.url, {
            subdomains: config.subdomains || [],
            minZoom: 4,
            maxZoom: 20,
        }).addTo(map);
    }
}

// initmap bisa
async function initMap() {
    try {
        const response = await fetch("/psd-geojson");
        const geoJsonData = await response.json();

        if (!geoJsonData?.features?.length) {
            return showAlert("Data GeoJSON kosong", "warning");
        }

        // ⬇️ Masukkan di sini
        kategoriWarnaMap = {};
        geoJsonData.all_categories.forEach((cat) => {
            if (cat.nama && cat.warna) {
                kategoriWarnaMap[cat.nama] = cat.warna;
            }
        });

        // Reset layer lama
        Object.values(layerGroups).forEach((group) => {
            Object.values(group).forEach((layer) => {
                if (map.hasLayer(layer)) map.removeLayer(layer);
            });
        });

        layerGroups = {};

        // Ambil semua nama subkategori dari root_categories
        const allSubNames = new Set();
        geoJsonData.root_categories.forEach((cat) => {
            cat.children?.forEach((child) => {
                if (child?.nama) {
                    allSubNames.add(child.nama);
                }
            });
        });

        // Siapkan struktur layerGroups, tapi hanya untuk root (yang bukan anak)
        geoJsonData.root_categories.forEach((cat) => {
            const kategori = cat.nama;
            // Jika ini adalah subkategori, cari parent-nya
            if (allSubNames.has(kategori)) {
                const parent = geoJsonData.all_categories.find(
                    (cat) => cat.nama === kategori
                )?.parent?.nama;
                if (parent && layerGroups[parent]?.[kategori]) {
                    L.geoJSON(feature, {
                        style: getStyleForCategory(kategori),
                        onEachFeature: (f, l) => bindPopupContent(f, l),
                    }).addTo(layerGroups[parent][kategori]);
                }
                return; // Sudah ditangani, tidak lanjut ke bawah
            }

            layerGroups[kategori] = {};
            cat.children?.forEach((sub) => {
                layerGroups[kategori][sub.nama] = L.layerGroup();
            });
        });

        // Proses fitur ke dalam layer
        geoJsonData.features.forEach((feature) => {
            const kategori = (feature.properties?.kategori || "").trim();
            let subkategori = (feature.properties?.subkategori || "").trim();

            if (!kategori) return;
            if (!subkategori) subkategori = kategori;

            // Jika ini adalah subkategori, cari parent-nya
            if (allSubNames.has(kategori)) {
                const parent = geoJsonData.all_categories.find(
                    (cat) => cat.nama === kategori
                )?.parent?.nama;
                if (parent && layerGroups[parent]?.[kategori]) {
                    L.geoJSON(feature, {
                        style: getStyleForCategory(kategori),
                        onEachFeature: (f, l) => bindPopupContent(f, l),
                    }).addTo(layerGroups[parent][kategori]);
                }
                return; // Sudah ditangani, tidak lanjut ke bawah
            }

            if (!layerGroups[kategori]) layerGroups[kategori] = {};
            if (!layerGroups[kategori][subkategori]) {
                layerGroups[kategori][subkategori] = L.layerGroup();
            }

            L.geoJSON(feature, {
                style: getStyleForCategory(subkategori),
                onEachFeature: (f, l) => bindPopupContent(f, l),
            }).addTo(layerGroups[kategori][subkategori]);
        });

        // Hapus kategori dan subkategori yang tidak punya layer (kosong)
        Object.entries(layerGroups).forEach(([kat, subs]) => {
            Object.entries(subs).forEach(([sub, layer]) => {
                if (layer.getLayers().length === 0) {
                    delete layerGroups[kat][sub]; // hapus subkategori kosong
                }
            });
            // Jika semua subkategori sudah dihapus, hapus kategori juga
            if (Object.keys(layerGroups[kat]).length === 0) {
                delete layerGroups[kat];
            }
        });
        updateLayerList();
        generateLegend();
    } catch (error) {
        console.error("Error:", error);
        showAlert("Gagal memuat data peta", "danger");
    }
}

function updateLayerList() {
    const container = document.getElementById("layer-list");
    container.innerHTML = "";

    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        const groupId = `group-${kategori.replace(/\s+/g, "-")}`;
        const groupWrapper = document.createElement("div");
        groupWrapper.classList.add("layer-group");

        const rootId = `root-${kategori.replace(/\s+/g, "-")}`;
        const header = document.createElement("div");
        header.className =
            "d-flex align-items-center justify-content-between px-2 py-1 bg-light border";

        const leftSection = document.createElement("div");
        leftSection.className = "d-flex align-items-center";

        const toggleBtn = document.createElement("button");
        toggleBtn.className = "btn btn-sm btn-link text-decoration-none";
        toggleBtn.innerHTML = `<i class="bi bi-caret-down-fill"></i>`;
        toggleBtn.onclick = () => {
            const list = document.getElementById(groupId);
            list.style.display =
                list.style.display === "none" ? "block" : "none";
            toggleBtn.innerHTML =
                list.style.display === "none"
                    ? `<i class="bi bi-caret-right-fill"></i>`
                    : `<i class="bi bi-caret-down-fill"></i>`;
        };

        const checkboxRoot = document.createElement("input");
        checkboxRoot.type = "checkbox";
        checkboxRoot.className = "form-check-input my-0 mx-2";
        checkboxRoot.id = rootId;

        const labelRoot = document.createElement("label");
        labelRoot.className = "form-check-label small";
        labelRoot.htmlFor = rootId;
        labelRoot.textContent = kategori;

        // Control all sublayers from root checkbox
        checkboxRoot.addEventListener("change", () => {
            const isChecked = checkboxRoot.checked;
            Object.entries(sublayers).forEach(([subname, layer]) => {
                const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
                const checkbox = document.getElementById(subId);
                if (checkbox) checkbox.checked = isChecked;
                isChecked ? map.addLayer(layer) : map.removeLayer(layer);
            });
        });

        leftSection.appendChild(toggleBtn);
        leftSection.appendChild(checkboxRoot);
        leftSection.appendChild(labelRoot);
        header.appendChild(leftSection);
        groupWrapper.appendChild(header);

        const subLayerList = document.createElement("div");
        subLayerList.id = groupId;
        subLayerList.style.paddingLeft = "1.5rem";

        Object.entries(sublayers).forEach(([subname, layer]) => {
            // Hindari sub sama dengan kategori agar tidak ganda
            const hasChildren = Object.keys(sublayers).length > 1;
            if (subname === kategori && hasChildren) return;
            // if (subname === kategori) return;

            const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
            const row = document.createElement("div");
            row.className = "d-flex align-items-center py-1";

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.className = "form-check-input my-0 me-2";
            checkbox.id = subId;
            checkbox.checked = false;

            checkbox.addEventListener("change", () => {
                checkbox.checked ? map.addLayer(layer) : map.removeLayer(layer);
            });

            const label = document.createElement("label");
            label.className = "form-check-label small";
            label.htmlFor = subId;
            label.textContent = subname;

            row.appendChild(checkbox);
            row.appendChild(label);
            subLayerList.appendChild(row);
        });

        groupWrapper.appendChild(subLayerList);
        container.appendChild(groupWrapper);
    });
}

function setupUI() {
    document.getElementById("transparency")?.addEventListener("input", (e) => {
        const val = e.target.value / 100;
        Object.values(layerGroups).forEach((group) => {
            Object.values(group).forEach((layerGroup) => {
                if (layerGroup.eachLayer) {
                    layerGroup.eachLayer((layer) => {
                        if (layer.setStyle) {
                            layer.setStyle({
                                fillOpacity: val,
                                opacity: val,
                            });
                        }
                    });
                }
            });
        });
    });

    const basemapList = document.getElementById("basemap-list");
    mapConfig.baseMapsList.forEach((bm, i) => {
        basemapList.innerHTML += `
      <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="radio" role="switch" name="basemap-radio" id="bm-${
            bm.id
        }" value="${bm.id}" ${i === 4 ? "checked" : ""}>
        <label class="form-check-label" for="bm-${bm.id}">${bm.label}</label>
      </div>`;
    });

    basemapList.addEventListener("change", (e) => {
        if (e.target.name === "basemap-radio") changeBaseMap(e.target.value);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initMap();
    changeBaseMap("esri-world-imagery");
    setupUI();

    // Sidebar toggle buttons
    const btnToggleLayer = document.getElementById("btn-toggle-sidebar-layer");
    const btnToggleBasemap = document.getElementById(
        "btn-toggle-sidebar-basemap"
    );
    const btnToggleLegend = document.getElementById(
        "btn-toggle-sidebar-legend"
    );
    const btnToggleDownload = document.getElementById(
        "btn-toggle-sidebar-download"
    );
    const btnFullscreen = document.getElementById("btn-fullscreen");
    const btnDefaultZoom = document.getElementById("btn-default-zoom");

    // Panduan awal dengan modal dan highlight tombol kontrol
    const guideModal = document.getElementById("guideModal");
    const guideSteps = document.querySelectorAll(".guide-step");
    const btnPrev = document.getElementById("btnPrev");
    const btnNext = document.getElementById("btnNext");

    let currentStep = 1;
    const totalSteps = guideSteps.length;
    const controlButtons = [
        document.getElementById("btn-toggle-sidebar-help"),
        document.getElementById("btn-toggle-sidebar-legend"),
        document.getElementById("btn-toggle-sidebar-basemap"),
        document.getElementById("btn-toggle-sidebar-layer"),
        document.getElementById("btn-toggle-sidebar-download"),
        document.getElementById("btn-fullscreen"),
        document.getElementById("btn-default-zoom"),
    ];

    // Modal Help Control
    const btnToggleHelp = document.getElementById("btn-toggle-sidebar-help");
    const btnSkip = document.getElementById("btnSkip");

    const sidebarLayer = document.getElementById("sidebar-layer");
    const sidebarBasemap = document.getElementById("sidebar-basemap");
    const sidebarLegend = document.getElementById("sidebar-legend");
    const sidebarDownload = document.getElementById("sidebar-download");
    const modalHelp = document.getElementById("guideModal");

    const btnCloseLayer = document.getElementById("btn-close-sidebar-layer");
    const btnCloseBasemap = document.getElementById(
        "btn-close-sidebar-basemap"
    );
    const btnCloseLegend = document.getElementById("btn-close-sidebar-legend");
    const btnCloseDownload = document.getElementById(
        "btn-close-sidebar-download"
    );

    function closeAllSidebars() {
        sidebarLayer.style.display = "none";
        sidebarBasemap.style.display = "none";
        sidebarLegend.style.display = "none";
        sidebarDownload.style.display = "none";
        modalHelp.style.display = "none";
    }

    // Fungsi untuk mengecek apakah sudah dalam mode fullscreen
    function isFullscreen() {
        return (
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement
        );
    }

    // Fungsi untuk masuk dan keluar dari mode fullscreen
    function toggleFullscreen() {
        if (isFullscreen()) {
            // Keluar dari fullscreen
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                // Untuk Safari
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                // Untuk Firefox
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                // Untuk IE/Edge
                document.msExitFullscreen();
            }
        } else {
            // Masuk ke fullscreen
            const elem = document.documentElement; // Bisa diubah dengan elemen lain jika perlu
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                // Untuk Safari
                elem.webkitRequestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                // Untuk Firefox
                elem.mozRequestFullScreen();
            } else if (elem.msRequestFullscreen) {
                // Untuk IE/Edge
                elem.msRequestFullscreen();
            }
        }
    }

    function showStep(step) {
        guideSteps.forEach((stepDiv) => {
            stepDiv.classList.toggle(
                "d-none",
                parseInt(stepDiv.dataset.step) !== step
            );
        });

        btnPrev.disabled = step === 1;
        btnNext.textContent = step === totalSteps ? "Finish" : "Next";

        // Hapus highlight dari semua tombol kontrol
        controlButtons.forEach((btn) => {
            btn.classList.remove("highlighted-control");
            btn.style.position = "";
            btn.style.zIndex = "";
            btn.style.padding = "";
        });

        switch (step) {
            case 1:
                break;
            case 2:
                break;
            case 3:
                controlButtons[0]?.classList.add("highlighted-control");
                break;
            case 4:
                controlButtons[1]?.classList.add("highlighted-control");
                break;
            case 5:
                controlButtons[2]?.classList.add("highlighted-control");
                break;
            case 6:
                controlButtons[3]?.classList.add("highlighted-control");
                break;
            case 7:
                controlButtons[4]?.classList.add("highlighted-control");
                break;
            case 8:
                controlButtons[5]?.classList.add("highlighted-control");
                break;
            case 9:
                controlButtons[6]?.classList.add("highlighted-control");
                break;
            default:
                break;
        }
    }

    btnToggleLayer?.addEventListener("click", () => {
        const isVisible = sidebarLayer.style.display === "block";
        closeAllSidebars();
        sidebarLayer.style.display = isVisible ? "none" : "block";
    });

    btnToggleBasemap?.addEventListener("click", () => {
        const isVisible = sidebarBasemap.style.display === "block";
        closeAllSidebars();
        sidebarBasemap.style.display = isVisible ? "none" : "block";
    });

    btnToggleLegend?.addEventListener("click", () => {
        const isVisible = sidebarLegend.style.display === "block";
        closeAllSidebars();
        sidebarLegend.style.display = isVisible ? "none" : "block";
    });

    btnToggleDownload?.addEventListener("click", () => {
        const isVisible = sidebarDownload.style.display === "block";
        closeAllSidebars();
        sidebarDownload.style.display = isVisible ? "none" : "block";
    });

    btnToggleHelp?.addEventListener("click", () => {
        const modalInstance =
            bootstrap.Modal.getInstance(modalHelp) ||
            new bootstrap.Modal(modalHelp);
        const isVisible = modalInstance._isShown;
        closeAllSidebars();
        if (!isVisible) {
            currentStep = 1;
            showStep(currentStep);
            // Hapus overlay bebas di sekitar tombol
            controlButtons.forEach((btn) => {
                btn.style.position = "";
                btn.style.zIndex = "";
                btn.style.padding = "";
            });
            modalInstance.show();
        } else {
            // Hapus overlay bebas saat modal ditutup
            controlButtons.forEach((btn) => {
                btn.style.position = "";
                btn.style.zIndex = "";
                btn.style.padding = "";
            });
            modalInstance.hide();
        }
    });

    btnCloseLayer?.addEventListener("click", () => {
        sidebarLayer.style.display = "none";
    });

    btnCloseBasemap?.addEventListener("click", () => {
        sidebarBasemap.style.display = "none";
    });

    btnCloseLegend?.addEventListener("click", () => {
        sidebarLegend.style.display = "none";
    });

    btnCloseDownload?.addEventListener("click", () => {
        sidebarDownload.style.display = "none";
    });

    btnSkip?.addEventListener("click", () => {
        const modalInstance = bootstrap.Modal.getInstance(guideModal);
        if (modalInstance) {
            modalInstance.hide();
        }
        controlButtons.forEach((btn) =>
            btn.classList.remove("highlighted-control")
        );
    });

    btnPrev.addEventListener("click", () => {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    btnNext.addEventListener("click", () => {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        } else {
            // Tutup modal dengan Bootstrap method
            const modalInstance = bootstrap.Modal.getInstance(guideModal);
            modalInstance.hide();
            controlButtons.forEach((btn) =>
                btn.classList.remove("highlighted-control")
            );
        }
    });

    // Menambahkan event listener pada tombol fullscreen
    btnFullscreen.addEventListener("click", toggleFullscreen);

    // Menambahkan event listener pada tombol Default Zoom
    btnDefaultZoom.addEventListener("click", function () {
        map.setView(mapConfig.center, mapConfig.zoom); // Mengatur ulang peta ke koordinat default dan zoom level
    });

    // Tampilkan modal dengan Bootstrap method
    const modalInstance = new bootstrap.Modal(guideModal);
    modalInstance.show();
    showStep(currentStep);

    // Layer search filter
    const layerSearchInput = document.getElementById("layer-search");
    const layerListDiv = document.getElementById("layer-list");

    layerSearchInput?.addEventListener("input", (e) => {
        const searchTerm = e.target.value.toLowerCase();
        if (!layerListDiv) return;

        // Filter checkboxes by label text
        const checkboxes = layerListDiv.querySelectorAll(
            "input[type='checkbox']"
        );
        checkboxes.forEach((checkbox) => {
            const label = layerListDiv.querySelector(
                `label[for='${checkbox.id}']`
            );
            if (label) {
                const text = label.textContent.toLowerCase();
                const match = text.includes(searchTerm);
                checkbox.parentElement.style.display = match ? "flex" : "none";
                if (!match && checkbox.checked) {
                    map.removeLayer(layerGroups[checkbox.dataset.kategori]);
                    checkbox.checked = false;
                }
            }
        });
    });
});
