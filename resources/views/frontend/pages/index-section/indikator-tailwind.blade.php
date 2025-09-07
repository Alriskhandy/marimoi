<section
    class="indikator-pembangunan relative w-screen min-h-[600px] max-h-[70vh] flex items-center overflow-hidden text-white touch-pan-y lg:touch-auto"
    style="touch-action: pan-y; -webkit-overflow-scrolling: touch;">
    <!-- Background dengan Parallax -->
    @php
        $i = Arr::random([1, 2, 3, 4, 5]);
    @endphp
    <div class="parallax-bg absolute inset-0 will-change-transform transition-transform duration-700 ease-[cubic-bezier(0.25,0.46,0.45,0.94)]"
        style="background-image: url('{{ asset('frontend/img/parallax/' . $i . '.jpg') }}'); 
               background-size: cover; 
               background-position: center; 
               background-repeat: no-repeat;"
        data-swiper-parallax="-50%" data-swiper-parallax-scale="0.8" loading="lazy">
    </div> <!-- Dark Overlay -->
    <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/40 to-black/60 z-[1]"></div>

    <!-- Konten -->
    <div class="relative z-10 container mx-auto px-6 md:px-12 lg:px-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">

            <!-- Kiri: Judul -->
            <div class="text-center md:text-left">
                <h2 class="text-3xl md:text-5xl font-bold leading-tight drop-shadow-lg">
                    Indikator <br class="hidden md:block">
                    Pembangunan <br class="hidden md:block">
                    Strategis
                </h2>
            </div>

            <!-- Kanan: Subtitle + Deskripsi -->
            <div class="text-sm md:text-base lg:text-lg leading-relaxed space-y-4">
                <h3 id="dynamicSubtitle" class="text-xl md:text-2xl font-semibold drop-shadow-md">
                    Indeks Pengembangan Wilayah
                </h3>
                <div id="dynamicDescription" class="space-y-3 drop-shadow-sm">
                    <p>MARIMOI meningkatkan Indeks Pengembangan Wilayah melalui:</p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>Penyediaan data spasial dan sektoral untuk mengukur keterjangkauan layanan dasar.</li>
                        <li>Akselerasi pembangunan infrastruktur di wilayah hinterland dan kawasan tertinggal.</li>
                        <li>Integrasi lintas wilayah dalam perencanaan berbasis konektivitas (antarpulau, antarkawasan).
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigasi Bullets -->
    <!-- Mobile/Tablet: Bottom Center -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 lg:hidden z-10">
        <div class="flex space-x-3">
            <button
                class="nav-dot w-2 h-2 rounded-full bg-blue-400 ring-2 ring-white shadow-lg transition-all duration-300 hover:scale-110 active"
                aria-label="Slide 1" data-title="Indeks Pengembangan Wilayah"
                data-description="<p>MARIMOI meningkatkan Indeks Pengembangan Wilayah melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Penyediaan data spasial dan sektoral untuk mengukur keterjangkauan layanan dasar.</li><li>Akselerasi pembangunan infrastruktur di wilayah hinterland dan kawasan tertinggal.</li><li>Integrasi lintas wilayah dalam perencanaan berbasis konektivitas (antarpulau, antarkawasan).</li></ul>"></button>
            <button
                class="nav-dot w-2 h-2 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 2" data-title="Indeks Kualitas Lingkungan Hidup"
                data-description="<p>MARIMOI meningkatkan kualitas lingkungan hidup melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Monitoring kualitas air dan udara secara real-time.</li><li>Pengelolaan sampah dan limbah berkelanjutan.</li><li>Konservasi ekosistem laut dan darat.</li></ul>"></button>
            <button
                class="nav-dot w-2 h-2 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 3" data-title="Indeks Pembangunan Ekonomi"
                data-description="<p>MARIMOI mendorong pertumbuhan ekonomi melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Pengembangan sektor pariwisata dan perikanan.</li><li>Peningkatan akses pasar dan investasi.</li><li>Pemberdayaan UMKM dan koperasi lokal.</li></ul>"></button>
            <button
                class="nav-dot w-2 h-2 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 4" data-title="Indeks Kualitas Sumber Daya Manusia"
                data-description="<p>MARIMOI meningkatkan kualitas SDM melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Program pendidikan dan pelatihan vokasi.</li><li>Peningkatan akses layanan kesehatan.</li><li>Pengembangan kapasitas aparatur dan masyarakat.</li></ul>"></button>
        </div>
    </div>

    <!-- Desktop: Right Center Vertical -->
    <div
        class="hidden lg:flex absolute right-6 top-1/2 -translate-y-1/2 flex-col items-center space-y-6 z-10 indikator-nav-buttons">
        <!-- Tombol Previous -->
        <button
            class="nav-btn-prev bg-white/20 hover:bg-white/40 rounded-full p-2.5 backdrop-blur-md transition-all duration-300 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
            aria-label="Previous">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z" />
            </svg>
        </button>

        <!-- Bullets -->
        <div class="flex flex-col space-y-3">
            <button
                class="nav-dot w-3 h-3 rounded-full bg-blue-400 ring-2 ring-white shadow-lg transition-all duration-300 hover:scale-110 active"
                aria-label="Slide 1" data-title="Indeks Pengembangan Wilayah"
                data-description="<p>MARIMOI meningkatkan Indeks Pengembangan Wilayah melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Penyediaan data spasial dan sektoral untuk mengukur keterjangkauan layanan dasar.</li><li>Akselerasi pembangunan infrastruktur di wilayah hinterland dan kawasan tertinggal.</li><li>Integrasi lintas wilayah dalam perencanaan berbasis konektivitas (antarpulau, antarkawasan).</li></ul>"></button>
            <button
                class="nav-dot w-3 h-3 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 2" data-title="Indeks Kualitas Lingkungan Hidup"
                data-description="<p>MARIMOI meningkatkan kualitas lingkungan hidup melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Monitoring kualitas air dan udara secara real-time.</li><li>Pengelolaan sampah dan limbah berkelanjutan.</li><li>Konservasi ekosistem laut dan darat.</li></ul>"></button>
            <button
                class="nav-dot w-3 h-3 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 3" data-title="Indeks Pembangunan Ekonomi"
                data-description="<p>MARIMOI mendorong pertumbuhan ekonomi melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Pengembangan sektor pariwisata dan perikanan.</li><li>Peningkatan akses pasar dan investasi.</li><li>Pemberdayaan UMKM dan koperasi lokal.</li></ul>"></button>
            <button
                class="nav-dot w-3 h-3 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 4" data-title="Indeks Kualitas Sumber Daya Manusia"
                data-description="<p>MARIMOI meningkatkan kualitas SDM melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Program pendidikan dan pelatihan vokasi.</li><li>Peningkatan akses layanan kesehatan.</li><li>Pengembangan kapasitas aparatur dan masyarakat.</li></ul>"></button>
        </div>

        <!-- Tombol Next -->
        <button
            class="nav-btn-next bg-white/20 hover:bg-white/40 rounded-full p-2.5 backdrop-blur-md transition-all duration-300 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
            aria-label="Next">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z" />
            </svg>
        </button>
    </div>
</section>
