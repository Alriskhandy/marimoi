# Plan Implementasi: Manajemen Pemetaan di Dashboard di Atas Skema Baru

## Status

✅ Fase 1–7 diimplementasikan dan diverifikasi (2026-09-28). Menu "Kelola Peta" sudah ada di sidebar admin (izin `maps.manage`), halaman metadata layer terpasang di daftar kategori.

**Bug nyata yang ditemukan lagi lewat verifikasi berulang**: `SpatialLayer::$fillable` kembali tidak menyertakan `legacy_category_id` — ini adalah regresi dari rollback penuh sesi sebelumnya (kolom itu sempat ditambahkan lalu ikut terhapus saat rollback dashboard eksekutif). Ditemukan lewat test Bagian A (`SpatialLayerMetadataTest`) yang gagal karena controller tidak bisa menemukan `SpatialLayer` yang seharusnya ada — diperbaiki lagi dengan menambahkan `legacy_category_id` ke `$fillable`. **Catatan untuk sesi berikutnya**: kolom ini penting untuk seluruh fitur yang menjodohkan `categories` ↔ `spatial_layers` (Bagian A dokumen ini, dan juga rencana Observer kalau nanti dikerjakan lagi) — jangan dihapus lagi tanpa sadar saat rollback sebagian di masa depan.

Verifikasi manual dilakukan lewat `curl` ke server dev yang sedang jalan dengan data nyata (bukan cuma test): buat `Map`, tempel `SpatialLayer` aktif, `publish()`, `MapShare::generateFor()`, lalu buka link `/peta/bagikan/{token}` — judul peta ("Smoke Test Peta") berhasil muncul di response HTML. Data uji langsung dibersihkan setelahnya.

Regresi akhir: `php artisan test --compact` → 260 lulus (naik dari 228 di awal sesi implementasi ini), 8 gagal — persis baseline pra-eksisting (`Auth\RegistrationTest`/`AuthenticationTest`, `FeedbackStoreTest`), tidak bertambah. Pint bersih.

## Catatan Revisi

Dokumen ini sebelumnya berisi rencana "Dashboard Eksekutif" (`development_projects`, analisis tren/sektor/wilayah pembangunan, Observer sinkronisasi jalur tulis). Implementasi dari rencana itu sempat dibangun lalu **di-rollback penuh oleh user** — bukan bagian dari cakupan yang dimaksud. Dokumen ini ditulis ulang dari awal dengan cakupan yang benar: **manajemen data pemetaan** (layer, komposisi peta, metadata, publikasi, berbagi), bukan dashboard eksekutif atau analisis tren pembangunan. Kalau kebutuhan dashboard eksekutif ingin dikerjakan lagi nanti, itu topik terpisah — lihat catatan di riwayat git untuk pendekatan yang sempat dicoba.

## Tujuan

Skema V2 (Prioritas 1–3 di `09-implementasi-penuh-database-v2.md`) sudah membangun tiga kemampuan manajemen pemetaan yang **sama sekali tidak ada** di sistem lama, lengkap sampai level model + migration + (sebagian) test — tapi **nol antarmuka admin**:

1. **`spatial_layer_metadata`** — metadata standar per layer (sumber data, lisensi, akurasi, tanggal referensi, frekuensi update, dst.). `categories` tidak punya field setara sama sekali.
2. **`maps` + `map_layers` + `map_layer_groups`** — menyusun banyak layer jadi satu "peta" bernama dengan urutan tampil, grouping, style/opacity/zoom per layer. Sistem lama tidak punya konsep "peta tersusun" — pengguna publik memilih layer manual tiap kali buka `/peta-tematik`.
3. **`map_publications` + `map_shares` + `map_share_accesses`** — publikasi peta bervensi (snapshot tiap kali diterbitkan, bisa lihat riwayat) dan link berbagi yang aman (token 48-karakter acak, disimpan sebagai hash — bukan plaintext, bisa kedaluwarsa/dicabut, tercatat siapa/kapan mengakses). Bandingkan dengan sistem share yang ada sekarang (`SharedMap`, dipakai `/peta-tematik/share`): cuma menyimpan daftar nama layer + viewport, **tanpa** kontrol keamanan, tanpa revoke, tanpa access log.

Dokumen ini membangun antarmuka admin untuk ketiga kemampuan itu — inilah "perubahan manajemen pemetaan yang lebih baik" yang dimaksud.

## Kondisi Nyata yang Sudah Diverifikasi (2026-09-28)

| Yang dicek | Hasil |
| --- | --- |
| Model `Map`, `MapLayer`, `MapLayerGroup`, `MapPublication`, `MapShare`, `MapShareAccess` | Sudah ada, lengkap, dengan logika domain sudah jadi: `Map::publish()` (snapshot config + revisi baru, matikan revisi lama), `MapShare::generateFor()` (token acak di-hash sebelum simpan, plaintext cuma dikembalikan sekali), `MapShare::isValid()`/`revoke()`/`recordAccess()`. |
| Test yang sudah ada | `tests/Feature/MapSharingTest.php` — mengetes langsung lewat model (draft→publish→share→akses→revoke), **bukan** lewat HTTP/controller, karena controllernya memang belum ada. |
| Controller/route admin untuk domain `Map` | **Tidak ada sama sekali.** `grep` ke `routes/*.php` untuk `MapController`/`maps.` tidak menemukan apa pun. Ada folder kosong `app/Http/Controllers/Map/` (peninggalan lama, tidak terpakai). |
| Endpoint publik untuk membuka link share (`MapShare`) | Tidak ada — `MapShare::findByToken()` sudah ada di model tapi tidak dipanggil controller manapun. |
| `spatial_layer_metadata` | Tabel ada, model ada, **0 controller yang menyentuhnya**, isinya cuma 1 baris di database (kemungkinan sisa uji coba manual, bukan data produksi). |
| `spatial_layers` (sumber layer untuk disusun ke peta) | 211 baris, hasil backfill satu kali dari `categories` (Prioritas 1). |
| `maps`/`map_layers`/dst | 0 baris — fitur ini belum pernah dipakai sama sekali. |

## Kenapa Ini Lebih Aman daripada Rencana Dashboard Eksekutif Sebelumnya

Rencana sebelumnya (yang di-rollback) butuh Observer sinkronisasi karena `development_projects`/`spatial_layer_features` adalah **backfill dari tabel lama yang masih aktif ditulis** (`data_spatial`) — celah itulah yang menghentikan Prioritas 8.

`maps`/`map_layers`/`map_publications`/`map_shares` **tidak punya tabel lama setara sama sekali** (dicatat eksplisit di komentar migration-nya: *"Semua tabel di sini murni baru — tidak ada padanan tabel lama"*). Jadi tidak ada sync-gap untuk domain ini — ini murni fitur baru di atas data yang sudah ada, bukan migrasi dari sistem lama. **Tidak perlu Observer, tidak perlu keputusan arsitektur sinkronisasi apa pun** untuk mengerjakan dokumen ini.

**Satu keterbatasan yang tetap harus disadari** (bukan blocker, tapi harus jujur ditulis di UI): daftar layer yang bisa dipilih untuk disusun ke peta berasal dari `spatial_layers`, yang isinya adalah **snapshot backfill Prioritas 1** dari `categories`. Kategori baru yang dibuat lewat `CategoryController` **setelah** backfill (2026-09-27) tidak otomatis muncul sebagai `spatial_layers` baru — ini persis temuan sync-gap Prioritas 8 yang belum diputuskan arsitekturnya. Solusinya di sini bukan membangun Observer (di luar cakupan dokumen ini), melainkan **menampilkan kondisi ini apa adanya** di halaman "pilih layer": layer yang tersedia untuk disusun adalah yang sudah tersinkron ke `spatial_layers`, bukan seluruh `categories` yang ada.

## Ruang Lingkup

**Termasuk:**
- Bagian A — form metadata layer (`spatial_layer_metadata`), diakses dari halaman detail kategori/layer yang sudah ada.
- Bagian B — CRUD `Map` + penyusunan `MapLayer` (pilih layer, urutan, opacity, style, grouping via `MapLayerGroup`).
- Bagian C — publikasi (`Map::publish()`) + riwayat revisi (`MapPublication`).
- Bagian D — pembuatan link berbagi (`MapShare::generateFor()`), revoke, access log, **dan** halaman publik untuk membuka link itu (belum ada sama sekali, wajib dibangun supaya fitur share benar-benar bisa dipakai).

**Tidak termasuk (sengaja):**
- Dashboard eksekutif / analisis tren pembangunan (`development_projects`, `project_progress_reports`) — sudah dicoba dan di-rollback, bukan bagian dokumen ini.
- Observer sinkronisasi `categories`→`spatial_layers` — tetap menunggu keputusan arsitektur terpisah (Prioritas 8), tidak dipaksakan di sini.
- Migrasi sistem share lama (`SharedMap`, `/peta-tematik/share`) ke sistem baru — disebut di "Jalur ke Depan", tidak dikerjakan sekarang supaya `/peta-tematik` publik tidak kena risiko regresi.

## Pemetaan Kemampuan: Sistem Lama vs Sistem Baru

| Kebutuhan | Sistem lama | Sistem baru (dokumen ini) |
| --- | --- | --- |
| Metadata sumber/lisensi data per layer | Tidak ada field sama sekali di `categories` | `spatial_layer_metadata` |
| Menyusun banyak layer jadi satu peta bernama, dengan urutan & style | Tidak ada — pengguna pilih layer manual tiap buka halaman | `Map` + `MapLayer` (`display_order`, `opacity`, `style_config`) |
| Mengelompokkan layer dalam peta | Tidak ada | `MapLayerGroup` (bisa bersarang lewat `parent_id`) |
| Riwayat versi konfigurasi peta | Tidak ada | `MapPublication` (`revision`, `config_snapshot`, `is_current`) |
| Link berbagi | `SharedMap`: token pendek, tanpa expiry/revoke/tracking | `MapShare`: token 48-karakter **di-hash** sebelum disimpan, `expires_at`, `revoke()`, `access_count`, `last_accessed_at`, `qr_path` |
| Log siapa/kapan mengakses link share | Tidak ada | `MapShareAccess` (timestamp, ip_hash, user_agent, referer) |

## Bagian A — Metadata Layer

Route baru, menyatu dengan halaman kategori yang sudah ada (bukan halaman terpisah — metadata itu properti dari layer, bukan entitas berdiri sendiri):

```php
Route::get('/categories/{id}/metadata', [SpatialLayerMetadataController::class, 'edit'])
    ->name('categories.metadata.edit')->middleware('permission:categories.edit');
Route::put('/categories/{id}/metadata', [SpatialLayerMetadataController::class, 'update'])
    ->name('categories.metadata.update')->middleware('permission:categories.edit');
```

Controller baru `SpatialLayerMetadataController` (bukan menambah method ke `CategoryController` yang sudah besar):

```php
public function edit(int $categoryId)
{
    $category = Category::findOrFail($categoryId);
    $layer = SpatialLayer::where('legacy_category_id', $categoryId)->first();

    if (! $layer) {
        // Kategori dibuat setelah backfill Prioritas 1 — belum ada spatial_layers
        // yang berpadanan. Bukan error, tapi metadata memang belum bisa diisi.
        return redirect()->route('categories.show', $categoryId)
            ->with('error', 'Kategori ini belum tersinkron ke Layer (skema baru) — metadata belum bisa diisi.');
    }

    $metadata = $layer->metadata ?? new SpatialLayerMetadata(['spatial_layer_id' => $layer->id]);

    return view('backend.pages.categories.metadata', compact('category', 'layer', 'metadata'));
}

public function update(Request $request, int $categoryId)
{
    $layer = SpatialLayer::where('legacy_category_id', $categoryId)->firstOrFail();

    $validated = $request->validate([
        'abstract' => 'nullable|string',
        'source_name' => 'nullable|string|max:255',
        'source_url' => 'nullable|url',
        'license' => 'nullable|string|max:255',
        'attribution' => 'nullable|string',
        'update_frequency' => 'nullable|string|max:50',
        'data_reference_year' => 'nullable|integer|min:1900|max:2100',
    ]);

    SpatialLayerMetadata::updateOrCreate(
        ['spatial_layer_id' => $layer->id],
        $validated + ['updated_by' => auth()->id()]
    );

    return redirect()->route('categories.show', $categoryId)->with('success', 'Metadata layer berhasil disimpan.');
}
```

Tambahkan tombol "Metadata Layer" di halaman `categories.show` yang sudah ada, mengarah ke route di atas — **disembunyikan/nonaktif** kalau `SpatialLayer::where('legacy_category_id', ...)` tidak ketemu, supaya konsisten dengan keterbatasan yang sudah dijelaskan di atas.

**Test:** `SpatialLayerMetadataTest` — assert metadata tersimpan untuk kategori yang punya layer; assert redirect dengan pesan error (bukan 500) untuk kategori tanpa layer; assert nilai lama tidak hilang saat update sebagian field (`updateOrCreate` sudah menangani ini, tinggal dibuktikan).

## Bagian B — Kelola Peta (CRUD `Map` + Susun Layer)

Controller baru `MapController` (folder `app/Http/Controllers/Map/` yang kosong bisa dibersihkan terpisah, tidak dipakai di sini — controller baru langsung di `app/Http/Controllers/MapController.php` mengikuti konvensi `MapTypeController` yang sudah ada).

Route, mengikuti persis pola `map-types` yang sudah ada di `routes/backend.php`:

```php
Route::resource('maps', MapController::class)->middleware('permission:maps.manage');
Route::prefix('maps/{map}/layers')->name('maps.layers.')->middleware('permission:maps.manage')->group(function () {
    Route::post('/', [MapLayerController::class, 'store'])->name('store');
    Route::put('/{mapLayer}', [MapLayerController::class, 'update'])->name('update');
    Route::delete('/{mapLayer}', [MapLayerController::class, 'destroy'])->name('destroy');
    Route::post('/reorder', [MapLayerController::class, 'reorder'])->name('reorder');
});
```

Permission baru di katalog (`app/Models/Permission.php`, pola sama dengan `map-types`):

```php
'maps' => [
    'label' => 'Kelola Peta',
    'actions' => ['manage' => 'Susun, terbitkan, dan bagikan peta'],
],
```

`MapController::index()` — daftar peta (`Map::withCount('layers')->get()`). `create()`/`store()` — form judul/deskripsi/OPD pemilik/visibilitas. `edit()` — halaman builder: peta Leaflet pratinjau (reuse pola `/peta-v2`, lihat `10-plan-peta-skema-baru.md`) + panel kiri daftar `spatial_layers` aktif yang bisa ditambahkan.

`MapLayerController::store()` — attach satu `SpatialLayer` ke `Map` sebagai `MapLayer` baru (`display_order` = max + 1). `reorder()` — terima array urutan `spatial_layer_id`, update `display_order` per baris dalam satu transaksi. `update()` — ubah `opacity`/`style_config`/`is_visible`/`display_name` satu `MapLayer`. `destroy()` — detach dari peta (hapus `MapLayer`, bukan `SpatialLayer`-nya).

**Test:** `MapManagementTest` — CRUD `Map` lewat HTTP; attach layer menghasilkan `MapLayer` dengan `display_order` benar; reorder mengubah urutan sesuai payload; opacity di luar rentang `0–1` ditolak validasi.

## Bagian C — Publikasi

Tombol "Terbitkan" di halaman builder, memanggil method baru di `MapController`:

```php
public function publish(Map $map)
{
    $publication = $map->publish(auth()->user());

    return redirect()->route('maps.edit', $map)
        ->with('success', "Peta berhasil diterbitkan sebagai revisi #{$publication->revision}.");
}
```

Route: `Route::post('/maps/{map}/publish', [MapController::class, 'publish'])->name('maps.publish')->middleware('permission:maps.manage');`

Halaman riwayat revisi (`Map::publications()->orderByDesc('revision')->get()`) — tampilkan tanggal, siapa yang menerbitkan, dan ringkasan jumlah layer di `config_snapshot` tiap revisi. Logika `publish()` **sudah ada dan sudah teruji** (`MapSharingTest`) — bagian ini murni memanggilnya dari HTTP, tidak menulis ulang logikanya.

**Test:** `MapPublicationTest` — publish pertama menghasilkan revisi 1 dan `is_current=true`; publish kedua menghasilkan revisi 2, revisi 1 `is_current=false`; snapshot berisi layer yang benar sesuai kondisi `Map` saat tombol ditekan (bukan kondisi setelahnya).

## Bagian D — Berbagi (Share Link) + Halaman Publik

### D.1 Admin: generate/revoke link

```php
public function share(Request $request, MapPublication $publication)
{
    $request->validate(['expires_in_days' => 'nullable|integer|min:1|max:365']);
    $expiresAt = $request->expires_in_days ? now()->addDays($request->expires_in_days) : null;

    ['share' => $share, 'token' => $token] = MapShare::generateFor($publication, auth()->user(), $expiresAt);

    // Token plaintext HANYA ada di sini — tidak pernah disimpan, tidak bisa
    // ditampilkan ulang setelah request ini selesai. Tampilkan sekali ke admin
    // dengan peringatan jelas, pola sama seperti generate API token pada umumnya.
    return back()->with('shareToken', $token)->with('shareUrl', route('map-shares.show', $token));
}

public function revokeShare(MapShare $mapShare)
{
    $mapShare->revoke();

    return back()->with('success', 'Link berbagi berhasil dicabut.');
}
```

Halaman detail publikasi menampilkan daftar `MapShare` milik publikasi itu (status aktif/dicabut, kedaluwarsa, `access_count`, `last_accessed_at`) — **bukan** token-nya (sudah di-hash, tidak bisa ditampilkan ulang), cuma statusnya.

### D.2 Publik: buka link share

Ini **wajib** dibangun supaya fitur share benar-benar berfungsi — tanpa ini, `MapShare::generateFor()` cuma menghasilkan token yang tidak bisa dipakai siapa pun.

```php
// routes/web.php — publik, tanpa auth
Route::get('/peta/bagikan/{token}', [PublicMapShareController::class, 'show'])->name('map-shares.show');
```

```php
public function show(string $token)
{
    $share = MapShare::findByToken($token);

    abort_if(! $share || ! $share->isValid(), 404);

    $share->recordAccess(
        ipHash: hash('sha256', request()->ip()),
        userAgent: request()->userAgent(),
        referer: request()->header('referer'),
    );

    return view('frontend.pages.map-share', [
        'snapshot' => $share->publication->config_snapshot,
    ]);
}
```

`ip_hash` di-hash (bukan simpan IP mentah) — konsisten dengan `token_hash` yang juga tidak pernah simpan data sensitif plaintext. View `frontend.pages.map-share` merender Leaflet read-only dari `config_snapshot['layers']` (daftar `spatial_layer_id` + style), memanggil ulang endpoint geojson yang sama seperti `/peta-v2` (`10-plan-peta-skema-baru.md`) untuk tiap layer di snapshot — reuse, bukan duplikasi logic rendering peta.

**Test:** `PublicMapShareTest` — link valid menampilkan peta & menambah `access_count`; link yang sudah `revoke()` → 404; link kedaluwarsa (`expires_at` masa lalu) → 404; token acak yang tidak pernah dibuat → 404 (bukan 500).

## Fase Implementasi & Urutan Eksekusi

| Fase | Isi | Bergantung pada |
| --- | --- | --- |
| 1 | Permission `maps.manage` di katalog + migration seed permission | — |
| 2 | Bagian A: `SpatialLayerMetadataController` + view + test | — (independen dari Bagian B–D) |
| 3 | Bagian B: `MapController` + `MapLayerController` + view builder + test | Fase 1 |
| 4 | Bagian C: publish + riwayat revisi + test | Fase 3 |
| 5 | Bagian D.1: generate/revoke share (admin) + test | Fase 4 |
| 6 | Bagian D.2: halaman publik buka link + test | Fase 5 |
| 7 | QA manual end-to-end (lihat "Cara Mencoba") | Fase 1–6 |

Fase 2 bisa dikerjakan kapan saja, independen dari fase lain — tidak ada urutan wajib dengan Bagian B–D.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Admin menyusun peta dari layer yang sudah stale (kategori baru pasca-backfill tidak muncul) | Ditulis eksplisit di halaman "pilih layer" — bukan disembunyikan; tidak diperbaiki lewat Observer di dokumen ini (di luar cakupan) |
| Token share bocor lewat log/URL history | `token_hash` (SHA-256) yang disimpan, bukan plaintext — kebocoran database tidak membocorkan token asli; `ip_hash` juga di-hash, bukan IP mentah |
| Endpoint publik `/peta/bagikan/{token}` dipakai untuk brute-force menebak token | Token 48 karakter acak (`Str::random(48)`) — ruang tebakan besar; tambahkan rate limit (`throttle`) di route publik sebagai lapisan tambahan |
| `reorder()` race condition kalau dua admin edit peta yang sama bersamaan | Skala pemakaian kecil (admin internal, bukan trafik publik) — dampak diabaikan untuk versi pertama; bisa ditambah optimistic locking kalau nanti jadi masalah nyata |
| Snapshot `config_snapshot` membesar kalau peta punya banyak layer dengan `style_config` kompleks | `jsonb`, Postgres menangani ini secara native tanpa masalah performa berarti di skala data proyek ini |

## Testing

| Fase | File test (baru) | Skenario minimal |
| --- | --- | --- |
| 2 | `SpatialLayerMetadataTest` | Simpan metadata untuk kategori dengan layer; redirect aman untuk kategori tanpa layer |
| 3 | `MapManagementTest` | CRUD `Map`; attach/detach/reorder `MapLayer` |
| 4 | `MapPublicationTest` | Publish berurutan menghasilkan revisi bertambah, `is_current` pindah ke revisi terbaru |
| 5 | `MapShareAdminTest` | Generate share menghasilkan token yang bisa dipakai sekali; revoke membuat share tidak valid |
| 6 | `PublicMapShareTest` | Link valid/revoked/expired/tidak-ada — masing-masing hasil yang benar, tidak ada 500 |

Regresi wajib tiap fase: `php artisan test --compact` penuh — baseline gagal yang sudah diketahui (`Auth\RegistrationTest`/`AuthenticationTest`, `FeedbackStoreTest`) tidak boleh bertambah.

## Cara Mencoba (Manual, Setelah Fase 1–6 Selesai)

1. Login sebagai super-admin, buka menu baru "Kelola Peta" → buat peta baru ("Peta Uji Coba").
2. Di halaman builder, tambahkan 2–3 layer dari daftar `spatial_layers` aktif, atur urutan & opacity.
3. Klik "Terbitkan" → cek muncul "revisi #1".
4. Di halaman revisi, klik "Buat Link Berbagi" → salin link yang ditampilkan (hanya sekali).
5. Buka link itu di tab **incognito** (tanpa login) → peta harus tampil dengan layer sesuai yang disusun.
6. Kembali ke admin, klik "Cabut Link" → buka ulang link yang sama → harus 404.
7. Isi metadata untuk salah satu kategori/layer (Bagian A) → buka lagi halamannya → nilai tersimpan.

## Jalur ke Depan

- **Migrasi sistem share lama** (`SharedMap`, `/peta-tematik/share`) ke `MapShare` — setelah sistem baru terbukti stabil, evaluasi apakah `/peta-tematik` publik bisa memakai `MapShare` juga, sehingga `SharedMap` bisa dipensiunkan mengikuti pola Prioritas 8 (`@deprecated` dulu, bukan langsung dihapus).
- **Observer sinkronisasi `categories`→`spatial_layers`** — kalau nanti diputuskan (topik terpisah, bukan bagian dokumen ini), keterbatasan "layer stale" di Bagian B otomatis hilang tanpa perlu mengubah apa pun di halaman builder Kelola Peta.
- **Dashboard eksekutif/tren pembangunan** — tetap topik terpisah di luar cakupan dokumen ini, sesuai catatan revisi di awal.
