# Rekomendasi Pengelompokan Layer dan Arsitektur Analisis MARIMOI

## 1. Latar Belakang

Berdasarkan hasil review aplikasi MARIMOI, aplikasi telah memiliki fondasi yang baik sebagai platform perencanaan pembangunan daerah berbasis WebGIS. MARIMOI telah mengintegrasikan informasi spasial, proyek strategis, Musrenbang, Pokok Pikiran DPRD, aspirasi masyarakat, dan publikasi dalam satu portal.

Namun, struktur informasi pada Peta Tematik perlu dikembangkan agar tidak hanya berfungsi sebagai kumpulan layer peta, tetapi juga menjadi fondasi bagi:

- dashboard eksekutif;
- monitoring pembangunan;
- analisis spasial;
- analisis tren pembangunan;
- analisis kesenjangan infrastruktur; dan
- pengambilan keputusan berbasis data.

Rekomendasi utama adalah mengubah pendekatan pengelompokan layer dari sekadar berdasarkan dataset yang tersedia menjadi **arsitektur informasi pembangunan yang berorientasi pada kebutuhan analisis**.

---

# 2. Prinsip Dasar Pengelompokan Layer

Pengelompokan layer MARIMOI sebaiknya mengikuti beberapa prinsip:

1. **Berorientasi pada kebutuhan pengguna**
   - Masyarakat membutuhkan informasi lokasi dan pembangunan.
   - Perencana membutuhkan kondisi, sebaran, dan intervensi pembangunan.
   - Pimpinan membutuhkan indikator, progres, tren, dan masalah strategis.

2. **Berorientasi pada infrastruktur**
   - Infrastruktur dan konektivitas menjadi kelompok utama karena sesuai dengan fokus MARIMOI sebagai sistem informasi manajemen akselerasi infrastruktur.

3. **Memisahkan kondisi eksisting dengan intervensi pembangunan**
   - Layer kondisi wilayah menunjukkan keadaan saat ini.
   - Layer pembangunan menunjukkan intervensi pemerintah atau usulan pembangunan.

4. **Memiliki dimensi waktu**
   - Data harus dapat dibandingkan antarperiode/tahun sehingga mendukung analisis tren.

5. **Memiliki metadata**
   - Setiap dataset perlu memiliki sumber, instansi pengelola, periode data, dan waktu pembaruan.

6. **Mendukung analisis lintas dataset**
   - Layer tidak hanya ditampilkan secara individual, tetapi dapat dikombinasikan untuk menghasilkan informasi baru.

---

# 3. Struktur Pengelompokan Layer yang Direkomendasikan

Struktur utama Peta Tematik MARIMOI direkomendasikan sebagai berikut:

```text
PETA TEMATIK MARIMOI
│
├── 1. REFERENSI WILAYAH
│
├── 2. INFRASTRUKTUR & KONEKTIVITAS
│   ├── Transportasi
│   ├── Utilitas Dasar
│   ├── Energi
│   ├── Digital
│   └── Infrastruktur Ekonomi
│
├── 3. KAWASAN & POTENSI WILAYAH
│   ├── Permukiman
│   ├── Pertanian
│   ├── Perkebunan
│   ├── Perikanan
│   ├── Pertambangan
│   ├── Industri
│   ├── Pariwisata
│   ├── Kawasan Strategis
│   └── Kawasan Konservasi
│
├── 4. LAYANAN DASAR & FASILITAS PUBLIK
│   ├── Pendidikan
│   ├── Kesehatan
│   ├── Pemerintahan
│   ├── Pasar
│   ├── Fasilitas Sosial
│   └── Fasilitas Ekonomi
│
├── 5. PEMBANGUNAN & INTERVENSI
│   ├── Proyek Strategis Daerah
│   ├── Proyek Strategis Nasional
│   ├── Program Prioritas Daerah
│   ├── Musrenbang
│   ├── Pokok Pikiran DPRD
│   ├── Aspirasi Masyarakat
│   └── Infrastruktur Terbangun
│
├── 6. LINGKUNGAN & RISIKO
│   ├── Rawan Banjir
│   ├── Rawan Longsor
│   ├── Rawan Bencana
│   ├── Tutupan Lahan
│   ├── DAS
│   ├── Kawasan Lindung
│   └── Kawasan Konservasi
│
└── 7. ANALISIS
    ├── Konektivitas Wilayah
    ├── Aksesibilitas Layanan
    ├── Kesenjangan Infrastruktur
    ├── Sebaran Pembangunan
    ├── Prioritas Intervensi
    └── Tren Pembangunan
```

---

# 4. Referensi Wilayah

Kelompok ini berfungsi sebagai layer dasar untuk orientasi spasial dan filtering.

```text
REFERENSI WILAYAH
├── Batas Provinsi
├── Batas Kabupaten/Kota
├── Batas Kecamatan
├── Batas Desa/Kelurahan
├── Ibu Kota Provinsi
├── Ibu Kota Kabupaten/Kota
└── Dapil
```

Layer referensi tidak perlu dianggap sebagai data pembangunan. Fungsinya adalah menjadi **spatial reference layer** yang digunakan oleh kelompok layer lainnya.

Layer ini juga penting untuk fitur filter:

```text
Provinsi
    ↓
Kabupaten/Kota
    ↓
Kecamatan
    ↓
Desa/Kelurahan
```

---

# 5. Infrastruktur & Konektivitas

Kelompok ini sebaiknya menjadi **kelompok utama** dalam MARIMOI.

## 5.1 Transportasi

```text
INFRASTRUKTUR & KONEKTIVITAS
└── Transportasi
    ├── Jalan
    ├── Jembatan
    ├── Pelabuhan
    ├── Bandara
    ├── Terminal
    ├── Transportasi Darat
    └── Transportasi Laut
```

Data jalan sebaiknya tidak hanya menyimpan geometri, tetapi juga atribut kondisi:

```text
jenis_jalan
status_jalan
panjang
lebar
kondisi
permukaan
tahun_data
instansi_pengelola
```

## 5.2 Utilitas Dasar

```text
Utilitas Dasar
├── Air Minum
├── Sanitasi
├── Irigasi
├── Drainase
└── Persampahan
```

## 5.3 Energi

```text
Energi
├── Jaringan Listrik
├── Gardu
├── Pembangkit
└── Infrastruktur Energi Lainnya
```

## 5.4 Infrastruktur Digital

```text
Digital
├── BTS
├── Fiber Optik
├── Titik Internet
└── Infrastruktur Telekomunikasi
```

## 5.5 Infrastruktur Ekonomi

```text
Infrastruktur Ekonomi
├── Kawasan Industri
├── Pelabuhan Logistik
├── Pasar
└── Infrastruktur Pendukung Kawasan Ekonomi
```

---

# 6. Pisahkan Kondisi Eksisting dan Intervensi Pembangunan

Ini merupakan prinsip arsitektur yang penting.

Jangan mencampur pertanyaan:

> "Di mana infrastruktur berada?"

dengan:

> "Apa pembangunan yang sedang dilakukan terhadap infrastruktur tersebut?"

Contoh:

## Kondisi Eksisting

```text
INFRASTRUKTUR
└── Jalan
    ├── Jalan Nasional
    ├── Jalan Provinsi
    ├── Jalan Kabupaten
    └── Jalan Desa
```

## Intervensi Pembangunan

```text
PEMBANGUNAN
└── Jalan
    ├── Pembangunan Baru
    ├── Peningkatan
    ├── Rehabilitasi
    └── Pemeliharaan
```

Dengan struktur tersebut, MARIMOI dapat menggabungkan:

```text
Jalan Eksisting
      +
Proyek Jalan
      +
Musrenbang
      +
Pokir DPRD
      +
Aspirasi Masyarakat
      ↓
Analisis Kebutuhan Infrastruktur
```

Pendekatan ini akan lebih berguna untuk perencanaan dibandingkan hanya menampilkan layer secara terpisah.

---

# 7. Kawasan & Potensi Wilayah

Kelompok ini berisi karakteristik ruang dan potensi ekonomi wilayah.

```text
KAWASAN & POTENSI WILAYAH
├── Permukiman
├── Pertanian
├── Perkebunan
├── Perikanan
├── Pertambangan
├── Industri
├── Pariwisata
├── Kawasan Strategis
└── Kawasan Konservasi
```

Kelompok ini dapat menjadi data pendukung analisis pembangunan.

Contoh:

```text
Kawasan Industri
      +
Pelabuhan
      +
Jaringan Jalan
      +
Jaringan Listrik
      ↓
Analisis Kesiapan Infrastruktur Kawasan Industri
```

---

# 8. Layanan Dasar & Fasilitas Publik

Kelompok ini digunakan untuk menganalisis akses masyarakat terhadap layanan dasar.

```text
LAYANAN DASAR & FASILITAS PUBLIK
├── Pendidikan
├── Kesehatan
├── Pemerintahan
├── Pasar
├── Fasilitas Sosial
└── Fasilitas Ekonomi
```

Contoh analisis:

```text
Penduduk
   +
Fasilitas Kesehatan
   +
Jaringan Jalan
   +
Waktu/Jarak Tempuh
   ↓
Analisis Aksesibilitas Kesehatan
```

---

# 9. Pembangunan & Intervensi

Kelompok ini menjadi penghubung antara perencanaan dan kondisi spasial.

```text
PEMBANGUNAN & INTERVENSI
├── Proyek Strategis Daerah
├── Proyek Strategis Nasional
├── Program Prioritas Daerah
├── Musrenbang
├── Pokok Pikiran DPRD
├── Aspirasi Masyarakat
└── Infrastruktur Terbangun
```

Setiap objek pembangunan sebaiknya memiliki atribut minimal:

```text
id
nama
kategori
sektor
lokasi
kabupaten_kota
kecamatan
tahun
status
target
progress_fisik
target_keuangan
realisasi_keuangan
instansi_pengelola
sumber_data
tanggal_update
geometry
```

Dengan demikian, satu proyek dapat ditampilkan sekaligus pada:

- Peta;
- Dashboard;
- Statistik;
- Analisis wilayah;
- Analisis tren.

---

# 10. Lingkungan & Risiko

Kelompok ini menjadi data pembatas dan faktor risiko pembangunan.

```text
LINGKUNGAN & RISIKO
├── Rawan Banjir
├── Rawan Longsor
├── Rawan Bencana
├── Tutupan Lahan
├── DAS
├── Kawasan Lindung
└── Kawasan Konservasi
```

Kelompok ini penting untuk analisis pembangunan berkelanjutan.

Contoh:

```text
Proyek Infrastruktur
        +
Peta Risiko Bencana
        +
Kawasan Lindung
        ↓
Analisis Risiko Lokasi Proyek
```

---

# 11. Layer Analisis

Layer analisis sebaiknya dipisahkan dari layer data mentah.

Strukturnya:

```text
ANALISIS
├── Konektivitas Wilayah
├── Aksesibilitas Layanan
├── Kesenjangan Infrastruktur
├── Sebaran Pembangunan
├── Prioritas Intervensi
└── Tren Pembangunan
```

Layer analisis merupakan **hasil pengolahan beberapa dataset**, bukan sekadar layer yang diinput secara manual.

---

# 12. Analisis Konektivitas Wilayah

Contoh input:

```text
Jalan
+
Jembatan
+
Pelabuhan
+
Bandara
+
Pusat Permukiman
```

Output:

```text
TINGKAT KONEKTIVITAS WILAYAH

Tinggi
Sedang
Rendah
```

Analisis dapat dikembangkan berdasarkan:

- kepadatan jaringan jalan;
- akses ke pusat pemerintahan;
- akses ke pusat ekonomi;
- akses ke pelabuhan;
- akses ke bandara;
- akses antarkawasan.

---

# 13. Analisis Aksesibilitas Layanan

Contoh:

```text
Penduduk
+
Fasilitas Kesehatan
+
Jaringan Jalan
+
Kondisi Jalan
+
Jarak/Waktu Tempuh
```

Output:

```text
AKSESIBILITAS KESEHATAN

Sangat Baik
Baik
Sedang
Rendah
Sangat Rendah
```

Pendekatan yang sama dapat digunakan untuk:

- pendidikan;
- kesehatan;
- pasar;
- pemerintahan;
- layanan publik lainnya.

---

# 14. Analisis Kesenjangan Infrastruktur

Ini dapat menjadi salah satu fitur analitik utama MARIMOI.

Contoh:

```text
Kepadatan Penduduk
        +
Kondisi Infrastruktur
        +
Fasilitas Publik
        +
Proyek Pembangunan
        ↓
Analisis
        ↓
Wilayah dengan
Kesenjangan Infrastruktur Tinggi
```

Contoh output:

```text
Kabupaten/Kota
    ↓
Kecamatan
    ↓
Desa/Kelurahan
    ↓
Jenis Infrastruktur
```

Dengan demikian pengguna dapat mengetahui:

- wilayah yang infrastrukturnya masih rendah;
- jenis infrastruktur yang kurang;
- apakah wilayah tersebut sudah memiliki intervensi pembangunan;
- apakah usulan pembangunan sudah masuk Musrenbang/Pokir;
- apakah sudah ada proyek berjalan.

---

# 15. Analisis Sebaran Pembangunan

Analisis ini menggabungkan:

```text
Proyek Strategis
+
Program Prioritas
+
Musrenbang
+
Pokir
+
Aspirasi
```

Kemudian ditampilkan berdasarkan:

- Kabupaten/Kota;
- Kecamatan;
- sektor;
- jenis infrastruktur;
- tahun;
- status proyek;
- sumber pembiayaan.

Contoh:

```text
SEBARAN PEMBANGUNAN 2026

Kabupaten A      ██████████
Kabupaten B      ███████
Kabupaten C      █████
Kabupaten D      ███
```

---

# 16. Analisis Prioritas Intervensi

MARIMOI dapat dikembangkan untuk mengidentifikasi wilayah yang membutuhkan perhatian lebih lanjut berdasarkan beberapa indikator.

Contoh konsep:

```text
Kesenjangan Infrastruktur
          +
Jumlah Penduduk
          +
Aksesibilitas
          +
Risiko Bencana
          +
Potensi Ekonomi
          ↓
Analisis Prioritas
          ↓
Wilayah Prioritas Intervensi
```

Catatan: hasil analisis sebaiknya ditampilkan sebagai **indikator/analisis pendukung**, bukan sebagai keputusan otomatis. Penetapan prioritas tetap memerlukan validasi dan kebijakan pemerintah daerah.

---

# 17. Dimensi Waktu untuk Analisis Tren

Agar MARIMOI dapat menjadi dashboard pembangunan, data pembangunan harus memiliki dimensi waktu.

Minimal:

```text
tahun
periode
tanggal_update
status
target
realisasi
progress_fisik
progress_keuangan
```

Contoh:

```text
2025 ───────── 2026 ───────── 2027
  │              │              │
  ↓              ↓              ↓
120 proyek     145 proyek     170 proyek

63% progres    71% progres    78% progres
```

Dari struktur tersebut dapat dibuat:

- jumlah proyek per tahun;
- nilai pembangunan per tahun;
- progres fisik per tahun;
- realisasi keuangan per tahun;
- jumlah proyek selesai;
- jumlah proyek tertunda;
- distribusi proyek per wilayah;
- distribusi proyek per sektor;
- tren usulan pembangunan.

---

# 18. Arsitektur Metadata Dataset

Setiap layer/dataset sebaiknya memiliki metadata standar.

Contoh:

```text
Dataset
├── id
├── nama
├── kategori
├── subkategori
├── sektor
├── sumber_data
├── instansi_pengelola
├── tahun_data
├── tanggal_update
├── tingkat_wilayah
├── status
└── geometry
```

Contoh dataset:

```text
Nama             : Jalan Provinsi
Kategori         : Infrastruktur
Subkategori      : Transportasi
Sektor           : PUPR
Sumber Data      : Dinas PUPR
Tahun Data       : 2026
Tanggal Update   : 15-09-2026
Status           : Aktif
```

Metadata ini penting karena dapat langsung digunakan untuk:

- informasi layer;
- filter;
- dashboard;
- audit data;
- validasi data;
- indikator freshness;
- transparansi sumber data.

---

# 19. Prinsip Data untuk Dashboard

Dashboard sebaiknya tidak mengambil data secara hard-code dari masing-masing layer.

Sebagai contoh, jangan membuat dashboard dengan logika:

```text
ambil tabel jalan
ambil tabel jembatan
ambil tabel pelabuhan
ambil tabel proyek
...
```

secara langsung pada setiap widget.

Lebih baik data memiliki klasifikasi standar:

```text
kategori = Infrastruktur
subkategori = Transportasi
tahun = 2026
```

Kemudian dashboard dapat melakukan query berdasarkan dimensi tersebut.

Contoh:

```text
kategori = Infrastruktur
subkategori = Transportasi
wilayah = Kabupaten A
tahun = 2026
```

Hasilnya dapat digunakan oleh:

- peta;
- kartu statistik;
- grafik;
- tabel;
- analisis tren.

---

# 20. Rancangan Dashboard Eksekutif

Struktur dashboard dapat diarahkan menjadi:

```text
DASHBOARD EKSEKUTIF
│
├── Ringkasan Pembangunan
│   ├── Total Proyek
│   ├── Proyek Berjalan
│   ├── Proyek Selesai
│   ├── Proyek Tertunda
│   └── Total Nilai Pembangunan
│
├── Infrastruktur
│   ├── Jalan
│   ├── Jembatan
│   ├── Pelabuhan
│   ├── Bandara
│   └── Infrastruktur Dasar
│
├── Progres
│   ├── Fisik
│   ├── Keuangan
│   └── Target vs Realisasi
│
├── Distribusi Wilayah
│   ├── Kabupaten/Kota
│   ├── Kecamatan
│   └── Sektor
│
└── Tren
    ├── Tren Proyek
    ├── Tren Anggaran
    ├── Tren Progres
    └── Tren Infrastruktur
```

---

# 21. Hubungan Layer dengan Dashboard

Arsitektur data sebaiknya menghasilkan hubungan:

```text
                 DATA SPASIAL
                      │
        ┌─────────────┼─────────────┐
        ↓             ↓             ↓
 Infrastruktur   Wilayah       Lingkungan
        │             │             │
        └─────────────┼─────────────┘
                      ↓
                DATA PEMBANGUNAN
                      │
        ┌─────────────┼─────────────┐
        ↓             ↓             ↓
     Proyek       Musrenbang      Pokir
        │             │             │
        └─────────────┼─────────────┘
                      ↓
                  ANALISIS
                      │
        ┌─────────────┼─────────────┐
        ↓             ↓             ↓
   Kesenjangan   Aksesibilitas    Tren
        │             │             │
        └─────────────┼─────────────┘
                      ↓
             DASHBOARD EKSEKUTIF
```

---

# 22. Rekomendasi UI Peta Tematik

Pada halaman `/peta-tematik`, sebaiknya jangan langsung menampilkan seluruh layer dalam satu daftar panjang.

Gunakan struktur:

```text
[ Peta Tematik ]

Cari layer...

▼ Referensi Wilayah
  □ Kabupaten/Kota
  □ Kecamatan
  □ Desa

▼ Infrastruktur & Konektivitas
  ▼ Transportasi
    □ Jalan
    □ Jembatan
    □ Pelabuhan
    □ Bandara

  ▼ Utilitas
    □ Air Minum
    □ Sanitasi
    □ Irigasi

▼ Kawasan & Potensi
  □ Permukiman
  □ Pertanian
  □ Industri
  □ Pariwisata

▼ Layanan Dasar
  □ Pendidikan
  □ Kesehatan
  □ Fasilitas Publik

▼ Pembangunan
  □ Proyek Strategis
  □ Musrenbang
  □ Pokir
  □ Aspirasi

▼ Lingkungan & Risiko
  □ Rawan Bencana
  □ Kawasan Lindung

▼ Analisis
  □ Konektivitas
  □ Aksesibilitas
  □ Kesenjangan
  □ Tren Pembangunan
```

Tambahkan:

- search layer;
- filter kategori;
- filter wilayah;
- filter tahun;
- legenda dinamis;
- metadata layer;
- tanggal pembaruan;
- sumber data.

---

# 23. Prioritas Implementasi

Tidak semua komponen perlu dikembangkan sekaligus.

## Tahap 1 — Restrukturisasi Layer

Prioritas:

1. Referensi Wilayah.
2. Infrastruktur & Konektivitas.
3. Kawasan & Potensi.
4. Layanan Dasar.
5. Pembangunan & Intervensi.
6. Lingkungan & Risiko.
7. Metadata dataset.

Tujuan:

> Membentuk struktur data dan layer yang konsisten.

---

## Tahap 2 — Penguatan Data Pembangunan

Tambahkan:

- status proyek;
- tahun;
- target;
- realisasi;
- progress fisik;
- progress keuangan;
- sumber data;
- instansi pengelola;
- tanggal update.

Tujuan:

> Membuat data spasial dapat digunakan sebagai data monitoring pembangunan.

---

## Tahap 3 — Dashboard Eksekutif

Bangun:

- ringkasan proyek;
- progres fisik;
- progres keuangan;
- sebaran pembangunan;
- statistik wilayah;
- filter sektor;
- filter tahun;
- filter kabupaten/kota.

Tujuan:

> Mengubah MARIMOI dari viewer menjadi dashboard monitoring.

---

## Tahap 4 — Analisis

Kembangkan:

- konektivitas;
- aksesibilitas;
- kesenjangan infrastruktur;
- sebaran pembangunan;
- prioritas intervensi;
- tren pembangunan.

Tujuan:

> Mengubah MARIMOI menjadi platform analisis pembangunan.

---

# 24. Arsitektur Target MARIMOI

Target jangka menengah dapat dirumuskan:

```text
                    MARIMOI
                       │
             ┌─────────┴─────────┐
             │                   │
        DATA SPASIAL         DATA PEMBANGUNAN
             │                   │
             └─────────┬─────────┘
                       ↓
                DATA TERINTEGRASI
                       │
                       ↓
                   ANALYTICS
                       │
        ┌──────────────┼──────────────┐
        ↓              ↓              ↓
   Monitoring     Dashboard       Analisis
        │              │              │
        └──────────────┼──────────────┘
                       ↓
              DECISION SUPPORT
```

Dengan arah ini, evolusi MARIMOI dapat dirumuskan sebagai:

```text
WebGIS
  ↓
Data Platform
  ↓
Monitoring Pembangunan
  ↓
Dashboard Eksekutif
  ↓
Analytical Platform
  ↓
Decision Support System
```

---

# 25. Kesimpulan

Pengelompokan layer MARIMOI sebaiknya tidak hanya dilakukan berdasarkan jenis dataset yang tersedia, tetapi berdasarkan **fungsi informasi dalam siklus pembangunan daerah**.

Struktur yang direkomendasikan adalah:

```text
1. Referensi Wilayah
2. Infrastruktur & Konektivitas
3. Kawasan & Potensi Wilayah
4. Layanan Dasar & Fasilitas Publik
5. Pembangunan & Intervensi
6. Lingkungan & Risiko
7. Analisis
```

Kelompok **Infrastruktur & Konektivitas** menjadi fokus utama, sedangkan kelompok **Pembangunan & Intervensi** menjadi penghubung antara kondisi eksisting dengan aktivitas pembangunan.

Selanjutnya, data dari berbagai kelompok dapat dikombinasikan menjadi layer analitis seperti:

```text
Konektivitas Wilayah
Aksesibilitas Layanan
Kesenjangan Infrastruktur
Sebaran Pembangunan
Prioritas Intervensi
Tren Pembangunan
```

Dengan arsitektur tersebut, MARIMOI tidak berhenti sebagai aplikasi WebGIS yang menampilkan berbagai layer, tetapi dapat berkembang menjadi **platform informasi dan analisis pembangunan daerah berbasis spasial** yang mendukung monitoring, evaluasi, dan pengambilan keputusan berbasis data.

> **Prinsip utama:** jangan hanya menambah layer; bangun hubungan antar-layer sehingga data spasial dapat menjawab pertanyaan pembangunan.
