<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->string('quality_clearance', 20)->nullable()->change();
        });
    }

    public function down(): void {}
};
