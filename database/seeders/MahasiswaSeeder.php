<?php

namespace Database\Seeders;

use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;

class MahasiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed specific Mahasiswa for User ID 1984
        Mahasiswa::updateOrCreate(
            ['user_id' => 1984],
            [
                'nim' => 'A11.2020.12345',
                'status_mahasiswa' => 'Aktif',
                'telepon' => '089876543210',
            ]
        );
    }
}
