@php
    $isHome = request()->routeIs('beranda');
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@hasSection('title')@yield('title')@else{{ $title ?? 'MARIMOI - Spatial Intelligence Platform Maluku Utara' }}@endif</title>
    @include('frontend.partials.seo-head')
    <link href="{{ asset('frontend/favicon_io/favicon.ico') }}" rel="icon">
    <link href="{{ asset('frontend/favicon_io/apple-touch-icon.png') }}" rel="apple-touch-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500&family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    @unless ($isHome)
        {{-- Gaya bawaan halaman lama (isi halaman) tetap dimuat; navbar & footer memakai desain baru. --}}
        <link rel="stylesheet" href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}">
        <link rel="stylesheet" href="{{ asset('frontend/css/main-dark.css') }}">
        <style>
            :root {
                --primary: #0a84ff;
                --primary-dark: #0866c4;
                --bg-primary: #061522;
                --bg-secondary: #071a2d;
            }
        </style>
    @endunless
    @vite(['resources/css/spatial.css', 'resources/js/spatial.js'])
    @stack('head')
    @stack('styles')
</head>

<body class="overflow-x-hidden bg-mist font-manrope text-base leading-relaxed text-slate-900 antialiased">
    @include('frontend.partials.spatial-nav')

    <main>
        @unless ($isHome)
            @hasSection('no-hero')
            @else
                @include('frontend.partials.page-hero')
            @endif
        @endunless
        @yield('main')
    </main>

    @hasSection('no-footer')
    @else
        @include('frontend.partials.spatial-footer')
    @endif

    @stack('scripts')
</body>

</html>
