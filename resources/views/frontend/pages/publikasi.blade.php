@extends('frontend.layouts.spatial', ['title' => 'Dokumen Publikasi - MARIMOI', 'heroTitle' => 'Dokumen Publikasi'])

@section('subtitle', 'Dokumen perencanaan dan publikasi resmi MARIMOI yang dapat diunduh.')

@php
    $search = trim((string) request('search'));
    $activeCategory = (string) request('category');
    $hasFilter = $search !== '' || $activeCategory !== '';
    $chip = 'inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition duration-300';
    $chipIdle = 'border-slate-900/10 bg-white text-slate-600 hover:border-ocean/40 hover:text-ocean';
    $chipActive = 'border-ocean bg-ocean text-white shadow-[0_8px_20px_-10px_rgba(10,132,255,.8)]';
    $input = 'block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-[15px] text-slate-900 placeholder:text-slate-400 transition focus:border-ocean focus:ring-4 focus:ring-ocean/15';
    $formatSize = function ($bytes) {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return 'N/A';
        }

        return $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', '.').' MB' : number_format($bytes / 1024, 0, ',', '.').' KB';
    };
@endphp

@section('main')
    <section class="bg-mist py-14 md:py-20">
        <div class="mx-auto w-full max-w-[1180px] px-6">
            {{-- Pencarian + kategori --}}
            <div class="reveal mb-10 grid gap-5 md:mb-14" data-reveal>
                <form method="GET" action="{{ route('tampil.publikasi') }}" class="flex flex-col gap-3 sm:flex-row" role="search">
                    @if ($activeCategory !== '')
                        <input type="hidden" name="category" value="{{ $activeCategory }}">
                    @endif
                    <div class="relative flex-1">
                        <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-4 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input type="search" name="search" value="{{ $search }}" placeholder="Cari judul atau deskripsi dokumen" aria-label="Cari dokumen" class="{{ $input }} pl-11">
                    </div>
                    <button type="submit" class="rounded-xl bg-ocean px-7 py-3 text-[15px] font-bold text-white shadow-[0_10px_30px_-12px_rgba(10,132,255,.8)] transition duration-300 hover:-translate-y-0.5">Cari</button>
                </form>

                @if ($categories->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-2" aria-label="Kategori dokumen">
                        <a href="{{ route('tampil.publikasi', array_filter(['search' => $search])) }}" class="{{ $chip }} {{ $activeCategory === '' ? $chipActive : $chipIdle }}">Semua</a>
                        @foreach ($categories as $category)
                            <a href="{{ route('tampil.publikasi', array_filter(['search' => $search, 'category' => $category])) }}"
                                class="{{ $chip }} {{ $activeCategory === $category ? $chipActive : $chipIdle }}">{{ $category }}</a>
                        @endforeach
                    </div>
                @endif

                <p class="font-grotesk text-xs uppercase tracking-widest text-slate-500">
                    {{ number_format($publications->total(), 0, ',', '.') }} dokumen
                    @if ($hasFilter)
                        <a href="{{ route('tampil.publikasi') }}" class="ml-3 text-ocean underline decoration-ocean/30 underline-offset-4 hover:decoration-ocean">Hapus filter</a>
                    @endif
                </p>
            </div>

            @if ($publications->isEmpty())
                <div class="reveal rounded-3xl border border-dashed border-slate-900/15 bg-white px-6 py-16 text-center" data-reveal>
                    <p class="text-xl font-bold text-navy">Dokumen tidak ditemukan</p>
                    <p class="mx-auto mt-2 max-w-md text-slate-600">Coba kata kunci lain atau tampilkan semua dokumen.</p>
                    <a href="{{ route('tampil.publikasi') }}" class="mt-6 inline-flex rounded-full bg-ocean px-6 py-3 text-sm font-bold text-white transition duration-300 hover:-translate-y-0.5">Lihat semua dokumen</a>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" id="publikasiList">
                    @foreach ($publications as $doc)
                        @php
                            $cover = $doc->cover ? asset('storage/'.$doc->cover) : null;
                            $attrs = 'data-id="'.$doc->id.'" data-path="'.e($doc->path).'" data-title="'.e($doc->title).'" data-thumbnail="'.e($cover ?? '').'"';
                        @endphp
                        <article class="group reveal flex flex-col overflow-hidden rounded-3xl border border-slate-900/10 bg-white transition duration-300 hover:-translate-y-2 hover:border-ocean/30 hover:shadow-[0_30px_60px_-30px_rgba(7,26,45,.35)]" data-reveal>
                            <button type="button" {!! $attrs !!} aria-label="Unduh {{ $doc->title }}"
                                class="open-download-modal relative block min-h-[14rem] w-full overflow-hidden bg-gradient-to-br from-navy to-deep">
                                <span class="absolute inset-0 grid place-items-center text-aqua/50" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" class="h-14 w-14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h6"/></svg>
                                </span>
                                @if ($cover)
                                    <img src="{{ $cover }}" alt="Sampul {{ $doc->title }}" loading="lazy" onerror="this.remove()"
                                        class="relative block h-auto w-full transition duration-700 group-hover:scale-[1.03]">
                                @endif
                                                                @if ($doc->category)
                                    <span class="absolute left-4 top-4 rounded-full bg-deep/75 px-3 py-1 font-grotesk text-[11px] uppercase tracking-widest text-white backdrop-blur-md">{{ $doc->category }}</span>
                                @endif
                                @if ($doc->file_type)
                                    <span class="absolute right-4 top-4 rounded-full bg-white/90 px-3 py-1 font-grotesk text-[11px] uppercase tracking-widest text-navy">{{ $doc->file_type }}</span>
                                @endif
                            </button>

                            <div class="flex flex-1 flex-col p-6">
                                <h3 class="text-lg font-bold leading-snug tracking-tight text-navy">
                                    <button type="button" {!! $attrs !!} class="open-download-modal text-left transition-colors hover:text-ocean">{{ $doc->title }}</button>
                                </h3>
                                @if ($doc->description)
                                    <p class="mt-2 line-clamp-3 text-[15px] leading-relaxed text-slate-600">{{ $doc->description }}</p>
                                @endif

                                <dl class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 font-grotesk text-xs text-slate-500">
                                    <div class="flex items-center gap-1.5"><dt class="sr-only">Ukuran</dt><dd>{{ $formatSize($doc->file_size) }}</dd></div>
                                    <div class="flex items-center gap-1.5"><dt class="sr-only">Diunduh</dt><dd>{{ number_format($doc->download_count ?? 0, 0, ',', '.') }}x diunduh</dd></div>
                                    @if ($doc->created_at)
                                        <div class="flex items-center gap-1.5"><dt class="sr-only">Tanggal</dt><dd>{{ $doc->created_at->locale('id')->translatedFormat('d M Y') }}</dd></div>
                                    @endif
                                </dl>

                                <div class="mt-auto pt-6">
                                <button type="button" {!! $attrs !!}
                                    class="open-download-modal inline-flex w-full items-center justify-center gap-2 rounded-xl bg-ocean px-5 py-3 text-sm font-bold text-white shadow-[0_10px_30px_-14px_rgba(10,132,255,.8)] transition duration-300 hover:bg-[#0a72e0] hover:shadow-[0_14px_38px_-12px_rgba(32,217,255,.7)]">
                                    <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg>
                                    Unduh Dokumen
                                </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($publications->hasPages())
                    <div class="mt-12">{{ $publications->withQueryString()->links() }}</div>
                @endif
            @endif
        </div>
    </section>

    {{-- Modal unduh --}}
    <div id="downloadModal" data-open="false" role="dialog" aria-modal="true" aria-labelledby="downloadTitle" aria-hidden="true"
        class="invisible fixed inset-0 z-[1200] grid place-items-center bg-slate-950/80 p-4 opacity-0 backdrop-blur-sm transition duration-300 data-[open=true]:visible data-[open=true]:opacity-100">
        <div class="grid max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white shadow-2xl lg:grid-cols-[2fr_3fr]">
            <div class="relative hidden flex-col items-center justify-center gap-4 overflow-hidden bg-gradient-to-br from-navy to-deep p-4 lg:flex">
                <svg class="contours pointer-events-none absolute inset-0 h-full w-full opacity-60 [&_path]:fill-none [&_path]:stroke-aqua/10 [&_path]:[vector-effect:non-scaling-stroke]" aria-hidden="true"></svg>
                <img id="previewImg" src="" alt="" class="relative hidden max-h-[calc(92vh-7rem)] w-full rounded-xl object-contain shadow-2xl" decoding="async" onerror="this.classList.add('hidden')">
                <p id="previewTitle" class="relative text-center text-sm font-semibold leading-snug text-white/85"></p>
            </div>

            <div class="p-6 md:p-8">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h3 id="downloadTitle" class="text-xl font-extrabold tracking-tight text-navy">Form Unduh Dokumen</h3>
                        <p class="mt-1 text-sm text-slate-500">Lengkapi data berikut untuk mengunduh dokumen.</p>
                    </div>
                    <button id="downloadModalClose" type="button" aria-label="Tutup" class="-mr-2 -mt-1 grid h-9 w-9 shrink-0 place-items-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                </div>

                <form id="downloadForm" method="post" action="#" class="space-y-4 text-sm" novalidate>
                    @csrf
                    <input type="hidden" name="doc_id" id="docIdInput">
                    <input type="hidden" name="doc_path" id="docPathInput">

                    <div id="formAlert" data-type="" role="alert"
                        class="hidden rounded-xl border px-4 py-3 text-sm data-[type=error]:block data-[type=error]:border-red-200 data-[type=error]:bg-red-50 data-[type=error]:text-red-700 data-[type=success]:block data-[type=success]:border-emerald-200 data-[type=success]:bg-emerald-50 data-[type=success]:text-emerald-800"></div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="dl-name" class="mb-1.5 block font-semibold text-navy">Nama lengkap <span class="text-red-500">*</span></label>
                            <input id="dl-name" required name="name" type="text" autocomplete="name" placeholder="Masukkan nama lengkap" class="{{ $input }}">
                        </div>
                        <div>
                            <label for="dl-email" class="mb-1.5 block font-semibold text-navy">Email <span class="text-red-500">*</span></label>
                            <input id="dl-email" required name="email" type="email" autocomplete="email" placeholder="contoh@email.com" class="{{ $input }}">
                        </div>
                        <div>
                            <label for="dl-phone" class="mb-1.5 block font-semibold text-navy">Nomor telepon</label>
                            <input id="dl-phone" name="phone" type="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" class="{{ $input }}">
                        </div>
                        <div>
                            <label for="dl-org" class="mb-1.5 block font-semibold text-navy">Organisasi/instansi</label>
                            <input id="dl-org" name="organization" type="text" autocomplete="organization" placeholder="Nama organisasi atau instansi" class="{{ $input }}">
                        </div>
                        <div class="md:col-span-2">
                            <label for="dl-position" class="mb-1.5 block font-semibold text-navy">Posisi/jabatan</label>
                            <input id="dl-position" name="position" type="text" autocomplete="organization-title" placeholder="Jabatan atau posisi Anda" class="{{ $input }}">
                        </div>
                        <div class="md:col-span-2">
                            <label for="dl-purpose" class="mb-1.5 block font-semibold text-navy">Tujuan penggunaan <span class="text-red-500">*</span></label>
                            <textarea id="dl-purpose" required name="purpose" rows="3" placeholder="Jelaskan tujuan penggunaan dokumen ini..." class="{{ $input }}"></textarea>
                        </div>
                    </div>

                    <div id="hcaptchaContainer" class="flex justify-center"></div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                        <button type="button" id="downloadCancel" class="rounded-xl border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 transition-colors hover:bg-slate-50">Batal</button>
                        <button type="submit" id="downloadSubmit" class="inline-flex items-center gap-2 rounded-xl bg-ocean px-6 py-2.5 font-bold text-white shadow-[0_10px_30px_-14px_rgba(10,132,255,.8)] transition duration-300 hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0">
                            <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg>
                            <span data-label>Kirim &amp; Unduh</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://js.hcaptcha.com/1/api.js?hl=id" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('downloadModal');
            const closeBtn = document.getElementById('downloadModalClose');
            const cancelBtn = document.getElementById('downloadCancel');
            const docIdInput = document.getElementById('docIdInput');
            const docPathInput = document.getElementById('docPathInput');
            const downloadForm = document.getElementById('downloadForm');
            const formAlert = document.getElementById('formAlert');
            const previewImg = document.getElementById('previewImg');
            const previewTitle = document.getElementById('previewTitle');
            const submitBtn = document.getElementById('downloadSubmit');
            const submitLabel = submitBtn.querySelector('[data-label]');
            let hcaptchaWidgetId = null;
            let lastTrigger = null;

            function renderHCaptcha() {
                if (typeof hcaptcha === 'undefined' || hcaptchaWidgetId !== null) return;
                try {
                    hcaptchaWidgetId = hcaptcha.render('hcaptchaContainer', {
                        sitekey: '{{ config('services.hcaptcha.sitekey') ?? env('HCAPTCHA_SITEKEY', '') }}'
                    });
                } catch (err) {
                    console.warn('hCaptcha render failed', err);
                }
            }

            function setFormAlert(message = '', type = 'error') {
                formAlert.textContent = message;
                formAlert.dataset.type = message ? type : '';
            }

            function resetCaptcha() {
                if (typeof hcaptcha !== 'undefined' && hcaptchaWidgetId !== null) {
                    try { hcaptcha.reset(hcaptchaWidgetId); } catch (e) {}
                }
            }

            function resetFormOnError() {
                resetCaptcha();
                const firstInvalid = downloadForm.querySelector(':invalid');
                if (firstInvalid && typeof firstInvalid.focus === 'function') firstInvalid.focus();
            }

            function openModal(docId, docPath, title, thumbnail, trigger) {
                lastTrigger = trigger;
                docIdInput.value = docId || '';
                docPathInput.value = docPath || '';
                if (docId) {
                    downloadForm.action = `{{ url('dokumen-publikasi') }}/${encodeURIComponent(docId)}/download`;
                }
                previewTitle.textContent = title || '';
                previewImg.classList.add('hidden');
                if (thumbnail) {
                    previewImg.alt = title || '';
                    previewImg.onload = () => previewImg.classList.remove('hidden');
                    previewImg.src = thumbnail;
                } else {
                    previewImg.removeAttribute('src');
                }
                setFormAlert();
                modal.dataset.open = 'true';
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                setTimeout(renderHCaptcha, 100);
                setTimeout(() => document.getElementById('dl-name').focus(), 150);
            }

            function closeModal() {
                modal.dataset.open = 'false';
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                downloadForm.reset();
                setFormAlert();
                resetCaptcha();
                if (lastTrigger) lastTrigger.focus();
            }

            document.querySelectorAll('.open-download-modal').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    openModal(btn.dataset.id, btn.dataset.path, btn.dataset.title || '', btn.dataset.thumbnail || '', btn);
                });
            });
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.dataset.open === 'true') closeModal(); });

            downloadForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const originalText = submitLabel.textContent;

                const formData = new FormData(downloadForm);
                if (!formData.get('name')?.trim() || !formData.get('email')?.trim() || !formData.get('purpose')?.trim()) {
                    setFormAlert('Nama, Email, dan Tujuan wajib diisi.', 'error');
                    resetFormOnError();
                    return;
                }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.get('email'))) {
                    setFormAlert('Format email tidak valid.', 'error');
                    resetFormOnError();
                    return;
                }
                if (typeof hcaptcha !== 'undefined' && hcaptchaWidgetId !== null) {
                    const token = hcaptcha.getResponse(hcaptchaWidgetId);
                    if (!token) {
                        setFormAlert('Silakan selesaikan verifikasi captcha.', 'error');
                        resetFormOnError();
                        return;
                    }
                    formData.append('h-captcha-response', token);
                }
                formData.append('additional_data', JSON.stringify({
                    user_agent: navigator.userAgent || '',
                    doc_id: docIdInput.value || '',
                    timestamp: new Date().toISOString()
                }));

                submitBtn.disabled = true;
                submitLabel.textContent = 'Memproses...';
                setFormAlert();

                try {
                    const response = await fetch(downloadForm.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json, application/octet-stream, */*'
                        },
                        body: formData
                    });
                    const contentType = response.headers.get('content-type') || '';

                    if (contentType.includes('application/json')) {
                        const data = await response.json();
                        if (response.ok) {
                            if (data.download_url) {
                                setFormAlert('Download akan dimulai...', 'success');
                                setTimeout(() => { window.open(data.download_url, '_blank'); closeModal(); }, 1000);
                            } else if (data.success) {
                                setFormAlert(data.message || 'Berhasil diproses.', 'success');
                                setTimeout(closeModal, 1500);
                            }
                        } else {
                            if (data.errors) {
                                setFormAlert(Object.values(data.errors).flat().join(' '), 'error');
                            } else {
                                setFormAlert(data.message || 'Gagal memproses permintaan.', 'error');
                            }
                            resetFormOnError();
                        }
                        return;
                    }

                    if (response.ok) {
                        const blob = await response.blob();
                        let filename = 'dokumen.pdf';
                        const disposition = response.headers.get('content-disposition');
                        if (disposition) {
                            const m = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                            if (m && m[1]) filename = m[1].replace(/['"]/g, '');
                        }
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        setFormAlert('Download berhasil!', 'success');
                        setTimeout(closeModal, 1500);
                        return;
                    }

                    const errorText = await response.text();
                    setFormAlert(errorText || `Error: ${response.status}`, 'error');
                    resetFormOnError();
                } catch (error) {
                    console.error('Download error:', error);
                    setFormAlert('Terjadi kesalahan jaringan. Silakan coba lagi.', 'error');
                    resetFormOnError();
                } finally {
                    submitBtn.disabled = false;
                    submitLabel.textContent = originalText;
                }
            });
        });
    </script>
@endpush
