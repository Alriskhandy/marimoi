# Roadmap MARIMOI V2

## Tujuan

Dokumen ini menjadi urutan kerja tingkat tinggi untuk seluruh pengembangan MARIMOI V2. Detail teknis setiap area berada pada dokumen plan terkait.

## Tahapan

| Tahap | Fokus | Output |
| --- | --- | --- |
| 0 | Baseline dan keputusan | inventaris pemakai, keputusan role, data, wilayah, SRID, publikasi, dan retensi log |
| 1 | Fondasi database | ownership, master wilayah/sektor, layer metadata, audit, dan constraint |
| 2 | Auth dan authorization | Google login publik, provisioning admin, role, policy, dan scope OPD |
| 3 | WebGIS baru | satu menu peta, katalog layer, filter, map-layer, feature query, dan metadata |
| 4 | Sharing | publication, share token, URL, QR, expiry, revoke, dan access log |
| 5 | Data pembangunan | proyek, lokasi, progres fisik, keuangan, indikator, dan histori |
| 6 | Partisipasi publik | aspirasi, kritik/saran, feedback proyek, tracking, notifikasi, dan privacy |
| 7 | Dashboard dan UI/UX | dashboard eksekutif, halaman utama, responsive UI, dan aksesibilitas |
| 8 | Migrasi dan rollout | backfill, compatibility API, pengujian, monitoring, dan deprecation |

## Definition Of Done Umum

- kebutuhan dan permission terdokumentasi;
- migration aman untuk database yang sudah berisi data;
- feature test happy path, failure path, dan authorization tersedia;
- response API tidak membocorkan data privat;
- query utama memiliki index dan diuji pada data realistis;
- perubahan dapat dipantau dan dikembalikan melalui prosedur rollback.
