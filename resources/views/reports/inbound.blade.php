@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-arrow-down-circle me-2"></i>Inbound Reports</h4>
            <p class="text-muted mb-0">View and analyze inbound stock transactions</p>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('reports.inbound.export') }}" class="d-inline">
                @foreach(request()->except('_token') as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                </button>
            </form>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>Print Report
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Entries</h6>
                            <h3 class="mb-0">{{ $summary['total_entries'] }}</h3>
                        </div>
                        <div class="fs-1 opacity-25">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Quantity</h6>
                            <h3 class="mb-0">{{ number_format($summary['total_items'], 2) }}</h3>
                        </div>
                        <div class="fs-1 opacity-25">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Units</h6>
                            <h3 class="mb-0">{{ number_format($summary['total_units']) }}</h3>
                        </div>
                        <div class="fs-1 opacity-25">
                            <i class="bi bi-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="bi bi-clipboard-check me-2"></i>QC Status</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-warning fw-semibold">● Pending</small>
                        <span class="badge bg-warning text-dark">{{ $summary['qc_pending'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-success fw-semibold">● Approved</small>
                        <span class="badge bg-success">{{ $summary['qc_approved'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-danger fw-semibold">● Rejected</small>
                        <span class="badge bg-danger">{{ $summary['qc_rejected'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.inbound') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Vendor</label>
                        <select name="vendor_id" class="form-control">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Warehouse</label>
                        <select name="warehouse_id" class="form-control">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Invoice No</label>
                        <div class="position-relative">
                            <input type="text"
                                   id="invoice_search"
                                   name="invoice_no"
                                   class="form-control"
                                   placeholder="Search invoice..."
                                   value="{{ request('invoice_no') }}"
                                   autocomplete="off">
                            <div id="invoice_suggestions" class="dropdown-menu w-100" style="max-height: 300px; overflow-y: auto;"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">QC Status</label>
                        <select name="qc_status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('qc_status') == 'pending' ? 'selected' : '' }}>
                                🟡 Pending
                            </option>
                            <option value="approved" {{ request('qc_status') == 'approved' ? 'selected' : '' }}>
                                🟢 Approved
                            </option>
                            <option value="rejected" {{ request('qc_status') == 'rejected' ? 'selected' : '' }}>
                                🔴 Rejected
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search me-2"></i>Apply Filters
                        </button>
                        <a href="{{ route('reports.inbound') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Date</th>
                            <th class="text-nowrap">Invoice No</th>
                            <th class="text-nowrap">Vendor</th>
                            <th class="text-nowrap">Warehouse</th>
                            <th class="text-nowrap">Total Items</th>
                            <th class="text-nowrap">QC Status</th>
                            <th class="text-nowrap text-end">Total Qty</th>
                            <th class="text-nowrap text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockIns as $stockIn)
                        @php
                            $qcCounts = [
                                'pending' => $stockIn->items->where('quality_clearance', 'pending')->count(),
                                'approved' => $stockIn->items->where('quality_clearance', 'approved')->count(),
                                'rejected' => $stockIn->items->where('quality_clearance', 'rejected')->count(),
                            ];
                        @endphp
                        <tr>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($stockIn->created_at)->format('d/m/Y') }}</td>
                            <td class="fw-semibold">
                                @if($stockIn->dispatched_invoice_no)
                                    {{ $stockIn->dispatched_invoice_no }}
                                @elseif($stockIn->inbound_invoice_no)
                                    {{ $stockIn->inbound_invoice_no }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $stockIn->vendor->name ?? 'N/A' }}</td>
                            <td>{{ $stockIn->warehouse->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-primary">{{ $stockIn->items->count() }} batches</span>
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($qcCounts['pending'] > 0)
                                        <span class="badge bg-warning text-dark" title="Pending">
                                            ⏳ {{ $qcCounts['pending'] }}
                                        </span>
                                    @endif
                                    @if($qcCounts['approved'] > 0)
                                        <span class="badge bg-success" title="Approved">
                                            ✓ {{ $qcCounts['approved'] }}
                                        </span>
                                    @endif
                                    @if($qcCounts['rejected'] > 0)
                                        <span class="badge bg-danger" title="Rejected">
                                            ✗ {{ $qcCounts['rejected'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($stockIn->items->sum('total_quantity'), 2) }}</td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('inbound.invoice', $stockIn->id) }}" class="btn btn-outline-primary" title="View Invoice">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('reports.inbound.pdf', $stockIn->id) }}" class="btn btn-outline-danger" title="Download PDF" target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No inbound records found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($stockIns->hasPages())
        <div class="card-footer bg-white">
            {{ $stockIns->links() }}
        </div>
        @endif
    </div>
</div>

<style>
    @media print {
        .btn, .card-header, nav, .sidebar, .top-header, form {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }

    /* Autocomplete styles */
    #invoice_suggestions {
        position: absolute;
        z-index: 1000;
        border: 1px solid #dee2e6;
        max-width: 100%;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    #invoice_suggestions .dropdown-item {
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid #f1f3f5;
    }

    #invoice_suggestions .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    #invoice_suggestions .dropdown-item:last-child {
        border-bottom: none;
    }

    .invoice-label {
        font-weight: 600;
        color: #495057;
    }

    .invoice-meta {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.15em;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const invoiceInput = document.getElementById('invoice_search');
    const suggestionsDiv = document.getElementById('invoice_suggestions');
    let debounceTimer;

    if (!invoiceInput) return;

    // Debounced search function
    invoiceInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();

        clearTimeout(debounceTimer);

        if (searchTerm.length < 2) {
            suggestionsDiv.classList.remove('show');
            suggestionsDiv.innerHTML = '';
            return;
        }

        // Show loading state
        suggestionsDiv.innerHTML = '<div class="dropdown-item text-center"><span class="spinner-border spinner-border-sm" role="status"></span> Searching...</div>';
        suggestionsDiv.classList.add('show');

        debounceTimer = setTimeout(() => {
            fetch(`{{ route('reports.inbound.invoice.suggestions') }}?q=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        suggestionsDiv.innerHTML = '<div class="dropdown-item text-muted">No invoices found</div>';
                    } else {
                        suggestionsDiv.innerHTML = data.map(item => `
                            <a href="#" class="dropdown-item" data-invoice="${item.invoice}">
                                <div class="invoice-label">${item.invoice}</div>
                                <div class="invoice-meta">${item.vendor} • ${item.date}</div>
                            </a>
                        `).join('');

                        // Add click handlers to suggestions
                        suggestionsDiv.querySelectorAll('.dropdown-item').forEach(item => {
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                const invoice = this.dataset.invoice;
                                invoiceInput.value = invoice;
                                suggestionsDiv.classList.remove('show');
                                suggestionsDiv.innerHTML = '';
                            });
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                    suggestionsDiv.innerHTML = '<div class="dropdown-item text-danger">Error loading suggestions</div>';
                });
        }, 300);
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!invoiceInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.remove('show');
        }
    });

    // Show suggestions on focus if there's text
    invoiceInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            this.dispatchEvent(new Event('input'));
        }
    });
});
</script>
@endpush

@endsection

