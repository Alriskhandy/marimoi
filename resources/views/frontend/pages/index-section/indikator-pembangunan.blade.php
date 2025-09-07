@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/section/indikator-pembangunan.css') }}">
@endpush

<section class="section indikator-pembangunan">
    <!-- Full Section Background with Enhanced Parallax -->
    @php
        $i = Arr::random([1, 2, 3, 4, 5]);
    @endphp
    <div class="parallax-bg" style="background-image: url({{ asset('frontend/img/parallax/' . $i . '.jpg') }});"
        data-swiper-parallax="-50%" data-swiper-parallax-scale="0.8" loading="lazy"></div>

    <div class="container">
        <!-- Content Layout: Left (Title), Right (Subtitle + Description), Navigation -->
        <div class="indikator-content-layout">
            <!-- Left Side: Title Only -->
            <div class="indikator-left-section">
                <h2 class="section-title-left">Indikator Pembangunan Strategis</h2>
            </div>

            <!-- Right Side: Dynamic Subtitle + Description -->
            <div class="indikator-description-section">
                <!-- Dynamic Subtitle (covering entire right section) -->
                <h3 class="dynamic-subtitle" id="dynamicSubtitle">
                    <span class="subtitle-text">Indeks Pengembangan Wilayah</span>
                </h3>

                <div class="dynamic-description" id="dynamicDescription">
                    <p>MARIMOI meningkatkan Indeks Pengembangan Wilayah melalui:</p>
                    <ul>
                        <li>Penyediaan data spasial dan sektoral untuk mengukur keterjangkauan layanan dasar.</li>
                        <li>Akselerasi pembangunan infrastruktur di wilayah hinterland dan kawasan tertinggal.</li>
                        <li>Integrasi lintas wilayah dalam perencanaan berbasis konektivitas (antarpulau,
                            antarkawasan).</li>
                    </ul>
                </div>
            </div>

            <!-- Navigation Dots -->
            <div class="indikator-navigation">
                <div class="nav-dot active" data-slide="0" data-title="Indeks Pengembangan Wilayah"
                    data-description='<p>MARIMOI meningkatkan Indeks Pengembangan Wilayah melalui:</p><ul><li>Penyediaan data spasial dan sektoral untuk mengukur keterjangkauan layanan dasar.</li><li>Akselerasi pembangunan infrastruktur di wilayah hinterland dan kawasan tertinggal.</li><li>Integrasi lintas wilayah dalam perencanaan berbasis konektivitas (antarpulau, antarkawasan).</li></ul>'>
                </div>
                <div class="nav-dot" data-slide="1" data-title="Indeks Pelayanan Publik"
                    data-description='<p>MARIMOI memberi dampak pada Indeks Pelayanan melalui:</p><ul><li>Partisipasi masyarakat dalam pelaporan kondisi infrastruktur (jalan rusak, PSU, jembatan, dll).</li><li>Penyediaan data real-time kepada unit pelayanan teknis untuk respon cepat.</li><li>Penguatan kualitas layanan berbasis kebutuhan wilayah, bukan hanya standar sektoral.</li></ul>'>
                </div>
                <div class="nav-dot" data-slide="2" data-title="Indeks SPBE"
                    data-description='<p>MARIMOI mendorong pencapaian SPBE melalui:</p><ul><li>Digitalisasi proses perencanaan, monitoring, dan pelaporan infrastruktur.</li><li>Interoperabilitas data antar instansi (Bappeda, OPD teknis, DPRD).</li><li>Fitur dashboard publik sebagai bentuk pelayanan digital transparan.</li></ul>'>
                </div>
                <div class="nav-dot" data-slide="3" data-title="Indeks Kualitas Layanan Infrastruktur"
                    data-description='<p>Melalui Marimoi:</p><ul><li>Pemerintah dapat memantau kondisi infrastruktur secara spasial dan waktu nyata.</li><li>Sistem mendukung evaluasi kinerja infrastruktur berdasarkan output dan outcome.</li><li>Layanan infrastruktur menjadi lebih merata, berkualitas, dan efisien.</li></ul>'>
                </div>
            </div>
        </div>

        <div class="indikator-nav-buttons" style="display: none;">
            <button class="nav-btn nav-btn-prev" aria-label="Previous slide">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z" />
                </svg>
            </button>
            <button class="nav-btn nav-btn-next" aria-label="Next slide">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z" />
                </svg>
            </button>
        </div>
    </div>
</section>
