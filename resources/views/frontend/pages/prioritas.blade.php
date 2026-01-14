@extends('frontend.layouts.dark', ['title' => 'Prioritas Daerah 2025-2029 - MARIMOI'])

@push('styles')
    @vite(['resources/css/app.css'])

    <style>
        /* Custom styles yang tidak bisa di-handle Tailwind */
        body {
            margin: 0;
            padding: 0;
            overflow: auto;
            /* allow normal scrolling but prevent horizontal overflow via layout */
        }

         /* Reformer section white gradient overlay */
        .section {
            position: relative;
            overflow: hidden;
        }

        .section::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background: linear-gradient(to bottom,
                    rgba(241, 245, 249, 0.90) 0%,
                    rgba(241, 245, 249, 1) 30%,
                    rgba(241, 245, 249, 1) 70%,
                    rgba(241, 245, 249, 0.90) 100%);
        }

        /* Ensure content appears above the overlay */
        .section .z-above-overlay {
            position: relative;
            z-index: 10;
        }

        /* Carousel styles */
        .carousel {
            position: relative;
            width: 100%;
            max-width: 1200px;
            aspect-ratio: 16 / 9;
            margin: 0 auto;
            overflow: hidden;
            background: #000;
        }

        .carousel-track {
            display: flex;
            transition: transform 300ms ease;
            will-change: transform;
        }

        .carousel-slide {
            min-width: 100%;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
        }

        .carousel-slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .carousel-controls {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .carousel-btn {
            pointer-events: auto;
            background: rgba(0, 0, 0, 0.45);
            color: #fff;
            border: none;
            padding: .6rem .9rem;
            border-radius: 9999px;
            font-size: 1.6rem;
            margin: 0 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 150ms ease;
        }

        .carousel-btn:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        .carousel-indicators {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: .5rem;
        }

        .carousel-indicators button {
            width: 10px;
            height: 10px;
            border-radius: 9999px;
            border: none;
            background: rgba(255, 255, 255, 0.6);
            transition: background 150ms ease, transform 150ms ease;
        }

        .carousel-indicators button.active {
            background: #fff;
            transform: scale(1.1);
        }


        /* Mobile sizes */
        @media (max-width: 767px) {
            .carousel {
                width: 100%;
                max-width: none;
            }

            .carousel-slide {
                min-width: 100%;
            }
        }

        /* Mobile tweaks */
        @media (max-width: 767px) {
            body {
                overflow: auto;
            }

            .carousel {
                aspect-ratio: 16 / 9;
            }
        }
    </style>
@endpush

@section('main')
    <!-- Document Section -->
    <section class="section min-h-auto mt-[76px] pt-6 pb-8 bg-slate-100" style="background: url('{{ asset('frontend/img/cv/bg.svg') }}') repeat;">

        <!-- Carousel Container -->
        <div class="carousel mt-5 rounded-lg shadow-lg" id="prioritasCarousel">
            <div class="carousel-track" id="carouselTrack">
                <div class="carousel-slide">
                    <img src="{{ asset('frontend/img/prioritas/visi-1.png') }}" alt="Prioritas Daerah 2025-2029 Halaman 1">
                </div>
                <div class="carousel-slide">
                    <img src="{{ asset('frontend/img/prioritas/visi-2.png') }}" alt="Prioritas Daerah 2025-2029 Halaman 2">
                </div>
            </div>

            <div class="carousel-controls">
                <div style="display:flex; align-items:center;">
                    <button id="prevBtn" class="carousel-btn" aria-label="Previous">‹</button>
                </div>
                <div style="display:flex; align-items:center;">
                    <button id="nextBtn" class="carousel-btn" aria-label="Next">›</button>
                </div>
            </div>

            <div class="carousel-indicators" id="carouselIndicators">
                <button data-slide-to="0" class="active" aria-label="Slide 1"></button>
                <button data-slide-to="1" aria-label="Slide 2"></button>
            </div>
        </div>

        <!-- Fullscreen modal for images -->
        <div id="imageModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/90">
            <button id="modalClose" aria-label="Close" class="absolute top-20 right-6 text-white text-4xl">&times;</button>
            <button id="modalPrev" aria-label="Previous"
                class="absolute left-4 text-white text-4xl p-3 rounded-full bg-black/50">‹</button>
            <button id="modalNext" aria-label="Next"
                class="absolute right-4 text-white text-4xl p-3 rounded-full bg-black/50">›</button>
            <div id="modalContent" class="w-full h-full flex items-center justify-center">
                <!-- modal image injected here -->
            </div>
        </div>
    </section><!-- /Document Section -->

    <!-- Footer Section -->
    @include('frontend.partials.footer-dark-tailwind')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const track = document.getElementById('carouselTrack');
            const slides = Array.from(track.querySelectorAll('.carousel-slide'));
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const indicators = Array.from(document.querySelectorAll('#carouselIndicators button'));

            let current = 0;
            let isDragging = false;
            let startX = 0;
            let currentTranslate = 0;
            let prevTranslate = 0;
            let animationID = 0;
            let carouselWidth = track.clientWidth;

            function setPosition() {
                carouselWidth = track.clientWidth;
                const x = -current * carouselWidth;
                track.style.transform = `translateX(${x}px)`;
                indicators.forEach((btn, idx) => btn.classList.toggle('active', idx === current));
                prevTranslate = -current * carouselWidth;
            }

            function prev() {
                current = (current - 1 + slides.length) % slides.length;
                setPosition();
            }

            function next() {
                current = (current + 1) % slides.length;
                setPosition();
            }

            prevBtn.addEventListener('click', prev);
            nextBtn.addEventListener('click', next);

            // Indicators
            indicators.forEach(btn => {
                btn.addEventListener('click', () => {
                    const i = parseInt(btn.dataset.slideTo, 10);
                    if (!Number.isNaN(i)) {
                        current = i;
                        setPosition();
                    }
                });
            });

            // Keyboard
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') prev();
                if (e.key === 'ArrowRight') next();
            });

            // Touch / drag support
            slides.forEach((slide, index) => {
                // Touch events
                slide.addEventListener('touchstart', touchStart(index), {
                    passive: true
                });
                slide.addEventListener('touchend', touchEnd);
                slide.addEventListener('touchmove', touchMove, {
                    passive: false
                });

                // Mouse events for desktop dragging
                slide.addEventListener('mousedown', touchStart(index));
                slide.addEventListener('mouseup', touchEnd);
                slide.addEventListener('mouseleave', touchEnd);
                slide.addEventListener('mousemove', touchMove);
            });

            function touchStart(index) {
                return function(event) {
                    isDragging = true;
                    current = index;
                    startX = getPositionX(event);
                    animationID = requestAnimationFrame(animation);
                    track.classList.add('grabbing');
                }
            }

            function touchMove(event) {
                if (!isDragging) return;
                const currentPosition = getPositionX(event);
                currentTranslate = prevTranslate + currentPosition - startX;
            }

            function touchEnd() {
                cancelAnimationFrame(animationID);
                isDragging = false;
                const movedBy = currentTranslate - prevTranslate;

                const threshold = Math.max(50, carouselWidth * 0.12);

                if (movedBy < -threshold) next();
                else if (movedBy > threshold) prev();
                else setPosition();

                prevTranslate = -current * carouselWidth;
                track.classList.remove('grabbing');
            }

            function getPositionX(event) {
                return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
            }

            function animation() {
                setTranslate(currentTranslate);
                if (isDragging) requestAnimationFrame(animation);
            }

            function setTranslate(xPos) {
                track.style.transform = `translateX(${xPos}px)`;
            }

            // Responsive: keep position on resize
            window.addEventListener('resize', () => {
                carouselWidth = track.clientWidth;
                setPosition();
            });

            // Initial render
            setPosition();
            // Modal functionality
            const modal = document.getElementById('imageModal');
            const modalContent = document.getElementById('modalContent');
            const modalClose = document.getElementById('modalClose');
            const modalPrev = document.getElementById('modalPrev');
            const modalNext = document.getElementById('modalNext');

            function openModal(index) {
                const img = slides[index].querySelector('img');
                if (!img) return;
                modalContent.innerHTML = '';
                const mimg = document.createElement('img');
                mimg.src = img.src;
                mimg.alt = img.alt || '';
                mimg.style.maxWidth = '95%';
                mimg.style.maxHeight = '95%';
                mimg.style.objectFit = 'contain';
                modalContent.appendChild(mimg);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.dataset.current = index;
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modalContent.innerHTML = '';
                document.body.style.overflow = '';
            }

            function modalPrevFn() {
                let idx = parseInt(modal.dataset.current || '0', 10);
                idx = (idx - 1 + slides.length) % slides.length;
                openModal(idx);
            }

            function modalNextFn() {
                let idx = parseInt(modal.dataset.current || '0', 10);
                idx = (idx + 1) % slides.length;
                openModal(idx);
            }

            // Open modal on image click
            slides.forEach((s, i) => {
                const img = s.querySelector('img');
                if (img) img.style.cursor = 'zoom-in';
                s.addEventListener('click', () => openModal(i));
            });

            modalClose.addEventListener('click', closeModal);
            modalPrev.addEventListener('click', modalPrevFn);
            modalNext.addEventListener('click', modalNextFn);

            // Keyboard for modal
            document.addEventListener('keydown', (e) => {
                if (!modal.classList.contains('hidden')) {
                    if (e.key === 'Escape') closeModal();
                    if (e.key === 'ArrowLeft') modalPrevFn();
                    if (e.key === 'ArrowRight') modalNextFn();
                }
            });

            // Close modal by clicking backdrop
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        });
    </script>
@endpush
