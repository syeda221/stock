@extends('layouts.app')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
.qc-wrap * { font-family: 'Inter', sans-serif; }

/* ── Stat Cards ── */
.qc-stat-card {
    border-radius: 16px;
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.qc-stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,.14); }
.qc-stat-card .stat-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
}
.qc-stat-card .stat-val   { font-size: 34px; font-weight: 800; line-height: 1; }
.qc-stat-card .stat-label { font-size: 11px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; margin-top: 4px; }
.qc-stat-card .stat-sub   { font-size: 11px; margin-top: 2px; opacity: .7; }

.card-pending  { background: linear-gradient(135deg,#fffbeb,#fef3c7); border: 1.5px solid #fde68a; }
.card-approved { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border: 1.5px solid #86efac; }
.card-rejected { background: linear-gradient(135deg,#fff1f2,#fee2e2); border: 1.5px solid #fca5a5; }

.card-pending  .stat-icon { background: #fef3c7; color: #d97706; }
.card-approved .stat-icon { background: #dcfce7; color: #16a34a; }
.card-rejected .stat-icon { background: #fee2e2; color: #dc2626; }

.card-pending  .stat-val  { color: #92400e; }
.card-approved .stat-val  { color: #14532d; }
.card-rejected .stat-val  { color: #991b1b; }

/* ── Table ── */
.qc-table thead th {
    background: linear-gradient(135deg,#1e293b 0%,#0f172a 100%);
    color: #cbd5e1; font-size: 10.5px; font-weight: 700;
    letter-spacing: .7px; text-transform: uppercase;
    border: none; padding: 13px 14px; white-space: nowrap;
}
.qc-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .12s; }
.qc-table tbody tr:hover { background: #f8fafc; }
.qc-table tbody tr.qc-rejected-row { background: #fff5f5 !important; }
.qc-table tbody tr.qc-rejected-row:hover { background: #fee2e2 !important; }
.qc-table tbody td { padding: 10px 14px; font-size: 13px; vertical-align: middle; border: none; }

/* ── QC Status Pill Select ── */
.qc-pill-select {
    appearance: none;
    border: none;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    padding: 5px 14px;
    cursor: pointer;
    transition: all .2s;
    outline: none;
    letter-spacing: .3px;
}
.qc-pill-select.qc-pending  { background: #fef3c7; color: #92400e; }
.qc-pill-select.qc-approved { background: #dcfce7; color: #15803d; }
.qc-pill-select.qc-rejected { background: #fee2e2; color: #b91c1c; }
.qc-pill-select:hover { filter: brightness(.96); transform: scale(1.03); }

/* ── Remarks Input ── */
.qc-remarks-input {
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 11.5px;
    width: 100%;
    transition: border-color .2s;
    background: #f8fafc;
    min-width: 160px;
}
.qc-remarks-input:focus { outline: none; border-color: #3b82f6; background: #fff; }
.qc-remarks-input.saved { border-color: #22c55e; background: #f0fdf4; }

/* ── Save btn ── */
.qc-save-btn {
    background: linear-gradient(135deg,#3b82f6,#1d4ed8);
    border: none; color: #fff; font-size: 11px; font-weight: 600;
    padding: 5px 13px; border-radius: 8px;
    transition: all .2s; white-space: nowrap;
}
.qc-save-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,.4); }
.qc-save-btn:disabled { opacity: .5; transform: none; cursor: not-allowed; }

/* ── Rejected badge overlay ── */
.rejected-overlay {
    display: inline-flex; align-items: center; gap: 4px;
    background: #fee2e2; color: #991b1b;
    font-size: 10px; font-weight: 700; padding: 3px 9px;
    border-radius: 20px; letter-spacing: .3px;
}

/* ── Bulk action bar ── */
.bulk-bar {
    background: linear-gradient(135deg,#1e293b,#0f172a);
    border-radius: 12px;
    padding: 12px 18px;
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 12px;
    display: none;
}
.bulk-bar.visible { display: flex; }
.bulk-bar .bulk-count { color: #93c5fd; font-size: 13px; font-weight: 700; }

/* ── Filter card ── */
.filter-card {
    background: #fff; border-radius: 12px; border: 1px solid #e9edf2;
    padding: 14px 18px; margin-bottom: 18px;
}

/* ── Tooltip save indicator ── */
.save-indicator {
    font-size: 11px; color: #22c55e; font-weight: 600;
    display: none; animation: fadeIn .3s;
}
@keyframes fadeIn { from { opacity:0; transform:translateY(2px); } to { opacity:1; transform:translateY(0); } }

/* ── Checkbox ── */
.qc-check { width: 15px; height: 15px; cursor: pointer; accent-color: #3b82f6; }

/* ── Toast ── */
.qc-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    min-width: 280px; border-radius: 12px; padding: 14px 18px;
    font-size: 13px; font-weight: 600;
    box-shadow: 0 8px 24px rgba(0,0,0,.2);
    display: flex; align-items: center; gap: 10px;
    transform: translateY(20px); opacity: 0;
    transition: all .3s;
}
.qc-toast.show { transform: translateY(0); opacity: 1; }
.qc-toast.success { background: #f0fdf4; color: #15803d; border: 1.5px solid #86efac; }
.qc-toast.error   { background: #fff1f2; color: #b91c1c; border: 1.5px solid #fca5a5; }
</style>
@endpush

@section('content')
<div class="qc-wrap">

{{-- ══ PAGE HEADER ══ --}}
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h5 class="fw-bold mb-0" style="color:#0f172a; font-size:18px;">
            <i class="bi bi-clipboard-check me-2" style="color:#3b82f6;"></i>QC Management
        </h5>
        <small class="text-muted">Manage quality clearance for all stock batches</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button id="autoRejectExpiredBtn" class="btn btn-sm fw-bold"
           style="background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;border:none;border-radius:20px;font-size:11px;padding:6px 16px;">
            <i class="bi bi-clock-history me-1"></i>Auto-Reject Expired
        </button>
        <a href="{{ route('qc.index', ['qc_status'=>'pending']) }}"
           class="btn btn-sm fw-semibold"
           style="background:#fef3c7;color:#92400e;border:1.5px solid #fde68a;border-radius:20px;font-size:11px;">
            <i class="bi bi-hourglass me-1"></i>View Pending ({{ $totalPending }})
        </a>
    </div>
</div>

{{-- ══ AUTO-REJECT INFO BANNER ══ --}}
<div style="background:linear-gradient(135deg,#fff7ed,#fff1f2);border:1.5px solid #fca5a5;border-radius:12px;padding:12px 18px;margin-bottom:18px;display:flex;align-items:center;gap:12px;">
    <div style="width:36px;height:36px;background:#fee2e2;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-shield-fill-exclamation" style="color:#dc2626;font-size:16px;"></i>
    </div>
    <div style="flex:1;">
        <div style="font-size:12px;font-weight:700;color:#991b1b;">Auto-Reject Expired Stock</div>
        <div style="font-size:11px;color:#7f1d1d;margin-top:1px;">
            Expired batches are <strong>automatically rejected</strong> and <strong>blocked from sale</strong> every day at midnight, and also when you visit this page.
            Remarks are set to: <code style="background:#fee2e2;padding:1px 5px;border-radius:4px;">Expired - Not for Sale</code>
        </div>
    </div>
    <div id="autoRejectResult" style="font-size:11px;font-weight:700;color:#15803d;display:none;"></div>
</div>

{{-- ══ KPI STAT CARDS ══ --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="qc-stat-card card-pending d-flex align-items-center gap-3">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-val">{{ $totalPending }}</div>
                <div class="stat-label" style="color:#92400e;">QC Pending</div>
                <div class="stat-sub" style="color:#b45309;">Awaiting quality check</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="qc-stat-card card-approved d-flex align-items-center gap-3">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-val">{{ $totalApproved }}</div>
                <div class="stat-label" style="color:#15803d;">QC Approved</div>
                <div class="stat-sub" style="color:#166534;">Cleared for sale & dispatch</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="qc-stat-card card-rejected d-flex align-items-center gap-3">
            <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
            <div>
                <div class="stat-val">{{ $totalRejected }}</div>
                <div class="stat-label" style="color:#b91c1c;">QC Rejected</div>
                <div class="stat-sub" style="color:#dc2626;">Blocked — cannot be sold</div>
            </div>
        </div>
    </div>
</div>

{{-- ══ FILTERS ══ --}}
<div class="filter-card">
    <form id="qcFilterForm">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Search</label>
                <input type="text" name="search" id="filter_search"
                       value="{{ request('search') }}"
                       class="form-control form-control-sm filter-field"
                       placeholder="Product, batch, remarks...">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">QC Status</label>
                <select name="qc_status" id="filter_qc_status" class="form-select form-select-sm filter-field">
                    <option value="">All Status</option>
                    <option value="pending"  {{ request('qc_status')=='pending'  ? 'selected':'' }}>🟡 Pending</option>
                    <option value="approved" {{ request('qc_status')=='approved' ? 'selected':'' }}>🟢 Approved</option>
                    <option value="rejected" {{ request('qc_status')=='rejected' ? 'selected':'' }}>🔴 Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">Warehouse</label>
                <select name="warehouse_id" id="filter_warehouse" class="form-select form-select-sm filter-field">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" {{ request('warehouse_id')==$w->id ? 'selected':'' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">Product</label>
                <select name="product_id" id="filter_product" class="form-select form-select-sm filter-field">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product_id')==$p->id ? 'selected':'' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                    <span class="text-muted small ms-2 align-self-center">
                        Total: <strong>{{ $items->total() }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ══ BULK ACTION BAR ══ --}}
<div class="bulk-bar" id="bulkBar">
    <i class="bi bi-ui-checks" style="color:#60a5fa; font-size:16px;"></i>
    <span class="bulk-count" id="bulkCount">0 selected</span>
    <div class="d-flex gap-2 ms-auto align-items-center">
        <select id="bulkStatus" class="form-select form-select-sm" style="width:140px; border-radius:8px;">
            <option value="">Set Status...</option>
            <option value="approved">🟢 Approved</option>
            <option value="pending">🟡 Pending</option>
            <option value="rejected">🔴 Rejected</option>
        </select>
        <input type="text" id="bulkRemarks" class="form-control form-control-sm" placeholder="Remarks (optional)" style="width:200px; border-radius:8px;">
        <button class="btn btn-sm btn-warning fw-bold" id="bulkApplyBtn" style="border-radius:8px; color:#fff;">
            <i class="bi bi-check2-all me-1"></i>Apply
        </button>
        <button class="btn btn-sm btn-outline-light" id="clearSelectionBtn" style="border-radius:8px;">Clear</button>
    </div>
</div>

{{-- ══ LOADING OVERLAY ══ --}}
<div id="filterLoadingOverlay" style="display:none;" class="text-center py-4">
    <div class="spinner-border text-primary" role="status"></div>
    <p class="mt-2 text-muted small">Loading...</p>
</div>

{{-- ══ MAIN TABLE ══ --}}
<div class="card shadow-sm border-0" style="border-radius:12px; overflow:hidden;">
    <div class="card-header d-flex justify-content-between align-items-center"
         style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%); border:none; padding:14px 20px;">
        <h6 class="mb-0 fw-bold" style="color:#f1f5f9; font-size:14px; letter-spacing:.3px;">
            <i class="bi bi-table me-2" style="color:#93c5fd;"></i>Stock Batches — QC Review
        </h6>
        <small style="color:#475569; font-size:11px;">
            Rejected items are automatically blocked from sale
        </small>
    </div>

    <div class="table-responsive">
        <table class="table table-sm mb-0 qc-table">
            <thead>
                <tr>
                    <th width="36">
                        <input type="checkbox" id="selectAllCheckbox" class="qc-check">
                    </th>
                    <th>#</th>
                    <th>Product</th>
                    <th>Warehouse / Location</th>
                    <th>SAP Batch</th>
                    <th class="text-end">Units (ctn)</th>
                    <th class="text-end">Balance Qty</th>
                    <th>Expiry</th>
                    <th>QC Status</th>
                    <th style="min-width:200px;">QC Remarks</th>
                    <th class="text-center" width="80">Save</th>
                </tr>
            </thead>
            <tbody id="qcTableBody">
                @forelse($items as $item)
                @php
                    $qc     = $item->quality_clearance ?? 'pending';
                    $isRej  = $qc === 'rejected';
                    $expiry = $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date) : null;
                    $expClass = '';
                    if ($expiry) {
                        if ($expiry->isPast()) $expClass = 'text-danger fw-bold';
                        elseif ($expiry->diffInDays(now()) <= 30) $expClass = 'text-warning fw-bold';
                    }
                    $wh  = $item->stockIn->warehouse->name ?? '—';
                    $row = $item->warehouseRow->row_name ?? null;
                    $loc = $row ? "$wh / Row $row" : $wh;
                @endphp
                <tr class="{{ $isRej ? 'qc-rejected-row' : '' }}" data-item-id="{{ $item->id }}">
                    <td>
                        <input type="checkbox" class="qc-check row-checkbox" value="{{ $item->id }}">
                    </td>
                    <td class="text-muted" style="font-size:11px;">
                        {{ ($items->currentPage()-1)*$items->perPage()+$loop->iteration }}
                    </td>
                    <td>
                        <div class="fw-semibold" style="font-size:12px; color:#0f172a;">
                            {{ $item->product->item_code ?? '-' }}
                        </div>
                        <small class="text-muted" style="display:block; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                               title="{{ $item->product->name ?? '-' }}">
                            {{ $item->product->name ?? '-' }}
                        </small>
                        @if($item->product->category)
                        <span style="background:#ede9fe;color:#6d28d9;padding:1px 6px;border-radius:12px;font-size:9.5px;font-weight:700;">
                            {{ $item->product->category->name }}
                        </span>
                        @endif
                    </td>
                    <td style="font-size:12px;">
                        <div class="fw-semibold" style="color:#1e293b;">{{ $wh }}</div>
                        @if($row)<small class="text-muted">Row: {{ $row }}</small>@endif
                    </td>
                    <td>
                        <span style="font-family:'Courier New',monospace; font-size:12px; background:#f1f5f9; padding:2px 7px; border-radius:6px; border:1px solid #e2e8f0;">
                            {{ $item->sap_batch ?: ($item->vendor_batch ?: '—') }}
                        </span>
                    </td>
                    <td class="text-end fw-bold" style="color:#0f172a;">
                        {{ number_format($item->units_received) }}
                    </td>
                    <td class="text-end fw-bold" style="color:#059669;">
                        {{ number_format($item->balance_quantity, 2) }}
                    </td>
                    <td>
                        @if($expiry)
                            <span class="{{ $expClass }}" style="font-size:11.5px;">
                                {{ $expiry->format('d.m.Y') }}
                            </span>
                            @if($expiry->isPast())
                                <span style="background:#fee2e2;color:#b91c1c;font-size:9px;padding:1px 5px;border-radius:6px;font-weight:700;display:block;margin-top:2px;">EXPIRED</span>
                            @elseif($expiry->diffInDays(now()) <= 30)
                                <span style="background:#fef3c7;color:#92400e;font-size:9px;padding:1px 5px;border-radius:6px;font-weight:700;display:block;margin-top:2px;">Near Expiry</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($isRej)
                        <div style="background:#fee2e2;padding:3px 6px;border-radius:8px;display:inline-block;">
                        @endif
                        <select class="qc-pill-select qc-{{ $qc }} qc-status-select"
                                data-item-id="{{ $item->id }}"
                                data-original="{{ $qc }}">
                            <option value="pending"  {{ $qc=='pending'  ? 'selected':'' }}>🟡 Pending</option>
                            <option value="approved" {{ $qc=='approved' ? 'selected':'' }}>🟢 Approved</option>
                            <option value="rejected" {{ $qc=='rejected' ? 'selected':'' }}>🔴 Rejected</option>
                        </select>
                        @if($isRej)
                        <div style="font-size:9px;color:#b91c1c;font-weight:700;margin-top:3px;text-align:center;">
                            <i class="bi bi-lock-fill"></i> Blocked from sale
                        </div>
                        </div>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <input type="text"
                                   class="qc-remarks-input"
                                   data-item-id="{{ $item->id }}"
                                   value="{{ $item->qc_remarks ?? '' }}"
                                   placeholder="Enter QC remarks...">
                        </div>
                        <span class="save-indicator" id="saved-{{ $item->id }}">
                            <i class="bi bi-check-circle-fill"></i> Saved
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="qc-save-btn save-qc-btn"
                                data-item-id="{{ $item->id }}"
                                title="Save QC">
                            <i class="bi bi-floppy me-1"></i>Save
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-5 text-muted">
                        <i class="bi bi-clipboard-check" style="font-size:36px;display:block;opacity:.3;margin-bottom:8px;"></i>
                        No stock batches found
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
                Showing <strong>{{ $items->firstItem() }}</strong> to <strong>{{ $items->lastItem() }}</strong>
                of <strong>{{ $items->total() }}</strong> batches
            </div>
            <div>{{ $items->appends(request()->query())->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
    @endif
</div>

{{-- ══ TOAST ══ --}}
<div class="qc-toast" id="qcToast">
    <i class="bi bi-check-circle-fill" id="qcToastIcon" style="font-size:18px;"></i>
    <span id="qcToastMsg"></span>
</div>

</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ──────────────────────────────────────────────────────────
   TOAST
────────────────────────────────────────────────────────── */
function showToast(msg, type = 'success') {
    const t = document.getElementById('qcToast');
    const icon = document.getElementById('qcToastIcon');
    document.getElementById('qcToastMsg').textContent = msg;
    t.className = 'qc-toast ' + type;
    icon.className = type === 'success'
        ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-triangle-fill';
    icon.style.fontSize = '18px';
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 3000);
}

/* ──────────────────────────────────────────────────────────
   SAVE SINGLE ITEM
────────────────────────────────────────────────────────── */
document.querySelectorAll('.save-qc-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const itemId  = this.dataset.itemId;
        const row     = document.querySelector(`tr[data-item-id="${itemId}"]`);
        const qcSel   = row.querySelector('.qc-status-select');
        const rmkInp  = row.querySelector('.qc-remarks-input');
        const indic   = document.getElementById(`saved-${itemId}`);

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch(`/qc-status/${itemId}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                quality_clearance: qcSel.value,
                qc_remarks: rmkInp.value
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update pill colour
                qcSel.className = `qc-pill-select qc-${qcSel.value} qc-status-select`;
                qcSel.dataset.original = qcSel.value;

                // Mark remarks saved
                rmkInp.classList.add('saved');
                setTimeout(() => rmkInp.classList.remove('saved'), 2000);

                // Rejected row style
                if (qcSel.value === 'rejected') {
                    row.classList.add('qc-rejected-row');
                } else {
                    row.classList.remove('qc-rejected-row');
                }

                // Show indicator
                indic.style.display = 'inline-flex';
                setTimeout(() => indic.style.display = 'none', 2500);

                showToast('QC updated successfully!', 'success');
            } else {
                showToast(data.message || 'Update failed', 'error');
            }
        })
        .catch(() => showToast('Network error. Try again.', 'error'))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-floppy me-1"></i>Save';
        });
    });
});

/* ──────────────────────────────────────────────────────────
   QC SELECT — change pill colour instantly
────────────────────────────────────────────────────────── */
document.querySelectorAll('.qc-status-select').forEach(sel => {
    sel.addEventListener('change', function() {
        this.className = `qc-pill-select qc-${this.value} qc-status-select`;
    });
});

/* ──────────────────────────────────────────────────────────
   REMARKS — save on Enter
────────────────────────────────────────────────────────── */
document.querySelectorAll('.qc-remarks-input').forEach(inp => {
    inp.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const row = this.closest('tr');
            row.querySelector('.save-qc-btn').click();
        }
    });
});

/* ──────────────────────────────────────────────────────────
   CHECKBOX SELECTION
────────────────────────────────────────────────────────── */
const bulkBar    = document.getElementById('bulkBar');
const bulkCount  = document.getElementById('bulkCount');
const selectAll  = document.getElementById('selectAllCheckbox');
const clearBtn   = document.getElementById('clearSelectionBtn');

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    if (checked.length > 0) {
        bulkBar.classList.add('visible');
        bulkCount.textContent = checked.length + ' item' + (checked.length > 1 ? 's' : '') + ' selected';
    } else {
        bulkBar.classList.remove('visible');
    }
}

document.querySelectorAll('.row-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});

selectAll.addEventListener('change', function() {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkBar();
});

clearBtn.addEventListener('click', function() {
    document.querySelectorAll('.row-checkbox, #selectAllCheckbox').forEach(cb => cb.checked = false);
    updateBulkBar();
});

/* ──────────────────────────────────────────────────────────
   BULK APPLY
────────────────────────────────────────────────────────── */
document.getElementById('bulkApplyBtn').addEventListener('click', function() {
    const ids    = [...document.querySelectorAll('.row-checkbox:checked')].map(cb => parseInt(cb.value));
    const status = document.getElementById('bulkStatus').value;
    const remarks = document.getElementById('bulkRemarks').value;

    if (!ids.length)   return showToast('Please select items first.', 'error');
    if (!status)        return showToast('Please select a status.', 'error');

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Applying...';

    fetch('{{ route("qc.bulk.update") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ ids, quality_clearance: status, qc_remarks: remarks })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(data.message || 'Bulk update failed', 'error');
        }
    })
    .catch(() => showToast('Network error.', 'error'))
    .finally(() => {
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-check2-all me-1"></i>Apply';
    });
});

/* ──────────────────────────────────────────────────────────
   AUTO-REJECT EXPIRED BUTTON
────────────────────────────────────────────────────────── */
document.getElementById('autoRejectExpiredBtn').addEventListener('click', function() {
    const btn    = this;
    const result = document.getElementById('autoRejectResult');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
    result.style.display = 'none';

    fetch('{{ route("qc.auto.reject") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        result.style.display = 'block';
        result.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>' + data.message;
        showToast(data.message, 'success');
        if (data.count > 0) {
            setTimeout(() => location.reload(), 1800);
        }
    })
    .catch(() => showToast('Network error. Try again.', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-clock-history me-1"></i>Auto-Reject Expired';
    });
});

/* ──────────────────────────────────────────────────────────
   AJAX FILTERS
────────────────────────────────────────────────────────── */
$(document).ready(function() {
    function applyFilters() {
        $('#filterLoadingOverlay').show();
        const params = {
            search:       $('#filter_search').val(),
            qc_status:    $('#filter_qc_status').val(),
            warehouse_id: $('#filter_warehouse').val(),
            product_id:   $('#filter_product').val(),
        };
        // Update URL without reload for filter persistence
        const url = new URL(window.location.href);
        Object.keys(params).forEach(k => {
            if (params[k]) url.searchParams.set(k, params[k]);
            else url.searchParams.delete(k);
        });
        window.location.href = url.toString();
    }

    $('#applyFilters').on('click', applyFilters);
    $('#resetFilters').on('click', function() {
        window.location.href = '{{ route("qc.index") }}';
    });
    $('.filter-field').on('keydown', function(e) {
        if (e.key === 'Enter') applyFilters();
    });
});
</script>
@endpush
