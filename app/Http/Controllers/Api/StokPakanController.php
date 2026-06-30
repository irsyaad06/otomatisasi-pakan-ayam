<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokPakan;
use App\Models\PeriodePemeliharaan;
use App\Services\PerhitunganPakanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StokPakanController extends Controller
{
    public function peramalan(PerhitunganPakanService $calcService): JsonResponse
    {
        $stokPakanTerbaru = StokPakan::orderBy('waktu_pembacaan', 'desc')->first();
        $periode = PeriodePemeliharaan::where('status', 'aktif')
            ->orderBy('created_at', 'desc')
            ->first();

        $siloGram = $stokPakanTerbaru ? $stokPakanTerbaru->berat_pakan_gram : 0;
        $gudangGram = $stokPakanTerbaru ? $stokPakanTerbaru->berat_gudang_gram : 0;
        $ramalan = $calcService->ramalkanSisaHari($siloGram, $gudangGram, $periode);

        return response()->json([
            'status' => true,
            'message' => 'Berhasil memproyeksikan sisa stok pakan',
            'data' => $ramalan
        ]);
    }

    public function index(): JsonResponse
    {
        $stok = StokPakan::orderBy('waktu_pembacaan', 'desc')->limit(50)->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data stok pakan',
            'data' => $stok
        ]);
    }

    public function show($id): JsonResponse
    {
        $stok = StokPakan::find($id);

        if (!$stok) {
            return response()->json([
                'status' => false,
                'message' => 'Data stok pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil detail stok pakan',
            'data' => $stok
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'berat_pakan_gram' => 'required|integer|min:0',
            'persentase_stok' => 'required|integer|min:0|max:100',
            'status_stok' => 'required|string|max:255',
            'waktu_pembacaan' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        if (empty($data['waktu_pembacaan'])) {
            $data['waktu_pembacaan'] = now();
        }

        $stok = StokPakan::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Berhasil menambahkan data stok pakan',
            'data' => $stok
        ], 210);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $stok = StokPakan::find($id);

        if (!$stok) {
            return response()->json([
                'status' => false,
                'message' => 'Data stok pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'berat_pakan_gram' => 'sometimes|required|integer|min:0',
            'persentase_stok' => 'sometimes|required|integer|min:0|max:100',
            'status_stok' => 'sometimes|required|string|max:255',
            'waktu_pembacaan' => 'sometimes|required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $stok->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Berhasil memperbarui data stok pakan',
            'data' => $stok
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $stok = StokPakan::find($id);

        if (!$stok) {
            return response()->json([
                'status' => false,
                'message' => 'Data stok pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $stok->delete();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil menghapus data stok pakan',
            'data' => null
        ]);
    }
}
