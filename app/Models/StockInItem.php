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
        'pallet_start',
        'last_pallet_vacant',
        'sound_stock',
        'block_stock',
        'hold_stock',
        'allow_expired_sale',
        'quality_clearance',
        'remarks',
    ];

    protected $casts = [
        'mfg_date' => 'date',
        'expiry_date' => 'date',
        'units_received' => 'integer',
        'pack_size_snapshot' => 'decimal:4',
        'total_quantity' => 'decimal:4',
        'balance_quantity' => 'decimal:4',
        'use_pallets' => 'boolean',
        'pallets_used' => 'integer',
        'pallet_start' => 'integer',
        'last_pallet_vacant' => 'integer',
        'sound_stock' => 'boolean',
        'block_stock' => 'boolean',
        'hold_stock' => 'boolean',
        'allow_expired_sale' => 'boolean',
    ];

    public static function computeActivePallets($item): int
    {
        $maxPerPallet = $item->product?->cartons_per_pallet ?? null;

        if ($maxPerPallet && $maxPerPallet > 0 && $item->pallets_used > 0) {
            $packSize = $item->pack_size_snapshot > 0 ? $item->pack_size_snapshot : 1;
            $remainingCartons = (int) ceil($item->balance_quantity / $packSize);
            $computed = (int) ceil($remainingCartons / $maxPerPallet);
            return max(1, min($computed, $item->pallets_used));
        }

        return $item->pallets_used ?? 0;
    }

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

    public function warehouseRow()
    {
        return $this->belongsTo(WarehouseRow::class, 'warehouse_row_id');
    }
}
