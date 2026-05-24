@extends('layouts.app')

@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h4 class="fw-bold mb-1">Import Products</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 13px;">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('product.index') }}" class="text-decoration-none">Products</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Import</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('product.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('error'))
    <div class="col-12">
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <i class="bi bi-x-circle me-1"></i> {{ session('error') }}
        </div>
    </div>
    @endif

    <div class="col-md-8">
        <div class="card">
            <div class="card-header border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0">Upload CSV File</h6>
            </div>
            <div class="card-body px-3">
                <form action="{{ route('product.import.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        <small class="text-muted">Upload a CSV file with product data. <a href="{{ route('product.import.template') }}" class="text-decoration-none">Download template</a></small>
                        @error('csv_file')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">CSV Format Requirements</label>
                        <div style="font-size: 13px; background: #f8fafc; padding: 1rem; border-radius: 8px;">
                            <p class="mb-2 fw-semibold">Required headers:</p>
                            <code style="font-size: 12px;">Item Code, Name, Category, Group, UOM, Packing Type, Pack Size, Cartons Per Pallet, Status</code>
                            <hr class="my-2">
                            <p class="mb-1 fw-semibold">Reference data (must exist in system):</p>
                            <ul class="mb-0" style="font-size: 12px;">
                                <li><strong>Category</strong> — must match an existing Product Category name</li>
                                <li><strong>Group</strong> — must match an existing Product Group name</li>
                                <li><strong>UOM</strong> — must match an existing UOM name</li>
                                <li><strong>Packing Type</strong> — must match an existing Packing Type name</li>
                            </ul>
                            <hr class="my-2">
                            <p class="mb-0" style="font-size: 12px;">
                                <strong>Note:</strong> Duplicate <code>Item Code</code> rows are skipped.
                                Status should be <code>Active</code> or <code>Inactive</code>.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i> Import
                        </button>
                        <a href="{{ route('product.import.template') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-download me-1"></i> Download Template
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header border-bottom-0 pt-3 px-3">
                <h6 class="fw-semibold mb-0">Instructions</h6>
            </div>
            <div class="card-body px-3" style="font-size: 13px;">
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Download the CSV template</li>
                    <li class="mb-2">Fill in your product data</li>
                    <li class="mb-2">Ensure all reference names (Category, Group, UOM, Packing Type) match existing records</li>
                    <li class="mb-2">Save as CSV (Comma delimited)</li>
                    <li>Upload and import</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
