@extends('layouts.app')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
.ibd-wrap * { font-family: 'Inter', sans-serif; }

/* ── Main Table ── */
.ibd-table thead th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #cbd5e1; font-size: 10.5px; font-weight: 700;
    letter-spacing: .7px; text-transform: uppercase;
    border: none; padding: 13px 14px; white-space: nowrap;
}
.ibd-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .12s; }
.ibd-table tbody tr:hover { background: #f8fafc; }
.ibd-table tbody td { padding: 10px 14px; font-size: 13px; vertical-align: middle; border: none; }

.ibd-details-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border: none; color: #fff; font-size: 11px; font-weight: 600;
    padding: 5px 13px; border-radius: 20px;
    display: inline-flex; align-items: center; gap: 5px;
    transition: all .2s; letter-spacing: .3px; cursor: pointer;
}
.ibd-details-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(59,130,246,.4); }

.pill-wh  { background: linear-gradient(135deg,#1e293b,#334155); color: #e2e8f0; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }

/* ── KPI Strip ── */
.kpi-strip { display: flex; background: #fff; border-bottom: 1px solid #e9edf2; }
.kpi-card  { flex: 1; padding: 14px 18px; text-align: center; border-right: 1px solid #f1f5f9; }
.kpi-card:last-child { border-right: none; }
.kpi-icon  { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 0 auto 5px; font-size: 13px; }
.kpi-val   { font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1; }
.kpi-lbl   { font-size: 9.5px; font-weight: 600; color: #94a3b8; letter-spacing: .5px; text-transform: uppercase; margin-top: 3px; }
.kpi-blue   .kpi-icon { background:#dbeafe; color:#2563eb; }
.kpi-green  .kpi-icon { background:#dcfce7; color:#16a34a; }
.kpi-purple .kpi-icon { background:#ede9fe; color:#7c3aed; }
.kpi-orange .kpi-icon { background:#ffedd5; color:#ea580c; }

/* ── Modal batches ── */
#ibdBatchesModal .modal-content { border: none; border-radius: 16px; overflow: hidden; }
@media (min-width: 993px) {
    #ibdBatchesModal { left: 280px !important; width: calc(100% - 280px) !important; }
}
.state-box { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:60px 20px; gap:12px; color:#94a3b8; }
</style>
@endpush

@section('content')
<div class="ibd-wrap">

{{-- ══════════ MAIN CARD ══════════ --}}
<div class="card shadow-sm border-0" style="border-radius:12px; overflow:hidden;">

  {{-- Header --}}
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"
       style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%); border:none; padding:16px 22px;">
    <div>
      <h6 class="mb-0 fw-bold" style="color:#f1f5f9; font-size:15px; letter-spacing:.3px;">
        <i class="bi bi-box-arrow-in-down me-2" style="color:#93c5fd;"></i>Inbound Stock Dashboard
      </h6>
      <small style="color:#475569; font-size:11px;">Document-wise inbound stock entries</small>
    </div>
    <div class="d-flex gap-2">
      <a href="javascript:void(0)" onclick="exportInbound()" class="btn btn-sm fw-semibold"
         style="background:#134e26; color:#4ade80; border:1px solid #166534; border-radius:8px; font-size:11px; padding:6px 14px;">
        <i class="bi bi-download me-1"></i>Export CSV
      </a>
      <a href="{{ route('inbound.import') }}" class="btn btn-sm fw-semibold"
         style="background:#172554; color:#60a5fa; border:1px solid #1e40af; border-radius:8px; font-size:11px; padding:6px 14px;">
        <i class="bi bi-upload me-1"></i>Import CSV
      </a>
      <a href="{{ route('inbound.create') }}" class="btn btn-sm fw-bold"
         style="background:linear-gradient(135deg,#3b82f6,#1d4ed8); color:#fff; border:none; border-radius:8px; font-size:11px; padding:6px 16px;">
        <i class="bi bi-plus-lg me-1"></i>New Inbound
      </a>
    </div>
  </div>

  {{-- KPI Strip --}}
  <div class="kpi-strip">
    <div class="kpi-card kpi-blue">
      <div class="kpi-icon"><i class="bi bi-receipt"></i></div>
      <div class="kpi-val">{{ $transactions->total() }}</div>
      <div class="kpi-lbl">Total Entries</div>
    </div>
    <div class="kpi-card kpi-green">
      <div class="kpi-icon"><i class="bi bi-boxes"></i></div>
      <div class="kpi-val">{{ $transactions->getCollection()->sum(fn($tx) => $tx->items->count()) }}</div>
      <div class="kpi-lbl">Total Products</div>
    </div>
    <div class="kpi-card kpi-purple">
      <div class="kpi-icon"><i class="bi bi-layers"></i></div>
      <div class="kpi-val">{{ number_format($transactions->getCollection()->sum(fn($tx) => $tx->items->sum('units_received'))) }}</div>
      <div class="kpi-lbl">Total Cartons</div>
    </div>
    <div class="kpi-card kpi-orange">
      <div class="kpi-icon"><i class="bi bi-building"></i></div>
      <div class="kpi-val">{{ $transactions->getCollection()->pluck('warehouse_id')->unique()->count() }}</div>
      <div class="kpi-lbl">Warehouses</div>
    </div>
  </div>

  {{-- Filters --}}
  <div class="p-3 bg-light border-bottom">
    <form id="inboundFilterForm">
      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label fw-semibold small">Search</label>
          <input type="text" name="search" id="filter_search" class="form-control form-control-sm filter-field" placeholder="Invoice, vehicle, driver, vendor...">
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
          <label class="form-label fw-semibold small">Vendor</label>
          <select name="vendor_id" id="filter_vendor" class="form-select form-select-sm filter-field">
            <option value="">All Vendors</option>
            @foreach($vendors as $v)
              <option value="{{ $v->id }}">{{ $v->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold small">Date From</label>
          <input type="date" name="date_from" id="filter_date_from" class="form-control form-control-sm filter-field">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold small">Date To</label>
          <input type="date" name="date_to" id="filter_date_to" class="form-control form-control-sm filter-field">
        </div>
        <div class="col-md-1">
          <label class="form-label fw-semibold small">&nbsp;</label>
          <div class="d-flex gap-1">
            <button type="button" id="applyFilters" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
            <button type="button" id="resetFilters"  class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></button>
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

  {{-- Table --}}
  <div class="table-responsive">
    <table class="table table-sm mb-0 align-middle ibd-table">
      <thead>
        <tr>
          <th width="46">#</th>
          <th>Invoice No</th>
          <th>Inbound Date</th>
          <th>Warehouse</th>
          <th>Vendor</th>
          <th>Transporter</th>
          <th class="text-center">Products</th>
          <th class="text-end">Total Cartons</th>
          <th>Vehicle / Driver</th>
          <th class="text-center" width="160">Action</th>
        </tr>
      </thead>
      <tbody id="inboundTableBody">
        @forelse($transactions as $tx)
        <tr>
          <td class="text-muted" style="font-size:11px;">{{ ($transactions->currentPage()-1)*$transactions->perPage()+$loop->iteration }}</td>

          <td>
            <span class="fw-bold" style="color:#1d4ed8; font-family:'Courier New',monospace; font-size:12px;">
              {{ $tx->dispatched_invoice_no ?: ('#IBD-'.$tx->id) }}
            </span>
          </td>

          <td style="font-size:11.5px; color:#64748b;">
            {{ $tx->created_at->format('d.m.Y H:i') }}
          </td>

          <td>
            <span class="pill-wh"><i class="bi bi-building"></i> {{ $tx->warehouse->name ?? 'Auto' }}</span>
          </td>

          <td style="font-size:12px;">{{ $tx->vendor->name ?? '—' }}</td>
          <td style="font-size:12px;">{{ $tx->transporter->name ?? '—' }}</td>

          <td class="text-center">
            <span style="background:#f1f5f9;color:#475569;padding:3px 9px;border-radius:12px;font-size:11px;font-weight:700;border:1px solid #e2e8f0;">
              {{ $tx->items->count() }} items
            </span>
          </td>

          <td class="text-end fw-bold" style="color:#0f172a;">
            {{ number_format($tx->items->sum('units_received')) }}
          </td>

          <td style="font-size:11px;">
            <div>{{ $tx->vehicle_no ?: '—' }}</div>
            <small class="text-muted">{{ $tx->driver_name ?: '' }}</small>
          </td>

          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
              <button class="ibd-details-btn view-ibd-batches-btn"
                      data-stock-in-id="{{ $tx->id }}"
                      data-invoice="{{ $tx->dispatched_invoice_no ?: '#IBD-'.$tx->id }}">
                <i class="bi bi-list-columns-reverse"></i> Details
              </button>
              <a href="{{ route('inbound.edit', $tx->id) }}"
                 class="btn btn-sm btn-warning fw-bold text-white d-inline-flex align-items-center gap-1 shadow-sm"
                 style="font-size:11px; padding:5px 13px; border-radius:20px; border:none; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"
                 title="Edit Entry">
                <i class="bi bi-pencil"></i> Edit
              </a>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="10" class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size:36px;display:block;opacity:.3;margin-bottom:8px;"></i>
            No inbound stock entries found
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
        Showing <strong>{{ $transactions->firstItem() }}</strong> to <strong>{{ $transactions->lastItem() }}</strong> of <strong>{{ $transactions->total() }}</strong> entries
      </div>
      <div>{{ $transactions->links('pagination::bootstrap-5') }}</div>
    </div>
  </div>
  @endif

</div>
</div>

{{-- ══════════ BATCHES DETAIL MODAL ══════════ --}}
<div class="modal fade" id="ibdBatchesModal" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:1060px;">
<div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">

  {{-- Modal Header --}}
  <div class="modal-header" style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);border:none;padding:20px 24px;">
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="width:40px;height:40px;background:rgba(59,130,246,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
        <i class="bi bi-box-arrow-in-down" style="color:#93c5fd;font-size:18px;"></i>
      </div>
      <div>
        <h6 class="modal-title mb-0" style="color:#f1f5f9;font-size:14px;font-weight:700;letter-spacing:.3px;">
          Inbound Entry Details
        </h6>
        <small id="ibdModalInvoiceName" style="color:#60a5fa;font-size:11.5px;font-weight:600;"></small>
      </div>
    </div>
    <button class="btn-close" data-bs-dismiss="modal" style="filter:invert(1) brightness(2);"></button>
  </div>

  {{-- KPI Strip --}}
  <div class="kpi-strip" id="ibdBatchKpiStrip" style="display:none;">
    <div class="kpi-card kpi-blue">
      <div class="kpi-icon"><i class="bi bi-archive"></i></div>
      <div class="kpi-val" id="ibdKpiItems">—</div>
      <div class="kpi-lbl">Products</div>
    </div>
    <div class="kpi-card kpi-green">
      <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
      <div class="kpi-val" id="ibdKpiUnits">—</div>
      <div class="kpi-lbl">Total Cartons</div>
    </div>
    <div class="kpi-card kpi-purple">
      <div class="kpi-icon"><i class="bi bi-layers"></i></div>
      <div class="kpi-val" id="ibdKpiPallets">—</div>
      <div class="kpi-lbl">Total Pallets</div>
    </div>
    <div class="kpi-card kpi-orange">
      <div class="kpi-icon"><i class="bi bi-stack"></i></div>
      <div class="kpi-val" id="ibdKpiQty">—</div>
      <div class="kpi-lbl">Balance Qty</div>
    </div>
  </div>

  {{-- Body --}}
  <div class="modal-body" style="background:#fff;padding:0;">
    <div id="ibdBatchLoadingState" class="state-box py-5 text-center" style="display:none;">
      <div class="spinner-border text-primary" style="width:30px;height:30px;border-width:3px;"></div>
      <div class="text-muted mt-2 small">Loading details...</div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-sm mb-0 align-middle small" style="font-size: 12.5px;">
        <thead class="table-dark">
          <tr>
            <th>Product</th>
            <th>Warehouse</th>
            <th>Row / Location</th>
            <th>SAP Batch</th>
            <th>Expiry Date</th>
            <th class="text-end">Units (ctn)</th>
            <th class="text-end">Balance Qty</th>
            <th class="text-center">Pallets</th>
            <th class="text-center">QC</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody id="ibdBatchesTableBody">
          <!-- Loaded dynamically -->
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>
</div>

@endsection

@push('scripts')
<script>
/* ── Click delegation ── */
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.view-ibd-batches-btn');
    if (!btn) return;

    var stockInId = btn.dataset.stockInId;
    var invoice   = btn.dataset.invoice;

    document.getElementById('ibdModalInvoiceName').innerText = invoice;
    document.getElementById('ibdBatchLoadingState').style.display = 'block';
    document.getElementById('ibdBatchKpiStrip').style.display = 'none';
    var tbody = document.getElementById('ibdBatchesTableBody');
    tbody.innerHTML = '';

    new bootstrap.Modal(document.getElementById('ibdBatchesModal')).show();

    fetch('/inbound/' + stockInId + '/items', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('ibdBatchLoadingState').style.display = 'none';

        if (!data || !data.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5 text-muted">No items found</td></tr>';
            return;
        }

        var totalUnits = 0, totalPallets = 0, totalBalance = 0;
        data.forEach(function(item) {
            totalUnits   += parseInt(item.units_received) || 0;
            totalPallets += parseInt(item.pallets_used) || 0;
            totalBalance += parseFloat(item.balance_quantity || 0);
        });

        document.getElementById('ibdKpiItems').textContent   = data.length;
        document.getElementById('ibdKpiUnits').textContent   = totalUnits.toLocaleString();
        document.getElementById('ibdKpiPallets').textContent = totalPallets;
        document.getElementById('ibdKpiQty').textContent     = totalBalance.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('ibdBatchKpiStrip').style.display = 'flex';

        var fmtDate = function(str) {
            if (!str) return '—';
            var d = new Date(str);
            return d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
        };

        data.forEach(function(item) {
            var qc = item.quality_clearance || 'pending';
            var qcHtml = '<span style="background:#fef3c7;color:#92400e;font-size:10px;padding:3px 9px;border-radius:12px;font-weight:700;">Pending</span>';
            if (qc === 'approved') qcHtml = '<span style="background:#d1fae5;color:#065f46;font-size:10px;padding:3px 9px;border-radius:12px;font-weight:700;">Approved</span>';
            if (qc === 'rejected') qcHtml = '<span style="background:#fee2e2;color:#991b1b;font-size:10px;padding:3px 9px;border-radius:12px;font-weight:700;">Rejected</span>';

            var sHtml = '<span style="background:#dcfce7;color:#15803d;font-size:10px;padding:3px 9px;border-radius:12px;font-weight:700;">Available</span>';
            if (item.block_stock) sHtml = '<span style="background:#fee2e2;color:#b91c1c;font-size:10px;padding:3px 9px;border-radius:12px;font-weight:700;">Blocked</span>';
            else if (item.hold_stock) sHtml = '<span style="background:#fef9c3;color:#92400e;font-size:10px;padding:3px 9px;border-radius:12px;font-weight:700;">Hold</span>';

            var wh      = (item.stock_in && item.stock_in.warehouse) ? item.stock_in.warehouse.name : '—';
            var rowName = (item.warehouse_row) ? item.warehouse_row.row_name : '—';
            var loc     = item.pallet_range_display || rowName;

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="fw-bold">' + ((item.product ? item.product.item_code + ' - ' + item.product.name : '—')) + '</td>' +
                '<td>' + wh + '</td>' +
                '<td class="fw-bold text-dark">' + loc + '</td>' +
                '<td>' + (item.sap_batch || '—') + '</td>' +
                '<td class="fw-bold">' + fmtDate(item.expiry_date) + '</td>' +
                '<td class="text-end font-monospace">' + (parseInt(item.units_received)||0).toLocaleString() + '</td>' +
                '<td class="text-end font-monospace fw-bold text-success">' + parseFloat(item.balance_quantity||0).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>' +
                '<td class="text-center"><span class="badge bg-secondary">' + (item.pallets_used||0) + '</span></td>' +
                '<td class="text-center">' + qcHtml + '</td>' +
                '<td class="text-center">' + sHtml + '</td>';
            tbody.appendChild(tr);
        });
    })
    .catch(function(err) {
        console.error(err);
        document.getElementById('ibdBatchLoadingState').style.display = 'none';
        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger py-5">Failed to load details</td></tr>';
    });
});

function exportInbound() {
    const params = new URLSearchParams({
        search: $('#filter_search').val() || '',
        warehouse_id: $('#filter_warehouse').val() || '',
        vendor_id: $('#filter_vendor').val() || '',
        date_from: $('#filter_date_from').val() || '',
        date_to: $('#filter_date_to').val() || ''
    });
    window.location.href = '{{ route("inbound.export") }}?' + params.toString();
}

/* ── Filter functionality ── */
$(document).ready(function() {
    $('#applyFilters').on('click', function() { applyFilters(); });
    $('#resetFilters').on('click', function() { $('#inboundFilterForm')[0].reset(); applyFilters(); });
    $('.filter-field').on('change keyup', function() {
        clearTimeout(window.filterTimeout);
        window.filterTimeout = setTimeout(applyFilters, 500);
    });

    function applyFilters() {
        $('#filterLoadingOverlay').show();
        $.ajax({
            url: '{{ route("inbound.index") }}',
            type: 'GET',
            data: {
                search:       $('#filter_search').val(),
                warehouse_id: $('#filter_warehouse').val(),
                vendor_id:    $('#filter_vendor').val(),
                date_from:    $('#filter_date_from').val(),
                date_to:      $('#filter_date_to').val(),
            },
            success: function(response) {
                var doc = new DOMParser().parseFromString(response, 'text/html');
                var newBody = doc.querySelector('#inboundTableBody');
                if (newBody) $('#inboundTableBody').html(newBody.innerHTML);
                $('#filterLoadingOverlay').hide();
            },
            error: function() { $('#filterLoadingOverlay').hide(); }
        });
    }
});
</script>
@endpush
