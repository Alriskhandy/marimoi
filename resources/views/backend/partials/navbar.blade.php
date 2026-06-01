<nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
        <a class="navbar-brand brand-logo" href="{{ route('dashboard') }}">
            <h2>MARIMOI</h2>
        </a>

        {{-- <h1 class="fw-bold fs-4 d-none d-md-inline">MARIMOI</h1> --}}

        <a class="navbar-brand brand-logo-mini" href="{{ route('dashboard') }}">
            <img src="{{ asset('frontend/img/logo/logo-dark.png') }}" alt="logo" style="height: 28px; width: auto;" />
        </a>
    </div>


    <div class="navbar-menu-wrapper d-flex align-items-stretch">
        <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="mdi mdi-menu"></span>
        </button>
        <!-- Tombol ke Halaman Depan -->
        <ul class="navbar-nav me-auto ms-3">
            <li class="nav-item">
                <a href="{{ url('/') }}" target="_blank" class="btn btn-sm btn-primary mt-2">
                    <i class="mdi mdi-home"></i> Halaman Depan
                </a>
            </li>
        </ul>

        <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item nav-profile dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    @php
                        $user = auth()->user();
                        $slug = $user->role->slug;
                    @endphp
                    @if (auth()->check() && $user->role)

                        @if ($slug === 'admin-opd' && $user->opd && $user->opd->logo)
                            <div class="nav-profile-img">
                                <img src="{{ asset('storage/' . $user->opd->logo) }}"
                                    alt="Logo {{ $user->opd->singkatan }}" class="rounded logo-img"
                                    style="width: 40px; height: 40px; object-fit: contain; ">
                                <span class="availability-status online"></span>
                            </div>
                        @else
                            <div class="nav-profile-img">
                                <img src="{{ asset('backend/assets/images/faces/profile.png') }}" alt="profile" />
                                <span class="availability-status online"></span>
                            </div>
                        @endif
                    @endif
                    <div class="nav-profile-text">
                        <p class="mb-1 text-black">{{ Auth::user()->name }}</p>
                    </div>
                </a>
                <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i
                                class="mdi mdi-cached me-2 text-success"></i>
                            Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="mdi mdi-logout me-2 text-primary"></i> Signout
                            </button>
                        </form>
                    </li>
                </ul>
            </li>

        </ul>

        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
            data-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
        </button>
    </div>
</nav>
