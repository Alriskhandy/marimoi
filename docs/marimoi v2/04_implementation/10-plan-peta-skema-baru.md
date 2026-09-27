# Plan Implementasi: Peta Publik di Atas Skema Baru (Paralel, Bisa Dicoba Langsung)

## Status

✅ Fase 1–3 diimplementasikan (2026-09-28): `SpatialMapController` (route `peta-v2.*`), halaman `frontend.pages.peta-v2` + `resources/js/peta-v2.js`, endpoint `layers`/`geojson/{layer}`/`feature/{feature}`. Tervalidasi lewat `SpatialMapApiTest` (8 test) dan cek manual `curl` ke server dev — halaman & endpoint merespons dengan data live. Fase 4 (indikator/filter wilayah) masih belum dikerjakan, sesuai rencana (stretch/opsional).

**Catatan kondisi data yang ditemukan saat uji coba nyata**: dari 18 `spatial_layers` root (`parent_id IS NULL`), hanya **1** yang `is_active=true` — sisanya nonaktif, warisan `categories.is_active` yang sebagian besar `false` di data lama. Ini bukan bug kode baru, murni refleksi data asli; berarti panel filter di `/peta-v2` akan terlihat sangat sepi (cuma 1 layer: "Jalan Provinsi") sampai ada keputusan produk terpisah soal mengaktifkan lebih banyak `spatial_layers`/`categories`.

## Tujuan

Memberi jalur konkret untuk memakai skema V2 (`spatial_layers`, `spatial_layer_features`, `administrative_regions`, `development_projects`, `map_types`) sampai ke UI/UX peta, **tanpa** menunggu keputusan arsitektur sinkronisasi yang menghentikan Prioritas 8 (lihat `09-implementasi-penuh-database-v2.md` bagian Prioritas 8, keputusan 2026-09-27: *"Stop migrasi kode dulu"*).

Caranya: bangun halaman peta **baru dan paralel** (`/peta-v2`) yang membaca skema baru secara read-only, berdampingan dengan `/peta-tematik` (skema lama) yang tetap jadi halaman produksi. Tidak ada file lama yang diubah, jadi tidak ada risiko regresi ke halaman yang sudah dipakai publik — dan hasilnya bisa langsung dibuka di browser begitu Fase 1–2 selesai.

## Kenapa Paralel, Bukan Swap Langsung

Blocker yang ditemukan di Prioritas 8 (lihat dokumen 09) adalah soal **jalur tulis**: `CategoryController`/`DataSpatialController` masih aktif menulis ke `categories`/`data_spatial`, dan tulisan itu tidak ikut membuat baris di `spatial_layers`/`spatial_layer_features`. Kalau halaman peta **produksi** (`/peta-tematik`) langsung di-swap ke skema baru sekarang, data yang diinput admin setelah hari ini tidak akan pernah muncul di peta — silent data loss dari sudut pandang pengguna.

Halaman **baru** (`/peta-v2`) tidak punya masalah ini selama statusnya jelas: ini adalah pratinjau/uji coba di atas snapshot backfill (11.927 `spatial_layer_features` per 2026-09-27), bukan pengganti produksi. Begitu keputusan sinkronisasi (Observer, atau migrasi jalur tulis sekaligus) diambil, `/peta-v2` yang sudah terbukti jalan tinggal dipromosikan jadi pengganti `/peta-tematik` — lihat "Jalur ke Depan" di bagian akhir.

## Ruang Lingkup

**Termasuk:**
- Halaman peta publik baru, setara `/peta-tematik` versi lama (layer tree, filter, klik-lihat-detail), tapi baca dari skema baru.
- Endpoint GeoJSON baru per `spatial_layer`.
- Popup/detail feature yang menampilkan atribut dari `spatial_layer_features.attributes` + join balik ke `data_spatial` untuk field deskriptif yang belum ikut backfill (lihat catatan data di bawah).
- Indikator visual untuk data yang sudah punya `region_id` terisi (wilayah Maluku Utara, 82 baris) vs yang belum.

**Tidak termasuk (sengaja):**
- Tidak menyentuh `/peta-tematik`, `FrontendController`, `DataSpatialController`, atau JS lama (`map.js`, `map-cache.js`).
- Tidak membangun halaman admin/CRUD baru — peta admin (`/dashboard/data-spatial/peta`) tetap dengan skema lama.
- Tidak menyelesaikan gap sinkronisasi tulis (Observer dsb.) — itu keputusan arsitektur terpisah, di luar cakupan dokumen ini.
- Tidak mengisi ulang `region_id`/`atribut_schema`/`thumbnail_path` yang masih kosong — dipakai apa adanya.

## Kondisi Data yang Harus Diketahui Sebelum Membangun UI

| Aspek | Kondisi nyata (2026-09-27) | Dampak ke UI |
| --- | --- | --- |
| `spatial_layers` | 211/211 ter-backfill dari `categories`, hierarki `parent_id` 193/211 cocok | Tree layer bisa dibangun persis seperti tree kategori lama |
| `spatial_layer_features` | 11.927 baris, 1:1 dengan `data_spatial` **pada saat backfill** | Data baru yang diinput admin **setelah** backfill tidak akan muncul — tampilkan sebagai snapshot, bukan real-time |
| `spatial_layer_features.attributes` | Isi = `dbf_attributes` mentah (field shapefile), **bukan** `deskripsi`/`sumber_data`/`tahun`/`gambar` milik `data_spatial` | Untuk popup detail yang butuh field itu, join balik ke `data_spatial` lewat `legacy_data_spatial_id` (read-only, aman — lihat Fase 3) |
| `spatial_layer_features.region_id` | Terisi 82/11.927 (≈0,7%), hanya hasil matching teks lokasi Maluku Utara | Tampilkan label wilayah **hanya jika ada**; jangan anggap kosong = error |
| `development_projects` | 121 baris, 100% `needs_review=true` (butuh verifikasi OPD manual) | Kalau menampilkan proyek strategis, beri badge "belum diverifikasi OPD" |
| `spatial_layers.atribut_schema`/`thumbnail_path`/`map_type_id` | Sebagian besar kosong (tidak ada di `categories` lama) | Jangan bikin UI yang mengasumsikan field ini selalu ada |

## Pemetaan Field (Lama → Baru)

| Kebutuhan UI | Sumber lama (`Category`/`DataSpatial`) | Sumber baru (`SpatialLayer`/`SpatialLayerFeature`) |
| --- | --- | --- |
| Nama layer di legend | `categories.nama` | `spatial_layers.name` / `title` |
| Warna marker/poligon | `categories.warna` | `spatial_layers.color` |
| Ikon marker | `categories.icon` | *(belum ada kolom setara — pakai default sementara)* |
| Apakah tampil sebagai marker vs poligon | `categories.is_marker` | `spatial_layers.is_marker` |
| Hierarki filter (grup > sub-grup) | `categories.parent_id` | `spatial_layers.parent_id` |
| Geometry per data | `data_spatial.geom` | `spatial_layer_features.geometry` |
| Atribut shapefile mentah | `data_spatial.dbf_attributes` | `spatial_layer_features.attributes` |
| Deskripsi/sumber/tahun/gambar | `data_spatial.deskripsi`/`sumber_data`/`tahun`/`gambar` | *(tidak dibackfill)* — join `legacy_data_spatial_id → data_spatial` |
| Wilayah (kabupaten/kecamatan) | *(tidak ada di skema lama)* | `spatial_layer_features.region_id → administrative_regions` |

## Fase 1 — Backend Read-Only API

Controller baru: `app/Http/Controllers/SpatialMapController.php` (namespace terpisah dari `DataSpatialController`, tidak mewarisi apa pun darinya).

Route baru di `routes/web.php`, ditambahkan sebagai grup baru (tidak menyisipkan ke grup lama):

```php
Route::prefix('peta-v2')->name('peta-v2.')->group(function () {
    Route::get('/', [SpatialMapController::class, 'index'])->name('index');
    Route::get('/layers', [SpatialMapController::class, 'layerTree'])->name('layers');
    Route::get('/geojson/{layer:slug}', [SpatialMapController::class, 'geojson'])->name('geojson');
    Route::get('/feature/{feature}', [SpatialMapController::class, 'featureDetail'])->name('feature');
});
```

Query inti (pola sama seperti `Category::with('children')->roots()` di `FrontendController`, tinggal ganti model):

```php
public function layerTree()
{
    $layers = SpatialLayer::with('children')
        ->whereNull('parent_id')
        ->where('is_active', true)
        ->get(['id', 'slug', 'name', 'title', 'color', 'is_marker', 'parent_id']);

    return response()->json($layers);
}

public function geojson(SpatialLayer $layer)
{
    $features = SpatialLayerFeature::where('spatial_layer_id', $layer->id)
        ->selectRaw('id, external_id, region_id, attributes, ST_AsGeoJSON(geometry) as geojson')
        ->get();

    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $features->map(fn ($f) => [
            'type' => 'Feature',
            'geometry' => json_decode($f->geojson),
            'properties' => [
                'id' => $f->id,
                'attributes' => $f->attributes,
                'region_id' => $f->region_id,
            ],
        ]),
    ]);
}
```

Route model binding `{layer:slug}` dan `{feature}` mengikuti konvensi Laravel 12 yang sudah dipakai di rules proyek — tidak perlu `findOrFail` manual.

**Test:** `tests/Feature/SpatialMapApiTest.php` — assert `layers` mengembalikan tree dengan `parent_id` benar, `geojson` mengembalikan FeatureCollection valid dengan jumlah feature sesuai `spatial_layer_id`, feature tanpa `region_id` tetap muncul (bukan di-exclude).

## Fase 2 — Halaman Peta Baru

View baru: `resources/views/frontend/pages/peta-v2.blade.php`. Reuse asset Leaflet yang sudah di-load via CDN di `peta.blade.php` (baris 5-9, 490-493) — sama persis, supaya tidak menambah dependency baru.

JS baru: `resources/js/peta-v2.js` (bukan `public/frontend/js/map.js` yang lama — file lama dipakai `/peta-tematik` dan tidak boleh disentuh). Alur:
1. `fetch('/peta-v2/layers')` → render tree filter (checkbox per layer, warna sesuai `color`).
2. Saat layer dicentang → `fetch('/peta-v2/geojson/{slug}')` → `L.geoJSON(data, {...}).addTo(map)`.
3. Klik feature → popup dari `properties.attributes` + tombol "Detail" yang memanggil `/peta-v2/feature/{id}`.

Route halaman ditambahkan ke grup `peta-v2` di atas (`index`), memanggil view ini dan meng-compile asset lewat `@vite(['resources/js/peta-v2.js'])`.

**Test:** feature test HTTP biasa (`$this->get('/peta-v2')->assertOk()->assertSee(...)`) — tidak perlu browser test untuk halaman ini; verifikasi visual dilakukan manual (lihat "Cara Mencoba").

## Fase 3 — Detail Feature (Join Balik ke Legacy untuk Field Deskriptif)

Karena `attributes` hanya berisi `dbf_attributes`, endpoint `featureDetail` perlu join balik read-only:

```php
public function featureDetail(SpatialLayerFeature $feature)
{
    $legacy = $feature->legacy_data_spatial_id
        ? DataSpatial::select('deskripsi', 'sumber_data', 'tahun', 'gambar')
            ->find($feature->legacy_data_spatial_id)
        : null;

    return response()->json([
        'attributes' => $feature->attributes,
        'region' => $feature->region?->only(['name', 'level']),
        'layer' => $feature->layer->only(['name', 'color']),
        'legacy' => $legacy,
    ]);
}
```

Ini **membaca** `data_spatial`, bukan menulis — tidak melanggar batasan yang membuat Prioritas 8 berhenti (masalahnya ada di jalur *tulis*, bukan baca). Kalau `legacy_data_spatial_id` null (data yang seharusnya baru tapi tidak ter-backfill), tampilkan fallback "detail tidak tersedia" alih-alih error.

**Test:** assert response berisi `legacy.deskripsi` untuk feature dengan `legacy_data_spatial_id` terisi, dan `legacy: null` (bukan 500) untuk yang tidak.

## Fase 4 — Indikator Wilayah (Stretch, Opsional)

Karena `region_id` baru terisi 0,7%, jangan bangun filter wilayah sebagai fitur utama dulu. Cukup: kalau `region_id` ada, tampilkan badge nama kabupaten/kecamatan di popup. Filter dropdown "cari per wilayah" ditunda sampai coverage `region_id` naik signifikan (butuh lebih banyak data lokasi berpola jelas, atau matching berbasis geometri — di luar cakupan dokumen ini).

## Rencana Testing

Ikuti pola `phpunit/core rules`: test per fase, jalankan dengan filter sebelum lanjut fase berikutnya.

| Fase | File test | Perintah |
| --- | --- | --- |
| 1 | `SpatialMapApiTest` | `php artisan test --compact --filter=SpatialMapApiTest` |
| 2 | tambahan di `SpatialMapApiTest` atau file baru `PetaV2PageTest` | `php artisan test --compact --filter=PetaV2` |
| 3 | tambahan assertion di `SpatialMapApiTest` | sama seperti Fase 1 |

Regresi wajib di akhir: `php artisan test --compact` penuh — pastikan tetap **8 gagal** (baseline pra-eksisting yang sudah diketahui: `Auth\RegistrationTest`/`AuthenticationTest`, `FeedbackStoreTest`), tidak boleh lebih.

## Cara Mencoba (Manual, Setelah Fase 1–2 Selesai)

1. `npm run dev` atau `npm run build` (view baru butuh asset ter-compile — sesuai catatan Frontend Bundling di `CLAUDE.md`).
2. Buka `http://<host>/peta-v2` di browser.
3. Centang salah satu layer di panel filter (mis. layer hasil migrasi kategori "Batas Administrasi" atau layer tematik lain) → titik/poligon harus muncul di peta.
4. Klik satu feature → popup muncul dengan atribut mentah; klik "Detail" → deskripsi/sumber/tahun tampil kalau `legacy_data_spatial_id` ada.
5. Sanity check silang: buka `/peta-tematik` (halaman lama) di tab lain, bandingkan jumlah titik pada layer yang sama — harus mirip (boleh sedikit beda kalau ada input admin baru setelah backfill 2026-09-27, itu ekspektasi yang benar, bukan bug).

## Jalur ke Depan

`/peta-v2` yang sudah tervalidasi lewat fase-fase di atas adalah modal utama untuk dua keputusan yang masih menggantung:

1. **Kalau arsitektur sinkronisasi (Observer atau migrasi jalur tulis) sudah diputuskan** (lihat Prioritas 8 di `09-implementasi-penuh-database-v2.md`): `/peta-v2` tinggal dipromosikan — ganti nama route jadi `/peta-tematik`, redirect URL lama, dan `FrontendController` lama bisa mulai dipensiunkan sesuai checklist Prioritas 8.
2. **Kalau sinkronisasi belum diputuskan tapi UI/UX baru ingin tetap dipakai untuk uji coba/demo internal**: `/peta-v2` bisa terus hidup berdampingan tanpa batas waktu sebagai pratinjau, selama statusnya tetap jelas sebagai snapshot (bukan real-time) di setiap tempat yang relevan (dokumentasi, mungkin banner kecil di UI).

Tidak ada keputusan yang perlu dikunci sebelum mulai Fase 1 — seluruh dokumen ini murni penambahan (additive), tidak mengubah apa pun yang sudah berjalan.
