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
        Schema::create('status_alat', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();
            $table->string('nama_perangkat');
            $table->decimal('berat_pakan', 8, 2)->default(0.00);
            $table->string('status_koneksi')->default('offline'); // 'online', 'offline'
            $table->string('status_motor')->default('mati'); // 'aktif', 'mati'
            $table->string('status_sensor')->default('normal'); // 'normal', 'rusak'
            $table->string('mode_operasi')->default('otomatis'); // 'otomatis', 'manual'
            $table->dateTime('terakhir_online')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_alat');
    }
};
