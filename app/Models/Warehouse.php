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

    /**
     * Get 2-digit warehouse code prefix (e.g. W01, W02, W03) based on warehouse name, or row name, or ID.
     */
    public static function getPrefix($warehouseId, $warehouseName = null, $rowName = null): string
    {
        if (!empty($warehouseName) && preg_match('/(\d+)/', $warehouseName, $wm)) {
            return 'W' . str_pad($wm[1], 2, '0', STR_PAD_LEFT);
        }

        if (!empty($rowName) && preg_match('/^(W\d+)/i', $rowName, $wm)) {
            return strtoupper($wm[1]);
        }

        if (!empty($warehouseId)) {
            static $whCache = [];
            if (!array_key_exists($warehouseId, $whCache)) {
                $whCache[$warehouseId] = self::where('id', $warehouseId)->value('name');
            }
            if (!empty($whCache[$warehouseId]) && preg_match('/(\d+)/', $whCache[$warehouseId], $wm)) {
                return 'W' . str_pad($wm[1], 2, '0', STR_PAD_LEFT);
            }
            return 'W' . str_pad((int) $warehouseId, 2, '0', STR_PAD_LEFT);
        }

        return 'W01';
    }

    public function getPrefixAttribute(): string
    {
        return self::getPrefix($this->id, $this->name);
    }
}
