{{--
    Panel detail kategori/subkategori terpilih di panel kanan — satu panel
    per baris $categories, disembunyikan lewat CSS lalu ditampilkan oleh JS
    saat node yang sesuai dipilih di pohon taksonomi (lihat @push('scripts')
    di index.blade.php). $category->direct_layers/root_id diisi oleh
    CategoryController::index().
--}}
@php
    $isRoot = $category->depth === 0;
    $canManage = in_array($role, ['super-admin', 'admin-bappeda']) || $category->user_id === $user->id;
    $slug = Str::slug($category->nama);
    $layers = $category->direct_layers ?? collect();
    $statusMap = [
        'published' => ['label' => 'Published', 'class' => 'bg-success', 'icon' => 'mdi-check-circle'],
        'draft' => ['label' => 'Draft', 'class' => 'bg-secondary', 'icon' => 'mdi-file-document-edit-outline'],
        'archived' => ['label' => 'Archived', 'class' => 'bg-dark', 'icon' => 'mdi-archive'],
    ];
@endphp
<div class="card category-detail-panel" id="category-panel-{{ $category->id }}"
    data-category-id="{{ $category->id }}" style="display: none;">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div class="d-flex align-items-start gap-3">
                <div>
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                        <span class="badge {{ $isRoot ? 'bg-primary' : 'bg-info' }}">
                            {{ $isRoot ? 'KATEGORI UTAMA' : 'SUBKATEGORI' }}
                        </span>
                        <small class="text-muted">slug: {{ $slug }}</small>
                    </div>
                    <h4 class="mb-0">{{ $category->nama }}</h4>
                </div>
            </div>
            <div class="d-flex gap-2">
                @if ($canManage && $user->can('categories.edit'))
                    <a href="{{ route('categories.metadata.edit', $category->id) }}"
                        class="btn btn-sm btn-outline-info">
                        <i class="mdi mdi-file-document-outline"></i> Metadata Kategori
                    </a>
                @endif
                {{--
                    §5.1 butir 4 — panel isi katalog: buka Daftar Layer sudah
                    terfilter ke kategori/node ini, bukan harus pindah halaman
                    lalu memfilter manual.
                --}}
                <a href="{{ route('spatial-layers.index', ['category' => ($isRoot ? 'cat:' : 'node:').$category->id]) }}"
                    class="btn btn-sm btn-outline-primary" title="Lihat Layer di kategori ini">
                    <i class="mdi mdi-layers-outline"></i> Lihat Semua Layer
                </a>
                @can('spatial-layers.create')
                    <a href="{{ route('spatial-layers.create', array_filter([
                        'category_id' => $category->root_id ?? $category->id,
                        'category_node_id' => $isRoot ? null : $category->id,
                    ])) }}" class="btn btn-sm btn-gradient-primary">
                        <i class="mdi mdi-plus"></i> Tambah Layer
                    </a>
                @endcan
            </div>
        </div>

        <div class="row g-3 mb-3 category-detail-meta">
            <div class="col-6 col-md-4">
                <div class="text-muted small">Sub-kategori</div>
                <div class="fw-semibold">{{ $category->children->count() }}</div>
            </div>
            <div class="col-6 col-md-4">
                <div class="text-muted small">Total Layer</div>
                <div class="fw-semibold">{{ (int) ($category->layers_count ?? 0) }}</div>
            </div>
            <div class="col-6 col-md-4">
                <div class="text-muted small">Total Data Spasial</div>
                <div class="fw-semibold">{{ (int) ($category->data_spatial_count ?? 0) }}</div>
            </div>
        </div>

        @if ($category->deskripsi)
            <p class="text-muted">{{ $category->deskripsi }}</p>
        @endif

        @if ($canManage)
            <div class="btn-group btn-group-sm mb-3" role="group">
                @can('categories.edit')
                    <button type="button" class="btn btn-outline-success btn-edit" data-id="{{ $category->id }}"
                        data-nama="{{ $category->nama }}" data-parent-id="{{ $category->parent_id }}"
                        data-sort-order="{{ $category->sort_order }}" data-deskripsi="{{ $category->deskripsi }}"
                        data-bs-toggle="modal" data-bs-target="#editModal" title="Edit Kategori">
                        <i class="mdi mdi-pencil"></i> Edit
                    </button>
                @endcan
                @can('categories.delete')
                    <form action="{{ route('categories.destroy', $category->id) }}" method="POST"
                        class="d-inline-block" data-confirm="delete" data-name="{{ $category->nama }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" title="Hapus kategori">
                            <i class="mdi mdi-delete"></i> Hapus
                        </button>
                    </form>
                @endcan
            </div>
        @endif

        <hr>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0">Daftar Layer ({{ $layers->count() }})</h5>
            <div class="d-flex flex-wrap gap-2">
                <input type="text" class="form-control form-control-sm layer-search" placeholder="Cari layer..."
                    style="width: 200px;">
                <div class="btn-group btn-group-sm layer-geometry-filter" role="group">
                    <button type="button" class="btn btn-outline-secondary active" data-geometry="">Semua</button>
                    <button type="button" class="btn btn-outline-secondary" data-geometry="polygon">Polygon</button>
                    <button type="button" class="btn btn-outline-secondary" data-geometry="line">Line</button>
                    <button type="button" class="btn btn-outline-secondary" data-geometry="point">Point</button>
                </div>
            </div>
        </div>

        <div class="category-layer-list">
            @forelse ($layers as $layer)
                @php
                    $statusInfo =
                        $statusMap[$layer->status] ?? [
                            'label' => ucfirst($layer->status),
                            'class' => 'bg-secondary',
                            'icon' => 'mdi-help-circle',
                        ];
                    $geometryLower = Str::lower($layer->geometry_type ?? '');
                @endphp
                <div class="card category-layer-card mb-3" data-layer-search="{{ Str::lower($layer->name) }}"
                    data-geometry="{{ $geometryLower }}">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge {{ $statusInfo['class'] }}"><i
                                    class="mdi {{ $statusInfo['icon'] }}"></i> {{ $statusInfo['label'] }}</span>
                            <small class="text-muted">ID: {{ $layer->code }}</small>
                            @if ($layer->geometry_type)
                                <span class="badge bg-light text-dark border">{{ strtoupper($layer->geometry_type) }}</span>
                            @endif
                        </div>
                        <h6 class="mb-1">{{ $layer->name }}</h6>
                        @if ($layer->short_description)
                            <p class="text-muted small mb-2">{{ $layer->short_description }}</p>
                        @endif

                        <div class="row g-2 category-layer-meta">
                            <div class="col-6 col-md-3">
                                <div class="text-muted small">Entitas</div>
                                <div class="fw-semibold">{{ number_format($layer->feature_count) }} data</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small">Tahun Data</div>
                                <div class="fw-semibold">{{ $layer->metadata?->data_year ?? '-' }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small">Sistem Koordinat</div>
                                <div class="fw-semibold">EPSG:{{ $layer->storage_srid }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted small">Wali Data</div>
                                <div class="fw-semibold">
                                    {{ $layer->opd?->singkatan ?? $layer->opd?->name ?? 'Provinsi/Bappeda' }}</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">Terakhir diperbarui
                                {{ $layer->updated_at?->diffForHumans() }}</small>
                            <a href="{{ route('spatial-layers.show', $layer) }}"
                                class="btn btn-sm btn-outline-primary">
                                Rincian & Atribut <i class="mdi mdi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4 category-layer-empty">
                    <i class="mdi mdi-layers-off-outline mdi-36px"></i>
                    <p class="mb-0 mt-2">Belum ada Layer di kategori ini.</p>
                </div>
            @endforelse
            <div class="text-center text-muted py-4 category-layer-no-match" style="display: none;">
                <p class="mb-0">Tidak ada layer yang cocok dengan pencarian/filter.</p>
            </div>
        </div>
    </div>
</div>
