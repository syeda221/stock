@extends('layouts.app')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

.os-wrap * { font-family: 'Inter', sans-serif; }

/* ── Main Table ── */
.os-table thead th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #cbd5e1; font-size: 10.5px; font-weight: 700;
    letter-spacing: .7px; text-transform: uppercase;
    border: none; padding: 13px 14px; white-space: nowrap;
}
.os-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .12s; }
.os-table tbody tr:hover { background: #f8fafc; }
.os-table tbody td { padding: 10px 14px; font-size: 13px; vertical-align: middle; border: none; }
.os-item-code {
    font-family: 'Courier New', monospace; background: #f1f5f9;
    padding: 2px 7px; border-radius: 5px; font-size: 11.5px;
    font-weight: 700; color: #0f172a; border: 1px solid #e2e8f0;
}
.os-details-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border: none; color: #fff; font-size: 11px; font-weight: 600;
    padding: 5px 13px; border-radius: 20px;
    display: inline-flex; align-items: center; gap: 5px;
    transition: all .2s; letter-spacing: .3px; cursor: pointer;
}
.os-details-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(59,130,246,.4); }

/* ── Batches Modal ── */
#batchesModal .modal-content { border: none; border-radius: 16px; overflow: hidden; }
#batchesModal .modal-body    { background: #fff; padding: 0; }
@media (min-width: 993px) {
    #batchesModal {
        left: 280px !important;
        width: calc(100% - 280px) !important;
    }
}

/* ── KPI Strip ── */
.kpi-strip { display: flex; background: #fff; border-bottom: 1px solid #e9edf2; }
.kpi-card  {
    flex: 1; padding: 14px 18px; text-align: center;
    border-right: 1px solid #f1f5f9; position: relative;
}
.kpi-card:last-child { border-right: none; }
.kpi-icon  {
    width: 30px; height: 30px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 5px; font-size: 13px;
}
.kpi-val { font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1; }
.kpi-lbl { font-size: 9.5px; font-weight: 600; color: #94a3b8; letter-spacing: .5px; text-transform: uppercase; margin-top: 3px; }
.kpi-blue   .kpi-icon { background:#dbeafe; color:#2563eb; }
.kpi-green  .kpi-icon { background:#dcfce7; color:#16a34a; }
.kpi-purple .kpi-icon { background:#ede9fe; color:#7c3aed; }
.kpi-orange .kpi-icon { background:#ffedd5; color:#ea580c; }
.kpi-red    .kpi-icon { background:#fee2e2; color:#dc2626; }

/* ── Batch Cards ── */
.batch-cards-wrap { padding: 14px; display: flex; flex-direction: column; gap: 10px; }

.b-card {
    background: #fff; border-radius: 12px; border: 1px solid #e9edf2;
    overflow: hidden; display: grid; grid-template-columns: 5px 1fr;
    transition: box-shadow .2s, transform .15s;
}
.b-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.09); transform: translateY(-1px); }
.b-stripe {}
.b-card.s-available .b-stripe { background: linear-gradient(180deg,#22c55e,#15803d); }
.b-card.s-blocked   .b-stripe { background: linear-gradient(180deg,#ef4444,#b91c1c); }
.b-card.s-hold      .b-stripe { background: linear-gradient(180deg,#f59e0b,#b45309); }

.b-inner { padding: 14px 16px; }

/* Header row */
.b-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 8px; }
.b-loc     { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

.pill-wh  { background: linear-gradient(135deg,#1e293b,#334155); color: #e2e8f0; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
.pill-row { background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #e2e8f0; }
.pill-type{ background: #ede9fe; color: #6d28d9; padding: 2px 8px; border-radius: 20px; font-size: 9.5px; font-weight: 700; letter-spacing: .4px; }

.b-badges { display: flex; gap: 6px; align-items: center; }

/* Metrics */
.b-metrics { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 11px; }
.b-metric {
    background: #f8fafc; border: 1px solid #e9edf2; border-radius: 8px;
    padding: 8px 12px; text-align: center; min-width: 72px; flex: 1;
}
.b-metric .mv { font-size: 17px; font-weight: 700; color: #0f172a; line-height: 1.1; }
.b-metric .ml { font-size: 9px; font-weight: 600; color: #94a3b8; letter-spacing: .4px; text-transform: uppercase; margin-top: 2px; }
.b-metric.hi-blue   { background:#eff6ff; border-color:#bfdbfe; }
.b-metric.hi-blue   .mv { color:#1d4ed8; }
.b-metric.hi-green  { background:#f0fdf4; border-color:#bbf7d0; }
.b-metric.hi-green  .mv { color:#15803d; }
.b-metric.hi-purple { background:#faf5ff; border-color:#ddd6fe; }
.b-metric.hi-purple .mv { color:#6d28d9; }

/* Pallet visual */
.pallet-bar { display: flex; gap: 3px; flex-wrap: wrap; align-items: center; margin-bottom: 10px; }
.p-slot {
    width: 24px; height: 24px; border-radius: 4px; font-size: 8px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; border: 1.5px solid;
}
.p-slot.used  { background: #dbeafe; border-color: #93c5fd; color: #1d4ed8; }
.p-slot.more  { background: #f1f5f9; border-color: #cbd5e1; color: #64748b; }
.p-range-lbl  { font-size: 10px; color: #475569; font-weight: 600; margin-left: 5px; }

/* Footer chips */
.b-footer     { display: flex; gap: 12px; flex-wrap: wrap; border-top: 1px solid #f1f5f9; padding-top: 10px; }
.b-chip       { display: flex; align-items: center; gap: 5px; font-size: 11px; color: #64748b; }
.b-chip .cl   { font-weight: 700; color: #374151; }
.exp-danger   { color: #dc2626 !important; }
.exp-danger .cl{ color: #dc2626 !important; }
.exp-warn     { color: #d97706 !important; }
.exp-warn .cl { color: #d97706 !important; }

/* Action btns */
.act-btn {
    width: 28px; height: 28px; border-radius: 6px; border: none; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px; transition: all .15s;
}
.act-btn:hover { transform: scale(1.12); }
.act-view { background: #eff6ff; color: #2563eb; }
.act-view:hover { background: #dbeafe; }
.act-edit { background: #fefce8; color: #ca8a04; }
.act-edit:hover { background: #fef9c3; }

/* Status / QC badges */
.bs-available { background:#dcfce7; color:#15803d; font-size:10px; padding:3px 9px; border-radius:12px; font-weight:700; }
.bs-blocked   { background:#fee2e2; color:#b91c1c; font-size:10px; padding:3px 9px; border-radius:12px; font-weight:700; }
.bs-hold      { background:#fef9c3; color:#92400e; font-size:10px; padding:3px 9px; border-radius:12px; font-weight:700; }
.bq-approved  { background:#d1fae5; color:#065f46; font-size:10px; padding:3px 9px; border-radius:12px; font-weight:700; }
.bq-rejected  { background:#fee2e2; color:#991b1b; font-size:10px; padding:3px 9px; border-radius:12px; font-weight:700; }
.bq-pending   { background:#fef3c7; color:#92400e; font-size:10px; padding:3px 9px; border-radius:12px; font-weight:700; }

/* Empty / Loading */
.state-box { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:60px 20px; gap:12px; color:#94a3b8; }

/* Tabs Styles */
.nav-tabs .nav-link {
    border: none; color: #64748b; border-bottom: 2px solid transparent;
    padding: 10px 18px; transition: all .15s; font-size: 13px;
}
.nav-tabs .nav-link.active {
    color: #1d4ed8; border-bottom-color: #1d4ed8; background: transparent;
}
</style>
@endpush

@section('content')
<div class="os-wrap">

{{-- ══════════ MAIN CARD ══════════ --}}
<div class="card shadow-sm border-0" style="border-radius:12px; overflow:hidden;">

  {{-- Header --}}
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"
       style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%); border:none; padding:16px 22px;">
    <div>
      <h6 class="mb-0 fw-bold" style="color:#f1f5f9; font-size:15px; letter-spacing:.3px;">
        <i class="bi bi-boxes me-2" style="color:#93c5fd;"></i>Opening Stock Dashboard
      </h6>
      <small style="color:#475569; font-size:11px;">Batch-wise and Document-wise inventory overview</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('opening-stock.export') }}" class="btn btn-sm"
         style="background:#134e26; color:#4ade80; border:1px solid #166534; font-size:11px; border-radius:8px; font-weight:600;">
        <i class="bi bi-download me-1"></i>Export
      </a>
      <a href="{{ route('opening-stock.import') }}" class="btn btn-sm"
         style="background:#172554; color:#60a5fa; border:1px solid #1e40af; font-size:11px; border-radius:8px; font-weight:600;">
        <i class="bi bi-upload me-1"></i>Import
      </a>
      <a href="{{ route('opening-stock.create') }}" class="btn btn-sm fw-bold"
         style="background:linear-gradient(135deg,#3b82f6,#1d4ed8); color:#fff; border:none; border-radius:8px; font-size:11px; padding:6px 16px;">
        <i class="bi bi-plus-lg me-1"></i>Add Opening Stock
      </a>
    </div>
  </div>

  {{-- Tabs Navigation --}}
  <ul class="nav nav-tabs px-3 pt-2 bg-light border-bottom" id="openingStockTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active fw-bold text-uppercase" id="products-tab" data-bs-toggle="tab" data-bs-target="#products-pane" type="button" role="tab" aria-controls="products-pane" aria-selected="true">
        <i class="bi bi-box-seam me-1"></i> Stock by Product
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-bold text-uppercase" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions-pane" type="button" role="tab" aria-controls="transactions-pane" aria-selected="false">
        <i class="bi bi-receipt me-1"></i> Stock Entries / Documents
      </button>
    </li>
  </ul>

  <div class="tab-content" id="openingStockTabContent">

    {{-- ================= TAB 1: PRODUCT LIST ================= --}}
    <div class="tab-pane fade show active" id="products-pane" role="tabpanel" aria-labelledby="products-tab">

      {{-- Filters --}}
      <div class="p-3 bg-light border-bottom">
        <form id="openingStockFilterForm">
          <div class="row g-2">
            <div class="col-md-3">
              <label class="form-label fw-semibold small">Search</label>
              <input type="text" name="search" id="filter_search" class="form-control form-control-sm filter-field" placeholder="Item code or name...">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold small">Warehouse</label>
              <select name="warehouse_id" id="filter_warehouse" class="form-select form-select-sm filter-field">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $w)
                  <option value="{{ $w->id }}">{{ $w->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold small">Product</label>
              <select name="product_id" id="filter_product" class="form-select form-select-sm filter-field">
                <option value="">All Products</option>
                @foreach($products as $p)
                  <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold small">Status</label>
              <select name="stock_status" id="filter_stock_status" class="form-select form-select-sm filter-field">
                <option value="">All Status</option>
                <option value="available">🟢 Available</option>
                <option value="blocked">🔴 Blocked</option>
                <option value="hold">🟡 Hold</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold small">&nbsp;</label>
              <div class="d-flex gap-2 align-items-center">
                <button type="button" id="applyFilters" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Filter</button>
                <button type="button" id="resetFilters"  class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                <span class="text-muted small ms-1">Total: <strong id="totalCount">{{ $items->count() }}</strong></span>
              </div>
            </div>
          </div>
        </form>
      </div>

      {{-- Loading overlay --}}
      <div id="filterLoadingOverlay" style="display:none;">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2 text-muted small">Applying filters...</p>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle os-table">
          <thead>
            <tr>
              <th width="46">#</th>
              <th>Item Code</th>
              <th>Description</th>
              <th>Category</th>
              <th>Date</th>
              <th class="text-center">Entries</th>
              <th class="text-end">Total Units</th>
              <th class="text-center">Pallets</th>
              <th class="text-end">Total Qty</th>
              <th class="text-center" width="120">Action</th>
            </tr>
          </thead>
          <tbody id="openingStockTableBody">
            @forelse($items as $item)
            <tr>
              <td class="text-muted" style="font-size:11px;">{{ ($items->currentPage()-1)*$items->perPage()+$loop->iteration }}</td>
              <td><span class="os-item-code">{{ $item->product->item_code }}</span></td>
              <td style="font-weight:600; color:#1e293b;">{{ $item->product->name }}</td>
              <td>
                <span style="background:#ede9fe;color:#6d28d9;padding:2px 8px;border-radius:12px;font-size:10px;font-weight:700;">
                  {{ $item->product->category->name ?? '—' }}
                </span>
              </td>
              <td style="font-size:11.5px;color:#64748b;">
                {{ $item->latest_date ? \Carbon\Carbon::parse($item->latest_date)->format('d.m.Y H:i') : '—' }}
              </td>
              <td class="text-center">
                <span style="background:#f1f5f9;color:#475569;padding:3px 9px;border-radius:12px;font-size:11px;font-weight:700;border:1px solid #e2e8f0;">
                  {{ $item->batch_count }}
                </span>
              </td>
              <td class="text-end fw-bold" style="color:#0f172a;">{{ number_format($item->total_units) }}</td>
              <td class="text-center">
                @if($item->total_pallets)
                  <span style="background:#dbeafe;color:#1d4ed8;padding:3px 9px;border-radius:12px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:3px;">
                    <i class="bi bi-layers"></i> {{ $item->total_pallets }}
                  </span>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td class="text-end fw-bold" style="color:#059669;">
                {{ rtrim(rtrim(number_format($item->total_qty,2),'0'),'.') }}
              </td>
              <td class="text-center">
                <div class="d-flex gap-1 justify-content-center align-items-center">
                  <button class="os-details-btn view-batches-btn"
                          data-product-id="{{ $item->product_id }}"
                          data-product-name="{{ $item->product->name }} ({{ $item->product->item_code }})">
                    <i class="bi bi-list-columns-reverse"></i> Details
                  </button>

                  @php
                    $sIds = $item->stock_in_ids ?? collect([$item->latest_stock_in_id])->filter();
                  @endphp

                  @if($sIds->count() === 1)
                    <a href="{{ route('opening-stock.transaction.show', $sIds->first()) }}"
                       class="btn btn-sm btn-info fw-bold text-white d-inline-flex align-items-center gap-1 shadow-sm"
                       style="font-size:11px; padding:5px 10px; border-radius:20px; border:none; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);"
                       title="View Document #OS-{{ $sIds->first() }}">
                      <i class="bi bi-file-earmark-text"></i> #OS-{{ $sIds->first() }}
                    </a>
                    <a href="{{ route('opening-stock.transaction.edit', $sIds->first()) }}"
                       class="btn btn-sm btn-warning fw-bold text-white d-inline-flex align-items-center gap-1 shadow-sm"
                       style="font-size:11px; padding:5px 10px; border-radius:20px; border:none; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"
                       title="Edit Sheet #OS-{{ $sIds->first() }}">
                      <i class="bi bi-pencil"></i> Edit
                    </a>
                  @elseif($sIds->count() > 1)
                    <div class="btn-group">
                      <button type="button" class="btn btn-sm btn-warning fw-bold text-white dropdown-toggle d-inline-flex align-items-center gap-1 shadow-sm"
                              data-bs-toggle="dropdown" aria-expanded="false"
                              style="font-size:11px; padding:5px 12px; border-radius:20px; border:none; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="bi bi-pencil-square"></i> Edit Sheet ({{ $sIds->count() }})
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:12px; border-radius:8px;">
                        <li class="dropdown-header text-uppercase fw-bold" style="font-size:10px;">Select Sheet to Edit</li>
                        @foreach($sIds as $sId)
                          <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center py-1 px-3"
                               href="{{ route('opening-stock.transaction.edit', $sId) }}">
                              <span><i class="bi bi-pencil text-warning me-1"></i> Edit <strong>#OS-{{ $sId }}</strong></span>
                              <span class="badge bg-info-subtle text-info rounded-pill ms-2"
                                    onclick="event.preventDefault(); event.stopPropagation(); window.location.href='{{ route('opening-stock.transaction.show', $sId) }}';"
                                    title="View Sheet #OS-{{ $sId }}">View</span>
                            </a>
                          </li>
                        @endforeach
                      </ul>
                    </div>
                  @endif
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size:36px;display:block;opacity:.3;margin-bottom:8px;"></i>
                No opening stock found
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($items->hasPages())
      <div class="card-footer bg-light border-top py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
          <div class="text-muted small">
            Showing <strong>{{ $items->firstItem() }}</strong> to <strong>{{ $items->lastItem() }}</strong> of <strong>{{ $items->total() }}</strong> entries
          </div>
          <div>{{ $items->links('pagination::bootstrap-5') }}</div>
        </div>
      </div>
      @endif
    </div>

    {{-- ================= TAB 2: TRANSACTION LIST ================= --}}
    <div class="tab-pane fade" id="transactions-pane" role="tabpanel" aria-labelledby="transactions-tab">
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle os-table">
          <thead>
            <tr>
              <th width="46">#</th>
              <th>Transaction ID</th>
              <th>Date</th>
              <th>Warehouse</th>
              <th class="text-center">Products Count</th>
              <th class="text-end">Total Cartons</th>
              <th class="text-end">Total Weight</th>
              <th>Remarks</th>
              <th class="text-center" width="220">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($transactions as $tx)
            <tr>
              <td class="text-muted" style="font-size:11px;">{{ ($transactions->currentPage()-1)*$transactions->perPage()+$loop->iteration }}</td>
              <td class="fw-bold" style="color:#1d4ed8;">#OS-{{ $tx->id }}</td>
              <td style="font-size:11.5px;color:#64748b;">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
              <td>
                @php
                  $whNames = $tx->items->map(fn($i) => $i->warehouse->name ?? $tx->warehouse->name ?? 'Auto')->unique()->filter()->values();
                @endphp
                @if($whNames->count() > 1)
                  <span class="pill-wh" title="{{ $whNames->join(', ') }}">
                    <i class="bi bi-building"></i> {{ Str::limit($whNames->first(), 15) }} <small style="opacity:0.8;">(+{{ $whNames->count() - 1 }})</small>
                  </span>
                @else
                  <span class="pill-wh"><i class="bi bi-building"></i> {{ $whNames->first() ?? 'Auto' }}</span>
                @endif
              </td>
              <td class="text-center">
                <span class="badge bg-secondary" style="font-size:11px;font-weight:700;">{{ $tx->items->count() }} Items</span>
              </td>
              <td class="text-end fw-bold">{{ number_format($tx->items->sum('units_received')) }}</td>
              <td class="text-end fw-bold" style="color:#059669;">{{ number_format($tx->items->sum('total_quantity'), 2) }}</td>
              <td style="font-size:12px;color:#64748b;">{{ Str::limit($tx->remarks, 50) }}</td>
              <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">
                  <a href="{{ route('opening-stock.transaction.show', $tx) }}" class="btn btn-sm btn-outline-primary" style="font-size:11px;padding:4px 10px;border-radius:6px;">
                    <i class="bi bi-eye"></i> Details
                  </a>
                  <a href="{{ route('opening-stock.transaction.edit', $tx) }}" class="btn btn-sm btn-outline-warning" style="font-size:11px;padding:4px 10px;border-radius:6px;">
                    <i class="bi bi-pencil"></i> Edit Entire Entry
                  </a>
                  <form action="{{ route('opening-stock.transaction.destroy', $tx) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this complete transaction?');" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:4px 10px;border-radius:6px;">
                      <i class="bi bi-trash"></i> Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">
                <i class="bi bi-receipt" style="font-size:36px;display:block;opacity:.3;margin-bottom:8px;"></i>
                No stock transactions found
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($transactions->hasPages())
      <div class="card-footer bg-light border-top py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
          <div class="text-muted small">
            Showing transactions <strong>{{ $transactions->firstItem() }}</strong> to <strong>{{ $transactions->lastItem() }}</strong> of <strong>{{ $transactions->total() }}</strong> entries
          </div>
          <div>{{ $transactions->links('pagination::bootstrap-5') }}</div>
        </div>
      </div>
      @endif
    </div>

  </div>
</div>
</div>



{{-- ══════════ PROFESSIONAL BATCHES MODAL ══════════ --}}
<div class="modal fade" id="batchesModal" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:1020px;">
<div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">

  {{-- Modal Header --}}
  <div class="modal-header" style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);border:none;padding:20px 24px;">
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="width:40px;height:40px;background:rgba(59,130,246,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-boxes" style="color:#93c5fd;font-size:18px;"></i>
      </div>
      <div>
        <h6 class="modal-title mb-0" style="color:#f1f5f9;font-size:14px;font-weight:700;letter-spacing:.3px;">
          Inventory Locations &amp; Batch Detail
        </h6>
        <small id="batchModalProductName" style="color:#60a5fa;font-size:11.5px;font-weight:600;"></small>
      </div>
    </div>
    <button class="btn-close" data-bs-dismiss="modal" style="filter:invert(1) brightness(2);"></button>
  </div>

  {{-- KPI Strip --}}
  <div class="kpi-strip" id="batchKpiStrip" style="display:none;">
    <div class="kpi-card kpi-blue">
      <div class="kpi-icon"><i class="bi bi-archive"></i></div>
      <div class="kpi-val" id="kpiEntries">—</div>
      <div class="kpi-lbl">Batches</div>
    </div>
    <div class="kpi-card kpi-green">
      <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
      <div class="kpi-val" id="kpiUnits">—</div>
      <div class="kpi-lbl">Total Units</div>
    </div>
    <div class="kpi-card kpi-purple">
      <div class="kpi-icon"><i class="bi bi-layers"></i></div>
      <div class="kpi-val" id="kpiPallets">—</div>
      <div class="kpi-lbl">Total Pallets</div>
    </div>
    <div class="kpi-card kpi-orange">
      <div class="kpi-icon"><i class="bi bi-stack"></i></div>
      <div class="kpi-val" id="kpiQty">—</div>
      <div class="kpi-lbl">Balance Qty</div>
    </div>
    <div class="kpi-card kpi-red">
      <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
      <div class="kpi-val" id="kpiExpiring">—</div>
      <div class="kpi-lbl">Near Expiry</div>
    </div>
  </div>

  {{-- Body --}}
  <div class="modal-body" style="background:#fff;padding:0;">
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-sm mb-0 align-middle small" style="font-size: 12.5px;">
        <thead class="table-dark">
          <tr>
            <th>Warehouse</th>
            <th>Row / Location</th>
            <th>SAP Batch</th>
            <th>Vendor Batch</th>
            <th>MFG Date</th>
            <th>Expiry Date</th>
            <th class="text-end">Units (ctn)</th>
            <th class="text-end">Balance Qty</th>
            <th class="text-center">Pallets</th>
            <th class="text-center">QC Check</th>
            <th class="text-center">Status</th>
            <th class="text-center" width="140">Document / Action</th>
          </tr>
        </thead>
        <tbody id="batchesTableBody">
          <!-- Loaded dynamically via AJAX -->
        </tbody>
      </table>
    </div>
    <div id="batchLoadingState" class="state-box py-5 text-center" style="display:none;">
      <div class="spinner-border text-primary" style="width:30px;height:30px;border-width:3px;"></div>
      <div class="text-muted mt-2 small">Loading batch details...</div>
    </div>
  </div>

</div>
</div>
</div>

@endsection

@push('scripts')
<script>
/* ── Stacked modal z-index fix ── */
document.addEventListener('show.bs.modal', function(event) {
    var z = 1055 + 10 * document.querySelectorAll('.modal.show').length;
    event.target.style.zIndex = z;
    setTimeout(function() {
        document.querySelectorAll('.modal-backdrop:not(.modal-stack)').forEach(function(b) {
            b.style.zIndex = z - 1; b.classList.add('modal-stack');
        });
    }, 0);
});

/* ── Click delegation ── */
document.addEventListener('click', function(e) {

    /* Simple VIEW button (inside batch cards) */
    var viewBtn = e.target.closest('.view-btn');
    if (viewBtn) {
        var d = JSON.parse(viewBtn.dataset.item);
        document.getElementById('vWarehouse').innerText     = d.warehouse?.name ?? d.stock_in?.warehouse?.name ?? '—';
        document.getElementById('vRow').innerText           = d.warehouse_row?.row_name ?? '—';
        document.getElementById('vDate').innerText          = d.created_at ? d.created_at.substring(0,16).replace('T',' ') : '—';
        document.getElementById('vCode').innerText          = d.product?.item_code ?? '—';
        document.getElementById('vDesc').innerText          = d.product?.name ?? '—';
        document.getElementById('vCategory').innerText      = d.product?.category?.name ?? '—';
        document.getElementById('vSap').innerText           = d.sap_batch    || '—';
        document.getElementById('vVendor').innerText        = d.vendor_batch || '—';
        document.getElementById('vIbd').innerText           = d.ibd_no       || '—';
        document.getElementById('vPo').innerText            = d.po_no        || '—';
        document.getElementById('vMfg').innerText           = d.mfg_date     || '—';
        document.getElementById('vExpiry').innerText        = d.expiry_date  || '—';
        document.getElementById('vUnits').innerText         = d.units_received;
        document.getElementById('vPack').innerText          = d.pack_size_snapshot;
        document.getElementById('vTotal').innerText         = d.total_quantity;
        document.getElementById('vPallets').innerText       = d.pallets_used ?? '—';
        document.getElementById('vCartonsPerPallet').innerText = d.product?.cartons_per_pallet
            ? d.product.cartons_per_pallet + ' ctn / pallet' : 'Not set';
        document.getElementById('vRemarks').innerText       = d.remarks || '—';
        new bootstrap.Modal(document.getElementById('viewModal')).show();
        return;
    }

    /* DETAILS button → Batches Modal */
    var detailsBtn = e.target.closest('.view-batches-btn');
    if (detailsBtn) {
        var prodId   = detailsBtn.dataset.productId;
        var prodName = detailsBtn.dataset.productName;

        document.getElementById('batchModalProductName').innerText = prodName;
        document.getElementById('batchLoadingState').style.display = 'block';
        var tbody = document.getElementById('batchesTableBody');
        tbody.innerHTML = '';
        document.getElementById('batchKpiStrip').style.display = 'none';

        new bootstrap.Modal(document.getElementById('batchesModal')).show();

        fetch('/opening-stock/product/' + prodId + '/batches', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('batchLoadingState').style.display = 'none';

            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="11" class="text-center py-5 text-muted">No batch records found</td></tr>';
                return;
            }

            /* ── KPI calculation ── */
            var totalUnits = 0, totalPallets = 0, totalBalance = 0, nearExpiry = 0;
            var today = new Date(), warn90 = new Date();
            warn90.setDate(today.getDate() + 90);

            data.forEach(function(item) {
                var u = parseInt(item.units_received) || 0;
                var p = item.pallets_used || (item.product && item.product.cartons_per_pallet > 0
                    ? Math.ceil(u / item.product.cartons_per_pallet) : 0);
                totalUnits   += u;
                totalPallets += p;
                totalBalance += parseFloat(item.balance_quantity || item.total_quantity || 0);
                if (item.expiry_date) {
                    var exp = new Date(item.expiry_date);
                    if (exp <= warn90 && exp >= today) nearExpiry++;
                }
            });

            document.getElementById('kpiEntries').textContent  = data.length;
            document.getElementById('kpiUnits').textContent    = totalUnits.toLocaleString();
            document.getElementById('kpiPallets').textContent  = totalPallets;
            document.getElementById('kpiQty').textContent      = totalBalance.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
            document.getElementById('kpiExpiring').textContent = nearExpiry;
            document.getElementById('batchKpiStrip').style.display = 'flex';

            /* ── Render each batch row ── */
            data.forEach(function(item) {
                var wh       = item.warehouse?.name || item.stock_in?.warehouse?.name || '—';
                var rowName  = item.warehouse_row?.row_name || '—';
                var sap      = item.sap_batch    || '—';
                var vendor   = item.vendor_batch || '—';
                var units    = parseInt(item.units_received) || 0;
                var balance  = parseFloat(item.balance_quantity || item.total_quantity || 0);
                var pallets  = item.pallets_used || (item.product && item.product.cartons_per_pallet > 0
                    ? Math.ceil(units / item.product.cartons_per_pallet) : 0);
                var qc       = item.quality_clearance || 'pending';

                /* Status */
                var sHtml  = '<span class="bs-available">Available</span>';
                if (item.block_stock) {
                    sHtml  = '<span class="bs-blocked">Blocked</span>';
                } else if (item.hold_stock) {
                    sHtml  = '<span class="bs-hold">Hold</span>';
                }

                /* QC */
                var qcHtml = '<span class="bq-pending">Pending</span>';
                if (qc === 'approved') qcHtml = '<span class="bq-approved">Approved</span>';
                if (qc === 'rejected') qcHtml = '<span class="bq-rejected">Rejected</span>';

                /* Dates */
                var fmtDate = function(str) {
                    if (!str) return '—';
                    var d = new Date(str);
                    return d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
                };
                var mfgStr    = fmtDate(item.mfg_date);
                var expStr    = fmtDate(item.expiry_date);

                var stockInId = item.stock_in_id;
                var docBtnHtml = stockInId 
                    ? '<div class="d-flex gap-1 justify-content-center">' +
                        '<a href="/opening-stock/transaction/' + stockInId + '" class="btn btn-sm btn-outline-primary py-0 px-2 fw-bold" style="font-size:10.5px;" title="View Document #OS-' + stockInId + '"><i class="bi bi-file-earmark-text"></i> #OS-' + stockInId + '</a>' +
                        '<a href="/opening-stock/transaction/' + stockInId + '/edit" class="btn btn-sm btn-outline-warning py-0 px-2" style="font-size:10.5px;" title="Edit Entry"><i class="bi bi-pencil"></i></a>' +
                      '</div>'
                    : '—';

                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + wh + '</td>' +
                    '<td class="fw-bold text-dark">' + (item.pallet_range_display || rowName) + '</td>' +
                    '<td>' + sap + '</td>' +
                    '<td>' + vendor + '</td>' +
                    '<td>' + mfgStr + '</td>' +
                    '<td class="fw-bold">' + expStr + '</td>' +
                    '<td class="text-end font-monospace">' + units.toLocaleString() + '</td>' +
                    '<td class="text-end font-monospace fw-bold text-success">' + balance.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>' +
                    '<td class="text-center"><span class="badge bg-secondary">' + pallets + '</span></td>' +
                    '<td class="text-center">' + qcHtml + '</td>' +
                    '<td class="text-center">' + sHtml + '</td>' +
                    '<td class="text-center">' + docBtnHtml + '</td>';

                tbody.appendChild(tr);
            });
        })
        .catch(function(err) {
            console.error(err);
            document.getElementById('batchLoadingState').style.display = 'none';
            tbody.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-5">Failed to load batch details</td></tr>';
        });

        return;
    }
});

/* ── Filter functionality ── */
$(document).ready(function() {
    $('#applyFilters').on('click', function() { applyFilters(); });
    $('#resetFilters').on('click',  function() { $('#openingStockFilterForm')[0].reset(); applyFilters(); });
    $('.filter-field').on('change keyup', function() {
        clearTimeout(window.filterTimeout);
        window.filterTimeout = setTimeout(applyFilters, 500);
    });

    function applyFilters() {
        $('#filterLoadingOverlay').show();
        $.ajax({
            url: '{{ route("opening-stock.index") }}',
            type: 'GET',
            data: {
                search:       $('#filter_search').val(),
                warehouse_id: $('#filter_warehouse').val(),
                product_id:   $('#filter_product').val(),
                stock_status: $('#filter_stock_status').val()
            },
            success: function(response) {
                var doc = new DOMParser().parseFromString(response, 'text/html');
                var newBody = doc.querySelector('#openingStockTableBody');
                if (newBody) $('#openingStockTableBody').html(newBody.innerHTML);
                $('#totalCount').text($(newBody).find('tr').length);
                $('#filterLoadingOverlay').hide();
            },
            error: function() { $('#filterLoadingOverlay').hide(); }
        });
    }

    // Keep active tab on refresh or page change if needed
    var hash = window.location.hash;
    if (hash) {
        $('.nav-tabs button[data-bs-target="' + hash + '"]').tab('show');
    }
    $('.nav-tabs button').on('shown.bs.tab', function (e) {
        window.location.hash = e.target.getAttribute('data-bs-target');
    });
});
</script>
@endpush
