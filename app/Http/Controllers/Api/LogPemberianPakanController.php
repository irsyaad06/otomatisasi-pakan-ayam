<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogPemberianPakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogPemberianPakanController extends Controller
{
    public function index(): JsonResponse
    {
        $logs = LogPemberianPakan::with('jadwalPakan')
            ->orderBy('waktu_mulai', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil log pemberian pakan',
            'data' => $logs
        ]);
    }

    public function show($id): JsonResponse
    {
        $log = LogPemberianPakan::with('jadwalPakan')->find($id);

        if (!$log) {
            return response()->json([
                'status' => false,
                'message' => 'Log pemberian pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil detail log pemberian pakan',
            'data' => $log
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'jadwal_pakan_id' => 'nullable|exists:jadwal_pakan,id',
            'sumber' => 'required|in:otomatis,manual',
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'required|date|after_or_equal:waktu_mulai',
            'durasi_motor_detik' => 'required|integer|min:0',
            'jumlah_pakan_keluar_gram' => 'required|integer|min:0',
            'status' => 'required|in:berhasil,gagal',
            'keterangan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $log = LogPemberianPakan::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Berhasil menambahkan log pemberian pakan',
            'data' => $log
        ], 210);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $log = LogPemberianPakan::find($id);

        if (!$log) {
            return response()->json([
                'status' => false,
                'message' => 'Log pemberian pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'jadwal_pakan_id' => 'nullable|exists:jadwal_pakan,id',
            'sumber' => 'sometimes|required|in:otomatis,manual',
            'waktu_mulai' => 'sometimes|required|date',
            'waktu_selesai' => 'sometimes|required|date|after_or_equal:waktu_mulai',
            'durasi_motor_detik' => 'sometimes|required|integer|min:0',
            'jumlah_pakan_keluar_gram' => 'sometimes|required|integer|min:0',
            'status' => 'sometimes|required|in:berhasil,gagal',
            'keterangan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $log->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Berhasil memperbarui log pemberian pakan',
            'data' => $log
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $log = LogPemberianPakan::find($id);

        if (!$log) {
            return response()->json([
                'status' => false,
                'message' => 'Log pemberian pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $log->delete();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil menghapus log pemberian pakan',
            'data' => null
        ]);
    }
}
