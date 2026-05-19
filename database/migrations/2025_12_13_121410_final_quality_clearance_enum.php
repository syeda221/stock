<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->enum('quality_clearance', ['pending','approved','rejected'])
                  ->default('pending')
                  ->change();
        });
    }

    public function down(): void {}
};
