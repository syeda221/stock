<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\Warehouse;
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

                $totalPalletsUsed = 0;

                foreach ($request->items as $item) {
                    if (! empty($item['use_pallets']) && ! empty($item['pallets_used'])) {
                        $totalPalletsUsed += (int) $item['pallets_used'];
                    }
                }

                if ($totalPalletsUsed > $warehouse->total_capacity) {
                    throw new \Exception('Total pallets exceed warehouse capacity.');
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
}
