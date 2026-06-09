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
                DB::raw('COUNT(id) as batch_count')
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

                $usedPallets = StockInItem::where('warehouse_id', $warehouse->id)
                    ->where('balance_quantity', '>', 0)
                    ->sum('pallets_used');

                $freePallets = $warehouse->total_capacity ? max(0, $warehouse->total_capacity - $usedPallets) : PHP_INT_MAX;

                if ($freePallets < $totalPalletsUsedByNewItems) {
                    throw new \Exception("Warehouse is full. Cannot add opening stock to {$warehouse->name}. Only {$freePallets} pallet slots available, but {$totalPalletsUsedByNewItems} needed.");
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
                            $packSize
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

                // Check capacity (excluding the current item)
                $usedPallets = StockInItem::where('warehouse_id', $warehouse->id)
                    ->where('id', '!=', $item->id)
                    ->where('balance_quantity', '>', 0)
                    ->sum('pallets_used');

                $freePallets = $warehouse->total_capacity ? max(0, $warehouse->total_capacity - $usedPallets) : PHP_INT_MAX;

                if ($freePallets < $palletsNeeded) {
                    throw new \Exception("Warehouse is full. Cannot update opening stock. Only {$freePallets} pallet slots available, but {$palletsNeeded} needed.");
                }

                // Auto-assignment of Row
                $splits = WarehouseRowFifo::assign(
                    $warehouse->id,
                    $palletsNeeded,
                    $units,
                    $packSize
                );

                $assignedRowId = count($splits) > 0 ? $splits[0]['warehouse_row_id'] : null;

                // Update the item
                $item->update([
                    'warehouse_id'       => $warehouse->id,
                    'product_id'         => $product->id,
                    'warehouse_row_id'   => $assignedRowId,
                    'units_received'     => $units,
                    'pack_size_snapshot' => $packSize,
                    'total_quantity'     => $totalQty,
                    'balance_quantity'   => $totalQty, // Reset balance quantity to new total quantity
                    'use_pallets'        => $palletsNeeded > 0,
                    'pallets_used'       => $palletsNeeded > 0 ? $palletsNeeded : null,
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
        $items = StockInItem::whereHas('stockIn', fn($q) => $q->where('source_type', 'opening'))
            ->with(['product.category', 'product.uom', 'product.packingType', 'warehouse', 'stockIn'])
            ->latest()
            ->get();

        $filename = 'opening_stock_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Item Code', 'Product Name', 'Warehouse', 'Category', 'UOM',
                'IBD', 'PO', 'Vendor Batch', 'SAP Batch', 'Packing',
                'Pack Size', 'Units Received', 'Total Qty', 'MFG Date',
                'Expiry Date', 'Balance Qty', 'Pallets Used', 'Quality Check',
                'Sound', 'Blocked', 'Hold'
            ]);

            foreach ($items as $item) {
                fputcsv($file, [
                    $item->product->item_code ?? '',
                    $item->product->name ?? '',
                    $item->warehouse->name ?? '',
                    $item->product->category->name ?? '',
                    $item->product->uom->name ?? '',
                    $item->ibd_no ?? '',
                    $item->po_no ?? '',
                    $item->vendor_batch ?? '',
                    $item->sap_batch ?? '',
                    $item->product->packingType->name ?? '',
                    $item->pack_size_snapshot,
                    $item->units_received,
                    $item->total_quantity,
                    $item->mfg_date ? (method_exists($item->mfg_date, 'format') ? $item->mfg_date->format('Y-m-d') : $item->mfg_date) : '',
                    $item->expiry_date ? (method_exists($item->expiry_date, 'format') ? $item->expiry_date->format('Y-m-d') : $item->expiry_date) : '',
                    $item->balance_quantity,
                    $item->pallets_used ?? 0,
                    $item->quality_clearance ?? '',
                    $item->sound_stock ? 'Yes' : 'No',
                    $item->block_stock ? 'Yes' : 'No',
                    $item->hold_stock ? 'Yes' : 'No',
                ]);
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
                'Item Code', 'Units Received', 'IBD', 'PO', 'SAP Batch', 'Vendor Batch',
                'MFG Date', 'Expiry Date', 'Pallets Used', 'Quality Check',
                'Blocked', 'Hold', 'Remarks'
            ]);
            fputcsv($file, [
                'PRD001', '100', 'IBD-001', 'PO-001', 'SAP-2024-001', 'VENDOR-BATCH-001',
                '2024-01-15', '2025-01-15', '5', 'approved',
                'No', 'No', 'Initial opening stock'
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
            'Item Code'      => ['Item Code', 'Item Code', 'item_code', 'ItemCode', 'Code'],
            'Units Received' => ['Units Received', 'Units Received', 'units_received', 'Units'],
            'IBD'            => ['IBD', 'ibd', 'ibd_no', 'IBD No'],
            'PO'             => ['PO', 'po', 'po_no', 'PO No'],
            'SAP Batch'      => ['SAP Batch', 'SAP Batch', 'sap_batch', 'SapBatch', 'Batch'],
            'Vendor Batch'   => ['Vendor Batch', 'Vendor Batch', 'vendor_batch', 'VendorBatch'],
            'Pallets Used'   => ['Pallets Used', 'pallets_used', 'Pallets'],
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

            $items[] = [
                'product' => $product,
                'units' => (int) $units,
                'warehouse' => $rowWarehouse,
                'ibd_no' => $getCell($row, 'IBD'),
                'po_no' => $getCell($row, 'PO'),
                'sap_batch' => $getCell($row, 'SAP Batch'),
                'vendor_batch' => $getCell($row, 'Vendor Batch'),
                'pallets_used' => $getCell($row, 'Pallets Used'),
                'mfg_date' => $getCell($row, 'MFG Date'),
                'expiry_date' => $getCell($row, 'Expiry Date'),
                'quality_clearance' => $qcValue,
                'blocked' => in_array(strtolower($getCell($row, 'Blocked')), ['yes', '1', 'true']),
                'hold' => in_array(strtolower($getCell($row, 'Hold')), ['yes', '1', 'true']),
                'remarks' => $getCell($row, 'Remarks'),
            ];
        }

        fclose($handle);

        if (count($items) === 0) {
            return back()->with('error', 'No valid rows found in CSV');
        }

        try {
            DB::transaction(function () use ($warehousePool, $items, $csvHasWarehouse, $request, &$imported, &$skipped, &$errors) {

                foreach ($items as $item) {
                    $product       = $item['product'];
                    $units         = $item['units'];
                    $packSize      = (float) $product->pack_size;
                    $palletsNeeded = 0;

                    if ($item['pallets_used'] !== '') {
                        $palletsNeeded = (int) $item['pallets_used'];
                    } elseif ($product->cartons_per_pallet > 0) {
                        $palletsNeeded = (int) ceil($units / $product->cartons_per_pallet);
                    }

                    if ($palletsNeeded > 0 && $product->cartons_per_pallet > 0) {
                        $maxUnits = $palletsNeeded * $product->cartons_per_pallet;
                        if ($units > $maxUnits) {
                            $correctPallets = (int) ceil($units / $product->cartons_per_pallet);
                            throw new \Exception(
                                "Row: {$product->name}: {$units} cartons cannot fit in {$palletsNeeded} pallet(s) (max {$product->cartons_per_pallet} per pallet). Need {$correctPallets} pallets."
                            );
                        }
                    }

                    // Determine target warehouses for this item
                    if ($item['warehouse']) {
                        // CSV specified a warehouse — only try that one
                        $targets = collect([$item['warehouse']]);
                    } else {
                        // Use the pool (specific warehouse or all for auto-assign)
                        $targets = $warehousePool;
                    }

                    $assigned = false;

                    foreach ($targets as $wh) {
                        // ── Check warehouse pallet capacity BEFORE assigning ──
                        $usedPallets = StockInItem::where('warehouse_id', $wh->id)
                            ->where('balance_quantity', '>', 0)
                            ->sum('pallets_used');

                        $freePallets = $wh->total_capacity > 0
                            ? max(0, $wh->total_capacity - $usedPallets)
                            : PHP_INT_MAX; // No capacity set = unlimited

                        if ($palletsNeeded > 0 && $freePallets < $palletsNeeded) {
                            // Not enough space in this warehouse — try next (auto-assign) or error (specific)
                            continue;
                        }

                        // ── Warehouse has enough space — assign via FIFO ──
                        $splits = WarehouseRowFifo::assign(
                            $wh->id,
                            $palletsNeeded,
                            $units,
                            $packSize,
                            false  // NEVER allow overflow
                        );

                        $stockIn = StockIn::firstOrCreate(
                            [
                                'source_type'  => 'opening',
                                'warehouse_id' => $wh->id,
                                'remarks'      => 'Imported via CSV on ' . now()->format('Y-m-d H:i'),
                            ],
                            ['shipment_type' => 'manual']
                        );

                        foreach ($splits as $split) {
                            if ($split['units'] <= 0) continue;

                            $splitUnits = $split['units'];
                            $splitQty   = round($splitUnits * $packSize, 4);

                            $lastVacant = $product->cartons_per_pallet > 0
                                ? max(0, ($split['pallets'] * $product->cartons_per_pallet) - $splitUnits)
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
                                'last_pallet_vacant' => $lastVacant,
                                'sound_stock'        => !$item['blocked'] && !$item['hold'],
                                'block_stock'        => $item['blocked'],
                                'hold_stock'         => $item['hold'],
                                'quality_clearance'  => $item['quality_clearance'],
                                'remarks'            => $item['remarks'] ?: null,
                            ]);
                        }

                        $assigned = true;
                        break; // Successfully assigned — don't try more warehouses
                    }

                    if ($assigned) {
                        $imported++;
                    } else {
                        // Build a clear error message
                        if ($targets->count() === 1) {
                            $whName = $targets->first()->name;
                            $usedP  = StockInItem::where('warehouse_id', $targets->first()->id)
                                ->where('balance_quantity', '>', 0)->sum('pallets_used');
                            $freeP  = $targets->first()->total_capacity > 0
                                ? max(0, $targets->first()->total_capacity - $usedP) : 0;
                            $errors[] = "Warehouse '{$whName}' is full — '{$product->item_code}' needs {$palletsNeeded} pallets but only {$freeP} available";
                        } else {
                            $errors[] = "No warehouse has enough space for '{$product->item_code}' ({$palletsNeeded} pallets needed)";
                        }
                        $skipped++;
                    }
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
