<?php

namespace App\Services\Kaprodi;

use App\Models\AkademikMahasiswa;
use Illuminate\Support\Facades\Auth;

class KaprodiMahasiswaListService
{
    private function getProdiId()
    {
        return Auth::user()->prodi_id;
    }

    /**
     * Get list mahasiswa dengan filter fleksibel untuk Kaprodi
     */
    public function getMahasiswaList($filters = [])
    {
        $prodiId = $this->getProdiId();

        $query = AkademikMahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_mahasiswa',
                    'mahasiswa.status_mahasiswa',
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
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId);

        // Pencarian Nama/NIM
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'LIKE', "%{$search}%")
                  ->orWhere('mahasiswa.nim', 'LIKE', "%{$search}%");
            });
        }

        // Filter status_mahasiswa
        if (!empty($filters['status_mahasiswa'])) {
            $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = ?', [strtolower($filters['status_mahasiswa'])]);
        } else {
            $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
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
            $query->where('akademik_mahasiswa.nilai_d_melebihi_batas', $hasNilaiD ? 'yes' : 'no');
        }

        // Filter memiliki nilai E
        if (isset($filters['has_nilai_e'])) {
            $hasNilaiE = filter_var($filters['has_nilai_e'], FILTER_VALIDATE_BOOLEAN);
            $query->where('akademik_mahasiswa.nilai_e', $hasNilaiE ? 'yes' : 'no');
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

        $mahasiswas = $query->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->orderBy('users.name', 'asc')
            ->get();

        return $mahasiswas->map(function ($mhs) {
            return [
                'mahasiswa_id' => $mhs->mahasiswa_id,
                'nim' => $mhs->nim,
                'nama_mahasiswa' => $mhs->nama_mahasiswa,
                'status_mahasiswa' => $mhs->status_mahasiswa,
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
     * Get list mahasiswa berdasarkan status (Kaprodi)
     */
    public function getMahasiswaByStatus($filters = [])
    {
        // Gunakan getMahasiswaList karena sudah mendukung status_mahasiswa & ews_status
        return $this->getMahasiswaList($filters);
    }
}