@extends('layouts.app')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('warehouse.index') }}" class="text-decoration-none">Warehouses</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Warehouse Details</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold">Warehouse Details</h5>
        <small class="text-muted">Drill down: Warehouse → Rows → Pallets</small>
    </div>
</div>

{{-- Level 1: Warehouses --}}
<div id="level1">
    <div class="row g-3" id="warehouseGrid">
        @forelse($warehouses as $warehouse)
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 warehouse-card @if($warehouse->is_full) border-danger @endif" data-id="{{ $warehouse->id }}" style="cursor:pointer;">
                    <div class="card-body p-3 text-center">
                        <div class="rounded-circle @if($warehouse->is_full) bg-danger @else bg-primary @endif bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width:56px;height:56px;">
                            <i class="bi bi-building fs-3 @if($warehouse->is_full) text-danger @else text-primary @endif"></i>
                        </div>
                        <h6 class="fw-bold mb-1">{{ $warehouse->name }}</h6>
                        <small class="text-muted d-block">{{ $warehouse->city ?? 'N/A' }}</small>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Rows:</span>
                            <span class="fw-semibold">{{ $warehouse->rows->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Capacity:</span>
                            <span class="fw-semibold">{{ number_format($warehouse->total_capacity) }} pallets</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Used:</span>
                            <span class="fw-semibold @if($warehouse->is_full) text-danger @endif">{{ number_format($warehouse->used_pallets) }}</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Free:</span>
                            <span class="fw-semibold @if($warehouse->is_full) text-danger @else text-success @endif">{{ $warehouse->free_pallets !== null ? number_format($warehouse->free_pallets) : '∞' }}</span>
                        </div>
                        @if($warehouse->is_full)
                            <span class="badge bg-danger mt-2 w-100">Full</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-building fs-1 d-block mb-2"></i>
                    No warehouses found
                </div>
            </div>
        @endforelse
    </div>
</div>

{{-- Level 2: Rows (hidden initially) --}}
<div id="level2" style="display:none;">
    <div class="d-flex align-items-center mb-3">
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-2" id="backToWarehouses">
            <i class="bi bi-arrow-left"></i> Back
        </button>
        <h5 class="mb-0 fw-bold" id="selectedWarehouseName"></h5>
    </div>
    <div class="row g-3" id="rowsContainer"></div>
</div>

{{-- Level 3: Pallets (hidden initially) --}}
<div id="level3" style="display:none;">
    <div class="d-flex align-items-center mb-3">
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-2" id="backToRows">
            <i class="bi bi-arrow-left"></i> Back
        </button>
        <div>
            <h5 class="mb-0 fw-bold" id="selectedRowName"></h5>
            <small class="text-muted" id="rowCapacityInfo"></small>
        </div>
    </div>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th style="width:80px;">#</th>
                            <th>Pallet</th>
                            <th>Product</th>
                            <th>Item Code</th>
                            <th class="text-end">Cartons</th>
                            <th class="text-end">Max Cartons/Pallet</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="palletsTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // Click warehouse → show rows
    $('.warehouse-card').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).find('h6').text();
        loadRows(id, name);
    });

    function loadRows(warehouseId, warehouseName) {
        $('#selectedWarehouseName').text(warehouseName + ' — Rows');
        $('#rowsContainer').html('<div class="col-12 text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
        $('#level1').hide();
        $('#level3').hide();
        $('#level2').show();

        $.get('/warehouses/' + warehouseId + '/rows', function(rows) {
            if (rows.length === 0) {
                $('#rowsContainer').html('<div class="col-12 text-center text-muted py-4"><i class="bi bi-inboxes fs-2 d-block mb-2"></i>No rows found</div>');
                return;
            }

            let html = '';
            rows.forEach(function(row) {
                const isOver = row.used_pallets > row.pallet_capacity;
                const isFull = row.is_full || isOver;
                const cardClass = isFull ? 'border-danger' : '';
                const iconClass = isFull ? 'bg-danger text-danger' : 'bg-success text-success';
                const freeClass = isFull ? 'text-danger' : 'text-success';
                const freeLabel = row.free_pallets !== null ? numberFormat(row.free_pallets) : '∞';
                const usedClass = isOver ? 'text-danger fw-bold' : (isFull ? 'text-danger' : '');
                const badge = isOver
                    ? '<span class="badge bg-danger mt-2 w-100">Over Capacity</span>'
                    : (row.is_full ? '<span class="badge bg-danger mt-2 w-100">Full</span>' : '');
                html += `
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100 row-card ${cardClass}" data-id="${row.id}" style="cursor:pointer;">
                            <div class="card-body p-3 text-center">
                                <div class="rounded-circle ${iconClass} bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width:56px;height:56px;">
                                    <i class="bi bi-layers fs-3 ${iconClass}"></i>
                                </div>
                                <h6 class="fw-bold mb-1">${row.row_name}</h6>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Capacity:</span>
                                    <span class="fw-semibold">${row.pallet_capacity}</span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Used:</span>
                                    <span class="fw-semibold ${usedClass}">${numberFormat(row.used_pallets)}</span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Free:</span>
                                    <span class="fw-semibold ${freeClass}">${freeLabel}</span>
                                </div>
                                ${badge}
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#rowsContainer').html(html);

            // Click row → show pallets
            $('.row-card').on('click', function() {
                loadPallets($(this).data('id'), $(this).find('h6').text());
            });
        });
    }

    function loadPallets(rowId, rowName) {
        $('#selectedRowName').text(rowName);
        $('#palletsTableBody').html('<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
        $('#level2').hide();
        $('#level3').show();

        $.get('/warehouses/rows/' + rowId + '/pallets', function(data) {
            $('#rowCapacityInfo').text('Used: ' + data.used + ' / Empty: ' + data.empty + ' / Total: ' + data.total_capacity + ' pallets');

            let html = '';
            data.pallets.forEach(function(pallet) {
                const statusBadge = pallet.is_empty
                    ? '<span class="badge rounded-pill bg-secondary">Empty</span>'
                    : '<span class="badge rounded-pill bg-success">Occupied</span>';

                const productName = pallet.is_empty ? '<em class="text-muted">— Empty —</em>' : pallet.product_name;
                const itemCode = pallet.is_empty ? '—' : (pallet.item_code || '—');
                const cartons = pallet.is_empty ? '—' : numberFormat(pallet.carton_qty);
                const maxCartons = (pallet.is_empty || !pallet.carton_capacity) ? '—' : pallet.carton_capacity;
                const overCapacity = pallet.is_over_capacity;
                const cartonClass = overCapacity ? 'text-danger fw-bold' : '';
                const overBadge = overCapacity
                    ? ' <span class="badge bg-danger" title="Exceeds max cartons per pallet">Over</span>'
                    : '';

                html += `
                    <tr class="${pallet.is_empty ? 'table-light' : (overCapacity ? 'table-danger' : '')}">
                        <td class="text-muted">${pallet.pallet_number}</td>
                        <td class="fw-semibold">Pallet ${pallet.pallet_number}</td>
                        <td>${productName}</td>
                        <td>${itemCode}</td>
                        <td class="text-end ${cartonClass}">${cartons}${overBadge}</td>
                        <td class="text-end">${maxCartons}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            });
            $('#palletsTableBody').html(html);
        });
    }

    function numberFormat(num) {
        return num ? num.toLocaleString() : '0';
    }

    $('#backToWarehouses').on('click', function() {
        $('#level2').hide();
        $('#level3').hide();
        $('#level1').show();
    });

    $('#backToRows').on('click', function() {
        $('#level3').hide();
        $('#level2').show();
    });

});
</script>
@endpush
