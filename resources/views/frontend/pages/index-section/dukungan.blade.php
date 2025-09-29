@push('styles')
    <style>
        /* Custom styles yang tidak tersedia di Tailwind */
        .swiper-3d .swiper-slide-shadow-left,
        .swiper-3d .swiper-slide-shadow-right {
            background-image: none;
        }

        .swiper-pagination-bullet,
        .swiper-pagination-bullet-active {
            background: #243057;
            opacity: 0.7;
        }

        .swiper-pagination-bullet-active {
            opacity: 1;
            transform: scale(1.2);
        }

        /* Perbaikan posisi pagination */
        .swiper-pagination {
            bottom: -40px !important;
            z-index: 10;
            position: relative !important;
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
        .support-swiper .swiper-slide {
            width: 480px;
            height: 285px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
            min-width: 280px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #ffffff;
            padding: 0;
            /* ensure thumbnail can touch card edges */
        }

        .support-swiper .swiper-slide-active {
            transform: scale(1.05);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.18);
            z-index: 10;
        }

        .support-swiper .swiper-slide:not(.swiper-slide-active) {
            opacity: 0.7;
        }

        .swiper-footer {
            background: #ffffff;
            padding: 12px 20px;
            margin-top: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.08);
            min-height: 70px;
        }

        .swiper-footer h2 {
            color: #243057 !important;
            font-family: "Inter", sans-serif !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            line-height: 1.4 !important;
            margin: 0 !important;
            padding: 0 !important;
            text-align: center;
        }

        /* Video thumbnail styles */
        .video-thumb {
            width: 100%;
            height: 100%;
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            border-radius: 12px 12px 0 0;
            transition: transform 0.25s ease;
            position: relative;
            background-color: #f8f9fa;
            margin: 0;
            /* remove extra spacing */
        }

        .support-swiper .swiper-slide:hover .video-thumb {
            transform: scale(1.02);
        }

        .play-overlay {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5) url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23007fff'><path d='M8 5v14l11-7z'/></svg>") center/38% no-repeat;
            border: none;
            z-index: 10;
            pointer-events: none;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);
            transition: all 0.25s ease;
        }

        .support-swiper .swiper-slide:hover .play-overlay {
            transform: translate(-50%, -50%) scale(1.15);
            box-shadow: 0 12px 32px rgba(0, 127, 255, 0.3);
        }

        /* Modal styles */
        #video-modal {
            display: none;
        }

        #video-modal.show {
            display: flex;
        }

        /* Responsive Swiper Slides */
        @media (max-width: 768px) {
            .support-swiper .swiper-slide {
                /* use viewport width to make slides large but keep some gutter */
                width: 88vw;
                max-width: 420px;
                height: auto;
                /* let content determine height with aspect-ratio */
                border-radius: 12px;
                min-width: 260px;
                box-shadow: 0 6px 18px rgba(0, 0, 0, 0.10);
            }

            .video-thumb {
                /* use modern aspect-ratio so thumbnail scales nicely on mobile */
                aspect-ratio: 2 / 1;
                height: auto;
                border-radius: 12px 12px 0 0;
            }

            .swiper-footer {
                padding: 10px 14px;
                min-height: 58px;
            }

            .swiper-footer h2 {
                font-size: 0.95rem !important;
                line-height: 1.25 !important;
            }

            .play-overlay {
                width: 64px;
                height: 64px;
            }

            .swiper-container-spacing {
                padding-bottom: 52px;
            }
        }

        @media (max-width: 480px) {
            .support-swiper .swiper-slide {
                width: 94vw;
                max-width: 360px;
                height: auto;
                min-width: 240px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }

            .video-thumb {
                aspect-ratio: 2 / 1;
                height: auto;
            }

            .swiper-footer {
                padding: 8px 10px;
                min-height: 52px;
            }

            .swiper-footer h2 {
                font-size: 0.82rem !important;
                line-height: 1.15 !important;
            }

            .play-overlay {
                width: 52px;
                height: 52px;
            }

            .swiper-pagination {
                bottom: -26px !important;
            }

            .swiper-container-spacing {
                padding-bottom: 46px;
            }
        }

        /* Extra mobile polish to prevent slides from being cut off and improve touch */
        @media (max-width: 768px) {
            .support-swiper {
                padding-left: 12px;
                padding-right: 12px;
                -webkit-overflow-scrolling: touch;
            }

            .support-swiper .swiper-slide {
                margin: 0 8px;
                /* small gutter so rounded corners aren't clipped */
                box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            }

            /* Make title smaller on mobile so it doesn't push content too low */
            #dukungan-marimoi>div:first-child h2 {
                font-size: 1.8rem !important;
                line-height: 1.12 !important;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .support-swiper {
                padding-left: 14px;
                padding-right: 14px;
            }

            .support-swiper .swiper-slide {
                margin: 0 6px;
                border-radius: 10px;
            }

            .support-swiper .swiper-slide:not(.swiper-slide-active) {
                opacity: 0.9;
                /* slightly more visible on tiny screens */
            }

            /* ensure thumbnail respects rounded corners */
            .video-thumb {
                border-radius: 10px 10px 0 0;
            }
        }

        /* Perbaikan container swiper untuk spacing yang lebih baik */
        .swiper-container-spacing {
            padding-bottom: 60px;
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
@endpush

<!-- Dukungan Section -->
<section
    class="bg-gradient-to-b from-white to-slate-300 text-black w-full relative py-10 md:py-20 sm:py-[25px] md:min-h-[50vh] overflow-hidden"
    id="dukungan-marimoi">
    <!-- Header Content -->
    <div
        class="max-w-[1400px] mx-auto px-5 md:px-4 sm:px-[15px] xs:px-[10px] flex flex-col items-center mb-[40px] md:mb-10 sm:mb-8">
        <h2
            class="text-[2.5rem] lg:text-[2.2rem] md:text-[2rem] sm:text-[1.8rem] font-bold text-black m-0 font-[Inter,sans-serif]">
            Dukungan Terhadap MARIMOI
        </h2>
    </div>

    <!-- Swiper Container -->
    <div
        class="swiper support-swiper swiper-container-spacing w-full max-w-[1400px] mx-auto py-[40px] md:py-8 sm:py-[20px] px-5 md:px-4 sm:px-[15px] overflow-visible">
        <div class="swiper-wrapper items-center">
            @php
                $videoCards = [
                    [
                        'id' => 'cWA8hBj4PcE',
                        'title' => 'Gubernur Provinsi Maluku Utara',
                        'url' => 'https://youtu.be/cWA8hBj4PcE',
                    ],
                    [
                        'id' => 'fcbyr-_O8VM',
                        'title' => 'Wakil Gubernur Provinsi Maluku Utara',
                        'url' => 'https://youtu.be/fcbyr-_O8VM',
                    ],
                    [
                        'id' => 'SlncXrLMJrM',
                        'title' => 'Sekretaris Daerah Provinsi Maluku Utara',
                        'url' => 'https://youtu.be/SlncXrLMJrM',
                    ],
                    [
                        'id' => 'uJyzLgpJa8U',
                        'title' => 'Direktur Regional III Indonesia Timur BAPPENAS',
                        'url' => 'https://youtu.be/uJyzLgpJa8U',
                    ],
                    [
                        'id' => '2wWShCIhAzs',
                        'title' => 'Kepala DISKOMINFO dan Persandian Prov MALUT',
                        'url' => 'https://youtu.be/2wWShCIhAzs',
                    ],
                    [
                        'id' => '6fkOgICo_Xs',
                        'title' => 'PLT. KADIKBUD Provinsi Maluku Utara',
                        'url' => 'https://youtu.be/6fkOgICo_Xs',
                    ],
                    [
                        'id' => 'tvWS8IOxy0w',
                        'title' => 'Kepala DISPERKIM Provinsi Maluku Utara',
                        'url' => 'https://youtu.be/tvWS8IOxy0w',
                    ],
                    [
                        'id' => 'CDelrE8NNwc',
                        'title' => 'Kepala Dinas PANGAN Provinsi Maluku Utara',
                        'url' => 'https://youtu.be/CDelrE8NNwc',
                    ],
                    [
                        'id' => 'qKU3BAL2CBA',
                        'title' => 'BAPPELITBANGDA Kota Ternate',
                        'url' => 'https://youtu.be/qKU3BAL2CBA',
                    ],
                    [
                        'id' => 'wJAVmcA_CDc',
                        'title' => 'BAPPERIDA Kota Tidore Kepulauan',
                        'url' => 'https://youtu.be/wJAVmcA_CDc',
                    ],
                    [
                        'id' => 'oaV902ATMn8',
                        'title' => 'BP3D Kabupaten Halmahera Barat',
                        'url' => 'https://youtu.be/oaV902ATMn8',
                    ],
                    [
                        'id' => 'Fxv5cDKptIQ',
                        'title' => 'BAPPELITBANGDA Kabupaten Halmahera Selatan',
                        'url' => 'https://youtu.be/Fxv5cDKptIQ',
                    ],
                    [
                        'id' => 'l7w_Y1WGWUY',
                        'title' => 'BP4D Kabupaten Halmahera Timur',
                        'url' => 'https://youtu.be/l7w_Y1WGWUY',
                    ],
                    [
                        'id' => '-kda5JnpjLg',
                        'title' => 'Sekretaris BAPPEDA Prov Maluku Utara',
                        'url' => 'https://youtu.be/-kda5JnpjLg',
                    ],
                    [
                        'id' => 'COkXbg26hIw',
                        'title' => 'Kabid PERAN BAPPEDA Prov Maluku Utara',
                        'url' => 'https://youtu.be/COkXbg26hIw',
                    ],
                    [
                        'id' => 'uVCVPWt6Pfk',
                        'title' => 'Kabid SOSBUD BAPPEDA Prov Maluku Utara',
                        'url' => 'https://youtu.be/uVCVPWt6Pfk',
                    ],
                    [
                        'id' => '-oaJ37KpdSE',
                        'title' => 'DUKUNGAN TERHADAP SISTEM MARIMOI',
                        'url' => 'https://youtu.be/-oaJ37KpdSE',
                    ],
                ];
            @endphp

            @foreach ($videoCards as $item)
                @php
                    $videoId = $item['id'];
                    $thumb = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
                    $title = $item['title'];
                @endphp

                <div class="swiper-slide cursor-pointer" data-video-id="{{ $videoId }}" role="button"
                    aria-label="Buka video {{ $title }}">
                    <div class="video-thumb" style="background-image:url('{{ $thumb }}')"></div>
                    <div class="swiper-footer">
                        <h2>{{ $title }}</h2>
                    </div>
                    <button type="button" class="play-overlay" aria-hidden="true"></button>
                </div>
            @endforeach
        </div>
        <!-- Add Pagination -->
        <div class="swiper-pagination dukungan-pagination"></div>
    </div>
</section>

<!-- Video modal -->
<div id="video-modal" class="fixed inset-0 z-[99] hidden items-center justify-center bg-black/70" role="dialog"
    aria-modal="true" aria-hidden="true">
    <div class="max-w-5xl w-[92%] md:w-[90%] aspect-[2/1] bg-black relative rounded-lg overflow-hidden shadow-2xl">
        <button id="video-modal-close"
            class="absolute top-3 right-3 z-20 bg-white/60 hover:bg-white/95 rounded-full w-10 h-10 flex items-center justify-center text-black text-xl font-bold transition-all cursor-pointer hover:scale-110">✕</button>
        <!-- iframe intentionally left without src until a video is opened -->
        <iframe id="video-iframe" class="w-full h-full border-0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen"
            allowfullscreen src=""></iframe>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Swiper
            const supportSwiper = new Swiper('.support-swiper', {
                effect: 'coverflow',
                grabCursor: true,
                centeredSlides: true,
                slidesPerView: 'auto',
                loop: true,
                coverflowEffect: {
                    rotate: 0,
                    stretch: 0,
                    depth: 100,
                    modifier: 2,
                    slideShadows: false,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false,
                },
                breakpoints: {
                    320: {
                        coverflowEffect: {
                            depth: 80,
                            modifier: 1.5,
                        }
                    },
                    768: {
                        coverflowEffect: {
                            depth: 100,
                            modifier: 2,
                        }
                    }
                }
            });

            // Modal functionality
            const modal = document.getElementById('video-modal');
            const iframe = document.getElementById('video-iframe');
            const closeBtn = document.getElementById('video-modal-close');

            function openModal(videoId) {
                if (!videoId) return;
                iframe.src = 'https://www.youtube.com/embed/' + videoId +
                    '?rel=0&autoplay=1&enablejsapi=1&modestbranding=1';
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';

                // Pause swiper autoplay when modal opens
                if (supportSwiper && supportSwiper.autoplay) {
                    supportSwiper.autoplay.stop();
                }
            }

            function closeModal() {
                modal.classList.remove('show');
                iframe.src = '';
                document.body.style.overflow = '';

                // Resume swiper autoplay when modal closes
                if (supportSwiper && supportSwiper.autoplay) {
                    supportSwiper.autoplay.start();
                }
            }

            // Open modal when clicking slide
            document.querySelectorAll('.swiper-slide[data-video-id]').forEach(function(slide) {
                slide.addEventListener('click', function(e) {
                    const vid = slide.getAttribute('data-video-id');
                    if (vid) openModal(vid);
                });
            });

            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeModal();
            });
        });
    </script>
@endpush
