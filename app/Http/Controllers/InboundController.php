<?php

namespace App\Http\Controllers;

use App\Models\ArrivedFrom;
use App\Models\Product;
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

        return view('inbound.index', compact('items', 'warehouses', 'vendors', 'products'));
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

        return view('inbound.create', [
            'warehouses' => Warehouse::where('status', 1)->with('rows')->orderBy('name')->get(),
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
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.units_received' => 'required|integer|min:1',
            'items.*.quality_clearance' => 'nullable|in:pending,approved,rejected',
        ]);

        try {
            DB::transaction(function () use ($request) {

                // dd($request);
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
                if (! empty($item['use_pallets'])) {
                    $totalPallets += (int) ($item['pallets_used'] ?? 0);
                }
            }

            if ($warehouse->total_capacity && $totalPallets > $warehouse->total_capacity) {
                throw new \Exception('Pallet capacity exceeded for selected warehouse.');
            }

            /** -----------------------------
             *  3️⃣ Stock Items (Batch Wise) — FIFO Row Auto-Assignment
             * ----------------------------- */
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

                // FIFO row splits — may return >1 split if a row fills up
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

                        'use_pallets'        => $split['pallets'] > 0,
                        'pallets_used'       => $split['pallets'] > 0 ? $split['pallets'] : null,

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
        });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inbound stock added successfully.',
                    'redirect' => route('inbound.index')
                ]);
            }

            return redirect()->route('inbound.index')
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
     * Edit inbound header (for fixing missing driver/vehicle/etc on old records)
     */
    public function edit(StockIn $stockIn)
    {
        return view('inbound.edit', [
            'stockIn' => $stockIn->load(['warehouse', 'vendor', 'transporter', 'arrivedFrom']),
            'warehouses' => Warehouse::where('status', 1)->orderBy('name')->get(),
            'vendors' => Vendor::where('status', 1)->orderBy('name')->get(),
            'transporters' => Transporter::where('status', 1)->orderBy('name')->get(),
            'arrivedFroms' => ArrivedFrom::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update inbound header
     */
    public function update(Request $request, StockIn $stockIn)
    {
        $data = $request->validate([
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
        ]);

        $stockIn->update($data);

        return redirect()
            ->route('inbound.invoice', $stockIn)
            ->with('success', 'Inbound updated successfully.');
    }
}
