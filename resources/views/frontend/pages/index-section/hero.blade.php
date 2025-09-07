@push('styles')
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
</section>
