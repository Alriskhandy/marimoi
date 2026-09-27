# Rekomendasi Kritis: Implementasi Penuh Database V2

> Ditulis 2026-09-27. Sumber: `04-marimoi-x-goat.md`, `Rekomendasi_Pengelompokan_Layer_MARIMOI.md`, `01-current-schema.md`, `Hasil_Review_Marimoi_Jamil.md`, `05-kondisi-eksisting-vs-goat-dan-use-case-pemetaan.md`, `../db-schema-v2.md` (1026 baris — rancangan target V2 paling lengkap yang tersedia), dan `../03_plan/00-roadmap.md`. Dokumen ini **bukan** rancangan skema baru — `db-schema-v2.md` sudah menjawab itu secara sangat lengkap (ERD, kolom per tabel, kebijakan FK, index, kriteria publikasi). Dokumen ini adalah **vonis kritis dan rencana eksekusi**: kenapa rancangan itu belum jalan, keputusan apa yang membuatnya macet, dan urutan konkret untuk benar-benar sampai ke sana.

## 1. Vonis Kritis

**Masalah MARIMOI bukan kekurangan desain — sudah ada `db-schema-v2.md` setebal 1026 baris yang menjawab hampir seluruh temuan `Hasil_Review_Marimoi_Jamil.md` (metadata tidak lengkap, dashboard tidak analitis, share peta, tracking aspirasi) dengan sangat rinci: kolom per tabel, kebijakan delete, index, bahkan tingkat kelengkapan metadata bertingkat (Draft/Internal/Public). Masalahnya adalah dokumen itu didiamkan, dan implementasi nyata (batch migrasi 2026-09-26: `map_types`, `categories.atribut_schema`, `categories.is_group`, `sektor`, `data_spatial_interventions`) berjalan ke arah yang berbeda dari yang ditulis di sana.**

Ini bukan salah — `map_types`/`atribut_schema`/`sektor` adalah perbaikan additive yang valid dan sudah dipakai di produksi (lihat `01-current-schema.md` §6–7). Tapi itu **bukan** langkah dari `db-schema-v2.md`. Dokumen itu secara eksplisit menulis (§"Urutan implementasi schema"): buat `spatial_layers` sebagai tabel BARU dan kanonik, `categories` jadi legacy compatibility source. Yang terjadi malah `categories` diperkuat langsung (kolom baru ditambahkan padanya) tanpa `spatial_layers` pernah dibuat. Dua kemungkinan konsekuensi:

1. Bila tim nanti tetap ingin mengeksekusi `db-schema-v2.md` apa adanya, kolom `map_type_id`/`sektor_id`/`atribut_schema`/`is_group` yang baru ditambah ke `categories` harus **dipetakan ulang** ke `spatial_layers`/`spatial_layer_metadata` — kerja migrasi dua kali untuk konsep yang sama.
2. Bila tim diam-diam memutuskan `categories`+`data_spatial` yang diperkuat **adalah** implementasi V2 (bukan `spatial_layers`), maka `db-schema-v2.md` perlu ditulis ulang atau ditandai usang — sampai saat ini tidak ada satupun dokumen yang menyatakan itu secara eksplisit.

**Keputusan #0 yang paling kritis dan mendesak, sebelum satu migrasi pun berikutnya ditulis: pilih salah satu jalur di atas.** Rekomendasi tegas ada di Bagian 2.

## 2. Keputusan Fork: Rename Kanonik vs. Evolusi In-Place

| | Opsi A — Ikuti `db-schema-v2.md` apa adanya (`spatial_layers`/`spatial_layer_features`/`maps` baru, `categories`/`data_spatial` jadi legacy compatibility source) | Opsi B — Evolusi in-place (`categories`/`data_spatial` tetap kanonik, terus diperkuat kolom) |
| --- | --- | --- |
| Konsisten dengan dokumen tertulis | Ya, persis | Tidak — perlu dokumen baru menggantikan `db-schema-v2.md` |
| Nasib desain kolom yang sudah live (`map_type_id`, `sektor_id`, `atribut_schema`, `is_group`) | Dipindah utuh ke `spatial_layers` lewat migrasi copy+backfill — desainnya tidak dibuang, hanya tabel tujuannya berubah | Tidak perlu dipindah |
| Identifier publik non-integer (`uuid`/`slug`), `visibility`, `owner_opd_id` di level layer | Didapat bersih sejak awal, tanpa membawa beban legacy `categories` (`type` yang di-hardcode ±15 file, `parent_id` dipakai dobel untuk hierarki grup baru dan taksonomi lama sekaligus) | Ditambahkan di atas tabel yang sudah overloaded — menumpuk debt baru di atas debt lama |
| Periode dual-read selama migrasi | Ada, tapi terbatas dan bertujuan jelas: `categories`/`data_spatial` jadi compatibility source sampai ±15 file pemakai lama dipindah, lalu dimatikan | Juga ada — memindah makna `sumber_data` dari per-feature ke per-layer tetap butuh periode baca-dua-sumber yang sama; bukan lebih murah, hanya lebih tersembunyi karena nama tabel tidak berubah |
| Kejelasan jangka panjang untuk pembaca baru/tooling | Tinggi — nama tabel menjelaskan isinya | Rendah permanen — pembaca baru akan terus salah tebak fungsi `categories` |
| Efort migrasi kode (±15 file yang membaca `categories.type` dkk., diaudit `01-current-schema.md` §6.1) | Harus disentuh sekaligus, tapi jadi *forcing function* untuk membereskan hardcode yang selama ini didiamkan | Tidak wajib disentuh sekarang — debt itu terus dibawa dan makin mahal dibongkar seiring makin banyak fitur baru menempel di atasnya |

**Rekomendasi: Opsi A (rename kanonik).** Ini merevisi rekomendasi awal draf ini yang condong ke Opsi B — setelah ditinjau ulang, alasan sebelumnya ("sudah ada data produksi di kolom baru") adalah argumen sunk-cost yang lebih lemah dari kelihatannya. Kolom `map_type_id`/`sektor_id`/`atribut_schema`/`is_group` tidak hilang di Opsi A — hanya dipindah lewat `INSERT INTO spatial_layers SELECT ... FROM categories`; keputusan desainnya (jenis peta dinamis, dimensi sektor, hierarki grup, skema atribut dinamis) tetap utuh dan terpakai penuh. Risiko "dual-read" yang tadinya dipakai sebagai pembeda juga ternyata muncul di kedua opsi — Opsi B tetap butuh mengubah makna `sumber_data` dari per-baris ke per-layer, yang juga perlu periode transisi baca-dua-sumber — jadi bukan pembeda yang valid.

Yang benar-benar membedakan kedua opsi adalah **kejelasan jangka panjang**, dan di situ Opsi B kalah: `categories` sudah memikul beban legacy yang berat. Menambahkan metadata layer ke tabel yang sudah setumpuk itu bukan evolusi yang murah, itu menumpuk debt baru di atas debt lama, dan nama tabelnya tetap menyesatkan pembaca baru selamanya. Rename ke `spatial_layers` sekaligus jadi *forcing function* yang memaksa ±15 file itu benar-benar dibereskan sekarang — bukan didiamkan lagi seperti nasib `db-schema-v2.md` sendiri selama ini.

**Syarat keras Opsi A** (supaya tidak jadi migrasi destruktif yang diperingatkan `04-marimoi-x-goat.md` §10):

1. `categories`/`data_spatial` **tidak dihapus** — jadi *compatibility source* yang tetap bisa dibaca selama migrasi kode berjalan, pola additive non-destruktif yang sama seperti yang sudah terbukti dipakai proyek ini (`METADATA_WAJIB_SEJAK`, dst.).
2. Migrasi data satu arah saja: `categories` → `spatial_layers`, `data_spatial` → `spatial_layer_features`. Jangan sinkronisasi dua arah berkelanjutan — begitu backfill selesai, `spatial_layers`/`spatial_layer_features` adalah satu-satunya sumber tulis baru.
3. Checklist eksplisit ±15 file dari `01-current-schema.md` §6.1 sebagai *definition of done* migrasi kode, bukan pekerjaan yang boleh ditunda — celah itulah yang membuat `db-schema-v2.md` didiamkan pertama kali.
4. Tetapkan kondisi jelas kapan `categories`/`data_spatial` berhenti dibaca kode baru (mis. begitu seluruh route admin pemetaan pindah ke controller yang query `spatial_layers`) — supaya periode compatibility tidak jadi permanen seperti nasib `shared_maps` yang diperingatkan di Bagian 3.2.

## 3. Kritik Tajam terhadap Kondisi Saat Ini (per Tujuan yang Diminta)

### 3.1 Metadata dataset — masalah lokasi, bukan hanya kelengkapan

`Hasil_Review_Marimoi_Jamil.md` menyebut "metadata dataset belum lengkap" sebagai temuan langsung dari pengguna eksternal. Audit di `05-....md` §2.1 menunjukkan masalah yang lebih dalam dari sekadar "field kosong": **metadata (`sumber_data`, `opd_pengelola_id`, `tanggal_data`) disimpan per baris `data_spatial` (per feature), bukan per `categories` (per layer)**. Ini kontradiksi langsung dengan prinsip `db-schema-v2.md` §"Master dan reference data": *"foreign key transaksi mengarah ke master, bukan menyimpan nama bebas sebagai sumber utama"* dan tingkat kelengkapan metadata bertingkat yang didefinisikan di sana (Draft/Internal aktif/Public) — desain itu **mengasumsikan** metadata melekat ke layer, bukan ke tiap baris feature. Selama itu belum dibetulkan, "kelengkapan metadata" akan selalu jadi pertanyaan per-baris yang tak pernah tuntas (ribuan baris `data_spatial` per kategori, masing-masing punya kombinasi lengkap/tidak lengkap sendiri), padahal seharusnya jadi pertanyaan per-layer yang dijawab sekali.

### 3.2 Kemudahan pengelolaan peta — `shared_maps` adalah jalan buntu, bukan fondasi

`shared_maps` (snapshot JSON flat, lihat `05-....md` §2.3) **tidak bisa** dikembangkan bertahap menjadi `maps`/`map_layers`/`map_publications` — strukturnya secara fundamental berbeda (tidak relasional, tidak ada draft state, tidak ada FK). Setiap fitur baru yang "mempermudah pengelolaan peta" (reorder layer tersimpan, style tersimpan per user, opacity per map, dsb.) **tidak bisa** dibangun di atas `shared_maps` tanpa akhirnya menulis ulang tabel itu. Ini bukan estimasi — ini konsekuensi struktural dari tidak adanya kolom relasional (`map_id`, `spatial_layer_id`) di `shared_maps`.

### 3.3 Dashboard eksekutif & analisis tren — fondasi datanya belum ada sama sekali

`Hasil_Review_Marimoi_Jamil.md` eksplisit: *"dashboard belum berorientasi pada analisis dan pengambilan keputusan... indikator kinerja pembangunan, maupun tren perkembangan pembangunan belum ditampilkan secara terpadu."* `Rekomendasi_Pengelompokan_Layer_MARIMOI.md` §17-19 menjelaskan **kenapa**: dashboard butuh dimensi terstruktur (`kategori`, `subkategori`, `wilayah`, `tahun`, `sektor`) untuk di-query, bukan tabel per jenis data yang di-hardcode satu-satu. MARIMOI sekarang punya `sektor` (✅ terstruktur), tapi **tidak punya wilayah terstruktur** (`administrative_regions`) dan **tidak punya identitas proyek master** (`development_projects`) — dua fondasi paling dasar untuk "tren pembangunan per wilayah per tahun". Tanpa keduanya, dashboard apapun yang dibangun sekarang akan berupa query ad hoc di atas `data_spatial.dbf_attributes` (JSONB tak tervalidasi) — persis anti-pola yang diperingatkan `04-marimoi-x-goat.md` §7.2: *"JSONB menjadi sumber dashboard yang tidak tervalidasi."*

**Kritik tegas terhadap keputusan sebelumnya:** menunda `administrative_regions` ("menunggu sumber data batas wilayah resmi") adalah keputusan yang **sekarang berbenturan langsung** dengan permintaan fitur baru ini. Dashboard eksekutif dan analisis tren pembangunan **secara definisi** butuh filter wilayah — `db-schema-v2.md` sendiri menyarankan solusi jalan tengah yang menghindari kebuntuan ini (§"Keputusan yang masih terbuka" → "Tingkat wilayah Kemendagri"): mulai dari **level provinsi + kabupaten/kota + kecamatan saja** (data resmi tersedia luas, tidak butuh survei baru), desa/kelurahan menyusul kemudian. Ini bukan menunda lagi — ini versi minimum yang sudah cukup untuk dashboard tingkat provinsi/kabupaten yang diminta.

### 3.4 Share map — bukan penambahan fitur, tapi penggantian tabel

Sama seperti §3.2: `shared_maps` tidak punya `revoked_at`, `token_hash` terpisah dari `slug` URL, atau `map_share_accesses`. `04-marimoi-x-goat.md` §"Kriteria Keberhasilan" poin 4 eksplisit: *"Draft, publication, share token, expiry, revoke, QR, dan audit akses dapat dibedakan dengan jelas."* Ini tidak bisa dicapai dengan menambah kolom ke `shared_maps` — butuh tabel baru (`maps`, `map_publications`, `map_shares`, `map_share_accesses`) persis seperti yang sudah dirancang `db-schema-v2.md` §"Peta" poin 6–11.

## 4. Rencana Implementasi Penuh (Dipetakan ke Opsi A)

### 4.1 Prasyarat mutlak sebelum Fase 1 (tidak bisa dilewati)

1. **Verifikasi cardinality `data_spatial`** (`04-marimoi-x-goat.md` §5.2, belum pernah dikerjakan menurut audit `01-current-schema.md` §3.2) — pastikan asumsi "1 baris = 1 feature" benar untuk seluruh `data_type`, terutama `proyek_strategis` yang punya `project_progress_reports` menempel. Ini menentukan bagaimana backfill `data_spatial` → `spatial_layer_features` dilakukan dan dari mana metadata kanonik per-layer diambil bila nilainya berbeda-beda antar baris dalam satu kategori.
2. **Kunci level wilayah awal**: provinsi + kabupaten/kota + kecamatan (rekomendasi `db-schema-v2.md`, lihat §3.3 di atas). Ini membatalkan penundaan `administrative_regions` secara penuh — tetap ditunda desa/kelurahan-nya saja, bukan seluruh tabelnya.
3. **Selesaikan dua-sistem-role** (`users.role_id` vs Spatie-style vs `user_role_assignments`, diaudit `01-current-schema.md` §1 dan direncanakan `02-analysis.md`/`03-database-planning.md`) — `owner_opd_id` di setiap tabel baru pada Bagian 4.2/4.3 di bawah bergantung pada satu sumber kebenaran role/OPD yang jelas. Menambah tabel baru di atas fondasi role yang masih dua sumber kebenaran mewariskan ambiguitas yang sama ke fitur baru.
4. **Susun checklist ±15 file** yang membaca `categories`/`data_spatial` langsung (daftar lengkap di `01-current-schema.md` §6.1: `Category.php`, `DataSpatial.php`, 5 model duplikat, `CategoryController`, `DataSpatialController`, `ProjectFeedbackController`, `FeedbackController`, `ScopedProjectFeedbackController`, `Api/V1/LayerController`, `MapDataVersion`, seeder, ±6 Blade view) — ini jadi *definition of done* Fase 1, bukan catatan kaki.

### 4.2 Katalog layer — `spatial_layers` + `spatial_layer_metadata`, backfill dari `categories`/`data_spatial`

Sesuai Opsi A: buat `spatial_layers` (identitas: `uuid`, `slug`, `name`, `title`, `layer_class`, `owner_user_id`, `owner_opd_id`, `visibility`, `is_active`, `published_at` — persis `db-schema-v2.md` §"`spatial_layers`") dan `spatial_layer_metadata` (satu-ke-satu: `source_name`, `source_url`, `license`, `attribution`, kontak, `data_reference_year`, `update_frequency`, `last_verified_at`, `geometry_type`, `srid`, `lineage`, `limitations` — persis `db-schema-v2.md` §"`spatial_layer_metadata`"). `map_type_id`, `sektor_id`, `atribut_schema`, `is_group`, `parent_id` dari `categories` **ikut pindah sebagai kolom `spatial_layers`** — desainnya tidak diulang dari nol, hanya dipetakan ke tabel baru lewat migrasi backfill.

Sumber metadata (`source_name`/`opd_pengelola_id`/`tanggal_data`) diambil dari `data_spatial` sesuai hasil verifikasi cardinality (Prasyarat 4.1 poin 1): bila memang seragam per kategori, backfill langsung jadi satu baris `spatial_layer_metadata`; bila bervariasi antar baris, kunci keputusan bisnis eksplisit dulu (nilai mana yang jadi metadata kanonik layer, sisanya jadi atribut per-feature di `spatial_layer_features.attributes`) — jangan diasumsikan sepihak oleh migrasi.

Terapkan **tingkat kelengkapan bertingkat** dari `db-schema-v2.md` (Draft/Internal aktif/Public) sebagai validasi aplikasi, bukan constraint DB — layer tanpa metadata tetap bisa disimpan (Draft), tapi tidak bisa naik status `visibility=public` tanpa memenuhi tingkat "Public".

### 4.3 `spatial_layer_features` — backfill dari `data_spatial`

Buat `spatial_layer_features` (`spatial_layer_id`, `external_id`, `geometry`, `attributes` jsonb, `source_version_id`, `created_by` — persis `db-schema-v2.md` §"`spatial_layer_features`"). Backfill dari `data_spatial`, memetakan `kategori_id` → `spatial_layer_id`. Karena ini menyalin kolom geometry besar untuk kemungkinan puluhan ribu baris, jalankan sebagai job antrian batch (bukan migrasi Laravel biasa yang berjalan synchronous), dengan checksum/idempotensi seperti yang disarankan `04-marimoi-x-goat.md` §9 Fase 5 — bukan mendadak dikerjakan on-the-fly saat deploy.

### 4.4 Map/Publication/Share

Tidak ada padanan lama sama sekali untuk bagian ini — buat persis seperti didefinisikan `db-schema-v2.md` §"Peta" poin 6–11:

- `maps` — container, ganti fungsi "peta aktif di frontend" jadi entitas tersimpan.
- `map_layers` — pivot map↔`spatial_layers` dengan `style_config`/`filter_config`/`display_order`.
- `map_publications` — pemisahan draft/publik.
- `map_shares` + `map_share_accesses` — ganti `shared_maps` sepenuhnya.

`shared_maps` dipertahankan read-only sebagai legacy selama compatibility period (share link lama tetap terbuka), tapi **share baru** langsung memakai tabel ini. Bagian ini bergantung pada `spatial_layers` (Fase 4.2) sudah ada karena `map_layers.spatial_layer_id` mengarah ke sana, tapi tidak bergantung pada `spatial_layer_features` (Fase 4.3) — bisa mulai begitu Fase 4.2 selesai, paralel dengan Fase 4.3.

### 4.5 Dashboard eksekutif & tren pembangunan — fondasi minimum, bukan semuanya sekaligus

Urutan minimum yang benar-benar dibutuhkan (bukan seluruh §5.5 `db-schema-v2.md` sekaligus):

1. `administrative_regions` (3 level: provinsi/kabupaten-kota/kecamatan) + `spatial_layer_features.wilayah_id` (nullable, `SET NULL`) — **ini cukup untuk filter wilayah**, gap terbesar yang diidentifikasi `05-....md` dan `Rekomendasi_Pengelompokan_Layer_MARIMOI.md` §4.
2. `development_projects` sebagai master identitas proyek strategis — pisahkan dari `spatial_layer_features` (baris migrasi `data_type='proyek_strategis'` saat ini menyatu geometry+atribut+identitas proyek dalam satu baris). Tanpa ini, satu proyek yang punya banyak lokasi/laporan tidak punya "identitas" tunggal untuk dihitung di kartu ringkasan dashboard ("Total Proyek", "Proyek Berjalan", dst. — `Rekomendasi_Pengelompokan_Layer_MARIMOI.md` §20).
3. **Jangan** bangun `analysis_layers`/`analysis_results` di fase ini — dashboard "ringkasan pembangunan" dan "tren per tahun" (`Rekomendasi_Pengelompokan_Layer_MARIMOI.md` §20 kelompok Ringkasan/Progres/Distribusi/Tren) bisa dijawab query agregasi langsung dari `development_projects` + `project_progress_reports` + `administrative_regions`, tanpa perlu tabel hasil komputasi terjadwal. `analysis_layers` (konektivitas, aksesibilitas, kesenjangan infrastruktur — §11-16 dokumen yang sama) adalah kelas masalah berbeda (butuh rumus/metodologi dikunci, sudah ditunda secara sadar) — jangan dicampur ke scope "dashboard eksekutif" yang diminta sekarang.

### 4.6 Taksonomi 7-kelompok — kerja data, bukan kerja skema

`is_group` sudah ada (✅, ikut pindah ke `spatial_layers` di Fase 4.2). Yang belum: datanya masih **flat** (audit `01-current-schema.md` §7.1 — `KategoriLayerSeeder` bikin 9 baris tanpa `parent_id`). Susun ulang jadi 3 tingkat (Kelompok → Subkelompok → Layer) sesuai 7 kelompok `Rekomendasi_Pengelompokan_Layer_MARIMOI.md` §3 adalah **pekerjaan seeder/admin UI** di atas `spatial_layers`, bukan migrasi skema tambahan — paling praktis dikerjakan setelah Fase 4.2 (begitu `spatial_layers.parent_id` ada), dan sebaiknya tidak ditunda karena inilah yang langsung memperbaiki keluhan "hierarki informasi pada halaman beranda belum optimal" (`Hasil_Review_Marimoi_Jamil.md` §2).

## 5. Urutan Eksekusi Definitif

| Fase | Isi | Bergantung pada | Bisa paralel dengan |
| --- | --- | --- | --- |
| 0 | Kunci Opsi A (Bagian 2) + verifikasi cardinality + level wilayah + resolusi dua-sistem-role + checklist ±15 file | — | — |
| 1 | `spatial_layers` + `spatial_layer_metadata` (backfill dari `categories`, termasuk `map_type_id`/`sektor_id`/`atribut_schema`/`is_group`/`parent_id`) + migrasi ±15 file pemakai lama ke `spatial_layers` (§4.2) | Fase 0 | — |
| 2 | `spatial_layer_features` (backfill dari `data_spatial`, job batch — §4.3) + reorganisasi taksonomi 3-tingkat (§4.6) | Fase 1 | Fase 3 |
| 3 | `maps`/`map_layers`/`map_publications`/`map_shares`/`map_share_accesses` (`map_layers` → `spatial_layers` — §4.4) | Fase 1 | Fase 2 |
| 4 | `administrative_regions` (3 level) + `spatial_layer_features.wilayah_id` | Fase 2 | Fase 3 |
| 5 | `development_projects` + migrasi bertahap `project_feedbacks`/`project_progress_reports` ke FK proyek | Fase 4 | — |
| 6 | Dashboard eksekutif (query agregasi di atas Fase 4+5, **bukan** tabel baru) | Fase 4, 5 | — |
| 7 (ditunda, tetap disadari) | `analysis_layers`/`analysis_results` + job terjadwal | Fase 4, 5, rumus dikunci | — |
| 8 | Matikan `categories`/`data_spatial`/`shared_maps` sebagai compatibility source begitu Fase 1–3 tuntas dan tidak ada kode baru yang membacanya | Fase 1, 2, 3 | — |

## 6. Yang Jangan Dilakukan

- **Jangan** biarkan `categories`/`data_spatial` dan `spatial_layers`/`spatial_layer_features` berjalan sebagai dua sumber tulis paralel tanpa batas waktu — begitu backfill Fase 1/2 selesai, `categories`/`data_spatial` jadi read-only untuk kode lama saja sampai migrasi kode (Fase 8) selesai, lalu dimatikan sepenuhnya. Kembali "menunda" pematian ini persis mengulang nasib `db-schema-v2.md` yang didiamkan sejak awal.
- **Jangan** bangun widget dashboard yang query langsung `dbf_attributes`/`attributes` JSONB dengan operator ad hoc untuk angka strategis (total proyek, realisasi anggaran) — itu justru anti-pola yang dikritik `04-marimoi-x-goat.md` §7.2 dan akan mewarisi masalah "statistik masih bersifat umum" yang sudah dikeluhkan (`Hasil_Review_Marimoi_Jamil.md` §2).
- **Jangan** kerjakan `analysis_layers`/`analysis_results` sebelum `administrative_regions` dan `development_projects` berdiri — analisis kesenjangan/tren butuh dimensi wilayah dan proyek yang valid sebagai input, mengerjakannya lebih dulu hanya menghasilkan tabel kosong yang tidak bisa diisi.
- **Jangan** anggap menunda `administrative_regions` masih valid setelah permintaan dashboard eksekutif + analisis tren ini — itu keputusan usang untuk scope yang lebih sempit dari sekarang (lihat kritik §3.3).
- **Jangan** jalankan backfill `spatial_layer_features` (Fase 2) sebagai migrasi Laravel synchronous biasa — kolom geometry besar untuk puluhan ribu baris berisiko sama seperti kasus memory-exhaustion yang pernah terjadi di `CategoryController`/`ProjectFeedbackController` (lihat riwayat kerja sebelumnya di sesi ini) bila di-load penuh dalam satu request/proses.
- **Jangan** hapus `shared_maps` atau `data_spatial` secara langsung di Fase manapun sebelum Fase 8 — keduanya deprecated dalam peran (bukan sumber utama lagi), bukan langsung dihapus, selama compatibility period.

## 7. Kesimpulan

MARIMOI tidak kekurangan rencana — `db-schema-v2.md` sudah menjawab hampir seluruh pertanyaan teknis, termasuk penamaan kanonik `spatial_layers`/`spatial_layer_features`/`maps` yang seharusnya diikuti apa adanya (Bagian 2). Yang hilang adalah **kemauan mengeksekusi rename itu sekarang selagi datanya masih relatif kecil** (bukan menunda lagi dengan alasan sunk-cost kolom yang baru ditambahkan), dan **kemauan untuk tidak menunda lagi** wilayah administratif (Bagian 3.3) — karena tiga fitur yang diminta sekarang, dashboard eksekutif, analisis tren, dan share map yang layak, secara struktural tidak bisa dibangun di atas tabel legacy yang masih menumpuk debt penamaan dan hardcode ±15 file. Urutan di Bagian 5 memberi jalur konkret yang memindahkan seluruh keputusan desain yang sudah dikunci (`map_types`, `atribut_schema`, `is_group`, `sektor`) ke rumah barunya (`spatial_layers`) tanpa mengulanginya dari nol, sekaligus membereskan dua blocker langsung yang tersisa: `administrative_regions` dan `maps`/`map_layers`/`map_shares`.
