@extends('frontend.layouts.main')

@push('styles')
    <style>
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .card-text {
            font-size: 0.75rem;
            color: #555;
        }
    </style>
@endpush

@section('main')
    <!-- Navbar Section -->
    @include('frontend.partials.nav-map')

    <!-- Hero Section -->
    <section id="hero" class="hero section dark-background position-relative">

        <img src="{{ asset('frontend/img/hero2.png') }}" alt="" class="hero-bg" data-aos="fade-in">

        <div class="overlay-dark position-absolute top-0 start-0 w-100 h-100"
            style="z-index: 1; background-color: rgba(0, 0, 0, 0.5);"></div>

        <div class="container position-relative" style="z-index: 2;">
            <div class="row gy-4 d-flex justify-content-center">
                <div class="col-lg-10 order-2 order-lg-1 d-flex flex-column justify-content-center">
                    <h2 class="text-center text-white" data-aos="fade-in" data-aos-delay="100">SISTEM INFORMASI MANAJEMEN
                        AKSELERASI
                        INFRASTRUKTUR UNTUK MONITORING DAN INTEGRASI WILAYAH</h2>
                    <p class="text-white text-center" data-aos="fade-in" data-aos-delay="200">
                        Platform digital berbasis web untuk perencanaan, pelaksanaan, pemantauan, dan evaluasi
                        pembangunan infrastruktur daerah secara partisipatif, terintegrasi, dan efektif di Provinsi Maluku
                        Utara.
                    </p>
                </div>

                <div class="col-lg-5 order-1 order-lg-2 hero-img" data-aos="zoom-out">
                    <img src="assets/img/hero-img.svg" class="img-fluid mb-3 mb-lg-0" alt="">
                </div>

            </div>
        </div>

    </section><!-- /Hero Section -->

    <!-- Fitur Section -->
    <section id="services" class="services section pt-3 pb-5">

        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
            <span>Fitur-Fitur<br></span>
            <h2>Fitur-Fitur</h2>
            <p>Jelajahi Fitur-Fitur Sistem Informasi Manajemen Akselerasi Untuk Monitoring dan Integrasi Wilayah (MARIMOI)
            </p>
        </div><!-- End Section Title -->

        <div class="container">

            <div class="row d-flex justify-content-center">

                @php
                    $cards = [
                        [
                            'img' => 'daerah.png',
                            'route' => 'tampil.psd',
                            'title' => 'Proyek Strategis Daerah',
                            'desc' => 'Pemantauan proyek penting di tingkat daerah.',
                        ],
                        [
                            'img' => 'nasional.jpg',
                            'route' => 'tampil.psn',
                            'title' => 'Proyek Strategis Nasional',
                            'desc' => 'Koordinasi proyek nasional secara terpadu.',
                        ],
                        [
                            'img' => 'wilayah.jpg',
                            'route' => 'tampil.prioritas',
                            'title' => 'Prioritas Daerah 2025-2029',
                            'desc' => 'Fokus pembangunan wilayah 2025–2029.',
                        ],
                        [
                            'img' => 'musyawarah.jpg',
                            'route' => 'tampil.musrenbang',
                            'title' => 'Usulan Musrenbang',
                            'desc' => 'Hasil usulan masyarakat dalam Musrenbang.',
                        ],
                        [
                            'img' => 'pokok-pikiran.jpg',
                            'route' => 'tampil.pokir',
                            'title' => 'Pokir DPRD',
                            'desc' => 'Pokok pikiran DPRD untuk pengembangan wilayah.',
                        ],
                        [
                            'img' => 'aspirasi.jpg',
                            'route' => 'tampil.aspirasi',
                            'title' => 'Aspirasi Masyarakat',
                            'desc' => 'Saluran pengaduan dan saran masyarakat.',
                        ],
                    ];
                @endphp

                @foreach ($cards as $index => $card)
                    <div class="col-lg-2 col-md-4 col-5 mb-3" data-aos="fade-up" data-aos-delay="{{ 100 + $index * 100 }}">
                        <div class="card card-hover shadow-md text-center">
                            <div class="card-img">
                                <img src="{{ asset('frontend/img/' . $card['img']) }}" alt="" class="img-fluid">
                            </div>
                            <h3 class="card-title px-2 py-1 m-0" style="font-size: 12px">
                                <a href="{{ route($card['route']) }}" class="stretched-link">{{ $card['title'] }}</a>
                            </h3>
                            <p class="card-text px-2 py-1 m-0" style="font-size: 12px">{{ $card['desc'] }}</p>
                        </div>
                    </div>
                @endforeach

            </div>


        </div>

    </section><!-- /Fitur Section -->

    <!--Indikator Pembangunan Section -->
    <section id="featured-services" class="featured-services section pt-3 pb-5">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up" data-aos-delay="200">
            <span>Indikator Pembangunan Strategis<br></span>
            <h2>Indikator Pembangunan Strategis</h2>
            <p>Kontribusi MARIMOI Terhadap Indikator-Indikator Pembangunan Strategis</p>
        </div><!-- End Section Title -->

        <div class="container">

            <div class="row gy-4">

                <div class="col-lg-6 col-md-6 service-item d-flex" data-aos="fade-up" data-aos-delay="100">
                    <div class="icon flex-shrink-0"><i class="fa-solid fa-map-location-dot"></i></div>
                    <div>
                        <h4 class="title">Indeks Pengembangan Wilayah</h4>
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
                    <div class="icon flex-shrink-0"><i class="fa-solid fa-person"></i></div>
                    <div>
                        <h4 class="title">Indeks Pelayanan Publik</h4>
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
                    <div class="icon flex-shrink-0"><i class="fa-solid fa-computer"></i></div>
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
                    <div class="icon flex-shrink-0"><i class="fa-solid fa-road-bridge"></i></div>
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

    </section><!-- /Indikator Pembangunan Section -->

    <!-- About Section -->
    <section id="about" class="about section">

        <div class="container">

            <div class="row gy-4">

                <div class="col-lg-6 position-relative align-self-start order-lg-last order-first" data-aos="fade-up"
                    data-aos-delay="200">
                    <img src="{{ asset('frontend/img/kantor-gub-malut.jpeg') }}" class="img-fluid" alt="">
                    <a href="https://www.youtube.com/watch?v=EQbw-E1ecB8" class="glightbox pulsating-play-btn"></a>
                </div>

                <div class="col-lg-6 content order-last  order-lg-first" data-aos="fade-up" data-aos-delay="100">
                    <h3>TENTANG BAPPEDA</h3>
                    <p class="text-justify">
                        Bappeda Provinsi Maluku Utara adalah OPD yang mengoordinasikan perencanaan pembangunan, serta
                        pemantauan dan evaluasi pelaksanaannya. Dibentuk melalui Pergub No. 63 Tahun 2021, Bappeda berperan
                        membantu Gubernur menyusun kebijakan pembangunan berdasarkan RPJPD, RPJMD, serta peraturan
                        perundangan yang berlaku.
                    </p>


                </div>

            </div>

        </div>

    </section><!-- /About Section -->

    <!-- Footer Section -->
    @include('frontend.partials.footer')
@endsection
