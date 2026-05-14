<template>
    <Transition name="modal">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="$emit('close')"
        >
            <div class="mx-3 bg-white text-gray-700 relative self-center rounded-lg shadow-lg w-full max-w-lg">
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-300">
                    <h5 class="text-lg font-semibold">Panduan Penggunaan</h5>
                </div>

                <!-- Body -->
                <div class="px-4 py-5 text-sm text-justify min-h-[80px]">
                    <p v-html="currentStepContent"></p>
                </div>

                <!-- Footer -->
                <div class="flex justify-between items-center px-4 py-3 border-t border-gray-300">
                    <span class="text-xs text-gray-400">{{ currentStep }} / {{ steps.length }}</span>
                    <div class="flex gap-2">
                        <button
                            @click="$emit('close')"
                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm"
                        >Skip</button>
                        <button
                            @click="prev"
                            :disabled="currentStep === 1"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-1 rounded text-sm disabled:opacity-40"
                        >Prev</button>
                        <button
                            @click="next"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm"
                        >{{ currentStep === steps.length ? 'Selesai' : 'Next' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({ show: Boolean })
const emit = defineEmits(['close'])

const currentStep = ref(1)

watch(() => props.show, (val) => {
    if (val) currentStep.value = 1
})

const steps = [
    'Selamat datang di WebGIS Perencanaan! Gunakan tombol-tombol kontrol untuk mengatur tampilan peta.',
    'Tombol <strong><i class="bi bi-plus border border-gray-700 p-1"></i> Zoom In</strong> & <strong><i class="bi bi-dash border border-gray-700 p-1"></i> Zoom Out</strong> digunakan untuk mengatur zoom peta.',
    'Gunakan tombol <strong><i class="bi bi-info-circle-fill border border-gray-700 p-1"></i> Bantuan</strong> untuk melihat panduan ini kapan saja.',
    'Tombol <strong><i class="bi bi-list-ul border border-gray-700 p-1"></i> Legenda Peta</strong> menampilkan keterangan simbol pada peta.',
    'Tombol <strong><i class="bi bi-grid-fill border border-gray-700 p-1"></i> Basemap Peta</strong> digunakan untuk memilih jenis peta dasar.',
    'Tombol <strong><i class="bi bi-layers-fill border border-gray-700 p-1"></i> Layer Peta</strong> digunakan untuk mengatur layer yang ingin ditampilkan.',
    'Tombol <strong><i class="bi bi-file-earmark-arrow-down-fill border border-gray-700 p-1"></i> Download Peta</strong> memungkinkan Anda mengunduh peta.',
    'Tombol <strong><i class="bi bi-arrows-fullscreen border border-gray-700 p-1"></i> Fullscreen</strong> memungkinkan Anda untuk masuk dan keluar dari tampilan penuh.',
    'Tombol <strong><i class="bi bi-house-door-fill border border-gray-700 p-1"></i> Home</strong> memungkinkan Anda kembali ke default zoom dari peta.',
]

const currentStepContent = computed(() => steps[currentStep.value - 1])

function prev() {
    if (currentStep.value > 1) currentStep.value--
}

function next() {
    if (currentStep.value < steps.length) {
        currentStep.value++
    } else {
        emit('close')
    }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>