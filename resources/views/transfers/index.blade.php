@extends('layouts.app')

@section('content')

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 42px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        padding: 6px 12px;
        background-color: #ffffff;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #0f172a !important;
        font-weight: 600;
        line-height: 28px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }

    .relocate-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
    }

    .table-custom-header th {
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569;
        font-weight: 700;
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .location-pill {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
    }

    .modal-detail-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
    }
</style>

<div class="container-fluid px-4 py-4">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-info bg-opacity-10 text-info fw-bold px-2.5 py-1 rounded-pill" style="font-size: 11px;">INVENTORY MOVEMENT</span>
                <h4 class="mb-0 fw-bold text-dark">Stock Relocation & Pallet Transfers</h4>
            </div>
            <p class="text-secondary small mb-0">Select any product to view all stored locations (warehouses/pallets) and transfer stock easily between locations.</p>
        </div>
    </div>

    <!-- PRODUCT SELECTOR & CURRENT LOCATIONS CARD -->
    <div class="relocate-card mb-4 overflow-hidden">
        <div class="card-header bg-slate-50 py-3.5 px-4 border-bottom d-flex align-items-center justify-content-between" style="background-color: #f8fafc;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-box-seam-fill text-primary fs-5"></i>
                <h6 class="mb-0 fw-bold text-dark">Step 1: Select Product to View Locations</h6>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Choose Product (Search by Code or Name)</label>
                    <select id="select_product" class="form-select select2-product">
                        <option value="">-- Select Product --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" data-code="{{ $prod->item_code }}" data-pack="{{ $prod->pack_size }}">
                                [{{ $prod->item_code }}] {{ $prod->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-6">
                    <div id="product_summary_strip" class="d-none p-3 rounded-3 border bg-slate-50" style="background:#f8fafc;">
                        <div class="d-flex flex-wrap gap-4 align-items-center">
                            <div>
                                <span class="text-muted small d-block font-semibold">Total Stocked Locations</span>
                                <strong id="summary_locations_count" class="fs-6 text-primary">0 Locations</strong>
                            </div>
                            <div>
                                <span class="text-muted small d-block font-semibold">Total Available Cartons</span>
                                <strong id="summary_total_cartons" class="fs-6 text-success">0.00</strong>
                            </div>
                            <div>
                                <span class="text-muted small d-block font-semibold">Total Available Units</span>
                                <strong id="summary_total_units" class="fs-6 text-info">0</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DYNAMIC LOCATIONS LIST CONTAINER -->
            <div id="locations_wrapper" class="mt-4 d-none">
                <hr class="my-4 text-slate-200">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Current Stock Locations for Selected Product</h6>
                    <span class="small text-muted">Click <strong>Relocate Stock</strong> on any location row to move items to another pallet/warehouse.</span>
                </div>

                <div class="table-responsive border rounded-3 overflow-hidden">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-custom-header">
                            <tr>
                                <th class="ps-3">Location & Warehouse</th>
                                <th>Pallet / Position</th>
                                <th>SAP Batch</th>
                                <th>Vendor Batch</th>
                                <th class="text-end">Cartons (Qty)</th>
                                <th class="text-end">Available Units</th>
                                <th>Expiry Date</th>
                                <th>QC Status</th>
                                <th class="text-center" style="width: 150px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="locations_tbody">
                            <!-- Populated dynamically via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- EMPTY PLACEHOLDER -->
            <div id="empty_product_placeholder" class="text-center py-5">
                <i class="bi bi-search-heart fs-1 text-slate-300 d-block mb-2"></i>
                <h6 class="fw-semibold text-secondary">No Product Selected</h6>
                <p class="small text-muted mb-0">Select a product from the dropdown above to view all its stored pallet and warehouse locations.</p>
            </div>
        </div>
    </div>

    <!-- RECENT TRANSFERS HISTORY TABLE -->
    <div class="relocate-card overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary fs-5"></i>
                <h6 class="mb-0 fw-bold text-dark">Recent Relocations & Stock Transfers Audit Log</h6>
            </div>
            <form method="GET" action="{{ route('stock-transfers.index') }}" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search transfer # or product..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary btn-sm px-3 rounded-3"><i class="bi bi-search"></i></button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-custom-header">
                        <tr>
                            <th class="ps-3">Transfer #</th>
                            <th>Date & Time</th>
                            <th>Product Code & Name</th>
                            <th>Source Location (From)</th>
                            <th>Destination Location (To)</th>
                            <th class="text-end">Transferred Units</th>
                            <th>Transferred By</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $trf)
                        <tr>
                            <td class="ps-3 fw-bold text-primary">{{ $trf->transfer_no }}</td>
                            <td class="text-nowrap text-secondary small">
                                {{ $trf->created_at ? $trf->created_at->format('d.m.Y h:i A') : '-' }}
                            </td>
                            <td>
                                <div class="fw-bold text-dark">[{{ $trf->product->item_code ?? '-' }}]</div>
                                <div class="small text-muted">{{ $trf->product->name ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2.5 py-1 rounded-2">
                                    <i class="bi bi-geo-alt me-1"></i>{{ $trf->from_location_display }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1 rounded-2">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>{{ $trf->to_location_display }}
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark fs-6">
                                {{ number_format($trf->units) }} units
                            </td>
                            <td class="small fw-semibold text-secondary">
                                {{ $trf->user->name ?? 'System' }}
                            </td>
                            <td class="small text-muted" style="max-width: 180px;">
                                {{ $trf->remarks ?: '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                <p class="mb-0 font-medium">No stock transfers recorded yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transfers->hasPages())
        <div class="card-footer bg-white border-top py-3 px-4">
            {{ $transfers->links() }}
        </div>
        @endif
    </div>

</div>

<!-- ========================================== -->
<!-- STOCK RELOCATION TRANSFER MODAL POPUP -->
<!-- ========================================== -->
<div class="modal fade" id="relocateModal" tabindex="-1" aria-labelledby="relocateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header modal-detail-header py-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="relocateModalLabel">
                        <i class="bi bi-arrow-left-right me-2 text-info"></i>Relocate / Move Stock
                    </h5>
                    <span class="small text-light opacity-75" id="relocateSubHeader">Move stock between pallets or warehouses</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="relocateForm">
                @csrf
                <input type="hidden" name="product_id" id="modal_product_id">
                <input type="hidden" name="stock_in_item_id" id="modal_stock_in_item_id">

                <div class="modal-body p-4 bg-light">

                    <!-- SOURCE SUMMARY BOX -->
                    <div class="card border-0 shadow-sm rounded-3 bg-white p-3.5 mb-4 border-start border-4 border-primary">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-geo-alt-fill text-primary me-1"></i>Source Stock Location Details</h6>
                        <div class="row g-2 small">
                            <div class="col-6 col-md-4">
                                <span class="text-muted d-block font-semibold">Product Name</span>
                                <strong id="m_src_product_name" class="text-dark">-</strong>
                            </div>
                            <div class="col-6 col-md-4">
                                <span class="text-muted d-block font-semibold">Current Location</span>
                                <strong id="m_src_location_display" class="text-primary">-</strong>
                            </div>
                            <div class="col-6 col-md-4">
                                <span class="text-muted d-block font-semibold">Available Units</span>
                                <strong id="m_src_available_units" class="text-success fs-6">-</strong>
                            </div>
                        </div>
                    </div>

                    <!-- TARGET DESTINATION FORM -->
                    <div class="card border-0 shadow-sm rounded-3 bg-white p-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-box-arrow-in-right text-success me-1"></i>Destination Location & Quantity</h6>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Units to Transfer <span class="text-danger">*</span></label>
                                <input type="number" name="transfer_units" id="modal_transfer_units" class="form-control" min="1" required placeholder="Enter units count">
                                <span class="text-muted" style="font-size: 11px;">Max available: <strong id="m_max_units_label">0</strong> units</span>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Target Warehouse <span class="text-danger">*</span></label>
                                <select name="to_warehouse_id" id="modal_to_warehouse_id" class="form-select" required>
                                    <option value="">-- Select Target Warehouse --</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Target Warehouse Row</label>
                                <select name="to_warehouse_row_id" id="modal_to_warehouse_row_id" class="form-select">
                                    <option value="">-- Select Row (Optional) --</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Target Pallet Position (Pallet Start)</label>
                                <input type="number" name="to_pallet_start" class="form-control" min="1" value="1" placeholder="e.g. 1, 2, 5">
                            </div>

                            <!-- LIVE TRANSFER CALCULATOR STRIP -->
                            <div class="col-12">
                                <div class="p-3 rounded-3 border bg-slate-50" style="background:#f8fafc; border-color:#cbd5e1 !important;">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                        <div>
                                            <span class="text-muted small d-block font-semibold">Transfer Calculation Summary</span>
                                            <strong id="m_calc_summary_text" class="fs-6 text-dark">0 Units</strong>
                                        </div>
                                        <div class="text-end">
                                            <span class="text-muted small d-block font-semibold">Pallets Required</span>
                                            <span class="badge bg-primary fs-6 px-3 py-1 rounded-pill" id="m_calc_pallets_needed">0 Pallet(s)</span>
                                        </div>
                                    </div>
                                    <div id="m_calc_row_preview_strip" class="pt-2 border-top small d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-top-color:#e2e8f0 !important;">
                                        <span class="text-secondary"><i class="bi bi-geo-alt me-1 text-primary"></i>Pallet Position Range: <strong id="m_calc_range_val" class="text-dark">-</strong></span>
                                        <span class="badge bg-secondary" id="m_calc_capacity_badge">Select Target Row</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary">Transfer Remarks / Reason</label>
                                <input type="text" name="remarks" class="form-control" placeholder="e.g. Rack optimization, damaged rack transfer, inter-warehouse relocation">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white py-3 px-4 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="modal_submit_btn" class="btn btn-primary rounded-3 px-4 font-semibold">
                        <i class="bi bi-check-circle me-1"></i>Confirm Transfer & Relocate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const warehouseDataMap = @json($warehouses);
let activeProductLocations = [];
let currentActiveLocation = null;

$(document).ready(function() {
    $('.select2-product').select2({
        placeholder: "-- Select Product --",
        allowClear: true,
        width: '100%'
    });

    // Product selection change handler
    $('#select_product').on('change', function() {
        const productId = $(this).val();
        if (!productId) {
            $('#locations_wrapper').addClass('d-none');
            $('#product_summary_strip').addClass('d-none');
            $('#empty_product_placeholder').removeClass('d-none');
            return;
        }

        // Show loading
        $('#empty_product_placeholder').html(`
            <div class="spinner-border text-primary my-4" role="status"></div>
            <p class="text-secondary font-medium">Fetching stored locations for selected product...</p>
        `).removeClass('d-none');
        $('#locations_wrapper').addClass('d-none');

        // Fetch product locations
        fetch(`/stock-transfers/product-locations/${productId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire('Error', 'Failed to load product locations.', 'error');
                    return;
                }

                activeProductLocations = data.locations;

                // Update Summary Strip
                $('#summary_locations_count').text(data.total_locations + ' Locations');
                $('#summary_total_cartons').text(data.total_cartons.toLocaleString() + ' CTN');
                $('#summary_total_units').text(data.total_units.toLocaleString() + ' Units');
                $('#product_summary_strip').removeClass('d-none');

                // Populate locations table
                let tbodyHtml = '';
                data.locations.forEach(loc => {
                    let qcBadge = '<span class="badge bg-warning text-dark px-2 py-1">Pending QC</span>';
                    if (loc.quality_clearance === 'approved') {
                        qcBadge = '<span class="badge bg-success px-2 py-1">Approved</span>';
                    } else if (loc.quality_clearance === 'rejected') {
                        qcBadge = '<span class="badge bg-danger px-2 py-1">Rejected</span>';
                    }

                    tbodyHtml += `
                        <tr>
                            <td class="ps-3 fw-bold text-dark">
                                <i class="bi bi-building me-1.5 text-primary"></i>${loc.warehouse_name} 
                                <span class="small text-muted font-normal">(${loc.row_name})</span>
                            </td>
                            <td>
                                <span class="location-pill"><i class="bi bi-geo-alt me-1"></i>${loc.location_display}</span>
                            </td>
                            <td><code>${loc.sap_batch}</code></td>
                            <td><code>${loc.vendor_batch}</code></td>
                            <td class="text-end fw-bold text-slate-800">${loc.balance_quantity.toLocaleString()} CTN</td>
                            <td class="text-end fw-bold text-success fs-6">${loc.units_available.toLocaleString()} Units</td>
                            <td>${loc.expiry_date}</td>
                            <td>${qcBadge}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-primary rounded-3 px-3 fw-semibold shadow-2xs" 
                                        onclick="openRelocateModal(${loc.batch_id})">
                                    <i class="bi bi-arrow-left-right me-1"></i>Relocate
                                </button>
                            </td>
                        </tr>
                    `;
                });

                if (data.locations.length === 0) {
                    tbodyHtml = `
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-exclamation-circle fs-2 d-block mb-2 text-warning"></i>
                                <p class="mb-0 fw-semibold">No available stock found for this product in any warehouse.</p>
                            </td>
                        </tr>
                    `;
                }

                $('#locations_tbody').html(tbodyHtml);
                $('#empty_product_placeholder').addClass('d-none');
                $('#locations_wrapper').removeClass('d-none');
            })
            .catch(err => {
                console.error(err);
                $('#empty_product_placeholder').html(`
                    <p class="text-danger my-4 font-semibold"><i class="bi bi-exclamation-triangle me-1"></i>Error loading locations. Please try again.</p>
                `);
            });
    });

    // Populate rows on Target Warehouse change
    $('#modal_to_warehouse_id').on('change', function() {
        const targetWhId = parseInt($(this).val());
        const rowSelect = $('#modal_to_warehouse_row_id');
        rowSelect.html('<option value="">-- Select Row (Optional) --</option>');

        if (!targetWhId) {
            updateTransferCalculator();
            return;
        }

        const whObj = warehouseDataMap.find(w => w.id === targetWhId);
        if (whObj && whObj.rows) {
            whObj.rows.forEach(r => {
                rowSelect.append(`<option value="${r.id}">${r.row_name} (Capacity: ${r.pallet_capacity})</option>`);
            });
        }
        updateTransferCalculator();
    });

    // Bind live events for transfer calculator
    $('#modal_transfer_units, #modal_to_warehouse_id, #modal_to_warehouse_row_id, input[name="to_pallet_start"]').on('input change', function() {
        updateTransferCalculator();
    });

    // Form submit for Relocation Transfer
    $('#relocateForm').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $('#modal_submit_btn');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');

        const formData = $(this).serialize();

        $.ajax({
            url: "{{ route('stock-transfers.store') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Confirm Transfer & Relocate');
                $('#relocateModal').modal('hide');

                Swal.fire({
                    icon: 'success',
                    title: 'Transfer Complete!',
                    text: response.message,
                    timer: 2500,
                    showConfirmButton: false
                }).then(() => {
                    // Trigger refresh of product locations
                    $('#select_product').trigger('change');
                    location.reload();
                });
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Confirm Transfer & Relocate');
                let errMsg = 'Transfer failed. Please check inputs.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                Swal.fire('Transfer Error', errMsg, 'error');
            }
        });
    });
});

function updateTransferCalculator() {
    if (!currentActiveLocation) return;

    const units = parseInt($('#modal_transfer_units').val()) || 0;
    const cartonsPerPallet = parseInt(currentActiveLocation.cartons_per_pallet) || 20;
    const palletsNeeded = units > 0 ? Math.ceil(units / cartonsPerPallet) : 0;

    $('#m_calc_summary_text').text(`${units.toLocaleString()} Units (${palletsNeeded} Pallet(s) @ ${cartonsPerPallet} units/pallet)`);
    $('#m_calc_pallets_needed').text(`${palletsNeeded} Pallet(s) Required`);

    const selectedWhId = parseInt($('#modal_to_warehouse_id').val());
    const selectedRowId = parseInt($('#modal_to_warehouse_row_id').val());
    const palletStart = parseInt($('input[name="to_pallet_start"]').val()) || 1;

    if (!selectedWhId || !selectedRowId) {
        $('#m_calc_range_val').text('-');
        $('#m_calc_capacity_badge').attr('class', 'badge bg-secondary').text('Select Target Row');
        $('#modal_submit_btn').prop('disabled', false);
        return;
    }

    const whObj = warehouseDataMap.find(w => w.id === selectedWhId);
    const rowObj = whObj && whObj.rows ? whObj.rows.find(r => r.id === selectedRowId) : null;

    if (!rowObj) {
        $('#m_calc_range_val').text('-');
        $('#m_calc_capacity_badge').attr('class', 'badge bg-secondary').text('Invalid Row');
        $('#modal_submit_btn').prop('disabled', false);
        return;
    }

    const rowCap = parseInt(rowObj.pallet_capacity) || 10;
    
    // Helper to get row prefix and padding
    function parseRowPrefix(rowName) {
        let prefix = 'P';
        let padLen = 3;
        const match = rowName.match(/^(.+?)(\d+)\s+to\s+/i);
        if (match) {
            prefix = match[1];
            padLen = match[2].length;
        }
        return { prefix, padLen };
    }

    const firstRowParse = parseRowPrefix(rowObj.row_name);
    const maxPadded = firstRowParse.prefix + String(rowCap).padStart(firstRowParse.padLen, '0');

    if (palletStart > rowCap) {
        $('#m_calc_range_val').text('-');
        $('#m_calc_capacity_badge').attr('class', 'badge bg-danger').text(`⚠️ Invalid Start! Row Max is ${maxPadded}`);
        $('#modal_submit_btn').prop('disabled', true);
        return;
    }

    const fitInFirstRow = Math.min(palletsNeeded, Math.max(0, rowCap - palletStart + 1));
    const overflowNeeded = palletsNeeded - fitInFirstRow;

    const startPadded = firstRowParse.prefix + String(palletStart).padStart(firstRowParse.padLen, '0');
    const endFirstPadded = firstRowParse.prefix + String(palletStart + fitInFirstRow - 1).padStart(firstRowParse.padLen, '0');

    if (overflowNeeded === 0) {
        // Fits completely in the selected row!
        const rangeText = (fitInFirstRow === 1) ? startPadded : `${startPadded} to ${endFirstPadded}`;
        $('#m_calc_range_val').text(rangeText);
        $('#m_calc_capacity_badge').attr('class', 'badge bg-success').text(`✓ Fits in Row (${rangeText})`);
        $('#modal_submit_btn').prop('disabled', false);
    } else {
        // Overflows into next row(s)
        let nextRowObj = null;
        if (whObj && whObj.rows) {
            const rowIdx = whObj.rows.findIndex(r => r.id === selectedRowId);
            if (rowIdx !== -1 && rowIdx + 1 < whObj.rows.length) {
                nextRowObj = whObj.rows[rowIdx + 1];
            }
        }

        if (nextRowObj) {
            const nextParse = parseRowPrefix(nextRowObj.row_name);
            const nextEndPadded = nextParse.prefix + String(overflowNeeded).padStart(nextParse.padLen, '0');
            const nextStartPadded = nextParse.prefix + String(1).padStart(nextParse.padLen, '0');
            
            const rangeText = `${startPadded}-${endFirstPadded} (${fitInFirstRow}p) + ${nextStartPadded}-${nextEndPadded} (${overflowNeeded}p in Next Row)`;
            $('#m_calc_range_val').text(rangeText);
            $('#m_calc_capacity_badge').attr('class', 'badge bg-success').text(`✓ Splits into Next Row (${nextRowObj.row_name})`);
            $('#modal_submit_btn').prop('disabled', false);
        } else {
            const rangeText = `${startPadded} to ${endFirstPadded} (${fitInFirstRow} pallets fit)`;
            $('#m_calc_range_val').text(rangeText);
            $('#m_calc_capacity_badge').attr('class', 'badge bg-warning text-dark').text(`⚠️ Row ${rowObj.row_name} stops at ${maxPadded}! (${overflowNeeded} p extra)`);
            $('#modal_submit_btn').prop('disabled', false);
        }
    }
}

function openRelocateModal(batchId) {
    const loc = activeProductLocations.find(l => l.batch_id === batchId);
    if (!loc) return;

    currentActiveLocation = loc;

    const prodId = $('#select_product').val();
    const prodCode = $('#select_product option:selected').data('code');
    const prodText = $('#select_product option:selected').text();

    $('#modal_product_id').val(prodId);
    $('#modal_stock_in_item_id').val(loc.batch_id);

    $('#m_src_product_name').text(prodText);
    $('#m_src_location_display').text(loc.location_display + ` (${loc.warehouse_name})`);
    $('#m_src_available_units').text(loc.units_available.toLocaleString() + ' Units (' + loc.balance_quantity.toLocaleString() + ' CTN)');
    
    $('#modal_transfer_units').val(loc.units_available).attr('max', loc.units_available);
    $('#m_max_units_label').text(loc.units_available.toLocaleString());

    // Preselect current warehouse and row as default target
    $('#modal_to_warehouse_id').val(loc.warehouse_id).trigger('change');
    if (loc.warehouse_row_id) {
        $('#modal_to_warehouse_row_id').val(loc.warehouse_row_id);
    }
    if (loc.pallet_start) {
        $('input[name="to_pallet_start"]').val(loc.pallet_start);
    } else {
        $('input[name="to_pallet_start"]').val(1);
    }

    updateTransferCalculator();

    const bsModal = new bootstrap.Modal(document.getElementById('relocateModal'));
    bsModal.show();
}
</script>
@endpush

@endsection
