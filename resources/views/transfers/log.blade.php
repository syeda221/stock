@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
body, .log-page * { font-family: 'Inter', sans-serif; }
.log-page { background: #f0f4f8; min-height: 100vh; }

.log-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0f4c81 100%);
    border-radius: 18px;
    padding: 26px 32px;
    margin-bottom: 24px;
    color: #fff;
    position: relative; overflow: hidden;
}
.log-header::before {
    content: ''; position: absolute; inset: 0;
    background: repeating-linear-gradient(45deg,transparent,transparent 40px,rgba(255,255,255,.02) 40px,rgba(255,255,255,.02) 80px);
    pointer-events: none;
}

.log-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 16px -4px rgba(15,23,42,.06);
    overflow: hidden;
    margin-bottom: 20px;
}
.log-card-head {
    background: #f8fafc;
    padding: 16px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}

.log-th {
    font-size: 11px; text-transform: uppercase; letter-spacing: .06em;
    color: #64748b; font-weight: 700;
    background: #f8fafc; border-bottom: 2px solid #e2e8f0;
    padding: 11px 16px; white-space: nowrap;
}
.log-td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.log-tr:hover td { background: #fafbfc; }

.trf-no { font-size: 12.5px; font-weight: 800; color: #0369a1; letter-spacing: .01em; }
.from-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #fef2f2; color: #991b1b;
    border: 1px solid #fecaca; border-radius: 6px;
    padding: 3px 9px; font-size: 11.5px; font-weight: 600;
}
.to-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f0fdf4; color: #166534;
    border: 1px solid #bbf7d0; border-radius: 6px;
    padding: 3px 9px; font-size: 11.5px; font-weight: 600;
}
.search-btn {
    background: #0369a1; color: #fff; border: none;
    padding: 8px 18px; border-radius: 9px;
    font-weight: 600; font-size: 13px; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    transition: background .2s;
}
.search-btn:hover { background: #0284c7; }
.back-btn {
    background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
    padding: 8px 18px; border-radius: 9px;
    font-weight: 600; font-size: 13px; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    transition: background .2s; text-decoration: none;
}
.back-btn:hover { background: #e2e8f0; color: #334155; }

/* KPI strip */
.kpi-strip { display: flex; flex-wrap: wrap; gap: 24px; margin-bottom: 20px; }
.kpi-box {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 14px; padding: 16px 22px;
    flex: 1; min-width: 160px;
    box-shadow: 0 2px 10px -4px rgba(15,23,42,.05);
}
.kpi-box .k-lbl { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; font-weight: 700; }
.kpi-box .k-val { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.1; margin-top: 4px; }
.kpi-box .k-sub { font-size: 12px; color: #64748b; margin-top: 2px; }
</style>

<div class="log-page px-4 py-4">

{{-- HEADER --}}
<div class="log-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="badge mb-1" style="background:rgba(255,255,255,.15);font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;">AUDIT TRAIL</span>
            <h3 class="fw-bold mb-1" style="font-size:22px;">Stock Transfer Log</h3>
            <p class="mb-0 opacity-75" style="font-size:13px;">Complete history of all pallet relocations and stock movements</p>
        </div>
        <a href="{{ route('stock-transfers.index') }}" class="back-btn">
            <i class="bi bi-arrow-left-right"></i> Back to Wizard
        </a>
    </div>
</div>

{{-- FILTERS --}}
<div class="log-card mb-3">
    <div class="log-card-head">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-funnel-fill text-primary fs-5"></i>
            <h6 class="mb-0 fw-bold text-dark">Search & Filter</h6>
        </div>
    </div>
    <div class="p-4">
        <form method="GET" action="{{ route('stock-transfers.log') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small fw-semibold text-secondary mb-1">Search</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Transfer #, product name, location..."
                           value="{{ request('search') }}"
                           style="border-radius:10px;border:1.5px solid #cbd5e1;">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Filter by Product</label>
                    <select name="product_id" class="form-select" style="border-radius:10px;border:1.5px solid #cbd5e1;">
                        <option value="">-- All Products --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                [{{ $p->item_code }}] {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="search-btn flex-fill">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('stock-transfers.log') }}" class="back-btn" style="flex:1;justify-content:center;">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- KPI STRIP --}}
<div class="kpi-strip">
    <div class="kpi-box">
        <div class="k-lbl">Total Transfers</div>
        <div class="k-val text-primary">{{ number_format($transfers->total()) }}</div>
        <div class="k-sub">Transfer entries</div>
    </div>
    <div class="kpi-box">
        <div class="k-lbl">Total Units Moved</div>
        <div class="k-val text-success">{{ number_format($transfers->sum('units')) }}</div>
        <div class="k-sub">Units relocated</div>
    </div>
    <div class="kpi-box">
        <div class="k-lbl">This Page</div>
        <div class="k-val text-dark">{{ $transfers->count() }}</div>
        <div class="k-sub">of {{ $transfers->total() }} results</div>
    </div>
    @if(request('search') || request('product_id'))
    <div class="kpi-box" style="border-color:#fde68a;background:#fffbeb;">
        <div class="k-lbl" style="color:#92400e;">Active Filters</div>
        <div class="k-val" style="font-size:18px;color:#b45309;">Filtered</div>
        <div class="k-sub" style="color:#92400e;">Results filtered</div>
    </div>
    @endif
</div>

{{-- TRANSFER TABLE --}}
<div class="log-card">
    <div class="log-card-head">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary fs-5"></i>
            <h6 class="mb-0 fw-bold text-dark">Transfer Records</h6>
        </div>
        <span class="badge bg-primary" style="font-size:12px;">{{ $transfers->total() }} Records</span>
    </div>
    <div class="p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="log-th ps-4">Transfer #</th>
                        <th class="log-th">Date & Time</th>
                        <th class="log-th">Product</th>
                        <th class="log-th">From Location</th>
                        <th class="log-th">To Location</th>
                        <th class="log-th text-end">Units</th>
                        <th class="log-th">Transferred By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $trf)
                    <tr class="log-tr">
                        <td class="log-td ps-4">
                            <span class="trf-no">{{ $trf->transfer_no }}</span>
                        </td>
                        <td class="log-td">
                            <div class="fw-semibold text-dark" style="font-size:13px;">{{ $trf->created_at ? $trf->created_at->format('d M Y') : '-' }}</div>
                            <div class="small text-muted">{{ $trf->created_at ? $trf->created_at->format('h:i A') : '' }}</div>
                        </td>
                        <td class="log-td">
                            <div class="fw-bold text-dark" style="font-size:13px;">[{{ $trf->product->item_code ?? '-' }}]</div>
                            <div class="small text-muted">{{ $trf->product->name ?? '-' }}</div>
                        </td>
                        <td class="log-td">
                            <span class="from-badge">
                                <i class="bi bi-geo-alt"></i>
                                {{ $trf->from_location_display }}
                            </span>
                        </td>
                        <td class="log-td">
                            <span class="to-badge">
                                <i class="bi bi-box-arrow-in-right"></i>
                                {{ $trf->to_location_display }}
                            </span>
                        </td>
                        <td class="log-td text-end">
                            <span class="fw-bold text-dark" style="font-size:16px;">{{ number_format($trf->units) }}</span>
                            <span class="small text-muted ms-1">units</span>
                        </td>
                        <td class="log-td">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                     style="width:30px;height:30px;background:#0369a1;font-size:12px;flex-shrink:0;">
                                    {{ strtoupper(substr($trf->user->name ?? 'S', 0, 1)) }}
                                </div>
                                <span class="small fw-semibold text-dark">{{ $trf->user->name ?? 'System' }}</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3 opacity-30"></i>
                            <h6 class="fw-semibold">No Transfer Records Found</h6>
                            <p class="small mb-0">
                                @if(request('search') || request('product_id'))
                                    No results match your search. <a href="{{ route('stock-transfers.log') }}">Clear filters</a>
                                @else
                                    No stock transfers have been recorded yet.
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($transfers->hasPages())
    <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small text-muted">
            Showing {{ $transfers->firstItem() }}–{{ $transfers->lastItem() }} of {{ $transfers->total() }} records
        </span>
        {{ $transfers->appends(request()->query())->links() }}
    </div>
    @endif
</div>

</div>{{-- /log-page --}}
@endsection
