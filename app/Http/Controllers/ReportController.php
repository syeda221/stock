<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function inbound(Request $request)
    {
        $query = StockIn::with(['vendor', 'warehouse', 'transporter', 'arrivedFrom', 'items.product'])
            ->where('source_type', 'inbound'); // Only inbound transactions, not opening stock

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('invoice_no')) {
            $query->where(function($q) use ($request) {
                $q->where('inbound_invoice_no', 'like', '%' . $request->invoice_no . '%')
                  ->orWhere('dispatched_invoice_no', 'like', '%' . $request->invoice_no . '%');
            });
        }

        // Filter by QC status (quality_clearance)
        if ($request->filled('qc_status')) {
            $query->whereHas('items', function($q) use ($request) {
                $q->where('quality_clearance', $request->qc_status);
            });
        }

        // Get paginated results
        $stockIns = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Calculate summary statistics
        $baseQuery = StockIn::where('source_type', 'inbound');

        // Apply same filters for summary
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('vendor_id')) {
            $baseQuery->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('warehouse_id')) {
            $baseQuery->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('invoice_no')) {
            $baseQuery->where(function($q) use ($request) {
                $q->where('inbound_invoice_no', 'like', '%' . $request->invoice_no . '%')
                  ->orWhere('dispatched_invoice_no', 'like', '%' . $request->invoice_no . '%');
            });
        }
        if ($request->filled('qc_status')) {
            $baseQuery->whereHas('items', function($q) use ($request) {
                $q->where('quality_clearance', $request->qc_status);
            });
        }

        // Get stock_in IDs for item calculations
        $stockInIds = $baseQuery->pluck('id');

        // Build items query with QC filter
        $itemsQuery = DB::table('stock_in_items')->whereIn('stock_in_id', $stockInIds);
        if ($request->filled('qc_status')) {
            $itemsQuery->where('quality_clearance', $request->qc_status);
        }

        $summary = [
            'total_entries' => $baseQuery->count(),
            'total_items' => $itemsQuery->sum('total_quantity'),
            'total_units' => $itemsQuery->sum('units_received'),
            'qc_pending' => DB::table('stock_in_items')
                ->whereIn('stock_in_id', $stockInIds)
                ->where('quality_clearance', 'pending')
                ->count(),
            'qc_approved' => DB::table('stock_in_items')
                ->whereIn('stock_in_id', $stockInIds)
                ->where('quality_clearance', 'approved')
                ->count(),
            'qc_rejected' => DB::table('stock_in_items')
                ->whereIn('stock_in_id', $stockInIds)
                ->where('quality_clearance', 'rejected')
                ->count(),
        ];

        // Get filter options
        $vendors = \App\Models\Vendor::orderBy('name', 'asc')->get();
        $warehouses = \App\Models\Warehouse::orderBy('name', 'asc')->get();

        return view('reports.inbound', compact('stockIns', 'summary', 'vendors', 'warehouses'));
    }

    public function outbound(Request $request)
    {
        $query = StockOut::with(['customer', 'warehouse', 'toWarehouse', 'transporter', 'items.product']);

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('invoice_no')) {
            $query->where(function($q) use ($request) {
                $q->where('dispatched_invoice_no', 'like', '%' . $request->invoice_no . '%')
                  ->orWhere('delivery_no', 'like', '%' . $request->invoice_no . '%')
                  ->orWhere('gatepass_no', 'like', '%' . $request->invoice_no . '%');
            });
        }

        // Get paginated results
        $stockOuts = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Calculate summary statistics
        $baseQuery = StockOut::query();

        // Apply same filters for summary
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('customer_id')) {
            $baseQuery->where('customer_id', $request->customer_id);
        }
        if ($request->filled('warehouse_id')) {
            $baseQuery->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('invoice_no')) {
            $baseQuery->where(function($q) use ($request) {
                $q->where('dispatched_invoice_no', 'like', '%' . $request->invoice_no . '%')
                  ->orWhere('delivery_no', 'like', '%' . $request->invoice_no . '%')
                  ->orWhere('gatepass_no', 'like', '%' . $request->invoice_no . '%');
            });
        }

        $summary = [
            'total_entries' => $baseQuery->count(),
            'total_quantity' => DB::table('stock_out_items')
                ->whereIn('stock_out_id', $baseQuery->pluck('id'))
                ->sum('dispatch_quantity'),
            'total_units' => DB::table('stock_out_items')
                ->whereIn('stock_out_id', $baseQuery->pluck('id'))
                ->sum('units_dispatch'),
        ];

        // Get filter options
        $customers = \App\Models\Customer::orderBy('name', 'asc')->get();
        $warehouses = \App\Models\Warehouse::orderBy('name', 'asc')->get();

        return view('reports.outbound', compact('stockOuts', 'summary', 'customers', 'warehouses'));
    }

    public function inboundExport(Request $request)
    {
        $query = StockIn::with(['vendor', 'warehouse', 'transporter', 'arrivedFrom', 'items.product', 'items.warehouseRow'])
            ->where('source_type', 'inbound');

        // Apply same filters as inbound report
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('invoice_no')) {
            $query->where(function($q) use ($request) {
                $q->where('inbound_invoice_no', 'like', '%' . $request->invoice_no . '%')
                  ->orWhere('dispatched_invoice_no', 'like', '%' . $request->invoice_no . '%');
            });
        }
        if ($request->filled('qc_status')) {
            $query->whereHas('items', function($q) use ($request) {
                $q->where('quality_clearance', $request->qc_status);
            });
        }

        $inboundData = $inboundQuery->orderBy('stock_in_items.warehouse_row_id')->orderBy('stock_in_items.id')->get();

        // Build row-letter mapping: first row per warehouse = A, second = B, etc.
        $rowLetterMap = [];
        $allRows = \App\Models\WarehouseRow::orderBy('warehouse_id')->orderBy('row_name')->get()->groupBy('warehouse_id');
        foreach ($allRows as $whId => $rows) {
            $rows = $rows->sortBy('row_name', SORT_NATURAL | SORT_FLAG_CASE)->values();
            foreach ($rows as $i => $row) {
                $n = $i + 1;
                $letter = '';
                while ($n > 0) {
                    $n--;
                    $letter = chr(65 + $n % 26) . $letter;
                    $n = (int)($n / 26);
                }
                $rowLetterMap[$whId . '-' . $row->row_name] = $letter;
            }
        }

        // Pre-compute pallet positions across ALL items (opening + inbound) so they match stock ledger
        $rowPalletOffsets = [];
        $inboundPositions = [];
        $allItems = \App\Models\StockInItem::whereHas('stockIn', fn($q) => $q->whereIn('source_type', ['opening', 'inbound']))
            ->where('balance_quantity', '>', 0)
            ->where('pallets_used', '>', 0)
            ->whereNotNull('warehouse_row_id')
            ->with('stockIn', 'warehouseRow')
            ->orderBy('created_at')
            ->get();
        foreach ($allItems as $item) {
            $whId = $item->warehouse_id;
            $row = $item->warehouseRow;
            if (!$whId || !$row || !$row->row_name) continue;
            $palletCount = (int)$item->pallets_used;
            $rowKey = $whId . '-' . $row->row_name;
            if (!isset($rowPalletOffsets[$rowKey])) $rowPalletOffsets[$rowKey] = 0;
            if ($item->pallet_start !== null) {
                $palletStart = (int)$item->pallet_start;
                $palletEnd = $palletStart + $palletCount - 1;
                $rowPalletOffsets[$rowKey] = max($rowPalletOffsets[$rowKey], $palletEnd);
            } else {
                $palletStart = $rowPalletOffsets[$rowKey] + 1;
                $palletEnd = $rowPalletOffsets[$rowKey] + $palletCount;
                $rowPalletOffsets[$rowKey] = $palletEnd;
            }
            if ($item->stockIn->source_type === 'inbound') {
                $rw = $rowLetterMap[$rowKey] ?? '';
                $wp = str_pad($whId, 2, '0', STR_PAD_LEFT);
                $inboundPositions[$item->id] = [
                    'start' => $palletStart, 'end' => $palletEnd,
                    'letter' => $rw, 'wh_padded' => $wp,
                ];
            }
        }

        // Generate CSV
        $filename = 'inbound_report_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($stockIns, $inboundPositions) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Item Code', 'Product Name', 'Warehouse', 'Category', 'UOM',
                'IBD', 'PO', 'Vendor Batch', 'SAP Batch', 'Packing', 'Pack Size',
                'Units Received', 'Total Qty', 'MFG Date', 'Expiry Date',
                'Balance Qty', 'Pallets Used', 'Quality Check', 'Sound', 'Blocked', 'Hold',
                'Entry ID', 'Date', 'Source Type', 'Vendor', 'Arrived From', 'Transporter',
                'Inbound Invoice', 'Dispatched Invoice', 'Shipment No', 'STO No',
                'Delivery No', 'Vehicle No', 'Vehicle Size', 'Driver Name', 'Driver Mobile',
                'Vehicle In Time', 'Vehicle Out Time', 'Picker', 'Shipment Type',
                'Warehouse Row', 'Item Remarks', 'General Remarks'
            ]);

            // Data rows
            foreach ($stockIns as $stockIn) {
                foreach ($stockIn->items as $item) {
                    $warehouseDisplay = $stockIn->warehouse->name ?? '';
                    $unitsVal = $item->units_received ?? 0;
                    $qtyVal = $item->total_quantity ?? 0;
                    $balVal = $item->balance_quantity ?? 0;
                    $palletsVal = $item->pallets_used ?? 0;
                    $rowNameVal = $item->warehouseRow->name ?? '';

                    $pos = $inboundPositions[$item->id] ?? null;

                    if ($pos) {
                        $whPadded = $pos['wh_padded'];
                        $rowLetter = $pos['letter'];
                        $palletStart = $pos['start'];
                        $palletEnd = $pos['end'];
                        $numPallets = $palletEnd - $palletStart + 1;

                        $maxPerPallet = $item->product->cartons_per_pallet ?? null;
                        $totalUnits = (float) $unitsVal;
                        $totalQty = (float) $qtyVal;
                        $totalBalance = (float) $balVal;
                        $remainingUnits = $totalUnits;
                        $assignedQty = 0.0;
                        $assignedBalance = 0.0;

                        for ($p = $palletStart; $p <= $palletEnd; $p++) {
                            $isLast = ($p == $palletEnd);
                            if ($maxPerPallet) {
                                $perPalletUnits = min($maxPerPallet, $remainingUnits);
                            } else {
                                $perPalletUnits = $numPallets > 0 ? $totalUnits / $numPallets : $totalUnits;
                            }
                            $ratio = $totalUnits > 0 ? $perPalletUnits / $totalUnits : 0;
                            $palletQty = $isLast ? $totalQty - $assignedQty : round($ratio * $totalQty, 4);
                            $palletBalance = $isLast ? $totalBalance - $assignedBalance : round($ratio * $totalBalance, 4);
                            $remainingUnits -= $perPalletUnits;
                            $assignedQty += $palletQty;
                            $assignedBalance += $palletBalance;

                            $psPadded = str_pad($p, 3, '0', STR_PAD_LEFT);
                            $warehouseDisplay = "W{$whPadded}.{$rowLetter}{$psPadded}";

                            fputcsv($file, [
                                $item->product->item_code ?? '',
                                $item->product->name ?? '',
                                $warehouseDisplay,
                                $item->product->category->name ?? '',
                                $item->product->uom->name ?? ($item->uom_snapshot ?? ''),
                                $stockIn->ibd_no ?? $item->ibd_no ?? '',
                                $stockIn->po_no ?? $item->po_no ?? '',
                                $item->vendor_batch ?? '',
                                $item->sap_batch ?? '',
                                $item->product->packingType->name ?? ($item->packing_snapshot ?? ''),
                                $item->pack_size_snapshot ?? '',
                                $perPalletUnits,
                                $palletQty,
                                $item->mfg_date ?? '',
                                $item->expiry_date ?? '',
                                $palletBalance,
                                1,
                                $item->quality_clearance ?? '',
                                $item->sound_stock ? 'Yes' : 'No',
                                $item->block_stock ? 'Yes' : 'No',
                                $item->hold_stock ? 'Yes' : 'No',
                                $stockIn->id,
$stockIn->created_at->format('d.m.Y H:i'),
                    $stockIn->source_type ?? '',
                    $stockIn->vendor->name ?? '',
                    $stockIn->arrivedFrom->name ?? '',
                    $stockIn->transporter->name ?? '',
                    $stockIn->inbound_invoice_no ?? '',
                    $stockIn->dispatched_invoice_no ?? '',
                    $stockIn->shipment_no ?? '',
                    $stockIn->sto_no ?? '',
                    $stockIn->delivery_no ?? '',
                    $stockIn->vehicle_no ?? '',
                                $stockIn->vehicle_size ?? '',
                                $stockIn->driver_name ?? '',
                                $stockIn->driver_mobile ?? '',
                                $stockIn->vehicle_in_time ?? '',
                                $stockIn->vehicle_out_time ?? '',
                                $stockIn->picker ?? '',
                                $stockIn->shipment_type ?? '',
                                $rowNameVal,
                                $item->remarks ?? '',
                                $stockIn->remarks ?? ''
                            ]);
                        }
                    } else {
                        fputcsv($file, [
                            $item->product->item_code ?? '',
                            $item->product->name ?? '',
                            $warehouseDisplay,
                            $item->product->category->name ?? '',
                            $item->product->uom->name ?? ($item->uom_snapshot ?? ''),
                            $stockIn->ibd_no ?? $item->ibd_no ?? '',
                            $stockIn->po_no ?? $item->po_no ?? '',
                            $item->vendor_batch ?? '',
                            $item->sap_batch ?? '',
                            $item->product->packingType->name ?? ($item->packing_snapshot ?? ''),
                            $item->pack_size_snapshot ?? '',
                            $unitsVal,
                            $qtyVal,
                            $item->mfg_date ?? '',
                            $item->expiry_date ?? '',
                            $balVal,
                            $palletsVal,
                            $item->quality_clearance ?? '',
                            $item->sound_stock ? 'Yes' : 'No',
                            $item->block_stock ? 'Yes' : 'No',
$item->hold_stock ? 'Yes' : 'No',
                                $stockIn->id,
                                $stockIn->created_at->format('d.m.Y H:i'),
                                $stockIn->source_type ?? '',
                                $stockIn->vendor->name ?? '',
                                $stockIn->arrivedFrom->name ?? '',
                                $stockIn->transporter->name ?? '',
                                $stockIn->inbound_invoice_no ?? '',
                                $stockIn->dispatched_invoice_no ?? '',
                                $stockIn->shipment_no ?? '',
                                $stockIn->sto_no ?? '',
                                $stockIn->delivery_no ?? '',
                                $stockIn->vehicle_no ?? '',
                            $stockIn->vehicle_size ?? '',
                            $stockIn->driver_name ?? '',
                            $stockIn->driver_mobile ?? '',
                            $stockIn->vehicle_in_time ?? '',
                            $stockIn->vehicle_out_time ?? '',
                            $stockIn->picker ?? '',
                            $stockIn->shipment_type ?? '',
                            $rowNameVal,
                            $item->remarks ?? '',
                            $stockIn->remarks ?? ''
                        ]);
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Outbound Report to Excel
     */
    public function outboundExport(Request $request)
    {
        $query = StockOut::with(['customer', 'warehouse', 'toWarehouse', 'transporter', 'items.product', 'items.warehouseRow']);

        // Apply same filters as outbound report
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('invoice_no')) {
            $query->where(function($q) use ($request) {
                $q->where('dispatched_invoice_no', 'like', '%' . $request->invoice_no . '%')
                  ->orWhere('delivery_no', 'like', '%' . $request->invoice_no . '%')
                  ->orWhere('gatepass_no', 'like', '%' . $request->invoice_no . '%');
            });
        }

        $stockOuts = $query->orderBy('created_at', 'desc')->get();

        // Build row-letter mapping: first row per warehouse = A, second = B, etc.
        $rowLetterMap = [];
        $allRows = \App\Models\WarehouseRow::orderBy('warehouse_id')->orderBy('row_name')->get()->groupBy('warehouse_id');
        foreach ($allRows as $whId => $rows) {
            $rows = $rows->sortBy('row_name', SORT_NATURAL | SORT_FLAG_CASE)->values();
            foreach ($rows as $i => $row) {
                $n = $i + 1;
                $letter = '';
                while ($n > 0) {
                    $n--;
                    $letter = chr(65 + $n % 26) . $letter;
                    $n = (int)($n / 26);
                }
                $rowLetterMap[$whId . '-' . $row->row_name] = $letter;
            }
        }

        // Pre-compute pallet positions across ALL outbound items for consistency
        $rowPalletOffsets = [];
        $outboundPositions = [];
        $allOutItems = \App\Models\StockOutItem::where('pallets_returned', '>', 0)
            ->whereNotNull('warehouse_row_id')
            ->with('warehouseRow')
            ->orderBy('created_at')
            ->get();
        foreach ($allOutItems as $item) {
            $whId = $item->warehouse_id;
            $row = $item->warehouseRow;
            if (!$whId || !$row || !$row->row_name) continue;
            $palletCount = (int)$item->pallets_returned;
            $rowKey = $whId . '-' . $row->row_name;
            if (!isset($rowPalletOffsets[$rowKey])) $rowPalletOffsets[$rowKey] = 0;
            $palletStart = $rowPalletOffsets[$rowKey] + 1;
            $palletEnd = $rowPalletOffsets[$rowKey] + $palletCount;
            $rowPalletOffsets[$rowKey] = $palletEnd;
            $rw = $rowLetterMap[$rowKey] ?? '';
            $wp = str_pad($whId, 2, '0', STR_PAD_LEFT);
            $outboundPositions[$item->id] = [
                'start' => $palletStart, 'end' => $palletEnd,
                'letter' => $rw, 'wh_padded' => $wp,
            ];
        }

        // Generate CSV
        $filename = 'outbound_report_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($stockOuts, $outboundPositions) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Item Code', 'Product Name', 'Warehouse', 'Category', 'UOM',
                'IBD', 'PO', 'Vendor Batch', 'SAP Batch', 'Packing', 'Pack Size',
                'Units Dispatch', 'Dispatch Qty', 'MFG Date', 'Expiry Date',
                'Balance Qty', 'Pallets Used', 'Quality Check', 'Sound', 'Blocked', 'Hold',
                'Entry ID', 'Date', 'Source Type', 'To Warehouse', 'Customer', 'Transporter',
                'Dispatched Invoice', 'Delivery No', 'Gatepass No', 'STO No', 'Shipment No',
                'Vehicle No', 'Vehicle Size', 'Driver Name', 'Driver Mobile',
                'Vehicle In Time', 'Vehicle Out Time', 'Picker',
                'Warehouse Row', 'Item Remarks', 'General Remarks'
            ]);

            // Data rows
            foreach ($stockOuts as $stockOut) {
                foreach ($stockOut->items as $item) {
                    $warehouseDisplay = $stockOut->warehouse->name ?? '';
                    $unitsVal = $item->units_dispatch ?? 0;
                    $qtyVal = $item->dispatch_quantity ?? 0;
                    $palletsVal = $item->pallets_returned ?? 0;
                    $rowNameVal = $item->warehouseRow->name ?? '';

                    $pos = $outboundPositions[$item->id] ?? null;

                    if ($pos) {
                        $whPadded = $pos['wh_padded'];
                        $rowLetter = $pos['letter'];
                        $palletStart = $pos['start'];
                        $palletEnd = $pos['end'];
                        $numPallets = $palletEnd - $palletStart + 1;

                        $maxPerPallet = $item->product->cartons_per_pallet ?? null;
                        $totalUnits = (float) $unitsVal;
                        $totalQty = (float) $qtyVal;
                        $remainingUnits = $totalUnits;
                        $assignedQty = 0.0;

                        for ($p = $palletStart; $p <= $palletEnd; $p++) {
                            $isLast = ($p == $palletEnd);
                            if ($maxPerPallet) {
                                $perPalletUnits = min($maxPerPallet, $remainingUnits);
                            } else {
                                $perPalletUnits = $numPallets > 0 ? $totalUnits / $numPallets : $totalUnits;
                            }
                            $ratio = $totalUnits > 0 ? $perPalletUnits / $totalUnits : 0;
                            $palletQty = $isLast ? $totalQty - $assignedQty : round($ratio * $totalQty, 4);
                            $remainingUnits -= $perPalletUnits;
                            $assignedQty += $palletQty;

                            $psPadded = str_pad($p, 3, '0', STR_PAD_LEFT);
                            $warehouseDisplay = "W{$whPadded}.{$rowLetter}{$psPadded}";

                            fputcsv($file, [
                                $item->product->item_code ?? '',
                                $item->product->name ?? '',
                                $warehouseDisplay,
                                $item->product->category->name ?? '',
                                $item->product->uom->name ?? '',
                                $stockOut->ibd_no ?? $item->ibd_no ?? '',
                                $stockOut->po_no ?? $item->po_no ?? '',
                                $item->vendor_batch ?? '',
                                $item->sap_batch ?? '',
                                $item->product->packingType->name ?? '',
                                $item->pack_size_snapshot ?? '',
                                $perPalletUnits,
                                $palletQty,
                                $item->mfg_date ?? '',
                                $item->expiry_date ?? '',
                                '', // Balance Qty N/A
                                1,
                                '', // Quality Check N/A
                                '', // Sound N/A
                                '', // Blocked N/A
                                '', // Hold N/A
                                $stockOut->id,
                                $stockOut->created_at->format('d.m.Y H:i'),
                                $stockOut->source_type ?? '',
                                $stockOut->toWarehouse->name ?? '',
                                $stockOut->customer->name ?? 'Transfer',
                                $stockOut->transporter->name ?? '',
                                $stockOut->dispatched_invoice_no ?? '',
                                $stockOut->delivery_no ?? '',
                                $stockOut->gatepass_no ?? '',
                                $stockOut->sto_no ?? '',
                                $stockOut->shipment_no ?? '',
                                $stockOut->vehicle_no ?? '',
                                $stockOut->vehicle_size ?? '',
                                $stockOut->driver_name ?? '',
                                $stockOut->driver_mobile ?? '',
                                $stockOut->vehicle_in_time ?? '',
                                $stockOut->vehicle_out_time ?? '',
                                $stockOut->picker ?? '',
                                $rowNameVal,
                                $item->remarks ?? '',
                                $stockOut->remarks ?? ''
                            ]);
                        }
                    } else {
                        fputcsv($file, [
                            $item->product->item_code ?? '',
                            $item->product->name ?? '',
                            $warehouseDisplay,
                            $item->product->category->name ?? '',
                            $item->product->uom->name ?? '',
                            $stockOut->ibd_no ?? $item->ibd_no ?? '',
                            $stockOut->po_no ?? $item->po_no ?? '',
                            $item->vendor_batch ?? '',
                            $item->sap_batch ?? '',
                            $item->product->packingType->name ?? '',
                            $item->pack_size_snapshot ?? '',
                            $unitsVal,
                            $qtyVal,
                            $item->mfg_date ?? '',
                            $item->expiry_date ?? '',
                            '', // Balance Qty N/A
                            $palletsVal,
                            '', // Quality Check N/A
                            '', // Sound N/A
                            '', // Blocked N/A
                            '', // Hold N/A
                            $stockOut->id,
                            $stockOut->created_at->format('d.m.Y H:i'),
                            $stockOut->source_type ?? '',
                            $stockOut->toWarehouse->name ?? '',
                            $stockOut->customer->name ?? 'Transfer',
                            $stockOut->transporter->name ?? '',
                            $stockOut->dispatched_invoice_no ?? '',
                            $stockOut->delivery_no ?? '',
                            $stockOut->gatepass_no ?? '',
                            $stockOut->sto_no ?? '',
                            $stockOut->shipment_no ?? '',
                            $stockOut->vehicle_no ?? '',
                            $stockOut->vehicle_size ?? '',
                            $stockOut->driver_name ?? '',
                            $stockOut->driver_mobile ?? '',
                            $stockOut->vehicle_in_time ?? '',
                            $stockOut->vehicle_out_time ?? '',
                            $stockOut->picker ?? '',
                            $rowNameVal,
                            $item->remarks ?? '',
                            $stockOut->remarks ?? ''
                        ]);
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate PDF for a StockIn (inbound) entry.
     */
    public function inboundPdf($stockInId)
    {
        $stockIn = \App\Models\StockIn::with([
            'vendor', 
            'warehouse', 
            'transporter', 
            'arrivedFrom',
            'items.product',
            'items.warehouseRow'
        ])->findOrFail($stockInId);

        // If PDF package available, return PDF download, otherwise return HTML view
        if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf') || class_exists('PDF')) {
            try {
                $pdf = \PDF::loadView('reports.pdf.inbound', compact('stockIn'));
                $pdf->setPaper('a4', 'portrait');
                return $pdf->download('inbound_' . $stockInId . '_' . date('Ymd') . '.pdf');
            } catch (\Throwable $e) {
                \Log::error('PDF generation failed for inbound ' . $stockInId . ': ' . $e->getMessage());
                return view('reports.pdf.inbound', compact('stockIn'));
            }
        }

        return view('reports.pdf.inbound', compact('stockIn'));
    }

    /**
     * Generate PDF for a StockOut (outbound) entry.
     */
    public function outboundPdf($stockOutId)
    {
        $stockOut = \App\Models\StockOut::with([
            'customer', 
            'warehouse', 
            'toWarehouse',
            'transporter', 
            'items.product',
            'items.warehouseRow'
        ])->findOrFail($stockOutId);

        if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf') || class_exists('PDF')) {
            try {
                $pdf = \PDF::loadView('reports.pdf.outbound', compact('stockOut'));
                $pdf->setPaper('a4', 'portrait');
                return $pdf->download('outbound_' . $stockOutId . '_' . date('Ymd') . '.pdf');
            } catch (\Throwable $e) {
                \Log::error('PDF generation failed for outbound ' . $stockOutId . ': ' . $e->getMessage());
                return view('reports.pdf.outbound', compact('stockOut'));
            }
        }

        return view('reports.pdf.outbound', compact('stockOut'));
    }

    /**
     * Get invoice suggestions for inbound search
     */
    public function inboundInvoiceSuggestions(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $suggestions = StockIn::where('source_type', 'inbound')
            ->where(function($q) use ($search) {
                $q->where('inbound_invoice_no', 'like', '%' . $search . '%')
                  ->orWhere('dispatched_invoice_no', 'like', '%' . $search . '%');
            })
            ->where(function($q) {
                $q->whereNotNull('dispatched_invoice_no')
                  ->orWhereNotNull('inbound_invoice_no');
            })
            ->select('id', 'inbound_invoice_no', 'dispatched_invoice_no', 'created_at', 'vendor_id')
            ->with('vendor:id,name')
            ->latest()
            ->limit(10)
            ->get()
            ->filter(function($stockIn) {
                return $stockIn->dispatched_invoice_no || $stockIn->inbound_invoice_no;
            })
            ->map(function($stockIn) {
                $invoice = $stockIn->dispatched_invoice_no ?? $stockIn->inbound_invoice_no;
                return [
                    'id' => $stockIn->id,
                    'invoice' => $invoice,
                    'vendor' => $stockIn->vendor->name ?? 'N/A',
                    'date' => $stockIn->created_at->format('d.m.Y'),
                    'label' => $invoice . ' - ' . ($stockIn->vendor->name ?? 'N/A') . ' (' . $stockIn->created_at->format('d.m.Y') . ')'
                ];
            })
            ->values();

        return response()->json($suggestions);
    }

    /**
     * Get invoice suggestions for outbound search
     */
    public function outboundInvoiceSuggestions(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $suggestions = StockOut::where(function($q) use ($search) {
                $q->where('dispatched_invoice_no', 'like', '%' . $search . '%')
                  ->orWhere('delivery_no', 'like', '%' . $search . '%')
                  ->orWhere('gatepass_no', 'like', '%' . $search . '%');
            })
            ->where(function($q) {
                $q->whereNotNull('dispatched_invoice_no')
                  ->orWhereNotNull('delivery_no')
                  ->orWhereNotNull('gatepass_no');
            })
            ->select('id', 'dispatched_invoice_no', 'delivery_no', 'gatepass_no', 'created_at', 'customer_id')
            ->with('customer:id,name')
            ->latest()
            ->limit(10)
            ->get()
            ->filter(function($stockOut) {
                return $stockOut->dispatched_invoice_no || $stockOut->delivery_no || $stockOut->gatepass_no;
            })
            ->map(function($stockOut) {
                $invoice = $stockOut->dispatched_invoice_no ?? $stockOut->delivery_no ?? $stockOut->gatepass_no;
                return [
                    'id' => $stockOut->id,
                    'invoice' => $invoice,
                    'customer' => $stockOut->customer->name ?? 'Transfer',
                    'date' => $stockOut->created_at->format('d.m.Y'),
                    'label' => $invoice . ' - ' . ($stockOut->customer->name ?? 'Transfer') . ' (' . $stockOut->created_at->format('d.m.Y') . ')'
                ];
            })
            ->values();

        return response()->json($suggestions);
    }

    /**
     * Warehouse Stock Report
     */
    public function warehouseStock(Request $request)
    {
        $warehouses = \App\Models\Warehouse::orderBy('name')->get();
        $products = \App\Models\Product::orderBy('name')->get();

        // Build query for detailed stock data with location, pallets, and batch info
        $query = DB::table('stock_in_items')
            ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
            ->join('products', 'stock_in_items.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_ins.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('warehouse_rows', 'stock_in_items.warehouse_row_id', '=', 'warehouse_rows.id')
            ->select(
                'stock_in_items.id as stock_in_item_id',
                'products.id as product_id',
                'products.item_code',
                'products.name as product_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                'warehouse_rows.id as row_id',
                'warehouse_rows.row_name',
                'stock_in_items.sap_batch',
                'stock_in_items.vendor_batch',
                'stock_in_items.expiry_date',
                'stock_in_items.mfg_date',
                'stock_in_items.pack_size_snapshot',
                'stock_in_items.pallets_used',
                'stock_in_items.total_quantity',
                'stock_in_items.balance_quantity',
                'stock_in_items.quality_clearance',
                'products.cartons_per_pallet'
            )
            ->where('stock_in_items.balance_quantity', '>', 0);

        // Apply warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->where('warehouses.id', $request->warehouse_id);
        }

        // Apply product filter
        if ($request->filled('product_id')) {
            $query->where('products.id', $request->product_id);
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('stock_in_items.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('stock_in_items.created_at', '<=', $request->date_to);
        }

        $stockReport = $query->orderBy('warehouses.name')
            ->orderBy('products.name')
            ->orderBy('warehouse_rows.row_name')
            ->orderBy('stock_in_items.sap_batch')
            ->get();

        // Calculate summary
        $summary = [
            'total_products' => $stockReport->unique('product_id')->count(),
            'total_warehouses' => $stockReport->unique('warehouse_id')->count(),
            'total_pallets' => $stockReport->sum(function ($item) {
                $maxPerPallet = $item->cartons_per_pallet ?? null;
                if ($maxPerPallet && $maxPerPallet > 0 && ($item->pallets_used ?? 0) > 0) {
                    $packSize = ($item->pack_size_snapshot ?? 0) > 0 ? $item->pack_size_snapshot : 1;
                    $remainingCartons = (int) ceil(($item->balance_quantity ?? 0) / $packSize);
                    $computed = (int) ceil($remainingCartons / $maxPerPallet);
                    return max(1, min($computed, $item->pallets_used));
                }
                return $item->pallets_used ?? 0;
            }),
            'total_balance' => $stockReport->sum('balance_quantity'),
        ];

        return view('reports.warehouse_stock', compact('stockReport', 'warehouses', 'products', 'summary'));
    }

    /**
     * All Stocks Report - Complete stock overview
     */
    public function allStocks(Request $request)
    {
        $warehouses = \App\Models\Warehouse::orderBy('name')->get();
        $products = \App\Models\Product::orderBy('name')->get();
        $categories = \App\Models\ProductCategory::orderBy('name')->get();

        // Base query for products
        $productQuery = \App\Models\Product::with(['category', 'uom', 'packingType']);

        // Apply product filter
        if ($request->filled('product_id')) {
            $productQuery->where('id', $request->product_id);
        }

        // Apply category filter
        if ($request->filled('category_id')) {
            $productQuery->where('product_category_id', $request->category_id);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $productQuery->where(function($q) use ($search) {
                $q->where('item_code', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $allProducts = $productQuery->orderBy('name')->get();

        // Build stock report
        $stockReport = collect();

        foreach ($allProducts as $product) {
            // Opening Stock Query
            $openingQuery = DB::table('stock_in_items')
                ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
                ->where('stock_ins.source_type', 'opening')
                ->where('stock_in_items.product_id', $product->id);

            // Inbound Stock Query
            $inboundQuery = DB::table('stock_in_items')
                ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
                ->where('stock_ins.source_type', 'inbound')
                ->where('stock_in_items.product_id', $product->id);

            // Outbound Query
            $outboundQuery = DB::table('stock_out_items')
                ->where('product_id', $product->id);

            // Balance Query
            $balanceQuery = DB::table('stock_in_items')
                ->where('product_id', $product->id);

            // Apply warehouse filter
            if ($request->filled('warehouse_id')) {
                $openingQuery->where('stock_ins.warehouse_id', $request->warehouse_id);
                $inboundQuery->where('stock_in_items.warehouse_id', $request->warehouse_id);
                $outboundQuery->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
                    ->where('stock_outs.warehouse_id', $request->warehouse_id);
                $balanceQuery->join('stock_ins as si', 'stock_in_items.stock_in_id', '=', 'si.id')
                    ->where('si.warehouse_id', $request->warehouse_id);
            }

            // Apply date range filter
            if ($request->filled('date_from')) {
                $inboundQuery->whereDate('stock_in_items.created_at', '>=', $request->date_from);
                $outboundQuery->whereDate('stock_out_items.created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $inboundQuery->whereDate('stock_in_items.created_at', '<=', $request->date_to);
                $outboundQuery->whereDate('stock_out_items.created_at', '<=', $request->date_to);
            }

            $openingStock = $openingQuery->sum('stock_in_items.total_quantity');
            $inboundStock = $inboundQuery->sum('stock_in_items.total_quantity');
            $outboundStock = $outboundQuery->sum('stock_out_items.dispatch_quantity');
            $balanceStock = $balanceQuery->sum('stock_in_items.balance_quantity');

            // Get first inbound date for stock duration
            $firstInboundDate = DB::table('stock_in_items')
                ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
                ->where('stock_ins.source_type', 'inbound')
                ->where('stock_in_items.product_id', $product->id)
                ->where('stock_in_items.balance_quantity', '>', 0)
                ->min('stock_in_items.created_at');

            // Only include if there's any stock activity
            if ($openingStock > 0 || $inboundStock > 0 || $outboundStock > 0 || $balanceStock > 0) {
                $stockReport->push([
                    'product_id' => $product->id,
                    'item_code' => $product->item_code,
                    'product_name' => $product->name,
                    'category' => $product->category->name ?? '-',
                    'uom' => $product->uom->name ?? '-',
                    'packing' => $product->packingType->name ?? '-',
                    'pack_size' => $product->pack_size,
                    'opening_stock' => $openingStock,
                    'inbound_stock' => $inboundStock,
                    'outbound_stock' => $outboundStock,
                    'balance_stock' => $balanceStock,
                    'first_inbound_date' => $firstInboundDate,
                ]);
            }
        }

        // Calculate summary
        $summary = [
            'total_products' => $stockReport->count(),
            'total_opening' => $stockReport->sum('opening_stock'),
            'total_inbound' => $stockReport->sum('inbound_stock'),
            'total_outbound' => $stockReport->sum('outbound_stock'),
            'total_balance' => $stockReport->sum('balance_stock'),
        ];

        return view('reports.all_stocks', compact('stockReport', 'warehouses', 'products', 'categories', 'summary'));
    }

    /**
     * Get stock details for a product (AJAX)
     */
    public function stockDetails($productId, Request $request)
    {
        $product = \App\Models\Product::with(['category', 'uom', 'packingType'])->findOrFail($productId);

        // Get opening stock batches with vendor, transporter, vehicle, etc.
        $openingBatches = DB::table('stock_in_items')
            ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
            ->join('warehouses', 'stock_ins.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('vendors', 'stock_ins.vendor_id', '=', 'vendors.id')
            ->leftJoin('transporters', 'stock_ins.transporter_id', '=', 'transporters.id')
            ->leftJoin('warehouse_rows', 'stock_in_items.warehouse_row_id', '=', 'warehouse_rows.id')
            ->where('stock_ins.source_type', 'opening')
            ->where('stock_in_items.product_id', $productId)
            ->select(
                'warehouses.name as warehouse_name',
                'vendors.name as vendor_name',
                'transporters.name as transporter_name',
                'stock_ins.vehicle_no',
                'stock_ins.vehicle_size',
                'stock_ins.driver_name',
                'stock_ins.driver_mobile',
                'stock_in_items.sap_batch',
                'stock_in_items.stock_in_id as stock_in_id',
                'stock_in_items.vendor_batch',
                'stock_in_items.total_quantity',
                'stock_in_items.balance_quantity',
                'stock_in_items.pack_size_snapshot',
                'stock_in_items.created_at'
            )
            ->get();

        // Get inbound batches with vendor, transporter, vehicle, etc.
        $inboundBatches = DB::table('stock_in_items')
            ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
            ->join('warehouses', 'stock_ins.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('vendors', 'stock_ins.vendor_id', '=', 'vendors.id')
            ->leftJoin('transporters', 'stock_ins.transporter_id', '=', 'transporters.id')
            ->leftJoin('warehouse_rows', 'stock_in_items.warehouse_row_id', '=', 'warehouse_rows.id')
            ->where('stock_ins.source_type', 'inbound')
            ->where('stock_in_items.product_id', $productId)
            ->select(
                'warehouses.name as warehouse_name',
                'vendors.name as vendor_name',
                'transporters.name as transporter_name',
                'stock_ins.vehicle_no',
                'stock_ins.vehicle_size',
                'stock_ins.driver_name',
                'stock_ins.driver_mobile',
                'stock_in_items.sap_batch',
                'stock_in_items.stock_in_id as stock_in_id',
                'stock_in_items.vendor_batch',
                'stock_in_items.total_quantity',
                'stock_in_items.balance_quantity',
                'stock_in_items.pack_size_snapshot',
                'stock_in_items.quality_clearance',
                'stock_in_items.created_at'
            )
            ->get();

        // Get outbound records with transporter, vehicle, etc. (no vendor on stock_outs table)
        $outboundRecords = DB::table('stock_out_items')
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->join('warehouses', 'stock_outs.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('customers', 'stock_outs.customer_id', '=', 'customers.id')
            ->leftJoin('transporters', 'stock_outs.transporter_id', '=', 'transporters.id')
            ->where('stock_out_items.product_id', $productId)
            ->select(
                'warehouses.name as warehouse_name',
                'customers.name as customer_name',
                'transporters.name as transporter_name',
                'stock_outs.vehicle_no',
                'stock_outs.vehicle_size',
                'stock_outs.driver_name',
                'stock_outs.driver_mobile',
                'stock_outs.source_type',
                'stock_out_items.stock_out_id as stock_out_id',
                'stock_out_items.dispatch_quantity',
                'stock_out_items.created_at'
            )
            ->get();

        return response()->json([
            'product' => $product,
            'opening_batches' => $openingBatches,
            'inbound_batches' => $inboundBatches,
            'outbound_records' => $outboundRecords,
        ]);
    }

    /**
     * Stock Ledger Report - Detailed batch-level transactions
     */
    public function stockLedger(Request $request)
    {
        $warehouses = \App\Models\Warehouse::orderBy('name')->get();
        $products = \App\Models\Product::with(['category', 'uom', 'packingType'])->orderBy('name')->get();
        $categories = \App\Models\ProductCategory::orderBy('name')->get();
        $vendors = \App\Models\Vendor::orderBy('name')->get();
        $customers = \App\Models\Customer::orderBy('name')->get();

        // Build ledger entries (combining inbound + outbound)
        $ledgerEntries = collect();

        // ===== INBOUND ENTRIES (Opening + Inbound) =====
        $inboundQuery = DB::table('stock_in_items')
            ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
            ->join('products', 'stock_in_items.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_in_items.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->leftJoin('uoms', 'products.uom_id', '=', 'uoms.id')
            ->leftJoin('vendors', 'stock_ins.vendor_id', '=', 'vendors.id')
            ->leftJoin('transporters', 'stock_ins.transporter_id', '=', 'transporters.id')
            ->leftJoin('warehouse_rows', 'stock_in_items.warehouse_row_id', '=', 'warehouse_rows.id')
            ->select(
                'stock_in_items.id',
                'stock_in_items.stock_in_id as transaction_id',
                'stock_in_items.created_at',
                'stock_ins.source_type',
                DB::raw("'IN' as direction"),
                'products.id as product_id',
                'products.item_code',
                'products.name as product_name',
                'product_categories.name as category_name',
                'uoms.name as uom_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                'warehouse_rows.row_name as row_name',
                DB::raw('COALESCE(stock_in_items.pallets_used, 0) as pallets_used'),
                'stock_in_items.pallet_start',
                'products.cartons_per_pallet',
                'vendors.name as vendor_name',
                'transporters.name as transporter_name',
                'stock_ins.vehicle_no',
                'stock_ins.driver_name',
                'stock_ins.inbound_invoice_no',
                'stock_ins.dispatched_invoice_no as invoice_no',
                'stock_in_items.sap_batch',
                'stock_in_items.vendor_batch',
                'stock_in_items.po_no',
                'stock_in_items.ibd_no',
                'stock_in_items.mfg_date',
                'stock_in_items.expiry_date',
                'stock_in_items.units_received as units',
                'stock_in_items.pack_size_snapshot as pack_size',
                'stock_in_items.total_quantity as quantity',
                'stock_in_items.balance_quantity',
                'stock_in_items.quality_clearance as qc_status',
                DB::raw('NULL as customer_name'),
                DB::raw('NULL as to_warehouse_name')
            );

        // Apply filters to inbound
        if ($request->filled('warehouse_id')) {
            $inboundQuery->where('stock_in_items.warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('product_id')) {
            $inboundQuery->where('stock_in_items.product_id', $request->product_id);
        }
        if ($request->filled('category_id')) {
            $inboundQuery->where('products.product_category_id', $request->category_id);
        }
        if ($request->filled('vendor_id')) {
            $inboundQuery->where('stock_ins.vendor_id', $request->vendor_id);
        }
        if ($request->filled('date_from')) {
            $inboundQuery->whereDate('stock_in_items.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $inboundQuery->whereDate('stock_in_items.created_at', '<=', $request->date_to);
        }
        if ($request->filled('source_type')) {
            $inboundQuery->where('stock_ins.source_type', $request->source_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $inboundQuery->where(function($q) use ($search) {
                $q->where('products.item_code', 'like', '%'.$search.'%')
                  ->orWhere('products.name', 'like', '%'.$search.'%')
                  ->orWhere('stock_in_items.sap_batch', 'like', '%'.$search.'%')
                  ->orWhere('stock_in_items.vendor_batch', 'like', '%'.$search.'%')
                  ->orWhere('stock_ins.inbound_invoice_no', 'like', '%'.$search.'%');
            });
        }

        $inboundData = $inboundQuery->orderBy('stock_in_items.warehouse_row_id')->orderBy('stock_in_items.id')->get();

        // Build row-letter mapping: first row per warehouse = A, second = B, etc.
        $rowLetterMap = [];
        $allRows = \App\Models\WarehouseRow::orderBy('warehouse_id')->orderBy('row_name')->get()->groupBy('warehouse_id');
        foreach ($allRows as $whId => $rows) {
            $rows = $rows->sortBy('row_name', SORT_NATURAL | SORT_FLAG_CASE)->values();
            foreach ($rows as $i => $row) {
                $n = $i + 1;
                $letter = '';
                while ($n > 0) {
                    $n--;
                    $letter = chr(65 + $n % 26) . $letter;
                    $n = (int)($n / 26);
                }
                $rowLetterMap[$whId . '-' . $row->row_name] = $letter;
            }
        }

        // Compute pallet positions per warehouse row and expand into per-pallet rows
        $rowPalletOffsets = [];
        $expandedInbound = collect();
        foreach ($inboundData as $entry) {
            $entry->pallet_end = null;
            $entry->warehouse_display = null;
            if ($entry->warehouse_id && $entry->row_name && $entry->pallets_used > 0) {
                $rowKey = $entry->warehouse_id . '-' . $entry->row_name;
                if (!isset($rowPalletOffsets[$rowKey])) {
                    $rowPalletOffsets[$rowKey] = 0;
                }
                if ($entry->pallet_start !== null) {
                    $entry->pallet_end = $entry->pallet_start + $entry->pallets_used - 1;
                    $rowPalletOffsets[$rowKey] = max($rowPalletOffsets[$rowKey], $entry->pallet_end);
                } else {
                    $entry->pallet_start = $rowPalletOffsets[$rowKey] + 1;
                    $entry->pallet_end = $rowPalletOffsets[$rowKey] + $entry->pallets_used;
                    $rowPalletOffsets[$rowKey] = $entry->pallet_end;
                }

                $mapKey = $entry->warehouse_id . '-' . $entry->row_name;
                $rowLetter = $rowLetterMap[$mapKey] ?? '';
                $whPadded = str_pad($entry->warehouse_id, 2, '0', STR_PAD_LEFT);

                // Expand: one row per pallet with sequential carton fill
                $numPallets = $entry->pallets_used;
                $maxPerPallet = $entry->cartons_per_pallet ?? null;
                $totalUnits = (float) $entry->units;
                $totalQty = (float) $entry->quantity;
                $totalBalance = (float) $entry->balance_quantity;
                $remainingUnits = $totalUnits;
                $assignedQty = 0.0;
                $assignedBalance = 0.0;

                for ($p = $entry->pallet_start; $p <= $entry->pallet_end; $p++) {
                    $clone = clone $entry;
                    $clone->pallet_start = $p;
                    $clone->pallet_end = $p;
                    $clone->pallets_used = 1;

                    if ($maxPerPallet) {
                        $perPalletUnits = min($maxPerPallet, $remainingUnits);
                    } else {
                        $perPalletUnits = $numPallets > 0 ? $entry->units / $numPallets : $entry->units;
                    }
                    $isLast = ($p == $entry->pallet_end);
                    $ratio = $totalUnits > 0 ? $perPalletUnits / $totalUnits : 0;
                    $clone->units = $perPalletUnits;
                    $clone->quantity = $isLast ? $totalQty - $assignedQty : round($ratio * $totalQty, 4);
                    $clone->balance_quantity = $isLast ? $totalBalance - $assignedBalance : round($ratio * $totalBalance, 4);
                    $remainingUnits -= $perPalletUnits;
                    $assignedQty += $clone->quantity;
                    $assignedBalance += $clone->balance_quantity;

                    $psPadded = str_pad($p, 3, '0', STR_PAD_LEFT);
                    $clone->warehouse_display = "W{$whPadded}.{$rowLetter}{$psPadded}";
                    $expandedInbound->push($clone);
                }
            } else {
                if ($entry->pallet_start !== null && $entry->warehouse_id && $entry->row_name) {
                    $mapKey = $entry->warehouse_id . '-' . $entry->row_name;
                    $rowLetter = $rowLetterMap[$mapKey] ?? '';
                    if ($rowLetter) {
                        $whPadded = str_pad($entry->warehouse_id, 2, '0', STR_PAD_LEFT);
                        $psPadded = str_pad((int)$entry->pallet_start, 3, '0', STR_PAD_LEFT);
                        $entry->warehouse_display = "W{$whPadded}.{$rowLetter}{$psPadded}";
                    }
                }
                $expandedInbound->push($entry);
            }
        }
        $inboundData = $expandedInbound;

        // ===== OUTBOUND ENTRIES =====
        $outboundQuery = DB::table('stock_out_items')
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->join('products', 'stock_out_items.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_outs.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->leftJoin('uoms', 'products.uom_id', '=', 'uoms.id')
            ->leftJoin('customers', 'stock_outs.customer_id', '=', 'customers.id')
            ->leftJoin('transporters', 'stock_outs.transporter_id', '=', 'transporters.id')
            ->leftJoin('warehouses as to_wh', 'stock_outs.to_warehouse_id', '=', 'to_wh.id')
            ->leftJoin('warehouse_rows', 'stock_out_items.warehouse_row_id', '=', 'warehouse_rows.id')
            ->select(
                'stock_out_items.id',
                'stock_out_items.stock_out_id as transaction_id',
                'stock_out_items.created_at',
                'stock_outs.source_type',
                DB::raw("'OUT' as direction"),
                'products.id as product_id',
                'products.item_code',
                'products.name as product_name',
                'product_categories.name as category_name',
                'uoms.name as uom_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                'warehouse_rows.row_name as row_name',
                'stock_out_items.stock_in_item_id',
                'stock_out_items.pallet_position',
                DB::raw('NULL as vendor_name'),
                'transporters.name as transporter_name',
                'stock_outs.vehicle_no',
                'stock_outs.driver_name',
                DB::raw('NULL as inbound_invoice_no'),
                'stock_outs.dispatched_invoice_no as invoice_no',
                'stock_out_items.sap_batch',
                'stock_out_items.vendor_batch',
                'stock_out_items.po_no',
                'stock_out_items.ibd_no',
                'stock_out_items.mfg_date',
                'stock_out_items.expiry_date',
                'stock_out_items.units_dispatch as units',
                'stock_out_items.pack_size_snapshot as pack_size',
                'stock_out_items.dispatch_quantity as quantity',
                DB::raw('0 as balance_quantity'),
                DB::raw('NULL as qc_status'),
                'customers.name as customer_name',
                'to_wh.name as to_warehouse_name'
            );

        // Apply filters to outbound
        if ($request->filled('warehouse_id')) {
            $outboundQuery->where('stock_outs.warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('product_id')) {
            $outboundQuery->where('stock_out_items.product_id', $request->product_id);
        }
        if ($request->filled('category_id')) {
            $outboundQuery->where('products.product_category_id', $request->category_id);
        }
        if ($request->filled('customer_id')) {
            $outboundQuery->where('stock_outs.customer_id', $request->customer_id);
        }
        if ($request->filled('date_from')) {
            $outboundQuery->whereDate('stock_out_items.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $outboundQuery->whereDate('stock_out_items.created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $outboundQuery->where(function($q) use ($search) {
                $q->where('products.item_code', 'like', '%'.$search.'%')
                  ->orWhere('products.name', 'like', '%'.$search.'%')
                  ->orWhere('stock_out_items.sap_batch', 'like', '%'.$search.'%')
                  ->orWhere('stock_out_items.vendor_batch', 'like', '%'.$search.'%')
                  ->orWhere('stock_outs.dispatched_invoice_no', 'like', '%'.$search.'%');
            });
        }

        // Only get outbound if not filtering specifically for inbound types
        if (!$request->filled('source_type') || !in_array($request->source_type, ['opening', 'inbound'])) {
            $outboundData = $outboundQuery->get();
        } else {
            $outboundData = collect();
        }

        // Format warehouse_display for outbound entries using source stock_in_item pallet position
        if ($outboundData->isNotEmpty()) {
            $stockInItemIds = $outboundData->pluck('stock_in_item_id')->filter()->unique();
            if ($stockInItemIds->isNotEmpty()) {
                $sourceItems = DB::table('stock_in_items')
                    ->whereIn('id', $stockInItemIds)
                    ->get()
                    ->keyBy('id');
                $warehouseRowIds = $sourceItems->pluck('warehouse_row_id')->filter()->unique();
                $allRowItems = collect();
                if ($warehouseRowIds->isNotEmpty()) {
                    $allRowItems = DB::table('stock_in_items')
                        ->whereIn('warehouse_row_id', $warehouseRowIds)
                        ->orderBy('warehouse_row_id')
                        ->orderBy('id')
                        ->get();
                }
                $palletStartMap = [];
                $currentRowId = null;
                $offset = 0;
                foreach ($allRowItems as $item) {
                    if ($item->warehouse_row_id !== $currentRowId) {
                        $currentRowId = $item->warehouse_row_id;
                        $offset = 0;
                    }
                    if ($item->pallet_start !== null) {
                        $start = (int)$item->pallet_start;
                        $offset = max($offset, $start + $item->pallets_used - 1);
                    } else {
                        $start = $offset + 1;
                        $offset = $start + $item->pallets_used - 1;
                    }
                    $palletStartMap[$item->id] = $start;
                }
                foreach ($outboundData as $entry) {
                    if ($entry->stock_in_item_id && isset($palletStartMap[$entry->stock_in_item_id])) {
                        $basePosition = $palletStartMap[$entry->stock_in_item_id];
                        $palletNum = $entry->pallet_position
                            ? $basePosition + $entry->pallet_position - 1
                            : $basePosition;
                        $rowKey = $entry->warehouse_id . '-' . $entry->row_name;
                        $rowLetter = $rowLetterMap[$rowKey] ?? '';
                        if ($rowLetter) {
                            $whPadded = str_pad($entry->warehouse_id, 2, '0', STR_PAD_LEFT);
                            $psPadded = str_pad($palletNum, 3, '0', STR_PAD_LEFT);
                            $entry->warehouse_display = "W{$whPadded}.{$rowLetter}{$psPadded}";
                        }
                    }
                }
            }
        }

        // Merge and sort by date
        $ledgerEntries = $inboundData->concat($outboundData)
            ->sortByDesc('created_at')
            ->values();

        // Paginate manually
        $page = $request->get('page', 1);
        $perPage = 50;
        $total = $ledgerEntries->count();
        $paginatedEntries = $ledgerEntries->forPage($page, $perPage);

        $ledgerPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedEntries,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Calculate summary
        $summary = [
            'total_entries' => $total,
            'total_inbound_qty' => $inboundData->sum('quantity'),
            'total_outbound_qty' => $outboundData->sum('quantity'),
            'total_balance' => $inboundData->sum('balance_quantity'),
            'unique_products' => $ledgerEntries->unique('product_id')->count(),
        ];

        return view('reports.stock_ledger', compact(
            'ledgerPaginated', 'summary', 'warehouses', 'products', 
            'categories', 'vendors', 'customers'
        ));
    }

    /**
     * Export Stock Ledger Report to CSV
     */

    /**
     * Export Stock Ledger Report to CSV
     */
    public function stockLedgerExport(Request $request)
    {
        // ===== INBOUND ENTRIES (Opening + Inbound) =====
        $inboundQuery = DB::table('stock_in_items')
            ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
            ->join('products', 'stock_in_items.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_in_items.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->leftJoin('uoms', 'products.uom_id', '=', 'uoms.id')
            ->leftJoin('vendors', 'stock_ins.vendor_id', '=', 'vendors.id')
            ->leftJoin('transporters', 'stock_ins.transporter_id', '=', 'transporters.id')
            ->leftJoin('warehouse_rows', 'stock_in_items.warehouse_row_id', '=', 'warehouse_rows.id')
            ->select(
                'stock_in_items.id',
                'stock_in_items.stock_in_id as transaction_id',
                'stock_in_items.created_at',
                'stock_ins.source_type',
                DB::raw("'IN' as direction"),
                'products.id as product_id',
                'products.item_code',
                'products.name as product_name',
                'product_categories.name as category_name',
                'uoms.name as uom_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                'warehouse_rows.row_name as row_name',
                DB::raw('COALESCE(stock_in_items.pallets_used, 0) as pallets_used'),
                'stock_in_items.pallet_start',
                'products.cartons_per_pallet',
                'vendors.name as vendor_name',
                'transporters.name as transporter_name',
                'stock_ins.vehicle_no',
                'stock_ins.driver_name',
                'stock_ins.inbound_invoice_no',
                'stock_ins.dispatched_invoice_no as invoice_no',
                'stock_in_items.sap_batch',
                'stock_in_items.vendor_batch',
                'stock_in_items.po_no',
                'stock_in_items.ibd_no',
                'stock_in_items.mfg_date',
                'stock_in_items.expiry_date',
                'stock_in_items.units_received as units',
                'stock_in_items.pack_size_snapshot as pack_size',
                'stock_in_items.total_quantity as quantity',
                'stock_in_items.balance_quantity',
                'stock_in_items.quality_clearance as qc_status',
                DB::raw('NULL as customer_name'),
                DB::raw('NULL as to_warehouse_name')
            );

        // Apply filters to inbound
        if ($request->filled('warehouse_id')) {
            $inboundQuery->where('stock_in_items.warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('product_id')) {
            $inboundQuery->where('stock_in_items.product_id', $request->product_id);
        }
        if ($request->filled('category_id')) {
            $inboundQuery->where('products.product_category_id', $request->category_id);
        }
        if ($request->filled('vendor_id')) {
            $inboundQuery->where('stock_ins.vendor_id', $request->vendor_id);
        }
        if ($request->filled('date_from')) {
            $inboundQuery->whereDate('stock_in_items.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $inboundQuery->whereDate('stock_in_items.created_at', '<=', $request->date_to);
        }
        if ($request->filled('source_type')) {
            $inboundQuery->where('stock_ins.source_type', $request->source_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $inboundQuery->where(function($q) use ($search) {
                $q->where('products.item_code', 'like', '%'.$search.'%')
                  ->orWhere('products.name', 'like', '%'.$search.'%')
                  ->orWhere('stock_in_items.sap_batch', 'like', '%'.$search.'%')
                  ->orWhere('stock_in_items.vendor_batch', 'like', '%'.$search.'%')
                  ->orWhere('stock_ins.inbound_invoice_no', 'like', '%'.$search.'%');
            });
        }

        $inboundData = $inboundQuery->orderBy('stock_in_items.warehouse_row_id')->orderBy('stock_in_items.id')->get();

        // Build row-letter mapping: first row per warehouse = A, second = B, etc.
        $rowLetterMap = [];
        $allRows = \App\Models\WarehouseRow::orderBy('warehouse_id')->orderBy('row_name')->get()->groupBy('warehouse_id');
        foreach ($allRows as $whId => $rows) {
            $rows = $rows->sortBy('row_name', SORT_NATURAL | SORT_FLAG_CASE)->values();
            foreach ($rows as $i => $row) {
                $n = $i + 1;
                $letter = '';
                while ($n > 0) {
                    $n--;
                    $letter = chr(65 + $n % 26) . $letter;
                    $n = (int)($n / 26);
                }
                $rowLetterMap[$whId . '-' . $row->row_name] = $letter;
            }
        }

        // Compute pallet positions per warehouse row and expand into per-pallet rows
        $rowPalletOffsets = [];
        $expandedInbound = collect();
        foreach ($inboundData as $entry) {
            $entry->pallet_end = null;
            $entry->warehouse_display = null;
            if ($entry->warehouse_id && $entry->row_name && $entry->pallets_used > 0) {
                $rowKey = $entry->warehouse_id . '-' . $entry->row_name;
                if (!isset($rowPalletOffsets[$rowKey])) {
                    $rowPalletOffsets[$rowKey] = 0;
                }
                if ($entry->pallet_start !== null) {
                    $entry->pallet_end = $entry->pallet_start + $entry->pallets_used - 1;
                    $rowPalletOffsets[$rowKey] = max($rowPalletOffsets[$rowKey], $entry->pallet_end);
                } else {
                    $entry->pallet_start = $rowPalletOffsets[$rowKey] + 1;
                    $entry->pallet_end = $rowPalletOffsets[$rowKey] + $entry->pallets_used;
                    $rowPalletOffsets[$rowKey] = $entry->pallet_end;
                }

                $mapKey = $entry->warehouse_id . '-' . $entry->row_name;
                $rowLetter = $rowLetterMap[$mapKey] ?? '';
                $whPadded = str_pad($entry->warehouse_id, 2, '0', STR_PAD_LEFT);

                // Expand: one row per pallet with sequential carton fill
                $numPallets = $entry->pallets_used;
                $maxPerPallet = $entry->cartons_per_pallet ?? null;
                $totalUnits = (float) $entry->units;
                $totalQty = (float) $entry->quantity;
                $totalBalance = (float) $entry->balance_quantity;
                $remainingUnits = $totalUnits;
                $assignedQty = 0.0;
                $assignedBalance = 0.0;

                for ($p = $entry->pallet_start; $p <= $entry->pallet_end; $p++) {
                    $clone = clone $entry;
                    $clone->pallet_start = $p;
                    $clone->pallet_end = $p;
                    $clone->pallets_used = 1;

                    if ($maxPerPallet) {
                        $perPalletUnits = min($maxPerPallet, $remainingUnits);
                    } else {
                        $perPalletUnits = $numPallets > 0 ? $entry->units / $numPallets : $entry->units;
                    }
                    $isLast = ($p == $entry->pallet_end);
                    $ratio = $totalUnits > 0 ? $perPalletUnits / $totalUnits : 0;
                    $clone->units = $perPalletUnits;
                    $clone->quantity = $isLast ? $totalQty - $assignedQty : round($ratio * $totalQty, 4);
                    $clone->balance_quantity = $isLast ? $totalBalance - $assignedBalance : round($ratio * $totalBalance, 4);
                    $remainingUnits -= $perPalletUnits;
                    $assignedQty += $clone->quantity;
                    $assignedBalance += $clone->balance_quantity;

                    $psPadded = str_pad($p, 3, '0', STR_PAD_LEFT);
                    $clone->warehouse_display = "W{$whPadded}.{$rowLetter}{$psPadded}";
                    $expandedInbound->push($clone);
                }
            } else {
                if ($entry->pallet_start !== null && $entry->warehouse_id && $entry->row_name) {
                    $mapKey = $entry->warehouse_id . '-' . $entry->row_name;
                    $rowLetter = $rowLetterMap[$mapKey] ?? '';
                    if ($rowLetter) {
                        $whPadded = str_pad($entry->warehouse_id, 2, '0', STR_PAD_LEFT);
                        $psPadded = str_pad((int)$entry->pallet_start, 3, '0', STR_PAD_LEFT);
                        $entry->warehouse_display = "W{$whPadded}.{$rowLetter}{$psPadded}";
                    }
                }
                $expandedInbound->push($entry);
            }
        }
        $inboundData = $expandedInbound;

        // ===== OUTBOUND ENTRIES =====
        $outboundQuery = DB::table('stock_out_items')
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->join('products', 'stock_out_items.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_outs.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->leftJoin('uoms', 'products.uom_id', '=', 'uoms.id')
            ->leftJoin('customers', 'stock_outs.customer_id', '=', 'customers.id')
            ->leftJoin('transporters', 'stock_outs.transporter_id', '=', 'transporters.id')
            ->leftJoin('warehouses as to_wh', 'stock_outs.to_warehouse_id', '=', 'to_wh.id')
            ->leftJoin('warehouse_rows', 'stock_out_items.warehouse_row_id', '=', 'warehouse_rows.id')
            ->select(
                'stock_out_items.id',
                'stock_out_items.stock_out_id as transaction_id',
                'stock_out_items.created_at',
                'stock_outs.source_type',
                DB::raw("'OUT' as direction"),
                'products.id as product_id',
                'products.item_code',
                'products.name as product_name',
                'product_categories.name as category_name',
                'uoms.name as uom_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                'warehouse_rows.row_name as row_name',
                'stock_out_items.stock_in_item_id',
                'stock_out_items.pallet_position',
                DB::raw('NULL as vendor_name'),
                'transporters.name as transporter_name',
                'stock_outs.vehicle_no',
                'stock_outs.driver_name',
                DB::raw('NULL as inbound_invoice_no'),
                'stock_outs.dispatched_invoice_no as invoice_no',
                'stock_out_items.sap_batch',
                'stock_out_items.vendor_batch',
                'stock_out_items.po_no',
                'stock_out_items.ibd_no',
                'stock_out_items.mfg_date',
                'stock_out_items.expiry_date',
                'stock_out_items.units_dispatch as units',
                'stock_out_items.pack_size_snapshot as pack_size',
                'stock_out_items.dispatch_quantity as quantity',
                DB::raw('0 as balance_quantity'),
                DB::raw('NULL as qc_status'),
                'customers.name as customer_name',
                'to_wh.name as to_warehouse_name'
            );

        // Apply filters to outbound
        if ($request->filled('warehouse_id')) {
            $outboundQuery->where('stock_outs.warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('product_id')) {
            $outboundQuery->where('stock_out_items.product_id', $request->product_id);
        }
        if ($request->filled('category_id')) {
            $outboundQuery->where('products.product_category_id', $request->category_id);
        }
        if ($request->filled('customer_id')) {
            $outboundQuery->where('stock_outs.customer_id', $request->customer_id);
        }
        if ($request->filled('date_from')) {
            $outboundQuery->whereDate('stock_out_items.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $outboundQuery->whereDate('stock_out_items.created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $outboundQuery->where(function($q) use ($search) {
                $q->where('products.item_code', 'like', '%'.$search.'%')
                  ->orWhere('products.name', 'like', '%'.$search.'%')
                  ->orWhere('stock_out_items.sap_batch', 'like', '%'.$search.'%')
                  ->orWhere('stock_out_items.vendor_batch', 'like', '%'.$search.'%')
                  ->orWhere('stock_outs.dispatched_invoice_no', 'like', '%'.$search.'%');
            });
        }

        // Only get outbound if not filtering specifically for inbound types
        if (!$request->filled('source_type') || !in_array($request->source_type, ['opening', 'inbound'])) {
            $outboundData = $outboundQuery->get();
        } else {
            $outboundData = collect();
        }

        // Format warehouse_display for outbound entries using source stock_in_item pallet position
        if ($outboundData->isNotEmpty()) {
            $stockInItemIds = $outboundData->pluck('stock_in_item_id')->filter()->unique();
            if ($stockInItemIds->isNotEmpty()) {
                $sourceItems = DB::table('stock_in_items')
                    ->whereIn('id', $stockInItemIds)
                    ->get()
                    ->keyBy('id');
                $warehouseRowIds = $sourceItems->pluck('warehouse_row_id')->filter()->unique();
                $allRowItems = collect();
                if ($warehouseRowIds->isNotEmpty()) {
                    $allRowItems = DB::table('stock_in_items')
                        ->whereIn('warehouse_row_id', $warehouseRowIds)
                        ->orderBy('warehouse_row_id')
                        ->orderBy('id')
                        ->get();
                }
                $palletStartMap = [];
                $currentRowId = null;
                $offset = 0;
                foreach ($allRowItems as $item) {
                    if ($item->warehouse_row_id !== $currentRowId) {
                        $currentRowId = $item->warehouse_row_id;
                        $offset = 0;
                    }
                    if ($item->pallet_start !== null) {
                        $start = (int)$item->pallet_start;
                        $offset = max($offset, $start + $item->pallets_used - 1);
                    } else {
                        $start = $offset + 1;
                        $offset = $start + $item->pallets_used - 1;
                    }
                    $palletStartMap[$item->id] = $start;
                }
                foreach ($outboundData as $entry) {
                    if ($entry->stock_in_item_id && isset($palletStartMap[$entry->stock_in_item_id])) {
                        $basePosition = $palletStartMap[$entry->stock_in_item_id];
                        $palletNum = $entry->pallet_position
                            ? $basePosition + $entry->pallet_position - 1
                            : $basePosition;
                        $rowKey = $entry->warehouse_id . '-' . $entry->row_name;
                        $rowLetter = $rowLetterMap[$rowKey] ?? '';
                        if ($rowLetter) {
                            $whPadded = str_pad($entry->warehouse_id, 2, '0', STR_PAD_LEFT);
                            $psPadded = str_pad($palletNum, 3, '0', STR_PAD_LEFT);
                            $entry->warehouse_display = "W{$whPadded}.{$rowLetter}{$psPadded}";
                        }
                    }
                }
            }
        }

        // Merge and sort by date
        $ledgerEntries = $inboundData->concat($outboundData)
            ->sortByDesc('created_at')
            ->values();

        $filename = 'stock_ledger_report_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($ledgerEntries) {
            $file = fopen('php://output', 'w');
            
            // Header row required by user
            fputcsv($file, [
                'Date',
                'Type (Inbound / Outbound)',
                'Product',
                'Warehouse',
                'Batch',
                'Invoice / Reference',
                'Party',
                'IN Quantity',
                'OUT Quantity',
                'Balance',
                'Expiry Date'
            ]);

            // Data rows
            foreach ($ledgerEntries as $entry) {
                // Determine party
                $party = '-';
                if ($entry->direction === 'IN' && $entry->vendor_name) {
                    $party = $entry->vendor_name;
                } elseif ($entry->direction === 'OUT') {
                    $party = $entry->customer_name ?? $entry->to_warehouse_name ?? '-';
                }

                // Format batch
                $batch = '-';
                if ($entry->sap_batch) {
                    $batch = $entry->sap_batch;
                } elseif ($entry->vendor_batch) {
                    $batch = $entry->vendor_batch;
                }

                $productDisplay = ($entry->item_code ? $entry->item_code . ' - ' : '') . $entry->product_name;

                $warehouseDisplay = !empty($entry->warehouse_display) ? $entry->warehouse_display : (!empty($entry->row_name) ? $entry->row_name : '-');

                fputcsv($file, [
                        \Carbon\Carbon::parse($entry->created_at)->format('d.m.Y H:i'),
                    strtoupper($entry->source_type ?? $entry->direction),
                    $productDisplay,
                    $warehouseDisplay,
                    $batch,
                    $entry->invoice_no ?? '-',
                    $party,
                    $entry->direction === 'IN' ? ($entry->quantity ?? 0) : '-',
                    $entry->direction === 'OUT' ? ($entry->quantity ?? 0) : '-',
                    $entry->balance_quantity ?? '-',
                    $entry->expiry_date ? \Carbon\Carbon::parse($entry->expiry_date)->format('d.m.Y') : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Warehouse Capacity & Pallets Management Report
     */
    public function warehouseCapacity(Request $request)
    {
        // Get all active warehouses with their rows
        $warehouses = Warehouse::with(['rows'])->where('status', 1)->get();

        $reportData = [];

        foreach ($warehouses as $wh) {
            // Get total occupied pallets in this warehouse
            $whUsedPallets = \App\Models\StockInItem::with('product')
                ->where('warehouse_id', $wh->id)
                ->where('balance_quantity', '>', 0)
                ->get()
                ->sum(fn($i) => \App\Models\StockInItem::computeActivePallets($i));

            // Get used pallets grouped by row
            $rowUsage = \App\Services\WarehouseRowFifo::usedPalletsPerRow($wh->id);

            $rowsData = [];
            $totalRowCapacity = 0;

            foreach ($wh->rows as $row) {
                $used = $rowUsage[$row->id] ?? 0;
                $capacity = $row->pallet_capacity ?? 0;
                $free = max(0, $capacity - $used);
                $percent = $capacity > 0 ? round(($used / $capacity) * 100) : 0;

                $totalRowCapacity += $capacity;

                $rowsData[] = [
                    'row_name' => $row->row_name,
                    'capacity' => $capacity,
                    'used' => $used,
                    'free' => $free,
                    'percent' => $percent,
                ];
            }

            // Unassigned pallets (pallets sitting in the warehouse but not assigned to any row)
            $assignedPallets = array_sum($rowUsage);
            $unassignedPallets = max(0, $whUsedPallets - $assignedPallets);

            // Calculate overall percentages
            $whCapacity = $wh->total_capacity ?: $totalRowCapacity; // Use explicit total capacity or sum of rows
            $whFree = max(0, $whCapacity - $whUsedPallets);
            $whPercent = $whCapacity > 0 ? round(($whUsedPallets / $whCapacity) * 100) : 0;

            $reportData[] = [
                'warehouse' => $wh,
                'total_capacity' => $whCapacity,
                'total_used' => $whUsedPallets,
                'total_free' => $whFree,
                'percent' => $whPercent,
                'unassigned' => $unassignedPallets,
                'rows' => $rowsData
            ];
        }

        return view('reports.warehouse_capacity', compact('reportData'));
    }
}

