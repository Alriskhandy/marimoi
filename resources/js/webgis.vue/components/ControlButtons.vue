<template>
    <!-- Fixed in the navbar strip (top-0 right-0, z above navbar z-1000) -->
    <div
        class="fixed top-0 right-0 h-[70px] z-[1010] flex items-center"
        role="group"
        aria-label="Sidebar Control Buttons"
    >
        <button
            v-for="btn in buttons"
            :key="btn.id"
            @click="handleClick(btn)"
            :title="btn.label"
            :class="[
                'px-[14px] h-full flex items-center justify-center text-base transition-colors duration-200',
                activeSidebar === btn.sidebar
                    ? 'text-white bg-white/20'
                    : 'text-white/75 hover:text-white hover:bg-white/10',
            ]"
        >
            <i :class="['bi', btn.icon]"></i>
        </button>
    </div>
</template>

<script setup>
const props = defineProps({ activeSidebar: String })
const emit = defineEmits(['toggle-sidebar', 'show-guide'])

const buttons = [
    { id: 'help',    icon: 'bi-info-circle-fill', label: 'Bantuan',      action: 'guide'   },
    { id: 'legend',  icon: 'bi-list-ul',           label: 'Legenda Peta', sidebar: 'legend' },
    { id: 'basemap', icon: 'bi-grid-fill',          label: 'Basemap Peta', sidebar: 'basemap'},
    { id: 'layer',   icon: 'bi-layers-fill',        label: 'Layer Peta',   sidebar: 'layer'  },
]

function handleClick(btn) {
    if (btn.action === 'guide') {
        emit('show-guide')
    } else {
        emit('toggle-sidebar', btn.sidebar)
    }
}
</script>
