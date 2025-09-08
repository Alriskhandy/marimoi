{{-- Tailwind Supporting CSS --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/section/aspirasi-tailwind.css') }}">
@endpush

<!-- Aspirasi Section - Tailwind Version -->
<section class="relative w-full py-20 lg:py-24 bg-white" id="aspirasi">
    <div class="container max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <!-- Left Side: Counters + Button (Mobile: Bottom, Desktop: Left) -->
            <div class="order-2 lg:order-1 flex flex-col gap-6 items-center lg:items-start">
                <!-- Counters -->
                <div
                    class="counter-section w-full lg:w-auto flex flex-col sm:flex-row gap-5 sm:gap-6 justify-center lg:justify-around bg-gradient-to-br from-[#0a0f1e] to-[#151c31] p-6 lg:p-8 rounded-2xl shadow-xl">
                    <div class="counter-item text-center min-w-[120px] lg:min-w-[140px]">
                        <div class="counter-number text-3xl lg:text-4xl font-bold text-white mb-2 font-[Poppins]"
                            id="proposalCounter" data-count="18">
                            18
                        </div>
                        <div class="counter-label text-md lg:text-lg text-white/90 leading-snug font-medium">
                            Usulan Pembangunan
                        </div>
                    </div>
                    <div class="counter-item text-center min-w-[120px] lg:min-w-[140px]">
                        <div class="counter-number text-3xl lg:text-4xl font-bold text-white mb-2 font-[Poppins]"
                            id="feedbackCounter" data-count="150">
                            150
                        </div>
                        <div class="counter-label text-md lg:text-lg text-white/90 leading-snug font-medium">
                            Kritik & Saran
                        </div>
                    </div>
                </div>

                <!-- CTA Button -->
                <button
                    class="group bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white font-semibold py-3 px-8 lg:px-8 rounded-3xl transition-all duration-300 transform hover:translate-x-3 hover:shadow-xl hover:shadow-blue-500/25 active:translate-x-2 active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-blue-500/30 font-[Inter] text-base lg:text-lg w-full sm:w-3/4 lg:w-1/2 flex items-center justify-center gap-2"
                    onclick="openAspirationForm()">
                    <span>Berikan Aspirasi Anda</span>
                    <span class="transition-transform duration-300 group-hover:translate-x-1">→</span>
                </button>
            </div>

            <!-- Right Side: Title + Description (Mobile: Top, Desktop: Right) -->
            <div class="order-1 lg:order-2 flex flex-col gap-6 text-center lg:text-right">
                <h2
                    class="text-3xl md:text-3xl lg:text-5xl font-bold text-gray-900 font-[Poppins] leading-tight tracking-tight">
                    Aspirasi
                </h2>
                <p class="text-md lg:text-lg text-gray-800/80 leading-relaxed max-w-2xl mx-auto lg:mx-0 lg:ml-auto">
                    Platform terintegrasi untuk menampung aspirasi masyarakat terkait pembangunan infrastruktur.
                    Berikan masukan dan saran Anda untuk kemajuan daerah yang lebih baik.
                </p>
            </div>
        </div>
    </div>
</section>