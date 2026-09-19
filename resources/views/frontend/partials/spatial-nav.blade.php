@php
    $navUnderline = 'relative after:absolute after:inset-x-0 after:-bottom-1 after:h-px after:origin-left after:scale-x-0 after:bg-current after:transition-transform after:duration-300 hover:after:scale-x-100';
    $navSolid = $navSolid ?? ! request()->routeIs('beranda');
    $navItems = [
        ['Beranda', route('beranda'), request()->routeIs('beranda')],
        ['Peta Tematik', route('tampil.tematik'), request()->routeIs('tampil.tematik', 'detail.tematik', 'tematik.share.show')],
        ['Publikasi', route('tampil.publikasi'), request()->routeIs('tampil.publikasi')],
        ['Aspirasi', route('tampil.aspirasi'), request()->routeIs('tampil.aspirasi')],
        ['Profil Reformer', route('tampil.reformer'), request()->routeIs('tampil.reformer')],
        ['Tentang', route('tampil.tentang'), request()->routeIs('tampil.tentang')],
        ['FAQ', route('tampil.faq'), request()->routeIs('tampil.faq')],
    ];
@endphp

{{-- Navbar --}}
<header id="nav" data-scrolled="false" data-solid="{{ $navSolid ? 'true' : 'false' }}"
    class="group/nav fixed inset-x-0 top-0 z-[1000] border-b border-transparent text-white transition-[background-color,border-color] duration-500 data-[scrolled=true]:border-white/10 data-[scrolled=true]:bg-slate-950/85 data-[scrolled=true]:backdrop-blur-md data-[solid=true]:border-white/10 data-[solid=true]:bg-slate-950/90 data-[solid=true]:backdrop-blur-md">
    <div
        class="mx-auto flex h-20 w-full max-w-[1180px] items-center justify-between gap-4 px-6 transition-[height] duration-500 group-data-[scrolled=true]/nav:h-16 group-data-[solid=true]/nav:h-[76px]">
        <a href="{{ route('beranda') }}" class="flex items-center gap-3 text-lg font-extrabold tracking-wider"
            aria-label="MARIMOI">
            <img src="{{ asset('frontend/img/logo/logo-white.png') }}" alt="" class="h-8 w-auto">
            MARIMOI
        </a>

        <nav class="hidden items-center gap-6 xl:flex" aria-label="Menu utama">
            @foreach ($navItems as [$label, $href, $isActive])
                <a href="{{ $href }}" @if ($isActive) aria-current="page" @endif
                    class="{{ $navUnderline }} whitespace-nowrap text-[13px] font-semibold transition-colors duration-300 hover:text-white {{ $isActive ? 'text-white after:scale-x-100' : 'text-white/70' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-4 text-sm font-semibold">
            @auth
                <span class="hidden max-w-[10rem] items-center gap-2 truncate text-white/80 lg:inline-flex">
                    <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>
                    <span class="truncate">{{ auth()->user()->name }}</span>
                </span>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('dashboard') }}" class="{{ $navUnderline }} hidden sm:inline">Dashboard</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="{{ $navUnderline }} font-semibold text-white/70 hover:text-white">Keluar</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="{{ $navUnderline }} hidden sm:inline">Masuk</a>
            @endauth
            <button id="burger" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="mobileMenu"
                class="grid h-10 w-10 place-items-center rounded-full border border-white/15 bg-white/5 xl:hidden">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>
        </div>
    </div>
</header>

{{-- Mobile slide panel --}}
<div id="mobileMenu" data-open="false" aria-hidden="true"
    class="invisible fixed inset-0 z-[1100] translate-x-full overflow-y-auto bg-deep/95 px-6 pb-10 pt-6 text-white backdrop-blur-2xl transition duration-500 ease-out data-[open=true]:visible data-[open=true]:translate-x-0 xl:hidden">
    <div class="flex items-center justify-between">
        <span class="flex items-center gap-3 text-lg font-extrabold tracking-wider">
            <img src="{{ asset('frontend/img/logo/logo-white.png') }}" alt="" class="h-8 w-auto">MARIMOI
        </span>
        <button id="burgerClose" type="button" aria-label="Tutup menu"
            class="grid h-10 w-10 place-items-center rounded-full border border-white/15 bg-white/5">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>
    <nav class="mt-10 flex flex-col gap-1" aria-label="Menu seluler">
        @foreach ($navItems as [$label, $href, $isActive])
            <a href="{{ $href }}"
                class="border-b border-white/10 py-3 text-2xl font-bold tracking-tight transition-colors hover:text-aqua {{ $isActive ? 'text-aqua' : 'text-white/90' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="mt-8 flex items-center gap-6 text-sm font-semibold">
        @auth
            <span class="max-w-[9rem] truncate text-white/60">{{ auth()->user()->name }}</span>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('dashboard') }}" class="text-aqua">Dashboard</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-white/70">Keluar</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="text-aqua">Masuk</a>
        @endauth
    </div>
</div>
