@extends('layouts.app')

@section('content')

{{-- Breadcrumbs + Page Header --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('warehouse.index') }}" class="text-decoration-none">Warehouses</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Add</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold">Add Warehouse</h5>
        <small class="text-muted">Create a new warehouse and define its capacity</small>
    </div>
</div>

{{-- Card --}}
<div class="card border-0 shadow-lg rounded-4">
    <div class="card-body p-4">

        <form method="POST" action="{{ route('warehouse.store') }}">
            @csrf

            <div class="row g-3">

                {{-- Warehouse Name --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Warehouse Name</label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           class="form-control rounded-pill @error('name') is-invalid @enderror"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- City --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">City</label>
                    <input type="text"
                           name="city"
                           value="{{ old('city') }}"
                           class="form-control rounded-pill @error('city') is-invalid @enderror">
                    @error('city')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Location --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Location</label>
                    <input type="text"
                           name="location"
                           value="{{ old('location') }}"
                           class="form-control rounded-pill @error('location') is-invalid @enderror">
                    @error('location')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Capacity Mode --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Capacity Mode</label>
                    <select name="capacity_mode"
                            id="capacity_mode"
                            class="form-select rounded-pill @error('capacity_mode') is-invalid @enderror"
                            required>
                        <option value="manual">Manual Capacity</option>
                        <option value="row">Rows & Pallets</option>
                    </select>
                    @error('capacity_mode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Manual Capacity --}}
                <div class="col-md-4" id="manual_capacity_div">
                    <label class="form-label fw-semibold">Total Capacity (Pallets)</label>
                    <input type="number"
                           name="manual_capacity"
                           value="{{ old('manual_capacity') }}"
                           class="form-control rounded-pill @error('manual_capacity') is-invalid @enderror"
                           min="1">
                    @error('manual_capacity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            {{-- Rows Section --}}
            <div id="row_section" class="mt-4" style="display:none;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="mb-0 fw-bold">Warehouse Rows</h6>
                        <small class="text-muted">Define rows and pallet capacity per row</small>
                    </div>

                    <button type="button"
                            class="btn btn-sm btn-outline-secondary rounded-pill"
                            id="add_row">
                        <i class="bi bi-plus-lg me-1"></i> Add Row
                    </button>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="rows_table">
                            <thead class="table-primary">
                                <tr>
                                    <th>Row Name</th>
                                    <th style="width:220px;">Pallet Capacity</th>
                                    <th style="width:90px;" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="text"
                                               name="rows[0][row_name]"
                                               class="form-control rounded-pill"
                                               placeholder="e.g. Row A">
                                    </td>
                                    <td>
                                        <input type="number"
                                               name="rows[0][pallet_capacity]"
                                               class="form-control rounded-pill pallet-input"
                                               min="1"
                                               value="1">
                                    </td>
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger rounded-pill remove-row">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-3">
                    <span class="fw-semibold">Total Capacity:</span>
                    <span id="total_capacity_preview" class="fw-bold">1</span>
                    <span class="text-muted">pallets</span>
                </div>
            </div>

            {{-- Status --}}
            <div class="form-check form-switch mt-4">
                <input class="form-check-input"
                       type="checkbox"
                       role="switch"
                       name="status"
                       value="1"
                       id="statusSwitch"
                       checked>
                <label class="form-check-label fw-semibold" for="statusSwitch">
                    Active
                </label>
            </div>

            {{-- Actions --}}
            <div class="d-flex justify-content-start gap-2 mt-4">
                <a href="{{ route('warehouse.index') }}"
                   class="btn btn-outline-secondary rounded-pill px-4">
                    Cancel
                </a>

                <button type="submit"
                        class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-save me-1"></i> Save
                </button>
            </div>

        </form>

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const capacityMode = document.getElementById('capacity_mode');
    const manualDiv = document.getElementById('manual_capacity_div');
    const rowSection = document.getElementById('row_section');
    const rowsTableBody = document.querySelector('#rows_table tbody');
    const addRowBtn = document.getElementById('add_row');
    const totalPreview = document.getElementById('total_capacity_preview');

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('.pallet-input').forEach(input => {
            const val = parseInt(input.value || 0, 10);
            if (!isNaN(val)) total += val;
        });
        totalPreview.textContent = total;
    }

    function toggleCapacitySections() {
        if (capacityMode.value === 'manual') {
            manualDiv.style.display = '';
            rowSection.style.display = 'none';
        } else {
            manualDiv.style.display = 'none';
            rowSection.style.display = '';
            recalcTotal();
        }
    }

    capacityMode.addEventListener('change', toggleCapacitySections);

    addRowBtn.addEventListener('click', function () {
        const index = rowsTableBody.querySelectorAll('tr').length;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="text"
                       name="rows[${index}][row_name]"
                       class="form-control rounded-pill"
                       placeholder="e.g. Row B">
            </td>
            <td>
                <input type="number"
                       name="rows[${index}][pallet_capacity]"
                       class="form-control rounded-pill pallet-input"
                       min="1"
                       value="1">
            </td>
            <td class="text-end">
                <button type="button"
                        class="btn btn-sm btn-outline-danger rounded-pill remove-row">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;
        rowsTableBody.appendChild(tr);
        recalcTotal();
    });

    rowsTableBody.addEventListener('input', function (e) {
        if (e.target.classList.contains('pallet-input')) recalcTotal();
    });

    rowsTableBody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row');
        if (!btn) return;
        btn.closest('tr').remove();
        recalcTotal();
    });

    // Initial state
    toggleCapacitySections();
    recalcTotal();
});
</script>
@endpush

@endsection
