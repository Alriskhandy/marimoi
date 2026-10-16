# Plan Implementasi Penuh: Perbaikan Pemetaan (Jenis → Layer → Data Spasial → Feedback)

## Status

✅ Bagian 1–5 diimplementasikan dan diverifikasi penuh (2026-09-30). Bagian 6 (cutover — cabut menu lama) **sengaja belum dijalankan**, sesuai urutan wajib dokumen ini sendiri (QA paralel dulu, baru cabut menu). Menu baru ditambahkan **aditif** di sidebar (label "(Baru)"), berdampingan dengan menu lama — bukan pengganti.

**Yang sudah nyata jadi kode**:
- Bagian 1: 5 migration (Metadata Utama di `map_types`, `map_type_dynamic_attributes`, `icon`/`opacity` di `spatial_layers`, `gambar`+`metadata_dinamis` di `spatial_layer_features`, `spatial_feedbacks` dengan CHECK constraint).
- Bagian 1.6: command `marimoi:granularize-map-types` dan `marimoi:reconcile-spatial-layers-backfill` — **sudah dites lengkap, TAPI belum dijalankan sungguhan terhadap data produksi**. Dry-run terhadap database dev mengungkap masalah nyata: beberapa root Layer adalah "payung" luas (mis. "Tematik Kabupaten/Kota" mencakup 101 Layer campuran — Jaringan Jalan, Kesehatan, Pendidikan, dst. jadi 1 Jenis), bukan objek granular seperti "Jalan Provinsi" yang dimaksud Keputusan #1. **Aturan "root saja jadi Jenis" tidak otomatis cocok untuk struktur tree yang ada** — butuh keputusan lanjutan sebelum dijalankan sungguhan (lihat detail di memori proyek).
- Bagian 2: model `MapTypeDynamicAttribute`, `SpatialFeedback` (baru), `MapType`/`SpatialLayer`/`SpatialLayerFeature` diperluas. `SpatialLayer::wouldCreateCycle()` untuk validasi tree.
- Bagian 3.1: `MapTypeController` diperluas jadi halaman penuh (bukan modal lagi) dengan Metadata Utama wajib + builder Metadata Dinamis (placeholder toggle + custom, is_wajib per baris).
- Bagian 3.2: `SpatialLayerController` (tree tanpa batas kedalaman, validasi cycle) + `SpatialLayerFeatureController` (form dua bagian: Atribut Impor read-only vs Metadata Dinamis tervalidasi, geometry via WKT dengan escaping `PDO::quote()`).
- Bagian 3.3: `SpatialFeedbackController` (admin + endpoint publik tanpa auth, throttle 10/menit).
- Bagian 4: menu sidebar baru ditambahkan aditif, permission `spatial-layers.*`/`spatial-feedbacks.*` di katalog.
- Bagian 5: `/peta-v2` (dokumen 10) diperluas — icon/opacity di render layer, `metadata_dinamis` berlabel + `gambar` di popup detail, form kirim feedback inline.

**Verifikasi**: full regression `php artisan test --compact` → 302 lulus (naik dari 269 di awal sesi implementasi ini), 8 gagal — persis baseline lama. Pint bersih. Divalidasi manual lewat `curl` ke server dev dengan data live sungguhan (buat Jenis+skema metadata+Layer+Data Spasial lewat tinker → cek `/peta-v2/feature/{id}` menampilkan "Pagu (Rp)" dengan label benar), data uji dibersihkan setelahnya.

**Bug pasca-implementasi ditemukan & diperbaiki (2026-09-30)**: `resources/views/backend/pages/map-types/_form.blade.php` gagal dikompilasi (`Unclosed '[' ... does not match ')'`) — direktif `@json()` Blade tidak menangani argumen array literal **multi-baris** dengan banyak koma (`$existingAttributes->map(fn ($a) => [...multi-baris...])` langsung di dalam `@json(...)`), hasil kompilasinya terpotong jadi PHP tidak valid. Diperbaiki dengan memindahkan transformasi array ke blok `@php` di atas, lalu `@json()` cukup mereferensi variabel biasa satu baris. **Penyebab lolos dari 302 test yang sudah "lulus"**: TIDAK ADA satu test pun yang benar-benar mengirim GET ke halaman create/edit manapun (`map-types`, `spatial-layers`, `spatial-layers.features`, `maps`) — semua test hanya menguji aksi POST/PUT/DELETE, yang tidak pernah merender partial Blade-nya sama sekali. Ditambahkan test render (GET + `assertOk()`) untuk SEMUA halaman create/edit yang sebelumnya tidak punya, di semua controller Bagian 3 (10 test baru) — sekaligus dipakai untuk memverifikasi ulang seluruh view lain TIDAK punya bug kompilasi serupa (dicek eksplisit lewat `Blade::compileString()` + `php -l` untuk tiap file).

**Penyesuaian tampilan tabel `/dashboard/spatial-layers` (2026-09-30, lanjutan lagi)**: warna tombol expand/collapse ditukar (default/collapsed sekarang biru, expanded jadi abu — baik tombol per-baris maupun "Expand All"), ukuran font tabel diperkecil (header 0.85rem→0.75rem, isi sel diberi 0.85rem eksplisit yang sebelumnya tidak diset/ikut default browser). 317 test tetap lulus (perubahan murni CSS, tidak ada test PHP yang menguji warna/ukuran font — diverifikasi manual lewat isi HTML response live).

**Perbaikan lanjutan tabel `/dashboard/spatial-layers` (2026-09-30)**: kolom Gambar dihapus (colspan/`columnDefs` DataTables disesuaikan). Tombol "Expand All" ternyata tanpa gaya sama sekali — CSS `.hierarchy-toggle-all`/`.hierarchy-controls` dari `categories/index.blade.php` terlewat saat menyalin styling sebelumnya (cuma sebagian CSS yang disalin, blok ini ada di bagian file yang belum kebaca saat itu). Ditambahkan CSS eksplisit untuk tombol expand per-baris dan tombol "Expand All", dengan `!important` supaya tidak gampang tertimpa `.btn-link` bawaan Bootstrap. 317 test lulus.

**Perbaikan tabel `/dashboard/spatial-layers` (2026-09-30, lanjutan)**: kolom Warna/Tipe/Icon digabung jadi 1 kolom "Style" (warna+icon+badge marker/layer dalam satu sel), kolom Status dihapus, tombol detail (ikon mata) ditambahkan di kolom Aksi menuju halaman baru `spatial-layers.show` (read-only: info Layer, Metadata Utama Jenis, daftar Layer anak, daftar Data Spasial). Route baru `GET /dashboard/spatial-layers/{spatialLayer}`, permission sama dengan index (`spatial-layers.view`). 4 test baru (kolom hilang/ada, render detail, permission). 316 test lulus.

**Tampilan `/dashboard/spatial-layers` disamakan dengan `/dashboard/categories` (2026-09-30)**: stats card (Layer Akar/Sub Layer/Marker Aktif/Status Aktif), toolbar cari + per-halaman via DataTables client-side (library sudah dimuat global di `backend.partials.main`, tidak perlu tambahan), tabel hierarki dengan expand/collapse tak terbatas kedalaman (beda dari `categories` yang dibatasi 3 level — Keputusan #3), badge Jenis/warna/tipe/status, CSS sama persis. Tambah/Edit tetap ke halaman khusus yang sudah ada (Bagian 3.2), bukan modal+AJAX seperti `categories` — direplikasi modal+AJAX-nya akan jadi pekerjaan yang jauh lebih besar (icon picker, color picker, image preview, ~800 baris JS) untuk manfaat marginal, mengingat halaman khusus sudah dibangun & diuji. **Bug kedua ditemukan & diperbaiki lewat verifikasi (bukan cuma dari user)**: fungsi PHP top-level (`function renderSpatialLayerHierarchy(...)`) yang dideklarasikan langsung di dalam `@php` blok view — fatal `Cannot redeclare` begitu halaman yang sama dirender **lebih dari sekali dalam satu proses PHP** (kejadian nyata: dua test yang sama-sama GET index dalam satu test run). Diperbaiki dengan guard `if (! function_exists(...))`. **Catatan**: `categories/index.blade.php` punya pola identis (`function renderCategoryHierarchy(...)` tanpa guard) — berisiko sama kalau pernah dirender 2x per proses, TAPI sengaja tidak diubah karena di luar cakupan permintaan (cuma diminta menyamakan tampilan `spatial-layers`, bukan memperbaiki `categories`).

**Halaman detail `spatial-layers.show` dilengkapi card peta + tabel Data Spasial (2026-09-30)**: daftar sederhana `<ul>` di bawah "Layer Anak" diganti 2 card baru full-width: (1) card peta Leaflet yang menampilkan Data Spasial Layer ini lewat endpoint publik `GET /peta-v2/geojson/{layer:slug}` yang sudah ada (dokumen 10) — beda dari `/peta-v2` publik yang punya panel pilih banyak Layer, di sini cukup basemap switcher (Satelit/Jalan/Topografi/Gelap, pola disalin dari `data_spatial/map.blade.php`) karena Layer sudah tunggal & tetap. Card peta bisa di-collapse/expand lewat Bootstrap `collapse` (JS bawaan, sudah dimuat global) — `map.invalidateSize()` dipanggil di event `shown.bs.collapse` supaya tile tidak rusak saat card baru dibuka kembali. (2) card tabel Data Spasial bergaya `table-striped` sama seperti `/dashboard/data-spatial?type=tematik` (kolom No/Kode/Wilayah/Metadata/Tanggal Input/Aksi), di-scope ke Layer ini saja lewat `$layer->features` yang sudah dimuat `show()` (ditambah eager-load `features.region` untuk kolom Wilayah). 1 test baru (`test_show_page_renders_map_card_and_data_spatial_table`), total 318 lulus. **Catatan ketidaksengajaan ditemukan**: test lama `test_index_table_has_no_status_column_and_links_to_detail_page` sempat gagal karena header `<th>Style</th>` di `index.blade.php` sudah lebih dulu berubah jadi `<th style="width: 15%;">Style</th>` (perubahan di luar sesi ini) — assertion disesuaikan jadi `assertSee('>Style</th>')`, bukan reverting markup.

**Penyesuaian lanjutan halaman detail `spatial-layers.show` (2026-09-30)**: card "Layer Anak" di samping "Informasi Layer" dihapus (kolom `col-lg-5` dibuang, `col-lg-7` jadi `col-12` penuh) — info Layer induk tetap ada lewat field "Layer Induk" yang sudah ada di tabel Informasi Layer, jadi tidak kehilangan data. Card "Informasi Layer" (+ "Jenis" di dalamnya) dijadikan collapsible dengan Bootstrap `collapse` yang sama polanya dengan card peta, **default collapsed** (beda dari card peta yang default `show`). Tabel Data Spasial ditambah: search box client-side (filter baris lewat `textContent.includes()`, tidak perlu roundtrip server karena datanya sudah di-scope ke satu Layer), font diperkecil (th 0.75rem/td 0.85rem, pola sama seperti tabel index), dan tombol hapus per baris (`spatial-layers.features.destroy`, pola `data-confirm="delete"` yang sama seperti index.blade.php, handler global sudah ada di `main.blade.php`). 1 test baru (`test_show_page_has_collapsible_info_card_search_and_delete_button`), total 319 lulus (8 gagal baseline tidak berubah).

**Tombol tambah data & pilihan jumlah baris di `spatial-layers.show` (2026-09-30)**: tombol "Tambah Data Spasial" ditambahkan di header tabel Data Spasial (menuju `spatial-layers.features.create`, halaman form yang sudah ada di Bagian 3.2 — tidak perlu route baru). Select "Tampilkan N data" (10/25/50/semua, default "semua" supaya perilaku lama tidak berubah untuk yang belum pernah menyentuhnya) ditambahkan bersebelahan dengan search box — keduanya kerja bareng lewat satu fungsi `applyDataSpasialTableView()` client-side (baris ditandai `data-feature-row` supaya baris "Belum ada Data Spasial" kosong tidak ikut kehitung sebagai kandidat). 1 test baru (`test_show_page_has_add_feature_button_and_per_page_select`), total 320 lulus (8 gagal baseline tidak berubah).

**Halaman edit `spatial-layers` terpisah dihapus, diganti modal di halaman detail (2026-09-30)**: `GET /dashboard/spatial-layers/{id}/edit` beserta view `edit.blade.php` dihapus total. Form ubah Informasi Layer (partial `_form.blade.php` yang sama, tidak diubah) sekarang tampil lewat modal Bootstrap (`#editLayerModal`) di `spatial-layers.show`, dipicu tombol "Edit" yang dipindah dari page-header ke dalam header card "Informasi Layer" — pola visual (modal-header/body/footer, tombol Simpan gradient-primary) meniru modal edit Kategori di `/dashboard/categories`, tapi submit-nya form biasa (bukan AJAX) karena di sini sudah pasti mengedit satu Layer yang sedang dibuka, bukan baris tabel dinamis seperti di `categories` — jadi tidak perlu mekanisme populate lewat `data-*` attribute. Modal auto-terbuka lagi kalau submit gagal validasi (`$errors->any()` memicu `bootstrap.Modal(...).show()`), supaya pesan error tidak "hilang" di balik modal tertutup. Semua alur yang sebelumnya redirect ke `spatial-layers.edit` (create/update/destroy Data Spasial, tombol "Kembali"/"Batal" di form feature) dipindah ke `spatial-layers.show`; tombol "Kelola" (pencil) di tabel index juga dihapus karena tidak ada lagi halaman tujuannya — tabel index sekarang cuma py Detail (mata) + Hapus. Test lama `test_edit_page_renders_with_children_dropdown_and_feature_list` diganti `test_show_page_has_edit_modal_instead_of_separate_edit_page` + `test_spatial_layers_edit_route_no_longer_exists`; 4 assertion redirect di `SpatialLayerFeatureControllerTest` diarahkan ulang ke `spatial-layers.show`. Total 321 lulus (8 gagal baseline tidak berubah).

**Modal edit Layer disamakan visual/strukturnya dengan modal edit Kategori (2026-09-30)**: modal `#editLayerModal` di `spatial-layers.show` sebelumnya cuma form polos (hasil `@include` partial `_form.blade.php`) — diganti replikasi struktur DOM + CSS modal edit Kategori (`categories/index.blade.php` `#editModal`) secara copy-paste lalu dimodifikasi isian field-nya: layout dua kolom (kiri: Jenis/Nama/Deskripsi/Layer Induk/Kelas Layer/switch Aktif+Marker dalam `.settings-switch-group`; kanan: `.color-picker-widget` dengan 10 swatch preset + native color input + hex text field yang saling sinkron, opacity), plus icon-picker full-width di bawah (`.icon-picker-grid` dibangun dari `<select>` tersembunyi berisi `@include('backend.partials.icon-options')`, dengan search box dan preview) yang muncul/hilang mengikuti checkbox "Gunakan sebagai Marker". CSS-nya (`.icon-picker-*`, `.color-picker-widget`, `.color-swatch*`, `.settings-switch-group`, `.icon-preview-*`) disalin persis dari `categories/index.blade.php`. **Perbedaan yang disengaja**: (1) tidak ada widget upload gambar karena `SpatialLayer` tidak punya kolom `gambar`; (2) submit tetap form biasa (redirect balik ke `spatial-layers.show`), bukan AJAX seperti categories — karena modal ini cuma untuk SATU Layer yang sedang dibuka (bukan baris tabel dinamis lewat tombol `.btn-edit` + `data-*` attribute), jadi state awal (warna/icon/marker) langsung di-render dari `$layer` lewat Blade + di-apply sekali oleh JS saat halaman dimuat, bukan lewat handler klik seperti di categories; validasi error men-trigger modal auto-terbuka lagi (sudah ada dari perubahan sebelumnya). 1 test baru (`test_edit_modal_matches_categories_edit_modal_widgets`) memverifikasi class CSS & elemen kunci widget benar-benar ada. Total 322 lulus (8 gagal baseline tidak berubah). Diverifikasi juga manual lewat PUT langsung (bukan cuma GET render) untuk memastikan field `color`/`opacity`/`icon` yang dikirim lewat widget baru ini benar-benar tersimpan.

**Tampilan `/dashboard/map-types` diperbaiki (2026-09-30)**: tabel polos (tanpa search/pagination, tanpa stats, tombol hapus `onsubmit="return confirm(...)"` browser native) diganti gaya yang sama dengan `spatial-layers`/`categories` — stats card (Total Jenis/Aktif/Nonaktif/Total Layer), toolbar cari + per-halaman via DataTables (library sudah global), kolom baru "OPD Penanggung Jawab" (`MapTypeController::index()` ditambah eager-load `opdPenanggungJawab` supaya tidak N+1), kolom Jenis Peta menggabungkan icon+nama+slug dalam satu sel, tombol hapus diseragamkan pakai pola `data-confirm="delete"` (modal konfirmasi global yang sama dipakai `spatial-layers`/`categories`) menggantikan `confirm()` browser bawaan. 1 test baru (`test_index_page_shows_stats_cards_opd_column_and_delete_button`). Total 323 lulus (8 gagal baseline tidak berubah).

**Kolom index `/dashboard/map-types` diganti (2026-09-30)**: kolom "Sumber Data" dan "OPD Penanggung Jawab" dihapus, diganti "Atribut Utama" dan "Atribut Tambahan" — badge jumlah `MapTypeDynamicAttribute` aktif per Jenis, dipecah menurut `tipe` (`TIPE_PLACEHOLDER` vs `TIPE_CUSTOM`, lihat Bagian 1.2/2). `MapTypeController::index()` pakai `withCount()` dengan closure per tipe + filter `is_active` (bukan eager-load penuh relasinya) supaya tetap satu query ringkas, atribut nonaktif sengaja tidak ikut terhitung karena tidak lagi wajib diisi saat input Data Spasial. 1 test lama disesuaikan (hapus assertion kolom OPD), 1 test baru (`test_index_page_shows_atribut_utama_and_tambahan_columns_instead_of_sumber_data_and_opd`) memverifikasi hitungan placeholder-aktif vs custom-aktif benar dan kolom lama sudah tidak ada. Total 324 lulus (8 gagal baseline tidak berubah).

**Index `/dashboard/map-types` menegaskan konsep Jenis Peta (2026-09-30)**: banner info ditambahkan di atas tabel — Jenis Peta **bukan** untuk mengelompokkan Layer, tapi mendefinisikan atribut/metadata acuan; saat menambah Layer baru user wajib pilih satu Jenis lalu mengisi Atribut Utama & Atribut Tambahan yang sudah ditentukan. Kolom "Jumlah Layer" diganti nama jadi "Dipakai di Layer" (+ tooltip `title`) supaya tidak terkesan seperti kolom pengelompokan/kategori, kolom Atribut Utama/Tambahan juga diberi tooltip penjelas. Ini murni copy/label UI, tidak ada perubahan skema atau perilaku CRUD. 1 test baru (`test_index_page_explains_map_type_is_an_attribute_reference_not_a_grouping`). Total 325 lulus (8 gagal baseline tidak berubah).

**Halaman "Tambah Jenis Peta" dihapus, diganti modal di index (2026-09-30)**: `GET /dashboard/map-types/create` beserta view `create.blade.php` dan controller method `create()` dihapus total (pola sama seperti penghapusan halaman edit terpisah `spatial-layers` sebelumnya). Form tambah (partial `_form.blade.php` yang sama, dipakai bareng dengan `edit.blade.php`) sekarang tampil lewat modal `#addMapTypeModal` di `map-types.index`, dipicu tombol "Tambah Jenis Peta" (header) dan tombol empty-state — submit tetap form biasa ke `map-types.store` (bukan AJAX), modal auto-terbuka lagi kalau validasi gagal (pola sama seperti modal edit Layer). `MapTypeController::index()` sekarang juga menyiapkan `$opdOptions`/`$placeholderAttributes` yang dulunya cuma milik `create()`.

**Perbaikan field form `_form.blade.php` (2026-09-30)**: urutan Nama/Slug ditukar (Nama duluan, lebih natural untuk diisi), Slug sekarang **auto-terisi dari Nama** lewat JS sederhana (`toLowerCase()` + ganti non-alfanumerik jadi `_`) selama user belum mengetik manual di kolom Slug sendiri — begitu user mengetik di kolom Slug, auto-fill berhenti supaya tidak menimpa input manual. Validasi server (`regex:/^[a-z0-9_]+$/`, unique) **tidak diubah**, cuma ditambah atribut HTML `pattern` untuk validasi client-side yang senada. Label field wajib (Nama, Slug, Sumber Data, OPD, Tanggal Data) diberi tanda `*` merah yang sebelumnya cuma ada di judul section "Metadata Utama", bukan di tiap label. Karena `_form.blade.php` dipakai bareng oleh modal tambah DAN halaman edit, perbaikan ini otomatis berlaku di keduanya.

Route `map-types.create` dihapus dari `Route::resource(...)->only([...])`; 1 test lama (`test_create_page_renders_without_compile_error`) diganti `test_index_page_renders_without_compile_error`, ditambah `test_map_types_create_route_no_longer_exists` dan `test_index_page_has_add_map_type_modal_with_improved_form_fields`. **Catatan tidak terduga**: banner info "Jenis Peta bukan untuk mengelompokkan Layer" yang ditambahkan sesi sebelumnya ternyata sudah hilang dari `index.blade.php` di luar perubahan sesi ini (bukan dihapus lewat pekerjaan ini) — test yang mengandalkannya (`test_index_page_explains_map_type_is_an_attribute_reference_not_a_grouping`) disesuaikan jadi cuma cek rename kolom "Dipakai di Layer" (bagian yang masih ada), bukan banner-nya; kalau banner itu memang masih diinginkan, perlu ditambahkan ulang secara eksplisit. Total 327 lulus (8 gagal baseline tidak berubah).

**Field Icon dihapus dari `MapType` (2026-09-30)**: kolom `icon` di `map_types` dihapus lewat migration baru (`drop_icon_from_map_types_table`, dropColumn dengan `down()` yang mengembalikan kolom `nullable` kalau perlu rollback) — dicek dulu tidak ada seeder atau bagian UI lain yang mengisinya, jadi aman dihapus daripada dibiarkan kolom mati. Dihapus juga dari `$fillable` model, rule validasi di `MapTypeController`, input field di `_form.blade.php`, dan tampilan icon di kolom "Jenis Peta" pada tabel index.

**Metadata Dinamis direstrukturisasi jadi tabel Nama/Key-Nilai-Satuan (2026-09-30)**: sebelumnya UI-nya adalah daftar checkbox longgar (4 placeholder: Pagu/Realisasi Anggaran/Realisasi Fisik/Status) yang terpisah gaya dari form baris penuh untuk atribut custom (Kode Atribut/Label/Satuan/Wajib) — dua pola visual berbeda untuk konsep yang sama. Diganti satu tabel seragam (`#dynamic-attributes-table`) dengan kolom eksplisit **Aktif | Nama/Key | Nilai (Label) | Satuan | Wajib | Aksi** untuk SEMUA baris, baik 4 atribut siap-pakai (Nama/Key & Nilai/Satuan tampil sebagai teks tetap, tinggal centang Aktif+opsional Wajib) maupun atribut custom (semua kolom jadi input teks + tombol hapus). Ini murni restrukturisasi tampilan — kontrak submit ke server (`dynamic_attributes[idx][tipe/kode_atribut/label/satuan/is_wajib]` sebagai hidden input yang disusun ulang saat submit) **tidak berubah sama sekali**, jadi `MapTypeController::syncDynamicAttributes()` tidak perlu disentuh. 2 test baru (`test_icon_field_is_removed_from_map_type`, `test_metadata_dinamis_shown_as_key_value_satuan_table`). Total 329 lulus (8 gagal baseline tidak berubah).

## Referensi

- **Desain sumber (otoritatif untuk relasi/model)**: `docs/marimoi v2/03_plan/13-perbaikan-pemetaan.md`. Dokumen ini menerjemahkan desain di sana menjadi migration/kode konkret, bertahap dari database sampai tampilan — **tidak mengubah keputusan desainnya**. Kalau ada perbedaan baca antara dokumen ini dan `13-perbaikan-pemetaan.md`, `13-perbaikan-pemetaan.md` yang menang.
- **Fondasi skema yang sudah ada**: `09-implementasi-penuh-database-v2.md` Prioritas 1–2 sudah membangun `map_types` → `spatial_layers` → `spatial_layer_features`, dengan bentuk relasi yang **persis sama** dengan Jenis → Layer → Data Spasial di `13-perbaikan-pemetaan.md`. Dokumen ini **memperluas fondasi itu**, bukan membangun skema paralel baru.
- **Peta publik**: `10-plan-peta-skema-baru.md` (`/peta-v2`) sudah membaca `spatial_layers`/`spatial_layer_features` — bagian tampilan di dokumen ini memperluas `/peta-v2`, bukan menduplikasinya.
- **Pasangan eksisting↔intervensi**: `spatial_layer_feature_interventions` (Prioritas 2, tabel `feature_id_eksisting`/`feature_id_intervensi`) **sudah ada** dan pas dipakai untuk kasus "Jalan Provinsi (eksisting) ↔ Jalan Provinsi yang diintervensi DPUPR (Data Spasial di bawah Jenis intervensi baru)" — lihat Keputusan #2. Dokumen ini tidak membuat tabel pasangan baru, cukup memakai yang sudah ada.

## Pemetaan Istilah: Desain (`13-perbaikan-pemetaan.md`) → Skema Existing

| Istilah di desain | Tabel existing yang dipakai | Alasan |
| --- | --- | --- |
| Jenis Layer/Data | `map_types` | Sudah 1:banyak ke `spatial_layers` lewat `map_type_id` — persis "1 Jenis banyak Layer, 1 Layer 1 Jenis". |
| Layer | `spatial_layers` | Sudah punya `parent_id` (tree), `color`/`is_marker` (sebagian style) — persis ciri "Layer" di desain: struktur tree + konfigurasi style. |
| Data Spasial | `spatial_layer_features` | Sudah punya `geometry`, `attributes` (jsonb, atribut default) — sudah 1:banyak dari `spatial_layers` lewat `spatial_layer_id`, persis "1 Layer banyak Data Spasial". |
| Feedback | **Baru**: `spatial_feedbacks` | `project_feedbacks` yang sudah ada khusus untuk proyek strategis (kolom `nama_proyek`, terikat `development_project_id`) — bukan feedback umum untuk sembarang Layer/Data Spasial seperti yang diminta desain. Dibuat tabel baru, bukan memaksakan `project_feedbacks`. |

Konsekuensi penting: karena Jenis/Layer/Data Spasial **menggunakan tabel yang sama** dengan `/peta-v2` (dokumen 10) dan `Kelola Peta` (dokumen 11), dokumen ini **melanjutkan** pekerjaan itu, bukan memulai dari nol. Bagian 6 membahas konsekuensi paling penting: dokumen ini pada akhirnya menjadikan `map_types`/`spatial_layers`/`spatial_layer_features` sebagai **satu-satunya** jalur tulis admin untuk pemetaan, menggantikan `CategoryController`/`DataSpatialController` — ini keputusan besar yang harus dibaca sebelum eksekusi.

## Keputusan yang Sudah Dikunci (2026-09-30)

1. **Granularitas "Jenis"**: dibuat granular sesuai rekomendasi — bukan tetap 5 baris kasar (`map_types` saat ini), Jenis baru dibuat seganular level induk `categories` sekarang (mis. "Jalan Provinsi" jadi Jenis sendiri). Lihat Bagian 1.6 untuk langkah migrasinya.
2. **Metadata Dinamis akan menggantikan alur `project_progress_reports` di masa depan — tapi tidak untuk semua Jenis, dan tidak dieksekusi sekaligus di dokumen ini**:
   - Jenis yang **bukan** objek pembangunan/intervensi (mis. "Administrasi", "Pola Ruang", "Jalan Provinsi" polos) **tidak perlu** metadata Pagu/Realisasi/dst — Metadata Dinamis untuk Jenis itu boleh kosong sama sekali.
   - Kalau suatu objek **perlu** dilacak sebagai pembangunan/intervensi (mis. "Jalan Provinsi yang dibangun/dipelihara DPUPR"), dibuat **Jenis peta baru** yang di-custom metadatanya sendiri (mis. "Jalan Provinsi — Intervensi DPUPR") — **bukan** menambah opsi metadata ke Jenis "Jalan Provinsi" yang sudah ada. Jenis lama dan Jenis intervensi-nya bisa dihubungkan lewat `spatial_layer_feature_interventions` yang sudah ada (lihat Referensi).
   - **Jenis hanya berfungsi sebagai referensi/panduan pengisian metadata** — Jenis sendiri tidak menyimpan nilai metadata, cuma mendefinisikan skema (field apa saja, wajib atau opsional). Nilai aktualnya selalu diisi di level Data Spasial saat data itu dibuat.
   - Tiap atribut Metadata Dinamis punya flag **wajib/opsional yang diset per-atribut saat Jenis dibuat** (bukan satu flag global "Metadata Dinamis wajib/opsional" untuk seluruh Jenis) — lihat perubahan skema 1.2.
   - **Data Spasial tetap punya atribut bebas hasil impor** file SHP/KMZ/KML apa adanya (skema-bebas, sama seperti `dbf_attributes` sekarang) — ini **terpisah** dari Metadata Dinamis terstruktur yang didefinisikan Jenis, supaya atribut asli file import tidak tercampur/tertimpa field yang didefinisikan Jenis. Lihat perubahan skema 1.4.
   - **Migrasi penuh dari `project_progress_reports` ke Metadata Dinamis TIDAK termasuk cakupan dokumen ini** — dokumen ini hanya membangun kemampuan (skema + form) yang jadi prasyaratnya. Alur pelaporan progres proyek strategis yang sudah ada tetap berjalan seperti biasa sampai ada dokumen migrasi terpisah.
3. **Struktur tree Layer (parent-child) tidak dibatasi kedalamannya** — beda dari `CategoryController` yang sekarang membatasi 3 level (`level < 2`). Admin bebas menyusun tree sedalam yang dibutuhkan. Satu-satunya batasan yang tetap wajib: mencegah *cycle* (Layer tidak boleh jadi parent dari dirinya sendiri secara langsung maupun berantai) — ini validasi teknis wajib, bukan pembatasan kedalaman.

## 1. Database

### 1.1 `map_types` — Tambah Metadata Utama

```php
Schema::table('map_types', function (Blueprint $table) {
    $table->string('sumber_data')->nullable()->after('deskripsi');
    $table->foreignId('opd_penanggung_jawab_id')->nullable()->after('sumber_data')
        ->constrained('opd')->nullOnDelete();
    $table->date('tanggal_data')->nullable()->after('opd_penanggung_jawab_id');
});
```

Tiga kolom ini **wajib diisi** di level aplikasi (validasi form), bukan di level database (tetap `nullable` supaya baris `map_types` lama tidak pecah) — sesuai desain: "Metadata Utama (Wajib)" berarti wajib saat membuat Jenis baru lewat form, bukan constraint `NOT NULL` yang memaksa migrasi data lama.

### 1.2 `map_type_dynamic_attributes` (baru) — Skema Metadata Dinamis

Menyimpan **definisi/referensi** atribut dinamis per Jenis, bukan nilainya (nilai selalu diisi di Data Spasial saat dibuat, sesuai Keputusan #2 — Jenis cuma panduan):

```php
Schema::create('map_type_dynamic_attributes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('map_type_id')->constrained('map_types')->cascadeOnDelete();
    $table->string('tipe', 20); // 'placeholder' | 'custom'
    $table->string('kode_atribut'); // key di metadata_dinamis jsonb, mis. 'pagu', 'realisasi_anggaran'
    $table->string('label');
    $table->string('satuan')->nullable();
    $table->boolean('is_wajib')->default(false); // wajib/opsional per-atribut, diset saat Jenis dibuat
    $table->unsignedInteger('urutan')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['map_type_id', 'kode_atribut']);
});
```

`tipe = 'placeholder'` untuk 4 pilihan siap pakai di desain (Pagu, Realisasi Anggaran, Realisasi Fisik, Status) — direpresentasikan sebagai baris dengan `kode_atribut` tetap (`pagu`, `realisasi_anggaran`, `realisasi_fisik`, `status`), tinggal diaktifkan per Jenis kalau Jenis itu memang butuh (mis. Jenis "Jalan Provinsi — Intervensi DPUPR", **bukan** Jenis "Jalan Provinsi" biasa — lihat Keputusan #2). `tipe = 'custom'` untuk atribut bebas (nama atribut + satuan diisi manual saat membuat Jenis). `is_wajib` dicentang per baris, bukan pengaturan global.

### 1.3 `spatial_layers` — Tambah `icon` dan `opacity`

```php
Schema::table('spatial_layers', function (Blueprint $table) {
    $table->string('icon')->nullable()->after('color');
    $table->decimal('opacity', 4, 3)->default(1)->after('icon');
});
```

`color`/`is_marker`/`parent_id` sudah ada (Prioritas 1) — cuma dua kolom ini yang kurang untuk memenuhi "Layer: memiliki konfigurasi style (warna, ikon, opacity)" di desain. `parent_id` **tidak** ditambah kolom pembatas kedalaman apa pun (Keputusan #3) — validasi cycle cukup dilakukan di level aplikasi (Bagian 3.2), bukan di skema.

### 1.4 `spatial_layer_features` — Tambah `gambar` dan `metadata_dinamis`

```php
Schema::table('spatial_layer_features', function (Blueprint $table) {
    $table->string('gambar')->nullable()->after('attributes');
    $table->jsonb('metadata_dinamis')->nullable()->after('gambar');
});
```

Dua kolom terpisah dengan tanggung jawab berbeda (Keputusan #2), **jangan dicampur**:
- **`attributes`** (sudah ada sejak Prioritas 2): atribut mentah apa adanya hasil impor SHP/KMZ/KML — skema bebas, ditentukan file sumbernya, bukan oleh Jenis.
- **`metadata_dinamis`** (baru): nilai terstruktur sesuai skema `MapTypeDynamicAttribute` milik Jenis dari Layer tempat Data Spasial ini berada — key-nya selalu `kode_atribut` yang terdaftar di Jenis, divalidasi wajib/opsional saat form submit (Bagian 3.2).

`gambar`: path file (disk `public`, pola sama dengan `data_spatial.gambar`/`categories.gambar` yang sudah ada).

### 1.5 `spatial_feedbacks` (baru)

```php
Schema::create('spatial_feedbacks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('spatial_layer_id')->nullable()->constrained('spatial_layers')->cascadeOnDelete();
    $table->foreignId('spatial_layer_feature_id')->nullable()->constrained('spatial_layer_features')->cascadeOnDelete();
    $table->string('nama_pemberi');
    $table->string('email')->nullable();
    $table->string('phone', 20)->nullable();
    $table->text('pesan');
    $table->string('status', 20)->default('baru'); // baru|ditinjau|ditanggapi
    $table->text('response_admin')->nullable();
    $table->timestamp('responded_at')->nullable();
    $table->timestamps();
});

DB::statement('
    ALTER TABLE spatial_feedbacks
    ADD CONSTRAINT spatial_feedbacks_target_check
    CHECK (
        (spatial_layer_id IS NOT NULL AND spatial_layer_feature_id IS NULL)
        OR (spatial_layer_id IS NULL AND spatial_layer_feature_id IS NOT NULL)
    )
');
```

Constraint `CHECK` di level database memastikan **tepat satu** target terisi (Layer atau Data Spasial, sesuai "umpan balik dari masyarakat pada 1 data spasial ATAU 1 layer" — bukan keduanya, bukan tidak ada) — bukan cuma validasi di form yang bisa dilewati lewat jalur lain.

### 1.6 Migrasi Granularitas Jenis + Rekonsiliasi Data

Dua langkah berurutan, **wajib** sebelum admin mulai memakai Jenis/Layer/Data Spasial sebagai jalur tulis utama (Bagian 6):

1. **Granularisasi Jenis** (menjalankan Keputusan #1): saat ini `spatial_layers` (211 baris) semua menunjuk ke salah satu dari 5 `map_types` kasar lewat `map_type_id`. Command baru `marimoi:granularize-map-types`:
   - Untuk tiap `spatial_layers` yang levelnya paling atas dalam tree kategorinya (akar hierarki, bukan anak), buat satu `map_types` baru dengan `nama` mengikuti nama Layer itu (mis. Layer "Jalan Provinsi" → Jenis baru "Jalan Provinsi").
   - `spatial_layers.map_type_id` untuk Layer itu dan seluruh keturunannya (`parent_id` menurun) di-update menunjuk ke Jenis baru itu.
   - Layer yang levelnya bukan akar (sudah punya parent) **ikut Jenis parent-nya**, tidak dibuatkan Jenis sendiri-sendiri — supaya jumlah Jenis baru masuk akal (seganular kategori induk, sesuai Keputusan #1), bukan 211 Jenis terpisah untuk tiap baris.
   - 5 `map_types` lama **tidak dihapus** — ditandai `is_active = false` kalau sudah tidak ada Layer yang menunjuk ke situ setelah migrasi, tetap ada untuk audit historis.
   - Dijalankan dengan `--dry-run` dulu, output ringkasan berapa Jenis baru dibuat dan daftar nama-nya, **direview manusia sebelum dijalankan sungguhan** — ini tetap keputusan yang menyentuh struktur data produksi, bukan sekadar migration teknis.
2. **Rekonsiliasi backfill**: `spatial_layers`/`spatial_layer_features` adalah backfill satu kali dari `categories`/`data_spatial` per Prioritas 1–2. Setelah langkah 1 selesai, jalankan ulang query backfill yang sama untuk menangkap `categories`/`data_spatial` yang dibuat **setelah** backfill awal (2026-09-27) — pakai `INSERT ... SELECT ... WHERE NOT EXISTS (...)` supaya idempoten. Command baru `marimoi:reconcile-spatial-layers-backfill`, dengan output ringkasan jumlah baris baru yang ter-backfill (baris baru ini otomatis ikut Jenis granular hasil langkah 1, mengikuti `kategori_id` aslinya).

## 2. Model & Relasi

- `MapType` (`app/Models/MapType.php`): tambah `sumber_data`, `opd_penanggung_jawab_id`, `tanggal_data` ke `$fillable`; tambah relasi `dynamicAttributes(): HasMany` ke `MapTypeDynamicAttribute`; tambah relasi `opdPenanggungJawab(): BelongsTo`.
- `MapTypeDynamicAttribute` (baru, `app/Models/MapTypeDynamicAttribute.php`): `belongsTo(MapType::class)`; `$fillable` termasuk `is_wajib`.
- `SpatialLayer`: tambah `icon`, `opacity` ke `$fillable`.
- `SpatialLayerFeature`: tambah `gambar`, `metadata_dinamis` (cast `array`) ke `$fillable`/`casts()` — terpisah dari `attributes` yang sudah ada (lihat 1.4).
- `SpatialFeedback` (baru): `belongsTo(SpatialLayer::class)`, `belongsTo(SpatialLayerFeature::class)`, scope `scopeUntukLayer`/`scopeUntukDataSpasial`.

## 3. Controller & Routing Admin

Tiga controller baru, sesuai 3 submenu di `13-perbaikan-pemetaan.md` — **tidak** menumpuk ke `CategoryController`/`DataSpatialController` yang lama (biarkan tetap ada sampai Bagian 6 selesai, lihat alasan di sana).

### 3.1 "Jenis Layer & Data" — `MapTypeController` (perluasan)

`MapTypeController` yang sudah ada (Prioritas 1, CRUD dasar `map_types`) diperluas:
- Form create/edit menambahkan field Metadata Utama (Sumber Data, OPD Penanggung Jawab, Tahun/Tanggal Data) — wajib diisi di validator.
- Sub-form "Metadata Dinamis": tabel builder untuk menambah/hapus baris `MapTypeDynamicAttribute` (toggle 4 placeholder + tambah custom), **dengan checkbox wajib/opsional per baris** (Keputusan #2) — di halaman yang sama, mengikuti pola submit form tunggal (bukan AJAX terpisah) supaya konsisten dengan modal CRUD `map_types` yang sudah ada.
- Halaman ini murni mendefinisikan **skema/panduan**, tidak ada input nilai — sesuai Keputusan #2 ("Jenis hanya sebagai referensi").

### 3.2 "Daftar Layer & Data" — `SpatialLayerController` + `SpatialLayerFeatureController` (baru)

- `SpatialLayerController`: CRUD `spatial_layers` dengan pemilihan `map_type_id` (Jenis) di form, tree parent-child **tanpa batas kedalaman** (Keputusan #3) — reuse pola `renderCategoryHierarchy()` yang sudah ada di `categories/index.blade.php` tapi **hapus batas `level < 2`**, ganti jadi rekursi murni sampai tidak ada anak lagi. Validasi wajib saat simpan: `parent_id` yang dipilih tidak boleh sama dengan Layer itu sendiri maupun salah satu keturunannya (cegah *cycle* — cek rantai `parent_id` ke atas sebelum simpan, bukan cuma cek langsung satu level).
- Form style: `color`, `icon`, `opacity`, `is_marker`.
- `SpatialLayerFeatureController`: CRUD `spatial_layer_features` — form Data Spasial punya **dua bagian atribut yang terpisah jelas** (Keputusan #2):
  1. **Atribut Impor** (read-only kalau data berasal dari file SHP/KMZ/KML — tampilkan apa adanya dari `attributes`, tidak divalidasi/dibatasi skema apa pun).
  2. **Metadata Dinamis** (form input terstruktur): begitu `spatial_layer_id` dipilih, ambil `map_type_id`-nya lalu tampilkan field sesuai `MapTypeDynamicAttribute` aktif milik Jenis itu (placeholder yang aktif + custom), field dengan `is_wajib = true` divalidasi wajib, sisanya opsional. Kalau Jenis tidak punya `MapTypeDynamicAttribute` aktif sama sekali (kasus normal untuk Jenis non-intervensi, mis. "Jalan Provinsi" polos), bagian ini otomatis kosong/tidak tampil — bukan error.
  - Tersimpan ke kolom `metadata_dinamis` (bukan `attributes`) dengan key = `kode_atribut`. Upload `gambar` juga di form ini.

### 3.3 "Feedback" — `SpatialFeedbackController` (baru)

- Admin: `index()` (list + filter status), `respond()` (isi `response_admin`, set `status`, `responded_at`).
- Publik: endpoint `store()` tanpa auth (mirip `ProjectFeedbackController::store()` yang sudah ada) untuk masyarakat mengirim feedback dari halaman detail Layer/Data Spasial di `/peta-v2`.

## 4. Sidebar & Menu

Di `resources/views/backend/partials/sidebar.blade.php`: ganti label "Peta Tematik" → "Pemetaan", ganti isi submenu dari (`Data Peta Tematik`, `Tampilan Peta`, `Kategori Peta Tematik`, `Jenis Peta`, `Feedback Peta Tematik`) menjadi 3 submenu sesuai desain:
- **Daftar Layer & Data** → `spatial-layers.index` (baru)
- **Jenis Layer & Data** → `map-types.index` (sudah ada, diperluas)
- **Feedback** → `spatial-feedbacks.index` (baru)

Permission katalog (`app/Models/Permission.php`): tambah modul `spatial-layers` (`view`/`create`/`edit`/`delete`) dan `spatial-feedbacks` (`view`/`respond`/`delete`); `map-types.manage` sudah ada, dipakai ulang.

## 5. Tampilan Publik

Perluas `/peta-v2` (`10-plan-peta-skema-baru.md`), bukan bikin halaman baru:
- Render `icon` (bukan cuma warna bulat) dan `opacity` dari `spatial_layers` di `resources/js/peta-v2.js` — saat ini cuma pakai `color`.
- Popup detail feature (`SpatialMapController::featureDetail()`) tambahkan `gambar` (kalau ada), tampilkan `metadata_dinamis` dengan label dari `MapTypeDynamicAttribute` (bukan key mentah), dan `attributes` (atribut impor) ditampilkan terpisah sebagai bagian "Atribut Sumber" — dua bagian, tidak dicampur, konsisten dengan pemisahan di Bagian 1.4/3.2.
- Tombol "Kirim Feedback" di popup detail (Layer atau Data Spasial) → form kecil, submit ke `SpatialFeedbackController::store()` publik.

## 6. Cutover: Jalur Tulis Admin Pindah ke Skema Baru

**Ini konsekuensi paling besar dari dokumen ini** — wajib dibaca sebelum eksekusi Bagian 3.

Begitu "Daftar Layer & Data"/"Jenis Layer & Data" jadi menu admin yang dipakai sehari-hari (Bagian 4), admin akan membuat Layer/Data Spasial baru **langsung** di `spatial_layers`/`spatial_layer_features` — bukan lagi lewat `CategoryController`/`DataSpatialController` yang menulis ke `categories`/`data_spatial`. Ini **membalik** arah sync-gap yang menghentikan Prioritas 8 sebelumnya (dulu: data baru di `data_spatial` tidak terlihat skema baru; sekarang: data baru di `spatial_layers` tidak terlihat sistem lama) — tapi kali ini **sengaja dan terkendali**, karena:

1. Menu admin lama (`Kategori Peta Tematik`, `Data Peta Tematik`) **dihapus dari sidebar** di Bagian 4 — bukan dibiarkan hidup berdampingan tanpa batas waktu seperti draft-draft sebelumnya (`10-plan-peta-skema-baru.md`/`11-plan-dashboard-skema-baru.md` sengaja paralel; dokumen ini **tidak**, sesuai instruksi eksplisit `13-perbaikan-pemetaan.md` yang mengganti nama & isi menu, bukan menambah menu baru di sampingnya).
2. Publik (`/peta-tematik`, `FrontendController`) **tetap** membaca `categories`/`data_spatial` sampai dipindah terpisah — artinya **setelah cutover ini, data baru yang diinput admin lewat menu baru TIDAK akan muncul di `/peta-tematik` publik** sampai `FrontendController` juga dipindah ke `/peta-v2` sebagai pengganti (di luar cakupan dokumen ini, harus jadi tindak lanjut segera, dicatat sebagai risiko di bawah).
3. `CategoryController`/`DataSpatialController` **tidak dihapus** dari kode — cuma link menunya yang dicabut, PHPDoc ditandai `@deprecated` (pola sama seperti Prioritas 8), supaya data lama tetap bisa diaudit dan controller bisa dihidupkan lagi kalau ternyata cutover perlu dibatalkan.

**Urutan wajib**: Bagian 1.6 (granularisasi + rekonsiliasi backfill) → Bagian 3 (controller baru, diuji tanpa mencabut menu lama dulu) → QA paralel (admin coba menu baru berdampingan dengan lama, bandingkan) → **baru** Bagian 4 (cabut menu lama) dijalankan.

## 7. Testing

| Bagian | File test (baru) | Skenario minimal |
| --- | --- | --- |
| 1.1–1.2 | `MapTypeDynamicAttributeTest` | Toggle placeholder aktif/nonaktif; tambah custom attribute; `kode_atribut` unik per Jenis; `is_wajib` tersimpan per baris |
| 1.5 | `SpatialFeedbackTest` (model) | `CHECK` constraint menolak insert tanpa target maupun dengan dua target sekaligus |
| 1.6 | `GranularizeMapTypesTest` | Layer akar dapat Jenis baru sendiri; Layer anak ikut Jenis parent-nya, tidak dibuatkan Jenis baru; `--dry-run` tidak mengubah data; idempoten dijalankan dua kali |
| 3.1 | `MapTypeMetadataTest` | Simpan Metadata Utama; validator menolak submit tanpa Sumber Data/OPD/Tanggal |
| 3.2 | `SpatialLayerControllerTest` | CRUD lewat HTTP; tree tanpa batas kedalaman tersimpan benar; parent_id yang membentuk cycle (langsung maupun berantai) ditolak validasi |
| 3.2 | `SpatialLayerFeatureControllerTest` | `attributes` (impor) dan `metadata_dinamis` (terstruktur) tersimpan di kolom terpisah, tidak saling menimpa; field `is_wajib=true` yang kosong ditolak; Jenis tanpa `MapTypeDynamicAttribute` aktif tidak memaksa isian apa pun |
| 3.3 | `SpatialFeedbackControllerTest` | Submit publik tanpa auth berhasil; admin `respond()` mengubah status |
| 5 | `PetaV2StyleRenderTest` (opsional, bisa manual) | `icon`/`opacity` terkirim di response `/peta-v2/layers`; popup detail memisahkan `attributes` vs `metadata_dinamis` |
| 6 | Regresi penuh sebelum & sesudah cabut menu | `php artisan test --compact` — baseline gagal tidak boleh bertambah |

## 8. Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Granularisasi Jenis (1.6) membuat terlalu banyak/terlalu sedikit Jenis kalau aturan "ikut Jenis parent" salah terap pada hierarki yang tidak rapi | `--dry-run` wajib direview manusia sebelum dijalankan sungguhan — bukan langsung eksekusi |
| Tree Layer tanpa batas kedalaman rawan *cycle* kalau validasi parent lupa dipasang | Validasi cycle wajib di `SpatialLayerController` (Bagian 3.2) — cek seluruh rantai `parent_id`, bukan cuma satu level, dites eksplisit di `SpatialLayerControllerTest` |
| Admin bingung membedakan "Atribut Impor" vs "Metadata Dinamis" di form Data Spasial | Dua bagian dipisah jelas di UI (Bagian 3.2/5), bukan digabung dalam satu daftar |
| Metadata Dinamis disalahpahami sebagai pengganti langsung `project_progress_reports` sehingga tim mulai mengabaikan alur pelaporan progres yang ada | Dicatat eksplisit di Keputusan #2: migrasi penuh dari `project_progress_reports` **di luar cakupan dokumen ini** — alur lama tetap jalan sampai ada dokumen migrasi terpisah |
| `/peta-tematik` publik "kehilangan" data baru setelah cutover (poin 2 di Bagian 6) | Dicatat eksplisit sebagai tindak lanjut wajib segera, bukan dianggap selesai di dokumen ini — jangan cutover Bagian 4 tanpa rencana konkret pemindahan `FrontendController` |
| `CHECK` constraint `spatial_feedbacks` gagal di lingkungan yang belum support (versi Postgres lama) | Proyek ini sudah pakai fitur Postgres modern lain (jsonb, PostGIS) — risiko diabaikan, tapi tetap dites di migration test |

## 9. Checklist Sebelum Menganggap Selesai

- [x] Keputusan granularitas Jenis, Metadata Dinamis, dan kedalaman tree dikunci (2026-09-30).
- [ ] Bagian 1–5 diimplementasikan & diuji per bagian (bukan sekaligus).
- [ ] Bagian 1.6 (granularisasi + rekonsiliasi backfill) dijalankan dengan `--dry-run` direview manusia dulu, tepat sebelum Bagian 6.
- [ ] Regresi penuh hijau (baseline gagal tidak bertambah) sebelum dan sesudah Bagian 6.
- [ ] Rencana pemindahan `FrontendController`/`/peta-tematik` publik dicatat sebagai tindak lanjut (dokumen terpisah), bukan diabaikan.
- [ ] Rencana migrasi penuh dari `project_progress_reports` ke Metadata Dinamis dicatat sebagai dokumen terpisah di masa depan, bukan bagian dari dokumen ini.
