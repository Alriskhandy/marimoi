import { createRouter, createWebHashHistory } from 'vue-router'
import MapView from '../views/MapView.vue'

export default createRouter({
    history: createWebHashHistory(),
    routes: [
        { path: '/', component: MapView }
    ]
})