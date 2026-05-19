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
        float $packSize
    ): array {
        // Load rows ordered by name (Row A < Row B, Row 1 < Row 2 …)
        $rows = WarehouseRow::where('warehouse_id', $warehouseId)
            ->orderBy('row_name')
            ->get();

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

        // All rows full but still pallets left → overflow into last row
        if ($remaining > 0 && $rows->isNotEmpty()) {
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
