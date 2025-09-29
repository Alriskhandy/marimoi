<section
    class="bg-gradient-to-bl from-[var(--bg-primary)] to-[var(--bg-secondary)] py-6 md:py-8 border-t border-white/10">
    <!-- Section Title -->
    <div class="container mx-auto px-4 text-center mb-6 md:mb-8 section-title z-above-overlay">
        <h2 class="text-2xl md:text-3xl font-bold pt-4 text-white mb-4">
            Filosofi Logo
        </h2>
    </div>

    <div class="container mx-auto">
        <!-- Responsive grid: 1 kolom di mobile/tablet, 3 di desktop, centered -->
        <div class="max-w-6xl mx-auto">
            <div
                class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 items-start justify-items-center">
                <div class="group flex flex-col items-center justify-center p-4 text-center md:text-justify">
                    <img src="{{ asset('frontend/img/filosofi/filosofi-1.png') }}" alt="Filosofi 1 - EVP" loading="lazy"
                        class="w-auto h-[64px] sm:h-[72px] md:h-[72px] lg:h-[96px] object-contain mb-4 transform transition-transform duration-300 ease-out will-change-transform group-hover:scale-110 group-focus:scale-110">
                    <h3 class="text-lg md:text-xl font-bold text-white uppercase tracking-wide">Huruf “M”</h3>
                    <p class="text-sm md:text-base text-slate-200 max-w-[360px] mt-3">
                        Huruf “M” merupakan inisial dari nama website "Marimoi".
                    </p>
                </div>

                <div class="group flex flex-col items-center justify-center p-4 text-center md:text-justify">
                    <img src="{{ asset('frontend/img/filosofi/filosofi-2.png') }}" alt="Filosofi 2 - BerAKHLAK"
                        loading="lazy"
                        class="w-auto h-[64px] sm:h-[72px] md:h-[72px] lg:h-[96px] object-contain mb-4 transform transition-transform duration-300 ease-out will-change-transform group-hover:scale-110 group-focus:scale-110">
                    <h3 class="text-lg md:text-xl font-bold text-white uppercase tracking-wide">Kebersamaan</h3>
                    <p class="text-sm md:text-base text-slate-200 max-w-[360px] mt-3">
                        Ilustrasi dua orang yang saling bergandengan mewakili kata “Marimoi” yang memiliki makna
                        semangat
                        kebersamaan, gotong royong dan solidaritas.
                    </p>
                </div>

                <div class="group flex flex-col items-center justify-center p-4 text-center md:text-justify">
                    <img src="{{ asset('frontend/img/filosofi/filosofi-3.png') }}" alt="Filosofi 3 - Lokasi"
                        loading="lazy"
                        class="w-auto h-[64px] sm:h-[72px] md:h-[72px] lg:h-[96px] object-contain mb-4 transform transition-transform duration-300 ease-out will-change-transform group-hover:scale-110 group-focus:scale-110">
                    <h3 class="text-lg md:text-xl font-bold text-white uppercase tracking-wide">Lokasi</h3>
                    <p class="text-sm md:text-base text-slate-200 max-w-[360px] mt-3">
                        Ilustrasi lokasi yang mewakili tema utama dari website Marimoi yaitu web GIS dan peta.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
