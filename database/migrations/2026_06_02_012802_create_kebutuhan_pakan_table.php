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
        Schema::create('kebutuhan_pakan', function (Blueprint $table) {
            $table->id();
            $table->string('fase_umur');
            $table->integer('umur_mulai_hari');
            $table->integer('umur_selesai_hari');
            $table->integer('gram_per_ekor_per_hari');
            $table->integer('frekuensi_pemberian_per_hari');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kebutuhan_pakan');
    }
};
