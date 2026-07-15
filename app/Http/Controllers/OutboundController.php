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
use App\Models\WarehouseRow;
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

        if ($request->filled('dispatch_no')) {
            $dispatchNos = (array) $request->dispatch_no;
            $query->whereHas('stockOut', function ($q) use ($dispatchNos) {
                $q->whereIn('dispatched_invoice_no', $dispatchNos);
            });
        }

        // Apply date filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('stockOut', function ($sq) use ($search) {
                    $sq->where('vehicle_no', 'like', "%{$search}%")
                        ->orWhere('vehicle_size', 'like', "%{$search}%")
                        ->orWhere('driver_name', 'like', "%{$search}%")
                        ->orWhere('driver_mobile', 'like', "%{$search}%")
                        ->orWhere('dispatched_invoice_no', 'like', "%{$search}%")
                        ->orWhere('delivery_no', 'like', "%{$search}%")
                        ->orWhere('gatepass_no', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn($tq) => $tq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('transporter', fn($tq) => $tq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('toWarehouse', fn($wq) => $wq->where('name', 'like', "%{$search}%"));
                })->orWhere('sap_batch', 'like', "%{$search}%")
                  ->orWhere('vendor_batch', 'like', "%{$search}%")
                  ->orWhereHas('product', fn($pq) => $pq->where('item_code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
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
        $warehouses = Warehouse::orderBy('name')->get();
        $customers = Customer::where('status', 1)->orderBy('name')->get();
        $productGroups = ProductGroup::where('status', 1)->orderBy('name')->get();

        $dispatchNos = StockOut::whereNotNull('dispatched_invoice_no')
            ->where('dispatched_invoice_no', '!=', '')
            ->distinct()
            ->orderBy('dispatched_invoice_no', 'asc')
            ->pluck('dispatched_invoice_no');

        return view('outbound.index', compact('items', 'warehouses', 'customers', 'productGroups', 'warehouseCapacities', 'dispatchNos'));
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

        // For customer sales, exclude expired, near-expiry, blocked, and rejected stock
        $outboundType = $request->query('outbound_type');
        if ($outboundType === 'customer') {
            $query->where(function ($q) {
                $q->where('block_stock', false)
                  ->where('quality_clearance', '!=', 'rejected')
                  ->where(function ($q2) {
                      $q2->whereNull('expiry_date')
                         ->orWhere('expiry_date', '>=', now()->addMonths(3)->toDateString());
                  });
            });
        }

        $items = $query->orderBy('warehouse_id')
            ->orderBy('created_at', 'asc')
            ->orderBy('warehouse_row_id', 'asc')
            ->orderBy('pallet_start', 'asc')
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
            'customer_id'                 => 'required_if:outbound_type,customer',
            'to_warehouse_id'             => 'required_if:outbound_type,warehouse',
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
                    $totalPalletsNeeded = 0;
                    foreach ($request->items as $it) {
                        $totalPalletsNeeded += (int) ($it['pallets_returned'] ?? 0);
                    }

                    if ($totalPalletsNeeded > 0) {
                        $freePallets = \App\Services\WarehouseRowFifo::getFreeRowCapacity($destinationWarehouse->id);
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

                    // For customer sales, exclude blocked, rejected, expired, and near-expiry stock
                    // unless allow_expired_sale is set on the batch
                    if ($request->outbound_type === 'customer') {
                        $batchQuery->where(function ($q) {
                            $q->where('block_stock', false)
                              ->where('quality_clearance', '!=', 'rejected')
                              ->where(function ($q2) {
                                  $q2->whereNull('expiry_date')
                                     ->orWhere('expiry_date', '>=', now()->addMonths(3)->toDateString());
                              })
                              ->orWhere('allow_expired_sale', true);
                        });
                    }

                    $batches = $batchQuery->orderBy('created_at', 'asc')
                        ->orderBy('warehouse_row_id', 'asc')
                        ->orderBy('pallet_start', 'asc')
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
                            throw new \Exception("Cannot dispatch product '{$productName}'. Available stock is expired, near-expiry, blocked, or rejected.");
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

                        // Calculate FIFO pallet deductions
                        $maxPerPallet = $batch->product?->cartons_per_pallet ?? null;
                        
                        $palletDeductions = [];
                        
                        if ($maxPerPallet && $maxPerPallet > 0 && $batch->pallets_used > 0) {
                            $maxPerPalletInUnits = $maxPerPallet * $pack;
                            
                            // Reconstruct PRE-deduction pallet state
                            $preDecrementBalance = $batch->balance_quantity + $qtyToTake;
                            
                            // Temporarily set balance_quantity to pre-decrement to get correct pallets
                            $originalBalance = $batch->balance_quantity;
                            $batch->balance_quantity = $preDecrementBalance;
                            $currentPallets = $batch->getPalletBalances();
                            $batch->balance_quantity = $originalBalance;
                            
                            // getPalletBalances() skips emptied pallets (qty=0). 
                            // The keys are sequential integers (0, 1, 2, ...) corresponding to active pallets.
                            $preActivePallets = count($currentPallets);
                            
                            // Now deduct from the LEFTMOST (earliest) pallets first
                            $remTake = $qtyToTake;
                            foreach ($currentPallets as $pIdx => $pQty) {
                                if ($remTake <= 0) break;
                                if ($pQty <= 0) continue;
                                
                                $takeHere = min($remTake, $pQty);
                                $remTake -= $takeHere;
                                $currentPallets[$pIdx] -= $takeHere;
                                
                                $palletDeductions[] = [
                                    'position' => $batch->pallet_start !== null ? ($batch->pallet_start + $pIdx) : null,
                                    'qty' => $takeHere,
                                    'units' => $takeHere / $pack
                                ];
                            }
                        } else {
                            $palletDeductions[] = [
                                'position' => null,
                                'qty' => $qtyToTake,
                                'units' => $unitsToTake
                            ];
                        }

                        foreach ($palletDeductions as $pIdx => $deduction) {
                            StockOutItem::create([
                                'stock_out_id'        => $stockOut->id,
                                'stock_in_item_id'    => $batch->id,
                                'product_id'          => $batch->product_id,
                                'warehouse_id'        => $batch->warehouse_id,
                                'warehouse_row_id'    => $batch->warehouse_row_id,
                                'sap_batch'           => $batch->sap_batch,
                                'vendor_batch'        => $batch->vendor_batch,
                                'units_dispatch'      => $deduction['units'],
                                'pack_size_snapshot'  => $pack,
                                'dispatch_quantity'   => $deduction['qty'],
                                'po_no'               => $userPo ?: $batch->po_no,
                                'ibd_no'              => $userIbd ?: $batch->ibd_no,
                                'sto_no'              => $stoNo,
                                'mfg_date'            => $batch->mfg_date,
                                'expiry_date'         => $batch->expiry_date,
                                'pallets_returned'    => 1,
                                'pallet_position'     => $deduction['position'],
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
                            $cpp = $batch->product ? (int) $batch->product->cartons_per_pallet : 0;
                            $splits = \App\Services\WarehouseRowFifo::assign(
                                (int) $request->to_warehouse_id,
                                $palletsToTake,
                                $unitsToTake,
                                $pack,
                                true,
                                $cpp
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
            return $item->product_id . '_' . $item->warehouse_id . '_' . $item->po_no . '_' . $item->ibd_no . '_' . $item->sto_no;
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

        $dispatchNos = StockOut::whereNotNull('dispatched_invoice_no')
            ->where('dispatched_invoice_no', '!=', '')
            ->distinct()
            ->orderBy('dispatched_invoice_no', 'asc')
            ->pluck('dispatched_invoice_no');

        return view('outbound.edit', [
            'stockOut' => $stockOut,
            'groupedItems' => $groupedItems,
            'warehouses' => $warehouses,
            'customers' => $customers,
            'transporters' => $transporters,
            'products' => Product::where('status', 1)->orderBy('name')->get(),
            'dispatchNos' => $dispatchNos,
        ]);
    }

    /* ================= UPDATE ================= */
    public function update(Request $request, StockOut $stockOut)
    {
        $request->validate([
            'outbound_type'               => 'required|in:warehouse,customer',
            'customer_id'                 => 'required_if:outbound_type,customer',
            'to_warehouse_id'             => 'required_if:outbound_type,warehouse',
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
                        $batchQuery->where(function ($q) {
                            $q->where('block_stock', false)
                              ->where('quality_clearance', '!=', 'rejected')
                              ->where(function ($q2) {
                                  $q2->whereNull('expiry_date')
                                     ->orWhere('expiry_date', '>=', now()->addMonths(3)->toDateString());
                              })
                              ->orWhere('allow_expired_sale', true);
                        });
                    }

                    $batches = $batchQuery->orderBy('created_at', 'asc')
                        ->orderBy('warehouse_row_id', 'asc')
                        ->orderBy('pallet_start', 'asc')
                        ->lockForUpdate()
                        ->get();

                    if ($batches->isEmpty()) {
                        $productName = Product::find($productId)->name ?? $productId;
                        
                        $anyStockExists = StockInItem::where('product_id', $productId)
                            ->where('warehouse_id', $itWarehouseId)
                            ->where('balance_quantity', '>', 0)
                            ->exists();

                        if ($anyStockExists && $request->outbound_type === 'customer') {
                            throw new \Exception("Cannot dispatch product '{$productName}'. Available stock is expired, near-expiry, blocked, or rejected.");
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

                        $batch->decrement('balance_quantity', $qtyToTake);

                        // Calculate FIFO pallet deductions
                        $maxPerPallet = $batch->product?->cartons_per_pallet ?? null;
                        
                        $palletDeductions = [];
                        
                        if ($maxPerPallet && $maxPerPallet > 0 && $batch->pallets_used > 0) {
                            $maxPerPalletInUnits = $maxPerPallet * $pack;
                            
                            $preDecrementBalance = $batch->balance_quantity + $qtyToTake;
                            
                            $originalBalance = $batch->balance_quantity;
                            $batch->balance_quantity = $preDecrementBalance;
                            $currentPallets = $batch->getPalletBalances();
                            $batch->balance_quantity = $originalBalance;
                            
                            $preActivePallets = count($currentPallets);
                            
                            $remTake = $qtyToTake;
                            foreach ($currentPallets as $pIdx => $pQty) {
                                if ($remTake <= 0) break;
                                if ($pQty <= 0) continue;
                                
                                $takeHere = min($remTake, $pQty);
                                $remTake -= $takeHere;
                                $currentPallets[$pIdx] -= $takeHere;
                                
                                $palletDeductions[] = [
                                    'position' => $batch->pallet_start !== null ? ($batch->pallet_start + $pIdx) : null,
                                    'qty' => $takeHere,
                                    'units' => $takeHere / $pack
                                ];
                            }
                        } else {
                            $palletDeductions[] = [
                                'position' => null,
                                'qty' => $qtyToTake,
                                'units' => $unitsToTake
                            ];
                        }

                        foreach ($palletDeductions as $pIdx => $deduction) {
                            StockOutItem::create([
                                'stock_out_id'        => $stockOut->id,
                                'stock_in_item_id'    => $batch->id,
                                'product_id'          => $batch->product_id,
                                'warehouse_id'        => $batch->warehouse_id,
                                'warehouse_row_id'    => $batch->warehouse_row_id,
                                'sap_batch'           => $batch->sap_batch,
                                'vendor_batch'        => $batch->vendor_batch,
                                'units_dispatch'      => $deduction['units'],
                                'pack_size_snapshot'  => $pack,
                                'dispatch_quantity'   => $deduction['qty'],
                                'po_no'               => $userPo ?: $batch->po_no,
                                'ibd_no'              => $userIbd ?: $batch->ibd_no,
                                'sto_no'              => $stoNo,
                                'mfg_date'            => $batch->mfg_date,
                                'expiry_date'         => $batch->expiry_date,
                                'pallets_returned'    => 1,
                                'pallet_position'     => $deduction['position'],
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
            'items.warehouseRow',
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
            'items.warehouseRow',
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
            'items.warehouseRow',
        ]);
//        print_r($stockOut->toArray());
        return view('outbound.invoice', compact('stockOut'));
    }

    public function dispatchDetails(StockOut $stockOut)
    {
        $stockOut->load([
            'warehouse',
            'toWarehouse',
            'customer',
            'transporter',
            'items.product',
            'items.sourceStockInItem',
            'items.product.uom',
            'items.product.packingType',
            'items.warehouseRow',
        ]);

        return view('outbound.dispatch_details', compact('stockOut'));
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
            'items.warehouseRow',
        ]);

        return view('outbound.dc', compact('stockOut'));
    }

    public function export(Request $request)
    {
        // Build row-letter mapping
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

        $query = StockOutItem::with([
            'stockOut.warehouse', 'stockOut.customer', 'stockOut.toWarehouse', 'stockOut.transporter',
            'product.category', 'product.uom', 'product.packingType',
            'sourceStockInItem', 'sourceStockInItem.warehouseRow', 'warehouseRow',
        ]);

        if ($request->filled('selected_ids')) {
            $selectedIds = is_array($request->selected_ids) ? $request->selected_ids : explode(',', $request->selected_ids);
            $query->whereIn('id', $selectedIds);
        }

        // Apply same filters as index
        if ($request->filled('source_type')) {
            $query->whereHas('stockOut', fn($q) => $q->where('source_type', $request->source_type));
        }
        if ($request->filled('warehouse_id')) {
            $query->whereHas('stockOut', fn($q) => $q->where('warehouse_id', $request->warehouse_id));
        }
        if ($request->filled('customer_id')) {
            $query->whereHas('stockOut', fn($q) => $q->where('customer_id', $request->customer_id));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('product_group_id')) {
            $groupId = $request->product_group_id;
            $query->whereHas('product', fn($q) => $q->where('product_group_id', $groupId));
        }
        if ($request->filled('dispatch_no')) {
            $dispatchNos = (array) $request->dispatch_no;
            $query->whereHas('stockOut', fn($q) => $q->whereIn('dispatched_invoice_no', $dispatchNos));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('stockOut', function ($sq) use ($search) {
                    $sq->where('vehicle_no', 'like', "%{$search}%")
                        ->orWhere('vehicle_size', 'like', "%{$search}%")
                        ->orWhere('driver_name', 'like', "%{$search}%")
                        ->orWhere('driver_mobile', 'like', "%{$search}%")
                        ->orWhere('dispatched_invoice_no', 'like', "%{$search}%")
                        ->orWhere('delivery_no', 'like', "%{$search}%")
                        ->orWhere('gatepass_no', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn($tq) => $tq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('transporter', fn($tq) => $tq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('toWarehouse', fn($wq) => $wq->where('name', 'like', "%{$search}%"));
                })->orWhere('sap_batch', 'like', "%{$search}%")
                  ->orWhere('vendor_batch', 'like', "%{$search}%")
                  ->orWhereHas('product', fn($pq) => $pq->where('item_code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        $items = $query->latest()->get();

        $filename = 'outbound_stock_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $csv = fopen('php://memory', 'w');
        fputcsv($csv, [
            'Date', 'Item Code', 'Product Name', 'Type', 'Warehouse',
            'Category', 'UOM', 'Packing', 'Pack Size',
            'Units Dispatched', 'Dispatch Qty', 'Pallets',
            'Customer / Destination', 'Transporter', 'Vehicle No',
            'Driver Name', 'Vehicle In Time', 'Vehicle Out Time',
            'Dispatched Invoice', 'PO', 'IBD', 'STO',
            'SAP Batch', 'Vendor Batch', 'MFG Date', 'Expiry Date',
            'Remarks'
        ]);

        foreach ($items as $item) {
            $dateVal = $item->created_at ? (method_exists($item->created_at, 'format') ? $item->created_at->format('d.m.Y H:i') : $item->created_at) : '';
            $type = optional($item->stockOut)->source_type === 'sale' ? 'Sale' : (optional($item->stockOut)->source_type === 'transfer' ? 'Transfer' : '');

            $sourceItem = $item->sourceStockInItem;
            $row = $item->warehouseRow ?: optional($sourceItem)->warehouseRow;
            $whId = $item->warehouse_id;

            $warehouseDisplay = optional($item->warehouse)->name ?? '';
            if ($row) {
                $palletNum = $item->pallet_position;
                $rowKey = $whId . '-' . $row->row_name;
                $letter = $rowLetterMap[$rowKey] ?? '';
                
                if ($letter && $palletNum !== null) {
                    $wp = str_pad($whId, 2, '0', STR_PAD_LEFT);
                    $psPadded = str_pad($palletNum, 3, '0', STR_PAD_LEFT);
                    $rowNameStr = $row->row_name ?? '';
                    $wName = (strpos($rowNameStr, '.') !== false) ? explode('.', $rowNameStr)[0] : "W{$wp}";
                    $warehouseDisplay = "{$wName}.{$letter}{$psPadded}";
                } elseif ($row->row_name) {
                    $warehouseDisplay = $row->row_name;
                }
            }

            fputcsv($csv, [
                $dateVal,
                optional($item->product)->item_code ?? '',
                optional($item->product)->name ?? '',
                $type,
                $warehouseDisplay,
                optional(optional($item->product)->category)->name ?? '',
                optional(optional($item->product)->uom)->name ?? '',
                optional(optional($item->product)->packingType)->name ?? '',
                $item->pack_size_snapshot,
                $item->units_dispatch,
                $item->dispatch_quantity,
                $item->pallets_returned,
                optional($item->stockOut->customer)->name ?? optional($item->stockOut->toWarehouse)->name ?? '',
                optional($item->stockOut->transporter)->name ?? '',
                optional($item->stockOut)->vehicle_no ?? '',
                optional($item->stockOut)->driver_name ?? '',
                optional($item->stockOut)->vehicle_in_time ? (method_exists(optional($item->stockOut)->vehicle_in_time, 'format') ? optional($item->stockOut)->vehicle_in_time->format('d.m.Y H:i') : date('d.m.Y H:i', strtotime(optional($item->stockOut)->vehicle_in_time))) : '',
                optional($item->stockOut)->vehicle_out_time ? (method_exists(optional($item->stockOut)->vehicle_out_time, 'format') ? optional($item->stockOut)->vehicle_out_time->format('d.m.Y H:i') : date('d.m.Y H:i', strtotime(optional($item->stockOut)->vehicle_out_time))) : '',
                optional($item->stockOut)->dispatched_invoice_no ?? '',
                $item->po_no ?? optional($item->sourceStockInItem)->po_no ?? '',
                $item->ibd_no ?? optional($item->sourceStockInItem)->ibd_no ?? '',
                $item->sto_no ?? '',
                $item->sap_batch ?? optional($item->sourceStockInItem)->sap_batch ?? '',
                $item->vendor_batch ?? optional($item->sourceStockInItem)->vendor_batch ?? '',
                $item->mfg_date ? (method_exists($item->mfg_date, 'format') ? $item->mfg_date->format('d.m.Y') : $item->mfg_date) : '',
                $item->expiry_date ? (method_exists($item->expiry_date, 'format') ? $item->expiry_date->format('d.m.Y') : $item->expiry_date) : '',
                optional($item->stockOut)->remarks ?? '',
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, $headers);
    }

    public function downloadTemplate()
    {
        $filename = 'outbound_stock_import_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () {
        $file = fopen('php://output', 'w');
        fputcsv($file, [
            'Item Code', 'Product Name', 'Warehouse', 'Type',
            'Units Dispatched', 'Customer', 'Transporter', 'Vehicle No', 'Driver Name', 'Vehicle In Time', 'Vehicle Out Time',
            'PO', 'IBD', 'STO',
            'SAP Batch', 'Vendor Batch', 'MFG Date', 'Expiry Date', 'Remarks'
        ]);
        fputcsv($file, [
            '001', 'Sample Product', '', 'sale',
            '100', 'Customer A', 'Transporter X', 'XYZ-123', 'John Doe', '2024-01-15 10:00', '2024-01-15 11:30', 'PO-001', 'IBD-001', '',
            '', '', '2024-01-15', '2025-01-15', ''
        ]);
        fputcsv($file, [
            '002', 'Sample Product 2', 'Main Warehouse', 'transfer',
            '50', '', '', '', '', '', '', 'PO-002', '', 'STO-001',
            '', '', '', '', ''
        ]);
        fclose($file);
    };

        return response()->stream($callback, 200, $headers);
    }

    public function importForm()
    {
        return view('outbound.import', [
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

        $fieldAliases = [
            'Item Code'        => ['Item Code', 'item_code', 'ItemCode', 'Code'],
            'Units Dispatched' => ['Units Dispatched', 'units_dispatch', 'Units Dispatch', 'Dispatch Units'],
            'Type'             => ['Type', 'type', 'Source Type', 'source_type', 'Outbound Type'],
            'PO'               => ['PO', 'po', 'po_no', 'PO No'],
            'IBD'              => ['IBD', 'ibd', 'ibd_no', 'IBD No'],
            'STO'              => ['STO', 'sto', 'sto_no', 'STO No'],
            'SAP Batch'        => ['SAP Batch', 'sap_batch', 'SapBatch', 'Batch'],
            'Vendor Batch'     => ['Vendor Batch', 'vendor_batch', 'VendorBatch'],
            'MFG Date'         => ['MFG Date', 'mfg_date', 'MfgDate', 'Manufacturing Date'],
            'Expiry Date'      => ['Expiry Date', 'expiry_date', 'ExpiryDate', 'Exp Date'],
            'Pallets'          => ['Pallets', 'pallets', 'pallets_returned', 'Pallets Returned'],
            'Customer'         => ['Customer', 'customer', 'Customer Name', 'Customer / Destination'],
            'Transporter'      => ['Transporter', 'transporter', 'Transporter Name'],
            'Vehicle No'       => ['Vehicle No', 'vehicle_no', 'Vehicle Number'],
            'Driver Name'      => ['Driver Name', 'driver_name', 'Driver'],
            'Vehicle In Time'  => ['Vehicle In Time', 'vehicle_in_time', 'In Time'],
            'Vehicle Out Time' => ['Vehicle Out Time', 'vehicle_out_time', 'Out Time'],
            'Remarks'          => ['Remarks', 'remarks', 'Notes', 'Comment'],
            'Warehouse'        => ['Warehouse', 'warehouse', 'Warehouse Name', 'WH'],
        ];

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

        if (!isset($headerMap['Item Code'])) {
            fclose($handle);
            return back()->with('error', 'Missing required column "Item Code". Found: ' . implode(', ', $csvHeaders));
        }
        if (!isset($headerMap['Units Dispatched'])) {
            fclose($handle);
            return back()->with('error', 'Missing required column "Units Dispatched". Found: ' . implode(', ', $csvHeaders));
        }

        $errors = [];
        $imported = 0;
        $skipped = 0;
        $csvRows = [];
        $allProducts = Product::where('status', 1)->get()->keyBy('item_code');
        $allWarehouses = Warehouse::where('status', 1)->get();

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
            $units = $getCell($row, 'Units Dispatched');
            $rowErrors = [];

            if (empty($itemCode)) $rowErrors[] = 'Missing Item Code';
            if (empty($units) || !is_numeric($units)) $rowErrors[] = 'Invalid Units Dispatched';

            $product = null;
            if (!empty($itemCode)) {
                $product = $allProducts->get($itemCode);
                if (!$product) $rowErrors[] = "Product '{$itemCode}' not found";
            }

            if (!empty($rowErrors)) {
                $errors[] = "Row {$rowNum}: " . implode('; ', $rowErrors);
                $skipped++;
                continue;
            }

            $type = strtolower($getCell($row, 'Type'));
            if (!in_array($type, ['sale', 'transfer', ''])) $type = 'sale';

            $warehouseName = $getCell($row, 'Warehouse');
            $warehouseId = null;
            if (!empty($warehouseName)) {
                $matchedWh = $allWarehouses->first(fn($w) => strtolower($w->name) === strtolower($warehouseName) || $w->id == $warehouseName);
                $warehouseId = $matchedWh ? $matchedWh->id : null;
                if (!$warehouseId) {
                    $errors[] = "Row {$rowNum}: Warehouse '{$warehouseName}' not found";
                    $skipped++;
                    continue;
                }
            }

            $csvRows[] = [
                'product'       => $product,
                'units'         => (int) $units,
                'type'          => $type ?: 'sale',
                'customer'      => $getCell($row, 'Customer'),
                'transporter'   => $getCell($row, 'Transporter'),
                'vehicle_no'    => $getCell($row, 'Vehicle No'),
                'driver_name'   => $getCell($row, 'Driver Name'),
                'vehicle_in_time' => $getCell($row, 'Vehicle In Time'),
                'vehicle_out_time'=> $getCell($row, 'Vehicle Out Time'),
                'warehouse_id'  => $warehouseId,
                'po_no'         => $getCell($row, 'PO'),
                'ibd_no'        => $getCell($row, 'IBD'),
                'sto_no'        => $getCell($row, 'STO'),
                'sap_batch'     => $getCell($row, 'SAP Batch'),
                'vendor_batch'  => $getCell($row, 'Vendor Batch'),
                'mfg_date'      => $getCell($row, 'MFG Date'),
                'expiry_date'   => $getCell($row, 'Expiry Date'),
                'remarks'       => $getCell($row, 'Remarks'),
            ];
        }

        fclose($handle);

        if (count($csvRows) === 0) {
            return back()->with('error', 'No valid rows found in CSV');
        }

        try {
            DB::transaction(function () use ($csvRows, $request, &$imported, &$skipped, &$errors) {
                $allCustomers = \App\Models\Customer::where('status', 1)->get();
                $allTransporters = \App\Models\Transporter::where('status', 1)->get();
                
                $groupedRows = [];
                foreach ($csvRows as $item) {
                    $customerName = trim($item['customer'] ?? '');
                    $groupKey = $item['type'] . '|' . $customerName;
                    $groupedRows[$groupKey][] = $item;
                }

                foreach ($groupedRows as $groupKey => $rows) {
                    $stockOut = null;
                    $customerId = null;
                    $transporterId = null;
                    
                    $parts = explode('|', $groupKey, 2);
                    $customerName = $parts[1] ?? '';

                    if ($customerName !== '') {
                        $matchedCustomer = $allCustomers->first(function($c) use ($customerName) {
                            return strtolower($c->name) === strtolower($customerName) || $c->id == $customerName;
                        });
                        $customerId = $matchedCustomer ? $matchedCustomer->id : null;
                    }
                    
                    $transporterName = $rows[0]['transporter'] ?? '';
                    if ($transporterName !== '') {
                        $matchedTransporter = $allTransporters->first(function($t) use ($transporterName) {
                            return strtolower($t->name) === strtolower($transporterName) || $t->id == $transporterName;
                        });
                        $transporterId = $matchedTransporter ? $matchedTransporter->id : null;
                    }

                    foreach ($rows as $item) {
                        $product   = $item['product'];
                        $units     = $item['units'];
                        $packSize  = (float) $product->pack_size;
                        $type      = $item['type'];

                    // Find available stock batches (FIFO)
                    $batchQuery = StockInItem::where('product_id', $product->id)
                        ->where('balance_quantity', '>', 0);

                    if ($type === 'sale') {
                        $batchQuery->where(function ($q) {
                            $q->where('block_stock', false)
                              ->where('quality_clearance', '!=', 'rejected')
                              ->where(function ($q2) {
                                  $q2->whereNull('expiry_date')
                                     ->orWhere('expiry_date', '>=', now()->addMonths(3)->toDateString());
                              })
                              ->orWhere('allow_expired_sale', true);
                        });
                    }

                    $whId = $item['warehouse_id'] ?? $request->warehouse_id;
                    if ($whId) {
                        $batchQuery->where('warehouse_id', $whId);
                    }

                    $batches = $batchQuery->orderBy('created_at', 'asc')
                        ->orderBy('warehouse_row_id', 'asc')
                        ->orderBy('pallet_start', 'asc')
                        ->lockForUpdate()
                        ->get();

                    if ($batches->isEmpty()) {
                        $errors[] = "No available stock for '{$product->name}'";
                        $skipped++;
                        continue;
                    }

                    $unitsRemaining = $units;
                    $warehouseId = null;

                    foreach ($batches as $batch) {
                        if ($unitsRemaining <= 0) break;

                        $pack = (float) $batch->pack_size_snapshot;
                        if ($pack <= 0) continue;

                        $batchAvailableUnits = floor($batch->balance_quantity / $pack);
                        if ($batchAvailableUnits <= 0) continue;

                        $unitsToTake = min($unitsRemaining, $batchAvailableUnits);
                        $qtyToTake = $unitsToTake * $pack;

                        $warehouseId = $batch->warehouse_id;

                        $batch->decrement('balance_quantity', $qtyToTake);

                        // Create StockOut header on first item for this Customer
                        if (!$stockOut) {
                            $invoiceNo = $this->generateDispatchedInvoiceNo();
                            $stockOut = StockOut::create([
                                'source_type'           => $type === 'sale' ? 'sale' : 'transfer',
                                'customer_id'           => $customerId,
                                'transporter_id'        => $transporterId,
                                'vehicle_no'            => $rows[0]['vehicle_no'] ?? null,
                                'driver_name'           => $rows[0]['driver_name'] ?? null,
                                'vehicle_in_time'       => $rows[0]['vehicle_in_time'] ?: null,
                                'vehicle_out_time'      => $rows[0]['vehicle_out_time'] ?: null,
                                'warehouse_id'          => $warehouseId,
                                'dispatched_invoice_no' => $invoiceNo,
                                'shipment_type'         => 'manual',
                                'remarks'               => $item['remarks'] ?: 'Imported via CSV on ' . now()->format('d.m.Y H:i'),
                            ]);
                        }

                        $maxPerPallet = $batch->product?->cartons_per_pallet ?? null;
                        $palletDeductions = [];

                        if ($maxPerPallet && $maxPerPallet > 0 && $batch->pallets_used > 0) {
                            $preDecrementBalance = $batch->balance_quantity + $qtyToTake;
                            $originalBalance = $batch->balance_quantity;
                            $batch->balance_quantity = $preDecrementBalance;
                            $currentPallets = $batch->getPalletBalances();
                            $batch->balance_quantity = $originalBalance;
                            
                            $remTake = $qtyToTake;
                            foreach ($currentPallets as $pIdx => $pQty) {
                                if ($remTake <= 0) break;
                                if ($pQty <= 0) continue;
                                
                                $takeHere = min($remTake, $pQty);
                                $remTake -= $takeHere;
                                $currentPallets[$pIdx] -= $takeHere;
                                
                                $palletDeductions[] = [
                                    'position' => $batch->pallet_start !== null ? ($batch->pallet_start + $pIdx) : null,
                                    'qty' => $takeHere,
                                    'units' => $takeHere / $pack
                                ];
                            }
                        } else {
                            $palletDeductions[] = [
                                'position' => null,
                                'qty' => $qtyToTake,
                                'units' => $unitsToTake
                            ];
                        }
                        
                        foreach ($palletDeductions as $deduction) {
                            StockOutItem::create([
                                'stock_out_id'        => $stockOut->id,
                                'stock_in_item_id'    => $batch->id,
                                'product_id'          => $batch->product_id,
                                'warehouse_id'        => $batch->warehouse_id,
                                'warehouse_row_id'    => $batch->warehouse_row_id,
                                'sap_batch'           => $batch->sap_batch,
                                'vendor_batch'        => $batch->vendor_batch,
                                'units_dispatch'      => $deduction['units'],
                                'pack_size_snapshot'  => $pack,
                                'dispatch_quantity'   => $deduction['qty'],
                                'po_no'               => $item['po_no'] ?: $batch->po_no,
                                'ibd_no'              => $item['ibd_no'] ?: $batch->ibd_no,
                                'sto_no'              => $item['sto_no'],
                                'mfg_date'            => $batch->mfg_date,
                                'expiry_date'         => $batch->expiry_date,
                                'pallets_returned'    => 1,
                                'pallet_position'     => $deduction['position'],
                                'remarks'             => null,
                            ]);
                        }

                        $unitsRemaining -= $unitsToTake;
                    }

                    if ($unitsRemaining > 0) {
                        $errors[] = "Insufficient stock for '{$product->name}'. Need {$unitsRemaining} more units.";
                        $skipped++;
                        continue;
                    }

                    $imported++;
                }
                } // End foreach groupedRows
            });

            $message = "Dispatched {$imported} product(s)";
            if ($request->warehouse_id) $message .= " from warehouse: " . Warehouse::find($request->warehouse_id)->name;
            if ($skipped > 0) $message .= ". {$skipped} skipped.";
            if ($errors) $message .= ". " . implode(' | ', array_slice($errors, 0, 10));

            return redirect()->route('outbound.index')->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
