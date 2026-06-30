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
        Schema::create('log_pemberian_pakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_pakan_id')
                  ->nullable()
                  ->constrained('jadwal_pakan')
                  ->onDelete('set null');
            $table->string('sumber'); // 'otomatis', 'manual'
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->integer('durasi_motor_detik');
            $table->integer('jumlah_pakan_keluar_gram');
            $table->string('status'); // 'berhasil', 'gagal'
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_pemberian_pakan');
    }
};
