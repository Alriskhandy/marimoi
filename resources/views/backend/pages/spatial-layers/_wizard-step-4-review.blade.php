@php
    $categoryLabel = $layer->categoryNode?->name ?? '-';
    $geometryIcon = match ($layer->layerType?->geometry_type) {
        'MULTIPOINT' => 'mdi-map-marker',
        'MULTILINESTRING' => 'mdi-vector-polyline',
        'MULTIPOLYGON' => 'mdi-vector-square',
        default => 'mdi-layers',
    };
    $existingThumbnail = $layer->defaultStyle?->definition['thumbnail'] ?? null;
@endphp

<h5 class="mb-1"><i class="mdi mdi-check-decagram-outline me-2"></i>Review & Publish</h5>
<p class="text-muted small mb-4">Periksa kembali Layer sebelum menyelesaikan wizard.</p>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-semibold mb-2">Gambar Layer</label>
        <div class="thumbnail-preview-box" id="thumbnailPreviewBox"
            style="background-color: {{ $layer->color }};">
            @if ($existingThumbnail)
                <img src="{{ asset('storage/'.$existingThumbnail) }}" alt="Gambar Layer" id="thumbnailPreviewImg">
            @else
                <i class="mdi {{ $geometryIcon }}" id="thumbnailPreviewIcon"></i>
            @endif
        </div>

        <div class="form-check mt-3">
            <input class="form-check-input" type="radio" name="thumbnail_choice" id="thumbnail_default"
                value="default" checked>
            <label class="form-check-label small" for="thumbnail_default">
                Gunakan gambar default (digenerate otomatis)
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="thumbnail_choice" id="thumbnail_upload"
                value="upload">
            <label class="form-check-label small" for="thumbnail_upload">
                Unggah gambar sendiri
            </label>
        </div>

        <div id="thumbnailUploadField" class="mt-2" style="display: none;">
            <input type="file" class="form-control form-control-sm" name="thumbnail" id="thumbnailFileInput"
                accept="image/*" form="wizardFinishForm">
            @error('thumbnail')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-8">
        <table class="table table-sm">
            <tbody>
                <tr>
                    <th style="width: 180px;">Nama</th>
                    <td>{{ $layer->name }}</td>
                </tr>
                <tr>
                    <th>Jenis Layer</th>
                    <td>{{ $layer->layerType?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Kategori</th>
                    <td>{{ $categoryLabel }}</td>
                </tr>
                <tr>
                    <th>OPD Pemilik</th>
                    <td>{{ $layer->opd?->name ?? 'Provinsi/Bappeda' }}</td>
                </tr>
                <tr>
                    <th>Jumlah Data Spasial</th>
                    <td>{{ $featureCount }} fitur</td>
                </tr>
                <tr>
                    <th>Sumber Data Terakhir</th>
                    <td>
                        @if ($latestImport)
                            {{ $latestImport->original_filename }}
                            <span class="badge bg-{{ $latestImport->status === 'completed' ? 'success' : 'secondary' }}">
                                {{ $latestImport->status }}
                            </span>
                        @else
                            <span class="text-muted">Belum ada data diimpor</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        @if ($featureCount === 0)
            <div class="alert alert-warning">
                <i class="mdi mdi-alert-circle me-2"></i>
                Layer ini belum punya Data Spasial. Anda tetap bisa menyelesaikan wizard sebagai draft,
                tapi publikasi butuh minimal satu Data Spasial tersimpan.
            </div>
        @endif
    </div>
</div>

<form method="POST" action="{{ route('spatial-layers.wizard.finish', $layer) }}" enctype="multipart/form-data"
    id="wizardFinishForm">
    @csrf

    <div class="mt-4 d-flex justify-content-between">
        <a href="{{ route('spatial-layers.wizard', [$layer, 'step' => 3]) }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left"></i> Kembali
        </a>
        <div>
            <button type="submit" name="publish" value="0" class="btn btn-outline-primary">
                Selesai (Simpan sebagai Draft)
            </button>
            @if ($canPublish)
                <button type="submit" name="publish" value="1" class="btn btn-gradient-success">
                    <i class="mdi mdi-check-circle"></i> Selesai & Publikasikan
                </button>
            @endif
        </div>
    </div>
</form>

@push('styles')
    <style>
        .thumbnail-preview-box {
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .thumbnail-preview-box i {
            font-size: 4.5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .thumbnail-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function() {
            const $uploadField = $('#thumbnailUploadField');
            const $previewBox = $('#thumbnailPreviewBox');
            const defaultColor = @json($layer->color);
            const defaultIconClass = @json($geometryIcon);
            const defaultHtml = $previewBox.html();

            $('input[name="thumbnail_choice"]').on('change', function() {
                const isUpload = $(this).val() === 'upload';
                $uploadField.toggle(isUpload);

                if (!isUpload) {
                    $previewBox.css('background-color', defaultColor).html(defaultHtml);
                    $('#thumbnailFileInput').val('');
                }
            });

            $('#thumbnailFileInput').on('change', function() {
                const file = this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    $previewBox.css('background-color', 'transparent')
                        .html(`<img src="${e.target.result}" alt="Preview">`);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endpush
