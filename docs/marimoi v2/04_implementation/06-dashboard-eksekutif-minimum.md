# Plan Implementasi: Dashboard Eksekutif Minimum (Progres Fisik, Keuangan, Status Proyek)

## Status Implementasi

- **Tahap 1–8 — selesai.** Migration, model `ProjectProgressReport` + relasi `DataSpatial::progressReports()`, permission catalog + seeder, `ProjectProgressController`, `PembangunanDashboardController`, routes, 4 view baru, menu sidebar, factory, dan 9 test baru (`ProjectProgressReportTest` 6 test + `PembangunanDashboardTest` 3 test) — seluruhnya lulus di run pertama (26 assertion). Regresi `RolePermissionTest`/`MetadataDatasetTest`/`DataSpatialGeojsonTest`/`FrontendGeojsonMetadataTest` (35 test) tetap lulus tanpa gangguan.
- Migration dijalankan terisolasi (`--path=...`) sesuai konvensi sesi ini untuk migration baru; `PermissionSeeder` dijalankan ulang di lingkungan development (`php artisan db:seed --class=PermissionSeeder`) — aman karena role `admin-bappeda`/`admin-opd` di database development ini belum punya permission tersimpan sebelumnya (kondisi `$role->permissions()->exists()` masih false), jadi permission baru langsung ter-assign. Konfirmasi ulang di lingkungan lain sesuai catatan Urutan Eksekusi langkah 11.
- **Tahap 9 — selesai (2026-09-24).** Revisi mengambil business rules dari [`../03_plan/11-integrasi-inaproc.md`](../03_plan/11-integrasi-inaproc.md) (khususnya Keputusan #8/#9 dokumen itu: satu proyek bisa punya banyak sumber angka, dan laporan progres boleh diperbarui dengan jejak audit) tapi **diimplementasikan versi manual murni** — tanpa tabel/klien/command INAPROC apa pun. Kolom `project_progress_reports.sumber_data` dan tabel `project_progress_report_revisions` sengaja dibuat generik (bukan diikat ke istilah "INAPROC") supaya saat integrasi INAPROC sungguhan dikerjakan nanti (dokumen 11), mekanisme pembaruan + audit trail ini tinggal dipakai ulang, bukan dibangun dari nol. Detail lengkap di Tahap 9 di bawah. 4 test baru lulus (total `ProjectProgressReportTest` jadi 10 test/36 assertion); regresi `PembangunanDashboardTest`/`RolePermissionTest` (29 test total) tetap lulus.

### Catatan audit saat eksekusi

- Controller memakai scope `DataSpatial::proyekStrategis()` yang **sudah ada** di model (bukan `where('data_type', 'proyek_strategis')` mentah seperti draf awal dokumen) — ditemukan saat membaca ulang `DataSpatial.php` sebelum menulis kode, lebih konsisten dengan scope lain (`scopeProyekStrategisDaerah`, dst.) yang sudah dipakai di codebase.
- Test awalnya memakai `$role->givePermissionTo(['project-progress.view'])` (nama string langsung) — diperbaiki sebelum eksekusi jadi `Permission::firstOrCreate([...])` dulu baru `givePermissionTo()`, karena `TestCase` dasar proyek tidak auto-seed permission dan Spatie melempar `PermissionDoesNotExist` bila permission belum ada di DB (pola ini dikonfirmasi dari `tests/Feature/DataSpatial/MetadataDatasetTest.php`).
- **Belum ada verifikasi browser** — tampilan dashboard (kartu, filter, grafik Chart.js, tabel) belum pernah dibuka di browser sungguhan pada sesi ini. Chart.js sendiri sudah dikonfirmasi lewat audit sudah dimuat global (`main.blade.php`), tapi rendering grafik & interaksi filter (`onchange="this.form.submit()"`) belum diverifikasi visual.
- `npm run build`/`npm run dev` **tidak relevan** untuk fitur ini — semua view baru adalah Blade admin biasa (server-rendered), tidak ada aset Vite yang disentuh.

## Tujuan

Menutup rekomendasi review *"dashboard eksekutif menampilkan capaian fisik, realisasi keuangan, dan status proyek"* (bagian D di [`../03_plan/10-tindak-lanjut-review-jamil.md`](../03_plan/10-tindak-lanjut-review-jamil.md)) dengan menambah **model data minimum untuk progres proyek** dan **dashboard pembangunan terpisah** dari `DashboardController` yang sudah ada (yang 100% berisi statistik aspirasi/pengunjung), tanpa menyentuh migrasi skema V2 penuh (`spatial_layers`/`development_projects`).

## Referensi

- [`../Hasil_Review_Marimoi_Jamil.md`](../Hasil_Review_Marimoi_Jamil.md) — rekomendasi asli.
- [`../03_plan/10-tindak-lanjut-review-jamil.md`](../03_plan/10-tindak-lanjut-review-jamil.md) — bagian D (Dashboard Eksekutif Minimum) dan bagian E (Analisis Tren Pembangunan, lanjutan yang **tidak** termasuk dokumen ini).
- [`03-metadata-dataset.md`](03-metadata-dataset.md) — prasyarat yang sudah selesai: kolom `opd_pengelola_id`/`tanggal_data`/`sumber_data` pada `data_spatial`, relasi `DataSpatial::opdPengelola()`. Dokumen ini murni membangun di atasnya untuk kepemilikan OPD atas proyek.
- [`../03_plan/09-ringkasan-konsep-dan-alur.md`](../03_plan/09-ringkasan-konsep-dan-alur.md) §12 — penamaan tabel `project_progress_reports` di skema V2 penuh, jadi rujukan penamaan tabel baru di dokumen ini supaya reparenting nanti (`data_spatial_id` → `project_location_id`) tidak perlu bangun ulang dari nol.
- [`../03_plan/11-integrasi-inaproc.md`](../03_plan/11-integrasi-inaproc.md) — sumber business rules untuk Tahap 9 (pembaruan laporan + jejak audit). Dokumen itu tetap jadi rujukan untuk integrasi INAPROC sungguhan (sinkronisasi, `inaproc_realisasi_reports`, dst.) yang **tidak** termasuk cakupan revisi ini.

## Cakupan

Termasuk: tabel baru `project_progress_reports` (satu baris per proyek per periode laporan; sejak Tahap 9 **boleh diperbarui**, bukan append-only murni lagi — lihat Keputusan #9), form admin untuk menambah laporan progres pada proyek strategis (`data_type = 'proyek_strategis'`) miliknya, halaman "Dashboard Pembangunan" baru (kartu ringkasan, filter wilayah/sektor/OPD/tahun/status, tabel proyek dengan tautan ke histori laporan, satu grafik distribusi progres per sektor), scope akses admin-opd (hanya proyek OPD sendiri), sejak Tahap 9: pembaruan laporan yang sudah ada dengan jejak audit (`project_progress_report_revisions`) dan kolom `sumber_data` (disiapkan untuk integrasi INAPROC nanti, saat ini seluruhnya `'manual'`).

Tidak termasuk: dashboard publik (tidak ada kolom status publikasi di `data_spatial` saat ini — lihat Keputusan #7), analisis tren tahun-ke-tahun (bagian E, baru bisa jalan setelah tabel ini punya data multi-periode), migrasi skema V2 penuh (`development_projects`/`project_locations`), **integrasi INAPROC sungguhan** — sinkronisasi otomatis, `inaproc_realisasi_reports`, penautan paket pengadaan ke peta, dsb. (seluruhnya di [`11-integrasi-inaproc.md`](../03_plan/11-integrasi-inaproc.md), disimpan untuk nanti; Tahap 9 di sini hanya menyiapkan skema `sumber_data`/`revisions` yang akan dipakai ulang, bukan mengimplementasikan INAPROC).

## Kondisi Existing (Audit Singkat)

| Area | Temuan |
| --- | --- |
| `DashboardController` | [`app/Http/Controllers/DashboardController.php`](../../../app/Http/Controllers/DashboardController.php) — 766 baris, **17 method, tidak satu pun menyinggung proyek/fisik/keuangan**. Semuanya statistik `Aspirasi`/`Visitor` (`getMonthlyAspirasiData`, `getCategoryDistributionForDashboard`, `getMonthlyVisitorData`, dst.). Konfirmasi langsung dari `grep -n "public function\|private function"` — daftar lengkap tidak punya method proyek. |
| Bug pra-eksisting di `index()` (tidak diperbaiki di sini, di luar cakupan) | Baris ~42: `$userOpdId = Auth::user()->id;` dipakai untuk `DataSpatial::where('user_id', $userOpdId)->count()` saat admin-opd — seharusnya `Auth::user()->opd_id` (pola yang benar dipakai di method lain pada file yang sama, mis. `applyOpdFilterToRawQuery()`). Dicatat sebagai temuan audit, **tidak disentuh** karena di luar scope dashboard pembangunan. |
| Skema `data_spatial` | Kolom aktual (`Schema::getColumnListing('data_spatial')`): `id, user_id, uuid, data_type, sub_type, gambar, kategori_id, deskripsi, dbf_attributes, tahun, views, created_at, updated_at, geom, sumber_data, opd_pengelola_id, tanggal_data`. **Tidak ada** `pagu`, `realisasi_anggaran`, `progres_fisik_persen`, atau `status` proyek — persis seperti yang sudah diidentifikasi di bagian D `10-tindak-lanjut-review-jamil.md`, dikonfirmasi ulang di sini terhadap skema nyata. |
| Proyek strategis sudah ada sebagai data, bukan konsep baru | `public/frontend/js/map.js` `getDataType()` (baris 1207-1224) memetakan `/proyek-strategis-daerah` → `{type: "proyek_strategis", sub_type: "psd"}` dan `/proyek-strategis-nasional` → `{type: "proyek_strategis", sub_type: "psn"}`. Baris `data_spatial` dengan `data_type = 'proyek_strategis'` **sudah ada** di database produksi (diinput via shapefile/KMZ import atau form manual) — dashboard ini murni menambah lapisan pelaporan progres di atasnya, bukan membuat entitas proyek baru. |
| `opd_pengelola_id` sudah otomatis terisi untuk admin-opd | `DataSpatialController::saveDataSpatial()` (baris 887-889): `$data['opd_pengelola_id'] = $this->isAdminOPD() ? Auth::user()->opd_id : $request->opd_pengelola_id;`. Kolom ini (hasil bagian A) sudah bisa langsung dipakai sebagai penentu kepemilikan OPD atas satu proyek — tidak perlu kolom kepemilikan baru. |
| **Tidak ada halaman admin untuk melihat daftar Proyek Strategis Daerah/Nasional** | `DataSpatialController::index()` baris 46-49: `if ($type !== 'tematik') { return redirect()->back(); }` — secara eksplisit memblokir listing selain `tematik`. `grep -n "proyek\|psd\|psn" routes/backend.php` hanya menemukan 1 baris (redirect `/tematik/*` ke `data-spatial.*`) — **tidak ada route group khusus PSD/PSN admin sama sekali**. Konsekuensi: dokumen ini harus membangun listing proyek sendiri di `ProjectProgressController::index()` (Tahap 3), tidak bisa reuse halaman existing. |
| Pola scoping OPD yang sudah terbukti | `DashboardController::applyOpdFilterToRawQuery()` (baris 25-33) dan `isAdminOpd()` (baris 18-20, cek `Auth::user()->role->slug === 'admin-opd'`) — pola yang sama dipakai ulang di dokumen ini, disederhanakan karena relasi kepemilikan proyek cuma butuh satu kolom (`data_spatial.opd_pengelola_id`), bukan join berlapis seperti `aspirasi` → `kategori_aspirasi.opd_id`. |
| Sistem permission | [`app/Models/Permission.php`](../../../app/Models/Permission.php) — `Permission::CATALOG` (const array modul → aksi → label) dan [`database/seeders/PermissionSeeder.php`](../../../database/seeders/PermissionSeeder.php) (`DEFAULTS` per role, pola `"{modul}.{aksi}"`, wildcard `"{modul}.*"`). Pola yang sudah dipakai `data-spatial.*`, `project-feedbacks.*`, dst — dokumen ini menambah modul baru `project-progress` dengan pola identik. |
| Routing admin | `routes/backend.php` baris 76: **seluruh** route admin (data-spatial, categories, project-feedbacks, aspirasi, dst.) ternyata bersarang di dalam `Route::prefix('dashboard')->middleware(['auth'])->group(function () { ... })` yang baru ditutup di baris 359. Dikonfirmasi lewat `php artisan route:list` — `data-spatial.index` benar-benar resolve ke URI `dashboard/data-spatial`, bukan `/data-spatial`. Route baru di dokumen ini otomatis dapat prefix `/dashboard/*` yang sama tanpa perlu deklarasi eksplisit. |
| Layout & sidebar admin | `resources/views/dashboard.blade.php` → `@extends('backend.partials.main')`, `@section('main')`. Chart.js (`chart.umd.js`) dimuat sekali secara global di `resources/views/backend/partials/main.blade.php` baris 93 dan skrip per-halaman ditulis lewat `@section('scripts')` (**bukan** `@push`). Sidebar (`resources/views/backend/partials/sidebar.blade.php`) memakai pola `@can('modul.aksi')`/`$user?->canAny([...])` + `nav-section-label` per grup menu + `request()->routeIs('x.*')` untuk state aktif — dipakai ulang persis untuk menu baru. |
| Role `'user'` juga punya `dashboard.view` | `PermissionSeeder::DEFAULTS['user'] = ['dashboard.view']` — permission ini terlalu luas untuk dipakai sebagai gerbang dashboard pembangunan yang berisi angka keuangan proyek. Dashboard baru **harus** digerbangi permission modul baru (`project-progress.view`), bukan `dashboard.view` yang generik (lihat Keputusan #6). |

## Keputusan yang Perlu Dikunci Sebelum Implementasi

1. **Tabel baru `project_progress_reports`, append-only.** Satu baris = satu laporan untuk satu proyek pada satu periode. Nama tabel & sebagian besar nama kolom sengaja disamakan dengan `09-ringkasan-konsep-dan-alur.md` §12 supaya migrasi V2 penuh nanti tinggal reparent `data_spatial_id` → `project_location_id`.
2. **`opd_id` dan `kategori_id` di `project_progress_reports` adalah snapshot saat laporan dibuat**, diambil dari `data_spatial.opd_pengelola_id`/`kategori_id` proyek terkait pada saat itu — **bukan** live join ke `data_spatial`. Alasan: atribusi historis (laporan tahun 2025 tetap tercatat milik OPD X meski proyeknya direassign ke OPD Y di 2026) tidak boleh berubah retroaktif hanya karena metadata proyek diedit belakangan. Otorisasi **membuat** laporan baru tetap memakai `data_spatial.opd_pengelola_id` yang live (lihat Keputusan #3).
3. **Admin-opd hanya boleh melapor untuk proyek yang `opd_pengelola_id`-nya (live, saat ini) sama dengan OPD dia.** Proyek dengan `opd_pengelola_id` kosong (`null`) tidak bisa dilaporkan oleh admin-opd sama sekali (hanya admin-bappeda/super-admin) — konsekuensi yang diterima, bukan bug, karena tidak ada cara memastikan kepemilikan proyek yang datanya belum lengkap.
4. **Append-only: tidak ada edit/hapus laporan progres individual di iterasi pertama**, persis sesuai rencana awal ("Laporan baru menambah baris, bukan overwrite"). Kesalahan input dikoreksi dengan menambah laporan baru di periode yang sama **tidak bisa** (dicegah unique constraint — lihat Keputusan #5), jadi kesalahan hanya bisa dikoreksi manual lewat DB untuk iterasi pertama. Ini keterbatasan yang disengaja, dicatat eksplisit di Risiko, bukan sesuatu yang "lupa" ditangani.
   > **Diamendemen di Tahap 9 (2026-09-24):** append-only murni terbukti terlalu kaku begitu ada kebutuhan nyata memperbarui angka periode yang sudah dilaporkan (mis. realisasi anggaran bertambah setelah laporan disubmit). Laporan sekarang **boleh diperbarui** lewat aksi terpisah yang selalu mencatat nilai lama ke `project_progress_report_revisions` sebelum menimpa — histori tetap terjaga, hanya modelnya berubah dari "tidak bisa diubah sama sekali" jadi "bisa diubah, tapi setiap perubahan tercatat". Lihat Keputusan #11 dan Tahap 9.
5. **Unique constraint `(data_spatial_id, tahun_anggaran, periode_laporan)`** mencegah laporan ganda untuk kombinasi proyek+tahun+periode yang sama. `periode_laporan` dibatasi ke 4 nilai tetap (`Triwulan 1`..`Triwulan 4`, lewat validasi `in:`, bukan kolom DB enum native Postgres supaya migrasi tetap portable) — bukan teks bebas, supaya agregasi tren di bagian E nanti bisa mengelompokkan periode secara konsisten tanpa normalisasi teks.
6. **"Laporan terbaru per proyek" diselesaikan dengan `MAX(id)` per `data_spatial_id` dalam `tahun_anggaran` terpilih** — bukan window function berbasis urutan periode. Asumsi: input bersifar berurutan (admin melapor Triwulan 1 dulu baru Triwulan 2, dst., sesuai workflow yang diharapkan). Konsekuensi yang diterima: bila admin mengisi periode tidak berurutan (mis. Triwulan 3 sebelum Triwulan 2), kartu ringkasan akan menganggap entri **terakhir disimpan** (bukan periode kronologis terakhir) sebagai "laporan terbaru". Dicatat sebagai keterbatasan iterasi pertama di Risiko, bukan bug — perbaikan penuh (window function `ROW_NUMBER() OVER (PARTITION BY ... ORDER BY tahun_anggaran, periode_rank)`) didorong ke iterasi berikutnya bila terbukti jadi masalah nyata.
7. **"Proyek bermasalah" = kolom `status` bernilai `terlambat`, diisi manual oleh admin pelapor** — bukan dihitung otomatis dari progres fisik vs kalender anggaran (butuh model kalender fiskal yang belum ada di sistem manapun). Ini definisi eksplisit dan sederhana untuk MVP, menghindari heuristik otomatis yang rapuh sebelum ada kebutuhan nyata yang memvalidasinya.
8. **Tidak ada dashboard publik.** `data_spatial` tidak punya kolom status publikasi apa pun saat ini (dikonfirmasi di audit; juga dicatat sebagai gap terbuka di `03-metadata-dataset.md`). Seluruh fitur di dokumen ini (form input, dashboard) berada di belakang permission modul baru `project-progress`, hanya bisa diakses lewat `/dashboard/*` (admin), bukan halaman `frontend/*`.
9. **Dashboard Pembangunan digerbangi permission `project-progress.view`, bukan `dashboard.view` yang generik** — karena `dashboard.view` juga dipunyai role `'user'` (lihat audit), yang tidak seharusnya otomatis melihat angka pagu/realisasi anggaran proyek. `project-progress.view` di-assign eksplisit hanya ke `admin-bappeda`/`admin-opd` (Tahap 2) plus `super-admin` (otomatis lewat sinkronisasi permission penuh yang sudah ada di `PermissionSeeder::run()`).
10. **Sektor = `kategori_id` proyek (pohon `Category` yang sudah ada, tipe `tematik`), bukan kolom teks bebas baru.** Konsisten dengan data yang sudah melekat di setiap baris `data_spatial`, menghindari admin mengisi taksonomi sektor dua kali (di kategori peta dan di laporan progres).

### Keputusan tambahan — Tahap 9 (2026-09-24)

11. **Pembaruan laporan diizinkan untuk field finansial/progres (`pagu`, `realisasi_anggaran`, `progres_fisik_persen`, `status`, `catatan`), tapi `tahun_anggaran`/`periode_laporan` tetap tidak bisa diubah.** Kedua kolom itu adalah *identitas* laporan (bagian dari unique constraint Keputusan #5) — mengizinkan perubahannya lewat aksi "Perbarui" akan membuka celah admin diam-diam memindahkan laporan ke periode lain alih-alih membuat laporan baru untuk periode itu. Ganti periode berarti tambah laporan baru (alur Tahap 3 yang sudah ada), bukan edit.
12. **Setiap pembaruan mencatat snapshot nilai lama ke `project_progress_report_revisions` sebagai JSON (`data_sebelumnya`), bukan kolom `_sebelumnya` per field.** Berbeda dari rancangan awal di `11-integrasi-inaproc.md` (yang memakai kolom eksplisit `pagu_sebelumnya`/`realisasi_sebelumnya`, dibatasi ke 2 field finansial karena waktu itu didesain khusus untuk pembaruan bersumber INAPROC) — versi manual ini menyimpan seluruh field yang boleh diperbarui (`ProjectProgressReport::FIELD_DAPAT_DIPERBARUI`) dalam satu kolom JSON. Alasan: tanpa gating `sumber_data = 'inaproc'`, field yang bisa berubah lebih banyak (termasuk `status`/`catatan`, bukan cuma angka keuangan), dan JSON tunggal lebih murah diperluas tanpa migration baru bila field yang boleh diedit bertambah lagi nanti.
13. **Otorisasi pembaruan mengikuti kepemilikan proyek (`authorizeProject()`), bukan siapa yang membuat laporan aslinya.** Admin-bappeda/super-admin atau admin-opd pemilik proyek boleh memperbarui laporan mana pun milik proyek itu, termasuk yang dibuat admin lain — konsisten dengan model "laporan milik proyek/OPD", bukan "laporan milik pembuatnya". Permission baru `project-progress.edit` (terpisah dari `.create`) supaya hak "menambah laporan" dan "mengubah laporan yang sudah ada" bisa diatur berbeda per role bila dibutuhkan nanti, meski saat ini keduanya di-assign bersamaan ke `admin-bappeda`/`admin-opd`.
14. **`sumber_data` (`'manual'`/`'inaproc'`) ditambahkan sekarang meski INAPROC belum diimplementasikan** — seluruh laporan yang dibuat lewat Tahap 3/9 selalu `'manual'`. Kolom ini murni penyiapan skema untuk `11-integrasi-inaproc.md` (disimpan untuk nanti): begitu sinkronisasi INAPROC dibangun, laporan yang nilainya berasal dari sana tinggal ditandai `'inaproc'` memakai mekanisme pembaruan yang **sama persis** dari Tahap 9 ini, tanpa migration atau controller baru.

## Tahap 1 — Migration dan Model `ProjectProgressReport`

### 1.1 Migration `..._create_project_progress_reports_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_progress_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_spatial_id')->constrained('data_spatial')->restrictOnDelete();
            $table->foreignId('opd_id')->nullable()->constrained('opd')->nullOnDelete();
            $table->foreignId('kategori_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedSmallInteger('tahun_anggaran');
            $table->string('periode_laporan', 20);
            $table->decimal('pagu', 18, 2)->nullable();
            $table->decimal('realisasi_anggaran', 18, 2)->nullable();
            $table->decimal('progres_fisik_persen', 5, 2)->default(0);
            $table->string('status', 20)->default('belum_mulai');
            $table->text('catatan')->nullable();
            $table->foreignId('dilaporkan_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['data_spatial_id', 'tahun_anggaran', 'periode_laporan'],
                'project_progress_reports_unique_period'
            );
            $table->index(['opd_id', 'tahun_anggaran']);
            $table->index(['kategori_id', 'tahun_anggaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_progress_reports');
    }
};
```

Dijalankan via `php artisan make:migration create_project_progress_reports_table --create=project_progress_reports`, lalu isi seperti di atas. Karena tabel **baru** (bukan menyunting `data_spatial` yang sudah berisi data produksi), migrasi ini bisa dijalankan lewat `php artisan migrate` normal — tidak perlu pola `--path` terisolasi yang dipakai untuk migration lain di sesi-sesi sebelumnya (itu khusus untuk migration yang menyunting tabel lama dengan `migrations` table yang out-of-sync).

### 1.2 Model `app/Models/ProjectProgressReport.php` (baru)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProgressReport extends Model
{
    use HasFactory;

    protected $table = 'project_progress_reports';

    /**
     * @var array<int, string>
     */
    public const STATUSES = ['belum_mulai', 'on_track', 'terlambat', 'selesai'];

    /**
     * @var array<int, string>
     */
    public const PERIODE = ['Triwulan 1', 'Triwulan 2', 'Triwulan 3', 'Triwulan 4'];

    protected $fillable = [
        'data_spatial_id',
        'opd_id',
        'kategori_id',
        'tahun_anggaran',
        'periode_laporan',
        'pagu',
        'realisasi_anggaran',
        'progres_fisik_persen',
        'status',
        'catatan',
        'dilaporkan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tahun_anggaran' => 'integer',
            'pagu' => 'decimal:2',
            'realisasi_anggaran' => 'decimal:2',
            'progres_fisik_persen' => 'decimal:2',
        ];
    }

    public function dataSpatial(): BelongsTo
    {
        return $this->belongsTo(DataSpatial::class, 'data_spatial_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id');
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilaporkan_oleh');
    }
}
```

Dibuat via `php artisan make:model ProjectProgressReport` lalu isi sesuai di atas (jangan buat migration/factory otomatis dari command ini — migration sudah ditulis manual di 1.1, factory di Tahap 6 testing).

### 1.3 Tambahan relasi di `app/Models/DataSpatial.php`

Tambah `use Illuminate\Database\Eloquent\Relations\HasMany;` di bagian import, lalu tambah method setelah `opdPengelola()`:

```php
// Relasi ke seluruh laporan progres proyek ini (terurut dari yang paling baru disimpan)
public function progressReports(): HasMany
{
    return $this->hasMany(ProjectProgressReport::class, 'data_spatial_id')->latest('id');
}
```

## Tahap 2 — Permission Catalog dan Seeder

### 2.1 `app/Models/Permission.php`

Tambah entri baru ke `CATALOG`, setelah blok `'project-feedbacks' => [...]`:

```php
'project-progress' => [
    'label' => 'Progres Proyek Strategis',
    'actions' => [
        'view' => 'Lihat laporan progres proyek',
        'create' => 'Tambah laporan progres',
    ],
],
```

### 2.2 `database/seeders/PermissionSeeder.php`

Tambah ke `DEFAULTS['admin-bappeda']` (array yang sudah ada): `'project-progress.*',`.

Tambah ke `DEFAULTS['admin-opd']`: `'project-progress.view',` dan `'project-progress.create',`.

`super-admin` otomatis dapat semua permission baru lewat `Role::where('slug', 'super-admin')->each(fn ($role) => $role->syncPermissions($names))` yang sudah ada di `run()` — tidak perlu perubahan.

**Penting (dicatat juga di Risiko):** `PermissionSeeder::run()` melewati role yang **sudah** punya permission tersimpan (`$role->permissions()->exists()` → `continue`). Di environment yang sudah pernah menjalankan seeder ini sebelumnya (staging/produksi), menjalankan ulang `php artisan db:seed --class=PermissionSeeder` **tidak** otomatis menambahkan `project-progress.*` ke role `admin-bappeda`/`admin-opd` yang sudah ada. Perlu langkah manual terpisah (lihat Urutan Eksekusi langkah 8).

## Tahap 3 — Backend: `ProjectProgressController` (Input Laporan Progres)

### 3.1 Controller baru `app/Http/Controllers/ProjectProgressController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\ProjectProgressReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectProgressController extends Controller
{
    private function isAdminOpd(): bool
    {
        return Auth::user()?->role?->slug === 'admin-opd';
    }

    private function scopeProjectsToOpd($query)
    {
        if ($this->isAdminOpd()) {
            $query->where('opd_pengelola_id', Auth::user()->opd_id);
        }

        return $query;
    }

    private function authorizeProject(DataSpatial $proyek): void
    {
        if ($this->isAdminOpd() && (int) $proyek->opd_pengelola_id !== (int) Auth::user()->opd_id) {
            abort(403, 'Anda tidak memiliki akses ke proyek OPD lain.');
        }
    }

    public function index(Request $request)
    {
        $query = DataSpatial::query()
            ->where('data_type', 'proyek_strategis')
            ->with(['kategori:id,nama', 'opdPengelola:id,name,singkatan'])
            ->with(['progressReports' => fn ($q) => $q->limit(1)]);

        $this->scopeProjectsToOpd($query);

        if ($request->filled('sub_type')) {
            $query->where('sub_type', $request->sub_type);
        }

        if ($request->filled('opd_pengelola_id') && ! $this->isAdminOpd()) {
            $query->where('opd_pengelola_id', $request->opd_pengelola_id);
        }

        $proyek = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $opdOptions = $this->isAdminOpd() ? collect() : Opd::orderBy('name')->get(['id', 'name', 'singkatan']);

        return view('backend.pages.project-progress.index', compact('proyek', 'opdOptions'));
    }

    public function show(string $uuid)
    {
        $proyek = DataSpatial::where('uuid', $uuid)
            ->where('data_type', 'proyek_strategis')
            ->with(['kategori:id,nama', 'opdPengelola:id,name,singkatan'])
            ->firstOrFail();

        $this->authorizeProject($proyek);

        $laporan = $proyek->progressReports()->with('pelapor:id,name')->get();

        return view('backend.pages.project-progress.show', compact('proyek', 'laporan'));
    }

    public function create(string $uuid)
    {
        $proyek = DataSpatial::where('uuid', $uuid)
            ->where('data_type', 'proyek_strategis')
            ->firstOrFail();

        $this->authorizeProject($proyek);

        return view('backend.pages.project-progress.create', [
            'proyek' => $proyek,
            'statuses' => ProjectProgressReport::STATUSES,
            'periodeOptions' => ProjectProgressReport::PERIODE,
        ]);
    }

    public function store(Request $request, string $uuid)
    {
        $proyek = DataSpatial::where('uuid', $uuid)
            ->where('data_type', 'proyek_strategis')
            ->firstOrFail();

        $this->authorizeProject($proyek);

        $validated = $request->validate([
            'tahun_anggaran' => 'required|integer|min:2000|max:2100',
            'periode_laporan' => 'required|string|in:'.implode(',', ProjectProgressReport::PERIODE),
            'pagu' => 'nullable|numeric|min:0',
            'realisasi_anggaran' => 'nullable|numeric|min:0',
            'progres_fisik_persen' => 'required|numeric|min:0|max:100',
            'status' => 'required|string|in:'.implode(',', ProjectProgressReport::STATUSES),
            'catatan' => 'nullable|string|max:2000',
        ]);

        $duplikat = ProjectProgressReport::where('data_spatial_id', $proyek->id)
            ->where('tahun_anggaran', $validated['tahun_anggaran'])
            ->where('periode_laporan', $validated['periode_laporan'])
            ->exists();

        if ($duplikat) {
            return back()->withInput()->withErrors([
                'periode_laporan' => 'Laporan untuk periode dan tahun anggaran ini sudah pernah ditambahkan.',
            ]);
        }

        ProjectProgressReport::create([
            ...$validated,
            'data_spatial_id' => $proyek->id,
            'opd_id' => $proyek->opd_pengelola_id,
            'kategori_id' => $proyek->kategori_id,
            'dilaporkan_oleh' => Auth::id(),
        ]);

        return redirect()
            ->route('project-progress.show', $proyek->uuid)
            ->with('success', 'Laporan progres berhasil ditambahkan.');
    }
}
```

Catatan: `Category` di-import untuk konsistensi dengan controller lain di modul ini meski belum dipakai langsung di sini (dipakai di `PembangunanDashboardController`, Tahap 5) — audit ulang saat eksekusi apakah import ini benar-benar terpakai di file ini; hapus bila tidak, supaya lolos `vendor/bin/pint`/linter tanpa "unused import" (Pint sendiri tidak menegakkan ini, tapi tetap dirapikan).

### 3.2 Routes — tambah di `routes/backend.php`

Tambah import di bagian atas file (dekat `use App\Http\Controllers\ProjectFeedbackController;`):

```php
use App\Http\Controllers\PembangunanDashboardController;
use App\Http\Controllers\ProjectProgressController;
```

Tambah route group baru **di dalam** `Route::prefix('dashboard')->middleware(['auth'])->group(function () { ... })` yang sudah ada (jadi otomatis dapat prefix URI `/dashboard/*` — lihat audit), diletakkan setelah blok `project-feedbacks` (sebelum komentar `Resource Routes`):

```php
/*
|--------------------------------------------------------------------------
| Progres Proyek Strategis & Dashboard Pembangunan
|--------------------------------------------------------------------------
*/

Route::prefix('project-progress')->name('project-progress.')->group(function () {
    Route::get('/', [ProjectProgressController::class, 'index'])->name('index')->middleware('permission:project-progress.view');
    Route::get('/{uuid}', [ProjectProgressController::class, 'show'])->name('show')->middleware('permission:project-progress.view');
    Route::get('/{uuid}/create', [ProjectProgressController::class, 'create'])->name('create')->middleware('permission:project-progress.create');
    Route::post('/{uuid}', [ProjectProgressController::class, 'store'])->name('store')->middleware('permission:project-progress.create');
});

Route::get('/pembangunan', [PembangunanDashboardController::class, 'index'])
    ->name('dashboard.pembangunan')
    ->middleware('permission:project-progress.view');
```

## Tahap 4 — Views: Daftar Proyek, Form Laporan, Histori

Tiga view baru di `resources/views/backend/pages/project-progress/` (folder baru), semuanya `@extends('backend.partials.main')` mengikuti pola `dashboard.blade.php`.

### 4.1 `index.blade.php` — daftar proyek strategis (pengganti halaman "buka proyek milikku" yang belum ada)

```blade
@extends('backend.partials.main')

@section('main')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-clipboard-text"></i></span>
        Progres Proyek Strategis
    </h3>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            @if ($opdOptions->isNotEmpty())
                <div class="col-auto">
                    <select name="opd_pengelola_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua OPD</option>
                        @foreach ($opdOptions as $opd)
                            <option value="{{ $opd->id }}" @selected(request('opd_pengelola_id') == $opd->id)>{{ $opd->singkatan }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-auto">
                <select name="sub_type" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Jenis</option>
                    <option value="psd" @selected(request('sub_type') === 'psd')>Proyek Strategis Daerah</option>
                    <option value="psn" @selected(request('sub_type') === 'psn')>Proyek Strategis Nasional</option>
                </select>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Proyek</th>
                        <th>OPD</th>
                        <th>Sektor</th>
                        <th>Laporan Terakhir</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proyek as $item)
                        @php($latest = $item->progressReports->first())
                        <tr>
                            <td>{{ $item->deskripsi }}</td>
                            <td>{{ $item->opdPengelola?->singkatan ?? '-' }}</td>
                            <td>{{ $item->kategori?->nama ?? '-' }}</td>
                            <td>
                                @if ($latest)
                                    {{ $latest->periode_laporan }} {{ $latest->tahun_anggaran }} — {{ $latest->progres_fisik_persen }}%
                                @else
                                    Belum ada laporan
                                @endif
                            </td>
                            <td>{{ $latest?->status ?? '-' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('project-progress.show', $item->uuid) }}" class="btn btn-sm btn-outline-primary">Histori</a>
                                @can('project-progress.create')
                                    <a href="{{ route('project-progress.create', $item->uuid) }}" class="btn btn-sm btn-primary">Tambah Laporan</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                Belum ada proyek strategis yang tercatat{{ $opdOptions->isEmpty() ? ' untuk OPD Anda' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $proyek->links() }}
    </div>
</div>
@endsection
```

### 4.2 `create.blade.php` — form tambah laporan progres

```blade
@extends('backend.partials.main')

@section('main')
<div class="page-header">
    <h3 class="page-title">Tambah Laporan Progres — {{ $proyek->deskripsi }}</h3>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('project-progress.store', $proyek->uuid) }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Tahun Anggaran</label>
                <input type="number" name="tahun_anggaran" class="form-control @error('tahun_anggaran') is-invalid @enderror"
                    value="{{ old('tahun_anggaran', now()->year) }}" required>
                @error('tahun_anggaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Periode Laporan</label>
                <select name="periode_laporan" class="form-select @error('periode_laporan') is-invalid @enderror" required>
                    <option value="">Pilih periode</option>
                    @foreach ($periodeOptions as $periode)
                        <option value="{{ $periode }}" @selected(old('periode_laporan') === $periode)>{{ $periode }}</option>
                    @endforeach
                </select>
                @error('periode_laporan') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Pagu (Rp)</label>
                <input type="number" step="0.01" name="pagu" class="form-control" value="{{ old('pagu') }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Realisasi Anggaran (Rp)</label>
                <input type="number" step="0.01" name="realisasi_anggaran" class="form-control" value="{{ old('realisasi_anggaran') }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Progres Fisik (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="progres_fisik_persen"
                    class="form-control @error('progres_fisik_persen') is-invalid @enderror" value="{{ old('progres_fisik_persen') }}" required>
                @error('progres_fisik_persen') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(old('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="catatan" class="form-control" rows="3">{{ old('catatan') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Laporan</button>
            <a href="{{ route('project-progress.show', $proyek->uuid) }}" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
@endsection
```

### 4.3 `show.blade.php` — histori laporan per proyek (audit trail)

```blade
@extends('backend.partials.main')

@section('main')
<div class="page-header">
    <h3 class="page-title">Histori Progres — {{ $proyek->deskripsi }}</h3>
</div>

@can('project-progress.create')
    <a href="{{ route('project-progress.create', $proyek->uuid) }}" class="btn btn-primary mb-3">Tambah Laporan</a>
@endcan

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Pagu</th>
                        <th>Realisasi</th>
                        <th>% Realisasi</th>
                        <th>Progres Fisik</th>
                        <th>Status</th>
                        <th>Dilaporkan Oleh</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporan as $item)
                        <tr>
                            <td>{{ $item->periode_laporan }} {{ $item->tahun_anggaran }}</td>
                            <td>Rp {{ number_format($item->pagu ?? 0, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->realisasi_anggaran ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $item->pagu > 0 ? round($item->realisasi_anggaran / $item->pagu * 100, 1) : 0 }}%</td>
                            <td>{{ $item->progres_fisik_persen }}%</td>
                            <td>{{ $item->status }}</td>
                            <td>{{ $item->pelapor?->name ?? '-' }}</td>
                            <td>{{ $item->catatan }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">Belum ada laporan progres untuk proyek ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
```

## Tahap 5 — Backend: `PembangunanDashboardController`

### 5.1 Controller baru `app/Http/Controllers/PembangunanDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\ProjectProgressReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PembangunanDashboardController extends Controller
{
    private function isAdminOpd(): bool
    {
        return Auth::user()?->role?->slug === 'admin-opd';
    }

    public function index(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $opdId = $this->isAdminOpd() ? Auth::user()->opd_id : $request->get('opd_id');
        $kategoriId = $request->get('kategori_id');
        $status = $request->get('status');

        // Laporan terbaru per proyek untuk tahun anggaran terpilih (lihat Keputusan #6:
        // MAX(id) per data_spatial_id, mengasumsikan input berurutan per triwulan).
        $latestReportIds = ProjectProgressReport::query()
            ->where('tahun_anggaran', $tahun)
            ->groupBy('data_spatial_id')
            ->selectRaw('MAX(id) as id')
            ->pluck('id');

        $laporanQuery = ProjectProgressReport::query()
            ->whereIn('id', $latestReportIds)
            ->with(['dataSpatial:id,uuid,deskripsi,data_type,sub_type', 'opd:id,name,singkatan', 'kategori:id,nama']);

        if ($opdId) {
            $laporanQuery->where('opd_id', $opdId);
        }

        if ($kategoriId) {
            $laporanQuery->whereIn('kategori_id', Category::selfAndDescendantIds((int) $kategoriId));
        }

        if ($status) {
            $laporanQuery->where('status', $status);
        }

        $laporan = $laporanQuery->get();

        $cards = [
            'jumlah_proyek' => DataSpatial::where('data_type', 'proyek_strategis')
                ->when($opdId, fn ($q) => $q->where('opd_pengelola_id', $opdId))
                ->count(),
            'jumlah_dilaporkan' => $laporan->count(),
            'total_pagu' => (float) $laporan->sum('pagu'),
            'total_realisasi' => (float) $laporan->sum('realisasi_anggaran'),
            'rata_progres_fisik' => $laporan->count() ? round((float) $laporan->avg('progres_fisik_persen'), 1) : 0,
            'jumlah_bermasalah' => $laporan->where('status', 'terlambat')->count(),
        ];
        $cards['persen_realisasi'] = $cards['total_pagu'] > 0
            ? round(($cards['total_realisasi'] / $cards['total_pagu']) * 100, 1)
            : 0;

        $progresPerSektor = $laporan
            ->groupBy(fn (ProjectProgressReport $item) => $item->kategori?->nama ?? 'Tanpa Sektor')
            ->map(fn ($group) => round((float) $group->avg('progres_fisik_persen'), 1));

        $tahunOptions = ProjectProgressReport::query()
            ->distinct()
            ->orderByDesc('tahun_anggaran')
            ->pluck('tahun_anggaran');
        if ($tahunOptions->isEmpty()) {
            $tahunOptions = collect([now()->year]);
        }

        $opdOptions = $this->isAdminOpd() ? collect() : Opd::orderBy('name')->get(['id', 'name', 'singkatan']);

        $kategoriOptions = Category::whereIn(
            'id',
            DataSpatial::where('data_type', 'proyek_strategis')->whereNotNull('kategori_id')->pluck('kategori_id')->unique()
        )->orderBy('nama')->get(['id', 'nama']);

        return view('backend.pages.dashboard-pembangunan', compact(
            'cards', 'laporan', 'progresPerSektor', 'tahun', 'tahunOptions', 'opdOptions', 'kategoriOptions', 'opdId', 'kategoriId', 'status'
        ));
    }
}
```

Catatan otorisasi: `$opdId` untuk admin-opd **selalu** dipaksa ke `Auth::user()->opd_id` di baris kedua method — parameter query `opd_id` dari request diabaikan sepenuhnya untuk role ini (bukan cuma disembunyikan di UI). ini penegakan otorisasi sungguhan di server, sesuai kriteria selesai "admin OPD tidak bisa melihat data progres OPD lain" di `10-tindak-lanjut-review-jamil.md` bagian D — diuji eksplisit di Tahap 7.

## Tahap 6 — View: Dashboard Pembangunan

`resources/views/backend/pages/dashboard-pembangunan.blade.php` (baru):

```blade
@extends('backend.partials.main')

@section('main')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-chart-line"></i></span>
        Dashboard Pembangunan
    </h3>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-auto">
        <select name="tahun" class="form-select" onchange="this.form.submit()">
            @foreach ($tahunOptions as $t)
                <option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    @if ($opdOptions->isNotEmpty())
        <div class="col-auto">
            <select name="opd_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua OPD</option>
                @foreach ($opdOptions as $opd)
                    <option value="{{ $opd->id }}" @selected($opdId == $opd->id)>{{ $opd->singkatan }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-auto">
        <select name="kategori_id" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Sektor</option>
            @foreach ($kategoriOptions as $kategori)
                <option value="{{ $kategori->id }}" @selected($kategoriId == $kategori->id)>{{ $kategori->nama }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            @foreach (\App\Models\ProjectProgressReport::STATUSES as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="row">
    <div class="col-md-4 stretch-card grid-margin">
        <div class="card bg-gradient-primary card-img-holder text-white">
            <div class="card-body">
                <h6 class="font-weight-normal">Proyek Terdaftar</h6>
                <h2 class="mb-2">{{ $cards['jumlah_proyek'] }}</h2>
                <small>{{ $cards['jumlah_dilaporkan'] }} sudah melapor tahun {{ $tahun }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 stretch-card grid-margin">
        <div class="card bg-gradient-info card-img-holder text-white">
            <div class="card-body">
                <h6 class="font-weight-normal">Realisasi Anggaran</h6>
                <h2 class="mb-2">{{ $cards['persen_realisasi'] }}%</h2>
                <small>Rp {{ number_format($cards['total_realisasi'], 0, ',', '.') }} / Rp {{ number_format($cards['total_pagu'], 0, ',', '.') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 stretch-card grid-margin">
        <div class="card bg-gradient-success card-img-holder text-white">
            <div class="card-body">
                <h6 class="font-weight-normal">Rata-rata Progres Fisik</h6>
                <h2 class="mb-2">{{ $cards['rata_progres_fisik'] }}%</h2>
                <small>{{ $cards['jumlah_bermasalah'] }} proyek berstatus terlambat</small>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h6 class="card-title">Rata-rata Progres Fisik per Sektor</h6>
        <canvas id="progresSektorChart" height="80"></canvas>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Proyek</th>
                        <th>OPD</th>
                        <th>Sektor</th>
                        <th>Periode</th>
                        <th>Pagu</th>
                        <th>Realisasi</th>
                        <th>Progres Fisik</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporan as $item)
                        <tr>
                            <td>{{ $item->dataSpatial?->deskripsi }}</td>
                            <td>{{ $item->opd?->singkatan ?? '-' }}</td>
                            <td>{{ $item->kategori?->nama ?? '-' }}</td>
                            <td>{{ $item->periode_laporan }} {{ $item->tahun_anggaran }}</td>
                            <td>Rp {{ number_format($item->pagu ?? 0, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->realisasi_anggaran ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $item->progres_fisik_persen }}%</td>
                            <td>{{ $item->status }}</td>
                            <td>
                                @if ($item->dataSpatial)
                                    <a href="{{ route('project-progress.show', $item->dataSpatial->uuid) }}">Detail</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">Belum ada laporan progres untuk filter yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    new Chart(document.getElementById('progresSektorChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: @json($progresPerSektor->keys()),
            datasets: [{
                label: 'Progres Fisik (%)',
                data: @json($progresPerSektor->values()),
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
</script>
@endsection
```

Chart.js **tidak perlu** dimuat ulang di sini — sudah global lewat `backend/partials/main.blade.php` baris 93 (`chart.umd.js`), dikonfirmasi lewat audit.

## Tahap 7 — Menu Sidebar

Tambah di `resources/views/backend/partials/sidebar.blade.php`, setelah blok `@endif` penutup menu "Peta Tematik" (baris ~102) dan sebelum blok "Upload Dokumen" (baris ~104):

```blade
{{-- Pembangunan --}}
@can('project-progress.view')
    <div class="nav-section-label">Pembangunan</div>
    <a class="nav-link {{ request()->routeIs('dashboard.pembangunan') ? 'active' : '' }}"
        href="{{ route('dashboard.pembangunan') }}">
        <span class="nav-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
        <span class="nav-text">Dashboard Pembangunan</span>
    </a>
    <a class="nav-link {{ request()->routeIs('project-progress.*') ? 'active' : '' }}"
        href="{{ route('project-progress.index') }}">
        <span class="nav-icon"><i class="bi bi-clipboard-data" aria-hidden="true"></i></span>
        <span class="nav-text">Progres Proyek Strategis</span>
    </a>
@endcan
```

Satu gerbang `@can('project-progress.view')` untuk kedua link — konsisten dengan Keputusan #9 (dashboard baru **tidak** memakai `dashboard.view` yang generik).

## Tahap 8 — Testing (PHPUnit)

### 8.1 Factory `database/factories/ProjectProgressReportFactory.php` (baru, untuk kebutuhan test)

```php
<?php

namespace Database\Factories;

use App\Models\DataSpatial;
use App\Models\ProjectProgressReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectProgressReportFactory extends Factory
{
    protected $model = ProjectProgressReport::class;

    public function definition(): array
    {
        $dataSpatial = DataSpatial::factory()->create(['data_type' => 'proyek_strategis', 'sub_type' => 'psd']);

        return [
            'data_spatial_id' => $dataSpatial->id,
            'opd_id' => $dataSpatial->opd_pengelola_id,
            'kategori_id' => $dataSpatial->kategori_id,
            'tahun_anggaran' => now()->year,
            'periode_laporan' => $this->faker->randomElement(ProjectProgressReport::PERIODE),
            'pagu' => $this->faker->randomFloat(2, 100000000, 5000000000),
            'realisasi_anggaran' => $this->faker->randomFloat(2, 0, 3000000000),
            'progres_fisik_persen' => $this->faker->randomFloat(2, 0, 100),
            'status' => $this->faker->randomElement(ProjectProgressReport::STATUSES),
            'catatan' => null,
            'dilaporkan_oleh' => User::factory(),
        ];
    }
}
```

### 8.2 `tests/Feature/ProjectProgressReportTest.php` (baru)

| Test | Skenario |
| --- | --- |
| `test_admin_bappeda_can_add_progress_report_for_any_project` | Bappeda submit laporan untuk proyek OPD manapun → tersimpan, redirect ke `show`. |
| `test_admin_opd_can_add_progress_report_for_their_own_project` | Admin-opd submit laporan untuk proyek dengan `opd_pengelola_id` = OPD-nya → berhasil. |
| `test_admin_opd_cannot_add_progress_report_for_other_opd_project` | Admin-opd submit laporan untuk proyek OPD lain → `403`, tidak ada baris tersimpan. |
| `test_duplicate_period_for_same_project_is_rejected` | Submit laporan kedua dengan `tahun_anggaran`+`periode_laporan` yang sama pada proyek yang sama → gagal dengan pesan error di `periode_laporan`, jumlah baris tetap 1. |
| `test_progress_percentage_validation_rejects_out_of_range_values` | `progres_fisik_persen` = 150 → `422`/validation error, tidak tersimpan. |
| `test_guest_cannot_access_progress_report_routes` | Tanpa login, akses `project-progress.index`/`create`/`store` → redirect ke halaman login. |

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\Permission;
use App\Models\ProjectProgressReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectProgressReportTest extends TestCase
{
    use RefreshDatabase;

    private function roleWithPermissions(string $slug, array $permissions): Role
    {
        $role = Role::create(['slug' => $slug, 'name' => ucfirst(str_replace('-', ' ', $slug)), 'description' => null]);

        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $role;
    }

    private function proyek(?int $opdId = null): DataSpatial
    {
        $category = Category::create(['type' => 'tematik', 'nama' => 'Jalan Provinsi', 'warna' => '#0d6efd']);
        $uploader = User::factory()->create();

        return DataSpatial::factory()->create([
            'user_id' => $uploader->id,
            'kategori_id' => $category->id,
            'data_type' => 'proyek_strategis',
            'sub_type' => 'psd',
            'opd_pengelola_id' => $opdId,
        ]);
    }

    public function test_admin_bappeda_can_add_progress_report_for_any_project(): void
    {
        $role = $this->roleWithPermissions('admin-bappeda', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $opd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);
        $proyek = $this->proyek($opd->id);

        $response = $this->actingAs($admin)->post(route('project-progress.store', $proyek->uuid), [
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'pagu' => 1000000000,
            'realisasi_anggaran' => 250000000,
            'progres_fisik_persen' => 25,
            'status' => 'on_track',
        ]);

        $response->assertRedirect(route('project-progress.show', $proyek->uuid));
        $this->assertDatabaseHas('project_progress_reports', [
            'data_spatial_id' => $proyek->id,
            'opd_id' => $opd->id,
            'periode_laporan' => 'Triwulan 1',
        ]);
    }

    public function test_admin_opd_can_add_progress_report_for_their_own_project(): void
    {
        $opd = Opd::create(['name' => 'Dinas Kesehatan', 'singkatan' => 'DINKES']);
        $role = $this->roleWithPermissions('admin-opd', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id, 'opd_id' => $opd->id]);
        $proyek = $this->proyek($opd->id);

        $response = $this->actingAs($admin)->post(route('project-progress.store', $proyek->uuid), [
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'progres_fisik_persen' => 10,
            'status' => 'belum_mulai',
        ]);

        $response->assertRedirect(route('project-progress.show', $proyek->uuid));
        $this->assertDatabaseHas('project_progress_reports', ['data_spatial_id' => $proyek->id]);
    }

    public function test_admin_opd_cannot_add_progress_report_for_other_opd_project(): void
    {
        $ownOpd = Opd::create(['name' => 'Dinas Kesehatan', 'singkatan' => 'DINKES']);
        $otherOpd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);
        $role = $this->roleWithPermissions('admin-opd', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id, 'opd_id' => $ownOpd->id]);
        $proyek = $this->proyek($otherOpd->id);

        $response = $this->actingAs($admin)->post(route('project-progress.store', $proyek->uuid), [
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'progres_fisik_persen' => 10,
            'status' => 'belum_mulai',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('project_progress_reports', 0);
    }

    public function test_duplicate_period_for_same_project_is_rejected(): void
    {
        $role = $this->roleWithPermissions('admin-bappeda', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $proyek = $this->proyek();

        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyek->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
        ]);

        $response = $this->actingAs($admin)->post(route('project-progress.store', $proyek->uuid), [
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'progres_fisik_persen' => 50,
            'status' => 'on_track',
        ]);

        $response->assertSessionHasErrors('periode_laporan');
        $this->assertDatabaseCount('project_progress_reports', 1);
    }

    public function test_progress_percentage_validation_rejects_out_of_range_values(): void
    {
        $role = $this->roleWithPermissions('admin-bappeda', ['project-progress.view', 'project-progress.create']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $proyek = $this->proyek();

        $response = $this->actingAs($admin)->post(route('project-progress.store', $proyek->uuid), [
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'progres_fisik_persen' => 150,
            'status' => 'on_track',
        ]);

        $response->assertSessionHasErrors('progres_fisik_persen');
        $this->assertDatabaseCount('project_progress_reports', 0);
    }

    public function test_guest_cannot_access_progress_report_routes(): void
    {
        $proyek = $this->proyek();

        $this->get(route('project-progress.index'))->assertRedirect(route('login'));
        $this->get(route('project-progress.create', $proyek->uuid))->assertRedirect(route('login'));
        $this->post(route('project-progress.store', $proyek->uuid), [])->assertRedirect(route('login'));
    }
}
```

### 8.3 `tests/Feature/PembangunanDashboardTest.php` (baru)

| Test | Skenario |
| --- | --- |
| `test_dashboard_shows_aggregated_cards_for_selected_year` | 2 laporan (2 proyek berbeda, tahun sama) → kartu `total_pagu`/`total_realisasi`/`rata_progres_fisik` sesuai penjumlahan/rata-rata dua laporan terbaru. |
| `test_admin_opd_only_sees_their_own_opd_data_even_if_query_param_is_tampered` | Admin-opd akses `?opd_id=<opd lain>` → data yang tampil tetap hanya milik OPD sendiri (parameter diabaikan, bukan cuma disembunyikan). |
| `test_dashboard_filters_by_status` | Filter `status=terlambat` → hanya laporan berstatus itu yang muncul di tabel. |

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\ProjectProgressReport;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembangunanDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function grantProjectProgressView(Role $role): void
    {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'project-progress.view', 'guard_name' => 'web']));
    }

    private function proyek(?int $opdId, ?int $kategoriId = null): DataSpatial
    {
        $category = $kategoriId
            ? Category::find($kategoriId)
            : Category::create(['type' => 'tematik', 'nama' => 'Jalan Provinsi', 'warna' => '#0d6efd']);
        $uploader = User::factory()->create();

        return DataSpatial::factory()->create([
            'user_id' => $uploader->id,
            'kategori_id' => $category->id,
            'data_type' => 'proyek_strategis',
            'sub_type' => 'psd',
            'opd_pengelola_id' => $opdId,
        ]);
    }

    public function test_dashboard_shows_aggregated_cards_for_selected_year(): void
    {
        $role = Role::create(['slug' => 'admin-bappeda', 'name' => 'Admin Bappeda', 'description' => null]);
        $this->grantProjectProgressView($role);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $opd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);
        $proyekA = $this->proyek($opd->id);
        $proyekB = $this->proyek($opd->id);

        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekA->id,
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'pagu' => 1000000000,
            'realisasi_anggaran' => 500000000,
            'progres_fisik_persen' => 50,
            'status' => 'on_track',
        ]);
        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekB->id,
            'opd_id' => $opd->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'pagu' => 1000000000,
            'realisasi_anggaran' => 1000000000,
            'progres_fisik_persen' => 100,
            'status' => 'selesai',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.pembangunan', ['tahun' => 2026]));

        $response->assertOk();
        $response->assertViewHas('cards', function (array $cards) {
            return $cards['jumlah_dilaporkan'] === 2
                && (float) $cards['total_pagu'] === 2000000000.0
                && (float) $cards['total_realisasi'] === 1500000000.0
                && $cards['rata_progres_fisik'] === 75.0;
        });
    }

    public function test_admin_opd_only_sees_their_own_opd_data_even_if_query_param_is_tampered(): void
    {
        $ownOpd = Opd::create(['name' => 'Dinas Kesehatan', 'singkatan' => 'DINKES']);
        $otherOpd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);

        $role = Role::create(['slug' => 'admin-opd', 'name' => 'Admin OPD', 'description' => null]);
        $this->grantProjectProgressView($role);
        $admin = User::factory()->create(['role_id' => $role->id, 'opd_id' => $ownOpd->id]);

        $proyekMilikSendiri = $this->proyek($ownOpd->id);
        $proyekOpdLain = $this->proyek($otherOpd->id);

        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekMilikSendiri->id,
            'opd_id' => $ownOpd->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
        ]);
        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekOpdLain->id,
            'opd_id' => $otherOpd->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
        ]);

        // opd_id di query string sengaja diarahkan ke OPD lain — harus tetap diabaikan.
        $response = $this->actingAs($admin)->get(route('dashboard.pembangunan', ['tahun' => 2026, 'opd_id' => $otherOpd->id]));

        $response->assertOk();
        $response->assertViewHas('cards', fn (array $cards) => $cards['jumlah_dilaporkan'] === 1);
    }

    public function test_dashboard_filters_by_status(): void
    {
        $role = Role::create(['slug' => 'admin-bappeda', 'name' => 'Admin Bappeda', 'description' => null]);
        $this->grantProjectProgressView($role);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $opd = Opd::create(['name' => 'Dinas PUPR', 'singkatan' => 'PUPR']);
        $proyekTerlambat = $this->proyek($opd->id);
        $proyekOnTrack = $this->proyek($opd->id);

        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekTerlambat->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'status' => 'terlambat',
        ]);
        ProjectProgressReport::factory()->create([
            'data_spatial_id' => $proyekOnTrack->id,
            'tahun_anggaran' => 2026,
            'periode_laporan' => 'Triwulan 1',
            'status' => 'on_track',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.pembangunan', ['tahun' => 2026, 'status' => 'terlambat']));

        $response->assertOk();
        $response->assertViewHas('laporan', fn ($laporan) => $laporan->count() === 1 && $laporan->first()->status === 'terlambat');
    }
}
```

Jalankan dengan filter dulu (`php artisan test --compact --filter=ProjectProgressReportTest`, `--filter=PembangunanDashboardTest`), baru tawarkan regresi/full suite ke user.

## Tahap 9 — Revisi: Progres Keuangan Dapat Diperbarui (Manual, Disiapkan untuk INAPROC)

Mengambil business rules dari [`11-integrasi-inaproc.md`](../03_plan/11-integrasi-inaproc.md) Keputusan #8/#9 (satu proyek bisa punya banyak sumber angka; laporan boleh diperbarui dengan jejak audit), tapi **diimplementasikan tanpa satu pun bagian INAPROC** — tidak ada `inaproc_realisasi_reports`, `InaprocClient`, command sinkronisasi, atau tombol "Hubungkan ke Peta". Yang dibangun murni mekanisme "laporan boleh diperbarui + histori" secara manual, dengan skema yang sengaja generik supaya integrasi INAPROC nanti tinggal menambah cara BARU untuk *memicu* pembaruan (dari sinkronisasi), bukan membangun ulang cara *menyimpan* pembaruan.

### 9.1 Migration — kolom `sumber_data` di `project_progress_reports`

```php
Schema::table('project_progress_reports', function (Blueprint $table) {
    // 'manual' (default, input langsung admin) | 'inaproc' (nilai keuangan bersumber
    // sinkronisasi INAPROC, lihat docs/marimoi v2/03_plan/11-integrasi-inaproc.md —
    // kolom disiapkan sekarang, nilai 'inaproc' belum dipakai karena sinkronisasi
    // belum diimplementasikan).
    $table->string('sumber_data', 20)->default('manual')->after('status');
});
```

### 9.2 Migration — tabel baru `project_progress_report_revisions`

```php
Schema::create('project_progress_report_revisions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_progress_report_id')
        ->constrained('project_progress_reports')
        ->cascadeOnDelete();
    // Snapshot nilai (pagu, realisasi_anggaran, progres_fisik_persen, status, catatan)
    // sebelum ditimpa oleh pembaruan ini — bukan kolom per field supaya generik
    // terhadap field yang boleh diperbarui tanpa migration tambahan di kemudian hari.
    $table->json('data_sebelumnya');
    $table->foreignId('diperbarui_oleh')->constrained('users')->restrictOnDelete();
    $table->timestamp('created_at')->useCurrent();

    $table->index('project_progress_report_id');
});
```

### 9.3 Model `app/Models/ProjectProgressReportRevision.php` (baru)

```php
class ProjectProgressReportRevision extends Model
{
    public $timestamps = false;

    protected $fillable = ['project_progress_report_id', 'data_sebelumnya', 'diperbarui_oleh'];

    protected function casts(): array
    {
        return ['data_sebelumnya' => 'array', 'created_at' => 'datetime'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ProjectProgressReport::class, 'project_progress_report_id');
    }

    public function diperbaruiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperbarui_oleh');
    }
}
```

### 9.4 Tambahan di `app/Models/ProjectProgressReport.php`

```php
public const FIELD_DAPAT_DIPERBARUI = ['pagu', 'realisasi_anggaran', 'progres_fisik_persen', 'status', 'catatan'];

public const SUMBER_MANUAL = 'manual';
public const SUMBER_INAPROC = 'inaproc';

// 'sumber_data' ditambahkan ke $fillable

public function revisions(): HasMany
{
    return $this->hasMany(ProjectProgressReportRevision::class)->latest();
}
```

`store()` di `ProjectProgressController` diperbarui menyetel `'sumber_data' => ProjectProgressReport::SUMBER_MANUAL` eksplisit saat membuat laporan baru (konsisten dengan default kolom, tapi eksplisit di kode supaya jelas dibaca tanpa perlu buka migration).

### 9.5 `ProjectProgressController` — aksi `edit()`/`update()`

```php
private function findReportForProject(DataSpatial $proyek, int $reportId): ProjectProgressReport
{
    return ProjectProgressReport::where('data_spatial_id', $proyek->id)->findOrFail($reportId);
}

public function edit(string $uuid, int $report)
{
    $proyek = DataSpatial::where('uuid', $uuid)->proyekStrategis()->firstOrFail();
    $this->authorizeProject($proyek);
    $laporan = $this->findReportForProject($proyek, $report);

    return view('backend.pages.project-progress.edit', [
        'proyek' => $proyek,
        'laporan' => $laporan,
        'statuses' => ProjectProgressReport::STATUSES,
    ]);
}

public function update(Request $request, string $uuid, int $report)
{
    $proyek = DataSpatial::where('uuid', $uuid)->proyekStrategis()->firstOrFail();
    $this->authorizeProject($proyek);
    $laporan = $this->findReportForProject($proyek, $report);

    $validated = $request->validate([
        'pagu' => 'nullable|numeric|min:0',
        'realisasi_anggaran' => 'nullable|numeric|min:0',
        'progres_fisik_persen' => 'required|numeric|min:0|max:100',
        'status' => 'required|string|in:'.implode(',', ProjectProgressReport::STATUSES),
        'catatan' => 'nullable|string|max:2000',
    ]);

    $nilaiSebelumnya = $laporan->only(ProjectProgressReport::FIELD_DAPAT_DIPERBARUI);

    $laporan->revisions()->create([
        'data_sebelumnya' => $nilaiSebelumnya,
        'diperbarui_oleh' => Auth::id(),
    ]);

    $laporan->update($validated);

    return redirect()->route('project-progress.show', $proyek->uuid)->with('success', 'Laporan progres berhasil diperbarui.');
}
```

`findReportForProject()` sengaja mencari lewat `data_spatial_id = $proyek->id` (bukan `ProjectProgressReport::findOrFail($report)` polos) — mencegah admin-opd mengedit laporan proyek OPD lain hanya dengan menebak `report` ID di URL sambil memakai `uuid` proyek miliknya sendiri (IDOR). `authorizeProject()` yang sudah ada dari Tahap 3 tetap jadi lapisan otorisasi utama; pencarian scoped ini lapisan kedua.

### 9.6 Routes tambahan di `routes/backend.php`

```php
Route::get('/{uuid}/laporan/{report}/edit', [ProjectProgressController::class, 'edit'])->name('laporan.edit')->middleware('permission:project-progress.edit');
Route::put('/{uuid}/laporan/{report}', [ProjectProgressController::class, 'update'])->name('laporan.update')->middleware('permission:project-progress.edit');
```

Ditambahkan di dalam grup `Route::prefix('project-progress')->name('project-progress.')` yang sudah ada (Tahap 3.2) — otomatis dapat prefix URI `/dashboard/project-progress/*` yang sama.

### 9.7 Permission baru `project-progress.edit`

`app/Models/Permission.php` — tambah `'edit' => 'Perbarui laporan progres yang sudah ada'` ke `actions` modul `project-progress`.

`database/seeders/PermissionSeeder.php` — tambah `'project-progress.edit'` ke `DEFAULTS['admin-opd']` (admin-bappeda sudah otomatis dapat lewat wildcard `project-progress.*` yang sudah ada).

### 9.8 View: `show.blade.php` (badge riwayat + tombol Perbarui) dan `edit.blade.php` (baru)

`show.blade.php` — tiap baris laporan yang punya `revisions` menampilkan badge `Diperbarui {N}×` (tooltip: kapan & oleh siapa terakhir), plus kolom aksi baru berisi tombol "Perbarui" (gerbang `@can('project-progress.edit')`) mengarah ke `project-progress.laporan.edit`. Controller `show()` diperbarui eager-load `revisions` sekaligus (`$proyek->progressReports()->with(['pelapor:id,name', 'revisions'])->get()`) supaya badge tidak memicu N+1 query.

`edit.blade.php` (baru) — form serupa `create.blade.php` tapi **tanpa** field `tahun_anggaran`/`periode_laporan` (ditampilkan sebagai teks statis, sesuai Keputusan #11), pre-filled dari `$laporan`, submit ke `project-progress.laporan.update` dengan `@method('PUT')`.

### 9.9 Test baru di `tests/Feature/ProjectProgressReportTest.php`

4 test ditambahkan (total file jadi 10 test):

| Test | Skenario |
| --- | --- |
| `test_admin_bappeda_can_update_existing_report_and_a_revision_is_recorded` | Update `progres_fisik_persen`/`realisasi_anggaran` → nilai baru tersimpan, `sumber_data` tetap `'manual'`, 1 baris revisi tercatat dengan nilai lama yang benar di `data_sebelumnya`. |
| `test_admin_opd_cannot_update_report_belonging_to_other_opd_project` | Admin-opd coba update laporan proyek OPD lain → `403`, tidak ada revisi tercatat. |
| `test_update_rejects_out_of_range_percentage_and_keeps_original_value` | `progres_fisik_persen = 130` → validasi gagal, nilai lama di DB tidak berubah, tidak ada revisi tercatat. |
| `test_user_without_edit_permission_cannot_update_report` | User dengan `project-progress.create` tapi **tanpa** `.edit` → `403` saat akses form edit. |

Seluruh 10 test `ProjectProgressReportTest` (36 assertion) lulus di run pertama; regresi `PembangunanDashboardTest`/`RolePermissionTest` (29 test total) tetap lulus tanpa gangguan.

## Urutan Eksekusi

1. Konfirmasi Keputusan #1–#10 di atas, khususnya #6 (asumsi input berurutan untuk "laporan terbaru") dan #7 (definisi "bermasalah" = status `terlambat` manual).
2. Migration Tahap 1.1 (`php artisan migrate`) + model `ProjectProgressReport` (1.2) + relasi `DataSpatial::progressReports()` (1.3).
3. Permission catalog + seeder (Tahap 2) — jalankan `php artisan db:seed --class=PermissionSeeder` di lingkungan development/test (lihat catatan penting soal role lama di 2.2).
4. Backend `ProjectProgressController` + routes (Tahap 3).
5. Views Tahap 4 (index/create/show) — verifikasi manual: buka `/dashboard/project-progress`, tambah laporan, lihat histori.
6. Backend `PembangunanDashboardController` + route (Tahap 5).
7. View dashboard pembangunan + grafik (Tahap 6).
8. Sidebar (Tahap 7).
9. `vendor/bin/pint --dirty --format agent` untuk seluruh file PHP yang disentuh.
10. Factory + test Tahap 8, jalankan per filter, pastikan lulus.
11. **Khusus lingkungan yang sudah punya role `admin-bappeda`/`admin-opd` tersimpan** (staging/produksi, bukan `RefreshDatabase` test): jalankan manual untuk menambahkan permission baru ke role yang sudah ada, misalnya lewat `php artisan tinker --execute '\App\Models\Role::where("slug","admin-bappeda")->first()->givePermissionTo(["project-progress.view","project-progress.create"]);'` dan sejenisnya untuk `admin-opd` (`project-progress.view`, `project-progress.create`) — karena `PermissionSeeder::run()` melewati role yang sudah punya permission tersimpan (lihat Tahap 2.2).
12. Verifikasi manual: login sebagai admin-opd, pastikan hanya melihat proyek & data dashboard OPD sendiri; login sebagai admin-bappeda/super-admin, pastikan melihat semua.
13. Tawarkan full test suite ke user.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Role lama (`admin-bappeda`/`admin-opd`) di lingkungan yang sudah berjalan tidak otomatis dapat permission `project-progress.*` setelah seeder diubah | Dicatat eksplisit di Tahap 2.2 dan Urutan Eksekusi langkah 11 — perlu langkah manual `givePermissionTo()` sekali per lingkungan yang sudah punya data role. |
| "Laporan terbaru" berbasis `MAX(id)`, bukan urutan periode kronologis (Keputusan #6) | Diterima sebagai batasan iterasi pertama; workflow yang diharapkan adalah input berurutan per triwulan. Bila terbukti admin sering mengisi tidak berurutan, migrasi ke window function `ROW_NUMBER()` di iterasi berikutnya — perubahan terlokalisasi di satu query (`PembangunanDashboardController::index()`), tidak menyentuh skema. |
| ~~Append-only tanpa edit/hapus (Keputusan #4) berarti kesalahan input butuh intervensi manual DB~~ — **diselesaikan Tahap 9**: laporan sekarang bisa diperbarui lewat `project-progress.edit`, dengan jejak audit. Tidak ada aksi hapus laporan (dianggap belum perlu — koreksi cukup lewat "Perbarui"). | Selesai. Bila kebutuhan "hapus laporan" (bukan sekadar perbarui) muncul nanti, tinggal tambah `project-progress.delete` + `destroy()` mengikuti pola yang sama, `revisions` yang sudah ada tetap jadi jejak sebelum baris dihapus. |
| Role lama (`admin-bappeda`/`admin-opd`) di lingkungan yang sudah berjalan tidak otomatis dapat permission `project-progress.edit` yang baru ditambahkan Tahap 9 | Sama seperti risiko permission Tahap 1-8 di atas — perlu langkah manual `givePermissionTo('project-progress.edit')` sekali per lingkungan yang role-nya sudah tersimpan sebelum revisi ini. |
| Snapshot `data_sebelumnya` di `project_progress_report_revisions` berupa JSON, bukan kolom eksplisit — sedikit lebih sulit di-query/agregasi lewat SQL biasa dibanding rancangan awal `11-integrasi-inaproc.md` (kolom `_sebelumnya` per field) | Diterima (Keputusan #12) — tabel ini murni log tampilan ("apa yang berubah, kapan, siapa"), bukan sumber agregasi laporan/dashboard. Bila kebutuhan query historis kompleks muncul nanti, JSON Postgres tetap bisa di-query (`->>'field'`) tanpa migration ulang. |
| Threshold "bermasalah" = status manual `terlambat`, bergantung kedisiplinan admin mengisi status dengan benar | Diterima sebagai desain sederhana MVP (Keputusan #7); heuristik otomatis berbasis kalender fiskal didorong ke iterasi berikutnya, butuh model periode anggaran yang belum ada di sistem manapun saat ini. |
| Query agregasi dashboard (`MAX(id)` per proyek, lalu `whereIn`) berpotensi lambat bila jumlah proyek/laporan sangat besar | Index `(opd_id, tahun_anggaran)` dan `(kategori_id, tahun_anggaran)` sudah ditambahkan di migration (Tahap 1.1); belum diuji dengan volume data produksi sungguhan — dicatat di "Belum Diverifikasi" setelah implementasi berjalan. |
| `data_spatial.opd_pengelola_id` bisa `null` untuk proyek lama yang diinput sebelum bagian A (metadata dataset) selesai | Proyek tanpa OPD hanya bisa dilaporkan oleh admin-bappeda/super-admin (Keputusan #3) — bukan bug, konsisten dengan keterbatasan data lama yang sudah dicatat di `03-metadata-dataset.md`. |
| Dua controller baru (`ProjectProgressController`, `PembangunanDashboardController`) menambah permukaan kode yang perlu dipelihara terpisah dari `DashboardController` yang sudah ada | Keputusan sadar (bukan risiko tak terduga) — `DashboardController` sudah 766 baris sarat urusan aspirasi/pengunjung; memisahkan controller baru mengikuti pola yang sudah dipakai di codebase (`ProjectFeedbackController`, `OpdController`, dst., semuanya terpisah dari `DashboardController`). |

## Kriteria Selesai

- [x] User (admin-bappeda/super-admin/admin-opd sesuai scope) bisa menambah laporan progres (pagu, realisasi, persen fisik, status) untuk proyek strategis, dan laporan baru menambah baris tanpa menimpa riwayat sebelumnya — diimplementasikan di `ProjectProgressController::store()`, diuji `test_admin_bappeda_can_add_progress_report_for_any_project`/`test_admin_opd_can_add_progress_report_for_their_own_project`.
- [x] Dashboard Pembangunan menampilkan capaian fisik, realisasi keuangan, dan status proyek — bukan lagi cuma statistik aspirasi/pengunjung yang sudah ada di `DashboardController`. Controller & view terpisah (`PembangunanDashboardController`, `dashboard-pembangunan.blade.php`).
- [x] Angka di kartu ringkasan bisa ditelusuri ke laporan progres sumbernya — tautan "Detail" di tabel dashboard mengarah ke `project-progress.show` (histori lengkap per proyek).
- [x] Filter wilayah (OPD)/sektor (kategori)/tahun/status konsisten antara kartu ringkasan, grafik, dan tabel — satu query (`$laporan`) dipakai untuk ketiganya di `PembangunanDashboardController::index()`.
- [x] Admin-opd tidak bisa melihat atau melaporkan data progres proyek OPD lain — ditegakkan di server (`authorizeProject()` di `ProjectProgressController`, paksa `$opdId` di `PembangunanDashboardController`), bukan cuma disembunyikan di UI. Diuji `test_admin_opd_cannot_add_progress_report_for_other_opd_project` dan `test_admin_opd_only_sees_their_own_opd_data_even_if_query_param_is_tampered` (parameter `opd_id` sengaja di-tamper di test, tetap terabaikan).
- [x] Seluruh test Tahap 8 lulus — `ProjectProgressReportTest` (6 test) dan `PembangunanDashboardTest` (3 test), total 9 test/26 assertion, termasuk uji constraint duplikat periode dan validasi rentang persentase. Regresi permission/geojson terkait (35 test) tetap lulus.
- [x] **(Tahap 9)** Satu proyek peta bisa punya laporan progres keuangan yang historis **dan** diperbarui — laporan yang sudah tersimpan bisa diedit (`pagu`/`realisasi_anggaran`/`progres_fisik_persen`/`status`/`catatan`) lewat `project-progress.laporan.update`, setiap pembaruan tercatat ke `project_progress_report_revisions` dengan nilai lama utuh, `tahun_anggaran`/`periode_laporan` (identitas laporan) tidak bisa diubah. Diuji `test_admin_bappeda_can_update_existing_report_and_a_revision_is_recorded`.
- [x] **(Tahap 9)** Skema disiapkan untuk integrasi INAPROC nanti tanpa membangun satu pun bagian INAPROC sekarang — kolom `sumber_data` (`'manual'`/`'inaproc'`, seluruhnya `'manual'` saat ini) dan mekanisme pembaruan+revisi generik, sesuai permintaan eksplisit "versi manual, disimpan untuk nanti". Rujukan lengkap integrasi sungguhan: [`11-integrasi-inaproc.md`](../03_plan/11-integrasi-inaproc.md).
- [x] **(Tahap 9)** Otorisasi pembaruan konsisten dengan pola yang sudah ada — admin-opd tidak bisa memperbarui laporan proyek OPD lain (`403`, diuji `test_admin_opd_cannot_update_report_belonging_to_other_opd_project`), dan permission `project-progress.edit` terpisah dari `.create` (diuji `test_user_without_edit_permission_cannot_update_report`).
- [ ] **Verifikasi manual di browser belum dilakukan** — tampilan kartu, filter dropdown, grafik Chart.js, tabel proyek, badge "Diperbarui Nx", dan form edit/perbarui belum pernah dibuka di browser sungguhan pada sesi ini.
- [ ] **Assign permission manual untuk role lama di lingkungan lain** (staging/produksi) belum dilakukan — berlaku juga untuk `project-progress.edit` yang baru ditambahkan Tahap 9, bukan cuma permission dari Tahap 1-8 (lihat Urutan Eksekusi langkah 11 dan Risiko).
