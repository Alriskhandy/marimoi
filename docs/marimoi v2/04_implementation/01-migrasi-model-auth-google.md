# Plan Implementasi: Migrasi, Model, dan Login Google untuk Authentication

## Status Implementasi

- **Tahap 1 (Migration)** — selesai.
- **Tahap 2 (Model & Relasi)** — selesai.
- **Tahap 3 (Seeder)** — selesai.
- **Tahap 4 (Login Google)** — selesai, dengan penyesuaian dan catatan audit di bawah.
- Tahap 5–9 belum dikerjakan.

### Catatan audit: kode OAuth Google manual yang sudah ada (selesai dibersihkan)

Sebelum Tahap 4 dikerjakan, ditemukan implementasi Google login versi lain di working tree (bukan dari plan ini — tampaknya sedang dikerjakan manual secara paralel, kemungkinan langsung di editor, di luar sesi ini): route `auth.google.redirect`/`auth.google.callback` di `routes/auth.php` yang menunjuk ke `AuthenticatedSessionController@google_redirect`/`@google_callback`, dan `config/services.php['google']['redirect']` hardcode ke `config('app.url').'/auth/google/callback'`.

Belakangan, method `google_redirect()`/`google_callback()` di `AuthenticatedSessionController` hilang dari file (di luar edit sesi ini — kemungkinan tertimpa proses lain), sehingga dua route manual di atas sempat mereferensikan method yang tidak ada (`BadMethodCallException` bila diakses).

**Atas persetujuan eksplisit, dibersihkan sepenuhnya:**

- Dua route `auth.google.redirect`/`auth.google.callback` dihapus dari `routes/auth.php` — tidak ada lagi referensi ke `AuthenticatedSessionController@google_redirect`/`@google_callback` di mana pun (sudah diverifikasi dengan grep sebelum dihapus).
- `config/services.php['google']['redirect']` diarahkan ke `/login/google/callback` (path relatif; Socialite otomatis meresolusinya jadi URL penuh sesuai `APP_URL`), menggantikan hardcode `config('app.url').'/auth/google/callback'` yang lama.
- `GoogleAuthController` disederhanakan: tidak lagi memanggil `->redirectUrl(route('login.google.callback'))` secara manual di setiap request karena `config('services.google.redirect')` sekarang sudah benar dan menjadi satu-satunya sumber kebenaran untuk redirect URI Google.
- Route `register`/`GET /register` tetap terhapus dari `routes/auth.php` (bagian dari perubahan paralel yang sama, konsisten dengan aturan bisnis "publik daftar/login mandiri melalui Google") — `RegistrationTest` bawaan Breeze karena itu tetap gagal (404); belum dikonfirmasi apakah ini disengaja permanen.

Sekarang hanya ada **satu** jalur Google OAuth yang hidup: `/login/google` + `/login/google/callback` (Socialite, lengkap, sudah diuji lulus).

### Penyesuaian lain selama implementasi

- `Role::$fillable` sebelumnya di-comment total (hanya `$casts` yang aktif), sehingga `Role::create()` — termasuk yang dipakai `RoleSeeder` — selalu melempar `MassAssignmentException`. Diaktifkan kembali (`name`, `slug`, `description`, `is_active`) karena secara langsung memblokir Tahap 3 dan Tahap 4.
- `User::$fillable` ditambah `is_active` dan `email_verified_at` (sebelumnya hanya `name`, `email`, `password`, `role_id`, `opd_id`) agar `HandleGoogleLogin` bisa membuat user publik baru dengan status aktif dan email terverifikasi lewat `create()` biasa, bukan `forceFill()`.
- `RoleFactory` dibuat baru (belum pernah ada) karena `Role` memakai trait `HasFactory` tapi tidak ada factory class-nya — dibutuhkan oleh `UserRoleAssignmentFactory` dan test.
- `.env.testing` menunjuk ke role Postgres `postgres` yang tidak ada di lingkungan ini, dan database `test_marimoi_db` belum pernah dibuat — test suite tidak pernah bisa jalan sama sekali sebelum ini. Diperbaiki agar memakai role `marimoi_user` yang sama dengan `.env`, database `test_marimoi_db` dibuat terpisah dari database dev, dan extension PostGIS diaktifkan di database tersebut.

## Tujuan

Menerjemahkan bagian **Authentication** pada [db-schema-v2.md](../db-schema-v2.md#authentication) menjadi migration dan model Laravel yang additive terhadap schema yang sudah berjalan, sekaligus mengimplementasikan login publik dengan akun Google sesuai [overview.md](../overview.md) dan [02-auth-user-role.md](../03_plan/02-auth-user-role.md).

## Referensi

- [db-schema-v2.md](../db-schema-v2.md) — bagian `Authentication` (`users`, `roles`, `opd`, `user_identities`, `user_role_assignments`, `authentication_logs`).
- [overview.md](../overview.md) — peran pengguna dan aturan bisnis utama.
- [02-auth-user-role.md](../03_plan/02-auth-user-role.md) — alur login dan kriteria selesai.
- [08-migrasi-pengujian.md](../03_plan/08-migrasi-pengujian.md) — strategi migrasi dan testing minimum.

## Cakupan

Termasuk: migration `users`, `roles`, `opd`, `user_identities`, `user_role_assignments`, `authentication_logs`; model dan relasinya; login Google untuk publik; pencatatan authentication log untuk login admin dan publik.

Tidak termasuk: Policy per-resource, dashboard eksekutif, WebGIS, dan tabel domain lain di luar Authentication — dibahas pada dokumen plan lain.

## Kondisi Existing (Audit Singkat)

Audit terhadap kode saat ini menemukan beberapa kesenjangan terhadap desain schema V2 yang perlu diperhitungkan sebelum migration:

| Area | Temuan |
| --- | --- |
| `users` | `password` masih `NOT NULL`; belum ada `is_active`, `last_login_at`, `disabled_at`. |
| `roles` | Belum ada kolom `is_active` walau `Role` model sudah meng-cast-nya sebagai boolean. |
| `opd` | Belum ada kolom `is_active`. |
| Slug role | Data dan kode memakai slug `super-admin`, bukan `admin-sistem` seperti pada `overview.md`. Ditemukan **≥15 file** (`UserController`, `DashboardController`, `AspirasiController`, `DataSpatialController`, `CategoryController`, `ProjectFeedbackController`, `RoleSeeder`, dll.) yang hardcode string `super-admin`. |
| Login | Hanya login email/password (Laravel Breeze, `AuthenticatedSessionController`). Tidak ada `laravel/socialite` di `composer.json`. Tidak ada `config/services.php['google']`. |
| Authorization | `RoleMiddleware` (alias `role`) sudah memfilter berdasarkan `$user->role->slug`, cukup untuk membedakan role `publik` dari role admin tanpa perubahan. |
| Audit | Belum ada tabel log login (`authentication_logs`) maupun histori perubahan role (`user_role_assignments`). |

## Keputusan yang Perlu Dikunci Sebelum Implementasi

1. **Slug role `admin-sistem` vs `super-admin`.**
   Rekomendasi: **pertahankan slug `super-admin`** sebagai slug canonical, cukup perbarui `name`/label tampilan jadi "Admin Sistem" bila diperlukan secara kosmetik. Alasan: mengganti slug butuh migration data plus edit ≥15 file otorisasi berbasis string — biaya dan risiko regresi tidak sebanding untuk pekerjaan yang murni penamaan. Dokumen ini selanjutnya memakai `super-admin` sebagai representasi "Admin Sistem", `admin-bappeda`, `admin-opd`, dan menambah `publik` sebagai role baru. Jika pemilik produk tetap ingin slug `admin-sistem`, tambahkan sebagai task rename terpisah di luar dokumen ini karena menyentuh banyak file di luar domain Authentication.
2. **Dependency baru `laravel/socialite`.**
   CLAUDE.md project ini melarang mengubah dependency tanpa persetujuan. Implementasi login Google **wajib** menambah `laravel/socialite` — minta konfirmasi eksplisit sebelum menjalankan `composer require laravel/socialite`.
3. **`user_role_assignments` dibangun sekarang, bukan ditunda.**
   Schema menandainya "New, optional", tetapi kriteria selesai di `02-auth-user-role.md` mensyaratkan "semua role change memiliki actor, waktu, nilai lama, dan nilai baru" — sehingga tabel ini dibuat pada tahap ini agar `UserController` bisa langsung mencatat histori saat Admin Sistem mengubah role/OPD user.
4. **Akun admin yang sudah ada tanpa Google identity.**
   Saat admin yang diprovisioning manual (email sudah ada di `users`) login lewat Google untuk pertama kali, sistem **menautkan** (`account linking`) `user_identities` baru ke `users` yang emailnya cocok — bukan membuat user baru dengan role `publik`. Ini mengimplementasikan poin 4 pada alur login di `02-auth-user-role.md`.

## Tahap 1 — Migration Database

Migration dibuat additive, tanpa menghapus kolom/tabel lama, sesuai prinsip arsitektur pada `overview.md`.

### 1.1 `..._add_auth_columns_to_roles_table.php`

```php
Schema::table('roles', function (Blueprint $table) {
    $table->boolean('is_active')->default(true)->after('description');
});
```

### 1.2 `..._add_is_active_to_opd_table.php`

```php
Schema::table('opd', function (Blueprint $table) {
    $table->boolean('is_active')->default(true)->after('email');
});
```

### 1.3 `..._add_auth_columns_to_users_table.php`

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('password')->nullable()->change();
    $table->boolean('is_active')->default(true)->after('remember_token');
    $table->timestamp('last_login_at')->nullable()->after('is_active');
    $table->timestamp('disabled_at')->nullable()->after('last_login_at');
});
```

Catatan: `password` diubah `nullable` karena user Google tidak memiliki password lokal. Laravel 12 tidak lagi memerlukan `doctrine/dbal` untuk `change()` (grammar native per database), dan karena satu-satunya atribut yang pernah didefinisikan pada kolom `password` hanyalah tipe `string`, tidak ada modifier lama yang berisiko hilang.

### 1.4 `..._create_user_identities_table.php`

```php
Schema::create('user_identities', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('provider', 50);
    $table->string('provider_subject');
    $table->string('provider_email')->nullable();
    $table->jsonb('provider_data')->nullable();
    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();

    $table->unique(['provider', 'provider_subject']);
    $table->index('user_id');
});
```

### 1.5 `..._create_user_role_assignments_table.php`

```php
Schema::create('user_role_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('role_id')->constrained()->restrictOnDelete();
    $table->foreignId('opd_id')->nullable()->constrained('opd')->nullOnDelete();
    $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('started_at');
    $table->timestamp('ended_at')->nullable();
    $table->text('reason')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'started_at']);
});
```

### 1.6 `..._create_authentication_logs_table.php`

```php
Schema::create('authentication_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('provider', 50);
    $table->string('event', 50); // login, logout, callback, failed, blocked, session_expired
    $table->boolean('success');
    $table->string('failure_reason')->nullable();
    $table->string('ip_hash', 128)->nullable();
    $table->text('user_agent')->nullable();
    $table->string('session_id_hash', 128)->nullable();
    $table->string('request_id', 100)->nullable();
    $table->timestamp('occurred_at');
    $table->jsonb('metadata')->nullable();

    $table->index(['user_id', 'occurred_at']);
    $table->index(['event', 'occurred_at']);
});
```

`ip_hash`/`session_id_hash` diisi dengan `hash('sha256', $value . config('app.key'))` agar tidak menyimpan IP/session id mentah, sesuai catatan privasi di `db-schema-v2.md`.

## Tahap 2 — Model dan Relasi

- **`User`** (`app/Models/User.php`): tambah `is_active`, `last_login_at`, `disabled_at` ke `casts()`; tambah relasi `identities()` (`hasMany UserIdentity`), `roleAssignments()` (`hasMany UserRoleAssignment`), `authenticationLogs()` (`hasMany AuthenticationLog`); tambah `scopeActive()`; tambah `isPublik()` memakai `hasRole('publik')`; pertahankan `isSuperAdmin()/isAdminBappeda()/isAdminOpd()/isAdmin()` yang sudah ada tanpa mengubah slug.
- **`UserIdentity`** (baru, `app/Models/UserIdentity.php`): `fillable = ['user_id','provider','provider_subject','provider_email','provider_data','last_used_at']`, cast `provider_data` → `array`, cast `last_used_at` → `datetime`; `belongsTo(User::class)`.
- **`UserRoleAssignment`** (baru): `fillable = ['user_id','role_id','opd_id','assigned_by','started_at','ended_at','reason']`, cast `started_at`/`ended_at` → `datetime`; relasi `user()`, `role()`, `opd()`, `assignedBy()` (`belongsTo(User::class, 'assigned_by')`).
- **`AuthenticationLog`** (baru): `fillable = ['user_id','provider','event','success','failure_reason','ip_hash','user_agent','session_id_hash','request_id','occurred_at','metadata']`, cast `success` → `boolean`, `metadata` → `array`, `occurred_at` → `datetime`; `belongsTo(User::class)` nullable.
- **`Role`**: tambah `scopeActive()` (sudah ada), pastikan `is_active` benar-benar ter-cast setelah kolom tersedia; tidak perlu perubahan struktural lain.
- **`Opd`**: tambah `is_active` ke `$casts`.

Gunakan `php artisan make:model UserIdentity -mf --no-interaction`, dst., lalu satukan migration hasil generate dengan definisi pada Tahap 1 supaya penomoran timestamp tetap berurutan dan sesuai konvensi command Artisan proyek ini.

## Tahap 3 — Seeder

- `database/seeders/RoleSeeder.php`: tambah entri role `publik` (`name: 'Publik'`, `slug: 'publik'`, `description: 'Pengguna publik hasil login Google'`). Role admin yang sudah ada tidak diubah slug-nya (lihat Keputusan #1).
- Tambah `database/factories/UserIdentityFactory.php`, `UserRoleAssignmentFactory.php`, `AuthenticationLogFactory.php` untuk kebutuhan test (bukan seeder produksi).

## Tahap 4 — Login Publik dengan Google (Socialite)

### 4.1 Instalasi dan konfigurasi

- `composer require laravel/socialite` (perlu persetujuan — lihat Keputusan #2). **Sudah terpasang.**
- `config/services.php`, key `google`:
  ```php
  'google' => [
      'client_id' => env('GOOGLE_CLIENT_ID'),
      'client_secret' => env('GOOGLE_CLIENT_SECRET'),
      'redirect' => '/login/google/callback',
  ],
  ```
  `redirect` memakai path relatif (bukan `env('GOOGLE_REDIRECT_URI')` terpisah) — Socialite otomatis meresolusinya jadi URL penuh berdasarkan `APP_URL`, sehingga tidak perlu env var tambahan dan tetap konsisten di semua environment (dev/staging/production) selama route `login.google.callback` tidak berpindah path.
- `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` sudah ada di `.env` sebelumnya (dipakai jalur OAuth manual yang kini dihapus); ditambahkan juga ke `.env.example` sebagai dokumentasi.

### 4.2 Route (`routes/auth.php`, di dalam grup `middleware('guest')`)

```php
Route::get('login/google', [GoogleAuthController::class, 'redirect'])->name('login.google');
Route::get('login/google/callback', [GoogleAuthController::class, 'callback'])->name('login.google.callback');
```

Gunakan flow **default** Socialite (bukan `stateless()`), karena `02-auth-user-role.md` mensyaratkan callback "diverifikasi melalui state/CSRF".

### 4.3 Controller — `app/Http/Controllers/Auth/GoogleAuthController.php`

Dua method tipis: `redirect()` memanggil `Socialite::driver('google')->redirect()`, `callback()` menangkap `\Laravel\Socialite\Two\InvalidStateException` (mencatat `authentication_logs` event `failed`, redirect ke `login` dengan flash error) lalu mendelegasikan hasil sukses ke Action `HandleGoogleLogin`.

### 4.4 Action — `app/Actions/Auth/HandleGoogleLogin.php`

Alur (mengikuti `02-auth-user-role.md`):

1. Cari `UserIdentity::where('provider', 'google')->where('provider_subject', $googleUser->getId())->first()`.
2. **Jika identity ditemukan** → ambil `user`-nya, perbarui `provider_email`, `provider_data`, `last_used_at`.
3. **Jika tidak ditemukan** → cari `User::where('email', $googleUser->getEmail())->first()`:
   - **Ada** (akun admin yang sudah diprovisioning Admin Sistem, atau publik lama) → buat `UserIdentity` baru yang menaut ke user tersebut (account linking). Role/OPD user **tidak diubah** oleh proses ini.
   - **Tidak ada** → buat `User` baru: `role_id` = id role `publik` (dicari dari master `roles` by slug, bukan dari input manapun), `is_active = true`, `email_verified_at = now()` (Google sudah memverifikasi email), `password = null`. Lalu buat `UserIdentity` yang menaut ke user baru ini.
4. Tolak login jika `user->is_active === false` → catat `authentication_logs` event `blocked`, jangan panggil `Auth::login()`.
5. `Auth::login($user, remember: true)`, `$request->session()->regenerate()`.
6. Set `last_login_at = now()` pada `user`.
7. Catat `authentication_logs` (`provider: google`, `event: callback` lalu `login`, `success: true`, `ip_hash`, `user_agent`, `session_id_hash`).
8. Redirect: role `publik` → halaman publik yang dituju (`intended()` fallback ke beranda); role admin → `route('dashboard')` (mengikuti perilaku Breeze yang sudah ada).

Menaruh logika ini di Action class (bukan langsung di controller) membuatnya dapat diuji lewat unit test tanpa HTTP roundtrip, dan dapat dipanggil ulang bila nanti ada provider lain.

### 4.5 Edge case yang wajib ditangani

- **Akun dinonaktifkan** (`is_active = false`) mencoba login Google → ditolak, dicatat sebagai `blocked`.
- **`InvalidStateException`** (CSRF/state Google gagal, biasanya karena reload/callback kadaluarsa) → redirect ke login dengan pesan, dicatat sebagai `failed`.
- **Provider mengembalikan email null** (bisa terjadi bila scope `email` tidak diberikan) → tolak login dengan pesan eksplisit, jangan membuat user tanpa email.
- **Duplicate identity race condition** (dua request callback bersamaan untuk `provider_subject` yang sama) → constraint unique `(provider, provider_subject)` pada `user_identities` mencegah duplikasi; tangkap `QueryException` dan retry lookup sekali sebelum menampilkan error.
- **Role publik tidak boleh naik privilege**: `role_id` untuk user baru **selalu** diisi dari lookup `Role::where('slug', 'publik')`, tidak pernah dari request/query string, sesuai aturan bisnis di `overview.md`.

## Tahap 5 — Pencatatan Authentication Log untuk Login Admin

Business rule di `overview.md` mensyaratkan **semua** login admin (bukan hanya login Google) dicatat. Sentuh `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (Breeze) yang sudah ada:

- Setelah `Auth::attempt()` sukses: set `last_login_at`, catat `authentication_logs` (`provider: local`, `event: login`, `success: true`).
- Saat `ValidationException` dilempar oleh `LoginRequest::authenticate()` (gagal login) → tangkap di controller atau tambahkan hook di `LoginRequest` untuk mencatat `event: failed` dengan `user_id` null jika email tidak ditemukan.
- Pada `destroy()` (logout) → catat `event: logout`.

Ekstrak logika pencatatan ke `app/Services/Auth/AuthenticationLogger.php` (satu method `log(User|null $user, string $provider, string $event, bool $success, array $context = [])`) supaya dipakai bersama oleh `AuthenticatedSessionController` dan `HandleGoogleLogin` — menghindari duplikasi hashing IP/session id.

## Tahap 6 — Middleware Status Akun Aktif

`is_active = false` bisa terjadi **setelah** user memiliki session aktif (dinonaktifkan Admin Sistem saat user lain sedang login). Tambah `app/Http/Middleware/EnsureAccountIsActive.php`:

```php
public function handle(Request $request, Closure $next): Response
{
    if (Auth::check() && ! Auth::user()->is_active) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Akun Anda telah dinonaktifkan.',
        ]);
    }

    return $next($request);
}
```

Daftarkan sebagai bagian dari `$middleware->web(append: [...])` di `bootstrap/app.php`, setelah `TrackVisitor::class`.

## Tahap 7 — Update UI

Tambah tombol "Masuk dengan Google" (`route('login.google')`) pada `resources/views/auth/login.blade.php`. Jika tombol tidak tampil setelah perubahan, tanyakan ke user apakah perlu `npm run build`/`npm run dev` sesuai aturan Frontend Bundling di CLAUDE.md — perubahan ini murni Blade sehingga umumnya tidak butuh build ulang asset, kecuali menambah ikon/CSS baru dari pipeline Vite.

## Tahap 8 — Audit Role Assignment

`app/Http/Controllers/UserController.php` (method `store`/`update` pengelolaan user oleh Admin Sistem) ditambah: setiap kali `role_id` atau `opd_id` berubah, tutup assignment lama (`ended_at = now()`) dan buat baris baru `UserRoleAssignment` (`assigned_by = Auth::id()`, `started_at = now()`, `reason` dari input form bila ada). Ini memenuhi kriteria selesai "semua role change memiliki actor, waktu, nilai lama, dan nilai baru".

## Tahap 9 — Testing (PHPUnit)

Sesuai aturan proyek, seluruh test berbasis PHPUnit (bukan Pest), dibuat dengan `php artisan make:test --phpunit`.

| Test | Skenario |
| --- | --- |
| `tests/Feature/Auth/GoogleAuthenticationTest.php` | `Socialite::fake('google', ...)`: (1) user baru → role `publik` ter-assign otomatis; (2) email cocok dengan admin existing → identity ditaut, role admin tidak berubah; (3) `is_active = false` → login ditolak; (4) `InvalidStateException` → dicatat `failed`, redirect ke login; (5) request kedua dengan `provider_subject` sama → login ke user yang sama, tidak membuat user duplikat. |
| `tests/Feature/Auth/AuthenticationLogTest.php` | Login admin sukses/gagal dan logout masing-masing menghasilkan satu baris `authentication_logs` dengan `event` yang sesuai. |
| `tests/Feature/Auth/AccountDisabledTest.php` | User dengan session aktif yang di-set `is_active = false` di tengah request berikutnya otomatis ter-logout oleh `EnsureAccountIsActive`. |
| `tests/Feature/User/RoleAssignmentAuditTest.php` | Admin Sistem mengubah role/OPD user lain → `user_role_assignments` bertambah satu baris dengan `assigned_by` yang benar dan assignment lama tertutup `ended_at`. |

Jalankan test dengan filter spesifik dulu (`php artisan test --compact --filter=Google`, `--filter=AuthenticationLog`, dst.), lalu tanyakan ke user apakah ingin menjalankan `php artisan test --compact` penuh.

## Urutan Eksekusi

1. Konfirmasi Keputusan #1–#4 di atas dengan pemilik produk.
2. `composer require laravel/socialite` (setelah persetujuan).
3. Buat & jalankan migration Tahap 1 (roles → opd → users → user_identities → user_role_assignments → authentication_logs) secara berurutan agar foreign key valid.
4. Buat model + relasi (Tahap 2), factory pendukung test.
5. Update `RoleSeeder` (Tahap 3), jalankan `php artisan db:seed --class=RoleSeeder --no-interaction` di lingkungan dev/staging.
6. Implementasi `AuthenticationLogger` service, integrasikan ke `AuthenticatedSessionController` (Tahap 5).
7. Implementasi Google login: config, route, `GoogleAuthController`, `HandleGoogleLogin` (Tahap 4).
8. Tambah `EnsureAccountIsActive` middleware (Tahap 6).
9. Update tombol login di Blade (Tahap 7).
10. Update `UserController` untuk mencatat `user_role_assignments` (Tahap 8).
11. Tulis seluruh test pada Tahap 9, jalankan per filter.
12. `vendor/bin/pint --dirty --format agent` untuk merapikan seluruh file PHP yang disentuh.
13. Jalankan test terfilter sesuai kode yang paling banyak berubah, lalu tawarkan full test suite ke user.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Rename slug role memicu regresi otorisasi di banyak controller | Slug lama (`super-admin`) dipertahankan (Keputusan #1). |
| Kolom `password` nullable membuka celah bila validasi login lokal tidak memeriksa null | `LoginRequest`/`Auth::attempt()` Laravel otomatis gagal untuk hash null; tambahkan test eksplisit bahwa user tanpa password tidak bisa login lewat form lokal. |
| User publik hasil Google mendapat privilege admin lewat manipulasi request | Role selalu di-lookup dari master `roles` di server, tidak pernah dari payload klien (Tahap 4.5). |
| Penambahan dependency `laravel/socialite` tanpa sepengetahuan pemilik produk | Approval eksplisit sebelum `composer require` (Keputusan #2). |
| IP/session mentah tersimpan permanen dan melanggar prinsip privasi di `db-schema-v2.md` | Simpan hanya `ip_hash`/`session_id_hash` melalui `AuthenticationLogger` (Tahap 5). |

## Kriteria Selesai

- Migration additive berjalan bersih di database kosong maupun database berisi data existing (`php artisan migrate --pretend` dicek dulu di staging).
- User publik berhasil login/registrasi otomatis lewat Google dan mendapat role `publik`.
- Email yang sama dengan admin existing tertaut ke akun admin tersebut, bukan membuat akun publik baru.
- User `is_active = false` tidak bisa login maupun mempertahankan session aktif.
- Setiap login/logout admin dan publik tercatat di `authentication_logs` tanpa menyimpan IP/session mentah.
- Setiap perubahan role/OPD oleh Admin Sistem tercatat di `user_role_assignments` dengan actor dan waktunya.
- Seluruh test pada Tahap 9 lulus.
