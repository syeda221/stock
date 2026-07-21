<?php

use App\Console\Commands\AutoRejectExpiredStock;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Auto-reject expired stock batches every day at midnight ──
Schedule::command(AutoRejectExpiredStock::class)->dailyAt('00:00')->withoutOverlapping();
