<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JadwalPakan;
use App\Models\PeriodePemeliharaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JadwalPakanController extends Controller
{
    public function index(): JsonResponse
    {
        $jadwal = JadwalPakan::with('periodePemeliharaan')
            ->orderBy('waktu_pakan', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil daftar jadwal pakan',
            'data' => $jadwal
        ]);
    }

    public function show($id): JsonResponse
    {
        $jadwal = JadwalPakan::with(['periodePemeliharaan', 'logPemberianPakan' => function ($query) {
            $query->orderBy('waktu_mulai', 'desc')->limit(20);
        }])->find($id);

        if (!$jadwal) {
            return response()->json([
                'status' => false,
                'message' => 'Jadwal pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil detail jadwal pakan',
            'data' => $jadwal
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'periode_pemeliharaan_id' => 'nullable|exists:periode_pemeliharaan,id',
            'waktu_pakan' => 'required',
            'fase_umur' => 'required|string|max:255',
            'status_aktif' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'periode_pemeliharaan_id',
            'waktu_pakan',
            'fase_umur',
            'status_aktif'
        ]);

        // Jika periode_pemeliharaan_id tidak dikirim, pasangkan ke periode aktif terbaru
        if (empty($data['periode_pemeliharaan_id'])) {
            $activePeriode = PeriodePemeliharaan::where('status', 'aktif')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$activePeriode) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal membuat jadwal: Tidak ada periode pemeliharaan aktif.',
                    'data' => null
                ], 400);
            }
            $data['periode_pemeliharaan_id'] = $activePeriode->id;
        }

        // Set status_aktif default to true
        if (!isset($data['status_aktif'])) {
            $data['status_aktif'] = true;
        }

        // Target porsi dan durasi motor pakan selalu otomatis
        $data['target_otomatis'] = true;
        $data['target_pakan_gram'] = null;
        $data['durasi_motor_detik'] = null;

        $jadwal = JadwalPakan::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Berhasil menambahkan jadwal pakan',
            'data' => $jadwal
        ], 210);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $jadwal = JadwalPakan::find($id);

        if (!$jadwal) {
            return response()->json([
                'status' => false,
                'message' => 'Jadwal pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'periode_pemeliharaan_id' => 'nullable|exists:periode_pemeliharaan,id',
            'waktu_pakan' => 'sometimes|required',
            'fase_umur' => 'sometimes|required|string|max:255',
            'status_aktif' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'periode_pemeliharaan_id',
            'waktu_pakan',
            'fase_umur',
            'status_aktif'
        ]);

        // Target porsi dan durasi motor pakan selalu otomatis
        $data['target_otomatis'] = true;
        $data['target_pakan_gram'] = null;
        $data['durasi_motor_detik'] = null;

        $jadwal->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Berhasil memperbarui jadwal pakan',
            'data' => $jadwal
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $jadwal = JadwalPakan::find($id);

        if (!$jadwal) {
            return response()->json([
                'status' => false,
                'message' => 'Jadwal pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $jadwal->delete();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil menghapus jadwal pakan',
            'data' => null
        ]);
    }
}
