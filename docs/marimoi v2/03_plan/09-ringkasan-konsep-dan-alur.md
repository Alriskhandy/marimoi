# Ringkasan Konsep dan Alur MARIMOI V2

## 1. Tujuan

MARIMOI V2 dikembangkan sebagai WebGIS dan dashboard pembangunan daerah yang menggabungkan:

- peta tematik dan peta pembangunan;
- metadata layer dan data spasial;
- data proyek, lokasi, progres fisik, dan keuangan;
- indikator pembangunan dan analisis dashboard;
- aspirasi, kritik/saran, serta tanggapan proyek;
- sharing peta melalui URL dan QR code;
- authentication, authorization, dan audit aktivitas.

Arsitektur awal menggunakan modular monolith Laravel dengan PostgreSQL/PostGIS. Pemisahan service seperti GOAT GeoAPI, Processes API, DuckLake, dan Windmill hanya dipertimbangkan jika kebutuhan skala dan performa sudah terbukti.

## 2. Istilah Utama

| Istilah | Pengertian | Tabel canonical V2 |
| --- | --- | --- |
| Map | Konfigurasi atau komposisi peta yang dilihat dan dibagikan user | `maps` |
| Layer | Dataset bertema dengan metadata, sumber, owner, dan versi | `spatial_layers` |
| Map layer | Relasi layer di dalam map beserta konfigurasi tampilannya | `map_layers` |
| Feature | Satu objek/record di dalam layer | `spatial_layer_features` |
| Geometry | Bentuk spasial feature: Point, LineString, Polygon, atau Multi* | `spatial_layer_features.geometry` |
| Development project | Identitas bisnis proyek pembangunan | `development_projects` |
| Project location | Lokasi geometry milik proyek pembangunan | `project_locations` |
| Progress report | Laporan target dan realisasi proyek per periode | `project_progress_reports` |
| Development indicator | Definisi indikator dan cara menghitungnya | `development_indicators` |
| Indicator value | Nilai indikator untuk dimensi wilayah/periode tertentu | `indicator_values` |

## 3. Perbedaan Schema Lama dan Schema Baru

### Schema lama

Pada schema lama:

```text
categories (layer/tree layer)
└── data_spatial (feature)
    ├── dbf_attributes (atribut feature)
    └── geom (geometry feature)
```

- `categories` berfungsi sebagai layer dan membentuk tree.
- `data_spatial` berfungsi sebagai feature.
- `data_spatial.kategori_id` mengarah ke `categories.id`.
- `categories.type` digunakan untuk membedakan menu seperti tematik, PSD, PSN, Pokir DPRD, dan Musrenbang.

### Schema baru

Schema baru memperjelas penamaan dan memisahkan tanggung jawab:

```text
spatial_layers
└── spatial_layer_features
    ├── attributes
    └── geometry

maps
└── map_layers
    └── spatial_layers
```

- `spatial_layers` menyimpan identitas dan metadata layer.
- `spatial_layer_features` menyimpan feature.
- `maps` menyimpan konfigurasi peta.
- `map_layers` menyimpan cara layer digunakan pada map tertentu.
- `categories` dan `data_spatial` menjadi sumber migrasi dan compatibility layer selama masa transisi.

## 4. Perbedaan `maps` dan `development_projects`

### `maps`

`maps` adalah wadah tampilan peta. Tabel ini menyimpan:

- layer yang ditampilkan;
- urutan layer;
- visibility;
- opacity;
- style;
- filter;
- basemap;
- center dan zoom;
- publication dan share.

`maps` tidak menyimpan identitas proyek, anggaran, atau progres.

### `development_projects`

`development_projects` adalah data bisnis pembangunan. Tabel ini menyimpan:

- kode dan nama proyek;
- OPD penanggung jawab;
- sektor;
- tahun anggaran;
- status;
- pagu;
- sumber dana.

Satu proyek dapat ditampilkan pada satu atau beberapa map, tetapi proyek bukan map.

## 5. Relasi Data Peta dan Pembangunan

Relasi utama:

```text
maps
└── map_layers
    └── spatial_layers
        └── spatial_layer_features

spatial_layer_features
└── project_locations
    └── development_projects
        └── project_progress_reports
```

Relasi feature dengan proyek perlu dibuat eksplisit, misalnya melalui:

- `spatial_layer_features.project_location_id`; atau
- `project_locations.spatial_layer_feature_id`.

Pilihan tersebut mencegah aplikasi hanya mengandalkan pencarian nama proyek di `attributes` JSONB.

Perbedaannya:

| Data | Fungsi |
| --- | --- |
| `spatial_layer_features` | Objek spasial umum pada sebuah layer |
| `project_locations` | Lokasi bisnis yang dimiliki proyek |
| `development_projects` | Identitas dan informasi bisnis proyek |
| `project_progress_reports` | Histori kinerja proyek |

## 6. Apakah Input Peta dan Pembangunan Terpisah?

Ya. Input dilakukan melalui workflow terpisah, tetapi datanya saling terhubung.

### Workflow input layer/peta

1. Admin membuat atau memilih layer.
2. Admin mengisi metadata layer.
3. Admin mengunggah file atau memilih sumber data.
4. Sistem membuat `spatial_layer_versions`.
5. Sistem memvalidasi SRID dan geometry.
6. Sistem menyimpan feature ke `spatial_layer_features`.
7. Sistem menghitung extent dan feature count.
8. Admin menyusun `maps` dan `map_layers`.
9. Map dapat dipublikasikan dan dibagikan.

### Workflow input data pembangunan

1. Admin membuat `development_projects`.
2. Admin mengisi OPD, sektor, tahun, status, pagu, dan sumber dana.
3. Admin menambahkan `project_regions`.
4. Admin menambahkan satu atau beberapa `project_locations`.
5. Admin mengirim `project_progress_reports` secara berkala.
6. Sistem menampilkan proyek pada map dan dashboard melalui relasi lokasi.

Form layer tidak perlu mencampur upload geometry dengan laporan progres. Form proyek juga tidak perlu mengulang metadata layer.

## 7. Alur Admin Bappeda

Admin Bappeda memiliki akses lintas OPD sesuai policy.

### Pengelolaan layer

Admin Bappeda dapat:

- membuat dan mengatur layer lintas OPD;
- memeriksa metadata dan kualitas geometry;
- mengelola kategori/sektor/wilayah;
- menyetujui publication layer atau map;
- membaca seluruh data spasial.

### Pengelolaan proyek

Admin Bappeda dapat:

- membuat atau mengimpor proyek lintas OPD;
- menghubungkan proyek dengan OPD, sektor, wilayah, dan lokasi;
- melihat semua laporan progres;
- menyusun map dan dashboard lintas OPD;
- memvalidasi data untuk dashboard publik/eksekutif.

## 8. Alur Admin OPD

Admin OPD dibatasi oleh `user.opd_id`.

```text
resource.owner_opd_id = user.opd_id
```

Admin OPD dapat:

- mengelola layer milik OPD;
- menginput dan memperbarui proyek OPD;
- menambahkan lokasi proyek;
- mengirim laporan progres dan keuangan;
- melihat serta menangani feedback proyek OPD.

Admin OPD tidak dapat mengubah data OPD lain. Pembatasan harus diterapkan pada backend Policy dan query, bukan hanya disembunyikan pada UI.

Sebaiknya Admin OPD dapat menyimpan atau mengajukan data, sedangkan publication final untuk data strategis dapat memerlukan persetujuan Admin Bappeda.

## 9. Tampilan User

### Menu Peta

User melihat satu menu Peta dengan pengelompokan baru:

```text
Peta
├── Layer Tematik
│   ├── Jalan
│   ├── Sekolah
│   └── Fasilitas Kesehatan
└── Layer Pembangunan
    ├── Proyek Jalan
    ├── Proyek Jembatan
    └── Proyek Kawasan
```

User dapat:

- mengaktifkan dan menonaktifkan layer;
- memilih wilayah;
- memilih sektor;
- memilih OPD;
- memilih tahun;
- memilih status;
- melihat legend dan metadata layer;
- membuka detail feature.

### Detail feature proyek

Alur ketika feature diklik:

```text
User klik geometry
→ spatial_layer_feature
→ project_location
→ development_project
→ progress report terbaru
→ detail proyek
```

Detail dapat menampilkan nama proyek, OPD, tahun, status, pagu, progres fisik, realisasi keuangan, lokasi, sumber, dan waktu pembaruan.

### Dashboard eksekutif

Dashboard membaca data bisnis, bukan hanya konfigurasi map:

```text
development_projects
+ project_progress_reports
+ indicator_values
+ administrative_regions
+ opd
+ sectors
```

Komponen utama:

- jumlah proyek aktif;
- progres fisik;
- realisasi keuangan;
- proyek terlambat;
- capaian indikator;
- tren tahunan;
- perbandingan wilayah, sektor, dan OPD;
- peta infrastruktur dan pengembangan wilayah.

## 10. Sharing Peta

User publik wajib login untuk membuat share URL.

```text
User publik
→ memilih layer/filter aktif
→ maps
→ map_layers
→ map_publications
→ map_shares
→ URL/QR
```

`map_shares` menyimpan token read-only terhadap publication. `expires_at` dapat:

- diisi untuk share yang kedaluwarsa;
- dikosongkan (`NULL`) untuk share tanpa batas waktu.

Keputusan penerima URL masih terbuka:

- publication public dapat dibuka guest; atau
- semua penerima wajib login.

Rekomendasi awal: publication `public` dapat dibuka guest, sedangkan publication `unlisted` atau data terbatas memerlukan login.

## 11. Metadata Layer

Gunakan metadata bertingkat agar penginputan tidak terlalu berat:

| Tingkat | Field minimum |
| --- | --- |
| Draft | nama/judul layer dan owner |
| Internal aktif | draft + sumber data, tanggal/tahun data, OPD pengelola, tipe geometry, SRID |
| Public | internal aktif + waktu validasi, deskripsi, dan lisensi/attribution atau catatan penggunaan |

Metadata wajib utama sebelum public:

- sumber data;
- tanggal/tahun referensi atau pembaruan;
- instansi pengelola.

Metadata tambahan seperti kontak, URL sumber, lineage, akurasi, kelengkapan, dan limitations disarankan untuk dataset strategis.

## 12. Tabel Utama dan Fungsi

| Tabel | Posisi dan fungsi |
| --- | --- |
| `spatial_layers` | Master/katalog layer canonical |
| `spatial_layer_metadata` | Metadata deskriptif layer |
| `spatial_layer_versions` | Histori import dan versi layer |
| `spatial_layer_features` | Data feature/geometri layer |
| `maps` | Master/configuration peta |
| `map_layers` | Relasi dan konfigurasi layer dalam map |
| `map_publications` | Snapshot/revisi map yang diterbitkan |
| `map_shares` | Token URL/QR read-only |
| `development_projects` | Master identitas proyek |
| `project_locations` | Lokasi spasial proyek |
| `project_progress_reports` | Histori progres dan keuangan |
| `development_indicators` | Master definisi indikator |
| `indicator_values` | Nilai indikator per wilayah/periode |
| `categories` | Layer/tree pada schema lama |
| `data_spatial` | Feature pada schema lama |

## 13. Dampak Jika Tabel Pembangunan Tidak Ada

Tanpa `development_projects`:

- proyek tidak memiliki identitas terstruktur;
- raw attribute/text menjadi sumber utama;
- sulit mencegah duplikasi;
- feedback sulit dikaitkan ke proyek.

Tanpa `project_locations`:

- proyek multi-lokasi sulit direpresentasikan;
- geometry proyek tidak memiliki model bisnis yang jelas;
- spatial filter dan pengaitan feedback menjadi terbatas.

Tanpa `project_progress_reports`:

- hanya kondisi terakhir yang diketahui;
- tidak ada tren progres;
- target dan realisasi per periode tidak dapat dibandingkan;
- audit keterlambatan lemah.

Tanpa `development_indicators`:

- angka dashboard tidak memiliki definisi, satuan, formula, atau pemilik yang jelas;
- indikator sulit dibandingkan antarperiode.

Tanpa `indicator_values`:

- definisi indikator ada, tetapi tidak ada nilai aktual untuk dashboard;
- tidak dapat membuat tren, ranking, atau perbandingan wilayah.

## 14. Keputusan yang Sudah Ditetapkan

- `categories` dan `data_spatial` adalah tabel lama.
- Schema baru menggunakan `spatial_layers` dan `spatial_layer_features`.
- Kode wilayah menggunakan standar Kemendagri.
- Admin Bappeda memiliki akses seluruh data spasial.
- Admin OPD dibatasi berdasarkan OPD resource.
- User publik wajib login untuk membuat share URL.
- Share URL dapat memiliki batas waktu atau tanpa batas waktu.
- Sumber data, tanggal data/pembaruan, dan instansi pengelola menjadi metadata wajib utama sebelum publication.
- Peta lama digabung menjadi satu menu Peta dengan klasifikasi tematik dan pembangunan.

## 15. Keputusan yang Masih Terbuka

- Level seed wilayah: kabupaten/kota, kecamatan, atau sampai desa/kelurahan.
- Penerima share URL public boleh guest atau wajib login.
- Publication membaca current layer version atau snapshot version tertentu.
- Metadata tambahan mana yang wajib untuk layer strategis.
- Retensi activity log, authentication log, access log, dan data pribadi.
- Apakah publication final Admin OPD memerlukan approval Admin Bappeda.
- Relasi final antara `spatial_layer_features` dan `project_locations`.
