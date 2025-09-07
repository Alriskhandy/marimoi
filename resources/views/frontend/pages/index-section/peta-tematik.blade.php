@push('styles')
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
</section>
