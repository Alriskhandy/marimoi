@extends('frontend.layouts.spatial', ['title' => 'Tentang MARIMOI', 'heroTitle' => 'Tentang MARIMOI'])

@section('subtitle', 'Sistem digital terpadu BAPPEDA Provinsi Maluku Utara untuk memperkuat koordinasi, pemantauan, dan integrasi pembangunan infrastruktur.')

@php
    $pendekatan = [
        ['Spatial Intelligence', 'Data pembangunan dibaca melalui lokasi, sehingga hubungan antarwilayah dan antarsektor terlihat jelas.'],
        ['Interactive Mapping', 'Peta interaktif dengan layer, legenda, dan detail per objek yang dapat dijelajahi siapa saja.'],
        ['Data Integration', 'Data dari berbagai perangkat daerah dihimpun ke satu basis data yang seragam.'],
        ['Infrastructure Monitoring', 'Perkembangan pembangunan infrastruktur dapat dipantau dan ditanggapi.'],
        ['Regional Development Planning', 'Mendukung perencanaan pembangunan lintas sektor secara kolaboratif.'],
        ['Public Transparency', 'Informasi pembangunan terbuka bagi masyarakat, lengkap dengan saluran aspirasi.'],
    ];
    $filosofi = [
        ['Huruf “M”', 'filosofi-1.png', 'Huruf "M" adalah inisial MARIMOI. Bentuknya dibuat sederhana dan tegas untuk menyampaikan identitas platform yang mudah dikenali, stabil, dan dapat diandalkan sebagai portal informasi spasial.'],
        ['Kebersamaan', 'filosofi-2.png', 'Dua figur yang saling berdekatan mewakili semangat gotong royong, kolaborasi, dan hubungan antar-komunitas. Warna biru yang dominan menunjukkan kepercayaan dan keterhubungan antarwilayah.'],
        ['Lokasi', 'filosofi-3.png', 'Elemen lokasi (pin) menegaskan fokus MARIMOI pada peta dan data spasial. Bentuknya menyatukan aspek identitas dan konteks geografis.'],
    ];
    $layanan = [
        ['Peta Tematik', route('tampil.tematik')],
        ['Prioritas Daerah 2025-2029', route('tampil.prioritas')],
        ['Dokumen Publikasi', route('tampil.publikasi')],
        ['Aspirasi Masyarakat', route('tampil.aspirasi')],
        ['Profil Reformer', route('tampil.reformer')],
    ];
    $kicker = 'mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-ocean before:h-px before:w-7 before:bg-current';
    $h2 = 'mb-5 text-3xl font-bold leading-[1.1] tracking-tight text-navy md:text-4xl lg:text-5xl';
@endphp

@section('main')
    {{-- Apa itu MARIMOI --}}
    <section class="bg-mist py-20 md:py-28">
        <div class="mx-auto grid w-full max-w-[1180px] gap-12 px-6 lg:grid-cols-[5fr_7fr] lg:gap-20">
            <div>
                <p class="reveal {{ $kicker }}" data-reveal>Tentang</p>
                <h2 class="reveal delay-100 {{ $h2 }}" data-reveal>Memetakan pembangunan, menghubungkan Maluku Utara.</h2>
            </div>
            <div class="reveal space-y-5 text-lg text-slate-600 delay-200" data-reveal>
                <p>MARIMOI adalah <b class="text-navy">Manajemen Akselerasi Infrastruktur untuk Monitoring dan Integrasi Wilayah</b>,
                    platform digital berbasis spasial yang dikelola BAPPEDA Provinsi Maluku Utara.</p>
                <p>Platform ini mengintegrasikan data, memantau pembangunan, dan mendukung pengambilan keputusan melalui peta
                    tematik, proyek strategis, usulan Musrenbang, Pokok Pikiran DPRD, dokumen publikasi, dan aspirasi
                    masyarakat, dalam satu tempat yang terbuka untuk semua.</p>
                <dl class="grid grid-cols-3 gap-6 border-t border-slate-900/10 pt-6">
                    <div><dt class="text-sm text-slate-500">Objek data spasial</dt><dd class="font-grotesk text-3xl font-medium text-navy md:text-4xl">{{ number_format($spatial['total'], 0, ',', '.') }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Kategori peta aktif</dt><dd class="font-grotesk text-3xl font-medium text-navy md:text-4xl">{{ number_format($spatial['categories'], 0, ',', '.') }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Dokumen publikasi</dt><dd class="font-grotesk text-3xl font-medium text-navy md:text-4xl">{{ number_format($totalPublikasi, 0, ',', '.') }}</dd></div>
                </dl>
            </div>
        </div>
    </section>

    {{-- Video profil MARIMOI --}}
    <section id="video" class="border-t border-slate-900/10 bg-white py-20 md:py-28">
        <div class="mx-auto w-full max-w-[1180px] px-6">
            <div class="mb-12 max-w-2xl">
                <p class="reveal {{ $kicker }}" data-reveal>Video</p>
                <h2 class="reveal delay-100 {{ $h2 }}" data-reveal>Kenali MARIMOI dalam video.</h2>
                <p class="reveal text-lg text-slate-600 delay-200" data-reveal>Tonton pengenalan singkat tentang MARIMOI dan bagaimana platform ini mendukung perencanaan pembangunan Maluku Utara.</p>
            </div>

            <button type="button" data-video-id="rxI6vk7dFGw" data-video-title="Video profil MARIMOI" aria-label="Putar video profil MARIMOI"
                class="group reveal-blur relative block aspect-video w-full overflow-hidden rounded-3xl bg-navy text-left shadow-[0_40px_80px_-40px_rgba(7,26,45,.6)]" data-reveal>
                <img src="https://img.youtube.com/vi/rxI6vk7dFGw/maxresdefault.jpg" alt="" loading="lazy"
                    class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                <span class="absolute inset-0 bg-gradient-to-t from-deep/70 via-transparent to-transparent" aria-hidden="true"></span>
                <span class="absolute inset-0 grid place-items-center">
                    <span class="relative grid h-20 w-20 place-items-center rounded-full border border-white/30 bg-slate-950/40 backdrop-blur-md transition duration-300 group-hover:scale-110 group-hover:bg-ocean md:h-24 md:w-24">
                        <span class="absolute inset-0 rounded-full border border-white/40 motion-safe:animate-ring" aria-hidden="true"></span>
                        <svg viewBox="0 0 24 24" class="ml-1 h-8 w-8 text-white md:h-9 md:w-9" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg>
                    </span>
                </span>
                <span class="absolute bottom-5 left-6 right-6 flex items-center justify-between gap-4 text-white">
                    <span class="text-lg font-bold md:text-xl">Video profil MARIMOI</span>
                    <span class="font-grotesk text-xs uppercase tracking-widest text-white/70">YouTube</span>
                </span>
            </button>
        </div>
    </section>

    {{-- Pendekatan --}}
    <section class="relative overflow-hidden bg-deep py-20 text-white md:py-28">
        <div data-parallax="0.06" class="pointer-events-none absolute inset-x-0 -inset-y-[8%] opacity-50 will-change-transform" aria-hidden="true">
            <svg class="contours h-full w-full [&_path]:fill-none [&_path]:stroke-aqua/10 [&_path]:[vector-effect:non-scaling-stroke]"></svg>
        </div>
        <div class="relative mx-auto w-full max-w-[1180px] px-6">
            <p class="reveal mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-aqua before:h-px before:w-7 before:bg-current" data-reveal>Pendekatan</p>
            <h2 class="reveal mb-14 max-w-[18ch] text-3xl font-bold leading-[1.1] tracking-tight delay-100 md:text-4xl lg:text-5xl" data-reveal>Enam prinsip yang mendasari MARIMOI.</h2>
            <div class="grid gap-x-12 gap-y-10 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($pendekatan as $i => [$judul, $isi])
                    <div class="reveal border-t border-white/15 pt-5" data-reveal style="transition-delay: {{ $i * 70 }}ms">
                        <span class="font-grotesk text-xs text-aqua">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="mb-2 mt-2 text-lg font-bold">{{ $judul }}</h3>
                        <p class="text-[15px] leading-relaxed text-white/65">{{ $isi }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Kutipan --}}
    <section class="bg-mist py-20 md:py-24">
        <figure class="reveal mx-auto w-full max-w-4xl px-6 text-center" data-reveal>
            <blockquote class="text-2xl font-semibold leading-snug tracking-tight text-navy md:text-3xl">
                “Dengan MARIMOI, kita tidak hanya menguatkan koordinasi lintas sektor, tetapi juga membuka ruang
                partisipasi masyarakat secara luas, sehingga pembangunan Maluku Utara dapat lebih terarah, transparan,
                dan sesuai dengan kebutuhan masyarakat.”
            </blockquote>
            <figcaption class="mt-6 text-slate-500">Sherly Tjoanda Laos, <span class="text-slate-400">Gubernur Provinsi Maluku Utara</span></figcaption>
        </figure>
    </section>

    {{-- Dukungan terhadap MARIMOI --}}
    <section id="dukungan" class="bg-deep py-20 text-white md:py-28">
        <div class="mx-auto w-full max-w-[1180px] px-6">
            <p class="reveal mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-aqua before:h-px before:w-7 before:bg-current" data-reveal>Testimonial</p>
            <h2 class="reveal mb-5 max-w-[18ch] text-3xl font-bold leading-[1.1] tracking-tight delay-100 md:text-4xl lg:text-5xl" data-reveal>Dukungan Terhadap MARIMOI</h2>
            <p class="reveal mb-14 max-w-2xl text-lg text-white/70 delay-200" data-reveal>Pesan dan dukungan dari pimpinan daerah, perangkat daerah, dan mitra pembangunan
                untuk mewujudkan perencanaan yang terintegrasi di Maluku Utara.</p>

            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($dukungan as $i => $video)
                    <button type="button" data-video-id="{{ $video['id'] }}" data-video-title="{{ $video['title'] }}"
                        aria-label="Putar video: {{ $video['title'] }}"
                        class="group reveal text-left" data-reveal style="transition-delay: {{ ($i % 3) * 80 }}ms">
                        <span class="relative block aspect-video overflow-hidden rounded-2xl border border-white/10 bg-navy">
                            <img src="https://img.youtube.com/vi/{{ $video['id'] }}/hqdefault.jpg" alt="" loading="lazy"
                                class="h-full w-full object-cover opacity-80 transition duration-500 group-hover:scale-105 group-hover:opacity-100">
                            <span class="absolute inset-0 grid place-items-center">
                                <span class="grid h-14 w-14 place-items-center rounded-full border border-white/25 bg-slate-950/50 backdrop-blur-md transition duration-300 group-hover:scale-110 group-hover:border-aqua/60 group-hover:bg-ocean">
                                    <svg viewBox="0 0 24 24" class="ml-0.5 h-5 w-5 text-white" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg>
                                </span>
                            </span>
                        </span>
                        <span class="mt-4 block text-[15px] font-semibold leading-snug transition-colors duration-300 group-hover:text-aqua">{{ $video['title'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Video modal (iframe hanya dimuat saat video dibuka) --}}
    <div id="videoModal" data-open="false" role="dialog" aria-modal="true" aria-label="Pemutar video testimonial" aria-hidden="true"
        class="invisible fixed inset-0 z-[1200] grid place-items-center bg-slate-950/85 p-4 opacity-0 backdrop-blur-sm transition duration-300 data-[open=true]:visible data-[open=true]:opacity-100">
        <div class="relative w-full max-w-5xl scale-95 transition duration-300 [[data-open=true]_&]:scale-100">
            <button id="videoClose" type="button" aria-label="Tutup video"
                class="absolute -top-12 right-0 grid h-10 w-10 place-items-center rounded-full border border-white/20 bg-white/10 text-white transition-colors hover:bg-white/20">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
            <div class="aspect-video overflow-hidden rounded-2xl bg-black shadow-2xl">
                <iframe id="videoFrame" title="Video testimonial" class="h-full w-full border-0" src=""
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen"
                    allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
            <p id="videoCaption" class="mt-4 text-center text-sm text-white/70"></p>
        </div>
    </div>

    {{-- Filosofi logo --}}
    <section class="border-t border-slate-900/10 bg-white py-20 md:py-28">
        <div class="mx-auto w-full max-w-[1180px] px-6">
            <p class="reveal {{ $kicker }}" data-reveal>Filosofi logo</p>
            <h2 class="reveal delay-100 {{ $h2 }} max-w-[20ch]" data-reveal>Identitas, kebersamaan, dan lokasi.</h2>
            <p class="reveal mb-14 max-w-2xl text-lg text-slate-600 delay-200" data-reveal>Penjabaran elemen visual logo MARIMOI yang merepresentasikan identitas,
                nilai kebersamaan, dan fokus spasial dari platform ini.</p>
            <div class="grid gap-10 md:grid-cols-3">
                @foreach ($filosofi as $i => [$judul, $gambar, $isi])
                    <div class="reveal" data-reveal style="transition-delay: {{ $i * 80 }}ms">
                        <div class="mb-6 grid aspect-[4/3] place-items-center overflow-hidden rounded-3xl bg-mist">
                            <img src="{{ asset('frontend/img/filosofi/'.$gambar) }}" alt="{{ $judul }} - Filosofi logo" class="max-h-[80%] w-auto" loading="lazy">
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-navy">{{ $judul }}</h3>
                        <p class="text-[15px] leading-relaxed text-slate-600">{{ $isi }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Jelajahi --}}
    <section class="bg-mist py-20 md:py-24">
        <div class="mx-auto grid w-full max-w-[1180px] gap-10 px-6 lg:grid-cols-[5fr_7fr] lg:gap-20">
            <h2 class="reveal {{ $h2 }} !mb-0 max-w-[14ch]" data-reveal>Mulai menjelajah.</h2>
            <div class="reveal border-t border-navy" data-reveal>
                @foreach ($layanan as [$nama, $href])
                    <a href="{{ $href }}" class="group flex items-center justify-between border-b border-slate-900/10 py-5 text-lg font-bold text-navy transition-all duration-300 hover:pl-3 hover:text-ocean">
                        {{ $nama }}
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-ocean transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endsection
