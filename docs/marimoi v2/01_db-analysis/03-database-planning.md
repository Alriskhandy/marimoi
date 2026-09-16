# Database Improvement Planning

## Tujuan

Dokumen ini menjadi rencana implementasi bertahap untuk mengembangkan database MARIMOI menjadi fondasi WebGIS dan dashboard pembangunan yang rapi, terukur, mudah dipelihara, serta mendukung:

- katalog layer peta dengan metadata lengkap;
- versioning dan audit data spasial;
- peta dengan banyak layer aktif, filter, style, dan extent;
- share peta melalui URL publik dan QR code;
- dashboard proyek, indikator, progres fisik, dan realisasi keuangan;
- filter analisis berdasarkan wilayah, OPD, sektor, kategori, dan tahun.

Rencana ini berfokus pada data dan peta. Implementasi dilakukan secara backward-compatible agar fitur yang berjalan tidak langsung rusak.

## Prinsip Pelaksanaan

1. **Migration bertahap**: tambahkan struktur baru terlebih dahulu, migrasikan data, lalu hapus atau ubah struktur lama setelah seluruh pemakai diperbarui.
2. **Tidak memindahkan geometri secara sembarangan**: pastikan SRID, tipe geometri, dan validitas PostGIS diverifikasi sebelum perubahan.
3. **Identifier publik tidak berurutan**: gunakan UUID atau token acak untuk URL publik, bukan ID integer.
4. **Metadata dipisahkan dari konfigurasi peta**: metadata melekat pada layer, sedangkan style, filter, urutan, dan visibilitas melekat pada peta.
5. **Data analitis terstruktur**: atribut yang dipakai untuk filter atau agregasi tidak hanya disimpan di JSONB atau teks bebas.
6. **Histori tidak ditimpa**: versi layer dan laporan progres disimpan sebagai data historis.
7. **Setiap tahap memiliki verifikasi**: migration, data migration, index, dan query utama diuji sebelum tahap berikutnya dimulai.

## Urutan Tahap

| Tahap | Fokus | Hasil utama | Ketergantungan |
| --- | --- | --- | --- |
| 0 | Persiapan dan baseline | backup, inventaris pemakai, keputusan desain | tidak ada |
| 1 | Fondasi integritas | UUID, ownership OPD, constraint, index, master referensi | tahap 0 |
| 2 | Katalog dan metadata layer | metadata, sumber, versi, wilayah layer | tahap 1 |
| 3 | Peta dan layer aktif | `maps`, `map_layers`, konfigurasi tampilan | tahap 1 dan 2 |
| 4 | Share URL dan QR code | token share, expiry, revoke, audit akses | tahap 3 |
| 5 | Data proyek dan indikator | proyek, wilayah, progres, indikator | tahap 1 dan 2 |
| 6 | Dashboard dan optimasi | query agregasi, snapshot, index lanjutan | tahap 5 |
| 7 | Konsolidasi dan cleanup | deprecate kolom lama, dokumentasi, audit akhir | seluruh tahap |

---

## Tahap 0: Persiapan dan Baseline

### Tujuan

Mendapatkan baseline skema, data, query, dan pemakai sebelum migration baru dijalankan.

### Pekerjaan

- Buat backup database dan uji proses restore pada environment staging.
- Simpan hasil `php artisan migrate:status` dan daftar index/constraint PostgreSQL.
- Inventarisasikan seluruh pemakai `data_spatial`, terutama controller, resource API, importer, dan frontend peta.
- Catat format data yang sudah tersimpan di `dbf_attributes`, nilai `data_type`, `sub_type`, `tahun`, dan `kategori_id`.
- Ukur jumlah layer, jumlah fitur, geometri invalid, UUID null/duplikat, dan nilai koordinat di luar rentang.
- Tetapkan apakah peta publik membaca data layer terbaru secara real-time atau versi tertentu.

### Output dan kriteria selesai

- backup terverifikasi;
- laporan kualitas data awal;
- daftar endpoint dan UI yang bergantung pada struktur lama;
- keputusan owner data, visibility, versioning, dan kebijakan share;
- restore backup berhasil di staging dan seluruh pemakai kolom yang akan diubah teridentifikasi.

## Tahap 1: Fondasi Integritas dan Master Data

### Tujuan

Menetapkan identifier, ownership, referensi, dan aturan data yang menjadi dasar seluruh fitur peta.

### Migration dan tabel

1. **Perbaikan `data_spatial`**
	- isi `uuid` yang masih null dengan UUID baru;
	- pastikan `uuid` unique dan ubah menjadi `NOT NULL`;
	- tambahkan `slug` unique bila layer membutuhkan URL berbasis nama;
	- tambahkan `owner_opd_id` sebagai foreign key ke `opd.id`;
	- tambahkan `visibility`, `is_active`, dan `is_downloadable`;
	- tambahkan `published_at`, `last_updated_at`, dan `deleted_at` bila soft delete diperlukan.

2. **Master `sectors`**
	- `id`, `code`, `name`, `description`, `is_active`, timestamps;
	- unique pada `code` dan index pada `is_active`.

3. **Master `administrative_regions`**
	- `id`, `code`, `name`, `level`, `parent_id`, `geom`, timestamps;
	- unique pada `code`;
	- index pada `(level, parent_id)`;
	- GiST index pada `geom` jika wilayah disimpan sebagai geometri.

4. **Relasi layer**
	- `data_spatial_sector`: `data_spatial_id`, `sector_id`;
	- `spatial_layer_regions`: `data_spatial_id`, `region_id`;
	- unique gabungan untuk mencegah relasi duplikat.

5. **Perbaikan relasi yang sudah ada**
	- validasi penggunaan `categories.user_id`; tambahkan foreign key atau tandai untuk dihapus;
	- audit foreign key `sessions.user_id` sesuai kebutuhan aplikasi;
	- audit dan hapus index duplikat setelah memastikan tidak dipakai.

### Data migration

- Isi `owner_opd_id` dari OPD user pembuat bila pemetaan tersebut dapat dibuktikan. Data yang tidak dapat dipastikan diberi status review, bukan ditebak.
- Bentuk master sektor dan wilayah dari sumber resmi yang disepakati.
- Pemetaan kategori lama ke sektor baru dibuat dalam tabel mapping dan dapat ditinjau sebelum dipermanenkan.

### Kriteria selesai

- seluruh `data_spatial.uuid` terisi, unique, dan non-null;
- layer memiliki owner yang jelas atau status `needs_review`;
- master sektor dan wilayah memiliki kode unique;
- foreign key dan index lolos pemeriksaan PostgreSQL;
- API lama masih dapat membaca layer.

## Tahap 2: Katalog dan Metadata Layer

### Tujuan

Memberikan metadata agar pengguna mengetahui isi, sumber, pemilik, kualitas, dan kemutakhiran setiap layer.

### Tabel `spatial_layer_metadata`

Satu baris metadata utama untuk setiap layer:

- `data_spatial_id` unique dan foreign key;
- `title`, `description`, `abstract`;
- `source_name`, `source_url`, `license`, `attribution`;
- `contact_name`, `contact_email`, `contact_phone`;
- `data_date`, `update_frequency`, `last_verified_at`;
- `quality_notes`, `completeness_notes`, `limitations`;
- `geometry_type`, `srid`, `bbox`, `center_point`;
- `created_by`, `updated_by`, timestamps.

### Tabel versioning dan sumber

`spatial_layer_versions` menyimpan `data_spatial_id`, nomor versi, label versi, file sumber, format, ukuran, checksum, jumlah fitur, waktu impor, operator, dan catatan perubahan. Tambahkan unique pada `(data_spatial_id, version_number)` dan pastikan hanya ada satu versi current per layer.

`spatial_layer_sources` menyimpan sumber tambahan per layer, sedangkan `spatial_layer_keywords` dan tabel pivot keyword digunakan bila pencarian berdasarkan keyword diperlukan.

### Penyesuaian `data_spatial`

- Pertahankan `dbf_attributes` sebagai data mentah hasil impor.
- Atribut untuk filter atau agregasi harus dipetakan ke kolom/tabel terstruktur dan diberi index.
- Jangan menyalin metadata ke `map_layers`; tabel tersebut hanya menyimpan konfigurasi tampilan.
- Simpan file sumber di storage terkelola, sedangkan database menyimpan path, checksum, format, dan ukuran.

### Kriteria selesai

- setiap layer aktif memiliki judul, deskripsi, sumber, OPD pengelola, tanggal data, tanggal pembaruan, SRID, dan status publikasi;
- versi layer dapat ditelusuri dan versi current dapat ditentukan secara konsisten;
- metadata API dapat ditampilkan tanpa membaca JSON mentah;
- layer tanpa metadata minimum masuk daftar validasi admin.

## Tahap 3: Peta dan Layer Aktif

### Tabel `maps`

Kolom minimum: `id`, `uuid`, `slug`, `title`, `description`, `owner_id`, `visibility`, `is_active`, `center_point`, `zoom`, `bbox`, `published_at`, timestamps, dan `deleted_at`. Nilai `visibility` minimal `private`, `unlisted`, atau `public`.

### Tabel `map_layers`

Kolom minimum: `map_id`, `data_spatial_id`, `layer_order`, `is_visible`, `opacity`, `style_config` JSONB, `filter_config` JSONB, `min_zoom`, `max_zoom`, dan timestamps.

Constraint dan index:

- unique `(map_id, data_spatial_id)`;
- index `(map_id, layer_order)`;
- foreign key ke `maps` dan `data_spatial`;
- constraint opacity antara 0 dan 1;
- validasi konfigurasi style dan filter di application layer.

### Data migration dan integrasi

- Buat peta default untuk konfigurasi peta publik yang saat ini dirakit langsung oleh frontend/backend.
- Salin daftar layer aktif ke `map_layers` dengan urutan dan visibilitas yang sama.
- Pertahankan endpoint lama sampai seluruh client menggunakan struktur baru.
- Ubah frontend agar mengambil metadata layer dan konfigurasi tampilan dari sumber terpisah.

### Kriteria selesai

- satu peta dapat memiliki banyak layer dan satu layer dapat digunakan pada banyak peta;
- urutan, visibility, opacity, style, filter, extent, dan zoom tersimpan per peta;
- perubahan style pada satu peta tidak mengubah peta lain;
- endpoint publik menolak layer inactive atau unpublished.

## Tahap 4: Share URL dan QR Code

### Tabel `map_shares`

Kolom minimum: `map_id`, `token` unique, `created_by`, `expires_at`, `revoked_at`, `is_active`, `access_count`, `last_accessed_at`, dan timestamps.

Token harus dibuat dengan generator kriptografis yang cukup panjang. Jangan gunakan `map_id` sebagai token publik. Bila pencarian berdasarkan hash digunakan, simpan token dalam bentuk hash.

### Tabel opsional `map_share_accesses`

Untuk audit detail, simpan `map_share_id`, `accessed_at`, `ip_hash`, `user_agent`, `referer`, dan `response_status`. Hindari menyimpan IP mentah lebih lama dari kebutuhan operasional.

### URL dan QR

- URL contoh: `/maps/share/{token}`.
- Server memvalidasi token, `is_active`, `revoked_at`, dan `expires_at` pada setiap request.
- QR code dibuat dari URL share yang sama.
- QR tidak wajib disimpan di database; bila diperlukan untuk cache/cetak, simpan file di object storage dan path-nya di `qr_path`.
- Revoke token harus segera membuat URL dan QR tidak dapat mengakses peta.

### Kriteria selesai

- pengguna dapat membuat, menonaktifkan, dan mencabut share URL;
- URL share memuat konfigurasi peta dan layer aktif yang tepat;
- QR code dapat dipindai dan membuka URL yang sama;
- token kadaluarsa dan token revoke ditolak;
- access count tidak menggantikan log audit bila audit detail diwajibkan.

## Tahap 5: Data Proyek dan Indikator Pembangunan

### Tabel inti

- `development_projects`: kode proyek, OPD, sektor, tahun anggaran, status, target, pagu, sumber dana, dan deskripsi;
- `project_regions`: relasi many-to-many proyek dan wilayah dengan unique `(project_id, region_id)`;
- `project_locations`: `project_id`, `geom`, tipe geometri, SRID, nama, dan catatan dengan GiST index;
- `project_progress_reports`: periode, target/realisasi fisik, target/realisasi keuangan, status, pelapor, dan catatan;
- `development_indicators`: kode, definisi, satuan, arah capaian, sumber, frekuensi, sektor/OPD pemilik;
- `indicator_values`: indikator, wilayah, sektor, OPD, periode, nilai, target, sumber, dan waktu pelaporan.

### Migrasi dari `project_feedbacks`

- Jangan langsung menganggap `nama_proyek` sebagai proyek unik.
- Buat proses deduplikasi dan mapping manual untuk variasi penulisan nama proyek.
- Pindahkan wilayah teks ke master wilayah hanya bila kode/nama dapat diverifikasi.
- Pertahankan data lama sampai relasi ke proyek baru tervalidasi.

### Kriteria selesai

- dashboard dapat memfilter proyek berdasarkan tahun, OPD, sektor, dan wilayah;
- progres fisik dan keuangan memiliki periode serta sumber data;
- histori laporan tidak ditimpa ketika laporan baru masuk;
- nilai indikator dapat dibandingkan antarwilayah dan ditampilkan sebagai tren.

## Tahap 6: Dashboard dan Optimasi Query

- Definisikan metrik resmi: jumlah proyek, progres fisik, realisasi keuangan, capaian indikator, dan tren.
- Dokumentasikan sumber setiap metrik dan waktu perhitungan.
- Tambahkan index gabungan berdasarkan query aktual, misalnya `(owner_opd_id, tahun, status)` dan `(sector_id, tahun, status)`.
- Gunakan GiST untuk kolom geometri yang dipakai dalam pencarian spasial.
- Gunakan materialized view atau tabel snapshot hanya bila agregasi langsung tidak memenuhi target respons.
- Tambahkan `calculated_at`, filter snapshot, dan sumber data agar angka dapat diaudit.

### Kriteria selesai

- filter wilayah, OPD, sektor, kategori, status, dan tahun menghasilkan data konsisten;
- query dashboard memiliki execution plan yang diterima pada data staging realistis;
- angka dashboard dapat ditelusuri ke baris sumber;
- perubahan indeks tidak menurunkan performa impor layer.

## Tahap 7: Konsolidasi dan Cleanup

- Hapus kolom lama hanya setelah seluruh pemakai berpindah dan periode observasi selesai.
- Deprecate kolom `data_spatial` yang sudah digantikan metadata terstruktur setelah audit pemakai.
- Hapus data duplikat/orphan setelah backup dan persetujuan pemilik data.
- Hapus index redundant setelah diverifikasi melalui `pg_indexes` dan execution plan.
- Perbarui diagram Mermaid pada `01-current-schema.md` setelah migration produksi stabil.
- Tambahkan data dictionary, pemilik tabel, dan jadwal audit metadata, permission, share token, serta kualitas geometri.

## Strategi Pengujian

Setiap tahap minimal memiliki pengujian berikut:

1. **Migration test**: migration berjalan pada database kosong dan database berisi data tiruan realistis.
2. **Data integrity test**: foreign key, unique, not-null, check constraint, SRID, dan geometri valid.
3. **Compatibility test**: endpoint lama tetap berjalan sampai client dimigrasikan.
4. **Feature test**: CRUD layer, metadata, peta, map layer, share token, expiry, revoke, dan QR URL.
5. **Authorization test**: user hanya dapat mengubah layer/peta sesuai ownership dan role.
6. **Performance test**: query peta, metadata, filter dashboard, dan pencarian spasial.
7. **Rollback test**: backup restore dan rollback migration diuji di staging.

## Risiko Utama dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Layer tidak memiliki owner yang jelas | gunakan status review dan validasi admin, jangan menebak owner |
| Perubahan SRID merusak tampilan peta | verifikasi SRID dan hasil transformasi di staging |
| Share URL membocorkan data private | validasi visibility layer dan peta pada setiap request |
| Token share ditebak atau tidak dapat dicabut | token acak panjang, expiry, revoke, rate limit, dan audit |
| JSONB menjadi tempat semua data analitis | petakan atribut penting ke kolom/tabel terstruktur |
| Migrasi nama proyek menghasilkan duplikasi | deduplikasi dan mapping berbasis kode/sumber resmi |
| Agregat dashboard berbeda antarhalaman | definisikan metric layer dan sumber data tunggal |
| Migration besar mengunci tabel produksi | lakukan backfill bertahap dan jadwalkan constraint saat traffic rendah |

## Keputusan Wajib Sebelum Tahap 2

- standar kode wilayah;
- daftar sektor dan OPD pemilik layer;
- metadata minimum sebelum layer dipublikasikan;
- aturan versi: satu versi current atau beberapa versi berdasarkan periode;
- mode peta publik: real-time atau snapshot;
- masa berlaku default share URL;
- retensi log akses dan perlakuan terhadap data pribadi;
- format dan SRID standar data spasial;
- pihak yang boleh membuat, menerbitkan, dan mencabut peta/share URL.

## Rekomendasi Prioritas

Mulai dari Tahap 0 sampai Tahap 2. Tanpa identifier, ownership, dan metadata konsisten, fitur peta dan share sulit diaudit dan rawan menampilkan data yang salah. Setelah metadata stabil, Tahap 3 dan 4 memberikan nilai langsung berupa peta yang dapat dikomposisi dan dibagikan. Tahap 5 dan 6 membangun dashboard pembangunan di atas data yang memiliki wilayah, sektor, sumber, dan histori yang jelas.
