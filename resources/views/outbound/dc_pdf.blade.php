<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans; font-size: 12px }
        table { width: 100%; border-collapse: collapse }
        th, td { border: 1px solid #000; padding: 6px }
        th { background: #eee }
        .text-end { text-align: right }
        .text-center { text-align: center }
    </style>
</head>
<body>

<h3 style="text-align:center">DISPATCH CHALLAN</h3>

<p>
<strong>DC No:</strong> {{ $stockOut->dispatched_invoice_no }} <br>
<strong>Date:</strong> {{ $stockOut->created_at->format('d/m/Y') }} <br>
<strong>From:</strong> {{ $stockOut->warehouse->name }} <br>
<strong>To:</strong>
{{ $stockOut->customer->name ?? $stockOut->toWarehouse->name ?? '-' }}
</p>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th>Batch</th>
            <th>Pack</th>
            <th>Units</th>
            <th>Qty</th>
        </tr>
    </thead>
    <tbody>
        @foreach($stockOut->items as $i => $item)
        <tr>
            <td class="text-center">{{ $i+1 }}</td>
            <td>{{ $item->product->item_code }} - {{ $item->product->name }}</td>
            <td>{{ $item->sap_batch ?? '-' }}</td>
            <td class="text-end">{{ $item->pack_size_snapshot }}</td>
            <td class="text-end">{{ $item->units_dispatch }}</td>
            <td class="text-end">{{ $item->dispatch_quantity }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<p style="margin-top:40px">
<strong>Vehicle:</strong> {{ $stockOut->vehicle_no }} <br>
<strong>Driver:</strong> {{ $stockOut->driver_name }} <br>
<strong>Remarks:</strong> {{ $stockOut->remarks }}
</p>

</body>
</html>
