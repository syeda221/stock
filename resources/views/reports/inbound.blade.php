@extends('layouts.app')

@section('content')

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
        --card-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.05);
        --card-hover-shadow: 0 10px 25px -3px rgba(15, 23, 42, 0.1);
    }

    .select2-container--default .select2-selection--multiple {
        border: 1px solid #cbd5e1 !important;
        min-height: 38px !important;
        border-radius: 8px !important;
        padding: 2px 6px;
        background-color: #ffffff;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #eff6ff !important;
        border: 1px solid #bfdbfe !important;
        color: #1e40af !important;
        border-radius: 6px !important;
        padding: 2px 8px !important;
        font-weight: 600;
        font-size: 12px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #1d4ed8 !important;
        margin-right: 5px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    }

    /* KPI CARD STYLING WITH PSEUDO LEFT BAR */
    .report-kpi-card {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: var(--card-shadow);
        transition: all 0.25s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
        padding: 1.2rem 1.2rem 1.2rem 1.6rem;
    }
    .report-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--card-hover-shadow);
    }

    .report-kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        width: 5px;
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }

    .kpi-accent-blue::before { background-color: #2563eb; }
    .kpi-accent-green::before { background-color: #10b981; }
    .kpi-accent-cyan::before { background-color: #06b6d4; }
    .kpi-accent-amber::before { background-color: #f59e0b; }

    .kpi-icon-wrapper {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .filter-card {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: var(--card-shadow);
    }

    .table-custom-header th {
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569 !important;
        font-weight: 700;
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    /* MINI QC STAT BOXES INSIDE CARD 4 */
    .qc-stat-box {
        border-radius: 8px;
        padding: 4px 6px;
        transition: transform 0.15s ease;
    }
    .qc-stat-box:hover {
        transform: scale(1.02);
    }
    .qc-box-pending { background-color: #fef3c7 !important; color: #92400e !important; border: 1px solid #fde68a !important; }
    .qc-box-approved { background-color: #d1fae5 !important; color: #065f46 !important; border: 1px solid #a7f3d0 !important; }
    .qc-box-rejected { background-color: #fee2e2 !important; color: #991b1b !important; border: 1px solid #fecaca !important; }

    .qc-pill {
        font-size: 11.5px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    .qc-pill-pending { background-color: #fef3c7 !important; color: #92400e !important; border: 1px solid #fde68a !important; }
    .qc-pill-approved { background-color: #d1fae5 !important; color: #065f46 !important; border: 1px solid #a7f3d0 !important; }
    .qc-pill-rejected { background-color: #fee2e2 !important; color: #991b1b !important; border: 1px solid #fecaca !important; }

    /* WAREHOUSE BADGE & BATCH BADGE FIX (ALWAYS DARK HIGH-CONTRAST TEXT) */
    .wh-badge {
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
        border: 1px solid #cbd5e1 !important;
        font-weight: 600;
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        text-shadow: none !important;
    }
    .wh-badge i {
        color: #2563eb !important;
    }

    .batch-badge {
        background-color: #e0e7ff !important;
        color: #3730a3 !important;
        border: 1px solid #c7d2fe !important;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        display: inline-block;
        text-shadow: none !important;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.15s ease;
    }

    .btn-gradient-primary {
        background: var(--primary-gradient);
        color: #ffffff !important;
        border: none;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }
    .btn-gradient-primary:hover {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        color: #ffffff !important;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
    }

    .btn-gradient-success {
        background: var(--success-gradient);
        color: #ffffff !important;
        border: none;
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
    }
    .btn-gradient-success:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: #ffffff !important;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
    }
</style>

<div class="container-fluid px-4 py-4">

    <!-- PAGE HEADER BAR -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2.5 py-1 rounded-pill" style="font-size: 11px;">REPORTS</span>
                <h4 class="mb-0 fw-bold" style="color: #0f172a;">Inbound Reports</h4>
            </div>
            <p class="text-secondary small mb-0">Search, filter, track and export all warehouse inbound stock receipts</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" action="{{ route('reports.inbound.export') }}" class="d-inline" id="exportForm">
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                <input type="hidden" name="vendor_id" value="{{ request('vendor_id') }}">
                <input type="hidden" name="warehouse_id" value="{{ request('warehouse_id') }}">
                <input type="hidden" name="qc_status" value="{{ request('qc_status') }}">
                @php
                    $reqInvoices = (array)(request('inbound_invoice') ?? request('invoice_no'));
                @endphp
                @foreach($reqInvoices as $invVal)
                    <input type="hidden" name="inbound_invoice[]" value="{{ $invVal }}">
                @endforeach
                <button type="submit" class="btn btn-gradient-success btn-sm px-3.5 py-2 rounded-3 font-semibold d-inline-flex align-items-center">
                    <i class="bi bi-file-earmark-excel-fill me-2 fs-6"></i>Export to Excel
                </button>
            </form>
            <button class="btn btn-outline-secondary btn-sm px-3.5 py-2 rounded-3 border bg-white text-dark font-semibold shadow-2xs d-inline-flex align-items-center" onclick="window.print()">
                <i class="bi bi-printer me-2 text-primary fs-6"></i>Print Report
            </button>
        </div>
    </div>

    <!-- SUMMARY KPI CARDS GRID -->
    <div class="row g-3 mb-4">
        <!-- Total Entries -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-kpi-card kpi-accent-blue">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="font-bold uppercase tracking-wider d-block mb-1" style="font-size: 11px; color: #64748b;">TOTAL ENTRIES</span>
                        <h3 class="mb-0 fw-bold" style="color: #0f172a;">{{ number_format($summary['total_entries']) }}</h3>
                    </div>
                    <div class="kpi-icon-wrapper" style="background-color: #eff6ff; color: #2563eb;">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Quantity -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-kpi-card kpi-accent-green">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="font-bold uppercase tracking-wider d-block mb-1" style="font-size: 11px; color: #64748b;">TOTAL QUANTITY (CTN)</span>
                        <h3 class="mb-0 fw-bold" style="color: #059669;">{{ number_format($summary['total_items'], 2) }}</h3>
                    </div>
                    <div class="kpi-icon-wrapper" style="background-color: #ecfdf5; color: #10b981;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Units -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-kpi-card kpi-accent-cyan">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="font-bold uppercase tracking-wider d-block mb-1" style="font-size: 11px; color: #64748b;">TOTAL UNITS</span>
                        <h3 class="mb-0 fw-bold" style="color: #0891b2;">{{ number_format($summary['total_units']) }}</h3>
                    </div>
                    <div class="kpi-icon-wrapper" style="background-color: #ecfeff; color: #06b6d4;">
                        <i class="bi bi-boxes"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- QC Status Overview -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="report-kpi-card kpi-accent-amber">
                <span class="font-bold uppercase tracking-wider d-block mb-2" style="font-size: 11px; color: #64748b;">
                    <i class="bi bi-shield-check me-1" style="color: #d97706;"></i>QC STATUS OVERVIEW
                </span>
                <div class="d-flex align-items-center justify-content-between gap-1 flex-nowrap">
                    <div class="qc-stat-box qc-box-pending text-center flex-fill">
                        <span class="d-block text-uppercase font-bold" style="font-size: 9px; opacity: 0.85;">Pending</span>
                        <span class="fw-bold fs-6">{{ $summary['qc_pending'] }}</span>
                    </div>
                    <div class="qc-stat-box qc-box-approved text-center flex-fill">
                        <span class="d-block text-uppercase font-bold" style="font-size: 9px; opacity: 0.85;">Approved</span>
                        <span class="fw-bold fs-6">{{ $summary['qc_approved'] }}</span>
                    </div>
                    <div class="qc-stat-box qc-box-rejected text-center flex-fill">
                        <span class="d-block text-uppercase font-bold" style="font-size: 9px; opacity: 0.85;">Rejected</span>
                        <span class="fw-bold fs-6">{{ $summary['qc_rejected'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER & MULTI-INVOICE SEARCH PANEL -->
    <div class="filter-card mb-4 overflow-hidden">
        <div class="card-header py-3 px-4 border-bottom d-flex align-items-center justify-content-between" style="background-color: #f8fafc;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-funnel-fill text-primary fs-6"></i>
                <h6 class="mb-0 fw-bold" style="color: #1e293b;">Filter & Multi-Invoice Search</h6>
            </div>
            @if(request()->anyFilled(['date_from', 'date_to', 'vendor_id', 'warehouse_id', 'inbound_invoice', 'invoice_no', 'qc_status']))
                <a href="{{ route('reports.inbound') }}" class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold p-0">
                    <i class="bi bi-x-circle-fill me-1"></i>Reset All Filters
                </a>
            @endif
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('reports.inbound') }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small fw-semibold mb-1" style="color: #475569;">Date From</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-slate-300 text-muted"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" name="date_from" class="form-control form-control-sm border-slate-300" value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small fw-semibold mb-1" style="color: #475569;">Date To</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-slate-300 text-muted"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" name="date_to" class="form-control form-control-sm border-slate-300" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small fw-semibold mb-1" style="color: #475569;">Vendor</label>
                        <select name="vendor_id" class="form-select form-select-sm border-slate-300">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small fw-semibold mb-1" style="color: #475569;">Warehouse</label>
                        <select name="warehouse_id" class="form-select form-select-sm border-slate-300">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- MULTIPLE INVOICE SELECTOR -->
                    <div class="col-12 col-md-7 col-lg-6">
                        <label class="form-label small fw-semibold mb-1 d-flex justify-content-between" style="color: #475569;">
                            <span><i class="bi bi-receipt me-1 text-primary"></i>Inbound / Dispatch Invoice #</span>
                            <span class="text-muted font-normal" style="font-size: 11px;">(Select multiple or type search)</span>
                        </label>
                        @php
                            $selectedInvoices = (array)(request('inbound_invoice') ?? request('invoice_no'));
                            if (count($selectedInvoices) == 1 && strpos($selectedInvoices[0], ',') !== false) {
                                $selectedInvoices = array_map('trim', explode(',', $selectedInvoices[0]));
                            }
                        @endphp
                        <select name="inbound_invoice[]" id="inbound_invoice_select" class="form-control form-control-sm select2-multi" multiple="multiple">
                            @foreach($inboundInvoicesList as $inv)
                                <option value="{{ $inv }}" {{ in_array($inv, $selectedInvoices) ? 'selected' : '' }}>
                                    {{ $inv }}
                                </option>
                            @endforeach
                            @foreach($selectedInvoices as $selInv)
                                @if(!in_array($selInv, $inboundInvoicesList->toArray()) && !empty($selInv))
                                    <option value="{{ $selInv }}" selected>{{ $selInv }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                        <label class="form-label small fw-semibold mb-1" style="color: #475569;">QC Clearance Status</label>
                        <select name="qc_status" class="form-select form-select-sm border-slate-300">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('qc_status') == 'pending' ? 'selected' : '' }}>🟡 Pending</option>
                            <option value="approved" {{ request('qc_status') == 'approved' ? 'selected' : '' }}>🟢 Approved</option>
                            <option value="rejected" {{ request('qc_status') == 'rejected' ? 'selected' : '' }}>🔴 Rejected</option>
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-md-2 col-lg-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-gradient-primary btn-sm flex-fill py-2 rounded-3 font-semibold d-inline-flex align-items-center justify-content-center">
                            <i class="bi bi-search me-1.5 fs-6"></i>Search Records
                        </button>
                        <a href="{{ route('reports.inbound') }}" class="btn btn-outline-secondary btn-sm py-2 px-3 rounded-3 border bg-white text-dark" title="Reset Filters">
                            <i class="bi bi-arrow-clockwise fs-6"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DATA TABLE CONTAINER -->
    <div class="card filter-card overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table text-primary fs-6"></i>
                <h6 class="mb-0 fw-bold" style="color: #1e293b;">Inbound Transactions List</h6>
            </div>
            <span class="badge border px-3 py-1.5 rounded-pill font-semibold" style="font-size: 11.5px; background: #f1f5f9; color: #334155;">
                Showing {{ $stockIns->count() }} of {{ $stockIns->total() }} Records
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-custom-header">
                        <tr>
                            <th style="width: 40px;" class="text-center"></th>
                            <th>Date & Time</th>
                            <th>Invoice No</th>
                            <th>Vendor</th>
                            <th>Warehouse</th>
                            <th>Item Code(s)</th>
                            <th>Product Description</th>
                            <th class="text-center">Batches</th>
                            <th>QC Status</th>
                            <th class="text-end">Total Qty</th>
                            <th class="text-center" style="width: 130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody style="border-top: none;">
                        @forelse($stockIns as $stockIn)
                        @php
                            $qcCounts = [
                                'pending' => $stockIn->items->where('quality_clearance', 'pending')->count(),
                                'approved' => $stockIn->items->where('quality_clearance', 'approved')->count(),
                                'rejected' => $stockIn->items->where('quality_clearance', 'rejected')->count(),
                            ];
                            $itemCodes = $stockIn->items->pluck('product.item_code')->filter()->unique()->implode(', ');
                            $productNames = $stockIn->items->pluck('product.name')->filter()->unique()->implode(', ');
                            $invNo = $stockIn->dispatched_invoice_no ?: ($stockIn->inbound_invoice_no ?: 'N/A');
                        @endphp
                        <tr class="align-middle">
                            <td class="text-center cursor-pointer" onclick="toggleRowItems({{ $stockIn->id }})">
                                <i id="toggle-icon-{{ $stockIn->id }}" class="bi bi-chevron-right text-muted fs-6"></i>
                            </td>
                            <td class="text-nowrap font-medium" style="font-size: 12.5px; color: #475569;">
                                {{ \Carbon\Carbon::parse($stockIn->created_at)->format('d.m.Y h:i A') }}
                            </td>
                            <td class="fw-bold" style="color: #2563eb;">
                                {{ $invNo }}
                            </td>
                            <td class="fw-semibold" style="color: #1e293b;">{{ $stockIn->vendor->name ?? 'N/A' }}</td>
                            <td>
                                <!-- WAREHOUSE BADGE WITH EXPLICIT HIGH CONTRAST DARK TEXT -->
                                <span class="wh-badge">
                                    <i class="bi bi-building me-1.5"></i>{{ $stockIn->warehouse->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td style="max-width:140px;" class="text-truncate text-secondary" title="{{ $itemCodes }}">
                                {{ $itemCodes ?: '-' }}
                            </td>
                            <td style="max-width:180px;" class="text-truncate text-secondary" title="{{ $productNames }}">
                                {{ $productNames ?: '-' }}
                            </td>
                            <td class="text-center">
                                <!-- BATCH BADGE WITH EXPLICIT DARK PURPLE TEXT -->
                                <span class="batch-badge">
                                    {{ $stockIn->items->count() }} batches
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($qcCounts['pending'] > 0)
                                        <span class="qc-pill qc-pill-pending" title="Pending QC">⏳ {{ $qcCounts['pending'] }}</span>
                                    @endif
                                    @if($qcCounts['approved'] > 0)
                                        <span class="qc-pill qc-pill-approved" title="Approved QC">✓ {{ $qcCounts['approved'] }}</span>
                                    @endif
                                    @if($qcCounts['rejected'] > 0)
                                        <span class="qc-pill qc-pill-rejected" title="Rejected QC">✗ {{ $qcCounts['rejected'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end fw-bold fs-6" style="color: #0f172a;">
                                {{ number_format($stockIn->items->sum('total_quantity'), 2) }}
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <!-- QUICK VIEW MODAL BUTTON -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary action-btn" 
                                            title="Quick View Details"
                                            onclick="openInboundModal({{ json_encode($stockIn) }})">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    <!-- DIRECT INVOICE PAGE -->
                                    <a href="{{ route('inbound.invoice', $stockIn->id) }}" 
                                       class="btn btn-sm btn-outline-secondary action-btn" 
                                       title="View Full Invoice" 
                                       target="_blank">
                                        <i class="bi bi-file-text-fill"></i>
                                    </a>
                                    <!-- PDF DOWNLOAD -->
                                    <a href="{{ route('reports.inbound.pdf', $stockIn->id) }}" 
                                       class="btn btn-sm btn-outline-danger action-btn" 
                                       title="Download PDF" 
                                       target="_blank">
                                        <i class="bi bi-file-pdf-fill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- EXPANDABLE INLINE BATCH BREAKDOWN -->
                        <tr id="row-items-{{ $stockIn->id }}" class="d-none" style="background-color: #f8fafc;">
                            <td colspan="11" class="p-3">
                                <div class="card border rounded-3 overflow-hidden shadow-2xs">
                                    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center" style="background:#f1f5f9; border-bottom: 1px solid #e2e8f0;">
                                        <span class="fw-bold small" style="color: #334155;">
                                            <i class="bi bi-list-task me-1 text-primary"></i>Batch Breakdown for Invoice: {{ $invNo }}
                                        </span>
                                        <span class="small text-muted">Created: {{ \Carbon\Carbon::parse($stockIn->created_at)->format('d.m.Y h:i A') }}</span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0 small">
                                            <thead style="background:#ffffff;">
                                                <tr>
                                                    <th class="ps-3">Item Code</th>
                                                    <th>Description</th>
                                                    <th>SAP Batch</th>
                                                    <th>Vendor Batch</th>
                                                    <th class="text-end">Units</th>
                                                    <th class="text-end">Qty</th>
                                                    <th class="text-end">Balance</th>
                                                    <th>MFG</th>
                                                    <th>Expiry</th>
                                                    <th>QC Clearance</th>
                                                    <th class="text-center">Days in WH</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($stockIn->items as $item)
                                                @php
                                                    $daysInWh = $item->created_at ? now()->startOfDay()->diffInDays($item->created_at->startOfDay()) : 0;
                                                @endphp
                                                <tr>
                                                    <td class="ps-3 fw-semibold text-primary">{{ $item->product->item_code ?? '-' }}</td>
                                                    <td class="fw-medium style="color: #1e293b;"">{{ $item->product->name ?? '-' }}</td>
                                                    <td><code>{{ $item->sap_batch ?? '-' }}</code></td>
                                                    <td><code>{{ $item->vendor_batch ?? '-' }}</code></td>
                                                    <td class="text-end fw-semibold">{{ number_format($item->units_received ?? 0) }}</td>
                                                    <td class="text-end fw-semibold">{{ number_format($item->total_quantity ?? 0, 2) }}</td>
                                                    <td class="text-end fw-semibold text-success" style="color: #059669;">{{ number_format($item->balance_quantity ?? 0, 2) }}</td>
                                                    <td>{{ $item->mfg_date ? \Carbon\Carbon::parse($item->mfg_date)->format('d.m.Y') : '-' }}</td>
                                                    <td>{{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('d.m.Y') : '-' }}</td>
                                                    <td>
                                                        @if($item->quality_clearance == 'approved')
                                                            <span class="qc-pill qc-pill-approved">Approved</span>
                                                        @elseif($item->quality_clearance == 'rejected')
                                                            <span class="qc-pill qc-pill-rejected">Rejected</span>
                                                        @else
                                                            <span class="qc-pill qc-pill-pending">Pending</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge border px-2" style="background:#e2e8f0; color:#334155;">{{ $daysInWh }}d</span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted opacity-50"></i>
                                <h6 class="fw-semibold text-dark">No Inbound Transactions Found</h6>
                                <p class="small text-muted mb-0">Try adjusting your date range or invoice filters.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($stockIns->hasPages())
        <div class="card-footer bg-white border-top py-3 px-4">
            {{ $stockIns->links() }}
        </div>
        @endif
    </div>
</div>

<!-- ========================================== -->
<!-- QUICK VIEW DETAIL POPUP MODAL (STANDARD UI) -->
<!-- ========================================== -->
<div class="modal fade" id="inboundDetailModal" tabindex="-1" aria-labelledby="inboundDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="inboundDetailModalLabel">
                        <i class="bi bi-box-arrow-in-down me-2 text-info"></i>Inbound Transaction Details
                    </h5>
                    <span class="small text-light opacity-75" id="modalSubHeader">Invoice Details</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                
                <!-- HEADER META GRID -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white p-3.5">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block font-semibold uppercase" style="font-size: 11px;">Inbound Invoice #</span>
                            <span class="fw-bold text-primary fs-6" id="m_invoice_no">-</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block font-semibold uppercase" style="font-size: 11px;">Transaction Date</span>
                            <span class="fw-semibold text-dark" id="m_date">-</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block font-semibold uppercase" style="font-size: 11px;">Vendor</span>
                            <span class="fw-semibold text-dark" id="m_vendor">-</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block font-semibold uppercase" style="font-size: 11px;">Warehouse</span>
                            <span class="fw-semibold text-dark" id="m_warehouse">-</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block font-semibold uppercase" style="font-size: 11px;">Gatepass No</span>
                            <span class="fw-medium text-secondary" id="m_gatepass">-</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block font-semibold uppercase" style="font-size: 11px;">Vehicle & Driver</span>
                            <span class="fw-medium text-secondary" id="m_vehicle">-</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block font-semibold uppercase" style="font-size: 11px;">Transporter</span>
                            <span class="fw-medium text-secondary" id="m_transporter">-</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block font-semibold uppercase" style="font-size: 11px;">Vehicle In / Out</span>
                            <span class="fw-medium text-secondary" id="m_vehicle_times">-</span>
                        </div>
                    </div>
                </div>

                <!-- ITEMS TABLE CARD -->
                <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-boxes text-primary me-2"></i>Item Batches List</h6>
                        <span class="badge bg-primary rounded-pill px-3 py-1" id="m_batch_count">0 Batches</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-custom-header">
                                <tr>
                                    <th class="ps-3">Item Code</th>
                                    <th>Product Name</th>
                                    <th>SAP Batch</th>
                                    <th>Vendor Batch</th>
                                    <th class="text-end">Units</th>
                                    <th class="text-end">Total Qty</th>
                                    <th class="text-end">Balance Qty</th>
                                    <th>MFG</th>
                                    <th>Expiry</th>
                                    <th>QC Status</th>
                                </tr>
                            </thead>
                            <tbody id="m_items_body">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white py-3 px-4 border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary border rounded-3 px-4 font-semibold" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-2">
                    <a id="m_pdf_btn" href="#" class="btn btn-outline-danger rounded-3 font-semibold px-3" target="_blank">
                        <i class="bi bi-file-pdf-fill me-1"></i>Download PDF
                    </a>
                    <a id="m_invoice_btn" href="#" class="btn btn-gradient-primary rounded-3 font-semibold px-3" target="_blank">
                        <i class="bi bi-file-earmark-text-fill me-1"></i>Full Invoice View
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function toggleRowItems(id) {
    const row = document.getElementById('row-items-' + id);
    const icon = document.getElementById('toggle-icon-' + id);
    if (row.classList.contains('d-none')) {
        row.classList.remove('d-none');
        icon.classList.remove('bi-chevron-right');
        icon.classList.add('bi-chevron-down');
    } else {
        row.classList.add('d-none');
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-right');
    }
}

function openInboundModal(data) {
    const invNo = data.dispatched_invoice_no || data.inbound_invoice_no || ('#IBD-' + data.id);
    document.getElementById('modalSubHeader').innerText = 'Invoice #' + invNo;
    document.getElementById('m_invoice_no').innerText = invNo;
    
    // Date formatting (12-hour AM/PM)
    const dateObj = new Date(data.created_at);
    document.getElementById('m_date').innerText = dateObj.toLocaleString('en-GB', { 
        day: '2-digit', month: '2-digit', year: 'numeric', 
        hour: '2-digit', minute: '2-digit', hour12: true 
    });

    document.getElementById('m_vendor').innerText = data.vendor ? data.vendor.name : 'N/A';
    document.getElementById('m_warehouse').innerText = data.warehouse ? data.warehouse.name : 'N/A';
    document.getElementById('m_gatepass').innerText = data.gatepass_no || '-';
    
    const vehNo = data.vehicle_no || '-';
    const driver = data.driver_name ? ` (${data.driver_name})` : '';
    document.getElementById('m_vehicle').innerText = vehNo + driver;
    
    document.getElementById('m_transporter').innerText = data.transporter ? data.transporter.name : '-';
    
    const vIn = data.vehicle_in_time ? new Date(data.vehicle_in_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: true}) : '-';
    const vOut = data.vehicle_out_time ? new Date(data.vehicle_out_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: true}) : '-';
    document.getElementById('m_vehicle_times').innerText = `${vIn} / ${vOut}`;

    // Populate items
    const items = data.items || [];
    document.getElementById('m_batch_count').innerText = items.length + ' Batches';

    let tbodyHtml = '';
    items.forEach(item => {
        const prodCode = item.product ? item.product.item_code : '-';
        const prodName = item.product ? item.product.name : '-';
        const sapBatch = item.sap_batch || '-';
        const vendorBatch = item.vendor_batch || '-';
        const units = (item.units_received || 0).toLocaleString();
        const qty = parseFloat(item.total_quantity || 0).toFixed(2);
        const bal = parseFloat(item.balance_quantity || 0).toFixed(2);
        
        let qcBadge = '<span class="qc-pill qc-pill-pending">Pending</span>';
        if (item.quality_clearance === 'approved') {
            qcBadge = '<span class="qc-pill qc-pill-approved">Approved</span>';
        } else if (item.quality_clearance === 'rejected') {
            qcBadge = '<span class="qc-pill qc-pill-rejected">Rejected</span>';
        }

        tbodyHtml += `
            <tr>
                <td class="ps-3 fw-bold text-primary">${prodCode}</td>
                <td class="fw-semibold text-dark">${prodName}</td>
                <td><code>${sapBatch}</code></td>
                <td><code>${vendorBatch}</code></td>
                <td class="text-end fw-semibold">${units}</td>
                <td class="text-end fw-bold">${qty}</td>
                <td class="text-end fw-bold text-success" style="color: #059669 !important;">${bal}</td>
                <td>${item.mfg_date || '-'}</td>
                <td>${item.expiry_date || '-'}</td>
                <td>${qcBadge}</td>
            </tr>
        `;
    });

    if (items.length === 0) {
        tbodyHtml = `<tr><td colspan="10" class="text-center py-4 text-muted">No items found in this transaction.</td></tr>`;
    }

    document.getElementById('m_items_body').innerHTML = tbodyHtml;

    // Set action buttons
    document.getElementById('m_pdf_btn').href = `/reports/inbound/${data.id}/pdf`;
    document.getElementById('m_invoice_btn').href = `/inbound/invoice/${data.id}`;

    // Show modal
    const bsModal = new bootstrap.Modal(document.getElementById('inboundDetailModal'));
    bsModal.show();
}

$(document).ready(function() {
    $('.select2-multi').select2({
        placeholder: "Select or search multiple invoices...",
        tags: true,
        tokenSeparators: [',', ' '],
        allowClear: true,
        width: '100%'
    });
});
</script>
@endpush

@endsection
