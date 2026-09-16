# Plan Arsitektur dan Database

## Tujuan

Membentuk fondasi data yang memisahkan layer, feature, geometri, konfigurasi peta, user, data pembangunan, dan audit tanpa memutus fitur lama.

## Target Domain

- **Catalog**: layer, metadata, sumber, versi, keyword, wilayah, dan status publikasi.
- **Mapping**: map, map layer, group, basemap, style, filter, extent, dan snapshot.
- **Development**: proyek, lokasi, sektor, wilayah, progres, keuangan, dan indikator.
- **Identity**: user, role, OPD, scope permission, dan provider login.
- **Governance**: activity log, authentication log, publication, dan share access.

## Entitas Prioritas

| Entitas | Fungsi |
| --- | --- |
| `data_spatial` | compatibility layer yang dikendalikan selama transisi |
| `spatial_layer_metadata` | judul, sumber, owner, SRID, kualitas, dan waktu pembaruan |
| `spatial_layer_versions` | histori import, checksum, feature count, dan current version |
| `maps` | container peta dan owner |
| `map_layers` | konfigurasi layer di dalam map |
| `map_publications` | versi map yang diterbitkan |
| `development_projects` | identitas proyek pembangunan |
| `project_progress_reports` | histori progres fisik dan keuangan |
| `administrative_regions` | wilayah resmi dan hierarki parent-child |
| `sectors` | master sektor/urusan pembangunan |

## Aturan Data

- Gunakan identifier publik acak/UUID; jangan gunakan ID integer sebagai URL publik.
- `owner_user_id` dan `owner_opd_id` disimpan langsung, bukan disimpulkan dari user saat query.
- `dbf_attributes` atau `raw_attributes` hanya untuk data mentah; field analitis wajib terstruktur.
- Nama tahun harus spesifik: `data_year`, `fiscal_year`, atau `publication_year`.
- Geometry memakai tipe dan SRID eksplisit serta GiST index.
- Foreign key, unique, check constraint, dan kebijakan delete ditentukan pada migration.
- Metadata layer tidak disalin ke `map_layers`; map layer hanya menyimpan konfigurasi penggunaan.

## Urutan Migration

1. audit data lama: UUID null/duplikat, geometri invalid, SRID, dan owner;
2. tambahkan kolom baru secara nullable;
3. backfill dalam batch dan tandai data yang perlu review;
4. tambahkan foreign key/index setelah data valid;
5. ubah kolom wajib menjadi `NOT NULL`;
6. migrasikan pemakai aplikasi;
7. deprecate kolom lama setelah compatibility period.

## Keputusan yang Dibutuhkan

- apakah feature tetap disimpan di PostGIS untuk fase awal;
- standar kode wilayah dan tingkat administrasi;
- satu current version atau version berdasarkan periode;
- map publik real-time atau snapshot;
- definisi owner dan status publikasi minimum.
