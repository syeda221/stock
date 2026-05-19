@extends('layouts.app')

@section('content')
<div class="card shadow-sm">
    <div class="card-header">
        <strong>Outbound – Quick View</strong>
    </div>

    <div class="card-body">

        <div class="row mb-3">
            <div class="col-md-6">
                <b>Invoice:</b> {{ $stockOut->dispatched_invoice_no }} <br>
                <b>Date:</b> {{ $stockOut->created_at->format('d-m-Y') }} <br>
                <b>Type:</b> {{ ucfirst($stockOut->source_type) }}
            </div>
            <div class="col-md-6 text-end">
                <b>From:</b> {{ $stockOut->warehouse->name }} <br>
                <b>To:</b>
                {{ $stockOut->customer->name ?? $stockOut->toWarehouse->name ?? '-' }}
            </div>
        </div>

        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Batch</th>
                    <th class="text-end">Units</th>
                    <th class="text-end">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockOut->items as $i => $item)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $item->product->item_code }} - {{ $item->product->name }}</td>
                    <td>{{ $item->sap_batch }}</td>
                    <td class="text-end">{{ $item->units_dispatch }}</td>
                    <td class="text-end">{{ $item->dispatch_quantity }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="text-end mt-3">
            <a href="{{ route('outbound.invoice', $stockOut->id) }}"
               class="btn btn-primary btn-sm">
                View Invoice
            </a>
        </div>

    </div>
</div>
@endsection
