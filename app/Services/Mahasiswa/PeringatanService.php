<?php

namespace App\Services\Mahasiswa;

use App\Models\Mahasiswa;

class PeringatanService
{
    public function getPeringatan($userId)
    {
        // Get mahasiswa by user_id with relationships
        $mahasiswa = Mahasiswa::where('user_id', $userId)
            ->with([
                'akademikmahasiswa.early_warning_systems'
            ])->first();

        if (!$mahasiswa) {
            return null;
        }

        $akademik = $mahasiswa->akademikmahasiswa;

        // Get latest EWS
        $ews = null;
        if ($akademik) {
            $ews = $akademik->early_warning_systems()->latest()->first();
        }

        // Compile riwayat SPS
        $riwayatSps = [];
        if ($ews) {
            if ($ews->SPS1 === 'yes') {
                $riwayatSps[] = [
                    'semester' => 1,
                    'status' => 'SPS1',
                    'keterangan' => 'IPS semester 1 < 2.0'
                ];
            }
            if ($ews->SPS2 === 'yes') {
                $riwayatSps[] = [
                    'semester' => 2,
                    'status' => 'SPS2',
                    'keterangan' => 'IPS semester 2 < 2.0'
                ];
            }
            if ($ews->SPS3 === 'yes') {
                $riwayatSps[] = [
                    'semester' => 3,
                    'status' => 'SPS3',
                    'keterangan' => 'IPS semester 3 < 2.0 (Wajib rekomitmen)'
                ];
            }
        }

        return [
            'status_ews' => $ews ? $ews->status : null,
            'riwayat_sps' => $riwayatSps,
        ];
    }
}
