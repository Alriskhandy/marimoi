# Plan Partisipasi Publik dan Tracking

## Tujuan

Menyediakan alur transparan untuk aspirasi/usulan, kritik/saran, dan tanggapan proyek yang dapat dilacak oleh pengirim tanpa membuka data pribadi.

## Jenis Layanan

- aspirasi/usulan pembangunan;
- kritik dan saran layanan;
- tanggapan terhadap proyek pembangunan;
- feedback yang terkait dengan layer, feature, proyek, atau lokasi.

## Model Data

- pertahankan tabel lama selama migrasi;
- tambahkan `tracking_token_hash` atau nomor tiket publik yang tidak memuat data sensitif;
- tambahkan status history terpisah, misalnya `submission_status_histories`;
- gunakan `development_project_id`, `project_location_id`, atau `data_spatial_id` bila relasi dapat diverifikasi;
- wilayah memakai `administrative_region_id`, bukan hanya teks;
- lampiran menyimpan storage key, MIME, ukuran, checksum, dan status scan;
- simpan `responded_by`, `responded_at`, dan response publik terpisah dari catatan internal.

## Alur Tracking

1. User publik login Google dan mengirim laporan.
2. Sistem validasi input, lampiran, rate limit, dan koordinat.
3. Sistem menerbitkan nomor tiket/tracking token.
4. Admin menerima, mengklasifikasi, dan memperbarui status.
5. Setiap perubahan status dicatat dengan waktu dan actor.
6. Pengirim melihat status publik dan timeline melalui token/login.
7. Data pribadi dan catatan internal tidak ditampilkan pada halaman publik.

Status awal yang disarankan: `submitted`, `under_review`, `in_progress`, `resolved`, `rejected`, dengan alasan publik saat ditolak/ditutup.

## Kriteria Selesai

- pengirim dapat melacak status dan waktu pembaruan;
- admin dapat memfilter berdasarkan status, wilayah, OPD, jenis, dan periode;
- status tidak dapat berubah tanpa actor dan histori;
- akses tiket milik user lain ditolak;
- notifikasi perubahan status tidak membocorkan isi laporan.
