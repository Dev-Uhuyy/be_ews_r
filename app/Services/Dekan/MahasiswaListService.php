<?php

namespace App\Services\Dekan;

use App\Models\AkademikMahasiswa;
use Illuminate\Support\Facades\DB;

class MahasiswaListService
{
    /**
     * Get list mahasiswa dengan filter fleksibel
     *
     * Filter options:
     * - prodi_id (optional)
     * - tahun_masuk (optional)
     * - ipk_max: IPK < nilai tertentu
     * - sks_max: SKS lulus < nilai tertentu
     * - has_nilai_d: true/false - memiliki nilai D melebihi batas
     * - has_nilai_e: true/false - memiliki nilai E
     * - status_kelulusan: 'eligible' atau 'noneligible'
     * - ews_status: 'tepat_waktu', 'normal', 'perhatian', 'kritis'
     */
    public function getMahasiswaList($filters = [])
    {
        $query = AkademikMahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_mahasiswa',
                    'prodis.nama as nama_prodi',
                    'prodis.kode_prodi',
                    'akademik_mahasiswa.tahun_masuk',
                    'akademik_mahasiswa.sks_lulus',
                    'akademik_mahasiswa.ipk',
                    'akademik_mahasiswa.nilai_d_melebihi_batas',
                    'akademik_mahasiswa.nilai_e',
                    'early_warning_system.status as ews_status',
                    'early_warning_system.status_kelulusan'
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        // Filter by prodi_id
        if (!empty($filters['prodi_id'])) {
            $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }

        // Filter by tahun_masuk
        if (!empty($filters['tahun_masuk'])) {
            $query->where('akademik_mahasiswa.tahun_masuk', $filters['tahun_masuk']);
        }

        // Filter IPK < nilai tertentu
        if (isset($filters['ipk_max']) && is_numeric($filters['ipk_max'])) {
            $query->where('akademik_mahasiswa.ipk', '<', $filters['ipk_max']);
        }

        // Filter SKS lulus < nilai tertentu
        if (isset($filters['sks_max']) && is_numeric($filters['sks_max'])) {
            $query->where('akademik_mahasiswa.sks_lulus', '<', $filters['sks_max']);
        }

        // Filter memiliki nilai D melebihi batas
        if (isset($filters['has_nilai_d'])) {
            $hasNilaiD = filter_var($filters['has_nilai_d'], FILTER_VALIDATE_BOOLEAN);
            if ($hasNilaiD) {
                $query->where('akademik_mahasiswa.nilai_d_melebihi_batas', 'yes');
            } else {
                $query->where('akademik_mahasiswa.nilai_d_melebihi_batas', 'no');
            }
        }

        // Filter memiliki nilai E
        if (isset($filters['has_nilai_e'])) {
            $hasNilaiE = filter_var($filters['has_nilai_e'], FILTER_VALIDATE_BOOLEAN);
            if ($hasNilaiE) {
                $query->where('akademik_mahasiswa.nilai_e', 'yes');
            } else {
                $query->where('akademik_mahasiswa.nilai_e', 'no');
            }
        }

        // Filter status kelulusan
        if (!empty($filters['status_kelulusan'])) {
            $status = $filters['status_kelulusan'];
            if (in_array($status, ['eligible', 'noneligible'])) {
                $query->where('early_warning_system.status_kelulusan', $status);
            }
        }

        // Filter EWS status
        if (!empty($filters['ews_status'])) {
            $ewsStatus = $filters['ews_status'];
            if (in_array($ewsStatus, ['tepat_waktu', 'normal', 'perhatian', 'kritis'])) {
                $query->where('early_warning_system.status', $ewsStatus);
            }
        }

        $mahasiswas = $query->orderBy('prodis.nama', 'asc')
            ->orderBy('users.name', 'asc')
            ->get();

        return $mahasiswas->map(function ($mhs) {
            return [
                'mahasiswa_id' => $mhs->mahasiswa_id,
                'nim' => $mhs->nim,
                'nama_mahasiswa' => $mhs->nama_mahasiswa,
                'prodi' => [
                    'id' => $mhs->prodi_id,
                    'kode_prodi' => $mhs->kode_prodi,
                    'nama_prodi' => $mhs->nama_prodi,
                ],
                'tahun_masuk' => $mhs->tahun_masuk,
                'sks_total' => $mhs->sks_lulus ?? 0,
                'ipk' => $mhs->ipk ?? 0,
                'nilai_d_melebihi_batas' => $mhs->nilai_d_melebihi_batas ?? 'no',
                'nilai_e' => $mhs->nilai_e ?? 'no',
                'ews_status' => $mhs->ews_status ?? null,
                'status_kelulusan' => $mhs->status_kelulusan ?? null,
            ];
        });
    }

    /**
     * Get list kriteria yang tersedia
     */
    public function getAvailableKriteria()
    {
        return [
            'filters' => [
                'prodi_id' => 'Filter berdasarkan ID Prodi',
                'tahun_masuk' => 'Filter berdasarkan tahun angkatan',
                'ipk_max' => 'IPK kurang dari nilai (contoh: 2.0)',
                'sks_max' => 'SKS lulus kurang dari nilai (contoh: 144)',
                'has_nilai_d' => 'Memiliki nilai D melebihi batas (true/false)',
                'has_nilai_e' => 'Memiliki nilai E (true/false)',
                'status_kelulusan' => 'Status kelulusan (eligible/noneligible)',
                'ews_status' => 'Status EWS (tepat_waktu/normal/perhatian/kritis)',
            ],
            'status_kelulusan_options' => ['eligible', 'noneligible'],
            'ews_status_options' => ['tepat_waktu', 'normal', 'perhatian', 'kritis'],
        ];
    }
}
