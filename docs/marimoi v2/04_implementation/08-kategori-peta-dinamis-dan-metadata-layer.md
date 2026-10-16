# Plan Implementasi: Kategori Peta Dinamis & Arsitektur Layer (Bagian 6 & 7)

## Status Implementasi

**Tahap 1, 2, 3, 4, 6 — selesai (2026-09-27).** Tahap 5 (`administrative_regions`) dan Tahap 7 (`analysis_layers`/`analysis_results`) **sengaja ditunda** sesuai keputusan eksekusi berikut (dikunci oleh product owner sebelum implementasi):

1. CRUD `map_types` ditambahkan sebagai permission baru (`map-types.manage`), default hanya diberikan ke `super-admin` (otomatis lewat `PermissionSeeder`, tidak ditambahkan ke role lain).
2. Batasan "maksimal 10 kategori aktif" dipertahankan, kuncinya dipindah dari `type` (string) ke `map_type_id`.
3. Tahap 5 (`administrative_regions`) ditunda — sumber data batas wilayah resmi belum ditentukan.
4. Tahap 7 bagian jadwal job ditunda — tidak ada job terjadwal yang didaftarkan.
5. Tahap 7 bagian rumus/command analisis ditunda — `analysis_layers`/`analysis_results` tidak dibuat sama sekali di iterasi ini (keduanya juga bergantung pada Tahap 5 yang ditunda).

Ringkasan yang benar-benar dieksekusi: 6 migration baru, 4 model baru (`MapType`, `Sektor`, `DataSpatialIntervention`, plus perubahan `Category`/`DataSpatial`), 2 controller baru (`MapTypeController`, `DataSpatialInterventionController`), perubahan pada `CategoryController` dan `DataSpatialController`, 1 permission baru, 5 file test baru (16 test, 44 assertion), 3 view baru (`map-types/{index,create,edit}`). Regresi penuh: 181 test lulus (644 assertion); 8 kegagalan tersisa seluruhnya pra-existing dan tidak terkait (`AuthenticationTest`/`RegistrationTest` — route `/register` sudah 404 sebelum sesi ini; `FeedbackStoreTest` — `ProyekStrategisDaerahFactory` memang belum pernah dibuat, dikonfirmasi lewat `git log`/pencarian file).

### Catatan audit saat eksekusi

- **Temuan regresi nyata, diperbaiki:** Backfill awal men-set `is_active=true` untuk seluruh 5 `map_types` (termasuk `psd`/`psn`/`pokir_dprd`/`usulan_musrenbang`). Ini membuat `BackendTematikOnlyTest::test_category_index_rejects_legacy_types` gagal — test itu secara sengaja memverifikasi bahwa tipe legacy ditolak di UI admin kategori (sesuai migration `2026_09_19_075917_merge_legacy_map_types_into_tematik`). Diperbaiki dengan men-set `is_active=false` untuk keempat tipe legacy tersebut di migration backfill — barisnya tetap ada (untuk backfill/FK data lama yang masih hidup), tapi tidak muncul sebagai opsi valid lagi, mempertahankan perilaku lama persis seperti sebelumnya.
- **Tahap 2 disesuaikan dari rencana awal:** Form edit `data_spatial` menyimpan `dbf_attributes` sebagai string JSON via hidden input yang diisi JS (tabel key/value dinamis), bukan array field HTML biasa seperti asumsi awal dokumen ini. Validasi atribut dinamis (`Category::atributValidationRules()`) diterapkan dengan mem-validasi array hasil `json_decode` secara terpisah (`Validator::make($dbfAttributes, $rules)`), bukan digabung ke rules utama request — UI JS tabel key/value yang sudah ada **tidak diubah** (di luar cakupan, murni kosmetik).
- **Tahap 3 dijalankan terbatas:** `is_group` dan `Category::scopeSelectable()` diimplementasikan dan diuji penuh, tapi `LayerGroupSeeder` **tidak didaftarkan** di `DatabaseSeeder` — sengaja tidak dijalankan terhadap data kategori nyata (187 baris `tematik` sudah dipakai OPD) karena akan mengubah struktur sidebar/dropdown publik tanpa keputusan produk eksplisit. Seeder terverifikasi lewat test terisolasi (`CategoryHierarchyTest`, `RefreshDatabase`). Jalankan manual (`php artisan db:seed --class=LayerGroupSeeder`) hanya setelah keputusan produk soal taksonomi final dikunci.
- **Drift migration lama di database dev (`marimoi_v2`) — sudah diperbaiki.** `php artisan migrate` awalnya gagal di 9 migration lama (`2026_08_27_052942_add_is_active_to_roles_table` dst., termasuk `create_permission_tables` dan `merge_legacy_map_types_into_tematik`) — tabel `migrations` tidak mencatatnya sebagai sudah dijalankan padahal skemanya sudah ada di database (drift pra-existing, bukan disebabkan sesi ini). Diperbaiki dengan menandai kesembilan migration tersebut sebagai sudah dijalankan (insert metadata batch ke tabel `migrations`, **tanpa** menjalankan ulang `up()`-nya — penting karena `merge_legacy_map_types_into_tematik` berisi `UPDATE` yang akan menggabungkan ulang data `psd`/`psn`/`pokir_dprd`/`usulan_musrenbang` yang masih hidup ke `tematik` bila dijalankan lagi). Setelah tracker diperbaiki, keenam migration baru Tahap 1/2/3/4/6 berhasil diterapkan ke `marimoi_v2` tanpa error, backfill `categories.map_type_id` terverifikasi 100% (212/212 baris, distribusi persis sama dengan sebelum migrasi: tematik=187, psd=12, usulan_musrenbang=10, pokir_dprd=2, psn=1). `PermissionSeeder` dan `SektorSeeder` juga sudah dijalankan (permission `map-types.manage` aktif untuk super-admin; 5 sektor awal ter-seed).
- Verifikasi manual di browser (CRUD jenis peta, form atribut dinamis, penautan intervensi) masih belum dilakukan dari sesi ini (tidak ada akses browser), tapi seluruh query controller inti sudah diuji langsung terhadap data live lewat tinker tanpa error, dan `php artisan test` (181 lulus, 8 kegagalan pra-existing tidak terkait) serta `pint` bersih setelah migrasi diterapkan.

## Tujuan

Mewujudkan 7 kebutuhan yang sudah dianalisis (bukan diputuskan ulang di sini — keputusan arsitektur sudah dikunci di Bagian 6/7, dokumen ini hanya menerjemahkannya ke migration/model/controller/view nyata):

1. Kategori peta (`map_types`) dapat dibuat dinamis lewat UI admin, bukan lewat deploy kode.
2. Kategori (`categories`) punya skema metadata dinamis (`atribut_schema`) di luar metadata utama.
3. `categories` disusun 3 tingkat (Kelompok → Subkelompok → Layer) sesuai taksonomi yang direkomendasikan.
4. Dimensi `sektor` sebagai filter independen dari kelompok/kategori.
5. Referensi wilayah administratif (`administrative_regions`) dengan geometri batas.
6. Penautan eksplisit kondisi eksisting ↔ intervensi pembangunan (`data_spatial_interventions`).
7. Fondasi layer analisis terkomputasi (`analysis_layers` + `analysis_results` + job terjadwal).

## Referensi

- [`../01_db-analysis/01-current-schema.md`](../01_db-analysis/01-current-schema.md) Bagian 6 dan 7 — audit dan rekomendasi yang diimplementasikan di sini.
- [`../01_db-analysis/Rekomendasi_Pengelompokan_Layer_MARIMOI.md`](../01_db-analysis/Rekomendasi_Pengelompokan_Layer_MARIMOI.md) — dokumen sumber taksonomi.
- [`07-perbaikan-effort-kecil.md`](07-perbaikan-effort-kecil.md) — pola migrasi additive/non-destruktif dan konvensi eksekusi yang dipakai ulang di sini (`config/marimoi.php`, grace period, dsb.).

## Cakupan

**Termasuk:** 7 Tahap sesuai urutan prioritas di Bagian 7.4 (map_types → atribut_schema → reorganisasi categories 3-tingkat → sektor → administrative_regions → data_spatial_interventions → analysis_layers/results).

**Tidak termasuk (di luar cakupan dokumen ini, butuh keputusan/dokumen terpisah):**
- Pencocokan spasial otomatis (`ST_DWithin`) untuk `data_spatial_interventions` — Tahap 6 hanya menyediakan struktur data untuk tautan **manual**.
- Sumber data resmi batas wilayah administratif (shapefile BPS/Bappeda) — Tahap 5 menyediakan tabel dan import command, tapi bukan penyedia data itu sendiri.
- Deprekasi penuh 5 model duplikat (`Lokasi`, `PokirDprd`, `ProyekStrategisDaerah`, `ProyekStrategisNasional`, `UsulanMusrenbang`) — disinggung di Tahap 1 sebagai catatan, tidak dieksekusi di sini karena berisiko lebih tinggi dan butuh audit pemakaian terpisah.
- Metodologi/rumus tiap `analysis_layer` (konektivitas, aksesibilitas, dst.) — Tahap 7 hanya menyediakan kerangka penyimpanan hasil dan satu contoh perhitungan sederhana; rumus final tetap keputusan Bappeda/perencana, bukan keputusan teknis.

## Kondisi Existing yang Diaudit (Verifikasi Tambahan)

Selain audit file:line di Bagian 6.1/7.1, berikut hasil query langsung ke database live (`mcp__laravel-boost__database-query`, 2026-09-27) yang menegaskan data legacy **masih hidup**, bukan cuma kode mati:

| Query | Hasil |
| --- | --- |
| `SELECT type, count(*) FROM categories GROUP BY type` | `tematik=187`, `psd=12`, `usulan_musrenbang=10`, `pokir_dprd=2`, `psn=1` |
| `SELECT data_type, sub_type, count(*) FROM data_spatial GROUP BY data_type, sub_type` | `tematik/null=11908`, `proyek_strategis/psd=115`, `usulan_musrenbang/null=23`, `proyek_strategis/psn=6`, `pokir_dprd/null=1` |

Catatan penting yang memengaruhi Tahap 1: migration `2026_09_19_075917_merge_legacy_map_types_into_tematik.php` pernah menggabungkan **seluruh** kategori/data non-tematik ke `tematik` pada satu titik waktu — tapi `database/seeders/KategoriLayerSeeder.php` (dijalankan lagi di setiap fresh install/seeding) membuat ulang kategori dengan 5 `type` berbeda, dan jalur input lain (di luar `CategoryController`/`DataSpatialController` yang sekarang dikunci `in:tematik`) tetap menghasilkan baris `usulan_musrenbang`/`pokir_dprd`/`proyek_strategis`. **Kesimpulan: kedua sistem type (kategori lama dan `data_type` proyek strategis baru) sama-sama masih punya data hidup dan harus ikut di-backfill — tidak boleh diasumsikan "cuma tematik yang dipakai".**

Lihat juga tabel audit lengkap di Bagian 6.1 dan 7.1 dokumen sumber untuk referensi file:line per masalah.

## Keputusan yang Perlu Dikunci (sebelum eksekusi)

| # | Keputusan | Opsi | Rekomendasi |
| --- | --- | --- | --- |
| 1 | Siapa yang boleh membuat `map_types` baru? | (a) super-admin saja, (b) admin-bappeda juga | (a) — perubahan taksonomi punya dampak lintas OPD, harus dikunci ke role tertinggi |
| 2 | Apakah cap "maksimal 10 kategori aktif per type" (`CategoryController::validateMaxActiveCategories`, baris ±131) tetap berlaku per `map_type_id`? | (a) tetap, dihitung per `map_type_id`, (b) dihapus | (a) — pertahankan batasan yang sudah ada, cuma pindah kunci hitung dari `type` ke `map_type_id` |
| 3 | Sumber data batas wilayah administratif (Tahap 5) | (a) impor shapefile BPS/Bappeda resmi, (b) tunda tabel ini, isi manual titik-per-titik | Keputusan produk/data, bukan teknis — Tahap 5 di bawah diberi status **opsional/dapat ditunda** sampai sumber data tersedia |
| 4 | Jadwal job `analysis_results` (Tahap 7) | (a) harian, (b) mingguan | (b) mingguan — data pembangunan tidak berubah secepat itu, mengurangi beban job |
| 5 | Rumus awal `analysis_layers` mana yang dikerjakan lebih dulu | Salah satu dari: konektivitas, aksesibilitas, kesenjangan infrastruktur, dst. | Rekomendasi: mulai dari **kesenjangan infrastruktur per kecamatan** (paling sederhana: rasio jumlah infrastruktur terhadap jumlah penduduk/wilayah), sebagai contoh yang disediakan Tahap 7 |

---

## Tahap 1 — `map_types`: Master Jenis Peta (Bagian 6.2)

### Migration 1: buat tabel `map_types`

`database/migrations/2026_09_28_090000_create_map_types_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->jsonb('konfigurasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_types');
    }
};
```

### Migration 2: tambah `categories.map_type_id` + backfill dari data live

`database/migrations/2026_09_28_090100_add_map_type_id_to_categories_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Seed 5 map_types dari nilai `type` yang benar-benar dipakai saat ini
        // (lihat tabel query live di dokumen implementasi 08, bukan asumsi).
        DB::table('map_types')->insertOrIgnore([
            ['slug' => 'tematik', 'nama' => 'Peta Tematik', 'urutan' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'usulan_musrenbang', 'nama' => 'Usulan Musrenbang', 'urutan' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'pokir_dprd', 'nama' => 'Pokok Pikiran DPRD', 'urutan' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'psd', 'nama' => 'Proyek Strategis Daerah', 'urutan' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'psn', 'nama' => 'Proyek Strategis Nasional', 'urutan' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('map_type_id')->nullable()->after('type')
                ->constrained('map_types')->nullOnDelete();
            $table->index('map_type_id');
        });

        DB::statement('
            UPDATE categories c
            SET map_type_id = mt.id
            FROM map_types mt
            WHERE c.type = mt.slug
        ');
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['map_type_id']);
            $table->dropColumn('map_type_id');
        });
    }
};
```

Backfill dilakukan di migration yang sama (bukan seeder terpisah) supaya berjalan otomatis di semua environment termasuk staging/production tanpa langkah manual tambahan — mengikuti pola `2026_09_19_075917_merge_legacy_map_types_into_tematik.php` yang sudah ada di codebase ini.

### Model baru: `app/Models/MapType.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapType extends Model
{
    protected $table = 'map_types';

    protected $fillable = [
        'slug',
        'nama',
        'deskripsi',
        'icon',
        'urutan',
        'is_active',
        'konfigurasi',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'konfigurasi' => 'array',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'map_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('urutan');
    }
}
```

### `app/Models/Category.php` — tambah relasi dan `map_type_id` ke fillable

```diff
     protected $fillable = [
         'type',
+        'map_type_id',
         'nama',
```

```diff
+    public function mapType(): BelongsTo
+    {
+        return $this->belongsTo(MapType::class, 'map_type_id');
+    }
+
     // Relasi ke data spatial
     public function dataSpatial(): HasMany
```

(Tambahkan `use Illuminate\Database\Eloquent\Relations\BelongsTo;` — sudah ada di import untuk `parent()`, tidak perlu ditambah lagi.)

### `CategoryController.php` — ganti validator hardcode

Titik yang harus diubah (baris berdasarkan audit Bagian 6.1, verifikasi ulang saat eksekusi karena nomor baris bisa bergeser):

| Baris (perkiraan) | Sebelum | Sesudah |
| --- | --- | --- |
| `:20` (`index`) | `$validTypes = ['tematik'];` | `$validTypes = MapType::active()->pluck('slug')->all();` |
| `:112-114` (`create`) | `$types = ['tematik' => 'Peta Tematik'];` | `$types = MapType::active()->pluck('nama', 'slug')->all();` |
| `:147` (`store`) | `'type' => 'required\|in:tematik',` | `'type' => 'required\|exists:map_types,slug',` |
| `:324` (`update`) | `'type' => 'required\|in:tematik',` | `'type' => 'required\|exists:map_types,slug',` |
| `:888,1003,1181` (`duplicate`/`move`/`import`) | `'type' => 'nullable\|in:tematik'` | `'type' => 'nullable\|exists:map_types,slug'` |

Tambahkan resolusi `map_type_id` di `store()`/`update()` sebelum `Category::create()`/`$category->update()`:

```php
$validated['map_type_id'] = \App\Models\MapType::where('slug', $validated['type'])->value('id');
```

`validateMaxActiveCategories()` (baris ±131, Keputusan #2) diubah menghitung berdasar `map_type_id`, bukan `type` string:

```diff
-    private function validateMaxActiveCategories($type, $excludeId = null)
+    private function validateMaxActiveCategories($mapTypeId, $excludeId = null)
     {
-        $query = Category::where('type', $type)->where('is_active', true);
+        $query = Category::where('map_type_id', $mapTypeId)->where('is_active', true);
```

### CRUD admin `map_types` (baru)

- Route (permission-gated, hanya super-admin sesuai Keputusan #1): `resource` sederhana di `routes/backend.php` — `Route::resource('map-types', MapTypeController::class)->middleware('permission:map-types.manage');`.
- Permission baru: tambahkan `'map-types.manage'` ke `Permission::CATALOG` (`app/Models/Permission.php`) dan `PermissionSeeder::DEFAULTS` (assign ke `super-admin` saja).
- Controller `app/Http/Controllers/MapTypeController.php` — CRUD standar (index/create/store/edit/update/destroy), validasi `slug` unique lowercase-snake, `destroy()` menolak jika `categories()->exists()` (jangan hapus type yang masih dipakai — konsisten dengan `RESTRICT` di level FK).
- View minimal: `resources/views/backend/pages/map-types/{index,create,edit}.blade.php`, mengikuti struktur Bootstrap admin yang sudah ada di `backend/pages/categories/*.blade.php` sebagai contoh.

### Catatan peluruhan model duplikat (tidak dieksekusi di Tahap ini)

`Lokasi`, `PokirDprd`, `ProyekStrategisDaerah`, `ProyekStrategisNasional`, `UsulanMusrenbang` (`app/Models/DataSpatial.php:243-345`) tetap dipertahankan apa adanya di dokumen ini — konsolidasi ke satu model + scope `mapType` dicatat sebagai technical debt terpisah (lihat Bagian 6.2 poin 6 di dokumen sumber), bukan bagian dari Kriteria Selesai di bawah.

---

## Tahap 2 — `categories.atribut_schema`: Metadata Layer Dinamis (Bagian 6.3)

### Migration

`database/migrations/2026_09_28_090200_add_atribut_schema_to_categories_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->jsonb('atribut_schema')->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('atribut_schema');
        });
    }
};
```

### Bentuk data `atribut_schema`

```json
{
  "version": 1,
  "fields": [
    { "key": "lebar_jalan_m", "label": "Lebar Jalan (m)", "type": "number", "required": true },
    { "key": "kondisi", "label": "Kondisi", "type": "select", "required": true,
      "options": ["baik", "sedang", "rusak_ringan", "rusak_berat"] },
    { "key": "tahun_konstruksi", "label": "Tahun Konstruksi", "type": "number", "required": false }
  ]
}
```

Tipe field yang didukung tahap ini: `string`, `number`, `date`, `select` (dengan `options`). Cukup untuk pilot kategori infrastruktur; tipe lain (mis. `multiselect`, `file`) didokumentasikan sebagai perluasan di masa depan, bukan bagian Kriteria Selesai.

### `Category.php` — tambah `atribut_schema` ke `$fillable`/`$casts`

```diff
     protected $fillable = [
         'type',
         'map_type_id',
         'nama',
         'warna',
         'icon',
         'is_marker',
         'user_id',
         'deskripsi',
+        'atribut_schema',
         'parent_id',
         'is_active',
         'gambar',
     ];

     protected $casts = [
         'is_marker' => 'boolean',
+        'atribut_schema' => 'array',
     ];
```

### Helper server-side: bangun rules Laravel dari skema

Tambahkan ke `app/Models/Category.php`:

```php
/**
 * Bangun rule Laravel Validator dari atribut_schema kategori ini, untuk divalidasi
 * terhadap payload dbf_attributes yang dikirim form data spasial.
 *
 * @return array<string, string>
 */
public function atributValidationRules(): array
{
    $rules = [];

    foreach ($this->atribut_schema['fields'] ?? [] as $field) {
        $parts = [$field['required'] ?? false ? 'required' : 'nullable'];

        $parts[] = match ($field['type'] ?? 'string') {
            'number' => 'numeric',
            'date' => 'date',
            'select' => 'in:'.implode(',', $field['options'] ?? []),
            default => 'string',
        };

        $rules['dbf_attributes.'.$field['key']] = implode('|', $parts);
    }

    return $rules;
}
```

Tidak ada `eval`/kode dinamis — murni pemetaan data ke string rule Laravel standar.

### `DataSpatialController.php` — pakai rules dinamis saat `store()`/`update()`

Tambahkan setelah rules statis di `store()`/`update()` (lokasi persis mengikuti pola `metadataWajib()` yang sudah ditambahkan sebelumnya di controller ini):

```php
$kategori = Category::find($request->input('kategori_id'));
$rules = array_merge($rules, $kategori?->atributValidationRules() ?? []);
```

`dbf_attributes` sendiri tetap divalidasi sebagai array (bukan string JSON mentah lagi — lihat perubahan form di bawah):

```php
$rules['dbf_attributes'] = 'nullable|array';
```

### `DataSpatial.php` — accessor kelengkapan atribut dinamis

Mengikuti pola `getMetadataLengkapAttribute()` yang sudah ada:

```php
public function getAtributLengkapAttribute(): bool
{
    $schema = $this->kategori?->atribut_schema['fields'] ?? [];
    $wajib = array_filter($schema, fn ($f) => $f['required'] ?? false);

    foreach ($wajib as $field) {
        if (blank($this->dbf_attributes[$field['key']] ?? null)) {
            return false;
        }
    }

    return true;
}
```

### View: ganti textarea JSON dengan field terstruktur

`resources/views/backend/pages/data_spatial/edit.blade.php:312-313` — textarea JSON mentah diganti render field per definisi `$category->atribut_schema`:

```blade
@php $fields = $category?->atribut_schema['fields'] ?? []; @endphp

@if ($fields)
    <div class="mb-3">
        <label class="form-label fw-semibold">Atribut Kategori</label>
        @foreach ($fields as $field)
            <div class="mb-2">
                <label class="form-label">{{ $field['label'] }}{{ ($field['required'] ?? false) ? ' *' : '' }}</label>
                @if ($field['type'] === 'select')
                    <select name="dbf_attributes[{{ $field['key'] }}]" class="form-select">
                        <option value="">— Pilih —</option>
                        @foreach ($field['options'] as $option)
                            <option value="{{ $option }}" @selected(($dataSpatial->dbf_attributes[$field['key']] ?? null) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="{{ $field['type'] === 'number' ? 'number' : ($field['type'] === 'date' ? 'date' : 'text') }}"
                        name="dbf_attributes[{{ $field['key'] }}]" class="form-control"
                        value="{{ $dataSpatial->dbf_attributes[$field['key']] ?? '' }}">
                @endif
            </div>
        @endforeach
    </div>
@else
    {{-- Kategori tanpa atribut_schema: pertahankan textarea JSON bebas seperti sekarang (baris 312-313 asli) --}}
@endif
```

Field lama tanpa `atribut_schema` **tidak berubah perilaku** — tetap textarea bebas, sesuai prinsip non-breaking di Bagian 6.3.

### Badge kelengkapan atribut (opsional, mengikuti pola badge metadata yang sudah ada)

`resources/views/frontend/partials/detail-peta.blade.php` — tambahkan badge kedua setelah badge metadata yang sudah ada (dari `07-perbaikan-effort-kecil.md`):

```blade
@if ($project->kategori?->atribut_schema && ! $project->atribut_lengkap)
    <div class="mt-2 inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Atribut kategori belum lengkap.
    </div>
@endif
```

---

## Tahap 3 — Reorganisasi `categories` 3 Tingkat + `is_group` (Bagian 7.2 #1)

### Migration

`database/migrations/2026_09_28_090300_add_is_group_to_categories_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_group')->default(false)->after('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_group');
        });
    }
};
```

### Seeder baru: `database/seeders/LayerGroupSeeder.php`

Membuat node kelompok/subkelompok (`is_group=true`, tanpa data spasial melekat) sesuai taksonomi Bagian 3–11 rekomendasi, **hanya untuk `map_type_id` = tematik** (pilot, sesuai arahan Bagian 6.4/7.4 mulai dari kategori infrastruktur dulu):

```php
<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MapType;
use Illuminate\Database\Seeder;

class LayerGroupSeeder extends Seeder
{
    public function run(): void
    {
        $tematik = MapType::where('slug', 'tematik')->firstOrFail();

        $taksonomi = [
            'Infrastruktur & Konektivitas' => [
                'Transportasi' => ['Jalan', 'Jembatan', 'Pelabuhan', 'Bandara', 'Terminal'],
                'Utilitas Dasar' => ['Air Minum', 'Sanitasi', 'Irigasi', 'Drainase', 'Persampahan'],
                'Energi' => ['Jaringan Listrik', 'Gardu', 'Pembangkit'],
                'Digital' => ['BTS', 'Fiber Optik', 'Titik Internet'],
            ],
            'Layanan Dasar & Fasilitas Publik' => [
                'Pendidikan' => [],
                'Kesehatan' => [],
                'Pemerintahan' => [],
                'Pasar' => [],
            ],
        ];

        foreach ($taksonomi as $kelompokNama => $subkelompok) {
            $kelompok = Category::firstOrCreate(
                ['nama' => $kelompokNama, 'map_type_id' => $tematik->id, 'parent_id' => null],
                ['type' => 'tematik', 'is_group' => true, 'is_active' => true]
            );

            foreach ($subkelompok as $subNama => $layers) {
                $sub = Category::firstOrCreate(
                    ['nama' => $subNama, 'map_type_id' => $tematik->id, 'parent_id' => $kelompok->id],
                    ['type' => 'tematik', 'is_group' => true, 'is_active' => true]
                );

                foreach ($layers as $layerNama) {
                    Category::firstOrCreate(
                        ['nama' => $layerNama, 'map_type_id' => $tematik->id, 'parent_id' => $sub->id],
                        ['type' => 'tematik', 'is_group' => false, 'is_active' => true]
                    );
                }
            }
        }
    }
}
```

Kategori yang **sudah ada** (mis. "Pendidikan", "Kesehatan" dari `KategoriLayerSeeder`) sengaja dibiarkan sebagai root terpisah — pemindahan kategori existing ke bawah node kelompok baru adalah keputusan admin lewat UI (`parent_id` sudah bisa diedit di `CategoryController::update`), bukan migrasi otomatis, supaya tidak diam-diam memindahkan data yang sedang dipakai OPD tanpa sepengetahuan mereka.

### `Category.php` — scope query yang mengecualikan node kelompok

```php
public function scopeSelectable($query)
{
    return $query->where('is_group', false);
}
```

Pakai `Category::selectable()` di titik-titik yang mengisi dropdown "pilih kategori" saat input data spasial (`DataSpatialController::create()`), supaya node kelompok/subkelompok tidak bisa dipilih sebagai kategori data langsung — hanya layer daun yang bisa.

### Sidebar peta tematik (frontend) — render berjenjang

`resources/views/frontend/pages/index.blade.php` (area filter kategori, ±baris 240-249 per audit Bagian 6.1) — pola rendering rekursif sederhana:

```blade
@php
    function renderCategoryTree($categories, $level = 0) {
        foreach ($categories as $cat) {
            echo '<div style="padding-left: '.($level * 12).'px">';
            echo $cat->is_group
                ? '<strong>'.e($cat->nama).'</strong>'
                : '<label><input type="checkbox" class="layer-toggle" value="'.$cat->id.'"> '.e($cat->nama).'</label>';
            echo '</div>';
            if ($cat->children->isNotEmpty()) {
                renderCategoryTree($cat->children, $level + 1);
            }
        }
    }
@endphp
```

Detail styling/UX (search box, collapse/expand seperti Bagian 22 rekomendasi) diserahkan ke implementasi frontend saat eksekusi — di luar cakupan contoh kode plan ini, karena bersifat kosmetik bukan struktural.

---

## Tahap 4 — Tabel `sektor` (Bagian 7.2 #2)

### Migration

`database/migrations/2026_09_28_090400_create_sektor_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sektor', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('sektor_id')->nullable()->after('map_type_id')
                ->constrained('sektor')->nullOnDelete();
        });

        Schema::table('data_spatial', function (Blueprint $table) {
            $table->foreignId('sektor_id')->nullable()->after('kategori_id')
                ->constrained('sektor')->nullOnDelete();
            $table->index('sektor_id');
        });
    }

    public function down(): void
    {
        Schema::table('data_spatial', function (Blueprint $table) {
            $table->dropForeign(['sektor_id']);
            $table->dropColumn('sektor_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['sektor_id']);
            $table->dropColumn('sektor_id');
        });

        Schema::dropIfExists('sektor');
    }
};
```

`data_spatial.sektor_id` nullable dan independen dari `categories.sektor_id` — sesuai catatan risiko di Bagian 7.3 (kamus data eksplisit): **aturan yang dikunci di sini** adalah `sektor` pada `data_spatial` DIWARISKAN dari kategorinya sebagai default (diisi otomatis saat kategori dipilih), tapi bisa ditimpa manual per data bila proyek lintas sektor. Ini dituliskan sebagai `Category::sektor_id` = default, bukan sumber tunggal kebenaran, untuk menghindari ambiguitas yang dikhawatirkan di Bagian 7.3.

### Model `app/Models/Sektor.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sektor extends Model
{
    protected $table = 'sektor';

    protected $fillable = ['slug', 'nama'];
}
```

### Seeder `database/seeders/SektorSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Sektor;
use Illuminate\Database\Seeder;

class SektorSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['pupr' => 'PUPR', 'kesehatan' => 'Kesehatan', 'pendidikan' => 'Pendidikan', 'ekonomi' => 'Ekonomi', 'lingkungan' => 'Lingkungan Hidup'] as $slug => $nama) {
            Sektor::firstOrCreate(['slug' => $slug], ['nama' => $nama]);
        }
    }
}
```

Daftar sektor awal mengikuti contoh di Bagian 18 dokumen rekomendasi (`Sektor: PUPR`) — daftar lengkap OPD/sektor sebaiknya dikonfirmasi ke Bappeda saat eksekusi, bukan diasumsikan final di sini.

---

## Tahap 5 — `administrative_regions` (Bagian 7.2 #3) — **Opsional/Dapat Ditunda**

Status berbeda dari tahap lain: tahap ini **boleh ditunda** sampai Keputusan #3 (sumber data batas wilayah) dikunci, karena tanpa data batas wilayah resmi, tabel ini hanya jadi struktur kosong yang tidak berguna.

### Migration

`database/migrations/2026_09_28_090500_create_administrative_regions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_regions', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique()->comment('Kode wilayah BPS/Kemendagri');
            $table->string('nama');
            $table->string('tingkat'); // provinsi | kabupaten_kota | kecamatan | desa_kelurahan
            $table->foreignId('parent_id')->nullable()
                ->constrained('administrative_regions')->nullOnDelete();
            $table->geometry('batas')->nullable();
            $table->timestamps();

            $table->index('tingkat');
        });

        Schema::table('data_spatial', function (Blueprint $table) {
            $table->foreignId('wilayah_id')->nullable()->after('sektor_id')
                ->constrained('administrative_regions')->nullOnDelete();
            $table->index('wilayah_id');
        });
    }

    public function down(): void
    {
        Schema::table('data_spatial', function (Blueprint $table) {
            $table->dropForeign(['wilayah_id']);
            $table->dropColumn('wilayah_id');
        });

        Schema::dropIfExists('administrative_regions');
    }
};
```

Tidak ditambahkan check constraint DB untuk `tingkat` (mengikuti konvensi `project_progress_reports.status` yang divalidasi di aplikasi, bukan DB — lihat Bagian 4 dokumen sumber) supaya menambah tingkat baru (mis. "dapil") tidak perlu migration baru.

### Command import (kerangka, bukan implementasi penuh)

`app/Console/Commands/ImportAdministrativeRegions.php` — kerangka command yang menerima path shapefile/GeoJSON batas wilayah dan melakukan `upsert` berdasarkan `kode`. **Isi parsing geometri sengaja tidak ditulis penuh di sini** karena bergantung pada format sumber data yang belum ditentukan (Keputusan #3):

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportAdministrativeRegions extends Command
{
    protected $signature = 'marimoi:import-wilayah {path : Path file GeoJSON batas wilayah} {--tingkat=kabupaten_kota}';

    protected $description = 'Import batas wilayah administratif dari GeoJSON ke administrative_regions';

    public function handle(): int
    {
        $this->error('Belum diimplementasikan — tunggu keputusan sumber data resmi (lihat Keputusan #3 di 08-kategori-peta-dinamis-dan-metadata-layer.md).');

        return self::FAILURE;
    }
}
```

Command ini sengaja di-scaffold dalam status "belum diimplementasikan" (bukan dihapus) supaya keputusan sumber data bisa menyusul tanpa perlu membuat file baru lagi.

---

## Tahap 6 — `data_spatial_interventions`: Penghubung Kondisi Eksisting ↔ Intervensi (Bagian 7.2 #4)

### Migration

`database/migrations/2026_09_28_090600_create_data_spatial_interventions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_spatial_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_spatial_id_eksisting')->constrained('data_spatial')->cascadeOnDelete();
            $table->foreignId('data_spatial_id_intervensi')->constrained('data_spatial')->cascadeOnDelete();
            $table->string('jenis_hubungan')->default('terkait'); // terkait | peningkatan | rehabilitasi | pemeliharaan
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['data_spatial_id_eksisting', 'data_spatial_id_intervensi'], 'data_spatial_interventions_unique_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_spatial_interventions');
    }
};
```

`cascadeOnDelete` pada kedua sisi (bukan `RESTRICT`) karena tabel ini murni anotasi hubungan — kehilangan baris `data_spatial` sudah punya arti sendiri (dihapus), tautannya boleh ikut hilang tanpa memblokir penghapusan.

### Model `app/Models/DataSpatialIntervention.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSpatialIntervention extends Model
{
    protected $table = 'data_spatial_interventions';

    protected $fillable = [
        'data_spatial_id_eksisting',
        'data_spatial_id_intervensi',
        'jenis_hubungan',
        'dibuat_oleh',
    ];

    public function eksisting(): BelongsTo
    {
        return $this->belongsTo(DataSpatial::class, 'data_spatial_id_eksisting');
    }

    public function intervensi(): BelongsTo
    {
        return $this->belongsTo(DataSpatial::class, 'data_spatial_id_intervensi');
    }
}
```

### `DataSpatial.php` — relasi kedua arah

```php
public function intervensiTerkait(): HasMany
{
    return $this->hasMany(DataSpatialIntervention::class, 'data_spatial_id_eksisting');
}

public function kondisiEksistingTerkait(): HasMany
{
    return $this->hasMany(DataSpatialIntervention::class, 'data_spatial_id_intervensi');
}
```

### UI penautan manual (kerangka)

Tambahkan tombol "Tautkan ke Proyek/Usulan" di halaman detail admin `data_spatial` (`backend/pages/data_spatial/_detail_modal.blade.php`), memicu modal pencarian sederhana (dropdown/autocomplete `data_spatial` lain, difilter `data_type != 'tematik'` untuk sisi intervensi) yang mengirim ke endpoint baru `POST /data-spatial/{id}/intervensi` → `DataSpatialInterventionController::store()`. Detail UI (autocomplete library, styling) diserahkan ke eksekusi karena bersifat kosmetik.

---

## Tahap 7 — `analysis_layers` + `analysis_results` + Job Terjadwal (Bagian 7.2 #5)

### Migration

`database/migrations/2026_09_28_090700_create_analysis_layers_and_results_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_layers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nama');
            $table->text('metodologi')->nullable();
            $table->string('tingkat_wilayah_default')->default('kecamatan');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_layer_id')->constrained('analysis_layers')->cascadeOnDelete();
            $table->foreignId('wilayah_id')->constrained('administrative_regions')->restrictOnDelete();
            $table->smallInteger('tahun');
            $table->decimal('nilai', 12, 4)->nullable();
            $table->string('kategori_hasil')->nullable(); // mis. "Tinggi"/"Sedang"/"Rendah"
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['analysis_layer_id', 'wilayah_id', 'tahun'], 'analysis_results_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_results');
        Schema::dropIfExists('analysis_layers');
    }
};
```

`wilayah_id` adalah `RESTRICT` (konsisten dengan pola `project_progress_reports.data_spatial_id`) — wilayah administratif tidak boleh dihapus selama masih ada hasil analisis yang mengacu padanya. **Prasyarat: Tahap 5 (`administrative_regions`) harus sudah berjalan sebelum Tahap 7 di-migrate**, karena FK ini bergantung padanya.

### Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisLayer extends Model
{
    protected $table = 'analysis_layers';

    protected $fillable = ['slug', 'nama', 'metodologi', 'tingkat_wilayah_default', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function results(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }
}

class AnalysisResult extends Model
{
    protected $table = 'analysis_results';

    protected $fillable = ['analysis_layer_id', 'wilayah_id', 'tahun', 'nilai', 'kategori_hasil', 'computed_at'];

    protected $casts = ['computed_at' => 'datetime'];

    public function layer(): BelongsTo
    {
        return $this->belongsTo(AnalysisLayer::class, 'analysis_layer_id');
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(AdministrativeRegion::class, 'wilayah_id');
    }
}
```

### Contoh perhitungan pertama: "Kesenjangan Infrastruktur" (Keputusan #5)

`app/Console/Commands/HitungKesenjanganInfrastruktur.php` — contoh rumus paling sederhana (jumlah infrastruktur tematik per wilayah, dikategorikan tinggi/sedang/rendah berdasar tercile), **sebagai starting point, bukan rumus final** (rumus final tetap keputusan Bappeda):

```php
<?php

namespace App\Console\Commands;

use App\Models\AnalysisLayer;
use App\Models\AnalysisResult;
use App\Models\DataSpatial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HitungKesenjanganInfrastruktur extends Command
{
    protected $signature = 'marimoi:hitung-kesenjangan-infrastruktur {--tahun=}';

    protected $description = 'Hitung ulang layer analisis kesenjangan infrastruktur per wilayah';

    public function handle(): int
    {
        $tahun = $this->option('tahun') ?? now()->year;

        $layer = AnalysisLayer::firstOrCreate(
            ['slug' => 'kesenjangan-infrastruktur'],
            ['nama' => 'Kesenjangan Infrastruktur', 'tingkat_wilayah_default' => 'kecamatan']
        );

        $jumlahPerWilayah = DataSpatial::query()
            ->where('data_type', 'tematik')
            ->whereNotNull('wilayah_id')
            ->select('wilayah_id', DB::raw('count(*) as jumlah'))
            ->groupBy('wilayah_id')
            ->get();

        if ($jumlahPerWilayah->isEmpty()) {
            $this->warn('Tidak ada data_spatial dengan wilayah_id terisi — jalankan setelah Tahap 5 dan pengisian wilayah_id.');

            return self::FAILURE;
        }

        $nilai = $jumlahPerWilayah->pluck('jumlah')->sort()->values();
        $tercile1 = $nilai->get((int) ($nilai->count() * 0.33));
        $tercile2 = $nilai->get((int) ($nilai->count() * 0.66));

        foreach ($jumlahPerWilayah as $row) {
            $kategori = match (true) {
                $row->jumlah <= $tercile1 => 'Tinggi', // makin sedikit infrastruktur, makin tinggi kesenjangannya
                $row->jumlah <= $tercile2 => 'Sedang',
                default => 'Rendah',
            };

            AnalysisResult::updateOrCreate(
                ['analysis_layer_id' => $layer->id, 'wilayah_id' => $row->wilayah_id, 'tahun' => $tahun],
                ['nilai' => $row->jumlah, 'kategori_hasil' => $kategori, 'computed_at' => now()]
            );
        }

        $this->info("Selesai — {$jumlahPerWilayah->count()} wilayah dihitung untuk tahun {$tahun}.");

        return self::SUCCESS;
    }
}
```

### Penjadwalan (Keputusan #4: mingguan)

`bootstrap/app.php` — tambahkan `->withSchedule()` (belum ada scheduler terdaftar di aplikasi ini sebelumnya, `routes/console.php` baru berisi command `inspire` bawaan):

```php
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(/* ... */)
    ->withMiddleware(/* ... */)
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('marimoi:hitung-kesenjangan-infrastruktur')->weekly();
    })
    ->withExceptions(/* ... */)
    ->create();
```

---

## Testing

Ikuti konvensi `phpunit/core rules`: test per Tahap, jalankan `php artisan test --compact --filter=...` setelah tiap tahap sebelum lanjut ke tahap berikutnya (bukan menumpuk semua perubahan lalu test sekali di akhir).

| Tahap | File test | Skenario minimal |
| --- | --- | --- |
| 1 | `tests/Feature/MapTypeTest.php` (baru) | admin bisa CRUD `map_types`; `CategoryController::store` menerima `type` baru yang ada di `map_types` dan menolak yang tidak ada; cap 10 kategori aktif dihitung per `map_type_id` |
| 2 | `tests/Feature/DataSpatial/AtributSchemaTest.php` (baru) | kategori dengan `atribut_schema` mewajibkan field `required`; kategori tanpa `atribut_schema` tetap freeform seperti sebelumnya (regresi terhadap `MetadataDatasetTest` yang sudah ada) |
| 3 | `tests/Feature/CategoryHierarchyTest.php` (baru) | `LayerGroupSeeder` membentuk 3 tingkat yang benar; `Category::selectable()` mengecualikan node `is_group=true`; kategori existing tidak ikut pindah tanpa aksi eksplisit |
| 4 | `tests/Feature/SektorTest.php` (baru) | `data_spatial.sektor_id` terisi otomatis dari kategori saat kosong; bisa ditimpa manual |
| 5 | `tests/Feature/AdministrativeRegionTest.php` (baru, hanya jika Tahap 5 dieksekusi) | hierarki `parent_id` wilayah benar; FK `wilayah_id` di `data_spatial` `SET NULL` saat wilayah dihapus |
| 6 | `tests/Feature/DataSpatialInterventionTest.php` (baru) | pasangan `(eksisting, intervensi)` unik; kedua relasi (`intervensiTerkait`/`kondisiEksistingTerkait`) mengembalikan data yang benar |
| 7 | `tests/Feature/AnalysisResultTest.php` (baru) | command `marimoi:hitung-kesenjangan-infrastruktur` menghasilkan kategori tercile yang benar untuk data uji; `updateOrCreate` tidak menduplikasi baris saat dijalankan ulang untuk tahun yang sama |

Regresi wajib dijalankan ulang setelah Tahap 1–2 (karena menyentuh `CategoryController`/`DataSpatialController` yang sudah punya banyak test existing): `RolePermissionTest`, `MetadataDatasetTest`, `FrontendPagesTest`, `FrontendGeojsonMetadataTest`, `SharedMapFilterTest`, `FrontendFilterOptionsTest`.

## Urutan Eksekusi

Ikuti urutan Tahap 1 → 7 seperti ditulis (bukan diacak) — Tahap 3 bergantung pada `map_type_id` dari Tahap 1, Tahap 7 bergantung pada `wilayah_id` dari Tahap 5, dan Tahap 4 (`sektor`) independen sehingga bisa disisipkan kapan saja setelah Tahap 1. Setelah setiap Tahap: jalankan migration → jalankan test tahap terkait → `vendor/bin/pint --dirty --format agent` → baru lanjut Tahap berikutnya. Jangan menjalankan seluruh 7 migration sekaligus tanpa test di antaranya — risiko men-debug 7 perubahan sekaligus jika ada yang gagal.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Data `categories`/`data_spatial` legacy (psd/psn/pokir_dprd/usulan_musrenbang) yang masih hidup (lihat tabel query live di atas) ikut ter-backfill ke `map_types` yang salah | Migration Tahap 1 memakai `slug` yang identik dengan nilai `type` asli — tidak ada transformasi/tebakan, jadi backfill 1:1 apa adanya |
| `LayerGroupSeeder` (Tahap 3) berjalan berulang dan membuat duplikat kelompok | Pakai `firstOrCreate` dengan kombinasi `(nama, map_type_id, parent_id)` sebagai kunci, idempoten |
| `analysis_results` (Tahap 7) dijalankan sebelum `wilayah_id` terisi di `data_spatial` | Command mendeteksi kondisi ini secara eksplisit (`$jumlahPerWilayah->isEmpty()`) dan gagal dengan pesan jelas, bukan menyimpan hasil kosong/menyesatkan |
| Perubahan `CategoryController` validator (Tahap 1) berdampak ke integrasi lain yang mengirim `type=tematik` secara hardcode (mis. import script eksternal) | `exists:map_types,slug` tetap menerima `tematik` karena nilai itu ada di `map_types` — tidak ada breaking change untuk pemanggil existing yang sudah mengirim `tematik` |
| Tahap 5 (`administrative_regions`) dikerjakan tanpa sumber data resmi, menghasilkan tabel kosong yang menyesatkan | Tahap ini ditandai eksplisit **opsional/dapat ditunda** — jangan dipaksakan sebelum Keputusan #3 dikunci |

## Kriteria Selesai

- [x] Tahap 1: `map_types` dibuat, ter-seed dari data live, `categories.map_type_id` ter-backfill 100%, validator `CategoryController` memakai `exists:map_types,slug`, CRUD admin `map_types` berfungsi dan digated permission `map-types.manage`. Diverifikasi: `MapTypeTest` (4 test).
- [x] Tahap 2: `categories.atribut_schema` dibuat, helper `atributValidationRules()` + accessor `atribut_lengkap` dibuat, validasi diterapkan di `DataSpatialController::update()`, kategori tanpa skema tidak berubah perilaku. Diverifikasi: `AtributSchemaTest` (3 test).
- [x] Tahap 3: `categories.is_group` dibuat, `LayerGroupSeeder` dibuat (tidak dijalankan terhadap data nyata, lihat Catatan audit), `Category::selectable()` dipakai di `DataSpatialController::create()`/`edit()`. Diverifikasi: `CategoryHierarchyTest` (3 test, `RefreshDatabase` terisolasi).
- [x] Tahap 4: `sektor` dibuat dan ter-seed (`SektorSeeder`, terdaftar di `DatabaseSeeder`), `categories.sektor_id`/`data_spatial.sektor_id` tersedia, pewarisan default sektor dari kategori berfungsi dan bisa ditimpa manual. Diverifikasi: `SektorTest` (2 test).
- [ ] Tahap 5 — **ditunda** (Keputusan eksekusi #3): sumber data batas wilayah administratif belum ditentukan.
- [x] Tahap 6: `data_spatial_interventions` dibuat, `DataSpatialInterventionController` (store/destroy) + route ditambahkan. Diverifikasi: `DataSpatialInterventionTest` (4 test) — belum ada UI tautan di `_detail_modal.blade.php` (kosmetik, endpoint sudah fungsional).
- [ ] Tahap 7 — **ditunda** (Keputusan eksekusi #4 dan #5): jadwal job dan rumus analisis pertama belum ditentukan; `analysis_layers`/`analysis_results` tidak dibuat di iterasi ini.
- [x] Seluruh test baru per Tahap lulus (16 test, 44 assertion), seluruh test regresi tetap lulus (181 total lulus; 8 kegagalan tersisa pra-existing & tidak terkait — lihat Catatan audit).
- [x] `vendor/bin/pint --dirty --format agent` bersih.
- [x] Migration Tahap 1/2/3/4/6 diterapkan ke database dev `marimoi_v2` — drift migration lama pra-existing diperbaiki lebih dulu (lihat Catatan audit), backfill `categories.map_type_id` terverifikasi 212/212, `PermissionSeeder`/`SektorSeeder` sudah dijalankan.
- [ ] Verifikasi manual di browser (CRUD jenis peta, form atribut dinamis, penautan intervensi) — belum dilakukan, tidak bisa dilakukan dari sesi ini. Query controller inti sudah diverifikasi langsung terhadap data live lewat tinker tanpa error.
