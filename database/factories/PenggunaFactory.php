<?php

namespace Database\Factories;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pengguna>
 */
class PenggunaFactory extends Factory
{
    protected static ?string $password;

    protected $model = Pengguna::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'peran' => 'petugas', // 'admin', 'petugas'
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }
}
