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
        <small class="text-muted">View stock levels by warehouse and product</small>
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
                            <i class="bi bi-arrow-down-circle text-info fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Inbound</h6>
                        <h4 class="mb-0 fw-bold text-info" id="summaryInbound">{{ number_format($summary['total_inbound'], 2) }}</h4>
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
                            <i class="bi bi-arrow-up-circle text-warning fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Outbound</h6>
                        <h4 class="mb-0 fw-bold text-warning" id="summaryOutbound">{{ number_format($summary['total_outbound'], 2) }}</h4>
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
                        <th class="text-end">Total Inbound</th>
                        <th class="text-end">Total Outbound</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>

                <tbody id="stockTableBody">
                    @forelse($stockReport as $index => $item)
                        <tr>
                            <td class="text-muted fw-semibold">{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $item['item_code'] }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $item['product_name'] }}</div>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                    <i class="bi bi-buildings me-1"></i>{{ $item['warehouse_name'] }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="text-info fw-semibold">
                                    <i class="bi bi-arrow-down-circle me-1"></i>{{ number_format($item['total_inbound'], 2) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="text-warning fw-semibold">
                                    <i class="bi bi-arrow-up-circle me-1"></i>{{ number_format($item['total_outbound'], 2) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold {{ $item['total_balance'] > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($item['total_balance'], 2) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($item['total_balance'] > 0)
                                    <span class="badge bg-success">In Stock</span>
                                @else
                                    <span class="badge bg-danger">Out of Stock</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
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
                        <td colspan="4" class="text-end">Totals:</td>
                        <td class="text-end text-info">{{ number_format($summary['total_inbound'], 2) }}</td>
                        <td class="text-end text-warning">{{ number_format($summary['total_outbound'], 2) }}</td>
                        <td class="text-end text-success">{{ number_format($summary['total_balance'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
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
                const summaryInbound = doc.querySelector('#summaryInbound');
                const summaryOutbound = doc.querySelector('#summaryOutbound');
                const summaryBalance = doc.querySelector('#summaryBalance');
                const totalCount = doc.querySelector('#totalCount');

                if (summaryWarehouses) $('#summaryWarehouses').text(summaryWarehouses.textContent);
                if (summaryInbound) $('#summaryInbound').text(summaryInbound.textContent);
                if (summaryOutbound) $('#summaryOutbound').text(summaryOutbound.textContent);
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

