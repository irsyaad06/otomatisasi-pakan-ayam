<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StatusAlat;

class CheckDeviceConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:check-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ubah status koneksi perangkat menjadi offline jika tidak mengirim data selama 30 detik';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = now()->subSeconds(30);

        $affected = StatusAlat::where('status_koneksi', 'online')
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('terakhir_online')
                      ->orWhere('terakhir_online', '<', $cutoff);
            })
            ->update([
                'status_koneksi' => 'offline',
            ]);

        $this->info("Pengecekan selesai. {$affected} perangkat diubah statusnya menjadi offline.");

        return Command::SUCCESS;
    }
}
