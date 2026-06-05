@extends('layouts.app')

@section('content')

<form method="POST"
      action="{{ route('opening-stock.store') }}"
      id="openingStockForm">
@csrf

{{-- ================= HEADER ================= --}}
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h6 class="mb-0">Opening Stock Entry</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Warehouse</label>
                <select name="warehouse_id"
                        class="form-control form-control-sm"
                        required>
                    <option value="">Select Warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-8 mb-3">
                <label class="form-label">Remarks</label>
                <input type="text"
                       name="remarks"
                       class="form-control form-control-sm">
            </div>
        </div>
    </div>
</div>

{{-- ================= ITEMS ================= --}}
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between">
        <h6 class="mb-0">Products</h6>
        <button type="button"
                id="addRowBtn"
                class="btn btn-sm btn-success">
            + Add Row
        </button>
    </div>

    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0" id="itemsTable">
            <thead>
            <tr>
                <th width="240">Product</th>
                <th width="140">Product Code</th>
                <th width="80">Units</th>
                <th width="70">Pack</th>
                <th width="80">Total</th>
                <th width="80">Pallets</th>
                <th width="120">QC Clearance</th>
                <th width="120">Status</th>
                <th width="40"></th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <button class="btn btn-primary">Save Opening Stock</button>
    <a href="{{ route('opening-stock.index') }}"
       class="btn btn-secondary">Back</a>
</div>
</form>

{{-- ================= PRODUCT DATALIST ================= --}}
<datalist id="products_list">
@foreach($products as $p)
    <option value="{{ $p->name }} ({{ $p->item_code }})"
            data-id="{{ $p->id }}"
            data-pack="{{ $p->pack_size }}"
            data-cartons="{{ $p->cartons_per_pallet ?? '' }}"
            data-code="{{ $p->item_code }}">
@endforeach
</datalist>

{{-- ================= BATCH MODAL ================= --}}
<div class="modal fade" id="batchModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">

<div class="modal-header">
    <h6 class="modal-title">Batch Details</h6>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body row g-2">
    <div class="col-md-4">
        <label>SAP</label>
        <input class="form-control form-control-sm modal-sap">
    </div>
    <div class="col-md-4">
        <label>Vendor</label>
        <input class="form-control form-control-sm modal-vendor">
    </div>
    <div class="col-md-4">
        <label>IBD</label>
        <input class="form-control form-control-sm modal-ibd">
    </div>

    <div class="col-md-4">
        <label>PO</label>
        <input class="form-control form-control-sm modal-po">
    </div>
    <div class="col-md-4">
        <label>MFG</label>
        <input type="date"
               class="form-control form-control-sm modal-mfg">
    </div>
    <div class="col-md-4">
        <label>Expiry</label>
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

let rowIndex = 0;
let activeRow = null;

/* ================= FORM SUBMISSION & DISABLE ENTER ================= */
document.getElementById('openingStockForm').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        
        // If Enter is pressed inside the items table, add a new row
        if (e.target.closest('#itemsTable')) {
            document.getElementById('addRowBtn').click();
            
            // Focus on the newly added row's product search input
            setTimeout(() => {
                let rows = document.querySelectorAll('#itemsTable tbody tr');
                if (rows.length > 0) {
                    let lastRow = rows[rows.length - 1];
                    let input = lastRow.querySelector('.product-input');
                    if (input) input.focus();
                }
            }, 50);
        }
    }
});

document.getElementById('openingStockForm').addEventListener('submit', function(e) {
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
            submitBtn.innerHTML = 'Save Opening Stock';
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
                text: res.body.message || 'Saved successfully!',
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
            submitBtn.innerHTML = 'Save Opening Stock';
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

    document.querySelector('#itemsTable tbody')
    .insertAdjacentHTML('beforeend', `
<tr>
<td>
<input list="products_list"
       class="form-control form-control-sm product-input"
       placeholder="Search product"
       autocomplete="off">
<input type="hidden"
       name="items[${rowIndex}][product_id]"
       class="product-id">
</td>

<td>
<input class="form-control form-control-sm product-code" readonly>
</td>

<td>
<input type="number" min="1"
       name="items[${rowIndex}][units_received]"
       class="form-control form-control-sm units">
</td>

<td>
<input class="form-control form-control-sm pack-size" readonly>
</td>

<td>
<input class="form-control form-control-sm total-qty" readonly>
</td>

<td>
<input type="number" min="0"
       name="items[${rowIndex}][pallets_used]"
       class="form-control form-control-sm pallets-used"
       placeholder="Auto">
<input type="hidden" name="items[${rowIndex}][use_pallets]" value="1">
<input type="hidden" class="pallets-per-packing" value="">
</td>

<td>
<select name="items[${rowIndex}][quality_clearance]" class="form-select form-select-sm">
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
</select>
</td>

<td>
<label><input type="checkbox"
       name="items[${rowIndex}][sound_stock]"
       checked> S</label>
<label><input type="checkbox"
       name="items[${rowIndex}][block_stock]"> B</label>
<label><input type="checkbox"
       name="items[${rowIndex}][hold_stock]"> H</label>
</td>

<td>
<button type="button"
        class="btn btn-sm btn-danger removeRow">×</button>
</td>

<input type="hidden" name="items[${rowIndex}][sap_batch]">
<input type="hidden" name="items[${rowIndex}][vendor_batch]">
<input type="hidden" name="items[${rowIndex}][ibd_no]">
<input type="hidden" name="items[${rowIndex}][po_no]">
<input type="hidden" name="items[${rowIndex}][mfg_date]">
<input type="hidden" name="items[${rowIndex}][expiry_date]">
</tr>
`);

    rowIndex++;
});

/* ================= PRODUCT SEARCH SELECT ================= */
document.addEventListener('input', function (e) {

    if (!e.target.classList.contains('product-input')) return;

    const row = e.target.closest('tr');
    const value = e.target.value;

    const option = [...document.querySelectorAll('#products_list option')]
        .find(o => o.value === value);

    if (!option) return;

    row.querySelector('.product-id').value   = option.dataset.id;
    row.querySelector('.pack-size').value    = option.dataset.pack;
    row.querySelector('.product-code').value = option.dataset.code;

    // Store cartons_per_pallet and auto-calculate pallets needed
    const cartonsPerPallet = option.dataset.cartons || '';
    row.querySelector('.pallets-per-packing').value = cartonsPerPallet;
    const palletsInput = row.querySelector('.pallets-used');
    if (cartonsPerPallet) {
        const units = Number(row.querySelector('.units').value || 0);
        palletsInput.value = units > 0 ? Math.ceil(units / Number(cartonsPerPallet)) : '';
        palletsInput.placeholder = 'Auto (' + cartonsPerPallet + ' ctn/pallet)';
    } else {
        palletsInput.placeholder = 'Enter pallets';
    }

    activeRow = row;

    // blank modal for new entry
    document.querySelector('.modal-sap').value = '';
    document.querySelector('.modal-vendor').value = '';
    document.querySelector('.modal-ibd').value = '';
    document.querySelector('.modal-po').value = '';
    document.querySelector('.modal-mfg').value = '';
    document.querySelector('.modal-expiry').value = '';

    new bootstrap.Modal(document.getElementById('batchModal')).show();
});

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

    // Auto-calculate pallets needed: ceil(units / cartons_per_pallet)
    const cartonsPerPallet = Number(row.querySelector('.pallets-per-packing').value || 0);
    const palletsInput = row.querySelector('.pallets-used');
    if (cartonsPerPallet > 0 && units > 0) {
        palletsInput.value = Math.ceil(units / cartonsPerPallet);
    }
});

    // Add first row by default
    document.getElementById('addRowBtn').click();

    /* ================= REMOVE ================= */
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('removeRow')) {
            e.target.closest('tr').remove();
        }
    });
});
</script>
@endpush
