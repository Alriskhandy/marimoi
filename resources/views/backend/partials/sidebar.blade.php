<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <li class="nav-item nav-profile">
            <a href="#!" class="nav-link">
                <div class="nav-profile-image">
                    <img src="{{ asset('backend/assets/images/faces/face1.jpg') }}" alt="profile" />
                    <span class="login-status online"></span>
                </div>
                <div class="nav-profile-text d-flex flex-column">
                    <span class="font-weight-bold mb-2">{{ Auth::user()->name }}</span>
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

        @php
            $isPetaTematikActive = request()->routeIs('lokasi.index') || request()->routeIs('kategori-layers.index');
        @endphp

        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#petaTematikMenu"
                aria-expanded="{{ $isPetaTematikActive ? 'true' : 'false' }}" aria-controls="petaTematikMenu">
                <span class="menu-title">Data Peta Tematik</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-map menu-icon"></i>
            </a>

            <div class="collapse {{ $isPetaTematikActive ? 'show' : '' }}" id="petaTematikMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item {{ request()->routeIs('lokasi.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('lokasi.index') }}">Data Peta Tematik</a>
                    </li>
                    <li class="nav-item {{ request()->routeIs('kategori-layers.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('kategori-layers.index') }}">Kategori Peta Tematik</a>
                    </li>
                </ul>
            </div>
        </li>
        @php
            $isPSDActive = request()->routeIs('lokasi.index');
        @endphp

        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#psdMenu"
                aria-expanded="{{ $isPSDActive ? 'true' : 'false' }}" aria-controls="psdMenu">
                <span class="menu-title">Proyek Strategis Daerah</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-city menu-icon"></i>
            </a>

            <div class="collapse {{ $isPSDActive ? 'show' : '' }}" id="psdMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item {{ request()->routeIs('lokasi.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('lokasi.index') }}">Tahun 2025</a>
                        <a class="nav-link" href="{{ route('lokasi.index') }}">Tahun 2024</a>
                        <a class="nav-link" href="{{ route('lokasi.index') }}">Tahun 2023</a>
                    </li>
                    <li class="nav-item {{ request()->routeIs('lokasi.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('lokasi.index') }}">Kategori Proyek Daerah</a>
                    </li>
                </ul>
            </div>
        </li>
        @php
            $isPSNActive = request()->routeIs('lokasi.index');
        @endphp

        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#psnMenu"
                aria-expanded="{{ $isPSNActive ? 'true' : 'false' }}" aria-controls="psnMenu">
                <span class="menu-title">Proyek Strategis RPJMD</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-domain menu-icon"></i>
            </a>

            <div class="collapse {{ $isPSNActive ? 'show' : '' }}" id="psnMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item {{ request()->routeIs('lokasi.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('lokasi.index') }}">Data Proyek RPJMD</a>
                    </li>
                    <li class="nav-item {{ request()->routeIs('lokasi.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('lokasi.index') }}">Kategori Proyek RPJMD</a>
                    </li>
                </ul>
            </div>
        </li>


        <li class="nav-item">
            <a class="nav-link" href="{{ route('project-feedbacks.index') }}">
                <span class="menu-title">Tanggapan Masyarakat</span>
                <i class="mdi mdi-account-multiple menu-icon"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('project-feedbacks.index') }}">
                <span class="menu-title">Ulasan Musrembang</span>
                <i class="mdi mdi-account-multiple menu-icon"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#!">
                <span class="menu-title">Ulasan Tentang Website</span>
                <i class="mdi mdi-comment-multiple-outline menu-icon"></i>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="{{ route('cooming_soon') }}">
                <span class="menu-title">DESK MUSREMBANG</span>
                <i class="mdi mdi-city-variant-outline menu-icon"></i>
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
