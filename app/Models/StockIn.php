<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    protected $fillable = [
        'source_type',
        'warehouse_id',
        'inbound_invoice_no',
        'gatepass_no',
        'vendor_id',
        'arrived_from_id',
        'transporter_id',
        'po_no',
        'ibd_no',
        'shipment_no',
        'sto_no',
        'vehicle_no',
        'vehicle_size',
        'vehicle_in_time',
        'vehicle_out_time',
        'driver_name',
        'driver_mobile',
        'delivery_no',
        'dispatched_invoice_no',
        'dispatcher_sig',
        'picker',
        'shipment_type',
        'remarks',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function arrivedFrom()
    {
        return $this->belongsTo(ArrivedFrom::class);
    }

    public function items()
    {
        return $this->hasMany(StockInItem::class);
    }
}
