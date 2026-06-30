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
                $query->where('balance_quantity', '>', 0)
                      ->where('block_stock', 0)
                      ->where('hold_stock', 0);
            } elseif ($request->stock_status === 'blocked') {
                $query->where('block_stock', 1);
            } elseif ($request->stock_status === 'hold') {
                $query->where('hold_stock', 1);
            } elseif ($request->stock_status === 'stock_out') {
                $query->where('balance_quantity', 0);
            }
        }

        $items = $query->select(
                'product_id',
                DB::raw('SUM(units_received) as total_units'),
                DB::raw('SUM(total_quantity) as total_qty'),
                DB::raw('SUM(balance_quantity) as total_balance'),
                DB::raw('SUM(pallets_used) as total_pallets'),
                DB::raw('COUNT(id) as batch_count'),
                DB::raw('MAX(created_at) as latest_date')
            )
            ->groupBy('product_id')
            ->with(['product.category'])
            ->latest('product_id')
            ->paginate(20);

        // Get filter options
        $warehouses = Warehouse::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('opening_stock.index', compact('items', 'warehouses', 'products'));
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
            ->latest()
            ->get();

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
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',

            'items.*.product_id' => 'required|exists:products,id',
            'items.*.units_received' => 'required|integer|min:1',

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
            DB::transaction(function () use ($request) {

                /*
                |--------------------------------------------------------------------------
                | 1️⃣ Create Opening Stock Header
                |--------------------------------------------------------------------------
                */
                $stockIn = StockIn::create([
                    'source_type' => 'opening',
                    'warehouse_id' => $request->warehouse_id,
                    'shipment_type' => 'manual',
                    'remarks' => $request->remarks,
                ]);

                /*
                |--------------------------------------------------------------------------
                | 2️⃣ Validate Warehouse Pallet Capacity
                |--------------------------------------------------------------------------
                */
                $warehouse = Warehouse::findOrFail($request->warehouse_id);

                $totalPalletsUsedByNewItems = 0;

                foreach ($request->items as $item) {
                    if (!empty($item['product_id']) && !empty($item['units_received'])) {
                        $product = Product::find($item['product_id']);
                        $palletsNeeded = (int) ($item['pallets_used'] ?? 0);
                        if ($palletsNeeded === 0 && $product && $product->cartons_per_pallet > 0) {
                            $palletsNeeded = (int) ceil((int) $item['units_received'] / $product->cartons_per_pallet);
                        }
                        $totalPalletsUsedByNewItems += $palletsNeeded;
                    }
                }

                $freeRowCapacity = WarehouseRowFifo::getFreeRowCapacity($warehouse->id);

                if ($freeRowCapacity < $totalPalletsUsedByNewItems) {
                    throw new \Exception("Warehouse is full. Cannot add opening stock to {$warehouse->name}. Only {$freeRowCapacity} pallet slots available across all rows, but {$totalPalletsUsedByNewItems} needed.");
                }

                // Validate per-item: pallet count must be sufficient for cartons
                foreach ($request->items as $item) {
                    if (!empty($item['product_id']) && !empty($item['units_received'])) {
                        $product = Product::find($item['product_id']);
                        $pNeeded = (int) ($item['pallets_used'] ?? 0);
                        if ($pNeeded === 0 && $product && $product->cartons_per_pallet > 0) {
                            $pNeeded = (int) ceil((int) $item['units_received'] / $product->cartons_per_pallet);
                        }
                        if ($pNeeded > 0 && $product && $product->cartons_per_pallet > 0) {
                            $maxUnits = $pNeeded * $product->cartons_per_pallet;
                            if ((int) $item['units_received'] > $maxUnits) {
                                throw new \Exception(
                                    "Product {$product->name}: {$item['units_received']} cartons cannot fit in {$pNeeded} pallet(s) (max {$product->cartons_per_pallet} per pallet). Need " .
                                    ceil((int) $item['units_received'] / $product->cartons_per_pallet) . " pallets."
                                );
                            }
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | 3️⃣ Create Stock In Items (BATCH LEVEL) — FIFO Row Auto-Assignment
                |--------------------------------------------------------------------------
                */
                foreach ($request->items as $item) {

                    $product  = Product::findOrFail($item['product_id']);
                    $units    = (int) $item['units_received'];
                    $packSize = (float) $product->pack_size;

                    // Step 1: Fill partial pallets of the same product first
                    $partialResult = WarehouseRowFifo::fillPartials(
                        $warehouse->id,
                        $product->id,
                        $units,
                        $packSize,
                        (int) $product->cartons_per_pallet
                    );

                    // Update existing StockInItems for partial fills
                    foreach ($partialResult['splits'] as $split) {
                        $existingItem = StockInItem::find($split['stock_in_item_id']);
                        if ($existingItem) {
                            $existingItem->increment('units_received', $split['units']);
                            $existingItem->increment('total_quantity', $split['qty']);
                            $existingItem->increment('balance_quantity', $split['qty']);
                            $existingItem->decrement('last_pallet_vacant', $split['units']);
                        }
                    }

                    $remainingUnits = $partialResult['remaining_units'];

                    // Step 2: Handle remaining units with new pallet allocations
                    if ($remainingUnits > 0) {
                        $palletsNeeded = (int) ($item['pallets_used'] ?? 0);

                        if ($palletsNeeded === 0 && $product->cartons_per_pallet > 0) {
                            $palletsNeeded = (int) ceil($remainingUnits / $product->cartons_per_pallet);
                        }

                        if ($palletsNeeded > 0 && $product->cartons_per_pallet > 0) {
                            $maxUnits = $palletsNeeded * $product->cartons_per_pallet;
                            if ($remainingUnits > $maxUnits) {
                                throw new \Exception(
                                    "Product {$product->name}: {$remainingUnits} cartons cannot fit in {$palletsNeeded} pallet(s) (max {$product->cartons_per_pallet} per pallet)."
                                );
                            }
                        }

                        $splits = WarehouseRowFifo::assign(
                            $warehouse->id,
                            $palletsNeeded,
                            $remainingUnits,
                            $packSize,
                            true,
                            (int) $product->cartons_per_pallet
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
                                'warehouse_id'       => $warehouse->id,
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
                            ]);
                        }
                    }
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

        // Compute pallet positions across ALL stock items (opening + inbound) so positions match stock ledger
        $rowPalletOffsets = [];
        $openingPositions = []; // id => { pallet_start, pallet_end, row_letter, wh_padded }
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
            $palletStart = $rowPalletOffsets[$rowKey] + 1;
            $palletEnd = $rowPalletOffsets[$rowKey] + $palletCount;
            $rowPalletOffsets[$rowKey] = $palletEnd;
            if ($item->stockIn->source_type === 'opening') {
                $rw = $rowLetterMap[$rowKey] ?? '';
                $wp = str_pad($whId, 2, '0', STR_PAD_LEFT);
                $openingPositions[$item->id] = [
                    'start' => $palletStart, 'end' => $palletEnd,
                    'letter' => $rw, 'wh_padded' => $wp,
                ];
            }
        }

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

        $callback = function () use ($items, $openingPositions) {
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
                $balVal = $item->balance_quantity;
                $palletsVal = $item->pallets_used ?? 0;
                $dateVal = $item->created_at ? (method_exists($item->created_at, 'format') ? $item->created_at->format('d.m.Y H:i') : $item->created_at) : '';

                $pos = $openingPositions[$item->id] ?? null;

                if ($pos) {
                    $whPadded = $pos['wh_padded'];
                    $rowLetter = $pos['letter'];
                    $palletStart = $pos['start'];
                    $palletEnd = $pos['end'];

                    $maxPerPallet = $item->product->cartons_per_pallet ?? null;
                    $numPallets = $palletEnd - $palletStart + 1;
                    $totalUnits = (float) $item->units_received;
                    $totalQty = (float) $item->total_quantity;
                    $totalBalance = (float) $item->balance_quantity;
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
            return trim($row[$idx] ?? '');
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
                // Step 1: Pre-process items to identify existing records by Product ID if Record ID is missing
                foreach ($items as $k => $item) {
                    if (empty($item['record_id']) && $item['product']) {
                        $existing = StockInItem::where('product_id', $item['product']->id)
                            ->whereHas('stockIn', fn($q) => $q->where('source_type', 'opening'))
                            ->get();
                        // Only auto-match by Product ID if there is exactly 1 opening stock entry to avoid ambiguous overrides
                        if ($existing->count() === 1) {
                            $items[$k]['record_id'] = $existing->first()->id;
                        }
                    }
                }
                
                // Step 2: Aggregate items by Record ID
                $aggregatedItems = [];
                $newItems = [];
                foreach ($items as $item) {
                    $rid = $item['record_id'];
                    if (!empty($rid)) {
                        if (isset($aggregatedItems[$rid])) {
                            $aggregatedItems[$rid]['units'] += $item['units'];
                        } else {
                            $aggregatedItems[$rid] = $item;
                        }
                    } else {
                        $newItems[] = $item;
                    }
                }
                
                // Step 3: Update existing items (ONLY Opening Stock records)
                foreach ($aggregatedItems as $rid => $item) {
                    $existingItem = StockInItem::find($rid);
                    if ($existingItem) {
                        $packSize = (float) $existingItem->pack_size_snapshot;
                        if (!$packSize && $item['product']) $packSize = (float) $item['product']->pack_size;
                        if (!$packSize) $packSize = 1;

                        // Only update basic fields, isolated to Opening Stock
                        $updateData = [
                            'ibd_no'             => $item['ibd_no'] ?: null,
                            'po_no'              => $item['po_no'] ?: null,
                            'sap_batch'          => $item['sap_batch'] ?: null,
                            'vendor_batch'       => $item['vendor_batch'] ?: null,
                            'mfg_date'           => $item['mfg_date'] ?: null,
                            'expiry_date'        => $item['expiry_date'] ?: null,
                            'sound_stock'        => !$item['blocked'] && !$item['hold'],
                            'block_stock'        => $item['blocked'],
                            'hold_stock'         => $item['hold'],
                            'quality_clearance'  => $item['quality_clearance'],
                            'remarks'            => $item['remarks'] ?: null,
                        ];
                        
                        if ($existingItem->units_received != $item['units']) {
                            // If units changed, just update units and total_quantity directly
                            $updateData['units_received'] = $item['units'];
                            $newQty = round($item['units'] * $packSize, 4);
                            $updateData['total_quantity'] = $newQty;
                            $updateData['balance_quantity'] = $newQty;
                            
                            // Recalculate pallets automatically based on units and cartons_per_pallet
                            $cartonsPerPallet = (int) ($item['product']->cartons_per_pallet ?? 0);
                            if ($cartonsPerPallet > 0) {
                                $updateData['pallets_used'] = max(1, (int) ceil($item['units'] / $cartonsPerPallet));
                            } else {
                                $updateData['pallets_used'] = 1;
                            }
                        }

                        $existingItem->update($updateData);
                        $imported++;
                    } else {
                        $errors[] = "Record ID '{$rid}' not found in database. To import as a new entry, leave the Record ID column blank.";
                        $skipped++;
                    }
                }

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
                                $cartonsPerPallet
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
}
