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
    Schema::create('warehouses', function (Blueprint $table) {
        $table->id();

        $table->string('name');
        $table->string('city')->nullable();
        $table->string('location')->nullable();

        $table->enum('capacity_mode', ['row', 'manual'])->default('manual');
        $table->integer('manual_capacity')->nullable();

        $table->integer('total_capacity')->default(0);
        $table->boolean('status')->default(1);

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
