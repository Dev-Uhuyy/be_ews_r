<?php

namespace App\Services\Mahasiswa;

use App\Models\AkademikMahasiswa;
use App\Models\IpsMahasiswa;
use App\Models\KhsKrsMahasiswa;
use App\Models\EarlyWarningSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProfileService
{
    public function getProfile()
    {
        $user = Auth::user();
        $mahasiswa = $user->mahasiswa;

        if (!$mahasiswa) {
            throw new \Exception('Data mahasiswa tidak ditemukan');
        }

        $akademik = AkademikMahasiswa::where('mahasiswa_id', $mahasiswa->id)->first();
        $ips = IpsMahasiswa::where('mahasiswa_id', $mahasiswa->id)->first();
        $ews = $akademik ? EarlyWarningSystem::where('akademik_mahasiswa_id', $akademik->id)->first() : null;

        $khsKrsWithNilaiDE = $this->getMatakuliahWithNilaiDE($mahasiswa->id);
        $progressMk = $this->getProgressMk($akademik);

        return [
            'mahasiswa' => [
                'nama' => $user->name,
                'nim' => $mahasiswa->nim,
                'email' => $user->email,
                'semester_aktif' => $akademik->semester_aktif ?? 1,
                'tahun_masuk' => $akademik->tahun_masuk ?? null,
            ],
            'dosen_wali' => $akademik && $akademik->dosenWali ? [
                'nama' => $akademik->dosenWali->nama_lengkap,
                'npp' => $akademik->dosenWali->npp,
            ] : null,
            'akademik' => [
                'ipk' => $akademik->ipk ?? 0,
                'sks_lulus' => $akademik->sks_lulus ?? 0,
                'sks_tempuh' => $akademik->sks_tempuh ?? 0,
                'sks_now' => $akademik->sks_now ?? 0,
            ],
            'ews' => [
                'status' => $ews->status ?? null,
                'status_kelulusan' => $ews->status_kelulusan ?? null,
                'alasan_tidak_eligible' => $this->getAlasanTidakEligible($akademik),
            ],
            'ips' => $this->getIpsData($ips),
            'matakuliah_nilai_de' => $khsKrsWithNilaiDE,
            'progress_mk' => $progressMk,
        ];
    }

    private function getIpsData($ips)
    {
        if (!$ips) {
            return [];
        }

        $ipsData = [];
        for ($i = 1; $i <= 14; $i++) {
            $ipsField = 'ips_' . $i;
            if ($ips->$ipsField !== null) {
                $ipsData[] = [
                    'semester' => $i,
                    'ips' => (float) $ips->$ipsField,
                ];
            }
        }

        return $ipsData;
    }

    private function getMatakuliahWithNilaiDE($mahasiswaId)
    {
        return KhsKrsMahasiswa::join('mata_kuliahs', 'khs_krs_mahasiswa.matakuliah_id', '=', 'mata_kuliahs.id')
            ->join('kelompok_mata_kuliah', 'khs_krs_mahasiswa.kelompok_id', '=', 'kelompok_mata_kuliah.id')
            ->where('khs_krs_mahasiswa.mahasiswa_id', $mahasiswaId)
            ->whereIn('khs_krs_mahasiswa.nilai_akhir_huruf', ['D', 'E'])
            ->whereIn('khs_krs_mahasiswa.id', function ($query) use ($mahasiswaId) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa')
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->groupBy('matakuliah_id');
            })
            ->select(
                'mata_kuliahs.kode as kode_mk',
                'mata_kuliahs.name as nama_mk',
                'mata_kuliahs.sks',
                'mata_kuliahs.semester as semester_mk',
                'kelompok_mata_kuliah.kode as kelompok',
                'khs_krs_mahasiswa.nilai_akhir_huruf as nilai',
                'khs_krs_mahasiswa.nilai_akhir_angka as nilai_angka'
            )
            ->orderBy('mata_kuliahs.semester', 'asc')
            ->get()
            ->map(function ($mk) {
                return [
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'sks' => $mk->sks,
                    'semester' => $mk->semester_mk,
                    'kelompok' => $mk->kelompok,
                    'nilai' => $mk->nilai,
                    'nilai_angka' => $mk->nilai_angka,
                ];
            });
    }

    private function getProgressMk($akademik)
    {
        if (!$akademik) {
            return [
                'mk_nasional' => 'no',
                'mk_fakultas' => 'no',
                'mk_prodi' => 'no',
            ];
        }

        return [
            'mk_nasional' => $akademik->mk_nasional ?? 'no',
            'mk_fakultas' => $akademik->mk_fakultas ?? 'no',
            'mk_prodi' => $akademik->mk_prodi ?? 'no',
        ];
    }

    private function getAlasanTidakEligible($akademik)
    {
        if (!$akademik) {
            return [];
        }

        $alasan = [];

        if ($akademik->ipk <= 2.0) {
            $alasan[] = 'IPK kurang dari atau sama dengan 2.0';
        }

        if ($akademik->sks_lulus < 144) {
            $alasan[] = 'SKS Lulus kurang dari 144';
        }

        if ($akademik->mk_nasional !== 'yes') {
            $alasan[] = 'MK Nasional belum diselesaikan';
        }

        if ($akademik->mk_fakultas !== 'yes') {
            $alasan[] = 'MK Fakultas belum diselesaikan';
        }

        if ($akademik->mk_prodi !== 'yes') {
            $alasan[] = 'MK Prodi belum diselesaikan';
        }

        if ($akademik->nilai_e === 'yes') {
            $alasan[] = 'Memiliki nilai E';
        }

        if ($akademik->nilai_d_melebihi_batas === 'yes') {
            $alasan[] = 'Nilai D melebihi batas 5% (lebih dari 7.2 SKS atau lebih dari 2 MK)';
        }

        return $alasan;
    }
}
