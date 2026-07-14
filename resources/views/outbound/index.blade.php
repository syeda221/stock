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
</style>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0">Outbound / Dispatch</h5>
        <small class="text-muted">Manage outward stock movements</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('outbound.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>New Outbound
        </a>
        <a href="javascript:void(0)" onclick="exportOutbound()" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i> Export
        </a>
        <a href="{{ route('outbound.import') }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-upload me-1"></i> Import
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
                    <small class="text-muted d-block">Total Records</small>
                    <strong class="fs-6">{{ $items->total() }}</strong>
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
                    <strong class="fs-6">{{ $items->where('stockOut.source_type', 'sale')->count() }}</strong>
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
                    <strong class="fs-6">{{ $items->where('stockOut.source_type', 'transfer')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-arrow-return-left text-warning fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Returns</small>
                    <strong class="fs-6">{{ $items->where('stockOut.source_type', 'return')->count() }}</strong>
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
                <span class="ms-auto text-muted small">Total: <strong id="totalCount">{{ $items->count() }}</strong> records</span>
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

        <div id="selectionToolbar" class="d-none align-items-center text-white p-2 rounded m-3 shadow-sm" style="background-color: var(--bs-primary) !important;">
            <div class="me-auto fw-semibold ms-2" id="selectionCount">0 selected</div>
            <form id="exportSelectedForm" method="POST" action="{{ route('outbound.exportSelected') }}" class="m-0 p-0 d-flex align-items-center">
                @csrf
                <div id="hiddenInputsContainer"></div>
                <button type="submit" class="btn btn-sm btn-outline-light me-2">
                    <i class="bi bi-file-earmark-arrow-down"></i> Export selected
                </button>
            </form>
            <button type="button" class="btn btn-sm btn-light text-primary fw-semibold me-2" id="clearSelectionBtn">Clear</button>
        </div>

        <div class="table-responsive rounded-bottom">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-primary small">
                    <tr class="text-nowrap">
                        <th style="width:30px">
                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input shadow-none">
                        </th>
                        <th>WH</th>
                        <th>To / Customer</th>
                        <th>Product</th>
                        <th>Group</th>
                        <th class="text-end">Units</th>
                        <th class="text-end">Pack</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Pallets</th>
                        <th>Vehicle</th>
                        <th>Dispatch No</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody id="outboundTableBody">
                @forelse($items as $item)
                    @php
                        $out = $item->stockOut;

                        $badge = match($out->source_type){
                            'sale'     => 'success',
                            'transfer' => 'info',
                            'return'   => 'warning',
                            default    => 'secondary'
                        };

                        $target = $out->source_type === 'sale'
                            ? ($out->customer->name ?? '-')
                            : ($out->toWarehouse->name ?? '-');

                        $productText = ($item->product->item_code ?? '') . ' - ' . ($item->product->name ?? '-');

                        $headerData = [
                            'Outbound Type'     => ucfirst($out->source_type),
                            'From Warehouse'    => $out->warehouse->name ?? '-',
                            'To / Customer'     => $target,
                            'Transporter'       => $out->transporter->name ?? '-',
                            'Shipment No'       => $out->shipment_no ?? '-',
                            'Delivery No'       => $out->delivery_no ?? '-',
                            'Gatepass No'       => $out->gatepass_no ?? '-',
                            'Shipment Type'     => strtoupper($out->shipment_type ?? 'MANUAL'),
                            'Vehicle No'        => $out->vehicle_no ?? '-',
                            'Vehicle Size'      => $out->vehicle_size ?? '-',
                            'Vehicle In Time'   => $out->vehicle_in_time ? \Carbon\Carbon::parse($out->vehicle_in_time)->format('d.m.Y H:i') : '-',
                            'Vehicle Out Time'  => $out->vehicle_out_time ? \Carbon\Carbon::parse($out->vehicle_out_time)->format('d.m.Y H:i') : '-',
                            'Driver Name'       => $out->driver_name ?? '-',
                            'Driver Mobile'     => $out->driver_mobile ?? '-',
                            'Dispatched Invoice No' => $out->dispatched_invoice_no ?? '-',
                            'Dispatcher Sig'    => $out->dispatcher_sig ?? '-',
                            'Picker'            => $out->picker ?? '-',
                            'Header Remarks'    => $out->remarks ?? '-',
                        ];

                        $sourceItem = $item->sourceStockInItem;
                        $palletLocationStr = 'Unassigned';
                        $rowName = $item->warehouseRow->row_name ?? ($sourceItem->warehouseRow->row_name ?? '-');

                        if ($item->warehouse_row_id || ($sourceItem && $sourceItem->warehouse_row_id)) {
                            $rowId = $item->warehouse_row_id ?? $sourceItem->warehouse_row_id;

                            if ($item->pallet_position) {
                                $offset = 0;
                                if ($sourceItem) {
                                    $offset = \App\Models\StockInItem::where('warehouse_row_id', $rowId)
                                        ->where('id', '<', $sourceItem->id)
                                        ->sum('pallets_used');
                                }
                                $palletLocationStr = "Row " . $rowName . " (Pallet " . ($offset + $item->pallet_position) . ")";
                            } else {
                                $pallets = $item->pallets_returned > 0 ? $item->pallets_returned : 0;
                                if ($pallets > 0) {
                                    $offset = 0;
                                    if ($sourceItem) {
                                        $offset = \App\Models\StockInItem::where('warehouse_row_id', $rowId)
                                            ->where('id', '<', $sourceItem->id)
                                            ->sum('pallets_used');
                                    }
                                    $start = $offset + 1;
                                    $end = $offset + $pallets;
                                    if ($start == $end) {
                                        $palletLocationStr = "Row " . $rowName . " (Pallet " . $start . ")";
                                    } else {
                                        $palletLocationStr = "Row " . $rowName . " (Pallets " . $start . "-" . $end . ")";
                                    }
                                } else {
                                    $palletLocationStr = "Row " . $rowName;
                                }
                            }
                        }

                        $itemData = [
                            'Product'          => ($item->product?->item_code ?? '-') . ' - ' . ($item->product?->name ?? '-'),
                            'Category'         => $item->product?->category?->name ?? '-',
                            'UOM'              => $item->product?->uom?->name ?? ($item->uom_snapshot ?? '-'),
                            'Packing'          => $item->product?->packingType?->name ?? ($item->packing_snapshot ?? '-'),
                            'SAP Batch'        => $item->sap_batch ?? '-',
                            'Vendor Batch'     => $item->vendor_batch ?? '-',
                            'PO No'            => $item->po_no ?? '-',
                            'IBD No'           => $item->ibd_no ?? '-',
                            'STO No'           => $item->sto_no ?? '-',
                            'MFG Date'         => $item->mfg_date ? \Carbon\Carbon::parse($item->mfg_date)->format('d.m.Y') : '-',
                            'Expiry Date'      => $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('d.m.Y') : '-',
                            'Units Dispatch'   => $item->units_dispatch ?? 0,
                            'Pack Size'        => $item->pack_size_snapshot ?? 0,
                            'Dispatch Quantity'=> $item->dispatch_quantity ?? 0,
                            'Pallet Location'  => $palletLocationStr,
                            'Pallets Returned' => $item->pallets_returned ?? 0,
                            'Item Remarks'     => $item->remarks ?? '-',
                        ];
                    @endphp

                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input row-checkbox shadow-none" value="{{ $item->id }}">
                        </td>
                        <td>
                            @php
                                $whName = $item->warehouse->name ?? $out->warehouse->name ?? '-';
                            @endphp
                            <div class="fw-bold text-nowrap small">{{ $whName }}</div>
                        </td>

                        <td class="fw-semibold small text-nowrap">{{ $target }}</td>

                        <td>
                            <div class="text-truncate small" style="max-width:180px"
                                 title="{{ $productText }}">
                                {{ $productText }}
                            </div>
                        </td>

                        <td>
                            @if(optional($item->product)->group)
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1" style="font-size: 9px;">
                                    {{ optional($item->product)->group->name }}
                                </span>
                            @else
                                <span class="text-muted" style="font-size: 9px;">—</span>
                            @endif
                        </td>

                        <td class="text-end small">{{ $item->units_dispatch ?? '-' }}</td>
                        <td class="text-end small">{{ $item->pack_size_snapshot ?? '-' }}</td>
                        <td class="text-end fw-bold small">{{ number_format($item->dispatch_quantity,2) }}</td>
                        <td class="text-end fw-bold text-primary small">
                            {{ $item->pallets_returned > 0 ? $item->pallets_returned : '-' }}
                        </td>

                        <td class="small text-nowrap">
                            {{ $out->vehicle_no ?? '-' }}
                            <div class="text-muted" style="font-size: 10px;">
                                {{ $out->driver_name ?? '' }}
                            </div>
                        </td>

                        <td class="small fw-semibold text-nowrap">{{ $out->dispatched_invoice_no ?? '-' }}</td>

                        <td class="small text-nowrap">{{ $item->created_at->format('d.m.Y H:i') }}</td>

                        {{-- ACTIONS --}}
                        <td class="text-center text-nowrap">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm btn-outline-primary js-more"
                                    data-bs-toggle="modal"
                                    data-bs-target="#supportiveModal"
                                    data-title="Outbound Item Details"
                                    data-header='@json($headerData)'
                                    data-item='@json($itemData)'
                                    title="View">
                                    <i class="bi bi-eye"></i>
                                </button>

                                <a href="{{ route('outbound.invoice', $out->id) }}"
                                   class="btn btn-sm btn-outline-success"
                                   target="_blank" title="Pick List">
                                    <i class="bi bi-file-text"></i>
                                </a>

                                <a href="{{ route('outbound.dispatch_details', $out->id) }}"
                                   class="btn btn-sm btn-outline-info"
                                   target="_blank"
                                   title="Dispatch Details">
                                    <i class="bi bi-receipt"></i>
                                </a>

                                <a href="{{ route('outbound.dc', $out->id) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   target="_blank" title="DC">
                                    <i class="bi bi-file-earmark"></i>
                                </a>

                                <a href="{{ route('outbound.edit', $out->id) }}"
                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('outbound.destroy', $out->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <!-- <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete outbound?')"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button> -->
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center py-4 text-muted">
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
                <small class="text-muted">Showing {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} records</small>
                {{ $items->links() }}
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
</script>
@endpush
