@extends('frontend.layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/prioritas.css') }}">
@endpush

@section('main')
    @include('frontend.partials.navbar')

    <!-- Document Section -->
    <section class="horizontal-scroll-section section mx-auto p-0 shadow-md">
        <img src="{{ asset('frontend/img/prioritas/prioritas-1.jpg') }}" alt="Image 1">
        <img src="{{ asset('frontend/img/prioritas/prioritas-2.jpg') }}" alt="Image 2">
        <img src="{{ asset('frontend/img/prioritas/prioritas-3.jpg') }}" alt="Image 3">
        <img src="{{ asset('frontend/img/prioritas/prioritas-4.jpg') }}" alt="Image 4">
    </section><!-- /Document Section -->

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const scrollContainer = document.querySelector('.horizontal-scroll-section');
                if (scrollContainer) {
                    scrollContainer.addEventListener('wheel', function(evt) {
                        evt.preventDefault();
                        scrollContainer.scrollLeft += evt.deltaY;
                    });
                }
            });
        </script>
    @endpush
@endsection
