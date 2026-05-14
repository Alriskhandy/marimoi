<template>
    <div
        :class="[
            'absolute top-0 right-0 w-[280px] md:w-[300px] h-[calc(100vh-44px)] bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 flex flex-col overflow-hidden',
            visible ? '' : 'hidden',
        ]"
    >
        <!-- Header -->
        <div class="flex-shrink-0 flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
            <h6 class="text-white mb-0 text-sm font-semibold">Layer</h6>
            <button @click="$emit('close')" class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                <i class="bi bi-x-lg text-white"></i>
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

        <!-- 3-level hierarchy -->
        <div v-else class="flex-1 min-h-0 overflow-y-auto text-sm">
            <!-- Root level -->
            <div
                v-for="(rootNode, rootName) in categoryTree"
                :key="rootName"
                class="mb-3"
            >
                <!-- Root header — no checkbox, click to expand -->
                <div
                    class="flex items-center justify-between px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200"
                    @click="toggleRoot(rootName)"
                >
                    <div class="flex items-center">
                        <i
                            class="bi mr-2 text-gray-600 text-xs transition-transform duration-200"
                            :class="expandedRoots[rootName] ? 'bi-chevron-down' : 'bi-chevron-right'"
                        ></i>
                        <span class="font-semibold text-gray-900 text-sm">{{ rootName }}</span>
                        <span class="ml-2 px-2 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">
                            {{ Object.keys(rootNode.children).length }}
                        </span>
                    </div>
                </div>

                <!-- Second level container -->
                <div
                    v-show="expandedRoots[rootName]"
                    class="border-l border-r border-b border-gray-300 rounded-b-lg bg-gray-50"
                >
                    <div
                        v-for="(secondNode, secondName) in rootNode.children"
                        :key="secondName"
                        class="px-2 py-1"
                    >
                        <!-- Second level header — has checkbox + expand toggle -->
                        <div
                            class="flex items-center px-3 py-2 border border-gray-200 rounded-md hover:bg-gray-100 transition-colors duration-200 ml-3 mb-1"
                        >
                            <i
                                class="bi mr-2 text-gray-500 text-xs cursor-pointer transition-transform duration-200"
                                :class="expandedSeconds[`${rootName}/${secondName}`] ? 'bi-chevron-down' : 'bi-chevron-right'"
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
                            >{{ secondName }}</label>
                            <span class="ml-2 px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full flex-shrink-0">
                                {{ Object.keys(secondNode.children).length }}
                            </span>
                        </div>

                        <!-- Third level container -->
                        <div
                            v-show="expandedSeconds[`${rootName}/${secondName}`]"
                            class="pl-4 ml-5 border-l border-gray-200 mt-1"
                        >
                            <div
                                v-for="(thirdNode, thirdName) in secondNode.children"
                                :key="thirdName"
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
                                >{{ thirdName }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive } from 'vue'

const props = defineProps({
    visible: Boolean,
    categoryTree: Object,
    loading: Boolean,
})

const emit = defineEmits(['close', 'toggle-third', 'toggle-second'])

const expandedRoots   = reactive({}) // { rootName: bool }
const expandedSeconds = reactive({}) // { 'root/second': bool }

const hasCategories = computed(() => Object.keys(props.categoryTree).length > 0)

function toggleRoot(rootName) {
    expandedRoots[rootName] = !expandedRoots[rootName]
}

function toggleSecond(rootName, secondName) {
    const key = `${rootName}/${secondName}`
    expandedSeconds[key] = !expandedSeconds[key]
}

function onThirdChange(rootName, secondName, thirdName, checked) {
    emit('toggle-third', thirdName, secondName, rootName, checked)
}

function onSecondChange(rootName, secondName, checked) {
    emit('toggle-second', secondName, rootName, checked)
}

// Vue template ref callback to set indeterminate (not bindable via v-bind)
function setIndeterminate(el, value) {
    if (el) el.indeterminate = value
}
</script>