<template>
    <!--
        Positioned to the right of the Leaflet zoom control group.
        left = zoom-left(10) + zoom-width(28) + gap(10) = 48px
        top  = same as Leaflet zoom: navbar(44) + leaflet-margin(10) = 54px
    -->
    <div class="map-search">
        <!-- Input row -->
        <div class="map-search__row">
            <input
                ref="inputEl"
                v-model="query"
                @input="onInput"
                @keydown.enter.prevent="searchFirst"
                @keydown.escape="clear"
                @focus="showResults = results.length > 0"
                @blur="onBlur"
                placeholder="Telusuri alamat atau tempat.."
                class="map-search__input"
                autocomplete="off"
                spellcheck="false"
            />
            <button
                @click="query ? clear() : searchFirst()"
                class="map-search__btn"
                :title="query ? 'Hapus' : 'Cari'"
            >
                <i :class="query ? 'bi bi-x-lg' : 'bi bi-search'"></i>
            </button>
        </div>

        <!-- Dropdown results -->
        <ul v-show="showResults && results.length > 0" class="map-search__results">
            <li
                v-for="r in results"
                :key="r.place_id"
                @mousedown.prevent="selectResult(r)"
                class="map-search__result-item"
            >
                <i class="bi bi-geo-alt-fill map-search__result-icon"></i>
                <span class="map-search__result-text">{{ r.display_name }}</span>
            </li>
        </ul>

        <!-- Loading indicator -->
        <div v-if="loading" class="map-search__loading">
            <i class="bi bi-arrow-clockwise animate-spin"></i>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useLeafletMap } from '../composables/useLeafletMap.js'

const { mapInstance } = useLeafletMap()

const query       = ref('')
const results     = ref([])
const loading     = ref(false)
const showResults = ref(false)
const inputEl     = ref(null)

let debounceTimer = null
let searchMarker  = null

function onInput() {
    clearTimeout(debounceTimer)
    if (!query.value.trim()) {
        results.value = []
        showResults.value = false
        return
    }
    loading.value = true
    debounceTimer = setTimeout(() => fetchResults(query.value.trim()), 400)
}

async function fetchResults(q) {
    try {
        const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=6&addressdetails=1&accept-language=id`
        const res = await fetch(url, { headers: { 'Accept-Language': 'id' } })
        const data = await res.json()
        results.value = data
        showResults.value = data.length > 0
    } catch {
        results.value = []
    } finally {
        loading.value = false
    }
}

function searchFirst() {
    if (!query.value.trim()) return
    if (results.value.length > 0) {
        selectResult(results.value[0])
    } else {
        fetchResults(query.value.trim()).then(() => {
            if (results.value.length > 0) selectResult(results.value[0])
        })
    }
}

function selectResult(r) {
    const map = mapInstance.value
    if (!map) return

    const lat = parseFloat(r.lat)
    const lon = parseFloat(r.lon)
    const L   = window.L

    // Remove previous search marker
    if (searchMarker) {
        map.removeLayer(searchMarker)
        searchMarker = null
    }

    const label = r.display_name.split(',')[0].trim()

    // circleMarker is SVG-based — always stays exactly on geographic coords
    // regardless of zoom level or basemap changes (no CSS positioning involved)
    searchMarker = L.circleMarker([lat, lon], {
        radius:      9,
        fillColor:   '#007fff',
        color:       '#ffffff',
        weight:      2.5,
        opacity:     1,
        fillOpacity: 1,
    })
    .addTo(map)
    .bindPopup(`
        <div style="font-family:'Inter',sans-serif;min-width:160px;max-width:260px">
            <div style="font-weight:600;font-size:13px;color:#1f2937;margin-bottom:4px">${label}</div>
            <div style="font-size:11px;color:#6b7280;line-height:1.4">${r.display_name}</div>
        </div>
    `, { maxWidth: 280 })
    .openPopup()

    map.flyTo([lat, lon], 14, { duration: 1.2 })
    query.value = r.display_name
    showResults.value = false
}

function clear() {
    query.value = ''
    results.value = []
    showResults.value = false
    // Remove search marker when cleared
    if (searchMarker && mapInstance.value) {
        mapInstance.value.removeLayer(searchMarker)
        searchMarker = null
    }
    inputEl.value?.focus()
}

function onBlur() {
    setTimeout(() => { showResults.value = false }, 150)
}
</script>

<style scoped>
.map-search {
    position:   fixed;
    left:       48px;
    top:        54px;
    z-index:    400;
    width:      260px;
    font-family: 'Inter', sans-serif;
}

/* ── Input row ─────────────────────────────────────────────── */
.map-search__row {
    display:       flex;
    align-items:   stretch;
    height:        28px;
    background:    #fff;
    border-radius: 4px;
    overflow:      hidden;
    box-shadow:    0 1px 5px rgba(0,0,0,.4);
}

.map-search__input {
    flex:          1;
    height:        28px;
    padding:       0 8px;
    font-size:     12px;
    font-family:   'Inter', sans-serif;
    color:         #333;
    background:    transparent;
    border:        none;
    outline:       none;
    min-width:     0;
}

.map-search__input::placeholder {
    color: #999;
    font-size: 11px;
}

.map-search__btn {
    display:         flex;
    align-items:     center;
    justify-content: center;
    width:           28px;
    height:          28px;
    flex-shrink:     0;
    font-size:       12px;
    color:           #555;
    background:      transparent;
    border:          none;
    border-left:     1px solid #e0e0e0;
    cursor:          pointer;
    padding:         0;
    transition:      background 0.15s, color 0.15s;
}

.map-search__btn:hover {
    background: #f4f4f4;
    color: #222;
}

/* ── Loading spinner ────────────────────────────────────────── */
.map-search__loading {
    position:   absolute;
    right:      32px;
    top:        7px;
    font-size:  12px;
    color:      #888;
}

/* ── Dropdown ───────────────────────────────────────────────── */
.map-search__results {
    position:      absolute;
    top:           calc(100% + 2px);
    left:          0;
    right:         0;
    background:    #fff;
    border-radius: 4px;
    box-shadow:    0 2px 10px rgba(0,0,0,.2);
    list-style:    none;
    margin:        0;
    padding:       4px 0;
    max-height:    220px;
    overflow-y:    auto;
    z-index:       401;
}

.map-search__result-item {
    display:     flex;
    align-items: flex-start;
    gap:         6px;
    padding:     6px 10px;
    cursor:      pointer;
    font-size:   11px;
    color:       #333;
    line-height: 1.4;
    transition:  background 0.1s;
}

.map-search__result-item:hover {
    background: #f0f7ff;
}

.map-search__result-icon {
    flex-shrink: 0;
    color:       #007fff;
    margin-top:  2px;
    font-size:   11px;
}

.map-search__result-text {
    flex: 1;
    word-break: break-word;
}
</style>
