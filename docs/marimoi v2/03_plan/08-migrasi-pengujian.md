# Plan Migrasi, Pengujian, dan Rollout

## Tujuan

Memastikan perubahan MARIMOI V2 dapat diterapkan pada database dan aplikasi yang sudah berjalan dengan risiko terukur.

## Strategi Migrasi

1. backup database dan uji restore di staging;
2. inventaris route, controller, model, importer, dan frontend yang memakai struktur lama;
3. tambahkan tabel/kolom baru tanpa menghapus data;
4. buat command/job backfill yang idempotent;
5. verifikasi count, foreign key, unique, geometri, dan checksum;
6. ubah read path ke struktur baru;
7. ubah write path dan jalankan dual-read bila diperlukan;
8. observasi error dan query selama compatibility period;
9. hapus/deprecate struktur lama setelah persetujuan dan backup.

## Testing Minimum

- migration pada database kosong dan database berisi fixture realistis;
- feature test login Google callback, role, policy, dan scope OPD;
- test layer metadata, map-layer, publication, share expiry, revoke, dan QR URL;
- test tracking submission, status history, privacy, dan attachment;
- test dashboard filter, agregasi, dan visibility publik/admin;
- test upload file berbahaya, invalid geometry, SRID salah, dan import gagal;
- test queue retry, idempotensi, dan partial failure;
- query performance dengan `EXPLAIN` pada data staging;
- browser test responsive untuk workflow utama.

## Rollout

- gunakan feature flag untuk menu peta baru dan dashboard;
- rilis migration sebelum mengaktifkan read path baru;
- aktifkan per role atau environment secara bertahap;
- monitor exception, authentication failure, queue failure, latency, dan query lambat;
- siapkan rollback aplikasi dan prosedur restore sebelum production rollout.

## Kriteria Selesai

- test terpengaruh lulus;
- data lama dan baru dapat dibandingkan;
- tidak ada route publik yang membocorkan resource private;
- migration rollback/restore telah diuji di staging;
- dokumentasi deprecation dan owner operasional tersedia.
