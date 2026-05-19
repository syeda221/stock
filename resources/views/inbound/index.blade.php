@extends('layouts.app')

@section('content')

<style>
.bg-orange {
    background-color: #fd7e14 !important;
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

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Inbound Stock (Batch Wise)</h6>
        <a href="{{ route('inbound.create') }}" class="btn btn-sm btn-primary">
            + Add Inbound
        </a>
    </div>

    <div class="card-body p-3">
        <!-- Filters -->
        <form id="inboundFilterForm" class="mb-3" onsubmit="return false;">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">QC Status</label>
                    <select name="qc_status" id="filter_qc_status" class="form-select form-select-sm filter-field">
                        <option value="">All Status</option>
                        <option value="pending">🟡 Pending</option>
                        <option value="approved">🟢 Approved</option>
                        <option value="rejected">🔴 Rejected</option>
                    </select>
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
                    <label class="form-label fw-semibold small">Vendor</label>
                    <select name="vendor_id" id="filter_vendor" class="form-select form-select-sm filter-field">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Product</label>
                    <select name="product_id" id="filter_product" class="form-select form-select-sm filter-field">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
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
            </div>
            <div class="mt-2">
                <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
                    <i class="bi bi-search"></i> Apply Filters
                </button>
                <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <span class="ms-2 text-muted small">Total: <strong id="totalCount">{{ $items->count() }}</strong> batches</span>
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

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="min-width:160px">Product</th>
                        <th style="width:100px">Warehouse</th>
                        <th class="text-end" style="width:60px">Units</th>
                        <th class="text-end" style="width:70px">Balance</th>
                        <th style="width:90px">Status</th>
                        <th style="width:110px">QC</th>
                        <th style="width:120px">Vehicle/Driver</th>
                        <th style="width:100px">Days</th>
                        <th style="width:85px">Date</th>
                        <th style="width:90px" class="text-center">Actions</th>
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

                            'PO No' => $item->stockIn->po_no ?? '-',
                            'IBD No' => $item->stockIn->ibd_no ?? '-',
                            'Shipment No' => $item->stockIn->shipment_no ?? '-',
                            'STO No' => $item->stockIn->sto_no ?? '-',

                            'Shipment Type' => strtoupper($item->stockIn->shipment_type ?? 'MANUAL'),

                            'Vehicle No' => $item->stockIn->vehicle_no ?? '-',
                            'Vehicle Size' => $item->stockIn->vehicle_size ?? '-',
                            'Vehicle In Time' => $item->stockIn->vehicle_in_time
                            ? \Carbon\Carbon::parse($item->stockIn->vehicle_in_time)->format('d-m-Y H:i')
                            : '-',
                            'Vehicle Out Time' => $item->stockIn->vehicle_out_time
                            ? \Carbon\Carbon::parse($item->stockIn->vehicle_out_time)->format('d-m-Y H:i')
                            : '-',

                            'Driver Name' => $item->stockIn->driver_name ?? '-',
                            'Driver Mobile' => $item->stockIn->driver_mobile ?? '-',

                            'Delivery No' => $item->stockIn->delivery_no ?? '-',
                            'Dispatched Invoice No' => $item->stockIn->dispatched_invoice_no ?? '-',
                            'Dispatcher Sig' => $item->stockIn->dispatcher_sig ?? '-',
                            'Picker' => $item->stockIn->picker ?? '-',

                            'Header Remarks' => $item->stockIn->remarks ?? '-',
                            ];

                            $itemData = [

                            'Product' => ($item->product->item_code ?? '-') . ' - ' . ($item->product->name ?? '-'),
                            'Category' => $item->product->category->name ?? '-',
                            'UOM' => $item->product->uom->name ?? ($item->uom_snapshot ?? '-'),
                            'Packing' => $item->product->packingType->name ?? ($item->packing_snapshot ?? '-'),

                            'SAP Batch' => $item->sap_batch ?? '-',
                            'Vendor Batch' => $item->vendor_batch ?? '-',
                            'IBD#' => $item->ibd_no ?? '-',
                            'PO' => $item->po_no ?? '-',

                            'MFG Date' => $item->mfg_date ? \Carbon\Carbon::parse($item->mfg_date)->format('d-m-Y') :
                            '-',
                            'Expiry Date' => $item->expiry_date ?
                            \Carbon\Carbon::parse($item->expiry_date)->format('d-m-Y') : '-',
                            'Days in Warehouse' => $item->created_at ? $item->created_at->diffInDays(now()) : 0,

                            'Units Received' => $item->units_received ?? 0,
                            'Pack Size' => $item->pack_size_snapshot ?? 0,
                            'Total Quantity' => $item->total_quantity ?? 0,
                            'Balance Quantity' => $item->balance_quantity ?? 0,

                            'Use Pallets' => (bool) $item->use_pallets,
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
                            <td>{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>
                            
                            <td>
                                <div class="fw-semibold">{{ $item->product->item_code ?? '-' }}</div>
                                <small class="text-muted" style="display: block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $item->product->name ?? '-' }}">{{ $item->product->name ?? '-' }}</small>
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
                                <select class="form-select form-select-sm qc-status-select"
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
                                {{ $item->created_at ? $item->created_at->format('d-m-Y') : '-' }}
                            </td>
                            
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('reports.inbound.pdf', $item->stock_in_id) }}" 
                                       class="btn btn-sm btn-outline-danger" 
                                       title="Download PDF"
                                       target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-primary js-more"
                                        data-bs-toggle="modal" data-bs-target="#supportiveModal"
                                        data-title="Inbound Item Details" 
                                        data-header='@json($headerData)'
                                        data-item='@json($itemData)'
                                        title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted p-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                <p class="mb-0">No inbound stock found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
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

                {{-- Batch & Reference Numbers --}}
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

                {{-- Vehicle & Transport --}}
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
                    'Vendor': headerData['Vendor'],
                    'Arrived From': headerData['Arrived From'],
                    'Transporter': headerData['Transporter']
                });

                // Batch & Reference
                renderSection('batchInfo', {
                    'SAP Batch': itemData['SAP Batch'],
                    'Vendor Batch': itemData['Vendor Batch'],
                    'IBD#': itemData['IBD#'],
                    'PO': itemData['PO'],
                    'PO No': headerData['PO No'],
                    'IBD No': headerData['IBD No'],
                    'Shipment No': headerData['Shipment No'],
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
                    'Delivery No': headerData['Delivery No'],
                    'Dispatched Invoice No': headerData['Dispatched Invoice No']
                });

                // Stock Status & Quality
                renderSection('statusInfo', {
                    'Sound Stock': itemData['Sound Stock'],
                    'Block Stock': itemData['Block Stock'],
                    'Hold Stock': itemData['Hold Stock'],
                    'QC Status': itemData['QC Status'],
                    'Damage Stock': itemData['Damage Stock'],
                    'Use Pallets': itemData['Use Pallets'],
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
                            // Show success feedback
                            selectElement.style.backgroundColor = '#d4edda';
                            setTimeout(() => {
                                selectElement.style.backgroundColor = originalBg;
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


<!-- iam adding some colors in to the modal for beautifiction -->
<style>
    /* ===== Colorful Modal ===== */
    #supportiveModal .modal-content {
        border-radius: 16px;
        border: 0;
        background: linear-gradient(180deg, #f8f9ff, #ffffff);
    }

    /* Header */
    #supportiveModal .modal-header {
        background: linear-gradient(135deg, #1C0D82, #4f46e5);
        color: #fff;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
    }

    #supportiveModal .modal-title {
        font-weight: 600;
        letter-spacing: 0.4px;
    }

    #supportiveModal .btn-close {
        filter: invert(1);
    }

    /* Body */
    #supportiveModal .modal-body {
        background: #f4f6fb;
    }

    /* Cards */
    #supportiveModal .card {
        border-radius: 14px;
        overflow: hidden;
    }

    /* Inbound Header Card */
    #supportiveModal .card-header {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        font-weight: 600;
    }

    /* Item Details Card */
    #supportiveModal .col-md-6:last-child .card-header {
        background: linear-gradient(135deg, #0ea5e9, #2563eb);
    }

    /* Tables */
    #supportiveModal table {
        background: #fff;
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
<script>
$(document).ready(function() {
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
            product_id: $('#filter_product').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val()
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
            },
            error: function(xhr, status, error) {
                console.error('Filter error:', error);
                alert('An error occurred while filtering. Please try again.');
                $('#filterLoadingOverlay').hide();
            }
        });
    }
});
</script>
@endpush

