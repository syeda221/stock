<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$all = DB::table('stock_ins')->orderBy('id', 'desc')->limit(20)->select('id', 'ibd_no', 'created_at', 'warehouse_id')->get();
echo "All stock_ins:\n";
foreach ($all as $r) {
    echo "ID:{$r->id} ibd_no:'{$r->ibd_no}' WH:{$r->warehouse_id} {$r->created_at}\n";
}

// Also search case-insensitive
echo "\n\nSearching like '%IBD%':\n";
$ibd = DB::table('stock_ins')->where('ibd_no', 'like', '%IBD%')->orWhere('ibd_no', 'like', '%ibd%')->orderBy('id', 'desc')->limit(10)->select('id', 'ibd_no', 'created_at')->get();
foreach ($ibd as $r) {
    echo "ID:{$r->id} ibd_no:'{$r->ibd_no}' {$r->created_at}\n";
}

echo "\nSearching for 'SPC':\n";
$spc = DB::table('stock_ins')->where('ibd_no', 'like', '%SPC%')->orderBy('id', 'desc')->limit(10)->select('id', 'ibd_no', 'created_at')->get();
foreach ($spc as $r) {
    echo "ID:{$r->id} ibd_no:'{$r->ibd_no}' {$r->created_at}\n";
}
