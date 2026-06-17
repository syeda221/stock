<?php

namespace App\Services;

use App\Models\WarehouseRow;
use App\Models\StockInItem;

/**
 * WarehouseRowFifo
 *
 * Automatically assigns warehouse rows to inbound/opening stock
 * using FIFO logic:
 *  - Rows are filled in order (by row_name ASC).
 *  - When a row reaches 100% pallet capacity, the next row is used.
 *  - A single item that overflows one row is split into multiple
 *    StockInItem records, one per row.
 */
class WarehouseRowFifo
{
    /**
     * Get pallet slots already occupied per row (live from DB).
     * Only counts stock_in_items with balance_quantity > 0.
     */
    public static function usedPalletsPerRow(int $warehouseId): array
    {
        $items = StockInItem::with('product')
            ->where('warehouse_id', $warehouseId)
            ->where('balance_quantity', '>', 0)
            ->whereNotNull('warehouse_row_id')
            ->get();

        $result = [];
        foreach ($items as $item) {
            $rowId = $item->warehouse_row_id;
            $result[$rowId] = ($result[$rowId] ?? 0) + StockInItem::computeActivePallets($item);
        }
        return $result;
    }

    /**
     * Find partial pallets of the same product and return fill data.
     * Returns ['splits' => [...], 'remaining_units' => int]
     */
    public static function fillPartials(
        int    $warehouseId,
        ?int   $productId,
        int    $totalUnits,
        float  $packSize,
        int    $cartonsPerPallet
    ): array {
        $splits = [];
        $remaining = $totalUnits;

        if (!$productId || $cartonsPerPallet <= 0) {
            return ['splits' => $splits, 'remaining_units' => $remaining];
        }

        $partials = StockInItem::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('balance_quantity', '>', 0)
            ->where('last_pallet_vacant', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($partials as $partial) {
            if ($remaining <= 0) break;

            $fill = min($remaining, $partial->last_pallet_vacant);
            $remaining -= $fill;

            $splits[] = [
                'stock_in_item_id' => $partial->id,
                'warehouse_row_id' => $partial->warehouse_row_id,
                'pallets'          => 0,
                'units'            => $fill,
                'qty'              => round($fill * $packSize, 4),
            ];
        }

        return ['splits' => $splits, 'remaining_units' => $remaining];
    }

    /**
     * Get total free pallet capacity across all rows in a warehouse.
     * This is the actual available space considering current usage per row.
     */
    public static function getFreeRowCapacity(int $warehouseId): int
    {
        $rows = WarehouseRow::where('warehouse_id', $warehouseId)->get();
        $maxPos = self::getRowMaxPositions($warehouseId);
        $free = 0;
        foreach ($rows as $row) {
            $capacity = (int) $row->pallet_capacity;
            $pos = (int) ($maxPos[$row->id] ?? 0);
            $free += max(0, $capacity - $pos);
        }
        return $free;
    }

    /**
     * Get the highest occupied pallet position in each row for a warehouse.
     * Only counts stock_in_items with balance_quantity > 0 and pallet_start set.
     */
    public static function getRowMaxPositions(int $warehouseId): array
    {
        $rows = WarehouseRow::where('warehouse_id', $warehouseId)->pluck('pallet_capacity', 'id');
        $result = [];
        foreach ($rows as $rowId => $capacity) {
            $maxPos = StockInItem::where('warehouse_row_id', $rowId)
                ->where('balance_quantity', '>', 0)
                ->whereNotNull('pallet_start')
                ->where('pallets_used', '>', 0)
                ->max(\Illuminate\Support\Facades\DB::raw('pallet_start + pallets_used - 1'));
            $result[$rowId] = (int) $maxPos;
        }
        return $result;
    }

    /**
     * Assign warehouse rows for a given number of pallets using FIFO.
     *
     * Returns an array of "splits", each with:
     *   - warehouse_row_id  : ID of assigned row (null if no rows configured)
     *   - pallets           : pallets assigned to this split
     *   - units             : units for this split
     *   - qty               : units × pack_size
     *
     * If palletsNeeded = 0 or no rows configured, returns one split
     * with warehouse_row_id = null.
     */
    public static function assign(
        int   $warehouseId,
        int   $palletsNeeded,
        int   $totalUnits,
        float $packSize,
        bool  $allowOverflow = true,
        int   $cartonsPerPallet = 0
    ): array {
        // Load rows and sort them using natural sorting
        // This ensures "row 10" comes after "row 2", and "row1" comes before "row 2" despite spaces
        $rows = WarehouseRow::where('warehouse_id', $warehouseId)
            ->get()
            ->sortBy('row_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        if ($rows->isEmpty() || $palletsNeeded <= 0) {
            return [[
                'warehouse_row_id' => null,
                'pallets'          => $palletsNeeded,
                'units'            => $totalUnits,
                'qty'              => round($totalUnits * $packSize, 4),
            ]];
        }

        $rowMaxPos     = self::getRowMaxPositions($warehouseId);
        $remaining      = $palletsNeeded;
        $remainingUnits = $totalUnits;
        $splits         = [];

        foreach ($rows as $row) {
            if ($remaining <= 0) break;

            $capacity    = (int) $row->pallet_capacity;
            $maxPos      = (int) ($rowMaxPos[$row->id] ?? 0);
            $available   = max(0, $capacity - $maxPos);

            if ($available <= 0) continue; // row is full — skip

            $palletsHere = min($remaining, $available);

            // Sequential fill: each row gets as many units as its pallets can hold
            if ($palletsHere >= $remaining) {
                $unitsHere = $remainingUnits;
            } elseif ($cartonsPerPallet > 0) {
                $unitsHere = min($remainingUnits, $palletsHere * $cartonsPerPallet);
            } else {
                $unitsHere = (int) round($totalUnits * ($palletsHere / $palletsNeeded));
                $unitsHere = min($unitsHere, $remainingUnits);
            }

            $palletStart   = $maxPos + 1;
            $rowMaxPos[$row->id] = $maxPos + $palletsHere;

            $remaining      -= $palletsHere;
            $remainingUnits -= $unitsHere;

            $splits[] = [
                'warehouse_row_id' => $row->id,
                'pallet_start'     => $palletStart,
                'pallets'          => $palletsHere,
                'units'            => $unitsHere,
                'qty'              => round($unitsHere * $packSize, 4),
            ];
        }

        if ($remaining > 0) {
            throw new \RuntimeException(
                "Insufficient warehouse capacity: {$remaining} pallet(s) could not be allocated. "
                . "All rows in warehouse {$warehouseId} are full."
            );
        }

        return $splits;
    }
}
