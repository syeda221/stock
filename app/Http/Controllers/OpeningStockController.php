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
            $query->whereHas('stockIn', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
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

        $items = $query->with([
                'stockIn.warehouse',
                'product.category',
                'product.uom',
                'product.packingType',
                'warehouseRow',
            ])
            ->latest()
            ->paginate(20);

        // Get filter options
        $warehouses = Warehouse::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('opening_stock.index', compact('items', 'warehouses', 'products'));

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

                /*
                |--------------------------------------------------------------------------
                | 3️⃣ Create Stock In Items (BATCH LEVEL) — FIFO Row Auto-Assignment
                |--------------------------------------------------------------------------
                */
                foreach ($request->items as $item) {

                    $product  = Product::findOrFail($item['product_id']);
                    $units    = (int) $item['units_received'];
                    $packSize = (float) $product->pack_size;

                    // Calculate pallets for this item
                    $palletsNeeded = (int) ($item['pallets_used'] ?? 0);

                    // Auto-calculate if product has cartons_per_pallet set and pallet count is 0
                    if ($palletsNeeded === 0 && $product->cartons_per_pallet > 0) {
                        $palletsNeeded = (int) ceil($units / $product->cartons_per_pallet);
                    }

                    $splits = WarehouseRowFifo::assign(
                        $warehouse->id,
                        $palletsNeeded,
                        $units,
                        $packSize
                    );

                    foreach ($splits as $split) {
                        $splitUnits = $split['units'];
                        $splitQty   = round($splitUnits * $packSize, 4);

                        StockInItem::create([
                            'stock_in_id'        => $stockIn->id,
                            'product_id'         => $product->id,
                            'warehouse_id'       => $warehouse->id,
                            'warehouse_row_id'   => $split['warehouse_row_id'],

                            // Batch / Reference
                            'sap_batch'          => $item['sap_batch'] ?? null,
                            'vendor_batch'       => $item['vendor_batch'] ?? null,
                            'ibd_no'             => $item['ibd_no'] ?? null,
                            'po_no'              => $item['po_no'] ?? null,

                            // Dates
                            'mfg_date'           => $item['mfg_date'] ?? null,
                            'expiry_date'        => $item['expiry_date'] ?? null,

                            // Quantities
                            'units_received'     => $splitUnits,
                            'pack_size_snapshot' => $packSize,
                            'total_quantity'     => $splitQty,
                            'balance_quantity'   => $splitQty,

                            // Pallets
                            'use_pallets'        => $split['pallets'] > 0,
                            'pallets_used'       => $split['pallets'] > 0 ? $split['pallets'] : null,

                            // Stock Status
                            'sound_stock'        => ! empty($item['sound_stock']),
                            'block_stock'        => ! empty($item['block_stock']),
                            'hold_stock'         => ! empty($item['hold_stock']),
                            'quality_clearance'  => $item['quality_clearance'] ?? 'pending',

                            // Remarks
                            'remarks'            => $item['remarks'] ?? null,
                        ]);
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
                'Expiry Date', 'Balance Qty', 'Pallets Used', 'Quality Clearance',
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
                'Item Code', 'Units Received', 'SAP Batch', 'Vendor Batch',
                'MFG Date', 'Expiry Date', 'Quality Clearance',
                'Blocked', 'Hold', 'Remarks'
            ]);
            fputcsv($file, [
                'PRD001', '100', 'SAP-2024-001', 'VENDOR-BATCH-001',
                '2024-01-15', '2025-01-15', 'approved',
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
            'SAP Batch'      => ['SAP Batch', 'SAP Batch', 'sap_batch', 'SapBatch', 'Batch'],
            'Vendor Batch'   => ['Vendor Batch', 'Vendor Batch', 'vendor_batch', 'VendorBatch'],
            'MFG Date'       => ['MFG Date', 'MFG Date', 'mfg_date', 'MfgDate', 'Manufacturing Date'],
            'Expiry Date'    => ['Expiry Date', 'Expiry Date', 'expiry_date', 'ExpiryDate', 'Exp Date'],
            'Quality Clearance' => ['Quality Clearance', 'Quality Clearance', 'quality_clearance', 'QC'],
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
        if ($request->warehouse_id) {
            // User selected a specific warehouse
            $targetWarehouse = Warehouse::findOrFail($request->warehouse_id);
            $warehousePool = collect([$targetWarehouse]);
        } elseif ($csvHasWarehouse) {
            // CSV has Warehouse column — use it per-row; fall back to auto-assign
            $warehousePool = Warehouse::where('status', 1)->whereHas('rows')->orderBy('name')->get();
            if ($warehousePool->isEmpty()) {
                $warehousePool = Warehouse::where('status', 1)->orderBy('name')->get();
            }
        } else {
            // Auto-assign across warehouses with rows
            $warehousePool = Warehouse::where('status', 1)->whereHas('rows')->orderBy('name')->get();
            if ($warehousePool->isEmpty()) {
                $warehousePool = Warehouse::where('status', 1)->orderBy('name')->get();
            }
            if ($warehousePool->isEmpty()) {
                fclose($handle);
                return back()->with('error', 'No active warehouses found for auto-assignment.');
            }
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

            $items[] = [
                'product' => $product,
                'units' => (int) $units,
                'warehouse' => $rowWarehouse,
                'sap_batch' => $getCell($row, 'SAP Batch'),
                'vendor_batch' => $getCell($row, 'Vendor Batch'),
                'mfg_date' => $getCell($row, 'MFG Date'),
                'expiry_date' => $getCell($row, 'Expiry Date'),
                'quality_clearance' => in_array(strtolower($getCell($row, 'Quality Clearance')), ['approved', 'rejected']) ? strtolower($getCell($row, 'Quality Clearance')) : 'pending',
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
            DB::transaction(function () use ($warehousePool, $items, $csvHasWarehouse, &$imported, &$skipped, &$errors) {
                $whIndex = 0;

                foreach ($items as $item) {
                    $product = $item['product'];
                    $units = $item['units'];
                    $packSize = (float) $product->pack_size;
                    $palletsNeeded = 0;

                    if ($product->cartons_per_pallet > 0) {
                        $palletsNeeded = (int) ceil($units / $product->cartons_per_pallet);
                    }

                    // Determine target warehouse(s) for this item
                    if ($item['warehouse']) {
                        $targets = collect([$item['warehouse']]);
                    } else {
                        $targets = $warehousePool;
                    }

                    $assigned = false;
                    $attempts = 0;

                    while (!$assigned && $attempts < $targets->count()) {
                        $wh = $targets[$whIndex % $targets->count()];
                        $whIndex++;

                        $splits = WarehouseRowFifo::assign($wh->id, $palletsNeeded, $units, $packSize);

                        $hasSpace = false;
                        foreach ($splits as $s) {
                            if ($s['warehouse_row_id'] !== null) { $hasSpace = true; break; }
                        }
                        if ($wh->rows()->count() === 0) $hasSpace = true;

                        if ($hasSpace || $attempts >= $targets->count() - 1) {
                            $stockIn = StockIn::firstOrCreate(
                                [
                                    'source_type' => 'opening',
                                    'warehouse_id' => $wh->id,
                                    'remarks' => 'Imported via CSV on ' . now()->format('Y-m-d H:i'),
                                ],
                                ['shipment_type' => 'manual']
                            );

                            foreach ($splits as $split) {
                                $splitUnits = $split['units'];
                                $splitQty = round($splitUnits * $packSize, 4);

                                StockInItem::create([
                                    'stock_in_id' => $stockIn->id,
                                    'product_id' => $product->id,
                                    'warehouse_id' => $wh->id,
                                    'warehouse_row_id' => $split['warehouse_row_id'],
                                    'sap_batch' => $item['sap_batch'] ?: null,
                                    'vendor_batch' => $item['vendor_batch'] ?: null,
                                    'mfg_date' => $item['mfg_date'] ?: null,
                                    'expiry_date' => $item['expiry_date'] ?: null,
                                    'units_received' => $splitUnits,
                                    'pack_size_snapshot' => $packSize,
                                    'total_quantity' => $splitQty,
                                    'balance_quantity' => $splitQty,
                                    'use_pallets' => $split['pallets'] > 0,
                                    'pallets_used' => $split['pallets'] > 0 ? $split['pallets'] : null,
                                    'sound_stock' => !$item['blocked'] && !$item['hold'],
                                    'block_stock' => $item['blocked'],
                                    'hold_stock' => $item['hold'],
                                    'quality_clearance' => $item['quality_clearance'],
                                    'remarks' => $item['remarks'] ?: null,
                                ]);
                            }

                            $assigned = true;
                            $imported++;
                        }
                        $attempts++;
                    }

                    if (!$assigned) {
                        $errors[] = "No warehouse with space for '{$product->item_code}'";
                        $skipped++;
                    }
                }
            });

            $message = "Imported {$imported} product(s).";
            if ($csvHasWarehouse) $message .= " (warehouse from CSV)";
            elseif ($request->warehouse_id) $message .= " Warehouse: {$targetWarehouse->name}.";
            else $message .= " Auto-assigned.";
            if ($skipped > 0) $message .= " {$skipped} skipped.";
            if ($errors) $message .= " " . implode(' | ', array_slice($errors, 0, 10));

            return redirect()->route('opening-stock.index')->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
