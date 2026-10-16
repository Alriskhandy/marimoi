# Evaluasi Progress Dokumen Perencanaan MARIMOI V2

## Tujuan

Memetakan seluruh dokumen di `/docs` terhadap kondisi kode yang benar-benar berjalan saat ini, agar terlihat jelas: dokumen mana yang sudah final (isinya sudah terbukti sesuai dengan yang terimplementasi), dokumen mana yang sudah tidak relevan/tidak terpakai, dan dokumen mana yang perlu diperbaiki agar tetap menjadi acuan yang benar untuk pengembangan MARIMOI V2 selanjutnya. Ditambahkan juga daftar keputusan fitur yang belum jelas/final.

**Metode**: setiap dokumen dibaca ulang lalu diverifikasi terhadap kode nyata (migration, model, controller, route, test) via pembacaan langsung dan pencarian di codebase — bukan asumsi dari isi dokumen saja.

**Kondisi implementasi saat ini** (per evaluasi ini): migrasi database + model untuk Authentication (`users`, `roles`, `opd`, `user_identities`, `user_role_assignments`, `authentication_logs`), login publik via Google (Socialite), dan menu Manajemen Role (khusus Super Admin) sudah berhasil dibangun dan diuji (lihat [01-migrasi-model-auth-google.md](01-migrasi-model-auth-google.md)). Di luar itu — WebGIS V2 (`spatial_layers`/`maps`/`map_shares`), dashboard eksekutif pembangunan, pelacakan aspirasi/partisipasi publik, redesign UI/UX halaman utama, dan `activity_logs` — **belum ada satupun yang diimplementasikan**; baru sebatas folder kosong (`app/Models/Map/`, `app/Models/Master/`, `app/Http/Controllers/Map/`) yang mengindikasikan rencana kerja berikutnya belum dimulai.

## Ringkasan Status Semua Dokumen

| Dokumen | Status | Catatan singkat |
| --- | --- | --- |
| `overview.md` | 🔧 Perlu perbaikan | Tautan navigasi ke dokumen lain rusak (path salah); tabel role tidak sinkron dengan keputusan slug yang sudah dijalankan |
| `db-schema-v2.md` | 🔧 Perlu perbaikan (bagian) / 🟡 Sebagian terimplementasi | Bagian Authentication sudah terimplementasi; slug role rekomendasi (`admin-sistem`, `publik`) tidak sesuai keputusan aktual (`super-admin`, `user`); bagian Peta/Pembangunan/Partisipasi 100% belum diimplementasikan |
| `01_db-analysis/01-current-schema.md` | ⚪ Rencana valid, belum diimplementasikan | Snapshot skema lama, masih akurat sebagai baseline; catatan "perbarui diagram setelah migration produksi stabil" belum relevan karena migration belum jalan |
| `01_db-analysis/02-analysis.md` | ⚪ Rencana valid, belum diimplementasikan | Analisis dan rekomendasi masih berlaku penuh; jadi dasar `db-schema-v2.md`, belum ada satupun poin yang dieksekusi |
| `01_db-analysis/03-database-planning.md` | ⚪ Rencana valid, belum diimplementasikan | Tahap 0–7 (fondasi, katalog, map, share, proyek, dashboard, cleanup) semuanya belum dimulai |
| `01_db-analysis/04-marimoi-x-goat.md` | ⚪ Rencana valid, belum diimplementasikan | Sintesis arsitektur target; belum ada kode yang mengikuti pola `Catalog/Mapping/Sharing/Development` yang direkomendasikan |
| `02_ui-design-analysis/ANALISIS-TAMPILAN-PUBLIK.md` | ⚪ Valid, temuan masih berlaku | Audit bug/UX nyata (route duplikat `tampil.publikasi`, link survei mati, halaman Prioritas Daerah masih statis) — diverifikasi ulang, **semua masih terjadi persis seperti dilaporkan** |
| `03_plan/00-roadmap.md` | 🟡 Sebagian terimplementasi | Tahap 2 (Auth) jalan; Tahap 0, 1, 3–8 belum dimulai |
| `03_plan/01-arsitektur-database.md` | ⚪ Rencana valid, belum diimplementasikan | Isinya konsisten dengan `db-schema-v2.md`/`04-marimoi-x-goat.md` tapi jauh lebih ringkas dan sebagian tumpang-tindih (lihat catatan di bagian C) |
| `03_plan/02-auth-user-role.md` | 🟡 Sebagian terimplementasi | Login Google, provisioning role otomatis, dan pemisahan akses admin/publik sudah jalan; audit "role change memiliki actor/waktu/nilai lama-baru" **belum** ditulis ke `user_role_assignments`, dan login admin (password) belum tercatat di `authentication_logs` |
| `03_plan/03-security-audit.md` | ⚪ Rencana valid, belum diimplementasikan | `authentication_logs` sudah ada tapi baru dipakai jalur Google; `activity_logs` (audit CRUD admin) **belum ada sama sekali**; rekomendasi Form Request untuk validasi belum jadi konvensi (kode masih pakai `Validator::make` inline) |
| `03_plan/04-webgis-map-layer.md` | ⚪ Rencana valid, belum diimplementasikan | Tidak ada `maps`, `map_layers`, `map_shares`, `spatial_layers` di database maupun kode; lima menu peta lama masih terpisah seperti semula |
| `03_plan/05-dashboard-eksekutif.md` | ⚪ Rencana valid, belum diimplementasikan | `DashboardController` saat ini masih dashboard lama (statistik pengunjung/kategori), bukan dashboard pengendalian pembangunan (fisik/keuangan/indikator) yang diminta dokumen ini |
| `03_plan/06-partisipasi-publik.md` | ⚪ Rencana valid, belum diimplementasikan | `aspirasi`/`project_feedbacks` masih struktur lama; belum ada tracking token, status history, atau relasi ke `development_projects` |
| `03_plan/07-uiux-main-page.md` | ⚪ Rencana valid, belum diimplementasikan | Halaman utama & navigasi publik belum dirombak; temuan `ANALISIS-TAMPILAN-PUBLIK.md` (bug/UX) langsung relevan sebagai starting point saat dokumen ini dikerjakan |
| `03_plan/08-migrasi-pengujian.md` | 🟡 Metodologi terbukti pada 1 domain | Prinsip additive migration, backup-first, dan feature test PHPUnit sudah dipraktikkan penuh pada migrasi Authentication; belum diterapkan ke domain lain karena domain lain belum digarap |
| `03_plan/09-ringkasan-konsep-dan-alur.md` | ⚪ Rencana valid, belum diimplementasikan | Konsisten dengan `db-schema-v2.md`; berisi daftar "Keputusan yang Masih Terbuka" yang jadi rujukan utama bagian D di bawah |
| `Hasil_Review_Marimoi_Jamil.md` | ⚪ Rencana valid, belum diimplementasikan | Review awal yang memicu seluruh inisiatif V2; rekomendasinya (dashboard analitik, metadata, tracking aspirasi, sederhanakan beranda) semuanya masih menunggu implementasi |
| `04_implementation/01-migrasi-model-auth-google.md` | ✅ Final & terimplementasi | Dibuat dan diverifikasi pada sesi ini; isinya sudah sinkron dengan kode yang benar-benar berjalan (termasuk penyesuaian pasca-implementasi) |
| `goat/ringkasan-project-dan-arsitektur.md` | 🚫 Tidak relevan/tidak terpakai | Dokumentasi arsitektur GOAT (produk eksternal) apa adanya, bukan spek MARIMOI; sudah diserap sepenuhnya ke `01_db-analysis/04-marimoi-x-goat.md` |
| `goat/ringkasan-database-dan-relasi.md` | 🚫 Tidak relevan/tidak terpakai | Idem — skema database GOAT (PostgreSQL+DuckLake), bukan skema MARIMOI |
| `goat/detail-kolom-dan-relasi-peta-user-layer.md` | 🚫 Tidak relevan/tidak terpakai | Idem — detail kolom tabel GOAT (`apps/core`), tidak dipakai langsung sebagai spek |
| `upgrade-laravel/upgrade-guide.md` | 🚫 Tidak relevan/tidak terpakai | Salinan upgrade guide resmi Laravel 11→12; upgrade sudah selesai dan sudah ter-commit (`3d62276 upgrade laravel version 11 to 12`) — tidak ada tindakan tersisa |

Legenda: ✅ Final & terimplementasi · 🟡 Terimplementasi sebagian · ⚪ Rencana valid, belum diimplementasikan · 🔧 Perlu perbaikan isi dokumen · 🚫 Tidak relevan/tidak terpakai.

---

## A. Dokumen Final — Sudah Tepat dan Terimplementasi

Hanya satu dokumen yang isinya benar-benar mencerminkan kode yang berjalan secara utuh:

- **[`04_implementation/01-migrasi-model-auth-google.md`](01-migrasi-model-auth-google.md)** — migration `users`/`roles`/`opd`/`user_identities`/`user_role_assignments`/`authentication_logs`, model & relasi, seeder, login Google (Socialite) lengkap dengan account-linking dan edge case, serta menu Manajemen Role khusus Super Admin. Sudah diuji dengan PHPUnit (`GoogleAuthenticationTest`, `RoleManagementTest`, `FrontendNavbarAuthTest`) dan diverifikasi terhadap database nyata.

Tidak ada dokumen `03_plan/*` yang masuk kategori ini secara utuh — semuanya masih punya bagian kriteria selesai yang belum tercapai (lihat kolom "Terimplementasi Sebagian" pada tabel, khususnya `02-auth-user-role.md` yang paling dekat ke final).

### Catatan: "Terimplementasi Sebagian" (belum bisa disebut final)

Agar tidak menyamarkan gap yang tersisa, tiga dokumen berikut sengaja **tidak** dimasukkan ke kategori final meski sebagian besar sudah jalan:

| Dokumen | Yang sudah terpenuhi | Yang masih kurang |
| --- | --- | --- |
| `03_plan/02-auth-user-role.md` | Login Google, provisioning role otomatis, pemisahan akses admin/publik, revoked account diuji | Audit histori perubahan role (`user_role_assignments`) belum ditulis oleh `UserController`; login admin (password) belum tercatat di `authentication_logs`; `EnsureAccountIsActive` (logout paksa saat akun dinonaktifkan di tengah sesi) belum dibuat |
| `03_plan/03-security-audit.md` | `authentication_logs` untuk jalur Google, hashing IP/session, rate-limit bawaan Laravel pada beberapa route | `activity_logs` (CRUD audit) belum ada sama sekali; validasi masih `Validator::make` inline, bukan Form Request seperti direkomendasikan |
| `03_plan/08-migrasi-pengujian.md` | Prinsip additive migration, PHPUnit feature test, database test terpisah — terbukti berhasil pada migrasi Authentication | Belum diterapkan ke domain lain karena domain lain (WebGIS, dashboard, partisipasi) belum digarap |

## B. Dokumen Tidak Relevan / Tidak Terpakai

- **`docs/goat/ringkasan-project-dan-arsitektur.md`**
- **`docs/goat/ringkasan-database-dan-relasi.md`**
- **`docs/goat/detail-kolom-dan-relasi-peta-user-layer.md`**

  Ketiganya adalah dokumentasi arsitektur/skema produk GOAT apa adanya (bukan tulisan tentang MARIMOI). Nilainya sudah sepenuhnya diserap dan diterjemahkan ke `01_db-analysis/04-marimoi-x-goat.md` yang secara eksplisit menyebutkan "referensi GOAT digunakan sebagai sumber pola arsitektur... bukan instruksi untuk menyalin seluruh stack". Tidak ada kebutuhan membaca ulang ketiga file ini untuk pengembangan MARIMOI V2 — cukup rujuk `04-marimoi-x-goat.md`. Rekomendasi: boleh diarsipkan/dipindah keluar dari alur dokumentasi aktif.

- **`docs/upgrade-laravel/upgrade-guide.md`**

  Salinan upgrade guide resmi Laravel (11→12). Upgrade tersebut **sudah selesai dan sudah di-commit** (`git log`: `3d62276 upgrade laravel version 11 to 12`). Dokumen ini tidak punya kaitan dengan rancangan fitur MARIMOI V2 dan tidak ada tindakan tersisa — murni referensi historis proses upgrade yang sudah selesai.

## C. Dokumen Perlu Perbaikan

### `overview.md` — tautan navigasi rusak & tabel role usang

1. **Tautan rusak** — seluruh 13 link markdown di file ini salah path:
   - Baris 11–18 memakai `plan/xx-....md`, padahal folder aslinya `03_plan/xx-....md`.
   - Baris 76–80 memakai `../db-analysis/xx-....md`, padahal folder aslinya `01_db-analysis/xx-....md` (dan `Hasil_Review_Marimoi_Jamil.md` berada di folder yang **sama** dengan `overview.md`, bukan di `../`).
   - Baris 7 (`../db-analysis/04-marimoi-x-goat.md`) juga terkena masalah yang sama.
2. **Daftar ruang lingkup tidak lengkap** — tidak menyebut `09-ringkasan-konsep-dan-alur.md`, `02_ui-design-analysis/ANALISIS-TAMPILAN-PUBLIK.md`, maupun folder `04_implementation/` yang sekarang sudah ada.
3. **Tabel "Peran Pengguna" tidak sinkron dengan keputusan aktual** — baris "Cara dibuat" untuk role publik menyebut hasil "Daftar/login mandiri melalui Google" (masih akurat), tapi dokumen ini tidak menyebutkan slug apapun sehingga tidak langsung salah; masalah slug justru ada di `db-schema-v2.md` dan `02-auth-user-role.md` di bawah.

### `db-schema-v2.md` — slug role rekomendasi tidak sesuai keputusan implementasi

- Baris 124: *"Master role. Rekomendasi slug: `admin-sistem`, `admin-bappeda`, `admin-opd`, `publik`."*
- Keputusan yang **sudah dijalankan** di kode (didokumentasikan di `04_implementation/01-migrasi-model-auth-google.md`, bagian "Keputusan yang Perlu Dikunci"): slug **tetap** `super-admin` (bukan `admin-sistem`, demi kompatibilitas ~15 file yang sudah hardcode string tersebut), dan role publik hasil login Google memakai slug **`user`** (bukan `publik` — role ini dibuat manual lewat menu Manajemen Role dengan nama "User" sebelum keputusan `publik` sempat dipakai).
- **Rekomendasi perbaikan**: update baris 124 menjadi `super-admin`, `admin-bappeda`, `admin-opd`, `user` agar dokumen ini konsisten dengan database nyata dan tidak menyesatkan pembaca berikutnya yang membangun fitur di atas skema ini.

### `03_plan/02-auth-user-role.md` — slug role publik usang

- Baris 14: *"`publik` — otomatis setelah Google login — fitur publik yang membutuhkan autentikasi"*. Sama seperti di atas, slug aktual adalah `user`. Perbaiki referensi slug di seluruh dokumen ini (termasuk bagian "Alur Login" langkah 3: *"User baru dibuat dengan role `publik`"*).

### `03_plan/01-arsitektur-database.md` — tumpang tindih dengan dokumen lain

- File ini ada dan valid, tapi isinya (target domain Catalog/Mapping/Development/Identity/Governance, entitas prioritas, aturan data) sudah tercakup lebih detail di `db-schema-v2.md` dan `01_db-analysis/04-marimoi-x-goat.md`. Bagian "Keputusan yang Dibutuhkan" di file ini juga sebagian **sudah dijawab** oleh `db-schema-v2.md` (misalnya "apakah feature tetap di PostGIS untuk fase awal" — sudah dijawab "ya" di `db-schema-v2.md` prinsip desain nomor 6) sehingga terlihat seolah masih terbuka padahal sudah diputuskan. **Rekomendasi**: ringkas dokumen ini menjadi pointer ke `db-schema-v2.md` sebagai sumber kebenaran tunggal, atau hapus poin keputusan yang sudah terjawab agar tidak membingungkan pembaca berikutnya.

## D. Fitur / Keputusan yang Belum Jelas

Dikumpulkan dari bagian "Keputusan yang Masih Terbuka"/"Risiko" di berbagai dokumen, plus temuan baru dari evaluasi ini. Belum ada jawaban resmi untuk poin-poin berikut — perlu diputuskan pemilik produk sebelum domain terkait mulai dibangun:

1. **Level seed wilayah administratif** — sampai kabupaten/kota saja, plus kecamatan, atau sampai desa/kelurahan? (`db-schema-v2.md`, `09-ringkasan-konsep-dan-alur.md §15`)
2. **Akses penerima share URL peta publik** — publication `public` boleh dibuka guest tanpa login, atau semua penerima wajib login? (`db-schema-v2.md`, `09-ringkasan §15`)
3. **Mode peta yang dipublikasikan** — membaca layer versi terbaru secara real-time, atau snapshot versi tertentu saat dipublikasikan? (`01_db-analysis/03-database-planning.md §7`, `09-ringkasan §15`)
4. **Metadata tambahan wajib untuk layer strategis** — kontak, lisensi, lineage, akurasi/kelengkapan data — wajib atau opsional? (`db-schema-v2.md`, `09-ringkasan §15`)
5. **Retensi log** — berapa lama `authentication_logs`, `activity_logs` (belum dibuat), dan `map_share_accesses` (belum dibuat) disimpan, terutama untuk data yang menyentuh privasi (ip_hash, user_agent)? (`03-security-audit.md`, `09-ringkasan §15`)
6. **Approval publikasi data OPD** — apakah publication final dari Admin OPD memerlukan persetujuan Admin Bappeda, atau Admin OPD bisa publish langsung? (`09-ringkasan §15`, `03_plan/09` §7–8)
7. **Relasi final `spatial_layer_features` ↔ `project_locations`** — foreign key di sisi feature atau di sisi lokasi proyek? Cardinality `data_spatial` (satu baris = satu feature, atau satu baris = satu dataset) juga belum diverifikasi ulang terhadap data produksi. (`09-ringkasan §15`, `01_db-analysis/04-marimoi-x-goat.md §5.2`)
8. **Standar validasi/authorization ke depan** — `03-security-audit.md` merekomendasikan Form Request untuk validasi+authorization, tapi seluruh controller yang sudah ada (termasuk `RoleController` yang baru dibuat) memakai `Validator::make` inline mengikuti konvensi lama. **Perlu keputusan eksplisit**: apakah proyek pindah ke Form Request untuk fitur V2 baru, atau tetap konsisten dengan pola inline yang sudah dipakai di seluruh codebase?
9. **Nasib halaman "Prioritas Daerah"** — saat ini hanya menampilkan gambar statis (bukan peta interaktif seperti komentar lama `// NANTINYA DIISI PETA RPJMD //` mengindikasikan). Apakah halaman ini akan dijadikan peta RPJMD interaktif sungguhan sebagai bagian dari WebGIS V2, atau memang sengaja dipertahankan sebagai halaman statis?
10. **Scaffold kosong `app/Models/Map/`, `app/Models/Master/`, `app/Models/Auth/`, `app/Http/Controllers/Map/`** — folder-folder ini sudah ada di working tree tapi masih kosong (tidak ada file, tidak tercatat riwayat commit karena Git tidak melacak folder kosong). Perlu dikonfirmasi ke pemilik/pengembang lain: apakah ini pekerjaan WebGIS V2 (dan kemungkinan restrukturisasi model Auth) yang sedang disiapkan secara paralel — mirip insiden kode Google OAuth manual yang ditemukan sebelumnya — agar tidak terjadi duplikasi struktur atau konflik saat domain terkait mulai dikerjakan.
11. **Standar penamaan role final** — sekarang ada dua sumber kebenaran yang berbeda: dokumen (`admin-sistem`, `publik`) vs implementasi (`super-admin`, `user`). Bagian C di atas merekomendasikan memperbarui dokumen mengikuti kode, tapi ini tetap perlu persetujuan eksplisit karena menyangkut penamaan yang dipakai jangka panjang di seluruh sistem.

## Rekomendasi Urutan Tindak Lanjut

1. Selesaikan bagian "Terimplementasi Sebagian" pada Authentication (audit `user_role_assignments`, `authentication_logs` untuk login admin, `EnsureAccountIsActive`) sebelum pindah domain — ini kriteria selesai yang sudah didefinisikan `03_plan/02-auth-user-role.md` dan belum tercapai.
2. Perbaiki dokumen di kategori C (tautan `overview.md`, slug role di `db-schema-v2.md` dan `02-auth-user-role.md`) sebelum dipakai sebagai acuan tim lain — biaya perbaikannya kecil tapi dampak kebingungannya besar bila dibiarkan.
3. Ambil keputusan pada poin D, minimal untuk 5 poin pertama (wilayah, share URL, snapshot, metadata wajib, retensi log) — ini prasyarat eksplisit sebelum `01_db-analysis/03-database-planning.md` Tahap 2 (katalog layer) bisa dimulai.
4. Lanjutkan ke `04-webgis-map-layer.md` (Tahap 3 roadmap) sebagai domain besar berikutnya, karena `05-dashboard-eksekutif.md` dan `06-partisipasi-publik.md` sama-sama bergantung pada `spatial_layers`/`development_projects` yang belum ada.
5. Konfirmasi status folder kosong `app/Models/Map/`, `app/Models/Master/`, `app/Models/Auth/`, `app/Http/Controllers/Map/` (poin D.10) sebelum mulai coding WebGIS V2, agar tidak tumpang tindih dengan pekerjaan yang mungkin sedang berjalan paralel.
