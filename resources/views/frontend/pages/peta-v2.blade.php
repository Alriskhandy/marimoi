<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MARIMOI - Peta Interaktif</title>
    <meta name="theme-color" content="#0a0f1e">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="{{ asset('frontend/css/leaflet.extra-markers.min.css') }}">

    <!-- Vite app CSS -->
    @vite(['resources/css/app.css'])

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
        }

        #app {
            height: 100%;
        }

        /* ── Leaflet popup overrides ─────────────────────────────── */
        .tailwind-popup .leaflet-popup-content-wrapper {
            padding: 0 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05) !important;
        }
        .tailwind-popup .leaflet-popup-content {
            margin: 0 !important;
            line-height: 1.5 !important;
            font-family: inherit !important;
        }
        .tailwind-popup .leaflet-popup-close-button {
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

        /* ── ExtraMarkers icon centering ─────────────────────────── */
        .extra-marker i {
            position: absolute !important;
            top: 20% !important;
            left: 45% !important;
            transform: translate(-50%, -50%) !important;
            line-height: 1 !important;
            font-size: 14px !important;
        }

        /* ── Protect Leaflet marker from Tailwind box-sizing reset ─────────────── */
        /* DO NOT reset margin here — Leaflet sets margin-left/top inline for anchor */
        .leaflet-marker-icon,
        .leaflet-marker-shadow {
            box-sizing: content-box !important;
        }

        /* ── Restore Leaflet zoom control sizes (Tailwind preflight resets them) ── */
        .leaflet-bar {
            box-shadow: 0 1px 5px rgba(0,0,0,.4) !important;
            border: none !important;
        }
        .leaflet-bar a,
        .leaflet-bar a:hover {
            display: block !important;
            width: 28px !important;
            height: 28px !important;
            line-height: 28px !important;
            font-size: 16px !important;
            font-weight: bold !important;
            text-align: center !important;
            text-decoration: none !important;
            background-color: #fff !important;
            border-bottom: 1px solid #ccc !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .leaflet-bar a:last-child {
            border-bottom: none !important;
            border-radius: 0 0 4px 4px !important;
        }
        .leaflet-bar a:first-child {
            border-radius: 4px 4px 0 0 !important;
        }
        .leaflet-bar a:hover {
            background-color: #f4f4f4 !important;
        }
        .leaflet-bar a.leaflet-disabled {
            color: #bbb !important;
            cursor: default !important;
        }
    </style>
</head>

<body>
    <div id="app"></div>

    <script>
        window.MARIMOI_CONFIG = {
            documents:        @json($documents ?? []),
            selectedCategory: @json(session('selectedCategory')),
            csrfToken:        '{{ csrf_token() }}',
            logoUrl:          '{{ asset("frontend/img/logo/logo-white.png") }}'
        };
    </script>

    <!-- Leaflet (must load before Vue bundle) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="{{ asset('frontend/js/leaflet.extra-markers.min.js') }}"></script>

    @vite(['resources/js/webgis-app.js'])
</body>
</html>
