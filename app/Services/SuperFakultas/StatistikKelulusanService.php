<?php

namespace App\Services\SuperFakultas;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatistikKelulusanService
{
    /**
     * Membantu filter data query (Query Builder atau Eloquent) berdasarkan role prodi
     */
    private function applyProdiScope($query)
    {
        $user = Auth::user();
        if ($user) {
            if ($user->hasRole('admin')) {
                $query->where('mahasiswa.prodi_id', $user->prodi_id);
            } elseif ($user->hasRole('super_fakultas') && request()->has('prodi_id') && request('prodi_id') != '') {
                $query->where('mahasiswa.prodi_id', request('prodi_id'));
            }
        }

        return $query;
    }

    /**
     * Table Statistik Kelulusan per Prodi dengan detail per tahun angkatan
     * Untuk SuperFakultas - menampilkan data per prodi dan breakdown per tahun angkatan
     */
    public function getTableStatistikKelulusanPerProdiWithTahun($prodiId = null)
    {
        $query = Prodi::query();

        if ($prodiId) {
            $query->where('id', $prodiId);
        }

        $prodis = $query->get();
        $result = [];

        foreach ($prodis as $prodi) {
            $statsPerProdi = $this->getStatistikPerProdi($prodi);
            $detailPerTahun = $this->getStatistikPerTahun($prodi->id);

            $result[] = [
                'prodi' => $statsPerProdi['prodi'],
                'jumlah_mahasiswa' => $statsPerProdi['jumlah_mahasiswa'],
                'ipk_dibawah_2' => $statsPerProdi['ipk_dibawah_2'],
                'sks_kurang_dari_144' => $statsPerProdi['sks_kurang_dari_144'],
                'nilai_d_lebih_dari_5_persen' => $statsPerProdi['nilai_d_lebih_dari_5_persen'],
                'ada_nilai_e' => $statsPerProdi['ada_nilai_e'],
                'eligible' => $statsPerProdi['eligible'],
                'tidak_eligible' => $statsPerProdi['tidak_eligible'],
                'ipk_rata_rata' => $statsPerProdi['ipk_rata_rata'],
                'jumlah_aktif' => $statsPerProdi['jumlah_aktif'],
                'jumlah_mangkir' => $statsPerProdi['jumlah_mangkir'],
                'detail_per_tahun' => $detailPerTahun,
            ];
        }

        return $result;
    }

    private function getStatistikPerProdi($prodi)
    {
        $stats = AkademikMahasiswa::select(
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
            DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2 THEN 1 ELSE 0 END) as ipk_dibawah_2'),
            DB::raw('SUM(CASE WHEN akademik_mahasiswa.sks_lulus < 144 THEN 1 ELSE 0 END) as sks_kurang_dari_144'),
            DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as nilai_d_lebih_dari_5_persen'),
            DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_e = "yes" THEN 1 ELSE 0 END) as ada_nilai_e'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible'),
            DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as jumlah_aktif'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as jumlah_mangkir')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->where('mahasiswa.prodi_id', $prodi->id)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->first();

        return [
            'prodi' => [
                'id' => $prodi->id,
                'kode_prodi' => $prodi->kode_prodi,
                'nama_prodi' => $prodi->nama,
            ],
            'jumlah_mahasiswa' => $stats->jumlah_mahasiswa ?? 0,
            'ipk_dibawah_2' => $stats->ipk_dibawah_2 ?? 0,
            'sks_kurang_dari_144' => $stats->sks_kurang_dari_144 ?? 0,
            'nilai_d_lebih_dari_5_persen' => $stats->nilai_d_lebih_dari_5_persen ?? 0,
            'ada_nilai_e' => $stats->ada_nilai_e ?? 0,
            'eligible' => $stats->eligible ?? 0,
            'tidak_eligible' => $stats->tidak_eligible ?? 0,
            'ipk_rata_rata' => $stats->ipk_rata_rata ?? 0,
            'jumlah_aktif' => $stats->jumlah_aktif ?? 0,
            'jumlah_mangkir' => $stats->jumlah_mangkir ?? 0,
        ];
    }

    private function getStatistikPerTahun($prodiId)
    {
        return AkademikMahasiswa::select(
            'akademik_mahasiswa.tahun_masuk',
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
            DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2 THEN 1 ELSE 0 END) as ipk_dibawah_2'),
            DB::raw('SUM(CASE WHEN akademik_mahasiswa.sks_lulus < 144 THEN 1 ELSE 0 END) as sks_kurang_dari_144'),
            DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as nilai_d_lebih_dari_5_persen'),
            DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_e = "yes" THEN 1 ELSE 0 END) as ada_nilai_e'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible'),
            DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as jumlah_aktif'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as jumlah_mangkir')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->groupBy('akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get();
    }
}
