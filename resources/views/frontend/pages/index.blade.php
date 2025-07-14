@extends('frontend.layouts.main')

@section('main')
    <!-- Hero Section -->
    @include('frontend.partials.nav-map')

    <!-- Hero Section -->
    <section id="hero" class="hero section dark-background position-relative">

        <img src="{{ asset('frontend/img/hero2.png') }}" alt="" class="hero-bg" data-aos="fade-in">

        <div class="overlay-dark position-absolute top-0 start-0 w-100 h-100"
            style="z-index: 1; background-color: rgba(0, 0, 0, 0.5);"></div>

        <div class="container position-relative" style="z-index: 2;">
            <div class="row gy-4 d-flex justify-content-center">
                <div class="col-lg-10 order-2 order-lg-1 d-flex flex-column justify-content-center">
                    <h2 class="text-center text-white" data-aos="fade-up">SISTEM INFORMASI MANAJEMEN AKSELERASI
                        INFRASTRUKTUR UNTUK MONITORING DAN INTEGRASI WILAYAH</h2>
                    <p class="text-white text-center" data-aos="fade-up" data-aos-delay="100">Sistem digital terpadu
                        berbasis web dan mobile yang dikembangkan untuk mendukung perencanaan, pelaksanaan, pemantauan, dan
                        evaluasi pembangunan infrastruktur daerah secara lebih efektif, partisipatif, dan terintegrasi.
                        Sistem ini menyasar penguatan sinergi lintas sektor dan wilayah dalam mendukung pembangunan wilayah
                        Provinsi Maluku Utara.</p>
                </div>

                <div class="col-lg-5 order-1 order-lg-2 hero-img" data-aos="zoom-out">
                    <img src="assets/img/hero-img.svg" class="img-fluid mb-3 mb-lg-0" alt="">
                </div>

            </div>
        </div>

    </section><!-- /Hero Section -->

    <!-- Featured Services Section -->
    <section id="featured-services" class="featured-services section">

        <div class="container">

            <div class="row gy-4">

                <div class="col-lg-6 col-md-6 service-item d-flex" data-aos="fade-up" data-aos-delay="100">
                    <div class="icon flex-shrink-0"><i class="fa-solid fa-cart-flatbed"></i></div>
                    <div>
                        <h4 class="title">Indeks Pengembanyan Wilayah</h4>
                        <p class="description">MARIMOI
                            meningkatkan Indeks Pengembangan Wilayah
                            melalui:
                        </p>
                        <ul>
                            <li>Penyediaan data spasial dan sektoral untuk mengukur
                                keterjangkauan layanan dasar.</li>
                            <li>Akselerasi pembangunan infrastruktur di wilayah hinterland
                                dan kawasan tertinggal.</li>
                            <li>Integrasi lintas wilayah dalam perencanaan berbasis
                                konektivitas (antarpulau, antarkawasan).</li>
                        </ul>
                    </div>
                </div>
                <!-- End Service Item -->

                <div class="col-lg-6 col-md-6 service-item d-flex" data-aos="fade-up" data-aos-delay="200">
                    <div class="icon flex-shrink-0"><i class="fa-solid fa-truck"></i></div>
                    <div>
                        <h4 class="title">Indeks Pelyanan Public</h4>
                        <p class="description">MARIMOI memberi dampak pada Indeks Pelayanan
                            melalui:</p>
                        <ul>
                            <li>Partisipasi masyarakat dalam pelaporan kondisi infrastruktur
                                (jalan rusak, PSU, jembatan, dIl).</li>
                            <li>Penyediaan data real-time kepada unit pelayanan teknis
                                untuk respon cepat.</li>
                            <li>Penguatan kualitas layanan berbasis kebutuhan wilayah,
                                bukan hanya standar sektoral.</li>
                        </ul>
                    </div>
                </div><!-- End Service Item -->

                <div class="col-lg-6 col-md-6 service-item d-flex" data-aos="fade-up" data-aos-delay="300">
                    <div class="icon flex-shrink-0"><i class="fa-solid fa-truck-ramp-box"></i></div>
                    <div>
                        <h4 class="title">Indeks SPBE</h4>
                        <p class="description">MARIMOI mendorong pencapaian SPBE melalui:</p>
                        <ul>
                            <li>Digitalisasi proses perencanaan, monitoring, dan pelaporan
                                infrastruktur.</li>
                            <li>Interoperabilitas data antar instansi (Bappeda, OPD teknis,
                                DPRD).</li>
                            <li>Fitur dashboard publik sebagai bentuk pelayanan digital
                                transparan.</li>
                        </ul>
                    </div>
                </div><!-- End Service Item -->
                <div class="col-lg-6 col-md-6 service-item d-flex" data-aos="fade-up" data-aos-delay="300">
                    <div class="icon flex-shrink-0"><i class="fa-solid fa-truck-ramp-box"></i></div>
                    <div>
                        <h4 class="title">Indeks Kualitas Layanan Infrastruktur</h4>
                        <p class="description">MARIMOI memberi dampak pada Indeks Pelayanan
                            melalui:</p>
                        <ul>
                            <li>Partisipasi masyarakat dalam pelaporan kondisi infrastruktur
                                (jalan rusak, PSU, jembatan, dIl).</li>
                            <li>Penyediaan data real-time kepada unit pelayanan teknis
                                untuk respon cepat.</li>
                            <li>Penguatan kualitas layanan berbasis kebutuhan wilayah,
                                bukan hanya standar sektoral.</li>
                        </ul>
                    </div>
                </div><!-- End Service Item -->


            </div>

        </div>

    </section><!-- /Featured Services Section -->

    <!-- About Section -->
    <section id="about" class="about section">

        <div class="container">

            <div class="row gy-4">

                <div class="col-lg-6 position-relative align-self-start order-lg-last order-first" data-aos="fade-up"
                    data-aos-delay="200">
                    <img src="assets/img/about.jpg" class="img-fluid" alt="">
                    <a href="https://www.youtube.com/watch?v=Y7f98aduVJ8" class="glightbox pulsating-play-btn"></a>
                </div>

                <div class="col-lg-6 content order-last  order-lg-first" data-aos="fade-up" data-aos-delay="100">
                    <h3>TENTANG BAPEDA</h3>
                    <p>
                        Badan Perencanaan Pembangunan Daerah merupakan Organisasi
                        Perangkat Daerah yang mengemban tugas mengkoordinasikan penyusunan
                        dokumen Perencanaan Pembangunan Daerah serta melakukan pemantauan dan
                        evaluasi pelaksanaan rencana pada periodesasi tertentu sesuai ketentuan
                        perundang - undangan yang berlaku.
                    </p>
                    <br>
                    <p>
                        Bappeda Provinsi Maluku Utara dibentuk berdasarkan Peraturan Gubernur Nomor 63 Tahun 2021 dan
                        bertugas membantu Gubernur dalam melaksanakan fungsi penunjang urusan pemerintahan di bidang
                        perencanaan dan pembangunan daerah, dengan mengacu pada RPJPD, RPJMD, kebijakan Gubernur, kondisi
                        objektif, serta peraturan perundang-undangan yang berlaku.
                    </p>

                </div>

            </div>

        </div>

    </section><!-- /About Section -->

    <!-- About Section -->
    @include('frontend.partials.footer')
@endsection
