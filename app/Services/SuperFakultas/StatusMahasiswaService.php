<?php

declare(strict_types=1);

namespace App\Services\SuperFakultas;

use App\Models\AkademikMahasiswa;
use App\Models\EarlyWarningSystem;

/**
 * Service status EWS untuk Super Fakultas (lingkup seluruh fakultas).
 *
 * Scope prodi bersifat opsional: jika request menyertakan prodi_id maka
 * data dibatasi ke prodi tersebut, kalau tidak maka mencakup seluruh fakultas.
 */
class StatusMahasiswaService
{
    private const EWS_STATUSES = ['tepat_waktu', 'normal', 'perhatian', 'kritis'];

    /**
     * Distribusi jumlah mahasiswa per status EWS.
     *
     * @return array<string,int>
     */
    public function getDistribusiStatusEws(?int $tahunMasuk = null): array
    {
        $query = EarlyWarningSystem::query()
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        $prodiId = request('prodi_id');
        if (! empty($prodiId)) {
            $query->where('mahasiswa.prodi_id', $prodiId);
        }

        if ($tahunMasuk !== null) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $counts = $query->selectRaw('early_warning_system.status as status, COUNT(*) as jumlah')
            ->groupBy('early_warning_system.status')
            ->pluck('jumlah', 'status');

        $distribusi = [];
        foreach (self::EWS_STATUSES as $status) {
            $distribusi[$status] = (int) ($counts[$status] ?? 0);
        }

        return $distribusi;
    }

    /**
     * Detail lengkap 1 mahasiswa beserta status EWS terbaru.
     */
    public function getDetailMahasiswa(int $mahasiswaId): ?array
    {
        $mhs = AkademikMahasiswa::select(
            'mahasiswa.id as mahasiswa_id',
            'mahasiswa.nim',
            'users.name as nama_mahasiswa',
            'mahasiswa.status_mahasiswa',
            'prodis.id as prodi_id',
            'prodis.nama as nama_prodi',
            'prodis.kode_prodi',
            'akademik_mahasiswa.tahun_masuk',
            'akademik_mahasiswa.semester_aktif',
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
            ->where('mahasiswa.id', $mahasiswaId)
            ->first();

        if (! $mhs) {
            return null;
        }

        return [
            'mahasiswa_id' => $mhs->mahasiswa_id,
            'nim' => $mhs->nim,
            'nama_mahasiswa' => $mhs->nama_mahasiswa,
            'status_mahasiswa' => $mhs->status_mahasiswa,
            'prodi' => [
                'id' => $mhs->prodi_id,
                'kode_prodi' => $mhs->kode_prodi,
                'nama_prodi' => $mhs->nama_prodi,
            ],
            'tahun_masuk' => $mhs->tahun_masuk,
            'semester_aktif' => $mhs->semester_aktif,
            'sks_total' => $mhs->sks_lulus ?? 0,
            'ipk' => $mhs->ipk ?? 0,
            'nilai_d_melebihi_batas' => $mhs->nilai_d_melebihi_batas ?? 'no',
            'nilai_e' => $mhs->nilai_e ?? 'no',
            'ews_status' => $mhs->ews_status ?? null,
            'status_kelulusan' => $mhs->status_kelulusan ?? null,
        ];
    }
}
