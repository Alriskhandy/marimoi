@extends('frontend.layouts.app')

@section('main')
    <!-- Navbar Section -->
    @include('frontend.partials.navbar')

    <!-- Form Section -->
    <section class="section">
        <!-- Section Title -->
        <div class="container section-title mb-0" data-aos="fade-up">
            <span>Kritik & Saran Pengembangan<br></span>
            <h2>Kritik & Saran Pengembangan</h2>
        </div><!-- End Section Title -->
        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row gy-4">
                <div class="col-lg-8 mx-auto">
                    <h4 class="text-center text-secondary mb-3">Formulir Kritik & Saran Pengembangan</h4>
                    <form action="forms/contact.php" method="post" enctype="multipart/form-data" class="php-email-form"
                        data-aos="fade-up" data-aos-delay="200" id="complaintForm">
                        @csrf
                        <div class="row gy-4">

                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" placeholder="Nama" required>
                            </div>

                            <div class="col-md-6">
                                <input type="email" name="email" class="form-control" placeholder="Email Aktif"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <input type="text" name="whatsapp" class="form-control" placeholder="No WhatsApp"
                                    required pattern="^\+?\d{10,15}$" title="Masukkan nomor WhatsApp yang valid">
                            </div>

                            <div class="col-md-6">
                                <select name="type" id="type" class="form-select" required>
                                    <option class="text-muted" value="" disabled selected>-- Pilih Kategori --
                                    </option>
                                    <option value="kritik">Kritik</option>
                                    <option value="saran">Saran Pengembangan</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <textarea name="message" class="form-control" rows="6" placeholder="Isi Teks" required></textarea>
                            </div>

                            <div class="col-md-12">
                                <label for="attachment" class="form-label">Lampiran<span class="text-danger ms-3">*
                                        Tambahkan Lampiran Jika Ada</span></label>
                                <input type="file" name="attachment" id="attachment" class="form-control"
                                    accept="image/*,.pdf,.dwg,.dxf">
                            </div>

                            <div class="col-md-12 text-center d-grid gap-2">
                                <button type="submit" class="btn btn-md btn-outline-primary">Kirim Masukan</button>
                                {{-- <div class="loading">Loading</div>
                                <div class="error-message"></div> --}}
                                <div class="sent-message"
                                    style="display: none; opacity: 0; transition: opacity 1s ease-in-out, visibility 1s ease-in-out; visibility: hidden;">
                                    Tanggapan Anda telah dikirim. Terima kasih!</div>
                            </div>

                        </div>
                    </form>
                    <script>
                        document.getElementById('complaintForm').addEventListener('submit', function(event) {
                            var type = document.getElementById('type').value;
                            var attachment = document.getElementById('attachment');

                            // Menampilkan pesan sukses untuk dummy
                            var sentMessage = document.querySelector('.sent-message');
                            sentMessage.style.display = 'block'; // Tampilkan elemen
                            sentMessage.style.opacity = 1; // Set opacity ke 1 untuk tampak terlihat
                            sentMessage.style.visibility = 'visible'; // Set visibility menjadi visible

                            // Menyembunyikan pesan setelah 2 detik (2000 ms) dengan transisi
                            setTimeout(function() {
                                sentMessage.style.opacity = 0; // Gradual hide dengan opacity
                                sentMessage.style.visibility = 'hidden'; // Gradual hide dengan visibility
                            }, 2000); // 2000 ms = 2 detik
                        });
                    </script>
                </div><!-- End Form -->
            </div>

        </div>

    </section><!-- /Form Section -->

    <!-- Footer Section -->
    @include('frontend.partials.footer')
@endsection
