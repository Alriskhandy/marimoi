@extends('frontend.layouts.spatial', ['title' => 'MARIMOI - Peta Interaktif'])

@push('styles')
    @vite(['resources/css/app.css', 'resources/css/peta.css'])
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="{{ asset('frontend/css/leaflet.extra-markers.min.css') }}">
    <link href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

    <style>
        /* Font judul & teks halaman peta. */
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

        /* Tabel & tautan di popup tidak boleh terpengaruh style global halaman. */
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
            text-decoration : none !important;
        }

        .tailwind-popup button:focus,
        .tailwind-popup a:focus {
            outline: 1px solid #3b82f6 !important;
            outline-offset: 2px !important;
        }

        /* Popup lebih sempit di layar kecil. */
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

@section('no-hero', '1')
@section('no-footer', '1')
@section('no-nav', '1')

@section('main')
    <div class="p-0 h-screen">
        <section id="map-section" class="relative p-0 h-screen w-full overflow-hidden">
            <div class="p-0 relative h-full">
                {{-- Tempat notifikasi singkat (showAlert di map.js). --}}
                <div id="toast-container" class="fixed top-4 left-1/2 -translate-x-1/2 z-[1200] space-y-2"></div>

                {{-- Panduan (tur berlangkah): menyorot kontrol peta satu per satu. Isi langkahnya di map-guide.js. --}}
                <div id="mapGuide" class="map-guide hidden" role="dialog" aria-modal="true"
                    aria-labelledby="map-guide-title" aria-describedby="map-guide-text">
                    <div class="map-guide-spotlight" data-guide-spotlight></div>
                    <div class="map-guide-card" data-guide-card>
                        <div class="map-guide-head">
                            <span class="map-guide-icon" data-guide-icon aria-hidden="true"></span>
                            <span class="map-guide-progress" data-guide-progress></span>
                            <button type="button" class="map-guide-close" data-guide-skip aria-label="Tutup panduan">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <h3 id="map-guide-title" data-guide-title></h3>
                        <p id="map-guide-text" data-guide-text></p>
                        <div class="map-guide-dots" data-guide-dots aria-hidden="true"></div>
                        <div class="map-guide-actions">
                            <button type="button" class="map-guide-link" data-guide-skip>Lewati</button>
                            <div>
                                <button type="button" class="catalog-btn-outline" data-guide-prev>Sebelumnya</button>
                                <button type="button" class="catalog-btn-primary" data-guide-next>Berikutnya</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Safelist Tailwind: kelas ini hanya dipasang lewat map.js (tombol Copy Link saat berhasil),
                     padahal file di public/ tidak dipindai Tailwind. Ditulis di sini agar ikut di-build;
                     elemen ini tidak pernah tampil. --}}
                <div class="hidden bg-green-500 hover:bg-green-600"></div>

                {{-- Modal share: link pendek ke tampilan peta saat ini (dibuat lewat map.js). --}}
                <div id="shareMapModal"
                    class="fixed inset-0 z-[1100] hidden items-center justify-center bg-slate-950/70 backdrop-blur-sm">
                    <div
                        class="mx-3 bg-white text-gray-700 relative self-center overflow-hidden rounded-3xl shadow-2xl w-full max-w-lg">
                        <div
                            class="px-6 py-4 bg-gradient-to-br from-[#071a2d] to-[#0b3a66] text-white flex justify-between items-center">
                            <h5 class="text-lg font-bold tracking-tight">Bagikan Peta</h5>
                            <button id="btn-close-share-modal" class="text-white/70 hover:text-white">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div class="px-4 py-5 text-sm">
                            <p id="shareMapEmptyState" class="hidden text-gray-500">
                                Pilih minimal satu layer di panel Layer sebelum membagikan peta.
                            </p>

                            <div id="shareMapContent">
                                <p class="text-gray-600 mb-3">
                                    Link ini akan menampilkan layer dan posisi peta yang sama seperti saat ini.
                                </p>

                                <div class="flex items-center gap-2 mb-5">
                                    <div class="relative flex-1 min-w-0">
                                        <input type="text" id="shareMapLink" readonly
                                            class="w-full text-sm text-gray-900 bg-gray-100 border border-gray-300 rounded-lg pl-3 pr-9 py-2.5 outline-none transition-colors duration-300 focus:border-blue-400"
                                            placeholder="Membuat link...">
                                        <i id="shareMapLinkSpinner"
                                            class="bi bi-arrow-repeat animate-spin absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    <button id="btn-copy-share-link" type="button" disabled
                                        class="shrink-0 w-[118px] bg-blue-500 hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-500 text-white px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 active:scale-95 flex items-center justify-center gap-1.5">
                                        <i id="btn-copy-share-icon" class="bi bi-clipboard"></i>
                                        <span id="btn-copy-share-label">Copy Link</span>
                                    </button>
                                </div>



                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal Katalog Peta: memilih mapset yang ditampilkan. Isi kolom & kartu dibuat map-catalog.js. --}}
                {{-- Analisis Peta: isi dirender map-analysis.js dari layer aktif --}}
                <div id="analysisModal" class="catalog-modal analysis-modal hidden" role="dialog" aria-modal="true"
                    aria-labelledby="analysis-modal-title">
                    <div class="catalog-dialog analysis-dialog">
                        <header class="analysis-header">
                            <div>
                                <h2 id="analysis-modal-title">Analisis Peta</h2>
                                <p data-analysis-subtitle></p>
                            </div>
                            <div class="analysis-header-actions">
                                <div class="analysis-scope" role="group" aria-label="Cakupan analisis">
                                    <button type="button" data-analysis-scope="view" aria-pressed="true"><i class="bi bi-aspect-ratio"></i> Tampilan peta</button>
                                    <button type="button" data-analysis-scope="all" aria-pressed="false"><i class="bi bi-globe2"></i> Seluruh data</button>
                                </div>
                                {{-- Orientasi cetak dari template analisis yang tersedia (map-analysis.js). --}}
                                <div class="analysis-scope analysis-orientation" role="group" aria-label="Orientasi cetak" data-analysis-orientations hidden>
                                    <button type="button" data-analysis-orientation="portrait" aria-pressed="false"><i class="bi bi-file-earmark"></i> Potret</button>
                                    <button type="button" data-analysis-orientation="landscape" aria-pressed="false"><i class="bi bi-file-earmark rotate-90"></i> Lanskap</button>
                                </div>
                                <label class="analysis-template-select" hidden>
                                    <span class="sr-only">Template dokumen</span>
                                    <select data-analysis-template aria-label="Template dokumen untuk orientasi terpilih"></select>
                                </label>
                                <button type="button" class="analysis-print" data-analysis-download title="Unduh laporan PDF A4"><i class="bi bi-file-earmark-arrow-down"></i> <span>Unduh PDF</span></button>
                                <button type="button" class="catalog-icon-btn" data-analysis-close aria-label="Tutup analisis">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </header>
                        <div class="analysis-body" data-analysis-body aria-live="polite"></div>
                    </div>
                </div>

                <div id="catalogModal" class="catalog-modal hidden" role="dialog" aria-modal="true"
                    aria-labelledby="catalog-modal-title">
                    <div class="catalog-dialog">
                        <header class="catalog-header">
                            <h2 id="catalog-modal-title">Katalog Peta</h2>
                            <button type="button" class="catalog-icon-btn" data-catalog-close aria-label="Tutup katalog">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </header>

                        <div class="catalog-body">
                            <nav class="catalog-groups" aria-label="Kelompok data">
                                <ul id="catalog-group-list"></ul>
                            </nav>

                            <section class="catalog-main">
                                <p id="catalog-section-parent" class="catalog-section-parent" hidden></p>
                                <h3 id="catalog-section-title">Semua Mapset</h3>
                                <div class="catalog-toolbar">
                                    <label class="catalog-search">
                                        <i class="bi bi-search"></i>
                                        <span class="sr-only">Cari Mapset</span>
                                        <input type="search" id="catalog-search" autocomplete="off"
                                            placeholder="Cari Mapset">
                                    </label>
                                    <button type="button" id="catalog-filter-toggle" class="catalog-filter-toggle"
                                        aria-expanded="false" aria-controls="catalog-filter-panel">
                                        <i class="bi bi-funnel-fill"></i>
                                        <span>Filter</span>
                                        <span id="filter-summary-count" class="catalog-filter-count hidden"></span>
                                    </button>
                                    <div class="catalog-view-toggle" role="group" aria-label="Tampilan katalog">
                                        <button type="button" data-catalog-view="grid" aria-pressed="true"
                                            aria-label="Tampilan grid"><i class="bi bi-grid-fill"></i></button>
                                        <button type="button" data-catalog-view="list" aria-pressed="false"
                                            aria-label="Tampilan daftar"><i class="bi bi-list-ul"></i></button>
                                    </div>
                                </div>
                                {{-- Filter Data: menyaring kartu katalog, lalu titik/area di peta setelah
                                     "Terapkan Pilihan". Opsinya diisi loadFilterOptionsFromServer() di map.js. --}}
                                <div id="catalog-filter-panel" class="catalog-filter-panel hidden">
                                    <label>
                                        <span>Kabupaten/Kota</span>
                                        <select id="filter-kabupaten">
                                            <option value="">Semua Kabupaten/Kota</option>
                                        </select>
                                    </label>
                                    <label>
                                        <span>Tahun</span>
                                        <select id="filter-tahun">
                                            <option value="">Semua Tahun</option>
                                        </select>
                                    </label>
                                    <label>
                                        <span>OPD Pengelola</span>
                                        <select id="filter-opd">
                                            <option value="">Semua OPD</option>
                                        </select>
                                    </label>
                                    <button id="btn-reset-filter" type="button"
                                        class="catalog-btn-outline">Reset</button>
                                    <p id="catalog-filter-note" class="catalog-filter-note">Filter menampilkan mapset yang
                                        memiliki data sesuai pilihan, dan menyaring titik/area di peta setelah
                                        diterapkan.</p>
                                    <p id="filter-count" class="sr-only" aria-live="polite"></p>
                                </div>
                                <div id="catalog-items" class="catalog-items is-grid"></div>
                            </section>
                        </div>

                        <footer class="catalog-footer">
                            <div class="catalog-summary">
                                <span id="catalog-summary-text">0 layer</span>
                                <button type="button" id="catalog-select-all" class="catalog-chip">
                                    <i class="bi bi-check2-all"></i> <span>Pilih Semua</span>
                                </button>
                            </div>
                            <div class="catalog-actions">
                                <button type="button" class="catalog-btn-outline" data-catalog-close>Tutup</button>
                                <button type="button" id="catalog-apply" class="catalog-btn-primary">
                                    <i class="bi bi-check-lg"></i> Terapkan Pilihan
                                </button>
                            </div>
                        </footer>
                    </div>
                </div>

                {{-- Detail fitur area/garis: panel kanan menutupi sidebar & tombol kontrol kanan (map-feature-detail.js). --}}
                <aside id="feature-drawer" class="feature-panel feature-drawer hidden" role="dialog"
                    aria-labelledby="feature-drawer-title">
                    <header class="feature-panel-header">
                        <h2 id="feature-drawer-title">Detail Fitur</h2>
                        <button type="button" class="catalog-icon-btn" data-feature-close aria-label="Tutup detail">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </header>
                    <div class="feature-panel-body" data-feature-body></div>
                </aside>

                {{-- Detail fitur titik: modal di tengah, di atas panel detail area/garis. --}}
                <div id="feature-modal" class="feature-modal hidden" role="dialog" aria-modal="true"
                    aria-labelledby="feature-modal-title">
                    <div class="feature-panel feature-modal-dialog">
                        <header class="feature-panel-header">
                            <h2 id="feature-modal-title">Detail Fitur</h2>
                            <button type="button" class="catalog-icon-btn" data-feature-close aria-label="Tutup detail">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </header>
                        <div class="feature-panel-body" data-feature-body></div>
                    </div>
                </div>

                {{-- Panel Layer Aktif: daftarnya (#layer-list) diisi map-catalog.js. --}}
                <div id="sidebar-layer"
                    class="absolute top-0 right-0 w-[320px] md:w-[340px] h-screen bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 hidden flex flex-col">
                    <div
                        class="shrink-0 flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
                        <h6 class="text-white mb-0 text-sm font-semibold">Layer Aktif</h6>
                        <button id="btn-close-sidebar-layer"
                            class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                            <i class="bi bi-x-lg text-white"></i>
                        </button>
                    </div>

                    <div class="shrink-0 mb-2 w-full">
                        <div class="flex items-center gap-2 w-full">
                            <button type="button" id="btn-open-catalog" data-open-catalog
                                class="flex-1 min-w-0 h-9 flex items-center justify-center gap-2 rounded-lg bg-[#071a2d] text-white text-sm font-semibold hover:bg-[#0b3a66] transition-colors">
                                <i class="bi bi-grid-3x3-gap-fill"></i>
                                Katalog Peta
                            </button>

                        </div>
                    </div>

                    <div id="layer-list" class="flex-1 min-h-0 overflow-y-auto text-sm">
                    </div>
                </div>

                {{-- Panel Basemap: kartu pratinjau diisi setupUI() di map.js. --}}
                <div id="sidebar-basemap"
                    class="absolute top-0 right-0 w-[320px] md:w-[340px] h-screen bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 hidden">

                    <div
                        class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
                        <h6 class="text-white mb-0 text-sm font-semibold">Basemap</h6>
                        <button id="btn-close-sidebar-basemap"
                            class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                            <i class="bi bi-x-lg text-white"></i>
                        </button>
                    </div>

                    <div id="basemap-list" class="pt-2 max-h-[calc(100vh-250px)] overflow-y-auto text-sm">
                    </div>
                </div>

                {{-- Panel Unduh Peta (map-download.js): area cetak = bingkai pratinjau di peta. --}}
                <div id="sidebar-download"
                    class="absolute top-0 right-0 w-[320px] md:w-[340px] h-screen bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 hidden">

                    <div
                        class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
                        <h6 class="text-white mb-0 text-sm font-semibold">Unduh Peta</h6>
                        <button id="btn-close-sidebar-download" type="button" aria-label="Tutup panel unduh peta"
                            class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                            <i class="bi bi-x-lg text-white"></i>
                        </button>
                    </div>

                    <form id="download-form" class="download-form" novalidate>
                        <p class="download-hint"><i class="bi bi-bounding-box"></i> Area di dalam bingkai pada peta yang akan diunduh. Geser atau perbesar peta untuk mengaturnya.</p>

                        <label class="download-field">
                            <span>Judul peta</span>
                            <input type="text" name="title" maxlength="80" value="Peta Interaktif MARIMOI">
                        </label>

                        <fieldset class="download-field" data-download-orientation>
                            <legend>Orientasi</legend>
                            <div class="download-options">
                                <label><input type="radio" name="orientation" value="landscape" checked><span><i class="bi bi-file-earmark-richtext rotate-90"></i> Lanskap</span></label>
                                <label><input type="radio" name="orientation" value="portrait"><span><i class="bi bi-file-earmark-richtext"></i> Potret</span></label>
                            </div>
                        </fieldset>

                        {{-- Diisi map-download.js dari template aktif (dashboard › Template Dokumen). --}}
                        <div class="download-field" hidden>
                            <label for="download-template">Template dokumen</label>
                            <select id="download-template" name="template" class="download-select"></select>
                            <p class="download-template-single" data-template-single hidden><i class="bi bi-check-circle-fill"></i> Satu-satunya template untuk orientasi ini.</p>
                            <div class="download-template-preview" data-template-preview hidden></div>
                        </div>

                        <fieldset class="download-field">
                            <legend>Ukuran kertas</legend>
                            <div class="download-options is-five">
                                @foreach (['A1', 'A2', 'A3', 'A4', 'A5'] as $paper)
                                    <label><input type="radio" name="paper" value="{{ $paper }}" @checked($paper === 'A4')><span>{{ $paper }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>


                        <fieldset class="download-field">
                            <legend>Resolusi</legend>
                            <div class="download-options is-three">
                                @foreach (['low' => ['Rendah', '96 dpi'], 'medium' => ['Sedang', '150 dpi'], 'high' => ['Tinggi', '300 dpi']] as $value => [$label, $dpi])
                                    <label><input type="radio" name="resolution" value="{{ $value }}" @checked($value === 'medium')><span>{{ $label }}<small>{{ $dpi }}</small></span></label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="download-field">
                            <legend>Format</legend>
                            <div class="download-options">
                                <label><input type="radio" name="format" value="png" checked><span><i class="bi bi-filetype-png"></i> PNG</span></label>
                                <label><input type="radio" name="format" value="pdf"><span><i class="bi bi-filetype-pdf"></i> PDF</span></label>
                            </div>
                        </fieldset>

                        <div class="download-checks">
                            <label class="download-check" data-download-legend>
                                <input type="checkbox" name="legend" checked>
                                <span>Sertakan legenda layer aktif</span>
                            </label>
                            <label class="download-check">
                                <input type="checkbox" name="labels" checked>
                                <span>Tampilkan label fitur</span>
                            </label>
                        </div>

                        <div class="download-summary" data-download-summary aria-live="polite"></div>

                        <div class="download-progress" data-download-progress hidden>
                            <div class="download-progress-track"><span></span></div>
                            <p data-download-progress-text>Menyiapkan…</p>
                        </div>

                        <button type="submit" class="download-submit" data-download-submit><i class="bi bi-download"></i> Unduh Peta</button>
                        <button type="button" class="download-cancel" data-download-cancel hidden><i class="bi bi-x-circle"></i> Batalkan</button>
                        <p class="download-status" data-download-status role="status" hidden></p>
                    </form>
                </div>

                {{-- Panel Legenda: diisi generateLegend() di map.js. --}}
                <div id="sidebar-legend"
                    class="absolute top-0 right-0 w-[320px] md:w-[340px] h-screen bg-slate-50 border border-gray-300 p-4 shadow-lg z-[101] transition-all duration-300 ease-in-out text-gray-900 hidden">

                    <div
                        class="flex justify-between items-center mb-3 bg-gradient-to-br from-[#007fff] to-[#0066cc] text-white py-1 px-2 rounded w-full">
                        <h6 class="text-white mb-0 text-sm font-semibold">Legenda</h6>
                        <button id="btn-close-sidebar-legend"
                            class="text-sm p-1 hover:bg-white/20 rounded transition-colors">
                            <i class="bi bi-x-lg text-white"></i>
                        </button>
                    </div>

                    <div id="legend-content" class="ml-2 max-h-[calc(100vh-250px)] overflow-y-auto">
                    </div>
                </div>

                {{-- Kiri atas: kembali ke Beranda. --}}
                <a id="btn-home-page" class="map-pill" href="{{ route('beranda') }}" aria-label="Beranda">
                    <i class="bi bi-house-fill"></i>
                    <span>Beranda</span>
                </a>

                {{-- Tengah atas: pencarian fitur pada layer aktif (skrip di bagian bawah halaman). --}}
                <div id="map-search-bar" role="search">
                    <label for="map-feature-search" class="sr-only">Cari data pada peta</label>
                    <div class="map-search-field">
                        <i class="bi bi-search"></i>
                        <input type="search" id="map-feature-search" autocomplete="off"
                            placeholder="Cari data pada layer aktif...">
                    </div>
                    <ul id="map-feature-search-results" role="listbox" class="hidden"></ul>
                </div>

                <div id="right-control-stack">
                    {{-- Kanan atas: Masuk, atau Dashboard (admin) & Keluar. --}}
                    <div id="app-control-buttons" class="map-pill-group" role="group" aria-label="Akun">
                        @auth
                            @if (auth()->user()->isAdmin())
                                <a id="btn-dashboard" class="map-pill" href="{{ route('dashboard') }}"
                                    aria-label="Dashboard">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>Dashboard</span>
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                @csrf
                                <button id="btn-logout" class="map-pill" type="submit" aria-label="Keluar">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        @else
                            <a id="btn-login" class="map-pill" href="{{ route('login') }}" aria-label="Masuk">
                                <i class="bi bi-box-arrow-in-right"></i>
                                <span>Masuk</span>
                            </a>
                        @endauth
                    </div>
                </div>

                {{-- Tombol panel kanan: Bantuan, Legenda, Basemap, Layer. --}}
                <div id="sidebar-control-buttons" class="bg-gray-300 shadow-md flex flex-col items-center rounded-none"
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

                    {{-- Analisis layer aktif; nonaktif (aria-disabled) bila belum ada layer aktif. --}}
                    <button id="btn-open-analysis" type="button" aria-haspopup="dialog" aria-controls="analysisModal"
                        class="text-black border border-black/20 border-b border-gray-400 rounded-none bg-white hover:bg-slate-200 px-3 py-2 text-sm transition-colors duration-200"
                        title="Analisis Peta" data-tooltip="Analisis Peta">
                        <i class="bi bi-bar-chart-line-fill"></i>
                    </button>

                    <button id="btn-toggle-sidebar-download" type="button" aria-controls="sidebar-download"
                        class="text-black border border-black/20 rounded-none bg-white hover:bg-slate-200 px-3 py-2 text-sm transition-colors duration-200"
                        title="Unduh Peta" data-tooltip="Unduh Peta">
                        <i class="bi bi-download"></i>
                    </button>
                </div>

                {{-- Kanan bawah: Share peta. --}}
                <div id="nav-control-buttons"
                    class="absolute bottom-[30px] right-2.5 z-[99] bg-gray-300 shadow-md flex flex-col items-center rounded-none"
                    role="group" aria-label="Navigation Control Buttons">

                    <button id="btn-share-map" type="button"
                        class="text-black border border-black/20 rounded-none bg-white hover:bg-slate-200 px-3 py-2 text-sm transition-colors duration-200"
                        title="Share Peta" data-tooltip="Share Peta">
                        <i class="bi bi-share-fill"></i>
                    </button>
                </div>


                {{-- Kiri bawah, sejajar tombol share: HUD koordinat & zoom, dengan skala Leaflet di bawahnya
                     (elemen skala dipindah ke sini oleh skrip HUD). --}}
                <div id="map-bottom-bar">
                    <div id="map-hud" aria-hidden="true"><span>Lat <b id="hud-lat">-</b></span><i></i><span>Lng <b
                                id="hud-lng">-</b></span><i></i><span>Zoom <b id="hud-zoom">-</b></span></div>
                </div>

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
    <script src="{{ asset('frontend/js/leaflet.extra-markers.min.js') }}"></script>

    @if (session('selectedCategory'))
        <script>
            // Mapset yang dipilih dari halaman lain (tautan "Lihat peta" di beranda).
            window.MARIMOI_SELECTED_CATEGORY = @json(session('selectedCategory'));
        </script>
    @endif

    {{-- Konfigurasi dari server untuk skrip peta (URL endpoint, token CSRF, state link share). --}}
    <script>
        window.MARIMOI_CSRF_TOKEN = @json(csrf_token());
        window.MARIMOI_MAP_VERSION_URL = @json(route('interaktif.version'));
        window.MARIMOI_SHARE_STORE_URL = @json(route('interaktif.share.store'));
        window.MARIMOI_SHARE_SHOW_URL_TEMPLATE = @json(route('interaktif.share.show', ':slug'));
        window.MARIMOI_FEATURE_DETAIL_URL_TEMPLATE = @json(route('detail.interaktif', ':uuid'));
        // Template dokumen aktif (dikelola di dashboard) untuk Unduh Peta & cetak Analisis Peta.
        window.MARIMOI_DOCUMENT_TEMPLATES = @json($documentTemplates ?? []);

        @if (isset($sharedMapState))
            window.MARIMOI_SHARED_STATE = @json($sharedMapState);
        @endif

        @if (isset($sharedMapError))
            window.MARIMOI_SHARE_ERROR = @json($sharedMapError);
        @endif
    </script>

    {{-- Urutan penting: map-cache.js & map.js membuat global yang dipakai file map-*.js berikutnya.
         ?v=filemtime memaksa browser mengambil versi terbaru setiap file berubah. --}}
    <script src="{{ asset('frontend/js/map-cache.js') }}?v={{ filemtime(public_path('frontend/js/map-cache.js')) }}">
    </script>
    <script src="{{ asset('frontend/js/map.js') }}?v={{ filemtime(public_path('frontend/js/map.js')) }}"></script>
    <script src="{{ asset('frontend/js/map-catalog.js') }}?v={{ filemtime(public_path('frontend/js/map-catalog.js')) }}">
    </script>
    <script
        src="{{ asset('frontend/js/map-feature-detail.js') }}?v={{ filemtime(public_path('frontend/js/map-feature-detail.js')) }}">
    </script>
    <script src="{{ asset('frontend/js/map-labels.js') }}?v={{ filemtime(public_path('frontend/js/map-labels.js')) }}">
    </script>
    <script src="{{ asset('frontend/js/map-analysis.js') }}?v={{ filemtime(public_path('frontend/js/map-analysis.js')) }}">
    </script>
    <script src="{{ asset('frontend/js/map-download.js') }}?v={{ filemtime(public_path('frontend/js/map-download.js')) }}">
    </script>
    <script src="{{ asset('frontend/js/map-guide.js') }}?v={{ filemtime(public_path('frontend/js/map-guide.js')) }}">
    </script>

    {{-- HUD: koordinat di bawah kursor (atau tengah peta saat kursor di luar peta) dan zoom,
         plus skala jarak. `map` adalah konstanta global dari map.js. --}}
    <script>
        (function() {
            if (typeof map === 'undefined' || typeof L === 'undefined') {
                return;
            }
            var lat = document.getElementById('hud-lat');
            var lng = document.getElementById('hud-lng');
            var zoom = document.getElementById('hud-zoom');
            var pending = null;
            var scale = L.control.scale({
                imperial: false,
                position: 'bottomleft'
            }).addTo(map);
            document.getElementById('map-bottom-bar')?.appendChild(scale.getContainer());

            function showZoom() {
                zoom.textContent = Number(map.getZoom().toFixed(2));
            }

            function showCenter() {
                var c = map.getCenter();
                lat.textContent = c.lat.toFixed(4);
                lng.textContent = c.lng.toFixed(4);
            }
            // Koordinat diperbarui paling banyak sekali per frame agar mousemove tidak membebani.
            map.on('mousemove', function(e) {
                pending = e.latlng;
                if (pending && !map._hudRaf) {
                    map._hudRaf = requestAnimationFrame(function() {
                        lat.textContent = pending.lat.toFixed(4);
                        lng.textContent = pending.lng.toFixed(4);
                        map._hudRaf = 0;
                    });
                }
            });
            map.on('mouseout', showCenter);
            map.on('zoomend', showZoom);
            map.on('moveend', showCenter);
            showZoom();
            showCenter();
        })();
    </script>

    {{-- Pencarian: mencocokkan kata (min. 2 huruf) dengan semua atribut fitur pada layer yang sedang
         tampil, lalu menampilkan maksimal 10 hasil. Memilih hasil memperbesar peta ke fitur itu dan
         membuka popup-nya. `map` dan `layerGroups` adalah global dari map.js. --}}
    <script>
        (function() {
            var input = document.getElementById('map-feature-search');
            var list = document.getElementById('map-feature-search-results');
            if (!input || !list || typeof map === 'undefined' || typeof layerGroups === 'undefined') {
                return;
            }
            var maxResults = 10;
            var debounce = null;
            var matches = [];

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Layer group mapset yang sedang tampil, dari struktur bertingkat layerGroups.
            function collectActiveGroups(node, groups) {
                if (!node) {
                    return groups;
                }
                if (node instanceof L.Layer) {
                    if (map.hasLayer(node)) {
                        groups.push(node);
                    }
                    return groups;
                }
                Object.keys(node).forEach(function(key) {
                    collectActiveGroups(node[key], groups);
                });
                return groups;
            }

            // Semua fitur (Path/Marker asli) di dalam satu layer group.
            function collectFeatureLayers(layer, result) {
                if (layer.feature) {
                    result.push(layer);
                } else if (typeof layer.getLayers === 'function') {
                    layer.getLayers().forEach(function(child) {
                        collectFeatureLayers(child, result);
                    });
                }
                return result;
            }

            // Judul hasil: atribut nama yang umum, atau teks pertama yang bukan URL.
            function featureTitle(props) {
                var preferred = props.KEGIATAN || props.kegiatan || props.nama || props.NAMA || props.name;
                if (preferred) {
                    return String(preferred);
                }
                var firstText = Object.keys(props).map(function(key) {
                    return props[key];
                }).find(function(value) {
                    return typeof value === 'string' && value.trim() !== '' && !/^https?:\/\//.test(value);
                });
                return firstText || 'Tanpa nama';
            }

            function search(term) {
                var needle = term.toLowerCase();
                var found = [];
                var groups = collectActiveGroups(layerGroups, []);
                for (var g = 0; g < groups.length && found.length < maxResults; g++) {
                    var layers = collectFeatureLayers(groups[g], []);
                    for (var i = 0; i < layers.length && found.length < maxResults; i++) {
                        var props = layers[i].feature.properties || {};
                        var isMatch = Object.keys(props).some(function(key) {
                            var value = props[key];
                            return (typeof value === 'string' || typeof value === 'number') &&
                                String(value).toLowerCase().indexOf(needle) !== -1;
                        });
                        if (isMatch) {
                            found.push({
                                layer: layers[i],
                                title: featureTitle(props),
                                subtitle: props.kategori || ''
                            });
                        }
                    }
                }
                return {
                    results: found,
                    hasActiveLayer: groups.length > 0
                };
            }

            function hideResults() {
                list.classList.add('hidden');
                list.innerHTML = '';
                matches = [];
            }

            function renderMessage(message) {
                list.innerHTML = '<li class="search-result-message">' + escapeHtml(message) + '</li>';
                list.classList.remove('hidden');
            }

            function render(term) {
                var outcome = search(term);
                matches = outcome.results;
                if (!outcome.hasActiveLayer) {
                    renderMessage('Aktifkan layer terlebih dahulu melalui menu Layer Peta.');
                    return;
                }
                if (matches.length === 0) {
                    renderMessage('Tidak ada data yang cocok dengan "' + term + '".');
                    return;
                }
                list.innerHTML = matches.map(function(match, index) {
                    return '<li role="option"><button type="button" data-index="' + index + '">' +
                        '<span class="search-result-title">' + escapeHtml(match.title) + '</span>' +
                        (match.subtitle ? '<span class="search-result-subtitle">' + escapeHtml(match.subtitle) +
                            '</span>' : '') +
                        '</button></li>';
                }).join('');
                list.classList.remove('hidden');
            }

            function focusMatch(match) {
                var layer = match.layer;
                var open = function() {
                    if (typeof layer.openPopup === 'function') {
                        layer.openPopup();
                    }
                };
                if (typeof layer.getBounds === 'function') {
                    map.fitBounds(layer.getBounds(), {
                        maxZoom: 17
                    });
                } else if (typeof layer.getLatLng === 'function') {
                    map.setView(layer.getLatLng(), Math.max(map.getZoom(), 16));
                }
                open();
            }

            input.addEventListener('focus', function() {
                if (input.value.trim().length >= 2) {
                    render(input.value.trim());
                }
            });

            input.addEventListener('input', function() {
                clearTimeout(debounce);
                var term = input.value.trim();
                if (term.length < 2) {
                    hideResults();
                    return;
                }
                debounce = setTimeout(function() {
                    render(term);
                }, 200);
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    hideResults();
                    input.blur();
                } else if (e.key === 'Enter' && matches.length > 0) {
                    e.preventDefault();
                    focusMatch(matches[0]);
                    hideResults();
                    input.blur();
                }
            });

            list.addEventListener('click', function(e) {
                var button = e.target.closest('button[data-index]');
                if (button && matches[button.dataset.index]) {
                    focusMatch(matches[button.dataset.index]);
                    hideResults();
                }
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('#map-search-bar')) {
                    hideResults();
                }
            });
        })();
    </script>
@endpush
