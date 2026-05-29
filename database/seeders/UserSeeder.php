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
     * Role EWS: mahasiswa | admin | super_fakultass
     * User existing di DB tidak diubah, hanya tambah user test EWS.
     */
    public function run(): void
    {
        $prodiA11 = Prodi::where('kode_prodi', 'A11')->first();
        $prodiA12 = Prodi::where('kode_prodi', 'A12')->first();
        $prodiA14 = Prodi::where('kode_prodi', 'A14')->first();
        $prodiA15 = Prodi::where('kode_prodi', 'A15')->first();

        // 1. Akun Admin (Kepala Program Studi) - A11
        $adminA11 = User::firstOrCreate(
            ['email' => 'admin_a11@ews.com'],
            [
                'name' => 'Admin TI Test',
                'password' => Hash::make('password'),
                'prodi_id' => $prodiA11?->id,
            ]
        );
        if (! $adminA11->hasRole('admin')) {
            $adminA11->assignRole('admin');
        }

        // Akun Admin - A12
        $adminA12 = User::firstOrCreate(
            ['email' => 'admin_a12@ews.com'],
            [
                'name' => 'Admin SI Test',
                'password' => Hash::make('password'),
                'prodi_id' => $prodiA12?->id,
            ]
        );
        if (! $adminA12->hasRole('admin')) {
            $adminA12->assignRole('admin');
        }

        // Akun Admin - A14
        $adminA14 = User::firstOrCreate(
            ['email' => 'admin_a14@ews.com'],
            [
                'name' => 'Admin DKV Test',
                'password' => Hash::make('password'),
                'prodi_id' => $prodiA14?->id,
            ]
        );
        if (! $adminA14->hasRole('admin')) {
            $adminA14->assignRole('admin');
        }

        // Akun Admin - A15
        $adminA15 = User::firstOrCreate(
            ['email' => 'admin_a15@ews.com'],
            [
                'name' => 'Admin Ilkom Test',
                'password' => Hash::make('password'),
                'prodi_id' => $prodiA15?->id,
            ]
        );
        if (! $adminA15->hasRole('admin')) {
            $adminA15->assignRole('admin');
        }

        // 2. Akun Super Fakultas
        $superFakultass = User::firstOrCreate(
            ['email' => 'super_fakultass@ews.com'],
            [
                'name' => 'Super Fakultas EWS Test',
                'password' => Hash::make('password'),
                'prodi_id' => null, // super_fakultass level fakultas
            ]
        );
        if (! $superFakultass->hasRole('super_fakultass')) {
            $superFakultass->assignRole('super_fakultass');
        }

        // 3. Akun Mahasiswa test
        // Role 'mahasiswa' sudah ada di DB existing (user_id 8, 9, dst.)
        // Kita seed 1 mahasiswa test EWS tambahan
        $mahasiswa = User::firstOrCreate(
            ['email' => 'mahasiswa@ews.com'],
            [
                'name' => 'Mahasiswa EWS Test',
                'password' => Hash::make('password'),
                'prodi_id' => $prodiA11?->id,
            ]
        );
        if (! $mahasiswa->hasRole('mahasiswa')) {
            $mahasiswa->assignRole('mahasiswa');
        }

        $this->command->line('  admin_a11@ews.com   / password  (role: admin - TI)');
        $this->command->line('  admin_a12@ews.com   / password  (role: admin - SI)');
        $this->command->line('  admin_a14@ews.com   / password  (role: admin - DKV)');
        $this->command->line('  admin_a15@ews.com   / password  (role: admin - Ilkom)');
        $this->command->line('  super_fakultass@ews.com / password  (role: super_fakultass)');
        $this->command->line('  mahasiswa@ews.com  / password  (role: mahasiswa)');
    }
}
