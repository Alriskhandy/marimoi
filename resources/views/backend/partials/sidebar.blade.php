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

        <!-- Data Peta Tematik -->
        @php
            $isPetaTematikActive = request()->routeIs('lokasi.*') || request()->routeIs('kategori-layers.index');
        @endphp
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#petaTematikMenu"
                aria-expanded="{{ $isPetaTematikActive ? 'true' : 'false' }}" aria-controls="petaTematikMenu">
                <span class="menu-title">Peta RPJMD</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-layers menu-icon"></i>
            </a>
            <div class="collapse {{ $isPetaTematikActive ? 'show' : '' }}" id="petaTematikMenu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item {{ request()->routeIs('lokasi.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('lokasi.index') }}">
                            <i class="mdi mdi-map-outline me-2"></i>Data Peta RPJMD
                        </a>
                    </li>
                    <li class="nav-item {{ request()->routeIs('kategori-layers.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('kategori-layers.index') }}">
                            <i class="mdi mdi-format-list-bulleted me-2"></i>Kategori Peta RPJMD
                        </a>
                    </li>
                    @if (Route::has('lokasi.feedbacks.index'))
                        <li class="nav-item {{ request()->routeIs('lokasi.feedbacks.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('lokasi.feedbacks.index') }}">
                                <i class="mdi mdi-comment-multiple me-2"></i>Feedback Lokasi

                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </li>

        <!-- Proyek Strategis Daerah -->
        @php
            try {
                $availableYears = \App\Models\ProyekStrategisDaerah::select('tahun')
                    ->distinct()
                    ->orderBy('tahun', 'desc')
                    ->pluck('tahun');
            } catch (Exception $e) {
                $availableYears = collect();
            }

            $isPSDActive = request()->routeIs('psd.*') || request()->routeIs('daerah.feedbacks.*');
            $currentYear = request()->route('year') ?? date('Y');
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

                    @if ($availableYears->count() > 0)
                        @foreach ($availableYears as $year)
                            <li class="nav-item">
                                @if (Route::has('psd.tahun.show'))
                                    <a class="nav-link {{ request()->routeIs('psd.tahun.show') && request()->route('year') == $year ? 'active' : '' }}"
                                        href="{{ route('psd.tahun.show', $year) }}">
                                        <i class="mdi mdi-calendar me-2"></i>Tahun {{ $year }}
                                        <span class="badge badge-sm bg-primary ms-auto"
                                            data-year-count="{{ $year }}">
                                            @php
                                                try {
                                                    echo \App\Models\ProyekStrategisDaerah::where(
                                                        'tahun',
                                                        $year,
                                                    )->count();
                                                } catch (Exception $e) {
                                                    echo '0';
                                                }
                                            @endphp
                                        </span>
                                    </a>
                                @else
                                    <span class="nav-link">
                                        <i class="mdi mdi-calendar me-2"></i>Tahun {{ $year }}
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

                    <!-- Menu kategori -->
                    @if (Route::has('psd.kategori.index'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('psd.kategori.*') ? 'active' : '' }}"
                                href="{{ route('psd.kategori.index') }}">
                                <i class="mdi mdi-tag-multiple me-2"></i>Kategori Proyek Daerah
                            </a>
                        </li>
                    @endif

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
                    @if (Route::has('psd.create'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('psd.create') ? 'active' : '' }}"
                                href="{{ route('psd.create') }}">
                                <i class="mdi mdi-plus-circle me-2"></i>Tambah Data Baru
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </li>

        <!-- Proyek Strategis Nasional -->
        @php
            try {
                $availableYearsNasional = \App\Models\ProyekStrategisNasional::select('tahun')
                    ->distinct()
                    ->orderBy('tahun', 'desc')
                    ->pluck('tahun');
            } catch (Exception $e) {
                $availableYearsNasional = collect();
            }

            $isPSNActive = request()->routeIs('psn.*') || request()->routeIs('nasional.feedbacks.*');
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

                    @if ($availableYearsNasional->count() > 0)
                        @foreach ($availableYearsNasional as $year)
                            <li class="nav-item">
                                @if (Route::has('psn.tahun.show'))
                                    <a class="nav-link {{ request()->routeIs('psn.tahun.show') && request()->route('year') == $year ? 'active' : '' }}"
                                        href="{{ route('psn.tahun.show', $year) }}">
                                        <i class="mdi mdi-calendar me-2"></i>Tahun {{ $year }}
                                        <span class="badge badge-sm bg-primary ms-auto">
                                            @php
                                                try {
                                                    echo \App\Models\ProyekStrategisNasional::where(
                                                        'tahun',
                                                        $year,
                                                    )->count();
                                                } catch (Exception $e) {
                                                    echo '0';
                                                }
                                            @endphp
                                        </span>
                                    </a>
                                @else
                                    <span class="nav-link">
                                        <i class="mdi mdi-calendar me-2"></i>Tahun {{ $year }}
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

                    <!-- Menu kategori -->
                    @if (Route::has('psn.kategori.index'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('psn.kategori.*') ? 'active' : '' }}"
                                href="{{ route('psn.kategori.index') }}">
                                <i class="mdi mdi-tag-multiple me-2"></i>Kategori Proyek Nasional
                            </a>
                        </li>
                    @endif

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
                    @if (Route::has('psn.create'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('psn.create') ? 'active' : '' }}"
                                href="{{ route('psn.create') }}">
                                <i class="mdi mdi-plus-circle me-2"></i>Tambah Data Baru
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </li>

        <!-- POKIR DPRD -->
        @php
            $isPOKIRActive =
                request()->routeIs('pokir-dprd.*') ||
                request()->routeIs('kategori-pokir-dprd.*') ||
                request()->routeIs('pokir.feedbacks.*');
        @endphp

        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#pokirMenu"
                aria-expanded="{{ $isPOKIRActive ? 'true' : 'false' }}" aria-controls="pokirMenu">
                <span class="menu-title">Pokir DPRD</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-gavel menu-icon"></i>
            </a>
            <div class="collapse {{ $isPOKIRActive ? 'show' : '' }}" id="pokirMenu">
                <ul class="nav flex-column sub-menu">
                    @if (Route::has('pokir-dprd.index'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('pokir-dprd.index') ? 'active' : '' }}"
                                href="{{ route('pokir-dprd.index') }}">
                                <i class="mdi mdi-database me-2"></i>Data Pokir DPRD
                            </a>
                        </li>
                    @endif
                    @if (Route::has('kategori-pokir-dprd.index'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('kategori-pokir-dprd.*') ? 'active' : '' }}"
                                href="{{ route('kategori-pokir-dprd.index') }}">
                                <i class="mdi mdi-tag-multiple me-2"></i>Kategori Pokir
                            </a>
                        </li>
                    @endif
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
                request()->routeIs('kategori-usulan-musrenbang.*') ||
                request()->routeIs('usulan.feedbacks.*');
        @endphp
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#usulanmusrenbang"
                aria-expanded="{{ $isMUSRENBANGActive ? 'true' : 'false' }}" aria-controls="usulanmusrenbang">
                <span class="menu-title">Usulan Musrenbang</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-forum menu-icon"></i>
            </a>
            <div class="collapse {{ $isMUSRENBANGActive ? 'show' : '' }}" id="usulanmusrenbang">
                <ul class="nav flex-column sub-menu">
                    @if (Route::has('usulan-musrenbang.index'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('usulan-musrenbang.index') ? 'active' : '' }}"
                                href="{{ route('usulan-musrenbang.index') }}">
                                <i class="mdi mdi-database me-2"></i>Data Usulan Musrenbang
                            </a>
                        </li>
                    @endif
                    @if (Route::has('kategori-usulan-musrenbang.index'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('kategori-usulan-musrenbang.*') ? 'active' : '' }}"
                                href="{{ route('kategori-usulan-musrenbang.index') }}">
                                <i class="mdi mdi-tag-multiple me-2"></i>Kategori Musrenbang
                            </a>
                        </li>
                    @endif
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

        <!-- Desk Musrenbang (Coming Soon) -->
        @if (Route::has('cooming_soon'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('cooming_soon') }}">
                    <span class="menu-title">Desk Forum PD</span>
                    <i class="mdi mdi-city-variant-outline menu-icon"></i>
                    <span class="badge badge-warning badge-sm ms-auto">Soon</span>
                </a>
            </li>
        @endif

        <!-- Upload Dokumen -->
        @php
            $isDokumenActive = request()->routeIs('dokumen.*');
        @endphp
        @if (Route::has('dokumen.index'))
            <li class="nav-item">
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

    <!-- JavaScript untuk badge counts yang aman -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Safely update feedback counts via AJAX
            function updateFeedbackCounts() {
                // Only fetch if API route exists
                if (typeof feedbackStatsUrl !== 'undefined' || document.querySelector('[data-feedback-count]')) {
                    fetch('/api/feedback-stats')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Safely update badge counts
                                updateBadgeCount('total', data.total || 0);
                                updateBadgeCount('pokir_dprd', data.pokir_dprd || 0);
                                updateBadgeCount('usulan_musrenbang', data.usulan_musrenbang || 0);
                                updateBadgeCount('proyek_strategis_nasional', data.proyek_strategis_nasional ||
                                    0);
                                updateBadgeCount('proyek_strategis_daerah', data.proyek_strategis_daerah || 0);
                                updateBadgeCount('lokasi', data.lokasi || 0);
                            }
                        })
                        .catch(error => {
                            // Silently handle errors - don't break the sidebar
                            console.log('Feedback stats update failed:', error);
                        });
                }
            }

            function updateBadgeCount(type, count) {
                const badges = document.querySelectorAll(`[data-feedback-count="${type}"]`);
                badges.forEach(badge => {
                    if (badge) {
                        badge.textContent = count;
                        // Add subtle animation
                        badge.classList.add('badge-updated');
                        setTimeout(() => badge.classList.remove('badge-updated'), 500);
                    }
                });
            }

            // Initial load with delay to avoid blocking page render
            setTimeout(updateFeedbackCounts, 2000);

            // Update every 60 seconds (reduced frequency to avoid performance issues)
            setInterval(updateFeedbackCounts, 60000);
        });
    </script>

    <style>
        /* Safe badge animation */
        .badge-updated {
            animation: gentle-pulse 0.5s ease-in-out;
        }

        @keyframes gentle-pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        /* Ensure sidebar doesn't break on small screens */
        .sidebar .nav .nav-item .nav-link {
            word-wrap: break-word;
        }

        .badge {
            min-width: 20px;
            text-align: center;
        }
    </style>
</nav>
