import { ref, reactive } from 'vue'

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
        const map = L().map(containerId, {
            zoomControl: true,
            attributionControl: true,
        }).setView(DEFAULT_CENTER, DEFAULT_ZOOM)

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
            targetLayer.clearLayers()

            let offset = 0
            const chunkSize = 500
            const maxRecords = 3000
            let totalLoaded = 0
            let hasMore = true

            const color = kategoriColors[thirdName] || kategoriColors[secondName] || '#007fff'
            const useMarker = isMarkerMap[thirdName] || false
            const markerIcon = iconMap[thirdName] || null

            // Build ExtraMarkers icon once if this category uses marker icons.
            // cat.icon is stored as full class string e.g. "fa fa-hospital-o",
            // so prefix must be '' to avoid duplicating "fa".
            let extraMarkerIcon = null
            if (useMarker && markerIcon) {
                const EM = window.L?.ExtraMarkers
                if (EM) {
                    try {
                        extraMarkerIcon = EM.icon({
                            icon: markerIcon,
                            prefix: '',
                            svg: true,
                            markerColor: color || '#007fff',
                            iconColor: '#ffffff',
                            shape: 'circle',
                        })
                    } catch (_) { /* fall back to circleMarker */ }
                }
            }

            // Use the new features GeoJSON API keyed by layerId
            const layerId = node.layerId

            while (hasMore && totalLoaded < maxRecords) {
                const url = `/api/features/geojson/${layerId}?limit=${chunkSize}&offset=${offset}`
                const res = await fetch(url)
                if (!res.ok) throw new Error(`HTTP ${res.status}`)

                const data = await res.json()
                if (!data?.features?.length) break

                data.features.forEach(feature => {
                    if (!feature?.geometry) return
                    const geomType = feature.geometry.type
                    const isLine = geomType === 'LineString' || geomType === 'MultiLineString'

                    L().geoJSON(feature, {
                        style: () => isLine
                            ? { color, weight: 5, opacity: 0.9, lineCap: 'round', lineJoin: 'round' }
                            : { color, weight: 2, opacity: 0.7, fillColor: color, fillOpacity: 0.4 },
                        pointToLayer: (f, latlng) => {
                            if (extraMarkerIcon) {
                                return L().marker(latlng, { icon: extraMarkerIcon })
                            }
                            return L().circleMarker(latlng, {
                                radius: 8,
                                fillColor: color,
                                color: '#172953',
                                weight: 1,
                                opacity: 0.9,
                                fillOpacity: 0.75,
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
        const p = feature.properties || {}
        const name = p.NAMA_OBJEK || p.NAMOBJ || p.nama || p.name || 'Detail'
        const kategori = p.kategori || ''

        const excluded = new Set(['geometry', 'id', 'kategori_color', 'kategori', 'parent_kategori', 'kategori_full_path'])
        const rows = Object.entries(p)
            .filter(([k, v]) => v != null && v !== '' && !excluded.has(k))
            .slice(0, 8)
            .map(([k, v]) => {
                const label = k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
                return `<tr>
                    <td style="color:#6b7280;padding:2px 8px 2px 0;vertical-align:top;white-space:nowrap;font-size:11px">${label}</td>
                    <td style="color:#1f2937;font-size:11px;padding:2px 0">${v}</td>
                </tr>`
            })
            .join('')

        layer.bindPopup(`
            <div style="min-width:200px;max-width:300px;border-radius:8px;overflow:hidden">
                <div style="background:linear-gradient(to right,#007fff,#0066cc);color:white;padding:8px 12px">
                    <p style="margin:0;font-weight:600;font-size:13px">${name}</p>
                    <p style="margin:0;font-size:11px;color:#bfdbfe">${kategori}</p>
                </div>
                <div style="padding:8px 12px">
                    <table style="width:100%;border-collapse:collapse">${rows}</table>
                </div>
            </div>
        `, { className: 'tailwind-popup', maxWidth: 320 })
    }

    async function loadLayersTreeByCategory(toastFn) {
        loading.value = true
        toastFn?.('Memuat daftar layer...', 'info')

        try {
            const res = await fetch('/api/layers/tree')
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
        resetView,
        toggleFullscreen,
        destroyMap,
        loadLayersTreeByCategory,
    }
}