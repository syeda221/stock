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
                        <option value="stock_out">⚫ Stock Out</option>
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
                    <th>#</th>
                    <th>Warehouse</th>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>SAP / Vendor</th>
                    <th class="text-end">Units</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center">Pallets</th>
                    <th class="text-center">Age</th>
                    <th>Status</th>
                    <th width="70">View</th>
                </tr>
                </thead>

                <tbody id="openingStockTableBody">
                @forelse($items as $item)
                    <tr>
                        <td>{{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}</td>

                        <td>{{ $item->stockIn->warehouse->name }}</td>

                        <td class="fw-semibold">
                            {{ $item->product->item_code }}
                        </td>

                        <td>{{ $item->product->name }}</td>

                        <td>
                            <div><small>SAP:</small> {{ $item->sap_batch ?? '-' }}</div>
                            <div><small>Vendor:</small> {{ $item->vendor_batch ?? '-' }}</div>
                        </td>

                        <td class="text-end">{{ $item->units_received }}</td>

                        <td class="text-end fw-bold">
                            @php
                                $balUnits = $item->pack_size_snapshot > 0 ? $item->balance_quantity / $item->pack_size_snapshot : 0;
                            @endphp
                            {{ rtrim(rtrim(number_format($balUnits, 2), '0'), '.') }} U<br>
                            <small class="text-muted fw-normal">({{ rtrim(rtrim(number_format($item->balance_quantity, 2), '0'), '.') }} Qty)</small>
                        </td>

                        <td class="text-center">
                            @if($item->pallets_used)
                                <span class="badge bg-info text-dark" title="{{ $item->product->cartons_per_pallet ? $item->product->cartons_per_pallet.' ctn/pallet' : '' }}">
                                    <i class="bi bi-layers me-1"></i>{{ $item->pallets_used }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        {{-- 🔥 Stock Age --}}
                       <td class="text-center">
    @php
        $days = $item->created_at->startOfDay()->diffInDays(now()->startOfDay());
    @endphp

    @if($days ==0)
        <span class="badge bg-info">Today</span>
    @elseif($days == 1)
        <span class="badge bg-secondary">Yesterday</span>
    @else
        <span class="badge bg-light text-dark">{{ $days }} days</span>
    @endif
</td>


                        {{-- 🔥 STATUS LOGIC --}}
                        <td>
                            @if($item->balance_quantity == 0)
                                <span class="badge bg-dark">Stock Out</span>
                            @elseif($item->block_stock)
                                <span class="badge bg-danger">Blocked</span>
                            @elseif($item->hold_stock)
                                <span class="badge bg-warning text-dark">Hold</span>
                            @else
                                <span class="badge bg-success">Available</span>
                            @endif
                        </td>

                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary view-btn"
                                    data-item='@json($item)'>
                                👁
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11"
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
    <div class="col-md-3"><strong>Balance:</strong> <span id="vBalance"></span></div>

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
@endsection

@push('scripts')
<script>
document.addEventListener('click',function(e){

if(!e.target.classList.contains('view-btn')) return;

const d = JSON.parse(e.target.dataset.item);

document.getElementById('vWarehouse').innerText = d.stock_in.warehouse.name;
document.getElementById('vRow').innerText = d.warehouse_row?.row_name ?? '-';
document.getElementById('vDate').innerText = d.created_at?.substring(0,10);

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
document.getElementById('vBalance').innerText = d.balance_quantity;
document.getElementById('vPallets').innerText = d.pallets_used ?? '-';
document.getElementById('vCartonsPerPallet').innerText = d.product?.cartons_per_pallet ? d.product.cartons_per_pallet + ' ctn/pallet' : 'Not set';
document.getElementById('vRemarks').innerText = d.remarks ?? '-';

new bootstrap.Modal(document.getElementById('viewModal')).show();
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

                // Rebind view button events
                document.querySelectorAll('.view-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const d = JSON.parse(this.dataset.item);
                        document.getElementById('vWarehouse').innerText = d.stock_in?.warehouse?.name ?? '-';
                        document.getElementById('vProduct').innerText = d.product?.name ?? '-';
                        document.getElementById('vCategory').innerText = d.product?.category?.name ?? '-';
                        document.getElementById('vUom').innerText = d.product?.uom?.name ?? '-';
                        document.getElementById('vPacking').innerText = d.product?.packing_type?.name ?? '-';
                        document.getElementById('vSap').innerText = d.sap_batch ?? '-';
                        document.getElementById('vVendor').innerText = d.vendor_batch ?? '-';
                        document.getElementById('vIbd').innerText = d.ibd_no ?? '-';
                        document.getElementById('vPo').innerText = d.po_no ?? '-';
                        document.getElementById('vMfg').innerText = d.mfg_date ?? '-';
                        document.getElementById('vExpiry').innerText = d.expiry_date ?? '-';
                        document.getElementById('vUnits').innerText = d.units_received;
                        document.getElementById('vPack').innerText = d.pack_size_snapshot;
                        document.getElementById('vTotal').innerText = d.total_quantity;
                        document.getElementById('vBalance').innerText = d.balance_quantity;
                        document.getElementById('vPallets').innerText = d.pallets_used ?? 0;
                        document.getElementById('vRemarks').innerText = d.remarks ?? '-';
                        new bootstrap.Modal(document.getElementById('viewModal')).show();
                    });
                });
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
