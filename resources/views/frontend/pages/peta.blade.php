@extends('frontend.layouts.app')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<link rel="stylesheet" href="{{ asset('frontend/css/leaflet.extra-markers.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/map.css') }}">
@endpush

@section('main')
    <div class="container-fluid p-0 full-height">

        <!-- Page Title -->
        @include('frontend.partials.navbar')

        <!-- Map Section -->
        <section id="map-section" class="section pb-0">
            <div class="container-fluid p-0 map-container" data-aos="fade-in" data-aos-delay="100">
                <div class="position-relative map-wrapper">

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
                                        <p>Tombol <strong><i class="bi bi-plus border p-1"></i> Zoom In & <i
                                                    class="bi bi-dash border p-1"></i> Zoom Out</strong>, digunakan untuk
                                            mengatur zoom peta.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="3">
                                        <p>Gunakan tombol <strong><i class="bi bi-info-circle-fill border p-1"></i>
                                                Bantuan</strong> untuk melihat panduan ini kapan saja.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="4">
                                        <p>Tombol <strong><i class="bi bi-list-ul border p-1"></i> Legenda Peta</strong>
                                            menampilkan keterangan simbol pada peta.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="5">
                                        <p>Tombol <strong><i class="bi bi-grid-fill border p-1"></i> Basemap Peta</strong>
                                            digunakan untuk memilih jenis peta dasar.
                                        </p>
                                    </div>
                                    <div class="guide-step d-none" data-step="6">
                                        <p>Tombol <strong><i class="bi bi-layers-fill border p-1"></i> Layer Peta</strong>
                                            digunakan untuk mengatur layer yang ingin
                                            ditampilkan.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="7">
                                        <p>Tombol <strong><i class="bi bi-file-earmark-arrow-down-fill border p-1"></i>
                                                Download Peta</strong> memungkinkan Anda mengunduh peta.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="8">
                                        <p>Tombol <strong><i class="bi bi-arrows-fullscreen border p-1"></i>
                                                Fullscreen</strong> memungkinkan Anda untuk masuk dan keluar dari tampilan
                                            penuh.</p>
                                    </div>
                                    <div class="guide-step d-none" data-step="9">
                                        <p>Tombol <strong><i class="bi bi-house-door-fill border p-1"></i> Home</strong>
                                            memungkinkan Anda kembali ke default zoom dari peta.</p>
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
                    <div id="sidebar-layer" class="sidebar text-dark position-absolute">
                        <div class="d-flex justify-content-between align-items-center mb-3 gradient-purple">
                            <h6 class="text-white mb-0">Layer</h6>
                            <button id="btn-close-sidebar-layer" class="btn btn-sm"><i
                                    class="bi bi-x-lg text-white"></i></button>
                        </div>
                        <div class="mb-3 ms-2">
                            <label for="transparency" class="form-label text-sm">Transparansi Layer</label>
                            <input type="range" class="form-range" min="0" max="100" value="100"
                                id="transparency">
                        </div>
                        <div id="layer-list" class="ms-2" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                            <!-- Layer list will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Sidebar Basemap -->
                    <div id="sidebar-basemap" class="sidebar text-dark position-absolute">
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
                    <div id="sidebar-legend" class="sidebar text-dark position-absolute">
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
                    <div id="sidebar-download" class="sidebar text-dark position-absolute">
                        <div class="d-flex justify-content-between align-items-center mb-3 gradient-purple">
                            <h6 class="text-white mb-0">Unduh Data/Informasi</h6>
                            <button id="btn-close-sidebar-download" class="btn btn-sm"><i
                                    class="bi bi-x-lg text-white"></i></button>
                        </div>
                        <div id="download-content" class="ms-2"
                            style="max-height: calc(100vh - 250px); overflow-y: auto;">
                            <p class="text-sm">Daftar Dokumen :</p>
                            <ul class="text-sm">
                                @foreach ($documents as $doc)
                                    <li><a href="{{ asset('storage/' . $doc->file) }}" title="{{ $doc->nama }}"
                                            download>{{ $doc->nama }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <!-- Sidebar Control Buttons -->
                    <div id="sidebar-control-buttons" class="btn-group position-absolute" role="group"
                        aria-label="Sidebar Control Buttons">
                        <button id="btn-toggle-sidebar-help" type="button" class="btn btn-sm"
                            title="Bantuan" class="control-button" data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="Bantuan">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                        <button id="btn-toggle-sidebar-legend" type="button" class="btn btn-sm"
                            title="Legenda Peta" class="control-button" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Legenda Peta">
                            <i class="bi bi-list-ul"></i>
                        </button>
                        <button id="btn-toggle-sidebar-basemap" type="button" class="btn btn-sm"
                            title="Basemap Peta" class="control-button" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Basemap Peta">
                            <i class="bi bi-grid-fill"></i>
                        </button>
                        <button id="btn-toggle-sidebar-layer" type="button" class="btn btn-sm"
                            title="Layer Peta" class="control-button" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Layer Peta">
                            <i class="bi bi-layers-fill"></i>
                        </button>
                    </div>

                    <!-- Navigation Control Buttons -->
                    <div id="nav-control-buttons" class="btn-group position-absolute" role="group"
                        aria-label="Sidebar Control Buttons">
                        <button id="btn-toggle-sidebar-download" type="button" class="btn btn-sm"
                            title="Unduh Data/Informasi" class="control-button" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Download Peta">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i>
                        </button>
                        <button id="btn-fullscreen" type="button" class="btn btn-sm"
                            title="Tampilan Penuh" class="control-button" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Tampilan Penuh">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <button id="btn-default-zoom" type="button" class="btn btn-sm"
                            title="Default Zoom" class="control-button" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" data-bs-title="Default Zoom">
                            <i class="bi bi-house-door-fill"></i>
                        </button>
                    </div>

                    <!-- Map -->
                    <div id="map"></div>
                </div>
            </div>
        </section>
        <!-- /Map Section -->
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="{{ asset('frontend/js/leaflet.extra-markers.min.js') }}"></script>
    <script src="{{ asset('frontend/js/map.js') }}"></script>
@endpush
