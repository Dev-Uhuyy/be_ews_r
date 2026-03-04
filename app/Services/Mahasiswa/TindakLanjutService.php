<?php

namespace App\Services\Mahasiswa;

use Illuminate\Support\Facades\DB;
use App\Models\TindakLanjut;
use App\Models\EarlyWarningSystem;

class TindakLanjutService
{
    public function getHistory($mahasiswaId)
    {
        return TindakLanjut::whereHas('ews', function($query) use ($mahasiswaId) {
            $query->where('akademik_mahasiswa_id', $mahasiswaId);
        })
        ->orderBy('tanggal_pengajuan', 'desc')
        ->get();
    }

    public function getCardSummary($mahasiswaId)
    {
        $baseQuery = TindakLanjut::whereHas('ews', function($query) use ($mahasiswaId) {
            $query->where('akademik_mahasiswa_id', $mahasiswaId);
        });

        return [
            'dalam_proses' => (clone $baseQuery)->where('status', 'belum_diverifikasi')->count(),
            'selesai' => (clone $baseQuery)->where('status', 'telah_diverifikasi')->count(),
        ];
    }

    public function submit($mahasiswaId, $data)
    {
        // Get the latest EWS record for this student
        $ews = EarlyWarningSystem::where('akademik_mahasiswa_id', $mahasiswaId)
            ->latest()
            ->first();

        if (!$ews) {
            return ['success' => false, 'message' => 'Status EWS mahasiswa tidak ditemukan'];
        }

        return DB::transaction(function() use ($ews, $data) {
            $tindakLanjut = TindakLanjut::create([
                'id_ews' => $ews->id,
                'kategori' => $data['kategori'],
                'link' => $data['link'],
                'status' => 'belum_diverifikasi',
                'tanggal_pengajuan' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Surat tindak lanjut berhasil diajukan',
                'data' => $tindakLanjut
            ];
        });
    }
}
