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
.product-result-item:hover {
    background-color: #0d6efd;
    color: white;
}
.product-result-item strong {
    font-weight: 600;
}
.allocation-item-card:hover {
    border-color: #0d6efd !important;
    box-shadow: 0 4px 8px rgba(0,0,0,0.08);
}
</style>

<form method="POST" action="{{ route('outbound.update', $stockOut) }}" id="outboundForm">
@csrf
@method('PUT')

{{-- ================= HEADER ================= --}}
<div class="card shadow-sm mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong>Edit Outbound Dispatch #{{ $stockOut->dispatched_invoice_no ?: ('#OUT-'.$stockOut->id) }}</strong>
        <span class="badge bg-primary text-uppercase">{{ $stockOut->source_type }}</span>
    </div>

    <div class="card-body">
        <div class="row g-2">

            {{-- OUTBOUND TYPE --}}
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Outbound Type</label>
                <select name="outbound_type" id="outboundType"
                        class="form-select form-select-sm" required {{ $stockOut->source_type === 'transfer' ? 'disabled' : '' }}>
                    <option value="">Select</option>
                    <option value="customer" {{ $stockOut->source_type === 'sale' ? 'selected' : '' }}>Customer Sale</option>
                    <option value="warehouse" {{ $stockOut->source_type === 'transfer' ? 'selected' : '' }}>Warehouse Transfer</option>
                </select>
                @if($stockOut->source_type === 'transfer')
                    <input type="hidden" name="outbound_type" value="warehouse">
                @endif
            </div>

            {{-- CUSTOMER --}}
            <div class="col-md-2 {{ $stockOut->source_type === 'sale' ? '' : 'd-none' }}" id="customerBox">
                <label class="form-label fw-semibold small">Customer</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">Select</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ $stockOut->customer_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- TO WAREHOUSE --}}
            <div class="col-md-2 {{ $stockOut->source_type === 'transfer' ? '' : 'd-none' }}" id="toWarehouseBox">
                <label class="form-label fw-semibold small">To Warehouse</label>
                <select name="to_warehouse_id" class="form-select form-select-sm" {{ $stockOut->source_type === 'transfer' ? 'disabled' : '' }}>
                    <option value="">Select</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" {{ $stockOut->to_warehouse_id == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
                @if($stockOut->source_type === 'transfer')
                    <input type="hidden" name="to_warehouse_id" value="{{ $stockOut->to_warehouse_id }}">
                @endif
            </div>

            {{-- TRANSPORTER --}}
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Transporter</label>
                <select name="transporter_id" class="form-select form-select-sm">
                    <option value="">Optional</option>
                    @foreach($transporters as $t)
                        <option value="{{ $t->id }}" {{ $stockOut->transporter_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small">Shipment Type</label>
                <select name="shipment_type" class="form-select form-select-sm">
                    <option value="manual" {{ $stockOut->shipment_type === 'manual' ? 'selected' : '' }}>Manual</option>
                    <option value="auto" {{ $stockOut->shipment_type === 'auto' ? 'selected' : '' }}>Auto</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small">Dispatched Invoice No</label>
                <input name="dispatched_invoice_no" class="form-control form-control-sm bg-light"
                       value="{{ $stockOut->dispatched_invoice_no }}" readonly>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small">Outbound Dispatcher</label>
                <input name="dispatcher_sig" class="form-control form-control-sm" value="{{ $stockOut->dispatcher_sig }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small">Picker</label>
                <input name="picker" class="form-control form-control-sm" value="{{ $stockOut->picker }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small">Vehicle No</label>
                <input name="vehicle_no" class="form-control form-control-sm" value="{{ $stockOut->vehicle_no }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small">Vehicle Size</label>
                <input name="vehicle_size" class="form-control form-control-sm" value="{{ $stockOut->vehicle_size }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small">Driver Name</label>
                <input name="driver_name" class="form-control form-control-sm" value="{{ $stockOut->driver_name }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small">Driver Mobile</label>
                <input name="driver_mobile" class="form-control form-control-sm" value="{{ $stockOut->driver_mobile }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small">Vehicle In Time</label>
                <input type="datetime-local" name="vehicle_in_time"
                       class="form-control form-control-sm" value="{{ $stockOut->vehicle_in_time ? \Carbon\Carbon::parse($stockOut->vehicle_in_time)->format('Y-m-d\TH:i') : '' }}">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small">Vehicle Out Time</label>
                <input type="datetime-local" name="vehicle_out_time"
                       class="form-control form-control-sm" value="{{ $stockOut->vehicle_out_time ? \Carbon\Carbon::parse($stockOut->vehicle_out_time)->format('Y-m-d\TH:i') : '' }}">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small">DA #</label>
                <input name="da_no" class="form-control form-control-sm" value="{{ $stockOut->da_no }}">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Remarks / Gatepass Notes</label>
                <input name="remarks" class="form-control form-control-sm" value="{{ $stockOut->remarks }}">
            </div>
        </div>
    </div>
</div>

{{-- ================= ITEMS ================= --}}
<div class="card shadow-sm">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong>Dispatch Items</strong>
        @if($stockOut->source_type === 'transfer')
            <span class="badge bg-warning text-dark">Editing Transfer Items is not supported</span>
        @else
            <button type="button" id="addRowBtn" class="btn btn-sm btn-success">
                + Add Item
            </button>
        @endif
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0" id="itemsTable">
                <thead class="table-light text-center">
                    <tr>
                        <th width="260">Product Search</th>
                        <th width="180">Source Warehouse</th>
                        <th class="text-end" width="100">Avail</th>
                        <th class="text-end" width="80">Pack</th>
                        <th class="text-end" width="100">Units</th>
                        <th class="text-end" width="110">STO #</th>
                        <th class="text-end" width="100">Qty</th>
                        <th class="text-center" width="180">Locations / Pallets / Batch Details (👁)</th>
                        <th width="40"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div>
        <button type="submit" class="btn btn-primary px-4 shadow-sm" {{ $stockOut->source_type === 'transfer' ? 'disabled' : '' }}>
            {{ $stockOut->source_type === 'transfer' ? 'Only Customer Sales can be edited' : 'Update Outbound' }}
        </button>
        <a href="{{ route('outbound.index') }}" class="btn btn-secondary px-3 ms-2">Cancel</a>
    </div>
</div>
</form>

{{-- ================= PICK DETAILS MODAL ================= --}}
<div class="modal fade" id="pickModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title font-weight-bold text-white mb-0">Outbound Allocation & Pallet Preview</h6>
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

const isTransfer = {{ $stockOut->source_type === 'transfer' ? 'true' : 'false' }};
const sourceWarehouseId = {{ $stockOut->warehouse_id }};

const products = @json($products);
const groupedItems = @json($groupedItems);

/* Toggle customer / warehouse */
document.getElementById('outboundType').addEventListener('change', function () {
    document.getElementById('customerBox').classList.add('d-none');
    document.getElementById('toWarehouseBox').classList.add('d-none');

    if (this.value === 'customer') document.getElementById('customerBox').classList.remove('d-none');
    if (this.value === 'warehouse') document.getElementById('toWarehouseBox').classList.remove('d-none');
});

/* Add Row Function */
function addRow(data = null) {
    const tableBody = document.querySelector('#itemsTable tbody');
    tableBody.insertAdjacentHTML('beforeend', `
    <tr data-row-idx="${rowIndex}">
        <td>
            <div class="position-relative">
                <input type="text" class="form-control form-control-sm product-search" placeholder="Search product..." value="${data ? data.item_code + ' - ' + data.product_name : ''}" ${isTransfer ? 'readonly' : ''}>
                <div class="product-results position-absolute w-100 bg-white border rounded shadow-sm d-none" style="max-height: 200px; overflow-y: auto; z-index: 1000;"></div>
            </div>
            <input type="hidden" name="items[${rowIndex}][product_id]" class="selected-product-id" value="${data ? data.product_id : ''}">
        </td>

        <td>
            <select name="items[${rowIndex}][warehouse_id]" class="form-select form-select-sm warehouse-select" ${isTransfer ? 'readonly disabled' : ''}>
                ${data ? `<option value="${data.warehouse_id}" selected>${data.warehouse_name} (Selected)</option>` : '<option value="">Select Product First</option>'}
            </select>
            ${isTransfer && data ? `<input type="hidden" name="items[${rowIndex}][warehouse_id]" value="${data.warehouse_id}">` : ''}
        </td>

        <td><input class="form-control form-control-sm text-end avail bg-light" readonly value="${data ? 'N/A' : ''}"></td>
        <td><input class="form-control form-control-sm text-end pack bg-light" readonly value="${data ? data.pack_size : ''}"></td>

        <td>
            <input type="number" min="1"
                name="items[${rowIndex}][units_dispatch]"
                class="form-control form-control-sm text-end units" value="${data ? data.units_dispatch : ''}" ${isTransfer ? 'readonly' : ''}>
        </td>

        <td><input name="items[${rowIndex}][sto_no]" class="form-control form-control-sm text-end sto_no" value="${data && data.sto_no ? data.sto_no : ''}" ${isTransfer ? 'readonly' : ''}></td>

        <td><input class="form-control form-control-sm text-end qty bg-light" readonly value="${data ? data.total_qty : ''}"></td>

        <td>
            <div class="d-flex align-items-center justify-content-between">
                <span class="location-preview-text text-muted small text-truncate">FIFO</span>
                <button type="button" class="btn btn-sm btn-link py-0 px-1 btn-view-pallet-modal" title="View Allocation & Override">
                    <i class="bi bi-eye-fill text-primary" style="font-size: 16px;"></i>
                </button>
            </div>
            <input type="hidden" name="items[${rowIndex}][warehouse_row_id]" class="manual-warehouse-row-id">
            <input type="hidden" class="pallets-per-packing" value="">
            <input type="hidden" name="items[${rowIndex}][pallets_returned]" class="pallets-returned" value="${data ? (data.pallets_returned || 0) : 0}">
        </td>

        <td>
            ${isTransfer ? '' : '<button type="button" class="btn btn-sm btn-danger del">×</button>'}
        </td>
    </tr>
    `);
    rowIndex++;
}

/* Add Row Button */
if(document.getElementById('addRowBtn')) {
    document.getElementById('addRowBtn').onclick = () => {
        addRow();
    };
}

/* Prepopulate */
if (groupedItems && groupedItems.length > 0) {
    groupedItems.forEach(item => addRow(item));
} else if (!isTransfer) {
    addRow();
}

/* Product Search Handlers */
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('product-search')) {
        const input = e.target;
        const query = input.value.toLowerCase().trim();
        const resultsDiv = input.nextElementSibling;
        
        if (query.length === 0) {
            resultsDiv.classList.add('d-none');
            return;
        }

        const filtered = products.filter(p => 
            p.name.toLowerCase().includes(query) || 
            (p.item_code && p.item_code.toLowerCase().includes(query))
        );

        if (filtered.length === 0) {
            resultsDiv.innerHTML = '<div class="p-2 text-muted small">No products found</div>';
        } else {
            resultsDiv.innerHTML = filtered.map(p => `
                <div class="p-2 border-bottom product-result-item cursor-pointer" data-id="${p.id}" data-name="${p.name}" data-code="${p.item_code || ''}" data-pack="${p.pack_size}">
                    <strong>${p.item_code || ''}</strong> - ${p.name}
                </div>
            `).join('');
        }
        resultsDiv.classList.remove('d-none');
    }
});

document.addEventListener('click', function(e) {
    const item = e.target.closest('.product-result-item');
    if (item) {
        const row = item.closest('tr');
        const searchInput = row.querySelector('.product-search');
        const hiddenId = row.querySelector('.selected-product-id');
        const packInput = row.querySelector('.pack');
        const resultsDiv = row.querySelector('.product-results');

        const pId = item.dataset.id;
        const pName = item.dataset.name;
        const pCode = item.dataset.code;
        const pPack = item.dataset.pack;

        searchInput.value = `${pCode} - ${pName}`;
        hiddenId.value = pId;
        packInput.value = pPack;
        resultsDiv.classList.add('d-none');

        // Fetch stock for this product
        fetchProductStock(row, pId);
    } else if (!e.target.classList.contains('product-search')) {
        document.querySelectorAll('.product-results').forEach(div => div.classList.add('d-none'));
    }
});

function fetchProductStock(row, productId) {
    const whSelect = row.querySelector('.warehouse-select');
    const availInput = row.querySelector('.avail');
    const outboundType = document.getElementById('outboundType').value;

    fetch(`/outbound/product-stock/${productId}?outbound_type=${outboundType}`)
        .then(r => r.json())
        .then(data => {
            row.productStockData = data;
            whSelect.innerHTML = '<option value="auto">Auto Assign (FIFO)</option>';

            let totalAvail = 0;
            data.forEach(wh => {
                whSelect.innerHTML += `<option value="${wh.warehouse_id}">${wh.warehouse_name} (Avail: ${wh.available_qty})</option>`;
                totalAvail += parseFloat(wh.available_qty);
            });

            availInput.value = totalAvail.toFixed(2);
        })
        .catch(err => {
            whSelect.innerHTML = '<option value="">Error loading stock</option>';
        });
}

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('units')) {
        const row = e.target.closest('tr');
        const units = parseFloat(e.target.value || 0);
        const pack = parseFloat(row.querySelector('.pack').value || 1);
        const qtyInput = row.querySelector('.qty');
        qtyInput.value = (units * pack).toFixed(2);
    }
});

/* Eye button - Open Pick Modal */
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-view-pallet-modal');
    if (!btn) return;

    const row = btn.closest('tr');
    currentActiveRowIndexForModal = row.dataset.rowIdx;
    
    const productId = row.querySelector('.selected-product-id').value;
    const warehouseId = row.querySelector('.warehouse-select').value;
    const units = row.querySelector('.units').value;
    const outboundType = document.getElementById('outboundType').value;

    if (!productId || !units || units <= 0) {
        Swal.fire('Warning', 'Please select a product and enter units to dispatch first.', 'warning');
        return;
    }

    // Show loading modal
    document.getElementById('pallet-preview-summary').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">Allocating stock via FIFO...</p></div>';
    document.getElementById('pallet-grid-visualizer').innerHTML = '<p class="text-muted pt-5">Loading layout...</p>';
    
    new bootstrap.Modal(document.getElementById('pickModal')).show();

    // Fetch Preview Picks
    fetch('{{ route("outbound.preview_picks") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            items: [{
                product_id: productId,
                warehouse_id: warehouseId,
                units_dispatch: units
            }],
            outbound_type: outboundType,
            active_row_index: 0
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            renderModalAllocations(res.allocations);
        } else {
            document.getElementById('pallet-preview-summary').innerHTML = '<div class="text-danger p-3">Failed to load allocation preview.</div>';
        }
    })
    .catch(err => {
        document.getElementById('pallet-preview-summary').innerHTML = '<div class="text-danger p-3">Error fetching allocation data.</div>';
    });
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function loadOutboundPalletGrid(rowId, rowName, highlightPallets = []) {
    const gridDiv = document.getElementById('pallet-grid-visualizer');
    gridDiv.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div><p class="mt-2 text-muted small">Loading row layout...</p></div>';

    fetch(`/warehouse-rows/${rowId}/pallets`)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.pallets) {
                gridDiv.innerHTML = '<div class="text-muted small p-3">No pallet layout available for this row.</div>';
                return;
            }

            let gridHtml = `<div class="mb-2 text-start font-weight-bold text-dark border-bottom pb-1" style="font-size:12px;"><i class="bi bi-grid-3x3 me-1 text-primary"></i> ${escapeHtml(rowName)} (${data.occupied_pallets}/${data.total_capacity} Occupied)</div>`;
            gridHtml += '<div class="d-flex flex-wrap gap-1 justify-content-start">';

            const normHighlights = highlightPallets.map(p => String(p).toLowerCase().trim());

            data.pallets.forEach(p => {
                const isHighlight = normHighlights.includes(String(p.name).toLowerCase().trim());
                let cardBg = 'bg-light border text-dark';
                
                if (isHighlight) {
                    cardBg = 'bg-success text-white font-weight-bold shadow-sm';
                } else if (p.status === 'occupied') {
                    cardBg = 'bg-secondary text-white';
                }

                if (p.status === 'occupied' || isHighlight) {
                    const prodName = p.product_name || 'Stock Item';
                    const batchInfo = p.sap_batch ? `Batch: ${p.sap_batch}` : (p.vendor_batch ? `V-Batch: ${p.vendor_batch}` : '');
                    gridHtml += `
                        <div class="p-1 rounded ${cardBg} text-center" style="width: 72px; font-size: 10px;" title="${escapeHtml(p.name)} - ${escapeHtml(prodName)} ${batchInfo}">
                            <div class="text-truncate font-weight-bold">${escapeHtml(p.name)}</div>
                            <div class="text-truncate opacity-75" style="font-size: 9px;">${escapeHtml(p.item_code || prodName)}</div>
                        </div>
                    `;
                } else {
                    gridHtml += `
                        <div class="p-1 rounded ${cardBg} text-center opacity-50" style="width: 72px; font-size: 10px;" title="${escapeHtml(p.name)} - Free Space">
                            <div class="text-truncate font-weight-bold">${escapeHtml(p.name)}</div>
                            <div class="text-muted" style="font-size:10px;">[ Empty ]</div>
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

    if (!allocations || allocations.length === 0) {
        summaryDiv.innerHTML = '<div class="text-warning p-3 rounded bg-warning-subtle small font-weight-bold">No stock matched. Verify units and availability.</div>';
        gridDiv.innerHTML = '<p class="text-muted pt-5">Grid unavailable</p>';
        return;
    }

    let summaryHtml = '';

    allocations.forEach((alloc, index) => {
        const isFirst = index === 0;
        const activeClass = isFirst ? 'border-primary bg-primary-subtle' : 'bg-white';
        const clickStyle = alloc.row_id ? 'cursor: pointer; transition: all 0.2s;' : '';
        const clickClass = alloc.row_id ? `allocation-item-card p-3 mb-2 rounded border hover-shadow ${activeClass}` : 'p-3 mb-2 rounded border bg-light';
        const rowIdAttr = alloc.row_id ? `data-row-id="${alloc.row_id}"` : '';
        const rowNameAttr = alloc.row_name ? `data-row-name="${alloc.row_name}"` : '';
        const palletNamesAttr = alloc.pallet_names ? `data-pallet-names="${escapeHtml(JSON.stringify(alloc.pallet_names))}"` : '';

        let expiryBadges = '';
        if (alloc.has_near_expiry) {
            expiryBadges += `<span class="badge bg-danger text-white me-1" style="font-size: 10px;"><i class="bi bi-clock-history"></i> Near Expiry</span>`;
        }
        if (alloc.has_long_expiry) {
            expiryBadges += `<span class="badge bg-success text-white" style="font-size: 10px;"><i class="bi bi-shield-check"></i> Good Expiry</span>`;
        }

        summaryHtml += `
            <div class="${clickClass}" ${rowIdAttr} ${rowNameAttr} ${palletNamesAttr} style="${clickStyle}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark fs-6"><i class="bi bi-building me-1"></i> WH: ${escapeHtml(alloc.warehouse_name)}</span>
                    <span class="badge bg-primary fs-6">${alloc.units} Units (${alloc.qty} Qty)</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2" style="font-size:12px; color:#1e293b;">
                    <div><i class="bi bi-geo-alt me-1 text-danger"></i><strong>Row/Location:</strong> ${escapeHtml(alloc.row_name)}</div>
                    <div>${expiryBadges}</div>
                </div>

                <div class="bg-white p-2 rounded border mb-2 small text-dark" style="font-size: 11px;">
                    <div class="row g-1">
                        <div class="col-6"><strong>PO #:</strong> <span class="text-primary font-monospace">${escapeHtml(alloc.po_no || '-')}</span></div>
                        <div class="col-6"><strong>IBD #:</strong> <span class="text-primary font-monospace">${escapeHtml(alloc.ibd_no || '-')}</span></div>
                        <div class="col-6"><strong>SAP Batch:</strong> <span class="font-monospace">${escapeHtml(alloc.sap_batch || '-')}</span></div>
                        <div class="col-6"><strong>Vendor Batch:</strong> <span class="font-monospace">${escapeHtml(alloc.vendor_batch || '-')}</span></div>
                        <div class="col-6"><strong>MFG Date:</strong> <span>${escapeHtml(alloc.mfg_date || '-')}</span></div>
                        <div class="col-6"><strong>Expiry Date:</strong> <span class="fw-bold text-danger">${escapeHtml(alloc.expiry_date || '-')}</span></div>
                    </div>
                </div>

                <div class="text-muted small" style="font-size:11px;">
                    <i class="bi bi-box-seam me-1"></i><strong>Pallet Locations:</strong> ${escapeHtml(alloc.pallet_names.join(', '))}
                </div>

                ${alloc.row_id ? '<div class="text-end mt-1"><span class="badge bg-primary-subtle text-primary" style="font-size: 9px;"><i class="bi bi-grid-3x3"></i> Click to View Grid</span></div>' : ''}
            </div>
        `;
    });

    summaryDiv.innerHTML = summaryHtml;

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

    if (overrideCheck && overrideCheck.checked && overrideRow && overrideRow.value) {
        manualRowInput.value = overrideRow.value;
        const rowText = overrideRow.options[overrideRow.selectedIndex].text;
        previewText.textContent = `Manual [${rowText}]`;
    } else if (previewText) {
        if (manualRowInput) manualRowInput.value = '';
        previewText.textContent = 'FIFO';
    }

    bootstrap.Modal.getInstance(document.getElementById('pickModal')).hide();
});

// Delete Row
document.addEventListener('click', e => {
    if (!e.target.classList.contains('del')) return;
    const row = e.target.closest('tr');
    if (row) row.remove();
});

// AJAX Form Submit for Outbound Update
document.getElementById('outboundForm').addEventListener('submit', function(e) {
    e.preventDefault();

    let hasError = false;
    let hasValidRow = false;

    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        const prodId = row.querySelector('.selected-product-id');
        const warehouseId = row.querySelector('.warehouse-select');

        if (!prodId || !prodId.value) return;

        hasValidRow = true;

        if (warehouseId && !warehouseId.value) {
            hasError = true;
            warehouseId.classList.add('is-invalid');
        }
    });

    if (hasError || !hasValidRow) {
        Swal.fire('Error', 'Please resolve validation errors and ensure at least one row contains a valid product.', 'error');
        return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');
    const origText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating Outbound...';

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
        submitBtn.innerHTML = origText;

        if (res.status === 200 || res.status === 201 || res.body.success) {
            Swal.fire('Success', 'Outbound transaction updated successfully!', 'success').then(() => {
                window.location.href = res.body.redirect || '{{ route("outbound.index") }}';
            });
        } else {
            let errorMsg = res.body.message || 'Validation Error';
            if (res.body.errors) {
                errorMsg = Object.values(res.body.errors).flat().join('<br>');
            }
            Swal.fire({ icon: 'error', title: 'Error Updating Outbound', html: errorMsg });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = origText;
        Swal.fire('Error', 'A network connection error occurred.', 'error');
    });
});
</script>
@endpush