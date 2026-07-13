<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inbound Report - {{ $stockIn->id }}</title>
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
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 20px;
            color: #2c3e50;
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
            border-left: 4px solid #3498db;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            table-layout: fixed;
        }
        table.data-table th {
            background: #34495e;
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
            word-wrap: break-word;
            overflow-wrap: break-word;
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
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-pending {
            background: #f39c12;
            color: white;
        }
        .badge-approved {
            background: #27ae60;
            color: white;
        }
        .badge-rejected {
            background: #e74c3c;
            color: white;
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
        <h1>INBOUND STOCK REPORT</h1>
        <div class="subtitle">{{ $stockIn->source_type == 'opening' ? 'Opening Stock Entry' : 'Inbound Entry' }}</div>
    </div>

    <!-- Basic Information -->
    <div class="info-grid">
        <div class="info-row">
            
            <div class="info-cell">
                <span class="info-label">Date & Time:</span>
                <span class="info-value">{{ optional($stockIn->created_at)->format('d.m.Y H:i') }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Source Type:</span>
                <span class="info-value">{{ ucfirst($stockIn->source_type ?? 'N/A') }}</span>
            </div>
            <!-- <div class="info-cell">
                <span class="info-label">Warehouse:</span>
                <span class="info-value">{{ $stockIn->warehouse->name ?? 'N/A' }}</span>
            </div> -->
        </div>
    </div>

    <!-- Invoice & Document Details -->
    <div class="section-title">Invoice & Document Information</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Inbound Invoice No:</span>
                <span class="info-value">{{ $stockIn->inbound_invoice_no ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Party & Transport Details -->
    <div class="section-title">Party & Transport Information</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Vendor:</span>
                <span class="info-value">{{ $stockIn->vendor->name ?? '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Arrived From:</span>
                <span class="info-value">{{ $stockIn->arrivedFrom->name ?? '-' }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Transporter:</span>
                <span class="info-value">{{ $stockIn->transporter->name ?? '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Vehicle Number:</span>
                <span class="info-value">{{ $stockIn->vehicle_no ?? '-' }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Vehicle Size:</span>
                <span class="info-value">{{ $stockIn->vehicle_size ?? '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Driver Name:</span>
                <span class="info-value">{{ $stockIn->driver_name ?? '-' }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Driver Mobile:</span>
                <span class="info-value">{{ $stockIn->driver_mobile ?? '-' }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Gatepass etc:</span>
                <span class="info-value"></span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Seal #:</span>
                <span class="info-value"></span>
            </div>
            <div class="info-cell">
            </div>
        </div>
     <div class="info-row">
            <div class="info-cell">
                <!-- <span class="info-label">Vehicle In Time:</span> -->
                <!-- <span class="info-value">{{ $stockIn->vehicle_in_time ? \Carbon\Carbon::parse($stockIn->vehicle_in_time)->format('d.m.Y H:i') : '-' }}</span> -->
            </div>
            <div class="info-cell">
                <!-- <span class="info-label">Vehicle Out Time:</span> -->
                <!-- <span class="info-value">{{ $stockIn->vehicle_out_time ? \Carbon\Carbon::parse($stockIn->vehicle_out_time)->format('d.m.Y H:i') : '-' }}</span> -->
            </div>
        </div>
    </div>

    @php
        $totalQty = $stockIn->items->sum('total_quantity');
        $totalBalance = 0; // $stockIn->items->sum('balance_quantity');
        $totalUnits = $stockIn->items->sum('units_received');
        $totalBalanceUnits = 0;
        /* foreach($stockIn->items as $item) {
            $totalBalanceUnits += $item->pack_size_snapshot > 0 ? $item->balance_quantity / $item->pack_size_snapshot : 0;
        } */
        
        $groupedItems = $stockIn->items->groupBy(function($item) {
            return $item->product_id . '_' . $item->sap_batch . '_' . $item->vendor_batch . '_' . $item->po_no . '_' . $item->ibd_no . '_' . $item->mfg_date . '_' . $item->expiry_date;
        })->map(function($group) {
            $first = clone $group->first();
            $first->units_received = $group->sum('units_received');
            $first->total_quantity = $group->sum('total_quantity');
            
            // Sum up the sub-items for the details row below
            $first->pallets_used = $group->sum('pallets_used');
            // $first->sound_stock = $group->sum('sound_stock');
            $first->block_stock = $group->sum('block_stock');
            $first->hold_stock = $group->sum('hold_stock');
            
            // Gather row names
            $rows = $group->map(function($i) { return optional($i->warehouseRow)->name; })->filter()->unique()->implode(', ');
            $first->row_names = $rows;
            
            return $first;
        })->values();
    @endphp
    <div class="summary-box">
        <div class="summary-item">
            <span class="summary-label">Total Items:</span>
            <span class="summary-value">{{ $groupedItems->count() }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Quantity:</span>
            <span class="summary-value">{{ number_format($totalQty, 2) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Units:</span>
            <span class="summary-value">{{ number_format($totalUnits) }}</span>
        </div>
        <!-- <div class="summary-item">
            <span class="summary-label">Balance Quantity:</span>
            <span class="summary-value">{{ number_format($totalBalance, 2) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Balance Units:</span>
            <span class="summary-value">{{ number_format($totalBalanceUnits, 2) }}</span>
        </div> -->
    </div>

    <!-- Items Details -->
    <div class="section-title">Stock Items Details</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 80px;">Item Code</th>
                <th style="width: 30%;">Product Name</th>
                <!-- <th style="width: 80px;">SAP Batch</th> -->
                <th style="width: 80px;">Vendor Batch</th>
                <th style="width: 50px;">PO</th>
                <th style="width: 50px;">IBD</th>
                <th style="width: 50px;" class="text-center">Units</th>
                <th style="width: 50px;" class="text-center">Pack Size</th>
                <th style="width: 70px;" class="text-center">Total Qty</th>
                <th style="width: 100px;" class="text-center">MFG / EXP</th>
                <th style="width: 60px;" class="text-center">QC Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupedItems as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product->item_code ?? '-' }}</td>
                <td>{{ $item->product->name ?? '-' }}</td>
                <!-- <td>{{ $item->sap_batch ?? '-' }}</td> -->
                <td>{{ $item->vendor_batch ?? '-' }}</td>
                <td>{{ $item->po_no ?? '-' }}</td>
                <td>{{ $item->ibd_no ?? '-' }}</td>
                <td class="text-center">{{ number_format($item->units_received) }}</td>
                <td class="text-center">{{ $item->pack_size_snapshot ?? '-' }}</td>
                <td class="text-center">{{ number_format($item->total_quantity, 2) }}</td>
                <td class="text-center">{{ $item->mfg_date ? \Carbon\Carbon::parse($item->mfg_date)->format('d.m.Y') : '-' }} / {{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('d.m.Y') : '-' }}</td>
                <td class="text-center">
                    @if($item->quality_clearance == 'pending')
                        <span class="badge badge-pending">PENDING</span>
                    @elseif($item->quality_clearance == 'approved')
                        <span class="badge badge-approved">APPROVED</span>
                    @elseif($item->quality_clearance == 'rejected')
                        <span class="badge badge-rejected">REJECTED</span>
                    @else
                        <span>-</span>
                    @endif
                </td>
            </tr>
            @if($item->remarks || $item->row_names || $item->use_pallets)
            <tr>
                <td></td>
                <td colspan="12" style="font-size: 9px; color: #666; padding-left: 15px;">
                    @if($item->row_names)
                        <strong>Row:</strong> {{ $item->row_names }} &nbsp;|&nbsp;
                    @endif
                    @if($item->use_pallets)
                        <strong>Pallets:</strong> {{ $item->pallets_used }} &nbsp;|&nbsp;
                    @endif
                    @if($item->sound_stock)
                        <!-- <strong>Sound:</strong> {{ number_format($item->sound_stock, 2) }} &nbsp;|&nbsp; -->
                    @endif
                    @if($item->block_stock)
                        <strong>Block:</strong> {{ number_format($item->block_stock, 2) }} &nbsp;|&nbsp;
                    @endif
                    @if($item->hold_stock)
                        <strong>Hold:</strong> {{ number_format($item->hold_stock, 2) }} &nbsp;|&nbsp;
                    @endif
                    @if($item->remarks)
                        <strong>Remarks:</strong> {{ $item->remarks }}
                    @endif
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

    <!-- General Remarks -->
    @if($stockIn->remarks)
    <div class="section-title">General Remarks</div>
    <div style="padding: 8px; background: #f9f9f9; border-left: 3px solid #3498db; margin-bottom: 15px;">
        {{ $stockIn->remarks }}
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Generated on {{ now()->format('d.m.Y H:i:s') }} | Warehouse Management System
    </div>
</body>
</html>
