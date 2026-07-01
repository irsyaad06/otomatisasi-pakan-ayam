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
        $statusAlat = StatusAlat::latest()->first();

        return response()->json([
            'status' => true,
            'motor' => $statusAlat ? ($statusAlat->status_motor ?? 'mati') : 'mati'
        ]);
    }
}
