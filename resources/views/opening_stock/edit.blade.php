@extends('layouts.app')

@section('content')

<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h6 class="mb-0">Edit Opening Stock Item</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('opening-stock.update', $item->id) }}" id="editOpeningStockForm">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <!-- Warehouse Selection -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold small">Warehouse</label>
                    <select name="warehouse_id" id="warehouse_id" class="form-select form-select-sm" required>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ $item->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Product Selection -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold small">Product</label>
                    <select name="product_id" id="product_id" class="form-select form-select-sm" required>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" 
                                    data-pack="{{ $p->pack_size }}" 
                                    data-cartons="{{ $p->cartons_per_pallet ?? '' }}"
                                    {{ $item->product_id == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->item_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Quantities -->
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold small">Units Received</label>
                    <input type="number" min="1" name="units_received" id="units_received" class="form-control form-control-sm" value="{{ $item->units_received }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold small">Pack Size</label>
                    <input type="text" id="pack_size" class="form-control form-control-sm" value="{{ $item->pack_size_snapshot }}" readonly>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold small">Total Quantity</label>
                    <input type="text" id="total_quantity" class="form-control form-control-sm" value="{{ $item->total_quantity }}" readonly>
                </div>

                <!-- Pallets -->
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold small">Pallets Used</label>
                    <input type="number" min="0" name="pallets_used" id="pallets_used" class="form-control form-control-sm" value="{{ $item->pallets_used }}" placeholder="Auto">
                </div>

                <!-- Quality Clearance -->
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold small">Quality Clearance</label>
                    <select name="quality_clearance" class="form-select form-select-sm">
                        <option value="pending" {{ $item->quality_clearance == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $item->quality_clearance == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ $item->quality_clearance == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <!-- Status Checkboxes -->
                <div class="col-md-4 mb-3 align-self-end">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="sound_stock" id="sound_stock" value="1" {{ $item->sound_stock ? 'checked' : '' }}>
                        <label class="form-check-label small" for="sound_stock">Sound Stock</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="block_stock" id="block_stock" value="1" {{ $item->block_stock ? 'checked' : '' }}>
                        <label class="form-check-label small" for="block_stock">Blocked</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="hold_stock" id="hold_stock" value="1" {{ $item->hold_stock ? 'checked' : '' }}>
                        <label class="form-check-label small" for="hold_stock">Hold</label>
                    </div>
                </div>

                <!-- Batches & Dates -->
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold small">SAP Batch</label>
                    <input type="text" name="sap_batch" class="form-control form-control-sm" value="{{ $item->sap_batch }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold small">Vendor Batch</label>
                    <input type="text" name="vendor_batch" class="form-control form-control-sm" value="{{ $item->vendor_batch }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold small">IBD No</label>
                    <input type="text" name="ibd_no" class="form-control form-control-sm" value="{{ $item->ibd_no }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold small">PO No</label>
                    <input type="text" name="po_no" class="form-control form-control-sm" value="{{ $item->po_no }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold small">MFG Date</label>
                    <input type="date" name="mfg_date" class="form-control form-control-sm" value="{{ $item->mfg_date ? (is_string($item->mfg_date) ? substr($item->mfg_date, 0, 10) : $item->mfg_date->format('Y-m-d')) : '' }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold small">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control form-control-sm" value="{{ $item->expiry_date ? (is_string($item->expiry_date) ? substr($item->expiry_date, 0, 10) : $item->expiry_date->format('Y-m-d')) : '' }}">
                </div>

                <!-- Remarks -->
                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold small">Remarks</label>
                    <textarea name="remarks" class="form-control form-control-sm" rows="3">{{ $item->remarks }}</textarea>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm">Update Opening Stock</button>
                <a href="{{ route('opening-stock.index') }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const productSelect = document.getElementById('product_id');
    const unitsInput = document.getElementById('units_received');
    const packInput = document.getElementById('pack_size');
    const totalInput = document.getElementById('total_quantity');
    const palletsInput = document.getElementById('pallets_used');

    function updateCalculations() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        if (!selectedOption) return;
        const pack = parseFloat(selectedOption.dataset.pack || 0);
        const cartonsPerPallet = parseFloat(selectedOption.dataset.cartons || 0);
        const units = parseFloat(unitsInput.value || 0);

        packInput.value = pack;
        totalInput.value = (units * pack).toFixed(4);

        if (cartonsPerPallet > 0 && units > 0) {
            palletsInput.value = Math.ceil(units / cartonsPerPallet);
            palletsInput.placeholder = `Auto (${cartonsPerPallet} ctn/pallet)`;
        } else {
            palletsInput.placeholder = 'Enter pallets';
        }
    }

    productSelect.addEventListener('change', updateCalculations);
    unitsInput.addEventListener('input', updateCalculations);

    // Initial run
    updateCalculations();

    // Form Submission
    document.getElementById('editOpeningStockForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

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
            submitBtn.innerHTML = 'Update Opening Stock';

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
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: res.body.message || 'Updated successfully!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = "{{ route('opening-stock.index') }}";
                });
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Update Opening Stock';
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
