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
                <li class="breadcrumb-item active" aria-current="page">Products</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold">Products</h5>
        <small class="text-muted">Manage items, categories, packing and stock units</small>
    </div>

    <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
        {{-- Search (UI ready) --}}
        <!-- <div class="input-group input-group-sm" style="width:280px;">
            <span class="input-group-text bg-white border-end-0 rounded-pill ps-3">
                <i class="bi bi-search"></i>
            </span>
            <input type="text"
                   class="form-control border-start-0 rounded-pill pe-3"
                   placeholder="Search product...">
        </div> -->

        <a href="{{ route('product.create') }}"
           class="btn btn-sm btn-primary rounded-pill shadow-sm px-3">
            <i class="bi bi-plus-lg me-1"></i> Add Product
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
        <form id="productFilterForm" class="mb-0">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Search</label>
                    <input type="text" name="search" id="filter_search" class="form-control form-control-sm filter-field" placeholder="Item code or name...">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Category</label>
                    <select name="category_id" id="filter_category" class="form-select form-select-sm filter-field">
                        <option value="">All Categories</option>
                        @foreach(\App\Models\ProductCategory::orderBy('name')->get() as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Group</label>
                    <select name="group_id" id="filter_group" class="form-select form-select-sm filter-field">
                        <option value="">All Groups</option>
                        @foreach(\App\Models\ProductGroup::orderBy('name')->get() as $grp)
                            <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">UOM</label>
                    <select name="uom_id" id="filter_uom" class="form-select form-select-sm filter-field">
                        <option value="">All UOMs</option>
                        @foreach(\App\Models\Uom::orderBy('name')->get() as $uom)
                            <option value="{{ $uom->id }}">{{ $uom->name }}</option>
                        @endforeach
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
                <div class="col-md-1">
                    <label class="form-label fw-semibold small">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button type="button" id="applyFilters" class="btn btn-sm btn-primary w-100" title="Apply Filters">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mt-2">
                <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <span class="ms-2 text-muted small">Total: <strong id="totalCount">{{ $products->count() }}</strong> products</span>
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
                        <th>Item Code</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Group</th>
                        <th>UOM</th>
                        <th>Packing</th>
                        <th>Pack Size</th>
                        <th>Status</th>
                        <th style="width:190px;" class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody id="productTableBody">
                    @forelse($products as $product)
                        <tr>
                            <td class="text-muted fw-semibold">{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</td>

                            <td class="fw-semibold">{{ $product->item_code }}</td>

                            <td>
                                <div class="fw-semibold text-dark">{{ $product->name }}</div>
                                <small class="text-muted">ID: {{ $product->id }}</small>
                            </td>

                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td>{{ $product->group->name ?? '-' }}</td>
                            <td>{{ $product->uom->name ?? '-' }}</td>
                            <td>{{ $product->packingType->name ?? '-' }}</td>

                            <td class="fw-semibold">{{ $product->pack_size }}</td>

                            <td>
                                @if($product->status)
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
                                    <a href="{{ route('product.edit', $product->id) }}"
                                       class="btn btn-sm btn-outline-primary rounded-pill">
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </a>

                                    <form action="{{ route('product.destroy', $product->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this product?')">
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
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No products found
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="card-footer bg-light border-top py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Showing <strong>{{ $products->firstItem() }}</strong> to <strong>{{ $products->lastItem() }}</strong> of <strong>{{ $products->total() }}</strong> entries
                </div>
                <div>
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
        @endif

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
        $('#productFilterForm')[0].reset();
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
            category_id: $('#filter_category').val(),
            group_id: $('#filter_group').val(),
            uom_id: $('#filter_uom').val(),
            status: $('#filter_status').val()
        };

        // Show loading
        $('#filterLoadingOverlay').show();

        $.ajax({
            url: '{{ route("product.index") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                // Parse the response HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');

                // Extract table body
                const newTableBody = doc.querySelector('#productTableBody');
                if (newTableBody) {
                    $('#productTableBody').html(newTableBody.innerHTML);
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

