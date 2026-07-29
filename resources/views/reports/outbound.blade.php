@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-arrow-up-circle me-2"></i>Outbound Reports</h4>
            <p class="text-muted mb-0">View and analyze outbound stock transactions</p>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('reports.outbound.export') }}" class="d-inline">
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
                <input type="hidden" name="warehouse_id" value="{{ request('warehouse_id') }}">
                <input type="hidden" name="invoice_no" value="{{ request('invoice_no') }}">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                </button>
            </form>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>Print Report
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Entries</h6>
                            <h3 class="mb-0">{{ $summary['total_entries'] }}</h3>
                        </div>
                        <div class="fs-1 opacity-25">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Quantity</h6>
                            <h3 class="mb-0">{{ number_format($summary['total_quantity'], 2) }}</h3>
                        </div>
                        <div class="fs-1 opacity-25">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Units</h6>
                            <h3 class="mb-0">{{ number_format($summary['total_units']) }}</h3>
                        </div>
                        <div class="fs-1 opacity-25">
                            <i class="bi bi-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.outbound') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Customer</label>
                        <select name="customer_id" class="form-control">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Warehouse</label>
                        <select name="warehouse_id" class="form-control">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Invoice No</label>
                        <div class="position-relative">
                            <input type="text"
                                   id="invoice_search"
                                   name="invoice_no"
                                   class="form-control"
                                   placeholder="Search invoice..."
                                   value="{{ request('invoice_no') }}"
                                   autocomplete="off">
                            <div id="invoice_suggestions" class="dropdown-menu w-100" style="max-height: 300px; overflow-y: auto;"></div>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search me-2"></i>Apply Filters
                        </button>
                        <a href="{{ route('reports.outbound') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:30px"></th>
                            <th class="text-nowrap">Date</th>
                            <th class="text-nowrap">Invoice No</th>
                            <th class="text-nowrap">Customer</th>
                            <th class="text-nowrap">Warehouse</th>
                            <th class="text-nowrap">Item Code</th>
                            <th class="text-nowrap">Description</th>
                            <th class="text-nowrap">Total Items</th>
                            <th class="text-nowrap text-end">Total Qty</th>
                            <th class="text-nowrap text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockOuts as $stockOut)
                        @php
                            $itemCodes = $stockOut->items->pluck('product.item_code')->filter()->unique()->implode(', ');
                            $productNames = $stockOut->items->pluck('product.name')->filter()->unique()->implode(', ');
                        @endphp
                        <tr onclick="toggleOutboundItems({{ $stockOut->id }})" style="cursor:pointer">
                            <td class="text-center">
                                <i id="otoggle-{{ $stockOut->id }}" class="bi bi-chevron-right"></i>
                            </td>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($stockOut->created_at)->format('d.m.Y') }}</td>
                            <td class="fw-semibold">
                                @if($stockOut->dispatched_invoice_no)
                                    {{ $stockOut->dispatched_invoice_no }}
                                @elseif($stockOut->delivery_no)
                                    {{ $stockOut->delivery_no }}
                                @elseif($stockOut->gatepass_no)
                                    {{ $stockOut->gatepass_no }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($stockOut->customer)
                                    {{ $stockOut->customer->name }}
                                @elseif($stockOut->toWarehouse)
                                    <span class="badge bg-info">Transfer: {{ $stockOut->toWarehouse->name }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $stockOut->warehouse->name ?? 'N/A' }}</td>
                            <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="{{ $itemCodes }}">{{ $itemCodes ?: '-' }}</td>
                            <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="{{ $productNames }}">{{ $productNames ?: '-' }}</td>
                            <td>
                                <span class="badge bg-warning text-dark">{{ $stockOut->items->count() }} batches</span>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($stockOut->items->sum('dispatch_quantity'), 2) }}</td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <!-- QUICK VIEW MODAL BUTTON -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary action-btn p-1 px-2" 
                                            title="Quick View Details"
                                            onclick="openOutboundModal({{ json_encode($stockOut->load(['items.product', 'customer', 'toWarehouse', 'warehouse', 'transporter'])) }})">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    <!-- PICK LIST / INVOICE -->
                                    <a href="{{ route('outbound.invoice', $stockOut->id) }}" 
                                       class="btn btn-sm btn-outline-secondary action-btn p-1 px-2" 
                                       title="Pick List / Invoice" 
                                       target="_blank">
                                        <i class="bi bi-file-earmark-text-fill"></i>
                                    </a>
                                    <!-- DISPATCH DETAILS -->
                                    <a href="{{ route('outbound.dispatch_details', $stockOut->id) }}" 
                                       class="btn btn-sm btn-outline-info action-btn p-1 px-2" 
                                       title="Dispatch Details" 
                                       target="_blank">
                                        <i class="bi bi-truck"></i>
                                    </a>
                                    <!-- GATE PASS DC -->
                                    <a href="{{ route('outbound.dc', $stockOut->id) }}" 
                                       class="btn btn-sm btn-outline-warning action-btn p-1 px-2" 
                                       title="Gate Pass DC" 
                                       target="_blank">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    <!-- DOWNLOAD PDF -->
                                    <a href="{{ route('reports.outbound.pdf', $stockOut->id) }}" 
                                       class="btn btn-sm btn-outline-danger action-btn p-1 px-2" 
                                       title="Download PDF" 
                                       target="_blank">
                                        <i class="bi bi-file-pdf-fill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr id="oitems-{{ $stockOut->id }}" class="d-none">
                            <td colspan="10" class="p-0">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th class="px-3">Item Code</th>
                                            <th>Description</th>
                                            <th>SAP Batch</th>
                                            <th>Vendor Batch</th>
                                            <th>Units</th>
                                            <th>Qty</th>
                                            <th>Source Row</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stockOut->items as $item)
                                        <tr>
                                            <td class="px-3">{{ $item->product->item_code ?? '-' }}</td>
                                            <td>{{ $item->product->name ?? '-' }}</td>
                                            <td>{{ $item->sap_batch ?? '-' }}</td>
                                            <td>{{ $item->vendor_batch ?? '-' }}</td>
                                            <td>{{ $item->units_dispatch ?? 0 }}</td>
                                            <td>{{ number_format($item->dispatch_quantity ?? 0, 2) }}</td>
                                            <td>{{ $item->warehouseRow->name ?? '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No outbound records found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($stockOuts->hasPages())
        <div class="card-footer bg-white">
            {{ $stockOuts->links() }}
        </div>
        @endif
    </div>
</div>

<style>
    @media print {
        .btn, .card-header, nav, .sidebar, .top-header, form {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }

    /* Autocomplete styles */
    #invoice_suggestions {
        position: absolute;
        z-index: 1000;
        border: 1px solid #dee2e6;
        max-width: 100%;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    #invoice_suggestions .dropdown-item {
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid #f1f3f5;
    }

    #invoice_suggestions .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    #invoice_suggestions .dropdown-item:last-child {
        border-bottom: none;
    }

    .invoice-label {
        font-weight: 600;
        color: #495057;
    }

    .invoice-meta {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.15em;
    }
</style>

@push('scripts')
<script>
function toggleOutboundItems(id) {
    const row = document.getElementById('oitems-' + id);
    const icon = document.getElementById('otoggle-' + id);
    if (row.classList.contains('d-none')) {
        row.classList.remove('d-none');
        icon.classList.remove('bi-chevron-right');
        icon.classList.add('bi-chevron-down');
    } else {
        row.classList.add('d-none');
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-right');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const invoiceInput = document.getElementById('invoice_search');
    const suggestionsDiv = document.getElementById('invoice_suggestions');
    let debounceTimer;

    if (!invoiceInput) return;

    // Debounced search function
    invoiceInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();

        clearTimeout(debounceTimer);

        if (searchTerm.length < 2) {
            suggestionsDiv.classList.remove('show');
            suggestionsDiv.innerHTML = '';
            return;
        }

        // Show loading state
        suggestionsDiv.innerHTML = '<div class="dropdown-item text-center"><span class="spinner-border spinner-border-sm" role="status"></span> Searching...</div>';
        suggestionsDiv.classList.add('show');

        debounceTimer = setTimeout(() => {
            fetch(`{{ route('reports.outbound.invoice.suggestions') }}?q=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        suggestionsDiv.innerHTML = '<div class="dropdown-item text-muted">No invoices found</div>';
                    } else {
                        suggestionsDiv.innerHTML = data.map(item => `
                            <a href="#" class="dropdown-item" data-invoice="${item.invoice}">
                                <div class="invoice-label">${item.invoice}</div>
                                <div class="invoice-meta">${item.customer} • ${item.date}</div>
                            </a>
                        `).join('');

                        // Add click handlers to suggestions
                        suggestionsDiv.querySelectorAll('.dropdown-item').forEach(item => {
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                const invoice = this.dataset.invoice;
                                invoiceInput.value = invoice;
                                suggestionsDiv.classList.remove('show');
                                suggestionsDiv.innerHTML = '';
                            });
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                    suggestionsDiv.innerHTML = '<div class="dropdown-item text-danger">Error loading suggestions</div>';
                });
        }, 300);
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!invoiceInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.remove('show');
        }
    });

    // Show suggestions on focus if there's text
    invoiceInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            this.dispatchEvent(new Event('input'));
        }
    });
});

function openOutboundModal(data) {
    const invNo = data.dispatched_invoice_no || data.delivery_no || data.gatepass_no || ('#OUT-' + data.id);
    document.getElementById('ob_modalSubHeader').innerText = 'Invoice #' + invNo;
    document.getElementById('ob_invoice_no').innerText = invNo;
    
    const dateObj = new Date(data.created_at);
    document.getElementById('ob_date').innerText = dateObj.toLocaleString('en-GB', { 
        day: '2-digit', month: '2-digit', year: 'numeric', 
        hour: '2-digit', minute: '2-digit', hour12: true 
    });

    const targetName = data.customer ? data.customer.name : (data.to_warehouse ? 'Transfer: ' + data.to_warehouse.name : 'N/A');
    document.getElementById('ob_target').innerText = targetName;
    document.getElementById('ob_warehouse').innerText = data.warehouse ? data.warehouse.name : 'N/A';
    document.getElementById('ob_transporter').innerText = data.transporter ? data.transporter.name : '-';
    
    const vehNo = data.vehicle_no || '-';
    const driver = data.driver_name ? ` (${data.driver_name})` : '';
    document.getElementById('ob_vehicle').innerText = vehNo + driver;

    // Action URLs
    const baseInvoiceUrl = "{{ url('/outbound') }}/" + data.id + "/invoice";
    const baseDetailsUrl = "{{ url('/outbound') }}/" + data.id + "/dispatch-details";
    const baseDcUrl = "{{ url('/outbound') }}/" + data.id + "/dc";
    const basePdfUrl = "{{ url('/reports/outbound') }}/" + data.id + "/pdf";

    document.getElementById('ob_invoice_btn').href = baseInvoiceUrl;
    document.getElementById('ob_details_btn').href = baseDetailsUrl;
    document.getElementById('ob_dc_btn').href = baseDcUrl;
    document.getElementById('ob_pdf_btn').href = basePdfUrl;

    // Populate items
    const items = data.items || [];
    document.getElementById('ob_batch_count').innerText = items.length + ' Batches';

    let tbodyHtml = '';
    let totalUnits = 0;
    let totalQty = 0;

    items.forEach(item => {
        const prodCode = item.product ? item.product.item_code : '-';
        const prodName = item.product ? item.product.name : '-';
        const sapBatch = item.sap_batch || '-';
        const vendorBatch = item.vendor_batch || '-';
        const units = parseFloat(item.units_dispatch || 0);
        const qty = parseFloat(item.dispatch_quantity || 0);

        totalUnits += units;
        totalQty += qty;

        tbodyHtml += `
            <tr>
                <td class="ps-3 fw-bold text-primary">${prodCode}</td>
                <td><div class="fw-semibold text-dark">${prodName}</div></td>
                <td><span class="badge bg-light text-dark border">${sapBatch}</span></td>
                <td><span class="badge bg-light text-dark border">${vendorBatch}</span></td>
                <td class="text-end fw-bold">${units}</td>
                <td class="text-end fw-bold text-dark">${qty.toFixed(2)}</td>
            </tr>
        `;
    });

    tbodyHtml += `
        <tr class="fw-bold bg-light">
            <td colspan="4" class="text-end pe-3">TOTAL</td>
            <td class="text-end text-primary fs-6">${totalUnits}</td>
            <td class="text-end text-dark fs-6">${totalQty.toFixed(2)}</td>
        </tr>
    `;

    document.getElementById('ob_items_body').innerHTML = tbodyHtml;

    const modal = new bootstrap.Modal(document.getElementById('outboundDetailsModal'));
    modal.show();
}
</script>
@endpush

{{-- OUTBOUND QUICK VIEW MODAL --}}
<div class="modal fade" id="outboundDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div>
                    <h5 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-up-right text-warning fs-5"></i> Outbound Transaction Details
                    </h5>
                    <small class="text-white-50" id="ob_modalSubHeader">Invoice # -</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background: #f8fafc;">
                
                {{-- HEADER SUMMARY CARDS --}}
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Outbound Invoice #</small>
                            <div class="fw-bold text-primary fs-6 mt-1" id="ob_invoice_no">-</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Date & Time</small>
                            <div class="fw-semibold text-dark small mt-1" id="ob_date">-</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Dispatch To / Customer</small>
                            <div class="fw-semibold text-dark small mt-1" id="ob_target">-</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Dispatch Warehouse</small>
                            <div class="fw-semibold text-dark small mt-1" id="ob_warehouse">-</div>
                        </div>
                    </div>
                </div>

                {{-- SECONDARY INFO ROW --}}
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Transporter</small>
                            <div class="fw-semibold text-dark small mt-1" id="ob_transporter">-</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Vehicle & Driver</small>
                            <div class="fw-semibold text-dark small mt-1" id="ob_vehicle">-</div>
                        </div>
                    </div>
                </div>

                {{-- ITEMS TABLE CARD --}}
                <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-boxes text-primary me-2"></i>Outbound Item Batches List</h6>
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1" id="ob_batch_count">0 Batches</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Item Code</th>
                                    <th>Product Name</th>
                                    <th>SAP Batch</th>
                                    <th>Vendor Batch</th>
                                    <th class="text-end">Cartons/Units</th>
                                    <th class="text-end">Dispatch Qty</th>
                                </tr>
                            </thead>
                            <tbody id="ob_items_body">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white py-3 px-4 border-top d-flex justify-content-between flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary border rounded-3 px-4 font-semibold" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-2 flex-wrap">
                    <a id="ob_pdf_btn" href="#" class="btn btn-outline-danger rounded-3 font-semibold px-3" target="_blank">
                        <i class="bi bi-file-pdf-fill me-1"></i>Download PDF
                    </a>
                    <a id="ob_dc_btn" href="#" class="btn btn-outline-warning text-dark rounded-3 font-semibold px-3" target="_blank">
                        <i class="bi bi-receipt me-1"></i>Gate Pass DC
                    </a>
                    <a id="ob_details_btn" href="#" class="btn btn-outline-info rounded-3 font-semibold px-3" target="_blank">
                        <i class="bi bi-truck me-1"></i>Dispatch Details
                    </a>
                    <a id="ob_invoice_btn" href="#" class="btn btn-primary rounded-3 font-semibold px-3" target="_blank">
                        <i class="bi bi-file-earmark-text-fill me-1"></i>Pick List / Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

