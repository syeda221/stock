<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
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
            'product',
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
                $usedPallets = \App\Models\StockInItem::where('warehouse_id', $wid)
                    ->where('balance_quantity', '>', 0)
                    ->sum('pallets_used');
                $warehouseCapacities[$wid] = max(0, $totalCapacity - $usedPallets);
            }
        }

        return view('outbound.index', compact('items', 'warehouseCapacities'));
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
                    $batches = StockInItem::where('product_id', $productId)
                        ->where('warehouse_id', $itWarehouseId)
                        ->where('balance_quantity', '>', 0)
                        ->orderBy('expiry_date', 'asc')
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    if ($batches->isEmpty()) {
                        throw new \Exception("No stock available for product ID $productId in selected warehouse.");
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

                        // Deduct from source batch
                        DB::statement(
                            'UPDATE stock_in_items SET balance_quantity = balance_quantity - ? WHERE id = ?',
                            [$qtyToTake, $batch->id]
                        );

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

                        /* ===== CREATE OUTBOUND ITEM ===== */
                        StockOutItem::create([
                            'stock_out_id'        => $stockOut->id,
                            'stock_in_item_id'    => $batch->id,
                            'product_id'          => $batch->product_id,
                            'warehouse_id'        => $batch->warehouse_id,
                            'warehouse_row_id'    => $batch->warehouse_row_id,
                            'sap_batch'           => $batch->sap_batch,
                            'vendor_batch'        => $batch->vendor_batch,
                            'units_dispatch'      => $unitsToTake,
                            'pack_size_snapshot'  => $pack,
                            'dispatch_quantity'   => $qtyToTake,
                            'po_no'               => $userPo ?: $batch->po_no,
                            'ibd_no'              => $userIbd ?: $batch->ibd_no,
                            'sto_no'              => $stoNo,
                            'mfg_date'            => $batch->mfg_date,
                            'expiry_date'         => $batch->expiry_date,
                            'pallets_returned'    => $palletsToTake,
                        ]);

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

                            StockInItem::create([
                                'stock_in_id'        => $inbound->id,
                                'product_id'         => $batch->product_id,
                                'warehouse_id'       => $request->to_warehouse_id,
                                'sap_batch'          => $batch->sap_batch,
                                'vendor_batch'       => $batch->vendor_batch,
                                'mfg_date'           => $batch->mfg_date,
                                'expiry_date'        => $batch->expiry_date,
                                'units_received'     => $unitsToTake,
                                'pack_size_snapshot' => $pack,
                                'total_quantity'     => $qtyToTake,
                                'balance_quantity'   => $qtyToTake,
                                'po_no'              => $userPo ?: $batch->po_no,
                                'ibd_no'             => $userIbd ?: $batch->ibd_no,
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

    /* ================= PRINT / VIEW ================= */
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
