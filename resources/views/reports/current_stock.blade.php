@extends('layouts.app')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a>
                </li>
                <li class="breadcrumb-item">Reports</li>
                <li class="breadcrumb-item active" aria-current="page">Current Stock</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam text-success me-2"></i>Current Stock Report</h5>
        <small class="text-muted">Detailed view of active stock batches physically stored in warehouses</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.all-stocks') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-2"></i>Back to Summary
        </a>
        <a href="{{ route('reports.current-stock.export', request()->query()) }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10">
            <div class="card-body py-2 px-3 text-center">
                <h6 class="text-muted mb-1 small">Unique Products</h6>
                <h4 class="mb-0 fw-bold text-success">{{ number_format($summary['total_products']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10">
            <div class="card-body py-2 px-3 text-center">
                <h6 class="text-muted mb-1 small">Total Available Units</h6>
                <h4 class="mb-0 fw-bold text-primary">{{ number_format($summary['total_balance_units']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100 bg-info bg-opacity-10">
            <div class="card-body py-2 px-3 text-center">
                <h6 class="text-muted mb-1 small">Total Current Stock Qty</h6>
                <h4 class="mb-0 fw-bold text-info">{{ number_format($summary['total_balance_qty'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- Filter Section --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.current-stock') }}" class="mb-0">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Item code, name, batch...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary">Warehouse</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-secondary">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 py-2">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('reports.current-stock') }}" class="btn btn-sm btn-outline-secondary w-100 py-2">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Data Table --}}
<div class="card border-0 shadow-lg rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Item Code</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Warehouse</th>
                        <th>Location / Pallet</th>
                        <th>SAP Batch</th>
                        <th>Vendor Batch</th>
                        <th>PO #</th>
                        <th>IBD #</th>
                        <th class="text-center">MFG Date</th>
                        <th class="text-center">Expiry Date</th>
                        <th class="text-center">QC Status</th>
                        <th class="text-end bg-success bg-opacity-25">Available Units</th>
                        <th class="text-end bg-success bg-opacity-50">Balance Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $index => $item)
                        @php
                            $expiry = $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date) : null;
                            $daysToExpiry = $expiry ? now()->diffInDays($expiry, false) : null;
                            $units = $item->pack_size > 0 ? round($item->quantity / $item->pack_size) : 0;
                        @endphp
                        <tr>
                            <td class="text-muted fw-semibold">{{ $index + 1 }}</td>
                            <td><span class="badge bg-light text-dark fw-semibold">{{ $item->item_code }}</span></td>
                            <td class="fw-semibold text-dark">{{ $item->product_name }}</td>
                            <td><span class="text-muted small">{{ $item->category_name ?? '—' }}</span></td>
                            <td>{{ $item->warehouse_name }}</td>
                            <td class="fw-bold text-primary">{{ $item->warehouse_display }}</td>
                            <td><code class="text-primary font-monospace">{{ $item->sap_batch ?? '—' }}</code></td>
                            <td><code class="text-secondary font-monospace">{{ $item->vendor_batch ?? '—' }}</code></td>
                            <td><small>{{ $item->po_no ?? '—' }}</small></td>
                            <td><small>{{ $item->ibd_no ?? '—' }}</small></td>
                            <td class="text-center"><small>{{ $item->mfg_date ? \Carbon\Carbon::parse($item->mfg_date)->format('d.m.Y') : '—' }}</small></td>
                            <td class="text-center">
                                @if($expiry)
                                    @if($daysToExpiry < 0)
                                        <span class="badge bg-danger">Expired</span>
                                    @elseif($daysToExpiry <= 30)
                                        <span class="badge bg-warning text-dark">{{ $expiry->format('d.m.Y') }}</span>
                                    @elseif($daysToExpiry <= 90)
                                        <span class="badge bg-info">{{ $expiry->format('d.m.Y') }}</span>
                                    @else
                                        <span class="badge bg-success bg-opacity-25 text-success">{{ $expiry->format('d.m.Y') }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($item->quality_clearance == 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($item->quality_clearance == 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-success">{{ number_format($units) }}</td>
                            <td class="text-end fw-bold text-success font-monospace">{{ number_format($item->quantity, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No active stock found in warehouse rows
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
