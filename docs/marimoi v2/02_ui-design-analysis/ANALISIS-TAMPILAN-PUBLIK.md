# Analisis Tampilan & Penyajian Informasi — Marimoi (Publik)

Dokumen ini menganalisis bagaimana halaman publik (non-admin, dapat diakses tamu) Marimoi menyajikan informasi kepada pengunjung, berdasarkan `routes/web.php` beserta controller dan view terkait.

## 1. Peta Situs Publik

| Route | Controller | Halaman |
|---|---|---|
| `/` | `FrontendController@indexDark` | Beranda |
| `/profil-reformer` | `FrontendController@reformer` | Profil Reformer Birokrasi |
| `/proyek-strategis-daerah` | `FrontendController@psd` | Peta PSD |
| `/proyek-strategis-nasional` | `FrontendController@psn` | Peta PSN |
| `/prioritas-daerah` | `FrontendController@prioritas` | Prioritas Daerah 2025–2029 |
| `/peta-tematik` | `FrontendController@tematik` | Peta Tematik |
| `/usulan-musrenbang` | `FrontendController@musrenbang` | Peta Usulan Musrenbang |
| `/pokir-dprd` | `FrontendController@pokir` | Peta Pokir DPRD |
| `/dokumen-publikasi` | `PublicationController@index`* | Dokumen Publikasi |
| `/aspirasi-masyarakat` | `FrontendController@aspirasi` | Form Aspirasi Masyarakat |
| `/{grup}/{id}` (PSD, PSN, RPJMD, Pokir, Musrenbang) | `FrontendController@detailPeta` | Detail proyek/kegiatan |
| `/peta-tematik/{id}` | `FrontendController@detailPetaTematik` | Detail layer tematik |
| `/syarat-ketentuan`, `/kebijakan-privasi` | closure di routes | Halaman legal statis |

\* Catatan: `tampil.publikasi` didaftarkan **dua kali** dengan nama route yang sama — sekali di `FrontendController::publikasi()` (baris atas `web.php`) dan sekali lagi via grup `Route::prefix('dokumen-publikasi')` yang memanggil `PublicationController@index`. Karena URI yang sama, registrasi kedua yang menang; `FrontendController::publikasi()` menjadi **dead code** yang tidak pernah tereksekusi.

Seluruh halaman publik menggunakan satu layout: `frontend/layouts/dark.blade.php`. Layout lama (`layouts/main.blade.php`, `partials/navbar.blade.php`, dll., tema terang/Bootstrap) sudah tidak dipakai controller manapun — peninggalan desain sebelumnya.

## 2. Struktur Navigasi & Layout

- **Head**: meta SEO/OG/Twitter lengkap + `JSON-LD GovernmentOrganization`, font Poppins/Inter, tema gelap (`main-dark.css`).
- **Navbar** (dark-glass, fixed): Beranda, Profil Reformer, Peta Tematik, dropdown "Proyek Strategis" (Daerah/Nasional), Prioritas Daerah, Musrenbang, Pokir DPRD, Publikasi, Aspirasi. Status aktif ditentukan lewat `request()->routeIs(...)`.
- **Hero**: hanya ada di Beranda — video full-bleed autoplay + mockup tablet/HP dengan efek tilt (IntersectionObserver). Halaman lain memakai band judul sederhana sebagai pengganti hero.
- **Footer** (`footer-dark-tailwind.blade.php`, dipakai di semua halaman): video YouTube tertanam, info kontak Bappeda, sosial media, tautan Kebijakan Privasi/Syarat & Ketentuan, kredit developer, dua tombol mengambang (back-to-top & shortcut survei), serta modal "Survey Kepuasan Pengguna" yang muncul otomatis 5 detik setelah kunjungan pertama (disimpan via `localStorage`).
- **Bug ditemukan**: tombol survei mengambang dan modal survei mengarah ke `/survey`, tetapi grup route `survey.*` **dikomentari** di `web.php` — tautan ini saat ini 404.

## 3. Beranda (`/`) — Penyajian Informasi

Beranda dirakit dari beberapa section (`resources/views/frontend/pages/index-section/`):

1. **Hero** — video background, judul MARIMOI, CTA "Jelajahi Platform".
2. **Running text** — marquee logo mitra (BAPPEDA Malut, Opendata Malut, BPS, BAPPENAS), animasi `requestAnimationFrame`.
3. **Peta Tematik carousel** — Swiper carousel dari data `Category` (`type=tematik`, aktif, punya gambar). Tombol "Lihat Peta" mengirim `POST` ke `post.tematik/{id}` yang menyimpan kategori terpilih ke session lalu redirect ke `/peta-tematik`.
4. **Fitur Utama** — tautan ke PSD/PSN/Prioritas/Musrenbang/Pokir, plus model 3D `<model-viewer>` (GLB) gedung kantor gubernur.
5. **Indikator Pembangunan** — slideshow paralaks 5 gambar (Indeks Pengembangan Wilayah, SPBE, Pelayanan Publik, dll.) — **konten statis**, tidak terhubung ke data indikator riil.
6. **Aspirasi & Statistik** — blok counter `$totalUsulan` dan `$totalKritik` (dari model `Aspirasi`), CTA ke `/aspirasi-masyarakat`, dan panel "Statistik Pengunjung" (hari ini/minggu/bulan/total, dari model `Visitor`, diisi middleware `TrackVisitor` yang melakukan geolokasi IP via ip-api.com).
7. **Dukungan, About, Logo Section (EVP/BerAKHLAK/Jargon), FAQ (accordion statis), Filosofi (slider makna logo)** — seluruhnya konten marketing statis.

Catatan: ada blok kode dummy alternatif untuk carousel Peta Tematik yang di-comment (dead code, tidak dirender).

## 4. Halaman Peta (PSD / PSN / Musrenbang / Pokir / Peta Tematik)

Empat halaman (PSD, PSN, Musrenbang, Pokir) dan halaman Peta Tematik berbagi **view yang sama** (`frontend/pages/peta.blade.php`) — perbedaannya murni ditentukan di sisi client lewat `window.location.pathname` (`map.js::getDataType()`), bukan parameter controller.

**Cara kerja penyajian data:**
- View berisi shell Leaflet kosong (`#map`) + sidebar (Layer, Basemap, Legend, Layer Tools, Download).
- Semua data spasial dimuat lewat AJAX ke endpoint `GET /geojson` (`FrontendController@getGeojsonByDataType`), yang mem-join `data_spatial` ⋈ `categories` dan mengembalikan `ST_AsGeoJSON` (PostGIS) sebagai `FeatureCollection`.
- Endpoint mendukung filter kaya: `type`, `sub_type`, `year`, `kategori[]`, `bbox` (spatial intersect), `search` (ILIKE nama/deskripsi/atribut DBF), `dbf_filter`, `limit` (maks 3000, default 500), `offset`; serta mode `metadata_only=true` untuk memuat hierarki kategori 3 level + jumlah data per kategori (mengisi sidebar Layer tanpa memuat geometri).
- **Basemap**: OSM, beberapa layer Esri (Streets/Topographic/Oceans/World Imagery/Dark & Light Gray), Google (Roadmap/Hybrid/Terrain).
- **Marker**: pakai `L.markerClusterGroup` bila kategori bertipe marker, atau `L.layerGroup` biasa untuk garis/poligon. Ikon marker kustom dari `leaflet.extra-markers`.
- **Popup**: nama kategori, thumbnail (jika ada), subset atribut DBF terpilih (`KEGIATAN`, `TAHUN`, `KABUPATEN`, `URUSAN`), statistik geometri terhitung (panjang garis dalam km, atau titik tengah lat/lng), tombol "Zoom To" dan "Detail" (tautan ke halaman detail via `uuid`).
- **Sidebar Unduhan**: daftar `Dokumen::all()` yang bisa diunduh langsung dari storage.

## 5. Halaman Detail Proyek/Kegiatan

`/proyek-strategis-daerah/{id}`, `/proyek-strategis-nasional/{id}`, `/pokir-dprd/{id}`, `/usulan-musrenbang/{id}`, `/rpjmd/{id}` → `FrontendController@detailPeta`; `/peta-tematik/{id}` → `detailPetaTematik`.

- Lookup sebenarnya berdasarkan **`uuid`**, bukan `id` numerik (nama parameter route menyesatkan).
- Setiap kunjungan menambah counter `views` pada `DataSpatial`.
- Layout dua kolom: peta Leaflet baru (`#map-detail`, basemap Esri World Imagery) menggambar satu geometri (Point/LineString/Polygon) + gambar (jika ada), lalu tabel atribut (`KATEGORI`, `DESKRIPSI` — disanitasi via `HtmlSanitizer::clean()`, dan seluruh `dbf_attributes` lainnya).
- **Form Tanggapan Kegiatan** hanya muncul jika `data_type != 'tematik'` — masuk akal karena layer tematik bukan objek yang "bisa ditanggapi" seperti proyek/kegiatan.

## 6. Dokumen Publikasi (`/dokumen-publikasi`)

- `PublicationController@index` mem-paginate `Publication` (12/halaman), mendukung filter `category` dan `search` (title/description) — **namun view Blade tidak pernah merender kontrol filter maupun tautan pagination**, sehingga fitur ini secara efektif tidak dapat diakses dari UI (halaman ke-2+ tak terjangkau).
- Tampilan: grid kartu responsif (3/2/1 kolom) — thumbnail cover, judul, badge jumlah unduhan/ukuran/tipe file.
- **Unduhan bergerbang survei**: modal unduhan meminta nama, email, telepon, organisasi, jabatan, tujuan + hCaptcha sebelum file diberikan. Endpoint `POST /dokumen-publikasi/{publication}/download` mencatat data survei (`PublicationDownload`), menaikkan `download_count`, lalu men-stream file.

## 7. Formulir Interaktif

| Formulir | Endpoint | Validasi Kunci | Catatan Penyajian |
|---|---|---|---|
| Tanggapan Kegiatan | `POST /feedback-send` | hCaptcha wajib; foto laporan wajib hanya jika `jenis_tanggapan = keluhan` | Auto-isi lokasi via geolocation browser; mengirim email ke pelapor & OPD terkait |
| Aspirasi Masyarakat | `POST /aspirasi-masyarakat` | hCaptcha wajib; tipe `usulan` wajib kategori + titik peta + lampiran file; tipe `kritik & saran` lebih longgar | Nomor tiket otomatis; peta klik-untuk-pin dengan geolocation akurasi tinggi (2 percobaan); 3 email terkirim (warga, admin, OPD) |
| Unduh Publikasi (gerbang survei) | `POST /dokumen-publikasi/{id}/download` | hCaptcha + nama/email/tujuan (min 10 karakter) | Respons bisa JSON (error) atau file biner, ditangani via `fetch`+`Blob` di client |

Ketiganya berbagi pola yang sama namun **diimplementasikan terpisah** (tidak ada komponen bersama): reset hCaptcha saat validasi gagal, modal loading/sukses/gagal custom per halaman, submit AJAX dengan header `X-Requested-With`.

## 8. Observasi UX & Kualitas Konten

- **Statistik hidup di beranda** (usulan/kritik, pengunjung) memberi kesan platform yang aktif digunakan — nilai tambah kredibilitas.
- **Indikator Pembangunan** di beranda murni dekoratif/statis, berpotensi menyesatkan karena terlihat seperti data resmi padahal tidak terhubung ke sumber data apa pun.
- **Halaman Prioritas Daerah** belum sesuai rencana awal (komentar controller: `// NANTINYA DIISI PETA RPJMD //`) — saat ini hanya penampil 2 gambar statis, bukan peta interaktif seperti halaman peta lainnya.
- **Fitur pencarian/filter yang sudah dibangun di backend tapi tidak terekspos di UI**: filter kategori/pencarian pada Dokumen Publikasi, serta pagination-nya.
- **Tautan mati**: tombol/modal survei kepuasan di footer mengarah ke route yang dinonaktifkan (`/survey`).
- **Duplikasi route** `tampil.publikasi` membuat satu controller method (`FrontendController::publikasi`) menjadi dead code — berisiko membingungkan saat maintenance.
- **Konsistensi peta**: lima halaman peta berbagi satu view generik yang dibedakan lewat parsing URL di JavaScript — efisien untuk reuse tapi rapuh terhadap perubahan struktur URL di masa depan.

## 9. Ringkasan Teknologi Penyajian

- **Peta**: Leaflet 1.9.4 + Leaflet.markercluster + leaflet.extra-markers (ikon kustom). Tidak ada Mapbox/OpenLayers.
- **Data spasial**: PostGIS (`ST_AsGeoJSON`, `ST_Intersects`, `ST_MakeEnvelope`) diekspos lewat endpoint `/geojson`.
- **"Chart"**: tidak ada library chart sungguhan — visual di beranda berupa angka counter dan slideshow paralaks, bukan grafik data.
- **3D**: `<model-viewer>` untuk model gedung kantor gubernur (GLB) di section Fitur Utama.
- **Anti-spam**: hCaptcha pada seluruh formulir publik (tanggapan, aspirasi, unduhan publikasi).
