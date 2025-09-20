<section
    class="gap-2 md:gap-2 lg:gap-6 items-center relative bg-gradient-to-br from-[var(--bg-primary)] to-[var(--bg-secondary)] px-[5%] overflow-visible"
    id="beranda">

    <!-- Hero Content -->
    <div class="text-center max-w-full z-[20] relative justify-center pt-20 md:pt-28 lg:pt-28 mt-6 md:mt-10 lg:mt-0">

        <h1
            class="text-[48px] md:text-[64px] lg:text-[85px] font-[Poppins,sans-serif] font-bold tracking-[8px] lg:tracking-[6px] md:tracking-[4px] sm:tracking-[2px] uppercase text-white relative">
            MARIMOI
        </h1>
        <p
            class="text-base md:text-lg mb-8 md:mb-6 sm:mb-6 opacity-90 text-[var(--text-secondary)] leading-[1.6] font-normal">
            Sistem Informasi Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi Wilayah
        </p>

        <button
            class="py-[16px] px-[30px] md:py-[14px] md:px-[35px] sm:py-[12px] sm:px-[30px] bg-gradient-to-r from-[var(--primary)] to-[#0066cc] text-[var(--text-primary)] border-none rounded-[50px] text-[1.1rem] md:text-[1.2rem] sm:text-[1.1rem] font-bold cursor-pointer transition-all duration-300 ease-in-out font-[Poppins,sans-serif] shadow-[0_8px_25px_rgba(0,127,255,0.3)] md:shadow-[0_10px_30px_rgba(0,127,255,0.4)] sm:shadow-[0_8px_25px_rgba(0,127,255,0.4)] sm:min-w-[180px] md:min-w-[200px] hover:-translate-y-1 hover:shadow-[0_15px_35px_rgba(0,127,255,0.4)] hover:bg-gradient-to-r hover:from-[#0066cc] hover:to-[var(--primary)]"
            onclick="scrollToSection('peta-tematik')">
            Jelajahi Platform
        </button>
    </div>

    <!-- Mockup placed below the hero text (stacked) -->
    <div class="w-full flex justify-center sm:mt-2 md:mt-5 lg:mt-10">
        <div id="mockupPosition" class="mockup-position pointer-events-none z-0">
            <div id="mockupWrapper" class="mockup-wrapper mx-auto">
                <img id="mockupImg" src="{{ asset('frontend/img/mockup/tablet.png') }}"
                    alt="Desktop & Smartphone Mockup"
                    class="mockup-img h-[640px] xl:h-[620px] lg:h-[520px] md:h-[420px] sm:h-[320px] xs:h-[220px] w-auto max-w-[95vw] lg:max-w-[1100px] object-contain pointer-events-none"
                    loading="lazy">
            </div>
        </div>
    </div>
</section>

@push('styles')
    <style>
        /* Mockup tilt styles */
        .mockup-wrapper {
            display: inline-block;
            perspective: 1200px;
        }

        .mockup-img {
            /* pivot lower and subtle translate so the image sits below the text */
            transform-origin: 50% 82%;
            transform: rotateX(14deg) translateY(0px) scale(0.98) rotateZ(-6deg);
            transition: transform 700ms cubic-bezier(.22, .9, .2, 1), filter 600ms ease;
            will-change: transform;
            filter: drop-shadow(0 36px 80px rgba(0, 0, 0, 0.48));
            display: block;
            margin: 0 auto;
        }

        /* lift the mockup slightly above the section end on large screens while keeping it below the text */
        .mockup-position {
            transform: translateY(0);
            transition: transform 400ms ease;
        }

        @media (min-width: 1024px) {
            .mockup-position {
                transform: translateY(-6vh);
            }
        }

        .mockup-wrapper.upright .mockup-img {
            transform: rotateX(0deg) translateY(0px) scale(1) rotateZ(0deg);
            transition: transform 700ms cubic-bezier(.22, .9, .2, 1), filter 600ms ease;
            filter: drop-shadow(0 12px 40px rgba(0, 0, 0, 0.28));
        }


        @media (max-width: 768px) {
            .mockup-img {
                transform: rotateX(12deg) translateY(0px) scale(0.98) rotateZ(-3deg);
            }

            .mockup-wrapper.upright .mockup-img {
                transform: rotateX(0deg) translateY(0px) scale(1) rotateZ(0deg);
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            const wrapper = document.getElementById('mockupWrapper');
            const img = document.getElementById('mockupImg');

            if (!wrapper || !img) return;

            let ticking = false;

            function onScroll() {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        const scrollY = window.scrollY || window.pageYOffset;
                        const trigger = Math.max(window.innerHeight * 0.08, 40);

                        if (scrollY > trigger) {
                            wrapper.classList.add('upright');
                        } else {
                            wrapper.classList.remove('upright');
                        }

                        ticking = false;
                    });
                    ticking = true;
                }
            }

            // initial check (in case user starts mid-page)
            onScroll();

            window.addEventListener('scroll', onScroll, {
                passive: true
            });
        })();
    </script>
@endpush
