<template>
    <div
        :class="[
            'absolute top-0 right-0 w-[280px] md:w-[300px] h-[calc(100vh-44px)] bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900',
            visible ? '' : 'hidden',
        ]"
    >
        <!-- Header -->
        <div class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
            <h6 class="text-white mb-0 text-sm font-semibold">Unduh Data/Informasi</h6>
            <button @click="$emit('close')" class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                <i class="bi bi-x-lg text-white"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="ml-2 max-h-[calc(100vh-200px)] overflow-y-auto text-sm">
            <p class="text-sm text-black mb-3">Daftar Dokumen :</p>

            <ul v-if="documents && documents.length > 0" class="space-y-2">
                <li v-for="doc in documents" :key="doc.nama ?? doc.name">
                    <a
                        :href="doc.url ?? doc.file"
                        :title="doc.nama ?? doc.name"
                        download
                        class="text-black hover:text-blue-800 hover:underline transition-colors duration-200 flex items-center gap-2"
                    >
                        <i class="bi bi-file-earmark-arrow-down text-gray-700"></i>
                        {{ doc.nama ?? doc.name }}
                    </a>
                </li>
            </ul>

            <div v-else class="text-center py-8">
                <i class="bi bi-folder2-open text-4xl text-gray-400 mb-3 block"></i>
                <p class="text-gray-700 text-sm">Tidak ada dokumen tersedia</p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({ visible: Boolean, documents: Array })
defineEmits(['close'])
</script>