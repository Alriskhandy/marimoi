<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="{{ route('beranda') }}" class="logo d-flex align-items-center me-auto">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <img src="{{ asset('frontend/img/logo.svg') }}" alt="Logo Bappeda" class="w-12 h-12" style="height: 40px; margin-right: 8px;">
        <h1 class="sitename">MARIMOI</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="{{ route('beranda') }}" class="{{ request()->routeIs('beranda') ? 'active' : '' }}">Beranda</a></li>
          <li><a href="{{ route('tampil.peta') }}" class="{{ request()->routeIs('tampil.peta') ? 'active' : '' }}">Peta</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <a class="btn-getstarted" href="{{ route('login') }}">Login</a>

    </div>
  </header>
