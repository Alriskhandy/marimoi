<!DOCTYPE html>
<html lang="id">

<head>
    <!-- Meta -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>
        MARIMOI - Sistem Informasi Manajemen Akselerasi Infrastruktur
    </title>
    <meta name="description" content="">
    <meta name="keywords" content="">

    <!-- Favicons -->
    <link href="{{ asset('frontend/favicon/favicon.ico') }}" rel="icon" type="image/webp">
    <link href="{{ asset('frontend/favicon/apple-touch-icon.png') }}" rel="apple-touch-icon">
    <link href="{{ asset('frontend/favicon/favicon-32x32.png') }}" rel="icon" sizes="32x32">
    <link href="{{ asset('frontend/favicon/favicon-16x16.png') }}" rel="icon" sizes="16x16">
    <link href="{{ asset('frontend/favicon/android-chrome-192x192.png') }}" rel="icon" sizes="192x192"> <!-- Android/Chrome -->
    <link href="{{ asset('frontend/favicon/android-chrome-512x512.png') }}" rel="icon" sizes="512x512"> <!-- Android/Chrome -->

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

    <!-- Hero Section -->
    @include('frontend.partials.hero')

    <!-- Main Content -->
    @yield('main')

    <!-- Footer -->
    @include('frontend.partials.footer')

    <!-- Vendor JS Files -->
    <script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Main JS File -->
    <script src="{{ asset('frontend/js/app.js') }}"></script>

    <!-- Stack JS File -->
    @stack('scripts')
</body>

</html>
