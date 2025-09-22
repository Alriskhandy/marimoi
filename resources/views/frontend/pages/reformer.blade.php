@extends('frontend.layouts.dark', ['title' => 'Profil REFORMER - MARIMOI'])

@push('styles')
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <!-- Tailwind CSS via Vite -->
    @vite(['resources/css/app.css'])
    <style>
        /* Typography Fonts */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
        }

        p,
        body,
        ul,
        li {
            font-family: 'Inter', sans-serif;
        }

        /* Custom animations and transitions */
        .answer {
            /* remove heavy max-height transition */
            max-height: none;
            overflow: hidden;
            padding-top: 0;
        }

        /* inner wrapper that will be GPU-animated */
        .answer>.collapsible {
            transform-origin: top;
            transform: scaleY(0);
            opacity: 0;
            transition: transform 280ms cubic-bezier(.2, .8, .2, 1), opacity 200ms ease;
            will-change: transform, opacity;
            /* keep layout stable */
            display: block;
        }

        /* when radio checked, expand via scale */
        .tab input[type="radio"]:checked~.answer>.collapsible {
            transform: scaleY(1);
            opacity: 1;
            padding-top: 1rem;
            /* if you need the extra spacing */
        }

        /* remove heavy per-faq max-height rules */
        #faq1:checked~.answer,
        #faq2:checked~.answer,
        #faq3:checked~.answer,
        #faq4:checked~.answer,
        #faq5:checked~.answer,
        #faq6:checked~.answer,
        #faq7:checked~.answer,
        #faq8:checked~.answer {
            /* no-op kept for selector compatibility */
        }

        /* optionally limit expensive shadows on many elements */
        .tab {
            transition: transform 180ms ease;
        }

        .tab:hover {
            transform: translateY(-1px);
            /* lighter shadow to reduce overdraw */
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        /* Style for checked/active accordion labels */
        .tab input[type="radio"]:checked~label {
            background: linear-gradient(to bottom right, #2563eb, #1e40af) !important;
            color: white !important;
        }

        .tab input[type="radio"]:checked~label h3 {
            color: white !important;
        }

        .tab input[type="radio"]:checked~label::after {
            color: white !important;
        }

        .tab input[type="radio"]:checked~label i {
            color: white !important;
        }

        /* Default label styling */
        .tab label {
            color: #374151 !important;
            background-color: transparent;
        }

        .tab label h3 {
            color: #374151 !important;
            margin: 0;
            font-weight: 600;
        }

        .tab label i {
            color: #374151 !important;
        }

        /* Smooth hover effects */
        .tab:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .container {
            max-width: 1200px;
        }

        /* Tab icon styling: consistent size, spacing and states */
        .tab label h3 {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .tab label i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 8px;
            background: transparent;
            color: #2563eb;
            /* primary blue */
            font-size: 18px;
            margin-right: 0.5rem;
            /* space between icon and text */
            transition: background 180ms ease, color 180ms ease, transform 180ms ease;
        }

        /* Hover state for icons */
        .tab label:hover i {
            background: rgba(37, 99, 235, 0.08);
            color: #1e40af;
            transform: translateY(-2px);
        }

        /* Active (checked) state — keep icons visible on colored background */
        .tab input[type="radio"]:checked~label i {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
            transform: none;
        }

        /* Reformer section white gradient overlay */
        .reformer-section {
            position: relative;
            overflow: hidden;
        }

        /* Gradient: solid white at top until ~55%, then fade to transparent toward bottom */
        .reformer-section::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background: linear-gradient(to bottom,
                    rgba(241, 245, 249, 0.90) 0%,
                    rgba(241, 245, 249, 1) 20%,
                    rgba(241, 245, 249, 1) 80%,
                    rgba(241, 245, 249, 0.90) 100%);
        }

        /* Ensure content appears above the overlay */
        .reformer-section .z-above-overlay {
            position: relative;
            z-index: 10;
        }
    </style>
@endpush

@section('main')
    <!-- CV Section -->
    <section class="reformer-section min-h-auto mt-[76px] pt-0 pb-8 bg-slate-100"
        style="background: url('{{ asset('frontend/img/cv/bg.svg') }}') repeat;">
        <!-- Section Title -->
        <div class="container mx-auto px-4 text-center mb-8 z-above-overlay">
            <h2 class="text-2xl md:text-3xl font-bold pt-8 text-slate-800 mb-4">
                Profil Reformer MARIMOI
            </h2>
        </div>


        <div class="container mx-auto px-4 z-above-overlay">
            <div class="wrapper w-full max-w-4xl mx-auto">
                <div
                    class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                    <img src="{{ asset('frontend/img/cv/1.jpg') }}" alt="CV 1">
                </div>

                <!-- Item 1: Data Pribadi & Riwayat Pendidikan -->
                <div
                    class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                    <input type="radio" name="faq" id="faq1" class="hidden peer">
                    <label for="faq1"
                        class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                        tabindex="0">
                        <h3><i class="bi bi-person-vcard me-2"></i> Data Pribadi, Keluarga & Riwayat Pendidikan</h3>
                    </label>
                    <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                        <div class="collapsible text-gray-700 text-sm md:text-md leading-relaxed">
                            <img src="{{ asset('frontend/img/cv/2.jpg') }}" alt="CV 2" class="border rounded-lg"
                                loading="lazy" decoding="async">
                        </div>
                    </div>
                </div>

                <!-- Item 2: Riwayat Kepangkatan -->
                <div
                    class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                    <input type="radio" name="faq" id="faq2" class="hidden peer">
                    <label for="faq2"
                        class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                        tabindex="0">
                        <h3><i class="bi bi-bar-chart-steps me-2"></i> Riwayat Kepangkatan</h3>
                    </label>
                    <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                        <div class="collapsible text-gray-700 text-sm md:text-md leading-relaxed">
                            <img src="{{ asset('frontend/img/cv/3.jpg') }}" alt="CV 3" class="border rounded-lg">
                        </div>
                    </div>
                </div>

                <!-- Item 3: Riwayat Jabatan -->
                <div
                    class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                    <input type="radio" name="faq" id="faq3" class="hidden peer">
                    <label for="faq3"
                        class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                        tabindex="0">
                        <h3><i class="bi bi-briefcase me-2"></i> Riwayat Jabatan</h3>
                    </label>
                    <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                        <div class="collapsible text-gray-700 text-sm md:text-md leading-relaxed">
                            <img src="{{ asset('frontend/img/cv/4.jpg') }}" alt="CV 4" class="border rounded-lg">
                        </div>
                    </div>
                </div>

                <!-- Item 4: Riwayat Kinerja, Diklat, & Penghargaan -->
                <div
                    class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                    <input type="radio" name="faq" id="faq4" class="hidden peer">
                    <label for="faq4"
                        class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                        tabindex="0">
                        <h3><i class="bi bi-award  me-2"></i> Riwayat Kinerja, Diklat, & Penghargaan</h3>
                    </label>
                    <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                        <div class="collapsible text-gray-700 text-sm md:text-md leading-relaxed">
                            <img src="{{ asset('frontend/img/cv/5.jpg') }}" alt="CV 5" class="border rounded-lg">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Fullscreen carousel modal -->
        <div id="imageModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/80">
            <button id="closeModal" aria-label="Close" style="z-index:9999"
                class="absolute top-20 right-8 text-white text-4xl leading-none">&times;</button>
            <button id="prevBtn" aria-label="Previous" style="z-index:9999"
                class="absolute left-4 text-white text-4xl leading-none p-2 rounded-lg bg-black/40 hover:bg-black/70">‹</button>
            <button id="nextBtn" aria-label="Next" style="z-index:9999"
                class="absolute right-8 text-white text-4xl leading-none p-2 rounded-lg bg-black/40 hover:bg-black/70">›</button>
            <div id="carousel" class="w-full h-full flex items-center justify-center relative overflow-hidden">
                <!-- slides will be injected here -->
            </div>
        </div>
    </section><!-- /CV Section -->

    <!-- Footer Section -->
    @include('frontend.partials.footer-dark-tailwind')
@endsection

@push('scripts')
    <!-- Vite JavaScript -->
    @vite(['resources/js/app.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add keyboard support for accordion
            const labels = document.querySelectorAll('.tab label');
            const radioButtons = document.querySelectorAll('input[name="faq"]');

            labels.forEach((label, index) => {
                // Handle keyboard events
                label.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleAccordion(index);
                    }
                });

                // Handle click events
                label.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleAccordion(index);
                });
            });

            // NEW: helper to open/close with measured max-height for reliable layout
            function setCollapseState(tabIndex, open) {
                const tab = labels[tabIndex].closest('.tab');
                if (!tab) return;
                const collapsible = tab.querySelector('.collapsible');
                if (!collapsible) return;

                // Ensure we have a transition for max-height + opacity
                collapsible.style.overflow = 'hidden';
                collapsible.style.transition = 'max-height 300ms cubic-bezier(.2,.8,.2,1), opacity 200ms ease';

                if (open) {
                    // measure then expand
                    collapsible.style.maxHeight = '0px';
                    // allow DOM to apply before measuring
                    requestAnimationFrame(() => {
                        const h = collapsible.scrollHeight;
                        collapsible.style.maxHeight = h + 'px';
                        collapsible.style.opacity = '1';
                    });
                } else {
                    // collapse
                    collapsible.style.maxHeight = '0px';
                    collapsible.style.opacity = '0';
                }
            }

            function toggleAccordion(index) {
                const radio = radioButtons[index];

                // If the clicked accordion is already open, close it
                if (radio.checked) {
                    radio.checked = false;
                    radio.dispatchEvent(new Event('change'));
                    setCollapseState(index, false);
                } else {
                    // Close all other accordions and open the clicked one
                    radioButtons.forEach((otherRadio, otherIndex) => {
                        if (otherIndex !== index) {
                            otherRadio.checked = false;
                            otherRadio.dispatchEvent(new Event('change'));
                            setCollapseState(otherIndex, false);
                        }
                    });

                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                    setCollapseState(index, true);

                    // Optionally lazy-load images inside the opened panel immediately
                    const tab = labels[index].closest('.tab');
                    if (tab) {
                        const imgs = tab.querySelectorAll('img[loading="lazy"]');
                        imgs.forEach(img => {
                            // trigger eager decode by removing loading attr so browser loads now
                            img.loading = 'eager';
                            // try decode to avoid flicker
                            if (img.decode) img.decode().catch(() => {});
                        });
                    }
                }
            }

            // Initialize collapsed states (ensure maxHeight 0 and opacity 0)
            radioButtons.forEach((rb, idx) => {
                const tab = labels[idx].closest('.tab');
                if (!tab) return;
                const collapsible = tab.querySelector('.collapsible');
                if (!collapsible) return;
                collapsible.style.maxHeight = rb.checked ? collapsible.scrollHeight + 'px' : '0px';
                collapsible.style.opacity = rb.checked ? '1' : '0';
                collapsible.style.overflow = 'hidden';
            });
        });
    </script>

    <script>
        (function() {
            // Build a list of images from the page (all img inside .wrapper)
            const wrapper = document.querySelector('.wrapper');
            if (!wrapper) return;

            const imgs = Array.from(wrapper.querySelectorAll('img')).map(img => ({
                src: img.getAttribute('src') || img.dataset.src,
                alt: img.getAttribute('alt') || ''
            })).filter(i => i.src);

            if (imgs.length === 0) return;

            const modal = document.getElementById('imageModal');
            const carousel = document.getElementById('carousel');
            const closeBtn = document.getElementById('closeModal');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            let current = 0;

            // Create slides
            imgs.forEach((img, idx) => {
                const slide = document.createElement('div');
                slide.className =
                    'slide absolute inset-0 flex items-center justify-center transition-opacity duration-300';
                slide.style.opacity = idx === 0 ? '1' : '0';
                slide.style.transform = 'translateX(0)';

                const el = document.createElement('img');
                el.src = img.src;
                el.alt = img.alt;
                el.className = 'max-w-full max-h-full object-contain';

                slide.appendChild(el);
                carousel.appendChild(slide);
            });

            const slides = Array.from(carousel.querySelectorAll('.slide'));

            function show(index) {
                if (index < 0) index = slides.length - 1;
                if (index >= slides.length) index = 0;
                slides.forEach((s, i) => {
                    s.style.opacity = i === index ? '1' : '0';
                    s.style.pointerEvents = i === index ? 'auto' : 'none';
                });
                current = index;
            }

            function open(index) {
                show(index);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }

            function close() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }

            // Wire up click on page images to open at the right index
            const pageImgs = Array.from(wrapper.querySelectorAll('img'));
            pageImgs.forEach((imgEl, idx) => {
                imgEl.style.cursor = 'zoom-in';
                imgEl.addEventListener('click', (e) => {
                    e.preventDefault();
                    open(idx);
                });
            });

            closeBtn.addEventListener('click', close);
            prevBtn.addEventListener('click', () => show(current - 1));
            nextBtn.addEventListener('click', () => show(current + 1));

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (modal.classList.contains('hidden')) return;
                if (e.key === 'Escape') close();
                if (e.key === 'ArrowLeft') show(current - 1);
                if (e.key === 'ArrowRight') show(current + 1);
            });

            // Click outside to close
            modal.addEventListener('click', (e) => {
                if (e.target === modal) close();
            });

            // Simple touch support: swipe left/right
            let touchStartX = 0;
            let touchEndX = 0;
            carousel.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            }, {
                passive: true
            });
            carousel.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                const dx = touchEndX - touchStartX;
                if (Math.abs(dx) > 40) {
                    if (dx > 0) show(current - 1);
                    else show(current + 1);
                }
            }, {
                passive: true
            });
        })();
    </script>
@endpush
