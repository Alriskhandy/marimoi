<template>
    <div
        :class="[
            'fixed w-[260px] h-auto bg-white border border-gray-300 shadow-lg z-[500] text-gray-900 flex flex-col rounded-md overflow-hidden',
            visible ? '' : 'hidden',
        ]"
        :style="{ top: '204px', left: '10px', maxHeight: 'calc(100vh - 208px)' }"
    >
        <!-- Header -->
        <div class="flex-shrink-0 flex justify-between items-center bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-2 px-3">
            <h6 class="text-white mb-0 text-sm font-semibold flex items-center gap-2">
                <i class="bi bi-sliders2"></i>
                Layer Tools
            </h6>
            <button @click="$emit('close')" class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                <i class="bi bi-x-lg text-white"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto">

            <!-- Empty state -->
            <div v-if="activeLayers.length === 0" class="flex flex-col items-center justify-center gap-2 text-center text-gray-400 py-8 px-4">
                <i class="bi bi-layers text-2xl"></i>
                <p class="text-xs font-medium text-gray-500">Tidak ada layer aktif</p>
                <p class="text-xs leading-relaxed max-w-[200px]">
                    Aktifkan layer dari panel Layer untuk menggunakan alat ini.
                </p>
            </div>

            <!-- Per-layer controls -->
            <div v-else class="divide-y divide-gray-100">
                <div
                    v-for="item in activeLayers"
                    :key="item.key"
                    class="px-3 py-3"
                >
                    <!-- Layer name -->
                    <div class="flex items-center gap-2 mb-3">
                        <span
                            class="w-2.5 h-2.5 rounded-sm flex-shrink-0"
                            :style="{ backgroundColor: item.node.color || '#007fff' }"
                        ></span>
                        <span class="text-xs font-semibold text-gray-700 truncate">{{ item.thirdName }}</span>
                    </div>

                    <!-- Transparency -->
                    <div class="mb-3">
                        <label class="text-[11px] text-gray-500 mb-1 block">Transparansi</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="range"
                                min="0" max="100" step="1"
                                :value="item.node.opacity ?? 100"
                                @input="onOpacity(item, +$event.target.value)"
                                class="flex-1 h-1.5 accent-[#007fff] cursor-pointer"
                            />
                            <input
                                type="number"
                                min="0" max="100" step="1"
                                :value="item.node.opacity ?? 100"
                                @change="onOpacity(item, clamp(+$event.target.value))"
                                class="w-12 text-xs text-center border border-gray-200 rounded px-1 py-0.5 outline-none focus:border-blue-400"
                            />
                            <span class="text-[11px] text-gray-400 flex-shrink-0">%</span>
                        </div>
                    </div>

                    <!-- Color -->
                    <div>
                        <label class="text-[11px] text-gray-500 mb-1 block">Warna Feature</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="color"
                                :value="item.node.color || '#007fff'"
                                @input="onColor(item, $event.target.value)"
                                class="w-8 h-8 rounded cursor-pointer border border-gray-200 p-0.5 bg-white"
                                title="Pilih warna feature"
                            />
                            <input
                                type="text"
                                :value="item.node.color || '#007fff'"
                                @change="onColor(item, $event.target.value)"
                                @blur="onColor(item, $event.target.value)"
                                maxlength="7"
                                placeholder="#007fff"
                                class="flex-1 text-xs border border-gray-200 rounded px-2 py-1 outline-none focus:border-blue-400 font-mono"
                            />
                            <!-- Reset button -->
                            <button
                                @click="resetColor(item)"
                                title="Reset warna awal"
                                class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                            >
                                <i class="bi bi-arrow-counterclockwise text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useLeafletMap } from '../composables/useLeafletMap.js'

defineProps({ visible: Boolean })
defineEmits(['close'])

const { categoryTree, kategoriColors, setLayerOpacity, setLayerColor } = useLeafletMap()

// Flat list of currently active (checked) leaf nodes
const activeLayers = computed(() => {
    const result = []
    for (const [rootName, rootNode] of Object.entries(categoryTree)) {
        for (const [secondName, secondNode] of Object.entries(rootNode.children)) {
            for (const [thirdName, thirdNode] of Object.entries(secondNode.children)) {
                if (thirdNode.checked) {
                    result.push({
                        key:        `${rootName}/${secondName}/${thirdName}`,
                        rootName,
                        secondName,
                        thirdName,
                        node:       thirdNode,
                        origColor:  kategoriColors[thirdName] || thirdNode.color || '#007fff',
                    })
                }
            }
        }
    }
    return result
})

function clamp(v) { return Math.max(0, Math.min(100, v || 0)) }

function onOpacity(item, val) {
    setLayerOpacity(item.rootName, item.secondName, item.thirdName, clamp(val))
}

function onColor(item, val) {
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
        setLayerColor(item.rootName, item.secondName, item.thirdName, val)
    }
}

function resetColor(item) {
    const orig = item.origColor
    setLayerColor(item.rootName, item.secondName, item.thirdName, orig)
    setLayerOpacity(item.rootName, item.secondName, item.thirdName, 100)
}
</script>
