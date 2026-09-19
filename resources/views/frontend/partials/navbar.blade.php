<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="/">
            <img width="40" src="{{ asset('frontend/img/logo/logo-dark.png') }}" alt="Logo Bappeda" />
            {{-- <span>MARIMOI</span> --}}
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
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.prioritas') ? 'active' : '' }}" href="{{ route('tampil.prioritas') }}">
                        <span class="nav-text">Prioritas Daerah 2025-2029</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tampil.aspirasi') ? 'active' : '' }}" href="{{ route('tampil.aspirasi') }}"><span class="nav-text">Usulan Aspirasi</span></a>
                </li>
            </ul>
        </div>
    </div>
</nav>
