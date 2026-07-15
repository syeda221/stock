<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOutItem extends Model
{
    protected $guarded = [];

    /* ================= CASTS ================= */
    protected $casts = [
        'mfg_date'           => 'date',
        'expiry_date'        => 'date',
        'units_dispatch'     => 'integer',
        'pack_size_snapshot' => 'decimal:3',
        'dispatch_quantity'  => 'decimal:3',
        'pallets_returned'   => 'integer',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function stockOut()
    {
        return $this->belongsTo(StockOut::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ✅ FROM WAREHOUSE (THIS WAS MISSING)
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseRow()
    {
        return $this->belongsTo(WarehouseRow::class, 'warehouse_row_id');
    }

    // ✅ inbound batch (already correct)
    public function sourceStockInItem()
    {
        return $this->belongsTo(StockInItem::class, 'stock_in_item_id');
    }

    /* ================= ACCESSORS (VERY IMPORTANT) ================= */

    // ✅ PO fallback
    public function getPoResolvedAttribute()
    {
        return $this->po_no
            ?? optional($this->sourceStockInItem)->po_no
            ?? '-';
    }

    // ✅ IBD fallback
    public function getIbdResolvedAttribute()
    {
        return $this->ibd_no
            ?? optional($this->sourceStockInItem)->ibd_no
            ?? '-';
    }

    // ✅ STO fallback
    public function getStoResolvedAttribute()
    {
        return $this->sto_no
            ?? optional($this->sourceStockInItem)->sto_no
            ?? '-';
    }

    // ✅ UOM snapshot (future-proof)
    public function getUomResolvedAttribute()
    {
        return $this->uom_snapshot
            ?? optional($this->product->uom)->code
            ?? '-';
    }

    public function getSpecificPalletAttribute()
    {
        $row = $this->warehouseRow ?? optional($this->sourceStockInItem)->warehouseRow;
        if (!$row || !$this->pallet_position) {
            return $row ? $row->row_name : '-';
        }

        $rowName = $row->row_name;
        
        $parts = explode(' to ', $rowName);
        $firstPallet = $parts[0];

        if (preg_match('/^(.*?)(\d+)$/', $firstPallet, $matches)) {
            $prefix = $matches[1];
            $startNum = (int)$matches[2];
            $actualNum = $startNum + $this->pallet_position - 1;
            $digits = strlen($matches[2]);
            return $prefix . sprintf("%0{$digits}d", $actualNum);
        }

        return $rowName . ' - P' . $this->pallet_position;
    }
}
