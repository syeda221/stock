@extends('layouts.app')

@section('content')
<div class="row g-3">
    {{-- Expired Section --}}
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex align-items-center gap-2" style="background:#fee2e2;border-bottom:2px solid #fca5a5;">
                <i class="bi bi-x-circle-fill text-danger"></i>
                <h6 class="mb-0 fw-bold text-danger">Expired Stock ({{ $countExpired }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Warehouse</th>
                                <th>Batch</th>
                                <th>Expiry Date</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expired as $item)
                            <tr style="background:#fef2f2;">
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $item->product->name ?? '-' }} <small class="text-muted">({{ $item->product->item_code ?? '' }})</small></td>
                                <td>{{ $item->warehouse->name ?? $item->stockIn->warehouse->name ?? '-' }}</td>
                                <td>{{ $item->vendor_batch ?? $item->sap_batch ?? '-' }}</td>
                                <td><span class="badge bg-danger">{{ $item->expiry_date?->format('d/m/Y') }}</span></td>
                                <td class="text-end fw-bold">{{ number_format($item->balance_quantity, 2) }}</td>
                                <td><span class="badge bg-danger">Expired</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-check-circle text-success me-1"></i> No expired stock
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Near Expiry Section --}}
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex align-items-center gap-2" style="background:#fef3c7;border-bottom:2px solid #fcd34d;">
                <i class="bi bi-clock-fill text-warning"></i>
                <h6 class="mb-0 fw-bold" style="color:#92400e;">Near Expiry ({{ $countExpiring }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Warehouse</th>
                                <th>Batch</th>
                                <th>Expiry Date</th>
                                <th class="text-end">Balance</th>
                                <th>Days Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expiring as $item)
                            @php
                                $daysLeft = $today->diffInDays($item->expiry_date, false);
                            @endphp
                            <tr style="background:#fffbeb;">
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $item->product->name ?? '-' }} <small class="text-muted">({{ $item->product->item_code ?? '' }})</small></td>
                                <td>{{ $item->warehouse->name ?? $item->stockIn->warehouse->name ?? '-' }}</td>
                                <td>{{ $item->vendor_batch ?? $item->sap_batch ?? '-' }}</td>
                                <td>
                                    @if($daysLeft <= 7)
                                        <span class="badge bg-danger">{{ $item->expiry_date?->format('d/m/Y') }}</span>
                                    @elseif($daysLeft <= 15)
                                        <span class="badge bg-warning text-dark">{{ $item->expiry_date?->format('d/m/Y') }}</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-dark border">{{ $item->expiry_date?->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">{{ number_format($item->balance_quantity, 2) }}</td>
                                <td>
                                    @if($daysLeft <= 7)
                                        <span class="badge bg-danger">{{ $daysLeft }} days</span>
                                    @elseif($daysLeft <= 15)
                                        <span class="badge bg-warning text-dark">{{ $daysLeft }} days</span>
                                    @else
                                        <span class="badge bg-info">{{ $daysLeft }} days</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-check-circle text-success me-1"></i> No near-expiry stock
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
