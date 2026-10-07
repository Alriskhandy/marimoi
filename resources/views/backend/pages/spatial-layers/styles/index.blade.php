@extends('backend.partials.main', ['title' => 'Style Layer'])

@push('styles')
    @include('backend.partials._style-picker-styles')
    <style>
        .feature-style-swatch {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
    </style>
@endpush

@php
    $isPointLayer = in_array($layer->layerType?->geometry_type, ['MULTIPOINT', 'GEOMETRY']);
@endphp

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-palette-outline"></i></span>
            Style: {{ $layer->name }}
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('spatial-layers.index') }}">Daftar Layer & Data</a></li>
                <li class="breadcrumb-item"><a href="{{ route('spatial-layers.show', $layer) }}">{{ $layer->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Style</li>
            </ul>
        </nav>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($classificationFields->isEmpty())
        <div class="alert alert-info">
            <i class="mdi mdi-information me-2"></i>
            Layer ini belum punya atribut dinamis aktif (Jenis Peta: {{ $layer->mapType?->nama ?? '-' }}) — style
            <strong>categorized</strong>/<strong>graduated</strong> butuh minimal satu atribut untuk diklasifikasi.
            Style <strong>simple</strong> tetap bisa dibuat.
        </div>
    @endif

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="card-title mb-0">Daftar Style ({{ $styles->count() }})</p>
                        <button type="button" class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#addStyleModal">
                            <i class="mdi mdi-plus"></i> Tambah Style
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Default</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>Legenda</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($styles as $style)
                                    <tr>
                                        <td>
                                            @if ($layer->default_style_id === $style->id)
                                                <span class="badge bg-success text-white">Default</span>
                                            @endif
                                        </td>
                                        <td>{{ $style->name }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $styleTypes[$style->style_type] ?? $style->style_type }}</span></td>
                                        <td>
                                            @if ($style->style_type === 'simple')
                                                <span style="display:inline-block;width:16px;height:16px;border-radius:4px;background:{{ $style->definition['color'] ?? '#2563eb' }};border:1px solid #dee2e6;"></span>
                                            @else
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach ($style->legend as $entry)
                                                        <span class="d-inline-flex align-items-center gap-1" style="font-size:0.75rem;">
                                                            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:{{ $entry['color'] }};border:1px solid #dee2e6;"></span>
                                                            {{ $entry['label'] }}
                                                        </span>
                                                    @endforeach
                                                    @if (empty($style->legend))
                                                        <span class="text-muted small">Belum ada kelas</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @if ($layer->default_style_id !== $style->id)
                                                    <form action="{{ route('spatial-layers.styles.update', [$layer, $style]) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="name" value="{{ $style->name }}">
                                                        <input type="hidden" name="style_type" value="{{ $style->style_type }}">
                                                        <input type="hidden" name="classification_field" value="{{ $style->classification_field }}">
                                                        <input type="hidden" name="color" value="{{ $style->definition['color'] ?? '' }}">
                                                        <input type="hidden" name="icon" value="{{ $style->definition['icon'] ?? '' }}">
                                                        <input type="hidden" name="opacity" value="{{ $style->definition['opacity'] ?? 1 }}">
                                                        @foreach ($style->definition['classes'] ?? [] as $i => $class)
                                                            <input type="hidden" name="classes[{{ $i }}][value]" value="{{ $class['value'] ?? '' }}">
                                                            <input type="hidden" name="classes[{{ $i }}][min]" value="{{ $class['min'] ?? '' }}">
                                                            <input type="hidden" name="classes[{{ $i }}][max]" value="{{ $class['max'] ?? '' }}">
                                                            <input type="hidden" name="classes[{{ $i }}][color]" value="{{ $class['color'] ?? '' }}">
                                                            <input type="hidden" name="classes[{{ $i }}][label]" value="{{ $class['label'] ?? '' }}">
                                                        @endforeach
                                                        <input type="hidden" name="is_default" value="1">
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Jadikan Default">
                                                            <i class="mdi mdi-star-outline"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-outline-warning" title="Edit"
                                                    data-bs-toggle="modal" data-bs-target="#editStyleModal{{ $loop->index }}">
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                @if ($layer->default_style_id !== $style->id)
                                                    <form action="{{ route('spatial-layers.styles.destroy', [$layer, $style]) }}" method="POST"
                                                        data-confirm="delete" data-name="{{ $style->name }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada style untuk Layer ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-1">Custom Style per Data Spasial ({{ $features->count() }})</p>
                    <p class="text-muted small mb-3">
                        Semua Data Spasial ikut style default di atas, kecuali diberi style sendiri di sini — mis.
                        tiap Kab/Kota di Layer "Peta Administrasi Kab/Kota" diberi warna polygon berbeda.
                    </p>

                    @if ($features->isEmpty())
                        <p class="text-muted text-center py-3">Belum ada Data Spasial di Layer ini.</p>
                    @else
                        <div class="list-group" style="max-height: 480px; overflow-y: auto;">
                            @foreach ($features as $feature)
                                @php
                                    $override = $feature->style_override;
                                    $effectiveColor = $override['color'] ?? $layer->color ?? '#2563eb';
                                    $panelId = 'featureStylePanel'.$feature->id;
                                @endphp
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="feature-style-swatch" style="background:{{ $effectiveColor }};"></span>
                                            <span>{{ $feature->label ?? '#'.$feature->id }}</span>
                                            @if ($override)
                                                <span class="badge bg-info text-white">Custom</span>
                                            @else
                                                <span class="text-muted small">Ikut style default</span>
                                            @endif
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                                                data-bs-target="#{{ $panelId }}">
                                                <i class="mdi mdi-palette-outline"></i> {{ $override ? 'Edit Custom' : 'Atur Custom' }}
                                            </button>
                                            @if ($override)
                                                <form action="{{ route('spatial-layers.features.update-style', [$layer, $feature]) }}"
                                                    method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="custom_style" value="0">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                        title="Kembali ke style default Layer">
                                                        <i class="mdi mdi-backup-restore"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="collapse mt-3 feature-style-panel" id="{{ $panelId }}"
                                        data-feature-id="{{ $feature->id }}">
                                        <form action="{{ route('spatial-layers.features.update-style', [$layer, $feature]) }}"
                                            method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="custom_style" value="1">

                                            <div class="text-center mb-3">
                                                <div id="featureStylePreviewCircle{{ $feature->id }}"
                                                    class="layer-style-preview-circle"
                                                    style="background-color: {{ $effectiveColor }}; opacity: {{ $override['opacity'] ?? $layer->opacity ?? 1 }}; width: {{ ($override['size'] ?? $layer->size ?? 6) * 4 }}px; height: {{ ($override['size'] ?? $layer->size ?? 6) * 4 }}px;">
                                                    <i id="featureStylePreviewIcon{{ $feature->id }}"
                                                        class="{{ ($override['is_marker'] ?? false) ? ($override['icon'] ?? '') : '' }}"
                                                        style="{{ ($override['is_marker'] ?? false) && ($override['icon'] ?? null) ? '' : 'display:none' }}; color: {{ $effectiveColor }};"></i>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="feature_style_color{{ $feature->id }}" class="form-label">Warna</label>
                                                <input type="color" class="form-control form-control-color w-100"
                                                    id="feature_style_color{{ $feature->id }}" name="style_color"
                                                    value="{{ $effectiveColor }}">
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="feature_style_opacity{{ $feature->id }}" class="form-label">
                                                        Opacity — <span id="featureStyleOpacityValue{{ $feature->id }}">{{ $override['opacity'] ?? $layer->opacity ?? 1 }}</span>
                                                    </label>
                                                    <input type="range" class="form-range"
                                                        id="feature_style_opacity{{ $feature->id }}" name="style_opacity"
                                                        min="0" max="1" step="0.05"
                                                        value="{{ $override['opacity'] ?? $layer->opacity ?? 1 }}">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="feature_style_size{{ $feature->id }}" class="form-label">Ukuran (px)</label>
                                                    <input type="number" class="form-control"
                                                        id="feature_style_size{{ $feature->id }}" name="style_size"
                                                        min="1" max="100" step="1"
                                                        value="{{ $override['size'] ?? $layer->size ?? 6 }}">
                                                </div>
                                            </div>

                                            @if ($isPointLayer)
                                                <div class="mb-3">
                                                    <label class="form-label d-block">Jenis Marker</label>
                                                    <div class="btn-group w-100" role="group">
                                                        <input type="radio" class="btn-check" name="style_is_marker" value="0"
                                                            id="feature_style_marker_dot{{ $feature->id }}" autocomplete="off"
                                                            @checked(! ($override['is_marker'] ?? false))>
                                                        <label class="btn btn-outline-secondary"
                                                            for="feature_style_marker_dot{{ $feature->id }}">
                                                            <i class="mdi mdi-circle"></i> Dot
                                                        </label>

                                                        <input type="radio" class="btn-check" name="style_is_marker" value="1"
                                                            id="feature_style_marker_icon{{ $feature->id }}" autocomplete="off"
                                                            @checked($override['is_marker'] ?? false)>
                                                        <label class="btn btn-outline-secondary"
                                                            for="feature_style_marker_icon{{ $feature->id }}">
                                                            <i class="mdi mdi-map-marker"></i> Icon
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="mb-3" id="feature_style_icon_wrapper{{ $feature->id }}"
                                                    style="{{ ($override['is_marker'] ?? false) ? '' : 'display:none' }}">
                                                    <label class="form-label">Pilih Icon</label>

                                                    <select id="feature_style_icon{{ $feature->id }}" name="style_icon"
                                                        class="d-none">
                                                        <option value="">-- Pilih Icon --</option>
                                                        @include('backend.partials.icon-options')
                                                    </select>

                                                    <div class="input-group input-group-sm mb-2">
                                                        <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                                        <input type="text" class="form-control"
                                                            id="feature_style_icon_search{{ $feature->id }}"
                                                            placeholder="Cari ikon berdasarkan nama atau class...">
                                                    </div>

                                                    <div class="icon-card-grid" id="feature_style_icon_grid{{ $feature->id }}"></div>
                                                </div>
                                            @endif

                                            <button type="submit" class="btn btn-sm btn-gradient-primary">
                                                <i class="mdi mdi-content-save"></i> Simpan Style
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Tambah Style Modal -->
    <div class="modal fade" id="addStyleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('spatial-layers.styles.store', $layer) }}" class="style-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="mdi mdi-plus"></i> Tambah Style</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @include('backend.pages.spatial-layers.styles._form', ['style' => null, 'styleTypes' => $styleTypes, 'classificationFields' => $classificationFields, 'uid' => 'new'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-gradient-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Style Modals -->
    @foreach ($styles as $style)
        <div class="modal fade" id="editStyleModal{{ $loop->index }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('spatial-layers.styles.update', [$layer, $style]) }}" class="style-form">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="mdi mdi-pencil"></i> Edit Style</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @include('backend.pages.spatial-layers.styles._form', ['style' => $style, 'styleTypes' => $styleTypes, 'classificationFields' => $classificationFields, 'uid' => $loop->index])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-gradient-warning">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
    @include('backend.partials._style-picker-script')
    <script>
        // Grid ikon tiap panel custom style Data Spasial BARU dibangun saat
        // panelnya pertama kali dibuka (bukan semua sekaligus saat halaman
        // dimuat) — Layer dengan banyak Data Spasial bisa punya banyak panel,
        // membangun semuanya di depan percuma kalau yang dibuka cuma 1-2.
        document.querySelectorAll('.feature-style-panel').forEach((panel) => {
            panel.addEventListener('show.bs.collapse', function onShow() {
                if (panel.dataset.initialized) {
                    return;
                }
                panel.dataset.initialized = '1';

                const featureId = panel.dataset.featureId;
                initStylePicker({
                    colorId: `feature_style_color${featureId}`,
                    opacityId: `feature_style_opacity${featureId}`,
                    opacityValueId: `featureStyleOpacityValue${featureId}`,
                    sizeId: `feature_style_size${featureId}`,
                    previewCircleId: `featureStylePreviewCircle${featureId}`,
                    previewIconId: `featureStylePreviewIcon${featureId}`,
                    iconSelectId: `feature_style_icon${featureId}`,
                    iconWrapperId: `feature_style_icon_wrapper${featureId}`,
                    iconGridId: `feature_style_icon_grid${featureId}`,
                    iconSearchId: `feature_style_icon_search${featureId}`,
                    markerRadioName: 'style_is_marker',
                    initialIconValue: document.getElementById(`feature_style_icon${featureId}`)?.value ?? '',
                    scope: panel,
                });
            });
        });

        /**
         * Builder kelas categorized/graduated — baris {value|min/max, warna,
         * label} ditambah/dihapus secara dinamis per instance form (dibedakan
         * lewat `uid` supaya modal Tambah & tiap modal Edit tidak saling
         * bertabrakan id elemen-nya).
         */
        function toggleStyleFields(uid) {
            const type = document.getElementById(`style_type_${uid}`).value;
            document.getElementById(`simple_fields_${uid}`).style.display = type === 'simple' ? '' : 'none';
            document.getElementById(`classified_fields_${uid}`).style.display = type === 'simple' ? 'none' : '';
            document.querySelectorAll(`#classes_${uid} .class-row`).forEach((row) => {
                row.querySelectorAll('.class-field-value').forEach((el) => el.style.display = type === 'categorized' ? '' : 'none');
                row.querySelectorAll('.class-field-range').forEach((el) => el.style.display = type === 'graduated' ? '' : 'none');
            });
        }

        function addClassRow(uid) {
            const container = document.getElementById(`classes_${uid}`);
            const index = container.querySelectorAll('.class-row').length;
            const type = document.getElementById(`style_type_${uid}`).value;
            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 class-row align-items-center';
            row.innerHTML = `
                <div class="col-3 class-field-value" style="display:${type === 'categorized' ? '' : 'none'}">
                    <input type="text" class="form-control form-control-sm" name="classes[${index}][value]" placeholder="Nilai">
                </div>
                <div class="col-1 class-field-range" style="display:${type === 'graduated' ? '' : 'none'}">
                    <input type="number" step="any" class="form-control form-control-sm" name="classes[${index}][min]" placeholder="Min">
                </div>
                <div class="col-1 class-field-range" style="display:${type === 'graduated' ? '' : 'none'}">
                    <input type="number" step="any" class="form-control form-control-sm" name="classes[${index}][max]" placeholder="Max">
                </div>
                <div class="col-2">
                    <input type="color" class="form-control form-control-sm form-control-color" name="classes[${index}][color]" value="#2563eb">
                </div>
                <div class="col-4">
                    <input type="text" class="form-control form-control-sm" name="classes[${index}][label]" placeholder="Label legenda (opsional)">
                </div>
                <div class="col-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.class-row').remove()">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
        }
    </script>
@endpush
