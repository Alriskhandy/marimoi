@extends('frontend.layouts.spatial', ['title' => 'Detail Kegiatan - MARIMOI', 'heroTitle' => 'Detail Peta'])

@section('subtitle', $project->kategori->nama ?? 'Informasi lokasi dan atribut data.')

{{-- Gaya formulir disamakan dengan formulir Aspirasi Masyarakat (frontend/pages/aspirasi.blade.php). --}}
@php
    $input =
        'block w-full rounded-xl border-slate-300 bg-white px-3.5 py-2.5 text-[0.9375rem] text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-ocean focus:ring-2 focus:ring-ocean/20';
    $label = 'mb-1 block text-sm font-semibold text-slate-700';
    $feedback = 'invalid-feedback mt-1 hidden text-sm text-red-600';
    $legend = 'mb-3 flex items-center gap-2.5 text-base font-bold text-navy';
    $badge = 'grid h-6 w-6 shrink-0 place-items-center rounded-full bg-ocean/10 font-grotesk text-xs text-ocean';
    $btnPrimary =
        'inline-flex items-center justify-center gap-2 rounded-full bg-ocean px-7 py-3.5 text-[0.9375rem] font-bold text-white shadow-[0_10px_30px_-12px_rgba(10,132,255,.8)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_38px_-10px_rgba(32,217,255,.75)] disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0';
    $btnSoft =
        'inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-ocean hover:text-ocean';
    $jenisTanggapan = [
        ['saran', 'Saran', 'Masukan untuk perbaikan kegiatan.', 'bi-lightbulb-fill'],
        ['keluhan', 'Pengaduan', 'Laporkan masalah di lapangan. Wajib foto.', 'bi-exclamation-octagon-fill'],
        ['apresiasi', 'Apresiasi', 'Sampaikan penghargaan atas kegiatan ini.', 'bi-hand-thumbs-up-fill'],
        ['pertanyaan', 'Pertanyaan', 'Tanyakan hal yang belum jelas.', 'bi-question-circle-fill'],
    ];
    $panduan = [
        [
            'bi-chat-square-text',
            'Jenis tanggapan',
            [
                'Pilih saran, pengaduan, apresiasi, atau pertanyaan sesuai maksud Anda.',
                'Tanggapan langsung terhubung dengan data spasial di halaman ini.',
            ],
        ],
        [
            'bi-paperclip',
            'Lampiran foto',
            [
                'Wajib untuk pengaduan, opsional untuk jenis lain.',
                'Format JPG atau PNG, maksimal 5 MB.',
                'Foto kondisi lapangan membantu tim menindaklanjuti lebih cepat.',
            ],
        ],
        [
            'bi-envelope',
            'Tindak lanjut',
            [
                'Konfirmasi penerimaan dikirim ke email Anda.',
                'Tanggapan petugas dikirim lewat email saat diproses dan selesai.',
            ],
        ],
        [
            'bi-shield-lock',
            'Privasi data',
            [
                'Data dipakai untuk memproses dan menindaklanjuti tanggapan Anda.',
                'Data tidak dibagikan kepada pihak ketiga tanpa persetujuan.',
            ],
        ],
    ];
    $captchaRequired = \App\Rules\ValidHCaptcha::isRequired();
@endphp

@push('styles')
    @if ($captchaRequired)
        <script src="https://js.hcaptcha.com/1/api.js?hl=id" async defer></script>
    @endif
    <style>
        /* Status validasi di-toggle oleh skrip formulir. */
        #feedbackForm .is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .12);
        }

        #feedbackForm .is-valid {
            border-color: #10b981;
        }
    </style>
@endpush

@section('main')
    @include('frontend.partials.detail-peta')

    {{-- Formulir tanggapan --}}
    <section id="tanggapan" class="bg-mist pb-16 md:pb-20">
        <div class="mx-auto grid w-full max-w-[73.75rem] gap-6 px-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:gap-8">
            <div
                class="rounded-3xl border border-slate-900/10 bg-white p-5 shadow-[0_30px_60px_-40px_rgba(7,26,45,.35)] sm:p-7">
                <h2 class="mb-1 text-xl font-bold text-navy">Formulir Tanggapan</h2>
                <p class="mb-6 text-sm text-slate-500">Sampaikan tanggapan Anda tentang data ini. Kolom bertanda <span
                        class="text-red-500">*</span> wajib diisi.</p>

                <form id="feedbackForm" action="{{ route('feedback.store') }}" method="POST" enctype="multipart/form-data"
                    novalidate class="space-y-7">
                    @csrf
                    <input type="hidden" name="data_spatial_id" value="{{ $project->id }}">
                    <input type="hidden" name="nama_proyek"
                        value="{{ $project->deskripsi ?? ($project->dbf_attributes['KEGIATAN'] ?? 'TANPA NAMA') }}">
                    <input type="hidden" name="kabupaten_kota"
                        value="{{ $project->dbf_attributes['KABUPATEN'] ?? ($project->dbf_attributes['KOTA'] ?? 'TANPA KABUPATEN/KOTA') }}">
                    <input type="hidden" name="kecamatan" value="{{ $project->dbf_attributes['KECAMATAN'] ?? 'TANPA KECAMATAN' }}">
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">

                    {{-- 1. Jenis tanggapan --}}
                    <fieldset data-field>
                        <legend class="{{ $legend }}"><span class="{{ $badge }}">1</span> Jenis tanggapan <span
                                class="text-red-500">*</span></legend>
                        <div class="grid gap-2.5 sm:grid-cols-2">
                            @foreach ($jenisTanggapan as [$nilai, $nama, $keterangan, $ikon])
                                <label class="relative block cursor-pointer">
                                    <input type="radio" name="jenis_tanggapan" value="{{ $nilai }}"
                                        class="peer sr-only" @checked($loop->first) @if ($loop->first) required @endif>
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
                            <div data-field class="md:col-span-2">
                                <label for="nama_pemberi_aspirasi" class="{{ $label }}">Nama lengkap <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="nama_pemberi_aspirasi" id="nama_pemberi_aspirasi"
                                    class="{{ $input }}" placeholder="Nama sesuai identitas" autocomplete="name"
                                    required minlength="3" maxlength="100">
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
                                class="bi bi-info-circle mt-px"></i> Email dipakai untuk mengirim konfirmasi dan
                            tanggapan petugas.</p>
                    </fieldset>

                    {{-- 3. Isi tanggapan --}}
                    <fieldset>
                        <legend class="{{ $legend }}"><span class="{{ $badge }}">3</span> Isi tanggapan</legend>
                        <div data-field>
                            <label for="isi_tanggapan" class="{{ $label }}">Pesan <span
                                    class="text-red-500">*</span></label>
                            <textarea name="tanggapan" id="isi_tanggapan" rows="4" class="{{ $input }}" required minlength="10"
                                maxlength="1000" placeholder="Jelaskan kondisi, masukan, atau pertanyaan Anda selengkap mungkin."></textarea>
                            <div class="mt-1 flex items-start justify-between gap-4">
                                <p class="{{ $feedback }} !mt-0"></p>
                                <p class="ml-auto shrink-0 text-xs text-slate-500"><span id="charCount">0</span>/1000
                                    karakter</p>
                            </div>
                        </div>
                    </fieldset>

                    {{-- 4. Lampiran --}}
                    <fieldset data-field>
                        <legend class="{{ $legend }}"><span class="{{ $badge }}">4</span> Lampiran foto
                            <span id="lampiranWajib" class="hidden text-red-500">*</span>
                            <span id="lampiranOpsional" class="text-sm font-medium text-slate-400">(opsional)</span>
                        </legend>
                        <label for="laporan_gambar"
                            class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-3.5 transition hover:border-ocean hover:bg-ocean/[0.03]">
                            <i class="bi bi-cloud-arrow-up text-2xl text-ocean"></i>
                            <span><span class="block text-sm font-semibold text-navy">Pilih foto untuk diunggah</span>
                                <span class="block text-xs text-slate-500">JPG atau PNG · maksimal 5 MB</span></span>
                            <input type="file" name="laporan_gambar" id="laporan_gambar" class="sr-only"
                                accept=".jpg,.jpeg,.png">
                        </label>
                        <div id="fileInfo"
                            class="mt-2 hidden items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-sm text-emerald-700">
                            <img id="imagePreview" src="" alt="Pratinjau lampiran"
                                class="h-12 w-12 shrink-0 rounded-lg object-cover">
                            <span id="fileInfoText" class="min-w-0 flex-1 break-all"></span>
                            <button type="button" id="removeImageBtn"
                                class="shrink-0 font-semibold text-red-600 hover:underline">Hapus</button>
                        </div>
                        <p class="{{ $feedback }}"></p>
                    </fieldset>

                    {{-- 5. Kirim --}}
                    <fieldset>
                        <legend class="{{ $legend }}"><span class="{{ $badge }}">5</span> Kirim</legend>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            @if ($captchaRequired)
                                <div class="h-captcha" data-sitekey="{{ config('services.hcaptcha.sitekey_test') }}"></div>
                            @else
                                <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                    <i class="bi bi-cone-striped"></i> Mode development: hCaptcha dinonaktifkan.
                                </p>
                            @endif
                            <button type="submit" id="submitBtn" class="{{ $btnPrimary }} w-full sm:w-auto">
                                <i class="bi bi-send-fill"></i> <span id="submitText">Kirim Tanggapan</span>
                            </button>
                        </div>
                    </fieldset>
                </form>
            </div>

            {{-- Panduan singkat --}}
            <aside class="lg:sticky lg:top-[6.5rem] lg:self-start">
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
            <div id="modalActions" class="mt-6 hidden flex-col gap-3 sm:flex-row sm:justify-center">
                <button type="button" id="modalCloseBtn" class="{{ $btnPrimary }} !py-2.5">Tutup</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        /**
         * Formulir tanggapan: pola sama dengan AspirasiForm (aspirasi.blade.php) — validasi per kolom,
         * lampiran wajib untuk pengaduan, lokasi perangkat ikut dikirim bila diizinkan, kirim via fetch + hCaptcha.
         */
        class TanggapanForm {
            constructor() {
                const byId = (id) => document.getElementById(id);
                this.isSubmitting = false;
                this.el = {
                    form: byId('feedbackForm'),
                    jenisRadios: document.querySelectorAll('input[name="jenis_tanggapan"]'),
                    tanggapan: byId('isi_tanggapan'),
                    charCount: byId('charCount'),
                    lampiran: byId('laporan_gambar'),
                    lampiranWajib: byId('lampiranWajib'),
                    lampiranOpsional: byId('lampiranOpsional'),
                    fileInfo: byId('fileInfo'),
                    fileInfoText: byId('fileInfoText'),
                    imagePreview: byId('imagePreview'),
                    removeImageBtn: byId('removeImageBtn'),
                    latitude: byId('latitude'),
                    longitude: byId('longitude'),
                    submitBtn: byId('submitBtn'),
                    submitText: byId('submitText'),
                    modalOverlay: byId('modalOverlay'),
                    modalCard: byId('modalCard'),
                    modalIcon: byId('modalIcon'),
                    modalTitle: byId('modalTitle'),
                    modalMessage: byId('modalMessage'),
                    modalActions: byId('modalActions'),
                    modalCloseBtn: byId('modalCloseBtn'),
                };

                this.bindEvents();
                this.handleJenisChange();
            }

            // Aturan validasi tiap kolom (sama dengan aturan server di FrontendController::store).
            get rules() {
                return {
                    nama_pemberi_aspirasi: {
                        validate: (v) => v.length >= 3 && v.length <= 100,
                        message: 'Nama harus 3–100 karakter'
                    },
                    email: {
                        validate: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
                        message: 'Format email tidak valid'
                    },
                    phone: {
                        validate: (v) => /^(\+?62|0)[0-9]{9,13}$/.test(v),
                        message: 'Format: 08xxxxxxxxxx atau +628xxxxxxxxxx'
                    },
                    tanggapan: {
                        validate: (v) => v.length >= 10 && v.length <= 1000,
                        message: 'Pesan harus 10–1000 karakter'
                    },
                };
            }

            get jenis() {
                return this.el.form.elements.jenis_tanggapan.value;
            }

            bindEvents() {
                const el = this.el;
                el.form.addEventListener('submit', (e) => this.handleSubmit(e));
                el.jenisRadios.forEach((radio) => radio.addEventListener('change', () => this.handleJenisChange()));
                el.tanggapan.addEventListener('input', () => this.updateCharCount());
                el.lampiran.addEventListener('change', (e) => this.handleFileChange(e));
                el.removeImageBtn.addEventListener('click', () => this.clearFile());
                el.modalCloseBtn.addEventListener('click', () => this.hideModal());
                el.modalOverlay.addEventListener('click', (e) => {
                    if (e.target === el.modalOverlay) this.hideModal();
                });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !this.isSubmitting) this.hideModal();
                });

                Object.entries(this.rules).forEach(([name, rule]) => {
                    const field = this.el.form.elements[name];
                    // Kolom baru divalidasi setelah ditinggalkan, lalu diperbarui saat diketik.
                    field.addEventListener('blur', () => field.value && this.validateField(field, rule.validate(field
                        .value.trim()), rule.message));
                    field.addEventListener('input', () => {
                        if (field.classList.contains('is-invalid') || field.classList.contains('is-valid')) {
                            this.validateField(field, rule.validate(field.value.trim()), rule.message);
                        }
                    });
                });
            }

            validateField(field, isValid, message = '') {
                const feedback = field.closest('[data-field]')?.querySelector('.invalid-feedback');
                const isGroup = field.type === 'radio' || field.type === 'file';
                if (!isGroup) {
                    field.classList.toggle('is-invalid', !isValid);
                    field.classList.toggle('is-valid', isValid);
                    field.setAttribute('aria-invalid', String(!isValid));
                }
                if (feedback) {
                    feedback.textContent = isValid ? '' : message;
                    feedback.classList.toggle('hidden', isValid);
                }
                return isValid;
            }

            handleJenisChange() {
                const isPengaduan = this.jenis === 'keluhan';
                this.el.lampiran.toggleAttribute('required', isPengaduan);
                this.el.lampiranWajib.classList.toggle('hidden', !isPengaduan);
                this.el.lampiranOpsional.classList.toggle('hidden', isPengaduan);
                if (!isPengaduan) this.validateField(this.el.lampiran, true);
            }

            updateCharCount() {
                const length = this.el.tanggapan.value.length;
                this.el.charCount.textContent = length;
                this.el.charCount.className = length >= 1000 ? 'font-bold text-red-600' : length > 900 ?
                    'font-medium text-orange-600' : '';
            }

            handleFileChange(e) {
                const file = e.target.files[0];
                if (!file) {
                    this.clearFile();
                    return;
                }

                const extension = file.name.toLowerCase().slice(file.name.lastIndexOf('.'));
                if (!['.jpg', '.jpeg', '.png'].includes(extension)) {
                    this.clearFile();
                    this.validateField(e.target, false, 'Format foto tidak didukung. Gunakan JPG atau PNG.');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    this.clearFile();
                    this.validateField(e.target, false, 'Ukuran foto maksimal 5 MB.');
                    return;
                }

                this.validateField(e.target, true);
                this.el.fileInfoText.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                this.el.imagePreview.src = URL.createObjectURL(file);
                this.el.fileInfo.classList.remove('hidden');
                this.el.fileInfo.classList.add('flex');
            }

            clearFile() {
                this.el.lampiran.value = '';
                this.el.imagePreview.src = '';
                this.el.fileInfo.classList.add('hidden');
                this.el.fileInfo.classList.remove('flex');
            }

            /**
             * Periksa seluruh formulir sebelum dikirim; kolom pertama yang bermasalah difokuskan.
             * @returns {string|null} pesan kesalahan pertama, atau null bila semua valid
             */
            validateForm() {
                const problems = [];

                if (!this.jenis) {
                    this.validateField(this.el.jenisRadios[0], false, 'Pilih jenis tanggapan');
                    problems.push([this.el.jenisRadios[0], 'Pilih jenis tanggapan terlebih dahulu.']);
                }
                Object.entries(this.rules).forEach(([name, rule]) => {
                    const field = this.el.form.elements[name];
                    const value = field.value.trim();
                    if (!this.validateField(field, rule.validate(value), value ? rule.message : 'Kolom ini wajib diisi')) {
                        problems.push([field, 'Periksa kembali kolom yang ditandai merah.']);
                    }
                });
                if (this.jenis === 'keluhan' && !this.el.lampiran.files.length) {
                    this.validateField(this.el.lampiran, false, 'Foto wajib untuk pengaduan');
                    problems.push([this.el.lampiran.closest('[data-field]').querySelector('label'),
                        'Foto wajib disertakan untuk pengaduan.'
                    ]);
                }

                if (!problems.length) return null;
                const [target, message] = problems[0];
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
                    this.showModal('error', 'Verifikasi belum selesai', 'Selesaikan verifikasi captcha sebelum mengirim.');
                    return;
                }

                this.showModal('loading', 'Mengirim tanggapan', 'Mohon tunggu sebentar…');
                await this.attachDeviceLocation();
                this.submitForm();
            }

            // Lokasi perangkat ikut dikirim bila diizinkan (tidak wajib).
            attachDeviceLocation() {
                return new Promise((resolve) => {
                    if (!navigator.geolocation) {
                        resolve();
                        return;
                    }
                    navigator.geolocation.getCurrentPosition((position) => {
                        this.el.latitude.value = position.coords.latitude;
                        this.el.longitude.value = position.coords.longitude;
                        resolve();
                    }, () => resolve(), {
                        timeout: 10000,
                        maximumAge: 300000,
                        enableHighAccuracy: false
                    });
                });
            }

            async submitForm() {
                const el = this.el;
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
                        const ticket = data.data?.kode;
                        this.showModal('success', 'Tanggapan berhasil dikirim', ticket ?
                            `Nomor tiket Anda: ${ticket}. Salinannya beserta panduan melacak status telah dikirim ke email Anda.` :
                            (data.message || 'Terima kasih, tanggapan Anda sudah kami terima.'));
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
                    el.submitText.textContent = 'Kirim Tanggapan';
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
                        const field = this.el.form.elements[name];
                        const target = field instanceof RadioNodeList ? field[0] : field;
                        if (target) this.validateField(target, false, messages[0]);
                    });
                    const firstError = Object.values(data.errors)[0];
                    this.showModal('error', 'Data belum sesuai', Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    this.showModal('error', 'Terjadi kesalahan', data.message || 'Silakan coba lagi.');
                }
            }

            resetForm() {
                this.el.form.reset();
                this.el.form.querySelectorAll('input, textarea').forEach((field) => {
                    field.classList.remove('is-valid', 'is-invalid');
                    field.removeAttribute('aria-invalid');
                });
                this.el.form.querySelectorAll('.invalid-feedback').forEach((feedback) => feedback.classList.add('hidden'));
                this.clearFile();
                this.handleJenisChange();
                this.updateCharCount();
                this.resetCaptcha();
            }

            resetCaptcha() {
                try {
                    if (typeof hcaptcha !== 'undefined') hcaptcha.reset();
                } catch (error) {
                    // hCaptcha belum siap; tidak ada yang perlu direset.
                }
            }

            /**
             * @param {'loading'|'success'|'error'} type
             */
            showModal(type, title, message) {
                const el = this.el;
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
                el.modalActions.classList.toggle('hidden', type === 'loading');
                el.modalActions.classList.toggle('flex', type !== 'loading');

                el.modalOverlay.classList.remove('opacity-0', 'invisible');
                el.modalCard.classList.remove('scale-95', 'translate-y-5');
                if (type !== 'loading') el.modalCloseBtn.focus({
                    preventScroll: true
                });
            }

            hideModal() {
                this.el.modalOverlay.classList.add('opacity-0', 'invisible');
                this.el.modalCard.classList.add('scale-95', 'translate-y-5');
            }
        }

        document.addEventListener('DOMContentLoaded', () => new TanggapanForm());
    </script>
@endpush
