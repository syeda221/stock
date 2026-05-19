<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_outs', function (Blueprint $table) {
            $table->id();

            /* ===== TYPE ===== */
            $table->enum('source_type', ['sale','transfer','return'])->default('sale');
            $table->enum('shipment_type', ['manual','auto'])->default('manual');

            /* ===== RELATIONS (NO FK FOR NOW) ===== */
            $table->unsignedBigInteger('warehouse_id');      // FROM
            $table->unsignedBigInteger('to_warehouse_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('transporter_id')->nullable();

            /* ===== DISPATCH ===== */
            $table->string('dispatched_invoice_no')->nullable();
            $table->string('delivery_no')->nullable();
            $table->string('shipment_no')->nullable();
            $table->string('gatepass_no')->nullable();
            /* ===== PEOPLE ===== */
            $table->string('dispatcher_sig')->nullable();
            $table->string('picker')->nullable();

            /* ===== VEHICLE ===== */
            $table->string('vehicle_no')->nullable();
            $table->string('vehicle_size')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_mobile')->nullable();
            $table->dateTime('vehicle_in_time')->nullable();
            $table->dateTime('vehicle_out_time')->nullable();

            /* ===== REMARKS ===== */
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_outs');
    }
};
