@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0">Outbound / Dispatch</h5>
        <small class="text-muted">Manage outward stock movements</small>
    </div>
    <a href="{{ route('outbound.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Outbound
    </a>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-truck text-primary fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Total Records</small>
                    <strong class="fs-6">{{ $items->total() }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-success bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-cart text-success fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Sales</small>
                    <strong class="fs-6">{{ $items->where('stockOut.source_type', 'sale')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-info bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-info bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-arrow-left-right text-info fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Transfers</small>
                    <strong class="fs-6">{{ $items->where('stockOut.source_type', 'transfer')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-25 rounded-3 p-2">
                    <i class="bi bi-arrow-return-left text-warning fs-5"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Returns</small>
                    <strong class="fs-6">{{ $items->where('stockOut.source_type', 'return')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2"></i>Outbound Records</h6>
    </div>

    <div class="card-body p-3">
        <form id="outboundFilterForm" class="mb-0">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-funnel me-1"></i>Type</label>
                    <select name="source_type" id="filter_source_type" class="form-select form-select-sm filter-field">
                        <option value="">All Types</option>
                        <option value="sale">Sale</option>
                        <option value="transfer">Transfer</option>
                        <option value="return">Return</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-buildings me-1"></i>Warehouse</label>
                    <select name="warehouse_id" id="filter_warehouse" class="form-select form-select-sm filter-field">
                        <option value="">All Warehouses</option>
                        @foreach(\App\Models\Warehouse::orderBy('name')->get() as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-people me-1"></i>Customer</label>
                    <select name="customer_id" id="filter_customer" class="form-select form-select-sm filter-field">
                        <option value="">All Customers</option>
                        @foreach(\App\Models\Customer::orderBy('name')->get() as $cust)
                            <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-tag me-1"></i>Product Group</label>
                    <select name="product_group_id" id="filter_product_group" class="form-select form-select-sm filter-field">
                        <option value="">All Groups</option>
                        @foreach($productGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-box me-1"></i>Product</label>
                    <select name="product_id" id="filter_product" class="form-select form-select-sm filter-field">
                        <option value="">All Products</option>
                        @foreach(\App\Models\Product::orderBy('name')->get() as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-calendar me-1"></i>Date From</label>
                    <input type="date" name="date_from" id="filter_date_from" class="form-control form-control-sm filter-field">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small"><i class="bi bi-calendar me-1"></i>Date To</label>
                    <input type="date" name="date_to" id="filter_date_to" class="form-control form-control-sm filter-field">
                </div>
            </div>
            <div class="mt-2 d-flex align-items-center gap-2">
                <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
                    <i class="bi bi-search"></i> Apply Filters
                </button>
                <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <span class="ms-auto text-muted small">Total: <strong id="totalCount">{{ $items->count() }}</strong> records</span>
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
            <div class="alert alert-success rounded-0 mb-0 border-0">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-responsive rounded-bottom">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-primary small">
                    <tr class="text-nowrap">
                        <th>WH</th>
                        <th>To / Customer</th>
                        <th>Product</th>
                        <th>Group</th>
                        <th class="text-end">Units</th>
                        <th class="text-end">Pack</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Pallets</th>
                        <th>Vehicle</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody id="outboundTableBody">
                @forelse($items as $item)
                    @php
                        $out = $item->stockOut;

                        $badge = match($out->source_type){
                            'sale'     => 'success',
                            'transfer' => 'info',
                            'return'   => 'warning',
                            default    => 'secondary'
                        };

                        $target = $out->source_type === 'sale'
                            ? ($out->customer->name ?? '-')
                            : ($out->toWarehouse->name ?? '-');

                        $productText = ($item->product->item_code ?? '') . ' - ' . ($item->product->name ?? '-');

                        $modalData = [
                            'Outbound Type'     => ucfirst($out->source_type),
                            'From Warehouse'    => $out->warehouse->name ?? '-',
                            'To / Customer'     => $target,
                            'Product'           => $productText,
                            'Product Group'     => $item->product->group->name ?? '-',
                            'Dispatch Quantity' => $item->dispatch_quantity,
                            'Pallets Returned'  => $item->pallets_returned ?? 0,
                            'Vehicle No'        => $out->vehicle_no ?? '-',
                            'Vehicle Size'      => $out->vehicle_size ?? '-',
                            'Driver Name'       => $out->driver_name ?? '-',
                            'Driver Mobile'     => $out->driver_mobile ?? '-',
                            'Transporter'       => $out->transporter->name ?? '-',
                            'Remarks'           => $item->remarks ?? $out->remarks ?? '-',
                        ];
                    @endphp

                    <tr>
                        <td>
                            @php
                                $whName = $item->warehouse->name ?? $out->warehouse->name ?? '-';
                                $whId = $item->warehouse_id ?? $out->warehouse_id;
                                $free = $warehouseCapacities[$whId] ?? null;
                            @endphp
                            <div class="fw-bold text-nowrap small">{{ $whName }}</div>
                            @if($free !== null)
                                <div class="small text-success fw-semibold" style="font-size: 10px;">Free: {{ $free }}</div>
                            @endif
                            <span class="badge bg-{{ $badge }}" style="font-size: 0.55rem;">
                                {{ strtoupper($out->source_type) }}
                            </span>
                        </td>

                        <td class="fw-semibold small text-nowrap">{{ $target }}</td>

                        <td>
                            <div class="text-truncate small" style="max-width:180px"
                                 title="{{ $productText }}">
                                {{ $productText }}
                            </div>
                        </td>

                        <td>
                            @if($item->product->group)
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1" style="font-size: 9px;">
                                    {{ $item->product->group->name }}
                                </span>
                            @else
                                <span class="text-muted" style="font-size: 9px;">—</span>
                            @endif
                        </td>

                        <td class="text-end small">{{ $item->units_dispatch ?? '-' }}</td>
                        <td class="text-end small">{{ $item->pack_size_snapshot ?? '-' }}</td>
                        <td class="text-end fw-bold small">{{ number_format($item->dispatch_quantity,2) }}</td>
                        <td class="text-end fw-bold text-primary small">
                            {{ $item->pallets_returned > 0 ? $item->pallets_returned : '-' }}
                        </td>

                        <td class="small text-nowrap">
                            {{ $out->vehicle_no ?? '-' }}
                            <div class="text-muted" style="font-size: 10px;">
                                {{ $out->driver_name ?? '' }}
                            </div>
                        </td>

                        <td class="small text-nowrap">{{ $item->created_at->format('d.m.Y') }}</td>

                        {{-- ACTIONS --}}
                        <td class="text-center text-nowrap">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailModal"
                                    data-item='@json($modalData)'
                                    title="View">
                                    <i class="bi bi-eye"></i>
                                </button>

                                <a href="{{ route('outbound.invoice', $out->id) }}"
                                   class="btn btn-sm btn-outline-success"
                                   target="_blank" title="Invoice">
                                    <i class="bi bi-file-text"></i>
                                </a>

                                <a href="{{ route('outbound.dc', $out->id) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   target="_blank" title="DC">
                                    <i class="bi bi-file-earmark"></i>
                                </a>

                                <a href="{{ route('outbound.edit', $out->id) }}"
                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form action="{{ route('outbound.destroy', $out->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete outbound?')"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                            <p class="mb-0">No outbound records found</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-top-0 py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} records</small>
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>

{{-- QUICK VIEW MODAL --}}
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-eye me-2"></i>Outbound Details</h6>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <tbody id="detailModalBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-bs-target="#detailModal"]');
    if(!btn) return;

    const data = JSON.parse(btn.getAttribute('data-item') || '{}');
    const body = document.getElementById('detailModalBody');
    body.innerHTML = '';

    Object.entries(data).forEach(([k,v]) => {
        body.insertAdjacentHTML('beforeend', `
            <tr>
                <th class="bg-light" style="width:40%">${k}</th>
                <td class="fw-semibold">${v ?? '-'}</td>
            </tr>
        `);
    });
});

$(document).ready(function() {
    $('#applyFilters').on('click', function() {
        applyFilters();
    });

    $('#resetFilters').on('click', function() {
        $('#outboundFilterForm')[0].reset();
        applyFilters();
    });

    $('.filter-field').on('change', function() {
        applyFilters();
    });

    function applyFilters() {
        const formData = {
            source_type: $('#filter_source_type').val(),
            warehouse_id: $('#filter_warehouse').val(),
            customer_id: $('#filter_customer').val(),
            product_group_id: $('#filter_product_group').val(),
            product_id: $('#filter_product').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val()
        };

        $('#filterLoadingOverlay').show();

        $.ajax({
            url: '{{ route("outbound.index") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');

                const newTableBody = doc.querySelector('#outboundTableBody');
                if (newTableBody) {
                    $('#outboundTableBody').html(newTableBody.innerHTML);
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
