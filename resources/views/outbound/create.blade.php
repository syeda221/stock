@extends('layouts.app')

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    
@endif

<style>
    /* Premium style system */
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
    /* Fixed scrollable table container */
    .table-responsive-outbound {
        max-height: 270px;
        overflow-y: auto;
    }
    .table-responsive-outbound th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 10;
        box-shadow: inset 0 -1px 0 #dee2e6;
    }
    /* Autocomplete floating dropdown styling */
    #global-product-autocomplete {
        position: fixed;
        background: white;
        border: 1px solid #ccc;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 999999;
        display: none;
        max-height: 250px;
        overflow-y: auto;
        border-radius: 6px;
    }
    #global-product-autocomplete .autocomplete-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }
    #global-product-autocomplete .autocomplete-item:hover {
        background-color: #0d6efd;
        color: white;
    }
</style>

<form method="POST" action="{{ route('outbound.store') }}" id="outboundForm">
@csrf

{{-- ================= HEADER ================= --}}
<div class="card shadow-sm mb-3">
    <div class="card-header py-2">
        <strong class="text-primary">Outbound Dispatch Entry</strong>
    </div>

    <div class="card-body py-3">
        <div class="row g-2">
            {{-- OUTBOUND TYPE --}}
            <div class="col-md-2">
                <label class="form-label font-weight-bold">Outbound Type</label>
                <select name="outbound_type" id="outboundType" class="form-select form-select-sm" required>
                    <option value="">Select Type</option>
                    <option value="customer" selected>Customer Sale</option>
                    <option value="warehouse">Warehouse Transfer</option>
                </select>
            </div>

            {{-- CUSTOMER --}}
            <div class="col-md-3" id="customerBox">
                <label class="form-label font-weight-bold">Customer</label>
                <select name="customer_id" id="customer_id" class="form-select form-select-sm" required>
                    <option value="">Select Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- TO WAREHOUSE --}}
            <div class="col-md-3 d-none" id="toWarehouseBox">
                <label class="form-label font-weight-bold">To Warehouse</label>
                <select name="to_warehouse_id" class="form-select form-select-sm">
                    <option value="">Select Target Warehouse</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- TRANSPORTER --}}
            <div class="col-md-2">
                <label class="form-label font-weight-bold">Transporter</label>
                <select name="transporter_id" class="form-select form-select-sm">
                    <option value="">Optional</option>
                    @foreach($transporters as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label font-weight-bold">Shipment Type</label>
                <select name="shipment_type" class="form-select form-select-sm">
                    <option value="manual">Manual</option>
                    <option value="auto">Auto</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label font-weight-bold">Dispatched Invoice No</label>
                <input name="dispatched_invoice_no" class="form-control form-control-sm text-primary font-weight-bold"
                       value="{{ old('dispatched_invoice_no', $nextDispatchedInvoiceNo ?? '') }}" readonly>
            </div>

            <div class="col-md-2">
                <label class="form-label font-weight-bold">Dispatcher</label>
                <input name="dispatcher_sig" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label font-weight-bold">Picker</label>
                <input name="picker" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label font-weight-bold">Vehicle No</label>
                <input name="vehicle_no" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label font-weight-bold">Vehicle Size</label>
                <input name="vehicle_size" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label font-weight-bold">Driver Name</label>
                <input name="driver_name" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label font-weight-bold">Driver Mobile</label>
                <input name="driver_mobile" class="form-control form-control-sm">
            </div>

            <div class="col-md-3">
                <label class="form-label font-weight-bold">Vehicle In Time</label>
                <input type="datetime-local" name="vehicle_in_time" class="form-control form-control-sm">
            </div>

            <div class="col-md-3">
                <label class="form-label font-weight-bold">Vehicle Out Time</label>
                <input type="datetime-local" name="vehicle_out_time" class="form-control form-control-sm">
            </div>

            <div class="col-md-6">
                <label class="form-label font-weight-bold">DA #</label>
                <input name="da_no" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
                <label class="form-label font-weight-bold">Remarks / Gatepass Notes</label>
                <input name="remarks" class="form-control form-control-sm">
            </div>
        </div>
    </div>
</div>

{{-- ================= ITEMS ================= --}}
<div class="card shadow-sm mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong class="text-primary">Dispatch Items List</strong>
        <button type="button" id="addRowBtn" class="btn btn-sm btn-success px-3">
            + Add Row
        </button>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive table-responsive-outbound">
            <table class="table table-sm table-bordered mb-0" id="itemsTable">
                <thead class="table-light text-center">
                    <tr>
                        <th width="240">Product Search</th>
                        <th width="160">Source Warehouse</th>
                        <th width="120">PO #</th>
                        <th width="120">IBD #</th>
                        <th class="text-end" width="90">Avail</th>
                        <th class="text-end" width="70">Pack</th>
                        <th class="text-end" width="95">Units</th>
                        <th class="text-end" width="100">STO #</th>
                        <th class="text-end" width="95">Qty</th>
                        <th class="text-center" width="160">Locations / Pallets</th>
                        <th width="35"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-5">
    <div>
        <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Outbound & Dispatch</button>
        <a href="{{ route('outbound.index') }}" class="btn btn-secondary px-3">Cancel</a>
    </div>
</div>
</form>

{{-- ================= POPUP FLOATING AUTOCOMPLETE ================= --}}
<div id="global-product-autocomplete"></div>

{{-- ================= PICK DETAILS MODAL ================= --}}
<div class="modal fade" id="pickModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title font-weight-bold text-white">Outbound Allocation & Pallet Preview</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    {{-- Left Pane: Allocation summary --}}
                    <div class="col-md-6 border-end">
                        <h6 class="border-bottom pb-2 text-primary font-weight-bold">FIFO Proposed Locations</h6>
                        <div id="pallet-preview-summary" class="mb-3" style="max-height: 280px; overflow-y: auto;">
                            <div class="text-muted small">No product or units specified.</div>
                        </div>

                        {{-- Manual Override Section --}}
                        <div class="card bg-light border-0 p-3 mt-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="modalOverrideCheck">
                                <label class="form-check-label font-weight-bold" for="modalOverrideCheck">
                                    Manual Override Location
                                </label>
                            </div>
                            <div id="modalOverrideInputs" class="d-none">
                                <label class="form-label small">Target Row/Location</label>
                                <select id="modalOverrideRow" class="form-select form-select-sm">
                                    <option value="">Select Specific Row</option>
                                </select>
                                <div class="manual-range-info mt-2 text-primary small font-weight-bold"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Pane: Pallet Grid layout visualization --}}
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 text-primary font-weight-bold">Row Layout Grid</h6>
                        <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                            <div><span class="badge bg-secondary me-1">&nbsp;</span> Occupied</div>
                            <div><span class="badge bg-success me-1">&nbsp;</span> Pick Highlight</div>
                            <div><span class="badge bg-light text-dark border me-1">&nbsp;</span> Free</div>
                        </div>
                        <div id="pallet-grid-visualizer" class="p-2 border rounded bg-white text-center" style="min-height: 180px; max-height: 280px; overflow-y: auto;">
                            <p class="text-muted pt-5">Select a warehouse row to view layout</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-primary btn-sm px-4" id="modalApplyBtn">Apply & Close</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let rowIndex = 0;
let activeRow = null;
let currentActiveRowIndexForModal = null;

// Populate product data
const products = @json($products);

// Toggle Customer or Warehouse box
document.getElementById('outboundType').addEventListener('change', function () {
    const customerBox = document.getElementById('customerBox');
    const toWarehouseBox = document.getElementById('toWarehouseBox');
    const customerSelect = document.getElementById('customer_id');

    customerBox.classList.add('d-none');
    toWarehouseBox.classList.add('d-none');
    customerSelect.removeAttribute('required');

    if (this.value === 'customer') {
        customerBox.classList.remove('d-none');
        customerSelect.setAttribute('required', 'required');
    } else if (this.value === 'warehouse') {
        toWarehouseBox.classList.remove('d-none');
    }
});

// Add a table row
function addRow() {
    const tableBody = document.querySelector('#itemsTable tbody');
    tableBody.insertAdjacentHTML('beforeend', `
    <tr data-row-idx="${rowIndex}">
        <td>
            <div class="position-relative product-search-container">
                <input type="text" class="form-control form-control-sm product-search font-weight-bold" placeholder="Search product..." autocomplete="off">
            </div>
            <input type="hidden" name="items[${rowIndex}][product_id]" class="selected-product-id">
        </td>
        <td>
            <select name="items[${rowIndex}][warehouse_id]" class="form-select form-select-sm warehouse-select">
                <option value="">Select Warehouse</option>
            </select>
        </td>
        <td><input name="items[${rowIndex}][po_no]" class="form-control form-control-sm po_no"></td>
        <td><input name="items[${rowIndex}][ibd_no]" class="form-control form-control-sm ibd_no"></td>
        <td><input class="form-control form-control-sm text-end avail bg-light" readonly></td>
        <td><input class="form-control form-control-sm text-end pack bg-light" readonly></td>
        <td>
            <input type="number" min="1" name="items[${rowIndex}][units_dispatch]" class="form-control form-control-sm text-end units">
        </td>
        <td><input name="items[${rowIndex}][sto_no]" class="form-control form-control-sm text-end sto_no"></td>
        <td><input class="form-control form-control-sm text-end qty bg-light" readonly></td>
        <td>
            <div class="d-flex align-items-center justify-content-between">
                <span class="location-preview-text text-muted small text-truncate">FIFO</span>
                <button type="button" class="btn btn-sm btn-link py-0 px-1 btn-view-pallet-modal" title="View Allocation & Override">
                    <i class="bi bi-eye-fill text-primary" style="font-size: 16px;"></i>
                </button>
            </div>
            <input type="hidden" name="items[${rowIndex}][warehouse_row_id]" class="manual-warehouse-row-id">
            <input type="hidden" class="pallets-per-packing">
            <input type="hidden" name="items[${rowIndex}][pallets_returned]" class="pallets-returned" value="0">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger del px-2 py-0">×</button>
        </td>
    </tr>
    `);
    rowIndex++;
}

document.getElementById('addRowBtn').addEventListener('click', addRow);

// Initialize 5 rows by default
for (let i = 0; i < 5; i++) {
    addRow();
}

// Global fixed autocomplete list functionality
const autocompleteList = document.getElementById('global-product-autocomplete');
let activeInput = null;

document.addEventListener('click', function(e) {
    if (activeInput && !activeInput.contains(e.target) && !autocompleteList.contains(e.target)) {
        autocompleteList.style.display = 'none';
        activeInput = null;
    }
});

document.addEventListener('focusin', function(e) {
    if (!e.target.classList.contains('product-search')) return;
    showAutocomplete(e.target);
});

document.addEventListener('input', function(e) {
    if (!e.target.classList.contains('product-search')) return;
    filterAutocomplete(e.target);
});

function showAutocomplete(input) {
    activeInput = input;
    const rect = input.getBoundingClientRect();
    autocompleteList.style.top = rect.bottom + 'px';
    autocompleteList.style.left = rect.left + 'px';
    autocompleteList.style.width = rect.width + 'px';
    autocompleteList.style.display = 'block';

    const q = input.value.trim().toLowerCase();
    renderAutocompleteItems(q);
}

function filterAutocomplete(input) {
    const q = input.value.trim().toLowerCase();
    renderAutocompleteItems(q);
}

function renderAutocompleteItems(query) {
    let filtered = products;
    if (query.length > 0) {
        filtered = products.filter(p => p.name.toLowerCase().includes(query) || p.item_code.toLowerCase().includes(query));
    }

    if (filtered.length === 0) {
        autocompleteList.innerHTML = '<div class="p-2 text-muted">No items matched</div>';
        return;
    }

    autocompleteList.innerHTML = filtered.slice(0, 30).map(p => `
        <div class="autocomplete-item" data-id="${p.id}" data-pack="${p.pack_size}" data-cartons="${p.cartons_per_pallet || 0}" data-code="${p.item_code}" data-name="${p.name}">
            <strong>${p.item_code}</strong> - ${p.name} <span class="badge bg-light text-dark float-end">Pack: ${p.pack_size}</span>
        </div>
    `).join('');
}

// Select item from dropdown
autocompleteList.addEventListener('click', function(e) {
    const item = e.target.closest('.autocomplete-item');
    if (!item || !activeInput) return;

    const row = activeInput.closest('tr');
    const pId = item.dataset.id;
    const pack = item.dataset.pack;
    const code = item.dataset.code;
    const name = item.dataset.name;
    const cPerPallet = item.dataset.cartons;

    activeInput.value = `${code} - ${name}`;
    row.querySelector('.selected-product-id').value = pId;
    row.querySelector('.pack').value = pack;
    row.querySelector('.pallets-per-packing').value = cPerPallet;

    autocompleteList.style.display = 'none';
    activeInput = null;

    // Load available warehouses for this product
    const warehouseSelect = row.querySelector('.warehouse-select');
    warehouseSelect.innerHTML = '<option value="">Loading Stock...</option>';

    const outboundType = document.getElementById('outboundType')?.value || '';
    const otParam = outboundType ? `&outbound_type=${outboundType}` : '';
    fetch(`/outbound/product-stock/${pId}?t=${new Date().getTime()}${otParam}`)
        .then(r => r.json())
        .then(data => {
            if (data.length > 0) {
                let totalAllStock = 0;
                data.forEach(wh => {
                    totalAllStock += parseFloat(wh.total_stock || 0);
                });
                const totalStockStr = totalAllStock.toFixed(2);
                let options = `<option value="auto" data-stock="${totalStockStr}" data-pack="${pack}" selected>Auto Assign (FIFO) (Avail: ${totalStockStr} Qty)</option>`;
                data.forEach(wh => {
                    const whStockStr = parseFloat(wh.total_stock || 0).toFixed(2);
                    options += `<option value="${wh.warehouse_id}"
                                      data-stock="${whStockStr}"
                                      data-pack="${pack}">
                                    ${wh.warehouse} (Avail: ${whStockStr} Qty)
                                </option>`;
                });
                warehouseSelect.innerHTML = options;
                warehouseSelect.value = "auto";
                warehouseSelect.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                warehouseSelect.innerHTML = '<option value="">No Stock Available</option>';
            }
        })
        .catch(() => {
            warehouseSelect.innerHTML = '<option value="">Error loading stock</option>';
        });
});

// Update stock fields when Warehouse is selected in Row
document.addEventListener('change', e => {
    if (!e.target.classList.contains('warehouse-select')) return;

    const row = e.target.closest('tr');
    const option = e.target.options[e.target.selectedIndex];

    if (option) {
        row.querySelector('.avail').value = option.getAttribute('data-stock') || '';
        row.querySelector('.pack').value = option.getAttribute('data-pack') || '';
        calculateQty(row);
    } else {
        row.querySelector('.avail').value = '';
        row.querySelector('.pack').value = '';
    }
});

// Units and quantity calculation
document.addEventListener('input', e => {
    if (!e.target.classList.contains('units')) return;
    const row = e.target.closest('tr');
    calculateQty(row);
});

function calculateQty(row) {
    const pack = parseFloat(row.querySelector('.pack').value || 0);
    const units = parseFloat(row.querySelector('.units').value || 0);
    const avail = parseFloat(row.querySelector('.avail').value || 0);
    const qty = pack * units;

    row.querySelector('.qty').value = qty.toFixed(2);

    // Auto calculate pallets count
    const cpp = parseFloat(row.querySelector('.pallets-per-packing').value || 0);
    if (cpp > 0 && units > 0) {
        row.querySelector('.pallets-returned').value = Math.ceil(units / cpp);
    }

    if (units > avail) {
        row.querySelector('.units').classList.add('is-invalid');
    } else {
        row.querySelector('.units').classList.remove('is-invalid');
    }
}

// Excel style Enter key navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const target = e.target;
        if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
            e.preventDefault();

            const currentCell = target.closest('td');
            const currentRow = target.closest('tr');
            if (!currentCell || !currentRow) return;

            const colIndex = Array.from(currentRow.children).indexOf(currentCell);
            const nextRow = currentRow.nextElementSibling;

            if (nextRow) {
                const nextInput = nextRow.children[colIndex].querySelector('input:not([readonly]), select');
                if (nextInput) nextInput.focus();
            } else {
                // If it is the last row, automatically add a new row and focus it
                addRow();
                setTimeout(() => {
                    const allRows = document.querySelectorAll('#itemsTable tbody tr');
                    const newRow = allRows[allRows.length - 1];
                    const nextInput = newRow.children[colIndex].querySelector('input:not([readonly]), select');
                    if (nextInput) nextInput.focus();
                }, 100);
            }
        }
    }
});

// View Pick Details / Override Modal trigger
document.addEventListener('click', e => {
    const eyeBtn = e.target.closest('.btn-view-pallet-modal');
    if (!eyeBtn) return;

    const row = eyeBtn.closest('tr');
    currentActiveRowIndexForModal = parseInt(row.getAttribute('data-row-idx'));

    const productId = row.querySelector('.selected-product-id').value;
    const warehouseId = row.querySelector('.warehouse-select').value;
    const units = parseInt(row.querySelector('.units').value || 0);

    if (!productId || !warehouseId || units <= 0) {
        Swal.fire('Info', 'Please select Product, Warehouse, and enter valid Units to preview picks.', 'info');
        return;
    }

    // Set up modal state
    const overrideCheck = document.getElementById('modalOverrideCheck');
    const overrideInputs = document.getElementById('modalOverrideInputs');
    const manualRowSelect = document.getElementById('modalOverrideRow');
    const currentManualRowId = row.querySelector('.manual-warehouse-row-id').value;

    if (currentManualRowId) {
        overrideCheck.checked = true;
        overrideInputs.classList.remove('d-none');
    } else {
        overrideCheck.checked = false;
        overrideInputs.classList.add('d-none');
    }

    // Load available rows/locations - if auto warehouse, fetch all stock without warehouse filter
    const rowFetchUrl = (warehouseId && warehouseId !== 'auto')
        ? `/outbound/product-stock/${productId}?warehouse_id=${warehouseId}&t=${new Date().getTime()}`
        : `/outbound/product-stock/${productId}?t=${new Date().getTime()}`;

    manualRowSelect.innerHTML = '<option value="">Loading rows...</option>';
    fetch(rowFetchUrl)
        .then(r => r.json())
        .then(data => {
            if (data.length > 0) {
                const rowMap = new Map();
                data.forEach(wh => {
                    if (wh.batches) {
                        wh.batches.forEach(b => {
                            // Use composite key: row_id to deduplicate
                            if (b.row_id != null && b.row_name) {
                                const key = b.row_id;
                                if (!rowMap.has(key)) {
                                    const label = `${wh.warehouse} — ${b.row_name}`;
                                    rowMap.set(key, label);
                                }
                            }
                        });
                    }
                });

                let options = '<option value="">Select Specific Row</option>';
                if (rowMap.size === 0) {
                    options += '<option value="" disabled>No rows found for this product</option>';
                }
                rowMap.forEach((rowLabel, rowId) => {
                    options += `<option value="${rowId}" ${currentManualRowId == rowId ? 'selected' : ''}>${rowLabel}</option>`;
                });
                manualRowSelect.innerHTML = options;
            } else {
                manualRowSelect.innerHTML = '<option value="">No stock available</option>';
            }
        });

    // Run preview calculation
    fetchPreviewPicks();

    // Show modal
    const myModal = new bootstrap.Modal(document.getElementById('pickModal'));
    myModal.show();
});

// Listen to override checkbox change
document.getElementById('modalOverrideCheck').addEventListener('change', function() {
    const overrideInputs = document.getElementById('modalOverrideInputs');
    if (this.checked) {
        overrideInputs.classList.remove('d-none');
    } else {
        overrideInputs.classList.add('d-none');
        document.getElementById('modalOverrideRow').value = '';
        fetchPreviewPicks();
    }
});

document.getElementById('modalOverrideRow').addEventListener('change', function() {
    fetchPreviewPicks();
});

function fetchPreviewPicks() {
    // Build entire request items state to simulate sequentially
    const itemsData = [];
    document.querySelectorAll('#itemsTable tbody tr').forEach(tr => {
        const idx = parseInt(tr.getAttribute('data-row-idx'));
        const isCurrent = idx === currentActiveRowIndexForModal;

        let overrideRowId = tr.querySelector('.manual-warehouse-row-id').value;
        if (isCurrent && document.getElementById('modalOverrideCheck').checked) {
            overrideRowId = document.getElementById('modalOverrideRow').value;
        }

        itemsData.push({
            product_id: tr.querySelector('.selected-product-id').value,
            warehouse_id: tr.querySelector('.warehouse-select').value,
            units_dispatch: tr.querySelector('.units').value || 0,
            warehouse_row_id: overrideRowId, // will prioritize this batch/row
        });
    });

    const outboundType = document.getElementById('outboundType').value || 'customer';

    fetch('/outbound/preview-picks', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            items: itemsData,
            active_row_index: currentActiveRowIndexForModal,
            outbound_type: outboundType
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.allocations) {
            renderModalAllocations(res.allocations);
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getPalletDisplayName(rowName, palletNumber) {
    if (!rowName) return 'Pallet ' + palletNumber;
    const parts = rowName.split(/\s+to\s+/i);
    const firstPallet = parts[0];
    const match = firstPallet.match(/^(.*?)(\d+)$/);
    if (match) {
        const prefix = match[1];
        const startNum = parseInt(match[2], 10);
        const digits = match[2].length;
        const actualNum = startNum + palletNumber - 1;
        return prefix + String(actualNum).padStart(digits, '0');
    }
    return rowName + ' - P' + palletNumber;
}

function loadOutboundPalletGrid(rowId, rowName, pickedPalletNames) {
    const gridDiv = document.getElementById('pallet-grid-visualizer');
    gridDiv.innerHTML = '<div class="text-center w-100 py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading pallet grid...</div>';

    fetch('/warehouses/rows/' + rowId + '/pallets')
        .then(r => r.json())
        .then(data => {
            gridDiv.innerHTML = '';
            let gridHtml = '<div class="d-flex flex-wrap gap-2 justify-content-center">';
            
            data.pallets.forEach(pallet => {
                const displayName = getPalletDisplayName(rowName, pallet.pallet_number);
                const isPicked = pickedPalletNames.includes(displayName);
                
                if (isPicked) {
                    gridHtml += `
                        <div class="border border-success rounded p-2 text-center text-white shadow-sm d-flex flex-column justify-content-center align-items-center" style="width: 105px; min-height: 75px; background-color: #198754; font-size: 11px; font-weight: bold; position: relative;">
                            <div class="fw-bold" style="font-size:12px;">${displayName}</div>
                            <div class="text-white-50 small mt-1" style="font-size:9.5px; font-weight: normal; opacity: 0.95;">[ PICK ]</div>
                        </div>
                    `;
                } else if (!pallet.is_empty) {
                    gridHtml += `
                        <div class="border border-danger-subtle rounded p-2 text-center shadow-sm d-flex flex-column justify-content-center align-items-center" style="width: 105px; min-height: 75px; background-color: #f8d7da; border-color: #f5c2c7; font-size: 11px; position: relative;">
                            <div class="fw-bold text-danger" style="font-size:11px;">${displayName}</div>
                            <div class="text-truncate text-muted fw-semibold" style="font-size:9px; margin-top: 2px; max-width: 90px;" title="${pallet.product_name || ''}">${pallet.product_name || '[ Occupied ]'}</div>
                            <div class="text-muted" style="font-size:9px;">${pallet.carton_qty ? 'Qty: ' + pallet.carton_qty : '[ Occupied ]'}</div>
                        </div>
                    `;
                } else {
                    gridHtml += `
                        <div class="border border-success-subtle rounded p-2 text-center shadow-sm d-flex flex-column justify-content-center align-items-center" style="width: 105px; min-height: 75px; background-color: #d1e7dd; border-color: #a3cfbb; font-size: 11px; position: relative;">
                            <div class="fw-bold text-success" style="font-size:11px;">${displayName}</div>
                            <div class="text-muted" style="font-size:10px; margin-top: 4px;">[ Empty ]</div>
                        </div>
                    `;
                }
            });
            
            gridHtml += '</div>';
            gridDiv.innerHTML = gridHtml;
        })
        .catch(err => {
            gridDiv.innerHTML = '<div class="text-danger small p-3">Failed to load row layout grid.</div>';
        });
}

function renderModalAllocations(allocations) {
    const summaryDiv = document.getElementById('pallet-preview-summary');
    const gridDiv = document.getElementById('pallet-grid-visualizer');

    if (allocations.length === 0) {
        summaryDiv.innerHTML = '<div class="text-warning small">No stock matched. Verify units and availability.</div>';
        gridDiv.innerHTML = '<p class="text-muted pt-5">Grid unavailable</p>';
        return;
    }

    let summaryHtml = '';

    allocations.forEach((alloc, index) => {
        const isFirst = index === 0;
        const activeClass = isFirst ? 'border-primary bg-primary-subtle' : 'bg-white';
        const clickStyle = alloc.row_id ? 'cursor: pointer; transition: all 0.2s;' : '';
        const clickClass = alloc.row_id ? `allocation-item-card p-2 mb-2 rounded border hover-shadow ${activeClass}` : 'p-2 mb-2 rounded border bg-light';
        const rowIdAttr = alloc.row_id ? `data-row-id="${alloc.row_id}"` : '';
        const rowNameAttr = alloc.row_name ? `data-row-name="${alloc.row_name}"` : '';
        const palletNamesAttr = alloc.pallet_names ? `data-pallet-names="${escapeHtml(JSON.stringify(alloc.pallet_names))}"` : '';

        // Build expiry indicators
        let expiryBadges = '';
        if (alloc.has_near_expiry) {
            expiryBadges += `<span class="badge bg-danger text-white me-1" style="font-size: 10px;"><i class="bi bi-clock-history"></i> Near Expiry</span>`;
        }
        if (alloc.has_long_expiry) {
            expiryBadges += `<span class="badge bg-success text-white" style="font-size: 10px;"><i class="bi bi-shield-check"></i> Long Expiry</span>`;
        }

        summaryHtml += `
            <div class="${clickClass}" ${rowIdAttr} ${rowNameAttr} ${palletNamesAttr} style="${clickStyle}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark" style="font-size:12px;">Warehouse: ${alloc.warehouse_name}</span>
                    <span class="badge bg-primary">${alloc.units} Units</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:12px; color:#1e293b;">
                    <div><strong>Row/Location:</strong> ${alloc.row_name}</div>
                    <div>${expiryBadges}</div>
                </div>
                <div class="text-muted small mt-1" style="font-size:11px;">
                    <strong>Pallets:</strong> ${alloc.pallet_names.join(', ')}
                </div>
                ${alloc.row_id ? '<div class="text-end mt-1"><span class="badge bg-primary-subtle text-primary" style="font-size: 9px;"><i class="bi bi-grid-3x3"></i> Click to View Grid</span></div>' : ''}
            </div>
        `;
    });

    summaryDiv.innerHTML = summaryHtml;

    // Load visual grid for the first allocation
    const firstAlloc = allocations[0];
    if (firstAlloc && firstAlloc.row_id) {
        loadOutboundPalletGrid(firstAlloc.row_id, firstAlloc.row_name, firstAlloc.pallet_names);
    } else {
        gridDiv.innerHTML = '<p class="text-muted pt-5">Grid unavailable</p>';
    }
}

document.addEventListener('click', function(e) {
    const card = e.target.closest('.allocation-item-card');
    if (card) {
        const rowId = card.dataset.rowId;
        const rowName = card.dataset.rowName;
        const palletNames = JSON.parse(card.dataset.palletNames || '[]');
        
        document.querySelectorAll('.allocation-item-card').forEach(c => {
            c.classList.remove('border-primary', 'bg-primary-subtle');
            c.classList.add('bg-white');
        });
        card.classList.remove('bg-white');
        card.classList.add('border-primary', 'bg-primary-subtle');
        
        loadOutboundPalletGrid(rowId, rowName, palletNames);
    }
});

// Apply modal decisions back to the form row
document.getElementById('modalApplyBtn').addEventListener('click', function() {
    const tr = document.querySelector(`#itemsTable tbody tr[data-row-idx="${currentActiveRowIndexForModal}"]`);
    if (!tr) return;

    const overrideCheck = document.getElementById('modalOverrideCheck');
    const overrideRow = document.getElementById('modalOverrideRow');
    const previewText = tr.querySelector('.location-preview-text');
    const manualRowInput = tr.querySelector('.manual-warehouse-row-id');

    if (overrideCheck.checked && overrideRow.value) {
        manualRowInput.value = overrideRow.value;
        const rowText = overrideRow.options[overrideRow.selectedIndex].text;
        previewText.textContent = `Manual [${rowText}]`;
    } else {
        manualRowInput.value = '';
        previewText.textContent = 'FIFO';
    }

    // Hide modal
    bootstrap.Modal.getInstance(document.getElementById('pickModal')).hide();
});

// Remove Row functionality
document.addEventListener('click', e => {
    if (!e.target.classList.contains('del')) return;
    const row = e.target.closest('tr');
    if (row) row.remove();
});

// Form submission wrapper with AJAX and Swal notifications
document.getElementById('outboundForm').addEventListener('submit', function(e) {
    e.preventDefault();

    let hasError = false;
    let hasValidRow = false;

    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        const prodId = row.querySelector('.selected-product-id');
        const warehouseId = row.querySelector('.warehouse-select');

        if (!prodId || !prodId.value) return;

        hasValidRow = true;

        if (!warehouseId.value) {
            hasError = true;
            warehouseId.classList.add('is-invalid');
        }

        const units = parseFloat(row.querySelector('.units').value || 0);
        const avail = parseFloat(row.querySelector('.avail').value || 0);

        if (units > avail) {
            hasError = true;
            row.querySelector('.units').classList.add('is-invalid');
        }
    });

    if (hasError || !hasValidRow) {
        Swal.fire('Error', 'Please resolve validation errors and ensure at least one row contains a valid product and warehouse.', 'error');
        return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Saving Outbound...';

    const formData = new FormData(this);

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
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Outbound & Dispatch';

        if (res.status === 200 || res.status === 201 || res.body.success) {
            Swal.fire('Success', 'Outbound dispatch transaction saved successfully!', 'success').then(() => {
                window.location.href = res.body.redirect || '{{ route("outbound.index") }}';
            });
        } else {
            let errorMsg = res.body.message || 'Validation Error';
            if (res.body.errors) {
                errorMsg = Object.values(res.body.errors).flat().join('<br>');
            }
            Swal.fire({ icon: 'error', title: 'Error Saving Outbound', html: errorMsg });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Outbound & Dispatch';
        Swal.fire('Error', 'A network connection error occurred.', 'error');
    });
});
</script>
@endpush
