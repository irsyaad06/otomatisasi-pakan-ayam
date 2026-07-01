<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusAlat extends Model
{
    use HasFactory;

    protected $table = 'status_alat';

    protected $fillable = [
        'device_id',
        'nama_perangkat',
        'berat_pakan',
        'status_koneksi',
        'status_motor',
        'status_sensor',
        'mode_operasi',
        'terakhir_online',
    ];

    protected $casts = [
        'berat_pakan' => 'float',
        'terakhir_online' => 'datetime',
    ];

    /**
     * Check and batch update stale connections.
     */
    public static function checkConnections(): void
    {
        $cutoff = now()->subSeconds(30);
        self::where('status_koneksi', 'online')
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('terakhir_online')
                      ->orWhere('terakhir_online', '<', $cutoff);
            })
            ->update(['status_koneksi' => 'offline']);
    }

    /**
     * Dynamic connection status accessor.
     */
    public function getStatusKoneksiAttribute($value)
    {
        if ($value === 'online' && $this->terakhir_online && $this->terakhir_online->lt(now()->subSeconds(30))) {
            return 'offline';
        }
        return $value;
    }
}
