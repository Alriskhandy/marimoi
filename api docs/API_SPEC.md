# API Specification

# Project Name
MARIMOI - Manajemen Akselerasi Untuk Monitoring dan Integrasi Wilayah

---

# API Overview

| Item | Value |
|---|---|
| Architecture | REST API |
| Authentication | JWT / Sanctum / token based OUTH2 |
| Response Format | JSON |
| Version | v1 |
| Documentation | swagger - route: 'api/v1/documentation' |

---

# API Arsitektur
Request
   ↓
Controller
   ↓
Service
   ↓
Model
   ↓
Resource

---

# API folder
Controller: /api/v1
Service: /api/v1
Resource: /api/v1


# API security
OAUTH2
Rate limit: 60 / minute
Api log

---

# Base URL

local: http://127.0.0.1:8000/api/v1
prod: https://marimoi.malutprov.go.id/api/v1

# Api Response example
{
    success: "true"
    message: "message"
    data: {}
}

---

# API Modul & Endpoint

## Layers
Route: /layers
Method: GET
Model: Category
Cache: true
data: 
{
    id:
    parent_id:
    type:
    name:
    description:
    color:
    icon:
    is_marker:
    is_active:
    created_at:
    updated_at:
    img_path:
    child: {}
}

--

Route: /layers/{id}
Method: GET
Model: Category
Cache: false
data: 
{
    id:
    parent_id:
    type:
    name:
    description:
    color:
    icon:
    is_marker:
    is_active:
    created_at:
    updated_at:
    img_path:
}

## Publications
Route: /publications
Method: GET
Model: Publication
Cache: false
data: 
{
    id:
    title:
    description:
    category:
    file_name:
    file_path:
    file_type:
    file_size:
    file_cover:
    download_count:
    published_at:
}

--

Route: /publications/{id}
Method: GET
Model: Publication
Cache: false
data: 
{
    id:
    title:
    description:
    category:
    file_name:
    file_path:
    file_type:
    file_size:
    file_cover:
    download_count:
    published_at:
}

## Statistics
Route: /web-statistics
Method: GET
Model: Visitor & Aspirasi
Cache: false
data: 
{
    visitors: 
    {
        today:
        week:
        month:
        year:
        total:
    }
    aspirations:
    {
        kritik_dan_saran:
        usulan: 
    }
}

--

---

# Production Setup

## 1. Environment Variables

Tambahkan ke `.env` production:

```
L5_SWAGGER_CONST_HOST=https://marimoi.malutprov.go.id
L5_SWAGGER_GENERATE_ALWAYS=false
```

## 2. Database Migration

Jalankan migration untuk tabel `api_tokens`:

```bash
php artisan migrate --force
```

## 3. Storage Permissions

Pastikan direktori `storage/` writable agar API logger dapat menulis ke `storage/logs/api.log`:

```bash
chmod -R 775 storage/ bootstrap/cache/
chown -R www-data:www-data storage/ bootstrap/cache/
```

## 4. Generate Swagger Docs

Generate dokumentasi Swagger dengan URL server production (pastikan `L5_SWAGGER_CONST_HOST` sudah diset di `.env`):

```bash
php artisan swagger:generate
```

## 5. Clear & Cache Config

```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

## 6. Generate API Token

Buat token untuk setiap client yang akan mengakses API. Raw token hanya ditampilkan sekali — simpan segera di tempat aman.

```bash
# Token tanpa kedaluwarsa
php artisan api:token:generate "Nama Client"

# Token dengan tanggal kedaluwarsa
php artisan api:token:generate "Nama Client" --expires=2027-01-01
```

Client menggunakan token dengan header:
```
Authorization: Bearer marimoi_<raw_token>
```

## 7. Verifikasi

Checklist setelah deploy:

- [ ] `GET /api/v1/layers` tanpa token → **401**
- [ ] `GET /api/v1/layers` dengan `Authorization: Bearer <token>` → **200**
- [ ] `GET /api/v1/publications` dengan token → **200**
- [ ] `GET /api/v1/web-statistics` dengan token → **200**
- [ ] `GET /api/v1/documentation` → Swagger UI terbuka, ada padlock icon di tiap endpoint
- [ ] Cek `storage/logs/api.log` → harus ada log request masuk