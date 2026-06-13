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
                <li class="breadcrumb-item active" aria-current="page">Stock Ledger</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Stock Ledger</h5>
        <small class="text-muted">Detailed batch-level stock transactions with complete history</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-success" onclick="exportLedger()">
            <i class="bi bi-file-earmark-excel me-1"></i> Export
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white text-center py-3">
                <i class="bi bi-list-ol fs-4"></i>
                <h6 class="text-white-50 mb-1 small">Total Entries</h6>
                <h4 class="mb-0 fw-bold">{{ number_format($summary['total_entries']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body text-white text-center py-3">
                <i class="bi bi-arrow-down-circle fs-4"></i>
                <h6 class="text-white-50 mb-1 small">Total Inbound</h6>
                <h4 class="mb-0 fw-bold">{{ number_format($summary['total_inbound_qty'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%);">
            <div class="card-body text-white text-center py-3">
                <i class="bi bi-arrow-up-circle fs-4"></i>
                <h6 class="text-white-50 mb-1 small">Total Outbound</h6>
                <h4 class="mb-0 fw-bold">{{ number_format($summary['total_outbound_qty'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #0061ff 0%, #60efff 100%);">
            <div class="card-body text-white text-center py-3">
                <i class="bi bi-check-circle fs-4"></i>
                <h6 class="text-white-50 mb-1 small">Current Balance</h6>
                <h4 class="mb-0 fw-bold">{{ number_format($summary['total_balance'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #8e44ad 0%, #c0392b 100%);">
            <div class="card-body text-white text-center py-3">
                <i class="bi bi-box-seam fs-4"></i>
                <h6 class="text-white-50 mb-1 small">Unique Products</h6>
                <h4 class="mb-0 fw-bold">{{ number_format($summary['unique_products']) }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- Filter Section --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-3">
        <form id="ledgerFilterForm" method="GET" action="{{ route('reports.stock-ledger') }}">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" 
                           value="{{ request('search') }}" placeholder="Product, batch, invoice...">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Product</label>
                    <select name="product_id" class="form-select form-select-sm">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->item_code }} - {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Warehouse</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Type</label>
                    <select name="source_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="opening" {{ request('source_type') == 'opening' ? 'selected' : '' }}>Opening Stock</option>
                        <option value="inbound" {{ request('source_type') == 'inbound' ? 'selected' : '' }}>Inbound</option>
                        <option value="sale" {{ request('source_type') == 'sale' ? 'selected' : '' }}>Sale (Outbound)</option>
                        <option value="transfer" {{ request('source_type') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Vendor</label>
                    <select name="vendor_id" class="form-select form-select-sm">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Customer</label>
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('reports.stock-ledger') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Data Table --}}
<div class="card border-0 shadow-lg rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-dark">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th style="width:90px;">Date</th>
                        <th style="width:70px;" class="text-center">Type</th>
                        <th>Product</th>
                        <th>Warehouse</th>
                        <th>Batch</th>
                        <th>Invoice/Ref</th>
                        <th>Party</th>
                        <th class="text-end bg-success bg-opacity-50">IN</th>
                        <th class="text-end bg-danger bg-opacity-50">OUT</th>
                        <th class="text-end bg-info bg-opacity-50">Balance</th>
                        <th class="text-center">Expiry</th>
                        <th class="text-center" style="width:80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerPaginated as $index => $entry)
                        <tr>
                            <td class="text-muted fw-semibold">{{ $ledgerPaginated->firstItem() + $index }}</td>
                            <td>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($entry->created_at)->format('d M Y') }}</small>
                                <br>
                                <small class="text-muted opacity-75">{{ \Carbon\Carbon::parse($entry->created_at)->format('H:i') }}</small>
                            </td>
                            <td class="text-center">
                                @if($entry->direction == 'IN')
                                    @if($entry->source_type == 'opening')
                                        <span class="badge bg-secondary">Opening</span>
                                    @elseif($entry->source_type == 'transfer')
                                        <span class="badge bg-info">Transfer In</span>
                                    @else
                                        <span class="badge bg-success">Inbound</span>
                                    @endif
                                @else
                                    @if($entry->source_type == 'sale')
                                        <span class="badge bg-warning text-dark">Sale</span>
                                    @elseif($entry->source_type == 'transfer')
                                        <span class="badge bg-info">Transfer Out</span>
                                    @else
                                        <span class="badge bg-danger">Outbound</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $entry->product_name }}</div>
                                <small class="text-muted">
                                    <span class="badge bg-light text-dark">{{ $entry->item_code }}</span>
                                    {{ $entry->category_name ?? '' }}
                                </small>
                            </td>
                            <td>
                                <span class="text-primary fw-medium">{{ $entry->warehouse_name }}</span>
                                @if(!empty($entry->row_name))
                                    <br><small class="text-muted"><i class="bi bi-geo-alt"></i> Row: {{ $entry->row_name }}</small>
                                @endif
                                @if($entry->to_warehouse_name)
                                    <br><small class="text-muted">→ {{ $entry->to_warehouse_name }}</small>
                                @endif
                            </td>
                            <td>
                                <small>
                                    @if($entry->sap_batch)
                                        <span class="badge bg-primary bg-opacity-10 text-primary">SAP: {{ $entry->sap_batch }}</span>
                                    @endif
                                    @if($entry->vendor_batch)
                                        <br><span class="text-muted">V: {{ $entry->vendor_batch }}</span>
                                    @endif
                                </small>
                            </td>
                            <td>
                                <small>
                                    @if($entry->invoice_no)
                                        <span class="fw-medium">{{ $entry->invoice_no }}</span>
                                    @elseif($entry->inbound_invoice_no)
                                        <span class="fw-medium">{{ $entry->inbound_invoice_no }}</span>
                                    @else
                                        -
                                    @endif
                                    @if($entry->po_no)
                                        <br><span class="text-muted">PO: {{ $entry->po_no }}</span>
                                    @endif
                                </small>
                            </td>
                            <td>
                                <small>
                                    @if($entry->vendor_name)
                                        <span class="text-success"><i class="bi bi-person-badge me-1"></i>{{ $entry->vendor_name }}</span>
                                    @elseif($entry->customer_name)
                                        <span class="text-warning"><i class="bi bi-people me-1"></i>{{ $entry->customer_name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                    @if($entry->transporter_name)
                                        <br><span class="text-muted"><i class="bi bi-truck me-1"></i>{{ $entry->transporter_name }}</span>
                                    @endif
                                </small>
                            </td>
                            <td class="text-end">
                                @if($entry->direction == 'IN')
                                    <span class="fw-bold text-success">
                                        <i class="bi bi-plus-lg"></i>{{ number_format($entry->quantity, 2) }}
                                    </span>
                                    <br><small class="text-muted">{{ $entry->units }} × {{ $entry->pack_size }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($entry->direction == 'OUT')
                                    <span class="fw-bold text-danger">
                                        <i class="bi bi-dash-lg"></i>{{ number_format($entry->quantity, 2) }}
                                    </span>
                                    <br><small class="text-muted">{{ $entry->units }} × {{ $entry->pack_size }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($entry->direction == 'IN')
                                    @php
                                        $balUnits = $entry->pack_size > 0 ? $entry->balance_quantity / $entry->pack_size : 0;
                                    @endphp
                                    <span class="fw-bold {{ $entry->balance_quantity > 0 ? 'text-info' : 'text-muted' }}">
                                        {{ rtrim(rtrim(number_format($balUnits, 2), '0'), '.') }} U<br>
                                        <small class="fw-normal">({{ rtrim(rtrim(number_format($entry->balance_quantity, 2), '0'), '.') }} Qty)</small>
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($entry->expiry_date)
                                    @php
                                        $expiry = \Carbon\Carbon::parse($entry->expiry_date);
                                        $daysToExpiry = now()->diffInDays($expiry, false);
                                    @endphp
                                    @if($daysToExpiry < 0)
                                        <span class="badge bg-danger">Expired</span>
                                    @elseif($daysToExpiry <= 30)
                                        <span class="badge bg-warning text-dark">{{ $expiry->format('d M Y') }}</span>
                                    @elseif($daysToExpiry <= 90)
                                        <span class="badge bg-info">{{ $expiry->format('d M Y') }}</span>
                                    @else
                                        <small class="text-muted">{{ $expiry->format('d M Y') }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($entry->direction == 'IN')
                                    <a href="{{ route('reports.inbound.pdf', $entry->transaction_id) }}" 
                                       target="_blank" class="btn btn-sm btn-outline-primary" title="View PDF">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                @else
                                    <a href="{{ route('reports.outbound.pdf', $entry->transaction_id) }}" 
                                       target="_blank" class="btn btn-sm btn-outline-danger" title="View PDF">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                                    No ledger entries found matching your criteria
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($ledgerPaginated->hasPages())
            <div class="card-footer bg-light border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing {{ $ledgerPaginated->firstItem() }} to {{ $ledgerPaginated->lastItem() }} 
                        of {{ $ledgerPaginated->total() }} entries
                    </small>
                    {{ $ledgerPaginated->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Legend --}}
<div class="mt-4">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <small class="text-muted fw-semibold">Legend:</small>
                <span class="badge bg-secondary">Opening</span>
                <span class="badge bg-success">Inbound</span>
                <span class="badge bg-info">Transfer</span>
                <span class="badge bg-warning text-dark">Sale</span>
                <span class="badge bg-danger">Outbound</span>
                <span class="ms-auto">
                    <small class="text-muted">
                        <i class="bi bi-plus-lg text-success"></i> IN = Stock received &nbsp;|&nbsp;
                        <i class="bi bi-dash-lg text-danger"></i> OUT = Stock dispatched
                    </small>
                </span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function exportLedger() {
    // Build export URL with current filters
    const form = document.getElementById('ledgerFilterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData).toString();
    
    window.location.href = '{{ route("reports.stock-ledger.export") }}?' + params;
}
</script>
@endpush
