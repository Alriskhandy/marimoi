@php
    use App\Support\HtmlSanitizer;

    $kategori = $project->kategori;
    $warna = $kategori->warna ?? '#0A84FF';
    $geometry = $project->geojson ?? null;
    $geometryLabels = [
        'Point' => 'Titik',
        'MultiPoint' => 'Multi titik',
        'LineString' => 'Garis',
        'MultiLineString' => 'Multi garis',
        'Polygon' => 'Poligon',
        'MultiPolygon' => 'Multi poligon',
        'GeometryCollection' => 'Kumpulan geometri',
    ];
    $geometryLabel = $geometryLabels[$geometry->type ?? ''] ?? 'Geometri';
    $dbfAttributes = is_string($project->dbf_attributes) ? json_decode($project->dbf_attributes, true) : $project->dbf_attributes;
    $dbfAttributes = collect(is_array($dbfAttributes) ? $dbfAttributes : [])
        ->reject(fn ($value, $key) => strtolower((string) $key) === 'id' || $value === null || trim((string) $value) === '');
    $backUrl = url()->previous() === url()->current() ? route('tampil.tematik') : url()->previous();
    $btn = 'inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold transition duration-300 hover:-translate-y-0.5';
@endphp

<section class="bg-mist py-12 md:py-16">
    <div class="mx-auto w-full max-w-[1180px] px-6">
        {{-- Navigasi + aksi --}}
        <div class="reveal mb-6 flex flex-wrap items-center justify-between gap-3" data-reveal>
            <a href="{{ $backUrl }}" class="{{ $btn }} border border-slate-900/10 bg-white text-slate-700 hover:border-ocean/40 hover:text-ocean">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
                Kembali
            </a>
            <div class="flex flex-wrap gap-2">
                <button type="button" id="copyDetailLink" class="{{ $btn }} border border-slate-900/10 bg-white text-slate-700 hover:border-ocean/40 hover:text-ocean">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>
                    <span data-label>Salin tautan</span>
                </button>
                <a href="{{ route('tampil.tematik') }}" class="{{ $btn }} bg-ocean text-white shadow-[0_10px_30px_-12px_rgba(10,132,255,.8)]">Buka Peta Tematik</a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[7fr_5fr] lg:items-start">
            {{-- Peta --}}
            <div class="reveal-blur relative overflow-hidden rounded-3xl border border-slate-900/10 bg-navy shadow-[0_30px_70px_-40px_rgba(7,26,45,.6)]" data-reveal>
                <div id="map-detail" class="z-0 h-[420px] w-full bg-navy sm:h-[520px] lg:h-[620px]" role="application" aria-label="Peta lokasi {{ $kategori->nama ?? '' }}"></div>

                <div class="pointer-events-none absolute left-4 top-4 z-[500] flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-white/15 bg-slate-950/70 px-3.5 py-1.5 font-grotesk text-[11px] uppercase tracking-widest text-white backdrop-blur-md">{{ $geometryLabel }}</span>
                    <span class="flex items-center gap-2 rounded-full border border-white/15 bg-slate-950/70 px-3.5 py-1.5 text-xs font-semibold text-white backdrop-blur-md">
                        <i class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $warna }}; box-shadow: 0 0 10px {{ $warna }}"></i>{{ $kategori->nama ?? '-' }}
                    </span>
                </div>

                <div class="absolute right-4 top-4 z-[500] flex overflow-hidden rounded-full border border-white/15 bg-slate-950/70 text-xs font-semibold text-white backdrop-blur-md" role="group" aria-label="Jenis peta dasar">
                    <button type="button" data-basemap="satelit" aria-pressed="true" class="px-3.5 py-1.5 transition-colors hover:text-aqua aria-pressed:bg-white/15">Satelit</button>
                    <button type="button" data-basemap="jalan" aria-pressed="false" class="px-3.5 py-1.5 transition-colors hover:text-aqua aria-pressed:bg-white/15">Peta</button>
                </div>

                <div class="absolute bottom-4 left-4 z-[500] flex flex-wrap items-center gap-2">
                    <button type="button" id="copyCoordinates" title="Salin koordinat titik pusat"
                        class="flex items-center gap-2 rounded-full border border-white/15 bg-slate-950/70 px-3.5 py-2 font-grotesk text-[11px] uppercase tracking-widest text-white backdrop-blur-md transition-colors hover:text-aqua">
                        <span class="text-white/50">Pusat</span><span id="centerCoordinates" class="text-aqua">-</span>
                    </button>
                </div>

                <button type="button" id="refocusMap" aria-label="Fokus ke lokasi" title="Fokus ke lokasi"
                    class="absolute bottom-[6.75rem] right-[10px] z-[500] grid h-[34px] w-[34px] place-items-center rounded-[4px] border border-white/15 bg-slate-950/70 text-white backdrop-blur-md transition-colors hover:text-aqua">
                    <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>
                </button>
            </div>

            {{-- Informasi --}}
            <div class="reveal grid gap-6" data-reveal>
                <article class="rounded-3xl border border-slate-900/10 bg-white p-6 md:p-8">
                    <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold" style="color: {{ $warna }}; border-color: {{ $warna }}55; background-color: {{ $warna }}12">
                        <i class="h-2 w-2 rounded-full" style="background-color: {{ $warna }}"></i>{{ $kategori->nama ?? '-' }}
                    </span>
                    <h2 class="mt-4 text-2xl font-extrabold leading-snug tracking-tight text-navy md:text-3xl">{{ \Illuminate\Support\Str::limit(strip_tags($project->deskripsi ?: ($kategori->nama ?? 'Detail Peta')), 140) }}</h2>

                    @if (! empty($project->deskripsi) && strip_tags($project->deskripsi) !== $project->deskripsi)
                        <div class="mt-4 text-[15px] leading-relaxed text-slate-600 [&_a]:text-ocean [&_a]:underline [&_ul]:list-disc [&_ul]:pl-5">{!! HtmlSanitizer::clean($project->deskripsi) !!}</div>
                    @endif

                    <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-900/10 pt-6 text-sm">
                        @if ($project->tahun)
                            <div><dt class="text-slate-500">Tahun</dt><dd class="mt-0.5 font-grotesk text-lg font-medium text-navy">{{ $project->tahun }}</dd></div>
                        @endif
                        <div><dt class="text-slate-500">Dilihat</dt><dd class="mt-0.5 font-grotesk text-lg font-medium text-navy">{{ number_format((int) $project->views, 0, ',', '.') }}x</dd></div>
                        <div><dt class="text-slate-500">Jenis geometri</dt><dd class="mt-0.5 font-semibold text-navy">{{ $geometryLabel }}</dd></div>
                        @if ($project->sub_type)
                            <div><dt class="text-slate-500">Sub tipe</dt><dd class="mt-0.5 font-semibold uppercase text-navy">{{ $project->sub_type }}</dd></div>
                        @endif
                    </dl>
                </article>

                @if (! empty($project->gambar))
                    <div class="overflow-hidden rounded-3xl border border-slate-900/10 bg-white p-2">
                        <img src="{{ asset('storage/' . $project->gambar) }}" alt="{{ strip_tags($project->deskripsi ?? $kategori->nama ?? 'Foto lokasi') }}" loading="lazy"
                            class="h-64 w-full rounded-2xl object-cover">
                    </div>
                @endif

                @if ($dbfAttributes->isNotEmpty())
                    <article class="rounded-3xl border border-slate-900/10 bg-white p-6 md:p-8">
                        <h3 class="mb-4 font-grotesk text-xs uppercase tracking-widest text-ocean">Atribut data</h3>
                        <dl class="divide-y divide-slate-900/10">
                            @foreach ($dbfAttributes as $key => $value)
                                <div class="grid gap-1 py-3 sm:grid-cols-[9rem_1fr] sm:gap-4">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ ucwords(str_replace('_', ' ', (string) $key)) }}</dt>
                                    <dd class="break-words text-[15px] text-navy">{!! HtmlSanitizer::clean($value) !!}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </article>
                @endif
            </div>
        </div>
    </div>
</section>

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            var mapEl = document.getElementById('map-detail');
            if (!mapEl || typeof L === 'undefined') { return; }

            var geometry = @json($geometry);
            var color = @json($warna);
            var DEFAULT_VIEW = { center: [1.5, 127.5], zoom: 8 };
            var MERCATOR = 20037508.34;

            // Beberapa data lama tersimpan dalam meter (Web Mercator) walau berlabel lon/lat.
            // Koordinat di luar rentang derajat dikonversi kembali agar peta tidak terlempar ke tempat yang salah.
            function coordsOutOfRange(coords) {
                if (typeof coords[0] === 'number') {
                    return Math.abs(coords[0]) > 180 || Math.abs(coords[1]) > 90;
                }
                return coords.some(coordsOutOfRange);
            }

            function unproject(coords) {
                if (typeof coords[0] === 'number') {
                    var lon = coords[0] / MERCATOR * 180;
                    var lat = 180 / Math.PI * (2 * Math.atan(Math.exp(coords[1] / MERCATOR * Math.PI)) - Math.PI / 2);
                    return [lon, lat].concat(coords.slice(2));
                }
                return coords.map(unproject);
            }

            function normalize(geom) {
                if (!geom) { return geom; }
                if (geom.type === 'GeometryCollection') {
                    return { type: geom.type, geometries: (geom.geometries || []).map(normalize) };
                }
                if (geom.coordinates && coordsOutOfRange(geom.coordinates)) {
                    return { type: geom.type, coordinates: unproject(geom.coordinates) };
                }
                return geom;
            }

            var map = L.map(mapEl, { zoomControl: false, scrollWheelZoom: false }).setView(DEFAULT_VIEW.center, DEFAULT_VIEW.zoom);
            L.control.zoom({ position: 'bottomright' }).addTo(map);
            map.once('focus', function () { map.scrollWheelZoom.enable(); });
            map.on('click', function () { map.scrollWheelZoom.enable(); });

            var basemaps = {
                satelit: L.layerGroup([
                    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, attribution: 'Tiles &copy; Esri' }),
                    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18 })
                ]),
                jalan: L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' })
            };
            var activeBase = 'satelit';
            basemaps.satelit.addTo(map);

            document.querySelectorAll('[data-basemap]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var next = btn.getAttribute('data-basemap');
                    if (next === activeBase) { return; }
                    map.removeLayer(basemaps[activeBase]);
                    basemaps[next].addTo(map);
                    activeBase = next;
                    document.querySelectorAll('[data-basemap]').forEach(function (b) {
                        b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
                    });
                });
            });

            var layer = null;
            var bounds = null;

            try {
                layer = L.geoJSON(normalize(geometry), {
                    style: function () { return { color: color, weight: 3, opacity: 0.95, fillColor: color, fillOpacity: 0.28 }; },
                    pointToLayer: function (feature, latlng) { return L.marker(latlng); }
                }).addTo(map);
                bounds = layer.getBounds();
            } catch (error) {
                console.warn('Geometri tidak dapat digambar:', error);
            }

            var coordsEl = document.getElementById('centerCoordinates');
            var centerText = '';

            function fit() {
                map.invalidateSize();

                if (!bounds || !bounds.isValid()) {
                    map.setView(DEFAULT_VIEW.center, DEFAULT_VIEW.zoom);
                    return;
                }

                if (bounds.getSouthWest().equals(bounds.getNorthEast())) {
                    map.setView(bounds.getCenter(), 16);
                } else {
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 17 });
                }
            }

            if (bounds && bounds.isValid()) {
                var c = bounds.getCenter();
                centerText = c.lat.toFixed(5) + ', ' + c.lng.toFixed(5);
                coordsEl.textContent = centerText;
            }

            // Ukuran wadah baru pasti setelah layout selesai; fit ulang agar peta tepat di lokasi.
            fit();
            window.addEventListener('load', fit);
            setTimeout(fit, 350);
            if (window.ResizeObserver) {
                var settle = 0;
                new ResizeObserver(function () { if (settle++ < 4) { fit(); } }).observe(mapEl);
            }

            document.getElementById('refocusMap').addEventListener('click', fit);

            function copy(text, button, doneLabel) {
                if (!text || !navigator.clipboard) { return; }
                navigator.clipboard.writeText(text).then(function () {
                    var label = button.querySelector('[data-label]') || button.querySelector('#centerCoordinates');
                    var old = label.textContent;
                    label.textContent = doneLabel;
                    setTimeout(function () { label.textContent = old; }, 1600);
                });
            }

            document.getElementById('copyCoordinates').addEventListener('click', function () {
                copy(centerText, this, 'Tersalin');
            });
            document.getElementById('copyDetailLink').addEventListener('click', function () {
                copy(window.location.href, this, 'Tersalin');
            });
        })();
    </script>
@endpush
