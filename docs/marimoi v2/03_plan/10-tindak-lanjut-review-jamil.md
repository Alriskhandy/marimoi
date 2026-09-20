# Tindak Lanjut Hasil Review (Jamil) — Rekomendasi yang Belum Terjawab

## Sumber dan Konteks

- Sumber rekomendasi: [`../Hasil_Review_Marimoi_Jamil.md`](../Hasil_Review_Marimoi_Jamil.md).
- Audit kondisi eksisting per dokumen ini: branch `laravell-12-db-v2`, commit `54a0dc3`, 2026-09-21.
- Dokumen `04_implementation/02-evaluasi-progress-dokumen.md` menyatakan seluruh rekomendasi review "belum diimplementasikan". Sejak evaluasi itu ditulis, beranda dan peta tematik sudah mengalami beberapa iterasi (redesain "Spatial Intelligence", share link peta, loading per layer). Dokumen ini menulis ulang status berdasarkan kode yang benar-benar berjalan saat ini, bukan mengulang asumsi lama.
- Dokumen ini **tidak menggantikan** `04-webgis-map-layer.md`, `05-dashboard-eksekutif.md`, `06-partisipasi-publik.md`, atau `09-ringkasan-konsep-dan-alur.md`. Dokumen-dokumen tersebut tetap jadi rujukan target akhir (schema V2 penuh: `spatial_layers`, `development_projects`, `map_shares`, dst). Dokumen ini adalah **jalur incremental** yang bisa dikerjakan di atas schema `data_spatial`/`aspirasi` yang ada sekarang, tanpa menunggu migrasi besar tersebut.

## Ringkasan Kondisi Eksisting vs Rekomendasi

| # | Rekomendasi Review | Status | Bukti |
| --- | --- | --- | --- |
| 1 | Sederhanakan halaman beranda | ✅ Selesai | `resources/views/frontend/pages/home.blade.php` — redesain scroll-story satu CTA utama ("Jelajahi Peta"), hasil commit `0ae71d1` dan lanjutannya |
| 2 | Dashboard eksekutif (fisik, keuangan, progres proyek) | ❌ Belum | `app/Http/Controllers/DashboardController.php` masih 100% statistik aspirasi + pengunjung, tidak ada satu pun field fisik/keuangan/proyek |
| 3 | Metadata dataset (sumber data, tanggal pembaruan, instansi pengelola) | ❌ Belum | Migration `data_spatial` tidak punya kolom `sumber_data`/`instansi_pengelola`; popup detail peta tidak menampilkan metadata ini |
| 4 | Mekanisme pelacakan status aspirasi | 🟡 Sebagian | Status lifecycle + email notifikasi (`TanggapanMail`) sudah ada di backend admin; **tidak ada** halaman publik untuk cek status pakai nomor tiket |
| 5 | Filter wilayah, sektor, OPD, tahun pada visualisasi data | 🟡 Sebagian | Peta Tematik publik (`peta.blade.php`) sudah punya search layer/kategori + share link (commit `dc839c4`), tapi belum ada filter terstruktur per wilayah/OPD/tahun |
| 6 | Analisis tren pembangunan | 🟡 Sebagian | Tren bulanan baru ada untuk data aspirasi (`DashboardController::getMonthlyData`), belum ada tren capaian pembangunan (fisik/keuangan) |

Dokumen ini fokus ke baris 2–6. Baris 1 sudah selesai dan tidak dibahas lagi di bawah.

## A. Metadata Dataset (Prasyarat)

Item ini murah dikerjakan dan jadi prasyarat kepercayaan data untuk filter (C) dan dashboard (D) — tanpa `instansi_pengelola` dan `tahun`/`sumber_data` yang konsisten, filter OPD/tahun di bagian C tidak punya sumber nilai yang bisa diandalkan.

### Perubahan Data

- Tambah migration `data_spatial`: `sumber_data` (string, nullable), `opd_pengelola_id` (foreign key ke `opd`, nullable), `tanggal_data` (date, nullable — tahun/tanggal referensi data, berbeda dari `updated_at` sistem).
- Isi otomatis `opd_pengelola_id` dari `user_id` pengunggah bila admin adalah admin-opd; admin-bappeda/super-admin wajib memilih manual saat input/edit.

### Tampilan

- Form input/edit data spasial (backend): field sumber data, OPD pengelola, tanggal data — wajib diisi sebelum data bisa "dipublikasikan" (bila ada flag publish; jika belum ada, tampilkan sebagai warning, bukan blocker keras, di iterasi pertama).
- Popup detail peta publik (`detail-peta.blade.php`) dan info panel beranda (`#mapInfo` di `home.blade.php`): tampilkan sumber data, tanggal data, dan instansi pengelola.

### Kriteria Selesai

- Semua data spasial baru wajib mengisi sumber data dan OPD pengelola sebelum tersimpan.
- Popup peta publik menampilkan metadata ini tanpa perlu buka halaman lain.
- Data lama yang belum punya metadata ditandai jelas ("metadata belum lengkap"), bukan ditampilkan seolah lengkap.

## B. Pelacakan Status Aspirasi Publik

Backend sudah punya semua bahan (`status`, `nomor_tiket`, `tanggapan_admin`, `tanggal_respon`) — yang belum ada murni halaman publik dan endpoint pencariannya. Ini bisa dikerjakan tanpa tabel baru untuk iterasi pertama; tabel riwayat status (`submission_status_histories`, sesuai `06-partisipasi-publik.md`) menyusul di iterasi kedua kalau dibutuhkan histori lengkap.

### Alur

1. Halaman baru `GET /aspirasi-masyarakat/lacak` — form input nomor tiket + salah satu dari (email atau nomor HP) sebagai verifikasi kepemilikan.
2. Endpoint mencari `Aspirasi` berdasarkan `nomor_tiket`, memvalidasi kecocokan email/phone, lalu menampilkan: status saat ini, tanggal pengajuan, tanggal respon (jika ada), `tanggapan_admin` (bagian yang memang untuk publik — bukan catatan internal), dan timeline sederhana `submitted → diproses → selesai/ditolak` berbasis kolom `status` yang ada.
3. Setelah submit form aspirasi baru berhasil (`aspirasi.blade.php`), tampilkan nomor tiket dengan tautan langsung ke halaman lacak.
4. Rate limit endpoint pelacakan (throttle Laravel bawaan) supaya nomor tiket tidak bisa di-brute-force; kombinasi nomor tiket + email/phone wajib cocok, bukan nomor tiket saja.

### Kriteria Selesai

- Pengirim bisa cek status tanpa login, hanya dengan nomor tiket + email/phone.
- Endpoint pelacakan tidak membocorkan data aspirasi milik pengirim lain (percobaan nomor tiket acak tanpa email/phone yang cocok ditolak).
- Rate limit teruji (feature test: percobaan berulang kena throttle).
- Link "Lacak Aspirasi" muncul di halaman aspirasi publik dan di pesan sukses submit.

## C. Filter Terstruktur pada Peta Tematik Publik

Dibangun di atas fitur yang sudah ada (search layer/kategori, loading per layer, share link — commit `dc839c4`), bukan menggantikannya.

### Perubahan

- Tambah filter dropdown di `#mapTools` (`peta.blade.php` dan `home.blade.php`): Kabupaten/Kota, Tahun (`data_spatial.tahun`), OPD Pengelola (dari kolom baru di bagian A), Sektor/Kategori (dari `categories`).
- Filter beroperasi di atas layer yang sudah aktif (kombinasi AND dengan search existing), dieksekusi client-side untuk data yang sudah dimuat, atau lewat parameter query ke endpoint per-layer bila jumlah data besar.
- State filter aktif ikut tersimpan di `shared_maps` (`SharedMap` model) saat share link dibuat, supaya link yang dibagikan membuka kombinasi layer + filter yang sama persis.

### Kriteria Selesai

- User bisa mempersempit tampilan peta berdasarkan wilayah, tahun, OPD, dan sektor sekaligus.
- Kombinasi filter + layer aktif konsisten dengan yang tersimpan di share link.
- Filter tidak menyembunyikan indikator jumlah titik yang sedang ditampilkan (`#mapCount` tetap akurat).

## D. Dashboard Eksekutif Minimum (Fisik, Keuangan, Progres Proyek)

Bagian paling besar. `data_spatial` saat ini **tidak menyimpan** data progres fisik atau realisasi keuangan sama sekali — hanya `dbf_attributes` JSONB generik. Rekomendasi review soal "dashboard pengendalian pembangunan" tidak bisa dipenuhi tanpa data model baru; ini bukan sekadar query ulang data yang sudah ada.

### Perubahan Data (minimum, bukan full schema V2)

- Tabel baru `project_progress_reports`: `data_spatial_id` (FK ke baris `data_type = 'proyek_strategis'`), `opd_id`, `sektor` (atau `kategori_id`), `tahun_anggaran`, `pagu`, `realisasi_anggaran`, `progres_fisik_persen`, `status` (`on_track`/`terlambat`/`selesai`/dll), `periode_laporan`, `catatan`, `dilaporkan_oleh`, timestamps.
- Satu proyek (`data_spatial` dengan `data_type = 'proyek_strategis'`) bisa punya banyak baris `project_progress_reports` (satu per periode laporan) — ini yang jadi basis tren di bagian E.
- Nama tabel sengaja disamakan dengan `09-ringkasan-konsep-dan-alur.md` §12 supaya kalau migrasi V2 penuh (`development_projects`) jalan nanti, tabel ini tinggal di-reparent dari `data_spatial_id` ke `project_location_id`, bukan dibangun ulang dari nol.

### Alur Input

1. Admin OPD/Bappeda membuka proyek strategis miliknya (baris `data_spatial` existing).
2. Admin mengisi laporan progres periode berjalan: pagu, realisasi anggaran, persen fisik, status.
3. Laporan baru **menambah** baris (bukan overwrite), supaya histori per periode tersimpan.

### Dashboard

- Controller baru (terpisah dari `DashboardController` yang sudah sarat urusan aspirasi/pengunjung) khusus dashboard pembangunan, atau tab baru di dashboard existing.
- Kartu ringkasan: jumlah proyek aktif, total pagu vs realisasi (persen), rata-rata progres fisik, jumlah proyek bermasalah (progres jauh di bawah target berdasarkan threshold yang disepakati).
- Filter global: wilayah, sektor, OPD, tahun, status — memengaruhi kartu, tabel, dan grafik sekaligus (bukan filter yang cuma mengubah satu komponen).
- Tabel proyek yang bisa ditelusuri ke detail dan laporan sumbernya (audit trail angka).
- Scope akses: admin-opd hanya lihat proyek OPD-nya (pola `applyOpdFilterToRawQuery` yang sudah dipakai `DashboardController` bisa direplikasi); dashboard publik (jika ada) hanya menampilkan proyek yang statusnya "dipublikasikan".

### Kriteria Selesai

- Dashboard menampilkan capaian fisik, realisasi keuangan, dan status proyek — bukan lagi cuma statistik aspirasi/pengunjung.
- Angka di kartu ringkasan bisa ditelusuri sampai ke laporan progres sumbernya.
- Filter wilayah/sektor/OPD/tahun konsisten antara kartu, tabel, dan grafik.
- Admin OPD tidak bisa melihat data progres OPD lain.

## E. Analisis Tren Pembangunan

Lanjutan dari D — tidak bisa dimulai sebelum `project_progress_reports` punya data multi-periode.

- Agregasi tren tahun-ke-tahun: progres fisik dan realisasi anggaran per sektor, wilayah, dan OPD, dari histori `project_progress_reports`.
- Tampil sebagai grafik tren di dashboard eksekutif (bagian D), bukan dashboard terpisah.
- Definisikan baseline dan aturan perubahan (mis. dibanding periode sebelumnya vs dibanding target tahunan) secara eksplisit, sesuai catatan "tren: periode, baseline, dan aturan perubahan" di `05-dashboard-eksekutif.md`.

### Kriteria Selesai

- Tren bisa difilter per sektor/wilayah/OPD/tahun.
- Baseline perbandingan (periode sebelumnya vs target) jelas dan konsisten di seluruh grafik.

## Urutan Pengerjaan yang Disarankan

1. **A — Metadata dataset.** Paling murah, jadi prasyarat kepercayaan data untuk C dan D.
2. **C — Filter peta tematik.** Bisa jalan cepat begitu kolom OPD/tahun dari A tersedia; user-facing win cepat.
3. **B — Pelacakan aspirasi.** Independen dari A/C, bisa dikerjakan paralel; dampak transparansi publik langsung terasa.
4. **D — Dashboard eksekutif.** Effort terbesar (tabel baru, form admin, scope akses); mulai setelah A selesai.
5. **E — Tren pembangunan.** Baru bisa dimulai setelah D punya data multi-periode berjalan minimal 1-2 periode laporan.

## Definition of Done Tambahan

Mengikuti DOD umum di `00-roadmap.md`, ditambah:

- Setiap fitur baru pada dokumen ini punya feature test happy path, failure path, dan authorization (khususnya scope OPD di bagian D).
- Migration bersifat additive terhadap `data_spatial`/`aspirasi` yang sudah berisi data produksi (sesuai prinsip di `08-migrasi-pengujian.md`).
- Tidak ada bagian dari dokumen ini yang mensyaratkan migrasi schema V2 penuh (`spatial_layers`/`development_projects`) selesai lebih dulu — semuanya jalan di atas `data_spatial`/`categories`/`aspirasi` yang ada sekarang.

## Referensi

- [`../Hasil_Review_Marimoi_Jamil.md`](../Hasil_Review_Marimoi_Jamil.md) — sumber rekomendasi.
- [`04-webgis-map-layer.md`](04-webgis-map-layer.md), [`05-dashboard-eksekutif.md`](05-dashboard-eksekutif.md), [`06-partisipasi-publik.md`](06-partisipasi-publik.md) — target akhir bila migrasi schema V2 penuh berjalan.
- [`09-ringkasan-konsep-dan-alur.md`](09-ringkasan-konsep-dan-alur.md) — penamaan tabel V2 yang jadi rujukan penamaan `project_progress_reports` di bagian D.
- [`../04_implementation/02-evaluasi-progress-dokumen.md`](../04_implementation/02-evaluasi-progress-dokumen.md) — evaluasi terakhir sebelum redesain beranda dan peta lanjutan; sebagian temuannya (soal beranda) sudah tidak berlaku, lihat tabel status di atas.
