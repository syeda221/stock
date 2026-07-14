@extends('layouts.app')

<style>
    .signature-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 40px;
}

.signature-table td {
    border: 1px solid #000;
    text-align: center;
    padding: 20px 10px;
    height: 110px;
    vertical-align: bottom;
    font-size: 13px;
}

.signature-title {
    font-weight: bold;
    margin-bottom: 30px;
    display: block;
}
</style>
@section('content')
<div class="card shadow-sm print-area">

    {{-- HEADER --}}
    <div class="card-header d-flex justify-content-between align-items-center no-print">
        <strong>Pick List</strong>
        <button onclick="window.print()" class="btn btn-sm btn-secondary">Print</button>
    </div>

    <div class="card-body">

        {{-- COMPANY --}}
        <table class="table table-borderless mb-2">
            <tr>
                <td width="60%">
                    <img src="{{ asset('logo.png') }}" alt="Company Logo" style="max-height: 150px;" class="mb-2"><br>
                    <strong>Unilever Pakistan Limited</strong><br>
                    Dispatch Location: {{ $stockOut->warehouse->name }}
                </td>
                <td width="40%" class="text-end">
                    <strong>Pick List</strong><br>
                    Date: {{ $stockOut->created_at->format('d.m.Y H:i') }}
                </td>
            </tr>
        </table>

        {{-- HEADER DETAILS --}}
        <table class="table table-bordered table-sm mb-3">
            <tr>
                <th>Date</th>
                <td>{{ $stockOut->created_at->format('d.m.Y H:i') }}</td>

                <th>Dispatched Invoice #</th>
                <td>{{ $stockOut->dispatched_invoice_no ?? '-' }}</td>
            </tr>

            <tr>
                <th>W/H. (Location)</th>
                <td>{{ optional($stockOut->warehouse)->name ?? '-' }}</td>

                <th>Dispatch To</th>
                <td>{{ optional($stockOut->customer)->name ?? optional($stockOut->toWarehouse)->name ?? '-' }}</td>
            </tr>

            <tr>
                <th>Vehicle #</th>
                <td>{{ $stockOut->vehicle_no ?? '-' }}</td>

                <th>Remarks</th>
                <td>{{ $stockOut->remarks ?? '-' }}</td>
            </tr>
        </table>

        {{-- ITEMS --}}
        <table class="table table-bordered table-sm">
            <thead class="table-light text-center">
                <tr>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Vendor Batch</th>
                    <th>PO #</th>
                    <th>Packing</th>
                    <th>Pack Size</th>
                    <th>UOM</th>
                    <th>Units Dispatched</th>
                    <th>Dispatch Qty</th>
                    <th>MFG Date</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQty = 0; @endphp
                @foreach($stockOut->items as $item)
                    @php $totalQty += $item->dispatch_quantity; @endphp
                    <tr>
                        <td>{{ optional($item->product)->item_code ?? '-' }}</td>
                        <td>{{ optional($item->product)->name ?? '-' }}</td>
                        <td>{{ $item->vendor_batch ?? $item->sap_batch ?? '-' }}</td>
                        <td>{{ $item->po_resolved ?? '-' }}</td>
                        <td class="text-center">{{ optional(optional($item->product)->packingType)->name ?? '-' }}</td>
                        <td class="text-end">{{ $item->pack_size_snapshot }}</td>
                        <td class="text-center">{{ $item->uom_resolved ?? '-' }}</td>
                        <td class="text-end">{{ $item->units_dispatch }}</td>
                        <td class="text-end fw-bold">{{ $item->dispatch_quantity }}</td>
                        <td>{{ optional($item->mfg_date)->format('d.m.Y') ?? '-' }}</td>
                        <td>{{ optional($item->expiry_date)->format('d.m.Y') ?? '-' }}</td>
                    </tr>
                @endforeach

                <tr class="fw-bold">
                    <td colspan="8" class="text-end">TOTAL</td>
                    <td class="text-end">{{ $totalQty }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        {{-- SIGNATURES --}}
        <div class="row mt-5">
            <div class="col-6">
                <b>PICKER NAME & SIG:</b><br><br>
                ______________________________
            </div>
            <div class="col-6 text-end">
                <b>PICKING CREATOR NAME & SIG:</b><br><br>
                ______________________________
            </div>
        </div>

    </div>
</div>

<style>
@media print {
    .no-print { display:none }
    body { background:#fff }
    .card { border:none }
    table { font-size:12px }
}
</style>
@endsection
