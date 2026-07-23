@extends('frontend.layouts.dark', ['title' => 'MARIMOI - Peta Interaktif'])

@push('styles')
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="{{ asset('frontend/css/leaflet.extra-markers.min.css') }}">
    <link href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    {{-- <link rel="stylesheet" href="{{ asset('frontend/css/map.css') }}"> --}}

    <style>
        /* Typography Fonts */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
        }

        p,
        body,
        ul,
        li {
            font-family: 'Inter', sans-serif;
        }

        .tailwind-popup .leaflet-popup-content-wrapper {
            @apply bg-white rounded-lg shadow-lg border border-gray-200;
            padding: 0 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }

        .tailwind-popup .leaflet-popup-content {
            margin: 0 !important;
            line-height: 1.5 !important;
            font-family: inherit !important;
        }

        .tailwind-popup .leaflet-popup-close-button {
            @apply text-gray-400 hover:text-gray-600;
            font-size: 18px !important;
            font-weight: bold !important;
            padding: 8px !important;
            top: 8px !important;
            right: 8px !important;
            width: auto !important;
            height: auto !important;
            background: transparent !important;
            border: none !important;
        }

        .tailwind-popup .leaflet-popup-tip {
            @apply bg-white border-gray-200;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
        }

        /* Pastikan text dalam popup tidak terpengaruh global styles */
        .tailwind-popup table td {
            padding: 0.25rem 0.5rem 0.25rem 0 !important;
            vertical-align: top !important;
            border: none !important;
            background: transparent !important;
        }

        .tailwind-popup table {
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            margin: 0 !important;
        }

        .tailwind-popup a {
            text-decoration: none !important;
        }

        .tailwind-popup button:focus,
        .tailwind-popup a:focus {
            outline: 1px solid #3b82f6 !important;
            outline-offset: 2px !important;
        }

        /* Mobile responsive popup */
        @media (max-width: 640px) {
            .tailwind-popup .leaflet-popup-content-wrapper {
                max-width: calc(100vw - 40px) !important;
            }
        }

        .extra-marker i {
            position: absolute !important;
            top: 20% !important;
            left: 45% !important;
            transform: translate(-50%, -50%) !important;
            line-height: 1 !important;
            font-size: 14px !important;
        }
    </style>
@endpush

@section('main')
    <div class="p-0 h-[calc(100vh-70px)]">
        <!-- Map Section -->
        <section id="map-section" class="relative p-0 mt-[70px] h-[calc(100vh-70px)] w-full overflow-hidden">
            <div class="p-0 relative h-full">
                <!-- Container Toast -->
                <div id="toast-container" class="fixed top-20 right-3 space-y-2"></div>

                <!-- Modal Panduan Awal -->
                <div id="guideModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
                    <div class="mx-3 bg-white text-gray-700 relative self-center rounded-lg shadow-lg w-full max-w-lg">
                        <!-- Header -->
                        <div class="px-4 py-3 border-b border-b-gray-400">
                            <h5 class="text-lg font-semibold">Panduan Penggunaan</h5>
                        </div>

                        <!-- Body -->
                        <div class="px-4 py-5 text-sm text-justify">
                            <div class="guide-step" data-step="1">
                                <p>Selamat datang di WebGIS Perencanaan! Gunakan tombol-tombol kontrol untuk
                                    mengatur tampilan peta.</p>
                            </div>
                            <div class="guide-step hidden" data-step="2">
                                <p>Tombol <strong><i class="bi bi-plus border border-gray-700 p-1 text-gray-700"></i> Zoom
                                        In & <i class="bi bi-dash border border-gray-700 p-1 text-gray-700"></i> Zoom
                                        Out</strong>, digunakan untuk
                                    mengatur zoom peta.</p>
                            </div>
                            <div class="guide-step hidden" data-step="3">
                                <p>Gunakan tombol <strong><i
                                            class="bi bi-info-circle-fill border border-gray-700 p-1 text-gray-700"></i>
                                        Bantuan</strong> untuk melihat panduan ini kapan saja.</p>
                            </div>
                            <div class="guide-step hidden" data-step="4">
                                <p>Tombol <strong><i class="bi bi-list-ul border border-gray-700 p-1 text-gray-700"></i>
                                        Legenda Peta</strong>
                                    menampilkan keterangan simbol pada peta.</p>
                            </div>
                            <div class="guide-step hidden" data-step="5">
                                <p>Tombol <strong><i class="bi bi-grid-fill border border-gray-700 p-1 text-gray-700"></i>
                                        Basemap Peta</strong>
                                    digunakan untuk memilih jenis peta dasar.
                                </p>
                            </div>
                            <div class="guide-step hidden" data-step="6">
                                <p>Tombol <strong><i class="bi bi-layers-fill border border-gray-700 p-1 text-gray-700"></i>
                                        Layer Peta</strong>
                                    digunakan untuk mengatur layer yang ingin
                                    ditampilkan.</p>
                            </div>
                            <div class="guide-step hidden" data-step="7">
                                <p>Tombol <strong><i
                                            class="bi bi-file-earmark-arrow-down-fill border border-gray-700 p-1 text-gray-700"></i>
                                        Download Peta</strong> memungkinkan Anda mengunduh peta.</p>
                            </div>
                            <div class="guide-step hidden" data-step="8">
                                <p>Tombol <strong><i
                                            class="bi bi-arrows-fullscreen border border-gray-700 p-1 text-gray-700"></i>
                                        Fullscreen</strong> memungkinkan Anda untuk masuk dan keluar dari tampilan
                                    penuh.</p>
                            </div>
                            <div class="guide-step hidden" data-step="9">
                                <p>Tombol <strong><i
                                            class="bi bi-house-door-fill border border-gray-700 p-1 text-gray-700"></i>
                                        Home</strong>
                                    memungkinkan Anda kembali ke default zoom dari peta.</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end gap-2 px-4 py-3 border-t border-t-gray-400">
                            <button id="btnSkip"
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">Skip</button>
                            <button id="btnPrev"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-1 rounded disabled:opacity-50"
                                disabled>Prev</button>
                            <button id="btnNext"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">Next</button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Layer -->
                <div id="sidebar-layer"
                    class="absolute top-0 right-0 w-[280px] md:w-[300px] h-[calc(100vh-70px)] bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 hidden">
                    <!-- Header with gradient background -->
                    <div
                        class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
                        <h6 class="text-white mb-0 text-sm font-semibold">Layer</h6>
                        <button id="btn-close-sidebar-layer"
                            class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                            <i class="bi bi-x-lg text-white"></i>
                        </button>
                    </div>

                    <div class="mb-3 w-full">
                        <label for="layer-search" class="sr-only">Cari Layer/Kategori</label>
                        <div
                            class="flex items-center gap-2 w-full px-3 bg-white border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-400 focus-within:border-blue-400">
                            <i class="bi bi-search text-gray-400 text-sm shrink-0"></i>
                            <input type="text" id="layer-search" name="layer-search" autocomplete="off"
                                spellcheck="false" maxlength="100"
                                class="flex-1 min-w-0 text-sm text-gray-900 placeholder-gray-400 bg-transparent border-0 py-2 outline-none ring-0 focus:outline-none focus:ring-0 focus:border-0 shadow-none"
                                placeholder="Cari layer atau kategori...">
                            <button type="button" id="layer-search-clear"
                                class="hidden shrink-0 text-gray-400 hover:text-gray-600" aria-label="Hapus pencarian">
                                <i class="bi bi-x-circle-fill text-sm"></i>
                            </button>
                        </div>
                        <p id="layer-search-empty" class="hidden text-xs text-gray-500 mt-2 px-1">
                            Tidak ada layer/kategori yang cocok.
                        </p>
                    </div>

                    <div id="layer-list" class="max-h-[calc(100vh-250px)] overflow-y-auto text-sm">
                        <!-- Layer list will be populated dynamically -->
                    </div>
                </div>

                <!-- Sidebar Basemap -->
                <div id="sidebar-basemap"
                    class="absolute top-0 right-0 w-[280px] md:w-[300px] h-[calc(100vh-70px)] bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 hidden">

                    <!-- Header with gradient background -->
                    <div
                        class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
                        <h6 class="text-white mb-0 text-sm font-semibold">Basemap</h6>
                        <button id="btn-close-sidebar-basemap"
                            class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                            <i class="bi bi-x-lg text-white"></i>
                        </button>
                    </div>

                    <!-- List area -->
                    <div id="basemap-list" class="pt-2 max-h-[calc(100vh-250px)] overflow-y-auto text-sm">
                        <!-- Generated By Js -->
                    </div>
                </div>

                <!-- Sidebar Legend -->
                <div id="sidebar-legend"
                    class="absolute top-0 right-0 w-[280px] md:w-[300px] h-[calc(100vh-70px)] bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 hidden">

                    <!-- Header with gradient background -->
                    <div
                        class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
                        <h6 class="text-white mb-0 text-sm font-semibold">Legenda</h6>
                        <button id="btn-close-sidebar-legend"
                            class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                            <i class="bi bi-x-lg text-white"></i>
                        </button>
                    </div>

                    <!-- Content area -->
                    <div id="legend-content" class="ml-2 max-h-[calc(100vh-250px)] overflow-y-auto">
                        <!-- Generated By Js -->
                    </div>
                </div>

                <!-- Panel Layer Tools (slider transparansi per layer aktif, posisi diatur oleh JS
                     agar selalu menyambung tepat di bawah kolom tombol Leaflet sisi kiri) -->
                <div id="sidebar-layer-tools"
                    class="absolute w-[280px] md:w-[300px] bg-slate-50 border border-gray-300 rounded-lg shadow-lg z-[101] text-gray-900 hidden">
                    <!-- Header with gradient background -->
                    <div id="layer-tools-header"
                        class="flex justify-between items-center bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-2 px-3 rounded-t-lg w-full">
                        <h6 class="text-white mb-0 text-sm font-semibold flex items-center gap-2">
                            <i class="bi bi-sliders"></i> Layer Tools
                        </h6>
                        <button id="btn-close-sidebar-layer-tools"
                            class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                            <i class="bi bi-x-lg text-white"></i>
                        </button>
                    </div>

                    <!-- Content area (max-height diatur oleh JS berdasarkan sisa ruang aktual, lihat positionLayerToolsPanel di map.js) -->
                    <div id="layer-tools-content" class="overflow-y-auto">
                        <!-- Generated by JS: empty state atau slider per layer aktif -->
                    </div>
                </div>

                <!-- Sidebar Download Map -->
                <div id="sidebar-download"
                    class="absolute top-0 right-0 w-[280px] md:w-[300px] h-[calc(100vh-70px)] bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 hidden">

                    <!-- Header with gradient background -->
                    <div
                        class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
                        <h6 class="text-white mb-0 text-sm font-semibold">Unduh Data/Informasi</h6>
                        <button id="btn-close-sidebar-download"
                            class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                            <i class="bi bi-x-lg text-white"></i>
                        </button>
                    </div>

                    <!-- Content area -->
                    <div id="download-content" class="ml-2 max-h-[calc(100vh-250px)] overflow-y-auto">
                        <p class="text-sm text-black mb-3">Daftar Dokumen :</p>

                        <!-- Document list -->
                        <ul class="text-sm space-y-2">
                            @foreach ($documents as $doc)
                                <li>
                                    <a href="{{ asset('storage/' . $doc->file) }}" title="{{ $doc->nama }}" download
                                        class="text-black hover:text-blue-800 hover:underline transition-colors duration-200 flex items-center gap-2">
                                        <i class="bi bi-file-earmark-arrow-down text-gray-700"></i>
                                        {{ $doc->nama }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>


                        <!-- Empty state when no documents -->
                        <div id="empty-state" class="hidden text-center py-8">
                            <i class="bi bi-folder2-open text-4xl text-gray-400 mb-3 block"></i>
                            <p class="text-gray-700 text-sm">Tidak ada dokumen tersedia</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Control Buttons -->
                <div id="sidebar-control-buttons"
                    class="absolute top-2.5 right-2.5 z-[99] bg-gray-300 shadow-md flex flex-col items-center rounded-none"
                    role="group" aria-label="Sidebar Control Buttons">

                    <button id="btn-toggle-sidebar-help" type="button"
                        class="text-black border border-black/20 border-b border-gray-400 rounded-none bg-white hover:bg-slate-200 px-3 py-2 text-sm transition-colors duration-200"
                        title="Bantuan" data-tooltip="Bantuan">
                        <i class="bi bi-info-circle-fill"></i>
                    </button>

                    <button id="btn-toggle-sidebar-legend" type="button"
                        class="text-black border border-black/20 border-b border-gray-400 rounded-none bg-white hover:bg-slate-200 px-3 py-2 text-sm transition-colors duration-200"
                        title="Legenda Peta" data-tooltip="Legenda Peta">
                        <i class="bi bi-list-ul"></i>
                    </button>

                    <button id="btn-toggle-sidebar-basemap" type="button"
                        class="text-black border border-black/20 border-b border-gray-400 rounded-none bg-white hover:bg-slate-200 px-3 py-2 text-sm transition-colors duration-200"
                        title="Basemap Peta" data-tooltip="Basemap Peta">
                        <i class="bi bi-grid-fill"></i>
                    </button>

                    <button id="btn-toggle-sidebar-layer" type="button"
                        class="text-black border border-black/20 border-b border-gray-400 rounded-none bg-white hover:bg-slate-200 px-3 py-2 text-sm transition-colors duration-200"
                        title="Layer Peta" data-tooltip="Layer Peta">
                        <i class="bi bi-layers-fill"></i>
                    </button>
                </div>

                <!-- Navigation Control Buttons -->
                <div id="nav-control-buttons"
                    class="absolute bottom-[30px] right-2.5 z-[99] bg-gray-300 shadow-md flex flex-col items-center rounded-none"
                    role="group" aria-label="Navigation Control Buttons">

                    <button id="btn-toggle-sidebar-download" type="button"
                        class="text-black border border-black/20 border-b border-gray-400 rounded-none bg-white hover:bg-slate-200 px-3 py-2 text-sm transition-colors duration-200"
                        title="Unduh Data/Informasi" data-tooltip="Download Peta">
                        <i class="bi bi-file-earmark-arrow-down-fill"></i>
                    </button>
                </div>

                <!-- Tombol Fullscreen & Home dirender oleh Leaflet sebagai control 'topleft',
                     langsung menyambung di bawah tombol zoom in/out bawaan Leaflet (lihat map.js) -->

                <!-- Map -->
                <div id="map" class="relative z-10 h-full w-full bg-gray-200 flex items-center justify-center">
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="{{ asset('frontend/js/leaflet.extra-markers.min.js') }}"></script>

    @if (session('selectedCategory'))
        <script>
            // Pass selected category from session to JavaScript
            window.MARIMOI_SELECTED_CATEGORY = @json(session('selectedCategory'));
        </script>
    @endif

    <script src="{{ asset('frontend/js/map-cache.js') }}"></script>
    <script src="{{ asset('frontend/js/map.js') }}"></script>
@endpush
