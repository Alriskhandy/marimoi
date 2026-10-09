@extends('frontend.layouts.spatial', ['title' => 'Tentang MARIMOI', 'heroTitle' => 'Tentang MARIMOI'])

@section('subtitle', 'Sistem digital terpadu Bappeda Provinsi Maluku Utara untuk perencanaan, pelaksanaan, pemantauan, dan evaluasi pembangunan infrastruktur daerah.')

@php
    $pendekatan = [
        ['Tematik', 'Data pembangunan disusun per tema, seperti jalan, air bersih, dan permukiman, sehingga mudah ditelusuri dan dibandingkan.'],
        ['Holistik', 'Pembangunan dilihat secara utuh, mulai dari perencanaan, pelaksanaan, pemantauan, hingga evaluasi.'],
        ['Integratif', 'Menyatukan data lintas sektor dan lintas wilayah, serta menghubungkan rencana pembangunan dengan usulan Musrenbang dan Pokok Pikiran DPRD.'],
        ['Spasial', 'Setiap data dibaca melalui lokasinya, sehingga pemerataan pembangunan dan keterhubungan antarpulau terlihat jelas.'],
        ['Digital', 'Perencanaan dan pemantauan yang sebelumnya banyak dilakukan manual beralih ke platform web dan mobile.'],
        ['Partisipatif', 'Masyarakat dapat melihat informasi pembangunan dan menyampaikan kondisi infrastruktur di sekitarnya.'],
    ];
    $filosofi = [
        ['Huruf “M”', 'filosofi-1.png', 'Huruf "M" adalah inisial MARIMOI. Bentuknya dibuat sederhana dan tegas untuk menyampaikan identitas platform yang mudah dikenali, stabil, dan dapat diandalkan sebagai portal informasi spasial.'],
        ['Kebersamaan', 'filosofi-2.png', 'Dua figur yang saling berdekatan mewakili makna marimoi: bersatu dan bergandengan tangan. Semangat gotong royong ini menyatukan pemerintah, DPRD, dan masyarakat dalam membangun daerah. Warna biru yang dominan menunjukkan kepercayaan dan keterhubungan antarwilayah.'],
        ['Lokasi', 'filosofi-3.png', 'Elemen lokasi (pin) menegaskan fokus MARIMOI pada data spasial: setiap rencana dan hasil pembangunan memiliki tempat di peta, dari pulau ke pulau di seluruh Maluku Utara.'],
    ];
    $layanan = [
        ['Peta Interaktif', route('tampil.interaktif')],
        ['Prioritas Daerah 2025-2029', route('tampil.prioritas')],
        ['Dokumen Publikasi', route('tampil.publikasi')],
        ['Aspirasi Masyarakat', route('tampil.aspirasi')],
    ];
    $pendidikan = [
        ['1993', 'SD Kenari Tinggi 4 Ternate', 'Sekolah Dasar', null],
        ['1996', 'SMP Negeri 1 Ternate', 'Sekolah Menengah Pertama', null],
        ['1999', 'SMA Negeri 1 Ternate', 'Sekolah Menengah Atas, Jurusan IPS', null],
        ['2003', 'Sekolah Tinggi Pemerintahan Dalam Negeri', 'S-1 / D-IV', 'S.STP'],
        ['2006', 'Fisipol Universitas Gadjah Mada', 'S-2', 'Magister Ilmu Politik'],
        ['2016', 'Fisipol Universitas Gadjah Mada', 'S-3', 'Doktor Ilmu Politik'],
    ];
    $jabatan = [
        ['2014', 'Kepala Bidang Sosial Budaya', 'BAPPEDA Kabupaten Halmahera Timur', '28-08-2014'],
        ['2017', 'Kepala Bidang Pemerintahan dan Pembangunan Manusia', 'BP4D Kabupaten Halmahera Timur', '06-01-2017'],
        ['2019', 'Sekretaris Dinas', 'DPMD Kabupaten Halmahera Timur', '18-07-2019'],
        ['2020', 'Sekretaris Badan', 'BPPD Provinsi Maluku Utara', '08-04-2020'],
        ['2020', 'Kepala Bidang Pemerintahan dan Sosial Budaya', 'BAPPEDA Provinsi Maluku Utara', '09-2020'],
        ['2023', 'Sekretaris Badan', 'BAPPEDA Provinsi Maluku Utara', '31-03-2023'],
        ['2023', 'Plt. Kepala Badan', 'BAPPEDA Provinsi Maluku Utara', '27-03-2023'],
        ['2023', 'Kepala Badan', 'BAPPEDA Provinsi Maluku Utara', '13-09-2023'],
    ];
    $dokumenCv = [
        ['Halaman 1', 'frontend/img/cv/cv-halaman-1.webp'],
        ['Halaman 2', 'frontend/img/cv/cv-halaman-2.webp'],
        ['Riwayat Jabatan', 'frontend/img/cv/4.jpg'],
    ];
    $contour = 'h-full w-full [&_path]:fill-none [&_path]:stroke-aqua/10 [&_path]:[vector-effect:non-scaling-stroke]';
    $kicker = 'mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-ocean before:h-px before:w-7 before:bg-current';
    $h2 = 'mb-5 text-3xl font-bold leading-[1.1] tracking-tight text-navy md:text-4xl lg:text-5xl';
@endphp

@section('main')
    {{-- Apa itu MARIMOI --}}
    <section class="bg-mist py-20 md:py-28">
        <div class="mx-auto grid w-full max-w-[1180px] gap-12 px-6 lg:grid-cols-[5fr_7fr] lg:gap-20">
            <div>
                <p class="reveal {{ $kicker }}" data-reveal>Tentang</p>
                <h2 class="reveal delay-100 {{ $h2 }}" data-reveal>Bersatu membangun Maluku Utara, dari pulau ke pulau.</h2>
            </div>
            <div class="reveal space-y-5 text-lg text-slate-600 delay-200" data-reveal>
                <p>MARIMOI adalah <b class="text-navy">Manajemen Akselerasi Infrastruktur untuk Monitoring dan Integrasi Wilayah</b>,
                    sistem digital terpadu berbasis spasial yang dikelola Bappeda Provinsi Maluku Utara. Sistem ini mendukung
                    perencanaan, pelaksanaan, pemantauan, dan evaluasi pembangunan infrastruktur secara efektif, partisipatif,
                    dan terintegrasi.</p>
                <p>Sebagai wilayah kepulauan dengan konektivitas terbatas, Maluku Utara membutuhkan data pembangunan yang terpadu.
                    MARIMOI menyatukan data lintas sektor dan lintas wilayah, dari provinsi hingga kabupaten/kota, menghubungkan usulan
                    Musrenbang dan Pokok Pikiran DPRD dengan rencana pembangunan, serta membuka ruang aspirasi bagi masyarakat.
                    Tujuannya agar visi <b class="text-navy">Maluku Utara Bangkit</b> terwujud sebagai pembangunan yang terukur.</p>
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
                <p class="reveal text-lg text-slate-600 delay-200" data-reveal>Tonton pengenalan singkat tentang MARIMOI dan bagaimana sistem ini mendukung perencanaan dan pemantauan pembangunan infrastruktur Maluku Utara.</p>
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
            <p class="reveal mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-aqua before:h-px before:w-7 before:bg-current" data-reveal>Pendekatan THIS-DP</p>
            <h2 class="reveal mb-14 max-w-[18ch] text-3xl font-bold leading-[1.1] tracking-tight delay-100 md:text-4xl lg:text-5xl" data-reveal>Enam prinsip perencanaan yang mendasari MARIMOI.</h2>
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
                untuk mewujudkan perencanaan yang terintegrasi lintas sektor dan lintas wilayah di Maluku Utara.</p>

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
            <p class="reveal mb-14 max-w-2xl text-lg text-slate-600 delay-200" data-reveal>Marimoi berasal dari bahasa Ternate yang berarti <em>bersatu kita teguh</em>, falsafah kebersamaan dan gotong royong
                masyarakat Maluku Utara. Nilai itu dituangkan dalam elemen visual logo MARIMOI.</p>
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

    {{-- Profil Reformer: sorotan --}}
    <section id="profil-reformer" class="relative scroll-mt-20 overflow-hidden bg-deep py-20 text-white md:py-28">
        <div data-parallax="0.06" class="pointer-events-none absolute inset-x-0 -inset-y-[8%] opacity-50 will-change-transform" aria-hidden="true">
            <svg class="contours {{ $contour }}"></svg>
        </div>
        <div class="relative mx-auto grid w-full max-w-[1180px] items-center gap-10 px-6 md:grid-cols-[5fr_7fr] md:gap-16">
            <div class="reveal-blur relative mx-auto w-full max-w-sm md:max-w-none" data-reveal>
                <div class="absolute inset-x-[8%] bottom-0 top-[14%] rounded-t-[999px] bg-gradient-to-b from-ocean/70 to-navy" aria-hidden="true"></div>
                <div class="absolute -right-2 top-[18%] h-24 w-24 rounded-full bg-amber-400/90 blur-[1px]" aria-hidden="true"></div>
                <img src="{{ asset('frontend/img/cv/kepala-bappeda.webp') }}" alt="Dr. Muhammad Sarmin S. Adam, Kepala BAPPEDA Provinsi Maluku Utara"
                    width="900" height="1350" loading="lazy" class="relative z-10 mx-auto max-h-[560px] w-auto">
            </div>

            <div>
                <p class="reveal mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-aqua before:h-px before:w-7 before:bg-current" data-reveal>Profil Reformer</p>
                <h2 class="reveal delay-100 text-3xl font-extrabold leading-[1.1] tracking-tight md:text-4xl lg:text-5xl" data-reveal>Dr. Muhammad Sarmin S. Adam, S.STP, M.Si</h2>
                <p class="reveal mt-6 max-w-xl text-lg text-white/70 delay-200" data-reveal>Penggagas MARIMOI dan Kepala Bappeda Provinsi Maluku Utara. Meniti karier di perencanaan
                    pembangunan daerah sejak 2014, dari BAPPEDA Kabupaten Halmahera Timur hingga memimpin Bappeda Provinsi Maluku Utara.</p>

                <dl class="reveal mt-10 grid grid-cols-3 gap-6 border-t border-white/15 pt-8 delay-300" data-reveal>
                    <div>
                        <dt class="text-sm text-white/50">Jabatan saat ini</dt>
                        <dd class="mt-1 font-semibold leading-snug">Kepala BAPPEDA</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-white/50">Pendidikan tertinggi</dt>
                        <dd class="mt-1 font-semibold leading-snug">S-3 Ilmu Politik, UGM</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-white/50">Riwayat jabatan</dt>
                        <dd class="mt-1 font-grotesk text-2xl font-medium text-aqua">{{ count($jabatan) }} <span class="font-manrope text-sm font-semibold text-white">posisi</span></dd>
                    </div>
                </dl>

                <div class="reveal mt-10 flex flex-wrap gap-3 delay-300" data-reveal>
                    <button type="button" data-cv-open="0"
                        class="inline-flex items-center gap-2 rounded-full bg-ocean px-7 py-3.5 text-[15px] font-bold text-white shadow-[0_10px_30px_-12px_rgba(10,132,255,.8)] transition duration-300 ease-out hover:-translate-y-1 hover:shadow-[0_14px_38px_-10px_rgba(32,217,255,.75)]">
                        Lihat CV lengkap
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Profil Reformer: riwayat pendidikan & jabatan --}}
    <section class="border-t border-slate-900/10 bg-white py-20 md:py-24">
        <div class="mx-auto w-full max-w-[1180px] px-6">
            <h2 class="reveal mb-12 text-3xl font-bold leading-[1.1] tracking-tight text-navy md:text-4xl" data-reveal>Riwayat pendidikan & jabatan</h2>
            <div class="grid gap-12 md:grid-cols-2 md:gap-16">
                <div class="reveal" data-reveal>
                    <p class="{{ $kicker }}">Pendidikan</p>
                    <ol class="divide-y divide-slate-900/10 border-y border-slate-900/10">
                        @foreach ($pendidikan as [$tahun, $lembaga, $tingkat, $gelar])
                            <li class="grid grid-cols-[3.5rem_1fr] gap-3 py-3.5">
                                <span class="font-grotesk text-sm text-ocean">{{ $tahun }}</span>
                                <div>
                                    <p class="font-semibold leading-snug text-navy">{{ $lembaga }}</p>
                                    <p class="text-sm text-slate-500">{{ $tingkat }}@if ($gelar) · <b class="font-semibold text-slate-700">{{ $gelar }}</b>@endif</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <div class="reveal delay-100" data-reveal>
                    <p class="{{ $kicker }}">Jabatan</p>
                    <ol class="divide-y divide-slate-900/10 border-y border-slate-900/10">
                        @foreach ($jabatan as [$tahun, $posisi, $instansi, $tmt])
                            <li class="grid grid-cols-[3.5rem_1fr] gap-3 py-3.5">
                                <span class="font-grotesk text-sm text-ocean">{{ $tahun }}</span>
                                <div>
                                    <p class="font-semibold leading-snug text-navy">{{ $posisi }}</p>
                                    <p class="text-sm text-slate-500">{{ $instansi }} <span class="text-slate-400">· TMT {{ $tmt }}</span></p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </section>

    {{-- Penampil CV lengkap --}}
    <div id="cvModal" data-open="false" role="dialog" aria-modal="true" aria-label="CV lengkap" aria-hidden="true"
        class="invisible fixed inset-0 z-[1200] grid place-items-center bg-slate-950/90 p-4 opacity-0 backdrop-blur-sm transition duration-300 data-[open=true]:visible data-[open=true]:opacity-100">
        <div class="relative flex max-h-full w-full max-w-5xl flex-col">
            <div class="mb-3 flex items-center justify-between text-white">
                <p id="cvCaption" class="font-grotesk text-xs uppercase tracking-widest text-white/70"></p>
                <button id="cvClose" type="button" aria-label="Tutup"
                    class="grid h-10 w-10 place-items-center rounded-full border border-white/20 bg-white/10 transition-colors hover:bg-white/20">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
            <div class="relative min-h-0 overflow-auto rounded-2xl bg-white">
                <img id="cvImage" src="" alt="" class="mx-auto h-auto w-full">
            </div>
            <div class="mt-4 flex items-center justify-center gap-3 text-white">
                <button id="cvPrev" type="button" aria-label="Sebelumnya" class="grid h-11 w-11 place-items-center rounded-full border border-white/20 bg-white/10 transition-colors hover:bg-white/20">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
                </button>
                <span id="cvCounter" class="min-w-[4ch] text-center font-grotesk text-sm text-white/70"></span>
                <button id="cvNext" type="button" aria-label="Berikutnya" class="grid h-11 w-11 place-items-center rounded-full border border-white/20 bg-white/10 transition-colors hover:bg-white/20">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                </button>
            </div>
        </div>
    </div>

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

@push('scripts')
    <script>
        window.MARIMOI_CV = @json(collect($dokumenCv)->map(fn ($d) => ['label' => $d[0], 'src' => asset($d[1])])->all());
    </script>
@endpush
