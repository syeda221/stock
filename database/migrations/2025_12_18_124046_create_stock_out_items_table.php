<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_out_items', function (Blueprint $table) {
            $table->id();

    // LINKS
    $table->unsignedBigInteger('stock_out_id');
    $table->unsignedBigInteger('stock_in_item_id');

    $table->unsignedBigInteger('product_id');
    $table->unsignedBigInteger('warehouse_id');
    $table->unsignedBigInteger('warehouse_row_id')->nullable();

    // BATCH SNAPSHOT (single source of truth)
    $table->string('sap_batch')->nullable();
    $table->string('vendor_batch')->nullable();

    $table->string('po_no')->nullable();
    $table->string('ibd_no')->nullable();
    $table->string('sto_no')->nullable();
    $table->string('dn_no')->nullable();
    $table->date('mfg_date')->nullable();
    $table->date('expiry_date')->nullable();

    // QUANTITY
    $table->integer('units_dispatch');            // cartons / packs
    $table->decimal('pack_size_snapshot',10,3);  // snapshot
    $table->decimal('dispatch_quantity',12,3);   // final qty

    $table->integer('pallets_returned')->default(0);

    $table->text('remarks')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_out_items');
    }
};
