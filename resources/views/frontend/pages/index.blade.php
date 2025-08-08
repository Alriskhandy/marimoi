@extends('frontend.layouts.app')

@section('main')
    <!-- Hero Section -->
    @include('frontend.partials.hero')

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Fitur Utama MARIMOI</h2>
                <p>
                    Platform terintegrasi untuk manajemen infrastruktur yang
                    efektif dan transparan
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h4>Proyek Strategis Daerah</h4>
                    <p>
                        Pemetaan proyek strategis daerah.
                    </p>
                    <a href="/proyek-strategis-daerah" class="feature-link">
                        Lihat Detail <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-flag"></i>
                    </div>
                    <h4>Proyek Strategis Nasional</h4>
                    <p>
                        Pemetaan proyek nasional di Maluku Utara.
                    </p>
                    <a href="/proyek-strategis-nasional" class="feature-link">
                        Lihat Detail <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h4>Prioritas Daerah 2025-2029</h4>
                    <p>
                        Program prioritas jangka menengah daerah.
                    </p>
                    <a href="/prioritas-daerah" class="feature-link">
                        Lihat Detail <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4>Usulan Musrenbang</h4>
                    <p>
                        Pemetaan usulan pembangunan hasil Musrenbang.
                    </p>
                    <a href="/usulan-musrenbang" class="feature-link">
                        Lihat Detail <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-gavel"></i>
                    </div>
                    <h4>Pokir DPRD</h4>
                    <p>
                        Pemetaan usulan pembangunan pokok pikiran DPRD.
                    </p>
                    <a href="/pokir-dprd" class="feature-link">
                        Lihat Detail <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h4>Aspirasi Masyarakat</h4>
                    <p>
                        Formulir Saran, Kritik, dan Aspirasi Pembangunan.
                    </p>
                    <a href="/aspirasi-masyarakat" class="feature-link">
                        Lihat Detail <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Indikator Pembangunan Section -->
    <section id="indikator" class="features-section" style="background: var(--gray-light)">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Indikator Pembangunan Strategis</h2>
                <p>
                    Kontribusi MARIMOI Terhadap Indikator-Indikator
                    Pembangunan Strategis
                </p>
            </div>

            <div class="row gy-4">
                <!-- Card 1: Indeks Pengembangan Wilayah -->
                <div class="col-lg-6 col-md-6">
                    <div class="service-item d-flex animate-on-scroll">
                        <div class="icon flex-shrink-0 me-4">
                            <div class="service-icon" style="background: var(--primary-blue);">
                                <i class="fa-solid fa-map-location-dot"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="service-title">Indeks Pengembangan Wilayah</h4>
                            <p class="service-description">
                                MARIMOI meningkatkan Indeks Pengembangan Wilayah melalui:
                            </p>
                            <ul class="service-list">
                                <li>Penyediaan data spasial dan sektoral untuk mengukur keterjangkauan layanan dasar.</li>
                                <li>Akselerasi pembangunan infrastruktur di wilayah hinterland dan kawasan tertinggal.</li>
                                <li>Integrasi lintas wilayah dalam perencanaan berbasis konektivitas (antarpulau,
                                    antarkawasan).</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Indeks Pelayanan Publik -->
                <div class="col-lg-6 col-md-6">
                    <div class="service-item d-flex animate-on-scroll">
                        <div class="icon flex-shrink-0 me-4">
                            <div class="service-icon" style="background: var(--green-accent);">
                                <i class="fa-solid fa-person"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="service-title">Indeks Pelayanan Publik</h4>
                            <p class="service-description">
                                MARIMOI memberi dampak pada Indeks Pelayanan melalui:
                            </p>
                            <ul class="service-list">
                                <li>Partisipasi masyarakat dalam pelaporan kondisi infrastruktur (jalan rusak, PSU,
                                    jembatan, dll).</li>
                                <li>Penyediaan data real-time kepada unit pelayanan teknis untuk respon cepat.</li>
                                <li>Penguatan kualitas layanan berbasis kebutuhan wilayah, bukan hanya standar sektoral.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Indeks SPBE -->
                <div class="col-lg-6 col-md-6">
                    <div class="service-item d-flex animate-on-scroll">
                        <div class="icon flex-shrink-0 me-4">
                            <div class="service-icon" style="background: var(--cyan-accent);">
                                <i class="fa-solid fa-computer"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="service-title">Indeks SPBE</h4>
                            <p class="service-description">
                                MARIMOI mendorong pencapaian SPBE melalui:
                            </p>
                            <ul class="service-list">
                                <li>Digitalisasi proses perencanaan, monitoring, dan pelaporan infrastruktur.</li>
                                <li>Interoperabilitas data antar instansi (Bappeda, OPD teknis, DPRD).</li>
                                <li>Fitur dashboard publik sebagai bentuk pelayanan digital transparan.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Indeks Kualitas Layanan Infrastruktur -->
                <div class="col-lg-6 col-md-6">
                    <div class="service-item d-flex animate-on-scroll">
                        <div class="icon flex-shrink-0 me-4">
                            <div class="service-icon" style="background: var(--yellow-accent);">
                                <i class="fa-solid fa-road-bridge"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="service-title">Indeks Kualitas Layanan Infrastruktur</h4>
                            <p class="service-description">
                                Melalui Marimoi:
                            </p>
                            <ul class="service-list">
                                <li>Pemerintah dapat memantau kondisi infrastruktur secara spasial dan waktu nyata.</li>
                                <li>Sistem mendukung evaluasi kinerja infrastruktur berdasarkan output dan outcome.</li>
                                <li>Layanan infrastruktur menjadi lebih merata, berkualitas, dan efisien.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Backlink Section -->
    <section id="backlink" class="backlink-section">
        <div class="container">
            <div class="row">
                <div class="col d-flex flex-wrap justify-content-center align-items-center gap-4">
                    <a href="https://bappeda.malutprov.go.id/" class="web-link" target="_blank" rel="noopener">
                        <img src="{{ asset('frontend/img/logo.webp') }}" alt="Bappeda Maluku Utara" class="logo">
                        <span>BAPPEDA MALUT</span>
                    </a>
                    <a href="https://opendata.malutprov.go.id/" class="web-link" target="_blank" rel="noopener">
                        <img src="{{ asset('frontend/img/logo-opendata-malut.png') }}" alt="Opendata Maluku Utara" class="logo">
                        <span>OPENDATA MALUT</span>
                    </a>
                    <a href="https://malut.bps.go.id/id" class="web-link" target="_blank" rel="noopener">
                        <img src="{{ asset('frontend/img/logo-bps.webp') }}" alt="BPS Maluku Utara" class="logo">
                        <span>BADAN PUSAT STATISTIK</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats" class="features-section">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Statistik</h2>
                <p>
                    Peta interaktif dari berbagai proyek infrastruktur.
                </p>
            </div>

            <div class="stats-container animate-on-scroll">
                <div class="stats-grid">
                    <div class="stat-item" onclick="animateStatNumbers()">
                        <span class="stat-number" data-target="{{ $totalPsd }}">0</span>
                        <div class="stat-label">Proyek Strategis Daerah</div>
                    </div>
                    <div class="stat-item" onclick="animateStatNumbers()">
                        <span class="stat-number" data-target="{{ $totalPsn }}">0</span>
                        <div class="stat-label">Proyek Strategis Nasional</div>
                    </div>
                    <div class="stat-item" onclick="animateStatNumbers()">
                        <span class="stat-number" data-target="{{ $totalMusrenbang }}">0</span>
                        <div class="stat-label">Usulan Musrenbang</div>
                    </div>
                    <div class="stat-item" onclick="animateStatNumbers()">
                        <span class="stat-number" data-target="{{ $totalPokir }}">0</span>
                        <div class="stat-label">Pokir DPRD</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Monitoring Section -->
    <section id="monitoring" class="monitoring-section">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Proyek Paling Populer</h2>
                <p>
                    Lihat proyek yang paling menarik perhatian masyarakat.
                </p>
            </div>

            <div class="monitoring-dashboard animate-on-scroll">
                <div class="dashboard-header">
                    <h3 class="dashboard-title">
                        <i class="fas fa-chart-bar me-2"></i>
                        Kategori Proyek
                    </h3>
                    <div class="status-filters">
                        <button class="status-btn active" onclick="filterProjects(event, 'psd')">
                            Strategis Daerah
                        </button>
                        <button class="status-btn" onclick="filterProjects(event, 'psn')">
                            Strategis Nasional
                        </button>
                        <button class="status-btn" onclick="filterProjects(event, 'musrenbang')">
                            Usulan Musrenbang
                        </button>
                        <button class="status-btn" onclick="filterProjects(event, 'pokir_dprd')">
                            Pokir DPRD
                        </button>
                    </div>
                </div>

                <div class="project-grid" id="projectGrid">
                    @foreach ($dataPeta as $data)
                        @php
                            $status = $data->sub_type ?? $data->data_type;
                            $slug = $links[$status] ?? '#';
                        @endphp
                        <div class="project-card" data-status="{{ $data->sub_type ?? $data->data_type }}">
                            <h4 class="project-title">{{ $data->dbf_attributes['KEGIATAN'] ?? '' }}</h4>
                            <p class="project-location">
                                <i class="fas fa-map-marker-alt me-1"></i>{{ $data->dbf_attributes['KABUPATEN'] ?? '' }}
                            </p>

                            <div class="progress-group">
                                <div class="progress-header">
                                    <p class="progress-label">Realisasi Fisik</p>
                                    <p class="progress-text">75%</p>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 75%"></div>
                                </div>
                            </div>

                            <div class="progress-group">
                                <div class="progress-header">
                                    <p class="progress-label">Realisasi Anggaran</p>
                                    <p class="progress-text">75%</p>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 75%"></div>
                                </div>
                            </div>
                            <a class="project-button" href="{{ url($slug . '/' . $data->uuid) }}">Lihat Detail</a>
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="features-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="animate-on-scroll">
                        <h2 class="mb-4">Tentang MARIMOI</h2>
                        <p class="mb-4">
                            MARIMOI adalah sistem digital terpadu untuk memperkuat
                            koordinasi, pemantauan, dan integrasi pembangunan infrastruktur di Maluku Utara. Dengan
                            pendekatan spasial dan peta tematik, sistem ini menyediakan data real-time yang mendukung
                            perencanaan lintas sektor secara kolaboratif dan transparan.

                            Platform ini mempercepat perencanaan, mendorong partisipasi publik, dan meningkatkan
                            akuntabilitas, menjadi bagian dari transformasi digital berbasis data dan kebutuhan lokal.
                        </p>
                        <div class="row mt-4">
                            <div class="col-6">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-check-circle me-3 fs-5" style="color: var(--green-accent)"></i>
                                    <span>Integrasi data spasial & sektoral</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-check-circle me-3 fs-5" style="color: var(--green-accent)"></i>
                                    <span>Pemantauan proyek strategis</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-check-circle me-3 fs-5" style="color: var(--green-accent)"></i>
                                    <span>Kelola usulan Pokir & Musrenbang</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-check-circle me-3 fs-5" style="color: var(--green-accent)"></i>
                                    <span>Prioritas pembangunan 2025–2029</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-check-circle me-3 fs-5" style="color: var(--green-accent)"></i>
                                    <span>Evaluasi & pelaporan transparan</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-check-circle me-3 fs-5" style="color: var(--green-accent)"></i>
                                    <span>Dorong partisipasi & kolaborasi</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="animate-on-scroll">
                        <div class="position-relative">
                            <div class="ratio ratio-16x9 rounded-4 border border-primary">
                                <iframe src="https://www.youtube-nocookie.com/embed/EQbw-E1ecB8"
                                    title="YouTube video player"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen referrerpolicy="strict-origin-when-cross-origin">
                                </iframe>

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
