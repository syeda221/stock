<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockInItem;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
{
    private function formatLocationPalletDisplay($warehouse, $warehouseRow, $palletStart, $palletsCount = 1)
    {
        $whName = $warehouse->name ?? 'WH';
        if (!$warehouseRow) {
            return $whName;
        }

        $rowName = $warehouseRow->row_name;
        $pStart = (int) ($palletStart ?: 1);
        $pEnd = $pStart + max(1, (int)$palletsCount) - 1;

        $match = [];
        if (preg_match('/^(.+?)(\d+)\s+to\s+/i', $rowName, $match)) {
            $prefix = $match[1];
            $padLen = strlen($match[2]);
            $rowStartNum = (int) $match[2];

            $actualStartNum = $rowStartNum + $pStart - 1;
            $actualEndNum = $rowStartNum + $pEnd - 1;

            $startPadded = $prefix . str_pad($actualStartNum, $padLen, '0', STR_PAD_LEFT);
            $endPadded = $prefix . str_pad($actualEndNum, $padLen, '0', STR_PAD_LEFT);

            if ($actualStartNum === $actualEndNum) {
                return "{$whName} ({$startPadded})";
            } else {
                return "{$whName} ({$startPadded} - {$endPadded})";
            }
        }

        return "{$whName} ({$rowName} - P{$pStart})";
    }

    /**
     * Display stock relocation & transfer management page
     */
    public function index(Request $request)
    {
        $products = Product::with('category')->orderBy('name', 'asc')->get();
        $warehouses = Warehouse::where('status', 1)->with('rows')->orderBy('name', 'asc')->get();
        
        $transfersQuery = StockTransfer::with([
            'product', 'fromWarehouse', 'fromWarehouseRow', 
            'toWarehouse', 'toWarehouseRow', 'user'
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $transfersQuery->where(function($q) use ($search) {
                $q->where('transfer_no', 'like', "%{$search}%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$search}%")->orWhere('item_code', 'like', "%{$search}%"))
                  ->orWhere('from_location_display', 'like', "%{$search}%")
                  ->orWhere('to_location_display', 'like', "%{$search}%");
            });
        }

        if ($request->filled('product_id')) {
            $transfersQuery->where('product_id', $request->product_id);
        }

        $transfers = $transfersQuery->latest()->paginate(15);

        return view('transfers.index', compact('products', 'warehouses', 'transfers'));
    }

    /**
     * AJAX endpoint: Get all current stock locations for a specific product
     */
    public function getProductLocations(Product $product)
    {
        $batches = StockInItem::with(['warehouse', 'warehouseRow', 'stockIn', 'product'])
            ->where('product_id', $product->id)
            ->where('balance_quantity', '>', 0)
            ->orderBy('warehouse_id')
            ->orderBy('id', 'asc')
            ->get();

        // Build row-letter mapping for precise pallet display
        $rowLetterMap = [];
        $allRows = WarehouseRow::orderBy('warehouse_id')->orderBy('row_name')->get()->groupBy('warehouse_id');
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

        $locations = $batches->map(function ($item) use ($rowLetterMap) {
            $packSize = (float) ($item->pack_size_snapshot ?: ($item->product->pack_size ?? 1));
            $unitsAvailable = $packSize > 0 ? floor($item->balance_quantity / $packSize) : 0;
            
            // Location string calculation
            $whId = $item->warehouse_id;
            $whName = $item->warehouse->name ?? "WH-{$whId}";
            $rowName = $item->warehouseRow->row_name ?? 'Unassigned Row';
            
            $whPadded = str_pad($whId, 2, '0', STR_PAD_LEFT);
            $pStart = (int) ($item->pallet_start ?? 1);
            $psPadded = str_pad($pStart, 3, '0', STR_PAD_LEFT);
            $rowKey = $whId . '-' . $rowName;
            $letter = $rowLetterMap[$rowKey] ?? '';

            if ($letter) {
                $cleanName = (strpos($rowName, '.') !== false) ? explode('.', $rowName)[0] : "W{$whPadded}";
                $locationDisplay = "{$cleanName}.{$letter}{$psPadded}";
            } else {
                $cleanRow = trim(preg_split('/ to /i', $rowName)[0]);
                $locationDisplay = "{$whName} - {$cleanRow}";
            }

            return [
                'batch_id' => $item->id,
                'warehouse_id' => $whId,
                'warehouse_name' => $whName,
                'warehouse_row_id' => $item->warehouse_row_id,
                'row_name' => $rowName,
                'location_display' => $locationDisplay,
                'balance_quantity' => (float) $item->balance_quantity,
                'units_available' => (int) $unitsAvailable,
                'pack_size' => $packSize,
                'sap_batch' => $item->sap_batch ?: '-',
                'vendor_batch' => $item->vendor_batch ?: '-',
                'mfg_date' => $item->mfg_date ? $item->mfg_date->format('d.m.Y') : '-',
                'expiry_date' => $item->expiry_date ? $item->expiry_date->format('d.m.Y') : '-',
                'quality_clearance' => $item->quality_clearance ?: 'pending',
                'cartons_per_pallet' => max(1, (int) ($item->product->cartons_per_pallet ?? 1)),
                'stock_in_id' => $item->stock_in_id,
            ];
        });

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'item_code' => $product->item_code,
                'pack_size' => $product->pack_size,
                'cartons_per_pallet' => max(1, (int) ($product->cartons_per_pallet ?? 1)),
            ],
            'locations' => $locations,
            'total_locations' => $locations->count(),
            'total_cartons' => $locations->sum('balance_quantity'),
            'total_units' => $locations->sum('units_available'),
        ]);
    }

    /**
     * Store a new stock transfer / location relocation
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'stock_in_item_id' => 'required|exists:stock_in_items,id',
            'transfer_units' => 'required|integer|min:1',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_row_id' => 'nullable|exists:warehouse_rows,id',
            'to_pallet_start' => 'nullable|integer|min:1',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $transfer = DB::transaction(function () use ($request) {
                // Lock source batch
                $sourceBatch = StockInItem::with(['warehouse', 'warehouseRow', 'product'])->lockForUpdate()->findOrFail($request->stock_in_item_id);
                
                $product = $sourceBatch->product;
                $packSize = (float) ($sourceBatch->pack_size_snapshot ?: ($product->pack_size ?? 1));
                $requestedUnits = (int) $request->transfer_units;
                $requestedQty = round($requestedUnits * $packSize, 4);

                $availableUnits = $packSize > 0 ? floor($sourceBatch->balance_quantity / $packSize) : 0;

                if ($requestedUnits > $availableUnits) {
                    throw new \Exception("Cannot transfer {$requestedUnits} units. Only {$availableUnits} units available in source location.");
                }

                // Calculate exact source location display (e.g. Warehouse 01 (C010))
                $fromLocationDisplay = $this->formatLocationPalletDisplay(
                    $sourceBatch->warehouse,
                    $sourceBatch->warehouseRow,
                    $sourceBatch->pallet_start,
                    $sourceBatch->pallets_used
                );

                $cartonsPerPallet = max(1, (int) ($product->cartons_per_pallet ?? 1));
                $palletsNeeded = max(1, (int) ceil($requestedUnits / $cartonsPerPallet));
                $toPalletStart = max(1, (int) ($request->to_pallet_start ?: 1));

                // Use WarehouseRowFifo to assign pallets strictly within row capacities and split into next rows if needed
                $splits = \App\Services\WarehouseRowFifo::assign(
                    $request->to_warehouse_id,
                    $palletsNeeded,
                    $requestedUnits,
                    $packSize,
                    true,
                    $cartonsPerPallet,
                    $request->to_warehouse_row_id,
                    $toPalletStart
                );

                // 1. Decrement source batch balance
                $sourceBatch->decrement('balance_quantity', $requestedQty);

                // 2. Create Destination Items for each split
                $destLocationDisplays = [];
                foreach ($splits as $split) {
                    $splitWh = Warehouse::find($split['warehouse_id']);
                    $splitRow = $split['warehouse_row_id'] ? WarehouseRow::find($split['warehouse_row_id']) : null;
                    
                    $splitLocationName = $this->formatLocationPalletDisplay(
                        $splitWh,
                        $splitRow,
                        $split['pallet_start'],
                        $split['pallets']
                    );
                    $destLocationDisplays[] = $splitLocationName;

                    StockInItem::create([
                        'stock_in_id' => $sourceBatch->stock_in_id,
                        'warehouse_id' => $split['warehouse_id'],
                        'warehouse_row_id' => $split['warehouse_row_id'],
                        'pallet_start' => $split['pallet_start'],
                        'pallets_used' => $split['pallets'],
                        'product_id' => $sourceBatch->product_id,
                        'vendor_id' => $sourceBatch->vendor_id,
                        'units_received' => $split['units'],
                        'total_quantity' => $split['qty'],
                        'balance_quantity' => $split['qty'],
                        'vendor_batch' => $sourceBatch->vendor_batch,
                        'sap_batch' => $sourceBatch->sap_batch,
                        'po_no' => $sourceBatch->po_no,
                        'ibd_no' => $sourceBatch->ibd_no,
                        'mfg_date' => $sourceBatch->mfg_date,
                        'expiry_date' => $sourceBatch->expiry_date,
                        'sound_stock' => $sourceBatch->sound_stock,
                        'block_stock' => $sourceBatch->block_stock,
                        'hold_stock' => $sourceBatch->hold_stock,
                        'quality_clearance' => $sourceBatch->quality_clearance,
                        'qc_remarks' => $sourceBatch->qc_remarks,
                        'allow_expired_sale' => $sourceBatch->allow_expired_sale,
                        'pack_size_snapshot' => $sourceBatch->pack_size_snapshot,
                        'packing_snapshot' => $sourceBatch->packing_snapshot,
                        'uom_snapshot' => $sourceBatch->uom_snapshot,
                        'remarks' => "Relocated from {$fromLocationDisplay}. " . ($request->remarks ?? ''),
                    ]);
                }

                $toLocationDisplay = implode(' + ', array_unique($destLocationDisplays));

                // 3. Generate Transfer Serial No
                $todayStr = date('Ymd');
                $countToday = StockTransfer::whereDate('created_at', now()->toDateString())->count() + 1;
                $transferNo = 'TRF-' . $todayStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

                // 4. Create StockTransfer Audit Log
                $transferLog = StockTransfer::create([
                    'transfer_no' => $transferNo,
                    'product_id' => $sourceBatch->product_id,
                    'stock_in_item_id' => $sourceBatch->id,
                    'from_warehouse_id' => $sourceBatch->warehouse_id,
                    'from_warehouse_row_id' => $sourceBatch->warehouse_row_id,
                    'from_location_display' => $fromLocationDisplay,
                    'to_warehouse_id' => $request->to_warehouse_id,
                    'to_warehouse_row_id' => $request->to_warehouse_row_id,
                    'to_location_display' => $toLocationDisplay,
                    'quantity' => $requestedQty,
                    'units' => $requestedUnits,
                    'transfer_date' => now(),
                    'user_id' => Auth::id(),
                    'remarks' => $request->remarks,
                ]);

                return $transferLog;
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Stock successfully transferred ({$transfer->units} units) to {$transfer->to_location_display}.",
                    'transfer' => $transfer
                ]);
            }

            return redirect()->route('stock-transfers.index')
                ->with('success', "Stock successfully transferred ({$transfer->units} units) to {$transfer->to_location_display}.");

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
