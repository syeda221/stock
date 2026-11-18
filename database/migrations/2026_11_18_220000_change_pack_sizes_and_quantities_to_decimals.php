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
        // 1. products.pack_size -> DECIMAL(12, 3)
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('pack_size', 12, 3)->default(1)->change();
        });

        // 2. stock_in_items: pack_size_snapshot, total_quantity, balance_quantity -> decimals
        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->decimal('pack_size_snapshot', 12, 3)->change();
            $table->decimal('total_quantity', 14, 3)->change();
            $table->decimal('balance_quantity', 14, 3)->change();
        });

        // 3. warehouse_stocks: quantities -> DECIMAL(14, 3)
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->decimal('total_quantity', 14, 3)->default(0)->change();
            $table->decimal('available_quantity', 14, 3)->default(0)->change();
            $table->decimal('blocked_quantity', 14, 3)->default(0)->change();
            $table->decimal('hold_quantity', 14, 3)->default(0)->change();
            $table->decimal('damaged_quantity', 14, 3)->default(0)->change();
        });

        // 4. stock_out_items: precision consistency
        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->decimal('pack_size_snapshot', 12, 3)->change();
            $table->decimal('dispatch_quantity', 14, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->decimal('pack_size_snapshot', 10, 3)->change();
            $table->decimal('dispatch_quantity', 12, 3)->change();
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->decimal('total_quantity', 12, 2)->default(0)->change();
            $table->decimal('available_quantity', 12, 2)->default(0)->change();
            $table->decimal('blocked_quantity', 12, 2)->default(0)->change();
            $table->decimal('hold_quantity', 12, 2)->default(0)->change();
            $table->decimal('damaged_quantity', 12, 2)->default(0)->change();
        });

        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->integer('pack_size_snapshot')->change();
            $table->integer('total_quantity')->change();
            $table->integer('balance_quantity')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('pack_size')->change();
        });
    }
};
