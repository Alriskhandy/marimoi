<header id="header" class="header d-flex align-items-center shadow-lg border-bottom border-dark">
    <div class="container-fluid container-xl d-flex align-items-center py-2 py-md-0">

        <a href="{{ route('beranda') }}" class="logo d-flex align-items-center me-auto">
            <!-- Uncomment the line below if you also wish to use an image logo -->
            <img src="{{ asset('frontend/img/logo.svg') }}" alt="Logo Bappeda" style="height: 24px; margin-right: 8px;">
            <h1 class="sitename fs-5 fs-md-1">MARIMOI</h1>
        </a>

        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="{{ route('beranda') }}"
                        class="{{ request()->routeIs('beranda') ? 'active' : '' }}">Beranda</a></li>
                <li><a href="{{ route('tampil.psd') }}"
                        class="{{ request()->routeIs('tampil.psd') ? 'active' : '' }}">Proyek Strategis Daerah</a></li>
                <li><a href="{{ route('tampil.psn') }}"
                        class="{{ request()->routeIs('tampil.psn') ? 'active' : '' }}">Proyek Strategis Nasional</a>
                </li>
                <li><a href="{{ route('tampil.rpjmd') }}"
                        class="{{ request()->routeIs('tampil.rpjmd') ? 'active' : '' }}">Peta RPJMD</a></li>
                <li><a href="{{ route('tampil.peta') }}"
                        class="{{ request()->routeIs('tampil.peta') ? 'active' : '' }}">Usulan Musrenbang</a></li>
                <li><a href="{{ route('tampil.pokir') }}"
                        class="{{ request()->routeIs('tampil.pokir') ? 'active' : '' }}">POKIR DPRD</a></li>
                <li><a href="{{ route('tampil.peta') }}"
                        class="{{ request()->routeIs('tampil.peta') ? 'active' : '' }}">Tanggapan Masyarakat</a></li>
            </ul>
            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>

        <a class="btn-getstarted" href="{{ route('login') }}">Login</a>

    </div>
</header>
