@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/section/about.css') }}">
@endpush

<section class="section about-section" id="about">
    <div class="container">
        <!-- Content Layout: Left (Quotes), Right (Person's Photo) -->
        <div class="about-content-layout">
            <!-- Left Side: Quotes -->
            <div class="about-left-section">
                <div class="quotes-container">
                    <blockquote class="testimonial-quote">
                        <p class="quote-text">
                            "MARIMOI adalah platform inovatif yang menghubungkan pemerintah dan masyarakat dalam
                            perencanaan pembangunan infrastruktur. Dengan teknologi pemetaan digital dan sistem
                            aspirasi terintegrasi, kami memastikan pembangunan yang tepat sasaran dan
                            berkelanjutan."
                        </p>
                        <p class="quote-additional">
                            Kepala Bappeda Prov. Maluku Utara,
                            Periode 2023 - Sekarang <br>
                            - Dr. Muhammad Sarmin S. Adam, S.STP, M.Si
                        </p>
                    </blockquote>
                </div>
            </div>

            <!-- Right Side: Person's Photo -->
            <div class="about-right-section">
                <div class="person-photo">
                    <img src="{{ asset('frontend/img/2x3.png') }}" alt="Foto Dr. Muhammad Sarmin S. Adam, S.STP, M.Si"
                        loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>
