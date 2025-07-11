/**
 * GIS Map Application Configuration
 * Contains all configuration settings and constants
 */

// Application Configuration
const APP_CONFIG = {
    // Map Settings
    map: {
        defaultCenter: [-0.8, 127.4],
        defaultZoom: 9,
        minZoom: 5,
        maxZoom: 18,
        maxBounds: [
            [-5, 125],
            [3, 130],
        ], // Bounds for North Maluku area
    },

    // API Endpoints
    api: {
        geojson: "/geojson",
        categories: "/api/categories",
        statistics: "/api/statistics",
        dbfColumns: "/api/dbf/columns",
        dbfColumnValues: "/api/dbf/column/{column}/values",
        categoryData: "/api/category/{kategori}",
        debugShapefile: "/debug-shapefile",
    },

    // UI Settings
    ui: {
        searchDebounceTime: 500,
        animationDuration: 300,
        maxFilterColumns: 3,
        maxColumnValues: 20,
        maxPopupAttributes: 6,
        tooltipDelay: 500,
    },

    // Feature Limits
    limits: {
        maxFeatures: 1000,
        maxPopupWidth: 350,
        maxLabelLength: 50,
    },
};

// Category Color Scheme
const CATEGORY_COLORS = {
    default: {
        color: "#ff7800",
        fillColor: "#ffd27f",
        description: "Default category",
    },
    hutan: {
        color: "#228B22",
        fillColor: "#90EE90",
        description: "Forest areas",
    },
    sawah: {
        color: "#DAA520",
        fillColor: "#F0E68C",
        description: "Rice fields",
    },
    permukiman: {
        color: "#DC143C",
        fillColor: "#FFB6C1",
        description: "Residential areas",
    },
    industri: {
        color: "#4169E1",
        fillColor: "#87CEEB",
        description: "Industrial zones",
    },
    jalan: {
        color: "#2F4F4F",
        fillColor: "#708090",
        description: "Roads and transportation",
    },
    sungai: {
        color: "#1E90FF",
        fillColor: "#87CEFA",
        description: "Rivers and waterways",
    },
    danau: {
        color: "#000080",
        fillColor: "#ADD8E6",
        description: "Lakes and water bodies",
    },
    kebun: {
        color: "#32CD32",
        fillColor: "#98FB98",
        description: "Gardens and plantations",
    },
    pantai: {
        color: "#FFD700",
        fillColor: "#FFFFE0",
        description: "Coastal areas",
    },
    gunung: {
        color: "#8B4513",
        fillColor: "#DEB887",
        description: "Mountain areas",
    },
    rawa: {
        color: "#556B2F",
        fillColor: "#9ACD32",
        description: "Swamp areas",
    },
};

// Map Layer Styles
const MAP_STYLES = {
    default: {
        weight: 2,
        opacity: 0.8,
        fillOpacity: 0.4,
        dashArray: null,
    },
    hover: {
        weight: 3,
        fillOpacity: 0.7,
    },
    selected: {
        weight: 4,
        opacity: 1,
        fillOpacity: 0.8,
        dashArray: "5, 5",
    },
};

// Tile Layer Options
const TILE_LAYERS = {
    openstreetmap: {
        url: "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        name: "OpenStreetMap",
    },
    satellite: {
        url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
        attribution:
            "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community",
        name: "Satellite",
    },
    terrain: {
        url: "https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png",
        attribution:
            'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
        name: "Terrain",
    },
};

// Animation Configurations
const ANIMATIONS = {
    fadeIn: "fade-in",
    slideInLeft: "slide-in-left",
    pulse: "pulse",
};

// Error Messages
const ERROR_MESSAGES = {
    noData: "Tidak ada data yang ditemukan dengan filter yang dipilih.",
    loadError: "Gagal memuat data peta. Silakan refresh halaman.",
    networkError:
        "Koneksi internet bermasalah. Beberapa fitur mungkin tidak berfungsi.",
    geoLocationError: "Tidak dapat mengakses lokasi Anda.",
    exportError: "Gagal mengekspor data.",
    printError: "Gagal mencetak peta.",
};

// Success Messages
const SUCCESS_MESSAGES = {
    dataLoaded: "Data berhasil dimuat.",
    filterApplied: "Filter berhasil diterapkan.",
    locationFound: "Lokasi Anda ditemukan.",
    dataExported: "Data berhasil diekspor.",
    linkCopied: "Link berhasil disalin ke clipboard.",
};

// Keyboard Shortcuts
const KEYBOARD_SHORTCUTS = {
    search: "f",
    escape: "Escape",
    fullscreen: "F11",
    help: "h",
};

// Local Storage Keys
const STORAGE_KEYS = {
    mapView: "gis_map_view",
    preferences: "gis_preferences",
    recentSearches: "gis_recent_searches",
};

// Export configuration for use in other modules
window.GIS_CONFIG = {
    APP_CONFIG,
    CATEGORY_COLORS,
    MAP_STYLES,
    TILE_LAYERS,
    ANIMATIONS,
    ERROR_MESSAGES,
    SUCCESS_MESSAGES,
    KEYBOARD_SHORTCUTS,
    STORAGE_KEYS,
};
