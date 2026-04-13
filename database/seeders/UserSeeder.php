<?php

namespace Database\Seeders;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed user test EWS per role.
     *
     * Role EWS: mahasiswa | kaprodi | dekan
     * User existing di DB tidak diubah, hanya tambah user test EWS.
     */
    public function run(): void
    {
        $prodiA11 = Prodi::where('kode_prodi', 'A11')->first();
        $prodiA12 = Prodi::where('kode_prodi', 'A12')->first();

        // 1. Akun Kaprodi (Kepala Program Studi) - A11
        $kaprodiA11 = User::firstOrCreate(
            ['email' => 'kaprodi_a11@ews.com'],
            [
                'name'     => 'Kaprodi TI Test',
                'password' => Hash::make('password'),
                'prodi_id' => $prodiA11?->id,
            ]
        );
        if (!$kaprodiA11->hasRole('kaprodi')) {
            $kaprodiA11->assignRole('kaprodi');
        }

        // Akun Kaprodi - A12
        $kaprodiA12 = User::firstOrCreate(
            ['email' => 'kaprodi_a12@ews.com'],
            [
                'name'     => 'Kaprodi SI Test',
                'password' => Hash::make('password'),
                'prodi_id' => $prodiA12?->id,
            ]
        );
        if (!$kaprodiA12->hasRole('kaprodi')) {
            $kaprodiA12->assignRole('kaprodi');
        }

        // 2. Akun Dekan
        $dekan = User::firstOrCreate(
            ['email' => 'dekan@ews.com'],
            [
                'name'     => 'Dekan EWS Test',
                'password' => Hash::make('password'),
                'prodi_id' => null, // dekan level fakultas
            ]
        );
        if (!$dekan->hasRole('dekan')) {
            $dekan->assignRole('dekan');
        }

        // 3. Akun Mahasiswa test
        // Role 'mahasiswa' sudah ada di DB existing (user_id 8, 9, dst.)
        // Kita seed 1 mahasiswa test EWS tambahan
        $mahasiswa = User::firstOrCreate(
            ['email' => 'mahasiswa@ews.com'],
            [
                'name'     => 'Mahasiswa EWS Test',
                'password' => Hash::make('password'),
                'prodi_id' => $prodiA11?->id,
            ]
        );
        if (!$mahasiswa->hasRole('mahasiswa')) {
            $mahasiswa->assignRole('mahasiswa');
        }

        $this->command->line('  kaprodi_a11@ews.com / password  (role: kaprodi - TI)');
        $this->command->line('  kaprodi_a12@ews.com / password  (role: kaprodi - SI)');
        $this->command->line('  dekan@ews.com       / password  (role: dekan)');
        $this->command->line('  mahasiswa@ews.com   / password  (role: mahasiswa)');
    }
}
