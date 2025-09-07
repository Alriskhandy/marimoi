{{-- Legacy CSS Version - Commented Out
@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/section/fitur-utama.css') }}">
@endpush

<section class="section fitur-utama">
    <div class="container">
        <div class="fitur-header">
            <!-- Menu List on LEFT side -->
            <div class="menu-section">
                <div class="menu-vertical">
                    <a href="/proyek-strategis-daerah" class="vertical-menu-item">
                        <span>Proyek Strategis Daerah</span>
                        <i class="arrow">→</i>
                    </a>
                    <a href="/proyek-strategis-nasional" class="vertical-menu-item">
                        <span>Proyek Strategis Nasional</span>
                        <i class="arrow">→</i>
                    </a>
                    <a href="/prioritas-daerah" class="vertical-menu-item">
                        <span>Prioritas Daerah 2025-2029</span>
                        <i class="arrow">→</i>
                    </a>
                    <a href="/usulan-musrenbang" class="vertical-menu-item">
                        <span>Usulan Musrenbang</span>
                        <i class="arrow">→</i>
                    </a>
                    <a href="/pokir-dprd" class="vertical-menu-item">
                        <span>Pokir DPRD</span>
                        <i class="arrow">→</i>
                    </a>
                </div>
            </div>

            <!-- Content on RIGHT side -->
            <div class="fitur-content">
                <h2 class="section-title-right">Fitur Utama</h2>
                <p class="section-description">
                    Klik menu di sebelah kiri untuk melihat detail fitur utama MARIMOI.
                </p>
            </div>
        </div>
    </div>
</section>
--}}

<!-- Fitur Utama Section - Tailwind Version -->
<section class="relative w-full py-16 lg:py-20 bg-white">
    <div class="container max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center min-h-[600px]">

            <!-- Menu List on LEFT side (Desktop) / BOTTOM (Mobile) -->
            <div class="order-2 lg:order-1 flex flex-col justify-center">
                <div class="space-y-4">
                    <a href="/proyek-strategis-daerah"
                        class="group relative bg-gray-50/80 border-2 border-gray-200/60 rounded-2xl p-4 lg:p-5 cursor-pointer transition-all duration-300 overflow-hidden flex items-center justify-between text-inherit hover:border-blue-500/70 hover:bg-blue-50/60 hover:translate-x-3 hover:shadow-xl hover:shadow-blue-500/15 active:translate-x-2 active:scale-[1.02] focus:outline-none focus:border-blue-500 focus:shadow-[0_0_0_3px_rgba(59,130,246,0.1)] backdrop-blur-sm">
                        <span
                            class="font-semibold text-base lg:text-lg text-gray-800 transition-colors duration-300 group-hover:text-blue-700">
                            Proyek Strategis Daerah
                        </span>
                        <i
                            class="text-xl text-gray-600 transition-all duration-300 group-hover:translate-x-2 group-hover:text-blue-600 group-hover:scale-110">→</i>
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        </div>
                    </a>

                    <a href="/proyek-strategis-nasional"
                        class="group relative bg-gray-50/80 border-2 border-gray-200/60 rounded-2xl p-4 lg:p-5 cursor-pointer transition-all duration-300 overflow-hidden flex items-center justify-between text-inherit hover:border-blue-500/70 hover:bg-blue-50/60 hover:translate-x-3 hover:shadow-xl hover:shadow-blue-500/15 active:translate-x-2 active:scale-[1.02] focus:outline-none focus:border-blue-500 focus:shadow-[0_0_0_3px_rgba(59,130,246,0.1)] backdrop-blur-sm">
                        <span
                            class="font-semibold text-base lg:text-lg text-gray-800 transition-colors duration-300 group-hover:text-blue-700">
                            Proyek Strategis Nasional
                        </span>
                        <i
                            class="text-xl text-gray-600 transition-all duration-300 group-hover:translate-x-2 group-hover:text-blue-600 group-hover:scale-110">→</i>
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        </div>
                    </a>

                    <a href="/prioritas-daerah"
                        class="group relative bg-gray-50/80 border-2 border-gray-200/60 rounded-2xl p-4 lg:p-5 cursor-pointer transition-all duration-300 overflow-hidden flex items-center justify-between text-inherit hover:border-blue-500/70 hover:bg-blue-50/60 hover:translate-x-3 hover:shadow-xl hover:shadow-blue-500/15 active:translate-x-2 active:scale-[1.02] focus:outline-none focus:border-blue-500 focus:shadow-[0_0_0_3px_rgba(59,130,246,0.1)] backdrop-blur-sm">
                        <span
                            class="font-semibold text-base lg:text-lg text-gray-800 transition-colors duration-300 group-hover:text-blue-700">
                            Prioritas Daerah 2025-2029
                        </span>
                        <i
                            class="text-xl text-gray-600 transition-all duration-300 group-hover:translate-x-2 group-hover:text-blue-600 group-hover:scale-110">→</i>
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        </div>
                    </a>

                    <a href="/usulan-musrenbang"
                        class="group relative bg-gray-50/80 border-2 border-gray-200/60 rounded-2xl p-4 lg:p-5 cursor-pointer transition-all duration-300 overflow-hidden flex items-center justify-between text-inherit hover:border-blue-500/70 hover:bg-blue-50/60 hover:translate-x-3 hover:shadow-xl hover:shadow-blue-500/15 active:translate-x-2 active:scale-[1.02] focus:outline-none focus:border-blue-500 focus:shadow-[0_0_0_3px_rgba(59,130,246,0.1)] backdrop-blur-sm">
                        <span
                            class="font-semibold text-base lg:text-lg text-gray-800 transition-colors duration-300 group-hover:text-blue-700">
                            Usulan Musrenbang
                        </span>
                        <i
                            class="text-xl text-gray-600 transition-all duration-300 group-hover:translate-x-2 group-hover:text-blue-600 group-hover:scale-110">→</i>
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        </div>
                    </a>

                    <a href="/pokir-dprd"
                        class="group relative bg-gray-50/80 border-2 border-gray-200/60 rounded-2xl p-4 lg:p-5 cursor-pointer transition-all duration-300 overflow-hidden flex items-center justify-between text-inherit hover:border-blue-500/70 hover:bg-blue-50/60 hover:translate-x-3 hover:shadow-xl hover:shadow-blue-500/15 active:translate-x-2 active:scale-[1.02] focus:outline-none focus:border-blue-500 focus:shadow-[0_0_0_3px_rgba(59,130,246,0.1)] backdrop-blur-sm">
                        <span
                            class="font-semibold text-base lg:text-lg text-gray-800 transition-colors duration-300 group-hover:text-blue-700">
                            Pokir DPRD
                        </span>
                        <i
                            class="text-xl text-gray-600 transition-all duration-300 group-hover:translate-x-2 group-hover:text-blue-600 group-hover:scale-110">→</i>
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        </div>
                    </a>
                </div>
            </div>

            <!-- Content on RIGHT side (Desktop) / TOP (Mobile) -->
            <div class="order-1 lg:order-2 flex flex-col justify-center text-center lg:text-right lg:pl-8">
                <h2
                    class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-6 lg:mb-8 font-[Poppins] leading-tight tracking-tight">
                    Fitur Utama
                </h2>
                <p
                    class="text-md lg:text-lg text-gray-600 leading-relaxed font-[Inter] max-w-2xl mx-auto lg:mx-0">
                    Klik menu di sebelah kiri untuk melihat detail fitur utama MARIMOI.
                </p>
            </div>
        </div>
    </div>
</section>

@push('styles')
    <!-- Fitur Utama Responsive Styles -->
    <style>
        /* Enhanced hover effects for mobile */
        @media (max-width: 1023px) {
            .group:hover {
                transform: translateX(0.5rem) scale(1.01);
            }

            .group:active {
                transform: translateX(0.25rem) scale(0.99);
            }
        }

        /* Mobile optimizations */
        @media (max-width: 640px) {
            .group {
                padding: 1rem 1.25rem;
                border-radius: 1rem;
            }

            .group:hover {
                transform: translateX(0.375rem);
                box-shadow: 0 10px 25px rgba(59, 130, 246, 0.1);
            }

            .group:active {
                transform: translateX(0.25rem) scale(0.98);
            }
        }

        /* Extra small mobile devices */
        @media (max-width: 480px) {
            .group {
                padding: 0.875rem 1rem;
                border-radius: 0.75rem;
            }

            .group span {
                font-size: 0.9375rem;
                line-height: 1.375;
            }

            .group i {
                font-size: 1.125rem;
            }
        }

        /* Very small devices */
        @media (max-width: 360px) {
            .group {
                padding: 0.75rem 0.875rem;
                border-radius: 0.625rem;
            }

            .group span {
                font-size: 0.875rem;
                line-height: 1.25;
            }

            .group i {
                font-size: 1rem;
            }

            .group:hover {
                transform: translateX(0.25rem);
            }
        }

        /* Smooth transitions */
        .group * {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Focus states */
        .group:focus-visible {
            outline: 2px solid rgb(59 130 246);
            outline-offset: 2px;
        }
    </style>
@endpush
