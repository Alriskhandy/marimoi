<!DOCTYPE html>
<html lang="id">

<head>
    <!-- Meta -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'MARIMOI - Sistem Informasi Manajemen Akselerasi Infrastruktur' }}</title>
    <meta name="google-site-verification" content="6RZF3ryk7c1bQytWoY25iwHocGbgi7eHi5j9-Y0u0E8" />
    
    <!-- Primary Meta Tags -->
    <meta name="title"
        content="MARIMOI - Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi wilayah">
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
    <meta property="og:image:alt" content="MARIMOI - Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi wilayah">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="id_ID">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@MalukuUtaraProv">
    <meta name="twitter:creator" content="@MalukuUtaraProv">
    <meta name="twitter:title" content="MARIMOI - Manajemen Akselerasi Infrastruktur Untuk Monitoring dan Integrasi wilayah">
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
            <a href="/" class="logo">
                <img src="{{ asset('frontend/img/logo/logo-white.png') }}" alt="MARIMOI Logo">
                <span class="logo-brand">MARIMOI</span>
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
                            class="{{ request()->routeIs('tampil.reformer') ? 'active' : '' }}">Profil Reformer</a></li>
                    <li><a href="{{ route('peta.v2') }}"
                            class="{{ request()->routeIs('peta.v2') ? 'active' : '' }}">Peta</a></li>
                    <li><a href="{{ route('tampil.prioritas') }}"
                            class="{{ request()->routeIs('tampil.prioritas') ? 'active' : '' }}">Prioritas Daerah</a></li>
                    <li><a href="{{ route('tampil.publikasi') }}"
                            class="{{ request()->routeIs('tampil.publikasi') ? 'active' : '' }}">Publikasi</a></li>
                    <li><a href="{{ route('tampil.aspirasi') }}"
                            class="{{ request()->routeIs('tampil.aspirasi') ? 'active' : '' }}">Aspirasi</a></li>
                </ul>
            </div>
        </div>
        <!-- Right spacer — same width as Navbar.vue control buttons (4×44px) -->
        <div class="nav-spacer" aria-hidden="true"></div>
        <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    </nav>

    @yield('main')

    <!-- Navbar -->
    <script src="{{ asset('frontend/js/nav.js') }}"></script>

    @stack('scripts')
</body>

</html>
