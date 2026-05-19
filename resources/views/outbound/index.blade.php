@extends('layouts.app')

@section('content')

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Outbound / Dispatch</h5>
        <a href="{{ route('outbound.create') }}" class="btn btn-sm btn-primary">
            + New Outbound
        </a>
    </div>

    <div class="card-body p-3">
        <!-- Filters -->
        <form id="outboundFilterForm" class="mb-3">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Type</label>
                    <select name="source_type" id="filter_source_type" class="form-select form-select-sm filter-field">
                        <option value="">All Types</option>
                        <option value="sale">Sale</option>
                        <option value="transfer">Transfer</option>
                        <option value="return">Return</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">From Warehouse</label>
                    <select name="warehouse_id" id="filter_warehouse" class="form-select form-select-sm filter-field">
                        <option value="">All Warehouses</option>
                        @foreach(\App\Models\Warehouse::orderBy('name')->get() as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Customer</label>
                    <select name="customer_id" id="filter_customer" class="form-select form-select-sm filter-field">
                        <option value="">All Customers</option>
                        @foreach(\App\Models\Customer::orderBy('name')->get() as $cust)
                            <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Product</label>
                    <select name="product_id" id="filter_product" class="form-select form-select-sm filter-field">
                        <option value="">All Products</option>
                        @foreach(\App\Models\Product::orderBy('name')->get() as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name }}</option>
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
            </div>
            <div class="mt-2">
                <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
                    <i class="bi bi-search"></i> Apply Filters
                </button>
                <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <span class="ms-2 text-muted small">Total: <strong id="totalCount">{{ $items->count() }}</strong> records</span>
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
            <div class="alert alert-success rounded-0 mb-0">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-nowrap">
                        <th>WH</th>
                        <th>To / Customer</th>
                        <th>Product</th>
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
                            <div class="fw-bold text-nowrap">{{ $whName }}</div>
                            @if($free !== null)
                                <div class="small text-success fw-bold">Free: {{ $free }}</div>
                            @endif
                            <span class="badge bg-{{ $badge }} x-small" style="font-size: 0.6rem;">
                                {{ strtoupper($out->source_type) }}
                            </span>
                        </td>

                        <td class="fw-semibold">{{ $target }}</td>

                        <td>
                            <div class="text-truncate" style="max-width:220px"
                                 title="{{ $productText }}">
                                {{ $productText }}
                            </div>
                        </td>

                        <td class="text-end">{{ $item->units_dispatch ?? '-' }}</td>
                        <td class="text-end">{{ $item->pack_size_snapshot ?? '-' }}</td>
                        <td class="text-end fw-bold">{{ number_format($item->dispatch_quantity,2) }}</td>
                        <td class="text-end fw-bold text-primary">
                            {{ $item->pallets_returned > 0 ? $item->pallets_returned : '-' }}
                        </td>

                        <td>
                            {{ $out->vehicle_no ?? '-' }}
                            <div class="text-muted small">
                                {{ $out->driver_name ?? '' }}
                            </div>
                        </td>

                        <td>{{ $item->created_at->format('d M Y') }}</td>

                        {{-- ACTIONS --}}
                        <td class="text-center text-nowrap">

                            {{-- QUICK VIEW --}}
                            <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#detailModal"
                                data-item='@json($modalData)'>
                                View
                            </button>

                            {{-- INVOICE --}}
                            <a href="{{ route('outbound.invoice', $out->id) }}"
                               class="btn btn-sm btn-outline-success"
                               target="_blank">
                                Invoice
                            </a>

                            {{-- DC --}}
                            <a href="{{ route('outbound.dc', $out->id) }}"
                               class="btn btn-sm btn-outline-secondary"
                               target="_blank">
                                DC
                            </a>

                            {{-- FUTURE --}}
                            <a href="{{ route('outbound.edit', $out->id) }}"
                               class="btn btn-sm btn-outline-warning">
                                Edit
                            </a>

                            <form action="{{ route('outbound.destroy', $out->id) }}"
                                  method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Delete outbound?')">
                                    Del
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            No outbound records found
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- QUICK VIEW MODAL --}}
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Outbound Details</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
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
                <td>${v ?? '-'}</td>
            </tr>
        `);
    });
});

// Filter functionality
$(document).ready(function() {
    // Apply filters with AJAX
    $('#applyFilters').on('click', function() {
        applyFilters();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#outboundFilterForm')[0].reset();
        applyFilters();
    });

    // Auto-apply on filter change
    $('.filter-field').on('change', function() {
        applyFilters();
    });

    function applyFilters() {
        const formData = {
            source_type: $('#filter_source_type').val(),
            warehouse_id: $('#filter_warehouse').val(),
            customer_id: $('#filter_customer').val(),
            product_id: $('#filter_product').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val()
        };

        // Show loading
        $('#filterLoadingOverlay').show();

        $.ajax({
            url: '{{ route("outbound.index") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                // Parse the response HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');

                // Extract table body
                const newTableBody = doc.querySelector('#outboundTableBody');
                if (newTableBody) {
                    $('#outboundTableBody').html(newTableBody.innerHTML);
                }

                // Update count
                const totalCount = $(newTableBody).find('tr').length;
                $('#totalCount').text(totalCount);

                // Hide loading
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
