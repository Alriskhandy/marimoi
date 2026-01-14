{{-- Tailwind Supporting CSS --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/section/aspirasi-tailwind.css') }}">
@endpush

<!-- Aspirasi Section - Tailwind Version -->
<section class="relative w-full py-20 lg:py-24 bg-white" id="aspirasi">
    <div class="container max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <!-- Left Side: Counters + Button (Mobile: Bottom, Desktop: Left) -->
            <div class="order-2 lg:order-1 flex flex-col gap-6 items-center lg:items-start lg:self-start">
                <h2
                    class="text-3xl md:text-3xl lg:text-4xl font-bold text-gray-900 font-[Poppins] leading-tight tracking-tight">
                    Aspirasi
                </h2>

                <!-- Counters -->
                <div
                    class="counter-section w-full lg:w-auto flex flex-col sm:flex-row gap-5 sm:gap-6 justify-center lg:justify-around bg-gradient-to-br from-[#0a0f1e] to-[#151c31] p-6 lg:p-8 rounded-2xl shadow-xl">
                    <div class="counter-item text-center min-w-[120px] lg:min-w-[140px]">
                        <div class="counter-number text-3xl lg:text-4xl font-bold text-white mb-2 font-[Poppins]"
                            id="proposalCounter" data-count="{{ $totalUsulan }}">
                            {{ $totalUsulan }}
                        </div>
                        <div class="counter-label text-md lg:text-lg text-white/90 leading-snug font-medium">
                            Usulan Pembangunan
                        </div>
                    </div>
                    <div class="counter-item text-center min-w-[120px] lg:min-w-[140px]">
                        <div class="counter-number text-3xl lg:text-4xl font-bold text-white mb-2 font-[Poppins]"
                            id="feedbackCounter" data-count="{{ $totalKritik }}">
                            {{ $totalKritik }}
                        </div>
                        <div class="counter-label text-md lg:text-lg text-white/90 leading-snug font-medium">
                            Kritik & Saran
                        </div>
                    </div>
                </div>

                <!-- CTA Button -->
                <a href="{{ route('tampil.aspirasi') }}"
                    class="group bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white font-semibold py-3 px-8 lg:px-4 rounded-3xl transition-all duration-300 transform hover:translate-x-3 hover:shadow-xl hover:shadow-blue-500/25 active:translate-x-2 active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-blue-500/30 font-[Inter] text-base lg:text-lg w-full sm:w-3/4 lg:w-1/2 flex items-center justify-center gap-2">
                    <span>Berikan Aspirasi Anda</span>
                    <span class="transition-transform duration-300 group-hover:translate-x-1">→</span>
                </a>
            </div>

            <!-- Right Side: Title + Description (Mobile: Top, Desktop: Right) -->
            <div class="order-1 lg:order-2 flex flex-col gap-4 text-center lg:text-right">
                <h2
                    class="text-3xl md:text-3xl lg:text-4xl font-bold text-gray-900 font-[Poppins] leading-tight tracking-tight">
                    Statistik Pengunjung
                </h2>

                <div class="wrapper w-full max-w-4xl mx-auto">
                    @php
                        // keep original styling while generating unique IDs and safe counts
                        $visitorItems = [
                            [
                                'key' => 'today',
                                'icon' => 'circle-fill animate-pulse',
                                'label' => 'Hari ini',
                                'count' => $visitorsToday ?? 0,
                            ],
                            [
                                'key' => 'week',
                                'icon' => 'calendar-week',
                                'label' => 'Minggu ini',
                                'count' => $visitorsWeek ?? 0,
                            ],
                            [
                                'key' => 'month',
                                'icon' => 'calendar-date',
                                'label' => 'Bulan ini',
                                'count' => $visitorsMonth ?? 0,
                            ],
                            [
                                'key' => 'total',
                                'icon' => 'calendar2-check',
                                'label' => 'Total Pengunjung',
                                'count' => $visitorsTotal ?? ($totalVisitor ?? 0),
                            ],
                        ];
                    @endphp

                    @foreach ($visitorItems as $item)
                        <div
                            class="visitor mb-4 px-5 py-1 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                            <label class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md"
                                tabindex="0">
                                <h3 class="pr-4 md:pr-0 flex items-center">
                                    <i class="bi bi-{{ $item['icon'] }} me-2"></i>
                                    {{ $item['label'] }}
                                </h3>
                                <span
                                    class="ml-3 font-medium text-gray-700 absolute right-5 md:right-10 text-2xl hover:text-gray-80">{{ number_format($item['count']) }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
