@extends('frontend.layouts.dark')

@push('styles')
    <!-- Tailwind CSS via Vite -->
    @vite(['resources/css/app.css'])

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <!-- Wavify CSS -->
    <style>
        .wave-canvas {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 120px;
        }

        .wave-layer-1 {
            z-index: 5;
        }

        .wave-layer-2 {
            z-index: 4;
        }

        .wave-layer-3 {
            z-index: 3;
        }
    </style>
@endpush

@section('main')
    <!-- Hero Section -->
    @include('frontend.pages.index-section.hero')

    <!-- Running Text -->
    @include('frontend.pages.index-section.running-text')

    <!-- Peta Tematik -->
    @include('frontend.pages.index-section.peta-tematik')

    <!-- Fitur Utama -->
    @include('frontend.pages.index-section.fitur-utama')

    @include('frontend.pages.index-section.indikator-pembangunan')

    <!-- Aspirasi -->
    @include('frontend.pages.index-section.aspirasi')

    <!-- About -->
    @include('frontend.pages.index-section.about')

    <!-- Logo Section -->
    @include('frontend.pages.index-section.logo-section')

    <!-- Footer -->
    @include('frontend.partials.footer-dark-tailwind')
@endsection

@push('scripts')
    <!-- Vite JavaScript -->
    @vite(['resources/js/app.js'])

    <!-- Model Viewer for 3D Model -->
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>

    <!-- Swiper JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- TweenMax (required for Wavify) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/2.1.3/TweenMax.min.js"></script>

    <!-- Wavify Library -->
    <script src="{{ asset('frontend/js/wavify.js') }}"></script>

    <!-- Main Dark JavaScript (includes IndikatorModule) -->
    <script src="{{ asset('frontend/js/main-dark.js') }}"></script>
@endpush
