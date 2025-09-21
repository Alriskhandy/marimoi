<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <!-- User Profile -->
        <li class="nav-item nav-profile">
            <a href="#!" class="nav-link">
                @php
                    $user = auth()->user();
                    $slug = $user->role->slug;
                @endphp

                @if (auth()->check() && $user->role)
                    <div class="nav-profile-image">
                        @if ($slug === 'admin-opd' && $user->opd && $user->opd->logo)
                            <img src="{{ asset('storage/' . $user->opd->logo) }}" alt="Logo {{ $user->opd->singkatan }}"
                                class="rounded logo-img" style="width: 40px; height: 40px; object-fit: contain; ">
                        @else
                            <img src="{{ asset('backend/assets/images/faces/profile.png') }}" alt="profile"
                                class="img-fluid rounded-circle"
                                style="width: 50px; height: 50px; object-fit: cover;" />
                        @endif

                        <span class="login-status online"></span>
                    </div>

                    <div class="nav-profile-text d-flex flex-column">
                        @if ($slug === 'admin-opd' && $user->opd)
                            <span class="font-weight-bold mb-2">{{ $user->opd->singkatan }}</span>
                        @endif
                        <span class="text-secondary text-small">{{ $user->role->name }}</span>
                    </div>
                @endif

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

        <!-- Divider -->
        <li class="nav-item nav-category">
            <span
                class="nav-link d-flex align-items-center text-uppercase fw-semibold text-secondary opacity-75 border-bottom pb-1 mb-2"
                style="cursor: default;">
                <i class="mdi mdi-database-outline me-2 fs-5 opacity-50"></i>
                Master Data
            </span>
        </li>

        <!-- Data Peta Tematik -->
        @php
            $isPetaTematikActive =
                request()->routeIs('tematik.*') ||
                (request()->routeIs('data-spatial.*') && request()->get('type') === 'tematik') ||
                (request()->routeIs('categories.*') && request()->get('type') == 'tematik') ||
                request()->routeIs('kategori-tematik.*') ||
                (request()->routeIs('project-feedbacks.*') && request()->get('type') === 'tematik');
        @endphp
        <li class="nav-item {{ $isPetaTematikActive ? 'active' : '' }}">
            <a class="nav-link" data-bs-toggle="collapse" href="#petaTematikMenu"
                aria-expanded="{{ $isPetaTematikActive ? 'true' : 'false' }}" aria-controls="petaTematikMenu">
                <span class="menu-title">Peta Tematik</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-tematik menu-icon"></i>
            </a>
            <div class="collapse {{ $isPetaTematikActive ? 'show' : '' }}" id="petaTematikMenu">
                <ul class="nav flex-column sub-menu">
                    <li
                        class="nav-item {{ request()->routeIs('data-spatial.*') && request()->get('type') === 'tematik' ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('data-spatial.index', ['type' => 'tematik']) }}">
                            <i class="mdi mdi-map-outline me-2"></i>Data Peta Tematik
                        </a>
                    </li>

                    <!-- Kategori Peta Tematik -->
                    <li
                        class="nav-item {{ (request()->routeIs('categories.*') && request()->get('type') == 'tematik') || request()->routeIs('kategori-tematik.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('categories.index', ['type' => 'tematik']) }}">
                            <i class="mdi mdi-format-list-bulleted me-2"></i>Kategori Peta Tematik
                        </a>
                    </li>

                    <!-- Feedback RPJMD -->
                    {{-- <li
                        class="nav-item {{ request()->routeIs('project-feedbacks.*') && request()->get('type') === 'tematik' ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('project-feedbacks.index', ['type' => 'tematik']) }}">
                            <i class="mdi mdi-comment-multiple me-2"></i>Feedback RPJMD
                            <span class="badge badge-sm bg-danger ms-auto">
                                @php
                                    try {
                                        echo \App\Models\ProjectFeedback::whereHas('dataSpatial', function ($q) {
                                            $q->where('data_type', 'tematik');
                                        })->count();
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                @endphp
                            </span>
                        </a>
                    </li> --}}


                </ul>
            </div>
        </li>

        <!-- Proyek Strategis Daerah -->
        @php
            try {
                $query = \App\Models\DataSpatial::where('data_type', 'proyek_strategis')
                    ->where('sub_type', 'psd')
                    ->select('tahun')
                    ->distinct()
                    ->whereNotNull('tahun');

                // Filter khusus jika user adalah admin OPD
                if (auth()->user()?->role?->slug === 'admin-opd') {
                    $query->where('user_id', auth()->id());
                }

                $availableYears = $query->orderBy('tahun', 'desc')->pluck('tahun');
            } catch (Exception $e) {
                $availableYears = collect();
            }

            $isPSDActive =
                request()->routeIs('psd.*') ||
                (request()->routeIs('data-spatial.*') &&
                    request()->get('type') === 'proyek_strategis' &&
                    request()->get('sub_type') === 'psd') ||
                (request()->routeIs('categories.*') && request()->get('type') == 'psd') ||
                (request()->routeIs('project-feedbacks.*') &&
                    request()->get('type') === 'proyek_strategis' &&
                    request()->get('sub_type') === 'psd');
            $currentYear = request()->route('year') ?? date('Y');
        @endphp
        <li class="nav-item {{ $isPSDActive ? 'active' : '' }}">
            <a class="nav-link" data-bs-toggle="collapse" href="#psdMenu"
                aria-expanded="{{ $isPSDActive ? 'true' : 'false' }}" aria-controls="psdMenu">
                <span class="menu-title">Proyek Strategis Daerah</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-city menu-icon"></i>
            </a>
            <div class="collapse {{ $isPSDActive ? 'show' : '' }}" id="psdMenu">
                <ul class="nav flex-column sub-menu">
                    <!-- Semua Data PSD -->
                    <li
                        class="nav-item {{ request()->routeIs('data-spatial.*') && request()->get('type') === 'proyek_strategis' && request()->get('sub_type') === 'psd' && !request()->route('year') ? 'active' : '' }}">
                        <a class="nav-link"
                            href="{{ route('data-spatial.index', ['type' => 'proyek_strategis', 'sub_type' => 'psd']) }}">
                            <i class="mdi mdi-map-marker-path me-2"></i> Semua Data PSD
                        </a>
                    </li>

                    <li class="nav-item">
                        <h6 class="sub-menu-header">Data per Tahun</h6>
                    </li>

                    @if ($availableYears->count() > 0)
                        @foreach ($availableYears as $year)
                            <li
                                class="nav-item {{ request()->routeIs('psd.tahun.show') && request()->route('year') == $year ? 'active' : '' }}">
                                @if (Route::has('psd.tahun.show'))
                                    <a class="nav-link" href="{{ route('psd.tahun.show', $year) }}">
                                        <i class="mdi mdi-calendar me-2"></i>Tahun {{ $year }}
                                        <span class="badge badge-sm bg-primary ms-auto"
                                            data-year-count="{{ $year }}">
                                            @php
                                                try {
                                                    echo \App\Models\DataSpatial::where('data_type', 'proyek_strategis')
                                                        ->where('sub_type', 'psd')
                                                        ->where('tahun', $year)
                                                        ->count();
                                                } catch (Exception $e) {
                                                    echo '0';
                                                }
                                            @endphp
                                        </span>
                                    </a>
                                @else
                                    <span class="nav-link">
                                        <i class="mdi mdi-calendar me-2"></i>Tahun {{ $year }}
                                        <span class="badge badge-sm bg-secondary ms-auto">
                                            @php
                                                try {
                                                    echo \App\Models\DataSpatial::where('data_type', 'proyek_strategis')
                                                        ->where('sub_type', 'psd')
                                                        ->where('tahun', $year)
                                                        ->count();
                                                } catch (Exception $e) {
                                                    echo '0';
                                                }
                                            @endphp
                                        </span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    @else
                        <li class="nav-item">
                            <span class="nav-link text-muted">
                                <i class="mdi mdi-information me-2"></i>Belum ada data tahunan
                            </span>
                        </li>
                    @endif

                    <li class="nav-item">
                        <hr class="dropdown-divider">
                    </li>

                    <!-- Menu kategori PSD -->
                    <li
                        class="nav-item {{ request()->routeIs('categories.*') && request()->get('type') == 'psd' ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('categories.index', ['type' => 'psd']) }}">
                            <i class="mdi mdi-tag-multiple me-2"></i>Kategori Proyek Daerah
                        </a>
                    </li>

                    <!-- Feedback Proyek Strategis Daerah -->
                    <li
                        class="nav-item {{ request()->routeIs('project-feedbacks.*') && request()->get('type') === 'proyek_strategis' && request()->get('sub_type') === 'psd' ? 'active' : '' }}">
                        <a class="nav-link"
                            href="{{ route('project-feedbacks.index', ['type' => 'proyek_strategis', 'sub_type' => 'psd']) }}">
                            <i class="mdi mdi-comment-multiple me-2"></i>Feedback Proyek Daerah
                            {{-- <span class="badge badge-sm bg-info ms-auto">
                                @php
                                    try {
                                        echo \App\Models\ProjectFeedback::whereHas('dataSpatial', function ($q) {
                                            $q->where('type', 'proyek_strategis_daerah');
                                        })->count();
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                @endphp
                            </span> --}}
                        </a>
                    </li>

                    <!-- Menu tambah data baru -->
                    <li class="nav-item">
                        <hr class="dropdown-divider">
                    </li>
                    <li
                        class="nav-item {{ request()->routeIs('data-spatial.create') && request()->get('type') == 'proyek_strategis' && request()->get('sub_type') == 'psd' ? 'active' : '' }}">
                        <a class="nav-link"
                            href="{{ route('data-spatial.create') }}?type=proyek_strategis&sub_type=psd">
                            <i class="mdi mdi-plus-circle me-2"></i>Tambah Data Baru
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Proyek Strategis Nasional -->
        @php
            try {
                $query = \App\Models\DataSpatial::where('data_type', 'proyek_strategis')
                    ->where('sub_type', 'psn')
                    ->select('tahun')
                    ->distinct()
                    ->whereNotNull('tahun');

                // Filter berdasarkan user_id jika role adalah admin-opd
                if (auth()->user()?->role?->slug === 'admin-opd') {
                    $query->where('user_id', auth()->id());
                }

                $availableYearsNasional = $query->orderBy('tahun', 'desc')->pluck('tahun');
            } catch (Exception $e) {
                $availableYearsNasional = collect();
            }

            $isPSNActive =
                request()->routeIs('psn.*') ||
                (request()->routeIs('data-spatial.*') &&
                    request()->get('type') === 'proyek_strategis' &&
                    request()->get('sub_type') === 'psn') ||
                (request()->routeIs('categories.*') && request()->get('type') == 'psn') ||
                (request()->routeIs('project-feedbacks.*') &&
                    request()->get('type') === 'proyek_strategis' &&
                    request()->get('sub_type') === 'psn');
        @endphp
        <li class="nav-item {{ $isPSNActive ? 'active' : '' }}">
            <a class="nav-link" data-bs-toggle="collapse" href="#psnMenu"
                aria-expanded="{{ $isPSNActive ? 'true' : 'false' }}" aria-controls="psnMenu">
                <span class="menu-title">Proyek Strategis Nasional</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-flag menu-icon"></i>
            </a>
            <div class="collapse {{ $isPSNActive ? 'show' : '' }}" id="psnMenu">
                <ul class="nav flex-column sub-menu">
                    <!-- Semua Data PSN -->
                    <li
                        class="nav-item {{ request()->routeIs('data-spatial.*') && request()->get('type') === 'proyek_strategis' && request()->get('sub_type') === 'psn' && !request()->route('year') ? 'active' : '' }}">
                        <a class="nav-link"
                            href="{{ route('data-spatial.index', ['type' => 'proyek_strategis', 'sub_type' => 'psn']) }}">
                            <i class="mdi mdi-domain me-2"></i> Semua Data PSN
                        </a>
                    </li>

                    <li class="nav-item">
                        <h6 class="sub-menu-header">Data per Tahun</h6>
                    </li>

                    @if ($availableYearsNasional->count() > 0)
                        @foreach ($availableYearsNasional as $year)
                            <li
                                class="nav-item {{ request()->routeIs('psn.tahun.show') && request()->route('year') == $year ? 'active' : '' }}">
                                @if (Route::has('psn.tahun.show'))
                                    <a class="nav-link" href="{{ route('psn.tahun.show', $year) }}">
                                        <i class="mdi mdi-calendar me-2"></i>Tahun {{ $year }}
                                        <span class="badge badge-sm bg-primary ms-auto">
                                            @php
                                                try {
                                                    echo \App\Models\DataSpatial::where('data_type', 'proyek_strategis')
                                                        ->where('sub_type', 'psn')
                                                        ->where('tahun', $year)
                                                        ->count();
                                                } catch (Exception $e) {
                                                    echo '0';
                                                }
                                            @endphp
                                        </span>
                                    </a>
                                @else
                                    <span class="nav-link">
                                        <i class="mdi mdi-calendar me-2"></i>Tahun {{ $year }}
                                        <span class="badge badge-sm bg-secondary ms-auto">
                                            @php
                                                try {
                                                    echo \App\Models\DataSpatial::where('data_type', 'proyek_strategis')
                                                        ->where('sub_type', 'psn')
                                                        ->where('tahun', $year)
                                                        ->count();
                                                } catch (Exception $e) {
                                                    echo '0';
                                                }
                                            @endphp
                                        </span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    @else
                        <li class="nav-item">
                            <span class="nav-link text-muted">
                                <i class="mdi mdi-information me-2"></i>Belum ada data tahunan
                            </span>
                        </li>
                    @endif

                    <li class="nav-item">
                        <hr class="dropdown-divider">
                    </li>

                    <!-- Menu kategori PSN -->
                    <li
                        class="nav-item {{ request()->routeIs('categories.*') && request()->get('type') == 'psn' ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('categories.index', ['type' => 'psn']) }}">
                            <i class="mdi mdi-tag-multiple me-2"></i>Kategori Proyek Nasional
                        </a>
                    </li>

                    <!-- Feedback Proyek Strategis Nasional -->
                    <li
                        class="nav-item {{ request()->routeIs('project-feedbacks.*') && request()->get('type') === 'proyek_strategis' && request()->get('sub_type') === 'psn' ? 'active' : '' }}">
                        <a class="nav-link"
                            href="{{ route('project-feedbacks.index', ['type' => 'proyek_strategis', 'sub_type' => 'psn']) }}">
                            <i class="mdi mdi-comment-multiple me-2"></i>Feedback Proyek Nasional
                            {{-- <span class="badge badge-sm bg-primary ms-auto">
                                @php
                                    try {
                                        echo \App\Models\ProjectFeedback::whereHas('dataSpatial', function ($q) {
                                            $q->where('type', 'proyek_strategis_nasional');
                                        })->count();
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                @endphp
                            </span> --}}
                        </a>
                    </li>

                    <!-- Menu tambah data baru -->
                    <li class="nav-item">
                        <hr class="dropdown-divider">
                    </li>
                    <li
                        class="nav-item {{ request()->routeIs('data-spatial.create') && request()->get('type') == 'proyek_strategis' && request()->get('sub_type') == 'psn' ? 'active' : '' }}">
                        <a class="nav-link"
                            href="{{ route('data-spatial.create') }}?type=proyek_strategis&sub_type=psn">
                            <i class="mdi mdi-plus-circle me-2"></i>Tambah Data Baru
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- POKIR DPRD -->
        @php
            $isPOKIRActive =
                request()->routeIs('pokir-dprd.*') ||
                (request()->routeIs('data-spatial.*') && request()->get('type') === 'pokir_dprd') ||
                (request()->routeIs('categories.*') && request()->get('type') == 'pokir_dprd') ||
                request()->routeIs('kategori-pokir-dprd.*') ||
                (request()->routeIs('project-feedbacks.*') && request()->get('type') === 'pokir_dprd');
        @endphp
        <li class="nav-item {{ $isPOKIRActive ? 'active' : '' }}">
            <a class="nav-link" data-bs-toggle="collapse" href="#pokirMenu"
                aria-expanded="{{ $isPOKIRActive ? 'true' : 'false' }}" aria-controls="pokirMenu">
                <span class="menu-title">Pokir DPRD</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-gavel menu-icon"></i>
            </a>
            <div class="collapse {{ $isPOKIRActive ? 'show' : '' }}" id="pokirMenu">
                <ul class="nav flex-column sub-menu">
                    <li
                        class="nav-item {{ request()->routeIs('data-spatial.*') && request()->get('type') === 'pokir_dprd' ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('data-spatial.index', ['type' => 'pokir_dprd']) }}">
                            <i class="mdi mdi-database me-2"></i>Data Pokir DPRD
                        </a>
                    </li>

                    <!-- Kategori Pokir DPRD -->
                    <li
                        class="nav-item {{ (request()->routeIs('categories.*') && request()->get('type') == 'pokir_dprd') || request()->routeIs('kategori-pokir-dprd.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('categories.index', ['type' => 'pokir_dprd']) }}">
                            <i class="mdi mdi-tag-multiple me-2"></i>Kategori Pokir
                        </a>
                    </li>

                    <!-- Feedback Pokir DPRD -->
                    <li
                        class="nav-item {{ request()->routeIs('project-feedbacks.*') && request()->get('type') === 'pokir_dprd' ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('project-feedbacks.index', ['type' => 'pokir_dprd']) }}">
                            <i class="mdi mdi-comment-multiple me-2"></i>Feedback Pokir DPRD
                            {{-- <span class="badge badge-sm bg-warning ms-auto">
                                @php
                                    try {
                                        echo \App\Models\ProjectFeedback::whereHas('dataSpatial', function ($q) {
                                            $q->where('type', 'pokir_dprd');
                                        })->count();
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                @endphp
                            </span> --}}
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Usulan Musrenbang -->
        @php
            $isMUSRENBANGActive =
                request()->routeIs('usulan-musrenbang.*') ||
                (request()->routeIs('data-spatial.*') && request()->get('type') === 'usulan_musrenbang') ||
                (request()->routeIs('categories.*') && request()->get('type') == 'usulan_musrenbang') ||
                request()->routeIs('kategori-usulan-musrenbang.*') ||
                (request()->routeIs('project-feedbacks.*') && request()->get('type') === 'usulan_musrenbang');
        @endphp
        <li class="nav-item {{ $isMUSRENBANGActive ? 'active' : '' }}">
            <a class="nav-link" data-bs-toggle="collapse" href="#usulanmusrenbang"
                aria-expanded="{{ $isMUSRENBANGActive ? 'true' : 'false' }}" aria-controls="usulanmusrenbang">
                <span class="menu-title">Usulan Musrenbang</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-forum menu-icon"></i>
            </a>
            <div class="collapse {{ $isMUSRENBANGActive ? 'show' : '' }}" id="usulanmusrenbang">
                <ul class="nav flex-column sub-menu">
                    <li
                        class="nav-item {{ request()->routeIs('data-spatial.*') && request()->get('type') === 'usulan_musrenbang' ? 'active' : '' }}">
                        <a class="nav-link"
                            href="{{ route('data-spatial.index', ['type' => 'usulan_musrenbang']) }}">
                            <i class="mdi mdi-database me-2"></i>Data Usulan Musrenbang
                        </a>
                    </li>

                    <!-- Kategori Usulan Musrenbang -->
                    <li
                        class="nav-item {{ (request()->routeIs('categories.*') && request()->get('type') == 'usulan_musrenbang') || request()->routeIs('kategori-usulan-musrenbang.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('categories.index', ['type' => 'usulan_musrenbang']) }}">
                            <i class="mdi mdi-tag-multiple me-2"></i>Kategori Musrenbang
                        </a>
                    </li>

                    <!-- Feedback Usulan Musrenbang -->
                    <li
                        class="nav-item {{ request()->routeIs('project-feedbacks.*') && request()->get('type') === 'usulan_musrenbang' ? 'active' : '' }}">
                        <a class="nav-link"
                            href="{{ route('project-feedbacks.index', ['type' => 'usulan_musrenbang']) }}">
                            <i class="mdi mdi-comment-multiple me-2"></i>Feedback Usulan Musrenbang
                            {{-- <span class="badge badge-sm bg-success ms-auto">
                                @php
                                    try {
                                        echo \App\Models\ProjectFeedback::whereHas('dataSpatial', function ($q) {
                                            $q->where('type', 'usulan_musrenbang');
                                        })->count();
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                @endphp
                            </span> --}}
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        @if ($slug != 'admin-opd')

            <!-- Upload Dokumen -->
            @php
                $isDokumenActive = request()->routeIs('dokumen.*');
            @endphp
            @if (Route::has('dokumen.index'))
                <li class="nav-item {{ $isDokumenActive ? 'active' : '' }}">
                    <a class="nav-link" data-bs-toggle="collapse" href="#dokumenMenu"
                        aria-expanded="{{ $isDokumenActive ? 'true' : 'false' }}" aria-controls="dokumenMenu">
                        <span class="menu-title">Upload Dokumen</span>
                        <i class="menu-arrow"></i>
                        <i class="mdi mdi-file-document-multiple menu-icon"></i>
                    </a>
                    <div class="collapse {{ $isDokumenActive ? 'show' : '' }}" id="dokumenMenu">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item {{ request()->routeIs('dokumen.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('dokumen.index') }}">
                                    <i class="mdi mdi-file-document me-2"></i>Upload Dokumen
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endif
        @endif

        @php
            $isAspirasiMenuActive =
                request()->routeIs('aspirasi.*') ||
                request()->routeIs('opd.*') ||
                request()->routeIs('kategori-aspirasi.*');

            $isSuperAdmin = auth()->check() && auth()->user()->role && auth()->user()->role->slug === 'super-admin';
        @endphp

        <li class="nav-item {{ $isAspirasiMenuActive ? 'active' : '' }}">
            <a class="nav-link" data-bs-toggle="collapse" href="#aspirasiMenu"
                aria-expanded="{{ $isAspirasiMenuActive ? 'true' : 'false' }}" aria-controls="aspirasiMenu">
                <span class="menu-title">Aspirasi</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-shield-account menu-icon"></i>
            </a>
            <div class="collapse {{ $isAspirasiMenuActive ? 'show' : '' }}" id="aspirasiMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item {{ request()->routeIs('aspirasi.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('aspirasi.index') }}">
                            <i class="mdi mdi-shield-account me-2"></i>Data Aspirasi
                        </a>
                    </li>

                    @if ($isSuperAdmin)
                        <li class="nav-item {{ request()->routeIs('opd.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('opd.index') }}">
                                <i class="mdi mdi-office-building me-2"></i>OPD
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('kategori-aspirasi.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('kategori-aspirasi.index') }}">
                                <i class="mdi mdi-tag-multiple me-2"></i>Kategori Aspirasi
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </li>




        @if (auth()->check() && auth()->user()->role && auth()->user()->role->slug === 'super-admin')
            <li class="nav-item nav-category">
                <span
                    class="nav-link d-flex align-items-center text-uppercase fw-semibold text-secondary opacity-75 border-bottom pb-1 mb-2"
                    style="cursor: default;">
                    <i class="mdi mdi-lan me-2 fs-5 opacity-50"></i>
                    Sistem
                </span>
            </li>

            @php
                $isSystemActive = request()->routeIs('users.*') || request()->routeIs('settings.*');
            @endphp
            <!-- Sistem & Pengguna -->
            <li class="nav-item {{ $isSystemActive ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#systemMenu"
                    aria-expanded="{{ $isSystemActive ? 'true' : 'false' }}" aria-controls="systemMenu">
                    <span class="menu-title">Sistem & Pengguna</span>
                    <i class="menu-arrow"></i>
                    <i class="mdi mdi-cog-outline menu-icon"></i>
                </a>

                <div class="collapse {{ $isSystemActive ? 'show' : '' }}" id="systemMenu">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('users.index') }}">
                                <i class="mdi mdi-account-multiple me-2"></i>Manajemen Pengguna
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('visitors.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('visitors.index') }}">
                                <i class="mdi mdi-chart-line me-2"></i>Analytics Pengunjung
                            </a>
                        </li>
                        {{-- <li class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('settings.index') }}">
                        <i class="mdi mdi-settings me-2"></i>Pengaturan Sistem
                    </a>
                </li> --}}
                    </ul>
                </div>
            </li>

            @php
                $isPublicationActive = request()->routeIs('publications.*');
            @endphp
            <!-- Manajemen Publikasi -->
            <li class="nav-item {{ $isPublicationActive ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#publicationMenu"
                    aria-expanded="{{ $isPublicationActive ? 'true' : 'false' }}" aria-controls="publicationMenu">
                    <span class="menu-title">Manajemen Publikasi</span>
                    <i class="menu-arrow"></i>
                    <i class="mdi mdi-book-open-page-variant menu-icon"></i>
                </a>

                <div class="collapse {{ $isPublicationActive ? 'show' : '' }}" id="publicationMenu">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item {{ request()->routeIs('publications.index') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('publications.index') }}">
                                <i class="mdi mdi-format-list-bulleted me-2"></i>Daftar Publikasi
                            </a>
                        </li>
                        {{-- <li class="nav-item {{ request()->routeIs('publications.create') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('publications.create') }}">
                                <i class="mdi mdi-plus-circle me-2"></i>Tambah Publikasi
                            </a>
                        </li> --}}
                        {{-- <li class="nav-item {{ request()->routeIs('publications.categories') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('publications.categories') }}">
                                <i class="mdi mdi-tag-multiple me-2"></i>Kategori Publikasi
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('publications.statistics') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('publications.statistics') }}">
                                <i class="mdi mdi-chart-bar me-2"></i>Statistik Download
                            </a>
                        </li> --}}
                    </ul>
                </div>
            </li>
        @endif

    </ul>

    <style>
        .nav-section-divider {
            height: 1px;
            background-color: #bdbdbd;
            margin: 0.75rem 1rem;
            border: none;
        }

        .nav-category .nav-link {
            pointer-events: none;
            cursor: default;
            color: #6c757d !important;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        .nav-category .mdi {
            opacity: 0.5;
            font-size: 1.1rem;
        }

        .sub-menu-header {
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0.5rem 1rem 0.25rem 1rem;
            font-weight: 600;
        }

        .badge-sm {
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
        }

        .dropdown-divider {
            border-top: 1px solid #e3e6f0;
            margin: 0.5rem 1rem;
        }

        /* Enhanced styling for feedback menu items */
        .nav-item .nav-link:hover .badge {
            background-color: rgba(255, 255, 255, 0.2) !important;
        }

        /* Active state for nested items */
        .nav-item.active>.nav-link .badge {
            background-color: rgba(255, 255, 255, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        /* Feedback menu styling */
        .nav-item .nav-link .badge {
            transition: all 0.3s ease;
        }

        .nav-item:hover .nav-link .badge {
            transform: scale(1.1);
        }

        /* Color coding for different feedback types */
        .badge.bg-danger {
            background-color: #dc3545 !important;
        }

        /* RPJMD */
        .badge.bg-warning {
            background-color: #ffc107 !important;
        }

        /* Pokir DPRD */
        .badge.bg-success {
            background-color: #28a745 !important;
        }

        /* Usulan Musrenbang */
        .badge.bg-primary {
            background-color: #007bff !important;
        }

        /* PSN */
        .badge.bg-info {
            background-color: #17a2b8 !important;
        }

        /* PSD & Semua */
    </style>
</nav>
