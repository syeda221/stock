<?php

namespace App\Http\Controllers;

use App\Models\ArrivedFrom;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\Transporter;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\WarehouseRowFifo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboundController extends Controller
{
    /**
     * Inbound list (batch-wise)
     */
    public function index(Request $request)
    {
        $query = StockInItem::whereHas('stockIn', function ($q) {
            $q->where('source_type', 'inbound');
        });

        // Apply QC filter
        if ($request->filled('qc_status')) {
            $query->where('quality_clearance', $request->qc_status);
        }

        // Apply warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->whereHas('stockIn', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // Apply vendor filter
        if ($request->filled('vendor_id')) {
            $query->whereHas('stockIn', function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            });
        }

        // Apply product filter
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Apply product group filter
        if ($request->filled('product_group_id')) {
            $groupId = $request->product_group_id;
            $query->whereHas('product', function ($q) use ($groupId) {
                $q->where('product_group_id', $groupId);
            });
        }

        // Apply date filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $items = $query->with([
                'stockIn.warehouse',
                'stockIn.vendor',
                'stockIn.transporter',
                'stockIn.arrivedFrom',
                'product.category',
                'product.group',
                'product.uom',
                'product.packingType',
                'warehouseRow',
            ])
            ->latest()
            ->paginate(20);

        // Get filter options
        $warehouses = Warehouse::orderBy('name', 'asc')->get();
        $vendors = Vendor::orderBy('name', 'asc')->get();
        $products = Product::orderBy('name', 'asc')->get();
        $productGroups = ProductGroup::where('status', 1)->orderBy('name', 'asc')->get();

        return view('inbound.index', compact('items', 'warehouses', 'vendors', 'products', 'productGroups'));
    }

    private function generateDispatchedInvoiceNo(): string
    {
        $prefix = 'SPC-IBD-';

        $last = StockIn::whereNotNull('dispatched_invoice_no')
            ->where('dispatched_invoice_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('dispatched_invoice_no');

        if (! $last) {
            $candidate = $prefix.'000';

            // <---- In case 'SPC-IBD-000' already exists, keep incrementing until unique. ---->
            $counter = 0;
            while (StockIn::where('dispatched_invoice_no', $candidate)->exists()) {
                $counter++;
                $candidate = $prefix.str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
            }

            return $candidate;
        }

        $numericPart = substr($last, strlen($prefix));
        $nextNumber = is_numeric($numericPart) ? ((int) $numericPart + 1) : 1;

        //  <----- Ensure uniqueness even if numbers were manually edited. ------>
        $attempts = 0;
        do {
            $candidate = $prefix.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            $nextNumber++;
            $attempts++;
        } while (StockIn::where('dispatched_invoice_no', $candidate)->exists() && $attempts < 2000);

        if ($attempts >= 2000) {
            throw new \RuntimeException('Unable to generate unique dispatched invoice number');
        }

        return $candidate;
    }
    /**
     * Show inbound form
     */
    public function create()
    {
        $warehouses = Warehouse::where('status', 1)->with('rows')->orderBy('name')->get();

        $warehouseData = $warehouses->map(function ($w) {
            $usedPallets = StockInItem::where('warehouse_id', $w->id)
                ->where('balance_quantity', '>', 0)
                ->sum('pallets_used');
            $freePallets = $w->total_capacity ? max(0, $w->total_capacity - $usedPallets) : PHP_INT_MAX;
            return [
                'id' => $w->id,
                'name' => $w->name,
                'total_capacity' => $w->total_capacity,
                'used_pallets' => $usedPallets,
                'free_pallets' => $freePallets,
                'has_space' => $freePallets > 0,
            ];
        });

        $autoSelectId = $warehouseData->where('has_space', true)->sortByDesc('free_pallets')->first()['id'] ?? null;

        return view('inbound.create', [
            'warehouses' => $warehouses,
            'warehouseData' => $warehouseData,
            'autoSelectWarehouseId' => $autoSelectId,
            'products' => Product::where('status', 1)->orderBy('name')->get(),
            'vendors' => Vendor::where('status', 1)->orderBy('name')->get(),
            'transporters' => Transporter::where('status', 1)->orderBy('name')->get(),
            'arrivedFroms' => ArrivedFrom::where('status', 1)->orderBy('name')->get(),
            'nextDispatchedInvoiceNo' => $this->generateDispatchedInvoiceNo(),
        ]);
    }


//     private function generateInboundInvoiceNo()
// {
//     $last = StockIn::where('source_type', 'inbound')
//         ->orderBy('id', 'desc')
//         ->value('inbound_invoice_no');

//     if (!$last) {
//         return 'SPC-IBD-001';
//     }

//     $num = (int) substr($last, -3);
//     return 'SPC-IBD-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
// }


    /**
     * Store inbound stock
     */
    public function store(Request $request)
    {
        // dd($request->items);

            $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'transporter_id' => 'nullable|exists:transporters,id',

            'vehicle_in_time' => 'nullable|date',
            'vehicle_out_time' => 'nullable|date',
            'vehicle_no' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'driver_mobile' => 'nullable|string|max:30',

            'delivery_no' => 'nullable|string|max:80',
            'dispatched_invoice_no' => 'nullable|string|max:80',
            'dispatcher_sig' => 'nullable|string|max:255',
            'picker' => 'nullable|string|max:120',

            'shipment_type' => 'nullable|in:manual,auto',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.units_received' => 'nullable|integer|min:0',
            'items.*.quality_clearance' => 'nullable|in:pending,approved,rejected',
        ]);

        try {
            DB::transaction(function () use ($request, &$stockIn) {

                /** -----------------------------
                 *  1️⃣ Inbound Header
                 * ----------------------------- */
                $stockIn = StockIn::create([
                    'source_type' => 'inbound',
                    'warehouse_id' => $request->warehouse_id,
                    'inbound_invoice_no' => $this->generateDispatchedInvoiceNo(),
                    'vendor_id' => $request->vendor_id,
                    'arrived_from_id' => $request->arrived_from_id,
                    'transporter_id' => $request->transporter_id,

                    'po_no' => $request->po_no,
                    'ibd_no' => $request->ibd_no,
                    'shipment_no' => $request->shipment_no,
                    'sto_no' => $request->sto_no,

                    'vehicle_no' => $request->vehicle_no,
                    'vehicle_size' => $request->vehicle_size,
                    'vehicle_in_time' => $request->vehicle_in_time,
                    'vehicle_out_time' => $request->vehicle_out_time,

                    'driver_name' => $request->driver_name,
                    'driver_mobile' => $request->driver_mobile,

                    'delivery_no' => $request->delivery_no,
                    'dispatched_invoice_no' => $request->dispatched_invoice_no,
                    'dispatcher_sig' => $request->dispatcher_sig,
                    'picker' => $request->picker,

                    'shipment_type' => $request->shipment_type ?? 'manual',
                    'remarks' => $request->remarks,
                ]);

            /** -----------------------------
             * 2️⃣ Pallet Capacity Validation
             * ----------------------------- */
            $warehouse = Warehouse::findOrFail($request->warehouse_id);
            $totalPallets = 0;

            foreach ($request->items as $item) {
                if (!empty($item['product_id']) && !empty($item['units_received'])) {
                    $product = Product::find($item['product_id']);
                    $palletsNeeded = (int) ($item['pallets_used'] ?? 0);
                    if ($palletsNeeded === 0 && $product && $product->cartons_per_pallet > 0) {
                        $palletsNeeded = (int) ceil((int) $item['units_received'] / $product->cartons_per_pallet);
                    }
                    $totalPallets += $palletsNeeded;
                }
            }

            $freeRowCapacity = WarehouseRowFifo::getFreeRowCapacity($warehouse->id);

            if ($freeRowCapacity < $totalPallets) {
                throw new \Exception("Warehouse is full. Cannot inbound more stock to {$warehouse->name}. Only {$freeRowCapacity} pallet slots available across all rows, but {$totalPallets} needed.");
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

            /** -----------------------------
             *  3️⃣ Stock Items (Batch Wise) — FIFO Row Auto-Assignment
             * ----------------------------- */
            foreach ($request->items as $item) {
                if (empty($item['product_id']) || empty($item['units_received'])) continue;

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

                // Step 2: Handle remaining units with new pallets
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
                            'ibd_no'             => $item['ibd_no'] ?? $request->ibd_no,
                            'po_no'              => $item['po_no'] ?? $request->po_no,

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
                            'damage_stock'       => ! empty($item['damage_stock']),
                            'remarks'            => $item['remarks'] ?? null,
                            'uom_snapshot'       => optional($product->uom)->name,
                            'packing_snapshot'   => optional($product->packingType)->name,
                        ]);
                    }
                }
            }
        });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inbound stock added successfully.',
                    'redirect' => route('inbound.invoice', $stockIn) . '?print=1'
                ]);
            }

            return redirect()->route('inbound.invoice', $stockIn)
                ->with('success', 'Inbound stock added successfully.');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Print inbound receipt
     */
    public function print(StockIn $stockIn)
    {
        $stockIn->load([
            'warehouse',
            'vendor',
            'transporter',
            'arrivedFrom',
            'items.product',
        ]);

        return view('inbound.print', compact('stockIn'));
    }

    /**
     * Full inbound invoice view (screen + print button)
     */
    public function invoice(StockIn $stockIn)
    {
        $stockIn->load([
            'warehouse',
            'vendor',
            'transporter',
            'arrivedFrom',
            'items.product',
            'items.product.uom',
        ]);

        return view('inbound.invoice', compact('stockIn'));
    }

    /**
     * Edit inbound (Header and Items)
     */
    public function edit(StockIn $stockIn)
    {
        $stockIn->load(['warehouse', 'vendor', 'transporter', 'arrivedFrom', 'items.product', 'items.warehouseRow']);

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
                'mfg_date' => $first->mfg_date ? $first->mfg_date->format('Y-m-d') : null,
                'expiry_date' => $first->expiry_date ? $first->expiry_date->format('Y-m-d') : null,
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
                'damage_stock' => $first->damage_stock,
                'remarks' => $first->remarks,
                // store the IDs of the splits so we can clean them up if modified
                'split_ids' => $group->pluck('id')->join(','),
            ];
        })->values();

        $warehouses = Warehouse::where('status', 1)->with('rows')->orderBy('name')->get();
        $warehouseData = $warehouses->map(function ($w) {
            $usedPallets = StockInItem::where('warehouse_id', $w->id)
                ->where('balance_quantity', '>', 0)
                ->sum('pallets_used');
            $freePallets = $w->total_capacity ? max(0, $w->total_capacity - $usedPallets) : PHP_INT_MAX;
            return [
                'id' => $w->id,
                'name' => $w->name,
                'total_capacity' => $w->total_capacity,
                'used_pallets' => $usedPallets,
                'free_pallets' => $freePallets,
                'has_space' => $freePallets > 0,
            ];
        });

        return view('inbound.edit', [
            'stockIn' => $stockIn,
            'groupedItems' => $groupedItems,
            'warehouses' => $warehouses,
            'warehouseData' => $warehouseData,
            'products' => Product::where('status', 1)->orderBy('name')->get(),
            'vendors' => Vendor::where('status', 1)->orderBy('name')->get(),
            'transporters' => Transporter::where('status', 1)->orderBy('name')->get(),
            'arrivedFroms' => ArrivedFrom::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update inbound header and items
     */
    public function update(Request $request, StockIn $stockIn)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'arrived_from_id' => 'nullable|exists:arrived_froms,id',
            'transporter_id' => 'nullable|exists:transporters,id',

            'shipment_type' => 'nullable|in:manual,auto',

            'vehicle_in_time' => 'nullable|date',
            'vehicle_out_time' => 'nullable|date',
            'vehicle_no' => 'nullable|string|max:50',
            'vehicle_size' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'driver_mobile' => 'nullable|string|max:30',

            'po_no' => 'nullable|string|max:80',
            'ibd_no' => 'nullable|string|max:80',
            'shipment_no' => 'nullable|string|max:80',
            'sto_no' => 'nullable|string|max:80',
            'delivery_no' => 'nullable|string|max:80',

            'dispatched_invoice_no' => 'nullable|string|max:80',
            'dispatcher_sig' => 'nullable|string|max:255',
            'picker' => 'nullable|string|max:120',
            'remarks' => 'nullable|string|max:1000',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.units_received' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request, $stockIn) {
                // 1. Update Header
                $stockIn->update($request->only([
                    'warehouse_id', 'vendor_id', 'arrived_from_id', 'transporter_id',
                    'shipment_type', 'vehicle_in_time', 'vehicle_out_time', 'vehicle_no',
                    'vehicle_size', 'driver_name', 'driver_mobile', 'po_no', 'ibd_no',
                    'shipment_no', 'sto_no', 'delivery_no', 'dispatched_invoice_no',
                    'dispatcher_sig', 'picker', 'remarks'
                ]));

                $warehouse = Warehouse::findOrFail($request->warehouse_id);

                // Fetch original split IDs BEFORE modifying the database
                $allOriginalSplitIds = $stockIn->items()->pluck('id')->toArray();

                // Track which split IDs are submitted so we can delete removed ones
                $submittedSplitIds = [];

                // 2. Process Items
                foreach ($request->items as $itemData) {
                    if (empty($itemData['product_id']) || empty($itemData['units_received'])) continue;

                    $productId = $itemData['product_id'];
                    $product = Product::findOrFail($productId);
                    $newUnits = (float) $itemData['units_received'];
                    $packSize = (float) $product->pack_size;
                    $newQty = round($newUnits * $packSize, 4);
                    
                    $splitIdsStr = $itemData['split_ids'] ?? '';
                    $splitIds = array_filter(explode(',', $splitIdsStr));

                    if (!empty($splitIds)) {
                        // EXISTING ITEM
                        $submittedSplitIds = array_merge($submittedSplitIds, $splitIds);

                        $existingSplits = StockInItem::whereIn('id', $splitIds)->get();
                        
                        $oldTotalQty = round($existingSplits->sum('total_quantity'), 4);
                        $oldBalanceQty = round($existingSplits->sum('balance_quantity'), 4);
                        $isDispatched = ($oldTotalQty - $oldBalanceQty) > 0.001;

                        if ($isDispatched) {
                            // Enforce constraint: Cannot reduce units below what is already dispatched
                            $dispatchedQty = $oldTotalQty - $oldBalanceQty;
                            if ($newQty < $dispatchedQty) {
                                throw new \Exception("Cannot reduce product '{$product->name}' below dispatched quantity.");
                            }
                            // Dispatched items cannot be fully modified regarding their physical rows because they're tied to outbounds.
                            // We only allow updating text/status fields.
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
                                    'damage_stock' => !empty($itemData['damage_stock']),
                                ]);
                            }
                        } else {
                            // Item NOT dispatched. We can safely delete old splits and re-create them with FIFO
                            StockInItem::whereIn('id', $splitIds)->delete();
                            
                            $this->createItemSplits($stockIn, $warehouse, $product, $itemData);
                        }

                    } else {
                        // NEW ITEM
                        $this->createItemSplits($stockIn, $warehouse, $product, $itemData);
                    }
                }

                // 3. Delete Removed Items
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

            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inbound updated successfully.',
                    'redirect' => route('inbound.invoice', $stockIn) . '?print=1'
                ]);
            }

            return redirect()->route('inbound.invoice', $stockIn)
                ->with('success', 'Inbound updated successfully.');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Helper to create FIFO split items
     */
    private function createItemSplits($stockIn, $warehouse, $product, $itemData)
    {
        $units = (float) $itemData['units_received'];
        $packSize = (float) $product->pack_size;

        // Step 1: Fill partial pallets first
        $partialResult = \App\Services\WarehouseRowFifo::fillPartials(
            $warehouse->id,
            $product->id,
            $units,
            $packSize,
            (int) $product->cartons_per_pallet
        );

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

        // Step 2: New pallets for remaining units
        if ($remainingUnits > 0) {
            $palletsNeeded = (int) ($itemData['pallets_used'] ?? 0);

            if ($palletsNeeded === 0 && $product->cartons_per_pallet > 0) {
                $palletsNeeded = (int) ceil($remainingUnits / $product->cartons_per_pallet);
            }

            $splits = \App\Services\WarehouseRowFifo::assign($warehouse->id, $palletsNeeded, $remainingUnits, $packSize);

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
                    'last_pallet_vacant' => $lastVacant,
                    'sound_stock'        => !empty($itemData['sound_stock']),
                    'block_stock'        => !empty($itemData['block_stock']),
                    'hold_stock'         => !empty($itemData['hold_stock']),
                    'quality_clearance'  => $itemData['quality_clearance'] ?? 'pending',
                    'damage_stock'       => !empty($itemData['damage_stock']),
                    'remarks'            => $itemData['remarks'] ?? null,
                    'uom_snapshot'       => optional($product->uom)->name,
                    'packing_snapshot'   => optional($product->packingType)->name,
                ]);
            }
        }
    }
}
