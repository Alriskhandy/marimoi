@extends('frontend.layouts.main')
@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@section('main')
    <!-- Hero Section -->
    @include('frontend.partials.nav-map')

    <!-- Detail Section -->
    <section class="section py-4">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
            <span>Detail Kegiatan<br></span>
            <h2>Detail Kegiatan</h2>
        </div><!-- End Section Title -->

        <div class="container">
            @if (isset($project))
                <div class="row gy-4 align-items-stretch">
                    <!-- Detail Map -->
                    <div class="col-md-6">
                        <div id="map-detail" class="rounded shadow-sm h-100"
                            style="width: 100%; min-height: 300px; max-height: 400px; height: 100%;" data-aos="fade-up"
                            data-aos-delay="200">
                        </div>
                    </div>

                    <!-- Gambar -->
                    <div class="col-md-6">
                        <div class="h-100">
                            <img src="{{ asset('frontend/img/kantor-gub-malut.jpeg') }}" alt="Kantor Gubernur Malut"
                                class="img-fluid rounded shadow-sm w-100"
                                style="height: 100%; object-fit: cover; min-height: 300px; max-height: 400px;">
                        </div>
                    </div>
                </div>

                <!-- Deskripsi -->
                <div class="row gy-4 mt-4">
                    <div class="col-md-12">
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

    <!-- Form sederhana untuk testing - sesuai dengan contoh data -->
    <section class="section py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Form Feedback - Testing</h5>
                        </div>
                        <div class="card-body">
                            <!-- Alert container -->
                            <div id="alertContainer" style="display: none;">
                                <div id="alertMessage" class="alert" role="alert"></div>
                            </div>

                            <form id="feedbackForm" action="{{ route('usulan.feedback.store') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf

                                <!-- Hidden fields sesuai contoh data -->
                                <input type="hidden" name="project_type" value="proyek_strategis_daerah">
                                <input type="hidden" name="feedbackable_id" value="1">
                                <input type="hidden" name="nama_proyek" value="Pokir Pembangunan Jalan Desa">
                                <input type="hidden" name="kabupaten_kota" value="Ternate">
                                <input type="hidden" name="kecamatan" value="Ternate Selatan">
                                <input type="hidden" name="latitude" value="0.78930000">
                                <input type="hidden" name="longitude" value="127.37740000">

                                <!-- Form fields yang bisa diisi user -->
                                <div class="mb-3">
                                    <label for="nama_pemberi_aspirasi" class="form-label">Nama Pemberi Aspirasi <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_pemberi_aspirasi"
                                        name="nama_pemberi_aspirasi" required placeholder="Contoh: Ahmad Salam">
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="Contoh: ahmad.salam@email.com">
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">No. WhatsApp</label>
                                    <input type="text" class="form-control" id="phone" name="phone"
                                        placeholder="Contoh: 081234567890">
                                </div>

                                <div class="mb-3">
                                    <label for="jenis_tanggapan" class="form-label">Jenis Tanggapan <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" id="jenis_tanggapan" name="jenis_tanggapan" required>
                                        <option value="">-- Pilih Jenis --</option>
                                        <option value="keluhan">Pengaduan</option>
                                        <option value="saran" selected>Saran</option>
                                        <option value="apresiasi">Apresiasi</option>
                                        <option value="pertanyaan">Pertanyaan</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="tanggapan" class="form-label">Tanggapan <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" id="tanggapan" name="tanggapan" rows="4" required
                                        placeholder="Usulan Pokir ini sangat bagus untuk kemajuan desa kami. Mohon segera direalisasikan."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="laporan_gambar" class="form-label">Lampiran Gambar
                                        <span id="image_required" class="text-danger" style="display: none;">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="laporan_gambar" name="laporan_gambar"
                                        accept="image/jpeg,image/png,image/jpg,image/gif">
                                    <small class="text-muted">Maksimal 2MB. Format: JPG, PNG, GIF.
                                        <span id="image_note">Wajib untuk pengaduan.</span>
                                    </small>
                                </div>

                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <span id="submitText">Kirim Feedback</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('feedbackForm');
            const jenisTanggapan = document.getElementById('jenis_tanggapan');
            const imageRequired = document.getElementById('image_required');
            const imageNote = document.getElementById('image_note');
            const laporanGambar = document.getElementById('laporan_gambar');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');

            // Handle jenis tanggapan change
            jenisTanggapan.addEventListener('change', function() {
                if (this.value === 'keluhan') {
                    laporanGambar.setAttribute('required', 'required');
                    imageRequired.style.display = 'inline';
                    imageNote.textContent = 'Wajib untuk pengaduan.';
                    imageNote.className = 'text-danger fw-bold';
                } else {
                    laporanGambar.removeAttribute('required');
                    imageRequired.style.display = 'none';
                    imageNote.textContent = 'Opsional.';
                    imageNote.className = 'text-muted';
                }
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                submitBtn.disabled = true;
                submitText.textContent = 'Mengirim...';

                const formData = new FormData(this);

                fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showAlert('success', data.message);
                            form.reset();
                            // Reset image requirement
                            imageRequired.style.display = 'none';
                            imageNote.textContent = 'Wajib untuk pengaduan.';
                            imageNote.className = 'text-muted';
                        } else {
                            showAlert('error', data.message);
                            if (data.errors) {
                                console.log('Validation errors:', data.errors);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('error', 'Terjadi kesalahan saat mengirim data.');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitText.textContent = 'Kirim Feedback';
                    });
            });

            function showAlert(type, message) {
                const alertContainer = document.getElementById('alertContainer');
                const alertMessage = document.getElementById('alertMessage');

                alertMessage.className = `alert alert-${type === 'success' ? 'success' : 'danger'}`;
                alertMessage.textContent = message;
                alertContainer.style.display = 'block';

                // Auto hide after 5 seconds
                setTimeout(() => {
                    alertContainer.style.display = 'none';
                }, 5000);
            }
        });
    </script>

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
                        maxZoom: 18,
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
