<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JadwalPakanController;
use App\Http\Controllers\Api\StokPakanController;
use App\Http\Controllers\Api\LogPemberianPakanController;
use App\Http\Controllers\Api\StatusAlatController;
use App\Http\Controllers\Api\IoTController;
use App\Http\Controllers\Api\SimulasiController;
use App\Http\Controllers\Api\KebutuhanPakanController;
use App\Http\Controllers\Api\PeriodePemeliharaanController;

// 1. Dashboard, Ringkasan, & Grafik
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/ringkasan', [DashboardController::class, 'ringkasan']);
Route::get('/grafik/stok-pakan', [DashboardController::class, 'stokPakanChart']);
Route::get('/grafik/log-pakan', [DashboardController::class, 'logPakanChart']);

// 2. Jadwal Pakan CRUD (Resourceful)
Route::get('/jadwal-pakan', [JadwalPakanController::class, 'index']);
Route::post('/jadwal-pakan', [JadwalPakanController::class, 'store']);
Route::get('/jadwal-pakan/{id}', [JadwalPakanController::class, 'show']);
Route::put('/jadwal-pakan/{id}', [JadwalPakanController::class, 'update']);
Route::delete('/jadwal-pakan/{id}', [JadwalPakanController::class, 'destroy']);

// 3. Stok Pakan CRUD (Resourceful)
Route::get('/stok-pakan', [StokPakanController::class, 'index']);
Route::post('/stok-pakan', [StokPakanController::class, 'store']);
Route::get('/stok-pakan/peramalan', [StokPakanController::class, 'peramalan']);
Route::get('/stok-pakan/{id}', [StokPakanController::class, 'show']);
Route::put('/stok-pakan/{id}', [StokPakanController::class, 'update']);
Route::delete('/stok-pakan/{id}', [StokPakanController::class, 'destroy']);

// 4. Log Pemberian Pakan CRUD (Resourceful)
Route::get('/log-pemberian-pakan', [LogPemberianPakanController::class, 'index']);
Route::post('/log-pemberian-pakan', [LogPemberianPakanController::class, 'store']);
Route::get('/log-pemberian-pakan/{id}', [LogPemberianPakanController::class, 'show']);
Route::put('/log-pemberian-pakan/{id}', [LogPemberianPakanController::class, 'update']);
Route::delete('/log-pemberian-pakan/{id}', [LogPemberianPakanController::class, 'destroy']);

// 5. Status Alat CRUD (Resourceful)
Route::get('/status-alat', [StatusAlatController::class, 'index']);
Route::get('/status-alat/motor', [StatusAlatController::class, 'getMotorStatus']);
Route::post('/status-alat', [StatusAlatController::class, 'store']);
Route::get('/status-alat/{id}', [StatusAlatController::class, 'show']);
Route::put('/status-alat/{id}', [StatusAlatController::class, 'update']);
Route::delete('/status-alat/{id}', [StatusAlatController::class, 'destroy']);

// 6. IoT Telemetry & Simulator Triggers
Route::post('/iot/kirim-data', [IoTController::class, 'kirimData']);
Route::post('/iot/simulasi-pakan', [IoTController::class, 'simulasiPakan']);
Route::post('/iot/simulasi-stok-menipis', [IoTController::class, 'simulasiStokMenipis']);
Route::post('/iot/simulasi-auto-cut', [IoTController::class, 'simulasiAutoCut']);

// 7. Realistic Simulator Endpoints
Route::get('/simulasi/status', [SimulasiController::class, 'status']);
Route::post('/simulasi/jalankan-pakan', [SimulasiController::class, 'jalankanPakan']);
Route::post('/simulasi/auto-cut', [SimulasiController::class, 'autoCut']);
Route::get('/simulasi/cek-jadwal', [SimulasiController::class, 'cekJadwal']);

// 8. Parameter Pakan (Kebutuhan Pakan) CRUD
Route::get('/kebutuhan-pakan', [KebutuhanPakanController::class, 'index']);
Route::post('/kebutuhan-pakan', [KebutuhanPakanController::class, 'store']);
Route::get('/kebutuhan-pakan/{id}', [KebutuhanPakanController::class, 'show']);
Route::put('/kebutuhan-pakan/{id}', [KebutuhanPakanController::class, 'update']);
Route::delete('/kebutuhan-pakan/{id}', [KebutuhanPakanController::class, 'destroy']);
Route::post('/periode-aktif/update-ayam', [SimulasiController::class, 'updateJumlahAyam']);
Route::post('/periode-aktif/start', [SimulasiController::class, 'startPeriode']);

// 9. Periode Pemeliharaan CRUD
Route::get('/periode-pemeliharaan', [PeriodePemeliharaanController::class, 'index']);
Route::post('/periode-pemeliharaan', [PeriodePemeliharaanController::class, 'store']);
Route::put('/periode-pemeliharaan/{id}', [PeriodePemeliharaanController::class, 'update']);
Route::delete('/periode-pemeliharaan/{id}', [PeriodePemeliharaanController::class, 'destroy']);
Route::post('/periode-pemeliharaan/{id}/selesai', [PeriodePemeliharaanController::class, 'complete']);

