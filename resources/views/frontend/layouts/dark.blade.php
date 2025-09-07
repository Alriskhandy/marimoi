<!DOCTYPE html>
<html lang="id">

<head>
    <!-- Meta -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'MARIMOI - Sistem Informasi Manajemen Akselerasi Infrastruktur' }}</title>

    <meta name="description"
        content="MARIMOI adalah sistem digital terpadu untuk memperkuat koordinasi, pemantauan, dan integrasi pembangunan infrastruktur di Maluku Utara. Dengan pendekatan spasial dan peta tematik, sistem ini menyediakan data real-time yang mendukung perencanaan lintas sektor secara kolaboratif dan transparan. Platform ini mempercepat perencanaan, mendorong partisipasi publik, dan meningkatkan akuntabilitas, menjadi bagian dari transformasi digital berbasis data dan kebutuhan lokal.">
    <meta name="keywords"
        content="Sistem Informasi, Akselerasi Infrastruktur, Manajemen Proyek, Monitoring Infrastruktur, Peta Proyek, Pemerintahan Digital, MARIMOI, Sistem Infrastruktur, Pembangunan Daerah, WebGIS">

    <!-- Favicons -->
    <link href="{{ asset('frontend/favicon_io/favicon.ico') }}" rel="icon" type="image/webp">
    <link href="{{ asset('frontend/favicon_io/apple-touch-icon.png') }}" rel="apple-touch-icon">
    <link href="{{ asset('frontend/favicon_io/favicon-32x32.png') }}" rel="icon" sizes="32x32">
    <link href="{{ asset('frontend/favicon_io/favicon-16x16.png') }}" rel="icon" sizes="16x16">
    <link href="{{ asset('frontend/favicon_io/android-chrome-192x192.png') }}" rel="icon" sizes="192x192">
    <!-- Android/Chrome -->
    <link href="{{ asset('frontend/favicon_io/android-chrome-512x512.png') }}" rel="icon" sizes="512x512">

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Main CSS For Global & Navbar -->
    <link rel="stylesheet" href="{{ asset('frontend/css/main-dark.css') }}">

    @stack('styles')
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="nav-content">
            <a href="/" class="logo"
                style="display: flex; justify-content: center; align-items: center; text-decoration: none; color: inherit;">
                <img src="{{ asset('frontend/img/logo/logo-white.png') }}" alt="MARIMOI Logo">
            </a>
            <button class="mobile-menu-button" id="mobileMenuButton" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-menu" id="navMenu">
                <ul class="menu-list">
                    <li><a href="{{ route('beranda') }}"
                            class="{{ request()->routeIs('beranda') ? 'active' : '' }}">Beranda</a></li>
                    <li><a href="{{ route('tampil.tematik') }}"
                            class="{{ request()->routeIs('tampil.tematik') ? 'active' : '' }}">Peta
                            Tematik</a></li>
                    <li class="dropdown">
                        @php
                            $isDropdownActive = request()->routeIs('tampil.psd') || request()->routeIs('tampil.psn');
                        @endphp
                        <a href="#" class="dropdown-trigger {{ $isDropdownActive ? 'active' : '' }}"
                            aria-haspopup="true" aria-expanded="false" role="button">
                            Proyek Strategis
                            <span class="arrow">▼</span>
                        </a>
                        <ul class="dropdown-menu" role="menu" aria-label="Proyek Strategis Menu">
                            <li role="none"><a href="{{ route('tampil.psn') }}" role="menuitem"
                                    class="{{ request()->routeIs('tampil.psd') ? 'active' : '' }}">Proyek Strategis
                                    Daerah</a></li>
                            <li role="none"><a href="{{ route('tampil.psn') }}" role="menuitem"
                                    class="{{ request()->routeIs('tampil.psn') ? 'active' : '' }}">Proyek Strategis
                                    Nasional</a></li>
                        </ul>
                    </li>
                    <li><a href="{{ route('tampil.prioritas') }}"
                            class="{{ request()->routeIs('tampil.prioritas') ? 'active' : '' }}">Prioritas Daerah
                            2025-2029</a></li>
                    <li><a href="{{ route('tampil.musrenbang') }}"
                            class="{{ request()->routeIs('tampil.musrenbang') ? 'active' : '' }}">Musrenbang</a></li>
                    <li><a href="{{ route('tampil.pokir') }}"
                            class="{{ request()->routeIs('tampil.pokir') ? 'active' : '' }}">Pokir
                            DPRD</a></li>
                    <li><a href="{{ route('tampil.aspirasi') }}"
                            class="{{ request()->routeIs('tampil.aspirasi') ? 'active' : '' }}">Aspirasi</a></li>
                </ul>
            </div>
        </div>
        <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    </nav>

    @yield('main')

    @stack('scripts')

    <!-- Main Application Script (Clean & Organized) -->
    <script src="{{ asset('frontend/js/main-dark.js') }}"></script>
</body>

</html>
