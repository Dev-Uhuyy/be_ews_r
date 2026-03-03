<?php

namespace App\Services\Koor;

use Illuminate\Support\Facades\DB;

class TindakLanjutProdiService
{
    /**
     * Get data tindak lanjut mahasiswa
     * @param string|null $search Search by Nama/NIM
     * @param int|null $tahunMasuk Filter by angkatan
     * @param string|null $category Filter by category (rekomitmen/pindah_prodi)
     * @param string|null $status Filter by status
     * @return \Illuminate\Database\Query\Builder
     */
    private function getTindakLanjutQuery($search = null, $tahunMasuk = null, $category = null, $status = null)
    {
        $query = DB::table('tindak_lanjuts')
                ->select(
                    'tindak_lanjuts.id',
                    'users.name as nama',
                    'mahasiswa.nim',
                    'tindak_lanjuts.tanggal_pengajuan',
                    'dosen_users.name as dosen_wali',
                    'tindak_lanjuts.status as status_tindak_lanjut',
                    'tindak_lanjuts.link',
                    'tindak_lanjuts.kategori',
                    'tindak_lanjuts.catatan'
                )
                ->join('early_warning_system', 'tindak_lanjuts.id_ews', '=', 'early_warning_system.id')
                ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('dosen', 'akademik_mahasiswa.dosen_wali_id', '=', 'dosen.id')
                ->leftJoin('users as dosen_users', 'dosen.user_id', '=', 'dosen_users.id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'LIKE', '%' . $search . '%')
                  ->orWhere('mahasiswa.nim', 'LIKE', '%' . $search . '%');
            });
        }

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        if ($category) {
            $query->where('tindak_lanjuts.kategori', $category);
        }

        if ($status) {
            $query->where('tindak_lanjuts.status', $status);
        }

        return $query->orderBy('tindak_lanjuts.tanggal_pengajuan', 'desc')
                    ->orderBy('mahasiswa.nim', 'asc');
    }

    public function getTindakLanjutData($search = null, $tahunMasuk = null, $category = null, $status = null, $perPage = 10)
    {
        return $this->getTindakLanjutQuery($search, $tahunMasuk, $category, $status)->paginate($perPage);
    }

    public function getTindakLanjutExport($search = null, $tahunMasuk = null, $category = null, $status = null)
    {
        return $this->getTindakLanjutQuery($search, $tahunMasuk, $category, $status)->get();
    }

    public function getCardSummary()
    {
        $baseQuery = DB::table('tindak_lanjuts')
            ->join('early_warning_system', 'tindak_lanjuts.id_ews', '=', 'early_warning_system.id')
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        return [
            'total_rekomitmen' => (clone $baseQuery)->where('tindak_lanjuts.kategori', 'rekomitmen')->count(),
            'total_pindah_prodi' => (clone $baseQuery)->where('tindak_lanjuts.kategori', 'pindah_prodi')->count(),
            'dalam_proses' => (clone $baseQuery)->where('tindak_lanjuts.status', 'belum_diverifikasi')->count(),
            'selesai' => (clone $baseQuery)->where('tindak_lanjuts.status', 'diterima')->count(),
        ];
    }

    public function updateStatus($id, $status)
    {
        $affected = DB::table('tindak_lanjuts')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]);

        if ($affected === 0) {
            // Check if record exists
            $exists = DB::table('tindak_lanjuts')->where('id', $id)->exists();
            if (!$exists) {
                return ['success' => false, 'message' => 'Data tidak ditemukan'];
            }
            // If exists but affected is 0, it means the status was already the same
        }

        $updatedData = DB::table('tindak_lanjuts')->where('id', $id)->first();

        return [
            'success' => true,
            'message' => 'Status berhasil diperbarui',
            'data' => $updatedData
        ];
    }
}
