@extends('frontend.layouts.dark', ['title' => 'Detail Kegiatan - MARIMOI'])

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- Tailwind CSS via Vite -->
    @vite(['resources/css/app.css'])
    <script src="https://js.hcaptcha.com/1/api.js?hl=id" async defer></script>
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
        li,
        td,
        th,
        input,
        textarea,
        select,
        label {
            font-family: 'Inter', sans-serif;
            color: #374151;
        }

        /* Custom styles for detail table */
        .detail-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #0a0f1e;
            width: 30%;
        }

        .detail-table td {
            color: #6b7280;
        }

        /* Image preview styles */
        .image-preview-container {
            position: relative;
        }

        .image-preview {
            max-height: 200px;
            object-fit: cover;
        }

        .image-placeholder {
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .placeholder-icon {
            font-size: 2rem;
            color: #9ca3af;
        }

        .mobile-image-preview {
            display: none;
            margin-top: 1rem;
        }

        .mobile-image-preview.show {
            display: block;
        }

        .mobile-image-preview img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 0.5rem;
        }

        @media (max-width: 767px) {
            .mobile-image-preview.show {
                display: block;
            }
        }

        /* Form Validation */
        .is-invalid {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }

        .is-valid {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
        }

        .invalid-feedback:not(.hidden) {
            display: block;
        }
    </style>
@endpush

@section('main')
    <!-- Modal Overlay -->
    <div id="modalOverlay"
        class="fixed inset-0 bg-black/60 flex justify-center items-center z-[9999] opacity-0 invisible transition-all duration-300 ease-out backdrop-blur-sm">
        <div
            class="bg-white p-8 rounded-2xl shadow-2xl text-center max-w-sm w-[90%] scale-90 translate-y-5 transition-transform duration-300 ease-out">
            <div id="modalIcon"
                class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center text-3xl bg-blue-100 text-blue-600">
                <div class="w-12 h-12 border-4 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
            </div>
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-800 mb-2">Memproses...</h3>
            <p id="modalMessage" class="text-gray-600 mb-4">Mohon tunggu sebentar</p>
            <div id="modalActions" class="hidden">
                <button id="modalCloseBtn"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Detail Section -->
    <section class="min-h-auto mt-[76px] pt-8 pb-12 bg-slate-50">
        <!-- Section Title -->
        <div class="container mx-auto px-4 text-center mb-8 max-w-7xl">
            <h2 class="text-2xl md:text-3xl font-bold text-slate-800 mb-2">Detail Peta</h2>
        </div>

        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Navigasi -->
            <div class="flex items-center space-x-2 mb-3">
                <a href="{{ url()->previous() }}"
                    class="inline-flex items-center px-2 py-1 md:px-4 md:py-2 bg-slate-100 border border-slate-300 rounded-lg shadow-sm text-sm font-medium text-slate-800 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </a>

                <span class="text-slate-400">/</span>
                <h4 class="text-sm md:text-md lg:text-lg font-medium text-slate-600">
                    {{ $project->kategori->deskripsi ?? $project->kategori->nama }}
                </h4>
            </div>

            @if (isset($project))
                <div class="grid grid-cols-1 {{ $project->gambar ? 'lg:grid-cols-2' : 'lg:grid-cols-1' }} gap-6 mb-8">
                    <!-- Detail Map -->
                    <div class="w-full">
                        <div class="bg-white shadow-xl rounded-xl overflow-hidden h-full">
                            <div class="p-2 md:p-6">
                                <div id="map-detail" class="w-full h-96 lg:h-[400px] rounded-lg z-[101]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Gambar jika ada -->
                    @if (!empty($project->gambar))
                        <div class="w-full">
                            <div class="bg-white shadow-xl rounded-xl overflow-hidden h-full">
                                <div class="p-2 md:p-6">
                                    <img src="{{ asset('storage/' . $project->gambar) }}" alt="{{ $project->judul }}"
                                        class="w-full h-96 lg:h-[400px] object-cover rounded-lg">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Deskripsi -->
                <div class="w-full mb-8">
                    <div class="bg-white shadow-xl rounded-xl overflow-hidden">
                        <div class="p-2 md:p-6">
                            <div class="overflow-x-auto">
                                <table class="detail-table w-full border-collapse">
                                    <tbody>
                                        <tr class="border-b border-slate-200">
                                            <th class="py-3 px-4 text-left font-semibold text-slate-700 bg-slate-50">
                                                KATEGORI</th>
                                            <td class="py-3 px-4 text-slate-600">{{ $project->kategori->nama }}</td>
                                        </tr>
                                        <tr class="border-b border-slate-200">
                                            <th class="py-3 px-4 text-left font-semibold text-slate-700 bg-slate-50">
                                                DESKRIPSI</th>
                                            <td class="py-3 px-4 text-slate-600">{{ $project->deskripsi }}</td>
                                        </tr>
                                        @if (isset($project->dbf_attributes))
                                            @php
                                                $dbfAttributes = is_string($project->dbf_attributes)
                                                    ? json_decode($project->dbf_attributes, true)
                                                    : $project->dbf_attributes;
                                            @endphp
                                            @foreach ($dbfAttributes as $key => $value)
                                                @if (strtolower($key) !== 'id')
                                                    <tr class="border-b border-slate-200">
                                                        <th
                                                            class="py-3 px-4 text-left font-semibold text-slate-700 bg-slate-50">
                                                            {{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                                        <td class="py-3 px-4 text-slate-600">{{ $value }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white shadow-xl rounded-xl p-8 text-center">
                    <div class="text-slate-600">
                        <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-lg">Data proyek tidak ditemukan.</p>
                    </div>
                </div>
            @endif
        </div>
    </section><!-- /Detail Section -->

    @if ($project->data_type != 'tematik')
        <!-- Form Feedback Section -->
        <section class="py-12 bg-white">
            <div class="container mx-auto px-4 max-w-6xl">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <div class="lg:col-span-7">
                        <div class="bg-slate-50 rounded-xl p-6 lg:p-8 h-full">
                            <h3 class="text-xl text-center md:text-2xl font-bold text-slate-800 mb-6">Formulir Tanggapan
                                Kegiatan</h3>
                            <div class="space-y-6">
                                <form id="feedbackForm" action="{{ route('feedback.store') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf

                                    <!-- Hidden fields sesuai contoh data -->
                                    <input type="hidden" name="data_spatial_id" value="{{ $project->id }}">
                                    <input type="hidden" name="nama_proyek"
                                        value="{{ $project->deskripsi ?? ($project->dbf_attributes['KEGIATAN'] ?? 'TANPA NAMA') }}">
                                    <input type="hidden" name="kabupaten_kota"
                                        value="{{ $project->dbf_attributes['KABUPATEN'] ?? ($project->dbf_attributes['KOTA'] ?? 'TANPA KABUPATEN/KOTA') }}">
                                    <input type="hidden" name="kecamatan"
                                        value="{{ $project->dbf_attributes['KECAMATAN'] ?? 'TANPA KECAMATAN' }}">
                                    <input type="hidden" name="latitude" id="latitude" value="">
                                    <input type="hidden" name="longitude" id="longitude" value="">

                                    <!-- Form fields yang bisa diisi user -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label for="nama_pemberi_aspirasi"
                                                class="block text-sm font-medium text-slate-700">
                                                Nama Lengkap <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="h-5 w-5 text-slate-400" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                                <input type="text"
                                                    class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                    id="nama_pemberi_aspirasi" name="nama_pemberi_aspirasi" required
                                                    placeholder="Contoh: Ahmad Salam">
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <label for="email" class="block text-sm font-medium text-slate-700">
                                                Email Aktif <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="h-5 w-5 text-slate-400" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                                    </svg>
                                                </div>
                                                <input type="email"
                                                    class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                    id="email" name="email"
                                                    placeholder="Contoh: ahmad.salam@email.com" required>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <label for="phone" class="block text-sm font-medium text-slate-700">
                                                No. WhatsApp <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="h-5 w-5 text-slate-400" fill="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path
                                                            d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm4.52 7.09l-4.25 2.25c-.81.43-1.8.43-2.61 0L5.45 9.09c-.35-.19-.48-.63-.29-.98.19-.35.63-.48.98-.29l4.25 2.25c.27.14.59.14.86 0l4.25-2.25c.35-.19.79-.06.98.29.19.35.06.79-.29.98z" />
                                                    </svg>
                                                </div>
                                                <input type="text"
                                                    class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                    id="phone" name="phone" placeholder="Contoh: 081234567890"
                                                    required>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <label for="jenis_tanggapan" class="block text-sm font-medium text-slate-700">
                                                Jenis Tanggapan <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="h-5 w-5 text-slate-400" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                </div>
                                                <select
                                                    class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                    id="jenis_tanggapan" name="jenis_tanggapan" required>
                                                    <option value="">-- Pilih Jenis --</option>
                                                    <option value="saran" selected>Saran</option>
                                                    <option value="keluhan">Pengaduan</option>
                                                    <option value="apresiasi">Apresiasi</option>
                                                    <option value="pertanyaan">Pertanyaan</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-2 mt-6">
                                        <label for="tanggapan" class="block text-sm font-medium text-slate-700">
                                            Tanggapan <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute top-3 left-0 pl-3 pointer-events-none">
                                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </div>
                                            <textarea
                                                class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                id="tanggapan" name="tanggapan" rows="4" required
                                                placeholder="Usulan Pokir ini sangat bagus untuk kemajuan desa kami. Mohon segera direalisasikan."></textarea>
                                        </div>
                                    </div>

                                    <div class="mt-6">
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                            <div class="lg:col-span-2 space-y-2">
                                                <label for="laporan_gambar"
                                                    class="block text-sm font-medium text-slate-700">
                                                    Lampiran Gambar
                                                    <span id="image_required" class="text-red-500"
                                                        style="display: none;">*</span>
                                                </label>
                                                <div class="relative">
                                                    <div
                                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="h-5 w-5 text-slate-400" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                        </svg>
                                                    </div>
                                                    <input type="file"
                                                        class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                        id="laporan_gambar" name="laporan_gambar"
                                                        accept="image/jpeg,image/png,image/jpg,image/gif">
                                                </div>
                                                <p class="text-xs text-slate-500">
                                                    Maksimal 5MB. Format: JPG, JPEG, PNG.
                                                    <span id="image_note" class="text-slate-500">Wajib untuk
                                                        pengaduan.</span>
                                                </p>
                                            </div>
                                            <div class="lg:col-span-1">
                                                <div id="image_preview_container" class="image-preview-container hidden">
                                                    <img id="image_preview" src="" alt="Preview"
                                                        class="w-full h-32 object-cover rounded-lg border image-preview">
                                                    <button type="button" id="remove_image"
                                                        class="mt-2 w-full px-3 py-2 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition-colors remove-btn">
                                                        <svg class="h-4 w-4 inline mr-1" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Hapus
                                                    </button>
                                                </div>
                                                <div id="image_placeholder"
                                                    class="image-placeholder text-center p-6 border-2 border-dashed border-slate-300 rounded-lg bg-slate-50">
                                                    <div class="text-slate-400">
                                                        <svg class="mx-auto h-8 w-8 placeholder-icon" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                        <div class="text-xs mt-1">Preview gambar</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Mobile Image Preview -->
                                        <div class="mobile-image-preview" id="mobileImagePreview">
                                            <img id="mobilePreviewImage" src="" alt="Mobile Preview">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 justify-center gap-6 mt-6">
                                        <!-- Captcha -->
                                        <div class="flex justify-center">
                                            <div class="h-captcha"
                                                data-sitekey="{{ config('services.hcaptcha.sitekey_test') }}">
                                            </div>
                                        </div>

                                        <!-- Tombol -->
                                        <div class="flex items-center justify-center">
                                            <button type="submit"
                                                class="w-full px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors"
                                                id="submitBtn">
                                                <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                </svg>
                                                <span id="submitText">Kirim Tanggapan</span>
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-5">
                        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 h-full">
                            <h3 class="text-lg text-center font-semibold text-slate-900 mb-6">Petunjuk Pengisian</h3>
                            <div class="space-y-6">
                                <div class="text-sm text-slate-600">
                                    <p class="text-justify leading-relaxed">
                                        Formulir ini digunakan untuk menyampaikan saran, pengaduan, apresiasi, atau
                                        pertanyaan terkait proyek.
                                    </p>
                                </div>

                                <div class="border-t border-slate-200 pt-4">
                                    <h4 class="font-semibold text-slate-900 mb-3">Langkah-langkah pengisian:</h4>
                                    <ol class="text-sm text-slate-600 space-y-1 list-decimal list-inside">
                                        <li>Isi nama lengkap.</li>
                                        <li>Masukkan email aktif.</li>
                                        <li>Isi nomor WhatsApp.</li>
                                        <li>Pilih jenis tanggapan.</li>
                                        <li>Tuliskan tanggapan secara jelas.</li>
                                        <li>Unggah gambar (wajib untuk pengaduan).</li>
                                    </ol>
                                </div>

                                <div class="border-t border-slate-200 pt-4">
                                    <h4 class="font-semibold text-slate-900 mb-3">Catatan:</h4>
                                    <ul class="text-sm text-slate-600 space-y-1 list-disc list-inside">
                                        <li>Gambar penting untuk memperjelas pengaduan.</li>
                                        <li>Email aktif dibutuhkan untuk tindak lanjut.</li>
                                        <li>Masukan Anda akan diproses dan ditindaklanjuti.</li>
                                        <li>Notifikasi akan dikirim lewat email atau WhatsApp.</li>
                                    </ul>
                                </div>

                                <div class="border-t border-slate-200 pt-4">
                                    <h4 class="font-semibold text-slate-900 mb-2">Privasi:</h4>
                                    <p class="text-sm text-slate-600">
                                        Data Anda aman dan hanya digunakan untuk penanganan masukan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Footer Section -->
    @include('frontend.partials.footer-dark')
@endsection

@push('scripts')
    <!-- Tailwind JS via Vite -->
    @vite(['resources/js/app.js'])
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize form elements
            const form = document.getElementById('feedbackForm');
            const jenisTanggapan = document.getElementById('jenis_tanggapan');
            const imageRequired = document.getElementById('image_required');
            const imageNote = document.getElementById('image_note');
            const laporanGambar = document.getElementById('laporan_gambar');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const imagePreview = document.getElementById('image_preview');
            const imagePreviewContainer = document.getElementById('image_preview_container');
            const imagePlaceholder = document.getElementById('image_placeholder');
            const removeImageBtn = document.getElementById('remove_image');
            const lat = document.getElementById('latitude');
            const long = document.getElementById('longitude');

            // Modal elements
            const modalOverlay = document.getElementById('modalOverlay');
            const modalIcon = document.getElementById('modalIcon');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const modalActions = document.getElementById('modalActions');
            const modalCloseBtn = document.getElementById('modalCloseBtn');

            // Initialize validation system
            setupValidation();

            // Modal handling
            modalCloseBtn.addEventListener('click', () => hideModal());
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) hideModal();
            });

            // File upload validation
            laporanGambar.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Check file size (2MB = 2 * 1024 * 1024 bytes)
                    if (file.size > 2 * 1024 * 1024) {
                        this.value = '';
                        validateField(this, false, 'Ukuran file maksimal 2MB');
                        resetCaptcha();
                        return;
                    }

                    // Check file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        this.value = '';
                        validateField(this, false, 'Format file tidak didukung');
                        resetCaptcha();
                        return;
                    }

                    validateField(this, true);
                    handleImagePreview(file);

                    // Clear any previous validation errors for keluhan
                    if (jenisTanggapan.value === 'keluhan') {
                        validateField(this, true, '');
                    }
                }
            });

            // Handle image preview
            function handleImagePreview(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const mobilePreview = document.getElementById('mobileImagePreview');
                    const mobilePreviewImage = document.getElementById('mobilePreviewImage');

                    // Check if mobile view
                    if (window.innerWidth <= 767) {
                        // Mobile: Show mobile preview only, hide desktop preview
                        mobilePreviewImage.src = e.target.result;
                        mobilePreview.classList.add('show');
                        imagePreviewContainer.style.display = 'none';
                        imagePlaceholder.style.display = 'none';
                    } else {
                        // Desktop: Show desktop preview only, hide mobile preview
                        imagePreview.src = e.target.result;
                        imagePreviewContainer.style.display = 'block';
                        imagePlaceholder.style.display = 'none';
                        mobilePreview.classList.remove('show');
                    }
                };
                reader.readAsDataURL(file);
            }

            // Handle remove image
            removeImageBtn.addEventListener('click', function() {
                laporanGambar.value = '';
                imagePreview.src = '';
                imagePreviewContainer.style.display = 'none';
                imagePlaceholder.style.display = 'flex';

                // Hide mobile preview
                const mobilePreview = document.getElementById('mobileImagePreview');
                const mobilePreviewImage = document.getElementById('mobilePreviewImage');
                mobilePreviewImage.src = '';
                mobilePreview.classList.remove('show');

                // Reset validation state and revalidate if keluhan
                laporanGambar.classList.remove('is-valid', 'is-invalid');
                if (jenisTanggapan.value === 'keluhan') {
                    validateField(laporanGambar, false, 'Gambar wajib untuk pengaduan');
                }
            });

            // Handle jenis tanggapan change
            jenisTanggapan.addEventListener('change', function() {
                if (this.value === 'keluhan') {
                    laporanGambar.setAttribute('required', 'required');
                    imageRequired.style.display = 'inline';
                    imageNote.textContent = 'Wajib untuk pengaduan.';
                    imageNote.className = 'text-red-600 font-medium';

                    // Validate if image is present
                    if (!laporanGambar.files[0]) {
                        validateField(laporanGambar, false, 'Gambar wajib untuk pengaduan');
                    }
                } else {
                    laporanGambar.removeAttribute('required');
                    imageRequired.style.display = 'none';
                    imageNote.textContent = 'Opsional.';
                    imageNote.className = 'text-slate-500';

                    // Clear any validation errors when not keluhan
                    laporanGambar.classList.remove('is-invalid');
                    const feedbackDiv = laporanGambar.closest('.space-y-2')?.querySelector(
                        '.invalid-feedback');
                    if (feedbackDiv) {
                        feedbackDiv.classList.add('hidden');
                    }
                }
                validateField(this, !!this.value, 'Pilih jenis tanggapan');
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!validateForm()) {
                    showModal('error', 'Data tidak lengkap',
                        'Mohon periksa kembali data yang Anda masukkan.');
                    return;
                }

                showModal('loading', 'Mengirim Tanggapan', 'Mohon tunggu sebentar...');

                // Get user's current location with improved accuracy
                if (navigator.geolocation) {
                    const options = {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    };

                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            // Set coordinates in hidden fields
                            lat.value = position.coords.latitude;
                            long.value = position.coords.longitude;

                            console.log('Location acquired:', {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                                accuracy: position.coords.accuracy
                            });

                            // Submit form
                            submitForm();
                        },
                        function(error) {
                            console.error('Error getting location:', error);
                            // Submit form without coordinates if location is not available
                            submitForm();
                        },
                        options
                    );
                } else {
                    // Submit form without coordinates if geolocation is not supported
                    submitForm();
                }
            });

            // Setup real-time validation
            function setupValidation() {
                const validationRules = {
                    nama_pemberi_aspirasi: {
                        test: (value) => value.length >= 3 && value.length <= 100,
                        message: 'Nama harus 3-100 karakter'
                    },
                    email: {
                        test: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
                        message: 'Format email tidak valid'
                    },
                    phone: {
                        test: (value) => /^(\+628|08)[0-9]{8,12}$/.test(value.replace(/\s/g, '')),
                        message: 'Format: 08xxxxxxxxxx atau +628xxxxxxxxxx'
                    },
                    tanggapan: {
                        test: (value) => value.length >= 10 && value.length <= 1000,
                        message: 'Tanggapan harus 10-1000 karakter'
                    },
                    jenis_tanggapan: {
                        test: (value) => value && ['saran', 'keluhan', 'apresiasi', 'pertanyaan'].includes(
                            value),
                        message: 'Pilih jenis tanggapan yang valid'
                    }
                };

                Object.keys(validationRules).forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) {
                        field.addEventListener('blur', () => {
                            const rule = validationRules[fieldName];
                            validateField(field, rule.test(field.value), rule.message);
                        });

                        field.addEventListener('input', () => {
                            if (field.classList.contains('is-invalid')) {
                                const rule = validationRules[fieldName];
                                if (rule.test(field.value)) {
                                    validateField(field, true);
                                }
                            }
                        });

                        // For select fields, also listen to change event
                        if (field.tagName === 'SELECT') {
                            field.addEventListener('change', () => {
                                const rule = validationRules[fieldName];
                                validateField(field, rule.test(field.value), rule.message);
                            });
                        }
                    }
                });

                // Additional specific validation for jenis tanggapan
                jenisTanggapan.addEventListener('change', () => {
                    const validValues = ['saran', 'keluhan', 'apresiasi', 'pertanyaan'];
                    validateField(jenisTanggapan, validValues.includes(jenisTanggapan.value),
                        'Pilih jenis tanggapan yang valid');
                });
            }

            // Validate individual field
            function validateField(field, isValid, message = '') {
                const fieldContainer = field.closest('.space-y-2') || field.parentElement;
                let feedbackDiv = fieldContainer.querySelector('.invalid-feedback');

                if (!feedbackDiv) {
                    feedbackDiv = document.createElement('div');
                    feedbackDiv.className = 'invalid-feedback text-red-600 text-sm mt-1 hidden';
                    fieldContainer.appendChild(feedbackDiv);
                }

                if (isValid) {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                    feedbackDiv.classList.add('hidden');
                    feedbackDiv.textContent = '';
                } else {
                    field.classList.remove('is-valid');
                    field.classList.add('is-invalid');
                    feedbackDiv.classList.remove('hidden');
                    feedbackDiv.textContent = message;
                }
                return isValid;
            }

            // Validate entire form
            function validateForm() {
                let isValid = true;

                // Define validation rules (same as in setupValidation)
                const validationRules = {
                    nama_pemberi_aspirasi: {
                        test: (value) => value.length >= 3 && value.length <= 100,
                        message: 'Nama harus 3-100 karakter'
                    },
                    email: {
                        test: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
                        message: 'Format email tidak valid'
                    },
                    phone: {
                        test: (value) => /^(\+628|08)[0-9]{8,12}$/.test(value.replace(/\s/g, '')),
                        message: 'Format: 08xxxxxxxxxx atau +628xxxxxxxxxx'
                    },
                    tanggapan: {
                        test: (value) => value.length >= 10 && value.length <= 1000,
                        message: 'Tanggapan harus 10-1000 karakter'
                    },
                    jenis_tanggapan: {
                        test: (value) => value && ['saran', 'keluhan', 'apresiasi', 'pertanyaan'].includes(
                            value),
                        message: 'Pilih jenis tanggapan yang valid'
                    }
                };

                // Validate required fields using specific rules
                Object.keys(validationRules).forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field && field.hasAttribute('required')) {
                        const rule = validationRules[fieldName];
                        if (!validateField(field, rule.test(field.value), rule.message)) {
                            isValid = false;
                        }
                    }
                });

                // Check other required fields that don't have specific rules
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    const fieldName = field.name || field.id;
                    if (!validationRules[fieldName]) {
                        if (field.type === 'checkbox') {
                            if (!field.checked) {
                                validateField(field, false, 'Field ini wajib dicentang');
                                isValid = false;
                            }
                        } else if (!field.value.trim()) {
                            validateField(field, false, 'Field ini wajib diisi');
                            isValid = false;
                        }
                    }
                });

                // Special validation for image requirement when jenis_tanggapan is 'keluhan'
                const jenisValue = jenisTanggapan.value;
                if (jenisValue === 'keluhan') {
                    const imageFile = laporanGambar.files[0];
                    if (!imageFile) {
                        validateField(laporanGambar, false, 'Gambar wajib untuk pengaduan');
                        isValid = false;
                    }
                }

                return isValid;
            }

            // Submit form function
            function submitForm() {
                submitBtn.disabled = true;
                submitText.textContent = 'Mengirim...';

                const formData = new FormData(form);

                // Ensure hCaptcha response is included in form data
                try {
                    const hcaptchaResponse = hcaptcha.getResponse();
                    if (hcaptchaResponse) {
                        formData.set('h-captcha-response', hcaptchaResponse);
                    }
                } catch (error) {
                    console.error('hCaptcha error:', error);
                }

                fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showModal('success', 'Tanggapan Berhasil Dikirim', data.message);
                            resetForm();
                        } else {
                            handleSubmitError(data);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showModal('error', 'Koneksi Bermasalah',
                            'Terjadi kesalahan koneksi. Silakan coba lagi.');
                        resetCaptcha();
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitText.textContent = 'Kirim Feedback';
                    });
            }

            // Handle submit errors
            function handleSubmitError(data) {
                // Always reset captcha when there are any validation errors
                // This prevents users from thinking captcha is valid when other fields fail
                if (data.errors) {
                    resetCaptcha();

                    if (data.errors['h-captcha-response']) {
                        showModal('error', 'Verifikasi Captcha Gagal', data.errors['h-captcha-response'][0]);
                    } else {
                        const firstError = Object.values(data.errors)[0];
                        showModal('error', 'Validasi Gagal', Array.isArray(firstError) ? firstError[0] :
                            firstError);
                    }
                } else {
                    showModal('error', 'Terjadi Kesalahan', data.message || 'Silakan coba lagi.');
                }
            }

            // Reset form
            function resetForm() {
                form.reset();

                // Clear validation states
                form.querySelectorAll('.form-control, input, select, textarea').forEach(field => {
                    field.classList.remove('is-valid', 'is-invalid');
                });

                // Reset image requirement
                imageRequired.style.display = 'none';
                imageNote.textContent = 'Opsional.';
                imageNote.className = 'text-slate-500';

                // Reset image preview
                imagePreview.src = '';
                imagePreviewContainer.style.display = 'none';
                imagePlaceholder.style.display = 'flex';

                // Reset mobile preview
                const mobilePreview = document.getElementById('mobileImagePreview');
                const mobilePreviewImage = document.getElementById('mobilePreviewImage');
                if (mobilePreview && mobilePreviewImage) {
                    mobilePreviewImage.src = '';
                    mobilePreview.classList.remove('show');
                }

                resetCaptcha();
            }

            // Reset captcha
            function resetCaptcha() {
                try {
                    if (typeof hcaptcha !== 'undefined') {
                        hcaptcha.reset();
                    }
                } catch (error) {
                    console.error('Captcha reset error:', error);
                }
            }

            // Modal functions
            function showModal(type, title, message, autoHide = false) {
                const iconMap = {
                    loading: {
                        icon: '<div class="w-12 h-12 border-4 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>',
                        bgClass: 'bg-blue-100',
                        textClass: 'text-blue-600'
                    },
                    success: {
                        icon: '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                        bgClass: 'bg-green-100',
                        textClass: 'text-green-600'
                    },
                    error: {
                        icon: '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
                        bgClass: 'bg-red-100',
                        textClass: 'text-red-600'
                    },
                    warning: {
                        icon: '<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L5.268 14.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
                        bgClass: 'bg-yellow-100',
                        textClass: 'text-yellow-600'
                    }
                };

                const config = iconMap[type] || iconMap.loading;

                // Reset icon classes and apply new ones
                modalIcon.className =
                    `w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center text-3xl ${config.bgClass} ${config.textClass}`;
                modalIcon.innerHTML = config.icon;
                modalTitle.textContent = title;
                modalMessage.textContent = message;

                if (type === 'loading') {
                    modalActions.classList.add('hidden');
                } else {
                    modalActions.classList.remove('hidden');
                }

                // Show modal with Tailwind classes
                modalOverlay.classList.remove('opacity-0', 'invisible');
                modalOverlay.classList.add('opacity-100', 'visible');

                // Animate modal content
                const modalContent = modalOverlay.querySelector('div:first-child');
                modalContent.classList.remove('scale-90', 'translate-y-5');
                modalContent.classList.add('scale-100', 'translate-y-0');

                if (autoHide) {
                    setTimeout(() => hideModal(), 3000);
                }
            }

            function hideModal() {
                // Hide modal with Tailwind classes
                modalOverlay.classList.remove('opacity-100', 'visible');
                modalOverlay.classList.add('opacity-0', 'invisible');

                // Animate modal content back
                const modalContent = modalOverlay.querySelector('div:first-child');
                modalContent.classList.remove('scale-100', 'translate-y-0');
                modalContent.classList.add('scale-90', 'translate-y-5');
            }

            // Map functionality
            @if (isset($project) && isset($project->geojson))
                // Initialize the map
                var map = L.map('map-detail').setView([0, 0], 13);

                // Add OpenStreetMap tile layer
                L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        id: "esri-world-imagery",
                        label: "ESRI World Imagery",
                        minZoom: 7,
                        maxZoom: 18,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);

                // Parse geometry JSON
                var geometry = {!! json_encode($project->geojson) !!};

                var layer;

                if (geometry.type === 'Point') {
                    var coords = [geometry.coordinates[1], geometry.coordinates[0]];
                    layer = L.marker(coords).addTo(map);
                    map.setView(coords, 16);
                } else if (geometry.type === 'LineString') {
                    var latlngs = geometry.coordinates.map(function(coord) {
                        return [coord[1], coord[0]];
                    });
                    layer = L.polyline(latlngs).addTo(map);
                    map.fitBounds(layer.getBounds());
                } else if (geometry.type === 'Polygon') {
                    var latlngs = geometry.coordinates[0].map(function(coord) {
                        return [coord[1], coord[0]];
                    });
                    layer = L.polygon(latlngs).addTo(map);
                    map.fitBounds(layer.getBounds());
                }

                // Add popup with feature title
                // if (layer) {
                //     layer.bindPopup("{{ $project->kategori->nama ?? 'Feature' }}").openPopup();
                // }
            @endif
        });
    </script>
@endpush
