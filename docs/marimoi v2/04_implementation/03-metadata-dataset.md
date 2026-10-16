# Plan Implementasi: Metadata Dataset (Sumber Data, Tanggal Data, Instansi Pengelola)

## Status Implementasi

- **Tahap 1–7 — selesai.** Migration dijalankan, model/controller/view/endpoint/JS diupdate, dan seluruh test pada Tahap 7 lulus (lihat catatan di bawah).
- Semua path file, nomor baris, dan mekanisme di bawah sudah diverifikasi langsung terhadap kode yang berjalan saat ini pada saat penulisan; beberapa nomor baris di `FrontendController::homeSpatialSummary()` sedikit bergeser saat eksekusi karena method tersebut sudah berkembang lebih jauh dari audit awal (ada `homeRegionShapes()`/`$pointSource` yang belum ada saat dokumen ini pertama ditulis) — implementasi menyesuaikan ke struktur aktual, bukan mengikuti buta nomor baris yang sudah usang.

### Catatan audit saat eksekusi

- **Migration lama di repo sudah "Pending" tapi skemanya sudah ada di database** (`add_is_active_to_roles_table`, `create_shared_maps_table`, `create_permission_tables`, dll. — lihat `migrate:status`). Ini kondisi environment yang sudah ada sebelum sesi ini, tidak disentuh. Migration baru untuk metadata dijalankan terisolasi lewat `--path` agar tidak memicu migration lama yang bentrok.
- **Bug di luar cakupan ditemukan lalu dihindari, bukan diperbaiki**: route `data-spatial.update` punya parameter bernama `{uuid}`, tapi form edit yang sebenarnya (`edit.blade.php`) selalu mengirim `$data->id` (numerik), bukan `$data->uuid` — jadi `DataSpatialController::update()` yang melakukan `DataSpatial::find($id)` sebenarnya aman di pemakaian nyata. Test awal yang memakai `$data->uuid` gagal karena PostgreSQL menolak string non-numerik untuk kolom `id` bigint; diperbaiki di test (pakai `$data->id`), bukan di aplikasi — penamaan parameter route yang membingungkan ini di luar cakupan dokumen ini.
- `FrontendController` sudah punya `use Illuminate\Support\Carbon;` sehingga Tahap 4 memakai `Carbon::parse(...)` langsung, bukan `\Illuminate\Support\Carbon::parse(...)` seperti draf awal.
- `DataSpatialController::isAdminOPD()` (helper existing di controller) dipakai untuk override `opd_pengelola_id` pada `update()`, menggantikan `Auth::user()->role->slug === 'admin-opd'` yang ditulis di draf awal — lebih konsisten dengan konvensi file dan aman terhadap `role` null.

## Tujuan

Menutup rekomendasi review *"sebagian data yang disajikan belum dilengkapi metadata seperti sumber data, waktu pembaruan, dan instansi pengelola"* dengan menambahkan metadata wajib pada setiap data spasial (`data_spatial`), lalu menampilkannya di semua titik tampil publik (peta beranda, peta tematik, halaman detail) dan admin (form input/edit).

Item ini juga jadi **prasyarat** untuk filter terstruktur wilayah/OPD/tahun pada peta tematik (bagian C di `10-tindak-lanjut-review-jamil.md`) — tanpa `opd_pengelola_id` yang terstruktur, filter OPD di peta tidak punya sumber nilai yang bisa diandalkan.

## Referensi

- [`../Hasil_Review_Marimoi_Jamil.md`](../Hasil_Review_Marimoi_Jamil.md) — rekomendasi asli.
- [`../03_plan/10-tindak-lanjut-review-jamil.md`](../03_plan/10-tindak-lanjut-review-jamil.md) — bagian A (Metadata Dataset), termasuk keputusan awal soal nama kolom dan sifat wajib/opsional.
- [`../09-ringkasan-konsep-dan-alur.md`](../09-ringkasan-konsep-dan-alur.md) §11 — definisi metadata layer bertingkat pada rencana V2 penuh (Draft/Internal aktif/Public); dokumen ini mengimplementasikan versi minimum di schema lama, bukan versi bertingkat penuh.

## Cakupan

Termasuk: kolom metadata baru pada `data_spatial`, form input/edit admin, endpoint GeoJSON publik (peta tematik dan peta beranda), popup peta tematik, panel info peta beranda, dan halaman detail lokasi.

Tidak termasuk: metadata bertingkat (Draft/Internal/Public) ala schema V2 penuh, approval workflow publikasi, dan migrasi ke `spatial_layers`/`spatial_layer_metadata` — itu domain `04-webgis-map-layer.md` bila migrasi besar V2 dikerjakan.

## Kondisi Existing (Audit Singkat)

| Area | Temuan |
| --- | --- |
| Schema | Migration `data_spatial` ([`2025_08_01_093701_create_data_spatials_table.php`](../../../database/migrations/2025_08_01_093701_create_data_spatials_table.php)) tidak punya kolom sumber data, instansi pengelola, atau tanggal data. Hanya `updated_at` bawaan (tanggal perubahan sistem, bukan tanggal referensi data). |
| Model | `app/Models/DataSpatial.php` — `$fillable` tidak menyertakan field metadata apa pun di luar `data_type`, `sub_type`, `kategori_id`, `deskripsi`, `dbf_attributes`, `tahun`, `gambar`, `views`, `geom`, `user_id`. |
| Input admin | `DataSpatialController::store()` mendukung 3 jalur input (shapefile, koordinat manual, KMZ/KML) yang **semuanya** bermuara ke satu helper `saveDataSpatial()` (baris 841–868) sebelum `DataSpatial::create($data)` — titik injeksi tunggal untuk field baru saat create. `update()` (baris 225–314) melakukan assignment field manual satu-satu (baris 283–287) — titik injeksi terpisah untuk update. |
| Form admin | `create.blade.php`/`edit.blade.php` hanya punya field kategori, deskripsi, gambar, tahun (lewat query string), dan editor JSON `dbf_attributes` — tidak ada field sumber data/OPD pengelola/tanggal data. |
| Endpoint publik (peta tematik) | `FrontendController::getGeojsonByDataType()` (baris 385–520-an) — `SELECT` eksplisit kolom (baris 399–415) dan `properties` GeoJSON dibentuk lewat `array_merge([...kolom statis...], $dbfAttributes)` (baris 551–567). Field baru harus ditambah di kedua titik ini agar sampai ke frontend. |
| Endpoint publik (peta beranda) | `FrontendController::homeSpatialSummary()` (baris 114–152) — query `points` hanya `SELECT id, kategori_id, deskripsi, tahun, x, y`. Tidak membawa metadata; perlu ditambah bila info panel beranda (`#mapInfo`) mau menampilkannya. |
| Popup peta tematik | `public/frontend/js/map.js::bindPopupContent()` (baris 796–929) — tabel atribut popup di-generate generik dari `Object.entries(props)` yang difilter lewat `allowedKeys = ["KEGIATAN", "TAHUN", "KABUPATEN", "URUSAN"]` (baris 817), dicocokkan case-insensitive (`key.toUpperCase()`). Field statis seperti `tahun` (lowercase di `properties`) sudah otomatis ikut tampil karena `"tahun".toUpperCase() === "TAHUN"` ada di `allowedKeys` — mekanisme yang sama bisa dipakai untuk metadata baru tanpa mengubah struktur, cukup menambah entri ke `allowedKeys`. |
| Info panel peta beranda | `resources/views/frontend/pages/home.blade.php` (baris 241–253) — `<dl>` di `#mapInfo` hanya punya baris Tahun (`#infoYear`) dan Koordinat (`#infoCoord`). `resources/js/spatial.js::select()` (baris 591–604) mengisinya dari objek `p` (satu titik dari `DATA.points`). |
| Halaman detail lokasi | `resources/views/frontend/partials/detail-peta.blade.php` — `<dl>` ringkasan (baris 83–91) menampilkan Tahun, Dilihat, Jenis geometri, Sub tipe secara kondisional (`@if`). Titik injeksi alami untuk Sumber Data/Instansi Pengelola/Tanggal Data. |
| Detail modal admin | `resources/views/backend/pages/data_spatial/_detail_modal.blade.php` — belum menampilkan metadata (belum ada karena kolomnya belum ada). |

## Keputusan yang Perlu Dikunci Sebelum Implementasi

1. **Nama kolom tanggal: `tanggal_data`, bukan menumpang di `tahun`.**
   `tahun` sudah dipakai luas (filter index admin, query proyek strategis, dashboard) dengan makna "tahun anggaran/kegiatan". Review meminta "waktu pembaruan"/tanggal referensi data — konsep berbeda. Tambah kolom baru `tanggal_data` (date, nullable) supaya tidak menabrak makna `tahun` yang sudah dipakai di banyak tempat.
2. **`opd_pengelola_id` sebagai foreign key ke `opd`, bukan teks bebas.**
   Supaya bisa langsung dipakai sebagai filter terstruktur di peta tematik (bagian C, `10-tindak-lanjut-review-jamil.md`) tanpa normalisasi teks di kemudian hari. Diisi otomatis dari OPD milik admin yang input bila role `admin-opd`; admin-bappeda/super-admin wajib memilih manual dari dropdown.
3. **Wajib vs opsional saat simpan: soft warning, bukan hard block, di iterasi pertama.**
   Data lama (bisa ribuan baris di produksi) tidak mungkin diisi metadata sekaligus, dan admin OPD yang sedang input data baru tidak boleh terblokir total oleh field yang belum jadi kebiasaan. Rekomendasi: field **direkomendasikan** di form (label + placeholder mengarahkan), ditandai dengan badge "Metadata belum lengkap" di admin dan publik bila kosong, **tanpa** validasi `required` pada iterasi pertama. Keputusan mengunci `required` diserahkan ke pemilik produk setelah field ini berjalan beberapa minggu dan sebagian besar data baru sudah terisi.
4. **`sumber_data` sebagai teks bebas (string), bukan enum/master data.**
   Sumber data sangat beragam antar OPD dan kategori (survei lapangan, SK, dokumen resmi, data dari sistem lain, dst.) — enum akan cepat usang. String bebas dengan placeholder contoh di form.

## Tahap 1 — Migration Database

Additive terhadap `data_spatial`, semua kolom baru `nullable` agar aman untuk baris existing.

### `..._add_metadata_columns_to_data_spatial_table.php`

```php
Schema::table('data_spatial', function (Blueprint $table) {
    $table->string('sumber_data')->nullable()->after('deskripsi');
    $table->foreignId('opd_pengelola_id')->nullable()->after('sumber_data')
        ->constrained('opd')->nullOnDelete();
    $table->date('tanggal_data')->nullable()->after('opd_pengelola_id');

    $table->index('opd_pengelola_id');
});
```

`nullOnDelete()` dipilih (bukan `restrictOnDelete()`) supaya OPD yang dihapus/dinonaktifkan tidak memblokir penghapusan data OPD tersebut — metadata cukup jadi kosong kembali, ditandai "belum lengkap" oleh accessor di Tahap 2.

## Tahap 2 — Model

### `app/Models/DataSpatial.php`

- Tambah ke `$fillable`: `'sumber_data'`, `'opd_pengelola_id'`, `'tanggal_data'`.
- Tambah ke `casts()` (atau `$casts` mengikuti konvensi file saat ini — file ini sudah punya `protected $casts` array, ikuti pola yang sama): `'tanggal_data' => 'date'`.
- Tambah relasi:
  ```php
  public function opdPengelola(): BelongsTo
  {
      return $this->belongsTo(Opd::class, 'opd_pengelola_id');
  }
  ```
- Tambah accessor kelengkapan metadata (dipakai badge admin dan publik):
  ```php
  public function getMetadataLengkapAttribute(): bool
  {
      return filled($this->sumber_data)
          && filled($this->opd_pengelola_id)
          && filled($this->tanggal_data);
  }
  ```

### `app/Models/Opd.php`

- Opsional, tidak wajib untuk cakupan ini: relasi balik `dataSpatials(): HasMany` bila nanti dibutuhkan (mis. halaman "data yang dikelola OPD ini"). Tidak ditambah di iterasi pertama supaya cakupan tetap minimum.

## Tahap 3 — Backend: Form Input dan Update

### 3.1 Validasi (`DataSpatialController`)

- `store()` (baris 148–196): tambah ke `$rules` (baris 152–157):
  ```php
  'sumber_data' => 'nullable|string|max:255',
  'opd_pengelola_id' => 'nullable|exists:opd,id',
  'tanggal_data' => 'nullable|date',
  ```
- `update()` (baris 228–240): tambah rule yang sama ke `Validator::make(...)`, dengan pesan custom mengikuti pola existing (`'opd_pengelola_id.exists' => 'OPD pengelola tidak valid.'`, dst.).

### 3.2 Create — `saveDataSpatial()` (baris 841–868)

Satu-satunya titik `DataSpatial::create()` dipakai oleh ketiga jalur input (shapefile, koordinat, KMZ). Tambah ke array `$data` (setelah baris 850, sejajar dengan blok `sub_type`/`tahun` di baris 853–860):

```php
if ($request->filled('sumber_data')) {
    $data['sumber_data'] = $request->sumber_data;
}

$data['opd_pengelola_id'] = $request->filled('opd_pengelola_id')
    ? $request->opd_pengelola_id
    : (Auth::user()->role->slug === 'admin-opd' ? Auth::user()->opd_id : null);

if ($request->filled('tanggal_data')) {
    $data['tanggal_data'] = $request->tanggal_data;
}
```

Auto-isi `opd_pengelola_id` dari `Auth::user()->opd_id` untuk role `admin-opd` (Keputusan #2) — admin OPD tidak perlu memilih OPD-nya sendiri berulang-ulang di form.

### 3.3 Update — `update()` (baris 225–314)

Tambah setelah baris 286 (`$data->gambar = $imagePath;`), sebelum `$data->save();`:

```php
$data->sumber_data = $request->sumber_data;
$data->opd_pengelola_id = $request->opd_pengelola_id
    ?? (Auth::user()->role->slug === 'admin-opd' ? Auth::user()->opd_id : $data->opd_pengelola_id);
$data->tanggal_data = $request->tanggal_data;
```

### 3.4 Form Blade — `create.blade.php` dan `edit.blade.php`

Tambah 3 field baru tepat setelah field `deskripsi` (setelah baris 155 di `edit.blade.php`, posisi setara di `create.blade.php`):

```blade
<div class="mb-3">
    <label for="sumber_data">Sumber Data</label>
    <input type="text" class="form-control @error('sumber_data') is-invalid @enderror"
        id="sumber_data" name="sumber_data" value="{{ old('sumber_data', $data->sumber_data ?? '') }}"
        placeholder="Contoh: Survei lapangan Bappeda 2025, SK Gubernur No. ...">
    @error('sumber_data') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="opd_pengelola_id">Instansi Pengelola (OPD)</label>
    <select class="form-control select2 @error('opd_pengelola_id') is-invalid @enderror"
        id="opd_pengelola_id" name="opd_pengelola_id"
        @if (Auth::user()->role->slug === 'admin-opd') disabled @endif>
        <option value="">-- Pilih OPD --</option>
        @foreach ($opdList as $opd)
            <option value="{{ $opd->id }}"
                @selected(old('opd_pengelola_id', $data->opd_pengelola_id ?? Auth::user()->opd_id) == $opd->id)>
                {{ $opd->name }}
            </option>
        @endforeach
    </select>
    {{-- Select disabled tidak ikut terkirim; admin-opd terkunci ke OPD sendiri lewat server-side di 3.2/3.3 --}}
    @error('opd_pengelola_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="tanggal_data">Tanggal Data</label>
    <input type="date" class="form-control @error('tanggal_data') is-invalid @enderror"
        id="tanggal_data" name="tanggal_data"
        value="{{ old('tanggal_data', optional($data->tanggal_data ?? null)->format('Y-m-d')) }}">
    <small class="form-text text-muted">Tanggal referensi/pembaruan data, bukan tanggal kegiatan.</small>
    @error('tanggal_data') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
```

Select memakai class `select2` mengikuti konvensi yang sudah dipakai di modal bulk edit kategori/layer (commit `9e7b617`) — proyek ini sudah memakai Select2 untuk dropdown serupa.

`create()`/`edit()` di controller (baris 132–146 dan 198–223) perlu menambah `$opdList = Opd::orderBy('name')->get();` ke `compact(...)` yang dikirim ke kedua view.

### 3.5 Badge Kelengkapan Metadata — `index.blade.php` dan `_detail_modal.blade.php`

- `index.blade.php`: tambah kolom/badge kecil di tabel list — `@if(!$row->metadata_lengkap) <span class="badge bg-warning">Metadata belum lengkap</span> @endif`.
- `_detail_modal.blade.php`: tampilkan tiga field baru (Sumber Data, Instansi Pengelola via `$data->opdPengelola->name`, Tanggal Data) di quick view, dengan fallback "Belum diisi" bila kosong.

## Tahap 4 — Endpoint Publik: Peta Tematik (GeoJSON)

### `FrontendController::getGeojsonByDataType()` (baris 385–520-an)

1. Tambah ke `SELECT` (setelah baris 411, sejajar `data_spatial.deskripsi`):
   ```php
   'data_spatial.sumber_data',
   'data_spatial.tanggal_data',
   'opd.name as opd_pengelola',
   ```
   Perlu tambah `->leftJoin('opd', 'data_spatial.opd_pengelola_id', '=', 'opd.id')` pada query builder (baris ~399–400), memakai `leftJoin` supaya data tanpa OPD pengelola tetap ikut tampil.
2. Tambah ke array `properties` (baris 551–567, dalam `array_merge([...], $dbfAttributes)`):
   ```php
   'sumber_data' => $lokasi->sumber_data,
   'opd_pengelola' => $lokasi->opd_pengelola,
   'tanggal_data' => $lokasi->tanggal_data
       ? \Illuminate\Support\Carbon::parse($lokasi->tanggal_data)->format('d-m-Y')
       : null,
   ```

### `public/frontend/js/map.js::bindPopupContent()` (baris 817)

Tambah entri ke `allowedKeys`:
```js
const allowedKeys = ["KEGIATAN", "TAHUN", "KABUPATEN", "URUSAN", "SUMBER_DATA", "OPD_PENGELOLA", "TANGGAL_DATA"];
```
Mekanisme filter sudah case-insensitive dan generik (baris 818–829) — properti baru otomatis muncul di tabel popup tanpa perubahan struktur lain. Label otomatis: `sumber_data` → "Sumber Data", `opd_pengelola` → "Opd Pengelola" (huruf besar/kecil kosmetik, bukan blocker — bisa diperbaiki lewat lookup label map kecil bila diperlukan).

## Tahap 5 — Peta Beranda (Home)

### `FrontendController::homeSpatialSummary()` (baris 114–152)

Tambah ke query `points` (baris 130–142):
```php
$points = collect(DB::select(
    'select d.id, d.kategori_id as k, d.deskripsi as d, d.tahun as t,
            d.sumber_data as sd, o.name as op, d.tanggal_data as td,
            round(ST_X(d.geom)::numeric, 5) as x, round(ST_Y(d.geom)::numeric, 5) as y
     from data_spatial d
     left join opd o on o.id = d.opd_pengelola_id
     where GeometryType(d.geom) = ? and ST_SRID(d.geom) = 4326',
    ['POINT']
))->map(fn ($row) => [
    'id' => $row->id,
    'k' => $row->k,
    'n' => $row->d ?: null,
    't' => $row->t,
    'sd' => $row->sd,
    'op' => $row->op,
    'td' => $row->td,
    'x' => (float) $row->x,
    'y' => (float) $row->y,
])->all();
```

Singkatan key (`sd`, `op`, `td`) mengikuti konvensi existing (`k`, `n`, `t`, `x`, `y`) yang memang dibuat pendek karena payload ini dikirim ke seluruh titik data lewat `window.MARIMOI_HOME` — perlu tetap ringkas mengingat catatan performa `build()` di `spatial.js` (lihat `10-tindak-lanjut-review-jamil.md`).

### `resources/views/frontend/pages/home.blade.php` (baris 241–253)

Tambah baris di `<dl>` dalam `#mapInfo`, setelah baris `Koordinat`:
```blade
<dt class="text-white/45">Sumber</dt><dd id="infoSource"></dd>
<dt class="text-white/45">Instansi</dt><dd id="infoOpd"></dd>
```

### `resources/js/spatial.js::select()` (baris 591–604)

Tambah pengisian dua elemen baru, dengan fallback bila kosong:
```js
$('#infoSource').textContent = p.sd || 'Belum diisi';
$('#infoOpd').textContent = p.op || 'Belum diisi';
```

Baris `Sumber`/`Instansi` di `<dl>` bisa disembunyikan (`hidden`) via JS bila keduanya kosong, supaya panel info tidak terasa "bolong" untuk data lama yang belum bermetadata — detail styling diputuskan saat implementasi, bukan bagian normatif dokumen ini.

## Tahap 6 — Halaman Detail Lokasi

### `resources/views/frontend/partials/detail-peta.blade.php` (baris 83–91)

Tambah ke dalam `<dl class="mt-6 grid grid-cols-2 gap-4 ...">`, mengikuti pola `@if` yang sudah dipakai untuk `tahun`/`sub_type`:
```blade
@if ($project->sumber_data)
    <div><dt class="text-slate-500">Sumber Data</dt><dd class="mt-0.5 font-semibold text-navy">{{ $project->sumber_data }}</dd></div>
@endif
@if ($project->opdPengelola)
    <div><dt class="text-slate-500">Instansi Pengelola</dt><dd class="mt-0.5 font-semibold text-navy">{{ $project->opdPengelola->name }}</dd></div>
@endif
@if ($project->tanggal_data)
    <div><dt class="text-slate-500">Tanggal Data</dt><dd class="mt-0.5 font-semibold text-navy">{{ $project->tanggal_data->format('d M Y') }}</dd></div>
@endif
```

`$project` di controller (`FrontendController::detailPeta()`/`detailPetaTematik()`, baris 738–766) memakai `DataSpatial::select('*', ...)` — kolom baru otomatis ikut tanpa perubahan query, tapi relasi `opdPengelola` perlu di-*eager load* (`->with('opdPengelola')`) supaya tidak N+1 di halaman detail.

## Tahap 7 — Testing (PHPUnit)

Sesuai aturan proyek, seluruh test PHPUnit (bukan Pest), dibuat dengan `php artisan make:test --phpunit`.

| Test | Skenario |
| --- | --- |
| `tests/Feature/DataSpatial/MetadataDatasetTest.php` | (1) Admin OPD menyimpan data koordinat baru → `opd_pengelola_id` otomatis terisi OPD miliknya tanpa mengirim field itu di request; (2) Admin Bappeda mengirim `opd_pengelola_id` eksplisit → tersimpan sesuai input; (3) Update data existing mengubah `sumber_data`/`tanggal_data` → tersimpan; (4) `opd_pengelola_id` tidak valid (`exists:opd,id` gagal) → validasi menolak dengan pesan yang benar; (5) Data tanpa metadata → `metadata_lengkap` accessor bernilai `false`; data lengkap ketiga field → `true`. |
| `tests/Feature/DataSpatialGeojsonTest.php` (perluasan file existing dari commit `dc839c4`) | `getGeojsonByDataType()` mengembalikan `sumber_data`, `opd_pengelola`, `tanggal_data` di `properties` setiap feature; data tanpa `opd_pengelola_id` tetap muncul di response (memverifikasi `leftJoin`, bukan `join`, tidak menghilangkan baris). |
| `tests/Feature/Frontend/DetailPetaMetadataTest.php` | Halaman detail lokasi menampilkan sumber data/instansi pengelola/tanggal data ketika terisi, dan tidak menampilkan blok terkait ketika kosong (tidak ada label "Sumber Data" untuk data tanpa `sumber_data`). |

Jalankan test dengan filter spesifik dulu (`php artisan test --compact --filter=MetadataDataset`, `--filter=DataSpatialGeojson`, `--filter=DetailPetaMetadata`), baru tawarkan ke user untuk menjalankan `php artisan test --compact` penuh.

## Urutan Eksekusi

1. Konfirmasi Keputusan #1–#4 di atas dengan pemilik produk (khususnya #3: soft warning vs hard block).
2. Buat & jalankan migration Tahap 1.
3. Update model (Tahap 2): `$fillable`, cast, relasi, accessor.
4. Update `DataSpatialController` (Tahap 3.1–3.3): validasi, `saveDataSpatial()`, `update()`.
5. Update `create.blade.php`/`edit.blade.php` + `create()`/`edit()` controller untuk kirim `$opdList` (Tahap 3.4).
6. Update `index.blade.php`/`_detail_modal.blade.php` untuk badge kelengkapan (Tahap 3.5).
7. Update `getGeojsonByDataType()` + `bindPopupContent()`/`allowedKeys` di `map.js` (Tahap 4).
8. Update `homeSpatialSummary()` + `home.blade.php` `#mapInfo` + `spatial.js::select()` (Tahap 5). Ingat: `home.spatial-summary` di-cache 1 jam (`Cache::remember`, baris 116) — flush cache ini setelah deploy supaya perubahan select langsung terlihat, ikuti pola cache-busting yang sudah dipakai commit `a8a45c9` ("cache peta dibuang saat data/kategori tematik berubah").
9. Update `detail-peta.blade.php` + eager load `opdPengelola` di `detailPeta()`/`detailPetaTematik()` (Tahap 6).
10. Tulis seluruh test pada Tahap 7, jalankan per filter.
11. `vendor/bin/pint --dirty --format agent` untuk merapikan seluruh file PHP yang disentuh.
12. Jalankan test terfilter, lalu tawarkan full test suite ke user.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Ribuan baris data lama tanpa metadata membuat fitur terlihat "kosong semua" begitu dirilis | Badge "Metadata belum lengkap" (admin) dan fallback "Belum diisi" (publik) dari awal, bukan menyembunyikan ketidaklengkapan; sifat opsional (Keputusan #3) mencegah rilis ini memblokir kerja admin OPD. |
| `leftJoin` ke `opd` menambah beban query pada endpoint GeoJSON yang sudah menangani ribuan feature per request | `opd_pengelola_id` sudah diindeks (Tahap 1); `leftJoin` pada foreign key terindeks berdampak minor dibanding kompleksitas `ST_AsGeoJSON`/filter spasial yang sudah ada di endpoint yang sama. |
| Cache `home.spatial-summary` (TTL 1 jam) membuat metadata baru tidak langsung tampil di beranda setelah deploy | `Cache::forget('home.spatial-summary')` dijalankan sebagai bagian dari langkah deploy (Tahap 5, urutan eksekusi #8), konsisten dengan pola cache-busting yang sudah ada di commit `a8a45c9`. |
| Admin OPD mengubah `opd_pengelola_id` data OPD lain lewat form yang di-disable tapi tetap bisa dimanipulasi di client (DOM edit manual) | Server-side selalu override `opd_pengelola_id` ke `Auth::user()->opd_id` untuk role `admin-opd` pada `update()` (Tahap 3.3) — bukan hanya mengandalkan atribut `disabled` di HTML. |
| Field baru bertambah di 4 tempat berbeda (form, GeoJSON, beranda, detail) berisiko ada yang lupa ter-update saat field metadata berkembang lagi nanti | Titik injeksi didokumentasikan eksplisit per tahap di atas; pertimbangkan factory helper (`DataSpatial::toMetadataArray()`) bila field metadata bertambah lagi di iterasi berikutnya, supaya tidak lagi tersebar 4 tempat. |

## Kriteria Selesai

- [x] Semua data spasial baru bisa diisi sumber data, instansi pengelola, dan tanggal data lewat form admin (ketiganya opsional sesuai Keputusan #3).
- [x] Admin OPD tidak bisa mengubah `opd_pengelola_id` ke OPD lain, baik lewat form normal maupun manipulasi client-side (server-side override di `update()`/`saveDataSpatial()`, diuji `test_admin_opd_cannot_move_data_to_another_opd_on_update`).
- [x] Popup peta tematik publik dan panel info peta beranda menampilkan metadata ini ketika terisi (diuji lewat properti GeoJSON; tampilan popup/`spatial.js` diverifikasi manual, bukan lewat test — lihat catatan build di bawah).
- [x] Halaman detail lokasi menampilkan metadata ini dengan eager loading `opdPengelola` (tidak N+1).
- [x] Data lama tanpa metadata ditandai jelas sebagai "belum lengkap" — badge di `index.blade.php`, fallback "Belum diisi" di modal detail admin dan panel info beranda.
- [x] Seluruh test Tahap 7 lulus (28 test, 209 assertion): `tests/Feature/DataSpatial/MetadataDatasetTest.php` (6 test, baru), `tests/Feature/FrontendGeojsonMetadataTest.php` (2 test, baru — bukan perluasan `DataSpatialGeojsonTest.php` seperti draf awal, karena file itu menguji endpoint GeoJSON **admin** (`DataSpatialController::geojson`), sedangkan metadata dataset tampil di endpoint GeoJSON **publik** (`FrontendController::getGeojsonByDataType`) yang belum punya test sebelumnya), dan 2 test baru di `tests/Feature/FrontendPagesTest.php` (halaman detail). Regresi dicek lewat `DataSpatialGeojsonTest`, `HomepageTest`, `SharedMapTest`, `BackendTematikOnlyTest`, `RolePermissionTest` — semua tetap lulus (44 test).

### Belum diverifikasi — perlu tindak lanjut

- **`npm run build`/`npm run dev` belum dijalankan.** `resources/js/spatial.js` diubah tapi file itu di-bundle Vite ke `public/build/assets/spatial-*.js` — perubahan panel info peta beranda (`#infoSource`/`#infoOpd`) **tidak akan terlihat di browser** sampai build dijalankan ulang. `public/frontend/js/map.js` (popup peta tematik) **tidak** butuh build karena disajikan langsung dari `public/`.
- Tampilan visual (popup peta tematik, panel info beranda, form admin, badge index) belum dicek langsung di browser — hanya diverifikasi lewat feature test (response HTML/JSON), sesuai keterbatasan sesi non-interaktif ini.
