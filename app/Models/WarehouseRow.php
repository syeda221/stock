<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseRow extends Model
{
    protected $fillable = [
        'warehouse_id',
        'row_name',
        'pallet_capacity',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
