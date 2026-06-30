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
        Schema::create('jadwal_pakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_pemeliharaan_id')
                  ->constrained('periode_pemeliharaan')
                  ->onDelete('cascade');
            $table->time('waktu_pakan');
            $table->string('fase_umur'); // e.g. Starter, Grower, Finisher
            $table->integer('target_pakan_gram');
            $table->integer('durasi_motor_detik');
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_pakan');
    }
};
