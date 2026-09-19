@php
    $heroTitle = $heroTitle ?? trim(\Illuminate\Support\Str::before($title ?? 'MARIMOI', ' - '));
@endphp
<section class="relative isolate overflow-hidden bg-deep bg-[radial-gradient(900px_400px_at_75%_0%,#0b2a45_0%,#061522_70%)] pb-14 pt-36 text-white md:pb-16 md:pt-40">
    <div data-parallax="0.08" class="pointer-events-none absolute inset-x-0 -inset-y-[10%] opacity-70 will-change-transform" aria-hidden="true">
        <svg class="contours h-full w-full [&_path]:fill-none [&_path]:stroke-aqua/10 [&_path]:[vector-effect:non-scaling-stroke]"></svg>
    </div>
    <div class="mx-auto w-full max-w-[1180px] px-6">
        <nav aria-label="Breadcrumb" class="reveal mb-4 flex items-center gap-2 font-grotesk text-xs uppercase tracking-widest text-white/50" data-reveal>
            <a href="{{ route('beranda') }}" class="transition-colors hover:text-aqua">Beranda</a>
            <span aria-hidden="true">/</span>
            <span class="text-aqua">{{ $heroTitle }}</span>
        </nav>
        <h1 class="reveal delay-100 font-manrope max-w-[22ch] text-4xl font-extrabold leading-[1.05] tracking-tight md:text-5xl lg:text-6xl" data-reveal>{{ $heroTitle }}</h1>
        @hasSection('subtitle')
            <p class="reveal mt-5 max-w-2xl text-base text-white/70 delay-200 md:text-lg" data-reveal>@yield('subtitle')</p>
        @endif
    </div>
</section>
