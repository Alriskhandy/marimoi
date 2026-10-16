# MARIMOI Specification — Dashboard Admin: Manajemen Peta

> **Versi dokumen:** 1.1
> **Tanggal:** 3 Oktober 2026
> **Ruang lingkup:** Halaman dashboard admin untuk mengelola peta (katalog, layer, metadata, sumber data, impor, style, fitur spasial).
> **Basis:** skema database v3 yang **sudah terimplementasi** (lihat `db-schema-v3.md` dan plan migrasi `mellow-weaving-eclipse`, Fase 0–6 selesai).
>
> **Perubahan v1.1:** 8 dari 9 pertanyaan terbuka di §10 sudah dijawab dan dipindahkan ke §9 Decisions (D13–D19) — termasuk persetujuan me-retire modul `Data Spasial` lama, kewenangan publish, dan kolom kepemilikan OPD pada layer.

---

## 1. Vision

MARIMOI adalah WebGIS satu pintu Pemerintah Provinsi Maluku Utara: publik melihat peta tematik pembangunan, admin mengelola datanya.

Dashboard admin harus menjadi **satu-satunya tempat** mengelola seluruh siklus hidup data peta — dari membuat katalog, membuat layer, mengunggah data spasial, memetakan atributnya, mengatur simbolisasi, mengisi metadata, sampai mempublikasikan ke peta publik — tanpa perlu menyentuh database secara manual dan tanpa dua UI paralel untuk data yang sama.

Kondisi saat ini: skema v3 sudah jadi dan datanya sudah pindah (176 layer, 12.393 fitur), tapi dashboard admin **baru memakai sebagian kecil** dari skema itu. Empat tabel inti masih kosong sepenuhnya karena belum ada UI-nya:

| Tabel | Baris saat ini | Artinya |
|---|---|---|
| `layers` | 176 | Terpakai |
| `spatial_features` | 12.393 | Terpakai |
| `layer_styles` | 176 | Terpakai (hanya style `simple` bawaan migrasi) |
| `layer_metadata` | **0** | Form metadata ada, tapi aksesnya lewat id kategori lama & belum pernah terisi |
| `layer_imports` | **0** | Impor file berjalan, tapi **tidak tercatat** sebagai riwayat |
| `layer_attribute_mappings` | **0** | Pemetaan kolom file → atribut belum ada |
| `layer_sources` | **0** | Layer layanan (WMS/XYZ/ArcGIS/COG) belum bisa didaftarkan sama sekali |

Tujuan pekerjaan ini: menutup jarak antara skema v3 dan dashboard admin, sekaligus menghapus UI ganda (`Data Spasial` lama vs `Daftar Layer & Data` baru).

---

## 2. Users & Roles

Peran memakai `roles.slug` yang sudah ada; kewenangan ditegakkan permission `{modul}.{aksi}` (lihat `App\Models\Permission::CATALOG`).

| Peran | Kewenangan terhadap manajemen peta |
|---|---|
| `super-admin` | Semua modul, semua aksi. Boleh mengunggah layer untuk OPD mana pun dan **mengubah status** layer (draft ↔ published ↔ archived). Lolos semua pengecekan lewat `Gate::before`. |
| `admin-bappeda` | Kelola katalog, layer, metadata, impor, style untuk **semua OPD**, dan **satu-satunya peran selain super-admin yang boleh mengubah status layer**. Pemilik proses kurasi data lintas OPD. |
| `admin-opd` | Hanya **baca** katalog. Membuat, mengubah, dan mengimpor data pada layer **milik OPD-nya sendiri** (`layers.opd_id` = OPD user). Layer buatannya selalu berstatus `draft`; **tidak boleh** mengubah status apa pun. |
| `user` | Hanya melihat dashboard; tidak mengelola peta. |
| Publik (tanpa login) | Hanya melihat layer `published` + `public` di peta publik. Tidak masuk cakupan dokumen ini. |

Permission yang relevan:

- `categories.view|create|edit|delete` — Kategori Peta
- `spatial-layers.view|create|edit|delete` — Daftar Layer & Data
- **`spatial-layers.publish`** — ubah status layer (**baru**, lihat D15). Default hanya untuk `super-admin` & `admin-bappeda`.
- `map-types.manage` — Jenis Peta (master atribut dinamis)
- `spatial-feedbacks.view|respond|delete` — Feedback Pemetaan
- ~~`data-spatial.*`~~ — modul lama, **akan dihapus** bersama retirement modul `Data Spasial` (D13)

**Bagaimana OPD seorang user ditentukan:** lewat kolom `users.opd_id` yang sudah ada (relasi `User::opd()`). Pembatasan `admin-opd` membandingkan `users.opd_id` dengan `layers.opd_id` (kolom baru, lihat D16).

---

## 3. Core Concepts

### Catalog (Katalog / Kategori) — `categories_v3`
Kelompok akar untuk menemukan layer, misalnya *Tata Ruang*, *Kebencanaan*, *Infrastruktur*. Punya `code`, `name`, `slug`, `icon`, `color`, `type`, `is_active`, dan urutan tampil. Kolom `type` adalah proxy ke `map_types.slug` — penentu set atribut dinamis yang berlaku (lihat *Map Type*).

### Catalog Node (Subkategori) — `category_nodes`
Pohon subkategori **di dalam satu katalog**, dengan `path` ltree + `depth`. Sebuah node selalu milik tepat satu katalog (`category_id`), dan induknya wajib berada di katalog yang sama (dijaga FK komposit).

Skema mendukung kedalaman tak terbatas, tetapi **UI dibatasi 3 level** (katalog → subkategori → sub-subkategori) sesuai D14.

Di aplikasi, katalog + node dibaca sebagai **satu pohon datar** lewat view SQL `categories_tree_v3`, sehingga model `Category` tetap bisa dipakai dengan pola `parent_id` seperti versi lama. Penulisan (`create`/`update`/`delete`) diarahkan model ke tabel yang benar: akar → `categories_v3`, turunan → `category_nodes`.

### Layer — `layers`
Objek GIS utama, satuan yang dinyalakan/dimatikan pengunjung di peta. Satu layer:
- berada di **tepat satu katalog** (`category_id` wajib), opsional menunjuk satu node (`category_node_id`)
- punya **satu jenis layer** (`layer_type_id`)
- punya `code`/`name`/`slug` unik, `status` (`draft`/`published`/`archived`), `visibility` (`public`/`internal`/`private`)
- punya **satu OPD pemilik** (`opd_id`, kolom baru — lihat D16); `NULL` berarti milik provinsi/Bappeda
- punya cache `feature_count` + `bbox` untuk *zoom to layer*
- punya `map_type_id` (deviasi dari dokumen skema) sebagai penentu atribut dinamis
- punya satu style default (`default_style_id`)

### Layer Type — `layer_types`
Tabel referensi (10 baris seed): `vector_point`, `vector_line`, `vector_polygon`, `vector_mixed`, `raster_cog`, `service_wms`, `service_wmts`, `service_xyz`, `service_arcgis`, `service_vector_tile`.

Kolom kuncinya `stores_features`: `true` berarti fiturnya disimpan di `spatial_features` (layer vektor), `false` berarti layer dirender langsung dari sumber eksternal (raster/service) sehingga **wajib** punya `layer_sources` dan **tidak boleh** punya fitur.

Saat ini seluruh 176 layer bertipe vektor; jenis raster/service belum bisa dipakai karena `layer_sources` belum ada UI-nya.

### Metadata — `layer_metadata`
Metadata deskriptif 1:1 per layer, mengacu ISO 19115 / Satu Data Indonesia: `title`, `abstract`, `purpose`, `keywords`, `producer_organization`, narahubung, `data_year`, `reference_date`, `update_frequency`, `scale_denominator`, `license`, `lineage`, `use_constraints`, plus `extra` jsonb.

Berbeda dari **atribut dinamis** (`map_type_dynamic_attributes` + `metadata_definitions`) yang mendeskripsikan **tiap fitur** di dalam layer. Metadata mendeskripsikan **dataset-nya**; atribut dinamis mendeskripsikan **barisnya**.

### Source — `layer_sources`
Asal data layer: `file_upload` (hasil unggahan) atau layanan eksternal (`wms`, `wmts`, `wfs`, `xyz`, `arcgis_rest`, `geojson_url`, `vector_tile`, `cog_url`, `database`). Menyimpan `url`, `service_layer_name`, `format`, `crs`, `auth_type`, `credential_ref`, `options`, dan `health_status`.

Kredensial **tidak** disimpan di database — `credential_ref` hanya menunjuk ke secret store/env.

### Import — `layer_imports`
Riwayat satu proses impor file ke `spatial_features`. Karena skema ini sengaja tidak punya `layer_versions`, **tabel inilah satu-satunya riwayat perubahan data layer**: file asli, checksum, SRID sumber, encoding, mode (`replace`/`append`), status, `detected_fields`, jumlah fitur berhasil/gagal, dan log kegagalan per baris.

`layer_attribute_mappings` melengkapinya: pemetaan nama kolom di file sumber → atribut standar, per impor, dengan aturan `transform` opsional.

---

## 4. Business Rules

Aturan yang tidak boleh dilanggar. Yang sudah ditegakkan database ditandai **[DB]**; yang harus ditegakkan aplikasi ditandai **[APP]**.

| No | Aturan | Penegak |
|---|---|---|
| R1 | Satu layer berada di tepat satu katalog. Jika `category_node_id` diisi, node itu wajib milik katalog yang sama. | **[DB]** FK komposit `(category_node_id, category_id)` |
| R2 | Node induk wajib berada di katalog yang sama dengan node anak. | **[DB]** FK komposit `category_nodes (parent_id, category_id)` |
| R3 | Node tidak boleh menjadi induk dirinya sendiri atau keturunannya. | **[DB]** `CHECK` + **[APP]** validasi hierarki |
| R4 | Satu layer hanya punya satu style default, dan style itu milik layer tersebut. | **[DB]** partial unique index + FK komposit |
| R5 | Fitur spasial wajib merujuk impor dari layer yang sama. | **[DB]** FK komposit `(layer_import_id, layer_id)` |
| R6 | Geometri wajib valid, tidak kosong, dan ber-SRID 4326. | **[DB]** tipe kolom + `CHECK ST_IsValid` + `CHECK ST_SRID = 4326` |
| R7 | Layer `published` wajib punya `published_at`. | **[DB]** `CHECK` |
| R8 | Layer vektor tidak boleh dipublikasikan tanpa minimal satu fitur dan satu style default. | **[APP]** validasi sebelum publish — **belum ada** |
| R9 | Layer dengan `layer_type.stores_features = false` (raster/service) wajib punya `layer_sources` primary, dan tidak boleh punya `spatial_features`. | **[APP]** — **belum ada** |
| R10 | Layer vektor yang fiturnya berasal dari file wajib punya baris `layer_imports`. | **[APP]** — **belum ada**, impor saat ini tidak tercatat |
| R11 | Kredensial layanan eksternal tidak boleh tersimpan di database. | **[APP]** hanya `credential_ref` |
| R12 | Impor `replace` mengganti seluruh fitur layer **dalam satu transaksi** — peta publik tidak boleh pernah menampilkan data setengah jadi. | **[APP]** |
| R13 | Menampilkan/menyembunyikan layer di peta **tidak menulis apa pun** ke database. Peta adalah konfigurasi tampilan di client, bukan tempat menyimpan layer baru. | Desain |
| R14 | Layer yang dibuat user selalu berasal dari katalog yang sudah tersedia — user tidak membuat katalog secara implisit saat membuat layer. | **[DB]** `category_id NOT NULL` + **[APP]** |
| R15 | Jenis Peta (`map_type_id`) layer menentukan set atribut dinamis yang berlaku untuk fitur-fiturnya. | **[APP]** |
| R16 | Nilai atribut dinamis disimpan di `spatial_features.properties` dengan kunci `metadata_definitions.kode`, bergabung dengan atribut mentah hasil impor. | **[APP]** |
| R17 | **Perubahan status layer** (`draft` ↔ `published` ↔ `archived`) hanya boleh dilakukan `super-admin` dan `admin-bappeda`, lewat permission `spatial-layers.publish`. | **[APP]** — **belum ada** |
| R18 | `feature_count` dan `bbox` layer wajib dihitung ulang setiap kali fiturnya berubah (impor, tambah/ubah/hapus fitur). | **[APP]** |
| R19 | `admin-opd` hanya boleh melihat, mengubah, mengimpor, dan menghapus layer dengan `layers.opd_id` = `users.opd_id` miliknya. Layer OPD lain maupun layer tanpa OPD (`NULL`) tidak boleh diubahnya. | **[APP]** — **belum ada** |
| R20 | Layer yang dibuat `admin-opd` otomatis berstatus `draft` dan `opd_id` otomatis diisi OPD user tersebut — tidak bisa dipilih bebas. | **[APP]** |
| R21 | `super-admin`/`admin-bappeda` boleh mengunggah layer untuk OPD mana pun, termasuk mengubah `opd_id` layer yang sudah ada. | **[APP]** |
| R22 | Ukuran file impor maksimal **100 MB** per unggahan; jumlah fitur tidak dibatasi angka tetap, melainkan dibatasi oleh batas ukuran file tersebut. | **[APP]** validasi unggahan + konfigurasi server |

---

## 5. Features

### 5.1 Catalog (Kategori Peta)

**Sudah ada:** halaman daftar hierarkis 3 level dengan modal tambah/ubah (AJAX), hapus, upload gambar, warna, ikon, penanda marker, status aktif, batas maksimal 10 kategori aktif per tipe, cascading select 3 level (`categories.api.options`), dan badge jumlah data per kategori (dihitung dari `layers.feature_count`).

**Perbaikan yang dibutuhkan:**
1. ~~Kedalaman hierarki~~ — **tetap 3 level** (D14). Batas ini harus divalidasi eksplisit di server (bukan hanya disembunyikan di UI) dan pesan errornya jelas saat admin mencoba menambah level ke-4.
2. **Pemindahan node (move)** — belum ada cara memindahkan subkategori ke induk lain; `path`/`depth` harus ikut diperbarui untuk seluruh keturunannya, dan hasil pemindahan tidak boleh melewati batas 3 level.
3. **Urutan tampil (`sort_order`)** — kolomnya ada tapi tidak bisa diatur dari UI (drag & drop atau input angka).
4. **Panel isi katalog** — dari satu katalog/node, admin harus bisa langsung melihat & membuka layer di dalamnya (saat ini harus pindah ke halaman Daftar Layer lalu memfilter).
5. **Hapus aman** — sudah diblokir jika punya subkategori atau layer terkait; pesan errornya perlu menyebut **jumlah dan nama** layer yang menghalangi.

### 5.2 Layer

**Sudah ada:** daftar layer (flat + kolom breadcrumb kategori), form tambah/ubah (kategori + subkategori lewat cascading picker, nama, deskripsi singkat, Jenis Peta, warna, ikon, opacity, penanda marker, status aktif), halaman detail dengan daftar fitur, hapus, dan bulk ubah Jenis Peta.

**Perbaikan yang dibutuhkan:**
1. **Jenis layer (`layer_type_id`)** — belum bisa dipilih di form; semua layer baru dipaksa `vector_mixed` (id 4). Harus bisa dipilih, dan pilihan itu menentukan validasi geometri serta ketersediaan tab Sumber/Impor.
2. **Status & publikasi** — `status` (`draft`/`published`/`archived`) saat ini hanya turunan dari checkbox "aktif". Perlu aksi publish/unpublish/arsip eksplisit dengan validasi R8, pencatatan `published_at`, dan dibatasi permission `spatial-layers.publish` (R17). Bagi `admin-opd`, kontrol status tidak boleh tampil sama sekali — bukan hanya ditolak saat disubmit.
3. **Visibility** — `public`/`internal`/`private` belum ada di UI sama sekali.
4. **OPD pemilik (`opd_id`)** — kolom baru (D16). Untuk `super-admin`/`admin-bappeda` tampil sebagai dropdown OPD yang bisa dipilih bebas; untuk `admin-opd` terisi otomatis dari OPD-nya dan tidak bisa diubah (R20–R21).
5. **Properti tampilan peta** — `is_default_on`, `is_queryable`, `is_downloadable`, `min_zoom`, `max_zoom`, `sort_order` belum ada di form, padahal semuanya dipakai peta publik.
6. **Zoom to layer** — `bbox` sudah tersimpan tapi belum dipakai; halaman detail layer semestinya menampilkan pratinjau peta yang langsung ter-zoom ke cakupan layer.
7. **Filter & pencarian** — daftar layer butuh filter per katalog/node, Jenis Peta, jenis layer, status, **OPD pemilik**, dan pencarian nama (indeks trigram `ix_layers_name_trgm` sudah tersedia). Untuk `admin-opd`, daftar otomatis terbatas pada layer OPD-nya (R19).

### 5.3 Metadata

**Sudah ada:** form metadata (`categories/{id}/metadata`) yang menulis ke `layer_metadata`, memetakan "Nama Sumber Data" → `producer_organization`, serta `source_url`/`attribution` ke `extra` jsonb.

**Perbaikan yang dibutuhkan:**
1. **Pindahkan ke konteks layer** — rutenya masih di bawah `categories/{id}` dan mencari layer lewat `legacy_category_id`. Pasca-cutover ini menyesatkan; metadata milik **layer**, jadi harus berada di `spatial-layers/{layer}/metadata`.
2. **Lengkapi field** — baru ~5 field terpakai dari ~20 kolom yang tersedia. `keywords`, `topic_category`, `scale_denominator`, `reference_date`, `date_type`, `positional_accuracy`, `administrative_area`, `lineage`, `license`, `use_constraints` belum ada di form.
3. **Indikator kelengkapan** — tampilkan persentase/kelengkapan metadata per layer di daftar layer, supaya kurator tahu dataset mana yang belum layak publish.

### 5.4 Source (Sumber Data)

**Belum ada sama sekali.** Yang dibutuhkan:
1. CRUD `layer_sources` per layer: pilih `source_type`, isi `url`, `service_layer_name`, `format`, `crs`, `options` jsonb.
2. Penandaan satu sumber `is_primary` per layer (sudah dijaga partial unique index).
3. Tombol **uji koneksi** yang mengisi `health_status` + `last_checked_at`.
4. Pengisian `credential_ref` sebagai referensi env/secret store, dengan peringatan eksplisit di UI bahwa kredensial mentah tidak boleh dimasukkan (R11).
5. Konsekuensinya: layer bertipe `service_*`/`raster_cog` menjadi bisa dipakai — peta publik merender langsung dari sumber, tanpa `spatial_features`.

### 5.5 Import (Impor Data Spasial)

**Sudah ada:** unggah Shapefile (`.shp`+`.shx`+`.dbf`), KMZ/KML, dan input koordinat manual lewat `SpatialLayerFeatureController@store` + `SpatialGeometryBatchImporter`. Validasi koordinat, perbaikan dimensi geometri, dan pembersihan DBF sudah berjalan.

**Perbaikan yang dibutuhkan — ini bagian terbesar:**
1. **Catat sebagai `layer_imports`** — setiap impor harus menghasilkan satu baris riwayat: nama file asli, `storage_path`, `file_format`, ukuran, `checksum_sha256`, `source_srid`, `encoding`, `import_mode`, status, `total_features`/`imported_features`/`failed_features`, dan `log` kegagalan per baris. Tanpa ini tidak ada jejak sama sekali atas 12.393 fitur yang ada.
2. **Simpan file sumbernya** — file asli harus tersimpan supaya impor bisa dijalankan ulang (pengganti rollback versi, lihat §8.2 dokumen skema).
3. **Langkah pemetaan kolom** — setelah file dibaca, tampilkan `detected_fields` dan biarkan admin memetakan tiap kolom ke atribut standar (`metadata_definitions`) atau menandainya diabaikan; simpan sebagai `layer_attribute_mappings`. Saat ini kolom DBF masuk mentah ke `properties` apa adanya.
4. **Mode `replace` vs `append`** — saat ini selalu menambah. Mode `replace` (hapus seluruh fitur layer lalu isi ulang, satu transaksi, R12) wajib ada untuk perbaikan data.
5. **Format tambahan** — GeoJSON, GeoPackage, dan CSV berkoordinat sudah masuk `CHECK` kolom `file_format` tapi belum didukung importer.
6. **Impor besar via queue** — impor ribuan fitur harus berjalan di background job dengan status yang bisa dipantau (`pending` → `uploaded` → `mapping` → `processing` → `completed`/`failed`), bukan menahan request HTTP.
7. **Laporan hasil** — halaman riwayat impor per layer: siapa, kapan, berapa berhasil/gagal, dan unduh log kegagalan.
8. **Batas ukuran 100 MB** (R22, D18) — validasi di sisi aplikasi *dan* prasyarat konfigurasi server (`upload_max_filesize`, `post_max_size` di PHP; `client_max_body_size` di Nginx). Pesan error harus menyebut batas dan ukuran file yang dikirim, bukan sekadar "gagal unggah". Tidak ada chunked upload — unggahan tunggal sampai 100 MB.

### 5.6 Style (Simbolisasi)

**Sudah ada:** satu style `simple` per layer, ditulis implisit dari field warna/ikon/marker/opacity di form layer.

**Perbaikan yang dibutuhkan:**
1. **Style `categorized`/`graduated`** — skema `layer_styles` sudah mendukung (`classification_field`, `definition`, `legend`), tapi UI belum. Ini kebutuhan nyata untuk peta tematik (mis. warna per fungsi kawasan).
2. **Beberapa style per layer** + pemilihan default (sudah dijaga unique index).
3. **Legenda** — `legend` jsonb belum pernah diisi, padahal peta publik butuh legenda yang konsisten.
4. **Pratinjau** — perubahan style harus bisa dilihat langsung di peta mini sebelum disimpan.

### 5.7 Feature (Data Spasial per Fitur)

**Sudah ada:** tambah/ubah/hapus fitur satuan, form atribut dinamis sesuai Jenis Peta layer (`map_type_dynamic_attributes` → `metadata_definitions`), unggah gambar per fitur, dan penggabungan atribut mentah + atribut dinamis ke `properties`.

**Perbaikan yang dibutuhkan:**
1. **Tabel fitur yang bisa dicari/difilter/dipaginasi** — layer dengan ribuan fitur saat ini sulit ditelusuri.
2. **Edit geometri di peta** — saat ini geometri hanya bisa diisi sebagai WKT/koordinat; butuh editor di peta (draw/edit/snap).
3. **Bulk edit atribut** — mengubah satu atribut untuk banyak fitur sekaligus (ada di modul lama, belum ada di modul baru).
4. **Penanda wilayah (`region_id`)** — kolomnya dipakai peta publik tapi belum bisa diisi dari UI.

### 5.8 Map Type (Jenis Peta & Atribut Dinamis)

**Sudah ada:** CRUD `map-types` + pemilihan atribut dinamis per Jenis Peta dari pustaka `metadata_definitions` (11 definisi), dengan penanda wajib/urutan/aktif, dan pencarian definisi.

**Perbaikan yang dibutuhkan:**
1. **Dampak perubahan** — saat atribut dinamis ditambah/dinonaktifkan, tampilkan berapa layer & fitur yang terpengaruh sebelum menyimpan.
2. **Validasi nilai** — `metadata_definitions.validasi` jsonb belum ditegakkan saat menyimpan fitur.
3. **Atribut `is_filterable`** — penandanya ada tapi belum dipakai membentuk filter di peta publik maupun admin.

### 5.9 Retirement modul `Data Spasial` lama (disetujui, D13)

**Kondisi saat ini:** `DataSpatialController` (17 rute, ±1.900 baris) masih menjadi UI aktif untuk data yang sama, menulis ke `data_spatial_legacy_v1`, dan hasilnya direplikasi ke `spatial_features` oleh jembatan `SpatialFeaturesV3Sync` (D8).

**Yang harus dikerjakan:**
1. **Pindahkan kemampuan yang belum ada di modul baru** sebelum rute lama dimatikan — khususnya *bulk edit atribut* (§5.7 butir 3) dan validasi metadata wajib (`marimoi.metadata_wajib_sejak`) yang saat ini hanya ada di modul lama.
2. **Redirect rute lama** `data-spatial.*` dan `tematik.*` ke padanannya di `spatial-layers.*` (bukan dihapus langsung, supaya bookmark admin tidak mati).
3. **Hapus menu `Data Spasial`** dari sidebar admin; sisakan satu menu *Daftar Layer & Data*.
4. **Hapus jembatan `SpatialFeaturesV3Sync`** beserta pemasangannya di `AppServiceProvider` — setelah tidak ada lagi tulis ke `data_spatial_legacy_v1`, jembatan ini jadi kode mati.
5. **Hapus permission `data-spatial.*`** dari katalog permission dan dari role yang memilikinya.
6. **Tulis ulang / hapus test yang menguji perilaku lama** — terutama test fungsional `DataSpatial\MetadataDatasetTest` dan `DataSpatialGeojsonTest` yang memanggil `data-spatial.store`/`geojson`. **Penghapusan test ini sudah disetujui** sebagai bagian dari keputusan D13; perilaku yang masih relevan (validasi metadata wajib) harus punya test penggantinya di modul baru sebelum test lama dihapus.
7. **Keputusan terpisah yang tetap ditunda:** tabel `data_spatial_legacy_v1` **tidak** di-drop (D17) — hanya berhenti ditulis.

---

## 6. User Flow

### 6.1 Menyiapkan katalog (sekali, oleh `admin-bappeda`)

```
Kategori Peta → Tambah Kategori (akar)
  → isi nama, tipe (Jenis Peta), ikon, warna, status aktif
  → Tambah Subkategori di bawahnya (opsional, bertingkat)
```

### 6.2 Membuat layer vektor dari file (alur utama)

```
1. Daftar Layer & Data → Tambah Layer
     pilih Katalog (+ Subkategori), Jenis Layer, Jenis Peta, OPD pemilik
     isi nama, deskripsi singkat, simbolisasi awal
     → layer tersimpan status DRAFT
     (admin-opd: OPD pemilik terisi otomatis, tidak bisa dipilih)
2. Tab Metadata → isi metadata dataset (produsen, tahun, lisensi, dst.)
3. Tab Impor → unggah file (SHP-zip / GeoJSON / KML / KMZ / GPKG / CSV)
     → sistem membaca file: detected_fields, SRID, jumlah fitur   [status: mapping]
4. Pemetaan kolom → kolom file dipetakan ke atribut standar atau diabaikan
     → disimpan sebagai layer_attribute_mappings
5. Pilih mode impor (replace / append) → Jalankan
     → background job: transform ke 4326, ST_MakeValid, isi properties,
       insert spatial_features, hitung ulang feature_count & bbox   [status: processing → completed]
6. Tab Style → atur simbolisasi (simple / categorized / graduated) + legenda
7. Pratinjau layer di peta (zoom to bbox) → periksa hasil
8. Publish (hanya super-admin / admin-bappeda, permission spatial-layers.publish)
     → validasi: ada fitur, ada style default, metadata minimum terisi
     → status PUBLISHED, published_at terisi, layer tampil di peta publik
```

### 6.3 Membuat layer layanan eksternal (WMS/XYZ/ArcGIS)

```
1. Tambah Layer → Jenis Layer = service_wms (atau lainnya)
2. Tab Sumber → isi URL, nama layer layanan, format, CRS, credential_ref
     → Uji Koneksi → health_status = ok
3. Tab Metadata → isi metadata
4. Publish   (tab Impor & Fitur tidak berlaku untuk jenis ini)
```

### 6.4 Memperbaiki data layer yang salah

```
Tab Impor → Riwayat Impor → unggah file yang benar, mode = replace
  → seluruh fitur layer diganti dalam satu transaksi
  → riwayat impor lama tetap tersimpan (file + mapping) untuk audit/ulang
```

### 6.5 Alur dua peran: OPD menyiapkan, Bappeda mempublikasikan (R17, R20)

```
admin-opd                                     super-admin / admin-bappeda
─────────                                     ──────────────────────────
Tambah Layer
  opd_id terisi otomatis (OPD-nya)
  status otomatis DRAFT
Impor data + isi metadata + atur style
  → selesai, layer tetap DRAFT
  (kontrol status tidak tampil untuk peran ini)
                                      →   Daftar Layer, filter status = draft
                                          Tinjau data, metadata, style
                                          Publish  → status PUBLISHED
                                          atau kembalikan ke OPD lewat catatan
```

Layer yang sudah `published` tetap bisa diubah datanya oleh OPD pemiliknya (impor ulang, perbaikan atribut), tetapi **statusnya** tidak bisa ia turunkan kembali.

### 6.6 Menanggapi feedback publik

```
Feedback Pemetaan → pilih feedback → tinjau layer/fitur yang dilaporkan
  → perbaiki data bila perlu → tulis tanggapan → status: ditanggapi
```

---

## 7. Acceptance Criteria

Fitur dianggap selesai bila seluruh kriteria berikut terpenuhi dan **tertutup test otomatis** (PHPUnit, feature test).

### Katalog
- [ ] Katalog & subkategori bisa dibuat, diubah, dipindah induknya, diurutkan, dan dihapus dari UI.
- [ ] Memindahkan subkategori memperbarui `path` & `depth` seluruh keturunannya.
- [ ] Hapus katalog/node yang masih punya turunan atau layer ditolak dengan pesan yang menyebut penghalangnya.

### Layer
- [ ] Jenis layer, visibility, `opd_id`, `is_default_on`, `is_queryable`, `is_downloadable`, `min_zoom`/`max_zoom`, dan `sort_order` bisa diatur dari UI.
- [ ] Publish ditolak bila layer vektor tidak punya fitur atau tidak punya style default (R8).
- [ ] Layer `service_*`/`raster_cog` tidak bisa dipublikasikan tanpa sumber primary (R9).
- [ ] Daftar layer bisa difilter per katalog, Jenis Peta, jenis layer, status, OPD pemilik, dan dicari per nama.

### Kewenangan (R17, R19–R21)
- [ ] `admin-opd` tidak bisa mengubah status layer lewat cara apa pun — kontrolnya tidak dirender, dan request langsung ke endpoint pun ditolak 403.
- [ ] `super-admin` dan `admin-bappeda` bisa mengubah status layer (publish/unpublish/arsip) dan `published_at` tercatat benar.
- [ ] Layer baru buatan `admin-opd` otomatis `draft` dengan `opd_id` = OPD-nya, tanpa bisa memilih OPD lain.
- [ ] `admin-opd` tidak bisa melihat/mengubah/menghapus/mengimpor layer milik OPD lain maupun layer dengan `opd_id` kosong (403 / tidak muncul di daftar).
- [ ] `super-admin`/`admin-bappeda` bisa membuat layer untuk OPD mana pun dan memindahkan kepemilikan layer antar-OPD.
- [ ] Permission `spatial-layers.publish` terdaftar di katalog permission dan ter-seed hanya untuk `super-admin` & `admin-bappeda`.

### Metadata
- [ ] Form metadata berada di bawah layer (`spatial-layers/{layer}/metadata`), bukan di bawah id kategori lama.
- [ ] Seluruh kolom `layer_metadata` yang relevan tersedia di form dan tersimpan benar.
- [ ] Kelengkapan metadata tampil sebagai indikator di daftar layer.

### Sumber
- [ ] Sumber bisa ditambah/diubah/dihapus per layer; tepat satu bisa ditandai primary.
- [ ] Uji koneksi memperbarui `health_status` + `last_checked_at`.
- [ ] Tidak ada kredensial mentah tersimpan di `layer_sources` (hanya `credential_ref`).

### Impor
- [ ] Setiap impor menghasilkan satu baris `layer_imports` berisi file, checksum, SRID, mode, status, dan hitungan berhasil/gagal.
- [ ] File sumber tersimpan dan bisa diunduh/diimpor ulang.
- [ ] Langkah pemetaan kolom tersimpan sebagai `layer_attribute_mappings` dan benar-benar menentukan isi `properties`.
- [ ] Mode `replace` mengganti seluruh fitur layer dalam satu transaksi — tidak pernah ada state setengah jadi yang terlihat publik.
- [ ] Format SHP-zip, GeoJSON, KML/KMZ, GPKG, dan CSV berkoordinat semuanya terdukung.
- [ ] Impor >1.000 fitur berjalan via queue dengan status yang terpantau di UI dan tidak menyebabkan timeout.
- [ ] File >100 MB ditolak dengan pesan yang menyebut batas & ukuran file (R22); file tepat di bawah 100 MB berhasil diproses sampai selesai pada konfigurasi server produksi.
- [ ] `feature_count` & `bbox` layer selalu cocok dengan isi `spatial_features` setelah impor (R18).

### Style
- [ ] Style `simple`, `categorized`, dan `graduated` bisa dibuat dari UI, lengkap dengan legenda.
- [ ] Satu layer bisa punya beberapa style dengan tepat satu default.
- [ ] Legenda yang tersimpan dipakai peta publik.

### Fitur
- [ ] Tabel fitur per layer bisa dicari, difilter per atribut, dan dipaginasi.
- [ ] Geometri bisa diedit lewat peta.
- [ ] Bulk edit atribut tersedia.

### Retirement modul lama (D13)
- [ ] Rute `data-spatial.*` & `tematik.*` redirect ke `spatial-layers.*`; menu `Data Spasial` hilang dari sidebar.
- [ ] Kemampuan yang hanya ada di modul lama (bulk edit atribut, validasi metadata wajib) sudah tersedia di modul baru **sebelum** rute lama dimatikan.
- [ ] `SpatialFeaturesV3Sync` dan pemasangannya di `AppServiceProvider` sudah dihapus; tidak ada lagi tulis ke `data_spatial_legacy_v1`.
- [ ] Permission `data-spatial.*` sudah dihapus dari katalog & role.
- [ ] Test lama yang menguji `data-spatial.*` sudah dihapus, dan perilaku relevannya punya test pengganti di modul baru.

### Umum
- [ ] Tidak ada dua UI paralel untuk data yang sama.
- [ ] `php artisan test` tidak punya regresi dibanding baseline.
- [ ] `vendor/bin/pint --dirty` bersih.
- [ ] Smoke test manual sebagai `admin-opd`: buat layer → impor → style → **tidak bisa publish**.
- [ ] Smoke test manual sebagai `admin-bappeda`: tinjau draft OPD → publish → tampil benar di peta publik.

---

## 8. Technical Constraints

| Aspek | Ketentuan |
|---|---|
| Framework | Laravel 12, PHP 8.3 (struktur streamlined; middleware di `bootstrap/app.php`) |
| Database | PostgreSQL 17 + PostGIS, ekstensi `ltree` & `pg_trgm` aktif |
| SRID penyimpanan | EPSG:4326 wajib; sumber ber-SRID lain ditransformasi saat impor |
| Frontend admin | Blade + Bootstrap + jQuery (AJAX), bukan SPA |
| Peta | Leaflet di sisi klien; GeoJSON penuh untuk volume saat ini |
| Permission | Spatie Permission lewat `App\Models\Permission::CATALOG`, pola `{modul}.{aksi}` |
| Testing | PHPUnit (bukan Pest), `RefreshDatabase`, wajib ada test untuk setiap perubahan |
| Format kode | Laravel Pint (`--dirty`) sebelum selesai |
| Atribut dinamis | Per **Jenis Peta** (`map_types` + `map_type_dynamic_attributes` + `metadata_definitions`), **bukan** per layer |
| Nama kelas PHP | `SpatialLayer`, `SpatialLayerFeature`, `SpatialLayerMetadata`, `Category` dipertahankan meski tabelnya v3 |
| Upload | Disk `public` (`storage/app/public`), diakses lewat `asset('storage/...')` |
| Batas unggahan | **100 MB** per file impor; tanpa chunked upload. Prasyarat server: `upload_max_filesize` & `post_max_size` ≥ 100M (PHP), `client_max_body_size` ≥ 100M (Nginx) |
| Kedalaman katalog | Maksimal **3 level** di UI & validasi server, meski skema ltree mendukung tak terbatas |
| Vector tile | **Ditunda** (D19). Peta publik tetap memakai GeoJSON penuh sampai ada kebutuhan performa nyata |

---

## 9. Decisions

Keputusan yang sudah disepakati dan **tidak boleh diubah tanpa persetujuan eksplisit**.

| No | Keputusan | Alasan |
|---|---|---|
| D1 | **Peta bukan data master.** Tidak ada tabel `maps`, `map_layers`, `map_shares`, `saved_maps`. State layer aktif hidup di client (URL/localStorage). | Ribuan pengunjung memakai dataset yang sama; menampilkan layer tidak boleh menulis ke database. |
| D2 | **Atribut dinamis di-scope per Jenis Peta**, bukan per layer. Tabel `layer_attribute_definitions` dari dokumen skema **tidak dibuat**; `layer_attribute_mappings` menunjuk langsung ke `metadata_definitions`. | Sistem per-Jenis sudah ada, teruji, dan dipakai; mengganti ke per-layer akan membuang pekerjaan yang berjalan. |
| D3 | `layers` punya kolom tambahan **`map_type_id`** (deviasi dari dokumen skema). | Konsekuensi langsung dari D2. |
| D4 | `categories_v3` mempertahankan kolom **`type`** (deviasi dari dokumen skema). | Dipakai sebagai proxy `map_types.slug` di validasi, filter, cache key, dan otorisasi di banyak controller. |
| D5 | Katalog tetap **dua tabel** (`categories_v3` akar + `category_nodes` turunan), dibaca aplikasi lewat view `categories_tree_v3`; keduanya **tidak** di-rename menjadi `categories`. | Hirarki v2 pecah jadi akar + turunan; tidak ada tabel tunggal yang bisa menggantikan nama `categories`, dan konsumen mengakses lewat view, bukan nama tabel mentah. |
| D6 | **Tidak ada `layer_versions`.** Riwayat perubahan data = `layer_imports` + file sumber yang tersimpan. Perbaikan data lewat impor ulang `replace`, bukan rollback versi. | Menyederhanakan skema; kebutuhannya terpenuhi oleh riwayat impor. |
| D7 | Tabel legacy **di-rename, tidak di-drop**: `categories_legacy_v1`, `data_spatial_legacy_v1`, `spatial_layers_legacy_v2`, `spatial_layer_features_legacy_v2`, `spatial_layer_metadata_legacy_v2`. | Rollback cepat tetap tersedia selama masa observasi pasca-cutover. |
| D8 | Selama `DataSpatialController` belum di-retire, setiap tulis ke `data_spatial_legacy_v1` **direplikasi real-time** ke `spatial_features` oleh `App\Support\SpatialFeaturesV3Sync` (event model, dipasang di `AppServiceProvider`). | Mencegah data baru dari admin lama "hilang" dari peta publik yang sudah membaca v3. Ini **jembatan sementara**, bukan arsitektur tujuan. |
| D9 | `region_id` dipertahankan di `spatial_features` (kolom ekstra non-dokumen). | Dipakai aktif endpoint publik `peta-v2/*`. |
| D10 | `gambar` & `is_marker` ditambahkan ke `categories_v3`/`category_nodes`, serta `gambar` & `created_by` ke `spatial_features` (deviasi dari dokumen skema). | Fitur nyata yang sudah berjalan dan dipakai UI; tidak boleh hilang karena dokumen skema melewatkannya. |
| D11 | Nama kelas PHP lama dipertahankan walau tabelnya sudah v3. | Puluhan file (controller, route-model-binding, view, test) memakai nama itu; rename hanya memperbesar blast radius tanpa nilai fungsional. |
| D12 | Kredensial layanan eksternal **tidak pernah** masuk database. | Keamanan; `credential_ref` hanya referensi ke secret store/env. |
| D13 | **Modul `Data Spasial` lama di-retire** (disetujui 3 Okt 2026). Rute lama diredirect ke `spatial-layers.*`, menu dihapus, jembatan `SpatialFeaturesV3Sync` dihapus, permission `data-spatial.*` dihapus, dan test lama yang menguji perilakunya **disetujui untuk dihapus/ditulis ulang** (lihat §5.9). | Menghilangkan dua sumber kebenaran & dua UI untuk data yang sama; jembatan dual-write memang hanya penyangga sementara. |
| D14 | **Kedalaman katalog tetap 3 level** (katalog → subkategori → sub-subkategori), ditegakkan di validasi server, meski `category_nodes` (ltree) mendukung tak terbatas. | Mempertahankan model mental admin yang sudah berjalan; membuka kedalaman bebas menambah kompleksitas UI tanpa kebutuhan nyata saat ini. |
| D15 | **Hak mengubah status layer hanya milik `super-admin` & `admin-bappeda`**, lewat permission baru `spatial-layers.publish`. `admin-opd` membuat layer yang selalu berstatus `draft` dan tidak punya kontrol status sama sekali. | Publikasi ke peta publik adalah keputusan kurasi lintas OPD, bukan keputusan masing-masing OPD. |
| D16 | **Tambah kolom `layers.opd_id`** (nullable, FK ke `opd`). `super-admin`/`admin-bappeda` boleh mengunggah layer untuk OPD mana pun dan memindahkan kepemilikan; `admin-opd` hanya mengelola layer dengan `opd_id` = `users.opd_id` miliknya. `NULL` berarti milik provinsi/Bappeda. | `created_by` (user) tidak cukup: layer bisa diunggah Bappeda atas nama OPD tertentu, dan kepemilikan harus tetap benar walau user pengunggahnya berganti/dihapus. |
| D17 | Tabel legacy (`*_legacy_v1`, `*_legacy_v2`) **dibiarkan sementara**, tanpa jadwal drop. Berhenti ditulis bukan berarti dihapus. | Rollback cepat tetap tersedia; penghapusan dievaluasi terpisah setelah modul baru terbukti stabil di produksi. |
| D18 | **Batas unggahan impor 100 MB per file**, tanpa chunked upload. Jumlah fitur tidak dibatasi angka tetap — dibatasi oleh batas ukuran file. | Angka yang realistis untuk shapefile/GeoPackage daerah; menghindari kompleksitas chunked upload sebelum terbukti perlu. |
| D19 | **Vector tile (Fase 7) ditunda.** Peta publik tetap GeoJSON penuh. | Volume saat ini (12.393 fitur) masih tertangani; dikerjakan hanya bila ada keluhan performa nyata. |
| D20 | **`layer_sources` tipe `database` tidak dibangun** untuk sekarang; nilainya tetap ada di skema tanpa UI. | Belum ada kebutuhan layer yang dirender langsung dari database eksternal. |

---

## 10. Open Questions

Pertanyaan Q1–Q6, Q8, Q9 versi 1.0 **sudah dijawab** dan pindah ke §9 Decisions (D13–D20). Yang tersisa:

### Q7 — Apa yang terjadi pada nilai atribut lama bila Jenis Peta sebuah layer diganti?

*(Pertanyaan ini sebelumnya belum tersampaikan dengan jelas — berikut penjelasan konkretnya.)*

**Latar:** set atribut yang diisi admin per fitur ditentukan oleh **Jenis Peta** layer (D2). Nilainya tersimpan di `spatial_features.properties` sebagai pasangan kunci–nilai, misalnya `{"tahun": 2024, "kondisi": "Baik", "sumber_dana": "APBD"}`.

**Kasusnya:** misalkan layer *Jalan Provinsi* memakai Jenis Peta **A** yang punya atribut `kondisi` dan `sumber_dana`. Ke-500 fiturnya sudah terisi. Lalu admin mengganti Jenis Peta layer itu ke **B**, yang tidak punya `sumber_dana` tapi punya `panjang_km`.

Pertanyaannya: nilai `sumber_dana` di 500 fitur itu diapakan?

| Opsi | Perilaku | Konsekuensi |
|---|---|---|
| **A. Dibiarkan** | Nilai `sumber_dana` tetap tersimpan di `properties`, hanya tidak lagi muncul di form & popup. | Tidak ada data hilang; bila Jenis Peta dikembalikan ke A, datanya utuh. Tapi `properties` menyimpan kunci "yatim" yang tak terdokumentasi. |
| **B. Dibersihkan** | Nilai atribut yang tidak dikenal Jenis Peta baru dihapus dari `properties`. | `properties` selalu bersih & cocok definisi, tapi **data hilang permanen** dan tidak bisa dibatalkan. |
| **C. Dikonfirmasi admin** | Saat ganti Jenis Peta, tampilkan daftar atribut yang akan jadi yatim + jumlah fitur terdampak, lalu admin memilih biarkan/hapus. | Paling aman & transparan, tapi butuh UI tambahan. |

**Rekomendasi saya: opsi A sebagai default + peringatan di UI** (sebutkan atribut mana yang akan berhenti tampil dan berapa fitur terdampak, tanpa menghapus apa pun). Alasannya: penghapusan data atribut tidak bisa dibatalkan, sementara kunci yatim di `jsonb` tidak merusak apa pun dan bisa dibersihkan belakangan lewat command terpisah bila memang mengganggu. Opsi C bisa jadi peningkatan nanti bila kebutuhan nyata muncul.

**Perlu keputusan Anda sebelum §5.8 dikerjakan.**

### Q10 — Siapa pemilik OPD untuk 174 layer yang sudah ada? *(pertanyaan baru, konsekuensi D16)*

Dari 176 layer hasil migrasi, hanya **2** yang punya jejak OPD di data legacy (`data_spatial_legacy_v1.opd_pengelola_id`) — sisanya tidak punya petunjuk apa pun. Jadi `layers.opd_id` **tidak bisa di-backfill otomatis**.

Pilihan:
1. **Biarkan `NULL` semua** (= milik provinsi/Bappeda), lalu Bappeda menetapkan kepemilikan satu per satu saat meninjau layer. Konsekuensinya: `admin-opd` tidak bisa menyentuh layer lama mana pun sampai ditetapkan.
2. **Tetapkan massal per katalog** — asumsikan katalog tertentu milik OPD tertentu, lalu isi `opd_id` seluruh layer di bawahnya sekaligus lewat command.

Rekomendasi: opsi 1 (aman, tidak menebak), dengan menyediakan aksi *bulk set OPD* di daftar layer supaya penetapannya tidak melelahkan.
