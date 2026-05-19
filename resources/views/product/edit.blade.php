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
                    <a href="{{ route('product.index') }}" class="text-decoration-none">Products</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold">Edit Product</h5>
        <small class="text-muted">Update product details, packing & unit configuration</small>
    </div>
</div>

{{-- Card --}}
<div class="card border-0 shadow-lg rounded-4">
    <div class="card-body p-4">

        <form method="POST" action="{{ route('product.update', $product->id) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">

                {{-- Item Code --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Item Code</label>
                    <input type="text"
                           name="item_code"
                           value="{{ old('item_code', $product->item_code) }}"
                           class="form-control rounded-pill @error('item_code') is-invalid @enderror"
                           required>
                    @error('item_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $product->name) }}"
                           class="form-control rounded-pill @error('name') is-invalid @enderror"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Category --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Product Category</label>
                    <select name="product_category_id"
                            class="form-select rounded-pill @error('product_category_id') is-invalid @enderror"
                            required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ old('product_category_id', $product->product_category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Group --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Product Group</label>
                    <select name="product_group_id"
                            class="form-select rounded-pill @error('product_group_id') is-invalid @enderror"
                            required>
                        <option value="">Select Group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}"
                                {{ old('product_group_id', $product->product_group_id) == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_group_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- UOM --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">UOM</label>
                    <select name="uom_id"
                            class="form-select rounded-pill @error('uom_id') is-invalid @enderror"
                            required>
                        <option value="">Select UOM</option>
                        @foreach($uoms as $uom)
                            <option value="{{ $uom->id }}"
                                {{ old('uom_id', $product->uom_id) == $uom->id ? 'selected' : '' }}>
                                {{ $uom->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('uom_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Packing Type --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Packing Type</label>
                    <select name="packing_type_id"
                            class="form-select rounded-pill @error('packing_type_id') is-invalid @enderror"
                            required>
                        <option value="">Select Packing</option>
                        @foreach($packingTypes as $packing)
                            <option value="{{ $packing->id }}"
                                {{ old('packing_type_id', $product->packing_type_id) == $packing->id ? 'selected' : '' }}>
                                {{ $packing->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('packing_type_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Pack Size --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Pack Size</label>
                    <input type="number"
                           name="pack_size"
                           value="{{ old('pack_size', $product->pack_size) }}"
                           class="form-control rounded-pill @error('pack_size') is-invalid @enderror"
                           min="1"
                           required>
                    <small class="text-muted ms-2">Units per packing</small>
                    @error('pack_size')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Cartons Per Pallet --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Cartons Per Pallet <span class="text-muted fw-normal">(optional)</span></label>
                    <div class="input-group">
                        <span class="input-group-text rounded-start-pill"><i class="bi bi-layers"></i></span>
                        <input type="number"
                               name="cartons_per_pallet"
                               value="{{ old('cartons_per_pallet', $product->cartons_per_pallet) }}"
                               class="form-control rounded-end-pill @error('cartons_per_pallet') is-invalid @enderror"
                               min="1"
                               placeholder="e.g. 20">
                    </div>
                    <small class="text-muted ms-2">How many cartons fit on 1 pallet</small>
                    @error('cartons_per_pallet')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               role="switch"
                               name="status"
                               value="1"
                               id="statusSwitch"
                               {{ old('status', $product->status) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="statusSwitch">
                            Active
                        </label>
                    </div>
                </div>

            </div>

            {{-- Actions --}}
            <div class="d-flex justify-content-start gap-2 mt-4">
                <a href="{{ route('product.index') }}"
                   class="btn btn-outline-secondary rounded-pill px-4">
                    Cancel
                </a>

                <button type="submit"
                        class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Update
                </button>
            </div>

        </form>

    </div>
</div>

@endsection
