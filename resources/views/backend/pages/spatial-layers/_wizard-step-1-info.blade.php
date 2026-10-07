@php
    $isResume = $layer !== null;
    $formAction = $isResume ? route('spatial-layers.wizard.info', $layer) : route('spatial-layers.store');
@endphp

<h5 class="mb-1"><i class="mdi mdi-information-outline me-2"></i>Informasi Layer</h5>
<p class="text-muted small mb-4">Data dasar yang mengidentifikasi Layer ini.</p>

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if ($isResume)
        @method('PUT')
    @endif

    <div class="wizard-section-card mb-3">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="layer_add_name" class="form-label">Nama Layer <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="layer_add_name" name="name"
                    value="{{ old('name', $layer?->name) }}" placeholder="mis. Jaringan Jalan Provinsi" required>
                @error('name')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="layer_add_layer_type_id" class="form-label">Jenis Layer <span
                        class="text-danger">*</span></label>
                <select class="form-select" id="layer_add_layer_type_id" name="layer_type_id" required>
                    @foreach ($layerTypes as $layerType)
                        <option value="{{ $layerType->id }}"
                            @selected(old('layer_type_id', $layer?->layer_type_id) == $layerType->id)>{{ $layerType->name }}</option>
                    @endforeach
                </select>
                @error('layer_type_id')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @include('backend.pages.spatial-layers._category-picker', [
            'prefix' => 'layer_add',
            'selectedCategoryId' => old('category_id', $layer?->category_id),
            'selectedCategoryNodeId' => old('category_node_id', $layer?->category_node_id),
        ])

        <div class="row justify-content-between">
            <div class="col-md-4 mb-3">
                <label for="layer_add_data_year" class="form-label">Tahun Data</label>
                <input type="number" class="form-control" id="layer_add_data_year" name="data_year" min="1900"
                    max="2100" placeholder="mis. 2026"
                    value="{{ old('data_year', $layer?->metadata?->data_year) }}">
                @error('data_year')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label for="layer_add_sumber_data" class="form-label">Sumber Data</label>
                <input type="text" class="form-control" id="layer_add_sumber_data" name="sumber_data"
                    placeholder="mis. Dinas PUPR Provinsi Maluku Utara"
                    value="{{ old('sumber_data', $layer?->metadata?->sumber_data) }}">
                @error('sumber_data')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            @if ($opds->isNotEmpty())
                <div class="col-md-4 mb-3">
                    <label for="layer_add_opd_id" class="form-label">OPD Penanggungjawab</label>
                    <select class="form-select" id="layer_add_opd_id" name="opd_id">
                        <option value="">-- Provinsi/Bappeda --</option>
                        @foreach ($opds as $opd)
                            <option value="{{ $opd->id }}"
                                @selected(old('opd_id', $layer?->opd_id) == $opd->id)>{{ $opd->singkatan }} - {{ $opd->name }}</option>
                        @endforeach
                    </select>
                    @error('opd_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            @endif
        </div>

        <div class="mb-0">
            <label for="layer_add_short_description" class="form-label">Deskripsi</label>
            <textarea class="form-control" id="layer_add_short_description" name="short_description" rows="2"
                placeholder="Penjelasan singkat isi Layer ini (opsional)">{{ old('short_description', $layer?->short_description) }}</textarea>
            @error('short_description')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-between">
        <a href="{{ route('spatial-layers.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-gradient-primary">
            {{ $isResume ? 'Simpan & Lanjut' : 'Lanjut ke Impor Data' }} <i class="mdi mdi-arrow-right"></i>
        </button>
    </div>
</form>

@push('styles')
    <style>
        .wizard-section-card {
            padding: 1.1rem 1.25rem;
            background-color: #f8f9fa;
            border: 1px solid #eef2f7;
            border-radius: 10px;
        }
    </style>
@endpush
