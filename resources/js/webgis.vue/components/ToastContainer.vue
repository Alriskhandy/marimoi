<template>
    <div class="fixed top-20 right-3 z-[200] flex flex-col gap-2 pointer-events-none">
        <TransitionGroup name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="['flex items-center gap-2 px-3 py-2 rounded shadow-lg text-sm text-white max-w-xs pointer-events-auto', colorClass(toast.type)]"
            >
                <i :class="['bi', iconClass(toast.type)]"></i>
                <span class="flex-1">{{ toast.message }}</span>
                <button @click="removeToast(toast.id)" class="text-white/70 hover:text-white leading-none">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { useToast } from '../composables/useToast.js'

const { toasts, removeToast } = useToast()

const COLOR_MAP = {
    info: 'bg-blue-500',
    success: 'bg-green-500',
    error: 'bg-red-500',
    danger: 'bg-red-500',
    warning: 'bg-amber-500',
}

const ICON_MAP = {
    info: 'bi-info-circle-fill',
    success: 'bi-check-circle-fill',
    error: 'bi-x-circle-fill',
    danger: 'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
}

const colorClass = type => COLOR_MAP[type] ?? 'bg-gray-600'
const iconClass = type => ICON_MAP[type] ?? 'bi-info-circle-fill'
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(2rem);
}
</style>