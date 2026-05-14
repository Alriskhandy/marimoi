<template>
    <div
        :class="[
            'absolute top-0 right-0 w-[280px] md:w-[300px] h-[calc(100vh-44px)] bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900',
            visible ? '' : 'hidden',
        ]"
    >
        <!-- Header -->
        <div class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
            <h6 class="text-white mb-0 text-sm font-semibold">Basemap</h6>
            <button @click="$emit('close')" class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                <i class="bi bi-x-lg text-white"></i>
            </button>
        </div>

        <!-- Grid list -->
        <div class="pt-1 max-h-[calc(100vh-120px)] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3">
                <div
                    v-for="bm in basemaps"
                    :key="bm.id"
                    @click="select(bm.id)"
                    class="col-span-1 cursor-pointer"
                >
                    <div class="overflow-hidden">
                        <!-- Preview image -->
                        <div class="relative w-full h-[70px] bg-gray-100 border-2 transition-all duration-200"
                            :class="active === bm.id ? 'border-blue-500 shadow-[0_0_10px_rgba(0,123,255,0.6)]' : 'border-white shadow-md'"
                        >
                            <img
                                :src="previewUrl(bm.id)"
                                :alt="bm.label"
                                class="w-full h-full object-cover"
                                @error="onImgError($event)"
                            />
                            <!-- Fallback shown when img fails to load -->
                            <div class="img-fallback absolute inset-0 flex-col items-center justify-center bg-gray-50" style="display:none">
                                <i class="bi bi-image text-gray-400 text-xl"></i>
                                <span class="text-xs text-gray-400 mt-1">Preview</span>
                            </div>
                        </div>

                        <!-- Label -->
                        <div
                            class="py-1 px-1 text-center leading-tight transition-colors duration-200"
                            :class="active === bm.id ? 'text-blue-600 font-semibold' : 'text-gray-700'"
                            style="font-size: 0.68rem"
                        >
                            {{ bm.label }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    visible: Boolean,
    active: String,
    basemaps: Array,
})

const emit = defineEmits(['close', 'change'])

function select(id) {
    emit('change', id)
}

function previewUrl(id) {
    return `/frontend/img/map-preview/${id}-min.png`
}

function onImgError(e) {
    // Hide the broken image and show fallback sibling
    e.target.style.display = 'none'
    const fallback = e.target.nextElementSibling
    if (fallback) fallback.style.display = 'flex'
}
</script>