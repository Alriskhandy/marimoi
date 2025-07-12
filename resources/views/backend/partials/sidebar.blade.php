<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <!-- User Profile -->
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

        <!-- Dashboard -->
        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('dashboard') }}">
                <span class="menu-title">Dashboard</span>
                <i class="mdi mdi-view-dashboard menu-icon"></i>
            </a>
        </li>

        <!-- Peta Interaktif -->
        <li class="nav-item {{ request()->routeIs('lokasi.peta') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('lokasi.peta') }}">
                <span class="menu-title">Peta Interaktif</span>
                <i class="mdi mdi-map menu-icon"></i>
            </a>
        </li>

        <!-- Data Peta Tematik -->
        @php
            $isPetaTematikActive = request()->routeIs('lokasi.index') || request()->routeIs('kategori-layers.index');
        @endphp
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#petaTematikMenu"
                aria-expanded="{{ $isPetaTematikActive ? 'true' : 'false' }}" aria-controls="petaTematikMenu">
                <span class="menu-title">Data Peta Tematik</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-layers menu-icon"></i>
            </a>
            <div class="collapse {{ $isPetaTematikActive ? 'show' : '' }}" id="petaTematikMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item {{ request()->routeIs('lokasi.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('lokasi.index') }}">
                            <i class="mdi mdi-map-outline me-2"></i>Data Peta Tematik
                        </a>
                    </li>
                    <li class="nav-item {{ request()->routeIs('kategori-layers.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('kategori-layers.index') }}">
                            <i class="mdi mdi-format-list-bulleted me-2"></i>Kategori Peta Tematik
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Proyek Strategis Daerah -->
        @php
            $isPSDActive = request()->routeIs('psd.*');
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
                    <li class="nav-item">
                        <h6 class="sub-menu-header">Data per Tahun</h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-calendar me-2"></i>Tahun 2025
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-calendar me-2"></i>Tahun 2024
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-calendar me-2"></i>Tahun 2023
                        </a>
                    </li>
                    <li class="nav-item">
                        <hr class="dropdown-divider">
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-tag-multiple me-2"></i>Kategori Proyek Daerah
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Proyek Strategis Nasional -->
        @php
            $isPSNActive = request()->routeIs('psn.*');
        @endphp
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#psnMenu"
                aria-expanded="{{ $isPSNActive ? 'true' : 'false' }}" aria-controls="psnMenu">
                <span class="menu-title">Proyek Strategis Nasional</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-flag menu-icon"></i>
            </a>
            <div class="collapse {{ $isPSNActive ? 'show' : '' }}" id="psnMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <h6 class="sub-menu-header">Data per Tahun</h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-calendar me-2"></i>Tahun 2025
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-calendar me-2"></i>Tahun 2024
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-calendar me-2"></i>Tahun 2023
                        </a>
                    </li>
                    <li class="nav-item">
                        <hr class="dropdown-divider">
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-tag-multiple me-2"></i>Kategori Proyek Nasional
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Proyek Strategis RPJMD -->
        @php
            $isRPJMDActive = request()->routeIs('rpjmd.*');
        @endphp
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#rpjmdMenu"
                aria-expanded="{{ $isRPJMDActive ? 'true' : 'false' }}" aria-controls="rpjmdMenu">
                <span class="menu-title">PETA RPJMD</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-domain menu-icon"></i>
            </a>
            <div class="collapse {{ $isRPJMDActive ? 'show' : '' }}" id="rpjmdMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-database me-2"></i>Data Peta RPJMD
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-tag-multiple me-2"></i>Kategori Peta RPJMD
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- POKIR DPRD -->
        @php
            $isPOKIRActive = request()->routeIs('pokir.*');
        @endphp
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#pokirMenu"
                aria-expanded="{{ $isPOKIRActive ? 'true' : 'false' }}" aria-controls="pokirMenu">
                <span class="menu-title">Pokir DPRD</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-account-group menu-icon"></i>
            </a>
            <div class="collapse {{ $isPOKIRActive ? 'show' : '' }}" id="pokirMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-database me-2"></i>Data Pokir DPRD
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-tag-multiple me-2"></i>Kategori Pokir
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-comment-text me-2"></i>Ulasan Pokir DPRD
                        </a>
                    </li>
                </ul>
            </div>
        </li>


        <!-- Usulan Musrembang (Single Menu) -->
        <li class="nav-item {{ request()->routeIs('musrembang.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('cooming_soon') }}">
                <span class="menu-title">Usulan Musrembang</span>
                <i class="mdi mdi-forum menu-icon"></i>
                <span class="badge badge-warning badge-sm ms-auto">Soon</span>
            </a>
        </li>
        <!-- Desk Musrenbang (Coming Soon) -->
        <li class="nav-item">
            <a class="nav-link" href="{{ route('cooming_soon') }}">
                <span class="menu-title">Desk Forum PD</span>
                <i class="mdi mdi-city-variant-outline menu-icon"></i>
                <span class="badge badge-warning badge-sm ms-auto">Soon</span>
            </a>
        </li>

        <!-- Partisipasi Masyarakat -->
        @php
            $isPartisipasiActive = request()->routeIs('project-feedbacks.*') || request()->routeIs('Musrenbang.*');
        @endphp
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#partisipasiMenu"
                aria-expanded="{{ $isPartisipasiActive ? 'true' : 'false' }}" aria-controls="partisipasiMenu">
                <span class="menu-title">Partisipasi Masyarakat</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-account-heart menu-icon"></i>
            </a>
            <div class="collapse {{ $isPartisipasiActive ? 'show' : '' }}" id="partisipasiMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item {{ request()->routeIs('project-feedbacks.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('project-feedbacks.index') }}">
                            <i class="mdi mdi-comment-multiple me-2"></i>Tanggapan Masyarakat
                        </a>
                    </li>

                </ul>
            </div>
        </li>
        <!-- Divider -->
        <li class="nav-item nav-category">
            <span class="nav-link">Sistem</span>
        </li>

        <!-- Sistem & Pengguna -->
        @php
            $isSystemActive = request()->routeIs('users.*') || request()->routeIs('settings.*');
        @endphp
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#systemMenu"
                aria-expanded="{{ $isSystemActive ? 'true' : 'false' }}" aria-controls="systemMenu">
                <span class="menu-title">Sistem & Pengguna</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-cog-outline menu-icon"></i>
            </a>
            <div class="collapse {{ $isSystemActive ? 'show' : '' }}" id="systemMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-account-multiple me-2"></i>Manajemen Pengguna
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-settings me-2"></i>Pengaturan Sistem
                        </a>
                    </li>


                </ul>
            </div>
        </li>
    </ul>
</nav>
