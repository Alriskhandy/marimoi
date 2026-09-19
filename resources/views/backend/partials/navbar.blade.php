@php
    $navUser = auth()->user();
    $navAvatar =
        $navUser?->role?->slug === 'admin-opd' && $navUser->opd && $navUser->opd->logo
            ? asset('storage/' . $navUser->opd->logo)
            : asset('backend_baru/assets/images/avatar/avatar-fallback.jpg');
@endphp
<nav class="navbar admin-navbar navbar-expand bg-white">
    <div class="container-fluid px-3 px-lg-4">
        <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="adminSidebar"
            aria-expanded="true" aria-label="Toggle sidebar">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <a href="{{ url('/') }}" target="_blank" class="btn btn-sm btn-outline-primary ms-3">
            <i class="bi bi-house" aria-hidden="true"></i> <span class="d-none d-sm-inline">Halaman Depan</span>
        </a>

        <div class="navbar-actions ms-auto">
            <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Ganti tema"
                title="Ganti tema">
                <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
            </button>

            <div class="dropdown">
                <button class="profile-button dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <img class="avatar-img avatar-sm" src="{{ $navAvatar }}" alt="{{ $navUser?->name }}">
                    <span class="profile-name d-none d-sm-inline">{{ $navUser?->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Sign out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
