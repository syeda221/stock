# All Stocks Report - Data Collection Logic Explained

## 📋 Route Definition (web.php)
```php
Route::get('/all-stocks', [ReportController::class, 'allStocks'])->name('all-stocks');
Route::get('/stock-details/{product}', [ReportController::class, 'stockDetails'])->name('stock-details');
```

---

## 🎯 Controller Method: allStocks()

Ye method 4 cheezein calculate karta hai har product ke liye:

### 1. **Opening Stock** - Shuruaati stock jo system mein manually add hua
```php
$openingStock = DB::table('stock_in_items')
    ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
    ->where('stock_ins.source_type', 'opening')  // <-- KEY: 'opening' type
    ->where('stock_in_items.product_id', $product->id)
    ->sum('stock_in_items.total_quantity');
```

### 2. **Inbound Stock** - Jo maal warehouse mein aaya
```php
$inboundStock = DB::table('stock_in_items')
    ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
    ->where('stock_ins.source_type', 'inbound')  // <-- KEY: 'inbound' type
    ->where('stock_in_items.product_id', $product->id)
    ->sum('stock_in_items.total_quantity');
```

### 3. **Outbound Stock** - Jo maal customer ko dispatched hua
```php
$outboundStock = DB::table('stock_out_items')
    ->where('product_id', $product->id)
    ->sum('dispatch_quantity');
```

### 4. **Balance Stock** - Abhi warehouse mein available quantity
```php
$balanceStock = DB::table('stock_in_items')
    ->where('product_id', $product->id)
    ->sum('balance_quantity');  // <-- Real-time balance from stock_in_items
```

---

## 🗃️ Database Tables Relationships

```
┌──────────────────────┐     ┌──────────────────────┐
│     stock_ins        │     │   stock_in_items     │
├──────────────────────┤     ├──────────────────────┤
│ id                   │────▶│ stock_in_id (FK)     │
│ source_type          │     │ product_id           │
│   - 'opening'        │     │ total_quantity       │
│   - 'inbound'        │     │ balance_quantity     │ ◀── REAL-TIME BALANCE
│   - 'transfer'       │     │ warehouse_id         │
│ warehouse_id         │     │ sap_batch            │
│ vendor_id            │     │ vendor_batch         │
│ transporter_id       │     └──────────────────────┘
│ vehicle_no           │
└──────────────────────┘

┌──────────────────────┐     ┌──────────────────────┐
│     stock_outs       │     │  stock_out_items     │
├──────────────────────┤     ├──────────────────────┤
│ id                   │────▶│ stock_out_id (FK)    │
│ source_type          │     │ stock_in_item_id     │ ◀── Links to source batch
│   - 'sale'           │     │ product_id           │
│   - 'transfer'       │     │ dispatch_quantity    │ ◀── OUTBOUND QTY
│ warehouse_id         │     │ units_dispatch       │
│ customer_id          │     └──────────────────────┘
│ to_warehouse_id      │
│ transporter_id       │
└──────────────────────┘
```

---

## 📊 Formula Explanation

```
BALANCE = (Opening Total + Inbound Total) - Outbound Total
       OR
BALANCE = SUM(stock_in_items.balance_quantity)  <-- Direct from DB (real-time)
```

**Note:** Jab outbound hota hai, `OutboundController::store()` mein:
```php
DB::statement(
    'UPDATE stock_in_items SET balance_quantity = balance_quantity - ? WHERE id = ?',
    [$qty, $batch->id]
);
```

---

## 🔍 Complete allStocks() Method (Simplified)

```php
public function allStocks(Request $request)
{
    $warehouses = Warehouse::orderBy('name')->get();
    $categories = ProductCategory::orderBy('name')->get();

    $productQuery = Product::with(['category', 'uom', 'packingType']);

    // Apply filters
    if ($request->filled('warehouse_id')) {
        // warehouse filter logic
    }
    if ($request->filled('category_id')) {
        $productQuery->where('product_category_id', $request->category_id);
    }
    if ($request->filled('search')) {
        $productQuery->where(function($q) use ($request) {
            $q->where('item_code', 'like', '%'.$request->search.'%')
              ->orWhere('name', 'like', '%'.$request->search.'%');
        });
    }

    $allProducts = $productQuery->orderBy('name')->get();

    $stockReport = collect();

    foreach ($allProducts as $product) {
        // Opening Stock
        $openingStock = DB::table('stock_in_items')
            ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
            ->where('stock_ins.source_type', 'opening')
            ->where('stock_in_items.product_id', $product->id)
            ->sum('stock_in_items.total_quantity');

        // Inbound Stock
        $inboundStock = DB::table('stock_in_items')
            ->join('stock_ins', 'stock_in_items.stock_in_id', '=', 'stock_ins.id')
            ->where('stock_ins.source_type', 'inbound')
            ->where('stock_in_items.product_id', $product->id)
            ->sum('stock_in_items.total_quantity');

        // Outbound Stock
        $outboundStock = DB::table('stock_out_items')
            ->where('product_id', $product->id)
            ->sum('dispatch_quantity');

        // Balance Stock (Real-time from balance_quantity column)
        $balanceStock = DB::table('stock_in_items')
            ->where('product_id', $product->id)
            ->sum('balance_quantity');

        // Only include if has any stock activity
        if ($openingStock > 0 || $inboundStock > 0 || $outboundStock > 0 || $balanceStock > 0) {
            $stockReport->push([
                'product_id' => $product->id,
                'item_code' => $product->item_code,
                'product_name' => $product->name,
                'category' => $product->category->name ?? '-',
                'uom' => $product->uom->name ?? '-',
                'packing' => $product->packingType->name ?? '-',
                'pack_size' => $product->pack_size,
                'opening_stock' => $openingStock,
                'inbound_stock' => $inboundStock,
                'outbound_stock' => $outboundStock,
                'balance_stock' => $balanceStock,
            ]);
        }
    }

    // Summary
    $summary = [
        'total_products' => $stockReport->count(),
        'total_opening' => $stockReport->sum('opening_stock'),
        'total_inbound' => $stockReport->sum('inbound_stock'),
        'total_outbound' => $stockReport->sum('outbound_stock'),
        'total_balance' => $stockReport->sum('balance_stock'),
    ];

    return view('reports.all_stocks', compact('stockReport', 'warehouses', 'categories', 'summary'));
}
```

---

## 🎨 View Data Expectations (all_stocks.blade.php)

View ko ye variables milte hain:

| Variable | Type | Description |
|----------|------|-------------|
| `$stockReport` | Collection | Array of products with stock info |
| `$warehouses` | Collection | All warehouses for filter dropdown |
| `$categories` | Collection | All categories for filter dropdown |
| `$summary` | Array | Totals for summary cards |

### $stockReport Item Structure:
```php
[
    'product_id' => 1,
    'item_code' => 'PROD-001',
    'product_name' => 'Product Name',
    'category' => 'Category A',
    'uom' => 'KG',
    'packing' => 'Box',
    'pack_size' => 10,
    'opening_stock' => 100.00,
    'inbound_stock' => 500.00,
    'outbound_stock' => 200.00,
    'balance_stock' => 400.00,
]
```

### $summary Structure:
```php
[
    'total_products' => 25,
    'total_opening' => 1000.00,
    'total_inbound' => 5000.00,
    'total_outbound' => 2000.00,
    'total_balance' => 4000.00,
]
```

---

## 🔗 AJAX Endpoint: stockDetails()

Jab user "View Details" button click kare:

```php
public function stockDetails($productId, Request $request)
{
    $product = Product::with(['category', 'uom', 'packingType'])->findOrFail($productId);

    // Opening batches
    $openingBatches = DB::table('stock_in_items')
        ->join('stock_ins', ...)
        ->where('stock_ins.source_type', 'opening')
        ->where('stock_in_items.product_id', $productId)
        ->select('warehouse_name', 'vendor_name', 'transporter_name', 'sap_batch', 'total_quantity', ...)
        ->get();

    // Inbound batches
    $inboundBatches = DB::table('stock_in_items')
        ->join('stock_ins', ...)
        ->where('stock_ins.source_type', 'inbound')
        ->where('stock_in_items.product_id', $productId)
        ->get();

    // Outbound records
    $outboundRecords = DB::table('stock_out_items')
        ->join('stock_outs', ...)
        ->where('stock_out_items.product_id', $productId)
        ->get();

    return response()->json([
        'product' => $product,
        'opening_batches' => $openingBatches,
        'inbound_batches' => $inboundBatches,
        'outbound_records' => $outboundRecords,
    ]);
}
```

---

## ✅ Summary

1. **Data Source**: `stock_in_items` (balance_quantity) is the single source of truth for current stock
2. **Outbound Effect**: When outbound happens, it directly deducts from `balance_quantity`
3. **Report Calculation**: OpeningStock + InboundStock = TotalReceived, TotalReceived - OutboundStock = Balance
4. **Real-time**: Balance is always accurate because OutboundController updates it immediately
