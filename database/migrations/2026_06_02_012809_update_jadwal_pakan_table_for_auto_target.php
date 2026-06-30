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
        Schema::table('jadwal_pakan', function (Blueprint $table) {
            $table->boolean('target_otomatis')->default(true)->after('status_aktif');
            $table->integer('target_pakan_gram')->nullable()->change();
            $table->integer('durasi_motor_detik')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_pakan', function (Blueprint $table) {
            $table->dropColumn('target_otomatis');
            $table->integer('target_pakan_gram')->nullable(false)->change();
            $table->integer('durasi_motor_detik')->nullable(false)->change();
        });
    }
};
