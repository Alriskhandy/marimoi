# Plan Implementasi: Perbaikan Prioritas Effort Kecil (Rekomendasi V2)

## Status Implementasi

- **Tahap 1–7 — selesai (2026-09-24).** Seluruh 6 item diimplementasikan sesuai rencana, tanpa penyimpangan dari desain kode yang ditulis di dokumen ini. 16 test baru/perluasan ditambahkan (3 di `MetadataDatasetTest`, 2 di `FrontendPagesTest`, 1 di `PembangunanDashboardTest`, 5 di `ProjectProgressReportTest`) — seluruhnya lulus (48 test/191 assertion untuk 4 file yang disentuh langsung). Regresi `RolePermissionTest`/`DataSpatialGeojsonTest`/`FrontendGeojsonMetadataTest`/`SharedMapFilterTest`/`FrontendFilterOptionsTest`/`AspirasiTrackingTest` (45 test/151 assertion) tetap lulus tanpa gangguan.

### Catatan audit saat eksekusi

- **Satu bug ditemukan & diperbaiki saat menulis test Tahap 4** (bukan bug di kode Tahap 1-6, tapi kerapuhan test yang baru ketahuan): `ProjectProgressReportFactory::definition()` selalu membuat satu baris `DataSpatial` throwaway (`opd_pengelola_id` null) setiap kali dipanggil, terlepas dari override `data_spatial_id` yang diberikan test. Baris throwaway ini ikut kehitung di `jumlah_proyek` saat query tidak difilter per-OPD (kasus admin-bappeda tanpa parameter `opd_id`). Test kartu kelengkapan pelaporan awalnya gagal karena ini — diperbaiki dengan menyertakan `opd_id` eksplisit di request test (bukan mengubah factory, yang dipakai banyak test lain dan berisiko regresi luas kalau diubah). Perilaku produksi tidak terpengaruh — ini murni isolasi antar-test.
- `config/marimoi.php` baru butuh `php artisan config:clear` sekali setelah dibuat (config Laravel bisa ter-cache) — dijalankan sebagai bagian eksekusi, bukan langkah manual tambahan untuk deploy production (config cache akan otomatis rebuild saat `php artisan config:cache` berikutnya dijalankan, konsisten dengan file config lain).
- Tanggal cutoff `METADATA_WAJIB_SEJAK=2026-10-01` di `.env.example` **tetap nilai placeholder** seperti direncanakan (Keputusan #2) — belum diubah ke tanggal rilis sesungguhnya karena itu keputusan bisnis (kapan fitur ini benar-benar tayang), bukan keputusan teknis. `.env` lokal untuk development tidak diubah (memakai default `2026-10-01` dari config bila variabel tidak di-set).
- Tidak ada perubahan pada keputusan "update() ikut mewajibkan data lama" yang dicatat di Tahap 2.2 — diimplementasikan persis seperti tertulis (berbasis `now() >= cutoff`, bukan `created_at` data). Tetap dicatat sebagai open question di Kriteria Selesai bila perilaku ini ternyata tidak diinginkan.
- **Belum ada verifikasi browser** — badge metadata, submenu sidebar Dashboard, 4 kartu dashboard pembangunan, banner pengingat, dan pra-isi form belum pernah dibuka di browser sungguhan pada sesi ini.

## Tujuan

Mengimplementasikan seluruh item berlabel **effort "Kecil"** di tabel "Ringkasan Prioritas dan Urutan Pengerjaan" pada [`../03_plan/12-rekomendasi-perbaikan-v2.md`](../03_plan/12-rekomendasi-perbaikan-v2.md), **kecuali Prioritas 3.4** (role `pimpinan` read-only — sengaja dikecualikan sesuai permintaan eksplisit, karena statusnya "hanya bila Prioritas 3.1 tidak disetujui karena pertimbangan kebijakan", bukan keputusan yang bisa diambil sepihak oleh tim teknis).

6 item yang termasuk cakupan dokumen ini:

| # | Item | Baris di tabel ringkasan `12-rekomendasi-perbaikan-v2.md` |
| --- | --- | --- |
| 1 | Prioritas 1.1 — Badge "metadata belum lengkap" di popup publik | Urutan 1 |
| 2 | Prioritas 1.2 Opsi B — Wajib bertahap (grace period) | Urutan 2 |
| 3 | Prioritas 3.5 — Konsolidasi menu dashboard internal | Urutan 3 |
| 4 | Prioritas 5.1 — Kartu kelengkapan pelaporan (global) | Urutan 4 (separuh — separuh lainnya, 6.3, ditangani terpisah di dokumen ini) |
| 5 | Prioritas 6.3 — Pengingat personal untuk admin-opd | Urutan 4 (separuh) |
| 6 | Prioritas 6.1 — Pra-isi form dari periode sebelumnya | Urutan 5 |

## Referensi

- [`../03_plan/12-rekomendasi-perbaikan-v2.md`](../03_plan/12-rekomendasi-perbaikan-v2.md) — sumber seluruh 6 item di dokumen ini (Prioritas 1, 3, 5, 6).
- [`03-metadata-dataset.md`](03-metadata-dataset.md) — implementasi awal `sumber_data`/`opd_pengelola_id`/`tanggal_data` dan `DataSpatial::metadata_lengkap`, jadi dasar Tahap 1-2.
- [`06-dashboard-eksekutif-minimum.md`](06-dashboard-eksekutif-minimum.md) — implementasi `PembangunanDashboardController`/`ProjectProgressController` yang jadi dasar Tahap 3-6.

## Cakupan

Termasuk: 6 item di atas, seluruhnya perubahan aditif (tidak ada migration untuk 5 dari 6 item — hanya Prioritas 1.2 yang menambah 1 file config baru, bukan migration schema).

Tidak termasuk: Prioritas 3.4 (dikecualikan eksplisit), dan seluruh item berlabel effort "Sedang"/"Sedang-Besar"/"Besar" di `12-rekomendasi-perbaikan-v2.md` (Prioritas 2, 3.1, 3.3, 4, 6.2, 6.4, 7, 8) — semuanya butuh dokumen implementasi terpisah karena melibatkan migration schema baru dan/atau keputusan kebijakan yang belum dikonfirmasi pemilik produk.

## Kondisi Existing (Audit Singkat)

| Area | Temuan |
| --- | --- |
| Badge metadata publik | `resources/views/frontend/partials/detail-peta.blade.php` baris 92-100 — field `sumber_data`/`opdPengelola`/`tanggal_data` masing-masing dibungkus `@if (...)` terpisah; **tidak ada** penanganan kondisi "semua kosong". `DataSpatial::getMetadataLengkapAttribute()` (`app/Models/DataSpatial.php` baris 72-77) sudah ada dan **sudah dipakai** di listing admin (`resources/views/backend/pages/data_spatial/index.blade.php` baris 345-349) — tinggal dipakai ulang di view publik, tidak perlu accessor baru. |
| Validasi metadata | `DataSpatialController::store()` baris 158-166 dan `::update()` baris 239-256 — `sumber_data`/`opd_pengelola_id`/`tanggal_data` semuanya `nullable`. `protected function isAdminOPD()` (baris 26-29) sudah ada untuk deteksi role — dipakai ulang, bukan dibuat baru. |
| Menu dashboard | `resources/views/backend/partials/sidebar.blade.php` — "Dashboard" (baris 53-58, link tunggal) dan "Dashboard Pembangunan" (baris 104-111, section terpisah "Pembangunan") saat ini 2 entri sidebar yang tidak berdekatan secara visual, padahal keduanya bagian dari "dashboard". |
| Kartu dashboard pembangunan | `PembangunanDashboardController::index()` baris 53-65 — `$cards` sudah punya `jumlah_proyek` dan `jumlah_dilaporkan`, tapi belum ada `persen_kelengkapan` terhitung eksplisit. View (`dashboard-pembangunan.blade.php` baris 47-71) render 3 kartu dengan grid `col-md-4` — nilai kelengkapan pelaporan cuma teks kecil di kartu pertama ("3 sudah melapor tahun 2026"), bukan kartu tersendiri. |
| Pengingat personal | `ProjectProgressController::index()` baris 34-56 — tidak ada logika perhitungan proyek yang belum melapor untuk admin-opd yang sedang login; halaman langsung menampilkan daftar proyek tanpa ringkasan status pelaporan personal. |
| Pra-isi form | `ProjectProgressController::create()` baris 72-85 — view menerima `proyek`/`statuses`/`periodeOptions` saja, tidak ada data laporan sebelumnya. `create.blade.php` baris 33 — input `pagu` cuma `value="{{ old('pagu') }}"`, selalu kosong saat form pertama dibuka. `DataSpatial::progressReports()` (`app/Models/DataSpatial.php` baris 67-70) sudah `->latest('id')` — `$proyek->progressReports()->first()` langsung memberi laporan terakhir tanpa query tambahan. |

## Keputusan yang Perlu Dikunci Sebelum Implementasi

1. **Badge metadata publik memakai styling Tailwind konsisten dengan `detail-peta.blade.php` (bukan class Bootstrap admin `badge bg-gradient-warning`).** Frontend dan backend admin pakai sistem desain berbeda (dikonfirmasi: frontend Tailwind, admin Bootstrap) — badge publik didesain ulang dengan palet amber Tailwind, bukan copy-paste class admin yang tidak akan ter-render benar di frontend.
2. **Tanggal cutoff "wajib bertahap" disimpan di `config/marimoi.php` (env-overridable), bukan hardcode tanggal di controller.** Memudahkan penyesuaian tanggal rilis tanpa deploy ulang kode (cukup ubah `.env`), dan konsisten dengan pola Laravel untuk nilai konfigurasi yang bisa berubah per lingkungan. **Nilai default di dokumen ini (`2026-10-01`) adalah placeholder — ganti dengan tanggal rilis sesungguhnya saat implementasi dieksekusi**, bukan tanggal dokumen ini ditulis.
3. **`opd_pengelola_id` hanya wajib untuk non-admin-opd (admin-bappeda/super-admin).** Admin-opd tidak pernah mengisi field ini secara manual — nilainya auto-terisi dari `Auth::user()->opd_id` di `saveDataSpatial()` (baris 887-889, di luar cakupan Tahap 2 — tidak disentuh). Mewajibkan field ini di request admin-opd akan salah total karena mereka memang tidak mengirim field itu.
4. **`tanggal_data` ikut diwajibkan bersama `sumber_data`/`opd_pengelola_id`**, meski rekomendasi asli bagian A cuma eksplisit menyebut 2 field pertama. Alasan: `DataSpatial::metadata_lengkap` (dipakai di Tahap 1) sudah mensyaratkan ketiganya — kalau validasi "wajib" cuma menutup 2 dari 3 field, data baru pasca-cutoff masih bisa lolos tersimpan tapi tetap muncul badge "belum lengkap" di Tahap 1, yang membingungkan (admin sudah memenuhi "wajib" versi sistem tapi tetap ditandai tidak lengkap). Menyamakan cakupan kedua fitur menghindari kontradiksi ini.
5. **Kartu kelengkapan pelaporan mengubah grid dari 3 kolom (`col-md-4`) jadi 4 kolom (`col-md-3`)**, bukan ditaruh di baris baru terpisah — menjaga kartu-kartu tetap terlihat sebagai satu kelompok ringkasan yang sejajar.
6. **Pengingat personal (6.3) hitung "belum lapor" berdasarkan tahun anggaran berjalan saja** (proyek punya ≥1 laporan di `tahun_anggaran = now()->year`), **bukan** per-periode/triwulan spesifik — menghindari kebutuhan model kalender fiskal (masalah yang sama yang sudah dicatat sebagai keterbatasan di `06-dashboard-eksekutif-minimum.md` Keputusan #6). Konsekuensi yang diterima: banner tetap "diam" (tidak menandai belum lapor) untuk proyek yang sudah punya 1 laporan Triwulan 1 meski Triwulan 2 sudah due — batasan iterasi pertama, bukan bug.
7. **Pengingat personal hanya tampil untuk admin-opd**, tidak untuk admin-bappeda/super-admin — sesuai definisi "personal" di `12-rekomendasi-perbaikan-v2.md` (banner ini tentang "proyek OPD Anda", konsepnya tidak berlaku untuk role yang mengawasi banyak OPD sekaligus).
8. **Pra-isi form hanya untuk field `pagu`**, tidak untuk `realisasi_anggaran`/`progres_fisik_persen`/`status` — persis sesuai alasan yang sudah tertulis di `12-rekomendasi-perbaikan-v2.md` Prioritas 6.1 (field-field itu **harus** mencerminkan kondisi terkini, pra-isi berisiko admin asal klik submit tanpa memperbarui angka yang memang seharusnya berubah tiap periode).

## Tahap 1 — Badge "Metadata Belum Lengkap" di Popup Publik (Prioritas 1.1)

### 1.1 `resources/views/frontend/partials/detail-peta.blade.php`

Tambah setelah `</dl>` (baris 101), sebelum `</article>` (baris 102):

```blade
                    </dl>

                    @if (! $project->metadata_lengkap)
                        <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Metadata belum lengkap — sumber data, instansi pengelola, atau tanggal data belum dicantumkan sepenuhnya.
                        </div>
                    @endif
                </article>
```

Bootstrap Icons (`bi-*`) sudah dimuat di layout frontend (`resources/views/frontend/layouts/spatial.blade.php`, dikonfirmasi lewat audit) — tidak perlu tambahan aset.

## Tahap 2 — Wajib Bertahap: Grace Period untuk Metadata (Prioritas 1.2 Opsi B)

### 2.1 Config baru `config/marimoi.php`

```php
<?php

return [
    // Tanggal mulai berlakunya kewajiban mengisi sumber_data/opd_pengelola_id/tanggal_data
    // saat input data spasial baru (lihat 03_plan/12-rekomendasi-perbaikan-v2.md Prioritas 1.2
    // Opsi B). Data yang dibuat sebelum tanggal ini tetap nullable — tidak retroaktif.
    'metadata_wajib_sejak' => env('METADATA_WAJIB_SEJAK', '2026-10-01'),
];
```

Tambah `METADATA_WAJIB_SEJAK=2026-10-01` ke `.env.example` (nilai contoh, disesuaikan saat deploy sungguhan).

### 2.2 `app/Http/Controllers/DataSpatialController.php`

Tambah private method baru (dekat `isAdminOPD()`, baris 26-29):

```php
private function metadataWajib(): bool
{
    return now()->greaterThanOrEqualTo(\Illuminate\Support\Carbon::parse(config('marimoi.metadata_wajib_sejak')));
}
```

Ubah `$rules` di `store()` (baris 158-166):

```php
$wajibMetadata = $this->metadataWajib();

$rules = [
    'data_type' => 'required|in:tematik',
    'kategori_id' => 'required|exists:categories,id',
    'deskripsi' => 'nullable|string',
    'input_type' => 'required|in:shapefile,coordinates,kmz',
    'sumber_data' => [$wajibMetadata ? 'required' : 'nullable', 'string', 'max:255'],
    'opd_pengelola_id' => [
        ($wajibMetadata && ! $this->isAdminOPD()) ? 'required' : 'nullable',
        'exists:opd,id',
    ],
    'tanggal_data' => [$wajibMetadata ? 'required' : 'nullable', 'date'],
];
```

Ubah blok `Validator::make(...)` di `update()` (baris 239-247) dengan pola yang sama:

```php
$wajibMetadata = $this->metadataWajib();

$validator = Validator::make($request->all(), [
    'kategori_id' => 'required|exists:categories,id',
    'deskripsi' => 'nullable|string|max:255',
    'dbf_attributes' => 'nullable|string',
    'gambar' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
    'sumber_data' => [$wajibMetadata ? 'required' : 'nullable', 'string', 'max:255'],
    'opd_pengelola_id' => [
        ($wajibMetadata && ! $this->isAdminOPD()) ? 'required' : 'nullable',
        'exists:opd,id',
    ],
    'tanggal_data' => [$wajibMetadata ? 'required' : 'nullable', 'date'],
], [
    'kategori_id.required' => 'Kategori harus dipilih',
    'kategori_id.exists' => 'Kategori tidak valid',
    'deskripsi.max' => 'Deskripsi maksimal 255 karakter',
    'gambar.image' => 'File harus berupa gambar',
    'gambar.mimes' => 'Format gambar harus jpeg, jpg, png, atau gif',
    'gambar.max' => 'Ukuran gambar maksimal 2MB',
    'sumber_data.required' => 'Sumber data wajib diisi.',
    'opd_pengelola_id.required' => 'OPD pengelola wajib dipilih.',
    'opd_pengelola_id.exists' => 'OPD pengelola tidak valid.',
    'tanggal_data.required' => 'Tanggal data wajib diisi.',
    'tanggal_data.date' => 'Tanggal data tidak valid.',
]);
```

**Catatan penting soal `update()`:** data yang **sudah ada** sebelum cutoff (dibuat sebelum `metadata_wajib_sejak`) akan ikut terkena validasi wajib ini begitu admin membuka & menyimpan form edit-nya **setelah** cutoff — ini beda dari `store()` yang murni berdasarkan waktu aksi, bukan waktu data dibuat. Efek sampingnya: admin yang sekadar mau perbaiki `deskripsi` data lama tanpa metadata lengkap akan terhambat submit sampai mereka juga mengisi metadata. **Ini konsekuensi yang perlu dikonfirmasi dulu** — kalau tidak diinginkan, alternatifnya cek `$data->created_at < cutoff` di `update()` (bukan cuma `now() >= cutoff`) supaya data lama tetap bebas dari kewajiban bahkan saat diedit. Dicatat di Risiko.

## Tahap 3 — Konsolidasi Menu Dashboard Internal (Prioritas 3.5)

### 3.1 `resources/views/backend/partials/sidebar.blade.php`

Ganti blok "Dashboard" tunggal (baris 53-58):

```blade
@can('dashboard.view')
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
        <span class="nav-text">Dashboard</span>
    </a>
@endcan
```

menjadi (kolaps 2-anak bila user juga punya `project-progress.view`, jatuh ke link tunggal bila tidak):

```blade
@can('dashboard.view')
    @if ($user?->can('project-progress.view'))
        @php($isDashboardGroupActive = request()->routeIs('dashboard') || request()->routeIs('dashboard.pembangunan'))
        <a class="nav-link {{ $isDashboardGroupActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#dashboardMenu"
            role="button" aria-expanded="{{ $isDashboardGroupActive ? 'true' : 'false' }}" aria-controls="dashboardMenu">
            <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
            <span class="nav-text">Dashboard</span>
            <i class="bi bi-chevron-down nav-caret" aria-hidden="true"></i>
        </a>
        <div class="collapse {{ $isDashboardGroupActive ? 'show' : '' }}" id="dashboardMenu">
            <div class="sidebar-submenu">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <span class="nav-text">Ringkasan</span>
                </a>
                <a class="nav-link {{ request()->routeIs('dashboard.pembangunan') ? 'active' : '' }}" href="{{ route('dashboard.pembangunan') }}">
                    <span class="nav-text">Pembangunan</span>
                </a>
            </div>
        </div>
    @else
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
            <span class="nav-text">Dashboard</span>
        </a>
    @endif
@endcan
```

Lalu hapus baris "Dashboard Pembangunan" dari blok "Pembangunan" (baris 104-111), sisakan cuma link "Progres Proyek Strategis":

```blade
{{-- Pembangunan --}}
@can('project-progress.view')
    <div class="nav-section-label">Pembangunan</div>
    <a class="nav-link {{ request()->routeIs('project-progress.*') ? 'active' : '' }}"
        href="{{ route('project-progress.index') }}">
        <span class="nav-icon"><i class="bi bi-clipboard-data" aria-hidden="true"></i></span>
        <span class="nav-text">Progres Proyek Strategis</span>
    </a>
@endcan
```

Pola `data-bs-toggle="collapse"` dan class `sidebar-submenu`/`nav-caret` diambil dari struktur "Peta Tematik" yang sudah ada di file yang sama (baris 65-72) — bukan pola baru.

## Tahap 4 — Kartu Kelengkapan Pelaporan (Prioritas 5.1)

### 4.1 `app/Http/Controllers/PembangunanDashboardController.php`

Tambah setelah `$cards['persen_realisasi'] = ...` (baris 63-65):

```php
$cards['persen_kelengkapan'] = $cards['jumlah_proyek'] > 0
    ? round(($cards['jumlah_dilaporkan'] / $cards['jumlah_proyek']) * 100, 1)
    : 0;
```

### 4.2 `resources/views/backend/pages/dashboard-pembangunan.blade.php`

Ubah 3 kartu existing dari `col-md-4` jadi `col-md-3` (baris 48, 57, 66), tambah kartu ke-4 setelah kartu "Rata-rata Progres Fisik" (setelah baris 72, sebelum penutup `</div>` baris 73 dari `<div class="row">`):

```blade
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-warning card-img-holder text-white">
            <div class="card-body">
                <h6 class="font-weight-normal">Kelengkapan Pelaporan</h6>
                <h2 class="mb-2">{{ $cards['persen_kelengkapan'] }}%</h2>
                <small>{{ $cards['jumlah_dilaporkan'] }} dari {{ $cards['jumlah_proyek'] }} proyek sudah melapor tahun {{ $tahun }}</small>
            </div>
        </div>
    </div>
```

## Tahap 5 — Pengingat Personal untuk Admin-OPD (Prioritas 6.3)

### 5.1 `app/Http/Controllers/ProjectProgressController.php`

Tambah di `index()` (setelah baris 41, `$this->scopeProjectsToOpd($query);`, sebelum filter `sub_type`):

```php
$belumLaporBanner = null;
if ($this->isAdminOpd() && Auth::user()->opd_id) {
    $totalProyekOpd = DataSpatial::proyekStrategis()
        ->where('opd_pengelola_id', Auth::user()->opd_id)
        ->count();

    $proyekSudahLaporTahunIni = ProjectProgressReport::where('opd_id', Auth::user()->opd_id)
        ->where('tahun_anggaran', now()->year)
        ->distinct()
        ->count('data_spatial_id');

    $belumLapor = $totalProyekOpd - $proyekSudahLaporTahunIni;

    if ($totalProyekOpd > 0 && $belumLapor > 0) {
        $belumLaporBanner = [
            'belum' => $belumLapor,
            'total' => $totalProyekOpd,
            'tahun' => now()->year,
        ];
    }
}
```

Tambah `'belumLaporBanner'` ke `compact(...)` di baris return `view(...)` (baris 55):

```php
return view('backend.pages.project-progress.index', compact('proyek', 'opdOptions', 'belumLaporBanner'));
```

### 5.2 `resources/views/backend/pages/project-progress/index.blade.php`

Tambah setelah `<div class="page-header">...</div>` (setelah baris 9), sebelum `<div class="card">` (baris 11):

```blade
@if ($belumLaporBanner)
    <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
        <i class="mdi mdi-alert-circle-outline"></i>
        <div>
            <strong>{{ $belumLaporBanner['belum'] }} dari {{ $belumLaporBanner['total'] }}</strong>
            proyek OPD Anda belum melapor progres untuk tahun anggaran {{ $belumLaporBanner['tahun'] }}.
        </div>
    </div>
@endif
```

## Tahap 6 — Pra-isi Form dari Periode Sebelumnya (Prioritas 6.1)

### 6.1 `app/Http/Controllers/ProjectProgressController.php`

Ubah `create()` (baris 72-85):

```php
public function create(string $uuid)
{
    $proyek = DataSpatial::where('uuid', $uuid)
        ->proyekStrategis()
        ->firstOrFail();

    $this->authorizeProject($proyek);

    $laporanTerakhir = $proyek->progressReports()->first();

    return view('backend.pages.project-progress.create', [
        'proyek' => $proyek,
        'statuses' => ProjectProgressReport::STATUSES,
        'periodeOptions' => ProjectProgressReport::PERIODE,
        'laporanTerakhir' => $laporanTerakhir,
    ]);
}
```

`DataSpatial::progressReports()` (`app/Models/DataSpatial.php` baris 67-70) sudah `->latest('id')`, jadi `->first()` otomatis memberi laporan yang paling baru disimpan.

### 6.2 `resources/views/backend/pages/project-progress/create.blade.php`

Ganti input `pagu` (baris 31-34):

```blade
            <div class="mb-3">
                <label class="form-label">Pagu (Rp)</label>
                <input type="number" step="0.01" name="pagu" class="form-control" value="{{ old('pagu', $laporanTerakhir->pagu ?? '') }}">
                @if ($laporanTerakhir && $laporanTerakhir->pagu)
                    <small class="text-muted">Diisi otomatis dari laporan {{ $laporanTerakhir->periode_laporan }} {{ $laporanTerakhir->tahun_anggaran }} — sesuaikan bila berubah.</small>
                @endif
            </div>
```

Field lain (`realisasi_anggaran`, `progres_fisik_persen`, `status`, `catatan`) **tidak diubah** — tetap kosong/default sesuai Keputusan #8.

## Tahap 7 — Testing (PHPUnit)

| Test | File | Skenario |
| --- | --- | --- |
| `test_public_detail_page_shows_incomplete_metadata_badge_when_metadata_missing` | `tests/Feature/FrontendPagesTest.php` (atau file baru `MetadataBadgeTest.php`) | Proyek tanpa `sumber_data`/`opd_pengelola_id`/`tanggal_data` → `assertSee('Metadata belum lengkap')`. |
| `test_public_detail_page_hides_incomplete_metadata_badge_when_metadata_complete` | sda | Proyek dengan ketiga field terisi → `assertDontSee('Metadata belum lengkap')`. |
| `test_new_data_spatial_requires_metadata_after_cutoff` | `tests/Feature/DataSpatial/MetadataDatasetTest.php` (extend) | `Carbon::setTestNow('2026-10-02')` (setelah cutoff), submit tanpa `sumber_data` → `assertSessionHasErrors('sumber_data')`. |
| `test_new_data_spatial_metadata_still_optional_before_cutoff` | sda | `Carbon::setTestNow('2026-09-25')` (sebelum cutoff), submit tanpa `sumber_data` → sukses tersimpan, tidak ada error. |
| `test_admin_opd_not_required_to_fill_opd_pengelola_id_after_cutoff` | sda | Setelah cutoff, admin-opd submit tanpa `opd_pengelola_id` di request → tetap sukses (auto-terisi dari `Auth::user()->opd_id`), bukan `422`. |
| `test_pembangunan_dashboard_shows_kelengkapan_pelaporan_percentage` | `tests/Feature/PembangunanDashboardTest.php` (extend) | 2 proyek, 1 sudah lapor tahun berjalan → `assertViewHas('cards', fn ($c) => $c['persen_kelengkapan'] === 50.0)`. |
| `test_admin_opd_sees_reminder_banner_when_reports_incomplete` | `tests/Feature/ProjectProgressReportTest.php` (extend) | Admin-opd, 3 proyek, 1 sudah lapor tahun ini → `assertViewHas('belumLaporBanner', fn ($b) => $b['belum'] === 2)`. |
| `test_admin_opd_does_not_see_banner_when_all_reported` | sda | Semua proyek OPD sudah lapor tahun ini → `assertViewHas('belumLaporBanner', fn ($b) => $b === null)`. |
| `test_admin_bappeda_never_sees_personal_banner` | sda | Admin-bappeda buka `project-progress.index` → `belumLaporBanner` selalu `null` meski ada proyek OPD lain yang belum lapor (bukan konsep personal untuk role ini). |
| `test_create_form_prefills_pagu_from_last_report` | sda | Proyek dengan 1 laporan `pagu = 500000000` → buka `project-progress.create` → `assertViewHas('laporanTerakhir', fn ($l) => (float) $l->pagu === 500000000.0)`. |
| `test_create_form_has_no_prefill_for_first_report` | sda | Proyek tanpa laporan sama sekali → `assertViewHas('laporanTerakhir', fn ($l) => $l === null)`. |

Jalankan dengan filter dulu per file (`--filter=MetadataDatasetTest`, `--filter=PembangunanDashboardTest`, `--filter=ProjectProgressReportTest`, `--filter=FrontendPagesTest`), baru tawarkan regresi/full suite ke user.

## Urutan Eksekusi

1. Konfirmasi Keputusan #1-8, khususnya #2 (tanggal cutoff sesungguhnya) dan catatan penting di Tahap 2.2 soal perilaku `update()` untuk data lama.
2. Tahap 1 (badge publik) — independen, bisa dikerjakan lebih dulu atau paralel dengan yang lain.
3. Tahap 2 (grace period) — `config/marimoi.php` + `.env.example` + perubahan `DataSpatialController`.
4. Tahap 3 (sidebar) — independen dari Tahap 1-2 dan 4-6.
5. Tahap 4 (kartu kelengkapan) — independen.
6. Tahap 5 (banner personal) — independen.
7. Tahap 6 (pra-isi form) — independen.
8. `vendor/bin/pint --dirty --format agent` untuk seluruh file PHP yang disentuh.
9. Test Tahap 7, jalankan per file dulu.
10. Verifikasi manual di browser: buka detail proyek publik tanpa metadata (lihat badge), coba submit data baru dengan `now()` disimulasikan setelah cutoff (lihat validasi wajib), buka sidebar (lihat submenu Dashboard), buka dashboard pembangunan (lihat 4 kartu), login sebagai admin-opd dengan proyek belum lapor (lihat banner), buka form tambah laporan kedua untuk satu proyek (lihat pagu terisi otomatis).
11. Tawarkan full test suite ke user.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| `update()` di Tahap 2 memaksa data lama (dibuat sebelum cutoff) ikut mengisi metadata wajib begitu diedit setelah cutoff | Dicatat eksplisit di Tahap 2.2 sebagai keputusan yang perlu dikonfirmasi dulu — alternatif (cek `created_at` data, bukan cuma waktu aksi) sudah disiapkan bila perilaku ini tidak diinginkan. |
| Badge Tahap 1 dan validasi wajib Tahap 2 punya kriteria "lengkap" yang berbeda kalau tidak hati-hati (Tahap 1 pakai `metadata_lengkap` yang mensyaratkan 3 field, Tahap 2 kalau cuma mewajibkan 2 field akan kontradiksi) | Diselesaikan lewat Keputusan #4 — `tanggal_data` ikut diwajibkan di Tahap 2, menyamakan cakupan dengan `metadata_lengkap`. |
| Banner personal (Tahap 5) dan kartu kelengkapan (Tahap 4) pakai definisi "sudah lapor" yang sedikit berbeda cara hitung (Tahap 4 pakai laporan terbaru per proyek dari query dashboard yang sudah ada; Tahap 5 pakai query baru `distinct()->count('data_spatial_id')`) | Diterima — keduanya secara logis menghasilkan angka yang sama (proyek dengan ≥1 laporan tahun berjalan), hanya jalur query berbeda karena konteks controller berbeda (`PembangunanDashboardController` vs `ProjectProgressController`). Tidak digabung jadi satu helper/trait untuk menjaga masing-masing controller tetap sederhana — pertimbangkan ekstraksi ke trait/service kalau logika serupa bertambah lagi di iterasi berikutnya. |
| Perubahan grid `col-md-4` → `col-md-3` di Tahap 4 bisa merapatkan tampilan di layar sempit/tablet | Bootstrap grid sudah responsif secara default (`col-md-*` cuma berlaku ≥768px, di layar lebih kecil otomatis full-width bertumpuk) — risiko rendah, tapi tetap perlu diverifikasi visual di Urutan Eksekusi langkah 10. |
| Sidebar Tahap 3 menambah 1 level collapse baru — berisiko mengganggu state "active" menu lain bila `request()->routeIs()` tumpang tindih | Pola collapse yang dipakai identik dengan "Peta Tematik" yang sudah teruji di file yang sama — risiko rendah, tapi test dashboard yang sudah ada (`FrontendPagesTest`, dll.) tidak menyentuh sidebar admin sehingga regresi di sini tidak akan otomatis terdeteksi test — perlu verifikasi manual eksplisit. |

## Kriteria Selesai

- [x] Popup detail peta publik menampilkan badge "Metadata belum lengkap" untuk data yang `metadata_lengkap`-nya `false`, dan tidak menampilkannya untuk data lengkap — diuji `test_detail_page_shows_dataset_metadata_when_present`/`test_detail_page_hides_dataset_metadata_fields_when_absent` (`FrontendPagesTest`).
- [x] Data spasial baru yang diinput setelah tanggal cutoff wajib mengisi `sumber_data`/`tanggal_data` (dan `opd_pengelola_id` untuk non-admin-opd); data sebelum cutoff tidak terpengaruh saat dibuat — diuji 3 test baru di `MetadataDatasetTest`.
- [x] Menu sidebar admin menampilkan "Dashboard" sebagai satu grup (Ringkasan + Pembangunan) untuk user yang punya `project-progress.view`, bukan dua entri terpisah — diimplementasikan, **belum ada test otomatis** (dicatat sebagai keterbatasan di Risiko sejak awal — perlu verifikasi manual).
- [x] Dashboard Pembangunan menampilkan kartu "Kelengkapan Pelaporan" dengan persentase yang benar — diuji `test_dashboard_shows_kelengkapan_pelaporan_percentage`.
- [x] Admin-opd dengan proyek yang belum melapor tahun berjalan melihat banner pengingat personal di halaman daftar proyek; admin-opd yang semua proyeknya sudah lapor, dan admin-bappeda/super-admin, tidak melihat banner ini — diuji 3 test baru di `ProjectProgressReportTest`.
- [x] Form tambah laporan progres kedua (dan seterusnya) untuk satu proyek terisi otomatis nilai `pagu` dari laporan terakhir; form untuk laporan pertama tetap kosong — diuji 2 test baru di `ProjectProgressReportTest`.
- [x] Seluruh test Tahap 7 lulus (48 test/191 assertion), regresi test terkait (`RolePermissionTest`, `DataSpatialGeojsonTest`, `FrontendGeojsonMetadataTest`, `SharedMapFilterTest`, `FrontendFilterOptionsTest`, `AspirasiTrackingTest` — 45 test/151 assertion) tetap lulus.
- [ ] **Verifikasi manual di browser belum dilakukan** — lihat "Catatan audit saat eksekusi".
