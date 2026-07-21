<?php

namespace App\Console\Commands;

use App\Models\StockInItem;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoRejectExpiredStock extends Command
{
    protected $signature   = 'stock:auto-reject-expired';
    protected $description = 'Auto-reject all expired stock batches (balance_quantity > 0) and block them from sale.';

    public function handle(): int
    {
        $today = Carbon::today();

        $expired = StockInItem::where('balance_quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->where(function ($q) {
                // Only update those not already rejected
                $q->where('quality_clearance', '!=', 'rejected')
                  ->orWhereNull('quality_clearance');
            })
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired stock found. All good!');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($expired as $item) {
            $item->quality_clearance = 'rejected';
            $item->block_stock       = true;
            $item->sound_stock       = false;
            $item->qc_remarks        = 'Expired - Not for Sale';
            $item->save();
            $count++;
        }

        $this->info("Auto-rejected {$count} expired stock batch(es) successfully.");
        return self::SUCCESS;
    }
}
