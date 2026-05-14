<template>
    <!--
        top = navbar(44) + leaflet-margin(10) + zoom+(28) + zoom-(28) + gap(4) = 114px
        Sliders group: 114 + fullscreen(28) + home(28) + gap(4) = 174px
    -->
    <div
        class="fixed left-[10px] top-[114px] z-[400] flex flex-col"
        style="box-shadow: 0 1px 5px rgba(0,0,0,.4); border-radius: 4px; overflow: hidden;"
        role="group"
        aria-label="Navigation Controls"
    >
        <button @click="$emit('fullscreen')" title="Perbesar Layar" class="nav-btn">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>
        <button @click="$emit('reset-zoom')" title="Kembali ke Tampilan Awal" class="nav-btn border-b-0">
            <i class="bi bi-house-door-fill"></i>
        </button>
    </div>

    <!-- Layer tools button — separate group with gap -->
    <div
        class="fixed left-[10px] top-[174px] z-[400] flex flex-col"
        style="box-shadow: 0 1px 5px rgba(0,0,0,.4); border-radius: 4px; overflow: hidden;"
    >
        <button
            @click="$emit('toggle-tools')"
            title="Layer Tools"
            :class="['nav-btn border-b-0', toolsActive ? 'nav-btn--active' : '']"
        >
            <i class="bi bi-sliders"></i>
        </button>
    </div>
</template>

<script setup>
defineProps({ toolsActive: Boolean })
defineEmits(['fullscreen', 'reset-zoom', 'toggle-tools'])
</script>

<style scoped>
.nav-btn {
    display:         flex !important;
    align-items:     center !important;
    justify-content: center !important;
    width:           28px !important;
    height:          28px !important;
    padding:         0 !important;
    margin:          0 !important;
    font-size:       16px !important;
    line-height:     1 !important;
    background:      #fff !important;
    color:           #333 !important;
    border:          none !important;
    border-bottom:   1px solid #ccc !important;
    cursor:          pointer !important;
    transition:      background 0.15s, color 0.15s;
    box-sizing:      border-box !important;
}

.nav-btn:hover {
    background: #f4f4f4 !important;
}

.nav-btn--active {
    background: #e8f0fe !important;
    color:      #1a73e8 !important;
}

.border-b-0 {
    border-bottom: none !important;
}
</style>
