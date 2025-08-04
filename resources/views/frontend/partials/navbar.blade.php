<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img width="40" src="{{ asset('frontend/img/logo.webp') }}" alt="Logo Bappeda" />MARIMOI
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('beranda') ? 'active' : '' }}"
                        href="{{ route('beranda') }}">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.tematik') ? 'active' : '' }}" href="{{ route('tampil.tematik') }}">Peta Tematik</a>
                </li>
                <!-- Dropdown Start -->
                <li class="nav-item dropdown">
                    @php
                        $isDropdownActive = request()->routeIs('tampil.psd') || request()->routeIs('tampil.psn');
                    @endphp
                    <a class="nav-link dropdown-toggle {{ $isDropdownActive ? 'active' : '' }}" href="#"
                        id="dropdownProyek" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-haspopup="true">
                        <span class="d-flex align-items-center">
                            Proyek Strategis
                            <i class="fa fa-chevron-down ms-2"></i>
                        </span>
                    </a>
                    <ul class="dropdown-menu animate slideIn" aria-labelledby="dropdownProyek">
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('tampil.psd') ? 'active' : '' }}"
                                href="{{ route('tampil.psd') }}">
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('frontend/img/daerah.svg') }}" alt="Proyek Strategis Daerah"
                                        class="me-3" width="24" height="24" />
                                    <div>
                                        <div class="fw-medium">Proyek Strategis Daerah</div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('tampil.psn') ? 'active' : '' }}"
                                href="{{ route('tampil.psn') }}">
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('frontend/img/nasional.svg') }}" alt="Proyek Strategis Nasional"
                                        class="me-3" width="24" height="24" />
                                    <div>
                                        <div class="fw-medium">Proyek Strategis Nasional</div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- Dropdown End -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.prioritas') ? 'active' : '' }}" href="{{ route('tampil.prioritas') }}">Prioritas Daerah 2025-2029</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.musrenbang') ? 'active' : '' }}" href="{{ route('tampil.musrenbang') }}">Musrenbang</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.pokir') ? 'active' : '' }}" href="{{ route('tampil.pokir') }}">Pokir DPRD</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.aspirasi') ? 'active' : '' }}" href="{{ route('tampil.aspirasi') }}">Usulan Aspirasi</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
