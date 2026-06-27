@extends('layouts.app')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a>
                </li>
                <li class="breadcrumb-item">Reports</li>
                <li class="breadcrumb-item active" aria-current="page">Warehouse Stock</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold">Warehouse Stock Report</h5>
        <small class="text-muted">View stock levels by warehouse, location, batch, and pallet details</small>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="bi bi-buildings text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Warehouses</h6>
                        <h4 class="mb-0 fw-bold" id="summaryWarehouses">{{ $summary['total_warehouses'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="bi bi-box-seam text-info fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Products</h6>
                        <h4 class="mb-0 fw-bold text-info" id="summaryProducts">{{ $summary['total_products'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="bi bi-pallet text-warning fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Pallets</h6>
                        <h4 class="mb-0 fw-bold text-warning" id="summaryPallets">{{ number_format($summary['total_pallets']) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="bi bi-box-seam text-success fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Balance</h6>
                        <h4 class="mb-0 fw-bold text-success" id="summaryBalance">{{ number_format($summary['total_balance'], 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Section --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-3">
        <form id="warehouseStockFilterForm" class="mb-0">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Warehouse</label>
                    <select name="warehouse_id" id="filter_warehouse" class="form-select form-select-sm filter-field">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Product</label>
                    <select name="product_id" id="filter_product" class="form-select form-select-sm filter-field">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->item_code }} - {{ $product->name }}</option>
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
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="mt-2">
                <span class="text-muted small">Total: <strong id="totalCount">{{ count($stockReport) }}</strong> records</span>
            </div>
        </form>
    </div>
</div>

{{-- Data Table --}}
<div class="card border-0 shadow-lg rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div id="filterLoadingOverlay" style="display: none; position: relative;">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading data...</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Item Code</th>
                        <th>Product Name</th>
                        <th>Warehouse</th>
                        <th>Location</th>
                        <th class="text-end">Pallets Used</th>
                        <th class="text-end">Units</th>
                        <th>SAP Batch</th>
                        <th>Vendor Batch</th>
                        <th>Expiry Date</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">QC Status</th>
                        <th class="text-center" style="width:80px;">Action</th>
                    </tr>
                </thead>

                <tbody id="stockTableBody">
                    @forelse($stockReport as $index => $item)
                        <tr>
                            <td class="text-muted fw-semibold">{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $item->item_code }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $item->product_name }}</div>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                    <i class="bi bi-buildings me-1"></i>{{ $item->warehouse_name }}
                                </span>
                            </td>
                            <td>
                                @if(!empty($item->warehouse_display))
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-geo-alt me-1"></i>{{ $item->warehouse_display }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="fw-semibold">
                                    {{ $item->pallets_used ? number_format($item->pallets_used) : '—' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="fw-semibold">{{ $item->units_received ?? 0 }}</span>
                                <br><small class="text-muted">{{ $item->units_received ?? 0 }} × {{ $item->pack_size_snapshot }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $item->sap_batch ?: '—' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $item->vendor_batch ?: '—' }}</span>
                            </td>
                            <td>
                                @if($item->expiry_date)
                                    <span class="badge {{ \Carbon\Carbon::parse($item->expiry_date)->isPast() ? 'bg-danger' : 'bg-success' }} bg-opacity-10 text-dark">
                                        {{ \Carbon\Carbon::parse($item->expiry_date)->format('d.m.Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-success">
                                    {{ number_format($item->balance_quantity, 2) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($item->quality_clearance === 'cleared')
                                    <span class="badge bg-success">Cleared</span>
                                @elseif($item->quality_clearance === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($item->quality_clearance === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary quick-view-btn"
                                    data-item_code="{{ $item->item_code }}"
                                    data-product_name="{{ $item->product_name }}"
                                    data-warehouse_name="{{ $item->warehouse_name }}"
                                    data-row_name="{{ $item->warehouse_display ?? '—' }}"
                                    data-pallets_used="{{ $item->pallets_used ? number_format($item->pallets_used) : '—' }}"
                                    data-sap_batch="{{ $item->sap_batch ?: '—' }}"
                                    data-vendor_batch="{{ $item->vendor_batch ?: '—' }}"
                                    data-expiry_date="{{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('d.m.Y') : '—' }}"
                                    data-balance_quantity="{{ number_format($item->balance_quantity, 2) }}"
                                    data-quality_clearance="{{ $item->quality_clearance ?: '—' }}"
                                    data-mfg_date="{{ $item->mfg_date ? \Carbon\Carbon::parse($item->mfg_date)->format('d.m.Y') : '—' }}"
                                    data-total_quantity="{{ number_format($item->total_quantity, 2) }}"
                                    title="Quick View">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No stock data found
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if(count($stockReport) > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end">Totals:</td>
                        <td class="text-end text-warning">{{ number_format($summary['total_pallets']) }}</td>
                        <td></td>
                        <td colspan="4"></td>
                        <td class="text-end text-success">{{ number_format($summary['total_balance'], 2) }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Quick View Modal --}}
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="bi bi-eye me-2"></i>Stock Detail</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Item Code</small>
                            <strong class="fs-6" id="mdl_item_code">—</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Product Name</small>
                            <strong class="fs-6" id="mdl_product_name">—</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Warehouse</small>
                            <strong id="mdl_warehouse_name">—</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Location</small>
                            <strong id="mdl_row_name">—</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Total Quantity</small>
                            <strong id="mdl_total_quantity">—</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Balance Quantity</small>
                            <strong class="text-success" id="mdl_balance_quantity">—</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Pallets Used</small>
                            <strong id="mdl_pallets_used">—</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">SAP Batch</small>
                            <strong id="mdl_sap_batch">—</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Vendor Batch</small>
                            <strong id="mdl_vendor_batch">—</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Mfg Date</small>
                            <strong id="mdl_mfg_date">—</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Expiry Date</small>
                            <strong id="mdl_expiry_date">—</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">QC Status</small>
                            <strong id="mdl_quality_clearance">—</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#applyFilters').on('click', function() {
        applyFilters();
    });

    $('#resetFilters').on('click', function() {
        $('#warehouseStockFilterForm')[0].reset();
        applyFilters();
    });

    $('.filter-field').on('change', function() {
        applyFilters();
    });

    $(document).on('click', '.quick-view-btn', function() {
        const btn = $(this);
        $('#mdl_item_code').text(btn.data('item_code'));
        $('#mdl_product_name').text(btn.data('product_name'));
        $('#mdl_warehouse_name').text(btn.data('warehouse_name'));
        $('#mdl_row_name').text(btn.data('row_name'));
        $('#mdl_total_quantity').text(btn.data('total_quantity'));
        $('#mdl_balance_quantity').text(btn.data('balance_quantity'));
        $('#mdl_pallets_used').text(btn.data('pallets_used'));
        $('#mdl_sap_batch').text(btn.data('sap_batch'));
        $('#mdl_vendor_batch').text(btn.data('vendor_batch'));
        $('#mdl_mfg_date').text(btn.data('mfg_date'));
        $('#mdl_expiry_date').text(btn.data('expiry_date'));
        $('#mdl_quality_clearance').text(btn.data('quality_clearance'));
        $('#quickViewModal').modal('show');
    });

    function applyFilters() {
        const formData = {
            warehouse_id: $('#filter_warehouse').val(),
            product_id: $('#filter_product').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val()
        };

        $('#filterLoadingOverlay').show();

        $.ajax({
            url: '{{ route("reports.warehouse-stock") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');

                // Update table body
                const newTableBody = doc.querySelector('#stockTableBody');
                if (newTableBody) {
                    $('#stockTableBody').html(newTableBody.innerHTML);
                }

                // Update table footer
                const newTableFoot = doc.querySelector('tfoot');
                if (newTableFoot) {
                    $('tfoot').replaceWith(newTableFoot);
                }

                // Update summary cards
                const summaryWarehouses = doc.querySelector('#summaryWarehouses');
                const summaryProducts = doc.querySelector('#summaryProducts');
                const summaryPallets = doc.querySelector('#summaryPallets');
                const summaryBalance = doc.querySelector('#summaryBalance');
                const totalCount = doc.querySelector('#totalCount');

                if (summaryWarehouses) $('#summaryWarehouses').text(summaryWarehouses.textContent);
                if (summaryProducts) $('#summaryProducts').text(summaryProducts.textContent);
                if (summaryPallets) $('#summaryPallets').text(summaryPallets.textContent);
                if (summaryBalance) $('#summaryBalance').text(summaryBalance.textContent);
                if (totalCount) $('#totalCount').text(totalCount.textContent);

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

