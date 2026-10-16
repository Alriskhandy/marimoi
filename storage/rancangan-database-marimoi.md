# Rancangan Database MARIMOI (WebGIS)

> **Versi dokumen:** 1.0
> **Tanggal:** 2 Oktober 2026
> **DBMS target:** PostgreSQL 15+ dengan ekstensi PostGIS 3.3+
> **Ruang lingkup:** Core system: data layer, kategori, metadata, sumber data, impor, atribut, style, versi, fitur spasial, dan dokumen pendukung.

---

## 1. Prinsip Desain

### 1.1 Layer adalah objek GIS utama

```
Layer
├── berada di 1 kategori (opsional di 1 node/subkategori)
├── memiliki 1 jenis layer
├── memiliki 1 metadata
├── memiliki banyak sumber data
├── memiliki banyak riwayat impor
├── memiliki banyak definisi atribut
├── memiliki banyak style (1 default)
├── memiliki banyak versi (1 aktif)
├── memiliki banyak spatial feature
└── memiliki banyak dokumen pendukung
```

### 1.2 Kategori adalah organisasi dan discovery layer

```
Kategori (categories)
└── Subkategori (category_nodes)
    └── Subkategori (category_nodes)
        └── Layer
```

- `categories` adalah **akar** (misalnya: *Tata Ruang*, *Kebencanaan*, *Infrastruktur*).
- `category_nodes` adalah **pohon subkategori** di bawah sebuah kategori, dengan kedalaman tak terbatas (self-reference `parent_id`).
- Layer dapat ditempatkan langsung di akar kategori (`category_node_id = NULL`) atau di salah satu node.

### 1.3 Peta adalah tampilan, bukan data master

```
Layer yang tersimpan (database)
        ↓
User memilih layer di panel kategori
        ↓
Active layer state (di browser: state aplikasi / URL / localStorage)
        ↓
Render di peta
```

### 1.4 Database tidak berubah karena user menampilkan atau menyembunyikan layer

Ribuan pengguna dapat membuka MARIMOI dan memakai **dataset layer yang sama**, masing-masing dengan kombinasi layer berbeda di browser, tanpa menulis satu record pun ke database. Karena itu, entitas berikut **tidak** termasuk core system:

| Entitas | Keputusan | Alasan |
|---|---|---|
| `maps` | Tidak | Peta bukan data master |
| `map_layers` | Tidak | State tampilan disimpan di client |
| `map_shares` | Tidak | Berbagi tampilan cukup lewat URL state |
| `category_layers` (dulu `catalog_layers`) | Tidak | Relasi layer–kategori cukup lewat FK di `layers` (1 layer = 1 kategori) |
| `saved_maps` | Opsional | Hanya jika nanti ada fitur "Simpan Peta" (lihat §6) |

---

## 2. Daftar Entitas

| No | Entitas | Status | Fungsi singkat |
|---|---|---|---|
| 1 | `categories` | Core | Kategori akar untuk organisasi layer |
| 2 | `category_nodes` | Core | Pohon subkategori di dalam kategori |
| 3 | `layer_types` | Core (referensi) | Jenis layer: titik, garis, poligon, raster, WMS, dll. |
| 4 | `layers` | Core | Objek GIS utama |
| 5 | `layer_metadata` | Core | Metadata deskriptif layer (1:1) |
| 6 | `layer_sources` | Core | Asal data: file unggahan atau layanan eksternal |
| 7 | `layer_imports` | Core | Riwayat proses impor file ke database |
| 8 | `layer_attribute_definitions` | Core | Skema atribut yang terstandar per layer |
| 9 | `layer_attribute_mappings` | Core | Pemetaan kolom file sumber → atribut standar per impor |
| 10 | `layer_styles` | Core | Simbolisasi dan legenda layer |
| 11 | `layer_versions` | Core | Versi data layer |
| 12 | `spatial_features` | Core | Geometri dan atribut setiap fitur |
| 13 | `documents` | Core (bersyarat) | Dokumen pendukung (SK, Perda, laporan, PDF peta) |
| 14 | `saved_maps` | Opsional | Snapshot tampilan peta milik user |
| — | `users` | Pendukung | Pengelola data (admin, operator) |
| — | `audit_logs` | Pendukung | Jejak perubahan data |

> `users` dan `audit_logs` bukan bagian dari domain GIS, tetapi dibutuhkan agar kolom `created_by`, `updated_by`, dan jejak audit memiliki referensi yang valid. Jika MARIMOI sudah memiliki modul autentikasi sendiri, cukup sesuaikan FK-nya.

---

## 3. Diagram Relasi (ERD)

```mermaid
erDiagram
    categories ||--o{ category_nodes : "memiliki"
    category_nodes ||--o{ category_nodes : "induk dari"
    categories ||--o{ layers : "mengelompokkan"
    category_nodes |o--o{ layers : "menampung"
    layer_types ||--o{ layers : "menjeniskan"

    layers ||--|| layer_metadata : "dideskripsikan"
    layers ||--o{ layer_sources : "bersumber dari"
    layers ||--o{ layer_imports : "diimpor lewat"
    layers ||--o{ layer_versions : "berversi"
    layers ||--o{ layer_attribute_definitions : "berskema"
    layers ||--o{ layer_styles : "bergaya"
    layers ||--o{ spatial_features : "berisi"
    layers ||--o{ documents : "didukung"

    layer_sources |o--o{ layer_imports : "dipakai oleh"
    layer_imports ||--o{ layer_attribute_mappings : "memetakan"
    layer_attribute_definitions ||--o{ layer_attribute_mappings : "dipetakan ke"
    layer_imports |o--o| layer_versions : "menghasilkan"
    layer_versions ||--o{ spatial_features : "memuat"

    users ||--o{ saved_maps : "menyimpan"
```

Ringkasan kardinalitas:

| Relasi | Kardinalitas |
|---|---|
| `categories` → `category_nodes` | 1 : N |
| `category_nodes` → `category_nodes` (parent) | 1 : N |
| `categories` → `layers` | 1 : N |
| `category_nodes` → `layers` | 1 : N (opsional) |
| `layer_types` → `layers` | 1 : N |
| `layers` → `layer_metadata` | 1 : 1 |
| `layers` → `layer_sources` / `layer_imports` / `layer_versions` / `layer_attribute_definitions` / `layer_styles` / `documents` | 1 : N |
| `layers` → `spatial_features` | 1 : N (sangat besar) |
| `layer_versions` → `spatial_features` | 1 : N |
| `layer_imports` → `layer_attribute_mappings` | 1 : N |
| `layer_imports` → `layer_versions` | 1 : 0..1 |

---

## 4. Konvensi

| Aspek | Ketentuan |
|---|---|
| Penamaan | `snake_case`, nama tabel jamak (plural), bahasa Inggris |
| Primary key | `uuid` (`gen_random_uuid()`), kecuali `spatial_features` memakai `bigint identity` karena volumenya besar |
| Waktu | `timestamptz`, disimpan dalam UTC; tampilan dikonversi ke WIT (Asia/Jayapura) di aplikasi |
| Soft delete | Kolom `deleted_at` pada tabel master (`categories`, `category_nodes`, `layers`, `documents`) |
| SRID penyimpanan | **EPSG:4326** (WGS 84). Data sumber dengan SRID lain ditransformasi saat impor (`ST_Transform`) |
| Perhitungan luas/panjang | Memakai `geography` atau proyeksi UTM zona 52N/52S (EPSG:32652 / EPSG:32752) untuk wilayah Maluku Utara |
| Nilai enumerasi | `text` + `CHECK` (lebih mudah diubah daripada tipe `ENUM` PostgreSQL) |
| Atribut dinamis | `jsonb` (`properties`), dengan skema yang dijaga oleh `layer_attribute_definitions` |
| `updated_at` | Diperbarui otomatis melalui trigger |

---

## 5. Definisi Tabel

### 5.0 Persiapan

```sql
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS pgcrypto;   -- gen_random_uuid()
CREATE EXTENSION IF NOT EXISTS ltree;      -- path hierarki category_nodes
CREATE EXTENSION IF NOT EXISTS pg_trgm;    -- pencarian teks fuzzy

-- Trigger umum untuk updated_at
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger AS $$
BEGIN
  NEW.updated_at := now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

---

### 5.1 `users` (pendukung)

Pengelola data. Pengunjung publik tidak perlu akun untuk melihat peta.

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `id` | uuid | PK | |
| `name` | varchar(150) | Tidak | |
| `email` | varchar(150) | Tidak | Unik |
| `password_hash` | text | Tidak | |
| `role` | text | Tidak | `super_admin`, `admin`, `operator`, `viewer` |
| `organization` | varchar(200) | Ya | Instansi/OPD |
| `is_active` | boolean | Tidak | Default `true` |
| `last_login_at` | timestamptz | Ya | |
| `created_at`, `updated_at` | timestamptz | Tidak | |

```sql
CREATE TABLE users (
  id             uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  name           varchar(150) NOT NULL,
  email          varchar(150) NOT NULL UNIQUE,
  password_hash  text NOT NULL,
  role           text NOT NULL DEFAULT 'operator'
                 CHECK (role IN ('super_admin','admin','operator','viewer')),
  organization   varchar(200),
  is_active      boolean NOT NULL DEFAULT true,
  last_login_at  timestamptz,
  created_at     timestamptz NOT NULL DEFAULT now(),
  updated_at     timestamptz NOT NULL DEFAULT now()
);
```

---

### 5.2 `categories`

Kategori akar yang tampil sebagai kelompok utama di panel layer.

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `id` | uuid | PK | |
| `code` | varchar(50) | Tidak | Kode unik, mis. `TATA_RUANG` |
| `name` | varchar(150) | Tidak | Nama tampilan |
| `slug` | varchar(160) | Tidak | Unik, untuk URL |
| `description` | text | Ya | |
| `icon` | varchar(100) | Ya | Nama ikon untuk UI |
| `color` | varchar(9) | Ya | Warna aksen (hex) |
| `sort_order` | integer | Tidak | Urutan tampil |
| `is_active` | boolean | Tidak | Kategori nonaktif tidak tampil di publik |
| `created_by`, `updated_by` | uuid | Ya | FK `users` |
| `created_at`, `updated_at`, `deleted_at` | timestamptz | | |

```sql
CREATE TABLE categories (
  id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  code         varchar(50)  NOT NULL,
  name         varchar(150) NOT NULL,
  slug         varchar(160) NOT NULL,
  description  text,
  icon         varchar(100),
  color        varchar(9),
  sort_order   integer NOT NULL DEFAULT 0,
  is_active    boolean NOT NULL DEFAULT true,
  created_by   uuid REFERENCES users(id),
  updated_by   uuid REFERENCES users(id),
  created_at   timestamptz NOT NULL DEFAULT now(),
  updated_at   timestamptz NOT NULL DEFAULT now(),
  deleted_at   timestamptz
);

CREATE UNIQUE INDEX uq_categories_code ON categories (code) WHERE deleted_at IS NULL;
CREATE UNIQUE INDEX uq_categories_slug ON categories (slug) WHERE deleted_at IS NULL;
```

---

### 5.3 `category_nodes`

Subkategori bertingkat di dalam satu kategori.

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `id` | uuid | PK | |
| `category_id` | uuid | Tidak | FK `categories` |
| `parent_id` | uuid | Ya | FK `category_nodes`; `NULL` = node tingkat pertama |
| `name` | varchar(150) | Tidak | |
| `slug` | varchar(160) | Tidak | Unik di antara saudara (sibling) |
| `description` | text | Ya | |
| `path` | ltree | Tidak | Jalur materialized, mis. `tata_ruang.rtrw.pola_ruang` |
| `depth` | smallint | Tidak | 1 = tingkat pertama |
| `sort_order` | integer | Tidak | |
| `is_active` | boolean | Tidak | |
| audit & waktu | | | sama seperti `categories` |

Aturan:
- `parent_id` harus berada di `category_id` yang sama (dijaga composite FK).
- `path` dan `depth` diisi trigger saat insert/pindah node, sehingga query satu cabang cukup `WHERE path <@ 'tata_ruang.rtrw'`.

```sql
CREATE TABLE category_nodes (
  id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  category_id  uuid NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
  parent_id    uuid,
  name         varchar(150) NOT NULL,
  slug         varchar(160) NOT NULL,
  description  text,
  path         ltree NOT NULL,
  depth        smallint NOT NULL DEFAULT 1 CHECK (depth >= 1),
  sort_order   integer NOT NULL DEFAULT 0,
  is_active    boolean NOT NULL DEFAULT true,
  created_by   uuid REFERENCES users(id),
  updated_by   uuid REFERENCES users(id),
  created_at   timestamptz NOT NULL DEFAULT now(),
  updated_at   timestamptz NOT NULL DEFAULT now(),
  deleted_at   timestamptz,

  -- dibutuhkan agar layers & child node bisa memastikan konsistensi kategori
  CONSTRAINT uq_category_nodes_id_category UNIQUE (id, category_id),
  CONSTRAINT fk_category_nodes_parent
    FOREIGN KEY (parent_id, category_id)
    REFERENCES category_nodes (id, category_id) ON DELETE CASCADE,
  CONSTRAINT ck_category_nodes_not_self CHECK (parent_id IS DISTINCT FROM id)
);

CREATE UNIQUE INDEX uq_category_nodes_sibling_slug
  ON category_nodes (category_id, COALESCE(parent_id, '00000000-0000-0000-0000-000000000000'::uuid), slug)
  WHERE deleted_at IS NULL;
CREATE INDEX ix_category_nodes_path   ON category_nodes USING gist (path);
CREATE INDEX ix_category_nodes_parent ON category_nodes (parent_id);
```

---

### 5.4 `layer_types`

Tabel referensi jenis layer. Isinya relatif tetap (seed data).

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `id` | smallint | PK | |
| `code` | varchar(40) | Tidak | Unik |
| `name` | varchar(100) | Tidak | |
| `data_kind` | text | Tidak | `vector`, `raster`, `service` |
| `geometry_type` | text | Ya | `POINT`, `LINESTRING`, `POLYGON`, `MULTI*`, `GEOMETRY`; `NULL` untuk raster/service |
| `stores_features` | boolean | Tidak | `true` jika fiturnya disimpan di `spatial_features` |
| `description` | text | Ya | |

```sql
CREATE TABLE layer_types (
  id               smallint PRIMARY KEY,
  code             varchar(40)  NOT NULL UNIQUE,
  name             varchar(100) NOT NULL,
  data_kind        text NOT NULL CHECK (data_kind IN ('vector','raster','service')),
  geometry_type    text CHECK (geometry_type IN
                     ('POINT','MULTIPOINT','LINESTRING','MULTILINESTRING',
                      'POLYGON','MULTIPOLYGON','GEOMETRY')),
  stores_features  boolean NOT NULL DEFAULT true,
  description      text
);

INSERT INTO layer_types (id, code, name, data_kind, geometry_type, stores_features) VALUES
 (1,  'vector_point',      'Vektor Titik',         'vector',  'MULTIPOINT',      true),
 (2,  'vector_line',       'Vektor Garis',         'vector',  'MULTILINESTRING', true),
 (3,  'vector_polygon',    'Vektor Poligon',       'vector',  'MULTIPOLYGON',    true),
 (4,  'vector_mixed',      'Vektor Campuran',      'vector',  'GEOMETRY',        true),
 (10, 'raster_cog',        'Raster (COG/GeoTIFF)', 'raster',  NULL,              false),
 (20, 'service_wms',       'Layanan WMS',          'service', NULL,              false),
 (21, 'service_wmts',      'Layanan WMTS',         'service', NULL,              false),
 (22, 'service_xyz',       'Tile XYZ',             'service', NULL,              false),
 (23, 'service_arcgis',    'ArcGIS REST',          'service', NULL,              false),
 (24, 'service_vector_tile','Vector Tile (MVT)',   'service', NULL,              false);
```

---

### 5.5 `layers`

Objek GIS utama.

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `id` | uuid | PK | |
| `category_id` | uuid | Tidak | FK `categories` |
| `category_node_id` | uuid | Ya | FK `category_nodes` (harus di kategori yang sama) |
| `layer_type_id` | smallint | Tidak | FK `layer_types` |
| `code` | varchar(80) | Tidak | Kode unik, mis. `RTRW_POLA_RUANG_2024` |
| `name` | varchar(200) | Tidak | Nama tampilan |
| `slug` | varchar(220) | Tidak | Unik |
| `short_description` | varchar(500) | Ya | Ringkasan untuk panel layer |
| `geometry_type` | text | Ya | Dipertegas per layer (bisa lebih spesifik dari `layer_types`) |
| `storage_srid` | integer | Tidak | Default 4326 |
| `bbox` | geometry(Polygon,4326) | Ya | Batas cakupan versi aktif, untuk *zoom to layer* |
| `feature_count` | integer | Tidak | Jumlah fitur versi aktif (cache) |
| `current_version_id` | uuid | Ya | FK `layer_versions` (versi aktif) |
| `default_style_id` | uuid | Ya | FK `layer_styles` |
| `visibility` | text | Tidak | `public`, `internal`, `private` |
| `status` | text | Tidak | `draft`, `published`, `archived` |
| `is_default_on` | boolean | Tidak | Langsung tampil saat peta pertama dibuka |
| `is_downloadable` | boolean | Tidak | Boleh diunduh publik |
| `is_queryable` | boolean | Tidak | Bisa diklik untuk popup atribut |
| `min_zoom`, `max_zoom` | smallint | Ya | Rentang zoom tampil |
| `default_opacity` | numeric(3,2) | Tidak | 0–1 |
| `sort_order` | integer | Tidak | Urutan di dalam node |
| `published_at` | timestamptz | Ya | |
| audit & waktu | | | `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at` |

```sql
CREATE TABLE layers (
  id                 uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  category_id        uuid NOT NULL REFERENCES categories(id),
  category_node_id   uuid,
  layer_type_id      smallint NOT NULL REFERENCES layer_types(id),
  code               varchar(80)  NOT NULL,
  name               varchar(200) NOT NULL,
  slug               varchar(220) NOT NULL,
  short_description  varchar(500),
  geometry_type      text,
  storage_srid       integer NOT NULL DEFAULT 4326,
  bbox               geometry(Polygon, 4326),
  feature_count      integer NOT NULL DEFAULT 0,
  current_version_id uuid,       -- FK ditambahkan setelah layer_versions dibuat
  default_style_id   uuid,       -- FK ditambahkan setelah layer_styles dibuat
  visibility         text NOT NULL DEFAULT 'public'
                     CHECK (visibility IN ('public','internal','private')),
  status             text NOT NULL DEFAULT 'draft'
                     CHECK (status IN ('draft','published','archived')),
  is_default_on      boolean NOT NULL DEFAULT false,
  is_downloadable    boolean NOT NULL DEFAULT false,
  is_queryable       boolean NOT NULL DEFAULT true,
  min_zoom           smallint CHECK (min_zoom BETWEEN 0 AND 24),
  max_zoom           smallint CHECK (max_zoom BETWEEN 0 AND 24),
  default_opacity    numeric(3,2) NOT NULL DEFAULT 1.00
                     CHECK (default_opacity BETWEEN 0 AND 1),
  sort_order         integer NOT NULL DEFAULT 0,
  published_at       timestamptz,
  created_by         uuid REFERENCES users(id),
  updated_by         uuid REFERENCES users(id),
  created_at         timestamptz NOT NULL DEFAULT now(),
  updated_at         timestamptz NOT NULL DEFAULT now(),
  deleted_at         timestamptz,

  CONSTRAINT fk_layers_category_node
    FOREIGN KEY (category_node_id, category_id)
    REFERENCES category_nodes (id, category_id),
  CONSTRAINT ck_layers_zoom CHECK (min_zoom IS NULL OR max_zoom IS NULL OR min_zoom <= max_zoom),
  CONSTRAINT ck_layers_published CHECK (status <> 'published' OR published_at IS NOT NULL)
);

CREATE UNIQUE INDEX uq_layers_code ON layers (code) WHERE deleted_at IS NULL;
CREATE UNIQUE INDEX uq_layers_slug ON layers (slug) WHERE deleted_at IS NULL;
CREATE INDEX ix_layers_category      ON layers (category_id, category_node_id, sort_order);
CREATE INDEX ix_layers_status_vis    ON layers (status, visibility) WHERE deleted_at IS NULL;
CREATE INDEX ix_layers_bbox          ON layers USING gist (bbox);
CREATE INDEX ix_layers_name_trgm     ON layers USING gin (name gin_trgm_ops);
```

> **Catatan FK komposit:** `MATCH SIMPLE` (default) membuat FK diabaikan bila `category_node_id` bernilai `NULL`, sehingga layer boleh berada langsung di akar kategori.

---

### 5.6 `layer_metadata`

Metadata deskriptif (relasi 1:1 dengan `layers`), mengacu pada elemen inti ISO 19115 / SNI ISO 19115 dan kebutuhan Satu Data Indonesia.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `layer_id` | uuid | PK sekaligus FK `layers` |
| `title` | varchar(300) | Judul resmi dataset |
| `abstract` | text | Ringkasan isi |
| `purpose` | text | Tujuan pembuatan data |
| `keywords` | text[] | Kata kunci pencarian |
| `topic_category` | varchar(60) | Kategori topik ISO (mis. `planningCadastre`, `environment`) |
| `producer_organization` | varchar(200) | Walidata / produsen data |
| `contact_name`, `contact_email`, `contact_phone` | varchar | Narahubung |
| `data_year` | smallint | Tahun data |
| `reference_date` | date | Tanggal acuan data |
| `date_type` | text | `creation`, `publication`, `revision` |
| `update_frequency` | text | `once`, `annually`, `quarterly`, `monthly`, `irregular` |
| `scale_denominator` | integer | Skala, mis. 50000 untuk 1:50.000 |
| `positional_accuracy` | varchar(100) | Ketelitian posisi |
| `source_srid` | integer | SRID data asli |
| `administrative_area` | varchar(200) | Cakupan wilayah, mis. *Kota Ternate* |
| `lineage` | text | Riwayat/proses pembuatan data |
| `license` | varchar(100) | Lisensi, mis. `CC-BY-4.0` |
| `use_constraints` | text | Batasan penggunaan |
| `extra` | jsonb | Elemen metadata tambahan |
| `updated_at` | timestamptz | |

```sql
CREATE TABLE layer_metadata (
  layer_id               uuid PRIMARY KEY REFERENCES layers(id) ON DELETE CASCADE,
  title                  varchar(300),
  abstract               text,
  purpose                text,
  keywords               text[] NOT NULL DEFAULT '{}',
  topic_category         varchar(60),
  producer_organization  varchar(200),
  contact_name           varchar(150),
  contact_email          varchar(150),
  contact_phone          varchar(40),
  data_year              smallint CHECK (data_year BETWEEN 1900 AND 2100),
  reference_date         date,
  date_type              text CHECK (date_type IN ('creation','publication','revision')),
  update_frequency       text CHECK (update_frequency IN
                           ('once','annually','semiannually','quarterly','monthly','irregular')),
  scale_denominator      integer CHECK (scale_denominator > 0),
  positional_accuracy    varchar(100),
  source_srid            integer,
  administrative_area    varchar(200),
  lineage                text,
  license                varchar(100),
  use_constraints        text,
  extra                  jsonb NOT NULL DEFAULT '{}',
  updated_at             timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX ix_layer_metadata_keywords ON layer_metadata USING gin (keywords);
```

---

### 5.7 `layer_sources`

Asal data sebuah layer. Untuk layer vektor hasil unggahan, sumbernya adalah file; untuk layer layanan (WMS/XYZ/dll.), sumber inilah yang langsung dirender oleh client.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | PK |
| `layer_id` | uuid | FK `layers` |
| `source_type` | text | `file_upload`, `wms`, `wmts`, `wfs`, `xyz`, `arcgis_rest`, `geojson_url`, `vector_tile`, `cog_url`, `database` |
| `name` | varchar(150) | Label sumber |
| `url` | text | Endpoint layanan (untuk tipe layanan) |
| `service_layer_name` | varchar(200) | Nama layer di layanan, mis. `LAYERS=` pada WMS |
| `format` | varchar(50) | `image/png`, `application/json`, dll. |
| `crs` | varchar(30) | CRS layanan, mis. `EPSG:3857` |
| `auth_type` | text | `none`, `api_key`, `basic`, `token` |
| `credential_ref` | varchar(200) | **Referensi** ke secret store/env, bukan secret itu sendiri |
| `options` | jsonb | Parameter tambahan (tileSize, attribution, dll.) |
| `is_primary` | boolean | Sumber utama untuk render |
| `health_status` | text | `unknown`, `ok`, `error` |
| `last_checked_at` | timestamptz | Waktu pengecekan terakhir |
| `created_at`, `updated_at` | timestamptz | |

```sql
CREATE TABLE layer_sources (
  id                  uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  layer_id            uuid NOT NULL REFERENCES layers(id) ON DELETE CASCADE,
  source_type         text NOT NULL CHECK (source_type IN
                        ('file_upload','wms','wmts','wfs','xyz','arcgis_rest',
                         'geojson_url','vector_tile','cog_url','database')),
  name                varchar(150),
  url                 text,
  service_layer_name  varchar(200),
  format              varchar(50),
  crs                 varchar(30),
  auth_type           text NOT NULL DEFAULT 'none'
                      CHECK (auth_type IN ('none','api_key','basic','token')),
  credential_ref      varchar(200),
  options             jsonb NOT NULL DEFAULT '{}',
  is_primary          boolean NOT NULL DEFAULT false,
  health_status       text NOT NULL DEFAULT 'unknown'
                      CHECK (health_status IN ('unknown','ok','error')),
  last_checked_at     timestamptz,
  created_at          timestamptz NOT NULL DEFAULT now(),
  updated_at          timestamptz NOT NULL DEFAULT now(),

  CONSTRAINT ck_layer_sources_url CHECK (
    source_type IN ('file_upload','database') OR url IS NOT NULL)
);

CREATE UNIQUE INDEX uq_layer_sources_primary ON layer_sources (layer_id) WHERE is_primary;
```

---

### 5.8 `layer_imports`

Riwayat setiap proses impor file (SHP-zip, GeoJSON, KML/KMZ, GeoPackage, CSV berkoordinat) ke `spatial_features`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | PK |
| `layer_id` | uuid | FK `layers` |
| `layer_source_id` | uuid | FK `layer_sources` (opsional) |
| `original_filename` | varchar(255) | Nama file asli |
| `storage_path` | text | Lokasi file di object storage |
| `file_format` | text | `shp_zip`, `geojson`, `kml`, `kmz`, `gpkg`, `csv` |
| `file_size_bytes` | bigint | |
| `checksum_sha256` | char(64) | Deteksi file duplikat |
| `source_srid` | integer | SRID terdeteksi/diisi user |
| `target_srid` | integer | Default 4326 |
| `encoding` | varchar(30) | Mis. `UTF-8`, `windows-1252` (penting untuk DBF) |
| `import_mode` | text | `new_version` (buat versi baru) atau `append` |
| `status` | text | `pending`, `uploaded`, `mapping`, `processing`, `completed`, `failed`, `cancelled` |
| `detected_fields` | jsonb | Daftar kolom & tipe hasil pembacaan file |
| `total_features` | integer | |
| `imported_features` | integer | |
| `failed_features` | integer | |
| `error_message` | text | |
| `log` | jsonb | Detail per baris yang gagal, peringatan geometri, dll. |
| `started_at`, `finished_at` | timestamptz | |
| `imported_by` | uuid | FK `users` |
| `created_at` | timestamptz | |

```sql
CREATE TABLE layer_imports (
  id                 uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  layer_id           uuid NOT NULL REFERENCES layers(id) ON DELETE CASCADE,
  layer_source_id    uuid REFERENCES layer_sources(id) ON DELETE SET NULL,
  original_filename  varchar(255) NOT NULL,
  storage_path       text NOT NULL,
  file_format        text NOT NULL CHECK (file_format IN
                       ('shp_zip','geojson','kml','kmz','gpkg','csv')),
  file_size_bytes    bigint CHECK (file_size_bytes >= 0),
  checksum_sha256    char(64),
  source_srid        integer,
  target_srid        integer NOT NULL DEFAULT 4326,
  encoding           varchar(30) DEFAULT 'UTF-8',
  import_mode        text NOT NULL DEFAULT 'new_version'
                     CHECK (import_mode IN ('new_version','append')),
  status             text NOT NULL DEFAULT 'pending' CHECK (status IN
                       ('pending','uploaded','mapping','processing',
                        'completed','failed','cancelled')),
  detected_fields    jsonb NOT NULL DEFAULT '[]',
  total_features     integer,
  imported_features  integer NOT NULL DEFAULT 0,
  failed_features    integer NOT NULL DEFAULT 0,
  error_message      text,
  log                jsonb NOT NULL DEFAULT '{}',
  started_at         timestamptz,
  finished_at        timestamptz,
  imported_by        uuid REFERENCES users(id),
  created_at         timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX ix_layer_imports_layer  ON layer_imports (layer_id, created_at DESC);
CREATE INDEX ix_layer_imports_status ON layer_imports (status)
  WHERE status IN ('pending','uploaded','mapping','processing');
```

---

### 5.9 `layer_versions`

Setiap impor dengan mode `new_version` menghasilkan satu versi. Hanya satu versi yang **aktif** per layer, dan itulah yang dirender di peta.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | PK |
| `layer_id` | uuid | FK `layers` |
| `version_number` | integer | Naik berurutan per layer (1, 2, 3, …) |
| `label` | varchar(100) | Mis. *RTRW 2024 revisi Perda No. 2* |
| `change_note` | text | Catatan perubahan |
| `layer_import_id` | uuid | FK `layer_imports` yang menghasilkan versi ini |
| `status` | text | `draft`, `active`, `archived` |
| `feature_count` | integer | |
| `bbox` | geometry(Polygon,4326) | |
| `attribute_schema` | jsonb | Snapshot definisi atribut saat versi dibuat |
| `created_by` | uuid | FK `users` |
| `created_at`, `activated_at` | timestamptz | |

```sql
CREATE TABLE layer_versions (
  id                uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  layer_id          uuid NOT NULL REFERENCES layers(id) ON DELETE CASCADE,
  version_number    integer NOT NULL CHECK (version_number > 0),
  label             varchar(100),
  change_note       text,
  layer_import_id   uuid UNIQUE REFERENCES layer_imports(id) ON DELETE SET NULL,
  status            text NOT NULL DEFAULT 'draft'
                    CHECK (status IN ('draft','active','archived')),
  feature_count     integer NOT NULL DEFAULT 0,
  bbox              geometry(Polygon, 4326),
  attribute_schema  jsonb NOT NULL DEFAULT '[]',
  created_by        uuid REFERENCES users(id),
  created_at        timestamptz NOT NULL DEFAULT now(),
  activated_at      timestamptz,

  CONSTRAINT uq_layer_versions_number UNIQUE (layer_id, version_number),
  CONSTRAINT uq_layer_versions_id_layer UNIQUE (id, layer_id)
);

-- Hanya satu versi aktif per layer
CREATE UNIQUE INDEX uq_layer_versions_active
  ON layer_versions (layer_id) WHERE status = 'active';

-- FK dari layers ke versi aktif (versi harus milik layer yang sama)
ALTER TABLE layers
  ADD CONSTRAINT fk_layers_current_version
  FOREIGN KEY (current_version_id, id)
  REFERENCES layer_versions (id, layer_id)
  DEFERRABLE INITIALLY DEFERRED;
```

---

### 5.10 `layer_attribute_definitions`

Skema atribut standar milik layer. Menentukan label yang tampil di popup, tipe data, dan apakah atribut dapat dicari/difilter. Nilai aktual disimpan di `spatial_features.properties` dengan kunci `field_key`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | PK |
| `layer_id` | uuid | FK `layers` |
| `field_key` | varchar(63) | Kunci di `properties`, `snake_case`, mis. `luas_ha` |
| `label` | varchar(150) | Label tampil, mis. *Luas (ha)* |
| `data_type` | text | `string`, `integer`, `decimal`, `boolean`, `date`, `datetime`, `enum`, `url` |
| `unit` | varchar(30) | Mis. `ha`, `m`, `jiwa` |
| `enum_options` | jsonb | Untuk `enum`: `[{"value":"KL","label":"Kawasan Lindung"}]` |
| `default_value` | text | |
| `is_required` | boolean | |
| `is_visible_in_popup` | boolean | |
| `is_searchable` | boolean | Ikut pencarian teks |
| `is_filterable` | boolean | Tampil sebagai filter |
| `is_label_field` | boolean | Dipakai sebagai label fitur di peta (maks. 1 per layer) |
| `display_order` | integer | |
| `description` | text | |
| `created_at`, `updated_at` | timestamptz | |

```sql
CREATE TABLE layer_attribute_definitions (
  id                   uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  layer_id             uuid NOT NULL REFERENCES layers(id) ON DELETE CASCADE,
  field_key            varchar(63)  NOT NULL CHECK (field_key ~ '^[a-z][a-z0-9_]*$'),
  label                varchar(150) NOT NULL,
  data_type            text NOT NULL CHECK (data_type IN
                         ('string','integer','decimal','boolean','date','datetime','enum','url')),
  unit                 varchar(30),
  enum_options         jsonb,
  default_value        text,
  is_required          boolean NOT NULL DEFAULT false,
  is_visible_in_popup  boolean NOT NULL DEFAULT true,
  is_searchable        boolean NOT NULL DEFAULT false,
  is_filterable        boolean NOT NULL DEFAULT false,
  is_label_field       boolean NOT NULL DEFAULT false,
  display_order        integer NOT NULL DEFAULT 0,
  description          text,
  created_at           timestamptz NOT NULL DEFAULT now(),
  updated_at           timestamptz NOT NULL DEFAULT now(),

  CONSTRAINT uq_layer_attr_key UNIQUE (layer_id, field_key),
  CONSTRAINT ck_layer_attr_enum CHECK (data_type <> 'enum' OR enum_options IS NOT NULL)
);

CREATE UNIQUE INDEX uq_layer_attr_label_field
  ON layer_attribute_definitions (layer_id) WHERE is_label_field;
```

---

### 5.11 `layer_attribute_mappings`

Pemetaan kolom file sumber ke atribut standar, **per impor**. Ini yang memungkinkan file dengan nama kolom berbeda (mis. `LUAS`, `Luas_Ha`, `AREA_HA`) masuk ke atribut yang sama (`luas_ha`).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | PK |
| `layer_import_id` | uuid | FK `layer_imports` |
| `source_field_name` | varchar(255) | Nama kolom di file sumber |
| `source_field_type` | varchar(50) | Tipe terdeteksi di file |
| `attribute_definition_id` | uuid | FK `layer_attribute_definitions`; `NULL` jika diabaikan |
| `is_ignored` | boolean | Kolom tidak diimpor |
| `transform` | jsonb | Aturan transformasi, mis. `{"trim":true,"case":"upper","multiply":0.0001}` atau lookup nilai |
| `created_at` | timestamptz | |

```sql
CREATE TABLE layer_attribute_mappings (
  id                       uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  layer_import_id          uuid NOT NULL REFERENCES layer_imports(id) ON DELETE CASCADE,
  source_field_name        varchar(255) NOT NULL,
  source_field_type        varchar(50),
  attribute_definition_id  uuid REFERENCES layer_attribute_definitions(id) ON DELETE SET NULL,
  is_ignored               boolean NOT NULL DEFAULT false,
  transform                jsonb NOT NULL DEFAULT '{}',
  created_at               timestamptz NOT NULL DEFAULT now(),

  CONSTRAINT uq_layer_attr_map_source UNIQUE (layer_import_id, source_field_name),
  CONSTRAINT ck_layer_attr_map_target CHECK (is_ignored OR attribute_definition_id IS NOT NULL)
);

-- Satu atribut standar hanya boleh diisi oleh satu kolom sumber per impor
CREATE UNIQUE INDEX uq_layer_attr_map_target
  ON layer_attribute_mappings (layer_import_id, attribute_definition_id)
  WHERE attribute_definition_id IS NOT NULL;
```

> Aplikasi wajib memastikan `attribute_definition_id` milik layer yang sama dengan `layer_import_id` (validasi di service layer atau trigger).

---

### 5.12 `layer_styles`

Simbolisasi layer. Disimpan sebagai JSON yang dapat diterjemahkan langsung ke style MapLibre GL / Leaflet, ditambah legenda siap tampil.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | PK |
| `layer_id` | uuid | FK `layers` |
| `name` | varchar(100) | Mis. *Default*, *Berdasarkan Fungsi Kawasan* |
| `style_type` | text | `simple`, `categorized`, `graduated`, `heatmap`, `raster`, `external` |
| `renderer` | text | `maplibre`, `leaflet`, `generic` |
| `classification_field` | varchar(63) | `field_key` untuk categorized/graduated |
| `definition` | jsonb | Definisi style (paint/layout, aturan kelas) |
| `legend` | jsonb | Item legenda: `[{"label":"Hutan Lindung","color":"#2E7D32","type":"fill"}]` |
| `sld` | text | Opsional, untuk interoperabilitas GeoServer/QGIS |
| `is_default` | boolean | Maks. 1 per layer |
| `created_by` | uuid | FK `users` |
| `created_at`, `updated_at` | timestamptz | |

```sql
CREATE TABLE layer_styles (
  id                    uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  layer_id              uuid NOT NULL REFERENCES layers(id) ON DELETE CASCADE,
  name                  varchar(100) NOT NULL,
  style_type            text NOT NULL CHECK (style_type IN
                          ('simple','categorized','graduated','heatmap','raster','external')),
  renderer              text NOT NULL DEFAULT 'maplibre'
                        CHECK (renderer IN ('maplibre','leaflet','generic')),
  classification_field  varchar(63),
  definition            jsonb NOT NULL,
  legend                jsonb NOT NULL DEFAULT '[]',
  sld                   text,
  is_default            boolean NOT NULL DEFAULT false,
  created_by            uuid REFERENCES users(id),
  created_at            timestamptz NOT NULL DEFAULT now(),
  updated_at            timestamptz NOT NULL DEFAULT now(),

  CONSTRAINT uq_layer_styles_name UNIQUE (layer_id, name),
  CONSTRAINT uq_layer_styles_id_layer UNIQUE (id, layer_id),
  CONSTRAINT ck_layer_styles_class CHECK (
    style_type NOT IN ('categorized','graduated') OR classification_field IS NOT NULL)
);

CREATE UNIQUE INDEX uq_layer_styles_default ON layer_styles (layer_id) WHERE is_default;

ALTER TABLE layers
  ADD CONSTRAINT fk_layers_default_style
  FOREIGN KEY (default_style_id, id)
  REFERENCES layer_styles (id, layer_id)
  DEFERRABLE INITIALLY DEFERRED;
```

Contoh `definition` (categorized, MapLibre):

```json
{
  "type": "fill",
  "paint": {
    "fill-color": ["match", ["get", "fungsi_kawasan"],
      "KL", "#2E7D32",
      "KB", "#F9A825",
      "#9E9E9E"],
    "fill-opacity": 0.7,
    "fill-outline-color": "#424242"
  }
}
```

---

### 5.13 `spatial_features`

Tabel terbesar. Menyimpan geometri dan atribut setiap fitur dari layer vektor.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | PK (identity) |
| `layer_id` | uuid | FK `layers` |
| `layer_version_id` | uuid | FK `layer_versions` (harus milik layer yang sama) |
| `source_fid` | varchar(100) | ID fitur di file asal |
| `geom` | geometry(Geometry,4326) | Geometri, selalu valid dan multi-part bila sesuai tipe layer |
| `geom_type` | text | Generated: `ST_GeometryType(geom)` |
| `properties` | jsonb | Atribut dengan kunci `field_key` |
| `label` | varchar(255) | Nilai label (cache dari atribut label) |
| `area_m2` | double precision | Generated untuk poligon (geography) |
| `length_m` | double precision | Generated untuk garis (geography) |
| `search_text` | tsvector | Untuk pencarian teks atribut |
| `created_at`, `updated_at` | timestamptz | |

```sql
CREATE TABLE spatial_features (
  id                bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  layer_id          uuid NOT NULL REFERENCES layers(id) ON DELETE CASCADE,
  layer_version_id  uuid NOT NULL,
  source_fid        varchar(100),
  geom              geometry(Geometry, 4326) NOT NULL,
  geom_type         text GENERATED ALWAYS AS (ST_GeometryType(geom)) STORED,
  properties        jsonb NOT NULL DEFAULT '{}',
  label             varchar(255),
  area_m2           double precision GENERATED ALWAYS AS (
                      CASE WHEN ST_Dimension(geom) = 2
                           THEN ST_Area(geom::geography) END) STORED,
  length_m          double precision GENERATED ALWAYS AS (
                      CASE WHEN ST_Dimension(geom) = 1
                           THEN ST_Length(geom::geography) END) STORED,
  search_text       tsvector,
  created_at        timestamptz NOT NULL DEFAULT now(),
  updated_at        timestamptz NOT NULL DEFAULT now(),

  CONSTRAINT fk_spatial_features_version
    FOREIGN KEY (layer_version_id, layer_id)
    REFERENCES layer_versions (id, layer_id) ON DELETE CASCADE,
  CONSTRAINT ck_spatial_features_valid CHECK (ST_IsValid(geom) AND NOT ST_IsEmpty(geom))
);

CREATE INDEX ix_spatial_features_geom       ON spatial_features USING gist (geom);
CREATE INDEX ix_spatial_features_version    ON spatial_features (layer_version_id);
CREATE INDEX ix_spatial_features_layer      ON spatial_features (layer_id, layer_version_id);
CREATE INDEX ix_spatial_features_properties ON spatial_features USING gin (properties jsonb_path_ops);
CREATE INDEX ix_spatial_features_search     ON spatial_features USING gin (search_text);
```

**Strategi skala:**
- Untuk jutaan fitur, gunakan **partisi** `PARTITION BY HASH (layer_id)` (mis. 16 partisi) atau `LIST` untuk layer yang sangat besar. Indeks GIST per partisi tetap efisien.
- Render peta memakai **vector tile** (`ST_AsMVT`) melalui server tile seperti Martin atau pg_tileserv, bukan GeoJSON penuh.
- Untuk zoom rendah, siapkan geometri tersederhanakan (`ST_SimplifyPreserveTopology`) sebagai *materialized view* per layer bila perlu.

---

### 5.14 `documents`

Dokumen pendukung layer, misalnya SK penetapan, Perda RTRW, laporan survei, atau PDF peta cetak.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid | PK |
| `layer_id` | uuid | FK `layers` |
| `title` | varchar(255) | |
| `document_type` | text | `regulation`, `decree`, `report`, `metadata_sheet`, `map_pdf`, `other` |
| `document_number` | varchar(100) | Nomor SK/Perda |
| `document_date` | date | |
| `description` | text | |
| `storage_path` | text | Lokasi di object storage |
| `original_filename` | varchar(255) | |
| `mime_type` | varchar(100) | |
| `file_size_bytes` | bigint | |
| `checksum_sha256` | char(64) | |
| `is_public` | boolean | Dapat diunduh pengunjung |
| `sort_order` | integer | |
| `uploaded_by` | uuid | FK `users` |
| `created_at`, `updated_at`, `deleted_at` | timestamptz | |

```sql
CREATE TABLE documents (
  id                 uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  layer_id           uuid NOT NULL REFERENCES layers(id) ON DELETE CASCADE,
  title              varchar(255) NOT NULL,
  document_type      text NOT NULL DEFAULT 'other' CHECK (document_type IN
                       ('regulation','decree','report','metadata_sheet','map_pdf','other')),
  document_number    varchar(100),
  document_date      date,
  description        text,
  storage_path       text NOT NULL,
  original_filename  varchar(255) NOT NULL,
  mime_type          varchar(100) NOT NULL,
  file_size_bytes    bigint CHECK (file_size_bytes >= 0),
  checksum_sha256    char(64),
  is_public          boolean NOT NULL DEFAULT true,
  sort_order         integer NOT NULL DEFAULT 0,
  uploaded_by        uuid REFERENCES users(id),
  created_at         timestamptz NOT NULL DEFAULT now(),
  updated_at         timestamptz NOT NULL DEFAULT now(),
  deleted_at         timestamptz
);

CREATE INDEX ix_documents_layer ON documents (layer_id, sort_order) WHERE deleted_at IS NULL;
```

> Jika kelak dokumen juga perlu ditautkan ke kategori atau ke fitur tertentu, tambahkan kolom `category_id` / `spatial_feature_id` yang nullable dengan `CHECK` bahwa tepat satu induk terisi, daripada memakai relasi polimorfik tanpa FK.

---

### 5.15 `audit_logs` (pendukung)

```sql
CREATE TABLE audit_logs (
  id           bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id      uuid REFERENCES users(id),
  action       text NOT NULL,          -- create, update, delete, publish, import, activate_version
  entity_type  text NOT NULL,          -- 'layers', 'categories', ...
  entity_id    text NOT NULL,
  changes      jsonb,                  -- {"before": {...}, "after": {...}}
  ip_address   inet,
  user_agent   text,
  created_at   timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX ix_audit_logs_entity ON audit_logs (entity_type, entity_id, created_at DESC);
```

---

### 5.16 Trigger `updated_at`

```sql
DO $$
DECLARE t text;
BEGIN
  FOREACH t IN ARRAY ARRAY[
    'users','categories','category_nodes','layers','layer_metadata','layer_sources',
    'layer_attribute_definitions','layer_styles','spatial_features','documents']
  LOOP
    EXECUTE format(
      'CREATE TRIGGER trg_%1$s_updated_at BEFORE UPDATE ON %1$s
         FOR EACH ROW EXECUTE FUNCTION set_updated_at()', t);
  END LOOP;
END $$;
```

---

## 6. `saved_maps` (Opsional)

Hanya dibangun jika ada fitur **"Simpan Peta"**. Desainnya sengaja berupa **snapshot JSON**, bukan tabel relasi `map_layers`, agar tetap konsisten dengan prinsip "peta adalah tampilan".

```sql
CREATE TABLE saved_maps (
  id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id      uuid NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  name         varchar(150) NOT NULL,
  description  text,
  view_state   jsonb NOT NULL,   -- center, zoom, bearing, pitch, basemap
  layer_state  jsonb NOT NULL,   -- daftar layer aktif (lihat contoh)
  share_token  varchar(32) UNIQUE,
  is_public    boolean NOT NULL DEFAULT false,
  created_at   timestamptz NOT NULL DEFAULT now(),
  updated_at   timestamptz NOT NULL DEFAULT now()
);
```

Saat dibuka, aplikasi wajib memvalidasi bahwa setiap `layer_id` masih ada, `published`, dan dapat diakses user; layer yang tidak valid dilewati dengan pemberitahuan.

---

## 7. Active Layer State (di Client, bukan Database)

Struktur state yang disarankan di aplikasi frontend:

```json
{
  "view": {
    "center": [127.3836, 0.7893],
    "zoom": 11,
    "bearing": 0,
    "pitch": 0,
    "basemap": "osm"
  },
  "activeLayers": [
    { "layerId": "6f1c…", "styleId": null, "opacity": 0.8, "visible": true, "order": 1 },
    { "layerId": "a92e…", "styleId": "c31d…", "opacity": 1.0, "visible": false, "order": 2 }
  ]
}
```

- **Sumber awal:** layer dengan `is_default_on = true`.
- **Persistensi ringan:** `localStorage` untuk sesi berikutnya pada perangkat yang sama.
- **Berbagi tampilan:** state diringkas ke URL, mis. `?l=6f1c:0.8,a92e:1:c31d&c=127.38,0.79&z=11`.
- `styleId: null` berarti memakai `default_style_id` layer.

---

## 8. Aturan Bisnis

| No | Aturan | Ditegakkan oleh |
|---|---|---|
| R1 | Satu layer berada di tepat satu kategori; node (jika diisi) harus milik kategori yang sama | FK `layers.category_id` + FK komposit `(category_node_id, category_id)` |
| R2 | Node induk harus berada di kategori yang sama dengan node anak | FK komposit `category_nodes (parent_id, category_id)` |
| R3 | Node tidak boleh menjadi induk dirinya sendiri atau keturunannya | `CHECK` + validasi trigger berbasis `path` saat pemindahan |
| R4 | Hanya satu versi aktif per layer | Partial unique index `uq_layer_versions_active` |
| R5 | `layers.current_version_id` harus versi milik layer tersebut | FK komposit ke `layer_versions (id, layer_id)` |
| R6 | Hanya satu style default per layer, dan milik layer itu | Partial unique index + FK komposit |
| R7 | Fitur spasial harus milik versi dari layer yang sama | FK komposit `(layer_version_id, layer_id)` |
| R8 | Geometri harus valid, tidak kosong, dan ber-SRID 4326 | Tipe kolom + `CHECK ST_IsValid` |
| R9 | Layer `published` wajib punya `published_at`; layer vektor wajib punya versi aktif dan style default | `CHECK` + validasi service sebelum publish |
| R10 | Pengunjung publik hanya melihat layer `published` + `public` di kategori/node aktif | Query/view publik (§9) |
| R11 | Kredensial layanan eksternal tidak disimpan di database | `credential_ref` hanya berisi referensi |
| R12 | Menampilkan/menyembunyikan layer tidak menulis ke database | Desain (§1.4, §7) |

---

## 9. Alur Utama

### 9.1 Impor data layer vektor

```
1. Admin membuat layer (status: draft) + metadata
2. Unggah file            → layer_imports (status: uploaded), file ke object storage
3. Sistem membaca file    → detected_fields, source_srid, total_features (status: mapping)
4. Admin memetakan kolom  → layer_attribute_mappings
                            (membuat layer_attribute_definitions baru bila perlu)
5. Proses impor           → status: processing
     • transformasi ke EPSG:4326 (ST_Transform)
     • perbaikan geometri (ST_MakeValid), konversi ke multi-part (ST_Multi)
     • transformasi nilai atribut sesuai mapping
     • insert ke spatial_features dengan layer_version_id baru (versi status: draft)
6. Selesai                → status: completed, catat imported/failed_features & log
7. Admin meninjau, lalu mengaktifkan versi (satu transaksi):
     • versi lama → archived
     • versi baru → active
     • layers.current_version_id, bbox, feature_count diperbarui
8. Publish layer          → status: published, published_at = now()
```

### 9.2 Rollback versi

Aktifkan kembali versi `archived` sebelumnya dengan langkah 7 yang sama. Data fitur versi lama tetap tersimpan sampai versi tersebut dihapus melalui kebijakan retensi (mis. simpan 3 versi terakhir).

---

## 10. Query Penting

### 10.1 View layer publik (dipakai API panel kategori)

```sql
CREATE VIEW v_public_layers AS
SELECT l.id, l.code, l.name, l.slug, l.short_description,
       l.category_id, c.name AS category_name,
       l.category_node_id, n.name AS node_name, n.path AS node_path,
       lt.code AS layer_type, l.geometry_type,
       l.bbox, l.feature_count, l.default_style_id,
       l.is_default_on, l.is_queryable, l.is_downloadable,
       l.min_zoom, l.max_zoom, l.default_opacity, l.sort_order
FROM layers l
JOIN categories c      ON c.id = l.category_id AND c.is_active AND c.deleted_at IS NULL
LEFT JOIN category_nodes n ON n.id = l.category_node_id
JOIN layer_types lt    ON lt.id = l.layer_type_id
WHERE l.status = 'published'
  AND l.visibility = 'public'
  AND l.deleted_at IS NULL
  AND (n.id IS NULL OR (n.is_active AND n.deleted_at IS NULL));
```

### 10.2 Pohon kategori lengkap

```sql
SELECT c.id AS category_id, c.name AS category,
       n.id AS node_id, n.parent_id, n.name AS node, n.depth, n.path
FROM categories c
LEFT JOIN category_nodes n ON n.category_id = c.id
     AND n.is_active AND n.deleted_at IS NULL
WHERE c.is_active AND c.deleted_at IS NULL
ORDER BY c.sort_order, n.path;
```

### 10.3 Fungsi vector tile per layer

```sql
CREATE OR REPLACE FUNCTION layer_mvt(p_layer_id uuid, z int, x int, y int)
RETURNS bytea AS $$
  WITH bounds AS (
    SELECT ST_TileEnvelope(z, x, y) AS env3857,
           ST_Transform(ST_TileEnvelope(z, x, y), 4326) AS env4326
  ),
  mvt AS (
    SELECT f.id, f.label, f.properties,
           ST_AsMVTGeom(ST_Transform(f.geom, 3857), b.env3857, 4096, 64, true) AS geom
    FROM spatial_features f
    JOIN layers l ON l.id = f.layer_id
                 AND f.layer_version_id = l.current_version_id
    CROSS JOIN bounds b
    WHERE f.layer_id = p_layer_id
      AND f.geom && b.env4326
  )
  SELECT ST_AsMVT(mvt.*, 'features', 4096, 'geom', 'id') FROM mvt;
$$ LANGUAGE sql STABLE PARALLEL SAFE;
```

### 10.4 Identifikasi fitur di titik klik (popup)

```sql
SELECT f.id, f.label, f.properties
FROM spatial_features f
JOIN layers l ON l.id = f.layer_id AND f.layer_version_id = l.current_version_id
WHERE f.layer_id = ANY($1::uuid[])                         -- layer aktif dari client
  AND ST_DWithin(f.geom::geography,
                 ST_SetSRID(ST_MakePoint($2, $3), 4326)::geography,
                 $4)                                       -- toleransi meter
LIMIT 20;
```

### 10.5 Pencarian layer

```sql
SELECT l.id, l.name, similarity(l.name, $1) AS score
FROM v_public_layers l
LEFT JOIN layer_metadata m ON m.layer_id = l.id
WHERE l.name % $1 OR $1 = ANY (m.keywords)
ORDER BY score DESC
LIMIT 20;
```

---

## 11. Keamanan dan Hak Akses

| Peran | Kategori & Node | Layer & Metadata | Impor / Versi | Style | Dokumen | User |
|---|---|---|---|---|---|---|
| `super_admin` | CRUD | CRUD | Semua | CRUD | CRUD | CRUD |
| `admin` | CRUD | CRUD + publish | Semua | CRUD | CRUD | Baca |
| `operator` | Baca | Buat/ubah draft | Impor, buat versi | CRUD | Unggah | — |
| `viewer` | Baca | Baca (termasuk `internal`) | Baca | Baca | Baca | — |
| Publik (tanpa login) | Baca aktif | `published` + `public` saja | — | Baca default | `is_public` saja | — |

Rekomendasi tambahan:
- Akses API publik melalui role database terpisah yang hanya memiliki `SELECT` pada `v_public_layers`, fungsi tile, dan tabel referensi.
- Pertimbangkan Row Level Security (RLS) pada `layers` dan `spatial_features` bila layer `internal`/`private` dilayani dari database yang sama.

---

## 12. Urutan Migrasi

1. Ekstensi dan fungsi `set_updated_at`
2. `users`
3. `categories`
4. `category_nodes`
5. `layer_types` + seed
6. `layers` (tanpa FK `current_version_id` dan `default_style_id`)
7. `layer_metadata`
8. `layer_sources`
9. `layer_imports`
10. `layer_versions` + FK `layers.current_version_id`
11. `layer_attribute_definitions`
12. `layer_attribute_mappings`
13. `layer_styles` + FK `layers.default_style_id`
14. `spatial_features` (+ partisi bila diperlukan)
15. `documents`
16. `audit_logs`
17. Trigger, view `v_public_layers`, fungsi `layer_mvt`
18. *(Opsional)* `saved_maps`

---

## 13. Ringkasan Keputusan Akhir

| Entitas | Keputusan |
|---|---|
| `categories` | **Ya** |
| `category_nodes` | **Ya** |
| `layers` | **Ya** |
| `layer_types` | **Ya** |
| `layer_metadata` | **Ya** |
| `layer_sources` | **Ya** |
| `layer_imports` | **Ya** |
| `layer_attribute_definitions` | **Ya** |
| `layer_attribute_mappings` | **Ya** |
| `layer_styles` | **Ya** |
| `layer_versions` | **Ya** |
| `spatial_features` | **Ya** |
| `documents` | **Ya, bila dokumen pendukung diperlukan** |
| `maps` | **Tidak untuk core system** |
| `map_layers` | **Tidak untuk core system** |
| `map_shares` | **Tidak untuk core system** |
| `category_layers` | **Tidak** |
| `saved_maps` | **Opsional, jika nanti ada "Simpan Peta"** |
| `users`, `audit_logs` | **Pendukung** (sesuaikan dengan modul autentikasi yang ada) |
