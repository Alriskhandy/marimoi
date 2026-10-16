@php
    $prefix = $prefix ?? '';
    $fieldId = fn (string $name) => $prefix !== '' ? "{$prefix}_{$name}" : $name;
    $categoryFieldId = $fieldId('category_id');
    $nodeFieldId = $fieldId('category_node_id');
@endphp

{{--
    Pengganti picker "Layer Induk" v2 (parent_id antar-layer, sudah dihapus
    di skema v3) — Layer sekarang ditempatkan di Kategori/Subkategori
    (categories_v3/category_nodes), bukan di bawah Layer lain. Dipakai di 3
    tempat (modal Tambah Layer di index, create.blade.php, modal Edit Layer
    di show.blade.php) lewat @include supaya logiknya konsisten di satu
    tempat saja.
--}}
<div class="row">
    <div class="col-md-6 mb-2">
        <label for="{{ $categoryFieldId }}" class="form-label">Kategori <span class="text-danger">*</span></label>
        <select class="form-control category-picker-root" id="{{ $categoryFieldId }}" name="category_id"
            data-node-target="{{ $nodeFieldId }}" required>
            <option value="">-- Pilih Kategori --</option>
            @foreach ($categoryOptions as $option)
                <option value="{{ $option->id }}" @selected(($selectedCategoryId ?? old('category_id')) == $option->id)>
                    {{ $option->name }}</option>
            @endforeach
        </select>
        @error('category_id')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 mb-2">
        <label for="{{ $nodeFieldId }}" class="form-label">Subkategori</label>
        <select class="form-control category-picker-node" id="{{ $nodeFieldId }}" name="category_node_id"
            data-initial-value="{{ $selectedCategoryNodeId ?? old('category_node_id') }}">
            <option value="">-- Tidak ada (langsung di akar Kategori) --</option>
        </select>
        @error('category_node_id')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>
</div>

@once
    <script>
        window.__categoryNodeOptions = @json($categoryNodeOptions);

        function refreshCategoryNodePicker(rootSelect) {
            const nodeSelect = document.getElementById(rootSelect.dataset.nodeTarget);
            if (!nodeSelect) {
                return;
            }

            const categoryId = rootSelect.value;
            const preserved = nodeSelect.dataset.initialValue || '';
            nodeSelect.innerHTML = '<option value="">-- Tidak ada (langsung di akar Kategori) --</option>';

            window.__categoryNodeOptions
                .filter((node) => node.category_id === categoryId)
                .forEach((node) => {
                    const opt = document.createElement('option');
                    opt.value = node.id;
                    opt.textContent = '— '.repeat(Math.max(node.depth - 1, 0)) + node.name;
                    if (node.id === preserved) {
                        opt.selected = true;
                    }
                    nodeSelect.appendChild(opt);
                });

            nodeSelect.dataset.initialValue = '';
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('category-picker-root')) {
                refreshCategoryNodePicker(e.target);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.category-picker-root').forEach(refreshCategoryNodePicker);
        });
    </script>
@endonce
