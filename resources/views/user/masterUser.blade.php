<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Teams - Trans Jatim</title>
    <meta content="Dashboard Teams dengan jadwal, jobdesk, daily report, proyek & rekap bulanan" name="description">
    <meta content="trans jatim, Teams, dashboard, niceadmin" name="keywords">

    <!-- Favicons -->
    <link href="{{ asset('assets/img/favicon.png') }}" rel="icon">
    <link href="{{ asset('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Nunito:300,400,600,700|Poppins:300,400,500,600,700"
        rel="stylesheet">

    <!-- Vendor CSS Files (NiceAdmin) -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">

    <!-- Template Main CSS File (NiceAdmin) -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">

    <style>
        .progress-sm {
            height: .5rem;
        }

        .img-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: .5rem;
            border: 1px solid #e9ecef;
        }

        .video-frame {
            width: 100%;
            background: #000;
            border-radius: .5rem;
        }

        .kanban {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        @media (max-width: 992px) {
            .kanban {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    <!-- ======= Header ======= -->
    <header id="header" class="header fixed-top d-flex align-items-center">

        <div class="d-flex align-items-center justify-content-between">
            <a href="{{ route('dashboard.user') }}" class="logo d-flex align-items-center">
                <img src="{{ asset('assets/img/logo.png') }}" alt="">
                <span class="d-none d-lg-block">Trans Jatim</span>
            </a>
            <i class="bi bi-list toggle-sidebar-btn"></i>
        </div>

        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">

                <li class="nav-item d-block d-lg-none">
                    <a class="nav-link nav-icon search-bar-toggle" href="{{ route('dashboard.user') }}">
                        <i class="bi bi-search"></i>
                    </a>
                </li>

                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        {{-- ✅ Foto profil dinamis (storage) dengan fallback --}}
                        <img src="{{ Auth::user()?->avatar ? asset('storage/' . Auth::user()->avatar) : asset('assets/img/profile-img.jpg') }}"
                            alt="Profile" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                        <span class="d-none d-md-block dropdown-toggle ps-2" id="navUserName">
                            {{ Auth::user()->name ?? 'User' }}
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                        <li class="dropdown-header">
                            <h6 id="dropdownUser">{{ Auth::user()->name ?? 'User' }}</h6>
                            <span id="dropdownRole">Role : {{ Auth::user()->role ?? '-' }}</span>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <!-- ✅ Link ke halaman Profil -->
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.user') }}">
                                <i class="bi bi-person-circle"></i>
                                <span>Profil Saya</span>
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <!-- Logout -->
                        <li>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                                @csrf
                            </form>
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
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

            <li class="nav-item">
                <a class="nav-link " href="{{ route('dashboard.user') }}">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-heading">Teams</li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('jadwal.user') }}">
                    <i class="bi bi-calendar-event"></i><span>Absensi & Jadwal</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('jobdesk.user') }}">
                    <i class="bi bi-list-check"></i><span>List Jobdesk</span>
                </a>
            </li>

            <!-- ✅ Penugasan -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('penugasan.user') }}">
                    <i class="bi bi-clipboard-check"></i><span>List Penugasan</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('dailyreport.user') }}">
                    <i class="bi bi-journal-check"></i><span>Daily Summary</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('rekapBulanan.user') }}">
                    <i class="bi bi-clipboard-data"></i><span>Rekap Bulanan</span>
                </a>
            </li>
        </ul>
    </aside>
    <!-- End Sidebar-->

    <!-- ========= Main Content ========= -->
    <main id="main" class="main">
        {{-- Gunakan @section('content') di halaman --}}
        @yield('content')
    </main>

    <!-- ======= Footer ======= -->
    <!-- <footer id="footer" class="footer">
        <div class="copyright">
            &copy; <strong><span>Trans Jatim</span></strong>. All Rights Reserved
        </div>
    </footer> -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- ===== Vendor JS Files ===== -->
    <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>

    <!-- Template Main JS (NiceAdmin sidebar/toggles) -->
    <script src="{{ asset('assets/js/main.js') }}"></script>

    @stack('scripts')
</body>

</html>
