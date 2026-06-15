@extends('layouts.app')

@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h4 class="fw-bold mb-1" style="color: #1e293b;">Dashboard</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 13px;">
                        <li class="breadcrumb-item active" aria-current="page">Overview</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span style="font-size: 13px; color: #64748b;">{{ date('d/m/Y') }}</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    @if($lowStockCount > 0 || $expiringCount > 0 || $qcPending > 0 || $expired > 0)
    <div class="col-12">
        <div class="row g-2">
            @if($lowStockCount > 0)
            <div class="col-md-3 col-6">
                <div class="alert-banner d-flex align-items-center gap-2 px-3 py-2" style="font-size: 13px; background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><strong>{{ $lowStockCount }}</strong> low stock items</span>
                </div>
            </div>
            @endif
            @if($expiringCount > 0)
            <div class="col-md-6 col-8">
                <div class="alert-banner d-flex align-items-center gap-2 px-3 py-2" style="font-size: 13px; background: linear-gradient(135deg, #cffafe, #a5f3fc); color: #155e75;">
                    <i class="bi bi-clock-fill"></i>
                    <span><strong>{{ $expiringCount }}</strong> expiring soon</span>
                </div>
            </div>
            @endif
            @if($qcPending > 0)
            <div class="col-md-3 col-6">
                <div class="alert-banner d-flex align-items-center gap-2 px-3 py-2" style="font-size: 13px; background: linear-gradient(135deg, #e2e8f0, #cbd5e1); color: #334155;">
                    <i class="bi bi-clipboard-check"></i>
                    <span><strong>{{ $qcPending }}</strong> QC pending</span>
                </div>
            </div>
            @endif
            @if($expired > 0)
            <div class="col-md-3 col-6">
                <div class="alert-banner d-flex align-items-center gap-2 px-3 py-2" style="font-size: 13px; background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b;">
                    <i class="bi bi-x-circle-fill"></i>
                    <span><strong>{{ $expired }}</strong> expired</span>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="col-12">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1" style="font-size: 11px; letter-spacing: 0.8px; font-weight: 600;">INBOUND</p>
                                <h3 class="fw-bold mb-0" style="color: #2563eb;">{{ $inboundCount }}</h3>
                                <small class="text-muted">{{ $inboundItems }} items &bull; {{ $inboundTotal ?? 0 }} units</small>
                            </div>
                            <div style="width: 52px; height: 52px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-box-arrow-in-down" style="font-size: 24px; color: #2563eb;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1" style="font-size: 11px; letter-spacing: 0.8px; font-weight: 600;">OUTBOUND</p>
                                <h3 class="fw-bold mb-0" style="color: #dc2626;">{{ $outboundCount }}</h3>
                                <small class="text-muted">{{ $outboundItems }} items &bull; {{ $outboundTotal ?? 0 }} units</small>
                            </div>
                            <div style="width: 52px; height: 52px; background: linear-gradient(135deg, #fee2e2, #fecaca); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-box-arrow-up" style="font-size: 24px; color: #dc2626;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1" style="font-size: 11px; letter-spacing: 0.8px; font-weight: 600;">TOTAL</p>
                                <h3 class="fw-bold mb-0" style="color: #16a34a;">{{ $totalTransactions }}</h3>
                                <small class="text-muted">{{ $totalItems }} items &bull; {{ $totalQty }} units</small>
                            </div>
                            <div style="width: 52px; height: 52px; background: linear-gradient(135deg, #dcfce7, #bbf7d0); border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-bar-chart-fill" style="font-size: 24px; color: #16a34a;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">7-Day Activity</h6>
                <div style="font-size: 12px; color: #64748b;">
                    <span class="me-3"><i class="bi bi-circle-fill text-primary me-1" style="font-size: 8px;"></i>Inbound</span>
                    <span><i class="bi bi-circle-fill text-danger me-1" style="font-size: 8px;"></i>Outbound</span>
                </div>
            </div>
            <div class="card-body px-3 pt-0">
                <canvas id="activityChart" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">This Month vs Last Month</h6>
            </div>
            <div class="card-body px-3 pt-0">
                <div style="height: 180px; position: relative;">
                    <canvas id="monthChart"></canvas>
                </div>
                <div class="row g-1 mt-2 pt-2 border-top text-center">
                    <div class="col-6">
                        <small class="text-muted d-block" style="font-size: 11px;">This Month In</small>
                        <span class="fw-bold" style="font-size: 14px; color: #0d6efd;">{{ $currentMonthIn }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block" style="font-size: 11px;">This Month Out</small>
                        <span class="fw-bold" style="font-size: 14px; color: #dc3545;">{{ $currentMonthOut }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">Entities</h6>
            </div>
            <div class="card-body pt-0 px-3" style="height: 300px; overflow-y: auto;">
                <div style="height: 140px; position: relative;">
                    <canvas id="entitiesChart"></canvas>
                </div>
                <div class="row g-1 mt-2 pt-2 border-top">
                    @foreach(['Products' => $stats['products'], 'Warehouses' => $stats['warehouses'], 'Vendors' => $stats['vendors'], 'Customers' => $stats['customers']] as $label => $count)
                    <div class="col-6">
                        <small class="text-muted d-block" style="font-size: 11px;">{{ $label }}</small>
                        <span class="fw-bold" style="font-size: 14px;">{{ $count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">Warehouse Capacity</h6>
            </div>
            <div class="card-body px-3 pt-0" style="height: 300px; overflow-y: auto;">
                @if($warehouseCapacity->count() > 0)
                    <div class="d-flex flex-column gap-2 mt-2">
                        @foreach($warehouseCapacity as $wc)
                        <div>
                            <div class="d-flex justify-content-between" style="font-size: 12px;">
                                <span>{{ Str::limit($wc['name'], 16) }}</span>
                                <span class="text-muted">{{ $wc['used'] }}/{{ $wc['total'] }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar rounded-pill {{ $wc['pct'] >= 90 ? 'bg-danger' : ($wc['pct'] >= 70 ? 'bg-warning' : 'bg-success') }}"
                                     style="width: {{ $wc['pct'] }}%;" role="progressbar"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center py-3" style="font-size: 13px;">No warehouse data</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">Top Vendors</h6>
            </div>
            <div class="card-body px-3 pt-0" style="height: 300px; overflow-y: auto;">
                @if($topVendors->count() > 0)
                    <div class="d-flex flex-column gap-1 mt-2">
                        @foreach($topVendors as $v)
                        <div class="d-flex justify-content-between align-items-center" style="font-size: 13px;">
                            <span>{{ Str::limit($v->name, 22) }}</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill">{{ $v->stock_ins_count }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center py-3" style="font-size: 13px;">No vendors yet</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">Top Customers</h6>
            </div>
            <div class="card-body px-3 pt-0" style="height: 300px; overflow-y: auto;">
                @if($topCustomers->count() > 0)
                    <div class="d-flex flex-column gap-1 mt-2">
                        @foreach($topCustomers as $c)
                        <div class="d-flex justify-content-between align-items-center" style="font-size: 13px;">
                            <span>{{ Str::limit($c->name, 22) }}</span>
                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill">{{ $c->stock_outs_count }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center py-3" style="font-size: 13px;">No customers yet</p>
                @endif
            </div>
        </div>
    </div>

    @if($lowStockItems->count() > 0)
    <div class="col-md-6">
        <div class="card h-100" style="border-left: 4px solid #f59e0b;">
            <div class="card-header border-bottom-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Low Stock</h6>
                <span class="badge text-bg-warning bg-opacity-10" style="font-size: 11px; color: #92400e; background: #fef3c7;">{{ $lowStockCount }} items</span>
            </div>
            <div class="p-0" style="height: 300px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Warehouse</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockItems as $item)
                            <tr>
                                <td>{{ Str::limit($item->product->name ?? 'N/A', 20) }}</td>
                                <td>{{ Str::limit($item->warehouse->name ?? 'N/A', 15) }}</td>
                                <td class="text-end fw-semibold" style="color: #dc2626;">{{ $item->balance_quantity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($expiringSoon->count() > 0)
    <div class="col-md-6">
        <div class="card h-100" style="border-left: 4px solid #0891b2;">
            <div class="card-header border-bottom-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0"><i class="bi bi-clock text-info me-1"></i>Expiring Soon</h6>
                <span class="badge" style="font-size: 11px; color: #155e75; background: #cffafe;">{{ $expiringCount }} items</span>
            </div>
            <div class="p-0" style="height: 300px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Warehouse</th>
                                <th>Expiry</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiringSoon as $item)
                            <tr>
                                <td>{{ Str::limit($item->product->name ?? 'N/A', 18) }}</td>
                                <td>{{ Str::limit($item->warehouse->name ?? 'N/A', 12) }}</td>
                                <td>{{ optional($item->expiry_date)->format('d/m/Y') }}</td>
                                <td class="text-end fw-semibold">{{ $item->balance_quantity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-md-6">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">Recent Inbound</h6>
                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 11px;">{{ $recentInbound->count() }} entries</span>
            </div>
            <div class="card-body p-0">
                @if($recentInbound->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">Invoice</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">Warehouse</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentInbound as $inbound)
                                    <tr>
                                        <td style="padding: 0.5rem 0.75rem; color: #0d6efd;">{{ $inbound->inbound_invoice_no ?? 'N/A' }}</td>
                                        <td style="padding: 0.5rem 0.75rem;">{{ Str::limit($inbound->warehouse->name ?? 'N/A', 20) }}</td>
                                        <td style="padding: 0.5rem 0.75rem; color: #64748b;">{{ $inbound->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted mb-0" style="font-size: 13px;">No inbound transactions yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">Recent Outbound</h6>
                <span class="badge bg-danger bg-opacity-10 text-danger" style="font-size: 11px;">{{ $recentOutbound->count() }} entries</span>
            </div>
            <div class="card-body p-0">
                @if($recentOutbound->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">Invoice</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">Customer</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOutbound as $outbound)
                                    <tr>
                                        <td style="padding: 0.5rem 0.75rem; color: #dc3545;">#{{ $outbound->dispatched_invoice_no ?? 'N/A' }}</td>
                                        <td style="padding: 0.5rem 0.75rem;">{{ Str::limit($outbound->customer->name ?? 'N/A', 20) }}</td>
                                        <td style="padding: 0.5rem 0.75rem; color: #64748b;">{{ $outbound->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted mb-0" style="font-size: 13px;">No outbound transactions yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">Stock by Warehouse</h6>
            </div>
            <div class="card-body pt-0 px-3">
                @if($stockByWarehouse->count() > 0)
                    <div style="height: 160px; position: relative;">
                        <canvas id="warehouseStockChart"></canvas>
                    </div>
                    <div class="d-flex flex-column gap-1 mt-2 pt-2 border-top">
                        @foreach($stockByWarehouse as $sw)
                        <div class="d-flex justify-content-between" style="font-size: 12px;">
                            <span>{{ Str::limit($sw['name'], 18) }}</span>
                            <span class="fw-semibold">{{ $sw['qty'] }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center py-3" style="font-size: 13px;">No stock data</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">Product Categories</h6>
            </div>
            <div class="card-body pt-0 px-3">
                @if($categoryBreakdown->count() > 0)
                    <div style="height: 140px; position: relative;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="d-flex flex-column gap-1 mt-2 pt-2 border-top">
                        @foreach($categoryBreakdown as $cat)
                        <div class="d-flex justify-content-between" style="font-size: 12px;">
                            <span>{{ Str::limit($cat->name, 20) }}</span>
                            <span class="fw-semibold">{{ $cat->products_count }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center py-3" style="font-size: 13px;">No categories</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">Top Products by Stock</h6>
            </div>
            <div class="card-body px-3 pt-0">
                @if($topProducts->count() > 0)
                    <div class="d-flex flex-column gap-2 mt-2">
                        @foreach($topProducts as $i => $tp)
                        <div>
                            <div class="d-flex justify-content-between" style="font-size: 12px;">
                                <span class="text-truncate" style="max-width: 160px;">{{ $tp['name'] }}</span>
                                <span class="fw-semibold">{{ $tp['qty'] }}</span>
                            </div>
                            <div class="progress" style="height: 4px;">
                                @php $barPct = $topProducts->max('qty') > 0 ? ($tp['qty'] / $topProducts->max('qty') * 100) : 0; @endphp
                                <div class="progress-bar rounded-pill bg-primary" style="width: {{ $barPct }}%;"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center py-3" style="font-size: 13px;">No products with stock</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0" style="color: #1e293b;">Recent Activity</h6>
            </div>
            <div class="card-body p-0">
                @if($recentFeed->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">Type</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">Reference</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">Location</th>
                                    <th style="padding: 0.5rem 0.75rem; font-weight: 600;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentFeed as $feed)
                                <tr>
                                    <td style="padding: 0.5rem 0.75rem;">
                                        @if($feed['type'] == 'inbound')
                                            <span class="badge bg-primary bg-opacity-10 text-primary">IN</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger">OUT</span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem;">{{ $feed['ref'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; color: #64748b;">{{ $feed['location'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; color: #64748b;">{{ $feed['date']->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted mb-0" style="font-size: 13px;">No recent activity</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const activityData = @json($last7Days);
    const stats = {
        products: {{ $stats['products'] }},
        warehouses: {{ $stats['warehouses'] }},
        vendors: {{ $stats['vendors'] }},
        customers: {{ $stats['customers'] }}
    };
    const stockByWarehouse = @json($stockByWarehouse);
    const categoryData = @json($categoryBreakdown);

    const activityCtx = document.getElementById('activityChart');
    if (activityCtx) {
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: activityData.map(d => d.date),
                datasets: [{
                    label: 'Inbound',
                    data: activityData.map(d => d.inbound),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#0d6efd',
                    borderWidth: 2
                }, {
                    label: 'Outbound',
                    data: activityData.map(d => d.outbound),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#dc3545',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    },
                    x: {
                        ticks: { font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    const monthCtx = document.getElementById('monthChart');
    if (monthCtx) {
        new Chart(monthCtx, {
            type: 'bar',
            data: {
                labels: ['Inbound', 'Outbound'],
                datasets: [
                    {
                        label: 'This Month',
                        data: [{{ $currentMonthIn }}, {{ $currentMonthOut }}],
                        backgroundColor: ['rgba(13, 110, 253, 0.7)', 'rgba(220, 53, 69, 0.7)'],
                        borderRadius: 4
                    },
                    {
                        label: 'Last Month',
                        data: [{{ $lastMonthIn }}, {{ $lastMonthOut }}],
                        backgroundColor: ['rgba(13, 110, 253, 0.2)', 'rgba(220, 53, 69, 0.2)'],
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { size: 10 }, usePointStyle: true, padding: 8 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 10 } },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    },
                    x: {
                        ticks: { font: { size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    const entitiesCtx = document.getElementById('entitiesChart');
    if (entitiesCtx) {
        new Chart(entitiesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Products', 'Warehouses', 'Vendors', 'Customers'],
                datasets: [{
                    data: [stats.products, stats.warehouses, stats.vendors, stats.customers],
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: { legend: { display: false } }
            }
        });
    }

    const warehouseStockCtx = document.getElementById('warehouseStockChart');
    if (warehouseStockCtx && stockByWarehouse.length) {
        new Chart(warehouseStockCtx, {
            type: 'doughnut',
            data: {
                labels: stockByWarehouse.map(d => d.name),
                datasets: [{
                    data: stockByWarehouse.map(d => d.qty),
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 9 }, usePointStyle: true, padding: 6 }
                    }
                }
            }
        });
    }

    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx && categoryData.length) {
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryData.map(d => d.name),
                datasets: [{
                    label: 'Products',
                    data: categoryData.map(d => d.products_count),
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 10 } },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    },
                    y: {
                        ticks: { font: { size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }
</script>
@endpush
