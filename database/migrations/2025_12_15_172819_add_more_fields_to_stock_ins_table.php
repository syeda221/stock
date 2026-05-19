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
        // Schema::table('stock_ins', function (Blueprint $table) {
        //     $table->string('delivery_no')->nullable()->after('shipment_no');
        //     $table->string('dispatched_invoice_no')->nullable()->after('delivery_no');
        //     $table->string('dispatcher_sig')->nullable()->after('driver_mobile');
        //     $table->string('picker')->nullable()->after('dispatcher_sig');
        // });
        Schema::table('stock_ins', function (Blueprint $table) {
            $table->string('delivery_no')->nullable();
            $table->string('dispatched_invoice_no')->nullable();
            $table->string('dispatcher_sig')->nullable();
            $table->string('picker')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_ins', function (Blueprint $table) {});
    }
};
