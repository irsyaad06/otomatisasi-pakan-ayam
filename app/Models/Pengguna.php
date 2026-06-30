<?php

namespace App\Models;

use Database\Factories\PenggunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class Pengguna extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<PenggunaFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $table = 'pengguna';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'peran',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $appends = ['name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Accessor for name to support default Laravel user attributes.
     */
    public function getNameAttribute(): string
    {
        return $this->nama;
    }

    /**
     * Mutator for name to support default Laravel user attributes.
     */
    public function setNameAttribute(string $value): void
    {
        $this->nama = $value;
    }
}
