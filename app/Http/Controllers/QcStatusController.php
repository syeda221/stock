<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockInItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class QcStatusController extends Controller
{
    /**
     * QC Management dashboard page
     */
    public function index(Request $request)
    {
        // Silently auto-reject expired stock every time page loads
        $this->rejectExpiredItems();

        $query = StockInItem::with([
            'product.category',
            'stockIn.warehouse',
            'stockIn.vendor',
            'warehouseRow',
        ])->where('balance_quantity', '>', 0);

        // Filter by QC status
        if ($request->filled('qc_status')) {
            $query->where('quality_clearance', $request->qc_status);
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->whereHas('stockIn', fn($q) => $q->where('warehouse_id', $request->warehouse_id));
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('product', fn($p) => $p->where('name', 'like', "%$s%")->orWhere('item_code', 'like', "%$s%"))
                  ->orWhere('sap_batch', 'like', "%$s%")
                  ->orWhere('vendor_batch', 'like', "%$s%")
                  ->orWhere('qc_remarks', 'like', "%$s%");
            });
        }

        // Counts for KPI cards
        $totalPending  = StockInItem::where('balance_quantity', '>', 0)->where('quality_clearance', 'pending')->count();
        $totalApproved = StockInItem::where('balance_quantity', '>', 0)->where('quality_clearance', 'approved')->count();
        $totalRejected = StockInItem::where('balance_quantity', '>', 0)->where('quality_clearance', 'rejected')->count();

        $items      = $query->latest()->paginate(25);
        $warehouses = Warehouse::orderBy('name')->get();
        $products   = Product::where('status', 1)->orderBy('name')->get();

        return view('qc.index', compact('items', 'warehouses', 'products', 'totalPending', 'totalApproved', 'totalRejected'));
    }

    /**
     * Update QC status + remarks for a stock in item
     */
    public function update(Request $request, StockInItem $item)
    {
        $request->validate([
            'quality_clearance' => 'required|in:pending,approved,rejected',
            'qc_remarks'        => 'nullable|string|max:500',
        ]);

        $item->quality_clearance = $request->quality_clearance;

        // If rejected, block the stock automatically
        if ($request->quality_clearance === 'rejected') {
            $item->block_stock = true;
            $item->sound_stock = false;
        } elseif ($request->quality_clearance === 'approved') {
            $item->block_stock = false;
            $item->sound_stock = true;
        }

        if ($request->has('qc_remarks')) {
            $item->qc_remarks = $request->qc_remarks;
        }

        $item->save();

        return response()->json([
            'success'           => true,
            'message'           => 'QC updated successfully',
            'quality_clearance' => $item->quality_clearance,
            'qc_remarks'        => $item->qc_remarks,
            'block_stock'       => $item->block_stock,
        ]);
    }

    /**
     * Bulk update QC status
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids'               => 'required|array',
            'ids.*'             => 'integer|exists:stock_in_items,id',
            'quality_clearance' => 'required|in:pending,approved,rejected',
            'qc_remarks'        => 'nullable|string|max:500',
        ]);

        $updates = ['quality_clearance' => $request->quality_clearance];

        if ($request->quality_clearance === 'rejected') {
            $updates['block_stock'] = true;
            $updates['sound_stock'] = false;
        } elseif ($request->quality_clearance === 'approved') {
            $updates['block_stock'] = false;
            $updates['sound_stock'] = true;
        }

        if ($request->filled('qc_remarks')) {
            $updates['qc_remarks'] = $request->qc_remarks;
        }

        StockInItem::whereIn('id', $request->ids)->update($updates);

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' items updated successfully',
        ]);
    }

    /**
     * Manual trigger: auto-reject all expired items (called from QC page button)
     */
    public function autoRejectExpired(Request $request)
    {
        $count = $this->rejectExpiredItems();

        return response()->json([
            'success' => true,
            'count'   => $count,
            'message' => $count > 0
                ? "✅ {$count} expired batch(es) auto-rejected and blocked from sale."
                : "✅ No new expired batches found. Everything is up to date.",
        ]);
    }

    /**
     * Core logic: find and reject all expired batches
     * Returns count of updated rows.
     */
    private function rejectExpiredItems(): int
    {
        $today = \Carbon\Carbon::today();

        $expired = StockInItem::where('balance_quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->where(function ($q) {
                $q->where('quality_clearance', '!=', 'rejected')
                  ->orWhereNull('quality_clearance');
            })
            ->get();

        foreach ($expired as $item) {
            $item->quality_clearance = 'rejected';
            $item->block_stock       = true;
            $item->sound_stock       = false;
            $item->qc_remarks        = 'Expired - Not for Sale';
            $item->save();
        }

        return $expired->count();
    }
}
