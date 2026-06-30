<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokPakan;
use App\Models\StatusAlat;
use App\Models\JadwalPakan;
use App\Models\LogPemberianPakan;
use App\Models\PeriodePemeliharaan;
use App\Services\PerhitunganPakanService;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $calcService;

    public function __construct(PerhitunganPakanService $calcService)
    {
        $this->calcService = $calcService;
    }

    public function index(): JsonResponse
    {
        // 1. Stok pakan terbaru
        $stokPakanTerbaru = StokPakan::orderBy('waktu_pembacaan', 'desc')->first();

        // 2. Status alat terbaru
        $statusAlat = StatusAlat::orderBy('updated_at', 'desc')->first();

        // 3. Jadwal pakan berikutnya
        $currentTime = Carbon::now()->toTimeString();
        $jadwalBerikutnya = JadwalPakan::where('status_aktif', true)
            ->where('waktu_pakan', '>', $currentTime)
            ->orderBy('waktu_pakan', 'asc')
            ->first();

        // Jika tidak ada jadwal yang tersisa hari ini, ambil jadwal aktif pertama besok
        if (!$jadwalBerikutnya) {
            $jadwalBerikutnya = JadwalPakan::where('status_aktif', true)
                ->orderBy('waktu_pakan', 'asc')
                ->first();
        }

        // 4. Total pakan hari ini (dalam gram)
        $totalPakanHariIni = LogPemberianPakan::whereDate('waktu_mulai', Carbon::today())
            ->where('status', 'berhasil')
            ->sum('jumlah_pakan_keluar_gram');

        // 5. Log terbaru (5 terakhir)
        $logTerbaru = LogPemberianPakan::with('jadwalPakan')
            ->orderBy('waktu_mulai', 'desc')
            ->limit(5)
            ->get();

        // 4.5. Periode aktif & info umur/fase
        $periode = PeriodePemeliharaan::where('status', 'aktif')
            ->orderBy('created_at', 'desc')
            ->first();

        $umurHari = 0;
        $faseUmur = 'Tidak ada';
        if ($periode) {
            $umurHari = $this->calcService->hitungUmurAyam($periode->tanggal_mulai);
            $kebutuhan = $this->calcService->tentukanFase($umurHari);
            if ($kebutuhan) {
                $faseUmur = $kebutuhan->fase_umur;
            }
        }

        $data = [
            'stok_pakan_terbaru' => $stokPakanTerbaru,
            'status_alat' => $statusAlat,
            'jadwal_pakan_berikutnya' => $jadwalBerikutnya,
            'total_pakan_hari_ini' => (int)$totalPakanHariIni,
            'log_terbaru' => $logTerbaru,
            'periode_aktif' => $periode,
            'umur_hari' => $umurHari,
            'fase_umur' => $faseUmur,
            'ramalan_stok' => $this->calcService->ramalkanSisaHari(
                $stokPakanTerbaru ? $stokPakanTerbaru->berat_pakan_gram : 0,
                $stokPakanTerbaru ? $stokPakanTerbaru->berat_gudang_gram : 0,
                $periode
            ),
            'kebutuhan_pakan_master' => \App\Models\KebutuhanPakan::orderBy('umur_mulai_hari', 'asc')->get()
        ];

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data dashboard',
            'data' => $data
        ]);
    }

    public function ringkasan(): JsonResponse
    {
        $stokPakanTerbaru = StokPakan::orderBy('waktu_pembacaan', 'desc')->first();
        $statusAlat = StatusAlat::orderBy('updated_at', 'desc')->first();

        $currentTime = Carbon::now()->toTimeString();
        $jadwalBerikutnya = JadwalPakan::where('status_aktif', true)
            ->where('waktu_pakan', '>', $currentTime)
            ->orderBy('waktu_pakan', 'asc')
            ->first() ?: JadwalPakan::where('status_aktif', true)->orderBy('waktu_pakan', 'asc')->first();

        $totalPakanHariIni = LogPemberianPakan::whereDate('waktu_mulai', Carbon::today())
            ->where('status', 'berhasil')
            ->sum('jumlah_pakan_keluar_gram');

        $data = [
            'stok_pakan_terbaru' => $stokPakanTerbaru,
            'status_alat' => $statusAlat,
            'jadwal_pakan_berikutnya' => $jadwalBerikutnya,
            'total_pakan_hari_ini' => (int)$totalPakanHariIni,
        ];

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil ringkasan data',
            'data' => $data
        ]);
    }

    public function stokPakanChart(): JsonResponse
    {
        // Ambil 15 pembacaan stok pakan terakhir untuk grafik
        $readings = StokPakan::orderBy('waktu_pembacaan', 'desc')
            ->limit(15)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data grafik stok pakan',
            'data' => $readings
        ]);
    }

    public function logPakanChart(): JsonResponse
    {
        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $days->push(Carbon::today()->subDays($i)->format('Y-m-d'));
        }

        $chartData = $days->map(function ($date) {
            $total = LogPemberianPakan::whereDate('waktu_mulai', $date)
                ->where('status', 'berhasil')
                ->sum('jumlah_pakan_keluar_gram');

            // Set hari name
            $dayName = Carbon::parse($date)->locale('id')->isoFormat('dddd');

            return [
                'tanggal' => $date,
                'hari' => $dayName,
                'total_gram' => (int)$total
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data grafik log pakan',
            'data' => $chartData
        ]);
    }
}
