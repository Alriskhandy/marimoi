    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header bg-primary text-white rounded-top-3 py-2">
                    <h6 class="modal-title fw-semibold" id="detailModalLabel">
                        <i class="fa fa-map-marker" style="margin-right: 6px;"></i>Detail Data Spasial
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" id="detailModalBody">
                    <div class="d-flex justify-content-center align-items-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-3 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times" style="margin-right: 5px;"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
    <script>
        // Show details function with improved UX
        function showDetails(id) {
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            const modalBody = document.getElementById('detailModalBody');

            // Show loading with better animation
            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h6 class="text-muted">Memuat detail data...</h6>
                    <p class="text-muted small">Mohon tunggu sebentar</p>
                </div>
            `;

            modal.show();

            // Fetch data details with improved error handling
            fetch(`/dashboard/data-spatial/${id}/details`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        modalBody.innerHTML = `
                            <div class="container-fluid px-0">
                                <div class="card border-0 shadow-sm rounded-4 bg-light">
                                    <div class="card-header bg-primary text-white rounded-top-4 py-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="mdi mdi-information-outline me-2"></i>Informasi Dasar
                                        </h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Data Type:</small>
                                                <div class="fw-medium">${data.data.data_type || '-'}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Sub Type:</small>
                                                <div class="fw-medium">${data.data.sub_type || '-'}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Tahun:</small>
                                                <div class="fw-medium">${data.data.tahun || '-'}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted fw-semibold">Kategori:</small>
                                                <div class="fw-medium">${data.data.kategori?.nama || '-'}</div>
                                            </div>
                                            <div class="col-12">
                                                <small class="text-muted fw-semibold">Deskripsi:</small>
                                                <div class="fw-medium">${data.data.deskripsi || '-'}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm rounded-4 bg-light mt-3">
                                    <div class="card-header bg-secondary text-white rounded-top-4 py-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="mdi mdi-shield-check-outline me-2"></i>Metadata Dataset
                                        </h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <small class="text-muted fw-semibold">Sumber Data:</small>
                                                <div class="fw-medium">${data.data.sumber_data || 'Belum diisi'}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted fw-semibold">Instansi Pengelola:</small>
                                                <div class="fw-medium">${data.data.opd_pengelola?.name || 'Belum diisi'}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted fw-semibold">Tanggal Data:</small>
                                                <div class="fw-medium">${data.data.tanggal_data || 'Belum diisi'}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm rounded-4 bg-light mt-3">
                                    <div class="card-header bg-info text-white rounded-top-4 py-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="mdi mdi-database me-2"></i>Atribut DBF
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div style="max-height: 300px; overflow-y: auto;">
                                            ${data.data.dbf_attributes ? 
                                                '<div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead class="table-dark sticky-top"><tr><th class="fw-semibold">Atribut</th><th class="fw-semibold">Nilai</th></tr></thead><tbody>'+
                                                Object.entries(data.data.dbf_attributes).map(([key,value])=>`<tr><td class="fw-medium text-primary">${key}</td><td>${value || '-'}</td></tr>`).join('')+
                                                '</tbody></table></div>' : 
                                                '<div class="text-center py-4"><i class="mdi mdi-database-remove text-muted" style="font-size: 3rem;"></i><p class="text-muted mb-0 mt-2">Tidak ada atribut DBF tersedia</p></div>'
                                            }
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div class="text-center py-5">
                            <i class="mdi mdi-alert-circle text-danger" style="font-size: 4rem;"></i>
                            <h5 class="text-danger mt-3">Terjadi Kesalahan</h5>
                            <p class="text-muted">
                                ${error.message || 'Gagal memuat detail data. Silakan coba lagi.'}
                            </p>
                            <button class="btn btn-outline-primary btn-sm" onclick="showDetails('${id}')">
                                <i class="mdi mdi-refresh me-1"></i>Coba Lagi
                            </button>
                        </div>
                    `;
                });
        }
    </script>
@endpush
