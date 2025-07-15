@extends('frontend.layouts.main')
@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@section('main')
    <!-- Hero Section -->
    @include('frontend.partials.nav-map')

    <!-- Hero Section -->
    <section id="hero" class="hero section dark-background position-relative">

        <img src="{{ asset('frontend/img/hero2.png') }}" alt="" class="hero-bg" data-aos="fade-in">

        <div class="overlay-dark position-absolute top-0 start-0 w-100 h-100"
            style="z-index: 1; background-color: rgba(0, 0, 0, 0.5);"></div>

        <div class="container position-relative" style="z-index: 2;">
            <div class="row gy-4 d-flex justify-content-center">
                <div class="col-lg-10 order-2 order-lg-1 d-flex flex-column justify-content-center">
                    <h2 class="text-center text-white" data-aos="fade-up">SISTEM INFORMASI MANAJEMEN AKSELERASI
                        INFRASTRUKTUR UNTUK MONITORING DAN INTEGRASI WILAYAH</h2>
                    <p class="text-white text-center" data-aos="fade-up" data-aos-delay="100">Sistem digital terpadu
                        berbasis web dan mobile yang dikembangkan untuk mendukung perencanaan, pelaksanaan, pemantauan, dan
                        evaluasi pembangunan infrastruktur daerah secara lebih efektif, partisipatif, dan terintegrasi.
                        Sistem ini menyasar penguatan sinergi lintas sektor dan wilayah dalam mendukung pembangunan wilayah
                        Provinsi Maluku Utara.</p>
                </div>

                <div class="col-lg-5 order-1 order-lg-2 hero-img" data-aos="zoom-out">
                    <img src="assets/img/hero-img.svg" class="img-fluid mb-3 mb-lg-0" alt="">
                </div>

            </div>
        </div>

    </section><!-- /Hero Section -->

    <!-- Detail Section -->
    <section class="section py-4">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
            <span>Detail Lokasi<br></span>
            <h2>Detail Lokasi</h2>
        </div><!-- End Section Title -->

        <div class="container">
            @if (isset($project))
                <div class="row gy-4">

                    <!-- Detail Map -->
                    <div class="col-md-4 col-lg-6">
                        <div id="map-detail" class="mb-4" style="border:0; width: 100%; height: 400px; min-height: 300px;"
                            data-aos="fade-up" data-aos-delay="200"></div>
                    </div><!-- End Detail Map -->

                    <div class="col-md-8 col-lg-6">

                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>KATEGORI</th>
                                    <td>{{ $project->kategori->nama }}</td>
                                </tr>
                                <tr>
                                    <th>DESKRIPSI</th>
                                    <td>{{ $project->deskripsi }}</td>
                                </tr>
                                @if (isset($project->dbf_attributes))
                                    @php
                                        $dbfAttributes = is_string($project->dbf_attributes)
                                            ? json_decode($project->dbf_attributes, true)
                                            : $project->dbf_attributes;
                                    @endphp
                                    @foreach ($dbfAttributes as $key => $value)
                                        @if (strtolower($key) !== 'id')
                                            <tr>
                                                <th>{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                                <td>{{ $value }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <p>Data proyek tidak ditemukan.</p>
            @endif
        </div>
    </section><!-- /Detail Section -->

    <!-- Form Section -->
    <section class="section py-4">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
            <span>Aspirasi & Pengaduan<br></span>
            <h2>Aspirasi & Pengaduan</h2>
        </div><!-- End Section Title -->
        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row gy-4">

                <div class="col-lg-8">
                    <h4 class="text-center text-secondary mb-3">Formulir Aspirasi / Pengaduan</h4>
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
                                    <option value="aspirasi">Aspirasi</option>
                                    <option value="pengaduan">Pengaduan</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <textarea name="message" class="form-control" rows="6" placeholder="Isi Teks" required></textarea>
                            </div>

                            <div class="col-md-12">
                                <label for="attachment" class="form-label">Lampiran (drawing atau foto) <span
                                        class="text-danger fw-bold">* Wajib Unggah Lampiran Untuk Jenis
                                        Pengaduan</span></label>
                                <input type="file" name="attachment" id="attachment" class="form-control"
                                    accept="image/*,.pdf,.dwg,.dxf">
                            </div>

                            <div class="col-md-12 text-center d-grid gap-2">
                                <div class="loading">Loading</div>
                                <div class="error-message"></div>
                                <div class="sent-message">Tanggapan Anda telah dikirim. Terima kasih!</div>

                                <button type="submit" class="btn btn-md btn-outline-success">Kirim</button>
                            </div>

                        </div>
                    </form>
                    <script>
                        document.getElementById('complaintForm').addEventListener('submit', function(event) {
                            var type = document.getElementById('type').value;
                            var attachment = document.getElementById('attachment');
                            if (type === 'pengaduan' && attachment.files.length === 0) {
                                event.preventDefault();
                                alert('Lampiran wajib diisi untuk jenis pengaduan.');
                                attachment.focus();
                            }
                        });
                    </script>
                </div><!-- End Form -->

                <div class="col-lg-4">
                    <h4 class="text-center text-secondary mb-3">Keterangan</h4>
                    <ul>
                        <li>Pastikan alamat email & nomor whatsapp aktif dan valid.</li>
                        <li>Pengaduan tidak akan di proses jika alamat email & nomor whatsapp tidak valid.</li>
                        <li>Identitas pengirim tidak akan diungkapkan.</li>
                    </ul>
                </div>
            </div>

        </div>

    </section><!-- /Form Section -->

    <!-- Footer Section -->
    @include('frontend.partials.footer')
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (isset($project) && isset($project->geojson))
                // Initialize the map
                var map = L.map('map-detail').setView([0, 0], 13);

                // Add OpenStreetMap tile layer
                L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        id: "esri-world-imagery",
                        label: "ESRI World Imagery",
                        minZoom: 6,
                        maxZoom: 20,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);

                // Parse geometry JSON
                var geometry = {!! json_encode($project->geojson) !!};

                var layer;

                if (geometry.type === 'Point') {
                    var coords = [geometry.coordinates[1], geometry.coordinates[0]];
                    layer = L.marker(coords).addTo(map);
                    map.setView(coords, 15);
                } else if (geometry.type === 'LineString') {
                    var latlngs = geometry.coordinates.map(function(coord) {
                        return [coord[1], coord[0]];
                    });
                    layer = L.polyline(latlngs).addTo(map);
                    map.fitBounds(layer.getBounds());
                } else if (geometry.type === 'Polygon') {
                    var latlngs = geometry.coordinates[0].map(function(coord) {
                        return [coord[1], coord[0]];
                    });
                    layer = L.polygon(latlngs).addTo(map);
                    map.fitBounds(layer.getBounds());
                }

                // Add popup with feature title
                if (layer) {
                    layer.bindPopup("{{ $project->kategori->nama ?? 'Feature' }}").openPopup();
                }
            @endif
        });
    </script>
@endpush
