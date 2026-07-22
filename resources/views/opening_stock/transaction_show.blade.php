@extends('layouts.app')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

.doc-wrap * { font-family: 'Inter', sans-serif; }

.header-meta th {
    background: #f8fafc;
    width: 25%;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
}
.header-meta td {
    width: 25%;
    font-size: 13px;
    color: #0f172a;
}
.doc-table thead th {
    background: #0f172a;
    color: #e2e8f0;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
    padding: 8px 10px;
}
.doc-table tbody td {
    font-size: 12.5px;
    padding: 8px 10px;
    vertical-align: middle;
}
.status-pill {
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 20px;
}
.sp-available { background: #dcfce7; color: #15803d; }
.sp-blocked   { background: #fee2e2; color: #b91c1c; }
.sp-hold      { background: #fef9c3; color: #92400e; }

.qc-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
}
.qcb-approved { background: #d1fae5; color: #065f46; }
.qcb-rejected { background: #fee2e2; color: #991b1b; }
.qcb-pending  { background: #fef3c7; color: #92400e; }

@media print {
    .no-print { display: none !important; }
    body { background: #fff !important; }
    .card { border: none !important; box-shadow: none !important; }
    .card-body { padding: 0 !important; }
    .doc-table thead th {
        background: #e2e8f0 !important;
        color: #000 !important;
        border: 1px solid #cbd5e1 !important;
    }
    .doc-table td, .doc-table th {
        border: 1px solid #cbd5e1 !important;
    }
}
</style>
@endpush

@section('content')
<div class="doc-wrap container-fluid py-4">

  {{-- Top Navigation & Actions --}}
  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="{{ route('opening-stock.index') }}#transactions-pane" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back to List
    </a>
    <div class="d-flex gap-2">
      <a href="{{ route('opening-stock.transaction.edit', $stockIn) }}" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-pencil"></i> Edit Entire Entry
      </a>
      <button onclick="window.print()" class="btn btn-sm btn-primary">
        <i class="bi bi-printer"></i> Print Document
      </button>
    </div>
  </div>

  {{-- Document Card --}}
  <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
    <div class="card-body p-4">
      
      {{-- Document Title & Company Logo --}}
      <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div>
          <h4 class="fw-bold mb-1" style="color: #0f172a;">SPC WAREHOUSE</h4>
          <p class="text-muted mb-0 small"><i class="bi bi-geo-alt"></i> KACHA SADIQ ABAD ROAD NEAR ZOO RYK</p>
        </div>
        <div class="text-end">
          <h4 class="fw-extrabold text-primary mb-1">OPENING STOCK</h4>
          <span class="badge bg-primary-subtle text-primary fw-bold" style="font-size:12px;">#OS-{{ $stockIn->id }}</span>
        </div>
      </div>

      {{-- Header Meta Table --}}
      <table class="table table-bordered header-meta mb-4">
        <tbody>
          <tr>
            <th>Document Date</th>
            <td>{{ $stockIn->created_at->format('d M Y, H:i') }}</td>
            <th>Total Products</th>
            <td>{{ $stockIn->items->pluck('product_id')->unique()->count() }} items</td>
          </tr>
          <tr>
            <th>Total Cartons</th>
            <td>{{ number_format($stockIn->items->sum('units_received')) }} ctn</td>
            <th>Remarks</th>
            <td class="text-muted">{{ $stockIn->remarks ?: 'No remarks provided.' }}</td>
          </tr>
        </tbody>
      </table>

      {{-- Items Table --}}
      <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-list-ul"></i> Product Rows & Locations</h6>
      <div class="table-responsive">
        <table class="table table-bordered table-sm doc-table mb-4">
          <thead class="text-center">
            <tr>
              <th>Item Code</th>
              <th>Description</th>
              <th>Category</th>
              <th>Warehouse</th>
              <th>Row / Slot</th>
              <th>SAP / Vendor Batch</th>
              <th>MFG / Expiry</th>
              <th class="text-end">Units (ctn)</th>
              <th class="text-end">Total Units</th>
              <th>QC Check</th>
              <th>UOM</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @php 
              $totalReceived = 0;
              $totalQty = 0;
            @endphp
            @foreach($stockIn->items as $item)
              @foreach($item->getOriginalPallets() as $pIdx => $palletData)
                @php 
                  $totalReceived += (float) ($palletData['units'] ?? 0);
                  $totalQty += (float) ($palletData['qty'] ?? 0);
                @endphp
                <tr>
                  <td class="font-monospace fw-bold" style="font-size:11.5px;">{{ $item->product->item_code }}</td>
                  <td class="fw-semibold">{{ $item->product->name }}</td>
                  <td>{{ $item->product->category->name ?? '—' }}</td>
                  <td class="text-center">{{ $item->warehouse->name ?? $stockIn->warehouse->name ?? '—' }}</td>
                  <td class="text-center fw-bold text-dark">{{ $item->getPalletName($pIdx) }}</td>
                  <td>
                    <div><small class="text-muted">SAP:</small> {{ $item->sap_batch ?: '—' }}</div>
                    <div><small class="text-muted">Vendor:</small> {{ $item->vendor_batch ?: '—' }}</div>
                  </td>
                  <td class="text-center">
                    <div><small class="text-muted">MFG:</small> {{ $item->mfg_date ? $item->mfg_date->format('d.m.Y') : '—' }}</div>
                    <div><small class="text-muted">EXP:</small> {{ $item->expiry_date ? $item->expiry_date->format('d.m.Y') : '—' }}</div>
                  </td>
                  <td class="text-end font-monospace">{{ number_format($palletData['units'] ?? 0) }}</td>
                  <td class="text-end font-monospace fw-bold">{{ number_format($palletData['qty'] ?? 0, 2) }}</td>
                  <td class="text-center">
                    @php $qc = $item->quality_clearance ?? 'pending'; @endphp
                    <span class="qc-badge qcb-{{ $qc }}">{{ strtoupper($qc) }}</span>
                  </td>
                  <td class="text-center small">{{ $item->product->uom->name ?? '—' }}</td>
                  <td class="text-center">
                    @if($item->block_stock)
                      <span class="status-pill sp-blocked">Blocked</span>
                    @elseif($item->hold_stock)
                      <span class="status-pill sp-hold">Hold</span>
                    @else
                      <span class="status-pill sp-available">Available</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            @endforeach
            <tr class="fw-bold bg-light">
              <td colspan="7" class="text-end">TOTAL</td>
              <td class="text-end font-monospace">{{ number_format($totalReceived) }}</td>
              <td class="text-end font-monospace text-success">{{ number_format($totalQty, 2) }}</td>
              <td colspan="3"></td>
            </tr>
          </tbody>
        </table>
      </div>

      {{-- Signature Fields --}}
      <div class="row mt-5 pt-4">
        <div class="col-4">
          <div class="text-center">
            <p class="mb-4 small fw-bold text-uppercase text-muted">Created By</p>
            <div style="border-bottom: 1px solid #cbd5e1; width: 80%; margin: 0 auto 5px;"></div>
            <p class="small mb-0 text-secondary">Authorized Signature</p>
          </div>
        </div>
        <div class="col-4">
          <div class="text-center">
            <p class="mb-4 small fw-bold text-uppercase text-muted">Audited By</p>
            <div style="border-bottom: 1px solid #cbd5e1; width: 80%; margin: 0 auto 5px;"></div>
            <p class="small mb-0 text-secondary">Authorized Signature</p>
          </div>
        </div>
        <div class="col-4">
          <div class="text-center">
            <p class="mb-4 small fw-bold text-uppercase text-muted">Warehouse In-Charge</p>
            <div style="border-bottom: 1px solid #cbd5e1; width: 80%; margin: 0 auto 5px;"></div>
            <p class="small mb-0 text-secondary">Authorized Signature</p>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>
@endsection
