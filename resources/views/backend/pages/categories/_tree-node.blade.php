{{--
    Satu node pohon taksonomi (dipakai rekursif untuk categories_v3/category_nodes
    lewat relasi Category::children). "layers_count"/"root_id" diisi oleh
    CategoryController::index() — lihat komentar di sana.
--}}
@php
    $hasChildren = $category->children->isNotEmpty();
    $badge = (int) ($category->layers_count ?? 0);
@endphp
<li class="taxonomy-tree-node" data-depth="{{ $category->depth }}">
    <div class="taxonomy-tree-row" data-category-id="{{ $category->id }}"
        data-root-id="{{ $category->root_id ?? $category->id }}" data-search="{{ Str::lower($category->nama) }}">
        @if ($hasChildren)
            <button type="button" class="taxonomy-tree-toggle" aria-label="Buka/tutup">
                <i class="mdi mdi-chevron-right"></i>
            </button>
        @else
            <span class="taxonomy-tree-toggle-spacer"></span>
        @endif
        @can('categories.delete')
            <input type="checkbox" class="form-check-input category-row-checkbox" value="{{ $category->id }}"
                data-nama="{{ $category->nama }}" aria-label="Pilih {{ $category->nama }} untuk dihapus">
        @endcan
        <i class="mdi {{ $category->depth === 0 ? 'mdi-folder-outline' : 'mdi-subdirectory-arrow-right' }} taxonomy-tree-icon"></i>
        <span class="taxonomy-tree-label">{{ $category->nama }}</span>
        @if ($badge > 0)
            <span class="badge bg-light text-dark taxonomy-tree-badge">{{ $badge }}</span>
        @endif
    </div>
    @if ($hasChildren)
        <ul class="taxonomy-tree-children">
            @foreach ($category->children as $child)
                @include('backend.pages.categories._tree-node', ['category' => $child])
            @endforeach
        </ul>
    @endif
</li>
