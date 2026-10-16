@extends('frontend.layouts.spatial')

@section('title', 'MARIMOI - Manajemen Akselerasi Infrastruktur untuk Monitoring dan Integrasi Wilayah')

@php
    $wrap = 'mx-auto w-full max-w-[73.75rem] px-6';
    $btnPrimary = 'inline-flex items-center gap-2 rounded-full bg-ocean px-7 py-3.5 text-[0.9375rem] font-bold text-white shadow-[0_10px_30px_-12px_rgba(10,132,255,.8)] transition duration-300 ease-out hover:-translate-y-1 hover:shadow-[0_14px_38px_-10px_rgba(32,217,255,.75)]';
    $btnGhost = 'inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-7 py-3.5 text-[0.9375rem] font-bold text-white backdrop-blur-xl transition duration-300 ease-out hover:-translate-y-1 hover:border-white/40';
    $kickerDark = 'mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-aqua before:h-px before:w-7 before:bg-current';
    $kickerLight = 'mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-ocean before:h-px before:w-7 before:bg-current';
    $h2 = 'mb-5 max-w-[16ch] text-4xl font-bold leading-[1.08] tracking-tight md:text-5xl lg:text-6xl';
    $contour = 'h-full w-full [&_path]:fill-none [&_path]:stroke-aqua/10 [&_path]:[vector-effect:non-scaling-stroke]';
    $gridBg = "[background-image:linear-gradient(rgba(32,217,255,.07)_1px,transparent_1px),linear-gradient(90deg,rgba(32,217,255,.07)_1px,transparent_1px)] [background-size:72px_72px] [mask-image:radial-gradient(ellipse_at_65%_45%,#000_20%,transparent_72%)]";
    $topMax = max(1, (int) ($spatial['top'][0]->total ?? 1));
    $alur = [
        ['Data', 'Data spasial dan tematik dari perangkat daerah provinsi dan kabupaten/kota dihimpun dalam satu basis data yang seragam.'],
        ['Spasial', 'Setiap data ditempatkan pada lokasinya di peta, sehingga sebaran pembangunan antarpulau terlihat jelas.'],
        ['Perencanaan', 'Peta dikaitkan dengan prioritas pembangunan daerah, usulan Musrenbang, dan Pokok Pikiran DPRD.'],
        ['Pelaksanaan', 'Kegiatan dan proyek infrastruktur dapat dilihat per lokasi, per tahun, dan per perangkat daerah.'],
        ['Pemantauan', 'Perkembangan pembangunan dipantau, dan masyarakat dapat menyampaikan kondisi infrastruktur di sekitarnya.'],
        ['Keputusan', 'Data yang terpadu menjadi dasar keputusan berbasis bukti serta evaluasi pembangunan.'],
    ];
    $homeData = ['points' => $spatial['points'], 'layers' => $spatial['layers'], 'shapes' => $spatial['shapes']];
    $arrow = '<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
@endphp

@section('main')
    {{-- HERO --}}
    <section id="beranda"
        class="relative isolate min-h-svh overflow-hidden bg-deep bg-[radial-gradient(1200px_700px_at_70%_40%,#0b2a45_0%,#061522_62%)] text-white">
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
        {{-- Di layar sempit teks menumpuk di atas peta titik, jadi lapisan penggelapnya dibuat merata --}}
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-deep/80 via-deep/25 to-transparent max-md:bg-gradient-to-b max-md:from-deep/50 max-md:via-deep/75 max-md:to-deep/40" aria-hidden="true"></div>

        {{-- Layer 6: content --}}
        <div data-hero-speed="0.5" data-hero-fade class="relative z-10 flex min-h-svh items-center will-change-transform">
            <div class="{{ $wrap }} pb-24 pt-32 [@media(max-height:520px)]:pb-10 [@media(max-height:520px)]:pt-24">
                <p class="reveal mb-6 font-grotesk text-xs uppercase tracking-widest text-aqua" data-reveal>Manajemen Akselerasi Infrastruktur untuk Monitoring dan Integrasi Wilayah</p>
                <h1 class="reveal delay-100 max-w-[18ch] text-5xl font-extrabold leading-[1.02] tracking-tight sm:text-6xl lg:text-7xl xl:text-8xl [@media(max-height:520px)]:text-5xl" data-reveal>
                    Memetakan Masa Depan <span class="text-aqua">Maluku Utara.</span>
                </h1>
                <p class="reveal mt-7 max-w-xl text-base text-white/75 delay-200 md:text-lg [@media(max-height:520px)]:mt-4" data-reveal>
                    Sistem digital terpadu Bappeda Provinsi Maluku Utara untuk merencanakan, memantau, dan mengintegrasikan
                    pembangunan infrastruktur daerah, dari pulau ke pulau.
                </p>
                <div class="reveal mt-9 flex flex-wrap gap-3 delay-300 [@media(max-height:520px)]:mt-6 max-sm:flex-col max-sm:[&>a]:justify-center" data-reveal>
                    <a href="{{ route('tampil.interaktif') }}" class="{{ $btnPrimary }}">Jelajahi Peta {!! $arrow !!}</a>
                    <a href="{{ route('tampil.tentang') }}" class="{{ $btnGhost }}">Tentang MARIMOI</a>
                </div>
            </div>
        </div>

        <div class="pointer-events-none absolute bottom-8 left-6 z-10 hidden items-center gap-3 font-grotesk text-[0.6875rem] uppercase tracking-widest text-white/50 md:flex lg:left-[max(1.5rem,calc((100vw-73.75rem)/2))]" aria-hidden="true">
            <i class="block h-11 w-px animate-cue bg-gradient-to-b from-aqua to-transparent motion-reduce:animate-none"></i>Gulir untuk menjelajah
        </div>
        <div class="pointer-events-none absolute bottom-8 right-6 z-10 hidden text-right font-grotesk text-[0.6875rem] uppercase tracking-widest text-white/50 md:block lg:right-[max(1.5rem,calc((100vw-73.75rem)/2))]" aria-hidden="true">
            Maluku Utara · <span class="text-aqua">01°34′ N · 127°48′ E</span><br>
            <span class="text-aqua">{{ number_format($spatial['total'], 0, ',', '.') }}</span> objek spasial terpetakan
        </div>
    </section>

    {{-- 01 SATU WILAYAH. BERAGAM INFORMASI. --}}
    <section id="lapisan" class="relative flex min-h-svh items-center overflow-hidden bg-mist py-24 md:py-32">
        <div data-parallax="0.08" class="pointer-events-none absolute inset-x-0 -inset-y-[5%] will-change-transform" aria-hidden="true">
            <svg class="contours h-full w-full [&_path]:fill-none [&_path]:stroke-ocean/10 [&_path]:[vector-effect:non-scaling-stroke]"></svg>
        </div>
        <div class="{{ $wrap }} relative grid items-center gap-12 lg:grid-cols-[5fr_7fr] lg:gap-20">
            <div>
                <p class="reveal {{ $kickerLight }}" data-reveal>01 · Lapisan data</p>
                <h2 class="reveal delay-100 {{ $h2 }} text-navy" data-reveal>Satu wilayah. Beragam informasi.</h2>
                <p class="reveal max-w-[54ch] text-lg text-slate-600 delay-200" data-reveal>Infrastruktur, permukiman, pertanian, hingga
                    kawasan lindung ditata per tema dalam lapisan yang bisa dibuka bersamaan, dari provinsi hingga kabupaten/kota.</p>
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

    {{-- 02 DATA YANG TERHUBUNG --}}
    <section id="alur" class="relative overflow-hidden bg-deep py-24 text-white md:py-32">
        <div data-parallax="0.06" class="pointer-events-none absolute inset-x-0 -inset-y-[5%] opacity-50 will-change-transform" aria-hidden="true">
            <svg class="contours {{ $contour }}"></svg>
        </div>

        <div class="{{ $wrap }} relative">
            <div class="mx-auto max-w-2xl md:text-center">
                <p class="reveal {{ $kickerDark }} md:justify-center" data-reveal>02 · Alur data</p>
                <h2 class="reveal delay-100 mb-5 text-4xl font-bold leading-[1.08] tracking-tight md:text-5xl lg:text-6xl" data-reveal>Data yang terhubung.</h2>
                <p class="reveal text-lg text-white/70 delay-200" data-reveal>Dari data mentah hingga keputusan, setiap tahap perencanaan, pelaksanaan,
                    pemantauan, dan evaluasi saling menyambung dalam satu alur.</p>
            </div>

            {{-- Alur menurun: nomor di garis tengah, penjelasan & ilustrasi bergantian kiri-kanan.
                 Garis terisi mengikuti scroll dan langkah menyala (data-on) saat dilewati. --}}
            <div id="flow" class="relative mt-16 md:mt-24">
                <div class="pointer-events-none absolute bottom-6 left-6 top-6 w-px -translate-x-1/2 bg-white/10 md:left-1/2" aria-hidden="true">
                    <span id="flowLine" class="block h-0 w-full bg-gradient-to-b from-ocean to-aqua shadow-[0_0_12px_rgba(32,217,255,.7)]"></span>
                </div>
                <ol class="relative">
                @foreach ($alur as $i => [$judul, $isi])
                    @php $kanan = $i % 2 === 1; @endphp
                    <li data-step data-on="false" class="group relative min-h-12 pb-16 pl-20 last:pb-0 md:grid md:grid-cols-2 md:items-center md:gap-24 md:pl-0">
                        <span class="absolute left-0 top-0 z-10 grid h-12 w-12 place-items-center rounded-full border border-white/15 bg-deep font-grotesk text-sm text-white/45 transition duration-500 group-data-[on=true]:border-aqua group-data-[on=true]:bg-ocean group-data-[on=true]:text-white group-data-[on=true]:shadow-[0_0_0_8px_rgba(32,217,255,.12),0_0_28px_rgba(32,217,255,.5)] md:left-1/2 md:top-1/2 md:-translate-x-1/2 md:-translate-y-1/2" aria-hidden="true">
                            {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <div class="reveal md:row-start-1 {{ $kanan ? 'md:col-start-2' : 'md:col-start-1 md:text-right' }}" data-reveal>
                            <p class="font-grotesk text-xs uppercase tracking-widest text-aqua">Langkah {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</p>
                            <h3 class="mt-2 text-2xl font-bold tracking-tight md:text-3xl">{{ $judul }}</h3>
                            <p class="mt-3 text-[0.9375rem] leading-relaxed text-white/65 md:text-lg {{ $kanan ? '' : 'md:ml-auto' }} max-w-md">{{ $isi }}</p>
                        </div>
                        <div class="hidden h-40 w-full max-w-[13.75rem] md:row-start-1 md:block {{ $kanan ? 'md:col-start-1 md:justify-self-end' : 'md:col-start-2 md:justify-self-start' }}">
                            @include('frontend.partials.flow-illustration', ['i' => $i])
                        </div>
                    </li>
                @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- 03 PETA PEMBANGUNAN TERPADU --}}
    <section id="peta" class="relative bg-gradient-to-b from-deep via-[#08243b] to-deep pb-24 pt-16 text-white md:pb-32 md:pt-20">
        <div class="{{ $wrap }}">
            <p class="reveal {{ $kickerDark }}" data-reveal>03 · Peta interaktif</p>
            <h2 class="reveal delay-100 {{ $h2 }} max-w-[20ch]" data-reveal>Peta Pembangunan Terpadu</h2>
            <p class="reveal max-w-[54ch] text-lg text-white/70 delay-200" data-reveal>Melihat pembangunan Maluku Utara dalam satu perspektif
                spasial. Cari lokasi, nyalakan atau matikan lapisan, lalu pilih titik untuk melihat rinciannya.</p>

            <div class="reveal relative mt-12 h-[min(75svh,40rem)] min-h-[26rem] overflow-hidden rounded-[1.75rem] border border-white/10 bg-[#0a2236] shadow-[0_40px_90px_-40px_rgba(0,0,0,.8)] md:h-[min(78vh,45rem)] md:min-h-[32.5rem]" data-reveal>
                <div id="homeMap" class="absolute inset-0" role="application" aria-label="Peta titik lokasi pembangunan Maluku Utara"></div>
                {{-- Perangkat sentuh: peta baru bisa digeser setelah diketuk, agar tidak menahan scroll halaman --}}
                <div id="mapHint" class="pointer-events-none absolute inset-x-0 top-1/2 z-[790] -translate-y-1/2 mx-auto hidden w-max max-w-[calc(100%-2rem)] rounded-full border border-white/10 bg-slate-950/75 px-4 py-2 text-center text-xs text-white/85 backdrop-blur-md">Ketuk peta untuk menggeser &amp; memperbesar</div>
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
                        <span class="font-grotesk text-[0.6875rem] uppercase tracking-widest text-white/55">Lapisan</span>
                        <button id="layerAll" type="button" class="text-[0.6875rem] text-aqua">Semua / kosongkan</button>
                    </div>
                    <div id="layerList" class="overflow-auto px-2 pb-3 group-data-[collapsed=true]/tools:hidden"></div>
                    <div class="border-t border-white/10 px-4 py-3 space-y-2 group-data-[collapsed=true]/tools:hidden">
                        <select id="homeFilterTahun" class="w-full rounded-lg border-white/10 bg-white/10 text-xs text-white">
                            <option value="" class="text-black">Semua Tahun</option>
                        </select>
                        <select id="homeFilterOpd" class="w-full rounded-lg border-white/10 bg-white/10 text-xs text-white">
                            <option value="" class="text-black">Semua OPD</option>
                        </select>
                    </div>
                </div>

                {{-- Zoom --}}
                <div class="absolute right-4 top-4 z-[800] hidden grid-rows-2 overflow-hidden rounded-2xl border border-white/10 bg-white/10 text-white backdrop-blur-xl md:grid">
                    <button id="zoomIn" type="button" aria-label="Perbesar" class="h-10 w-10 text-xl transition-colors hover:text-aqua">+</button>
                    <button id="zoomOut" type="button" aria-label="Perkecil" class="h-10 w-10 border-t border-white/10 text-xl transition-colors hover:text-aqua">−</button>
                </div>

                {{-- Jumlah titik --}}
                <div class="absolute bottom-4 right-4 z-[800] hidden rounded-2xl border border-white/10 bg-white/10 px-4 py-2.5 font-grotesk text-[0.6875rem] uppercase tracking-widest text-white backdrop-blur-xl md:block">
                    <b id="mapCount" class="font-medium text-aqua">0</b> titik ditampilkan
                </div>

                {{-- Information panel --}}
                <aside id="mapInfo" data-open="false" aria-live="polite"
                    class="pointer-events-none absolute z-[800] translate-y-4 rounded-2xl border border-white/10 bg-white/10 p-5 text-white opacity-0 backdrop-blur-xl transition duration-500 ease-out data-[open=true]:pointer-events-auto data-[open=true]:translate-x-0 data-[open=true]:translate-y-0 data-[open=true]:opacity-100 max-md:inset-x-4 max-md:bottom-4 md:right-4 md:top-20 md:w-[18.75rem] md:translate-x-6 md:translate-y-0">
                    <button id="infoClose" type="button" aria-label="Tutup" class="absolute right-3 top-2 text-2xl leading-none text-white/60 transition-colors hover:text-white">×</button>
                    <div class="mb-3 flex items-center gap-2 font-grotesk text-[0.6875rem] uppercase tracking-widest">
                        <i id="infoDot" class="h-2 w-2 rounded-full bg-aqua"></i><span id="infoLayer"></span>
                    </div>
                    <h3 id="infoTitle" class="mb-3 pr-4 text-lg font-bold leading-snug"></h3>
                    <dl class="mb-4 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-[0.8125rem] text-white/70">
                        <dt class="text-white/45">Tahun</dt><dd id="infoYear"></dd>
                        <dt class="text-white/45">Koordinat</dt><dd id="infoCoord"></dd>
                        <dt id="infoSourceLabel" class="text-white/45">Sumber</dt><dd id="infoSource"></dd>
                        <dt id="infoOpdLabel" class="text-white/45">Instansi</dt><dd id="infoOpd"></dd>
                    </dl>
                    <a href="{{ route('tampil.interaktif') }}" class="inline-flex items-center gap-2 rounded-full bg-ocean px-4 py-2 text-[0.8125rem] font-bold text-white transition duration-300 hover:-translate-y-0.5 hover:shadow-lg">Buka peta lengkap {!! $arrow !!}</a>
                </aside>
            </div>

            <div class="reveal mt-10 text-center" data-reveal>
                <a href="{{ route('tampil.interaktif') }}" class="{{ $btnPrimary }}">Buka Peta Pembangunan {!! $arrow !!}</a>
            </div>
        </div>
    </section>

    {{-- 04 DATA MENJADI INSIGHT --}}
    <section id="insight" class="bg-mist py-24 md:py-32">
        <div class="{{ $wrap }}">
            <p class="reveal {{ $kickerLight }}" data-reveal>04 · Insight</p>
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

    {{-- CTA: gradasi menurun dari biru laut gelap ke warna footer --}}
    <section id="cta" class="relative overflow-hidden bg-gradient-to-b from-[#0b2a45] via-deep to-[#04101a] py-24 text-center text-white md:py-32">
        <div data-parallax="0.08" class="pointer-events-none absolute inset-x-0 -inset-y-[5%] opacity-55 will-change-transform" aria-hidden="true">
            <svg class="contours {{ $contour }}"></svg>
        </div>
        <div class="{{ $wrap }} relative">
            <h2 class="reveal mx-auto mb-5 max-w-[18ch] text-4xl font-bold leading-[1.08] tracking-tight md:text-5xl lg:text-6xl" data-reveal>Bangun Maluku Utara bersama.</h2>
            <p class="reveal mx-auto mb-10 max-w-[54ch] text-lg text-white/70 delay-100" data-reveal><em>Marimoi</em>, bersatu kita teguh. Jelajahi peta pembangunan atau sampaikan kondisi infrastruktur di wilayah Anda.</p>
            <div class="reveal flex flex-wrap justify-center gap-3 delay-200 max-sm:flex-col max-sm:[&>a]:justify-center" data-reveal>
                <a href="{{ route('tampil.interaktif') }}" class="{{ $btnPrimary }}">Jelajahi Peta {!! $arrow !!}</a>
                <a href="{{ route('tampil.aspirasi') }}" class="{{ $btnGhost }}">Sampaikan Aspirasi</a>
            </div>
        </div>
    </section>

    {{-- Templates used by the map script (kept in Blade so Tailwind can see the classes) --}}
    <template id="tplLayerItem">
        <button type="button" data-off="false" class="group flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left text-[0.8125rem] text-white/85 transition hover:bg-white/5 data-[off=true]:opacity-40">
            <i class="js-dot h-2.5 w-2.5 shrink-0 rounded-full group-data-[off=true]:!shadow-none"></i>
            <span class="js-name"></span>
            <em class="js-count ml-auto font-grotesk text-[0.6875rem] not-italic text-white/50"></em>
        </button>
    </template>
    <template id="tplPulse">
        <div class="relative h-[1.375rem] w-[1.375rem]">
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
