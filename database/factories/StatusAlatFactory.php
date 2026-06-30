<?php

namespace Database\Factories;

use App\Models\StatusAlat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatusAlat>
 */
class StatusAlatFactory extends Factory
{
    protected $model = StatusAlat::class;

    public function definition(): array
    {
        return [
            'nama_perangkat' => 'ESP32-KandangAyam',
            'status_koneksi' => 'online',
            'status_motor' => 'mati',
            'status_sensor' => 'normal',
            'mode_operasi' => 'otomatis',
            'terakhir_online' => now(),
        ];
    }
}
