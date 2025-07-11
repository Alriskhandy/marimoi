<option value="{{ $kategori->id }}" {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}>
    {!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level) !!}{{ $kategori->nama }}
</option>

@if ($kategori->children && $kategori->children->count())
    @foreach ($kategori->children as $child)
        @include('backend.partials.kategori_option', ['kategori' => $child, 'level' => $level + 1])
    @endforeach
@endif
