<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="/">
            <img width="40" src="{{ asset('frontend/img/logo.webp') }}" alt="Logo Bappeda" />
            <span>MARIMOI</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse mobile-nav-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mobile-nav">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('beranda') ? 'active' : '' }}"
                        href="{{ route('beranda') }}"><span class="nav-text">Beranda</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.tematik') ? 'active' : '' }}" href="{{ route('tampil.tematik') }}"><span class="nav-text">Peta Tematik</span></a>
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
                            <span class="nav-text">Proyek Strategis</span>
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
                    <a class="nav-link {{ request()->routeIs('tampil.prioritas') ? 'active' : '' }}" href="{{ route('tampil.prioritas') }}">
                        <span class="nav-text">Prioritas Daerah 2025-2029</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.musrenbang') ? 'active' : '' }}" href="{{ route('tampil.musrenbang') }}"><span class="nav-text">Musrenbang</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.pokir') ? 'active' : '' }}" href="{{ route('tampil.pokir') }}"><span class="nav-text">Pokir DPRD</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.aspirasi') ? 'active' : '' }}" href="{{ route('tampil.aspirasi') }}"><span class="nav-text">Usulan Aspirasi</span></a>
                </li>
            </ul>
        </div>
    </div>
</nav>
