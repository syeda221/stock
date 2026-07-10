@extends('layouts.app')

@section('content')

<div class="card shadow-sm">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h6 class="mb-0">Opening Stock (Batch Wise)</h6>
        <div class="d-flex gap-2">
            <a href="{{ route('opening-stock.export') }}"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-download"></i> Export
            </a>
            <a href="{{ route('opening-stock.import') }}"
               class="btn btn-sm btn-outline-info">
                <i class="bi bi-upload"></i> Import
            </a>
            <a href="{{ route('opening-stock.create') }}"
               class="btn btn-sm btn-primary">
                + Add Opening Stock
            </a>
        </div>
    </div>

    <div class="card-body p-3">
        <!-- Filters -->
        <form id="openingStockFilterForm" class="mb-0">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Search</label>
                    <input type="text" name="search" id="filter_search" class="form-control form-control-sm filter-field" placeholder="Item code or name...">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Warehouse</label>
                    <select name="warehouse_id" id="filter_warehouse" class="form-select form-select-sm filter-field">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Product</label>
                    <select name="product_id" id="filter_product" class="form-select form-select-sm filter-field">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
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
                    <div class="d-flex gap-2">
                        <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                        <span class="ms-2 text-muted small align-self-center">Total: <strong id="totalCount">{{ $items->count() }}</strong></span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div id="filterLoadingOverlay" style="display: none; position: relative;">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Applying filters...</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success m-3">
                <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger m-3">
                <i class="bi bi-x-circle me-1"></i> {{ session('error') }}
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle">
                <thead class="table-light">
                <tr>
                    <th width="60">#</th>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th class="text-center">Entries</th>
                    <th class="text-end">Total Units</th>
                    <th class="text-center">Total Pallets</th>
                    <th class="text-end">Total Qty</th>
                    <th width="140" class="text-center">Action</th>
                </tr>
                </thead>

                <tbody id="openingStockTableBody">
                @forelse($items as $item)
                    <tr>
                        <td>{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>

                        <td class="fw-semibold">
                            {{ $item->product->item_code }}
                        </td>

                        <td>{{ $item->product->name }}</td>

                        <td>{{ $item->product->category->name ?? '-' }}</td>

                        <td>{{ $item->latest_date ? \Carbon\Carbon::parse($item->latest_date)->format('d.m.Y H:i') : '-' }}</td>

                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $item->batch_count }}</span>
                        </td>

                        <td class="text-end">{{ $item->total_units }}</td>

                        <td class="text-center">
                            @if($item->total_pallets)
                                <span class="badge bg-info text-dark">
                                    <i class="bi bi-layers me-1"></i>{{ $item->total_pallets }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td class="text-end fw-bold">
                            {{ rtrim(rtrim(number_format($item->total_qty, 2), '0'), '.') }}
                        </td>

                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary view-batches-btn"
                                    data-product-id="{{ $item->product_id }}"
                                    data-product-name="{{ $item->product->name }} ({{ $item->product->item_code ?? '' }})"
                                    title="View Details">
                                👁 Details
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                    <td colspan="10"
                        class="text-center text-muted py-4">
                        No opening stock found
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($items->hasPages())
        <div class="card-footer bg-light border-top py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Showing <strong>{{ $items->firstItem() }}</strong> to <strong>{{ $items->lastItem() }}</strong> of <strong>{{ $items->total() }}</strong> entries
                </div>
                <div>
                    {{ $items->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

{{-- ================= VIEW MODAL ================= --}}
<div class="modal fade" id="viewModal" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-scrollable">
<div class="modal-content">

<div class="modal-header">
    <h6 class="modal-title">Opening Stock Details</h6>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

{{-- ===== Warehouse Info ===== --}}
<div class="row g-3 mb-3">
    <div class="col-md-4"><strong>Warehouse:</strong> <span id="vWarehouse"></span></div>
    <div class="col-md-4"><strong>Row:</strong> <span id="vRow"></span></div>
    <div class="col-md-4"><strong>Date:</strong> <span id="vDate"></span></div>
</div>

<hr>

{{-- ===== Product Info ===== --}}
<div class="row g-3 mb-3">
    <div class="col-md-4"><strong>Item Code:</strong> <span id="vCode"></span></div>
    <div class="col-md-8"><strong>Description:</strong> <span id="vDesc"></span></div>
    <div class="col-md-4"><strong>Category:</strong> <span id="vCategory"></span></div>
</div>

<hr>

{{-- ===== Batch Info ===== --}}
<div class="row g-3 mb-3">
    <div class="col-md-3"><strong>SAP Batch:</strong> <span id="vSap"></span></div>
    <div class="col-md-3"><strong>Vendor Batch:</strong> <span id="vVendor"></span></div>
    <div class="col-md-3"><strong>IBD:</strong> <span id="vIbd"></span></div>
    <div class="col-md-3"><strong>PO:</strong> <span id="vPo"></span></div>

    <div class="col-md-3"><strong>MFG:</strong> <span id="vMfg"></span></div>
    <div class="col-md-3"><strong>Expiry:</strong> <span id="vExpiry"></span></div>
</div>

<hr>

{{-- ===== Quantity Info ===== --}}
<div class="row g-3 mb-3">
    <div class="col-md-3"><strong>Units:</strong> <span id="vUnits"></span></div>
    <div class="col-md-3"><strong>Pack Size:</strong> <span id="vPack"></span></div>
    <div class="col-md-3"><strong>Total Qty:</strong> <span id="vTotal"></span></div>
    <div class="col-md-3"></div>

    <div class="col-md-3">
        <strong>Pallets Used:</strong>
        <span id="vPallets" class="badge bg-info text-dark"></span>
    </div>
    <div class="col-md-3">
        <strong>Cartons/Pallet:</strong>
        <span id="vCartonsPerPallet" class="text-muted"></span>
    </div>
    <div class="col-md-6"><strong>Remarks:</strong> <span id="vRemarks"></span></div>
</div>

</div>

</div>
</div>
</div>

{{-- ================= BATCHES DETAILS MODAL ================= --}}
<div class="modal fade" id="batchesModal" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-scrollable">
<div class="modal-content">
    <div class="modal-header">
        <h6 class="modal-title">Locations & Batches for <span id="batchModalProductName" class="text-primary fw-bold"></span></h6>
        <button class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Warehouse</th>
                        <th>Row / Slot</th>
                        <th>SAP / Vendor Batch</th>
                        <th>MFG / Expiry</th>
                        <th class="text-end">Units</th>
                        <th class="text-end">Total Qty</th>
                        <th class="text-center">Pallets</th>
                        <th>QC</th>
                        <th>Status</th>
                        <th width="110" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="batchesTableBody">
                    <!-- Loaded dynamically via AJAX -->
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
// Fix for multiple stacked modals in Bootstrap
document.addEventListener('show.bs.modal', function (event) {
    var zIndex = 1055 + (10 * document.querySelectorAll('.modal.show').length);
    event.target.style.zIndex = zIndex;
    setTimeout(function() {
        var backdrops = document.querySelectorAll('.modal-backdrop:not(.modal-stack)');
        backdrops.forEach(function(backdrop) {
            backdrop.style.zIndex = zIndex - 1;
            backdrop.classList.add('modal-stack');
        });
    }, 0);
});

document.addEventListener('click',function(e){
    // VIEW BTN
    let viewBtn = e.target.closest('.view-btn');
    if (viewBtn) {
        const d = JSON.parse(viewBtn.dataset.item);

        document.getElementById('vWarehouse').innerText = d.stock_in?.warehouse?.name ?? '-';
        document.getElementById('vRow').innerText = d.warehouse_row?.row_name ?? '-';
        document.getElementById('vDate').innerText = d.created_at ? d.created_at.substring(0,16).replace('T', ' ') : '-';

        document.getElementById('vCode').innerText = d.product.item_code;
        document.getElementById('vDesc').innerText = d.product.name;
        document.getElementById('vCategory').innerText = d.product.category?.name ?? '-';

        document.getElementById('vSap').innerText = d.sap_batch ?? '-';
        document.getElementById('vVendor').innerText = d.vendor_batch ?? '-';
        document.getElementById('vIbd').innerText = d.ibd_no ?? '-';
        document.getElementById('vPo').innerText = d.po_no ?? '-';

        document.getElementById('vMfg').innerText = d.mfg_date ?? '-';
        document.getElementById('vExpiry').innerText = d.expiry_date ?? '-';

        document.getElementById('vUnits').innerText = d.units_received;
        document.getElementById('vPack').innerText = d.pack_size_snapshot;
        document.getElementById('vTotal').innerText = d.total_quantity;
        document.getElementById('vPallets').innerText = d.pallets_used ?? '-';
        document.getElementById('vCartonsPerPallet').innerText = d.product?.cartons_per_pallet ? d.product.cartons_per_pallet + ' ctn/pallet' : 'Not set';
        document.getElementById('vRemarks').innerText = d.remarks ?? '-';

        new bootstrap.Modal(document.getElementById('viewModal')).show();
        return;
    }

    // VIEW BATCHES BTN
    let viewBatchesBtn = e.target.closest('.view-batches-btn');
    if (viewBatchesBtn) {
        const prodId = viewBatchesBtn.dataset.productId;
        const prodName = viewBatchesBtn.dataset.productName;

        document.getElementById('batchModalProductName').innerText = prodName;
        const tbody = document.getElementById('batchesTableBody');
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4"><span class="spinner-border spinner-border-sm" role="status"></span> Loading batches...</td></tr>';

        new bootstrap.Modal(document.getElementById('batchesModal')).show();

        fetch(`/opening-stock/product/${prodId}/batches`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-4">No batches found</td></tr>';
                return;
            }

            data.forEach(item => {
                const warehouseName = item.stock_in?.warehouse?.name ?? '-';
                const rowName = item.warehouse_row?.row_name ?? '-';
                const sapBatch = item.sap_batch ?? '-';
                const vendorBatch = item.vendor_batch ?? '-';
                const mfg = item.mfg_date ?? '-';
                const expiry = item.expiry_date ?? '-';
                const units = item.units_received;
                const balance = parseFloat(item.total_quantity).toFixed(2);
                
                let originalPallets = item.pallets_used ?? 0;
                if (item.product && item.product.cartons_per_pallet > 0 && item.units_received > 0) {
                    originalPallets = Math.ceil(item.units_received / item.product.cartons_per_pallet);
                }
                const pallets = originalPallets;
                const qc = item.quality_clearance ?? 'pending';

                // Status Badge
                let statusBadge = '';
                if (item.block_stock) {
                    statusBadge = '<span class="badge bg-danger">Blocked</span>';
                } else if (item.hold_stock) {
                    statusBadge = '<span class="badge bg-warning text-dark">Hold</span>';
                } else {
                    statusBadge = '<span class="badge bg-success">Available</span>';
                }

                // QC Badge
                let qcBadge = '';
                if (qc === 'approved') {
                    qcBadge = '<span class="badge bg-success">Approved</span>';
                } else if (qc === 'rejected') {
                    qcBadge = '<span class="badge bg-danger">Rejected</span>';
                } else {
                    qcBadge = '<span class="badge bg-warning text-dark">Pending</span>';
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${warehouseName}</td>
                    <td>${rowName}</td>
                    <td>
                        <div><small>SAP:</small> ${sapBatch}</div>
                        <div><small>Vendor:</small> ${vendorBatch}</div>
                    </td>
                    <td>
                        <div><small>MFG:</small> ${mfg}</div>
                        <div><small>EXP:</small> ${expiry}</div>
                    </td>
                    <td class="text-end">${units}</td>
                    <td class="text-end fw-bold">${balance}</td>
                    <td class="text-center">${pallets}</td>
                    <td>${qcBadge}</td>
                    <td>${statusBadge}</td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm btn-outline-primary view-btn"
                                    data-item='${JSON.stringify(item)}' title="View Detail">
                                👁
                            </button>
                            <a href="/opening-stock/${item.id}/edit" class="btn btn-sm btn-outline-warning" title="Edit">
                                ✏️
                            </a>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger py-4">Error loading batches</td></tr>';
        });
        return;
    }
});

// Filter functionality
$(document).ready(function() {
    $('#applyFilters').on('click', function() {
        applyFilters();
    });

    $('#resetFilters').on('click', function() {
        $('#openingStockFilterForm')[0].reset();
        applyFilters();
    });

    $('.filter-field').on('change keyup', function() {
        clearTimeout(window.filterTimeout);
        window.filterTimeout = setTimeout(applyFilters, 500);
    });

    function applyFilters() {
        const formData = {
            search: $('#filter_search').val(),
            warehouse_id: $('#filter_warehouse').val(),
            product_id: $('#filter_product').val(),
            stock_status: $('#filter_stock_status').val()
        };

        $('#filterLoadingOverlay').show();

        $.ajax({
            url: '{{ route("opening-stock.index") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');
                const newTableBody = doc.querySelector('#openingStockTableBody');
                if (newTableBody) {
                    $('#openingStockTableBody').html(newTableBody.innerHTML);
                }
                const totalCount = $(newTableBody).find('tr').length;
                $('#totalCount').text(totalCount);
                $('#filterLoadingOverlay').hide();
            },
            error: function(xhr, status, error) {
                console.error('Filter error:', error);
                alert('An error occurred while filtering. Please try again.');
                $('#filterLoadingOverlay').hide();
            }
        });
    }
});
</script>
@endpush
