# Plan Implementasi: Filter Terstruktur pada Peta Tematik Publik

## Status Implementasi

- **Tahap 1–6 — selesai.** Migration, model, backend share link, panel filter (Peta Tematik + peta mini beranda), dan seluruh test Tahap 6 lulus (4 test baru, 121 assertion di run pertama; regresi `SharedMapTest`/`HomepageTest`/`BackendTematikOnlyTest`/`DataSpatialGeojsonTest` — total 32 test lulus tanpa gangguan).
- Semua path file dan mekanisme sudah diverifikasi ulang tepat sebelum disunting (bukan hanya mengandalkan audit awal) — beberapa nomor baris di `map.js` bergeser dari yang tercatat di audit awal karena file 3000+ baris ini sensitif terhadap edit sebelumnya; setiap edit dicek ulang posisinya sebelum dieksekusi.
- **Revisi UI pasca-implementasi:** panel dan tombol toggle filter yang semula terpisah (`#sidebar-filter`, `#btn-toggle-sidebar-filter`) **dihapus**. Ketiga `<select>` filter beserta tombol reset dan `#filter-count` dipindahkan ke dalam `#sidebar-layer`, tepat di atas `#layer-list`, dipisahkan garis pembatas. Tujuannya: filter bisa diisi dulu tanpa mengaktifkan layer apa pun, baru layer yang relevan dicentang — karena `refreshFilterPanel()` sudah dipanggil setiap kali sebuah layer baru selesai dimuat (Tahap 4.3), layer yang baru dicentang langsung mematuhi filter yang sudah diisi. Wiring JS disesuaikan: `sidebarElements`/`toggleButtons`/close-array tidak lagi punya entri `filter`; `toggleButtons.layer` sekarang juga memicu `refreshFilterPanel()` saat sidebar Layer dibuka (pengganti `toggleButtons.filter` yang dihapus). Test `FrontendPagesTest` disesuaikan (`assertSee('id="sidebar-filter"')`/`btn-toggle-sidebar-filter` diganti `assertSee('id="sidebar-layer"')`, `filter-kabupaten` tetap). 24 test relevan lulus ulang setelah revisi ini.
- **Revisi UI kedua:** blok filter dibungkus elemen native `<details id="filter-details">`/`<summary>` (tertutup secara default) supaya sidebar Layer tetap ringkas — tidak menambah JS toggle baru, memakai disclosure widget bawaan browser. Ditambah `#filter-summary-count` di baris `<summary>` yang menampilkan jumlah dimensi filter aktif (mis. "2 aktif") lewat `refreshFilterPanel()` di `map.js`, supaya status filter tetap terlihat walau panel filter sedang tertutup. Field HTML (`filter-kabupaten`, dst.) tidak berubah id/struktur internalnya, jadi test yang sudah ada tetap valid tanpa perubahan.
- **Revisi UI ketiga:** `<details>` diganti tombol corong (`#btn-toggle-filter-panel`) diletakkan di samping kolom pencarian layer (bukan lagi baris tersendiri di atas daftar layer) — `#filter-panel` (berisi 3 `<select>`, tombol reset, `#filter-count`) tersembunyi (`hidden`) secara default, dibuka lewat listener baru di `map.js` yang toggle class `hidden` + `aria-expanded`, independen dari toggle sidebar Layer itu sendiri. `#filter-summary-count` sekarang jadi badge bulat kecil di pojok tombol (angka polos, bukan teks "N aktif") yang hanya muncul saat ada filter aktif. Sekaligus **perbaikan scroll/padding**: `#sidebar-layer` diubah jadi flex-column (`flex flex-col`), header/kolom pencarian/panel filter diberi `shrink-0`, dan `#layer-list` diganti dari `max-h-[calc(100vh-250px)]` (angka statis yang tidak memperhitungkan tinggi elemen di atasnya) menjadi `flex-1 min-h-0 overflow-y-auto` — daftar layer kini otomatis mengisi sisa ruang vertikal yang benar-benar tersedia dan tetap bisa di-scroll penuh, baik saat panel filter terbuka maupun tertutup. Tidak ada perubahan pada id field filter maupun logika `refreshFilterPanel()`/`getActiveFilterValues()`, jadi test yang ada tetap valid tanpa perubahan (24 test lulus ulang).
- **Revisi perilaku: filter kini men-drive layer, bukan sebaliknya.** Sebelumnya `refreshFilterPanel()` hanya menyaring feature di layer yang **sudah dicentang** (`map.hasLayer(leafGroup)`) — memilih filter tanpa layer aktif tidak menampilkan apa pun. Ini membalik Keputusan #2/#3 versi awal (yang sengaja menghindari pemuatan massal demi mencegah memory exhausted, commit `dc839c4`), atas permintaan eksplisit: filter sekarang harus menampilkan layer yang cocok, bukan menunggu layer aktif secara manual.
  - `thirdCheckbox` (level 3, unit muat-data terkecil) di `updateLayerList()` diubah agar handler `change`-nya disimpan sebagai `thirdCheckbox._applyChange` (pola yang sama dengan `secondCheckbox._applyChange` yang sudah ada), supaya bisa dipanggil terprogram dan **di-`await`** — `dispatchEvent()` tidak bisa ditunggu untuk handler `async`.
  - Fungsi baru `activateAllCategoriesForFilter()`: iterasi semua checkbox level 3 di `#layer-list`, mencentang + `await`-memuat (lewat `_applyChange`) yang belum aktif, **berurutan** (bukan paralel — pola yang sama dengan checkbox induk "centang semua sub kategori" yang sudah ada dan sudah teruji, sehingga tidak ada mekanisme pemuatan baru, hanya dipicu lebih luas).
  - Fungsi baru `applyStructuredFilters()`: dipanggil setiap kali salah satu dari 3 `<select>` filter berubah. Bila **ada** dimensi filter aktif → kunci sementara kontrol filter (`disabled`), tampilkan "Memuat seluruh layer untuk filter..." di `#filter-count`, panggil `activateAllCategoriesForFilter()`, baru `refreshFilterPanel()`. Bila **tidak ada** filter aktif (semua "Semua ...") → langsung `refreshFilterPanel()` saja tanpa memuat apa pun (idle, tidak ada regresi perilaku lama untuk kondisi filter kosong).
  - Tombol Reset Filter **tidak** memicu `applyStructuredFilters()` — hanya mengosongkan nilai lalu `refreshFilterPanel()`. Layer yang sudah terlanjur dimuat/dicentang akibat filter sebelumnya **tetap** tercentang setelah reset (sengaja — mengembalikan hanya nilai filter, bukan mencopot layer yang sudah dimuat user).
  - `applySharedMapState()` (buka share link) memakai `applyStructuredFilters()` (bukan `refreshFilterPanel()` langsung) supaya link yang membawa `filters` ikut memuat seluruh layer yang cocok, bukan cuma menyaring `state.layers` yang eksplisit tersimpan.
  - **Konsekuensi yang diterima secara sadar** (bagian dari permintaan revisi ini, bukan bug): memilih filter apa pun (termasuk satu dimensi saja, mis. hanya Tahun) akan memuat **seluruh** kategori/layer yang ada di pohon layer, bukan hanya yang relevan — karena tidak ada cara mengetahui kategori mana yang punya data cocok tanpa memuatnya lebih dulu (Keputusan #3 versi awal: opsi filter digali dari data yang sudah dimuat, bukan endpoint baru). Untuk peta dengan banyak kategori, ini bisa berarti pemuatan data yang signifikan (walau tetap berurutan & pakai caching IndexedDB yang sudah ada, bukan pemuatan paralel yang bisa memicu memory exhausted). Checkbox di pohon layer akan terlihat **tercentang semua** setelah filter pertama kali dipakai — ini perilaku yang disengaja (filter memutuskan tampilan, bukan checkbox manual), bukan bug visual.
  - Test PHPUnit tidak berubah untuk revisi ini — seluruh logika baru murni client-side (DOM/Leaflet, `async`/`await` berurutan), di luar jangkauan PHPUnit, konsisten dengan keterbatasan yang sudah dicatat di Tahap 6/"Belum Diverifikasi" sejak awal.
- **Perbaikan bug ganda (laporan user setelah revisi di atas):** (1) opsi dropdown filter (mis. "Kota Ternate") ternyata **masih tidak muncul** sampai ada layer aktif — karena `refreshFilterPanel()` cuma menggali opsi dari feature yang sudah dirender, jadi selama belum ada layer dimuat, dropdown-nya kosong (chicken-and-egg: user tidak bisa memilih nilai yang belum jadi opsi). (2) memilih satu nilai filter (mis. satu Kabupaten/Kota) memuat **seluruh** kategori sekaligus (`activateAllCategoriesForFilter()` versi sebelumnya), bukan cuma yang relevan — berat dan tidak presisi. Kedua bug diperbaiki dengan **menambah 2 endpoint backend baru** (membalik Keputusan #3 versi awal yang eksplisit menolak endpoint baru untuk filter — sekarang terbukti perlu untuk UX yang benar):
  - `GET /geojson/filter-options` (`FrontendController::getFilterOptions()`, route `tematik.filter-options`) — mengembalikan nilai distinct `kabupaten` (dari `dbf_attributes->>'KABUPATEN'`), `tahun` (`data_spatial.tahun`), `opd_pengelola` (`opd.name` lewat `leftJoin`) untuk kombinasi `type`/`sub_type`/`year` yang sedang dibuka, query langsung ke `data_spatial` (bukan tergantung apa yang sudah dirender di peta). Dipanggil sekali lewat `loadFilterOptionsFromServer()` di `map.js`, paralel dengan `loadCategoriesMetadata()` saat inisialisasi peta (tidak saling menunggu — elemen `<select>` filter statis di Blade, tidak butuh `layerGroups`). Mengisi dropdown lewat `ensureFilterOptions()` yang sama (append-only), sehingga tidak konflik dengan opsi tambahan yang tetap digali `refreshFilterPanel()` secara progresif dari data yang dirender.
  - `GET /geojson/filter-categories` (`FrontendController::getFilterCategories()`, route `tematik.filter-categories`) — menerima `kabupaten`/`tahun`/`opd_pengelola` (AND, sama seperti `matches()` di client) plus `type`/`sub_type`/`year`, mengembalikan `categories` berisi **hanya** nama kategori (`categories.nama`) yang punya minimal satu feature cocok. `activateAllCategoriesForFilter()` (muat semua) diganti `loadCategoriesMatchingFilter(filters)` yang memanggil endpoint ini lebih dulu, lalu untuk tiap nama kategori yang dikembalikan: `findCheckboxForCategory()` (fungsi pencarian checkbox yang sudah ada) → `expandParentGroupIfNeeded()` → centang + `dispatchEvent("change")` + jeda 300ms — pola yang **identik** dengan `applySharedMapState()` yang sudah terbukti bekerja untuk memuat layer dari share link (bukan mekanisme baru).
  - `applyStructuredFilters()` diperbarui memanggil `loadCategoriesMatchingFilter()` (bukan lagi `activateAllCategoriesForFilter()`), teks status berubah jadi "Mencari layer yang cocok dengan filter..." — tidak lagi "Memuat seluruh layer...".
  - `thirdCheckbox._applyChange` yang ditambahkan sebelumnya **tidak dipakai lagi** oleh mekanisme baru ini (yang sekarang memakai `dispatchEvent` seperti share link), tapi dibiarkan tetap ada karena tidak mengganggu dan konsisten dengan pola `secondCheckbox._applyChange` yang sudah ada — komentar di kode diperbarui supaya tidak merujuk fungsi yang sudah dihapus.
  - Test baru `tests/Feature/FrontendFilterOptionsTest.php` (3 test): endpoint `filter-options` mengembalikan nilai distinct tanpa butuh layer aktif; endpoint `filter-categories` mengembalikan hanya kategori yang cocok kabupaten; kombinasi kabupaten+tahun+opd (AND) — kombinasi tidak cocok mengembalikan array kosong. Total 40 test relevan lulus (termasuk regresi `SharedMapFilter`/`FrontendPagesTest`/`FrontendGeojsonMetadataTest`/`DataSpatialGeojsonTest`).
  - **Konsekuensi yang masih berlaku**: memilih filter tetap memuat data (kali ini hanya kategori yang benar-benar cocok, tapi tetap butuh 1 request tambahan + waktu muat per kategori yang relevan) — bukan filter murni instan tanpa jaringan sama sekali, karena arsitektur tetap on-demand per kategori (Keputusan #2 soal caching IndexedDB tidak diubah).

### Catatan audit saat eksekusi

- `node --check` pada `public/frontend/js/map.js` gagal karena **duplikasi deklarasi fungsi pra-eksisting** (`updateSecondLevelCheckboxState`) yang tidak berkaitan dengan perubahan sesi ini (dikonfirmasi lewat `git diff` — bukan bagian dari suntikan kode fitur filter). Tidak diperbaiki (di luar cakupan). Sebagai gantinya, perubahan diverifikasi lewat pembacaan ulang tiap section yang disunting plus pengecekan keseimbangan kurung/kurawal seluruh file (selisih 0 sebelum dan sesudah edit).
- `resources/js/spatial.js` lulus `node --check` bersih.
- **Belum ada verifikasi browser** — sesuai catatan "Belum Diverifikasi" di bawah, ini tetap berlaku penuh: interaksi Leaflet (marker cluster, Path style) belum pernah dijalankan di browser sungguhan pada sesi ini.
- `npm run build`/`npm run dev` **belum dijalankan** — wajib dilakukan agar perubahan `resources/js/spatial.js` (Tahap 5) terlihat di browser. `public/frontend/js/map.js` (Tahap 3–4) tidak butuh build.

## Tujuan

Menutup rekomendasi review *"melengkapi visualisasi data dengan filter wilayah, sektor, OPD, dan tahun"* dengan menambah filter terstruktur pada Peta Tematik publik (`peta.blade.php`) dan peta mini di beranda (`home.blade.php`), dibangun di atas fitur yang sudah ada (search layer/kategori, loading per layer, share link — commit `dc839c4`) tanpa menggantikannya.

## Referensi

- [`../Hasil_Review_Marimoi_Jamil.md`](../Hasil_Review_Marimoi_Jamil.md) — rekomendasi asli.
- [`../03_plan/10-tindak-lanjut-review-jamil.md`](../03_plan/10-tindak-lanjut-review-jamil.md) — bagian C (Filter Terstruktur pada Peta Tematik Publik).
- [`03-metadata-dataset.md`](03-metadata-dataset.md) — prasyarat yang sudah selesai: kolom `opd_pengelola_id`/`tanggal_data` pada `data_spatial`, dan properti `opd_pengelola`/`tanggal_data` yang sudah mengalir ke `FrontendController::getGeojsonByDataType()` dan `homeSpatialSummary()`. Fitur ini murni membangun di atasnya, **tidak ada perubahan skema tambahan** yang dibutuhkan untuk filter OPD/Tahun.

## Cakupan

Termasuk: panel filter baru di Peta Tematik publik (Kabupaten/Kota, Tahun, OPD Pengelola), filter setara (Tahun, OPD Pengelola) di peta mini beranda, persistensi filter aktif ke share link, indikator jumlah titik yang sedang tampil.

Tidak termasuk: filter "Sektor/Kategori" terpisah (lihat Keputusan #1 — sudah terpenuhi oleh pohon layer/kategori yang ada), filter Kabupaten/Kota di peta mini beranda (Keputusan #5), backend baru untuk daftar nilai filter (Keputusan #3 — nilai filter digali langsung dari data yang sudah dimuat).

## Kondisi Existing (Audit Singkat)

| Area | Temuan |
| --- | --- |
| Peta Tematik publik | `resources/views/frontend/pages/peta.blade.php` — sidebar bertipe panel (`sidebar-layer`, `sidebar-basemap`, `sidebar-legend`, `sidebar-layer-tools`, `sidebar-download`) dengan tombol toggle di `#sidebar-control-buttons` (baris 379–407) dan `#nav-control-buttons` (baris 409–425). **Tidak ada panel filter.** |
| Wiring sidebar (JS) | `public/frontend/js/map.js` baris 2567–2697 — pola generik: `sidebarElements`/`toggleButtons` di-keyed dengan string yang sama (`legend`, `basemap`, dst.), lalu satu `forEach` menyambungkan semuanya, dan satu array `["layer","basemap","legend","download"]` (baris 2690) menyambungkan tombol close. Menambah panel baru **hanya butuh 3 baris tambahan** ke struktur ini — tidak perlu event listener baru per panel. |
| Rendering feature | `addFeatureToLayer()` (baris 1218) memanggil `L.geoJSON(feature, {onEachFeature: (f, l) => bindPopupContent(f, l, urlPath)}).addTo(targetLayer)`. Leaflet otomatis menempelkan `.feature` (properti GeoJSON asli, termasuk `dbf_attributes` yang sudah di-merge server) ke **leaf layer** (Marker/Path) yang dihasilkan — **tidak perlu perubahan apa pun pada rendering** untuk membaca properti filter dari layer yang sudah ada. |
| Pola baca rekursif layer aktif | `generateLegend()` (baris 503–594) dan `getLayerGroupOpacity()` (baris 610–635) sudah membuktikan pola yang dibutuhkan: `Object.entries(layerGroups)` 3 level (`root → second → third`) dicek `map.hasLayer(layer)` untuk tahu layer mana yang aktif, lalu turun rekursif lewat `.eachLayer()` untuk mencapai leaf. Filter memakai pola identik, bukan pola baru. |
| Popup atribut | `bindPopupContent()` (baris 796–929) — `allowedKeys` sudah menyertakan `KABUPATEN` (baris 817, ditambah `SUMBER_DATA`/`OPD_PENGELOLA`/`TANGGAL_DATA` dari fitur metadata dataset). `KABUPATEN` berasal dari `dbf_attributes` (kunci uppercase, konvensi shapefile/factory — lihat `DataSpatialFactory::generateTematikDbfAttributes()`), bukan kolom terstruktur — **tidak semua data punya nilai ini**, tergantung kelengkapan input admin/import. |
| Endpoint GeoJSON | `FrontendController::getGeojsonByDataType()` — sejak fitur metadata dataset, `properties` setiap feature sudah menyertakan `tahun`, `opd_pengelola`, dan (lewat `array_merge` dengan `dbf_attributes`) `KABUPATEN` bila diisi admin. **Tidak ada perubahan backend yang dibutuhkan** untuk data yang difilter di Peta Tematik. |
| Peta mini beranda | `resources/views/frontend/pages/home.blade.php` `#mapInfo`/`#mapTools` dan `resources/js/spatial.js` — `DATA.points` (dari `FrontendController::homeSpatialSummary()`) sejak fitur metadata dataset sudah membawa `t` (tahun) dan `op` (opd_pengelola) per titik (lihat `03-metadata-dataset.md` Tahap 5). `apply()` (baris ~621–629 versi saat itu, cek ulang saat eksekusi) sudah memfilter berdasarkan `active[layerId]` dan `query` teks — **tinggal ditambah 2 kondisi filter**, tanpa perubahan query SQL. |
| Share link | `SharedMap` model + migration `2026_09_16_182614_create_shared_maps_table.php` — kolom `layers` (json), `viewport` (json), `data_type`, `sub_type`, `year`. **Tidak ada kolom untuk filter.** `FrontendController::createSharedMap()` (baris 323–355) menyimpan `layers`/`viewport`/`data_type`/`sub_type`/`year`; `showSharedMap()` (baris 360–376) **hanya** mengoper `layers` dan `viewport` ke `sharedMapState` — `data_type`/`sub_type`/`year` yang sudah tersimpan pun tidak pernah dibaca balik (gap pra-eksisting, di luar cakupan dokumen ini, tidak diperbaiki). |
| Terapkan state share | `applySharedMapState()` (`map.js` baris 2249–2278) — loop centang checkbox per `state.layers`, lalu terapkan `state.viewport`. Titik ekstensi alami untuk menerapkan `state.filters` setelah semua layer selesai dimuat. |
| Indikator jumlah titik | Peta Tematik publik **tidak** punya indikator jumlah titik yang persisten (beda dengan peta mini beranda yang punya `#mapCount`). Perlu ditambahkan sebagai bagian dari panel filter baru (Keputusan #6). |

## Keputusan yang Perlu Dikunci Sebelum Implementasi

1. **Filter "Sektor/Kategori" tidak dibuat sebagai dropdown terpisah.** Rencana awal (`10-tindak-lanjut-review-jamil.md`) menyebut sektor/kategori sebagai salah satu dimensi filter, tapi pohon layer di sidebar (`#layer-list`, checkbox per kategori/sub-kategori) **sudah** berfungsi persis sebagai filter sektor — menambah dropdown kedua untuk hal yang sama akan membingungkan (dua kontrol UI berbeda untuk satu konsep). Filter baru fokus ke 3 dimensi yang **belum** ada kontrolnya sama sekali: Kabupaten/Kota, Tahun, OPD Pengelola.
2. **Filter beroperasi di sisi client, sebagai toggle visibility pada feature yang sudah dimuat/dirender — bukan query baru ke server, bukan add/remove dari layer group.** Alasan: (a) `loadCategoryData()` punya sistem caching IndexedDB berlapis (`mapDataStore`, chunked, keyed dari `{type, sub_type, year, category, limit, offset}`) — menambah dimensi filter ke cache key akan memaksa re-arsitektur caching yang berisiko tinggi untuk fitur ini; (b) kategori bertipe marker dirender lewat `L.markerClusterGroup`, yang attach/detach individual marker punya perilaku non-trivial terhadap cluster count — memanipulasi keanggotaan cluster secara langsung berisiko regresi visual yang sulit diverifikasi tanpa test browser langsung. Toggle visibility (opacity untuk Path, `setOpacity(0/1)` untuk Marker) **tidak mengubah keanggotaan layer group maupun cache**, jauh lebih aman untuk iterasi pertama. Konsekuensi yang diterima: badge jumlah cluster marker yang di-nonaktifkan filter mungkin masih menghitung marker tersembunyi (tidak diverifikasi tanpa browser — lihat bagian "Belum diverifikasi" nanti).
3. **Opsi dropdown filter digali dari feature yang sudah dimuat (progresif), bukan endpoint baru.** Konsisten dengan filosofi pemuatan on-demand yang sudah ada (`dc839c4` — pemuatan per layer untuk menghindari memory exhausted). Opsi filter untuk layer yang belum pernah dicentang memang belum akan muncul — ini perilaku yang diterima, bukan bug, karena tidak ada data untuk difilter sebelum layer dimuat.
4. **`KABUPATEN` diambil dari `dbf_attributes` (kunci uppercase), diterima sebagai tidak lengkap untuk semua data.** Feature tanpa nilai `KABUPATEN` akan **disembunyikan** ketika filter Kabupaten/Kota aktif dan bernilai selain "Semua" (karena tidak bisa dipastikan cocok) — bukan ditampilkan secara default. Ini konsisten dengan semantik filter pada umumnya (unknown ≠ match).
5. **Peta mini beranda hanya dapat filter Tahun dan OPD Pengelola — tidak Kabupaten/Kota.** `homeSpatialSummary()` memakai raw SQL yang tidak mem-parsing `dbf_attributes` JSONB (beda dari `getGeojsonByDataType()`); menambah ekstraksi JSONB untuk peta yang sifatnya dekoratif/teaser (mengarahkan user ke Peta Tematik lengkap lewat CTA "Buka Peta Pembangunan") tidak sepadan dengan kompleksitasnya. Kabupaten/Kota tetap tersedia penuh di Peta Tematik.
6. **Indikator jumlah titik ditambahkan sebagai bagian dari panel filter baru di Peta Tematik** (halaman ini belum punya indikator serupa sebelumnya), memenuhi kriteria "filter tidak menyembunyikan indikator jumlah data" dari rencana awal. Peta mini beranda sudah punya `#mapCount` — cukup dipastikan tetap akurat setelah filter diterapkan (Tahap 5).
7. **Filter aktif ikut tersimpan di `shared_maps`** lewat kolom baru `filters` (JSON, nullable) — additive terhadap tabel yang sudah ada, tidak mengubah kolom `layers`/`viewport`/`data_type`/`sub_type`/`year` yang sudah ada. Gap pra-eksisting (`showSharedMap()` tidak mengoper balik `data_type`/`sub_type`/`year`) **tidak diperbaiki** di dokumen ini — di luar cakupan filter dan berisiko menyentuh perilaku share link untuk data_type non-tematik yang tidak diuji di sini.

## Tahap 1 — Migration dan Model `SharedMap`

### `..._add_filters_column_to_shared_maps_table.php`

```php
Schema::table('shared_maps', function (Blueprint $table) {
    $table->json('filters')->nullable()->after('year');
});
```

### `app/Models/SharedMap.php`

Tambah `'filters'` ke `$fillable`, dan `'filters' => 'array'` ke `casts()`.

## Tahap 2 — Backend: Terima dan Kembalikan Filter di Share Link

### 2.1 `FrontendController::createSharedMap()` (baris 323–355)

Tambah validasi dan penyimpanan `filters`:

```php
$validated = $request->validate([
    'layers' => 'required|array|min:1',
    'layers.*' => 'string|max:255',
    'viewport' => 'nullable|array',
    'viewport.lat' => 'nullable|numeric',
    'viewport.lng' => 'nullable|numeric',
    'viewport.zoom' => 'nullable|numeric',
    'data_type' => 'nullable|string|max:50',
    'sub_type' => 'nullable|string|max:50',
    'year' => 'nullable|integer',
    'filters' => 'nullable|array',
    'filters.kabupaten' => 'nullable|string|max:255',
    'filters.tahun' => 'nullable|integer',
    'filters.opd_pengelola' => 'nullable|string|max:255',
]);

// ...

$sharedMap = SharedMap::create([
    'slug' => $slug,
    'layers' => $validated['layers'],
    'viewport' => $validated['viewport'] ?? null,
    'data_type' => $validated['data_type'] ?? 'tematik',
    'sub_type' => $validated['sub_type'] ?? null,
    'year' => $validated['year'] ?? null,
    'filters' => $validated['filters'] ?? null,
]);
```

### 2.2 `FrontendController::showSharedMap()` (baris 360–376)

Tambah `filters` ke `sharedMapState` (baris `->with('sharedMapState', [...])`), **tanpa** menyentuh perilaku `layers`/`viewport` yang sudah ada:

```php
return view('frontend.pages.peta', compact('documents'))
    ->with('sharedMapState', [
        'layers' => $sharedMap->layers,
        'viewport' => $sharedMap->viewport,
        'filters' => $sharedMap->filters,
    ]);
```

## Tahap 3 — Panel Filter: Peta Tematik (`peta.blade.php`)

### 3.1 Tombol toggle di `#sidebar-control-buttons` (setelah baris 406, tombol layer)

```blade
<button id="btn-toggle-sidebar-filter" type="button"
    class="text-black border border-black/20 border-b border-gray-400 rounded-none bg-white hover:bg-slate-200 px-3 py-2 text-sm transition-colors duration-200"
    title="Filter Peta" data-tooltip="Filter Peta">
    <i class="bi bi-funnel-fill"></i>
</button>
```

### 3.2 Panel sidebar (setelah baris 275, penutup `sidebar-layer`, sebelum `sidebar-basemap`)

```blade
<!-- Sidebar Filter -->
<div id="sidebar-filter"
    class="absolute top-0 right-0 w-[280px] md:w-[300px] h-[calc(100vh-70px)] bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 hidden">
    <div class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
        <h6 class="text-white mb-0 text-sm font-semibold">Filter Data</h6>
        <button id="btn-close-sidebar-filter" class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
            <i class="bi bi-x-lg text-white"></i>
        </button>
    </div>

    <p class="text-xs text-gray-500 mb-3">
        Filter berlaku pada layer yang sedang aktif. Opsi bertambah otomatis saat layer baru dimuat.
    </p>

    <div class="mb-3">
        <label for="filter-kabupaten" class="block text-xs font-medium text-gray-700 mb-1">Kabupaten/Kota</label>
        <select id="filter-kabupaten" class="w-full text-sm rounded-lg border-gray-300">
            <option value="">Semua Kabupaten/Kota</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="filter-tahun" class="block text-xs font-medium text-gray-700 mb-1">Tahun</label>
        <select id="filter-tahun" class="w-full text-sm rounded-lg border-gray-300">
            <option value="">Semua Tahun</option>
        </select>
    </div>

    <div class="mb-4">
        <label for="filter-opd" class="block text-xs font-medium text-gray-700 mb-1">OPD Pengelola</label>
        <select id="filter-opd" class="w-full text-sm rounded-lg border-gray-300">
            <option value="">Semua OPD</option>
        </select>
    </div>

    <button id="btn-reset-filter" type="button"
        class="w-full text-sm text-blue-600 border border-blue-200 rounded-lg py-1.5 hover:bg-blue-50 mb-3">
        Reset Filter
    </button>

    <p id="filter-count" class="text-xs text-gray-500 text-center"></p>
</div>
```

## Tahap 4 — JS: Wiring Panel + Logika Filter (`map.js`)

### 4.1 Sambungkan panel baru ke pola sidebar generik yang sudah ada

`sidebarElements` (baris ~2568–2574): tambah `filter: document.getElementById("sidebar-filter"),`.
`toggleButtons` (baris ~2577–2583): tambah `filter: document.getElementById("btn-toggle-sidebar-filter"),`.
Array close-buttons (baris 2690): `["layer", "basemap", "legend", "download", "filter"]`.

Tidak perlu event listener baru — mekanisme `forEach` yang sudah ada (baris 2676–2697) otomatis menangani buka/tutup panel baru ini karena namanya konsisten (`filter` di `sidebarElements`, `toggleButtons`, dan `btn-close-sidebar-filter`).

### 4.2 Fungsi inti: kumpulkan opsi + terapkan filter

Ditambahkan setelah `getLayerGroupOpacity()` (baris 635), memakai pola rekursi yang identik:

```js
/**
 * Nilai filter yang sedang aktif, dibaca dari 3 <select> di panel Filter.
 * String kosong berarti "semua" (tidak memfilter dimensi itu).
 */
function getActiveFilterValues() {
    return {
        kabupaten: document.getElementById("filter-kabupaten")?.value || "",
        tahun: document.getElementById("filter-tahun")?.value || "",
        opd_pengelola: document.getElementById("filter-opd")?.value || "",
    };
}

/**
 * Isi <select> dengan opsi baru yang belum ada, tanpa mengubah pilihan yang
 * sedang aktif (append-only) — dipanggil berulang setiap kali data baru dimuat.
 */
function ensureFilterOptions(selectEl, values) {
    if (!selectEl) return;
    const existing = new Set(Array.from(selectEl.options).map((o) => o.value));

    Array.from(values).sort().forEach((value) => {
        if (value === "" || existing.has(String(value))) return;
        const opt = document.createElement("option");
        opt.value = value;
        opt.textContent = value;
        selectEl.appendChild(opt);
        existing.add(String(value));
    });
}

/**
 * Jalan-jalan rekursif ke setiap leaf layer yang sedang aktif di peta (pola sama
 * dengan generateLegend()/getLayerGroupOpacity()), lalu: (a) kumpulkan nilai
 * distinct KABUPATEN/tahun/opd_pengelola untuk opsi dropdown, (b) terapkan
 * visibility sesuai filter aktif. Dipanggil setelah data baru dimuat, setiap kali
 * filter berubah, dan saat panel filter dibuka.
 */
function refreshFilterPanel() {
    const filters = getActiveFilterValues();
    const kabupatenSet = new Set();
    const tahunSet = new Set();
    const opdSet = new Set();
    let shown = 0;
    let total = 0;

    function matches(props) {
        if (filters.kabupaten && (props.KABUPATEN || "") !== filters.kabupaten) return false;
        if (filters.tahun && String(props.tahun || "") !== filters.tahun) return false;
        if (filters.opd_pengelola && (props.opd_pengelola || "") !== filters.opd_pengelola) return false;
        return true;
    }

    function applyVisibility(leaf, visible) {
        if (typeof leaf.setStyle === "function") {
            if (visible) {
                leaf.setStyle(leaf.marimoiOriginalStyle || {});
            } else {
                leaf.marimoiOriginalStyle = leaf.marimoiOriginalStyle || { ...leaf.options };
                leaf.setStyle({ opacity: 0, fillOpacity: 0 });
            }
        } else if (typeof leaf.setOpacity === "function") {
            leaf.setOpacity(visible ? 1 : 0);
        }
    }

    function visit(layer) {
        if (layer.eachLayer) {
            layer.eachLayer(visit);
            return;
        }
        if (!layer.feature?.properties) return;

        const props = layer.feature.properties;
        total++;
        if (props.KABUPATEN) kabupatenSet.add(props.KABUPATEN);
        if (props.tahun) tahunSet.add(String(props.tahun));
        if (props.opd_pengelola) opdSet.add(props.opd_pengelola);

        const visible = matches(props);
        if (visible) shown++;
        applyVisibility(layer, visible);
    }

    Object.values(layerGroups).forEach((secondLevel) => {
        Object.values(secondLevel).forEach((thirdLevel) => {
            Object.values(thirdLevel).forEach((leafGroup) => {
                if (map.hasLayer(leafGroup)) leafGroup.eachLayer(visit);
            });
        });
    });

    ensureFilterOptions(document.getElementById("filter-kabupaten"), kabupatenSet);
    ensureFilterOptions(document.getElementById("filter-tahun"), tahunSet);
    ensureFilterOptions(document.getElementById("filter-opd"), opdSet);

    const countEl = document.getElementById("filter-count");
    if (countEl) {
        countEl.textContent = total > 0 ? `${shown} dari ${total} titik ditampilkan` : "Belum ada data dimuat";
    }
}
```

Catatan implementasi: `leaf.marimoiOriginalStyle` dipakai untuk mengembalikan style asli sebuah Path (warna/opacity per kategori dari `getStyleForCategory()`) tanpa perlu memanggil ulang fungsi style — disimpan sekali di percobaan hide pertama, dipakai berulang saat show/hide bolak-balik. Untuk Marker (`setOpacity`), tidak butuh penyimpanan style karena hanya satu angka (0/1).

### 4.3 Hubungkan ke event dan siklus hidup pemuatan data

Tambah di blok inisialisasi sidebar (dekat baris 2676–2697, setelah wiring close-button):

```js
["filter-kabupaten", "filter-tahun", "filter-opd"].forEach((id) => {
    document.getElementById(id)?.addEventListener("change", refreshFilterPanel);
});

document.getElementById("btn-reset-filter")?.addEventListener("click", () => {
    ["filter-kabupaten", "filter-tahun", "filter-opd"].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.value = "";
    });
    refreshFilterPanel();
});

toggleButtons.filter?.addEventListener("click", refreshFilterPanel);
```

Lalu panggil `refreshFilterPanel()` di **dua** titik penyelesaian pemuatan data supaya layer yang baru dicentang langsung mematuhi filter yang sedang aktif:

- Akhir jalur cache-hit di `loadCategoryData()` (setelah `loadedCategories.add(categoryName);`, sebelum `return;` — baris ~1364).
- Akhir `renderCachedChunks()` yang dipanggil dari jalur network (cari pemanggilnya di `loadCategoryData()` setelah loop chunk selesai, sebelum `loadedCategories.add(categoryName)` pada jalur network — verifikasi baris persis saat eksekusi karena berada di tengah blok `try/while` yang panjang).

### 4.4 Sertakan filter saat membuat share link

Di `btnShareMap` click handler (baris 2898–2939), tambah `filters: getActiveFilterValues()` ke body JSON:

```js
body: JSON.stringify({
    layers,
    viewport: { lat: center.lat, lng: center.lng, zoom },
    data_type: tipeLayer.type,
    sub_type: tipeLayer.sub_type,
    year: tipeLayer.year,
    filters: getActiveFilterValues(),
}),
```

Kirim objek filter apa adanya (termasuk key dengan value string kosong) — validasi backend Tahap 2.1 sudah `nullable`, sehingga aman diterima meski beberapa dimensi tidak difilter.

### 4.5 Terapkan filter saat membuka share link

Di `applySharedMapState()` (baris 2249–2278), setelah loop pemuatan layer selesai (setelah `for (const categoryName of state.layers) { ... }`, sebelum blok `viewport`):

```js
if (state.filters) {
    if (state.filters.kabupaten) document.getElementById("filter-kabupaten").value = state.filters.kabupaten;
    if (state.filters.tahun) document.getElementById("filter-tahun").value = String(state.filters.tahun);
    if (state.filters.opd_pengelola) document.getElementById("filter-opd").value = state.filters.opd_pengelola;
    refreshFilterPanel();
}
```

Catatan: dropdown filter baru berisi opsi hasil `ensureFilterOptions()` yang dipanggil oleh `refreshFilterPanel()` di dalam loop pemuatan layer (Tahap 4.3) — pada titik ini opsi yang relevan seharusnya sudah ada di `<select>` sebelum nilai di-set.

## Tahap 5 — Filter di Peta Mini Beranda (`home.blade.php` + `spatial.js`)

### 5.1 Tambah 2 dropdown di `#mapTools` (`home.blade.php`, di dalam `<div id="mapTools">`, setelah blok `#layerList`)

```blade
<div class="border-t border-white/10 px-4 py-3 space-y-2">
    <select id="homeFilterTahun" class="w-full text-xs rounded-lg bg-white/10 border-white/10 text-white">
        <option value="" class="text-black">Semua Tahun</option>
    </select>
    <select id="homeFilterOpd" class="w-full text-xs rounded-lg bg-white/10 border-white/10 text-white">
        <option value="" class="text-black">Semua OPD</option>
    </select>
</div>
```

### 5.2 Perluas `apply()` di `spatial.js` (fungsi di dalam `initMap()`, cek ulang nomor baris saat eksekusi — per audit `03-metadata-dataset.md` berada di sekitar `#infoSource`/`#infoOpd`)

```js
function apply() {
    const filterTahun = $('#homeFilterTahun')?.value || '';
    const filterOpd = $('#homeFilterOpd')?.value || '';
    let shown = 0;
    markers.forEach((m) => {
        const on = active[m.p.k]
            && (!query || m.hay.includes(query))
            && (!filterTahun || String(m.p.t || '') === filterTahun)
            && (!filterOpd || (m.p.op || '') === filterOpd);
        if (on) { shown++; if (!group.hasLayer(m.mk)) { group.addLayer(m.mk); } }
        else if (group.hasLayer(m.mk)) { group.removeLayer(m.mk); }
    });
    $('#mapCount').textContent = fmt(shown);
}
```

Tambah pengisian opsi dropdown sekali saat data dimuat (dekat pembuatan `markers`, sebelum `apply()` pertama kali dipanggil):

```js
const tahunSet = new Set(pts.map((p) => p.t).filter(Boolean));
const opdSet = new Set(pts.map((p) => p.op).filter(Boolean));
ensureHomeFilterOptions($('#homeFilterTahun'), tahunSet);
ensureHomeFilterOptions($('#homeFilterOpd'), opdSet);

function ensureHomeFilterOptions(selectEl, values) {
    if (!selectEl) return;
    Array.from(values).sort().forEach((value) => {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = value;
        opt.className = 'text-black';
        selectEl.appendChild(opt);
    });
}

$('#homeFilterTahun')?.addEventListener('change', apply);
$('#homeFilterOpd')?.addEventListener('change', apply);
```

`#mapCount` yang sudah ada otomatis tetap akurat karena `apply()` yang sama yang memperbaruinya (Keputusan #6) — tidak perlu elemen baru di halaman ini.

## Tahap 6 — Testing (PHPUnit)

Logika visibility murni client-side (manipulasi DOM/Leaflet) tidak bisa diuji lewat PHPUnit — dicatat eksplisit sebagai keterbatasan di "Belum diverifikasi" nanti. Cakupan test dibatasi ke bagian yang **bisa** diverifikasi lewat HTTP: persistensi filter di share link, dan kehadiran elemen panel filter di HTML.

| Test | Skenario |
| --- | --- |
| `tests/Feature/SharedMapFilterTest.php` | (1) `createSharedMap` dengan `filters` terisi → tersimpan apa adanya di `shared_maps.filters`; (2) `createSharedMap` tanpa `filters` (opsional) → tetap berhasil, kolom `filters` null; (3) `showSharedMap` untuk slug dengan filter tersimpan → `filters` yang sama muncul di `window.MARIMOI_SHARED_STATE` pada HTML response (assert lewat `assertSee` string JSON atau `viewData('sharedMapState')`); (4) `filters.tahun` bukan integer → validasi menolak (`422`). |
| Perluasan `tests/Feature/FrontendPagesTest.php::test_thematic_map_page_keeps_map_controls_and_shows_hud` | Tambah assertion elemen panel filter baru: `assertSee('id="sidebar-filter"', false)`, `assertSee('id="btn-toggle-sidebar-filter"', false)`, `assertSee('id="filter-kabupaten"', false)`. |

Jalankan dengan filter dulu (`php artisan test --compact --filter=SharedMapFilter`, `--filter=FrontendPagesTest`), baru tawarkan full suite ke user.

## Urutan Eksekusi

1. Konfirmasi Keputusan #1–#7 di atas dengan pemilik produk (khususnya #2: pendekatan client-side visibility, bukan re-fetch/re-cluster).
2. Migration Tahap 1 (`filters` column) + update model `SharedMap`.
3. Backend Tahap 2 (`createSharedMap`/`showSharedMap`).
4. Blade Tahap 3 (tombol + panel filter di `peta.blade.php`).
5. JS Tahap 4.1–4.2 (wiring sidebar generik + fungsi `refreshFilterPanel`/`getActiveFilterValues`/`ensureFilterOptions`) — verifikasi manual di browser sesegera mungkin karena ini bagian paling berisiko (interaksi dengan `markerClusterGroup`/style Leaflet yang tidak bisa diuji lewat PHPUnit.
6. JS Tahap 4.3 (hook ke siklus pemuatan data) — cek ulang titik persis di `loadCategoryData()` karena nomor baris berubah setiap ada perubahan lain pada file 3000+ baris ini.
7. JS Tahap 4.4–4.5 (share link kirim/terima filter).
8. Tahap 5 (peta mini beranda) — independen dari Tahap 3–4, bisa dikerjakan paralel.
9. Test Tahap 6, jalankan per filter.
10. `vendor/bin/pint --dirty --format agent` untuk file PHP yang disentuh (migration, controller, model).
11. Verifikasi manual di browser: centang beberapa layer, ubah filter, pastikan marker/polygon tersembunyi-tampil benar, cluster count tidak menyesatkan, share link membuka kembali filter yang sama.
12. Tawarkan full test suite ke user.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Marker yang di-hide via `setOpacity(0)` di dalam `markerClusterGroup` masih terhitung di badge jumlah cluster, membuat angka cluster terlihat tidak sinkron dengan titik yang benar-benar terlihat | Diterima sebagai batasan iterasi pertama (Keputusan #2); dicatat eksplisit di "Belum diverifikasi" untuk dicek langsung di browser sebelum dianggap final. Perbaikan penuh (add/remove dari cluster) didorong ke iterasi berikutnya bila terbukti mengganggu di pemakaian nyata. |
| Filter yang di-hide masih bisa diklik/muncul di popup karena opacity 0 bukan `interactive: false` | `applyVisibility()` hanya mengubah opacity, tidak mengubah `interactive` — marker tersembunyi transparan tapi area klik tetap ada. Risiko minor (UX, bukan kebocoran data) karena popup tetap menampilkan data milik user sendiri yang publik; jika mengganggu di verifikasi manual, tambahkan `leaf.options.interactive = false` saat hide dan kembalikan saat show. |
| `KABUPATEN` tidak konsisten diisi admin → opsi dropdown Kabupaten/Kota bisa terasa tidak lengkap | Diterima sebagai keterbatasan data existing (Keputusan #4), bukan bug — konsisten dengan keputusan yang sama di `03-metadata-dataset.md` soal data lama tanpa metadata. |
| Menambah 3 baris ke `sidebarElements`/`toggleButtons`/close-array salah tempat memutus panel lain yang sudah berjalan | Perubahan dibatasi ke penambahan entri baru (bukan mengubah entri lama); test regresi `FrontendPagesTest::test_thematic_map_page_keeps_map_controls_and_shows_hud` memverifikasi kontrol lama (`#btn-share-map`, dst.) tetap ada setelah perubahan. |
| Filter di share link (Tahap 4.4–4.5) menyimpan `filters` dengan bentuk yang nanti berubah (mis. tambah dimensi baru) sehingga link lama tidak kompatibel | `filters` disimpan sebagai JSON bebas struktur (bukan kolom terpisah per dimensi) — field baru di masa depan otomatis diabaikan oleh link lama, field yang hilang di link lama diperlakukan sebagai "tidak difilter" (`state.filters.xxx` undefined → tidak di-assign ke `<select>`). |

## Belum Diverifikasi — Perlu Tindak Lanjut

Sama seperti dua fitur sebelumnya, sesi ini tidak punya akses browser untuk verifikasi visual. Untuk fitur ini secara khusus, verifikasi manual **lebih penting dari biasanya** karena inti fiturnya adalah interaksi Leaflet (marker cluster, Path style) yang perilakunya tidak sepenuhnya bisa dipastikan hanya dari membaca kode:

- Apakah `setOpacity(0)` pada marker di dalam `L.markerClusterGroup` benar-benar menyembunyikannya secara visual (termasuk saat marker itu sedang di-cluster-kan bersama marker lain), atau perlu penanganan tambahan.
- Apakah badge angka pada cluster ikut menyesuaikan atau tetap menghitung marker yang disembunyikan filter (lihat Risiko #1).
- Perilaku `refreshFilterPanel()` saat dipanggil sangat sering (tiap kali chunk data baru selesai dimuat) pada dataset besar — perlu dicek tidak menyebabkan jank pada peta dengan ribuan feature (walk rekursif ke semua leaf setiap kali dipanggil, mirip kompleksitas yang sudah dicatat soal `build()` di `spatial.js` pada temuan awal audit review).
- Tampilan panel filter di mobile (lebar 280–300px sama dengan sidebar lain, perlu dicek tidak tumpang tindih dengan sidebar lain yang mungkin dibuka bersamaan — meski `closeAllSidebars()` seharusnya mencegah ini).
- `npm run build`/`npm run dev` **wajib** dijalankan setelah Tahap 5 karena `resources/js/spatial.js` di-bundle Vite (sama seperti catatan di `03-metadata-dataset.md`). `public/frontend/js/map.js` (Tahap 3–4) tidak butuh build karena disajikan langsung dari `public/`.
- **Baru (revisi filter men-drive layer):** durasi nyata `activateAllCategoriesForFilter()` saat memilih filter pertama kali di peta dengan banyak kategori — berapa lama, dan apakah UX "Memuat seluruh layer untuk filter..." di `#filter-count` cukup jelas selama proses berjalan (bisa beberapa detik hingga puluhan detik tergantung jumlah kategori × ukuran data tiap kategori). Perlu dicek juga apakah layak menambah pembatalan/skip bila user menutup panel filter di tengah pemuatan.

## Kriteria Selesai

- [x] User bisa mempersempit tampilan peta berdasarkan Kabupaten/Kota, Tahun, dan OPD Pengelola sekaligus (AND) — filter kini memuat & menampilkan layer yang cocok secara otomatis (`activateAllCategoriesForFilter()`/`applyStructuredFilters()`), tidak lagi terbatas pada layer yang sudah dicentang manual; logika `refreshFilterPanel()`/`matches()` untuk penyaringan per-feature tidak berubah. **Efeknya di Leaflet (termasuk durasi pemuatan massal) belum diverifikasi visual** (lihat "Belum Diverifikasi").
- [x] Kombinasi filter + layer aktif konsisten dengan yang tersimpan di share link — `filters` tersimpan dan dikembalikan lewat `MARIMOI_SHARED_STATE`, diuji `test_creating_shared_map_stores_active_filters`/`test_opening_shared_map_link_exposes_stored_filters_to_the_frontend`.
- [x] Filter tidak menyembunyikan indikator jumlah titik yang sedang ditampilkan (`#filter-count` baru di Peta Tematik, `#mapCount` yang sudah ada diperluas di peta mini beranda).
- [x] Opsi filter bertambah otomatis seiring layer baru dimuat, tanpa endpoint backend baru.
- [x] Reset filter mengembalikan tampilan ke kondisi sebelum difilter.
- [x] Perubahan tidak merusak kontrol sidebar yang sudah ada — diverifikasi `test_thematic_map_page_keeps_map_controls_and_shows_hud` (diperluas, tetap lulus) plus regresi `SharedMapTest`/`HomepageTest`/`BackendTematikOnlyTest`/`DataSpatialGeojsonTest` (32 test lulus total).
- [x] Seluruh test Tahap 6 lulus.
- [ ] **Verifikasi manual di browser belum dilakukan** — daftar lengkap di "Belum Diverifikasi" tetap terbuka sampai dicek langsung: perilaku `setOpacity(0)` pada marker ter-cluster, akurasi badge cluster, performa `refreshFilterPanel()` pada dataset besar, tampilan panel di mobile, dan `npm run build` untuk `spatial.js`.
