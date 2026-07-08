@extends('layouts.app')

@section('content')
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">

    <style>
        .form-label { font-size: 12px; margin-bottom: 2px }
        .form-control, .form-select { font-size: 13px; height: 32px }
        .table-wrapper { width: 100%; overflow-x: auto }
        .table-wrapper table { min-width: 1500px }
        .product-search-wrap { position: relative }
        .product-search-item { padding: 6px 10px; cursor: pointer; font-size: 13px }
        .product-search-item:hover { background: #0d6efd; color: #fff }
        .product-search-dropdown {
            position: fixed; z-index: 1070; background: #fff;
            border: 1px solid #ddd; border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
            max-height: 220px; overflow-y: auto; min-width: 280px;
        }
        .qc-select-pending option[value="pending"] { background: #fff3cd; }
        .qc-select-approved option[value="approved"] { background: #d1e7dd; }
        .qc-select-rejected option[value="rejected"] { background: #f8d7da; }
        .qc-select-pending { background: #fff3cd !important; }
        .qc-select-approved { background: #d1e7dd !important; }
        .qc-select-rejected { background: #f8d7da !important; }
        .auto-mode-wh { pointer-events: none; background-color: #e9ecef !important; opacity: 1; }
    </style>

    <form method="POST" action="{{ route('inbound.store') }}" id="inboundForm" autocomplete="off">
        @csrf

        {{-- ================= HEADER ================= --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header">
                <h6 class="mb-0">Inbound Entry</h6>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-md-3 mb-2">
                        <label class="form-label">Warehouse</label>
                        <div class="d-flex align-items-center gap-2">
                            <select id="warehouseSelect" name="warehouse_id" class="form-control form-control-sm" required>
                                <option value="">Select</option>
                                @foreach ($warehouseData as $wd)
                                    @php $w = $warehouses->firstWhere('id', $wd['id']) @endphp
                                    <option value="{{ $wd['id'] }}"
                                        data-free="{{ $wd['free_pallets'] }}"
                                        {{ $wd['id'] == $autoSelectWarehouseId ? 'selected' : '' }}
                                        {{ !$wd['has_space'] ? 'disabled' : '' }}>
                                        {{ $wd['name'] }}
                                        @if($w->total_capacity > 0)
                                            ({{ $wd['free_pallets'] }} free / {{ $w->total_capacity }} total)
                                        @else
                                            (unlimited)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-check form-switch mb-0 flex-shrink-0" style="white-space:nowrap;">
                                <input class="form-check-input" type="checkbox" id="manualToggle" style="cursor:pointer;">
                                <label class="form-check-label small" for="manualToggle" style="cursor:pointer;">Manual</label>
                            </div>
                        </div>
                        <input type="hidden" name="manual_selection" id="manualSelectionInput" value="0">
                        @if($autoSelectWarehouseId)
                            <small class="text-muted" id="warehouseHint">
                                <i class="bi bi-info-circle"></i> Auto-selected warehouse with most free space
                            </small>
                        @endif
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-control form-control-sm">
                            <option value="">Select</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Arrived From</label>
                        <select name="arrived_from_id" class="form-control form-control-sm">
                            <option value="">Select</option>
                            @foreach ($arrivedFroms as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Transporter</label>
                        <select name="transporter_id" class="form-control form-control-sm">
                            <option value="">Select</option>
                            @foreach ($transporters as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Shipment Type</label>

                        <select name="shipment_type" class="form-select form-select-sm">
                            <option value="manual">Manual</option>
                            <option value="auto">Auto</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Inbound Invoice No</label>
                        <input name="dispatched_invoice_no" value="{{ $nextDispatchedInvoiceNo ?? '' }}" class="form-control form-control-sm" readonly>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Inbound Signature </label>
                        <input name="dispatcher_sig" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Picker</label>
                        <input name="picker" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vehicle No</label>
                        <input name="vehicle_no" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vehicle Size</label>
                        <input name="vehicle_size" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Driver Name</label>
                        <input name="driver_name" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Driver Mobile</label>
                        <input name="driver_mobile" class="form-control form-control-sm">
                    </div>


                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vehicle In Time</label>
                        <input type="datetime-local" name="vehicle_in_time" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vehicle Out Time</label>
                        <input type="datetime-local" name="vehicle_out_time" class="form-control form-control-sm">
                    </div>


                    <div class="col-md-2 mb-2">
                        <label class="form-label">Remarks</label>
                        <input name="remarks" class="form-control form-control-sm">
                    </div>

                </div>
            </div>
        </div>

        {{-- ================= ITEMS ================= --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                <h6 class="card-title mb-0 fw-bold">Inbound Products</h6>
                <button type="button" id="addRowBtn" class="btn btn-sm btn-success"><i class="bi bi-plus-lg"></i> Add Row</button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-bordered table-hover align-middle mb-0" id="itemsTable" style="min-width: 1000px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 320px;">Product</th>
                                <th style="width: 90px;">Units</th>
                                <th style="width: 90px;">Pack</th>
                                <th style="width: 100px;">Total</th>
                                <th style="width: 90px;">Pallets</th>
                                <th style="width: 120px;" class="text-center">Status</th>
                                <th style="width: 130px;">QC</th>
                                <th style="width: 90px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary">Save Inbound</button>
            <a href="{{ route('inbound.index') }}" class="btn btn-secondary">Back</a>
        </div>

    </form>

    @php
        // warehouseRows variable no longer needed for UI
    @endphp

    {{-- ================= BATCH MODAL ================= --}}
    <div class="modal fade" id="batchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Batch Details</h6>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body row g-2">
                    <div class="col-md-4"><label>SAP</label><input class="form-control form-control-sm modal-sap"></div>
                    <div class="col-md-4"><label>Vendor Batch</label><input class="form-control form-control-sm modal-vendor"></div>
                    <div class="col-md-4"><label>PO</label><input class="form-control form-control-sm modal-po"></div>
                    <div class="col-md-4"><label>IBD</label><input class="form-control form-control-sm modal-ibd"></div>
                    <div class="col-md-4"><label>MFG</label><input type="date" class="form-control form-control-sm modal-mfg"></div>
                    <div class="col-md-4"><label>EXP</label><input type="date" class="form-control form-control-sm modal-expiry"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm" id="saveBatchBtn">OK</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let rowIndex = 0,
                activeRow = null;
            const products = @json($products);
            const warehouseData = @json($warehouseData);
            const batchModal = new bootstrap.Modal(document.getElementById('batchModal'));

            /* Manual warehouse toggle */
            const manualToggle = document.getElementById('manualToggle');
            const manualInput = document.getElementById('manualSelectionInput');

            function setWarehouseMode(manual) {
                warehouseSelect.classList.toggle('auto-mode-wh', !manual);
                if (manual) {
                    warehouseSelect.querySelectorAll('option').forEach(opt => {
                        if (opt.value && Number(opt.dataset.free) <= 0) opt.disabled = true;
                    });
                }
                manualInput.value = manual ? '1' : '0';
            }

            manualToggle.addEventListener('change', function() {
                setWarehouseMode(this.checked);
            });

            /* Show warehouse free space hint on change */
            warehouseSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                const free = opt ? opt.dataset.free : null;
                const hint = document.getElementById('warehouseHint');
                if (hint && free !== undefined) {
                    hint.innerHTML = `<i class="bi bi-info-circle"></i> ${free} pallet slots free`;
                }
            });

            setWarehouseMode(false);

            /* ADD ROW FUNCTION */
            function addRow() {
                itemsTable.querySelector('tbody').insertAdjacentHTML('beforeend', `
<tr>
<td>
<div class="input-group input-group-sm">
<input class="form-control product-input" placeholder="Search product" autocomplete="off">
<button type="button" class="btn btn-outline-primary open-batch-btn" title="Batch Details"><i class="bi bi-tags"></i> Batch</button>
</div>
<div class="product-search-wrap">
<input type="hidden" name="items[${rowIndex}][product_id]" class="product-id">
</div>
</td>

<td><input name="items[${rowIndex}][units_received]" class="form-control form-control-sm units"></td>
<td><input class="form-control form-control-sm pack-size bg-light" readonly></td>
<td><input class="form-control form-control-sm total-qty bg-light fw-bold" readonly></td>

<td>
<input name="items[${rowIndex}][pallets_used]" class="form-control form-control-sm pallets-used" placeholder="Auto">
<input type="hidden" name="items[${rowIndex}][use_pallets]" value="1">
<input type="hidden" class="pallets-per-packing" value="">
</td>

<td class="text-center">
<div class="btn-group" role="group">
  <input type="checkbox" class="btn-check" name="items[${rowIndex}][sound_stock]" id="ss_${rowIndex}" checked>
  <label class="btn btn-outline-success btn-sm px-2 py-1" for="ss_${rowIndex}" title="Sound Stock" style="font-size: 11px; font-weight: 600;">S</label>

  <input type="checkbox" class="btn-check" name="items[${rowIndex}][block_stock]" id="bs_${rowIndex}">
  <label class="btn btn-outline-danger btn-sm px-2 py-1" for="bs_${rowIndex}" title="Block Stock" style="font-size: 11px; font-weight: 600;">B</label>

  <input type="checkbox" class="btn-check" name="items[${rowIndex}][hold_stock]" id="hs_${rowIndex}">
  <label class="btn btn-outline-warning btn-sm px-2 py-1" for="hs_${rowIndex}" title="Hold Stock" style="font-size: 11px; font-weight: 600;">H</label>
</div>
</td>

<td>
<select name="items[${rowIndex}][quality_clearance]" class="form-select form-select-sm qc-select qc-select-pending" onchange="this.className='form-select form-select-sm qc-select qc-select-'+this.value">
<option value="pending">🟡 Pending</option>
<option value="approved">🟢 Approved</option>
<option value="rejected">🔴 Rejected</option>
</select>
</td>

<td>
<div class="d-flex gap-1 justify-content-center">
<button type="button" class="btn btn-sm btn-outline-danger removeRow" title="Remove"><i class="bi bi-trash"></i></button>
<button type="button" class="btn btn-sm btn-info text-white duplicateRow shadow-sm" title="Duplicate Row"><i class="bi bi-files"></i></button>
</div>
<input type="hidden" name="items[${rowIndex}][sap_batch]">
<input type="hidden" name="items[${rowIndex}][vendor_batch]">
<input type="hidden" name="items[${rowIndex}][ibd_no]">
<input type="hidden" name="items[${rowIndex}][po_no]">
<input type="hidden" name="items[${rowIndex}][mfg_date]">
<input type="hidden" name="items[${rowIndex}][expiry_date]">
</td>
</tr>`);

                rowIndex++;
            }

            /* ADD ROW BUTTON */
            addRowBtn.onclick = () => {
                addRow();
            };

            /* Enter key adds new row */
            document.addEventListener('keydown', e => {
                if (e.key === 'Enter' && e.target.closest('#itemsTable')) {
                    e.preventDefault();
                    addRow();
                }
            });

            /* Default first row */
            addRow();

            let productDropdown = null;
            let activeInput = null;

            function showProductDropdown(input) {
                hideProductDropdown();
                activeInput = input;
                const rect = input.getBoundingClientRect();
                const dropdown = document.createElement('div');
                dropdown.className = 'product-search-dropdown';
                dropdown.style.top = (rect.bottom + 4) + 'px';
                dropdown.style.left = Math.max(10, rect.left) + 'px';
                dropdown.style.width = Math.max(280, rect.width) + 'px';
                document.body.appendChild(dropdown);
                productDropdown = dropdown;
                renderProductItems(dropdown, '');
            }

            function hideProductDropdown() {
                if (productDropdown) {
                    productDropdown.remove();
                    productDropdown = null;
                }
                activeInput = null;
            }

            function renderProductItems(dropdown, filter) {
                const val = filter.toLowerCase();
                const filtered = filter
                    ? products.filter(p => p.name.toLowerCase().includes(val) || p.item_code.toLowerCase().includes(val))
                    : products;
                dropdown.innerHTML = filtered.map(p =>
                    `<div class="product-search-item" data-id="${p.id}" data-pack="${p.pack_size}" data-cartons="${p.cartons_per_pallet || ''}">${p.item_code} - ${p.name}</div>`
                ).join('');
            }

            /* SHOW DROPDOWN ON FOCUS */
            document.addEventListener('focusin', e => {
                if (!e.target.classList.contains('product-input')) return;
                showProductDropdown(e.target);
            });

            /* FILTER ON INPUT */
            document.addEventListener('input', e => {
                if (!e.target.classList.contains('product-input')) return;
                if (!productDropdown) return;
                renderProductItems(productDropdown, e.target.value);
            });

            /* SELECT PRODUCT */
            document.addEventListener('mousedown', e => {
                if (e.target.classList.contains('product-search-item')) {
                    e.preventDefault();
                    activeRow = e.target.closest('tr') || (activeInput ? activeInput.closest('tr') : null);
                    if (!activeRow) return;
                    activeRow.querySelector('.product-id').value = e.target.dataset.id;
                    activeRow.querySelector('.pack-size').value = e.target.dataset.pack;
                    activeRow.querySelector('.product-input').value = e.target.textContent;
                    hideProductDropdown();

                    const cartonsPerPallet = e.target.dataset.cartons || '';
                    activeRow.querySelector('.pallets-per-packing').value = cartonsPerPallet;
                    const palletsInput = activeRow.querySelector('.pallets-used');
                    if (cartonsPerPallet) {
                        const units = Number(activeRow.querySelector('.units').value || 0);
                        palletsInput.value = units > 0 ? Math.ceil(units / Number(cartonsPerPallet)) : '';
                        palletsInput.placeholder = 'Auto (' + cartonsPerPallet + ' ctn/pallet)';
                        palletsInput.readOnly = false;
                    } else {
                        palletsInput.placeholder = 'Enter pallets';
                        palletsInput.readOnly = false;
                    }

                    batchModal.show();
                }
            });

            /* HIDE DROPDOWN ON CLICK OUTSIDE */
            document.addEventListener('mousedown', e => {
                if (e.target.classList.contains('product-search-item')) return;
                if (productDropdown && !productDropdown.contains(e.target) && !e.target.classList.contains('product-input')) {
                    hideProductDropdown();
                }
            });

            /* Click Batch button to show batch modal */
            document.addEventListener('click', e => {
                let btn = e.target.closest('.open-batch-btn');
                if (btn) {
                    activeRow = btn.closest('tr');
                    if(activeRow.querySelector('.product-id').value) {
                        document.querySelector('.modal-sap').value = activeRow.querySelector('[name$="[sap_batch]"]').value;
                        document.querySelector('.modal-vendor').value = activeRow.querySelector('[name$="[vendor_batch]"]').value;
                        document.querySelector('.modal-po').value = activeRow.querySelector('[name$="[po_no]"]').value;
                        document.querySelector('.modal-ibd').value = activeRow.querySelector('[name$="[ibd_no]"]').value;
                        document.querySelector('.modal-mfg').value = activeRow.querySelector('[name$="[mfg_date]"]').value;
                        document.querySelector('.modal-expiry').value = activeRow.querySelector('[name$="[expiry_date]"]').value;
                        batchModal.show();
                    } else {
                        Swal.fire('Info', 'Please select a product first.', 'info');
                    }
                }
            });

            /* SAVE MODAL */
            saveBatchBtn.onclick = () => {
                if (!activeRow) return;
                activeRow.querySelector('[name$="[sap_batch]"]').value = document.querySelector('.modal-sap').value;
                activeRow.querySelector('[name$="[vendor_batch]"]').value = document.querySelector('.modal-vendor').value;
                activeRow.querySelector('[name$="[po_no]"]').value = document.querySelector('.modal-po').value;
                activeRow.querySelector('[name$="[ibd_no]"]').value = document.querySelector('.modal-ibd').value;
                activeRow.querySelector('[name$="[mfg_date]"]').value = document.querySelector('.modal-mfg').value;
                activeRow.querySelector('[name$="[expiry_date]"]').value = document.querySelector('.modal-expiry').value;
                batchModal.hide();
            };

            /* TOTAL + PALLETS AUTO-CALC */
            document.addEventListener('input', e => {
                if (!e.target.classList.contains('units')) return;
                const row = e.target.closest('tr');
                const units = +e.target.value || 0;
                const pack = +row.querySelector('.pack-size').value || 0;
                row.querySelector('.total-qty').value = (units * pack).toFixed(0);

                // Auto-recalculate pallets: ceil(units / cartons_per_pallet)
                const cartonsPerPallet = Number(row.querySelector('.pallets-per-packing').value || 0);
                if (cartonsPerPallet > 0 && units > 0) {
                    row.querySelector('.pallets-used').value = Math.ceil(units / cartonsPerPallet);
                }
            });

            /* REMOVE & DUPLICATE */
            document.addEventListener('click', e => {
                let removeBtn = e.target.closest('.removeRow');
                if (removeBtn) {
                    removeBtn.closest('tr').remove();
                }

                let dupBtn = e.target.closest('.duplicateRow');
                if (dupBtn) {
                    const row = dupBtn.closest('tr');
                    
                    // Create new row
                    addRow();
                    
                    // Find the newly added row (last row in table)
                    const allRows = itemsTable.querySelectorAll('tbody tr');
                    const newRow = allRows[allRows.length - 1];

                    // Copy values
                    newRow.querySelector('.product-input').value = row.querySelector('.product-input').value;
                    newRow.querySelector('.product-id').value = row.querySelector('.product-id').value;
                    newRow.querySelector('.units').value = row.querySelector('.units').value;
                    newRow.querySelector('.pack-size').value = row.querySelector('.pack-size').value;
                    newRow.querySelector('.total-qty').value = row.querySelector('.total-qty').value;
                    newRow.querySelector('.pallets-used').value = row.querySelector('.pallets-used').value;
                    newRow.querySelector('.pallets-per-packing').value = row.querySelector('.pallets-per-packing').value;
                    
                    newRow.querySelector('[name$="[sound_stock]"]').checked = row.querySelector('[name$="[sound_stock]"]').checked;
                    newRow.querySelector('[name$="[block_stock]"]').checked = row.querySelector('[name$="[block_stock]"]').checked;
                    newRow.querySelector('[name$="[hold_stock]"]').checked = row.querySelector('[name$="[hold_stock]"]').checked;
                    
                    const qcSelect = newRow.querySelector('.qc-select');
                    qcSelect.value = row.querySelector('.qc-select').value;
                    qcSelect.className = 'form-select form-select-sm qc-select qc-select-' + qcSelect.value;
                    
                    newRow.querySelector('[name$="[sap_batch]"]').value = row.querySelector('[name$="[sap_batch]"]').value;
                    newRow.querySelector('[name$="[vendor_batch]"]').value = row.querySelector('[name$="[vendor_batch]"]').value;
                    newRow.querySelector('[name$="[ibd_no]"]').value = row.querySelector('[name$="[ibd_no]"]').value;
                    newRow.querySelector('[name$="[po_no]"]').value = row.querySelector('[name$="[po_no]"]').value;
                    newRow.querySelector('[name$="[mfg_date]"]').value = row.querySelector('[name$="[mfg_date]"]').value;
                    newRow.querySelector('[name$="[expiry_date]"]').value = row.querySelector('[name$="[expiry_date]"]').value;
                }
            });

            /* AUTO-ADD ROW when last row gets a product */
            document.addEventListener('mousedown', e => {
                if (!e.target.classList.contains('product-search-item')) return;

                const allRows = document.querySelectorAll('#itemsTable tbody tr');
                const lastRow = allRows[allRows.length - 1];
                const clickedRow = e.target.closest('tr') || (activeInput ? activeInput.closest('tr') : null);

                // If user filled the last row, add a new one
                if (clickedRow === lastRow && warehouseSelect.value) {
                    setTimeout(() => addRow(), 100);
                }
            });

            /* AJAX FORM SUBMISSION */
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
                        submitBtn.innerHTML = 'Save Inbound';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'An error occurred while communicating with the server.',
                    });
                });
            });
        });
    </script>
@endpush
