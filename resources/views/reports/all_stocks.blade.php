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
                <li class="breadcrumb-item active" aria-current="page">All Stocks</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold">All Stocks Report</h5>
        <small class="text-muted">Complete stock overview - Opening, Inbound, Outbound & Balance</small>
    </div>
    <div>
        <button type="button" class="btn btn-success" onclick="exportAllStocks()">
            <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
        </button>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10">
            <div class="card-body text-center py-3">
                <i class="bi bi-box-seam text-primary fs-4"></i>
                <h6 class="text-muted mb-1 small">Products</h6>
                <h4 class="mb-0 fw-bold text-primary" id="summaryProducts">{{ $summary['total_products'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100 bg-secondary bg-opacity-10">
            <div class="card-body text-center py-3">
                <i class="bi bi-archive text-secondary fs-4"></i>
                <h6 class="text-muted mb-1 small">Opening</h6>
                <h4 class="mb-0 fw-bold text-secondary" id="summaryOpening">{{ number_format($summary['total_opening'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100 bg-info bg-opacity-10">
            <div class="card-body text-center py-3">
                <i class="bi bi-arrow-down-circle text-info fs-4"></i>
                <h6 class="text-muted mb-1 small">Inbound</h6>
                <h4 class="mb-0 fw-bold text-info" id="summaryInbound">{{ number_format($summary['total_inbound'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100 bg-warning bg-opacity-10">
            <div class="card-body text-center py-3">
                <i class="bi bi-arrow-up-circle text-warning fs-4"></i>
                <h6 class="text-muted mb-1 small">Outbound</h6>
                <h4 class="mb-0 fw-bold text-warning" id="summaryOutbound">{{ number_format($summary['total_outbound'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10">
            <div class="card-body text-center py-3">
                <i class="bi bi-check-circle text-success fs-4"></i>
                <h6 class="text-muted mb-1 small">Balance</h6>
                <h4 class="mb-0 fw-bold text-success" id="summaryBalance">{{ number_format($summary['total_balance'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- Filter Section --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-3">
        <form id="allStocksFilterForm" class="mb-0">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Search</label>
                    <input type="text" name="search" id="filter_search" class="form-control form-control-sm filter-field" placeholder="Item code or name...">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Warehouse</label>
                    <select name="warehouse_id" id="filter_warehouse" class="form-select form-select-sm filter-field">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Category</label>
                    <select name="category_id" id="filter_category" class="form-select form-select-sm filter-field">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Date From</label>
                    <input type="date" name="date_from" id="filter_date_from" class="form-control form-control-sm filter-field">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Date To</label>
                    <input type="date" name="date_to" id="filter_date_to" class="form-control form-control-sm filter-field">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="mt-2">
                <span class="text-muted small">Total: <strong id="totalCount">{{ count($stockReport) }}</strong> products with stock</span>
            </div>
        </form>
    </div>
</div>

{{-- Data Table --}}
<div class="card border-0 shadow-lg rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div id="filterLoadingOverlay" style="display: none; position: relative;">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading data...</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Item Code</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th class="text-center">UOM</th>
                        <th class="text-end bg-secondary bg-opacity-75">Opening</th>
                        <th class="text-end bg-info bg-opacity-75">Inbound</th>
                        <th class="text-end bg-info bg-opacity-50">Units Received</th>
                        <th class="text-end bg-warning bg-opacity-75 text-dark">Outbound</th>
                        <th class="text-end bg-success bg-opacity-75">Balance</th>
                        <th class="text-center" style="width:80px;">Action</th>
                    </tr>
                </thead>

                <tbody id="stockTableBody">
                    @forelse($stockReport as $index => $item)
                        <tr>
                            <td class="text-muted fw-semibold">{{ $index + 1 }}</td>
                            <td>
                                <span class="badge bg-light text-dark fw-semibold">{{ $item['item_code'] }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $item['product_name'] }}</div>
                                <small class="text-muted">{{ $item['packing'] }} × {{ $item['pack_size'] }}</small>
                            </td>
                            <td>
                                <span class="text-muted">{{ $item['category'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary bg-opacity-25 text-secondary">{{ $item['uom'] }}</span>
                            </td>
                            <td class="text-end">
                                <span class="fw-semibold text-secondary">{{ number_format($item['opening_stock'], 2) }}</span>
                            </td>
                            <td class="text-end">
                                <span class="fw-semibold text-info">
                                    <i class="bi bi-arrow-down-short"></i>{{ number_format($item['inbound_stock'], 2) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="fw-semibold">{{ $item['inbound_units'] }}</span>
                                <br><small class="text-muted">{{ $item['inbound_units'] }} × {{ $item['pack_size'] }}</small>
                            </td>
                            <td class="text-end">
                                <span class="fw-semibold text-warning">
                                    <i class="bi bi-arrow-up-short"></i>{{ number_format($item['outbound_stock'], 2) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold {{ $item['balance_stock'] > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($item['balance_stock'], 2) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary view-details-btn"
                                        data-product-id="{{ $item['product_id'] }}"
                                        data-product-name="{{ $item['product_name'] }}"
                                        title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No stock data found
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if(count($stockReport) > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end">Totals:</td>
                        <td class="text-end text-secondary" id="footerOpening">{{ number_format($summary['total_opening'], 2) }}</td>
                        <td class="text-end text-info" id="footerInbound">{{ number_format($summary['total_inbound'], 2) }}</td>
                        <td class="text-end text-info" id="footerUnits">—</td>
                        <td class="text-end text-warning" id="footerOutbound">{{ number_format($summary['total_outbound'], 2) }}</td>
                        <td class="text-end text-success" id="footerBalance">{{ number_format($summary['total_balance'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Stock Details Modal --}}
<div class="modal fade" id="stockDetailsModal" tabindex="-1" aria-labelledby="stockDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="stockDetailsModalLabel">
                    <i class="bi bi-box-seam me-2"></i>Stock Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading stock details...</p>
                </div>

                <div id="modalContent" style="display: none;">
                    {{-- Product Info --}}
                    <div class="card mb-3 border-0 bg-light">
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">Product</small>
                                    <h6 class="mb-0 fw-bold" id="modalProductName">-</h6>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted">Item Code</small>
                                    <h6 class="mb-0" id="modalItemCode">-</h6>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted">Category</small>
                                    <h6 class="mb-0" id="modalCategory">-</h6>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted">UOM</small>
                                    <h6 class="mb-0" id="modalUom">-</h6>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted">Packing</small>
                                    <h6 class="mb-0" id="modalPacking">-</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <ul class="nav nav-tabs" id="stockDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="opening-tab" data-bs-toggle="tab" data-bs-target="#opening-content" type="button" role="tab">
                                <i class="bi bi-archive me-1"></i> Opening Stock
                                <span class="badge bg-secondary ms-1" id="openingCount">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="inbound-tab" data-bs-toggle="tab" data-bs-target="#inbound-content" type="button" role="tab">
                                <i class="bi bi-arrow-down-circle me-1"></i> Inbound
                                <span class="badge bg-info ms-1" id="inboundCount">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="outbound-tab" data-bs-toggle="tab" data-bs-target="#outbound-content" type="button" role="tab">
                                <i class="bi bi-arrow-up-circle me-1"></i> Outbound
                                <span class="badge bg-warning text-dark ms-1" id="outboundCount">0</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content border border-top-0 rounded-bottom p-3" id="stockDetailsTabsContent">
                        {{-- Opening Stock Tab --}}
                        <div class="tab-pane fade show active" id="opening-content" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Warehouse</th>
                                            <th>Vendor</th>
                                            <th>Transporter</th>
                                            <th>Vehicle No</th>
                                            <th>Driver Name</th>
                                            <th>SAP Batch</th>
                                            <th>Vendor Batch</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">Balance</th>
                                            <th>Stock Duration</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="openingTableBody">
                                        <tr><td colspan="12" class="text-center text-muted">No data</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Inbound Tab --}}
                        <div class="tab-pane fade" id="inbound-content" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Warehouse</th>
                                            <th>Vendor</th>
                                            <th>Transporter</th>
                                            <th>Vehicle No</th>
                                            <th>Driver Name</th>
                                            <th>SAP Batch</th>
                                            <th>Vendor Batch</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">Balance</th>
                                            <th>QC Status</th>
                                            <th>Stock Duration</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inboundTableBody">
                                        <tr><td colspan="13" class="text-center text-muted">No data</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Outbound Tab --}}
                        <div class="tab-pane fade" id="outbound-content" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Warehouse</th>
                                            <th>Customer</th>
                                            <th>Vendor</th>
                                            <th>Transporter</th>
                                            <th>Vehicle No</th>
                                            <th>Driver Name</th>
                                            <th>Type</th>
                                            <th class="text-end">Quantity</th>
                                            <th>Stock Duration</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="outboundTableBody">
                                        <tr><td colspan="11" class="text-center text-muted">No data</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Apply filters
    $('#applyFilters').on('click', function() {
        applyFilters();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#allStocksFilterForm')[0].reset();
        applyFilters();
    });

    // Auto-apply on filter change
    $('.filter-field').on('change', function() {
        applyFilters();
    });

    // Search with debounce
    $('#filter_search').on('keyup', function() {
        clearTimeout(window.filterTimeout);
        window.filterTimeout = setTimeout(applyFilters, 500);
    });

    function applyFilters() {
        const formData = {
            search: $('#filter_search').val(),
            warehouse_id: $('#filter_warehouse').val(),
            category_id: $('#filter_category').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val()
        };

        $('#filterLoadingOverlay').show();

        $.ajax({
            url: '{{ route("reports.all-stocks") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');

                // Update table body
                const newTableBody = doc.querySelector('#stockTableBody');
                if (newTableBody) {
                    $('#stockTableBody').html(newTableBody.innerHTML);
                    bindViewButtons();
                }

                // Update summaries
                updateElement('#summaryProducts', doc);
                updateElement('#summaryOpening', doc);
                updateElement('#summaryInbound', doc);
                updateElement('#summaryOutbound', doc);
                updateElement('#summaryBalance', doc);
                updateElement('#totalCount', doc);
                updateElement('#footerOpening', doc);
                updateElement('#footerInbound', doc);
                updateElement('#footerOutbound', doc);
                updateElement('#footerBalance', doc);

                $('#filterLoadingOverlay').hide();
            },
            error: function(xhr, status, error) {
                console.error('Filter error:', error);
                alert('An error occurred while filtering. Please try again.');
                $('#filterLoadingOverlay').hide();
            }
        });
    }

    function updateElement(selector, doc) {
        const el = doc.querySelector(selector);
        if (el) $(selector).text(el.textContent);
    }

    // View details button handler
    function bindViewButtons() {
        $('.view-details-btn').off('click').on('click', function() {
            const productId = $(this).data('product-id');
            const productName = $(this).data('product-name');

            $('#stockDetailsModalLabel').html('<i class="bi bi-box-seam me-2"></i>' + productName);
            $('#modalLoading').show();
            $('#modalContent').hide();

            const modal = new bootstrap.Modal(document.getElementById('stockDetailsModal'));
            modal.show();
            const reportsBase = '{{ url('reports') }}';

            $.ajax({
                url: '{{ url("reports/stock-details") }}/' + productId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    try {
                    // Product info
                    $('#modalProductName').text(data.product.name);
                    $('#modalItemCode').text(data.product.item_code);
                    $('#modalCategory').text(data.product.category?.name || '-');
                    $('#modalUom').text(data.product.uom?.name || '-');
                    $('#modalPacking').text((data.product.packing_type?.name || '-') + ' × ' + data.product.pack_size);

                    // Opening batches
                    $('#openingCount').text(data.opening_batches.length);
                    let openingHtml = '';
                    if (data.opening_batches.length > 0) {
                        data.opening_batches.forEach((item, index) => {
                            openingHtml += `<tr>
                                <td>${index + 1}</td>
                                <td>${item.warehouse_name}</td>
                                <td>${item.vendor_name || '-'}</td>
                                <td>${item.transporter_name || '-'}</td>
                                <td>${item.vehicle_no || '-'}</td>
                                <td>${item.driver_name || '-'}</td>
                                <td>${item.sap_batch || '-'}</td>
                                <td>${item.vendor_batch || '-'}</td>
                                <td class="text-end">${parseFloat(item.total_quantity).toFixed(2)}</td>
                                <td class="text-end fw-bold">
                                    ${item.pack_size_snapshot > 0 ? parseFloat(item.balance_quantity / item.pack_size_snapshot).toFixed(2).replace(/\.00$/, '') : 0} U<br>
                                    <small class="text-muted fw-normal">(${parseFloat(item.balance_quantity).toFixed(2).replace(/\.00$/, '')} Qty)</small>
                                </td>
                                <td>${getStockDurationBadge(item.created_at)}</td>
                                <td>${formatDate(item.created_at)}</td>
                                <td><a class="btn btn-sm btn-outline-primary" target="_blank" href="${reportsBase}/inbound/${item.stock_in_id}/pdf">PDF</a></td>
                            </tr>`;
                        });
                    } else {
                        openingHtml = '<tr><td colspan="13" class="text-center text-muted">No opening stock</td></tr>';
                    }
                    $('#openingTableBody').html(openingHtml);

                    // Inbound batches
                    $('#inboundCount').text(data.inbound_batches.length);
                    let inboundHtml = '';
                    if (data.inbound_batches.length > 0) {
                        data.inbound_batches.forEach((item, index) => {
                            const qcBadge = getQcBadge(item.quality_clearance);
                            inboundHtml += `<tr>
                                <td>${index + 1}</td>
                                <td>${item.warehouse_name}</td>
                                <td>${item.vendor_name || '-'}</td>
                                <td>${item.transporter_name || '-'}</td>
                                <td>${item.vehicle_no || '-'}</td>
                                <td>${item.driver_name || '-'}</td>
                                <td>${item.sap_batch || '-'}</td>
                                <td>${item.vendor_batch || '-'}</td>
                                <td class="text-end">${parseFloat(item.total_quantity).toFixed(2)}</td>
                                <td class="text-end fw-bold">
                                    ${item.pack_size_snapshot > 0 ? parseFloat(item.balance_quantity / item.pack_size_snapshot).toFixed(2).replace(/\.00$/, '') : 0} U<br>
                                    <small class="text-muted fw-normal">(${parseFloat(item.balance_quantity).toFixed(2).replace(/\.00$/, '')} Qty)</small>
                                </td>
                                <td>${qcBadge}</td>
                                <td>${getStockDurationBadge(item.created_at)}</td>
                                <td>${formatDate(item.created_at)}</td>
                                <td><a class="btn btn-sm btn-outline-primary" target="_blank" href="${reportsBase}/inbound/${item.stock_in_id}/pdf">PDF</a></td>
                            </tr>`;
                        });
                    } else {
                        inboundHtml = '<tr><td colspan="14" class="text-center text-muted">No inbound records</td></tr>';
                    }
                    $('#inboundTableBody').html(inboundHtml);

                    // Outbound records
                    $('#outboundCount').text(data.outbound_records.length);
                    let outboundHtml = '';
                    if (data.outbound_records.length > 0) {
                        data.outbound_records.forEach((item, index) => {
                            outboundHtml += `<tr>
                                <td>${index + 1}</td>
                                <td>${item.warehouse_name}</td>
                                <td>${item.customer_name || 'Transfer'}</td>
                                <td>${item.vendor_name || '-'}</td>
                                <td>${item.transporter_name || '-'}</td>
                                <td>${item.vehicle_no || '-'}</td>
                                <td>${item.driver_name || '-'}</td>
                                <td><span class="badge bg-secondary">${item.source_type}</span></td>
                                <td class="text-end">${parseFloat(item.dispatch_quantity).toFixed(2)}</td>
                                <td>${getStockDurationBadge(item.created_at)}</td>
                                <td>${formatDate(item.created_at)}</td>
                                <td><a class="btn btn-sm btn-outline-primary" target="_blank" href="${reportsBase}/outbound/${item.stock_out_id}/pdf">PDF</a></td>
                            </tr>`;
                        });
                    } else {
                        outboundHtml = '<tr><td colspan="12" class="text-center text-muted">No outbound records</td></tr>';
                    }
                    $('#outboundTableBody').html(outboundHtml);

                    $('#modalLoading').hide();
                    $('#modalContent').show();
                    } catch (err) {
                        console.error('Processing error:', err);
                        alert('Failed to process stock details. See console for details.');
                        $('#modalLoading').hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error loading stock details:', {status: xhr.status, statusText: xhr.statusText, responseText: xhr.responseText, error});
                    let msg = 'Failed to load stock details';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg += ': ' + xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        // show first 300 chars of responseText
                        msg += ': ' + xhr.responseText.substring(0, 300);
                    }
                    alert(msg);
                    $('#modalLoading').hide();
                }
            });
        });
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function getQcBadge(status) {
        switch(status) {
            case 'approved': return '<span class="badge bg-success">Approved</span>';
            case 'rejected': return '<span class="badge bg-danger">Rejected</span>';
            case 'pending': return '<span class="badge bg-warning text-dark">Pending</span>';
            default: return '<span class="badge bg-secondary">-</span>';
        }
    }

    function getStockDurationBadge(dateString) {
        if (!dateString) return '<span class="badge bg-secondary">N/A</span>';
        
        const stockDate = new Date(dateString);
        const now = new Date();
        const days = Math.floor((now - stockDate) / (1000 * 60 * 60 * 24));
        
        if (days == 0) {
            return '<span class="badge bg-info">Today</span>';
        } else if (days <= 7) {
            return `<span class="badge bg-success">${days}d</span>`;
        } else if (days <= 30) {
            return `<span class="badge bg-warning text-dark">${days}d</span>`;
        } else if (days <= 90) {
            return `<span class="badge" style="background-color: #fd7e14; color: #fff;">${days}d</span>`;
        } else {
            return `<span class="badge bg-danger">${days}d</span>`;
        }
    }

    // Initial bind
    bindViewButtons();
});

function exportAllStocks() {
    const params = new URLSearchParams({
        search: $('#filter_search').val(),
        warehouse_id: $('#filter_warehouse').val(),
        category_id: $('#filter_category').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val()
    });
    window.location.href = '{{ route("reports.all-stocks.export") }}?' + params.toString();
}
</script>
@endpush

