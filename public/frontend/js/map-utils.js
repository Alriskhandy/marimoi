// map-utils.js
export function getStyleForCategory(kategori) {
    const colorMap = {
        Infrastruktur: { color: "#0d6efd", fillColor: "#b6d7ff" },
        Perekonomian: { color: "#198754", fillColor: "#a3e8c4" },
        Lingkungan: { color: "#fd7e14", fillColor: "#ffc582" },
        default: { color: "#6c757d", fillColor: "#adb5bd" },
    };
    return colorMap[kategori] || colorMap.default;
}

export function bindPopupContent(feature, layer) {
    const props = feature.properties;
    let content = `<div class="p-2" style="max-width: 300px;"><h5 class="fw-bold mb-2 text-primary">${
        props.kategori || "Feature"
    }</h5>`;

    Object.entries(props).forEach(([key, value]) => {
        if (value && !["geometry", "id"].includes(key)) {
            const label = key
                .replace(/_/g, " ")
                .replace(/\b\w/g, (l) => l.toUpperCase());
            content += `<p class="small mb-1"><span class="fw-medium">${label}:</span> ${value}</p>`;
        }
    });

    content += `</div>`;
    layer.bindPopup(content);
}

export function showAlert(message, type = "info") {
    console.log(`${type}: ${message}`);
}
