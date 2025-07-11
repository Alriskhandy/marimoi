<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ADMIN MARIMOI</title>
    <!-- container-scroller -->

    <!-- plugins:css -->
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/mdi/css/materialdesignicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/ti-icons/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/font-awesome/css/font-awesome.min.css') }}">
    <!-- endinject -->

    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="{{ asset('backend/assets/vendors/font-awesome/css/font-awesome.min.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('backend/assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">
    <!-- End plugin css for this page -->

    <!-- Layout styles -->
    <link rel="stylesheet" href="{{ asset('backend/assets/css/style.css') }}">
    <!-- End layout styles -->

    <link rel="shortcut icon" href="{{ asset('backend/assets/images/logo.png') }}" />
    @stack('styles')
</head>

<body>
    <div class="container-scroller">
        <!-- partial:partials/_navbar.html -->
        @include('backend.partials.navbar')

        <!-- partial -->
        <div class="container-fluid page-body-wrapper">
            <!-- partial:partials/_sidebar.html -->
            @include('backend.partials.sidebar')
            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    @yield('main')

                </div>
                <!-- content-wrapper ends -->
                @include('backend.partials.footer')
                <!-- partial -->
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->

    <!-- plugins:js -->
    <script src="{{ asset('backend/assets/vendors/js/vendor.bundle.base.js') }}"></script>
    <!-- endinject -->

    <!-- Plugin js for this page -->
    <script src="{{ asset('backend/assets/vendors/chart.js/chart.umd.js') }}"></script>
    <script src="{{ asset('backend/assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
    <!-- End plugin js for this page -->

    <!-- inject:js -->
    <script src="{{ asset('backend/assets/js/off-canvas.js') }}"></script>
    <script src="{{ asset('backend/assets/js/misc.js') }}"></script>
    <script src="{{ asset('backend/assets/js/settings.js') }}"></script>
    <script src="{{ asset('backend/assets/js/todolist.js') }}"></script>
    <script src="{{ asset('backend/assets/js/jquery.cookie.js') }}"></script>
    <!-- endinject -->

    <!-- Custom js for this page -->
    <script src="{{ asset('backend/assets/js/dashboard.js') }}"></script>
    <!-- End custom js for this page -->
    @yield('scripts')
</body>

</html>
