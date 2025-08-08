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
                            </p>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">1. Penggunaan Layanan</h5>
                                <p class="text-muted">
                                    Website MARIMOI disediakan untuk memberikan informasi dan layanan terkait koordinasi,
                                    pemantauan, serta integrasi pembangunan infrastruktur di Maluku Utara.
                                    Pengguna wajib menggunakan layanan ini secara bertanggung jawab dan sesuai hukum yang
                                    berlaku.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">2. Hak Kekayaan Intelektual</h5>
                                <p class="text-muted">
                                    Seluruh konten di situs ini adalah milik MARIMOI atau pihak ketiga yang memberikan
                                    lisensi.
                                    Dilarang menyalin, memodifikasi, atau menyebarkan tanpa izin tertulis resmi.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">3. Pembatasan Tanggung Jawab</h5>
                                <p class="text-muted">
                                    MARIMOI tidak bertanggung jawab atas kerugian langsung maupun tidak langsung yang timbul
                                    dari
                                    penggunaan informasi atau layanan di situs ini. Kami berupaya menjaga akurasi, namun
                                    tidak
                                    menjamin sepenuhnya bebas dari kesalahan.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">4. Perubahan Syarat dan Ketentuan
                                </h5>
                                <p class="text-muted">
                                    MARIMOI berhak mengubah Syarat dan Ketentuan ini kapan saja tanpa pemberitahuan
                                    sebelumnya.
                                    Pengguna disarankan memeriksa halaman ini secara berkala.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">5. Hukum yang Berlaku</h5>
                                <p class="text-muted">
                                    Syarat dan Ketentuan ini diatur dan ditafsirkan sesuai dengan hukum yang berlaku di
                                    Republik
                                    Indonesia.
                                </p>
                            </div>

                            <div class="alert alert-info rounded-3 mt-5">
                                Dengan menggunakan situs ini, Anda menyatakan telah membaca dan menyetujui seluruh
                                Syarat dan Ketentuan yang berlaku.
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

@push('scripts')
@endpush
