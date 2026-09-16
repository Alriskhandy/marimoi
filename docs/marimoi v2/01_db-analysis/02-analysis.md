# Perbaikan yang harus di lakukan
1. Baca file 01-current-schema.md
2. Baca /docs/Hasil_Review_Marimoi_Jamil.md
3. Analisa struktur database saat ini, berdasarkan:
- Perbaikan yang perlu dilakukan agar database lebih rapih dan terstruktur dengan baik
- setiap layer peta memiliki meta data yang baik
- mendukung fitur share peta dengan url dan qr code dari layer peta yang aktif
- mendukung fitur terkait data dan peta pada hasil review marimoi
4. Tulis hasil analisis pada file ini pada section "# Hasil Analisis Database"


# Hasil Analisis Database

## 1. Ringkasan Kondisi Saat Ini

Database saat ini sudah memiliki fondasi yang cukup untuk portal WebGIS: terdapat tabel `data_spatial`, `categories`, `opd`, PostGIS, `project_feedbacks`, serta tabel pengguna. Namun, struktur tersebut masih lebih cocok untuk menyimpan data operasional dasar daripada mendukung katalog data, dashboard pengendalian pembangunan, dan berbagi peta secara terkontrol.

Temuan utama:

1. `data_spatial` belum membedakan identitas layer, metadata dataset, konfigurasi tampilan peta, dan data hasil impor. Kolom seperti `data_type`, `sub_type`, `gambar`, `deskripsi`, `tahun`, dan `dbf_attributes` masih bersifat umum.
2. Metadata penting seperti sumber data, instansi pengelola, kontak, lisensi, tanggal data, tanggal pembaruan, cakupan wilayah, sistem koordinat, kualitas, dan status publikasi belum tersedia secara terstruktur.
3. Belum ada tabel untuk URL publik, QR code, masa berlaku, revokasi, dan statistik akses terhadap layer atau konfigurasi peta yang dibagikan.
4. `categories.user_id` dan `sessions.user_id` memiliki relasi logis tetapi tidak memiliki foreign key. `data_spatial.uuid` nullable, padahal identitas publik layer sebaiknya wajib dan stabil.
5. `project_feedbacks` menyimpan beberapa data proyek sebagai teks (`nama_proyek`, `kabupaten_kota`, dan `kecamatan`) sehingga analisis lintas tahun, wilayah, OPD, dan proyek akan sulit dilakukan.
6. Nilai enum dan beberapa atribut kategori membuat perubahan pilihan membutuhkan perubahan skema. Untuk data referensi yang berkembang, tabel referensi lebih fleksibel.
7. `dbf_attributes` memang berguna untuk mempertahankan atribut impor, tetapi tidak boleh menjadi satu-satunya sumber data untuk filter dan dashboard. Atribut analitis utama perlu memiliki kolom atau tabel terstruktur.

## 2. Prinsip Perbaikan Struktur

Perbaikan sebaiknya mengikuti pemisahan berikut:

- **Master data**: OPD, wilayah administratif, sektor/urusan, kategori layer, status, dan tipe indikator.
- **Katalog dataset**: identitas layer, metadata, pemilik, versi, sumber, dan status publikasi.
- **Data spasial**: geometri dan atribut layer, termasuk indeks spasial serta atribut analitis yang sering difilter.
- **Konfigurasi peta**: layer aktif, urutan layer, visibilitas, opacity, style, extent, dan filter pada sebuah peta.
- **Berbagi dan audit akses**: token URL, QR code yang merujuk ke URL, masa berlaku, jumlah akses, dan waktu akses terakhir.
- **Data pembangunan**: proyek, lokasi proyek, target, realisasi fisik, realisasi keuangan, indikator, dan histori pelaporan.
- **Analitik dan monitoring**: proyek, lokasi, indikator, progres, dan histori pelaporan pembangunan.

Dengan pemisahan ini, satu layer dapat digunakan pada banyak peta, satu peta dapat mengaktifkan banyak layer, dan metadata tidak perlu disalin ke setiap konfigurasi peta.

## 3. Rancangan Perubahan Entitas

### 3.1. Katalog layer dan metadata

`data_spatial` sebaiknya diposisikan sebagai entitas layer atau dataset utama, lalu diperbaiki dengan atribut berikut:

| Kelompok | Atribut yang disarankan |
| --- | --- |
| Identitas | `uuid` wajib dan unique, `slug` unique, `name`, `title`, `description`, `version` |
| Kepemilikan | `owner_opd_id`, `managed_by_user_id`, `contact_name`, `contact_email`, `contact_phone` |
| Asal dan waktu | `source_name`, `source_url`, `data_date`, `published_at`, `last_updated_at`, `update_frequency` |
| Klasifikasi | `category_id`, `sector_id`, `data_type`, `sub_type`, `theme`, `keywords` |
| Geospasial | `geometry_type`, `srid`, `bbox`, `center_point`, `min_zoom`, `max_zoom` |
| Kualitas dan akses | `quality_notes`, `license`, `attribution`, `visibility`, `is_active`, `is_downloadable` |
| Teknis | `source_file`, `source_format`, `file_size`, `checksum`, `imported_at`, `imported_by` |

Metadata yang berulang atau memiliki struktur sendiri, seperti kata kunci, cakupan wilayah, dan sumber data, lebih baik dipisahkan:

- `spatial_layer_metadata`: satu baris metadata lengkap untuk satu layer.
- `spatial_layer_versions`: histori versi, file sumber, checksum, jumlah fitur, waktu impor, dan catatan perubahan.
- `spatial_layer_keywords`: daftar kata kunci yang terhubung ke layer.
- `spatial_layer_regions`: hubungan many-to-many antara layer dan wilayah administratif.
- `spatial_layer_sources`: sumber data jika satu layer berasal dari beberapa dokumen atau instansi.

`dbf_attributes` tetap dapat dipertahankan sebagai JSONB untuk atribut mentah hasil impor. Akan tetapi, atribut yang dipakai untuk filter dashboard, agregasi, atau pencarian perlu dipetakan ke kolom/tabel terstruktur dan diberi index.

### 3.2. Peta, layer aktif, dan fitur share

Tambahkan entitas berikut:

#### `maps`

Mewakili sebuah peta yang dapat dilihat atau dibagikan.

- `id`, `uuid`, `slug`, `title`, `description`
- `owner_id`, `visibility` (`private`, `unlisted`, `public`), `is_active`
- `center_point`, `zoom`, `bbox`
- `created_at`, `updated_at`, `published_at`

#### `map_layers`

Tabel pivot antara peta dan layer.

- `map_id`, `data_spatial_id`
- `layer_order`, `is_visible`, `opacity`
- `style_config` JSONB, `filter_config` JSONB
- `min_zoom`, `max_zoom`
- unique gabungan `map_id` dan `data_spatial_id`

Konfigurasi layer aktif harus disimpan di `map_layers`, bukan di `data_spatial`, karena satu layer dapat tampil dengan style, filter, atau visibilitas berbeda pada beberapa peta.

#### `map_shares`

Mewakili tautan publik untuk sebuah peta.

- `id`, `map_id`, `token` unique
- `expires_at`, `revoked_at`, `is_active`
- `created_by`, `last_accessed_at`, `access_count`
- `created_at`, `updated_at`

URL publik dibentuk dari token acak yang tidak berasal dari ID berurutan, misalnya `/maps/share/{token}`. QR code tidak perlu disimpan sebagai gambar di database; QR dapat dihasilkan dari URL share saat diminta atau disimpan di object storage dengan `qr_path` jika diperlukan untuk cache. Dengan demikian URL dan QR selalu menunjuk ke konfigurasi peta yang sama.

Jika kebutuhan bisnisnya adalah berbagi satu layer tanpa konfigurasi peta, tambahkan `spatial_layer_shares` dengan pola token dan masa berlaku yang sama. Namun, prioritas awal sebaiknya `map_shares` karena mendukung kumpulan layer aktif, filter, extent, dan style.

### 3.3. Wilayah, proyek, dan indikator pembangunan

Untuk mendukung rekomendasi dashboard eksekutif dan filter wilayah, sektor, OPD, serta tahun, tambahkan struktur berikut:

- `administrative_regions`: provinsi, kabupaten/kota, kecamatan, dan desa/kelurahan dengan kode wilayah resmi serta geometri bila diperlukan.
- `sectors`: master sektor/urusan pembangunan.
- `development_projects`: identitas proyek, OPD penanggung jawab, sektor, lokasi, tahun, status, target, dan sumber anggaran.
- `project_regions`: relasi proyek dengan satu atau beberapa wilayah.
- `project_locations`: titik/garis/polygon proyek dan keterkaitannya dengan `data_spatial` bila proyek ditampilkan sebagai layer.
- `project_progress_reports`: histori pelaporan berkala berisi periode, target fisik, realisasi fisik, target keuangan, realisasi keuangan, status, catatan, dan pelapor.
- `development_indicators`: definisi indikator, satuan, arah capaian, sumber, dan frekuensi pengukuran.
- `indicator_values`: nilai indikator per wilayah, sektor, OPD, dan periode.

Struktur historis ini memungkinkan dashboard menampilkan capaian fisik, realisasi keuangan, progres proyek, indikator kinerja, perbandingan antarwilayah, dan tren tanpa mengandalkan parsing JSON atau teks bebas.

## 4. Perbaikan Integritas dan Konsistensi Data

Prioritas perapihan skema yang dapat dilakukan pada tabel yang sudah ada:

1. Jadikan `data_spatial.uuid` `NOT NULL`, beri default generator UUID di aplikasi, dan gunakan UUID/token sebagai identifier publik.
2. Tambahkan foreign key `categories.user_id -> users.id` bila kolom tersebut memang masih dibutuhkan. Jika tidak dibutuhkan, hapus kolomnya melalui migration setelah memastikan tidak ada pemakai.
3. Tambahkan `opd_id` atau `owner_opd_id` ke layer secara eksplisit daripada menyimpulkan pemilik dari user pembuat.
4. Tambahkan foreign key dan kebijakan penghapusan yang jelas untuk seluruh relasi baru. Gunakan `restrict` untuk master yang masih dipakai dan `set null` untuk kepemilikan historis.
5. Ganti teks bebas yang menjadi master data (`publications.category`, wilayah pada `project_feedbacks`) dengan foreign key ke tabel referensi.
6. Pertimbangkan mengganti enum bisnis yang sering berubah dengan tabel referensi atau status code. Jika enum dipertahankan, dokumentasikan transisi yang diperbolehkan.
7. Pastikan tipe koordinat konsisten. Untuk data spasial gunakan PostGIS dengan SRID yang jelas; kolom latitude/longitude hanya dipakai untuk data sederhana atau kompatibilitas API.
8. Tambahkan constraint validasi database untuk rating, opacity, tahun, longitude, latitude, serta nilai realisasi agar data tidak berada di luar rentang yang masuk akal.
9. Hindari index duplikat yang sudah teridentifikasi pada `opd.singkatan` dan `data_spatial.dbf_attributes`. Audit index sebelum migration baru.
10. Pisahkan file sumber layer dari metadata layer dan simpan informasi file dalam tabel versi atau storage terkelola. Simpan nama file, format, ukuran, checksum, dan waktu impor.
11. Pertahankan timestamp audit (`created_at`, `updated_at`, dan bila relevan `deleted_at`) pada entitas master, layer, peta, proyek, dan histori publikasi.

## 5. Kebutuhan Query dan Index Dashboard

Query dashboard utama akan banyak melakukan filter berdasarkan tahun, OPD, sektor, wilayah, status, dan kategori. Karena itu:

- gunakan foreign key bertipe konsisten dan index pada pasangan kolom yang sering dipakai bersama, misalnya `(opd_id, tahun, status)`;
- buat index pada `data_spatial` untuk `(data_type, kategori_id)`, `(opd_id, tahun)`, serta `GIST(geom)`;
- buat index pada nilai indikator untuk `(indicator_id, region_id, period)`;
- buat index akses share pada `(map_share_id, accessed_at)` bila log akses disimpan terpisah;
- jangan menyimpan agregat dashboard hanya di tabel transaksi tanpa definisi sumber dan waktu perhitungan;
- bila volume meningkat, siapkan materialized view atau tabel snapshot dashboard yang memiliki `calculated_at` dan sumber data.

## 6. Urutan Implementasi yang Disarankan

### Tahap 1: Fondasi dan metadata layer

- Rapikan constraint, foreign key, UUID, index, dan tipe data.
- Tambahkan metadata layer, pemilik OPD, status publikasi, lisensi, sumber, tanggal data, dan tanggal pembaruan.
- Tambahkan versi layer agar pembaruan data tidak menghapus jejak versi sebelumnya.
- Tambahkan master wilayah dan sektor.

### Tahap 2: Peta dan berbagi

- Buat `maps` dan `map_layers`.
- Pindahkan konfigurasi layer aktif, urutan, style, filter, extent, dan opacity ke `map_layers`.
- Buat `map_shares` dengan token acak, expiry, revoke, dan audit akses.
- Sediakan endpoint publik yang hanya memuat peta/layer berstatus public atau memiliki share token aktif.
- Hasilkan QR code dari URL share, bukan dari data layer yang disalin.

### Tahap 3: Dashboard pembangunan

- Buat proyek, lokasi proyek, laporan progres, indikator, dan nilai indikator.
- Hubungkan proyek ke OPD, sektor, tahun, dan wilayah.
- Bangun query agregasi untuk fisik, keuangan, progres, capaian, dan tren.
- Simpan definisi indikator dan sumber data agar angka dashboard dapat ditelusuri.

## 7. Risiko dan Keputusan yang Perlu Ditetapkan

- Apakah satu layer dapat memiliki beberapa versi aktif untuk periode berbeda, atau hanya satu versi terbaru?
- Apakah peta publik menampilkan data real-time atau snapshot pada saat URL dibuat?
- Apakah share token dapat diakses siapa saja yang memiliki URL, dan apakah token harus kedaluwarsa otomatis?
- Apakah QR code perlu dicetak dan disimpan permanen, atau cukup dibuat dinamis?
- Apakah wilayah proyek mengikuti kode wilayah resmi BPS/Kemendagri dan sampai tingkat administrasi mana?
- Siapa yang berwenang menerbitkan metadata, mempublikasikan layer, dan mengubah status proyek?
- Apakah nilai keuangan perlu menyimpan mata uang, tahun anggaran, sumber dana, dan status audit?

## 8. Kesimpulan

Perbaikan utama yang dibutuhkan adalah mengubah database dari kumpulan tabel operasional menjadi katalog data pembangunan yang memiliki metadata, versioning, governance, konfigurasi peta, dan histori. `data_spatial` tetap dapat dipertahankan sebagai inti data geospasial, tetapi perlu diperkaya dan dipisahkan dari konsep peta yang dibagikan.

Prioritas tertinggi adalah metadata layer, `maps`/`map_layers`, `map_shares`, master wilayah dan sektor, serta data proyek dan indikator. Setelah fondasi tersebut tersedia, dashboard eksekutif dapat dibangun dengan data proyek dan indikator yang terstruktur, bukan dari teks bebas atau atribut JSON yang sulit divalidasi.