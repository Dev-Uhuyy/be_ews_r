<?php

namespace App\Services\Dekan;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use Illuminate\Support\Facades\DB;

class DetailDashboardService
{
    /**
     * Get detail dashboard - data per prodi dan tahun angkatan
     */
    public function getDetailDashboard($prodiId = null)
    {
        $prodis = $prodiId
            ? Prodi::where('id', $prodiId)->get()
            : Prodi::all();

        $result = [];

        foreach ($prodis as $prodi) {
            $dataPerProdi = $this->getDataPerProdi($prodi);
            $result[] = $dataPerProdi;
        }

        return $result;
    }

    private function getDataPerProdi($prodi)
    {
        $tahunData = AkademikMahasiswa::select(
                    'akademik_mahasiswa.tahun_masuk',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as mahasiswa_aktif'),
                    DB::raw('SUM(CASE WHEN mahasiswa.cuti_2 = "yes" THEN 1 ELSE 0 END) as jumlah_cuti_2x'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as tepat_waktu'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "normal" THEN 1 ELSE 0 END) as normal'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as perhatian'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as kritis')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodi->id)
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('akademik_mahasiswa.tahun_masuk')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
                ->get();

        return [
            'prodi' => [
                'id' => $prodi->id,
                'kode_prodi' => $prodi->kode_prodi,
                'nama_prodi' => $prodi->nama,
            ],
            'tahun_angkatan' => $tahunData,
        ];
    }

    /**
     * Get list mahasiswa dengan kriteria spesifik per prodi dan tahun
     *
     * Filter options:
     * - prodi_id (required)
     * - tahun_masuk (optional)
     * - criteria: 'aktif', 'cuti_2x', 'tepat_waktu', 'perhatian', 'kritis'
     */
    public function getMahasiswaListByCriteria($prodiId, $tahunMasuk = null, $criteria = null)
    {
        $query = AkademikMahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_mahasiswa',
                    'akademik_mahasiswa.tahun_masuk',
                    'akademik_mahasiswa.sks_lulus',
                    'akademik_mahasiswa.ipk',
                    'mahasiswa.status_mahasiswa',
                    'early_warning_system.status as ews_status'
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        // Apply criteria filter
        if ($criteria) {
            switch ($criteria) {
                case 'aktif':
                    $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "aktif"');
                    break;
                case 'cuti_2x':
                    $query->where('mahasiswa.cuti_2', 'yes');
                    break;
                case 'tepat_waktu':
                    $query->where('early_warning_system.status', 'tepat_waktu');
                    break;
                case 'perhatian':
                    $query->where('early_warning_system.status', 'perhatian');
                    break;
                case 'kritis':
                    $query->where('early_warning_system.status', 'kritis');
                    break;
            }
        }

        $mahasiswas = $query->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->orderBy('users.name', 'asc')
            ->get();

        // Group by tahun_masuk
        $grouped = $mahasiswas->groupBy('tahun_masuk')->map(function ($items, $tahun) {
            return [
                'tahun_masuk' => $tahun,
                'jumlah' => $items->count(),
                'mahasiswa' => $items->map(function ($mhs) {
                    return [
                        'mahasiswa_id' => $mhs->mahasiswa_id,
                        'nim' => $mhs->nim,
                        'nama_mahasiswa' => $mhs->nama_mahasiswa,
                        'sks_total' => $mhs->sks_lulus ?? 0,
                        'ipk' => $mhs->ipk ?? 0,
                        'status_mahasiswa' => $mhs->status_mahasiswa,
                        'ews_status' => $mhs->ews_status,
                    ];
                })->values()
            ];
        })->values();

        return $grouped;
    }
}
