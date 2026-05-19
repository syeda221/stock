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
        Schema::create('stock_in_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_in_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_row_id')->nullable()->constrained('warehouse_rows')->nullOnDelete();

            $table->string('sap_batch')->nullable();
            $table->string('vendor_batch')->nullable();
            $table->string('ibd_no')->nullable();
            $table->string('po_no')->nullable();
            $table->enum('quality_clearance', ['pending', 'approved', 'rejected'])->default('pending');

            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->integer('units_received');
            $table->integer('pack_size_snapshot');

            $table->integer('total_quantity');
            $table->integer('balance_quantity');

            $table->boolean('use_pallets')->default(0);
            $table->integer('pallets_used')->nullable();

            $table->boolean('sound_stock')->default(1);
            $table->boolean('block_stock')->default(0);
            $table->boolean('hold_stock')->default(0);
            // $table->boolean('quality_clearance')->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_in_items');
    }
};
