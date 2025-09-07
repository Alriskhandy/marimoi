/**
 * Template Name: Logis
 * Template URL: https://bootstrapmade.com/logis-bootstrap-logistics-website-template/
 * Updated: Aug 07 2024 with Bootstrap v5.3.3
 * Author: BootstrapMade.com
 * License: https://bootstrapmade.com/license/
 */

(function () {
    "use strict";

    /**
     * Apply .scrolled class to the body as the page is scrolled down
     */
    function toggleScrolled() {
        const selectBody = document.querySelector("body");
        const selectHeader = document.querySelector("#header");
        if (
            !selectHeader.classList.contains("scroll-up-sticky") &&
            !selectHeader.classList.contains("sticky-top") &&
            !selectHeader.classList.contains("fixed-top")
        )
            return;
        window.scrollY > 100
            ? selectBody.classList.add("scrolled")
            : selectBody.classList.remove("scrolled");
    }

    document.addEventListener("scroll", toggleScrolled);
    window.addEventListener("load", toggleScrolled);

    /**
     * Mobile nav toggle
     */
    const mobileNavToggleBtn = document.querySelector(".mobile-nav-toggle");

    function mobileNavToogle() {
        document.querySelector("body").classList.toggle("mobile-nav-active");
        mobileNavToggleBtn.classList.toggle("bi-list");
        mobileNavToggleBtn.classList.toggle("bi-x");
    }
    mobileNavToggleBtn.addEventListener("click", mobileNavToogle);

    /**
     * Hide mobile nav on same-page/hash links
     */
    document.querySelectorAll("#navmenu a").forEach((navmenu) => {
        navmenu.addEventListener("click", () => {
            if (document.querySelector(".mobile-nav-active")) {
                mobileNavToogle();
            }
        });
    });

    /**
     * Toggle mobile nav dropdowns
     */
    document
        .querySelectorAll(".navmenu .toggle-dropdown")
        .forEach((navmenu) => {
            navmenu.addEventListener("click", function (e) {
                e.preventDefault();
                this.parentNode.classList.toggle("active");
                this.parentNode.nextElementSibling.classList.toggle(
                    "dropdown-active"
                );
                e.stopImmediatePropagation();
            });
        });

    /**
     * Preloader
     */
    const preloader = document.querySelector("#preloader");
    if (preloader) {
        window.addEventListener("load", () => {
            preloader.remove();
        });
    }

    /**
     * Scroll top button
     */
    let scrollTop = document.querySelector(".scroll-top");

    function toggleScrollTop() {
        if (scrollTop) {
            window.scrollY > 100
                ? scrollTop.classList.add("active")
                : scrollTop.classList.remove("active");
        }
    }
    scrollTop.addEventListener("click", (e) => {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: "smooth",
        });
    });

    window.addEventListener("load", toggleScrollTop);
    document.addEventListener("scroll", toggleScrollTop);

    /**
     * Animation on scroll function and init
     */
    function aosInit() {
        AOS.init({
            duration: 600,
            easing: "ease-in-out",
            once: true,
            mirror: false,
        });
    }
    window.addEventListener("load", aosInit);

    /**
     * Initiate Pure Counter
     */
    new PureCounter();

    /**
     * Initiate glightbox
     */
    const glightbox = GLightbox({
        selector: ".glightbox",
    });

    /**
     * Init swiper sliders
     */
    function initSwiper() {
        document
            .querySelectorAll(".init-swiper")
            .forEach(function (swiperElement) {
                let config = JSON.parse(
                    swiperElement
                        .querySelector(".swiper-config")
                        .innerHTML.trim()
                );

                if (swiperElement.classList.contains("swiper-tab")) {
                    initSwiperWithCustomPagination(swiperElement, config);
                } else {
                    new Swiper(swiperElement, config);
                }
            });
    }

    window.addEventListener("load", initSwiper);

    /**
     * Frequently Asked Questions Toggle
     */
    document
        .querySelectorAll(".faq-item h3, .faq-item .faq-toggle")
        .forEach((faqItem) => {
            faqItem.addEventListener("click", () => {
                faqItem.parentNode.classList.toggle("faq-active");
            });
        });

    /**
     * Vertical Menu Module for Fitur Utama Section
     */
    const VerticalMenu = {
        init() {
            this.menuItems = document.querySelectorAll(".vertical-menu-item");
            this.setupEventListeners();
            this.setDefaultActive();
        },

        setupEventListeners() {
            this.menuItems.forEach((item, index) => {
                item.addEventListener("click", () =>
                    this.handleMenuClick(item, index)
                );
                item.addEventListener("mouseenter", () =>
                    this.handleMenuHover(item)
                );
                item.addEventListener("mouseleave", () =>
                    this.handleMenuLeave(item)
                );
            });
        },

        handleMenuClick(clickedItem, index) {
            // Remove active class from all items
            this.menuItems.forEach((item) => {
                item.classList.remove("active");
            });

            // Add active class to clicked item
            clickedItem.classList.add("active");

            // Add ripple effect
            this.addRippleEffect(clickedItem);

            console.log(
                `Menu item ${index + 1} clicked:`,
                clickedItem.textContent.trim()
            );
        },

        handleMenuHover(item) {
            if (!item.classList.contains("active")) {
                item.style.transform = "translateX(10px)";
            }
        },

        handleMenuLeave(item) {
            if (!item.classList.contains("active")) {
                item.style.transform = "";
            }
        },

        addRippleEffect(element) {
            const ripple = document.createElement("div");
            ripple.style.cssText = `
              position: absolute;
              background: rgba(255, 255, 255, 0.3);
              border-radius: 50%;
              transform: scale(0);
              animation: ripple 0.6s linear;
              left: 50%;
              top: 50%;
              width: 100px;
              height: 100px;
              margin-left: -50px;
              margin-top: -50px;
              pointer-events: none;
            `;

            element.style.position = "relative";
            element.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        },

        setDefaultActive() {
            if (this.menuItems.length > 0) {
                this.menuItems[0].classList.add("active");
            }
        },
    };

    // Initialize VerticalMenu when page loads
    window.addEventListener("load", () => {
        if (document.querySelector(".vertical-menu-item")) {
            VerticalMenu.init();
        }
    });
})();
