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
    /**
     * Get pallet slots already occupied per row (live from DB).
     * Only counts stock_in_items with balance_quantity > 0.
     * Optionally excludes items from a specific stock_in_id or item ID list.
     */
    public static function usedPalletsPerRow(int $warehouseId, ?int $ignoreStockInId = null, array $ignoreItemIds = []): array
    {
        $query = StockInItem::where('warehouse_id', $warehouseId)
            ->where('balance_quantity', '>', 0)
            ->whereNotNull('warehouse_row_id');

        if ($ignoreStockInId) {
            $query->where('stock_in_id', '!=', $ignoreStockInId);
        }
        if (!empty($ignoreItemIds)) {
            $query->whereNotIn('id', $ignoreItemIds);
        }

        $items = $query->get();

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
    public static function getFreeRowCapacity(int $warehouseId, ?int $ignoreStockInId = null, array $ignoreItemIds = []): int
    {
        $rows = WarehouseRow::where('warehouse_id', $warehouseId)->get();
        $usedPallets = self::usedPalletsPerRow($warehouseId, $ignoreStockInId, $ignoreItemIds);
        
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
     * Considers the full pallet_start to pallet_start + pallets_used - 1 range as occupied,
     * incorporating both DB records and in-memory simulated allocations.
     */
    public static function getFreeBlocksForRow(
        int $rowId, 
        int $capacity, 
        array $simulatedOccupied = [], 
        ?int $ignoreStockInId = null, 
        array $ignoreItemIds = []
    ): array {
        $query = StockInItem::where('warehouse_row_id', $rowId)
            ->where('balance_quantity', '>', 0)
            ->whereNotNull('pallet_start')
            ->where('pallets_used', '>', 0);

        if ($ignoreStockInId) {
            $query->where('stock_in_id', '!=', $ignoreStockInId);
        }
        if (!empty($ignoreItemIds)) {
            $query->whereNotIn('id', $ignoreItemIds);
        }

        $items = $query->get();

        $occupied = [];
        if (isset($simulatedOccupied[$rowId]) && is_array($simulatedOccupied[$rowId])) {
            $occupied = $simulatedOccupied[$rowId];
        }

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
     * Supports optional manual start row ID, start pallet index, simulation state,
     * and optional stock_in_id / item_ids to ignore for edit operations.
     *
     * Returns an array of "splits", each with:
     *   - warehouse_id      : ID of assigned warehouse
     *   - warehouse_row_id  : ID of assigned row (null if no rows configured)
     *   - pallet_start      : start position of pallets
     *   - pallets           : pallets assigned to this split
     *   - units             : units for this split
     *   - qty               : units × pack_size
     */
    public static function assign(
        int   $warehouseId,
        int   $palletsNeeded,
        int   $totalUnits,
        float $packSize,
        bool  $allowOverflow = true,
        int   $cartonsPerPallet = 0,
        ?int  $startRowId = null,
        ?int  $startPallet = null,
        array &$simulatedOccupied = [],
        ?int  $ignoreStockInId = null,
        array $ignoreItemIds = []
    ): array {
        if ($palletsNeeded <= 0) {
            return [[
                'warehouse_id'     => $warehouseId,
                'warehouse_row_id' => null,
                'pallet_start'     => null,
                'pallets'          => 0,
                'units'            => $totalUnits,
                'qty'              => round($totalUnits * $packSize, 4),
            ]];
        }

        // Build warehouse sequence: target warehouse first, then other active warehouses if overflow allowed
        $primaryWh = \App\Models\Warehouse::find($warehouseId);
        $otherWhs = \App\Models\Warehouse::where('status', 1)
            ->where('id', '!=', $warehouseId)
            ->orderBy('name')
            ->get();

        $warehousesToTry = collect([]);
        if ($primaryWh) {
            $warehousesToTry->push($primaryWh);
        }
        if ($allowOverflow) {
            foreach ($otherWhs as $owh) {
                $warehousesToTry->push($owh);
            }
        }

        if ($warehousesToTry->isEmpty()) {
            return [[
                'warehouse_id'     => $warehouseId,
                'warehouse_row_id' => null,
                'pallet_start'     => null,
                'pallets'          => $palletsNeeded,
                'units'            => $totalUnits,
                'qty'              => round($totalUnits * $packSize, 4),
            ]];
        }

        $remaining      = $palletsNeeded;
        $remainingUnits = $totalUnits;
        $splits         = [];

        $isFirstWarehouse = true;

        foreach ($warehousesToTry as $wh) {
            if ($remaining <= 0) break;

            $allRows = WarehouseRow::where('warehouse_id', $wh->id)
                ->orderBy('id', 'asc')
                ->get();

            if ($allRows->isEmpty()) continue;

            $orderedRows = collect([]);
            if ($isFirstWarehouse && $startRowId) {
                $startRowObj = $allRows->firstWhere('id', $startRowId);
                if ($startRowObj) {
                    $orderedRows->push($startRowObj);
                    foreach ($allRows as $r) {
                        if ($r->id != $startRowId) {
                            $orderedRows->push($r);
                        }
                    }
                } else {
                    $orderedRows = $allRows;
                }
            } else {
                $orderedRows = $allRows;
            }

            $isFirstWarehouse = false;

            foreach ($orderedRows as $row) {
                if ($remaining <= 0) break;

                $capacity = (int) $row->pallet_capacity;
                if ($capacity <= 0) continue;

                $freeBlocks = self::getFreeBlocksForRow($row->id, $capacity, $simulatedOccupied, $ignoreStockInId, $ignoreItemIds);

                // If this is the requested startRowId, filter/adjust free blocks if startPallet is provided
                if ($startRowId && $row->id == $startRowId) {
                    if ($startPallet !== null && $startPallet > 0) {
                        $adjustedBlocks = [];
                        foreach ($freeBlocks as $block) {
                            $bEnd = $block['start'] + $block['length'] - 1;
                            if ($bEnd < $startPallet) {
                                continue;
                            }
                            if ($block['start'] < $startPallet && $bEnd >= $startPallet) {
                                $adjustedBlocks[] = [
                                    'start'  => $startPallet,
                                    'length' => $bEnd - $startPallet + 1,
                                ];
                            } elseif ($block['start'] >= $startPallet) {
                                $adjustedBlocks[] = $block;
                            }
                        }
                        $freeBlocks = $adjustedBlocks;
                    }
                    $startRowId = null; // Clear so subsequent rows use normal start
                }


                foreach ($freeBlocks as $block) {
                    if ($remaining <= 0) break;

                    $palletsHere = min($remaining, $block['length']);
                    if ($palletsHere <= 0) continue;

                    if ($palletsHere >= $remaining) {
                        $unitsHere = $remainingUnits;
                    } elseif ($cartonsPerPallet > 0) {
                        $unitsHere = min($remainingUnits, $palletsHere * $cartonsPerPallet);
                    } else {
                        $unitsHere = (int) round($totalUnits * ($palletsHere / $palletsNeeded));
                        $unitsHere = min($unitsHere, $remainingUnits);
                    }
                    if ($unitsHere <= 0 && $remainingUnits > 0) {
                        $unitsHere = $remainingUnits;
                    }

                    $pStart = $block['start'];

                    // Update simulated occupation for live multi-item or preview tracking
                    for ($k = 0; $k < $palletsHere; $k++) {
                        $simulatedOccupied[$row->id][$pStart + $k] = true;
                    }

                    $splits[] = [
                        'warehouse_id'     => $wh->id,
                        'warehouse_row_id' => $row->id,
                        'pallet_start'     => $pStart,
                        'pallets'          => $palletsHere,
                        'units'            => $unitsHere,
                        'qty'              => round($unitsHere * $packSize, 4),
                    ];

                    $remaining      -= $palletsHere;
                    $remainingUnits -= $unitsHere;
                }
            }
        }

        if ($remaining > 0) {
            throw new \RuntimeException(
                "Insufficient total warehouse capacity: {$remaining} pallet(s) could not be allocated. "
                . "All available warehouses are 100% full."
            );
        }

        return $splits;
    }
}

