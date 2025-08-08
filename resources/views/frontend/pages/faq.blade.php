@extends('frontend.layouts.app', ['title' => 'Usulan Aspirasi'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/aspirasi.css') }}">
@endpush

@section('main')
    <!-- Navbar Section -->
    @include('frontend.partials.navbar')

    <!-- Usulan Section -->
    <section class="usulan-section">
        <!-- Section Title -->
        <div class="container section-title mb-4" data-aos="fade-up">
            <h2 class="title pt-4">Usulan Aspirasi Masyarakat</h2>
        </div>

        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row justify-content-center g-4">
                <div class="col-lg-4 col-md-12">
                    <div class="feedback-info-card h-100 p-4">
                        <h3 class="section-title mb-4">Petunjuk Pengisian</h3>
                        <p class="text-justify mb-3">Formulir ini digunakan untuk menyampaikan usulan pembangunan atau kritik & saran terkait layanan sistem.</p>
                        
                        <div class="accordion instruction-accordion" id="instructionAccordion">
                            <!-- Jenis Aspirasi -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        <i class="bi bi-info-circle me-2"></i> Jenis Aspirasi
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#instructionAccordion">
                                    <div class="accordion-body">
                                        <p class="mb-2">Ada 2 jenis aspirasi yang dapat disampaikan:</p>
                                        <ul class="mb-0">
                                            <li><strong>Usulan Pembangunan</strong> - Untuk mengusulkan proyek pembangunan baru dengan lokasi spesifik</li>
                                            <li><strong>Kritik & Saran</strong> - Untuk memberikan masukan umum tanpa perlu menentukan lokasi</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Langkah Pengisian -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        <i class="bi bi-list-ol me-2"></i> Langkah Pengisian
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#instructionAccordion">
                                    <div class="accordion-body">
                                        <ol class="mb-0">
                                            <li>Isi data diri Anda (nama, alamat, email, dan nomor WhatsApp)</li>
                                            <li>Pilih jenis aspirasi (Usulan atau Kritik & Saran)</li>
                                            <li>Untuk Usulan: pilih kategori dan tentukan lokasi pada peta</li>
                                            <li>Isi judul dan pesan aspirasi secara jelas</li>
                                            <li>Lampirkan file pendukung jika diperlukan</li>
                                            <li>Centang persetujuan dan selesaikan captcha</li>
                                            <li>Klik tombol "Kirim Aspirasi"</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Lokasi Usulan -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        <i class="bi bi-geo-alt me-2"></i> Lokasi Usulan
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#instructionAccordion">
                                    <div class="accordion-body">
                                        <p class="mb-2">Untuk jenis aspirasi "Usulan Pembangunan", Anda perlu menentukan lokasi:</p>
                                        <ul class="mb-0">
                                            <li>Klik tombol "Gunakan Lokasi Saat Ini" untuk menggunakan lokasi Anda sekarang</li>
                                            <li>Atau klik langsung pada peta untuk memilih lokasi yang diinginkan</li>
                                            <li>Lokasi yang dipilih akan ditandai dengan pin pada peta</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Lampiran -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        <i class="bi bi-paperclip me-2"></i> Lampiran
                                    </button>
                                </h2>
                                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#instructionAccordion">
                                    <div class="accordion-body">
                                        <p class="mb-2">Anda dapat melampirkan file pendukung:</p>
                                        <ul class="mb-0">
                                            <li>Format yang didukung: gambar (JPG, PNG, GIF), PDF, DWG, DXF</li>
                                            <li>Ukuran maksimal file: 5MB</li>
                                            <li>Lampiran dapat berupa foto lokasi, sketsa, atau dokumen pendukung lainnya</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Privasi -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFive">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                        <i class="bi bi-shield-lock me-2"></i> Privasi Data
                                    </button>
                                </h2>
                                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#instructionAccordion">
                                    <div class="accordion-body">
                                        <p class="mb-0">Data yang Anda berikan akan digunakan untuk:</p>
                                        <ul class="mb-0">
                                            <li>Memproses aspirasi yang Anda sampaikan</li>
                                            <li>Menghubungi Anda terkait tindak lanjut aspirasi</li>
                                            <li>Data Anda tidak akan dibagikan kepada pihak ketiga tanpa persetujuan</li>
                                            <li>Aspirasi yang disampaikan akan ditinjau oleh tim terkait</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 col-md-12">
                    <div class="feedback-card h-100 p-4">
                        <h3 class="section-title mb-4">Formulir Usulan Aspirasi</h3>
                        <div class="card-body">
                            <form action="/aspirasi-masyarakat" method="post" enctype="multipart/form-data"
                                id="formUsulan">
                                @csrf
                                <div class="row gy-4">

                                    <div class="col-md-6">
                                        <label for="nama_pengirim" class="form-label">Nama Lengkap <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" name="nama_pengirim" id="nama_pengirim"
                                                class="form-control" placeholder="Nama Lengkap Anda" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="alamat" class="form-label">Alamat <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-geo"></i></span>
                                            <input type="text" name="alamat" id="alamat" class="form-control"
                                                placeholder="Masukkan Alamat Anda" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="email" class="form-label">Email Aktif <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" name="email" id="email" class="form-control"
                                                placeholder="Email Anda" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="phone" class="form-label">No WhatsApp <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
                                            <input type="text" name="phone" id="phone" class="form-control"
                                                placeholder="Masukkan No WA" required pattern="^\+?\d{10,15}$"
                                                title="Masukkan Nomor WhatsApp yang valid">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="type" class="form-label">Jenis Aspirasi <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-list-ul"></i></span>
                                            <select name="jenis_aspirasi" id="jenis_aspirasi" class="form-select" required>
                                                <option value="" disabled selected>-- Pilih Jenis Aspirasi --</option>
                                                <option value="usulan">Usulan Pembangunan</option>
                                                <option value="kritik & saran">Kritik & Saran</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6" id="kategoriUsulanContainer" style="display: none;">
                                        <label for="type" class="form-label">Kategori Usulan <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                            <select name="kategori_aspirasi_id" id="kategori_aspirasi_id"
                                                class="form-select">
                                                <option value="" disabled selected>-- Pilih Kategori Usulan --
                                                </option>
                                                @foreach ($aspirasi as $item)
                                                    <option value="{{ $item->id }}">{{ $item->nama_kategori }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="judul_aspirasi" class="form-label">Judul <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                                            <input type="text" name="judul_aspirasi" id="judul_aspirasi"
                                                class="form-control" placeholder="Judul Aspirasi" required
                                                title="Masukkan Judul yang menggambarkan isi aspirasi">
                                        </div>
                                    </div>


                                    <div class="col-md-12">
                                        <label for="isi_aspirasi" class="form-label">Pesan <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-pencil"></i></span>
                                            <textarea name="isi_aspirasi" id="isi_aspirasi" class="form-control" rows="6"
                                                placeholder="Berikan usulan pengembangan wilayah atau kritik & saran untuk peningkatan layanan sistem." required></textarea>
                                        </div>
                                    </div>

                                    <!-- Map Container -->
                                    <div class="col-md-12" id="mapContainer" style="display: none;">
                                        <label class="form-label">Lokasi Usulan <span class="text-danger">*</span></label>
                                        <div class="input-group mb-2">
                                            <button type="button" class="btn btn-outline-primary" id="getLocationBtn">
                                                <i class="bi bi-geo-alt"></i> Gunakan Lokasi Saat Ini
                                            </button>
                                        </div>
                                        <div id="map"></div>
                                        <div class="form-text">Klik pada peta untuk memilih lokasi atau gunakan tombol
                                            lokasi saat ini</div>
                                        <!-- Hidden input fields for coordinates -->
                                        <input type="hidden" name="latitude" id="latitude">
                                        <input type="hidden" name="longitude" id="longitude">
                                    </div>

                                    <div class="col-md-12">
                                        <label for="lampiran" class="form-label">Lampiran</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-paperclip"></i></span>
                                            <input type="file" name="lampiran" id="lampiran" class="form-control"
                                                accept="image/*,.pdf,.dwg,.dxf">
                                        </div>
                                        <div class="form-text">Tambahkan lampiran jika diperlukan (maks. 5MB)</div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="agreement" required>
                                            <label class="form-check-label" for="agreement">
                                                Saya menyetujui bahwa informasi yang saya berikan adalah benar dan dapat
                                                dipertanggungjawabkan
                                                <span class="text-danger">*</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="h-captcha text-center"
                                            data-sitekey="{{ config('services.hcaptcha.sitekey_test') }}">
                                        </div>
                                    </div>

                                    <div class="col-md-12 text-center">
                                        <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn">
                                            <i class="bi bi-send me-3"></i><span id="submitText">Kirim Aspirasi</span>
                                        </button>
                                        <div class="sent-message alert alert-success mt-4 mb-0 d-none">
                                            <i class="bi bi-check-circle me-2"></i>Tanggapan Anda telah dikirim. Terima
                                            kasih atas kontribusi Anda untuk pengembangan layanan kami!
                                        </div>
                                    </div>

                                    <!-- Alert container -->
                                    <div class="col-md-12 text-center">
                                        <div id="alertContainer" class="mt-3 alert-container">
                                            <div id="alertMessage" class="alert" role="alert"></div>
                                        </div>
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </section><!-- /Form Section -->

    <!-- Footer Section -->
    @include('frontend.partials.footer')
@endsection

@push('scripts')
@endpush
