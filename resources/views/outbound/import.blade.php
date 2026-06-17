@extends('layouts.app')

@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h4 class="fw-bold mb-1">Import Dispatch / Outbound</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 13px;">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('outbound.index') }}" class="text-decoration-none">Outbound</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Import</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('outbound.index') }}" class="btn btn-sm btn-outline-secondary">
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
                <h6 class="fw-semibold mb-0">Upload Dispatch CSV</h6>
            </div>
            <div class="card-body px-3">
                <form action="{{ route('outbound.import.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Source Warehouse <small class="text-muted">(optional — leave empty to pick from all warehouses)</small></label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        <small class="text-muted">Upload a CSV file. <a href="{{ route('outbound.import.template') }}">Download template</a></small>
                        @error('csv_file')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">CSV Format</label>
                        <div style="font-size: 13px; background: #f8fafc; padding: 1rem; border-radius: 8px;">
                            <p class="mb-2 fw-semibold">Required headers:</p>
                            <code style="font-size: 12px;">Item Code, Units Dispatched, Type (sale/transfer), PO, IBD, STO, Pallets, Customer, Remarks</code>
                            <hr class="my-2">
                            <p class="mb-1 fw-semibold">Notes:</p>
                            <ul class="mb-0" style="font-size: 12px;">
                                <li><strong>Item Code</strong> must exist in Products</li>
                                <li><strong>Units Dispatched</strong> — number of cartons to dispatch</li>
                                <li><strong>Type</strong> — <code>sale</code> or <code>transfer</code></li>
                                <li><strong>System uses FIFO</strong> to pick from available stock</li>
                                <li><strong>Dates</strong>: YYYY-MM-DD format</li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i> Import
                        </button>
                        <a href="{{ route('outbound.import.template') }}" class="btn btn-outline-secondary">
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
                    <li class="mb-2">Optionally select a source warehouse</li>
                    <li class="mb-2">Download the CSV template</li>
                    <li class="mb-2">Fill product Item Codes and dispatch quantities</li>
                    <li class="mb-2">Save as CSV and upload</li>
                    <li class="mb-2">System picks stock FIFO and creates outbound record</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection