# Plan WebGIS, Map, Layer, dan Sharing

## Tujuan

Menggabungkan lima menu peta lama menjadi satu WebGIS yang dapat menyusun layer tematik dan pembangunan dengan filter serta metadata yang konsisten.

## Terminologi dan Model

- **Map**: konfigurasi peta yang dilihat atau dibagikan.
- **Layer**: dataset bertema dengan metadata, sumber, owner, dan versi.
- **Map layer**: penggunaan layer pada map, termasuk order, visibility, opacity, style, filter, dan zoom.
- **Feature**: satu objek/record dalam layer.
- **Geometry**: bentuk spasial feature seperti Point, LineString, atau Polygon.

## Struktur Fitur

- satu menu peta dengan katalog layer;
- kategori layer: tematik dan pembangunan;
- filter wilayah, sektor, OPD, tahun, status, dan kategori;
- pencarian metadata dan atribut utama;
- bbox query dan pagination untuk feature;
- GeoJSON untuk jumlah kecil dan tile/simplification untuk data besar;
- panel metadata: sumber, owner, tanggal data, pembaruan, SRID, kualitas, dan lisensi;
- layer dapat dipakai ulang di banyak map tanpa menyalin metadata.

## Sharing URL dan QR

1. User memilih layer aktif dan konfigurasi map.
2. Sistem menyimpan atau memilih `map_publication`.
3. Sistem membuat token share acak dengan expiry dan status aktif.
4. URL bernama dibentuk dari token; QR dibuat dari URL tersebut.
5. Request publik memeriksa token, publication, visibility map, dan visibility layer.
6. Revoke membuat URL dan QR tidak dapat membuka map.

QR tidak menjadi sumber konfigurasi. QR hanya merepresentasikan URL share.

## Implementasi Bertahap

- pertahankan endpoint lama sebagai compatibility endpoint;
- buat `maps` dan `map_layers`;
- migrasikan daftar layer aktif dan urutan lama;
- pisahkan feature query dari endpoint admin/import;
- tambahkan metadata dan publication;
- tambahkan share token, QR, expiry, revoke, dan access log.

## Kriteria Selesai

- lima tipe peta lama tidak lagi menjadi menu utama;
- filter menghasilkan data konsisten dan terindeks;
- layer private tidak bocor melalui map share;
- konfigurasi style map A tidak mengubah map B;
- URL share dapat dibuka ulang dan QR menunjuk ke URL yang sama.
