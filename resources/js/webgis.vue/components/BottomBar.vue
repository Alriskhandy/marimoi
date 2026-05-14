<template>
    <!-- ── Toggle button — sticks to top edge of panel, no lag ── -->
    <button
        @click="toggle"
        :title="isOpen ? 'Sembunyikan' : 'Tampilkan Detail'"
        class="fixed left-1/2 -translate-x-1/2 z-[350] flex items-center justify-center bg-white shadow-md rounded-t-md w-16 h-6 hover:bg-gray-50"
        :style="{
            bottom: isOpen ? panelHeight + 'px' : '0px',
            transition: isDragging ? 'none' : 'bottom 0.25s ease',
        }"
    >
        <i :class="['bi text-gray-800 text-xs', isOpen ? 'bi-chevron-down' : 'bi-chevron-up']"></i>
    </button>

    <!-- ── Bottom bar panel ─────────────────────────────────── -->
    <Transition name="slide-up">
        <div
            v-show="isOpen"
            class="fixed bottom-0 left-0 right-0 z-[340] bg-white border-t border-gray-200 flex flex-col"
            :style="{
                height: panelHeight + 'px',
                boxShadow: '0 -2px 12px rgba(0,0,0,.12)',
                transition: isDragging ? 'none' : '',
            }"
        >
            <!-- Drag handle -->
            <div
                class="flex-shrink-0 flex items-center justify-center h-4 cursor-ns-resize bg-gray-50 border-b border-gray-100 select-none"
                @mousedown="startDrag"
                @touchstart.passive="startDragTouch"
                title="Seret untuk mengubah tinggi"
            >
                <span class="w-8 h-1 rounded-full bg-gray-300 block"></span>
            </div>

            <!-- Content area -->
            <div class="flex-1 min-h-0 overflow-y-auto">

                <!-- Empty state -->
                <div v-if="!feature" class="flex flex-col items-center justify-center h-full gap-2 text-center text-gray-400 py-6 px-4">
                    <i class="bi bi-info-circle text-2xl"></i>
                    <p class="font-medium text-gray-500">Tidak ada data yang tersedia untuk ditampilkan.</p>
                    <p class="text-xs leading-relaxed max-w-xs">
                        Data disinkronkan dengan lapisan yang terlihat di peta, Anda dapat
                        menggunakan alat &ldquo;Daftar Lapisan&rdquo; untuk mengaktifkan/menonaktifkan lapisan.
                    </p>
                </div>

                <!-- Feature detail -->
                <div v-else class="h-full p-4">

                    <!-- Header -->
                    <div class="flex items-start justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                        <div>
                            <h3 class="text-sm font-700 text-[#007fff] leading-tight mb-0.5" style="font-weight:700">
                                {{ feature.name }}
                            </h3>
                            <p class="text-xs text-gray-400 m-0">{{ feature.kategori }}</p>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <!-- Feedback / detail button — hidden for tematik type -->
                            <a
                                v-if="detailUrl"
                                :href="detailUrl"
                                target="_blank"
                                rel="noopener"
                                title="Kirim Feedback / Lihat Detail"
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium bg-[#007fff] text-white hover:bg-[#0066cc] transition-colors no-underline"
                            >
                                <i class="bi bi-send-fill text-[10px]"></i>
                                Feedback
                            </a>
                            <button
                                @click="clearFeature"
                                class="text-gray-400 hover:text-gray-600 p-1 rounded transition-colors"
                            >
                                <i class="bi bi-x-lg text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- With image: 7:5 grid -->
                    <div
                        v-if="feature.imageUrl"
                        class="grid gap-4 h-[calc(100%-60px)]"
                        style="grid-template-columns: 7fr 5fr"
                    >
                        <!-- Image (left, 7 parts) -->
                        <div class="overflow-hidden rounded-lg">
                            <img
                                :src="feature.imageUrl"
                                :alt="feature.name"
                                class="w-full h-full object-cover"
                                style="max-height: 100%"
                            />
                        </div>

                        <!-- Properties (right, 5 parts) -->
                        <div class="overflow-y-auto">
                            <table class="w-full text-xs border-collapse">
                                <tbody>
                                    <tr
                                        v-for="[k, v] in propRows"
                                        :key="k"
                                        class="border-b border-gray-50"
                                    >
                                        <td class="font-semibold text-gray-600 py-1.5 pr-3 whitespace-nowrap align-top w-2/5">
                                            {{ formatKey(k) }}
                                        </td>
                                        <td class="text-gray-700 py-1.5 align-top">{{ v }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Without image: full-width table -->
                    <div v-else>
                        <table class="w-full text-xs border-collapse">
                            <tbody>
                                <tr
                                    v-for="[k, v] in propRows"
                                    :key="k"
                                    class="border-b border-gray-100"
                                >
                                    <td class="font-semibold text-gray-600 py-2 pr-4 whitespace-nowrap align-top w-1/4">
                                        {{ formatKey(k) }}
                                    </td>
                                    <td class="text-gray-700 py-2 align-top">{{ v }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { useFeatureDetail } from '../composables/useFeatureDetail.js'

const { selectedFeature, bottomBarOpen, clearFeature } = useFeatureDetail()

const feature = computed(() => selectedFeature.value)

// Excluded keys from the properties table
const EXCLUDED = new Set([
    // system / internal
    'id','uuid','geometry','geom',
    // category metadata
    'kategori','kategori_id','kategori_color','kategori_full_path',
    'parent_kategori','parent_kategori_id',
    // header fields already shown
    'NAMA_OBJEK','NAMOBJ','nama','name','gambar',
    // data type / classification
    'data_type','sub_type','type','original_type',
    // relations
    'layer_id','user_id','feature_id',
    // timestamps & counters
    'created_at','updated_at','deleted_at','views','year',
    // legacy
    'legacy_uuid','deskripsi',
])

const propRows = computed(() => {
    if (!feature.value) return []
    return Object.entries(feature.value.properties || {})
        .filter(([k, v]) => v != null && v !== '' && !EXCLUDED.has(k))
})

function formatKey(k) {
    return k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

// URL ke halaman detail — hanya untuk type selain tematik
const detailUrl = computed(() => {
    const p = feature.value?.properties
    if (!p) return null

    const type = p.data_type    || ''
    const sub  = p.sub_type     || ''
    // legacy_uuid maps to data_spatial.uuid used by Laravel detail routes
    const uuid = p.legacy_uuid  || ''

    if (!uuid || type === 'tematik') return null

    const ROUTE_MAP = {
        proyek_strategis: () => sub === 'psn'
            ? `/proyek-strategis-nasional/${uuid}`
            : `/proyek-strategis-daerah/${uuid}`,
        usulan_musrenbang: () => `/usulan-musrenbang/${uuid}`,
        pokir_dprd:        () => `/pokir-dprd/${uuid}`,
    }

    return ROUTE_MAP[type]?.() ?? null
})

// ── Panel open/close ────────────────────────────────────────
const DEFAULT_HEIGHT = 200
const MIN_HEIGHT     = 80
const MAX_HEIGHT     = () => Math.floor(window.innerHeight / 2)

const isOpen      = ref(false)
const panelHeight = ref(DEFAULT_HEIGHT)
const isDragging  = ref(false)

// Open bottom bar when feature detail is triggered
watch(bottomBarOpen, (val) => {
    if (val) {
        isOpen.value = true
        bottomBarOpen.value = false   // reset so next click also triggers
    }
})

function toggle() {
    isOpen.value = !isOpen.value
}

// ── Mouse drag ──────────────────────────────────────────────
let dragStartY = 0
let dragStartH = 0

function startDrag(e) {
    isDragging.value = true
    dragStartY = e.clientY
    dragStartH = panelHeight.value
    document.addEventListener('mousemove', onDragMove)
    document.addEventListener('mouseup',   onDragEnd)
}

function onDragMove(e) {
    const dy = dragStartY - e.clientY
    panelHeight.value = Math.min(Math.max(dragStartH + dy, MIN_HEIGHT), MAX_HEIGHT())
}

function onDragEnd() {
    isDragging.value = false
    document.removeEventListener('mousemove', onDragMove)
    document.removeEventListener('mouseup',   onDragEnd)
    if (panelHeight.value <= MIN_HEIGHT + 10) {
        isOpen.value = false
        panelHeight.value = DEFAULT_HEIGHT
    }
}

// ── Touch drag ──────────────────────────────────────────────
function startDragTouch(e) {
    isDragging.value = true
    dragStartY = e.touches[0].clientY
    dragStartH = panelHeight.value
    document.addEventListener('touchmove', onTouchMove, { passive: true })
    document.addEventListener('touchend',  onTouchEnd)
}

function onTouchMove(e) {
    const dy = dragStartY - e.touches[0].clientY
    panelHeight.value = Math.min(Math.max(dragStartH + dy, MIN_HEIGHT), MAX_HEIGHT())
}

function onTouchEnd() {
    isDragging.value = false
    document.removeEventListener('touchmove', onTouchMove)
    document.removeEventListener('touchend',  onTouchEnd)
    if (panelHeight.value <= MIN_HEIGHT + 10) {
        isOpen.value = false
        panelHeight.value = DEFAULT_HEIGHT
    }
}

onUnmounted(() => {
    document.removeEventListener('mousemove', onDragMove)
    document.removeEventListener('mouseup',   onDragEnd)
    document.removeEventListener('touchmove', onTouchMove)
    document.removeEventListener('touchend',  onTouchEnd)
})

defineExpose({ isOpen, open: () => { isOpen.value = true } })
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.25s ease, opacity 0.25s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
</style>
