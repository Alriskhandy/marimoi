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
            <div class="flex-1 overflow-y-auto p-4 text-sm text-gray-700">
                <!-- Feature detail — wired up later -->
                <slot>
                    <div class="flex flex-col items-center justify-center h-full gap-2 text-center text-gray-400 py-6">
                        <i class="bi bi-info-circle text-2xl"></i>
                        <p class="font-medium text-gray-500">Tidak ada data yang tersedia untuk ditampilkan.</p>
                        <p class="text-xs leading-relaxed max-w-xs">
                            Data disinkronkan dengan lapisan yang terlihat di peta, Anda dapat
                            menggunakan alat &ldquo;Daftar Lapisan&rdquo; untuk mengaktifkan/menonaktifkan lapisan.
                        </p>
                    </div>
                </slot>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'

const DEFAULT_HEIGHT = 200
const MIN_HEIGHT     = 80
const MAX_HEIGHT     = () => Math.floor(window.innerHeight / 2)

const isOpen      = ref(false)
const panelHeight = ref(DEFAULT_HEIGHT)
const isDragging  = ref(false)

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

// Expose for parent to open programmatically (e.g. on feature click)
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
