<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StatusAlat;
use App\Models\StokPakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreStatusAlatRequest;

class StatusAlatController extends Controller
{
    public function index(): JsonResponse
    {
        StatusAlat::checkConnections();
        $status = StatusAlat::orderBy('updated_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data status alat',
            'data' => $status
        ]);
    }

    public function show($id): JsonResponse
    {
        StatusAlat::checkConnections();
        $status = StatusAlat::find($id);

        if (!$status) {
            return response()->json([
                'status' => false,
                'message' => 'Status alat tidak ditemukan',
                'data' => null
            ], 404);
        }

        // Ambil histori koneksi (seluruh rekaman status_alat)
        $historiKoneksi = StatusAlat::where('nama_perangkat', $status->nama_perangkat)
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        // Ambil histori sensor (seluruh pembacaan stok pakan terbaru)
        $historiSensor = StokPakan::orderBy('waktu_pembacaan', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil detail status alat',
            'data' => [
                'status_alat' => $status,
                'histori_koneksi' => $historiKoneksi,
                'histori_sensor' => $historiSensor
            ]
        ]);
    }

    public function store(StoreStatusAlatRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $status = StatusAlat::updateOrCreate(
            ['device_id' => $validated['device_id']],
            [
                'nama_perangkat' => $validated['nama_perangkat'],
                'berat_pakan' => $validated['berat_pakan'],
                'status_motor' => $validated['status_motor'],
                'status_sensor' => $validated['status_sensor'],
                'mode_operasi' => $validated['mode_operasi'],
                'status_koneksi' => 'online',
                'terakhir_online' => now(),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Berhasil memperbarui status alat',
            'data' => $status
        ], 200);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $status = StatusAlat::find($id);

        if (!$status) {
            return response()->json([
                'status' => false,
                'message' => 'Status alat tidak ditemukan',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_perangkat' => 'sometimes|required|string|max:255',
            'status_koneksi' => 'sometimes|required|in:online,offline',
            'status_motor' => 'sometimes|required|in:aktif,mati',
            'status_sensor' => 'sometimes|required|in:normal,rusak',
            'mode_operasi' => 'sometimes|required|in:otomatis,manual',
            'terakhir_online' => 'sometimes|required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $status->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Berhasil memperbarui status alat',
            'data' => $status
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $status = StatusAlat::find($id);

        if (!$status) {
            return response()->json([
                'status' => false,
                'message' => 'Status alat tidak ditemukan',
                'data' => null
            ], 404);
        }

        $status->delete();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil menghapus status alat',
            'data' => null
        ]);
    }

    public function getMotorStatus(): JsonResponse
    {
        // 1. Ambil data status_alat terbaru
        $statusAlat = StatusAlat::latest()->first();

        // 2. Cek apakah ada jadwal pakan yang harus aktif sekarang
        $activePeriode = \App\Models\PeriodePemeliharaan::where('status', 'aktif')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($activePeriode) {
            $now = \Carbon\Carbon::now('Asia/Jakarta');
            $currentTime = $now->toTimeString(); // "HH:MM:SS"

            // Ambil jadwal pakan aktif hari ini yang waktunya sudah terlewat
            $schedules = \App\Models\JadwalPakan::where('periode_pemeliharaan_id', $activePeriode->id)
                ->where('status_aktif', true)
                ->where('waktu_pakan', '<=', $currentTime)
                ->orderBy('waktu_pakan', 'desc')
                ->get();

            foreach ($schedules as $schedule) {
                // Cek apakah jadwal ini sudah dieksekusi hari ini
                $executed = \App\Models\LogPemberianPakan::where('jadwal_pakan_id', $schedule->id)
                    ->whereDate('waktu_mulai', \Carbon\Carbon::today('Asia/Jakarta'))
                    ->exists();

                if (!$executed) {
                    // Jika belum pernah dieksekusi hari ini, aktifkan motor!
                    if (!$statusAlat) {
                        $statusAlat = StatusAlat::create([
                            'device_id' => 'FEEDER-01',
                            'nama_perangkat' => 'ESP32-FeederKandang',
                            'status_koneksi' => 'online',
                            'status_motor' => 'aktif',
                            'status_sensor' => 'normal',
                            'mode_operasi' => 'otomatis',
                            'terakhir_online' => now(),
                        ]);
                    } else {
                        $statusAlat->update([
                            'status_motor' => 'aktif',
                            'terakhir_online' => now()
                        ]);
                    }

                    // Tentukan durasi motor aktif (default 10 detik)
                    $durasi = $schedule->durasi_motor_detik ?? 10;
                    $targetPakan = $schedule->target_pakan_gram ?? 500;

                    // Buat log pemberian pakan dengan status 'proses'
                    \App\Models\LogPemberianPakan::create([
                        'jadwal_pakan_id' => $schedule->id,
                        'sumber' => 'otomatis',
                        'waktu_mulai' => now(),
                        'waktu_selesai' => now()->addSeconds($durasi),
                        'durasi_motor_detik' => $durasi,
                        'jumlah_pakan_keluar_gram' => $targetPakan,
                        'status' => 'proses',
                        'keterangan' => 'Pemberian pakan otomatis berdasarkan jadwal sedang berlangsung.'
                    ]);

                    // Kurangi stok pakan sesuai target
                    $stok = \App\Models\StokPakan::orderBy('waktu_pembacaan', 'desc')->first();
                    if ($stok) {
                        $newBerat = max(0, $stok->berat_pakan_gram - $targetPakan);
                        $newPersentase = min(100, max(0, (int) round(($newBerat / 50000) * 100)));
                        $statusStok = 'aman';
                        if ($newPersentase < 20 && $newPersentase > 0) {
                            $statusStok = 'hampir_habis';
                        } elseif ($newPersentase <= 0) {
                            $statusStok = 'habis';
                        }

                        \App\Models\StokPakan::create([
                            'berat_pakan_gram' => $newBerat,
                            'persentase_stok' => $newPersentase,
                            'status_stok' => $statusStok,
                            'waktu_pembacaan' => now(),
                        ]);
                    }

                    return response()->json([
                        'status' => true,
                        'motor' => 'aktif'
                    ]);
                }
            }

            // Jika status motor saat ini sedang 'aktif', periksa apakah durasi pengumpanan otomatis sudah selesai
            if ($statusAlat && $statusAlat->status_motor === 'aktif') {
                $latestLog = \App\Models\LogPemberianPakan::where('sumber', 'otomatis')
                    ->where('status', 'proses')
                    ->orderBy('waktu_mulai', 'desc')
                    ->first();

                if ($latestLog) {
                    $waktuMulai = \Carbon\Carbon::parse($latestLog->waktu_mulai);
                    $durasi = $latestLog->durasi_motor_detik ?? 10;

                    if (abs(now()->diffInSeconds($waktuMulai)) >= $durasi) {
                        // Durasi sudah terpenuhi, matikan motor
                        $statusAlat->update([
                            'status_motor' => 'mati',
                            'terakhir_online' => now()
                        ]);

                        // Update status log menjadi 'berhasil'
                        $latestLog->update([
                            'status' => 'berhasil',
                            'waktu_selesai' => now(),
                            'keterangan' => 'Pemberian pakan otomatis selesai setelah durasi terpenuhi.'
                        ]);

                        return response()->json([
                            'status' => true,
                            'motor' => 'mati'
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'status' => true,
            'motor' => $statusAlat ? ($statusAlat->status_motor ?? 'mati') : 'mati'
        ]);
    }
}
