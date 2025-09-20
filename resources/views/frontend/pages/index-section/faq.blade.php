@push('styles')
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <style>
        /* Typography Fonts */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
        }

        p,
        body,
        ul,
        li {
            font-family: 'Inter', sans-serif;
        }

        /* Custom animations and transitions */
        .answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out, padding 0.3s ease-in-out;
            padding-top: 0;
        }

        .tab input[type="radio"]:checked~.answer {
            padding-top: 1rem;
        }

        /* Specific max-heights for each FAQ item for smooth animations */
        #faq1:checked~.answer {
            max-height: 600px;
        }

        #faq2:checked~.answer {
            max-height: 700px;
        }

        #faq3:checked~.answer {
            max-height: 800px;
        }

        #faq4:checked~.answer {
            max-height: 900px;
        }

        #faq5:checked~.answer {
            max-height: 900px;
        }

        #faq6:checked~.answer {
            max-height: 900px;
        }

        #faq7:checked~.answer {
            max-height: 1000px;
        }

        #faq8:checked~.answer {
            max-height: 600px;
        }

        .tab label::after {
            transition: transform 0.3s ease-in-out;
        }

        .tab input[type="radio"]:checked~label::after {
            transform: rotate(45deg);
        }

        /* Style for checked/active accordion labels */
        .tab input[type="radio"]:checked~label {
            background: linear-gradient(to bottom right, #2563eb, #1e40af) !important;
            color: white !important;
        }

        .tab input[type="radio"]:checked~label h3 {
            color: white !important;
        }

        .tab input[type="radio"]:checked~label::after {
            color: white !important;
        }

        .tab input[type="radio"]:checked~label i {
            color: white !important;
        }

        /* Default label styling */
        .tab label {
            color: #374151 !important;
            background-color: transparent;
        }

        .tab label h3 {
            color: #374151 !important;
            margin: 0;
            font-weight: 600;
        }

        .tab label i {
            color: #374151 !important;
        }

        /* Smooth hover effects */
        .tab:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .container {
            max-width: 1200px;
        }

        /* Reformer section white gradient overlay */
        .faq-section {
            position: relative;
            overflow: hidden;
        }

        /* Gradient: solid white at top until ~55%, then fade to transparent toward bottom */
        .faq-section::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background: linear-gradient(to bottom,
                    rgba(241, 245, 249, 0.90) 0%,
                    rgba(241, 245, 249, 1) 20%,
                    rgba(241, 245, 249, 1) 80%,
                    rgba(241, 245, 249, 0.90) 100%);
        }

        /* Ensure content appears above the overlay */
        .faq-section .z-above-overlay {
            position: relative;
            z-index: 1;
        }

        /* Smaller, compact FAQ adjustments */
        .faq-section .tab {
            padding: .5rem 1rem;
            /* reduce px/py */
        }

        /* Tighter on small screens: reduce horizontal padding and slightly smaller text */
        @media (max-width: 640px) {
            .faq-section .tab {
                padding-left: .5rem; /* reduce horizontal padding on mobile */
                padding-right: .5rem;
            }

            .faq-section .tab label {
                font-size: 0.9rem;
                padding: .35rem .45rem;
            }

            .faq-section .tab label h3 {
                font-size: .95rem; /* slightly smaller heading on mobile */
                display: flex;
                align-items: center;
                gap: .4rem;
            }

            .faq-section .tab label i {
                font-size: 1rem; /* slightly smaller icon on mobile */
            }
        }

        /* Default compact sizes for larger screens */
        .faq-section .tab label {
            font-size: .95rem;
            /* slightly smaller label text */
            padding: .4rem .6rem;
        }

        .faq-section .tab label h3 {
            font-size: 1rem;
            /* smaller heading in label */
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .faq-section .tab label i {
            font-size: 1.05rem;
            /* smaller icon */
        }

        /* Ensure the section title sits above the gradient overlay */
        .faq-section .section-title {
            position: relative;
            z-index: 2;
            margin-bottom: .5rem;
        }
    </style>
@endpush

<!-- FAQ Section -->
<section class="faq-section min-h-auto pt-8 pb-8 bg-slate-50"
    style="background: url('{{ asset('frontend/img/cv/bg.svg') }}') repeat;">
    <!-- Section Title -->
    <div class="container mx-auto px-4 text-center mb-8 section-title z-above-overlay">
        <h2 class="text-2xl md:text-3xl font-bold pt-8 text-slate-800 mb-4">
            Pertanyaan yang Sering Diajukan (FAQ)
        </h2>
        <p class="text-slate-600 text-sm md:text-base max-w-[800px] mx-auto mb-0 leading-relaxed">
            Temukan jawaban untuk pertanyaan umum tentang fitur dan layanan yang tersedia di platform MARIMOI.
        </p>
    </div>

    <div class="container mx-auto px-4">
        <div class="wrapper w-full max-w-4xl mx-auto">

            <!-- FAQ Item 1: Tentang MARIMOI -->
            <div
                class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                <input type="radio" name="faq" id="faq1" class="hidden peer">
                <label for="faq1"
                    class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-5 md:after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                    tabindex="0">
                    <h3 class="pr-4 md:pr-0"><i class="bi bi-info-circle me-2"></i> Apa itu MARIMOI?</h3>
                </label>
                <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                    <div class="text-gray-700 text-sm md:text-md leading-relaxed">
                        <p class="mb-3">MARIMOI adalah sistem digital terpadu untuk memperkuat koordinasi, pemantauan,
                            dan integrasi pembangunan infrastruktur di Maluku Utara. Dengan pendekatan spasial dan peta
                            tematik, sistem ini menyediakan data real-time yang mendukung perencanaan lintas sektor
                            secara kolaboratif dan transparan.</p>
                        <p class="mb-2 font-medium">Fitur utama MARIMOI meliputi:</p>
                        <ul class="list-disc list-inside space-y-1 pl-4">
                            <li>Integrasi data spasial & sektoral</li>
                            <li>Pemantauan proyek strategis daerah dan nasional</li>
                            <li>Pengelolaan usulan Pokir DPRD & Musrenbang</li>
                            <li>Prioritas pembangunan 2025–2029</li>
                            <li>Evaluasi & pelaporan transparan</li>
                            <li>Partisipasi masyarakat melalui usulan aspirasi</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 2: Peta Tematik -->
            <div
                class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                <input type="radio" name="faq" id="faq2" class="hidden peer">
                <label for="faq2"
                    class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-5 md:after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                    tabindex="0">
                    <h3 class="pr-4 md:pr-0"><i class="bi bi-map me-2"></i> Bagaimana cara menggunakan Peta Tematik?</h3>
                </label>
                <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                    <div class="text-gray-700 text-sm md:text-md leading-relaxed">
                        <p class="mb-3">Peta Tematik MARIMOI menyediakan visualisasi spasial dari berbagai data
                            pembangunan di Maluku Utara. Untuk menggunakan fitur ini:</p>
                        <ol class="list-decimal list-inside space-y-2 pl-4">
                            <li>Klik menu "Peta Tematik" pada navbar</li>
                            <li>Gunakan tombol kontrol di sisi kanan peta untuk:
                                <ul class="list-disc list-inside pl-6 mt-1 space-y-1">
                                    <li>Bantuan - Melihat panduan penggunaan peta</li>
                                    <li>Legenda Peta - Menampilkan keterangan simbol pada peta</li>
                                    <li>Basemap Peta - Memilih jenis peta dasar</li>
                                    <li>Layer Peta - Mengatur layer yang ingin ditampilkan</li>
                                </ul>
                            </li>
                            <li>Klik pada marker atau area di peta untuk melihat informasi detail</li>
                        </ol>
                        <p class="mt-3">Anda dapat mengatur transparansi layer, memilih layer yang ingin ditampilkan,
                            dan mengunduh data terkait melalui panel yang tersedia.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 3: Proyek Strategis -->
            <div
                class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                <input type="radio" name="faq" id="faq3" class="hidden peer">
                <label for="faq3"
                    class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-5 md:after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                    tabindex="0">
                    <h3 class="pr-4 md:pr-0"><i class="bi bi-building me-2"></i> Apa perbedaan Proyek Strategis Daerah dan Nasional?</h3>
                </label>
                <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                    <div class="text-gray-700 text-sm md:text-md leading-relaxed">
                        <p class="mb-3">MARIMOI membedakan dua jenis proyek strategis:</p>

                        <div class="mb-4">
                            <h5 class="font-semibold text-gray-800 mb-2">Proyek Strategis Daerah (PSD)</h5>
                            <ul class="list-disc list-inside space-y-1 pl-4">
                                <li>Proyek prioritas yang diinisiasi dan dikelola oleh Pemerintah Provinsi Maluku Utara
                                </li>
                                <li>Pendanaan utama berasal dari APBD Provinsi</li>
                                <li>Fokus pada pengembangan infrastruktur dan layanan publik tingkat provinsi</li>
                                <li>Dikoordinasikan oleh Bappeda Provinsi Maluku Utara</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <h5 class="font-semibold text-gray-800 mb-2">Proyek Strategis Nasional (PSN)</h5>
                            <ul class="list-disc list-inside space-y-1 pl-4">
                                <li>Proyek prioritas nasional yang berlokasi di Maluku Utara</li>
                                <li>Pendanaan utama berasal dari APBN atau kombinasi dengan sumber lain</li>
                                <li>Bagian dari rencana pembangunan nasional</li>
                                <li>Dikoordinasikan oleh Kementerian/Lembaga terkait dengan dukungan Pemerintah Provinsi
                                </li>
                            </ul>
                        </div>

                        <p>Untuk melihat detail proyek strategis, klik menu dropdown "Proyek Strategis" pada navbar dan
                            pilih jenis proyek yang ingin dilihat.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 4: Prioritas Daerah -->
            <div
                class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                <input type="radio" name="faq" id="faq4" class="hidden peer">
                <label for="faq4"
                    class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-5 md:after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                    tabindex="0">
                    <h3 class="pr-4 md:pr-0"><i class="bi bi-bullseye me-2"></i> Apa itu Prioritas Daerah 2025-2029?</h3>
                </label>
                <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                    <div class="text-gray-700 text-sm md:text-md leading-relaxed">
                        <p class="mb-3">Prioritas Daerah 2025-2029 adalah dokumen perencanaan yang berisi
                            program-program prioritas jangka menengah Pemerintah Provinsi Maluku Utara untuk periode
                            2025-2029. Dokumen ini menjadi acuan dalam perencanaan dan penganggaran pembangunan daerah.
                        </p>

                        <p class="mb-2 font-medium">Fitur ini menampilkan:</p>
                        <ul class="list-disc list-inside space-y-1 pl-4 mb-4">
                            <li>Visi dan misi pembangunan daerah 2025-2029</li>
                            <li>Program-program prioritas berdasarkan sektor</li>
                            <li>Target dan indikator capaian</li>
                            <li>Strategi implementasi</li>
                        </ul>

                        <p class="mb-2 font-medium">Untuk mengakses informasi Prioritas Daerah 2025-2029:</p>
                        <ol class="list-decimal list-inside space-y-2 pl-4 mb-3">
                            <li>Klik menu "Prioritas Daerah 2025-2029" pada navbar</li>
                            <li>Dokumen akan ditampilkan dalam format slide yang dapat di-scroll secara horizontal</li>
                            <li>Gunakan mouse wheel atau geser ke kanan/kiri untuk melihat seluruh isi dokumen</li>
                        </ol>

                        <p>Dokumen ini penting bagi masyarakat, pelaku usaha, dan pemangku kepentingan lainnya untuk
                            memahami arah pembangunan Maluku Utara dalam 5 tahun ke depan.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 5: Musrenbang -->
            <div
                class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                <input type="radio" name="faq" id="faq5" class="hidden peer">
                <label for="faq5"
                    class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-5 md:after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                    tabindex="0">
                    <h3 class="pr-4 md:pr-0"><i class="bi bi-people me-2"></i> Apa itu Musrenbang dan bagaimana melihat usulannya?</h3>
                </label>
                <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                    <div class="text-gray-700 text-sm md:text-md leading-relaxed">
                        <p class="mb-3">Musrenbang (Musyawarah Perencanaan Pembangunan) adalah forum diskusi untuk
                            menyusun rencana pembangunan daerah yang melibatkan berbagai pemangku kepentingan, mulai
                            dari tingkat desa/kelurahan hingga provinsi.</p>

                        <p class="mb-2 font-medium">Fitur Usulan Musrenbang di MARIMOI menampilkan:</p>
                        <ul class="list-disc list-inside space-y-1 pl-4 mb-4">
                            <li>Pemetaan usulan pembangunan hasil Musrenbang</li>
                            <li>Informasi detail setiap usulan (lokasi, anggaran, deskripsi)</li>
                            <li>Status tindak lanjut usulan</li>
                            <li>Distribusi usulan berdasarkan wilayah dan sektor</li>
                        </ul>

                        <p class="mb-2 font-medium">Untuk melihat usulan Musrenbang:</p>
                        <ol class="list-decimal list-inside space-y-2 pl-4 mb-3">
                            <li>Klik menu "Musrenbang" pada navbar</li>
                            <li>Gunakan filter yang tersedia untuk menyaring usulan berdasarkan tahun, wilayah, atau
                                sektor</li>
                            <li>Klik pada usulan tertentu untuk melihat detail lengkapnya</li>
                            <li>Lihat peta sebaran usulan untuk analisis spasial</li>
                        </ol>

                        <p>Fitur ini memungkinkan masyarakat untuk memantau tindak lanjut dari usulan yang telah
                            disampaikan melalui forum Musrenbang, sehingga meningkatkan transparansi proses perencanaan
                            pembangunan.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 6: Pokir DPRD -->
            <div
                class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                <input type="radio" name="faq" id="faq6" class="hidden peer">
                <label for="faq6"
                    class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-5 md:after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                    tabindex="0">
                    <h3 class="pr-4 md:pr-0"><i class="bi bi-award me-2"></i> Apa itu Pokir DPRD dan bagaimana melihat datanya?</h3>
                </label>
                <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                    <div class="text-gray-700 text-sm md:text-md leading-relaxed">
                        <p class="mb-3">Pokir DPRD (Pokok Pikiran DPRD) adalah usulan program/kegiatan yang
                            disampaikan oleh anggota DPRD (Dewan Perwakilan Rakyat Daerah) berdasarkan aspirasi
                            masyarakat di daerah pemilihannya untuk dimasukkan dalam perencanaan pembangunan daerah.</p>

                        <p class="mb-2 font-medium">Fitur Pokir DPRD di MARIMOI menampilkan:</p>
                        <ul class="list-disc list-inside space-y-1 pl-4 mb-4">
                            <li>Pemetaan usulan pembangunan dari anggota DPRD</li>
                            <li>Informasi detail setiap usulan (lokasi, anggaran, pengusul)</li>
                            <li>Status tindak lanjut usulan</li>
                            <li>Distribusi usulan berdasarkan daerah pemilihan dan fraksi</li>
                        </ul>

                        <p class="mb-2 font-medium">Untuk melihat data Pokir DPRD:</p>
                        <ol class="list-decimal list-inside space-y-2 pl-4 mb-3">
                            <li>Klik menu "Pokir DPRD" pada navbar</li>
                            <li>Gunakan filter yang tersedia untuk menyaring usulan berdasarkan tahun, dapil, atau
                                fraksi</li>
                            <li>Klik pada usulan tertentu untuk melihat detail lengkapnya</li>
                            <li>Lihat peta sebaran usulan untuk analisis spasial</li>
                        </ol>

                        <p>Fitur ini meningkatkan transparansi proses politik dan perencanaan pembangunan dengan
                            memungkinkan masyarakat memantau usulan dari wakil rakyat mereka dan tindak lanjutnya oleh
                            pemerintah daerah.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 7: Usulan Aspirasi -->
            <div
                class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                <input type="radio" name="faq" id="faq7" class="hidden peer">
                <label for="faq7"
                    class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-5 md:after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                    tabindex="0">
                    <h3 class="pr-4 md:pr-0"><i class="bi bi-chat-dots me-2"></i> Bagaimana cara menyampaikan Usulan Aspirasi?</h3>
                </label>
                <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                    <div class="text-gray-700 text-sm md:text-md leading-relaxed">
                        <p class="mb-3">Fitur Usulan Aspirasi memungkinkan masyarakat untuk menyampaikan usulan
                            pembangunan atau kritik & saran terkait layanan sistem. Untuk menyampaikan aspirasi:</p>

                        <ol class="list-decimal list-inside space-y-2 pl-4 mb-4">
                            <li>Klik menu "Usulan Aspirasi" pada navbar</li>
                            <li>Isi formulir dengan data diri Anda (nama, alamat, email, dan nomor WhatsApp)</li>
                            <li>Pilih jenis aspirasi:
                                <ul class="list-disc list-inside pl-6 mt-1 space-y-1">
                                    <li><strong>Usulan Pembangunan</strong> - Untuk mengusulkan proyek pembangunan baru
                                        dengan lokasi spesifik</li>
                                    <li><strong>Kritik & Saran</strong> - Untuk memberikan masukan umum tanpa perlu
                                        menentukan lokasi</li>
                                </ul>
                            </li>
                            <li>Untuk Usulan Pembangunan:
                                <ul class="list-disc list-inside pl-6 mt-1 space-y-1">
                                    <li>Pilih kategori usulan</li>
                                    <li>Tentukan lokasi pada peta (klik pada peta atau gunakan lokasi saat ini)</li>
                                </ul>
                            </li>
                            <li>Isi judul dan pesan aspirasi secara jelas dan detail</li>
                            <li>Lampirkan file pendukung jika diperlukan (opsional)</li>
                            <li>Centang persetujuan dan selesaikan captcha</li>
                            <li>Klik tombol "Kirim Aspirasi"</li>
                        </ol>

                        <p class="mb-2 font-medium">Setelah aspirasi dikirim:</p>
                        <ul class="list-disc list-inside space-y-1 pl-4">
                            <li>Anda akan menerima notifikasi bahwa aspirasi telah diterima</li>
                            <li>Aspirasi akan diproses dan ditinjau oleh tim terkait</li>
                            <li>Anda mungkin akan dihubungi melalui email atau WhatsApp untuk tindak lanjut</li>
                        </ul>

                        <p class="mt-3">Fitur ini merupakan saluran resmi bagi masyarakat untuk berpartisipasi dalam
                            perencanaan pembangunan dan peningkatan layanan publik di Maluku Utara.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Item 8: Kontak dan Bantuan -->
            <div
                class="tab mb-4 px-5 py-4 bg-white shadow-lg rounded-lg relative transition-all duration-300 hover:shadow-xl">
                <input type="radio" name="faq" id="faq8" class="hidden peer">
                <label for="faq8"
                    class="flex items-center text-sm md:text-lg font-semibold cursor-pointer py-2 px-3 rounded-md
                           after:absolute after:content-['+'] after:right-5 md:after:right-10 after:text-2xl 
                           after:text-gray-400 hover:after:text-gray-800 peer-checked:after:transform peer-checked:after:rotate-45 
                           after:transition-transform after:duration-300"
                    tabindex="0">
                    <h3 class="pr-4 md:pr-0"><i class="bi bi-question-circle me-2"></i> Bagaimana jika saya membutuhkan bantuan lebih
                        lanjut?</h3>
                </label>
                <div class="answer mt-0 overflow-hidden transition-all ease-in-out duration-300 peer-checked:pt-4">
                    <div class="text-gray-700 text-sm md:text-md leading-relaxed">
                        <p class="mb-3">Jika Anda membutuhkan bantuan lebih lanjut terkait penggunaan MARIMOI atau
                            memiliki pertanyaan yang tidak terjawab di FAQ ini, Anda dapat:</p>

                        <ul class="list-disc list-inside space-y-2 pl-4">
                            <li>Menghubungi Bappeda Provinsi Maluku Utara melalui:
                                <ul class="list-disc list-inside pl-6 mt-1 space-y-1">
                                    <li>Email: bappeda.provmalut024@gmail.com</li>
                                    <li>Kunjungan langsung ke kantor Bappeda Provinsi Maluku Utara</li>
                                </ul>
                            </li>
                            <li>Menggunakan fitur Usulan Aspirasi dengan memilih jenis "Kritik & Saran" untuk
                                menyampaikan pertanyaan atau masukan</li>
                            <li>Mengunjungi website resmi Bappeda Maluku Utara di <a
                                    href="https://bappeda.malutprov.go.id/" target="_blank"
                                    class="text-blue-600 hover:text-blue-800 underline">https://bappeda.malutprov.go.id/</a>
                            </li>
                        </ul>

                        <p class="mt-3">Tim MARIMOI berkomitmen untuk memberikan bantuan dan dukungan kepada pengguna
                            dalam memanfaatkan platform ini secara optimal.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section><!-- /FAQ Section -->


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add keyboard support for accordion
            const labels = document.querySelectorAll('.tab label');
            const radioButtons = document.querySelectorAll('input[name="faq"]');

            labels.forEach((label, index) => {
                // Handle keyboard events
                label.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleAccordion(index);
                    }
                });

                // Handle click events
                label.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleAccordion(index);
                });
            });

            function toggleAccordion(index) {
                const radio = radioButtons[index];

                // If the clicked accordion is already open, close it
                if (radio.checked) {
                    radio.checked = false;
                    // Trigger change event manually for CSS transitions
                    radio.dispatchEvent(new Event('change'));
                } else {
                    // Close all other accordions and open the clicked one
                    radioButtons.forEach((otherRadio, otherIndex) => {
                        if (otherIndex !== index) {
                            otherRadio.checked = false;
                            otherRadio.dispatchEvent(new Event('change'));
                        }
                    });
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            }
        });
    </script>
@endpush
