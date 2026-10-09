@extends('frontend.layouts.spatial', ['title' => 'Aspirasi Masyarakat - MARIMOI', 'heroTitle' => 'Aspirasi Masyarakat'])

@section('subtitle', 'Sampaikan usulan pembangunan atau kritik dan saran untuk Maluku Utara, lalu pantau tindak
    lanjutnya dengan nomor tiket.')

    @php
        $kicker =
            'mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-ocean before:h-px before:w-7 before:bg-current';
        $input =
            'block w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-[0.9375rem] text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-ocean focus:ring-2 focus:ring-ocean/20';
        $label = 'mb-1 block text-sm font-semibold text-slate-700';
        $feedback = 'invalid-feedback mt-1 hidden text-sm text-red-600';
        $legend = 'mb-3 flex items-center gap-2.5 text-base font-bold text-navy';
        $badge = 'grid h-6 w-6 shrink-0 place-items-center rounded-full bg-ocean/10 font-grotesk text-xs text-ocean';
        $btnPrimary =
            'inline-flex items-center justify-center gap-2 rounded-full bg-ocean px-7 py-3.5 text-[0.9375rem] font-bold text-white shadow-[0_10px_30px_-12px_rgba(10,132,255,.8)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_38px_-10px_rgba(32,217,255,.75)] disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0';
        $btnSoft =
            'inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-ocean hover:text-ocean disabled:cursor-wait disabled:opacity-70';
        $jenis = [
            [
                'usulan',
                'Usulan Pembangunan',
                'Usulkan pembangunan atau perbaikan infrastruktur di lokasi tertentu.',
                'bi-geo-alt-fill',
            ],
            [
                'kritik & saran',
                'Kritik & Saran',
                'Beri masukan umum untuk layanan dan pembangunan, tanpa menentukan lokasi.',
                'bi-chat-square-text-fill',
            ],
        ];
        $panduan = [
            [
                'bi-geo-alt',
                'Menentukan lokasi usulan',
                [
                    'Tekan "Gunakan Lokasi Saat Ini" bila Anda berada di lokasi tersebut.',
                    'Atau ketuk/klik langsung pada peta. Pin dapat digeser untuk merapikan posisi.',
                ],
            ],
            [
                'bi-paperclip',
                'Lampiran',
                [
                    'Wajib untuk usulan pembangunan, opsional untuk kritik & saran.',
                    'Format JPG, PNG, PDF, DOC, atau DOCX, maksimal 5 MB.',
                    'Bisa berupa foto lokasi, sketsa, atau dokumen pendukung.',
                ],
            ],
            [
                'bi-ticket-perforated',
                'Nomor tiket',
                [
                    'Setelah terkirim, Anda menerima nomor tiket di layar dan melalui email.',
                    'Gunakan nomor tiket beserta email atau nomor WhatsApp untuk melacak status.',
                ],
            ],
            [
                'bi-shield-lock',
                'Privasi data',
                [
                    'Data dipakai untuk memproses dan menindaklanjuti aspirasi Anda.',
                    'Data tidak dibagikan kepada pihak ketiga tanpa persetujuan.',
                ],
            ],
        ];
        $statusLabel = [
            'pending' => 'Menunggu',
            'diproses' => 'Diproses',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ];
        $lacakErrors = $errors->getBag('lacak');
    @endphp

    @push('styles')
        <script src="https://js.hcaptcha.com/1/api.js?hl=id" async defer></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <style>
            /* Status validasi di-toggle oleh skrip formulir. */
            #formUsulan .is-invalid {
                border-color: #ef4444;
                box-shadow: 0 0 0 3px rgba(239, 68, 68, .12);
            }

            #formUsulan .is-valid {
                border-color: #10b981;
            }

            /* Panel tab memakai class grid; pastikan atribut hidden tetap menyembunyikannya. */
            [role="tabpanel"][hidden] {
                display: none !important;
            }
        </style>
    @endpush

@section('main')
    @php
        // Tab awal: Lacak bila baru saja mencari tiket (hasil, tidak ditemukan, atau error validasi).
        $tabAwal = $hasilLacak || $lacakTidakDitemukan || $lacakErrors->any() ? 'lacak' : 'kirim';
        $tabs = [['kirim', 'Kirim Aspirasi', 'bi-send'], ['lacak', 'Lacak Status', 'bi-search']];
    @endphp
    <section class="bg-mist pb-16 md:pb-20">
        {{-- Tab menempel di bawah navbar supaya bisa berpindah tanpa scroll --}}
        <div class="sticky top-[4.75rem] z-30 border-b border-slate-900/10 bg-mist/90 backdrop-blur-md">
            <div class="mx-auto w-full max-w-[73.75rem] px-6 py-3">
                <div role="tablist" aria-label="Pilih layanan aspirasi"
                    class="inline-flex w-full rounded-full border border-slate-900/10 bg-white p-1 shadow-sm sm:w-auto">
                    @foreach ($tabs as [$kunci, $nama, $ikon])
                        <button type="button" role="tab" id="tab-{{ $kunci }}" data-tab="{{ $kunci }}"
                            aria-controls="panel-{{ $kunci }}"
                            aria-selected="{{ $tabAwal === $kunci ? 'true' : 'false' }}"
                            tabindex="{{ $tabAwal === $kunci ? '0' : '-1' }}"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:text-navy aria-selected:bg-ocean aria-selected:text-white aria-selected:shadow-[0_8px_20px_-10px_rgba(10,132,255,.9)] sm:flex-none sm:px-6">
                            <i class="bi {{ $ikon }}"></i> {{ $nama }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Tab: Kirim aspirasi --}}
        <div id="panel-kirim" role="tabpanel" aria-labelledby="tab-kirim" @if ($tabAwal !== 'kirim') hidden @endif
            class="mx-auto grid w-full max-w-[73.75rem] gap-6 px-6 pt-8 lg:grid-cols-[minmax(0,1fr)_18rem] lg:gap-8">
            <div
                class="rounded-3xl border border-slate-900/10 bg-white p-5 shadow-[0_30px_60px_-40px_rgba(7,26,45,.35)] sm:p-7">
                <p class="mb-6 text-sm text-slate-500">Kolom bertanda <span class="text-red-500">*</span> wajib diisi.</p>
                <form action="{{ route('aspirasi-masyarakat.store') }}" method="post" enctype="multipart/form-data"
                    id="formUsulan" novalidate class="space-y-7">
                    @csrf

                    {{-- 1. Jenis aspirasi --}}
                    <fieldset data-field>
                        <legend class="{{ $legend }}"><span class="{{ $badge }}">1</span> Jenis aspirasi <span
                                class="text-red-500">*</span></legend>
                        <div class="grid gap-2.5 sm:grid-cols-2">
                            @foreach ($jenis as [$nilai, $nama, $keterangan, $ikon])
                                <label class="relative block cursor-pointer">
                                    <input type="radio" name="jenis_aspirasi" value="{{ $nilai }}"
                                        class="peer sr-only" @if ($loop->first) required @endif>
                                    <span
                                        class="flex h-full gap-3 rounded-2xl border-2 border-slate-200 px-4 py-3 transition peer-checked:border-ocean peer-checked:bg-ocean/[0.04] peer-focus-visible:ring-2 peer-focus-visible:ring-ocean/40 hover:border-slate-300">
                                        <i class="bi {{ $ikon }} text-lg text-ocean"></i>
                                        <span>
                                            <span class="block font-bold text-navy">{{ $nama }}</span>
                                            <span
                                                class="mt-0.5 block text-[0.8125rem] leading-snug text-slate-500">{{ $keterangan }}</span>
                                        </span>
                                    </span>
                                    <i
                                        class="bi bi-check-circle-fill absolute right-4 top-4 hidden text-ocean peer-checked:block"></i>
                                </label>
                            @endforeach
                        </div>
                        <p class="{{ $feedback }}"></p>
                    </fieldset>

                    {{-- 2. Data diri --}}
                    <fieldset>
                        <legend class="{{ $legend }}"><span class="{{ $badge }}">2</span> Data diri</legend>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div data-field>
                                <label for="nama_pengirim" class="{{ $label }}">Nama lengkap <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="nama_pengirim" id="nama_pengirim" class="{{ $input }}"
                                    placeholder="Nama sesuai identitas" autocomplete="name" required minlength="3"
                                    maxlength="100">
                                <p class="{{ $feedback }}"></p>
                            </div>
                            <div data-field>
                                <label for="alamat" class="{{ $label }}">Alamat <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="alamat" id="alamat" class="{{ $input }}"
                                    placeholder="Desa/kelurahan, kecamatan, kabupaten/kota" autocomplete="street-address"
                                    required minlength="5" maxlength="200">
                                <p class="{{ $feedback }}"></p>
                            </div>
                            <div data-field>
                                <label for="email" class="{{ $label }}">Email aktif <span
                                        class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" class="{{ $input }}"
                                    placeholder="nama@email.com" autocomplete="email" inputmode="email" required>
                                <p class="{{ $feedback }}"></p>
                            </div>
                            <div data-field>
                                <label for="phone" class="{{ $label }}">Nomor WhatsApp <span
                                        class="text-red-500">*</span></label>
                                <input type="tel" name="phone" id="phone" class="{{ $input }}"
                                    placeholder="08xxxxxxxxxx" autocomplete="tel" inputmode="tel" required
                                    pattern="^(\+?62|0)[0-9]{9,13}$" title="Format: 08xxxxxxxxxx atau +628xxxxxxxxxx">
                                <p class="{{ $feedback }}"></p>
                            </div>
                        </div>
                        <p class="mt-2.5 flex items-start gap-2 text-xs text-slate-500"><i
                                class="bi bi-info-circle mt-px"></i> Email dan nomor WhatsApp dipakai untuk mengirim nomor
                            tiket dan memverifikasi saat Anda melacak status.</p>
                    </fieldset>

                    {{-- 3. Isi aspirasi --}}
                    <fieldset>
                        <legend class="{{ $legend }}"><span class="{{ $badge }}">3</span> Isi aspirasi
                        </legend>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div data-field id="kategoriUsulanContainer" class="hidden">
                                <label for="kategori_aspirasi_id" class="{{ $label }}">Kategori usulan <span
                                        class="text-red-500">*</span></label>
                                <select name="kategori_aspirasi_id" id="kategori_aspirasi_id"
                                    class="{{ $input }}">
                                    <option value="" selected disabled>Pilih kategori usulan</option>
                                    @foreach ($aspirasi as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama_kategori }}</option>
                                    @endforeach
                                </select>
                                <p class="{{ $feedback }}"></p>
                            </div>
                            <div data-field id="judulContainer" class="md:col-span-2">
                                <label for="judul_aspirasi" class="{{ $label }}">Judul <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="judul_aspirasi" id="judul_aspirasi"
                                    class="{{ $input }}" placeholder="Contoh: Perbaikan jalan desa yang rusak"
                                    required minlength="5" maxlength="150">
                                <p class="{{ $feedback }}"></p>
                            </div>
                            <div data-field class="md:col-span-2">
                                <label for="isi_aspirasi" class="{{ $label }}">Pesan <span
                                        class="text-red-500">*</span></label>
                                <textarea name="isi_aspirasi" id="isi_aspirasi" rows="4" class="{{ $input }}" required minlength="10"
                                    maxlength="1000" placeholder="Jelaskan kondisi, kebutuhan, atau masukan Anda selengkap mungkin."></textarea>
                                <div class="mt-1 flex items-start justify-between gap-4">
                                    <p class="{{ $feedback }} !mt-0"></p>
                                    <p class="ml-auto shrink-0 text-xs text-slate-500"><span id="charCount">0</span>/1000
                                        karakter</p>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    {{-- 4. Lokasi usulan (khusus usulan pembangunan) --}}
                    <fieldset id="mapContainer" class="hidden" data-field>
                        <legend class="{{ $legend }}"><span class="{{ $badge }}">4</span> Lokasi usulan
                            <span class="text-red-500">*</span></legend>
                        <div class="mb-3 flex flex-wrap gap-2">
                            <button type="button" id="getLocationBtn" class="{{ $btnSoft }}"><i
                                    class="bi bi-crosshair"></i> Gunakan Lokasi Saat Ini</button>
                            <button type="button" id="clearLocationBtn" class="{{ $btnSoft }}"><i
                                    class="bi bi-x-lg"></i> Hapus Lokasi</button>
                        </div>
                        <div id="locationStatus" class="mb-3 hidden rounded-xl border border-ocean/20 bg-ocean/5 p-3"
                            aria-live="polite">
                            <p class="flex items-center gap-2 text-sm text-ocean">
                                <span
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-ocean/30 border-t-ocean"></span>
                                <span id="locationStatusText">Mencari lokasi…</span>
                            </p>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-ocean/15">
                                <div id="locationProgress"
                                    class="h-full rounded-full bg-ocean transition-all duration-300" style="width: 0%">
                                </div>
                            </div>
                        </div>
                        <div class="isolate overflow-hidden rounded-2xl border border-slate-300">
                            <div id="map" class="h-64 w-full sm:h-72" role="application"
                                aria-label="Peta pemilihan lokasi usulan"></div>
                        </div>
                        <p class="mt-2 flex items-start gap-2 text-xs text-slate-500"><i
                                class="bi bi-hand-index mt-px"></i> Ketuk/klik peta untuk memilih lokasi. Pin dapat
                            digeser.</p>
                        <p id="locationInfo"
                            class="mt-2 hidden rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-sm text-emerald-700">
                            <i class="bi bi-check-circle-fill"></i> Lokasi dipilih: <b id="coordText"
                                class="font-grotesk font-medium"></b>
                        </p>
                        <p class="{{ $feedback }}"></p>
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                    </fieldset>

                    {{-- 5. Lampiran --}}
                    <fieldset data-field>
                        <legend class="{{ $legend }}"><span class="{{ $badge }}" data-step-number>4</span>
                            Lampiran
                            <span id="lampiranWajib" class="hidden text-red-500">*</span>
                            <span id="lampiranOpsional" class="text-sm font-medium text-slate-400">(opsional)</span>
                        </legend>
                        <label for="lampiran"
                            class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-3.5 transition hover:border-ocean hover:bg-ocean/[0.03]">
                            <i class="bi bi-cloud-arrow-up text-2xl text-ocean"></i>
                            <span><span class="block text-sm font-semibold text-navy">Pilih file untuk diunggah</span>
                                <span class="block text-xs text-slate-500">JPG, PNG, PDF, DOC, atau DOCX · maksimal 5
                                    MB</span></span>
                            <input type="file" name="lampiran" id="lampiran" class="sr-only"
                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        </label>
                        <p id="fileInfo"
                            class="mt-2 hidden items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-sm text-emerald-700">
                            <i class="bi bi-file-earmark-check-fill"></i> <span id="fileInfoText"></span>
                        </p>
                        <p class="{{ $feedback }}"></p>
                    </fieldset>

                    {{-- 6. Persetujuan & kirim --}}
                    <fieldset>
                        <legend class="{{ $legend }}"><span class="{{ $badge }}" data-step-number>5</span>
                            Kirim</legend>
                        <div data-field>
                            <label class="flex items-start gap-3 text-sm leading-relaxed text-slate-700" for="agreement">
                                <input type="checkbox" id="agreement" name="agreement" value="1" required
                                    class="mt-0.5 h-5 w-5 shrink-0 rounded border-slate-300 text-ocean focus:ring-ocean">
                                <span>Saya menyatakan informasi yang saya berikan benar dan dapat dipertanggungjawabkan,
                                    serta menyetujui penggunaan data sesuai
                                    <a href="{{ route('kebijakan_privasi') }}" target="_blank"
                                        class="font-semibold text-ocean underline-offset-2 hover:underline">kebijakan
                                        privasi</a>. <span class="text-red-500">*</span></span>
                            </label>
                            <p class="{{ $feedback }}"></p>
                        </div>
                        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="h-captcha" data-sitekey="{{ config('services.hcaptcha.sitekey_test') }}"></div>
                            <button type="submit" id="submitBtn" class="{{ $btnPrimary }} w-full sm:w-auto">
                                <i class="bi bi-send-fill"></i> <span id="submitText">Kirim Aspirasi</span>
                            </button>
                        </div>
                    </fieldset>
                </form>
            </div>

            {{-- Panduan singkat --}}
            <aside class="lg:sticky lg:top-[10.5rem] lg:self-start">
                <div class="rounded-3xl border border-slate-900/10 bg-white p-5">
                    <h2 class="mb-1 text-base font-bold text-navy">Panduan singkat</h2>
                    <p class="mb-4 text-sm text-slate-500">Hal yang perlu diketahui sebelum mengirim.</p>
                    <div class="divide-y divide-slate-900/10 border-t border-slate-900/10">
                        @foreach ($panduan as [$ikon, $judul, $poin])
                            <details class="group py-1" @if ($loop->first) open @endif>
                                <summary
                                    class="flex cursor-pointer list-none items-center gap-3 py-3 text-[0.9375rem] font-semibold text-navy [&::-webkit-details-marker]:hidden">
                                    <i class="bi {{ $ikon }} text-ocean"></i>
                                    <span class="flex-1">{{ $judul }}</span>
                                    <i
                                        class="bi bi-plus-lg text-slate-400 transition-transform duration-300 group-open:rotate-45"></i>
                                </summary>
                                <ul class="mb-3 ml-7 list-disc space-y-1.5 pl-4 text-sm leading-relaxed text-slate-600">
                                    @foreach ($poin as $teks)
                                        <li>{{ $teks }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>

        {{-- Tab: Lacak status --}}
        <div id="panel-lacak" role="tabpanel" aria-labelledby="tab-lacak" @if ($tabAwal !== 'lacak') hidden @endif
            class="mx-auto grid w-full max-w-[73.75rem] gap-6 px-6 pt-8 lg:grid-cols-[minmax(0,1fr)_18rem] lg:gap-8">
            <div
                class="rounded-3xl border border-slate-900/10 bg-white p-5 shadow-[0_30px_60px_-40px_rgba(7,26,45,.35)] sm:p-7 lg:self-start">
                <h2 class="mb-1 text-xl font-bold text-navy">Lacak Status Aspirasi</h2>
                <p class="mb-6 text-sm text-slate-500">Masukkan nomor tiket beserta email atau nomor WhatsApp yang Anda
                    gunakan saat mengirim aspirasi.</p>
                <form action="{{ route('aspirasi-masyarakat.lacak.cari') }}" method="post"
                    class="grid gap-5 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="nomor_tiket" class="{{ $label }}">Nomor tiket</label>
                        <input type="text" id="nomor_tiket" name="nomor_tiket" value="{{ old('nomor_tiket') }}"
                            placeholder="MARIMOI-ASP-20260101-0001"
                            class="{{ $input }} font-grotesk uppercase placeholder:normal-case {{ $lacakErrors->has('nomor_tiket') ? '!border-red-500' : '' }}"
                            required autocapitalize="characters" spellcheck="false">
                        @if ($lacakErrors->has('nomor_tiket'))
                            <p class="mt-1.5 text-sm text-red-600">{{ $lacakErrors->first('nomor_tiket') }}</p>
                        @endif
                    </div>
                    <div>
                        <label for="kontak" class="{{ $label }}">Email atau nomor WhatsApp</label>
                        <input type="text" id="kontak" name="kontak" value="{{ old('kontak') }}"
                            placeholder="nama@email.com atau 08xxxxxxxxxx"
                            class="{{ $input }} {{ $lacakErrors->has('kontak') ? '!border-red-500' : '' }}"
                            required>
                        @if ($lacakErrors->has('kontak'))
                            <p class="mt-1.5 text-sm text-red-600">{{ $lacakErrors->first('kontak') }}</p>
                        @endif
                    </div>
                    <button type="submit" class="{{ $btnPrimary }} sm:col-span-2"><i class="bi bi-search"></i> Cek
                        Status</button>
                </form>

                @if ($lacakTidakDitemukan)
                    <div class="mt-6 flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                        role="alert">
                        <i class="bi bi-exclamation-circle-fill mt-0.5"></i>
                        <p>Nomor tiket tidak ditemukan, atau email/nomor WhatsApp tidak cocok dengan data pengajuan.
                            Periksa kembali nomor tiket pada email konfirmasi Anda.</p>
                    </div>
                @endif

                @if ($hasilLacak)
                    @php
                        $langkahAktif = ['pending' => 0, 'diproses' => 1, 'selesai' => 2][$hasilLacak->status] ?? 0;
                        $ditolak = $hasilLacak->status === 'ditolak';
                    @endphp
                    <div class="mt-8 border-t border-slate-200 pt-6" role="status">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <h3 class="text-xl font-bold leading-snug text-navy">{{ $hasilLacak->judul_aspirasi }}</h3>
                            <span
                                @class([
                                    'rounded-full px-3 py-1 text-xs font-bold',
                                    'bg-amber-100 text-amber-700' => $hasilLacak->status === 'pending',
                                    'bg-blue-100 text-blue-700' => $hasilLacak->status === 'diproses',
                                    'bg-emerald-100 text-emerald-700' => $hasilLacak->status === 'selesai',
                                    'bg-red-100 text-red-700' => $ditolak,
                                ])>{{ $statusLabel[$hasilLacak->status] ?? ucfirst($hasilLacak->status) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500"><span
                                class="font-grotesk">{{ $hasilLacak->nomor_tiket }}</span> · Diajukan
                            {{ $hasilLacak->created_at->translatedFormat('d F Y') }}</p>

                        @if ($ditolak)
                            <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                Aspirasi ini tidak dapat kami tindak lanjuti.
                            </div>
                        @else
                            <ol class="mt-6 grid grid-cols-3">
                                @foreach (['Diterima', 'Diproses', 'Selesai'] as $i => $tahap)
                                    <li class="relative flex flex-col items-center text-center">
                                        @unless ($loop->first)
                                            <span
                                                class="absolute right-1/2 top-4 h-0.5 w-full -translate-y-1/2 {{ $i <= $langkahAktif ? 'bg-ocean' : 'bg-slate-200' }}"
                                                aria-hidden="true"></span>
                                        @endunless
                                        <span
                                            class="relative grid h-8 w-8 place-items-center rounded-full text-sm font-bold {{ $i <= $langkahAktif ? 'bg-ocean text-white' : 'bg-slate-200 text-slate-500' }}">
                                            @if ($i < $langkahAktif || ($i === $langkahAktif && $langkahAktif === 2))
                                                <i class="bi bi-check-lg"></i>@else{{ $i + 1 }}
                                            @endif
                                        </span>
                                        <span
                                            class="mt-2 text-sm {{ $i <= $langkahAktif ? 'font-semibold text-navy' : 'text-slate-400' }}">{{ $tahap }}</span>
                                    </li>
                                @endforeach
                            </ol>
                            <p class="mt-4 text-center text-xs text-slate-400">Menampilkan status terkini, bukan riwayat
                                lengkap perubahan.</p>
                        @endif

                        <div class="mt-6 rounded-2xl bg-mist p-5">
                            <p class="text-sm font-semibold text-slate-500">
                                Tanggapan{{ $hasilLacak->tanggal_respon ? ' · ' . $hasilLacak->tanggal_respon->translatedFormat('d F Y') : '' }}
                            </p>
                            <p class="mt-2 whitespace-pre-line text-slate-700">
                                {{ $hasilLacak->tanggapan_admin ?: 'Belum ada tanggapan. Mohon tunggu, tim kami akan segera memproses.' }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <aside class="rounded-3xl border border-slate-900/10 bg-white p-6 lg:self-start">
                <h2 class="mb-4 text-lg font-bold text-navy">Arti status</h2>
                <dl class="space-y-3 text-sm">
                    @foreach ([['Menunggu', 'Aspirasi sudah diterima dan menunggu peninjauan.', 'bg-amber-400'], ['Diproses', 'Aspirasi sedang ditinjau atau ditindaklanjuti.', 'bg-blue-500'], ['Selesai', 'Tindak lanjut selesai, lihat tanggapan tim.', 'bg-emerald-500'], ['Ditolak', 'Aspirasi tidak dapat ditindaklanjuti.', 'bg-red-500']] as [$nama, $arti, $warna])
                        <div class="flex gap-3">
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $warna }}"
                                aria-hidden="true"></span>
                            <div>
                                <dt class="font-semibold text-navy">{{ $nama }}</dt>
                                <dd class="text-slate-500">{{ $arti }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-5 border-t border-slate-900/10 pt-4 text-sm text-slate-500">Nomor tiket dikirim ke email Anda
                    saat aspirasi berhasil terkirim.</p>
            </aside>
        </div>
    </section>

    {{-- Modal status proses (loading, berhasil, gagal) --}}
    <div id="modalOverlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle"
        aria-describedby="modalMessage"
        class="invisible fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 p-4 opacity-0 backdrop-blur-sm transition-all duration-300">
        <div id="modalCard"
            class="w-full max-w-md translate-y-5 scale-95 rounded-3xl bg-white p-8 text-center shadow-2xl transition-transform duration-300">
            <div id="modalIcon" class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full text-3xl">
            </div>
            <h3 id="modalTitle" class="mb-2 text-xl font-bold text-navy">Memproses…</h3>
            <p id="modalMessage" class="text-slate-600">Mohon tunggu sebentar</p>
            <div id="modalTicket" class="mt-5 hidden rounded-2xl border border-ocean/20 bg-ocean/5 p-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Nomor tiket Anda</p>
                <p id="modalTicketCode" class="mt-1 break-all font-grotesk text-lg font-medium text-navy"></p>
                <button type="button" id="copyTicketBtn"
                    class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-ocean hover:underline"><i
                        class="bi bi-copy"></i> <span>Salin nomor tiket</span></button>
            </div>
            <div id="modalActions" class="mt-6 hidden flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="#lacak" id="modalTrackBtn" class="{{ $btnSoft }} hidden">Lacak status</a>
                <button type="button" id="modalCloseBtn" class="{{ $btnPrimary }} !py-2.5">Tutup</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        /**
         * Formulir aspirasi: jenis aspirasi menentukan kolom tambahan (kategori, lokasi, lampiran wajib),
         * validasi langsung per kolom, pemilihan lokasi di peta, dan pengiriman via fetch dengan hCaptcha.
         */
        class AspirasiForm {
            constructor() {
                this.map = null;
                this.currentLocationMarker = null;
                this.accuracyCircle = null;
                this.isSubmitting = false;
                this.lastTicket = null;

                this.initElements();
                this.initEventListeners();
                this.setupValidation();
            }

            initElements() {
                const byId = (id) => document.getElementById(id);
                this.elements = {
                    form: byId('formUsulan'),
                    jenisRadios: document.querySelectorAll('input[name="jenis_aspirasi"]'),
                    kategoriContainer: byId('kategoriUsulanContainer'),
                    kategoriSelect: byId('kategori_aspirasi_id'),
                    judulContainer: byId('judulContainer'),
                    mapContainer: byId('mapContainer'),
                    getLocationBtn: byId('getLocationBtn'),
                    clearLocationBtn: byId('clearLocationBtn'),
                    submitBtn: byId('submitBtn'),
                    submitText: byId('submitText'),
                    modalOverlay: byId('modalOverlay'),
                    modalCard: byId('modalCard'),
                    modalIcon: byId('modalIcon'),
                    modalTitle: byId('modalTitle'),
                    modalMessage: byId('modalMessage'),
                    modalActions: byId('modalActions'),
                    modalCloseBtn: byId('modalCloseBtn'),
                    modalTicket: byId('modalTicket'),
                    modalTicketCode: byId('modalTicketCode'),
                    modalTrackBtn: byId('modalTrackBtn'),
                    copyTicketBtn: byId('copyTicketBtn'),
                    lampiranInput: byId('lampiran'),
                    lampiranWajib: byId('lampiranWajib'),
                    lampiranOpsional: byId('lampiranOpsional'),
                    isiAspirasi: byId('isi_aspirasi'),
                    charCount: byId('charCount'),
                    latitude: byId('latitude'),
                    longitude: byId('longitude'),
                    fileInfo: byId('fileInfo'),
                    fileInfoText: byId('fileInfoText'),
                    locationInfo: byId('locationInfo'),
                    coordText: byId('coordText'),
                    locationStatus: byId('locationStatus'),
                    locationStatusText: byId('locationStatusText'),
                    locationProgress: byId('locationProgress'),
                    stepNumbers: document.querySelectorAll('[data-step-number]'),
                };
            }

            initEventListeners() {
                const el = this.elements;
                el.form.addEventListener('submit', (e) => this.handleSubmit(e));
                el.jenisRadios.forEach((radio) => radio.addEventListener('change', () => this
                .handleJenisAspirasiChange()));
                el.getLocationBtn.addEventListener('click', () => this.getCurrentLocation());
                el.clearLocationBtn.addEventListener('click', () => this.clearMapSelection());
                el.modalCloseBtn.addEventListener('click', () => this.hideModal());
                el.modalOverlay.addEventListener('click', (e) => {
                    if (e.target === el.modalOverlay) this.hideModal();
                });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isModalOpen() && !this.isSubmitting) this.hideModal();
                });
                el.lampiranInput.addEventListener('change', (e) => this.handleFileChange(e));
                el.isiAspirasi.addEventListener('input', (e) => this.updateCharCount(e));
                el.copyTicketBtn.addEventListener('click', () => this.copyTicket());
                el.modalTrackBtn.addEventListener('click', (e) => this.prefillTracking(e));
            }

            get jenisAspirasi() {
                return this.elements.form.elements.jenis_aspirasi.value;
            }

            // Aturan validasi tiap kolom (sama dengan aturan server di FrontendController::aspirasiStore).
            get rules() {
                return {
                    nama_pengirim: {
                        validate: (v) => v.length >= 3 && v.length <= 100,
                        message: 'Nama harus 3–100 karakter'
                    },
                    alamat: {
                        validate: (v) => v.length >= 5 && v.length <= 200,
                        message: 'Alamat harus 5–200 karakter'
                    },
                    email: {
                        validate: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
                        message: 'Format email tidak valid'
                    },
                    phone: {
                        validate: (v) => /^(\+?62|0)[0-9]{9,13}$/.test(v),
                        message: 'Format: 08xxxxxxxxxx atau +628xxxxxxxxxx'
                    },
                    judul_aspirasi: {
                        validate: (v) => v.length >= 5 && v.length <= 150,
                        message: 'Judul harus 5–150 karakter'
                    },
                    isi_aspirasi: {
                        validate: (v) => v.length >= 10 && v.length <= 1000,
                        message: 'Pesan harus 10–1000 karakter'
                    },
                };
            }

            setupValidation() {
                Object.entries(this.rules).forEach(([name, rule]) => {
                    const field = document.getElementById(name);
                    // Kolom baru divalidasi setelah ditinggalkan, lalu diperbarui saat diketik.
                    field.addEventListener('blur', () => field.value && this.validateField(field, rule.validate(
                        field.value.trim()), rule.message));
                    field.addEventListener('input', () => {
                        if (field.classList.contains('is-invalid') || field.classList.contains(
                                'is-valid')) {
                            this.validateField(field, rule.validate(field.value.trim()), rule.message);
                        }
                    });
                });
                this.elements.kategoriSelect.addEventListener('change', () => {
                    this.validateField(this.elements.kategoriSelect, !!this.elements.kategoriSelect.value,
                        'Pilih kategori usulan');
                });
                this.elements.form.elements.agreement.addEventListener('change', (e) => {
                    this.validateField(e.target, e.target.checked, 'Centang persetujuan untuk melanjutkan');
                });
            }

            validateField(field, isValid, message = '') {
                const feedback = field.closest('[data-field]')?.querySelector('.invalid-feedback');
                const isGroup = field.type === 'radio' || field.type === 'hidden';
                if (!isGroup) {
                    field.classList.toggle('is-invalid', !isValid);
                    field.classList.toggle('is-valid', isValid && field.type !== 'checkbox');
                    field.setAttribute('aria-invalid', String(!isValid));
                }
                if (feedback) {
                    feedback.textContent = isValid ? '' : message;
                    feedback.classList.toggle('hidden', isValid);
                }
                return isValid;
            }

            handleJenisAspirasiChange() {
                const el = this.elements;
                const isUsulan = this.jenisAspirasi === 'usulan';

                el.kategoriContainer.classList.toggle('hidden', !isUsulan);
                // Kategori & judul berbagi satu baris saat usulan; judul selebar penuh bila tanpa kategori.
                el.judulContainer.classList.toggle('md:col-span-2', !isUsulan);
                el.mapContainer.classList.toggle('hidden', !isUsulan);
                el.kategoriSelect.toggleAttribute('required', isUsulan);
                el.lampiranInput.toggleAttribute('required', isUsulan);
                el.lampiranWajib.classList.toggle('hidden', !isUsulan);
                el.lampiranOpsional.classList.toggle('hidden', isUsulan);
                // Penomoran langkah menyesuaikan: bagian Lokasi hanya ada untuk usulan.
                el.stepNumbers.forEach((badge, i) => {
                    badge.textContent = (isUsulan ? 5 : 4) + i;
                });

                if (isUsulan) {
                    setTimeout(() => this.initMap(), 50);
                } else {
                    el.kategoriSelect.value = '';
                    el.kategoriSelect.classList.remove('is-valid', 'is-invalid');
                    this.clearMapSelection();
                }

                this.validateField(el.jenisRadios[0], true);
            }

            updateCharCount(e) {
                const length = e.target.value.length;
                this.elements.charCount.textContent = length;
                this.elements.charCount.className = length >= 1000 ? 'font-bold text-red-600' : length > 900 ?
                    'font-medium text-orange-600' : '';
            }

            handleFileChange(e) {
                const file = e.target.files[0];
                const el = this.elements;

                if (!file) {
                    el.fileInfo.classList.add('hidden');
                    return;
                }

                const extension = file.name.toLowerCase().slice(file.name.lastIndexOf('.'));
                if (!['.jpg', '.jpeg', '.png', '.pdf', '.doc', '.docx'].includes(extension)) {
                    e.target.value = '';
                    el.fileInfo.classList.add('hidden');
                    this.validateField(e.target, false,
                        'Format file tidak didukung. Gunakan JPG, PNG, PDF, DOC, atau DOCX.');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    e.target.value = '';
                    el.fileInfo.classList.add('hidden');
                    this.validateField(e.target, false, 'Ukuran file maksimal 5 MB.');
                    return;
                }

                this.validateField(e.target, true);
                el.fileInfoText.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                el.fileInfo.classList.remove('hidden');
                el.fileInfo.classList.add('flex');
            }

            initMap() {
                if (this.map) {
                    this.map.invalidateSize();
                    return;
                }

                try {
                    this.map = L.map('map', {
                        center: [0.735485, 128.028201],
                        zoom: 8
                    });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19,
                    }).addTo(this.map);
                    this.map.on('click', (e) => this.setMarker(e.latlng));
                    setTimeout(() => this.map.invalidateSize(), 100);
                } catch (error) {
                    this.showModal('error', 'Gagal memuat peta', 'Muat ulang halaman lalu coba lagi.');
                }
            }

            setMarker(latlng) {
                if (!this.map) return;

                if (this.currentLocationMarker) {
                    this.map.removeLayer(this.currentLocationMarker);
                }
                this.currentLocationMarker = L.marker(latlng, {
                    draggable: true,
                    title: 'Lokasi usulan'
                }).addTo(this.map);
                this.currentLocationMarker.on('dragend', (e) => this.updateCoordinates(e.target.getLatLng()));
                this.updateCoordinates(latlng);
            }

            updateCoordinates(latlng) {
                const el = this.elements;
                el.latitude.value = latlng.lat.toFixed(6);
                el.longitude.value = latlng.lng.toFixed(6);
                el.coordText.textContent = `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
                el.locationInfo.classList.remove('hidden');
                this.validateField(el.latitude, true);
            }

            clearMapSelection() {
                if (this.currentLocationMarker && this.map) {
                    this.map.removeLayer(this.currentLocationMarker);
                    this.currentLocationMarker = null;
                }
                if (this.accuracyCircle && this.map) {
                    this.map.removeLayer(this.accuracyCircle);
                    this.accuracyCircle = null;
                }
                this.elements.latitude.value = '';
                this.elements.longitude.value = '';
                this.elements.locationInfo.classList.add('hidden');
                this.hideLocationStatus();
            }

            showLocationStatus(message, progress = 0) {
                this.elements.locationStatusText.textContent = message;
                this.elements.locationProgress.style.width = `${progress}%`;
                this.elements.locationStatus.classList.remove('hidden');
            }

            hideLocationStatus() {
                this.elements.locationStatus.classList.add('hidden');
                this.elements.locationProgress.style.width = '0%';
            }

            updateLocationProgress(progress, message = null) {
                this.elements.locationProgress.style.width = `${progress}%`;
                if (message) this.elements.locationStatusText.textContent = message;
            }

            /**
             * Ambil lokasi perangkat: coba akurasi tinggi dulu, lalu sekali lagi dengan pengaturan
             * yang lebih longgar bila gagal atau akurasinya masih di atas 50 meter.
             */
            getCurrentLocation() {
                if (!navigator.geolocation) {
                    this.showModal('error', 'Lokasi tidak didukung',
                        'Browser Anda tidak mendukung fitur lokasi. Pilih lokasi langsung di peta.');
                    return;
                }

                const button = this.elements.getLocationBtn;
                button.disabled = true;
                button.innerHTML = '<i class="bi bi-hourglass-split"></i> Mencari lokasi…';
                this.showLocationStatus('Memulai pencarian lokasi…', 10);

                const highAccuracy = {
                    enableHighAccuracy: true,
                    timeout: 20000,
                    maximumAge: 30000
                };
                const fallback = {
                    enableHighAccuracy: false,
                    timeout: 10000,
                    maximumAge: 120000
                };
                const maxAttempts = 2;
                let attempt = 0;
                let bestPosition = null;

                const tryGetLocation = (options, isRetry = false) => {
                    attempt++;
                    this.updateLocationProgress(isRetry ? 40 : 25, isRetry ?
                        'Mencoba dengan pengaturan alternatif…' : 'Mengakses GPS dengan akurasi tinggi…');

                    navigator.geolocation.getCurrentPosition((position) => {
                        const {
                            accuracy
                        } = position.coords;
                        this.updateLocationProgress(80, 'Memproses data lokasi…');

                        if (!bestPosition || accuracy < bestPosition.coords.accuracy) {
                            bestPosition = position;
                        }
                        if (accuracy <= 50 || attempt >= maxAttempts || isRetry) {
                            this.usePosition(bestPosition);
                        } else {
                            setTimeout(() => tryGetLocation(fallback, true), 1000);
                        }
                    }, (error) => {
                        if (!isRetry && attempt < maxAttempts) {
                            setTimeout(() => tryGetLocation(fallback, true), 1000);
                            return;
                        }
                        const messages = {
                            [error.PERMISSION_DENIED]: ['Akses lokasi ditolak',
                                'Izinkan akses lokasi di browser, atau pilih lokasi langsung di peta.'
                            ],
                            [error.POSITION_UNAVAILABLE]: ['Lokasi tidak tersedia',
                                'Pastikan GPS aktif dan sinyal baik, atau pilih lokasi langsung di peta.'
                            ],
                            [error.TIMEOUT]: ['Pencarian lokasi terlalu lama',
                                'Pastikan GPS aktif lalu coba lagi, atau pilih lokasi langsung di peta.'
                            ],
                        };
                        const [title, message] = messages[error.code] || ['Gagal mendapatkan lokasi',
                            'Silakan coba lagi atau pilih lokasi langsung di peta.'
                        ];
                        this.handleLocationError(title, message);
                    }, options);
                };

                tryGetLocation(highAccuracy);
                setTimeout(() => this.resetLocationButton(), 25000);
            }

            usePosition(position) {
                const {
                    latitude,
                    longitude,
                    accuracy
                } = position.coords;
                const latlng = L.latLng(latitude, longitude);
                this.setMarker(latlng);

                const zoom = accuracy <= 10 ? 18 : accuracy <= 50 ? 16 : accuracy <= 100 ? 15 : 14;
                this.map.setView(latlng, zoom);
                if (this.accuracyCircle) this.map.removeLayer(this.accuracyCircle);
                this.accuracyCircle = L.circle(latlng, {
                    radius: accuracy,
                    color: '#0a84ff',
                    fillOpacity: 0.08,
                    weight: 2,
                    dashArray: '5, 10'
                }).addTo(this.map);

                const quality = accuracy <= 10 ? 'sangat akurat' : accuracy <= 50 ? 'akurat' : accuracy <= 100 ?
                    'cukup akurat' : 'akurasi rendah, pertimbangkan memilih lokasi manual';
                this.updateLocationProgress(100, 'Lokasi ditemukan.');
                setTimeout(() => this.hideLocationStatus(), 2500);
                this.showModal('success', 'Lokasi ditemukan',
                    `Akurasi ±${Math.round(accuracy)} meter (${quality}). Geser pin bila perlu.`, {
                        autoHide: true
                    });
                this.resetLocationButton();
            }

            handleLocationError(title, message) {
                this.hideLocationStatus();
                this.showModal('error', title, message);
                this.resetLocationButton();
            }

            resetLocationButton() {
                this.elements.getLocationBtn.disabled = false;
                this.elements.getLocationBtn.innerHTML = '<i class="bi bi-crosshair"></i> Gunakan Lokasi Saat Ini';
            }

            /**
             * Periksa seluruh formulir sebelum dikirim; kolom pertama yang bermasalah difokuskan.
             * @returns {string|null} pesan kesalahan pertama, atau null bila semua valid
             */
            validateForm() {
                const el = this.elements;
                const problems = [];

                if (!this.jenisAspirasi) {
                    problems.push([el.jenisRadios[0], this.validateField(el.jenisRadios[0], false,
                        'Pilih jenis aspirasi'), 'Pilih jenis aspirasi terlebih dahulu.']);
                }
                Object.entries(this.rules).forEach(([name, rule]) => {
                    const field = document.getElementById(name);
                    const ok = this.validateField(field, rule.validate(field.value.trim()), field.value.trim() ?
                        rule.message : 'Kolom ini wajib diisi');
                    if (!ok) problems.push([field, false, 'Periksa kembali kolom yang ditandai merah.']);
                });
                if (this.jenisAspirasi === 'usulan') {
                    if (!this.validateField(el.kategoriSelect, !!el.kategoriSelect.value, 'Pilih kategori usulan')) {
                        problems.push([el.kategoriSelect, false, 'Pilih kategori usulan terlebih dahulu.']);
                    }
                    if (!this.validateField(el.latitude, !!el.latitude.value, 'Pilih lokasi usulan pada peta')) {
                        problems.push([el.getLocationBtn, false, 'Pilih lokasi usulan pada peta terlebih dahulu.']);
                    }
                    if (!el.lampiranInput.files.length) {
                        this.validateField(el.lampiranInput, false, 'Lampiran wajib untuk usulan pembangunan');
                        problems.push([el.lampiranInput.closest('[data-field]').querySelector('label'), false,
                            'Lampiran wajib disertakan untuk usulan pembangunan.'
                        ]);
                    }
                }
                const agreement = el.form.elements.agreement;
                if (!this.validateField(agreement, agreement.checked, 'Centang persetujuan untuk melanjutkan')) {
                    problems.push([agreement, false, 'Centang persetujuan untuk melanjutkan.']);
                }

                if (!problems.length) return null;
                const [target, , message] = problems[0];
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                target.focus({
                    preventScroll: true
                });
                return message;
            }

            async handleSubmit(e) {
                e.preventDefault();
                if (this.isSubmitting) return;

                const problem = this.validateForm();
                if (problem) {
                    this.showModal('error', 'Data belum lengkap', problem);
                    return;
                }
                if (typeof hcaptcha !== 'undefined' && !hcaptcha.getResponse()) {
                    this.showModal('error', 'Verifikasi belum selesai',
                        'Selesaikan verifikasi captcha sebelum mengirim.');
                    return;
                }

                this.showModal('loading', 'Mengirim aspirasi', 'Mohon tunggu sebentar…');
                if (this.jenisAspirasi !== 'usulan') {
                    await this.setCurrentLocationForNonUsulan();
                }
                this.submitForm();
            }

            // Kritik & saran: lokasi perangkat ikut dikirim bila diizinkan (tidak wajib).
            setCurrentLocationForNonUsulan() {
                return new Promise((resolve) => {
                    if (!navigator.geolocation) {
                        resolve();
                        return;
                    }
                    navigator.geolocation.getCurrentPosition((position) => {
                        this.elements.latitude.value = position.coords.latitude;
                        this.elements.longitude.value = position.coords.longitude;
                        resolve();
                    }, () => resolve(), {
                        timeout: 10000,
                        maximumAge: 300000,
                        enableHighAccuracy: false
                    });
                });
            }

            async submitForm() {
                const el = this.elements;
                this.isSubmitting = true;
                el.submitBtn.disabled = true;
                el.submitText.textContent = 'Mengirim…';

                const formData = new FormData(el.form);
                if (typeof hcaptcha !== 'undefined' && hcaptcha.getResponse()) {
                    formData.set('h-captcha-response', hcaptcha.getResponse());
                }

                try {
                    const response = await fetch(el.form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json'
                        },
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        const ticket = data.data?.nomor_tiket || null;
                        this.lastTicket = ticket ? {
                            ticket,
                            contact: el.form.elements.email.value.trim()
                        } : null;
                        this.showModal('success', 'Aspirasi berhasil dikirim', ticket ?
                            'Simpan nomor tiket berikut untuk melacak status. Salinannya juga dikirim ke email Anda.' :
                            data.message, {
                                ticket
                            });
                        this.resetForm();
                    } else {
                        this.handleSubmitError(data);
                    }
                } catch (error) {
                    this.showModal('error', 'Koneksi bermasalah', 'Terjadi kesalahan koneksi. Silakan coba lagi.');
                    this.resetCaptcha();
                } finally {
                    this.isSubmitting = false;
                    el.submitBtn.disabled = false;
                    el.submitText.textContent = 'Kirim Aspirasi';
                }
            }

            handleSubmitError(data) {
                this.resetCaptcha();
                if (data.errors) {
                    if (data.errors['h-captcha-response']) {
                        this.showModal('error', 'Verifikasi captcha gagal', data.errors['h-captcha-response'][0]);
                        return;
                    }
                    // Tandai kolom yang ditolak server supaya mudah ditemukan.
                    Object.entries(data.errors).forEach(([name, messages]) => {
                        const field = this.elements.form.elements[name];
                        const target = field instanceof RadioNodeList ? field[0] : field;
                        if (target) this.validateField(target, false, messages[0]);
                    });
                    const firstError = Object.values(data.errors)[0];
                    this.showModal('error', 'Data belum sesuai', Array.isArray(firstError) ? firstError[0] :
                    firstError);
                } else {
                    this.showModal('error', 'Terjadi kesalahan', data.message || 'Silakan coba lagi.');
                }
            }

            resetForm() {
                const el = this.elements;
                el.form.reset();
                el.form.querySelectorAll('input, select, textarea').forEach((field) => {
                    field.classList.remove('is-valid', 'is-invalid');
                    field.removeAttribute('aria-invalid');
                });
                el.form.querySelectorAll('.invalid-feedback').forEach((feedback) => feedback.classList.add('hidden'));
                this.handleJenisAspirasiChange();
                el.fileInfo.classList.add('hidden');
                el.charCount.textContent = '0';
                el.charCount.className = '';
                this.resetCaptcha();
            }

            resetCaptcha() {
                try {
                    if (typeof hcaptcha !== 'undefined') hcaptcha.reset();
                } catch (error) {
                    // hCaptcha belum siap; tidak ada yang perlu direset.
                }
            }

            async copyTicket() {
                if (!this.lastTicket) return;
                const label = this.elements.copyTicketBtn.querySelector('span');
                try {
                    await navigator.clipboard.writeText(this.lastTicket.ticket);
                    label.textContent = 'Tersalin';
                } catch (error) {
                    label.textContent = 'Salin manual nomor di atas';
                }
                setTimeout(() => {
                    label.textContent = 'Salin nomor tiket';
                }, 2000);
            }

            // Isi formulir lacak dengan tiket yang baru dibuat, tutup modal, lalu pindah ke tab Lacak.
            prefillTracking(event) {
                event?.preventDefault();
                if (this.lastTicket) {
                    document.getElementById('nomor_tiket').value = this.lastTicket.ticket;
                    document.getElementById('kontak').value = this.lastTicket.contact;
                }
                this.hideModal();
                window.AspirasiTabs?.show('lacak');
            }

            isModalOpen() {
                return !this.elements.modalOverlay.classList.contains('invisible');
            }

            /**
             * @param {'loading'|'success'|'error'} type
             * @param {Object} options autoHide (tutup otomatis 3 detik) dan ticket (nomor tiket untuk ditampilkan)
             */
            showModal(type, title, message, {
                autoHide = false,
                ticket = null
            } = {}) {
                const el = this.elements;
                const icons = {
                    success: ['bg-emerald-100 text-emerald-600', '<i class="bi bi-check-circle-fill"></i>'],
                    error: ['bg-red-100 text-red-600', '<i class="bi bi-exclamation-triangle-fill"></i>'],
                    loading: ['bg-ocean/10',
                        '<span class="h-10 w-10 animate-spin rounded-full border-4 border-ocean/20 border-t-ocean"></span>'
                    ],
                };
                const [iconClass, iconHtml] = icons[type] || icons.loading;

                el.modalIcon.className =
                    `mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full text-3xl ${iconClass}`;
                el.modalIcon.innerHTML = iconHtml;
                el.modalTitle.textContent = title;
                el.modalMessage.textContent = message;
                el.modalTicket.classList.toggle('hidden', !ticket);
                el.modalTicketCode.textContent = ticket || '';
                el.modalTrackBtn.classList.toggle('hidden', !ticket);
                el.modalActions.classList.toggle('hidden', type === 'loading');
                el.modalActions.classList.toggle('flex', type !== 'loading');

                el.modalOverlay.classList.remove('opacity-0', 'invisible');
                el.modalCard.classList.remove('scale-95', 'translate-y-5');
                if (type !== 'loading') el.modalCloseBtn.focus({
                    preventScroll: true
                });
                if (autoHide) setTimeout(() => this.hideModal(), 3000);
            }

            hideModal() {
                this.elements.modalOverlay.classList.add('opacity-0', 'invisible');
                this.elements.modalCard.classList.add('scale-95', 'translate-y-5');
            }
        }

        /**
         * Tab Kirim / Lacak. Tab aktif tercermin di hash URL (#kirim / #lacak) supaya bisa dibagikan
         * dan tetap terpilih setelah redirect pencarian tiket. Panah kiri/kanan berpindah tab.
         */
        const AspirasiTabs = (() => {
            const tabs = [...document.querySelectorAll('[role="tab"][data-tab]')];
            let onShow = () => {};

            function show(name, {
                focus = false
            } = {}) {
                tabs.forEach((tab) => {
                    const active = tab.dataset.tab === name;
                    tab.setAttribute('aria-selected', String(active));
                    tab.tabIndex = active ? 0 : -1;
                    document.getElementById(`panel-${tab.dataset.tab}`).hidden = !active;
                    if (active && focus) tab.focus();
                });
                history.replaceState(null, '', `#${name}`);
                // Bila tab berada di bawah layar, gulir sedikit agar awal panel terlihat.
                const bar = document.querySelector('[role="tablist"]');
                if (bar.getBoundingClientRect().top > window.innerHeight * 0.5) {
                    bar.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
                onShow(name);
            }

            tabs.forEach((tab, i) => {
                tab.addEventListener('click', () => show(tab.dataset.tab));
                tab.addEventListener('keydown', (e) => {
                    if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                    const next = tabs[(i + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs
                        .length];
                    show(next.dataset.tab, {
                        focus: true
                    });
                });
            });

            const fromHash = location.hash.slice(1);
            // Id panel sengaja berbeda dari hash (panel-lacak vs #lacak) agar browser tidak melompat sendiri.
            if (tabs.some((tab) => tab.dataset.tab === fromHash)) {
                show(fromHash);
            }

            return {
                show,
                onShow: (fn) => {
                    onShow = fn;
                }
            };
        })();
        window.AspirasiTabs = AspirasiTabs;

        document.addEventListener('DOMContentLoaded', () => {
            const form = new AspirasiForm();
            // Peta Leaflet perlu diukur ulang setelah panelnya terlihat lagi.
            AspirasiTabs.onShow((name) => {
                if (name === 'kirim' && form.map) setTimeout(() => form.map.invalidateSize(), 50);
            });
        });
    </script>
@endpush
