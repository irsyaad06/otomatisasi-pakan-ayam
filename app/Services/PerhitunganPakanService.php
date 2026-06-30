<?php

namespace App\Services;

use App\Models\PeriodePemeliharaan;
use App\Models\KebutuhanPakan;
use Carbon\Carbon;

class PerhitunganPakanService
{
    /**
     * Hitung umur ayam dalam hari berdasarkan selisih tanggal_mulai dan hari ini.
     *
     * @param string|Carbon $tanggalMulai
     * @return int
     */
    public function hitungUmurAyam($tanggalMulai): int
    {
        $startDate = Carbon::parse($tanggalMulai)->startOfDay();
        $today = Carbon::today();
        
        return (int) max(0, $startDate->diffInDays($today));
    }

    /**
     * Tentukan fase kebutuhan pakan berdasarkan umur ayam.
     *
     * @param int $umurHari
     * @return KebutuhanPakan|null
     */
    public function tentukanFase(int $umurHari): ?KebutuhanPakan
    {
        return KebutuhanPakan::where('umur_mulai_hari', '<=', $umurHari)
            ->where('umur_selesai_hari', '>=', $umurHari)
            ->first();
    }

    /**
     * Hitung kebutuhan pakan harian seluruh ayam dalam gram.
     *
     * @param int $jumlahAyam
     * @param int $gramPerEkorPerHari
     * @return int
     */
    public function hitungKebutuhanHarian(int $jumlahAyam, int $gramPerEkorPerHari): int
    {
        return $jumlahAyam * $gramPerEkorPerHari;
    }

    /**
     * Hitung porsi pakan per jadwal pemberian dalam gram.
     *
     * @param int $kebutuhanHarian
     * @param int $frekuensiPemberianPerHari
     * @return int
     */
    public function hitungPorsiPerJadwal(int $kebutuhanHarian, int $frekuensiPemberianPerHari): int
    {
        if ($frekuensiPemberianPerHari <= 0) {
            return 0;
        }
        return (int) round($kebutuhanHarian / $frekuensiPemberianPerHari);
    }

    /**
     * Ramalkan sisa hari kecukupan stok pakan secara dinamis berdasarkan pertumbuhan ayam.
     *
     * @param int $siloGram
     * @param int $gudangGram
     * @param PeriodePemeliharaan|null $periode
     * @return array
     */
    public function ramalkanSisaHari(int $siloGram, int $gudangGram, ?PeriodePemeliharaan $periode): array
    {
        if (!$periode) {
            return [
                'silo' => [
                    'sisa_hari' => 0,
                    'tanggal_habis' => null,
                    'tanggal_habis_formatted' => 'Tidak ada periode pemeliharaan yang aktif',
                    'detail_harian' => []
                ],
                'gudang' => [
                    'sisa_hari' => 0,
                    'tanggal_habis' => null,
                    'tanggal_habis_formatted' => 'Tidak ada periode pemeliharaan yang aktif',
                    'detail_harian' => []
                ],
                'jumlah_ayam' => 0,
                'umur_sekarang' => 0,
                'fase_sekarang' => 'Tidak ada',
                'sisa_hari_silo' => 0,
                'sisa_hari_gudang' => 0,
                'keterangan' => 'Tidak ada periode pemeliharaan yang aktif.'
            ];
        }

        $siloForecast = $this->proyeksikanPakan($siloGram, $periode);
        $gudangForecast = $this->proyeksikanPakan($gudangGram, $periode);

        return [
            'silo' => $siloForecast,
            'gudang' => $gudangForecast,
            'jumlah_ayam' => $periode->jumlah_ayam,
            'umur_sekarang' => $this->hitungUmurAyam($periode->tanggal_mulai),
            'fase_sekarang' => $this->tentukanFase($this->hitungUmurAyam($periode->tanggal_mulai))?->fase_umur ?? 'Tidak diketahui',
            'sisa_hari_silo' => $siloForecast['sisa_hari'],
            'sisa_hari_gudang' => $gudangForecast['sisa_hari'],
            'keterangan' => "Silo cukup untuk {$siloForecast['sisa_hari']} hari, gudang cukup untuk {$gudangForecast['sisa_hari']} hari."
        ];
    }

    /**
     * Helper untuk memproyeksikan kecukupan pakan berdasarkan berat tertentu.
     */
    private function proyeksikanPakan(int $beratPakanGram, PeriodePemeliharaan $periode): array
    {
        $jumlahAyam = $periode->jumlah_ayam;
        $umurMulai = $this->hitungUmurAyam($periode->tanggal_mulai);
        
        $kebutuhanMaster = KebutuhanPakan::orderBy('umur_mulai_hari', 'asc')->get();

        if ($kebutuhanMaster->isEmpty()) {
            return [
                'sisa_hari' => 0,
                'tanggal_habis' => null,
                'tanggal_habis_formatted' => 'Master parameter kebutuhan pakan kosong',
                'detail_harian' => []
            ];
        }

        $lastKebutuhan = $kebutuhanMaster->last();
        $sisaStok = $beratPakanGram;
        $detailHarian = [];
        $t = 0;
        $maxIterations = 365;
        
        while ($sisaStok > 0 && $t < $maxIterations) {
            $umurHari = $umurMulai + (int)floor($t);
            $tanggal = Carbon::today()->addDays((int)floor($t));

            $kebutuhan = $kebutuhanMaster->first(function ($item) use ($umurHari) {
                return $umurHari >= $item->umur_mulai_hari && $umurHari <= $item->umur_selesai_hari;
            });

            if (!$kebutuhan) {
                $kebutuhan = $lastKebutuhan;
            }

            $gramPerEkor = $kebutuhan->gram_per_ekor_per_hari;
            $kebutuhanHarianFlock = $jumlahAyam * $gramPerEkor;

            if ($kebutuhanHarianFlock <= 0) {
                break;
            }

            if ($sisaStok >= $kebutuhanHarianFlock) {
                $sisaStok -= $kebutuhanHarianFlock;
                $detailHarian[] = [
                    'hari_ke' => (int)floor($t),
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'hari_formatted' => $tanggal->locale('id')->isoFormat('dddd, D MMMM Y'),
                    'umur_ayam' => $umurHari,
                    'fase_umur' => $kebutuhan->fase_umur,
                    'gram_per_ekor' => $gramPerEkor,
                    'kebutuhan_harian_flock_gram' => $kebutuhanHarianFlock,
                    'stok_tersisa_akhir_hari_gram' => $sisaStok,
                    'persentase_terpakai_hari_ini' => 100.0,
                    'cukup_penuh' => true
                ];
                $t++;
            } else {
                $fraction = $sisaStok / $kebutuhanHarianFlock;
                $sisaStok = 0;
                $detailHarian[] = [
                    'hari_ke' => (int)floor($t),
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'hari_formatted' => $tanggal->locale('id')->isoFormat('dddd, D MMMM Y'),
                    'umur_ayam' => $umurHari,
                    'fase_umur' => $kebutuhan->fase_umur,
                    'gram_per_ekor' => $gramPerEkor,
                    'kebutuhan_harian_flock_gram' => $kebutuhanHarianFlock,
                    'stok_tersisa_akhir_hari_gram' => 0,
                    'persentase_terpakai_hari_ini' => round($fraction * 100, 2),
                    'cukup_penuh' => false
                ];
                $t += $fraction;
                break;
            }
        }

        $sisaHariRound = (int) round($t);
        $tanggalHabis = Carbon::now()->addSeconds((int)round($t * 24 * 3600));

        return [
            'sisa_hari' => $sisaHariRound,
            'tanggal_habis' => $t > 0 ? $tanggalHabis->format('Y-m-d H:i:s') : null,
            'tanggal_habis_formatted' => $t > 0 ? $tanggalHabis->locale('id')->isoFormat('dddd, D MMMM Y (HH:mm)') : 'Pakan Habis',
            'detail_harian' => $detailHarian
        ];
    }
}
