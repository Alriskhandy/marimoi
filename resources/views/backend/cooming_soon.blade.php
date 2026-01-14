@extends('backend.partials.main')

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-video-account"></i>
            </span> Musrenbang Digital
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span></span>Musrenbang Digital
                </li>
            </ul>
        </nav>
    </div>

    <!-- Musrenbang Digital Coming Soon Content -->
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-12">
            <div class="card musrenbang-card">
                <div class="card-body text-center p-5">
                    <!-- Main Icon -->
                    <div class="musrenbang-icon mb-4">
                        <div class="icon-container">
                            <i class="mdi mdi-video-account icon-main"></i>
                        </div>
                    </div>

                    <!-- Main Title -->
                    <h1 class="musrenbang-title mb-3">
                        <span class="text-gradient">Musrenbang Digital</span>
                    </h1>

                    <!-- Subtitle -->
                    <h4 class="musrenbang-subtitle mb-4">
                        Peluang Strategis sebagai Respons terhadap Efisiensi Anggaran Nasional
                    </h4>

                    <!-- Description -->
                    <div class="musrenbang-description mb-5">
                        <p class="lead text-primary mb-3">
                            Platform MARIMOI menjadi cikal bakal penyelenggaraan Musrenbang secara digital melalui platform
                            virtual
                        </p>
                        <p class="text-muted">
                            Sesuai Inpres No. 1 Tahun 2025 tentang Efisiensi Belanja - mengubah tantangan efisiensi menjadi
                            peluang inovasi tata kelola
                        </p>
                    </div>

                    <!-- Progress Section -->
                    <div class="progress-section mb-5">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="progress-info">
                                    <h6 class="text-primary mb-2">Progress Pengembangan</h6>
                                    <div class="progress-percentage" id="progress-text">10%</div>
                                    <small class="text-muted">Tahap Awal - Analisis Kebutuhan</small>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="progress progress-animated mb-2">
                                    <div class="progress-bar bg-gradient-primary progress-bar-striped progress-bar-animated"
                                        role="progressbar" style="width: 10%" aria-valuenow="10" aria-valuemin="0"
                                        aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="progress-stages">
                                    <span class="stage active">Analisis</span>
                                    <span class="stage">Integrasi</span>
                                    <span class="stage">Testing</span>
                                    <span class="stage">Pilot</span>
                                    <span class="stage">Launch</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Benefits -->
                    <div class="benefits-section mb-5">
                        <h5 class="mb-4 text-primary">Manfaat Signifikan</h5>
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="benefit-card">
                                    <div class="benefit-icon">
                                        <i class="mdi mdi-cash text-primary"></i>
                                    </div>
                                    <h6>Efisiensi Anggaran Drastis</h6>
                                    <small>Biaya perjalanan dinas, akomodasi, dan konsumsi dapat dipangkas secara
                                        masif</small>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="benefit-card">
                                    <div class="benefit-icon">
                                        <i class="mdi mdi-target text-primary"></i>
                                    </div>
                                    <h6>Fokus pada Substansi</h6>
                                    <small>Screen sharing dashboard memaksa diskusi fokus pada bukti dan prioritas</small>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="benefit-card">
                                    <div class="benefit-icon">
                                        <i class="mdi mdi-earth text-primary"></i>
                                    </div>
                                    <h6>Jangkauan Partisipasi</h6>
                                    <small>Menghilangkan hambatan geografis untuk perwakilan daerah terpencil</small>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="benefit-card">
                                    <div class="benefit-icon">
                                        <i class="mdi mdi-shield-check text-primary"></i>
                                    </div>
                                    <h6>Akuntabilitas & Transparansi</h6>
                                    <small>Sesi virtual dapat direkam untuk menciptakan arsip digital</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features Preview -->
                    <div class="features-preview mb-5">
                        <h5 class="mb-4 text-primary">Fitur Platform Virtual</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="feature-item">
                                    <div class="feature-icon mb-2">
                                        <i class="mdi mdi-share text-primary"></i>
                                    </div>
                                    <h6>Screen Sharing Dashboard</h6>
                                    <small class="text-muted">Fasilitator dapat membagikan layar untuk menampilkan analisis
                                        data MARIMOI secara real-time</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-item">
                                    <div class="feature-icon mb-2">
                                        <i class="mdi mdi-video-box text-primary"></i>
                                    </div>
                                    <h6>Platform Virtual</h6>
                                    <small class="text-muted">Integrasi dengan platform virtual meeting seperti Zoom atau
                                        Google Meet</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-item">
                                    <div class="feature-icon mb-2">
                                        <i class="mdi mdi-record-rec text-primary"></i>
                                    </div>
                                    <h6>Perekaman Sesi</h6>
                                    <small class="text-muted">Menciptakan arsip digital yang transparan dan dapat diakses
                                        kembali</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-item">
                                    <div class="feature-icon mb-2">
                                        <i class="mdi mdi-chart-timeline-variant text-primary"></i>
                                    </div>
                                    <h6>Analisis Data Real-time</h6>
                                    <small class="text-muted">Presentasi dashboard MARIMOI untuk pengambilan keputusan
                                        berbasis data</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- <!-- Implementation Timeline -->
                    <div class="timeline-section mb-5">
                        <h5 class="mb-4 text-primary">Tahapan Implementasi</h5>
                        <div class="timeline">
                            <div class="timeline-item active">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Fase 1: Analisis Kebutuhan</h6>
                                    <small>Studi mendalam terhadap Inpres No. 1/2025</small>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Fase 2: Integrasi Platform Virtual</h6>
                                    <small>Pengembangan integrasi MARIMOI dengan platform virtual meeting</small>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Fase 3: Uji Coba Terbatas</h6>
                                    <small>Testing dengan stakeholder terpilih untuk validasi konsep</small>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Fase 4: Pilot Project</h6>
                                    <small>Implementasi terbatas di Maluku Utara sebagai model</small>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Fase 5: Implementasi Penuh</h6>
                                    <small>Transformasi penuh proses Musrenbang di seluruh wilayah</small>
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="{{ route('dashboard') }}" class="btn btn-gradient-primary btn-lg me-3">
                            <i class="mdi mdi-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                        <button class="btn btn-outline-primary btn-lg me-3" id="notifyBtn">
                            <i class="mdi mdi-bell me-2"></i>Beritahu Saya
                        </button>
                        <button class="btn btn-outline-secondary btn-lg" id="learnMoreBtn">
                            <i class="mdi mdi-information me-2"></i>Pelajari Lebih Lanjut
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="mdi mdi-check-circle text-success me-2"></i>
                <strong class="me-auto">Musrenbang Digital</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Anda akan mendapatkan notifikasi saat Musrenbang Digital tersedia!
            </div>
        </div>
    </div>

    <!-- Info Modal -->
    <div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="infoModalLabel">
                        <i class="mdi mdi-information text-primary me-2"></i>
                        Tentang Musrenbang Digital
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Latar Belakang</h6>
                            <p class="small text-muted">
                                Berdasarkan Inpres No. 1 Tahun 2025 tentang Efisiensi Belanja, platform MARIMOI membuka
                                peluang strategis untuk penyelenggaraan Musrenbang secara digital. Ini bukan sekadar
                                memindahkan rapat fisik ke ranah online, melainkan desain ulang proses yang memberikan
                                manfaat signifikan.
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Manfaat Strategis</h6>
                            <ul class="small text-muted">
                                <li>Efisiensi anggaran drastis untuk provinsi kepulauan</li>
                                <li>Fokus pada substansi dan analisis data</li>
                                <li>Jangkauan partisipasi lintas geografis</li>
                                <li>Akuntabilitas dan transparansi digital</li>
                                <li>Transformasi tata kelola yang inovatif</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Progress Animation
            function animateProgress() {
                const progressBar = $('.progress-bar');
                const progressText = $('#progress-text');
                let progress = 0;
                const targetProgress = 10;

                const interval = setInterval(() => {
                    if (progress < targetProgress) {
                        progress += 1;
                        progressBar.css('width', progress + '%');
                        progressText.text(progress + '%');
                    } else {
                        clearInterval(interval);
                    }
                }, 50);
            }

            // Notification Button
            $('#notifyBtn').on('click', function() {
                const toast = new bootstrap.Toast(document.getElementById('notificationToast'));
                toast.show();
            });

            // Learn More Button
            $('#learnMoreBtn').on('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('infoModal'));
                modal.show();
            });

            // Initialize animations
            setTimeout(animateProgress, 500);
        });
    </script>
@endsection

@section('styles')
    <style>
        .musrenbang-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background: white;
            position: relative;
            overflow: hidden;
        }

        .musrenbang-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .musrenbang-icon {
            position: relative;
            display: inline-block;
        }

        .icon-container {
            position: relative;
            display: inline-block;
        }

        .icon-main {
            font-size: 5rem;
            color: #667eea;
            animation: iconFloat 3s ease-in-out infinite;
        }

        @keyframes iconFloat {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 3rem;
        }

        .musrenbang-title {
            animation: titleShine 4s ease-in-out infinite;
        }

        @keyframes titleShine {

            0%,
            100% {
                filter: brightness(1);
            }

            50% {
                filter: brightness(1.1);
            }
        }

        .benefit-card {
            background: white;
            padding: 2rem 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            border: 2px solid transparent;
            text-align: center;
        }

        .benefit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }

        .benefit-icon i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .progress-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid #e9ecef;
        }

        .progress-percentage {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            animation: numberPulse 2s ease-in-out infinite;
        }

        @keyframes numberPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .progress-animated {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }

        .progress-stages {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
        }

        .stage {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            background: #e9ecef;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .stage.active {
            background: #667eea;
            color: white;
            animation: stageGlow 2s ease-in-out infinite;
        }

        @keyframes stageGlow {

            0%,
            100% {
                box-shadow: none;
            }

            50% {
                box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
            }
        }

        .feature-item {
            padding: 1.5rem;
            border-radius: 12px;
            background: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            border-left: 3px solid transparent;
            text-align: center;
        }

        .feature-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            border-left-color: #667eea;
        }

        .feature-icon i {
            font-size: 2rem;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 2rem;
        }

        .timeline-marker {
            position: absolute;
            left: -2rem;
            top: 0.5rem;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e9ecef;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .timeline-item.active .timeline-marker {
            background: #667eea;
            animation: markerPulse 2s ease-in-out infinite;
        }

        @keyframes markerPulse {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }

            50% {
                transform: scale(1.2);
                box-shadow: 0 3px 8px rgba(102, 126, 234, 0.3);
            }
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .btn-gradient-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        /* Animation on page load */
        .musrenbang-card {
            animation: cardFadeInUp 0.8s ease-out;
        }

        @keyframes cardFadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .text-gradient {
                font-size: 2rem;
            }

            .icon-main {
                font-size: 3.5rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 1rem;
            }

            .action-buttons .btn {
                width: 100%;
                margin: 0 !important;
                margin-bottom: 0.5rem !important;
            }

            .timeline {
                padding-left: 1rem;
            }

            .timeline-item {
                padding-left: 1.5rem;
            }

            .timeline-marker {
                left: -1.5rem;
            }
        }
    </style>
@endsection
