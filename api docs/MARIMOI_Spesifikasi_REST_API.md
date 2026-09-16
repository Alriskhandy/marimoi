# SPESIFIKASI REST API
# MARIMOI
## Manajemen Akselerasi Untuk Monitoring dan Integrasi Wilayah

**Pemerintah Provinsi Maluku Utara**
Dinas Komunikasi dan Informatika

---

## DAFTAR ISI

1. [Informasi Umum API](#i-informasi-umum-api)
2. [Informasi Integrasi](#ii-informasi-integrasi)
3. [Standar API](#iii-standar-api)
4. [Autentikasi](#iv-autentikasi)
5. [Daftar Endpoint](#v-daftar-endpoint)
6. [Lampiran](#vi-lampiran)

---

## I. INFORMASI UMUM API

| Item | Keterangan |
|---|---|
| Nama Sistem | MARIMOI (Manajemen Akselerasi Untuk Monitoring dan Integrasi Wilayah) |
| Versi API | v1 |
| Arsitektur | REST API |
| Format Data | JSON |
| Enkripsi | HTTPS / TLS |
| Rate Limit | 60 request per menit per IP |
| Dokumentasi Interaktif | `https://marimoi.malutprov.go.id/api/v1/documentation` |

### Base URL

| Lingkungan | URL |
|---|---|
| Production | `https://marimoi.malutprov.go.id/api/v1` |
| Development | `http://127.0.0.1:8000/api/v1` |

---

## II. INFORMASI INTEGRASI

### Persyaratan Integrasi

- Client wajib memiliki **API Token** yang diterbitkan oleh administrator MARIMOI.
- Setiap request wajib menyertakan header `Authorization: Bearer <token>`.
- Semua komunikasi dilakukan melalui protokol **HTTPS**.
- Response selalu dalam format **JSON**.

### Kontak Administrator

Untuk permintaan API Token atau pertanyaan teknis, hubungi tim Dinas Komunikasi dan Informatika Provinsi Maluku Utara.

### Alur Integrasi

```
Client Application
       ↓
Authorization: Bearer <api_token>
       ↓
MARIMOI REST API (rate limit: 60/menit)
       ↓
Validasi Token → Controller → Service → Model
       ↓
JSON Response { success, message, data }
```

---

## III. STANDAR API

### 1. Request Format

Semua request dikirim menggunakan metode HTTP standar (GET). Header wajib disertakan:

```
Authorization: Bearer <api_token>
Accept: application/json
Content-Type: application/json
```

**Contoh request dengan cURL:**

```bash
curl -X GET "https://marimoi.malutprov.go.id/api/v1/layers" \
     -H "Authorization: Bearer marimoi_xxxxxxxxxxxxxxxx" \
     -H "Accept: application/json"
```

---

### 2. Response Format

Seluruh endpoint mengembalikan response JSON dengan struktur standar berikut:

```json
{
    "success": true,
    "message": "OK",
    "data": {}
}
```

| Field | Tipe | Keterangan |
|---|---|---|
| `success` | boolean | `true` jika request berhasil, `false` jika gagal |
| `message` | string | Pesan status response |
| `data` | object / array / null | Payload data hasil request |

**Contoh response sukses:**

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "id": 1,
        "name": "Infrastruktur",
        "type": "tematik"
    }
}
```

---

### 3. Error Format

Response error menggunakan struktur yang sama dengan `success: false`.

```json
{
    "success": false,
    "message": "Pesan error",
    "data": null
}
```

**Daftar HTTP Status Code:**

| Kode | Status | Keterangan |
|---|---|---|
| `200` | OK | Request berhasil |
| `401` | Unauthorized | Token tidak disertakan atau tidak valid |
| `404` | Not Found | Data tidak ditemukan |
| `422` | Unprocessable Entity | Parameter tidak valid |
| `429` | Too Many Requests | Melebihi batas rate limit (60/menit) |
| `500` | Internal Server Error | Kesalahan server |

**Contoh response error 401:**

```json
{
    "success": false,
    "message": "API token diperlukan. Sertakan header Authorization: Bearer <token>",
    "data": null
}
```

**Contoh response error 429:**

```json
{
    "success": false,
    "message": "Too Many Requests.",
    "data": null
}
```

---

## IV. AUTENTIKASI

### Jenis Autentikasi

MARIMOI REST API menggunakan **Bearer Token** berbasis custom API Token. Token diterbitkan oleh administrator dan bersifat rahasia.

### Cara Mendapatkan Token

Token tidak diperoleh melalui proses login. Token diterbitkan oleh administrator sistem menggunakan perintah berikut (hanya dapat dijalankan di server):

```bash
php artisan api:token:generate "Nama Aplikasi Client"
```

Token hanya ditampilkan **satu kali** saat pembuatan. Simpan token di tempat yang aman.

### Cara Menggunakan Token

Sertakan token pada setiap request di header `Authorization`:

```
Authorization: Bearer marimoi_<raw_token>
```

### Autentifikasi Key Value

| Key | Value |
|---|---|
| Header | `Authorization` |
| Format | `Bearer <token>` |
| Contoh | `Bearer marimoi_a1b2c3d4e5f6...` |

### Kedaluwarsa Token

Token dapat dibuat dengan atau tanpa tanggal kedaluwarsa. Jika token telah kedaluwarsa, response akan mengembalikan HTTP 401.

### Keamanan Token

- Token disimpan dalam database dalam bentuk **hash SHA-256** — tidak dapat di-reverse.
- Jika token bocor, hubungi administrator untuk membuat token baru.

---

## V. DAFTAR ENDPOINT

### A. Layers (Data Spasial)

---

#### 1. Daftar Semua Layer

| Item | Keterangan |
|---|---|
| **Endpoint** | `/api/v1/layers` |
| **Method** | `GET` |
| **Autentikasi** | Bearer Token |
| **Cache** | Ya (60 menit per kombinasi filter) |

**Query Parameter (Opsional):**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `type` | string | Tidak | Filter berdasarkan tipe layer |

**Nilai valid untuk parameter `type`:**

| Nilai | Keterangan |
|---|---|
| `tematik` | Layer tematik |
| `usulan_musrenbang` | Usulan musrenbang |
| `pokir_dprd` | Pokok-pokok pikiran DPRD |
| `psd` | Prasarana dan sarana dasar |
| `psn` | Proyek strategis nasional |

**Contoh Request:**

```
GET /api/v1/layers
GET /api/v1/layers?type=tematik
```

**Contoh Response:**

```json
{
    "success": true,
    "message": "OK",
    "data": [
        {
            "id": 1,
            "parent_id": null,
            "type": "tematik",
            "name": "Infrastruktur",
            "description": "Keterangan layer infrastruktur",
            "color": "#FF5733",
            "icon": null,
            "is_marker": false,
            "is_active": true,
            "img_path": "https://marimoi.malutprov.go.id/storage/gambar.png",
            "created_at": "2024-01-01T00:00:00+07:00",
            "updated_at": "2024-01-01T00:00:00+07:00",
            "child": [
                {
                    "id": 2,
                    "parent_id": 1,
                    "type": "tematik",
                    "name": "Jalan",
                    "child": []
                }
            ]
        }
    ]
}
```

---

#### 2. Detail Layer

| Item | Keterangan |
|---|---|
| **Endpoint** | `/api/v1/layers/{id}` |
| **Method** | `GET` |
| **Autentikasi** | Bearer Token |
| **Cache** | Tidak |

**Path Parameter:**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | integer | Ya | ID layer |

**Contoh Request:**

```
GET /api/v1/layers/1
```

**Contoh Response:**

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "id": 1,
        "parent_id": null,
        "type": "tematik",
        "name": "Infrastruktur",
        "description": "Keterangan layer infrastruktur",
        "color": "#FF5733",
        "icon": null,
        "is_marker": false,
        "is_active": true,
        "img_path": null,
        "created_at": "2024-01-01T00:00:00+07:00",
        "updated_at": "2024-01-01T00:00:00+07:00",
        "child": []
    }
}
```

---

### B. Publications (Publikasi)

---

#### 3. Daftar Publikasi

| Item | Keterangan |
|---|---|
| **Endpoint** | `/api/v1/publications` |
| **Method** | `GET` |
| **Autentikasi** | Bearer Token |
| **Cache** | Tidak |

**Query Parameter (Opsional):**

| Parameter | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| `per_page` | integer | Tidak | `15` | Jumlah item per halaman |
| `page` | integer | Tidak | `1` | Nomor halaman |

**Contoh Request:**

```
GET /api/v1/publications
GET /api/v1/publications?per_page=10&page=2
```

**Contoh Response:**

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "items": [
            {
                "id": 1,
                "title": "Laporan Tahunan 2024",
                "description": "Deskripsi publikasi",
                "category": "Laporan",
                "file_name": "laporan-2024.pdf",
                "file_path": "https://marimoi.malutprov.go.id/storage/files/laporan-2024.pdf",
                "file_type": "pdf",
                "file_size": "2.5 MB",
                "file_cover": "https://marimoi.malutprov.go.id/storage/covers/laporan-2024.jpg",
                "download_count": 120,
                "created_at": "2024-01-01T00:00:00+07:00"
            }
        ],
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 72
    }
}
```

---

#### 4. Detail Publikasi

| Item | Keterangan |
|---|---|
| **Endpoint** | `/api/v1/publications/{id}` |
| **Method** | `GET` |
| **Autentikasi** | Bearer Token |
| **Cache** | Tidak |

**Path Parameter:**

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | integer | Ya | ID publikasi |

**Contoh Request:**

```
GET /api/v1/publications/1
```

**Contoh Response:**

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "id": 1,
        "title": "Laporan Tahunan 2024",
        "description": "Deskripsi lengkap publikasi",
        "category": "Laporan",
        "file_name": "laporan-2024.pdf",
        "file_path": "https://marimoi.malutprov.go.id/storage/files/laporan-2024.pdf",
        "file_type": "pdf",
        "file_size": "2.5 MB",
        "file_cover": "https://marimoi.malutprov.go.id/storage/covers/laporan-2024.jpg",
        "download_count": 120,
        "created_at": "2024-01-01T00:00:00+07:00"
    }
}
```

---

### C. Statistics (Statistik Web)

---

#### 5. Statistik Pengunjung dan Aspirasi

| Item | Keterangan |
|---|---|
| **Endpoint** | `/api/v1/web-statistics` |
| **Method** | `GET` |
| **Autentikasi** | Bearer Token |
| **Cache** | Tidak |

**Contoh Request:**

```
GET /api/v1/web-statistics
```

**Contoh Response:**

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "visitors": {
            "today": 45,
            "week": 312,
            "month": 1250,
            "year": 14800,
            "total": 52000
        },
        "aspirations": {
            "kritik_dan_saran": 87,
            "usulan": 214
        }
    }
}
```

---

### Ringkasan Endpoint

| No | Endpoint | Method | Autentikasi | Cache |
|---|---|---|---|---|
| 1 | `/api/v1/layers` | GET | Bearer Token | Ya (60 menit) |
| 2 | `/api/v1/layers/{id}` | GET | Bearer Token | Tidak |
| 3 | `/api/v1/publications` | GET | Bearer Token | Tidak |
| 4 | `/api/v1/publications/{id}` | GET | Bearer Token | Tidak |
| 5 | `/api/v1/web-statistics` | GET | Bearer Token | Tidak |

---

## VI. LAMPIRAN

### A. Arsitektur Sistem

```
Request (HTTPS)
       ↓
Nginx (rate limit, static assets)
       ↓
Laravel 11 Application
       ↓
Middleware Stack:
  - throttle:api-v1 (60/menit)
  - api.logger (log ke storage/logs/api.log)
  - api.token (validasi Bearer Token)
       ↓
Controller (Api/V1/)
       ↓
Service (business logic)
       ↓
Model + Cache (Laravel Cache)
       ↓
Resource (transformasi data)
       ↓
JSON Response { success, message, data }
```

### B. Struktur Data Layer

| Field | Tipe | Nullable | Keterangan |
|---|---|---|---|
| `id` | integer | Tidak | ID unik layer |
| `parent_id` | integer | Ya | ID parent (null = root layer) |
| `type` | string | Tidak | Tipe layer |
| `name` | string | Tidak | Nama layer |
| `description` | string | Ya | Deskripsi layer |
| `color` | string | Ya | Warna layer (hex) |
| `icon` | string | Ya | Nama icon |
| `is_marker` | boolean | Tidak | Apakah layer menggunakan marker |
| `is_active` | boolean | Tidak | Status aktif layer |
| `img_path` | string | Ya | URL gambar layer |
| `created_at` | datetime | Tidak | Waktu pembuatan (ISO 8601) |
| `updated_at` | datetime | Tidak | Waktu pembaruan (ISO 8601) |
| `child` | array | Tidak | Daftar child layer |

### C. Struktur Data Publication

| Field | Tipe | Nullable | Keterangan |
|---|---|---|---|
| `id` | integer | Tidak | ID unik publikasi |
| `title` | string | Tidak | Judul publikasi |
| `description` | string | Ya | Deskripsi publikasi |
| `category` | string | Ya | Kategori publikasi |
| `file_name` | string | Ya | Nama file |
| `file_path` | string | Ya | URL download file |
| `file_type` | string | Ya | Tipe file (pdf, doc, dll) |
| `file_size` | string | Tidak | Ukuran file (diformat, misal: 2.5 MB) |
| `file_cover` | string | Ya | URL gambar cover |
| `download_count` | integer | Tidak | Jumlah unduhan |
| `created_at` | datetime | Tidak | Waktu pembuatan (ISO 8601) |

### D. Struktur Data Web Statistics

| Field | Tipe | Keterangan |
|---|---|---|
| `visitors.today` | integer | Jumlah pengunjung hari ini |
| `visitors.week` | integer | Jumlah pengunjung minggu ini |
| `visitors.month` | integer | Jumlah pengunjung bulan ini |
| `visitors.year` | integer | Jumlah pengunjung tahun ini |
| `visitors.total` | integer | Total seluruh pengunjung |
| `aspirations.kritik_dan_saran` | integer | Jumlah aspirasi kritik dan saran |
| `aspirations.usulan` | integer | Jumlah aspirasi usulan |

### E. Contoh Integrasi (Flutter/Dart)

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

const String baseUrl = 'https://marimoi.malutprov.go.id/api/v1';
const String apiToken = 'marimoi_xxxxxxxxxxxxxxxx';

Future<Map<String, dynamic>> getLayers({String? type}) async {
  final uri = Uri.parse('$baseUrl/layers').replace(
    queryParameters: type != null ? {'type': type} : null,
  );

  final response = await http.get(
    uri,
    headers: {
      'Authorization': 'Bearer $apiToken',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Gagal memuat data: ${response.statusCode}');
  }
}
```

### F. Log API

Seluruh request ke API v1 dicatat secara otomatis di:

```
storage/logs/api.log
```

Format log:

```json
{
    "method": "GET",
    "url": "https://marimoi.malutprov.go.id/api/v1/layers",
    "ip": "192.168.1.1",
    "user_agent": "Dart/3.0",
    "status": 200,
    "duration_ms": 45
}
```

---

*Dokumen ini dibuat untuk keperluan integrasi sistem eksternal dengan MARIMOI REST API v1.*
*Dinas Komunikasi dan Informatika — Pemerintah Provinsi Maluku Utara*
