@extends('frontend.layouts.main')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <style>
        /* Custom CSS untuk layer list */
        #layer-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .layer-checkbox:checked+label {
            font-weight: 600;
            color: #1e40af;
        }

        .custom-popup .leaflet-popup-content {
            margin: 8px;
            min-width: 200px;
        }
    </style>
@endpush

@section('main')
    <main class="flex flex-1 bg-base-100">

        <!-- Sidebar -->
        <aside id="sidebar"
            class="bg-white/95 border-r border-gray-200 p-6 w-72 flex-shrink-0 overflow-y-auto max-h-[calc(100vh-72px)] transition-transform duration-300">
            <div>
                <div id="basemap-section" class="mb-5">
                    <button onclick="document.getElementById('sidebar').classList.add('hidden')"
                        class="btn btn-xs btn-outline float-right mb-4">
                        ✖ Tutup
                    </button>
                    <h3 class="font-semibold text-gray-800 mb-3">Base Map</h3>
                    <fieldset class="space-y-2">
                        <label>
                            <input type="radio" name="basemap" value="osm" checked onclick="changeBaseMap('osm')">
                            OpenStreetMap
                        </label> <br>
                        <label>
                            <input type="radio" name="basemap" value="satelite" onclick="changeBaseMap('satelite')">
                            Satellite
                        </label> <br>
                        <label>
                            <input type="radio" name="basemap" value="terrain" onclick="changeBaseMap('terrain')"> Terrain
                        </label>
                    </fieldset>
                </div>

                <div id="layer-section" class="mb-5 hidden">
                    <button onclick="document.getElementById('sidebar').classList.add('hidden')"
                        class="btn btn-xs btn-outline float-right mb-4">
                        ✖ Tutup
                    </button>
                    <h3 class="font-semibold text-gray-800 mb-3">Layer Peta</h3>

                    <!-- Slider Transparansi -->
                    <div class="mb-4">
                        <label for="transparency" class="block text-sm font-medium text-gray-700">Transparansi Layer</label>
                        <input type="range" id="transparency" min="0" max="100" value="50"
                            class="w-full mt-1" />
                    </div>

                    <!-- Dropdown Pilih Kategori -->
                    <div class="mb-4">
                        <label for="category" class="block text-sm font-medium text-gray-700">Pilih Kategori</label>
                        <select id="category" class="select select-primary bg-white select-xs sm:select-sm sm:mt-5 w-full">
                            <option value="all">Semua Kategori</option>
                            <option value="infrastruktur">Infrastruktur</option>
                            <option value="perekonomian">Perekonomian</option>
                            <option value="lingkungan">Lingkungan</option>
                        </select>
                    </div>

                    <h4 class="block text-sm font-medium text-gray-700 mb-3 sm:mb-5">Daftar Layer</h4>
                    <!-- Daftar Layer Dinamis Berdasarkan Kategori -->
                    <div id="layer-list" class="space-y-3">
                        <!-- Dropdown untuk setiap kategori yang akan diisi secara dinamis -->
                    </div>
                </div>

                <div id="legend-section" class="hidden">
                    <button onclick="document.getElementById('sidebar').classList.add('hidden')"
                        class="btn btn-xs btn-outline float-right mb-4">
                        ✖ Tutup
                    </button>
                    <p class="text-sm font-medium mb-2">Legenda</p>
                    <ul class="space-y-2 text-sm">
                        <!-- Legenda -->
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Map -->
        <section class="flex-grow relative">

            <!-- Map controls top right -->
            <div class="absolute top-5 right-5 space-x-2 z-30 flex items-center">
                <button onclick="showSidebarSection('layer')"
                    class="btn btn-sm sm:btn-md bg-white border-0 hover:bg-gray-100 shadow-md" title="Layer">
                    <i class="ph-fill ph-stack" style="color: purple"></i>
                </button>
                <button onclick="showSidebarSection('basemap')"
                    class="btn btn-sm sm:btn-md bg-white border-0 hover:bg-gray-100 shadow-md" title="Basemap">
                    <i class="ph-fill ph-map-trifold" style="color: purple"></i>
                </button>
                <button onclick="showSidebarSection('legend')"
                    class="btn btn-sm sm:btn-md bg-white border-0 hover:bg-gray-100 shadow-md" title="Legenda">
                    <i class="ph-fill ph-list" style="color: purple"></i>
                </button>
            </div>

            <!-- Map Area -->
            <div class="w-full h-[calc(100vh-72px)] bg-blue-100 border-l border-gray-200 relative">

                <!-- Map Container -->
                <div id="map" class="w-full h-full z-10 rounded-md"></div>

            </div>

        </section>
    </main>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="{{ asset('js/map-config.js') }}"></script>
    <script src="{{ asset('js/map-main.js') }}"></script>
    <script src="{{ asset('js/map-utils.js') }}"></script>
@endpush
