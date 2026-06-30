<?php

namespace Database\Factories;

use App\Models\StokPakan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StokPakan>
 */
class StokPakanFactory extends Factory
{
    protected $model = StokPakan::class;

    public function definition(): array
    {
        $persentase = fake()->numberBetween(10, 100);
        $status = 'aman';
        if ($persentase < 20) {
            $status = 'hampir_habis';
        } elseif ($persentase <= 0) {
            $status = 'habis';
        }

        return [
            'berat_pakan_gram' => $persentase * 60, // e.g. 6000g is 100%
            'persentase_stok' => $persentase,
            'status_stok' => $status,
            'waktu_pembacaan' => now(),
        ];
    }
}
