@extends('frontend.layouts.dark', ['title' => 'Detail Kegiatan - MARIMOI'])

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- Tailwind CSS via Vite -->
    @vite(['resources/css/app.css'])
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
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
        li,
        td,
        th {
            font-family: 'Inter', sans-serif;
        }

        /* Custom styles for detail table */
        .detail-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #374151;
            width: 30%;
        }

        .detail-table td {
            color: #6b7280;
        }
    </style>
@endpush

@section('main')
    <!-- Detail Section -->
    <section class="min-h-auto mt-[76px] pt-8 pb-12 bg-slate-50">
        <!-- Section Title -->
        <div class="container mx-auto px-4 text-center mb-8 max-w-7xl">
            <h2 class="text-2xl md:text-3xl font-bold text-slate-800 mb-2">Detail Peta</h2>
        </div>

        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Navigasi -->
            <div class="flex items-center space-x-2 mb-3">
                <a href="{{ url()->previous() }}"
                    class="inline-flex items-center px-2 py-1.5 md:px-4 md:py-2 bg-slate-100 border border-slate-300 rounded-lg shadow-sm text-sm font-medium text-slate-800 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </a>

                <span class="text-slate-400">/</span>
                <h4 class="text-sm md:text-md lg:text-lg font-medium text-slate-600">
                    {{ $project->kategori->deskripsi ?? $project->kategori->nama }}
                </h4>
            </div>

            @if (isset($project))
                <div class="grid grid-cols-1 {{ $project->gambar ? 'lg:grid-cols-2' : 'lg:grid-cols-1' }} gap-6 mb-8">
                    <!-- Detail Map -->
                    <div class="w-full">
                        <div class="bg-white shadow-xl rounded-xl overflow-hidden h-full">
                            <div class="p-2 md:p-6">
                                <div id="map-detail" class="w-full h-96 lg:h-[400px] rounded-lg z-[101]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Gambar jika ada -->
                    @if (!empty($project->gambar))
                        <div class="w-full">
                            <div class="bg-white shadow-xl rounded-xl overflow-hidden h-full">
                                <div class="p-2 md:p-6">
                                    <img src="{{ asset('storage/' . $project->gambar) }}" alt="{{ $project->judul }}"
                                        class="w-full h-96 lg:h-[400px] object-cover rounded-lg">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Deskripsi -->
                <div class="w-full">
                    <div class="bg-white shadow-xl rounded-xl overflow-hidden">
                        <div class="p-2 md:p-6">
                            <div class="overflow-x-auto">
                                <table class="detail-table w-full border-collapse">
                                    <tbody>
                                        <tr class="border-b border-slate-200">
                                            <th class="py-3 px-4 text-left font-semibold text-slate-700 bg-slate-50">
                                                KATEGORI</th>
                                            <td class="py-3 px-4 text-slate-600">{{ $project->kategori->nama }}</td>
                                        </tr>
                                        <tr class="border-b border-slate-200">
                                            <th class="py-3 px-4 text-left font-semibold text-slate-700 bg-slate-50">
                                                DESKRIPSI</th>
                                            <td class="py-3 px-4 text-slate-600">{{ $project->deskripsi }}</td>
                                        </tr>
                                        @if (isset($project->dbf_attributes))
                                            @php
                                                $dbfAttributes = is_string($project->dbf_attributes)
                                                    ? json_decode($project->dbf_attributes, true)
                                                    : $project->dbf_attributes;
                                            @endphp
                                            @foreach ($dbfAttributes as $key => $value)
                                                @if (strtolower($key) !== 'id')
                                                    <tr class="border-b border-slate-200">
                                                        <th
                                                            class="py-3 px-4 text-left font-semibold text-slate-700 bg-slate-50">
                                                            {{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                                        <td class="py-3 px-4 text-slate-600">{{ $value }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white shadow-xl rounded-xl p-8 text-center">
                    <div class="text-slate-600">
                        <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-lg">Data proyek tidak ditemukan.</p>
                    </div>
                </div>
            @endif
        </div>
    </section><!-- /Detail Section -->

    <!-- Footer Section -->
    @include('frontend.partials.footer-dark')
@endsection

@push('scripts')
    <!-- Vite JavaScript -->
    @vite(['resources/js/app.js'])
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Map functionality
            @if (isset($project) && isset($project->geojson))
                // Initialize the map
                var map = L.map('map-detail').setView([0, 0], 13);

                // Add OpenStreetMap tile layer
                L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        id: "esri-world-imagery",
                        label: "ESRI World Imagery",
                        minZoom: 7,
                        maxZoom: 18,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);

                // Parse geometry JSON
                var geometry = {!! json_encode($project->geojson) !!};

                var layer;

                if (geometry.type === 'Point') {
                    var coords = [geometry.coordinates[1], geometry.coordinates[0]];
                    layer = L.marker(coords).addTo(map);
                    map.setView(coords, 16);
                } else if (geometry.type === 'LineString') {
                    var latlngs = geometry.coordinates.map(function(coord) {
                        return [coord[1], coord[0]];
                    });
                    layer = L.polyline(latlngs).addTo(map);
                    map.fitBounds(layer.getBounds());
                } else if (geometry.type === 'Polygon') {
                    var latlngs = geometry.coordinates[0].map(function(coord) {
                        return [coord[1], coord[0]];
                    });
                    layer = L.polygon(latlngs).addTo(map);
                    map.fitBounds(layer.getBounds());
                }

                // Add popup with feature title
                // if (layer) {
                //     layer.bindPopup("{{ $project->kategori->nama ?? 'Feature' }}").openPopup();
                // }
            @endif
        });
    </script>
@endpush
