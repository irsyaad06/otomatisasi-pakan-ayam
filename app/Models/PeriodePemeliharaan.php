<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodePemeliharaan extends Model
{
    use HasFactory;

    protected $table = 'periode_pemeliharaan';

    protected $fillable = [
        'nama_periode',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_ayam',
        'status',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function jadwalPakan(): HasMany
    {
        return $this->hasMany(JadwalPakan::class, 'periode_pemeliharaan_id');
    }
}
