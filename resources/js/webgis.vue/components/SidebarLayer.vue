<template>
    <div
        :class="[
            'absolute top-0 right-0 w-[280px] md:w-[300px] h-[calc(100vh-44px)] bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 flex flex-col overflow-hidden',
            visible ? '' : 'hidden',
        ]"
    >
        <!-- Header -->
        <div class="flex-shrink-0 flex justify-between items-center mb-2 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
            <h6 class="text-white mb-0 text-sm font-semibold">Layer</h6>
            <button @click="$emit('close')" class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                <i class="bi bi-x-lg text-white"></i>
            </button>
        </div>

        <!-- Search bar -->
        <div v-if="!loading && hasCategories" class="flex-shrink-0 relative mb-2">
            <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            <input
                v-model="searchQuery"
                type="search"
                placeholder="Cari layer..."
                class="w-full pl-7 pr-7 py-1.5 text-xs border border-gray-200 rounded-md bg-white outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-100"
            />
            <button
                v-if="searchQuery"
                @click="searchQuery = ''"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
                <i class="bi bi-x text-xs"></i>
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex-1 flex flex-col items-center justify-center text-gray-400">
            <i class="bi bi-arrow-clockwise text-2xl block mb-2 animate-spin"></i>
            Memuat kategori...
        </div>

        <!-- Empty -->
        <div v-else-if="!hasCategories" class="flex-1 flex flex-col items-center justify-center text-gray-400">
            <i class="bi bi-layers text-2xl block mb-2"></i>
            Belum ada layer
        </div>

        <!-- No search results -->
        <div v-else-if="searchQuery && !hasResults" class="flex-1 flex flex-col items-center justify-center text-gray-400 gap-2">
            <i class="bi bi-search text-2xl"></i>
            <p class="text-xs">Tidak ada layer yang cocok</p>
            <p class="text-[11px] text-gray-400">&ldquo;{{ searchQuery }}&rdquo;</p>
        </div>

        <!-- 3-level hierarchy -->
        <div v-else class="flex-1 min-h-0 overflow-y-auto text-sm">
            <template v-for="(rootNode, rootName) in categoryTree" :key="rootName">
                <!-- Skip root if none of its children match -->
                <div v-if="rootVisible(rootName, rootNode)" class="mb-3">

                    <!-- Root header -->
                    <div
                        class="flex items-center justify-between px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200"
                        @click="toggleRoot(rootName)"
                    >
                        <div class="flex items-center">
                            <i
                                class="bi mr-2 text-gray-600 text-xs transition-transform duration-200"
                                :class="isRootExpanded(rootName) ? 'bi-chevron-down' : 'bi-chevron-right'"
                            ></i>
                            <span class="font-semibold text-gray-900 text-sm" v-html="highlight(rootName)"></span>
                            <span class="ml-2 px-2 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">
                                {{ Object.keys(rootNode.children).length }}
                            </span>
                        </div>
                    </div>

                    <!-- Second level container -->
                    <div
                        v-show="isRootExpanded(rootName)"
                        class="border-l border-r border-b border-gray-300 rounded-b-lg bg-gray-50"
                    >
                        <template v-for="(secondNode, secondName) in rootNode.children" :key="secondName">
                            <div v-if="secondVisible(rootName, secondName, secondNode)" class="px-2 py-1">

                                <!-- Second level header -->
                                <div class="flex items-center px-3 py-2 border border-gray-200 rounded-md hover:bg-gray-100 transition-colors duration-200 ml-3 mb-1">
                                    <i
                                        class="bi mr-2 text-gray-500 text-xs cursor-pointer transition-transform duration-200"
                                        :class="isSecondExpanded(rootName, secondName) ? 'bi-chevron-down' : 'bi-chevron-right'"
                                        @click="toggleSecond(rootName, secondName)"
                                    ></i>
                                    <input
                                        type="checkbox"
                                        class="mr-2 h-4 w-4 cursor-pointer accent-blue-500"
                                        :checked="secondNode.allChecked"
                                        @click.stop
                                        @change="onSecondChange(rootName, secondName, $event.target.checked)"
                                        :ref="el => setIndeterminate(el, secondNode.someChecked)"
                                    />
                                    <label
                                        class="text-sm text-gray-700 cursor-pointer flex-1 select-none"
                                        @click="toggleSecond(rootName, secondName)"
                                        v-html="highlight(secondName)"
                                    ></label>
                                    <span class="ml-2 px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full flex-shrink-0">
                                        {{ Object.keys(secondNode.children).length }}
                                    </span>
                                </div>

                                <!-- Third level -->
                                <div
                                    v-show="isSecondExpanded(rootName, secondName)"
                                    class="pl-4 ml-5 border-l border-gray-200 mt-1"
                                >
                                    <template v-for="(thirdNode, thirdName) in secondNode.children" :key="thirdName">
                                        <div
                                            v-if="thirdVisible(thirdName)"
                                            class="flex items-center py-2 hover:bg-gray-100 transition-colors rounded px-2 mt-1"
                                        >
                                            <input
                                                type="checkbox"
                                                class="mr-2 h-4 w-4 cursor-pointer flex-shrink-0 accent-blue-400"
                                                :checked="thirdNode.checked"
                                                :disabled="thirdNode.loading"
                                                @change="onThirdChange(rootName, secondName, thirdName, $event.target.checked)"
                                            />
                                            <i
                                                v-if="thirdNode.loading"
                                                class="bi bi-arrow-clockwise animate-spin mr-1 text-blue-500 flex-shrink-0 text-xs"
                                            ></i>
                                            <label
                                                class="text-xs text-gray-600 cursor-pointer flex-1 leading-tight select-none"
                                                :class="thirdNode.loading ? 'opacity-60 animate-pulse' : ''"
                                                v-html="highlight(thirdName)"
                                            ></label>
                                        </div>
                                    </template>
                                </div>

                            </div>
                        </template>
                    </div>

                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'

const props = defineProps({
    visible: Boolean,
    categoryTree: Object,
    loading: Boolean,
})

const emit = defineEmits(['close', 'toggle-third', 'toggle-second'])

// ── Search ──────────────────────────────────────────────────
const searchQuery = ref('')

const q = computed(() => searchQuery.value.trim().toLowerCase())

function matches(text) {
    return !q.value || text.toLowerCase().includes(q.value)
}

function thirdVisible(thirdName) {
    return matches(thirdName)
}

function secondVisible(rootName, secondName, secondNode) {
    if (matches(rootName) || matches(secondName)) return true
    return Object.keys(secondNode.children).some(t => matches(t))
}

function rootVisible(rootName, rootNode) {
    if (matches(rootName)) return true
    return Object.values(rootNode.children).some((secondNode, i) => {
        const secondName = Object.keys(rootNode.children)[i]
        return secondVisible(rootName, secondName, secondNode)
    })
}

// When there's a query, auto-expand matching roots/seconds
function isRootExpanded(rootName) {
    return q.value ? true : !!expandedRoots[rootName]
}

function isSecondExpanded(rootName, secondName) {
    return q.value ? true : !!expandedSeconds[`${rootName}/${secondName}`]
}

const hasResults = computed(() => {
    if (!q.value) return true
    return Object.entries(props.categoryTree).some(([rootName, rootNode]) =>
        rootVisible(rootName, rootNode)
    )
})

// Highlight matching text
function highlight(text) {
    if (!q.value) return text
    const idx = text.toLowerCase().indexOf(q.value)
    if (idx === -1) return text
    return (
        text.slice(0, idx) +
        `<mark style="background:#bfdbfe;border-radius:2px;padding:0">${text.slice(idx, idx + q.value.length)}</mark>` +
        text.slice(idx + q.value.length)
    )
}

// ── Expand state ────────────────────────────────────────────
const expandedRoots   = reactive({})
const expandedSeconds = reactive({})

const hasCategories = computed(() => Object.keys(props.categoryTree).length > 0)

function toggleRoot(rootName) {
    expandedRoots[rootName] = !expandedRoots[rootName]
}

function toggleSecond(rootName, secondName) {
    const key = `${rootName}/${secondName}`
    expandedSeconds[key] = !expandedSeconds[key]
}

// ── Events ──────────────────────────────────────────────────
function onThirdChange(rootName, secondName, thirdName, checked) {
    emit('toggle-third', thirdName, secondName, rootName, checked)
}

function onSecondChange(rootName, secondName, checked) {
    emit('toggle-second', secondName, rootName, checked)
}

function setIndeterminate(el, value) {
    if (el) el.indeterminate = value
}
</script>
