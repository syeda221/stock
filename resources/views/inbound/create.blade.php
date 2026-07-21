@extends('layouts.app')

@section('content')

<style>
    .product-autocomplete-wrapper .autocomplete-results {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1060;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .product-autocomplete-wrapper .dropdown-item {
        width: 100%;
        text-align: left;
        background: none;
        border: none;
        padding: 0.375rem 0.75rem;
        cursor: pointer;
    }
    .product-autocomplete-wrapper .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #1e2125;
    }
</style>

<form method="POST"
      action="{{ route('inbound.store') }}"
      id="inboundForm"
      autocomplete="off">
@csrf

{{-- ================= HEADER ================= --}}
<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0 fw-bold"><i class="bi bi-box-arrow-in-down me-1"></i> Inbound Stock Entry</h6>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white text-primary border font-monospace fs-6 px-2 py-1 shadow-sm">
                <i class="bi bi-receipt me-1"></i> Invoice No: {{ $nextDispatchedInvoiceNo ?? 'SPC-IBD-000' }}
            </span>
            <input type="hidden" name="dispatched_invoice_no" value="{{ $nextDispatchedInvoiceNo ?? '' }}">
        </div>
    </div>
    <div class="card-body py-2">
        <div class="row g-2">
            <!-- Row 1 -->
            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Vendor</label>
                <select name="vendor_id" class="form-select form-select-sm">
                    <option value="">Select Vendor</option>
                    @foreach ($vendors as $v)
                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Arrived From</label>
                <select name="arrived_from_id" class="form-select form-select-sm">
                    <option value="">Select Arrived From</option>
                    @foreach ($arrivedFroms as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Transporter</label>
                <select name="transporter_id" class="form-select form-select-sm">
                    <option value="">Select Transporter</option>
                    @foreach ($transporters as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Shipment Type</label>
                <select name="shipment_type" class="form-select form-select-sm">
                    <option value="manual">Manual</option>
                    <option value="auto">Auto</option>
                </select>
            </div>

            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Gate Pass</label>
                <input name="gatepass_no" class="form-control form-control-sm" placeholder="Gate Pass No">
            </div>

            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Picker</label>
                <input name="picker" class="form-control form-control-sm" placeholder="Picker Name">
            </div>

            <!-- Row 2 -->
            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Vehicle No</label>
                <input name="vehicle_no" class="form-control form-control-sm" placeholder="Vehicle No">
            </div>

            <div class="col-md-1 mb-1">
                <label class="form-label fw-semibold small mb-1">Size</label>
                <input name="vehicle_size" class="form-control form-control-sm" placeholder="Size">
            </div>

            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Driver Name</label>
                <input name="driver_name" class="form-control form-control-sm" placeholder="Driver Name">
            </div>

            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Driver Mobile</label>
                <input name="driver_mobile" class="form-control form-control-sm" placeholder="Mobile No">
            </div>

            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Vehicle In</label>
                <input type="datetime-local" name="vehicle_in_time" class="form-control form-control-sm">
            </div>

            <div class="col-md-2 mb-1">
                <label class="form-label fw-semibold small mb-1">Vehicle Out</label>
                <input type="datetime-local" name="vehicle_out_time" class="form-control form-control-sm">
            </div>

            <div class="col-md-1 mb-1">
                <label class="form-label fw-semibold small mb-1">Remarks</label>
                <input name="remarks" class="form-control form-control-sm" placeholder="Remarks">
            </div>
        </div>
    </div>
</div>

{{-- ================= ITEMS ================= --}}
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white py-2">
        <h6 class="mb-0 fw-bold text-dark">Inbound Products</h6>
        <button type="button"
                id="addRowBtn"
                class="btn btn-sm btn-success">
            <i class="bi bi-plus-lg"></i> Add Row
        </button>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
            <table class="table table-bordered table-sm mb-0 align-middle" id="itemsTable">
                <thead class="table-light">
                <tr>
                    <th width="240">Product</th>
                    <th width="160">Warehouse</th>
                    <th width="130">Product Code</th>
                    <th width="80">Units</th>
                    <th width="70">Pack</th>
                    <th width="80">Total</th>
                    <th width="120">Pallets</th>
                    <th width="110">QC Clearance</th>
                    <th width="110">Status</th>
                    <th width="40"></th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <button class="btn btn-primary">Save Inbound</button>
    <a href="{{ route('inbound.index') }}"
       class="btn btn-secondary">Back</a>
</div>
</form>

{{-- ================= BATCH MODAL ================= --}}
<div class="modal fade" id="batchModal" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">

<div class="modal-header">
    <h6 class="modal-title fw-bold">Batch Details</h6>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body row g-2">
    <div class="col-md-4">
        <label class="form-label small">SAP Batch</label>
        <input class="form-control form-control-sm modal-sap">
    </div>
    <div class="col-md-4">
        <label class="form-label small">Vendor Batch</label>
        <input class="form-control form-control-sm modal-vendor">
    </div>
    <div class="col-md-4">
        <label class="form-label small">IBD No</label>
        <input class="form-control form-control-sm modal-ibd">
    </div>

    <div class="col-md-4">
        <label class="form-label small">PO No</label>
        <input class="form-control form-control-sm modal-po">
    </div>
    <div class="col-md-4">
        <label class="form-label small">MFG Date</label>
        <input type="date"
               class="form-control form-control-sm modal-mfg">
    </div>
    <div class="col-md-4">
        <label class="form-label small">Expiry Date</label>
        <input type="date"
               class="form-control form-control-sm modal-expiry">
    </div>
</div>

<div class="modal-footer">
    <button type="button"
            class="btn btn-primary btn-sm"
            id="saveBatchBtn">
        OK
    </button>
</div>

</div>
</div>
</div>

{{-- ================= UNIFIED PALLET ALLOCATION & PREVIEW MODAL ================= --}}
<div class="modal fade" id="palletManualModal" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-centered">
<div class="modal-content">
<div class="modal-header bg-light">
    <h6 class="modal-title fw-bold text-dark"><i class="bi bi-box-seam"></i> Pallet Allocation Details & Assignment</h6>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row g-3">
        <!-- Left Side: Live Preview & Summary -->
        <div class="col-md-5 border-end">
            <div class="p-3 bg-light rounded border mb-3">
                <h6 class="fw-bold mb-2 text-primary">Live Allocation Preview</h6>
                <div id="pallet-preview-summary" class="small" style="max-height: 280px; overflow-y: auto;">
                    <p class="text-muted">Loading live allocation preview...</p>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Allocation Mode</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="allocation_mode" id="mode_auto" value="auto" checked>
                        <label class="form-check-label" for="mode_auto">Auto (FIFO)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="allocation_mode" id="mode_manual" value="manual">
                        <label class="form-check-label" for="mode_manual">Manual Override</label>
                    </div>
                </div>
            </div>

            <div class="manual-controls-section d-none">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Warehouse</label>
                    <select class="form-select form-select-sm modal-manual-warehouse">
                        <option value="">-- Select Warehouse --</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Row</label>
                    <select class="form-select form-select-sm modal-manual-row">
                        <option value="">-- Select Row --</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Starting Pallet Number</label>
                    <input type="number" min="1" class="form-control form-control-sm modal-manual-pallet-start" placeholder="e.g. 1 (Optional - auto find free)">
                </div>
                <div class="manual-range-info p-2 mb-2 border rounded small d-none">
                    <!-- Range info injected here -->
                </div>
            </div>
        </div>

        <!-- Right Side: Pallet Layout / Grid -->
        <div class="col-md-7">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="form-label fw-bold mb-0">Pallet Layout / Grid</h6>
                <div class="d-flex gap-2">
                    <span class="badge bg-success-subtle text-success border border-success">[ ] Empty</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger">[ ] Occupied</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary">[ ] Proposed</span>
                </div>
            </div>
            <div id="modal-pallet-grid" class="d-flex flex-wrap gap-2 p-2 border rounded bg-light" style="max-height: 400px; overflow-y: auto; align-content: flex-start;">
                <div class="text-muted small p-4 text-center w-100">Select manual mode and a row above to view the layout grid.</div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer bg-light">
    <button type="button" class="btn btn-secondary btn-sm" id="clearManualPalletBtn">Reset to Auto</button>
    <button type="button" class="btn btn-primary btn-sm" id="saveManualPalletBtn">Apply</button>
</div>
</div>
</div>
</div>
<div id="global-product-autocomplete" class="dropdown-menu shadow" style="max-height: 200px; overflow-y: auto; display: none; position: fixed; z-index: 2050; box-shadow: 0 4px 12px rgba(0,0,0,0.15); background-color: #ffffff; border: 1px solid #ced4da;"></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

let rowIndex = 0;
let activeRow = null;
let currentProposedPalletNames = [];
const warehouses = @json($warehouses);
const products = @json($products);

/* ================= FORM SUBMISSION & DISABLE ENTER (Excel Style) ================= */
document.getElementById('inboundForm').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        
        const target = e.target;
        if (target.closest('#itemsTable')) {
            const currentCell = target.closest('td');
            const currentRow = target.closest('tr');
            const columnIndex = Array.from(currentRow.children).indexOf(currentCell);
            
            const nextRow = currentRow.nextElementSibling;
            if (nextRow) {
                // Focus the corresponding input in the next row
                const nextCell = nextRow.children[columnIndex];
                const input = nextCell ? nextCell.querySelector('input, select') : null;
                if (input) {
                    input.focus();
                } else {
                    const fallback = nextRow.querySelector('input, select');
                    if (fallback) fallback.focus();
                }
            } else {
                // Last row, add a new row
                document.getElementById('addRowBtn').click();
                setTimeout(() => {
                    const rows = document.querySelectorAll('#itemsTable tbody tr');
                    const lastRow = rows[rows.length - 1];
                    const input = lastRow.querySelector('.product-input');
                    if (input) input.focus();
                }, 50);
            }
        }
    }
});

document.getElementById('inboundForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    let submitBtn = this.querySelector('button[type="submit"]') || this.querySelector('button.btn-primary');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    }

    let formData = new FormData(this);

    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json().then(data => ({ status: response.status, body: data })))
    .then(res => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save Inbound';
        }

        if (res.status === 422) {
            let errors = res.body.errors || res.body;
            let errorMsg = Object.values(errors).flat().join('<br>');
            if(!errorMsg && res.body.message) {
                errorMsg = res.body.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: errorMsg,
            });
        } else if (res.status >= 400) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: res.body.message || 'Something went wrong!',
            });
        } else if (res.status === 200 || res.status === 201) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: res.body.message || 'Inbound saved successfully!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                if (res.body.redirect) {
                    window.location.href = res.body.redirect;
                } else {
                    window.location.reload();
                }
            });
        }
    })
    .catch(error => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save Inbound';
        }
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'An error occurred while communicating with the server.',
        });
    });
});

/* ================= ADD ROW ================= */
document.getElementById('addRowBtn').addEventListener('click', function () {
    const globalWarehouseVal = document.getElementById('global_warehouse_id')?.value || 'auto';

    document.querySelector('#itemsTable tbody')
    .insertAdjacentHTML('beforeend', `
<tr>
<td>
<div class="product-autocomplete-wrapper">
    <input type="text"
           class="form-control form-control-sm product-input"
           placeholder="Search product"
           autocomplete="off">
</div>
<input type="hidden"
       name="items[${rowIndex}][product_id]"
       class="product-id">
</td>

<td>
<select name="items[${rowIndex}][warehouse_id]" class="form-select form-select-sm warehouse-select">
    <option value="auto">Auto</option>
    @foreach($warehouses as $w)
        <option value="{{ $w->id }}">{{ $w->name }}</option>
    @endforeach
</select>
</td>

<td>
<input class="form-control form-control-sm product-code bg-light" readonly style="cursor: pointer;" title="Click to view/edit batch details">
</td>

<td>
<input type="number" min="1"
       name="items[${rowIndex}][units_received]"
       class="form-control form-control-sm units">
</td>

<td>
<input class="form-control form-control-sm pack-size bg-light" readonly>
</td>

<td>
<input class="form-control form-control-sm total-qty bg-light fw-bold" readonly>
</td>

<td>
<div class="input-group input-group-sm">
    <input type="number" min="0"
           name="items[${rowIndex}][pallets_used]"
           class="form-control form-control-sm pallets-used"
           placeholder="Auto"
           title="Click eye button to view details or assign manually">
    <button type="button" class="btn btn-outline-secondary btn-sm preview-pallets-btn" title="View Pallet Allocation Preview">
        👁
    </button>
</div>
<input type="hidden" name="items[${rowIndex}][use_pallets]" value="1">
<input type="hidden" class="pallets-per-packing" value="">
<div class="manual-pallet-info mt-1 small text-primary" style="font-size:10px; font-weight:600; line-height: 1.1;"></div>
</td>

<td>
<select name="items[${rowIndex}][quality_clearance]" class="form-select form-select-sm">
    <option value="pending">🟡 Pending</option>
    <option value="approved">🟢 Approved</option>
    <option value="rejected">🔴 Rejected</option>
</select>
</td>

<td>
<div class="btn-group btn-group-sm" role="group">
    <input type="checkbox" class="btn-check" name="items[${rowIndex}][sound_stock]" id="ss_${rowIndex}" checked>
    <label class="btn btn-outline-success px-1 py-0" for="ss_${rowIndex}" title="Sound Stock" style="font-size: 10px; font-weight: 600;">S</label>

    <input type="checkbox" class="btn-check" name="items[${rowIndex}][block_stock]" id="bs_${rowIndex}">
    <label class="btn btn-outline-danger px-1 py-0" for="bs_${rowIndex}" title="Block Stock" style="font-size: 10px; font-weight: 600;">B</label>

    <input type="checkbox" class="btn-check" name="items[${rowIndex}][hold_stock]" id="hs_${rowIndex}">
    <label class="btn btn-outline-warning px-1 py-0" for="hs_${rowIndex}" title="Hold Stock" style="font-size: 10px; font-weight: 600;">H</label>
</div>
</td>

<td>
<button type="button"
        class="btn btn-sm btn-outline-danger removeRow" title="Remove Row">×</button>
</td>

<input type="hidden" name="items[${rowIndex}][sap_batch]">
<input type="hidden" name="items[${rowIndex}][vendor_batch]">
<input type="hidden" name="items[${rowIndex}][ibd_no]">
<input type="hidden" name="items[${rowIndex}][po_no]">
<input type="hidden" name="items[${rowIndex}][mfg_date]">
<input type="hidden" name="items[${rowIndex}][expiry_date]">
<input type="hidden" name="items[${rowIndex}][warehouse_row_id]" class="manual-row-id">
<input type="hidden" name="items[${rowIndex}][pallet_start]" class="manual-pallet-start">
</tr>
`);

    const newRow = document.querySelector('#itemsTable tbody tr:last-child');
    if (newRow) {
        const select = newRow.querySelector('.warehouse-select');
        if (select) {
            select.value = globalWarehouseVal;
        }
    }

    rowIndex++;
});

// Default 5 rows (Excel style)
for (let i = 0; i < 5; i++) {
    document.getElementById('addRowBtn').click();
}

// Sync global warehouse changes if element exists
const globalWhElem = document.getElementById('global_warehouse_id');
if (globalWhElem) {
    globalWhElem.addEventListener('change', function () {
        const val = this.value;
        document.querySelectorAll('.warehouse-select').forEach(select => {
            select.value = val;
        });
    });
}

/* ================= CUSTOM PRODUCT AUTOCOMPLETE (Excel style) ================= */
let activeAutocompleteInput = null;

function renderAutocomplete(input) {
    activeAutocompleteInput = input;
    const globalDropdown = document.getElementById('global-product-autocomplete');
    const query = input.value.trim().toLowerCase();

    const rect = input.getBoundingClientRect();
    globalDropdown.style.top = rect.bottom + 'px';
    globalDropdown.style.left = rect.left + 'px';
    globalDropdown.style.width = Math.max(260, rect.width) + 'px';

    const matches = products.filter(p => {
        if (!query) return true;
        const nameMatch = p.name && p.name.toLowerCase().includes(query);
        const codeMatch = p.item_code && p.item_code.toLowerCase().includes(query);
        return nameMatch || codeMatch;
    });

    if (matches.length === 0) {
        globalDropdown.innerHTML = '<div class="dropdown-item text-muted small">No product found</div>';
        globalDropdown.style.display = 'block';
        return;
    }

    let itemsHtml = '';
    matches.forEach(p => {
        itemsHtml += `<button type="button" class="dropdown-item small text-truncate select-autocomplete-product" 
            data-id="${p.id}" 
            data-name="${p.name}" 
            data-code="${p.item_code}" 
            data-pack="${p.pack_size}" 
            data-cartons="${p.cartons_per_pallet || ''}">
            <strong>${p.name}</strong> (${p.item_code})
        </button>`;
    });

    globalDropdown.innerHTML = itemsHtml;
    globalDropdown.style.display = 'block';
}

document.addEventListener('input', function (e) {
    if (e.target.classList.contains('product-input')) {
        renderAutocomplete(e.target);
    }
});

document.addEventListener('focusin', function (e) {
    if (e.target.classList.contains('product-input')) {
        renderAutocomplete(e.target);
    }
});

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('product-input')) {
        renderAutocomplete(e.target);
    }
});

// Click handler to select autocomplete item
document.addEventListener('click', function (e) {
    const item = e.target.closest('.select-autocomplete-product');
    if (item && activeAutocompleteInput) {
        const input = activeAutocompleteInput;
        const row = input.closest('tr');
        const globalDropdown = document.getElementById('global-product-autocomplete');

        const pId = item.dataset.id;
        const pName = item.dataset.name;
        const pCode = item.dataset.code;
        const pPack = item.dataset.pack;
        const pCartons = item.dataset.cartons;

        input.value = `${pName} (${pCode})`;
        row.querySelector('.product-id').value = pId;
        row.querySelector('.pack-size').value = pPack;
        row.querySelector('.product-code').value = pCode;
        row.querySelector('.pallets-per-packing').value = pCartons;

        const palletsInput = row.querySelector('.pallets-used');
        if (pCartons) {
            const units = Number(row.querySelector('.units').value || 0);
            palletsInput.value = units > 0 ? Math.ceil(units / Number(pCartons)) : '';
            palletsInput.placeholder = 'Auto (' + pCartons + ' ctn/pallet)';
        } else {
            palletsInput.placeholder = 'Enter pallets';
        }

        globalDropdown.innerHTML = '';
        globalDropdown.style.display = 'none';

        activeRow = row;

        document.querySelector('.modal-sap').value = '';
        document.querySelector('.modal-vendor').value = '';
        document.querySelector('.modal-ibd').value = '';
        document.querySelector('.modal-po').value = '';
        document.querySelector('.modal-mfg').value = '';
        document.querySelector('.modal-expiry').value = '';

        new bootstrap.Modal(document.getElementById('batchModal')).show();
        return;
    }

    if (!e.target.closest('.product-input') && !e.target.closest('#global-product-autocomplete')) {
        const globalDropdown = document.getElementById('global-product-autocomplete');
        if (globalDropdown) {
            globalDropdown.style.display = 'none';
        }
    }
});

document.addEventListener('scroll', function (e) {
    const globalDropdown = document.getElementById('global-product-autocomplete');
    if (globalDropdown) {
        globalDropdown.style.display = 'none';
    }
}, true);

/* ================= PRODUCT CODE CLICK → EDIT ================= */
document.addEventListener('click', function (e) {
    if (!e.target.classList.contains('product-code')) return;

    const row = e.target.closest('tr');
    if (!row) return;

    activeRow = row;

    document.querySelector('.modal-sap').value =
        row.querySelector('input[name$="[sap_batch]"]').value || '';

    document.querySelector('.modal-vendor').value =
        row.querySelector('input[name$="[vendor_batch]"]').value || '';

    document.querySelector('.modal-ibd').value =
        row.querySelector('input[name$="[ibd_no]"]').value || '';

    document.querySelector('.modal-po').value =
        row.querySelector('input[name$="[po_no]"]').value || '';

    document.querySelector('.modal-mfg').value =
        row.querySelector('input[name$="[mfg_date]"]').value || '';

    document.querySelector('.modal-expiry').value =
        row.querySelector('input[name$="[expiry_date]"]').value || '';

    new bootstrap.Modal(document.getElementById('batchModal')).show();
});

/* ================= SAVE MODAL ================= */
document.getElementById('saveBatchBtn').addEventListener('click', function () {
    if (!activeRow) return;

    activeRow.querySelector('input[name$="[sap_batch]"]').value =
        document.querySelector('.modal-sap').value;

    activeRow.querySelector('input[name$="[vendor_batch]"]').value =
        document.querySelector('.modal-vendor').value;

    activeRow.querySelector('input[name$="[ibd_no]"]').value =
        document.querySelector('.modal-ibd').value;

    activeRow.querySelector('input[name$="[po_no]"]').value =
        document.querySelector('.modal-po').value;

    activeRow.querySelector('input[name$="[mfg_date]"]').value =
        document.querySelector('.modal-mfg').value;

    activeRow.querySelector('input[name$="[expiry_date]"]').value =
        document.querySelector('.modal-expiry').value;

    bootstrap.Modal.getInstance(
        document.getElementById('batchModal')
    ).hide();

    activeRow.querySelector('.units')?.focus();
});

/* ================= REAL-TIME UNITS → TOTAL + PALLETS ================= */
document.addEventListener('input', function (e) {
    if (!e.target.classList.contains('units')) return;

    const row   = e.target.closest('tr');
    const units = Number(e.target.value || 0);
    const pack  = Number(row.querySelector('.pack-size').value || 0);

    row.querySelector('.total-qty').value = units * pack;

    const cartonsPerPallet = Number(row.querySelector('.pallets-per-packing').value || 0);
    const palletsInput = row.querySelector('.pallets-used');
    if (cartonsPerPallet > 0 && units > 0) {
        palletsInput.value = Math.ceil(units / cartonsPerPallet);
    }
});

/* ================= REMOVE ROW ================= */
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('removeRow')) {
        e.target.closest('tr').remove();
    }
});

/* ================= SHOW UNIFIED PALLET MODAL ================= */
function openUnifiedPalletModal(row) {
    const rowWhId = row.querySelector('.warehouse-select')?.value;
    const globalWhId = document.getElementById('global_warehouse_id')?.value;
    const whId = rowWhId || globalWhId;
    activeRow = row;

    document.getElementById('pallet-preview-summary').innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm text-primary"></div> Calculating allocation...</div>';
    
    const manualRowId = row.querySelector('.manual-row-id').value;
    const manualPalletStart = row.querySelector('.manual-pallet-start').value;
    
    if (manualRowId) {
        document.getElementById('mode_manual').checked = true;
        document.querySelector('.manual-controls-section').classList.remove('d-none');
    } else {
        document.getElementById('mode_auto').checked = true;
        document.querySelector('.manual-controls-section').classList.add('d-none');
    }

    const modalWh = document.querySelector('.modal-manual-warehouse');
    const selectedWhId = (whId && whId !== 'auto') ? whId : (warehouses[0]?.id || '');
    modalWh.value = selectedWhId;

    const rowSelect = document.querySelector('.modal-manual-row');
    rowSelect.innerHTML = '<option value="">-- Select Row --</option>';
    if (selectedWhId) {
        const wh = warehouses.find(w => w.id == selectedWhId);
        if (wh && wh.rows) {
            wh.rows.forEach(r => {
                rowSelect.innerHTML += `<option value="${r.id}">Row ${r.row_name} (Capacity: ${r.pallet_capacity})</option>`;
            });
        }
    }
    
    rowSelect.value = manualRowId || '';
    document.querySelector('.modal-manual-pallet-start').value = manualPalletStart || '';

    const modalEl = document.getElementById('palletManualModal');
    const modalObj = new bootstrap.Modal(modalEl);
    modalObj.show();

    fetchPreviewAndRender();
}

function fetchPreviewAndRender() {
    if (!activeRow) return;

    const rowWhVal = activeRow.querySelector('.warehouse-select')?.value;
    const globalWhVal = document.getElementById('global_warehouse_id')?.value;
    const activeWhVal = rowWhVal || globalWhVal;

    const isManual = document.getElementById('mode_manual').checked;
    const manualWhId = document.querySelector('.modal-manual-warehouse').value;
    const manualRowId = document.querySelector('.modal-manual-row').value;
    const manualPalletStart = document.querySelector('.modal-manual-pallet-start').value;

    let targetWhId = isManual && manualWhId ? manualWhId : ((activeWhVal && activeWhVal !== 'auto') ? activeWhVal : (warehouses[0]?.id || ''));
    let whObj = warehouses.find(w => w.id == targetWhId) || warehouses[0];
    let defaultRowId = (whObj && whObj.rows && whObj.rows.length > 0) ? whObj.rows[0].id : null;

    const tableRows = document.querySelectorAll('#itemsTable tbody tr');
    const items = [];
    let activeRowIndex = 0;

    tableRows.forEach((row, idx) => {
        if (row === activeRow) {
            activeRowIndex = idx;
        }

        const pId = row.querySelector('.product-id').value;
        const uVal = row.querySelector('.units').value || 0;
        const wVal = row.querySelector('.warehouse-select')?.value || activeWhVal;
        const pUsed = row.querySelector('.pallets-used').value || 0;
        const mRowId = row.querySelector('.manual-row-id').value;
        const mPalletStart = row.querySelector('.manual-pallet-start').value;

        items.push({
            product_id: pId || '',
            units_received: uVal,
            warehouse_id: wVal,
            pallets_used: pUsed,
            warehouse_row_id: mRowId,
            pallet_start: mPalletStart
        });
    });

    if (isManual) {
        items[activeRowIndex].warehouse_id = manualWhId;
        items[activeRowIndex].warehouse_row_id = manualRowId;
        items[activeRowIndex].pallet_start = manualPalletStart;
    }

    const rangeInfoDiv = document.querySelector('.manual-range-info');
    rangeInfoDiv.classList.add('d-none');
    rangeInfoDiv.innerHTML = '';

    const activeItem = items[activeRowIndex];

    if (isManual && !activeItem.warehouse_id) {
        document.getElementById('pallet-preview-summary').innerHTML = '<div class="alert alert-warning py-2 mb-0">Please select a specific warehouse to use manual override.</div>';
        if (defaultRowId) {
            loadPalletGridWithProposed(defaultRowId, []);
        } else {
            document.getElementById('modal-pallet-grid').innerHTML = '<div class="text-muted small p-3">Please select a specific warehouse.</div>';
        }
        return;
    }

    const requestData = {
        items: items,
        active_row_index: activeRowIndex,
        _token: '{{ csrf_token() }}'
    };

    $.post('/inbound/preview-pallets', requestData, function(response) {
        if (!response.success) {
            document.getElementById('pallet-preview-summary').innerHTML = `<div class="text-danger small">${response.message}</div>`;
            return;
        }

        let summaryHtml = '';
        currentProposedPalletNames = [];
        let firstAssignedRowId = null;

        if (response.allocations && response.allocations.length > 0) {
            response.allocations.forEach(function(alloc) {
                const badgeColor = alloc.type === 'manual' ? 'bg-primary' : (alloc.type === 'partial' ? 'bg-warning text-dark' : 'bg-success');
                
                let pNamesStr = '';
                if (alloc.pallet_names && alloc.pallet_names.length > 0) {
                    currentProposedPalletNames = currentProposedPalletNames.concat(alloc.pallet_names);
                    if (alloc.pallets_count > 1) {
                        pNamesStr = `<strong class="text-dark">${alloc.pallet_names[0]} to ${alloc.pallet_names[alloc.pallet_names.length - 1]}</strong> (${alloc.pallets_count} pallets contiguous)`;
                    } else {
                        pNamesStr = alloc.pallet_names.map(name => `<strong class="text-dark">${name}</strong>`).join(', ');
                    }
                }

                const rowIdAttr = alloc.row_id ? `data-row-id="${alloc.row_id}"` : '';
                const clickStyle = alloc.row_id ? 'cursor: pointer; transition: all 0.2s;' : '';
                const clickClass = alloc.row_id ? 'allocation-item-card p-2 mb-2 rounded border hover-shadow bg-white' : 'p-2 mb-2 rounded border bg-light';

                summaryHtml += `
                    <div class="${clickClass}" ${rowIdAttr} style="${clickStyle}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge ${badgeColor}">${alloc.type.toUpperCase()}</span>
                            <span class="text-muted fw-bold" style="font-size:11px;">${alloc.units} cartons</span>
                        </div>
                        <div class="mb-1" style="font-size:12px;"><strong>Warehouse:</strong> ${alloc.warehouse_name}</div>
                        <div class="mb-1" style="font-size:12px;"><strong>Row/Location:</strong> ${alloc.row_name}</div>
                        <div style="font-size:12px;"><strong>Pallets:</strong> ${pNamesStr}</div>
                        ${alloc.row_id ? '<div class="text-end mt-1"><span class="badge bg-primary-subtle text-primary" style="font-size: 9px;"><i class="bi bi-grid-3x3"></i> Click to View Grid</span></div>' : ''}
                    </div>
                `;

                if (!firstAssignedRowId && alloc.row_id) {
                    firstAssignedRowId = alloc.row_id;
                }
            });

            if (isManual) {
                const alloc = response.allocations[0];
                rangeInfoDiv.classList.remove('d-none');
                rangeInfoDiv.className = 'manual-range-info p-2 mb-2 border rounded small bg-info-subtle text-info-emphasis border-info';
                
                let rangeText = '';
                if (alloc.pallet_names && alloc.pallet_names.length > 0) {
                    const startP = alloc.pallet_names[0];
                    const endP = alloc.pallet_names[alloc.pallet_names.length - 1];
                    rangeText = `<strong>Range:</strong> ${startP} to ${endP} (${alloc.pallets_count} pallets)`;
                } else {
                    rangeText = `<strong>Range:</strong> No pallets allocated yet.`;
                }

                rangeInfoDiv.innerHTML = `
                    <div class="fw-bold mb-1 text-primary"><i class="bi bi-info-circle"></i> Manual Assignment Details:</div>
                    <div class="mb-1" style="font-size:11px;"><strong>Warehouse:</strong> ${alloc.warehouse_name}</div>
                    <div class="mb-1" style="font-size:11px;">${rangeText}</div>
                    <div class="manual-warnings text-danger fw-bold small" style="font-size:11px;"></div>
                `;
            }
        } else {
            summaryHtml = '<div class="alert alert-info py-2 mb-0">No pallets allocated. Enter cartons and/or pallets to see preview.</div>';
        }

        document.getElementById('pallet-preview-summary').innerHTML = summaryHtml;

        const gridRowId = (isManual && manualRowId) ? manualRowId : (firstAssignedRowId || defaultRowId);
        if (gridRowId) {
            loadPalletGridWithProposed(gridRowId, currentProposedPalletNames);
            setTimeout(() => {
                const activeCard = document.querySelector(`.allocation-item-card[data-row-id="${gridRowId}"]`);
                if (activeCard) {
                    activeCard.classList.remove('bg-white');
                    activeCard.classList.add('border-primary', 'bg-primary-subtle');
                }
            }, 50);
        } else {
            document.getElementById('modal-pallet-grid').innerHTML = '<div class="text-muted small p-3 text-center w-100">No row layout grid loaded. Switch to Manual Mode or select a specific Warehouse & Row.</div>';
        }
    }).fail(function(xhr) {
        const errorMsg = xhr.responseJSON ? (xhr.responseJSON.message || 'Error fetching preview') : 'Failed to fetch preview.';
        document.getElementById('pallet-preview-summary').innerHTML = `<div class="alert alert-danger py-2 mb-0">${errorMsg}</div>`;
    });
}

function loadPalletGridWithProposed(rowId, proposedNames) {
    const gridContainer = document.getElementById('modal-pallet-grid');
    gridContainer.innerHTML = '<div class="text-center w-100 py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading pallet grid...</div>';

    let rowName = '';
    let prefix = 'Pallet ';
    let padLength = 0;
    let usePrefixLogic = false;
    let startNum = 1;

    warehouses.forEach(wh => {
        if (wh.rows) {
            const foundRow = wh.rows.find(r => r.id == rowId);
            if (foundRow) rowName = foundRow.row_name;
        }
    });

    if (rowName) {
        const match = rowName.match(/^(.+?)(\d+)\s+to\s+/i);
        if (match) {
            prefix = match[1];
            padLength = match[2].length;
            startNum = parseInt(match[2], 10);
            usePrefixLogic = true;
        }
    }

    $.get('/warehouses/rows/' + rowId + '/pallets', function(data) {
        gridContainer.innerHTML = '';
        
        let occupiedProposed = [];

        data.pallets.forEach(function(pallet) {
            const currentNum = usePrefixLogic ? (startNum + pallet.pallet_number - 1) : pallet.pallet_number;
            const displayName = usePrefixLogic 
                ? prefix + String(currentNum).padStart(padLength, '0')
                : 'Pallet ' + pallet.pallet_number;

            const isProposed = proposedNames.some(name => {
                const cleanName = name.split(' (')[0];
                return cleanName === displayName;
            });

            if (isProposed && !pallet.is_empty) {
                occupiedProposed.push(displayName);
            }

            const box = document.createElement('div');
            box.className = 'pallet-box p-2 border rounded text-center small position-relative';
            box.style.width = '120px';
            box.style.minHeight = '75px';
            box.style.cursor = pallet.is_empty ? 'pointer' : 'not-allowed';
            box.dataset.number = pallet.pallet_number;
            box.dataset.isEmpty = pallet.is_empty ? '1' : '0';
            box.dataset.displayName = displayName;

            if (isProposed) {
                box.style.backgroundColor = pallet.is_empty ? '#cfe2ff' : '#f8d7da';
                box.style.borderColor = pallet.is_empty ? '#9ec5fe' : '#f5c2c7';
                box.style.borderWidth = '2px';
                box.style.boxShadow = '0 0 5px rgba(13, 110, 253, 0.5)';
                box.innerHTML = `
                    <div class="fw-bold ${pallet.is_empty ? 'text-primary' : 'text-danger'}" style="font-size:11px;">${displayName}</div>
                    <div class="text-muted fw-semibold" style="font-size:10px; margin-top: 4px;">${pallet.is_empty ? '[ Proposed ]' : '[ Conflict ]'}</div>
                `;
            } else if (pallet.is_empty) {
                box.style.backgroundColor = '#d1e7dd';
                box.style.borderColor = '#a3cfbb';
                box.innerHTML = `
                    <div class="fw-bold text-success" style="font-size:11px;">${displayName}</div>
                    <div class="text-muted" style="font-size:10px; margin-top: 4px;">[ Empty ]</div>
                `;
            } else {
                box.style.backgroundColor = '#f8d7da';
                box.style.borderColor = '#f5c2c7';
                box.innerHTML = `
                    <div class="fw-bold text-danger" style="font-size:11px;">${displayName}</div>
                    <div class="text-muted" style="font-size:10px; margin-top: 4px;">[ Occupied ]</div>
                `;
            }

            box.addEventListener('click', function() {
                if (!pallet.is_empty) return;
                if (!document.getElementById('mode_manual').checked) {
                    Swal.fire('Info', 'Switch to "Manual Override" allocation mode to manually assign pallets.', 'info');
                    return;
                }
                document.querySelector('.modal-manual-pallet-start').value = pallet.pallet_number;
                fetchPreviewAndRender();
            });

            gridContainer.appendChild(box);
        });

        const warningDiv = document.querySelector('.manual-warnings');
        if (warningDiv) {
            if (occupiedProposed.length > 0) {
                warningDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> Warning: Proposed range overlaps occupied pallets (${occupiedProposed.join(', ')})!`;
            } else {
                warningDiv.innerHTML = '';
            }
        }
    });
}

document.addEventListener('click', function (e) {
    const card = e.target.closest('.allocation-item-card');
    if (card && card.dataset.rowId) {
        const rowId = card.dataset.rowId;
        document.querySelectorAll('.allocation-item-card').forEach(c => {
            c.classList.remove('border-primary', 'bg-primary-subtle');
            c.classList.add('bg-white');
        });
        card.classList.remove('bg-white');
        card.classList.add('border-primary', 'bg-primary-subtle');
        loadPalletGridWithProposed(rowId, currentProposedPalletNames);
    }

    if (e.target.classList.contains('preview-pallets-btn') || e.target.closest('.preview-pallets-btn')) {
        const btn = e.target.classList.contains('preview-pallets-btn') ? e.target : e.target.closest('.preview-pallets-btn');
        const row = btn.closest('tr');
        openUnifiedPalletModal(row);
    }
});

document.querySelectorAll('input[name="allocation_mode"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'manual') {
            document.querySelector('.manual-controls-section').classList.remove('d-none');
        } else {
            document.querySelector('.manual-controls-section').classList.add('d-none');
        }
        fetchPreviewAndRender();
    });
});

document.querySelector('.modal-manual-warehouse').addEventListener('change', function() {
    const whId = this.value;
    const rowSelect = document.querySelector('.modal-manual-row');
    rowSelect.innerHTML = '<option value="">-- Select Row --</option>';
    if (whId) {
        const wh = warehouses.find(w => w.id == whId);
        if (wh && wh.rows) {
            wh.rows.forEach(r => {
                rowSelect.innerHTML += `<option value="${r.id}">Row ${r.row_name} (Capacity: ${r.pallet_capacity})</option>`;
            });
        }
    }
    fetchPreviewAndRender();
});

document.querySelector('.modal-manual-row').addEventListener('change', function() {
    fetchPreviewAndRender();
});

document.querySelector('.modal-manual-pallet-start').addEventListener('input', function() {
    fetchPreviewAndRender();
});

document.getElementById('saveManualPalletBtn').addEventListener('click', function() {
    if (!activeRow) return;

    const isManual = document.getElementById('mode_manual').checked;
    const manualWhId = document.querySelector('.modal-manual-warehouse').value;
    const manualRowId = document.querySelector('.modal-manual-row').value;
    const manualPalletStart = document.querySelector('.modal-manual-pallet-start').value;
    const infoDiv = activeRow.querySelector('.manual-pallet-info');

    if (isManual) {
        if (!manualWhId || !manualRowId) {
            Swal.fire('Warning', 'Please select both Warehouse and Row for manual assignment.', 'warning');
            return;
        }

        activeRow.querySelector('.manual-row-id').value = manualRowId;
        activeRow.querySelector('.manual-pallet-start').value = manualPalletStart;

        const whObj = warehouses.find(w => w.id == manualWhId);
        const whName = whObj ? whObj.name : 'Warehouse';
        const rowObj = whObj && whObj.rows ? whObj.rows.find(r => r.id == manualRowId) : null;
        const rowName = rowObj ? rowObj.row_name : 'Row';
        const startText = manualPalletStart ? `, Start: P${manualPalletStart}` : '';

        if (infoDiv) {
            infoDiv.innerHTML = `<span class="badge bg-warning text-dark"><i class="bi bi-pin-angle-fill me-1"></i>Manual: ${whName} - ${rowName}${startText}</span>`;
        }
    } else {
        activeRow.querySelector('.manual-row-id').value = '';
        activeRow.querySelector('.manual-pallet-start').value = '';
        
        let firstAllocText = '';
        const cards = document.querySelectorAll('#pallet-preview-summary .allocation-item-card');
        if (cards.length > 0) {
            const firstCard = cards[0];
            const whText = firstCard.querySelector('div:nth-child(2)')?.textContent?.replace('Warehouse:', '')?.trim() || '';
            const rowText = firstCard.querySelector('div:nth-child(3)')?.textContent?.replace('Row/Location:', '')?.trim() || '';
            const palletText = firstCard.querySelector('div:nth-child(4)')?.textContent?.replace('Pallets:', '')?.trim() || '';
            firstAllocText = `<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-magic me-1"></i>Auto: ${whText} - ${rowText} (${palletText})</span>`;
        } else {
            firstAllocText = `<span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-magic me-1"></i>Auto FIFO</span>`;
        }

        if (infoDiv) {
            infoDiv.innerHTML = firstAllocText;
        }
    }

    bootstrap.Modal.getInstance(document.getElementById('palletManualModal')).hide();
});

document.getElementById('clearManualPalletBtn').addEventListener('click', function() {
    document.getElementById('mode_auto').checked = true;
    document.querySelector('.manual-controls-section').classList.add('d-none');
    document.querySelector('.modal-manual-row').value = '';
    document.querySelector('.modal-manual-pallet-start').value = '';
    
    if (activeRow) {
        activeRow.querySelector('.manual-row-id').value = '';
        activeRow.querySelector('.manual-pallet-start').value = '';
        const infoDiv = activeRow.querySelector('.manual-pallet-info');
        if (infoDiv) infoDiv.innerHTML = '';
    }

    fetchPreviewAndRender();
});

});
</script>
@endpush
