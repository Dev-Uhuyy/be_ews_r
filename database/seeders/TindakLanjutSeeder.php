<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TindakLanjutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // but user asked to remove "permohonan/keluhan" style data if exists)
        DB::table('tindak_lanjuts')->truncate();

        $akmIds = DB::table('akademik_mahasiswa')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->pluck('akademik_mahasiswa.id')
            ->take(10);

        if ($akmIds->isEmpty()) {
            return;
        }

        foreach ($akmIds as $index => $akmId) {
            // Get latest EWS for this AKM
            $ewsId = DB::table('early_warning_system')
                ->where('akademik_mahasiswa_id', $akmId)
                ->value('id');

            if (!$ewsId) {
                // Create a dummy EWS if not exists for the AKM to seed data correctly
                $ewsId = DB::table('early_warning_system')->insertGetId([
                    'akademik_mahasiswa_id' => $akmId,
                    'status' => 'normal',
                    'status_kelulusan' => 'noneligible',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Seed
            DB::table('tindak_lanjuts')->insert([
                'id_ews' => $ewsId,
                'kategori' => $index % 2 == 0 ? 'rekomitmen' : 'pindah_prodi',
                'link' => 'https://docs.google.com/document/d/example-' . $index,
                'catatan' => 'Seeded data for testing ' . ($index % 2 == 0 ? 'rekomitmen' : 'pindah_prodi'),
                'status' => $index % 3 == 0 ? 'telah_diverifikasi' : 'belum_diverifikasi',
                'tanggal_pengajuan' => now()->subDays($index),
                'created_at' => now()->subDays($index),
                'updated_at' => now()->subDays($index),
            ]);
        }
    }
}
