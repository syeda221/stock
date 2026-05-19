<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('stock_ins', function (Blueprint $table) {
            $table->id();
            $table->string('inbound_invoice_no')->nullable();
            $table->enum('source_type', ['opening', 'inbound']);

            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('arrived_from_id')->nullable()->constrained('arrived_froms')->nullOnDelete();

            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();

            $table->enum('shipment_type', ['manual', 'auto'])->default('manual');

            $table->dateTime('vehicle_in_time')->nullable();
            $table->dateTime('vehicle_out_time')->nullable();

            $table->string('vehicle_no')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_mobile')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ins');
    }
};
