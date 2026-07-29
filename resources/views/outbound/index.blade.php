@extends('layouts.app')

@section('content')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* select2 bootstrap 5 styling fixes */
.select2-container--default .select2-selection--multiple {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    min-height: calc(1.5em + 0.5rem + 2px);
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #212529;
}

/* ── Modal & KPI Strip Styles ── */
#outboundBatchesModal .modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    overflow: hidden;
}
@media (min-width: 993px) {
    #outboundBatchesModal { left: 280px !important; width: calc(100% - 280px) !important; }
}
.kpi-strip { display: flex; background: #ffffff; border-bottom: 1px solid #e2e8f0; }
.kpi-card  { flex: 1; padding: 14px 18px; text-align: center; border-right: 1px solid #f1f5f9; }
.kpi-card:last-child { border-right: none; }
.kpi-icon  { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px; font-size: 14px; }
.kpi-val   { font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-bottom: 2px; }
.kpi-lbl   { font-size: 10px; font-weight: 700; color: #64748b; letter-spacing: .5px; text-transform: uppercase; }
.kpi-blue   .kpi-icon { background:#dbeafe; color:#2563eb; }
.kpi-green  .kpi-icon { background:#dcfce7; color:#16a34a; }
.kpi-purple .kpi-icon { background:#ede9fe; color:#7c3aed; }
.kpi-orange .kpi-icon { background:#ffedd5; color:#ea580c; }
.state-box { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:60px 20px; gap:12px; color:#94a3b8; }
</style>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0">Outbound / Dispatch</h5>
        <small class="text-muted">Manage outward stock movements</small>
    </div>
    <div class="d-flex gap-2">
        <a href="javascript:void(0)" onclick="exportOutbound()" class="btn btn-sm fw-semibold"
           style="background:#134e26; color:#4ade80; border:1px solid #166534; border-radius:8px; font-size:11px; padding:6px 14px;">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
        {{-- Import CSV button hidden as per request --}}
        <a href="{{ route('outbound.create') }}" class="btn btn-sm fw-bold"
           style="background:linear-gradient(135deg,#3b82f6,#1d4ed8); color:#fff; border:none; border-radius:8px; font-size:11px; padding:6px 16px;">
            <i class="bi bi-plus-lg me-1"></i>New Outbound
        </a>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-truck text-primary fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Total Entries</small>
                    <strong class="fs-6">{{ $transactions->total() }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-cart text-success fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Sales</small>
                    <strong class="fs-6">{{ $transactions->getCollection()->where('source_type', 'sale')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-info bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-info bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-arrow-left-right text-info fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Transfers</small>
                    <strong class="fs-6">{{ $transactions->getCollection()->where('source_type', 'transfer')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-boxes text-warning fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Total Cartons</small>
                    <strong class="fs-6">{{ number_format($transactions->getCollection()->sum(fn($tx) => $tx->items->sum('units_dispatch'))) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2"></i>Outbound Records</h6>
    </div>

    <div class="card-body p-3">
        <form id="outboundFilterForm" class="mb-0">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-funnel me-1"></i>Type</label>
                    <select name="source_type" id="filter_source_type" class="form-select form-select-sm filter-field">
                        <option value="">All Types</option>
                        <option value="sale">Sale</option>
                        <option value="transfer">Transfer</option>
                        <option value="return">Return</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-buildings me-1"></i>Warehouse</label>
                    <select name="warehouse_id" id="filter_warehouse" class="form-select form-select-sm filter-field">
                        <option value="">All Warehouses</option>
                        @foreach(\App\Models\Warehouse::orderBy('name')->get() as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-people me-1"></i>Customer</label>
                    <select name="customer_id" id="filter_customer" class="form-select form-select-sm filter-field">
                        <option value="">All Customers</option>
                        @foreach(\App\Models\Customer::orderBy('name')->get() as $cust)
                            <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-tag me-1"></i>Product Group</label>
                    <select name="product_group_id" id="filter_product_group" class="form-select form-select-sm filter-field">
                        <option value="">All Groups</option>
                        @foreach($productGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-box me-1"></i>Product</label>
                    <select name="product_id" id="filter_product" class="form-select form-select-sm filter-field">
                        <option value="">All Products</option>
                        @foreach(\App\Models\Product::orderBy('name')->get() as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-receipt me-1"></i>Dispatch No</label>
                    <select name="dispatch_no[]" id="filter_dispatch_no" class="form-select form-select-sm filter-field" multiple data-placeholder="All Dispatch Nos">
                        @foreach($dispatchNos as $no)
                            <option value="{{ $no }}">{{ $no }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-calendar me-1"></i>Date From</label>
                    <input type="date" name="date_from" id="filter_date_from" class="form-control form-control-sm filter-field">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-calendar me-1"></i>Date To</label>
                    <input type="date" name="date_to" id="filter_date_to" class="form-control form-control-sm filter-field">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small"><i class="bi bi-search me-1"></i>Search</label>
                    <input type="text" name="search" id="filter_search" class="form-control form-control-sm" placeholder="Vehicle No, Driver Name, Customer, Transporter, Invoice No...">
                </div>
            </div>
            <div class="mt-2 d-flex align-items-center gap-2">
                <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
                    <i class="bi bi-search"></i> Apply Filters
                </button>
                <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div id="filterLoadingOverlay" style="display: none; position: relative;">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Applying filters...</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success rounded-0 mb-0 border-0">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-responsive rounded-bottom mt-3">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-dark small">
                    <tr class="text-nowrap">
                        <th width="46">#</th>
                        <th>Invoice No</th>
                        <th>Outbound Date</th>
                        <th>Warehouse</th>
                        <th>To / Customer</th>
                        <th>Transporter</th>
                        <th class="text-center">Products</th>
                        <th class="text-end">Total Cartons</th>
                        <th>Vehicle / Driver</th>
                        <th class="text-center" width="220">Action</th>
                    </tr>
                </thead>

                <tbody id="outboundTableBody">
                @forelse($transactions as $tx)
                    @php
                        $target = $tx->source_type === 'sale'
                            ? ($tx->customer->name ?? '-')
                            : ($tx->toWarehouse->name ?? '-');
                        $whName = $tx->warehouse->name ?? '-';
                        $itemCount = $tx->items->count();
                        $totalCartons = number_format($tx->items->sum('units_dispatch'));
                    @endphp

                    <tr>
                        <td class="text-muted" style="font-size:11px;">
                            {{ ($transactions->currentPage()-1)*$transactions->perPage() + $loop->iteration }}
                        </td>

                        <td>
                            <span class="fw-bold" style="color:#1d4ed8; font-family:'Courier New',monospace; font-size:12px;">
                                {{ $tx->dispatched_invoice_no ?: ('#OUT-'.$tx->id) }}
                            </span>
                        </td>

                        <td style="font-size:11.5px; color:#64748b;">
                            {{ $tx->created_at->format('d.m.Y h:i A') }}
                        </td>

                        <td>
                            <span class="badge bg-secondary"><i class="bi bi-building me-1"></i>{{ $whName }}</span>
                        </td>

                        <td class="fw-semibold small">{{ $target }}</td>

                        <td class="small">{{ $tx->transporter->name ?? '—' }}</td>

                        <td class="text-center">
                            <span style="background:#f1f5f9;color:#475569;padding:3px 9px;border-radius:12px;font-size:11px;font-weight:700;border:1px solid #e2e8f0;">
                                {{ $itemCount }} {{ Str::plural('item', $itemCount) }}
                            </span>
                        </td>

                        <td class="text-end fw-bold" style="color:#0f172a;">
                            {{ $totalCartons }}
                        </td>

                        <td class="small text-nowrap">
                            <div>{{ $tx->vehicle_no ?: '—' }}</div>
                            <small class="text-muted">{{ $tx->driver_name ?: '' }}</small>
                        </td>

                        {{-- ACTIONS --}}
                        <td class="text-center text-nowrap">
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button" class="btn btn-sm btn-primary text-white fw-bold view-outbound-batches-btn"
                                    style="font-size:11px; padding:4px 12px; border-radius:20px; border:none;"
                                    data-stock-out-id="{{ $tx->id }}"
                                    data-invoice="{{ $tx->dispatched_invoice_no ?: '#OUT-'.$tx->id }}"
                                    title="View Full Outbound Details">
                                    <i class="bi bi-list-columns-reverse me-1"></i> Details
                                </button>

                                <a href="{{ route('outbound.edit', $tx->id) }}"
                                   class="btn btn-sm btn-warning text-white fw-bold d-inline-flex align-items-center gap-1 shadow-sm"
                                   style="font-size:11px; padding:4px 12px; border-radius:20px; border:none; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"
                                   title="Edit Entry">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>

                                <a href="{{ route('outbound.invoice', $tx->id) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   target="_blank" title="Pick List">
                                    <i class="bi bi-file-text"></i>
                                </a>

                                <a href="{{ route('outbound.dispatch_details', $tx->id) }}"
                                   class="btn btn-sm btn-outline-info"
                                   target="_blank" title="Dispatch Details">
                                    <i class="bi bi-receipt"></i>
                                </a>

                                <a href="{{ route('outbound.dc', $tx->id) }}"
                                   class="btn btn-sm btn-outline-dark"
                                   target="_blank" title="Gate Pass">
                                    <i class="bi bi-file-earmark"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            <p class="mb-0">No outbound records found</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-top-0 py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} records</small>
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</div>

{{-- SUPPORTIVE MODAL --}}
<div class="modal fade" id="supportiveModal" tabindex="-1" aria-labelledby="supportiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="supportiveModalLabel">Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                {{-- Product Information --}}
                <div class="card border mb-3">
                    <div class="card-header bg-light border-bottom">
                        <i class="bi bi-box-seam me-2"></i><strong>Product Information</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-2" id="productInfo"></div>
                    </div>
                </div>

                {{-- Warehouse & Location --}}
                <div class="card border mb-3">
                    <div class="card-header bg-light border-bottom">
                        <i class="bi bi-building me-2"></i><strong>Warehouse & Location</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-2" id="warehouseInfo"></div>
                    </div>
                </div>

                <div class="card border mb-3">
                    <div class="card-header bg-light border-bottom">
                        <i class="bi bi-upc-scan me-2"></i><strong>Batch & Reference Numbers</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-2" id="batchInfo"></div>
                    </div>
                </div>

                {{-- Quantities & Dates --}}
                <div class="card border mb-3">
                    <div class="card-header bg-light border-bottom">
                        <i class="bi bi-boxes me-2"></i><strong>Quantities & Dates</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-2" id="quantityInfo"></div>
                    </div>
                </div>

                <div class="card border mb-3">
                    <div class="card-header bg-light border-bottom">
                        <i class="bi bi-truck me-2"></i><strong>Vehicle & Transport Details</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-2" id="vehicleInfo"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    /* ===== Minimal Modal ===== */
    #supportiveModal .modal-content {
        border-radius: 8px;
        border: 0;
        background: #ffffff;
    }

    #supportiveModal .modal-header {
        background: #f8f9fa;
        color: #212529;
        border-bottom: 1px solid #dee2e6;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    #supportiveModal .modal-title {
        font-weight: 600;
        letter-spacing: 0.2px;
    }

    #supportiveModal .btn-close {
        filter: none;
    }

    #supportiveModal .modal-body {
        background: #fdfdfd;
    }

    #supportiveModal .card {
        border-radius: 8px;
        border: 1px solid #e9ecef !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }

    #supportiveModal .card-header {
        background: #f8f9fa !important;
        color: #495057 !important;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef !important;
    }
</style>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalTitle = document.getElementById('supportiveModalLabel');

    function formatValue(value) {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-muted">-</span>';
        }
        if (typeof value === 'boolean') {
            return value ?
                '<span class="badge bg-success rounded-pill">Yes</span>' :
                '<span class="badge bg-secondary rounded-pill">No</span>';
        }
        return `<strong>${String(value)}</strong>`;
    }

    function renderSection(containerId, data) {
        const container = document.getElementById(containerId);
        if(!container) return;
        container.innerHTML = '';
        Object.keys(data || {}).forEach(key => {
            container.insertAdjacentHTML('beforeend', `
                <div class="col-md-6">
                    <div class="p-2 border-bottom">
                        <small class="text-muted d-block">${key}</small>
                        <div>${formatValue(data[key])}</div>
                    </div>
                </div>
            `);
        });
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-more');
        if (!btn) return;

        modalTitle.textContent = btn.getAttribute('data-title') || 'Details';

        let headerData = {};
        let itemData = {};

        try { headerData = JSON.parse(btn.getAttribute('data-header') || '{}'); } catch (e) {}
        try { itemData = JSON.parse(btn.getAttribute('data-item') || '{}'); } catch (e) {}

        // Product Information
        renderSection('productInfo', {
            'Product': itemData['Product'],
            'Category': itemData['Category'],
            'UOM': itemData['UOM'],
            'Packing': itemData['Packing']
        });

        // Warehouse & Location
        renderSection('warehouseInfo', {
            'Outbound Type': headerData['Outbound Type'],
            'From Warehouse': headerData['From Warehouse'],
            'To / Customer': headerData['To / Customer'],
            'Pallet Location': itemData['Pallet Location'],
            'Transporter': headerData['Transporter']
        });

        // Batch & Reference
        renderSection('batchInfo', {
            'SAP Batch': itemData['SAP Batch'],
            'Vendor Batch': itemData['Vendor Batch'],
            'PO No': itemData['PO No'],
            'IBD No': itemData['IBD No'],
            'STO No': itemData['STO No'],
            'Shipment No': headerData['Shipment No'],
            'Delivery No': headerData['Delivery No'],
            'Gatepass No': headerData['Gatepass No']
        });

        // Quantities & Dates
        renderSection('quantityInfo', {
            'Units Dispatch': itemData['Units Dispatch'],
            'Pack Size': itemData['Pack Size'],
            'Dispatch Quantity': itemData['Dispatch Quantity'],
            'Pallets Returned': itemData['Pallets Returned'],
            'MFG Date': itemData['MFG Date'],
            'Expiry Date': itemData['Expiry Date']
        });

        // Vehicle & Transport
        renderSection('vehicleInfo', {
            'Vehicle No': headerData['Vehicle No'],
            'Vehicle Size': headerData['Vehicle Size'],
            'Vehicle In Time': headerData['Vehicle In Time'],
            'Vehicle Out Time': headerData['Vehicle Out Time'],
            'Driver Name': headerData['Driver Name'],
            'Driver Mobile': headerData['Driver Mobile'],
            'Dispatched Invoice No': headerData['Dispatched Invoice No']
        });
    });
});

$(document).ready(function() {
    $('#filter_dispatch_no').select2({
        placeholder: "All Dispatch Nos",
        allowClear: true,
        width: '100%'
    });

    $('#applyFilters').on('click', function() {
        applyFilters();
    });

    $('#resetFilters').on('click', function() {
        $('#outboundFilterForm')[0].reset();
        applyFilters();
    });

    $('.filter-field').on('change', function() {
        applyFilters();
    });

    function applyFilters() {
        const formData = {
            source_type: $('#filter_source_type').val(),
            warehouse_id: $('#filter_warehouse').val(),
            customer_id: $('#filter_customer').val(),
            product_group_id: $('#filter_product_group').val(),
            product_id: $('#filter_product').val(),
            dispatch_no: $('#filter_dispatch_no').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val(),
            search: $('#filter_search').val()
        };

        $('#filterLoadingOverlay').show();

        $.ajax({
            url: '{{ route("outbound.index") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');

                const newTableBody = doc.querySelector('#outboundTableBody');
                if (newTableBody) {
                    $('#outboundTableBody').html(newTableBody.innerHTML);
                }

                const totalCount = $(newTableBody).find('tr').length;
                $('#totalCount').text(totalCount);

                $('#filterLoadingOverlay').hide();

                // Clear selection on filter
                if (document.getElementById('selectAllCheckbox')) {
                    document.getElementById('selectAllCheckbox').checked = false;
                    document.getElementById('selectAllCheckbox').indeterminate = false;
                }
                $('#selectionToolbar').removeClass('d-flex').addClass('d-none');
                $('#hiddenInputsContainer').empty();
            },
            error: function(xhr, status, error) {
                console.error('Filter error:', error);
                alert('An error occurred while filtering. Please try again.');
                $('#filterLoadingOverlay').hide();
            }
        });
    }
});

function exportOutbound() {
    const params = new URLSearchParams({
        source_type: $('#filter_source_type').val(),
        warehouse_id: $('#filter_warehouse').val(),
        customer_id: $('#filter_customer').val(),
        product_group_id: $('#filter_product_group').val(),
        product_id: $('#filter_product').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val(),
        search: $('#filter_search').val()
    });

    const dispatchNos = $('#filter_dispatch_no').val();
    if (dispatchNos && dispatchNos.length > 0) {
        dispatchNos.forEach(dn => params.append('dispatch_no[]', dn));
    }

    window.location.href = '{{ route("outbound.export") }}?' + params.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const selectionToolbar = document.getElementById('selectionToolbar');
    const selectionCount = document.getElementById('selectionCount');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const hiddenInputsContainer = document.getElementById('hiddenInputsContainer');

    function updateToolbar() {
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
        const count = selectedCheckboxes.length;
        
        if (count > 0) {
            selectionToolbar.classList.remove('d-none');
            selectionToolbar.classList.add('d-flex');
            selectionCount.textContent = count + ' selected';
            
            if(selectAllCheckbox) {
                selectAllCheckbox.checked = count === rowCheckboxes.length;
                selectAllCheckbox.indeterminate = count > 0 && count < rowCheckboxes.length;
            }
            
            hiddenInputsContainer.innerHTML = '';
            selectedCheckboxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_ids[]';
                input.value = cb.value;
                hiddenInputsContainer.appendChild(input);
            });
        } else {
            selectionToolbar.classList.add('d-none');
            selectionToolbar.classList.remove('d-flex');
            if(selectAllCheckbox) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
            hiddenInputsContainer.innerHTML = '';
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            rowCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            updateToolbar();
        });
    }

    $(document).on('change', '.row-checkbox', function() {
        updateToolbar();
    });

    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function() {
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            rowCheckboxes.forEach(cb => cb.checked = false);
            updateToolbar();
        });
    }
});

// Outbound Entry Details Modal Handler
// Outbound Entry Details Modal Handler
$(document).on('click', '.view-outbound-batches-btn', function() {
    var stockOutId = this.dataset.stockOutId;
    var invoice = this.dataset.invoice;

    document.getElementById('outboundModalInvoiceName').innerText = invoice;
    document.getElementById('outboundBatchLoadingState').style.display = 'flex';
    document.getElementById('outboundBatchKpiStrip').style.display = 'none';
    var tbody = document.getElementById('outboundBatchesTableBody');
    tbody.innerHTML = '';

    new bootstrap.Modal(document.getElementById('outboundBatchesModal')).show();

    fetch('/outbound/' + stockOutId + '/items', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('outboundBatchLoadingState').style.display = 'none';

        if (!data || !data.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5 text-muted">No items found</td></tr>';
            return;
        }

        var totalUnits = 0, totalPallets = 0, totalQty = 0;
        data.forEach(function(item) {
            totalUnits   += parseInt(item.units_dispatch) || 0;
            totalPallets += parseInt(item.pallets_returned) || 0;
            totalQty     += parseFloat(item.dispatch_quantity || 0);
        });

        document.getElementById('outboundKpiItems').textContent   = data.length;
        document.getElementById('outboundKpiUnits').textContent   = totalUnits.toLocaleString();
        document.getElementById('outboundKpiPallets').textContent = totalPallets;
        document.getElementById('outboundKpiQty').textContent     = totalQty.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('outboundBatchKpiStrip').style.display = 'flex';

        var fmtDate = function(str) {
            if (!str) return '—';
            var d = new Date(str);
            return d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
        };

        var escapeHtml = function(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        };

        data.forEach(function(item) {
            var wh = item.warehouse ? item.warehouse.name : '—';
            var rowName = item.warehouse_row ? item.warehouse_row.row_name : '';
            var palletCode = item.pallet_code_display || (item.pallet_position ? ('Pallet ' + item.pallet_position) : (rowName || '—'));

            var locBadge = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold" style="font-size:11px;"><i class="bi bi-geo-alt-fill me-1"></i>' + escapeHtml(palletCode) + '</span>';
            if (rowName && palletCode !== rowName) {
                locBadge += '<small class="text-muted d-block mt-1" style="font-size:10px;"><i class="bi bi-layers me-1"></i>' + escapeHtml(rowName) + '</small>';
            }

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="fw-bold text-dark ps-3 py-3">' + escapeHtml(item.product ? item.product.item_code + ' - ' + item.product.name : '—') + '</td>' +
                '<td class="py-3"><span class="badge bg-secondary" style="font-size:10px;"><i class="bi bi-building me-1"></i>' + escapeHtml(wh) + '</span></td>' +
                '<td class="py-3">' + locBadge + '</td>' +
                '<td class="py-3 font-monospace small text-primary fw-bold">' + escapeHtml(item.po_no || '—') + '</td>' +
                '<td class="py-3 font-monospace small text-primary fw-bold">' + escapeHtml(item.ibd_no || '—') + '</td>' +
                '<td class="py-3 font-monospace small">' + escapeHtml(item.sap_batch || '—') + '</td>' +
                '<td class="py-3 font-monospace small">' + escapeHtml(item.vendor_batch || '—') + '</td>' +
                '<td class="py-3 text-end font-monospace fw-bold fs-6">' + (parseInt(item.units_dispatch)||0).toLocaleString() + '</td>' +
                '<td class="py-3 text-end font-monospace fw-bold text-success fs-6">' + parseFloat(item.dispatch_quantity||0).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>' +
                '<td class="py-3 pe-3 fw-bold text-danger">' + fmtDate(item.expiry_date) + '</td>';
            tbody.appendChild(tr);
        });
    })
    .catch(function(err) {
        console.error(err);
        document.getElementById('outboundBatchLoadingState').style.display = 'none';
        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger py-5">Failed to load details</td></tr>';
    });
});
</script>

{{-- ══════════ OUTBOUND BATCHES DETAIL MODAL ══════════ --}}
<div class="modal fade" id="outboundBatchesModal" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:1080px;">
<div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden;">

  {{-- Modal Header --}}
  <div class="modal-header" style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%); border:none; padding:18px 24px;">
    <div class="d-flex align-items-center gap-3">
      <div style="width:42px; height:42px; background:rgba(59,130,246,.25); border-radius:12px; display:flex; align-items:center; justify-content:center;">
        <i class="bi bi-box-arrow-up-right" style="color:#93c5fd; font-size:20px;"></i>
      </div>
      <div>
        <h6 class="modal-title mb-0 text-white fw-bold" style="font-size:15px; letter-spacing:.3px;">
          Outbound Dispatch Details
        </h6>
        <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25 mt-1" id="outboundModalInvoiceName" style="font-size:11px;"></span>
      </div>
    </div>
    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>

  {{-- KPI Strip --}}
  <div class="kpi-strip" id="outboundBatchKpiStrip" style="display:none; background:#ffffff; border-bottom:1px solid #e2e8f0;">
    <div class="kpi-card kpi-blue">
      <div class="kpi-icon"><i class="bi bi-archive"></i></div>
      <div class="kpi-val" id="outboundKpiItems">—</div>
      <div class="kpi-lbl">Products</div>
    </div>
    <div class="kpi-card kpi-green">
      <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
      <div class="kpi-val" id="outboundKpiUnits">—</div>
      <div class="kpi-lbl">Dispatched Cartons</div>
    </div>
    <div class="kpi-card kpi-purple">
      <div class="kpi-icon"><i class="bi bi-layers"></i></div>
      <div class="kpi-val" id="outboundKpiPallets">—</div>
      <div class="kpi-lbl">Total Pallets</div>
    </div>
    <div class="kpi-card kpi-orange">
      <div class="kpi-icon"><i class="bi bi-stack"></i></div>
      <div class="kpi-val" id="outboundKpiQty">—</div>
      <div class="kpi-lbl">Total Qty</div>
    </div>
  </div>

  {{-- Body --}}
  <div class="modal-body p-0" style="background:#fff;">
    <div id="outboundBatchLoadingState" class="state-box py-5 text-center" style="display:none;">
      <div class="spinner-border text-primary" style="width:36px; height:36px; border-width:3px;"></div>
      <p class="mt-2 text-muted small fw-semibold">Loading dispatch details...</p>
    </div>

    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px;">
        <thead class="table-dark">
          <tr class="text-nowrap" style="font-size:11px; letter-spacing:0.4px;">
            <th class="py-2.5 ps-3">PRODUCT CODE & NAME</th>
            <th class="py-2.5">SOURCE WAREHOUSE</th>
            <th class="py-2.5">LOCATION / PALLET</th>
            <th class="py-2.5">PO #</th>
            <th class="py-2.5">IBD #</th>
            <th class="py-2.5">SAP BATCH</th>
            <th class="py-2.5">VENDOR BATCH</th>
            <th class="py-2.5 text-end">CARTONS</th>
            <th class="py-2.5 text-end">QTY</th>
            <th class="py-2.5 pe-3">EXPIRY DATE</th>
          </tr>
        </thead>
        <tbody id="outboundBatchesTableBody"></tbody>
      </table>
    </div>
  </div>

</div>
</div>
</div>
@endpush
