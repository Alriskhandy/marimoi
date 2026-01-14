<section
    class="indikator-pembangunan relative w-full min-h-[440px] max-h-[65vh] flex items-center overflow-hidden text-white touch-pan-y lg:touch-auto"
    style="touch-action: pan-y; -webkit-overflow-scrolling: touch;">
    <!-- Background slides (rotating client-side) -->
    <div class="parallax-slides absolute inset-0 will-change-transform transition-transform duration-700 ease-[cubic-bezier(0.25,0.46,0.45,0.94)]"
        aria-hidden="true">
        <div class="parallax-slide absolute inset-0 bg-cover bg-center bg-no-repeat opacity-0 bg-gradient-to-bl from-[var(--bg-primary)] to-[var(--bg-secondary)]"
            loading="lazy"></div>
    </div>

    <!-- Konten -->
    <div class="relative z-10 container mx-auto px-4 md:px-8 lg:px-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10 items-center">

            <!-- Kiri: Judul (Filosofi Logo) -->
            <div class="text-center md:text-left">
                <h2 class="text-2xl md:text-4xl font-bold leading-tight drop-shadow-lg">
                    Filosofi Logo
                </h2>
                <p class="mt-2 text-sm md:text-base text-white/90 max-w-[560px]">
                    Penjabaran elemen visual logo MARIMOI yang merepresentasikan identitas, nilai kebersamaan, dan fokus
                    spasial dari platform ini.
                </p>
            </div>

            <!-- Kanan: Slider masing-masing logo sebagai slide terpisah -->
            <div class="w-full">
                <div class="logo-slider relative">
                    <!-- Slide 1 -->
                    <div class="logo-slide relative w-full p-4 rounded-md bg-white/5 backdrop-blur-sm transition-all duration-300 ease-out"
                        role="group" aria-roledescription="slide" aria-label="Huruf M">
                        <div class="flex flex-col items-center text-center">
                            <img src="{{ asset('frontend/img/filosofi/filosofi-1.png') }}" alt="Huruf M - Filosofi"
                                loading="lazy"
                                class="w-auto h-[88px] object-contain mb-3 transform transition-transform duration-300 ease-out">
                            <h4 class="text-lg md:text-xl font-semibold text-white uppercase tracking-wide">Huruf “M”
                            </h4>
                            <p class="text-xs md:text-sm text-slate-200 mt-2 max-w-[420px]">
                                Huruf "M" adalah inisial MARIMOI. Bentuknya dibuat sederhana dan tegas untuk
                                menyampaikan identitas platform yang mudah dikenali,
                                stabil, dan dapat diandalkan sebagai portal informasi spasial.
                            </p>
                        </div>
                    </div>

                    <!-- Slide 2 -->
                    <div class="logo-slide hidden relative w-full p-4 rounded-md bg-white/5 backdrop-blur-sm transition-all duration-300 ease-out"
                        role="group" aria-roledescription="slide" aria-label="Kebersamaan">
                        <div class="flex flex-col items-center text-center">
                            <img src="{{ asset('frontend/img/filosofi/filosofi-2.png') }}" alt="Kebersamaan - Filosofi"
                                loading="lazy"
                                class="w-auto h-[88px] object-contain mb-3 transform transition-transform duration-300 ease-out">
                            <h4 class="text-lg md:text-xl font-semibold text-white uppercase tracking-wide">Kebersamaan
                            </h4>
                            <p class="text-xs md:text-sm text-slate-200 mt-2 max-w-[420px]">
                                Dua figur yang saling berdekatan mewakili semangat gotong royong, kolaborasi, dan
                                hubungan antar-komunitas.
                                Warna biru yang dominan menunjukkan kepercayaan dan keterhubungan antarwilayah.
                            </p>
                        </div>
                    </div>

                    <!-- Slide 3 -->
                    <div class="logo-slide hidden relative w-full p-4 rounded-md bg-white/5 backdrop-blur-sm transition-all duration-300 ease-out"
                        role="group" aria-roledescription="slide" aria-label="Lokasi">
                        <div class="flex flex-col items-center text-center">
                            <img src="{{ asset('frontend/img/filosofi/filosofi-3.png') }}" alt="Lokasi - Filosofi"
                                loading="lazy"
                                class="w-auto h-[88px] object-contain mb-3 transform transition-transform duration-300 ease-out">
                            <h4 class="text-lg md:text-xl font-semibold text-white uppercase tracking-wide">Lokasi</h4>
                            <p class="text-xs md:text-sm text-slate-200 mt-2 max-w-[420px]">
                                Elemen lokasi (pin) menegaskan fokus MARIMOI pada peta dan data spasial. Bentuknya
                                menyatukan aspek identitas dan konteks geografis.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Navigation: replace indikator content with simple slider controls (3 slides) -->

    <!-- Mobile/Tablet: Bottom Center -->
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 lg:hidden z-10">
        <div class="flex space-x-3" role="tablist" aria-label="Logo slides">
            <button
                class="logo-nav-dot w-2 h-2 rounded-full bg-blue-400 ring-2 ring-white transition-all duration-200 active"
                aria-label="Logo slide 1" data-index="0" role="tab" aria-selected="true"
                aria-controls="slide-0"></button>
            <button
                class="logo-nav-dot w-2 h-2 rounded-full bg-white/60 ring-2 ring-white/80 transition-all duration-200"
                aria-label="Logo slide 2" data-index="1" role="tab" aria-selected="false"
                aria-controls="slide-1"></button>
            <button
                class="logo-nav-dot w-2 h-2 rounded-full bg-white/60 ring-2 ring-white/80 transition-all duration-200"
                aria-label="Logo slide 3" data-index="2" role="tab" aria-selected="false"
                aria-controls="slide-2"></button>
        </div>
    </div>

    <!-- Desktop: Right Center Vertical -->
    <div class="hidden lg:flex absolute right-6 top-1/2 -translate-y-1/2 flex-col items-center space-y-6 z-10">
        <button
            class="logo-prev bg-white/20 hover:bg-white/40 rounded-full p-2.5 backdrop-blur-md transition-all duration-300 shadow-lg"
            aria-label="Previous slide">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z" />
            </svg>
        </button>

        <div class="flex flex-col space-y-3" role="tablist" aria-label="Logo slides desktop">
            <button
                class="logo-nav-dot w-3 h-3 rounded-full bg-blue-400 ring-2 ring-white transition-all duration-200 active"
                aria-label="Logo slide 1" data-index="0" role="tab" aria-selected="true"
                aria-controls="slide-0"></button>
            <button
                class="logo-nav-dot w-3 h-3 rounded-full bg-white/60 ring-2 ring-white/80 transition-all duration-200"
                aria-label="Logo slide 2" data-index="1" role="tab" aria-selected="false"
                aria-controls="slide-1"></button>
            <button
                class="logo-nav-dot w-3 h-3 rounded-full bg-white/60 ring-2 ring-white/80 transition-all duration-200"
                aria-label="Logo slide 3" data-index="2" role="tab" aria-selected="false"
                aria-controls="slide-2"></button>
        </div>

        <button
            class="logo-next bg-white/20 hover:bg-white/40 rounded-full p-2.5 backdrop-blur-md transition-all duration-300 shadow-lg"
            aria-label="Next slide">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z" />
            </svg>
        </button>
    </div>
</section>

@push('scripts')
    <script>
        (function() {
            // existing parallax background code (unchanged)
            const container = document.querySelector('.parallax-slides');
            if (!container) return;
            const slides = Array.from(container.querySelectorAll('.parallax-slide'));
            if (slides.length === 0) return;
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
            const interval = 3000;
            let timerId = null;
            let lastTimestamp = performance.now();

            function showSlideBg(index) {
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
                    showSlideBg((current + 1) % slides.length);
                    scheduleNext();
                }, delay);
            }
            lastTimestamp = performance.now();
            scheduleNext();
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    if (timerId) {
                        clearTimeout(timerId);
                        timerId = null;
                    }
                } else {
                    lastTimestamp = performance.now();
                    scheduleNext();
                }
            });

            // NEW: simple logo slider logic (3 slides) with autoplay + indicators
            const logoSlides = Array.from(document.querySelectorAll('.logo-slide'));
            const logoDots = Array.from(document.querySelectorAll('.logo-nav-dot'));
            const prevBtn = document.querySelector('.logo-prev');
            const nextBtn = document.querySelector('.logo-next');
            let logoIndex = 0;
            let autoplayTimer = null;
            const autoplayInterval = 4200;
            const pauseOnInteractionMs = 7000;

            function setIndicatorActive(i) {
                logoDots.forEach((d, idx) => {
                    const selected = idx === i;
                    d.classList.toggle('bg-blue-400', selected);
                    d.classList.toggle('bg-white/60', !selected);
                    d.setAttribute('aria-selected', selected ? 'true' : 'false');
                });
            }

            function activateLogo(index, userTriggered = false) {
                logoSlides.forEach((s, i) => {
                    s.classList.toggle('hidden', i !== index);
                    s.setAttribute('aria-hidden', i !== index ? 'true' : 'false');
                });
                setIndicatorActive(index);
                logoIndex = index;

                // pause autoplay briefly after user interaction
                if (userTriggered) {
                    stopAutoplay();
                    clearTimeout(autoplayTimer);
                    autoplayTimer = setTimeout(() => {
                        startAutoplay();
                    }, pauseOnInteractionMs);
                }
            }

            logoDots.forEach(d => {
                d.addEventListener('click', (e) => {
                    const idx = Number(d.getAttribute('data-index') || 0);
                    activateLogo(idx, true);
                });
            });

            prevBtn?.addEventListener('click', () => {
                const next = (logoIndex - 1 + logoSlides.length) % logoSlides.length;
                activateLogo(next, true);
            });

            nextBtn?.addEventListener('click', () => {
                const next = (logoIndex + 1) % logoSlides.length;
                activateLogo(next, true);
            });

            // autoplay control
            function startAutoplay() {
                stopAutoplay();
                autoplayTimer = setInterval(() => {
                    const next = (logoIndex + 1) % logoSlides.length;
                    activateLogo(next, false);
                }, autoplayInterval);
            }

            function stopAutoplay() {
                if (autoplayTimer) {
                    clearInterval(autoplayTimer);
                    autoplayTimer = null;
                }
            }

            // pause on hover/touch to improve UX
            const sliderEl = document.querySelector('.logo-slider');
            sliderEl?.addEventListener('mouseenter', () => stopAutoplay());
            sliderEl?.addEventListener('mouseleave', () => startAutoplay());
            sliderEl?.addEventListener('touchstart', () => stopAutoplay(), {
                passive: true
            });

            // init
            activateLogo(0);
            startAutoplay();
        })();
    </script>
@endpush
