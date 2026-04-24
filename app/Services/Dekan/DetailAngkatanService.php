<?php

namespace App\Services\Dekan;

use App\Models\AkademikMahasiswa;
use App\Models\KhsKrsMahasiswa;
use Illuminate\Support\Facades\DB;

class DetailAngkatanService
{
    /**
     * Get detail mahasiswa per angkatan (tahun_masuk)
     * Optional filter: prodi_id
     */
    public function getDetailAngkatan($tahunMasuk, $prodiId = null)
    {
        $query = AkademikMahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_mahasiswa',
                    'akademik_mahasiswa.sks_lulus',
                    'akademik_mahasiswa.ipk',
                    DB::raw('early_warning_system.status_kelulusan as eligible')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($prodiId) {
            $query->where('mahasiswa.prodi_id', $prodiId);
        }

        $mahasiswas = $query->orderBy('users.name', 'asc')->get();

        $result = [];
        foreach ($mahasiswas as $mhs) {
            $detail = $this->getNilaiDetail($mhs->mahasiswa_id);
            $mkStatus = $this->getMkStatus($mhs->mahasiswa_id);

            $result[] = [
                'mahasiswa_id' => $mhs->mahasiswa_id,
                'nim' => $mhs->nim,
                'nama_mahasiswa' => $mhs->nama_mahasiswa,
                'sks_total' => $mhs->sks_lulus ?? 0,
                'ipk' => $mhs->ipk ?? 0,
                'nilai_d' => [
                    'jumlah' => $detail['jumlah_nilai_d'],
                    'total_sks' => $detail['total_sks_nilai_d'],
                ],
                'nilai_e' => [
                    'jumlah' => $detail['jumlah_nilai_e'],
                    'total_sks' => $detail['total_sks_nilai_e'],
                ],
                'mk_nasional' => $mkStatus['mk_nasional'],
                'mk_fakultas' => $mkStatus['mk_fakultas'],
                'mk_prodi' => $mkStatus['mk_prodi'],
                'eligible' => $mhs->eligible ?? 'noneligible',
            ];
        }

        return $result;
    }

    /**
     * Get detail nilai D dan E dari khs_krs_mahasiswa
     */
    private function getNilaiDetail($mahasiswaId)
    {
        $khsData = KhsKrsMahasiswa::join('mata_kuliahs', 'khs_krs_mahasiswa.matakuliah_id', '=', 'mata_kuliahs.id')
            ->where('khs_krs_mahasiswa.mahasiswa_id', $mahasiswaId)
            ->whereIn('khs_krs_mahasiswa.id', function ($query) use ($mahasiswaId) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa')
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->groupBy('matakuliah_id');
            })
            ->select(
                'khs_krs_mahasiswa.nilai_akhir_huruf',
                'mata_kuliahs.sks'
            )
            ->get();

        $jumlahNilaiD = 0;
        $totalSksNilaiD = 0;
        $jumlahNilaiE = 0;
        $totalSksNilaiE = 0;

        foreach ($khsData as $khs) {
            if ($khs->nilai_akhir_huruf === 'D') {
                $jumlahNilaiD++;
                $totalSksNilaiD += $khs->sks ?? 0;
            } elseif ($khs->nilai_akhir_huruf === 'E') {
                $jumlahNilaiE++;
                $totalSksNilaiE += $khs->sks ?? 0;
            }
        }

        return [
            'jumlah_nilai_d' => $jumlahNilaiD,
            'total_sks_nilai_d' => $totalSksNilaiD,
            'jumlah_nilai_e' => $jumlahNilaiE,
            'total_sks_nilai_e' => $totalSksNilaiE,
        ];
    }

    /**
     * Get status MK Nasional, Fakultas, Prodi
     */
    private function getMkStatus($mahasiswaId)
    {
        $akademik = AkademikMahasiswa::where('mahasiswa_id', $mahasiswaId)->first();

        return [
            'mk_nasional' => $akademik->mk_nasional ?? 'no',
            'mk_fakultas' => $akademik->mk_fakultas ?? 'no',
            'mk_prodi' => $akademik->mk_prodi ?? 'no',
        ];
    }

    /**
     * Get list tahun angkatan dan prodi yang tersedia
     * Optional filter: prodi_id
     */
    public function getTahunAngkatan($prodiId = null)
    {
        $query = AkademikMahasiswa::select(
                    'tahun_masuk',
                    DB::raw('GROUP_CONCAT(DISTINCT mahasiswa.prodi_id) as prodi_ids')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->whereNotNull('tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($prodiId) {
            $query->where('mahasiswa.prodi_id', $prodiId);
        }

        $tahunAngkatanData = $query->groupBy('tahun_masuk')
            ->orderBy('tahun_masuk', 'desc')
            ->get();

        $prodis = \App\Models\Prodi::all();

        if ($prodiId) {
            $prodiDetails = $prodis->filter(function ($prodi) use ($prodiId) {
                return $prodi->id == $prodiId;
            })->map(function ($prodi) {
                return [
                    'id' => $prodi->id,
                    'kode_prodi' => $prodi->kode_prodi,
                    'nama_prodi' => $prodi->nama,
                ];
            })->values();
        } else {
            $prodiDetails = $prodis->map(function ($prodi) {
                return [
                    'id' => $prodi->id,
                    'kode_prodi' => $prodi->kode_prodi,
                    'nama_prodi' => $prodi->nama,
                ];
            });
        }

        $tahunAngkatan = $tahunAngkatanData->map(function ($item) use ($prodis, $prodiId) {
            $prodiIds = $item->prodi_ids ? explode(',', $item->prodi_ids) : [];

            if ($prodiId) {
                $filteredProdis = $prodis->filter(function ($prodi) use ($prodiIds) {
                    return in_array($prodi->id, $prodiIds);
                });
            } else {
                $filteredProdis = $prodis->filter(function ($prodi) use ($prodiIds) {
                    return in_array($prodi->id, $prodiIds);
                });
            }

            $prodiDetails = $filteredProdis->map(function ($prodi) {
                return [
                    'id' => $prodi->id,
                    'kode_prodi' => $prodi->kode_prodi,
                    'nama_prodi' => $prodi->nama,
                ];
            })->values();

            return [
                'tahun_masuk' => $item->tahun_masuk,
                'prodi' => $prodiDetails,
            ];
        });

        return [
            'tahun_angkatan' => $tahunAngkatan,
            'prodi' => $prodiDetails,
        ];
    }
}
