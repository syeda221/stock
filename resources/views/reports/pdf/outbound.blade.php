<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Outbound Report - {{ $stockOut->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 15px;
        }
        .header {
            border-bottom: 3px solid #e74c3c;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 20px;
            color: #e74c3c;
            margin-bottom: 3px;
        }
        .header .subtitle {
            font-size: 13px;
            color: #7f8c8d;
            font-weight: bold;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-row {
            display: table-row;
        }
        .info-cell {
            display: table-cell;
            padding: 4px 8px;
            vertical-align: top;
            width: 50%;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            min-width: 140px;
        }
        .info-value {
            color: #333;
        }
        .section-title {
            background: #ecf0f1;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 12px;
            margin: 15px 0 8px 0;
            border-left: 4px solid #e74c3c;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background: #c0392b;
            color: white;
            padding: 7px 5px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }
        table.data-table td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        table.data-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #ecf0f1;
            font-size: 9px;
            color: #7f8c8d;
            text-align: center;
        }
        .summary-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-bottom: 15px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 20px;
            padding: 5px 10px;
        }
        .summary-label {
            font-weight: bold;
            color: #555;
        }
        .summary-value {
            color: #2c3e50;
            font-weight: bold;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>OUTBOUND STOCK REPORT</h1>
        <div class="subtitle">Stock Dispatch Entry</div>
    </div>

    <!-- Basic Information -->
    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Entry ID:</span>
                <span class="info-value">#{{ $stockOut->id }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Date & Time:</span>
                <span class="info-value">{{ optional($stockOut->created_at)->format('d/m/Y H:i') }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Source Type:</span>
                <span class="info-value">{{ ucfirst($stockOut->source_type ?? 'N/A') }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Warehouse (From):</span>
                <span class="info-value">{{ $stockOut->warehouse->name ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Invoice & Document Details -->
    <div class="section-title">Invoice & Document Information</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Dispatched Invoice No:</span>
                <span class="info-value">{{ $stockOut->dispatched_invoice_no ?? '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Delivery Number:</span>
                <span class="info-value">{{ $stockOut->delivery_no ?? '-' }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Gatepass Number:</span>
                <span class="info-value">{{ $stockOut->gatepass_no ?? '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">PO Number:</span>
                <span class="info-value">{{ $stockOut->po_no ?? '-' }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">STO Number:</span>
                <span class="info-value">{{ $stockOut->sto_no ?? '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Shipment Number:</span>
                <span class="info-value">{{ $stockOut->shipment_no ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Party & Destination Details -->
    <div class="section-title">Party & Destination Information</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Customer:</span>
                <span class="info-value">{{ $stockOut->customer->name ?? 'Transfer' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">To Warehouse:</span>
                <span class="info-value">{{ $stockOut->toWarehouse->name ?? '-' }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Transporter:</span>
                <span class="info-value">{{ $stockOut->transporter->name ?? '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Vehicle Number:</span>
                <span class="info-value">{{ $stockOut->vehicle_no ?? '-' }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Vehicle Size:</span>
                <span class="info-value">{{ $stockOut->vehicle_size ?? '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Driver Name:</span>
                <span class="info-value">{{ $stockOut->driver_name ?? '-' }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Driver Mobile:</span>
                <span class="info-value">{{ $stockOut->driver_mobile ?? '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Picker:</span>
                <span class="info-value">{{ $stockOut->picker ?? '-' }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Vehicle In Time:</span>
                <span class="info-value">{{ $stockOut->vehicle_in_time ? \Carbon\Carbon::parse($stockOut->vehicle_in_time)->format('d/m/Y H:i') : '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Vehicle Out Time:</span>
                <span class="info-value">{{ $stockOut->vehicle_out_time ? \Carbon\Carbon::parse($stockOut->vehicle_out_time)->format('d/m/Y H:i') : '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Summary -->
    @php
        $totalQty = $stockOut->items->sum('dispatch_quantity');
        $totalUnits = $stockOut->items->sum('units_dispatch');
    @endphp
    <div class="summary-box">
        <div class="summary-item">
            <span class="summary-label">Total Items:</span>
            <span class="summary-value">{{ $stockOut->items->count() }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Dispatch Quantity:</span>
            <span class="summary-value">{{ number_format($totalQty, 2) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Units:</span>
            <span class="summary-value">{{ number_format($totalUnits) }}</span>
        </div>
    </div>

    <!-- Items Details -->
    <div class="section-title">Dispatched Items Details</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Product Name</th>
                <th style="width: 80px;">SAP Batch</th>
                <th style="width: 80px;">Vendor Batch</th>
                <th style="width: 60px;" class="text-right">Units</th>
                <th style="width: 50px;" class="text-right">Pack Size</th>
                <th style="width: 80px;" class="text-right">Dispatch Qty</th>
                <th style="width: 100px;">Warehouse Row</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockOut->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product->name ?? '-' }}</td>
                <td>{{ $item->sap_batch ?? '-' }}</td>
                <td>{{ $item->vendor_batch ?? '-' }}</td>
                <td class="text-right">{{ number_format($item->units_dispatch) }}</td>
                <td class="text-right">{{ $item->pack_size_snapshot ?? '-' }}</td>
                <td class="text-right">{{ number_format($item->dispatch_quantity, 2) }}</td>
                <td>{{ $item->warehouseRow->name ?? '-' }}</td>
                <td>{{ $item->remarks ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- General Remarks -->
    @if($stockOut->remarks)
    <div class="section-title">General Remarks</div>
    <div style="padding: 8px; background: #f9f9f9; border-left: 3px solid #e74c3c; margin-bottom: 15px;">
        {{ $stockOut->remarks }}
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Generated on {{ now()->format('d/m/Y H:i:s') }} | Warehouse Management System
    </div>
</body>
</html>
