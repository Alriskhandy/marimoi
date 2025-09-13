{{-- @push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/section/peta-tematik.css') }}">
@endpush

<section class="section peta-tematik" id="peta-tematik">
    <div class="header-content">
        <h2 class="section-title">Peta Tematik</h2>
        <div class="description">
            Jelajahi berbagai peta tematik interaktif yang menampilkan data spasial dan sektoral.
            <a href="/peta-tematik"><strong>Lihat Selengkapnya</strong></a>
        </div>
    </div>
    @php
        $show = true;
        $dummys = [1, 2, 3, 4, 5, 6, 7];
    @endphp

    @if ($show)
        @if ($dummys)
            <div class="swiper">
                <div class="swiper-wrapper">
                    @foreach ($dummys as $i)
                        <div class="swiper-slide"
                            style="background: url('{{ asset('frontend/img/carousel/' . $i . '.jpg') }}') no-repeat 50% 50% / cover;"
                            loading="lazy">
                            <div class="swiper-footer">
                                <h2>Peta Contoh {{ $i }}</h2>
                                <a href="{{ route('tampil.tematik') }}" class="view-map-btn">Lihat Peta</a>
                            </div>
                        </div>
                    @endforeach
                </div>
                <!-- Add Pagination -->
                <div class="swiper-pagination"></div>
            </div>
        @endif
    @else
        <div class="tematik-fallback">
            <div class="fallback-content">
                <img src="{{ asset('frontend/img/mockup/mockup-ds.png') }}" alt="Desktop & Smartphone Mockup"
                    class="mockup-devices" loading="lazy">
            </div>
        </div>
    @endif

    <!-- Wave Separator with Wavify -->
    <div class="wave-container">
        <!-- Layer 1: Blue wave 2 (paling belakang) -->
        <svg class="wave-svg wave-layer-3" width="100%" height="120" version="1.1"
            xmlns="http://www.w3.org/2000/svg">
            <defs></defs>
            <path id="wave-blue-2" d="" />
        </svg>
        <!-- Layer 2: Blue wave 1 (tengah) -->
        <svg class="wave-svg wave-layer-2" width="100%" height="120" version="1.1"
            xmlns="http://www.w3.org/2000/svg">
            <defs></defs>
            <path id="wave-blue-1" d="" />
        </svg>
        <!-- Layer 3: White wave (paling depan) -->
        <svg class="wave-svg wave-layer-1" width="100%" height="120" version="1.1"
            xmlns="http://www.w3.org/2000/svg">
            <defs></defs>
            <path id="wave-white" d="" />
        </svg>
    </div>
</section> --}}

<style>
    /* Custom styles yang tidak tersedia di Tailwind */
    .swiper-3d .swiper-slide-shadow-left,
    .swiper-3d .swiper-slide-shadow-right {
        background-image: none;
    }

    .swiper-slide {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .swiper-pagination-bullet,
    .swiper-pagination-bullet-active {
        background: #fff;
        opacity: 0.7;
    }

    .swiper-pagination-bullet-active {
        opacity: 1;
        transform: scale(1.2);
    }

    /* Perbaikan posisi pagination */
    .swiper-pagination {
        bottom: -40px !important;
        /* Lebih jauh ke bawah */
        z-index: 10;
        position: relative !important;
        /* Override positioning */
        margin-top: 40px !important;
    }

    /* Responsive pagination */
    @media (max-width: 768px) {
        .swiper-pagination {
            bottom: -35px !important;
            margin-top: 35px !important;
        }

        .swiper-pagination-bullet {
            width: 8px;
            height: 8px;
            margin: 0 4px;
        }
    }

    @media (max-width: 480px) {
        .swiper-pagination {
            bottom: -30px !important;
            margin-top: 30px !important;
        }

        .swiper-pagination-bullet {
            width: 6px;
            height: 6px;
            margin: 0 3px;
        }
    }

    /* Swiper Slide Custom Styles */
    .swiper-slide {
        width: 512px;
        height: 285px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        filter: blur(1px);
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        justify-content: end;
        align-items: self-start;
        overflow: hidden;
        position: relative;
        min-width: 280px;
        transition: all 0.3s ease;
        /* Tambah margin antar card */
    }

    .swiper-slide-active {
        filter: blur(0px);
        transform: scale(1.02);
    }

    .swiper-slide span {
        text-transform: uppercase;
        color: #fff;
        padding: 7px 18px 7px 25px;
        display: inline-block;
        border-radius: 0 20px 20px 0px;
        letter-spacing: 2px;
        font-size: 0.8rem;
        font-family: "Inter", sans-serif;
    }

    .swiper-footer {
        background: rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(10px);
        border-radius: 0 0 10px 10px;
        padding: 10px 25px;
        margin-top: auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        overflow: hidden;
    }

    .swiper-footer h2 {
        color: #333 !important;
        font-family: "Inter", sans-serif !important;
        font-weight: 700 !important;
        font-size: 1.1rem !important;
        line-height: 1.3 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .view-map-btn {
        background: linear-gradient(45deg, var(--primary), #0066cc);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        font-family: "Poppins", sans-serif;
        cursor: pointer;
        transition: all 0.3s ease;
        align-self: flex-start;
        text-decoration: none;
        display: inline-block;
    }

    .view-map-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 127, 255, 0.3);
        background: linear-gradient(45deg, #0066cc, var(--primary));
    }

    /* Responsive Swiper Slides */
    @media (max-width: 768px) {
        .swiper-slide {
            width: 90vw;
            max-width: 350px;
            height: 220px;
            border-radius: 12px;
            min-width: 280px;
        }

        .swiper-footer {
            padding: 12px 15px;
            border-radius: 0 0 12px 12px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .swiper-footer h2 {
            font-size: 0.95rem !important;
            line-height: 1.2 !important;
            flex: 1;
            min-width: 120px;
        }

        .view-map-btn {
            padding: 8px 14px;
            font-size: 0.8rem;
            border-radius: 6px;
            white-space: nowrap;
            flex-shrink: 0;
        }
    }

    @media (max-width: 480px) {
        .swiper-slide {
            width: 95vw;
            max-width: 340px;
            height: 210px;
            min-width: 260px;
            border-radius: 10px;
        }

        .swiper-footer {
            padding: 10px 12px;
            gap: 8px;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }

        .swiper-footer h2 {
            font-size: 0.85rem !important;
            line-height: 1.1 !important;
        }

        .view-map-btn {
            padding: 6px 12px;
            font-size: 0.75rem;
            border-radius: 5px;
            align-self: center;
            width: auto;
            max-width: 150px;
        }
    }

    /* Perbaikan container swiper untuk spacing yang lebih baik */
    .swiper-container-spacing {
        padding-bottom: 60px;
        /* Extra space untuk pagination */
    }

    @media (max-width: 768px) {
        .swiper-container-spacing {
            padding-bottom: 50px;
        }
    }

    @media (max-width: 480px) {
        .swiper-container-spacing {
            padding-bottom: 45px;
        }
    }
</style>

<!-- Peta Tematik Section -->
<section class="bg-gradient-to-br from-[var(--bg-primary)] to-[var(--bg-secondary)] text-white w-full relative py-20 md:py-32 sm:py-[50px] min-h-[700px] md:min-h-[50vh] sm:min-h-[50vh] overflow-hidden" id="peta-tematik">
    <!-- Header Content -->
    <div
        class="max-w-[1400px] mx-auto px-5 md:px-4 sm:px-[15px] xs:px-[10px] flex flex-col lg:flex-row justify-between items-start lg:items-start gap-5 lg:gap-0 mb-[30px] md:mb-5 sm:mb-4 xs:mb-[15px]">
        <h2
            class="text-[3rem] lg:text-[2.8rem] md:text-[2.5rem] sm:text-[1.8rem] font-bold text-white m-0 font-[Inter,sans-serif]">
            Peta Tematik
        </h2>
        <div
            class="max-w-[400px] lg:max-w-[400px] md:max-w-full text-base md:text-[0.95rem] sm:text-[0.9rem] leading-[1.6] md:leading-[1.5] sm:leading-[1.5] text-white/80 m-0">
            <p class="mb-0">Jelajahi berbagai peta tematik interaktif yang menampilkan data spasial dan sektoral.</p>
            <a href="/peta-tematik"
                class="text-white no-underline block mt-[15px] font-semibold transition-all duration-300 ease-in-out hover:text-[#007fff] hover:underline">
                <strong>Lihat Selengkapnya</strong>
            </a>
        </div>
    </div>

    @php
        $show = true;
    @endphp

    @if ($show)
        @if ($petaTematik && count($petaTematik) > 0)
            <!-- Swiper Container -->
            <div
                class="swiper swiper-container-spacing w-full max-w-[1400px] mx-auto py-[60px] md:py-10 sm:py-[20px] xs:py-[20px] px-5 md:px-4 sm:px-[15px] xs:px-[10px] overflow-visible">
                <div class="swiper-wrapper items-center">
                    @foreach ($petaTematik as $peta)
                        <div class="swiper-slide"
                            style="background: url('{{ asset('storage/' . $peta->gambar) }}') no-repeat 50% 50% / cover;"
                            loading="lazy">
                            <div class="swiper-footer">
                                <h2>Peta {{ $peta->nama }}</h2>
                                <a href="{{ route('tampil.tematik') }}" class="view-map-btn">Lihat Peta</a>
                            </div>
                        </div>
                    @endforeach
                </div>
                <!-- Add Pagination -->
                <div class="swiper-pagination"></div>
            </div>
        @endif
    @else
        <!-- Fallback Section -->
        <div
            class="relative w-full min-h-[400px] md:min-h-[350px] sm:min-h-[300px] xs:min-h-[250px] flex items-center justify-center overflow-hidden py-10 md:py-[30px] sm:py-[25px] xs:py-5 px-5 md:px-4 sm:px-[15px] xs:px-[10px]">
            <div
                class="relative w-auto max-w-[1200px] h-full md:h-[350px] sm:h-auto flex items-end md:items-center sm:flex-col sm:items-center justify-center sm:gap-5 xs:gap-4">
                <img src="{{ asset('frontend/img/mockup/mockup-ds.png') }}" alt="Desktop & Smartphone Mockup"
                    class="h-[500px] md:h-[450px] lg:h-[380px] sm:h-[280px] xs:h-[220px] w-auto max-w-full sm:max-w-[400px] xs:max-w-[320px] object-contain [filter:drop-shadow(0_10px_30px_rgba(0,0,0,0.3))] transition-all duration-300 ease-in-out sm:transform-none"
                    loading="lazy">
            </div>
        </div>
    @endif

    <!-- Wave Separator with Wavify -->
    <div class="absolute bottom-0 left-0 w-full h-[120px] overflow-hidden m-0 p-0 z-[1]">
        <!-- Layer 1: Blue wave 2 (paling belakang) -->
        <svg class="absolute bottom-0 left-0 w-full h-[120px] z-[3]" width="100%" height="120" version="1.1"
            xmlns="http://www.w3.org/2000/svg">
            <defs></defs>
            <path id="wave-blue-2" d="" />
        </svg>
        <!-- Layer 2: Blue wave 1 (tengah) -->
        <svg class="absolute bottom-0 left-0 w-full h-[120px] z-[4]" width="100%" height="120" version="1.1"
            xmlns="http://www.w3.org/2000/svg">
            <defs></defs>
            <path id="wave-blue-1" d="" />
        </svg>
        <!-- Layer 3: White wave (paling depan) -->
        <svg class="absolute bottom-0 left-0 w-full h-[120px] z-[5]" width="100%" height="120" version="1.1"
            xmlns="http://www.w3.org/2000/svg">
            <defs></defs>
            <path id="wave-white" d="" />
        </svg>
    </div>
</section>
