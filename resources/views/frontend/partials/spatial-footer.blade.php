@php
    $footUnderline = 'relative after:absolute after:inset-x-0 after:-bottom-1 after:h-px after:origin-left after:scale-x-0 after:bg-current after:transition-transform after:duration-300 hover:after:scale-x-100';
    $socials = [
        ['Instagram', 'https://www.instagram.com/bappeda_malut/', '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r=".8" fill="currentColor"/>'],
        ['Facebook', 'https://www.facebook.com/bappedamalut/', '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8a1 1 0 0 1 1-1Z"/>'],
        ['YouTube', 'https://www.youtube.com/@Bappeda_Malut', '<rect x="2.5" y="5" width="19" height="14" rx="4"/><path d="m10 9 5 3-5 3V9Z" fill="currentColor"/>'],
    ];
@endphp

{{-- Footer --}}
<footer class="bg-[#04101a] pb-8 pt-20 text-sm text-white/60">
    <div class="mx-auto w-full max-w-[1180px] px-6">
        <div class="mb-12 grid gap-12 md:grid-cols-[2fr_1fr_1.3fr]">
            <div>
                <div class="mb-4 flex items-center gap-3 text-lg font-extrabold tracking-wider text-white">
                    <img src="{{ asset('frontend/img/logo/logo-white.png') }}" alt="" class="h-8 w-auto">MARIMOI
                </div>
                <p class="font-grotesk text-xs uppercase tracking-widest text-aqua">Maluku Utara</p>
                <p class="mt-3 max-w-sm">Platform digital berbasis spasial untuk mendukung integrasi dan pemantauan
                    pembangunan Maluku Utara.</p>
                <p class="mt-4 max-w-sm">Jl. Raya Lintas Halmahera, Sofifi<br>Maluku Utara</p>
            </div>
            <div>
                <h4 class="mb-4 font-grotesk text-xs uppercase tracking-widest text-white">Jelajahi</h4>
                <ul class="grid gap-3">
                    <li><a href="{{ route('beranda') }}" class="{{ $footUnderline }} hover:text-white">Beranda</a></li>
                    <li><a href="{{ route('tampil.tematik') }}" class="{{ $footUnderline }} hover:text-white">Peta</a></li>
                    <li><a href="{{ route('tampil.prioritas') }}" class="{{ $footUnderline }} hover:text-white">Prioritas Daerah</a></li>
                    <li><a href="{{ route('tampil.publikasi') }}" class="{{ $footUnderline }} hover:text-white">Publikasi</a></li>
                    <li><a href="{{ route('tampil.aspirasi') }}" class="{{ $footUnderline }} hover:text-white">Aspirasi</a></li>
                    <li><a href="{{ route('tampil.reformer') }}" class="{{ $footUnderline }} hover:text-white">Profil Reformer</a></li>
                    <li><a href="{{ route('tampil.tentang') }}" class="{{ $footUnderline }} hover:text-white">Tentang</a></li>
                    <li><a href="{{ route('tampil.faq') }}" class="{{ $footUnderline }} hover:text-white">FAQ</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 font-grotesk text-xs uppercase tracking-widest text-white">Kontak</h4>
                <ul class="grid gap-3">
                    <li><a href="mailto:bappeda.provmalut024@gmail.com" class="{{ $footUnderline }} break-all hover:text-white">bappeda.provmalut024@gmail.com</a></li>
                    <li><a href="https://bappeda.malutprov.go.id/" target="_blank" rel="noopener" class="{{ $footUnderline }} hover:text-white">bappeda.malutprov.go.id</a></li>
                    <li><a href="https://opendata.malutprov.go.id/" target="_blank" rel="noopener" class="{{ $footUnderline }} hover:text-white">Open Data Malut</a></li>
                </ul>
                <div class="mt-5 flex gap-3">
                    @foreach ($socials as [$name, $href, $icon])
                        <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $name }}"
                            class="grid h-10 w-10 place-items-center rounded-full border border-white/15 text-white/70 transition duration-300 hover:-translate-y-1 hover:border-aqua/40 hover:text-aqua">
                            <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-6 text-[13px]">
            <span>&copy; {{ date('Y') }} BAPPEDA Provinsi Maluku Utara
                <span class="mx-2 text-white/25">|</span>
                <a href="{{ route('kebijakan_privasi') }}" class="{{ $footUnderline }} hover:text-white">Kebijakan Privasi</a>
                <span class="mx-1 text-white/25">·</span>
                <a href="{{ route('syarat_ketentuan') }}" class="{{ $footUnderline }} hover:text-white">Syarat &amp; Ketentuan</a>
            </span>
            <span class="inline-flex items-center gap-3">Developed by
                <img src="{{ asset('frontend/img/logo_heartware_putih.png') }}" alt="Heartware Digital" class="h-6 w-auto opacity-90" loading="lazy"></span>
        </div>
    </div>
</footer>

{{-- Floating actions: back to top + aspirasi --}}
<div id="floatActions" data-show="false"
    class="pointer-events-none fixed bottom-5 right-5 z-[900] flex translate-y-4 flex-col gap-3 opacity-0 transition duration-300 data-[show=true]:pointer-events-auto data-[show=true]:translate-y-0 data-[show=true]:opacity-100">
    <button type="button" id="backToTop" aria-label="Kembali ke atas" title="Kembali ke atas"
        class="grid h-12 w-12 place-items-center rounded-full border border-white/10 bg-slate-950/80 text-white shadow-lg backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:border-aqua/40 hover:text-aqua">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 15 6-6 6 6"/></svg>
    </button>
    <a href="{{ route('tampil.aspirasi') }}" aria-label="Sampaikan aspirasi" title="Sampaikan aspirasi"
        class="grid h-12 w-12 place-items-center rounded-full bg-ocean text-white shadow-lg transition duration-300 hover:-translate-y-1 hover:shadow-[0_10px_30px_-8px_rgba(32,217,255,.7)]">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l1-4.6A8 8 0 1 1 21 12Z"/></svg>
    </a>
</div>
