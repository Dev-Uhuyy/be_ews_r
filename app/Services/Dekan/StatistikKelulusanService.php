<?php

namespace App\Services\Dekan;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
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
     * Table Statistik Kelulusan per Prodi (tanpa filter tahun)
     * Untuk Dekan - menampilkan aggregated data per prodi
     */
    public function getTableStatistikKelulusanPerProdi()
    {
        $prodis = Prodi::all();
        $result = [];

        foreach ($prodis as $prodi) {
            $stats = $this->getStatistikPerProdi($prodi);
            $result[] = $stats;
        }

        return $result;
    }

    /**
     * Table Statistik Kelulusan per Prodi dengan detail per tahun angkatan
     * Untuk Dekan - menampilkan data per prodi dan breakdown per tahun angkatan
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
                'detail_per_tahun' => $detailPerTahun,
            ];
        }

        return $result;
    }

    private function getStatistikPerProdi($prodi)
    {
        $query = AkademikMahasiswa::select(
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2 THEN 1 ELSE 0 END) as ipk_dibawah_2'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.sks_lulus < 144 THEN 1 ELSE 0 END) as sks_kurang_dari_144'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as nilai_d_lebih_dari_5_persen'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_e = "yes" THEN 1 ELSE 0 END) as ada_nilai_e'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodi->id)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        $stats = $query->first();

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
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('akademik_mahasiswa.tahun_masuk')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
                ->get();
    }
}
