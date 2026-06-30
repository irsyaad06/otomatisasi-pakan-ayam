<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokPakan;
use App\Models\StatusAlat;
use App\Models\LogPemberianPakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class IoTController extends Controller
{
    public function kirimData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'berat_pakan_gram' => 'required|integer',
            'persentase_stok' => 'required|integer|min:0|max:100',
            'status_stok' => 'required|string',
            'status_koneksi' => 'required|string',
            'status_motor' => 'required|string',
            'status_sensor' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi payload gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // 1. Simpan data ke stok_pakan
        $stok = StokPakan::create([
            'berat_pakan_gram' => $request->berat_pakan_gram,
            'persentase_stok' => $request->persentase_stok,
            'status_stok' => $request->status_stok,
            'waktu_pembacaan' => now(),
        ]);

        // 2. Update status_alat (cari yang ada atau buat baru)
        $statusAlat = StatusAlat::updateOrCreate(
            ['nama_perangkat' => 'ESP32-FeederKandang'],
            [
                'status_koneksi' => $request->status_koneksi,
                'status_motor' => $request->status_motor,
                'status_sensor' => $request->status_sensor,
                'terakhir_online' => now(),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengirim data sensor dan status alat',
            'data' => [
                'stok' => $stok,
                'status_alat' => $statusAlat
            ]
        ]);
    }

    public function simulasiPakan(): JsonResponse
    {
        // 1. Dapatkan stok pakan terakhir
        $latestStok = StokPakan::orderBy('waktu_pembacaan', 'desc')->first();
        
        $pakanKeluar = 500; // 500 gram
        $newBerat = $latestStok ? max(0, $latestStok->berat_pakan_gram - $pakanKeluar) : 4000;
        // Hitung persentase (asumsi kapasitas silinder hopper adalah 50000 gram)
        $newPersentase = (int)round(($newBerat / 50000) * 100);
        $newPersentase = max(0, min(100, $newPersentase));
        
        $statusStok = 'aman';
        if ($newPersentase < 20 && $newPersentase > 0) {
            $statusStok = 'hampir_habis';
        } elseif ($newPersentase <= 0) {
            $statusStok = 'habis';
        }

        // 2. Simpan stok pakan baru
        $stokBaru = StokPakan::create([
            'berat_pakan_gram' => $newBerat,
            'persentase_stok' => $newPersentase,
            'status_stok' => $statusStok,
            'waktu_pembacaan' => now(),
        ]);

        // 3. Buat log pemberian pakan dummy (sumber: manual)
        $waktuMulai = now()->subSeconds(15);
        $log = LogPemberianPakan::create([
            'jadwal_pakan_id' => null,
            'sumber' => 'manual',
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => now(),
            'durasi_motor_detik' => 15,
            'jumlah_pakan_keluar_gram' => $pakanKeluar,
            'status' => 'berhasil',
            'keterangan' => 'Simulasi pemberian pakan manual lewat dashboard.',
        ]);

        // 4. Update status motor menjadi mati setelah simulasi
        $statusAlat = StatusAlat::updateOrCreate(
            ['nama_perangkat' => 'ESP32-FeederKandang'],
            [
                'status_koneksi' => 'online',
                'status_motor' => 'mati',
                'status_sensor' => 'normal',
                'terakhir_online' => now(),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Simulasi pemberian pakan berhasil dijalankan',
            'data' => [
                'stok_sebelumnya' => $latestStok ? $latestStok->berat_pakan_gram : null,
                'stok_sekarang' => $stokBaru->berat_pakan_gram,
                'log_pemberian' => $log,
                'status_alat' => $statusAlat
            ]
        ]);
    }

    public function simulasiStokMenipis(): JsonResponse
    {
        // Buat data stok_pakan baru dengan persentase 15% (hampir_habis)
        $stok = StokPakan::create([
            'berat_pakan_gram' => 7500, // 15% dari 50000g
            'persentase_stok' => 15,
            'status_stok' => 'hampir_habis',
            'waktu_pembacaan' => now(),
        ]);

        // Update status alat agar sinkron
        StatusAlat::updateOrCreate(
            ['nama_perangkat' => 'ESP32-FeederKandang'],
            [
                'status_koneksi' => 'online',
                'terakhir_online' => now(),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Simulasi stok pakan menipis berhasil dijalankan',
            'data' => $stok
        ]);
    }

    public function simulasiAutoCut(): JsonResponse
    {
        // 1. Buat log pemberian pakan dengan status "berhasil" dan keterangan khusus
        $waktuMulai = now()->subSeconds(20);
        $log = LogPemberianPakan::create([
            'jadwal_pakan_id' => null,
            'sumber' => 'otomatis',
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => now(),
            'durasi_motor_detik' => 20,
            'jumlah_pakan_keluar_gram' => 1000,
            'status' => 'berhasil',
            'keterangan' => 'Motor berhenti otomatis karena batas porsi tercapai',
        ]);

        // 2. Kurangi stok pakan
        $latestStok = StokPakan::orderBy('waktu_pembacaan', 'desc')->first();
        $newBerat = $latestStok ? max(0, $latestStok->berat_pakan_gram - 1000) : 3800;
        $newPersentase = (int)round(($newBerat / 50000) * 100);
        $newPersentase = max(0, min(100, $newPersentase));
        
        $statusStok = 'aman';
        if ($newPersentase < 20 && $newPersentase > 0) {
            $statusStok = 'hampir_habis';
        } elseif ($newPersentase <= 0) {
            $statusStok = 'habis';
        }

        StokPakan::create([
            'berat_pakan_gram' => $newBerat,
            'persentase_stok' => $newPersentase,
            'status_stok' => $statusStok,
            'waktu_pembacaan' => now(),
        ]);

        // 3. Update status alat
        $statusAlat = StatusAlat::updateOrCreate(
            ['nama_perangkat' => 'ESP32-FeederKandang'],
            [
                'status_koneksi' => 'online',
                'status_motor' => 'mati', // sudah auto-cut
                'status_sensor' => 'normal',
                'terakhir_online' => now(),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Simulasi Auto-Cut berhasil dijalankan',
            'data' => [
                'log' => $log,
                'status_alat' => $statusAlat
            ]
        ]);
    }
}
