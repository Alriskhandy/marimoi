@extends('frontend.layouts.dark', ['title' => 'Usulan Aspirasi'])

@push('styles')
    @vite(['resources/css/app.css'])
    <script src="https://js.hcaptcha.com/1/api.js?hl=id" async defer></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <style>
        /* Typography */
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

        /* Accordion Animations */
        .answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out, padding 0.3s ease-in-out;
            padding-top: 0;
        }

        .tab input[type="radio"]:checked~.answer {
            padding-top: 1rem;
        }

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

        /* Accordion States */
        .tab input[type="radio"]:checked~label {
            background: linear-gradient(to bottom right, #2563eb, #1e40af) !important;
            color: white !important;
        }

        .tab input[type="radio"]:checked~label h4,
        .tab input[type="radio"]:checked~label::after,
        .tab input[type="radio"]:checked~label i {
            color: white !important;
        }

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

        .tab:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Map Styles */
        #map {
            height: 350px;
            z-index: 99;
            width: 100%;
            border-radius: 8px;
        }

        .container {
            max-width: 1200px;
            color: #0a0f1e;
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

    <!-- Main Content -->
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
                        <p class="text-justify mb-4 text-slate-600">
                            Formulir ini digunakan untuk menyampaikan usulan pembangunan atau kritik & saran terkait layanan
                            sistem.
                        </p>

                        <!-- Accordion -->
                        <div class="wrapper w-full" id="instructionAccordion">
                            @foreach ([
            [
                'id' => 'acc1',
                'icon' => 'info-circle',
                'title' => 'Jenis Aspirasi',
                'content' => '
                                        <p class="mb-2">Ada 2 jenis aspirasi yang dapat disampaikan:</p>
                                        <ul class="list-disc list-inside space-y-1 pl-4">
                                            <li><strong>Usulan Pembangunan</strong> - Untuk mengusulkan proyek pembangunan baru dengan lokasi spesifik</li>
                                            <li><strong>Kritik & Saran</strong> - Untuk memberikan masukan umum tanpa perlu menentukan lokasi</li>
                                        </ul>
                                    ',
            ],
            [
                'id' => 'acc2',
                'icon' => 'list-ol',
                'title' => 'Langkah Pengisian',
                'content' => '
                                        <ol class="list-decimal list-inside space-y-1 pl-4">
                                            <li>Isi data diri Anda (nama, alamat, email, dan nomor WhatsApp)</li>
                                            <li>Pilih jenis aspirasi (Usulan atau Kritik & Saran)</li>
                                            <li>Untuk Usulan: pilih kategori dan tentukan lokasi pada peta</li>
                                            <li>Isi judul dan pesan aspirasi secara jelas</li>
                                            <li>Lampirkan file pendukung jika diperlukan</li>
                                            <li>Centang persetujuan dan selesaikan captcha</li>
                                            <li>Klik tombol "Kirim Aspirasi"</li>
                                        </ol>
                                    ',
            ],
            [
                'id' => 'acc3',
                'icon' => 'geo-alt',
                'title' => 'Lokasi Usulan',
                'content' => '
                                        <p class="mb-2">Untuk jenis aspirasi "Usulan Pembangunan", Anda perlu menentukan lokasi:</p>
                                        <ul class="list-disc list-inside space-y-1 pl-4">
                                            <li>Klik tombol "Gunakan Lokasi Saat Ini" untuk menggunakan lokasi Anda sekarang</li>
                                            <li>Atau klik langsung pada peta untuk memilih lokasi yang diinginkan</li>
                                            <li>Lokasi yang dipilih akan ditandai dengan pin pada peta</li>
                                        </ul>
                                    ',
            ],
            [
                'id' => 'acc4',
                'icon' => 'paperclip',
                'title' => 'Lampiran',
                'content' => '
                                        <p class="mb-2">Anda dapat melampirkan file pendukung:</p>
                                        <ul class="list-disc list-inside space-y-1 pl-4">
                                            <li>Format yang didukung: gambar (JPG, PNG, GIF), PDF, DWG, DXF</li>
                                            <li>Ukuran maksimal file: 5MB</li>
                                            <li>Lampiran dapat berupa foto lokasi, sketsa, atau dokumen pendukung lainnya</li>
                                        </ul>
                                    ',
            ],
            [
                'id' => 'acc5',
                'icon' => 'shield-lock',
                'title' => 'Privasi Data',
                'content' => '
                                        <p class="mb-2">Data yang Anda berikan akan digunakan untuk:</p>
                                        <ul class="list-disc list-inside space-y-1 pl-4">
                                            <li>Memproses aspirasi yang Anda sampaikan</li>
                                            <li>Menghubungi Anda terkait tindak lanjut aspirasi</li>
                                            <li>Data Anda tidak akan dibagikan kepada pihak ketiga tanpa persetujuan</li>
                                            <li>Aspirasi yang disampaikan akan ditinjau oleh tim terkait</li>
                                        </ul>
                                    ',
            ],
        ] as $accordion)
                                <div
                                    class="tab mb-4 px-3 py-3 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                                    <input type="radio" name="accordion" id="{{ $accordion['id'] }}" class="hidden peer">
                                    <label for="{{ $accordion['id'] }}"
                                        class="flex items-center text-sm md:text-base font-semibold cursor-pointer py-2 px-3 rounded-md
                                       after:absolute after:content-['+'] after:right-6 after:text-2xl 
                                       after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform 
                                       peer-checked:after:rotate-45 after:transition-transform after:duration-300"
                                        tabindex="0">
                                        <h4><i class="bi bi-{{ $accordion['icon'] }} me-2"></i> {{ $accordion['title'] }}
                                        </h4>
                                    </label>
                                    <div
                                        class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                                        <div class="text-gray-700 text-sm leading-relaxed">
                                            {!! $accordion['content'] !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
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
                            <div class="space-y-4">
                                <!-- Personal Information -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach ([['name' => 'nama_pengirim', 'label' => 'Nama Lengkap', 'type' => 'text', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'placeholder' => 'Nama Lengkap Anda', 'required' => true, 'minlength' => '3', 'maxlength' => '100'], ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'text', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', 'placeholder' => 'Masukkan Alamat Anda', 'required' => true, 'minlength' => '10', 'maxlength' => '200']] as $field)
                                        <div class="space-y-2">
                                            <label for="{{ $field['name'] }}"
                                                class="block text-sm font-medium text-slate-700">
                                                {{ $field['label'] }} <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="{{ $field['icon'] }}" />
                                                    </svg>
                                                </div>
                                                <input type="{{ $field['type'] }}" name="{{ $field['name'] }}"
                                                    id="{{ $field['name'] }}"
                                                    class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                    placeholder="{{ $field['placeholder'] }}"
                                                    {{ $field['required'] ? 'required' : '' }}
                                                    {{ isset($field['minlength']) ? 'minlength="' . $field['minlength'] . '"' : '' }}
                                                    {{ isset($field['maxlength']) ? 'maxlength="' . $field['maxlength'] . '"' : '' }}>
                                            </div>
                                            <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Contact Information -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                                <!-- Aspirasi Type and Category -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                                    <option value="{{ $item->id }}">{{ $item->nama_kategori }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                    </div>
                                </div>

                                <!-- Title and Message -->
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
                                            placeholder="Judul Aspirasi" required minlength="10" maxlength="150">
                                    </div>
                                    <div class="invalid-feedback hidden text-red-500 text-sm"></div>
                                </div>

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
                                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors text-sm font-medium"
                                            id="getLocationBtn">
                                            <svg class="h-4 w-4 inline mr-2" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
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
                                    <input type="hidden" name="latitude" id="latitude">
                                    <input type="hidden" name="longitude" id="longitude">
                                </div>

                                <!-- File Upload -->
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
                                        Format: JPG, JPEG, PNG, PDF, DOC, DOCX (maks. 5MB)
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

                                <!-- Captcha and Submit -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                                    <div class="flex justify-center md:justify-start">
                                        <div class="h-captcha"
                                            data-sitekey="{{ config('services.hcaptcha.sitekey_test') }}"></div>
                                    </div>

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
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.footer-dark')
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        class AspirasiForm {
            constructor() {
                this.map = null;
                this.currentLocationMarker = null;
                this.isSubmitting = false;

                this.initElements();
                this.initEventListeners();
                this.setupValidation();
            }

            initElements() {
                this.elements = {
                    form: document.getElementById('formUsulan'),
                    jenisAspirasi: document.getElementById('jenis_aspirasi'),
                    kategoriContainer: document.getElementById('kategoriUsulanContainer'),
                    kategoriSelect: document.getElementById('kategori_aspirasi_id'),
                    mapContainer: document.getElementById('mapContainer'),
                    getLocationBtn: document.getElementById('getLocationBtn'),
                    clearLocationBtn: document.getElementById('clearLocationBtn'),
                    submitBtn: document.getElementById('submitBtn'),
                    submitText: document.getElementById('submitText'),
                    modalOverlay: document.getElementById('modalOverlay'),
                    modalIcon: document.getElementById('modalIcon'),
                    modalTitle: document.getElementById('modalTitle'),
                    modalMessage: document.getElementById('modalMessage'),
                    modalActions: document.getElementById('modalActions'),
                    modalCloseBtn: document.getElementById('modalCloseBtn'),
                    lampiranInput: document.getElementById('lampiran'),
                    isiAspirasi: document.getElementById('isi_aspirasi'),
                    charCount: document.getElementById('charCount'),
                    latitude: document.getElementById('latitude'),
                    longitude: document.getElementById('longitude'),
                    fileInfo: document.getElementById('fileInfo'),
                    fileInfoText: document.getElementById('fileInfoText'),
                    locationInfo: document.getElementById('locationInfo'),
                    coordText: document.getElementById('coordText')
                };
            }

            initEventListeners() {
                // Form submission
                this.elements.form.addEventListener('submit', (e) => this.handleSubmit(e));

                // Aspirasi type change
                this.elements.jenisAspirasi.addEventListener('change', (e) => this.handleJenisAspirasiChange(e));

                // Location buttons
                this.elements.getLocationBtn.addEventListener('click', () => this.getCurrentLocation());
                this.elements.clearLocationBtn.addEventListener('click', () => this.clearMapSelection());

                // Modal close
                this.elements.modalCloseBtn.addEventListener('click', () => this.hideModal());
                this.elements.modalOverlay.addEventListener('click', (e) => {
                    if (e.target === this.elements.modalOverlay) this.hideModal();
                });

                // File upload
                this.elements.lampiranInput.addEventListener('change', (e) => this.handleFileChange(e));

                // Character count
                this.elements.isiAspirasi.addEventListener('input', (e) => this.updateCharCount(e));

                // Accordion functionality
                this.initAccordion();
            }

            initAccordion() {
                const labels = document.querySelectorAll('.tab label');
                const radioButtons = document.querySelectorAll('input[name="accordion"]');

                labels.forEach((label, index) => {
                    const toggleAccordion = () => {
                        const radio = radioButtons[index];
                        if (radio.checked) {
                            radio.checked = false;
                        } else {
                            radioButtons.forEach((otherRadio, otherIndex) => {
                                if (otherIndex !== index) otherRadio.checked = false;
                            });
                            radio.checked = true;
                        }
                        radio.dispatchEvent(new Event('change'));
                    };

                    label.addEventListener('click', (e) => {
                        e.preventDefault();
                        toggleAccordion();
                    });

                    label.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            toggleAccordion();
                        }
                    });
                });
            }

            setupValidation() {
                // Real-time validation for all form fields
                const validationRules = {
                    nama_pengirim: {
                        validate: (value) => value.length >= 3 && value.length <= 100,
                        message: 'Nama harus 3-100 karakter'
                    },
                    alamat: {
                        validate: (value) => value.length >= 5 && value.length <= 200,
                        message: 'Alamat harus 5-200 karakter'
                    },
                    email: {
                        validate: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
                        message: 'Format email tidak valid'
                    },
                    phone: {
                        validate: (value) => /^(\+?62|0)[0-9]{9,13}$/.test(value),
                        message: 'Format: 08xxxxxxxxxx atau +628xxxxxxxxxx'
                    },
                    judul_aspirasi: {
                        validate: (value) => value.length >= 5 && value.length <= 150,
                        message: 'Judul harus 5-150 karakter'
                    },
                    isi_aspirasi: {
                        validate: (value) => value.trim().length >= 10 && value.length <= 1000,
                        message: 'Pesan harus 10-1000 karakter'
                    }
                };

                Object.keys(validationRules).forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) {
                        field.addEventListener('input', () => {
                            const rule = validationRules[fieldName];
                            const isValid = rule.validate(field.value.trim());
                            this.validateField(field, isValid, rule.message);
                        });
                    }
                });

                // Category validation
                this.elements.kategoriSelect.addEventListener('change', () => {
                    this.validateField(this.elements.kategoriSelect, !!this.elements.kategoriSelect.value,
                        'Pilih kategori usulan');
                });
            }

            validateField(field, isValid, message = '') {
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

            handleJenisAspirasiChange(e) {
                const selectedValue = e.target.value;

                if (selectedValue === 'usulan') {
                    this.elements.kategoriContainer.classList.remove('hidden');
                    this.elements.mapContainer.classList.remove('hidden');
                    this.elements.kategoriSelect.setAttribute('required', 'required');

                    setTimeout(() => this.initMap(), 300);
                } else {
                    this.elements.kategoriContainer.classList.add('hidden');
                    this.elements.mapContainer.classList.add('hidden');
                    this.elements.kategoriSelect.removeAttribute('required');
                    this.elements.kategoriSelect.value = '';
                    this.elements.kategoriSelect.classList.remove('is-valid', 'is-invalid');
                    this.clearMapSelection();
                }

                this.validateField(e.target, !!selectedValue, 'Pilih jenis aspirasi');
            }

            updateCharCount(e) {
                const length = e.target.value.length;
                this.elements.charCount.textContent = length;
                this.elements.charCount.className = length > 900 ? 'text-orange-600 font-medium' :
                    length === 1000 ? 'text-red-600 font-bold' : '';
            }

            handleFileChange(e) {
                const file = e.target.files[0];

                if (!file) {
                    this.elements.fileInfo.classList.add('hidden');
                    return;
                }

                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf', 'application/doc', 'application/docx'];
                const fileName = file.name.toLowerCase();
                const fileExtension = fileName.substring(fileName.lastIndexOf('.'));

                const isValidType = allowedTypes.includes(file.type) || ['.jpg', '.jpeg', '.png', , '.pdf',
                    '.doc', '.docx'
                ].includes(fileExtension);
                const isValidSize = file.size <= maxSize;

                if (!isValidType) {
                    this.validateField(e.target, false, 'Format file tidak didukung');
                    this.elements.fileInfo.classList.add('hidden');
                    this.resetCaptcha(); // Reset captcha when file format validation fails
                    return;
                }

                if (!isValidSize) {
                    this.validateField(e.target, false, 'Ukuran file maksimal 5MB');
                    this.elements.fileInfo.classList.add('hidden');
                    this.resetCaptcha(); // Reset captcha when file size validation fails
                    return;
                }

                this.validateField(e.target, true);
                this.elements.fileInfoText.textContent =
                    `File dipilih: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                this.elements.fileInfo.classList.remove('hidden');
            }

            initMap() {
                if (this.map) return;

                const defaultCenter = [0.735485, 128.028201]; // Maluku Utara

                try {
                    this.map = L.map('map', {
                        center: defaultCenter,
                        zoom: 8,
                        zoomControl: true,
                        attributionControl: true
                    });

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19
                    }).addTo(this.map);

                    this.map.on('click', (e) => this.setMarker(e.latlng));

                    setTimeout(() => this.map.invalidateSize(), 100);
                } catch (error) {
                    console.error('Map initialization error:', error);
                    this.showModal('error', 'Gagal memuat peta', 'Refresh halaman dan coba lagi.');
                }
            }

            setMarker(latlng) {
                if (!this.map) return;

                try {
                    if (this.currentLocationMarker) {
                        this.map.removeLayer(this.currentLocationMarker);
                    }

                    this.currentLocationMarker = L.marker(latlng, {
                        draggable: true,
                        title: 'Lokasi Usulan'
                    }).addTo(this.map);

                    this.currentLocationMarker.on('dragend', (e) => {
                        this.updateCoordinates(e.target.getLatLng());
                    });

                    this.updateCoordinates(latlng);
                } catch (error) {
                    console.error('Marker error:', error);
                    this.showModal('error', 'Gagal menetapkan lokasi', 'Silakan coba lagi.');
                }
            }

            updateCoordinates(latlng) {
                this.elements.latitude.value = latlng.lat.toFixed(6);
                this.elements.longitude.value = latlng.lng.toFixed(6);
                this.elements.coordText.textContent = `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
                this.elements.locationInfo.classList.remove('hidden');
            }

            clearMapSelection() {
                if (this.currentLocationMarker && this.map) {
                    this.map.removeLayer(this.currentLocationMarker);
                    this.currentLocationMarker = null;
                }
                this.elements.latitude.value = '';
                this.elements.longitude.value = '';
                this.elements.locationInfo.classList.add('hidden');
            }

            getCurrentLocation() {
                if (!navigator.geolocation) {
                    this.showModal('error', 'Geolocation tidak didukung', 'Browser Anda tidak mendukung fitur lokasi.');
                    return;
                }

                this.elements.getLocationBtn.disabled = true;
                this.elements.getLocationBtn.innerHTML =
                    '<i class="bi bi-hourglass-split animate-spin mr-1"></i> Mencari Lokasi...';

                const options = {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 60000
                };

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const {
                            latitude,
                            longitude,
                            accuracy
                        } = position.coords;

                        if (latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) {
                            this.showModal('error', 'Lokasi tidak valid', 'Koordinat yang diterima tidak valid.');
                            return;
                        }

                        const latlng = L.latLng(latitude, longitude);
                        this.setMarker(latlng);

                        if (this.map) {
                            this.map.setView(latlng, 15);
                        }

                        const message = accuracy > 100 ?
                            `Lokasi ditemukan dengan akurasi ~${Math.round(accuracy)} meter` :
                            'Lokasi berhasil ditemukan';

                        this.showModal('success', 'Lokasi Ditemukan', message, true);
                    },
                    (error) => {
                        console.error('Geolocation error:', error);

                        const messages = {
                            [error.PERMISSION_DENIED]: 'Akses lokasi ditolak. Aktifkan izin lokasi di browser.',
                            [error.POSITION_UNAVAILABLE]: 'Informasi lokasi tidak tersedia.',
                            [error.TIMEOUT]: 'Pencarian lokasi timeout. Coba lagi.'
                        };

                        const message = messages[error.code] || 'Gagal mendapatkan lokasi';
                        this.showModal('error', 'Gagal Mendapatkan Lokasi', message);
                    },
                    options
                );

                setTimeout(() => {
                    this.elements.getLocationBtn.disabled = false;
                    this.elements.getLocationBtn.innerHTML =
                        '<i class="bi bi-geo-alt mr-1"></i> Gunakan Lokasi Saat Ini';
                }, 15000);
            }

            validateForm() {
                let isValid = true;
                const requiredFields = this.elements.form.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    if (field.type === 'checkbox') {
                        if (!field.checked) {
                            isValid = false;
                            field.focus();
                        }
                    } else if (!field.value.trim()) {
                        isValid = false;
                        this.validateField(field, false, 'Field ini wajib diisi');
                        if (isValid) field.focus();
                    }
                });

                return isValid;
            }

            async handleSubmit(e) {
                e.preventDefault();

                if (this.isSubmitting) return;

                if (!this.validateForm()) {
                    this.showModal('error', 'Data tidak lengkap', 'Mohon periksa kembali data yang Anda masukkan.');
                    this.resetCaptcha(); // Reset captcha when client-side validation fails
                    return;
                }

                const jenisAspirasi = this.elements.jenisAspirasi.value;

                // Validation for usulan type
                if (jenisAspirasi === 'usulan') {
                    if (!this.elements.kategoriSelect.value) {
                        this.showModal('error', 'Kategori belum dipilih', 'Pilih kategori usulan terlebih dahulu.');
                        this.resetCaptcha(); // Reset captcha when category validation fails
                        return;
                    }
                    if (!this.elements.latitude.value || !this.elements.longitude.value) {
                        this.showModal('error', 'Lokasi belum dipilih', 'Pilih lokasi pada peta terlebih dahulu.');
                        this.resetCaptcha(); // Reset captcha when location validation fails
                        return;
                    }
                }

                this.showModal('loading', 'Mengirim Aspirasi', 'Mohon tunggu sebentar...');

                // Get location for non-usulan types
                if (jenisAspirasi !== 'usulan') {
                    await this.setCurrentLocationForNonUsulan();
                }

                this.submitForm();
            }

            setCurrentLocationForNonUsulan() {
                return new Promise((resolve) => {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                this.elements.latitude.value = position.coords.latitude;
                                this.elements.longitude.value = position.coords.longitude;
                                resolve();
                            },
                            (error) => {
                                console.warn('Location error:', error);
                                resolve(); // Continue without coordinates
                            }, {
                                timeout: 10000,
                                maximumAge: 300000,
                                enableHighAccuracy: false
                            }
                        );
                    } else {
                        resolve();
                    }
                });
            }

            async submitForm() {
                this.isSubmitting = true;
                this.elements.submitBtn.disabled = true;

                const formData = new FormData(this.elements.form);

                // Add hCaptcha response
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

                try {
                    const response = await fetch(this.elements.form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.showModal('success', 'Aspirasi Berhasil Dikirim', data.message);
                        this.resetForm();
                    } else {
                        this.handleSubmitError(data);
                    }
                } catch (error) {
                    console.error('Submit error:', error);
                    this.showModal('error', 'Koneksi Bermasalah', 'Terjadi kesalahan koneksi. Silakan coba lagi.');
                    // Reset captcha on connection errors as well
                    this.resetCaptcha();
                } finally {
                    this.isSubmitting = false;
                    this.elements.submitBtn.disabled = false;
                }
            }

            handleSubmitError(data) {
                // Always reset captcha when there are any validation errors
                // This prevents users from thinking captcha is valid when other fields fail
                if (data.errors) {
                    this.resetCaptcha();
                    
                    if (data.errors['h-captcha-response']) {
                        this.showModal('error', 'Verifikasi Captcha Gagal', data.errors['h-captcha-response'][0]);
                    } else {
                        const firstError = Object.values(data.errors)[0];
                        this.showModal('error', 'Validasi Gagal', Array.isArray(firstError) ? firstError[0] :
                            firstError);
                    }
                } else {
                    this.showModal('error', 'Terjadi Kesalahan', data.message || 'Silakan coba lagi.');
                }
            }

            resetForm() {
                this.elements.form.reset();
                this.elements.kategoriContainer.classList.add('hidden');
                this.elements.mapContainer.classList.add('hidden');

                // Clear validation states
                this.elements.form.querySelectorAll('.form-control, input, select, textarea').forEach(field => {
                    field.classList.remove('is-valid', 'is-invalid');
                });

                // Reset map
                this.clearMapSelection();
                if (this.map) {
                    this.map.remove();
                    this.map = null;
                }

                // Reset file info
                this.elements.fileInfo.classList.add('hidden');

                // Reset character count
                this.elements.charCount.textContent = '0';
                this.elements.charCount.className = '';

                this.resetCaptcha();
            }

            resetCaptcha() {
                try {
                    if (typeof hcaptcha !== 'undefined') {
                        hcaptcha.reset();
                    }
                } catch (error) {
                    console.warn('Error resetting hCaptcha:', error);
                }
            }

            showModal(type, title, message, autoHide = false) {
                const iconMap = {
                    success: {
                        bgClass: 'bg-green-100',
                        textClass: 'text-green-600',
                        icon: '<i class="bi bi-check-circle-fill"></i>'
                    },
                    error: {
                        bgClass: 'bg-red-100',
                        textClass: 'text-red-600',
                        icon: '<i class="bi bi-exclamation-triangle-fill"></i>'
                    },
                    warning: {
                        bgClass: 'bg-yellow-100',
                        textClass: 'text-yellow-600',
                        icon: '<i class="bi bi-exclamation-circle-fill"></i>'
                    },
                    loading: {
                        bgClass: 'bg-blue-100',
                        textClass: 'text-blue-600',
                        icon: '<div class="w-12 h-12 border-4 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>'
                    }
                };

                const config = iconMap[type] || iconMap.loading;

                // Reset icon classes and apply new ones
                this.elements.modalIcon.className =
                    `w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center text-3xl ${config.bgClass} ${config.textClass}`;
                this.elements.modalIcon.innerHTML = config.icon;
                this.elements.modalTitle.textContent = title;
                this.elements.modalMessage.textContent = message;

                if (type === 'loading') {
                    this.elements.modalActions.classList.add('hidden');
                } else {
                    this.elements.modalActions.classList.remove('hidden');
                }

                // Show modal with Tailwind classes
                this.elements.modalOverlay.classList.remove('opacity-0', 'invisible');
                this.elements.modalOverlay.classList.add('opacity-100', 'visible');

                // Animate modal content
                const modalContent = this.elements.modalOverlay.querySelector('div:first-child');
                modalContent.classList.remove('scale-90', 'translate-y-5');
                modalContent.classList.add('scale-100', 'translate-y-0');

                if (autoHide) {
                    setTimeout(() => this.hideModal(), 3000);
                }
            }

            hideModal() {
                // Hide modal with Tailwind classes
                this.elements.modalOverlay.classList.remove('opacity-100', 'visible');
                this.elements.modalOverlay.classList.add('opacity-0', 'invisible');

                // Animate modal content back
                const modalContent = this.elements.modalOverlay.querySelector('div:first-child');
                modalContent.classList.remove('scale-100', 'translate-y-0');
                modalContent.classList.add('scale-90', 'translate-y-5');
            }
        }

        // Initialize the form when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new AspirasiForm();
        });
    </script>
@endpush
