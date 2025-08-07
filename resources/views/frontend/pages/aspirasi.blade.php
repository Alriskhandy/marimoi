@extends('frontend.layouts.app', ['title' => 'Usulan Aspirasi'])

@section('main')
    <!-- Navbar Section -->
    @include('frontend.partials.navbar')

    <!-- Form Section -->
    <section class="section py-5" style="background: #f8fafc;">
        <!-- Section Title -->
        <div class="container section-title pt-5" data-aos="fade-up">
            <h2 class="mb-3">Usulan Aspirasi Masyarakat</h2>
            <p class="mt-3">Kirimkan usulan pengembangan infrastruktur atau berikan kritik dan saran anda untuk membantu kami meningkatkan kualitas layanan sistem</p>
        </div><!-- End Section Title -->

        <div class="container" data-aos="fade-up" data-aos-delay="100">
            <div class="row gy-4 justify-content-center pt-0">
                <div class="col-lg-8">
                    <div class="card feedback-card">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <div class="icon-circle bg-primary text-white d-inline-flex align-items-center justify-content-center rounded-circle mb-3 mx-auto" style="width: 70px; height: 70px;">
                                    <i class="bi bi-chat-left-text fs-3"></i>
                                </div>
                                <h4 class="mb-1">Formulir Kritik & Saran</h4>
                                <p class="text-muted mb-4">Silakan isi form di bawah ini dengan lengkap</p>
                            </div>
                            <form action="forms/contact.php" method="post" enctype="multipart/form-data" class="php-email-form"
                                data-aos="fade-up" data-aos-delay="200" id="complaintForm">
                                @csrf
                                <div class="row gy-4">

                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" name="name" id="name" class="form-control" placeholder="Masukkan nama lengkap Anda" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Aktif <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" name="email" id="email" class="form-control" placeholder="Masukkan email aktif Anda" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="whatsapp" class="form-label">No WhatsApp <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
                                            <input type="text" name="whatsapp" id="whatsapp" class="form-control" placeholder="Masukkan nomor WhatsApp" required pattern="^\+?\d{10,15}$" title="Masukkan nomor WhatsApp yang valid">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="type" class="form-label">Kategori <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                            <select name="type" id="type" class="form-select" required>
                                                <option value="" disabled selected>-- Pilih Kategori --</option>
                                                <option value="usulan">Usulan Pembangunan</option>
                                                <option value="saran">Saran atau Kritik Pengembangan</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label for="message" class="form-label">Pesan <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-pencil"></i></span>
                                            <textarea name="message" id="message" class="form-control" rows="6" placeholder="Tuliskan kritik atau saran Anda secara detail" required></textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label for="attachment" class="form-label">Lampiran</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-paperclip"></i></span>
                                            <input type="file" name="attachment" id="attachment" class="form-control" accept="image/*,.pdf,.dwg,.dxf">
                                        </div>
                                        <div class="form-text">Tambahkan lampiran jika diperlukan (maks. 5MB)</div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="agreement" required>
                                            <label class="form-check-label" for="agreement">
                                                Saya menyetujui bahwa informasi yang saya berikan adalah benar dan dapat dipertanggungjawabkan
                                                <span class="text-danger">*</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-12 text-center">
                                        <button type="submit" class="btn btn-primary btn-lg px-5">
                                            <i class="bi bi-send me-2"></i>Kirim Masukan
                                        </button>
                                        <div class="sent-message alert alert-success mt-4 mb-0 d-none">
                                            <i class="bi bi-check-circle me-2"></i>Tanggapan Anda telah dikirim. Terima kasih atas kontribusi Anda untuk pengembangan layanan kami!
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
        document.getElementById('complaintForm').addEventListener('submit', function(event) {
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
