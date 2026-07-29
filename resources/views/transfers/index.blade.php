@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── Base ── */
body, .trf-page * { font-family: 'Inter', sans-serif; }

.trf-page { background: #f0f4f8; min-height: 100vh; }

/* ── Wizard Header ── */
.wizard-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #0369a1 100%);
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.wizard-header::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Ccircle cx='30' cy='30' r='30'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
    pointer-events: none;
}

/* ── Step Indicator ── */
.step-indicator {
    display: flex;
    align-items: center;
    gap: 0;
    margin-bottom: 24px;
}
.step-dot {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; font-weight: 600; color: #94a3b8;
    cursor: pointer;
}
.step-dot .dot {
    width: 34px; height: 34px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px;
    background: #e2e8f0; color: #94a3b8;
    border: 2px solid #e2e8f0;
    transition: all .3s;
    flex-shrink: 0;
}
.step-dot.active .dot { background: #0369a1; color: #fff; border-color: #0369a1; box-shadow: 0 0 0 4px rgba(3,105,161,.15); }
.step-dot.done .dot   { background: #059669; color: #fff; border-color: #059669; }
.step-dot.active      { color: #0f172a; }
.step-dot.done        { color: #059669; }
.step-line { flex: 1; height: 2px; background: #e2e8f0; margin: 0 6px; }
.step-line.done { background: #059669; }

/* ── Cards ── */
.trf-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 16px -4px rgba(15,23,42,.06);
    overflow: hidden;
    margin-bottom: 20px;
}
.trf-card-head {
    background: #f8fafc;
    padding: 16px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: space-between;
}
.trf-card-body { padding: 24px; }

/* ── Product Select ── */
.select2-container--default .select2-selection--single {
    height: 44px !important; border: 1.5px solid #cbd5e1 !important;
    border-radius: 10px !important; padding: 7px 12px;
    background: #fff; transition: border-color .2s;
}
.select2-container--default .select2-selection--single:hover { border-color: #93c5fd !important; }
.select2-container--default .select2-selection--single .select2-selection__rendered { color: #0f172a !important; font-weight: 600; line-height: 28px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 42px !important; }

/* ── Location Row Table ── */
.loc-table th {
    font-size: 11px; text-transform: uppercase; letter-spacing: .06em;
    color: #64748b; font-weight: 700; background: #f8fafc;
    border-bottom: 2px solid #e2e8f0; padding: 11px 14px; white-space: nowrap;
}
.loc-table td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.loc-table tr:hover td { background: #f8fafc; }
.loc-table tr.row-selected td { background: #eff6ff !important; border-left: 3px solid #3b82f6; }

/* ── Pallet Badge ── */
.pallet-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #eff6ff; color: #1d4ed8;
    border: 1px solid #bfdbfe;
    border-radius: 6px; padding: 3px 9px;
    font-size: 12px; font-weight: 700;
}
.pallet-badge.green { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
.pallet-badge.orange { background: #fff7ed; color: #9a3412; border-color: #fed7aa; }

/* ── Select all checkbox ── */
.form-check-input { cursor: pointer; width: 18px; height: 18px; margin-top: 0; }
.form-check-input:checked { background-color: #0369a1; border-color: #0369a1; }

/* ── Summary strip ── */
.summary-strip {
    display: flex; flex-wrap: wrap; gap: 20px;
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    border-radius: 12px; padding: 16px 24px;
    color: #fff;
}
.summary-strip .sk { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }
.summary-strip .sv { font-size: 20px; font-weight: 800; color: #fff; }

/* ── Space suggestion card ── */
.space-card {
    border: 2px solid #e2e8f0; border-radius: 14px;
    padding: 16px; cursor: pointer; transition: all .2s;
    position: relative; overflow: hidden;
}
.space-card:hover { border-color: #3b82f6; box-shadow: 0 4px 18px -4px rgba(59,130,246,.2); transform: translateY(-1px); }
.space-card.selected { border-color: #0369a1; background: #eff6ff; box-shadow: 0 4px 18px -4px rgba(3,105,161,.25); }
.space-card .fits-badge {
    position: absolute; top: 10px; right: 10px;
    font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px;
}
.space-card .wh-name { font-size: 13px; font-weight: 800; color: #0f172a; }
.space-card .row-info { font-size: 12px; color: #475569; margin-top: 2px; }
.space-card .pallet-info { font-size: 15px; font-weight: 700; color: #0369a1; margin-top: 8px; }
.space-card .free-count { font-size: 12px; color: #64748b; }

/* ── Wizard nav buttons ── */
.wizard-btn {
    padding: 10px 28px; border-radius: 10px;
    font-weight: 700; font-size: 14px; border: none;
    cursor: pointer; transition: all .2s;
    display: inline-flex; align-items: center; gap: 8px;
}
.wizard-btn-primary { background: #0369a1; color: #fff; box-shadow: 0 4px 14px -3px rgba(3,105,161,.4); }
.wizard-btn-primary:hover { background: #0284c7; transform: translateY(-1px); }
.wizard-btn-success { background: #059669; color: #fff; box-shadow: 0 4px 14px -3px rgba(5,150,105,.35); }
.wizard-btn-success:hover { background: #047857; transform: translateY(-1px); }
.wizard-btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.wizard-btn-secondary:hover { background: #e2e8f0; }
.wizard-btn:disabled { opacity: .55; cursor: not-allowed; transform: none !important; }

/* ── History table ── */
.hist-th { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 700; background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 11px 14px; }
.from-badge { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 6px; padding: 2px 8px; font-size: 11.5px; font-weight: 600; }
.to-badge   { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 6px; padding: 2px 8px; font-size: 11.5px; font-weight: 600; }

/* ── Pallet breakdown rows inside row ── */
.pallet-sub-row {
    display: flex; align-items: center; gap: 6px;
    font-size: 11.5px; padding: 3px 0;
    color: #475569;
}

/* ── loading spinner ── */
.spinner-overlay {
    position: fixed; inset: 0; background: rgba(15,23,42,.5);
    z-index: 9999; display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(2px);
}
.spinner-box {
    background: #fff; border-radius: 16px; padding: 36px 48px;
    text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.3);
}
</style>

<div class="trf-page px-4 py-4">

{{-- ── PAGE HEADER ── --}}
<div class="wizard-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-white bg-opacity-15 text-white fw-bold px-3 py-1 rounded-pill" style="font-size:11px;background:rgba(255,255,255,.15)!important;">INVENTORY MOVEMENT</span>
            </div>
            <h3 class="fw-bold mb-1" style="font-size:24px;">Stock Transfer Wizard</h3>
            <p class="mb-0 opacity-75" style="font-size:13.5px;">Select product → view pallet locations → choose & move to available space</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('stock-transfers.log') }}" class="wizard-btn wizard-btn-secondary">
                <i class="bi bi-clock-history"></i> Transfer Log
            </a>
        </div>
    </div>
</div>

{{-- ── STEP INDICATOR ── --}}
<div class="step-indicator">
    <div class="step-dot active" id="ind-step-1">
        <div class="dot"><i class="bi bi-box-seam-fill" style="font-size:14px;"></i></div>
        <span class="d-none d-md-inline">Select Product & Batches</span>
    </div>
    <div class="step-line" id="line-1-2"></div>
    <div class="step-dot" id="ind-step-2">
        <div class="dot"><i class="bi bi-arrow-left-right" style="font-size:13px;"></i></div>
        <span class="d-none d-md-inline">Choose Destination</span>
    </div>
    <div class="step-line" id="line-2-3"></div>
    <div class="step-dot" id="ind-step-3">
        <div class="dot"><i class="bi bi-check-lg" style="font-size:15px;"></i></div>
        <span class="d-none d-md-inline">Confirm Transfer</span>
    </div>
</div>

{{-- ══════════════════════════════════════════ --}}
{{-- STEP 1: SELECT PRODUCT & BATCHES         --}}
{{-- ══════════════════════════════════════════ --}}
<div id="step-1-panel">

    {{-- Product Selector --}}
    <div class="trf-card">
        <div class="trf-card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-box-seam-fill text-primary fs-5"></i>
                <h6 class="mb-0 fw-bold text-dark">Step 1 — Select Product to Transfer</h6>
            </div>
        </div>
        <div class="trf-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-7">
                    <label class="form-label small fw-semibold text-secondary mb-1">Search by Item Code or Product Name</label>
                    <select id="select_product" class="form-select select2-product">
                        <option value="">-- Select Product --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" data-code="{{ $prod->item_code }}" data-pack="{{ $prod->pack_size }}">
                                [{{ $prod->item_code }}] {{ $prod->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <div id="product_summary_strip" class="d-none p-3 rounded-3 border bg-light">
                        <div class="d-flex flex-wrap gap-4">
                            <div>
                                <div class="sk small text-muted fw-semibold">Locations</div>
                                <div id="s_locs" class="fw-bold fs-6 text-primary">—</div>
                            </div>
                            <div>
                                <div class="sk small text-muted fw-semibold">Total Cartons</div>
                                <div id="s_qty" class="fw-bold fs-6 text-dark">—</div>
                            </div>
                            <div>
                                <div class="sk small text-muted fw-semibold">Total Units</div>
                                <div id="s_units" class="fw-bold fs-6 text-success">—</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pallet Locations Table --}}
    <div id="locations_wrapper" class="trf-card d-none">
        <div class="trf-card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt-fill text-danger fs-5"></i>
                <h6 class="mb-0 fw-bold text-dark">Current Stock Locations <span class="badge bg-primary ms-2" id="loc_count_badge" style="font-size:12px;">0</span></h6>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="d-flex align-items-center gap-2 mb-0 small fw-semibold text-secondary" style="cursor:pointer;">
                    <input type="checkbox" id="select_all_batches" class="form-check-input">
                    Select All
                </label>
            </div>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table loc-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Warehouse</th>
                            <th>Pallet Position(s)</th>
                            <th>MFG Date</th>
                            <th>Expiry Date</th>
                            <th>SAP Batch</th>
                            <th>Vendor Batch</th>
                            <th class="text-end">Balance (CTN)</th>
                            <th class="text-end">Units Available</th>
                            <th>QC Status</th>
                        </tr>
                    </thead>
                    <tbody id="locations_tbody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Selection Summary & Next --}}
    <div id="selection_summary_bar" class="d-none trf-card">
        <div class="trf-card-body py-3">
            <div class="d-flex flex-wrap gap-4 align-items-center justify-content-between">
                <div class="d-flex flex-wrap gap-4">
                    <div>
                        <div class="small text-muted fw-semibold">Selected Batches</div>
                        <div class="fw-bold fs-5 text-primary" id="sel_batch_count">0</div>
                    </div>
                    <div>
                        <div class="small text-muted fw-semibold">Total Units to Move</div>
                        <div class="fw-bold fs-5 text-dark" id="sel_units_total">0</div>
                    </div>
                    <div>
                        <div class="small text-muted fw-semibold">Total Cartons</div>
                        <div class="fw-bold fs-5 text-secondary" id="sel_qty_total">0</div>
                    </div>
                    <div>
                        <div class="small text-muted fw-semibold">Pallets Required</div>
                        <div class="fw-bold fs-5 text-warning" id="sel_pallets_needed">—</div>
                    </div>
                </div>
                <button id="btn_next_step" class="wizard-btn wizard-btn-primary" disabled>
                    Next: Choose Destination <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <div id="empty_product_placeholder" class="text-center py-5 text-muted">
        <i class="bi bi-search-heart fs-1 d-block mb-2 opacity-40"></i>
        <h6 class="fw-semibold">No Product Selected</h6>
        <p class="small mb-0">Select a product above to view all pallet locations and batch details.</p>
    </div>

</div>
{{-- /step-1 --}}

{{-- ══════════════════════════════════════════ --}}
{{-- STEP 2: CHOOSE DESTINATION SPACE          --}}
{{-- ══════════════════════════════════════════ --}}
<div id="step-2-panel" class="d-none">

    {{-- Summary of selection --}}
    <div class="trf-card mb-4">
        <div class="trf-card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-cart-check-fill text-success fs-5"></i>
                <h6 class="mb-0 fw-bold text-dark">Selected Batches to Transfer</h6>
            </div>
            <button class="wizard-btn wizard-btn-secondary" onclick="goBackStep1()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
        </div>
        <div class="trf-card-body p-0">
            <div class="table-responsive">
                <table class="table loc-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Warehouse</th>
                            <th>Current Location</th>
                            <th>MFG</th>
                            <th>Expiry</th>
                            <th class="text-end">Units</th>
                            <th class="text-end">Pallets</th>
                        </tr>
                    </thead>
                    <tbody id="selected_batches_tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Available destination spaces --}}
    <div class="trf-card">
        <div class="trf-card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-in-right text-primary fs-5"></i>
                <h6 class="mb-0 fw-bold text-dark">Step 2 — Choose Destination Location</h6>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Need <strong id="pallets_needed_label" class="text-primary">—</strong> pallets. <span class="text-success fw-semibold">Green = fits all.</span></span>
            </div>
        </div>
        <div class="trf-card-body">
            <div id="spaces_loading" class="text-center py-4 text-muted">
                <div class="spinner-border text-primary spinner-border-sm me-2"></div> Loading available spaces...
            </div>
            <div id="spaces_grid" class="row g-3 d-none"></div>
            <div id="spaces_empty" class="text-center py-4 text-muted d-none">
                <i class="bi bi-exclamation-circle fs-2 d-block mb-2 text-warning opacity-75"></i>
                No available pallet spaces found. Please free up some pallets first.
            </div>
        </div>
    </div>

    {{-- Manual override (optional) --}}
    <div class="trf-card mt-3" id="manual_override_card">
        <div class="trf-card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-secondary fs-5"></i>
                <h6 class="mb-0 fw-semibold text-secondary">Or Override Manually</h6>
            </div>
            <button class="btn btn-sm btn-outline-secondary rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#manualOverrideBody">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse" id="manualOverrideBody">
            <div class="trf-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Target Warehouse</label>
                        <select id="manual_wh" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Target Row</label>
                        <select id="manual_row" class="form-select">
                            <option value="">-- Select --</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Start Pallet Position</label>
                        <input type="number" id="manual_pallet_start" class="form-control" min="1" value="1">
                    </div>
                    <div class="col-12">
                        <button class="wizard-btn wizard-btn-secondary" onclick="applyManualOverride()">
                            <i class="bi bi-check2-circle"></i> Apply Manual Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Remarks + Confirm --}}
    <div class="trf-card mt-3">
        <div class="trf-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-8">
                    <label class="form-label small fw-semibold text-secondary">Transfer Remarks (Optional)</label>
                    <input type="text" id="transfer_remarks" class="form-control" placeholder="e.g. Pallet optimization, damaged pallet relocation...">
                </div>
                <div class="col-12 col-md-4">
                    <div id="destination_confirm_box" class="p-3 rounded-3 border d-none mb-3" style="background:#f0fdf4;border-color:#bbf7d0!important;">
                        <div class="small fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>Destination Selected</div>
                        <div class="fw-bold text-dark" id="dest_label_display">—</div>
                        <div class="small text-muted" id="dest_pallet_display">—</div>
                    </div>
                    <button id="btn_confirm_transfer" class="wizard-btn wizard-btn-success w-100" disabled onclick="confirmTransfer()">
                        <i class="bi bi-check-circle-fill"></i> Confirm & Transfer
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
{{-- /step-2 --}}

{{-- History table moved to /stock-transfers/log --}}

</div>{{-- /trf-page --}}

{{-- Loading Overlay --}}
<div class="spinner-overlay d-none" id="loading_overlay">
    <div class="spinner-box">
        <div class="spinner-border text-primary mb-3" style="width:44px;height:44px;"></div>
        <div class="fw-bold text-dark" id="loading_text">Processing transfer...</div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const warehouseDataMap = @json($warehouses);
let activeProductLocations = [];
let activeProduct = null;
let selectedBatches = {}; // { batch_id: { loc, units } }
let destinationChoice = null; // { warehouse_id, row_id, pallet_start, label }

// ─── STEP MANAGEMENT ────────────────────────────────────────────────────
function goStep(n) {
    document.getElementById('step-1-panel').classList.add('d-none');
    document.getElementById('step-2-panel').classList.add('d-none');
    // history on separate page

    document.querySelectorAll('.step-dot').forEach((el, i) => {
        el.classList.remove('active', 'done');
        if (i + 1 < n) el.classList.add('done');
        if (i + 1 === n) el.classList.add('active');
    });
    document.getElementById('line-1-2').classList.toggle('done', n > 1);
    document.getElementById('line-2-3').classList.toggle('done', n > 2);

    if (n === 1) {
        document.getElementById('step-1-panel').classList.remove('d-none');
        // history on /stock-transfers/log
    } else if (n === 2) {
        document.getElementById('step-2-panel').classList.remove('d-none');
        populateSelectedBatchesSummary();
        loadAvailableSpaces();
    }
}

function goBackStep1() { goStep(1); }

// ─── PRODUCT SELECTION ───────────────────────────────────────────────────
$(document).ready(function() {
    $('.select2-product').select2({ placeholder: '-- Select Product --', allowClear: true, width: '100%' });

    $('#select_product').on('change', function() {
        const productId = $(this).val();
        selectedBatches = {};
        destinationChoice = null;

        if (!productId) {
            $('#locations_wrapper').addClass('d-none');
            $('#product_summary_strip').addClass('d-none');
            $('#selection_summary_bar').addClass('d-none');
            $('#empty_product_placeholder').removeClass('d-none');
            return;
        }

        $('#empty_product_placeholder').html(`
            <div class="spinner-border text-primary my-4" role="status"></div>
            <p class="text-secondary fw-semibold">Loading product locations...</p>
        `).removeClass('d-none');
        $('#locations_wrapper').addClass('d-none');

        fetch('/stock-transfers/product-locations/' + productId)
            .then(r => r.json())
            .then(data => {
                if (!data.success) { Swal.fire('Error', 'Failed to load locations.', 'error'); return; }
                activeProductLocations = data.locations;
                activeProduct = data.product;

                $('#s_locs').text(data.total_locations + ' Locations');
                $('#s_qty').text(parseFloat(data.total_qty).toLocaleString() + ' CTN');
                $('#s_units').text(parseInt(data.total_units).toLocaleString() + ' Units');
                $('#product_summary_strip').removeClass('d-none');
                $('#loc_count_badge').text(data.total_locations);

                renderLocationsTable(data.locations);
                $('#empty_product_placeholder').addClass('d-none');
                $('#locations_wrapper').removeClass('d-none');
                $('#selection_summary_bar').removeClass('d-none');
                updateSelectionBar();
            })
            .catch(err => {
                console.error(err);
                $('#empty_product_placeholder').html('<p class="text-danger my-4"><i class="bi bi-exclamation-triangle me-1"></i>Error loading locations.</p>');
            });
    });

    // Select-all checkbox
    $('#select_all_batches').on('change', function() {
        const checked = this.checked;
        document.querySelectorAll('.batch-check').forEach(cb => {
            cb.checked = checked;
            handleBatchCheck(cb);
        });
        updateSelectionBar();
    });

    // Warehouse change for manual override
    $('#manual_wh').on('change', function() {
        const whId = parseInt(this.value);
        const rowSel = document.getElementById('manual_row');
        rowSel.innerHTML = '<option value="">-- Select Row --</option>';
        if (!whId) return;
        const wh = warehouseDataMap.find(w => w.id === whId);
        if (wh && wh.rows) {
            wh.rows.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.row_name + ' (Cap: ' + r.pallet_capacity + ')';
                rowSel.appendChild(opt);
            });
        }
    });
});

// ─── RENDER LOCATIONS TABLE ──────────────────────────────────────────────
function renderLocationsTable(locations) {
    let html = '';
    locations.forEach((loc, idx) => {
        const qcHtml = getQcBadge(loc.quality_clearance);
        const palletHtml = loc.pallet_breakdown.map(pb =>
            `<div class="pallet-sub-row">
                <span class="pallet-badge"><i class="bi bi-grid-3x2-gap-fill me-1"></i>${pb.pallet_code}</span>
                <span class="text-muted">${pb.qty} CTN</span>
                <span class="text-success fw-semibold">${pb.units} units</span>
            </div>`
        ).join('');

        html += `
        <tr class="batch-row" id="row-${loc.batch_id}">
            <td>
                <input type="checkbox" class="form-check-input batch-check" 
                       data-batch-id="${loc.batch_id}" 
                       onchange="handleBatchCheck(this); updateSelectionBar();">
            </td>
            <td>
                <div class="fw-bold text-dark" style="font-size:13px;">${loc.warehouse_name}</div>
                <div class="small text-muted">${loc.row_name}</div>
            </td>
            <td>
                <div style="font-size:12.5px;">${palletHtml}</div>
                <div class="small text-muted mt-1">Range: <strong class="text-primary">${loc.pallet_range}</strong></div>
            </td>
            <td class="small fw-semibold text-secondary">${loc.mfg_date}</td>
            <td>
                <span style="font-size:12.5px;" class="${isExpired(loc.expiry_date) ? 'text-danger fw-bold' : 'text-secondary fw-semibold'}">${loc.expiry_date}</span>
            </td>
            <td><code style="font-size:11.5px;">${loc.sap_batch}</code></td>
            <td><code style="font-size:11.5px;">${loc.vendor_batch}</code></td>
            <td class="text-end fw-bold text-dark">${parseFloat(loc.balance_quantity).toLocaleString()}</td>
            <td class="text-end fw-bold text-success">${parseInt(loc.units_available).toLocaleString()}</td>
            <td>${qcHtml}</td>
        </tr>`;
    });

    if (!html) {
        html = `<tr><td colspan="10" class="text-center py-5 text-muted">
            <i class="bi bi-exclamation-circle fs-2 d-block mb-2 text-warning"></i>
            No stock found for this product.
        </td></tr>`;
    }
    document.getElementById('locations_tbody').innerHTML = html;
}

function getQcBadge(status) {
    if (status === 'approved') return '<span class="badge bg-success py-1 px-2">Approved</span>';
    if (status === 'rejected') return '<span class="badge bg-danger py-1 px-2">Rejected</span>';
    return '<span class="badge bg-warning text-dark py-1 px-2">Pending QC</span>';
}

function isExpired(dateStr) {
    if (!dateStr || dateStr === '-') return false;
    const parts = dateStr.split(' ');
    if (parts.length < 3) return false;
    try {
        const d = new Date(dateStr);
        return d < new Date();
    } catch(e) { return false; }
}

// ─── HANDLE BATCH CHECK ──────────────────────────────────────────────────
function handleBatchCheck(cb) {
    const batchId = parseInt(cb.dataset.batchId);
    const loc = activeProductLocations.find(l => l.batch_id === batchId);
    if (!loc) return;

    const row = document.getElementById('row-' + batchId);
    if (cb.checked) {
        selectedBatches[batchId] = { loc: loc, units: loc.units_available };
        if (row) row.classList.add('row-selected');
    } else {
        delete selectedBatches[batchId];
        if (row) row.classList.remove('row-selected');
    }
}

// ─── SELECTION BAR UPDATE ────────────────────────────────────────────────
function updateSelectionBar() {
    const batchIds = Object.keys(selectedBatches);
    const count = batchIds.length;
    let totalUnits = 0, totalQty = 0;

    batchIds.forEach(id => {
        const b = selectedBatches[id];
        totalUnits += b.units;
        totalQty += b.loc.balance_quantity;
    });

    const cpp = activeProduct ? activeProduct.cartons_per_pallet : 1;
    const palletsNeeded = totalUnits > 0 ? Math.ceil(totalUnits / cpp) : 0;

    document.getElementById('sel_batch_count').textContent = count;
    document.getElementById('sel_units_total').textContent = totalUnits.toLocaleString() + ' units';
    document.getElementById('sel_qty_total').textContent = parseFloat(totalQty).toLocaleString() + ' CTN';
    document.getElementById('sel_pallets_needed').textContent = palletsNeeded + ' pallet(s)';

    const nextBtn = document.getElementById('btn_next_step');
    nextBtn.disabled = count === 0;
}

// ─── NEXT STEP ───────────────────────────────────────────────────────────
document.getElementById('btn_next_step').addEventListener('click', function() {
    if (Object.keys(selectedBatches).length === 0) return;
    goStep(2);
});

// ─── STEP 2: POPULATE SELECTED BATCHES SUMMARY ───────────────────────────
function populateSelectedBatchesSummary() {
    const cpp = activeProduct ? activeProduct.cartons_per_pallet : 1;
    let html = '';
    let i = 1;
    Object.values(selectedBatches).forEach(b => {
        const loc = b.loc;
        const pallets = Math.ceil(b.units / cpp);
        html += `
        <tr>
            <td class="ps-3 fw-bold text-muted">${i++}</td>
            <td>
                <div class="fw-bold text-dark" style="font-size:13px;">${loc.warehouse_name}</div>
                <div class="small text-muted">${loc.row_name}</div>
            </td>
            <td><span class="pallet-badge"><i class="bi bi-geo-alt me-1"></i>${loc.pallet_range}</span></td>
            <td class="small text-secondary">${loc.mfg_date}</td>
            <td class="small ${isExpired(loc.expiry_date) ? 'text-danger fw-bold' : 'text-secondary'}">${loc.expiry_date}</td>
            <td class="text-end fw-bold text-success">${b.units.toLocaleString()}</td>
            <td class="text-end fw-bold text-primary">${pallets}</td>
        </tr>`;
    });
    document.getElementById('selected_batches_tbody').innerHTML = html || '<tr><td colspan="7" class="text-center text-muted py-3">No batches selected.</td></tr>';
}

// ─── LOAD AVAILABLE SPACES ───────────────────────────────────────────────
function loadAvailableSpaces() {
    const totalUnits = Object.values(selectedBatches).reduce((s, b) => s + b.units, 0);
    const excludeIds = Object.keys(selectedBatches).join(',');
    const productId = activeProduct ? activeProduct.id : '';
    const cpp = activeProduct ? activeProduct.cartons_per_pallet : 1;
    const palletsNeeded = Math.ceil(totalUnits / cpp);

    document.getElementById('pallets_needed_label').textContent = palletsNeeded + ' pallet(s)';

    document.getElementById('spaces_loading').classList.remove('d-none');
    document.getElementById('spaces_grid').classList.add('d-none');
    document.getElementById('spaces_empty').classList.add('d-none');

    fetch(`/stock-transfers/available-spaces?product_id=${productId}&units=${totalUnits}&exclude_batch_ids=${excludeIds}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('spaces_loading').classList.add('d-none');
            if (!data.success || !data.suggestions || data.suggestions.length === 0) {
                document.getElementById('spaces_empty').classList.remove('d-none');
                return;
            }
            renderSpaceGrid(data.suggestions, data.pallets_needed);
        })
        .catch(err => {
            console.error(err);
            document.getElementById('spaces_loading').classList.add('d-none');
            document.getElementById('spaces_empty').classList.remove('d-none');
        });
}

function renderSpaceGrid(suggestions, palletsNeeded) {
    const grid = document.getElementById('spaces_grid');
    let html = '';

    suggestions.forEach((s, idx) => {
        const fitsClass = s.fits_all ? 'bg-success text-white' : 'bg-warning text-dark';
        const fitsText = s.fits_all ? '✓ Fits All' : `Partial (${s.can_fit}/${palletsNeeded}p)`;
        const palletLabel = s.first_free_code === s.last_free_code
            ? s.first_free_code
            : `${s.first_free_code} → ${s.last_free_code}`;

        html += `
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="space-card" id="space-card-${idx}" onclick="selectSpace(${idx}, ${s.warehouse_id}, ${s.row_id}, ${s.first_free}, '${s.warehouse_name}', '${s.row_name}', ${s.row_capacity})">
                <span class="fits-badge ${fitsClass}">${fitsText}</span>
                <div class="wh-name"><i class="bi bi-building me-1 text-primary"></i>${s.warehouse_name}</div>
                <div class="row-info">Row: <strong>${s.row_name}</strong> &nbsp;|&nbsp; Capacity: ${s.row_capacity} pallets</div>
                <div class="pallet-info"><i class="bi bi-grid-3x2-gap-fill me-1"></i>${palletLabel}</div>
                <div class="free-count mt-1">
                    <span class="badge bg-light text-secondary border me-1">${s.free_pallets} free pallets available</span>
                    <span class="badge ${s.fits_all ? 'bg-success' : 'bg-warning text-dark'}">Start: Pallet ${s.first_free}</span>
                </div>
            </div>
        </div>`;
    });

    grid.innerHTML = html;
    grid.classList.remove('d-none');
}

// ─── SELECT SPACE ────────────────────────────────────────────────────────
function selectSpace(idx, whId, rowId, palletStart, whName, rowName, rowCap) {
    document.querySelectorAll('.space-card').forEach(c => c.classList.remove('selected'));
    const card = document.getElementById('space-card-' + idx);
    if (card) card.classList.add('selected');

    destinationChoice = { warehouse_id: whId, row_id: rowId, pallet_start: palletStart };

    const palletCode = getPalletCodeJS(rowName, palletStart - 1);
    const destLabel = `${whName} — ${rowName}`;
    const palletDisplay = `Starting at pallet ${palletCode} (Pos. ${palletStart})`;

    document.getElementById('dest_label_display').textContent = destLabel;
    document.getElementById('dest_pallet_display').textContent = palletDisplay;
    document.getElementById('destination_confirm_box').classList.remove('d-none');
    document.getElementById('btn_confirm_transfer').disabled = false;
}

function applyManualOverride() {
    const whId = parseInt(document.getElementById('manual_wh').value);
    const rowId = parseInt(document.getElementById('manual_row').value);
    const palletStart = parseInt(document.getElementById('manual_pallet_start').value) || 1;

    if (!whId || !rowId) {
        Swal.fire('Incomplete', 'Please select warehouse and row.', 'warning');
        return;
    }

    const wh = warehouseDataMap.find(w => w.id === whId);
    const row = wh && wh.rows ? wh.rows.find(r => r.id === rowId) : null;

    if (!wh || !row) { Swal.fire('Error', 'Invalid selection.', 'error'); return; }

    destinationChoice = { warehouse_id: whId, row_id: rowId, pallet_start: palletStart };

    const palletCode = getPalletCodeJS(row.row_name, palletStart - 1);
    document.getElementById('dest_label_display').textContent = `${wh.name} — ${row.row_name}`;
    document.getElementById('dest_pallet_display').textContent = `Starting at pallet ${palletCode} (Pos. ${palletStart})`;
    document.getElementById('destination_confirm_box').classList.remove('d-none');
    document.getElementById('btn_confirm_transfer').disabled = false;

    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Manual destination applied!', showConfirmButton: false, timer: 2000 });
}

// ─── PALLET CODE HELPER (JS) ─────────────────────────────────────────────
function getPalletCodeJS(rowName, offsetFromZero) {
    const parts = rowName.split(/ to /i);
    const first = parts[0].trim();
    const m = first.match(/^(.*?)(\d+)$/);
    if (m) {
        const prefix = m[1];
        const startNum = parseInt(m[2]);
        const digits = m[2].length;
        const num = startNum + offsetFromZero;
        return prefix + String(num).padStart(digits, '0');
    }
    return rowName + '-P' + (offsetFromZero + 1);
}

// ─── CONFIRM TRANSFER ────────────────────────────────────────────────────
function confirmTransfer() {
    if (!destinationChoice) { Swal.fire('No Destination', 'Please select a destination location.', 'warning'); return; }
    if (!activeProduct) { Swal.fire('Error', 'No product selected.', 'error'); return; }

    const batchList = Object.values(selectedBatches).map(b => ({
        batch_id: b.loc.batch_id,
        units: b.units
    }));

    if (batchList.length === 0) { Swal.fire('No Batches', 'No batches selected.', 'warning'); return; }

    const remarks = document.getElementById('transfer_remarks').value;
    const wh = warehouseDataMap.find(w => w.id === destinationChoice.warehouse_id);
    const row = wh && wh.rows ? wh.rows.find(r => r.id === destinationChoice.row_id) : null;
    const palletCode = getPalletCodeJS(row ? row.row_name : '', destinationChoice.pallet_start - 1);
    const totalUnits = batchList.reduce((s, b) => s + b.units, 0);

    Swal.fire({
        title: '⚡ Confirm Transfer',
        html: `
            <div class="text-start" style="font-size:14px;">
                <div class="mb-2"><strong>Product:</strong> [${activeProduct.item_code}] ${activeProduct.name}</div>
                <div class="mb-2"><strong>Batches:</strong> ${batchList.length}</div>
                <div class="mb-2"><strong>Total Units:</strong> <span class="text-success fw-bold">${totalUnits.toLocaleString()}</span></div>
                <div class="mb-2"><strong>Destination:</strong> <span class="text-primary fw-bold">${wh ? wh.name : ''} — ${row ? row.row_name : ''}</span></div>
                <div class="mb-2"><strong>Starting Pallet:</strong> <span class="text-primary fw-bold">${palletCode}</span></div>
            </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '✓ Confirm Transfer',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#059669',
    }).then(result => {
        if (!result.isConfirmed) return;

        document.getElementById('loading_overlay').classList.remove('d-none');
        document.getElementById('loading_text').textContent = 'Processing ' + batchList.length + ' batch(es)...';

        const payload = {
            product_id: activeProduct.id,
            to_warehouse_id: destinationChoice.warehouse_id,
            to_warehouse_row_id: destinationChoice.row_id,
            to_pallet_start: destinationChoice.pallet_start,
            batches: batchList,
            remarks: remarks,
            _token: document.querySelector('meta[name="csrf-token"]').content,
        };

        fetch('/stock-transfers/store-multi', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('loading_overlay').classList.add('d-none');
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Transfer Successful! 🎉',
                    html: `<div style="font-size:15px;">${data.message}</div>`,
                    confirmButtonText: 'Back to Transfers',
                    confirmButtonColor: '#0369a1',
                }).then(() => {
                    selectedBatches = {};
                    destinationChoice = null;
                    activeProduct = null;
                    activeProductLocations = [];
                    $('#select_product').val('').trigger('change');
                    goStep(1);
                    location.reload();
                });
            } else {
                Swal.fire('Transfer Failed', data.message, 'error');
            }
        })
        .catch(err => {
            document.getElementById('loading_overlay').classList.add('d-none');
            console.error(err);
            Swal.fire('Error', 'An unexpected error occurred. Please try again.', 'error');
        });
    });
}
</script>
@endpush

@endsection
