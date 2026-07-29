<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('session_id')->nullable();
            $table->timestamp('last_activity')->nullable();
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('user_name')->nullable();
            $table->string('email');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('status'); // success, suspended, outside_shift, single_session_terminated, logout
            $table->timestamp('logged_in_at')->nullable();
            $table->timestamp('logged_out_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn(['shift_id', 'is_active', 'session_id', 'last_activity']);
        });
        Schema::dropIfExists('shifts');
    }
};
