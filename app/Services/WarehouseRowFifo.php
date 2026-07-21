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
        $items = StockInItem::where('warehouse_id', $warehouseId)
            ->where('balance_quantity', '>', 0)
            ->whereNotNull('warehouse_row_id')
            ->get();

        $result = [];
        foreach ($items as $item) {
            $rowId = $item->warehouse_row_id;
            $result[$rowId] = ($result[$rowId] ?? 0) + (int) max(1, $item->pallets_used);
        }
        return $result;
    }

    /**
     * Find partial pallets of the same product and return fill data.
     * Returns ['splits' => [...], 'remaining_units' => int]
     * Disabled to prevent shared pallets and conflicts.
     */
    public static function fillPartials(
        int    $warehouseId,
        ?int   $productId,
        int    $totalUnits,
        float  $packSize,
        int    $cartonsPerPallet,
        ?string $sapBatch = null,
        ?string $vendorBatch = null,
        ?string $expiryDate = null
    ): array {
        // Disallow partial pallet sharing: 1 pallet spot = 1 dedicated allocation
        return ['splits' => [], 'remaining_units' => $totalUnits];
    }

    /**
     * Get total free pallet capacity across all rows in a warehouse.
     * This is the actual available space considering current usage per row.
     */
    public static function getFreeRowCapacity(int $warehouseId): int
    {
        $rows = WarehouseRow::where('warehouse_id', $warehouseId)->get();
        $usedPallets = self::usedPalletsPerRow($warehouseId);
        
        $free = 0;
        foreach ($rows as $row) {
            $capacity = (int) $row->pallet_capacity;
            $used = (int) ($usedPallets[$row->id] ?? 0);
            $free += max(0, $capacity - $used);
        }
        return $free;
    }

    /**
     * Helper to find contiguous free blocks of pallets in a row.
     * Considers the full pallet_start to pallet_start + pallets_used - 1 range as occupied.
     */
    public static function getFreeBlocksForRow(int $rowId, int $capacity): array
    {
        $items = StockInItem::where('warehouse_row_id', $rowId)
            ->where('balance_quantity', '>', 0)
            ->whereNotNull('pallet_start')
            ->where('pallets_used', '>', 0)
            ->get();

        $occupied = [];
        foreach ($items as $item) {
            $start = (int) $item->pallet_start;
            $count = (int) $item->pallets_used;
            for ($k = 0; $k < $count; $k++) {
                $occupied[$start + $k] = true;
            }
        }

        $blocks = [];
        $currentStart = null;
        $currentLength = 0;

        for ($i = 1; $i <= $capacity; $i++) {
            if (!isset($occupied[$i])) {
                if ($currentStart === null) {
                    $currentStart = $i;
                }
                $currentLength++;
            } else {
                if ($currentStart !== null) {
                    $blocks[] = ['start' => $currentStart, 'length' => $currentLength];
                    $currentStart = null;
                    $currentLength = 0;
                }
            }
        }

        if ($currentStart !== null) {
            $blocks[] = ['start' => $currentStart, 'length' => $currentLength];
        }

        return $blocks;
    }

    /**
     * Assign warehouse rows for a given number of pallets using FIFO.
     *
     * Returns an array of "splits", each with:
     *   - warehouse_row_id  : ID of assigned row (null if no rows configured)
     *   - pallet_start      : start position of pallets
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

        $remaining      = $palletsNeeded;
        $remainingUnits = $totalUnits;
        $splits         = [];

        foreach ($rows as $row) {
            if ($remaining <= 0) break;

            $capacity   = (int) $row->pallet_capacity;
            $freeBlocks = self::getFreeBlocksForRow($row->id, $capacity);

            foreach ($freeBlocks as $block) {
                if ($remaining <= 0) break;

                $palletsHere = min($remaining, $block['length']);

                // Sequential fill: each row gets as many units as its pallets can hold
                if ($palletsHere >= $remaining) {
                    $unitsHere = $remainingUnits;
                } elseif ($cartonsPerPallet > 0) {
                    $unitsHere = min($remainingUnits, $palletsHere * $cartonsPerPallet);
                } else {
                    $unitsHere = (int) round($totalUnits * ($palletsHere / $palletsNeeded));
                    $unitsHere = min($unitsHere, $remainingUnits);
                }

                $splits[] = [
                    'warehouse_row_id' => $row->id,
                    'pallet_start'     => $block['start'],
                    'pallets'          => $palletsHere,
                    'units'            => $unitsHere,
                    'qty'              => round($unitsHere * $packSize, 4),
                ];

                $remaining      -= $palletsHere;
                $remainingUnits -= $unitsHere;
            }
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
