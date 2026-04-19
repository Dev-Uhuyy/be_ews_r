<?php

namespace App\Services\Dekan;

use App\Models\AkademikMahasiswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StatistikKelulusanService
{
    /**
     * Membantu filter data query (Query Builder atau Eloquent) berdasarkan role prodi
     */
    private function applyProdiScope($query)
    {
        $user = Auth::user();
        if ($user) {
            if ($user->hasRole('kaprodi')) {
                $query->where('mahasiswa.prodi_id', $user->prodi_id);
            } elseif ($user->hasRole('dekan') && request()->has('prodi_id') && request('prodi_id') != '') {
                $query->where('mahasiswa.prodi_id', request('prodi_id'));
            }
        }
        return $query;
    }
    public function getCardStatistikKelulusan($tahunMasuk = null)
    {
        // berisi mahasiswa eligible/noneligible, aktif, mangkir, cuti, sebaran IPK (< 2.5 , 2.5-3, > 3.0), Pemenuhan MK Nasional/Fakultas/Prodi
        // dengan filter angkatan (tahun_masuk) dan exclude mahasiswa yang sudah lulus dan DO
        $query = AkademikMahasiswa::select(
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as noneligible'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as aktif'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as mangkir'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as cuti'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2.5 THEN 1 ELSE 0 END) as ipk_kurang_dari_2_5'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk >= 2.5 AND akademik_mahasiswa.ipk <= 3.0 THEN 1 ELSE 0 END) as ipk_antara_2_5_3'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk > 3.0 THEN 1 ELSE 0 END) as ipk_lebih_dari_3'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_nasional = "yes" THEN 1 ELSE 0 END) as mk_nasional'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_fakultas = "yes" THEN 1 ELSE 0 END) as mk_fakultas'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_prodi = "yes" THEN 1 ELSE 0 END) as mk_prodi')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $query = $this->applyProdiScope($query);

        return $query->first();
    }

    public function getTableStatistikKelulusan($perPage = 10)
    {
        //berisi angkatan, jmlh mhs, ipk < 2 , sks<144, nilai D, nilai E, eligible, noneligible, ipk rata2.
        //exclude mahasiswa yang sudah lulus dan DO
        $tableData = AkademikMahasiswa::select(
                    'prodis.nama as nama_prodi',
                    'prodis.kode_prodi',
                    'akademik_mahasiswa.tahun_masuk',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.mahasiswa_id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2 THEN 1 ELSE 0 END) as ipk_kurang_dari_2'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.sks_lulus < 144 THEN 1 ELSE 0 END) as sks_kurang_dari_144'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as nilai_d_melebihi_batas'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_e = "yes" THEN 1 ELSE 0 END) as nilai_e'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as noneligible'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata2')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        $tableData = $this->applyProdiScope($tableData)
                ->groupBy('prodis.nama', 'prodis.kode_prodi', 'akademik_mahasiswa.tahun_masuk')
                ->orderBy('prodis.nama', 'asc')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc');

        return $tableData->paginate($perPage);
    }

    /**
     * Get card statistik kelulusan for batch prodis - NO N+1 QUERIES
     * Returns data keyed by prodi_id
     */
    public function getCardStatistikKelulusanBatch(array $prodiIds, $tahunMasuk = null)
    {
        $result = [];

        // Single bulk query for all stats by prodi
        $bulkStats = AkademikMahasiswa::select(
                    'mahasiswa.prodi_id',
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as noneligible'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as aktif'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as mangkir'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as cuti'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2.5 THEN 1 ELSE 0 END) as ipk_kurang_dari_2_5'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk >= 2.5 AND akademik_mahasiswa.ipk <= 3.0 THEN 1 ELSE 0 END) as ipk_antara_2_5_3'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk > 3.0 THEN 1 ELSE 0 END) as ipk_lebih_dari_3'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_nasional = "yes" THEN 1 ELSE 0 END) as mk_nasional'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_fakultas = "yes" THEN 1 ELSE 0 END) as mk_fakultas'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_prodi = "yes" THEN 1 ELSE 0 END) as mk_prodi')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->whereIn('mahasiswa.prodi_id', $prodiIds);

        if ($tahunMasuk) {
            $bulkStats->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $statsByProdi = $bulkStats->groupBy('mahasiswa.prodi_id')
            ->get()
            ->keyBy('prodi_id');

        foreach ($prodiIds as $prodiId) {
            $stats = $statsByProdi->get($prodiId);

            $result[$prodiId] = [
                'eligible' => $stats ? (int)$stats->eligible : 0,
                'noneligible' => $stats ? (int)$stats->noneligible : 0,
                'aktif' => $stats ? (int)$stats->aktif : 0,
                'mangkir' => $stats ? (int)$stats->mangkir : 0,
                'cuti' => $stats ? (int)$stats->cuti : 0,
                'ipk_kurang_dari_2_5' => $stats ? (int)$stats->ipk_kurang_dari_2_5 : 0,
                'ipk_antara_2_5_3' => $stats ? (int)$stats->ipk_antara_2_5_3 : 0,
                'ipk_lebih_dari_3' => $stats ? (int)$stats->ipk_lebih_dari_3 : 0,
                'mk_nasional' => $stats ? (int)$stats->mk_nasional : 0,
                'mk_fakultas' => $stats ? (int)$stats->mk_fakultas : 0,
                'mk_prodi' => $stats ? (int)$stats->mk_prodi : 0,
            ];
        }

        return $result;
    }
}
