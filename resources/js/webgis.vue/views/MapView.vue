<template>
    <section
        class="relative p-0 w-full overflow-hidden"
        style="margin-top: 44px; height: calc(100vh - 44px);"
    >
        <!-- Toast -->
        <ToastContainer />

        <!-- Guide Modal -->
        <GuideModal :show="showGuide" @close="showGuide = false" />

        <!-- ── Left-side sidebars ────────────────────────────────────── -->
        <SidebarLayerTools
            :visible="activeSidebar === 'tools'"
            @close="closeSidebar"
        />
        <SidebarLayer
            :visible="activeSidebar === 'layer'"
            :category-tree="categoryTree"
            :loading="loading"
            @close="closeSidebar"
            @toggle-third="onThirdToggle"
            @toggle-second="onSecondToggle"
        />

        <!-- ── Right-side sidebars ───────────────────────────────────── -->
        <SidebarBasemap
            :visible="activeSidebar === 'basemap'"
            :active="activeBasemap"
            :basemaps="basemaps"
            @close="closeSidebar"
            @change="onBasemapChange"
        />
        <SidebarLegend
            :visible="activeSidebar === 'legend'"
            :legend-items="activeLegendItems"
            @close="closeSidebar"
        />
        <SidebarDownload
            :visible="activeSidebar === 'download'"
            :documents="documents"
            @close="closeSidebar"
        />

        <!-- ── Left map controls (below Leaflet zoom) ────────────── -->
        <NavButtons
            :tools-active="activeSidebar === 'tools'"
            @fullscreen="onFullscreen"
            @reset-zoom="resetView"
            @toggle-tools="toggleSidebar('tools')"
        />

        <!-- ── Map canvas — must use relative + w/h-full so Leaflet can read dimensions ── -->
        <div id="map" class="relative z-10 w-full h-full"></div>

        <!-- ── Bottom bar (feature detail drawer) ───────────────── -->
        <BottomBar />

        <!-- ── Bappeda logo — bottom right watermark ─────────────── -->
        <img
            :src="bappedaLogoUrl"
            alt="BAPPEDA"
            class="absolute bottom-6 right-3 z-[100] opacity-80 pointer-events-none"
            style="height: 32px; width: auto;"
        />

    </section>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

import ToastContainer     from '../components/ToastContainer.vue'
import GuideModal         from '../components/GuideModal.vue'
import SidebarLayerTools  from '../components/SidebarLayerTools.vue'
import SidebarLayer       from '../components/SidebarLayer.vue'
import SidebarBasemap     from '../components/SidebarBasemap.vue'
import SidebarLegend      from '../components/SidebarLegend.vue'
import SidebarDownload    from '../components/SidebarDownload.vue'
import NavButtons         from '../components/NavButtons.vue'
import BottomBar          from '../components/BottomBar.vue'

import { useToast }      from '../composables/useToast.js'
import { useLeafletMap } from '../composables/useLeafletMap.js'
import { useSidebar }    from '../composables/useSidebar.js'

// ─── Sidebar state (singleton shared with Navbar) ──────────────────────────
const { activeSidebar, toggleSidebar, closeSidebar } = useSidebar()

// ─── Local state ───────────────────────────────────────────────────────────
const showGuide      = ref(false)
const documents      = ref(window.MARIMOI_CONFIG?.documents ?? [])
const bappedaLogoUrl = '/frontend/img/logo/logo-bappeda.png'

// ─── Composables ───────────────────────────────────────────────────────────
const { showToast } = useToast()

const {
    categoryTree,
    kategoriColors,
    activeBasemap,
    loading,
    basemaps,
    initMap,
    changeBasemap,
    loadCategoryData,
    addCategoryToMap,
    removeCategoryFromMap,
    resetView,
    toggleFullscreen,
    destroyMap,
    loadLayersTreeByCategory,
} = useLeafletMap()

// ─── Layer Tools: first checked layer name ─────────────────────────────────
const activeToolsLayerName = computed(() => {
    for (const [, rootNode] of Object.entries(categoryTree)) {
        for (const [, secondNode] of Object.entries(rootNode.children)) {
            for (const [thirdName, thirdNode] of Object.entries(secondNode.children)) {
                if (thirdNode.checked) return thirdName
            }
        }
    }
    return null
})

// ─── Legend items ──────────────────────────────────────────────────────────
const activeLegendItems = computed(() => {
    const items = []
    for (const [, rootNode] of Object.entries(categoryTree)) {
        for (const [secondName, secondNode] of Object.entries(rootNode.children)) {
            for (const [thirdName, thirdNode] of Object.entries(secondNode.children)) {
                if (thirdNode.checked) {
                    items.push({
                        name:  thirdName,
                        color: kategoriColors[thirdName] || kategoriColors[secondName] || '#007fff',
                    })
                }
            }
        }
    }
    return items
})

// ─── Layer toggle handlers ──────────────────────────────────────────────────
async function onThirdToggle(thirdName, secondName, rootName, checked) {
    const node = categoryTree[rootName]?.children[secondName]?.children[thirdName]
    if (!node) return
    if (checked) {
        if (!node.loaded) await loadCategoryData(thirdName, secondName, rootName, showToast)
        addCategoryToMap(thirdName, secondName, rootName)
    } else {
        removeCategoryFromMap(thirdName, secondName, rootName)
    }
}

async function onSecondToggle(secondName, rootName, checked) {
    const secondNode = categoryTree[rootName]?.children[secondName]
    if (!secondNode) return
    for (const [thirdName, node] of Object.entries(secondNode.children)) {
        if (checked) {
            if (!node.loaded) await loadCategoryData(thirdName, secondName, rootName, showToast)
            addCategoryToMap(thirdName, secondName, rootName)
        } else {
            removeCategoryFromMap(thirdName, secondName, rootName)
        }
    }
}

// ─── Basemap ───────────────────────────────────────────────────────────────
function onBasemapChange(id) {
    changeBasemap(id)
    showToast(`Basemap diubah ke ${basemaps.find(b => b.id === id)?.label ?? id}`, 'success')
}

// ─── Fullscreen ────────────────────────────────────────────────────────────
function onFullscreen() {
    toggleFullscreen()
    showToast(!document.fullscreenElement ? 'Masuk mode fullscreen' : 'Keluar dari fullscreen', 'info')
}

// ─── Keyboard shortcuts ────────────────────────────────────────────────────
function onKeydown(e) {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return
    if (e.key === 'Escape')                        { closeSidebar(); return }
    if (e.ctrlKey && e.key.toLowerCase() === 'l') { e.preventDefault(); toggleSidebar('layer') }
    if (e.ctrlKey && e.key.toLowerCase() === 'b') { e.preventDefault(); toggleSidebar('basemap') }
    if (e.ctrlKey && e.key.toLowerCase() === 'h') { e.preventDefault(); showGuide.value = true }
}

// ─── Lifecycle ─────────────────────────────────────────────────────────────
onMounted(async () => {
    initMap('map')
    document.addEventListener('keydown', onKeydown)
    await loadLayersTreeByCategory(showToast)
})

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown)
    destroyMap()
})
</script>
