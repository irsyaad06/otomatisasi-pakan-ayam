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
        Schema::create('stok_pakan', function (Blueprint $table) {
            $table->id();
            $table->integer('berat_pakan_gram');
            $table->integer('berat_gudang_gram')->default(0);
            $table->integer('persentase_stok');
            $table->string('status_stok'); // 'aman', 'hampir_habis', 'habis'
            $table->dateTime('waktu_pembacaan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_pakan');
    }
};
