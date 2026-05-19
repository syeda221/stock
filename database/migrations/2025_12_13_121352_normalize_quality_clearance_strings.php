<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("UPDATE stock_in_items SET quality_clearance = 'pending' WHERE quality_clearance IS NULL OR quality_clearance = '0'");
        DB::statement("UPDATE stock_in_items SET quality_clearance = 'approved' WHERE quality_clearance = '1'");
        DB::statement("UPDATE stock_in_items SET quality_clearance = 'rejected' WHERE quality_clearance = '2'");
    }

    public function down(): void {}
};
