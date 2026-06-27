<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$missing = DB::table('stock_in_items')
    ->whereNull('pallet_start')
    ->where('balance_quantity', '>', 0)
    ->where('pallets_used', '>', 0)
    ->count();

$total = DB::table('stock_in_items')
    ->where('balance_quantity', '>', 0)
    ->where('pallets_used', '>', 0)
    ->count();

echo "Items without pallet_start (balance>0, pallets>0): $missing/$total\n";

if ($missing > 0) {
    $rows = DB::table('stock_in_items')
        ->whereNull('pallet_start')
        ->where('balance_quantity', '>', 0)
        ->where('pallets_used', '>', 0)
        ->limit(10)
        ->get();
    foreach ($rows as $r) {
        echo "  id={$r->id} product_id={$r->product_id} pallets_used={$r->pallets_used} balance={$r->balance_quantity} wh_id={$r->warehouse_id}\n";
    }
}
