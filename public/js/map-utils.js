// utils.js
function showSidebarSection(section) {
    const sidebar = document.getElementById("sidebar");
    const layerSection = document.getElementById("layer-section");
    const basemapSection = document.getElementById("basemap-section");
    const legendSection = document.getElementById("legend-section");

    // Tampilkan sidebar kalau disembunyikan
    sidebar.classList.remove("hidden");

    // Reset semua ke hidden
    layerSection.classList.add("hidden");
    basemapSection.classList.add("hidden");
    legendSection.classList.add("hidden");

    // Tampilkan hanya yang dipilih
    if (section === "layer") layerSection.classList.remove("hidden");
    if (section === "basemap") basemapSection.classList.remove("hidden");
    if (section === "legend") legendSection.classList.remove("hidden");
}
