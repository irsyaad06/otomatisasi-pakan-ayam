<?php

namespace Database\Factories;

use App\Models\JadwalPakan;
use App\Models\PeriodePemeliharaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalPakan>
 */
class JadwalPakanFactory extends Factory
{
    protected $model = JadwalPakan::class;

    public function definition(): array
    {
        return [
            'periode_pemeliharaan_id' => PeriodePemeliharaan::factory(),
            'waktu_pakan' => fake()->time('H:i'),
            'fase_umur' => fake()->randomElement(['Starter', 'Grower', 'Finisher']),
            'target_pakan_gram' => fake()->numberBetween(1000, 5000),
            'durasi_motor_detik' => fake()->numberBetween(10, 60),
            'status_aktif' => true,
        ];
    }
}
