@extends('frontend.layouts.dark', ['title' => 'Kebijakan Privasi'])

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
    <!-- Privacy Policy Section -->
    <section id="privacy" class="min-h-auto mt-[76px] pt-8 pb-12 bg-slate-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-center">
                <div class="w-full max-w-4xl">
                    <div class="bg-white shadow-xl border-0 rounded-2xl">
                        <div class="p-8 md:p-12">
                            <h2 class="text-center mb-6 text-2xl md:text-3xl font-bold text-[#0a0f1e]">
                                Kebijakan Privasi Website MARIMOI
                            </h2>
                            <p class="text-justify text-slate-600 mb-8 text-sm md:text-base leading-relaxed">
                                Kebijakan ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi data
                                pribadi Anda ketika menggunakan situs web MARIMOI. Kami berkomitmen untuk menjaga
                                kerahasiaan
                                dan keamanan informasi pribadi sesuai dengan peraturan yang berlaku di Republik Indonesia.
                            </p>

                            <div class="mb-6">
                                <h5 class="font-bold text-lg text-[#0a0f1e] mb-3">1. Pengumpulan Informasi</h5>
                                <p class="text-slate-600 mb-3">
                                    Kami dapat mengumpulkan informasi pribadi seperti nama, alamat email, nomor telepon,
                                    lokasi geografis, dan data lainnya ketika Anda:
                                </p>
                                <ul class="text-slate-600 list-disc list-inside space-y-1 ml-4">
                                    <li>Mengisi formulir atau mendaftar akun di situs MARIMOI</li>
                                    <li>Menghubungi kami melalui email, telepon, atau media lainnya</li>
                                    <li>Menggunakan fitur atau layanan berbasis lokasi</li>
                                </ul>
                                <p class="text-slate-600 mt-3">
                                    Informasi ini dikumpulkan untuk keperluan verifikasi, komunikasi, analisis kebutuhan,
                                    dan peningkatan layanan yang kami berikan.
                                </p>
                            </div>

                            <div class="mb-6">
                                <h5 class="font-bold text-lg text-[#0a0f1e] mb-3">2. Penggunaan Informasi</h5>
                                <p class="text-slate-600 mb-3">
                                    Data pribadi yang dikumpulkan digunakan untuk:
                                </p>
                                <ul class="text-slate-600 list-disc list-inside space-y-1 ml-4">
                                    <li>Meningkatkan kualitas layanan dan pengalaman pengguna</li>
                                    <li>Memberikan informasi terbaru terkait program atau kegiatan MARIMOI</li>
                                    <li>Memproses permintaan, laporan, atau pengaduan pengguna</li>
                                    <li>Mendukung analisis statistik dan pengembangan kebijakan publik</li>
                                </ul>
                                <p class="text-slate-600 mt-3">
                                    Kami tidak akan menggunakan data Anda untuk tujuan komersial tanpa persetujuan Anda.
                                </p>
                            </div>

                            <div class="mb-6">
                                <h5 class="font-bold text-lg text-[#0a0f1e] mb-3">3. Keamanan Data</h5>
                                <p class="text-slate-600 mb-3">
                                    Kami menggunakan langkah-langkah keamanan teknis dan administratif yang sesuai untuk
                                    melindungi data pribadi dari akses tidak sah, pengungkapan, atau kerusakan.
                                    Namun, perlu diketahui bahwa tidak ada metode transmisi data melalui internet
                                    atau penyimpanan elektronik yang 100% aman.
                                </p>
                                <p class="text-slate-600">
                                    Oleh karena itu, Anda juga disarankan untuk menjaga kerahasiaan akun dan kata sandi
                                    Anda sendiri.
                                </p>
                            </div>

                            <div class="mb-6">
                                <h5 class="font-bold text-lg text-[#0a0f1e] mb-3">4. Berbagi Informasi dengan Pihak
                                    Ketiga</h5>
                                <p class="text-slate-600 mb-3">
                                    MARIMOI tidak akan menjual atau menyewakan data pribadi Anda kepada pihak ketiga.
                                    Informasi hanya akan dibagikan apabila:
                                </p>
                                <ul class="text-slate-600 list-disc list-inside space-y-1 ml-4">
                                    <li>Diperlukan oleh ketentuan hukum dan peraturan perundang-undangan</li>
                                    <li>Diperlukan untuk melindungi keamanan, hak, atau properti MARIMOI</li>
                                    <li>Dengan persetujuan tertulis dari Anda</li>
                                </ul>
                            </div>

                            <div class="mb-6">
                                <h5 class="font-bold text-lg text-[#0a0f1e] mb-3">5. Perubahan Kebijakan Privasi</h5>
                                <p class="text-slate-600">
                                    Kebijakan ini dapat diperbarui dari waktu ke waktu untuk mencerminkan perubahan
                                    layanan atau peraturan yang berlaku. Perubahan akan diumumkan di halaman ini
                                    dengan tanggal pembaruan terbaru. Kami mendorong pengguna untuk memeriksa halaman
                                    ini secara berkala.
                                </p>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mt-8">
                                <p class="text-blue-800 mb-0">
                                    Dengan menggunakan situs ini, Anda menyatakan telah membaca, memahami, dan menyetujui
                                    Kebijakan Privasi MARIMOI.
                                </p>
                            </div>
                        </div>
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
@endpush
