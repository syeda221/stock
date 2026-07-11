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
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
            <i class="bi bi-funnel me-1"></i> Filters
        </button>
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
            <div class="card-body text-white py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-start">
                        <h6 class="text-white-50 mb-1" style="font-size: 0.75rem;">Total Entries</h6>
                        <h5 class="mb-0 fw-bold">{{ number_format($summary['total_entries']) }}</h5>
                    </div>
                    <i class="bi bi-list-ol fs-3 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body text-white py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-start">
                        <h6 class="text-white-50 mb-1" style="font-size: 0.75rem;">Total Inbound</h6>
                        <h5 class="mb-0 fw-bold">{{ number_format($summary['total_inbound_qty'], 2) }}</h5>
                    </div>
                    <i class="bi bi-arrow-down-circle fs-3 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%);">
            <div class="card-body text-white py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-start">
                        <h6 class="text-white-50 mb-1" style="font-size: 0.75rem;">Total Outbound</h6>
                        <h5 class="mb-0 fw-bold">{{ number_format($summary['total_outbound_qty'], 2) }}</h5>
                    </div>
                    <i class="bi bi-arrow-up-circle fs-3 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #0061ff 0%, #60efff 100%);">
            <div class="card-body text-white py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-start">
                        <h6 class="text-white-50 mb-1" style="font-size: 0.75rem;">Current Balance</h6>
                        <h5 class="mb-0 fw-bold">{{ number_format($summary['total_balance'], 2) }}</h5>
                    </div>
                    <i class="bi bi-check-circle fs-3 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #8e44ad 0%, #c0392b 100%);">
            <div class="card-body text-white py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-start">
                        <h6 class="text-white-50 mb-1" style="font-size: 0.75rem;">Unique Products</h6>
                        <h5 class="mb-0 fw-bold">{{ number_format($summary['unique_products']) }}</h5>
                    </div>
                    <i class="bi bi-box-seam fs-3 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Section --}}
<div class="collapse filter-section show" id="filterCollapse">
<div class="card border-0 shadow-sm rounded-4 mb-3" style="position: relative; z-index: 1050;">
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
                    <div class="dropdown custom-multiselect">
                        <button class="btn btn-outline-secondary btn-sm w-100 text-start bg-white d-flex justify-content-between align-items-center border" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <span class="multiselect-label text-truncate" data-default="All Types">All Types</span>
                            <i class="bi bi-chevron-down ms-1" style="font-size: 10px;"></i>
                        </button>
                        <div class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                            <input type="text" class="form-control form-control-sm mb-2 multiselect-search" placeholder="Search types...">
                            <div class="multiselect-options">
                                @php
                                    $typesList = [
                                        'opening' => 'Opening Stock',
                                        'inbound' => 'Inbound',
                                        'sale' => 'Sale (Outbound)',
                                        'transfer' => 'Transfer'
                                    ];
                                @endphp
                                @foreach($typesList as $tVal => $tLabel)
                                    <div class="form-check">
                                        <input class="form-check-input multiselect-checkbox" type="checkbox" name="source_type[]" value="{{ $tVal }}" id="type_{{ $tVal }}" {{ is_array(request('source_type')) && in_array($tVal, request('source_type')) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="type_{{ $tVal }}">
                                            {{ $tLabel }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
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
                <div class="col-md-1">
                    <label class="form-label fw-semibold small">Has Stock</label>
                    <div class="form-check mt-1">
                        <input type="hidden" name="has_stock" value="0">
                        <input type="checkbox" name="has_stock" value="1" class="form-check-input" id="hasStock" {{ request()->boolean('has_stock') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="hasStock">Active</label>
                    </div>
                </div>
                <div class="col-md-1">
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
            <div class="row g-2 mt-1">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Inbound Invoice</label>
                    <div class="dropdown custom-multiselect">
                        <button class="btn btn-outline-secondary btn-sm w-100 text-start bg-white d-flex justify-content-between align-items-center border" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <span class="multiselect-label text-truncate" data-default="All Invoices">All Invoices</span>
                            <i class="bi bi-chevron-down ms-1" style="font-size: 10px;"></i>
                        </button>
                        <div class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                            <input type="text" class="form-control form-control-sm mb-2 multiselect-search" placeholder="Search invoices...">
                            <div class="multiselect-options">
                                @foreach($inboundInvoicesList as $inv)
                                    <div class="form-check">
                                        <input class="form-check-input multiselect-checkbox" type="checkbox" name="inbound_invoice[]" value="{{ $inv }}" id="inv_{{ md5($inv) }}" {{ is_array(request('inbound_invoice')) && in_array($inv, request('inbound_invoice')) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="inv_{{ md5($inv) }}">
                                            {{ $inv }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Record ID</label>
                    <div class="dropdown custom-multiselect">
                        <button class="btn btn-outline-secondary btn-sm w-100 text-start bg-white d-flex justify-content-between align-items-center border" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <span class="multiselect-label text-truncate" data-default="All Record IDs">All Record IDs</span>
                            <i class="bi bi-chevron-down ms-1" style="font-size: 10px;"></i>
                        </button>
                        <div class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                            <input type="text" class="form-control form-control-sm mb-2 multiselect-search" placeholder="Search Record IDs...">
                            <div class="multiselect-options">
                                @foreach($recordIdsList as $rid)
                                    <div class="form-check">
                                        <input class="form-check-input multiselect-checkbox" type="checkbox" name="record_id[]" value="{{ $rid }}" id="rid_{{ $rid }}" {{ is_array(request('record_id')) && in_array($rid, request('record_id')) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="rid_{{ $rid }}">
                                            {{ $rid }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Dispatch No</label>
                    <div class="dropdown custom-multiselect">
                        <button class="btn btn-outline-secondary btn-sm w-100 text-start bg-white d-flex justify-content-between align-items-center border" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <span class="multiselect-label text-truncate" data-default="All Dispatch Nos">All Dispatch Nos</span>
                            <i class="bi bi-chevron-down ms-1" style="font-size: 10px;"></i>
                        </button>
                        <div class="dropdown-menu w-100 p-2 shadow" style="max-height: 250px; overflow-y: auto;">
                            <input type="text" class="form-control form-control-sm mb-2 multiselect-search" placeholder="Search dispatch nos...">
                            <div class="multiselect-options">
                                @foreach($dispatchNosList as $disp)
                                    <div class="form-check">
                                        <input class="form-check-input multiselect-checkbox" type="checkbox" name="dispatch_no[]" value="{{ $disp }}" id="disp_{{ md5($disp) }}" {{ is_array(request('dispatch_no')) && in_array($disp, request('dispatch_no')) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="disp_{{ md5($disp) }}">
                                            {{ $disp }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
</div>

{{-- Data Table --}}
<div class="card border-0 shadow-lg rounded-4 mb-0">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th style="width:80px;">Date</th>
                        <th class="text-center" style="width:65px;">Type</th>
                        <th>Item Code</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th style="width:45px;" class="text-center">UOM</th>
                        <th style="width:55px;" class="text-center">Pack</th>
                        <th style="width:90px;">SAP Batch</th>
                        <th style="width:90px;">Vendor Batch</th>
                        <th style="width:80px;">IBD</th>
                        <th style="width:70px;">PO</th>
                        <th class="text-end" style="width:55px;">Units</th>
                        <th class="text-end bg-success bg-opacity-50" style="width:70px;">IN</th>
                        <th class="text-end bg-danger bg-opacity-50" style="width:70px;">OUT</th>
                        <th class="text-end bg-info bg-opacity-50" style="width:80px;">Balance</th>
                        <th style="width:80px;">MFG</th>
                        <th style="width:80px;">Expiry</th>
                        <th class="text-center" style="width:50px;">Days</th>
                        <th class="text-center" style="width:50px;">Pallets</th>
                        <th style="width:60px;" class="text-center">QC</th>
                        <th style="width:90px;">Warehouse</th>
                        <th style="width:70px;">Row</th>
                        <th style="width:80px;">Record ID</th>
                        <th style="width:100px;">Inbound Invoice</th>
                        <th style="width:100px;">Dispatch No</th>
                        <th style="width:100px;">Party</th>
                        <th style="width:70px;">Vehicle</th>
                        <th class="text-center" style="width:70px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerPaginated as $index => $entry)
                        @php
                            $isIn = $entry->direction == 'IN';
                            $balUnits = $isIn && $entry->pack_size > 0 ? $entry->balance_quantity / $entry->pack_size : 0;
                            $daysInWh = $entry->created_at ? abs((int)now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($entry->created_at)->startOfDay(), false)) : '';
                            $expiry = $entry->expiry_date ? \Carbon\Carbon::parse($entry->expiry_date) : null;
                            $daysToExpiry = $expiry ? now()->diffInDays($expiry, false) : null;
                        @endphp
                        <tr>
                            <td class="text-muted fw-semibold">{{ $ledgerPaginated->firstItem() + $loop->index }}</td>
                            <td class="text-nowrap">
                                <small class="text-muted">{{ \Carbon\Carbon::parse($entry->created_at)->format('d.m.Y') }}</small>
                                <br>
                                <small class="text-muted opacity-75">{{ \Carbon\Carbon::parse($entry->created_at)->format('H:i') }}</small>
                            </td>
                            <td class="text-center">
                                @if($isIn)
                                    @if($entry->source_type == 'opening')
                                        <span class="badge bg-secondary">Opening</span>
                                    @elseif($entry->source_type == 'transfer')
                                        <span class="badge bg-info">Tr.In</span>
                                    @else
                                        <span class="badge bg-success">Inbound</span>
                                    @endif
                                @else
                                    @if($entry->source_type == 'sale')
                                        <span class="badge bg-warning text-dark">Sale</span>
                                    @elseif($entry->source_type == 'transfer')
                                        <span class="badge bg-info">Tr.Out</span>
                                    @else
                                        <span class="badge bg-danger">Outbound</span>
                                    @endif
                                @endif
                            </td>
                            <td><small class="fw-semibold">{{ $entry->item_code ?? '-' }}</small></td>
                            <td>
                                <div class="fw-semibold text-dark" style="font-size:13px;">{{ $entry->product_name }}</div>
                            </td>
                            <td><small class="text-muted">{{ $entry->category_name ?? '-' }}</small></td>
                            <td class="text-center"><small>{{ $entry->uom_name ?? '-' }}</small></td>
                            <td class="text-center"><small>{{ $entry->pack_size ?? '-' }}</small></td>
                            <td><small>{{ $entry->sap_batch ?: '-' }}</small></td>
                            <td><small>{{ $entry->vendor_batch ?: '-' }}</small></td>
                            <td><small>{{ $entry->ibd_no ?: '-' }}</small></td>
                            <td><small>{{ $entry->po_no ?: '-' }}</small></td>
                            <td class="text-end"><small>{{ $entry->units ?? 0 }}</small></td>
                            <td class="text-end">
                                @if($isIn)
                                    <span class="fw-bold text-success"><i class="bi bi-plus-lg"></i>{{ number_format($entry->quantity, 2) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if(!$isIn)
                                    <span class="fw-bold text-danger"><i class="bi bi-dash-lg"></i>{{ number_format($entry->quantity, 2) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($isIn)
                                    <span class="fw-bold {{ $entry->balance_quantity > 0 ? 'text-info' : 'text-muted' }}">
                                        {{ rtrim(rtrim(number_format($balUnits, 2), '0'), '.') }} U
                                    </span>
                                    <br><small class="fw-normal text-muted">({{ rtrim(rtrim(number_format($entry->balance_quantity, 2), '0'), '.') }})</small>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td><small>{{ $entry->mfg_date ? \Carbon\Carbon::parse($entry->mfg_date)->format('d.m.Y') : '-' }}</small></td>
                            <td class="text-center">
                                @if($expiry)
                                    @if($daysToExpiry < 0)
                                        <span class="badge bg-danger">Expired</span>
                                    @elseif($daysToExpiry <= 30)
                                        <span class="badge bg-warning text-dark">{{ $expiry->format('d.m.y') }}</span>
                                    @elseif($daysToExpiry <= 90)
                                        <span class="badge bg-info">{{ $expiry->format('d.m.y') }}</span>
                                    @else
                                        <small class="text-muted">{{ $expiry->format('d.m.y') }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($daysInWh !== '')
                                    @if($daysInWh <= 7)
                                        <span class="badge bg-success">{{ $daysInWh }}d</span>
                                    @elseif($daysInWh <= 30)
                                        <span class="badge bg-warning text-dark">{{ $daysInWh }}d</span>
                                    @elseif($daysInWh <= 90)
                                        <span class="badge" style="background:#fd7e14;color:#fff;">{{ $daysInWh }}d</span>
                                    @else
                                        <span class="badge bg-danger">{{ $daysInWh }}d</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center"><small>{{ $isIn ? ($entry->pallets_used ?? '-') : '-' }}</small></td>
                            <td class="text-center">
                                @if($isIn && $entry->qc_status)
                                    @if($entry->qc_status == 'approved')
                                        <span class="badge bg-success">A</span>
                                    @elseif($entry->qc_status == 'rejected')
                                        <span class="badge bg-danger">R</span>
                                    @else
                                        <span class="badge bg-warning text-dark">P</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $entry->warehouse_name ?? '-' }}</small>
                                @if($entry->to_warehouse_name)
                                    <br><small class="text-muted fw-semibold" style="font-size:11px;">→ {{ $entry->to_warehouse_name }}</small>
                                @endif
                            </td>
                            <td>
                                @if(!empty($entry->warehouse_display))
                                    <small class="fw-semibold">{{ $entry->warehouse_display }}</small>
                                @elseif(!empty($entry->row_name))
                                    <small>{{ $entry->row_name }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <small>
                                    @if($entry->source_type === 'opening')
                                        {{ $entry->transaction_id }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </small>
                            </td>
                            <td>
                                <small>
                                    @if($entry->direction === 'IN' && $entry->source_type !== 'opening')
                                        {{ $entry->inbound_invoice_no ?: '-' }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </small>
                            </td>
                            <td>
                                <small>
                                    @if($entry->direction === 'OUT')
                                        {{ $entry->invoice_no ?: '-' }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </small>
                            </td>
                            <td>
                                <small>
                                    @if($entry->vendor_name)
                                        <span class="text-success">{{ $entry->vendor_name }}</span>
                                    @elseif($entry->customer_name)
                                        <span class="text-warning">{{ $entry->customer_name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                    @if($entry->transporter_name)
                                        <br><small class="text-muted" style="font-size:11px;">{{ $entry->transporter_name }}</small>
                                    @endif
                                </small>
                            </td>
                            <td><small>{{ $entry->vehicle_no ?: '-' }}</small></td>
                            <td class="text-center">
                                @if($isIn)
                                    <a href="{{ route('reports.inbound.pdf', $entry->transaction_id) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="PDF"><i class="bi bi-file-pdf"></i></a>
                                @else
                                    <a href="{{ route('reports.outbound.pdf', $entry->transaction_id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="PDF"><i class="bi bi-file-pdf"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="29" class="text-center py-5">
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
document.addEventListener('DOMContentLoaded', function() {
    // Custom Multi-select Logic
    document.querySelectorAll('.custom-multiselect').forEach(function(dropdown) {
        const searchInput = dropdown.querySelector('.multiselect-search');
        const options = dropdown.querySelectorAll('.multiselect-options .form-check');
        const checkboxes = dropdown.querySelectorAll('.multiselect-checkbox');
        const label = dropdown.querySelector('.multiselect-label');
        const defaultText = label.getAttribute('data-default');

        // Search logic
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                options.forEach(function(opt) {
                    const text = opt.querySelector('label').textContent.toLowerCase();
                    if (text.includes(term)) {
                        opt.style.display = 'block';
                    } else {
                        opt.style.display = 'none';
                    }
                });
            });
        }

        // Update label text based on selected checkboxes
        function updateLabel() {
            let count = 0;
            checkboxes.forEach(function(cb) {
                if (cb.checked) count++;
            });
            if (count === 0) {
                label.textContent = defaultText;
            } else if (count === 1) {
                label.textContent = '1 selected';
            } else {
                label.textContent = count + ' selected';
            }
        }

        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', updateLabel);
        });

        // Initialize label on load
        updateLabel();
    });
});
</script>

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
