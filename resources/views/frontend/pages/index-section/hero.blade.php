{{-- @push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/section/hero.css') }}">
@endpush

<section class="hero" id="beranda">
    <div class="hero-content">
        <h1>MARIMOI</h1>
        <p>Sistem Informasi Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi Wilayah</p>
        <button class="cta-button" onclick="scrollToSection('peta-tematik')">
            Jelajahi Platform
        </button>
    </div>

    <div class="map-3d-container">
        <model-viewer src="{{ asset('frontend/models/kantor-gub-3D.glb') }}" alt="3D Model Kantor Gubernur Maluku Utara"
            camera-controls auto-rotate auto-rotate-delay="1500" rotation-per-second="20deg" shadow-intensity="2"
            shadow-softness="0.3" exposure="1.5" camera-orbit="-45deg 55deg" environment-image="neutral"
            interaction-prompt="none" loading="lazy" style="width: 100%; height: 100%; background: transparent;">

            <!-- Loading text -->
            <div slot="poster"
                style="
                    background: transparent; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    flex-direction: column; 
                    color: #007FFF; 
                    font-family: 'DM Mono', monospace; 
                    height: 100%;">
                <div>
                    <p style="margin: 0; font-size: 14px; opacity: 0.8;">Loading 3D Model...</p>
                </div>

                <style>
                    @keyframes spin {
                        …
                    }
                </style>
            </div>
        </model-viewer>
    </div>
</section> --}}

<!-- filepath: d:\Projects\LARAGON\marimoi\resources\views\frontend\pages\index-section\hero.blade.php -->
<style>
    /* Custom styles untuk model-viewer dan animations yang tidak tersedia di Tailwind */
    model-viewer {
        width: 100%;
        height: 400px;
        background: transparent;
        --poster-color: transparent;
        --progress-bar-color: transparent;
    }
    
    model-viewer[loading] {
        opacity: 0.8;
    }
    
    model-viewer[loaded] {
        opacity: 1;
        transition: opacity 0.5s ease;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Responsive model-viewer */
    @media (max-width: 768px) {
        model-viewer {
            height: 280px;
        }
    }
    
    @media (max-width: 480px) {
        model-viewer {
            height: 220px;
        }
    }
</style>

<section class="h-screen grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-2 lg:gap-6 items-center relative bg-gradient-to-br from-[var(--bg-primary)] to-[var(--bg-secondary)] px-[5%] overflow-hidden" id="beranda">
    <!-- Pseudo elements dengan Tailwind tidak bisa, jadi menggunakan div overlay -->
    <div class="absolute top-0 left-0 right-0 bottom-0 pointer-events-none z-[1]"></div>
    <div class="absolute bottom-0 right-[10%] w-1/2 h-[60%] pointer-events-none z-[1] blur-[20px]"></div>
    
    <!-- Hero Content -->
    <div class="text-center md:text-left max-w-full z-[2] relative justify-center md:justify-self-start pt-32 md:pt-16 lg:pt-8 mt-8 md:mt-12 lg:mt-0">
        
        <h1 class="text-[48px] md:text-[64px] lg:text-[85px] font-[Poppins,sans-serif] font-bold tracking-[8px] lg:tracking-[6px] md:tracking-[4px] sm:tracking-[2px] uppercase text-white relative">
            MARIMOI
        </h1>
        <p class="text-base md:text-lg mb-8 md:mb-6 sm:mb-6 opacity-90 text-[var(--text-secondary)] leading-[1.6] font-normal">
            Sistem Informasi Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi Wilayah
        </p>

        <button class="py-[16px] px-[30px] md:py-[14px] md:px-[35px] sm:py-[12px] sm:px-[30px] bg-gradient-to-r from-[var(--primary)] to-[#0066cc] text-[var(--text-primary)] border-none rounded-[50px] text-[1.1rem] md:text-[1.2rem] sm:text-[1.1rem] font-bold cursor-pointer transition-all duration-300 ease-in-out font-[Poppins,sans-serif] shadow-[0_8px_25px_rgba(0,127,255,0.3)] md:shadow-[0_10px_30px_rgba(0,127,255,0.4)] sm:shadow-[0_8px_25px_rgba(0,127,255,0.4)] sm:min-w-[180px] md:min-w-[200px] hover:-translate-y-1 hover:shadow-[0_15px_35px_rgba(0,127,255,0.4)] hover:bg-gradient-to-r hover:from-[#0066cc] hover:to-[var(--primary)]"
                onclick="scrollToSection('peta-tematik')">
            Jelajahi Platform
        </button>
    </div>

    <!-- 3D Map Container -->
    <div class="relative w-full h-full flex items-center justify-center z-[2] bg-transparent border-none justify-self-end md:justify-self-end sm:justify-self-center md:order-2 sm:order-1 sm:h-[300px] md:h-full">
        <div class="w-full h-[500px] lg:h-[450px] md:h-[350px] sm:h-[280px] xs:h-[220px]">
            <model-viewer 
                src="{{ asset('frontend/models/peta-sofifi.glb') }}" 
                alt="3D Model Kantor Gubernur Maluku Utara"
                camera-controls 
                auto-rotate 
                auto-rotate-delay="1500" 
                rotation-per-second="20deg" 
                shadow-intensity="2"
                shadow-softness="0.3" 
                exposure="1.5" 
                camera-orbit="-45deg 55deg" 
                environment-image="neutral"
                interaction-prompt="none" 
                loading="lazy" 
                class="w-full h-full bg-transparent">

                <!-- Loading text -->
                <div slot="poster" class="bg-transparent flex items-center justify-center flex-col text-[var(--primary)] font-[DM_Mono,monospace] h-full">
                    <div>
                        <p class="m-0 text-sm opacity-80">Loading 3D Model...</p>
                    </div>
                </div>
            </model-viewer>
        </div>
    </div>
</section>