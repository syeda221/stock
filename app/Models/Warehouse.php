<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'city',
        'location',
        'capacity_mode',
        'manual_capacity',
        'total_capacity',
        'status',
    ];

    public function rows()
    {
        return $this->hasMany(WarehouseRow::class);
    }
    public function stockInItems()
{
    return $this->hasMany(StockInItem::class);
}

}
