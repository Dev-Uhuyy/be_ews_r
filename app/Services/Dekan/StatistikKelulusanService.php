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
}
