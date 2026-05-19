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
</style>
<form method="POST" action="{{ route('outbound.store') }}" id="outboundForm">
@csrf

{{-- ================= HEADER ================= --}}
<div class="card shadow-sm mb-3">
    <div class="card-header py-2">
        <strong>Outbound Dispatch Entry</strong>
    </div>

    <div class="card-body">
        <div class="row g-2">

            {{-- OUTBOUND TYPE --}}
            <div class="col-md-2">
                <label class="form-label">Outbound Type</label>
                <select name="outbound_type" id="outboundType"
                        class="form-select form-select-sm" required>
                    <option value="">Select</option>
                    <option value="customer">Customer Sale</option>
                    <option value="warehouse">Warehouse Transfer</option>
                </select>
            </div>

            {{-- CUSTOMER --}}
            <div class="col-md-2 d-none" id="customerBox">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">Select</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- TO WAREHOUSE --}}
            <div class="col-md-2 d-none" id="toWarehouseBox">
                <label class="form-label">To Warehouse</label>
                <select name="to_warehouse_id" class="form-select form-select-sm">
                    <option value="">Select</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- TRANSPORTER --}}
            <div class="col-md-2">
                <label class="form-label">Transporter</label>
                <select name="transporter_id" class="form-select form-select-sm">
                    <option value="">Optional</option>
                    @foreach($transporters as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Shipment Type</label>

                <select name="shipment_type" class="form-select form-select-sm">
                    <option value="manual">Manual</option>
                    <option value="auto">Auto</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Dispatched Invoice No</label>
                <input name="dispatched_invoice_no" class="form-control form-control-sm"
                       value="{{ old('dispatched_invoice_no', $nextDispatchedInvoiceNo ?? '') }} " readonly>
            </div>

            <div class="col-md-2">
                <label class="form-label">Outbound Dispatcher</label>
                <input name="dispatcher_sig" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label">Picker</label>
                <input name="picker" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label">Vehicle No</label>
                <input name="vehicle_no" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label">Vehicle Size</label>
                <input name="vehicle_size" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label">Driver Name</label>
                <input name="driver_name" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label">Driver Mobile</label>
                <input name="driver_mobile" class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label">Vehicle In Time</label>
                <input type="datetime-local" name="vehicle_in_time"
                       class="form-control form-control-sm">
            </div>

            <div class="col-md-2">
                <label class="form-label">Vehicle Out Time</label>
                <input type="datetime-local" name="vehicle_out_time"
                       class="form-control form-control-sm">
            </div>

            <div class="col-md-6">
                <label class="form-label">DA #</label>
                <input name="da_no" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
                <label class="form-label">Remarks / Gatepass Notes</label>
                <input name="remarks" class="form-control form-control-sm">
            </div>
        </div>
    </div>
</div>

{{-- ================= ITEMS ================= --}}
<div class="card shadow-sm">
    <div class="card-header py-2 d-flex justify-content-between">
        <strong>Dispatch Items</strong>
        <button type="button" id="addRowBtn" class="btn btn-sm btn-success">
            + Add Item
        </button>
    </div>

    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0" id="itemsTable">
            <thead class="table-light text-center">
                <tr>
                    <th width="200">Product</th>
                    <th width="180">Source Warehouse</th>
                    <th width="120">PO #</th>
                    <th width="120">IBD #</th>
                    <th class="text-end" width="90">Avail</th>
                    <th class="text-end" width="70">Pack</th>
                    <th class="text-end" width="90">Units</th>
                    <th class="text-end" width="100">STO</th>
                    <th class="text-end" width="90">Qty</th>
                    <th class="text-end" width="70">Pallets</th>
                    <th width="35"></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<button type="submit" class="btn btn-primary mt-3">Save Outbound</button>
</form>

@endsection

@push('scripts')
<script>
let rowIndex = 0;
let activeRow = null;

/* Toggle customer / warehouse */
document.getElementById('outboundType').addEventListener('change', function () {
    document.getElementById('customerBox').classList.add('d-none');
    document.getElementById('toWarehouseBox').classList.add('d-none');

    if (this.value === 'customer') document.getElementById('customerBox').classList.remove('d-none');
    if (this.value === 'warehouse') document.getElementById('toWarehouseBox').classList.remove('d-none');
});

/* Add Row Function */
function addRow() {
    document.querySelector('#itemsTable tbody').insertAdjacentHTML('beforeend', `
    <tr>
        <td>
            <div class="position-relative">
                <input type="text" class="form-control form-control-sm product-search" placeholder="Search product...">
                <div class="product-results position-absolute w-100 bg-white border rounded shadow-sm d-none" style="max-height: 200px; overflow-y: auto; z-index: 1000;"></div>
            </div>
            <input type="hidden" name="items[${rowIndex}][product_id]" class="selected-product-id">
        </td>

        <td>
            <select name="items[${rowIndex}][warehouse_id]" class="form-select form-select-sm warehouse-select">
                <option value="">Select Product First</option>
            </select>
        </td>

        <td><input name="items[${rowIndex}][po_no]" class="form-control form-control-sm po_no"></td>
        <td><input name="items[${rowIndex}][ibd_no]" class="form-control form-control-sm ibd_no"></td>
        
        <td><input class="form-control form-control-sm text-end avail" readonly></td>
        <td><input class="form-control form-control-sm text-end pack" readonly></td>

        <td>
            <input type="number" min="1"
                name="items[${rowIndex}][units_dispatch]"
                class="form-control form-control-sm text-end units">
        </td>

        <td><input name="items[${rowIndex}][sto_no]" class="form-control form-control-sm text-end sto_no"></td>

        <td><input class="form-control form-control-sm text-end qty" readonly></td>

        <td>
            <input type="number" min="0"
                name="items[${rowIndex}][pallets_returned]"
                class="form-control form-control-sm text-end pallets-returned" value="0">
            <input type="hidden" class="pallets-per-packing" value="">
        </td>

        <td>
            <button type="button" class="btn btn-sm btn-danger del">×</button>
        </td>
    </tr>
    `);
    rowIndex++;
}

/* Add Row Button */
document.getElementById('addRowBtn').onclick = () => {
    addRow();
};

/* Product search functionality */
const products = @json($products);

/* Show all products on focus */
document.addEventListener('focus', e => {
    if (!e.target.classList.contains('product-search')) return;
    
    const resultsDiv = e.target.nextElementSibling;
    const searchTerm = e.target.value.toLowerCase();
    
    const filtered = searchTerm.length >= 2 
        ? products.filter(p => 
            p.name.toLowerCase().includes(searchTerm) || 
            p.item_code.toLowerCase().includes(searchTerm)
          )
        : products;
    
    if (filtered.length === 0) {
        resultsDiv.innerHTML = '<div class="p-2 text-muted">No products found</div>';
    } else {
        resultsDiv.innerHTML = filtered.slice(0, 50).map(p => `
            <div class="product-result-item p-2 border-bottom" style="cursor: pointer;" 
                 data-id="${p.id}" 
                 data-name="${p.item_code} - ${p.name}">
                <strong>${p.item_code}</strong> - ${p.name}
            </div>
        `).join('');
    }
    
    resultsDiv.classList.remove('d-none');
}, true);

/* Filter products as user types */
document.addEventListener('input', e => {
    if (!e.target.classList.contains('product-search')) return;
    
    const searchTerm = e.target.value.toLowerCase();
    const resultsDiv = e.target.nextElementSibling;
    
    if (searchTerm.length === 0) {
        resultsDiv.innerHTML = products.slice(0, 50).map(p => `
            <div class="product-result-item p-2 border-bottom" style="cursor: pointer;" 
                 data-id="${p.id}" 
                 data-name="${p.item_code} - ${p.name}">
                <strong>${p.item_code}</strong> - ${p.name}
            </div>
        `).join('');
        resultsDiv.classList.remove('d-none');
        return;
    }
    
    const filtered = products.filter(p => 
        p.name.toLowerCase().includes(searchTerm) || 
        p.item_code.toLowerCase().includes(searchTerm)
    );
    
    if (filtered.length === 0) {
        resultsDiv.innerHTML = '<div class="p-2 text-muted">No products found</div>';
        resultsDiv.classList.remove('d-none');
        return;
    }
    
    resultsDiv.innerHTML = filtered.map(p => `
        <div class="product-result-item p-2 border-bottom" style="cursor: pointer;" 
             data-id="${p.id}" 
             data-name="${p.item_code} - ${p.name}">
            <strong>${p.item_code}</strong> - ${p.name}
        </div>
    `).join('');
    
    resultsDiv.classList.remove('d-none');
});

/* Select product from search results */
document.addEventListener('click', e => {
    if (!e.target.closest('.product-result-item')) return;
    
    const item = e.target.closest('.product-result-item');
    const row = item.closest('tr');
    const searchInput = row.querySelector('.product-search');
    const resultsDiv = row.querySelector('.product-results');
    const productId = item.dataset.id;
    
    searchInput.value = item.dataset.name;
    row.querySelector('.selected-product-id').value = productId;
    resultsDiv.classList.add('d-none');
    
    const warehouseSelect = row.querySelector('.warehouse-select');
    warehouseSelect.innerHTML = '<option value="">Loading Stock...</option>';
    
    // Load all warehouses that have stock for this product
    fetch(`/outbound/product-stock/${productId}?t=${new Date().getTime()}`)
        .then(r => r.json())
        .then(data => {
            if (data.length > 0) {
                let options = '<option value="">Select Warehouse</option>';
                data.forEach(wh => {
                    const pack = wh.batches.length > 0 ? wh.batches[0].pack : 1;
                    options += `<option value="${wh.warehouse_id}" 
                                      data-stock="${wh.total_stock}" 
                                      data-pack="${pack}"
                                      data-ppp="${wh.cartons_per_pallet || 0}">
                                    ${wh.warehouse} (Avail: ${wh.total_stock} Qty)
                                </option>`;
                });
                // Note: The above logic assumes warehouse_id is in the batch data, let's refine in next step if needed.
                // Actually, my backend returns wh.warehouse name, but we need the ID.
                // Let's assume the backend return for warehouse includes the ID soon, or we use a better way.
                
                // RE-FETCHING data with IDs if possible. 
                // Let's assume the 'warehouse' name matches or we update the backend.
                warehouseSelect.innerHTML = options;
            } else {
                warehouseSelect.innerHTML = '<option value="">No Stock Available</option>';
            }
        });
});

/* Update Stock Info when Warehouse is selected in Row */
document.addEventListener('change', e => {
    if (!e.target.classList.contains('warehouse-select')) return;
    
    const row = e.target.closest('tr');
    const option = e.target.options[e.target.selectedIndex];
    
    if (option.value) {
        const stock = option.dataset.stock;
        const pack = option.dataset.pack;
        const ppp = option.dataset.ppp;
        
        row.querySelector('.avail').value = stock;
        row.querySelector('.pack').value = pack;
        row.querySelector('.pallets-per-packing').value = ppp;
        
        if (ppp > 0) {
            row.querySelector('.pallets-returned').placeholder = 'Auto (' + ppp + ' ctn/pallet)';
        }
    } else {
        row.querySelector('.avail').value = '';
        row.querySelector('.pack').value = '';
    }
});

/* Close search results when clicking outside */
document.addEventListener('click', e => {
    if (!e.target.classList.contains('product-search')) {
        document.querySelectorAll('.product-results').forEach(div => {
            div.classList.add('d-none');
        });
    }
});

/* Calculate Qty, validate against available stock, and auto-calc pallets */
document.addEventListener('input', e => {
    if (!e.target.classList.contains('units')) return;
    
    const row = e.target.closest('tr');
    const pack = parseFloat(row.querySelector('.pack').value || 0);
    const units = parseFloat(e.target.value || 0);
    const avail = parseFloat(row.querySelector('.avail').value || 0);
    const qty = pack * units;
    
    row.querySelector('.qty').value = qty.toFixed(2);
    
    // Auto-calculate pallets dispatched
    const cartonsPerPallet = parseFloat(row.querySelector('.pallets-per-packing').value || 0);
    if (cartonsPerPallet > 0 && units > 0) {
        row.querySelector('.pallets-returned').value = Math.ceil(units / cartonsPerPallet);
    }
    
    // Validation
    if (qty > avail) {
        e.target.classList.add('is-invalid');
        alert(`Insufficient stock in selected warehouse! Available: ${avail.toFixed(2)}, Requested: ${qty.toFixed(2)}`);
    } else {
        e.target.classList.remove('is-invalid');
    }
});

/* Form Submit */
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
        
        const qty = parseFloat(row.querySelector('.qty').value || 0);
        const avail = parseFloat(row.querySelector('.avail').value || 0);
        
        if (qty > avail) {
            hasError = true;
            row.querySelector('.units').classList.add('is-invalid');
        }
    });
    
    if (hasError || !hasValidRow) {
        Swal.fire('Error', 'Please fix validation errors and ensure at least one item is added with a warehouse selected.', 'error');
        return;
    }

    let submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Saving...';

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
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Outbound';

        if (res.status === 200 || res.status === 201) {
            Swal.fire('Success', 'Outbound saved successfully!', 'success').then(() => {
                window.location.href = res.body.redirect || '{{ route("outbound.index") }}';
            });
        } else {
            let errorMsg = res.body.message || 'Validation Error';
            if (res.body.errors) {
                errorMsg = Object.values(res.body.errors).flat().join('<br>');
            }
            Swal.fire({ icon: 'error', title: 'Error', html: errorMsg });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Outbound';
        Swal.fire('Error', 'A network error occurred.', 'error');
    });
});

/* Remove Row */
document.addEventListener('click', e => {
    if (!e.target.classList.contains('del')) return;
    e.target.closest('tr').remove();
});

/* Auto-add row when last row gets a product */
document.addEventListener('click', e => {
    if (!e.target.closest('.product-result-item')) return;
    const allRows = document.querySelectorAll('#itemsTable tbody tr');
    const lastRow = allRows[allRows.length - 1];
    if (e.target.closest('tr') === lastRow) {
        setTimeout(() => addRow(), 100);
    }
});

// Initialize first row
addRow();
</script>
@endpush