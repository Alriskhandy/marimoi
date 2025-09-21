@extends('frontend.layouts.dark', ['title' => 'Dokumen Publikasi - MARIMOI'])

@push('styles')
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <!-- Tailwind CSS via Vite -->
    @vite(['resources/css/app.css'])
    <style>
        /* Typography Fonts */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
        }

        p,
        body,
        ul,
        li {
            font-family: 'Inter', sans-serif;
        }

        .container {
            max-width: 1200px;
        }

        /* publikasi section white gradient overlay */
        .publikasi-section {
            position: relative;
            overflow: hidden;
        }

        /* Gradient: solid white at top until ~55%, then fade to transparent toward bottom */
        .publikasi-section::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background: linear-gradient(to bottom,
                    rgba(241, 245, 249, 0.90) 0%,
                    rgba(241, 245, 249, 1) 20%,
                    rgba(241, 245, 249, 1) 80%,
                    rgba(241, 245, 249, 0.90) 100%);
        }

        /* Ensure content appears above the overlay */
        .publikasi-section .z-above-overlay {
            position: relative;
            z-index: 10;
        }

        /* Aspect ratio helper for 156 x 220.5 px (height/width = 220.5/156 ~= 1.41346 => padding-top: 141.346%) */
        .ratio-156-220 {
            position: relative;
            width: 100%;
            padding-top: 141.3461538%;
            /* height as percentage of width */
            overflow: hidden;
            border-radius: 0.5rem;
            /* match rounded-lg */
        }

        .ratio-156-220 img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    </style>
@endpush

@section('main')
    <!-- Publikasi Section -->
    <section class="publikasi-section min-h-auto mt-[76px] pt-0 pb-8 bg-slate-100"
        style="background: url('{{ asset('frontend/img/cv/bg.svg') }}') repeat;">
        <!-- Section Title -->
        <div class="container mx-auto px-4 text-center mb-8 z-above-overlay">
            <h2 class="text-2xl md:text-3xl font-bold pt-8 text-slate-800 mb-4">
                Dokumen Publikasi
            </h2>
        </div>


        <div class="container mx-auto px-4 z-above-overlay">
            <!-- Publications Grid: 3 columns desktop, 2 columns tablet, 1 column mobile -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="publikasiList">

                @foreach ($publications as $doc)
                    <article class="bg-white rounded-2xl shadow-md p-4 flex items-center gap-4">
                        <!-- Left: thumbnail (fixed width) -->
                        <div class="w-1/3 lg:w-1/3 flex-shrink-0">
                            <button type="button" class="open-download-modal w-full p-0 block text-left ratio-156-220"
                                data-id="{{ $doc['id'] }}" data-path="{{ $doc['path'] }}"
                                data-title="{{ htmlspecialchars($doc['title'], ENT_QUOTES) }}"
                                data-thumbnail="{{ asset('frontend/img/publikasi/cover-publikasi-min.jpg') }}">
                                <img src="{{ asset('frontend/img/publikasi/cover-publikasi-min.jpg') }}" alt="{{ $doc['title'] }}" class="rounded-lg border">
                            </button>
                        </div>
                        
                        <!-- Right: details -->
                        <div class="flex-1">
                            <h3 class="text-md font-semibold text-slate-800">
                                <button type="button"
                                    class="open-download-modal inline-block text-left p-0 leading-tight text-slate-800 hover:text-blue-600"
                                    data-id="{{ $doc['id'] }}" data-path="{{ $doc['path'] }}"
                                    data-title="{{ htmlspecialchars($doc['title'], ENT_QUOTES) }}"
                                    data-thumbnail="{{ $doc['thumbnail'] }}">{{ $doc['title'] }}</button>
                            </h3>
                            <div class="mt-2 flex items-center gap-4 text-sm text-slate-600">
                                {{-- <div class="flex items-center gap-2">
                                    <i class="bi bi-file-earmark-arrow-down text-lg text-slate-700"></i>
                                    <span class="text-slate-600 font-medium">{{ $doc->created_at }}</span>
                                </div> --}}
                                <div class="flex items-center gap-1 text-slate-600">
                                    <i class="bi bi-download text-lg text-slate-700"></i>
                                    <span class="text-slate-600 font-medium">{{ $doc->download_count ?? 0 }}</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button data-id="{{ $doc['id'] }}" data-path="{{ $doc['path'] }}"
                                    data-title="{{ htmlspecialchars($doc['title'], ENT_QUOTES) }}"
                                    data-thumbnail="{{ $doc['thumbnail'] }}"
                                    class="open-download-modal inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                                    <i class="bi bi-download"></i> Unduh Dokumen
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

    </section><!-- Publikasi Section -->

    <!-- Download Modal (improved) -->
    <div id="downloadModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl overflow-hidden grid grid-cols-1 lg:grid-cols-2">
            <!-- Left: preview -->
            <div id="previewThumb" class="p-4 bg-slate-50 border-r hidden lg:block text-gray-800">
            </div>

            <!-- Right: form -->
            <div class="p-6">
                <div class="flex items-start justify-between text-gray-800">
                    <h3 class="text-lg font-semibold">Form Unduh Dokumen</h3>
                    <button id="downloadModalClose" class="text-slate-500 hover:text-slate-700">×</button>
                </div>

                <form id="downloadForm" method="post" action="{{ route('download.publikasi', $doc['id']) }}" class="mt-4 text-gray-800 text-sm">
                    @csrf
                    <input type="hidden" name="doc_id" id="docIdInput" value="{{ $doc['id'] }}">
                    <input type="hidden" name="doc_path" id="docPathInput" value="{{ $doc['id'] }}">

                    <div class="mb-2">
                        <h4 class="text-lg font-semibold">Data Pemohon</h4>
                        <p class="text-sm text-slate-500">Isi data Anda untuk proses unduhan</p>
                    </div>
                    <div id="formAlert" class="hidden mb-4 text-sm"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700"><i class="bi bi-person me-2"></i>
                                Nama</label>
                            <input required name="name" type="text"
                                class="form-control mt-1 block w-full rounded-md border-gray-200" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700"><i class="bi bi-envelope me-2"></i>
                                Email</label>
                            <input required name="email" type="email"
                                class="form-control mt-1 block w-full rounded-md border-gray-200" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700"><i class="bi bi-phone me-2"></i>
                                Nomor Telepon</label>
                            <input name="phone" type="tel" placeholder="08xxxxxxxxxx"
                                class="form-control mt-1 block w-full rounded-md border-gray-200" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700"><i class="bi bi-building me-2"></i>
                                Organisasi/Instansi</label>
                            <input name="organization" type="text"
                                class="form-control mt-1 block w-full rounded-md border-gray-200" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700"><i class="bi bi-briefcase me-2"></i>
                                Posisi/Jabatan</label>
                            <input name="position" type="text"
                                class="form-control mt-1 block w-full rounded-md border-gray-200" />
                        </div>

                        <input type="hidden" name="additional_data" id="additionalDataInput" value="" />

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700"><i class="bi bi-clipboard me-2"></i>
                                Tujuan Penggunaan</label>
                            <textarea required name="purpose" rows="3" class="form-control mt-1 block w-full rounded-md border-gray-200"></textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <!-- hCaptcha widget placeholder -->
                        <div id="hcaptchaContainer"></div>
                    </div>

                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button type="button" id="downloadCancel"
                            class="px-4 py-2 rounded-lg border border-slate-200">Batal</button>
                        <button type="submit" id="downloadSubmit"
                            class="px-4 py-2 rounded-lg bg-blue-600 text-white">Kirim &
                            Unduh</button>
                    </div>
            </div>
            </form>
        </div>
    </div>
    </div>

    <!-- Footer Section -->
    @include('frontend.partials.footer-dark-tailwind')
@endsection

@push('scripts')
    <!-- Vite JavaScript -->
    @vite(['resources/js/app.js'])
    <!-- hCaptcha (load only when publikasi page is used) -->
    <script src="https://js.hcaptcha.com/1/api.js?hl=id" async defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('downloadModal');
            const closeBtn = document.getElementById('downloadModalClose');
            const cancelBtn = document.getElementById('downloadCancel');
            const docIdInput = document.getElementById('docIdInput');
            const docPathInput = document.getElementById('docPathInput');
            const downloadButtons = document.querySelectorAll('.open-download-modal');
            const downloadForm = document.getElementById('downloadForm');

            // hCaptcha render - attempt to render when modal is opened
            let hcaptchaWidgetId = null;

            function renderHCaptcha() {
                if (typeof hcaptcha === 'undefined') return;
                if (hcaptchaWidgetId !== null) return; // already rendered

                try {
                    hcaptchaWidgetId = hcaptcha.render('hcaptchaContainer', {
                        sitekey: '{{ config('services.hcaptcha.sitekey') ?? (env('HCAPTCHA_SITEKEY') ?? '') }}'
                    });
                } catch (err) {
                    console.warn('hCaptcha render failed', err);
                }
            }

            const previewThumb = document.getElementById('previewThumb');
            const previewTitle = document.getElementById('previewTitle');
            const previewMeta = document.getElementById('previewMeta');
            const formAlert = document.getElementById('formAlert');

            function setFormAlert(message = '', type = 'error') {
                if (!formAlert) return;
                if (!message) {
                    formAlert.classList.add('hidden');
                    formAlert.textContent = '';
                    formAlert.className = 'hidden mb-4 text-sm';
                    return;
                }
                formAlert.classList.remove('hidden');
                formAlert.textContent = message;
                if (type === 'error') {
                    formAlert.className = 'mb-4 text-sm text-red-600';
                } else {
                    formAlert.className = 'mb-4 text-sm text-green-600';
                }
            }

            function openModal(docId, docPath, title = '', thumbnail = '') {
                docIdInput.value = docId;
                docPathInput.value = docPath;

                // set preview
                if (previewThumb && thumbnail) {
                    previewThumb.innerHTML =
                        `<img src="${thumbnail}" alt="${title}" class="rounded-lg" style="width:100%;height:100%;object-fit:cover;display:block;">`;
                } else if (previewThumb) {
                    previewThumb.innerHTML = '';
                }

                if (previewTitle) previewTitle.textContent = title || '-';
                if (previewMeta) previewMeta.textContent = docPath ? 'Dokumen siap diunduh' : '';

                setFormAlert();

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                renderHCaptcha();
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            downloadButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const id = btn.getAttribute('data-id');
                    const path = btn.getAttribute('data-path');
                    const title = btn.getAttribute('data-title') || '';
                    const thumbnail = btn.getAttribute('data-thumbnail') || '';
                    openModal(id, path, title, thumbnail);
                });
            });

            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);

            // Submit handler: send via fetch to server which should validate and then respond with the download URL
            downloadForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                // populate additional_data with small client context
                const additionalDataInput = document.getElementById('additionalDataInput');
                if (additionalDataInput) {
                    const ctx = {
                        user_agent: navigator.userAgent || '',
                        doc_id: docIdInput ? docIdInput.value : ''
                    };
                    additionalDataInput.value = JSON.stringify(ctx);
                }

                const formData = new FormData(downloadForm);

                // Include hcaptcha response if available
                if (typeof hcaptcha !== 'undefined' && hcaptchaWidgetId !== null) {
                    const token = hcaptcha.getResponse(hcaptchaWidgetId);
                    formData.append('h-captcha-response', token);
                }

                // Basic client-side check (name, email, purpose are required server-side)
                if (!formData.get('name') || !formData.get('email') || !formData.get('purpose')) {
                    setFormAlert('Lengkapi semua field yang diperlukan (Nama, Email, Tujuan).',
                    'error');
                    return;
                }

                const submitBtn = document.getElementById('downloadSubmit');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Mengirim...';

                try {
                    const res = await fetch(downloadForm.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await res.json();

                    if (res.ok && data.download_url) {
                        // close modal then redirect to download URL
                        closeModal();
                        // small delay so modal hides smoothly
                        setTimeout(() => {
                            window.location.href = data.download_url;
                        }, 250);
                    } else if (res.ok && data.success) {
                        // If server returns success without download_url, show message and close
                        setFormAlert(data.message || 'Permintaan diproses.', 'success');
                        setTimeout(() => closeModal(), 1200);
                    } else {
                        setFormAlert(data.message || 'Gagal memproses permintaan.', 'error');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Kirim & Unduh';
                    }
                } catch (err) {
                    console.error(err);
                    setFormAlert('Terjadi kesalahan, coba lagi.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Kirim & Unduh';
                }
            });
        });
    </script>
@endpush
