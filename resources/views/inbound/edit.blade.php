@extends('layouts.app')

@section('content')
    <form method="POST" action="{{ route('inbound.update', $stockIn) }}" id="inboundEditForm">
        @csrf
        @method('PUT')

        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Edit Inbound #{{ $stockIn->id }}</h6>
                <a href="{{ route('inbound.invoice', $stockIn) }}" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-control form-control-sm" required>
                            <option value="">Select</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}" {{ $stockIn->warehouse_id == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-control form-control-sm">
                            <option value="">Select</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}" {{ $stockIn->vendor_id == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Arrived From</label>
                        <select name="arrived_from_id" class="form-control form-control-sm">
                            <option value="">Select</option>
                            @foreach ($arrivedFroms as $a)
                                <option value="{{ $a->id }}" {{ $stockIn->arrived_from_id == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Transporter</label>
                        <select name="transporter_id" class="form-control form-control-sm">
                            <option value="">Select</option>
                            @foreach ($transporters as $t)
                                <option value="{{ $t->id }}" {{ $stockIn->transporter_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Shipment Type</label>
                        <select name="shipment_type" class="form-select form-select-sm">
                            <option value="manual" {{ $stockIn->shipment_type == 'manual' ? 'selected' : '' }}>Manual</option>
                            <option value="auto" {{ $stockIn->shipment_type == 'auto' ? 'selected' : '' }}>Auto</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Invoice No</label>
                        <input name="dispatched_invoice_no" value="{{ $stockIn->dispatched_invoice_no }}" class="form-control form-control-sm" readonly>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Dispatcher Sig</label>
                        <input name="dispatcher_sig" value="{{ $stockIn->dispatcher_sig }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Picker</label>
                        <input name="picker" value="{{ $stockIn->picker }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">PO No</label>
                        <input name="po_no" value="{{ $stockIn->po_no }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">IBD No</label>
                        <input name="ibd_no" value="{{ $stockIn->ibd_no }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Shipment No</label>
                        <input name="shipment_no" value="{{ $stockIn->shipment_no }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">STO No</label>
                        <input name="sto_no" value="{{ $stockIn->sto_no }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Delivery No</label>
                        <input name="delivery_no" value="{{ $stockIn->delivery_no }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vehicle No</label>
                        <input name="vehicle_no" value="{{ $stockIn->vehicle_no }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vehicle Size</label>
                        <input name="vehicle_size" value="{{ $stockIn->vehicle_size }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Driver Name</label>
                        <input name="driver_name" value="{{ $stockIn->driver_name }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Driver Mobile</label>
                        <input name="driver_mobile" value="{{ $stockIn->driver_mobile }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vehicle In</label>
                        <input type="datetime-local" name="vehicle_in_time" value="{{ $stockIn->vehicle_in_time }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vehicle Out</label>
                        <input type="datetime-local" name="vehicle_out_time" value="{{ $stockIn->vehicle_out_time }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">Remarks</label>
                        <input name="remarks" value="{{ $stockIn->remarks }}" class="form-control form-control-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= ITEMS ================= --}}
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <h6 class="mb-0">Inbound Products</h6>
                <button type="button" id="addRowBtn" class="btn btn-sm btn-success">+ Add Row</button>
            </div>

            <div class="card-body p-0">
                <div class="table-wrapper">
                    <table class="table table-bordered table-sm mb-0" id="itemsTable">
                        <thead>
                            <tr>
                                <th width="260">Product</th>
                                <th>Units</th>
                                <th>Pack</th>
                                <th>Total</th>
                                <th>Pallets</th>
                                <th>Status</th>
                                <th>QC</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary">Update Inbound</button>
            <a href="{{ route('inbound.invoice', $stockIn) }}" class="btn btn-secondary">Back</a>
        </div>

    </form>

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
            const groupedItems = @json($groupedItems);
            const batchModal = new bootstrap.Modal(document.getElementById('batchModal'));

            /* ADD ROW FUNCTION */
            function addRow(data = null) {
                let isDispatched = data ? data.is_dispatched : false;
                let lockMsg = isDispatched ? '<br><small class="text-danger">Dispatched: Cannot reduce units/pallets</small>' : '';
                let unitsReadOnly = isDispatched ? 'min="' + data.dispatched_quantity + '"' : ''; // Just a simple html5 constraint
                
                itemsTable.querySelector('tbody').insertAdjacentHTML('beforeend', `
<tr>
<td>
<div class="product-search-wrap">
<input class="form-control form-control-sm product-input" placeholder="Search product" autocomplete="off" value="${data ? data.item_code + ' - ' + data.product_name : ''}" ${isDispatched ? 'readonly' : ''}>
<input type="hidden" name="items[${rowIndex}][product_id]" class="product-id" value="${data ? data.product_id : ''}">
<input type="hidden" name="items[${rowIndex}][split_ids]" value="${data ? data.split_ids : ''}">
${lockMsg}
</div>
</td>

<td><input name="items[${rowIndex}][units_received]" class="form-control form-control-sm units" value="${data ? data.units_received : ''}" ${unitsReadOnly}></td>
<td><input class="form-control form-control-sm pack-size" value="${data ? data.pack_size : ''}" readonly></td>
<td><input class="form-control form-control-sm total-qty" value="${data ? data.total_quantity : ''}" readonly></td>

<td>
<input name="items[${rowIndex}][pallets_used]" class="form-control form-control-sm pallets-used" placeholder="Auto" value="${data && data.pallets_used > 0 ? data.pallets_used : ''}" ${isDispatched ? 'readonly' : ''}>
<input type="hidden" name="items[${rowIndex}][use_pallets]" value="1">
<input type="hidden" class="pallets-per-packing" value="${data ? data.cartons_per_pallet || '' : ''}">
</td>

<td>
<label><input type="checkbox" name="items[${rowIndex}][sound_stock]" ${!data || data.sound_stock ? 'checked' : ''}> S</label>
<label><input type="checkbox" name="items[${rowIndex}][block_stock]" ${data && data.block_stock ? 'checked' : ''}> B</label>
<label><input type="checkbox" name="items[${rowIndex}][hold_stock]" ${data && data.hold_stock ? 'checked' : ''}> H</label>
</td>

<td>
<select name="items[${rowIndex}][quality_clearance]" class="form-control form-control-sm qc-select qc-select-${data ? data.quality_clearance : 'pending'}" onchange="this.className='form-control form-control-sm qc-select qc-select-'+this.value">
<option value="pending" ${data && data.quality_clearance == 'pending' ? 'selected' : ''}>🟡 Pending</option>
<option value="approved" ${data && data.quality_clearance == 'approved' ? 'selected' : ''}>🟢 Approved</option>
<option value="rejected" ${data && data.quality_clearance == 'rejected' ? 'selected' : ''}>🔴 Rejected</option>
</select>
</td>

<td>
${isDispatched ? '<button type="button" class="btn btn-sm btn-secondary" disabled title="Cannot delete dispatched item">×</button>' : '<button type="button" class="btn btn-sm btn-danger removeRow">×</button>'}
</td>

<input type="hidden" name="items[${rowIndex}][sap_batch]" value="${data && data.sap_batch ? data.sap_batch : ''}">
<input type="hidden" name="items[${rowIndex}][vendor_batch]" value="${data && data.vendor_batch ? data.vendor_batch : ''}">
<input type="hidden" name="items[${rowIndex}][ibd_no]" value="${data && data.ibd_no ? data.ibd_no : ''}">
<input type="hidden" name="items[${rowIndex}][po_no]" value="${data && data.po_no ? data.po_no : ''}">
<input type="hidden" name="items[${rowIndex}][mfg_date]" value="${data && data.mfg_date ? data.mfg_date : ''}">
<input type="hidden" name="items[${rowIndex}][expiry_date]" value="${data && data.expiry_date ? data.expiry_date : ''}">
</tr>`);

                rowIndex++;
            }

            /* ADD ROW BUTTON */
            addRowBtn.onclick = () => {
                addRow();
            };

            /* Prepopulate existing items */
            if (groupedItems && groupedItems.length > 0) {
                groupedItems.forEach(item => addRow(item));
            } else {
                addRow();
            }

            let productDropdown = null;
            let activeInput = null;

            function showProductDropdown(input) {
                if (input.readOnly) return; // don't show dropdown for dispatched locked inputs
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
                    if (cartonsPerPallet && !palletsInput.readOnly) {
                        const units = Number(activeRow.querySelector('.units').value || 0);
                        palletsInput.value = units > 0 ? Math.ceil(units / Number(cartonsPerPallet)) : '';
                        palletsInput.placeholder = 'Auto (' + cartonsPerPallet + ' ctn/pallet)';
                        palletsInput.readOnly = false;
                    } else if (!palletsInput.readOnly) {
                        palletsInput.placeholder = 'Enter pallets';
                        palletsInput.readOnly = false;
                    }

                    batchModal.show();
                }
            });

            /* Double click to show batch modal for prepopulated fields */
            document.addEventListener('dblclick', e => {
                if (e.target.classList.contains('product-input')) {
                    activeRow = e.target.closest('tr');
                    if(activeRow.querySelector('.product-id').value) {
                        document.querySelector('.modal-sap').value = activeRow.querySelector('[name$="[sap_batch]"]').value;
                        document.querySelector('.modal-vendor').value = activeRow.querySelector('[name$="[vendor_batch]"]').value;
                        document.querySelector('.modal-po').value = activeRow.querySelector('[name$="[po_no]"]').value;
                        document.querySelector('.modal-ibd').value = activeRow.querySelector('[name$="[ibd_no]"]').value;
                        document.querySelector('.modal-mfg').value = activeRow.querySelector('[name$="[mfg_date]"]').value;
                        document.querySelector('.modal-expiry').value = activeRow.querySelector('[name$="[expiry_date]"]').value;
                        batchModal.show();
                    }
                }
            });

            /* HIDE DROPDOWN ON CLICK OUTSIDE */
            document.addEventListener('mousedown', e => {
                if (e.target.classList.contains('product-search-item')) return;
                if (productDropdown && !productDropdown.contains(e.target) && !e.target.classList.contains('product-input')) {
                    hideProductDropdown();
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
                const palletsInput = row.querySelector('.pallets-used');
                if(!palletsInput.readOnly) {
                    const cartonsPerPallet = Number(row.querySelector('.pallets-per-packing').value || 0);
                    if (cartonsPerPallet > 0 && units > 0) {
                        palletsInput.value = Math.ceil(units / cartonsPerPallet);
                    }
                }
            });

            /* REMOVE */
            document.addEventListener('click', e => {
                if (e.target.classList.contains('removeRow')) e.target.closest('tr').remove();
            });

            /* AJAX FORM SUBMISSION */
            document.getElementById('inboundEditForm').addEventListener('submit', function(e) {
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
                        submitBtn.innerHTML = 'Update Inbound';
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
                            text: res.body.message || 'Updated successfully!',
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
                        submitBtn.innerHTML = 'Update Inbound';
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
    </style>

