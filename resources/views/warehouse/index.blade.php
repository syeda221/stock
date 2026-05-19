@extends('layouts.app')

@section('content')

{{-- Breadcrumbs + Page Header --}}
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Warehouses</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold">Warehouses</h5>
        <small class="text-muted">Manage warehouse locations and capacity</small>
    </div>

    <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
        {{-- Search (UI ready) --}}
        <!-- <div class="input-group input-group-sm" style="width:260px;">
            <span class="input-group-text bg-white border-end-0 rounded-pill ps-3">
                <i class="bi bi-search"></i>
            </span>
            <input type="text"
                   class="form-control border-start-0 rounded-pill pe-3"
                   placeholder="Search warehouse...">
        </div> -->

        <a href="{{ route('warehouse.create') }}"
           class="btn btn-sm btn-primary rounded-pill shadow-sm px-3">
            <i class="bi bi-plus-lg me-1"></i> Add Warehouse
        </a>
    </div>
</div>

{{-- Success Alert --}}
@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    </div>
@endif

{{-- Filter Section --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-3">
        <form id="warehouseFilterForm" class="mb-0">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Search</label>
                    <input type="text" name="search" id="filter_search" class="form-control form-control-sm filter-field" placeholder="Name or city...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Capacity Mode</label>
                    <select name="capacity_mode" id="filter_capacity_mode" class="form-select form-select-sm filter-field">
                        <option value="">All Modes</option>
                        <option value="row_wise">Row Wise</option>
                        <option value="warehouse_level">Warehouse Level</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Status</label>
                    <select name="status" id="filter_status" class="form-select form-select-sm filter-field">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
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
                        <span class="ms-2 text-muted small align-self-center">Total: <strong id="totalCount">{{ $warehouses->count() }}</strong></span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Card + Table --}}
<div class="card border-0 shadow-lg rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div id="filterLoadingOverlay" style="display: none; position: relative;">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Applying filters...</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Name</th>
                        <th>City</th>
                        <th>Capacity Mode</th>
                        <th>Rows</th>
                        <th>Total Capacity (Pallets)</th>
                        <th>Status</th>
                        <th style="width:180px;" class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody id="warehouseTableBody">
                    @forelse($warehouses as $warehouse)
                        <tr>
                            <td class="text-muted fw-semibold">{{ $loop->iteration }}</td>

                            <td>
                                <div class="fw-semibold text-dark">{{ $warehouse->name }}</div>
                                <small class="text-muted">ID: {{ $warehouse->id }}</small>
                            </td>

                            <td>{{ $warehouse->city }}</td>

                            <td class="text-capitalize fw-semibold">
                                {{ str_replace('_', ' ', $warehouse->capacity_mode) }}
                            </td>

                            <td>
                                @if($warehouse->capacity_mode === 'row')
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" data-bs-toggle="modal" data-bs-target="#rowsModal{{ $warehouse->id }}">
                                        <span class="badge bg-info text-dark rounded-pill px-3 py-2">
                                            <i class="bi bi-list-task me-1"></i> {{ $warehouse->rows->count() }} Rows
                                        </span>
                                    </button>

                                    <!-- Modal -->
                                    <div class="modal fade" id="rowsModal{{ $warehouse->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold">Rows in {{ $warehouse->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    @if($warehouse->rows->count() > 0)
                                                        <div class="table-responsive border rounded-3">
                                                            <table class="table table-sm table-hover align-middle mb-0">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th class="ps-3">Row Name</th>
                                                                        <th class="text-end pe-3">Pallet Capacity</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($warehouse->rows as $row)
                                                                        <tr>
                                                                            <td class="ps-3 fw-semibold text-dark">{{ $row->row_name }}</td>
                                                                            <td class="text-end pe-3">{{ number_format($row->pallet_capacity) }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                                <tfoot class="table-light fw-bold">
                                                                    <tr>
                                                                        <td class="ps-3">Total</td>
                                                                        <td class="text-end pe-3">{{ number_format($warehouse->rows->sum('pallet_capacity')) }}</td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    @else
                                                        <div class="text-center text-muted py-3">
                                                            <i class="bi bi-inboxes fs-4 d-block mb-2"></i>
                                                            No rows found
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td class="fw-semibold">
                                {{ number_format($warehouse->total_capacity) }}
                            </td>

                            <td>
                                @if($warehouse->status)
                                    <span class="badge rounded-pill bg-success px-3 py-2">
                                        <i class="bi bi-check2-circle me-1"></i> Active
                                    </span>
                                @else
                                    <span class="badge rounded-pill bg-secondary px-3 py-2">
                                        <i class="bi bi-slash-circle me-1"></i> Inactive
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="{{ route('warehouse.edit', $warehouse->id) }}"
                                       class="btn btn-sm btn-outline-primary rounded-pill">
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </a>

                                    <form action="{{ route('warehouse.destroy', $warehouse->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this warehouse?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger rounded-pill ms-2">
                                            <i class="bi bi-trash3 me-1"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-building fs-3 d-block mb-2"></i>
                                No warehouses found
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Apply filters with AJAX
    $('#applyFilters').on('click', function() {
        applyFilters();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#warehouseFilterForm')[0].reset();
        applyFilters();
    });

    // Auto-apply on filter change
    $('.filter-field').on('change keyup', function() {
        clearTimeout(window.filterTimeout);
        window.filterTimeout = setTimeout(applyFilters, 500);
    });

    function applyFilters() {
        const formData = {
            search: $('#filter_search').val(),
            capacity_mode: $('#filter_capacity_mode').val(),
            status: $('#filter_status').val()
        };

        // Show loading
        $('#filterLoadingOverlay').show();

        $.ajax({
            url: '{{ route("warehouse.index") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                // Parse the response HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');

                // Extract table body
                const newTableBody = doc.querySelector('#warehouseTableBody');
                if (newTableBody) {
                    $('#warehouseTableBody').html(newTableBody.innerHTML);
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

