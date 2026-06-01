@extends('backend.partials.main', ['title' => 'Edit Fitur'])

@push('styles')
<style>
    .layer-badge { display: inline-flex; align-items: center; gap: 6px; padding: .35rem .9rem; border-radius: 20px; font-weight: 600; font-size: .85rem; }
    .layer-badge.tematik    { background: #d1ecf1; color: #0c5460; }
    .layer-badge.psd        { background: #d4edda; color: #155724; }
    .layer-badge.psn        { background: #cce5ff; color: #004085; }
    .layer-badge.pokir      { background: #fff3cd; color: #856404; }
    .layer-badge.musrenbang { background: #fce4ec; color: #880e4f; }

    .prop-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
    .prop-row .prop-key { flex: 1; font-family: monospace; font-size: .875rem; }
    .prop-row .prop-val { flex: 2; }

    .img-card { border: 1px solid #dee2e6; border-radius: 10px; overflow: hidden; background: #fff; }
    .img-card img { width: 100%; height: 130px; object-fit: cover; display: block; }
    .img-card .img-footer { padding: 6px 8px; display: flex; align-items: center; gap: 6px; background: #f8f9fa; }

    .upload-drop { border: 2px dashed #dee2e6; border-radius: 10px; padding: 24px; text-align: center; cursor: pointer; transition: all .3s; background: #fafafa; }
    .upload-drop:hover, .upload-drop.dragover { border-color: #667eea; background: #f0ecff; }
</style>
@endpush

@section('main')
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-warning text-white me-2">
            <i class="mdi mdi-pencil"></i>
        </span>
        Edit Fitur
    </h3>
    <nav aria-label="breadcrumb">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('layers.index', ['category' => $layer->category]) }}">
                    {{ ['tematik'=>'Peta Tematik','psd'=>'PSD','psn'=>'PSN','pokir'=>'Pokir DPRD','musrenbang'=>'Musrenbang'][$layer->category] ?? $layer->category }}
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('layers.features.index', $layer) }}">{{ $layer->name }}</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('layers.features.show', [$layer, $feature]) }}">Detail Fitur</a>
            </li>
            <li class="breadcrumb-item active">Edit</li>
        </ul>
    </nav>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">

    {{-- ── Kolom Kiri: Form Edit ─────────────────────────────────── --}}
    <div class="col-md-7">

        {{-- Layer Info Banner --}}
        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded-3">
            <span class="layer-badge {{ $layer->category }}">
                <i class="mdi mdi-layers"></i>
                {{ ['tematik'=>'Peta Tematik','psd'=>'Proyek Strategis Daerah','psn'=>'Proyek Strategis Nasional','pokir'=>'Pokir DPRD','musrenbang'=>'Usulan Musrenbang'][$layer->category] ?? $layer->category }}
            </span>
            <span class="fw-semibold">{{ $layer->name }}</span>
            <span class="ms-auto badge bg-secondary">{{ ucfirst($layer->type) }}</span>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="mdi mdi-information-outline me-2"></i>Informasi Fitur</h5>
            </div>
            <div class="card-body">

                {{-- UUID (readonly) --}}
                <div class="mb-3">
                    <label class="form-label text-muted small fw-semibold">UUID</label>
                    <div class="d-flex align-items-center gap-2">
                        <code class="bg-light px-3 py-2 rounded flex-grow-1 small">{{ $feature->uuid }}</code>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="navigator.clipboard.writeText('{{ $feature->uuid }}').then(()=>this.innerHTML='<i class=\'mdi mdi-check\'></i>').catch(()=>{})"
                                title="Salin UUID">
                            <i class="mdi mdi-content-copy"></i>
                        </button>
                    </div>
                </div>

                <form method="POST" action="{{ route('layers.features.update', [$layer, $feature]) }}" id="editForm">
                    @csrf @method('PUT')

                    {{-- Tahun --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Tahun Data</label>
                        <input type="number" name="year" class="form-control"
                               value="{{ old('year', $feature->year) }}"
                               min="1900" max="{{ date('Y') + 5 }}" style="width:130px;">
                    </div>

                    {{-- Properties key-value --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Properties</label>
                        <div class="form-text mb-2">Edit atribut fitur. Perubahan akan menggantikan seluruh properties yang ada.</div>

                        <div class="d-flex gap-2 mb-1 px-1">
                            <small class="text-muted fw-semibold" style="flex:1;">Nama Atribut</small>
                            <small class="text-muted fw-semibold" style="flex:2;">Nilai</small>
                            <div style="width:36px;"></div>
                        </div>

                        <div id="prop-rows">
                            @if($feature->properties && count($feature->properties) > 0)
                                @foreach($feature->properties as $key => $val)
                                <div class="prop-row">
                                    <input type="text" name="prop_keys[]" class="form-control form-control-sm prop-key"
                                           value="{{ $key }}" placeholder="Nama atribut">
                                    <input type="text" name="prop_values[]" class="form-control form-control-sm prop-val"
                                           value="{{ is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val }}" placeholder="Nilai">
                                    <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="removePropRow(this)">
                                        <i class="mdi mdi-minus"></i>
                                    </button>
                                </div>
                                @endforeach
                            @else
                                <div class="prop-row">
                                    <input type="text" name="prop_keys[]" class="form-control form-control-sm prop-key" placeholder="Nama atribut">
                                    <input type="text" name="prop_values[]" class="form-control form-control-sm prop-val" placeholder="Nilai">
                                    <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="removePropRow(this)" style="visibility:hidden;">
                                        <i class="mdi mdi-minus"></i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addPropRow()">
                            <i class="mdi mdi-plus me-1"></i>Tambah Atribut
                        </button>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-gradient-warning">
                            <i class="mdi mdi-content-save me-1"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('layers.features.show', [$layer, $feature]) }}" class="btn btn-light">
                            <i class="mdi mdi-arrow-left me-1"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Info dibuat/diupdate --}}
        <div class="card mt-3">
            <div class="card-body py-2">
                <div class="d-flex gap-4 text-muted small">
                    <span><i class="mdi mdi-calendar-plus me-1"></i>Dibuat: {{ $feature->created_at->format('d M Y, H:i') }}</span>
                    @if($feature->updated_at && $feature->updated_at != $feature->created_at)
                    <span><i class="mdi mdi-calendar-edit me-1"></i>Diperbarui: {{ $feature->updated_at->format('d M Y, H:i') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Kolom Kanan: Manajemen Gambar ───────────────────────────── --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="mdi mdi-image-multiple me-2"></i>
                    Gambar
                    <span class="badge bg-secondary ms-1">{{ $feature->images->count() }}</span>
                </h5>
            </div>
            <div class="card-body">

                {{-- Existing images --}}
                @if($feature->images->count() > 0)
                <div class="row g-2 mb-4">
                    @foreach($feature->images as $img)
                    <div class="col-6">
                        <div class="img-card">
                            <img src="{{ asset('storage/' . $img->file_path) }}" alt="{{ $img->caption }}">
                            <div class="img-footer">
                                @if($img->caption)
                                <span class="small text-muted flex-grow-1 text-truncate" title="{{ $img->caption }}">{{ $img->caption }}</span>
                                @else
                                <span class="small text-muted flex-grow-1">—</span>
                                @endif
                                <form method="POST"
                                      action="{{ route('layers.features.images.destroy', [$layer, $feature, $img]) }}"
                                      onsubmit="return confirm('Hapus gambar ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size:.75rem;">
                                        <i class="mdi mdi-trash-can-outline"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-3 mb-3">
                    <i class="mdi mdi-image-off-outline" style="font-size:2rem;"></i>
                    <p class="small mb-0 mt-1">Belum ada gambar</p>
                </div>
                @endif

                {{-- Upload new image --}}
                <form method="POST"
                      action="{{ route('layers.features.images.store', [$layer, $feature]) }}"
                      enctype="multipart/form-data" id="uploadForm">
                    @csrf

                    <div class="upload-drop mb-2" id="uploadDrop"
                         ondragover="event.preventDefault();this.classList.add('dragover')"
                         ondragleave="this.classList.remove('dragover')"
                         ondrop="handleImgDrop(event)"
                         onclick="document.getElementById('imgInput').click()">
                        <i class="mdi mdi-cloud-upload-outline" style="font-size:2rem;color:#adb5bd;"></i>
                        <p class="mb-1 mt-1 small text-muted">Drag & drop atau klik untuk upload</p>
                        <span class="btn btn-sm btn-outline-secondary">Pilih Gambar</span>
                        <input type="file" id="imgInput" name="image" accept="image/jpg,image/jpeg,image/png,image/webp"
                               style="display:none;" onchange="previewImg(this)" required>
                    </div>

                    <div id="img-preview-wrap" style="display:none;" class="mb-2">
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
                            <img id="img-preview-thumb" src="" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                            <div class="flex-grow-1 small text-truncate" id="img-preview-name"></div>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1"
                                    onclick="clearImgPreview()" style="font-size:.7rem;flex-shrink:0;">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>
                    </div>

                    <input type="text" name="caption" class="form-control form-control-sm mb-2"
                           placeholder="Keterangan gambar (opsional)">

                    <button type="submit" class="btn btn-sm btn-outline-primary w-100" id="btnUpload">
                        <i class="mdi mdi-upload me-1"></i> Upload Gambar
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// ── Properties rows ──────────────────────────────────────────────
function addPropRow() {
    const container = document.getElementById('prop-rows');
    const row = document.createElement('div');
    row.className = 'prop-row';
    row.innerHTML = `
        <input type="text" name="prop_keys[]"   class="form-control form-control-sm prop-key" placeholder="Nama atribut">
        <input type="text" name="prop_values[]" class="form-control form-control-sm prop-val" placeholder="Nilai">
        <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="removePropRow(this)">
            <i class="mdi mdi-minus"></i>
        </button>`;
    container.appendChild(row);
    updateRemoveButtons();
}

function removePropRow(btn) {
    btn.closest('.prop-row').remove();
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.prop-row');
    rows.forEach(r => {
        const btn = r.querySelector('button');
        if (btn) btn.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
    });
}

// ── Image upload helpers ──────────────────────────────────────────
function previewImg(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('img-preview-thumb').src = e.target.result;
        document.getElementById('img-preview-name').textContent = file.name;
        document.getElementById('img-preview-wrap').style.display = '';
        document.getElementById('uploadDrop').classList.add('uploaded');
    };
    reader.readAsDataURL(file);
}

function clearImgPreview() {
    document.getElementById('imgInput').value = '';
    document.getElementById('img-preview-wrap').style.display = 'none';
    document.getElementById('img-preview-thumb').src = '';
    document.getElementById('uploadDrop').classList.remove('uploaded', 'dragover');
}

function handleImgDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    const dt = e.dataTransfer;
    if (dt && dt.files.length) {
        const input = document.getElementById('imgInput');
        input.files = dt.files;
        previewImg(input);
    }
}

// ── Form: convert key-value rows → JSON properties field ─────────
// Override form submit to encode properties as JSON string
document.getElementById('editForm').addEventListener('submit', function(e) {
    // Collect prop rows into a hidden properties JSON field
    const keys   = [...document.querySelectorAll('#prop-rows input[name="prop_keys[]"]')].map(i => i.value.trim());
    const vals   = [...document.querySelectorAll('#prop-rows input[name="prop_values[]"]')].map(i => i.value);
    const obj    = {};
    keys.forEach((k, i) => { if (k) obj[k] = vals[i] ?? ''; });

    // Add hidden field
    let hidden = document.getElementById('properties-hidden');
    if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id   = 'properties-hidden';
        hidden.name = 'properties';
        this.appendChild(hidden);
    }
    hidden.value = JSON.stringify(obj);

    // Remove the key/value pair fields so they don't conflict
    document.querySelectorAll('#prop-rows input[name="prop_keys[]"]').forEach(el => el.removeAttribute('name'));
    document.querySelectorAll('#prop-rows input[name="prop_values[]"]').forEach(el => el.removeAttribute('name'));
});

// Upload form submit state
document.getElementById('uploadForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnUpload');
    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Mengupload...';
});

// Init remove buttons visibility
updateRemoveButtons();
</script>
@endpush
