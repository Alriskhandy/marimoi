@extends('frontend.layouts.spatial', ['title' => 'MARIMOI - '.$title])

@push('styles')
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        #map-share-wrapper {
            position: relative;
            height: calc(100vh - 5rem);
        }

        #map-share-map {
            height: 100%;
            width: 100%;
        }

        #map-share-panel {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            padding: 1rem;
            max-width: 20rem;
        }
    </style>
@endpush

@section('no-hero', '1')
@section('no-footer', '1')

@section('main')
    <div id="map-share-wrapper">
        <div id="map-share-map"></div>
        <div id="map-share-panel">
            <h2 style="font-size:1rem;font-weight:700;">{{ $title }}</h2>
            @if ($description)
                <p style="font-size:0.85rem;color:#4b5563;">{{ $description }}</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const map = L.map('map-share-map').setView([1.5, 127.8], 8);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const layers = @json($layers);

        layers.forEach(async (layer) => {
            if (layer.is_visible === false) {
                return;
            }

            try {
                const response = await fetch(`/peta-v2/geojson/${layer.slug}`);
                if (!response.ok) {
                    return;
                }
                const geojson = await response.json();

                L.geoJSON(geojson, {
                    style: () => ({ opacity: layer.opacity ?? 1, fillOpacity: (layer.opacity ?? 1) * 0.4 }),
                }).addTo(map);
            } catch (error) {
                console.error(`Gagal memuat layer ${layer.slug}`, error);
            }
        });
    </script>
@endpush
