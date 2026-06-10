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
        return StockInItem::where('warehouse_id', $warehouseId)
            ->where('balance_quantity', '>', 0)
            ->whereNotNull('warehouse_row_id')
            ->groupBy('warehouse_row_id')
            ->selectRaw('warehouse_row_id, COALESCE(SUM(pallets_used), 0) as total_used')
            ->pluck('total_used', 'warehouse_row_id')
            ->toArray();
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
        $used = self::usedPalletsPerRow($warehouseId);
        $free = 0;
        foreach ($rows as $row) {
            $capacity = (int) $row->pallet_capacity;
            $alreadyUsed = (int) ($used[$row->id] ?? 0);
            $free += max(0, $capacity - $alreadyUsed);
        }
        return $free;
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
        bool  $allowOverflow = true
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

        $used           = self::usedPalletsPerRow($warehouseId);
        $remaining      = $palletsNeeded;
        $remainingUnits = $totalUnits;
        $splits         = [];

        foreach ($rows as $row) {
            if ($remaining <= 0) break;

            $capacity    = (int) $row->pallet_capacity;
            $alreadyUsed = (int) ($used[$row->id] ?? 0);
            $available   = max(0, $capacity - $alreadyUsed);

            if ($available <= 0) continue; // row is full — skip

            $palletsHere = min($remaining, $available);

            // Proportional units: if this is the last chunk give all remaining units
            if ($palletsHere >= $remaining) {
                $unitsHere = $remainingUnits;
            } else {
                $unitsHere = (int) round($totalUnits * ($palletsHere / $palletsNeeded));
                $unitsHere = min($unitsHere, $remainingUnits);
            }

            $remaining      -= $palletsHere;
            $remainingUnits -= $unitsHere;

            $splits[] = [
                'warehouse_row_id' => $row->id,
                'pallets'          => $palletsHere,
                'units'            => $unitsHere,
                'qty'              => round($unitsHere * $packSize, 4),
            ];
        }

        // All rows full but still pallets left → overflow into last row ONLY if allowOverflow is true
        if ($remaining > 0 && $rows->isNotEmpty() && $allowOverflow) {
            $lastRow = $rows->last();
            $last    = count($splits) - 1;

            if ($splits && $splits[$last]['warehouse_row_id'] === $lastRow->id) {
                $splits[$last]['pallets'] += $remaining;
                $splits[$last]['units']   += $remainingUnits;
                $splits[$last]['qty']     += round($remainingUnits * $packSize, 4);
            } else {
                $splits[] = [
                    'warehouse_row_id' => $lastRow->id,
                    'pallets'          => $remaining,
                    'units'            => $remainingUnits,
                    'qty'              => round($remainingUnits * $packSize, 4),
                ];
            }
        }

        return $splits;
    }
}
