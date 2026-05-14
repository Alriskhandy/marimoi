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
                <i class="bi bi-tools"></i>
                Layer Tools
            </h6>
            <button @click="$emit('close')" class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                <i class="bi bi-x-lg text-white"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="flex-1 flex flex-col">
            <!-- No active layer state -->
            <div v-if="!activeLayerName" class="flex flex-col items-center justify-center py-8 px-4 text-center gap-2">
                <i class="bi bi-sliders2 text-gray-300 text-4xl"></i>
                <p class="text-sm font-medium text-gray-500 mt-1">Tidak ada layer aktif</p>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Pilih layer pada daftar layer untuk menggunakan alat.
                </p>
            </div>

            <!-- Active layer tools (future integration) -->
            <div v-else class="p-4 space-y-3 text-sm text-gray-700">
                <div class="font-medium text-gray-800 truncate">{{ activeLayerName }}</div>
                <p class="text-xs text-gray-400">Alat layer akan tersedia di sini.</p>
            </div>
        </div>

        <!-- Footer actions -->
        <div class="flex-shrink-0 border-t border-gray-200 flex items-center divide-x divide-gray-200">
            <button
                class="flex-1 flex flex-col items-center gap-1 py-3 text-xs text-gray-600 hover:bg-gray-50 transition-colors"
                title="Salin tautan peta"
            >
                <i class="bi bi-link-45deg text-base"></i>
                Copy Link
            </button>
            <button
                class="flex-1 flex flex-col items-center gap-1 py-3 text-xs text-gray-600 hover:bg-gray-50 transition-colors"
                title="Bagikan peta"
            >
                <i class="bi bi-share-fill text-base"></i>
                Share
            </button>
            <button
                class="flex-1 flex flex-col items-center gap-1 py-3 text-xs text-gray-600 hover:bg-gray-50 transition-colors"
                title="QR Code peta"
            >
                <i class="bi bi-qr-code text-base"></i>
                QR Code
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    visible:         Boolean,
    activeLayerName: { type: String, default: null },
})
defineEmits(['close'])
</script>
