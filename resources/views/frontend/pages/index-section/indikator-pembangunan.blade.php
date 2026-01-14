<section
    class="indikator-pembangunan relative w-screen min-h-[600px] max-h-[70vh] flex items-center overflow-hidden text-white touch-pan-y lg:touch-auto"
    style="touch-action: pan-y; -webkit-overflow-scrolling: touch;">
    <!-- Background slides (rotating client-side) -->
    <div class="parallax-slides absolute inset-0 will-change-transform transition-transform duration-700 ease-[cubic-bezier(0.25,0.46,0.45,0.94)]"
        aria-hidden="true">
        @for ($j = 1; $j <= 5; $j++)
            <div class="parallax-slide absolute inset-0 bg-cover bg-center bg-no-repeat opacity-0"
                style="background-image: url('{{ asset('frontend/img/parallax/' . $j . '.jpg') }}');"
                data-swiper-parallax="-50%" data-swiper-parallax-scale="0.8" loading="lazy"></div>
        @endfor
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
                data-description="<p><strong>MARIMOI</strong> meningkatkan <strong>Indeks Pengembangan Wilayah></strong> melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Penyediaan data spasial dan sektoral untuk mengukur keterjangkauan layanan dasar.</li><li>Akselerasi pembangunan infrastruktur di wilayah hinterland dan kawasan tertinggal.</li><li>Integrasi lintas wilayah dalam perencanaan berbasis konektivitas (antarpulau, antarkawasan).</li></ul><p><strong>Hasil:</strong> Distribusi pembangunan menjadi lebih merata, mempersempit disparitas wilayah.<p/>"></button>
            <button
                class="nav-dot w-2 h-2 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 2" data-title="Indeks SPBE (Sistem Pemerintahan Berbasis Elektronik)"
                data-description="<p><strong>MARIMOI</strong> mendorong pencapaian <strong>SPBE</strong> melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Digitalisasi proses perencanaan, monitoring, dan pelaporan infrastruktur.</li><li>Interopabilitas data antar instansi (BAPPEDA, OPD Teknis, DPRD).</li><li>Fitur Dashboard publik sebagai bentuk pelayanan digital transparan.</li></ul><p><strong>Hasil:</strong> Meningkatkan Domain Perencanaan Pembangunan dan Pelayanan Publik Berbasis Elektronik dalam penilaian SPBE.<p/>"></button>
            <button
                class="nav-dot w-2 h-2 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 3" data-title="Indeks Pelayanan Publik"
                data-description="<p><strong>MARIMOI</strong> memberi dampak pada <strong>Indeks Pelayanan Publik</strong> melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Partisipasi masyarakat dalam pelaporan kondisi infrastruktur (jalan rusak, PSU, jembatan, dll).</li><li>Penyediaan data real-time kepada unit pelayanan teknis untuk respon cepat.</li><li>Penguatan kualitas layanan berbasis kebutuhan wilayah, bukan hanya standar sektoral.</li></ul><p><strong>Hasil:</strong> Pelayanan publik lebih responsif, adil, dan terukur.<p/>"></button>
            <button
                class="nav-dot w-2 h-2 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 4" data-title="Indeks Kualitas Layanan Infrastruktur"
                data-description="<p>Melalui <strong>MARIMOI:</strong></p><ul class='list-disc pl-5 space-y-2'><li>Pemerintah dapat memantau kondisi infrastruktur secara spasial dan waktu nyata.</li><li>Sistem mendukung evaluasi kinerja infrastruktur berdasarkan output dan outcome.</li></ul><p><strong>Hasil:</strong> Layanan infrastruktur menjadi lebih merata, berkualitas, dan efisien.<p/>"></button>
            <button
                class="nav-dot w-2 h-2 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 5" data-title="Indeks Desa Membangun (IDM)"
                data-description="<p>Kontribusi <strong>MARIMOI</strong> terhadap <strong>IDM</strong> meliputi:</p><ul class='list-disc pl-5 space-y-2'><li>Mengitegrasikan kebutuhan dan aspirasi desa dalam sistem perencanaan wilayah.</li><li>Menyediakan data dukung untuk pembangunan infrastruktur dasar di desa (jalan, air, listrik).</li><li>Meningkatkan status desa dari <strong>Tertinggal -> Berkembang -> Mandiri</strong> secara terstruktur.</li></ul><p><strong>Hasil:</strong> Intervensi infrastruktur ke desa lebih tepat sasaran dan berdampak langsung terhadap indikator IDM.<p/>"></button>
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
                data-description="<p><strong>MARIMOI</strong> meningkatkan <strong>Indeks Pengembangan Wilayah></strong> melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Penyediaan data spasial dan sektoral untuk mengukur keterjangkauan layanan dasar.</li><li>Akselerasi pembangunan infrastruktur di wilayah hinterland dan kawasan tertinggal.</li><li>Integrasi lintas wilayah dalam perencanaan berbasis konektivitas (antarpulau, antarkawasan).</li></ul><p><strong>Hasil:</strong> Distribusi pembangunan menjadi lebih merata, mempersempit disparitas wilayah.<p/>"></button>
            <button
                class="nav-dot w-3 h-3 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 2" data-title="Indeks SPBE (Sistem Pemerintahan Berbasis Elektronik)"
                data-description="<p><strong>MARIMOI</strong> mendorong pencapaian <strong>SPBE</strong> melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Digitalisasi proses perencanaan, monitoring, dan pelaporan infrastruktur.</li><li>Interopabilitas data antar instansi (BAPPEDA, OPD Teknis, DPRD).</li><li>Fitur Dashboard publik sebagai bentuk pelayanan digital transparan.</li></ul><p><strong>Hasil:</strong> Meningkatkan Domain Perencanaan Pembangunan dan Pelayanan Publik Berbasis Elektronik dalam penilaian SPBE.<p/>"></button>
            <button
                class="nav-dot w-3 h-3 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 3" data-title="Indeks Pelayanan Publik"
                data-description="<p><strong>MARIMOI</strong> memberi dampak pada <strong>Indeks Pelayanan Publik</strong> melalui:</p><ul class='list-disc pl-5 space-y-2'><li>Partisipasi masyarakat dalam pelaporan kondisi infrastruktur (jalan rusak, PSU, jembatan, dll).</li><li>Penyediaan data real-time kepada unit pelayanan teknis untuk respon cepat.</li><li>Penguatan kualitas layanan berbasis kebutuhan wilayah, bukan hanya standar sektoral.</li></ul><p><strong>Hasil:</strong> Pelayanan publik lebih responsif, adil, dan terukur.<p/>"></button>
            <button
                class="nav-dot w-3 h-3 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 4" data-title="Indeks Kualitas Layanan Infrastruktur"
                data-description="<p>Melalui <strong>MARIMOI:</strong></p><ul class='list-disc pl-5 space-y-2'><li>Pemerintah dapat memantau kondisi infrastruktur secara spasial dan waktu nyata.</li><li>Sistem mendukung evaluasi kinerja infrastruktur berdasarkan output dan outcome.</li></ul><p><strong>Hasil:</strong> Layanan infrastruktur menjadi lebih merata, berkualitas, dan efisien.<p/>"></button>
            <button
                class="nav-dot w-3 h-3 rounded-full bg-white/60 hover:bg-blue-400 ring-2 ring-white/80 shadow-lg transition-all duration-300 hover:scale-110"
                aria-label="Slide 5" data-title="Indeks Desa Membangun (IDM)"
                data-description="<p>Kontribusi <strong>MARIMOI</strong> terhadap <strong>IDM</strong> meliputi:</p><ul class='list-disc pl-5 space-y-2'><li>Mengitegrasikan kebutuhan dan aspirasi desa dalam sistem perencanaan wilayah.</li><li>Menyediakan data dukung untuk pembangunan infrastruktur dasar di desa (jalan, air, listrik).</li><li>Meningkatkan status desa dari <strong>Tertinggal -> Berkembang -> Mandiri</strong> secara terstruktur.</li></ul><p><strong>Hasil:</strong> Intervensi infrastruktur ke desa lebih tepat sasaran dan berdampak langsung terhadap indikator IDM.<p/>"></button>
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

@push('scripts')
    <script>
        (function() {
            const container = document.querySelector('.parallax-slides');
            if (!container) return;
            const slides = Array.from(container.querySelectorAll('.parallax-slide'));
            if (slides.length === 0) return;

            // initial show first slide
            let current = 0;
            slides.forEach((s, i) => {
                s.style.transition = 'opacity 900ms ease-in-out';
                s.style.willChange = 'opacity, transform';
                s.setAttribute('aria-hidden', 'true');
                s.style.transform = 'translateZ(0)';
            });
            slides[current].classList.add('active');
            slides[current].style.opacity = '1';
            slides[current].setAttribute('aria-hidden', 'false');

            // cycle slides every 3s with fade using a timestamp-based scheduler for consistency
            const interval = 3000; // target interval in ms
            let timerId = null;
            let lastTimestamp = performance.now();

            function showSlide(index) {
                const prev = current;
                if (prev === index) return;
                slides[prev].classList.remove('active');
                slides[prev].style.opacity = '0';
                slides[prev].setAttribute('aria-hidden', 'true');

                current = index;
                slides[current].classList.add('active');
                slides[current].style.opacity = '1';
                slides[current].setAttribute('aria-hidden', 'false');
            }

            function scheduleNext() {
                if (timerId) clearTimeout(timerId);
                const now = performance.now();
                const elapsed = now - lastTimestamp;
                const delay = Math.max(0, interval - elapsed);
                timerId = setTimeout(() => {
                    lastTimestamp = performance.now();
                    showSlide((current + 1) % slides.length);
                    scheduleNext();
                }, delay);
            }

            // start the loop
            lastTimestamp = performance.now();
            scheduleNext();

            // pause/resume on visibilitychange to keep timing consistent
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    if (timerId) {
                        clearTimeout(timerId);
                        timerId = null;
                    }
                } else {
                    // resume, compute a fresh baseline so next change occurs ~interval ms from now
                    lastTimestamp = performance.now();
                    scheduleNext();
                }
            });
        })();
    </script>
@endpush
