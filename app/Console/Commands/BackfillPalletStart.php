<?php

namespace App\Console\Commands;

use App\Models\StockInItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPalletStart extends Command
{
    protected $signature = 'stock:backfill-pallet-start';
    protected $description = 'Compute and store pallet_start for historical stock_in_items that lack it';

    public function handle()
    {
        $this->info('Computing pallet_start for entries without one...');

        $rowIds = StockInItem::whereNull('pallet_start')
            ->where('pallets_used', '>', 0)
            ->whereNotNull('warehouse_row_id')
            ->distinct()
            ->pluck('warehouse_row_id');

        $updated = 0;

        foreach ($rowIds as $rowId) {
            $items = StockInItem::where('warehouse_row_id', $rowId)
                ->orderBy('id')
                ->get();

            $offset = 0;

            foreach ($items as $item) {
                if ($item->pallet_start !== null) {
                    $itemEnd = $item->pallet_start + $item->pallets_used - 1;
                    $offset = max($offset, $itemEnd);
                    continue;
                }

                if (!$item->pallets_used || $item->pallets_used <= 0) continue;

                $start = $offset + 1;
                $item->update(['pallet_start' => $start]);
                $offset = $start + $item->pallets_used - 1;
                $updated++;
            }
        }

        $this->info("Backfilled {$updated} entries.");
    }
}
