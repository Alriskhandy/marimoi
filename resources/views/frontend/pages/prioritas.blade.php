@extends('frontend.layouts.dark', ['title' => 'Prioritas Daerah 2025-2029 - MARIMOI'])

@push('styles')
    @vite(['resources/css/app.css'])

    <style>
        /* Custom styles yang tidak bisa di-handle Tailwind */
        body {
            background: linear-gradient(to bottom, #ddf1ff, #f2faff);
            margin: 0;
            padding: 0;
            overflow: hidden;
            /* Prevent body scroll on desktop */
        }

        /* Container wrapper untuk zoom */
        .zoom-container {
            width: 100%;
            height: 100%;
            overflow: auto;
            position: relative;
        }

        /* Content yang akan di-zoom */
        .zoomable-content {
            transform-origin: 0 0;
            transition: transform 0.2s ease;
            display: flex;
            flex-direction: row;
            width: max-content;
        }

        /* Override untuk full height di desktop */
        @media (min-width: 768px) {
            .horizontal-scroll-section {
                height: 100vh !important;
                margin-top: 0 !important;
                padding-top: 65px;
                /* Space for navbar */
                overflow: hidden;
                /* Container tidak scroll, zoom-container yang scroll */
            }

            .zoomable-content {
                height: calc(100vh - 65px);
            }

            .horizontal-scroll-section img {
                height: calc(100vh - 65px) !important;
            }
        }

        /* Mobile tetap menggunakan calculated height */
        @media (max-width: 767px) {
            body {
                overflow: auto;
                /* Allow scroll on mobile */
            }

            .zoomable-content {
                flex-direction: column;
                width: 100%;
                height: auto;
            }

            .horizontal-scroll-section img {
                width: 100%;
                height: auto;
            }
        }

        /* Custom scrollbar untuk zoom container */
        .zoom-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .zoom-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .zoom-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .zoom-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Zoom indicator */
        .zoom-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: black;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .zoom-indicator.show {
            opacity: 1;
        }

        /* Pan cursor */
        .panning {
            cursor: grabbing !important;
        }

        .pannable {
            cursor: grab !important;
        }

        /* Zoom controls */
        .zoom-controls {
            position: fixed;
            bottom: 20px;
            left: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 1000;
        }

        .zoom-btn {
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.95);
            color: black;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: background 0.2s ease;
        }

        .zoom-btn:hover {
            background: rgba(201, 201, 201, 0.9);
        }

        .zoom-btn:disabled {
            opacity: 0.8;
            cursor: not-allowed;
        }
    </style>
@endpush

@section('main')
    <!-- Zoom Indicator -->
    <div id="zoomIndicator" class="zoom-indicator">
        Zoom: <span id="zoomLevel">100%</span>
    </div>

    <!-- Zoom Controls -->
    <div class="zoom-controls">
        <button id="zoomIn" class="zoom-btn" title="Zoom In">+</button>
        <button id="zoomOut" class="zoom-btn" title="Zoom Out">−</button>
        <button id="zoomReset" class="zoom-btn" title="Reset Zoom">⌂</button>
    </div>

    <!-- Document Section -->
    <section
        class="horizontal-scroll-section 
                    w-full 
                    shadow-md
                    /* Desktop: min-width 768px */
                    md:h-screen
                    md:pt-[65px]
                    md:p-0
                    /* Mobile: max-width 767px */
                    h-[calc(100vh-60px)]
                    mt-[60px]
                    p-0
                    mx-auto
                    max-w-[767px]:mx-auto">

        <!-- Zoom Container -->
        <div class="zoom-container" id="zoomContainer">
            <!-- Zoomable Content -->
            <div class="zoomable-content" id="zoomableContent">
                <img src="{{ asset('frontend/img/prioritas/prioritas-1.jpg') }}" alt="Prioritas Daerah 2025-2029 Halaman 1"
                    class="priority-image
                            /* Desktop */
                            md:h-[calc(100vh-70px)] 
                            md:w-auto 
                            md:object-contain
                            md:flex-shrink-0
                            /* Mobile */
                            w-full
                            h-auto"
                    data-index="0">

                <img src="{{ asset('frontend/img/prioritas/prioritas-2.jpg') }}" alt="Prioritas Daerah 2025-2029 Halaman 2"
                    class="priority-image
                            /* Desktop */
                            md:h-[calc(100vh-70px)] 
                            md:w-auto 
                            md:object-contain
                            md:flex-shrink-0
                            /* Mobile */
                            w-full
                            h-auto"
                    data-index="1">

                <img src="{{ asset('frontend/img/prioritas/prioritas-3.jpg') }}" alt="Prioritas Daerah 2025-2029 Halaman 3"
                    class="priority-image
                            /* Desktop */
                            md:h-[calc(100vh-70px)] 
                            md:w-auto 
                            md:object-contain
                            md:flex-shrink-0
                            /* Mobile */
                            w-full
                            h-auto"
                    data-index="2">

                <img src="{{ asset('frontend/img/prioritas/prioritas-4.jpg') }}" alt="Prioritas Daerah 2025-2029 Halaman 4"
                    class="priority-image
                            /* Desktop */
                            md:h-[calc(100vh-70px)] 
                            md:w-auto 
                            md:object-contain
                            md:flex-shrink-0
                            /* Mobile */
                            w-full
                            h-auto"
                    data-index="3">
            </div>
        </div>
    </section><!-- /Document Section -->
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scrollContainer = document.querySelector('.horizontal-scroll-section');
            const zoomContainer = document.getElementById('zoomContainer');
            const zoomableContent = document.getElementById('zoomableContent');
            const images = document.querySelectorAll('.priority-image');
            const zoomIndicator = document.getElementById('zoomIndicator');
            const zoomLevel = document.getElementById('zoomLevel');
            const zoomInBtn = document.getElementById('zoomIn');
            const zoomOutBtn = document.getElementById('zoomOut');
            const zoomResetBtn = document.getElementById('zoomReset');

            // State management
            let currentZoom = 1;
            let isDesktop = window.innerWidth >= 768;
            let minZoom = 1;
            let maxZoom = 3;

            // Pan state
            let isPanning = false;
            let startX = 0;
            let startY = 0;
            let initialScrollLeft = 0;
            let initialScrollTop = 0;

            // Touch state
            let lastTouchX = 0;
            let lastTouchY = 0;
            let lastPinchDistance = null;
            let lastTapTime = 0;

            // Initialize
            function init() {
                isDesktop = window.innerWidth >= 768;
                setContainerHeight();
                setupEventListeners();
                updateZoomIndicator();
                updateZoomButtons();
            }

            function setContainerHeight() {
                if (isDesktop) {
                    scrollContainer.style.height = '100vh';
                    scrollContainer.style.marginTop = '0';
                    scrollContainer.style.paddingTop = '70px';
                } else {
                    scrollContainer.style.height = 'calc(100vh - 70px)';
                    scrollContainer.style.marginTop = '70px';
                    scrollContainer.style.paddingTop = '0';
                }
            }

            function setupEventListeners() {
                // Wheel event for zoom
                zoomContainer.addEventListener('wheel', handleWheel, {
                    passive: false
                });

                // Mouse events for pan
                zoomContainer.addEventListener('mousedown', handleMouseDown);
                zoomContainer.addEventListener('mousemove', handleMouseMove);
                zoomContainer.addEventListener('mouseup', handleMouseUp);
                zoomContainer.addEventListener('mouseleave', handleMouseUp);

                // Touch events
                zoomContainer.addEventListener('touchstart', handleTouchStart, {
                    passive: false
                });
                zoomContainer.addEventListener('touchmove', handleTouchMove, {
                    passive: false
                });
                zoomContainer.addEventListener('touchend', handleTouchEnd);

                // Zoom button events
                zoomInBtn.addEventListener('click', () => zoom(1.2));
                zoomOutBtn.addEventListener('click', () => zoom(0.8));
                zoomResetBtn.addEventListener('click', resetZoom);

                // Keyboard shortcuts
                document.addEventListener('keydown', handleKeyDown);

                // Window resize
                window.addEventListener('resize', handleResize);

                // Double click/tap to reset zoom
                zoomContainer.addEventListener('dblclick', resetZoom);
            }

            function handleWheel(e) {
                e.preventDefault();

                if (e.ctrlKey || e.metaKey) {
                    // Zoom functionality with Ctrl+Wheel
                    const zoomFactor = e.deltaY > 0 ? 0.9 : 1.1;
                    zoom(zoomFactor, e.clientX, e.clientY);
                } else {
                    // Pan when zoomed or scroll when not zoomed
                    if (currentZoom > 1) {
                        // Pan in both directions when zoomed
                        if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
                            zoomContainer.scrollLeft += e.deltaX;
                        } else {
                            zoomContainer.scrollTop += e.deltaY;
                        }
                    } else {
                        // Normal scroll behavior when not zoomed
                        if (isDesktop) {
                            zoomContainer.scrollLeft += e.deltaY;
                        } else {
                            zoomContainer.scrollTop += e.deltaY;
                        }
                    }
                }
            }

            function handleMouseDown(e) {
                isPanning = true;
                startX = e.clientX;
                startY = e.clientY;
                initialScrollLeft = zoomContainer.scrollLeft;
                initialScrollTop = zoomContainer.scrollTop;

                zoomContainer.classList.add('panning');
                e.preventDefault();
            }

            function handleMouseMove(e) {
                if (!isPanning) return;

                const deltaX = e.clientX - startX;
                const deltaY = e.clientY - startY;

                zoomContainer.scrollLeft = initialScrollLeft - deltaX;
                zoomContainer.scrollTop = initialScrollTop - deltaY;
            }

            function handleMouseUp() {
                isPanning = false;
                zoomContainer.classList.remove('panning');
                updateCursor();
            }

            function handleTouchStart(e) {
                if (e.touches.length === 1) {
                    const touch = e.touches[0];
                    lastTouchX = touch.clientX;
                    lastTouchY = touch.clientY;
                    startX = touch.clientX;
                    startY = touch.clientY;
                    initialScrollLeft = zoomContainer.scrollLeft;
                    initialScrollTop = zoomContainer.scrollTop;
                } else if (e.touches.length === 2) {
                    // Initialize pinch
                    const touch1 = e.touches[0];
                    const touch2 = e.touches[1];
                    lastPinchDistance = Math.sqrt(
                        Math.pow(touch2.clientX - touch1.clientX, 2) +
                        Math.pow(touch2.clientY - touch1.clientY, 2)
                    );
                }
            }

            function handleTouchMove(e) {
                e.preventDefault();

                if (e.touches.length === 1) {
                    // Single touch - pan
                    const touch = e.touches[0];
                    const deltaX = touch.clientX - startX;
                    const deltaY = touch.clientY - startY;

                    zoomContainer.scrollLeft = initialScrollLeft - deltaX;
                    zoomContainer.scrollTop = initialScrollTop - deltaY;

                } else if (e.touches.length === 2) {
                    // Pinch to zoom
                    const touch1 = e.touches[0];
                    const touch2 = e.touches[1];
                    const distance = Math.sqrt(
                        Math.pow(touch2.clientX - touch1.clientX, 2) +
                        Math.pow(touch2.clientY - touch1.clientY, 2)
                    );

                    if (lastPinchDistance) {
                        const zoomFactor = distance / lastPinchDistance;
                        const centerX = (touch1.clientX + touch2.clientX) / 2;
                        const centerY = (touch1.clientY + touch2.clientY) / 2;
                        zoom(zoomFactor, centerX, centerY);
                    }
                    lastPinchDistance = distance;
                }
            }

            function handleTouchEnd(e) {
                if (e.touches.length === 0) {
                    lastPinchDistance = null;

                    // Double tap to toggle zoom
                    const now = Date.now();
                    if (lastTapTime && now - lastTapTime < 300) {
                        if (currentZoom === 1) {
                            zoom(2, lastTouchX, lastTouchY);
                        } else {
                            resetZoom();
                        }
                    }
                    lastTapTime = now;
                }
            }

            function handleKeyDown(e) {
                switch (e.key) {
                    case '0':
                        if (e.ctrlKey || e.metaKey) {
                            resetZoom();
                            e.preventDefault();
                        }
                        break;
                    case '=':
                    case '+':
                        if (e.ctrlKey || e.metaKey) {
                            zoom(1.2);
                            e.preventDefault();
                        }
                        break;
                    case '-':
                        if (e.ctrlKey || e.metaKey) {
                            zoom(0.8);
                            e.preventDefault();
                        }
                        break;
                }
            }

            function zoom(factor, centerX = null, centerY = null) {
                const newZoom = Math.min(Math.max(currentZoom * factor, minZoom), maxZoom);

                if (newZoom !== currentZoom) {
                    // Calculate zoom center
                    const rect = zoomContainer.getBoundingClientRect();
                    const x = centerX !== null ? centerX - rect.left : rect.width / 2;
                    const y = centerY !== null ? centerY - rect.top : rect.height / 2;

                    // Adjust scroll position to maintain zoom center
                    const zoomRatio = newZoom / currentZoom;
                    const newScrollLeft = (zoomContainer.scrollLeft + x) * zoomRatio - x;
                    const newScrollTop = (zoomContainer.scrollTop + y) * zoomRatio - y;

                    // Apply zoom to content
                    currentZoom = newZoom;
                    zoomableContent.style.transform = `scale(${currentZoom})`;

                    // Set new scroll position
                    zoomContainer.scrollLeft = newScrollLeft;
                    zoomContainer.scrollTop = newScrollTop;

                    updateZoomIndicator();
                    updateZoomButtons();
                    updateCursor();
                }
            }

            function resetZoom() {
                currentZoom = 1;
                zoomableContent.style.transform = 'scale(1)';
                zoomContainer.scrollLeft = 0;
                zoomContainer.scrollTop = 0;
                updateZoomIndicator();
                updateZoomButtons();
                updateCursor();
            }

            function updateZoomIndicator() {
                const percentage = Math.round(currentZoom * 100);
                zoomLevel.textContent = `${percentage}%`;

                if (currentZoom !== 1) {
                    zoomIndicator.classList.add('show');
                    setTimeout(() => {
                        if (currentZoom === 1) {
                            zoomIndicator.classList.remove('show');
                        }
                    }, 2000);
                } else {
                    zoomIndicator.classList.remove('show');
                }
            }

            function updateZoomButtons() {
                zoomInBtn.disabled = currentZoom >= maxZoom;
                zoomOutBtn.disabled = currentZoom <= minZoom;
            }

            function updateCursor() {
                if (currentZoom > 1) {
                    zoomContainer.classList.add('pannable');
                } else {
                    zoomContainer.classList.remove('pannable');
                }
            }

            function handleResize() {
                const wasDesktop = isDesktop;
                isDesktop = window.innerWidth >= 768;

                if (wasDesktop !== isDesktop) {
                    setContainerHeight();
                    resetZoom();
                }
            }

            // Initialize on load
            init();
        });
    </script>
@endpush
