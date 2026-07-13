@extends('layouts.app')

@section('content')
<div class="card shadow-sm print-container">
    <div class="card-header d-flex justify-content-between">
        <strong>Dispatch Challan (DC)</strong>
        <button onclick="window.print()" class="btn btn-sm btn-secondary">Print</button>
    </div>

    <div class="card-body">

        <div class="dc-header" style="display: flex; justify-content: space-between; align-items: stretch; padding: 25px 0; margin-bottom: 20px;">
            <!-- Left Section: Details -->
            <div style="flex: 1; display: flex; flex-direction: column; justify-content: flex-start;">
                <div style="display: grid; grid-template-columns: 120px auto; row-gap: 8px;">
                    <strong>Date:</strong> <span>{{ $stockOut->created_at->format('d-m-Y') }}</span>
                    <strong>DC No.:</strong> <span>{{ $stockOut->dispatched_invoice_no ?? '-' }}</span>
                    <strong>Seal No.:</strong> <span>{{ $stockOut->seal_no ?? '-' }}</span>
                    <strong>Gate Pass No.:</strong> <span>{{ $stockOut->gatepass_no ?? '-' }}</span>
                    <strong>Dispatch No.:</strong> <span>{{ $stockOut->da_no ?? '-' }}</span>
                    <strong>Vehicle No.:</strong> <span>{{ $stockOut->vehicle_no ?? '-' }}</span>
                    <strong>From:</strong> <span>{{ optional($stockOut->warehouse)->name ?? '-' }}</span>
                    <strong>To:</strong> <span>{{ $stockOut->customer->name ?? $stockOut->toWarehouse->name ?? '-' }}</span>
                </div>
            </div>

            <!-- Center Section: Logo -->
            <div style="flex: 1; display: flex; justify-content: center; align-items: flex-start; margin-top: -30px;">
                <img src="{{ asset('logo.png') }}" alt="Company Logo" style="max-height: 130px;">
            </div>

            <!-- Right Section: Company Info -->
            <div style="flex: 1; display: flex; flex-direction: column; justify-content: flex-start; align-items: flex-end; row-gap: 5px; text-align: right;">
                <div><strong>Address:</strong> Your Company Address</div>
                <div><strong>Phone No:</strong> +1234567890</div>
                <div><strong>Email:</strong> info@company.com</div>
            </div>
        </div>

        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%; text-align: center;">#</th>
                    <th style="width: 60%; text-align: left;">Item</th>
                    <th style="width: 15%; text-align: left;">Batch</th>
                    <th style="width: 20%; text-align: right;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockOut->items as $i => $item)
                <tr>
                    <td style="text-align: center;">{{ $i+1 }}</td>
                    <td style="text-align: left;">{{ $item->product->name }}</td>
                    <td style="text-align: left;">{{ $item->sap_batch }}</td>
                    <td style="text-align: right;">{{ $item->dispatch_quantity }}</td>
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
    .btn, .card-header { display: none !important; }
    @page { margin: 1cm; } /* Equal margins around the page */
    body { background: #fff; }
    .card { border: none !important; box-shadow: none !important; }
}
.print-container {
    width: 100%;
    margin: 0 auto;
}
.table td, .table th {
    vertical-align: middle;
}
</style>
@endsection
