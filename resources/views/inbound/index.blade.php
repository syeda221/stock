@extends('layouts.app')

@section('content')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.bg-orange {
    background-color: #fd7e14 !important;
}
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

<!-- <style>
    .wms-table th, .wms-table td { vertical-align: middle; }
    .wms-nowrap { white-space: nowrap; }
    .wms-ellipsis { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wms-pill { border-radius: 999px; }

@media(max-width: 991.98px) {
        .wms-table thead { display: none; }
        .wms-table, .wms-table tbody, .wms-table tr, .wms-table td { display: block; width: 100%; }
        .wms-table tr { border: 1px solid #e9ecef; border-radius: 10px; margin: 10px 10px; overflow: hidden; }
        .wms-table td {
            border: none;
            border-bottom: 1px solid #f1f3f5;
            padding: 10px 12px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        .wms-table td:last-child { border-bottom: none; }
        .wms-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #495057;
            min-width: 40%;
        }
        .wms-ellipsis { max-width: 100%; }
    }
</style> -->

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0">Inbound Stock</h5>
        <small class="text-muted">Batch-wise inbound stock management</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inbound.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add Inbound
        </a>
        <a href="javascript:void(0)" onclick="exportInbound()" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i> Export
        </a>
        <a href="{{ route('inbound.import') }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-upload me-1"></i> Import
        </a>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-box-seam text-primary fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Total Batches</small>
                    <strong class="fs-6">{{ $items->total() }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-check-circle text-success fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">QC Approved</small>
                    <strong class="fs-6">{{ $items->where('quality_clearance', 'approved')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-exclamation-circle text-warning fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">QC Pending</small>
                    <strong class="fs-6">{{ $items->where('quality_clearance', 'pending')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-danger bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-x-circle text-danger fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">QC Rejected</small>
                    <strong class="fs-6">{{ $items->where('quality_clearance', 'rejected')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2"></i>Inbound Batches</h6>
    </div>

    <div class="card-body p-3">
        <!-- Filters -->
        <form id="inboundFilterForm" class="mb-0" onsubmit="return false;">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-funnel me-1"></i>QC Status</label>
                    <select name="qc_status" id="filter_qc_status" class="form-select form-select-sm filter-field">
                        <option value="">All Status</option>
                        <option value="pending">🟡 Pending</option>
                        <option value="approved">🟢 Approved</option>
                        <option value="rejected">🔴 Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-buildings me-1"></i>Warehouse</label>
                    <select name="warehouse_id" id="filter_warehouse" class="form-select form-select-sm filter-field">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-truck me-1"></i>Vendor</label>
                    <select name="vendor_id" id="filter_vendor" class="form-select form-select-sm filter-field">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
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
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-receipt me-1"></i>Inbound Invoice</label>
                    <select name="inbound_invoices[]" id="filter_inbound_invoices" class="form-select form-select-sm filter-field" multiple data-placeholder="All Invoices">
                        @foreach($inboundInvoices as $invoice)
                            <option value="{{ $invoice }}">{{ $invoice }}</option>
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
                    <input type="text" name="search" id="filter_search" class="form-control form-control-sm" placeholder="Transport, Driver, Vehicle, Shipment, PO No, Remarks...">
                </div>
            </div>
            <div class="mt-2 d-flex align-items-center gap-2">
                <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
                    <i class="bi bi-search"></i> Apply Filters
                </button>
                <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <span class="ms-auto text-muted small">Total: <strong id="totalCount">{{ $items->count() }}</strong> batches</span>
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
            <div class="alert alert-success m-3">
                {{ session('success') }}
            </div>
        @endif

        <div id="selectionToolbar" class="d-none align-items-center text-white p-2 rounded m-3 shadow-sm" style="background-color: var(--bs-primary) !important;">
            <div class="me-auto fw-semibold ms-2" id="selectionCount">0 selected</div>
            <form id="exportSelectedForm" method="POST" action="{{ route('inbound.exportSelected') }}" class="m-0 p-0 d-flex align-items-center">
                @csrf
                <div id="hiddenInputsContainer"></div>
                <button type="submit" class="btn btn-sm btn-outline-light me-2">
                    <i class="bi bi-file-earmark-arrow-down"></i> Export selected
                </button>
            </form>
            <button type="button" class="btn btn-sm btn-light text-primary fw-semibold me-2" id="clearSelectionBtn">Clear</button>
        </div>

        <div class="table-responsive rounded-bottom">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-primary small">
                    <tr>
                        <th style="width:30px">
                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input shadow-none">
                        </th>
                        <th style="width:40px">#</th>
                        <th style="min-width:140px">Product</th>
                        <th style="width:90px">Group</th>
                        <th style="width:90px">Warehouse</th>
                        <th class="text-end" style="width:55px">Units</th>
                        <th class="text-end" style="width:65px">Balance</th>
                        <th style="width:80px">Status</th>
                        <th style="width:100px">QC</th>
                        <th style="width:110px">Vehicle/Driver</th>
                        <th style="width:110px">Inbound Invoice</th>
                        <th style="width:80px">Days</th>
                        <th style="width:80px">Date</th>
                        <th style="width:80px" class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody id="inboundTableBody">
                    @forelse($items as $item)
                        @php
                            $itemRemarks = trim((string) ($item->remarks ?? ''));
                            $headerRemarks = trim((string) ($item->stockIn->remarks ?? ''));
                            $remarks = $itemRemarks !== '' ? $itemRemarks : ($headerRemarks !== '' ? $headerRemarks :
                            '-');

                            $headerData = [
                            'Warehouse' => $item->stockIn->warehouse->name ?? '-',
                            'Vendor' => $item->stockIn->vendor->name ?? '-',
                            'Arrived From' => $item->stockIn->arrivedFrom->name ?? '-',
                            'Transporter' => $item->stockIn->transporter->name ?? '-',

                            'Shipment No' => $item->stockIn->shipment_no ?? '-',
                            'Delivery No' => $item->stockIn->delivery_no ?? '-',
                            'STO No' => $item->stockIn->sto_no ?? '-',

                            'Shipment Type' => strtoupper($item->stockIn->shipment_type ?? 'MANUAL'),

                            'Vehicle No' => $item->stockIn->vehicle_no ?? '-',
                            'Vehicle Size' => $item->stockIn->vehicle_size ?? '-',
                            'Vehicle In Time' => $item->stockIn->vehicle_in_time
                            ? \Carbon\Carbon::parse($item->stockIn->vehicle_in_time)->format('d.m.Y H:i')
                            : '-',
                            'Vehicle Out Time' => $item->stockIn->vehicle_out_time
                            ? \Carbon\Carbon::parse($item->stockIn->vehicle_out_time)->format('d.m.Y H:i')
                            : '-',

                            'Driver Name' => $item->stockIn->driver_name ?? '-',
                            'Driver Mobile' => $item->stockIn->driver_mobile ?? '-',

                            'Inbound Invoice' => $item->stockIn->dispatched_invoice_no ?? '-',
                            'Dispatcher Sig' => $item->stockIn->dispatcher_sig ?? '-',
                            'Picker' => $item->stockIn->picker ?? '-',

                            'Header Remarks' => $item->stockIn->remarks ?? '-',
                            ];

                            $palletLocationStr = 'Unassigned';
                            if ($item->warehouse_row_id && $item->pallets_used > 0) {
                                if ($item->pallet_start !== null) {
                                    $start = (int) $item->pallet_start;
                                    $end = $start + $item->pallets_used - 1;
                                } else {
                                    $offset = \App\Models\StockInItem::where('warehouse_row_id', $item->warehouse_row_id)
                                        ->where('id', '<', $item->id)
                                        ->where('balance_quantity', '>', 0)
                                        ->sum('pallets_used');
                                    $start = $offset + 1;
                                    $end = $offset + $item->pallets_used;
                                }
                                if ($start == $end) {
                                    $palletLocationStr = "Row " . ($item->warehouseRow->row_name ?? '-') . " (Pallet " . $start . ")";
                                } else {
                                    $palletLocationStr = "Row " . ($item->warehouseRow->row_name ?? '-') . " (Pallets " . $start . "-" . $end . ")";
                                }
                            } elseif ($item->warehouse_row_id) {
                                $palletLocationStr = "Row " . ($item->warehouseRow->row_name ?? '-');
                            }

                            $itemData = [

                            'Product' => ($item->product?->item_code ?? '-') . ' - ' . ($item->product?->name ?? '-'),
                            'Category' => $item->product?->category?->name ?? '-',
                            'UOM' => $item->product?->uom?->name ?? ($item->uom_snapshot ?? '-'),
                            'Packing' => $item->product?->packingType?->name ?? ($item->packing_snapshot ?? '-'),

                            'SAP Batch' => $item->sap_batch ?? '-',
                            'Vendor Batch' => $item->vendor_batch ?? '-',
                            'IBD No' => $item->ibd_no ?? '-',
                            'PO No' => $item->po_no ?? '-',

                            'MFG Date' => $item->mfg_date ? \Carbon\Carbon::parse($item->mfg_date)->format('d.m.Y') :
                            '-',
                            'Expiry Date' => $item->expiry_date ?
                            \Carbon\Carbon::parse($item->expiry_date)->format('d.m.Y') : '-',
                            'Days in Warehouse' => $item->created_at ? (int) $item->created_at->diffInDays(now()) : 0,

                            'Units Received' => $item->units_received ?? 0,
                            'Pack Size' => $item->pack_size_snapshot ?? 0,
                            'Total Quantity' => $item->total_quantity ?? 0,
                            'Balance Quantity' => $item->balance_quantity ?? 0,

                            'Pallet Location' => $palletLocationStr,
                            'Pallets Used' => $item->pallets_used ?? 0,

                            'Sound Stock' => (bool) $item->sound_stock,
                            'Block Stock' => (bool) $item->block_stock,
                            'Hold Stock' => (bool) $item->hold_stock,
                            'QC Status' => $item->quality_clearance ?? '-',
                            'Damage Stock' => (bool) ($item->damage_stock ?? false),

                            ];

                            $productText = ($item->product->item_code ?? '-') . ' - ' . ($item->product->name ?? '-');
                            $statusHtml = '';
                            if ($item->block_stock) $statusHtml = '<span
                                class="badge bg-danger wms-pill">Blocked</span>';
                            elseif ($item->hold_stock) $statusHtml = '<span
                                class="badge bg-warning text-dark wms-pill">Hold</span>';
                            elseif (!$item->sound_stock) $statusHtml = '<span class="badge bg-secondary wms-pill">Not
                                Sound</span>';
                            else $statusHtml = '<span class="badge bg-success wms-pill">Available</span>';


                            $vehicleText = $item->stockIn->vehicle_no ?? '-';
                            $driverText = $item->stockIn->driver_name ?? '-';
                        @endphp

                        <tr>
                            <td class="text-center align-middle">
                                <input type="checkbox" class="form-check-input row-checkbox shadow-none" value="{{ $item->id }}">
                            </td>
                            <td>{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>

                            <td>
                                <div class="fw-semibold">{{ $item->product->item_code ?? '-' }}</div>
                                <small class="text-muted" style="display: block; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $item->product->name ?? '-' }}">{{ $item->product->name ?? '-' }}</small>
                            </td>

                            <td>
                                @if(optional($item->product)->group)
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1" style="font-size: 10px;">
                                        {{ optional($item->product)->group->name }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 10px;">—</span>
                                @endif
                            </td>

                            <td style="font-size: 11px;">{{ Str::limit($item->stockIn->warehouse->name ?? '-', 12) }}</td>

                            <td class="text-end">{{ $item->units_received ?? 0 }}</td>
                            <td class="text-end fw-bold">
                                @php
                                    $balUnits = $item->pack_size_snapshot > 0 ? $item->balance_quantity / $item->pack_size_snapshot : 0;
                                @endphp
                                {{ rtrim(rtrim(number_format($balUnits, 2), '0'), '.') }} U<br>
                                <small class="text-muted fw-normal">({{ rtrim(rtrim(number_format($item->balance_quantity, 2), '0'), '.') }} Qty)</small>
                            </td>

                            <td>{!! $statusHtml !!}</td>

                            <td>
                                <select class="form-select form-select-sm qc-status-select qc-bg-{{ $item->quality_clearance ?? 'pending' }}"
                                        data-item-id="{{ $item->id }}"
                                        style="width: 100%; font-size: 10px;">
                                    <option value="pending" {{ ($item->quality_clearance ?? 'pending') == 'pending' ? 'selected' : '' }}>
                                        🟡 Pending
                                    </option>
                                    <option value="approved" {{ ($item->quality_clearance ?? '') == 'approved' ? 'selected' : '' }}>
                                        🟢 Approved
                                    </option>
                                    <option value="rejected" {{ ($item->quality_clearance ?? '') == 'rejected' ? 'selected' : '' }}>
                                        🔴 Rejected
                                    </option>
                                </select>
                            </td>

                            <td style="font-size: 10px;">
                                <div class="fw-semibold" style="max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $vehicleText }}">{{ Str::limit($vehicleText, 12) }}</div>
                                <small class="text-muted" style="display: block; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $driverText }}">{{ Str::limit($driverText, 12) }}</small>
                            </td>

                            <td class="text-nowrap" style="font-size: 11px;">
                                <span class="text-muted fw-semibold">{{ $item->stockIn->dispatched_invoice_no ?? '-' }}</span>
                            </td>

                            <td class="text-center">
                                @php
                                    $days = (int) $item->created_at->diffInDays(now());
                                    if ($days == 0) {
                                        $daysText = 'Today';
                                        $badgeClass = 'bg-info';
                                    } elseif ($days == 1) {
                                        $daysText = 'Last 1 day';
                                        $badgeClass = 'bg-info';
                                    } elseif ($days >= 2 && $days <= 7) {
                                        $daysText = "Last $days days";
                                        $badgeClass = 'bg-success';
                                    } elseif ($days >= 8 && $days <= 30) {
                                        $daysText = "Last $days days";
                                        $badgeClass = 'bg-warning text-dark';
                                    } elseif ($days >= 31 && $days <= 90) {
                                        $daysText = "Last $days days";
                                        $badgeClass = 'bg-orange text-white';
                                    } else {
                                        $daysText = "Last $days days";
                                        $badgeClass = 'bg-danger';
                                    }
                                @endphp
                                <span class="badge {{ $badgeClass }} px-2 py-1" style="white-space: nowrap;">{{ $daysText }}</span>
                            </td>

                            <td class="text-nowrap">
                                {{ $item->created_at ? $item->created_at->format('d.m.Y') : '-' }}
                            </td>

                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary js-more"
                                        data-bs-toggle="modal" data-bs-target="#supportiveModal"
                                        data-title="Inbound Item Details"
                                        data-header='@json($headerData)'
                                        data-item='@json($itemData)'
                                        title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="{{ route('inbound.edit', $item->stock_in_id) }}"
                                       class="btn btn-sm btn-outline-warning"
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="{{ route('reports.inbound.pdf', $item->stock_in_id) }}"
                                       class="btn btn-sm btn-outline-danger"
                                       title="PDF"
                                       target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted p-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                <p class="mb-0">No inbound stock found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
        <div class="card-footer bg-white border-top-0 py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} batches</small>
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>

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

                {{-- Stock Status & Quality --}}
                <div class="card border mb-3">
                    <div class="card-header bg-light border-bottom">
                        <i class="bi bi-clipboard-check me-2"></i><strong>Stock Status & Quality</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-2" id="statusInfo"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
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

                try {
                    headerData = JSON.parse(btn.getAttribute('data-header') || '{}');
                } catch (e) {}
                try {
                    itemData = JSON.parse(btn.getAttribute('data-item') || '{}');
                } catch (e) {}

                // Product Information
                renderSection('productInfo', {
                    'Product': itemData['Product'],
                    'Category': itemData['Category'],
                    'UOM': itemData['UOM'],
                    'Packing': itemData['Packing']
                });

                // Warehouse & Location
                renderSection('warehouseInfo', {
                    'Warehouse': headerData['Warehouse'],
                    'Pallet Location': itemData['Pallet Location'],
                    'Vendor': headerData['Vendor'],
                    'Arrived From': headerData['Arrived From'],
                    'Transporter': headerData['Transporter']
                });

                // Batch & Reference
                renderSection('batchInfo', {
                    'SAP Batch': itemData['SAP Batch'],
                    'Vendor Batch': itemData['Vendor Batch'],
                    'PO No': itemData['PO No'],
                    'IBD No': itemData['IBD No'],
                    'Shipment No': headerData['Shipment No'],
                    'Delivery No': headerData['Delivery No'],
                    'STO No': headerData['STO No']
                });

                // Quantities & Dates
                renderSection('quantityInfo', {
                    'Units Received': itemData['Units Received'],
                    'Pack Size': itemData['Pack Size'],
                    'Total Quantity': itemData['Total Quantity'],
                    'Balance Quantity': itemData['Balance Quantity'],
                    'MFG Date': itemData['MFG Date'],
                    'Expiry Date': itemData['Expiry Date'],
                    'Days in Warehouse': itemData['Days in Warehouse']
                });

                // Vehicle & Transport
                renderSection('vehicleInfo', {
                    'Vehicle No': headerData['Vehicle No'],
                    'Vehicle Size': headerData['Vehicle Size'],
                    'Vehicle In Time': headerData['Vehicle In Time'],
                    'Vehicle Out Time': headerData['Vehicle Out Time'],
                    'Driver Name': headerData['Driver Name'],
                    'Driver Mobile': headerData['Driver Mobile'],
                    'Inbound Invoice': headerData['Inbound Invoice']
                });

                // Stock Status & Quality
                renderSection('statusInfo', {
                    'Sound Stock': itemData['Sound Stock'],
                    'Block Stock': itemData['Block Stock'],
                    'Hold Stock': itemData['Hold Stock'],
                    'QC Status': itemData['QC Status'],
                    'Damage Stock': itemData['Damage Stock'],
                    'Pallets Used': itemData['Pallets Used']
                });
            });
            // QC Status Change Handler
            document.querySelectorAll('.qc-status-select').forEach(select => {
                select.addEventListener('change', function() {
                    const itemId = this.getAttribute('data-item-id');
                    const newStatus = this.value;
                    const originalValue = this.querySelector('option[selected]')?.value || 'pending';

                    // Disable select while updating
                    this.disabled = true;
                    const selectElement = this;

                    // Show loading state
                    const originalBg = this.style.backgroundColor;
                    this.style.backgroundColor = '#f0f0f0';

                    fetch(`/qc-status/${itemId}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            quality_clearance: newStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update background color class
                            selectElement.className = selectElement.className.replace(/qc-bg-\w+/g, '').trim();
                            selectElement.classList.add('qc-bg-' + newStatus);

                            // Show success feedback
                            selectElement.style.backgroundColor = '#d4edda';
                            setTimeout(() => {
                                selectElement.style.backgroundColor = '';
                            }, 1000);

                            // Update the selected attribute
                            selectElement.querySelectorAll('option').forEach(opt => {
                                opt.removeAttribute('selected');
                            });
                            selectElement.querySelector(`option[value="${newStatus}"]`).setAttribute('selected', 'selected');

                            // Show toast notification
                            showToast('QC status updated successfully!', 'success');
                        } else {
                            throw new Error(data.message || 'Update failed');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Revert to original value
                        selectElement.value = originalValue;
                        selectElement.style.backgroundColor = '#f8d7da';
                        setTimeout(() => {
                            selectElement.style.backgroundColor = originalBg;
                        }, 1000);
                        showToast('Failed to update QC status', 'error');
                    })
                    .finally(() => {
                        selectElement.disabled = false;
                    });
                });
            });

            // Toast notification function
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed top-0 end-0 m-3`;
                toast.style.zIndex = '9999';
                toast.style.minWidth = '250px';
                toast.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        <span>${message}</span>
                    </div>
                `;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.style.transition = 'opacity 0.3s';
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        });

    </script>
@endpush


<style>
    .qc-bg-pending { background-color: #fff3cd !important; }
    .qc-bg-approved { background-color: #d1e7dd !important; }
    .qc-bg-rejected { background-color: #f8d7da !important; }
</style>

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
    #supportiveModal table td {
        padding: 10px 12px;
        vertical-align: middle;
        font-size: 13px;
    }

    #supportiveModal table tr:nth-child(even) {
        background: #f8fafc;
    }

    /* Label column */
    #supportiveModal table td:first-child {
        font-weight: 600;
        color: #1e293b;
        background: #eef2ff;
        width: 40%;
    }

    /* Value column */
    #supportiveModal table td:last-child {
        color: #334155;
    }

</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#filter_inbound_invoices').select2({
        placeholder: "All Invoices",
        allowClear: true,
        width: '100%'
    });

    // Apply filters with AJAX
    $('#applyFilters').on('click', function() {
        applyFilters();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#inboundFilterForm')[0].reset();
        applyFilters();
    });

    // Auto-apply on filter change (optional)
    $('.filter-field').on('change', function() {
        applyFilters();
    });

    function applyFilters() {
        const formData = {
            qc_status: $('#filter_qc_status').val(),
            warehouse_id: $('#filter_warehouse').val(),
            vendor_id: $('#filter_vendor').val(),
            product_group_id: $('#filter_product_group').val(),
            product_id: $('#filter_product').val(),
            inbound_invoices: $('#filter_inbound_invoices').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val(),
            search: $('#filter_search').val()
        };

        // Show loading
        $('#filterLoadingOverlay').show();

        $.ajax({
            url: '{{ route("inbound.index") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                // Parse the response HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');

                // Extract table body
                const newTableBody = doc.querySelector('#inboundTableBody');
                if (newTableBody) {
                    $('#inboundTableBody').html(newTableBody.innerHTML);
                }

                // Update count
                const totalCount = $(newTableBody).find('tr').length;
                $('#totalCount').text(totalCount);

                // Hide loading
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

function exportInbound() {
    const params = new URLSearchParams({
        qc_status: $('#filter_qc_status').val(),
        warehouse_id: $('#filter_warehouse').val(),
        vendor_id: $('#filter_vendor').val(),
        product_group_id: $('#filter_product_group').val(),
        product_id: $('#filter_product').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val(),
        search: $('#filter_search').val()
    });

    const invoices = $('#filter_inbound_invoices').val();
    if (invoices && invoices.length > 0) {
        invoices.forEach(inv => params.append('inbound_invoices[]', inv));
    }

    window.location.href = '{{ route("inbound.export") }}?' + params.toString();
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

    // Use event delegation for row checkboxes because they are recreated by AJAX filters
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

