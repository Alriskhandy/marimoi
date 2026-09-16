# Plan UI/UX Peta dan Halaman Utama

## Tujuan

Menyederhanakan akses ke layanan utama dan menjadikan WebGIS sebagai pengalaman utama, bukan kumpulan menu yang terpisah-pisah.

## Struktur Halaman Utama

1. navigasi utama: Peta, Dashboard, Partisipasi Publik, Publikasi, dan bantuan;
2. peta utama sebagai entry point dengan pencarian dan layer katalog;
3. ringkasan pembangunan yang mengarah ke dashboard detail;
4. status/tracking untuk laporan user;
5. publikasi dan peta yang sedang dibagikan;
6. informasi metadata dan sumber data yang mudah ditemukan.

## Pengalaman WebGIS

- satu menu Peta untuk tematik dan pembangunan;
- panel layer dengan group, search, visibility, order, dan opacity;
- filter wilayah, sektor, OPD, tahun, dan status;
- legenda mengikuti layer yang aktif;
- panel detail feature menampilkan atribut penting dan metadata layer;
- kontrol share URL/QR berada dekat daftar layer aktif;
- state map dapat dipulihkan dari URL/share publication.

## Dashboard Eksekutif

- tampilkan metric utama sebelum visualisasi sekunder;
- sediakan filter global yang memengaruhi semua komponen;
- bedakan data aktual, target, dan data belum diverifikasi;
- gunakan tabel detail untuk audit angka;
- tampilkan sumber dan waktu pembaruan tanpa menyembunyikannya di halaman lain.

## Prinsip UX

- responsive untuk desktop dan mobile;
- empty, loading, error, permission denied, dan stale data memiliki state jelas;
- form publik menampilkan validasi dan nomor tiket setelah berhasil;
- warna status konsisten dan tetap terbaca tanpa mengandalkan warna saja;
- navigasi keyboard, label form, kontras, dan focus state diuji.

## Kriteria Selesai

- user dapat menemukan Peta dan Dashboard dari halaman utama tanpa menelusuri menu lama;
- filter tidak menghilangkan konteks layer atau metric;
- UI tidak menampilkan data private pada state loading/error;
- layout diuji pada viewport desktop dan mobile.
