<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Warehouse Capacity Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.4; color: #333; padding: 12px; }
        .header { border-bottom: 3px solid #2c3e50; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { font-size: 18px; color: #2c3e50; margin-bottom: 2px; }
        .header .subtitle { font-size: 11px; color: #7f8c8d; }
        .summary { margin-bottom: 12px; }
        .summary table { width: 100%; border-collapse: collapse; }
        .summary td { padding: 3px 8px; font-size: 10px; }
        .summary .label { font-weight: bold; color: #555; width: 100px; }
        .warehouse-section { margin-bottom: 14px; page-break-inside: avoid; }
        .warehouse-title { font-size: 13px; font-weight: bold; color: #2c3e50; background: #f0f4f8; padding: 5px 8px; border-bottom: 2px solid #2c3e50; margin-bottom: 4px; }
        .wh-stats { font-size: 10px; color: #555; margin-bottom: 4px; padding: 0 8px; }
        table.rows { width: 100%; border-collapse: collapse; margin-bottom: 4px; table-layout: fixed; }
table.rows th { background: #34495e; color: #fff; padding: 4px 6px; text-align: left; font-size: 9px; text-transform: uppercase; }
table.rows td { padding: 3px 6px; border-bottom: 1px solid #ddd; font-size: 10px; word-wrap: break-word; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
table.rows th.text-right,
table.rows td.text-right { text-align: right; }
table.rows th.text-center,
table.rows td.text-center { text-align: center; }
table.rows tr:nth-child(even) td { background: #fafafa; }
        .full-row { background: #ffeaea !important; }
        .full-row td { color: #c0392b; font-weight: bold; }
        .total-row td { font-weight: bold; border-top: 2px solid #2c3e50; background: #ecf0f1 !important; }
        .badge-full { color: #c0392b; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 10px; font-size: 8px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Warehouse Capacity Report</h1>
        <div class="subtitle">Generated on {{ date('d.m.Y H:i') }}</div>
    </div>

    @php
        $grandTotalCapacity = 0;
        $grandTotalUsed = 0;
        $allUnlimited = true;
    @endphp

    @forelse($warehouses as $warehouse)
        @php
            $whCapacity = $warehouse->total_capacity ?? 0;
            $whUsed = $warehouse->used_pallets ?? 0;
            $whFree = $warehouse->free_pallets;
            $grandTotalCapacity += $whCapacity;
            $grandTotalUsed += $whUsed;
        @endphp
        <div class="warehouse-section">
            <div class="warehouse-title">{{ $warehouse->name }} ({{ $warehouse->city ?? 'N/A' }})</div>
            <div class="wh-stats">
                Capacity: {{ $whCapacity }} pallets &nbsp;|&nbsp;
                Used: {{ $whUsed }} pallets &nbsp;|&nbsp;
                Free: {{ $whFree !== null ? $whFree : '∞' }} pallets
                @if($warehouse->is_full)
                    &nbsp; <span class="badge-full">⚠ FULL</span>
                @endif
            </div>

            <table class="rows">
               <colgroup>
    <col style="width:5%;">
    <col style="width:37%;">
    <col style="width:14%;">
    <col style="width:14%;">
    <col style="width:14%;">
    <col style="width:16%;">
</colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Row Name</th>
                        <th class="text-right">Capacity</th>
                        <th class="text-right">Used</th>
                        <th class="text-right">Free</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouse->rows as $row)
                        @php
                            $isRowFull = $row->free_pallets !== null && $row->free_pallets === 0;
                            $isOver = ($row->used_pallets ?? 0) > $row->pallet_capacity;
                        @endphp
                        <tr class="{{ $isOver ? 'full-row' : ($isRowFull ? 'full-row' : '') }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->row_name }}</td>
                            <td class="text-right">{{ $row->pallet_capacity }}</td>
                            <td class="text-right">{{ $row->used_pallets ?? 0 }}</td>
                            <td class="text-right">{{ $row->free_pallets !== null ? $row->free_pallets : '∞' }}</td>
                            <td class="text-center">
                                @if($isOver) OVER CAPACITY
                                @elseif($isRowFull) FULL
                                @else OK
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No rows configured</td></tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td class="text-right">{{ $warehouse->rows->sum('pallet_capacity') }}</td>
                        <td class="text-right">{{ $warehouse->rows->sum('used_pallets') }}</td>
                        <td class="text-right">{{ $whFree !== null ? $whFree : '∞' }}</td>
                        <td class="text-center">{{ $warehouse->is_full ? 'FULL' : ($whFree === null ? '—' : 'OK') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <p class="text-center">No active warehouses found.</p>
    @endforelse

    <div class="summary">
        <table>
            <tr><td class="label">Grand Total</td><td>{{ $warehouses->count() }} warehouses</td></tr>
            <tr><td class="label">Total Capacity</td><td>{{ $grandTotalCapacity }} pallets</td></tr>
            <tr><td class="label">Total Used</td><td>{{ $grandTotalUsed }} pallets</td></tr>
            <tr><td class="label">Total Free</td><td>{{ $grandTotalCapacity ? $grandTotalCapacity - $grandTotalUsed . ' pallets' : 'N/A (unlimited)' }}</td></tr>
        </table>
    </div>

    <div class="footer">
        Warehouse Capacity Report &mdash; {{ date('d.m.Y H:i') }}
    </div>
</body>
</html>
