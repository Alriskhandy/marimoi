# Plan Implementasi Penuh: Database V2 (Rename Kanonik, Diurutkan dari Prioritas Tertinggi)

## Status

**Prioritas 0–6 sudah dieksekusi nyata di database dev (2026-09-27), Prioritas 7 tetap ditunda, Prioritas 8 belum bisa dikerjakan.** Ringkasan lengkap ada di bagian [Rekap Hasil Implementasi](#rekap-hasil-implementasi-2026-09-27) di bawah — baca itu dulu sebelum bagian "Prioritas 1-8" berikutnya di dokumen ini, karena bagian tersebut sekarang bersifat **rencana/referensi historis** (ditulis sebelum eksekusi) dan beberapa detailnya (terutama contoh SQL parsing anggaran di Prioritas 5, dan pendekatan backfill di Prioritas 2) **sudah direvisi saat eksekusi nyata** karena ditemukan masalah kualitas data yang tidak diantisipasi di rencana awal.

Sebuah batch migrasi pendahulu (`map_types`, `categories.atribut_schema`/`is_group`, `sektor`, `data_spatial_interventions`) sempat berjalan lalu **di-rollback penuh** (kode dan skema database) sebelum dipakai produksi — terverifikasi lewat `mcp__laravel-boost__database-schema` 2026-09-27: `categories` sudah kembali ke bentuk aslinya, tabel `map_types`/`sektor`/`data_spatial_interventions` tidak ada. Dokumen ini mengeksekusi ulang desain yang sama **langsung sebagai `spatial_layers`/`spatial_layer_features`** dari baseline asli, bukan dari sisa batch yang di-rollback.

---

## Rekap Hasil Implementasi (2026-09-27)

### Ringkasan status per Prioritas

| Prioritas | Status | Catatan singkat |
| --- | --- | --- |
| 0 — Baseline & keputusan | 🟡 Sebagian | Cardinality diverifikasi nyata; keputusan #1–7 dijalankan pakai rekomendasi default (bukan sign-off tertulis resmi dari Bappeda); **backup database formal tidak dilakukan** |
| 1 — `map_types`+`spatial_layers`+`spatial_layer_metadata` | ✅ Selesai | + CRUD `map_types`, validator `CategoryController`/`DataSpatialController` diganti |
| 2 — `sectors`+`spatial_layer_features`+intervensi | ✅ Selesai | Backfill 11.927 baris, pendekatan diubah dari rencana (lihat Deviasi) |
| 3 — `maps`/`map_layers`/`map_publications`/`map_shares` | ✅ Selesai (skema+logic) | Endpoint HTTP publik/UI **belum dibuat**, sengaja ditahan |
| 4 — `administrative_regions` | ✅ Selesai (skema + data) | Data Provinsi Maluku Utara terisi penuh (129 baris, kode BPS+Kemendagri) — lihat pembaruan 2026-09-27 |
| 5 — `development_projects` | ✅ Selesai | 121 proyek ter-backfill, **100%** butuh review OPD (bukan 67 seperti dugaan rencana awal) |
| 6 — Dashboard eksekutif | ✅ Selesai | Controller baru terpisah, **bukan** mengganti dashboard lama yang sudah ada |
| 7 — `analysis_layers`/`analysis_results` | ⏸️ Tetap ditunda | Sesuai rencana — rumus & data wilayah belum siap |
| 8 — Retirement tabel lama | ⏸️ Belum bisa | Checklist ±15 file baru selesai sebagian kecil; model ditandai `@deprecated` saja |

**Total test:** 219 passed, 8 failed (pra-existing, tidak terkait — route `/register` 404 dan `ProyekStrategisDaerahFactory` yang memang belum pernah dibuat sebelum sesi ini). Pint bersih di setiap tahap.

### Detail per Prioritas

#### Prioritas 0 — Baseline dan Keputusan

- **Cardinality `data_spatial` terverifikasi nyata**: total 11.927 baris berkategori+bergeometri (jadi dasar Prioritas 2); untuk `proyek_strategis` khusus — 121 baris, 53 nilai `PAKET` berbeda, **tidak ada satu pun yang dipakai >1 baris** → Keputusan #6 terjawab **(a) 1 baris = 1 proyek**.
- **Keputusan #1–#7**: dijalankan mengikuti kolom "Rekomendasi" di tabel keputusan (bukan hasil rapat/sign-off tertulis terpisah dengan Bappeda/product owner) — ini gap yang perlu disadari, bukan diklaim sebagai "terkunci resmi".
- **Backup database**: **tidak dilakukan** sebelum migration Prioritas 1 dijalankan — seluruh migration langsung dieksekusi ke database dev. Aman untuk dev, tapi kalau pola kerja yang sama diulang ke staging/production, langkah backup wajib benar-benar dijalankan, bukan dilewati lagi.
- **Checklist ±15 file**: disusun ulang dan diaudit ulang di akhir sesi (lihat Prioritas 8) — baru 2 dari ±15 file yang tersentuh (`CategoryController`, `DataSpatialController`, itu pun baru validatornya).

#### Prioritas 1 — `map_types` + `spatial_layers` + `spatial_layer_metadata` ✅

Migration nyata (nama file final, beda dari nama contoh di rencana bagian bawah):

- `2026_09_27_102911_create_map_types_table.php`
- `2026_09_27_102912_create_spatial_layers_table.php`
- `2026_09_27_102913_create_spatial_layer_metadata_table.php`
- `2026_09_27_103647_add_display_columns_to_spatial_layers_table.php` — **migration tambahan di luar rencana awal**, menutup gap: kolom `color`/`is_marker` (dipakai 48 tempat di view admin kategori untuk warna legend & marker) tidak ikut dirancang di migration pertama.
- `2026_09_27_105000_deactivate_legacy_map_types.php` — **migration tambahan**, perbaikan bug: seed awal men-set `is_active=true` untuk seluruh 5 `map_types`, padahal 4 tipe legacy (`psd`/`psn`/`pokir_dprd`/`usulan_musrenbang`) sudah digabung ke `tematik` dan harus tetap nonaktif (`BackendTematikOnlyTest` menangkap ini).

Model baru: `MapType`, `SpatialLayer`, `SpatialLayerMetadata`. Controller baru: `MapTypeController` (CRUD penuh + view index dengan modal, bukan cuma rencana kolom tabel) dengan permission `map-types.manage` (khusus `super-admin`). Validator `'type' => 'in:tematik'` di `CategoryController` (5 titik) dan `DataSpatialController` (1 titik) diganti `exists:map_types,slug`.

**Bug yang ditemukan & diperbaiki saat implementasi (tidak diantisipasi di rencana):**
1. Kolom display (`color`/`is_marker`/`thumbnail_path`) hilang dari desain awal → migration susulan.
2. Seed `map_types` salah — semua `is_active=true` → migration susulan menonaktifkan 4 tipe legacy.
3. **Bug 404 di UI**: form edit `map_types` membangun URL lewat `url('map-types')` yang mengabaikan prefix route `dashboard`, submit selalu 404. Diperbaiki jadi `route('map-types.update', ...)`.

**Verifikasi data**: 211/211 `categories` → `spatial_layers` cocok persis; 193/211 `parent_id` cocok persis dengan `categories.parent_id`.

**Test**: `SpatialLayerTest` (7), `MapTypeManagementTest` (8, termasuk 2 test regresi khusus bug 404 di atas).

#### Prioritas 2 — `sectors` + `spatial_layer_features` + `spatial_layer_feature_interventions` ✅

Migration nyata:

- `2026_09_27_110708_create_sectors_table.php`
- `2026_09_27_110709_create_spatial_layer_features_table.php`
- `2026_09_27_110710_create_spatial_layer_feature_interventions_table.php`

**Deviasi penting dari rencana**: rencana awal (lihat bagian Prioritas 2 di bawah) memakai job antrian Eloquent (`App\Jobs\BackfillSpatialLayerFeatures`) untuk backfill, dengan alasan takut OOM pada data besar. Implementasi nyata **tidak memakai job** — backfill dilakukan langsung lewat `DB::statement("INSERT INTO ... SELECT ...")` di dalam migration. Ini lebih sederhana **dan** lebih aman, karena PHP tidak pernah menarik data geometry ke memori sama sekali (seluruh proses copy terjadi di sisi database). Terbukti selesai 5 detik untuk 11.927 baris tanpa masalah.

Model baru: `Sector`, `SpatialLayerFeature`, `SpatialLayerFeatureIntervention`.

**Verifikasi data**: 11.927/11.927 baris `data_spatial` berkategori+bergeometri berhasil pindah, cocok persis dengan hitungan sebelum migrasi.

**Test**: `SpatialLayerFeatureTest` (5).

#### Prioritas 3 — `maps`/`map_layers`/`map_publications`/`map_shares`/`map_share_accesses` ✅ (skema + logic)

Migration nyata: `2026_09_27_113042_create_maps_and_sharing_tables.php` (satu file, 6 tabel).

**Deviasi dari rencana**: constraint `$table->check('opacity >= 0 AND opacity <= 1')` yang ditulis di contoh SQL rencana **dihapus** saat implementasi — Laravel Blueprint tidak punya method `check()` native, jadi baris itu tidak bisa dieksekusi apa adanya.

Model baru **ditulis lengkap dengan logic domain** (rencana awal menyebut "tidak ada logika non-trivial", ternyata sebaliknya — logic ini justru inti Prioritas 3): `Map::publish()` (buat revisi `map_publications` baru + snapshot layer, nonaktifkan revisi lama), `MapShare::generateFor()` (token acak, disimpan sebagai hash), `MapShare::isValid()`/`revoke()`/`recordAccess()`.

**Bug yang ditemukan & diperbaiki**: `MapShare::isValid()` sempat selalu mengembalikan `false` untuk share yang baru dibuat — penyebabnya, kolom `is_active` punya default `true` di level database, tapi Eloquent tidak otomatis membaca default itu ke objek PHP hasil `create()` kalau tidak disertakan eksplisit di array. Diperbaiki dengan mengisi eksplisit `'is_active' => true` saat `create()`.

**Belum dibuat, sengaja ditahan**: endpoint HTTP publik (buka link share, halaman kelola peta, generate QR) — butuh keputusan UX terpisah, sama alasannya dengan kenapa `CategoryController` tidak diganti total.

**Test**: `MapSharingTest` (10).

#### Prioritas 4 — `administrative_regions` ✅ (skema + data)

Migration nyata: `2026_09_27_114531_create_administrative_regions_table.php`. Model baru: `AdministrativeRegion`. Command `app/Console/Commands/ImportAdministrativeRegions.php` dibuat sebagai kerangka (`belum diimplementasikan`) — masih menunggu sumber **shapefile/batas geometri** resmi (bukan lagi kode wilayahnya, itu sudah ada, lihat di bawah).

**Bug yang ditemukan & diperbaiki**: relasi `belongsToMany` lewat tabel pivot `spatial_layer_regions` gagal karena Laravel menebak nama kolom `administrative_region_id` (dari nama model), padahal migration memakai `region_id`. Diperbaiki dengan menyebutkan eksplisit nama kolom di kedua sisi relasi.

**Update 2026-09-27 — data wilayah Provinsi Maluku Utara terisi penuh**: sumber resmi (`docs/marimoi v2/Kode_Wilayah_Provinsi_Maluku_Utara.md`) menyediakan **dua versi kode berbeda per wilayah** (BPS dan Kemendagri) yang keduanya dipakai luas di dokumen pemerintah/statistik — bukan cuma satu seperti asumsi kolom `code` semula. Migration susulan `2026_09_27_125305_split_code_into_bps_and_kemendagri_on_administrative_regions.php` memecah kolom `code` jadi `code_bps` + `code_kemendagri` (keduanya unique). `AdministrativeRegionSeeder` (terdaftar di `DatabaseSeeder`) mengisi:

- 1 provinsi (Maluku Utara)
- 10 kabupaten/kota
- 118 kecamatan

Total 129 baris, terverifikasi 100% terisi kedua kode-nya. Beberapa kecamatan punya ejaan/nama berbeda antara BPS dan Kemendagri (mis. BPS "Tabaru" = Kemendagri "Ibu Utara", BPS "Batang Lomang" = Kemendagri "Kepulauan Botanglomang") — `name` konsisten memakai versi Kemendagri sebagai acuan resmi, `code_bps` tetap disimpan lengkap untuk ditelusuri balik.

**Yang masih kosong**: kolom `geometry` (batas poligon wilayah) — hanya kode dan hierarki yang terisi, bukan bentuk spasialnya. Command import shapefile tetap scaffold.

**Test**: `AdministrativeRegionTest` (5) + `AdministrativeRegionSeederTest` (6, baru — memverifikasi jumlah baris per level, kelengkapan kedua kode, hierarki parent-child, dan idempotensi seeder).

#### Prioritas 5 — `development_projects` + Migrasi Bertahap ✅

Migration nyata: `2026_09_27_120902_create_development_projects_table.php`, `2026_09_27_120942_create_project_locations_table.php`, `2026_09_27_120942_create_project_regions_table.php`, `2026_09_27_121246_add_development_project_id_to_project_progress_reports_and_feedbacks.php`.

**Deviasi penting dari rencana (memengaruhi keakuratan contoh SQL di bagian Prioritas 5 di bawah)**: contoh migration di rencana awal memakai `regexp_replace(ANGGARAN, '[^0-9]', '', 'g')` polos untuk membersihkan angka anggaran. Saat benar-benar dieksekusi, ini **meledak jadi error "numeric field overflow"** — ternyata sebagian baris `ANGGARAN` berisi **dua angka digabung dalam satu teks** (contoh nyata: `"Jembatan Rp. 129.600.000.000, Jalan Rp. 264.787.000.000"` — rincian dua jenis pekerjaan dalam satu proyek gabungan). Regex polos menempelkan kedua angka itu jadi satu angka 24 digit yang salah total. **Diperbaiki** dengan validasi pola ketat dulu (pastikan teksnya benar-benar cuma satu angka rupiah bersih) sebelum diparse — kalau tidak cocok pola, `budget_amount` dibiarkan `NULL` dan `needs_review` ikut ditandai `true`.

Model baru: `DevelopmentProject`, `ProjectRegion`, `ProjectLocation`. `ProjectFeedback` dan `ProjectProgressReport` (model lama) dapat tambahan relasi `developmentProject()`.

**Temuan data yang mengoreksi asumsi rencana**: rencana awal menduga "67 dari 121 baris butuh review OPD". Kenyataan setelah backfill nyata: **seluruh 121 baris (100%)** butuh review, karena kolom `opd_pengelola_id` ternyata 100% `NULL` untuk seluruh data lama ini (bukan sebagian) — bukan bug kode, murni kondisi data yang belum pernah diisi.

**Verifikasi data**: 121 baris `data_spatial` `proyek_strategis` → 121 `development_projects` (1:1, tidak ada dedup); 121 `project_locations` (1:1).

**Test**: `DevelopmentProjectTest` (6).

#### Prioritas 6 — Dashboard Eksekutif ✅

Controller baru: `ExecutiveDashboardController` (4 endpoint: `summary`, `bySector`, `byRegion`, `trend`), route di bawah `dashboard/api/eksekutif/*`, permission `dashboard.view`.

**Deviasi dari rencana**: rencana tidak eksplisit menyebutkan ini, tapi keputusan implementasi nyata adalah **membuat controller baru yang berdiri sendiri**, bukan mengganti `PembangunanDashboardController` yang sudah ada dan sudah dipakai (masih berbasis `data_spatial`/`categories`). Alasannya sama seperti kenapa `CategoryController` tidak diganti total — mengganti fitur yang sudah berjalan berisiko merusak UI yang sudah dipakai. Keduanya sekarang hidup berdampingan; keputusan "mana yang dipakai di UI produksi" adalah pekerjaan produk terpisah.

**Test**: `ExecutiveDashboardTest` (7).

#### Prioritas 7 — `analysis_layers`/`analysis_results` ⏸️ Tetap ditunda

Tidak ada perubahan dari rencana — sengaja tidak dikerjakan. Dua syarat belum terpenuhi: (a) rumus/metodologi analisis belum dikunci Bappeda/perencana, (b) data `administrative_regions` masih kosong (strukturnya sudah ada dari Prioritas 4, isinya belum).

#### Prioritas 8 — Retirement `categories`/`data_spatial`/`shared_maps` ⏸️ Belum bisa dikerjakan

Audit ulang checklist ±15 file (2026-09-27) mengonfirmasi retirement **belum aman dilakukan**:

- `Category` masih dipakai penuh di: `CategoryController`, `DataSpatialController`, `FrontendController`, `PembangunanDashboardController`, `database/seeders/KategoriLayerSeeder.php`.
- `DataSpatial` masih dipakai penuh di: `DataSpatialController`, `FrontendController`, `PembangunanDashboardController`, `ProjectFeedbackController`, `ProjectProgressController`, `DashboardController`.
- 5 model duplikat (`Lokasi`, `PokirDprd`, `ProyekStrategisDaerah`, `ProyekStrategisNasional`, `UsulanMusrenbang`) dan `app/Support/MapDataVersion.php` juga belum tersentuh sama sekali.

Yang **sudah** dikerjakan sebagai langkah aman (tidak mengubah perilaku apa pun): `Category` dan `DataSpatial` ditandai `@deprecated` di PHPDoc, mengarahkan pembaca berikutnya ke `SpatialLayer`/`SpatialLayerFeature` dan ke checklist ini. **Tidak ada tabel atau kode yang dimatikan/dihapus** — mematikan compatibility source sekarang akan merusak aplikasi produksi secara langsung.

## Tujuan

Mengeksekusi Opsi A (rename kanonik, dikunci di `db-schema-v2.md` bagian "Keputusan bisnis yang sudah ditetapkan") sampai tuntas: `categories`/`data_spatial` pensiun sebagai tabel kanonik, digantikan `spatial_layers`/`spatial_layer_features`, dilengkapi `maps`/`map_layers`/`map_publications`/`map_shares`, `administrative_regions`, dan `development_projects` — sesuai definisi kolom lengkap di `db-schema-v2.md`. Dokumen ini tidak mengulang rasional/kolom (sudah ada di sana secara rinci); di sini murni urutan eksekusi, migration konkret, dan kriteria selesai per prioritas.

## Referensi

- [`../db-schema-v2.md`](../db-schema-v2.md) — definisi kolom lengkap tiap tabel, ERD, kebijakan FK/index, dan keputusan yang sudah dikunci vs masih terbuka. **Wajib dibaca sebelum eksekusi**, dokumen ini hanya menerjemahkan bagian "Urutan implementasi schema"-nya jadi migration/model/controller nyata.
- [`08-kategori-peta-dinamis-dan-metadata-layer.md`](08-kategori-peta-dinamis-dan-metadata-layer.md) — percobaan implementasi pertama untuk sebagian scope yang sama (map_types, atribut_schema, is_group, sektor, data_spatial_interventions), sudah di-rollback; **kode di sana tidak dipakai lagi**, tapi audit ±15 file dan struktur taksonomi 7-kelompok di dalamnya tetap valid dan dipakai ulang di sini.
- [`07-perbaikan-effort-kecil.md`](07-perbaikan-effort-kecil.md) — pola migrasi additive/non-destruktif dan grace period (`config/marimoi.php`) yang dipakai ulang di sini.

## Cakupan

**Termasuk:** Prioritas 0–8, urut sesuai dependency (bukan bisa diacak — lihat tabel fase di `db-schema-v2.md`).

**Tidak termasuk (butuh keputusan/dokumen terpisah, konsisten dengan `db-schema-v2.md`):**
- Prioritas 7 (`analysis_layers`/`analysis_results`) — menunggu rumus/metodologi dikunci; disinggung sebagai kerangka, tidak dieksekusi.
- Sumber data resmi batas wilayah administratif (shapefile BPS/Kemendagri) — Prioritas 4 menyediakan tabel dan command import, bukan penyedia datanya.
- Pencocokan spasial otomatis (`ST_DWithin`) untuk `spatial_layer_feature_interventions` — hanya struktur data untuk tautan manual.
- Peluruhan 5 model duplikat (`Lokasi`, `PokirDprd`, `ProyekStrategisDaerah`, `ProyekStrategisNasional`, `UsulanMusrenbang`) — disinggung di Prioritas 1, dieksekusi sebagai pekerjaan terpisah karena butuh audit pemakaian sendiri.
- Adapter `data_stores`/DuckLake — prinsip "jangan pindah storage sebelum ada kebutuhan yang dibuktikan" tetap berlaku; tidak dibuat di iterasi ini.

## Kondisi Existing yang Diverifikasi (2026-09-27)

| Verifikasi | Hasil |
| --- | --- |
| `SELECT type, count(*) FROM categories GROUP BY type` | `tematik=186`, `psd=12`, `usulan_musrenbang=10`, `pokir_dprd=2`, `psn=1` — total 211 baris |
| Kolom `categories` live | `id, type, nama, warna, icon, is_marker, deskripsi, parent_id, user_id, created_at, updated_at, is_active, gambar` — **tanpa** `map_type_id`/`sektor_id`/`atribut_schema`/`is_group` (dikonfirmasi rollback penuh) |
| Kolom `data_spatial` live | termasuk `sumber_data`, `opd_pengelola_id`, `tanggal_data` (migrasi metadata v2, **tidak** ikut rollback) — **tanpa** `sektor_id` |
| `CategoryController.php:147,324` | Masih `'type' => 'required\|in:tematik'` — audit lama masih akurat, rollback mengembalikan file ke kondisi ini |
| `routes/backend.php` | Tidak ada route `map-types` — konsisten dengan rollback |

**Cardinality `data_spatial` (Prasyarat Fase 0, belum diverifikasi ulang secara formal):** asumsi kerja "1 baris = 1 feature" dipakai di seluruh rencana ini mengikuti audit `db-schema-v2.md`/dokumen sumber sebelumnya — verifikasi ulang wajib jadi langkah pertama sebelum migration Prioritas 1 dijalankan (lihat Prioritas 0).

## Keputusan yang Perlu Dikunci Sebelum Eksekusi

| # | Keputusan | Opsi | Rekomendasi |
| --- | --- | --- | --- |
| 1 | Siapa yang boleh membuat `map_types` baru? | (a) super-admin saja, (b) admin-bappeda juga | (a) — perubahan taksonomi berdampak lintas OPD |
| 2 | Cap "maksimal 10 kategori aktif" (`CategoryController::validateMaxActiveCategories`) tetap berlaku? | (a) tetap, dihitung per `map_type_id`, (b) dihapus | (a) — pindah kunci hitung dari `type` string ke `map_type_id` |
| 3 | Sumber data kode wilayah administratif (Prioritas 4) | (a) impor shapefile BPS/Kemendagri resmi, (b) tunda tabel ini | **✅ Terjawab 2026-09-27**: kode wilayah (bukan batas geometri/shapefile) tersedia lengkap di `docs/marimoi v2/Kode_Wilayah_Provinsi_Maluku_Utara.md` untuk Provinsi Maluku Utara, **kedua versi kode** (BPS + Kemendagri) dipakai sekaligus (kolom `code_bps`+`code_kemendagri`). Batas geometri poligon (shapefile) masih belum ada — `marimoi:import-wilayah` tetap scaffold untuk itu. |
| 4 | Level wilayah awal | (a) provinsi+kab/kota saja, (b) sampai kecamatan, (c) sampai desa/kelurahan | **✅ Terjawab**: (b) — 129 baris (1 provinsi + 10 kabupaten/kota + 118 kecamatan) sudah diisi lewat `AdministrativeRegionSeeder` |
| 5 | Mode akses share URL publication `public` | (a) guest via token, (b) wajib login semua | (a) untuk `public`, wajib login untuk `unlisted`/data terbatas (sudah dikunci di `db-schema-v2.md`) |
| 6 | Cardinality `data_spatial` untuk baris `proyek_strategis` (yang punya `project_progress_reports` menempel) | (a) 1 baris = 1 proyek (feature = identity), (b) 1 baris = 1 lokasi dari proyek yang sama | Verifikasi query nyata dulu (lihat Prioritas 0) — menentukan bentuk migrasi Prioritas 5 |
| 7 | Dua-sistem-role (`users.role_id` vs Spatie-style vs `user_role_assignments`) | Konsolidasi sebelum atau sesudah Prioritas 1? | Sebelum — `owner_opd_id`/`owner_user_id` di setiap tabel baru butuh satu sumber kebenaran, lihat Prioritas 0 |

---

## Prioritas 0 — Baseline dan Keputusan (Wajib, Tidak Bisa Dilewati)

Tidak menghasilkan migration baru — murni verifikasi dan keputusan yang mengunci parameter migration di prioritas berikutnya.

1. **Verifikasi cardinality `data_spatial`**: jalankan query distribusi per `data_type`/`kategori_id` dan sampel manual 10-20 baris `proyek_strategis` untuk memastikan "1 baris = 1 feature" (Keputusan #6). Hasilnya menentukan apakah `spatial_layer_features` → `development_projects` di Prioritas 5 adalah pemetaan 1:1 atau butuh deduplikasi.
2. **Kunci Keputusan #1–#7** di atas dengan product owner/Bappeda.
3. **Backup database** dan uji restore di staging sebelum migration Prioritas 1 dijalankan — konsisten dengan kebiasaan proyek ini (lihat catatan drift migration di `08-kategori-peta-dinamis-dan-metadata-layer.md`).
4. **Susun ulang checklist ±15 file** (dari audit sebelumnya, masih berlaku setelah rollback — lihat tabel verifikasi di atas) sebagai *definition of done* Prioritas 1: `app/Models/Category.php`, `app/Models/DataSpatial.php`, 5 model duplikat, `CategoryController.php`, `DataSpatialController.php`, `ProjectFeedbackController.php`, `FeedbackController.php`, `ScopedProjectFeedbackController.php`, `Api/V1/LayerController.php`, `app/Support/MapDataVersion.php`, `database/seeders/KategoriLayerSeeder.php`, `routes/backend.php`, `categories/index.blade.php`, `data_spatial/{index,create}.blade.php`, `project-progress/index.blade.php`, `partials/sidebar.blade.php`.

**Kriteria selesai:** backup terverifikasi, ketujuh keputusan terkunci tertulis (mis. di PR description atau catatan rapat), laporan cardinality `data_spatial` tersedia.

---

## Prioritas 1 — `map_types` + `spatial_layers` + `spatial_layer_metadata`

Fondasi katalog layer. Semua prioritas berikutnya bergantung pada ini.

### Migration 1: `map_types`

`database/migrations/2026_10_01_090000_create_map_types_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->jsonb('konfigurasi')->nullable();
            $table->timestamps();
        });

        // Seed dari nilai `categories.type` yang benar-benar dipakai saat ini (lihat
        // "Kondisi Existing yang Diverifikasi" — jangan menebak, pakai hasil query nyata).
        DB::table('map_types')->insert([
            ['slug' => 'tematik', 'nama' => 'Peta Tematik', 'urutan' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'usulan_musrenbang', 'nama' => 'Usulan Musrenbang', 'urutan' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'pokir_dprd', 'nama' => 'Pokok Pikiran DPRD', 'urutan' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'psd', 'nama' => 'Proyek Strategis Daerah', 'urutan' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'psn', 'nama' => 'Proyek Strategis Nasional', 'urutan' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('map_types');
    }
};
```

### Migration 2: `spatial_layers` (identitas, backfill dari `categories`)

`database/migrations/2026_10_01_090100_create_spatial_layers_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spatial_layers', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('layer_class', 30);
            $table->string('source_type', 50)->default('feature');
            $table->foreignId('legacy_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('map_type_id')->nullable()->constrained('map_types')->nullOnDelete();
            $table->foreignId('sector_id')->nullable(); // FK ditambahkan setelah tabel `sectors` ada (Prioritas 2)
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_opd_id')->nullable()->constrained('opd')->nullOnDelete();
            $table->string('geometry_type', 30)->nullable();
            $table->integer('srid')->nullable();
            $table->geometry('extent')->nullable();
            $table->geometry('center_point')->nullable();
            $table->smallInteger('min_zoom')->nullable();
            $table->smallInteger('max_zoom')->nullable();
            $table->string('visibility', 20)->default('private');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_downloadable')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('spatial_layers')->nullOnDelete();
            $table->boolean('is_group')->default(false);
            $table->jsonb('atribut_schema')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_opd_id', 'is_active', 'visibility']);
            $table->index(['map_type_id', 'sector_id']);
        });

        // Backfill identitas dari categories — kolom map_type_id/is_group/atribut_schema
        // TIDAK di-backfill (tidak ada nilainya di categories saat ini), dibuat kosong/default.
        DB::statement("
            INSERT INTO spatial_layers
                (public_id, slug, name, title, description, layer_class, map_type_id,
                 owner_user_id, parent_id, visibility, is_active, is_group,
                 legacy_category_id, created_at, updated_at)
            SELECT
                gen_random_uuid(),
                'layer-' || c.id || '-' || regexp_replace(lower(c.nama), '[^a-z0-9]+', '-', 'g'),
                c.nama,
                c.nama,
                c.deskripsi,
                CASE WHEN c.type = 'tematik' THEN 'thematic' ELSE 'development' END,
                mt.id,
                c.user_id,
                NULL, -- parent_id dipetakan di pass kedua di bawah, butuh mapping id lama->baru dulu
                CASE WHEN c.is_active THEN 'private' ELSE 'private' END,
                c.is_active,
                false,
                c.id,
                c.created_at,
                c.updated_at
            FROM categories c
            LEFT JOIN map_types mt ON mt.slug = c.type
        ");

        // Pass kedua: isi parent_id spatial_layers dari parent_id categories lewat legacy_category_id.
        DB::statement("
            UPDATE spatial_layers sl
            SET parent_id = parent_sl.id
            FROM categories c
            JOIN spatial_layers parent_sl ON parent_sl.legacy_category_id = c.parent_id
            WHERE sl.legacy_category_id = c.id AND c.parent_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('spatial_layers');
    }
};
```

**Catatan idempotensi:** migration ini idempoten secara alami karena `spatial_layers` adalah tabel baru — tidak ada risiko duplikasi selama tidak dijalankan dua kali di database yang sama tanpa `down()` di antaranya.

### Migration 3: `spatial_layers.sector_id` FK (setelah `sectors` ada di Prioritas 2)

Ditunda ke Prioritas 2 secara eksplisit — lihat catatan di migration di atas (`$table->foreignId('sector_id')->nullable();` tanpa `constrained()`).

### Migration 4: `spatial_layer_metadata`

`database/migrations/2026_10_01_090200_create_spatial_layer_metadata_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spatial_layer_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spatial_layer_id')->unique()->constrained('spatial_layers')->cascadeOnDelete();
            $table->text('abstract')->nullable();
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->string('license')->nullable();
            $table->text('attribution')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->date('data_reference_date')->nullable();
            $table->smallInteger('data_reference_year')->nullable();
            $table->string('update_frequency', 50)->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->text('lineage')->nullable();
            $table->text('positional_accuracy')->nullable();
            $table->text('attribute_accuracy')->nullable();
            $table->text('completeness')->nullable();
            $table->text('limitations')->nullable();
            $table->string('language_code', 10)->default('id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Backfill 1 baris metadata per layer dari data_spatial.sumber_data/opd_pengelola_id/tanggal_data
        // milik BARIS PERTAMA tiap kategori — keputusan sementara sampai Prasyarat Prioritas 0 poin 1
        // (verifikasi cardinality) menentukan apakah nilai per-feature ini seragam atau bervariasi.
        DB::statement("
            INSERT INTO spatial_layer_metadata (spatial_layer_id, source_name, data_reference_year, created_at, updated_at)
            SELECT DISTINCT ON (sl.id)
                sl.id,
                ds.sumber_data,
                EXTRACT(YEAR FROM ds.tanggal_data)::int,
                now(),
                now()
            FROM spatial_layers sl
            JOIN data_spatial ds ON ds.kategori_id = sl.legacy_category_id
            WHERE ds.sumber_data IS NOT NULL
            ORDER BY sl.id, ds.created_at ASC
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('spatial_layer_metadata');
    }
};
```

### Model `app/Models/MapType.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapType extends Model
{
    protected $table = 'map_types';

    protected $fillable = ['slug', 'nama', 'deskripsi', 'icon', 'urutan', 'is_active', 'konfigurasi'];

    protected $casts = ['is_active' => 'boolean', 'konfigurasi' => 'array'];

    public function spatialLayers(): HasMany
    {
        return $this->hasMany(SpatialLayer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('urutan');
    }
}
```

### Model `app/Models/SpatialLayer.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpatialLayer extends Model
{
    use SoftDeletes;

    protected $table = 'spatial_layers';

    protected $fillable = [
        'slug', 'name', 'title', 'description', 'layer_class', 'source_type',
        'map_type_id', 'sector_id', 'owner_user_id', 'owner_opd_id',
        'geometry_type', 'srid', 'min_zoom', 'max_zoom', 'visibility',
        'is_active', 'is_downloadable', 'parent_id', 'is_group', 'atribut_schema',
        'published_at', 'thumbnail_path',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_downloadable' => 'boolean',
            'is_group' => 'boolean',
            'atribut_schema' => 'array',
            'published_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(fn (self $layer) => $layer->public_id ??= (string) \Illuminate\Support\Str::uuid());
    }

    public function mapType(): BelongsTo
    {
        return $this->belongsTo(MapType::class);
    }

    public function metadata(): HasOne
    {
        return $this->hasOne(SpatialLayerMetadata::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(SpatialLayerFeature::class);
    }

    public function scopeSelectable($query)
    {
        return $query->where('is_group', false);
    }

    public function scopeGroups($query)
    {
        return $query->where('is_group', true);
    }

    /**
     * Bangun rule Laravel Validator dari atribut_schema layer ini, dipakai untuk
     * memvalidasi payload attributes yang dikirim form data spasial.
     *
     * @return array<string, string>
     */
    public function atributValidationRules(): array
    {
        $rules = [];

        foreach ($this->atribut_schema['fields'] ?? [] as $field) {
            $parts = [($field['required'] ?? false) ? 'required' : 'nullable'];
            $parts[] = match ($field['type'] ?? 'string') {
                'number' => 'numeric',
                'date' => 'date',
                'select' => 'in:'.implode(',', $field['options'] ?? []),
                default => 'string',
            };
            $rules['attributes.'.$field['key']] = implode('|', $parts);
        }

        return $rules;
    }
}
```

### `CategoryController.php` — ganti validator hardcode ke `spatial_layers`/`map_types`

Perubahan sama persis strukturnya seperti direncanakan di `08-kategori-peta-dinamis-dan-metadata-layer.md` Tahap 1 (tabel sebelum/sesudah per baris), **tapi target model sekarang `SpatialLayer`, bukan `Category`**:

| Titik (verifikasi ulang nomor baris saat eksekusi) | Sebelum | Sesudah |
| --- | --- | --- |
| Query listing/dropdown | `Category::where(...)` | `SpatialLayer::selectable()->where(...)` |
| `store()`/`update()` validator | `'type' => 'required\|in:tematik'` | `'map_type_id' => 'required\|exists:map_types,id'` |
| Cap "maksimal 10 kategori aktif" (Keputusan #2) | dihitung per `type` | dihitung per `map_type_id` |

Controller ini idealnya di-rename mengikuti model (`SpatialLayerController`) sebagai bagian pekerjaan yang sama — tapi rename controller **tidak wajib** untuk Prioritas 1 selesai; boleh dilakukan di iterasi terpisah bila waktu terbatas, asal route/permission tetap konsisten.

### Routes: CRUD `map_types`

Sama seperti rencana `08-...md` — `Route::resource('map-types', MapTypeController::class)->middleware('permission:map-types.manage')`, permission baru `map-types.manage` default hanya `super-admin` (Keputusan #1).

---

## Prioritas 2 — `sectors` + `spatial_layer_features` + `spatial_layer_feature_interventions`

### Migration 1: `sectors` + FK `spatial_layers.sector_id`

`database/migrations/2026_10_02_090000_create_sectors_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('sectors')->insert(collect([
            'pupr' => 'PUPR', 'kesehatan' => 'Kesehatan', 'pendidikan' => 'Pendidikan',
            'ekonomi' => 'Ekonomi', 'lingkungan' => 'Lingkungan Hidup',
        ])->map(fn ($name, $code) => ['code' => $code, 'name' => $name, 'created_at' => now(), 'updated_at' => now()])->values()->all());

        Schema::table('spatial_layers', function (Blueprint $table) {
            $table->foreign('sector_id')->references('id')->on('sectors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('spatial_layers', function (Blueprint $table) {
            $table->dropForeign(['sector_id']);
        });

        Schema::dropIfExists('sectors');
    }
};
```

Daftar sektor awal adalah contoh — konfirmasi daftar lengkap ke Bappeda saat eksekusi, bukan diasumsikan final (sama seperti catatan di `08-...md` Tahap 4).

### Migration 2: `spatial_layer_features` (backfill dari `data_spatial`, job batch)

`database/migrations/2026_10_02_090100_create_spatial_layer_features_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spatial_layer_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spatial_layer_id')->constrained('spatial_layers')->restrictOnDelete();
            $table->foreignId('source_version_id')->nullable(); // FK ditambahkan setelah spatial_layer_versions ada
            $table->string('external_id')->nullable();
            $table->geometry('geometry');
            $table->foreignId('region_id')->nullable(); // FK ditambahkan Prioritas 4 (administrative_regions)
            $table->jsonb('attributes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('legacy_data_spatial_id')->nullable()->constrained('data_spatial')->nullOnDelete();
            $table->timestamps();

            $table->index('spatial_layer_id');
            $table->index('legacy_data_spatial_id');
        });

        DB::statement('CREATE INDEX spatial_layer_features_geometry_gist ON spatial_layer_features USING GIST (geometry)');
    }

    public function down(): void
    {
        Schema::dropIfExists('spatial_layer_features');
    }
};
```

**Backfill dijalankan sebagai `App\Jobs\BackfillSpatialLayerFeatures` (job antrian, bukan di dalam migration)** — beda pola dari Prioritas 1 karena `data_spatial` berpotensi puluhan ribu baris dengan kolom geometry besar (risiko memory exhaustion yang sama seperti pernah terjadi di `CategoryController`/`ProjectFeedbackController` bila di-load penuh dalam satu proses, lihat catatan risiko sesi sebelumnya):

```php
<?php

namespace App\Jobs;

use App\Models\DataSpatial;
use App\Models\SpatialLayer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class BackfillSpatialLayerFeatures implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        DataSpatial::query()
            ->select(['id', 'kategori_id', 'uuid', 'dbf_attributes', 'geom', 'user_id', 'sumber_data'])
            ->whereNotNull('kategori_id')
            ->chunkById(500, function ($chunk) {
                $layerIdByLegacyCategory = SpatialLayer::pluck('id', 'legacy_category_id');

                $rows = $chunk->map(fn ($ds) => [
                    'spatial_layer_id' => $layerIdByLegacyCategory[$ds->kategori_id] ?? null,
                    'external_id' => $ds->uuid,
                    'geometry' => $ds->geom,
                    'attributes' => json_encode($ds->dbf_attributes),
                    'created_by' => $ds->user_id,
                    'legacy_data_spatial_id' => $ds->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->filter(fn ($row) => $row['spatial_layer_id'] !== null);

                if ($rows->isNotEmpty()) {
                    DB::table('spatial_layer_features')->insert($rows->all());
                }
            });
    }
}
```

Dijalankan lewat `php artisan queue:work` setelah `dispatch(new BackfillSpatialLayerFeatures())`, bukan `migrate` biasa — konsisten dengan prinsip "import besar tidak boleh menahan request/proses migration" (`db-schema-v2.md` §"Penyimpanan Geospasial").

### Migration 3: `spatial_layer_feature_interventions`

`database/migrations/2026_10_02_090200_create_spatial_layer_feature_interventions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spatial_layer_feature_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id_eksisting')->constrained('spatial_layer_features')->cascadeOnDelete();
            $table->foreignId('feature_id_intervensi')->constrained('spatial_layer_features')->cascadeOnDelete();
            $table->string('jenis_hubungan', 50)->default('terkait');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['feature_id_eksisting', 'feature_id_intervensi'], 'slf_interventions_unique_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spatial_layer_feature_interventions');
    }
};
```

### Model `app/Models/SpatialLayerFeature.php` (ringkas)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpatialLayerFeature extends Model
{
    protected $table = 'spatial_layer_features';

    protected $fillable = ['spatial_layer_id', 'external_id', 'geometry', 'region_id', 'attributes', 'created_by'];

    protected $casts = ['attributes' => 'array'];

    public function layer(): BelongsTo
    {
        return $this->belongsTo(SpatialLayer::class, 'spatial_layer_id');
    }

    public function intervensiTerkait(): HasMany
    {
        return $this->hasMany(SpatialLayerFeatureIntervention::class, 'feature_id_eksisting');
    }

    public function kondisiEksistingTerkait(): HasMany
    {
        return $this->hasMany(SpatialLayerFeatureIntervention::class, 'feature_id_intervensi');
    }
}
```

**Kriteria selesai Prioritas 1–2:** ±15 file dari checklist Prioritas 0 sudah membaca `SpatialLayer`/`SpatialLayerFeature`, bukan `Category`/`DataSpatial`, untuk **jalur baru** (jalur lama tetap dipertahankan sebagai compatibility source, tidak dihapus di sini — lihat Prioritas 8); test regresi (`CategoryHierarchyTest`, `FrontendPagesTest`, dll.) lulus; job backfill `spatial_layer_features` selesai tanpa OOM untuk seluruh ~11.900+ baris `data_spatial` live.

---

## Prioritas 3 — `maps`/`map_layers`/`map_publications`/`map_shares`/`map_share_accesses`

Tidak ada padanan lama — seluruhnya tabel baru murni, bergantung hanya pada `spatial_layers` (Prioritas 1), bisa dikerjakan paralel dengan Prioritas 2.

`database/migrations/2026_10_03_090000_create_maps_and_sharing_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_opd_id')->nullable()->constrained('opd')->nullOnDelete();
            $table->string('visibility', 20)->default('private');
            $table->boolean('is_active')->default(true);
            $table->jsonb('basemap_config')->nullable();
            $table->geometry('center_point')->nullable();
            $table->decimal('zoom', 5, 2)->nullable();
            $table->geometry('max_extent')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('map_layer_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('maps')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('map_layer_groups')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('display_order')->default(0);
            $table->jsonb('properties')->nullable();
            $table->timestamps();
        });

        Schema::create('map_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('maps')->cascadeOnDelete();
            $table->foreignId('spatial_layer_id')->constrained('spatial_layers')->restrictOnDelete();
            $table->foreignId('layer_group_id')->nullable()->constrained('map_layer_groups')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('display_name')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->decimal('opacity', 4, 3)->default(1);
            $table->jsonb('style_config')->nullable();
            $table->jsonb('filter_config')->nullable();
            $table->jsonb('chart_config')->nullable();
            $table->smallInteger('min_zoom')->nullable();
            $table->smallInteger('max_zoom')->nullable();
            $table->timestamps();

            $table->unique(['map_id', 'spatial_layer_id']);
            $table->index(['map_id', 'display_order']);
            $table->check('opacity >= 0 AND opacity <= 1');
        });

        Schema::create('map_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('maps')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->jsonb('config_snapshot');
            $table->jsonb('layer_version_snapshot')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at');
            $table->timestamp('unpublished_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['map_id', 'revision']);
        });

        Schema::create('map_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_publication_id')->constrained('map_publications')->cascadeOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->bigInteger('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->string('qr_path')->nullable();
            $table->timestamps();
        });

        Schema::create('map_share_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_share_id')->constrained('map_shares')->cascadeOnDelete();
            $table->timestamp('accessed_at');
            $table->string('ip_hash', 128)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();
            $table->smallInteger('response_status')->nullable();

            $table->index(['map_share_id', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_share_accesses');
        Schema::dropIfExists('map_shares');
        Schema::dropIfExists('map_publications');
        Schema::dropIfExists('map_layers');
        Schema::dropIfExists('map_layer_groups');
        Schema::dropIfExists('maps');
    }
};
```

Model (`Map`, `MapLayer`, `MapPublication`, `MapShare`, `MapShareAccess`) mengikuti pola standar Eloquent — tidak ditulis penuh di sini karena tidak ada logika non-trivial di luar relasi FK biasa; buat lewat `php artisan make:model` mengikuti kolom di atas.

**Token share** dibuat dengan `Str::random(48)` lalu di-hash (`hash('sha256', $token)`) sebelum disimpan ke `token_hash` — token plaintext hanya ditampilkan sekali saat dibuat, sesuai `db-schema-v2.md` §"URL share dan QR code".

**Route publik baru:** `GET /maps/share/{token}` — validasi `token_hash`, `is_active`, `revoked_at`, `expires_at`, lalu render `map_publications.config_snapshot` milik `is_current=true`. `shared_maps` (lama) **tetap aktif tanpa perubahan** untuk share link yang sudah beredar — tidak ada migrasi data dari `shared_maps` ke `map_shares` (keduanya independen; `shared_maps` di-retire natural begitu semua `expired_at`-nya lewat, lihat Prioritas 8).

**Kriteria selesai:** user bisa membuat `maps` + `map_layers` dari UI (halaman baru, di luar cakupan kode di sini — kosmetik), publish jadi `map_publications`, generate `map_shares` + QR, buka link share tanpa login untuk publication `public` (Keputusan #5), revoke berfungsi (`revoked_at` diisi → akses berikutnya ditolak).

---

## Prioritas 4 — `administrative_regions`

`database/migrations/2026_10_04_090000_create_administrative_regions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_regions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Kode wilayah Kemendagri');
            $table->string('name');
            $table->string('level', 30); // provinsi | kabupaten_kota | kecamatan | (desa_kelurahan menyusul)
            $table->foreignId('parent_id')->nullable()->constrained('administrative_regions')->nullOnDelete();
            $table->geometry('geometry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('level');
        });

        DB::statement('CREATE INDEX administrative_regions_geometry_gist ON administrative_regions USING GIST (geometry)');

        Schema::table('spatial_layer_features', function (Blueprint $table) {
            $table->foreign('region_id')->references('id')->on('administrative_regions')->nullOnDelete();
        });

        Schema::create('spatial_layer_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spatial_layer_id')->constrained('spatial_layers')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('administrative_regions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['spatial_layer_id', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spatial_layer_regions');

        Schema::table('spatial_layer_features', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
        });

        Schema::dropIfExists('administrative_regions');
    }
};
```

Level awal: **provinsi + kabupaten/kota + kecamatan** (Keputusan #4) — tidak ada check constraint DB untuk `level` (mengikuti konvensi `project_progress_reports.status` yang divalidasi di aplikasi, supaya menambah level baru tidak butuh migration).

Command import (`app/Console/Commands/ImportAdministrativeRegions.php`) di-scaffold sebagai kerangka `belum diimplementasikan` sampai Keputusan #3 (sumber data resmi) dikunci — pola identik dengan `08-...md` Tahap 5.

**Kriteria selesai:** tabel + index dibuat, `spatial_layer_features.region_id`/`spatial_layer_regions` FK aktif; command import berstatus scaffold (boleh belum berfungsi penuh) sampai sumber data dikunci.

---

## Prioritas 5 — `development_projects` + Migrasi Bertahap `project_feedbacks`/`project_progress_reports`

Bergantung pada hasil verifikasi cardinality (Prasyarat 0 poin 1/Keputusan #6) untuk menentukan pemetaan `spatial_layer_features` (`data_type='proyek_strategis'`) → `development_projects`.

**Hasil verifikasi cardinality (2026-09-27, menjawab Prasyarat 0 poin 1 & Keputusan #6):** 121 baris `data_spatial` `data_type='proyek_strategis'`, 53 nilai `dbf_attributes.PAKET` berbeda dan **tidak ada satu pun PAKET yang dipakai lebih dari 1 baris** — cardinality "1 baris = 1 proyek" **terkonfirmasi benar**, bukan "1 proyek = banyak baris". Tapi ditemukan dua masalah kualitas data nyata yang mengubah migrasi di bawah:

1. **67 dari 121 baris (55%) tidak punya `PAKET` sama sekali** (`deskripsi` juga generik "Data tanpa nama") — proyek-proyek ini butuh fallback nama, bukan dibiarkan `NULL` di `development_projects.name` (kolom itu `NOT NULL`).
2. **`dbf_attributes.URUSAN` (teks nama dinas) tidak match persis dengan `opd.name` manapun** (mis. "Dinas Pekerjaan Umum dan Tata Ruang" vs `opd.name` asli "Dinas Pekerjaan Umum dan Penataan Ruang"; "Dinas Pendidikan dan Kebudayaan" vs "Dinas Pendidikan"), dan 67 baris `URUSAN`-nya `NULL`. Migration di bawah **direvisi** dari draf pertama dokumen ini: `owner_opd_id` dibuat **nullable** (bukan `COALESCE` ke OPD pertama sebagai tebakan, yang justru anti-pola "Owner layer salah hasil tebakan dari user" yang diperingatkan `db-schema-v2.md` sendiri), plus kolom `needs_review` untuk menandai baris yang butuh verifikasi manual Bappeda.

`database/migrations/2026_10_05_090000_create_development_projects_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('development_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('project_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('owner_opd_id')->nullable()->constrained('opd')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->nullOnDelete();
            $table->smallInteger('fiscal_year');
            $table->string('status', 40)->default('draft');
            $table->decimal('budget_amount', 20, 2)->nullable();
            $table->string('funding_source', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('needs_review')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('development_project_id')->constrained('development_projects')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('administrative_regions')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['development_project_id', 'region_id']);
        });

        Schema::create('project_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('development_project_id')->constrained('development_projects')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('geometry_type', 30);
            $table->geometry('geometry');
            $table->foreignId('region_id')->nullable()->constrained('administrative_regions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX project_locations_geometry_gist ON project_locations USING GIST (geometry)');

        // Backfill identitas proyek dari data_spatial data_type='proyek_strategis' — cardinality
        // 1 baris = 1 proyek terkonfirmasi (lihat "Hasil verifikasi cardinality" di atas: 121 baris,
        // 53 PAKET berbeda, tidak ada PAKET yang dipakai >1 baris). Nama proyek pakai fallback
        // berjenjang karena 55% baris tidak punya PAKET/deskripsi yang berarti. owner_opd_id
        // TIDAK ditebak — hanya diisi dari opd_pengelola_id yang sudah eksplisit ada di data_spatial;
        // sisanya NULL + needs_review=true, menunggu verifikasi manual Bappeda (URUSAN teks dari
        // dbf_attributes tidak match persis nama opd manapun, lihat catatan di atas).
        DB::statement("
            INSERT INTO development_projects
                (public_id, project_code, name, owner_opd_id, fiscal_year, status,
                 budget_amount, needs_review, created_at, updated_at)
            SELECT
                gen_random_uuid(),
                'LEGACY-' || ds.id,
                COALESCE(
                    NULLIF(ds.dbf_attributes->>'PAKET', ''),
                    NULLIF(ds.deskripsi, 'Data tanpa nama'),
                    'Proyek Tanpa Nama #' || ds.id
                ),
                ds.opd_pengelola_id,
                COALESCE(ds.tahun, EXTRACT(YEAR FROM now())::int),
                'berjalan',
                NULLIF(regexp_replace(ds.dbf_attributes->>'ANGGARAN', '[^0-9]', '', 'g'), '')::numeric,
                ds.opd_pengelola_id IS NULL,
                now(),
                now()
            FROM data_spatial ds
            WHERE ds.data_type = 'proyek_strategis'
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('project_locations');
        Schema::dropIfExists('project_regions');
        Schema::dropIfExists('development_projects');
    }
};
```

**Catatan kritis:** `owner_opd_id` sengaja dibiarkan `NULL` untuk 67 dari 121 baris legacy (bukan ditebak dari `URUSAN` yang tidak match persis nama `opd` manapun) — `needs_review=true` menandai baris ini untuk verifikasi manual Bappeda sebelum proyek dianggap punya data lengkap. Ini sesuai prinsip `db-schema-v2.md` §10 "Owner layer salah hasil tebakan dari user": lebih baik `NULL` + flag daripada tebakan yang terlihat meyakinkan tapi salah.

`project_feedbacks.development_project_id`/`project_progress_reports.development_project_id` ditambahkan sebagai kolom nullable terpisah (migration kedua, tidak digabung di atas supaya bisa di-rollback independen), diisi lewat mapping `LEGACY-{id}` di atas. Kolom teks lama (`nama_proyek`, `kabupaten_kota`) **tidak dihapus** — tetap snapshot historis sesuai `db-schema-v2.md` §5.5.

**Kriteria selesai:** setiap baris `data_spatial` lama `data_type='proyek_strategis'` punya padanan `development_projects`; baris tanpa `opd_pengelola_id` ditandai untuk review; `project_feedbacks`/`project_progress_reports` baru bisa FK ke `development_projects` selain ke `data_spatial`/`spatial_layer_features` lama.

---

## Prioritas 6 — Dashboard Eksekutif

**Tidak ada migration baru.** Query agregasi langsung di atas `development_projects` + `project_progress_reports` + `administrative_regions` (join by `project_regions`), difilter `sector_id`/`owner_opd_id`/`fiscal_year`/`region_id`. Controller baru (mis. `ExecutiveDashboardController`) dengan action per kartu ringkasan (Total Proyek, Proyek Berjalan, Realisasi Anggaran, Progres Fisik — lihat struktur di `Rekomendasi_Pengelompokan_Layer_MARIMOI.md` §20, sekarang dirujuk lewat `db-schema-v2.md`).

**Jangan** query `spatial_layer_features.attributes` (JSONB) untuk angka ini — semua input kartu berasal dari kolom terstruktur `development_projects`/`project_progress_reports`. Bila performa jadi masalah pada data realistis, baru pertimbangkan materialized view/snapshot dengan `calculated_at` (bukan langkah pertama).

**Kriteria selesai:** dashboard bisa difilter wilayah/sektor/OPD/tahun, setiap angka bisa ditelusuri balik ke baris sumber (bukan angka final tanpa jejak).

---

## Prioritas 7 (Ditunda) — `analysis_layers`/`analysis_results`

Tidak dieksekusi sampai rumus/metodologi dikunci (Keputusan produk terpisah, di luar cakupan dokumen ini) **dan** Prioritas 4+5 (wilayah + proyek) sudah berdiri sebagai input. Kerangka migration/model sudah pernah didesain di `08-kategori-peta-dinamis-dan-metadata-layer.md` Tahap 7 dan tetap valid sebagai starting point bila keputusan itu dikunci — cukup sesuaikan FK `wilayah_id` menjadi `region_id` mengikuti penamaan final di dokumen ini.

---

## Prioritas 8 — Retirement `categories`/`data_spatial`/`shared_maps`

Dikerjakan **setelah** Prioritas 1–3 tuntas dan tidak ada kode baru yang membaca tabel legacy:

1. Audit ulang ±15 file checklist Prioritas 0 — pastikan semua sudah pindah ke `SpatialLayer`/`SpatialLayerFeature`.
2. Tandai `categories`/`data_spatial`/`shared_maps` sebagai `@deprecated` di PHPDoc model, tanpa menghapus tabel (data lama tetap ada untuk audit historis).
3. Setelah periode observasi (rekomendasi: 1 rilis penuh tanpa insiden), evaluasi apakah tabel fisik perlu di-drop atau cukup dibiarkan read-only selamanya sebagai arsip — ini keputusan operasional terpisah, bukan bagian *definition of done* migrasi struktural.

**Jangan** jalankan langkah ini sebelum checklist ±15 file benar-benar tuntas — mematikan compatibility source prematur akan mengulang persis risiko yang diperingatkan `04-marimoi-x-goat.md` (kini bagian sejarah `db-schema-v2.md`) §"Risiko Migrasi": *"Pemindahan `data_spatial` merusak endpoint lama."*

---

## Testing

Ikuti `phpunit/core rules`: test per prioritas, `php artisan test --compact --filter=...` setelah tiap prioritas, sebelum lanjut ke berikutnya.

| Prioritas | File test (baru) | Skenario minimal |
| --- | --- | --- |
| 1 | `MapTypeTest`, `SpatialLayerTest` | CRUD `map_types`; backfill `spatial_layers` dari `categories` mempertahankan jumlah baris (211) dan hierarki `parent_id`; `is_group`/`atribut_schema` berfungsi seperti dirancang di `db-schema-v2.md` |
| 2 | `SectorTest`, `SpatialLayerFeatureBackfillTest`, `SpatialLayerFeatureInterventionTest` | job backfill tidak OOM untuk dataset besar (assert memory delta, pola sama seperti fix `getAvailableProjects` sesi sebelumnya); pasangan intervensi unik |
| 3 | `MapSharingTest` | draft→publish→share→akses publik→revoke, urut lengkap; token tidak bisa dipakai setelah `revoked_at`/`expires_at` lewat |
| 4 | `AdministrativeRegionTest` | hierarki `parent_id` benar; `spatial_layer_features.region_id` `SET NULL` saat wilayah dihapus |
| 5 | `DevelopmentProjectBackfillTest` | 121 baris `proyek_strategis` menghasilkan 121 `development_projects` (bukan dedup — cardinality 1:1 terkonfirmasi); baris tanpa `opd_pengelola_id` (67 baris) punya `owner_opd_id=NULL` + `needs_review=true`, bukan tebakan; nama fallback terisi untuk baris tanpa `PAKET` |
| 6 | `ExecutiveDashboardTest` | filter wilayah/sektor/OPD/tahun menghasilkan angka yang bisa ditelusuri ke baris sumber |

Regresi wajib setelah Prioritas 1–2 (menyentuh jalur yang dipakai banyak test existing): `FrontendPagesTest`, `BackendTematikOnlyTest`, `PetaRestructuringTest` (bila ada dari sesi sebelumnya), `RolePermissionTest`.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Backfill `development_projects.owner_opd_id` menebak OPD yang salah | Tidak ditebak — `NULL` + `needs_review=true` untuk baris tanpa `opd_pengelola_id` eksplisit (67/121 baris), menunggu verifikasi manual Bappeda (lihat catatan kritis Prioritas 5) |
| Job `BackfillSpatialLayerFeatures` gagal di tengah jalan untuk dataset besar | `chunkById` + idempotensi lewat `legacy_data_spatial_id` unique per baris — job bisa di-retry tanpa duplikasi jika ditambah guard `whereDoesntHave` sebelum insert |
| Dua sistem katalog (`categories` lama dan `spatial_layers` baru) berjalan paralel tanpa batas waktu | Prioritas 8 punya tenggat eksplisit (checklist ±15 file) — jangan biarkan ini jadi status permanen |
| `map_shares.token_hash` predictable bila generator lemah | Wajib `Str::random(48)` atau setara (kriptografis), bukan `Str::slug`/incrementing ID |
| Migration `spatial_layer_metadata` mengambil baris `data_spatial` yang salah sebagai representasi metadata layer (bila cardinality ternyata tidak seragam) | Prasyarat 0 poin 1 (verifikasi cardinality) wajib selesai dulu — backfill metadata memakai `DISTINCT ON` baris pertama hanya valid bila hasil verifikasi mengonfirmasi keseragaman |
| `administrative_regions` dibuat tanpa data (Keputusan #3 belum dikunci) | Command import di-scaffold status "belum diimplementasikan" secara eksplisit — jangan dipaksakan isi data dummy |

## Kriteria Selesai (Keseluruhan)

Status sebenarnya per 2026-09-27 — lihat [Rekap Hasil Implementasi](#rekap-hasil-implementasi-2026-09-27) untuk detail lengkap tiap poin.

- [x] Prioritas 0: laporan cardinality `data_spatial` tersedia (nyata, lewat query live). Keputusan #1–#7 dijalankan pakai rekomendasi default, **bukan** sign-off tertulis resmi terpisah. **Backup database tidak dilakukan** — gap yang harus diperbaiki sebelum pola kerja ini diulang ke staging/production.
- [x] Prioritas 1: `map_types` + `spatial_layers` + `spatial_layer_metadata` live, backfill 211/211 baris `categories` terverifikasi 100%. ±15 file: baru 2 (`CategoryController`, `DataSpatialController`, itu pun baru validatornya) yang bermigrasi.
- [x] Prioritas 2: `sectors` + `spatial_layer_features` (11.927/11.927 baris, lewat `INSERT...SELECT` langsung — bukan job seperti rencana) + `spatial_layer_feature_interventions` live.
- [x] Prioritas 3: `maps`/`map_layers`/`map_publications`/`map_shares`/`map_share_accesses` live, alur draft→publish→share→revoke berfungsi end-to-end (diuji lewat model/test). Endpoint HTTP publik/UI **belum dibuat**, sengaja ditahan.
- [x] Prioritas 4: `administrative_regions` (provinsi/kab-kota/kecamatan) + `spatial_layer_regions` live; **data Provinsi Maluku Utara terisi penuh (129 baris, kode BPS+Kemendagri)**. Command import shapefile/batas geometri tetap scaffold — itu bagian yang masih menunggu sumber resmi, bukan kode wilayahnya.
- [x] Prioritas 5: `development_projects` + `project_regions`/`project_locations` live, 121 baris proyek strategis lama ter-backfill 1:1. **Seluruh 121 baris (100%, bukan 67 seperti dugaan rencana)** bertanda `needs_review=true` (bukan ditebak).
- [x] Prioritas 6: dashboard eksekutif (`ExecutiveDashboardController`, terpisah dari dashboard lama) menampilkan kartu ringkasan dari data terstruktur, bisa difilter dan ditelusuri.
- [ ] Prioritas 7: tetap ditunda, tidak dieksekusi di iterasi ini (sesuai keputusan produk) — tidak berubah.
- [ ] Prioritas 8: **belum bisa** — checklist ±15 file baru sebagian kecil selesai (audit ulang mengonfirmasi `Category`/`DataSpatial` masih dipakai penuh di ±10 file + 5 model duplikat + `MapDataVersion`). Yang sudah dikerjakan: kedua model ditandai `@deprecated` (murni dokumentasi, tidak mengubah perilaku).
- [x] Seluruh test baru per prioritas + regresi existing lulus (219 passed, 8 failed pra-existing tidak terkait); `vendor/bin/pint --dirty --format agent` bersih di setiap prioritas.
