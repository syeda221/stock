@extends('layouts.app')

@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>Import Opening Stock</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 13px;">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('opening-stock.index') }}" class="text-decoration-none">Opening Stock</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Import</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('opening-stock.index') }}" class="btn btn-sm btn-outline-secondary rounded-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    @if(session('error'))
    <div class="col-12">
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-3">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-octagon-fill text-danger fs-5 mt-1"></i>
                <div>
                    <div class="fw-bold">Import Failed</div>
                    <div>{!! session('error') !!}</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(session('success'))
    <div class="col-12">
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <div class="fw-semibold">{!! session('success') !!}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom pt-3 pb-3 px-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cloud-upload text-primary me-2"></i>Upload Opening Stock CSV</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('opening-stock.import.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Warehouse <small class="text-muted">(leave empty for auto-assignment)</small></label>
                        <select name="warehouse_id" class="form-select rounded-3 py-2">
                            <option value="">Auto-Assign (find available space)</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-secondary">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control rounded-3 py-2" accept=".csv,.txt" required>
                        <div class="form-text mt-2">
                            Upload a CSV file containing your opening inventory items.
                            <a href="{{ route('opening-stock.import.template') }}" class="fw-semibold text-primary text-decoration-none">
                                <i class="bi bi-download me-1"></i>Download Template
                            </a>
                        </div>
                        @error('csv_file')
                            <div class="text-danger small mt-1"><i class="bi bi-x-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-secondary">CSV Format & Requirements</label>
                        <div class="p-3 bg-light rounded-3 border" style="font-size: 13px;">
                            <p class="mb-2 fw-semibold text-dark"><i class="bi bi-info-circle me-1 text-primary"></i>Required & Supported Headers:</p>
                            <code class="d-block p-2 bg-white rounded border text-dark font-monospace" style="font-size: 12px; word-break: break-all;">
                                Item Code, Units Received, IBD, PO, SAP Batch, Vendor Batch, MFG Date, Expiry Date, Quality Check, Blocked, Hold, Remarks
                            </code>
                            <hr class="my-2">
                            <p class="mb-1 fw-semibold text-dark">Data Specifications:</p>
                            <ul class="mb-0 text-muted ps-3" style="font-size: 12.5px;">
                                <li><strong>Item Code</strong> — Must exist in product catalog</li>
                                <li><strong>Quality Check</strong> — <code>pending</code> / <code>approved</code> / <code>rejected</code></li>
                                <li><strong>Blocked / Hold</strong> — <code>Yes</code> or <code>No</code></li>
                                <li><strong>Dates</strong> — <code>YYYY-MM-DD</code> or <code>DD.MM.YYYY</code> format</li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-bold">
                            <i class="bi bi-upload me-1"></i> Upload & Import
                        </button>
                        <a href="{{ route('opening-stock.import.template') }}" class="btn btn-outline-secondary px-3 py-2 rounded-3">
                            <i class="bi bi-download me-1"></i> Template
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom pt-3 pb-3 px-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-primary me-2"></i>Instructions</h6>
            </div>
            <div class="card-body p-4" style="font-size: 13px;">
                <ol class="mb-0 ps-3 text-secondary">
                    <li class="mb-3">Select a target warehouse or choose <strong>Auto-Assign</strong> to fill space dynamically via FIFO.</li>
                    <li class="mb-3">Download the sample CSV template to ensure headers match.</li>
                    <li class="mb-3">Ensure product Item Codes exist in the Products Master.</li>
                    <li class="mb-3">Save file in CSV format (comma-separated).</li>
                    <li class="mb-0">Click <strong>Upload & Import</strong> to process stock.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
