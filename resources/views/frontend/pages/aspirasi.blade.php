@extends('frontend.layouts.app', ['title' => 'Usulan Aspirasi'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/aspirasi.css') }}">
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
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
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map variables
            let map = null;
            let marker = null;

            // Form aspirasi functionality
            const form = document.getElementById('formUsulan');
            const jenisAspirasiSelect = document.getElementById('jenis_aspirasi');
            const kategoriUsulanContainer = document.getElementById('kategoriUsulanContainer');
            const mapContainer = document.getElementById('mapContainer');
            const getLocationBtn = document.getElementById('getLocationBtn');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');

            // Event listener for jenis aspirasi selection
            jenisAspirasiSelect.addEventListener('change', function() {
                const selectedValue = this.value;
                const kategoriSelect = document.getElementById('kategori_aspirasi_id');

                if (selectedValue === 'usulan') {
                    // Show kategori usulan and map container
                    kategoriUsulanContainer.style.display = 'block';
                    mapContainer.style.display = 'block';

                    // Make kategori required
                    kategoriSelect.setAttribute('required', 'required');

                    // Initialize map if not already initialized
                    initMap();
                } else {
                    // Hide kategori usulan and map container
                    kategoriUsulanContainer.style.display = 'none';
                    mapContainer.style.display = 'none';

                    // Remove required attribute and reset value
                    kategoriSelect.removeAttribute('required');
                    kategoriSelect.value = '';
                }
            });

            // Event listener for get location button
            getLocationBtn.addEventListener('click', function() {
                // Show immediate feedback
                showAlert('info', 'Loading...');
                getCurrentLocation();
            });

            // Handle form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Check if form is valid first
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return false;
                }

                // Validate form before submission
                const jenisAspirasi = jenisAspirasiSelect.value;
                const latitude = document.getElementById('latitude').value;
                const longitude = document.getElementById('longitude').value;
                const kategoriAspirasi = document.getElementById('kategori_aspirasi_id').value;

                if (jenisAspirasi === 'usulan') {
                    if (!kategoriAspirasi) {
                        showAlert('error', 'Pilih kategori usulan terlebih dahulu.');
                        return false;
                    }
                    if (!latitude || !longitude) {
                        showAlert('error', 'Pilih lokasi pada peta terlebih dahulu.');
                        return false;
                    }
                }

                // Get user's current location for non-usulan types
                if (jenisAspirasi !== 'usulan') {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                // Set coordinates in hidden fields
                                document.getElementById('latitude').value = position.coords.latitude;
                                document.getElementById('longitude').value = position.coords.longitude;

                                // Submit form
                                submitForm();
                            },
                            function(error) {
                                console.error('Error getting location:', error);
                                // Submit form without coordinates if location is not available
                                submitForm();
                            }, {
                                timeout: 5000,
                                maximumAge: 60000
                            }
                        );
                    } else {
                        // Submit form without coordinates if geolocation is not supported
                        submitForm();
                    }
                } else {
                    // For usulan, coordinates are already set by map interaction
                    submitForm();
                }
            });

            // Fungsi submit form
            function submitForm() {
                submitBtn.disabled = true;
                const originalText = submitText.textContent;
                submitText.textContent = 'Mengirim...';

                const formData = new FormData(form);

                // Ensure hCaptcha response is included in form data
                try {
                    if (typeof hcaptcha !== 'undefined') {
                        const hcaptchaResponse = hcaptcha.getResponse();
                        if (hcaptchaResponse) {
                            formData.set('h-captcha-response', hcaptchaResponse);
                        }
                    }
                } catch (error) {
                    console.warn('hCaptcha not available:', error);
                }

                fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(response => {
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            showAlert('success', data.message);
                            form.reset();
                            // Reset kategori usulan and map container visibility
                            kategoriUsulanContainer.style.display = 'none';
                            mapContainer.style.display = 'none';
                            // Reset map
                            if (typeof map !== 'undefined' && map) {
                                map.remove();
                                map = null;
                                marker = null;
                            }
                            // Reset hidden coordinates
                            document.getElementById('latitude').value = '';
                            document.getElementById('longitude').value = '';
                            // Reset hCaptcha
                            try {
                                if (typeof hcaptcha !== 'undefined') {
                                    hcaptcha.reset();
                                }
                            } catch (error) {
                                console.warn('Error resetting hCaptcha:', error);
                            }
                        } else {
                            if (data.errors) {
                                if (data.errors['h-captcha-response']) {
                                    showAlert('error', data.errors['h-captcha-response'][0]);
                                    // Reset hCaptcha on error
                                    try {
                                        if (typeof hcaptcha !== 'undefined') {
                                            hcaptcha.reset();
                                        }
                                    } catch (error) {
                                        console.warn('Error resetting hCaptcha:', error);
                                    }
                                } else {
                                    showAlert('error', data.message || 'Terjadi kesalahan validasi');
                                }
                            } else {
                                showAlert('error', data.message || 'Terjadi kesalahan');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        showAlert('error', 'Terjadi kesalahan saat mengirim data.');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitText.textContent = originalText;
                    });
            }

            // CSS moved to aspirasi.css

            // Fungsi tampil alert
            function showAlert(type, message) {
                const alertContainer = document.getElementById('alertContainer');
                const alertMessage = document.getElementById('alertMessage');

                // For loading/info alerts in map
                if (type === 'info' && document.getElementById('map')) {
                    // Remove existing map alerts first
                    const existingAlerts = document.querySelectorAll('.map-alert-overlay');
                    existingAlerts.forEach(alert => alert.remove());

                    const mapAlertContainer = document.createElement('div');
                    mapAlertContainer.className = 'map-alert-overlay';
                    mapAlertContainer.style.position = 'absolute';
                    mapAlertContainer.style.top = '0';
                    mapAlertContainer.style.left = '0';
                    mapAlertContainer.style.width = '100%';
                    mapAlertContainer.style.height = '100%';
                    mapAlertContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.3)';
                    mapAlertContainer.style.display = 'flex';
                    mapAlertContainer.style.alignItems = 'center';
                    mapAlertContainer.style.justifyContent = 'center';
                    mapAlertContainer.style.zIndex = '99'; // Set below navbar z-index (997)

                    // Create content container with spinner
                    const contentContainer = document.createElement('div');
                    contentContainer.style.display = 'flex';
                    contentContainer.style.alignItems = 'center';
                    contentContainer.style.gap = '10px';
                    contentContainer.style.color = 'white';
                    contentContainer.style.fontSize = window.innerWidth <= 768 ? '14px' : '16px';
                    contentContainer.style.fontWeight = 'bold';
                    contentContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';
                    contentContainer.style.padding = '10px 16px';
                    contentContainer.style.borderRadius = '8px';
                    contentContainer.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
                    contentContainer.style.transform = 'scale(0.9)';
                    contentContainer.style.transition = 'transform 0.3s ease';

                    // Create spinner
                    const spinner = document.createElement('div');
                    spinner.style.width = '24px';
                    spinner.style.height = '24px';
                    spinner.style.border = '3px solid rgba(255, 255, 255, 0.3)';
                    spinner.style.borderTop = '3px solid white';
                    spinner.style.borderRadius = '50%';
                    spinner.style.animation = 'spin 1s linear infinite';

                    // Spinner animation styles moved to aspirasi.css

                    // Create text element
                    const textElement = document.createElement('span');
                    textElement.textContent = message || 'Loading...';

                    contentContainer.appendChild(spinner);
                    contentContainer.appendChild(textElement);
                    mapAlertContainer.appendChild(contentContainer);

                    // Append to map container
                    const mapElement = document.getElementById('map');
                    mapElement.appendChild(mapAlertContainer);

                    // Trigger animations with a small delay
                    setTimeout(() => {
                        mapAlertContainer.classList.add('show');
                        contentContainer.style.transform = 'scale(1)';
                    }, 10);

                    // Auto hide after appropriate time
                    setTimeout(() => {
                        mapAlertContainer.classList.remove('show');
                        contentContainer.style.transform = 'scale(0.9)';
                        
                        // Remove after transition completes
                        setTimeout(() => {
                            if (mapAlertContainer.parentNode) {
                                mapAlertContainer.remove();
                            }
                        }, 300);
                    }, 1000); // Loading stays longer
                } else {
                    // For success and error messages, use the standard alert container
                    alertMessage.className = `alert alert-${type === 'success' ? 'success' : 'danger'}`;
                    alertMessage.innerHTML = type === 'success' ?
                        `<i class="bi bi-check-circle me-2"></i>${message}` :
                        `<i class="bi bi-exclamation-triangle me-2"></i>${message}`;
                    
                    // Remove any existing classes and add show class
                    alertContainer.classList.remove('show');
                    
                    // Force reflow to ensure animation plays
                    void alertContainer.offsetWidth;
                    
                    // Show the alert with animation
                    alertContainer.classList.add('show');

                    // Auto hide after 3 seconds
                    setTimeout(() => {
                        alertContainer.classList.remove('show');
                    }, 3000);
                }
            }

            // Function to initialize the map
            function initMap() {
                if (map) return; // If map already initialized, return

                // Set default center (Maluku Utara coordinates)
                const defaultCenter = [0.735485, 128.028201];

                // Create map
                map = L.map('map').setView(defaultCenter, 8);

                // Add tile layer (Google Hybrid)
                L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                    subdomains: ["mt0", "mt1", "mt2", "mt3"],
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                }).addTo(map);

                // Add click event to map
                map.on('click', function(e) {
                    setMarker(e.latlng);
                });
            }

            // Function to set marker on map
            // setMarker: pastikan hanya 1 marker
            function setMarker(latlng) {
                if (!window.currentLocationMarker) {
                    window.currentLocationMarker = L.marker(latlng).addTo(map);
                } else {
                    window.currentLocationMarker.setLatLng(latlng);
                }

                // Update hidden form fields with coordinates
                document.getElementById('latitude').value = latlng.lat;
                document.getElementById('longitude').value = latlng.lng;
            }

            function getCurrentLocation() {
                if (!navigator.geolocation) {
                    showAlert('error', 'Geolocation tidak didukung oleh browser ini.');
                    return;
                }

                // Optimized options for faster response
                const options = {
                    enableHighAccuracy: false, // Faster response, slightly less accurate
                    timeout: 5000, // Reduced timeout for faster failure
                    maximumAge: 30000 // Accept cached position up to 30 seconds old
                };

                // Show progress indicator
                showAlert('info', 'Loading...');

                // Single attempt with faster timeout
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;

                        // Simple validation
                        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                            showAlert('error', 'Lokasi tidak valid.');
                            return;
                        }

                        // Show success with accuracy info
                        if (accuracy && accuracy > 100) {
                            showAlert('warning',
                                `Akurasi ~${Math.round(accuracy)} m. Pastikan GPS aktif.`
                            );
                        } else {
                            showAlert('success', 'Lokasi berhasil ditemukan.');
                        }

                        // Set marker and update map
                        const latlng = L.latLng(lat, lng);
                        setMarker(latlng);
                        map.setView(latlng, 15);

                        // Update form fields
                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                        showAlert('error', 'Gagal');
                    },
                    options
                );
            }
        });
    </script>
@endpush
