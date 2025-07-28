// map-app.js - Fixed version based on working original code
console.log("map-app.js loaded");

const mapConfig = {
    weight: 6,
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
let kategoriWarnaMap = {};
let iconMap = {};

function getStyleForCategory(kategori) {
    const warna = kategoriWarnaMap[kategori] || "#ECE6D6";
    return {
        color: warna,
        weight: 5,
        fillColor: warna,
        fillOpacity: 0.4,
        opacity: 1,
    };
}

function generateLegend() {
    const legendContainer = document.getElementById("legend-content");
    if (!legendContainer) return;

    legendContainer.innerHTML = "";
    const added = new Set();

    // console.log("layerGroups:", layerGroups); // tampilkan isi layerGroups
    console.log("kategoriWarnaMap:", kategoriWarnaMap);
    console.log("iconMap:", iconMap);

    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        Object.keys(sublayers).forEach((sub) => {
            if (!added.has(sub)) {
                const color = kategoriWarnaMap[sub] || kategoriWarnaMap[kategori] || "#ccc";
                const icon = iconMap[sub];
                
                if (icon) {
                    legendContainer.innerHTML += `
                        <div class="d-flex align-items-center mb-2">
                            <div class="custom-fa-icon d-flex align-items-center justify-content-center" style="width: 14px; height: 14px; background: transparent; border: none; margin-right: 8px;">
                                <i class="${icon}" style="font-size: 12px; color: ${color}; line-height: 1;"></i>
                            </div>
                            <span style="font-size: 0.85rem;">${sub}</span>
                        </div>
                    `;
                } else {
                    legendContainer.innerHTML += `
                        <div class="d-flex align-items-center mb-2">
                            <div style="width: 14px; height: 14px; background-color: ${color}; border: 1px solid #333; margin-right: 8px;"></div>
                            <span style="font-size: 0.85rem;">${sub}</span>
                        </div>
                    `;
                }

                added.add(sub);
            }
        });
    });
}

function bindPopupContent(feature, layer, urlPath) {
    const props = feature.properties;
    let content = `<div class="py-2" style="max-width: 250px;">
        <h5 class="fw-bold text-primary" style="font-size: 14px;">${props.kategori || "Feature"}</h5>
        <img src="frontend/img/kantor-gub-malut.jpeg" alt="Template Image" style="width: 100%; max-height: 150px; object-fit: cover; margin-bottom: 5px;">`;

    content += `<hr><table class="table table-sm table-borderless" style="font-size: 10px; width: 100%;">`;
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

    const geom = feature.geometry;
    if (geom) {
        const type = geom.type;
        content += `<hr><table class="table table-sm table-borderless" style="font-size: 10px; width: 100%;">`;
        content += `<tr><td class="fw-medium">Geometry</td><td>${type}</td></tr>`;

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
            content += `<tr><td class="fw-medium">Panjang</td><td>${length.toFixed(
                2
            )} km</td></tr>`;
        }

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

    const id = props.id || "";
    content += `
        <div class="d-flex justify-content-between mt-3">
            <button class="btn text-white btn-sm btn-warning zoomToBtn" data-lat="${feature.geometry.coordinates[1]}" data-lng="${feature.geometry.coordinates[0]}">Zoom To</button>
            <a href="${urlPath}/${id}" class="btn text-white btn-sm btn-warning">Lihat Detail</a>
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
            maxZoom: 19,
        }).addTo(map);
    }
}

// ✅ Modified initMap with proper hierarchy using all_categories
async function initMap() {
    try {
        const urlPath = window.location.pathname.replace(/\/$/, "");
        const response = await fetch("/geojson" + urlPath);
        const geoJsonData = await response.json();

        if (!geoJsonData?.features?.length) {
            return showAlert("Data GeoJSON kosong", "warning");
        }

        // 🔸 Build kategoriWarnaMap dan iconMap
        kategoriWarnaMap = {};
        iconMap = {};

        if (Array.isArray(geoJsonData.all_categories)) {
            geoJsonData.all_categories.forEach((cat) => {
                // Pastikan setiap kategori memiliki nama dan warna
                if (cat.nama && cat.warna) {
                    kategoriWarnaMap[cat.nama] = cat.warna;

                    // Jika kategori adalah marker dan memiliki icon, simpan ke dalam iconMap
                    if (cat.is_marker === true && cat.icon) {
                        iconMap[cat.nama] = cat.icon;
                    }
                }
            });
        }

        // 🔸 Hapus layer lama dari peta
        Object.values(layerGroups).forEach((group) => {
            Object.values(group).forEach((layer) => {
                if (map.hasLayer(layer)) {
                    map.removeLayer(layer);
                }
            });
        });

        layerGroups = {};

        // 🔸 Bangun struktur layerGroups dari all_categories (parent-child)
        if (geoJsonData.all_categories?.length) {
            const parents = geoJsonData.all_categories.filter(
                (cat) => !cat.parent_id
            );
            const children = geoJsonData.all_categories.filter(
                (cat) => cat.parent_id
            );

            parents.forEach((parent) => {
                layerGroups[parent.nama] = {};

                const anak = children.filter(
                    (child) => child.parent_id === parent.id
                );

                if (anak.length > 0) {
                    anak.forEach((child) => {
                        layerGroups[parent.nama][child.nama] = L.layerGroup();
                    });
                } else {
                    layerGroups[parent.nama][parent.nama] = L.layerGroup();
                }
            });
        } else if (geoJsonData.root_categories) {
            geoJsonData.root_categories.forEach((cat) => {
                const kategori = cat.nama;
                layerGroups[kategori] = {};

                if (Array.isArray(cat.children) && cat.children.length > 0) {
                    cat.children.forEach((sub) => {
                        layerGroups[kategori][sub.nama] = L.layerGroup();
                    });
                } else {
                    layerGroups[kategori][kategori] = L.layerGroup();
                }
            });
        }

        // 🔸 Tambahkan fitur ke layer yang sesuai
        geoJsonData.features.forEach((feature) => {
            const kategori = (feature.properties?.kategori || "").trim();
            if (!kategori) return;

            let targetLayer = null;

            for (const [parentName, children] of Object.entries(layerGroups)) {
                if (children[kategori]) {
                    targetLayer = children[kategori]; // sebagai child
                    break;
                } else if (parentName === kategori && children[parentName]) {
                    targetLayer = children[parentName]; // sebagai parent
                    break;
                }
            }

            if (targetLayer) {
                let iconClass = iconMap[kategori] || null;
                let iconWarna = kategoriWarnaMap[kategori] || "#333";

                let markerOptions = {};
                if (iconClass) {
                    markerOptions.icon = L.divIcon({
                        html: `
                            <div class="custom-fa-icon" style="background: transparent; border: none;">
                                <i class="${iconClass}" style="font-size: 16px; color: ${iconWarna};"></i>
                            </div>
                        `,
                        className: "leaflet-fa-icon",
                        iconSize: [32, 32],
                        iconAnchor: [16, 32],
                        popupAnchor: [0, -32],
                    });
                }

                L.geoJSON(feature, {
                    pointToLayer: (feature, latlng) =>
                        iconClass
                            ? L.marker(latlng, markerOptions)
                            : L.marker(latlng),
                    style: getStyleForCategory(kategori),
                    onEachFeature: (f, l) => bindPopupContent(f, l, urlPath),
                }).addTo(targetLayer);
            }
        });

        // 🔸 Bersihkan layer kosong
        Object.entries(layerGroups).forEach(([kat, subs]) => {
            Object.entries(subs).forEach(([sub, layer]) => {
                if (layer.getLayers().length === 0) {
                    delete layerGroups[kat][sub];
                }
            });
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

// ✅ Enhanced updateLayerList with dropdown hierarchy
function updateLayerList() {
    const container = document.getElementById("layer-list");
    if (!container) return;

    container.innerHTML = "";

    Object.entries(layerGroups).forEach(([kategori, sublayers]) => {
        const groupId = `group-${kategori.replace(/\s+/g, "-")}`;
        const groupWrapper = document.createElement("div");
        groupWrapper.classList.add("layer-group", "mb-2");

        const rootId = `root-${kategori.replace(/\s+/g, "-")}`;

        // Create header
        const header = document.createElement("div");
        header.className =
            "d-flex align-items-center justify-content-between px-3 py-2 border rounded";
        header.style.cursor = "pointer";

        const leftSection = document.createElement("div");
        leftSection.className = "d-flex align-items-center";

        // Toggle icon
        const toggleBtn = document.createElement("span");
        toggleBtn.className = "me-2";
        toggleBtn.innerHTML = `<i class="bi bi-chevron-right"></i>`;
        toggleBtn.style.transition = "transform 0.3s ease";

        // Parent checkbox
        const checkboxRoot = document.createElement("input");
        checkboxRoot.type = "checkbox";
        checkboxRoot.className = "form-check-input me-2";
        checkboxRoot.id = rootId;
        checkboxRoot.style.border = "2px solid #999"; // Atur warna dan ketebalan border
        // Parent label
        const labelRoot = document.createElement("label");
        labelRoot.className = "form-check-label fw-bold ";
        labelRoot.style.fontSize = "0.85rem";
        labelRoot.htmlFor = rootId;
        labelRoot.textContent = kategori;

        // Count badge
        const subCount = Object.keys(sublayers).length;
        const badge = document.createElement("span");
        badge.className = "badge bg-light text-dark ms-2";
        badge.textContent = subCount;

        // Parent checkbox controls all children
        checkboxRoot.addEventListener("change", () => {
            const isChecked = checkboxRoot.checked;
            Object.entries(sublayers).forEach(([subname, layer]) => {
                const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
                const checkbox = document.getElementById(subId);
                if (checkbox) {
                    checkbox.checked = isChecked;
                    isChecked ? map.addLayer(layer) : map.removeLayer(layer);
                }
            });
        });

        leftSection.appendChild(toggleBtn);
        leftSection.appendChild(checkboxRoot);
        leftSection.appendChild(labelRoot);
        leftSection.appendChild(badge);
        header.appendChild(leftSection);
        groupWrapper.appendChild(header);

        // Create sublayers container
        const subLayerList = document.createElement("div");
        subLayerList.id = groupId;
        subLayerList.className = "border border-top-0 rounded-bottom bg-light ";
        subLayerList.style.display = "none";

        // Add sublayers
        Object.entries(sublayers).forEach(([subname, layer]) => {
            // Skip if same name as parent and has multiple children
            const hasChildren = Object.keys(sublayers).length > 1;
            if (subname === kategori && hasChildren) return;

            const subId = `sub-${kategori}-${subname}`.replace(/\s+/g, "-");
            const row = document.createElement("div");
            row.className = "d-flex align-items-center px-4 py-2 ";

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.className = "form-check-input me-3 ";
            checkbox.id = subId;

            checkbox.style.border = "2px solid #999"; // Atur warna dan ketebalan border

            checkbox.addEventListener("change", () => {
                checkbox.checked ? map.addLayer(layer) : map.removeLayer(layer);

                // Update parent state
                const allSubs = Array.from(
                    subLayerList.querySelectorAll('input[type="checkbox"]')
                );
                const checkedCount = allSubs.filter((cb) => cb.checked).length;

                if (checkedCount === 0) {
                    checkboxRoot.checked = false;
                    checkboxRoot.indeterminate = false;
                } else if (checkedCount === allSubs.length) {
                    checkboxRoot.checked = true;
                    checkboxRoot.indeterminate = false;
                } else {
                    checkboxRoot.checked = false;
                    checkboxRoot.indeterminate = true;
                }
            });

            const label = document.createElement("label");
            label.className = "form-check-label";
            label.htmlFor = subId;
            label.textContent = subname;
            // Parent Row (pastikan ada)
            row.style.cssText = `
    display: flex;
    align-items: center;
    width: 100%;
    gap: 0.5rem;
`;

            // Label
            label.style.cssText = `
    font-size: 0.75rem;              /* kecilkan teks */
    white-space: normal;             /* izinkan teks membungkus */
    word-wrap: break-word;
    overflow-wrap: break-word;
    flex: 1;                         /* isi ruang tersedia */
    max-width: calc(100% - 40px);    /* sisakan ruang untuk checkbox dan colorIndicator */
    line-height: 1.2;
`;

            // Color indicator
            // const colorIndicator = document.createElement("div");
            // Color indicator
            // colorIndicator.style.cssText = `
            //     width: 16px;
            //     height: 16px;
            //     flex-shrink: 0;                  /* jangan menyusutkan kotak warna */
            //     background-color: ${kategoriWarnaMap[subname] || kategoriWarnaMap[kategori] || "#ccc"};
            //     border: 1px solid #333;
            //     border-radius: 3px;
            //     margin-left: 0.5rem;
            // `;

            // colorIndicator.style.cssText = `
            //     width: 16px; height: 16px;
            //     background-color: ${kategoriWarnaMap[subname] || kategoriWarnaMap[kategori] || "#ccc"};
            //     border: 1px solid #333; border-radius: 3px; margin-left: auto;
            // // `;

            row.appendChild(checkbox);
            row.appendChild(label);
            // row.appendChild(colorIndicator);
            subLayerList.appendChild(row);
        });

        // Toggle functionality
        const toggleDropdown = () => {
            const isVisible = subLayerList.style.display !== "none";
            subLayerList.style.display = isVisible ? "none" : "block";
            toggleBtn.innerHTML = isVisible
                ? `<i class="bi bi-chevron-right"></i>`
                : `<i class="bi bi-chevron-down"></i>`;
        };

        header.addEventListener("click", (e) => {
            if (e.target !== checkboxRoot && e.target !== labelRoot) {
                toggleDropdown();
            }
        });

        groupWrapper.appendChild(subLayerList);
        container.appendChild(groupWrapper);
    });
}

function setupUI() {
    const transparencySlider = document.getElementById("transparency");
    if (transparencySlider) {
        transparencySlider.addEventListener("input", (e) => {
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
    }

    const basemapList = document.getElementById("basemap-list");
    if (basemapList) {
        mapConfig.baseMapsList.forEach((bm, i) => {
            basemapList.innerHTML += `
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="radio" role="switch" name="basemap-radio" id="bm-${
                        bm.id
                    }" value="${bm.id}" ${i === 4 ? "checked" : ""}>
                    <label class="form-check-label" for="bm-${bm.id}">${
                bm.label
            }</label>
                </div>`;
        });

        basemapList.addEventListener("change", (e) => {
            if (e.target.name === "basemap-radio")
                changeBaseMap(e.target.value);
        });
    }
}

document.addEventListener("DOMContentLoaded", () => {
    initMap();
    changeBaseMap("esri-world-imagery");
    setupUI();

    // Sidebar Elements
    const sidebarElements = {
        layer: document.getElementById("sidebar-layer"),
        basemap: document.getElementById("sidebar-basemap"),
        legend: document.getElementById("sidebar-legend"),
        download: document.getElementById("sidebar-download"),
        help: document.getElementById("guideModal"),
    };

    // Toggle Buttons
    const toggleButtons = {
        layer: document.getElementById("btn-toggle-sidebar-layer"),
        basemap: document.getElementById("btn-toggle-sidebar-basemap"),
        legend: document.getElementById("btn-toggle-sidebar-legend"),
        download: document.getElementById("btn-toggle-sidebar-download"),
        help: document.getElementById("btn-toggle-sidebar-help"),
    };

    const guideModal = document.getElementById("guideModal");
    const guideSteps = document.querySelectorAll(".guide-step");
    const btnPrev = document.getElementById("btnPrev");
    const btnNext = document.getElementById("btnNext");
    const btnSkip = document.getElementById("btnSkip");
    const btnToggleHelp = toggleButtons.help;

    const controlButtons = [
        toggleButtons.help,
        toggleButtons.legend,
        toggleButtons.basemap,
        toggleButtons.layer,
        toggleButtons.download,
        document.getElementById("btn-fullscreen"),
        document.getElementById("btn-default-zoom"),
    ];

    function closeAllSidebars() {
        Object.values(sidebarElements).forEach((el) => {
            if (el && el !== guideModal) el.style.display = "none";
        });
    }

    let currentStep = 1;
    const totalSteps = guideSteps.length;

    function clearHighlights() {
        controlButtons.forEach((btn) => {
            if (btn) {
                btn.classList.remove("highlighted-control");
                btn.style.position = "";
                btn.style.zIndex = "";
                btn.style.padding = "";
            }
        });
    }

    function showStep(step) {
        guideSteps.forEach((stepDiv) => {
            stepDiv.classList.toggle("d-none", parseInt(stepDiv.dataset.step) !== step);
        });

        btnPrev.disabled = step === 1;
        btnNext.textContent = step === totalSteps ? "Finish" : "Next";
        clearHighlights();

        switch (step) {
            case 3: controlButtons[0]?.classList.add("highlighted-control"); break;
            case 4: controlButtons[1]?.classList.add("highlighted-control"); break;
            case 5: controlButtons[2]?.classList.add("highlighted-control"); break;
            case 6: controlButtons[3]?.classList.add("highlighted-control"); break;
            case 7: controlButtons[4]?.classList.add("highlighted-control"); break;
            case 8: controlButtons[5]?.classList.add("highlighted-control"); break;
            case 9: controlButtons[6]?.classList.add("highlighted-control"); break;
        }
    }

    function hideGuideModal() {
        const modalInstance = bootstrap.Modal.getInstance(guideModal);
        modalInstance?.hide();

        document.body.classList.remove("modal-open");
        document.querySelectorAll(".modal-backdrop").forEach((el) => el.remove());
        document.querySelector(".guide-overlay")?.remove();
    }

    btnToggleHelp?.addEventListener("click", () => {
        const modalInstance = bootstrap.Modal.getInstance(guideModal) || new bootstrap.Modal(guideModal);
        const isVisible = guideModal.classList.contains("show");

        closeAllSidebars();
        clearHighlights();

        if (!isVisible) {
            currentStep = 1;
            showStep(currentStep);
            modalInstance.show();
        } else {
            modalInstance.hide();
        }
    });

    btnPrev?.addEventListener("click", () => {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    btnNext?.addEventListener("click", () => {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        } else {
            hideGuideModal();
            clearHighlights();
        }
    });

    btnSkip?.addEventListener("click", () => {
        hideGuideModal();
        clearHighlights();
    });

    // Sidebar toggles
    Object.entries(toggleButtons).forEach(([key, button]) => {
        if (button && sidebarElements[key]) {
            button.addEventListener("click", () => {
                const sidebar = sidebarElements[key];
                const isVisible = sidebar.style.display === "block";
                closeAllSidebars();

                if (key === "help" && window.bootstrap) {
                    const modal = new bootstrap.Modal(sidebar);
                    if (!isVisible) modal.show();
                } else {
                    sidebar.style.display = isVisible ? "none" : "block";
                }
            });
        }
    });

    // Close sidebar buttons
    ["layer", "basemap", "legend", "download"].forEach((type) => {
        const closeBtn = document.getElementById(`btn-close-sidebar-${type}`);
        if (closeBtn && sidebarElements[type]) {
            closeBtn.addEventListener("click", () => {
                sidebarElements[type].style.display = "none";
            });
        }
    });

    // Fullscreen toggle
    const btnFullscreen = document.getElementById("btn-fullscreen");
    btnFullscreen?.addEventListener("click", () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(console.error);
        } else {
            document.exitFullscreen().catch(console.error);
        }
    });

    // Zoom reset
    const btnDefaultZoom = document.getElementById("btn-default-zoom");
    btnDefaultZoom?.addEventListener("click", () => {
        map.setView(mapConfig.center, mapConfig.zoom);
    });

    // Search layer
    const layerSearchInput = document.getElementById("layer-search");
    layerSearchInput?.addEventListener("input", (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const layerGroups = document.querySelectorAll(".layer-group");

        layerGroups.forEach((group) => {
            const parentLabel = group.querySelector(".fw-bold");
            const childLabels = group.querySelectorAll(".bg-light label");
            let hasMatch = false;

            if (parentLabel && parentLabel.textContent.toLowerCase().includes(searchTerm)) hasMatch = true;

            childLabels.forEach((label) => {
                if (label.textContent.toLowerCase().includes(searchTerm)) {
                    hasMatch = true;
                }
            });

            group.style.display = hasMatch || searchTerm === "" ? "block" : "none";
        });
    });
});