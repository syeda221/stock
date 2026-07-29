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
     * Get the pallet code label for a specific pallet offset in a row
     */
    private function getPalletCode($rowName, $palletOffset)
    {
        $parts = preg_split('/ to /i', $rowName);
        $firstPallet = trim($parts[0]);
        if (preg_match('/^(.*?)(\d+)$/', $firstPallet, $m)) {
            $prefix = $m[1];
            $startNum = (int)$m[2];
            $digits = strlen($m[2]);
            return $prefix . str_pad($startNum + $palletOffset, $digits, '0', STR_PAD_LEFT);
        }
        return $rowName . '-P' . ($palletOffset + 1);
    }

    /**
     * Display stock transfer wizard (no history here)
     */
    public function index(Request $request)
    {
        $products   = Product::with('category')->orderBy('name', 'asc')->get();
        $warehouses = Warehouse::where('status', 1)->with('rows')->orderBy('name', 'asc')->get();
        return view('transfers.index', compact('products', 'warehouses'));
    }

    /**
     * Transfer log – separate page
     */
    public function log(Request $request)
    {
        $products = Product::orderBy('name')->get();
        $query    = StockTransfer::with(['product', 'fromWarehouse', 'toWarehouse', 'user']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('transfer_no', 'like', "%{$s}%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$s}%")->orWhere('item_code', 'like', "%{$s}%"))
                  ->orWhere('from_location_display', 'like', "%{$s}%")
                  ->orWhere('to_location_display', 'like', "%{$s}%");
            });
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $transfers = $query->latest()->paginate(20);
        return view('transfers.log', compact('transfers', 'products'));
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

        $locations = $batches->map(function ($item) {
            $packSize = (float) ($item->pack_size_snapshot ?: ($item->product->pack_size ?? 1));
            $unitsAvailable = $packSize > 0 ? floor($item->balance_quantity / $packSize) : 0;
            $whName = $item->warehouse->name ?? "WH-{$item->warehouse_id}";
            $rowName = $item->warehouseRow->row_name ?? 'Unassigned';
            $rowCapacity = $item->warehouseRow->pallet_capacity ?? 0;
            $palletsUsed = $item->pallets_used ?? 0;
            $palletStart = (int)($item->pallet_start ?? 1);

            // Build individual pallet breakdown
            $palletBreakdown = [];
            $maxPerPallet = (int)($item->product->cartons_per_pallet ?? 0);
            if ($maxPerPallet > 0 && $palletsUsed > 0) {
                $palletBalances = $item->getPalletBalances();
                foreach ($palletBalances as $pIdx => $pQty) {
                    $palletCode = $this->getPalletCode($rowName, $palletStart - 1 + $pIdx);
                    $units = $packSize > 0 ? floor($pQty / $packSize) : 0;
                    $palletBreakdown[] = [
                        'pallet_code' => $palletCode,
                        'pallet_index' => $pIdx,
                        'qty' => round($pQty, 2),
                        'units' => $units,
                    ];
                }
            } else {
                $palletBreakdown[] = [
                    'pallet_code' => $this->getPalletCode($rowName, $palletStart - 1),
                    'pallet_index' => 0,
                    'qty' => round((float)$item->balance_quantity, 2),
                    'units' => $unitsAvailable,
                ];
            }

            // Compute first & last pallet name
            $firstPallet = $palletBreakdown[0]['pallet_code'] ?? $rowName;
            $lastPallet  = count($palletBreakdown) > 1 ? end($palletBreakdown)['pallet_code'] : $firstPallet;
            $palletRange = $firstPallet === $lastPallet ? $firstPallet : "{$firstPallet} to {$lastPallet}";

            return [
                'batch_id'          => $item->id,
                'warehouse_id'      => $item->warehouse_id,
                'warehouse_name'    => $whName,
                'warehouse_row_id'  => $item->warehouse_row_id,
                'row_name'          => $rowName,
                'row_capacity'      => $rowCapacity,
                'pallet_start'      => $palletStart,
                'pallets_used'      => $palletsUsed,
                'pallet_range'      => $palletRange,
                'pallet_breakdown'  => $palletBreakdown,
                'balance_quantity'  => (float) $item->balance_quantity,
                'units_available'   => (int) $unitsAvailable,
                'pack_size'         => $packSize,
                'sap_batch'         => $item->sap_batch ?: '-',
                'vendor_batch'      => $item->vendor_batch ?: '-',
                'po_no'             => $item->po_no ?: '-',
                'ibd_no'            => $item->ibd_no ?: '-',
                'mfg_date'          => $item->mfg_date ? $item->mfg_date->format('d M Y') : '-',
                'expiry_date'       => $item->expiry_date ? $item->expiry_date->format('d M Y') : '-',
                'quality_clearance' => $item->quality_clearance ?: 'pending',
                'cartons_per_pallet'=> max(1, (int) ($item->product->cartons_per_pallet ?? 1)),
                'stock_in_id'       => $item->stock_in_id,
            ];
        });

        return response()->json([
            'success'         => true,
            'product'         => [
                'id'              => $product->id,
                'name'            => $product->name,
                'item_code'       => $product->item_code,
                'pack_size'       => $product->pack_size,
                'cartons_per_pallet' => max(1, (int) ($product->cartons_per_pallet ?? 1)),
            ],
            'locations'       => $locations,
            'total_locations' => $locations->count(),
            'total_qty'       => $locations->sum('balance_quantity'),
            'total_units'     => $locations->sum('units_available'),
        ]);
    }

    /**
     * AJAX: Get available (free/partially-free) pallet spaces across warehouses
     * Returns sorted list: Warehouse 01 first, row A first, pallets from beginning
     */
    public function getAvailableSpaces(Request $request)
    {
        $totalUnitsNeeded = max(1, (int)$request->units);
        $productId        = (int)$request->product_id;
        $excludeBatchIds  = array_filter(explode(',', $request->exclude_batch_ids ?? ''));
        
        $product = Product::find($productId);
        $cartonsPerPallet = max(1, (int)($product->cartons_per_pallet ?? 1));
        $packSize = (float)($product->pack_size ?? 1);
        $palletsNeeded = max(1, (int)ceil($totalUnitsNeeded / $cartonsPerPallet));

        // Get all warehouses and their rows (sorted alphabetically / numerically)
        $warehouses = Warehouse::where('status', 1)
            ->with(['rows' => function($q) { $q->orderByRaw("CAST(REGEXP_REPLACE(row_name, '[^0-9]', '') AS UNSIGNED), row_name"); }])
            ->orderBy('name')
            ->get();

        // Get occupied pallet positions per row
        $occupiedByRow = StockInItem::where('balance_quantity', '>', 0)
            ->whereNotIn('id', $excludeBatchIds ?: [0])
            ->select('warehouse_row_id', 'pallet_start', 'pallets_used')
            ->get()
            ->groupBy('warehouse_row_id');

        $suggestions = [];

        foreach ($warehouses as $wh) {
            foreach ($wh->rows as $row) {
                $rowCap = (int)($row->pallet_capacity ?? 0);
                if ($rowCap <= 0) continue;

                // Build occupied pallet set for this row
                $occupied = [];
                $rowItems = $occupiedByRow->get($row->id, collect());
                foreach ($rowItems as $it) {
                    $start = max(1, (int)$it->pallet_start);
                    $end   = $start + max(1, (int)$it->pallets_used) - 1;
                    for ($p = $start; $p <= min($end, $rowCap); $p++) {
                        $occupied[$p] = true;
                    }
                }

                // Find contiguous free blocks (from pallet 1 upward)
                $freeBlocks = [];
                $blockStart = null;
                for ($p = 1; $p <= $rowCap; $p++) {
                    if (!isset($occupied[$p])) {
                        if ($blockStart === null) $blockStart = $p;
                    } else {
                        if ($blockStart !== null) {
                            $freeBlocks[] = ['start' => $blockStart, 'end' => $p - 1];
                            $blockStart = null;
                        }
                    }
                }
                if ($blockStart !== null) {
                    $freeBlocks[] = ['start' => $blockStart, 'end' => $rowCap];
                }

                if (empty($freeBlocks)) continue;

                $freeCount = array_sum(array_map(fn($b) => $b['end'] - $b['start'] + 1, $freeBlocks));
                $firstBlock = $freeBlocks[0];
                $firstFreeCode = $this->getPalletCode($row->row_name, $firstBlock['start'] - 1);
                $lastFreeCode  = $this->getPalletCode($row->row_name, $firstBlock['end'] - 1);

                $canFit = min($freeCount, $palletsNeeded);

                $suggestions[] = [
                    'warehouse_id'    => $wh->id,
                    'warehouse_name'  => $wh->name,
                    'row_id'          => $row->id,
                    'row_name'        => $row->row_name,
                    'row_capacity'    => $rowCap,
                    'free_pallets'    => $freeCount,
                    'first_free'      => $firstBlock['start'],
                    'first_free_code' => $firstFreeCode,
                    'last_free_code'  => $lastFreeCode,
                    'can_fit'         => $canFit,
                    'fits_all'        => $freeCount >= $palletsNeeded,
                    'free_blocks'     => $freeBlocks,
                ];
            }
        }

        // Sort: ones that fit all first, then by warehouse name, then row name
        usort($suggestions, function($a, $b) {
            if ($b['fits_all'] !== $a['fits_all']) return $b['fits_all'] <=> $a['fits_all'];
            $wComp = strcmp($a['warehouse_name'], $b['warehouse_name']);
            if ($wComp !== 0) return $wComp;
            return strcmp($a['row_name'], $b['row_name']);
        });

        return response()->json([
            'success'        => true,
            'pallets_needed' => $palletsNeeded,
            'suggestions'    => array_values($suggestions),
        ]);
    }

    /**
     * Store a new stock transfer / location relocation (single batch)
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id'         => 'required|exists:products,id',
            'stock_in_item_id'   => 'required|exists:stock_in_items,id',
            'transfer_units'     => 'required|integer|min:1',
            'to_warehouse_id'    => 'required|exists:warehouses,id',
            'to_warehouse_row_id'=> 'nullable|exists:warehouse_rows,id',
            'to_pallet_start'    => 'nullable|integer|min:1',
            'remarks'            => 'nullable|string|max:500',
        ]);

        try {
            $transfer = DB::transaction(function () use ($request) {
                $sourceBatch = StockInItem::with(['warehouse', 'warehouseRow', 'product'])->lockForUpdate()->findOrFail($request->stock_in_item_id);
                
                $product = $sourceBatch->product;
                $packSize = (float) ($sourceBatch->pack_size_snapshot ?: ($product->pack_size ?? 1));
                $requestedUnits = (int) $request->transfer_units;
                $requestedQty = round($requestedUnits * $packSize, 4);

                $availableUnits = $packSize > 0 ? floor($sourceBatch->balance_quantity / $packSize) : 0;

                if ($requestedUnits > $availableUnits) {
                    throw new \Exception("Cannot transfer {$requestedUnits} units. Only {$availableUnits} units available.");
                }

                $fromLocationDisplay = $this->formatLocationPalletDisplay(
                    $sourceBatch->warehouse, $sourceBatch->warehouseRow,
                    $sourceBatch->pallet_start, $sourceBatch->pallets_used
                );

                $cartonsPerPallet = max(1, (int) ($product->cartons_per_pallet ?? 1));
                $palletsNeeded = max(1, (int) ceil($requestedUnits / $cartonsPerPallet));
                $toPalletStart = max(1, (int) ($request->to_pallet_start ?: 1));

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

                $sourceBatch->decrement('balance_quantity', $requestedQty);

                $destLocationDisplays = [];
                foreach ($splits as $split) {
                    $splitWh  = Warehouse::find($split['warehouse_id']);
                    $splitRow = $split['warehouse_row_id'] ? WarehouseRow::find($split['warehouse_row_id']) : null;
                    $destLocationDisplays[] = $this->formatLocationPalletDisplay($splitWh, $splitRow, $split['pallet_start'], $split['pallets']);

                    StockInItem::create([
                        'stock_in_id'       => $sourceBatch->stock_in_id,
                        'warehouse_id'      => $split['warehouse_id'],
                        'warehouse_row_id'  => $split['warehouse_row_id'],
                        'pallet_start'      => $split['pallet_start'],
                        'pallets_used'      => $split['pallets'],
                        'product_id'        => $sourceBatch->product_id,
                        'vendor_id'         => $sourceBatch->vendor_id,
                        'units_received'    => $split['units'],
                        'total_quantity'    => $split['qty'],
                        'balance_quantity'  => $split['qty'],
                        'vendor_batch'      => $sourceBatch->vendor_batch,
                        'sap_batch'         => $sourceBatch->sap_batch,
                        'po_no'             => $sourceBatch->po_no,
                        'ibd_no'            => $sourceBatch->ibd_no,
                        'mfg_date'          => $sourceBatch->mfg_date,
                        'expiry_date'       => $sourceBatch->expiry_date,
                        'sound_stock'       => $sourceBatch->sound_stock,
                        'block_stock'       => $sourceBatch->block_stock,
                        'hold_stock'        => $sourceBatch->hold_stock,
                        'quality_clearance' => $sourceBatch->quality_clearance,
                        'qc_remarks'        => $sourceBatch->qc_remarks,
                        'allow_expired_sale'=> $sourceBatch->allow_expired_sale,
                        'pack_size_snapshot'=> $sourceBatch->pack_size_snapshot,
                        'packing_snapshot'  => $sourceBatch->packing_snapshot,
                        'uom_snapshot'      => $sourceBatch->uom_snapshot,
                        'remarks'           => "Relocated from {$fromLocationDisplay}. " . ($request->remarks ?? ''),
                    ]);
                }

                $toLocationDisplay = implode(' + ', array_unique($destLocationDisplays));

                $todayStr   = date('Ymd');
                $countToday = StockTransfer::whereDate('created_at', now()->toDateString())->count() + 1;
                $transferNo = 'TRF-' . $todayStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

                return StockTransfer::create([
                    'transfer_no'          => $transferNo,
                    'product_id'           => $sourceBatch->product_id,
                    'stock_in_item_id'     => $sourceBatch->id,
                    'from_warehouse_id'    => $sourceBatch->warehouse_id,
                    'from_warehouse_row_id'=> $sourceBatch->warehouse_row_id,
                    'from_location_display'=> $fromLocationDisplay,
                    'to_warehouse_id'      => $request->to_warehouse_id,
                    'to_warehouse_row_id'  => $request->to_warehouse_row_id,
                    'to_location_display'  => $toLocationDisplay,
                    'quantity'             => $requestedQty,
                    'units'                => $requestedUnits,
                    'transfer_date'        => now(),
                    'user_id'              => Auth::id(),
                    'remarks'              => $request->remarks,
                ]);
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Stock successfully transferred ({$transfer->units} units) to {$transfer->to_location_display}.",
                    'transfer'=> $transfer
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

    /**
     * Store multiple batch transfers in one wizard step
     */
    public function storeMulti(Request $request)
    {
        $request->validate([
            'product_id'         => 'required|exists:products,id',
            'to_warehouse_id'    => 'required|exists:warehouses,id',
            'to_warehouse_row_id'=> 'required|exists:warehouse_rows,id',
            'to_pallet_start'    => 'required|integer|min:1',
            'batches'            => 'required|array|min:1',
            'batches.*.batch_id' => 'required|exists:stock_in_items,id',
            'batches.*.units'    => 'required|integer|min:1',
            'remarks'            => 'nullable|string|max:500',
        ]);

        try {
            $results = DB::transaction(function () use ($request) {
                $product = Product::findOrFail($request->product_id);
                $packSize = (float)($product->pack_size ?? 1);
                $cartonsPerPallet = max(1, (int)($product->cartons_per_pallet ?? 1));
                $toWarehouse = Warehouse::findOrFail($request->to_warehouse_id);
                $toRow = WarehouseRow::findOrFail($request->to_warehouse_row_id);
                $palletCursor = (int)$request->to_pallet_start;

                $transferLogs = [];

                foreach ($request->batches as $batchReq) {
                    $sourceBatch = StockInItem::with(['warehouse', 'warehouseRow', 'product'])
                        ->lockForUpdate()->findOrFail($batchReq['batch_id']);
                    $batchPackSize = (float)($sourceBatch->pack_size_snapshot ?: $packSize);
                    $requestedUnits = (int)$batchReq['units'];
                    $requestedQty = round($requestedUnits * $batchPackSize, 4);

                    $availableUnits = $batchPackSize > 0 ? floor($sourceBatch->balance_quantity / $batchPackSize) : 0;
                    if ($requestedUnits > $availableUnits) {
                        throw new \Exception("Batch #{$sourceBatch->id}: Cannot transfer {$requestedUnits} units. Only {$availableUnits} available.");
                    }

                    $fromLocationDisplay = $this->formatLocationPalletDisplay(
                        $sourceBatch->warehouse, $sourceBatch->warehouseRow,
                        $sourceBatch->pallet_start, $sourceBatch->pallets_used
                    );

                    $palletsNeeded = max(1, (int)ceil($requestedUnits / $cartonsPerPallet));

                    // Assign to destination using cursor position
                    $splits = \App\Services\WarehouseRowFifo::assign(
                        $request->to_warehouse_id,
                        $palletsNeeded,
                        $requestedUnits,
                        $batchPackSize,
                        true,
                        $cartonsPerPallet,
                        $request->to_warehouse_row_id,
                        $palletCursor
                    );

                    $sourceBatch->decrement('balance_quantity', $requestedQty);

                    $destDisplays = [];
                    foreach ($splits as $split) {
                        $splitWh  = Warehouse::find($split['warehouse_id']);
                        $splitRow = $split['warehouse_row_id'] ? WarehouseRow::find($split['warehouse_row_id']) : null;
                        $destDisplays[] = $this->formatLocationPalletDisplay($splitWh, $splitRow, $split['pallet_start'], $split['pallets']);

                        StockInItem::create([
                            'stock_in_id'        => $sourceBatch->stock_in_id,
                            'warehouse_id'        => $split['warehouse_id'],
                            'warehouse_row_id'    => $split['warehouse_row_id'],
                            'pallet_start'        => $split['pallet_start'],
                            'pallets_used'        => $split['pallets'],
                            'product_id'          => $sourceBatch->product_id,
                            'vendor_id'           => $sourceBatch->vendor_id,
                            'units_received'      => $split['units'],
                            'total_quantity'      => $split['qty'],
                            'balance_quantity'    => $split['qty'],
                            'vendor_batch'        => $sourceBatch->vendor_batch,
                            'sap_batch'           => $sourceBatch->sap_batch,
                            'po_no'               => $sourceBatch->po_no,
                            'ibd_no'              => $sourceBatch->ibd_no,
                            'mfg_date'            => $sourceBatch->mfg_date,
                            'expiry_date'         => $sourceBatch->expiry_date,
                            'sound_stock'         => $sourceBatch->sound_stock,
                            'block_stock'         => $sourceBatch->block_stock,
                            'hold_stock'          => $sourceBatch->hold_stock,
                            'quality_clearance'   => $sourceBatch->quality_clearance,
                            'qc_remarks'          => $sourceBatch->qc_remarks,
                            'allow_expired_sale'  => $sourceBatch->allow_expired_sale,
                            'pack_size_snapshot'  => $sourceBatch->pack_size_snapshot,
                            'packing_snapshot'    => $sourceBatch->packing_snapshot,
                            'uom_snapshot'        => $sourceBatch->uom_snapshot,
                            'remarks'             => "Wizard transfer from {$fromLocationDisplay}. " . ($request->remarks ?? ''),
                        ]);
                    }

                    $toLocationDisplay = implode(' + ', array_unique($destDisplays));
                    $palletCursor += $palletsNeeded;

                    $todayStr   = date('Ymd');
                    $countToday = StockTransfer::whereDate('created_at', now()->toDateString())->count() + 1;
                    $transferNo = 'TRF-' . $todayStr . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

                    $transferLogs[] = StockTransfer::create([
                        'transfer_no'           => $transferNo,
                        'product_id'            => $sourceBatch->product_id,
                        'stock_in_item_id'      => $sourceBatch->id,
                        'from_warehouse_id'     => $sourceBatch->warehouse_id,
                        'from_warehouse_row_id' => $sourceBatch->warehouse_row_id,
                        'from_location_display' => $fromLocationDisplay,
                        'to_warehouse_id'       => $request->to_warehouse_id,
                        'to_warehouse_row_id'   => $request->to_warehouse_row_id,
                        'to_location_display'   => $toLocationDisplay,
                        'quantity'              => $requestedQty,
                        'units'                 => $requestedUnits,
                        'transfer_date'         => now(),
                        'user_id'               => Auth::id(),
                        'remarks'               => $request->remarks,
                    ]);
                }

                return $transferLogs;
            });

            $totalUnits = collect($results)->sum('units');

            return response()->json([
                'success' => true,
                'message' => "âœ… {$totalUnits} units successfully transferred across " . count($results) . " batches.",
                'count'   => count($results),
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

