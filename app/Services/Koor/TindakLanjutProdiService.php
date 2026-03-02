<?php

namespace App\Services\Koor;

use App\Models\EarlyWarningSystem;
use Illuminate\Support\Facades\DB;

class TindakLanjutProdiService
{
    /**
     * Get data surat rekomitmen mahasiswa
     * @param string|null $search Search by id_rekomitmen
     * @param int|null $tahunMasuk Filter by angkatan
     * @param string|null $statusRekomitmen Filter by status_rekomitmen (yes/no)
     * @param int $perPage Items per page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getSuratRekomitmen($search = null, $tahunMasuk = null, $statusRekomitmen = null, $perPage = 10)
    {
        // Ambil data mahasiswa dengan surat rekomitmen
        // Hanya tampilkan yang sudah punya id_rekomitmen (sudah mengajukan rekomitmen)
        $query = EarlyWarningSystem::select(
                    'early_warning_system.id_rekomitmen as id_tiket',
                    'users.name as nama',
                    'mahasiswa.nim',
                    'early_warning_system.tanggal_pengajuan_rekomitmen as tanggal_pengajuan',
                    'dosen_users.name as dosen_wali',
                    'early_warning_system.status_rekomitmen as status_tindak_lanjut',
                    'early_warning_system.link_rekomitmen'
                )
                ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->whereNotNull('early_warning_system.id_rekomitmen'); // Hanya yang punya id_rekomitmen

        // Search by id_tiket
        if ($search) {
            $query->where('early_warning_system.id_rekomitmen', 'LIKE', '%' . $search . '%');
        }

        // Filter by tahun_masuk
        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        // Filter by status_tindak_lanjut (status_rekomitmen)
        if ($statusRekomitmen) {
            $query->where('early_warning_system.status_rekomitmen', $statusRekomitmen);
        }

        return $query->orderBy('early_warning_system.tanggal_pengajuan_rekomitmen', 'desc')
                    ->orderBy('mahasiswa.nim', 'asc')
                    ->paginate($perPage);
    }

    public function updateStatusRekomitmen($idRekomitmen, $status)
    {
        // Update status rekomitmen
        $rekomitmen = EarlyWarningSystem::where('id_rekomitmen', $idRekomitmen)->first();

        if (!$rekomitmen) {
            return ['success' => false, 'message' => 'Rekomitmen tidak ditemukan'];
        }
        $rekomitmen->status_rekomitmen = $status;
        $rekomitmen->save();

        return ['success' => true, 'message' => 'Status rekomitmen berhasil diperbarui'];
    }
}
