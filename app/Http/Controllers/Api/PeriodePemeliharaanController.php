<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PeriodePemeliharaan;
use App\Models\StokPakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PeriodePemeliharaanController extends Controller
{
    /**
     * GET /api/periode-pemeliharaan
     * Ambil semua riwayat periode pemeliharaan.
     */
    public function index(): JsonResponse
    {
        $periode = PeriodePemeliharaan::orderByRaw("status = 'aktif' DESC")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil riwayat periode pemeliharaan',
            'data' => $periode
        ]);
    }

    /**
     * POST /api/periode-pemeliharaan
     * Mulai periode pemeliharaan baru (menyelesaikan yang aktif & buat stok awal).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_periode' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'jumlah_ayam' => 'required|integer|min:1',
            'stok_gudang_kg' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // 1. Selesaikan periode aktif sebelumnya jika ada
        PeriodePemeliharaan::where('status', 'aktif')->update([
            'status' => 'selesai',
            'tanggal_selesai' => now()->format('Y-m-d')
        ]);

        // 2. Buat periode pemeliharaan baru
        $periode = PeriodePemeliharaan::create([
            'nama_periode' => $request->input('nama_periode'),
            'tanggal_mulai' => $request->input('tanggal_mulai'),
            'tanggal_selesai' => $request->input('tanggal_selesai'),
            'jumlah_ayam' => $request->input('jumlah_ayam'),
            'status' => 'aktif'
        ]);

        // 3. Inisialisasi stok pakan baru di database (silo otomatis dari data sensor terakhir)
        $latestStok = StokPakan::orderBy('waktu_pembacaan', 'desc')->first();
        $siloGram = $latestStok ? $latestStok->berat_pakan_gram : 0;
        $gudangGram = (int) ($request->input('stok_gudang_kg') * 1000);
        $persentase = $latestStok ? $latestStok->persentase_stok : 0;
        $statusStok = $latestStok ? $latestStok->status_stok : 'habis';

        StokPakan::create([
            'berat_pakan_gram' => $siloGram,
            'berat_gudang_gram' => $gudangGram,
            'persentase_stok' => $persentase,
            'status_stok' => $statusStok,
            'waktu_pembacaan' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Periode pemeliharaan baru berhasil dimulai!',
            'data' => $periode
        ], 201);
    }

    /**
     * PUT /api/periode-pemeliharaan/{id}
     * Perbarui detail data periode pemeliharaan.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $periode = PeriodePemeliharaan::find($id);

        if (!$periode) {
            return response()->json([
                'status' => false,
                'message' => 'Periode pemeliharaan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_periode' => 'sometimes|required|string|max:255',
            'tanggal_mulai' => 'sometimes|required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'jumlah_ayam' => 'sometimes|required|integer|min:1',
            'status' => 'sometimes|required|string|in:aktif,selesai'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $periode->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Periode pemeliharaan berhasil diperbarui',
            'data' => $periode
        ]);
    }

    /**
     * POST /api/periode-pemeliharaan/{id}/selesai
     * Tandai periode pemeliharaan aktif sebagai selesai.
     */
    public function complete($id): JsonResponse
    {
        $periode = PeriodePemeliharaan::find($id);

        if (!$periode) {
            return response()->json([
                'status' => false,
                'message' => 'Periode pemeliharaan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $periode->update([
            'status' => 'selesai',
            'tanggal_selesai' => now()->format('Y-m-d')
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Periode pemeliharaan berhasil diselesaikan!',
            'data' => $periode
        ]);
    }

    /**
     * DELETE /api/periode-pemeliharaan/{id}
     * Hapus periode pemeliharaan.
     */
    public function destroy($id): JsonResponse
    {
        $periode = PeriodePemeliharaan::find($id);

        if (!$periode) {
            return response()->json([
                'status' => false,
                'message' => 'Periode pemeliharaan tidak ditemukan',
                'data' => null
            ], 404);
        }

        $periode->delete();

        return response()->json([
            'status' => true,
            'message' => 'Periode pemeliharaan berhasil dihapus',
            'data' => null
        ]);
    }
}
