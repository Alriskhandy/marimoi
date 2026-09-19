@foreach ($categories as $category)
    @php
        $hasChildren = $category->children->isNotEmpty();
        $depth = $level ?? 0;
        // Induk yang anaknya masih punya anak hanya berfungsi sebagai pengelompokan (seperti
        // di peta frontend): tidak bisa dicentang, cukup dibuka untuk memilih sub kategorinya.
        $isGroupOnly = $category->children->contains(fn ($child) => $child->children->isNotEmpty());
    @endphp
    <div class="layer-node" data-name="{{ \Illuminate\Support\Str::lower($category->nama) }}">
        <div class="layer-row layer-depth-{{ min($depth, 2) }}">
            @if ($hasChildren)
                <button type="button" class="layer-toggle" aria-label="Buka/tutup sub kategori">
                    <i class="mdi mdi-chevron-right"></i>
                </button>
            @else
                <span class="layer-toggle layer-toggle-empty"></span>
            @endif

            @if ($isGroupOnly)
                <span class="layer-label layer-label-group" role="button" tabindex="0"
                    title="Pilih sub kategori di bawahnya">
                    <span class="layer-name">{{ $category->nama }}</span>
                </span>
            @else
                <label class="layer-label" for="layer-cat-{{ $category->id }}">
                    <input class="map-layer-checkbox" type="checkbox" value="{{ $category->id }}"
                        id="layer-cat-{{ $category->id }}">
                    <span class="layer-swatch" style="background: {{ $category->warna ?: '#0d6efd' }}"></span>
                    <span class="layer-name">{{ $category->nama }}</span>
                </label>
            @endif

            @if ($hasChildren)
                <span class="layer-badge" title="Jumlah sub kategori">{{ $category->children->count() }}</span>
            @endif

            <span class="layer-status">
                <span class="spinner-border spinner-border-sm text-primary d-none" role="status"></span>
                <span class="layer-count d-none"></span>
                @unless ($isGroupOnly)
                    <button type="button" class="layer-zoom d-none" data-category-id="{{ $category->id }}"
                        title="Zoom ke layer">
                        <i class="mdi mdi-crosshairs-gps"></i>
                    </button>
                @endunless
            </span>
        </div>

        @if ($hasChildren)
            <div class="layer-children d-none">
                @include('backend.pages.data_spatial._map_layer_checklist', [
                    'categories' => $category->children,
                    'level' => $depth + 1,
                ])
            </div>
        @endif
    </div>
@endforeach
