@extends('frontend.layouts.spatial', ['title' => 'MARIMOI - Peta Interaktif (Pratinjau Skema Baru)'])

@push('styles')
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        #peta-v2-wrapper {
            position: relative;
            height: calc(100vh - 5rem);
        }

        #peta-v2-map {
            height: 100%;
            width: 100%;
        }

        #peta-v2-panel {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            padding: 1rem;
            max-width: 20rem;
            max-height: calc(100% - 2rem);
            overflow-y: auto;
        }

        #peta-v2-panel h2 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        #peta-v2-panel .layer-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0;
        }

        #peta-v2-panel .layer-swatch {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 999px;
            flex-shrink: 0;
        }

        #peta-v2-banner {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
            padding-top: 0.5rem;
        }
    </style>
@endpush

@section('no-hero', '1')
@section('no-footer', '1')

@section('main')
    <div id="peta-v2-wrapper">
        <div id="peta-v2-map"></div>
        <div id="peta-v2-panel">
            <h2>Layer (Pratinjau Skema Baru)</h2>
            <div id="peta-v2-layer-list">Memuat daftar layer...</div>
            <p id="peta-v2-banner">
                Ini adalah pratinjau di atas snapshot data yang di-backfill 2026-09-27, bukan tampilan
                real-time. Data yang diinput admin setelah tanggal itu belum tentu muncul di sini.
                Lihat <code>docs/marimoi v2/04_implementation/10-plan-peta-skema-baru.md</code>.
            </p>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @vite(['resources/js/peta-v2.js'])
@endpush
