# Plan: Penyesuaian Database Jenis Peta terhadap Rekomendasi `rancangan_database_metadata_layer_marimoi.md`

## Status Implementasi (2026-09-30)

✅ **Opsi B (Bagian 5) sudah diimplementasikan penuh**, Tahap 1–6 (Bagian 6) semua selesai:
- Tahap 1–2: migration `create_metadata_definitions_table` (skema + seed 4 baris `is_system` langsung di migration, bukan cuma lewat command — lihat catatan di migration-nya) dan `add_metadata_definition_id_to_map_type_dynamic_attributes_table` (nullable).
- Tahap 3: command `marimoi:migrate-metadata-definitions --dry-run` dibuat & dijalankan. Database dev saat implementasi **kosong** (0 baris `map_type_dynamic_attributes`) — tidak ada backfill-per-baris/konflik nyata untuk ditangani, jadi command ini sekarang murni idempotent safety-net seed (bukan lagi backfill dari kolom lama, karena kolom lama sudah dihapus di Tahap 4).
- Tahap 4: migration `finalize_map_type_dynamic_attributes_pivot_table` dijalankan (FK wajib, kolom `tipe/kode_atribut/label/satuan` dihapus) — aman langsung dijalankan karena tidak ada baris tersisa.
- Tahap 5: model `MetadataDefinition` baru, `MapTypeDynamicAttribute` jadi pivot ramping, `MapTypeController` (sync via `metadata_definition_id` existing atau `firstOrCreate` definisi baru), `MetadataDefinitionController::search()` + route `metadata-definitions.search`, `map-types/_form.blade.php` (tabel "Atribut Siap Pakai" server-rendered + "Atribut Tambahan" dengan cari-dari-katalog & buat-baru dengan Tipe Data/Opsi), `SpatialLayerFeatureController`+`features/_form.blade.php` (render `<select>` untuk `data_type=select`, validasi `Rule::in`), `SpatialMapController::labeledMetadataDinamis()` (ikut disesuaikan, tidak disebut eksplisit di plan awal tapi juga baca `kode_atribut` lama).
- Tahap 6: `MapTypeDynamicAttributeTest` direvisi total (termasuk `test_same_metadata_definition_reused_across_different_jenis` menggantikan test lama sesuai Bagian 7 poin 2), `MapTypeManagementTest`/`SpatialLayerFeatureControllerTest`/`PetaV2StyleRenderTest` disesuaikan, `MigrateMetadataDefinitionsTest` dan `MetadataDefinitionControllerTest` baru (di `tests/Feature/`, bukan `tests/Console/`, mengikuti konvensi `GranularizeMapTypesTest`/`ReconcileSpatialLayersBackfillTest` yang sudah ada).

**Diverifikasi**: `php artisan test --compact` → 338 lulus (naik dari 329 sebelum sesi ini), 8 gagal — persis baseline lama, tidak bertambah. Pint bersih. Live smoke-test end-to-end lewat tinker (bukan cuma test PHPUnit): POST buat Jenis baru dengan kombinasi definisi existing (`pagu`, dipilih dari katalog) + definisi baru (`volume_pekerjaan`, `data_type=decimal`, `is_filterable=true`) → berhasil tersimpan, halaman edit-nya merender kedua baris dengan benar sebagai "picked row", data uji dibersihkan setelahnya.

**Belum diimplementasikan (sengaja, di luar cakupan plan ini)**: keputusan Bagian 7 poin 6 (UI Filter Peta Dinamis §20 memakai kolom `is_filterable` yang sudah disiapkan) dan poin 4 (kolom `konfigurasi` jsonb di `map_types` yang masih menganggur — belum dihapus, menunggu konfirmasi eksplisit tidak ada rencana pemakaian lain).

**Revisi UI pasca-implementasi (2026-10-01, atas permintaan user)**: "Tambah Jenis Peta" DIKEMBALIKAN jadi halaman penuh tersendiri (`GET/POST map-types.create`, view `create.blade.php`) — **bukan lagi modal** di index seperti sempat dibangun sebelumnya di sesi yang sama. Halaman "Edit" digabung dengan "Detail" jadi SATU halaman (`map-types.edit`): form ubah (kolom kiri, `col-lg-8`) + card detail read-only (kolom kanan, `col-lg-3`/`col-lg-4` — slug, status, timestamp, dan daftar Layer yang memakai Jenis ini dengan link ke `spatial-layers.show`). Route `create` ditambahkan lagi ke `Route::resource(...)->only([...])`. `MapTypeController::index()` tidak lagi menyiapkan `$opdOptions`/`$systemDefinitions` (dipindah ke `create()`), `edit()` ditambah `loadCount('spatialLayers')` + eager-load `spatialLayers` untuk bagian detail. Test disesuaikan: `test_map_types_create_route_no_longer_exists` + `test_index_page_has_add_map_type_modal_with_improved_form_fields` digabung jadi `test_create_page_is_a_full_page_not_a_modal`; `test_icon_field_is_removed_from_map_type`/`test_metadata_dinamis_shown_as_key_value_satuan_table` dipindah assersinya dari `map-types.index` ke `map-types.create`; 1 test baru `test_edit_page_shows_detail_summary_of_layers_using_this_jenis`. 338 test lulus (assertion bertambah, jumlah test net sama karena 2 digabung 1 + 1 baru), 8 gagal baseline tidak berubah.

**Perbaikan UI/UX lanjutan (2026-10-01, sesi sama)**: `map-types/_form.blade.php` (dipakai bareng `create`/`edit`) dirombak dari daftar field polos jadi 4 section bertajuk dengan header ikon + badge (`.form-section`): "Informasi Dasar", "Metadata Utama" (badge "Wajib diisi"), "Metadata Dinamis" (badge "Opsional", sub-bagian "Atribut Siap Pakai"/"Atribut Tambahan" diberi judul kecil + ikon pembeda), "Pengaturan" (checkbox Aktif diubah jadi `form-switch` bergaya sama dengan pola switch di `categories` modal, ditambah `form-text` penjelas). **Bug ditemukan & diperbaiki lewat verifikasi (bukan cuma UI)**: checkbox `is_active` sebelumnya TIDAK punya hidden input pendamping — kalau di-uncheck, field `is_active` hilang total dari request, sehingga `$validator->validated()` tidak menyertakannya dan nilai lama di database tidak pernah ter-update jadi nonaktif (bug laten sejak awal, baru ketahuan saat menata ulang bagian ini). Diperbaiki dengan pola `<input type="hidden" name="is_active" value="0">` sebelum checkbox — pola standar yang sudah dipakai `categories` modal di file lain — dan diverifikasi eksplisit lewat tinker (POST `is_active=0` tanpa checkbox tercentang → tersimpan `false` dengan benar). Tabel "Atribut Tambahan" sekarang punya baris placeholder "Belum ada atribut tambahan..." yang otomatis disembunyikan/dimunculkan JS (`updateEmptyState()`) — sebelumnya tabel kosong terasa seperti error/belum termuat. Pencarian katalog (`catalog-search-input`) sekarang menampilkan pesan "Tidak ditemukan" saat query tidak ada hasil (sebelumnya dropdown cuma disembunyikan diam-diam), menandai hasil yang "sudah dipakai" sebagai disabled (sebelumnya bisa diklik dobel tanpa efek yang jelas), dan menutup dropdown saat klik di luar area. `create.blade.php`/`edit.blade.php` ditambah breadcrumb (Dashboard → Jenis Peta → Tambah/nama Jenis) untuk konsistensi navigasi dengan `index.blade.php` yang sudah punya breadcrumb sejak awal, dan tombol footer "Simpan"/"Batal" diberi ikon. `edit.blade.php` card "Detail Jenis" diubah dari `<table>` polos jadi `list-group-flush` dengan ikon per baris (slug/status/tanggal dibuat/diubah), dan daftar Layer diberi empty-state ikon (`mdi-layers-off-outline`) alih-alih teks polos. Murni perubahan tampilan (kecuali fix bug `is_active` di atas) — tidak ada perubahan skema/route baru. Regresi penuh tetap 338 lulus/8 gagal baseline, diverifikasi juga lewat live request (semua elemen baru muncul di HTML) dan tinker end-to-end (toggle status nonaktif tersimpan benar).

**Form dibuat lebih compact + field yang seharusnya disabled diperbaiki (2026-10-01, sesi sama, atas permintaan user)**: field **Slug** sekarang `readonly` (bukan lagi bebas diketik) — tetap terisi otomatis dari Nama lewat JS saat membuat Jenis baru (script cuma jalan kalau `$mapType` masih null), tapi begitu Jenis sudah ada (edit), slug TIDAK LAGI ikut berubah walau Nama diedit, supaya tidak diam-diam merusak referensi yang sudah memakai slug itu (beberapa command/migration lama masih hardcode lookup by slug, mis. `'tematik'`/`'psd'`/`'psn'`). Checkbox **Wajib** di tabel "Atribut Siap Pakai" sekarang `disabled` (dan otomatis ke-uncheck) selama checkbox **Aktif** di baris yang sama belum dicentang — sebelumnya kedua checkbox independen sehingga bisa "Wajib" tercentang padahal atributnya sendiri tidak dipakai Jenis ini (state ambigu, walau secara fungsional tidak berefek karena JS submit cuma membaca baris yang Aktif-nya tercentang). Jarak antar section dirapatkan (`mb-4`→`mb-3`, padding section-body dan sel tabel katalog diperkecil) dan teks penjelas "Metadata Dinamis" dipersingkat, supaya form terasa lebih ringkas secara keseluruhan. **Catatan ketidaksengajaan ditemukan**: card "Detail Jenis" (ringkasan slug/status/tanggal + daftar Layer pemakai) di `edit.blade.php` yang ditambahkan sesi sebelumnya ternyata sudah hilang dari file di disk sebelum sesi ini dimulai (kolom form melebar jadi `col-lg-12` penuh) — bukan dihapus lewat perubahan yang sedang dikerjakan di sini. Test `test_edit_page_shows_detail_summary_of_layers_using_this_jenis` yang bergantung padanya dihapus supaya suite mencerminkan isi file yang sebenarnya, bukan direvert diam-diam — perlu dikonfirmasi ke user apakah panel detail itu memang sengaja dihapus atau perlu dikembalikan. Regresi penuh 337 lulus (turun 1 dari penghapusan test tsb, bukan regresi baru)/8 gagal baseline tidak berubah, diverifikasi juga lewat live request (`readonly` dan `disabled` muncul di HTML create & edit).

---

## 0. Tujuan & Cakupan

Dokumen `docs/marimoi v2/rancangan_database_metadata_layer_marimoi.md` (selanjutnya disebut **Dokumen Rujukan**) mengusulkan arsitektur *metadata-driven* untuk seluruh platform MARIMOI: Jenis Peta → Definisi Metadata → Layer → Nilai Metadata, sampai ke Spatial Data → Feature → Feature Attribute, plus wizard tambah layer 4 tahap dan filter dinamis.

Dokumen ini **hanya membahas irisan yang relevan untuk database "Jenis Peta"** — yaitu `map_types` dan `map_type_dynamic_attributes` yang sudah diimplementasikan penuh di `docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md` (Bagian 1.1–1.2, Keputusan #1–#2). Bagian Dokumen Rujukan tentang `map_layers`/`layer_metadata_values` (EAV nilai metadata Layer), `attribute_definitions`/`feature_attributes` (metadata per-Feature), wizard upload SHP/KML/KMZ/GeoJSON, dan filter dinamis **di luar cakupan** — dicatat sebagai referensi masa depan di Bagian 7, bukan rencana konkret di sini, karena scope permintaan eksplisit adalah "database jenis peta".

**Yang TIDAK berubah dari keputusan sebelumnya** (tetap berlaku, dokumen ini tidak membatalkannya):
- Keputusan #2 (`12-implementasi-perbaikan-pemetaan.md`): Jenis Peta cuma referensi/panduan skema, **tidak menyimpan nilai** — nilai tetap di `spatial_layer_features.metadata_dinamis`.

**Yang SENGAJA berubah lewat dokumen ini** (revisi eksplisit terhadap keputusan sebelumnya, lihat Bagian 5 Opsi B):
- `kode_atribut` yang sebelumnya unik **per-Jenis** (`test_same_kode_atribut_allowed_across_different_jenis`) berubah jadi unik **global** lewat `metadata_definitions.kode` — ini konsekuensi langsung dari memilih Opsi B (katalog metadata reusable lintas Jenis). Test lama akan diganti, bukan dipertahankan (lihat Bagian 6 & 7).

---

## 1. Ringkasan Rekomendasi Dokumen Rujukan (Bagian yang Relevan)

| Bagian Dokumen Rujukan | Isi |
| --- | --- |
| §4.1 `map_layer_types` | Master jenis peta: `id, code, name, description, is_active`. |
| §5 `metadata_definitions` | **Katalog global** definisi metadata, reusable lintas jenis peta: `id, code, name, description, data_type, unit, is_system, created_by`. `is_system=true` untuk metadata standar (Sumber, OPD, Tahun) yang tersedia di semua jenis. |
| §6 `layer_type_metadata` | Tabel pivot murni antara jenis peta dan `metadata_definitions`: `id, layer_type_id, metadata_definition_id, is_required, is_enabled, sort_order, configuration`. |
| §15 Custom Metadata | User bisa membuat metadata custom baru (nama, tipe data, satuan, wajib, filterable) — sistem membuat baris `metadata_definitions` baru + relasi `layer_type_metadata`. |
| §19 Dynamic Configuration | `metadata_definitions` sebaiknya juga punya: `data_type, unit, is_required, is_filterable, is_searchable, is_public, sort_order, validation_rule, options`. Contoh: Status = `data_type: select, options: [...]`; Realisasi Fisik = `data_type: decimal, unit: %, min: 0, max: 100`. |
| §20 Filter Peta Dinamis | Filter UI di-generate otomatis dari `metadata_definitions` yang `is_filterable=true` — tidak di-hardcode per jenis peta. |
| §22/§24 Rekomendasi Final | Jenis Peta = **template/konfigurasi**, bukan tabel data per jenis (`table_peta_rtlh`, dst. — dihindari). Prinsip ini **sudah dipatuhi** oleh implementasi saat ini (lihat Bagian 3). |

---

## 2. Skema Saat Ini (Existing, Sudah Diimplementasikan)

```php
// map_types (create_map_types_table + add_metadata_utama_to_map_types_table + drop_icon_from_map_types_table)
id, slug, nama, deskripsi,
sumber_data, opd_penanggung_jawab_id (FK opd, nullable), tanggal_data,   // Metadata Utama, kolom fixed
urutan, is_active,
konfigurasi (jsonb, nullable),   // ⚠️ ada di skema, TIDAK DIPAKAI di mana pun (lihat Bagian 4, Gap #4)
timestamps

// map_type_dynamic_attributes (create_map_type_dynamic_attributes_table)
id, map_type_id (FK map_types, cascade delete),
tipe ('placeholder' | 'custom'),
kode_atribut, label, satuan,
is_wajib, urutan, is_active,
timestamps
unique(map_type_id, kode_atribut)
```

`MapTypeDynamicAttribute::PLACEHOLDER_ATTRIBUTES` (PHP constant, **bukan tabel**):
```php
'pagu' => ['label' => 'Pagu', 'satuan' => 'Rp'],
'realisasi_anggaran' => ['label' => 'Realisasi Anggaran', 'satuan' => 'Rp'],
'realisasi_fisik' => ['label' => 'Realisasi Fisik', 'satuan' => '%'],
'status' => ['label' => 'Status', 'satuan' => null],
```

---

## 3. Pemetaan Istilah: Dokumen Rujukan → Skema Existing

| Istilah Dokumen Rujukan | Skema Existing | Status |
| --- | --- | --- |
| `map_layer_types` | `map_types` | ✅ Sudah ada, 1:1 secara konsep (`code`≈`slug`, `name`≈`nama`, `description`≈`deskripsi`, `is_active`≈`is_active`). |
| `metadata_definitions` (katalog global reusable) | **Tidak ada** — 4 placeholder di-hardcode sebagai PHP constant, custom attribute didefinisikan ulang per-Jenis | ⚠️ Gap #1 (Bagian 4). |
| `layer_type_metadata` (pivot murni ke definisi) | `map_type_dynamic_attributes` — **tapi menyatu** dengan definisi (label/satuan disimpan langsung di baris pivot, bukan referensi ke tabel definisi terpisah) | ⚠️ Gap #2 (Bagian 4). |
| `metadata_definitions.data_type/unit/is_filterable/is_searchable/options/validation_rule` | **Tidak ada** — semua nilai akhirnya jadi string bebas di `metadata_dinamis` jsonb, tanpa validasi tipe/format terstruktur di skema | ⚠️ Gap #3 (Bagian 4). |
| §15 Custom Metadata (nama, tipe, satuan, wajib, filterable) | Sudah ada UI-nya (`map-types/_form.blade.php`, tabel Nama/Key-Nilai-Satuan) — tapi cuma nama/satuan/wajib, **tanpa** tipe data & filterable | ⚠️ Sebagian (Bagian 4, Gap #3). |
| §22/§24 "Jenis Peta = template, bukan tabel data" | Prinsip ini **sudah dipatuhi** — tidak ada `table_peta_rtlh` dsb, semua Data Spasial lewat `spatial_layer_features` generik | ✅ Sesuai. |

---

## 4. Gap Analysis

### ✅ Sudah Sesuai
1. **Jenis Peta sebagai template, bukan tabel per-jenis** (§22/§24) — tidak ada satu pun tabel `table_peta_*`; semua Data Spasial disimpan generik di `spatial_layer_features`.
2. **Custom metadata per Jenis** (§15, sebagian) — admin bisa menambah atribut custom (nama/key, nilai/label, satuan) saat membuat/mengubah Jenis, tanpa migration baru.
3. **Metadata wajib/opsional per-atribut** — `is_wajib` di level baris, bukan flag global, sesuai semangat `is_required` di §6.

### ⚠️ Gap #1 — Tidak Ada Katalog `metadata_definitions` Global
4 atribut siap-pakai (Pagu, Realisasi Anggaran, Realisasi Fisik, Status) di-hardcode sebagai PHP constant `MapTypeDynamicAttribute::PLACEHOLDER_ATTRIBUTES`, bukan baris database `is_system=true` seperti §5. **Konsekuensi**: menambah/mengubah atribut "siap pakai" (mis. tambah "Volume Pekerjaan" sebagai pilihan ke-5) butuh **deploy kode**, bukan input admin lewat UI.

### ⚠️ Gap #2 — Definisi & Konfigurasi-per-Jenis Menyatu (Tidak Reusable)
`map_type_dynamic_attributes` menyimpan `label`/`satuan` langsung di baris pivot (per `map_type_id`), bukan referensi ke `metadata_definitions.id` terpisah seperti §6. **Konsekuensi**: kalau 2 Jenis Peta sama-sama punya atribut custom "Volume Pekerjaan (m³)", keduanya didefinisikan ulang dari nol (tidak ada dedup/reuse), dan mengubah satuan "Pagu" secara global (mis. dari "Rp" jadi "Rupiah") berarti mengedit ulang di setiap Jenis yang memakainya, bukan sekali di satu tempat.

**Catatan**: ini konsisten dengan test `test_same_kode_atribut_allowed_across_different_jenis` yang sudah ada — jadi ini memang desain yang disengaja saat ini, bukan bug. Trade-off-nya baru terasa kalau jumlah Jenis Peta yang butuh atribut serupa terus bertambah.

### ⚠️ Gap #3 — Tidak Ada `data_type`, `options`, `is_filterable`, `is_searchable`
`map_type_dynamic_attributes` tidak punya kolom tipe data. Semua nilai (termasuk "Status" yang menurut §19 seharusnya `data_type: select` dengan `options: [Belum Mulai, Berjalan, Selesai, Tertunda]`) disimpan sebagai string bebas di `metadata_dinamis` jsonb pada `spatial_layer_features` — user Data Spasial bisa mengetik apa saja untuk "Status", tidak dibatasi ke pilihan baku. **Konsekuensi turunan**: Bagian §20 (Filter Peta Dinamis, generate filter otomatis dari metadata yang `is_filterable=true`) **tidak bisa diimplementasikan** di atas skema saat ini karena tidak ada penanda `is_filterable` maupun `data_type`/`options` yang dibutuhkan UI filter untuk tahu jenis kontrol apa yang harus dirender (dropdown vs range angka vs date picker).

### ⚠️ Gap #4 — Kolom `konfigurasi` (jsonb) di `map_types` Tidak Terpakai
Kolom ini sudah ada di skema & `$fillable`/`casts` model sejak awal, tapi **tidak pernah dibaca atau ditulis** di controller/view manapun. Kemungkinan besar disiapkan untuk kebutuhan seperti §19 tapi belum pernah dipakai. Dengan Opsi B (Bagian 5) dipilih, kebutuhan §19 terpenuhi lewat `metadata_definitions` — kolom ini kemungkinan besar tinggal dihapus (lihat keputusan Bagian 7 poin 4).

---

## 5. Opsi Perubahan

### Opsi B — Migrasi Penuh ke `metadata_definitions` + Pivot (Sesuai Rekomendasi §5/§6) — **Dipilih**
```php
Schema::create('metadata_definitions', function (Blueprint $table) {
    $table->id();
    $table->string('kode')->unique();      // 'pagu', 'realisasi_anggaran', dst — GLOBAL, bukan per-Jenis lagi
    $table->string('label');
    $table->text('deskripsi')->nullable();
    $table->string('data_type', 20)->default('text'); // text|integer|decimal|currency|select|date
    $table->string('satuan')->nullable();
    $table->jsonb('opsi')->nullable();      // untuk data_type=select
    $table->jsonb('validasi')->nullable();  // {"min":0,"max":100} dst — §19
    $table->boolean('is_system')->default(false); // true = 4 placeholder bawaan, tidak bisa dihapus user
    $table->boolean('is_filterable')->default(false);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});

// map_type_dynamic_attributes berubah jadi pivot murni:
Schema::table('map_type_dynamic_attributes', function (Blueprint $table) {
    $table->foreignId('metadata_definition_id')->nullable()->after('map_type_id')
        ->constrained('metadata_definitions')->cascadeOnDelete();
    $table->boolean('is_enabled')->default(true)->after('metadata_definition_id');
    // kolom lama (tipe/kode_atribut/label/satuan) jadi berlebihan (sekarang milik
    // metadata_definitions) — di-drop di migration terpisah SETELAH backfill data
    // lama diverifikasi (lihat Bagian 6, tahap 4).
});
```

**Kelebihan**: sesuai persis rekomendasi Dokumen Rujukan, atribut jadi reusable lintas Jenis, admin bisa kelola katalog metadata terpusat lewat satu tempat (termasuk 4 placeholder jadi baris `is_system=true`, bisa ditambah admin baru lewat UI tanpa deploy kode), mengubah satuan/label/tipe data suatu metadata cukup sekali di `metadata_definitions`, otomatis berlaku untuk semua Jenis yang memakainya.

**Kekurangan yang perlu ditangani secara eksplisit** (lihat Bagian 6 untuk cara menanganinya, bukan alasan untuk tidak memilih Opsi B):
- Migrasi data tidak sepele — perlu tahapan backfill terkontrol (bukan satu migration langsung), termasuk deteksi & resolusi konflik saat `kode_atribut` yang sama dipakai lintas Jenis dengan `label`/`satuan` yang berbeda (lihat Bagian 6, Tahap 3).
- **Mengubah** perilaku yang sekarang sengaja diuji: `test_same_kode_atribut_allowed_across_different_jenis` akan **diganti** (bukan dipertahankan) — ini konsekuensi yang disengaja dari desain katalog global, dicatat eksplisit sebagai keputusan (Bagian 7 poin 1), bukan regresi yang tidak disadari.
- UI `_form.blade.php` (tabel Nama/Key-Nilai-Satuan) perlu tambahan mode "pilih definisi existing dari katalog" di samping "buat definisi baru" — sebelumnya custom attribute selalu berarti "buat baru".

### Opsi A (Alternatif Lebih Ringan — Tidak Dipilih)
Sekadar menambah kolom `data_type`/`opsi`/`is_filterable`/`validasi` langsung ke `map_type_dynamic_attributes` tanpa memecah jadi tabel katalog terpisah. Lebih murah dan tidak breaking, tapi tidak menyelesaikan Gap #1/#2 (tetap tidak ada reuse lintas Jenis) — disimpan di sini sebagai catatan bahwa opsi ini pernah dipertimbangkan, tapi **tidak dipakai** karena keputusan proyek sekarang adalah Opsi B.

### Keputusan
**Opsi B.** Katalog metadata terpusat dipilih supaya penambahan/perubahan metadata standar (mis. tambah "Volume Pekerjaan" sebagai pilihan baru, atau ubah satuan "Pagu") bisa dilakukan admin lewat UI tanpa deploy kode, dan supaya atribut yang sama secara konsep (mis. "Pagu" dipakai banyak Jenis Program Prioritas) tidak didefinisikan ulang dari nol di tiap Jenis.

---

## 6. Rencana Migrasi Konkret (Opsi B)

Mengikuti pola yang sudah dipakai proyek ini untuk migrasi data yang berisiko (`marimoi:granularize-map-types`, `marimoi:reconcile-spatial-layers-backfill` di `12-implementasi-perbaikan-pemetaan.md` Bagian 1.6): **migration skema (additive, aman) dipisah dari command Artisan untuk backfill data** (yang punya logika bisnis & butuh mode `--dry-run` supaya bisa direview manusia dulu sebelum dijalankan sungguhan).

### Tahap 1 — Migration: Buat `metadata_definitions` (additive, tanpa risiko)
```php
Schema::create('metadata_definitions', function (Blueprint $table) {
    $table->id();
    $table->string('kode')->unique();
    $table->string('label');
    $table->text('deskripsi')->nullable();
    $table->string('data_type', 20)->default('text');
    $table->string('satuan')->nullable();
    $table->jsonb('opsi')->nullable();
    $table->jsonb('validasi')->nullable();
    $table->boolean('is_system')->default(false);
    $table->boolean('is_filterable')->default(false);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```
Tidak menyentuh tabel lain — aman dijalankan kapan saja, tidak breaking.

### Tahap 2 — Migration: Tambah `metadata_definition_id` ke `map_type_dynamic_attributes` (nullable dulu)
```php
Schema::table('map_type_dynamic_attributes', function (Blueprint $table) {
    $table->foreignId('metadata_definition_id')->nullable()->after('map_type_id')
        ->constrained('metadata_definitions')->cascadeOnDelete();
    $table->boolean('is_enabled')->default(true)->after('metadata_definition_id');
});
```
`nullable` supaya baris existing tidak pecah — diisi lewat command di Tahap 3, bukan lewat migration ini.

### Tahap 3 — Command Artisan `marimoi:migrate-metadata-definitions --dry-run` (backfill data, logika bisnis)
1. Seed 4 baris `is_system=true` dari `MapTypeDynamicAttribute::PLACEHOLDER_ATTRIBUTES` (`pagu`, `realisasi_anggaran`, `realisasi_fisik`, `status`) ke `metadata_definitions` — sekali, idempoten (`firstOrCreate` by `kode`).
2. Untuk tiap baris `map_type_dynamic_attributes` existing:
   - Kalau `tipe = 'placeholder'` → hubungkan ke `metadata_definitions` `is_system` yang sesuai `kode_atribut` (dari langkah 1).
   - Kalau `tipe = 'custom'` → cek apakah sudah ada `metadata_definitions` dengan `kode` yang sama persis DAN `label`+`satuan` sama persis:
     - **Cocok** → hubungkan ke definisi itu (dedup, sesuai tujuan Opsi B).
     - **`kode` sama tapi `label`/`satuan` beda** (konflik nyata — kasus yang tadinya diizinkan oleh test lama) → buat `metadata_definitions` baru dengan `kode` di-suffix (`catatan_2`, `catatan_3`, dst.), supaya tidak ada data yang salah tertaut atau tertimpa diam-diam.
     - **Belum ada** → buat baris `metadata_definitions` baru dari data existing (`is_system=false`).
3. **Wajib `--dry-run` dulu** — cetak laporan: berapa baris ter-dedup, berapa yang kena suffix-conflict (dan detail Jenis mana + `kode_atribut` mana), supaya tim bisa review sebelum commit. Pola persis sama seperti `marimoi:granularize-map-types` yang sudah ada dan terbukti berguna (dry-run-nya sempat menemukan masalah nyata sebelum dijalankan — lihat catatan Bagian 1.6 `12-implementasi-perbaikan-pemetaan.md`).
4. Setelah direview & dijalankan tanpa `--dry-run`: isi `map_type_dynamic_attributes.metadata_definition_id` untuk semua baris.

### Tahap 4 — Migration: Wajibkan FK + Drop Kolom Lama (setelah Tahap 3 diverifikasi 100% terisi)
```php
Schema::table('map_type_dynamic_attributes', function (Blueprint $table) {
    $table->foreignId('metadata_definition_id')->nullable(false)->change();
    $table->dropColumn(['tipe', 'kode_atribut', 'label', 'satuan']);
});
```
Dijalankan sebagai migration **terpisah**, baru setelah Tahap 3 dikonfirmasi tidak ada baris `metadata_definition_id IS NULL` tersisa — pola yang sama dengan "jangan cutover sebelum QA paralel" di Keputusan-keputusan sebelumnya.

### Tahap 5 — Perubahan Model & Kode Aplikasi
1. **Model baru `MetadataDefinition`**: `$fillable` (`kode, label, deskripsi, data_type, satuan, opsi, validasi, is_system, is_filterable, created_by`), `casts()` untuk `opsi`/`validasi` (`array`), relasi `hasMany(MapTypeDynamicAttribute::class)`, konstanta `DATA_TYPES = ['text','integer','decimal','currency','select','date']`.
2. **`MapTypeDynamicAttribute` model**: jadi pivot ramping — `$fillable` (`map_type_id, metadata_definition_id, is_wajib, is_enabled, urutan, is_active`), relasi `belongsTo(MetadataDefinition::class)`. `PLACEHOLDER_ATTRIBUTES` constant **dihapus** (datanya sudah pindah jadi baris `is_system=true` di database, sumber kebenaran tunggal).
3. **`MapTypeController`**: `syncDynamicAttributes()` menerima payload berisi `metadata_definition_id` (definisi existing yang dipilih) ATAU data definisi baru (`kode/label/satuan/data_type/opsi` — sistem `firstOrCreate` ke `metadata_definitions` dulu, baru buat baris pivot).
4. **Controller/route baru `MetadataDefinitionController`** (opsional tapi disarankan): endpoint `GET /dashboard/metadata-definitions/search?q=...` (AJAX, dipakai UI Tahap 6 untuk cari definisi existing) — permission `map-types.manage` yang sudah ada, tidak perlu permission baru.
5. **`map-types/_form.blade.php`** (tabel Nama/Key-Nilai-Satuan yang baru direstrukturisasi): baris "Tambah Atribut Custom" berubah jadi dua mode — **"Pilih dari katalog"** (searchable select, AJAX ke endpoint Tahap 5.4) atau **"Buat definisi baru"** (form inline nama/key, nilai/label, satuan, tipe data — sama seperti sekarang, tapi hasilnya masuk katalog global, bukan cuma milik Jenis ini).
6. **`SpatialLayerFeatureController`** (form isi `metadata_dinamis`): kalau `metadata_definitions.data_type = select`, render `<select>` dari `opsi`, validasi `Rule::in($opsi)`.

### Tahap 6 — Test
- **Diganti** (bukan dihapus begitu saja — diganti dengan semantik baru yang eksplisit didokumentasikan): `test_same_kode_atribut_allowed_across_different_jenis` → `test_same_kode_atribut_reuses_same_metadata_definition_across_jenis` (menguji dedup Tahap 3 poin 2, kasus "cocok").
- Baru: `test_conflicting_kode_atribut_gets_suffixed_during_backfill` (menguji Tahap 3 poin 2, kasus "konflik" — command bukan migration, jadi ini test command/Artisan, bukan test HTTP).
- Baru: `MetadataDefinitionTest` — CRUD katalog, `is_system` tidak bisa dihapus, `data_type=select` mewajibkan `opsi` terisi.
- Update: `MapTypeManagementTest`/`MapTypeDynamicAttributeTest` existing yang berasumsi `map_type_dynamic_attributes` punya kolom `label`/`satuan` langsung — disesuaikan ke struktur pivot baru.

**Ringkasan risiko**: Tahap 1–2 aman (additive murni). Tahap 3 (command) adalah satu-satunya bagian yang punya risiko data nyata (konflik `kode_atribut`) — makanya **wajib `--dry-run` direview manusia dulu**, sama seperti pola `marimoi:granularize-map-types`. Tahap 4 (drop kolom lama) baru dijalankan setelah Tahap 3 dikonfirmasi tuntas 100%, supaya tidak ada window di mana data lama & baru sama-sama tidak lengkap.

---

## 7. Keputusan yang Perlu Dikunci Sebelum Implementasi

1. **Opsi B dikunci** (Bagian 5) — katalog `metadata_definitions` terpisah, bukan sekadar tambah kolom di `map_type_dynamic_attributes`.
2. **Perubahan semantik `kode_atribut`: dari unik-per-Jenis jadi unik-global** — `test_same_kode_atribut_allowed_across_different_jenis` **diganti**, bukan dipertahankan (Bagian 6 Tahap 6). Ini keputusan yang disengaja: dua Jenis yang sama-sama pakai atribut custom dengan `kode` sama sekarang **berbagi satu definisi** (kalau `label`/`satuan` cocok) atau **dipisah lewat suffix otomatis** (kalau beda) saat backfill — bukan lagi bebas didefinisikan ulang tanpa terhubung.
3. **Strategi resolusi konflik saat backfill** (Bagian 6 Tahap 3 poin 2) — dokumen ini mengusulkan "cocok persis → dedup, beda → suffix otomatis + laporan `--dry-run`". Perlu dikonfirmasi apakah cukup, atau tim ingin resolusi manual (mis. daftar konflik disiapkan dulu, tim yang putuskan mana yang di-merge) sebelum command dijalankan tanpa `--dry-run`.
4. **Kolom `konfigurasi` (jsonb) di `map_types`** — tetap tidak dipakai bahkan setelah Opsi B (§19 Dokumen Rujukan sekarang terpenuhi lewat `metadata_definitions`, bukan lewat kolom ini) — kemungkinan besar **dihapus** di migration terpisah, perlu dikonfirmasi tidak ada rencana pemakaian lain sebelum di-drop.
5. **Metadata Utama (Sumber Data/OPD/Tahun) — tetap kolom fixed di `map_types`, TIDAK ikut dipindah ke `metadata_definitions`.** Alasan: (a) OPD Penanggung Jawab butuh FK relasional ke tabel `opd` yang tidak mudah digeneralisasi ke `metadata_definitions` generik tanpa kolom tambahan semacam `value_relation_id`/tabel referensi terpisah, (b) tiga field ini sudah stabil & wajib di semua Jenis sejak awal, beda karakter dengan atribut custom yang memang dirancang untuk fleksibel. Kalau ke depan ingin disatukan juga, itu perubahan terpisah yang lebih besar (di luar cakupan dokumen ini).
6. **Prioritas `is_filterable`/Filter Dinamis (§20)** — kolom `is_filterable` disiapkan di skema `metadata_definitions` (Bagian 6 Tahap 1), tapi UI filter otomatis di `/peta-v2`/`/dashboard/spatial-layers` **belum termasuk** rencana ini — perlu dikonfirmasi apakah jadi dokumen susulan segera, atau kolomnya cukup disiapkan dulu.
7. **Siapa yang boleh mengelola katalog `metadata_definitions`** — tetap lewat `map-types.manage` (permission yang sudah ada, karena katalog cuma bisa diakses lewat form Jenis Peta), atau perlu permission baru `metadata-definitions.manage` terpisah (relevan kalau ke depan ada halaman katalog mandiri, bukan cuma inline lewat form Jenis Peta)?

---

## 8. Dampak ke Kode yang Sudah Ada (Ringkasan, Opsi B)

| File | Perubahan |
| --- | --- |
| `database/migrations/*_create_metadata_definitions_table.php` | Baru (Bagian 6 Tahap 1). |
| `database/migrations/*_add_metadata_definition_id_to_map_type_dynamic_attributes_table.php` | Baru (Bagian 6 Tahap 2). |
| `app/Console/Commands/MigrateMetadataDefinitions.php` | Baru — command backfill dengan `--dry-run` (Bagian 6 Tahap 3), pola sama seperti `MarimoiGranularizeMapTypes`/`MarimoiReconcileSpatialLayersBackfill` yang sudah ada. |
| `database/migrations/*_finalize_map_type_dynamic_attributes_pivot_table.php` | Baru — wajibkan FK, drop kolom lama (Bagian 6 Tahap 4), dijalankan setelah Tahap 3 diverifikasi. |
| `app/Models/MetadataDefinition.php` | Model baru (Bagian 6 Tahap 5.1). |
| `app/Models/MapTypeDynamicAttribute.php` | Jadi pivot ramping, `PLACEHOLDER_ATTRIBUTES` constant dihapus (Bagian 6 Tahap 5.2). |
| `app/Http/Controllers/MapTypeController.php` | `syncDynamicAttributes()` terima `metadata_definition_id` existing ATAU data definisi baru (Bagian 6 Tahap 5.3). |
| `app/Http/Controllers/MetadataDefinitionController.php` | Baru (opsional, disarankan) — endpoint search AJAX untuk UI pilih definisi existing (Bagian 6 Tahap 5.4). |
| `resources/views/backend/pages/map-types/_form.blade.php` | Tabel Metadata Dinamis (baru direstrukturisasi jadi Nama/Key-Nilai-Satuan minggu ini) dapat mode "Pilih dari katalog" vs "Buat definisi baru" (Bagian 6 Tahap 5.5). |
| `app/Http/Controllers/SpatialLayerFeatureController.php` + `features/_form.blade.php` | Render `<select>` untuk atribut `data_type=select` saat isi Data Spasial, validasi `Rule::in()` (Bagian 6 Tahap 5.6). |
| `tests/Feature/MapTypeDynamicAttributeTest.php` | Direvisi total mengikuti struktur pivot baru; `test_same_kode_atribut_allowed_across_different_jenis` diganti (Bagian 6 Tahap 6). |
| `tests/Feature/MetadataDefinitionTest.php` | Baru — CRUD katalog, validasi `is_system`, validasi `data_type=select` (Bagian 6 Tahap 6). |
| `tests/Console/MigrateMetadataDefinitionsTest.php` (atau `tests/Feature/`) | Baru — test command backfill: dedup, suffix-conflict, `--dry-run` tidak mengubah data (Bagian 6 Tahap 6). |
| `tests/Feature/MapTypeManagementTest.php` | Bagian yang menguji field `dynamic_attributes[]` disesuaikan ke payload baru (definisi existing vs baru). |

**Tidak disentuh**: `spatial_layers`, `spatial_layer_features.metadata_dinamis` (tetap jsonb bebas — hanya validasi tambahan di level form kalau `data_type=select`), `spatial_feedbacks`, seluruh alur `/peta-v2`, kolom Metadata Utama fixed di `map_types` (Bagian 7 poin 5).

**Section "Metadata Utama"/"Metadata Dinamis" digabung jadi satu (2026-10-01, atas permintaan user)**: `map-types/_form.blade.php` sebelumnya punya 2 `form-section` terpisah — "Metadata Utama" (Sumber Data/OPD/Tanggal, badge "Wajib diisi") dan "Metadata Dinamis" (badge "Opsional", berisi sub-bagian "Atribut Siap Pakai" + "Atribut Tambahan"). Digabung jadi **satu** section "Metadata", isinya 3 sub-bagian bertajuk dengan badge masing-masing (bukan satu badge di level section): **Atribut Utama** (Sumber Data/OPD/Tanggal, badge "Wajib diisi" — nama field & kolom database TIDAK berubah, cuma label section), **Atribut Siap Pakai** (4 definisi `is_system`, badge "Opsional"), **Atribut Custom** (dulu "Atribut Tambahan" — nama diganti biar konsisten dengan istilah "Atribut Utama"/"Atribut Custom" yang diminta user, badge "Opsional"). Sub-bagian dipisah garis putus-putus tipis (`border-top` CSS `.metadata-subsection + .metadata-subsection`) supaya tetap ada pemisah visual walau sudah satu card. Murni perubahan tampilan/pengelompokan, tidak ada perubahan nama field HTML/payload/kolom database. Test disesuaikan: assertion baru memastikan "Metadata Utama"/"Metadata Dinamis"/"Atribut Tambahan" TIDAK lagi muncul di halaman, dan "Metadata"/"Atribut Utama"/"Atribut Siap Pakai"/"Atribut Custom" muncul. 337 test lulus (jumlah sama, assertion bertambah), 8 gagal baseline tidak berubah, diverifikasi juga lewat live request.

**Ketiga sub-bagian atribut diseragamkan jadi format tabel yang sama (2026-10-01, sesi sama, atas permintaan user)**: "Atribut Utama" (Sumber Data/OPD/Tanggal) sebelumnya render lewat 3 kolom input polos (`row g-3`), berbeda gaya dari "Atribut Siap Pakai"/"Atribut Custom" yang sudah tabel. Diubah jadi tabel `#core-attributes-table` dengan header sama persis (`Nama/Key | Nilai (Label) | Satuan | Wajib`) seperti tabel "Atribut Siap Pakai" — tiap baris punya `<code>nama_kolom</code>` (mis. `sumber_data`, `opd_penanggung_jawab_id`, `tanggal_data`) + label kecil di bawahnya di kolom "Nama/Key", input/select/date asli di kolom "Nilai", "-" di kolom Satuan (tidak relevan untuk field ini), dan checkbox "Wajib" yang selalu tercentang+disabled (field ini memang selalu wajib, bukan opsional seperti atribut dinamis). Nama field HTML (`sumber_data`/`opd_penanggung_jawab_id`/`tanggal_data`) dan validasi server **tidak berubah** — murni pembungkusan ulang ke format tabel. **Bug ditemukan & diperbaiki di luar scope literal permintaan, tapi krusial**: field Slug ternyata sempat dapat atribut HTML `disabled` (selain `readonly`) dari perubahan di luar sesi ini — kombinasi itu membuat browser TIDAK mengirim field `slug` sama sekali saat submit (atribut `disabled` mengecualikan field dari `FormData`, beda dari `readonly` yang tetap mengirim), sehingga create/update Jenis Peta manapun akan SELALU gagal validasi "slug wajib diisi". Diperbaiki dengan menghapus atribut `disabled` (tetap `readonly`), diverifikasi eksplisit lewat POST end-to-end via tinker (Jenis baru berhasil tersimpan dengan slug yang benar). CSS padding tabel diperluas mencakup `#core-attributes-table` juga, konsisten dengan dua tabel lain. 337 test lulus (assertion bertambah), 8 gagal baseline tidak berubah, diverifikasi juga lewat live request.

**3 tabel digabung jadi 1, "Atribut Siap Pakai" dihapus dari tampilan, checkbox non-interaktif diganti badge (2026-10-01, sesi sama, atas permintaan user)**: revisi lanjutan atas perubahan sebelumnya (yang baru saja menyeragamkan 3 tabel TERPISAH) — sekarang benar-benar **satu tabel fisik** (`#dynamic-attributes-table`), bukan 3 tabel dengan header sama. Baris "Atribut Utama" (Sumber Data/OPD/Tanggal, class `core-attribute-row`, background abu-abu buat pembeda visual) jadi 3 baris statis di `<tbody>` pertama tabel yang sama, diikuti `<tbody id="dynamic-attributes-list">` untuk baris atribut dinamis (existing + custom) — dua `<tbody>` dalam satu `<table>`, valid HTML, tetap terlihat sebagai satu tabel menerus. Tabel "Atribut Siap Pakai" (`#system-definitions-table`, checklist 4 definisi `is_system` yang selalu tampil) **dihapus total** dari tampilan — 4 definisi bawaan (Pagu/Realisasi Anggaran/Realisasi Fisik/Status) TETAP ADA di database & tetap bisa dipakai, tapi sekarang HANYA lewat pencarian katalog (`catalog-search-input`) yang sudah ada, bukan lewat daftar checklist terpisah yang selalu memakan tempat. **Checkbox "Wajib" yang sebelumnya `checked disabled` untuk Atribut Utama** (field yang memang tidak perlu diisi/diklik user karena nilainya sudah pasti) **diganti jadi badge teks statis** `<span class="badge bg-danger-subtle text-danger">Wajib</span>` — tidak ada lagi elemen `disabled` yang terlihat seperti kontrol interaktif padahal tidak bisa diapa-apakan; kolom "Tipe Data" & "Filter" untuk baris ini juga jadi teks/badge statis ("text"/"select"/"date", "-") bukan input, dan kolom Aksi kosong (tidak ada tombol hapus, karena baris ini memang tidak bisa dihapus). JS disederhanakan: hapus seluruh listener `system-definition-toggle`/`system-definition-wajib` (elemen-nya sudah tidak ada), `existing.forEach(addPickedRow)` sekarang tanpa filter `!e.is_system` (definisi is_system yang sudah dipakai Jenis ini tetap dirender sebagai "picked row" seperti definisi custom — UI-nya sekarang seragam, tidak dibedakan lagi). `MapTypeController::create()`/`edit()` tidak lagi menyiapkan `$systemDefinitions` (dead variable setelah tabelnya dihapus). CSS `.metadata-subsection`/`.metadata-subsection-title` dihapus (subsection sudah tidak ada), diganti `.core-attribute-row { background-color: #f8f9fa; }`. 337 test lulus (test lama diganti `test_metadata_shown_as_single_merged_table` dengan assertion terbalik — `assertDontSee` untuk semua yang dihapus), 8 gagal baseline tidak berubah, diverifikasi juga lewat live request + POST end-to-end (Jenis baru + pilih "Pagu" via `metadata_definition_id` langsung, tanpa lewat UI checklist yang sudah dihapus, tetap tersimpan benar).

---

## 9. Referensi

- **Dokumen Rujukan (sumber rekomendasi)**: `docs/marimoi v2/rancangan_database_metadata_layer_marimoi.md`.
- **Desain & implementasi existing yang jadi baseline perbandingan**: `docs/marimoi v2/03_plan/13-perbaikan-pemetaan.md` (desain) dan `docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md` (implementasi, termasuk Keputusan #1–#3 yang tetap berlaku).
- **Fondasi skema**: `docs/marimoi v2/04_implementation/09-implementasi-penuh-database-v2.md`.
- **Test coverage existing yang jadi acuan "jangan breaking"**: `tests/Feature/MapTypeManagementTest.php`, `tests/Feature/MapTypeDynamicAttributeTest.php`.
