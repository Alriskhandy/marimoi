@extends('frontend.layouts.app', ['title' => 'Usulan Aspirasi'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/detail.css') }}">
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
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
                        <div class="content">
                            <ul class="list-unstyled mb-3">
                                <li class="text-justify mb-2">Formulir ini digunakan untuk menyampaikan saran,
                                    pengaduan,
                                    apresiasi, atau pertanyaan terkait proyek.</li>
                                <hr>
                                <li><strong>Langkah-langkah pengisian:</strong>
                                    <ol class="mb-0">
                                        <li>Isi nama lengkap.</li>
                                        <li>Masukkan email aktif.</li>
                                        <li>Isi nomor WhatsApp.</li>
                                        <li>Pilih jenis tanggapan.</li>
                                        <li>Tuliskan tanggapan secara jelas.</li>
                                        <li>Unggah gambar (wajib untuk pengaduan).</li>
                                    </ol>
                                </li>
                                <hr>
                                <li class="mt-3"><strong>Catatan:</strong>
                                    <ul class="mb-0">
                                        <li>Gambar penting untuk memperjelas pengaduan.</li>
                                        <li>Email aktif dibutuhkan untuk tindak lanjut.</li>
                                        <li>Masukan Anda akan diproses dan ditindaklanjuti.</li>
                                        <li>Notifikasi akan dikirim lewat email atau WhatsApp.</li>
                                    </ul>
                                </li>
                                <li class="mt-3"><strong>Privasi:</strong><br>
                                    Data Anda aman dan hanya digunakan untuk penanganan masukan.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 col-md-12">
                    <div class="feedback-card h-100 p-4">
                        <h3 class="section-title mb-4">Formulir Usulan Aspirasi</h3>
                        <div class="card-body">
                            <form action="{{ route('aspirasi.store') }}" method="post" enctype="multipart/form-data"
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
                                                <option value="saran">Kritik & Saran</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="type" class="form-label">Kategori Usulan <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                            <select name="kategori_aspirasi_id" id="kategori_aspirasi_id"
                                                class="form-select" required>
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
                                                class="form-control" placeholder="Judul Usulan" required
                                                title="Masukkan Judul yang menggambarkan isi usulan">
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
                                        <button type="submit" class="btn btn-primary btn-lg px-5">
                                            <i class="bi bi-send me-3"></i>Kirim Aspirasi
                                        </button>
                                        <div class="sent-message alert alert-success mt-4 mb-0 d-none">
                                            <i class="bi bi-check-circle me-2"></i>Tanggapan Anda telah dikirim. Terima
                                            kasih atas kontribusi Anda untuk pengembangan layanan kami!
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

    <script>
        document.getElementById('formUsulan').addEventListener('submit', function(event) {
            event.preventDefault();

            // Menampilkan pesan sukses
            var sentMessage = document.querySelector('.sent-message');
            sentMessage.classList.remove('d-none');
            sentMessage.classList.add('d-block');

            // Reset form
            this.reset();

            // Menyembunyikan pesan setelah 5 detik
            setTimeout(function() {
                sentMessage.classList.remove('d-block');
                sentMessage.classList.add('d-none');
            }, 5000);
        });
    </script>
@endsection
