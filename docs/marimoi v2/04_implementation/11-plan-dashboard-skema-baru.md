# Plan Implementasi: Dashboard Eksekutif — Versi Production-Ready (Pengganti `/dashboard/pembangunan`)

## Status

📝 Rencana — revisi dari draft "paralel/pratinjau" sebelumnya. Belum ada kode yang dieksekusi dari dokumen ini.

## Apa yang Berubah dari Draft Sebelumnya

Draft pertama dokumen ini (masih bisa dilihat di riwayat git) sengaja **read-only dan paralel** — halaman baru di samping `/dashboard/pembangunan`, dengan banner "ini snapshot, bisa drift dari data nyata". Itu jalan yang benar untuk "bisa dicoba cepat tanpa risiko", tapi **bukan** jalan menuju produksi: dashboard yang datanya diketahui bisa salah begitu ada input baru tidak boleh jadi dashboard utama.

Dokumen ini menggantikannya dengan rencana yang benar-benar menutup gap yang sebelumnya cuma didokumentasikan sebagai risiko:

| Aspek | Draft paralel (lama) | Versi production-ready (dokumen ini) |
| --- | --- | --- |
| Sync jalur tulis | Tidak ditutup — didokumentasikan sebagai known risk, banner "snapshot" di UI | **Ditutup pakai Observer** (Bagian A) — `CategoryController`/`DataSpatialController`/`ProjectProgressController`/`ProjectFeedbackController` tidak perlu diubah, tapi setiap simpanannya otomatis membuat/memperbarui baris kanonik |
| Filter sektor | 0% berguna (`sector_id` kosong semua), ditampilkan sebagai keterbatasan | **Crosswalk kategori→sektor** dibangun dan di-backfill (Bagian B) |
| Filter OPD | 0% berguna (`owner_opd_id` kosong semua), ditampilkan sebagai keterbatasan | **Alur verifikasi OPD** untuk 121 proyek `needs_review` (Bagian C) |
| Status akhir | Hidup berdampingan tanpa batas waktu | **Cutover eksplisit**: `/dashboard/pembangunan` dipensiunkan setelah kriteria di bawah terpenuhi (Bagian E) |

Konsekuensi penting: dokumen ini **membuka kembali** sebagian file yang diblokir keputusan "Stop migrasi kode dulu" di Prioritas 8 (`09-implementasi-penuh-database-v2.md`) — tapi dengan cara yang berbeda dari yang dihentikan waktu itu. Yang dihentikan dulu adalah **swap titik baca tanpa menutup gap titik tulis**. Di sini titik tulis (`CategoryController::store()`, `DataSpatialController::store()`, `ProjectProgressController::store()`, feedback) **tidak diubah sama sekali** — yang ditambahkan adalah Observer yang bereaksi terhadap event `created`/`updated`-nya. Ini adalah opsi "bangun Observer sinkronisasi dua arah" yang sebelumnya jadi rekomendasi utama saat keputusan itu diajukan ke user, dan sekarang dieksekusi.

## Definisi "Production Ready" (Definition of Done)

`/dashboard/pembangunan` boleh dipensiunkan **hanya jika semua ini benar**, bukan sebagian:

- [ ] Observer aktif di semua jalur tulis terkait (Bagian A) dan lulus test integrasi yang membuktikan data baru otomatis muncul di skema baru tanpa job manual.
- [ ] Crosswalk `categories` → `sectors` sudah **dikonfirmasi manusia** (Bappeda/product owner), bukan cuma usulan otomatis (Bagian B) — termasuk kategori yang ambigu/lintas-sektor punya keputusan eksplisit, bukan dibiarkan menebak.
- [ ] 121 proyek `needs_review` sudah melalui alur verifikasi OPD (Bagian C) — atau ada keputusan eksplisit produk untuk merilis dengan sisa proyek belum terverifikasi ditandai jelas di UI (bukan disembunyikan).
- [ ] Satu siklus rilis tanpa insiden berjalan dengan dashboard baru live berdampingan (paralel, permission sama) sebelum redirect dipasang — mengikuti pola observasi yang sama seperti checklist Prioritas 8.
- [ ] Regresi penuh: `php artisan test --compact` tetap di baseline gagal yang sudah diketahui, tidak bertambah.

## Bagian A — Menutup Sync-Gap Permanen Lewat Observer

### A.1 Kenapa Observer, Bukan Migrasi Big-Bang

Alternatif yang dulu ditolak user (Prioritas 8): migrasikan semua titik baca sekaligus dalam satu paket besar. Risikonya besar dan sulit di-review. Observer lebih aman karena:
- Titik tulis lama (`CategoryController::store()`/`update()`, `DataSpatialController::store()`/`update()`, `ProjectProgressController::store()`, `ProjectFeedbackController::store()`) **tidak disentuh sama sekali** — nol risiko regresi ke behavior existing yang sudah dipakai user aktif.
- Bisa diaktifkan dan ditest per model, bertahap (Category dulu, lalu DataSpatial, lalu laporan progres), bukan satu commit raksasa.
- Kalau ada bug di Observer, bisa dimatikan (`Model::flushEventListeners()` di test, atau un-register di provider) tanpa mempengaruhi jalur tulis lama sama sekali — jalur lama tetap berfungsi persis seperti sebelum Observer ada.

Catatan konvensi: codebase saat ini pakai closure inline di `AppServiceProvider::boot()` untuk reaksi event sederhana (`DataSpatial::saved(fn () => MapDataVersion::forget())`). Sinkronisasi di sini punya logika bisnis nyata (generate slug, resolve crosswalk, fallback nama) — **sengaja pakai class `Observer` biasa** (`php artisan make:observer`), bukan closure, supaya bisa ditest terisolasi dan tidak menggemukkan `AppServiceProvider`.

### A.2 `CategorySyncObserver` — `categories` → `spatial_layers`

```php
class CategorySyncObserver
{
    public function created(Category $category): void
    {
        $this->upsert($category);
    }

    public function updated(Category $category): void
    {
        $this->upsert($category);
    }

    private function upsert(Category $category): void
    {
        $mapType = MapType::where('slug', $category->type)->first();
        $parentLayer = $category->parent_id
            ? SpatialLayer::where('legacy_category_id', $category->parent_id)->first()
            : null;

        SpatialLayer::updateOrCreate(
            ['legacy_category_id' => $category->id],
            [
                'slug' => 'layer-'.$category->id.'-'.Str::slug($category->nama),
                'name' => $category->nama,
                'title' => $category->nama,
                'description' => $category->deskripsi,
                'layer_class' => $category->type === 'tematik' ? 'thematic' : 'development',
                'map_type_id' => $mapType?->id,
                'owner_user_id' => $category->user_id,
                'color' => $category->warna,
                'is_marker' => $category->is_marker,
                'thumbnail_path' => $category->gambar,
                'is_active' => $category->is_active,
                'parent_id' => $parentLayer?->id,
            ]
        );
    }

    public function deleted(Category $category): void
    {
        SpatialLayer::where('legacy_category_id', $category->id)->first()?->delete();
    }
}
```

Slug **aman dari tabrakan** karena selalu menyertakan `$category->id` (unik per baris) — pola sama persis dengan migration backfill Prioritas 1, jadi kategori lama dan baru menghasilkan slug dengan aturan identik.

### A.3 `DataSpatialSyncObserver` — `data_spatial` → `spatial_layer_features` (+ `development_projects` untuk `proyek_strategis`)

```php
class DataSpatialSyncObserver
{
    public function __construct(private readonly CategorySectorCrosswalk $crosswalk) {}

    public function created(DataSpatial $dataSpatial): void
    {
        $this->syncFeature($dataSpatial);

        if ($dataSpatial->data_type === 'proyek_strategis') {
            $this->syncProject($dataSpatial);
        }
    }

    public function updated(DataSpatial $dataSpatial): void
    {
        $this->created($dataSpatial); // updateOrCreate di dalam syncFeature/syncProject menangani keduanya
    }

    private function syncFeature(DataSpatial $dataSpatial): void
    {
        if (! $dataSpatial->kategori_id || ! $dataSpatial->geom) {
            return; // sama seperti syarat WHERE di migration backfill Prioritas 2 — jangan buat feature tanpa layer/geometri
        }

        $layer = SpatialLayer::where('legacy_category_id', $dataSpatial->kategori_id)->first();
        if (! $layer) {
            return; // CategorySyncObserver seharusnya sudah membuatnya; kalau belum, skip aman (bukan error keras)
        }

        SpatialLayerFeature::updateOrCreate(
            ['legacy_data_spatial_id' => $dataSpatial->id],
            [
                'spatial_layer_id' => $layer->id,
                'external_id' => $dataSpatial->uuid,
                'geometry' => $dataSpatial->geom,
                'attributes' => $dataSpatial->dbf_attributes,
                'created_by' => $dataSpatial->user_id,
            ]
        );
    }

    private function syncProject(DataSpatial $dataSpatial): void
    {
        DevelopmentProject::updateOrCreate(
            ['legacy_data_spatial_id' => $dataSpatial->id],
            [
                'project_code' => 'DP-'.$dataSpatial->id,
                'name' => $dataSpatial->dbf_attributes['PAKET']
                    ?? ($dataSpatial->deskripsi !== 'Data tanpa nama' ? $dataSpatial->deskripsi : null)
                    ?? 'Proyek Tanpa Nama #'.$dataSpatial->id,
                'owner_opd_id' => $dataSpatial->opd_pengelola_id,
                'sector_id' => $this->crosswalk->sectorFor($dataSpatial->kategori_id),
                'fiscal_year' => $dataSpatial->tahun ?? now()->year,
                'needs_review' => $dataSpatial->opd_pengelola_id === null,
                'created_by' => $dataSpatial->user_id,
            ]
        );
    }
}
```

Aturan nama & `needs_review` **sengaja identik** dengan migration backfill (Bagian B.4 di `09-implementasi-penuh-database-v2.md` Prioritas 5) — proyek baru harus diperlakukan dengan aturan yang sama persis dengan yang lama, bukan aturan baru yang berbeda. `budget_amount` **tidak** disinkronkan otomatis di sini (parsing `ANGGARAN` dari teks bebas terlalu berisiko untuk dijalankan real-time tanpa review — lihat alasan lengkap di migration Prioritas 5) — proyek baru akan punya `budget_amount = NULL` sampai diisi manual lewat form `development_projects` yang sudah ada (bukan bug, keputusan sadar mengikuti pola "jangan menebak dari teks bebas" yang sudah berkali-kali jadi pelajaran di proyek ini).

`CategorySectorCrosswalk` adalah service kecil yang dibangun di Bagian B.

### A.4 `ProjectProgressReportSyncObserver` dan `ProjectFeedbackSyncObserver`

Kolom `development_project_id` sudah ada di kedua tabel (dibuat Prioritas 5) — Observer di sini murni **mengisi otomatis** kolom itu saat baris baru dibuat, tidak membuat tabel/kolom baru:

```php
class ProjectProgressReportSyncObserver
{
    public function creating(ProjectProgressReport $report): void
    {
        if ($report->development_project_id === null && $report->data_spatial_id) {
            $report->development_project_id = DevelopmentProject::where('legacy_data_spatial_id', $report->data_spatial_id)->value('id');
        }
    }
}
```

Pakai event `creating` (bukan `created`), supaya kolomnya terisi **sebelum** `INSERT`, bukan lewat `UPDATE` susulan — lebih murah dan menghindari race kecil. `ProjectFeedbackSyncObserver` pola persis sama untuk `project_feedbacks`.

### A.5 Registrasi

```php
// app/Providers/AppServiceProvider.php, di dalam boot()
Category::observe(CategorySyncObserver::class);
DataSpatial::observe(DataSpatialSyncObserver::class);
ProjectProgressReport::observe(ProjectProgressReportSyncObserver::class);
ProjectFeedback::observe(ProjectFeedbackSyncObserver::class);
```

### A.6 Edge Case yang Sudah Aman Tanpa Kerja Tambahan

- **Penghapusan**: `spatial_layer_features.legacy_data_spatial_id`, `development_projects.legacy_data_spatial_id` sudah `nullOnDelete()` di level database (dibuat Prioritas 2/5) — kalau `DataSpatial` dihapus, FK otomatis `NULL` tanpa error, tidak perlu observer `deleted()` untuk `DataSpatial`. Cukup untuk `Category` karena `spatial_layers.legacy_category_id` juga `nullOnDelete()` — observer `deleted()` di A.2 sifatnya pembersihan tambahan (soft-delete layer), bukan wajib untuk mencegah FK error.
- **Update berulang**: `updateOrCreate` dengan key `legacy_*_id` (kolom unique secara implisit lewat FK+index) aman dipanggil berkali-kali — idempoten.

### A.7 Test

`tests/Feature/DataSpatialSyncObserverTest.php` (baru): buat `DataSpatial` baru lewat `DataSpatialController::store()` (bukan langsung lewat model, supaya test membuktikan jalur HTTP nyata yang dipakai admin) → assert `SpatialLayerFeature` dengan `legacy_data_spatial_id` yang sesuai langsung ada, tanpa command backfill manual. Sama untuk `CategorySyncObserverTest`, dan `ProjectProgressReportSyncObserverTest` (submit laporan lewat `ProjectProgressController::store()` → assert `development_project_id` terisi otomatis).

## Bagian B — Crosswalk Kategori Infrastruktur → Sektor

### B.1 Kondisi Nyata (diverifikasi live, 2026-09-28)

11 kategori dipakai di 121 baris `proyek_strategis`:

| `categories.nama` | Jumlah proyek | Usulan sektor (dari 5 yang sudah ada: PUPR/Kesehatan/Pendidikan/Ekonomi/Lingkungan Hidup) |
| --- | --- | --- |
| Klaster Metropolitan Sofifi | 29 | **Ambigu — lintas sektor** (klaster kawasan, bukan satu sektor tunggal). Jangan auto-assign. |
| Pembangunan Sarana Prasarana SMA, SMK, SLB | 28 | Pendidikan |
| Jalan Provinsi | 18 | PUPR |
| Klaster Pertanian, Perkebunan, dan Ketahanan Pangan | 14 | Ekonomi *(catatan: tidak ada sektor "Pertanian" tersendiri saat ini — perlu keputusan produk apakah cukup masuk Ekonomi atau perlu sektor baru)* |
| Rehabilitasi Sarana Prasarana SMA, SMK, SLB | 10 | Pendidikan |
| Proyek Strategis Nasional | 6 | **Ambigu — lintas sektor**. Jangan auto-assign. |
| Klaster Industri dan Pertambangan | 5 | Ekonomi |
| Klaster Perikanan Tangkap dan Budidaya Pesisir | 4 | Ekonomi *(atau Lingkungan Hidup — perlu keputusan)* |
| Klaster Afirmasi (transmigrasi, layanan dasar rendah) | 3 | **Ambigu — lintas sektor**. Jangan auto-assign. |
| Pengadaan Sarana Prasarana Sekolah | 2 | Pendidikan |
| Klaster Pariwisata Bahari | 2 | Ekonomi |

**Ini usulan, bukan keputusan final.** 4 dari 11 kategori (mencakup 42/121 proyek, ≈35%) sengaja ditandai ambigu — bukan karena sulit ditebak, tapi karena secara konsep memang lintas sektor (klaster kawasan gabungan beberapa urusan). Memaksa satu sektor untuk baris-baris ini akan mengulang persis kesalahan yang sudah dihindari di migration Prioritas 5 (menebak `owner_opd_id`). **Wajib direview dan dikonfirmasi Bappeda/product owner sebelum dipakai sebagai data produksi** — baik menerima usulan di atas, mengubahnya, atau memutuskan kategori ambigu tetap `sector_id = NULL` + `needs_review = true` secara permanen (opsi paling aman, konsisten dengan pola proyek ini).

### B.2 Skema: `categories.default_sector_id`

Migration baru:

```php
Schema::table('categories', function (Blueprint $table) {
    $table->foreignId('default_sector_id')->nullable()->after('type')->constrained('sectors')->nullOnDelete();
});
```

Kolom ini **bukan** kebutuhan skema lama (`categories` tetap compatibility source, tidak dipakai UI lama) — murni jembatan untuk crosswalk, supaya keputusan pemetaan tersimpan terstruktur (bisa diaudit/diubah lewat CRUD `map_types`/kategori yang sudah ada), bukan hardcode di kode.

### B.3 `CategorySectorCrosswalk` (service dipakai Observer)

```php
class CategorySectorCrosswalk
{
    public function sectorFor(?int $kategoriId): ?int
    {
        return $kategoriId
            ? Category::where('id', $kategoriId)->value('default_sector_id')
            : null;
    }
}
```

Sederhana secara sengaja — semua logika keputusan ada di data (`categories.default_sector_id`), bukan di kode, supaya perubahan pemetaan tidak butuh deploy.

### B.4 Backfill 121 Proyek Existing

**Hanya dijalankan setelah B.1 dikonfirmasi manusia.** Command baru `marimoi:backfill-development-project-sectors`:
1. Set `categories.default_sector_id` sesuai hasil konfirmasi (seed/manual lewat UI kategori).
2. `UPDATE development_projects dp SET sector_id = c.default_sector_id FROM data_spatial ds JOIN categories c ON c.id = ds.kategori_id WHERE dp.legacy_data_spatial_id = ds.id AND c.default_sector_id IS NOT NULL` — baris dengan kategori ambigu **tetap `NULL`**, tidak dipaksa.
3. Output ringkasan: berapa proyek ter-assign, berapa tetap kosong + daftar kategorinya, supaya hasilnya bisa direview lagi sebelum dianggap selesai.

**Test:** assert command idempoten (dijalankan 2x hasil sama), assert kategori tanpa `default_sector_id` tidak mengubah `sector_id` proyek terkait.

## Bagian C — Verifikasi OPD untuk 121 Proyek `needs_review`

### C.1 Kondisi

100% (121/121) proyek `needs_review = true` karena `owner_opd_id` tidak pernah diisi di data lama. Ini bukan masalah teknis yang bisa "diperbaiki" lewat migration — butuh manusia (Bappeda/OPD terkait) menetapkan pemilik tiap proyek.

### C.2 Halaman Verifikasi (kecil, baru)

`GET /dashboard/eksekutif/needs-review` — daftar `DevelopmentProject::needsReview()->get()` (scope sudah ada di model), dengan aksi assign `owner_opd_id` per baris (dropdown OPD + submit). Permission baru `development-projects.verify` (super-admin + admin-bappeda by default).

Ini **bukan** prasyarat teknis untuk Observer/crosswalk (Bagian A dan B bisa selesai tanpa ini) — tapi prasyarat untuk filter "per OPD" di dashboard eksekutif benar-benar berguna, jadi masuk sebagai bagian dari Definition of Done.

### C.3 Transparansi di Dashboard, Bukan Menyembunyikan

Sampai proses verifikasi ini tuntas (bisa berjalan bertahap, tidak harus 121 sekaligus sebelum go-live), kartu ringkasan dashboard eksekutif **wajib** menampilkan angka "X dari 121 proyek belum diverifikasi OPD" secara eksplisit — bukan menyaring proyek `needs_review` diam-diam dari hitungan, karena itu akan membuat total yang ditampilkan tidak sama dengan total proyek sebenarnya (kesalahan presentasi data yang gampang menyesatkan pengambil keputusan).

## Bagian D — Halaman Dashboard Eksekutif

Sama seperti draft sebelumnya (`ExecutiveDashboardController` sudah dibangun Prioritas 6 dengan 4 endpoint `summary`/`sektor`/`wilayah`/`tren`, sudah punya test), dengan 3 perubahan dari versi paralel:

1. **Endpoint opsi filter baru** (`filterOptions()`), sama seperti draft lama — tidak berubah.
2. **Banner "snapshot" dihapus**, diganti indikator verifikasi dari Bagian C.3 (karena setelah Observer aktif, data memang real-time, bukan snapshot lagi).
3. Route halaman `GET /dashboard/eksekutif` tetap `permission:dashboard.view` seperti draft lama.

View, pola fetch 4 endpoint + Chart.js, dan konvensi `@extends('backend.partials.main')` mengikuti `dashboard-pembangunan.blade.php` — detail sama seperti draft sebelumnya, tidak diulang di sini.

**Test:** `DashboardEksekutifPageTest` — sama seperti draft lama, ditambah assert bahwa setelah `DataSpatialController::store()` dipanggil (bukan langsung lewat model), `summary()` langsung mencerminkan proyek baru tanpa command backfill manual — ini yang membuktikan "production ready", bukan cuma "halaman jalan".

## Bagian E — Cutover: Pensiunkan `/dashboard/pembangunan`

Dijalankan **hanya setelah** semua item Definition of Done tercentang:

1. `Route::redirect('/dashboard/pembangunan', '/dashboard/eksekutif')` — pola sama seperti redirect halaman lama ke `/peta-tematik` yang sudah ada di `routes/web.php`.
2. `PembangunanDashboardController` ditandai `@deprecated` di PHPDoc (pola sama seperti `Category`/`DataSpatial` di Prioritas 8) — tidak langsung dihapus, tetap ada untuk audit historis.
3. Audit permission: user dengan `project-progress.view` (dipakai rute lama) tapi tanpa `dashboard.view` (dipakai rute baru) akan kena 403 setelah redirect — **wajib** dicek dan diselaraskan (beri `dashboard.view` ke role yang sama, atau route baru terima kedua permission) sebelum cutover, bukan ditemukan setelah user komplain.
4. Setelah 1 siklus rilis tanpa insiden (sama seperti kriteria observasi Prioritas 8 di `09-implementasi-penuh-database-v2.md`): evaluasi apakah `PembangunanDashboardController`/view-nya perlu benar-benar dihapus atau cukup dibiarkan sebagai kode mati terdokumentasi.

## Fase Implementasi & Urutan Eksekusi

Urutan wajib — jangan lompat, tiap fase menutup fase sebelumnya:

| Fase | Isi | Bergantung pada |
| --- | --- | --- |
| 1 | `CategorySyncObserver` + test | — |
| 2 | `DataSpatialSyncObserver` (tanpa bagian `syncProject` dulu) + test | Fase 1 (butuh `SpatialLayer` sudah ada) |
| 3 | `categories.default_sector_id` migration + review manusia atas usulan B.1 + `CategorySectorCrosswalk` | — (paralel dengan Fase 1-2) |
| 4 | Aktifkan `syncProject` di `DataSpatialSyncObserver` + `marimoi:backfill-development-project-sectors` untuk 121 proyek lama | Fase 2 + Fase 3 selesai dikonfirmasi |
| 5 | `ProjectProgressReportSyncObserver` + `ProjectFeedbackSyncObserver` + test | Fase 4 |
| 6 | Endpoint `filterOptions` + halaman `/dashboard/eksekutif` + test | Fase 1-5 (supaya datanya sudah real-time saat halaman dicoba) |
| 7 | Halaman verifikasi OPD (Bagian C.2) | Bisa paralel dengan Fase 6 |
| 8 | QA manual penuh + 1 siklus observasi | Fase 1-7 |
| 9 | Cutover (Bagian E) | Fase 8 tuntas tanpa insiden |

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Observer gagal diam-diam (exception ditelan, data tetap tidak sinkron tapi tidak ketahuan) | Observer **tidak** boleh membungkus logic dalam try/catch yang menelan error — biarkan exception naik di lingkungan dev/staging supaya ketahuan saat test; untuk produksi, log eksplisit + alert, bukan silent fail |
| Observer memperlambat request `store()` (query tambahan di jalur sinkron) | Untuk skala data proyek ini (ratusan, bukan jutaan baris per hari) dampaknya diabaikan; kalau nanti volume naik signifikan, pindah ke queued listener adalah langkah lanjutan terpisah, bukan bagian dokumen ini |
| Crosswalk kategori ambigu dipaksa auto-assign oleh developer yang terburu-buru | B.1 eksplisit menandai 4 kategori ambigu (35% dari 121 proyek) — proses review wajib sebelum Fase 4 jalan, bukan default "kalau ragu, tebak saja" |
| Redirect cutover (Bagian E) membuat user dengan permission lama kena 403 mendadak | Langkah E.3 audit permission wajib sebelum redirect dipasang, dicek manual di staging dengan akun tiap role yang relevan |
| `budget_amount` proyek baru selalu `NULL` (Observer sengaja tidak parse `ANGGARAN` dari teks bebas) | Sesuai desain — proyek baru diisi lewat form terstruktur yang akan dibangun terpisah dari `DataSpatialController` lama (di luar cakupan dokumen ini), bukan lewat parsing teks bebas yang sudah terbukti berisiko |

## Testing

| Fase | File test (baru) | Skenario minimal |
| --- | --- | --- |
| 1 | `CategorySyncObserverTest` | Create/update `Category` lewat `CategoryController` → `SpatialLayer` terkait otomatis ada/ter-update, slug tidak pernah tabrakan |
| 2 | `DataSpatialSyncObserverTest` | Create `DataSpatial` lewat `DataSpatialController::store()` → `SpatialLayerFeature` otomatis ada tanpa command manual |
| 3 | `CategorySectorCrosswalkTest` | `sectorFor()` mengembalikan `null` untuk kategori tanpa `default_sector_id`, nilai benar untuk yang sudah di-set |
| 4 | `BackfillDevelopmentProjectSectorsTest` | Command idempoten; kategori ambigu tidak ter-assign; command mengubah 79/121 (yang sektor jelas), sisanya tetap `NULL` |
| 5 | `ProjectProgressReportSyncObserverTest`, `ProjectFeedbackSyncObserverTest` | Submit laporan/feedback lewat controller asli → `development_project_id` terisi otomatis tanpa command manual |
| 6 | `DashboardEksekutifPageTest` | Halaman jalan; setelah insert baru lewat controller asli, `summary()` langsung berubah (bukti real-time, bukan snapshot) |
| 7 | `DevelopmentProjectVerificationTest` | Assign `owner_opd_id` lewat halaman verifikasi → `needs_review` otomatis jadi `false` |

Regresi wajib setelah tiap fase: `php artisan test --compact` penuh, baseline gagal tidak boleh bertambah dari yang sudah diketahui (`Auth\RegistrationTest`/`AuthenticationTest`, `FeedbackStoreTest`).

## Cara Verifikasi Production-Readiness (Manual, Sebelum Cutover)

1. Login sebagai admin OPD, input data spasial baru lewat `/dashboard/data-spatial/create` (jalur lama, tidak berubah) → cek `/peta-v2` (dokumen 10) menampilkan data itu **tanpa** menjalankan command apa pun. Ini bukti Observer Fase 1-2 jalan.
2. Input proyek strategis baru lewat jalur yang sama → buka `/dashboard/eksekutif`, pastikan kartu "jumlah proyek" naik seketika.
3. Submit laporan progres lewat `/dashboard/project-progress/{uuid}` (jalur lama) → cek angka realisasi/progres di `/dashboard/eksekutif` ikut berubah tanpa command manual.
4. Buka halaman verifikasi OPD (Bagian C.2), assign satu proyek → cek badge "belum diverifikasi" di dashboard eksekutif berkurang satu.
5. Ganti filter sektor di dashboard eksekutif ke salah satu dari 5 sektor yang sudah dikonfirmasi → hasil harus cocok dengan hitungan manual lewat `mcp__laravel-boost__database-query` untuk sektor yang sama (sanity check silang, bukan percaya UI begitu saja).
6. Baru setelah 1-5 semuanya benar **dan** disetujui product owner: pasang redirect cutover (Bagian E).
