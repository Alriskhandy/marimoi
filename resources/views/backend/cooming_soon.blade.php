@extends('backend.partials.main')

@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-rocket"></i>
            </span> Coming Soon
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span></span>Coming Soon
                </li>
            </ul>
        </nav>
    </div>

    <!-- Coming Soon Content -->
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card coming-soon-card">
                <div class="card-body text-center p-5">
                    <!-- Animated Icon -->
                    <div class="coming-soon-icon mb-4">
                        <div class="icon-container">
                            <i class="mdi mdi-rocket icon-rocket"></i>
                            <div class="icon-particles">
                                <span class="particle particle-1"></span>
                                <span class="particle particle-2"></span>
                                <span class="particle particle-3"></span>
                                <span class="particle particle-4"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Main Title -->
                    <h1 class="coming-soon-title mb-3">
                        <span class="text-gradient">Coming Soon</span>
                    </h1>

                    <!-- Subtitle -->
                    <h4 class="coming-soon-subtitle mb-4">
                        Fitur Baru Sedang Dalam Pengembangan
                    </h4>

                    <!-- Description -->
                    <div class="coming-soon-description mb-5">
                        <p class="lead text-muted mb-3">
                            Kami sedang mengembangkan fitur yang menakjubkan untuk meningkatkan pengalaman Anda.
                        </p>
                        <p class="text-muted">
                            Fitur ini akan segera hadir dengan berbagai kemampuan baru yang akan memudahkan proses
                            pengelolaan data.
                        </p>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress-section mb-5">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Progress Pengembangan</small>
                            <small class="text-primary font-weight-bold" id="progress-text">75%</small>
                        </div>
                        <div class="progress progress-animated">
                            <div class="progress-bar bg-gradient-primary progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0"
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <!-- Features Preview -->
                    <div class="features-preview mb-5">
                        <h5 class="mb-4">Yang Akan Datang:</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="feature-item">
                                    <div class="feature-icon mb-2">
                                        <i class="mdi mdi-chart-line text-success"></i>
                                    </div>
                                    <h6>Analytics Dashboard</h6>
                                    <small class="text-muted">Analisis data yang mendalam</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="feature-item">
                                    <div class="feature-icon mb-2">
                                        <i class="mdi mdi-bell-ring text-warning"></i>
                                    </div>
                                    <h6>Real-time Notifications</h6>
                                    <small class="text-muted">Notifikasi waktu nyata</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="feature-item">
                                    <div class="feature-icon mb-2">
                                        <i class="mdi mdi-file-export text-info"></i>
                                    </div>
                                    <h6>Advanced Export</h6>
                                    <small class="text-muted">Export laporan canggih</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- <!-- Countdown Timer -->
                    <div class="countdown-section mb-5">
                        <h5 class="mb-3">Perkiraan Peluncuran:</h5>
                        <div class="countdown-timer">
                            <div class="countdown-item">
                                <div class="countdown-number" id="days">30</div>
                                <div class="countdown-label">Hari</div>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <div class="countdown-number" id="hours">12</div>
                                <div class="countdown-label">Jam</div>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <div class="countdown-number" id="minutes">45</div>
                                <div class="countdown-label">Menit</div>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <div class="countdown-number" id="seconds">30</div>
                                <div class="countdown-label">Detik</div>
                            </div>
                        </div>
                    </div> --}}

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="{{ route('dashboard') }}" class="btn btn-gradient-primary btn-lg me-3">
                            <i class="mdi mdi-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                        <button class="btn btn-outline-secondary btn-lg" id="notifyBtn">
                            <i class="mdi mdi-bell me-2"></i>Beritahu Saya
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
                <strong class="me-auto">Notifikasi</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Anda akan mendapatkan notifikasi saat fitur ini tersedia!
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Countdown Timer
            function updateCountdown() {
                // Set target date (30 days from now)
                const targetDate = new Date().getTime() + (30 * 24 * 60 * 60 * 1000);

                function updateTimer() {
                    const now = new Date().getTime();
                    const timeLeft = targetDate - now;

                    if (timeLeft > 0) {
                        const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                        $('#days').text(days.toString().padStart(2, '0'));
                        $('#hours').text(hours.toString().padStart(2, '0'));
                        $('#minutes').text(minutes.toString().padStart(2, '0'));
                        $('#seconds').text(seconds.toString().padStart(2, '0'));
                    }
                }

                updateTimer();
                setInterval(updateTimer, 1000);
            }

            // Progress Animation
            function animateProgress() {
                const progressBar = $('.progress-bar');
                const progressText = $('#progress-text');
                let progress = 0;
                const targetProgress = 75;

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

            // Particle Animation
            function createParticles() {
                const container = $('.icon-particles');
                for (let i = 0; i < 6; i++) {
                    const particle = $(`<span class="particle particle-${i + 5}"></span>`);
                    container.append(particle);
                }
            }

            // Initialize
            updateCountdown();
            setTimeout(animateProgress, 500);
            createParticles();
        });
    </script>
@endsection

@section('styles')
    <style>
        .coming-soon-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            position: relative;
            overflow: hidden;
        }

        .coming-soon-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .coming-soon-icon {
            position: relative;
            display: inline-block;
        }

        .icon-container {
            position: relative;
            display: inline-block;
        }

        .icon-rocket {
            font-size: 5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: rocketBounce 2s ease-in-out infinite;
        }

        @keyframes rocketBounce {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-10px) rotate(5deg);
            }
        }

        .icon-particles {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
            height: 100px;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #667eea;
            border-radius: 50%;
            opacity: 0;
            animation: particleFloat 3s ease-in-out infinite;
        }

        .particle-1 {
            animation-delay: 0s;
            top: 10%;
            left: 20%;
        }

        .particle-2 {
            animation-delay: 0.5s;
            top: 20%;
            right: 10%;
        }

        .particle-3 {
            animation-delay: 1s;
            bottom: 20%;
            left: 10%;
        }

        .particle-4 {
            animation-delay: 1.5s;
            bottom: 10%;
            right: 20%;
        }

        @keyframes particleFloat {

            0%,
            100% {
                opacity: 0;
                transform: scale(0);
            }

            50% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 3.5rem;
        }

        .coming-soon-title {
            animation: titleGlow 3s ease-in-out infinite;
        }

        @keyframes titleGlow {

            0%,
            100% {
                filter: brightness(1);
            }

            50% {
                filter: brightness(1.1);
            }
        }

        .coming-soon-subtitle {
            color: #6c757d;
            font-weight: 600;
        }

        .progress-animated {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            animation: progressShine 2s ease-in-out infinite;
        }

        @keyframes progressShine {
            0% {
                filter: brightness(1);
            }

            50% {
                filter: brightness(1.2);
            }

            100% {
                filter: brightness(1);
            }
        }

        .feature-item {
            padding: 1.5rem;
            border-radius: 12px;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .feature-icon i {
            font-size: 2.5rem;
        }

        .countdown-timer {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .countdown-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            min-width: 80px;
        }

        .countdown-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            line-height: 1;
            animation: numberPulse 1s ease-in-out infinite;
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

        .countdown-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .countdown-separator {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            animation: separatorBlink 2s ease-in-out infinite;
        }

        @keyframes separatorBlink {

            0%,
            50% {
                opacity: 1;
            }

            51%,
            100% {
                opacity: 0.3;
            }
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }

        .btn-gradient-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
            color: white;
        }

        .action-buttons {
            animation: buttonsSlideUp 1s ease-out;
        }

        @keyframes buttonsSlideUp {
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
                font-size: 2.5rem;
            }

            .countdown-timer {
                gap: 0.5rem;
            }

            .countdown-item {
                min-width: 60px;
                padding: 0.8rem;
            }

            .countdown-number {
                font-size: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 1rem;
            }

            .action-buttons .btn {
                width: 100%;
            }
        }

        /* Animation on page load */
        .coming-soon-card {
            animation: cardSlideUp 1s ease-out;
        }

        @keyframes cardSlideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection
