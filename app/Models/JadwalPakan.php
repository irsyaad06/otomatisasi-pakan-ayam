<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JadwalPakan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'jadwal_pakan';

    protected $fillable = [
        'periode_pemeliharaan_id',
        'waktu_pakan',
        'fase_umur',
        'target_pakan_gram',
        'durasi_motor_detik',
        'status_aktif',
        'target_otomatis',
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
        'target_otomatis' => 'boolean',
        'target_pakan_gram' => 'integer',
        'durasi_motor_detik' => 'integer',
    ];

    public function periodePemeliharaan(): BelongsTo
    {
        return $this->belongsTo(PeriodePemeliharaan::class, 'periode_pemeliharaan_id');
    }

    public function logPemberianPakan(): HasMany
    {
        return $this->hasMany(LogPemberianPakan::class, 'jadwal_pakan_id');
    }
}
