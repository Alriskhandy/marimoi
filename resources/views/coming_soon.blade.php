@extends('frontend.layouts.dark', ['title' => 'Musrenbang Digital'])

@push('styles')
    <!-- Tailwind CSS via Vite -->
    @vite(['resources/css/app.css'])
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Typography Fonts */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
        }

        p,
        body,
        ul,
        li {
            font-family: 'Inter', sans-serif;
        }

        /* Simple animations */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-float {
            animation: float 4s ease-in-out infinite;
        }

        .animate-fade-in-up {
            animation: fade-in-up 0.6s ease-out;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Progress bar animation */
        .progress-bar {
            background: #3b82f6;
            transition: width 1s ease-in-out;
        }

        /* Card hover effects */
        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        /* Button styles */
        .btn-primary {
            background: #3b82f6;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }
    </style>
@endpush

@section('main')
    <!-- Hero Section -->
    <section class="py-20 bg-slate-900 mt-[76px]">
        <div class="container mx-auto px-4 text-center">
            <div class="max-w-4xl mx-auto">
                <!-- Main Icon -->
                <div class="mb-8">
                    <div class="w-24 h-24 mx-auto bg-blue-600 rounded-2xl flex items-center justify-center animate-float">
                        <i class="bi bi-camera-video text-white text-4xl"></i>
                    </div>
                </div>

                <!-- Title and Description -->
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
                    Musrenbang Digital
                </h1>

                <p class="text-xl md:text-2xl text-slate-300 mb-6">
                    Peluang Strategis sebagai Respons terhadap Efisiensi Anggaran Nasional
                </p>

                <p class="text-lg text-slate-400 mb-10 max-w-3xl mx-auto leading-relaxed">
                    Platform MARIMOI menjadi cikal bakal penyelenggaraan Musrenbang secara digital melalui platform virtual,
                    mengubah tantangan efisiensi menjadi peluang inovasi tata kelola sesuai Inpres No. 1 Tahun 2025.
                </p>

                <!-- Progress Card -->
                <div class="glass-card rounded-xl p-6 max-w-md mx-auto mb-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-white font-semibold">Progress Pengembangan</span>
                        <span class="text-yellow-400 font-bold text-xl">10%</span>
                    </div>
                    <div class="w-full bg-slate-700/50 rounded-full h-2 overflow-hidden mb-3">
                        <div class="progress-bar h-full rounded-full" style="width: 10%;"></div>
                    </div>
                    <p class="text-slate-300 text-sm">Tahap Awal - Analisis Kebutuhan</p>
                </div>

                {{-- <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center max-w-lg mx-auto">
                    <button class="btn-primary font-semibold py-3 px-8 rounded-lg flex-1">
                        <i class="bi bi-bell me-2"></i>
                        Daftar Notifikasi
                    </button>
                    <button class="btn-secondary font-semibold py-3 px-8 rounded-lg flex-1">
                        <i class="bi bi-info-circle me-2"></i>
                        Info Lebih Lanjut
                    </button>
                </div> --}}
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">
                    Manfaat Signifikan
                </h2>
                <p class="text-xl text-slate-600 max-w-3xl mx-auto">
                    Desain ulang proses yang memberikan manfaat strategis sesuai mandat Inpres No. 1 Tahun 2025
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Benefit 1 -->
                <div class="bg-white rounded-xl p-8 shadow-lg border border-slate-100 card-hover text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-xl flex items-center justify-center mb-6 mx-auto">
                        <i class="bi bi-cash-coin text-slate-700 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Efisiensi Anggaran Drastis</h3>
                    <p class="text-slate-600">Biaya perjalanan dinas, akomodasi, dan konsumsi dapat dipangkas secara masif,
                        sangat signifikan bagi provinsi kepulauan seperti Maluku Utara</p>
                </div>

                <!-- Benefit 2 -->
                <div class="bg-white rounded-xl p-8 shadow-lg border border-slate-100 card-hover text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-xl flex items-center justify-center mb-6 mx-auto">
                        <i class="bi bi-bullseye text-slate-700 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Fokus pada Substansi</h3>
                    <p class="text-slate-600">Screen sharing dashboard MARIMOI memaksa diskusi fokus pada bukti dan
                        prioritas, bukan retorika</p>
                </div>

                <!-- Benefit 3 -->
                <div class="bg-white rounded-xl p-8 shadow-lg border border-slate-100 card-hover text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-xl flex items-center justify-center mb-6 mx-auto">
                        <i class="bi bi-globe text-slate-700 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Jangkauan Partisipasi</h3>
                    <p class="text-slate-600">Menghilangkan hambatan geografis, memungkinkan perwakilan daerah terpencil
                        berpartisipasi aktif</p>
                </div>

                <!-- Benefit 4 -->
                <div class="bg-white rounded-xl p-8 shadow-lg border border-slate-100 card-hover text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-xl flex items-center justify-center mb-6 mx-auto">
                        <i class="bi bi-shield-check text-slate-700 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Akuntabilitas & Transparansi</h3>
                    <p class="text-slate-600">Sesi virtual dapat direkam dengan mudah, menciptakan arsip digital untuk
                        memverifikasi komitmen dan keputusan</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-slate-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">
                    Fitur Platform Virtual
                </h2>
                <p class="text-xl text-slate-600 max-w-3xl mx-auto">
                    Implementasi melalui platform virtual seperti Zoom atau Google Meet dengan integrasi MARIMOI
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Feature 1 -->
                <div class="bg-white rounded-xl p-8 shadow-lg border border-slate-200 card-hover">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="bi bi-share text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Screen Sharing Dashboard</h3>
                    <p class="text-slate-600">Fasilitator dapat membagikan layar untuk menampilkan analisis data MARIMOI
                        secara real-time</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white rounded-xl p-8 shadow-lg border border-slate-200 card-hover">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="bi bi-camera-video text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Platform Virtual</h3>
                    <p class="text-slate-600">Integrasi dengan platform virtual meeting seperti Zoom atau Google Meet</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white rounded-xl p-8 shadow-lg border border-slate-200 card-hover">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="bi bi-record-circle text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Perekaman Sesi</h3>
                    <p class="text-slate-600">Menciptakan arsip digital yang transparan dan dapat diakses kembali</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white rounded-xl p-8 shadow-lg border border-slate-200 card-hover">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="bi bi-bar-chart text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Analisis Data Real-time</h3>
                    <p class="text-slate-600">Presentasi dashboard MARIMOI untuk pengambilan keputusan berbasis data</p>
                </div>
            </div>
        </div>
    </section>

    {{-- <!-- Timeline Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">
                    Roadmap Pengembangan
                </h2>
                <p class="text-xl text-slate-600 max-w-3xl mx-auto">
                    Tahapan transformasi MARIMOI menjadi enabler proses perencanaan yang lebih hemat dan efektif
                </p>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="relative">
                    <!-- Central timeline line -->
                    <div
                        class="absolute left-8 md:left-1/2 transform md:-translate-x-px bg-slate-300 w-1 h-full rounded-full">
                    </div>

                    <!-- Phase 1 - Active -->
                    <div class="relative flex items-center mb-12">
                        <div
                            class="absolute left-6 md:left-1/2 transform md:-translate-x-1/2 w-4 h-4 bg-blue-600 rounded-full border-4 border-white shadow-lg z-10">
                        </div>
                        <div class="ml-16 md:ml-0 md:w-1/2 md:pr-8">
                            <div class="bg-white rounded-xl p-6 shadow-lg border border-slate-200">
                                <span
                                    class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full mb-3">Sedang
                                    Berjalan</span>
                                <h3 class="text-lg font-bold text-slate-900 mb-2">
                                    <i class="bi bi-search me-2"></i>
                                    Fase 1: Analisis Kebutuhan
                                </h3>
                                <p class="text-slate-600">Studi mendalam terhadap Inpres No. 1/2025 dan analisis kebutuhan
                                    teknis platform</p>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 2 -->
                    <div class="relative flex items-center mb-12">
                        <div
                            class="absolute left-6 md:left-1/2 transform md:-translate-x-1/2 w-4 h-4 bg-slate-300 rounded-full border-4 border-white shadow-lg z-10">
                        </div>
                        <div class="ml-16 md:ml-0 md:w-1/2 md:ml-auto md:pl-8">
                            <div class="bg-white rounded-xl p-6 shadow-lg border border-slate-200">
                                <span
                                    class="inline-block px-3 py-1 bg-slate-100 text-slate-600 text-sm font-semibold rounded-full mb-3">Mendatang</span>
                                <h3 class="text-lg font-bold text-slate-900 mb-2">
                                    <i class="bi bi-puzzle me-2"></i>
                                    Fase 2: Integrasi Platform Virtual
                                </h3>
                                <p class="text-slate-600">Pengembangan integrasi MARIMOI dengan platform virtual meeting</p>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 3 -->
                    <div class="relative flex items-center mb-12">
                        <div
                            class="absolute left-6 md:left-1/2 transform md:-translate-x-1/2 w-4 h-4 bg-slate-300 rounded-full border-4 border-white shadow-lg z-10">
                        </div>
                        <div class="ml-16 md:ml-0 md:w-1/2 md:pr-8">
                            <div class="bg-white rounded-xl p-6 shadow-lg border border-slate-200">
                                <span
                                    class="inline-block px-3 py-1 bg-slate-100 text-slate-600 text-sm font-semibold rounded-full mb-3">Mendatang</span>
                                <h3 class="text-lg font-bold text-slate-900 mb-2">
                                    <i class="bi bi-bug me-2"></i>
                                    Fase 3: Uji Coba Terbatas
                                </h3>
                                <p class="text-slate-600">Testing dengan stakeholder terpilih untuk validasi konsep</p>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 4 -->
                    <div class="relative flex items-center mb-12">
                        <div
                            class="absolute left-6 md:left-1/2 transform md:-translate-x-1/2 w-4 h-4 bg-slate-300 rounded-full border-4 border-white shadow-lg z-10">
                        </div>
                        <div class="ml-16 md:ml-0 md:w-1/2 md:ml-auto md:pl-8">
                            <div class="bg-white rounded-xl p-6 shadow-lg border border-slate-200">
                                <span
                                    class="inline-block px-3 py-1 bg-slate-100 text-slate-600 text-sm font-semibold rounded-full mb-3">Mendatang</span>
                                <h3 class="text-lg font-bold text-slate-900 mb-2">
                                    <i class="bi bi-play-circle me-2"></i>
                                    Fase 4: Pilot Project
                                </h3>
                                <p class="text-slate-600">Implementasi terbatas di Maluku Utara sebagai model</p>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 5 -->
                    <div class="relative flex items-center">
                        <div
                            class="absolute left-6 md:left-1/2 transform md:-translate-x-1/2 w-4 h-4 bg-slate-300 rounded-full border-4 border-white shadow-lg z-10">
                        </div>
                        <div class="ml-16 md:ml-0 md:w-1/2 md:pr-8">
                            <div class="bg-white rounded-xl p-6 shadow-lg border border-slate-200">
                                <span
                                    class="inline-block px-3 py-1 bg-slate-100 text-slate-600 text-sm font-semibold rounded-full mb-3">Mendatang</span>
                                <h3 class="text-lg font-bold text-slate-900 mb-2">
                                    <i class="bi bi-rocket-takeoff me-2"></i>
                                    Fase 5: Implementasi Penuh
                                </h3>
                                <p class="text-slate-600">Transformasi penuh proses Musrenbang di seluruh wilayah</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> --}}

    <!-- Footer Section -->
    @include('frontend.partials.footer-dark-tailwind')
@endsection

@push('scripts')
    <!-- Vite JavaScript -->
    @vite(['resources/js/app.js'])
    <script>
        // Simple scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.card-hover');

            animatedElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
@endpush
