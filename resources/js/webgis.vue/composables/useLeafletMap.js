import { ref, reactive } from 'vue'
import { useFeatureDetail } from './useFeatureDetail.js'

const DEFAULT_CENTER = [0.735485, 128.028201] // Maluku Utara
const DEFAULT_ZOOM = 7

export const BASEMAPS = [
    {
        id: 'osm',
        label: 'OpenStreetMap',
        url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    },
    {
        id: 'esri-streets',
        label: 'ESRI Streets',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
        maxZoom: 19,
        attribution: '&copy; ESRI',
    },
    {
        id: 'esri-topographic',
        label: 'Topographic',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
        maxZoom: 19,
        attribution: '&copy; ESRI',
    },
    {
        id: 'esri-oceans',
        label: 'ESRI Oceans',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Ocean/World_Ocean_Base/MapServer/tile/{z}/{y}/{x}',
        maxZoom: 16,
        attribution: '&copy; ESRI',
    },
    {
        id: 'esri-world-imagery',
        label: 'ESRI World Imagery',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        maxZoom: 18,
        attribution: '&copy; ESRI',
    },
    {
        id: 'esri-dark-gray',
        label: 'ESRI Dark Gray Canvas',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}',
        maxZoom: 16,
        attribution: '&copy; ESRI',
    },
    {
        id: 'esri-light-gray',
        label: 'Light Gray Canvas',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
        maxZoom: 16,
        attribution: '&copy; ESRI',
    },
    {
        id: 'google-roadmap',
        label: 'Google Map (ROADMAP)',
        url: 'https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        maxZoom: 20,
        attribution: '&copy; Google Maps',
    },
    {
        id: 'google-hybrid',
        label: 'Google Map (Hybrid)',
        url: 'https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        maxZoom: 20,
        attribution: '&copy; Google Maps',
    },
    {
        id: 'google-terrain',
        label: 'Google Map (Terrain)',
        url: 'https://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        maxZoom: 16,
        attribution: '&copy; Google Maps',
    },
]

// Module-level singletons shared across components
const mapInstance = ref(null)
const currentTileLayer = ref(null)
const activeBasemap = ref('osm')
const loading = ref(false)

// In-memory only — fetched from /api/map/token, never written to localStorage/sessionStorage
let _mapToken = null
let _mapTokenExpiresAt = 0  // epoch ms

async function fetchMapToken() {
    try {
        const res = await fetch('/api/map/token')
        if (!res.ok) return null
        const data = await res.json()
        _mapToken = data.access_token ?? null
        _mapTokenExpiresAt = Date.now() + ((data.expires_in ?? 3600) - 60) * 1000
        return _mapToken
    } catch {
        return null
    }
}

async function getMapToken() {
    if (_mapToken && Date.now() < _mapTokenExpiresAt) return _mapToken
    return fetchMapToken()
}

// 3-level category tree (reactive)
// { rootName: { children: { secondName: { allChecked, someChecked, children: { thirdName: { checked, loading, loaded } } } } } }
const categoryTree = reactive({})

// Non-reactive Leaflet layer map — keyed by 'root/second/third'
const leafletLayerMap = {}

// Non-reactive helpers
const loadedCategories = new Set()
const kategoriColors = reactive({})
const iconMap = {}      // { name: 'fa-...' class string }
const isMarkerMap = {}  // { name: true } for point-marker categories

export function useLeafletMap() {
    const L = () => window.L

    function initMap(containerId) {
        // Patch Leaflet Popup._animateZoom — guard against _map being null
        // (happens when a popup's layer is removed while the popup is still open)
        const LLib = L()
        if (LLib.Popup && !LLib.Popup._zoomPatchApplied) {
            const orig = LLib.Popup.prototype._animateZoom
            LLib.Popup.prototype._animateZoom = function (e) {
                if (!this._map) return
                orig.call(this, e)
            }
            LLib.Popup._zoomPatchApplied = true
        }

        const map = LLib.map(containerId, {
            zoomControl: true,
            attributionControl: true,
        }).setView(DEFAULT_CENTER, DEFAULT_ZOOM)

        // Custom panes — z-index ordering: polygon < line < point < marker (default 600)
        map.createPane('polygonPane'); map.getPane('polygonPane').style.zIndex = 390
        map.createPane('linePane');    map.getPane('linePane').style.zIndex    = 400
        map.createPane('pointPane');   map.getPane('pointPane').style.zIndex   = 410

        mapInstance.value = map
        changeBasemap('osm')
        return map
    }

    function changeBasemap(id) {
        const map = mapInstance.value
        if (!map) return

        if (currentTileLayer.value) map.removeLayer(currentTileLayer.value)

        const cfg = BASEMAPS.find(b => b.id === id)
        if (!cfg) return

        currentTileLayer.value = L().tileLayer(cfg.url, {
            subdomains: cfg.subdomains || [],
            minZoom: 4,
            maxZoom: cfg.maxZoom ?? 18,
            attribution: cfg.attribution,
        }).addTo(map)

        activeBasemap.value = id
    }

    async function loadMetadata(toastFn) {
        loading.value = true
        toastFn?.('Memuat daftar kategori...', 'info')

        try {
            const res = await fetch('/geojson?metadata_only=true')
            if (!res.ok) throw new Error(`HTTP ${res.status}`)

            const data = await res.json()

            // Clear existing state first, then repopulate
            Object.keys(categoryTree).forEach(k => delete categoryTree[k])
            Object.keys(leafletLayerMap).forEach(k => delete leafletLayerMap[k])
            Object.keys(kategoriColors).forEach(k => delete kategoriColors[k])
            Object.keys(iconMap).forEach(k => delete iconMap[k])
            Object.keys(isMarkerMap).forEach(k => delete isMarkerMap[k])
            loadedCategories.clear()

            // Build color / icon maps
            const allCats = data.all_categories || []
            allCats.forEach(cat => {
                if (!cat.nama) return
                if (cat.warna) kategoriColors[cat.nama] = cat.warna
                if (cat.is_marker && cat.icon) {
                    iconMap[cat.nama] = cat.icon
                    isMarkerMap[cat.nama] = true
                }
            })

            // Build 3-level hierarchy from flat all_categories list
            if (allCats.length > 0) {
                const rootCats   = allCats.filter(c => !c.parent_id)
                const secondCats = allCats.filter(c => c.parent_id && rootCats.some(r => r.id === c.parent_id))
                const thirdCats  = allCats.filter(c => c.parent_id && secondCats.some(s => s.id === c.parent_id))

                rootCats.forEach(root => {
                    categoryTree[root.nama] = { children: {} }
                    const l2s = secondCats.filter(s => s.parent_id === root.id)

                    if (l2s.length > 0) {
                        l2s.forEach(second => {
                            categoryTree[root.nama].children[second.nama] = {
                                allChecked: false, someChecked: false, children: {},
                            }
                            const l3s = thirdCats.filter(t => t.parent_id === second.id)

                            if (l3s.length > 0) {
                                l3s.forEach(third => {
                                    categoryTree[root.nama].children[second.nama].children[third.nama] = {
                                        checked: false, loading: false, loaded: false,
                                    }
                                    leafletLayerMap[`${root.nama}/${second.nama}/${third.nama}`] = L().layerGroup()
                                })
                            } else {
                                // second level is the leaf
                                categoryTree[root.nama].children[second.nama].children[second.nama] = {
                                    checked: false, loading: false, loaded: false,
                                }
                                leafletLayerMap[`${root.nama}/${second.nama}/${second.nama}`] = L().layerGroup()
                            }
                        })
                    } else {
                        // root level is the only level
                        categoryTree[root.nama].children[root.nama] = {
                            allChecked: false, someChecked: false, children: {},
                        }
                        categoryTree[root.nama].children[root.nama].children[root.nama] = {
                            checked: false, loading: false, loaded: false,
                        }
                        leafletLayerMap[`${root.nama}/${root.nama}/${root.nama}`] = L().layerGroup()
                    }
                })
            } else if (data.root_categories?.length) {
                // Fallback: use nested root_categories structure
                data.root_categories.forEach(root => {
                    categoryTree[root.nama] = { children: {} }
                    const l2s = root.children || []

                    if (l2s.length > 0) {
                        l2s.forEach(second => {
                            categoryTree[root.nama].children[second.nama] = {
                                allChecked: false, someChecked: false, children: {},
                            }
                            const l3s = second.children || []

                            if (l3s.length > 0) {
                                l3s.forEach(third => {
                                    categoryTree[root.nama].children[second.nama].children[third.nama] = {
                                        checked: false, loading: false, loaded: false,
                                    }
                                    leafletLayerMap[`${root.nama}/${second.nama}/${third.nama}`] = L().layerGroup()
                                })
                            } else {
                                categoryTree[root.nama].children[second.nama].children[second.nama] = {
                                    checked: false, loading: false, loaded: false,
                                }
                                leafletLayerMap[`${root.nama}/${second.nama}/${second.nama}`] = L().layerGroup()
                            }
                        })
                    } else {
                        categoryTree[root.nama].children[root.nama] = {
                            allChecked: false, someChecked: false, children: {},
                        }
                        categoryTree[root.nama].children[root.nama].children[root.nama] = {
                            checked: false, loading: false, loaded: false,
                        }
                        leafletLayerMap[`${root.nama}/${root.nama}/${root.nama}`] = L().layerGroup()
                    }
                })
            }

            toastFn?.('Kategori berhasil dimuat. Pilih layer untuk memuat data.', 'success')
        } catch (err) {
            toastFn?.(`Gagal memuat kategori: ${err.message}`, 'error')
        } finally {
            loading.value = false
        }
    }

    async function loadCategoryData(thirdName, secondName, rootName, toastFn) {
        const key = `${rootName}/${secondName}/${thirdName}`

        if (loadedCategories.has(key)) return true

        const node = categoryTree[rootName]?.children[secondName]?.children[thirdName]
        const targetLayer = leafletLayerMap[key]
        if (!node || !targetLayer) return false

        node.loading = true

        try {
            // Close any open popup before clearing — prevents _map null error on zoom animation
            mapInstance.value?.closePopup()
            targetLayer.clearLayers()

            let offset = 0
            const chunkSize = 500
            const maxRecords = 3000
            let totalLoaded = 0
            let hasMore = true

            const color      = kategoriColors[thirdName] || kategoriColors[secondName] || '#007fff'
            const useMarker  = isMarkerMap[thirdName] || false
            const markerIcon = iconMap[thirdName] || null

            // Use ExtraMarkers matching map.js reference exactly:
            // - prefix: "fa" (not empty) so FA classes resolve correctly
            // - html: overrides inner content, keeps only the pin SVG shell
            // - svg: true generates the colored pin shape as inline SVG
            let stableMarkerIcon = null
            if (useMarker && markerIcon) {
                const EM = window.L?.ExtraMarkers
                if (EM) {
                    try {
                        stableMarkerIcon = EM.icon({
                            icon:        markerIcon,
                            prefix:      'fa',
                            svg:         true,
                            markerColor: color || 'blue',
                            iconColor:   'white',
                            shape:       'circle',
                            html:        `<i class="${markerIcon}" style="color:white"></i>`,
                        })
                    } catch (_) { /* fall back to circleMarker */ }
                }
            }

            const layerId = node.layerId
            const token   = await getMapToken()
            const headers = token ? { 'Authorization': `Bearer ${token}` } : {}

            while (hasMore && totalLoaded < maxRecords) {
                const url = `/api/v1/admin/features/geojson/${layerId}?limit=${chunkSize}&offset=${offset}`
                const res = await fetch(url, { headers })
                if (!res.ok) throw new Error(`HTTP ${res.status}`)

                const data = await res.json()
                if (!data?.features?.length) break

                data.features.forEach(feature => {
                    if (!feature?.geometry) return
                    const geomType = feature.geometry.type
                    const isLine   = geomType === 'LineString' || geomType === 'MultiLineString'
                    const isPoint  = geomType === 'Point'      || geomType === 'MultiPoint'

                    // Assign to pane by geometry type: polygon(390) < line(400) < point(410) < marker(600)
                    const pane = isLine ? 'linePane' : (isPoint ? 'pointPane' : 'polygonPane')

                    L().geoJSON(feature, {
                        pane,
                        style: () => isLine
                            ? { pane, color, weight: 5, opacity: 0.9, lineCap: 'round', lineJoin: 'round' }
                            : { pane, color, weight: 2, opacity: 0.7, fillColor: color, fillOpacity: 0.4 },
                        pointToLayer: (f, latlng) => {
                            if (stableMarkerIcon) {
                                // L.marker goes to default markerPane (z-index 600) — always on top
                                return L().marker(latlng, { icon: stableMarkerIcon, riseOnHover: true })
                            }
                            return L().circleMarker(latlng, {
                                pane: 'pointPane',
                                radius: 7, fillColor: color,
                                color: '#ffffff', weight: 1.5,
                                opacity: 1, fillOpacity: 0.9,
                            })
                        },
                        onEachFeature: (f, l) => bindPopup(f, l),
                    }).addTo(targetLayer)
                    totalLoaded++
                })

                hasMore = data.meta?.has_more === true && totalLoaded < maxRecords
                offset += chunkSize
            }

            loadedCategories.add(key)
            node.loaded = true

            // Sync slider with actual rendered opacity of the first feature.
            // L.Polyline has fill:false and its fillOpacity defaults to 0.2 (Leaflet default),
            // so we must check fill:false and fall back to stroke opacity for lines.
            let detectedOpacity = 100
            let found = false
            _eachFeature(targetLayer, feature => {
                if (found) return
                const opts = feature.options || {}
                if (opts.fill !== false && opts.fillOpacity !== undefined) {
                    // Polygon / CircleMarker — use fill opacity
                    detectedOpacity = Math.round(opts.fillOpacity * 100)
                    found = true
                } else if (opts.opacity !== undefined) {
                    // Line or Marker — use stroke/element opacity
                    detectedOpacity = Math.round(opts.opacity * 100)
                    found = true
                }
            })
            node.opacity = detectedOpacity

            toastFn?.(`Data ${thirdName} berhasil dimuat (${totalLoaded} fitur)`, 'success')
            return true
        } catch (err) {
            toastFn?.(`Gagal memuat ${thirdName}: ${err.message}`, 'error')
            return false
        } finally {
            node.loading = false
        }
    }

    function addCategoryToMap(thirdName, secondName, rootName) {
        const key = `${rootName}/${secondName}/${thirdName}`
        const layer = leafletLayerMap[key]
        const map = mapInstance.value
        if (layer && map) map.addLayer(layer)
        _setChecked(thirdName, secondName, rootName, true)
    }

    function removeCategoryFromMap(thirdName, secondName, rootName) {
        const key = `${rootName}/${secondName}/${thirdName}`
        const layer = leafletLayerMap[key]
        const map = mapInstance.value
        if (layer && map) map.removeLayer(layer)
        _setChecked(thirdName, secondName, rootName, false)
    }

    function _setChecked(thirdName, secondName, rootName, checked) {
        const node = categoryTree[rootName]?.children[secondName]?.children[thirdName]
        if (node) node.checked = checked
        _updateSecondState(secondName, rootName)
    }

    function _updateSecondState(secondName, rootName) {
        const secondNode = categoryTree[rootName]?.children[secondName]
        if (!secondNode) return
        const children = Object.values(secondNode.children)
        const checkedCount = children.filter(c => c.checked).length
        secondNode.allChecked = children.length > 0 && checkedCount === children.length
        secondNode.someChecked = checkedCount > 0 && checkedCount < children.length
    }

    function resetView() {
        mapInstance.value?.setView(DEFAULT_CENTER, DEFAULT_ZOOM)
    }

    function toggleFullscreen() {
        if (document.fullscreenElement) {
            document.exitFullscreen()
        } else {
            document.documentElement.requestFullscreen()
        }
    }

    function destroyMap() {
        mapInstance.value?.remove()
        mapInstance.value = null
        currentTileLayer.value = null
        Object.keys(categoryTree).forEach(k => delete categoryTree[k])
        Object.keys(leafletLayerMap).forEach(k => delete leafletLayerMap[k])
        Object.keys(iconMap).forEach(k => delete iconMap[k])
        Object.keys(isMarkerMap).forEach(k => delete isMarkerMap[k])
        loadedCategories.clear()
        Object.keys(kategoriColors).forEach(k => delete kategoriColors[k])
    }

    function bindPopup(feature, layer) {
        const p        = feature.properties || {}
        const geom     = feature.geometry   || null
        const name     = p.NAMA_OBJEK || p.NAMOBJ || p.nama || p.name || 'Detail'
        const kategori = p.kategori || ''
        const imageUrl = p.gambar   || null

        // Resolve a center coordinate for Zoom To
        let zoomLat = 0, zoomLng = 0, coordText = ''
        if (geom) {
            const c = geom.coordinates
            if (geom.type === 'Point') {
                zoomLng = c[0]; zoomLat = c[1]
            } else if (geom.type === 'LineString') {
                const m = c[Math.floor(c.length / 2)]
                zoomLng = m[0]; zoomLat = m[1]
            } else if (geom.type === 'Polygon') {
                const m = c[0][Math.floor(c[0].length / 2)]
                zoomLng = m[0]; zoomLat = m[1]
            } else if (geom.type === 'MultiPolygon') {
                const m = c[0][0][Math.floor(c[0][0].length / 2)]
                zoomLng = m[0]; zoomLat = m[1]
            }
            if (zoomLat || zoomLng)
                coordText = `${zoomLat.toFixed(5)}, ${zoomLng.toFixed(5)}`
        }

        // Attribute rows — skip internal, system, and sensitive fields
        const excluded = new Set([
            // system / internal
            'id','uuid','geometry','geom',
            // category metadata
            'kategori','kategori_id','kategori_color','kategori_full_path',
            'parent_kategori','parent_kategori_id',
            // display fields already shown in header
            'NAMA_OBJEK','NAMOBJ','nama','name','gambar',
            // data type / classification (internal routing)
            'data_type','sub_type','type','original_type',
            // layer / user relations
            'layer_id','user_id','feature_id',
            // timestamps & counters
            'created_at','updated_at','deleted_at','views','year',
            // legacy
            'legacy_uuid','deskripsi',
        ])
        const rows = Object.entries(p)
            .filter(([k, v]) => v != null && v !== '' && !excluded.has(k))
            .slice(0, 6)
            .map(([k, v]) => {
                const label = k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
                return `<tr>
                    <td style="font-weight:600;font-size:11px;color:#374151;padding:3px 10px 3px 0;white-space:nowrap;vertical-align:top">${label}</td>
                    <td style="font-size:11px;color:#6b7280;padding:3px 0;vertical-align:top">${v}</td>
                </tr>`
            }).join('')

        const imgHtml = imageUrl
            ? `<img src="${imageUrl}" alt="${name}" style="width:100%;height:80px;object-fit:cover;display:block">`
            : ''

        const geomHtml = geom ? `
            <div style="padding:6px 14px 8px;border-top:1px solid #f3f4f6">
                <table style="width:100%;border-collapse:collapse">
                    <tr>
                        <td style="font-weight:600;font-size:11px;color:#374151;padding:2px 10px 2px 0">Geometry</td>
                        <td style="font-size:11px;color:#6b7280">${geom.type}</td>
                    </tr>
                    ${coordText ? `<tr>
                        <td style="font-weight:600;font-size:11px;color:#374151;padding:2px 10px 2px 0">Koordinat</td>
                        <td style="font-size:11px;color:#6b7280;font-family:monospace">${coordText}</td>
                    </tr>` : ''}
                </table>
            </div>` : ''

        layer.bindPopup(`
            <div style="min-width:220px;max-width:300px;font-family:'Inter',sans-serif">
                <div style="padding:10px 14px;border-bottom:2px solid #007fff">
                    <div style="font-weight:700;font-size:14px;color:#007fff;margin:0 0 2px">${name}</div>
                    <div style="font-size:11px;color:#9ca3af;margin:0">${kategori}</div>
                </div>
                ${imgHtml}
                <div style="padding:8px 14px">
                    <table style="width:100%;border-collapse:collapse">
                        ${rows || '<tr><td colspan="2" style="color:#9ca3af;font-size:11px;padding:4px 0">—</td></tr>'}
                    </table>
                </div>
                ${geomHtml}
                <div style="padding:8px 14px 12px;display:flex;gap:8px;border-top:1px solid #f3f4f6">
                    <button class="popup-zoom-btn"
                        data-lat="${zoomLat}" data-lng="${zoomLng}"
                        style="flex:1;background:#007fff;color:#fff;border:none;border-radius:6px;padding:6px 0;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px">
                        <i class="bi bi-zoom-in"></i> Zoom To
                    </button>
                    <button class="popup-detail-btn"
                        style="flex:1;background:transparent;color:#007fff;border:1.5px solid #007fff;border-radius:6px;padding:6px 0;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px">
                        <i class="bi bi-eye"></i> Detail
                    </button>
                </div>
            </div>
        `, { className: 'tailwind-popup', maxWidth: 320 })

        layer.on('popupopen', () => {
            const el  = layer.getPopup()?.getElement()
            const map = mapInstance.value
            if (!el) return

            // Zoom To
            const zBtn = el.querySelector('.popup-zoom-btn')
            if (zBtn && map) {
                zBtn.onclick = () => {
                    const lat = parseFloat(zBtn.dataset.lat)
                    const lng = parseFloat(zBtn.dataset.lng)
                    if (!isNaN(lat) && !isNaN(lng)) {
                        if (geom?.type === 'Point') {
                            map.flyTo([lat, lng], 16, { duration: 0.8 })
                        } else {
                            try { map.flyToBounds(layer.getBounds(), { padding: [40, 40], duration: 0.8 }) } catch (_) {
                                map.flyTo([lat, lng], 14, { duration: 0.8 })
                            }
                        }
                    }
                }
            }

            // Detail → open bottom bar
            const dBtn = el.querySelector('.popup-detail-btn')
            if (dBtn) {
                dBtn.onclick = () => {
                    useFeatureDetail().setFeature(feature)
                }
            }
        })
    }

    async function loadLayersTreeByCategory(toastFn) {
        loading.value = true
        toastFn?.('Memuat daftar layer...', 'info')

        try {
            const token = await getMapToken()
            const fetchOptions = {
                headers: token ? { 'Authorization': `Bearer ${token}` } : {},
            }

            const res = await fetch('/api/v1/admin/layers/tree', fetchOptions)
            if (!res.ok) throw new Error(`HTTP ${res.status}`)

            const json = await res.json()
            const layers = json.data || []

            // Clear existing state first
            Object.keys(categoryTree).forEach(k => delete categoryTree[k])
            Object.keys(leafletLayerMap).forEach(k => delete leafletLayerMap[k])
            Object.keys(kategoriColors).forEach(k => delete kategoriColors[k])
            Object.keys(iconMap).forEach(k => delete iconMap[k])
            Object.keys(isMarkerMap).forEach(k => delete isMarkerMap[k])
            loadedCategories.clear()

            // Display labels for each category key
            const CATEGORY_LABELS = {
                tematik:    'Tematik',
                psd:        'Proyek Strategis Daerah',
                psn:        'Proyek Strategis Nasional',
                musrenbang: 'Musrenbang',
                pokir:      'Pokir DPRD',
            }

            // Helper: register color / icon from layer.style
            function registerStyle(layer) {
                const s = layer.style || {}
                if (s.color) kategoriColors[layer.name] = s.color
                if (s.is_marker && s.icon) {
                    iconMap[layer.name]     = s.icon
                    isMarkerMap[layer.name] = true
                }
            }

            // Helper: build a leaf node storing layer metadata needed for fetching
            function leafNode(layer) {
                return {
                    checked:  false,
                    loading:  false,
                    loaded:   false,
                    layerId:  layer.id,
                    dataType: layer.style?.original_type || null,
                    opacity:  100,                          // 0–100 %
                    color:    layer.style?.color || '#007fff',
                }
            }

            // Group root layers by their category field
            const grouped = {}
            layers.forEach(layer => {
                const key = layer.category || 'lainnya'
                if (!grouped[key]) grouped[key] = []
                grouped[key].push(layer)
            })

            // Build tree: root = category label, L2 = layer, L3 = layer's children
            Object.entries(grouped).forEach(([catKey, catLayers]) => {
                const rootLabel = CATEGORY_LABELS[catKey] || catKey
                categoryTree[rootLabel] = { children: {} }

                catLayers.forEach(layer => {
                    registerStyle(layer)
                    categoryTree[rootLabel].children[layer.name] = {
                        allChecked: false, someChecked: false, children: {},
                    }

                    const children = layer.children || []
                    if (children.length > 0) {
                        children.forEach(child => {
                            registerStyle(child)
                            categoryTree[rootLabel].children[layer.name].children[child.name] = leafNode(child)
                            leafletLayerMap[`${rootLabel}/${layer.name}/${child.name}`] = L().layerGroup()
                        })
                    } else {
                        // layer has no children — it is the leaf itself
                        categoryTree[rootLabel].children[layer.name].children[layer.name] = leafNode(layer)
                        leafletLayerMap[`${rootLabel}/${layer.name}/${layer.name}`] = L().layerGroup()
                    }
                })
            })

            toastFn?.('Layer berhasil dimuat. Pilih layer untuk menampilkan data.', 'success')
        } catch (err) {
            toastFn?.(`Gagal memuat layer: ${err.message}`, 'error')
        } finally {
            loading.value = false
        }
    }

    // ── Real-time layer style controls (not persisted) ─────────────────────
    // Layer structure: targetLayer (layerGroup) → geoJsonLayer (L.GeoJSON) → actual feature
    // Must iterate two levels deep to reach circleMarker / L.marker

    function _eachFeature(targetLayer, cb) {
        targetLayer.eachLayer(geoJsonLayer => {
            // GeoJSON wrapper — propagates setStyle to vector layers (polygon/line/circleMarker)
            // but NOT to L.marker. Iterate inner layers to reach the actual feature.
            if (typeof geoJsonLayer.eachLayer === 'function') {
                geoJsonLayer.eachLayer(cb)
            } else {
                cb(geoJsonLayer)
            }
        })
    }

    function setLayerOpacity(rootName, secondName, thirdName, opacityPct) {
        const key = `${rootName}/${secondName}/${thirdName}`
        const targetLayer = leafletLayerMap[key]
        if (!targetLayer) return

        const o = Math.max(0, Math.min(100, opacityPct)) / 100

        _eachFeature(targetLayer, feature => {
            if (feature.setStyle)  feature.setStyle({ opacity: o, fillOpacity: o })
            if (feature.setOpacity) feature.setOpacity(o)  // L.marker / ExtraMarkers
        })

        const node = categoryTree[rootName]?.children[secondName]?.children[thirdName]
        if (node) node.opacity = opacityPct
    }

    function setLayerColor(rootName, secondName, thirdName, color) {
        const key = `${rootName}/${secondName}/${thirdName}`
        const targetLayer = leafletLayerMap[key]
        if (!targetLayer) return

        const markerClass = iconMap[thirdName] || null
        const isMarker    = isMarkerMap[thirdName] || false

        _eachFeature(targetLayer, feature => {
            if (feature.setStyle) {
                // circleMarker, polygon, line
                feature.setStyle({ color, fillColor: color })
            } else if (isMarker && markerClass && feature.setIcon) {
                // ExtraMarkers (L.marker) — recreate icon with new color
                const EM = window.L?.ExtraMarkers
                if (EM) {
                    try {
                        feature.setIcon(EM.icon({
                            icon:        markerClass,
                            prefix:      'fa',
                            svg:         true,
                            markerColor: color,
                            iconColor:   'white',
                            shape:       'circle',
                            html:        `<i class="${markerClass}" style="color:white"></i>`,
                        }))
                    } catch (_) {}
                }
            }
        })

        const node = categoryTree[rootName]?.children[secondName]?.children[thirdName]
        if (node) node.color = color
        kategoriColors[thirdName] = color
    }

    return {
        mapInstance,
        categoryTree,
        kategoriColors,
        activeBasemap,
        loading,
        basemaps: BASEMAPS,
        initMap,
        changeBasemap,
        loadMetadata,
        loadCategoryData,
        addCategoryToMap,
        removeCategoryFromMap,
        setLayerOpacity,
        setLayerColor,
        resetView,
        toggleFullscreen,
        destroyMap,
        loadLayersTreeByCategory,
    }
}