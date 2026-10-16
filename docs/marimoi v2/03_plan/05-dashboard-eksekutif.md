# Plan Dashboard Eksekutif

## Tujuan

Mengubah dashboard dari sekadar statistik umum menjadi alat monitoring pembangunan yang dapat digunakan untuk membandingkan kondisi antarwilayah, OPD, sektor, dan tahun.

## Data Dasar

- proyek pembangunan dan kode proyek;
- OPD penanggung jawab dan sektor;
- tahun anggaran dan sumber dana;
- wilayah dan lokasi geometry;
- target/realisasi fisik;
- target/realisasi keuangan;
- status proyek dan periode laporan;
- indikator, satuan, target, nilai, dan sumber.

## Metric Minimum

| Metric | Definisi wajib |
| --- | --- |
| jumlah proyek | status, periode, dan cakupan filter |
| progres fisik | formula agregasi dan sumber laporan |
| realisasi keuangan | pagu, realisasi, mata uang, dan tahun anggaran |
| capaian indikator | nilai aktual dibanding target dan arah capaian |
| tren | periode, baseline, dan aturan perubahan |
| proyek bermasalah | definisi status/threshold dan waktu snapshot |

## Tampilan

- kartu ringkasan dengan sumber dan waktu pembaruan;
- peta infrastruktur dan pengembangan wilayah;
- filter wilayah, sektor, OPD, tahun, dan status;
- tabel proyek yang dapat ditelusuri ke detail dan laporan;
- grafik tren fisik, keuangan, dan indikator;
- penanda data kosong, terlambat, atau belum diverifikasi.

## Arsitektur Query

- query dashboard memakai data terstruktur, bukan parsing `dbf_attributes`;
- gunakan eager loading dan agregasi terukur;
- tambahkan index sesuai execution plan;
- gunakan snapshot/materialized view hanya setelah benchmark;
- setiap response menyertakan `calculated_at`, filter, dan sumber metric.

## Kriteria Selesai

- hasil filter konsisten antara peta, kartu, tabel, dan grafik;
- angka dapat ditelusuri ke proyek/laporan sumber;
- dashboard admin memiliki scope sesuai role dan OPD;
- dashboard publik hanya menampilkan data yang telah dipublikasikan;
- data belum lengkap tidak dipresentasikan sebagai angka final.
