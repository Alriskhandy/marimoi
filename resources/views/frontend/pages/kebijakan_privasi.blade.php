@extends('frontend.layouts.app')

@section('main')
    <!-- Privacy Policy Section -->
    <section id="privacy" class="mt-5 py-5" style="background-color: #f9fafb;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-lg border-0 rounded-4 animate-on-scroll">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4 fw-bold" style="color: var(--primary-color);">
                                Kebijakan Privasi Website MARIMOI
                            </h2>
                            <p class="text-center text-muted mb-5">

                                Kebijakan ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi data
                                pribadi Anda.
                            </p>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">1. Pengumpulan Informasi</h5>
                                <p class="text-muted">
                                    Kami dapat mengumpulkan informasi pribadi seperti nama, alamat email, nomor telepon, dan
                                    data lainnya
                                    ketika Anda mengakses atau menggunakan layanan MARIMOI. Informasi ini dikumpulkan untuk
                                    keperluan
                                    verifikasi, komunikasi, dan peningkatan layanan.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">2. Penggunaan Informasi</h5>
                                <p class="text-muted">
                                    Data pribadi yang dikumpulkan digunakan untuk:
                                </p>
                                <ul class="text-muted">
                                    <li>Meningkatkan kualitas layanan</li>
                                    <li>Memberikan informasi terbaru terkait program atau kegiatan MARIMOI</li>
                                    <li>Memproses permintaan atau pengaduan</li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">3. Keamanan Data</h5>
                                <p class="text-muted">
                                    Kami menggunakan langkah-langkah keamanan yang sesuai untuk melindungi data pribadi dari
                                    akses
                                    tidak sah, pengungkapan, atau kerusakan. Namun, tidak ada metode transmisi data melalui
                                    internet
                                    yang sepenuhnya aman.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">4. Berbagi Informasi dengan Pihak
                                    Ketiga</h5>
                                <p class="text-muted">
                                    MARIMOI tidak akan menjual atau menyewakan data pribadi Anda kepada pihak ketiga.
                                    Informasi hanya dibagikan jika diwajibkan oleh hukum atau untuk melindungi kepentingan
                                    hukum MARIMOI.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: var(--primary-color);">5. Perubahan Kebijakan Privasi</h5>
                                <p class="text-muted">
                                    Kebijakan ini dapat diperbarui dari waktu ke waktu. Perubahan akan diumumkan di halaman
                                    ini
                                    dengan tanggal pembaruan terbaru.
                                </p>
                            </div>

                            <div class="alert alert-info rounded-3 mt-5">
                                Dengan menggunakan situs ini, Anda menyatakan telah membaca dan memahami
                                Kebijakan Privasi MARIMOI.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Floating Actions -->
    <div class="floating-actions">
        <div class="fab-menu" id="fabMenu">
            <div class="fab-item" onclick="reportIssue()" title="Laporkan Masalah">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="fab-item" onclick="submitAspiration()" title="Kirim Aspirasi">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="fab-item" onclick="viewHelp()" title="Bantuan">
                <i class="fas fa-question-circle"></i>
            </div>
        </div>
        <button class="fab-main" onclick="toggleFabMenu()">
            <i class="fas fa-plus"></i>
        </button>
    </div>
    <!-- Footer -->
    @include('frontend.partials.footer')
@endsection

@push('scripts')
@endpush
