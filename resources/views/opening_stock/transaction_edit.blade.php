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

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('opening-stock.index') }}#transactions-pane" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
    <h5 class="mb-0 fw-bold">Edit Opening Stock Entry #OS-{{ $stockIn->id }}</h5>
</div>

<form method="POST"
      action="{{ route('opening-stock.transaction.update', $stockIn) }}"
      id="openingStockForm">
@csrf
@method('PUT')

{{-- ================= HEADER ================= --}}
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text"></i> Document Header</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Default Warehouse</label>
                <select name="warehouse_id"
                        id="global_warehouse_id"
                        class="form-control form-control-sm"
                        required>
                    <option value="auto" {{ $stockIn->warehouse_id === 'auto' ? 'selected' : '' }}>Auto Assign (Automatic)</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $stockIn->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-8 mb-3">
                <label class="form-label fw-bold">Remarks</label>
                <input type="text"
                       name="remarks"
                       value="{{ $stockIn->remarks }}"
                       class="form-control form-control-sm">
            </div>
        </div>
    </div>
</div>

{{-- ================= ITEMS ================= --}}
<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-box-seam"></i> Products &amp; Batches</h6>
        <button type="button"
                id="addRowBtn"
                class="btn btn-sm btn-success">
            + Add Row
        </button>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-bordered table-sm mb-0" id="itemsTable">
                <thead>
                <tr class="table-dark">
                    <th width="240">Product</th>
                    <th width="160">Warehouse</th>
                    <th width="120">Product Code</th>
                    <th width="90">Units (ctn)</th>
                    <th width="80">Pack Size</th>
                    <th width="90">Total Qty</th>
                    <th width="130">Pallets</th>
                    <th width="100">QC Clearance</th>
                    <th width="140">Status</th>
                    <th width="40"></th>
                </tr>
                </thead>
                <tbody>
                    {{-- Rows will be injected here on load --}}
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-light py-3 text-end">
        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
            Update Opening Stock Entry
        </button>
    </div>
</div>

</form>

{{-- ================= BATCH BUBBLE MODAL ================= --}}
<div class="modal fade" id="batchModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title">Enter Batch &amp; Expiry Details</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-bold">SAP Batch</label>
                        <input type="text" class="form-control form-control-sm modal-sap" placeholder="Enter SAP batch">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-bold">Vendor Batch</label>
                        <input type="text" class="form-control form-control-sm modal-vendor" placeholder="Enter vendor batch">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-bold">IBD No</label>
                        <input type="text" class="form-control form-control-sm modal-ibd" placeholder="Enter inbound delivery no">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-bold">PO No</label>
                        <input type="text" class="form-control form-control-sm modal-po" placeholder="Enter purchase order no">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-bold">MFG Date</label>
                        <input type="date" class="form-control form-control-sm modal-mfg">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-bold">Expiry Date</label>
                        <input type="date" class="form-control form-control-sm modal-expiry">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="saveBatchBtn">Save Batch Info</button>
            </div>
        </div>
    </div>
</div>

{{-- ================= MANUAL PALLET ALLOCATION MODAL ================= --}}
<div class="modal fade" id="palletManualModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title">Pallet Slots Allocation &amp; Visual Grid</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="row">
                    <!-- Left Side: Allocations Summary -->
                    <div class="col-md-5 border-end">
                        <h6 class="fw-bold text-secondary mb-2">Live Allocation Preview</h6>
                        <div id="pallet-preview-summary" class="mb-3">
                            <!-- Populated via AJAX -->
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
const groupedItems = @json($groupedItems);

/* ================= FORM SUBMISSION & DISABLE ENTER ================= */
document.getElementById('openingStockForm').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const target = e.target;
        if (target.closest('#itemsTable')) {
            const currentCell = target.closest('td');
            const currentRow = target.closest('tr');
            const columnIndex = Array.from(currentRow.children).indexOf(currentCell);
            
            const nextRow = currentRow.nextElementSibling;
            if (nextRow) {
                const nextCell = nextRow.children[columnIndex];
                const input = nextCell ? nextCell.querySelector('input, select') : null;
                if (input) {
                    input.focus();
                } else {
                    const fallback = nextRow.querySelector('input, select');
                    if (fallback) fallback.focus();
                }
            } else {
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

document.getElementById('openingStockForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    let submitBtn = this.querySelector('button[type="submit"]');
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
            submitBtn.innerHTML = 'Update Opening Stock Entry';
        }

        if (res.status === 422) {
            let errors = res.body.errors || res.body;
            let errorMsg = Object.values(errors).flat().join('<br>');
            if(!errorMsg && res.body.message) {
                errorMsg = res.body.message;
            }
            Swal.fire({ icon: 'error', title: 'Validation Error', html: errorMsg });
        } else if (res.status >= 400) {
            Swal.fire({ icon: 'error', title: 'Error', text: res.body.message || 'Something went wrong!' });
        } else if (res.status === 200 || res.status === 201) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: res.body.message || 'Updated successfully!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = "{{ route('opening-stock.index') }}#transactions-pane";
            });
        }
    })
    .catch(error => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Update Opening Stock Entry';
        }
        Swal.fire({ icon: 'error', title: 'Network Error', text: 'An error occurred while communicating with the server.' });
    });
});

/* ================= ADD ROW ================= */
function addRow(prefilled = null) {
    const globalWarehouseVal = document.getElementById('global_warehouse_id').value;

    const rowHtml = `
<tr data-index="${rowIndex}">
<td>
<div class="product-autocomplete-wrapper">
    <input type="text"
           class="form-control form-control-sm product-input"
           value="${prefilled ? prefilled.product_name + ' (' + prefilled.item_code + ')' : ''}"
           placeholder="Search product"
           autocomplete="off">
</div>
<input type="hidden"
       name="items[${rowIndex}][product_id]"
       value="${prefilled ? prefilled.product_id : ''}"
       class="product-id">
<input type="hidden"
       name="items[${rowIndex}][split_ids]"
       value="${prefilled ? prefilled.split_ids : ''}">
</td>

<td>
<select name="items[${rowIndex}][warehouse_id]" class="form-select form-select-sm warehouse-select">
    <option value="auto">Auto</option>
    @foreach($warehouses as $w)
        <option value="{{ $w->id }}" ${prefilled && prefilled.warehouse_id == {{ $w->id }} ? 'selected' : ''}>{{ $w->name }}</option>
    @endforeach
</select>
</td>

<td>
<input class="form-control form-control-sm product-code" value="${prefilled ? prefilled.item_code : ''}" readonly>
</td>

<td>
<input type="number" min="1"
       name="items[${rowIndex}][units_received]"
       value="${prefilled ? prefilled.units_received : ''}"
       class="form-control form-control-sm units"
       ${prefilled && prefilled.is_dispatched ? 'readonly' : ''}>
</td>

<td>
<input class="form-control form-control-sm pack-size" value="${prefilled ? prefilled.pack_size : ''}" readonly>
</td>

<td>
<input class="form-control form-control-sm total-qty" value="${prefilled ? prefilled.total_quantity : ''}" readonly>
</td>

<td>
<div class="input-group input-group-sm">
    <input type="number" min="0"
           name="items[${rowIndex}][pallets_used]"
           value="${prefilled ? prefilled.pallets_used : ''}"
           class="form-control form-control-sm pallets-used"
           placeholder="Auto"
           ${prefilled && prefilled.is_dispatched ? 'readonly' : ''}>
    <button type="button" class="btn btn-outline-secondary btn-sm preview-pallets-btn" title="View Pallet Allocation Preview">
        👁
    </button>
</div>
<input type="hidden" name="items[${rowIndex}][use_pallets]" value="1">
<input type="hidden" class="pallets-per-packing" value="${prefilled ? prefilled.cartons_per_pallet : ''}">
<div class="manual-pallet-info mt-1 small text-primary" style="font-size:10px; font-weight:600; line-height: 1.1;">
    ${prefilled && prefilled.warehouse_row_id ? 'Row: ' + prefilled.warehouse_row_id + ', Start: ' + (prefilled.pallet_start || 'Auto') : ''}
</div>
</td>

<td>
<select name="items[${rowIndex}][quality_clearance]" class="form-select form-select-sm">
    <option value="pending" ${prefilled && prefilled.quality_clearance === 'pending' ? 'selected' : ''}>Pending</option>
    <option value="approved" ${prefilled && prefilled.quality_clearance === 'approved' ? 'selected' : ''}>Approved</option>
    <option value="rejected" ${prefilled && prefilled.quality_clearance === 'rejected' ? 'selected' : ''}>Rejected</option>
</select>
</td>

<td>
<label class="me-1"><input type="checkbox" name="items[${rowIndex}][sound_stock]" ${prefilled && prefilled.sound_stock ? 'checked' : (!prefilled ? 'checked' : '')}> S</label>
<label class="me-1"><input type="checkbox" name="items[${rowIndex}][block_stock]" ${prefilled && prefilled.block_stock ? 'checked' : ''}> B</label>
<label class="me-1"><input type="checkbox" name="items[${rowIndex}][hold_stock]" ${prefilled && prefilled.hold_stock ? 'checked' : ''}> H</label>
</td>

<td>
<button type="button"
        class="btn btn-sm btn-danger removeRow"
        ${prefilled && prefilled.is_dispatched ? 'disabled' : ''}>×</button>
</td>

<input type="hidden" name="items[${rowIndex}][sap_batch]" value="${prefilled ? prefilled.sap_batch : ''}">
<input type="hidden" name="items[${rowIndex}][vendor_batch]" value="${prefilled ? prefilled.vendor_batch : ''}">
<input type="hidden" name="items[${rowIndex}][ibd_no]" value="${prefilled ? prefilled.ibd_no : ''}">
<input type="hidden" name="items[${rowIndex}][po_no]" value="${prefilled ? prefilled.po_no : ''}">
<input type="hidden" name="items[${rowIndex}][mfg_date]" value="${prefilled ? prefilled.mfg_date : ''}">
<input type="hidden" name="items[${rowIndex}][expiry_date]" value="${prefilled ? prefilled.expiry_date : ''}">
<input type="hidden" name="items[${rowIndex}][warehouse_row_id]" class="manual-row-id" value="${prefilled ? prefilled.warehouse_row_id : ''}">
<input type="hidden" name="items[${rowIndex}][pallet_start]" class="manual-pallet-start" value="${prefilled ? prefilled.pallet_start : ''}">
</tr>
`;

    document.querySelector('#itemsTable tbody').insertAdjacentHTML('beforeend', rowHtml);

    const newRow = document.querySelector(`#itemsTable tbody tr[data-index="${rowIndex}"]`);
    if (newRow && !prefilled) {
        const select = newRow.querySelector('.warehouse-select');
        if (select) {
            select.value = globalWarehouseVal;
        }
    }

    rowIndex++;
}

document.getElementById('addRowBtn').addEventListener('click', function () {
    addRow();
});

// Sync global warehouse changes
document.getElementById('global_warehouse_id').addEventListener('change', function () {
    const val = this.value;
    document.querySelectorAll('.warehouse-select').forEach(select => {
        select.value = val;
    });
});

/* ================= CUSTOM PRODUCT AUTOCOMPLETE ================= */
let activeAutocompleteInput = null;

function renderAutocomplete(input) {
    activeAutocompleteInput = input;
    const globalDropdown = document.getElementById('global-product-autocomplete');
    const query = input.value.trim().toLowerCase();

    const rect = input.getBoundingClientRect();
    globalDropdown.style.top = rect.bottom + 'px';
    globalDropdown.style.left = rect.left + 'px';
    globalDropdown.style.width = rect.width + 'px';

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

/* ================= BATCH BUBBLE SAVE ================= */
document.getElementById('saveBatchBtn').addEventListener('click', function () {
    if (!activeRow) return;

    activeRow.querySelector('input[name$="[sap_batch]"]').value = document.querySelector('.modal-sap').value;
    activeRow.querySelector('input[name$="[vendor_batch]"]').value = document.querySelector('.modal-vendor').value;
    activeRow.querySelector('input[name$="[ibd_no]"]').value = document.querySelector('.modal-ibd').value;
    activeRow.querySelector('input[name$="[po_no]"]').value = document.querySelector('.modal-po').value;
    activeRow.querySelector('input[name$="[mfg_date]"]').value = document.querySelector('.modal-mfg').value;
    activeRow.querySelector('input[name$="[expiry_date]"]').value = document.querySelector('.modal-expiry').value;

    bootstrap.Modal.getInstance(document.getElementById('batchModal')).hide();
    activeRow.querySelector('.units')?.focus();
});

/* ================= REAL-TIME UNITS → TOTAL + PALLETS ================= */
document.addEventListener('input', function (e) {
    if (!e.target.classList.contains('units')) return;

    const row = e.target.closest('tr');
    const units = Number(e.target.value || 0);
    const pack = Number(row.querySelector('.pack-size').value || 0);

    row.querySelector('.total-qty').value = units * pack;

    const cartonsPerPallet = Number(row.querySelector('.pallets-per-packing').value || 0);
    const palletsInput = row.querySelector('.pallets-used');
    if (cartonsPerPallet > 0 && units > 0) {
        palletsInput.value = Math.ceil(units / cartonsPerPallet);
    }
});

/* ================= SHOW UNIFIED PALLET MODAL ================= */
function openUnifiedPalletModal(row) {
    const productId = row.querySelector('.product-id').value;
    if (!productId) {
        Swal.fire('Info', 'Please select a product first.', 'info');
        return;
    }

    const whId = row.querySelector('.warehouse-select').value;
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
    modalWh.value = whId === 'auto' ? '' : whId;

    const rowSelect = document.querySelector('.modal-manual-row');
    rowSelect.innerHTML = '<option value="">-- Select Row --</option>';
    const activeWhId = modalWh.value;
    if (activeWhId) {
        const wh = warehouses.find(w => w.id == activeWhId);
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

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.preview-pallets-btn');
    if (btn) {
        openUnifiedPalletModal(btn.closest('tr'));
    }
});

function fetchPreviewAndRender() {
    if (!activeRow) return;

    const tableRows = document.querySelectorAll('#itemsTable tbody tr');
    const items = [];
    let activeRowIndex = 0;

    tableRows.forEach((row, idx) => {
        if (row === activeRow) {
            activeRowIndex = idx;
        }

        const pId = row.querySelector('.product-id').value;
        const uVal = row.querySelector('.units').value || 0;
        const wVal = row.querySelector('.warehouse-select').value;
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

    const isManual = document.getElementById('mode_manual').checked;
    const manualWhId = document.querySelector('.modal-manual-warehouse').value;
    const manualRowId = document.querySelector('.modal-manual-row').value;
    const manualPalletStart = document.querySelector('.modal-manual-pallet-start').value;

    if (isManual) {
        items[activeRowIndex].warehouse_id = manualWhId;
        items[activeRowIndex].warehouse_row_id = manualRowId;
        items[activeRowIndex].pallet_start = manualPalletStart;
    }

    const rangeInfoDiv = document.querySelector('.manual-range-info');
    rangeInfoDiv.classList.add('d-none');
    rangeInfoDiv.innerHTML = '';

    const activeItem = items[activeRowIndex];
    if (!activeItem.product_id) {
        document.getElementById('pallet-preview-summary').innerHTML = '<div class="alert alert-info py-2 mb-0">Please select a product first.</div>';
        document.getElementById('modal-pallet-grid').innerHTML = '<div class="text-muted small p-3 text-center w-100">Select a product first.</div>';
        return;
    }

    if (isManual && !activeItem.warehouse_id) {
        document.getElementById('pallet-preview-summary').innerHTML = '<div class="alert alert-warning py-2 mb-0">Please select a specific warehouse to use manual override.</div>';
        document.getElementById('modal-pallet-grid').innerHTML = '<div class="text-muted small p-3">Please select a specific warehouse.</div>';
        return;
    }

    const requestData = {
        items: items,
        active_row_index: activeRowIndex,
        _token: '{{ csrf_token() }}'
    };

    $.post('/opening-stock/preview-pallets', requestData, function(response) {
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

                if (!firstAssignedRowId && alloc.type !== 'partial') {
                    if (isManual) {
                        firstAssignedRowId = manualRowId;
                    } else {
                        const whObj = warehouses.find(w => w.name === alloc.warehouse_name);
                        if (whObj && whObj.rows) {
                            const rowObj = whObj.rows.find(r => r.row_name === alloc.row_name);
                            if (rowObj) firstAssignedRowId = rowObj.id;
                        }
                    }
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

        const gridRowId = isManual ? manualRowId : firstAssignedRowId;
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

            gridContainer.appendChild(box);
        });

        const warningDiv = document.querySelector('.manual-warnings');
        if (warningDiv) {
            if (occupiedProposed.length > 0) {
                warningDiv.innerHTML = `⚠️ Conflict: Pallet(s) ${occupiedProposed.join(', ')} already occupied! Please select another start position.`;
            } else {
                warningDiv.innerHTML = '';
            }
        }
    });
}

/* ================= MANUAL PALLET ALLOCATION MODAL EVENTS ================= */
document.querySelectorAll('input[name="allocation_mode"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const manualWh = document.querySelector('.modal-manual-warehouse');
        const manualRow = document.querySelector('.modal-manual-row');
        const startPallet = document.querySelector('.modal-manual-pallet-start');

        if (this.value === 'manual') {
            document.querySelector('.manual-controls-section').classList.remove('d-none');
            if (activeRow) {
                const whVal = activeRow.querySelector('.warehouse-select').value;
                manualWh.value = whVal === 'auto' ? '' : whVal;
                manualWh.dispatchEvent(new Event('change'));
            }
        } else {
            document.querySelector('.manual-controls-section').classList.add('d-none');
            manualWh.value = '';
            manualRow.innerHTML = '<option value="">-- Select Row --</option>';
            startPallet.value = '';
            fetchPreviewAndRender();
        }
    });
});

document.querySelector('.modal-manual-warehouse').addEventListener('change', function () {
    const selectedWhId = this.value;
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
    rowSelect.value = '';
    document.querySelector('.modal-manual-pallet-start').value = '';
    fetchPreviewAndRender();
});

document.querySelector('.modal-manual-row').addEventListener('change', function () {
    document.querySelector('.modal-manual-pallet-start').value = '';
    fetchPreviewAndRender();
});

document.querySelector('.modal-manual-pallet-start').addEventListener('input', function () {
    fetchPreviewAndRender();
});

/* ================= MANUAL PALLET SAVE ================= */
document.getElementById('saveManualPalletBtn').addEventListener('click', function () {
    if (!activeRow) return;

    const isManual = document.getElementById('mode_manual').checked;
    const manualWhId = document.querySelector('.modal-manual-warehouse').value;
    const rowId = document.querySelector('.modal-manual-row').value;
    const palletStart = document.querySelector('.modal-manual-pallet-start').value;

    if (isManual && manualWhId && rowId) {
        activeRow.querySelector('.warehouse-select').value = manualWhId;

        const rowText = document.querySelector('.modal-manual-row option:checked').text;
        activeRow.querySelector('.manual-row-id').value = rowId;
        activeRow.querySelector('.manual-pallet-start').value = palletStart;
        activeRow.querySelector('.manual-pallet-info').innerHTML = `Row: ${rowText.split(' (')[0]}, Start: ${palletStart || 'Auto'}`;
    } else {
        activeRow.querySelector('.manual-row-id').value = '';
        activeRow.querySelector('.manual-pallet-start').value = '';
        activeRow.querySelector('.manual-pallet-info').innerHTML = '';
    }

    bootstrap.Modal.getInstance(document.getElementById('palletManualModal')).hide();
});

/* ================= MANUAL PALLET CLEAR ================= */
document.getElementById('clearManualPalletBtn').addEventListener('click', function () {
    if (!activeRow) return;

    activeRow.querySelector('.manual-row-id').value = '';
    activeRow.querySelector('.manual-pallet-start').value = '';
    activeRow.querySelector('.manual-pallet-info').innerHTML = '';
    
    document.querySelector('.modal-manual-warehouse').value = '';
    document.querySelector('.modal-manual-row').innerHTML = '<option value="">-- Select Row --</option>';
    document.querySelector('.modal-manual-pallet-start').value = '';

    document.getElementById('mode_auto').checked = true;
    document.querySelector('.manual-controls-section').classList.add('d-none');

    bootstrap.Modal.getInstance(document.getElementById('palletManualModal')).hide();
});

/* ================= REMOVE ROW ================= */
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('removeRow')) {
        const row = e.target.closest('tr');
        row.remove();
    }
});

// Load original grouped items
if (groupedItems && groupedItems.length > 0) {
    groupedItems.forEach(item => {
        addRow(item);
    });
} else {
    // If empty, add 3 default rows
    for (let i = 0; i < 3; i++) {
        addRow();
    }
}

});
</script>
@endpush
