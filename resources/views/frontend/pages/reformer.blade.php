@extends('frontend.layouts.spatial', ['title' => 'Profil Reformer - MARIMOI', 'heroTitle' => 'Profil Reformer MARIMOI'])

@section('subtitle', 'Kepala BAPPEDA Provinsi Maluku Utara, instansi yang mengelola MARIMOI.')

@php
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
@endphp

@section('main')
    {{-- Sorotan profil --}}
    <section class="relative overflow-hidden bg-deep pb-20 text-white md:pb-28">
        <div data-parallax="0.06" class="pointer-events-none absolute inset-x-0 -inset-y-[8%] opacity-50 will-change-transform" aria-hidden="true">
            <svg class="contours {{ $contour }}"></svg>
        </div>
        <div class="relative mx-auto grid w-full max-w-[1180px] items-center gap-10 px-6 pt-4 md:grid-cols-[5fr_7fr] md:gap-16">
            <div class="reveal-blur relative mx-auto w-full max-w-sm md:max-w-none" data-reveal>
                <div class="absolute inset-x-[8%] bottom-0 top-[14%] rounded-t-[999px] bg-gradient-to-b from-ocean/70 to-navy" aria-hidden="true"></div>
                <div class="absolute -right-2 top-[18%] h-24 w-24 rounded-full bg-amber-400/90 blur-[1px]" aria-hidden="true"></div>
                <img src="{{ asset('frontend/img/cv/kepala-bappeda.webp') }}" alt="Dr. Muhammad Sarmin S. Adam, Kepala BAPPEDA Provinsi Maluku Utara"
                    width="900" height="1350" class="relative z-10 mx-auto max-h-[560px] w-auto">
            </div>

            <div>
                <p class="reveal mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-aqua before:h-px before:w-7 before:bg-current" data-reveal>Kepala BAPPEDA Provinsi Maluku Utara</p>
                <h2 class="reveal delay-100 text-3xl font-extrabold leading-[1.1] tracking-tight md:text-4xl lg:text-5xl" data-reveal>Dr. Muhammad Sarmin S. Adam, S.STP, M.Si</h2>
                <p class="reveal mt-6 max-w-xl text-lg text-white/70 delay-200" data-reveal>Meniti karier di perencanaan pembangunan daerah sejak 2014, dari BAPPEDA Kabupaten
                    Halmahera Timur hingga memimpin BAPPEDA Provinsi Maluku Utara.</p>

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
                    <a href="{{ route('tampil.tentang') }}"
                        class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-7 py-3.5 text-[15px] font-bold text-white backdrop-blur-xl transition duration-300 ease-out hover:-translate-y-1 hover:border-white/40">Tentang MARIMOI</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Riwayat pendidikan --}}
    <section class="bg-mist py-20 md:py-28">
        <div class="mx-auto grid w-full max-w-[1180px] gap-12 px-6 lg:grid-cols-[4fr_8fr] lg:gap-20">
            <div class="lg:sticky lg:top-28 lg:self-start">
                <p class="reveal mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-ocean before:h-px before:w-7 before:bg-current" data-reveal>Pendidikan</p>
                <h2 class="reveal delay-100 text-3xl font-bold leading-[1.1] tracking-tight text-navy md:text-4xl" data-reveal>Riwayat pendidikan</h2>
                <p class="reveal mt-4 text-slate-600 delay-200" data-reveal>Dari Ternate hingga doktor Ilmu Politik di Universitas Gadjah Mada.</p>
            </div>
            <ol class="relative border-l border-slate-900/15 pl-8">
                @foreach ($pendidikan as $i => [$tahun, $lembaga, $tingkat, $gelar])
                    <li class="reveal relative pb-10 last:pb-0" data-reveal style="transition-delay: {{ $i * 60 }}ms">
                        <span class="absolute -left-[37px] top-1.5 h-3 w-3 rounded-full border-2 border-mist bg-ocean ring-4 ring-ocean/15" aria-hidden="true"></span>
                        <span class="font-grotesk text-sm text-ocean">{{ $tahun }}</span>
                        <h3 class="mt-1 text-lg font-bold leading-snug text-navy">{{ $lembaga }}</h3>
                        <p class="text-[15px] text-slate-600">{{ $tingkat }}@if ($gelar) <span class="text-slate-400">·</span> <b class="font-semibold text-navy">{{ $gelar }}</b>@endif</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Riwayat jabatan --}}
    <section class="relative overflow-hidden bg-deep py-20 text-white md:py-28">
        <div data-parallax="0.06" class="pointer-events-none absolute inset-x-0 -inset-y-[8%] opacity-40 will-change-transform" aria-hidden="true">
            <svg class="contours {{ $contour }}"></svg>
        </div>
        <div class="relative mx-auto grid w-full max-w-[1180px] gap-12 px-6 lg:grid-cols-[4fr_8fr] lg:gap-20">
            <div class="lg:sticky lg:top-28 lg:self-start">
                <p class="reveal mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-aqua before:h-px before:w-7 before:bg-current" data-reveal>Karier</p>
                <h2 class="reveal delay-100 text-3xl font-bold leading-[1.1] tracking-tight md:text-4xl" data-reveal>Riwayat jabatan</h2>
                <p class="reveal mt-4 text-white/65 delay-200" data-reveal>Perjalanan di perencanaan pembangunan daerah, dari kabupaten hingga provinsi.</p>
            </div>
            <ol class="relative border-l border-white/15 pl-8">
                @foreach ($jabatan as $i => [$tahun, $posisi, $instansi, $tmt])
                    <li class="reveal relative pb-10 last:pb-0" data-reveal style="transition-delay: {{ $i * 60 }}ms">
                        <span class="absolute -left-[37px] top-1.5 h-3 w-3 rounded-full border-2 border-deep bg-aqua ring-4 ring-aqua/15" aria-hidden="true"></span>
                        <span class="font-grotesk text-sm text-aqua">{{ $tahun }}</span>
                        <h3 class="mt-1 text-lg font-bold leading-snug">{{ $posisi }}</h3>
                        <p class="text-[15px] text-white/65">{{ $instansi }}</p>
                        <p class="mt-0.5 font-grotesk text-xs uppercase tracking-widest text-white/40">TMT {{ $tmt }}</p>
                    </li>
                @endforeach
            </ol>
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
@endsection

@push('scripts')
    <script>
        window.MARIMOI_CV = @json(collect($dokumenCv)->map(fn ($d) => ['label' => $d[0], 'src' => asset($d[1])])->all());
    </script>
@endpush
