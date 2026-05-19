<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockInItem extends Model
{
    protected $fillable = [
        'stock_in_id',
        'product_id',
        'warehouse_id',
        'warehouse_row_id',
        'sap_batch',
        'vendor_batch',
        'ibd_no',
        'po_no',
        'mfg_date',
        'expiry_date',
        'units_received',
        'pack_size_snapshot',
        'total_quantity',
        'balance_quantity',
        'use_pallets',
        'pallets_used',
        'sound_stock',
        'block_stock',
        'hold_stock',
        'quality_clearance',
        'remarks',
    ];

    public function stockIn()
    {
        return $this->belongsTo(StockIn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function row()
    {
        return $this->belongsTo(WarehouseRow::class, 'warehouse_row_id');
    }
    // ✅ THIS IS WHAT WAS MISSING
    public function warehouseRow()
    {
        return $this->belongsTo(WarehouseRow::class, 'warehouse_row_id');
    }
}
