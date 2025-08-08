<!-- Floating Actions -->
<div class="floating-actions">
    <div class="fab-menu" id="fabMenu">
        <div class="fab-item" onclick="halamanAspirasi()" title="Kirim Aspirasi">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="fab-item" onclick="halamanFAQ()" title="Bantuan">
            <i class="fas fa-question-circle"></i>
        </div>
    </div>
    <button class="fab-main" onclick="toggleFabMenu()">
        <i class="fas fa-plus"></i>
    </button>
</div>

<footer id="contact" class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <h5>
                    <img width="50" src="{{ asset('frontend/img/logo.webp') }}" alt="Logo MARIMOI" />
                    MARIMOI
                </h5>
                <p class="mb-4">
                    Sistem Informasi Manajemen Akselerasi Infrastruktur
                    untuk Monitoring dan Integrasi Wilayah Provinsi
                    Maluku Utara.
                </p>
                <p>
                    <strong>Bappeda Provinsi Maluku Utara</strong><br />
                    Jl. Raya Ternate-Tobelo, Sofifi<br />
                    Maluku Utara 97815
                </p>
            </div>

            <div class="col-lg-2 col-md-6 mb-4">
                <h5>Menu Utama</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#home">Beranda</a></li>
                    <li class="mb-2"><a href="#features">Fitur</a></li>
                    <li class="mb-2"><a href="#monitoring">Proyek Populer</a></li>
                    <li class="mb-2"><a href="{{ route('tampil.aspirasi') }}">Kirim Aspirasi</a></li>
                    <li class="mb-2"><a href="#about">Tentang</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Kategori Proyek</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#">Proyek Strategis Daerah</a>
                    </li>
                    <li class="mb-2">
                        <a href="#">Proyek Strategis Nasional</a>
                    </li>
                    <li class="mb-2">
                        <a href="#">Prioritas Daerah</a>
                    </li>
                    <li class="mb-2">
                        <a href="#">Usulan Musrenbang</a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Kontak</h5>
                <p class="mb-2">
                    <i class="fas fa-phone me-2"></i>+62 921 123 4567
                </p>
                <p class="mb-2">
                    <i class="fas fa-envelope me-2"></i>bappeda@malutprov.go.id
                </p>
                <p class="mb-2">
                    <i class="fas fa-globe me-2"></i>www.bappeda.malutprov.go.id
                </p>
                <div class="mt-3">
                    <a href="#" class="text-decoration-none me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-decoration-none me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-decoration-none me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-decoration-none"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>

        <hr class="my-4" style="border-color: #374151" />

        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">
                    &copy; 2025 MARIMOI - Bappeda Provinsi Maluku Utara.
                    All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="{{ route('kebijakan_privasi') }}" class="me-3">Kebijakan Privasi</a>
                <a href="{{ route('syarat_ketentuan') }}">Syarat & Ketentuan</a>
            </div>
        </div>
    </div>
</footer>
