@extends('frontend.layouts.main')

@push('styles')
    <style>
        body {
            background: linear-gradient(to bottom, #ddf1ff, #f2faff);
        }
        /* New styles for horizontal image scroll on desktop */
        @media (min-width: 768px) {
            .horizontal-scroll-section {
                max-width: 100%;
                height: calc(100vh - 58px);
                overflow-x: auto;
                overflow-y: hidden;
                white-space: nowrap;
                display: flex;
                flex-direction: row;
                align-items: center;
                padding: 0;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                margin: 0 auto;
            }
            .horizontal-scroll-section img {
                display: inline-block;
                height: 95vh;
                width: auto;
                object-fit: contain;
            }
        }
        /* For smaller screens, keep vertical scroll */
        @media (max-width: 767px) {
            .horizontal-scroll-section {
                max-width: 600px;
                height: calc(100vh - 58px);
                overflow-y: auto;
                overflow-x: hidden;
                display: block;
                padding: 0;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                margin: 0 auto;
            }
            .horizontal-scroll-section img {
                display: block;
                width: 100%;
                height: auto;
            }
        }
    </style>
@endpush

@section('main')
    @include('frontend.partials.nav-map')

    <!-- Document Section -->
    <section class="horizontal-scroll-section section mx-auto p-0 shadow-md">
        <img src="{{ asset('frontend/img/prioritas/prioritas-1.jpg') }}" alt="Image 1">
        <img src="{{ asset('frontend/img/prioritas/prioritas-2.jpg') }}" alt="Image 2">
        <img src="{{ asset('frontend/img/prioritas/prioritas-3.jpg') }}" alt="Image 3">
        <img src="{{ asset('frontend/img/prioritas/prioritas-4.jpg') }}" alt="Image 4">
    </section><!-- /Document Section -->
@endsection
