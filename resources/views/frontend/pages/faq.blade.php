@extends('frontend.layouts.app', ['title' => 'FAQ - Pertanyaan Umum'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/aspirasi.css') }}">
    <style>
        .faq-section {
            padding: 60px 0;
        }
        .faq-card {
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        .faq-accordion .accordion-button:not(.collapsed) {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            font-weight: 600;
        }
        .faq-accordion .accordion-button:focus {
            box-shadow: none;
            border-color: rgba(13, 110, 253, 0.25);
        }
        .faq-accordion .accordion-item {
            margin-bottom: 10px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .faq-accordion .accordion-body {
            padding: 20px;
        }
        .faq-accordion .accordion-button {
            padding: 15px 20px;
            font-weight: 500;
        }
        .section-description {
            color: #6c757d;
            max-width: 800px;
            margin: 0 auto 40px;
        }
        
        /* Responsive styles for mobile */
        @media (max-width: 767px) {
            .faq-section {
                padding: 40px 0;
            }
            .faq-card {
                padding: 15px !important;
            }
            .faq-accordion .accordion-button {
                padding: 12px 15px;
                font-size: 15px;
            }
            .faq-accordion .accordion-body {
                padding: 15px;
                font-size: 14px;
            }
            .faq-accordion .accordion-body ul,
            .faq-accordion .accordion-body ol {
                padding-left: 20px;
            }
            .faq-accordion .accordion-body li {
                margin-bottom: 8px;
            }
            .faq-accordion .accordion-body h5 {
                font-size: 15px;
                margin-top: 15px;
                margin-bottom: 10px;
            }
            .section-title h2 {
                font-size: 24px;
            }
            .section-description {
                font-size: 14px;
                margin-bottom: 25px;
            }
        }
        
        /* Extra small devices */
        @media (max-width: 575px) {
            .faq-section {
                padding: 30px 0;
            }
            .faq-card {
                padding: 10px !important;
                margin: 0 10px;
            }
            .faq-accordion .accordion-button {
                padding: 10px 12px;
                font-size: 14px;
            }
            .faq-accordion .accordion-body {
                padding: 12px;
                font-size: 13px;
            }
            .faq-accordion .accordion-body ul,
            .faq-accordion .accordion-body ol {
                padding-left: 15px;
            }
            .section-title h2 {
                font-size: 22px;
                margin-top: 40px;
            }
            .section-title p {
                font-size: 16px;
                margin-bottom: 20px;
            }
        }
    </style>
@endpush

@section('main')
    <!-- Navbar Section -->
    @include('frontend.partials.navbar')

    <!-- FAQ Section -->
    <section class="faq-section">
        <!-- Section Title -->
        <div class="container section-title mb-4" data-aos="fade-up">
            <h2 class="title pt-4">Pertanyaan yang Sering Diajukan (FAQ)</h2>
            <p class="section-description text-center">Temukan jawaban untuk pertanyaan umum tentang fitur dan layanan yang tersedia di platform MARIMOI.</p>
        </div>

        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="faq-card p-4">
                        <div class="accordion faq-accordion" id="faqAccordion">
                            
                            <!-- Tentang MARIMOI -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        <i class="bi bi-info-circle me-2"></i> Apa itu MARIMOI?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>MARIMOI adalah sistem digital terpadu untuk memperkuat koordinasi, pemantauan, dan integrasi pembangunan infrastruktur di Maluku Utara. Dengan pendekatan spasial dan peta tematik, sistem ini menyediakan data real-time yang mendukung perencanaan lintas sektor secara kolaboratif dan transparan.</p>
                                        <p>Fitur utama MARIMOI meliputi:</p>
                                        <ul>
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
                            
                            <!-- Peta Tematik -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        <i class="bi bi-map me-2"></i> Bagaimana cara menggunakan Peta Tematik?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Peta Tematik MARIMOI menyediakan visualisasi spasial dari berbagai data pembangunan di Maluku Utara. Untuk menggunakan fitur ini:</p>
                                        <ol>
                                            <li>Klik menu "Peta Tematik" pada navbar</li>
                                            <li>Gunakan tombol kontrol di sisi kanan peta untuk:
                                                <ul>
                                                    <li><i class="bi bi-info-circle-fill"></i> Bantuan - Melihat panduan penggunaan peta</li>
                                                    <li><i class="bi bi-list-ul"></i> Legenda Peta - Menampilkan keterangan simbol pada peta</li>
                                                    <li><i class="bi bi-grid-fill"></i> Basemap Peta - Memilih jenis peta dasar</li>
                                                    <li><i class="bi bi-layers-fill"></i> Layer Peta - Mengatur layer yang ingin ditampilkan</li>
                                                </ul>
                                            </li>
                                            <li>Gunakan tombol navigasi untuk:
                                                <ul>
                                                    <li><i class="bi bi-file-earmark-arrow-down-fill"></i> Download Peta - Mengunduh data atau informasi</li>
                                                    <li><i class="bi bi-arrows-fullscreen"></i> Fullscreen - Masuk/keluar dari tampilan penuh</li>
                                                    <li><i class="bi bi-house-door-fill"></i> Home - Kembali ke tampilan default peta</li>
                                                </ul>
                                            </li>
                                            <li>Klik pada marker atau area di peta untuk melihat informasi detail</li>
                                        </ol>
                                        <p>Anda dapat mengatur transparansi layer, memilih layer yang ingin ditampilkan, dan mengunduh data terkait melalui panel yang tersedia.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Proyek Strategis -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        <i class="bi bi-building me-2"></i> Apa perbedaan Proyek Strategis Daerah dan Nasional?
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>MARIMOI membedakan dua jenis proyek strategis:</p>
                                        
                                        <h5>Proyek Strategis Daerah (PSD)</h5>
                                        <ul>
                                            <li>Proyek prioritas yang diinisiasi dan dikelola oleh Pemerintah Provinsi Maluku Utara</li>
                                            <li>Pendanaan utama berasal dari APBD Provinsi</li>
                                            <li>Fokus pada pengembangan infrastruktur dan layanan publik tingkat provinsi</li>
                                            <li>Dikoordinasikan oleh Bappeda Provinsi Maluku Utara</li>
                                        </ul>
                                        
                                        <h5>Proyek Strategis Nasional (PSN)</h5>
                                        <ul>
                                            <li>Proyek prioritas nasional yang berlokasi di Maluku Utara</li>
                                            <li>Pendanaan utama berasal dari APBN atau kombinasi dengan sumber lain</li>
                                            <li>Bagian dari rencana pembangunan nasional</li>
                                            <li>Dikoordinasikan oleh Kementerian/Lembaga terkait dengan dukungan Pemerintah Provinsi</li>
                                        </ul>
                                        
                                        <p>Untuk melihat detail proyek strategis, klik menu dropdown "Proyek Strategis" pada navbar dan pilih jenis proyek yang ingin dilihat. Anda dapat melihat lokasi, anggaran, progres, dan informasi lainnya untuk setiap proyek.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Prioritas Daerah -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        <i class="bi bi-bullseye me-2"></i> Apa itu Prioritas Daerah 2025-2029?
                                    </button>
                                </h2>
                                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Prioritas Daerah 2025-2029 adalah dokumen perencanaan yang berisi program-program prioritas jangka menengah Pemerintah Provinsi Maluku Utara untuk periode 2025-2029. Dokumen ini menjadi acuan dalam perencanaan dan penganggaran pembangunan daerah.</p>
                                        
                                        <p>Fitur ini menampilkan:</p>
                                        <ul>
                                            <li>Visi dan misi pembangunan daerah 2025-2029</li>
                                            <li>Program-program prioritas berdasarkan sektor</li>
                                            <li>Target dan indikator capaian</li>
                                            <li>Strategi implementasi</li>
                                        </ul>
                                        
                                        <p>Untuk mengakses informasi Prioritas Daerah 2025-2029:</p>
                                        <ol>
                                            <li>Klik menu "Prioritas Daerah 2025-2029" pada navbar</li>
                                            <li>Dokumen akan ditampilkan dalam format slide yang dapat di-scroll secara horizontal</li>
                                            <li>Gunakan mouse wheel atau geser ke kanan/kiri untuk melihat seluruh isi dokumen</li>
                                        </ol>
                                        
                                        <p>Dokumen ini penting bagi masyarakat, pelaku usaha, dan pemangku kepentingan lainnya untuk memahami arah pembangunan Maluku Utara dalam 5 tahun ke depan.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Musrenbang -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFive">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                        <i class="bi bi-people me-2"></i> Apa itu Musrenbang dan bagaimana melihat usulannya?
                                    </button>
                                </h2>
                                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Musrenbang (Musyawarah Perencanaan Pembangunan) adalah forum diskusi untuk menyusun rencana pembangunan daerah yang melibatkan berbagai pemangku kepentingan, mulai dari tingkat desa/kelurahan hingga provinsi.</p>
                                        
                                        <p>Fitur Usulan Musrenbang di MARIMOI menampilkan:</p>
                                        <ul>
                                            <li>Pemetaan usulan pembangunan hasil Musrenbang</li>
                                            <li>Informasi detail setiap usulan (lokasi, anggaran, deskripsi)</li>
                                            <li>Status tindak lanjut usulan</li>
                                            <li>Distribusi usulan berdasarkan wilayah dan sektor</li>
                                        </ul>
                                        
                                        <p>Untuk melihat usulan Musrenbang:</p>
                                        <ol>
                                            <li>Klik menu "Musrenbang" pada navbar</li>
                                            <li>Gunakan filter yang tersedia untuk menyaring usulan berdasarkan tahun, wilayah, atau sektor</li>
                                            <li>Klik pada usulan tertentu untuk melihat detail lengkapnya</li>
                                            <li>Lihat peta sebaran usulan untuk analisis spasial</li>
                                        </ol>
                                        
                                        <p>Fitur ini memungkinkan masyarakat untuk memantau tindak lanjut dari usulan yang telah disampaikan melalui forum Musrenbang, sehingga meningkatkan transparansi proses perencanaan pembangunan.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pokir DPRD -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingSix">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                        <i class="bi bi-award me-2"></i> Apa itu Pokir DPRD dan bagaimana melihat datanya?
                                    </button>
                                </h2>
                                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Pokir DPRD (Pokok Pikiran DPRD) adalah usulan program/kegiatan yang disampaikan oleh anggota DPRD (Dewan Perwakilan Rakyat Daerah) berdasarkan aspirasi masyarakat di daerah pemilihannya untuk dimasukkan dalam perencanaan pembangunan daerah.</p>
                                        
                                        <p>Fitur Pokir DPRD di MARIMOI menampilkan:</p>
                                        <ul>
                                            <li>Pemetaan usulan pembangunan dari anggota DPRD</li>
                                            <li>Informasi detail setiap usulan (lokasi, anggaran, pengusul)</li>
                                            <li>Status tindak lanjut usulan</li>
                                            <li>Distribusi usulan berdasarkan daerah pemilihan dan fraksi</li>
                                        </ul>
                                        
                                        <p>Untuk melihat data Pokir DPRD:</p>
                                        <ol>
                                            <li>Klik menu "Pokir DPRD" pada navbar</li>
                                            <li>Gunakan filter yang tersedia untuk menyaring usulan berdasarkan tahun, dapil, atau fraksi</li>
                                            <li>Klik pada usulan tertentu untuk melihat detail lengkapnya</li>
                                            <li>Lihat peta sebaran usulan untuk analisis spasial</li>
                                        </ol>
                                        
                                        <p>Fitur ini meningkatkan transparansi proses politik dan perencanaan pembangunan dengan memungkinkan masyarakat memantau usulan dari wakil rakyat mereka dan tindak lanjutnya oleh pemerintah daerah.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Usulan Aspirasi -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingSeven">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                        <i class="bi bi-chat-dots me-2"></i> Bagaimana cara menyampaikan Usulan Aspirasi?
                                    </button>
                                </h2>
                                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Fitur Usulan Aspirasi memungkinkan masyarakat untuk menyampaikan usulan pembangunan atau kritik & saran terkait layanan sistem. Untuk menyampaikan aspirasi:</p>
                                        
                                        <ol>
                                            <li>Klik menu "Usulan Aspirasi" pada navbar</li>
                                            <li>Isi formulir dengan data diri Anda (nama, alamat, email, dan nomor WhatsApp)</li>
                                            <li>Pilih jenis aspirasi:
                                                <ul>
                                                    <li><strong>Usulan Pembangunan</strong> - Untuk mengusulkan proyek pembangunan baru dengan lokasi spesifik</li>
                                                    <li><strong>Kritik & Saran</strong> - Untuk memberikan masukan umum tanpa perlu menentukan lokasi</li>
                                                </ul>
                                            </li>
                                            <li>Untuk Usulan Pembangunan:
                                                <ul>
                                                    <li>Pilih kategori usulan</li>
                                                    <li>Tentukan lokasi pada peta (klik pada peta atau gunakan lokasi saat ini)</li>
                                                </ul>
                                            </li>
                                            <li>Isi judul dan pesan aspirasi secara jelas dan detail</li>
                                            <li>Lampirkan file pendukung jika diperlukan (opsional)</li>
                                            <li>Centang persetujuan dan selesaikan captcha</li>
                                            <li>Klik tombol "Kirim Aspirasi"</li>
                                        </ol>
                                        
                                        <p>Setelah aspirasi dikirim:</p>
                                        <ul>
                                            <li>Anda akan menerima notifikasi bahwa aspirasi telah diterima</li>
                                            <li>Aspirasi akan diproses dan ditinjau oleh tim terkait</li>
                                            <li>Anda mungkin akan dihubungi melalui email atau WhatsApp untuk tindak lanjut</li>
                                        </ul>
                                        
                                        <p>Fitur ini merupakan saluran resmi bagi masyarakat untuk berpartisipasi dalam perencanaan pembangunan dan peningkatan layanan publik di Maluku Utara.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Kontak dan Bantuan -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingEight">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                                        <i class="bi bi-question-circle me-2"></i> Bagaimana jika saya membutuhkan bantuan lebih lanjut?
                                    </button>
                                </h2>
                                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Jika Anda membutuhkan bantuan lebih lanjut terkait penggunaan MARIMOI atau memiliki pertanyaan yang tidak terjawab di FAQ ini, Anda dapat:</p>
                                        
                                        <ul>
                                            <li>Menghubungi Bappeda Provinsi Maluku Utara melalui:
                                                <ul>
                                                    <li>Email: bappeda@malutprov.go.id</li>
                                                    <li>Telepon: (0921) 3121125</li>
                                                    <li>Kunjungan langsung ke kantor Bappeda Provinsi Maluku Utara</li>
                                                </ul>
                                            </li>
                                            <li>Menggunakan fitur Usulan Aspirasi dengan memilih jenis "Kritik & Saran" untuk menyampaikan pertanyaan atau masukan</li>
                                            <li>Mengunjungi website resmi Bappeda Maluku Utara di <a href="https://bappeda.malutprov.go.id/" target="_blank">https://bappeda.malutprov.go.id/</a></li>
                                        </ul>
                                        
                                        <p>Tim MARIMOI berkomitmen untuk memberikan bantuan dan dukungan kepada pengguna dalam memanfaatkan platform ini secara optimal.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section><!-- /FAQ Section -->

    <!-- Footer Section -->
    @include('frontend.partials.footer')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId !== '#') {
                        document.querySelector(targetId).scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
@endpush

