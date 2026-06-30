<?php

namespace Database\Factories;

use App\Models\JadwalPakan;
use App\Models\LogPemberianPakan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogPemberianPakan>
 */
class LogPemberianPakanFactory extends Factory
{
    protected $model = LogPemberianPakan::class;

    public function definition(): array
    {
        $waktuMulai = fake()->dateTimeBetween('-5 days', 'now');
        $durasi = fake()->numberBetween(10, 60);
        $waktuSelesai = (clone $waktuMulai)->modify("+{$durasi} seconds");

        return [
            'jadwal_pakan_id' => JadwalPakan::factory(),
            'sumber' => fake()->randomElement(['otomatis', 'manual']),
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'durasi_motor_detik' => $durasi,
            'jumlah_pakan_keluar_gram' => fake()->numberBetween(500, 3000),
            'status' => 'berhasil',
            'keterangan' => fake()->sentence(),
        ];
    }
}
