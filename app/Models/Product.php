<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'item_code',
        'name',
        'product_category_id',
        'product_group_id',
        'uom_id',
        'packing_type_id',
        'pack_size',
        'cartons_per_pallet',
        'status'
    ];

    public function category()
{
    return $this->belongsTo(ProductCategory::class, 'product_category_id');
}

public function group()
{
    return $this->belongsTo(ProductGroup::class, 'product_group_id');
}

public function uom()
{
    return $this->belongsTo(Uom::class, 'uom_id');
}

public function packingType()
{
    return $this->belongsTo(PackingType::class, 'packing_type_id');
}
public function stockInItems()
{
    return $this->hasMany(StockInItem::class);
}


}

