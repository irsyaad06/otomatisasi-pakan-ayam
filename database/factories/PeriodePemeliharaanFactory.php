<?php

namespace Database\Factories;

use App\Models\PeriodePemeliharaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodePemeliharaan>
 */
class PeriodePemeliharaanFactory extends Factory
{
    protected $model = PeriodePemeliharaan::class;

    public function definition(): array
    {
        return [
            'nama_periode' => 'Periode ' . fake()->word() . ' ' . now()->format('Y'),
            'tanggal_mulai' => now()->subDays(10)->format('Y-m-d'),
            'tanggal_selesai' => now()->addDays(25)->format('Y-m-d'),
            'jumlah_ayam' => fake()->numberBetween(1000, 5000),
            'status' => 'aktif',
        ];
    }
}
