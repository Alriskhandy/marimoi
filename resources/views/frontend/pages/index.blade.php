@extends('frontend.layouts.app')

@section('main')
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
                <a href="#" class="feature-link">
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
                <a href="#" class="feature-link">
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
                <a href="#" class="feature-link">
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
                <a href="#" class="feature-link">
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
                <a href="#" class="feature-link">
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
                <a href="#" class="feature-link">
                    Lihat Detail <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Monitoring Section -->
<section id="monitoring" class="monitoring-section">
    <div class="container">
        <div class="section-title animate-on-scroll">
            <h2>Dashboard Monitoring Real-time</h2>
            <p>
                Pantau progres infrastruktur Maluku Utara secara
                langsung
            </p>
        </div>

        <div class="monitoring-dashboard animate-on-scroll">
            <div class="dashboard-header">
                <h3 class="dashboard-title">
                    <i class="fas fa-chart-bar me-2"></i>
                    Proyek Infrastruktur Aktif
                </h3>
                <div class="status-filters">
                    <button class="status-btn active" onclick="filterProjects('semua')">
                        Semua
                    </button>
                    <button class="status-btn" onclick="filterProjects('aktif')">
                        Aktif
                    </button>
                    <button class="status-btn" onclick="filterProjects('perencanaan')">
                        Perencanaan
                    </button>
                    <button class="status-btn" onclick="filterProjects('selesai')">
                        Selesai
                    </button>
                </div>
            </div>

            <div class="project-grid" id="projectGrid">
                <div class="project-card" data-status="aktif">
                    <span class="project-status status-aktif">Sedang Berjalan</span>
                    <h4 class="project-title">Jalan Trans Halmahera</h4>
                    <p class="project-location">
                        <i class="fas fa-map-marker-alt me-1"></i>Halmahera Utara
                    </p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 75%"></div>
                    </div>
                    <p class="progress-text">75% selesai</p>
                </div>

                <div class="project-card" data-status="perencanaan">
                    <span class="project-status status-perencanaan">Perencanaan</span>
                    <h4 class="project-title">Pelabuhan Tobelo</h4>
                    <p class="project-location">
                        <i class="fas fa-map-marker-alt me-1"></i>Halmahera Utara
                    </p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 25%"></div>
                    </div>
                    <p class="progress-text">25% selesai</p>
                </div>

                <div class="project-card" data-status="aktif">
                    <span class="project-status status-aktif">Sedang Berjalan</span>
                    <h4 class="project-title">Bandara Gamar Malamo</h4>
                    <p class="project-location">
                        <i class="fas fa-map-marker-alt me-1"></i>Galela
                    </p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 90%"></div>
                    </div>
                    <p class="progress-text">90% selesai</p>
                </div>

                <div class="project-card" data-status="selesai">
                    <span class="project-status status-selesai">Selesai</span>
                    <h4 class="project-title">Jembatan Dufa-Dufa</h4>
                    <p class="project-location">
                        <i class="fas fa-map-marker-alt me-1"></i>Ternate
                    </p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 100%"></div>
                    </div>
                    <p class="progress-text">100% selesai</p>
                </div>

                <div class="project-card" data-status="aktif">
                    <span class="project-status status-aktif">Sedang Berjalan</span>
                    <h4 class="project-title">
                        Terminal Tipe A Sofifi
                    </h4>
                    <p class="project-location">
                        <i class="fas fa-map-marker-alt me-1"></i>Tidore
                        Kepulauan
                    </p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 60%"></div>
                    </div>
                    <p class="progress-text">60% selesai</p>
                </div>

                <div class="project-card" data-status="perencanaan">
                    <span class="project-status status-perencanaan">Perencanaan</span>
                    <h4 class="project-title">RSUD Chasan Boesoirie</h4>
                    <p class="project-location">
                        <i class="fas fa-map-marker-alt me-1"></i>Ternate
                    </p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 15%"></div>
                    </div>
                    <p class="progress-text">15% selesai</p>
                </div>
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
                Platform terintegrasi untuk manajemen infrastruktur yang
                efektif dan transparan
            </p>
        </div>

        <div class="stats-container animate-on-scroll">
            <div class="stats-grid">
                <div class="stat-item" onclick="animateStatNumbers()">
                    <span class="stat-number" data-target="127">0</span>
                    <div class="stat-label">Proyek Aktif</div>
                </div>
                <div class="stat-item" onclick="animateStatNumbers()">
                    <span class="stat-number" data-target="85">0</span>
                    <div class="stat-label">% Progres Rata-rata</div>
                </div>
                <div class="stat-item" onclick="animateStatNumbers()">
                    <span class="stat-number" data-target="15">0</span>
                    <div class="stat-label">Kabupaten/Kota</div>
                </div>
                <div class="stat-item" onclick="animateStatNumbers()">
                    <span class="stat-number" data-target="42">0</span>
                    <div class="stat-label">Proyek Selesai</div>
                </div>
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
            <div class="col-lg-6 col-md-6">
                <div class="service-item d-flex animate-on-scroll"
                    style="
                                background: white;
                                padding: 30px;
                                border-radius: 16px;
                                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                                border: 2px solid var(--border-light);
                                transition: all 0.3s ease;
                            ">
                    <div class="icon flex-shrink-0 me-4">
                        <div
                            style="
                                        width: 60px;
                                        height: 60px;
                                        background: var(--primary-blue);
                                        border-radius: 12px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        color: white;
                                        font-size: 1.5rem;
                                    ">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="title"
                            style="
                                        color: var(--gray-dark);
                                        font-weight: 600;
                                        margin-bottom: 12px;
                                    ">
                            Indeks Pengembangan Wilayah
                        </h4>
                        <p class="description"
                            style="
                                        color: var(--text-secondary);
                                        margin-bottom: 16px;
                                    ">
                            MARIMOI meningkatkan Indeks Pengembangan
                            Wilayah melalui:
                        </p>
                        <ul
                            style="
                                        color: var(--text-secondary);
                                        line-height: 1.6;
                                    ">
                            <li>
                                Penyediaan data spasial dan sektoral
                                untuk mengukur keterjangkauan layanan
                                dasar.
                            </li>
                            <li>
                                Akselerasi pembangunan infrastruktur di
                                wilayah hinterland dan kawasan
                                tertinggal.
                            </li>
                            <li>
                                Integrasi lintas wilayah dalam
                                perencanaan berbasis konektivitas
                                (antarpulau, antarkawasan).
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6">
                <div class="service-item d-flex animate-on-scroll"
                    style="
                                background: white;
                                padding: 30px;
                                border-radius: 16px;
                                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                                border: 2px solid var(--border-light);
                                transition: all 0.3s ease;
                            ">
                    <div class="icon flex-shrink-0 me-4">
                        <div
                            style="
                                        width: 60px;
                                        height: 60px;
                                        background: var(--green-accent);
                                        border-radius: 12px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        color: white;
                                        font-size: 1.5rem;
                                    ">
                            <i class="fa-solid fa-person"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="title"
                            style="
                                        color: var(--gray-dark);
                                        font-weight: 600;
                                        margin-bottom: 12px;
                                    ">
                            Indeks Pelayanan Publik
                        </h4>
                        <p class="description"
                            style="
                                        color: var(--text-secondary);
                                        margin-bottom: 16px;
                                    ">
                            MARIMOI memberi dampak pada Indeks Pelayanan
                            melalui:
                        </p>
                        <ul
                            style="
                                        color: var(--text-secondary);
                                        line-height: 1.6;
                                    ">
                            <li>
                                Partisipasi masyarakat dalam pelaporan
                                kondisi infrastruktur (jalan rusak, PSU,
                                jembatan, dll).
                            </li>
                            <li>
                                Penyediaan data real-time kepada unit
                                pelayanan teknis untuk respon cepat.
                            </li>
                            <li>
                                Penguatan kualitas layanan berbasis
                                kebutuhan wilayah, bukan hanya standar
                                sektoral.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6">
                <div class="service-item d-flex animate-on-scroll"
                    style="
                                background: white;
                                padding: 30px;
                                border-radius: 16px;
                                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                                border: 2px solid var(--border-light);
                                transition: all 0.3s ease;
                            ">
                    <div class="icon flex-shrink-0 me-4">
                        <div
                            style="
                                        width: 60px;
                                        height: 60px;
                                        background: var(--cyan-accent);
                                        border-radius: 12px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        color: white;
                                        font-size: 1.5rem;
                                    ">
                            <i class="fa-solid fa-computer"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="title"
                            style="
                                        color: var(--gray-dark);
                                        font-weight: 600;
                                        margin-bottom: 12px;
                                    ">
                            Indeks SPBE
                        </h4>
                        <p class="description"
                            style="
                                        color: var(--text-secondary);
                                        margin-bottom: 16px;
                                    ">
                            MARIMOI mendorong pencapaian SPBE melalui:
                        </p>
                        <ul
                            style="
                                        color: var(--text-secondary);
                                        line-height: 1.6;
                                    ">
                            <li>
                                Digitalisasi proses perencanaan,
                                monitoring, dan pelaporan infrastruktur.
                            </li>
                            <li>
                                Interoperabilitas data antar instansi
                                (Bappeda, OPD teknis, DPRD).
                            </li>
                            <li>
                                Fitur dashboard publik sebagai bentuk
                                pelayanan digital transparan.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6">
                <div class="service-item d-flex animate-on-scroll"
                    style="
                                background: white;
                                padding: 30px;
                                border-radius: 16px;
                                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                                border: 2px solid var(--border-light);
                                transition: all 0.3s ease;
                            ">
                    <div class="icon flex-shrink-0 me-4">
                        <div
                            style="
                                        width: 60px;
                                        height: 60px;
                                        background: var(--yellow-accent);
                                        border-radius: 12px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        color: white;
                                        font-size: 1.5rem;
                                    ">
                            <i class="fa-solid fa-road-bridge"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="title"
                            style="
                                        color: var(--gray-dark);
                                        font-weight: 600;
                                        margin-bottom: 12px;
                                    ">
                            Indeks Kualitas Layanan Infrastruktur
                        </h4>
                        <p class="description"
                            style="
                                        color: var(--text-secondary);
                                        margin-bottom: 16px;
                                    ">
                            MARIMOI memberi dampak pada Indeks Pelayanan
                            melalui:
                        </p>
                        <ul
                            style="
                                        color: var(--text-secondary);
                                        line-height: 1.6;
                                    ">
                            <li>
                                Partisipasi masyarakat dalam pelaporan
                                kondisi infrastruktur (jalan rusak, PSU,
                                jembatan, dll).
                            </li>
                            <li>
                                Penyediaan data real-time kepada unit
                                pelayanan teknis untuk respon cepat.
                            </li>
                            <li>
                                Penguatan kualitas layanan berbasis
                                kebutuhan wilayah, bukan hanya standar
                                sektoral.
                            </li>
                        </ul>
                    </div>
                </div>
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
                        MARIMOI (Manajemen Akselerasi Infrastruktur
                        untuk Monitoring dan Integrasi Wilayah) adalah
                        sistem informasi yang dikembangkan oleh Bappeda
                        Provinsi Maluku Utara untuk mendukung
                        transparansi dan efektivitas pembangunan
                        infrastruktur daerah.
                    </p>
                    <p class="mb-4">
                        Platform ini memungkinkan integrasi data dari
                        berbagai sumber, mulai dari proyek strategis
                        nasional dan daerah, hingga aspirasi langsung
                        dari masyarakat melalui Musrenbang dan saluran
                        pengaduan digital.
                    </p>
                    <div class="row mt-4">
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle me-3 fs-5" style="color: var(--green-accent)"></i>
                                <span>Real-time Monitoring</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle me-3 fs-5" style="color: var(--green-accent)"></i>
                                <span>Partisipasi Masyarakat</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle me-3 fs-5" style="color: var(--green-accent)"></i>
                                <span>Integrasi Data</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle me-3 fs-5" style="color: var(--green-accent)"></i>
                                <span>Transparansi Publik</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="animate-on-scroll">
                    <div class="position-relative">
                        <div class="ratio ratio-16x9 rounded-4 border border-primary">
                            <iframe src="https://www.youtube.com/embed/3UlvbSy2ms0?si=SO54pz2P7VLNnrkZ"
                                title="YouTube video player" frameborder="0"
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
@endsection
