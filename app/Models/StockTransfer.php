<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_no',
        'product_id',
        'stock_in_item_id',
        'from_warehouse_id',
        'from_warehouse_row_id',
        'from_location_display',
        'to_warehouse_id',
        'to_warehouse_row_id',
        'to_location_display',
        'quantity',
        'units',
        'transfer_date',
        'user_id',
        'remarks',
    ];

    protected $casts = [
        'transfer_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockInItem()
    {
        return $this->belongsTo(StockInItem::class);
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function fromWarehouseRow()
    {
        return $this->belongsTo(WarehouseRow::class, 'from_warehouse_row_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function toWarehouseRow()
    {
        return $this->belongsTo(WarehouseRow::class, 'to_warehouse_row_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
