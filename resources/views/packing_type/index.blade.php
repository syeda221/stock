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
                <li class="breadcrumb-item active" aria-current="page">Packing Types</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold">Packing Types</h5>
        <small class="text-muted">Manage product packing configurations</small>
    </div>

    <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
        {{-- Search (UI ready) --}}
        <!-- <div class="input-group input-group-sm" style="width:260px;">
            <span class="input-group-text bg-white border-end-0 rounded-pill ps-3">
                <i class="bi bi-search"></i>
            </span>
            <input type="text"
                   class="form-control border-start-0 rounded-pill pe-3"
                   placeholder="Search packing type...">
        </div> -->

        <a href="{{ route('packing-type.create') }}"
           class="btn btn-sm btn-primary rounded-pill shadow-sm px-3">
            <i class="bi bi-plus-lg me-1"></i> Add Packing Type
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
        <form id="packingTypeFilterForm" class="mb-0">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Search</label>
                    <input type="text" name="search" id="filter_search" class="form-control form-control-sm filter-field" placeholder="Search packing type name...">
                </div>
                <div class="col-md-3">
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
                        <span class="ms-2 text-muted small align-self-center">Total: <strong id="totalCount">{{ $packingTypes->count() }}</strong></span>
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
                        <th style="width:70px;">#</th>
                        <th>Name</th>
                        <th style="width:160px;">Status</th>
                        <th style="width:180px;" class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody id="packingTypeTableBody">
                    @forelse($packingTypes as $type)
                        <tr>
                            <td class="text-muted fw-semibold">{{ $loop->iteration }}</td>

                            <td>
                                <div class="fw-semibold text-dark">{{ $type->name }}</div>
                                <small class="text-muted">ID: {{ $type->id }}</small>
                            </td>

                            <td>
                                @if($type->status)
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
                                    <a href="{{ route('packing-type.edit', $type->id) }}"
                                       class="btn btn-sm btn-outline-primary rounded-pill">
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </a>

                                    <form action="{{ route('packing-type.destroy', $type->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this packing type?')">
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
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No packing types found
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
    $('#applyFilters').on('click', function() {
        applyFilters();
    });

    $('#resetFilters').on('click', function() {
        $('#packingTypeFilterForm')[0].reset();
        applyFilters();
    });

    $('.filter-field').on('change keyup', function() {
        clearTimeout(window.filterTimeout);
        window.filterTimeout = setTimeout(applyFilters, 500);
    });

    function applyFilters() {
        const formData = {
            search: $('#filter_search').val(),
            status: $('#filter_status').val()
        };

        $('#filterLoadingOverlay').show();

        $.ajax({
            url: '{{ route("packing-type.index") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');
                const newTableBody = doc.querySelector('#packingTypeTableBody');
                if (newTableBody) {
                    $('#packingTypeTableBody').html(newTableBody.innerHTML);
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

