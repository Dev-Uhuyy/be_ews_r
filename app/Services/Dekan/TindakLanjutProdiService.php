<?php

namespace App\Services\Dekan;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TindakLanjutProdiService
{
    /**
     * Membantu filter data query berdasarkan role prodi
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
                    'prodis.nama as nama_prodi',
                    'tindak_lanjuts.id',
                    'users.name as nama',
                    'mahasiswa.nim',
                    'tindak_lanjuts.tanggal_pengajuan',
                    'dosen_users.name as dosen_wali',
                    'tindak_lanjuts.status as status_tindak_lanjut',
                    'tindak_lanjuts.link',
                    'tindak_lanjuts.kategori'
                )
                ->join('early_warning_system', 'tindak_lanjuts.id_ews', '=', 'early_warning_system.id')
                ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
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

        $query = $this->applyProdiScope($query);

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

        $baseQuery = $this->applyProdiScope($baseQuery);

        return [
            'total_rekomitmen' => (clone $baseQuery)->where('tindak_lanjuts.kategori', 'rekomitmen')->count(),
            'total_pindah_prodi' => (clone $baseQuery)->where('tindak_lanjuts.kategori', 'pindah_prodi')->count(),
            'dalam_proses' => (clone $baseQuery)->where('tindak_lanjuts.status', 'belum_diverifikasi')->count(),
            'selesai' => (clone $baseQuery)->where('tindak_lanjuts.status', 'telah_diverifikasi')->count(),
        ];
    }

    /**
     * Get card summary for batch prodis - NO N+1 QUERIES
     * Returns data keyed by prodi_id
     */
    public function getCardSummaryBatch(array $prodiIds)
    {
        $result = [];

        // Single bulk query for all categories and statuses per prodi
        $bulkData = DB::table('tindak_lanjuts')
            ->select(
                'mahasiswa.prodi_id',
                'tindak_lanjuts.kategori',
                'tindak_lanjuts.status',
                DB::raw('COUNT(*) as jumlah')
            )
            ->join('early_warning_system', 'tindak_lanjuts.id_ews', '=', 'early_warning_system.id')
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->groupBy('mahasiswa.prodi_id', 'tindak_lanjuts.kategori', 'tindak_lanjuts.status')
            ->get()
            ->groupBy('prodi_id');

        foreach ($prodiIds as $prodiId) {
            $prodiData = $bulkData->get($prodiId, collect());

            $result[$prodiId] = [
                'total_rekomitmen' => $prodiData->where('kategori', 'rekomitmen')->sum('jumlah'),
                'total_pindah_prodi' => $prodiData->where('kategori', 'pindah_prodi')->sum('jumlah'),
                'dalam_proses' => $prodiData->where('status', 'belum_diverifikasi')->sum('jumlah'),
                'selesai' => $prodiData->where('status', 'telah_diverifikasi')->sum('jumlah'),
            ];
        }

        return $result;
    }

    public function updateStatus($id, $status)
    {
        // Pastikan hanya bisa update record yang prodinya sesuai dengan user
        $queryCheck = DB::table('tindak_lanjuts')
            ->join('early_warning_system', 'tindak_lanjuts.id_ews', '=', 'early_warning_system.id')
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('tindak_lanjuts.id', $id);
            
        $queryCheck = $this->applyProdiScope($queryCheck);
        
        if (!$queryCheck->exists()) {
            return ['success' => false, 'message' => 'Data tidak ditemukan atau Anda tidak memiliki akses ke data ini'];
        }

        $affected = DB::table('tindak_lanjuts')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]);

        if ($affected === 0) {
            $exists = DB::table('tindak_lanjuts')->where('id', $id)->exists();
            if (!$exists) {
                return ['success' => false, 'message' => 'Data tidak ditemukan'];
            }
        }

        $updatedData = DB::table('tindak_lanjuts')->where('id', $id)->first();

        return [
            'success' => true,
            'message' => 'Status berhasil diperbarui',
            'data' => $updatedData
        ];
    }

    public function bulkUpdateStatus($ids, $status)
    {
        // Filter ids yang boleh diupdate oleh prodi ini
        $validIdsQuery = DB::table('tindak_lanjuts')
            ->join('early_warning_system', 'tindak_lanjuts.id_ews', '=', 'early_warning_system.id')
            ->join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('tindak_lanjuts.id', $ids);
            
        $validIdsQuery = $this->applyProdiScope($validIdsQuery);
        $validIds = $validIdsQuery->pluck('tindak_lanjuts.id')->toArray();
        
        if (empty($validIds)) {
            return [
                'success' => false,
                'message' => 'Tidak ada data valid yang bisa diubah',
                'data' => [],
                'affected' => 0
            ];
        }

        $affected = DB::table('tindak_lanjuts')
            ->whereIn('id', $validIds)
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]);

        $updatedData = DB::table('tindak_lanjuts')->whereIn('id', $validIds)->get();

        return [
            'success' => true,
            'message' => $affected . ' status berhasil diperbarui',
            'data' => $updatedData,
            'affected' => $affected
        ];
    }
}
