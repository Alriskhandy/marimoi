@php
    $isRoot = is_null($kategori->parent_id);
    $hasChild = $kategori->children && $kategori->children->count() > 0;
    $isDisabled = $isRoot && $hasChild;

    // Buat indentasi manual dengan karakter spasi
    $indent = str_repeat('  ', $level); // gunakan karakter spasi lebar (em space)

    // Label kategori
    $label = $isRoot ? $kategori->nama . ($hasChild ? ' (Tidak Bisa Dipilih)' : ' ') : $indent . '› ' . $kategori->nama;
@endphp

<option value="{{ $kategori->id }}" {{ old('kategori_id', $data->kategori_id ?? '') == $kategori->id ? 'selected' : '' }}
    {{ $isDisabled ? 'disabled' : '' }}
    style="
        color: {{ $isDisabled ? '#9ca3af' : '#374151' }};
        font-style: {{ $isDisabled ? 'italic' : 'normal' }};
        background-color: {{ $isDisabled ? '#f3f4f6' : '#fff' }};
        font-size: 14px;
    ">
    {{ $label }}
</option>

@if ($hasChild)
    @foreach ($kategori->children as $child)
        @include('backend.partials.kategori_option', [
            'kategori' => $child,
            'level' => $level + 1,
        ])
    @endforeach
@endif
