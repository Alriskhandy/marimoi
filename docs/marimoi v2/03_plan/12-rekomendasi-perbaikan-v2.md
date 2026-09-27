# Rekomendasi Perbaikan dan Solusi Alternatif — Menutup Gap Inti Pengembangan V2

## Latar Belakang

Audit terhadap [`10-tindak-lanjut-review-jamil.md`](10-tindak-lanjut-review-jamil.md) (bagian A-E) dan [`../Hasil_Review_Marimoi_Jamil.md`](../Hasil_Review_Marimoi_Jamil.md) (6 rekomendasi asli) per 2026-09-24 menemukan: 4 dari 6 rekomendasi selesai dengan baik (beranda, pelacakan aspirasi, filter peta, dan secara teknis dashboard eksekutif), tapi ada **gap antara "kode ada" dan "tujuan review tercapai"** di beberapa titik spesifik, plus **1 rekomendasi (analisis tren pembangunan) belum dikerjakan sama sekali**.

Dokumen ini **bukan** plan implementasi baru dari nol — ini kumpulan rekomendasi perbaikan bertarget untuk gap yang sudah teridentifikasi, dengan solusi alternatif di titik-titik yang punya lebih dari satu cara masuk akal untuk diselesaikan. Tujuannya: memastikan inti tujuan review Jamil — *"MARIMOI menjadi dashboard pengendalian pembangunan, bukan sekadar portal informasi"* — benar-benar tercapai, bukan cuma checklist fitur yang tercentang.

## Ringkasan Temuan (Rujukan Cepat)

| Area | Gap | Bukti |
| --- | --- | --- |
| Metadata dataset (bagian A) | Tidak wajib diisi; tidak ada penanda "belum lengkap" di popup publik | `DataSpatialController.php:163-164,244-245` (`nullable`, bukan `required`); `detail-peta.blade.php:92-99` (field hilang diam-diam bila kosong) |
| Dashboard eksekutif (bagian D) | Admin-only, menu terpisah dari dashboard utama; indikator "bermasalah" 100% self-reported; risiko tabel kosong (adopsi) | `06-dashboard-eksekutif-minimum.md` Keputusan #7, #9 |
| Analisis tren pembangunan (bagian E) | Belum ada kode sama sekali | Tidak ada hasil untuk pencarian kode tren/analisis di `app/Http/Controllers/`, `resources/views/backend/` |

## Peran dan Proses Bisnis per Role

Sebelum masuk ke rekomendasi teknis, perlu jelas dulu **siapa melakukan apa** — beberapa rekomendasi di bawah (terutama Prioritas 3 dan 6) baru masuk akal kalau proses bisnis tiap role dipetakan eksplisit, bukan diasumsikan.

| Role | Siapa (dunia nyata) | Proses bisnis utama | Tanggung jawab data | Akses sistem saat ini | Frekuensi pemakaian wajar |
| --- | --- | --- | --- | --- | --- |
| `super-admin` | Tim IT/pengelola sistem | Kelola role, permission, user, konfigurasi sistem | Tidak ada data domain — murni administrasi sistem | Semua permission (`PermissionSeeder` sync otomatis) | Jarang — hanya saat setup/insiden |
| `admin-bappeda` | Staf Bappeda (badan perencana pusat) | Koordinasi lintas-OPD: kelola kategori peta, tanggapi feedback publik, kelola OPD & user, **dan** bisa input/lihat progres proyek OPD manapun (peran pengawas) | Master data kategori, OPD, user; data spasial lintas-OPD; progres proyek (oversight) | `data-spatial.*`, `categories.*`, `project-feedbacks.*`, `project-progress.*`, `opd.*`, `users.*`, `aspirasi.*` | Rutin — mingguan/bulanan untuk koordinasi & verifikasi |
| `admin-opd` | Operator/staf tiap dinas (PUPR, Perhubungan, dst.) | Input data spasial proyek OPD-nya, tanggapi feedback proyeknya, **input laporan progres triwulanan proyek strategis OPD-nya** | Data spasial & progres proyek **milik OPD sendiri saja** | `data-spatial.*`, `project-feedbacks.view/respond`, `project-progress.view/create/edit` (scoped OPD), `aspirasi.view/edit/export` | Rutin tapi ringan — idealnya beberapa menit tiap triwulan, bukan tugas harian |
| `user` | **Vestigial/belum ada proses bisnis jelas** | Hanya `dashboard.view` — tidak ada tanggung jawab data, tidak ada alur kerja yang memanfaatkannya | Tidak ada | `dashboard.view` saja | Tidak terdefinisi — kandidat kuat untuk direpurpose (lihat Prioritas 3) |
| Publik (guest, tanpa akun) | Masyarakat umum, termasuk **pimpinan daerah** bila tidak diberi akun khusus | Jelajah Peta Tematik, ajukan & lacak aspirasi, unduh publikasi | Tidak ada — murni konsumen | Halaman `frontend/*` tanpa login | Tidak terbatas, kapan saja |

**Temuan kunci dari pemetaan ini:** role `user` saat ini **tidak punya proses bisnis nyata** — dibuat tapi tidak dipakai alur kerja mana pun. Ini bukan sekadar role kosong yang bisa dibiarkan; ini kandidat langsung untuk dua kemungkinan: (a) dihapus/tidak dipromosikan ke user baru, atau (b) **direpurpose jadi role `pimpinan`** dengan proses bisnis yang jelas (lihat Prioritas 3). Pertanyaan #1 dari kritik ini justru menyingkap gap desain yang belum eksplisit sebelumnya: sistem sudah punya 4 role teknis, tapi cuma 3 yang benar-benar dipakai untuk proses bisnis nyata.

## Prioritas 1 — Metadata: Wajib dan Terlihat Jelas (Perbaikan Cepat)

Ini yang termurah dan paling langsung menutup celah vs kriteria selesai yang sudah ditulis sendiri di `10-tindak-lanjut-review-jamil.md` bagian A.

### 1.1 Penanda "Metadata Belum Lengkap" di Popup Publik

**Rekomendasi:** Saat `sumber_data`/`opd_pengelola_id`/`tanggal_data` kosong, `detail-peta.blade.php` menampilkan badge eksplisit ("Metadata belum lengkap — sumber data tidak tercantum") alih-alih diam-diam menghilangkan baris itu. `DataSpatial::metadata_lengkap` (accessor yang sudah ada, saat ini cuma dipakai di listing admin) tinggal dipakai ulang di view publik. Perubahan kecil, dampak langsung ke kepercayaan data yang jadi keluhan utama review Jamil.

### 1.2 Kewajiban Pengisian Metadata — 3 Opsi

| Opsi | Deskripsi | Trade-off |
| --- | --- | --- |
| **A. Wajib keras (`required`)** | Validasi `sumber_data`/`opd_pengelola_id` jadi `required` di `store()`/`update()` `DataSpatialController`. | Paling sesuai kriteria selesai asli. Risiko: admin OPD yang belum tahu sumber data pasti akan terhambat input data baru sama sekali — bisa menurunkan laju input data yang justru ingin didorong. |
| **B. Wajib bertahap (grace period)** | Wajib untuk data baru mulai tanggal tertentu (mis. mulai rilis fitur ini); data yang diinput sebelum tanggal itu tetap `nullable` dan ditandai "data lama". | Tidak mengganggu alur kerja admin yang sudah berjalan; kriteria selesai tercapai untuk data ke depan. Butuh sedikit logika tambahan (cek `created_at` vs tanggal cutoff) — tapi tidak butuh migration baru. |
| **C. Tetap opsional, kuatkan di sisi insentif/UI** (bukan validasi keras) | Tampilkan warning jelas di form input ("Data tanpa sumber akan ditandai 'belum lengkap' di halaman publik") + badge Prioritas 1.1 di publik sebagai efek jera alami — admin OPD dipengaruhi rasa "malu" data terlihat tidak lengkap secara publik, bukan dipaksa sistem. | Paling minim risiko mengganggu alur kerja, tapi tidak ada jaminan teknis; bergantung pada psikologi pengguna, bukan constraint. |

**Rekomendasi konkret: Opsi B.** Ini satu-satunya yang benar-benar menutup kriteria selesai asli ("wajib diisi") tanpa memblokir histori data yang sudah ada, dan tidak butuh keputusan sensitif soal "kapan boleh diblokir" — cukup pakai tanggal rilis fitur sebagai cutoff. Gabungkan dengan Prioritas 1.1 supaya efeknya terlihat baik untuk data baru (wajib) maupun data lama (ditandai, bukan disembunyikan).

## Prioritas 2 — Analisis Tren Pembangunan (Bagian E): Jangan Tunggu Data, Bangun untuk Data Sedikit

Bagian E secara resmi "menunggu data multi-periode dari bagian D" — tapi ini keputusan yang perlu ditinjau ulang. Menunda pembangunan fitur sampai data terkumpul menciptakan siklus: *fitur tidak dibangun karena data kosong → OPD tidak termotivasi mengisi data karena tidak ada fitur yang memanfaatkannya → data tetap kosong selamanya.*

### 2.1 Rekomendasi Utama: Bangun Sekarang, Desain untuk Data Sedikit

- Grafik tren (`Chart.js`, sudah dipakai di `dashboard-pembangunan.blade.php`) dibangun sekarang, dengan **empty state yang jujur**: bila suatu proyek/sektor baru punya 1 periode laporan, tampilkan "Belum cukup data untuk tren — minimal 2 periode laporan diperlukan" alih-alih grafik kosong atau error. Begitu OPD mulai rutin melapor (yang sudah bisa dilakukan sejak bagian D + Tahap 9 selesai), grafik otomatis terisi tanpa perlu rilis fitur baru.
- Baseline perbandingan (per catatan `05-dashboard-eksekutif.md`) dimulai dari yang paling sederhana dan tidak butuh data historis sama sekali: **realisasi vs pagu tahun berjalan** (sudah ada di kartu bagian D) sebagai baseline pertama, baru **periode-ke-periode** begitu ada ≥2 periode, baru **tahun-ke-tahun** begitu ada ≥2 tahun anggaran. Bertingkat, bukan semua-atau-tidak-sama-sekali.

### 2.2 Solusi Alternatif: Tren Interim dari Data yang Sudah Ada

Selagi `project_progress_reports` masih tipis, `DashboardController::getMonthlyAspirasiData()` (statistik aspirasi bulanan) sudah berjalan dan punya data historis nyata. Tambahkan **satu grafik interim** di dashboard pembangunan: tren volume aspirasi terkait infrastruktur per bulan, sebagai proxy sinyal "wilayah mana yang sering dikeluhkan" — bukan pengganti tren capaian fisik/keuangan yang sesungguhnya, tapi memberi nilai analitik nyata dari hari pertama, tanpa menunggu adopsi bagian D. Bisa dilepas begitu tren pembangunan asli (2.1) sudah punya cukup data untuk berdiri sendiri.

### 2.3 Definisi Baseline yang Perlu Dikunci (mengikuti catatan `05-dashboard-eksekutif.md`)

- **Periode:** kuartalan (`Triwulan 1-4`, konsisten dengan `periode_laporan` yang sudah ada).
- **Baseline default:** periode sebelumnya dalam tahun anggaran yang sama; jatuh ke "target tahunan" (pagu) bila periode sebelumnya tidak ada.
- **Aturan perubahan:** naik/turun ditampilkan sebagai persentase poin (bukan persentase relatif) untuk progres fisik, dan Rupiah + persentase untuk realisasi — supaya "naik 5%" tidak ambigu antara 5 poin persentase vs 5% dari nilai sebelumnya.

## Prioritas 3 — Dashboard Eksekutif untuk Pimpinan Daerah: Publik, Bukan Login

**Jawaban tegas untuk pertanyaan #2:** effort paling rendah berarti **pimpinan daerah tidak perlu login sama sekali** untuk kasus pemakaian utamanya (lihat gambar cepat kondisi pembangunan — di rapat, lewat tautan yang dibagikan staf, atau ditampilkan di layar kantor Bappeda). Login masih relevan untuk role yang memang punya tanggung jawab *input* data (admin-bappeda, admin-opd — lihat tabel role di atas), tapi memaksa pimpinan (peran murni *konsumen* informasi) login untuk sekadar melihat angka bertentangan langsung dengan tujuan "effort paling rendah". Rekomendasi Opsi B versi dokumen sebelumnya (role `pimpinan` read-only berbasis login) **didowngrade jadi fallback**, bukan solusi utama — alasannya di bawah.

### 3.1 Rekomendasi Utama: Halaman Publik Agregat, Digerbangi Verifikasi

Halaman baru di `frontend/*` (bukan `/dashboard/*`), tanpa login, menampilkan **agregat** dashboard pembangunan: kartu ringkasan (jumlah proyek, persen realisasi, rata-rata progres fisik) dan grafik tren (begitu Prioritas 2 selesai) — reuse logika query yang sudah ada di `PembangunanDashboardController::index()`, tinggal dibatasi ke laporan yang **sudah terverifikasi** (lihat 3.2) dan diringkas jadi agregat, bukan tabel mentah per-laporan.

Ini bukan permukaan paparan baru dari nol — Peta Tematik publik (`detail-peta.blade.php`) **sudah** menampilkan detail per-proyek ke publik (nama, lokasi, metadata, kategori). Halaman baru ini menambah **lapisan agregat/tren di atas** apa yang sudah publik, bukan membuka data yang sebelumnya tertutup.

### 3.2 Menjawab Pertanyaan Keamanan: Apa yang Aman Dipublikasikan, Apa yang Tidak

| Data | Publik? | Alasan |
| --- | --- | --- |
| Kartu ringkasan agregat (total proyek, % realisasi, rata-rata progres fisik) | ✅ Ya | Angka ringkas, tidak mengidentifikasi individu, sudah jadi semangat transparansi MARIMOI. |
| Grafik tren per sektor/wilayah | ✅ Ya | Sama seperti di atas — agregat, bukan detail transaksi. |
| Tabel per-proyek dengan pagu/realisasi presisi | ⚠️ Hati-hati | Aman secara prinsip (uang publik), tapi **hanya setelah terverifikasi** (lihat 3.3) — angka self-report OPD yang belum dicek dan langsung tampil publik berisiko jadi kesalahan yang sulit ditarik kembali begitu terlanjur disebarluaskan/screenshot. |
| `catatan` (teks bebas admin) di laporan progres | ❌ Tidak | Bisa memuat komentar internal, nama kontraktor, atau catatan sensitif yang tidak dimaksudkan untuk konsumsi publik saat ditulis. |
| `dilaporkan_oleh` (nama admin yang input) | ❌ Tidak | Tidak ada manfaat publik menampilkan identitas staf yang menginput; hanya menambah risiko tanpa nilai transparansi tambahan. |
| Breakdown "proyek bermasalah" **per OPD tertentu** secara publik | 🟡 Keputusan kebijakan, bukan teknis | Berpotensi jadi sensitif secara politis (OPD tertentu terlihat "buruk" di mata publik). Rekomendasi teknis: agregat provinsi tampil publik oleh default; breakdown per-OPD yang detail tetap di balik login admin-bappeda kecuali pemilik produk (Bappeda/pimpinan) secara eksplisit memutuskan sebaliknya. **Ini perlu dikonfirmasi ke pemilik produk, bukan diputuskan sepihak oleh tim teknis.** |

### 3.3 Prasyarat: Gerbang Verifikasi (Menyatu dengan Prioritas 4)

Supaya angka publik bisa dipercaya (bukan cuma dipublikasikan), laporan progres butuh status **terverifikasi** sebelum masuk hitungan agregat publik — ini yang membuat Prioritas 4 Opsi C ("verifikasi berjenjang") naik status dari "nice-to-have" jadi **prasyarat** halaman publik, bukan lagi opsi independen. Implementasi minimum: kolom `terverifikasi_at`/`diverifikasi_oleh` (nullable) di `project_progress_reports`, diisi admin-bappeda lewat satu aksi "Verifikasi" di halaman yang sudah ada (`project-progress.show`) — tidak perlu alur approval berlapis yang rumit untuk iterasi pertama, cukup satu langkah konfirmasi.

### 3.4 Fallback: Role `Pimpinan` Read-Only (Bila Publikasi Penuh Belum Bisa Dilakukan)

Bila karena alasan kebijakan (lihat baris terakhir tabel 3.2) publikasi penuh belum bisa disetujui, repurpose role `user` yang vestigial (lihat tabel role) jadi role `pimpinan`: `dashboard.view` + `project-progress.view`, satu akun, langsung ke dashboard begitu login — masih lebih ringan dari alur admin biasa, meski tetap butuh login. Infrastruktur permission untuk ini sudah ada, jadi bisa jadi solusi sementara berbiaya rendah sambil menunggu keputusan kebijakan soal 3.1.

### 3.5 Konsolidasi Menu Internal (Tetap Relevan, Independen dari 3.1-3.4)

Terlepas dari halaman publik untuk pimpinan, menu internal admin (`/dashboard/pembangunan` terpisah dari `/dashboard`) tetap sebaiknya digabung jadi satu (tab di `/dashboard` utama) — ini menjawab keluhan fragmentasi menu untuk role admin-bappeda/admin-opd yang memang harus login, independen dari solusi pimpinan di atas.

**Rekomendasi urutan:** 3.3 (gerbang verifikasi) harus jalan duluan sebagai fondasi → 3.1 (halaman publik) menyusul begitu gerbang siap → 3.5 (konsolidasi menu internal) bisa paralel, tidak bergantung pada yang lain → 3.4 (role pimpinan) hanya bila 3.1 diputuskan tidak bisa dilakukan penuh karena pertimbangan kebijakan.

## Prioritas 4 — Indikator "Bermasalah" yang Tidak 100% Bergantung Self-Report

Definisi saat ini (`status = 'terlambat'`, diisi manual) rentan bias — admin OPD yang proyeknya bermasalah punya insentif untuk tidak menandainya "terlambat". **Sejak Prioritas 3 direvisi, ini bukan lagi perbaikan kualitas yang berdiri sendiri** — Opsi C di bawah (verifikasi berjenjang) adalah gerbang yang membuat halaman publik pimpinan daerah aman dipublikasikan (lihat 3.3).

| Opsi | Deskripsi | Trade-off |
| --- | --- | --- |
| **A. Ganti total ke otomatis** | Hitung "bermasalah" dari formula (mis. realisasi anggaran jauh di bawah proporsi waktu berjalan tahun anggaran). | Butuh model kalender fiskal (belum ada); berisiko heuristik yang salah "menuduh" proyek yang sebenarnya sehat (mis. proyek dengan pembayaran termin besar di akhir tahun). |
| **B. Dual-track (rekomendasi)** | Status manual tetap ada (keputusan akhir tetap milik admin — konsisten dengan Keputusan #7 sebelumnya), **tapi** tambahkan indikator sistem terpisah di sisi status manual: "Sistem: realisasi 20%, tahun anggaran sudah berjalan 75%" sebagai sinyal, bukan vonis. | Biaya implementasi rendah (murni perhitungan `realisasi_anggaran/pagu` vs proporsi bulan berjalan dalam tahun kalender — tidak butuh kalender fiskal rumit). Transparan: ketidaksesuaian antara status manual dan sinyal sistem justru jadi bahan tanya-jawab yang berguna buat bappeda. |
| **C. Verifikasi berjenjang** | Laporan progres OPD butuh persetujuan admin-bappeda sebelum masuk hitungan dashboard. | Solusi governance, bukan solusi teknis murni — efektif tapi menambah friksi alur kerja OPD dan butuh keputusan organisasi (SK/SOP), bukan sekadar kode. |

**Rekomendasi konkret: kombinasi B + C, bukan salah satu saja.** Opsi C (verifikasi admin-bappeda) jadi gerbang yang menentukan status resmi/publik laporan — wajib ada begitu Prioritas 3.1 (halaman publik) dikerjakan. Opsi B (sinyal sistem otomatis) tidak menggantikan verifikasi manusia, tapi jadi **alat bantu** admin-bappeda saat memverifikasi — ditampilkan di layar verifikasi sebagai pembanding cepat ("Admin lapor: on track. Sistem: realisasi baru 20%, tahun berjalan sudah 75%") supaya verifikasi tidak perlu menghitung manual satu-satu. Kombinasi ini lebih murah daripada Opsi C sendirian (yang tanpa bantuan sinyal sistem akan lambat diverifikasi manual) dan lebih aman daripada Opsi B sendirian (yang tanpa gerbang verifikasi tidak benar-benar mencegah data salah tampil publik).

## Prioritas 5 — Solusi Non-Teknis: Adopsi Data adalah Risiko Terbesar

Ini bagian yang paling sering terlewat dalam plan berbasis kode murni. Dashboard bagian D sudah lengkap secara teknis, tapi **nilainya nol kalau tabelnya kosong**. Beberapa langkah, campuran teknis-ringan dan organisasi:

1. **Kartu "Kelengkapan Pelaporan"** di dashboard pembangunan — persentase proyek yang sudah melapor untuk periode berjalan vs yang belum (mis. "12 dari 20 proyek sudah melapor Triwulan 3"). Biaya implementasi kecil (query yang sudah ada di `PembangunanDashboardController` tinggal dibalik), efeknya besar: visibilitas kelengkapan data jadi akuntabilitas terbuka antar-OPD, bukan cuma metrik pembangunan.
2. **Notifikasi pengingat** — email/notifikasi ke admin-opd yang belum mengisi laporan progres mendekati akhir periode (triwulan). Bisa dibangun di atas infrastruktur mail yang sudah ada (`TanggapanMail` sudah jadi preseden pengiriman email dari sistem).
3. **Bukan tugas kode: SK/surat edaran Bappeda** yang mewajibkan OPD melaporkan progres proyek strategis tiap triwulan lewat MARIMOI — tanpa mandat organisasi, fitur secanggih apa pun di sisi kode tidak akan mengubah perilaku pelaporan. Ini di luar cakupan tim pengembang, tapi krusial dicatat sebagai prasyarat keberhasilan, bukan diasumsikan akan terjadi sendiri.
4. **Materi onboarding singkat** untuk admin-opd (1 halaman panduan atau video pendek) tentang cara mengisi laporan progres — mengurangi friksi adopsi awal yang biasanya jadi alasan fitur baru tidak dipakai bukan karena rumit, tapi karena tidak familiar.

## Prioritas 6 — Memaksimalkan Peran OPD dengan Effort Minimal

Pertanyaan #3: admin-opd adalah role dengan pengetahuan domain paling lengkap tapi kapasitas paling sempit — pelaporan MARIMOI adalah tugas administratif tambahan di luar pekerjaan inti mereka. Prioritas 5 (adopsi data) fokus ke **dorongan** (reminder, kelengkapan, mandat organisasi) — bagian ini fokus ke **mengurangi kerja** yang perlu mereka lakukan tiap kali melapor, karena dorongan tanpa kemudahan cuma memindahkan rasa "repot" ke rasa "terpaksa".

1. **Pra-isi dari periode sebelumnya.** Saat admin-opd membuka form laporan baru untuk proyek yang sama, `pagu` (biasanya tetap sepanjang tahun anggaran) otomatis terisi dari laporan periode terakhir proyek itu — admin tinggal mengonfirmasi atau menyesuaikan, bukan mengetik ulang dari nol. Perubahan kecil di `ProjectProgressController::create()` (ambil laporan terakhir proyek itu, kirim ke view sebagai default value).
2. **Mode "perbarui cepat" di halaman daftar proyek** (`project-progress.index`) — untuk kasus paling umum (admin cuma mau menaikkan persen progres fisik atau ganti status, tanpa mengubah angka keuangan), sediakan input ringkas langsung di baris tabel tanpa perlu buka halaman form penuh. Mengurangi jumlah klik dari "buka form lengkap → isi ulang semua field → submit" jadi "ubah satu angka di tempat → simpan".
3. **Pengingat personal, bukan cuma kartu global.** Prioritas 5.1 (kartu kelengkapan pelaporan) itu untuk visibilitas publik/bappeda; tambahan untuk admin-opd sendiri: banner personal saat login ("3 dari 5 proyek OPD Anda belum melapor Triwulan 3") — lebih actionable daripada angka global, karena langsung menunjukkan pekerjaan spesifik yang perlu dilakukan orang itu, bukan statistik umum yang harus ditafsirkan dulu.
4. **Integrasi INAPROC (`11-integrasi-inaproc.md`) sebagai pengurang effort jangka menengah-panjang.** Ini bukan pengulangan rekomendasi lama — di konteks pertanyaan #3, integrasi INAPROC berarti admin-opd **tidak perlu mengetik ulang `pagu`/`realisasi_anggaran` sama sekali** untuk proyek yang datanya sudah tersinkron dari LKPP; effort admin-opd berkurang dari mengisi 5 field jadi cuma mengonfirmasi 2 (progres fisik + status). Ini alasan kuat untuk tidak menganggap dokumen 11 sebagai "nice-to-have jangka panjang" — ia punya nilai langsung terhadap pertanyaan #3.
5. **Ide lanjutan, belum jadi prioritas sekarang:** tautan pelaporan sekali-pakai yang bisa didelegasikan ke staf lapangan tanpa akun admin penuh, atau alur input lewat WhatsApp/mobile yang lebih akrab bagi staf teknis di lapangan dibanding form web desktop. Dicatat di sini supaya tidak hilang sebagai ide, tapi butuh keputusan produk lebih jauh sebelum masuk rencana kerja (siapa yang berwenang men-delegasikan, bagaimana otentikasi tautan sekali-pakai diamankan).

## Prioritas 7 — Metadata Terstruktur, Dinamis, dan Fokus Infrastruktur

Pertanyaan #4 punya 4 tuntutan yang tampak saling tarik: **terstruktur** (bukan teks bebas) vs **dinamis** (beda kategori butuh field berbeda) vs **fokus infrastruktur** (prioritas, bukan seragam ke semua 187 kategori sekaligus) vs **kualitas terjaga** (validasi tidak boleh kendur). Kondisi saat ini: `data_spatial.dbf_attributes` adalah JSONB bebas — apa pun bisa masuk, tidak ada validasi field per kategori. Metadata hasil Prioritas 1 (`sumber_data`, `opd_pengelola_id`, `tanggal_data`) seragam untuk semua `data_type`, tidak spesifik infrastruktur.

### 7.1 Solusi: Skema Atribut per Kategori (Bukan Kolom Tetap, Bukan JSON Bebas)

Ini pola yang sudah terbukti dibutuhkan sejak [`db-schema-v2.md`](../db-schema-v2.md) bagian "Status Implementasi Saat Ini" memberi contoh konkret: data jalan idealnya punya `jenis_jalan`, `status_jalan`, `panjang`, `lebar`, `kondisi`, `permukaan` — bukan cuma nama & geometri. Field itu **tidak masuk akal** sebagai kolom tetap di `data_spatial` (kategori lain seperti sekolah butuh field sama sekali berbeda: jumlah siswa, jenjang), tapi juga **tidak boleh** jadi JSON bebas tanpa validasi (itu yang sekarang terjadi, dan itu penyebab "kualitas belum terjaga").

**Rancangan:** tambah kolom `atribut_schema` (JSON) di tabel `categories` — mendefinisikan field mana yang berlaku untuk kategori itu, tipe datanya, dan apakah wajib:

```json
[
  {"key": "jenis_jalan", "label": "Jenis Jalan", "type": "select", "options": ["Nasional", "Provinsi", "Kabupaten", "Desa"], "required": true},
  {"key": "panjang_km", "label": "Panjang (km)", "type": "number", "required": true},
  {"key": "lebar_m", "label": "Lebar (m)", "type": "number", "required": false},
  {"key": "kondisi", "label": "Kondisi", "type": "select", "options": ["Baik", "Sedang", "Rusak Ringan", "Rusak Berat"], "required": true}
]
```

Form input `data_spatial` (backend) **merender field secara dinamis** berdasarkan `atribut_schema` kategori yang dipilih, tapi nilainya tetap disimpan di `dbf_attributes` JSONB yang sudah ada — **tidak perlu migration besar atau tabel baru per kategori**. Validasi server-side mengecek nilai yang disubmit terhadap `atribut_schema` (field `required` wajib ada, `type: number` harus numerik, `type: select` harus salah satu dari `options`) sebelum simpan — ini yang menjawab "kualitas tetap terjaga": bukan validasi generik, tapi validasi spesifik per kategori yang didefinisikan sekali oleh admin-bappeda saat membuat/mengedit kategori.

### 7.2 Menjawab "Fokus ke Infrastruktur"

`atribut_schema` **tidak perlu diisi untuk semua 187 kategori sekaligus** — itu justru akan mengulang masalah lama (proyek besar, lambat selesai, admin kelelahan). Rekomendasi: rilis bertahap, dimulai dari kategori di bawah kelompok "Infrastruktur & Konektivitas" (persis kelompok yang direkomendasikan jadi prioritas di `Rekomendasi_Pengelompokan_Layer_MARIMOI.md` §5) — mis. Jalan, Jembatan dulu sebagai 2 kategori percontohan. Kategori tanpa `atribut_schema` (defaultnya kosong/null) tetap berfungsi seperti sekarang (JSON bebas, tanpa validasi tambahan) — fitur ini **aditif**, bukan migrasi paksa untuk seluruh data yang ada.

### 7.3 Kaitan dengan Prioritas 1

Pola yang sama (badge "belum lengkap" untuk metadata generik di Prioritas 1.1) bisa dipakai ulang di level atribut kategori: proyek infrastruktur yang kategorinya punya `atribut_schema` tapi field wajibnya belum terisi lengkap (mis. `panjang_km` kosong untuk data Jalan) ditandai "atribut teknis belum lengkap" — memperluas prinsip "jangan tampilkan data tidak lengkap seolah lengkap" dari metadata generik (sumber/OPD/tanggal) ke atribut teknis spesifik infrastruktur.

## Prioritas 8 (Jangka Panjang) — Catatan Arsitektur, Bukan Tindakan Segera

`project_progress_reports.data_spatial_id` mengasumsikan **satu proyek strategis = satu baris `data_spatial`** (satu geometri). Ini cukup untuk sebagian besar kasus saat ini, tapi skema V2 penuh (`project_locations`, `09-ringkasan-konsep-dan-alur.md`) sengaja memisahkan "identitas proyek" dari "lokasi proyek" justru karena proyek infrastruktur riil (mis. jalan yang melintasi banyak ruas/segmen) sering tidak bisa direpresentasikan sebagai satu geometri tunggal.

**Rekomendasi:** jangan migrasi sekarang — penamaan tabel sudah disiapkan untuk reparenting murah (`data_spatial_id` → `project_location_id`) begitu waktunya tiba. Tapi **pantau** sebagai sinyal migrasi: begitu admin mulai butuh menginput 1 proyek dengan >1 geometri (dan terpaksa akal-akalan, mis. duplikasi baris `data_spatial` untuk satu proyek yang sama), itu tanda migrasi ke skema V2 penuh sudah waktunya, bukan lagi "nanti".

## Ringkasan Prioritas dan Urutan Pengerjaan

| Urutan | Item | Effort | Dampak |
| --- | --- | --- | --- |
| 1 | Prioritas 1.1 — badge "metadata belum lengkap" di publik | Kecil | Langsung menutup gap kriteria selesai bagian A |
| 2 | Prioritas 1.2 Opsi B — wajib bertahap | Kecil | Menutup sisa gap bagian A tanpa mengganggu data lama |
| 3 | Prioritas 3.5 — konsolidasi menu dashboard internal | Kecil | Langsung menjawab keluhan UX asli review untuk role admin |
| 4 | Prioritas 5.1 + 6.3 — kartu kelengkapan pelaporan (global & personal) | Kecil | Mitigasi risiko terbesar (adopsi) dengan biaya rendah |
| 5 | Prioritas 6.1 — pra-isi form dari periode sebelumnya | Kecil | Mengurangi effort OPD langsung, sebelum integrasi apa pun |
| 6 | Prioritas 3.3 — gerbang verifikasi (`terverifikasi_at`) + Prioritas 4 kombinasi B+C | Sedang | Prasyarat wajib sebelum data tampil publik; jawab pertanyaan keamanan #2 |
| 7 | Prioritas 3.1 — halaman publik agregat untuk pimpinan daerah | Sedang | Jawaban langsung untuk pertanyaan #2 — akses tanpa login |
| 8 | Prioritas 2 — tren pembangunan (dibangun untuk data sedikit) | Sedang-Besar | Menutup rekomendasi review yang paling penting dan paling terabaikan |
| 9 | Prioritas 7.1-7.2 — skema atribut per kategori, mulai Jalan/Jembatan | Sedang-Besar | Jawaban langsung untuk pertanyaan #4 — terstruktur + dinamis + fokus infrastruktur |
| 10 | Prioritas 6.2 — mode "perbarui cepat" | Sedang | Effort OPD berkurang lebih jauh setelah pola dasar (5) terbukti dipakai |
| 11 | Prioritas 5.2-5.4 — notifikasi, SK, onboarding | Campuran (sebagian di luar kode) | Prasyarat keberhasilan jangka panjang, bukan fitur |
| 12 | Prioritas 3.4 — role `pimpinan` read-only | Kecil | **Hanya bila** 3.1 tidak disetujui penuh karena pertimbangan kebijakan |
| 13 | Prioritas 6.4 — integrasi INAPROC (`11-integrasi-inaproc.md`) | Besar | Effort OPD paling minim untuk field keuangan, begitu API key tersedia |
| — | Prioritas 8 — reparenting skema V2 penuh | — | Dipantau, bukan dikerjakan sekarang |

## Definisi "Inti V2 Tercapai"

Bukan sekadar checklist 6 rekomendasi tercentang, tapi:

- Data yang ditampilkan **bisa dipercaya** — publik tahu mana data yang lengkap dan mana yang belum, bukan ditampilkan seragam; data infrastruktur teknis (panjang, kondisi, dst.) terstruktur dan tervalidasi, bukan JSON bebas tanpa aturan.
- Setiap role **punya proses bisnis yang jelas dan wajar bebannya** — admin-opd tidak merasa MARIMOI jadi beban administratif tambahan yang berat; pimpinan daerah bisa melihat kondisi pembangunan tanpa hambatan (login, navigasi menu berlapis) yang tidak perlu.
- Data yang tampil ke publik **sudah lewat gerbang verifikasi**, bukan angka self-report mentah yang berisiko jadi kesalahan publik yang sulit ditarik kembali.
- Data yang mengisi dashboard **datang dari proses yang berjalan nyata** (OPD rutin melapor, dengan effort serendah mungkin), bukan skema kosong yang menunggu adopsi yang tidak pernah terjadi.
- Tren pembangunan — rekomendasi yang justru paling menentukan nilai "pengendalian pembangunan" MARIMOI — **tidak lagi tertunda tanpa batas** menunggu prasyarat yang sirkular.

## Referensi

- [`10-tindak-lanjut-review-jamil.md`](10-tindak-lanjut-review-jamil.md) — sumber status bagian A-E yang dievaluasi di dokumen ini.
- [`../Hasil_Review_Marimoi_Jamil.md`](../Hasil_Review_Marimoi_Jamil.md) — 6 rekomendasi asli yang jadi tolok ukur.
- [`../db-schema-v2.md`](../db-schema-v2.md) — single source of truth desain database; contoh atribut teknis infrastruktur (jenis jalan, kondisi, dst.) dan taksonomi 7-kelompok yang jadi rujukan Prioritas 7.
- [`../04_implementation/03-metadata-dataset.md`](../04_implementation/03-metadata-dataset.md), [`../04_implementation/06-dashboard-eksekutif-minimum.md`](../04_implementation/06-dashboard-eksekutif-minimum.md) — implementasi yang jadi dasar temuan gap.
- [`05-dashboard-eksekutif.md`](05-dashboard-eksekutif.md) — catatan baseline/periode tren yang dirujuk di Prioritas 2.3.
- [`11-integrasi-inaproc.md`](11-integrasi-inaproc.md) — jalur tambahan pengurang beban input manual (realisasi keuangan dari sumber resmi), relevan sebagai mitigasi jangka menengah untuk Prioritas 5 dan 6 (adopsi & effort OPD) bila diimplementasikan.
