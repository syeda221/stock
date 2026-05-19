<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOut extends Model
{
    protected $fillable = [
        'source_type',
        'shipment_type',

        'warehouse_id',
        'to_warehouse_id',
        'customer_id',
        'vendor_id',
        'transporter_id',

        'vehicle_no',
        'vehicle_size',
        'driver_name',
        'driver_mobile',
        'vehicle_in_time',
        'vehicle_out_time',

        'dispatched_invoice_no',
        'delivery_no',
        'shipment_no',
        'gatepass_no',
        'po_no',
        'sto_no',

        'dispatcher_sig',
        'picker',
        'remarks',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // ✅ THIS WAS MISSING
    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function items()
    {
        return $this->hasMany(StockOutItem::class);
    }
}
