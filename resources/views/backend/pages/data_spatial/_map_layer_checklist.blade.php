<ul class="list-unstyled {{ $level ?? 0 ? 'ms-3' : '' }} mb-0">
    @forelse ($categories as $category)
        <li class="mb-1">
            <div class="form-check">
                <input class="form-check-input map-layer-checkbox" type="checkbox" value="{{ $category->id }}"
                    id="layer-cat-{{ $category->id }}">
                <label class="form-check-label" for="layer-cat-{{ $category->id }}">
                    {{ $category->nama }}
                </label>
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
