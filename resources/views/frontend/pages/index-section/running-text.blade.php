{{-- @push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/section/running-text.css') }}">
@endpush

<section class="running-text">
    <div class="running-content">
        <!-- First set -->
        <a href="https://bappeda.malutprov.go.id/" target="_blank" class="running-item">
            <img src="{{ asset('frontend/img/logo.webp') }}" alt="Bappeda Maluku Utara" class="logo" loading="lazy">
            <span>BAPPEDA MALUT</span>
        </a>
        <a href="https://opendata.malutprov.go.id/" target="_blank" class="running-item">
            <img src="{{ asset('frontend/img/logo-opendata-malut.png') }}" alt="Opendata Maluku Utara" class="logo"
                loading="lazy">
        </a>
        <a href="https://malut.bps.go.id/id" target="_blank" class="running-item">
            <img src="{{ asset('frontend/img/logo-bps.webp') }}" alt="BPS Maluku Utara" class="logo" loading="lazy">
            <span>BADAN PUSAT STATISTIK</span>
        </a>
        <a href="https://bappenas.go.id/" target="_blank" class="running-item">
            <img src="{{ asset('frontend/img/logo-bappenas.png') }}" alt="BAPPENAS" class="logo" loading="lazy">
            <span>BAPPENAS</span>
        </a>
        <!-- Duplicate set for seamless loop -->
        <a href="https://bappeda.malutprov.go.id/" target="_blank" class="running-item">
            <img src="{{ asset('frontend/img/logo.webp') }}" alt="Bappeda Maluku Utara" class="logo" loading="lazy">
            <span>BAPPEDA MALUT</span>
        </a>
        <a href="https://opendata.malutprov.go.id/" target="_blank" class="running-item">
            <img src="{{ asset('frontend/img/logo-opendata-malut.png') }}" alt="Opendata Maluku Utara" class="logo"
                loading="lazy">
        </a>
        <a href="https://malut.bps.go.id/id" target="_blank" class="running-item">
            <img src="{{ asset('frontend/img/logo-bps.webp') }}" alt="BPS Maluku Utara" class="logo" loading="lazy">
            <span>BADAN PUSAT STATISTIK</span>
        </a>
        <a href="https://bappenas.go.id/" target="_blank" class="running-item">
            <img src="{{ asset('frontend/img/logo-bappenas.png') }}" alt="BAPPENAS" class="logo" loading="lazy">
            <span>BAPPENAS</span>
        </a>
    </div>
</section> --}}

<!-- filepath: d:\Projects\LARAGON\marimoi\resources\views\frontend\pages\index-section\running-text.blade.php -->
<style>
    /* Custom animations yang tidak tersedia di Tailwind */
    @keyframes scroll {
        0% {
            transform: translateX(0%);
        }
        100% {
            transform: translateX(-50%);
        }
    }
    
    .running-content {
        animation: scroll 30s linear infinite;
        width: max-content;
    }
    
    /* Pause animation on hover */
    .running-text:hover .running-content {
        animation-play-state: paused;
    }
</style>

<section class="bg-[var(--bg-primary)] py-[25px] overflow-hidden border-t border-b border-[#333]">
    <div class="running-content flex whitespace-nowrap items-center">
        <!-- First set -->
        <a href="https://bappeda.malutprov.go.id/" target="_blank" 
           class="mr-20 md:mr-10 sm:mr-8 xs:mr-[30px] flex items-center gap-4 md:gap-[10px] sm:gap-2 xs:gap-[8px] no-underline text-white transition-all duration-300 ease-in-out py-2 px-4 md:px-3 sm:px-2 xs:px-[8px] rounded-lg flex-shrink-0 hover:-translate-y-0.5 group">
            <img src="{{ asset('frontend/img/logo.webp') }}" 
                 alt="Bappeda Maluku Utara" 
                 class="w-auto h-9 md:h-6 sm:h-5 xs:h-5 brightness-0 invert transition-all duration-300 ease-in-out group-hover:brightness-100 group-hover:invert-0 group-hover:scale-105"
                 loading="lazy">
            <span class="text-[36px] md:text-xl sm:text-base xs:text-base font-bold font-[Inter,sans-serif] text-white transition-all duration-300 ease-in-out">
                BAPPEDA MALUT
            </span>
        </a>
        
        <a href="https://opendata.malutprov.go.id/" target="_blank" 
           class="mr-20 md:mr-10 sm:mr-8 xs:mr-[30px] flex items-center gap-4 md:gap-[10px] sm:gap-2 xs:gap-[8px] no-underline text-white transition-all duration-300 ease-in-out py-2 px-4 md:px-3 sm:px-2 xs:px-[8px] rounded-lg flex-shrink-0 hover:-translate-y-0.5 group">
            <img src="{{ asset('frontend/img/logo-opendata-malut.png') }}" 
                 alt="Opendata Maluku Utara" 
                 class="w-auto h-9 md:h-6 sm:h-5 xs:h-5 brightness-0 invert transition-all duration-300 ease-in-out group-hover:brightness-100 group-hover:invert-0 group-hover:scale-105"
                 loading="lazy">
        </a>
        
        <a href="https://malut.bps.go.id/id" target="_blank" 
           class="mr-20 md:mr-10 sm:mr-8 xs:mr-[30px] flex items-center gap-4 md:gap-[10px] sm:gap-2 xs:gap-[8px] no-underline text-white transition-all duration-300 ease-in-out py-2 px-4 md:px-3 sm:px-2 xs:px-[8px] rounded-lg flex-shrink-0 hover:-translate-y-0.5 group">
            <img src="{{ asset('frontend/img/logo-bps.webp') }}" 
                 alt="BPS Maluku Utara" 
                 class="w-auto h-9 md:h-6 sm:h-5 xs:h-5 brightness-0 invert transition-all duration-300 ease-in-out group-hover:brightness-100 group-hover:invert-0 group-hover:scale-105"
                 loading="lazy">
            <span class="text-[36px] md:text-xl sm:text-base xs:text-base font-bold font-[Inter,sans-serif] text-white transition-all duration-300 ease-in-out">
                BADAN PUSAT STATISTIK
            </span>
        </a>
        
        <a href="https://bappenas.go.id/" target="_blank" 
           class="mr-20 md:mr-10 sm:mr-8 xs:mr-[30px] flex items-center gap-4 md:gap-[10px] sm:gap-2 xs:gap-[8px] no-underline text-white transition-all duration-300 ease-in-out py-2 px-4 md:px-3 sm:px-2 xs:px-[8px] rounded-lg flex-shrink-0 hover:-translate-y-0.5 group">
            <img src="{{ asset('frontend/img/logo-bappenas.png') }}" 
                 alt="BAPPENAS" 
                 class="w-auto h-9 md:h-6 sm:h-5 xs:h-5 brightness-0 invert transition-all duration-300 ease-in-out group-hover:brightness-100 group-hover:invert-0 group-hover:scale-105"
                 loading="lazy">
            <span class="text-[36px] md:text-xl sm:text-base xs:text-base font-bold font-[Inter,sans-serif] text-white transition-all duration-300 ease-in-out">
                BAPPENAS
            </span>
        </a>
        
        <!-- Duplicate set for seamless loop -->
        <a href="https://bappeda.malutprov.go.id/" target="_blank" 
           class="mr-20 md:mr-10 sm:mr-8 xs:mr-[30px] flex items-center gap-4 md:gap-[10px] sm:gap-2 xs:gap-[8px] no-underline text-white transition-all duration-300 ease-in-out py-2 px-4 md:px-3 sm:px-2 xs:px-[8px] rounded-lg flex-shrink-0 hover:-translate-y-0.5 group">
            <img src="{{ asset('frontend/img/logo.webp') }}" 
                 alt="Bappeda Maluku Utara" 
                 class="w-auto h-9 md:h-6 sm:h-5 xs:h-5 brightness-0 invert transition-all duration-300 ease-in-out group-hover:brightness-100 group-hover:invert-0 group-hover:scale-105"
                 loading="lazy">
            <span class="text-[36px] md:text-xl sm:text-base xs:text-base font-bold font-[Inter,sans-serif] text-white transition-all duration-300 ease-in-out">
                BAPPEDA MALUT
            </span>
        </a>
        
        <a href="https://opendata.malutprov.go.id/" target="_blank" 
           class="mr-20 md:mr-10 sm:mr-8 xs:mr-[30px] flex items-center gap-4 md:gap-[10px] sm:gap-2 xs:gap-[8px] no-underline text-white transition-all duration-300 ease-in-out py-2 px-4 md:px-3 sm:px-2 xs:px-[8px] rounded-lg flex-shrink-0 hover:-translate-y-0.5 group">
            <img src="{{ asset('frontend/img/logo-opendata-malut.png') }}" 
                 alt="Opendata Maluku Utara" 
                 class="w-auto h-9 md:h-6 sm:h-5 xs:h-5 brightness-0 invert transition-all duration-300 ease-in-out group-hover:brightness-100 group-hover:invert-0 group-hover:scale-105"
                 loading="lazy">
        </a>
        
        <a href="https://malut.bps.go.id/id" target="_blank" 
           class="mr-20 md:mr-10 sm:mr-8 xs:mr-[30px] flex items-center gap-4 md:gap-[10px] sm:gap-2 xs:gap-[8px] no-underline text-white transition-all duration-300 ease-in-out py-2 px-4 md:px-3 sm:px-2 xs:px-[8px] rounded-lg flex-shrink-0 hover:-translate-y-0.5 group">
            <img src="{{ asset('frontend/img/logo-bps.webp') }}" 
                 alt="BPS Maluku Utara" 
                 class="w-auto h-9 md:h-6 sm:h-5 xs:h-5 brightness-0 invert transition-all duration-300 ease-in-out group-hover:brightness-100 group-hover:invert-0 group-hover:scale-105"
                 loading="lazy">
            <span class="text-[36px] md:text-xl sm:text-base xs:text-base font-bold font-[Inter,sans-serif] text-white transition-all duration-300 ease-in-out">
                BADAN PUSAT STATISTIK
            </span>
        </a>
        
        <a href="https://bappenas.go.id/" target="_blank" 
           class="mr-20 md:mr-10 sm:mr-8 xs:mr-[30px] flex items-center gap-4 md:gap-[10px] sm:gap-2 xs:gap-[8px] no-underline text-white transition-all duration-300 ease-in-out py-2 px-4 md:px-3 sm:px-2 xs:px-[8px] rounded-lg flex-shrink-0 hover:-translate-y-0.5 group">
            <img src="{{ asset('frontend/img/logo-bappenas.png') }}" 
                 alt="BAPPENAS" 
                 class="w-auto h-9 md:h-6 sm:h-5 xs:h-5 brightness-0 invert transition-all duration-300 ease-in-out group-hover:brightness-100 group-hover:invert-0 group-hover:scale-105"
                 loading="lazy">
            <span class="text-[36px] md:text-xl sm:text-base xs:text-base font-bold font-[Inter,sans-serif] text-white transition-all duration-300 ease-in-out">
                BAPPENAS
            </span>
        </a>
    </div>
</section>