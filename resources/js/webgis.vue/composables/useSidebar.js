import { ref } from 'vue'

// Module-level singleton — shared between Navbar and MapView
const activeSidebar = ref(null)

export function useSidebar() {
    function toggleSidebar(name) {
        activeSidebar.value = activeSidebar.value === name ? null : name
    }

    function closeSidebar() {
        activeSidebar.value = null
    }

    return { activeSidebar, toggleSidebar, closeSidebar }
}
