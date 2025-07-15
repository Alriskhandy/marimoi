@extends('frontend.layouts.main')

@push('styles')
    <style>
        body {
            background: linear-gradient(to bottom, #ddf1ff, #f2faff);
        }
    </style>
@endpush

@section('main')
    @include('frontend.partials.nav-map')

    <!-- Document Section -->
    <section class="section mx-auto p-0 shadow-md" style="max-width: 600px; height: calc(100vh - 58px); overflow-y: auto;">
        <img src="{{ asset('frontend/img/prioritas/prioritas-1.jpg') }}" class="d-block w-100" alt="Image 1">
        <img src="{{ asset('frontend/img/prioritas/prioritas-2.jpg') }}" class="d-block w-100" alt="Image 2">
        <img src="{{ asset('frontend/img/prioritas/prioritas-3.jpg') }}" class="d-block w-100" alt="Image 3">
        <img src="{{ asset('frontend/img/prioritas/prioritas-4.jpg') }}" class="d-block w-100" alt="Image 4">
    </section><!-- /Document Section -->
@endsection
