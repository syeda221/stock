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
    </style>
</head>
<body onload="window.print()">

<h3 class="center title">Inbound Receipt</h3>

<p><strong>Inbound Invoice No:</strong> {{ $stockIn->inbound_invoice_no ?? '-' }}</p>

<table>
<thead>
<tr>
    <th style="width: 40px" class="center">#</th>
    <th>Product</th>
    <th style="width: 120px">SAP Batch</th>
    <th style="width: 120px">Vendor Batch</th>
    <th>PO</th>
    <th>IBD</th>
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
            {{ optional($it->product)->name ?? '-' }}{{ optional($it->product)->item_code ? ' ('.optional($it->product)->item_code.')' : '' }}
        </td>
        <td>{{ $it->sap_batch ?? '-' }}</td>
        <td>{{ $it->vendor_batch ?? '-' }}</td>
        <td>{{ $it->po_no ?? '-' }}</td>
        <td>{{ $it->ibd_no ?? '-' }}</td>
        <td class="right">{{ $it->units_received ?? 0 }}</td>
        <td class="right">{{ $it->pack_size_snapshot ?? 0 }}</td>
        <td class="right">{{ $it->total_quantity ?? 0 }}</td>
        <td class="center">{{ strtoupper($it->quality_clearance ?? 'PENDING') }}</td>
    </tr>
@empty
    <tr>
        <td colspan="10" class="center">No items</td>
    </tr>
@endforelse
</tbody>
</table>

</body>
</html>
