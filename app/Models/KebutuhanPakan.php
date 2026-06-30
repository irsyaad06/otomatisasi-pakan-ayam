<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KebutuhanPakan extends Model
{
    use HasFactory;

    protected $table = 'kebutuhan_pakan';

    protected $fillable = [
        'fase_umur',
        'umur_mulai_hari',
        'umur_selesai_hari',
        'gram_per_ekor_per_hari',
        'frekuensi_pemberian_per_hari',
    ];

    protected $casts = [
        'umur_mulai_hari' => 'integer',
        'umur_selesai_hari' => 'integer',
        'gram_per_ekor_per_hari' => 'integer',
        'frekuensi_pemberian_per_hari' => 'integer',
    ];
}
