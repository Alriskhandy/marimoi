<template>
    <nav class="peta-navbar">
        <!--
            3-column grid:
            [Logo & Brand]  [Nav Links (centered)]  [Control Buttons]
        -->
        <div class="peta-navbar__inner">

            <!-- Logo & Brand -->
            <div class="peta-navbar__brand-col">
                <a href="/" class="peta-navbar__brand">
                    <img :src="logoUrl" alt="MARIMOI" class="peta-navbar__logo" />
                    <span class="peta-navbar__brand-name">MARIMOI</span>
                </a>
            </div>

            <!-- Nav Links -->
            <nav class="peta-navbar__links-col" aria-label="Main navigation">
                <ul class="peta-navbar__links">
                    <li v-for="link in navLinks" :key="link.href">
                        <a
                            :href="link.href"
                            :class="['peta-navbar__link', link.active ? 'peta-navbar__link--active' : '']"
                        >{{ link.label }}</a>
                    </li>
                </ul>
            </nav>

            <!-- Control Buttons -->
            <div class="peta-navbar__controls">
                <button
                    v-for="btn in sidebarBtns"
                    :key="btn.id"
                    @click="handleBtn(btn)"
                    :title="btn.label"
                    :class="['peta-navbar__ctrl-btn', activeSidebar === btn.sidebar ? 'peta-navbar__ctrl-btn--active' : '']"
                >
                    <i :class="['bi', btn.icon]"></i>
                </button>

                <!-- Mobile hamburger — shown only on small screens -->
                <button class="peta-navbar__hamburger" @click="mobileOpen = !mobileOpen" aria-label="Menu">
                    <i :class="mobileOpen ? 'bi bi-x-lg' : 'bi bi-list'"></i>
                </button>
            </div>
        </div>

        <!-- Mobile dropdown -->
        <div v-show="mobileOpen" class="peta-navbar__mobile">
            <a
                v-for="link in navLinks"
                :key="link.href + '-m'"
                :href="link.href"
                :class="['peta-navbar__mobile-link', link.active ? 'peta-navbar__mobile-link--active' : '']"
            >{{ link.label }}</a>
        </div>
    </nav>
</template>

<script setup>
import { ref } from 'vue'
import { useSidebar } from '../composables/useSidebar.js'

const emit = defineEmits(['show-guide'])

const { activeSidebar, toggleSidebar } = useSidebar()
const mobileOpen = ref(false)
const logoUrl = window.MARIMOI_CONFIG?.logoUrl ?? '/frontend/img/logo/logo-white.png'

const path = window.location.pathname

const navLinks = [
    { href: '/',                    label: 'Beranda',          active: path === '/'                  },
    { href: '/profil-reformer',     label: 'Profil Reformer',  active: path === '/profil-reformer'   },
    { href: '/pemetaan',            label: 'Peta', active: path === '/pemetaan'           },
    { href: '/prioritas-daerah',    label: 'Prioritas Daerah', active: path === '/prioritas-daerah'  },
    { href: '/dokumen-publikasi',   label: 'Publikasi',        active: path === '/dokumen-publikasi' },
    { href: '/aspirasi-masyarakat', label: 'Aspirasi',         active: path === '/aspirasi-masyarakat'},
]

const sidebarBtns = [
    { id: 'layer',   icon: 'bi-layers-fill',        label: 'Layer Peta',   sidebar: 'layer'  },
    { id: 'basemap', icon: 'bi-grid-fill',          label: 'Basemap Peta', sidebar: 'basemap'},
    { id: 'legend',  icon: 'bi-list-ul',           label: 'Legenda Peta', sidebar: 'legend' },
    { id: 'help',    icon: 'bi-info-circle-fill', label: 'Bantuan',      action: 'guide'   },
]

function handleBtn(btn) {
    if (btn.action === 'guide') {
        emit('show-guide')
    } else {
        toggleSidebar(btn.sidebar)
    }
}
</script>

<style scoped>
/* ── Base navbar ─────────────────────────────────────────────── */
.peta-navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 44px;
    z-index: 1000;
    background: rgba(10, 15, 30, 0.97);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
    box-shadow: 0 1px 12px rgba(0, 0, 0, 0.3);
}

/* ── 3-column grid row ───────────────────────────────────────── */
.peta-navbar__inner {
    height: 44px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: stretch;
}

/* ── ① Brand column ─────────────────────────────────────────── */
.peta-navbar__brand-col {
    display: flex;
    align-items: center;
    padding: 0 1rem;
}

.peta-navbar__brand {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    text-decoration: none;
}

.peta-navbar__logo {
    height: 24px;
    width: auto;
    flex-shrink: 0;
}

.peta-navbar__brand-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.04em;
    white-space: nowrap;
    font-family: 'Poppins', sans-serif;
}

/* ── ② Nav links column ─────────────────────────────────────── */
.peta-navbar__links-col {
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 0 0.5rem;
}

.peta-navbar__links {
    display: flex;
    align-items: center;
    gap: 0;
    list-style: none;
    margin: 0;
    padding: 0;
}

.peta-navbar__link {
    display: inline-block;
    padding: 0 1.2rem;
    height: 44px;
    line-height: 44px;
    font-size: 14px;
    font-weight: 400;
    white-space: nowrap;
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    transition: color 0.15s, background 0.15s;
    font-family: 'Inter', sans-serif;
}

.peta-navbar__link:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.06);
}

.peta-navbar__link--active {
    color: #fff;
    border-bottom: 2px solid #007fff;
}

/* ── ③ Controls column ──────────────────────────────────────── */
.peta-navbar__controls {
    display: flex;
    align-items: stretch;
}

.peta-navbar__ctrl-btn {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: background 0.15s;
    flex-shrink: 0;
}

.peta-navbar__ctrl-btn:first-child {
    border-left: none;
}

/* 25×25 icon box centered inside the 44px button */
.peta-navbar__ctrl-btn i {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    font-size: 20px;
    color: rgba(255, 255, 255, 0.65);
    transition: background 0.15s, color 0.15s;
}

.peta-navbar__ctrl-btn:hover i {
    color: #fff;
    background: rgba(255, 255, 255, 0.1);
}

.peta-navbar__ctrl-btn--active i {
    color: #60a5fa;
    background: rgba(0, 127, 255, 0.18);
}

/* ── Hamburger (mobile only) ────────────────────────────────── */
.peta-navbar__hamburger {
    display: none;
    width: 44px;
    height: 44px;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: transparent;
    border: none;
    border-left: 1px solid rgba(255, 255, 255, 0.06);
    cursor: pointer;
    font-size: 1.1rem;
    flex-shrink: 0;
}

/* ── Mobile dropdown ────────────────────────────────────────── */
.peta-navbar__mobile {
    background: rgba(10, 15, 30, 0.99);
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    padding: 0.4rem 0.75rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.peta-navbar__mobile-link {
    padding: 0.55rem 0.75rem;
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.65);
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.15s, color 0.15s;
    font-family: 'Inter', sans-serif;
}

.peta-navbar__mobile-link:hover,
.peta-navbar__mobile-link--active {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
}

/* ── Responsive ─────────────────────────────────────────────── */
@media (max-width: 900px) {
    .peta-navbar__links-col {
        display: none;
    }
    .peta-navbar__hamburger {
        display: flex;
    }
}
</style>
