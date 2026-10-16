# Plan Implementasi: Pelacakan Status Aspirasi Publik

## Status Implementasi

- **Tahap 1–5 — selesai.** Route, controller, view, update halaman submit, dan seluruh test Tahap 5 lulus (9 test, 30 assertion, plus regresi `FrontendPagesTest`/`HomepageTest`/test aspirasi admin — total 32 test lulus).
- Semua path file, nomor baris, dan mekanisme di bawah sudah diverifikasi langsung terhadap kode yang berjalan saat ini pada saat penulisan.

### Catatan audit saat eksekusi

- **Bug pra-eksisting ditemukan lewat test, lalu diperbaiki di sesi lanjutan (atas permintaan eksplisit)**: `FrontendController::aspirasiStore()` sebelumnya meng-hardcode `kategori_aspirasi_id = 1` sebagai kategori default untuk jenis "kritik & saran". Ini rapuh — bergantung penuh pada baris `kategori_aspirasi` dengan id persis `1` selalu ada, padahal tidak dijamin (tergantung urutan seeding/insert). Diperbaiki dengan mencari kategori berdasarkan nama (`KATEGORI_KRITIK_SARAN = 'Kritik dan Saran'`, konstanta baru di `FrontendController`) — konsisten dengan `aspirasi()` (baris 277-an) yang sudah memakai nama kategori yang sama untuk mengecualikannya dari dropdown usulan. Bila kategori itu tidak ditemukan, sistem sekarang mengembalikan error 500 JSON yang jelas ("Sistem belum siap menerima kritik & saran...") alih-alih FK violation mentah. Diuji di `tests/Feature/AspirasiKritikSaranKategoriTest.php` (2 test: kategori dengan id bukan 1 tetap terpakai benar; kategori hilang gagal dengan pesan jelas, bukan crash).
- Tidak ada penyesuaian lain terhadap rencana awal — seluruh kode (route, controller, view, JS) diterapkan persis seperti draf di Tahap 1–4.

## Tujuan

Menutup rekomendasi review *"mekanisme pelacakan status usulan belum terlihat secara jelas sehingga masyarakat belum dapat memantau perkembangan tindak lanjut terhadap aspirasi yang telah disampaikan"* dengan menyediakan halaman publik self-service untuk cek status aspirasi berdasarkan nomor tiket, tanpa perlu login.

## Referensi

- [`../Hasil_Review_Marimoi_Jamil.md`](../Hasil_Review_Marimoi_Jamil.md) — rekomendasi asli.
- [`../03_plan/10-tindak-lanjut-review-jamil.md`](../03_plan/10-tindak-lanjut-review-jamil.md) — bagian B (Pelacakan Status Aspirasi Publik).
- [`../03_plan/06-partisipasi-publik.md`](../03_plan/06-partisipasi-publik.md) — target akhir versi V2 penuh (`tracking_token_hash`, `submission_status_histories`, status history per actor/waktu). Dokumen ini adalah **versi minimum** di atas schema `aspirasi` yang ada sekarang, bukan implementasi §06 secara penuh.

## Cakupan

Termasuk: halaman publik cek status (form + hasil), verifikasi kepemilikan tanpa login, rate limiting, penambahan nomor tiket ke pesan sukses submit, link "Lacak Aspirasi" pada halaman submit.

Tidak termasuk (ditunda ke iterasi V2 penuh via `06-partisipasi-publik.md`): tabel `submission_status_histories` (riwayat status per waktu/actor — schema saat ini hanya punya satu kolom `status` tunggal, jadi timeline yang bisa ditampilkan terbatas pada status *saat ini*, bukan riwayat perubahan), `tracking_token_hash` terenkripsi terpisah (diganti kombinasi nomor tiket + email/phone pada iterasi ini), notifikasi push/SMS.

## Kondisi Existing (Audit Singkat)

| Area | Temuan |
| --- | --- |
| Model | `app/Models/Aspirasi.php` — `nomor_tiket` (unique, format `MARIMOI-ASP-YYYYMMDD-NNNN`, auto-generate di `boot()`), `status` (`pending`/`diproses`/`selesai`/`ditolak`), `tanggapan_admin`, `tanggal_respon`, `email`, `phone`. Accessor `getStatusBadgeAttribute()` sudah memetakan status ke warna badge (`warning`/`info`/`success`/`danger`). |
| Schema | Migration `aspirasi` ([`2025_08_05_091216_create_aspirasi_table.php`](../../../database/migrations/2025_08_05_091216_create_aspirasi_table.php)) — `nomor_tiket` sudah `unique()` dan diindeks (baris 17, 36). Tidak ada tabel histori status — hanya satu kolom `status` yang menyimpan kondisi terkini. |
| Update status (admin) | `AspirasiController::updateStatus()` (baris 154–222) — saat admin mengubah status, `tanggapan_admin` dan `tanggal_respon` diisi, lalu **email dikirim ke `$aspirasi->email`** berisi `tanggapan_admin` mentah (`app/Mail/TanggapanMail.php`, view `emails.tanggapan`). Ini menunjukkan `tanggapan_admin` **sudah dianggap aman untuk dikonsumsi publik** oleh sistem yang ada — halaman tracking bisa menampilkan field yang sama tanpa perlu kolom "response publik" terpisah. |
| Submit publik | `FrontendController::aspirasiStore()` (baris 943–1153) — response JSON sukses (baris 1142–1150) **sudah mengembalikan `nomor_tiket`**, tapi tidak pernah dipakai. |
| Form submit (frontend) | `resources/views/frontend/pages/aspirasi.blade.php` — `showModal('success', 'Aspirasi Berhasil Dikirim', data.message)` (baris 1279–1281) hanya menampilkan `data.message` generik dari server; `data.data.nomor_tiket` **dibuang begitu saja**, tidak pernah ditampilkan ke pengguna. Modal (`#modalOverlay`, baris 133–150) memakai `textContent` untuk `#modalMessage` (baris 1382) — tidak bisa menyisipkan link HTML tanpa elemen tambahan. |
| Routing | `routes/web.php` baris 22 (`GET /aspirasi-masyarakat` → form) dan baris 50 (`POST /aspirasi-masyarakat` → submit). **Tidak ada route pelacakan sama sekali.** |
| Rate limiting | Belum ada pola khusus untuk endpoint publik non-auth di proyek ini; pola `throttle:6,1` sudah dipakai untuk route sensitif lain (`routes/auth.php` baris 43, 47 — reset password), dijadikan acuan konsistensi. |
| Layout halaman publik | `aspirasi.blade.php` memakai `@extends('frontend.layouts.spatial', ['title' => ..., 'heroTitle' => ...])` (baris 1) — pola yang sama dipakai semua halaman publik lain (dikonfirmasi lewat `FrontendPagesTest::test_public_page_uses_the_shared_spatial_shell`). |

## Keputusan yang Perlu Dikunci Sebelum Implementasi

1. **Verifikasi kepemilikan: nomor tiket + email/phone yang cocok — bukan token terenkripsi terpisah.**
   `06-partisipasi-publik.md` menyebut `tracking_token_hash` sebagai desain akhir V2. Untuk iterasi ini, `nomor_tiket` (sudah unique + acak per hari) dikombinasikan dengan email/phone yang match sebagai verifikasi kepemilikan — tanpa kolom baru. Cukup untuk mencegah orang asing menebak status aspirasi orang lain hanya dari nomor tiket yang bocor/tertebak, sambil tetap simpel untuk iterasi pertama.
2. **Field yang ditampilkan ke publik: status, badge, tanggal pengajuan (`created_at`), tanggal respon (`tanggal_respon`), dan `tanggapan_admin` bila ada.** Field yang **tidak** ditampilkan: `admin_id`, nama admin yang menangani, `kategori_aspirasi_id`/routing OPD internal, `lampiran` (bisa berisi dokumen sensitif milik pengirim sendiri — tetap tidak ditampilkan di iterasi pertama untuk membatasi permukaan risiko, meski secara teknis milik pengirim sendiri).
3. **Timeline terbatas pada status saat ini, bukan riwayat perubahan.** Karena schema tidak punya tabel histori, tampilan hanya bisa menunjukkan langkah mana yang sudah dilewati berdasarkan `status` tunggal (`pending → diproses → selesai`, dengan `ditolak` sebagai state terminal terpisah) — bukan kapan setiap perubahan terjadi. Ini keterbatasan yang disengaja, bukan bug; disebut eksplisit di UI ("status terkini", bukan "riwayat lengkap").
4. **Rate limit `throttle:6,1`** (6 percobaan per menit per IP) pada endpoint pencarian saja (bukan pada halaman form-nya), konsisten dengan pola yang sudah dipakai `routes/auth.php`.
5. **Pesan error yang sama** untuk "nomor tiket tidak ditemukan" maupun "nomor tiket ditemukan tapi email/phone tidak cocok" — supaya endpoint ini tidak bisa dipakai untuk enumerasi nomor tiket yang valid.
6. **Normalisasi nomor HP saat pencocokan** (strip karakter non-digit, samakan prefix `0`/`62`) supaya pengguna yang mengetik `+62812...`, `62812...`, atau `0812...` tetap cocok dengan yang tersimpan — dilakukan di PHP setelah `nomor_tiket` ditemukan (bukan query SQL), karena `nomor_tiket` sudah unique sehingga hasil query selalu ≤1 baris.

## Tahap 1 — Route

`routes/web.php`, setelah baris 50 (`aspirasi-masyarakat.store`):

```php
// LACAK STATUS ASPIRASI //
Route::get('/aspirasi-masyarakat/lacak', [FrontendController::class, 'aspirasiLacak'])->name('aspirasi-masyarakat.lacak');
Route::post('/aspirasi-masyarakat/lacak', [FrontendController::class, 'aspirasiLacakCari'])
    ->name('aspirasi-masyarakat.lacak.cari')
    ->middleware('throttle:6,1');
```

## Tahap 2 — Controller

Dua method baru di `FrontendController` (dekat `aspirasiStore()`, sekitar baris 1153 setelah method tersebut selesai). Perlu `use App\Models\Aspirasi;` — sudah ada di top-of-file (dipakai `aspirasiStore()`).

### 2.1 `aspirasiLacak()` — tampilkan form kosong

```php
public function aspirasiLacak()
{
    return view('frontend.pages.aspirasi-lacak');
}
```

### 2.2 `aspirasiLacakCari()` — proses pencarian

```php
public function aspirasiLacakCari(Request $request)
{
    $validated = $request->validate([
        'nomor_tiket' => 'required|string|max:30',
        'kontak' => 'required|string|max:255',
    ], [
        'nomor_tiket.required' => 'Nomor tiket wajib diisi.',
        'kontak.required' => 'Email atau nomor WhatsApp wajib diisi.',
    ]);

    $aspirasi = Aspirasi::where('nomor_tiket', trim($validated['nomor_tiket']))->first();

    $cocok = $aspirasi && (
        ($aspirasi->email && Str::lower($aspirasi->email) === Str::lower(trim($validated['kontak'])))
        || ($aspirasi->phone && $this->normalizeTelepon($aspirasi->phone) === $this->normalizeTelepon($validated['kontak']))
    );

    if (! $cocok) {
        return view('frontend.pages.aspirasi-lacak', [
            'notFound' => true,
        ])->withInput($request->only('nomor_tiket'));
    }

    return view('frontend.pages.aspirasi-lacak', [
        'aspirasi' => $aspirasi,
    ]);
}

/**
 * Normalisasi nomor telepon untuk pencocokan: buang karakter non-digit,
 * lalu samakan prefix 0/62 supaya "0812...", "62812...", "+62812..." dianggap sama.
 */
private function normalizeTelepon(string $value): string
{
    $digits = preg_replace('/\D+/', '', $value) ?? '';

    return preg_replace('/^(0|62)/', '', $digits) ?? $digits;
}
```

Catatan penting: pesan error untuk "nomor tiket tidak ada" dan "nomor tiket ada tapi kontak tidak cocok" **sengaja sama** (`notFound` generik) — lihat Keputusan #5. `withInput($request->only('nomor_tiket'))` mengisi ulang field nomor tiket saja (bukan kontak, supaya tidak "membantu" percobaan berikutnya menebak dari sisa input sebelumnya di form — meski browser autofill tetap bisa mengisi ulang, ini bukan proteksi keras, hanya UX yang tidak membocorkan disengaja).

## Tahap 3 — View

File baru `resources/views/frontend/pages/aspirasi-lacak.blade.php`, mengikuti pola layout `aspirasi.blade.php` (Tailwind, `frontend.layouts.spatial`), tanpa hCaptcha/Leaflet karena tidak dibutuhkan di halaman ini.

```blade
@extends('frontend.layouts.spatial', ['title' => 'Lacak Aspirasi', 'heroTitle' => 'Lacak Status Aspirasi'])

@section('main')
    <section class="min-h-screen mt-0 pt-8 pb-8 bg-slate-50">
        <div class="container mx-auto px-4 max-w-xl">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
                <h3 class="text-lg text-center font-semibold mb-2 text-slate-800">Lacak Status Aspirasi</h3>
                <p class="text-center text-slate-600 mb-6 text-sm">
                    Masukkan nomor tiket yang Anda terima saat mengirim aspirasi, beserta email atau nomor WhatsApp yang Anda daftarkan.
                </p>

                <form action="{{ route('aspirasi-masyarakat.lacak.cari') }}" method="post" class="space-y-4">
                    @csrf
                    <div>
                        <label for="nomor_tiket" class="block text-sm font-medium text-slate-700 mb-1">Nomor Tiket</label>
                        <input type="text" id="nomor_tiket" name="nomor_tiket"
                            value="{{ old('nomor_tiket') }}" placeholder="MARIMOI-ASP-20260101-0001"
                            class="w-full rounded-lg border-slate-300 @error('nomor_tiket') border-red-500 @enderror" required>
                        @error('nomor_tiket')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="kontak" class="block text-sm font-medium text-slate-700 mb-1">Email atau Nomor WhatsApp</label>
                        <input type="text" id="kontak" name="kontak" placeholder="nama@email.com atau 0812xxxxxxx"
                            class="w-full rounded-lg border-slate-300 @error('kontak') border-red-500 @enderror" required>
                        @error('kontak')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2.5 font-medium hover:bg-blue-700">
                        Cek Status
                    </button>
                </form>

                @if (! empty($notFound))
                    <div class="mt-6 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-4">
                        Nomor tiket tidak ditemukan, atau email/nomor WhatsApp tidak cocok dengan data pengajuan.
                        Periksa kembali nomor tiket pada email konfirmasi Anda.
                    </div>
                @endif

                @isset($aspirasi)
                    @php
                        $steps = ['pending' => 0, 'diproses' => 1, 'selesai' => 2];
                        $currentStep = $steps[$aspirasi->status] ?? 0;
                        $ditolak = $aspirasi->status === 'ditolak';
                    @endphp
                    <div class="mt-6 border-t border-slate-200 pt-6">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-slate-800">{{ $aspirasi->judul_aspirasi }}</h4>
                            <span class="text-xs px-3 py-1 rounded-full font-medium
                                @class([
                                    'bg-yellow-100 text-yellow-700' => $aspirasi->status === 'pending',
                                    'bg-blue-100 text-blue-700' => $aspirasi->status === 'diproses',
                                    'bg-green-100 text-green-700' => $aspirasi->status === 'selesai',
                                    'bg-red-100 text-red-700' => $ditolak,
                                ])">
                                {{ ucfirst($aspirasi->status) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mb-4">Nomor tiket: {{ $aspirasi->nomor_tiket }} &middot; Diajukan {{ $aspirasi->created_at->format('d M Y') }}</p>

                        @if ($ditolak)
                            <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-4 mb-4">
                                Aspirasi ini tidak dapat kami tindak lanjuti.
                            </div>
                        @else
                            <ol class="flex items-center w-full mb-4 text-xs">
                                @foreach (['Diterima', 'Diproses', 'Selesai'] as $i => $label)
                                    <li class="flex-1 flex flex-col items-center {{ !$loop->last ? 'relative' : '' }}">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px]
                                            {{ $i <= $currentStep ? 'bg-blue-600' : 'bg-slate-300' }}">{{ $i + 1 }}</div>
                                        <span class="mt-1 text-slate-600">{{ $label }}</span>
                                    </li>
                                @endforeach
                            </ol>
                            <p class="text-xs text-slate-400 mb-4">Menampilkan status terkini, bukan riwayat lengkap perubahan.</p>
                        @endif

                        <div class="text-sm">
                            <span class="text-slate-500">Tanggapan{{ $aspirasi->tanggal_respon ? ' ('.$aspirasi->tanggal_respon->format('d M Y').')' : '' }}:</span>
                            <p class="mt-1 whitespace-pre-line text-slate-700">
                                {{ $aspirasi->tanggapan_admin ?: 'Belum ada tanggapan. Mohon tunggu, tim kami akan segera memproses.' }}
                            </p>
                        </div>
                    </div>
                @endisset
            </div>
        </div>
    </section>
@endsection
```

Catatan: `@class` directive Blade dipakai untuk badge status kondisional — konsisten dengan Laravel 12 (tersedia sejak Laravel 9), aman dipakai di codebase ini.

## Tahap 4 — Update Halaman Submit Aspirasi

### 4.1 Tambah link "Lacak Aspirasi" di `aspirasi.blade.php`

Setelah judul form (baris 265, `<h3 ...>Formulir Usulan Aspirasi</h3>`):

```blade
<div class="flex items-center justify-between mb-6">
    <h3 class="text-lg font-semibold text-slate-800">Formulir Usulan Aspirasi</h3>
    <a href="{{ route('aspirasi-masyarakat.lacak') }}" class="text-sm text-blue-600 hover:underline">
        Sudah pernah mengirim? Lacak status
    </a>
</div>
```

(Menggantikan `<h3 class="text-lg text-center font-semibold mb-6 text-slate-800">Formulir Usulan Aspirasi</h3>` yang sudah ada — layout `text-center` diganti `flex justify-between` supaya link muat di baris yang sama.)

### 4.2 Tampilkan nomor tiket di modal sukses

`aspirasi.blade.php` baris 1279–1281 saat ini:

```js
if (data.status === 'success') {
    this.showModal('success', 'Aspirasi Berhasil Dikirim', data.message);
    this.resetForm();
}
```

Diubah supaya nomor tiket ikut ditampilkan (memakai `data.data.nomor_tiket` yang sebenarnya **sudah** dikembalikan API sejak awal, lihat audit di atas):

```js
if (data.status === 'success') {
    const tiket = data.data?.nomor_tiket;
    const pesan = tiket
        ? `${data.message} Nomor tiket Anda: ${tiket}. Simpan nomor ini untuk melacak status pengajuan.`
        : data.message;
    this.showModal('success', 'Aspirasi Berhasil Dikirim', pesan);
    this.resetForm();
}
```

Ini memakai mekanisme `showModal()` yang sudah ada (`#modalMessage` via `textContent`, baris 1382) tanpa mengubah struktur modal — nomor tiket disisipkan sebagai teks biasa ke pesan yang sama, bukan elemen HTML baru, supaya perubahan tetap minimal. Link langsung ke halaman lacak **tidak** disisipkan di modal ini (karena `textContent` tidak merender HTML); pengguna diarahkan lewat link statis di Tahap 4.1 yang selalu terlihat di halaman yang sama.

## Tahap 5 — Testing (PHPUnit)

| Test | Skenario |
| --- | --- |
| `tests/Feature/AspirasiTrackingTest.php` | (1) Guest bisa buka halaman lacak (`GET aspirasi-masyarakat.lacak` → 200); (2) nomor tiket + email yang cocok → menampilkan status, judul, tanggapan; (3) nomor tiket + phone dengan format beda (`+62...` vs `08...`) tetap cocok (uji `normalizeTelepon`); (4) nomor tiket benar tapi kontak salah → pesan generik "tidak ditemukan", **tidak** menampilkan detail aspirasi apa pun; (5) nomor tiket sama sekali tidak ada → pesan generik yang **identik** dengan skenario (4) (memverifikasi tidak ada enumerasi); (6) percobaan ke-7 dalam 1 menit dari IP yang sama → `429`; (7) aspirasi berstatus `ditolak` → tidak menampilkan timeline langkah, menampilkan pesan penolakan; (8) aspirasi tanpa `tanggapan_admin` → menampilkan pesan fallback "Belum ada tanggapan". |
| Perluasan `tests/Feature/FrontendPagesTest.php` atau file baru kecil | Response sukses `aspirasiStore()` (route `aspirasi-masyarakat.store`) tetap menyertakan `nomor_tiket` di JSON (regression guard — field ini sudah ada sebelum perubahan, dipertahankan eksplisit dengan test supaya perubahan Tahap 4.2 tidak diam-diam merusaknya). |

Jalankan dengan filter dulu (`php artisan test --compact --filter=AspirasiTracking`), baru tawarkan full suite ke user.

## Urutan Eksekusi

1. Konfirmasi Keputusan #1–#6 di atas dengan pemilik produk (khususnya #2: field apa saja yang aman ditampilkan publik).
2. Tambah route Tahap 1.
3. Tambah dua method controller Tahap 2 (`aspirasiLacak`, `aspirasiLacakCari`, `normalizeTelepon`).
4. Buat view baru Tahap 3 (`aspirasi-lacak.blade.php`).
5. Update `aspirasi.blade.php` (Tahap 4.1 link, Tahap 4.2 modal sukses).
6. Tulis test Tahap 5, jalankan per filter.
7. `vendor/bin/pint --dirty --format agent` untuk merapikan file PHP yang disentuh.
8. Jalankan test terfilter, cek regresi (`FrontendPagesTest`, test aspirasi admin yang sudah ada), lalu tawarkan full test suite ke user.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Endpoint pelacakan dipakai untuk enumerasi nomor tiket valid (mencoba banyak nomor tiket, melihat mana yang "ketemu" vs "tidak") | Pesan error identik untuk "tidak ada" maupun "kontak tidak cocok" (Keputusan #5); tidak ada perbedaan status code atau waktu respons yang signifikan antara kedua kasus karena keduanya sama-sama satu query `where nomor_tiket = ?` (indexed, cepat). |
| Brute-force kombinasi nomor tiket + email/phone | `throttle:6,1` per IP (Keputusan #4) — cukup untuk mencegah percobaan otomatis kasar; bukan proteksi sempurna terhadap distributed attack, tapi sepadan dengan risiko data yang terekspos (bukan data finansial/kredensial). |
| Data lampiran/informasi sensitif lain ikut terekspos di halaman publik | Field yang ditampilkan dibatasi eksplisit (Keputusan #2) — `lampiran`, `admin_id`, dan detail routing OPD internal sengaja tidak disertakan di view Tahap 3. |
| Normalisasi nomor telepon terlalu longgar (mis. dua nomor beda negara kebetulan sama setelah strip prefix) | Risiko rendah untuk konteks provinsi tunggal (Maluku Utara, semua nomor domestik `+62`); didokumentasikan sebagai batasan yang diterima, bukan celah yang diabaikan. |
| Perubahan pada `showModal()` (Tahap 4.2) tidak sengaja merusak alur sukses yang sudah berjalan | Perubahan dibatasi ke penyusunan string pesan saja, tidak mengubah `showModal()` itu sendiri atau elemen DOM modal; test regresi (Tahap 5, baris kedua tabel) memverifikasi `nomor_tiket` tetap ada di response JSON. |

## Kriteria Selesai

- [x] Pengirim bisa melacak status aspirasi tanpa login, hanya dengan nomor tiket + email/nomor WhatsApp yang terdaftar.
- [x] Endpoint pelacakan tidak membocorkan keberadaan nomor tiket ke pihak yang tidak tahu kontak yang benar (pesan error generik, tidak ada enumerasi) — diuji `test_correct_ticket_with_wrong_contact_shows_generic_not_found` dan `test_nonexistent_ticket_shows_identical_generic_message`.
- [x] Percobaan berulang di luar batas wajar kena rate limit (`429` setelah 6 percobaan/menit) — diuji `test_tracking_search_is_rate_limited`.
- [x] Link "Lacak Aspirasi" muncul di halaman submit aspirasi dan nomor tiket ditampilkan jelas setelah submit berhasil (link statis + pesan modal sukses; belum diverifikasi visual di browser, lihat catatan di bawah).
- [x] Aspirasi berstatus `ditolak` ditampilkan berbeda dari status progres normal — diuji `test_rejected_aspirasi_shows_rejection_message_without_progress_steps`.
- [x] Field yang ditampilkan terbatas pada yang sudah disepakati aman untuk publik (Keputusan #2) — tidak ada regresi privasi.
- [x] Seluruh test Tahap 5 lulus (9 test baru, 30 assertion; 32 test lulus termasuk regresi terkait).

### Belum diverifikasi — perlu tindak lanjut

- Tampilan visual halaman lacak, badge status, dan modal sukses belum dicek langsung di browser — hanya diverifikasi lewat feature test (response HTML), sesuai keterbatasan sesi non-interaktif ini.
- Tidak ada perubahan pada aset yang di-build Vite (`resources/js/spatial.js`/`resources/css/*`) di fitur ini — tidak perlu `npm run build`.
