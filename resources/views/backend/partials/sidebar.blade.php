<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <!-- User Profile -->
        <li class="nav-item nav-profile">
            <a href="#!" class="nav-link">
                <div class="nav-profile-image">
                    <img src="{{ asset('backend/assets/images/faces/profile.png') }}" alt="profile" />
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
                request()->routeIs('lokasi.*') ||
                (request()->routeIs('data-spatial.*') && request()->get('type') === 'lokasi') ||
                (request()->routeIs('categories.*') && request()->get('type') == 'layers') ||
                request()->routeIs('kategori-layers.*');
        @endphp
        <li class="nav-item {{ $isPetaTematikActive ? 'active' : '' }}">
            <a class="nav-link" data-bs-toggle="collapse" href="#petaTematikMenu"
                aria-expanded="{{ $isPetaTematikActive ? 'true' : 'false' }}" aria-controls="petaTematikMenu">
                <span class="menu-title">Peta Tematik</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-layers menu-icon"></i>
            </a>
            <div class="collapse {{ $isPetaTematikActive ? 'show' : '' }}" id="petaTematikMenu">
                <ul class="nav flex-column sub-menu">
                    <li
                        class="nav-item {{ request()->routeIs('data-spatial.*') && request()->get('type') === 'lokasi' ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('data-spatial.index', ['type' => 'lokasi']) }}">
                            <i class="mdi mdi-map-outline me-2"></i>Data Peta Tematik
                        </a>
                    </li>

                    <!-- Kategori Peta Tematik -->
                    <li
                        class="nav-item {{ (request()->routeIs('categories.*') && request()->get('type') == 'layers') || request()->routeIs('kategori-layers.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('categories.index', ['type' => 'layers']) }}">
                            <i class="mdi mdi-format-list-bulleted me-2"></i>Kategori Peta Tematik
                        </a>
                    </li>

                    @if (Route::has('lokasi.feedbacks.index'))
                        <li class="nav-item {{ request()->routeIs('lokasi.feedbacks.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('lokasi.feedbacks.index') }}">
                                <i class="mdi mdi-comment-multiple me-2"></i>Feedback Tematik
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </li>

        <!-- Proyek Strategis Daerah -->
        @php
            try {
                $availableYears = \App\Models\DataSpatial::where('data_type', 'proyek_strategis')
                    ->where('sub_type', 'psd')
                    ->select('tahun')
                    ->distinct()
                    ->whereNotNull('tahun')
                    ->orderBy('tahun', 'desc')
                    ->pluck('tahun');
            } catch (Exception $e) {
                $availableYears = collect();
            }

            $isPSDActive =
                request()->routeIs('psd.*') ||
                request()->routeIs('daerah.feedbacks.*') ||
                (request()->routeIs('data-spatial.*') &&
                    request()->get('type') === 'proyek_strategis' &&
                    request()->get('sub_type') === 'psd') ||
                (request()->routeIs('categories.*') && request()->get('type') == 'psd');
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
                    @if (Route::has('daerah.feedbacks.index'))
                        <li class="nav-item {{ request()->routeIs('daerah.feedbacks.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('daerah.feedbacks.index') }}">
                                <i class="mdi mdi-comment-multiple me-2"></i>Feedback Proyek Daerah
                            </a>
                        </li>
                    @endif

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
                $availableYearsNasional = \App\Models\DataSpatial::where('data_type', 'proyek_strategis')
                    ->where('sub_type', 'psn')
                    ->select('tahun')
                    ->distinct()
                    ->whereNotNull('tahun')
                    ->orderBy('tahun', 'desc')
                    ->pluck('tahun');
            } catch (Exception $e) {
                $availableYearsNasional = collect();
            }

            $isPSNActive =
                request()->routeIs('psn.*') ||
                request()->routeIs('nasional.feedbacks.*') ||
                (request()->routeIs('data-spatial.*') &&
                    request()->get('type') === 'proyek_strategis' &&
                    request()->get('sub_type') === 'psn') ||
                (request()->routeIs('categories.*') && request()->get('type') == 'psn');
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
                    @if (Route::has('nasional.feedbacks.index'))
                        <li class="nav-item {{ request()->routeIs('nasional.feedbacks.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('nasional.feedbacks.index') }}">
                                <i class="mdi mdi-comment-multiple me-2"></i>Feedback Proyek Nasional
                            </a>
                        </li>
                    @endif

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
                request()->routeIs('pokir.feedbacks.*') ||
                (request()->routeIs('data-spatial.*') && request()->get('type') === 'pokir_dprd') ||
                (request()->routeIs('categories.*') && request()->get('type') == 'pokir_dprds') ||
                request()->routeIs('kategori-pokir-dprd.*');
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
                        class="nav-item {{ (request()->routeIs('categories.*') && request()->get('type') == 'pokir_dprds') || request()->routeIs('kategori-pokir-dprd.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('categories.index', ['type' => 'pokir_dprds']) }}">
                            <i class="mdi mdi-tag-multiple me-2"></i>Kategori Pokir
                        </a>
                    </li>

                    @if (Route::has('pokir.feedbacks.index'))
                        <li class="nav-item {{ request()->routeIs('pokir.feedbacks.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('pokir.feedbacks.index') }}">
                                <i class="mdi mdi-comment-multiple me-2"></i>Feedback Pokir DPRD
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </li>

        <!-- Usulan Musrenbang -->
        @php
            $isMUSRENBANGActive =
                request()->routeIs('usulan-musrenbang.*') ||
                request()->routeIs('usulan.feedbacks.*') ||
                (request()->routeIs('data-spatial.*') && request()->get('type') === 'usulan_musrenbang') ||
                (request()->routeIs('categories.*') && request()->get('type') == 'musrenbangs') ||
                request()->routeIs('kategori-usulan-musrenbang.*');
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
                        class="nav-item {{ (request()->routeIs('categories.*') && request()->get('type') == 'musrenbangs') || request()->routeIs('kategori-usulan-musrenbang.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('categories.index', ['type' => 'musrenbangs']) }}">
                            <i class="mdi mdi-tag-multiple me-2"></i>Kategori Musrenbang
                        </a>
                    </li>

                    @if (Route::has('usulan.feedbacks.index'))
                        <li class="nav-item {{ request()->routeIs('usulan.feedbacks.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('usulan.feedbacks.index') }}">
                                <i class="mdi mdi-comment-multiple me-2"></i>Feedback Usulan Musrenbang
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </li>

        {{-- <!-- Desk Musrenbang (Coming Soon) -->
        @if (Route::has('cooming_soon'))
            <li class="nav-item {{ request()->routeIs('cooming_soon') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('cooming_soon') }}">
                    <span class="menu-title">Desk Forum PD</span>
                    <i class="mdi mdi-city-variant-outline menu-icon"></i>
                    <span class="badge badge-warning badge-sm ms-auto">Soon</span>
                </a>
            </li>
        @endif --}}

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

        <li class="nav-item nav-category">
            <span
                class="nav-link d-flex align-items-center text-uppercase fw-semibold text-secondary opacity-75 border-bottom pb-1 mb-2"
                style="cursor: default;">
                <i class="mdi mdi-lan me-2 fs-5 opacity-50"></i>
                Sistem
            </span>
        </li>

        <!-- Sistem & Pengguna -->
        @php
            $isSystemActive = request()->routeIs('users.*') || request()->routeIs('settings.*');
        @endphp
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
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-account-multiple me-2"></i>Manajemen Pengguna
                        </a>
                    </li>
                    <li class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <a class="nav-link" href="#!">
                            <i class="mdi mdi-settings me-2"></i>Pengaturan Sistem
                        </a>
                    </li>
                </ul>
            </div>
        </li>
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
    </style>
</nav>
