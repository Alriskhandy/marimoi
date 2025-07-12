@extends('backend.partials.main')

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-map-marker"></i>
            </span> Edit Lokasi
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('lokasi.index') }}">Lokasi</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span></span>Edit
                </li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Form Edit Lokasi</h4>
                    <p class="card-description">Edit informasi lokasi dan atribut DBF</p>

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> Terdapat kesalahan dalam pengisian form:
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('lokasi.update', $lokasi->id) }}" method="POST" id="lokasiForm">
                        @csrf
                        @method('PUT')

                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="kategori">Kategori <span class="text-danger">*</span></label>
                                    <select class="form-control @error('kategori') is-invalid @enderror" id="kategori"
                                        name="kategori" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        @foreach ($kategoriLayers as $kategori)
                                            <option value="{{ $kategori->id }}"
                                                {{ old('kategori', $lokasi->kategori_id) == $kategori->id ? 'selected' : '' }}>
                                                {{ $kategori->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('kategori')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="deskripsi">Deskripsi</label>
                                    <input type="text" class="form-control @error('deskripsi') is-invalid @enderror"
                                        id="deskripsi" name="deskripsi" value="{{ old('deskripsi', $lokasi->deskripsi) }}"
                                        placeholder="Deskripsi lokasi">
                                    @error('deskripsi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- DBF Attributes Section -->
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-table"></i> Atribut DBF
                                </h5>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-info" id="toggleAttributesView">
                                        <i class="mdi mdi-code-json"></i> Toggle JSON View
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addNewAttribute">
                                        <i class="mdi mdi-plus"></i> Tambah Atribut
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Form View -->
                                <div id="attributesFormView">
                                    <div class="row" id="attributesContainer">
                                        <!-- Attributes will be loaded here -->
                                    </div>
                                </div>

                                <!-- JSON View -->
                                <div id="attributesJsonView" style="display: none;">
                                    <div class="form-group">
                                        <label for="dbf_attributes_json">DBF Attributes JSON</label>
                                        <textarea class="form-control" id="dbf_attributes_json" rows="15" style="font-family: monospace;">{{ json_encode($lokasi->dbf_attributes, JSON_PRETTY_PRINT) }}</textarea>
                                        <small class="form-text text-muted">
                                            Edit JSON langsung. Pastikan format JSON valid.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Geometry Section -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-vector-polygon"></i> Data Geometri
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="geom">Geometri (WKT Format)</label>
                                    <textarea class="form-control @error('geom') is-invalid @enderror" id="geom" name="geom" rows="6"
                                        style="font-family: monospace;" placeholder="POLYGON((lng lat, lng lat, ...))" required>{{ old('geom', $lokasi->geom) }}</textarea>
                                    @error('geom')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Format: Well-Known Text (WKT). Contoh: POLYGON((127.123 0.789, 127.124 0.790, ...))
                                    </small>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-sm btn-outline-info" id="validateGeometry">
                                            <i class="mdi mdi-check-circle"></i> Validasi Geometri
                                        </button>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="showOnMap">
                                            <i class="mdi mdi-map"></i> Lihat di Peta
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden input untuk dbf_attributes -->
                        <input type="hidden" name="dbf_attributes" id="dbf_attributes_hidden">

                        <!-- Submit Buttons -->
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-gradient-primary me-2">
                                <i class="mdi mdi-content-save"></i> Update Lokasi
                            </button>
                            <a href="{{ route('lokasi.index') }}" class="btn btn-light">
                                <i class="mdi mdi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Preview Modal -->
    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mapModalLabel">
                        <i class="mdi mdi-map"></i> Preview Lokasi di Peta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="map" style="height: 500px; width: 100%;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Attribute Modal -->
    <div class="modal fade" id="addAttributeModal" tabindex="-1" aria-labelledby="addAttributeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAttributeModalLabel">
                        <i class="mdi mdi-plus"></i> Tambah Atribut Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="new_attribute_key">Nama Atribut</label>
                        <input type="text" class="form-control" id="new_attribute_key"
                            placeholder="Contoh: NAMA_OBJEK">
                    </div>
                    <div class="form-group">
                        <label for="new_attribute_value">Nilai</label>
                        <input type="text" class="form-control" id="new_attribute_value"
                            placeholder="Masukkan nilai">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="saveNewAttribute">
                        <i class="mdi mdi-content-save"></i> Tambah
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

    <script>
        $(document).ready(function() {
            let currentView = 'form';

            // Parse existing DBF attributes
            const dbfAttributes = @json($lokasi->dbf_attributes ?? []);

            // Initialize attributes form
            function initAttributesForm() {
                const container = $('#attributesContainer');
                container.empty();

                if (Object.keys(dbfAttributes).length === 0) {
                    container.append(`
                <div class="col-12">
                    <div class="text-center py-4">
                        <i class="mdi mdi-table mdi-48px text-muted"></i>
                        <p class="text-muted mt-2">Belum ada atribut DBF. Klik "Tambah Atribut" untuk menambahkan.</p>
                    </div>
                </div>
            `);
                    return;
                }

                let index = 0;
                Object.keys(dbfAttributes).forEach(function(key) {
                    if (index % 2 === 0 && index > 0) {
                        container.append('<div class="w-100"></div>'); // Force new row every 2 items
                    }

                    const value = dbfAttributes[key];
                    const colDiv = $(`
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="attr_${key}" class="d-flex justify-content-between">
                            <span>${key}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-attribute" data-key="${key}">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </label>
                        <input type="text" 
                               class="form-control attribute-input" 
                               id="attr_${key}" 
                               name="attr_${key}"
                               data-key="${key}"
                               value="${value || ''}" 
                               placeholder="Masukkan ${key}">
                    </div>
                </div>
            `);
                    container.append(colDiv);
                    index++;
                });
            }

            // Add new attribute
            $('#addNewAttribute').on('click', function() {
                $('#addAttributeModal').modal('show');
                $('#new_attribute_key').val('');
                $('#new_attribute_value').val('');
            });

            // Save new attribute
            $('#saveNewAttribute').on('click', function() {
                const key = $('#new_attribute_key').val().trim();
                const value = $('#new_attribute_value').val().trim();

                if (!key) {
                    alert('Nama atribut tidak boleh kosong!');
                    return;
                }

                if (dbfAttributes.hasOwnProperty(key)) {
                    alert('Atribut dengan nama tersebut sudah ada!');
                    return;
                }

                // Add to attributes object
                dbfAttributes[key] = value;

                // Reinitialize form
                initAttributesForm();

                // Close modal
                $('#addAttributeModal').modal('hide');
            });

            // Remove attribute
            $(document).on('click', '.remove-attribute', function() {
                const key = $(this).data('key');

                if (confirm(`Apakah Anda yakin ingin menghapus atribut "${key}"?`)) {
                    delete dbfAttributes[key];
                    initAttributesForm();
                }
            });

            // Toggle between form and JSON view
            $('#toggleAttributesView').on('click', function() {
                if (currentView === 'form') {
                    // Switch to JSON view
                    updateJsonFromForm();
                    $('#attributesFormView').hide();
                    $('#attributesJsonView').show();
                    $(this).html('<i class="mdi mdi-form-select"></i> Toggle Form View');
                    currentView = 'json';
                } else {
                    // Switch to form view
                    try {
                        updateFormFromJson();
                        $('#attributesJsonView').hide();
                        $('#attributesFormView').show();
                        $(this).html('<i class="mdi mdi-code-json"></i> Toggle JSON View');
                        currentView = 'form';
                    } catch (e) {
                        alert('JSON tidak valid! Perbaiki format JSON terlebih dahulu.');
                    }
                }
            });

            // Update JSON from form inputs
            function updateJsonFromForm() {
                const updatedAttributes = {};
                $('.attribute-input').each(function() {
                    const key = $(this).data('key');
                    const value = $(this).val();
                    updatedAttributes[key] = value;
                });

                // Update global attributes
                Object.assign(dbfAttributes, updatedAttributes);

                $('#dbf_attributes_json').val(JSON.stringify(dbfAttributes, null, 2));
            }

            // Update form from JSON
            function updateFormFromJson() {
                const jsonText = $('#dbf_attributes_json').val();
                const parsedAttributes = JSON.parse(jsonText);

                // Update global attributes
                Object.keys(dbfAttributes).forEach(key => delete dbfAttributes[key]);
                Object.assign(dbfAttributes, parsedAttributes);

                // Reinitialize form
                initAttributesForm();
            }

            // Validate geometry
            $('#validateGeometry').on('click', function() {
                const geometry = $('#geom').val().trim();

                if (!geometry) {
                    alert('Geometri tidak boleh kosong!');
                    return;
                }

                // Basic validation for WKT format
                const wktPatterns = [
                    /^POINT\s*\(/i,
                    /^LINESTRING\s*\(/i,
                    /^POLYGON\s*\(/i,
                    /^MULTIPOINT\s*\(/i,
                    /^MULTILINESTRING\s*\(/i,
                    /^MULTIPOLYGON\s*\(/i
                ];

                const isValid = wktPatterns.some(pattern => pattern.test(geometry));

                if (isValid) {
                    $(this).removeClass('btn-outline-info').addClass('btn-outline-success');
                    $(this).html('<i class="mdi mdi-check-circle"></i> Geometri Valid');
                    setTimeout(() => {
                        $(this).removeClass('btn-outline-success').addClass('btn-outline-info');
                        $(this).html('<i class="mdi mdi-check-circle"></i> Validasi Geometri');
                    }, 2000);
                } else {
                    alert('Format geometri tidak valid! Gunakan format WKT yang benar.');
                }
            });

            // Show on map
            $('#showOnMap').on('click', function() {
                $('#mapModal').modal('show');

                setTimeout(function() {
                    // Initialize map centered on North Maluku (Ternate area)
                    const map = L.map('map').setView([0.7893, 127.3776], 10);

                    // Add tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);

                    // Try to parse and display geometry
                    const geometry = $('#geom').val().trim();
                    if (geometry && geometry.startsWith('POLYGON')) {
                        try {
                            // Simple parsing for polygon
                            const coords = geometry.match(/POLYGON\s*\(\s*\((.*?)\)\s*\)/i);
                            if (coords && coords[1]) {
                                const points = coords[1].split(',').map(point => {
                                    const [lng, lat] = point.trim().split(/\s+/).map(
                                        Number);
                                    return [lat, lng]; // Leaflet uses [lat, lng]
                                });

                                const polygon = L.polygon(points, {
                                    color: 'red',
                                    fillColor: '#f03',
                                    fillOpacity: 0.5
                                }).addTo(map);

                                // Fit map to polygon bounds
                                map.fitBounds(polygon.getBounds());

                                // Add popup with location info
                                const namobj = dbfAttributes.NAMOBJ || dbfAttributes.nama ||
                                    'Lokasi';
                                polygon.bindPopup(`<strong>${namobj}</strong>`).openPopup();
                            }
                        } catch (e) {
                            console.error('Error parsing geometry:', e);
                        }
                    }
                }, 500);
            });

            // Form submission
            $('#lokasiForm').on('submit', function(e) {
                // Update hidden dbf_attributes input based on current view
                if (currentView === 'form') {
                    updateJsonFromForm();
                    $('#dbf_attributes_hidden').val($('#dbf_attributes_json').val());
                } else {
                    $('#dbf_attributes_hidden').val($('#dbf_attributes_json').val());
                }
            });

            // Initialize
            initAttributesForm();
        });
    </script>
@endsection
