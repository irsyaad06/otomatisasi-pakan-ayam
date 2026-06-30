<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokPakan extends Model
{
    use HasFactory;

    protected $table = 'stok_pakan';

    protected $fillable = [
        'berat_pakan_gram',
        'berat_gudang_gram',
        'persentase_stok',
        'status_stok',
        'waktu_pembacaan',
    ];

    protected $casts = [
        'berat_pakan_gram' => 'integer',
        'berat_gudang_gram' => 'integer',
        'persentase_stok' => 'integer',
        'waktu_pembacaan' => 'datetime',
    ];
}
