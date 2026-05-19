<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ins', function (Blueprint $table) {

            // FK
            if (! Schema::hasColumn('stock_ins', 'transporter_id')) {
                $table->foreignId('transporter_id')
                    ->nullable()
                    ->constrained('transporters')
                    ->nullOnDelete()
                    ->after('arrived_from_id');
            }
            

            // Refs
            if (! Schema::hasColumn('stock_ins', 'po_no')) {
                $table->string('po_no')->nullable()->after('transporter_id');
            }
            if (! Schema::hasColumn('stock_ins', 'ibd_no')) {
                $table->string('ibd_no')->nullable()->after('po_no');
            }
            if (! Schema::hasColumn('stock_ins', 'shipment_no')) {
                $table->string('shipment_no')->nullable()->after('ibd_no');
            }
            if (! Schema::hasColumn('stock_ins', 'sto_no')) {
                $table->string('sto_no')->nullable()->after('shipment_no');
            }

            // Vehicle extra
            if (! Schema::hasColumn('stock_ins', 'vehicle_size')) {
                $table->string('vehicle_size')->nullable()->after('vehicle_no');
            }

            // Extra inbound fields
            if (! Schema::hasColumn('stock_ins', 'delivery_no')) {
                $table->string('delivery_no')->nullable()->after('shipment_no');
            }
            if (! Schema::hasColumn('stock_ins', 'dispatched_invoice_no')) {
                $table->string('dispatched_invoice_no')->nullable()->after('delivery_no');
            }
            if (! Schema::hasColumn('stock_ins', 'dispatcher_sig')) {
                $table->string('dispatcher_sig')->nullable()->after('driver_mobile');
            }
            if (! Schema::hasColumn('stock_ins', 'picker')) {
                $table->string('picker')->nullable()->after('dispatcher_sig');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_ins', function (Blueprint $table) {
            // drop FK first
            if (Schema::hasColumn('stock_ins', 'transporter_id')) {
                try {
                    $table->dropForeign(['transporter_id']);
                } catch (\Throwable $e) {
                }
            }

            foreach ([
                'transporter_id',
                'po_no', 'ibd_no', 'shipment_no', 'sto_no',
                'vehicle_size',
                'delivery_no', 'dispatched_invoice_no',
                'dispatcher_sig', 'picker',
            ] as $col) {
                if (Schema::hasColumn('stock_ins', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
