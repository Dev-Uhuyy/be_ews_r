<?php

declare(strict_types=1);

namespace App\Services\SuperFakultas;

use App\Models\AkademikMahasiswa;
use App\Models\KhsKrsMahasiswa;
use App\Models\Prodi;
use Illuminate\Support\Facades\DB;

class DetailAngkatanService
{
    public function getDetailAngkatan(int $tahunMasuk, ?int $prodiId = null): array
    {
        $query = AkademikMahasiswa::select(
            'akademik_mahasiswa.id as akademik_id',
            'mahasiswa.id as mahasiswa_id',
            'mahasiswa.nim',
            'users.name as nama_mahasiswa',
            'akademik_mahasiswa.sks_lulus',
            'akademik_mahasiswa.ipk',
            'early_warning_system.status_kelulusan as eligible'
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

        $akademikIds = $mahasiswas->pluck('akademik_id')->toArray();
        $mahasiswaIds = $mahasiswas->pluck('mahasiswa_id')->toArray();

        $allKhsData = $this->getAllNilaiDetails($mahasiswaIds);
        $allMkStatus = $this->getAllMkStatus($akademikIds);

        $result = [];
        foreach ($mahasiswas as $mhs) {
            $detail = $allKhsData[$mhs->mahasiswa_id] ?? ['jumlah_nilai_d' => 0, 'total_sks_nilai_d' => 0, 'jumlah_nilai_e' => 0, 'total_sks_nilai_e' => 0];
            $mkStatus = $allMkStatus[$mhs->akademik_id] ?? ['mk_nasional' => 'no', 'mk_fakultas' => 'no', 'mk_prodi' => 'no'];

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

    private function getAllNilaiDetails(array $mahasiswaIds): array
    {
        if (empty($mahasiswaIds)) {
            return [];
        }

        $khsData = KhsKrsMahasiswa::join('mata_kuliahs', 'khs_krs_mahasiswa.matakuliah_id', '=', 'mata_kuliahs.id')
            ->whereIn('khs_krs_mahasiswa.mahasiswa_id', $mahasiswaIds)
            ->whereIn('khs_krs_mahasiswa.id', function ($query) use ($mahasiswaIds) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa')
                    ->whereIn('mahasiswa_id', $mahasiswaIds)
                    ->groupBy('matakuliah_id');
            })
            ->select(
                'khs_krs_mahasiswa.mahasiswa_id',
                'khs_krs_mahasiswa.nilai_akhir_huruf',
                'mata_kuliahs.sks'
            )
            ->get();

        $result = [];
        foreach ($mahasiswaIds as $mhsId) {
            $result[$mhsId] = ['jumlah_nilai_d' => 0, 'total_sks_nilai_d' => 0, 'jumlah_nilai_e' => 0, 'total_sks_nilai_e' => 0];
        }

        foreach ($khsData as $khs) {
            $mhsId = $khs->mahasiswa_id;
            if ($khs->nilai_akhir_huruf === 'D') {
                $result[$mhsId]['jumlah_nilai_d']++;
                $result[$mhsId]['total_sks_nilai_d'] += $khs->sks ?? 0;
            } elseif ($khs->nilai_akhir_huruf === 'E') {
                $result[$mhsId]['jumlah_nilai_e']++;
                $result[$mhsId]['total_sks_nilai_e'] += $khs->sks ?? 0;
            }
        }

        return $result;
    }

    private function getAllMkStatus(array $akademikIds): array
    {
        if (empty($akademikIds)) {
            return [];
        }

        $akademiks = AkademikMahasiswa::whereIn('id', $akademikIds)->get()->keyBy('id');

        $result = [];
        foreach ($akademikIds as $id) {
            $akademik = $akademiks->get($id);
            $result[$id] = [
                'mk_nasional' => $akademik?->mk_nasional ?? 'no',
                'mk_fakultas' => $akademik?->mk_fakultas ?? 'no',
                'mk_prodi' => $akademik?->mk_prodi ?? 'no',
            ];
        }

        return $result;
    }

    public function getTahunAngkatan(?int $prodiId = null): array
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

        $prodis = Prodi::query()->when($prodiId, fn ($q) => $q->where('id', $prodiId))->get();

        $prodiDetails = $prodis->map(fn ($prodi) => [
            'id' => $prodi->id,
            'kode_prodi' => $prodi->kode_prodi,
            'nama_prodi' => $prodi->nama,
        ])->values();

        $tahunAngkatan = $tahunAngkatanData->map(function ($item) use ($prodis): array {
            $prodiIds = $item->prodi_ids ? explode(',', $item->prodi_ids) : [];
            $filteredProdis = $prodis->filter(fn ($prodi) => in_array($prodi->id, $prodiIds));

            return [
                'tahun_masuk' => $item->tahun_masuk,
                'prodi' => $filteredProdis->map(fn ($prodi) => [
                    'id' => $prodi->id,
                    'kode_prodi' => $prodi->kode_prodi,
                    'nana_prodi' => $prodi->nama,
                ])->values(),
            ];
        });

        return [
            'tahun_angkatan' => $tahunAngkatan,
            'prodi' => $prodiDetails,
        ];
    }
}
