@extends('frontend.layouts.app', ['title' => 'Detail Kegiatan - MARIMOI'])
@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="{{ asset('frontend/css/detail.css') }}">
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
@endpush

@section('main')
    <!-- Hero Section -->
    @include('frontend.partials.navbar')

    <!-- Detail Section -->
    <section class="section-with-bg section-detail">
        <!-- Section Title -->
        <div class="container section-title mb-4" data-aos="fade-up">
            <h2 class="title pt-4">Detail Kegiatan</h2>
        </div>

        <div class="container">
            @if (isset($project))
                <div class="row g-4 align-items-stretch">
                    <!-- Detail Map -->
                    <div class="{{ $project->gambar ? 'col-lg-6' : 'col-12' }}">
                        <div class="card shadow h-100" data-aos="fade-up" data-aos-delay="100">
                            <div class="card-body">
                                <div id="map-detail" class="h-100" style="min-height: 400px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Gambar jika ada -->
                    @if (!empty($project->gambar))
                        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                            <div class="card shadow h-100">
                                <div class="card-body">
                                    <img src="{{ asset('storage/' . $project->gambar) }}" alt="{{ $project->judul }}"
                                        class="img-fluid project-image w-100 h-100 object-fit-cover">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Deskripsi -->
                <div class="row g-4 mt-4">
                    <div class="col-md-12" data-aos="fade-up" data-aos-delay="300">
                        <table class="table detail-table">
                            <tbody>
                                <tr>
                                    <th>KATEGORI</th>
                                    <td>{{ $project->kategori->nama }}</td>
                                </tr>
                                <tr>
                                    <th>DESKRIPSI</th>
                                    <td>{{ $project->deskripsi }}</td>
                                </tr>
                                @if (isset($project->dbf_attributes))
                                    @php
                                        $dbfAttributes = is_string($project->dbf_attributes)
                                            ? json_decode($project->dbf_attributes, true)
                                            : $project->dbf_attributes;
                                    @endphp
                                    @foreach ($dbfAttributes as $key => $value)
                                        @if (strtolower($key) !== 'id')
                                            <tr>
                                                <th>{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                                <td>{{ $value }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <p>Data proyek tidak ditemukan.</p>
            @endif
        </div>
    </section><!-- /Detail Section -->


    <!-- Footer Section -->
    @include('frontend.partials.footer')
@endsection

@push('scripts')
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
