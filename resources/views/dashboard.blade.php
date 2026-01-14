@extends('backend.partials.main')

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-home"></i>
            </span> Dashboard
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">
                    <span></span>Overview <i class="mdi mdi-alert-circle-outline icon-sm text-primary align-middle"></i>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Stats Row -->
    <div class="row">
        <div class="col-md-4 stretch-card grid-margin">
            <div class="card bg-gradient-primary card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle-image" />
                    <h4 class="font-weight-normal mb-3">
                        Total Data Spasial
                        <i class="mdi mdi-database mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-5">{{ $totalLokasi ?? '0' }}</h2>
                    <h6 class="card-text">Jumlah seluruh layer geometrik</h6>
                </div>
            </div>
        </div>

        <div class="col-md-4 stretch-card grid-margin">
            <div class="card bg-gradient-info card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle-image" />
                    <h4 class="font-weight-normal mb-3">
                        OPD
                        <i class="mdi mdi-layers mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-5">{{ $totalOpd ?? '0' }}</h2>
                    <h6 class="card-text">Jumlah Seluruh OPD Yang Terdaftar</h6>
                </div>
            </div>
        </div>

        <div class="col-md-4 stretch-card grid-margin">
            <div class="card bg-gradient-success card-img-holder text-white">
                <div class="card-body">
                    <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                        alt="circle-image" />
                    <h4 class="font-weight-normal mb-3">
                        Aspirasi
                        <i class="mdi mdi-map-outline mdi-24px float-end"></i>
                    </h4>
                    <h2 class="mb-5">{{ $totalPendingAspirasi ?? '0' }} </h2>
                    <h6 class="card-text">Jumlah Aspirasi Yang Belum Direspon</h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Visitor Statistics Row (Only for non admin-opd) -->
    @if (!auth()->user() || auth()->user()->role->slug !== 'admin-opd')
        <div class="row">
            <div class="col-md-6 stretch-card grid-margin">
                <div class="card bg-gradient-warning card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle-image" />
                        <h4 class="font-weight-normal mb-3">
                            Total Pengunjung
                            <i class="mdi mdi-account-multiple mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-5">{{ $totalVisitors ?? '0' }}</h2>
                        <h6 class="card-text">Jumlah seluruh pengunjung website</h6>
                    </div>
                </div>
            </div>

            <div class="col-md-6 stretch-card grid-margin">
                <div class="card bg-gradient-dark card-img-holder text-white">
                    <div class="card-body">
                        <img src="{{ asset('backend/assets/images/dashboard/circle.svg') }}" class="card-img-absolute"
                            alt="circle-image" />
                        <h4 class="font-weight-normal mb-3">
                            Pengunjung Hari Ini
                            <i class="mdi mdi-calendar-today mdi-24px float-end"></i>
                        </h4>
                        <h2 class="mb-5" id="todayVisitors">{{ $todayVisitors ?? '0' }}</h2>
                        <h6 class="card-text">Pengunjung yang mengakses hari ini</h6>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-6 col-sm-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-9">
                            <div class="d-flex align-items-center align-self-start">
                                <h3 class="mb-0">{{ $totalAspirasi ?? '0' }}</h3>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="icon icon-bg-primary">
                                <i class="mdi mdi-email-open icon-md"></i>
                            </div>
                        </div>
                    </div>
                    <h6 class="text-muted font-weight-normal">Total Aspirasi</h6>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-sm-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-9">
                            <div class="d-flex align-items-center align-self-start">
                                <h3 class="mb-0">{{ $totalPendingAspirasi ?? '0' }}</h3>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="icon icon-bg-warning">
                                <i class="mdi mdi-clock-outline icon-md"></i>
                            </div>
                        </div>
                    </div>
                    <h6 class="text-muted font-weight-normal">Pending</h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">Trend Aspirasi Bulanan</h4>
                        <div>
                            <select class="form-select form-select-sm" id="yearFilter">
                                @foreach ($availableYears ?? [date('Y')] as $year)
                                    <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visitor Chart Section (Only for non admin-opd) -->
    @if (!auth()->user() || auth()->user()->role->slug !== 'admin-opd')
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Trend Pengunjung Bulanan</h4>
                            <div>
                                <select class="form-select form-select-sm" id="visitorYearFilter">
                                    @foreach ($availableYears ?? [date('Y')] as $year)
                                        <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="visitorChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Recent Aspirasi Table -->
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Aspirasi Terbaru</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nomor Tiket</th>
                                    <th>Pengirim</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentAspirasi ?? [] as $aspirasi)
                                    <tr>
                                        <td>{{ $aspirasi->nomor_tiket }}</td>
                                        <td>{{ $aspirasi->nama_pengirim }}</td>
                                        <td>{{ $aspirasi->kategori->nama ?? '-' }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $aspirasi->status == 'pending' ? 'warning' : ($aspirasi->status == 'selesai' ? 'success' : 'info') }}">
                                                {{ ucfirst($aspirasi->status) }}
                                            </span>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($aspirasi->created_at)->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada data aspirasi</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Chart initialization
        let monthlyChart, visitorChart;
        let chartInitialized = false;

        $(document).ready(function() {
            // Wait for DOM to be fully loaded
            setTimeout(function() {
                initializeCharts();
                chartInitialized = true;
            }, 100);

            // Year filter change
            $('#yearFilter').on('change', function() {
                if (chartInitialized) {
                    loadDashboardData();
                }
            });

            // Visitor year filter change
            $('#visitorYearFilter').on('change', function() {
                if (chartInitialized) {
                    loadVisitorData();
                }
            });

            // Load today's visitor count if not admin-opd
            @if (!auth()->user() || auth()->user()->role->slug !== 'admin-opd')
                loadTodayVisitors();
            @endif
        });

        function initializeCharts() {
            try {
                // Destroy existing charts if they exist
                if (monthlyChart) {
                    monthlyChart.destroy();
                }
                if (visitorChart) {
                    visitorChart.destroy();
                }

                // Monthly Aspirasi Chart
                const monthlyCtx = document.getElementById('monthlyChart');
                if (monthlyCtx) {
                    monthlyChart = new Chart(monthlyCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov',
                                'Des'
                            ],
                            datasets: [{
                                label: 'Total Aspirasi',
                                data: @json($monthlyAspirasi['total'] ?? array_fill(0, 12, 0)),
                                borderColor: '#6f42c1',
                                backgroundColor: 'rgba(111, 66, 193, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#6f42c1',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 5
                            }, {
                                label: 'Selesai',
                                data: @json($monthlyAspirasi['selesai'] ?? array_fill(0, 12, 0)),
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#28a745',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 20
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    borderColor: '#6f42c1',
                                    borderWidth: 1
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.1)'
                                    },
                                    ticks: {
                                        stepSize: 1
                                    }
                                },
                                x: {
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.1)'
                                    }
                                }
                            }
                        }
                    });
                }

                // Visitor Chart (only for non admin-opd)
                @if (!auth()->user() || auth()->user()->role->slug !== 'admin-opd')
                    const visitorCtx = document.getElementById('visitorChart');
                    if (visitorCtx) {
                        visitorChart = new Chart(visitorCtx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt',
                                    'Nov', 'Des'
                                ],
                                datasets: [{
                                    label: 'Total Pengunjung',
                                    data: @json($visitorData['total'] ?? array_fill(0, 12, 0)),
                                    borderColor: '#ffc107',
                                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    pointBackgroundColor: '#ffc107',
                                    pointBorderColor: '#ffffff',
                                    pointBorderWidth: 2,
                                    pointRadius: 5
                                }, {
                                    label: 'Pengunjung Unik',
                                    data: @json($visitorData['unique'] ?? array_fill(0, 12, 0)),
                                    borderColor: '#17a2b8',
                                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    pointBackgroundColor: '#17a2b8',
                                    pointBorderColor: '#ffffff',
                                    pointBorderWidth: 2,
                                    pointRadius: 5
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    intersect: false,
                                    mode: 'index'
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 20
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: '#ffffff',
                                        bodyColor: '#ffffff',
                                        borderColor: '#ffc107',
                                        borderWidth: 1
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.1)'
                                        },
                                        ticks: {
                                            stepSize: 1
                                        }
                                    },
                                    x: {
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.1)'
                                        }
                                    }
                                }
                            }
                        });
                    }
                @endif

                console.log('Charts initialized successfully');

            } catch (error) {
                console.error('Error initializing charts:', error);
            }
        }

        function loadDashboardData() {
            const year = $('#yearFilter').val() || {{ date('Y') }};

            $.ajax({
                url: '{{ route('dashboard.api.statistics') }}',
                method: 'GET',
                data: {
                    year: year
                },
                beforeSend: function() {
                    $('.chart-container').addClass('loading');
                },
                success: function(response) {
                    if (response.success && monthlyChart) {
                        monthlyChart.data.datasets[0].data = response.monthly.total || Array(12).fill(0);
                        monthlyChart.data.datasets[1].data = response.monthly.selesai || Array(12).fill(0);
                        monthlyChart.update('none');
                    }
                },
                error: function(xhr) {
                    console.error('Failed to load dashboard data:', xhr);
                },
                complete: function() {
                    $('.chart-container').removeClass('loading');
                }
            });
        }

        @if (!auth()->user() || auth()->user()->role->slug !== 'admin-opd')
            function loadVisitorData() {
                const year = $('#visitorYearFilter').val() || {{ date('Y') }};

                $.ajax({
                    url: '{{ route('dashboard.visitor.statistics') }}',
                    method: 'GET',
                    data: {
                        year: year
                    },
                    success: function(response) {
                        if (response.success && visitorChart) {
                            visitorChart.data.datasets[0].data = response.monthly.total || Array(12).fill(0);
                            visitorChart.data.datasets[1].data = response.monthly.unique || Array(12).fill(0);
                            visitorChart.update('none');
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to load visitor data:', xhr);
                    }
                });
            }

            function loadTodayVisitors() {
                $.ajax({
                    url: '{{ route('dashboard.visitor.statistics') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.stats) {
                            $('#todayVisitors').text(response.stats.today || '0');
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to load today visitors:', xhr);
                    }
                });
            }
        @endif

        // Handle window resize
        $(window).on('resize', function() {
            if (monthlyChart) {
                monthlyChart.resize();
            }
            @if (!auth()->user() || auth()->user()->role->slug !== 'admin-opd')
                if (visitorChart) {
                    visitorChart.resize();
                }
            @endif
        });
    </script>

    <style>
        /* Chart Container Styles */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .chart-container-small {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Loading state for charts */
        .chart-container.loading::before,
        .chart-container-small.loading::before {
            content: 'Loading...';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            background: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        /* Prevent chart canvas from growing indefinitely */
        #monthlyChart,
        #visitorChart {
            max-height: 100% !important;
            max-width: 100% !important;
        }

        /* Card title spacing */
        .card-title {
            margin-bottom: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
            }

            .chart-container-small {
                height: 250px;
            }
        }

        @media (max-width: 576px) {
            .chart-container {
                height: 250px;
            }

            .chart-container-small {
                height: 200px;
            }
        }
    </style>
@endsection
