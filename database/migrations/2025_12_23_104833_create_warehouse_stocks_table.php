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
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('product_id');
    $table->unsignedBigInteger('warehouse_id');
    $table->unsignedBigInteger('warehouse_row_id')->nullable();

    $table->decimal('total_quantity', 12, 2)->default(0);
    $table->decimal('available_quantity', 12, 2)->default(0);
    $table->decimal('blocked_quantity', 12, 2)->default(0);
    $table->decimal('hold_quantity', 12, 2)->default(0);
    $table->decimal('damaged_quantity', 12, 2)->default(0);


    $table->unique(['product_id', 'warehouse_id'], 'ws_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');
    }
};
