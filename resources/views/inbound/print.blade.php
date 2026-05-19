<!DOCTYPE html>
<html>
<head>
    <title>Inbound Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #000; padding: 6px; vertical-align: top; }
        .no-border td { border: 0; padding: 3px 0; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #333; }
        .title { margin: 0 0 10px; }
        .meta td:first-child { width: 180px; }
    </style>
</head>
<body onload="window.print()">

<h3 class="center title">Inbound Receipt</h3>

@php
    /** @var \App\Models\StockIn $stockIn */
@endphp

<table class="no-border meta">
    <tr>
        <td><strong>Type</strong></td>
        <td>{{ ucfirst($stockIn->source_type ?? 'inbound') }}</td>
        <td><strong>Date</strong></td>
        <td>{{ optional($stockIn->created_at)->format('d-m-Y H:i') ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Inbound Invoice No</strong></td>
        <td>{{ $stockIn->inbound_invoice_no ?? '-' }}</td>
        <td><strong>Dispatched Invoice No</strong></td>
        <td>{{ $stockIn->dispatched_invoice_no ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Warehouse</strong></td>
        <td>{{ optional($stockIn->warehouse)->name ?? '-' }}</td>
        <td><strong>Vendor</strong></td>
        <td>{{ optional($stockIn->vendor)->name ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Arrived From</strong></td>
        <td>{{ optional($stockIn->arrivedFrom)->name ?? '-' }}</td>
        <td><strong>Transporter</strong></td>
        <td>{{ optional($stockIn->transporter)->name ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Vehicle No</strong></td>
        <td>{{ $stockIn->vehicle_no ?? '-' }}</td>
        <td><strong>Driver</strong></td>
        <td>
            {{ $stockIn->driver_name ?? '-' }}
            @if(!empty($stockIn->driver_mobile))
                <span class="muted">({{ $stockIn->driver_mobile }})</span>
            @endif
        </td>
    </tr>
    <tr>
        <td><strong>Vehicle In Time</strong></td>
        <td>
            {{ $stockIn->vehicle_in_time ? \Carbon\Carbon::parse($stockIn->vehicle_in_time)->format('d-m-Y H:i') : '-' }}
        </td>
        <td><strong>Vehicle Out Time</strong></td>
        <td>
            {{ $stockIn->vehicle_out_time ? \Carbon\Carbon::parse($stockIn->vehicle_out_time)->format('d-m-Y H:i') : '-' }}
        </td>
    </tr>
    <tr>
        <td><strong>PO / IBD / STO</strong></td>
        <td colspan="3">
            PO: {{ $stockIn->po_no ?? '-' }}
            &nbsp;|&nbsp; IBD: {{ $stockIn->ibd_no ?? '-' }}
            &nbsp;|&nbsp; STO: {{ $stockIn->sto_no ?? '-' }}
            &nbsp;|&nbsp; Shipment: {{ $stockIn->shipment_no ?? '-' }}
        </td>
    </tr>
    <tr>
        <td><strong>Dispatcher / Picker</strong></td>
        <td colspan="3">
            Dispatcher: {{ $stockIn->dispatcher_sig ?? '-' }}
            &nbsp;|&nbsp; Picker: {{ $stockIn->picker ?? '-' }}
            &nbsp;|&nbsp; Shipment Type: {{ strtoupper($stockIn->shipment_type ?? 'manual') }}
        </td>
    </tr>
</table>

<br>

<table>
<thead>
<tr>
    <th style="width: 40px" class="center">#</th>
    <th>Product</th>
    <th style="width: 120px">SAP Batch</th>
    <th style="width: 120px">Vendor Batch</th>
    <th style="width: 90px" class="right">Units</th>
    <th style="width: 90px" class="right">Pack</th>
    <th style="width: 110px" class="right">Total Qty</th>
    <th style="width: 90px" class="center">QC</th>
</tr>
</thead>
<tbody>
@forelse($stockIn->items as $it)
    <tr>
        <td class="center">{{ $loop->iteration }}</td>
        <td>
            {{ optional($it->product)->item_code ? optional($it->product)->item_code.' - ' : '' }}{{ optional($it->product)->name ?? '-' }}
        </td>
        <td>{{ $it->sap_batch ?? '-' }}</td>
        <td>{{ $it->vendor_batch ?? '-' }}</td>
        <td class="right">{{ $it->units_received ?? 0 }}</td>
        <td class="right">{{ $it->pack_size_snapshot ?? 0 }}</td>
        <td class="right">{{ $it->total_quantity ?? 0 }}</td>
        <td class="center">{{ strtoupper($it->quality_clearance ?? 'PENDING') }}</td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="center">No items</td>
    </tr>
@endforelse
</tbody>
</table>

<br>
<p><strong>Remarks:</strong> {{ $stockIn->remarks ?? '-' }}</p>

</body>
</html>
