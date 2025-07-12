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
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            z-index: 101;
            /* Pastikan sidebar di atas peta */
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .sidebar.d-none {
            opacity: 0;
            visibility: hidden;
        }

        .gradient-purple {
            background: linear-gradient(90deg, rgb(218, 140, 255), rgb(154, 85, 255));
            /* Adjust the color values to match your desired gradient */
            color: white;
            /* Ensure the text is readable */
            padding: 5px 8px;
            /* Add padding for better button size */
            border-radius: 5px;
            /* Optional: to make the corners rounded */
            display: inline-flex;
            align-items: center;
        }

        .guide-step.d-none {
            display: none;
        }

        .highlighted-control {
            position: relative;
            box-shadow: 0 0 10px 3px rgb(255, 255, 255);
            border-radius: 5px;
            z-index: 1050;
        }

        .btn-group, .btn-sm {
            border-radius: 0;
        }
        #map {
            position: relative;
            z-index: 1;
        }
    </style>
@endpush

@section('main')
    <div class="container-fluid p-0" style="height: 100vh;">

        <!-- Page Title -->
        @include('frontend.partials.nav-map')

        <!-- Map Section -->
        <section class="section pb-0" style="padding-top: 0;">
            <div class="container-fluid p-0" data-aos="fade-up" data-aos-delay="100" style="height: 93vh">
                <div class="position-relative" style="height: 100%;">

                    <!-- Modal Panduan Awal -->
                    <div class="modal fade" id="guideModal" tabindex="-1" aria-labelledby="guideModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="guideModalLabel">Panduan Penggunaan</h5>
                                </div>
                                <div class="modal-body">
                                    <div class="guide-step" data-step="1">
                                        <p>Selamat datang di WebGIS Perencanaan! Gunakan tombol-tombol kontrol untuk
                                            mengatur tampilan peta.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="2">
                                        <p>Tombol <strong><i class="bi bi-plus border p-1"></i> Zoom In & <i class="bi bi-dash border p-1"></i> Zoom Out</strong>, digunakan untuk mengatur zoom peta.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="3">
                                        <p>Gunakan tombol <strong><i class="bi bi-info-circle-fill border p-1"></i> Bantuan</strong> untuk melihat panduan ini kapan saja.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="4">
                                        <p>Tombol <strong><i class="bi bi-list-ul border p-1"></i> Legenda Peta</strong> menampilkan keterangan simbol pada peta.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="5">
                                        <p>Tombol <strong><i class="bi bi-grid-fill border p-1"></i> Basemap Peta</strong> digunakan untuk memilih jenis peta dasar.
                                        </p>
                                    </div>
                                    <div class="guide-step d-none" data-step="6">
                                        <p>Tombol <strong><i class="bi bi-layers-fill border p-1"></i> Layer Peta</strong> digunakan untuk mengatur layer yang ingin
                                            ditampilkan.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="7">
                                        <p>Tombol <strong><i class="bi bi-file-earmark-arrow-down-fill border p-1"></i> Download Peta</strong> memungkinkan Anda mengunduh peta.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="8">
                                        <p>Tombol <strong><i class="bi bi-arrows-fullscreen border p-1"></i> Fullscreen</strong> memungkinkan Anda untuk masuk dan keluar dari tampilan penuh.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="9">
                                        <p>Tombol <strong><i class="bi bi-house-door-fill border p-1"></i> Home</strong> memungkinkan Anda kembali ke default zoom dari peta.</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" id="btnSkip">Skip</button>
                                    <button type="button" class="btn btn-secondary" id="btnPrev" disabled>Prev</button>
                                    <button type="button" class="btn btn-secondary" id="btnNext">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Layer -->
                    <div id="sidebar-layer" class="sidebar bg-white text-dark position-absolute"
                        style="width: 320px; padding: 15px; overflow-y: auto; top: 0; left: 0; height: 100%; display: none; margin-left: 0; margin-right: 0;">
                        <div class="d-flex justify-content-between align-items-center mb-3 gradient-purple">
                            <h6 class="text-white mb-0">Layer</h6>
                            <button id="btn-close-sidebar-layer" class="btn btn-sm"><i
                                    class="bi bi-x-lg text-white"></i></button>
                        </div>
                        <div class="mb-3 ms-2">
                            <label for="transparency" class="form-label">Transparansi Layer</label>
                            <input type="range" class="form-range" min="0" max="100" value="100"
                                id="transparency">
                        </div>
                        <div class="mb-3 ms-2">
                            <input type="text" id="layer-search" class="form-control form-control-sm"
                                placeholder="Masukkan Kata Kunci Pencarian Layer">
                        </div>
                        <div id="layer-list" class="ms-2" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                            <!-- Layer list will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Sidebar Basemap -->
                    <div id="sidebar-basemap" class="sidebar bg-white text-dark position-absolute"
                        style="width: 320px; padding: 15px; overflow-y: auto; top: 0; left: 0; height: 100%; display: none; margin-left: 0; margin-right: 0;">
                        <div class="d-flex justify-content-between align-items-center mb-3 gradient-purple">
                            <h6 class="text-white mb-0">Basemap</h6>
                            <button id="btn-close-sidebar-basemap" class="btn btn-sm"><i
                                    class="bi bi-x-lg text-white"></i></button>
                        </div>
                        <div id="basemap-list" class="px-1 ms-2" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                            <!-- Basemap options will be populated dynamically -->
                            <p>Basemap options placeholder</p>
                        </div>
                    </div>

                    <!-- Sidebar Legend -->
                    <div id="sidebar-legend" class="sidebar bg-white text-dark position-absolute"
                        style="width: 320px; padding: 15px; overflow-y: auto; top: 0; left: 0; height: 100%; display: none; margin-left: 0; margin-right: 0;">
                        <div class="d-flex justify-content-between align-items-center mb-3 gradient-purple">
                            <h6 class="text-white mb-0">Legenda</h6>
                            <button id="btn-close-sidebar-legend" class="btn btn-sm"><i
                                    class="bi bi-x-lg text-white"></i></button>
                        </div>
                        <div id="legend-content" class="ms-2" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                            <!-- Legend content will be populated dynamically -->
                            <p>Legend content placeholder</p>
                        </div>
                    </div>

                    <!-- Sidebar Download Map -->
                    <div id="sidebar-download" class="sidebar bg-white text-dark position-absolute"
                        style="width: 320px; padding: 15px; overflow-y: auto; top: 0; left: 0; height: 100%; display: none; margin-left: 0; margin-right: 0;">
                        <div class="d-flex justify-content-between align-items-center mb-3 gradient-purple">
                            <h6 class="text-white mb-0">Download Peta</h6>
                            <button id="btn-close-sidebar-download" class="btn btn-sm"><i
                                    class="bi bi-x-lg text-white"></i></button>
                        </div>
                        <div id="download-content" class="ms-2" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                            <!-- Legend content will be populated dynamically -->
                            <p>Daftar Peta Yang Bisa di Download</p>
                        </div>
                    </div>

                    <!-- Sidebar Control Buttons -->
                    <div id="sidebar-control-buttons" class="btn-group position-absolute" role="group"
                        aria-label="Sidebar Control Buttons"
                        style="top: 10px; right: 10px; z-index: 99; background-color: rgb(90, 90, 90); box-shadow: 0 4px 10px rgba(0,0,0,0.15); display: flex; flex-direction: column; align-items: center;">
                        <button id="btn-toggle-sidebar-help" type="button" class="btn btn-sm border-white"
                            title="Bantuan" style="color: white;" data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="Bantuan">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                        <button id="btn-toggle-sidebar-legend" type="button" class="btn btn-sm border-white"
                            title="Legenda Peta" style="color: white;" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Legenda Peta">
                            <i class="bi bi-list-ul"></i>
                        </button>
                        <button id="btn-toggle-sidebar-basemap" type="button" class="btn btn-sm border-white"
                            title="Basemap Peta" style="color: white;" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Basemap Peta">
                            <i class="bi bi-grid-fill"></i>
                        </button>
                        <button id="btn-toggle-sidebar-layer" type="button" class="btn btn-sm border-white"
                            title="Layer Peta" style="color: white;" data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="Layer Peta">
                            <i class="bi bi-layers-fill"></i>
                        </button>
                    </div>

                    <!-- Navigation Control Buttons -->
                    <div id="nav-control-buttons" class="btn-group position-absolute" role="group"
                        aria-label="Sidebar Control Buttons"
                        style="bottom: 30px; right: 10px; z-index: 99; background-color: rgb(90, 90, 90); box-shadow: 0 4px 10px rgba(0,0,0,0.15); display: flex; flex-direction: column; align-items: center;">
                        <button id="btn-toggle-sidebar-download" type="button" class="btn btn-sm border-white"
                            title="Download Peta" style="color: white;" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Download Peta">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i>
                        </button>
                        <button id="btn-fullscreen" type="button" class="btn btn-sm border-white"
                            title="Tampilan Penuh" style="color: white;" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Tampilan Penuh">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <button id="btn-default-zoom" type="button" class="btn btn-sm border-white"
                            title="Default Zoom" style="color: white;" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Default Zoom">
                            <i class="bi bi-house-door-fill"></i>
                        </button>
                    </div>

                    <!-- Map -->
                    <div id="map" style="z-index: 10; height: 100%; width: 100%;"></div>
                </div>
            </div>
        </section>
        <!-- /Map Section -->
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script type="module" src="{{ asset('frontend/js/map-app.js') }}"></script>
@endpush
