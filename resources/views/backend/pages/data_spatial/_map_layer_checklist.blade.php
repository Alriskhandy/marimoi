<ul class="layer-tree {{ $level ?? 0 ? 'layer-tree-child' : '' }}">
    @forelse ($categories as $category)
        <li>
            <div class="layer-item">
                <label class="layer-label" for="layer-cat-{{ $category->id }}">
                    <input class="map-layer-checkbox" type="checkbox" value="{{ $category->id }}"
                        id="layer-cat-{{ $category->id }}">
                    <span class="layer-swatch" style="background: {{ $category->warna ?: '#0d6efd' }}"></span>
                    <span class="layer-name">{{ $category->nama }}</span>
                </label>
                <span class="layer-status">
                    <span class="spinner-border spinner-border-sm text-primary d-none" role="status"></span>
                    <span class="layer-count d-none"></span>
                    <button type="button" class="layer-zoom d-none" data-category-id="{{ $category->id }}"
                        title="Zoom ke layer">
                        <i class="mdi mdi-crosshairs-gps"></i>
                    </button>
                </span>
            </div>
            @if ($category->children->count())
                @include('backend.pages.data_spatial._map_layer_checklist', [
                    'categories' => $category->children,
                    'level' => ($level ?? 0) + 1,
                ])
            @endif
        </li>
    @empty
        <li class="text-muted small">Belum ada kategori.</li>
    @endforelse
</ul>
