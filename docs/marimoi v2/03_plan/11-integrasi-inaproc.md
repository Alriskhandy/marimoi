# Plan: Integrasi Data Realisasi Pengadaan INAPROC (LKPP)

## Latar Belakang

Dashboard Pembangunan (`06-dashboard-eksekutif-minimum.md`, sudah diimplementasikan) saat ini 100% bergantung pada input manual admin untuk `pagu`/`realisasi_anggaran` di `project_progress_reports`. Data realisasi keuangan pengadaan barang/jasa sebenarnya sudah tercatat resmi di sistem LKPP (INAPROC) dan bisa ditarik lewat API resmi mereka. Dokumen ini merencanakan integrasi tersebut, dibatasi ke domain **infrastruktur** sesuai fokus MARIMOI sebagai sistem informasi akselerasi infrastruktur (lihat `Rekomendasi_Pengelompokan_Layer_MARIMOI.md` §5).

Ini **bukan** pengganti input manual progres fisik — INAPROC hanya melacak realisasi *keuangan* pengadaan, tidak melacak progres fisik konstruksi di lapangan. Progres fisik tetap wajib diisi manual lewat alur yang sudah ada.

## Sumber Data — Temuan Riset

Dikonfirmasi langsung dari dokumentasi resmi (`data.inaproc.id/docs`), bukan asumsi:

| Aspek | Temuan |
| --- | --- |
| Jenis akses | **API resmi** (`https://data.inaproc.id/api`), bukan scraping halaman `/realisasi`. |
| Endpoint relevan | `GET /api/v1/tender/pencatatan-non-tender-realisasi` — parameter wajib `kode_klpd` (kode instansi) + `tahun`; opsional `limit` (maks 1000) dan `cursor` (pagination). |
| Field respons | `nilai_realisasi`, `pagu`, `tgl_realisasi`, `nama_paket`, `nama_penyedia`, `nama_satker`, `nama_klpd`, `no_realisasi`, `nama_ppk`. **Tidak ada** field progres fisik, status pekerjaan, atau koordinat/lokasi. |
| Autentikasi | Token API per akun INAPROC. Permintaan token direview manual oleh tim LKPP, **1–3 hari kerja** — bukan self-service instan. Proses ini sedang berjalan (pemilik produk sedang mendaftar). |
| Rate limit | HTTP 429 saat melebihi batas; perlu retry/backoff, batas pasti tidak terdokumentasi di halaman yang diaudit. |
| Filter sektor | **Tidak ada** parameter filter sektor/infrastruktur langsung di API. Satu-satunya cara mempersempit ke domain tertentu adalah lewat pemilihan `kode_klpd` (instansi) yang disinkronkan — ini yang mendasari Keputusan #1 di bawah. |

### Asumsi Kerja: Satu API Key untuk Seluruh Aplikasi

Berdasarkan indikasi pemilik produk (satu akun yang sedang didaftarkan kemungkinan besar bisa query `kode_klpd` instansi mana pun, tidak dibatasi ke satu instansi), desain dokumen ini memakai **satu token aplikasi**, tersimpan terpusat, dipakai untuk sinkronisasi seluruh OPD yang dipetakan — **bukan** satu token per OPD. Ini menyederhanakan konfigurasi (satu baris `.env`, bukan tabel token per OPD) dan sejalan dengan prinsip "siap pakai begitu key tersedia" (lihat bagian "Kesiapan Implementasi").

Desain tetap defensif terhadap kemungkinan asumsi ini keliru: `InaprocClient` (Alur §2) membedakan error `401`/`403` secara eksplisit dari error lain, dicatat lewat `Log::warning` dengan `kode_klpd` yang gagal — jadi bila ternyata token dibatasi per instansi, kegagalan akan **terlihat jelas per OPD saat sync pertama kali dijalankan** (bukan gagal diam-diam atau salah tafsir sebagai OPD tanpa data). Perbaikannya pun hanya perubahan cara penyimpanan token (dari satu env var jadi kolom token per baris `opd`), bukan perubahan skema `inaproc_realisasi_reports` — keputusan ini didesain agar murah dibatalkan bila asumsinya salah.

## Business Rules (dari pemilik produk) dan Operasionalisasinya

| # | Rule | Bagaimana diwujudkan di desain ini |
| --- | --- | --- |
| 1 | Data yang ditargetkan adalah Infrastruktur | API tidak punya filter sektor, jadi fokus infrastruktur dicapai lewat **pemilihan instansi yang disinkronkan** (Rule #2) — bukan filter query. |
| 2 | OPD yang diprioritaskan adalah yang berkaitan dengan infrastruktur | Kolom baru `opd.kode_klpd_inaproc` (nullable) — hanya OPD yang dipetakan admin ke kode instansi INAPROC yang ikut disinkronkan. Mengisi kolom ini untuk OPD infrastruktur (PUPR, Perhubungan, dll.) *adalah* mekanisme prioritisasi — tidak perlu kolom "kategori OPD" terpisah. |
| 3 | Data bisa dilakukan sinkronisasi | Command `php artisan inaproc:sync` (bisa dijadwalkan) + tombol "Sinkronkan Sekarang" per OPD di UI admin — upsert idempoten, aman dijalankan berulang. |
| 4 | Data yang sudah ada bisa diintegrasikan ke peta oleh admin/admin-opd | Aksi "Hubungkan ke Peta" per baris data INAPROC — admin memilih satu `data_spatial` (proyek/fitur peta) yang cocok secara manual. Tidak ada pencocokan otomatis berbasis nama (lihat Keputusan #4, alasan keamanan data). |
| 5 | Data INAPROC dan peta dibuat terpisah | Tabel baru `inaproc_realisasi_reports`, berdiri sendiri, terhubung ke `data_spatial` **hanya** lewat satu kolom FK nullable satu arah. Tidak ada migrasi/penggabungan data ke `data_spatial` atau `project_progress_reports`. |
| 6 | Ada indikator status terhubung/belum ke peta | Kolom `data_spatial_id` null/terisi **adalah** indikatornya — ditampilkan sebagai badge di listing, filter status, dan kartu ringkasan "X dari Y sudah terhubung". |

## Cakupan

Termasuk: pemetaan OPD ↔ kode instansi INAPROC, tabel penyimpanan data realisasi INAPROC (hasil sinkronisasi, nilai selalu termutakhir per paket), riwayat perubahan nilai per paket (`inaproc_realisasi_histories`), mekanisme sinkronisasi (command + tombol manual), halaman admin untuk melihat/menyinkronkan/menghubungkan data ke peta, indikator status terhubung, otorisasi berbasis OPD (pola yang sama dengan `project-progress`), **satu proyek peta boleh tertaut ke banyak paket INAPROC sekaligus** (agregasi, bukan cuma 1:1), dan mekanisme agar laporan progres keuangan (`project_progress_reports`) yang bersumber dari INAPROC bisa **diperbarui** ketika angka di LKPP berubah — dengan jejak audit, bukan menimpa diam-diam (lihat bagian "Progres Keuangan Historis & Dapat Diperbarui").

Tidak termasuk: pengambilan progres fisik dari INAPROC (datanya tidak ada di sana — `progres_fisik_persen`/`status` tetap 100% input manual, termasuk pada laporan yang sumber keuangannya dari INAPROC), pencocokan otomatis data INAPROC ke `data_spatial` berbasis nama/AI (terlalu berisiko untuk data publik — lihat Keputusan #4), sinkronisasi real-time/webhook (INAPROC tidak menyediakan webhook, hanya polling), endpoint INAPROC selain realisasi non-tender (tender reguler, e-katalog, dll. — bisa jadi iterasi lanjutan bila field-nya relevan), token per-OPD (lihat "Asumsi Kerja" di atas — didesain sebagai perubahan konfigurasi murah bila terbukti perlu, bukan dibangun sekarang).

## Keputusan Desain

1. **Fokus infrastruktur dicapai lewat cakupan OPD yang disinkronkan, bukan filter konten.** Konsekuensi: bila suatu saat OPD non-infrastruktur juga dipetakan (mis. untuk kebutuhan lain), datanya akan ikut tersinkron — tidak ada guardrail teknis yang mencegah ini selain disiplin admin saat mengisi `kode_klpd_inaproc`. Diterima sebagai batasan API pihak ketiga, bukan kelemahan desain.
2. **Pemetaan OPD ↔ `kode_klpd` adalah data konfigurasi (kolom di tabel `opd`), bukan tabel mapping terpisah.** Relasinya 1:1 (satu OPD MARIMOI = satu instansi INAPROC) — tabel mapping terpisah akan jadi abstraksi berlebih untuk relasi sesederhana ini.
3. **Sinkronisasi bersifat upsert berdasarkan `(kode_klpd, tahun, no_realisasi)`.** `no_realisasi` dipakai sebagai kunci alami dari sumber data (bukan ID auto-increment lokal) supaya sinkronisasi berulang tidak membuat duplikat maupun kehilangan histori.
4. **Menghubungkan data INAPROC ke `data_spatial` dilakukan manual oleh admin/admin-opd, satu per satu — tidak ada pencocokan otomatis berbasis kemiripan nama.** Nama paket pengadaan ("Pembangunan Jalan Ruas A-B Tahun 2026") dan nama proyek di peta jarang identik persis; pencocokan otomatis (fuzzy matching) berisiko salah tautkan data keuangan ke proyek yang salah pada dashboard publik-facing. Keputusan sadar untuk mengutamakan akurasi di atas otomatisasi penuh.
5. **`inaproc_realisasi_reports` sepenuhnya independen dari `data_spatial`/`project_progress_reports`.** Satu-satunya titik sambung adalah kolom `data_spatial_id` (nullable, FK, `nullOnDelete`) — menghapus baris peta tidak menghapus data INAPROC, hanya melepas tautannya (jejak sinkronisasi dari LKPP tetap harus tersimpan sebagai record resmi, tidak boleh hilang karena aksi di sisi peta).
6. **Otorisasi mengikuti pola yang sama dengan `project-progress` (bagian D, sudah ada preseden).** Admin-opd hanya bisa melihat & menghubungkan data INAPROC milik OPD sendiri (`opd_id` hasil pemetaan `kode_klpd`); admin-bappeda/super-admin melihat & mengelola semua OPD yang terpetakan, termasuk mengatur pemetaan `kode_klpd_inaproc` itu sendiri (aksi sensitif, dibatasi permission terpisah).
7. **Konversi data INAPROC → `project_progress_reports` bersifat opsional dan tetap butuh input manual untuk progres fisik/status.** Tombol "Buat/Perbarui Laporan Progres dari data ini" pada baris yang sudah terhubung ke peta cukup mengisi `pagu`/`realisasi_anggaran` — bukan pembuatan otomatis tanpa interaksi, karena progres fisik & status tetap butuh penilaian manusia.
8. **Satu proyek peta (`data_spatial`) boleh tertaut ke banyak paket INAPROC (many-to-one dari `inaproc_realisasi_reports` ke `data_spatial`, bukan 1:1).** Menjawab pertanyaan terbuka di revisi dokumen sebelumnya: skema `inaproc_realisasi_reports.data_spatial_id` sudah secara alami mendukung ini (banyak baris INAPROC boleh menunjuk `data_spatial_id` yang sama). Saat menghitung `pagu`/`realisasi_anggaran` untuk satu laporan progres, nilainya adalah **penjumlahan** seluruh paket yang tertaut ke proyek itu, dibatasi tahun anggaran yang sama (lihat bagian "Progres Keuangan Historis & Dapat Diperbarui").
9. **Laporan progres (`project_progress_reports`) yang nilainya bersumber dari INAPROC ditandai eksplisit (`sumber_data = 'inaproc'`) dan boleh diperbarui — mengoreksi prinsip append-only murni di `06-dashboard-eksekutif-minimum.md` Keputusan #4, secara sempit.** Alasan: append-only awalnya dirancang untuk mencegah kesalahan input manusia diam-diam ditimpa tanpa jejak. Untuk data yang bersumber dari sistem otoritatif (LKPP) yang memang bisa berubah dari waktu ke waktu (mis. realisasi bertambah karena pembayaran baru dicatat), memaksa admin membuat baris baru per periode yang sama justru mendistorsi laporan (dua baris "Triwulan 2" dengan angka berbeda). Pembaruan **dibatasi hanya pada kolom finansial** (`pagu`, `realisasi_anggaran`) — `progres_fisik_persen`/`status`/`catatan` tidak pernah ikut berubah otomatis, dan setiap pembaruan dicatat di tabel audit terpisah (`project_progress_report_revisions`), bukan menimpa tanpa jejak. Laporan dengan `sumber_data = 'manual'` (input langsung admin, tanpa INAPROC) tetap 100% append-only seperti desain awal.

## Model Data

### Migration 1 — `opd`: tambah kolom pemetaan

```php
Schema::table('opd', function (Blueprint $table) {
    $table->string('kode_klpd_inaproc', 20)->nullable()->unique()->after('singkatan');
});
```

### Migration 2 — tabel baru `inaproc_realisasi_reports`

```php
Schema::create('inaproc_realisasi_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('opd_id')->nullable()->constrained('opd')->nullOnDelete();
    $table->string('kode_klpd', 20);
    $table->string('nama_klpd')->nullable();
    $table->unsignedSmallInteger('tahun');
    $table->string('nama_satker')->nullable();
    $table->string('nama_paket');
    $table->string('no_realisasi', 100);
    $table->string('nama_penyedia')->nullable();
    $table->decimal('pagu', 18, 2)->nullable();
    $table->decimal('nilai_realisasi', 18, 2)->nullable();
    $table->date('tgl_realisasi')->nullable();
    $table->json('raw_payload')->nullable(); // salinan mentah respons API, untuk audit
    $table->timestamp('synced_at');

    $table->foreignId('data_spatial_id')->nullable()->constrained('data_spatial')->nullOnDelete();
    $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('linked_at')->nullable();

    $table->timestamps();

    $table->unique(['kode_klpd', 'tahun', 'no_realisasi'], 'inaproc_realisasi_unique_paket');
    $table->index(['opd_id', 'tahun']);
    $table->index('data_spatial_id');
});
```

### Model `InaprocRealisasiReport`

```php
class InaprocRealisasiReport extends Model
{
    protected $fillable = [
        'opd_id', 'kode_klpd', 'nama_klpd', 'tahun', 'nama_satker', 'nama_paket',
        'no_realisasi', 'nama_penyedia', 'pagu', 'nilai_realisasi', 'tgl_realisasi',
        'raw_payload', 'synced_at', 'data_spatial_id', 'linked_by', 'linked_at',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'pagu' => 'decimal:2',
            'nilai_realisasi' => 'decimal:2',
            'tgl_realisasi' => 'date',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
            'linked_at' => 'datetime',
        ];
    }

    public function opd(): BelongsTo { return $this->belongsTo(Opd::class, 'opd_id'); }
    public function dataSpatial(): BelongsTo { return $this->belongsTo(DataSpatial::class, 'data_spatial_id'); }
    public function linkedBy(): BelongsTo { return $this->belongsTo(User::class, 'linked_by'); }

    public function scopeLinked($query) { return $query->whereNotNull('data_spatial_id'); }
    public function scopeUnlinked($query) { return $query->whereNull('data_spatial_id'); }
}
```

### Migration 3 — riwayat perubahan nilai per paket (`inaproc_realisasi_histories`)

Snapshot otomatis setiap kali sinkronisasi mendeteksi `pagu`/`nilai_realisasi` berubah pada baris `inaproc_realisasi_reports` yang sudah ada — murni jejak audit dari sisi data mentah LKPP, tidak butuh aksi admin.

```php
Schema::create('inaproc_realisasi_histories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('inaproc_realisasi_report_id')
        ->constrained('inaproc_realisasi_reports')
        ->cascadeOnDelete();
    $table->decimal('pagu', 18, 2)->nullable();
    $table->decimal('nilai_realisasi', 18, 2)->nullable();
    $table->date('tgl_realisasi')->nullable();
    $table->timestamp('synced_at');
    $table->timestamps();

    $table->index(['inaproc_realisasi_report_id', 'synced_at']);
});
```

`cascadeOnDelete()` di sini (beda dari `nullOnDelete()` pada relasi ke `data_spatial`) karena histori ini murni turunan dari baris `inaproc_realisasi_reports` — tidak berguna lagi begitu baris induknya dihapus (mis. paket ternyata salah tarik karena `kode_klpd_inaproc` salah ketik, lalu dibersihkan manual).

Model `InaprocRealisasiHistory` — sekadar log, tanpa logika tambahan:

```php
class InaprocRealisasiHistory extends Model
{
    public $timestamps = false; // hanya created_at manual lewat kolom synced_at
    protected $fillable = ['inaproc_realisasi_report_id', 'pagu', 'nilai_realisasi', 'tgl_realisasi', 'synced_at'];

    protected function casts(): array
    {
        return ['pagu' => 'decimal:2', 'nilai_realisasi' => 'decimal:2', 'tgl_realisasi' => 'date', 'synced_at' => 'datetime'];
    }

    public function realisasiReport(): BelongsTo
    {
        return $this->belongsTo(InaprocRealisasiReport::class, 'inaproc_realisasi_report_id');
    }
}
```

### Migration 4 — tambah kolom sumber pada `project_progress_reports`

```php
Schema::table('project_progress_reports', function (Blueprint $table) {
    $table->string('sumber_data', 20)->default('manual')->after('status'); // manual|inaproc
});
```

### Migration 5 — audit pembaruan (`project_progress_report_revisions`)

```php
Schema::create('project_progress_report_revisions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_progress_report_id')
        ->constrained('project_progress_reports')
        ->cascadeOnDelete();
    $table->decimal('pagu_sebelumnya', 18, 2)->nullable();
    $table->decimal('realisasi_sebelumnya', 18, 2)->nullable();
    $table->decimal('pagu_baru', 18, 2)->nullable();
    $table->decimal('realisasi_baru', 18, 2)->nullable();
    $table->foreignId('diperbarui_oleh')->constrained('users')->restrictOnDelete();
    $table->timestamp('created_at')->useCurrent();

    $table->index('project_progress_report_id');
});
```

Tanpa `updated_at` (baris di tabel ini sendiri tidak pernah diubah — ini log, bukan entitas yang di-edit).

Model `ProjectProgressReportRevision`, juga sekadar log:

```php
class ProjectProgressReportRevision extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'project_progress_report_id', 'pagu_sebelumnya', 'realisasi_sebelumnya',
        'pagu_baru', 'realisasi_baru', 'diperbarui_oleh',
    ];

    protected function casts(): array
    {
        return [
            'pagu_sebelumnya' => 'decimal:2', 'realisasi_sebelumnya' => 'decimal:2',
            'pagu_baru' => 'decimal:2', 'realisasi_baru' => 'decimal:2',
        ];
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

### Migration 6 — pivot komposisi paket per laporan (`project_progress_report_inaproc_realisasi`)

Satu laporan progres bisa dihitung dari beberapa paket INAPROC (Keputusan #8) — pivot ini mencatat paket mana saja yang menyusun angka di satu baris `project_progress_reports`, supaya bisa ditelusuri ("angka Rp 2,3 M di laporan Triwulan 2 ini dari 3 paket berikut").

```php
Schema::create('project_progress_report_inaproc_realisasi', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_progress_report_id')
        ->constrained('project_progress_reports')
        ->cascadeOnDelete();
    $table->foreignId('inaproc_realisasi_report_id')
        ->constrained('inaproc_realisasi_reports')
        ->cascadeOnDelete();
    $table->timestamp('created_at')->useCurrent();

    $table->unique(
        ['project_progress_report_id', 'inaproc_realisasi_report_id'],
        'ppr_inaproc_pivot_unique'
    );
});
```

Nama constraint unique dipersingkat manual (`ppr_inaproc_pivot_unique`) — nama gabungan kedua kolom otomatis dari Laravel untuk pasangan tabel sepanjang ini akan melewati batas 63 karakter identifier PostgreSQL.

### Tambahan relasi & scope di `ProjectProgressReport`

```php
public function revisions(): HasMany
{
    return $this->hasMany(ProjectProgressReportRevision::class)->latest();
}

public function inaprocRealisasiReports(): BelongsToMany
{
    return $this->belongsToMany(
        InaprocRealisasiReport::class,
        'project_progress_report_inaproc_realisasi'
    );
}

public function scopeFromInaproc($query)
{
    return $query->where('sumber_data', 'inaproc');
}
```

## Alur

### 1. Setup pemetaan OPD (sekali per OPD, oleh admin-bappeda/super-admin)

Halaman pengaturan OPD yang sudah ada (`OpdController`) diperluas dengan field `kode_klpd_inaproc` + tombol "Tes Koneksi" (memanggil API dengan `kode_klpd` yang diisi, `tahun` = tahun berjalan, `limit=1` — bila respons berisi `nama_klpd` yang sesuai ekspektasi, tampilkan konfirmasi; bila gagal/kosong, tampilkan peringatan sebelum admin menyimpan). Ini mencegah kesalahan ketik kode instansi baru ketahuan saat sinkronisasi massal berjalan.

### 2. Sinkronisasi

`App\Services\InaprocClient` — wrapper HTTP (token dari `config('services.inaproc.token')`, diisi lewat `.env` `INAPROC_API_TOKEN`), menangani pagination lewat `cursor` dan retry sederhana saat menerima `429`.

`App\Console\Commands\SyncInaprocRealisasi` (`php artisan inaproc:sync {--tahun=} {--opd=}`):

```php
foreach (Opd::whereNotNull('kode_klpd_inaproc')->when($opdId, fn ($q) => $q->where('id', $opdId))->get() as $opd) {
    $records = $client->fetchRealisasi($opd->kode_klpd_inaproc, $tahun);

    foreach ($records as $record) {
        InaprocRealisasiReport::updateOrCreate(
            [
                'kode_klpd' => $opd->kode_klpd_inaproc,
                'tahun' => $tahun,
                'no_realisasi' => $record['no_realisasi'],
            ],
            [
                'opd_id' => $opd->id,
                'nama_klpd' => $record['nama_klpd'],
                'nama_satker' => $record['nama_satker'],
                'nama_paket' => $record['nama_paket'],
                'nama_penyedia' => $record['nama_penyedia'],
                'pagu' => $record['pagu'],
                'nilai_realisasi' => $record['nilai_realisasi'],
                'tgl_realisasi' => $record['tgl_realisasi'],
                'raw_payload' => $record,
                'synced_at' => now(),
            ]
        );
    }
}
```

`updateOrCreate` dipilih (bukan `create` murni) supaya nilai realisasi yang berubah di sisi LKPP (mis. paket yang tadinya "proses" lalu "selesai") ikut termutakhir tanpa duplikat baris — kolom identitas (`kode_klpd`+`tahun`+`no_realisasi`) tidak berubah, hanya kolom nilai yang di-update.

Dipicu lewat dua jalur: (a) `php artisan inaproc:sync` dijadwalkan (`routes/console.php`, mis. harian) setelah token & mapping OPD dikonfirmasi stabil, (b) tombol "Sinkronkan Sekarang" per OPD di halaman admin (memanggil logika yang sama secara sinkron — volume data per OPD per tahun kemungkinan kecil, jadi belum perlu queue job untuk MVP; jadi kandidat lanjutan bila terbukti lambat).

### 3. Melihat & menghubungkan ke peta (admin/admin-opd)

Halaman listing `inaproc_realisasi_reports` (scoped per OPD untuk admin-opd, sama seperti pola `ProjectProgressController::scopeProjectsToOpd()`), dengan:

- Filter: OPD (untuk bappeda/super-admin), tahun, status (`Semua` / `Sudah Terhubung` / `Belum Terhubung`).
- Kartu ringkasan: total data, jumlah sudah terhubung, jumlah belum.
- Badge per baris: hijau "Terhubung ke [nama proyek]" (tautan ke detail `data_spatial`) atau abu-abu "Belum Terhubung".
- Aksi "Hubungkan ke Peta" pada baris belum terhubung → modal/halaman pencarian `data_spatial` (cari lewat `deskripsi`/`uuid`, dibatasi ke OPD yang sama untuk admin-opd) → submit set `data_spatial_id`, `linked_by`, `linked_at`.
- Aksi "Lepas Tautan" pada baris yang sudah terhubung (untuk koreksi salah pilih) — set ketiga kolom itu kembali ke `null`. Melepas tautan **tidak** menghapus laporan progres yang mungkin sudah dibuat dari paket itu (lihat langkah 4) — hanya memutus sambungan agregasi untuk pembaruan berikutnya.

### 4. Membuat/memperbarui laporan progres dari INAPROC (historis & dapat diperbarui)

Di halaman detail proyek (`project-progress.show`), bila proyek itu punya minimal satu paket INAPROC tertaut, tampil tombol **"Hitung dari INAPROC"** di samping tombol "Tambah Laporan" manual yang sudah ada. Alurnya:

1. Admin memilih `tahun_anggaran` dan `periode_laporan` (dropdown sama seperti form manual).
2. Sistem menjumlahkan `pagu` dan `nilai_realisasi` dari **semua** `inaproc_realisasi_reports` yang tertaut ke proyek itu untuk `tahun` yang dipilih (Keputusan #8) — ditampilkan sebagai pratinjau (bukan langsung tersimpan) sebelum admin konfirmasi.
3. Admin tetap mengisi `progres_fisik_persen`, `status`, dan `catatan` secara manual (INAPROC tidak punya data ini — Cakupan §"Tidak termasuk").
4. Saat submit:
   - **Belum ada** laporan untuk `(data_spatial_id, tahun_anggaran, periode_laporan)` itu → buat baris baru seperti biasa, `sumber_data = 'inaproc'`, dan simpan pivot ke tiap paket yang disertakan dalam penjumlahan (Migration 6).
   - **Sudah ada** laporan untuk kombinasi itu **dan** `sumber_data`-nya `'inaproc'` → **perbarui** `pagu`/`realisasi_anggaran` (dan `progres_fisik_persen`/`status`/`catatan` bila admin mengubahnya), simpan nilai lama ke `project_progress_report_revisions` (Migration 5) sebelum ditimpa, lalu perbarui pivot komposisi paketnya.
   - **Sudah ada** laporan untuk kombinasi itu tapi `sumber_data`-nya `'manual'` → **ditolak** dengan pesan jelas ("Laporan periode ini sudah diisi manual — tidak bisa ditimpa otomatis dari INAPROC. Koreksi lewat halaman ini memerlukan keputusan sadar admin, bukan penimpaan otomatis") — mencegah data yang sengaja diisi manual (mis. karena admin punya informasi lebih akurat dari lapangan) hilang tanpa sadar.
5. Halaman detail proyek (`project-progress.show`) menampilkan badge kecil pada laporan yang `sumber_data = 'inaproc'` ("Dari INAPROC, {N}× diperbarui" — dihitung dari `revisions()->count()`), supaya jelas mana laporan yang datanya tersinkron otomatis vs input manual murni.

## Permission Baru

Modul `inaproc` di `Permission::CATALOG`, mengikuti pola yang sudah ada:

```php
'inaproc' => [
    'label' => 'Integrasi Data INAPROC',
    'actions' => [
        'view' => 'Lihat data realisasi INAPROC',
        'sync' => 'Sinkronkan data dari INAPROC',
        'link' => 'Hubungkan/lepas tautan data ke peta',
    ],
],
```

Default: `admin-bappeda` dapat `inaproc.*` (termasuk mengatur `kode_klpd_inaproc` di `opd` lewat permission `opd.edit` yang sudah ada); `admin-opd` dapat `inaproc.view` + `inaproc.link` (tidak `inaproc.sync` — sinkronisasi lintas OPD sebaiknya terpusat di bappeda supaya konsisten kapan data terakhir ditarik, bukan tiap admin-opd menyinkronkan sendiri-sendiri dengan jadwal berbeda).

Aksi "Hitung dari INAPROC" (Alur §4) **tidak** memakai permission baru — cukup `project-progress.create` yang sudah ada (bagian D), karena secara konsep aksi ini tetap "membuat/memutakhirkan laporan progres", hanya sumber angkanya beda. Menambah permission terpisah untuk hal ini akan jadi pemisahan berlebihan untuk satu aksi yang tetap di bawah wewenang yang sama dengan menambah laporan manual.

## Risiko dan Asumsi Terbuka

| Risiko/Asumsi | Catatan |
| --- | --- |
| Asumsi "satu token untuk semua `kode_klpd`" (lihat "Asumsi Kerja") ternyata salah | Bukan blocker desain — `InaprocClient` sudah membedakan `401`/`403` secara eksplisit per OPD saat sync (lihat "Asumsi Kerja"), jadi kegagalan langsung terlihat di log per `kode_klpd`, bukan gagal diam-diam. Perbaikan hanya perubahan cara simpan token (env → kolom per OPD), tidak menyentuh skema `inaproc_realisasi_reports`/`project_progress_reports`. |
| Proses approval token 1–3 hari kerja | Bukan hambatan teknis — direncanakan sebagai lead time administratif. Semua kode di dokumen ini didesain bisa dibangun & diuji (`Http::fake()`) tanpa menunggu token nyata (lihat "Kesiapan Implementasi"). |
| Rate limit (429) tidak terdokumentasi jelas | `InaprocClient` perlu retry/backoff yang defensif (mis. exponential backoff, bukan retry agresif) sampai batas sebenarnya diketahui dari pemakaian nyata. |
| Data yang sudah disinkron tapi OPD-nya kemudian tidak jadi dipetakan (`kode_klpd_inaproc` dikosongkan admin) | Baris `inaproc_realisasi_reports` yang sudah ada **tidak dihapus otomatis** (tidak ada cascade dari perubahan `opd.kode_klpd_inaproc`) — hanya sinkronisasi berikutnya yang berhenti menariknya. Data historis tetap tersimpan sebagai jejak, sesuai prinsip "jangan hilangkan data resmi dari LKPP". |
| Pencocokan manual (Keputusan #4) tidak bisa di-skala untuk ratusan paket sekaligus | Diterima untuk MVP — akurasi diutamakan. Bila volume data terbukti besar, tahap lanjutan bisa menambah *saran* pencocokan (mis. tampilkan 3 `data_spatial` dengan nama paling mirip sebagai starting point), tapi keputusan akhir tetap manual, bukan auto-link. |
| "Hitung dari INAPROC" (Alur §4) dijalankan berulang kali dengan komposisi paket yang berbeda tiap kali (mis. admin menautkan paket baru ke proyek yang sama setelah laporan periode itu sudah pernah dibuat) | Pivot `project_progress_report_inaproc_realisasi` **ditimpa penuh** (bukan ditambah) setiap kali "Hitung dari INAPROC" disubmit ulang untuk periode yang sama, supaya komposisinya selalu mencerminkan tautan yang aktif saat itu, bukan akumulasi historis paket yang mungkin sudah dilepas tautannya. Riwayat nilai tetap aman lewat `project_progress_report_revisions` yang append-only. |
| `project_progress_report_revisions` dan `inaproc_realisasi_histories` bisa tumbuh besar bila sinkronisasi harian menemukan perubahan nilai terus-menerus | Diterima untuk MVP — kedua tabel murni log (`id`, FK, angka, timestamp), murah disimpan. Kebijakan retensi/pembersihan lama bisa ditambah belakangan bila terbukti perlu, tidak memengaruhi desain skema. |

## Kesiapan Implementasi Sebelum API Key Tersedia

Supaya implementasi bisa langsung jalan begitu token INAPROC selesai diproses LKPP (bukan mulai dari nol saat itu), bagian yang **tidak bergantung pada token nyata** dikerjakan dulu — `InaprocClient` diuji lewat `Http::fake()` (meniru struktur respons yang sudah terdokumentasi di tabel "Sumber Data"), bukan menunggu panggilan API sungguhan:

**Bisa dikerjakan & diuji penuh sekarang (tanpa token):**
1. Migration 1–6 (kolom `opd.kode_klpd_inaproc`, tabel `inaproc_realisasi_reports`/`inaproc_realisasi_histories`/`project_progress_report_revisions`/`project_progress_report_inaproc_realisasi`, kolom `project_progress_reports.sumber_data`).
2. Model + relasi (`InaprocRealisasiReport`, `InaprocRealisasiHistory`, `ProjectProgressReportRevision`, tambahan relasi di `ProjectProgressReport`/`DataSpatial`/`Opd`).
3. `InaprocClient` — ditulis lengkap termasuk penanganan pagination/retry/`401`/`403`/`429`, diuji dengan `Http::fake([...])` mensimulasikan respons sukses, kosong, error auth, dan rate-limit.
4. `SyncInaprocRealisasi` command — logika upsert + snapshot histori, diuji lewat `InaprocClient` yang di-fake, termasuk skenario "record sudah ada, nilainya berubah" dan "kode_klpd tidak valid (401/403)".
5. Seluruh controller, view, permission, dan otorisasi OPD (halaman pengaturan OPD, listing INAPROC, aksi hubungkan/lepas tautan, alur "Hitung dari INAPROC") — semuanya beroperasi di atas data yang sudah ada di tabel lokal, tidak memanggil API asli saat digunakan sehari-hari (hanya `sync` yang memanggil API).
6. Tombol "Tes Koneksi" di halaman pengaturan OPD — bisa dibangun & diuji dengan `Http::fake()`, baru divalidasi dengan token & `kode_klpd` sungguhan saat token tersedia.

**Baru bisa divalidasi setelah token tersedia (langkah tersisa, bukan pekerjaan baru):**
1. Isi `.env` `INAPROC_API_TOKEN` (satu baris konfigurasi).
2. Jalankan "Tes Koneksi" dengan `kode_klpd` OPD infrastruktur sungguhan (PUPR, dst.) — memvalidasi asumsi "satu token untuk semua instansi" sekaligus memetakan `kode_klpd_inaproc` yang benar.
3. Jalankan `php artisan inaproc:sync` sungguhan sekali secara manual, periksa data yang masuk sesuai ekspektasi (`nama_paket`, nilai, dst. masuk akal dibanding data yang diketahui admin OPD).
4. Baru setelah langkah 3 lolos, jadwalkan sinkronisasi otomatis (`routes/console.php`) dan buka akses `inaproc.view`/`inaproc.link` ke admin-opd.

## Kriteria Selesai

- Admin-bappeda/super-admin bisa memetakan OPD infrastruktur ke kode instansi INAPROC lewat halaman pengaturan OPD, dengan validasi "Tes Koneksi" sebelum disimpan.
- Sinkronisasi (command dan/atau tombol manual) berhasil menarik data realisasi untuk OPD yang terpetakan, tanpa duplikat pada percobaan berulang (diuji lewat unique constraint `(kode_klpd, tahun, no_realisasi)`), dan mencatat perubahan nilai ke `inaproc_realisasi_histories` saat sinkronisasi ulang menemukan angka yang berbeda dari sebelumnya.
- Data INAPROC tampil di halaman terpisah dari peta (`inaproc_realisasi_reports`), dengan indikator visual yang jelas membedakan status "Terhubung ke Peta" vs "Belum Terhubung".
- Admin/admin-opd bisa menghubungkan satu baris data INAPROC ke satu fitur `data_spatial` secara manual (satu proyek boleh punya banyak paket tertaut), dan melepas tautan itu bila salah pilih.
- Satu proyek peta bisa punya laporan progres keuangan yang **historis** (satu baris `project_progress_reports` per periode, seperti sudah ada) **dan** bisa **diperbarui** khusus untuk laporan bersumber INAPROC (`sumber_data = 'inaproc'`) tanpa kehilangan jejak — setiap pembaruan tercatat di `project_progress_report_revisions` dengan nilai sebelum/sesudah. Laporan `sumber_data = 'manual'` tetap append-only, tidak bisa ditimpa dari alur INAPROC.
- Admin-opd hanya melihat & bisa menghubungkan data INAPROC milik OPD sendiri; tidak bisa memicu sinkronisasi (permission `inaproc.sync` khusus bappeda/super-admin).
- Menghapus baris `data_spatial` yang tertaut tidak menghapus data INAPROC terkait (hanya melepas tautan) — diuji lewat feature test.
- Seluruh alur di atas (migration, model, client, command, controller, otorisasi) sudah dibangun dan lulus test **sebelum** token INAPROC tersedia, memakai `Http::fake()` — begitu token tersedia, langkah tersisa murni konfigurasi & validasi (lihat "Kesiapan Implementasi"), bukan pengembangan fitur baru.

## Referensi

- [`Rekomendasi_Pengelompokan_Layer_MARIMOI.md`](../Rekomendasi_Pengelompokan_Layer_MARIMOI.md) §5 — dasar fokus infrastruktur.
- [`10-tindak-lanjut-review-jamil.md`](10-tindak-lanjut-review-jamil.md) bagian D — `project_progress_reports`, pola otorisasi OPD, dan titik sambung opsional (Keputusan #7) untuk fitur ini.
- [`../04_implementation/06-dashboard-eksekutif-minimum.md`](../04_implementation/06-dashboard-eksekutif-minimum.md) — implementasi `ProjectProgressController`/`DataSpatial::opdPengelola()` yang jadi preseden pola otorisasi & struktur controller di dokumen ini.
- Dokumentasi resmi INAPROC API Gateway: `data.inaproc.id/docs` (ringkasan temuan di tabel "Sumber Data — Temuan Riset" di atas; buka ulang dokumentasi lengkap saat token tersedia untuk detail request/response yang tidak tercakup di sini, dan untuk memvalidasi asumsi "satu token untuk semua `kode_klpd`" — lihat "Asumsi Kerja").
