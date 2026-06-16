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
        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('pallet_position')->nullable()->after('pallets_returned')
                ->comment('1-based position of this pallet within the source stock_in_item batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->dropColumn('pallet_position');
        });
    }
};
