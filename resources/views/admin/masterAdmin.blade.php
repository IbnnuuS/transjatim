<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Admin - Trans Jatim')</title>
    <meta name="description" content="Dashboard admin: monitoring, manajemen pengguna, penjadwalan, pekerjaan & laporan">
    <meta name="keywords" content="trans jatim, admin, dashboard, niceadmin">

    <!-- Favicons -->
    <link rel="icon" href="{{ asset('assets/img/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Nunito:300,400,600,700|Poppins:300,400,500,600,700"
        rel="stylesheet">

    <!-- Vendor CSS (NiceAdmin) -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/quill/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/quill/quill.bubble.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/simple-datatables/style.css') }}">

    <!-- Template Main CSS (NiceAdmin) -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <style>
        .progress-sm {
            height: .5rem
        }

        .img-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: .5rem;
            border: 1px solid #e9ecef
        }

        .video-frame {
            width: 100%;
            background: #000;
            border-radius: .5rem
        }

        .kanban {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem
        }

        @media (max-width:992px) {
            .kanban {
                grid-template-columns: 1fr
            }
        }
    </style>

    @stack('head')
</head>

<body>
    @php
        // ===== Helpers aktif menu & expand submenu =====
        if (!function_exists('navActive')) {
            function navActive($patterns = [], $classWhenActive = 'active')
            {
                foreach ((array) $patterns as $p) {
                    if (request()->routeIs($p) || request()->is($p)) {
                        return $classWhenActive;
                    }
                }
                return '';
            }
        }
        if (!function_exists('navExpanded')) {
            function navExpanded($patterns = [])
            {
                foreach ((array) $patterns as $p) {
                    if (request()->routeIs($p) || request()->is($p)) {
                        return 'show';
                    }
                }
                return '';
            }
        }
        if (!function_exists('navCollapsed')) {
            function navCollapsed($patterns = [])
            {
                foreach ((array) $patterns as $p) {
                    if (request()->routeIs($p) || request()->is($p)) {
                        return '';
                    }
                }
                return 'collapsed';
            }
        }
    @endphp

    <!-- ======= Header ======= -->
    <header id="header" class="header fixed-top d-flex align-items-center">
        <div class="d-flex align-items-center justify-content-between">
            <a href="{{ route('dashboard') }}" class="logo d-flex align-items-center">
                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo">
                <span class="d-none d-lg-block ms-2">Trans Jatim</span>
            </a>
            <i class="bi bi-list toggle-sidebar-btn"></i>
        </div>

        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">

                <li class="nav-item d-block d-lg-none">
                    <a class="nav-link nav-icon search-bar-toggle" href="{{ route('dashboard') }}">
                        <i class="bi bi-search"></i>
                    </a>
                </li>

                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <img src="{{ asset('assets/img/profile-img.jpg') }}" alt="Profile" class="rounded-circle">
                        <span class="d-none d-md-block dropdown-toggle ps-2">
                            {{ auth()->user()->name ?? 'User' }}
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                        <li class="dropdown-header">
                            <h6>{{ auth()->user()->name ?? 'User' }}</h6>
                            <span class="text-danger small">Super Admin</span>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf
                            </form>
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right"></i><span class="ms-2">Logout</span>
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
        </nav>
    </header>
    <!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <aside id="sidebar" class="sidebar">
        <ul class="sidebar-nav" id="sidebar-nav">

            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link {{ navCollapsed(['dashboard', '/dashboard']) }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-grid"></i><span>Dashboard</span>
                </a>
            </li>

            <!-- Tambah Task -->
            <li class="nav-item">
                <a class="nav-link {{ navCollapsed(['admin/tambahJobdesk*', 'admin/penugasan*']) }}"
                    data-bs-target="#menu-add-task" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-calendar-plus"></i><span>Tambah Task</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="menu-add-task"
                    class="nav-content collapse {{ navExpanded(['admin/tambahJobdesk*', 'admin/penugasan*']) }}"
                    data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="{{ navActive(['admin/tambahJobdesk*']) }}"
                            href="{{ route('admin.tambahJobdesk.create') }}">
                            <i class="bi bi-circle"></i><span>Tambah Jobdesk</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ navActive(['admin/penugasan*', 'admin.penugasan.*']) }}"
                            href="{{ route('admin.penugasan.index') }}">
                            <i class="bi bi-circle"></i><span>Tambah Penugasan</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Penjadwalan -->
            <li class="nav-item">
                <a class="nav-link {{ navCollapsed(['admin/jadwal*']) }}" data-bs-target="#menu-schedule"
                    data-bs-toggle="collapse" href="#">
                    <i class="bi bi-calendar-event"></i><span>Penjadwalan</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="menu-schedule" class="nav-content collapse {{ navExpanded(['admin/jadwal*']) }}"
                    data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="{{ navActive(['admin/jadwal*']) }}" href="{{ url('/admin/jadwal') }}">
                            <i class="bi bi-circle"></i><span>Jadwal & Absensi</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Laporan & Pekerjaan -->
            <li class="nav-item">
                <a class="nav-link {{ navCollapsed(['admin/jobdesk*', 'admin/laporan*']) }}"
                    data-bs-target="#menu-report" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-clipboard-data"></i><span>Laporan & Pekerjaan</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="menu-report"
                    class="nav-content collapse {{ navExpanded(['admin/jobdesk*', 'admin/laporan*']) }}"
                    data-bs-parent="#sidebar-nav">

                    {{-- Detail Pekerjaan --}}
                    <li>
                        <a class="{{ navActive(['admin/laporan/harian*']) }}"
                            href="{{ url('/admin/laporan/harian') }}">
                            <i class="bi bi-circle"></i><span>Detail Pekerjaan</span>
                        </a>
                    </li>

                    {{-- Rekap Pekerjaan --}}
                    <li>
                        <a class="{{ navActive(['admin/laporan/bulanan*']) }}"
                            href="{{ url('/admin/laporan/bulanan') }}">
                            <i class="bi bi-circle"></i><span>Rekap Pekerjaan</span>
                        </a>
                    </li>

                    {{-- Monitoring Proyek --}}
                    <li>
                        <a class="{{ navActive(['admin/jobdesk', 'admin/jobdesk/index', 'admin/jobdesk?*']) }}"
                            href="{{ url('/admin/jobdesk') }}">
                            <i class="bi bi-circle"></i><span>Monitoring Proyek</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Manajemen Pengguna -->
            <li class="nav-item">
                <a class="nav-link {{ navCollapsed(['admin/karyawan*', 'admin/roles*']) }}"
                    data-bs-target="#menu-users" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-people"></i><span>Manajemen Pengguna</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="menu-users"
                    class="nav-content collapse {{ navExpanded(['admin/karyawan*', 'admin/roles*']) }}"
                    data-bs-parent="#sidebar-nav">
                    <li><a class="{{ navActive(['admin/karyawan*']) }}" href="{{ url('/admin/karyawan') }}"><i
                                class="bi bi-circle"></i><span>Total Teams</span></a></li>
                    <li><a class="{{ navActive(['admin/roles*']) }}" href="{{ url('/admin/roles') }}"><i
                                class="bi bi-circle"></i><span>Role &amp; Akses</span></a></li>
                </ul>
            </li>
        </ul>
    </aside>
    <!-- End Sidebar -->

    <main id="main" class="main">
        {{-- Global Alerts --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-1"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-octagon me-1"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- ======= Footer ======= -->
    <!-- <footer id="footer" class="footer">
        <div class="copyright">
            &copy; <strong><span>Trans Jatim</span></strong>. All Rights Reserved
        </div>
        <div class="credits">
            UI berbasis NiceAdmin (Bootstrap).
        </div>
    </footer> -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS -->
    <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
    <script src="{{ asset('assets/vendor/quill/quill.min.js') }}"></script>

    <!-- Template Main JS -->
    <script src="{{ asset('assets/js/main.js') }}"></script>

    @stack('scripts')
</body>

</html>
