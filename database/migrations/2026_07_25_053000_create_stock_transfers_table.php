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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 80)->unique();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('stock_in_item_id')->nullable()->constrained('stock_in_items')->onDelete('set null');
            
            // Source location
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('from_warehouse_row_id')->nullable()->constrained('warehouse_rows')->onDelete('set null');
            $table->string('from_location_display', 100)->nullable();

            // Destination location
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('to_warehouse_row_id')->nullable()->constrained('warehouse_rows')->onDelete('set null');
            $table->string('to_location_display', 100)->nullable();

            $table->decimal('quantity', 12, 4); // Transferred quantity
            $table->integer('units')->default(0);
            $table->timestamp('transfer_date')->useCurrent();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
