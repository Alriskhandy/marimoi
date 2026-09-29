# Perbaikan Menu & Halaman Pemetaan

## Sidebar & Menu Pemetaan

Menu "Peta Tematik" diubah namanya menjadi "Pemetaan", berisi 3 submenu:
- Daftar Layer & Data
- Jenis Layer & Data
- Feedback

## Relasi / Skema Model

Jenis → Layer → Data Spasial → Feedback

### 1. Jenis Layer / Data

Memiliki Metadata Utama (wajib) dan Metadata Dinamis (opsional):
- **Metadata Utama**: Sumber Data, OPD Penanggung Jawab, Tahun/Tanggal Data.
- **Metadata Dinamis**: placeholder (Pagu, Realisasi Anggaran, Realisasi Fisik, Status) dan custom (nama atribut, nilai, satuan).

Metadata adalah data yang diisi saat menambahkan jenis layer baru, sehingga pada pembuatan layer baru, pengguna tinggal memilih jenis yang sudah ada atau membuat jenis layer baru. Dengan begitu, layer dan data spasial yang ditambahkan memiliki metadata yang seragam sesuai jenis peta yang dibuat — lebih fleksibel.

- 1 Jenis dapat memiliki banyak Layer
- 1 Layer memiliki 1 Jenis

### 2. Layer

- Memiliki informasi parent-child untuk mengatur struktur tree pada sidebar di tampilan peta.
- Memiliki konfigurasi style (warna, ikon, opacity).

- 1 Layer dapat memiliki banyak Data Spasial
- 1 Data Spasial memiliki 1 Layer

### 3. Data Spasial

- Memiliki informasi geometri.
- Memiliki atribut default.
- Dapat memiliki gambar.

### 4. Feedback

- Umpan balik dari masyarakat pada 1 Data Spasial atau 1 Layer.
