@extends('backend.partials.main', ['title' => 'Manajemen Layer'])

@section('main')
@php
    $categoryLabels = [
        'tematik'    => 'Peta Tematik',
        'psd'        => 'Proyek Strategis Daerah',
        'psn'        => 'Proyek Strategis Nasional',
        'pokir'      => 'Pokir DPRD',
        'musrenbang' => 'Usulan Musrenbang',
    ];
    $categoryIcons = [
        'tematik'    => 'mdi-map-outline',
        'psd'        => 'mdi-city',
        'psn'        => 'mdi-flag',
        'pokir'      => 'mdi-gavel',
        'musrenbang' => 'mdi-forum',
    ];
    $currentCategory = request('category');
    $pageTitle = $currentCategory ? ($categoryLabels[$currentCategory] ?? 'Layer') : 'Semua Layer';
@endphp

{{-- Page Header --}}
<div class="page-header">
    <h3 class="page-title">
        <span class="page-title-icon bg-gradient-primary text-white me-2">
            <i class="mdi {{ $categoryIcons[$currentCategory] ?? 'mdi-layers' }}"></i>
        </span>
        {{ $pageTitle }}
    </h3>
    <nav aria-label="breadcrumb">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">{{ $pageTitle }}</li>
        </ul>
    </nav>
</div>

{{-- Flash Messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats Cards --}}
@if($layers->count() > 0)
<div class="row mb-4">
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-primary card-img-holder text-white">
            <div class="card-body">
                <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute" alt="">
                <h4 class="font-weight-normal mb-3">Total Layer <i class="mdi mdi-layers mdi-24px float-end"></i></h4>
                <h2 class="mb-5">{{ $layers->count() }}</h2>
                <h6 class="card-text">{{ $currentCategory ? $categoryLabels[$currentCategory] : 'Semua kategori' }}</h6>
            </div>
        </div>
    </div>
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-success card-img-holder text-white">
            <div class="card-body">
                <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute" alt="">
                <h4 class="font-weight-normal mb-3">Layer Aktif <i class="mdi mdi-check-circle mdi-24px float-end"></i></h4>
                <h2 class="mb-5">{{ $layers->where('is_active', true)->count() }}</h2>
                <h6 class="card-text">{{ $layers->where('is_active', false)->count() }} nonaktif</h6>
            </div>
        </div>
    </div>
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-warning card-img-holder text-white">
            <div class="card-body">
                <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute" alt="">
                <h4 class="font-weight-normal mb-3">Layer Utama <i class="mdi mdi-folder-outline mdi-24px float-end"></i></h4>
                <h2 class="mb-5">{{ $layers->whereNull('parent_id')->count() }}</h2>
                <h6 class="card-text">{{ $layers->whereNotNull('parent_id')->count() }} sub-layer</h6>
            </div>
        </div>
    </div>
    <div class="col-md-3 stretch-card grid-margin">
        <div class="card bg-gradient-info card-img-holder text-white">
            <div class="card-body">
                <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute" alt="">
                <h4 class="font-weight-normal mb-3">Total Fitur <i class="mdi mdi-map-marker-multiple mdi-24px float-end"></i></h4>
                <h2 class="mb-5">{{ $layers->sum('features_count') }}</h2>
                <h6 class="card-text">Geometri di semua layer</h6>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Main Card --}}
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">

                {{-- Toolbar --}}
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    {{-- Filter Kategori --}}
                    @php
                        $catBtn = [
                            'tematik'    => ['out'=>'btn-outline-primary','on'=>'btn-primary'],
                            'psd'        => ['out'=>'btn-outline-success', 'on'=>'btn-success'],
                            'psn'        => ['out'=>'btn-outline-danger',  'on'=>'btn-danger'],
                            'pokir'      => ['out'=>'btn-outline-warning', 'on'=>'btn-warning'],
                            'musrenbang' => ['out'=>'btn-outline-info',    'on'=>'btn-info'],
                        ];
                    @endphp
                    <a href="{{ route('layers.index') }}"
                       class="btn btn-sm {{ !$currentCategory ? 'btn-dark' : 'btn-outline-dark' }}">
                        <i class="mdi mdi-layers me-1"></i>Semua
                    </a>
                    @foreach($categoryLabels as $key => $label)
                    <a href="{{ route('layers.index', ['category' => $key]) }}"
                       class="btn btn-sm {{ $currentCategory === $key ? $catBtn[$key]['on'] : $catBtn[$key]['out'] }}">
                        <i class="mdi {{ $categoryIcons[$key] }} me-1"></i>{{ $label }}
                    </a>
                    @endforeach

                    <div class="vr mx-1 d-none d-md-block"></div>

                    {{-- Per Halaman --}}
                    <select id="pageLengthSelect" class="form-select form-select-sm" style="width:80px;" title="Per halaman">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">Semua</option>
                    </select>

                    {{-- Search --}}
                    <div class="input-group input-group-sm" style="width:210px;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="mdi mdi-magnify text-muted"></i>
                        </span>
                        <input type="text" id="layerSearch" class="form-control border-start-0 ps-0"
                               placeholder="Cari nama layer...">
                        <button type="button" class="btn btn-outline-secondary d-none" id="clearSearch" title="Hapus">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>

                    {{-- Expand All --}}
                    <button type="button" id="btnExpandAll" class="btn btn-sm btn-expand-all">
                        <i class="mdi mdi-chevron-down me-1"></i>
                        <span>Expand All</span>
                    </button>

                    {{-- Tambah Layer --}}
                    <a href="{{ route('layers.create', array_filter(['category' => $currentCategory])) }}"
                       class="btn btn-sm btn-gradient-primary ms-auto">
                        <i class="mdi mdi-plus me-1"></i>Tambah Layer
                    </a>
                </div>

                {{-- Info drag --}}
                <p class="text-muted mb-2" style="font-size:.8rem;">
                    <i class="mdi mdi-drag me-1"></i>
                    Seret ikon <strong>⠿</strong> untuk mengubah hierarki layer. Drop ke baris lain → jadikan sub-layer.
                </p>

                {{-- Tabel --}}
                <div class="table-responsive">
                    <table class="table table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:30px;"></th>
                                <th style="width:40px;" class="text-center">No</th>
                                <th>Nama Layer</th>
                                <th>Kategori</th>
                                <th>Tipe</th>
                                <th>Parent</th>
                                <th class="text-center">Fitur</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Warna</th>
                                <th class="text-center" style="width:120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="layersTbody">
                            @php
                                $rowNo = 1;

                                function renderLayerHierarchy($layers, $parentId = null, $level = 0, &$no = 1)
                                {
                                    $catIcons  = ['tematik'=>'mdi-map-outline','psd'=>'mdi-city','psn'=>'mdi-flag','pokir'=>'mdi-gavel','musrenbang'=>'mdi-forum'];
                                    $catLabels = ['tematik'=>'Peta Tematik','psd'=>'Proyek Strategis Daerah','psn'=>'Proyek Strategis Nasional','pokir'=>'Pokir DPRD','musrenbang'=>'Usulan Musrenbang'];
                                    $catColor  = ['tematik'=>'primary','psd'=>'success','psn'=>'danger','pokir'=>'warning','musrenbang'=>'info'];
                                    $typeData  = [
                                        'point'   => ['icon'=>'mdi-map-marker',    'color'=>'primary'],
                                        'line'    => ['icon'=>'mdi-vector-line',   'color'=>'warning'],
                                        'polygon' => ['icon'=>'mdi-vector-polygon','color'=>'success'],
                                    ];

                                    $rows   = $layers->where('parent_id', $parentId)->sortBy('name');
                                    $output = '';

                                    foreach ($rows as $layer) {
                                        $hasChildren = $layer->children_count > 0;
                                        $td          = $typeData[$layer->type]     ?? ['icon'=>'mdi-shape','color'=>'secondary'];
                                        $cc          = $catColor[$layer->category] ?? 'secondary';
                                        $ci          = $catIcons[$layer->category] ?? 'mdi-layers';
                                        $cl          = $catLabels[$layer->category] ?? $layer->category;
                                        $color       = $layer->style['color']      ?? '#3388ff';
                                        $parentName  = $layer->parent?->name ?? '—';
                                        $indentPx    = $level * 20;
                                        $isChild     = $level > 0;

                                        $rowClass = $isChild
                                            ? 'layer-row layer-child children-' . $parentId
                                            : 'layer-row layer-parent';

                                        $output .= '<tr class="' . $rowClass . '"'
                                               . ' data-id="' . $layer->id . '"'
                                               . ' data-parent-id="' . ($layer->parent_id ?? '') . '"'
                                               . ' data-level="' . $level . '"'
                                               . ($isChild ? ' style="display:none;"' : '') . '>';

                                        // Drag handle
                                        $output .= '<td class="drag-handle text-center" title="Seret untuk pindah"><i class="mdi mdi-drag text-muted"></i></td>';

                                        // No
                                        $output .= '<td class="text-center text-muted small">' . $no++ . '</td>';

                                        // Nama Layer
                                        $output .= '<td><div class="d-flex align-items-center" style="padding-left:' . $indentPx . 'px;">';
                                        if ($hasChildren) {
                                            $output .= '<button type="button"'
                                                . ' class="btn btn-link btn-sm p-0 me-2 btn-expand text-secondary"'
                                                . ' data-target="children-' . $layer->id . '"'
                                                . ' data-parent-id="' . $layer->id . '"'
                                                . ' title="Expand/Collapse">'
                                                . '<i class="mdi mdi-chevron-right fs-5"></i>'
                                                . '</button>';
                                        } else {
                                            $output .= '<span style="width:28px;display:inline-block;"></span>';
                                        }
                                        if ($isChild) {
                                            $output .= '<i class="mdi mdi-subdirectory-arrow-right text-success me-1"></i>';
                                        }
                                        $output .= '<div>';
                                        $fw = $isChild ? 'fw-normal' : 'fw-semibold';
                                        $output .= '<a href="' . route('layers.features.index', $layer) . '" class="text-dark text-decoration-none ' . $fw . '">' . e($layer->name) . '</a>';
                                        if ($hasChildren) {
                                            $output .= ' <span class="badge bg-secondary bg-opacity-25 text-secondary" style="font-size:.65em;">' . $layer->children_count . ' sub</span>';
                                        }
                                        $output .= '</div></div></td>';

                                        // Kategori
                                        $output .= '<td><span class="badge bg-' . $cc . '">' . '<i class="mdi ' . $ci . ' me-1"></i>' . $cl . '</span></td>';

                                        // Tipe
                                        $output .= '<td><span class="badge bg-' . $td['color'] . '"><i class="mdi ' . $td['icon'] . ' me-1"></i>' . ucfirst($layer->type) . '</span></td>';

                                        // Parent
                                        $output .= '<td>' . ($layer->parent_id ? '<span class="text-primary small">' . e($parentName) . '</span>' : '<span class="text-muted small">—</span>') . '</td>';

                                        // Fitur
                                        $output .= '<td class="text-center"><a href="' . route('layers.features.index', $layer) . '" class="badge bg-primary text-decoration-none"><i class="mdi mdi-map-marker-multiple me-1"></i>' . $layer->features_count . '</a></td>';

                                        // Status
                                        $output .= '<td class="text-center"><div class="form-check form-switch d-flex justify-content-center m-0"><input class="form-check-input toggle-active" type="checkbox" data-id="' . $layer->id . '" ' . ($layer->is_active ? 'checked' : '') . ' title="' . ($layer->is_active ? 'Aktif' : 'Nonaktif') . '"></div></td>';

                                        // Warna
                                        $output .= '<td class="text-center"><div class="d-flex align-items-center justify-content-center gap-1">'
                                               . '<span class="rounded" style="display:inline-block;width:20px;height:20px;background:' . $color . ';border:1px solid rgba(0,0,0,.15);" title="' . $color . '"></span>'
                                               . '<small class="text-muted d-none d-lg-inline">' . $color . '</small>'
                                               . '</div></td>';

                                        // Aksi
                                        $output .= '<td class="text-center"><div class="btn-group">'
                                               . '<a href="' . route('layers.features.index', $layer) . '" class="btn btn-sm btn-outline-primary" title="Lihat Fitur"><i class="mdi mdi-eye"></i></a>'
                                               . '<a href="' . route('layers.edit', $layer) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="mdi mdi-pencil"></i></a>'
                                               . '<button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="' . $layer->id . '" data-name="' . addslashes(e($layer->name)) . '" title="Hapus"><i class="mdi mdi-delete"></i></button>'
                                               . '</div></td>';

                                        $output .= '</tr>';

                                        if ($hasChildren && $level < 2) {
                                            $output .= renderLayerHierarchy($layers, $layer->id, $level + 1, $no);
                                        }
                                    }
                                    return $output;
                                }

                                echo renderLayerHierarchy($layers, null, 0, $rowNo);
                            @endphp

                            @if($layers->isEmpty())
                            <tr id="emptyRow">
                                <td colspan="10" class="text-center py-5">
                                    <i class="mdi mdi-layers-off mdi-48px text-muted d-block mb-3 opacity-50"></i>
                                    <h5 class="text-muted">Belum ada layer{{ $currentCategory ? ' untuk kategori ini' : '' }}</h5>
                                    <a href="{{ route('layers.create', array_filter(['category' => $currentCategory])) }}"
                                       class="btn btn-sm btn-primary mt-2">
                                        <i class="mdi mdi-plus me-1"></i>Tambah Layer Pertama
                                    </a>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="text-muted small" id="tableInfo"></div>
                    <nav><ul class="pagination pagination-sm mb-0" id="tablePagination"></ul></nav>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table th {
        background:#f8f9fa; border-bottom:2px solid #dee2e6;
        font-weight:600; text-transform:uppercase; font-size:.82rem;
        letter-spacing:.4px; color:#495057; padding:11px 8px;
    }
    .table td { vertical-align:middle; padding:10px 8px; border-bottom:1px solid #eef2f7; }
    .table tbody tr { transition:background .12s; }
    .table tbody tr:hover { background:rgba(0,123,255,.04); }
    .layer-parent { background:#fff; }
    .layer-child  { background:rgba(108,117,125,.055); }

    /* Drag handle */
    .drag-handle { width:28px; opacity:.35; cursor:grab; }
    .drag-handle:hover { opacity:.85; }
    tr.sortable-ghost { opacity:.4; background:#ddeeff !important; outline:2px dashed #007bff; }
    tr.sortable-drag  { background:#fff !important; box-shadow:0 4px 18px rgba(0,0,0,.18); }
    tr.drop-over      { background:#cce5ff !important; outline:2px solid #0056b3; }

    /* Expand button */
    .btn-expand { display:inline-flex; align-items:center; justify-content:center;
                  width:26px; height:26px; border-radius:50% !important;
                  transition:background .15s, transform .2s; }
    .btn-expand:hover { background:rgba(0,123,255,.1); }
    .btn-expand.expanded i { transform:rotate(90deg); color:#007bff !important; }
    .btn-expand i { transition:transform .2s; }

    /* Badges */
    .badge { font-size:.72rem; padding:4px 8px; border-radius:20px; font-weight:500; }
    .btn-group .btn { border-radius:6px!important; margin:0 1px; }

    /* Expand All button */
    .btn-expand-all {
        background:linear-gradient(135deg,#007bff,#0056b3);
        border:none; color:white; padding:5px 12px; border-radius:20px;
        font-size:.82rem; font-weight:500; transition:all .2s;
        box-shadow:0 2px 5px rgba(0,123,255,.25);
    }
    .btn-expand-all:hover  { background:linear-gradient(135deg,#0056b3,#004085); color:white; }
    .btn-expand-all.is-all-expanded { background:linear-gradient(135deg,#6c757d,#495057); }

    /* Pagination */
    .pagination .page-link { border-radius:6px!important; margin:0 2px; font-size:.8rem; padding:4px 9px; }
    .pagination .page-item.active .page-link {
        background:linear-gradient(135deg,#007bff,#0056b3); border-color:#007bff;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
$(function () {

/* ─── State ─── */
const CSRF      = '{{ csrf_token() }}';
const BASE_URL  = '/dashboard/layers/';
let searchTerm  = '';
let pageLen     = 25;
let curPage     = 1;
const expanded  = new Set();   // set of layer id strings yang sedang di-expand

/* ─── Helpers ─── */
function rootRows() {
    return Array.from(document.querySelectorAll('#layersTbody tr.layer-parent'));
}
function childrenOf(id) {
    return Array.from(document.querySelectorAll(`#layersTbody tr.children-${id}`));
}
function allDescendants(id) {
    const result = [];
    function collect(pid) {
        childrenOf(pid).forEach(c => { result.push(c); collect(c.dataset.id); });
    }
    collect(id);
    return result;
}
function rowMatchesSearch(row) {
    if (!searchTerm) return true;
    return row.textContent.toLowerCase().includes(searchTerm);
}
function anyDescendantMatches(id) {
    return allDescendants(id).some(d => rowMatchesSearch(d));
}

/* ─── Rekursif tampilkan children yang di-expand ─── */
function showExpandedChildren(parentId) {
    // Jika parent ini tidak di-expand, jangan tampilkan children-nya
    if (!expanded.has(String(parentId))) return;

    childrenOf(parentId).forEach(child => {
        if (!searchTerm || rowMatchesSearch(child) || anyDescendantMatches(child.dataset.id)) {
            child.style.display = '';
            // Rekursif untuk grandchildren
            showExpandedChildren(child.dataset.id);
        }
    });
}

/* ─── Render visibility ─────────────────────────────────
   Pagination hanya berlaku untuk root rows.
   Child/grandchild muncul secara rekursif jika di-expand.
─────────────────────────────────────────────────────── */
function render() {
    const allRoots = rootRows();

    // Filter root yang cocok search (atau punya descendant yang cocok)
    const matching = searchTerm
        ? allRoots.filter(r => rowMatchesSearch(r) || anyDescendantMatches(r.dataset.id))
        : allRoots;

    const total   = matching.length;
    const perPage = pageLen === -1 ? total : pageLen;
    const maxPage = Math.max(1, Math.ceil(total / perPage));
    curPage       = Math.min(curPage, maxPage);
    const start   = (curPage - 1) * perPage;
    const end     = Math.min(start + perPage, total);

    // Sembunyikan semua
    document.querySelectorAll('#layersTbody tr').forEach(r => r.style.display = 'none');

    // Tampilkan root halaman ini beserta descendant-nya yang di-expand
    matching.slice(start, end).forEach(root => {
        root.style.display = '';
        showExpandedChildren(root.dataset.id);
    });

    // Search aktif: auto-expand path ke descendant yang cocok
    if (searchTerm) {
        matching.slice(start, end).forEach(root => {
            autoExpandToMatch(root.dataset.id);
        });
    }

    renderInfo(total, start, end);
    renderPagination(total, perPage, maxPage);
}

/* Tampilkan dan expand path ke semua descendant yang cocok search */
function autoExpandToMatch(parentId) {
    childrenOf(parentId).forEach(child => {
        const childId = child.dataset.id;
        const matches = rowMatchesSearch(child) || anyDescendantMatches(childId);
        if (matches) {
            child.style.display = '';
            // Tandai expand button parent sebagai expanded (visual saja)
            const parentRow = document.querySelector(`#layersTbody tr[data-id="${parentId}"]`);
            if (parentRow) {
                const btn = parentRow.querySelector('.btn-expand');
                if (btn) btn.classList.add('expanded');
            }
            // Rekursif ke level berikutnya
            autoExpandToMatch(childId);
        }
    });
}

function renderInfo(total, start, end) {
    const info = document.getElementById('tableInfo');
    if (!info) return;
    if (total === 0) {
        info.textContent = 'Tidak ada layer ditemukan';
    } else {
        info.textContent = `Menampilkan ${start + 1}–${Math.min(end, total)} dari ${total} layer`;
    }
}

function renderPagination(total, perPage, maxPage) {
    const pag = document.getElementById('tablePagination');
    if (!pag) return;
    if (maxPage <= 1 || pageLen === -1) { pag.innerHTML = ''; return; }

    let h = '';
    const p = curPage;
    h += `<li class="page-item${p===1?' disabled':''}"><a class="page-link" href="#" data-p="1">«</a></li>`;
    h += `<li class="page-item${p===1?' disabled':''}"><a class="page-link" href="#" data-p="${p-1}">‹</a></li>`;
    const s = Math.max(1, p-2), e = Math.min(maxPage, p+2);
    for (let i = s; i <= e; i++) {
        h += `<li class="page-item${i===p?' active':''}"><a class="page-link" href="#" data-p="${i}">${i}</a></li>`;
    }
    h += `<li class="page-item${p===maxPage?' disabled':''}"><a class="page-link" href="#" data-p="${p+1}">›</a></li>`;
    h += `<li class="page-item${p===maxPage?' disabled':''}"><a class="page-link" href="#" data-p="${maxPage}">»</a></li>`;
    pag.innerHTML = h;

    pag.querySelectorAll('[data-p]').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const pg = parseInt(a.dataset.p);
            if (pg < 1 || pg > maxPage) return;
            curPage = pg;
            render();
        });
    });
}

/* ─── Init ─── */
render();

/* ─── Search ─── */
const searchInput = document.getElementById('layerSearch');
const clearBtn    = document.getElementById('clearSearch');
if (searchInput) {
    let timer;
    searchInput.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
            searchTerm = this.value.trim().toLowerCase();
            curPage    = 1;
            if (clearBtn) clearBtn.classList.toggle('d-none', !this.value);
            render();
        }, 300);
    });
}
if (clearBtn) {
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        searchTerm = '';
        clearBtn.classList.add('d-none');
        curPage = 1;
        render();
    });
}

/* ─── Per halaman ─── */
document.getElementById('pageLengthSelect').addEventListener('change', function () {
    pageLen = parseInt(this.value);
    curPage = 1;
    render();
});

/* ─── Expand / Collapse baris ─── */
$(document).on('click', '.btn-expand', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const id       = String(this.dataset.parentId);
    const isExpand = !expanded.has(id);

    if (isExpand) {
        expanded.add(id);
        this.classList.add('expanded');
    } else {
        // Collapse: hapus id ini DAN semua descendant dari expanded
        function collapseDescendants(pid) {
            expanded.delete(String(pid));
            childrenOf(pid).forEach(c => {
                const cid = c.dataset.id;
                collapseDescendants(cid);
                // Reset tombol expand anak-anak
                const btn = c.querySelector('.btn-expand');
                if (btn) btn.classList.remove('expanded');
            });
        }
        collapseDescendants(id);
        this.classList.remove('expanded');
    }
    render();
});

/* ─── Expand All / Collapse All ─── */
$('#btnExpandAll').on('click', function () {
    const allExpanded = this.classList.contains('is-all-expanded');

    if (allExpanded) {
        expanded.clear();
        document.querySelectorAll('.btn-expand').forEach(b => b.classList.remove('expanded'));
        this.classList.remove('is-all-expanded');
        this.querySelector('i').className = 'mdi mdi-chevron-down me-1';
        this.querySelector('span').textContent = 'Expand All';
    } else {
        // Expand semua level: root, child, grandchild
        document.querySelectorAll('.btn-expand').forEach(b => {
            expanded.add(String(b.dataset.parentId));
            b.classList.add('expanded');
        });
        this.classList.add('is-all-expanded');
        this.querySelector('i').className = 'mdi mdi-chevron-up me-1';
        this.querySelector('span').textContent = 'Collapse All';
    }
    render();
});

/* ─── Toggle Aktif AJAX ─── */
$(document).on('change', '.toggle-active', function () {
    const id = this.dataset.id;
    const cb = this;
    cb.disabled = true;
    fetch(`${BASE_URL}${id}/toggle-active`, {
        method : 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    }).then(r => r.json()).then(d => {
        if (!d.success) cb.checked = !cb.checked;
        cb.title = cb.checked ? 'Aktif' : 'Nonaktif';
    }).catch(() => { cb.checked = !cb.checked; })
      .finally(() => { cb.disabled = false; });
});

/* ─── Delete Single ─── */
$(document).on('click', '.btn-delete', function (e) {
    e.stopPropagation();
    const id   = $(this).data('id');
    const name = $(this).data('name');
    Swal.fire({
        title: 'Konfirmasi Hapus',
        html : `Hapus layer <strong>"${name}"</strong>?<br>
                <small class="text-muted">Semua fitur di dalamnya ikut terhapus dan tidak dapat dibatalkan.</small>`,
        icon : 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal', reverseButtons: true,
    }).then(r => {
        if (!r.isConfirmed) return;
        Swal.fire({ title:'Menghapus...', allowOutsideClick:false, showConfirmButton:false, didOpen:()=>Swal.showLoading() });
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${BASE_URL}${id}`;
        form.innerHTML = `<input type="hidden" name="_token" value="${CSRF}">
                          <input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(form);
        form.submit();
    });
});

/* ─── Drag & Drop (SortableJS) ─── */
let dropTarget = null;

Sortable.create(document.getElementById('layersTbody'), {
    handle     : '.drag-handle',
    animation  : 150,
    ghostClass : 'sortable-ghost',
    dragClass  : 'sortable-drag',

    onMove(evt) {
        if (dropTarget) { dropTarget.classList.remove('drop-over'); dropTarget = null; }
        const related = evt.related;
        if (related && related.dataset.id && related !== evt.dragged) {
            related.classList.add('drop-over');
            dropTarget = related;
        }
        return true;
    },

    onEnd(evt) {
        const dragged     = evt.item;
        const draggedId   = dragged.dataset.id;
        const targetId    = dropTarget?.dataset.id;
        const oldParentId = dragged.dataset.parentId || null;

        if (dropTarget) { dropTarget.classList.remove('drop-over'); }

        const newParentId = (targetId && targetId !== draggedId) ? parseInt(targetId) : null;
        dropTarget = null;

        // Tidak ada perubahan
        if ((newParentId === null && !oldParentId) ||
            (newParentId && String(newParentId) === oldParentId)) {
            return;
        }

        Swal.fire({
            title: newParentId ? 'Jadikan Sub-Layer?' : 'Jadikan Layer Utama?',
            html: newParentId
                ? 'Layer akan dipindahkan sebagai sub-layer dari layer yang dipilih.'
                : 'Layer akan dipindahkan menjadi layer utama (tanpa parent).',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Pindahkan',
            cancelButtonText : 'Batal',
        }).then(r => {
            if (!r.isConfirmed) { location.reload(); return; }
            Swal.fire({ title:'Memindahkan...', allowOutsideClick:false, showConfirmButton:false, didOpen:()=>Swal.showLoading() });
            fetch(`${BASE_URL}${draggedId}/move`, {
                method : 'PATCH',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body   : JSON.stringify({ parent_id: newParentId }),
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    Swal.fire({ icon:'success', title:'Berhasil!', timer:900, showConfirmButton:false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon:'error', title:'Gagal', text: d.message || 'Terjadi kesalahan.' });
                    location.reload();
                }
            }).catch(() => location.reload());
        });
    },
});

/* ─── Auto-hide flash ─── */
setTimeout(() => $('.alert-success, .alert-danger').not('#bulkActionBar').fadeOut(600), 5000);

});
</script>
@endpush
