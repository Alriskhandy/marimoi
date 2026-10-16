@extends('backend.partials.main', ['title' => 'Template Dokumen'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-file-document-edit"></i></span>
            Template Dokumen
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Template Dokumen</li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Daftar Template</h4>
                            <p class="text-muted small mb-0">Kop, footer, dan pilihan template untuk hasil <b>Unduh Peta</b> dan cetak
                                <b>Analisis Peta</b> di Peta Interaktif. Hanya template aktif yang dapat dipilih pengunjung.</p>
                        </div>
                        @can('document-templates.manage')
                            <a href="{{ route('document-templates.create') }}" class="btn btn-gradient-primary">
                                <i class="mdi mdi-plus"></i> Tambah Template
                            </a>
                        @endcan
                    </div>

                    {{-- Filter jenis (orientasi) --}}
                    <ul class="nav nav-pills mb-3 gap-1">
                        @foreach (['' => 'Semua'] + \App\Models\DocumentTemplate::ORIENTATIONS as $value => $label)
                            <li class="nav-item">
                                <a class="nav-link py-1 px-3 {{ ($jenis ?? '') === $value ? 'active' : '' }}"
                                    href="{{ route('document-templates.index', $value ? ['jenis' => $value] : []) }}">
                                    {{ $label }}
                                    <span class="badge rounded-pill bg-light text-dark ms-1">{{ $value ? ($counts[$value] ?? 0) : $counts->sum() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-dark">Template</th>
                                    <th class="text-dark text-center">Jenis</th>
                                    <th class="text-dark">Kop</th>
                                    <th class="text-dark">Dipakai untuk</th>
                                    <th class="text-dark text-center">Status</th>
                                    @can('document-templates.manage')
                                        <th class="text-dark text-center" style="width: 130px;">Aksi</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($templates as $template)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="d-inline-block rounded" style="width: 12px; height: 32px; background: {{ $template->accent_color }}"></span>
                                                <div>
                                                    <span class="fw-bold">{{ $template->name }}</span>
                                                    @if ($template->is_default)
                                                        <span class="badge bg-primary ms-1">Bawaan</span>
                                                    @endif
                                                    @if ($template->description)
                                                        <div class="small text-muted">{{ $template->description }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php $isPortrait = $template->orientation === 'portrait'; @endphp
                                            <span class="badge {{ $isPortrait ? 'bg-dark' : 'bg-primary' }}" title="Orientasi halaman">
                                                <i class="mdi mdi-file-outline {{ $isPortrait ? '' : 'mdi-rotate-90' }}"></i>
                                                {{ \App\Models\DocumentTemplate::ORIENTATIONS[$template->orientation] ?? 'Lanskap' }}
                                            </span>
                                        </td>
                                        <td class="small">
                                            @if ($template->header_enabled)
                                                <div class="fw-semibold">{{ $template->header_line1 ?: '—' }}</div>
                                                <div class="text-muted">{{ $template->header_line2 }}</div>
                                                <div class="text-muted">
                                                    {{ collect(['left' => 'Logo kiri', 'right' => 'Logo kanan'])->filter(fn ($label, $slot) => $template->{\App\Models\DocumentTemplate::LOGO_SLOTS[$slot]})->join(' · ') ?: 'Tanpa logo' }}
                                                </div>
                                            @else
                                                <span class="text-muted">Tanpa kop</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($template->for_map)
                                                <span class="badge bg-info text-dark">Unduh Peta</span>
                                            @endif
                                            @if ($template->for_analysis)
                                                <span class="badge bg-warning text-dark">Analisis Peta</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </td>
                                        @can('document-templates.manage')
                                            <td class="text-center">
                                                <a href="{{ route('document-templates.edit', $template) }}" class="btn btn-sm btn-outline-primary" title="Ubah {{ $template->name }}">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <form method="POST" action="{{ route('document-templates.destroy', $template) }}" class="d-inline"
                                                    onsubmit="return confirm('Hapus template &quot;{{ $template->name }}&quot;? Logo yang diunggah ikut terhapus.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus {{ $template->name }}">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Belum ada template. Hasil unduhan memakai tata letak standar MARIMOI tanpa kop.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
