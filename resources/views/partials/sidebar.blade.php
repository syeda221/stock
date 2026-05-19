<div class="sidebar-brand mb-3">
    <div class="d-flex align-items-center gap-2">
        <div class="brand-badge">WMS</div>
        <div>
            <div class="brand-title">Admin Panel</div>
            <div class="brand-subtitle">Warehouse Management</div>
        </div>
    </div>
</div>

<ul class="nav flex-column gap-1 sidebar-nav">

    {{-- Dashboard --}}
    <li class="nav-item">
        <a href="{{ route('dashboard') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2 me-2"></i>
            <span>Dashboard</span>
        </a>
    </li>

    {{-- Masters --}}
    @canany(['uom-list', 'packing-type-list', 'product-category-list', 'product-group-list', 'product-list'])
    <li class="sidebar-section mt-3">Masters</li>

    @can('uom-list')
    <li class="nav-item">
        <a href="{{ route('uom.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('uom.*') ? 'active' : '' }}">
            <i class="bi bi-rulers me-2"></i>
            <span>UOM</span>
        </a>
    </li>
    @endcan

    @can('packing-type-list')
    <li class="nav-item">
        <a href="{{ route('packing-type.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('packing-type.*') ? 'active' : '' }}">
            <i class="bi bi-box-seam me-2"></i>
            <span>Packing Type</span>
        </a>
    </li>
    @endcan

    @can('product-category-list')
    <li class="nav-item">
        <a href="{{ route('product-category.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('product-category.*') ? 'active' : '' }}">
            <i class="bi bi-tags me-2"></i>
            <span>Product Category</span>
        </a>
    </li>
    @endcan

    @can('product-group-list')
    <li class="nav-item">
        <a href="{{ route('product-group.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('product-group.*') ? 'active' : '' }}">
            <i class="bi bi-collection me-2"></i>
            <span>Product Group</span>
        </a>
    </li>
    @endcan

    @can('product-list')
    <li class="nav-item">
        <a href="{{ route('product.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('product.*') ? 'active' : '' }}">
            <i class="bi bi-basket2 me-2"></i>
            <span>Products</span>
        </a>
    </li>
    @endcan
    @endcanany

    {{-- Inventory --}}
    @canany(['warehouse-list', 'opening-stock-list', 'inbound-list', 'outbound-list'])
    <li class="sidebar-section mt-3">Inventory</li>

    @can('warehouse-list')
    <li class="nav-item">
        <a href="{{ route('warehouse.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('warehouse.*') ? 'active' : '' }}">
            <i class="bi bi-buildings me-2"></i>
            <span>Warehouses</span>
        </a>
    </li>
    @endcan

    @can('opening-stock-list')
    <li class="nav-item">
        <a href="{{ route('opening-stock.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('opening-stock.*') ? 'active' : '' }}">
            <i class="bi bi-box-arrow-in-down me-2"></i>
            <span>Opening Stock</span>
        </a>
    </li>
    @endcan

    @can('inbound-list')
    <li class="nav-item">
        <a href="{{ route('inbound.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('inbound.*') ? 'active' : '' }}">
            <i class="bi bi-arrow-down-circle me-2"></i>
            <span>Inbound</span>
        </a>
    </li>
    @endcan

    @can('outbound-list')
    <li class="nav-item">
        <a href="{{ route('outbound.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('outbound.*') ? 'active' : '' }}">
            <i class="bi bi-arrow-up-circle me-2"></i>
            <span>Outbound</span>
        </a>
    </li>
    @endcan
    @endcanany

    {{-- Parties / Logistics --}}
    @canany(['vendor-list', 'customer-list', 'transporter-list', 'arrived-from-list'])
    <li class="sidebar-section mt-3">Parties / Logistics</li>

    @can('vendor-list')
    <li class="nav-item">
        <a href="{{ route('vendor.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('vendor.*') ? 'active' : '' }}">
            <i class="bi bi-person-badge me-2"></i>
            <span>Vendors</span>
        </a>
    </li>
    @endcan

    @can('customer-list')
    <li class="nav-item">
        <a href="{{ route('customer.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('customer.*') ? 'active' : '' }}">
            <i class="bi bi-people me-2"></i>
            <span>Customers</span>
        </a>
    </li>
    @endcan

    @can('transporter-list')
    <li class="nav-item">
        <a href="{{ route('transporter.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('transporter.*') ? 'active' : '' }}">
            <i class="bi bi-truck me-2"></i>
            <span>Transporters</span>
        </a>
    </li>
    @endcan

    @can('arrived-from-list')
    <li class="nav-item">
        <a href="{{ route('arrived-from.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('arrived-from.*') ? 'active' : '' }}">
            <i class="bi bi-geo-alt me-2"></i>
            <span>Arrived From</span>
        </a>
    </li>
    @endcan
    @endcanany

    {{-- User Management --}}
    @canany(['user-list', 'role-list'])
    <li class="sidebar-section mt-3">User Management</li>

    @can('user-list')
    <li class="nav-item">
        <a href="{{ route('users.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i class="bi bi-people-fill me-2"></i>
            <span>Users</span>
        </a>
    </li>
    @endcan

    @can('role-list')
    <li class="nav-item">
        <a href="{{ route('roles.index') }}"
           class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
            <i class="bi bi-shield-lock me-2"></i>
            <span>Roles & Permissions</span>
        </a>
    </li>
    @endcan
    @endcanany

    {{-- Reports --}}
    @canany(['report-inbound', 'report-outbound', 'report-warehouse-stock', 'report-warehouse-capacity', 'report-all-stocks', 'report-stock-ledger'])
    <li class="sidebar-section mt-3">Reports</li>

    <li class="nav-item">
        <a class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#reportsCollapse"
           role="button"
           aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}"
           aria-controls="reportsCollapse">
            <i class="bi bi-file-bar-graph me-2"></i>
            <span>Reports</span>
            <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <div class="collapse {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="reportsCollapse">
            <ul class="nav flex-column ms-3">
                
                {{-- Stock Reports Sub-section --}}
                @canany(['report-all-stocks', 'report-stock-ledger', 'report-warehouse-stock', 'report-warehouse-capacity'])
                <li class="nav-item mt-2">
                    <small class="text-white-50 px-3 text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                        <i class="bi bi-database me-1"></i> Stock Reports
                    </small>
                </li>
                @endcanany
                
                @can('report-all-stocks')
                <li class="nav-item">
                    <a href="{{ route('reports.all-stocks') }}"
                       class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('reports.all-stocks*') ? 'active' : '' }}">
                        <i class="bi bi-box-seam me-2"></i>
                        <span>All Stocks Overview</span>
                    </a>
                </li>
                @endcan
                
                @can('report-stock-ledger')
                <li class="nav-item">
                    <a href="{{ route('reports.stock-ledger') }}"
                       class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('reports.stock-ledger*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text me-2"></i>
                        <span>Stock Ledger</span>
                    </a>
                </li>
                @endcan
                
                @can('report-warehouse-stock')
                <li class="nav-item">
                    <a href="{{ route('reports.warehouse-stock') }}"
                       class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('reports.warehouse-stock*') ? 'active' : '' }}">
                        <i class="bi bi-buildings me-2"></i>
                        <span>Warehouse Stock</span>
                    </a>
                </li>
                @endcan
                
                @can('report-warehouse-capacity')
                <li class="nav-item">
                    <a href="{{ route('reports.warehouse-capacity') }}"
                       class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('reports.warehouse-capacity*') ? 'active' : '' }}">
                        <i class="bi bi-layout-text-window-reverse me-2"></i>
                        <span>Warehouse Capacity</span>
                    </a>
                </li>
                @endcan
                
                {{-- Transaction Reports Sub-section --}}
                @canany(['report-inbound', 'report-outbound'])
                <li class="nav-item mt-3">
                    <small class="text-white-50 px-3 text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                        <i class="bi bi-arrow-left-right me-1"></i> Transactions
                    </small>
                </li>
                @endcanany
                
                @can('report-inbound')
                <li class="nav-item">
                    <a href="{{ route('reports.inbound') }}"
                       class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('reports.inbound*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-down-circle me-2"></i>
                        <span>Inbound Report</span>
                    </a>
                </li>
                @endcan
                
                @can('report-outbound')
                <li class="nav-item">
                    <a href="{{ route('reports.outbound') }}"
                       class="nav-link px-3 py-2 sidebar-link {{ request()->routeIs('reports.outbound*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-up-circle me-2"></i>
                        <span>Outbound Report</span>
                    </a>
                </li>
                @endcan
            </ul>
        </div>
    </li>
    @endcanany

</ul>

<style>
/* ===== Sidebar upgraded ===== */
.sidebar {
    background: linear-gradient(180deg, #1f2328 0%, #2b3036 100%);
    border-right: 1px solid rgba(255,255,255,0.06);
}

.sidebar-brand {
    padding: 0.25rem 0.25rem 0.75rem 0.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.07);
}

.brand-badge {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    font-weight: 800;
    letter-spacing: 0.5px;
    color: #fff;
    background: linear-gradient(135deg, #0d6efd, #6f42c1);
    box-shadow: 0 10px 22px rgba(13,110,253,0.18);
}

.brand-title {
    color: #fff;
    font-weight: 800;
    font-size: 14px;
    line-height: 1.1;
}

.brand-subtitle {
    color: rgba(255,255,255,0.55);
    font-size: 11px;
    margin-top: 2px;
}

.sidebar-section {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: rgba(255,255,255,0.45);
    padding: 0.25rem 0.5rem;
}

.sidebar-link {
    color: rgba(255,255,255,0.90) !important;
    border-radius: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.18s ease-in-out;
}

.sidebar-link:hover {
    background: rgba(255,255,255,0.08);
    transform: translateX(2px);
}

.sidebar-link.active {
    background: rgba(13,110,253,0.18);
    border: 1px solid rgba(13,110,253,0.30);
    box-shadow: 0 8px 18px rgba(13,110,253,0.12);
}

.sidebar-link i {
    font-size: 1.05rem;
    opacity: 0.95;
}

/* Collapsible menu styles */
.sidebar-link .bi-chevron-down {
    transition: transform 0.3s ease;
    font-size: 0.85rem;
}

.sidebar-link[aria-expanded="true"] .bi-chevron-down {
    transform: rotate(180deg);
}

.collapse .nav-link {
    font-size: 0.9rem;
    padding-left: 1.5rem !important;
}

.collapse .nav-link:hover {
    background: rgba(255,255,255,0.06);
}
</style>
