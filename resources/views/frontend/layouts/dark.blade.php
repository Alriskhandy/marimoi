<!DOCTYPE html>
<html lang="id">

<head>
    <!-- Meta -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'MARIMOI - Sistem Informasi Manajemen Akselerasi Infrastruktur' }}</title>
    <meta name="google-site-verification" content="6RZF3ryk7c1bQytWoY25iwHocGbgi7eHi5j9-Y0u0E8" />

    <!-- Primary Meta Tags -->
    <meta name="title" content="MARIMOI - Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi wilayah">
    <meta name="description"
        content="MARIMOI adalah sistem digital terpadu berbasi web yang dikembangkan untuk mendukung perencanaan, pelaksanaan, pemantauan, dan evaluasi pembangunan infrastruktru daerah secara lebih efektif, partisipatif, dan terintegrasi. Sistem ini menyasar penguatan sinergi lintas sektor dan wilayah dalam mendukung pembangunan wilayah Provinsi Maluku Utara.">
    <meta name="keywords"
        content="MARIMOI,marimoi,bappeda malut, sistem informasi infrastruktur, akselerasi infrastruktur maluku utara, manajemen proyek digital, monitoring infrastruktur, peta proyek, pemerintahan digital, sistem infrastruktur, pembangunan daerah, WebGIS, koordinasi pembangunan, perencanaan infrastruktur, transformasi digital, data real-time infrastruktur, maluku utara">
    <meta name="author" content="Pemerintah Provinsi Maluku Utara">
    <meta name="robots" content="index, follow">
    <meta name="language" content="Indonesian">
    <meta name="revisit-after" content="1 days">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="MARIMOI - BAPPEDA Maluku Utara">
    <meta property="og:title"
        content="MARIMOI - Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi wilayah">
    <meta property="og:description"
        content="MARIMOI adalah sistem digital terpadu berbasi web yang dikembangkan untuk mendukung perencanaan, pelaksanaan, pemantauan, dan evaluasi pembangunan infrastruktru daerah secara lebih efektif, partisipatif, dan terintegrasi. Sistem ini menyasar penguatan sinergi lintas sektor dan wilayah dalam mendukung pembangunan wilayah Provinsi Maluku Utara.">
    <meta property="og:image" content="{{ asset('frontend/img/index-marimoi.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt"
        content="MARIMOI - Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi wilayah">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="id_ID">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@MalukuUtaraProv">
    <meta name="twitter:creator" content="@MalukuUtaraProv">
    <meta name="twitter:title"
        content="MARIMOI - Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi wilayah">
    <meta name="twitter:description"
        content="MARIMOI adalah sistem digital terpadu berbasi web yang dikembangkan untuk mendukung perencanaan, pelaksanaan, pemantauan, dan evaluasi pembangunan infrastruktru daerah secara lebih efektif, partisipatif, dan terintegrasi. Sistem ini menyasar penguatan sinergi lintas sektor dan wilayah dalam mendukung pembangunan wilayah Provinsi Maluku Utara.">
    <meta name="twitter:image" content="{{ asset('frontend/img/index-marimoi.png') }}">
    <meta name="twitter:image:alt" content="MARIMOI - Sistem Digital Infrastruktur">

    <!-- Geographic Meta Tags -->
    <meta name="geo.region" content="ID-MU">
    <meta name="geo.placename" content="Maluku Utara">
    <meta name="geo.position" content="1.5709;127.8084">
    <meta name="ICBM" content="1.5709, 127.8084">

    <!-- Additional SEO Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="msapplication-TileColor" content="#2563eb">
    <meta name="application-name" content="MARIMOI">
    <meta name="apple-mobile-web-app-title" content="MARIMOI">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <!-- Structured Data for Government Organization -->
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "GovernmentOrganization",
  "name": "MARIMOI",
  "alternateName": "Sistem Informasi Manajemen Akselerasi Infrastruktur",
  "description": "Sistem digital terpadu BAPPEDA Provinsi Maluku Utara untuk memperkuat koordinasi, pemantauan, dan integrasi pembangunan infrastruktur",
  "url": "{{ url()->current() }}",
  "logo": "{{ asset('frontend/img/logo/logo-dark.png') }}",
  "parentOrganization": {
    "@type": "GovernmentOrganization",
    "name": "BAPPEDA Provinsi Maluku Utara",
    "alternateName": "Badan Perencanaan Pembangunan Daerah Provinsi Maluku Utara"
  },
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Ternate",
    "addressRegion": "Maluku Utara",
    "addressCountry": "Indonesia"
  },
  "areaServed": {
    "@type": "State",
    "name": "Maluku Utara"
  },
  "serviceType": [
    "Sistem Informasi Infrastruktur",
    "Monitoring Pembangunan",
    "Perencanaan Digital",
    "WebGIS",
    "Perencanaan Pembangunan Daerah"
  ]
}
</script>

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">



    <!-- Favicons -->
    <link href="{{ asset('frontend/favicon_io/favicon.ico') }}" rel="icon" type="image/webp">
    <link href="{{ asset('frontend/favicon_io/apple-touch-icon.png') }}" rel="apple-touch-icon">
    <link href="{{ asset('frontend/favicon_io/favicon-32x32.png') }}" rel="icon" sizes="32x32">
    <link href="{{ asset('frontend/favicon_io/favicon-16x16.png') }}" rel="icon" sizes="16x16">
    <link href="{{ asset('frontend/favicon_io/android-chrome-192x192.png') }}" rel="icon" sizes="192x192">
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
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
                    <li><a href="{{ route('tampil.reformer') }}"
                            class="{{ request()->routeIs('tampil.reformer') ? 'active' : '' }}">Profil Reformer</a>
                    </li>
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
                            <span class="arrow"><i class="bi bi-chevron-down ms-2"></i></span>
                        </a>
                        <ul class="dropdown-menu" role="menu" aria-label="Proyek Strategis Menu">
                            <li role="none"><a href="{{ route('tampil.psd') }}" role="menuitem"
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
                    {{-- <li class="dropdown">
                        @php
                            $isDropdownActive =
                                request()->routeIs('tampil.musrenbang') || request()->routeIs('coming_soon');
                        @endphp
                        <a href="#" class="dropdown-trigger {{ $isDropdownActive ? 'active' : '' }}"
                            aria-haspopup="true" aria-expanded="false" role="button">
                            Musrenbang
                            <span class="arrow"><i class="bi bi-chevron-down ms-2"></i></span>
                        </a>
                        <ul class="dropdown-menu" role="menu" aria-label="Musrenbang Menu">
                            <li role="none">
                                <a href="{{ route('tampil.musrenbang') }}" role="menuitem"
                                    class="{{ request()->routeIs('tampil.musrenbang') ? 'active' : '' }}">
                                    Peta Musrenbang
                                </a>
                            </li>
                            <li role="none">
                                <a href="{{ route('coming_soon_public') }}" role="menuitem"
                                    class="{{ request()->routeIs('coming_soon_public') ? 'active' : '' }}">
                                    Musrenbang Digital
                                </a>
                            </li>
                        </ul>
                    </li> --}}

                    <li><a href="{{ route('tampil.pokir') }}"
                            class="{{ request()->routeIs('tampil.pokir') ? 'active' : '' }}">Pokir
                            DPRD</a></li>
                    <li><a href="{{ route('tampil.publikasi') }}"
                            class="{{ request()->routeIs('tampil.publikasi') ? 'active' : '' }}">Publikasi</a></li>
                    <li><a href="{{ route('tampil.aspirasi') }}"
                            class="{{ request()->routeIs('tampil.aspirasi') ? 'active' : '' }}">Aspirasi</a></li>
                </ul>
            </div>
        </div>
        <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    </nav>

    @yield('main')

    <!-- Navbar -->
    <script src="{{ asset('frontend/js/nav.js') }}"></script>

    @stack('scripts')
</body>

</html>
