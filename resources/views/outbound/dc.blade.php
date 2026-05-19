@extends('layouts.app')

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between">
        <strong>Dispatch Challan (DC)</strong>
        <button onclick="window.print()" class="btn btn-sm btn-secondary">Print</button>
    </div>

    <div class="card-body">

        <p>
            <b>DC No:</b> {{ $stockOut->dispatched_invoice_no }} <br>
            <b>From:</b> {{ $stockOut->warehouse->name }} <br>
            <b>To:</b>
            {{ $stockOut->customer->name ?? $stockOut->toWarehouse->name ?? '-' }}
        </p>

        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Batch</th>
                    <th>Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockOut->items as $i => $item)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->sap_batch }}</td>
                    <td class="text-end">{{ $item->dispatch_quantity }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row mt-5">
            <div class="col-md-6">
                <b>Issued By</b><br><br>
                ____________________
            </div>
            <div class="col-md-6 text-end">
                <b>Received By</b><br><br>
                ____________________
            </div>
        </div>

    </div>
</div>

<style>
@media print {
    .btn, .card-header { display:none }
}
</style>
@endsection
