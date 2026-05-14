<template>
    <div
        :class="[
            'absolute top-0 right-0 w-[280px] md:w-[300px] h-[calc(100vh-44px)] bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900',
            visible ? '' : 'hidden',
        ]"
    >
        <!-- Header -->
        <div class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
            <h6 class="text-white mb-0 text-sm font-semibold">Legenda</h6>
            <button @click="$emit('close')" class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                <i class="bi bi-x-lg text-white"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="ml-2 max-h-[calc(100vh-200px)] overflow-y-auto text-sm">
            <div v-if="!legendItems.length" class="text-center py-8 text-gray-400">
                <i class="bi bi-layers text-2xl block mb-2"></i>
                <p class="text-sm">Tidak ada layer aktif</p>
                <p class="text-xs">Aktifkan layer untuk melihat legenda</p>
            </div>
            <div
                v-for="item in legendItems"
                :key="item.name"
                class="flex items-center gap-2 py-2 border-b border-gray-100 last:border-0"
            >
                <span
                    :style="{ backgroundColor: item.color }"
                    class="w-4 h-4 rounded-sm flex-shrink-0 border border-gray-200"
                ></span>
                <span class="flex-1 text-gray-800 truncate">{{ item.name }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    visible: Boolean,
    legendItems: { type: Array, default: () => [] },
})

defineEmits(['close'])
</script>