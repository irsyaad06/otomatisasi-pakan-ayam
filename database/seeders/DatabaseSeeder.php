<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PeriodePemeliharaan;
use App\Models\JadwalPakan;
use App\Models\StokPakan;
use App\Models\LogPemberianPakan;
use App\Models\StatusAlat;
use App\Models\KebutuhanPakan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Pengguna (Test User / Administrator)
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'nama' => 'Pak Icad (Petugas)',
                'password' => Hash::make('password'),
                'peran' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 1.5. Seed Kebutuhan Pakan Master
        KebutuhanPakan::updateOrCreate(
            ['fase_umur' => 'Starter'],
            [
                'umur_mulai_hari' => 0,
                'umur_selesai_hari' => 10,
                'gram_per_ekor_per_hari' => 25,
                'frekuensi_pemberian_per_hari' => 4,
            ]
        );

        KebutuhanPakan::updateOrCreate(
            ['fase_umur' => 'Grower'],
            [
                'umur_mulai_hari' => 11,
                'umur_selesai_hari' => 24,
                'gram_per_ekor_per_hari' => 70,
                'frekuensi_pemberian_per_hari' => 4,
            ]
        );

        KebutuhanPakan::updateOrCreate(
            ['fase_umur' => 'Finisher'],
            [
                'umur_mulai_hari' => 25,
                'umur_selesai_hari' => 35,
                'gram_per_ekor_per_hari' => 120,
                'frekuensi_pemberian_per_hari' => 4,
            ]
        );

        // 2. Seed Periode Pemeliharaan Aktif
        $periode = PeriodePemeliharaan::updateOrCreate(
            ['nama_periode' => 'Periode Broiler 1'],
            [
                'tanggal_mulai' => now()->subDays(14)->format('Y-m-d'),
                'tanggal_selesai' => null,
                'jumlah_ayam' => 100,
                'status' => 'aktif',
            ]
        );

        // 3. Seed 3 Jadwal Pakan Aktif
        $jadwal1 = JadwalPakan::updateOrCreate(
            ['periode_pemeliharaan_id' => $periode->id, 'waktu_pakan' => '07:00:00'],
            [
                'fase_umur' => 'Grower',
                'target_pakan_gram' => null,
                'durasi_motor_detik' => null,
                'target_otomatis' => true,
                'status_aktif' => true,
            ]
        );

        $jadwal2 = JadwalPakan::updateOrCreate(
            ['periode_pemeliharaan_id' => $periode->id, 'waktu_pakan' => '12:00:00'],
            [
                'fase_umur' => 'Grower',
                'target_pakan_gram' => null,
                'durasi_motor_detik' => null,
                'target_otomatis' => true,
                'status_aktif' => true,
            ]
        );

        $jadwal3 = JadwalPakan::updateOrCreate(
            ['periode_pemeliharaan_id' => $periode->id, 'waktu_pakan' => '16:00:00'],
            [
                'fase_umur' => 'Grower',
                'target_pakan_gram' => null,
                'durasi_motor_detik' => null,
                'target_otomatis' => true,
                'status_aktif' => true,
            ]
        );

        // 4. Seed 1 Data Stok Pakan Terbaru
        if (StokPakan::count() === 0) {
            StokPakan::create([
                'berat_pakan_gram' => 40000,
                'berat_gudang_gram' => 25000,
                'persentase_stok' => 80,
                'status_stok' => 'aman',
                'waktu_pembacaan' => now(),
            ]);
        }

        // 5. Seed 5 Log Pemberian Pakan Realistis
        if (LogPemberianPakan::count() === 0) {
            // Log 1: Kemarin Pagi (Otomatis)
            LogPemberianPakan::create([
                'jadwal_pakan_id' => $jadwal1->id,
                'sumber' => 'otomatis',
                'waktu_mulai' => now()->subDay()->setTime(7, 0, 0),
                'waktu_selesai' => now()->subDay()->setTime(7, 0, 30),
                'durasi_motor_detik' => 30,
                'jumlah_pakan_keluar_gram' => 1495,
                'status' => 'berhasil',
                'keterangan' => 'Pemberian pakan terjadwal pagi hari selesai.',
            ]);

            // Log 2: Kemarin Siang (Otomatis)
            LogPemberianPakan::create([
                'jadwal_pakan_id' => $jadwal2->id,
                'sumber' => 'otomatis',
                'waktu_mulai' => now()->subDay()->setTime(12, 0, 0),
                'waktu_selesai' => now()->subDay()->setTime(12, 0, 24),
                'durasi_motor_detik' => 24,
                'jumlah_pakan_keluar_gram' => 1205,
                'status' => 'berhasil',
                'keterangan' => 'Pemberian pakan terjadwal siang hari selesai.',
            ]);

            // Log 3: Kemarin Sore (Otomatis)
            LogPemberianPakan::create([
                'jadwal_pakan_id' => $jadwal3->id,
                'sumber' => 'otomatis',
                'waktu_mulai' => now()->subDay()->setTime(16, 0, 0),
                'waktu_selesai' => now()->subDay()->setTime(16, 0, 36),
                'durasi_motor_detik' => 36,
                'jumlah_pakan_keluar_gram' => 1798,
                'status' => 'berhasil',
                'keterangan' => 'Pemberian pakan terjadwal sore hari selesai.',
            ]);

            // Log 4: Hari Ini Pagi (Otomatis)
            LogPemberianPakan::create([
                'jadwal_pakan_id' => $jadwal1->id,
                'sumber' => 'otomatis',
                'waktu_mulai' => now()->setTime(7, 0, 0),
                'waktu_selesai' => now()->setTime(7, 0, 30),
                'durasi_motor_detik' => 30,
                'jumlah_pakan_keluar_gram' => 1510,
                'status' => 'berhasil',
                'keterangan' => 'Pemberian pakan terjadwal pagi hari selesai.',
            ]);

            // Log 5: Hari Ini Siang (Manual)
            LogPemberianPakan::create([
                'jadwal_pakan_id' => null,
                'sumber' => 'manual',
                'waktu_mulai' => now()->setTime(10, 30, 0),
                'waktu_selesai' => now()->setTime(10, 30, 15),
                'durasi_motor_detik' => 15,
                'jumlah_pakan_keluar_gram' => 750,
                'status' => 'berhasil',
                'keterangan' => 'Tambahan pakan manual oleh petugas.',
            ]);
        }

        // 6. Seed 1 Status Alat ESP32 Terbaru
        StatusAlat::updateOrCreate(
            ['nama_perangkat' => 'ESP32-FeederKandang'],
            [
                'status_koneksi' => 'online',
                'status_motor' => 'mati',
                'status_sensor' => 'normal',
                'mode_operasi' => 'otomatis',
                'terakhir_online' => now(),
            ]
        );
    }
}
