<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ ($title ?? 'Dashboard') . ' - MARIMOI' }}</title>

    <link rel="stylesheet" href="{{ asset('backend_baru/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend_baru/assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/mdi/css/materialdesignicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/ti-icons/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('backend/assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend_baru/assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('backend_baru/assets/css/compat.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/datatables/datatables.min.css') }}">
    <link rel="shortcut icon" href="{{ asset('frontend/img/logo/logo-white.png') }}" />
    <script>
        (function() {
            try {
                var t = localStorage.getItem('adminHMD.colorTheme');
                if (t !== 'dark' && t !== 'light') {
                    t = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-theme', t);
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch (e) {}
        })();
    </script>
    @stack('styles')
    <style>
        #rowsPerPageSelect:focus {
            box-shadow: none;
            border-color: #764ba2;
        }

        #pagination {
            margin-top: 20px;
        }

        #pagination .page-item {
            margin: 0 2px;
        }

        #pagination .page-link {
            border: 1px solid #dee2e6;
            color: #4b4b4b;
            padding: 6px 12px;
            border-radius: 4px;
            background-color: #fff;
            transition: all 0.3s ease;
        }

        #pagination .page-link:hover {
            background-color: #667eea;
            color: #fff;
            border-color: #667eea;
        }

        #pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-color: transparent;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }
    </style>

</head>

<body>
    <div class="admin-shell">
        <div class="sidebar-backdrop" data-sidebar-close></div>

        @include('backend.partials.sidebar')

        <div class="admin-main">
            @include('backend.partials.navbar')

            <main class="dashboard-content">
                <div class="container-fluid px-3 px-lg-4 py-4 content-wrapper">
                    @yield('main')
                </div>
                @include('backend.partials.footer')
            </main>
        </div>
    </div>

    <script src="{{ asset('backend/assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('backend_baru/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('backend/assets/vendors/chart.js/chart.umd.js') }}"></script>
    <script src="{{ asset('backend/assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('backend/assets/js/jquery.cookie.js') }}"></script>
    <script src="{{ asset('backend_baru/assets/js/main.js') }}"></script>
    <script>
        (function() {
            function applyChartTheme() {
                if (typeof Chart === 'undefined') {
                    return;
                }
                var dark = document.documentElement.getAttribute('data-theme') === 'dark';
                Chart.defaults.color = dark ? '#9aa8bd' : '#666';
                Chart.defaults.borderColor = dark ? 'rgba(154, 168, 189, 0.18)' : 'rgba(0, 0, 0, 0.1)';
            }
            applyChartTheme();
            document.querySelectorAll('[data-theme-toggle]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    setTimeout(function() {
                        applyChartTheme();
                        if (typeof Chart !== 'undefined' && Chart.instances) {
                            Object.values(Chart.instances).forEach(function(chart) {
                                chart.options.scales && Object.values(chart.options.scales).forEach(function(scale) {
                                    scale.ticks = scale.ticks || {};
                                    scale.ticks.color = Chart.defaults.color;
                                    scale.grid = scale.grid || {};
                                    scale.grid.color = Chart.defaults.borderColor;
                                });
                                chart.update();
                            });
                        }
                    }, 0);
                });
            });
        })();
    </script>

    {{-- <script src="{{ asset('/assets/js/sweetalert/sweetalert2.all.min.js') }}"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteForms = document.querySelectorAll('form[data-confirm="delete"]');

            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Yakin ingin menghapus?',
                        text: "Data akan dihapus permanen!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // Tampilkan notifikasi jika ada flash message
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '{{ session('success') }}',
                    timer: 2000,
                    showConfirmButton: false
                });
            @endif
            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    confirmButtonText: 'OK'
                });
            @endif
        });
    </script>

    <script src="{{ asset('backend/datatables/datatables.min.js') }}"></script>


    @yield('scripts')
    @stack('scripts')
</body>

</html>
