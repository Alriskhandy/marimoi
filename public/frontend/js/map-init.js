// map-init.js
import { mapConfig } from "./map-config.js";
import {
    getStyleForCategory,
    bindPopupContent,
    showAlert,
} from "./map-utils.js";

export let layerGroups = {};
export let map = null;

export async function initMap() {
    map = L.map("map", {
        zoomControl: true,
        attributionControl: true,
    }).setView(mapConfig.center, mapConfig.zoom);

    try {
        const response = await fetch("/geojson");
        const geoJsonData = await response.json();

        if (!geoJsonData?.features?.length) {
            showAlert("Data GeoJSON kosong atau tidak valid", "warning");
            return;
        }

        geoJsonData.features.forEach((feature) => {
            const kategori = feature.properties?.kategori || "Lainnya";
            if (!layerGroups[kategori]) {
                layerGroups[kategori] = L.layerGroup();
            }

            L.geoJSON(feature, {
                style: getStyleForCategory(kategori),
                onEachFeature: (feature, layer) =>
                    bindPopupContent(feature, layer),
            }).addTo(layerGroups[kategori]);
        });
    } catch (error) {
        console.error("Error:", error);
        showAlert("Gagal memuat data peta", "danger");
    }
}
