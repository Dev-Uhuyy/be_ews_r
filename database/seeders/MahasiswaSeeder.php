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
     *
     * Also creates a Mahasiswa record for the test user (mahasiswa@ews.com)
     * so that AkademikMahasiswaSeeder can link to it.
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

        // Create Mahasiswa record for test user if it doesn't exist
        $testUser = User::where('email', 'mahasiswa@ews.com')->first();
        if ($testUser) {
            $prodiA11 = Prodi::where('kode_prodi', 'A11')->first();
            $existingMhs = Mahasiswa::where('user_id', $testUser->id)->first();

            if (!$existingMhs) {
                // Create new Mahasiswa record for test user
                // Find highest existing NIM and increment
                $highestNim = Mahasiswa::max('nim');
                $newNim = $highestNim ? ((int)$highestNim + 1) : '202300001';

                Mahasiswa::create([
                    'user_id'          => $testUser->id,
                    'prodi_id'         => $prodiA11?->id,
                    'nim'              => (string)$newNim,
                    'transkrip'        => null,
                    'telepon'          => null,
                    'minat'            => null,
                    'cuti_2'           => 'no',
                    'status_mahasiswa' => 'aktif',
                ]);
                $this->command->info("✔ Created Mahasiswa record for test user (user_id: {$testUser->id}, NIM: {$newNim})");
            } else {
                // Update existing to ensure proper linking
                $existingMhs->update([
                    'prodi_id'         => $prodiA11?->id,
                    'status_mahasiswa' => 'aktif',
                ]);
                $this->command->info("✔ Updated existing Mahasiswa record for test user (id: {$existingMhs->id})");
            }
        } else {
            $this->command->warn('⚠ Test user mahasiswa@ews.com not found - skipping');
        }

        $this->command->info('✔ MahasiswaSeeder: prodi_id & kolom EWS diisi untuk ' . count($assignments) . ' mahasiswa.');
    }
}
