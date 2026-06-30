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
        // 1. Add Soft Deletes to jadwal_pakan
        Schema::table('jadwal_pakan', function (Blueprint $table) {
            $table->softDeletes()->after('status_aktif');
        });

        // 2. Add Index to stok_pakan.waktu_pembacaan
        Schema::table('stok_pakan', function (Blueprint $table) {
            $table->index('waktu_pembacaan');
        });

        // 3. Add Index to log_pemberian_pakan.waktu_mulai
        Schema::table('log_pemberian_pakan', function (Blueprint $table) {
            $table->index('waktu_mulai');
        });

        // 4. Add Index to status_alat.nama_perangkat
        Schema::table('status_alat', function (Blueprint $table) {
            $table->index('nama_perangkat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_alat', function (Blueprint $table) {
            $table->dropIndex(['nama_perangkat']);
        });

        Schema::table('log_pemberian_pakan', function (Blueprint $table) {
            $table->dropIndex(['waktu_mulai']);
        });

        Schema::table('stok_pakan', function (Blueprint $table) {
            $table->dropIndex(['waktu_pembacaan']);
        });

        Schema::table('jadwal_pakan', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
