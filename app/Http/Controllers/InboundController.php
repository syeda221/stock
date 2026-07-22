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
use App\Models\WarehouseRow;
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
        $query = StockIn::where('source_type', 'inbound')
            ->with([
                'warehouse',
                'vendor',
                'transporter',
                'arrivedFrom',
                'items.product.category',
                'items.product.group',
                'items.warehouseRow',
            ]);

        // Apply warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Apply vendor filter
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Apply inbound invoice filter
        if ($request->filled('inbound_invoices')) {
            $invoices = (array) $request->inbound_invoices;
            $query->whereIn('dispatched_invoice_no', $invoices);
        }

        // Apply date filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('dispatched_invoice_no', 'like', "%{$search}%")
                  ->orWhere('inbound_invoice_no', 'like', "%{$search}%")
                  ->orWhere('vehicle_no', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%")
                  ->orWhere('driver_mobile', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn($v) => $v->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('transporter', fn($t) => $t->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('arrivedFrom', fn($a) => $a->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('items.product', fn($p) => $p->where('name', 'like', "%{$search}%")->orWhere('item_code', 'like', "%{$search}%"));
            });
        }

        $transactions = $query->latest()->paginate(25);

        // Get filter options
        $warehouses    = Warehouse::orderBy('name')->get();
        $vendors       = Vendor::orderBy('name')->get();
        $products      = Product::orderBy('name')->get();
        $productGroups = ProductGroup::where('status', 1)->orderBy('name')->get();

        $inboundInvoices = StockIn::where('source_type', 'inbound')
            ->whereNotNull('dispatched_invoice_no')
            ->where('dispatched_invoice_no', '!=', '')
            ->distinct()
            ->orderBy('dispatched_invoice_no')
            ->pluck('dispatched_invoice_no');

        // Keep $items for backward compat (used in stats cards via collection)
        $items = $transactions;

        return view('inbound.index', compact(
            'transactions', 'items', 'warehouses', 'vendors', 'products', 'productGroups', 'inboundInvoices'
        ));
    }

    /**
     * AJAX endpoint: return items for a specific StockIn (inbound document)
     */
    public function getItems(StockIn $stockIn)
    {
        $items = $stockIn->items()
            ->with(['product.category', 'product.group', 'warehouseRow', 'stockIn.warehouse'])
            ->get()
            ->map(function ($item) {
                // Resolve pallet range display
                $palletRange = null;
                if ($item->warehouse_row_id && $item->pallets_used > 0) {
                    $row = $item->warehouseRow;
                    if ($item->pallet_start !== null) {
                        $start = (int) $item->pallet_start;
                        $end   = $start + $item->pallets_used - 1;
                    } else {
                        $offset = StockInItem::where('warehouse_row_id', $item->warehouse_row_id)
                            ->where('id', '<', $item->id)
                            ->where('balance_quantity', '>', 0)
                            ->sum('pallets_used');
                        $start = $offset + 1;
                        $end   = $offset + $item->pallets_used;
                    }
                    $rowName = $row->row_name ?? '-';
                    $palletRange = $start == $end
                        ? "Row {$rowName} (P{$start})"
                        : "Row {$rowName} (P{$start}-P{$end})";
                }
                $item->pallet_range_display = $palletRange;
                return $item;
            });

        return response()->json($items);
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
            $usedPallets = StockInItem::with('product')
                ->where('warehouse_id', $w->id)
                ->where('balance_quantity', '>', 0)
                ->get()
                ->sum(fn($i) => StockInItem::computeActivePallets($i));
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

        $autoSelectId = $warehouseData->firstWhere('has_space', true)['id'] ?? null;

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

    public function previewPallets(Request $request)
    {
        $items = $request->input('items', []);
        $activeRowIndex = (int) $request->input('active_row_index', 0);

        if (empty($items)) {
            return response()->json(['success' => true, 'allocations' => []]);
        }

        // We will simulate allocations sequentially for all items in the form
        $simulatedOccupied = []; // row_id => [pallet_number => true]
        $activeAllocations = [];

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

            $product = $productId ? Product::find($productId) : null;
            $packSize = $product ? (float) $product->pack_size : 1.0;
            $cartonsPerPallet = $product ? (int) ($product->cartons_per_pallet ?? 0) : 0;

            if ($idx == $activeRowIndex) {
                if ($units <= 0 && $palletsUsed <= 0) {
                    $units = 1;
                    $palletsUsed = 1;
                } elseif ($units <= 0 && $palletsUsed > 0) {
                    $units = $palletsUsed * ($cartonsPerPallet > 0 ? $cartonsPerPallet : 1);
                } elseif ($units > 0 && $palletsUsed <= 0) {
                    $palletsUsed = $cartonsPerPallet > 0 ? (int) ceil($units / $cartonsPerPallet) : 1;
                }
            } else {
                if ($units <= 0 && $palletsUsed > 0) {
                    $units = $palletsUsed * ($cartonsPerPallet > 0 ? $cartonsPerPallet : 1);
                } elseif ($units > 0 && $palletsUsed <= 0) {
                    $palletsUsed = $cartonsPerPallet > 0 ? (int) ceil($units / $cartonsPerPallet) : 1;
                }
            }

            if ($units <= 0 || $palletsUsed <= 0) {
                continue;
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
                            'type'           => $manualRowId ? 'manual' : 'auto',
                            'warehouse_name' => $row->warehouse->name,
                            'row_name'       => $row->row_name,
                            'row_id'         => $row->id,
                            'pallets_count'  => $split['pallets'],
                            'pallet_names'   => $palletNames,
                            'units'          => $split['units'],
                            'qty'            => round($split['units'] * $packSize, 4)
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
            }
        }

        return response()->json([
            'success' => true,
            'allocations' => $activeAllocations
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
        if ($request->has('items') && is_array($request->items)) {
            $filteredItems = collect($request->items)
                ->filter(function ($item) {
                    return !empty($item['product_id']);
                })
                ->values()
                ->toArray();
            $request->merge(['items' => $filteredItems]);
        }

        // Auto-resolve top-level warehouse_id from first item row or default active warehouse
        if (!$request->has('warehouse_id') || empty($request->warehouse_id) || $request->warehouse_id === 'auto') {
            $firstItemWh = collect($request->items)->firstWhere('warehouse_id', '!=', 'auto')['warehouse_id'] ?? null;
            if (!$firstItemWh || $firstItemWh === 'auto') {
                $firstItemWh = Warehouse::where('status', 1)->value('id');
            }
            if ($firstItemWh) {
                $request->merge(['warehouse_id' => $firstItemWh]);
            }
        }

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
            'gatepass_no' => 'nullable|string|max:80',
            'dispatched_invoice_no' => 'nullable|string|max:80',
            'dispatcher_sig' => 'nullable|string|max:255',
            'picker' => 'nullable|string|max:120',

            'shipment_type' => 'nullable|in:manual,auto',
            'manual_selection' => 'nullable|in:0,1',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.units_received' => 'nullable|integer|min:0',
            'items.*.quality_clearance' => 'nullable|in:pending,approved,rejected',
            'items.*.qc_remarks' => 'nullable|string',
            'items.*.warehouse_row_id' => 'nullable|exists:warehouse_rows,id',
            'items.*.pallet_start' => 'nullable|integer|min:1',
        ]);

        if ($request->manual_selection == '1') {
            $freeCapacity = \App\Services\WarehouseRowFifo::getFreeRowCapacity($request->warehouse_id);
            if ($freeCapacity <= 0) {
                return back()->withErrors(['warehouse_id' => 'Selected warehouse has no free space.'])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($request, &$stockIn) {

                // Determine warehouse sequence: primary (user-selected/auto) first, then others by name
                $primaryWarehouse = Warehouse::findOrFail($request->warehouse_id);
                $otherWarehouses = Warehouse::where('status', 1)
                    ->where('id', '!=', $primaryWarehouse->id)
                    ->orderBy('name')
                    ->get();
                $whSequence = collect([$primaryWarehouse])->concat($otherWarehouses);

                // Pre-compute free capacity for each warehouse
                $freeCapacity = [];
                foreach ($whSequence as $wh) {
                    $freeCapacity[$wh->id] = WarehouseRowFifo::getFreeRowCapacity($wh->id);
                }

                /** -----------------------------
                 *  1️⃣ Inbound Header
                 * ----------------------------- */
                $stockIn = StockIn::create([
                    'source_type' => 'inbound',
                    'warehouse_id' => $primaryWarehouse->id,
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
                    'gatepass_no' => $request->gatepass_no,
                    'dispatched_invoice_no' => $request->dispatched_invoice_no,
                    'dispatcher_sig' => $request->dispatcher_sig,
                    'picker' => $request->picker,

                    'shipment_type' => $request->shipment_type ?? 'manual',
                    'remarks' => $request->remarks,
                ]);

            /** -----------------------------
             * 2️⃣ Pallet Capacity Validation (multi-warehouse)
             * ----------------------------- */
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

            $totalFree = array_sum($freeCapacity);
            if ($totalFree < $totalPallets) {
                throw new \Exception("Insufficient total warehouse capacity. Need {$totalPallets} pallets, but only {$totalFree} available across all warehouses.");
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
             *  3️⃣ Stock Items — Sequential Multi-Warehouse Fill
             * ----------------------------- */
            $simulatedOccupied = [];

            foreach ($request->items as $item) {
                if (empty($item['product_id']) || empty($item['units_received'])) continue;

                $product  = Product::findOrFail($item['product_id']);
                $units    = (int) $item['units_received'];
                $packSize = (float) $product->pack_size;

                $manualRowId = !empty($item['warehouse_row_id']) ? (int) $item['warehouse_row_id'] : null;
                $manualPalletStart = isset($item['pallet_start']) && $item['pallet_start'] !== '' ? (int) $item['pallet_start'] : null;

                $palletsNeeded = (int) ($item['pallets_used'] ?? 0);
                if ($palletsNeeded === 0 && $product->cartons_per_pallet > 0) {
                    $palletsNeeded = (int) ceil($units / $product->cartons_per_pallet);
                }

                $splits = WarehouseRowFifo::assign(
                    $primaryWarehouse->id,
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
                        'pallet_start'       => $split['pallet_start'] ?? null,
                        'last_pallet_vacant' => $lastVacant,

                        'sound_stock'        => ! empty($item['sound_stock']),
                        'block_stock'        => ! empty($item['block_stock']),
                        'hold_stock'         => ! empty($item['hold_stock']),
                        'quality_clearance'  => $item['quality_clearance'] ?? 'pending',
                        'qc_remarks'         => $item['qc_remarks'] ?? null,
                        'damage_stock'       => ! empty($item['damage_stock'] ?? 0),
                        'remarks'            => $item['remarks'] ?? null,
                        'uom_snapshot'       => optional($product->uom)->name,
                        'packing_snapshot'   => optional($product->packingType)->name,
                    ]);
                }
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
                'qc_remarks' => $first->qc_remarks,
                'damage_stock' => $first->damage_stock,
                'remarks' => $first->remarks,
                // store the IDs of the splits so we can clean them up if modified
                'split_ids' => $group->pluck('id')->join(','),
            ];
        })->values();

        $warehouses = Warehouse::where('status', 1)->with('rows')->orderBy('name')->get();
        $warehouseData = $warehouses->map(function ($w) {
            $usedPallets = StockInItem::with('product')
                ->where('warehouse_id', $w->id)
                ->where('balance_quantity', '>', 0)
                ->get()
                ->sum(fn($i) => StockInItem::computeActivePallets($i));
            $freePallets = $w->total_capacity ? max(0, $w->total_capacity - $usedPallets) : PHP_INT_MAX;
            return [
                'id' => $w->id,
                'name' => $w->name,
                'total_capacity' => $w->total_capacity,
                'used_pallets' => $usedPallets,
                'free_pallets' => $freePallets,
                'has_space' => $freePallets > 0,

            ];
        })->values();

        $vendors = Vendor::orderBy('name')->get();
        $transporters = Transporter::orderBy('name')->get();
        $arrivedFroms = ArrivedFrom::orderBy('name')->get();
        $products = Product::with('uom', 'group')->orderBy('name')->get();
        $productGroups = ProductGroup::where('status', 1)->orderBy('name')->get();

        return view('inbound.edit', compact(
            'stockIn', 'warehouses', 'warehouseData', 'vendors', 'transporters', 'arrivedFroms',
            'products', 'productGroups', 'groupedItems'
        ));
    }

    /**
     * Update inbound header and items
     */
    public function update(Request $request, StockIn $stockIn)
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
            'gatepass_no' => 'nullable|string|max:80',

            'dispatched_invoice_no' => 'nullable|string|max:80',
            'dispatcher_sig' => 'nullable|string|max:255',
            'picker' => 'nullable|string|max:120',
            'remarks' => 'nullable|string|max:1000',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.units_received' => 'nullable|numeric|min:0',
            'items.*.qc_remarks' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $stockIn) {
                // 1. Update Header
                $stockIn->update($request->only([
                    'warehouse_id', 'vendor_id', 'arrived_from_id', 'transporter_id',
                    'shipment_type', 'vehicle_in_time', 'vehicle_out_time', 'vehicle_no',
                    'vehicle_size', 'driver_name', 'driver_mobile', 'po_no', 'ibd_no',
                    'shipment_no', 'sto_no', 'delivery_no', 'gatepass_no', 'dispatched_invoice_no',
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
                                    'qc_remarks' => $itemData['qc_remarks'] ?? null,
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
    private function createItemSplits($stockIn, $primaryWarehouse, $product, $itemData)
    {
        $units = (int) $itemData['units_received'];
        $packSize = (float) $product->pack_size;

        $manualRowId = !empty($itemData['warehouse_row_id']) ? (int) $itemData['warehouse_row_id'] : null;
        $manualPalletStart = isset($itemData['pallet_start']) && $itemData['pallet_start'] !== '' ? (int) $itemData['pallet_start'] : null;

        $palletsNeeded = (int) ($itemData['pallets_used'] ?? 0);
        if ($palletsNeeded === 0 && $product->cartons_per_pallet > 0) {
            $palletsNeeded = (int) ceil($units / $product->cartons_per_pallet);
        }

        $simulatedOccupied = [];

        $splits = \App\Services\WarehouseRowFifo::assign(
            $primaryWarehouse->id,
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
                'qc_remarks'         => $itemData['qc_remarks'] ?? null,
                'damage_stock'       => !empty($itemData['damage_stock']),
                'remarks'            => $itemData['remarks'] ?? null,
                'uom_snapshot'       => optional($product->uom)->name,
                'packing_snapshot'   => optional($product->packingType)->name,
            ]);
        }
    }

    public function export(Request $request)
    {
        $query = StockInItem::whereHas('stockIn', fn($q) => $q->where('source_type', 'inbound'))
            ->with(['product.category', 'product.uom', 'product.packingType', 'warehouse', 'stockIn', 'stockIn.warehouse', 'stockIn.vendor', 'stockIn.transporter', 'stockIn.arrivedFrom', 'warehouseRow']);

        if ($request->filled('selected_ids')) {
            $selectedIds = is_array($request->selected_ids) ? $request->selected_ids : explode(',', $request->selected_ids);
            $query->whereIn('id', $selectedIds);
        }

        if ($request->filled('qc_status')) {
            $query->where('quality_clearance', $request->qc_status);
        }
        if ($request->filled('warehouse_id')) {
            $query->whereHas('stockIn', fn($q) => $q->where('warehouse_id', $request->warehouse_id));
        }
        if ($request->filled('vendor_id')) {
            $query->whereHas('stockIn', fn($q) => $q->where('vendor_id', $request->vendor_id));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('product_group_id')) {
            $groupId = $request->product_group_id;
            $query->whereHas('product', fn($q) => $q->where('product_group_id', $groupId));
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
                $q->whereHas('stockIn', function ($sq) use ($search) {
                    $sq->where('driver_name', 'like', "%{$search}%")
                        ->orWhere('driver_mobile', 'like', "%{$search}%")
                        ->orWhere('vehicle_no', 'like', "%{$search}%")
                        ->orWhere('vehicle_size', 'like', "%{$search}%")
                        ->orWhere('shipment_no', 'like', "%{$search}%")
                        ->orWhere('delivery_no', 'like', "%{$search}%")
                        ->orWhere('sto_no', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%")
                        ->orWhere('ibd_no', 'like', "%{$search}%")
                        ->orWhere('inbound_invoice_no', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('transporter', fn($tq) => $tq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('arrivedFrom', fn($aq) => $aq->where('name', 'like', "%{$search}%"));
                })->orWhere('sap_batch', 'like', "%{$search}%")
                  ->orWhere('vendor_batch', 'like', "%{$search}%");
            });
        }

        $items = $query->latest()->get();

        // Build row-letter mapping
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

        // Compute pallet positions
        $rowPalletOffsets = [];
        $inboundPositions = [];
        $allItems = StockInItem::whereHas('stockIn', fn($q) => $q->whereIn('source_type', ['opening', 'inbound']))
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
            if ($item->pallet_start !== null) {
                $palletStart = (int) $item->pallet_start;
                $palletEnd = $palletStart + $palletCount - 1;
            } else {
                if (!isset($rowPalletOffsets[$rowKey])) $rowPalletOffsets[$rowKey] = 0;
                $palletStart = $rowPalletOffsets[$rowKey] + 1;
                $palletEnd = $rowPalletOffsets[$rowKey] + $palletCount;
                $rowPalletOffsets[$rowKey] = $palletEnd;
            }
            if ($item->stockIn->source_type === 'inbound') {
                $rw = $rowLetterMap[$rowKey] ?? '';
                $wp = str_pad($whId, 2, '0', STR_PAD_LEFT);
                $inboundPositions[$item->id] = [
                    'start' => $palletStart, 'end' => $palletEnd,
                    'letter' => $rw, 'wh_padded' => $wp,
                ];
            }
        }

        $filename = 'inbound_stock_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($items, $inboundPositions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'DATE', 'Item Code', 'ITEM DESCRIPTION', 'Group', 'Warehouse', 'Category', 'UOM',
                'Vendor Batch', 'PO', 'IBD', 'SAP Batch', 'Packing',
                'Pack Size', 'RECEIVED Units', 'Total Qty', 'Balance Qty',
                'MFG Date', 'Expiry Date', 'Inbound Invoice', 'GATEPASS#', 'BILTY #',
                'DC#', 'CHALAN #', 'Delivery#', 'Shipment#',
                'Vendor', 'Arrived From', 'Vehicle#', 'Vehicle Size',
                'Transporter', 'DRIVER NAME', 'DRIVER CELL#', 'Vehicle In DATE & Time',
                'Vehicle Out DATE & Time', 'Shipment Type', 'Quality Check', 'QC Remarks', 'Blocked',
                'Hold', 'Remarks'
            ]);

            foreach ($items as $item) {
                $unitsVal = $item->units_received;
                $qtyVal = $item->total_quantity;
                $balVal = $item->balance_quantity;
                $palletsVal = $item->pallets_used ?? 0;
                $dateVal = $item->created_at ? (method_exists($item->created_at, 'format') ? $item->created_at->format('d.m.Y H:i') : $item->created_at) : '';

                $pos = $inboundPositions[$item->id] ?? null;

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
                    
                    $dispatchedQty = max(0, $totalQty - $totalBalance);
                    $remainingToDrain = $dispatchedQty;

                    for ($p = $palletStart; $p <= $palletEnd; $p++) {
                        $isLast = ($p == $palletEnd);
                        if ($maxPerPallet) {
                            $perPalletUnits = min($maxPerPallet, $remainingUnits);
                        } else {
                            $perPalletUnits = $numPallets > 0 ? $totalUnits / $numPallets : $totalUnits;
                        }
                        $ratio = $totalUnits > 0 ? $perPalletUnits / $totalUnits : 0;
                        $palletQty = $isLast ? $totalQty - $assignedQty : round($ratio * $totalQty, 4);
                        
                        if ($remainingToDrain >= $palletQty) {
                            $palletBalance = 0;
                            $remainingToDrain -= $palletQty;
                        } else {
                            $palletBalance = $palletQty - $remainingToDrain;
                            $remainingToDrain = 0;
                        }

                        $remainingUnits -= $perPalletUnits;
                        $assignedQty += $palletQty;

                        $psPadded = str_pad($p, 3, '0', STR_PAD_LEFT);
                        $rowNameStr = $item->warehouseRow->row_name ?? '';
                        $wName = (strpos($rowNameStr, '.') !== false) ? explode('.', $rowNameStr)[0] : "W{$whPadded}";
                        $warehouseDisplay = "{$wName}.{$rowLetter}{$psPadded}";

                        fputcsv($file, [
                            $dateVal,
                            $item->product?->item_code ?? '',
                            $item->product?->name ?? '',
                            $item->product?->group?->name ?? '',
                            $warehouseDisplay,
                            $item->product?->category?->name ?? '',
                            $item->product?->uom?->name ?? '',
                            $item->vendor_batch ?? '',
                            $item->po_no ?? '',
                            $item->ibd_no ?? '',
                            $item->sap_batch ?? '',
                            $item->product?->packingType?->name ?? '',
                            $item->pack_size_snapshot,
                            $perPalletUnits,
                            $palletQty,
                            $palletBalance,
                            $item->mfg_date ? (method_exists($item->mfg_date, 'format') ? $item->mfg_date->format('d.m.Y') : $item->mfg_date) : '',
                            $item->expiry_date ? (method_exists($item->expiry_date, 'format') ? $item->expiry_date->format('d.m.Y') : $item->expiry_date) : '',
                            $item->stockIn?->inbound_invoice_no ?? '',
                            $item->stockIn?->gatepass_no ?? '',
                            $item->stockIn?->shipment_no ?? '',
                            $item->stockIn?->dispatcher_sig ?? '',
                            $item->stockIn?->picker ?? '',
                            $item->stockIn?->delivery_no ?? '',
                            $item->stockIn?->shipment_no ?? '',
                            $item->stockIn?->vendor?->name ?? '',
                            $item->stockIn?->arrivedFrom?->name ?? '',
                            $item->stockIn?->vehicle_no ?? '',
                            $item->stockIn?->vehicle_size ?? '',
                            $item->stockIn?->transporter?->name ?? '',
                            $item->stockIn?->driver_name ?? '',
                            $item->stockIn?->driver_mobile ?? '',
                            $item->stockIn?->vehicle_in_time ?? '',
                            $item->stockIn?->vehicle_out_time ?? '',
                            $item->stockIn?->shipment_type ?? '',
                            $item->quality_clearance ?? '',
                            $item->qc_remarks ?? '',
                            $item->block_stock ? 'Yes' : 'No',
                            $item->hold_stock ? 'Yes' : 'No',
                            $item->remarks ?? '',
                        ]);
                    }
                } else {
                    $warehouseDisplay = $item->warehouse->name ?? '';

                    fputcsv($file, [
                        $dateVal,
                        $item->product?->item_code ?? '',
                        $item->product?->name ?? '',
                        $item->product?->group?->name ?? '',
                        $warehouseDisplay,
                        $item->product?->category?->name ?? '',
                        $item->product?->uom?->name ?? '',
                        $item->vendor_batch ?? '',
                        $item->po_no ?? '',
                        $item->ibd_no ?? '',
                        $item->sap_batch ?? '',
                        $item->product?->packingType?->name ?? '',
                        $item->pack_size_snapshot,
                        $unitsVal,
                        $qtyVal,
                        $balVal,
                        $item->mfg_date ? (method_exists($item->mfg_date, 'format') ? $item->mfg_date->format('d.m.Y') : $item->mfg_date) : '',
                        $item->expiry_date ? (method_exists($item->expiry_date, 'format') ? $item->expiry_date->format('d.m.Y') : $item->expiry_date) : '',
                        $item->stockIn?->inbound_invoice_no ?? '',
                        $item->stockIn?->gatepass_no ?? '',
                        $item->stockIn?->shipment_no ?? '',
                        $item->stockIn?->dispatcher_sig ?? '',
                        $item->stockIn?->picker ?? '',
                        $item->stockIn?->delivery_no ?? '',
                        $item->stockIn?->shipment_no ?? '',
                        $item->stockIn?->vendor?->name ?? '',
                        $item->stockIn?->arrivedFrom?->name ?? '',
                        $item->stockIn?->vehicle_no ?? '',
                        $item->stockIn?->vehicle_size ?? '',
                        $item->stockIn?->transporter?->name ?? '',
                        $item->stockIn?->driver_name ?? '',
                        $item->stockIn?->driver_mobile ?? '',
                        $item->stockIn?->vehicle_in_time ?? '',
                        $item->stockIn?->vehicle_out_time ?? '',
                        $item->stockIn?->shipment_type ?? '',
                        $item->quality_clearance ?? '',
                        $item->block_stock ? 'Yes' : 'No',
                        $item->hold_stock ? 'Yes' : 'No',
                        $item->remarks ?? '',
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadTemplate()
    {
        $filename = 'inbound_stock_import_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () {
        $file = fopen('php://output', 'w');
        fputcsv($file, [
            'DATE', 'Item Code', 'ITEM DESCRIPTION', 'Group', 'Warehouse', 'Category', 'UOM',
            'Vendor Batch', 'PO', 'IBD', 'SAP Batch', 'Packing',
            'Pack Size', 'RECEIVED Units', 'Total Qty', 'Balance Qty',
            'MFG Date', 'Expiry Date', 'Inbound Invoice', 'GATEPASS#', 'BILTY #',
            'DC#', 'CHALAN #', 'Delivery#', 'Shipment#',
            'Vendor', 'Arrived From', 'Vehicle#', 'Vehicle Size',
            'Transporter', 'DRIVER NAME', 'DRIVER CELL#', 'Vehicle In DATE & Time',
            'Vehicle Out DATE & Time', 'Shipment Type', 'Quality Check', 'Blocked',
            'Hold', 'Remarks'
        ]);
        fputcsv($file, [
            '15.01.2024', '001', 'Sample Product', 'Sample Group', 'Main Warehouse', 'Sample Category', 'PCS',
            'VB-001', 'PO-001', 'IBD-001', 'SB-001', 'Box',
            '10', '100', '1000', '1000',
            '15.01.2024', '15.01.2026', 'INV-001', 'DISP-001', 'SH-001',
            'John Sig', 'Picker Name', 'DL-001', 'SH-001',
            'ACME Corp', 'Supplier A', 'ABC-123', '40ft',
            'FastTrans', 'John Doe', '0300-1234567', '15.01.2024 08:00',
            '15.01.2024 10:00', 'manual', 'approved', 'No',
            'No', ''
        ]);
        fclose($file);
    };

        return response()->stream($callback, 200, $headers);
    }

    public function importForm()
    {
        return view('inbound.import', [
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
            'Units Received'   => ['RECEIVED Units', 'Units Received', 'units_received', 'Units'],
            'IBD'              => ['IBD', 'ibd', 'ibd_no', 'IBD No'],
            'PO'               => ['PO', 'po', 'po_no', 'PO No'],
            'SAP Batch'        => ['SAP Batch', 'sap_batch', 'SapBatch', 'Batch'],
            'Vendor Batch'     => ['Vendor Batch', 'vendor_batch', 'VendorBatch'],
            'MFG Date'         => ['MFG Date', 'mfg_date', 'MfgDate', 'Manufacturing Date'],
            'Expiry Date'      => ['Expiry Date', 'expiry_date', 'ExpiryDate', 'Exp Date'],
            'Quality Check'    => ['Quality Check', 'Quality Clearance', 'quality_clearance', 'QC'],
            'QC Remarks'       => ['QC Remarks', 'qc_remarks', 'qc remarks'],
            'Blocked'          => ['Blocked', 'block_stock', 'Block'],
            'Hold'             => ['Hold', 'hold_stock'],
            'Remarks'          => ['Remarks', 'remarks', 'Notes', 'Comment'],
            'Warehouse'        => ['Warehouse', 'warehouse', 'Warehouse Name', 'WH'],
            'Vendor'           => ['Vendor', 'vendor', 'Vendor Name'],
            'Transporter'      => ['Transporter', 'transporter', 'Transporter Name'],
            'Arrived From'     => ['Arrived From', 'arrived_from', 'ArrivedFrom', 'Source'],
            'Vehicle No'       => ['Vehicle#', 'Vehicle No', 'vehicle_no', 'Vehicle Number'],
            'Vehicle Size'     => ['Vehicle Size', 'vehicle_size'],
            'Driver Name'      => ['DRIVER NAME', 'Driver Name', 'driver_name'],
            'Driver Mobile'    => ['DRIVER CELL#', 'Driver Mobile', 'driver_mobile', 'Driver Phone'],
            'Vehicle In Time'  => ['Vehicle In DATE & Time', 'Vehicle In Time', 'vehicle_in_time', 'In Time'],
            'Vehicle Out Time' => ['Vehicle Out DATE & Time', 'Vehicle Out Time', 'vehicle_out_time', 'Out Time'],
            'Delivery No'      => ['Delivery#', 'Delivery No', 'delivery_no', 'Delivery Number'],
            'Shipment No'      => ['Shipment#', 'BILTY #', 'Shipment No', 'shipment_no', 'Shipment Number'],
            'STO No'           => ['STO No', 'sto_no', 'STO'],
            'Inbound Invoice'  => ['Inbound Invoice', 'inbound_invoice_no', 'InboundInvoice'],
            'GATEPASS#'        => ['GATEPASS#', 'GATE PASS', 'GATE PASS#', 'gatepass_no'],
            'Dispatch Invoice' => ['Dispatch Invoice', 'dispatched_invoice_no', 'Dispatch No'],
            'Dispatcher Sig'   => ['DC#', 'Dispatcher Sig', 'dispatcher_sig', 'Signature'],
            'Picker'           => ['CHALAN #', 'Picker', 'picker'],
            'Shipment Type'    => ['Shipment Type', 'shipment_type'],
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
        if (!isset($headerMap['Units Received'])) {
            fclose($handle);
            return back()->with('error', 'Missing required column "Units Received". Found: ' . implode(', ', $csvHeaders));
        }

        $allWarehouses = Warehouse::where('status', 1)->get()->keyBy(function($w) {
            return strtolower(trim($w->name));
        });

        $csvHasWarehouse = isset($headerMap['Warehouse']);

        $allWarehousePool = Warehouse::where('status', 1)->whereHas('rows')->orderBy('name')->get();
        if ($allWarehousePool->isEmpty()) {
            $allWarehousePool = Warehouse::where('status', 1)->orderBy('name')->get();
        }
        if ($allWarehousePool->isEmpty()) {
            fclose($handle);
            return back()->with('error', 'No active warehouses found.');
        }

        if ($request->warehouse_id) {
            $targetWarehouse = Warehouse::findOrFail($request->warehouse_id);
            $warehousePool = collect([$targetWarehouse]);
        } elseif ($csvHasWarehouse) {
            $warehousePool = $allWarehousePool;
        } else {
            $warehousePool = $allWarehousePool;
        }

        $errors = [];
        $imported = 0;
        $skipped = 0;
        $items = [];
        $allProducts = Product::where('status', 1)->get()->keyBy('item_code');

        $getCell = function($row, $field) use ($headerMap) {
            if (!isset($headerMap[$field])) return '';
            $idx = $headerMap[$field];
            $val = trim($row[$idx] ?? '');
            return mb_convert_encoding($val, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        };

        // Parse flexible date/time formats to MySQL date/datetime
        $parseDate = function($val) {
            if (empty($val)) return null;
            $val = trim($val);
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $val)) {
                return substr($val, 0, 10);
            }
            if (preg_match('/^(\d{1,2})[.](\d{1,2})[.](\d{2,4})$/', $val, $m)) {
                $y = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
                return "{$y}-{$m[2]}-{$m[1]}";
            }
            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2,4})$#', $val, $m)) {
                $y = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
                return "{$y}-{$m[1]}-{$m[2]}";
            }
            $ts = strtotime($val);
            return $ts ? date('Y-m-d', $ts) : null;
        };

        $parseDateTime = function($val) {
            if (empty($val)) return null;
            $val = trim($val);
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $val)) {
                return strlen($val) <= 10 ? $val : date('Y-m-d H:i:s', strtotime($val));
            }
            if (preg_match('/^(\d{1,2})[.](\d{1,2})[.](\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $val, $m)) {
                $y = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
                $h = isset($m[4]) ? str_pad($m[4], 2, '0', STR_PAD_LEFT) : '00';
                $i = isset($m[5]) ? str_pad($m[5], 2, '0', STR_PAD_LEFT) : '00';
                $s = isset($m[6]) ? str_pad($m[6], 2, '0', STR_PAD_LEFT) : '00';
                return "{$y}-{$m[2]}-{$m[1]} {$h}:{$i}:{$s}";
            }
            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$#', $val, $m)) {
                $y = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
                $h = isset($m[4]) ? str_pad($m[4], 2, '0', STR_PAD_LEFT) : '00';
                $i = isset($m[5]) ? str_pad($m[5], 2, '0', STR_PAD_LEFT) : '00';
                $s = isset($m[6]) ? str_pad($m[6], 2, '0', STR_PAD_LEFT) : '00';
                return "{$y}-{$m[1]}-{$m[2]} {$h}:{$i}:{$s}";
            }
            $ts = strtotime($val);
            return $ts ? date('Y-m-d H:i:s', $ts) : null;
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

            // Resolve reference data names to IDs
            $vendorId = null;
            $vendorName = $getCell($row, 'Vendor');
            if (!empty($vendorName)) {
                $matched = Vendor::where('name', $vendorName)->orWhere('id', $vendorName)->first();
                $vendorId = $matched ? $matched->id : null;
            }

            $transporterId = null;
            $transporterName = $getCell($row, 'Transporter');
            if (!empty($transporterName)) {
                $matched = Transporter::where('name', $transporterName)->orWhere('id', $transporterName)->first();
                $transporterId = $matched ? $matched->id : null;
            }

            $arrivedFromId = null;
            $arrivedFromName = $getCell($row, 'Arrived From');
            if (!empty($arrivedFromName)) {
                $matched = ArrivedFrom::where('name', $arrivedFromName)->orWhere('id', $arrivedFromName)->first();
                $arrivedFromId = $matched ? $matched->id : null;
            }

            $inTime = $parseDateTime($getCell($row, 'Vehicle In Time'));
            $outTime = $parseDateTime($getCell($row, 'Vehicle Out Time'));
            $mfgDate = $parseDate($getCell($row, 'MFG Date'));
            $expiryDate = $parseDate($getCell($row, 'Expiry Date'));

            $items[] = [
                'product'              => $product,
                'units'                => (int) $units,
                'warehouse'            => $rowWarehouse,
                'ibd_no'               => $getCell($row, 'IBD'),
                'po_no'                => $getCell($row, 'PO'),
                'sap_batch'            => $getCell($row, 'SAP Batch'),
                'vendor_batch'         => $getCell($row, 'Vendor Batch'),
                'mfg_date'             => $mfgDate,
                'expiry_date'          => $expiryDate,
                'quality_clearance'    => $qcValue,
                'qc_remarks'           => $getCell($row, 'QC Remarks'),
                'blocked'              => in_array(strtolower($getCell($row, 'Blocked')), ['yes', '1', 'true']),
                'hold'                 => in_array(strtolower($getCell($row, 'Hold')), ['yes', '1', 'true']),
                'remarks'              => $getCell($row, 'Remarks'),
                'vendor_id'            => $vendorId,
                'transporter_id'       => $transporterId,
                'arrived_from_id'      => $arrivedFromId,
                'vehicle_no'           => $getCell($row, 'Vehicle No'),
                'vehicle_size'         => $getCell($row, 'Vehicle Size'),
                'driver_name'          => $getCell($row, 'Driver Name'),
                'driver_mobile'        => $getCell($row, 'Driver Mobile'),
                'vehicle_in_time'      => $inTime ?: null,
                'vehicle_out_time'     => $outTime ?: null,
                'delivery_no'          => $getCell($row, 'Delivery No'),
                'shipment_no'          => $getCell($row, 'Shipment No'),
                'sto_no'               => $getCell($row, 'STO No'),
                'inbound_invoice_no'   => $getCell($row, 'Inbound Invoice'),
                'gatepass_no'          => $getCell($row, 'GATEPASS#'),
                'dispatched_invoice_no'=> $getCell($row, 'Dispatch Invoice'),
                'dispatcher_sig'       => $getCell($row, 'Dispatcher Sig'),
                'picker'               => $getCell($row, 'Picker'),
                'shipment_type'        => $getCell($row, 'Shipment Type') ?: 'manual',
            ];
        }

        fclose($handle);

        if (count($items) === 0) {
            return back()->with('error', 'No valid rows found in CSV');
        }

        try {
            DB::transaction(function () use ($warehousePool, $items, $csvHasWarehouse, $request, &$imported, &$skipped, &$errors) {

                $stockIn = null;

                foreach ($items as $i => $item) {
                    $product       = $item['product'];
                    $units         = $item['units'];
                    $packSize      = (float) $product->pack_size;
                    $cartonsPerPallet = (int) $product->cartons_per_pallet;

                    if ($item['warehouse']) {
                        $targets = collect([$item['warehouse']]);
                    } else {
                        $targets = $warehousePool;
                    }

                    $simulatedRemainingUnits = $units;
                    $warehouseAllocations = [];
                    $warehousePartials = [];

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

                    if ($simulatedRemainingUnits > 0) {
                        $targetMsg = $request->warehouse_id || $item['warehouse'] ? 'Selected warehouse(s)' : 'No warehouse';
                        $palletsNeededTotal = $cartonsPerPallet > 0 ? ceil($units / $cartonsPerPallet) : 0;
                        $errors[] = "{$targetMsg} does not have enough total space for '{$product->item_code}' ({$palletsNeededTotal} pallets needed)";
                        $skipped++;
                        continue;
                    }

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

                        // Create StockIn header on first allocation
                        if (!$stockIn) {
                            $generatedInvoiceNo = $this->generateDispatchedInvoiceNo();
                            $stockIn = StockIn::create([
                                'source_type'            => 'inbound',
                                'warehouse_id'           => $item['warehouse'] ? $item['warehouse']->id : ($request->warehouse_id ?: ($warehousePool->first()->id ?? 1)),
                                'inbound_invoice_no'     => $generatedInvoiceNo,
                                'vendor_id'              => $item['vendor_id'],
                                'transporter_id'         => $item['transporter_id'],
                                'arrived_from_id'        => $item['arrived_from_id'],
                                'vehicle_no'             => $item['vehicle_no'] ?: null,
                                'vehicle_size'           => $item['vehicle_size'] ?: null,
                                'driver_name'            => $item['driver_name'] ?: null,
                                'driver_mobile'          => $item['driver_mobile'] ?: null,
                                'vehicle_in_time'        => $item['vehicle_in_time'] ?: null,
                                'vehicle_out_time'       => $item['vehicle_out_time'] ?: null,
                                'delivery_no'            => $item['delivery_no'] ?: null,
                                'shipment_no'            => $item['shipment_no'] ?: null,
                                'sto_no'                => $item['sto_no'] ?: null,
                                'gatepass_no'            => $item['gatepass_no'] ?: null,
                                'dispatched_invoice_no'  => $item['dispatched_invoice_no'] ?: $generatedInvoiceNo,
                                'dispatcher_sig'         => $item['dispatcher_sig'] ?: null,
                                'picker'                 => $item['picker'] ?: null,
                                'shipment_type'          => $item['shipment_type'] ?: 'manual',
                                'remarks'                => 'Imported via CSV on ' . now()->format('d.m.Y H:i'),
                            ]);
                        }

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
                                'qc_remarks'         => $item['qc_remarks'],
                                'remarks'            => $item['remarks'] ?: null,
                            ]);
                        }
                    }

                    $imported++;
                }

                if (!$stockIn) {
                    $errors[] = "All units filled existing partial pallets. No new inbound header created.";
                    if ($imported > 0) $imported = 0;
                }
            });

            $message = "Imported {$imported} product(s).";
            if ($request->warehouse_id) $message .= " Warehouse: {$targetWarehouse->name}.";
            else $message .= " Auto-assigned to warehouses with available space.";
            if ($skipped > 0) $message .= " {$skipped} skipped.";
            if ($errors) $message .= " " . implode(' | ', array_slice($errors, 0, 10));

            return redirect()->route('inbound.index')->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function gatePassExport($stockInId)
    {
        $stockIn = StockIn::with([
            'items.product.category',
            'items.product.uom',
            'items.product.packingType',
            'items.warehouseRow',
            'vendor',
            'warehouse',
        ])->findOrFail($stockInId);

        $filename = 'GatePass-' . ($stockIn->dispatched_invoice_no ?: $stockIn->inbound_invoice_no ?: $stockIn->id) . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $csv = fopen('php://memory', 'w');

        fputcsv($csv, [
            'DATE',
            'Item Code',
            'DESCRIPTION',
            'W/H. (LOCATION)',
            'Group',
            'UOM',
            'Vendor Batch',
            'PO',
            'Packing',
            'Pack Size',
            'RECEIVED Units',
            'Total Qty',
            'MFG Date',
            'Expiry Date',
            'Inbound Invoice #',
            'GATE PASS ETC #',
            'Vehicle In DATE & Time',
            'Vehicle Out DATE & Time',
            'Remarks',
        ]);

        $totalItems = 0;

        foreach ($stockIn->items as $item) {
            $product = $item->product;

            $location = $stockIn->warehouse->name ?? '';

            $vehicleIn = $stockIn->vehicle_in_time
                ? ($stockIn->vehicle_in_time instanceof \Carbon\Carbon ? $stockIn->vehicle_in_time->format('d.m.Y H:i') : $stockIn->vehicle_in_time)
                : '';
            $vehicleOut = $stockIn->vehicle_out_time
                ? ($stockIn->vehicle_out_time instanceof \Carbon\Carbon ? $stockIn->vehicle_out_time->format('d.m.Y H:i') : $stockIn->vehicle_out_time)
                : '';
            $mfgDate = $item->mfg_date
                ? (method_exists($item->mfg_date, 'format') ? $item->mfg_date->format('d.m.Y') : $item->mfg_date)
                : '';
            $expiryDate = $item->expiry_date
                ? (method_exists($item->expiry_date, 'format') ? $item->expiry_date->format('d.m.Y') : $item->expiry_date)
                : '';

            fputcsv($csv, [
                $stockIn->created_at ? $stockIn->created_at->format('d.m.Y') : '',
                optional($product)->item_code ?? '',
                optional($product)->name ?? '',
                $location,
                optional(optional($product)->group)->name ?? '',
                optional(optional($product)->uom)->name ?? '',
                $item->vendor_batch ?? '',
                $item->po_no ?? ($stockIn->po_no ?? ''),
                optional(optional($product)->packingType)->name ?? '',
                $item->pack_size_snapshot ?? '',
                $item->units_received ?? 0,
                $item->total_quantity ?? 0,
                $mfgDate,
                $expiryDate,
                $stockIn->inbound_invoice_no ?? '',
                $stockIn->gatepass_no ?? '',
                $vehicleIn,
                $vehicleOut,
                $item->remarks ?? ($stockIn->remarks ?? ''),
            ]);

            $totalItems++;
        }

        fputcsv($csv, ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
        fputcsv($csv, ['Total Items:', $totalItems]);

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, $headers);
    }
}
