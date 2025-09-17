@extends('frontend.layouts.dark', ['title' => 'Syarat dan Ketentuan'])

@push('styles')
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
    </style>
@endpush

@section('main')
    <!-- Terms and Conditions Section -->
    <section id="terms" class="min-h-auto mt-[76px] pt-8 pb-12 bg-slate-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-center">
                <div class="w-full max-w-4xl">
                    <div class="bg-white shadow-xl border-0 rounded-2xl">
                        <div class="p-8 md:p-12">
                            <h2 class="text-center mb-6 text-2xl md:text-3xl font-bold text-[#0a0f1e]">
                                Syarat dan Ketentuan Website MARIMOI
                            </h2>
                            <p class="text-justify text-slate-600 mb-8 text-sm md:text-base leading-relaxed">
                                Dengan mengakses situs ini, Anda setuju untuk mematuhi Syarat dan Ketentuan berikut.
                                Syarat ini berlaku untuk semua pengunjung, pengguna, dan pihak lain yang mengakses atau
                                menggunakan layanan MARIMOI. Bacalah dengan seksama sebelum melanjutkan penggunaan.
                            </p>

                            <div class="mb-6">
                                <h5 class="font-bold text-lg text-[#0a0f1e] mb-3">1. Penggunaan Layanan</h5>
                                <p class="text-slate-600 mb-3">
                                    Website MARIMOI disediakan untuk memberikan informasi dan layanan terkait koordinasi,
                                    pemantauan, serta integrasi pembangunan infrastruktur di Maluku Utara.
                                    Pengguna wajib:
                                </p>
                                <ul class="text-slate-600 list-disc list-inside space-y-1 ml-4">
                                    <li>Menggunakan layanan hanya untuk tujuan yang sah dan sesuai hukum</li>
                                    <li>Tidak melakukan tindakan yang dapat merusak, menonaktifkan, atau mengganggu fungsi
                                        situs</li>
                                    <li>Tidak mencoba mengakses data atau sistem yang tidak diizinkan</li>
                                </ul>
                                <p class="text-slate-600 mt-3">
                                    Pelanggaran terhadap ketentuan ini dapat mengakibatkan penghentian akses secara
                                    permanen.
                                </p>
                            </div>

                            <div class="mb-6">
                                <h5 class="font-bold text-lg text-[#0a0f1e] mb-3">2. Hak Kekayaan Intelektual</h5>
                                <p class="text-slate-600 mb-3">
                                    Seluruh konten di situs ini, termasuk teks, gambar, ikon, video, logo, dan desain
                                    antarmuka
                                    adalah milik MARIMOI atau pihak ketiga yang memberikan lisensi resmi.
                                </p>
                                <p class="text-slate-600 mb-3">
                                    Dilarang:
                                </p>
                                <ul class="text-slate-600 list-disc list-inside space-y-1 ml-4">
                                    <li>Menyalin, menggandakan, atau mendistribusikan konten tanpa izin tertulis</li>
                                    <li>Memodifikasi atau membuat karya turunan dari materi situs</li>
                                    <li>Menggunakan merek dagang atau logo tanpa persetujuan resmi</li>
                                </ul>
                            </div>

                            <div class="mb-6">
                                <h5 class="font-bold text-lg text-[#0a0f1e] mb-3">3. Pembatasan Tanggung Jawab</h5>
                                <p class="text-slate-600 mb-3">
                                    MARIMOI berupaya menjaga agar seluruh informasi di situs ini akurat dan terkini,
                                    namun kami tidak memberikan jaminan bahwa informasi tersebut bebas dari kesalahan atau
                                    kelalaian.
                                </p>
                                <p class="text-slate-600 mb-3">
                                    MARIMOI tidak bertanggung jawab atas:
                                </p>
                                <ul class="text-slate-600 list-disc list-inside space-y-1 ml-4">
                                    <li>Kerugian langsung maupun tidak langsung akibat penggunaan informasi</li>
                                    <li>Gangguan teknis yang mengakibatkan layanan tidak tersedia sementara</li>
                                    <li>Tautan eksternal yang mengarah ke situs pihak ketiga</li>
                                </ul>
                            </div>

                            <div class="mb-6">
                                <h5 class="font-bold text-lg text-[#0a0f1e] mb-3">4. Perubahan Syarat dan Ketentuan</h5>
                                <p class="text-slate-600">
                                    MARIMOI berhak mengubah Syarat dan Ketentuan ini kapan saja tanpa pemberitahuan
                                    sebelumnya.
                                    Perubahan akan mulai berlaku sejak dipublikasikan di halaman ini.
                                    Pengguna disarankan memeriksa halaman ini secara berkala untuk mengetahui pembaruan.
                                </p>
                            </div>

                            <div class="mb-6">
                                <h5 class="font-bold text-lg text-[#0a0f1e] mb-3">5. Hukum yang Berlaku</h5>
                                <p class="text-slate-600">
                                    Syarat dan Ketentuan ini diatur dan ditafsirkan sesuai dengan hukum yang berlaku di
                                    Republik Indonesia.
                                    Setiap sengketa yang timbul dari penggunaan situs ini akan diselesaikan di wilayah hukum
                                    Republik Indonesia.
                                </p>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mt-8">
                                <p class="text-blue-800 mb-0">
                                    Dengan menggunakan situs ini, Anda menyatakan telah membaca, memahami, dan menyetujui
                                    seluruh Syarat dan Ketentuan yang berlaku.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    @include('frontend.partials.footer-dark-tailwind')
@endsection

@push('scripts')
    <!-- Vite JavaScript -->
    @vite(['resources/js/app.js'])
@endpush
