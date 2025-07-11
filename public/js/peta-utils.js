/**
 * GIS Map Application Utilities
 * Contains helper functions and utility methods
 */

// Destructure config from global object
const {
    APP_CONFIG,
    CATEGORY_COLORS,
    MAP_STYLES,
    ERROR_MESSAGES,
    SUCCESS_MESSAGES,
    ANIMATIONS,
} = window.GIS_CONFIG;

/**
 * Utility Functions
 */
const GISUtils = {
    /**
     * Get category color configuration
     * @param {string} kategori - Category name
     * @returns {object} Color configuration
     */
    getCategoryColor(kategori) {
        const key = kategori ? kategori.toLowerCase() : "default";
        return CATEGORY_COLORS[key] || CATEGORY_COLORS["default"];
    },

    /**
     * Format file size to human readable format
     * @param {number} bytes - File size in bytes
     * @returns {string} Formatted file size
     */
    formatFileSize(bytes) {
        if (bytes === 0) return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    },

    /**
     * Format number with thousand separators
     * @param {number} num - Number to format
     * @returns {string} Formatted number
     */
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    },

    /**
     * Truncate text to specified length
     * @param {string} text - Text to truncate
     * @param {number} length - Maximum length
     * @returns {string} Truncated text
     */
    truncateText(text, length = 50) {
        if (!text || text.length <= length) return text;
        return text.substring(0, length) + "...";
    },

    /**
     * Debounce function execution
     * @param {function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {function} Debounced function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Show loading overlay
     */
    showLoading() {
        const overlay = document.getElementById("loading-overlay");
        if (overlay) {
            overlay.style.display = "flex";
            overlay.classList.add(ANIMATIONS.fadeIn);
        }
    },

    /**
     * Hide loading overlay
     */
    hideLoading() {
        const overlay = document.getElementById("loading-overlay");
        if (overlay) {
            overlay.style.display = "none";
            overlay.classList.remove(ANIMATIONS.fadeIn);
        }
    },

    /**
     * Show alert message
     * @param {string} message - Message to show
     * @param {string} type - Alert type (success, warning, danger, info)
     * @param {number} duration - Auto-hide duration in milliseconds
     */
    showAlert(message, type = "info", duration = 5000) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll(".alert-custom");
        existingAlerts.forEach((alert) => alert.remove());

        const alertDiv = document.createElement("div");
        alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-custom ${ANIMATIONS.fadeIn}`;

        const iconMap = {
            success: "bi-check-circle-fill",
            warning: "bi-exclamation-triangle-fill",
            danger: "bi-x-circle-fill",
            info: "bi-info-circle-fill",
        };

        const icon = iconMap[type] || iconMap.info;

        alertDiv.innerHTML = `
            <i class="bi ${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;

        document.body.appendChild(alertDiv);

        // Auto remove after specified duration
        if (duration > 0) {
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.classList.add("fade");
                    setTimeout(() => alertDiv.remove(), 150);
                }
            }, duration);
        }
    },

    /**
     * Animate counter from start to end value
     * @param {string} elementId - Element ID to animate
     * @param {number} start - Start value
     * @param {number} end - End value
     * @param {number} duration - Animation duration in milliseconds
     */
    animateCounter(elementId, start, end, duration = 1000) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if (current >= end) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 16);
    },

    /**
     * Copy text to clipboard
     * @param {string} text - Text to copy
     * @returns {Promise<boolean>} Success status
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showAlert(SUCCESS_MESSAGES.linkCopied, "success", 3000);
            return true;
        } catch (err) {
            console.error("Failed to copy to clipboard:", err);
            return false;
        }
    },

    /**
     * Get current map URL with parameters
     * @param {L.Map} map - Leaflet map instance
     * @returns {string} Current map URL
     */
    getCurrentMapUrl(map) {
        const center = map.getCenter();
        const zoom = map.getZoom();
        const baseUrl = window.location.origin + window.location.pathname;
        return `${baseUrl}#${zoom}/${center.lat.toFixed(
            5
        )}/${center.lng.toFixed(5)}`;
    },

    /**
     * Save map view to localStorage
     * @param {L.Map} map - Leaflet map instance
     */
    saveMapView(map) {
        try {
            const center = map.getCenter();
            const zoom = map.getZoom();
            const view = {
                lat: center.lat,
                lng: center.lng,
                zoom: zoom,
                timestamp: Date.now(),
            };
            localStorage.setItem(
                window.GIS_CONFIG.STORAGE_KEYS.mapView,
                JSON.stringify(view)
            );
        } catch (err) {
            console.warn("Failed to save map view:", err);
        }
    },

    /**
     * Load map view from localStorage
     * @returns {object|null} Saved map view or null
     */
    loadMapView() {
        try {
            const saved = localStorage.getItem(
                window.GIS_CONFIG.STORAGE_KEYS.mapView
            );
            if (saved) {
                const view = JSON.parse(saved);
                // Check if saved view is not too old (24 hours)
                if (Date.now() - view.timestamp < 24 * 60 * 60 * 1000) {
                    return view;
                }
            }
        } catch (err) {
            console.warn("Failed to load map view:", err);
        }
        return null;
    },

    /**
     * Initialize tooltips
     */
    initTooltips() {
        try {
            const tooltipTriggerList = [].slice.call(
                document.querySelectorAll('[data-bs-toggle="tooltip"]')
            );
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    delay: { show: APP_CONFIG.ui.tooltipDelay, hide: 100 },
                });
            });
        } catch (err) {
            console.warn("Failed to initialize tooltips:", err);
        }
    },

    /**
     * Handle API errors
     * @param {Error} error - Error object
     * @param {string} context - Error context
     */
    handleApiError(error, context = "") {
        console.error(`API Error ${context}:`, error);

        let message = ERROR_MESSAGES.loadError;

        if (error.name === "TypeError" && error.message.includes("fetch")) {
            message = ERROR_MESSAGES.networkError;
        }

        this.showAlert(message, "danger");
    },

    /**
     * Validate coordinates
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @returns {boolean} Validation result
     */
    isValidCoordinate(lat, lng) {
        return (
            !isNaN(lat) &&
            !isNaN(lng) &&
            lat >= -90 &&
            lat <= 90 &&
            lng >= -180 &&
            lng <= 180
        );
    },

    /**
     * Calculate distance between two points
     * @param {array} point1 - [lat, lng]
     * @param {array} point2 - [lat, lng]
     * @returns {number} Distance in kilometers
     */
    calculateDistance(point1, point2) {
        const R = 6371; // Earth's radius in km
        const dLat = this.toRadians(point2[0] - point1[0]);
        const dLng = this.toRadians(point2[1] - point1[1]);
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(this.toRadians(point1[0])) *
                Math.cos(this.toRadians(point2[0])) *
                Math.sin(dLng / 2) *
                Math.sin(dLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    },

    /**
     * Convert degrees to radians
     * @param {number} degrees - Degrees
     * @returns {number} Radians
     */
    toRadians(degrees) {
        return degrees * (Math.PI / 180);
    },

    /**
     * Format coordinates for display
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @param {number} precision - Decimal precision
     * @returns {string} Formatted coordinates
     */
    formatCoordinates(lat, lng, precision = 6) {
        return `${lat.toFixed(precision)}, ${lng.toFixed(precision)}`;
    },

    /**
     * Check if device is mobile
     * @returns {boolean} Is mobile device
     */
    isMobile() {
        return window.innerWidth <= 768;
    },

    /**
     * Check if device supports touch
     * @returns {boolean} Supports touch
     */
    isTouchDevice() {
        return "ontouchstart" in window || navigator.maxTouchPoints > 0;
    },

    /**
     * Log performance metrics
     */
    logPerformance() {
        if ("performance" in window && window.performance.timing) {
            const timing = window.performance.timing;
            const loadTime = timing.loadEventEnd - timing.navigationStart;
            const domReadyTime =
                timing.domContentLoadedEventEnd - timing.navigationStart;

            console.group("🚀 Performance Metrics");
            console.log(`Page Load Time: ${loadTime}ms`);
            console.log(`DOM Ready Time: ${domReadyTime}ms`);
            console.log(
                `DNS Lookup: ${
                    timing.domainLookupEnd - timing.domainLookupStart
                }ms`
            );
            console.log(
                `TCP Connection: ${timing.connectEnd - timing.connectStart}ms`
            );
            console.groupEnd();
        }
    },

    /**
     * Initialize performance monitoring
     */
    initPerformanceMonitoring() {
        // Monitor long tasks
        if ("PerformanceObserver" in window) {
            try {
                const observer = new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        if (entry.duration > 50) {
                            console.warn(
                                `Long Task detected: ${entry.duration}ms`
                            );
                        }
                    });
                });
                observer.observe({ entryTypes: ["longtask"] });
            } catch (err) {
                console.warn("Performance monitoring not available:", err);
            }
        }
    },
};

// Export utilities to global scope
window.GISUtils = GISUtils;
