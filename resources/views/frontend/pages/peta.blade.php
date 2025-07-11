@extends('frontend.layouts.main')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        .sidebar {
            position: absolute;
            top: 60px;
            /* Sesuaikan dengan tinggi header */
            right: 20px;
            /* Jarak dari kanan */
            width: 250px;
            /* Lebar sidebar */
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            z-index: 99;
            /* Pastikan sidebar di atas peta */
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .sidebar.d-none {
            opacity: 0;
            visibility: hidden;
        }

        #map {
            position: relative;
            z-index: 1;
        }
    </style>
@endpush

@section('main')
    <!-- Page Title -->
    <div class="page-title dark-background" data-aos="fade" style="background-image: url(frontend/img/page-title-bg.jpg);">
        <div class="container position-relative">
            <h1>PETA SIG IT</h1>
            <p>Esse dolorum voluptatum ullam est sint nemo et est ipsa porro placeat quibusdam quia assumenda numquam
                molestias.</p>
            <nav class="breadcrumbs">
                <ol>
                    <li><a href="{{ route('beranda') }}">Home</a></li>
                    <li class="current">Peta SIG</li>
                </ol>
            </nav>
        </div>
    </div><!-- End Page Title -->

    <!-- Map Section -->
    <section class="section">
        <div class="container-fluid p-0" data-aos="fade-up" data-aos-delay="100" style="height: calc(100vh - 160px);">
            <div class="position-relative" style="height: 100%;">
                <!-- Sidebar Layer -->
                <div id="sidebar-layer" class="sidebar bg-white text-dark position-absolute"
                    style="width: 320px; padding: 15px; overflow-y: auto; top: 0; left: 0; height: 100%; display: none; margin-left: 0; margin-right: 0;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Layer</h5>
                        <button id="btn-close-sidebar-layer" class="btn btn-secondary btn-sm">×</button>
                    </div>
                    <div class="mb-3">
                        <label for="transparency" class="form-label">Transparansi Layer</label>
                        <input type="range" class="form-range" min="0" max="100" value="100"
                            id="transparency">
                    </div>
                    <div class="mb-3">
                        <input type="text" id="layer-search" class="form-control"
                            placeholder="Masukkan Kata Kunci Pencarian Layer">
                    </div>
                    <div id="layer-list" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                        <!-- Layer list will be populated dynamically -->
                    </div>
                </div>

                <!-- Sidebar Basemap -->
                <div id="sidebar-basemap" class="sidebar bg-white text-dark position-absolute"
                    style="width: 320px; padding: 15px; overflow-y: auto; top: 0; left: 0; height: 100%; display: none; margin-left: 0; margin-right: 0;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Basemap</h5>
                        <button id="btn-close-sidebar-basemap" class="btn btn-secondary btn-sm">×</button>
                    </div>
                    <div id="basemap-list" class="px-1" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                        <!-- Basemap options will be populated dynamically -->
                        <p>Basemap options placeholder</p>
                    </div>
                </div>

                <!-- Sidebar Legend -->
                <div id="sidebar-legend" class="sidebar bg-white text-dark position-absolute"
                    style="width: 320px; padding: 15px; overflow-y: auto; top: 0; left: 0; height: 100%; display: none; margin-left: 0; margin-right: 0;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Legenda</h5>
                        <button id="btn-close-sidebar-legend" class="btn btn-secondary btn-sm">×</button>
                    </div>
                    <div id="legend-content" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                        <!-- Legend content will be populated dynamically -->
                        <p>Legend content placeholder</p>
                    </div>
                </div>

                <!-- Map -->
                <div id="map" class="rounded" style="z-index: 10; height: 100%; width: 100%;"></div>

                <!-- Sidebar Control Buttons -->
                <div id="sidebar-control-buttons" class="btn-group position-absolute" role="group"
                    aria-label="Sidebar Control Buttons"
                    style="top: 10px; right: 10px; z-index: 1100; background-color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.15); border-radius: 4px;">
                    <button id="btn-toggle-sidebar-layer" type="button" class="btn btn-outline-dark btn-sm"
                        title="Layer Peta" style="color: black;" data-bs-toggle="tooltip" data-bs-placement="bottom"
                        data-bs-title="Layer Peta">
                        <i class="bi bi-layers-fill"></i>
                    </button>
                    <button id="btn-toggle-sidebar-basemap" type="button" class="btn btn-outline-dark btn-sm"
                        title="Basemap Peta" style="color: black;" data-bs-toggle="tooltip" data-bs-placement="bottom"
                        data-bs-title="Basemap Peta">
                        <i class="bi bi-map-fill"></i>
                    </button>
                    <button id="btn-toggle-sidebar-legend" type="button" class="btn btn-outline-dark btn-sm"
                        title="Legenda Peta" style="color: black;" data-bs-toggle="tooltip" data-bs-placement="bottom"
                        data-bs-title="Legenda Peta">
                        <i class="bi bi-list-ul"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
    <!-- /Map Section -->
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script type="module" src="{{ asset('frontend/js/map-app.js') }}"></script>
@endpush
