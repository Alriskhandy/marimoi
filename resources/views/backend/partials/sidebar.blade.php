<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <li class="nav-item nav-profile">
            <a href="#!" class="nav-link">
                <div class="nav-profile-image">
                    <img src="{{ asset('backend/assets/images/faces/face1.jpg') }}" alt="profile" />
                    <span class="login-status online"></span>
                </div>
                <div class="nav-profile-text d-flex flex-column">
                    <span class="font-weight-bold mb-2">Admin GIS</span>
                    <span class="text-secondary text-small">WebGIS MARIMOI</span>
                </div>
                <i class="mdi mdi-map-marker text-success nav-profile-badge"></i>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('dashboard') }}">
                <span class="menu-title">Dashboard</span>
                <i class="mdi mdi-view-dashboard menu-icon"></i>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('lokasi.peta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('lokasi.peta') }}">
                <span class="menu-title">Peta Interaktif</span>
                <i class="mdi mdi-map menu-icon"></i>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('lokasi.*') && !request()->routeIs('lokasi.peta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('lokasi.index') }}">
                <span class="menu-title">Data Spasial</span>
                <i class="mdi mdi-database menu-icon"></i>
            </a>
        </li>


        @php
            $currentRoute = request()->route()->getName();
            $isKategoriLayerActive = str_contains($currentRoute, 'kategori-layers');
        @endphp

        <li class="nav-item {{ $isKategoriLayerActive ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('kategori-layers.index') }}">
                <span class="menu-title">Kategori Layer</span>
                <i class="mdi mdi-layers menu-icon"></i>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="#!">
                <span class="menu-title">Manajemen Pengguna</span>
                <i class="mdi mdi-account-multiple menu-icon"></i>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="#!">
                <span class="menu-title">Pengaturan Sistem</span>
                <i class="mdi mdi-settings menu-icon"></i>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="#!">
                <span class="menu-title">Keluar</span>
                <i class="mdi mdi-logout menu-icon"></i>
            </a>
        </li>
    </ul>
</nav>
