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
        body {
            font-size: 14px;
        }

        /* ===== Layout ===== */
        .layout-wrapper {
            display: flex;
        }

        /* ===== Sidebar ===== */
        .sidebar{
  position: fixed;
  top: 0;
  left: 0;
  width: 280px;           /* you can keep 250 */
  height: 100vh;
  background: #343a40;
  overflow-y: auto;
  z-index: 1040;

  transform: none;        /* IMPORTANT: no slide hide on desktop */
  transition: none;
}

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar a {
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }

        .sidebar a:hover {
            background: #495057;
        }

        /* ===== Sidebar Inner ===== */
        .sidebar-inner {
            padding: 1rem;
        }

        /* ===== Backdrop ===== */
       .sidebar-backdrop{ display:none; }

        .sidebar-backdrop.show {
            display: block;
        }

        /* ===== Main Area ===== */
        .main-area{
  width: 100%;
  margin-left: 280px;     /* must match sidebar width */
  transition: margin-left 0.25s ease-in-out;
}

        .main-area.shifted {
            margin-left: 250px;
        }

        /* ===== Header ===== */
        .top-header {
            background: #212529;
            color: #fff;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ===== Mobile behavior (sidebar becomes drawer) ===== */
@media (max-width: 992px){
  .sidebar{
    width: 280px;
    transform: translateX(-100%);
    transition: transform 0.25s ease-in-out;
  }
  .sidebar.open{
    transform: translateX(0);
  }

  .main-area{
    margin-left: 0;
  }

  .sidebar-backdrop{
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    z-index: 1039;
    display: none;
  }
  .sidebar-backdrop.show{ display:block; }
}

        /* Mobile */
        @media (max-width: 768px) {
            .main-area.shifted {
                margin-left: 0;
            }
        }

        /* ===== Data Grid (WMS style) ===== */
.table-scroll-x {
    overflow-x: auto;
    white-space: nowrap;
}

.table-wms {
    min-width: 1800px; /* forces horizontal scroll */
}

.table-wms th,
.table-wms td {
    vertical-align: middle;
    padding: 0.5rem 0.75rem;
}

/* Wider text columns */
.col-wide {
    min-width: 150px;
}

.col-medium {
    min-width: 100px;
}

.col-small {
    min-width: 50px;
}

/* Sticky header */
.table-wms thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8f9fa;
}

/* Numbers alignment */
.text-number {
    text-align: right;
    font-variant-numeric: tabular-nums;
}


/* Added some style */
/* ===== Header (Upgraded) ===== */
.top-header {
    position: sticky;
    top: 0;
    z-index: 1050;
    background: rgba(33, 37, 41, 0.92);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: #fff;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-title {
    font-size: 14px;
    letter-spacing: 0.2px;
}

.header-subtitle {
    font-size: 11px;
    margin-top: 2px;
}

.header-icon-btn {
    border-radius: 999px;
    padding: 0.35rem 0.65rem;
}

.header-user-btn {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    color: #fff;
    border-radius: 999px;
    padding: 0.35rem 0.6rem;
}

.header-user-btn:hover {
    background: rgba(255,255,255,0.14);
}

.header-avatar {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,0.25);
}

.header-avatar-sm {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    object-fit: cover;
}

.header-user-name {
    font-size: 13px;
    font-weight: 700;
    line-height: 1.1;
}

.header-user-role {
    font-size: 11px;
    opacity: 0.75;
    line-height: 1.1;
}

/* Mobile fine-tuning */
@media (max-width: 576px) {
    .top-header {
        padding: 0.65rem 0.8rem;
    }
}

    </style>
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
       {{-- Header --}}
<div class="top-header">
    <div class="d-flex align-items-center gap-2">
        <button id="sidebarToggle" class="btn btn-outline-light btn-sm header-icon-btn">
            ☰
        </button>

        <div class="d-flex flex-column lh-1">
            <span class="fw-semibold header-title">Warehouse Management System</span>
            <small class="text-white-50 header-subtitle">Dashboard</small>
        </div>
    </div>

    <div class="ms-auto d-flex align-items-center gap-2">

        <!-- Notification (dummy) -->
        <button class="btn btn-outline-light btn-sm header-icon-btn position-relative">
            🔔
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                3
            </span>
        </button>

        <!-- User Dropdown -->
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
                <li><a class="dropdown-item py-2" href="#">👤 Profile</a></li>
                <li><a class="dropdown-item py-2" href="#">⚙️ Settings</a></li>
                <li><hr class="dropdown-divider my-0"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-danger border-0 bg-transparent" style="width: 100%; text-align: left;">
                            🚪 Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
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
