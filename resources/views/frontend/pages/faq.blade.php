@extends('frontend.layouts.spatial', ['title' => 'FAQ - Pertanyaan Umum - MARIMOI', 'heroTitle' => 'Pertanyaan Umum (FAQ)'])

@section('subtitle', 'Jawaban atas pertanyaan yang sering diajukan tentang fitur dan layanan MARIMOI.')

@php
    $link = 'font-semibold text-ocean underline decoration-ocean/30 underline-offset-4 transition-colors hover:decoration-ocean';
    $faqs = [
        [
            'q' => 'Apa itu MARIMOI?',
            'a' => '<p>MARIMOI adalah sistem digital terpadu untuk memperkuat koordinasi, pemantauan, dan integrasi pembangunan infrastruktur di Maluku Utara. Dengan pendekatan spasial dan peta tematik, MARIMOI menyediakan data yang mendukung perencanaan lintas sektor secara kolaboratif dan transparan.</p>
                <p class="mt-3 font-semibold text-navy">Fitur utama:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Integrasi data spasial dan sektoral</li>
                    <li>Pemantauan proyek strategis daerah dan nasional</li>
                    <li>Pengelolaan usulan Pokir DPRD dan Musrenbang</li>
                    <li>Prioritas pembangunan 2025-2029</li>
                    <li>Partisipasi masyarakat melalui usulan aspirasi</li>
                </ul>',
        ],
        [
            'q' => 'Bagaimana cara menggunakan Peta Tematik?',
            'a' => '<p>Peta Tematik menampilkan berbagai data pembangunan Maluku Utara di atas peta. Buka menu <a href="'.route('tampil.tematik').'" class="'.$link.'">Peta Tematik</a>, lalu gunakan tombol kontrol di sisi kanan peta:</p>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    <li><b class="text-navy">Bantuan</b>: melihat panduan penggunaan peta</li>
                    <li><b class="text-navy">Legenda</b>: keterangan simbol pada peta</li>
                    <li><b class="text-navy">Basemap</b>: memilih jenis peta dasar</li>
                    <li><b class="text-navy">Layer</b>: mengatur layer yang ditampilkan</li>
                </ul>
                <p class="mt-3">Klik penanda atau area di peta untuk melihat informasi detailnya.</p>',
        ],
        [
            'q' => 'Apa perbedaan Proyek Strategis Daerah dan Nasional?',
            'a' => '<p><b class="text-navy">Proyek Strategis Daerah (PSD)</b> adalah proyek prioritas yang diinisiasi dan dikelola Pemerintah Provinsi Maluku Utara, umumnya didanai APBD Provinsi, dan dikoordinasikan oleh Bappeda Provinsi Maluku Utara.</p>
                <p class="mt-3"><b class="text-navy">Proyek Strategis Nasional (PSN)</b> adalah proyek prioritas nasional yang berlokasi di Maluku Utara, umumnya didanai APBN atau kombinasi sumber lain, dan dikoordinasikan Kementerian/Lembaga terkait dengan dukungan Pemerintah Provinsi.</p>
                <p class="mt-3">Keduanya dapat dilihat sebagai layer di <a href="'.route('tampil.tematik').'" class="'.$link.'">Peta Tematik</a>.</p>',
        ],
        [
            'q' => 'Apa itu Prioritas Daerah 2025-2029?',
            'a' => '<p>Prioritas Daerah 2025-2029 adalah dokumen perencanaan berisi program prioritas jangka menengah Pemerintah Provinsi Maluku Utara. Dokumen ini menjadi acuan perencanaan dan penganggaran pembangunan daerah, serta memuat visi dan misi, program prioritas, dan arah pembangunan lima tahun ke depan.</p>
                <p class="mt-3">Bacalah selengkapnya di halaman <a href="'.route('tampil.prioritas').'" class="'.$link.'">Prioritas Daerah</a>.</p>',
        ],
        [
            'q' => 'Apa itu Musrenbang dan Pokir DPRD?',
            'a' => '<p><b class="text-navy">Musrenbang</b> (Musyawarah Perencanaan Pembangunan) adalah forum penyusunan rencana pembangunan daerah yang melibatkan berbagai pemangku kepentingan, dari tingkat desa atau kelurahan hingga provinsi.</p>
                <p class="mt-3"><b class="text-navy">Pokir DPRD</b> (Pokok Pikiran DPRD) adalah usulan program atau kegiatan dari anggota DPRD berdasarkan aspirasi masyarakat di daerah pemilihannya, untuk dimasukkan dalam perencanaan pembangunan daerah.</p>
                <p class="mt-3">Sebaran usulan keduanya dapat dilihat di <a href="'.route('tampil.tematik').'" class="'.$link.'">Peta Tematik</a>.</p>',
        ],
        [
            'q' => 'Bagaimana cara menyampaikan aspirasi?',
            'a' => '<p>Buka halaman <a href="'.route('tampil.aspirasi').'" class="'.$link.'">Aspirasi</a>, lalu:</p>
                <ol class="mt-3 list-decimal space-y-1.5 pl-5">
                    <li>Isi data diri: nama, alamat, email, dan nomor WhatsApp.</li>
                    <li>Pilih jenis aspirasi: <b class="text-navy">Usulan Pembangunan</b> (dengan lokasi di peta) atau <b class="text-navy">Kritik &amp; Saran</b> (tanpa lokasi).</li>
                    <li>Untuk usulan pembangunan, pilih kategori dan tentukan lokasi pada peta.</li>
                    <li>Tulis judul dan pesan dengan jelas, lalu lampirkan berkas pendukung bila perlu.</li>
                    <li>Setujui pernyataan, selesaikan captcha, lalu kirim.</li>
                </ol>
                <p class="mt-3">Setelah terkirim, aspirasi akan ditinjau oleh tim terkait, dan Anda dapat dihubungi melalui email atau WhatsApp untuk tindak lanjut.</p>',
        ],
        [
            'q' => 'Bagaimana jika saya membutuhkan bantuan lebih lanjut?',
            'a' => '<p>Hubungi Bappeda Provinsi Maluku Utara melalui email <a href="mailto:bappeda.provmalut024@gmail.com" class="'.$link.'">bappeda.provmalut024@gmail.com</a>, kunjungi kantor Bappeda di Jl. Raya Lintas Halmahera, Sofifi, atau buka situs resmi <a href="https://bappeda.malutprov.go.id/" target="_blank" rel="noopener" class="'.$link.'">bappeda.malutprov.go.id</a>. Anda juga dapat mengirim pertanyaan lewat formulir aspirasi dengan jenis "Kritik &amp; Saran".</p>',
        ],
    ];
@endphp

@section('main')
    <section class="bg-mist py-16 md:py-24">
        <div class="mx-auto w-full max-w-3xl px-6">
            <div class="divide-y divide-slate-900/10 border-y border-slate-900/10">
                @foreach ($faqs as $i => $faq)
                    <details class="group reveal py-1" data-reveal style="transition-delay: {{ $i * 50 }}ms" @if ($i === 0) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-5 text-left text-lg font-bold tracking-tight text-navy transition-colors hover:text-ocean [&::-webkit-details-marker]:hidden">
                            <span>{{ $faq['q'] }}</span>
                            <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0 text-ocean transition-transform duration-300 group-open:rotate-45" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        </summary>
                        <div class="pb-6 pr-10 text-[15px] leading-relaxed text-slate-600">{!! $faq['a'] !!}</div>
                    </details>
                @endforeach
            </div>

            <div class="reveal mt-12 flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-deep px-8 py-8 text-white" data-reveal>
                <div>
                    <p class="text-xl font-bold">Pertanyaan Anda belum terjawab?</p>
                    <p class="mt-1 text-sm text-white/65">Sampaikan langsung melalui formulir aspirasi.</p>
                </div>
                <a href="{{ route('tampil.aspirasi') }}" class="inline-flex items-center gap-2 rounded-full bg-ocean px-6 py-3 text-sm font-bold text-white transition duration-300 hover:-translate-y-1 hover:shadow-[0_10px_30px_-8px_rgba(32,217,255,.6)]">Sampaikan Aspirasi</a>
            </div>
        </div>
    </section>
@endsection
