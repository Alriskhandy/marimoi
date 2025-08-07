@extends('frontend.layouts.app', ['title' => 'Detail Kegiatan - MARIMOI'])
@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="{{ asset('frontend/css/detail.css') }}">
@endpush

@section('main')
    <!-- Hero Section -->
    @include('frontend.partials.navbar')

    <!-- Detail Section -->
    <section class="section-with-bg section-detail">
        <!-- Section Title -->
        <div class="container section-title mb-4" data-aos="fade-up">
            <h2 class="title pt-4">Detail Kegiatan</h2>
        </div>

        <div class="container">
            @if (isset($project))
                <div class="row g-4 align-items-stretch">
                    <!-- Detail Map -->
                    <div class="{{ $project->gambar ? 'col-lg-6' : 'col-12' }}">
                        <div class="card shadow h-100" data-aos="fade-up" data-aos-delay="100">
                            <div class="card-body">
                                <div id="map-detail" class="h-100" style="min-height: 400px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Gambar jika ada -->
                    @if (!empty($project->gambar))
                        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                            <div class="card shadow h-100">
                                <div class="card-body">
                                    <img src="{{ asset('storage/' . $project->gambar) }}" alt="{{ $project->judul }}"
                                        class="img-fluid project-image w-100 h-100 object-fit-cover">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Deskripsi -->
                <div class="row g-4 mt-4">
                    <div class="col-md-12" data-aos="fade-up" data-aos-delay="300">
                        <table class="table detail-table">
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

    @if ($project->data_type != 'tematik')
    <!-- Form Feedback Section -->
    <section class="section-with-bg feedback-section">
        <div class="container" data-aos="fade-up">
            <div class="row justify-content-center g-4">
                <div class="col-lg-7 col-md-12">
                    <div class="feedback-card h-100 p-4 border-end border-2 border-light">
                        <h3 class="section-title mb-4">Form Feedback</h3>
                        <div class="card-body">
                            <!-- Alert container -->
                            <div id="alertContainer" style="display: none;">
                                <div id="alertMessage" class="alert" role="alert"></div>
                            </div>

                            <form id="feedbackForm" action="{{ route('feedback.store') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf

                                <!-- Hidden fields sesuai contoh data -->
                                <input type="hidden" name="data_spatial_id" value="{{ $project->id }}">
                                <input type="hidden" name="nama_proyek" value="{{ $project->deskripsi ?? ($project->dbf_attributes['KEGIATAN'] ?? 'TANPA NAMA') }}">
                                <input type="hidden" name="kabupaten_kota" value="{{ $project->dbf_attributes['KABUPATEN'] ?? $project->dbf_attributes['KOTA'] ?? 'TANPA KABUPATEN/KOTA' }}">
                                <input type="hidden" name="kecamatan" value="{{ $project->dbf_attributes['KECAMATAN'] ?? 'TANPA KECAMATAN' }}">
                                <input type="hidden" name="latitude" id="latitude" value="">
                                <input type="hidden" name="longitude" id="longitude" value="">

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
                                        <option value="saran" selected>Saran</option>
                                        <option value="keluhan">Pengaduan</option>
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
                                    <div class="row g-3">
                                        <div class="col-lg-8 col-md-7">
                                            <input type="file" class="form-control" id="laporan_gambar" name="laporan_gambar"
                                                accept="image/jpeg,image/png,image/jpg,image/gif">
                                            <small class="text-muted d-block mt-1">Maksimal 2MB. Format: JPG, JPEG, PNG.
                                                <span id="image_note">Wajib untuk pengaduan.</span>
                                            </small>
                                        </div>
                                        <div class="col-lg-4 col-md-5">
                                            <div id="image_preview_container" class="image-preview-container" style="display: none;">
                                                <img id="image_preview" src="" alt="Preview" class="img-fluid rounded border image-preview">
                                                <button type="button" id="remove_image" class="btn btn-sm btn-danger mt-2 w-100 remove-btn">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </div>
                                            <div id="image_placeholder" class="image-placeholder text-center p-3 border rounded bg-light">
                                                <div class="text-muted">
                                                    <i class="bi bi-image placeholder-icon"></i>
                                                    <div class="small mt-1">Preview gambar</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Mobile Image Preview with 4:3 aspect ratio -->
                                    <div class="mobile-image-preview" id="mobileImagePreview">
                                        <img id="mobilePreviewImage" src="" alt="Mobile Preview">
                                    </div>
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
                <div class="col-lg-5 col-md-12">
                    <div class="feedback-info-card h-100 p-4">
                        <h3 class="section-title mb-4">Keterangan &amp; Petunjuk</h3>
                        <div class="content">
                            <ul class="list-unstyled mb-3">
                                <li><strong>Form ini digunakan untuk memberikan feedback, saran, pengaduan, atau apresiasi
                                        terkait proyek yang sedang berjalan.</strong></li>
                                <li>Isi data dengan benar dan lengkap agar tanggapan Anda dapat diproses dengan baik.</li>
                                <li>Jika memilih <span class="fw-bold text-danger">Pengaduan</span>, lampiran gambar <span
                                        class="fw-bold text-danger">wajib</span> diunggah.</li>
                                <li>Pastikan nomor WhatsApp aktif untuk komunikasi lebih lanjut.</li>
                                <li>Feedback Anda akan diteruskan ke Instansi terkait untuk ditindaklanjuti.</li>
                            </ul>
                            <hr>
                            <div class="mb-2">
                                <strong>Kontak Bantuan:</strong><br>
                                <span class="d-block"><i class="bi bi-envelope"></i> info@marimoi.id</span>
                                <span class="d-block"><i class="bi bi-whatsapp"></i> 0812-3456-7890</span>
                            </div>
                            <div class="mb-2">
                                <strong>Privasi:</strong><br>
                                Data Anda aman dan hanya digunakan untuk keperluan tindak lanjut feedback.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Footer Section -->
    @include('frontend.partials.footer')
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form feedback functionality
            const form = document.getElementById('feedbackForm');
            const jenisTanggapan = document.getElementById('jenis_tanggapan');
            const imageRequired = document.getElementById('image_required');
            const imageNote = document.getElementById('image_note');
            const laporanGambar = document.getElementById('laporan_gambar');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const imagePreview = document.getElementById('image_preview');
            const imagePreviewContainer = document.getElementById('image_preview_container');
            const imagePlaceholder = document.getElementById('image_placeholder');
            const removeImageBtn = document.getElementById('remove_image');
            const lat = document.getElementById('latitude');
            const long = document.getElementById('longitude');

            // Handle image preview
            laporanGambar.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Check file size (2MB = 2 * 1024 * 1024 bytes)
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Ukuran file terlalu besar. Maksimal 2MB.');
                        this.value = '';
                        return;
                    }

                    // Check file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.');
                        this.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const mobilePreview = document.getElementById('mobileImagePreview');
                        const mobilePreviewImage = document.getElementById('mobilePreviewImage');
                        
                        // Check if mobile view
                        if (window.innerWidth <= 767) {
                            // Mobile: Show mobile preview only, hide desktop preview
                            mobilePreviewImage.src = e.target.result;
                            mobilePreview.classList.add('show');
                            imagePreviewContainer.style.display = 'none';
                            imagePlaceholder.style.display = 'none';
                        } else {
                            // Desktop: Show desktop preview only, hide mobile preview
                            imagePreview.src = e.target.result;
                            imagePreviewContainer.style.display = 'block';
                            imagePlaceholder.style.display = 'none';
                            mobilePreview.classList.remove('show');
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Handle remove image
            removeImageBtn.addEventListener('click', function() {
                laporanGambar.value = '';
                imagePreview.src = '';
                imagePreviewContainer.style.display = 'none';
                imagePlaceholder.style.display = 'flex';
                
                // Hide mobile preview
                const mobilePreview = document.getElementById('mobileImagePreview');
                const mobilePreviewImage = document.getElementById('mobilePreviewImage');
                mobilePreviewImage.src = '';
                mobilePreview.classList.remove('show');
            });

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
                
                // Get user's current location
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            // Set coordinates in hidden fields
                            lat.value = position.coords.latitude;
                            long.value = position.coords.longitude;
                            
                            console.log(lat, long);
                            // Submit form
                            submitForm();
                        },
                        function(error) {
                            console.error('Error getting location:', error);
                            // Submit form without coordinates if location is not available
                            submitForm();
                        }
                    );
                } else {
                    // Submit form without coordinates if geolocation is not supported
                    submitForm();
                }
            });
            
            // Fungsi submit form
            function submitForm() {
                submitBtn.disabled = true;
                submitText.textContent = 'Mengirim...';

                const formData = new FormData(form);
                console.log('form', formData);

                fetch(form.action, {
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
                            // Reset image preview
                            imagePreview.src = '';
                            imagePreviewContainer.style.display = 'none';
                            imagePlaceholder.style.display = 'flex';
                            
                            // Reset mobile preview
                            const mobilePreview = document.getElementById('mobileImagePreview');
                            const mobilePreviewImage = document.getElementById('mobilePreviewImage');
                            mobilePreviewImage.src = '';
                            mobilePreview.classList.remove('show');
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
            }

            // Fungsi tampil alert
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

            // Map functionality
            @if (isset($project) && isset($project->geojson))
                // Initialize the map
                var map = L.map('map-detail').setView([0, 0], 13);

                // Add OpenStreetMap tile layer
                L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        id: "esri-world-imagery",
                        label: "ESRI World Imagery",
                        minZoom: 7,
                        maxZoom: 18,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);

                // Parse geometry JSON
                var geometry = {!! json_encode($project->geojson) !!};

                var layer;

                if (geometry.type === 'Point') {
                    var coords = [geometry.coordinates[1], geometry.coordinates[0]];
                    layer = L.marker(coords).addTo(map);
                    map.setView(coords, 16);
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
                // if (layer) {
                //     layer.bindPopup("{{ $project->kategori->nama ?? 'Feature' }}").openPopup();
                // }
            @endif
        });
    </script>
@endpush
