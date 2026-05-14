import { ref } from 'vue'

// Module-level singleton
const selectedFeature = ref(null)
// shape: { name, kategori, imageUrl, properties, geometry }

const bottomBarOpen = ref(false)

export function useFeatureDetail() {
    function setFeature(feature) {
        const p = feature.properties || {}
        const name     = p.NAMA_OBJEK || p.NAMOBJ || p.nama || p.name || 'Detail'
        const kategori = p.kategori || ''
        const imageUrl = p.gambar   || null

        selectedFeature.value = {
            name,
            kategori,
            imageUrl,
            properties: p,
            geometry:   feature.geometry,
        }
        bottomBarOpen.value = true
    }

    function clearFeature() {
        selectedFeature.value = null
    }

    return { selectedFeature, bottomBarOpen, setFeature, clearFeature }
}
