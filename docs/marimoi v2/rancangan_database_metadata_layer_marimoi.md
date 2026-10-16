# Rancangan Database dan Alur Metadata Layer MARIMOI

## 1. Tujuan

MARIMOI membutuhkan mekanisme yang fleksibel untuk menyimpan referensi atribut/metadata dari setiap layer peta yang ditambahkan user.

Alur utama:

```text
User membuat layer
        ↓
Memilih jenis peta
        ↓
Sistem mengambil definisi metadata berdasarkan jenis peta
        ↓
User mengisi metadata
        ↓
User mengunggah data spasial
        ↓
Sistem membaca geometry, CRS, dan atribut
        ↓
Preview & mapping atribut
        ↓
Validasi
        ↓
Publish layer
```

Desain harus memungkinkan penambahan jenis peta dan metadata baru tanpa mengubah struktur database utama.

---

# 2. Prinsip Arsitektur

Gunakan pendekatan **metadata-driven architecture**.

```text
Jenis Peta
    ↓
Definisi Metadata
    ↓
Konfigurasi Metadata untuk Jenis Peta
    ↓
Layer
    ↓
Nilai Metadata Layer
```

Untuk data spasial:

```text
Layer
    ↓
Spatial Data
    ↓
Feature
    ↓
Feature Attribute
```

Sehingga terdapat dua level informasi:

1. **Metadata Layer** — menjelaskan keseluruhan layer.
2. **Attribute Feature** — menjelaskan masing-masing objek/geometri dalam layer.

---

# 3. Contoh Kasus

## 3.1 Peta Dasar / Administrasi Wilayah

User memilih:

```text
Jenis Peta = Peta Dasar
```

Metadata otomatis:

```text
Sumber *
OPD Penanggung Jawab *
Tahun *
```

Contoh:

```text
Nama Layer       : Administrasi Kecamatan Maluku Utara 2026
Jenis Peta       : Peta Dasar
Sumber            : BIG
OPD               : Bappeda Provinsi Maluku Utara
Tahun             : 2026
```

---

## 3.2 Layer RTLH

User memilih:

```text
Jenis Peta = Program Prioritas
```

Metadata utama:

```text
Sumber
OPD Penanggung Jawab
Tahun
```

Metadata tambahan:

```text
Pagu
Realisasi Anggaran
Realisasi Fisik
Status
Penerima
```

Contoh:

```text
Nama Layer          : RTLH Kota Ternate 2026
Jenis Peta          : Program Prioritas
Sumber              : Dinas Perkim
OPD                 : Dinas Perkim
Tahun               : 2026
Pagu                : Rp2.500.000.000
Realisasi Anggaran  : Rp1.800.000.000
Realisasi Fisik     : 72%
Status              : Berjalan
Penerima            : 250 KK
```

---

# 4. Struktur Database

## 4.1 `map_layer_types`

Menyimpan master jenis/kategori peta.

```text
map_layer_types
------------------------------
id
code
name
description
is_active
created_at
updated_at
```

Contoh:

| code | name |
|---|---|
| BASEMAP | Peta Dasar |
| THEMATIC | Peta Tematik |
| PRIORITY_PROGRAM | Program Prioritas |
| STRATEGIC_PROJECT | Proyek Strategis |
| MUSRENBANG | Musrenbang |
| POKIR | Pokir DPRD |

Gunakan `id` sebagai foreign key dan `code` sebagai identifier stabil.

---

# 5. `metadata_definitions`

Master definisi metadata.

```text
metadata_definitions
--------------------------------
id
code
name
description
data_type
unit
is_system
created_by
created_at
updated_at
```

Contoh:

| code | name | data_type | unit | is_system |
|---|---|---|---|---|
| source | Sumber | text | - | true |
| responsible_opd | OPD Penanggung Jawab | opd | - | true |
| year | Tahun | integer | tahun | true |
| budget | Pagu | decimal | Rp | false |
| budget_realization | Realisasi Anggaran | decimal | Rp | false |
| physical_realization | Realisasi Fisik | decimal | % | false |
| status | Status | select | - | false |
| beneficiary | Penerima | integer | KK | false |

`is_system = true` digunakan untuk metadata standar MARIMOI yang tersedia pada semua jenis peta.

Metadata utama:

```text
source
responsible_opd
year
```

---

# 6. `layer_type_metadata`

Tabel penghubung antara jenis peta dan metadata.

```text
layer_type_metadata
--------------------------------
id
layer_type_id
metadata_definition_id
is_required
is_enabled
sort_order
configuration
created_at
updated_at
```

Contoh:

## Peta Dasar

```text
Peta Dasar
├── Sumber                 required
├── OPD Penanggung Jawab   required
└── Tahun                  required
```

## Program Prioritas

```text
Program Prioritas
├── Sumber                 required
├── OPD Penanggung Jawab   required
├── Tahun                  required
├── Pagu                   optional
├── Realisasi Anggaran     optional
├── Realisasi Fisik        optional
├── Status                 optional
└── Penerima               optional
```

Keuntungan:

- Jenis peta baru tidak membutuhkan tabel baru.
- Metadata dapat digunakan ulang.
- Metadata dapat diwajibkan atau dijadikan opsional.
- Urutan form dapat dikonfigurasi.
- Konfigurasi validasi dapat disimpan tanpa mengubah struktur utama.

---

# 7. `map_layers`

Menyimpan informasi utama layer.

```text
map_layers
--------------------------------
id
uuid
name
slug
layer_type_id
description

source
source_file
file_format

geometry_type
srid

style
visibility
is_public

created_by
created_at
updated_at
```

Metadata dinamis seperti:

```text
pagu
realisasi
status
penerima
```

**tidak disarankan** dimasukkan langsung ke tabel `map_layers`.

Metadata tersebut disimpan melalui `layer_metadata_values`.

---

# 8. `layer_metadata_values`

Menyimpan nilai metadata aktual milik layer.

```text
layer_metadata_values
--------------------------------
id
layer_id
metadata_definition_id

value_text
value_number
value_date
value_boolean
value_json

created_at
updated_at
```

Contoh:

```text
layer_id = 102
metadata_definition_id = 4
value_number = 2500000000
```

Artinya:

```text
Pagu = Rp2.500.000.000
```

Pendekatan multi-value column memungkinkan metadata mempunyai tipe data berbeda.

---

# 9. Metadata Layer vs Attribute Feature

Ini merupakan bagian penting dalam desain MARIMOI.

## 9.1 Metadata Layer

Menjelaskan keseluruhan layer.

Contoh:

```text
Layer:
RTLH Kota Ternate 2026

Sumber:
Dinas Perkim

OPD:
Dinas Perkim

Tahun:
2026
```

## 9.2 Attribute Feature

Menjelaskan setiap objek/geometri.

Contoh:

```text
RTLH #001
Kelurahan : Kalumpang
Penerima  : Ahmad
Pagu      : 50.000.000
Status    : Selesai

RTLH #002
Kelurahan : Maliaro
Penerima  : Budi
Pagu      : 45.000.000
Status    : Berjalan
```

Arsitektur:

```text
                    MAP LAYER
                        │
          ┌─────────────┴─────────────┐
          │                           │
    LAYER METADATA              GEO DATA
          │                           │
   Sumber, OPD, Tahun          Feature / Geometry
   Pagu, Status, dll                 │
                               Feature Attributes
```

---

# 10. `attribute_definitions`

Jika MARIMOI ingin berkembang menjadi platform geospasial pemerintah daerah, atribut feature juga sebaiknya dibuat metadata-driven.

```text
attribute_definitions
--------------------------------
id
layer_type_id
code
name
data_type
unit
is_required
is_system
is_filterable
is_searchable
sort_order
configuration
```

Contoh:

## Metadata Layer

```text
Program Prioritas
├── Sumber
├── OPD
├── Tahun
├── Pagu
├── Realisasi Anggaran
└── Status
```

## Attribute Feature

```text
Program Prioritas
├── ID Program
├── Nama Program
├── Kecamatan
├── Desa
├── Penerima
├── Koordinat
└── Status
```

---

# 11. Dukungan SHP, KML, KMZ, GeoJSON

User dapat mengunggah:

```text
SHP
KML
KMZ
GeoJSON
```

Alur:

```text
Buat Layer
    ↓
Pilih Jenis Peta
    ↓
Isi Metadata
    ↓
Upload Data Spasial
    ↓
Baca Geometry
    ↓
Baca CRS
    ↓
Baca Attribute
    ↓
Preview
    ↓
Mapping Attribute
    ↓
Validasi
    ↓
Publish
```

Sistem harus membaca:

- Geometry type
- CRS / SRID
- Jumlah feature
- Field/attribute
- Tipe data attribute
- Bounding box
- Validitas geometry

---

# 12. Tahap Preview & Mapping

Setelah upload SHP/KML/KMZ/GeoJSON, tampilkan preview.

Contoh:

```text
250 Feature berhasil dibaca

Geometry : Polygon
CRS      : EPSG:4326
```

Preview attribute:

| Field | Type | Mapping |
|---|---|---|
| ID | Integer | ID Feature |
| NAMA | Text | Nama |
| KECAMATAN | Text | Kecamatan |
| LUAS | Decimal | Luas |
| STATUS | Text | Status |

User dapat menentukan apakah field tertentu akan digunakan sebagai atribut resmi MARIMOI.

---

# 13. Wizard Tambah Layer

Disarankan menggunakan wizard 4 tahap.

## STEP 1 — Informasi Layer

```text
┌─────────────────────────────────────────────┐
│ Tambah Layer                                │
├─────────────────────────────────────────────┤
│                                             │
│ Nama Layer *                                │
│ [ RTLH Kota Ternate 2026              ]     │
│                                             │
│ Jenis Peta *                                │
│ [ Program Prioritas                  ▼ ]    │
│                                             │
│ Deskripsi                                   │
│ [                                       ]   │
│                                             │
│                              [Lanjut →]      │
└─────────────────────────────────────────────┘
```

Ketika user memilih:

```text
Program Prioritas
```

sistem mengambil konfigurasi:

```text
layer_type_metadata
        ↓
metadata_definitions
        ↓
generate dynamic form
```

---

# 14. STEP 2 — Metadata

Contoh form:

```text
┌─────────────────────────────────────────────┐
│ Metadata Layer                              │
├─────────────────────────────────────────────┤
│                                             │
│ Sumber *                                    │
│ [ Dinas Perumahan dan Kawasan Permukiman ]  │
│                                             │
│ OPD Penanggung Jawab *                      │
│ [ Dinas Perkim                       ▼ ]    │
│                                             │
│ Tahun *                                     │
│ [ 2026 ]                                    │
│                                             │
│ ───────── Metadata Tambahan ───────────────  │
│                                             │
│ Pagu                                        │
│ [ Rp 2.500.000.000 ]                        │
│                                             │
│ Realisasi Anggaran                          │
│ [ Rp 1.800.000.000 ]                        │
│                                             │
│ Realisasi Fisik                             │
│ [ 72 ] %                                    │
│                                             │
│ Status                                      │
│ [ Berjalan                           ▼ ]    │
│                                             │
│ Penerima                                    │
│ [ 250 ] KK                                  │
│                                             │
│                              [Lanjut →]      │
└─────────────────────────────────────────────┘
```

Form dibuat secara dinamis berdasarkan definisi metadata.

---

# 15. Custom Metadata

User dapat menambahkan metadata custom.

Contoh:

```text
Nama Metadata
[ Penerima ]

Tipe Data
[ Number ▼ ]

Satuan
[ KK ]

Wajib?
[ ✓ ]

Filter?
[ ✓ ]
```

Kemudian sistem membuat:

```text
metadata_definitions
```

dan relasinya:

```text
layer_type_metadata
```

Contoh konfigurasi:

```text
Program Prioritas
        │
        └── Penerima
              ├── type = number
              ├── unit = KK
              ├── required = false
              └── filterable = true
```

---

# 16. STEP 3 — Upload Data Spasial

```text
┌─────────────────────────────────────────────┐
│ Data Spasial                                │
├─────────────────────────────────────────────┤
│                                             │
│ Format yang didukung:                       │
│ SHP   KML   KMZ   GeoJSON                   │
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │                                         │ │
│ │       Drag & Drop file di sini         │ │
│ │                                         │ │
│ │       atau                             │ │
│ │                                         │ │
│ │       [ Pilih File ]                   │ │
│ │                                         │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ Sistem Koordinat                            │
│ [ EPSG:4326                          ▼ ]    │
│                                             │
│                              [Lanjut →]      │
└─────────────────────────────────────────────┘
```

---

# 17. STEP 4 — Preview & Publish

```text
┌─────────────────────────────────────────────┐
│ Preview Data                                │
├─────────────────────────────────────────────┤
│ 250 Feature berhasil dibaca                 │
│                                             │
│ Geometry: Polygon                           │
│ CRS: EPSG:4326                              │
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ ID │ NAMA │ KECAMATAN │ STATUS │ PAGU │ │
│ ├────┼──────┼───────────┼────────┼──────┤ │
│ │ 1  │ ...  │ ...       │ ...    │ ...  │ │
│ │ 2  │ ...  │ ...       │ ...    │ ...  │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│              [ ← Kembali ] [ Publish ]      │
└─────────────────────────────────────────────┘
```

---

# 18. Detail Layer

Setelah layer berhasil dibuat:

```text
RTLH Kota Ternate 2026

Program Prioritas
────────────────────────────────────────────

[ PETA ]

────────────────────────────────────────────

Informasi Layer

Sumber
Dinas Perkim

OPD Penanggung Jawab
Dinas Perkim

Tahun
2026

Pagu
Rp2,5 M

Realisasi Anggaran
Rp1,8 M

Realisasi Fisik
72%

Status
Berjalan

Penerima
250 KK
```

Tab yang disarankan:

```text
[Overview] [Peta] [Data] [Metadata] [Style] [Riwayat]
```

---

# 19. Dynamic Configuration

Definisi metadata sebaiknya tidak hanya mempunyai:

```text
nama
satuan
```

Tambahkan konfigurasi:

```text
data_type
unit
is_required
is_filterable
is_searchable
is_public
sort_order
validation_rule
options
```

## Contoh Status

```json
{
  "data_type": "select",
  "options": [
    "Belum Mulai",
    "Berjalan",
    "Selesai",
    "Tertunda"
  ],
  "is_filterable": true
}
```

## Contoh Realisasi Fisik

```json
{
  "data_type": "decimal",
  "unit": "%",
  "min": 0,
  "max": 100,
  "is_filterable": true
}
```

## Contoh Pagu

```json
{
  "data_type": "currency",
  "unit": "IDR",
  "min": 0
}
```

---

# 20. Filter Peta Dinamis

Dengan desain metadata-driven, MARIMOI dapat membuat filter secara otomatis.

Contoh:

```text
Filter Layer
────────────────────────

OPD
[ Dinas Perkim ▼ ]

Tahun
[ 2026 ▼ ]

Status
[ Berjalan ▼ ]

Pagu
[ > Rp1 M ]

Realisasi Fisik
[ 50 ─────── 100 ] %
```

Filter tidak perlu dibuat secara hard-code untuk setiap jenis peta.

---

# 21. Relasi Database

Arsitektur utama:

```text
users
  │
  │ created_by
  ▼
map_layers
  │
  ├──────────────► map_layer_types
  │                       │
  │                       ▼
  │                layer_type_metadata
  │                       │
  │                       ▼
  │                metadata_definitions
  │
  ├──────────────► layer_metadata_values
  │
  └──────────────► spatial_data
                          │
                          ▼
                       features
                          │
                          ▼
                  feature_attributes
```

Versi konseptual:

```text
                    ┌─────────────────┐
                    │  LAYER TYPES    │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │    METADATA     │
                    │   DEFINITIONS   │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │      LAYER      │
                    └───────┬─┬───────┘
                            │ │
             ┌──────────────┘ └──────────────┐
             ▼                               ▼
     LAYER METADATA                    SPATIAL DATA
             │                               │
             ▼                               ▼
     Metadata Values                    FEATURES
                                             │
                                             ▼
                                      ATTRIBUTES
```

---

# 22. Rekomendasi Arsitektur Final

MARIMOI sebaiknya tidak menggunakan pendekatan:

```text
Jenis Peta
    ↓
Tabel khusus
```

Contoh yang sebaiknya dihindari:

```text
table_peta_rtlh
table_peta_musrenbang
table_peta_pokir
table_peta_proyek
table_peta_administrasi
```

Pendekatan tersebut akan cepat menjadi sulit dipelihara.

Gunakan:

```text
Jenis Peta
    ↓
Metadata Definition
    ↓
Layer
    ↓
Metadata Value
    ↓
Spatial Feature
    ↓
Feature Attribute
```

Keuntungannya:

- Jenis peta baru tidak membutuhkan tabel baru.
- Metadata dapat digunakan kembali.
- Metadata dapat dibuat wajib/opsional.
- User dapat membuat metadata custom.
- Form dapat dibuat dinamis.
- Filter peta dapat dibuat dinamis.
- Mendukung SHP/KML/KMZ/GeoJSON.
- Cocok untuk PostgreSQL/PostGIS.
- Cocok untuk pengembangan WebGIS skala provinsi.
- Dapat digunakan untuk layer dari Bappeda maupun OPD/Kabupaten/Kota.
- Memudahkan pengembangan dashboard dan analitik.
- Memudahkan standarisasi metadata antar sumber data.

---

# 23. Rekomendasi Implementasi MARIMOI

Prioritas implementasi:

### Tahap 1 — Master

Buat:

```text
map_layer_types
metadata_definitions
layer_type_metadata
```

### Tahap 2 — Layer

Buat:

```text
map_layers
layer_metadata_values
```

### Tahap 3 — Spatial

Buat mekanisme:

```text
Upload
→ Validate
→ Convert
→ PostGIS
→ Feature
```

### Tahap 4 — Attribute

Tambahkan:

```text
attribute_definitions
feature_attributes
```

### Tahap 5 — Dynamic UI

Implementasikan:

```text
Jenis Peta
→ Dynamic Metadata Form
→ Dynamic Attribute Mapping
```

### Tahap 6 — Analysis

Implementasikan:

```text
Dynamic Filter
Dynamic Legend
Thematic Mapping
Dashboard
Statistics
Spatial Analysis
```

---

# 24. Kesimpulan

Desain yang paling tepat untuk MARIMOI adalah menjadikan **jenis peta sebagai template/konfigurasi**, bukan sebagai tabel data.

Konsep utamanya:

```text
                 JENIS PETA
                     │
                     ▼
             METADATA DEFINITION
                     │
                     ▼
             LAYER CONFIGURATION
                     │
                     ▼
                  MAP LAYER
                  /       \
                 /         \
                ▼           ▼
       LAYER METADATA    SPATIAL DATA
                             │
                             ▼
                          FEATURES
                             │
                             ▼
                     FEATURE ATTRIBUTES
```

Dengan pendekatan ini, MARIMOI dapat berkembang dari sekadar aplikasi pemetaan menjadi **platform manajemen data geospasial pembangunan daerah** yang mampu menerima berbagai jenis layer dan sumber data tanpa harus terus mengubah struktur database inti.

Desain ini juga menjadi fondasi yang baik untuk pengembangan MARIMOI menuju konsep **GEOSPASIAL Maluku Utara**, di mana Provinsi, OPD, dan Kabupaten/Kota dapat berkontribusi data menggunakan standar metadata dan struktur layer yang sama.
