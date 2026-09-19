@php
    $layananUtama = [
        [
            'judul' => 'Peta Tematik',
            'deskripsi' => 'Jelajahi proyek strategis, Musrenbang, Pokok Pikiran DPRD, dan data spasial pembangunan di atas peta.',
            'url' => route('tampil.tematik'),
            'ikon' => 'bi-map',
            'aksi' => 'Buka peta',
        ],
        [
            'judul' => 'Prioritas Daerah',
            'deskripsi' => 'Lihat prioritas pembangunan Maluku Utara periode 2025-2029.',
            'url' => route('tampil.prioritas'),
            'ikon' => 'bi-bullseye',
            'aksi' => 'Lihat prioritas',
        ],
        [
            'judul' => 'Aspirasi Masyarakat',
            'deskripsi' => 'Sampaikan usulan dan masukan pembangunan langsung kepada pemerintah daerah.',
            'url' => route('tampil.aspirasi'),
            'ikon' => 'bi-chat-square-text',
            'aksi' => 'Kirim aspirasi',
        ],
        [
            'judul' => 'Dokumen Publikasi',
            'deskripsi' => 'Unduh dokumen perencanaan dan publikasi resmi pembangunan daerah.',
            'url' => route('tampil.publikasi'),
            'ikon' => 'bi-file-earmark-text',
            'aksi' => 'Lihat dokumen',
        ],
    ];
@endphp

<!-- Layanan Utama -->
<section id="layanan-utama" class="relative w-full py-16 lg:py-20 bg-slate-50">
    <div class="container max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-10 lg:mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 font-[Poppins] leading-tight tracking-tight">
                Layanan Utama
            </h2>
            <p class="mt-3 text-gray-600">Pilih layanan yang Anda butuhkan.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 lg:gap-6">
            @foreach ($layananUtama as $layanan)
                <a href="{{ $layanan['url'] }}"
                    class="group flex flex-col bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 hover:border-blue-300 transition-all duration-300">
                    <span class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mb-4">
                        <i class="bi {{ $layanan['ikon'] }}"></i>
                    </span>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-blue-700">{{ $layanan['judul'] }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed mb-5">{{ $layanan['deskripsi'] }}</p>
                    <span class="mt-auto text-sm font-semibold text-blue-600 group-hover:translate-x-1 transition-transform">
                        {{ $layanan['aksi'] }} →
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
