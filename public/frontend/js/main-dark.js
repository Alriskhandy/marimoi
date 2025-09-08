/**
 * ========================================
 * MARIMOI - Main JavaScript (Clean Version)
 * ========================================
 */

// Configuration and Constants
const MARIMOI_APP_CONFIG = {
    ANIMATION_DELAY: 100,
    CAROUSEL_INTERVAL: 4000,
    COUNTER_DURATION: 2000,
    RETRY_DELAY: 2000,
    CARD_WIDTH: 370,
};

// Utility Functions
const Utils = {
    scrollToSection: function (sectionId) {
        const targetElement = document.getElementById(sectionId);
        if (targetElement) {
            targetElement.scrollIntoView({ behavior: "smooth" });
        }
    },

    animateCounter: function (
        elementId,
        target,
        duration = MARIMOI_APP_CONFIG.COUNTER_DURATION
    ) {
        const element = document.getElementById(elementId);
        if (!element) return;

        let start = 0;
        const increment = target / (duration / 16);

        const timer = setInterval(() => {
            start += increment;
            element.textContent = Math.floor(start);
            if (start >= target) {
                element.textContent = target;
                clearInterval(timer);
            }
        }, 16);
    },
};





// Carousel Module
const Carousel = {
    features: {
        currentIndex: 0,
        totalSlides: 0,
        interval: null,

        init: function () {
            const carousel = document.querySelector(".features-carousel");
            const slides = document.querySelectorAll(".feature-slide");
            const prevBtn = document.getElementById("prevFeature");
            const nextBtn = document.getElementById("nextFeature");

            if (!carousel || slides.length === 0) return;

            this.totalSlides = slides.length;

            // Event listeners
            if (prevBtn) {
                prevBtn.addEventListener("click", () => this.previous());
            }
            if (nextBtn) {
                nextBtn.addEventListener("click", () => this.next());
            }

            // Auto-advance
            this.startAutoAdvance();

            // Pause on hover
            carousel.addEventListener("mouseenter", () => {
                this.stopAutoAdvance();
            });

            carousel.addEventListener("mouseleave", () => {
                this.startAutoAdvance();
            });

            console.log("✓ Features carousel initialized");
        },

        next: function () {
            this.currentIndex = (this.currentIndex + 1) % this.totalSlides;
            this.updateCarousel();
        },

        previous: function () {
            this.currentIndex =
                (this.currentIndex - 1 + this.totalSlides) % this.totalSlides;
            this.updateCarousel();
        },

        updateCarousel: function () {
            const carousel = document.querySelector(".features-carousel");
            if (carousel) {
                const offset = -this.currentIndex * 100;
                carousel.style.transform = `translateX(${offset}%)`;
            }
        },

        startAutoAdvance: function () {
            this.stopAutoAdvance();
            this.interval = setInterval(() => {
                this.next();
            }, MARIMOI_APP_CONFIG.CAROUSEL_INTERVAL);
        },

        stopAutoAdvance: function () {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        },
    },
};

// Counter Animation Module
const CounterModule = {
    init: function () {
        const counterSection = document.querySelector(".counter-section");
        if (!counterSection) return;

        const counters = counterSection.querySelectorAll("[data-count]");
        if (counters.length === 0) return;

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        counters.forEach((counter) => {
                            const target = parseInt(
                                counter.getAttribute("data-count")
                            );
                            Utils.animateCounter(counter.id, target);
                        });
                        observer.disconnect();
                    }
                });
            },
            { threshold: 0.5 }
        );

        observer.observe(counterSection);
        console.log("✓ Counter module initialized");
    },
};

// Swiper Module
// const SwiperModule = {
//     init: function () {
//         const swipers = [];

//         // Peta Tematik Swiper
//         if (document.querySelector(".peta-tematik .swiper")) {
//             const petaTematikSwiper = new Swiper(".peta-tematik .swiper", {
//                 effect: "slide", // Changed from coverflow to slide
//                 grabCursor: true,
//                 centeredSlides: true,
//                 slidesPerView: "auto",
//                 loop: true,
//                 spaceBetween: 30,
//                 autoplay: {
//                     delay: 4000,
//                     disableOnInteraction: false,
//                 },
//                 pagination: {
//                     el: ".peta-tematik .swiper-pagination",
//                     clickable: true,
//                     dynamicBullets: true,
//                 },
//                 breakpoints: {
//                     640: {
//                         slidesPerView: 1,
//                         spaceBetween: 20,
//                         centeredSlides: true,
//                     },
//                     768: {
//                         slidesPerView: 2,
//                         spaceBetween: 25,
//                         centeredSlides: true,
//                     },
//                     1024: {
//                         slidesPerView: 3,
//                         spaceBetween: 30,
//                         centeredSlides: true,
//                     },
//                 },
//             });
//             swipers.push(petaTematikSwiper);
//             console.log("✓ Peta Tematik Swiper initialized");
//         }

//         // Main Swiper (for other sections)
//         if (document.querySelector(".main-swiper")) {
//             const mainSwiper = new Swiper(".main-swiper", {
//                 effect: "coverflow",
//                 grabCursor: true,
//                 centeredSlides: true,
//                 slidesPerView: "auto",
//                 loop: true,
//                 spaceBetween: 30,
//                 autoplay: {
//                     delay: 3000,
//                     disableOnInteraction: false,
//                 },
//                 coverflowEffect: {
//                     rotate: 50,
//                     stretch: 0,
//                     depth: 100,
//                     modifier: 1,
//                     slideShadows: true,
//                 },
//                 pagination: {
//                     el: ".swiper-pagination",
//                     clickable: true,
//                 },
//                 navigation: {
//                     nextEl: ".swiper-button-next",
//                     prevEl: ".swiper-button-prev",
//                 },
//                 breakpoints: {
//                     640: {
//                         slidesPerView: 1,
//                         spaceBetween: 20,
//                     },
//                     768: {
//                         slidesPerView: 2,
//                         spaceBetween: 30,
//                     },
//                     1024: {
//                         slidesPerView: 3,
//                         spaceBetween: 40,
//                     },
//                 },
//             });
//             swipers.push(mainSwiper);
//         }

//         return swipers;
//     },
// };
const SwiperModule = {
    init: function () {
        const swipers = [];

        // Peta Tematik Swiper - Sesuaikan dengan ID Tailwind
        if (document.querySelector("#peta-tematik .swiper")) {
            const petaTematikSwiper = new Swiper("#peta-tematik .swiper", {
                effect: "coverflow", // Kembali ke coverflow sesuai original
                grabCursor: true,
                centeredSlides: true,
                slidesPerView: "auto",
                loop: true,
                spaceBetween: 30,
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false,
                },
                coverflowEffect: {
                    rotate: 0,
                    stretch: 0,
                    depth: 100,
                    modifier: 2.5,
                },
                pagination: {
                    el: "#peta-tematik .swiper-pagination",
                    clickable: true,
                    dynamicBullets: true,
                },
                breakpoints: {
                    480: {
                        slidesPerView: 'auto',
                        spaceBetween: 20,
                        centeredSlides: true,
                        coverflowEffect: {
                            modifier: 2,
                            depth: 80,
                        }
                    },
                    768: {
                        slidesPerView: 'auto',
                        spaceBetween: 25,
                        centeredSlides: true,
                        coverflowEffect: {
                            modifier: 2.2,
                            depth: 90,
                        }
                    },
                    1024: {
                        slidesPerView: 'auto',
                        spaceBetween: 30,
                        centeredSlides: true,
                        coverflowEffect: {
                            modifier: 2.5,
                            depth: 100,
                        }
                    },
                },
                on: {
                    init: function() {
                        console.log("✓ Peta Tematik Swiper initialized with Tailwind");
                    }
                }
            });
            swipers.push(petaTematikSwiper);
        }

        // Main Swiper (for other sections) - tetap sama
        if (document.querySelector(".main-swiper")) {
            const mainSwiper = new Swiper(".main-swiper", {
                effect: "coverflow",
                grabCursor: true,
                centeredSlides: true,
                slidesPerView: "auto",
                loop: true,
                spaceBetween: 30,
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false,
                },
                coverflowEffect: {
                    rotate: 50,
                    stretch: 0,
                    depth: 100,
                    modifier: 1,
                    slideShadows: true,
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                breakpoints: {
                    640: {
                        slidesPerView: 1,
                        spaceBetween: 20,
                    },
                    768: {
                        slidesPerView: 2,
                        spaceBetween: 30,
                    },
                    1024: {
                        slidesPerView: 3,
                        spaceBetween: 40,
                    },
                },
            });
            swipers.push(mainSwiper);
        }

        return swipers;
    },
};


// Spotlight Effect Module
// const SpotlightEffect = {
//     init: function () {
//         try {
//             const aboutSection = document.getElementById("about-section");
//             if (!aboutSection) {
//                 console.log(
//                     "⚠ Spotlight: About section not found, skipping initialization"
//                 );
//                 return;
//             }

//             let targetX = 75; // Default position (foto)
//             let targetY = 50;
//             let currentX = 75;
//             let currentY = 50;
//             let animationId = null;

//             const lerp = (start, end, factor) => {
//                 return start + (end - start) * factor;
//             };

//             const updateSpotlight = () => {
//                 const speed = 0.05; // Kecepatan animasi

//                 currentX = lerp(currentX, targetX, speed);
//                 currentY = lerp(currentY, targetY, speed);

//                 aboutSection.style.setProperty("--mask-x", `${currentX}%`);
//                 aboutSection.style.setProperty("--mask-y", `${currentY}%`);

//                 const distance = Math.sqrt(
//                     Math.pow(targetX - currentX, 2) +
//                         Math.pow(targetY - currentY, 2)
//                 );

//                 if (distance > 0.1) {
//                     animationId = requestAnimationFrame(updateSpotlight);
//                 } else {
//                     animationId = null;
//                 }
//             };

//             const handleMouseMove = (e) => {
//                 const rect = aboutSection.getBoundingClientRect();
//                 targetX = ((e.clientX - rect.left) / rect.width) * 100;
//                 targetY = ((e.clientY - rect.top) / rect.height) * 100;

//                 // Clamp values to prevent going outside bounds
//                 targetX = Math.max(0, Math.min(100, targetX));
//                 targetY = Math.max(0, Math.min(100, targetY));

//                 // Ubah opacity background saat mouse move
//                 aboutSection.style.setProperty("--bg-opacity", "1");

//                 if (!animationId) {
//                     animationId = requestAnimationFrame(updateSpotlight);
//                 }
//             };

//             const handleMouseLeave = () => {
//                 // Kembali ke posisi foto saat mouse leave
//                 targetX = 75; // Kembali ke posisi foto
//                 targetY = 50;

//                 // Kembali ke opacity default
//                 aboutSection.style.setProperty("--bg-opacity", "1");

//                 if (!animationId) {
//                     animationId = requestAnimationFrame(updateSpotlight);
//                 }
//             };

//             // Add event listeners
//             aboutSection.addEventListener("mousemove", handleMouseMove);
//             aboutSection.addEventListener("mouseleave", handleMouseLeave);

//             // Initialize CSS custom properties
//             aboutSection.style.setProperty("--mask-x", "75%"); // Posisi foto
//             aboutSection.style.setProperty("--mask-y", "50%");
//             aboutSection.style.setProperty("--bg-opacity", "1"); // Default tampil dengan opacity 0.7

//             // Initialize spotlight at center
//             updateSpotlight();

//             console.log("✓ Spotlight effect initialized");
//         } catch (error) {
//             console.error("⚠ Spotlight effect initialization failed:", error);
//         }
//     },
// };
// Spotlight Effect Module
const SpotlightEffect = {
    init: function () {
        try {
            // Update ID sesuai dengan section about yang baru
            const aboutSection = document.getElementById("about-section");
            if (!aboutSection) {
                console.log(
                    "⚠ Spotlight: About section not found, skipping initialization"
                );
                return;
            }

            // Variabel untuk responsive positioning
            let isDesktop = window.innerWidth > 1024;
            let isTablet = window.innerWidth <= 1024 && window.innerWidth > 768;
            let isMobile = window.innerWidth <= 768;

            // Default position berdasarkan device
            let defaultX = isDesktop ? 80 : isMobile ? 50 : 75; // Responsive default position
            let defaultY = isDesktop ? 50 : isMobile ? 70 : 50;

            let targetX = defaultX;
            let targetY = defaultY;
            let currentX = defaultX;
            let currentY = defaultY;
            let animationId = null;

            const lerp = (start, end, factor) => {
                return start + (end - start) * factor;
            };

            const updateSpotlight = () => {
                const speed = isMobile ? 0.08 : 0.05; // Faster animation on mobile

                currentX = lerp(currentX, targetX, speed);
                currentY = lerp(currentY, targetY, speed);

                aboutSection.style.setProperty("--mask-x", `${currentX}%`);
                aboutSection.style.setProperty("--mask-y", `${currentY}%`);

                const distance = Math.sqrt(
                    Math.pow(targetX - currentX, 2) +
                        Math.pow(targetY - currentY, 2)
                );

                if (distance > 0.1) {
                    animationId = requestAnimationFrame(updateSpotlight);
                } else {
                    animationId = null;
                }
            };

            const handleMouseMove = (e) => {
                // Skip mouse events on mobile untuk better performance
                if (isMobile) return;

                const rect = aboutSection.getBoundingClientRect();
                targetX = ((e.clientX - rect.left) / rect.width) * 100;
                targetY = ((e.clientY - rect.top) / rect.height) * 100;

                // Clamp values to prevent going outside bounds
                targetX = Math.max(0, Math.min(100, targetX));
                targetY = Math.max(0, Math.min(100, targetY));

                // Ubah opacity background saat mouse move
                aboutSection.style.setProperty("--bg-opacity", "1");

                if (!animationId) {
                    animationId = requestAnimationFrame(updateSpotlight);
                }
            };

            const handleMouseLeave = () => {
                // Skip pada mobile
                if (isMobile) return;

                // Kembali ke posisi default berdasarkan device
                targetX = defaultX;
                targetY = defaultY;

                // Kembali ke opacity default
                aboutSection.style.setProperty("--bg-opacity", "1");

                if (!animationId) {
                    animationId = requestAnimationFrame(updateSpotlight);
                }
            };

            // Touch interaction untuk mobile
            const handleTouchMove = (e) => {
                if (!isMobile) return;

                const rect = aboutSection.getBoundingClientRect();
                const touch = e.touches[0];
                targetX = ((touch.clientX - rect.left) / rect.width) * 100;
                targetY = ((touch.clientY - rect.top) / rect.height) * 100;

                // Clamp values
                targetX = Math.max(0, Math.min(100, targetX));
                targetY = Math.max(0, Math.min(100, targetY));

                aboutSection.style.setProperty("--bg-opacity", "1");

                if (!animationId) {
                    animationId = requestAnimationFrame(updateSpotlight);
                }
            };

            const handleTouchEnd = () => {
                if (!isMobile) return;

                // Kembali ke posisi default setelah touch selesai
                setTimeout(() => {
                    targetX = defaultX;
                    targetY = defaultY;

                    if (!animationId) {
                        animationId = requestAnimationFrame(updateSpotlight);
                    }
                }, 1000); // Delay 1 detik sebelum kembali ke posisi default
            };

            // Handle resize untuk update responsive values
            const handleResize = () => {
                isDesktop = window.innerWidth > 1024;
                isTablet = window.innerWidth <= 1024 && window.innerWidth > 768;
                isMobile = window.innerWidth <= 768;

                // Update default positions
                defaultX = isDesktop ? 75 : isMobile ? 85 : 80;
                defaultY = isDesktop ? 50 : isMobile ? 75 : 60;

                // Reset to new default position
                targetX = defaultX;
                targetY = defaultY;

                if (!animationId) {
                    animationId = requestAnimationFrame(updateSpotlight);
                }
            };

            // Add event listeners berdasarkan device
            if (!isMobile) {
                aboutSection.addEventListener("mousemove", handleMouseMove);
                aboutSection.addEventListener("mouseleave", handleMouseLeave);
            } else {
                // Touch events untuk mobile
                aboutSection.addEventListener("touchmove", handleTouchMove, { passive: true });
                aboutSection.addEventListener("touchend", handleTouchEnd, { passive: true });
            }

            // Resize listener
            window.addEventListener("resize", handleResize);

            // Initialize CSS custom properties dengan nilai responsive
            aboutSection.style.setProperty("--mask-x", `${defaultX}%`);
            aboutSection.style.setProperty("--mask-y", `${defaultY}%`);
            aboutSection.style.setProperty("--bg-opacity", "1");

            // Initialize spotlight
            updateSpotlight();

            console.log(`✓ Spotlight effect initialized (${isMobile ? 'Mobile' : isTablet ? 'Tablet' : 'Desktop'} mode)`);
        } catch (error) {
            console.error("⚠ Spotlight effect initialization failed:", error);
        }
    },
};


// Indikator Module (Updated for Tailwind HTML with Mobile + Desktop Navigation)
const IndikatorModule = {
    init: function () {
        try {
            // Get mobile and desktop nav dots separately to handle duplicates
            const mobileNavDots = document.querySelectorAll(
                ".lg\\:hidden .nav-dot"
            );
            const desktopNavDots = document.querySelectorAll(
                ".lg\\:flex .nav-dot"
            );
            const allNavDots = document.querySelectorAll(".nav-dot");

            const dynamicSubtitle = document.getElementById("dynamicSubtitle");
            const dynamicDescription =
                document.getElementById("dynamicDescription");
            const parallaxBg = document.querySelector(".parallax-bg");
            const section = document.querySelector(".indikator-pembangunan");

            let currentIndex = 0;
            let isScrolling = false;
            const totalSlides = 4; // We know there are 4 slides

            // Detect device type
            const isDesktop = window.innerWidth > 1024;
            const isTablet =
                window.innerWidth <= 1024 && window.innerWidth > 768;
            const isMobile = window.innerWidth <= 768;

            if (allNavDots.length === 0) {
                console.log(
                    "⚠ Indikator: No nav dots found, skipping initialization"
                );
                return;
            }

            console.log(
                `Found ${allNavDots.length} total nav dots (${mobileNavDots.length} mobile, ${desktopNavDots.length} desktop)`
            );

            // Setup navigation buttons
            this.setupNavigationButtons();

            const updateContent = (index) => {
                if (index < 0 || index >= totalSlides) return;

                currentIndex = index;

                // Update ALL nav dots (both mobile and desktop)
                allNavDots.forEach((dot, dotIndex) => {
                    // Calculate which slide this dot represents
                    const slideIndex = dotIndex % totalSlides;

                    if (slideIndex === index) {
                        // This dot should be active
                        dot.classList.add("active");
                        dot.classList.remove("bg-white/60");
                        dot.classList.add("bg-blue-400");
                    } else {
                        // This dot should be inactive
                        dot.classList.remove("active");
                        dot.classList.remove("bg-blue-400");
                        dot.classList.add("bg-white/60");
                    }
                });

                // Update navigation buttons state
                this.updateNavigationButtons(index, totalSlides);

                // Get content from the first set of nav dots (they all have the same data)
                const referenceDot = allNavDots[index] || allNavDots[0];
                const title = referenceDot.getAttribute("data-title");
                const description =
                    referenceDot.getAttribute("data-description");

                if (dynamicSubtitle && title) {
                    dynamicSubtitle.style.opacity = "0";
                    setTimeout(() => {
                        dynamicSubtitle.textContent = title;
                        dynamicSubtitle.style.opacity = "1";
                    }, 150);
                }

                if (dynamicDescription) {
                    dynamicDescription.style.opacity = "0";
                    setTimeout(() => {
                        dynamicDescription.innerHTML = description;
                        dynamicDescription.style.opacity = "1";
                    }, 150);
                }

                // Static background - no parallax movement to prevent cropping
                if (parallaxBg) {
                    // Keep image static for all devices to show full image
                    parallaxBg.style.transform = `none`;
                }
            };

            // Navigation functions - NO LOOP (must go back manually)
            const nextSlide = () => {
                if (currentIndex < totalSlides - 1) {
                    const nextIndex = currentIndex + 1;
                    updateContent(nextIndex);
                }
            };

            const prevSlide = () => {
                if (currentIndex > 0) {
                    const prevIndex = currentIndex - 1;
                    updateContent(prevIndex);
                }
            };

            // Store navigation functions for button access
            this.nextSlide = nextSlide;
            this.prevSlide = prevSlide;

            // Dot click events - handle both mobile and desktop dots
            allNavDots.forEach((dot, dotIndex) => {
                dot.addEventListener("click", () => {
                    // Calculate which slide this dot represents
                    const slideIndex = dotIndex % totalSlides;
                    updateContent(slideIndex);
                });
            });

            // Mouse wheel navigation - disabled for desktop, enabled for mobile/tablet only
            if (
                (section && !isDesktop) ||
                (section && isTablet) ||
                (section && isMobile)
            ) {
                section.addEventListener(
                    "wheel",
                    (e) => {
                        if (isScrolling) return;

                        if (e.deltaY > 0) {
                            nextSlide();
                        } else {
                            prevSlide();
                        }

                        isScrolling = true;
                        setTimeout(() => {
                            isScrolling = false;
                        }, 300);
                    },
                    { passive: true }
                );
            }

            // Touch navigation - Enhanced for mobile swipe
            if (section) {
                let startY = 0;
                let startX = 0;
                let isDragging = false;

                section.addEventListener(
                    "touchstart",
                    (e) => {
                        startY = e.touches[0].clientY;
                        startX = e.touches[0].clientX;
                        isDragging = true;
                    },
                    { passive: true }
                );

                section.addEventListener(
                    "touchmove",
                    (e) => {
                        if (!isDragging) return;

                        // Prevent default scroll behavior for horizontal swipes on mobile
                        if (isMobile || isTablet) {
                            const currentX = e.touches[0].clientX;
                            const diffX = Math.abs(startX - currentX);
                            const currentY = e.touches[0].clientY;
                            const diffY = Math.abs(startY - currentY);

                            // If horizontal movement is greater than vertical, prevent scroll
                            if (diffX > diffY && diffX > 10) {
                                e.preventDefault();
                            }
                        }
                    },
                    { passive: false }
                );

                section.addEventListener(
                    "touchend",
                    (e) => {
                        if (!isDragging) return;

                        const endY = e.changedTouches[0].clientY;
                        const endX = e.changedTouches[0].clientX;
                        const diffY = startY - endY;
                        const diffX = startX - endX;

                        // Reduced threshold for better responsiveness
                        const threshold = 30;

                        if (isMobile || isTablet) {
                            // Mobile & Tablet: Horizontal swipe
                            if (
                                Math.abs(diffX) > threshold &&
                                Math.abs(diffX) > Math.abs(diffY)
                            ) {
                                if (diffX > 0) {
                                    // Swipe left - next slide
                                    nextSlide();
                                } else {
                                    // Swipe right - previous slide
                                    prevSlide();
                                }
                            }
                        } else if (isDesktop) {
                            // Desktop: Vertical swipe
                            if (
                                Math.abs(diffY) > threshold &&
                                Math.abs(diffY) > Math.abs(diffX)
                            ) {
                                if (diffY > 0) {
                                    nextSlide();
                                } else {
                                    prevSlide();
                                }
                            }
                        }

                        startY = 0;
                        startX = 0;
                        isDragging = false;
                    },
                    { passive: true }
                );

                // Keyboard navigation
                document.addEventListener("keydown", (e) => {
                    if (isDesktop) {
                        if (e.key === "ArrowDown") {
                            e.preventDefault();
                            nextSlide();
                        } else if (e.key === "ArrowUp") {
                            e.preventDefault();
                            prevSlide();
                        }
                    } else {
                        if (e.key === "ArrowRight") {
                            e.preventDefault();
                            nextSlide();
                        } else if (e.key === "ArrowLeft") {
                            e.preventDefault();
                            prevSlide();
                        }
                    }
                });
            }

            // Add smooth transitions to content elements
            if (dynamicSubtitle) {
                dynamicSubtitle.style.transition = "opacity 0.3s ease";
            }
            if (dynamicDescription) {
                dynamicDescription.style.transition = "opacity 0.3s ease";
            }

            // Add visual feedback for touch interactions on mobile
            if (section && (isMobile || isTablet)) {
                section.style.cursor = "grab";

                section.addEventListener(
                    "touchstart",
                    () => {
                        section.style.cursor = "grabbing";
                    },
                    { passive: true }
                );

                section.addEventListener(
                    "touchend",
                    () => {
                        section.style.cursor = "grab";
                    },
                    { passive: true }
                );
            }

            // Initialize first slide
            updateContent(0);

            console.log(
                "✓ Indikator Module initialized (Fixed Dual Navigation)"
            );
        } catch (error) {
            console.error("⚠ Indikator Module initialization failed:", error);
        }
    },

    setupNavigationButtons: function () {
        const prevBtn = document.querySelector(".nav-btn-prev");
        const nextBtn = document.querySelector(".nav-btn-next");

        if (prevBtn && nextBtn) {
            prevBtn.addEventListener("click", () => {
                if (this.prevSlide) this.prevSlide();
            });

            nextBtn.addEventListener("click", () => {
                if (this.nextSlide) this.nextSlide();
            });

            console.log("✓ Navigation buttons setup complete");
        }
    },

    stylizeNavDots: function (navDots) {
        console.log("✓ Navigation dots styling applied via CSS");
    },

    injectNavDotStyles: function () {
        console.log("✓ Navigation dot styles handled via CSS");
    },

    updateNavigationButtons: function (currentIndex, totalSlides) {
        const prevBtn = document.querySelector(".nav-btn-prev");
        const nextBtn = document.querySelector(".nav-btn-next");

        if (!prevBtn || !nextBtn) return;

        // Update button states - disable at ends (no cycling)
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex === totalSlides - 1;

        // Update visual states
        if (currentIndex === 0) {
            prevBtn.classList.add("opacity-50", "cursor-not-allowed");
        } else {
            prevBtn.classList.remove("opacity-50", "cursor-not-allowed");
        }

        if (currentIndex === totalSlides - 1) {
            nextBtn.classList.add("opacity-50", "cursor-not-allowed");
        } else {
            nextBtn.classList.remove("opacity-50", "cursor-not-allowed");
        }
    },

    showNavigateButton: function (section, isDesktop) {
        // Legacy function - kept for compatibility
        console.log("✓ Navigation handled by setupNavigationButtons");
    },
};

// Wavify Module
const WavifyModule = {
    config: {
        white: {
            height: 80,
            bones: 3,
            amplitude: 40,
            color: "rgba(255, 255, 255, 1)",
            speed: 0.15,
        },
        transparent: {
            height: 60,
            bones: 4,
            amplitude: 30,
            color: "rgba(0, 127, 255, 0.6)", // Blue color for wave-blue-1
            speed: 0.25,
        },
        gradient: {
            height: 100,
            bones: 5,
            amplitude: 50,
            color: "rgba(0, 127, 255, 0.3)", // Lighter blue for wave-blue-2
            speed: 0.2,
        },
    },

    init: function () {
        // Initialize white wave
        const whiteWave = document.querySelector("#wave-white");
        if (whiteWave && typeof wavify === "function") {
            try {
                wavify(whiteWave, this.config.white);
                console.log("✓ White wave initialized");
            } catch (error) {
                console.log("⚠ White wave initialization failed:", error);
            }
        }

        // Initialize blue wave 1 (middle layer)
        const blueWave1 = document.querySelector("#wave-blue-1");
        if (blueWave1 && typeof wavify === "function") {
            try {
                wavify(blueWave1, this.config.transparent);
                console.log("✓ Blue wave 1 initialized");
            } catch (error) {
                console.log("⚠ Blue wave 1 initialization failed:", error);
            }
        }

        // Initialize blue wave 2 (back layer)
        const blueWave2 = document.querySelector("#wave-blue-2");
        if (blueWave2 && typeof wavify === "function") {
            try {
                wavify(blueWave2, this.config.gradient);
                console.log("✓ Blue wave 2 initialized");
            } catch (error) {
                console.log("⚠ Blue wave 2 initialization failed:", error);
            }
        }
    },
};

// Global Functions (for onclick handlers)
window.nextFeature = () => Carousel.features.next();
window.previousFeature = () => Carousel.features.previous();
window.scrollToSection = (sectionId) => Utils.scrollToSection(sectionId);
window.openAspirationForm = () => {
    alert(
        "Formulir aspirasi akan segera diluncurkan. Terima kasih atas antusiasme Anda!"
    );
};

// Peta Tematik Functions
window.viewMap = (mapId) => {
    console.log(`Viewing map: ${mapId}`);
    alert(`Fitur Peta ${mapId} akan segera tersedia!`);
};


// Application Initialization
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(() => {
        console.log("🚀 Initializing MARIMOI Application...");

        Carousel.features.init();
        CounterModule.init();
        SpotlightEffect.init();

        // Initialize IndikatorModule if elements exist
        if (
            document.querySelector(".indikator-pembangunan") ||
            document.querySelector(".nav-dot")
        ) {
            IndikatorModule.init();
        }

        const swipers = SwiperModule.init();
        if (swipers && swipers.length > 0) {
            console.log(`✓ ${swipers.length} Swiper(s) initialized`);
        }

        console.log("✓ MARIMOI Application ready");
    }, MARIMOI_APP_CONFIG.ANIMATION_DELAY);
});

// Initialize Wavify on window load
window.addEventListener("load", () => {
    WavifyModule.init();
});
