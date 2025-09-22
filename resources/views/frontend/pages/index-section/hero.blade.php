<section
    class="gap-2 md:gap-2 lg:gap-6 items-center relative bg-gradient-to-br from-[var(--bg-primary)] to-[var(--bg-secondary)] px-[5%] overflow-visible"
    id="beranda" style="background: url({{ asset('frontend/img/hero/drone.gif') }}) no-repeat center center / cover;">

    <!-- Hero Content -->
    <div class="text-center max-w-full z-[20] relative justify-center pt-20 md:pt-28 lg:pt-28 mt-6 md:mt-10 lg:mt-0">

        <h1
            class="text-[48px] md:text-[64px] lg:text-[85px] font-[Poppins,sans-serif] font-bold tracking-[8px] lg:tracking-[6px] md:tracking-[4px] sm:tracking-[2px] uppercase text-white relative">
            MARIMOI
        </h1>
        <p
            class="text-base md:text-lg mb-8 md:mb-6 sm:mb-6 opacity-90 text-white leading-[1.6] font-normal">
            Sistem Informasi Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi Wilayah
        </p>

        <button
            class="py-[16px] px-[30px] md:py-[14px] md:px-[35px] sm:py-[12px] sm:px-[30px] bg-gradient-to-r from-[var(--primary)] to-[#0066cc] text-[var(--text-primary)] border-none rounded-[50px] text-[1.1rem] md:text-[1.2rem] sm:text-[1.1rem] font-bold cursor-pointer transition-all duration-300 ease-in-out font-[Poppins,sans-serif] shadow-[0_8px_25px_rgba(0,127,255,0.3)] md:shadow-[0_10px_30px_rgba(0,127,255,0.4)] sm:shadow-[0_8px_25px_rgba(0,127,255,0.4)] sm:min-w-[180px] md:min-w-[200px] hover:-translate-y-1 hover:shadow-[0_15px_35px_rgba(0,127,255,0.4)] hover:bg-gradient-to-r hover:from-[#0066cc] hover:to-[var(--primary)]"
            onclick="scrollToSection('peta-tematik')">
            Jelajahi Platform
        </button>
    </div>

    <!-- Mockup placed below the hero text (stacked) -->
    <div class="w-full flex justify-center sm:mt-2 md:mt-5 lg:mt-10 pt-10">
        <div id="mockupPosition" class="mockup-position pointer-events-none z-0">
            <div id="mockupWrapper" class="mockup-wrapper mx-auto">
                <img id="mockupImg" src="{{ asset('frontend/img/mockup/tab-mockup.png') }}"
                    alt="Desktop & Smartphone Mockup"
                    class="mockup-img h-[560px] xl:h-[540px] lg:h-[460px] md:h-[360px] sm:h-[260px] xs:h-[180px] w-auto max-w-[92vw] lg:max-w-[1000px] object-contain pointer-events-none"
                    loading="lazy" decoding="async" sizes="(max-width: 640px) 90vw, (max-width:1024px) 70vw, 1000px"
                    srcset="{{ asset('frontend/img/mockup/tab-mockup.png') }} 1100w">
            </div>
        </div>
    </div>
</section>

@push('styles')
    <style>
        #beranda::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background: rgba(11, 17, 32, 0.8);
        }

        /* Mockup tilt styles - optimized for performance */
        .mockup-wrapper {
            display: inline-block;
            /* lower perspective to reduce heavy 3D math on mobile */
            perspective: 800px;
            will-change: transform;
        }

        .mockup-img {
            /* use gentler 3D tilt on large screens; avoid large rotateX on mobile */
            transform-origin: 50% 82%;
            transform: rotateX(30deg) translateY(0) scale(0.98);
            transition: transform 420ms cubic-bezier(.22,.9,.2,1), filter 420ms ease;
            will-change: transform;
            /* reduce heavy shadows that cause costly paints */
            filter: drop-shadow(0 18px 40px rgba(0,0,0,0.32));
            display: block;
            margin: 0 auto;
            backface-visibility: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Upright state (less dramatic) */
        .mockup-wrapper.upright .mockup-img {
            transform: rotateX(0deg) translateY(-2vh) scale(1);
            transition: transform 520ms cubic-bezier(.22,.9,.2,1), filter 420ms ease;
            filter: drop-shadow(0 10px 30px rgba(0,0,0,0.22));
        }

        .mockup-position {
            transform: translateY(0);
            transition: transform 360ms ease;
            will-change: transform;
        }

        @media (min-width: 1024px) {
            .mockup-position {
                transform: translateY(-6vh);
            }
        }

        /* MOBILE: remove expensive 3D rotation and heavy filters */
        @media (max-width: 768px) {
            .mockup-wrapper {
                perspective: 600px;
            }

            .mockup-img {
                transform: translateY(0) scale(0.98);
                transition: transform 320ms ease;
                filter: drop-shadow(0 8px 20px rgba(0,0,0,0.18));
            }

            .mockup-wrapper.upright .mockup-img {
                transform: translateY(-1.5vh) scale(0.99);
                transition: transform 360ms ease;
                filter: drop-shadow(0 6px 16px rgba(0,0,0,0.14));
            }
        }

        /* Respect user reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            .mockup-img,
            .mockup-position,
            .mockup-wrapper.upright .mockup-img {
                transition: none !important;
                transform: none !important;
            }
        }

        /* Mobile spacing / size overrides to make hero more compact */
        @media (max-width: 640px) {
            /* reduce top padding of the hero text block */
            #beranda .text-center {
                padding-top: 3.5rem !important; /* was large pt-20 */
                margin-top: 4rem !important;
            }

            /* smaller main heading and tighter tracking */
            #beranda h1 {
                line-height: 1.05;
                margin-bottom: 0.7rem;
                padding-bottom: 0;
            }

            /* tighter subtitle */
            #beranda p {
                margin-bottom: 1.2rem;
            }

            /* compact primary button */
            #beranda button {
                padding: 0.7rem 1.2rem !important;
                font-size: 0.95rem !important;
                min-width: 140px !important;
                box-shadow: 0 8px 20px rgba(0,127,255,0.18) !important;
            }

            /* reduce mockup image height on small screens */
            .mockup-img {
                height: 270px !important;
                max-width: 92vw;
            }

            /* reduce vertical gap between text and mockup */
            .w-full > .mockup-position {
                margin-top: 2rem;
                transform: translateY(0);
            }

            /* reduce overlay darkness to keep contrast but lighter on small screens */
            #beranda::before {
                background: rgba(11, 17, 32, 0.6);
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            const wrapper = document.getElementById('mockupWrapper');
            const img = document.getElementById('mockupImg');
            const position = document.getElementById('mockupPosition');

            if (!wrapper || !img || !position) return;

            // Use IntersectionObserver instead of scroll events for better performance on mobile
            const options = {
                root: null,
                // trigger earlier when element enters viewport
                rootMargin: '0px 0px -60% 0px',
                threshold: [0, 0.05, 0.2]
            };

            let observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.intersectionRatio > 0.05) {
                        wrapper.classList.add('upright');
                    } else {
                        wrapper.classList.remove('upright');
                    }
                });
            }, options);

            observer.observe(position);

            // Fallback: small RAF-based check only if IntersectionObserver not supported
            if (!('IntersectionObserver' in window)) {
                let ticking = false;
                function onScroll() {
                    if (!ticking) {
                        window.requestAnimationFrame(() => {
                            const scrollY = window.scrollY || window.pageYOffset;
                            const trigger = Math.max(window.innerHeight * 0.08, 40);
                            if (scrollY > trigger) wrapper.classList.add('upright');
                            else wrapper.classList.remove('upright');
                            ticking = false;
                        });
                        ticking = true;
                    }
                }
                onScroll();
                window.addEventListener('scroll', onScroll, { passive: true });
            }

            // Optional: release observer on page unload to avoid retained observers
            window.addEventListener('beforeunload', () => {
                try { observer.disconnect(); } catch(e){}
            });
        })();
    </script>
@endpush
