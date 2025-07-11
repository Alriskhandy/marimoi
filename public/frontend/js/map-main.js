// map-main.js
import { initMap } from "./map-init.js";
import { setupUI } from "./map-ui.js";
import { changeBaseMap } from "./map-utils.js";

window.addEventListener("DOMContentLoaded", () => {
    initMap();
    setupUI();
    changeBaseMap("osm");
});
