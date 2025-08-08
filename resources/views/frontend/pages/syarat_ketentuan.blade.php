@extends('frontend.layouts.app')

@section('main')
    <!-- Terms and Conditions Section -->
    <section id="terms" class="mt-5 py-5" style="background-color: #f9fafb;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-lg border-0 rounded-4 animate-on-scroll">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4 fw-bold" style="color: var(--primary-color);">
                                Syarat dan Ketentuan Website MARIMOI
                            </h2>
                            <p class="text-center text-muted mb-5">
                                Dengan mengakses situs ini, Anda setuju untuk mematuhi Syarat dan Ketentuan berikut.
                                Syarat ini berlaku untuk semua pengunjung, pengguna, dan pihak lain yang mengakses atau
                                menggunakan layanan MARIMOI. Bacalah dengan seksama sebelum melanjutkan penggunaan.
                            </p>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">1. Penggunaan Layanan</h5>
                                <p class="text-muted">
                                    Website MARIMOI disediakan untuk memberikan informasi dan layanan terkait koordinasi,
                                    pemantauan, serta integrasi pembangunan infrastruktur di Maluku Utara.
                                    Pengguna wajib:
                                </p>
                                <ul class="text-muted">
                                    <li>Menggunakan layanan hanya untuk tujuan yang sah dan sesuai hukum</li>
                                    <li>Tidak melakukan tindakan yang dapat merusak, menonaktifkan, atau mengganggu fungsi
                                        situs</li>
                                    <li>Tidak mencoba mengakses data atau sistem yang tidak diizinkan</li>
                                </ul>
                                <p class="text-muted">
                                    Pelanggaran terhadap ketentuan ini dapat mengakibatkan penghentian akses secara
                                    permanen.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">2. Hak Kekayaan Intelektual</h5>
                                <p class="text-muted">
                                    Seluruh konten di situs ini, termasuk teks, gambar, ikon, video, logo, dan desain
                                    antarmuka
                                    adalah milik MARIMOI atau pihak ketiga yang memberikan lisensi resmi.
                                </p>
                                <p class="text-muted">
                                    Dilarang:
                                </p>
                                <ul class="text-muted">
                                    <li>Menyalin, menggandakan, atau mendistribusikan konten tanpa izin tertulis</li>
                                    <li>Memodifikasi atau membuat karya turunan dari materi situs</li>
                                    <li>Menggunakan merek dagang atau logo tanpa persetujuan resmi</li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">3. Pembatasan Tanggung Jawab</h5>
                                <p class="text-muted">
                                    MARIMOI berupaya menjaga agar seluruh informasi di situs ini akurat dan terkini,
                                    namun kami tidak memberikan jaminan bahwa informasi tersebut bebas dari kesalahan atau
                                    kelalaian.
                                </p>
                                <p class="text-muted">
                                    MARIMOI tidak bertanggung jawab atas:
                                </p>
                                <ul class="text-muted">
                                    <li>Kerugian langsung maupun tidak langsung akibat penggunaan informasi</li>
                                    <li>Gangguan teknis yang mengakibatkan layanan tidak tersedia sementara</li>
                                    <li>Tautan eksternal yang mengarah ke situs pihak ketiga</li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">4. Perubahan Syarat dan Ketentuan
                                </h5>
                                <p class="text-muted">
                                    MARIMOI berhak mengubah Syarat dan Ketentuan ini kapan saja tanpa pemberitahuan
                                    sebelumnya.
                                    Perubahan akan mulai berlaku sejak dipublikasikan di halaman ini.
                                    Pengguna disarankan memeriksa halaman ini secara berkala untuk mengetahui pembaruan.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">5. Hukum yang Berlaku</h5>
                                <p class="text-muted">
                                    Syarat dan Ketentuan ini diatur dan ditafsirkan sesuai dengan hukum yang berlaku di
                                    Republik Indonesia.
                                    Setiap sengketa yang timbul dari penggunaan situs ini akan diselesaikan di wilayah hukum
                                    Republik Indonesia.
                                </p>
                            </div>

                            <div class="alert alert-info rounded-3 mt-5">
                                Dengan menggunakan situs ini, Anda menyatakan telah membaca, memahami, dan menyetujui
                                seluruh Syarat dan Ketentuan yang berlaku.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    @include('frontend.partials.footer')
@endsection
