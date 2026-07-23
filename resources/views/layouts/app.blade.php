<!DOCTYPE html>
<html>
<head>
    <title>Warehouse Management System</title>
          <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --success: #16a34a;
            --success-light: #dcfce7;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #dc2626;
            --danger-light: #fee2e2;
            --info: #0891b2;
            --info-light: #cffafe;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #f1f5f9;
            --card-radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 25px rgba(0,0,0,0.12);
        }

        * { box-sizing: border-box; }

        body {
            font-size: 14px;
            background: #f8fafc;
            color: var(--dark);
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* ===== Layout ===== */
        .layout-wrapper { display: flex; }

        /* ===== Sidebar ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            overflow-y: auto;
            z-index: 1040;
            transform: none;
            transition: none;
        }
        .sidebar::-webkit-scrollbar { width: 10px; }
        .sidebar::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }

        .sidebar.open { transform: translateX(0); }

        .sidebar a {
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            display: block;
            transition: all 0.2s;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        .sidebar-inner { padding: 1rem; }

        /* ===== Backdrop ===== */
        .sidebar-backdrop { display: none; }
        .sidebar-backdrop.show { display: block; }

        /* ===== Main Area ===== */
        .main-area {
            flex: 1;
            margin-left: 280px;
            min-width: 0;
            transition: margin-left 0.25s ease-in-out;
        }
        .main-area.shifted { margin-left: 250px; }

        /* ===== Mobile ===== */
        @media (max-width: 992px) {
            .sidebar {
                width: 280px;
                transform: translateX(-100%);
                transition: transform 0.25s ease-in-out;
            }
            .sidebar.open { transform: translateX(0); }
            .main-area { width: 100%; margin-left: 0; }
            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(4px);
                z-index: 1039;
                display: none;
            }
            .sidebar-backdrop.show { display: block; }
        }
        @media (max-width: 768px) { .main-area.shifted { margin-left: 0; } }

        /* ===== Cards ===== */
        .card {
            border: none !important;
            border-radius: var(--card-radius) !important;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.04);
        }
        .card-body { color: var(--dark); }

        /* ===== Data Grid ===== */
        .table-scroll-x { overflow-x: auto; white-space: nowrap; }
        .table-wms { min-width: 1800px; }
        .table-wms th, .table-wms td { vertical-align: middle; padding: 0.5rem 0.75rem; }
        .col-wide { min-width: 150px; }
        .col-medium { min-width: 100px; }
        .col-small { min-width: 50px; }
        .table-wms thead th { position: sticky; top: 0; z-index: 2; background: #f1f5f9; }
        .text-number { text-align: right; font-variant-numeric: tabular-nums; }

        /* ===== Tables ===== */
        .table {
            --bs-table-hover-bg: #f8fafc;
        }
        .table thead th {
            background: #f8fafc;
            color: var(--gray);
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid #e2e8f0;
        }
        .table tbody tr {
            transition: background 0.15s;
        }
        .table tbody tr:hover {
            background: #f1f5f9;
        }
        .table tbody td {
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        /* ===== Progress Bars ===== */
        .progress {
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-bar {
            border-radius: 999px;
            transition: width 0.6s ease;
        }

        /* ===== Header ===== */
        .top-header {
            position: sticky;
            top: 0;
            z-index: 1050;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            color: #fff;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title { font-size: 14px; letter-spacing: 0.3px; }
        .header-subtitle { font-size: 11px; margin-top: 2px; opacity: 0.7; }
        .header-icon-btn { border-radius: 999px; padding: 0.35rem 0.65rem; border-color: rgba(255,255,255,0.2); transition: all 0.2s; }
        .header-icon-btn:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.3); }

        .header-user-btn {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 999px;
            padding: 0.35rem 0.6rem;
            transition: all 0.2s;
        }
        .header-user-btn:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.2);
        }

        .header-avatar { width: 34px; height: 34px; border-radius: 999px; object-fit: cover; border: 2px solid rgba(255,255,255,0.25); }
        .header-avatar-sm { width: 34px; height: 34px; border-radius: 999px; object-fit: cover; }
        .header-user-name { font-size: 13px; font-weight: 700; line-height: 1.1; }
        .header-user-role { font-size: 11px; opacity: 0.75; line-height: 1.1; }

        /* ===== Badge ===== */
        .badge {
            font-weight: 500;
            padding: 0.3em 0.6em;
        }

        /* ===== Breadcrumb ===== */
        .breadcrumb { margin: 0; }
        .breadcrumb-item { font-size: 13px; }
        .breadcrumb-item.active { color: var(--gray); }

        /* ===== Alert Banners ===== */
        .alert-banner {
            border-radius: 10px;
            transition: transform 0.2s;
        }
        .alert-banner:hover {
            transform: translateY(-1px);
        }

        @media (max-width: 576px) { .top-header { padding: 0.65rem 0.8rem; } }
    </style>
@stack('styles')
</head>

<body>

{{-- Sidebar --}}
<div id="sidebar" class="sidebar">
    <div class="sidebar-inner">
        @include('partials.sidebar')
    </div>
</div>

{{-- Backdrop --}}
<div id="sidebarBackdrop" class="sidebar-backdrop"></div>

<div class="layout-wrapper">

    {{-- Main Content Area --}}
    <div id="mainArea" class="main-area">

         {{-- Header --}}
        <div class="top-header">
            <div class="d-flex align-items-center gap-2">
                <button id="sidebarToggle" class="btn btn-outline-light btn-sm header-icon-btn">
                    <i class="bi bi-list"></i>
                </button>

                <div class="d-flex flex-column lh-1">
                    <span class="fw-semibold header-title">Warehouse Management System</span>
                    <small class="text-white-50 header-subtitle">Dashboard</small>
                </div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2 position-relative">
                @php
                    $user = auth()->user();
                    $userName = $user ? $user->name : 'Guest';
                    $userRole = $user && $user->roles->count() > 0 ? $user->roles->pluck('name')->implode(', ') : 'No Role';
                    $userImg  = "https://ui-avatars.com/api/?name=".urlencode($userName)."&background=random";
                @endphp

                <div class="dropdown">
                    <button class="btn header-user-btn dropdown-toggle d-flex align-items-center gap-2"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <img src="{{ $userImg }}" class="header-avatar" alt="User" />
                        <div class="text-start d-none d-sm-block">
                            <div class="header-user-name">{{ $userName }}</div>
                            <div class="header-user-role">{{ Str::limit($userRole, 15) }}</div>
                        </div>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 overflow-hidden">
                        <li class="px-3 py-2 bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $userImg }}" class="header-avatar-sm" alt="User" />
                                <div>
                                    <div class="fw-semibold">{{ $userName }}</div>
                                    <small class="text-muted">{{ $userRole }}</small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-0"></li>
                        <li><a class="dropdown-item py-2" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item py-2" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider my-0"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger border-0 bg-transparent w-100 text-start">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>


        {{-- Notifications --}}
        <div id="notificationBar" style="display:none;background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:6px 16px;">
            <div id="notificationContent" class="d-flex flex-wrap align-items-center gap-2" style="font-size:12px;"></div>
        </div>

        {{-- Page Content --}}
        <main class="p-4">
            @yield('content')
        </main>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var bar = document.getElementById('notificationBar');
    var container = document.getElementById('notificationContent');
    if (!bar || !container) return;

    function fetchAlerts() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/notifications', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onload = function() {
            if (xhr.status !== 200) return;
            try { var d = JSON.parse(xhr.responseText); } catch(e) { return; }
            if (!d.has_alerts) { bar.style.display = 'none'; return; }

            var h = '';
            if (d.low_stock > 0) h += '<a href="{{ route("opening-stock.index") }}" class="text-decoration-none d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#fef3c7;color:#92400e;"><i class="bi bi-exclamation-triangle-fill" style="font-size:11px;"></i><span><strong>' + d.low_stock + '</strong> low stock</span></a>';
            if (d.expiring > 0) h += '<span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#cffafe;color:#155e75;"><i class="bi bi-clock-fill" style="font-size:11px;"></i><span><strong>' + d.expiring + '</strong> expiring soon</span></span>';
            if (d.expired > 0) h += '<span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#fee2e2;color:#991b1b;"><i class="bi bi-x-circle-fill" style="font-size:11px;"></i><span><strong>' + d.expired + '</strong> expired</span></span>';
            if (d.qc_pending > 0) h += '<span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#e2e8f0;color:#334155;"><i class="bi bi-clipboard-check" style="font-size:11px;"></i><span><strong>' + d.qc_pending + '</strong> QC pending</span></span>';
            h += '<button onclick="this.closest(\'#notificationBar\').style.display=\'none\'" class="btn btn-sm p-0 border-0 ms-auto" style="font-size:14px;color:#94a3b8;" title="Dismiss">&times;</button>';

            container.innerHTML = h;
            bar.style.display = '';
        };
        xhr.send();
    }

    fetchAlerts();
    setInterval(fetchAlerts, 30000);
});
</script>

@stack('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {
  const sidebar   = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const backdrop  = document.getElementById('sidebarBackdrop');

  function isMobile() {
    return window.matchMedia('(max-width: 992px)').matches;
  }

  function openSidebar() {
    sidebar.classList.add('open');
    backdrop.classList.add('show');
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    backdrop.classList.remove('show');
  }

  toggleBtn.addEventListener('click', function () {
    // Desktop: do nothing (sidebar always visible)
    if (!isMobile()) return;

    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  backdrop.addEventListener('click', closeSidebar);

  // When switching between desktop/mobile, reset drawer state
  window.addEventListener('resize', () => {
    if (!isMobile()) closeSidebar();
  });
});
</script>


</body>
</html>
