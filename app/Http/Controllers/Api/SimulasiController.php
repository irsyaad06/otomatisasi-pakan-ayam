<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PeriodePemeliharaan;
use App\Models\JadwalPakan;
use App\Models\StokPakan;
use App\Models\StatusAlat;
use App\Models\LogPemberianPakan;
use App\Services\PerhitunganPakanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SimulasiController extends Controller
{
    protected $calcService;

    public function __construct(PerhitunganPakanService $calcService)
    {
        $this->calcService = $calcService;
    }

    /**
     * GET /api/simulasi/status
     */
    public function status(): JsonResponse
    {
        StatusAlat::checkConnections();

        // 1. Periode aktif
        $periode = PeriodePemeliharaan::where('status', 'aktif')
            ->orderBy('created_at', 'desc')
            ->first();

        $umurHari = 0;
        $faseUmur = 'Tidak ada';
        $kebutuhan = null;
        $targetPorsi = 0;

        if ($periode) {
            $umurHari = $this->calcService->hitungUmurAyam($periode->tanggal_mulai);
            $kebutuhan = $this->calcService->tentukanFase($umurHari);
            
            if ($kebutuhan) {
                $faseUmur = $kebutuhan->fase_umur;
                $kebutuhanHarian = $this->calcService->hitungKebutuhanHarian($periode->jumlah_ayam, $kebutuhan->gram_per_ekor_per_hari);
                $targetPorsi = $this->calcService->hitungPorsiPerJadwal($kebutuhanHarian, $kebutuhan->frekuensi_pemberian_per_hari);
            }
        }

        // 2. Stok pakan terbaru
        $stok = StokPakan::orderBy('waktu_pembacaan', 'desc')->first();

        // 3. Jadwal berikutnya
        $currentTime = Carbon::now()->toTimeString();
        $jadwalBerikutnya = null;
        if ($periode) {
            $jadwalBerikutnya = JadwalPakan::where('periode_pemeliharaan_id', $periode->id)
                ->where('status_aktif', true)
                ->where('waktu_pakan', '>', $currentTime)
                ->orderBy('waktu_pakan', 'asc')
                ->first();
                
            if (!$jadwalBerikutnya) {
                $jadwalBerikutnya = JadwalPakan::where('periode_pemeliharaan_id', $periode->id)
                    ->where('status_aktif', true)
                    ->orderBy('waktu_pakan', 'asc')
                    ->first();
            }
        }

        // 4. Status alat
        $statusAlat = StatusAlat::orderBy('updated_at', 'desc')->first();

        // 5. Log terbaru (5 terakhir)
        $logTerbaru = LogPemberianPakan::with('jadwalPakan')
            ->orderBy('waktu_mulai', 'desc')
            ->limit(5)
            ->get();

        $responseData = [
            'periode_aktif' => $periode,
            'umur_hari' => $umurHari,
            'fase_umur' => $faseUmur,
            'jumlah_ayam' => $periode ? $periode->jumlah_ayam : 0,
            'gram_per_ekor_per_hari' => $kebutuhan ? $kebutuhan->gram_per_ekor_per_hari : 0,
            'frekuensi_pemberian_per_hari' => $kebutuhan ? $kebutuhan->frekuensi_pemberian_per_hari : 0,
            'kebutuhan_pakan_harian_gram' => $kebutuhan ? ($periode->jumlah_ayam * $kebutuhan->gram_per_ekor_per_hari) : 0,
            'target_pakan_per_jadwal_gram' => $targetPorsi,
            'target_pakan_per_jadwal' => $targetPorsi, // for vue
            'stok_pakan' => $stok,
            'status_alat' => $statusAlat,
            'jadwal_berikutnya' => $jadwalBerikutnya,
            'log_terbaru' => $logTerbaru,
            'kebutuhan_pakan' => $kebutuhan, // for vue
        ];

        return response()->json(array_merge([
            'status' => true,
            'data' => $responseData
        ], $responseData));
    }

    /**
     * POST /api/simulasi/jalankan-pakan
     */
    public function jalankanPakan(Request $request): JsonResponse
    {
        // 1. Ambil periode aktif
        $periode = PeriodePemeliharaan::where('status', 'aktif')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$periode) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada periode pemeliharaan aktif.'
            ], 400);
        }

        // 2. Hitung target porsi pakan per jadwal
        $umurHari = $this->calcService->hitungUmurAyam($periode->tanggal_mulai);
        $kebutuhan = $this->calcService->tentukanFase($umurHari);
        
        $targetPorsi = 500; // Default fallback manual
        if ($kebutuhan) {
            $kebutuhanHarian = $this->calcService->hitungKebutuhanHarian($periode->jumlah_ayam, $kebutuhan->gram_per_ekor_per_hari);
            $targetPorsi = $this->calcService->hitungPorsiPerJadwal($kebutuhanHarian, $kebutuhan->frekuensi_pemberian_per_hari);
        }

        // 3. Ambil jadwal pakan jika di-request
        $jadwalId = $request->input('jadwal_pakan_id');
        $pakanKeluar = $targetPorsi;
        $sumber = 'manual';

        if ($jadwalId) {
            $schedule = JadwalPakan::find($jadwalId);
            if ($schedule) {
                $sumber = 'otomatis';
                if (!$schedule->target_otomatis && $schedule->target_pakan_gram) {
                    $pakanKeluar = $schedule->target_pakan_gram;
                }
            }
        }

        // 4. Validasi stok pakan mencukupi
        $stok = StokPakan::orderBy('waktu_pembacaan', 'desc')->first();
        $currentBerat = $stok ? $stok->berat_pakan_gram : 0;

        if ($currentBerat < $pakanKeluar) {
            return response()->json([
                'status' => false,
                'message' => 'Stok pakan tidak mencukupi'
            ], 400);
        }

        // 5. Kurangi stok pakan di database
        $newBerat = max(0, $currentBerat - $pakanKeluar);
        $newPersentase = (int) round(($newBerat / 50000) * 100);
        $statusStok = 'aman';
        if ($newPersentase < 20 && $newPersentase > 0) {
            $statusStok = 'hampir_habis';
        } elseif ($newPersentase <= 0) {
            $statusStok = 'habis';
        }

        $newStok = StokPakan::create([
            'berat_pakan_gram' => $newBerat,
            'persentase_stok' => $newPersentase,
            'status_stok' => $statusStok,
            'waktu_pembacaan' => now(),
        ]);

        // 6. Buat log pemberian pakan
        $durasi = (int) max(10, round($pakanKeluar / 50)); // Asumsi motor mengalirkan 50g per detik
        $log = LogPemberianPakan::create([
            'jadwal_pakan_id' => $jadwalId,
            'sumber' => $sumber,
            'waktu_mulai' => now()->subSeconds($durasi),
            'waktu_selesai' => now(),
            'durasi_motor_detik' => $durasi,
            'jumlah_pakan_keluar_gram' => $pakanKeluar,
            'status' => 'berhasil',
            'keterangan' => $jadwalId 
                ? 'Pemberian pakan otomatis terjadwal selesai.' 
                : 'Simulasi pemberian pakan manual selesai.',
        ]);

        // 7. Update status motor menjadi mati
        $firstDevice = StatusAlat::first();
        $deviceId = $firstDevice ? $firstDevice->device_id : 'FEEDER-01';
        $namaPerangkat = $firstDevice ? $firstDevice->nama_perangkat : 'ESP32-FeederKandang';

        StatusAlat::updateOrCreate(
            ['device_id' => $deviceId],
            [
                'nama_perangkat' => $namaPerangkat,
                'status_koneksi' => 'online',
                'status_motor' => 'mati',
                'status_sensor' => 'normal',
                'terakhir_online' => now(),
            ]
        );

        return $this->status();
    }

    /**
     * POST /api/simulasi/auto-cut
     */
    public function autoCut(Request $request): JsonResponse
    {
        // 1. Ambil periode aktif dan hitung target porsi
        $periode = PeriodePemeliharaan::where('status', 'aktif')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$periode) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada periode pemeliharaan aktif.'
            ], 400);
        }

        $umurHari = $this->calcService->hitungUmurAyam($periode->tanggal_mulai);
        $kebutuhan = $this->calcService->tentukanFase($umurHari);
        
        $targetPorsi = 500;
        if ($kebutuhan) {
            $kebutuhanHarian = $this->calcService->hitungKebutuhanHarian($periode->jumlah_ayam, $kebutuhan->gram_per_ekor_per_hari);
            $targetPorsi = $this->calcService->hitungPorsiPerJadwal($kebutuhanHarian, $kebutuhan->frekuensi_pemberian_per_hari);
        }

        $jadwalId = $request->input('jadwal_pakan_id');
        $pakanKeluar = $targetPorsi;
        $sumber = 'manual';

        if ($jadwalId) {
            $schedule = JadwalPakan::find($jadwalId);
            if ($schedule) {
                $sumber = 'otomatis';
                if (!$schedule->target_otomatis && $schedule->target_pakan_gram) {
                    $pakanKeluar = $schedule->target_pakan_gram;
                }
            }
        }

        // 2. Kurangi stok pakan
        $stok = StokPakan::orderBy('waktu_pembacaan', 'desc')->first();
        $currentBerat = $stok ? $stok->berat_pakan_gram : 0;

        if ($currentBerat < $pakanKeluar) {
            return response()->json([
                'status' => false,
                'message' => 'Stok pakan tidak mencukupi'
            ], 400);
        }

        $newBerat = max(0, $currentBerat - $pakanKeluar);
        $newPersentase = (int) round(($newBerat / 50000) * 100);
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

        // 3. Buat log pemberian pakan dengan status auto-cut
        $durasi = (int) max(10, round($pakanKeluar / 50));
        LogPemberianPakan::create([
            'jadwal_pakan_id' => $jadwalId,
            'sumber' => $sumber,
            'waktu_mulai' => now()->subSeconds($durasi),
            'waktu_selesai' => now(),
            'durasi_motor_detik' => $durasi,
            'jumlah_pakan_keluar_gram' => $pakanKeluar,
            'status' => 'berhasil',
            'keterangan' => 'Motor berhenti otomatis karena target porsi pakan tercapai',
        ]);

        // 4. Update status motor menjadi mati (auto-cut aktif)
        $firstDevice = StatusAlat::first();
        $deviceId = $firstDevice ? $firstDevice->device_id : 'FEEDER-01';
        $namaPerangkat = $firstDevice ? $firstDevice->nama_perangkat : 'ESP32-FeederKandang';

        StatusAlat::updateOrCreate(
            ['device_id' => $deviceId],
            [
                'nama_perangkat' => $namaPerangkat,
                'status_koneksi' => 'online',
                'status_motor' => 'mati',
                'status_sensor' => 'normal',
                'terakhir_online' => now(),
            ]
        );

        return $this->status();
    }

    /**
     * GET /api/simulasi/cek-jadwal
     */
    public function cekJadwal(): JsonResponse
    {
        $activePeriode = PeriodePemeliharaan::where('status', 'aktif')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$activePeriode) {
            return response()->json([
                'status' => true,
                'data' => null
            ]);
        }

        $currentTime = Carbon::now('Asia/Jakarta')->toTimeString();

        // Ambil seluruh jadwal aktif pada periode ini yang waktu pakannya sudah terlewat
        $schedules = JadwalPakan::where('periode_pemeliharaan_id', $activePeriode->id)
            ->where('status_aktif', true)
            ->where('waktu_pakan', '<=', $currentTime)
            ->orderBy('waktu_pakan', 'asc')
            ->get();

        foreach ($schedules as $schedule) {
            // Cek apakah jadwal ini sudah dieksekusi hari ini
            $executed = LogPemberianPakan::where('jadwal_pakan_id', $schedule->id)
                ->whereDate('waktu_mulai', Carbon::today('Asia/Jakarta'))
                ->exists();

            if (!$executed) {
                // Return jadwal pertama yang harus dieksekusi hari ini
                return response()->json([
                    'status' => true,
                    'data' => $schedule
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'data' => null
        ]);
    }

    /**
     * POST /api/periode-aktif/update-ayam
     */
    public function updateJumlahAyam(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'jumlah_ayam' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $periode = PeriodePemeliharaan::where('status', 'aktif')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$periode) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada periode pemeliharaan aktif.'
            ], 400);
        }

        $periode->update([
            'jumlah_ayam' => $request->input('jumlah_ayam')
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Jumlah ayam berhasil diperbarui',
            'data' => $periode
        ]);
    }

    /**
     * POST /api/periode-aktif/start
     */
    public function startPeriode(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nama_periode' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'jumlah_ayam' => 'required|integer|min:1',
            'stok_pakan_kg' => 'required|numeric|min:0|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // 1. Finish existing active periods
        PeriodePemeliharaan::where('status', 'aktif')->update([
            'status' => 'selesai',
            'tanggal_selesai' => now()->format('Y-m-d')
        ]);

        // 2. Start a new one
        $newPeriode = PeriodePemeliharaan::create([
            'nama_periode' => $request->input('nama_periode'),
            'tanggal_mulai' => $request->input('tanggal_mulai'),
            'tanggal_selesai' => $request->input('tanggal_selesai'),
            'jumlah_ayam' => $request->input('jumlah_ayam'),
            'status' => 'aktif'
        ]);

        // 3. Create initial stock record
        $beratGram = (int) ($request->input('stok_pakan_kg') * 1000);
        $persentase = min(100, (int) round(($beratGram / 50000) * 100));
        
        $statusStok = 'aman';
        if ($persentase < 20 && $persentase > 0) {
            $statusStok = 'hampir_habis';
        } elseif ($persentase <= 0) {
            $statusStok = 'habis';
        }

        StokPakan::create([
            'berat_pakan_gram' => $beratGram,
            'persentase_stok' => $persentase,
            'status_stok' => $statusStok,
            'waktu_pembacaan' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Periode pemeliharaan baru berhasil dimulai!',
            'data' => $newPeriode
        ]);
    }
}
