<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\StockOut;
use App\Models\StockOutItem;
use App\Models\Transporter;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutboundController extends Controller
{
    private function generateDispatchedInvoiceNo(): string
    {
        $prefix = 'SPC-OBD-';

        $last = StockOut::whereNotNull('dispatched_invoice_no')
            ->where('dispatched_invoice_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('dispatched_invoice_no');

        if (! $last) {
            $candidate = $prefix.'000';

            $counter = 0;
            while (StockOut::where('dispatched_invoice_no', $candidate)->exists()) {
                $counter++;
                $candidate = $prefix.str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
            }

            return $candidate;
        }

        $numericPart = substr($last, strlen($prefix));
        $nextNumber = is_numeric($numericPart) ? ((int) $numericPart + 1) : 1;

        $attempts = 0;
        do {
            $candidate = $prefix.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            $nextNumber++;
            $attempts++;
        } while (StockOut::where('dispatched_invoice_no', $candidate)->exists() && $attempts < 2000);

        if ($attempts >= 2000) {
            throw new \RuntimeException('Unable to generate unique dispatched invoice number');
        }

        return $candidate;
    }

    /* ================= LIST ================= */
    public function index(Request $request)
    {
        $query = StockOutItem::with([
            'stockOut.warehouse',
            'stockOut.customer',
            'stockOut.toWarehouse',
            'stockOut.transporter',
            'product.group',
        ]);

        // Apply source type filter
        if ($request->filled('source_type')) {
            $query->whereHas('stockOut', function ($q) use ($request) {
                $q->where('source_type', $request->source_type);
            });
        }

        // Apply warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->whereHas('stockOut', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // Apply customer filter
        if ($request->filled('customer_id')) {
            $query->whereHas('stockOut', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
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

        $items = $query->latest()->paginate(20);

        // Calculate free pallets for warehouses in the current result set
        $warehouseIds = $items->pluck('warehouse_id')->unique()->filter();
        $warehouseCapacities = [];
        
        foreach ($warehouseIds as $wid) {
            $wh = Warehouse::find($wid);
            if ($wh) {
                $totalCapacity = $wh->total_capacity ?: $wh->rows()->sum('pallet_capacity');
                $usedPallets = StockInItem::with('product')
                    ->where('warehouse_id', $wid)
                    ->where('balance_quantity', '>', 0)
                    ->get()
                    ->sum(fn($i) => StockInItem::computeActivePallets($i));
                $warehouseCapacities[$wid] = max(0, $totalCapacity - $usedPallets);
            }
        }

        $productGroups = ProductGroup::where('status', 1)->orderBy('name')->get();

        return view('outbound.index', compact('items', 'warehouseCapacities', 'productGroups'));
    }

    /* ================= CREATE ================= */
    public function create()
    {
        return view('outbound.create', [
            'warehouses'   => Warehouse::orderBy('name')->get(),
            'customers'    => Customer::orderBy('name')->get(),
            'products'     => Product::orderBy('name')->get(),
            'transporters' => Transporter::orderBy('name')->get(),
            'nextDispatchedInvoiceNo' => $this->generateDispatchedInvoiceNo(),
        ]);
    }

    /* ================= PRODUCT STOCK (AJAX) ================= */
    public function productStock(Request $request, $productId)
    {
        $warehouseId = $request->query('warehouse_id');
        $product = Product::find($productId);
        $palletsPerPacking = $product ? ($product->cartons_per_pallet ?? null) : null;

        $query = StockInItem::with(['warehouse', 'warehouseRow'])
            ->where('product_id', $productId)
            ->where('balance_quantity', '>', 0.001);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // For customer sales, exclude expired, blocked, and rejected stock
        $outboundType = $request->query('outbound_type');
        if ($outboundType === 'customer') {
            $query->where('block_stock', false)
                  ->where('quality_clearance', '!=', 'rejected')
                  ->where(function ($q) {
                      $q->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>=', now()->toDateString());
                  });
        }

        $items = $query->orderBy('warehouse_id')
            ->orderBy('expiry_date')
            ->orderBy('created_at') // FIFO within same expiry
            ->get();

        $data = $items->groupBy('warehouse_id')
            ->map(function ($group) use ($palletsPerPacking) {
                return [
                    'warehouse_id'       => $group->first()->warehouse_id,
                    'warehouse'          => $group->first()->warehouse->name,
                    'total_stock'        => $group->sum('balance_quantity'),
                    'cartons_per_pallet' => $palletsPerPacking,
                    'batches'            => $group->map(function ($b) {
                        return [
                            'id'        => $b->id,
                            'row'       => optional($b->warehouseRow)->name,
                            'batch'     => $b->sap_batch ?? $b->vendor_batch ?? 'NO-BATCH',
                            'available' => $b->balance_quantity,
                            'pack'      => $b->pack_size_snapshot,
                            'po_no'     => $b->po_no,
                            'ibd_no'    => $b->ibd_no,
                            'mfg'       => $b->mfg_date,
                            'expiry'    => $b->expiry_date,
                        ];
                    })->values(),
                ];
            })->values();

        return response()->json($data);
    }

    /* ================= STORE ================= */
    public function store(Request $request)
    {
        $request->validate([
            'outbound_type'               => 'required|in:warehouse,customer',
            'customer_id'                 => 'required_if:outbound_type,customer|nullable',
            'to_warehouse_id'             => 'required_if:outbound_type,warehouse|nullable',
            'shipment_type'               => 'nullable|string',
            'dispatched_invoice_no'        => 'nullable|string|max:80',
            'items'                       => 'required|array|min:1',
            'items.*.product_id'          => 'required|exists:products,id',
            'items.*.warehouse_id'        => 'required|exists:warehouses,id',
            'items.*.units_dispatch'      => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($request) {

                if ($request->filled('dispatched_invoice_no')
                    && StockOut::where('dispatched_invoice_no', $request->dispatched_invoice_no)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'dispatched_invoice_no' => 'Dispatched invoice no already exists.',
                    ]);
                }

                /* ========= SAFE FIRST ITEM ========= */
                $firstItem = collect($request->items)->first();

                if (! $firstItem || empty($firstItem['product_id'])) {
                    throw new \Exception('Invalid items data');
                }

                if ($request->outbound_type === 'warehouse') {
                    $destinationWarehouse = Warehouse::findOrFail($request->to_warehouse_id);
                    if ($destinationWarehouse->total_capacity > 0) {
                        $totalPalletsNeeded = 0;
                        foreach ($request->items as $it) {
                            $totalPalletsNeeded += (int) ($it['pallets_returned'] ?? 0);
                        }

                        $usedPallets = \App\Models\StockInItem::with('product')
                            ->where('warehouse_id', $destinationWarehouse->id)
                            ->where('balance_quantity', '>', 0)
                            ->get()
                            ->sum(fn($i) => \App\Models\StockInItem::computeActivePallets($i));
                        $freePallets = max(0, $destinationWarehouse->total_capacity - $usedPallets);

                        if ($freePallets < $totalPalletsNeeded) {
                            throw new \Exception("Warehouse is full. Cannot transfer more stock to {$destinationWarehouse->name}. Only {$freePallets} pallet slots available, but {$totalPalletsNeeded} needed.");
                        }
                    }
                }

                /* ========= AUTO DISPATCH INVOICE (UNIQUE) ========= */
                $invoiceNo = $request->dispatched_invoice_no ?: $this->generateDispatchedInvoiceNo();

                /* ========= OUTBOUND HEADER ========= */
                $stockOut = StockOut::create([
                    'source_type'          => $request->outbound_type === 'customer' ? 'sale' : 'transfer',
                    'warehouse_id'         => $firstItem['warehouse_id'], // Representative WH
                    'to_warehouse_id'      => $request->to_warehouse_id,
                    'customer_id'          => $request->customer_id,
                    'transporter_id'       => $request->transporter_id,
                    'shipment_type'        => $request->shipment_type,
                    'dispatched_invoice_no'=> $invoiceNo,
                    'dispatcher_sig'       => $request->dispatcher_sig,
                    'picker'               => $request->picker,
                    'da_no'                => $request->da_no,
                    'vehicle_no'           => $request->vehicle_no,
                    'vehicle_size'         => $request->vehicle_size,
                    'driver_name'          => $request->driver_name,
                    'driver_mobile'        => $request->driver_mobile,
                    'vehicle_in_time'      => $request->vehicle_in_time,
                    'vehicle_out_time'     => $request->vehicle_out_time,
                    'remarks'              => $request->remarks,
                ]);

                $inbound = null;

                foreach ($request->items as $it) {
                    $productId = $it['product_id'];
                    $itWarehouseId = $it['warehouse_id'];
                    $unitsRemaining = (int) $it['units_dispatch'];
                    
                    // User provided hints
                    $userPo = $it['po_no'] ?? null;
                    $userIbd = $it['ibd_no'] ?? null;
                    $stoNo = $it['sto_no'] ?? null;

                    // Find batches for this product in SPECIFIED warehouse for this item
                    $batchQuery = StockInItem::where('product_id', $productId)
                        ->where('warehouse_id', $itWarehouseId)
                        ->where('balance_quantity', '>', 0);

                    // For customer sales, exclude blocked, rejected, and expired stock
                    if ($request->outbound_type === 'customer') {
                        $batchQuery->where('block_stock', false)
                              ->where('quality_clearance', '!=', 'rejected')
                              ->where(function ($q) {
                                  $q->whereNull('expiry_date')
                                    ->orWhere('expiry_date', '>=', now()->toDateString());
                              });
                    }

                    $batches = $batchQuery->orderBy('expiry_date', 'asc')
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    if ($batches->isEmpty()) {
                        $productName = Product::find($productId)->name ?? $productId;

                        // Check if any stock exists before filtering
                        $anyStockExists = StockInItem::where('product_id', $productId)
                            ->where('warehouse_id', $itWarehouseId)
                            ->where('balance_quantity', '>', 0)
                            ->exists();

                        if ($anyStockExists && $request->outbound_type === 'customer') {
                            throw new \Exception("Cannot dispatch product '{$productName}'. Available stock is expired, blocked, or rejected.");
                        }

                        throw new \Exception("No valid stock available for product '{$productName}' in selected warehouse.");
                    }

                    $totalUnitsForThisItem = (int) $it['units_dispatch'];
                    $totalPalletsForThisItem = (int) ($it['pallets_returned'] ?? 0);
                    $palletsDistributed = 0;

                    foreach ($batches as $batch) {
                        if ($unitsRemaining <= 0) break;

                        $pack = (float) $batch->pack_size_snapshot;
                        if ($pack <= 0) continue;

                        $batchAvailableUnits = floor($batch->balance_quantity / $pack);
                        if ($batchAvailableUnits <= 0) continue;

                        $unitsToTake = min($unitsRemaining, $batchAvailableUnits);
                        $qtyToTake = $unitsToTake * $pack;

                        // Deduct from source batch using Eloquent (ensures transaction participation)
                        $batch->decrement('balance_quantity', $qtyToTake);

                        // Proportional pallets for this batch deduction
                        $palletsToTake = 0;
                        if ($totalUnitsForThisItem > 0) {
                            if ($unitsToTake == $unitsRemaining) {
                                // Last batch for this item takes remaining pallets
                                $palletsToTake = max(0, $totalPalletsForThisItem - $palletsDistributed);
                            } else {
                                $palletsToTake = (int) round($totalPalletsForThisItem * ($unitsToTake / $totalUnitsForThisItem));
                            }
                        }
                        $palletsDistributed += $palletsToTake;

                        /* ===== CREATE OUTBOUND ITEMS (one per pallet consumed) ===== */
                        $effectivePallets = max(1, $palletsToTake);
                        $perPalletUnits = $unitsToTake / $effectivePallets;
                        $perPalletQty = $qtyToTake / $effectivePallets;
                        $palletUnitsRemaining = $unitsToTake;
                        $palletQtyRemaining = $qtyToTake;

                        for ($p = 0; $p < $effectivePallets; $p++) {
                            $isLastPallet = ($p === $effectivePallets - 1);
                            $thisUnits = $isLastPallet ? $palletUnitsRemaining : round($perPalletUnits);
                            $thisQty = $isLastPallet ? $palletQtyRemaining : round($perPalletQty, 3);
                            $palletUnitsRemaining -= $thisUnits;
                            $palletQtyRemaining -= $thisQty;

                            StockOutItem::create([
                                'stock_out_id'        => $stockOut->id,
                                'stock_in_item_id'    => $batch->id,
                                'product_id'          => $batch->product_id,
                                'warehouse_id'        => $batch->warehouse_id,
                                'warehouse_row_id'    => $batch->warehouse_row_id,
                                'sap_batch'           => $batch->sap_batch,
                                'vendor_batch'        => $batch->vendor_batch,
                                'units_dispatch'      => $thisUnits,
                                'pack_size_snapshot'  => $pack,
                                'dispatch_quantity'   => $thisQty,
                                'po_no'               => $userPo ?: $batch->po_no,
                                'ibd_no'              => $userIbd ?: $batch->ibd_no,
                                'sto_no'              => $stoNo,
                                'mfg_date'            => $batch->mfg_date,
                                'expiry_date'         => $batch->expiry_date,
                                'pallets_returned'    => 1,
                                'pallet_position'     => $palletsToTake > 0 ? ($p + 1) : null,
                            ]);
                        }

                        /* ===== TRANSFER → CREATE INBOUND ITEM ===== */
                        if ($request->outbound_type === 'warehouse') {
                            if (! $inbound) {
                                $inbound = StockIn::create([
                                    'source_type'   => 'transfer',
                                    'warehouse_id'  => $request->to_warehouse_id,
                                    'shipment_type' => 'manual',
                                    'remarks'       => 'Transfer from warehouse '.$batch->warehouse_id,
                                ]);
                            }

                            // Use WarehouseRowFifo to assign proper rows in destination warehouse
                            $splits = \App\Services\WarehouseRowFifo::assign(
                                (int) $request->to_warehouse_id,
                                $palletsToTake,
                                $unitsToTake,
                                $pack
                            );

                            foreach ($splits as $split) {
                                StockInItem::create([
                                    'stock_in_id'        => $inbound->id,
                                    'product_id'         => $batch->product_id,
                                    'warehouse_id'       => $request->to_warehouse_id,
                                    'warehouse_row_id'   => $split['warehouse_row_id'],
                                    'sap_batch'          => $batch->sap_batch,
                                    'vendor_batch'       => $batch->vendor_batch,
                                    'mfg_date'           => $batch->mfg_date,
                                    'expiry_date'        => $batch->expiry_date,
                                    'units_received'     => $split['units'],
                                    'pack_size_snapshot' => $pack,
                                    'total_quantity'     => $split['qty'],
                                    'balance_quantity'   => $split['qty'],
                                    'pallets_used'       => $split['pallets'] > 0 ? $split['pallets'] : null,
                                    'use_pallets'        => $split['pallets'] > 0,
                                    'pallet_start'       => $split['pallet_start'] ?? null,
                                    'last_pallet_vacant' => 0,
                                    'quality_clearance'  => $batch->quality_clearance ?? 'approved',
                                    'sound_stock'        => $batch->sound_stock ?? true,
                                    'block_stock'        => $batch->block_stock ?? false,
                                    'hold_stock'         => $batch->hold_stock ?? false,
                                    'po_no'              => $userPo ?: $batch->po_no,
                                    'ibd_no'             => $userIbd ?: $batch->ibd_no,
                                ]);
                            }
                        }

                        $unitsRemaining -= $unitsToTake;
                    }

                    if ($unitsRemaining > 0) {
                        $productName = Product::find($productId)->name ?? $productId;
                        throw new \Exception("Insufficient stock for product '$productName'. Needed $unitsRemaining more units.");
                    }
                }
            });

            \Illuminate\Support\Facades\Log::info("Transaction COMMITTED successfully");

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Outbound & Dispatch completed successfully',
                    'redirect' => route('outbound.index')
                ]);
            }

            return redirect()
                ->route('outbound.index')
                ->with('success', 'Outbound & Dispatch completed successfully');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Outbound Transaction Failed: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'System Error: ' . $e->getMessage()]);
        }
    }

    /* ================= EDIT ================= */
    public function edit(StockOut $stockOut)
    {
        $stockOut->load([
            'warehouse',
            'toWarehouse',
            'customer',
            'transporter',
            'items.product',
            'items.product.uom',
        ]);

        $groupedItems = $stockOut->items->groupBy(function($item) {
            return $item->product_id . '_' . $item->warehouse_id;
        })->map(function($group) {
            $first = $group->first();
            return [
                'product_id' => $first->product_id,
                'product_name' => optional($first->product)->name,
                'item_code' => optional($first->product)->item_code,
                'warehouse_id' => $first->warehouse_id,
                'warehouse_name' => optional($first->warehouse)->name,
                'units_dispatch' => $group->sum('units_dispatch'),
                'pack_size' => $first->pack_size_snapshot,
                'total_qty' => $group->sum('dispatch_quantity'),
                'po_no' => $first->po_no,
                'ibd_no' => $first->ibd_no,
                'sto_no' => $first->sto_no,
                'pallets_returned' => $group->sum('pallets_returned'),
            ];
        })->values();

        $warehouses = Warehouse::where('status', 1)->orderBy('name')->get();
        $customers = Customer::where('status', 1)->orderBy('name')->get();
        $transporters = Transporter::where('status', 1)->orderBy('name')->get();

        return view('outbound.edit', [
            'stockOut' => $stockOut,
            'groupedItems' => $groupedItems,
            'warehouses' => $warehouses,
            'customers' => $customers,
            'transporters' => $transporters,
            'products' => Product::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    /* ================= UPDATE ================= */
    public function update(Request $request, StockOut $stockOut)
    {
        $request->validate([
            'outbound_type'               => 'required|in:warehouse,customer',
            'customer_id'                 => 'required_if:outbound_type,customer|nullable',
            'to_warehouse_id'             => 'required_if:outbound_type,warehouse|nullable',
            'shipment_type'               => 'nullable|string',
            'dispatched_invoice_no'        => 'nullable|string|max:80',
            'items'                       => 'required|array|min:1',
            'items.*.product_id'          => 'required|exists:products,id',
            'items.*.warehouse_id'        => 'required|exists:warehouses,id',
            'items.*.units_dispatch'      => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($request, $stockOut) {

                if ($request->filled('dispatched_invoice_no')
                    && StockOut::where('dispatched_invoice_no', $request->dispatched_invoice_no)->where('id', '!=', $stockOut->id)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'dispatched_invoice_no' => 'Dispatched invoice no already exists.',
                    ]);
                }

                if ($stockOut->source_type === 'transfer') {
                    throw new \Exception("Warehouse transfers cannot be edited because their destination stock might have already been used. Please delete the transfer and create a new one.");
                }

                $firstItem = collect($request->items)->first();

                // 1. Revert Existing Items (balance only — never modify inbound pallets_used)
                foreach ($stockOut->items as $item) {
                    $sourceBatch = StockInItem::lockForUpdate()->find($item->stock_in_item_id);
                    if ($sourceBatch) {
                        $sourceBatch->increment('balance_quantity', $item->dispatch_quantity);
                    }
                }
                
                // Delete old items
                $stockOut->items()->delete();

                // 2. Update Header
                $stockOut->update([
                    'warehouse_id'         => $firstItem['warehouse_id'],
                    'customer_id'          => $request->customer_id,
                    'transporter_id'       => $request->transporter_id,
                    'shipment_type'        => $request->shipment_type,
                    'dispatched_invoice_no'=> $request->dispatched_invoice_no,
                    'dispatcher_sig'       => $request->dispatcher_sig,
                    'picker'               => $request->picker,
                    'da_no'                => $request->da_no,
                    'vehicle_no'           => $request->vehicle_no,
                    'vehicle_size'         => $request->vehicle_size,
                    'driver_name'          => $request->driver_name,
                    'driver_mobile'        => $request->driver_mobile,
                    'vehicle_in_time'      => $request->vehicle_in_time,
                    'vehicle_out_time'     => $request->vehicle_out_time,
                    'remarks'              => $request->remarks,
                ]);

                // 3. Process New Items (FIFO)
                foreach ($request->items as $it) {
                    $productId = $it['product_id'];
                    $itWarehouseId = $it['warehouse_id'];
                    $unitsRemaining = (int) $it['units_dispatch'];
                    
                    $userPo = $it['po_no'] ?? null;
                    $userIbd = $it['ibd_no'] ?? null;
                    $stoNo = $it['sto_no'] ?? null;

                    $batchQuery = StockInItem::where('product_id', $productId)
                        ->where('warehouse_id', $itWarehouseId)
                        ->where('balance_quantity', '>', 0);

                    if ($request->outbound_type === 'customer') {
                        $batchQuery->where('block_stock', false)
                              ->where('quality_clearance', '!=', 'rejected')
                              ->where(function ($q) {
                                  $q->whereNull('expiry_date')
                                    ->orWhere('expiry_date', '>=', now()->toDateString());
                              });
                    }

                    $batches = $batchQuery->orderBy('expiry_date', 'asc')
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    if ($batches->isEmpty()) {
                        $productName = Product::find($productId)->name ?? $productId;
                        throw new \Exception("No valid stock available for product '{$productName}' in selected warehouse.");
                    }

                    $totalUnitsForThisItem = (int) $it['units_dispatch'];
                    $totalPalletsForThisItem = (int) ($it['pallets_returned'] ?? 0);
                    $palletsDistributed = 0;

                    foreach ($batches as $batch) {
                        if ($unitsRemaining <= 0) break;

                        $pack = (float) $batch->pack_size_snapshot;
                        if ($pack <= 0) continue;

                        $batchAvailableUnits = floor($batch->balance_quantity / $pack);
                        if ($batchAvailableUnits <= 0) continue;

                        $unitsToTake = min($unitsRemaining, $batchAvailableUnits);
                        $qtyToTake = $unitsToTake * $pack;

                        $batch->decrement('balance_quantity', $qtyToTake);

                        $palletsToTake = 0;
                        if ($totalUnitsForThisItem > 0) {
                            if ($unitsToTake == $unitsRemaining) {
                                $palletsToTake = max(0, $totalPalletsForThisItem - $palletsDistributed);
                            } else {
                                $palletsToTake = (int) round($totalPalletsForThisItem * ($unitsToTake / $totalUnitsForThisItem));
                            }
                        }
                        $palletsDistributed += $palletsToTake;

                        /* ===== CREATE OUTBOUND ITEMS (one per pallet consumed) ===== */
                        $effectivePallets = max(1, $palletsToTake);
                        $perPalletUnits = $unitsToTake / $effectivePallets;
                        $perPalletQty = $qtyToTake / $effectivePallets;
                        $palletUnitsRemaining = $unitsToTake;
                        $palletQtyRemaining = $qtyToTake;

                        for ($p = 0; $p < $effectivePallets; $p++) {
                            $isLastPallet = ($p === $effectivePallets - 1);
                            $thisUnits = $isLastPallet ? $palletUnitsRemaining : round($perPalletUnits);
                            $thisQty = $isLastPallet ? $palletQtyRemaining : round($perPalletQty, 3);
                            $palletUnitsRemaining -= $thisUnits;
                            $palletQtyRemaining -= $thisQty;

                            StockOutItem::create([
                                'stock_out_id'        => $stockOut->id,
                                'stock_in_item_id'    => $batch->id,
                                'product_id'          => $batch->product_id,
                                'warehouse_id'        => $batch->warehouse_id,
                                'warehouse_row_id'    => $batch->warehouse_row_id,
                                'sap_batch'           => $batch->sap_batch,
                                'vendor_batch'        => $batch->vendor_batch,
                                'units_dispatch'      => $thisUnits,
                                'pack_size_snapshot'  => $pack,
                                'dispatch_quantity'   => $thisQty,
                                'po_no'               => $userPo ?: $batch->po_no,
                                'ibd_no'              => $userIbd ?: $batch->ibd_no,
                                'sto_no'              => $stoNo,
                                'mfg_date'            => $batch->mfg_date,
                                'expiry_date'         => $batch->expiry_date,
                                'pallets_returned'    => 1,
                                'pallet_position'     => $palletsToTake > 0 ? ($p + 1) : null,
                            ]);
                        }

                        $unitsRemaining -= $unitsToTake;
                    }

                    if ($unitsRemaining > 0) {
                        $productName = Product::find($productId)->name ?? $productId;
                        throw new \Exception("Insufficient stock for product '$productName'. Needed $unitsRemaining more units.");
                    }
                }
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Outbound updated successfully',
                    'redirect' => route('outbound.index')
                ]);
            }

            return redirect()
                ->route('outbound.index')
                ->with('success', 'Outbound updated successfully');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    public function print(StockOut $stockOut)
    {
        $stockOut->load([
            'items.product',
            'items.product.uom',
            'warehouse',
            'customer',
            'toWarehouse',
            'transporter',
        ]);

        return view('outbound.print', compact('stockOut'));
    }

    public function show(StockOut $stockOut)
    {
        $stockOut->load([
            'warehouse',
            'toWarehouse',
            'customer',
            'transporter',
            'items.product',
            'items.product.uom',
            'items.sourceStockInItem',
        ]);

        return view('outbound.quick_view', compact('stockOut'));
    }

    public function invoice(StockOut $stockOut)
    {
        $stockOut->load([
            'warehouse',
            'toWarehouse',
            'customer',
            'transporter',
            'items.product',
            'items.sourceStockInItem',
            'items.product.uom',
        ]);
//        print_r($stockOut->toArray());
        return view('outbound.invoice', compact('stockOut'));
    }

    public function dc(StockOut $stockOut)
    {
        $stockOut->load([
            'warehouse',
            'toWarehouse',
            'customer',
            'transporter',
            'items.product',
            'items.sourceStockInItem',
            'items.product.uom',
        ]);

        return view('outbound.dc', compact('stockOut'));
    }
}
