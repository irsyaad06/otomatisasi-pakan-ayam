<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogPemberianPakan extends Model
{
    use HasFactory;

    protected $table = 'log_pemberian_pakan';

    protected $fillable = [
        'jadwal_pakan_id',
        'sumber',
        'waktu_mulai',
        'waktu_selesai',
        'durasi_motor_detik',
        'jumlah_pakan_keluar_gram',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'durasi_motor_detik' => 'integer',
        'jumlah_pakan_keluar_gram' => 'integer',
    ];

    public function jadwalPakan(): BelongsTo
    {
        return $this->belongsTo(JadwalPakan::class, 'jadwal_pakan_id');
    }
}
