@extends('layouts.app')

@section('content')

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Arrived From</h6>
        <a href="{{ route('arrived-from.create') }}" class="btn btn-sm btn-primary">
            + Add Arrived From
        </a>
    </div>

    <div class="card-body p-3">
        <!-- Filters -->
        <form id="arrivedFromFilterForm" class="mb-0">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Search</label>
                    <input type="text" name="search" id="filter_search" class="form-control form-control-sm filter-field" placeholder="Search name...">
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
                        <span class="ms-2 text-muted small align-self-center">Total: <strong id="totalCount">{{ $arrivedFroms->count() }}</strong></span>
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
            <div class="alert alert-success m-3">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th width="5%">#</th>
                    <th>Name</th>
                    <th width="15%">Status</th>
                    <th width="20%">Actions</th>
                </tr>
            </thead>
            <tbody id="arrivedFromTableBody">
                @forelse($arrivedFroms as $arrivedFrom)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $arrivedFrom->name }}</td>
                        <td>
                            @if($arrivedFrom->status)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('arrived-from.edit', $arrivedFrom->id) }}"
                               class="btn btn-sm btn-warning">
                                Edit
                            </a>

                            <form action="{{ route('arrived-from.destroy', $arrivedFrom->id) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete this arrived from file?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            No arrived froms found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

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
        $('#arrivedFromFilterForm')[0].reset();
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
            url: '{{ route("arrived-from.index") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');
                const newTableBody = doc.querySelector('#arrivedFromTableBody');
                if (newTableBody) {
                    $('#arrivedFromTableBody').html(newTableBody.innerHTML);
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

