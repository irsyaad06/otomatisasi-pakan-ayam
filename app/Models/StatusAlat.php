<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusAlat extends Model
{
    use HasFactory;

    protected $table = 'status_alat';

    protected $fillable = [
        'nama_perangkat',
        'status_koneksi',
        'status_motor',
        'status_sensor',
        'mode_operasi',
        'terakhir_online',
    ];

    protected $casts = [
        'terakhir_online' => 'datetime',
    ];
}
