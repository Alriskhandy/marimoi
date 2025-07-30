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

    <!-- Form Section -->
    <section class="section py-4">
        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
            <span>Tanggapan Terhadap Pelakasanaan Kegiatan<br></span>
            <h2>Tanggapan Terhadap Pelakasanaan Kegiatan</h2>
            <p class="p-1">Anda dapat menyampaikan tanggapan, masukan ataupun informasi terkini terkait pelaksanaan
                kegiatan ini</p>
        </div><!-- End Section Title -->
        <div class="container" data-aos="fade-up" data-aos-delay="100">

            <div class="row gy-4">

                <div class="col-lg-8">
                    <h4 class="text-center text-secondary mb-3">Formulir Tanggapan</h4>
                    <form id="addForm"
                        action="{{ isset($projectType) && $projectType !== 'all' ? request()->url() . $project->id : route('project-feedbacks.store') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        @if (isset($projectType) && $projectType !== 'all')
                            <input type="hidden" name="project_type" value="{{ $projectType }}">
                        @endif

                        @php
                            $koordinat = is_string($project->geojson)
                                ? json_decode($project->geojson, true)
                                : (array) $project->geojson;
                        @endphp

                        <p class="text-danger">{{ $koordinat['coordinates'][1] ?? '' }}</p>

                        <div class="modal-body">
                            {{-- Jika ada project, maka pakai hidden --}}
                            @if (isset($project))
                                <input type="hidden" name="feedbackable_id" value="{{ $project->id }}">
                                <input type="hidden" name="nama_proyek"
                                    value="{{ $project->dbf_attributes['KEGIATAN'] ?? '' }}">
                                <input type="hidden" name="kabupaten_kota"
                                    value="{{ $project->dbf_attributes['KABUPATEN'] ?? ($project->dbf_attributes['KOTA'] ?? '') }}">
                                <input type="hidden" name="kecamatan"
                                    value="{{ $project->dbf_attributes['KECAMATAN'] ?? '' }}">
                                <input type="hidden" name="latitude"
                                    value="{{ $koordinat['coordinates'][1] ?? '' }}">
                                <input type="hidden" name="longitude"
                                    value="{{ $koordinat['coordinates'][0] ?? '' }}">
                            @else
                                {{-- Jika tidak ada project, user harus pilih/input manual --}}
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group mb-3">
                                            <label for="feedbackable_id" class="form-label">Pilih Proyek <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" id="feedbackable_id" name="feedbackable_id"
                                                required>
                                                <option value="">-- Pilih Proyek --</option>
                                                @foreach ($availableProjects ?? [] as $p)
                                                    <option value="{{ $p->id }}">{{ $p->deskripsi }}</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Nama Pemberi Aspirasi --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_nama_pemberi_aspirasi" class="form-label">Nama Pemberi Aspirasi
                                            <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="add_nama_pemberi_aspirasi"
                                            name="nama_pemberi_aspirasi" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                {{-- Nama Proyek (ditampilkan jika tidak hidden) --}}
                                @unless (isset($project))
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="add_nama_proyek" class="form-label">Nama Proyek <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="add_nama_proyek" name="nama_proyek"
                                                required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                @endunless
                            </div>

                            <div class="row">
                                @unless (isset($project))
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="add_kabupaten_kota" class="form-label">Kabupaten/Kota <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" id="add_kabupaten_kota" name="kabupaten_kota"
                                                required>
                                                <option value="">-- Pilih Kabupaten/Kota --</option>
                                                @foreach ($kabupaten_list ?? [] as $kab)
                                                    <option value="{{ $kab }}">{{ $kab }}</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="add_kecamatan" class="form-label">Kecamatan</label>
                                            <select class="form-control" id="add_kecamatan" name="kecamatan">
                                                <option value="">-- Pilih Kecamatan --</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                @endunless
                            </div>

                            {{-- Jenis Tanggapan --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_jenis_tanggapan" class="form-label">Jenis Tanggapan <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" id="add_jenis_tanggapan" name="jenis_tanggapan"
                                            required>
                                            <option value="">-- Pilih Jenis --</option>
                                            <option value="keluhan">Pengaduan</option>
                                            <option value="saran">Saran</option>
                                            <option value="apresiasi">Apresiasi</option>
                                            <option value="pertanyaan">Pertanyaan</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_laporan_gambar" class="form-label">Lampiran Gambar (wajib untuk
                                            keluhan)</label>
                                        <input type="file" class="form-control" id="add_laporan_gambar"
                                            name="laporan_gambar" accept="image/*">
                                        <small class="text-muted">Maksimal 2MB. JPG/PNG/PDF</small>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Email dan Telepon --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="add_email" name="email">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="add_phone" class="form-label">No. WhatsApp</label>
                                        <input type="text" class="form-control" id="add_phone" name="phone"
                                            pattern="^\+?\d{10,15}$" title="Masukkan nomor WhatsApp yang valid">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Latitude & Longitude jika manual --}}
                            @unless (isset($project))
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="add_latitude" class="form-label">Latitude</label>
                                            <input type="number" step="any" class="form-control" id="add_latitude"
                                                name="latitude" placeholder="0.7881">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="add_longitude" class="form-label">Longitude</label>
                                            <input type="number" step="any" class="form-control" id="add_longitude"
                                                name="longitude" placeholder="127.3781">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            @endunless

                            {{-- Tanggapan --}}
                            <div class="form-group mb-3">
                                <label for="add_tanggapan" class="form-label">Tanggapan <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="add_tanggapan" name="tanggapan" rows="4" required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>

                            {{-- Captcha --}}
                            {{-- <div class="form-group mb-3">
                                <label for="captcha" class="form-label">Verifikasi Gambar (Captcha)</label>
                                <div class="mb-2">
                                    <img src="{{ asset('storage/captcha.png') }}" alt="Captcha" class="img-fluid"
                                        style="max-width: 250px;">
                                </div>
                                <input type="text" name="captcha" id="captcha" class="form-control"
                                    placeholder="Masukkan teks pada gambar" required>
                            </div> --}}
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary me-3" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-success">
                                <i class="mdi mdi-content-save"></i> Kirim
                            </button>
                        </div>
                    </form>
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
