<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Koor (ID: 1982)
        $koor = User::updateOrCreate(
            ['id' => 1982],
            [
                'email' => 'koor@ews.com',
                'name' => 'Koordinator EWS',
                'password' => 'password',
            ]
        );
        $koor->assignRole('koor');

        // 2. Akun Dosen (ID: 1983)
        $dosen = User::updateOrCreate(
            ['id' => 1983],
            [
                'email' => 'dosen@ews.com',
                'name' => 'Dosen Penguji',
                'password' => 'password',
            ]
        );
        $dosen->assignRole('dosen');

        // 3. Akun Mahasiswa (ID: 1984)
        $mahasiswa = User::updateOrCreate(
            ['id' => 1984],
            [
                'email' => 'mahasiswa@ews.com',
                'name' => 'Mahasiswa Test',
                'password' => 'password',
            ]
        );
        $mahasiswa->assignRole('mahasiswa');
    }
}
