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
        <strong>Inbound Receipt</strong>
        <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-sm btn-secondary">Print</button>

        </div>
    </div>

    <div class="card-body">

        {{-- COMPANY --}}
        <table class="table table-borderless mb-2">
            <tr>
                <td width="60%">
                    <strong>Unilever Pakistan Limited</strong><br>
                    Receiving Location: {{ optional($stockIn->warehouse)->name ?? '-' }}
                </td>
                <td width="40%" class="text-end">
                    <strong>Receipt</strong><br>
                    Date: {{ optional($stockIn->created_at)->format('d.m.Y H:i') ?? '-' }}
                </td>
            </tr>
        </table>

        {{-- HEADER DETAILS --}}
        <table class="table table-bordered table-sm mb-3 header-details">
            <tr>
                <th>Inbound Invoice No</th>
                <td>{{ $stockIn->inbound_invoice_no ?? '-' }}</td>
            </tr>

            <tr class="print-hidden">
                <th>Inbound Type</th>
                <td>{{ strtoupper($stockIn->source_type ?? 'inbound') }}</td>
                <th>Warehouse</th>
                <td>{{ optional($stockIn->warehouse)->name ?? '-' }}</td>
            </tr>

            <tr class="print-hidden">
                <th>Vendor</th>
                <td>{{ optional($stockIn->vendor)->name ?? '-' }}</td>
                <th>Arrived From</th>
                <td>{{ optional($stockIn->arrivedFrom)->name ?? '-' }}</td>
            </tr>

            <tr class="print-hidden">
                <th>Transporter</th>
                <td>{{ optional($stockIn->transporter)->name ?? '-' }}</td>
                <th>Shipment Type</th>
                <td>{{ strtoupper($stockIn->shipment_type ?? 'manual') }}</td>
            </tr>

            <tr class="print-hidden">
                <th>Vehicle No</th>
                <td>{{ $stockIn->vehicle_no ?? '-' }}</td>
                <th>Vehicle Size</th>
                <td>{{ $stockIn->vehicle_size ?? '-' }}</td>
            </tr>

            <tr class="print-hidden">
                <th>Driver</th>
                <td>
                    {{ $stockIn->driver_name ?? '-' }}
                    @if(!empty($stockIn->driver_mobile))
                        ({{ $stockIn->driver_mobile }})
                    @endif
                </td>
                <th>Delivery No</th>
                <td>{{ $stockIn->delivery_no ?? '-' }}</td>
            </tr>

            <tr class="print-hidden">
                <th>Vehicle In</th>
                <td>{{ $stockIn->vehicle_in_time ? \Illuminate\Support\Carbon::parse($stockIn->vehicle_in_time)->format('d.m.Y H:i') : '-' }}</td>
                <th>Vehicle Out</th>
                <td>{{ $stockIn->vehicle_out_time ? \Illuminate\Support\Carbon::parse($stockIn->vehicle_out_time)->format('d.m.Y H:i') : '-' }}</td>
            </tr>

            <tr class="print-hidden">
                <th>Dispatcher</th>
                <td>{{ $stockIn->dispatcher_sig ?? '-' }}</td>
                <th>Picker</th>
                <td>{{ $stockIn->picker ?? '-' }}</td>
            </tr>

            <tr class="print-hidden">
                <th>Remarks</th>
                <td colspan="3">{{ $stockIn->remarks ?? '-' }}</td>
            </tr>
        </table>

        {{-- ITEMS --}}
        <table class="table table-bordered table-sm">
            <thead class="table-light text-center">
                <tr>
                    <th>SKU</th>
                    <th>Description</th>
                    <th>SAP Batch</th>
                    <th>Vendor Batch</th>
                    <th>Delivery No</th>
                    <th>Shipment</th>
                    <th>STO</th>
                    <th>PO</th>
                    <th>IBD</th>
                    <th>MFG</th>
                    <th>Expiry</th>
                    <th>Pack</th>
                    <th>Units</th>
                    <th>Total Qty</th>
                    <th>QC</th>
                    <th>UOM</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQty = 0; @endphp
                @foreach($stockIn->items as $item)
                    @php $totalQty += (float) ($item->total_quantity ?? 0); @endphp
                    <tr>
                        <td>{{ $item->product->item_code ?? '-' }}</td>
                        <td>{{ $item->product->name ?? '-' }} ({{ $item->product->item_code ?? '-' }})</td>
                        <td>{{ $item->sap_batch ?? '-' }}</td>
                        <td>{{ $item->vendor_batch ?? '-' }}</td>
                        <td>{{ $stockIn->delivery_no ?? '-' }}</td>
                        <td>{{ $stockIn->shipment_no ?? '-' }}</td>
                        <td>{{ $stockIn->sto_no ?? '-' }}</td>
                        <td>{{ $item->po_no ?? $stockIn->po_no ?? '-' }}</td>
                        <td>{{ $item->ibd_no ?? $stockIn->ibd_no ?? '-' }}</td>
                        <td>{{ optional($item->mfg_date)->format('d.m.Y') }}</td>
                        <td>{{ optional($item->expiry_date)->format('d.m.Y') }}</td>
                        <td class="text-end">{{ $item->pack_size_snapshot }}</td>
                        <td class="text-end">{{ $item->units_received ?? 0 }}</td>
                        <td class="text-end fw-bold">{{ $item->total_quantity ?? 0 }}</td>
                        <td class="text-center">{{ strtoupper($item->quality_clearance ?? 'PENDING') }}</td>
                        <td class="text-center">{{ $item->product->uom->name ?? '-' }}</td>
                        <td>{{ $item->remarks ?? '-' }}</td>
                    </tr>
                @endforeach

                <tr class="fw-bold">
                    <td colspan="12" class="text-end">TOTAL</td>
                    <td class="text-end">{{ $totalQty }}</td>
                    <td colspan="4"></td>
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
    .print-hidden { display:none }
    body { background:#fff }
    .card { border:none }
    table { font-size:12px }
}
</style>
@endsection
