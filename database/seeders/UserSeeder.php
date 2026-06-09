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
     * Role EWS: mahasiswa | admin | super_fakultas | dosen
     * User existing di DB tidak diubah, hanya tambah user test EWS.
     */
    public function run(): void
    {
        $prodis = Prodi::all();

        // 1. Akun Admin per Prodi
        $adminEmails = [
            'A11' => 'admin_a11@ews.com',
            'A12' => 'admin_a12@ews.com',
            'A14' => 'admin_a14@ews.com',
            'A15' => 'admin_a15@ews.com',
        ];
        foreach ($adminEmails as $kodeProdi => $email) {
            $prodi = $prodis->firstWhere('kode_prodi', $kodeProdi);
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Admin {$kodeProdi} Test",
                    'password' => Hash::make('password'),
                    'prodi_id' => $prodi?->id,
                ]
            );
            if (! $user->hasRole('admin')) {
                $user->assignRole('admin');
            }
        }

        // 2. Akun Super Fakultas
        $superFakultas = User::firstOrCreate(
            ['email' => 'super_fakultas@ews.com'],
            [
                'name' => 'Super Fakultas EWS Test',
                'password' => Hash::make('password'),
                'prodi_id' => null,
            ]
        );
        if (! $superFakultas->hasRole('super_fakultas')) {
            $superFakultas->assignRole('super_fakultas');
        }

        // 3. Akun Mahasiswa test
        $prodiA11 = $prodis->firstWhere('kode_prodi', 'A11');
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

        // 4. Akun Dosen test per Prodi (6 dosen per prodi = 60 dosen untuk 10 prodi)
        //    Dosen punya role 'dosen', dipakai oleh DosenSeeder untuk bikin record dosen.
        //    Nama & email deterministic per prodi untuk konsistensi.
        foreach ($prodis as $prodi) {
            $dosenCount = 6;
            for ($i = 1; $i <= $dosenCount; $i++) {
                $email = "dosen_{$prodi->kode_prodi}_{$i}@ews.com";
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => "Dosen {$prodi->kode_prodi} {$i}",
                        'password' => Hash::make('password'),
                        'prodi_id' => $prodi->id,
                    ]
                );
                if (! $user->hasRole('dosen')) {
                    $user->assignRole('dosen');
                }
            }
        }

        $this->command->line('  admin_a11@ews.com, admin_a12@ews.com, admin_a14@ews.com, admin_a15@ews.com  / password  (role: admin)');
        $this->command->line('  super_fakultas@ews.com  / password  (role: super_fakultas)');
        $this->command->line('  mahasiswa@ews.com  / password  (role: mahasiswa)');
        $this->command->line('  dosen_<KODEPRODI>_<1-6>@ews.com  / password  (role: dosen)');
    }
}
