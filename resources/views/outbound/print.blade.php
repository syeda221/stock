<!DOCTYPE html>
<html>
<head>
    <title>Outbound Receipt</title>
    <style>
        body{font-family:Arial;font-size:13px}
        table{width:100%;border-collapse:collapse}
        td,th{border:1px solid #000;padding:6px}
        .no-border td{border:0}
        .center{text-align:center}
    </style>
</head>
<body onload="window.print()">

<h3 class="center">Outbound Receipt</h3>

<table class="no-border">
<tr><td>Type:</td><td>{{ ucfirst($stockOut->source_type) }}</td></tr>
<tr><td>From Warehouse:</td><td>{{ $stockOut->warehouse->name }}</td></tr>
<tr>
<td>To / Customer:</td>
<td>{{ $stockOut->source_type === 'sale'
        ? $stockOut->customer->name
        : $stockOut->toWarehouse->name }}</td>
</tr>
<tr><td>Date:</td><td>{{ $stockOut->created_at->format('d.m.Y H:i') }}</td></tr>
</table>

<br>

<table>
<thead>
<tr>
<th>Product</th>
<th>Qty</th>
</tr>
</thead>
<tbody>
@foreach($stockOut->items as $it)
<tr>
<td>{{ $it->product->name }}</td>
<td class="center">{{ $it->dispatch_quantity }}</td>
</tr>
@endforeach
</tbody>
</table>

<br>
<p><strong>Remarks:</strong> {{ $stockOut->remarks ?? '-' }}</p>

</body>
</html>
