@extends('frontend.layouts.dark', ['title' => 'Usulan Aspirasi'])

@push('styles')
    <!-- Tailwind CSS via Vite -->
    @vite(['resources/css/app.css'])
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
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

        /* Custom animations and transitions */
        .answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out, padding 0.3s ease-in-out;
            padding-top: 0;
        }

        .tab input[type="radio"]:checked~.answer {
            padding-top: 1rem;
        }

        /* Specific max-heights for each accordion item for smooth animations */
        #acc1:checked~.answer {
            max-height: 400px;
        }

        #acc2:checked~.answer {
            max-height: 500px;
        }

        #acc3:checked~.answer {
            max-height: 400px;
        }

        #acc4:checked~.answer {
            max-height: 300px;
        }

        #acc5:checked~.answer {
            max-height: 400px;
        }

        .tab label::after {
            transition: transform 0.3s ease-in-out;
        }

        .tab input[type="radio"]:checked~label::after {
            transform: rotate(45deg);
        }

        /* Style for checked/active accordion labels */
        .tab input[type="radio"]:checked~label {
            background: linear-gradient(to bottom right, #2563eb, #1e40af) !important;
            color: white !important;
        }

        .tab input[type="radio"]:checked~label h4 {
            color: white !important;
        }

        .tab input[type="radio"]:checked~label::after {
            color: white !important;
        }

        .tab input[type="radio"]:checked~label i {
            color: white !important;
        }

        /* Default label styling */
        .tab label {
            color: #374151 !important;
            background-color: transparent;
        }

        .tab label h4 {
            color: #374151 !important;
            margin: 0;
            font-weight: 600;
        }

        .tab label i {
            color: #374151 !important;
        }

        /* Smooth hover effects */
        .tab:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Map specific styles */
        #map {
            height: 350px;
            z-index: 99;
            width: 100%;
            border-radius: 8px;
        }

        /* Ensure proper container max-width */
        .container {
            max-width: 1200px;
        }

        /* Loading overlay styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Form validation styles */
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

        .container {
            color: #0a0f1e;
        }
    </style>
@endpush

@section('main')
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="bg-white p-6 rounded-lg shadow-lg text-center">
            <div class="loading-spinner mx-auto mb-4"></div>
            <p class="text-gray-700">Mengirim aspirasi...</p>
        </div>
    </div>

    <!-- Usulan Section -->
    <section class="min-h-screen mt-[76px] pt-8 pb-8 bg-slate-50">
        <!-- Section Title -->
        <div class="container mx-auto px-4 text-center mb-6" data-aos="fade-up">
            <h2 class="text-2xl md:text-3xl font-bold text-slate-800">Usulan Aspirasi Masyarakat</h2>
        </div>

        <div class="container mx-auto px-4" data-aos="fade-up" data-aos-delay="100">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Instruction Card -->
                <div class="lg:col-span-5">
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 h-full">
                        <h3 class="text-lg text-center font-semibold mb-4 text-slate-800">Petunjuk Pengisian</h3>
                        <p class="text-justify mb-4 text-slate-600">Formulir ini digunakan untuk menyampaikan usulan
                            pembangunan atau kritik & saran terkait layanan sistem.</p>

                        <!-- Accordion -->
                        <div class="wrapper w-full" id="instructionAccordion">
                            <!-- Jenis Aspirasi -->
                            <div
                                class="tab mb-4 px-3 py-3 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                                <input type="radio" name="accordion" id="acc1" class="hidden peer">
                                <label for="acc1"
                                    class="flex items-center text-sm md:text-base font-semibold cursor-pointer py-2 px-3 rounded-md
                                       after:absolute after:content-['+'] after:right-6 after:text-2xl 
                                       after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                                       after:transition-transform after:duration-300"
                                    tabindex="0">
                                    <h4><i class="bi bi-info-circle me-2"></i> Jenis Aspirasi</h4>
                                </label>
                                <div
                                    class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                                    <div class="text-gray-700 text-sm leading-relaxed">
                                        <p class="mb-2">Ada 2 jenis aspirasi yang dapat disampaikan:</p>
                                        <ul class="list-disc list-inside space-y-1 pl-4">
                                            <li><strong>Usulan Pembangunan</strong> - Untuk mengusulkan proyek pembangunan
                                                baru dengan lokasi spesifik</li>
                                            <li><strong>Kritik & Saran</strong> - Untuk memberikan masukan umum tanpa perlu
                                                menentukan lokasi</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Langkah Pengisian -->
                            <div
                                class="tab mb-4 px-3 py-3 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                                <input type="radio" name="accordion" id="acc2" class="hidden peer">
                                <label for="acc2"
                                    class="flex items-center text-sm md:text-base font-semibold cursor-pointer py-2 px-3 rounded-md
                                       after:absolute after:content-['+'] after:right-6 after:text-2xl 
                                       after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                                       after:transition-transform after:duration-300"
                                    tabindex="0">
                                    <h4><i class="bi bi-list-ol me-2"></i> Langkah Pengisian</h4>
                                </label>
                                <div
                                    class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                                    <div class="text-gray-700 text-sm leading-relaxed">
                                        <ol class="list-decimal list-inside space-y-1 pl-4">
                                            <li>Isi data diri Anda (nama, alamat, email, dan nomor WhatsApp)</li>
                                            <li>Pilih jenis aspirasi (Usulan atau Kritik & Saran)</li>
                                            <li>Untuk Usulan: pilih kategori dan tentukan lokasi pada peta</li>
                                            <li>Isi judul dan pesan aspirasi secara jelas</li>
                                            <li>Lampirkan file pendukung jika diperlukan</li>
                                            <li>Centang persetujuan dan selesaikan captcha</li>
                                            <li>Klik tombol "Kirim Aspirasi"</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>

                            <!-- Lokasi Usulan -->
                            <div
                                class="tab mb-4 px-3 py-3 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                                <input type="radio" name="accordion" id="acc3" class="hidden peer">
                                <label for="acc3"
                                    class="flex items-center text-sm md:text-base font-semibold cursor-pointer py-2 px-3 rounded-md
                                       after:absolute after:content-['+'] after:right-6 after:text-2xl 
                                       after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                                       after:transition-transform after:duration-300"
                                    tabindex="0">
                                    <h4><i class="bi bi-geo-alt me-2"></i> Lokasi Usulan</h4>
                                </label>
                                <div
                                    class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                                    <div class="text-gray-700 text-sm leading-relaxed">
                                        <p class="mb-2">Untuk jenis aspirasi "Usulan Pembangunan", Anda perlu menentukan
                                            lokasi:</p>
                                        <ul class="list-disc list-inside space-y-1 pl-4">
                                            <li>Klik tombol "Gunakan Lokasi Saat Ini" untuk menggunakan lokasi Anda sekarang
                                            </li>
                                            <li>Atau klik langsung pada peta untuk memilih lokasi yang diinginkan</li>
                                            <li>Lokasi yang dipilih akan ditandai dengan pin pada peta</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Lampiran -->
                            <div
                                class="tab mb-4 px-3 py-3 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                                <input type="radio" name="accordion" id="acc4" class="hidden peer">
                                <label for="acc4"
                                    class="flex items-center text-sm md:text-base font-semibold cursor-pointer py-2 px-3 rounded-md
                                       after:absolute after:content-['+'] after:right-6 after:text-2xl 
                                       after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                                       after:transition-transform after:duration-300"
                                    tabindex="0">
                                    <h4><i class="bi bi-paperclip me-2"></i> Lampiran</h4>
                                </label>
                                <div
                                    class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                                    <div class="text-gray-700 text-sm leading-relaxed">
                                        <p class="mb-2">Anda dapat melampirkan file pendukung:</p>
                                        <ul class="list-disc list-inside space-y-1 pl-4">
                                            <li>Format yang didukung: gambar (JPG, PNG, GIF), PDF, DWG, DXF</li>
                                            <li>Ukuran maksimal file: 5MB</li>
                                            <li>Lampiran dapat berupa foto lokasi, sketsa, atau dokumen pendukung lainnya
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Privasi -->
                            <div
                                class="tab mb-4 px-3 py-3 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                                <input type="radio" name="accordion" id="acc5" class="hidden peer">
                                <label for="acc5"
                                    class="flex items-center text-sm md:text-base font-semibold cursor-pointer py-2 px-3 rounded-md
                                       after:absolute after:content-['+'] after:right-6 after:text-2xl 
                                       after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                                       after:transition-transform after:duration-300"
                                    tabindex="0">
                                    <h4><i class="bi bi-shield-lock me-2"></i> Privasi Data</h4>
                                </label>
                                <div
                                    class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                                    <div class="text-gray-700 text-sm leading-relaxed">
                                        <p class="mb-2">Data yang Anda berikan akan digunakan untuk:</p>
                                        <ul class="list-disc list-inside space-y-1 pl-4">
                                            <li>Memproses aspirasi yang Anda sampaikan</li>
                                            <li>Menghubungi Anda terkait tindak lanjut aspirasi</li>
                                            <li>Data Anda tidak akan dibagikan kepada pihak ketiga tanpa persetujuan</li>
                                            <li>Aspirasi yang disampaikan akan ditinjau oleh tim terkait</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="lg:col-span-7">
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 h-full">
                        <h3 class="text-lg text-center font-semibold mb-6 text-slate-800">Formulir Usulan Aspirasi</h3>

                        <form action="/aspirasi-masyarakat" method="post" enctype="multipart/form-data" id="formUsulan"
                            novalidate>
                            @csrf
                            <div class="space-y-2">

                                <!-- Personal Information Row -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Nama Lengkap -->
                                    <div class="space-y-2">
                                        <label for="nama_pengirim" class="block text-sm font-medium text-slate-700">
                                            Nama Lengkap <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <input type="text" name="nama_pengirim" id="nama_pengirim"
                                                class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                placeholder="Nama Lengkap Anda" required minlength="3" maxlength="100">
                                        </div>
                                        <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                    </div>

                                    <!-- Alamat -->
                                    <div class="space-y-2">
                                        <label for="alamat" class="block text-sm font-medium text-slate-700">
                                            Alamat <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            <input type="text" name="alamat" id="alamat"
                                                class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                placeholder="Masukkan Alamat Anda" required minlength="10"
                                                maxlength="200">
                                        </div>
                                        <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                    </div>
                                </div>

                                <!-- Contact Information Row -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Email -->
                                    <div class="space-y-2">
                                        <label for="email" class="block text-sm font-medium text-slate-700">
                                            Email Aktif <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <input type="email" name="email" id="email"
                                                class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                placeholder="Email Anda" required>
                                        </div>
                                        <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                    </div>

                                    <!-- No WhatsApp -->
                                    <div class="space-y-2">
                                        <label for="phone" class="block text-sm font-medium text-slate-700">
                                            No WhatsApp <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                </svg>
                                            </div>
                                            <input type="tel" name="phone" id="phone"
                                                class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                placeholder="08xxxxxxxxxx" required pattern="^(\+?62|0)[0-9]{9,13}$"
                                                title="Format: 08xxxxxxxxxx atau +628xxxxxxxxxx">
                                        </div>
                                        <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                    </div>
                                </div>

                                <!-- Aspirasi Type and Category Row -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Jenis Aspirasi -->
                                    <div class="space-y-2">
                                        <label for="jenis_aspirasi" class="block text-sm font-medium text-slate-700">
                                            Jenis Aspirasi <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                            </div>
                                            <select name="jenis_aspirasi" id="jenis_aspirasi"
                                                class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                required>
                                                <option value="" disabled selected>-- Pilih Jenis Aspirasi --
                                                </option>
                                                <option value="usulan">Usulan Pembangunan</option>
                                                <option value="kritik & saran">Kritik & Saran</option>
                                            </select>
                                        </div>
                                        <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                    </div>

                                    <!-- Kategori Usulan -->
                                    <div class="space-y-2 hidden transition-all duration-300 ease-in-out"
                                        id="kategoriUsulanContainer">
                                        <label for="kategori_aspirasi_id"
                                            class="block text-sm font-medium text-slate-700">
                                            Kategori Usulan <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                            </div>
                                            <select name="kategori_aspirasi_id" id="kategori_aspirasi_id"
                                                class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                                <option value="" disabled selected>-- Pilih Kategori Usulan --
                                                </option>
                                                @foreach ($aspirasi as $item)
                                                    <option value="{{ $item->id }}">{{ $item->nama_kategori }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                    </div>
                                </div>

                                <!-- Judul -->
                                <div class="space-y-2">
                                    <label for="judul_aspirasi" class="block text-sm font-medium text-slate-700">
                                        Judul <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input type="text" name="judul_aspirasi" id="judul_aspirasi"
                                            class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            placeholder="Judul Aspirasi" required minlength="10" maxlength="150"
                                            title="Masukkan Judul yang menggambarkan isi aspirasi">
                                    </div>
                                    <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                </div>

                                <!-- Pesan -->
                                <div class="space-y-2">
                                    <label for="isi_aspirasi" class="block text-sm font-medium text-slate-700">
                                        Pesan <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute top-3 left-0 pl-3 pointer-events-none">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </div>
                                        <textarea name="isi_aspirasi" id="isi_aspirasi"
                                            class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            rows="5" required minlength="20" maxlength="1000"
                                            placeholder="Berikan usulan pengembangan wilayah atau kritik & saran untuk peningkatan layanan sistem."></textarea>
                                    </div>
                                    <div class="flex justify-between items-center mt-1">
                                        <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                        <div class="text-xs text-slate-500">
                                            <span id="charCount">0</span>/1000 karakter
                                        </div>
                                    </div>
                                </div>

                                <!-- Map Container -->
                                <div class="space-y-2 hidden transition-all duration-300 ease-in-out" id="mapContainer">
                                    <label class="block text-sm font-medium text-slate-700">
                                        Lokasi Usulan <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex flex-wrap gap-3 mb-4">
                                        <button type="button"
                                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                            id="getLocationBtn">
                                            <svg class="h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Gunakan Lokasi Saat Ini
                                        </button>
                                        <button type="button"
                                            class="px-4 py-2 bg-slate-100 text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-200 transition-colors text-sm font-medium"
                                            id="clearLocationBtn">
                                            <svg class="h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Hapus Lokasi
                                        </button>
                                    </div>
                                    <div id="map" class="w-full h-80 rounded-lg border border-slate-300 shadow-sm">
                                    </div>
                                    <p class="text-xs text-slate-500 flex items-center">
                                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Klik pada peta untuk memilih lokasi atau gunakan tombol lokasi saat ini
                                    </p>
                                    <div id="locationInfo"
                                        class="hidden p-3 text-sm text-blue-700 bg-blue-50 rounded-lg border border-blue-200">
                                        <svg class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Lokasi dipilih: <span id="coordText"></span></span>
                                    </div>
                                    <!-- Hidden input fields for coordinates -->
                                    <input type="hidden" name="latitude" id="latitude">
                                    <input type="hidden" name="longitude" id="longitude">
                                </div>

                                <!-- Lampiran -->
                                <div class="space-y-2">
                                    <label for="lampiran"
                                        class="block text-sm font-medium text-slate-700">Lampiran</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                        </div>
                                        <input type="file" name="lampiran" id="lampiran"
                                            class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            accept="image/*,.pdf,.dwg,.dxf">
                                    </div>
                                    <p class="text-xs text-slate-500 flex items-center">
                                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Tambahkan lampiran jika diperlukan (maks. 5MB) - Format: JPG, PNG, GIF, PDF, DWG,
                                        DXF
                                    </p>
                                    <div id="fileInfo"
                                        class="hidden p-2 text-sm bg-green-50 text-green-700 rounded-lg border border-green-200">
                                        <svg class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span id="fileInfoText"></span>
                                    </div>
                                    <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                </div>

                                <!-- Agreement -->
                                <div class="space-y-3">
                                    <div class="flex items-start space-x-3">
                                        <input
                                            class="mt-1 w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 focus:ring-2"
                                            type="checkbox" id="agreement" name="agreement" required>
                                        <label class="text-sm text-slate-700" for="agreement">
                                            Saya menyetujui bahwa informasi yang saya berikan adalah benar dan dapat
                                            dipertanggungjawabkan serta data saya digunakan sesuai kebijakan privasi yang
                                            berlaku
                                            <span class="text-red-500">*</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Captcha and Submit Button Row -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                                    <!-- Captcha -->
                                    <div class="flex justify-center md:justify-start">
                                        <div class="h-captcha"
                                            data-sitekey="{{ config('services.hcaptcha.sitekey_test') }}">
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="flex justify-center md:justify-end">
                                        <button type="submit"
                                            class="w-full md:w-auto px-8 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            id="submitBtn">
                                            <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                            <span id="submitText">Kirim Aspirasi</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Alert container -->
                                <div class="mt-6">
                                    <div id="alertContainer" class="hidden">
                                        <div id="alertMessage" class="p-4 rounded-lg" role="alert"></div>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    @include('frontend.partials.footer-dark')
@endsection

@push('scripts')
    <!-- Vite JavaScript -->
    @vite(['resources/js/app.js'])
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map variables
            let map = null;
            let currentLocationMarker = null;
            let isSubmitting = false;

            // Get DOM elements
            const form = document.getElementById('formUsulan');
            const jenisAspirasiSelect = document.getElementById('jenis_aspirasi');
            const kategoriUsulanContainer = document.getElementById('kategoriUsulanContainer');
            const mapContainer = document.getElementById('mapContainer');
            const getLocationBtn = document.getElementById('getLocationBtn');
            const clearLocationBtn = document.getElementById('clearLocationBtn');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const lampiranInput = document.getElementById('lampiran');
            const isiAspirasiTextarea = document.getElementById('isi_aspirasi');
            const charCountSpan = document.getElementById('charCount');

            // Accordion functionality
            const labels = document.querySelectorAll('.tab label');
            const radioButtons = document.querySelectorAll('input[name="accordion"]');

            labels.forEach((label, index) => {
                // Handle keyboard events
                label.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleAccordion(index);
                    }
                });

                // Handle click events
                label.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleAccordion(index);
                });
            });

            function toggleAccordion(index) {
                const radio = radioButtons[index];

                // If the clicked accordion is already open, close it
                if (radio.checked) {
                    radio.checked = false;
                    // Trigger change event manually for CSS transitions
                    radio.dispatchEvent(new Event('change'));
                } else {
                    // Close all other accordions and open the clicked one
                    radioButtons.forEach((otherRadio, otherIndex) => {
                        if (otherIndex !== index) {
                            otherRadio.checked = false;
                        }
                    });
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            }

            // Form validation helper
            function validateField(field, isValid, message = '') {
                const feedbackDiv = field.parentElement.querySelector('.invalid-feedback') ||
                    field.closest('.space-y-2').querySelector('.invalid-feedback');

                if (isValid) {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                    if (feedbackDiv) {
                        feedbackDiv.textContent = '';
                        feedbackDiv.classList.add('hidden');
                    }
                } else {
                    field.classList.remove('is-valid');
                    field.classList.add('is-invalid');
                    if (feedbackDiv) {
                        feedbackDiv.textContent = message;
                        feedbackDiv.classList.remove('hidden');
                    }
                }

                return isValid;
            }

            // Real-time validation
            function setupRealtimeValidation() {
                // Name validation
                document.getElementById('nama_pengirim').addEventListener('input', function() {
                    const value = this.value.trim();
                    const isValid = value.length >= 3 && value.length <= 100;
                    validateField(this, isValid, isValid ? '' : 'Nama harus 3-100 karakter');
                });

                // Address validation
                document.getElementById('alamat').addEventListener('input', function() {
                    const value = this.value.trim();
                    const isValid = value.length >= 10 && value.length <= 200;
                    validateField(this, isValid, isValid ? '' : 'Alamat harus 10-200 karakter');
                });

                // Email validation
                document.getElementById('email').addEventListener('input', function() {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    const isValid = emailRegex.test(this.value);
                    validateField(this, isValid, isValid ? '' : 'Format email tidak valid');
                });

                // Phone validation
                document.getElementById('phone').addEventListener('input', function() {
                    const phoneRegex = /^(\+?62|0)[0-9]{9,13}$/;
                    const isValid = phoneRegex.test(this.value);
                    validateField(this, isValid, isValid ? '' : 'Format: 08xxxxxxxxxx atau +628xxxxxxxxxx');
                });

                // Title validation
                document.getElementById('judul_aspirasi').addEventListener('input', function() {
                    const value = this.value.trim();
                    const isValid = value.length >= 10 && value.length <= 150;
                    validateField(this, isValid, isValid ? '' : 'Judul harus 10-150 karakter');
                });

                // Message validation with character count
                isiAspirasiTextarea.addEventListener('input', function() {
                    const value = this.value.trim();
                    const length = this.value.length;
                    const isValid = value.length >= 20 && length <= 1000;

                    charCountSpan.textContent = length;
                    charCountSpan.className = length > 900 ? 'text-orange-600 font-medium' : length ===
                        1000 ? 'text-red-600 font-bold' : '';

                    validateField(this, isValid, isValid ? '' : 'Pesan harus 20-1000 karakter');
                });
            }

            // File validation
            lampiranInput.addEventListener('change', function() {
                const file = this.files[0];
                const fileInfo = document.getElementById('fileInfo');
                const fileInfoText = document.getElementById('fileInfoText');

                if (file) {
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf',
                        'application/dwg', 'application/dxf', '.dwg', '.dxf'
                    ];
                    const fileName = file.name.toLowerCase();
                    const fileExtension = fileName.substring(fileName.lastIndexOf('.'));

                    let isValidType = allowedTypes.includes(file.type) || ['.jpg', '.jpeg', '.png', '.gif',
                        '.pdf', '.dwg', '.dxf'
                    ].includes(fileExtension);
                    let isValidSize = file.size <= maxSize;

                    if (!isValidType) {
                        validateField(this, false, 'Format file tidak didukung');
                        fileInfo.classList.add('hidden');
                        return;
                    }

                    if (!isValidSize) {
                        validateField(this, false, 'Ukuran file maksimal 5MB');
                        fileInfo.classList.add('hidden');
                        return;
                    }

                    validateField(this, true);
                    fileInfoText.textContent =
                        `File dipilih: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                    fileInfo.classList.remove('hidden');
                } else {
                    fileInfo.classList.add('hidden');
                }
            });

            // Event listener for jenis aspirasi selection
            jenisAspirasiSelect.addEventListener('change', function() {
                const selectedValue = this.value;
                const kategoriSelect = document.getElementById('kategori_aspirasi_id');

                if (selectedValue === 'usulan') {
                    // Show kategori usulan and map container with animation
                    kategoriUsulanContainer.classList.remove('hidden');
                    mapContainer.classList.remove('hidden');

                    // Make kategori required
                    kategoriSelect.setAttribute('required', 'required');

                    // Initialize map if not already initialized
                    setTimeout(() => {
                        initMap();
                    }, 300); // Delay to allow for container animation
                } else {
                    // Hide kategori usulan and map container
                    kategoriUsulanContainer.classList.add('hidden');
                    mapContainer.classList.add('hidden');

                    // Remove required attribute and reset value
                    kategoriSelect.removeAttribute('required');
                    kategoriSelect.value = '';

                    // Clear validation state
                    kategoriSelect.classList.remove('is-valid', 'is-invalid');

                    // Clear map and coordinates
                    clearMapSelection();
                }

                validateField(this, !!selectedValue, 'Pilih jenis aspirasi');
            });

            // Kategori validation
            document.getElementById('kategori_aspirasi_id').addEventListener('change', function() {
                validateField(this, !!this.value, 'Pilih kategori usulan');
            });

            // Event listener for get location button
            getLocationBtn.addEventListener('click', function() {
                getCurrentLocation();
            });

            // Event listener for clear location button
            clearLocationBtn.addEventListener('click', function() {
                clearMapSelection();
            });

            // Handle form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (isSubmitting) return;

                // Validate form before submission
                if (!validateForm()) {
                    showAlert('error', 'Mohon periksa kembali data yang Anda masukkan');
                    return false;
                }

                const jenisAspirasi = jenisAspirasiSelect.value;
                const latitude = document.getElementById('latitude').value;
                const longitude = document.getElementById('longitude').value;
                const kategoriAspirasi = document.getElementById('kategori_aspirasi_id').value;

                if (jenisAspirasi === 'usulan') {
                    if (!kategoriAspirasi) {
                        showAlert('error', 'Pilih kategori usulan terlebih dahulu');
                        return false;
                    }
                    if (!latitude || !longitude) {
                        showAlert('error', 'Pilih lokasi pada peta terlebih dahulu');
                        return false;
                    }
                }

                // Get user's current location for non-usulan types
                if (jenisAspirasi !== 'usulan') {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                document.getElementById('latitude').value = position.coords.latitude;
                                document.getElementById('longitude').value = position.coords.longitude;
                                submitForm();
                            },
                            function(error) {
                                console.warn('Location error:', error);
                                submitForm(); // Submit without coordinates if location fails
                            }, {
                                timeout: 10000,
                                maximumAge: 300000,
                                enableHighAccuracy: false
                            }
                        );
                    } else {
                        submitForm(); // Submit without coordinates if geolocation not supported
                    }
                } else {
                    submitForm();
                }
            });

            // Form validation function
            function validateForm() {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    if (field.type === 'checkbox') {
                        if (!field.checked) {
                            isValid = false;
                            field.focus();
                        }
                    } else if (!field.value.trim()) {
                        isValid = false;
                        validateField(field, false, 'Field ini wajib diisi');
                        if (isValid) field.focus(); // Focus first invalid field
                    }
                });

                return isValid;
            }

            // Submit form function
            function submitForm() {
                if (isSubmitting) return;

                isSubmitting = true;
                submitBtn.disabled = true;
                loadingOverlay.classList.add('active');

                const originalText = submitText.textContent;
                submitText.innerHTML = '<i class="bi bi-hourglass-split mr-2"></i>Mengirim...';

                const formData = new FormData(form);

                // Ensure hCaptcha response is included
                try {
                    if (typeof hcaptcha !== 'undefined') {
                        const hcaptchaResponse = hcaptcha.getResponse();
                        if (hcaptchaResponse) {
                            formData.set('h-captcha-response', hcaptchaResponse);
                        }
                    }
                } catch (error) {
                    console.warn('hCaptcha not available:', error);
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
                            showAlert('success', data.message);
                            resetForm();
                        } else {
                            handleSubmitError(data);
                        }
                    })
                    .catch(error => {
                        console.error('Submit error:', error);
                        showAlert('error', 'Terjadi kesalahan koneksi. Silakan coba lagi.');
                    })
                    .finally(() => {
                        isSubmitting = false;
                        submitBtn.disabled = false;
                        loadingOverlay.classList.remove('active');
                        submitText.innerHTML = `<i class="bi bi-send mr-2"></i>${originalText}`;
                    });
            }

            // Handle submit errors
            function handleSubmitError(data) {
                if (data.errors) {
                    if (data.errors['h-captcha-response']) {
                        showAlert('error', data.errors['h-captcha-response'][0]);
                        resetCaptcha();
                    } else {
                        // Show first validation error
                        const firstError = Object.values(data.errors)[0];
                        showAlert('error', Array.isArray(firstError) ? firstError[0] : firstError);
                    }
                } else {
                    showAlert('error', data.message || 'Terjadi kesalahan');
                }
            }

            // Reset form function
            function resetForm() {
                form.reset();
                kategoriUsulanContainer.classList.add('hidden');
                mapContainer.classList.add('hidden');

                // Clear validation states
                form.querySelectorAll('.form-control').forEach(field => {
                    field.classList.remove('is-valid', 'is-invalid');
                });

                // Reset map
                clearMapSelection();
                if (map) {
                    map.remove();
                    map = null;
                }

                // Reset file info
                document.getElementById('fileInfo').classList.add('hidden');

                // Reset character count
                charCountSpan.textContent = '0';
                charCountSpan.className = '';

                // Reset captcha
                resetCaptcha();
            }

            // Reset captcha
            function resetCaptcha() {
                try {
                    if (typeof hcaptcha !== 'undefined') {
                        hcaptcha.reset();
                    }
                } catch (error) {
                    console.warn('Error resetting hCaptcha:', error);
                }
            }

            // Show alert function
            function showAlert(type, message) {
                const alertContainer = document.getElementById('alertContainer');
                const alertMessage = document.getElementById('alertMessage');

                let alertClass, iconClass;

                switch (type) {
                    case 'success':
                        alertClass = 'bg-green-100 border border-green-400 text-green-700';
                        iconClass = 'bi-check-circle';
                        break;
                    case 'error':
                        alertClass = 'bg-red-100 border border-red-400 text-red-700';
                        iconClass = 'bi-exclamation-triangle';
                        break;
                    case 'warning':
                        alertClass = 'bg-yellow-100 border border-yellow-400 text-yellow-700';
                        iconClass = 'bi-exclamation-circle';
                        break;
                    default:
                        alertClass = 'bg-blue-100 border border-blue-400 text-blue-700';
                        iconClass = 'bi-info-circle';
                }

                alertMessage.className = `${alertClass} shadow-lg rounded-lg mb-0 px-4 py-3`;
                alertMessage.innerHTML = `<i class="bi ${iconClass} mr-2"></i>${message}`;

                // Show with animation
                alertContainer.classList.remove('opacity-0', 'invisible', '-translate-y-5');
                alertContainer.classList.add('opacity-100', 'visible', 'translate-y-0');

                // Auto hide after 5 seconds
                setTimeout(() => {
                    alertContainer.classList.add('opacity-0', 'invisible', '-translate-y-5');
                    alertContainer.classList.remove('opacity-100', 'visible', 'translate-y-0');
                }, 5000);
            }

            // Initialize map function
            function initMap() {
                if (map) return;

                const defaultCenter = [0.735485, 128.028201]; // Maluku Utara coordinates

                try {
                    map = L.map('map', {
                        center: defaultCenter,
                        zoom: 8,
                        zoomControl: true,
                        attributionControl: true
                    });

                    // Add tile layer (OpenStreetMap)
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19
                    }).addTo(map);

                    // Add click event to map
                    map.on('click', function(e) {
                        setMarker(e.latlng);
                    });

                    // Refresh map size after container is visible
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);

                } catch (error) {
                    console.error('Error initializing map:', error);
                    showAlert('error', 'Gagal memuat peta. Refresh halaman dan coba lagi.');
                }
            }

            // Set marker function
            function setMarker(latlng) {
                if (!map) return;

                try {
                    if (currentLocationMarker) {
                        map.removeLayer(currentLocationMarker);
                    }

                    currentLocationMarker = L.marker(latlng, {
                        draggable: true,
                        title: 'Lokasi Usulan'
                    }).addTo(map);

                    // Add drag event to marker
                    currentLocationMarker.on('dragend', function(e) {
                        const newLatLng = e.target.getLatLng();
                        updateCoordinates(newLatLng);
                    });

                    updateCoordinates(latlng);

                } catch (error) {
                    console.error('Error setting marker:', error);
                    showAlert('error', 'Gagal menetapkan lokasi');
                }
            }

            // Update coordinates function
            function updateCoordinates(latlng) {
                document.getElementById('latitude').value = latlng.lat.toFixed(6);
                document.getElementById('longitude').value = latlng.lng.toFixed(6);

                const locationInfo = document.getElementById('locationInfo');
                const coordText = document.getElementById('coordText');

                coordText.textContent = `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
                locationInfo.classList.remove('hidden');
            }

            // Clear map selection function
            function clearMapSelection() {
                if (currentLocationMarker && map) {
                    map.removeLayer(currentLocationMarker);
                    currentLocationMarker = null;
                }

                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                document.getElementById('locationInfo').classList.add('hidden');
            }

            // Get current location function
            function getCurrentLocation() {
                if (!navigator.geolocation) {
                    showAlert('error', 'Browser Anda tidak mendukung geolocation');
                    return;
                }

                getLocationBtn.disabled = true;
                getLocationBtn.innerHTML =
                    '<i class="bi bi-hourglass-split animate-spin mr-1"></i> Mencari Lokasi...';

                const options = {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 60000
                };

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;

                        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                            showAlert('error', 'Lokasi tidak valid');
                            return;
                        }

                        const latlng = L.latLng(lat, lng);
                        setMarker(latlng);

                        if (map) {
                            map.setView(latlng, 15);
                        }

                        if (accuracy > 100) {
                            showAlert('warning',
                                `Lokasi ditemukan dengan akurasi ~${Math.round(accuracy)} meter`);
                        } else {
                            showAlert('success', 'Lokasi berhasil ditemukan');
                        }
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                        let message = 'Gagal mendapatkan lokasi';

                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                message = 'Akses lokasi ditolak. Aktifkan izin lokasi di browser';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                message = 'Informasi lokasi tidak tersedia';
                                break;
                            case error.TIMEOUT:
                                message = 'Pencarian lokasi timeout. Coba lagi';
                                break;
                        }

                        showAlert('error', message);
                    },
                    options
                );

                // Reset button state after timeout
                setTimeout(() => {
                    getLocationBtn.disabled = false;
                    getLocationBtn.innerHTML = '<i class="bi bi-geo-alt mr-1"></i> Gunakan Lokasi Saat Ini';
                }, 15000);
            }

            // Initialize real-time validation
            setupRealtimeValidation();
        });
    </script>
@endpush
