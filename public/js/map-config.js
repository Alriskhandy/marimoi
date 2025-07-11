// config.js
const mapConfig = {
    center: [0.36310009945603017, 127.12398149281738],
    zoom: 8,
    baseMaps: {
        osm: {
            url: "https://tile.openstreetmap.org/{z}/{x}/{y}.png",
            attribution:
                '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        },
        satelite: {
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}.png",
            attribution:
                "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community",
        },
        terrain: {
            url: "https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png",
            attribution:
                'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
        },
    },
};

const API = {
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
};
