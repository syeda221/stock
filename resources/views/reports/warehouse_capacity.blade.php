@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Warehouse Capacity & Pallet Management</h5>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>

    @if(empty($reportData))
        <div class="alert alert-info">No warehouses configured or active.</div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
            @foreach($reportData as $data)
            <div class="col">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h6 class="card-title fw-bold text-primary mb-1">
                            <i class="bi bi-building me-2"></i>{{ $data['warehouse']->name }}
                        </h6>
                        <small class="text-muted">{{ $data['warehouse']->code ?? 'N/A' }} | {{ $data['warehouse']->type ?? 'Standard' }}</small>
                    </div>
                    
                    <div class="card-body">
                        {{-- Overall Progress --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-semibold">Overall Capacity</span>
                                <span class="small fw-bold {{ $data['percent'] >= 90 ? 'text-danger' : ($data['percent'] >= 75 ? 'text-warning' : 'text-success') }}">
                                    {{ $data['percent'] }}% Used
                                </span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar {{ $data['percent'] >= 90 ? 'bg-danger' : ($data['percent'] >= 75 ? 'bg-warning' : 'bg-success') }}" 
                                     role="progressbar" 
                                     style="width: {{ min(100, $data['percent']) }}%" 
                                     aria-valuenow="{{ $data['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 text-muted small">
                                <div><strong>{{ $data['total_used'] }}</strong> Used</div>
                                <div><strong>{{ $data['total_free'] }}</strong> Free</div>
                                <div><strong>{{ $data['total_capacity'] }}</strong> Total</div>
                            </div>
                        </div>

                        {{-- Unassigned Warning --}}
                        @if($data['unassigned'] > 0)
                            <div class="alert alert-warning py-2 small mb-4">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong>{{ $data['unassigned'] }} pallets</strong> are not assigned to any specific row.
                            </div>
                        @endif

                        {{-- Rows Breakdown --}}
                        @if(count($data['rows']) > 0)
                            <h6 class="small fw-bold text-secondary mb-3 border-bottom pb-2">Row Breakdown</h6>
                            <div class="row-breakdown-list" style="max-height: 250px; overflow-y: auto;">
                                @foreach($data['rows'] as $row)
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small fw-semibold">{{ $row['row_name'] }}</span>
                                            <span class="badge {{ $row['percent'] >= 90 ? 'bg-danger' : ($row['percent'] >= 75 ? 'bg-warning' : 'bg-success') }}">
                                                {{ $row['used'] }} / {{ $row['capacity'] }}
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar {{ $row['percent'] >= 90 ? 'bg-danger' : ($row['percent'] >= 75 ? 'bg-warning' : 'bg-success') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min(100, $row['percent']) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted small py-3 bg-light rounded">
                                No rows configured for this warehouse.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    @media print {
        .navbar, .sidebar, footer, .btn { display: none !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; break-inside: avoid; }
        body { background-color: white !important; }
        .row-breakdown-list { max-height: none !important; overflow: visible !important; }
    }
</style>
@endsection
