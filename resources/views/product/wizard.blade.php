@extends('layouts.app')

@section('content')

<style>
/* Custom Stepper Progress Bar styling matching reference design */
.stepper-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
}

.stepper-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    padding: 0.5rem 1rem;
}

.stepper-wrapper::before {
    content: '';
    position: absolute;
    top: 22px;
    left: 7%;
    right: 7%;
    height: 3px;
    background-color: #e2e8f0;
    z-index: 1;
}

.stepper-progress-line {
    position: absolute;
    top: 22px;
    left: 7%;
    height: 3px;
    background-color: #3b302a;
    z-index: 1;
    transition: width 0.4s ease;
    width: 0%;
}

.step-item {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    background: transparent;
}

.step-circle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background-color: #ffffff;
    border: 3px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    color: #64748b;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.step-item.active .step-circle {
    background-color: #3b302a;
    border-color: #3b302a;
    color: #ffffff;
    transform: scale(1.08);
    box-shadow: 0 4px 12px rgba(59, 48, 42, 0.3);
}

.step-item.completed .step-circle {
    background-color: #ffffff;
    border-color: #3b302a;
    color: #3b302a;
}

.step-label {
    margin-top: 8px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #94a3b8;
    transition: color 0.3s ease;
}

.step-item.active .step-label,
.step-item.completed .step-label {
    color: #3b302a;
}

.btn-stepper-primary {
    background-color: #3b302a;
    border-color: #3b302a;
    color: #ffffff;
    font-weight: 600;
    padding: 8px 24px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-stepper-primary:hover {
    background-color: #27201c;
    border-color: #27201c;
    color: #ffffff;
}

.btn-stepper-outline {
    border: 2px solid #cbd5e1;
    color: #475569;
    font-weight: 600;
    padding: 7px 20px;
    border-radius: 6px;
}

.btn-stepper-outline:hover {
    background-color: #f8fafc;
    border-color: #94a3b8;
    color: #1e293b;
}

.wizard-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.05);
    background: #ffffff;
}

/* Compact Scrollable Master Tables with Sticky Header */
.master-table-container {
    max-height: 220px;
    overflow-y: auto;
    border-radius: 8px;
}

.master-table-container::-webkit-scrollbar {
    width: 6px;
}
.master-table-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.master-table-container table thead th {
    position: sticky;
    top: 0;
    background-color: #f8fafc;
    z-index: 2;
    box-shadow: inset 0 -1px 0 #e2e8f0;
}
</style>

{{-- Breadcrumbs & Header --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('product.index') }}" class="text-decoration-none">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page">Setup Wizard</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold text-dark">Master Setup Wizard</h4>
        <small class="text-muted">Manage product masters or create a new product step-by-step</small>
    </div>

    <div>
        <button type="button" class="btn btn-outline-dark rounded-pill px-3 shadow-sm btn-sm fw-semibold" id="btnDirectProduct">
            <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Direct Product Creation
        </button>
        <a href="{{ route('product.index') }}" class="btn btn-light btn-sm rounded-pill px-3 border ms-2">
            <i class="bi bi-x-circle me-1"></i> Cancel
        </a>
    </div>
</div>

{{-- Stepper Progress Bar --}}
<div class="card stepper-card border-0 mb-3 p-3">
    <div class="stepper-wrapper">
        <div class="stepper-progress-line" id="stepperLine"></div>

        <div class="step-item active" data-step="1" onclick="jumpToStep(1)">
            <div class="step-circle" id="circle-1">1</div>
            <div class="step-label">Category</div>
        </div>

        <div class="step-item" data-step="2" onclick="jumpToStep(2)">
            <div class="step-circle" id="circle-2">2</div>
            <div class="step-label">Group</div>
        </div>

        <div class="step-item" data-step="3" onclick="jumpToStep(3)">
            <div class="step-circle" id="circle-3">3</div>
            <div class="step-label">UOM</div>
        </div>

        <div class="step-item" data-step="4" onclick="jumpToStep(4)">
            <div class="step-circle" id="circle-4">4</div>
            <div class="step-label">Packing Type</div>
        </div>

        <div class="step-item" data-step="5" onclick="jumpToStep(5)">
            <div class="step-circle" id="circle-5">5</div>
            <div class="step-label">Product Details</div>
        </div>
    </div>
</div>

{{-- Wizard Main Container --}}
<div class="card wizard-card p-3">
    <div class="card-body py-2">

        {{-- STEP 1: PRODUCT CATEGORY MANAGEMENT --}}
        <div class="wizard-step-panel" id="step-panel-1">
            <div class="text-center mb-3">
                <h5 class="fw-bold text-dark mb-1">Step 1: Product Category Management</h5>
                <p class="text-muted small mb-0">Add new product categories or delete existing ones</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-9">
                    {{-- Creation Box --}}
                    <div class="card bg-light border-0 p-3 rounded-3 mb-3">
                        <label class="form-label fw-bold mb-1 small">+ Create New Category</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="new_category_name" placeholder="Enter new category name (e.g. Skin Care, Beverages)...">
                            <button class="btn btn-dark" type="button" onclick="saveNewCategory()">
                                <i class="bi bi-plus-circle me-1"></i> Add Category
                            </button>
                        </div>
                        <div id="category_msg" class="mt-1 small"></div>
                    </div>

                    {{-- Existing Categories Table --}}
                    <div class="border rounded-3 p-3 bg-white mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-tags me-2 text-primary"></i>Existing Categories</h6>
                            <span class="badge bg-light text-dark border" id="cat-count-badge">{{ count($categories) }} Items</span>
                        </div>
                        <div class="master-table-container border">
                            <table class="table table-hover table-sm align-middle mb-0" id="categoriesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Category Name</th>
                                        <th width="90">Status</th>
                                        <th width="70" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categories as $idx => $cat)
                                        <tr id="cat-row-{{ $cat->id }}">
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="fw-semibold text-dark">{{ $cat->name }}</td>
                                            <td><span class="badge bg-success" style="font-size:10px;">Active</span></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteCategory({{ $cat->id }})" title="Delete Category">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="no-categories-row"><td colspan="4" class="text-center text-muted py-3">No categories found. Add one above.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-stepper-outline btn-sm" onclick="skipStep(1)">
                            Skip Step <i class="bi bi-chevron-double-right ms-1"></i>
                        </button>
                        <button type="button" class="btn btn-stepper-primary btn-sm shadow-sm" onclick="nextStep(1)">
                            Continue <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- STEP 2: PRODUCT GROUP MANAGEMENT --}}
        <div class="wizard-step-panel d-none" id="step-panel-2">
            <div class="text-center mb-3">
                <h5 class="fw-bold text-dark mb-1">Step 2: Product Group Management</h5>
                <p class="text-muted small mb-0">Add new product groups or delete existing ones</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-9">
                    {{-- Creation Box --}}
                    <div class="card bg-light border-0 p-3 rounded-3 mb-3">
                        <label class="form-label fw-bold mb-1 small">+ Create New Product Group</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="new_group_name" placeholder="Enter new group name (e.g. Group A, Soaps)...">
                            <button class="btn btn-dark" type="button" onclick="saveNewGroup()">
                                <i class="bi bi-plus-circle me-1"></i> Add Group
                            </button>
                        </div>
                        <div id="group_msg" class="mt-1 small"></div>
                    </div>

                    {{-- Existing Groups Table --}}
                    <div class="border rounded-3 p-3 bg-white mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-collection me-2 text-primary"></i>Existing Product Groups</h6>
                            <span class="badge bg-light text-dark border" id="grp-count-badge">{{ count($groups) }} Items</span>
                        </div>
                        <div class="master-table-container border">
                            <table class="table table-hover table-sm align-middle mb-0" id="groupsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Group Name</th>
                                        <th width="90">Status</th>
                                        <th width="70" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($groups as $idx => $grp)
                                        <tr id="grp-row-{{ $grp->id }}">
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="fw-semibold text-dark">{{ $grp->name }}</td>
                                            <td><span class="badge bg-success" style="font-size:10px;">Active</span></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteGroup({{ $grp->id }})" title="Delete Group">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="no-groups-row"><td colspan="4" class="text-center text-muted py-3">No product groups found. Add one above.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-stepper-outline btn-sm" onclick="prevStep(2)">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </button>
                        <div>
                            <button type="button" class="btn btn-stepper-outline btn-sm me-2" onclick="skipStep(2)">
                                Skip Step <i class="bi bi-chevron-double-right ms-1"></i>
                            </button>
                            <button type="button" class="btn btn-stepper-primary btn-sm shadow-sm" onclick="nextStep(2)">
                                Continue <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- STEP 3: UOM MANAGEMENT --}}
        <div class="wizard-step-panel d-none" id="step-panel-3">
            <div class="text-center mb-3">
                <h5 class="fw-bold text-dark mb-1">Step 3: Unit of Measure (UOM) Management</h5>
                <p class="text-muted small mb-0">Add new UOMs (e.g. Kg, Carton, Piece, Bag) or delete existing ones</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-9">
                    {{-- Creation Box --}}
                    <div class="card bg-light border-0 p-3 rounded-3 mb-3">
                        <label class="form-label fw-bold mb-1 small">+ Create New UOM</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="new_uom_name" placeholder="Enter new UOM name (e.g. Kg, Carton, Piece)...">
                            <button class="btn btn-dark" type="button" onclick="saveNewUom()">
                                <i class="bi bi-plus-circle me-1"></i> Add UOM
                            </button>
                        </div>
                        <div id="uom_msg" class="mt-1 small"></div>
                    </div>

                    {{-- Existing UOMs Table --}}
                    <div class="border rounded-3 p-3 bg-white mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-rulers me-2 text-primary"></i>Existing UOMs</h6>
                            <span class="badge bg-light text-dark border" id="uom-count-badge">{{ count($uoms) }} Items</span>
                        </div>
                        <div class="master-table-container border">
                            <table class="table table-hover table-sm align-middle mb-0" id="uomsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>UOM Name</th>
                                        <th width="90">Status</th>
                                        <th width="70" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($uoms as $idx => $uom)
                                        <tr id="uom-row-{{ $uom->id }}">
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="fw-semibold text-dark">{{ $uom->name }}</td>
                                            <td><span class="badge bg-success" style="font-size:10px;">Active</span></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteUom({{ $uom->id }})" title="Delete UOM">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="no-uoms-row"><td colspan="4" class="text-center text-muted py-3">No UOMs found. Add one above.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-stepper-outline btn-sm" onclick="prevStep(3)">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </button>
                        <div>
                            <button type="button" class="btn btn-stepper-outline btn-sm me-2" onclick="skipStep(3)">
                                Skip Step <i class="bi bi-chevron-double-right ms-1"></i>
                            </button>
                            <button type="button" class="btn btn-stepper-primary btn-sm shadow-sm" onclick="nextStep(3)">
                                Continue <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- STEP 4: PACKING TYPE MANAGEMENT --}}
        <div class="wizard-step-panel d-none" id="step-panel-4">
            <div class="text-center mb-3">
                <h5 class="fw-bold text-dark mb-1">Step 4: Packing Type Management</h5>
                <p class="text-muted small mb-0">Add new packing types (e.g. Bag, Box, Drum, Bottle) or delete existing ones</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-9">
                    {{-- Creation Box --}}
                    <div class="card bg-light border-0 p-3 rounded-3 mb-3">
                        <label class="form-label fw-bold mb-1 small">+ Create New Packing Type</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="new_packing_type_name" placeholder="Enter new packing type (e.g. Box, Bag, Drum)...">
                            <button class="btn btn-dark" type="button" onclick="saveNewPackingType()">
                                <i class="bi bi-plus-circle me-1"></i> Add Packing Type
                            </button>
                        </div>
                        <div id="packing_type_msg" class="mt-1 small"></div>
                    </div>

                    {{-- Existing Packing Types Table --}}
                    <div class="border rounded-3 p-3 bg-white mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Existing Packing Types</h6>
                            <span class="badge bg-light text-dark border" id="pack-count-badge">{{ count($packingTypes) }} Items</span>
                        </div>
                        <div class="master-table-container border">
                            <table class="table table-hover table-sm align-middle mb-0" id="packingTypesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Packing Type Name</th>
                                        <th width="90">Status</th>
                                        <th width="70" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($packingTypes as $idx => $pack)
                                        <tr id="pack-row-{{ $pack->id }}">
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="fw-semibold text-dark">{{ $pack->name }}</td>
                                            <td><span class="badge bg-success" style="font-size:10px;">Active</span></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deletePackingType({{ $pack->id }})" title="Delete Packing Type">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="no-packing-row"><td colspan="4" class="text-center text-muted py-3">No packing types found. Add one above.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-stepper-outline btn-sm" onclick="prevStep(4)">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </button>
                        <div>
                            <button type="button" class="btn btn-stepper-outline btn-sm me-2" onclick="skipStep(4)">
                                Skip Step <i class="bi bi-chevron-double-right ms-1"></i>
                            </button>
                            <button type="button" class="btn btn-stepper-primary btn-sm shadow-sm" onclick="nextStep(4)">
                                Continue to Product <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- STEP 5: FINAL PRODUCT DETAILS --}}
        <div class="wizard-step-panel d-none" id="step-panel-5">
            <div class="text-center mb-3">
                <h5 class="fw-bold text-dark mb-1">Step 5: Product Details & Final Creation</h5>
                <p class="text-muted small mb-0">Select masters and fill in product details to complete creation</p>
            </div>

            <form id="finalProductForm" onsubmit="return false;">
                @csrf
                <div class="row g-3">
                    {{-- Item Code --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Item Code <span class="text-danger">*</span></label>
                        <input type="text" name="item_code" class="form-control form-control-sm rounded-3" placeholder="e.g. PRD-001" required>
                    </div>

                    {{-- Product Name / Description --}}
                    <div class="col-md-8">
                        <label class="form-label fw-bold small">Product Name / Description <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm rounded-3" placeholder="e.g. Lux Soap 125g Pack" required>
                    </div>

                    {{-- Category --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Product Category <span class="text-danger">*</span></label>
                        <select name="product_category_id" id="final_category_id" class="form-select form-select-sm rounded-3" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Group --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Product Group <span class="text-danger">*</span></label>
                        <select name="product_group_id" id="final_group_id" class="form-select form-select-sm rounded-3" required>
                            <option value="">Select Group</option>
                            @foreach($groups as $grp)
                                <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- UOM --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">UOM <span class="text-danger">*</span></label>
                        <select name="uom_id" id="final_uom_id" class="form-select form-select-sm rounded-3" required>
                            <option value="">Select UOM</option>
                            @foreach($uoms as $uom)
                                <option value="{{ $uom->id }}">{{ $uom->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Packing Type --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Packing Type <span class="text-danger">*</span></label>
                        <select name="packing_type_id" id="final_packing_type_id" class="form-select form-select-sm rounded-3" required>
                            <option value="">Select Packing Type</option>
                            @foreach($packingTypes as $pack)
                                <option value="{{ $pack->id }}">{{ $pack->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Pack Size --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Pack Size (Units/Ctn) <span class="text-danger">*</span></label>
                        <input type="number" name="pack_size" class="form-control form-control-sm rounded-3" min="1" value="1" placeholder="e.g. 12" required>
                    </div>

                    {{-- Cartons Per Pallet --}}
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Cartons Per Pallet <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="number" name="cartons_per_pallet" class="form-control form-control-sm rounded-3" min="1" placeholder="e.g. 20">
                    </div>

                    {{-- Status Switch --}}
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch p-2 ps-5 bg-light rounded-3 border w-100">
                            <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="status" value="1" checked id="productStatusSwitch" style="transform: scale(1.2); cursor: pointer;">
                            <label class="form-check-label fw-bold text-dark" for="productStatusSwitch" style="cursor: pointer;">
                                Status: <span id="productStatusText" class="badge bg-success ms-1">Active</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div id="product_error_msg" class="mt-2 text-danger small font-weight-bold"></div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-stepper-outline btn-sm" onclick="prevStep(5)">
                        <i class="bi bi-arrow-left me-1"></i> Back to Step 4
                    </button>
                    <button type="submit" class="btn btn-stepper-primary btn-sm shadow">
                        <i class="bi bi-check-circle-fill me-1"></i> Save & Finish Product Creation
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
let currentStep = 1;

function updateStepperUI() {
    const progressPct = ((currentStep - 1) / 4) * 84;
    document.getElementById('stepperLine').style.width = progressPct + '%';

    for (let i = 1; i <= 5; i++) {
        const item = document.querySelector(`.step-item[data-step="${i}"]`);
        const circle = document.getElementById(`circle-${i}`);
        const panel = document.getElementById(`step-panel-${i}`);

        item.classList.remove('active', 'completed');

        if (i < currentStep) {
            item.classList.add('completed');
            circle.innerHTML = '<i class="bi bi-check-lg"></i>';
        } else if (i === currentStep) {
            item.classList.add('active');
            circle.innerHTML = i;
        } else {
            circle.innerHTML = i;
        }

        if (panel) {
            if (i === currentStep) {
                panel.classList.remove('d-none');
            } else {
                panel.classList.add('d-none');
            }
        }
    }
}

function nextStep(step) {
    if (step < 5) {
        currentStep = step + 1;
        updateStepperUI();
    }
}

function prevStep(step) {
    if (step > 1) {
        currentStep = step - 1;
        updateStepperUI();
    }
}

function skipStep(step) {
    nextStep(step);
}

function jumpToStep(step) {
    currentStep = step;
    updateStepperUI();
}

document.getElementById('btnDirectProduct').addEventListener('click', function() {
    jumpToStep(5);
});

// Product Status Toggle Switch Handler
const statusSwitch = document.getElementById('productStatusSwitch');
if (statusSwitch) {
    statusSwitch.addEventListener('change', function() {
        const badge = document.getElementById('productStatusText');
        if (this.checked) {
            badge.className = 'badge bg-success ms-1';
            badge.innerText = 'Active';
        } else {
            badge.className = 'badge bg-secondary ms-1';
            badge.innerText = 'Inactive';
        }
    });
}

// Toast notification helper
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true
});

// Category - Save AJAX
function saveNewCategory() {
    const nameInput = document.getElementById('new_category_name');
    const msgDiv = document.getElementById('category_msg');
    const name = nameInput.value.trim();

    if (!name) {
        msgDiv.className = 'mt-1 small text-danger font-weight-bold';
        msgDiv.innerText = 'Please enter category name';
        return;
    }

    fetch('{{ route("product.wizard.category") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ name: name })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            msgDiv.className = 'mt-1 small text-success font-weight-bold';
            msgDiv.innerText = res.message;
            nameInput.value = '';

            Toast.fire({ icon: 'success', title: res.message });

            const tbody = document.querySelector('#categoriesTable tbody');
            const noRow = document.getElementById('no-categories-row');
            if (noRow) noRow.remove();

            const rowCount = tbody.rows.length + 1;
            const tr = document.createElement('tr');
            tr.id = `cat-row-${res.category.id}`;
            tr.innerHTML = `
                <td>${rowCount}</td>
                <td class="fw-semibold text-dark">${res.category.name}</td>
                <td><span class="badge bg-success" style="font-size:10px;">Active</span></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteCategory(${res.category.id})" title="Delete Category">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);

            const optFinal = new Option(res.category.name, res.category.id, true, true);
            document.getElementById('final_category_id').add(optFinal);
        } else {
            msgDiv.className = 'mt-1 small text-danger font-weight-bold';
            msgDiv.innerText = res.message || 'Error saving category';
        }
    })
    .catch(err => {
        msgDiv.className = 'mt-1 small text-danger font-weight-bold';
        msgDiv.innerText = 'Category name might already exist or server error.';
    });
}

// Category - Delete AJAX
function deleteCategory(id) {
    if (!confirm('Are you sure you want to delete this category?')) return;

    fetch(`/products/wizard/category/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Toast.fire({ icon: 'success', title: res.message });
            const row = document.getElementById(`cat-row-${id}`);
            if (row) row.remove();

            const select = document.getElementById('final_category_id');
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value == id) {
                    select.remove(i);
                    break;
                }
            }
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not delete category' });
        }
    })
    .catch(err => Swal.fire({ icon: 'error', title: 'Action Failed', text: 'Error deleting category. It might be linked to existing products.' }));
}

// Group - Save AJAX
function saveNewGroup() {
    const nameInput = document.getElementById('new_group_name');
    const msgDiv = document.getElementById('group_msg');
    const name = nameInput.value.trim();

    if (!name) {
        msgDiv.className = 'mt-1 small text-danger font-weight-bold';
        msgDiv.innerText = 'Please enter group name';
        return;
    }

    fetch('{{ route("product.wizard.group") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ name: name })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            msgDiv.className = 'mt-1 small text-success font-weight-bold';
            msgDiv.innerText = res.message;
            nameInput.value = '';

            Toast.fire({ icon: 'success', title: res.message });

            const tbody = document.querySelector('#groupsTable tbody');
            const noRow = document.getElementById('no-groups-row');
            if (noRow) noRow.remove();

            const rowCount = tbody.rows.length + 1;
            const tr = document.createElement('tr');
            tr.id = `grp-row-${res.group.id}`;
            tr.innerHTML = `
                <td>${rowCount}</td>
                <td class="fw-semibold text-dark">${res.group.name}</td>
                <td><span class="badge bg-success" style="font-size:10px;">Active</span></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteGroup(${res.group.id})" title="Delete Group">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);

            const optFinal = new Option(res.group.name, res.group.id, true, true);
            document.getElementById('final_group_id').add(optFinal);
        } else {
            msgDiv.className = 'mt-1 small text-danger font-weight-bold';
            msgDiv.innerText = res.message || 'Error saving group';
        }
    })
    .catch(err => {
        msgDiv.className = 'mt-1 small text-danger font-weight-bold';
        msgDiv.innerText = 'Group name might already exist or server error.';
    });
}

// Group - Delete AJAX
function deleteGroup(id) {
    if (!confirm('Are you sure you want to delete this group?')) return;

    fetch(`/products/wizard/group/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Toast.fire({ icon: 'success', title: res.message });
            const row = document.getElementById(`grp-row-${id}`);
            if (row) row.remove();

            const select = document.getElementById('final_group_id');
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value == id) {
                    select.remove(i);
                    break;
                }
            }
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not delete group' });
        }
    })
    .catch(err => Swal.fire({ icon: 'error', title: 'Action Failed', text: 'Error deleting group. It might be linked to existing products.' }));
}

// UOM - Save AJAX
function saveNewUom() {
    const nameInput = document.getElementById('new_uom_name');
    const msgDiv = document.getElementById('uom_msg');
    const name = nameInput.value.trim();

    if (!name) {
        msgDiv.className = 'mt-1 small text-danger font-weight-bold';
        msgDiv.innerText = 'Please enter UOM name';
        return;
    }

    fetch('{{ route("product.wizard.uom") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ name: name })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            msgDiv.className = 'mt-1 small text-success font-weight-bold';
            msgDiv.innerText = res.message;
            nameInput.value = '';

            Toast.fire({ icon: 'success', title: res.message });

            const tbody = document.querySelector('#uomsTable tbody');
            const noRow = document.getElementById('no-uoms-row');
            if (noRow) noRow.remove();

            const rowCount = tbody.rows.length + 1;
            const tr = document.createElement('tr');
            tr.id = `uom-row-${res.uom.id}`;
            tr.innerHTML = `
                <td>${rowCount}</td>
                <td class="fw-semibold text-dark">${res.uom.name}</td>
                <td><span class="badge bg-success" style="font-size:10px;">Active</span></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteUom(${res.uom.id})" title="Delete UOM">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);

            const optFinal = new Option(res.uom.name, res.uom.id, true, true);
            document.getElementById('final_uom_id').add(optFinal);
        } else {
            msgDiv.className = 'mt-1 small text-danger font-weight-bold';
            msgDiv.innerText = res.message || 'Error saving UOM';
        }
    })
    .catch(err => {
        msgDiv.className = 'mt-1 small text-danger font-weight-bold';
        msgDiv.innerText = 'UOM name might already exist or server error.';
    });
}

// UOM - Delete AJAX
function deleteUom(id) {
    if (!confirm('Are you sure you want to delete this UOM?')) return;

    fetch(`/products/wizard/uom/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Toast.fire({ icon: 'success', title: res.message });
            const row = document.getElementById(`uom-row-${id}`);
            if (row) row.remove();

            const select = document.getElementById('final_uom_id');
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value == id) {
                    select.remove(i);
                    break;
                }
            }
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not delete UOM' });
        }
    })
    .catch(err => Swal.fire({ icon: 'error', title: 'Action Failed', text: 'Error deleting UOM. It might be linked to existing products.' }));
}

// Packing Type - Save AJAX
function saveNewPackingType() {
    const nameInput = document.getElementById('new_packing_type_name');
    const msgDiv = document.getElementById('packing_type_msg');
    const name = nameInput.value.trim();

    if (!name) {
        msgDiv.className = 'mt-1 small text-danger font-weight-bold';
        msgDiv.innerText = 'Please enter packing type name';
        return;
    }

    fetch('{{ route("product.wizard.packing-type") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ name: name })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            msgDiv.className = 'mt-1 small text-success font-weight-bold';
            msgDiv.innerText = res.message;
            nameInput.value = '';

            Toast.fire({ icon: 'success', title: res.message });

            const tbody = document.querySelector('#packingTypesTable tbody');
            const noRow = document.getElementById('no-packing-row');
            if (noRow) noRow.remove();

            const rowCount = tbody.rows.length + 1;
            const tr = document.createElement('tr');
            tr.id = `pack-row-${res.packingType.id}`;
            tr.innerHTML = `
                <td>${rowCount}</td>
                <td class="fw-semibold text-dark">${res.packingType.name}</td>
                <td><span class="badge bg-success" style="font-size:10px;">Active</span></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deletePackingType(${res.packingType.id})" title="Delete Packing Type">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);

            const optFinal = new Option(res.packingType.name, res.packingType.id, true, true);
            document.getElementById('final_packing_type_id').add(optFinal);
        } else {
            msgDiv.className = 'mt-1 small text-danger font-weight-bold';
            msgDiv.innerText = res.message || 'Error saving packing type';
        }
    })
    .catch(err => {
        msgDiv.className = 'mt-1 small text-danger font-weight-bold';
        msgDiv.innerText = 'Packing Type name might already exist or server error.';
    });
}

// Packing Type - Delete AJAX
function deletePackingType(id) {
    if (!confirm('Are you sure you want to delete this packing type?')) return;

    fetch(`/products/wizard/packing-type/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Toast.fire({ icon: 'success', title: res.message });
            const row = document.getElementById(`pack-row-${id}`);
            if (row) row.remove();

            const select = document.getElementById('final_packing_type_id');
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value == id) {
                    select.remove(i);
                    break;
                }
            }
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not delete packing type' });
        }
    })
    .catch(err => Swal.fire({ icon: 'error', title: 'Action Failed', text: 'Error deleting packing type. It might be linked to existing products.' }));
}

// Final Product Submit
document.getElementById('finalProductForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const errorDiv = document.getElementById('product_error_msg');
    errorDiv.innerText = '';

    // Show loading spinner on button
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Saving Product...';

    fetch('{{ route("product.wizard.product") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;

        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Product Created Successfully!',
                text: res.message,
                timer: 1800,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                window.location.href = res.redirect;
            });
        } else {
            errorDiv.innerText = res.message || 'Failed to create product. Check all fields.';
            Swal.fire({
                icon: 'error',
                title: 'Product Creation Failed',
                text: res.message || 'Please check all required fields.',
                confirmButtonColor: '#d33'
            });
        }
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        errorDiv.innerText = 'Item Code may already exist or required fields are missing.';
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Item Code may already exist or required fields are missing.',
            confirmButtonColor: '#d33'
        });
    });
});
</script>
@endpush
