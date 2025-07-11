// map-ui.js
import { layerGroups, map } from "./map-init.js";

export function setupUI() {
    // Implement listener for sliders, sidebar toggle, checkbox actions, etc.
    // Example:
    document.getElementById("transparency")?.addEventListener("input", (e) => {
        const opacity = e.target.value / 100;
        Object.values(layerGroups).forEach((group) => {
            group.eachLayer((layer) =>
                layer.setStyle?.({ fillOpacity: opacity })
            );
        });
    });
}
