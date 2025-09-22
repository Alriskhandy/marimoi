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

        /* Reduce input size and padding in download modal for more compact form */
        #downloadModal .p-6 input[type="text"],
        #downloadModal .p-6 input[type="email"],
        #downloadModal .p-6 input[type="tel"],
        #downloadModal .p-6 textarea,
        #downloadModal .p-6 select {
            font-size: 0.875rem;
            /* 14px */
            line-height: 1.25;
            padding: 0.5rem 0.625rem;
            /* 8px 10px */
            border-radius: 0.375rem;
        }

        /* Slightly smaller labels/icons to match compact inputs */
        #downloadModal .p-6 label {
            font-size: 0.875rem;
        }

        #downloadModal .p-6 .bi {
            font-size: 0.95rem;
            margin-right: 0.35rem;
        }

        /* Mobile: make inputs a bit tighter */
        @media (max-width: 640px) {
            #downloadModal .p-6 {
                padding: 0.75rem;
            }

            #downloadModal .p-6 input[type="text"],
            #downloadModal .p-6 input[type="email"],
            #downloadModal .p-6 input[type="tel"],
            #downloadModal .p-6 textarea {
                font-size: 0.825rem;
                padding: 0.45rem 0.5rem;
            }
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
                                data-id="{{ $doc->id }}" data-path="{{ $doc->path }}"
                                data-title="{{ htmlspecialchars($doc->title, ENT_QUOTES) }}"
                                data-thumbnail="{{ $doc->cover ?? $thumbFallback }}">
                                <img src="{{ $doc->cover ?? $thumbFallback }}"
                                    alt="{{ $doc->title }}" class="rounded-lg border">
                            </button>
                        </div>

                        <!-- Right: details -->
                        <div class="flex-1">
                            <h3 class="text-md font-semibold text-slate-800">
                                <button type="button"
                                    class="open-download-modal inline-block text-left p-0 leading-tight text-slate-800 hover:text-blue-600"
                                    data-id="{{ $doc->id }}" data-path="{{ $doc->path }}"
                                    data-title="{{ htmlspecialchars($doc->title, ENT_QUOTES) }}"
                                    data-thumbnail="{{ $doc->cover ?? $thumbFallback }}">{{ $doc->title }}</button>
                            </h3>
                            <div class="mt-2 flex items-center gap-4 text-sm text-slate-600">
                                <div class="flex items-center gap-1 text-slate-600">
                                    <i class="bi bi-download text-lg text-slate-700"></i>
                                    <span class="text-slate-600 font-medium">{{ $doc->download_count ?? 0 }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-slate-600">
                                    <i class="bi bi-file-earmark-binary text-lg text-slate-700"></i>
                                    <span
                                        class="text-slate-600 font-medium">{{ number_format($doc->file_size / 1024 / 1024, 2) . ' MB' ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-slate-600">
                                    <i class="bi bi-file-earmark-pdf text-lg text-slate-700"></i>
                                    <span class="text-slate-600 font-medium">{{ $doc->file_type ?? 'N/A' }}</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button data-id="{{ $doc->id }}" data-path="{{ $doc->path }}"
                                    data-title="{{ htmlspecialchars($doc->title, ENT_QUOTES) }}"
                                    data-thumbnail="{{ $doc->cover ?? $thumbFallback }}"
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
        <div
            class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto grid grid-cols-1 lg:grid-cols-2">
            <!-- Left: preview -->
            <div id="previewThumb" class="p-6 bg-slate-50 border-r hidden lg:flex items-center justify-center">
            </div>

            <!-- Right: form -->
            <div class="p-6">
                <div class="flex items-start justify-between text-gray-800 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">Form Unduh Dokumen</h3>
                        <p class="text-sm text-slate-500 mt-1">Lengkapi data untuk mengunduh dokumen</p>
                    </div>
                    <button id="downloadModalClose"
                        class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                </div>

                <form id="downloadForm" method="post" action="#" class="space-y-4 text-gray-800 text-sm">
                    @csrf
                    <input type="hidden" name="doc_id" id="docIdInput">
                    <input type="hidden" name="doc_path" id="docPathInput">

                    <div id="formAlert" class="hidden"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                <i class="bi bi-person me-2"></i>Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input required name="name" type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Masukkan nama lengkap" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                <i class="bi bi-envelope me-2"></i>Email <span class="text-red-500">*</span>
                            </label>
                            <input required name="email" type="email"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="contoh@email.com" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                <i class="bi bi-phone me-2"></i>Nomor Telepon
                            </label>
                            <input name="phone" type="tel"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="08xxxxxxxxxx" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                <i class="bi bi-building me-2"></i>Organisasi/Instansi
                            </label>
                            <input name="organization" type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Nama organisasi/instansi" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                <i class="bi bi-briefcase me-2"></i>Posisi/Jabatan
                            </label>
                            <input name="position" type="text"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Jabatan atau posisi Anda" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                <i class="bi bi-clipboard me-2"></i>Tujuan Penggunaan <span class="text-red-500">*</span>
                            </label>
                            <textarea required name="purpose" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Jelaskan tujuan penggunaan dokumen ini..."></textarea>
                        </div>
                    </div>

                    <!-- hCaptcha container -->
                    <div id="hcaptchaContainer" class="flex justify-center"></div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t">
                        <button type="button" id="downloadCancel"
                            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit" id="downloadSubmit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition disabled:opacity-50">
                            <i class="bi bi-download me-2"></i>Kirim & Unduh
                        </button>
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

            // hCaptcha render
            let hcaptchaWidgetId = null;

            function renderHCaptcha() {
                if (typeof hcaptcha === 'undefined') return;
                if (hcaptchaWidgetId !== null) return;

                try {
                    hcaptchaWidgetId = hcaptcha.render('hcaptchaContainer', {
                        sitekey: '{{ config('services.hcaptcha.sitekey') ?? env('HCAPTCHA_SITEKEY', '') }}'
                    });
                } catch (err) {
                    console.warn('hCaptcha render failed', err);
                }
            }

            const previewThumb = document.getElementById('previewThumb');
            const formAlert = document.getElementById('formAlert');

            function setFormAlert(message = '', type = 'error') {
                if (!formAlert) return;
                if (!message) {
                    formAlert.classList.add('hidden');
                    formAlert.textContent = '';
                    return;
                }
                formAlert.classList.remove('hidden');
                formAlert.textContent = message;
                formAlert.className = type === 'error' ?
                    'mb-4 text-sm text-red-600' :
                    'mb-4 text-sm text-green-600';
            }

            function openModal(docId, docPath, title = '', thumbnail = '') {
                // Set hidden inputs
                docIdInput.value = docId || '';
                docPathInput.value = docPath || '';

                // Set form action - perbaikan: gunakan route yang benar
                if (downloadForm && docId) {
                    downloadForm.action = `{{ url('dokumen-publikasi') }}/${encodeURIComponent(docId)}/download`;
                }

                // Set preview
                if (previewThumb && thumbnail) {
                    previewThumb.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full">
                        <div class="w-full">
                            <img src="${thumbnail}" alt="${title}" class="rounded-lg border object-cover w-full h-full"
                                loading="lazy" decoding="async">
                        </div>
                    </div>
                    `;
                }

                setFormAlert(); // Clear alerts
                modal.classList.remove('hidden');
                modal.classList.add('flex');

                // Render hCaptcha setelah modal terbuka
                setTimeout(renderHCaptcha, 100);
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');

                // Reset form
                downloadForm.reset();
                setFormAlert();

                // Reset hCaptcha
                if (typeof hcaptcha !== 'undefined' && hcaptchaWidgetId !== null) {
                    try {
                        hcaptcha.reset(hcaptchaWidgetId);
                    } catch (err) {
                        console.warn('hCaptcha reset failed', err);
                    }
                }
            }

            // Event listeners
            downloadButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = btn.getAttribute('data-id');
                    const path = btn.getAttribute('data-path');
                    const title = btn.getAttribute('data-title') || '';
                    const thumbnail = btn.getAttribute('data-thumbnail') || '';
                    openModal(id, path, title, thumbnail);
                });
            });

            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);

            // Close modal when clicking outside
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            // Submit handler - PERBAIKAN UTAMA
            downloadForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const submitBtn = document.getElementById('downloadSubmit');
                const originalText = submitBtn.textContent;

                // Validasi client-side
                const formData = new FormData(downloadForm);
                if (!formData.get('name')?.trim() || !formData.get('email')?.trim() || !formData.get(
                        'purpose')?.trim()) {
                    setFormAlert('Nama, Email, dan Tujuan wajib diisi.', 'error');
                    return;
                }

                // Validasi email format
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(formData.get('email'))) {
                    setFormAlert('Format email tidak valid.', 'error');
                    return;
                }

                // Include hCaptcha response
                if (typeof hcaptcha !== 'undefined' && hcaptchaWidgetId !== null) {
                    const token = hcaptcha.getResponse(hcaptchaWidgetId);
                    if (!token) {
                        setFormAlert('Silakan selesaikan verifikasi captcha.', 'error');
                        return;
                    }
                    formData.append('h-captcha-response', token);
                }

                // Additional data
                const additionalData = {
                    user_agent: navigator.userAgent || '',
                    doc_id: docIdInput.value || '',
                    timestamp: new Date().toISOString()
                };
                formData.append('additional_data', JSON.stringify(additionalData));

                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.textContent = 'Memproses...';
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

                    // Handle JSON response (errors atau redirect)
                    if (contentType.includes('application/json')) {
                        const data = await response.json();

                        if (response.ok) {
                            if (data.download_url) {
                                setFormAlert('Download akan dimulai...', 'success');
                                setTimeout(() => {
                                    window.open(data.download_url, '_blank');
                                    closeModal();
                                }, 1000);
                            } else if (data.success) {
                                setFormAlert(data.message || 'Berhasil diproses.', 'success');
                                setTimeout(closeModal, 1500);
                            }
                        } else {
                            // Handle validation errors
                            if (data.errors) {
                                const errorMessages = Object.values(data.errors).flat();
                                setFormAlert(errorMessages.join(' '), 'error');
                            } else {
                                setFormAlert(data.message || 'Gagal memproses permintaan.', 'error');
                            }
                        }
                        return;
                    }

                    // Handle file download response
                    if (response.ok) {
                        const blob = await response.blob();

                        // Extract filename from Content-Disposition header
                        let filename = 'dokumen.pdf';
                        const disposition = response.headers.get('content-disposition');
                        if (disposition) {
                            const filenameMatch = disposition.match(
                                /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                            if (filenameMatch && filenameMatch[1]) {
                                filename = filenameMatch[1].replace(/['"]/g, '');
                            }
                        }

                        // Create download link
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = filename;

                        document.body.appendChild(a);
                        a.click();

                        // Cleanup
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);

                        setFormAlert('Download berhasil!', 'success');
                        setTimeout(closeModal, 1500);
                        return;
                    }

                    // Handle error responses
                    const errorText = await response.text();
                    setFormAlert(errorText || `Error: ${response.status}`, 'error');

                } catch (error) {
                    console.error('Download error:', error);
                    setFormAlert('Terjadi kesalahan jaringan. Silakan coba lagi.', 'error');
                } finally {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        });
    </script>
@endpush
