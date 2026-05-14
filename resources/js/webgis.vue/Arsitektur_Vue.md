# Arsitektur SPA Peta (WebGIS Vue)

Halaman peta interaktif dibangun sebagai **Single Page Application (SPA)** menggunakan Vue 3 dengan Composition API, dimuat di route `/pemetaan` yang di-render oleh Laravel.

---

## Alur Bootstrap

```
Browser buka /pemetaan
    └─ Laravel render peta-v2.blade.php
           ├─ Inject window.MARIMOI_CONFIG (documents, csrf, selectedCategory)
           ├─ Load CDN: leaflet.js + leaflet.extra-markers.min.js  →  window.L
           └─ Load Vite bundle: webgis-app.js
                   └─ createApp(App).use(router).mount('#app')
                          └─ RouterView  →  MapView.vue
                                 └─ onMounted:
                                        initMap('map')
                                        loadMetadata(showToast)
```

---

## Struktur File

```
resources/js/
├── webgis-app.js                        ← Entry point Vite
└── webgis.vue/
    ├── App.vue                          ← Shell (hanya RouterView)
    ├── router/
    │   └── index.js                     ← Hash router, satu route
    ├── composables/
    │   ├── useLeafletMap.js             ← Otak utama: peta + layer + data
    │   └── useToast.js                  ← Notifikasi global
    ├── views/
    │   └── MapView.vue                  ← Satu-satunya smart component
    └── components/
        ├── ControlButtons.vue           ← Tombol sidebar (kanan atas)
        ├── NavButtons.vue               ← Tombol navigasi (kanan bawah)
        ├── ToastContainer.vue           ← Render notifikasi
        ├── GuideModal.vue               ← Modal panduan multi-step
        ├── SidebarLayer.vue             ← Panel layer (hierarki 3 level)
        ├── SidebarBasemap.vue           ← Panel pilih basemap
        ├── SidebarLegend.vue            ← Panel legenda layer aktif
        └── SidebarDownload.vue          ← Panel unduh dokumen
```

---

## Layer 1 — Entry Point & Blade

### `webgis-app.js`

Titik masuk Vite. Membuat Vue app, memasang router, lalu me-mount ke elemen `#app`.

```js
createApp(App).use(router).mount('#app')
```

### `peta-v2.blade.php`

Halaman Laravel biasa. Tidak ikut campur dalam logika SPA — tugasnya hanya:

1. Menyuntikkan data server ke `window.MARIMOI_CONFIG` sebelum Vue berjalan
2. Menyediakan `<div id="app">` sebagai titik mount Vue
3. Me-load Leaflet via CDN agar tersedia sebagai `window.L`

```html
<script>
    window.MARIMOI_CONFIG = {
        documents: @json($documents),
        selectedCategory: @json(session('selectedCategory')),
        csrfToken: '{{ csrf_token() }}'
    };
</script>
```

> **Mengapa Leaflet via CDN?**
> Leaflet memanipulasi DOM secara langsung dan meregister `window.L` sebagai global.
> Jika di-bundle lewat Vite, ada risiko konflik. Dengan CDN, Leaflet pasti siap
> sebelum Vue dijalankan dan dapat diakses oleh composable kapan saja via `window.L`.

---

## Layer 2 — Router & App Shell

### `router/index.js`

Menggunakan **hash history** (`createWebHashHistory`) sehingga URL menjadi `/#/`.
Ini mencegah konflik dengan routing Laravel — Laravel hanya melihat `/pemetaan`,
bukan fragment `#/` setelahnya.

Hanya ada satu route:

```js
{ path: '/', component: MapView }
```

### `App.vue`

Shell paling tipis — hanya berisi `<RouterView />`. Tidak ada state, tidak ada logika.
Seluruh aplikasi berjalan di dalam `MapView`.

---

## Layer 3 — Composables (Otak Aplikasi)

Composable menggunakan pola **module-level singleton**: variabel dideklarasikan
di luar fungsi `use...()`. Akibatnya, seluruh komponen yang memanggil composable
yang sama akan **berbagi state yang sama** — tidak ada instance terpisah.

### `composables/useToast.js`

```
Module scope:
  toasts  = ref([])    ← satu array, dibaca siapapun
  nextId  = 0

useToast() mengembalikan:
  showToast(message, type)  → push item baru, auto-remove setelah 4.5 detik
  removeToast(id)           → hapus dari array
  toasts                    → dibaca ToastContainer untuk render
```

`MapView` memanggil `showToast()` untuk notifikasi hasil operasi.
`ToastContainer` membaca `toasts` untuk menampilkan UI.
Keduanya tidak saling tahu — dihubungkan oleh singleton.

---

### `composables/useLeafletMap.js`

Composable utama. Semua state peta hidup di module scope:

```
Module-level singletons:
  mapInstance       ref(null)      ← instance Leaflet Map
  currentTileLayer  ref(null)      ← tile layer basemap aktif
  activeBasemap     ref('osm')     ← ID basemap yang sedang tampil
  loading           ref(false)     ← status loading global

  categoryTree      reactive({})   ← pohon kategori 3 level (state UI)
  kategoriColors    reactive({})   ← { nama: '#hexcolor' }

  leafletLayerMap   {}             ← { 'root/second/third': L.layerGroup() }  — NON-reactive
  loadedCategories  Set()          ← nama kategori yang sudah di-fetch
  iconMap           {}             ← { nama: 'fa fa-hospital-o' }
  isMarkerMap       {}             ← { nama: true }
```

> `leafletLayerMap` sengaja **tidak reactive** — Leaflet mengelola DOM layer-nya
> sendiri. Jika dibuat reactive, Vue akan mencoba melacak seluruh object Leaflet
> yang sangat besar dan menyebabkan masalah performa.

#### Struktur `categoryTree`

```js
{
  "Proyek Strategis Daerah": {          // Root (Level 1)
    children: {
      "Infrastruktur": {                 // Level 2
        allChecked: false,
        someChecked: false,              // untuk indeterminate checkbox
        children: {
          "Jalan Nasional": {            // Level 3 (leaf)
            checked: false,
            loading: false,
            loaded:  false,
          }
        }
      }
    }
  }
}
```

#### Fungsi yang Diekspos

| Fungsi | Tugas |
|--------|-------|
| `initMap(containerId)` | Buat instance `L.map`, pasang basemap awal OSM |
| `changeBasemap(id)` | Hapus tile lama, tambah tile baru ke peta |
| `loadMetadata(toastFn)` | Fetch `/geojson?metadata_only=true`, bangun `categoryTree` + `leafletLayerMap`, isi color/icon maps |
| `loadCategoryData(third, second, root, toastFn)` | Fetch GeoJSON per-kategori secara chunked (500/request, maks 3000), render fitur ke `leafletLayerMap[key]` |
| `addCategoryToMap(third, second, root)` | `map.addLayer(targetLayer)` + set `node.checked = true` |
| `removeCategoryFromMap(third, second, root)` | `map.removeLayer(targetLayer)` + set `node.checked = false` |
| `resetView()` | Kembali ke koordinat dan zoom default Maluku Utara |
| `toggleFullscreen()` | Masuk/keluar fullscreen browser |
| `destroyMap()` | Bersihkan semua state saat komponen di-unmount |

#### Alur Loading On-Demand

```
User centang checkbox Level 3
  └─ SidebarLayer emit 'toggle-third'
        └─ MapView.onThirdToggle(third, second, root, checked=true)
               ├─ node.loaded === false?
               │     └─ loadCategoryData()
               │            ├─ node.loading = true  (spinner muncul di sidebar)
               │            ├─ fetch /geojson?kategori[]=Name&limit=500&offset=0
               │            ├─ render features ke L.layerGroup (chunked)
               │            ├─ node.loaded = true
               │            └─ node.loading = false
               └─ addCategoryToMap()
                      ├─ map.addLayer(targetLayer)   ← layer muncul di peta
                      ├─ node.checked = true
                      └─ _updateSecondState()        ← update allChecked/someChecked L2
                             └─ activeLegendItems recomputed otomatis
```

#### Rendering Fitur di Peta

Saat `loadCategoryData` memproses setiap feature GeoJSON:

- **Point + `is_marker: true`** → `L.ExtraMarkers.icon()` (pin berwarna dengan ikon FA)
- **Point biasa** → `L.circleMarker()` dengan warna kategori
- **LineString / MultiLineString** → `L.geoJSON` dengan style garis tebal, tanpa fill
- **Polygon / MultiPolygon** → `L.geoJSON` dengan stroke + fill, fillOpacity 0.4

Popup terikat via `bindPopup()` — menampilkan nama, kategori, dan atribut dari `feature.properties`.

---

## Layer 4 — MapView (Koordinator)

### `views/MapView.vue`

Satu-satunya **smart component** dalam aplikasi. Bertanggung jawab:

1. Memanggil kedua composable dan mengekspos hasilnya ke child components
2. Menyimpan satu state lokal: `activeSidebar` — sidebar mana yang sedang terbuka
3. Menghubungkan event dari child component ke fungsi composable
4. Menghitung `activeLegendItems` untuk SidebarLegend

```
State lokal:
  activeSidebar  ref(null)   ← 'layer' | 'basemap' | 'legend' | 'download' | null
  documents      ref([])     ← dari window.MARIMOI_CONFIG

Computed:
  activeLegendItems  ← scan categoryTree, kumpulkan node checked=true
                        baca warnanya dari kategoriColors
                        hasil jadi prop ke SidebarLegend
```

**Keyboard shortcuts** didaftarkan di `onMounted`:

| Shortcut | Aksi |
|----------|------|
| `Esc` | Tutup sidebar yang terbuka |
| `Ctrl+L` | Toggle sidebar Layer |
| `Ctrl+B` | Toggle sidebar Basemap |
| `Ctrl+H` | Buka Guide Modal |

**Lifecycle:**
- `onMounted` → `initMap('map')` + `loadMetadata(showToast)`
- `onUnmounted` → hapus event listener keyboard + `destroyMap()`

---

## Layer 5 — Komponen UI (Dumb Components)

Komponen-komponen ini tidak memiliki logika bisnis — hanya menerima props dan melempar events ke parent.

### `SidebarLayer.vue`

Menerima `categoryTree` (reactive object dari composable) sebagai prop read-only.
Menampilkan hierarki 3 level yang bisa di-expand/collapse:

```
Root  (header tanpa checkbox)           ← klik area header untuk expand
  └─ Level 2  (checkbox + chevron)      ← centang untuk load semua anak sekaligus
        └─ Level 3  (checkbox + label)  ← centang untuk load satu kategori ini
```

State expand/collapse disimpan **lokal** di component:
```js
expandedRoots   = reactive({})   // { rootName: true/false }
expandedSeconds = reactive({})   // { 'root/second': true/false }
```

State `checked`, `loading`, `loaded` dibaca langsung dari prop `categoryTree`.

Indeterminate state pada checkbox L2 di-set lewat **`ref` callback** karena
Vue tidak bisa mem-bind properti DOM `.indeterminate` via `v-bind`:
```vue
:ref="el => setIndeterminate(el, secondNode.someChecked)"
```

**Events yang diemit:**
- `toggle-third(thirdName, secondName, rootName, checked)`
- `toggle-second(secondName, rootName, checked)`
- `close`

### `SidebarBasemap.vue`

Grid 2 kolom, setiap item menampilkan preview image dari:
```
/frontend/img/map-preview/{id}-min.png
```
Item aktif diberi border biru + glow shadow. Jika gambar gagal dimuat,
fallback icon ditampilkan via `onImgError` (menggunakan `style.display`
langsung, bukan class Tailwind, untuk menghindari konflik `hidden` vs `flex`).

**Events:** `change(id)`, `close`

### `SidebarLegend.vue`

Daftar sederhana. Menerima `legendItems: Array<{name, color}>` yang sudah
dihitung oleh MapView. Tidak punya logika sendiri — murni presentasi.

**Events:** `close`

### `SidebarDownload.vue`

Daftar link download dokumen. Data berasal dari `window.MARIMOI_CONFIG.documents`
yang diteruskan MapView lewat prop. Menggunakan atribut `download` pada tag `<a>`.

**Events:** `close`

### `ControlButtons.vue`

Grup tombol kanan atas (Bantuan, Legenda, Basemap, Layer). Data tombol hardcoded
sebagai array sehingga mudah ditambah. Tombol yang sidebaarnya sedang aktif
mendapat highlight biru berdasarkan prop `activeSidebar`.

**Events:** `toggle-sidebar(name)`, `show-guide`

### `NavButtons.vue`

Grup tombol kanan bawah (Download, Fullscreen, Home). Murni melempar events
tanpa state apapun.

**Events:** `toggle-download`, `fullscreen`, `reset-zoom`

### `ToastContainer.vue`

Memanggil `useToast()` **secara mandiri** (tidak lewat prop dari MapView)
karena memanfaatkan singleton composable. Render notifikasi dengan
`<TransitionGroup>` untuk animasi slide-in dari kanan. Auto-dismiss
diurus oleh composable (4.5 detik).

### `GuideModal.vue`

Modal panduan multi-step. Menyimpan `currentStep` sebagai state lokal.
Reset ke step 1 setiap kali prop `show` berubah jadi `true` via `watch`.
Step terakhir: tombol "Next" berubah menjadi "Selesai" dan menutup modal.

---

## Alur Data Keseluruhan

```
window.MARIMOI_CONFIG           ← dari blade (server-side)
        │
        ▼
   MapView.vue                  ← baca documents
        │
        ├──────────────────────────────────────────────────────────┐
        │                                                          │
        ▼                                                          ▼
 useLeafletMap.js                                          useToast.js
  (singleton state)                                        (singleton)
        │                                                          │
        ├─ categoryTree ──────► SidebarLayer (prop)               │
        ├─ activeBasemap ─────► SidebarBasemap (prop)             │
        ├─ aktiveLegendItems ─► SidebarLegend (prop)              │
        ├─ loading ───────────► SidebarLayer (prop)               │
        └─ basemaps ──────────► SidebarBasemap (prop)             │
                                                                   ▼
                                                           ToastContainer
                                                           (akses langsung
                                                            via singleton)
```

---

## Keputusan Arsitektur

| Keputusan | Alasan |
|-----------|--------|
| **Singleton composable** (bukan Pinia/Vuex) | State peta perlu dibagi antar komponen tanpa overhead store management. Composable cukup karena tidak ada async middleware atau devtools requirement |
| **Leaflet via CDN** | Leaflet membutuhkan `window.L` sebagai global dan memanipulasi DOM langsung. Bundle via Vite berisiko konflik. CDN memastikan Leaflet siap sebelum Vue berjalan |
| **Hash router** | Mencegah konflik dengan routing Laravel. `/pemetaan` ditangani Laravel, `/#/` ditangani Vue |
| **On-demand loading** | Data spasial bisa sangat besar. User hanya load kategori yang dibutuhkan. `loadedCategories` Set mencegah fetch ulang |
| **`leafletLayerMap` non-reactive** | Leaflet layer adalah object kompleks yang dikelola Leaflet sendiri. Membuat reactive akan menyebabkan Vue melacak seluruh internal Leaflet dan merusak performa |
| **Dumb components** | Semua state ada di MapView + composable. Component hanya render — mudah diganti, ditest, dan di-debug secara independen |
| **`prefix: ''` pada ExtraMarkers** | `cat.icon` disimpan sebagai class lengkap (`"fa fa-hospital-o"`). Prefix kosong menghindari duplikasi class `"fa fa fa-hospital-o"` |
