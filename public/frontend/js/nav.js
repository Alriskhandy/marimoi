/**
 * ========================================
 * MARIMOI - Navigation JavaScript Module
 * ========================================
 */

// Navigation Module
const Navigation = {
    init: function () {
        const navbar = document.getElementById("navbar");
        const mobileMenuButton = document.getElementById("mobileMenuButton");
        const navMenu = document.getElementById("navMenu");
        const mobileMenuOverlay = document.getElementById("mobileMenuOverlay");
        const dropdowns = document.querySelectorAll(".dropdown");
        const body = document.body;

        if (!mobileMenuButton || !navMenu) return;

        const closeMobileMenu = () => {
            mobileMenuButton.classList.remove("active");
            navMenu.classList.remove("active");
            if (mobileMenuOverlay) mobileMenuOverlay.classList.remove("active");
            dropdowns.forEach((dropdown) =>
                dropdown.classList.remove("active")
            );
            body.style.overflow = "";
        };

        const openMobileMenu = () => {
            mobileMenuButton.classList.add("active");
            navMenu.classList.add("active");
            if (mobileMenuOverlay) mobileMenuOverlay.classList.add("active");
            body.style.overflow = "hidden";
        };

        // Event listeners
        mobileMenuButton.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            navMenu.classList.contains("active")
                ? closeMobileMenu()
                : openMobileMenu();
        });

        if (mobileMenuOverlay) {
            mobileMenuOverlay.addEventListener("click", closeMobileMenu);
        }

        dropdowns.forEach((dropdown) => {
            const trigger = dropdown.querySelector(".dropdown-trigger");
            if (trigger) {
                // Click handler for mobile and desktop
                trigger.addEventListener("click", (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    // For mobile specifically
                    if (window.innerWidth <= 768) {
                        // Close other dropdowns
                        dropdowns.forEach((otherDropdown) => {
                            if (otherDropdown !== dropdown) {
                                otherDropdown.classList.remove("active");
                                const otherTrigger =
                                    otherDropdown.querySelector(
                                        ".dropdown-trigger"
                                    );
                                if (otherTrigger) {
                                    otherTrigger.setAttribute(
                                        "aria-expanded",
                                        "false"
                                    );
                                }
                            }
                        });

                        // Toggle current dropdown
                        const isActive = dropdown.classList.contains("active");
                        dropdown.classList.toggle("active");
                        trigger.setAttribute(
                            "aria-expanded",
                            (!isActive).toString()
                        );
                    }
                });

                // Hover handlers for desktop
                if (window.innerWidth > 768) {
                    dropdown.addEventListener("mouseenter", () => {
                        dropdown.classList.add("active");
                        trigger.setAttribute("aria-expanded", "true");
                    });

                    dropdown.addEventListener("mouseleave", () => {
                        dropdown.classList.remove("active");
                        trigger.setAttribute("aria-expanded", "false");
                    });
                }
            }
        });

        // Close dropdowns when clicking outside
        document.addEventListener("click", (e) => {
            if (!e.target.closest(".dropdown")) {
                dropdowns.forEach((dropdown) => {
                    dropdown.classList.remove("active");
                    const trigger = dropdown.querySelector(".dropdown-trigger");
                    if (trigger) {
                        trigger.setAttribute("aria-expanded", "false");
                    }
                });
            }
        });

        // Close mobile menu when clicking nav links
        const navLinks = document.querySelectorAll(".nav-link");
        navLinks.forEach((link) => {
            link.addEventListener("click", closeMobileMenu);
        });
    },
};

// Vertical Menu for Mobile
const VerticalMenu = {
    init: function () {
        const menuItems = document.querySelectorAll(".vertical-menu-item");

        menuItems.forEach((item) => {
            item.addEventListener("click", function (e) {
                e.preventDefault();

                // Remove active class from all items
                menuItems.forEach((i) => i.classList.remove("active"));

                // Add active class to clicked item
                this.classList.add("active");

                // Get target section
                const targetId = this.getAttribute("data-target");
                if (targetId) {
                    // Import scrollToSection from Utils if needed
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: "smooth" });
                    }
                }
            });
        });
    },
};

// Initialize navigation modules when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    Navigation.init();
    VerticalMenu.init();
});

// Export modules for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Navigation, VerticalMenu };
} else if (typeof window !== 'undefined') {
    window.Navigation = Navigation;
    window.VerticalMenu = VerticalMenu;
}
