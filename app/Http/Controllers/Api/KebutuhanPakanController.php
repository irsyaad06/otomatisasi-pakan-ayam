<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KebutuhanPakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KebutuhanPakanController extends Controller
{
    public function index(): JsonResponse
    {
        $kebutuhan = KebutuhanPakan::orderBy('umur_mulai_hari', 'asc')->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data kebutuhan pakan',
            'data' => $kebutuhan
        ]);
    }

    public function show($id): JsonResponse
    {
        $kebutuhan = KebutuhanPakan::find($id);

        if (!$kebutuhan) {
            return response()->json([
                'status' => false,
                'message' => 'Data kebutuhan pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil detail kebutuhan pakan',
            'data' => $kebutuhan
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fase_umur' => 'required|string|max:255',
            'umur_mulai_hari' => 'required|integer|min:0',
            'umur_selesai_hari' => 'required|integer|gte:umur_mulai_hari',
            'gram_per_ekor_per_hari' => 'required|integer|min:1',
            'frekuensi_pemberian_per_hari' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $kebutuhan = KebutuhanPakan::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Berhasil menambahkan data kebutuhan pakan',
            'data' => $kebutuhan
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $kebutuhan = KebutuhanPakan::find($id);

        if (!$kebutuhan) {
            return response()->json([
                'status' => false,
                'message' => 'Data kebutuhan pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'fase_umur' => 'sometimes|required|string|max:255',
            'umur_mulai_hari' => 'sometimes|required|integer|min:0',
            'umur_selesai_hari' => 'sometimes|required|integer|gte:umur_mulai_hari',
            'gram_per_ekor_per_hari' => 'sometimes|required|integer|min:1',
            'frekuensi_pemberian_per_hari' => 'sometimes|required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $kebutuhan->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Berhasil memperbarui data kebutuhan pakan',
            'data' => $kebutuhan
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $kebutuhan = KebutuhanPakan::find($id);

        if (!$kebutuhan) {
            return response()->json([
                'status' => false,
                'message' => 'Data kebutuhan pakan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $kebutuhan->delete();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil menghapus data kebutuhan pakan',
            'data' => null
        ]);
    }
}
