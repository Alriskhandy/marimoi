@push('styles')
    <style>
        /* Dropdown styling */
        .dropdown {
            position: relative;
        }

        .dropdown-toggle {
            cursor: pointer;
            user-select: none;
            position: relative;
        }

        .dropdown-toggle::after {
            content: "";
            display: inline-block;
            margin-left: 8px;
            vertical-align: middle;
            border-top: 4px solid;
            border-right: 4px solid transparent;
            border-bottom: 0;
            border-left: 4px solid transparent;
            transition: transform 0.3s ease;
        }

        .dropdown.show .dropdown-toggle::after {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 300px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            z-index: 1001;
            overflow: hidden;
            border: 1px solid #e9ecef;
            margin-top: 8px;
            padding: 8px 0;
        }

        .dropdown.show .dropdown-menu {
            display: block;
            animation: slideDown 0.3s ease;
        }

        .dropdown-menu a {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            border-radius: 8px;
            margin: 4px 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
            position: relative;
        }

        .dropdown-menu a:hover {
            background: #f8f9fa;
            color: #007bff;
            transform: translateX(3px);
        }

        .dropdown-menu .menu-icon {
            width: 48px;
            height: 48px;
            margin-right: 12px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            background: #f8f9fa;
        }

        .dropdown-menu .menu-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }

        .dropdown-menu .menu-text {
            display: flex;
            flex-direction: column;
            flex: 1;
            text-align: left;
        }

        .dropdown-menu .menu-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
            color: #333;
        }

        .dropdown-menu .menu-subtitle {
            font-size: 12px;
            color: #666;
            font-weight: normal;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive dropdown untuk mobile */
        @media (max-width: 1199px) {
            .dropdown-menu {
                position: static;
                display: none;
                box-shadow: none;
                background: #f8f9fa;
                margin: 0;
                border: none;
                border-radius: 0;
                padding: 0;
                min-width: auto;
                z-index: auto;
            }

            .dropdown.show .dropdown-menu {
                display: block;
                animation: none;
            }

            .dropdown-menu a {
                margin: 0;
                padding: 12px 40px;
                border-radius: 0;
                background: #f8f9fa;
            }

            .dropdown-menu a:hover {
                background: #e9ecef;
                transform: none;
            }

            .dropdown-menu .menu-icon {
                width: 40px;
                height: 40px;
            }

            .dropdown-menu .menu-title {
                font-size: 13px;
            }

            .dropdown-menu .menu-subtitle {
                font-size: 11px;
            }

            /* Pastikan dropdown bisa diklik di mobile */
            .dropdown-toggle {
                pointer-events: auto;
                touch-action: manipulation;
            }
        }
    </style>
@endpush

<script>
    function toggleDropdown(event, element) {
        event.preventDefault();
        event.stopPropagation();

        // Tutup semua dropdown yang terbuka
        const allDropdowns = document.querySelectorAll(".dropdown");
        const currentDropdown = element.closest(".dropdown");

        allDropdowns.forEach((dropdown) => {
            if (dropdown !== currentDropdown) {
                dropdown.classList.remove("show");
            }
        });

        // Toggle dropdown saat ini
        currentDropdown.classList.toggle("show");
    }

    // Tutup dropdown ketika klik di luar (tapi tidak di mobile nav)
    document.addEventListener("click", function(event) {
        // Jangan tutup dropdown jika klik di dalam mobile nav
        if (window.innerWidth <= 1199) {
            const navmenu = event.target.closest("#navmenu");
            if (navmenu) return; // Abaikan klik di dalam navmenu pada mobile
        }

        const dropdowns = document.querySelectorAll(".dropdown");
        const clickedInsideDropdown = event.target.closest(".dropdown");

        if (!clickedInsideDropdown) {
            dropdowns.forEach((dropdown) => {
                dropdown.classList.remove("show");
            });
        }
    });

    // Tutup dropdown ketika ESC ditekan
    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape") {
            const dropdowns = document.querySelectorAll(".dropdown");
            dropdowns.forEach((dropdown) => {
                dropdown.classList.remove("show");
            });
        }
    });

    // Tambahkan function untuk mobile nav toggle jika diperlukan
    function toggleMobileNav() {
        const navmenu = document.getElementById("navmenu");
        navmenu.classList.toggle("active");

        // Tutup semua dropdown saat mobile nav dibuka/ditutup
        const dropdowns = document.querySelectorAll(".dropdown");
        dropdowns.forEach((dropdown) => {
            dropdown.classList.remove("show");
        });
    }

    // Handle touch events untuk mobile
    document.addEventListener("DOMContentLoaded", function() {
        const dropdownToggles = document.querySelectorAll(".dropdown-toggle");

        dropdownToggles.forEach((toggle) => {
            // Tambahkan touch event untuk mobile
            toggle.addEventListener(
                "touchstart",
                function(e) {
                    e.preventDefault();
                    toggleDropdown(e, this);
                }, {
                    passive: false,
                }
            );
        });
    });
</script>
<header id="header" class="header d-flex align-items-center shadow-lg border-bottom border-dark">
    <div class="container-fluid container-xl d-flex align-items-center py-2 py-md-0">
        <a href="{{ route('beranda') }}" class="logo d-flex align-items-center me-auto">
            <img src="{{ asset('frontend/img/logo.webp') }}" alt="Logo Bappeda" style="height: 24px; margin-right: 8px" />
            <h1 class="sitename fs-5 fs-md-1">MARIMOI</h1>
        </a>

        <nav id="navmenu" class="navmenu">
            <ul>
                <li>
                    <a href="{{ route('beranda') }}"
                        class="{{ request()->routeIs('beranda') ? 'active' : '' }}">Beranda</a>
                </li>

                <!-- Dropdown: Proyek Strategis -->
                <li class="dropdown">
                    <a href="#"
                        class="dropdown-toggle {{ request()->routeIs(['tampil.psd', 'tampil.psn']) ? 'active' : '' }}"
                        onclick="toggleDropdown(event, this)">
                        Proyek Strategis
                    </a>
                    <div class="dropdown-menu">
                        <a href="{{ route('tampil.psd') }}"
                            class="{{ request()->routeIs('tampil.psd') ? 'active' : '' }}">
                            <div class="menu-icon">
                                <img src="{{ asset('frontend/img/daerah.svg') }}" alt="Proyek Strategis Daerah" />
                            </div>
                            <div class="menu-text">
                                <div class="menu-title">
                                    Proyek Strategis Daerah
                                </div>
                                <div class="menu-subtitle">
                                    Program pembangunan daerah prioritas
                                </div>
                            </div>
                        </a>
                        <a href="{{ route('tampil.psn') }}"
                            class="{{ request()->routeIs('tampil.psn') ? 'active' : '' }}">
                            <div class="menu-icon">
                                <img src="{{ asset('frontend/img/nasional.svg') }}" alt="Proyek Strategis Nasional" />
                            </div>
                            <div class="menu-text">
                                <div class="menu-title">
                                    Proyek Strategis Nasional
                                </div>
                                <div class="menu-subtitle">
                                    Program prioritas pemerintah pusat
                                </div>
                            </div>
                        </a>
                    </div>
                </li>

                <li>
                    <a href="{{ route('tampil.prioritas') }}"
                        class="{{ request()->routeIs('tampil.prioritas') ? 'active' : '' }}">Prioritas Daerah
                        2025-2029</a>
                </li>

                {{-- Aktifkan Jika RPJMD sudah Siap --}}
                
                <li>
                    <a
                        href="{{ route('tampil.rpjmd') }}"
                        class="{{ request()->routeIs('tampil.rpjmd') ? 'active' : '' }}"
                        >RPJMD</a
                    >
                </li>
               

                <li>
                    <a href="{{ route('tampil.musrenbang') }}"
                        class="{{ request()->routeIs('tampil.musrenbang') ? 'active' : '' }}">Musrenbang</a>
                </li>
                <li>
                    <a href="{{ route('tampil.pokir') }}"
                        class="{{ request()->routeIs('tampil.pokir') ? 'active' : '' }}">Pokir DPRD</a>
                </li>
                {{-- <li>
                    <a href="{{ route('tampil.aspirasi') }}"
                        class="{{ request()->routeIs('tampil.aspirasi') ? 'active' : '' }}">Aspirasi</a>
                </li> --}}
            </ul>

            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>

        <a class="btn-getstarted" href="{{ route('login') }}">Login</a>
    </div>
</header>