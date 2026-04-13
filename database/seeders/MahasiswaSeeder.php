<?php

namespace Database\Seeders;

use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;

class MahasiswaSeeder extends Seeder
{
    /**
     * Update mahasiswa yang sudah ada di DB agar punya prodi_id.
     *
     * Data mahasiswa sudah ada di sti_api.sql (id 1–8).
     * Migration EWS menambah kolom: prodi_id, minat, cuti_2.
     * Seeder ini mengisi kolom-kolom baru tersebut agar tidak NULL.
     */
    public function run(): void
    {
        // Mapping user_id → kode_prodi berdasarkan data di sti_api.sql
        $assignments = [
            // A11 mahasiswa: user_id 8, 9
            8  => 'A11',
            9  => 'A11',
            // A12 mahasiswa: user_id 19, 20
            19 => 'A12',
            20 => 'A12',
            // A14 mahasiswa: user_id 30, 31
            30 => 'A14',
            31 => 'A14',
            // A15 mahasiswa: user_id 41, 42
            41 => 'A15',
            42 => 'A15',
        ];

        foreach ($assignments as $userId => $kodeProdi) {
            $prodi = Prodi::where('kode_prodi', $kodeProdi)->first();
            $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

            if ($mahasiswa) {
                // Update kolom EWS yang ditambahkan oleh migration
                $mahasiswa->updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'prodi_id'        => $prodi?->id,
                        'minat'           => null,
                        'cuti_2'          => 'no',
                        'status_mahasiswa' => 'aktif',
                    ]
                );
            }
        }

        $this->command->info('✔ MahasiswaSeeder: prodi_id & kolom EWS diisi untuk ' . count($assignments) . ' mahasiswa.');
    }
}
