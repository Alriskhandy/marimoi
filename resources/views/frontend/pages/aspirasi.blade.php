@extends('frontend.layouts.app', ['title' => 'Usulan Aspirasi'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/detail.css') }}">
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

                                    <div class="col-md-6" id="kategoriUsulanContainer" style="display: none;">
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
                                        <div id="map"
                                            style="height: 300px; border: 1px solid #ddd; border-radius: 5px;"></div>
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

                if (selectedValue === 'usulan') {
                    // Show kategori usulan and map container
                    kategoriUsulanContainer.style.display = 'block';
                    mapContainer.style.display = 'block';

                    // Initialize map if not already initialized
                    initMap();
                } else {
                    // Hide kategori usulan and map container
                    kategoriUsulanContainer.style.display = 'none';
                    mapContainer.style.display = 'none';
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

                // Validate form before submission
                const jenisAspirasi = jenisAspirasiSelect.value;
                const latitude = document.getElementById('latitude').value;
                const longitude = document.getElementById('longitude').value;

                if (jenisAspirasi === 'usulan' && (!latitude || !longitude)) {
                    showAlert('error', 'Silakan pilih lokasi pada peta untuk usulan pembangunan.');
                    return false;
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
                // Use hCaptcha API to get the response value
                const hcaptchaResponse = hcaptcha.getResponse();
                if (hcaptchaResponse) {
                    formData.set('h-captcha-response', hcaptchaResponse);
                }

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
                            if (typeof hcaptcha !== 'undefined') {
                                hcaptcha.reset();
                            }
                        } else {
                            if (data.errors) {
                                if (data.errors['h-captcha-response']) {
                                    showAlert('error', data.errors['h-captcha-response'][0]);
                                    // Reset hCaptcha on error
                                    if (typeof hcaptcha !== 'undefined') {
                                        hcaptcha.reset();
                                    }
                                } else {
                                    showAlert('error', data.message);
                                }
                            } else {
                                showAlert('error', data.message);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('error', 'Terjadi kesalahan saat mengirim data.');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitText.textContent = originalText;
                    });
            }

            // Fungsi tampil alert
            function showAlert(type, message) {
                // Remove existing alerts first
                const existingAlerts = document.querySelectorAll('.map-alert-overlay');
                existingAlerts.forEach(alert => alert.remove());
                
                const alertContainer = document.createElement('div');
                alertContainer.className = 'map-alert-overlay';
                
                // Create overlay background for loading
                if (type === 'info') {
                    alertContainer.style.position = 'absolute';
                    alertContainer.style.top = '0';
                    alertContainer.style.left = '0';
                    alertContainer.style.width = '100%';
                    alertContainer.style.height = '100%';
                    alertContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.3)';
                    alertContainer.style.display = 'flex';
                    alertContainer.style.alignItems = 'center';
                    alertContainer.style.justifyContent = 'center';
                    alertContainer.style.zIndex = '9999';
                    
                    // Create content container
                    const contentContainer = document.createElement('div');
                    contentContainer.style.display = 'flex';
                    contentContainer.style.alignItems = 'center';
                    contentContainer.style.gap = '10px';
                    contentContainer.style.color = 'white';
                    contentContainer.style.fontSize = window.innerWidth <= 768 ? '14px' : '16px';
                    contentContainer.style.fontWeight = 'bold';
                    
                    // Create spinner
                    const spinner = document.createElement('div');
                    spinner.style.width = '24px';
                    spinner.style.height = '24px';
                    spinner.style.border = '3px solid rgba(255, 255, 255, 0.3)';
                    spinner.style.borderTop = '3px solid white';
                    spinner.style.borderRadius = '50%';
                    spinner.style.animation = 'spin 1s linear infinite';
                    
                    // Add CSS for spinner animation if not exists
                    if (!document.getElementById('spinner-style')) {
                        const style = document.createElement('style');
                        style.id = 'spinner-style';
                        style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); }}';
                        document.head.appendChild(style);
                    }
                    
                    // Create text element
                    const textElement = document.createElement('span');
                    textElement.textContent = 'Loading...';
                    
                    contentContainer.appendChild(spinner);
                    contentContainer.appendChild(textElement);
                    alertContainer.appendChild(contentContainer);
                } else {
                    // For error messages, show simple centered text without background
                    alertContainer.style.position = 'absolute';
                    alertContainer.style.top = '50%';
                    alertContainer.style.left = '50%';
                    alertContainer.style.transform = 'translate(-50%, -50%)';
                    alertContainer.style.zIndex = '9999';
                    alertContainer.style.color = 'white';
                    alertContainer.style.fontSize = window.innerWidth <= 768 ? '14px' : '16px';
                    alertContainer.style.fontWeight = 'bold';
                    alertContainer.style.textAlign = 'center';
                    alertContainer.style.padding = '15px';
                    alertContainer.style.backgroundColor = 'transparent';
                    alertContainer.style.maxWidth = '80%';
                    alertContainer.style.wordWrap = 'break-word';
                    alertContainer.style.textShadow = '2px 2px 4px rgba(0,0,0,0.8)';
                    
                    // Simplified error message
                    if (type === 'error') {
                        alertContainer.textContent = 'Gagal';
                    } else {
                        alertContainer.textContent = message;
                    }
                }

                // Append to map container
                const mapElement = document.getElementById('map');
                if (mapElement) {
                    mapElement.appendChild(alertContainer);
                } else {
                    // Fallback to body if map container not found
                    document.body.appendChild(alertContainer);
                }

                // Auto hide after appropriate time
                const hideTime = type === 'info' ? 10000 : 3000; // Loading stays longer
                setTimeout(() => {
                    if (alertContainer.parentNode) {
                        alertContainer.remove();
                    }
                }, hideTime);
            }

            // Function to initialize the map
            function initMap() {
                if (map) return; // If map already initialized, return

                // Set default center (Maluku Utara coordinates)
                const defaultCenter = [0.735485, 128.028201];

                // Create map
                map = L.map('map').setView(defaultCenter, 7);

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
