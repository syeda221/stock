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
        <strong>Outbound Invoice</strong>
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
                    <strong>Invoice</strong><br>
                    Date: {{ $stockOut->created_at->format('d.m.Y H:i') }}
                </td>
            </tr>
        </table>

        {{-- HEADER DETAILS --}}
        <table class="table table-bordered table-sm mb-3">
            <tr>
                <th>Invoice No</th>
                <td>{{ $stockOut->dispatched_invoice_no }}</td>

                <th>Outbound Type</th>
                <td>{{ strtoupper($stockOut->source_type) }}</td>
            </tr>

            <tr>
                <th>From Warehouse</th>
                <td>{{ $stockOut->warehouse->name }}</td>

                <th>To</th>
                <td>{{ $stockOut->customer->name ?? $stockOut->toWarehouse->name ?? '-' }}</td>
            </tr>

            <tr>
                <th>Transporter</th>
                <td>{{ $stockOut->transporter->name ?? '-' }}</td>

                <th>Shipment Type</th>
                <td>{{ strtoupper($stockOut->shipment_type) }}</td>
            </tr>

            <tr>
                <th>Vehicle No</th>
                <td>{{ $stockOut->vehicle_no }}</td>

                <th>Vehicle Size</th>
                <td>{{ $stockOut->vehicle_size }}</td>
            </tr>

            <tr>
                <th>Driver</th>
                <td>{{ $stockOut->driver_name }} ({{ $stockOut->driver_mobile }})</td>

                <th>DA No</th>
                <td>{{ $stockOut->da_no ?? '-' }}</td>
            </tr>

            <tr>
                <th>Vehicle In</th>
                <td>{{ \Carbon\Carbon::parse($stockOut->Vehicle_in_time)->format('d.m.Y H:i:s')}}</td>

                <th>Vehicle Out</th>
                <td>{{ \Carbon\Carbon::parse($stockOut->Vehicle_out_time)->format('d.m.Y H:i:s') }}</td>
            </tr>

            <tr>
                <th>Dispatcher</th>
                <td>{{ $stockOut->dispatcher_sig }}</td>

                <th>Picker</th>
                <td>{{ $stockOut->picker }}</td>
            </tr>

            <tr>
                <th>Remarks</th>
                <td colspan="3">{{ $stockOut->remarks ?? '-' }}</td>
            </tr>
        </table>

        {{-- ITEMS --}}
        <table class="table table-bordered table-sm">
            <thead class="table-light text-center">
                <tr>
                    <th>SKU</th>
                    <th>Description</th>
                    {{-- <th>From WH</th> --}}
                    <th>Batch</th>
                    {{-- <th>PO</th> --}}
                    <th>IBD</th>
                    <th>MFG</th>
                    <th>Expiry</th>
                    <th>Pack</th>
                    <th>Units</th>
                    <th>Qty</th>
                    <th>STO</th>
                    <th>UOM</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQty = 0; @endphp
                @foreach($stockOut->items as $item)
                    @php $totalQty += $item->dispatch_quantity; @endphp
                    <tr>
                        <td>{{ $item->product->item_code }}</td>
                        <td>{{ $item->product->name }}</td>
                        {{-- <td>{{ $item->warehouse->name ?? '-' }}</td> --}}
                        <td>{{ $item->sap_batch ?? $item->vendor_batch }}</td>
                        {{-- <td>{{ $item->po_no ?? $item->sourceStockInItem->po_no ?? '-' }}</td> --}}
                        <td>{{ $item->ibd_no ?? $item->sourceStockInItem->ibd_no ?? '-' }}</td>
                        <td>{{ optional($item->mfg_date)->format('d.m.Y') }}</td>
                        <td>{{ optional($item->expiry_date)->format('d.m.Y') }}</td>
                        <td class="text-end">{{ $item->pack_size_snapshot }}</td>
                        <td class="text-end">{{ $item->units_dispatch }}</td>
                        <td class="text-end fw-bold">{{ $item->dispatch_quantity }}</td>
                        <td>{{ $item->sto_no ?? '-' }}</td>

                        <td class="text-center">{{ $item->product->uom->name ?? '-' }}</td>
                        <td>{{ $item->remarks ?? '-' }}</td>
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
      <table class="signature-table">
    <tr>
        <td width="20%">
            <span class="signature-title">Shift Incharge</span>
            ____________________
        </td>
        <td width="20%">
            <span class="signature-title">Shift Supervisor</span>
            ____________________
        </td>
        <td width="20%">
            <span class="signature-title">Warehouse Incharge</span>
            ____________________
        </td>
        <td width="20%">
            <span class="signature-title">Security Incharge</span>
            ____________________
        </td>
        <td width="20%">
            <span class="signature-title">Driver Sign</span>
            ____________________
        </td>
    </tr>
</table>

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
