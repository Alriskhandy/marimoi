@php
    $user = auth()->user();
    $slug = $user?->role?->slug;
    $isSuperAdmin = $slug === 'super-admin';

    $isPetaTematikActive =
        request()->routeIs('tematik.*') ||
        request()->routeIs('data-spatial.map') ||
        (request()->routeIs('data-spatial.*') && request()->get('type') === 'tematik') ||
        (request()->routeIs('categories.*') && request()->get('type') == 'tematik') ||
        request()->routeIs('kategori-tematik.*') ||
        (request()->routeIs('project-feedbacks.*') && request()->get('type') === 'tematik');
    $isDokumenActive = request()->routeIs('dokumen.*');
    $isAspirasiMenuActive =
        request()->routeIs('aspirasi.*') || request()->routeIs('opd.*') || request()->routeIs('kategori-aspirasi.*');
    $isPublicationActive = request()->routeIs('publications.*');
    $isSystemActive =
        request()->routeIs('users.*') ||
        request()->routeIs('roles.*') ||
        request()->routeIs('settings.*') ||
        request()->routeIs('opd.*') ||
        request()->routeIs('visitors.*') ||
        request()->routeIs('logs.*');

    $canPetaTematik = $user?->canAny(['data-spatial.view', 'categories.view', 'project-feedbacks.view']);
    $canAspirasi = $user?->canAny(['aspirasi.view', 'kategori-aspirasi.view']);
    $canPublikasi = $user?->can('publications.view');
    $canSistem = $user?->canAny(['users.view', 'roles.view', 'opd.view', 'visitors.view', 'logs.view']);

    $hasOpdLogo = $slug === 'admin-opd' && $user?->opd && $user->opd->logo;
    $logoPath = $user->opd?->logo ? storage_path('app/public/' . $user->opd->logo) : null;

    $sidebarAvatar =
        $hasOpdLogo && $logoPath && file_exists($logoPath) && is_readable($logoPath)
            ? asset('storage/' . $user->opd->logo)
            : asset('backend_baru/assets/images/avatar/avatar-fallback.jpg');
@endphp
<aside class="admin-sidebar" id="adminSidebar" aria-label="Navigasi utama">
    <div class="sidebar-header">
        <a class="brand-mark" href="{{ route('dashboard') }}" aria-label="MARIMOI dashboard">
            <img width="40" class="brand-logo brand-logo-light" src="{{ asset('frontend/img/logo/logo-dark.png') }}"
                alt="">
            <img width="40" class="brand-logo brand-logo-dark" src="{{ asset('frontend/img/logo/logo-white.png') }}"
                alt="">
            <span class="brand-copy">
                <span class="brand-title">MARIMOI</span>
                <span class="brand-subtitle">Bappeda Maluku Utara</span>
            </span>
        </a>
    </div>

    <nav class="sidebar-nav">
        @can('dashboard.view')
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
                <span class="nav-text">Dashboard</span>
            </a>
        @endcan

        @if ($canPetaTematik || $canAspirasi || ($slug != 'admin-opd' && $user?->can('dokumen.view')))
            <div class="nav-section-label">Master Data</div>
        @endif

        {{-- Peta Tematik --}}
        @if ($canPetaTematik)
        <a class="nav-link {{ $isPetaTematikActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#petaTematikMenu"
            role="button" aria-expanded="{{ $isPetaTematikActive ? 'true' : 'false' }}"
            aria-controls="petaTematikMenu">
            <span class="nav-icon"><i class="bi bi-map" aria-hidden="true"></i></span>
            <span class="nav-text">Peta Tematik</span>
            <i class="bi bi-chevron-down nav-caret" aria-hidden="true"></i>
        </a>
        <div class="collapse {{ $isPetaTematikActive ? 'show' : '' }}" id="petaTematikMenu">
            <div class="sidebar-submenu">
                @can('data-spatial.view')
<a class="nav-link {{ request()->routeIs('data-spatial.*') && request()->get('type') === 'tematik' ? 'active' : '' }}"
                    href="{{ route('data-spatial.index', ['type' => 'tematik']) }}">
                    <span class="nav-text">Data Peta Tematik</span>
                </a>
@endcan
                @can('data-spatial.view')
<a class="nav-link {{ request()->routeIs('data-spatial.map') ? 'active' : '' }}"
                    href="{{ route('data-spatial.map') }}">
                    <span class="nav-text">Tampilan Peta</span>
                </a>
@endcan
                @can('categories.view')
<a class="nav-link {{ (request()->routeIs('categories.*') && request()->get('type') == 'tematik') || request()->routeIs('kategori-tematik.*') ? 'active' : '' }}"
                    href="{{ route('categories.index', ['type' => 'tematik']) }}">
                    <span class="nav-text">Kategori Peta Tematik</span>
                </a>
@endcan
                @can('project-feedbacks.view')
<a class="nav-link {{ request()->routeIs('project-feedbacks.*') && request()->get('type') === 'tematik' ? 'active' : '' }}"
                    href="{{ route('project-feedbacks.index', ['type' => 'tematik']) }}">
                    <span class="nav-text">Feedback Peta Tematik</span>
                </a>
@endcan
            </div>
        </div>

        @endif

        {{-- Pembangunan --}}
        @can('project-progress.view')
            <div class="nav-section-label">Pembangunan</div>
            <a class="nav-link {{ request()->routeIs('dashboard.pembangunan') ? 'active' : '' }}"
                href="{{ route('dashboard.pembangunan') }}">
                <span class="nav-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
                <span class="nav-text">Dashboard Pembangunan</span>
            </a>
            <a class="nav-link {{ request()->routeIs('project-progress.*') ? 'active' : '' }}"
                href="{{ route('project-progress.index') }}">
                <span class="nav-icon"><i class="bi bi-clipboard-data" aria-hidden="true"></i></span>
                <span class="nav-text">Progres Proyek Strategis</span>
            </a>
        @endcan

        {{-- Upload Dokumen --}}
        @if ($slug != 'admin-opd' && Route::has('dokumen.index') && $user?->can('dokumen.view'))
            <a class="nav-link {{ $isDokumenActive ? 'active' : '' }}" href="{{ route('dokumen.index') }}">
                <span class="nav-icon"><i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i></span>
                <span class="nav-text">Upload Dokumen</span>
            </a>
        @endif

        {{-- Aspirasi --}}
        @if ($canAspirasi)
        <a class="nav-link {{ $isAspirasiMenuActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#aspirasiMenu"
            role="button" aria-expanded="{{ $isAspirasiMenuActive ? 'true' : 'false' }}" aria-controls="aspirasiMenu">
            <span class="nav-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
            <span class="nav-text">Aspirasi</span>
            <i class="bi bi-chevron-down nav-caret" aria-hidden="true"></i>
        </a>
        <div class="collapse {{ $isAspirasiMenuActive ? 'show' : '' }}" id="aspirasiMenu">
            <div class="sidebar-submenu">
                @can('aspirasi.view')
<a class="nav-link {{ request()->routeIs('aspirasi.*') ? 'active' : '' }}"
                    href="{{ route('aspirasi.index') }}">
                    <span class="nav-text">Data Aspirasi</span>
                </a>
@endcan
                @can('kategori-aspirasi.view')
                    <a class="nav-link {{ request()->routeIs('kategori-aspirasi.*') ? 'active' : '' }}"
                        href="{{ route('kategori-aspirasi.index') }}">
                        <span class="nav-text">Kategori Aspirasi</span>
                    </a>
                @endcan
            </div>
        </div>
        @endif

        @if ($canPublikasi || $canSistem)
            <div class="nav-section-label">Sistem</div>
        @endif

        @if ($canPublikasi)
            {{-- Publikasi --}}
            <a class="nav-link {{ $isPublicationActive ? 'active' : '' }}" data-bs-toggle="collapse"
                href="#publicationMenu" role="button" aria-expanded="{{ $isPublicationActive ? 'true' : 'false' }}"
                aria-controls="publicationMenu">
                <span class="nav-icon"><i class="bi bi-book" aria-hidden="true"></i></span>
                <span class="nav-text">Manajemen Publikasi</span>
                <i class="bi bi-chevron-down nav-caret" aria-hidden="true"></i>
            </a>
            <div class="collapse {{ $isPublicationActive ? 'show' : '' }}" id="publicationMenu">
                <div class="sidebar-submenu">
                    <a class="nav-link {{ request()->routeIs('publications.index') ? 'active' : '' }}"
                        href="{{ route('publications.index') }}">
                        <span class="nav-text">Daftar Publikasi</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('publications.downloads.*') ? 'active' : '' }}"
                        href="{{ route('publications.downloads.index') }}">
                        <span class="nav-text">Data Download</span>
                    </a>
                </div>
            </div>

        @endif

        @if ($canSistem)
            {{-- Sistem & Pengguna --}}
            <a class="nav-link {{ $isSystemActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#systemMenu"
                role="button" aria-expanded="{{ $isSystemActive ? 'true' : 'false' }}" aria-controls="systemMenu">
                <span class="nav-icon"><i class="bi bi-gear" aria-hidden="true"></i></span>
                <span class="nav-text">Sistem & Pengguna</span>
                <i class="bi bi-chevron-down nav-caret" aria-hidden="true"></i>
            </a>
            <div class="collapse {{ $isSystemActive ? 'show' : '' }}" id="systemMenu">
                <div class="sidebar-submenu">
                    @can('users.view')<a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                        href="{{ route('users.index') }}"><span class="nav-text">Manajemen Pengguna</span></a>@endcan
                    @can('roles.view')<a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"
                        href="{{ route('roles.index') }}"><span class="nav-text">Manajemen Role</span></a>@endcan
                    @can('opd.view')<a class="nav-link {{ request()->routeIs('opd.*') ? 'active' : '' }}"
                        href="{{ route('opd.index') }}"><span class="nav-text">Manajemen OPD</span></a>@endcan
                    @can('visitors.view')<a class="nav-link {{ request()->routeIs('visitors.*') ? 'active' : '' }}"
                        href="{{ route('visitors.index') }}"><span class="nav-text">Analisis Pengunjung</span></a>@endcan
                    @can('logs.view')<a class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}"
                        href="{{ route('logs.index') }}"><span class="nav-text">Log Sistem</span></a>@endcan
                </div>
            </div>
        @endif
    </nav>

    @if ($user)
        <div class="sidebar-user">
            <img class="avatar-img avatar-md sidebar-user-avatar" src="{{ $sidebarAvatar }}"
                alt="{{ $user->name }}">
            <strong>{{ $slug === 'admin-opd' && $user->opd ? $user->opd->singkatan : $user->name }}</strong>
            <small>{{ $user->role?->name }}</small>
        </div>
    @endif

    <div class="sidebar-footer">
        <span class="status-dot"></span>
        <span class="sidebar-footer-text">Sistem berjalan normal</span>
    </div>
</aside>
