<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\Warehouse;
use App\Models\WarehouseRow;
use App\Services\WarehouseRowFifo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpeningStockController extends Controller
{
    /**
     * List all opening stock entries
     */
    public function index(Request $request)
    {
        $query = StockInItem::whereHas('stockIn', function ($q) {
            $q->where('source_type', 'opening');
        });

        // Apply warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Apply product filter
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Apply search filter (item code or product name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('item_code', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        // Apply status filter
        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'available') {
                $query->where('block_stock', 0)
                      ->where('hold_stock', 0);
            } elseif ($request->stock_status === 'blocked') {
                $query->where('block_stock', 1);
            } elseif ($request->stock_status === 'hold') {
                $query->where('hold_stock', 1);
            }
        }

        $items = $query->select(
                'product_id',
                DB::raw('SUM(units_received) as total_units'),
                DB::raw('SUM(total_quantity) as total_qty'),
                DB::raw('SUM(balance_quantity) as total_balance'),
                DB::raw('SUM(pallets_used) as total_pallets'),
                DB::raw('COUNT(id) as batch_count'),
                DB::raw('MAX(created_at) as latest_date'),
                DB::raw('MAX(stock_in_id) as latest_stock_in_id')
            )
            ->groupBy('product_id')
            ->with(['product.category'])
            ->latest('product_id')
            ->paginate(20);

        $items->getCollection()->transform(function($item) {
            if ($item->product && $item->product->cartons_per_pallet > 0 && $item->total_units > 0) {
                $item->total_pallets = ceil($item->total_units / $item->product->cartons_per_pallet);
            }
            return $item;
        });

        $warehouses = Warehouse::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        $transactions = StockIn::where('source_type', 'opening')
            ->with(['warehouse', 'items.product'])
            ->latest()
            ->paginate(15, ['*'], 'transactions_page');

        return view('opening_stock.index', compact('items', 'warehouses', 'products', 'transactions'));
    }

    /**
     * Get all batches/locations for a specific product
     */
    public function productBatches(Request $request, $productId)
    {
        $items = StockInItem::whereHas('stockIn', function ($q) {
                $q->where('source_type', 'opening');
            })
            ->where('product_id', $productId)
            ->with([
                'stockIn.warehouse',
                'warehouseRow',
                'product.category',
                'product.uom',
                'product.packingType',
            ])
            ->get();

        $items->transform(function ($item) {
            $row = $item->warehouseRow;
            $units = (int)$item->units_received;
            $pallets = $item->pallets_used ?: ($item->product && $item->product->cartons_per_pallet > 0
                ? ceil($units / $item->product->cartons_per_pallet) : 0);

            if (!$row || !$item->pallet_start || $pallets <= 0) {
                $item->pallet_range_display = $row ? $row->row_name : '—';
            } else {
                $firstPallet = $item->getPalletName(0);
                if ($pallets > 1) {
                    $lastPallet = $item->getPalletName($pallets - 1);
                    $item->pallet_range_display = $firstPallet . ' to ' . $lastPallet;
                } else {
                    $item->pallet_range_display = $firstPallet;
                }
            }
            return $item;
        });

        // Sort items naturally: Warehouse Name -> Location (pallet_range_display / row_name) -> Pallet Start -> ID
        $items = $items->sortBy([
            fn ($a, $b) => strnatcasecmp(
                $a->stockIn->warehouse->name ?? $a->warehouse->name ?? '',
                $b->stockIn->warehouse->name ?? $b->warehouse->name ?? ''
            ),
            fn ($a, $b) => strnatcasecmp(
                $a->pallet_range_display ?? $a->warehouseRow->row_name ?? '',
                $b->pallet_range_display ?? $b->warehouseRow->row_name ?? ''
            ),
            fn ($a, $b) => ($a->pallet_start ?? 0) <=> ($b->pallet_start ?? 0),
            fn ($a, $b) => $a->id <=> $b->id,
        ])->values();

        return response()->json($items);
    }

    /**
     * Show opening stock form
     */
    public function create()
    {
        return view('opening_stock.create', [
            'warehouses' => Warehouse::with('rows')
                ->where('status', 1)
                ->orderBy('name')
                ->get(),

            'products' => Product::where('status', 1)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Store opening stock
     */
    public function store(Request $request)
    {
        if ($request->has('items') && is_array($request->items)) {
            $filteredItems = collect($request->items)
                ->filter(function ($item) {
                    return !empty($item['product_id']);
                })
                ->values()
                ->toArray();
            $request->merge(['items' => $filteredItems]);
        }

        $request->validate([
            'warehouse_id' => 'required|string',
            'items' => 'required|array|min:1',

            'items.*.product_id' => 'required|exists:products,id',
            'items.*.units_received' => 'required|integer|min:1',
            'items.*.warehouse_id' => 'nullable|string',

            'items.*.quality_clearance' => 'nullable|in:pending,approved,rejected',

            'items.*.sap_batch' => 'nullable|string|max:100',
            'items.*.vendor_batch' => 'nullable|string|max:100',
            'items.*.ibd_no' => 'nullable|string|max:100',
            'items.*.po_no' => 'nullable|string|max:100',

            'items.*.mfg_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.pallets_used' => 'nullable|integer|min:0',
            'items.*.remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($request, &$stockIn) {

                /*
                |--------------------------------------------------------------------------
                | 1️⃣ Create Opening Stock Header
                |--------------------------------------------------------------------------
                | We'll use the header warehouse_id if it's not "auto". If it is "auto",
                | we will temporarily set it to the first active warehouse, and then
                | sync it at the end to the first actual warehouse where items are stored.
                */
                $headerWhId = $request->warehouse_id;
                $activeWarehouses = Warehouse::where('status', 1)->orderBy('name')->get();
                if ($activeWarehouses->isEmpty()) {
                    throw new \Exception("No active warehouses found.");
                }

                $tempWhId = $headerWhId === 'auto' ? $activeWarehouses->first()->id : (int) $headerWhId;

                $stockIn = StockIn::create([
                    'source_type' => 'opening',
                    'warehouse_id' => $tempWhId,
                    'shipment_type' => 'manual',
                    'remarks' => $request->remarks,
                ]);

                // Track remaining capacities for each warehouse dynamically during processing
                $remainingFree = [];
                foreach ($activeWarehouses as $wh) {
                    $remainingFree[$wh->id] = WarehouseRowFifo::getFreeRowCapacity($wh->id);
                }

                /*
                |--------------------------------------------------------------------------
                | 2️⃣ Pre-validate Capacity for all items across all active warehouses
                |--------------------------------------------------------------------------
                */
                $totalNeededPallets = 0;

                foreach ($request->items as $item) {
                    if (!empty($item['product_id']) && !empty($item['units_received'])) {
                        $product = Product::find($item['product_id']);
                        $palletsNeeded = (int) ($item['pallets_used'] ?? 0);
                        if ($palletsNeeded === 0 && $product && $product->cartons_per_pallet > 0) {
                            $palletsNeeded = (int) ceil((int) $item['units_received'] / $product->cartons_per_pallet);
                        }
                        $totalNeededPallets += $palletsNeeded;

                        if ($palletsNeeded > 0 && $product && $product->cartons_per_pallet > 0) {
                            $maxUnits = $palletsNeeded * $product->cartons_per_pallet;
                            if ((int) $item['units_received'] > $maxUnits) {
                                throw new \Exception(
                                    "Product {$product->name}: {$item['units_received']} cartons cannot fit in {$palletsNeeded} pallet(s) (max {$product->cartons_per_pallet} per pallet). Need " .
                                    ceil((int) $item['units_received'] / $product->cartons_per_pallet) . " pallets."
                                );
                            }
                        }
                    }
                }

                $totalAvailable = array_sum($remainingFree);
                if ($totalAvailable < $totalNeededPallets) {
                    throw new \Exception("Insufficient total warehouse capacity. Need {$totalNeededPallets} pallets, but only {$totalAvailable} available slots across all active warehouses.");
                }

                /*
                |--------------------------------------------------------------------------
                | 3️⃣ Create Stock In Items (BATCH LEVEL) — Sequential Multi-Warehouse Fill
                |--------------------------------------------------------------------------
                */
                $simulatedOccupied = [];

                foreach ($request->items as $item) {
                    if (empty($item['product_id']) || empty($item['units_received'])) continue;

                    $product  = Product::findOrFail($item['product_id']);
                    $units    = (int) $item['units_received'];
                    $packSize = (float) $product->pack_size;

                    $itemWh = !empty($item['warehouse_id']) ? $item['warehouse_id'] : $headerWhId;
                    $itemWhId = ($itemWh === 'auto') ? $activeWarehouses->first()->id : (int) $itemWh;

                    $manualRowId = !empty($item['warehouse_row_id']) ? (int) $item['warehouse_row_id'] : null;
                    $manualPalletStart = isset($item['pallet_start']) && $item['pallet_start'] !== '' ? (int) $item['pallet_start'] : null;

                    $palletsNeeded = (int) ($item['pallets_used'] ?? 0);
                    if ($palletsNeeded === 0 && $product->cartons_per_pallet > 0) {
                        $palletsNeeded = (int) ceil($units / $product->cartons_per_pallet);
                    }

                    $splits = WarehouseRowFifo::assign(
                        $itemWhId,
                        $palletsNeeded,
                        $units,
                        $packSize,
                        true,
                        (int) $product->cartons_per_pallet,
                        $manualRowId,
                        $manualPalletStart,
                        $simulatedOccupied
                    );

                    foreach ($splits as $split) {
                        $splitUnits   = $split['units'];
                        $splitQty     = round($splitUnits * $packSize, 4);
                        $splitPallets = $split['pallets'];

                        $lastVacant = $product->cartons_per_pallet > 0
                            ? max(0, ($splitPallets * $product->cartons_per_pallet) - $splitUnits)
                            : 0;

                        StockInItem::create([
                            'stock_in_id'        => $stockIn->id,
                            'product_id'         => $product->id,
                            'warehouse_id'       => $split['warehouse_id'],
                            'warehouse_row_id'   => $split['warehouse_row_id'],

                            'sap_batch'          => $item['sap_batch'] ?? null,
                            'vendor_batch'       => $item['vendor_batch'] ?? null,
                            'ibd_no'             => $item['ibd_no'] ?? null,
                            'po_no'              => $item['po_no'] ?? null,

                            'mfg_date'           => $item['mfg_date'] ?? null,
                            'expiry_date'        => $item['expiry_date'] ?? null,

                            'units_received'     => $splitUnits,
                            'pack_size_snapshot' => $packSize,
                            'total_quantity'     => $splitQty,
                            'balance_quantity'   => $splitQty,

                            'use_pallets'        => $splitPallets > 0,
                            'pallets_used'       => $splitPallets > 0 ? $splitPallets : null,
                            'pallet_start'       => $split['pallet_start'] ?? null,
                            'last_pallet_vacant' => $lastVacant,

                            'sound_stock'        => ! empty($item['sound_stock']),
                            'block_stock'        => ! empty($item['block_stock']),
                            'hold_stock'         => ! empty($item['hold_stock']),
                            'quality_clearance'  => $item['quality_clearance'] ?? 'pending',
                            'remarks'            => $item['remarks'] ?? null,
                            'uom_snapshot'       => optional($product->uom)->name,
                            'packing_snapshot'   => optional($product->packingType)->name,
                        ]);
                    }
                }

                // Sync header warehouse_id to the first actual warehouse where items are stored
                $actualWhId = $stockIn->items()->orderBy('warehouse_id')->value('warehouse_id');
                if ($actualWhId && $actualWhId != $stockIn->warehouse_id) {
                    $stockIn->update(['warehouse_id' => $actualWhId]);
                }
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Opening stock added successfully',
                    'redirect' => route('opening-stock.index')
                ]);
            }

            return redirect()
                ->route('opening-stock.index')
                ->with('success', 'Opening stock added successfully');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show edit form for a single opening stock item
     */
    public function edit($id)
    {
        $item = StockInItem::whereHas('stockIn', function ($q) {
            $q->where('source_type', 'opening');
        })->with(['stockIn.warehouse', 'product', 'warehouseRow'])->findOrFail($id);

        $warehouses = Warehouse::where('status', 1)->orderBy('name')->get();
        $products = Product::where('status', 1)->orderBy('name')->get();

        return view('opening_stock.edit', compact('item', 'warehouses', 'products'));
    }

    /**
     * Update a single opening stock item (all fields editable)
     */
    public function update(Request $request, $id)
    {
        $item = StockInItem::whereHas('stockIn', function ($q) {
            $q->where('source_type', 'opening');
        })->findOrFail($id);

        $request->validate([
            'warehouse_id'      => 'required|exists:warehouses,id',
            'product_id'         => 'required|exists:products,id',
            'units_received'     => 'required|integer|min:1',
            'pallets_used'       => 'nullable|integer|min:0',
            'sap_batch'         => 'nullable|string|max:100',
            'vendor_batch'      => 'nullable|string|max:100',
            'ibd_no'            => 'nullable|string|max:100',
            'po_no'             => 'nullable|string|max:100',
            'mfg_date'          => 'nullable|date',
            'expiry_date'       => 'nullable|date',
            'quality_clearance' => 'nullable|in:pending,approved,rejected',
            'sound_stock'       => 'nullable|boolean',
            'block_stock'       => 'nullable|boolean',
            'hold_stock'        => 'nullable|boolean',
            'remarks'           => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($request, $item) {
                $warehouse = Warehouse::findOrFail($request->warehouse_id);
                $product = Product::findOrFail($request->product_id);
                $units = (int) $request->units_received;
                $packSize = (float) $product->pack_size;
                $totalQty = round($units * $packSize, 4);

                // Calculate pallets
                $palletsNeeded = (int) ($request->pallets_used ?? 0);
                if ($palletsNeeded === 0 && $product->cartons_per_pallet > 0) {
                    $palletsNeeded = (int) ceil($units / $product->cartons_per_pallet);
                }

                // Check capacity using standard method
                $freePallets = WarehouseRowFifo::getFreeRowCapacity($warehouse->id);
                if ($freePallets < $palletsNeeded) {
                    throw new \Exception("Warehouse is full. Cannot update opening stock. Only {$freePallets} pallet slots available, but {$palletsNeeded} needed.");
                }

                // Auto-assignment of Row
                $splits = WarehouseRowFifo::assign(
                    $warehouse->id,
                    $palletsNeeded,
                    $units,
                    $packSize,
                    true,
                    (int) $product->cartons_per_pallet
                );

                // Update the item with first split
                $split = $splits[0] ?? null;
                $item->update([
                    'warehouse_id'       => $warehouse->id,
                    'product_id'         => $product->id,
                    'warehouse_row_id'   => $split ? $split['warehouse_row_id'] : null,
                    'units_received'     => $units,
                    'pack_size_snapshot' => $packSize,
                    'total_quantity'     => $totalQty,
                    'balance_quantity'   => $totalQty,
                    'use_pallets'        => $palletsNeeded > 0,
                    'pallets_used'       => $split ? $split['pallets'] : null,
                    'pallet_start'       => $split ? $split['pallet_start'] : null,
                    'sap_batch'         => $request->sap_batch,
                    'vendor_batch'      => $request->vendor_batch,
                    'ibd_no'            => $request->ibd_no,
                    'po_no'             => $request->po_no,
                    'mfg_date'          => $request->mfg_date ?: null,
                    'expiry_date'       => $request->expiry_date ?: null,
                    'quality_clearance' => $request->quality_clearance ?? 'pending',
                    'sound_stock'       => $request->boolean('sound_stock'),
                    'block_stock'       => $request->boolean('block_stock'),
                    'hold_stock'        => $request->boolean('hold_stock'),
                    'remarks'           => $request->remarks,
                ]);

                // If multiple splits, create additional items for remaining rows
                if (count($splits) > 1) {
                    for ($i = 1; $i < count($splits); $i++) {
                        $extraSplit = $splits[$i];
                        $extraQty = round($extraSplit['units'] * $packSize, 4);
                        StockInItem::create([
                            'stock_in_id'        => $item->stock_in_id,
                            'product_id'         => $product->id,
                            'warehouse_id'       => $warehouse->id,
                            'warehouse_row_id'   => $extraSplit['warehouse_row_id'],
                            'units_received'     => $extraSplit['units'],
                            'pack_size_snapshot' => $packSize,
                            'total_quantity'     => $extraQty,
                            'balance_quantity'   => $extraQty,
                            'use_pallets'        => true,
                            'pallets_used'       => $extraSplit['pallets'],
                            'pallet_start'       => $extraSplit['pallet_start'],
                            'sap_batch'          => $request->sap_batch,
                            'vendor_batch'       => $request->vendor_batch,
                            'ibd_no'             => $request->ibd_no,
                            'po_no'              => $request->po_no,
                            'mfg_date'           => $request->mfg_date ?: null,
                            'expiry_date'        => $request->expiry_date ?: null,
                            'quality_clearance'  => $request->quality_clearance ?? 'pending',
                            'sound_stock'        => $request->boolean('sound_stock'),
                            'block_stock'        => $request->boolean('block_stock'),
                            'hold_stock'         => $request->boolean('hold_stock'),
                            'remarks'            => $request->remarks,
                            'last_pallet_vacant' => 0,
                        ]);
                    }
                }

                // Also update the stockIn warehouse if needed
                if ($item->stockIn) {
                    $item->stockIn->update([
                        'warehouse_id' => $warehouse->id,
                    ]);
                }
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Opening stock item updated successfully.',
                ]);
            }

            return redirect()->route('opening-stock.index')->with('success', 'Opening stock item updated successfully.');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function export()
    {
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

        // (Dynamic computation across ALL stock items is removed so Opening Stock remains historically immutable)

        // Now fetch only opening stock items for CSV output
        $items = StockInItem::whereHas('stockIn', fn($q) => $q->where('source_type', 'opening'))
            ->with(['product.category', 'product.uom', 'product.packingType', 'warehouse', 'stockIn', 'warehouseRow'])
            ->latest()
            ->get();

        $filename = 'opening_stock_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($items, $rowLetterMap) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Date', 'Record ID', 'Item Code', 'Product Name', 'Warehouse', 'Category', 'UOM',
                'IBD', 'PO', 'Vendor Batch', 'SAP Batch', 'Packing',
                'Pack Size', 'Units Received', 'Total Qty', 'MFG Date',
                'Expiry Date', 'Balance Qty', 'Pallets Used', 'Quality Check',
                'Sound', 'Blocked', 'Hold'
            ]);

            foreach ($items as $item) {
                $warehouseDisplay = $item->warehouse->name ?? '';
                $unitsVal = $item->units_received;
                $qtyVal = $item->total_quantity;
                $balVal = $item->total_quantity; // Export original quantity, not current balance
                
                $originalPallets = $item->pallets_used ?? 0;
                if ($item->product && $item->product->cartons_per_pallet > 0 && $item->units_received > 0) {
                    $originalPallets = max($originalPallets, (int) ceil($item->units_received / $item->product->cartons_per_pallet));
                }
                $palletsVal = $originalPallets;
                
                $dateVal = $item->created_at ? (method_exists($item->created_at, 'format') ? $item->created_at->format('d.m.Y H:i') : $item->created_at) : '';

                if ($item->warehouse_row_id) {
                    $pStart = (int) $item->pallet_start;
                    $pUsed = (int) $item->pallets_used;
                    if ($originalPallets > $pUsed && $pStart > 0) {
                        $pStart = $pStart + $pUsed - $originalPallets;
                    }
                    
                    $palletStart = $pStart > 0 ? $pStart : 1;
                    $palletEnd = $palletStart + $originalPallets - 1;
                    
                    $whId = $item->warehouse_id;
                    $rowNameStr = $item->warehouseRow->row_name ?? '';
                    $rowKey = $whId . '-' . $rowNameStr;
                    $rowLetter = $rowLetterMap[$rowKey] ?? '';
                    $whPadded = str_pad($whId, 2, '0', STR_PAD_LEFT);

                    $maxPerPallet = $item->product->cartons_per_pallet ?? null;
                    $numPallets = $originalPallets;
                    $totalUnits = (float) $item->units_received;
                    $totalQty = (float) $item->total_quantity;
                    $totalBalance = (float) $item->total_quantity; // Export original quantity, not current balance
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
                        $rowNameStr = $item->warehouseRow->row_name ?? '';
                        $wName = (strpos($rowNameStr, '.') !== false) ? explode('.', $rowNameStr)[0] : "W{$whPadded}";
                        $warehouseDisplay = "{$wName}.{$rowLetter}{$psPadded}";

                        fputcsv($file, [
                            $dateVal,
                            $item->id,
                            $item->product?->item_code ?? '',
                            $item->product?->name ?? '',
                            $warehouseDisplay,
                            $item->product?->category?->name ?? '',
                            $item->product?->uom?->name ?? '',
                            $item->ibd_no ?? '',
                            $item->po_no ?? '',
                            $item->vendor_batch ?? '',
                            $item->sap_batch ?? '',
                            $item->product?->packingType?->name ?? '',
                            $item->pack_size_snapshot,
                            $perPalletUnits,
                            $palletQty,
                            $item->mfg_date ? (method_exists($item->mfg_date, 'format') ? $item->mfg_date->format('d.m.Y') : $item->mfg_date) : '',
                            $item->expiry_date ? (method_exists($item->expiry_date, 'format') ? $item->expiry_date->format('d.m.Y') : $item->expiry_date) : '',
                            $palletBalance,
                            1,
                            $item->quality_clearance ?? '',
                            $item->sound_stock ? 'Yes' : 'No',
                            $item->block_stock ? 'Yes' : 'No',
                            $item->hold_stock ? 'Yes' : 'No',
                        ]);
                    }
                } else {
                    fputcsv($file, [
                        $dateVal,
                        $item->id,
                        $item->product?->item_code ?? '',
                        $item->product?->name ?? '',
                        $warehouseDisplay,
                        $item->product?->category?->name ?? '',
                        $item->product?->uom?->name ?? '',
                        $item->ibd_no ?? '',
                        $item->po_no ?? '',
                        $item->vendor_batch ?? '',
                        $item->sap_batch ?? '',
                        $item->product?->packingType?->name ?? '',
                        $item->pack_size_snapshot,
                        $unitsVal,
                        $qtyVal,
                        $item->mfg_date ? (method_exists($item->mfg_date, 'format') ? $item->mfg_date->format('d.m.Y') : $item->mfg_date) : '',
                        $item->expiry_date ? (method_exists($item->expiry_date, 'format') ? $item->expiry_date->format('d.m.Y') : $item->expiry_date) : '',
                        $balVal,
                        $palletsVal,
                        $item->quality_clearance ?? '',
                        $item->sound_stock ? 'Yes' : 'No',
                        $item->block_stock ? 'Yes' : 'No',
                        $item->hold_stock ? 'Yes' : 'No',
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadTemplate()
    {
        $filename = 'opening_stock_import_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Record ID', 'Item Code', 'Product Name', 'Date', 'Warehouse', 'Category', 'UOM',
                'IBD', 'PO', 'Vendor Batch', 'SAP Batch', 'Packing',
                'Pack Size', 'Units Received', 'Total Qty', 'MFG Date',
                'Expiry Date', 'Balance Qty', 'Pallets Used', 'Quality Check',
                'Sound', 'Blocked', 'Hold'
            ]);
            fputcsv($file, [
                '', '001', 'Sample Product', '2024-01-01 10:00', '', '', '',
                'IBD-001', 'PO-001', 'VENDOR-001', 'SAP-001', '',
                '', '100', '', '15.01.2024', '15.01.2025', 
                '', '', 'approved', 'Yes', 'No', 'No'
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importForm()
    {
        return view('opening_stock.import', [
            'warehouses' => Warehouse::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            fclose($handle);
            return back()->with('error', 'Could not read CSV headers.');
        }

        $csvHeaders = array_map(fn($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $h)), $rawHeaders);

        // Flexible field aliases
        $fieldAliases = [
            'Record ID'      => ['Record ID', 'Record ID', 'record_id', 'ID', 'id'],
            'Item Code'      => ['Item Code', 'Item Code', 'item_code', 'ItemCode', 'Code'],
            'Units Received' => ['Units Received', 'Units Received', 'units_received', 'Units'],
            'IBD'            => ['IBD', 'ibd', 'ibd_no', 'IBD No'],
            'PO'             => ['PO', 'po', 'po_no', 'PO No'],
            'SAP Batch'      => ['SAP Batch', 'SAP Batch', 'sap_batch', 'SapBatch', 'Batch'],
            'Vendor Batch'   => ['Vendor Batch', 'Vendor Batch', 'vendor_batch', 'VendorBatch'],
            'MFG Date'       => ['MFG Date', 'MFG Date', 'mfg_date', 'MfgDate', 'Manufacturing Date'],
            'Expiry Date'    => ['Expiry Date', 'Expiry Date', 'expiry_date', 'ExpiryDate', 'Exp Date'],
            'Quality Check'  => ['Quality Check', 'Quality Clearance', 'quality_clearance', 'QC'],
            'Blocked'        => ['Blocked', 'Blocked', 'block_stock', 'Block'],
            'Hold'           => ['Hold', 'Hold', 'hold_stock', 'Hold'],
            'Remarks'        => ['Remarks', 'Remarks', 'remarks', 'Notes', 'Comment'],
            'Warehouse'      => ['Warehouse', 'Warehouse', 'warehouse', 'Warehouse Name', 'WH'],
        ];

        // Build header map: for each field, find first matching column
        $headerMap = [];
        foreach ($fieldAliases as $field => $aliases) {
            $pos = false;
            foreach ($aliases as $alias) {
                $idx = array_search($alias, $csvHeaders);
                if ($idx === false) $idx = array_search(strtolower($alias), array_map('strtolower', $csvHeaders));
                if ($idx !== false) {
                    $pos = $idx;
                    break;
                }
            }
            if ($pos !== false) {
                $headerMap[$field] = $pos;
            }
        }

        // Item Code and Units Received are required
        if (!isset($headerMap['Item Code'])) {
            fclose($handle);
            return back()->with('error', 'Missing required column "Item Code". Found: ' . implode(', ', $csvHeaders));
        }
        if (!isset($headerMap['Units Received'])) {
            fclose($handle);
            return back()->with('error', 'Missing required column "Units Received". Found: ' . implode(', ', $csvHeaders));
        }

        // Build warehouse map (name -> id)
        $allWarehouses = Warehouse::where('status', 1)->get()->keyBy(function($w) {
            return strtolower(trim($w->name));
        });

        // Determine warehouses to use
        $csvHasWarehouse = isset($headerMap['Warehouse']);

        // Full pool of all active warehouses (for auto-assign mode)
        $allWarehousePool = Warehouse::where('status', 1)->whereHas('rows')->orderBy('name')->get();
        if ($allWarehousePool->isEmpty()) {
            $allWarehousePool = Warehouse::where('status', 1)->orderBy('name')->get();
        }
        if ($allWarehousePool->isEmpty()) {
            fclose($handle);
            return back()->with('error', 'No active warehouses found.');
        }

        if ($request->warehouse_id) {
            // User selected a specific warehouse — ONLY use that one, no overflow
            $targetWarehouse = Warehouse::findOrFail($request->warehouse_id);
            $warehousePool = collect([$targetWarehouse]);
        } elseif ($csvHasWarehouse) {
            // CSV has Warehouse column — per-row warehouse only, no overflow
            $warehousePool = $allWarehousePool;
        } else {
            // Auto-assign — try all warehouses, use one with space
            $warehousePool = $allWarehousePool;
        }

        $errors = [];
        $imported = 0;
        $skipped = 0;
        $items = [];
        $allProducts = Product::where('status', 1)->get()->keyBy('item_code');

        // Helper to read cell value by field name
        $getCell = function($row, $field) use ($headerMap) {
            if (!isset($headerMap[$field])) return '';
            $idx = $headerMap[$field];
            $val = trim($row[$idx] ?? '');
            return mb_convert_encoding($val, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        };

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($row) <= 1 && ($row[0] === null || trim($row[0]) === '')) continue;

            $itemCode = $getCell($row, 'Item Code');
            $units = $getCell($row, 'Units Received');
            $rowErrors = [];

            if (empty($itemCode)) $rowErrors[] = 'Missing Item Code';
            if (empty($units) || !is_numeric($units)) $rowErrors[] = 'Invalid Units Received';

            $product = null;
            if (!empty($itemCode)) {
                $product = $allProducts->get($itemCode);
                if (!$product) {
                    // Excel often strips leading zeros, e.g. '002' becomes '2'. Try to find it.
                    $product = $allProducts->first(function($p) use ($itemCode) {
                        return ltrim($p->item_code, '0') === ltrim($itemCode, '0') || 
                               strtolower(trim($p->item_code)) === strtolower(trim($itemCode));
                    });
                }
                if (!$product) $rowErrors[] = "Product '{$itemCode}' not found";
            }

            // Determine warehouse for this row
            $rowWarehouse = null;
            if ($csvHasWarehouse) {
                $whName = strtolower(trim($getCell($row, 'Warehouse')));
                if (!empty($whName) && $allWarehouses->has($whName)) {
                    $rowWarehouse = $allWarehouses[$whName];
                }
            }
            if (!$rowWarehouse && $request->warehouse_id) {
                $rowWarehouse = $targetWarehouse;
            }

            if (!empty($rowErrors)) {
                $errors[] = "Row {$rowNum}: " . implode('; ', $rowErrors);
                $skipped++;
                continue;
            }

            $qcValue = strtolower($getCell($row, 'Quality Check'));
            if (in_array($qcValue, ['pass', 'approved'])) $qcValue = 'approved';
            elseif (in_array($qcValue, ['fail', 'rejected'])) $qcValue = 'rejected';
            else $qcValue = 'pending';

            $parseDate = function($dateStr) {
                if (empty($dateStr)) return null;
                if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $dateStr, $matches)) {
                    return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                }
                return $dateStr;
            };

            $items[] = [
                'record_id' => $getCell($row, 'Record ID'),
                'product' => $product,
                'units' => (int) $units,
                'warehouse' => $rowWarehouse,
                'ibd_no' => $getCell($row, 'IBD'),
                'po_no' => $getCell($row, 'PO'),
                'sap_batch' => $getCell($row, 'SAP Batch'),
                'vendor_batch' => $getCell($row, 'Vendor Batch'),
                'pallets_used' => '', // auto-calculated below
                'mfg_date' => $parseDate($getCell($row, 'MFG Date')),
                'expiry_date' => $parseDate($getCell($row, 'Expiry Date')),
                'quality_clearance' => $qcValue,
                'blocked' => in_array(strtolower($getCell($row, 'Blocked')), ['yes', '1', 'true']),
                'hold' => in_array(strtolower($getCell($row, 'Hold')), ['yes', '1', 'true']),
                'remarks' => $getCell($row, 'Remarks'),
            ];
        }

        fclose($handle);

        if (count($items) === 0) {
            $errorMsg = 'No valid rows found in CSV.';
            if (count($errors) > 0) {
                $errorMsg .= '<br>Details:<br>' . implode('<br>', array_slice($errors, 0, 10));
            }
            return back()->with('error', $errorMsg);
        }

        try {
            DB::transaction(function () use ($warehousePool, $items, $csvHasWarehouse, $request, &$imported, &$skipped, &$errors) {
                // Delete all existing opening stock records first to allow a clean override / replacement
                $oldItems = StockInItem::whereHas('stockIn', function ($q) {
                    $q->where('source_type', 'opening');
                })->get();
                foreach ($oldItems as $oldItem) {
                    $oldItem->delete();
                }
                StockIn::where('source_type', 'opening')->delete();
                
                // We treat all imported items as new on this clean slate
                $newItems = $items;
                


                // Step 4: Insert new items
                foreach ($newItems as $item) {
                    $product       = $item['product'];
                    $units         = $item['units'];
                    $packSize      = (float) $product->pack_size;
                    $cartonsPerPallet = (int) $product->cartons_per_pallet;

                    // Determine target warehouses for this item
                    if ($item['warehouse']) {
                        $targets = collect([$item['warehouse']]);
                    } else {
                        $targets = $warehousePool;
                    }

                    $simulatedRemainingUnits = $units;
                    $warehouseAllocations = [];
                    $warehousePartials = [];

                    // 1. Simulate partial fills
                    if ($cartonsPerPallet > 0) {
                        foreach ($targets as $wh) {
                            if ($simulatedRemainingUnits <= 0) break;
                            
                            $partialResult = WarehouseRowFifo::fillPartials(
                                $wh->id,
                                $product->id,
                                $simulatedRemainingUnits,
                                $packSize,
                                $cartonsPerPallet,
                                $item['sap_batch'] ?? null,
                                $item['vendor_batch'] ?? null,
                                $item['expiry_date'] ?? null
                            );

                            if (!empty($partialResult['splits'])) {
                                $warehousePartials[$wh->id] = $partialResult['splits'];
                                $simulatedRemainingUnits = $partialResult['remaining_units'];
                            }
                        }
                    }

                    // 2. Simulate new pallet allocations
                    if ($simulatedRemainingUnits > 0) {
                        foreach ($targets as $wh) {
                            if ($simulatedRemainingUnits <= 0) break;

                            $freeRowCapacity = WarehouseRowFifo::getFreeRowCapacity($wh->id);
                            
                            if ($freeRowCapacity <= 0 && $cartonsPerPallet > 0) continue;

                            $palletsWeNeed = $cartonsPerPallet > 0 ? (int) ceil($simulatedRemainingUnits / $cartonsPerPallet) : 0;
                            
                            $palletsToAssign = 0;
                            $unitsToAssign = $simulatedRemainingUnits;

                            if ($cartonsPerPallet > 0) {
                                $palletsToAssign = min($freeRowCapacity, $palletsWeNeed);
                                $maxUnitsThisWarehouse = $palletsToAssign * $cartonsPerPallet;
                                $unitsToAssign = min($simulatedRemainingUnits, $maxUnitsThisWarehouse);
                            }

                            if ($unitsToAssign > 0 || $palletsToAssign === 0) {
                                $warehouseAllocations[$wh->id] = [
                                    'pallets' => $palletsToAssign,
                                    'units'   => $unitsToAssign
                                ];
                                $simulatedRemainingUnits -= $unitsToAssign;
                            }
                        }
                    }

                    // --- CHECK IF WE CAN FIT IT ---
                    if ($simulatedRemainingUnits > 0) {
                        $targetMsg = $request->warehouse_id || $item['warehouse'] ? 'Selected warehouse(s)' : 'No warehouse';
                        $palletsNeededTotal = $cartonsPerPallet > 0 ? ceil($units / $cartonsPerPallet) : 0;
                        $errors[] = "{$targetMsg} does not have enough total space for '{$product->item_code}' ({$palletsNeededTotal} pallets needed)";
                        $skipped++;
                        continue;
                    }

                    // --- ACTUALLY SAVE ---
                    // Process partials
                    foreach ($warehousePartials as $whId => $splits) {
                        foreach ($splits as $split) {
                            $existingItem = StockInItem::find($split['stock_in_item_id']);
                            if ($existingItem) {
                                $existingItem->increment('units_received', $split['units']);
                                $existingItem->increment('total_quantity', $split['qty']);
                                $existingItem->increment('balance_quantity', $split['qty']);
                                $existingItem->decrement('last_pallet_vacant', $split['units']);
                            }
                        }
                    }

                    // Process new pallets
                    foreach ($warehouseAllocations as $whId => $alloc) {
                        $wh = $targets->firstWhere('id', $whId);
                        
                        $splits = WarehouseRowFifo::assign(
                            $wh->id,
                            $alloc['pallets'],
                            $alloc['units'],
                            $packSize,
                            false,
                            $cartonsPerPallet
                        );

                        $stockIn = StockIn::firstOrCreate(
                            [
                                'source_type'  => 'opening',
                                'warehouse_id' => $wh->id,
                                'remarks'      => 'Imported via CSV on ' . now()->format('d.m.Y H:i'),
                            ],
                            ['shipment_type' => 'manual']
                        );

                        foreach ($splits as $split) {
                            if ($split['units'] <= 0) continue;

                            $splitUnits = $split['units'];
                            $splitQty   = round($splitUnits * $packSize, 4);

                            $lastVacant = $cartonsPerPallet > 0
                                ? max(0, ($split['pallets'] * $cartonsPerPallet) - $splitUnits)
                                : 0;

                            StockInItem::create([
                                'stock_in_id'        => $stockIn->id,
                                'product_id'         => $product->id,
                                'warehouse_id'       => $wh->id,
                                'warehouse_row_id'   => $split['warehouse_row_id'],
                                'ibd_no'             => $item['ibd_no'] ?: null,
                                'po_no'              => $item['po_no'] ?: null,
                                'sap_batch'          => $item['sap_batch'] ?: null,
                                'vendor_batch'       => $item['vendor_batch'] ?: null,
                                'mfg_date'           => $item['mfg_date'] ?: null,
                                'expiry_date'        => $item['expiry_date'] ?: null,
                                'units_received'     => $splitUnits,
                                'pack_size_snapshot' => $packSize,
                                'total_quantity'     => $splitQty,
                                'balance_quantity'   => $splitQty,
                                'use_pallets'        => $split['pallets'] > 0,
                                'pallets_used'       => $split['pallets'] > 0 ? $split['pallets'] : null,
                                'pallet_start'       => $split['pallet_start'] ?? null,
                                'last_pallet_vacant' => $lastVacant,
                                'sound_stock'        => !$item['blocked'] && !$item['hold'],
                                'block_stock'        => $item['blocked'],
                                'hold_stock'         => $item['hold'],
                                'quality_clearance'  => $item['quality_clearance'],
                                'remarks'            => $item['remarks'] ?: null,
                            ]);
                        }
                    }

                    $imported++;
                }
            });

            $message = "Imported {$imported} product(s).";
            if ($request->warehouse_id) $message .= " Warehouse: {$targetWarehouse->name}.";
            else $message .= " Auto-assigned to warehouses with available space.";
            if ($skipped > 0) $message .= " {$skipped} skipped.";
            if ($errors) $message .= " " . implode(' | ', array_slice($errors, 0, 10));

            return redirect()->route('opening-stock.index')->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function previewPallets(Request $request)
    {
        $items = $request->input('items', []);
        $activeRowIndex = $request->input('active_row_index', 0);

        if (empty($items)) {
            return response()->json(['success' => true, 'allocations' => []]);
        }

        // We will simulate allocations sequentially for all items in the form
        $simulatedOccupied = []; // row_id => [pallet_number => true]
        $activeAllocations = [];
        $activePalletsUsed = 0;
        $activeUnits = 0;

        // Preload active warehouses
        $activeWarehouses = Warehouse::where('status', 1)->orderBy('name')->get();
        if ($activeWarehouses->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No active warehouses found.']);
        }

        foreach ($items as $idx => $itemData) {
            $productId = $itemData['product_id'] ?? null;
            $units = (int) ($itemData['units_received'] ?? 0);
            $warehouseId = $itemData['warehouse_id'] ?? 'auto'; // 'auto' or ID
            $palletsUsed = isset($itemData['pallets_used']) ? (int) $itemData['pallets_used'] : 0;
            $manualRowId = !empty($itemData['warehouse_row_id']) ? (int) $itemData['warehouse_row_id'] : null;
            $manualPalletStart = isset($itemData['pallet_start']) && $itemData['pallet_start'] !== '' ? (int) $itemData['pallet_start'] : null;

            if (!$productId || $units <= 0) {
                continue;
            }

            $product = Product::find($productId);
            if (!$product) continue;

            $packSize = (float) $product->pack_size;
            $cartonsPerPallet = (int) ($product->cartons_per_pallet ?? 0);

            if ($palletsUsed === 0 && $cartonsPerPallet > 0) {
                $palletsUsed = (int) ceil($units / $cartonsPerPallet);
            }

            $targetWhId = ($warehouseId === 'auto') ? $activeWarehouses->first()->id : (int) $warehouseId;

            // Helper to get pallet name
            $getPalletNameLocal = function($row, $palletStart, $offsetIndex) {
                if (!$row || !$palletStart) return '-';
                $rowName = $row->row_name;
                $parts = preg_split('/ to /i', $rowName);
                $firstPallet = $parts[0];
                if (preg_match('/^(.*?)(\d+)$/', $firstPallet, $matches)) {
                    $prefix = $matches[1];
                    $startNum = (int)$matches[2];
                    $actualNum = $startNum + $palletStart - 1 + $offsetIndex;
                    $digits = strlen($matches[2]);
                    return $prefix . sprintf("%0{$digits}d", $actualNum);
                }
                return $rowName . ' - P' . ($palletStart + $offsetIndex);
            };

            $allocations = [];
            try {
                $splits = WarehouseRowFifo::assign(
                    $targetWhId,
                    $palletsUsed,
                    $units,
                    $packSize,
                    true,
                    $cartonsPerPallet,
                    $manualRowId,
                    $manualPalletStart,
                    $simulatedOccupied
                );

                foreach ($splits as $split) {
                    $row = WarehouseRow::with('warehouse')->find($split['warehouse_row_id']);
                    if ($row) {
                        $palletNames = [];
                        for ($i = 0; $i < $split['pallets']; $i++) {
                            $palletNames[] = $getPalletNameLocal($row, $split['pallet_start'], $i);
                        }

                        $allocations[] = [
                            'row_id'         => $row->id,
                            'warehouse_name' => $row->warehouse->name,
                            'row_name'       => $row->row_name,
                            'pallet_start'   => $split['pallet_start'],
                            'pallets_count'  => $split['pallets'],
                            'pallet_names'   => $palletNames,
                            'units'          => $split['units'],
                            'type'           => $manualRowId ? 'manual' : 'auto',
                        ];
                    }
                }
            } catch (\Exception $e) {
                if ($idx == $activeRowIndex) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
            }

            if ($idx == $activeRowIndex) {
                $activeAllocations = $allocations;
                $activePalletsUsed = $palletsUsed;
                $activeUnits = $units;
            }
        }

        return response()->json([
            'success' => true,
            'allocations' => $activeAllocations,
            'units' => $activeUnits,
            'pallets_used' => $activePalletsUsed,
            'remaining_units' => 0,
        ]);
    }

    public function showTransaction(StockIn $stockIn)
    {
        $stockIn->load([
            'warehouse',
            'items.product.category',
            'items.product.uom',
            'items.warehouseRow'
        ]);

        return view('opening_stock.transaction_show', compact('stockIn'));
    }

    public function editTransaction(StockIn $stockIn)
    {
        $stockIn->load(['warehouse', 'items.product', 'items.warehouseRow']);

        // Group items that were split across multiple rows back into single logical entries
        $groupedItems = $stockIn->items->groupBy(function($item) {
            return $item->product_id . '_' . $item->sap_batch . '_' . $item->vendor_batch . '_' . $item->po_no . '_' . $item->ibd_no . '_' . $item->mfg_date . '_' . $item->expiry_date;
        })->map(function($group) {
            $first = $group->first();
            $totalUnits = $group->sum('units_received');
            $totalQty = $group->sum('total_quantity');
            $balanceQty = $group->sum('balance_quantity');
            $isDispatched = round($totalQty - $balanceQty, 4) > 0;

            return [
                'product_id' => $first->product_id,
                'product_name' => optional($first->product)->name,
                'item_code' => optional($first->product)->item_code,
                'pack_size' => $first->pack_size_snapshot,
                'cartons_per_pallet' => optional($first->product)->cartons_per_pallet,
                'sap_batch' => $first->sap_batch,
                'vendor_batch' => $first->vendor_batch,
                'po_no' => $first->po_no,
                'ibd_no' => $first->ibd_no,
                'mfg_date' => $first->mfg_date ? (method_exists($first->mfg_date, 'format') ? $first->mfg_date->format('Y-m-d') : substr($first->mfg_date, 0, 10)) : null,
                'expiry_date' => $first->expiry_date ? (method_exists($first->expiry_date, 'format') ? $first->expiry_date->format('Y-m-d') : substr($first->expiry_date, 0, 10)) : null,
                'units_received' => $totalUnits,
                'total_quantity' => $totalQty,
                'balance_quantity' => $balanceQty,
                'is_dispatched' => $isDispatched,
                'use_pallets' => $first->use_pallets,
                'pallets_used' => $group->sum('pallets_used'),
                'sound_stock' => $first->sound_stock,
                'block_stock' => $first->block_stock,
                'hold_stock' => $first->hold_stock,
                'quality_clearance' => $first->quality_clearance,
                // store the IDs of the splits so we can clean them up if modified
                'split_ids' => $group->pluck('id')->join(','),
                'warehouse_row_id' => $first->warehouse_row_id,
                'pallet_start' => $first->pallet_start,
            ];
        })->values();

        $warehouses = Warehouse::where('status', 1)->with('rows')->orderBy('name')->get();
        $products = Product::where('status', 1)->orderBy('name')->get();

        return view('opening_stock.transaction_edit', compact('stockIn', 'groupedItems', 'warehouses', 'products'));
    }

    public function updateTransaction(Request $request, StockIn $stockIn)
    {
        if ($request->has('items') && is_array($request->items)) {
            $filteredItems = collect($request->items)
                ->filter(function ($item) {
                    return !empty($item['product_id']);
                })
                ->values()
                ->toArray();
            $request->merge(['items' => $filteredItems]);
        }

        $request->validate([
            'warehouse_id' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.units_received' => 'required|integer|min:1',
            'items.*.warehouse_id' => 'nullable|string',
            'items.*.quality_clearance' => 'nullable|in:pending,approved,rejected',
            'items.*.sap_batch' => 'nullable|string|max:100',
            'items.*.vendor_batch' => 'nullable|string|max:100',
            'items.*.ibd_no' => 'nullable|string|max:100',
            'items.*.po_no' => 'nullable|string|max:100',
            'items.*.mfg_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.pallets_used' => 'nullable|integer|min:0',
            'items.*.remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($request, $stockIn) {
                // Update header
                $headerWhId = $request->warehouse_id;
                $activeWarehouses = Warehouse::where('status', 1)->orderBy('name')->get();
                if ($activeWarehouses->isEmpty()) {
                    throw new \Exception("No active warehouses found.");
                }
                $tempWhId = $headerWhId === 'auto' ? $activeWarehouses->first()->id : (int) $headerWhId;

                $stockIn->update([
                    'warehouse_id' => $tempWhId,
                    'remarks' => $request->remarks,
                ]);

                // Fetch original split IDs BEFORE modifying
                $allOriginalSplitIds = $stockIn->items()->pluck('id')->toArray();
                $submittedSplitIds = [];

                // Track remaining capacities
                $remainingFree = [];
                foreach ($activeWarehouses as $wh) {
                    $remainingFree[$wh->id] = WarehouseRowFifo::getFreeRowCapacity($wh->id);
                }

                // Process Items
                foreach ($request->items as $itemData) {
                    if (empty($itemData['product_id']) || empty($itemData['units_received'])) continue;

                    $productId = $itemData['product_id'];
                    $product = Product::findOrFail($productId);
                    $newUnits = (int) $itemData['units_received'];
                    $packSize = (float) $product->pack_size;
                    $newQty = round($newUnits * $packSize, 4);

                    $splitIdsStr = $itemData['split_ids'] ?? '';
                    $splitIds = array_filter(explode(',', $splitIdsStr));

                    if (!empty($splitIds)) {
                        $submittedSplitIds = array_merge($submittedSplitIds, $splitIds);

                        $existingSplits = StockInItem::whereIn('id', $splitIds)->get();
                        $oldTotalQty = round($existingSplits->sum('total_quantity'), 4);
                        $oldBalanceQty = round($existingSplits->sum('balance_quantity'), 4);
                        $isDispatched = ($oldTotalQty - $oldBalanceQty) > 0.001;

                        if ($isDispatched) {
                            // Dispatched - enforce constraints, only update details
                            $dispatchedQty = $oldTotalQty - $oldBalanceQty;
                            if ($newQty < $dispatchedQty) {
                                throw new \Exception("Cannot reduce product '{$product->name}' below dispatched quantity.");
                            }
                            foreach ($existingSplits as $split) {
                                $split->update([
                                    'sap_batch' => $itemData['sap_batch'] ?? null,
                                    'vendor_batch' => $itemData['vendor_batch'] ?? null,
                                    'po_no' => $itemData['po_no'] ?? null,
                                    'ibd_no' => $itemData['ibd_no'] ?? null,
                                    'mfg_date' => $itemData['mfg_date'] ?? null,
                                    'expiry_date' => $itemData['expiry_date'] ?? null,
                                    'sound_stock' => !empty($itemData['sound_stock']),
                                    'block_stock' => !empty($itemData['block_stock']),
                                    'hold_stock' => !empty($itemData['hold_stock']),
                                    'quality_clearance' => $itemData['quality_clearance'] ?? 'pending',
                                    'remarks' => $itemData['remarks'] ?? null,
                                ]);
                            }
                        } else {
                            // Not dispatched - delete old and recreate
                            StockInItem::whereIn('id', $splitIds)->delete();
                            $this->createTransactionItemSplits($stockIn, $tempWhId, $product, $itemData, $remainingFree, $activeWarehouses);
                        }
                    } else {
                        // New Item
                        $this->createTransactionItemSplits($stockIn, $tempWhId, $product, $itemData, $remainingFree, $activeWarehouses);
                    }
                }

                // Delete removed items
                $splitsToRemove = array_diff($allOriginalSplitIds, $submittedSplitIds);
                if (!empty($splitsToRemove)) {
                    $removedSplits = StockInItem::whereIn('id', $splitsToRemove)->get();
                    foreach ($removedSplits as $removedSplit) {
                        if (round($removedSplit->total_quantity - $removedSplit->balance_quantity, 4) > 0) {
                            throw new \Exception("Cannot delete item '{$removedSplit->product->name}' because it has already been dispatched.");
                        }
                    }
                    StockInItem::whereIn('id', $splitsToRemove)->delete();
                }

                // Sync header warehouse_id if items ended up in a different warehouse
                $actualWhId = $stockIn->items()->where('balance_quantity', '>', 0)
                    ->orderBy('warehouse_id')->value('warehouse_id');
                if ($actualWhId && $actualWhId != $stockIn->warehouse_id) {
                    $stockIn->update(['warehouse_id' => $actualWhId]);
                }
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Opening stock transaction updated successfully.',
                    'redirect' => route('opening-stock.index')
                ]);
            }

            return redirect()->route('opening-stock.index')->with('success', 'Opening stock transaction updated successfully.');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroyTransaction(StockIn $stockIn)
    {
        try {
            DB::transaction(function () use ($stockIn) {
                // Check if any items have been dispatched
                foreach ($stockIn->items as $item) {
                    if (round($item->total_quantity - $item->balance_quantity, 4) > 0) {
                        throw new \Exception("Cannot delete opening stock transaction #{$stockIn->id} because item '{$item->product->name}' has already been dispatched.");
                    }
                }

                // Delete items
                $stockIn->items()->delete();
                // Delete header
                $stockIn->delete();
            });

            return redirect()->route('opening-stock.index')->with('success', 'Opening stock transaction deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function createTransactionItemSplits($stockIn, $headerWhId, $product, $itemData, &$remainingFree, $activeWarehouses)
    {
        $units = (int) $itemData['units_received'];
        $packSize = (float) $product->pack_size;

        $itemWh = !empty($itemData['warehouse_id']) ? $itemData['warehouse_id'] : $headerWhId;
        $whId = ($itemWh === 'auto') ? $activeWarehouses->first()->id : (int) $itemWh;

        $manualRowId = !empty($itemData['warehouse_row_id']) ? (int) $itemData['warehouse_row_id'] : null;
        $manualPalletStart = isset($itemData['pallet_start']) && $itemData['pallet_start'] !== '' ? (int) $itemData['pallet_start'] : null;

        $palletsNeeded = (int) ($itemData['pallets_used'] ?? 0);
        if ($palletsNeeded === 0 && $product->cartons_per_pallet > 0) {
            $palletsNeeded = (int) ceil($units / $product->cartons_per_pallet);
        }

        $simulatedOccupied = [];

        $splits = WarehouseRowFifo::assign(
            $whId,
            $palletsNeeded,
            $units,
            $packSize,
            true,
            (int) $product->cartons_per_pallet,
            $manualRowId,
            $manualPalletStart,
            $simulatedOccupied
        );

        foreach ($splits as $split) {
            $splitUnits   = $split['units'];
            $splitQty     = round($splitUnits * $packSize, 4);
            $splitPallets = $split['pallets'];

            $lastVacant = $product->cartons_per_pallet > 0
                ? max(0, ($splitPallets * $product->cartons_per_pallet) - $splitUnits)
                : 0;

            StockInItem::create([
                'stock_in_id'        => $stockIn->id,
                'product_id'         => $product->id,
                'warehouse_id'       => $split['warehouse_id'],
                'warehouse_row_id'   => $split['warehouse_row_id'],
                'sap_batch'          => $itemData['sap_batch'] ?? null,
                'vendor_batch'       => $itemData['vendor_batch'] ?? null,
                'ibd_no'             => $itemData['ibd_no'] ?? null,
                'po_no'              => $itemData['po_no'] ?? null,
                'mfg_date'           => $itemData['mfg_date'] ?? null,
                'expiry_date'        => $itemData['expiry_date'] ?? null,
                'units_received'     => $splitUnits,
                'pack_size_snapshot' => $packSize,
                'total_quantity'     => $splitQty,
                'balance_quantity'   => $splitQty,
                'use_pallets'        => $splitPallets > 0,
                'pallets_used'       => $splitPallets > 0 ? $splitPallets : null,
                'pallet_start'       => $split['pallet_start'] ?? null,
                'last_pallet_vacant' => $lastVacant,
                'sound_stock'        => !empty($itemData['sound_stock']),
                'block_stock'        => !empty($itemData['block_stock']),
                'hold_stock'         => !empty($itemData['hold_stock']),
                'quality_clearance'  => $itemData['quality_clearance'] ?? 'pending',
                'remarks'            => $itemData['remarks'] ?? null,
                'uom_snapshot'       => optional($product->uom)->name,
                'packing_snapshot'   => optional($product->packingType)->name,
            ]);
        }
    }
}

