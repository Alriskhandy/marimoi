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
    <!-- Android/Chrome -->

    <!-- Vendor CSS Files -->
    <link href="{{ asset('frontend/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

    <!-- Main CSS Files -->
    <link href="{{ asset('frontend/css/app.css') }}" rel="stylesheet" />

    <!-- Stack CSS Files -->
    @stack('styles')
</head>

<body>
    <!-- Navigation -->
    @include('frontend.partials.navbar')

    <!-- Main Content -->
    @yield('main')

    <!-- Vendor JS Files -->
    <script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Main JS File -->
    <script src="{{ asset('frontend/js/app.js') }}"></script>

    <!-- Stack JS File -->
    @stack('scripts')
</body>

</html>
