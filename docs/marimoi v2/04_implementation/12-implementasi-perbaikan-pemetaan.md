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
