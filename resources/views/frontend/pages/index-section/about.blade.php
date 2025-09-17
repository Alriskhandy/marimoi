<style>
    /* Custom mask untuk radial spotlight - tidak tersedia di Tailwind */
    .spotlight-mask::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: url("{{ asset('frontend/img/about-bg2.png') }}");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: var(--bg-opacity, 1);
        transition: opacity 0.1s ease;
        z-index: 1;
        pointer-events: none;
        mask-image: radial-gradient(circle 300px at var(--mask-x, 75%) var(--mask-y, 50%),
                black,
                transparent);
        -webkit-mask-image: radial-gradient(circle 300px at var(--mask-x, 75%) var(--mask-y, 50%),
                black,
                transparent);
        mask-repeat: no-repeat;
        -webkit-mask-repeat: no-repeat;
    }

    /* Responsive mask sizes */
    @media (max-width: 1350px) {
        .spotlight-mask::before {
            mask-image: radial-gradient(circle 280px at var(--mask-x, 82%) var(--mask-y, 50%),
                    black,
                    transparent);
            -webkit-mask-image: radial-gradient(circle 280px at var(--mask-x, 82%) var(--mask-y, 50%),
                    black,
                    transparent);
        }
    }

    @media (max-width: 1024px) {
        .spotlight-mask::before {
            mask-image: radial-gradient(circle 250px at var(--mask-x, 80%) var(--mask-y, 50%),
                    black,
                    transparent);
            -webkit-mask-image: radial-gradient(circle 250px at var(--mask-x, 80%) var(--mask-y, 50%),
                    black,
                    transparent);
        }
    }

    @media (max-width: 768px) {
        .spotlight-mask::before {
            mask-image: radial-gradient(circle 250px at var(--mask-x, 50%) var(--mask-y, 70%),
                    black,
                    transparent);
            -webkit-mask-image: radial-gradient(circle 250px at var(--mask-x, 50%) var(--mask-y, 70%),
                    black,
                    transparent);
        }
    }

    @media (max-width: 480px) {
        .spotlight-mask::before {
            mask-image: radial-gradient(circle 250px at var(--mask-x, 50%) var(--mask-y, 70%),
                    black,
                    transparent);
            -webkit-mask-image: radial-gradient(circle 250px at var(--mask-x, 50%) var(--mask-y, 70%),
                    black,
                    transparent);
        }
    }

    @media (max-width: 360px) {
        .spotlight-mask::before {
            mask-image: radial-gradient(circle 200px at var(--mask-x, 50%) var(--mask-y, 70%),
                    black,
                    transparent);
            -webkit-mask-image: radial-gradient(circle 200px at var(--mask-x, 50%) var(--mask-y, 70%),
                    black,
                    transparent);
        }
    }
</style>

<section
    class="text-white pt-8 pb-0 md:pt-10 md:pb-0 lg:pt-10 lg:pb-0 min-h-[50vh] relative overflow-hidden flex items-center w-full bg-[var(--bg-primary)] pointer-events-auto spotlight-mask"
    id="about-section">
    <div class="relative z-[2] w-full max-w-[1200px] mx-auto px-4 md:px-6 lg:px-8">
        <!-- Content Layout: Left (Quotes), Right (Person's Photo) -->
        <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-8 md:gap-10 lg:gap-20 items-start">

            <!-- Left Side: Quotes - Mobile First -->
            <div class="flex justify-start items-start relative z-[3]">
                <div class="w-full">
                    <blockquote class="m-0 p-0 border-none">
                        <p
                            class="text-[0.9rem] md:text-base lg:text-[1.2rem] leading-[1.4] md:leading-[1.5] lg:leading-[1.6] text-white mb-4 md:mb-5 relative text-left font-[Inter,sans-serif] [text-shadow:2px_2px_4px_rgba(0,0,0,0.7)]">
                            "Dengan MARIMOI, kita tidak hanya menguatkan koordinasi lintas sektor, tetapi juga membuka
                            ruang partisipasi masyarakat secara luas, sehingga pembangunan Maluku Utara dapat lebih
                            terarah, transparan, dan sesuai dengan kebutuhan masyarakat"
                        </p>
                        <p
                            class="text-[0.75rem] md:text-[0.8rem] lg:text-base italic leading-[1.3] md:leading-normal lg:leading-normal text-[#b7b7b7] m-0 font-semibold text-left [text-shadow:1px_1px_2px_rgba(0,0,0,0.6)]">
                            Kepala Bappeda Prov. Maluku Utara,<br>
                            Periode 2023 - Sekarang<br>
                            - Dr. Muhammad Sarmin S. Adam, S.STP, M.Si
                        </p>
                    </blockquote>
                </div>
            </div>

            <!-- Right Side: Person's Photo - Mobile First -->
            <div class="flex justify-center md:justify-end items-end relative z-[3]">
                <div
                    class="w-full max-w-[280px] md:max-w-[250px] lg:max-w-[350px] aspect-[2/3] relative overflow-hidden mx-auto md:mx-0">
                    <img src="{{ asset('frontend/img/foto-2x3.png') }}"
                        alt="Foto Dr. Muhammad Sarmin S. Adam, S.STP, M.Si"
                        class="w-full h-full object-cover object-center transition-transform duration-300 ease-in-out group-hover:scale-105"
                        loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>
