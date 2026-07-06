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

    public function getPalletBalances(): array
    {
        $maxPerPallet = $this->product?->cartons_per_pallet ?? null;
        $active = self::computeActivePallets($this);

        if (!$maxPerPallet || $maxPerPallet <= 0 || $this->pallets_used <= 0) {
            $qtyPer = $active > 0 ? round($this->balance_quantity / $active, 4) : $this->balance_quantity;
            $arr = [];
            for ($i = 0; $i < $active; $i++) {
                $arr[$i] = $qtyPer;
            }
            return $arr;
        }

        $packSize = $this->pack_size_snapshot > 0 ? $this->pack_size_snapshot : 1;
        $maxPerPalletInUnits = $maxPerPallet * $packSize;
        
        $originalQty = round((float)$this->units_received * $packSize, 4);
        
        $pallets = [];
        $remainingOriginal = $originalQty;
        for ($i = 0; $i < $this->pallets_used; $i++) {
            $fill = min($maxPerPalletInUnits, $remainingOriginal);
            $pallets[$i] = $fill;
            $remainingOriginal -= $fill;
            if ($remainingOriginal <= 0) break;
        }
        
        $dispatchedQty = max(0, $originalQty - (float)$this->balance_quantity);
        $remainingToDrain = $dispatchedQty;
        
        foreach ($pallets as $i => $qty) {
            if ($remainingToDrain <= 0) break;
            $take = min($remainingToDrain, $qty);
            $pallets[$i] -= $take;
            $remainingToDrain -= $take;
        }
        
        $activePallets = [];
        foreach ($pallets as $qty) {
            if ($qty > 0.0001) {
                $activePallets[] = $qty;
            }
        }
        
        if (empty($activePallets)) {
            return [0 => $this->balance_quantity];
        }
        
        return $activePallets;
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
