@extends('frontend.layouts.spatial')

@section('title', 'MARIMOI - Spatial Intelligence Platform Maluku Utara')

@php
    $wrap = 'mx-auto w-full max-w-[1180px] px-6';
    $btnPrimary = 'inline-flex items-center gap-2 rounded-full bg-ocean px-7 py-3.5 text-[15px] font-bold text-white shadow-[0_10px_30px_-12px_rgba(10,132,255,.8)] transition duration-300 ease-out hover:-translate-y-1 hover:shadow-[0_14px_38px_-10px_rgba(32,217,255,.75)]';
    $btnGhost = 'inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-7 py-3.5 text-[15px] font-bold text-white backdrop-blur-xl transition duration-300 ease-out hover:-translate-y-1 hover:border-white/40';
    $kickerDark = 'mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-aqua before:h-px before:w-7 before:bg-current';
    $kickerLight = 'mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-ocean before:h-px before:w-7 before:bg-current';
    $h2 = 'mb-5 max-w-[16ch] text-4xl font-bold leading-[1.08] tracking-tight md:text-5xl lg:text-6xl';
    $contour = 'h-full w-full [&_path]:fill-none [&_path]:stroke-aqua/10 [&_path]:[vector-effect:non-scaling-stroke]';
    $gridBg = "[background-image:linear-gradient(rgba(32,217,255,.07)_1px,transparent_1px),linear-gradient(90deg,rgba(32,217,255,.07)_1px,transparent_1px)] [background-size:72px_72px] [mask-image:radial-gradient(ellipse_at_65%_45%,#000_20%,transparent_72%)]";
    $topMax = max(1, (int) ($spatial['top'][0]->total ?? 1));
    $alur = [
        ['Data', 'Data spasial dan tematik dari perangkat daerah dihimpun dalam satu basis data.'],
        ['Spasial', 'Setiap data ditempatkan pada koordinat dan kategorinya di atas peta.'],
        ['Program', 'Peta dikaitkan dengan program dan prioritas pembangunan daerah.'],
        ['Pembangunan', 'Sebaran kegiatan dan proyek pembangunan dapat dilihat per lokasi.'],
        ['Monitoring', 'Perkembangan dipantau, dan masyarakat dapat memberi tanggapan.'],
        ['Keputusan', 'Informasi yang terpadu menjadi dasar perencanaan dan keputusan.'],
    ];
    $homeData = ['points' => $spatial['points'], 'layers' => $spatial['layers']];
    $arrow = '<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
@endphp

@section('main')
    {{-- HERO --}}
    <section id="beranda"
        class="relative isolate min-h-screen overflow-hidden bg-deep bg-[radial-gradient(1200px_700px_at_70%_40%,#0b2a45_0%,#061522_62%)] text-white">
        {{-- Layer 1: topographic map --}}
        <div data-hero-speed="0.10" class="pointer-events-none absolute inset-x-0 -inset-y-[6%] will-change-transform" aria-hidden="true">
            <svg class="contours {{ $contour }}"></svg>
        </div>
        {{-- Layer 2: geographic grid --}}
        <div data-hero-speed="0.15" class="pointer-events-none absolute inset-x-0 -inset-y-[6%] will-change-transform {{ $gridBg }}" aria-hidden="true"></div>
        {{-- Layer 3: particles --}}
        <canvas id="heroParticles" data-hero-speed="0.25" class="pointer-events-none absolute inset-0 block h-full w-full will-change-transform" aria-hidden="true"></canvas>
        {{-- Layer 4: data nodes --}}
        <canvas id="heroNodes" data-hero-speed="0.35" class="pointer-events-none absolute inset-0 block h-full w-full will-change-transform" aria-hidden="true"></canvas>
        {{-- Layer 5: gradient overlay --}}
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-deep/80 via-deep/25 to-transparent" aria-hidden="true"></div>

        {{-- Layer 6: content --}}
        <div data-hero-speed="0.5" data-hero-fade class="relative z-10 flex min-h-screen items-center will-change-transform">
            <div class="{{ $wrap }} pb-24 pt-32">
                <p class="reveal mb-6 font-grotesk text-xs uppercase tracking-widest text-aqua" data-reveal>Spatial Intelligence Platform</p>
                <h1 class="reveal delay-100 max-w-[18ch] text-5xl font-extrabold leading-[1.02] tracking-tight sm:text-6xl lg:text-7xl xl:text-8xl" data-reveal>
                    Memetakan Masa Depan <span class="text-aqua">Maluku Utara.</span>
                </h1>
                <p class="reveal mt-7 max-w-xl text-base text-white/75 delay-200 md:text-lg" data-reveal>
                    Platform digital berbasis spasial untuk mengintegrasikan data, memantau pembangunan, dan mendukung
                    pengambilan keputusan di Maluku Utara.
                </p>
                <div class="reveal mt-9 flex flex-wrap gap-3 delay-300" data-reveal>
                    <a href="{{ route('tampil.tematik') }}" class="{{ $btnPrimary }}">Jelajahi Peta {!! $arrow !!}</a>
                    <a href="{{ route('tampil.tentang') }}" class="{{ $btnGhost }}">Tentang MARIMOI</a>
                </div>
            </div>
        </div>

        <div class="pointer-events-none absolute bottom-8 left-6 z-10 hidden items-center gap-3 font-grotesk text-[11px] uppercase tracking-widest text-white/50 md:flex lg:left-[max(1.5rem,calc((100vw-1180px)/2))]" aria-hidden="true">
            <i class="block h-11 w-px animate-cue bg-gradient-to-b from-aqua to-transparent motion-reduce:animate-none"></i>Gulir untuk menjelajah
        </div>
        <div class="pointer-events-none absolute bottom-8 right-6 z-10 hidden text-right font-grotesk text-[11px] uppercase tracking-widest text-white/50 md:block lg:right-[max(1.5rem,calc((100vw-1180px)/2))]" aria-hidden="true">
            Maluku Utara · <span class="text-aqua">01°34′ N · 127°48′ E</span><br>
            <span class="text-aqua">{{ number_format($spatial['total'], 0, ',', '.') }}</span> objek spasial terpetakan
        </div>
    </section>

    {{-- 01 MALUKU UTARA DALAM SATU PERSPEKTIF --}}
    <section id="perspektif" class="relative h-[260vh] bg-deep text-white max-md:h-[220vh] motion-reduce:h-auto">
        <div class="sticky top-0 h-screen overflow-hidden">
            <div data-parallax="0.08" class="pointer-events-none absolute inset-x-0 -inset-y-[6%] opacity-60 will-change-transform {{ $gridBg }}" aria-hidden="true"></div>
            <canvas id="pinCanvas" class="pointer-events-none absolute inset-0 block h-full w-full" aria-hidden="true"></canvas>
            <div class="{{ $wrap }} relative z-10 h-full">
                <div class="flex h-full max-w-[520px] flex-col justify-center max-md:justify-start max-md:pt-28">
                    <p class="mb-4 font-grotesk text-xs uppercase tracking-widest text-aqua">01 · Perspektif</p>
                    <h2 class="mb-6 text-4xl font-bold leading-[1.08] tracking-tight md:text-5xl lg:text-6xl">Maluku Utara dalam satu perspektif.</h2>
                    <div class="relative min-h-[120px]">
                        @foreach ([
                            'Setiap titik adalah lokasi nyata dalam basis data MARIMOI, tersebar dari Morotai hingga Kepulauan Sula dan Taliabu.',
                            'Lokasi saling terhubung menjadi jaringan: fasilitas kesehatan, pendidikan, dan kawasan pembangunan dalam satu gambaran.',
                            'Dari pesisir hingga pulau terluar, semuanya dapat dilihat, dibandingkan, dan dipantau dalam satu peta.',
                        ] as $caption)
                            <p data-caption data-on="false" class="absolute left-0 top-0 translate-y-3 text-white/70 opacity-0 transition duration-700 ease-out data-[on=true]:translate-y-0 data-[on=true]:opacity-100 motion-reduce:first:translate-y-0 motion-reduce:first:opacity-100">{{ $caption }}</p>
                        @endforeach
                    </div>
                    <div class="mt-6 flex items-baseline gap-3 font-grotesk">
                        <b id="pinMeter" class="min-w-[3ch] text-5xl font-medium tracking-tight text-aqua">0</b>
                        <span class="text-xs uppercase tracking-widest text-white/55">titik lokasi terhubung</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 02 SATU WILAYAH. BERAGAM INFORMASI. --}}
    <section id="lapisan" class="relative overflow-hidden bg-mist py-24 md:py-32">
        <div data-parallax="0.08" class="pointer-events-none absolute inset-x-0 -inset-y-[5%] will-change-transform" aria-hidden="true">
            <svg class="contours h-full w-full [&_path]:fill-none [&_path]:stroke-ocean/10 [&_path]:[vector-effect:non-scaling-stroke]"></svg>
        </div>
        <div class="{{ $wrap }} relative grid items-center gap-12 lg:grid-cols-[5fr_7fr] lg:gap-20">
            <div>
                <p class="reveal {{ $kickerLight }}" data-reveal>02 · Lapisan data</p>
                <h2 class="reveal delay-100 {{ $h2 }} text-navy" data-reveal>Satu wilayah. Beragam informasi.</h2>
                <p class="reveal max-w-[54ch] text-lg text-slate-600 delay-200" data-reveal>Kawasan permukiman, pertanian, mangrove, hingga
                    transportasi ditata dalam lapisan yang bisa dibuka bersamaan.</p>
            </div>
            <div class="lg:self-center">
                {{-- Mockup tablet: masuk 3D, melayang, miring mengikuti kursor, garis pindai, dan titik wilayah berdenyut --}}
                <div id="tabletStage" data-reveal class="group relative mx-auto max-w-xl [perspective:1200px] lg:max-w-none">
                    <div class="absolute inset-x-[10%] -bottom-6 h-16 rounded-full bg-ocean/25 blur-3xl" aria-hidden="true"></div>
                    <div class="origin-center [transform:rotateX(16deg)_rotateY(-12deg)_scale(.92)] opacity-0 transition duration-[1200ms] ease-out group-data-[in=true]:[transform:none] group-data-[in=true]:opacity-100 motion-reduce:[transform:none] motion-reduce:opacity-100">
                        <div class="animate-float motion-reduce:animate-none">
                            <div id="tabletTilt" class="relative will-change-transform">
                                <img data-parallax="0.05" src="{{ asset('frontend/img/mockup/tab-mockup.webp') }}"
                                    alt="Peta wilayah kabupaten dan kota Maluku Utara pada tablet" width="1080" height="841" loading="lazy"
                                    class="relative w-full">

                                {{-- Lapisan layar: garis pindai + sorotan kursor, dipotong mengikuti layar tablet --}}
                                <div class="pointer-events-none absolute inset-x-[3.4%] bottom-[5.6%] top-[5.4%] overflow-hidden rounded-[2.2%]" aria-hidden="true">
                                    <div class="absolute inset-x-0 top-0 h-1/5 animate-scan bg-gradient-to-b from-transparent via-aqua/25 to-transparent motion-reduce:hidden"></div>
                                    <div class="absolute inset-0 opacity-0 transition-opacity duration-300 [background:radial-gradient(circle_at_var(--mx,50%)_var(--my,50%),rgba(255,255,255,.2),transparent_42%)] group-hover:opacity-100"></div>
                                </div>

                                {{-- Titik wilayah yang saling terhubung --}}
                                <svg viewBox="0 0 1080 841" class="pointer-events-none absolute inset-0 h-full w-full" fill="none" aria-hidden="true">
                                    <g stroke="#20D9FF" stroke-opacity=".7" stroke-width="2.5" stroke-linecap="round">
                                        @foreach ([[635, 172], [575, 235], [630, 290], [660, 378], [565, 470], [250, 612], [378, 648]] as $k => [$x, $y])
                                            <path d="M556 368 {{ $x < 556 ? 'Q '.(int) ((556 + $x) / 2).' '.($y + 60).' ' : 'L ' }}{{ $x }} {{ $y }}" pathLength="1"
                                                class="[stroke-dasharray:1] [stroke-dashoffset:1] group-data-[in=true]:animate-draw" style="animation-delay: {{ 0.6 + $k * 0.15 }}s"/>
                                        @endforeach
                                    </g>
                                    @foreach ([[556, 368, true], [635, 172, false], [575, 235, false], [630, 290, false], [660, 378, false], [565, 470, false], [250, 612, false], [378, 648, false]] as $k => [$x, $y, $hub])
                                        <g>
                                            <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $hub ? 10 : 7 }}" fill="#20D9FF" stroke="#061522" stroke-width="3"/>
                                            <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $hub ? 10 : 7 }}" stroke="#20D9FF" stroke-width="2.5" class="origin-center [transform-box:fill-box] motion-safe:animate-ring" style="animation-delay: {{ $k * 0.35 }}s"/>
                                        </g>
                                    @endforeach
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 03 DATA YANG TERHUBUNG --}}
    <section id="alur" class="relative h-[320vh] bg-deep text-white max-md:h-[300vh] motion-reduce:h-auto">
        <div class="sticky top-0 flex h-screen flex-col justify-center overflow-hidden motion-reduce:relative motion-reduce:h-auto motion-reduce:py-24">
            <div data-parallax="0.06" class="pointer-events-none absolute inset-x-0 -inset-y-[5%] opacity-50 will-change-transform" aria-hidden="true">
                <svg class="contours {{ $contour }}"></svg>
            </div>
            <div class="pointer-events-none absolute left-1/2 top-1/2 h-[620px] w-[920px] -translate-x-1/2 -translate-y-1/2 bg-[radial-gradient(closest-side,rgba(10,132,255,.16),transparent)]" aria-hidden="true"></div>

            <div class="{{ $wrap }} relative">
                <p class="{{ $kickerDark }}">03 · Alur data</p>
                <h2 class="mb-3 max-w-[16ch] text-3xl font-bold leading-[1.08] tracking-tight md:text-5xl lg:text-6xl">Data yang terhubung.</h2>
                <p class="hidden max-w-[54ch] text-lg text-white/70 md:block">Dari data mentah hingga keputusan, setiap tahap saling menyambung dalam satu alur yang dapat ditelusuri.</p>

                {{-- Jalur data: kanvas menggambar garis, komet, dan partikel di belakang simpul --}}
                <div id="flow" class="relative mt-8 md:mt-12">
                    <canvas id="flowCanvas" class="pointer-events-none absolute inset-0 h-full w-full" aria-hidden="true"></canvas>
                    <ol class="relative grid grid-cols-6">
                        @foreach ($alur as $i => [$judul, $isi])
                            <li data-step data-on="false" data-active="false" class="group/n relative flex flex-col items-center text-center">
                                <button type="button" data-dot aria-label="Langkah {{ $i + 1 }}: {{ $judul }}"
                                    class="relative z-10 grid h-11 w-11 place-items-center rounded-full border border-white/15 bg-deep font-grotesk text-xs text-white/45 transition duration-500 hover:border-aqua/60 group-data-[on=true]/n:border-aqua group-data-[on=true]/n:text-aqua group-data-[active=true]/n:scale-125 group-data-[active=true]/n:bg-ocean group-data-[active=true]/n:text-white group-data-[active=true]/n:shadow-[0_0_0_8px_rgba(32,217,255,.12),0_0_32px_rgba(32,217,255,.6)] md:h-12 md:w-12">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                    <i class="pointer-events-none absolute inset-0 hidden rounded-full border border-aqua group-data-[active=true]/n:block motion-safe:animate-ring"></i>
                                </button>
                                <span class="mt-4 hidden text-sm font-semibold text-white/40 transition-colors duration-500 group-data-[on=true]/n:text-white md:block">{{ $judul }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Panel langkah aktif --}}
                <div id="flowPanels" class="mt-8 grid md:mt-12 motion-reduce:flex motion-reduce:flex-col motion-reduce:gap-8 [&>*]:col-start-1 [&>*]:row-start-1">
                    @foreach ($alur as $i => [$judul, $isi])
                        <article data-panel data-on="false" aria-hidden="true"
                            class="group invisible grid translate-y-6 items-center gap-6 rounded-3xl border border-white/10 bg-white/[0.07] p-6 opacity-0 backdrop-blur-md transition-[opacity,transform,visibility] duration-700 ease-out pointer-events-none data-[on=true]:pointer-events-auto data-[on=true]:visible data-[on=true]:translate-y-0 data-[on=true]:opacity-100 md:grid-cols-[1fr_220px] md:gap-10 md:p-10 motion-reduce:!pointer-events-auto motion-reduce:!visible motion-reduce:!translate-y-0 motion-reduce:!opacity-100">
                            <div class="relative">
                                <span class="block select-none font-grotesk text-[5.5rem] font-medium leading-none tracking-tighter text-transparent [-webkit-text-stroke:1px_rgba(32,217,255,.4)] md:text-[8rem]" aria-hidden="true">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <h3 class="-mt-4 text-2xl font-bold tracking-tight md:-mt-6 md:text-4xl">{{ $judul }}</h3>
                                <p class="mt-3 max-w-md text-[15px] leading-relaxed text-white/65 md:text-lg">{{ $isi }}</p>
                            </div>
                            <div class="mx-auto hidden h-40 w-full max-w-[220px] sm:block md:h-44">
                                @include('frontend.partials.flow-illustration', ['i' => $i])
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- 04 PETA PEMBANGUNAN TERPADU --}}
    <section id="peta" class="relative bg-gradient-to-b from-deep via-[#08243b] to-deep pb-24 pt-10 text-white md:pb-32">
        <div class="{{ $wrap }}">
            <p class="reveal {{ $kickerDark }}" data-reveal>04 · Peta interaktif</p>
            <h2 class="reveal delay-100 {{ $h2 }} max-w-[20ch]" data-reveal>Peta Pembangunan Terpadu</h2>
            <p class="reveal max-w-[54ch] text-lg text-white/70 delay-200" data-reveal>Melihat pembangunan Maluku Utara dalam satu perspektif
                spasial. Cari lokasi, nyalakan atau matikan lapisan, lalu pilih titik untuk melihat rinciannya.</p>

            <div class="reveal relative mt-12 h-[640px] min-h-[520px] overflow-hidden rounded-[28px] border border-white/10 bg-[#0a2236] shadow-[0_40px_90px_-40px_rgba(0,0,0,.8)] md:h-[min(78vh,720px)]" data-reveal>
                <div id="homeMap" class="absolute inset-0" role="application" aria-label="Peta titik lokasi pembangunan Maluku Utara"></div>
                <div id="mapLoading" class="absolute inset-0 z-[700] grid place-items-center font-grotesk text-xs uppercase tracking-widest text-white/50">Memuat peta…</div>

                {{-- Search + layer control --}}
                <div id="mapTools" data-collapsed="false" class="group/tools absolute left-4 top-4 z-[800] flex max-h-[calc(100%-2rem)] w-[calc(100%-2rem)] flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/10 text-white backdrop-blur-xl md:w-72">
                    <div class="flex items-center gap-3 border-b border-white/10 px-4 py-3">
                        <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0 text-white/60" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input id="mapSearch" type="search" placeholder="Cari lokasi atau kategori" aria-label="Cari lokasi"
                            class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-white outline-none placeholder:text-white/45 focus:border-0 focus:ring-0">
                        <button id="toolsToggle" type="button" aria-label="Tampilkan atau sembunyikan lapisan" class="grid h-7 w-7 place-items-center rounded-md text-white/80 md:hidden">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5ZM3 13l9 5 9-5"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center justify-between px-4 pb-1 pt-3 group-data-[collapsed=true]/tools:hidden">
                        <span class="font-grotesk text-[11px] uppercase tracking-widest text-white/55">Lapisan</span>
                        <button id="layerAll" type="button" class="text-[11px] text-aqua">Semua / kosongkan</button>
                    </div>
                    <div id="layerList" class="overflow-auto px-2 pb-3 group-data-[collapsed=true]/tools:hidden"></div>
                </div>

                {{-- Zoom --}}
                <div class="absolute right-4 top-4 z-[800] hidden grid-rows-2 overflow-hidden rounded-2xl border border-white/10 bg-white/10 text-white backdrop-blur-xl md:grid">
                    <button id="zoomIn" type="button" aria-label="Perbesar" class="h-10 w-10 text-xl transition-colors hover:text-aqua">+</button>
                    <button id="zoomOut" type="button" aria-label="Perkecil" class="h-10 w-10 border-t border-white/10 text-xl transition-colors hover:text-aqua">−</button>
                </div>

                {{-- Jumlah titik --}}
                <div class="absolute bottom-4 right-4 z-[800] hidden rounded-2xl border border-white/10 bg-white/10 px-4 py-2.5 font-grotesk text-[11px] uppercase tracking-widest text-white backdrop-blur-xl md:block">
                    <b id="mapCount" class="font-medium text-aqua">0</b> titik ditampilkan
                </div>

                {{-- Information panel --}}
                <aside id="mapInfo" data-open="false" aria-live="polite"
                    class="pointer-events-none absolute z-[800] translate-y-4 rounded-2xl border border-white/10 bg-white/10 p-5 text-white opacity-0 backdrop-blur-xl transition duration-500 ease-out data-[open=true]:pointer-events-auto data-[open=true]:translate-x-0 data-[open=true]:translate-y-0 data-[open=true]:opacity-100 max-md:inset-x-4 max-md:bottom-4 md:right-4 md:top-20 md:w-[300px] md:translate-x-6 md:translate-y-0">
                    <button id="infoClose" type="button" aria-label="Tutup" class="absolute right-3 top-2 text-2xl leading-none text-white/60 transition-colors hover:text-white">×</button>
                    <div class="mb-3 flex items-center gap-2 font-grotesk text-[11px] uppercase tracking-widest">
                        <i id="infoDot" class="h-2 w-2 rounded-full bg-aqua"></i><span id="infoLayer"></span>
                    </div>
                    <h3 id="infoTitle" class="mb-3 pr-4 text-lg font-bold leading-snug"></h3>
                    <dl class="mb-4 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-[13px] text-white/70">
                        <dt class="text-white/45">Tahun</dt><dd id="infoYear"></dd>
                        <dt class="text-white/45">Koordinat</dt><dd id="infoCoord"></dd>
                    </dl>
                    <a href="{{ route('tampil.tematik') }}" class="inline-flex items-center gap-2 rounded-full bg-ocean px-4 py-2 text-[13px] font-bold text-white transition duration-300 hover:-translate-y-0.5 hover:shadow-lg">Buka peta lengkap {!! $arrow !!}</a>
                </aside>
            </div>

            <div class="reveal mt-10 text-center" data-reveal>
                <a href="{{ route('tampil.tematik') }}" class="{{ $btnPrimary }}">Buka Peta Pembangunan {!! $arrow !!}</a>
            </div>
        </div>
    </section>

    {{-- 05 DATA MENJADI INSIGHT --}}
    <section id="insight" class="bg-mist py-24 md:py-32">
        <div class="{{ $wrap }}">
            <p class="reveal {{ $kickerLight }}" data-reveal>05 · Insight</p>
            <h2 class="reveal delay-100 {{ $h2 }} text-navy" data-reveal>Data menjadi insight.</h2>
            <div class="mt-14 grid grid-cols-2 gap-x-6 gap-y-10 border-t border-slate-900/10 pt-10 lg:grid-cols-4 lg:gap-x-0 lg:gap-y-0">
                @foreach ([
                    [1, 'Provinsi', 2],
                    [10, 'Kabupaten/Kota', 2],
                    [$spatial['total'], 'Objek data spasial', 0],
                    [$spatial['categories'], 'Kategori peta aktif', 0],
                ] as $i => [$angka, $label, $pad])
                    <div class="reveal lg:border-l lg:border-slate-900/10 lg:pl-6 lg:first:border-l-0 lg:first:pl-0" data-reveal style="transition-delay: {{ $i * 80 }}ms">
                        <b class="block font-grotesk text-5xl font-medium leading-none tracking-tighter text-navy md:text-7xl" data-count="{{ $angka }}" @if ($pad) data-pad="{{ $pad }}" @endif>{{ $pad ? str_pad($angka, $pad, '0', STR_PAD_LEFT) : $angka }}</b>
                        <span class="mt-3 block text-slate-500">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
            <div class="reveal mt-10 flex flex-wrap gap-x-10 gap-y-2 text-slate-500" data-reveal>
                <span><b class="font-grotesk font-medium text-slate-900">{{ number_format($totalUsulan + $totalKritik, 0, ',', '.') }}</b> aspirasi masyarakat diterima</span>
                <span><b class="font-grotesk font-medium text-slate-900">{{ number_format($totalPublikasi, 0, ',', '.') }}</b> dokumen publikasi</span>
                <span><b class="font-grotesk font-medium text-slate-900">{{ number_format(count($spatial['points']), 0, ',', '.') }}</b> titik lokasi pada peta interaktif</span>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="relative overflow-hidden bg-deep py-24 text-center text-white md:py-32">
        <div data-parallax="0.08" class="pointer-events-none absolute inset-x-0 -inset-y-[5%] opacity-55 will-change-transform" aria-hidden="true">
            <svg class="contours {{ $contour }}"></svg>
        </div>
        <div class="{{ $wrap }} relative">
            <h2 class="reveal mx-auto mb-5 max-w-[18ch] text-4xl font-bold leading-[1.08] tracking-tight md:text-5xl lg:text-6xl" data-reveal>Bangun Maluku Utara bersama.</h2>
            <p class="reveal mx-auto mb-10 max-w-[54ch] text-lg text-white/70 delay-100" data-reveal>Jelajahi peta pembangunan atau sampaikan aspirasi untuk wilayah Anda.</p>
            <div class="reveal flex flex-wrap justify-center gap-3 delay-200" data-reveal>
                <a href="{{ route('tampil.tematik') }}" class="{{ $btnPrimary }}">Jelajahi Peta {!! $arrow !!}</a>
                <a href="{{ route('tampil.aspirasi') }}" class="{{ $btnGhost }}">Sampaikan Aspirasi</a>
            </div>
        </div>
    </section>

    {{-- Templates used by the map script (kept in Blade so Tailwind can see the classes) --}}
    <template id="tplLayerItem">
        <button type="button" data-off="false" class="group flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left text-[13px] text-white/85 transition hover:bg-white/5 data-[off=true]:opacity-40">
            <i class="js-dot h-2.5 w-2.5 shrink-0 rounded-full group-data-[off=true]:!shadow-none"></i>
            <span class="js-name"></span>
            <em class="js-count ml-auto font-grotesk text-[11px] not-italic text-white/50"></em>
        </button>
    </template>
    <template id="tplPulse">
        <div class="relative h-[22px] w-[22px]">
            <i class="absolute inset-0 animate-ring rounded-full border-2 border-aqua"></i>
            <i class="absolute inset-0 animate-ring rounded-full border-2 border-aqua [animation-delay:.9s]"></i>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        window.MARIMOI_HOME = @json($homeData);
    </script>
@endpush
